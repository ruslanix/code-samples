const pako = require("pako");
import { PayloadChunk, PayloadChunkRepos } from "../../clients";

/**
 * There are API Gateway Limits for WebSocket API
 * https://docs.aws.amazon.com/en_pv/apigateway/latest/developerguide/limits.html
 * > WebSocket frame size 32 KB
 * > Message payload size 128 KB
 *
 * If client payload size > 32 KB client will compress and send payload by chunk.
 * This utility class helps to work with compressed chunks.
 */
export class CompressionIn {

  /**
   * Client compress large payloads and send it by chunk.
   * Each chunk sent through one single Websocket.send(chunk) call.
   * This function eaither save chunk into db or in case if got last chunk - load all from db and decompress.
   * There might be situation when we got last chunk but previous chunks still not saved in db yet (they were sent as separate api calls)
   * - in this case we do sleep and recursively call this function again
   *
   * Returns:
   *   false - something wrong with meta or decoding problems
   *   true - chunk saved to db, waiting for other chunks
   *   String - decoded payload json string
   */
  async getPayload(meta: Record<string, any>, chunkRepos: PayloadChunkRepos, sleepDelay?: number ): Promise<string|boolean> {

    if (!this.validateCompressionMeta(meta)) {
      console.log("[Compression] compression meta is invalid");

      return false;
    }

    const numChunks = meta.numChunks;
    const currChunk: PayloadChunk = {
      payloadId: meta.payloadId,
      idx: meta.chunkIdx,
      data: meta.chunkData,
    };

    console.log(`[Compression] got chunk ${currChunk.payloadId} : ${currChunk.idx}`);

    let chunks: PayloadChunk[] = [];

    if (currChunk.idx === numChunks - 1) {
      // this is the last chunk of payload
      if (numChunks > 1) {
        // load prev chunks from db
        chunks = (await chunkRepos.findByPayloadId(currChunk.payloadId)).unwrapOrElse(e => {
          throw e;
        });
      }
      // add last chunk
      chunks.push(currChunk);
    } else {
      // save chunk into db
      await chunkRepos.add(currChunk);
    }

    if (chunks.length) {
      const currLength = chunks.length;
      const metaLength = numChunks;
      console.log("[Compression] Got all chunks. lenght: ", currLength, ". Length from meta: ", metaLength);

      if (currLength !== metaLength) {
        if (!sleepDelay) {
          sleepDelay = 500;
        }

        console.log(`[Compression] Looks like not all chunks saved in db, waiting for 500 ms and trying again ...`);
        if (sleepDelay > 30000) {
          console.log(`[Compression] Waiting too long, something wrong. Return false.`);
          return false;
        }
        await this.sleep(500);
        return this.getPayload(meta, chunkRepos, sleepDelay + 500);
      }

      const inflate = new pako.Inflate({
        to: "string"
      });
      chunks.forEach((c, idx) => {
        inflate.push(Buffer.from(c.data, "base64").toString(), idx === chunks.length - 1);
      });
      if (inflate.err) {
        console.error(inflate.msg);
        return false;
      }

      return inflate.result;
    }

    return true;
  }

  validateCompressionMeta(meta) {
    if (!meta
      || meta.payloadId === undefined
      || meta.numChunks === undefined
      || meta.chunkIdx === undefined
      || meta.chunkData === undefined) {
        return false;
    }

    return true;
  }

  private sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
  }
}
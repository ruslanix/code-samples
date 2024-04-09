const pako = require("pako");
import { Result } from "true-myth";
import { Exception } from "@company/cloud-stack-services-common";
import { WebsocketClient } from "../../clients";

/**
 * There are API Gateway Limits for WebSocket API
 * https://docs.aws.amazon.com/en_pv/apigateway/latest/developerguide/limits.html
 * > WebSocket frame size 32 KB
 * > Message payload size 128 KB
 *
 * If payload size > 32 KB then compress and send payload by chunk.
 */
export class CompressionOut {
  protected websocketClient: WebsocketClient;

  constructor(websocketClient: WebsocketClient) {
    this.websocketClient = websocketClient;
  }

  async sendPayload(connectionId: string, payload: Record<string, any>): Promise<Result<true, Exception>> {
    const action = payload.action;
    const documentUrn = payload.documentUrn;
    const payloadStr = JSON.stringify(payload);

    if (payloadStr.length < 31744) {
      return await this.websocketClient.send(connectionId, payload);
    }

    console.log("[Compression] Payload too big. Going to compress and split by chunks");

    // compress and send by chunks
    const chunks: string[] = [];
    const deflate = new pako.Deflate({
      chunkSize: 15360, // + ~50% for base64 in result should be < 32kb
      to: "string"
    });
    deflate.onData = (chunk: any) => chunks.push(Buffer.from(chunk).toString("base64"));
    deflate.push(payloadStr, true);
    const payloadId = connectionId
      + "."
      + String(Date.now())
      + "."
      + Math.random().toString(36).substring(7);

    console.log(`[Compression] Going to send ${chunks.length} chunks`);

    const promises = chunks.map(async (chunk, idx) => {
      return await this.websocketClient.send(connectionId, JSON.stringify({
        action: action,
        documentUrn: documentUrn,
        compression: {
          payloadId: payloadId,
          numChunks: chunks.length,
          chunkIdx: idx,
          chunkData: chunk
        }
      }));
    });

    const errResults = (await Promise.all(promises)).filter(r => r.isErr());

    if (errResults.length) {
      return errResults[0];
    }

    return Result.ok(true);
  }
}
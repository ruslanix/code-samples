import { APIGatewayProxyHandler } from "aws-lambda";
import { CollabDocument, getCollabDocumentRepos, getConnectedClientRepos, getPayloadChunkRepos } from "../clients";
import { CompressionIn } from "./utils/CompressionIn";

/**
 * Periodically connected clients save document state(checkpoint)
 * All newly joined clients will get document from this state + latest changes with higher version
 */
export const handler: APIGatewayProxyHandler = async (event, _context) => {
  console.log("Set document event");

  let params = JSON.parse(event.body);
  let payloadSize = Buffer.byteLength(event.body, "utf8");

  if (!params) {
    console.log("[setDocument] Can't unserialize body");
    return {
      statusCode: 400,
      body: JSON.stringify({ message: "Can't unserialize body" })
    };
  }

  if (params.compression) {
    console.log("[setDocument] Compression detected", {
      payloadId: params.compression.payloadId,
      chunkIdx: params.compression.chunkIdx,
      numChunks: params.compression.numChunks
    });

    const compression = new CompressionIn();
    const payload = await compression.getPayload(params.compression, getPayloadChunkRepos(event));

    if (typeof payload === "boolean" && !payload) {
      console.log("[setDocument] Compression meta is invalid or decompression error");
      return {
        statusCode: 400,
        body: JSON.stringify({ message: "Compression meta is invalid or decompression error" })
      };
    } else if (typeof payload === "boolean" && payload) {
      console.log("[setDocument] Stored payload chunk, waiting for all other chunks for processing ...");
      return {
        statusCode: 200,
        body: JSON.stringify({ message: "Store payload chunk, waiting for all other chunks for processing ..." })
      };
    } else {
      console.log("[setDocument] payload decompressed. Proceed ...");
      params = JSON.parse(payload as string);
      payloadSize = Buffer.byteLength((payload as string), "utf8");
    }
  }

  if (!params || !params.document
    || !params.document.urn || params.document.version === undefined || params.document.content === undefined) {
    console.log(`No document urn|version|payload provided.`, params);
    return {
      statusCode: 400,
      body: JSON.stringify({ message: "Expected JSON body with a document urn|version|payload string property" })
    };
  }

  const document: CollabDocument = {
    urn: params.document.urn,
    version: params.document.version,
    content: params.document.content,
  };
  const connectionId = event.requestContext.connectionId;

  // Check if user connected
  const isConnected = (await getConnectedClientRepos(event).getByDocAndConnection(document.urn, connectionId)).unwrapOr(undefined);
  if (!isConnected) {
    return {
      statusCode: 403,
      body: ""
    };
  }

  // Check document size
  if (payloadSize > parseInt(process.env.MAX_DOCUMENT_SIZE)) {
    console.log("Document too big. Disable collab");

    document.isCollabDisabled = true;
    await getCollabDocumentRepos(event).updateStatuses(document);

    return {
      statusCode: 400,
      body: JSON.stringify({ message: "Document too big. Disable collab" })
    };
  }

  // Add document if version ok
  await getCollabDocumentRepos(event).updateOrInsertVersion(document);

  return {
    statusCode: 200,
    body: ""
  };
};
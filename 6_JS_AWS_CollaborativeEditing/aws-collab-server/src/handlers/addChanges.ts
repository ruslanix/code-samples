import { APIGatewayProxyHandler } from "aws-lambda";

import {
  DocumentEditEvent,
  getDocumentEditEventRepos,
  getCollabDocumentRepos,
  getPayloadChunkRepos
} from "../clients";
import { CompressionIn } from "./utils/CompressionIn";

/**
 * Add document edit changes to db for futher broadcasting to connected clients.
 * Each change has an incremented version and there is specil logic that accepts only new version number
 */
export const handler: APIGatewayProxyHandler = async (event, _context) => {
  let params = JSON.parse(event.body);
  let payloadSize = Buffer.byteLength(event.body, "utf8");

  if (!params) {
    console.log("[addChanges] Can't unserialize body");
    return {
      statusCode: 400,
      body: JSON.stringify({ message: "Can't unserialize body" })
    };
  }

  if (params.compression) {
    console.log("[addChanges] Compression detected", {
      payloadId: params.compression.payloadId,
      chunkIdx: params.compression.chunkIdx,
      numChunks: params.compression.numChunks
    });

    const compression = new CompressionIn();
    const payload = await compression.getPayload(params.compression, getPayloadChunkRepos(event));

    if (typeof payload === "boolean" && !payload) {
      console.log("[addChanges] Compression meta is invalid or decompression error");
      return {
        statusCode: 400,
        body: JSON.stringify({ message: "Compression meta is invalid or decompression error" })
      };
    } else if (typeof payload === "boolean" && payload) {
      console.log("[addChanges] Stored payload chunk, waiting for all other chunks for processing ...");
      return {
        statusCode: 200,
        body: JSON.stringify({ message: "Store payload chunk, waiting for all other chunks for processing ..." })
      };
    } else {
      console.log("[addChanges] payload decompressed. Proceed ...");
      params = JSON.parse(payload as string);
      payloadSize = Buffer.byteLength((payload as string), "utf8");
    }
  }

  if (!params || !params.documentUrn || !params.steps || params.version === undefined) {
    return {
      statusCode: 400,
      body: JSON.stringify({ message: "Expected JSON body with a `documentUrn|steps|version` string property" })
    };
  }

  // Check document exists
  const documentRepos = getCollabDocumentRepos(event);
  const document = (await documentRepos.getByDocumentUrn(params.documentUrn)).unwrapOr(undefined);

  if (!document) {
    console.log(`[addChanges] Document not initialized yet (${params.documentUrn})`);
    return {
      statusCode: 400,
      body: JSON.stringify({ message: `[addChanges] Document not initialized yet (${params.documentUrn})` })
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

  const editEventsRepos = getDocumentEditEventRepos(event);
  const documentUrn = params.documentUrn;
  const connectionId = event.requestContext.connectionId;
  const currVersion = params.version;
  // generating new version
  const newVersion = currVersion + params.steps.length;

  // Check version
  // We can accept editor steps from client only if it has latest version and there are no greater or equal versionS in central authority
  // - else - client had to accept steps from central authority, merge with local steps and then try to submit again
  const graterVersions = (await editEventsRepos.findGreaterVersions(documentUrn, document.sessionId, currVersion)).unwrapOr([]);
  if (graterVersions.length > 0) {
    console.log("Failed to add documentEvent. Grater versions exists");
    return {
      statusCode: 409,
      body: ""
    };
  }

  // @TODO: check that client connected to document

  const documentEvent: DocumentEditEvent = {
    documentUrn: documentUrn,
    sessionVersion: `${document.sessionId}_${newVersion}`,
    version: newVersion,
    ttl: Date.now() / 1000 + 60 * 60 * 24,
    event: {
      steps: params.steps,
      clientID: params.clientID,
      connectionId: connectionId
    },
    date: (new Date().toISOString())
  };

  return (await editEventsRepos.add(documentEvent)).mapOrElse(
    err => {
      console.log(`Failed to add documentEvent [${connectionId}, ${documentEvent.documentUrn}, ${documentEvent.version}]`);
      if (err.cause.name === "ConditionalCheckFailedException") {
        console.log("ConditionalCheckFailedException - duplicate");
        return {
          statusCode: 409,
          body: ""
        };
      } else {
        console.error(err);
        return {
          statusCode: 500,
          body: JSON.stringify({message: err.message})
        };
      }
    },

    () => {
      return {
        statusCode: 200,
        body: JSON.stringify(documentEvent)
      };
    }
  );
};

import { APIGatewayProxyHandler } from "aws-lambda";
import {
  ConnectedClient,
  getConnectedClientRepos,
  CollabDocument,
  getCollabDocumentRepos ,
  getPayloadChunkRepos
} from "../clients";
import { CompressionIn } from "./utils/CompressionIn";
import * as jwt from "jsonwebtoken";

/**
 * Join collab and set initial document state/version if first collaborator
 */
export const handler: APIGatewayProxyHandler = async (event, _context) => {
  console.log("Join collab Received event:", JSON.stringify(event, undefined, 2));

  let params = JSON.parse(event.body);
  let payloadSize = Buffer.byteLength(event.body, "utf8");

  if (!params) {
    console.log("[JoinCollab] Can't unserialize body");
    return {
      statusCode: 400,
      body: JSON.stringify({ message: "Can't unserialize body" })
    };
  }

  if (params.compression) {
    console.log("[JoinCollab] Compression detected", {
      payloadId: params.compression.payloadId,
      chunkIdx: params.compression.chunkIdx,
      numChunks: params.compression.numChunks
    });

    const compression = new CompressionIn();
    const payload = await compression.getPayload(params.compression, getPayloadChunkRepos(event));

    if (typeof payload === "boolean" && !payload) {
      console.log("[JoinCollab] Compression meta is invalid or decompression error");
      return {
        statusCode: 400,
        body: JSON.stringify({ message: "Compression meta is invalid or decompression error" })
      };
    } else if (typeof payload === "boolean" && payload) {
      console.log("[JoinCollab] Stored payload chunk, waiting for all other chunks for processing ...");
      return {
        statusCode: 200,
        body: JSON.stringify({ message: "Store payload chunk, waiting for all other chunks for processing ..." })
      };
    } else {
      console.log("[JoinCollab] payload decompressed. Proceed ...");
      params = JSON.parse(payload as string);
      payloadSize = Buffer.byteLength((payload as string), "utf8");
    }
  }

  if (!params || !params.authToken || !params.document
    || !params.document.urn || params.document.version === undefined || params.document.content === undefined) {
    console.log(`Fail to join collab. No documentUrn|authToken|document provided.`, params);
    return {
      statusCode: 400,
      body: JSON.stringify({ message: "Expected JSON body with a `documentUrn` string property" })
    };
  }

  const document: CollabDocument = {
    urn: params.document.urn,
    version: params.document.version,
    content: params.document.content,
  };

  const connectionAuthTokenPayload = event.requestContext.authorizer ? event.requestContext.authorizer : undefined;
  if (!connectionAuthTokenPayload) {
    console.log("Fail to join collab. Connection Auth token payload not provided");
    return {
      statusCode: 401,
      body: JSON.stringify({ message: "Connection Auth token payload not provided" })
    };
  }

  try {
    verifyToken(connectionAuthTokenPayload, params.authToken, document.urn);
  } catch (e) {
    console.error(e);
    return {
      statusCode: 401,
      body: JSON.stringify({ message: "Unauthorized", error: e})
    };
  }

  // Check document size
  if (payloadSize > parseInt(process.env.MAX_DOCUMENT_SIZE)) {
    console.log("Document too big. Disable collab");

    document.isCollabDisabled = true;
    document.content = "";
  }

  // Add document if version ok
  await getCollabDocumentRepos(event).updateOrInsertVersion(document);

  // Add connected users
  const connectedClientsRepos = getConnectedClientRepos(event);
  const connectedClient: ConnectedClient = {
    connectionId: event.requestContext.connectionId,
    documentUrn: document.urn,
    identity: connectionAuthTokenPayload.sub,
    displayName: connectionAuthTokenPayload.name,
    cursorPosition: params.cursorPosition || 1
  };

  // In some cases `leaveCollab` or `disconnect` handlers might not be called and items stay in db
  // If user already connected - remove it from db first,
  // so dynamostream catch `insert` event and stream handler process joined user accordingly
  // (send him document and etc...)
  const clients = (await connectedClientsRepos.findByDocumentUrn(connectedClient.documentUrn))
  .unwrapOr([])
  .filter(client => client.connectionId === connectedClient.connectionId || client.identity === connectedClient.identity);

  if (clients && clients.length) {
    console.log("[JoinCollab] found already connected clients with same identity or connection id. Removing them first.");
    const promises = clients.map(async (client) => {
      return await connectedClientsRepos.remove(client);
    });

    try {
      await Promise.all(promises);
    } catch (e) {
      console.log("[JoinCollab] Exception during clients remove ", e.message);
    }
  }

  return (await connectedClientsRepos.add(connectedClient)).mapOrElse(
    err => {
      console.log(`Failed to add ConnectedClients [${connectedClient}]`);
      console.error(err);
      return {
        statusCode: 500,
        body: JSON.stringify({message: err.message})
      };
    },

    () => {
      console.log(`Add ConnectedClients ${connectedClient}`);
      return {
        statusCode: 200,
        body: JSON.stringify(connectedClient)
      };
    }
  );
};

const verifyToken = (connectionAuthTokenPayload, joinCollabAuthToken, documentUrn) => {

  const joinCollabAuthTokenPayload = jwt.decode(joinCollabAuthToken);
  if (!joinCollabAuthTokenPayload["iss"]
      || !joinCollabAuthTokenPayload["sub"]
      || !joinCollabAuthTokenPayload["name"]
      || !joinCollabAuthTokenPayload["aud"]) {
    console.log("Invalid join collab token -- missing iss|sub|aud|name");
    throw "Unauthorized";
  }

  jwt.verify(joinCollabAuthToken, process.env.JWT_TOKEN_SECRET);

  if (connectionAuthTokenPayload.iss !== joinCollabAuthTokenPayload["iss"]
    || connectionAuthTokenPayload.sub !== joinCollabAuthTokenPayload["sub"]) {
    console.log("Join collab token mismatch with connection token (wrong iss|sub)");
    throw "Unauthorized";
  }

  if (joinCollabAuthTokenPayload["aud"] !== documentUrn) {
    console.log("Join collab token `aud` mismatch with params `documentUrn`");
    throw "Unauthorized";
  }
};
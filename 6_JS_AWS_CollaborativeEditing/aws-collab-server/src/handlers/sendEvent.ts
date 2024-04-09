import { APIGatewayProxyHandler } from "aws-lambda";
import { getConnectedClientRepos, getWebsocketClient, ConnectedClient } from "../clients";

/**
 * Broadcast event to connected clients
 */
export const handler: APIGatewayProxyHandler = async (event, _context) => {
  console.log("Event handler");

  const params = JSON.parse(event.body);

  if (!params || !params.documentUrn || !params.type ) {
    console.log(`No urn|type provided.`, params);
    return {
      statusCode: 400,
      body: JSON.stringify({ message: "Expected JSON body with a document urn|type string property" })
    };
  }

  const connectedClientsRepos = getConnectedClientRepos(event);
  const currConnectionId = event.requestContext.connectionId;

  // Check if user connected
  const isConnected = (await connectedClientsRepos.getByDocAndConnection(params.documentUrn, currConnectionId)).unwrapOr(undefined);
  if (!isConnected) {
    console.log(`[sendEvent] client ${currConnectionId} not connected to document ${params.documentUrn} `);
    return {
      statusCode: 403,
      body: ""
    };
  }

  const connectedClients = (await connectedClientsRepos.findByDocumentUrn(params.documentUrn)).unwrapOrElse(e => {
    throw e;
  });

  console.log(`Going to notify ${connectedClients.length} collaborators for document ${params.documentUrn}`);

  const wsClient = getWebsocketClient(event);
  const payload = {
    action: "event",
    documentUrn: params.documentUrn,
    type: params.type,
    data: params.data
  };

  const wsPromises = connectedClients.map(async (connectedClient: ConnectedClient) => {
    if (connectedClient.connectionId === currConnectionId)
      return;

    return (await wsClient.send(connectedClient.connectionId, payload)).mapOrElse(
      err => {
        console.log(`Failed to notify connected client ${connectedClient.connectionId}`);
        console.error(err);
      },

      () => {
        console.log(`Connected client notified ${connectedClient.connectionId}`);
      }
    );
  });

  await Promise.all(wsPromises);

  return {
    statusCode: 200,
    body: ""
  };
};
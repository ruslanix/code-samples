import { APIGatewayProxyHandler } from "aws-lambda";
import { ConnectedClient, getConnectedClientRepos, getWebsocketClient } from "../clients";

export const handler: APIGatewayProxyHandler = async (event, _context) => {
  const params = JSON.parse(event.body);

  if (!params || !params.documentUrn) {
    return {
      statusCode: 400,
      body: JSON.stringify({ message: "Expected JSON body with a `documentUrn` string property" })
    };
  }

  const documentUrn = params.documentUrn;
  const connectionId = event.requestContext.connectionId;
  const connectedClientsRepos = getConnectedClientRepos(event);
  const websocketClient = getWebsocketClient(event);

  return (await connectedClientsRepos.findByDocumentUrn(documentUrn)).mapOrElse(
    async err => {
      console.log(`Failed to get ConnectedClients for document ${documentUrn}`);
      console.error(err);
      return {
        statusCode: 500,
        body: JSON.stringify({message: err.message})
      };
    },

    async connectedClients => {

      const users = connectedClients.map((connectedClient: ConnectedClient) => {
        return {
          connectionId: connectedClient.connectionId,
          identity: connectedClient.identity || undefined,
          displayName: connectedClient.displayName || undefined,
          cursorPosition: connectedClient.cursorPosition
        };
      });

      const payload = {
        action: "users",
        users: users
      };

      return (await websocketClient.send(connectionId, payload)).mapOrElse(
        err => {
          console.log(`Failed to send response to client ${connectionId}`);
          console.error(err);
          return {
            statusCode: 500,
            body: JSON.stringify({message: err.message})
          };
        },

        () => {
          return {
            statusCode: 200,
            body: ""
          };
        }
      );
    }
  );
};
import { APIGatewayProxyHandler } from "aws-lambda";
import { ConnectedClient, getConnectedClientRepos, getCollabDocumentRepos } from "../clients";

/**
 * Leave collab.
 * If there are not collaborators left - remove document from table.
 */
export const handler: APIGatewayProxyHandler = async (event, _context) => {
  const params = JSON.parse(event.body);

  if (!params || !params.documentUrn) {
    return {
      statusCode: 400,
      body: JSON.stringify({ message: "Expected JSON body with a `documentUrn` string property" })
    };
  }

  const connectedClientsRepos = getConnectedClientRepos(event);
  const collabDocumentRepos = getCollabDocumentRepos(event);
  const documentUrn = params.documentUrn;

  const connectedClient: ConnectedClient = {
    connectionId: event.requestContext.connectionId,
    documentUrn: documentUrn
  };

  (await connectedClientsRepos.remove(connectedClient)).mapOrElse(
    err => {
      console.log(`Failed to remove ConnectedClients [${connectedClient}]`);
      console.error(err);
      return false;
    },

    () => {
      return true;
    }
  );

  const connectedClients = (await connectedClientsRepos.findByDocumentUrn(documentUrn)).unwrapOr([]);
  if (!connectedClients.length) {
    console.log(`Document has no collaborators, remove it ${documentUrn}`);
    await collabDocumentRepos.remove(documentUrn);
  }

  return {
    statusCode: 200,
    body: JSON.stringify(connectedClient)
  };
};
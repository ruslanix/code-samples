import { APIGatewayProxyHandler } from "aws-lambda";
import { getConnectedClientRepos, getCollabDocumentRepos } from "../clients";

/**
 * Similar to leaveCollab but process all documents user was connected to
 */
export const handler: APIGatewayProxyHandler = async (event, _context) => {
  const connectionId = event.requestContext.connectionId;
  const connectedClientsRepos = getConnectedClientRepos(event);
  const collabDocumentRepos = getCollabDocumentRepos(event);

  // Get affected documents for possible cleanup
  const documentUrns = (await connectedClientsRepos.findByConnectionId(connectionId)).unwrapOr([]).map(c => c.documentUrn);

  (await connectedClientsRepos.removeAllByConnectionId(connectionId)).mapOrElse(
    err => {
      console.log(`Failed to remove ConnectedClient by connectionId [${event.requestContext.connectionId}]`);
      console.error(err);

      return false;
    },

    () => {
      return true;
    }
  );

  // Check if connections lefts for affected documents
  const cleanupPromises = documentUrns.map(async (documentUrn) => {
    const connectedClients = (await connectedClientsRepos.findByDocumentUrn(documentUrn)).unwrapOr([]);
    if (!connectedClients.length) {
      console.log(`Document has no collaborators, remove it ${documentUrn}`);
      return await collabDocumentRepos.remove(documentUrn);
    }
  });

  await Promise.all(cleanupPromises);

  return {
    statusCode: 200,
    body: ""
  };
};
import { APIGatewayProxyHandler } from "aws-lambda";
import {
  ConnectedClient,
  getConnectedClientRepos,
  getCollabDocumentRepos } from "../clients";

export const handler: APIGatewayProxyHandler = async (event, _context) => {
  const params = JSON.parse(event.body);

  if (!params || !params.documentUrn || !params.cursorPosition) {
    return {
      statusCode: 400,
      body: JSON.stringify({ message: "Expected JSON body with a `documentUrn|cursorPosition string property" })
    };
  }

  const documentRepos = getCollabDocumentRepos(event);
  const document = (await documentRepos.getByDocumentUrn(params.documentUrn)).unwrapOr(undefined);

  if (!document) {
    console.log(`[setCursorPosition] Document not initialized yet (${params.documentUrn})`);
    return {
      statusCode: 400,
      body: JSON.stringify({ message: `[setCursorPosition] Document not initialized yet (${params.documentUrn})` })
    };
  }

  const connectedClientRepos = getConnectedClientRepos(event);
  const connectedClient: ConnectedClient = {
    connectionId: event.requestContext.connectionId,
    documentUrn: params.documentUrn,
    cursorPosition: params.cursorPosition
  };

  return (await connectedClientRepos.update(connectedClient)).mapOrElse(
    err => {
      console.log(`Failed to update connected client cursor position [${connectedClient.connectionId}]`);
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
};
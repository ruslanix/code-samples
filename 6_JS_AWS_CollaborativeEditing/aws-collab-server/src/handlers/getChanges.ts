import { APIGatewayProxyHandler } from "aws-lambda";
import { DocumentEditEvent, getDocumentEditEventRepos, getCollabDocumentRepos, getWebsocketClient } from "../clients";
import { CompressionOut } from "./utils/CompressionOut";

export const handler: APIGatewayProxyHandler = async (event, _context) => {
  const params = JSON.parse(event.body);

  if (!params || !params.documentUrn) {
    return {
      statusCode: 400,
      body: JSON.stringify({ message: "Expected JSON body with a `documentUrn` string property" })
    };
  }

  const documentUrn = params.documentUrn;
  const versionFrom = params.versionFrom || 1;
  const editEventsRepos = getDocumentEditEventRepos(event);
  const documentRepos   = getCollabDocumentRepos(event);

  const document = (await documentRepos.getByDocumentUrn(documentUrn)).unwrapOr(undefined);
  if (!document) {
    console.log(`Can't find document ${documentUrn}`);
    return {
      statusCode: 404,
      body: ""
    };
  }

  const graterVersions = (await editEventsRepos.findGreaterVersions(documentUrn, document.sessionId, versionFrom)).unwrapOr([]);

  const compressionOut = new CompressionOut(getWebsocketClient(event));
  const connectionId = event.requestContext.connectionId;
  const payload = {
    action: "changes",
    documentUrn: documentUrn,
    changes: graterVersions.map((e: DocumentEditEvent) => {
      return {
        version: e.version,
        event: e.event
      };
    }),
    versionFrom: versionFrom
  };

  (await compressionOut.sendPayload(connectionId, payload)).mapOrElse(
    err => {
      console.log(`Failed to send changes to ${connectionId}`);
      console.error(err);
    },

    () => {
      console.log(`Send changes to ${connectionId}`);
    }
  );

  return {
    statusCode: 200,
    body: ""
  };
};
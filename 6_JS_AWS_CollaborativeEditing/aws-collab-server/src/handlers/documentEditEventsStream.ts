import { DynamoDBStreamHandler, DynamoDBStreamEvent, DynamoDBRecord } from "aws-lambda";
import { DynamoDB } from "aws-sdk";
import { ConnectedClient, getConnectedClientRepos, getCollabDocumentRepos, getWebsocketClient } from "../clients";
import { CompressionOut } from "./utils/CompressionOut";

/**
 * Broadcast edit changes to all clients
 */
export const handler: DynamoDBStreamHandler = async (event: DynamoDBStreamEvent, _context) => {

  console.log(`newDocumentEvents handler got ${event.Records.length} events to process`);

  const documentRepos = getCollabDocumentRepos(event);
  const connectedClientsRepos = getConnectedClientRepos(event);
  const compressionOut = new CompressionOut(getWebsocketClient(event));

  const records = event.Records
    .filter((record: DynamoDBRecord) => {
      return record.eventName === "INSERT"
        && record.dynamodb.NewImage
        && record.dynamodb.NewImage.documentUrn
        && record.dynamodb.NewImage.documentUrn.S;
    });

  // Load docs from db
  const documentsMap = {};
  const docPromises = records.map(async (record: DynamoDBRecord) => {
    const documentUrn = record.dynamodb.NewImage.documentUrn.S;
    if (documentsMap[documentUrn] !== undefined) {
      return;
    }

    return (await documentRepos.getByDocumentUrn(documentUrn)).mapOrElse(
      () => {
        documentsMap[documentUrn] = false;
      },
      document => {
        documentsMap[documentUrn] = document;
      }
    );
  });

  await Promise.all(docPromises);

  // Process changes
  // @TODO: merge records by documentUrn then notify clients with bulk changes
  const promises = event.Records
    .map( async (record: DynamoDBRecord) => {

      const documentEventsRecord = DynamoDB.Converter.unmarshall(record.dynamodb.NewImage);

      console.log(`Going to notify collaborators for document ${documentEventsRecord.documentUrn}`);

      const document = documentsMap[documentEventsRecord.documentUrn];
      if (!document) {
        console.log(`Document doesn't exist. Skip notifications`);
        return;
      }

      return (await connectedClientsRepos.findByDocumentUrn(documentEventsRecord.documentUrn)).mapOrElse(
        err => {
          console.log(`Failed to get ConnectedClients for document ${documentEventsRecord.documentUrn}`);
          console.error(err);
        },

        connectedClients => {
          // If collab disabled notify client with collab document status
          // Usually clients shouldn't broadcast their changes at this point
          // But just in case - notify them again
          const payload = document.isCollabDisabled
          ? {
              action: "document",
              documentUrn: document.urn,
              content: "",
              version: document.version,
              isCollabDisabled: true
            }
          : {
              action: "changes",
              documentUrn: documentEventsRecord.documentUrn,
              version: documentEventsRecord.version,
              event: documentEventsRecord.event
            };

          if (document.isCollabDisabled)
            console.log("Collab disabled. Notify clients with new collab disabled status.");

          const wsPromises = connectedClients.map(async (connectedClient: ConnectedClient) => {
            return (await compressionOut.sendPayload(connectedClient.connectionId, payload)).mapOrElse(
              err => {
                console.log(`Failed to notify connected client ${connectedClient.connectionId}`);
                // @TODO: maybe stalled connection => remove this connection from ConnectedClients
                console.error(err);
              },

              () => {
                console.log(`Connected client notified ${connectedClient.connectionId}`);
              }
            );
          });

          return Promise.all(wsPromises);
        }
      );
    });

  await Promise.all(promises);
};
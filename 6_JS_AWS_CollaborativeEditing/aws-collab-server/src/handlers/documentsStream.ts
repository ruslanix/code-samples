import { DynamoDBStreamHandler, DynamoDBStreamEvent, DynamoDBRecord } from "aws-lambda";
import { DynamoDB } from "aws-sdk";
import { ConnectedClient, getConnectedClientRepos, CollabDocument, getWebsocketClient } from "../clients";
import { CompressionOut } from "./utils/CompressionOut";

/**
 * Broadcast document status changes to all clients
 */
export const handler: DynamoDBStreamHandler = async (event: DynamoDBStreamEvent, _context) => {

  console.log(`documents stream handler got ${event.Records.length} events to process`);

  const docStatusChangePromises = event.Records
    .filter(record => {
      if (record.eventName !== "MODIFY")
        return false;

      const oldDisabled = record.dynamodb.OldImage.isCollabDisabled ? record.dynamodb.OldImage.isCollabDisabled.BOOL : undefined;
      const newDisabled = record.dynamodb.NewImage.isCollabDisabled ? record.dynamodb.NewImage.isCollabDisabled.BOOL : undefined;

      return oldDisabled !== newDisabled;
    })
    .map( async (record: DynamoDBRecord) => {
      if (!record.dynamodb.NewImage) {
        console.log(`Failed to process document event. New image is empty. ${record}`);
        return;
      }
      if (!record.dynamodb.NewImage.urn || !record.dynamodb.NewImage.urn.S) {
        console.log(`Failed to process document event. urn is empty. ${record}`);
        return;
      }

      const document: CollabDocument = (DynamoDB.Converter.unmarshall(record.dynamodb.NewImage) as CollabDocument);
      const connectedClientsRepos = getConnectedClientRepos(event);
      const compressionOut = new CompressionOut(getWebsocketClient(event));

      console.log(`Going to notify collaborators for document ${document.urn}`);

      return (await connectedClientsRepos.findByDocumentUrn(document.urn)).mapOrElse(
        err => {
          console.log(`Failed to get ConnectedClients for document ${document.urn}`);
          console.error(err);
        },

        connectedClients => {

          const payload = {
            action: "document",
            documentUrn: document.urn,
            content: "",
            version: document.version,
            isCollabDisabled: document.isCollabDisabled
          };

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

  await Promise.all(docStatusChangePromises);
};
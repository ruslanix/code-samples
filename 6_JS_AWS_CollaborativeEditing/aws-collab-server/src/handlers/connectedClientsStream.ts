import { DynamoDBStreamHandler, DynamoDBStreamEvent } from "aws-lambda";
import {
  ConnectedClient,
  getConnectedClientRepos,
  getCollabDocumentRepos,
  getWebsocketClient,
  getDocumentEditEventRepos,
  DocumentEditEvent
} from "../clients";
import { CompressionOut } from "./utils/CompressionOut";

/**
 * Process ConnectedClient table events.
 * 1. Notify connected user with document status, so user can
 * - update local document to collab version
 * - or if collab document is undefined - init it with local version
 *
 * 2. Notify clients about connected users so clients could
 * - show user list
 * - cursor positions
 */
export const handler: DynamoDBStreamHandler = async (event: DynamoDBStreamEvent, _context) => {

  console.log(`connectedClient stream handler got ${event.Records.length} events to process`);

  // collect documents with changed cursorPosition
  let touchedDocuments = [];
  const insertedClients = [];
  event.Records.forEach(record => {
    switch (record.eventName) {
      case "INSERT":
        touchedDocuments.push(record.dynamodb.NewImage.documentUrn.S);
        insertedClients.push({
          documentUrn: record.dynamodb.NewImage.documentUrn.S,
          connectionId: record.dynamodb.NewImage.connectionId.S
        });
        break;
      case "REMOVE":
        touchedDocuments.push(record.dynamodb.OldImage.documentUrn.S);
        break;
      case "MODIFY":
        const oldCursorPosition = record.dynamodb.OldImage.cursorPosition ? record.dynamodb.OldImage.cursorPosition.N : undefined;
        const newCursorPosition = record.dynamodb.NewImage.cursorPosition ? record.dynamodb.NewImage.cursorPosition.N : undefined;
        if (oldCursorPosition !== newCursorPosition) {
          touchedDocuments.push(record.dynamodb.NewImage.documentUrn.S);
        }
      }
  });

  const compressionOut = new CompressionOut(getWebsocketClient(event));

  // Send corresponding document status and latest changes to newly connected clients
  let promisesDoc = [];
  if (insertedClients) {
    const documentRepos = getCollabDocumentRepos(event);
    const editEventsRepos = getDocumentEditEventRepos(event);

    promisesDoc = insertedClients.map( async insertedClient => {
      const payload = await (await documentRepos.getByDocumentUrn(insertedClient.documentUrn)).mapOrElse(
        async () => {
          return {
            action: "document",
            content: undefined,
            version: undefined,
            isCollabDisabled: undefined
          };
        },

        async document => {
          const graterVersions = document.isCollabDisabled
            ? []
            : (await editEventsRepos.findGreaterVersions(document.urn, document.sessionId, document.version)).unwrapOr([]);

          return {
            action: "document",
            documentUrn: document.urn,
            content: document.content,
            version: document.version,
            isCollabDisabled: document.isCollabDisabled,
            changes: graterVersions.map((e: DocumentEditEvent) => {
              return {
                version: e.version,
                event: e.event
              };
            }),
          };
        }
      );
      return (await compressionOut.sendPayload(insertedClient.connectionId, payload)).mapOrElse(
        err => {
          console.log(`Failed to pass document status to connected client ${insertedClient.connectionId}`);
          // @TODO: maybe stalled connection => remove this connection from ConnectedClients
          console.error(err);
        },

        () => {
          console.log(`Send document status to connected client ${insertedClient.connectionId}`);
        }
      );

    });
  }

  await Promise.all(promisesDoc);

  // Send Users info to connected clients
  let promisesUserInfo = [];
  if (touchedDocuments) {
    touchedDocuments = [...new Set(touchedDocuments)];
    const connectedClientsRepos = getConnectedClientRepos(event);

    promisesUserInfo = touchedDocuments.map( async documentUrn => {
      return (await connectedClientsRepos.findByDocumentUrn(documentUrn)).mapOrElse(
        err => {
          console.log(`Failed to get ConnectedClients for document ${documentUrn}`);
          console.error(err);
          return;
        },

        connectedClients => {
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
            documentUrn: documentUrn,
            users: users
          };
          const wsPromises = connectedClients.map(async (connectedClient: ConnectedClient) => {
            return (await compressionOut.sendPayload(connectedClient.connectionId, payload)).mapOrElse(
              err => {
                console.log(`Failed to notify connected client ${connectedClient.connectionId}`);
                // @TODO: maybe stalled connection => remove this connection from ConnectedClients
                console.error(err);
              },

              () => {
                console.log(`Send users info to connected client ${connectedClient.connectionId}`);
              }
            );
          });

          return Promise.all(wsPromises);
        }
      );
    });
  }

  await Promise.all(promisesUserInfo);
};
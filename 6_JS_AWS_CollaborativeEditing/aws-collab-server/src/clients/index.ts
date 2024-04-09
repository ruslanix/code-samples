import { ConnectedClient, ConnectedClientRepos, ConnectedClientReposDynamo, ConnectedClientReposMemory } from "./ConnectedClientRepos";
import { DocumentEditEvent, DocumentEditEventRepos, DocumentEditEventReposDynamo, DocumentEditEventReposMemory } from "./DocumentEditEventRepos";
import { CollabDocument, DocumentRepos, DocumentReposDynamo, DocumentReposMemory } from "./CollabDocumentRepos";
import { PayloadChunk, PayloadChunkRepos, PayloadChunkReposDynamo, PayloadChunkReposMemory } from "./PayloadChunkRepos";
import { WebsocketClient, WebsocketClientApiGateway } from "./wsClient";
import { dynamoDbClient, apiGatewayClient } from "../aws";
import { APIGatewayEvent, DynamoDBStreamEvent } from "aws-lambda";

export { ConnectedClient,
  ConnectedClientRepos,
  ConnectedClientReposDynamo,
  ConnectedClientReposMemory,
  DocumentEditEvent,
  DocumentEditEventRepos,
  DocumentEditEventReposDynamo,
  DocumentEditEventReposMemory,
  CollabDocument,
  DocumentRepos,
  DocumentReposDynamo,
  DocumentReposMemory,
  PayloadChunk,
  PayloadChunkRepos,
  PayloadChunkReposDynamo,
  PayloadChunkReposMemory,
  WebsocketClient };


// In tests, we inject test repos into the event directly
type APIGatewayEventWithRepos = {
  __connectedClientRepos?: ConnectedClientRepos;
  __documentEditEventRepos?: DocumentEditEventRepos;
  __websocketClient?: WebsocketClient;
  __collabDocumentRepos?: DocumentRepos;
  __payloadChunkRepos: PayloadChunkRepos;
};

/**
 * Get a client repos instance for the given event.
 */
export function getConnectedClientRepos(event?: APIGatewayEvent | APIGatewayEventWithRepos| DynamoDBStreamEvent): ConnectedClientRepos {
  if (event && (event as APIGatewayEventWithRepos).__connectedClientRepos) {
    return (event as APIGatewayEventWithRepos).__connectedClientRepos;
  }
  return new ConnectedClientReposDynamo(dynamoDbClient, process.env.AWS_DYNAMODB_CONNECTED_CLIENTS_TABLE);
}

/**
 * Get a docoment edit event repos instance for the given event.
 */
export function getDocumentEditEventRepos(event?: APIGatewayEvent | APIGatewayEventWithRepos| DynamoDBStreamEvent): DocumentEditEventRepos {
  if (event && (event as APIGatewayEventWithRepos).__documentEditEventRepos) {
    return (event as APIGatewayEventWithRepos).__documentEditEventRepos;
  }
  return new DocumentEditEventReposDynamo(dynamoDbClient, process.env.AWS_DYNAMODB_DOCUMENT_EDIT_EVENTS_TABLE);
}

/**
 * Get a document repos instance for the given event.
 */
export function getCollabDocumentRepos(event?: APIGatewayEvent | APIGatewayEventWithRepos| DynamoDBStreamEvent): DocumentRepos {
  if (event && (event as APIGatewayEventWithRepos).__collabDocumentRepos) {
    return (event as APIGatewayEventWithRepos).__collabDocumentRepos;
  }
  return new DocumentReposDynamo(dynamoDbClient, process.env.AWS_DYNAMODB_DOCUMENTS_TABLE);
}

/**
 * Get a payload chunks repos instance for the given event.
 */
export function getPayloadChunkRepos(event?: APIGatewayEvent | APIGatewayEventWithRepos| DynamoDBStreamEvent): PayloadChunkRepos {
  if (event && (event as APIGatewayEventWithRepos).__payloadChunkRepos) {
    return (event as APIGatewayEventWithRepos).__payloadChunkRepos;
  }
  return new PayloadChunkReposDynamo(dynamoDbClient, process.env.AWS_DYNAMODB_PAYLOAD_CHUNKS_TABLE);
}

/**
 * Get a websocket api client instance for the given event.
 */
export function getWebsocketClient(event?: APIGatewayEvent| APIGatewayEventWithRepos| DynamoDBStreamEvent): WebsocketClient {
  if (event && (event as APIGatewayEventWithRepos).__websocketClient) {
    return (event as APIGatewayEventWithRepos).__websocketClient;
  }
  return new WebsocketClientApiGateway(apiGatewayClient);
}

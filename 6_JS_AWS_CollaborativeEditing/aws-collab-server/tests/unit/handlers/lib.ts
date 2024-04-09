import { executeLambdaHandler, ExecLambdaConfig, executeDynamoStreamLambdaHandler, ExecDynamoStreamLambdaConfig } from "@company/cloud-stack-services-common-test/src/aws";
import {
  ConnectedClient,
  ConnectedClientReposMemory,
  DocumentEditEvent,
  DocumentEditEventReposMemory,
  CollabDocument,
  DocumentReposMemory,
  PayloadChunkReposMemory,
  WebsocketClient } from "../../../src/clients";
import uuidv4 from "uuid/v4";
import randomatic from "randomatic";

export function createConnectedClient(props): ConnectedClient {
  return {
    documentUrn: uuidv4(),
    connectionId: uuidv4(),
    displayName: randomatic("Aa0", 30),
    identity: uuidv4(),
    cursorPosition: 100,
    ... props
  };
}
export const testConnectedClientRepos  = new ConnectedClientReposMemory();

export function createDocumentEditEvent(props): DocumentEditEvent {
  const version = props.version ? props.version : 1;
  const collabSessionId = props.collabSessionId ? props.collabSessionId : String(Date.now());
  delete props.collabSessionId;

  return {
    documentUrn: uuidv4(),
    sessionVersion: `${collabSessionId}_${version}`,
    version: version,
    date: Date.now() / 1000 + 60 * 60 * 24,
    event: {
      clientId: 1,
      steps: [1, 2, 3]
    },
    ttl: 1000,
    ... props
  };
}
export const testDocumentEditEventRepos  = new DocumentEditEventReposMemory();

export function createDocument(props): CollabDocument {
  return {
    urn: uuidv4(),
    content: "test",
    sessionId: String(Date.now()),
    version: 1,
    ... props
  };
}
export const testDocumentRepos  = new DocumentReposMemory();

export const testPayloadChunkRepos  = new PayloadChunkReposMemory();

export const mockWSClient_send = jest.fn();

const testWebsocketClient: WebsocketClient = {
  send: mockWSClient_send
};

export async function executeHandler(config: ExecLambdaConfig) {
  return executeLambdaHandler({
    ...config,
    eventExtra: {
      __connectedClientRepos: testConnectedClientRepos,
      __websocketClient: testWebsocketClient,
      __collabDocumentRepos: testDocumentRepos,
      __documentEditEventRepos: testDocumentEditEventRepos,
      __payloadChunkRepos: testPayloadChunkRepos,
      ...(config.eventExtra || {})
    }
  });
}

export async function executeDynamoStreamHandler(config: ExecDynamoStreamLambdaConfig) {
  return executeDynamoStreamLambdaHandler({
    ...config,
    eventExtra: {
      __connectedClientRepos: testConnectedClientRepos,
      __websocketClient: testWebsocketClient,
      __collabDocumentRepos: testDocumentRepos,
      __documentEditEventRepos: testDocumentEditEventRepos,
      ...(config.eventExtra || {})
    }
  });
}

export function createDocumentEventsStreamEventRecord(documentUrn, eventType, event) {
  // check @company/cloud-stack-services-common-test/src/aws-events for events templates
  return {
    "eventID": "1",
    "eventVersion": "1.0",
    "dynamodb": {
        "Keys": {
            "Id": {
                "documentUrn": documentUrn
            }
        },
        "NewImage": {
            "documentUrn": {
                "S": "document:A"
            },
            "eventType": {
                "S": eventType
            },
            "event": {
              "S": event
            },
        },
        "StreamViewType": "NEW_AND_OLD_IMAGES",
        "SequenceNumber": "111",
        "SizeBytes": 26
    },
    "awsRegion": "us-west-2",
    "eventName": "INSERT",
    "eventSourceARN": "arn:aws:dynamodb:us-east-1:123456789012:table/document-events",
    "eventSource": "aws:dynamodb"
  };
}

export function createConnectedClientsStreamInsertEventRecord(connectionId, documentUrn) {
  // check @company/cloud-stack-services-common-test/src/aws-events for events templates
  return {
    "eventID": "1",
    "eventVersion": "1.0",
    "dynamodb": {
        "Keys": {
            "Id": {
                "connectionId": connectionId,
                "documentUrn": documentUrn
            }
        },
        "NewImage": {
            "connectionId": {
              "S": connectionId
            },
            "documentUrn": {
                "S": documentUrn
            },
            "identity": {
                "S": "user:identity"
            },
            "displayName": {
              "S": "user:name"
            },
            "cursorPosition": {
              "N": 1
            },
        },
        "StreamViewType": "NEW_AND_OLD_IMAGES",
        "SequenceNumber": "111",
        "SizeBytes": 26
    },
    "awsRegion": "us-west-2",
    "eventName": "INSERT",
    "eventSourceARN": "arn:aws:dynamodb:us-east-1:123456789012:table/document-events",
    "eventSource": "aws:dynamodb"
  };
}

export function createDocumentStatusChangeEventRecord(document: CollabDocument, newIsCollabDisabled) {
  // check @company/cloud-stack-services-common-test/src/aws-events for events templates
  return {
    "eventID": "1",
    "eventVersion": "1.0",
    "dynamodb": {
        "Keys": {
            "Id": {
                "urn": document.urn
            }
        },
        "OldImage": {
          "urn": {
              "S": document.urn
          },
          "isCollabDisabled": {
              "BOOL": document.isCollabDisabled
          }
        },
        "NewImage": {
            "urn": {
                "S": document.urn
            },
            "isCollabDisabled": {
                "BOOL": newIsCollabDisabled
            }
        },
        "StreamViewType": "NEW_AND_OLD_IMAGES",
        "SequenceNumber": "111",
        "SizeBytes": 26
    },
    "awsRegion": "us-west-2",
    "eventName": "MODIFY",
    "eventSourceARN": "arn:aws:dynamodb:us-east-1:123456789012:table/documents",
    "eventSource": "aws:dynamodb"
  };
}
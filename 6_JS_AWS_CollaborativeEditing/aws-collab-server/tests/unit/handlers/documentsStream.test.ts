import {
  executeDynamoStreamHandler,
  testConnectedClientRepos,
  createDocument,
  testDocumentRepos,
  mockWSClient_send,
  createDocumentStatusChangeEventRecord } from "./lib";
import { handler } from "../../../src/handlers/documentsStream";
import { ConnectedClient, CollabDocument } from "../../../src/clients";
import { Result } from "true-myth";

test("Test document status NOT changed and clients should not be notified", async () => {

  // GIVEN
  const connectedClient1: ConnectedClient = {
    connectionId: "connection:A",
    documentUrn: "document:A"
  };
  const connectedClient2: ConnectedClient = {
    connectionId: "connection:B",
    documentUrn: "document:A"
  };
  testConnectedClientRepos.reset();
  testConnectedClientRepos.add(connectedClient1);
  testConnectedClientRepos.add(connectedClient2);

  const documentA: CollabDocument = createDocument({urn: "document:A"});
  testDocumentRepos.reset();
  testDocumentRepos.add(documentA);

  mockWSClient_send.mockReset();
  mockWSClient_send.mockResolvedValue(Result.ok(true));

  // WHEN
  await executeDynamoStreamHandler({
    handler,
    eventExtra: {
      "Records": [
        createDocumentStatusChangeEventRecord(documentA, documentA.isCollabDisabled)
      ]
    }
  });

  // THEN
  expect(mockWSClient_send.mock.calls.length).toBe(0);
});

test("Test document isCollabDisabled status changed and clients should be notified", async () => {

  // GIVEN
  const connectedClient1: ConnectedClient = {
    connectionId: "connection:A",
    documentUrn: "document:A"
  };
  const connectedClient2: ConnectedClient = {
    connectionId: "connection:B",
    documentUrn: "document:A"
  };
  testConnectedClientRepos.reset();
  testConnectedClientRepos.add(connectedClient1);
  testConnectedClientRepos.add(connectedClient2);

  const documentA: CollabDocument = createDocument({urn: "document:A"});
  testDocumentRepos.reset();
  testDocumentRepos.add(documentA);

  mockWSClient_send.mockReset();
  mockWSClient_send.mockResolvedValue(Result.ok(true));

  // WHEN
  await executeDynamoStreamHandler({
    handler,
    eventExtra: {
      "Records": [
        createDocumentStatusChangeEventRecord(documentA, true)
      ]
    }
  });

  // THEN
  expect(mockWSClient_send.mock.calls.length).toBe(2);

  expect(mockWSClient_send.mock.calls[0][0]).toBe("connection:A");
  expect(mockWSClient_send.mock.calls[0][1]["action"]).toBe("document");
  expect(mockWSClient_send.mock.calls[0][1]["isCollabDisabled"]).toBe(true);

  expect(mockWSClient_send.mock.calls[1][0]).toBe("connection:B");
  expect(mockWSClient_send.mock.calls[1][1]["action"]).toBe("document");
  expect(mockWSClient_send.mock.calls[1][1]["isCollabDisabled"]).toBe(true);
});


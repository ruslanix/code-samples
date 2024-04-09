import {
  executeDynamoStreamHandler,
  testConnectedClientRepos,
  createDocument,
  testDocumentRepos,
  mockWSClient_send,
  createConnectedClientsStreamInsertEventRecord } from "./lib";
import { handler } from "../../../src/handlers/connectedClientsStream";
import { ConnectedClient, CollabDocument } from "../../../src/clients";
import { Result } from "true-myth";

test("Test connected clients events stream", async () => {

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
        createConnectedClientsStreamInsertEventRecord("connection:A", "document:A")
      ]
    }
  });

  // THEN
  expect(mockWSClient_send.mock.calls.length).toBe(3); // notify doc status + 2 * users info

  expect(mockWSClient_send.mock.calls[0][0]).toBe("connection:A");
  expect(mockWSClient_send.mock.calls[0][1]["action"]).toBe("document");
  expect(mockWSClient_send.mock.calls[0][1]["content"]).toBe(documentA.content);
  expect(mockWSClient_send.mock.calls[0][1]["version"]).toBe(documentA.version);

  expect(mockWSClient_send.mock.calls[1][0]).toBe("connection:A");
  expect(mockWSClient_send.mock.calls[1][1]["action"]).toBe("users");

  expect(mockWSClient_send.mock.calls[2][0]).toBe("connection:B");
  expect(mockWSClient_send.mock.calls[2][1]["action"]).toBe("users");
});

test("Test connected clients events stream with undefined document", async () => {

  // GIVEN
  const connectedClient1: ConnectedClient = {
    connectionId: "connection:A",
    documentUrn: "document:A"
  };
  testConnectedClientRepos.reset();
  testConnectedClientRepos.add(connectedClient1);

  testDocumentRepos.reset();

  mockWSClient_send.mockReset();
  mockWSClient_send.mockResolvedValue(Result.ok(true));

  // WHEN
  await executeDynamoStreamHandler({
    handler,
    eventExtra: {
      "Records": [
        createConnectedClientsStreamInsertEventRecord("connection:A", "document:A")
      ]
    }
  });

  // THEN
  expect(mockWSClient_send.mock.calls.length).toBe(2); // notify doc status + users info

  expect(mockWSClient_send.mock.calls[0][0]).toBe("connection:A");
  expect(mockWSClient_send.mock.calls[0][1]["action"]).toBe("document");
  expect(mockWSClient_send.mock.calls[0][1]["content"]).toBe(undefined);
  expect(mockWSClient_send.mock.calls[0][1]["version"]).toBe(undefined);

  expect(mockWSClient_send.mock.calls[1][0]).toBe("connection:A");
  expect(mockWSClient_send.mock.calls[1][1]["action"]).toBe("users");
});

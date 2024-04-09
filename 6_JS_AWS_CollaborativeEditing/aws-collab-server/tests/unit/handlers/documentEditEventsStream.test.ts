import { executeDynamoStreamHandler,
  testConnectedClientRepos,
  mockWSClient_send,
  createDocumentEventsStreamEventRecord,
  createDocument,
  testDocumentRepos
} from "./lib";
import { handler } from "../../../src/handlers/documentEditEventsStream";
import { ConnectedClient, CollabDocument } from "../../../src/clients";
import { Result } from "true-myth";

test("Test document events stream", async () => {

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
        createDocumentEventsStreamEventRecord("document:A", "paste", "paste"),
        createDocumentEventsStreamEventRecord("document:A", "paste", "paste")
      ]
    }
  });

  // THEN
  expect(mockWSClient_send.mock.calls.length).toBe(4);
  expect(mockWSClient_send.mock.calls[0][0]).toBe("connection:A");
  expect(mockWSClient_send.mock.calls[0][1]["action"]).toBe("changes");

  expect(mockWSClient_send.mock.calls[1][0]).toBe("connection:B");
  expect(mockWSClient_send.mock.calls[1][1]["action"]).toBe("changes");
});

test("Test document events stream when document doesn't exist", async () => {

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

  testDocumentRepos.reset();

  mockWSClient_send.mockReset();
  mockWSClient_send.mockResolvedValue(Result.ok(true));

  // WHEN
  await executeDynamoStreamHandler({
    handler,
    eventExtra: {
      "Records": [
        createDocumentEventsStreamEventRecord("document:A", "paste", "paste"),
        createDocumentEventsStreamEventRecord("document:A", "paste", "paste")
      ]
    }
  });

  // THEN
  expect(mockWSClient_send.mock.calls.length).toBe(0);
});

test("Test document events stream when collab disabled", async () => {

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

  const documentA: CollabDocument = createDocument({urn: "document:A", isCollabDisabled: true});
  testDocumentRepos.reset();
  testDocumentRepos.add(documentA);

  mockWSClient_send.mockReset();
  mockWSClient_send.mockResolvedValue(Result.ok(true));

  // WHEN
  await executeDynamoStreamHandler({
    handler,
    eventExtra: {
      "Records": [
        createDocumentEventsStreamEventRecord("document:A", "paste", "paste"),
        createDocumentEventsStreamEventRecord("document:A", "paste", "paste")
      ]
    }
  });

  // THEN
  expect(mockWSClient_send.mock.calls.length).toBe(4);

  expect(mockWSClient_send.mock.calls[0][0]).toBe("connection:A");
  expect(mockWSClient_send.mock.calls[0][1]["action"]).toBe("document");
  expect(mockWSClient_send.mock.calls[0][1]["isCollabDisabled"]).toBe(true);

  expect(mockWSClient_send.mock.calls[1][0]).toBe("connection:B");
  expect(mockWSClient_send.mock.calls[1][1]["action"]).toBe("document");
  expect(mockWSClient_send.mock.calls[1][1]["isCollabDisabled"]).toBe(true);
});


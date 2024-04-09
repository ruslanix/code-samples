import {
  executeHandler,
  testConnectedClientRepos,
  createDocument,
  testDocumentRepos,
  mockWSClient_send } from "./lib";
import { ConnectedClient, CollabDocument } from "../../../src/clients";
import { handler } from "../../../src/handlers/sendEvent";
import { Result } from "true-myth";

test("Test send event", async () => {

  // GIVEN
  const documentA: CollabDocument = createDocument({urn: "document:A"});
  testDocumentRepos.reset();
  testDocumentRepos.add(documentA);

  const connectedClientA: ConnectedClient = {
    connectionId: "connection:A",
    documentUrn: "document:A",
    cursorPosition: 1
  };
  const connectedClientB: ConnectedClient = {
    connectionId: "connection:B",
    documentUrn: "document:A",
    cursorPosition: 1
  };
  const connectedClientC: ConnectedClient = {
    connectionId: "connection:C",
    documentUrn: "document:A",
    cursorPosition: 1
  };
  testConnectedClientRepos.reset();
  testConnectedClientRepos.add(connectedClientA);
  testConnectedClientRepos.add(connectedClientB);
  testConnectedClientRepos.add(connectedClientC);

  mockWSClient_send.mockReset();
  mockWSClient_send.mockResolvedValue(Result.ok(true));

  // WHEN
  await executeHandler({
    handler,
    body: {
      documentUrn: "document:A",
      type: "persisted"
    },
    eventExtra: {
      requestContext: {
        connectionId: "connection:A"
      }
    }
  });

  // THEN
  expect(mockWSClient_send.mock.calls.length).toBe(2);

  expect(mockWSClient_send.mock.calls[0][0]).toBe("connection:B");
  expect(mockWSClient_send.mock.calls[0][1]["action"]).toBe("event");
  expect(mockWSClient_send.mock.calls[0][1]["type"]).toBe("persisted");
});

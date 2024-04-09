import {
  executeHandler,
  testConnectedClientRepos,
  createDocument,
  testDocumentRepos } from "./lib";
import { ConnectedClient, CollabDocument } from "../../../src/clients";
import { handler } from "../../../src/handlers/setCursorPosition";

test("Test set cursor position", async () => {

  // GIVEN
  const documentA: CollabDocument = createDocument({urn: "document:A"});
  testDocumentRepos.reset();
  testDocumentRepos.add(documentA);

  const connectedClient: ConnectedClient = {
    connectionId: "connection:A",
    documentUrn: "document:A",
    cursorPosition: 1
  };
  testConnectedClientRepos.reset();
  testConnectedClientRepos.add(connectedClient);

  // WHEN
  await executeHandler({
    handler,
    body: {
      documentUrn: "document:A",
      cursorPosition: 2
    },
    eventExtra: {
      requestContext: {
        connectionId: "connection:A"
      }
    }
  });

  // THEN
  const res = await testConnectedClientRepos.findByDocumentUrn("document:A");
  expect(res.isOk()).toBe(true);
  res.map(data => {
    expect(data.length).toBe(1);
    expect(data[0]["cursorPosition"]).toBe(2);
  });
});

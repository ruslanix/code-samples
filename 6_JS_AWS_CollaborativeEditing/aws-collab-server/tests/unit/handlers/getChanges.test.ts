import {
  executeHandler,
  testDocumentEditEventRepos,
  createDocumentEditEvent,
  createDocument,
  testDocumentRepos,
  mockWSClient_send } from "./lib";
import { CollabDocument } from "../../../src/clients";
import { handler } from "../../../src/handlers/getChanges";
import { Result } from "true-myth";

test("Test getChanges", async () => {

  // GIVEN
  const documentA: CollabDocument = createDocument({urn: "document:A"});
  testDocumentRepos.reset();
  testDocumentRepos.add(documentA);

  testDocumentEditEventRepos.reset();
  testDocumentEditEventRepos.add(createDocumentEditEvent({documentUrn: "document:A", version: 1, collabSessionId: documentA.sessionId}));
  testDocumentEditEventRepos.add(createDocumentEditEvent({documentUrn: "document:A", version: 2, collabSessionId: documentA.sessionId}));
  testDocumentEditEventRepos.add(createDocumentEditEvent({documentUrn: "document:A", version: 3, collabSessionId: documentA.sessionId}));
  testDocumentEditEventRepos.add(createDocumentEditEvent({documentUrn: "document:B", version: 1, collabSessionId: documentA.sessionId}));

  mockWSClient_send.mockResolvedValue(Result.ok(true));

  // WHEN
  await executeHandler({
    handler,
    body: {
      documentUrn: "document:A",
      versionFrom: 1
    },
    eventExtra: {
      requestContext: {
        connectionId: "connection:A"
      }
    }
  });

  // THEN
  expect(mockWSClient_send.mock.calls.length).toBe(1);
  expect(mockWSClient_send.mock.calls[0][0]).toBe("connection:A");
  expect(mockWSClient_send.mock.calls[0][1]["action"]).toBe("changes");
  expect(mockWSClient_send.mock.calls[0][1]["documentUrn"]).toBe("document:A");
  expect(mockWSClient_send.mock.calls[0][1]["changes"].length).toBe(2);
});

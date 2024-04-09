import {
  executeHandler,
  testDocumentEditEventRepos,
  createDocumentEditEvent,
  createDocument,
  testDocumentRepos,
  testPayloadChunkRepos } from "./lib";
import { CollabDocument } from "../../../src/clients";
import { handler } from "../../../src/handlers/addChanges";
const pako = require("pako");

test("Test add changes", async () => {

  // GIVEN
  const documentA: CollabDocument = createDocument({urn: "document:A"});
  testDocumentRepos.reset();
  testDocumentRepos.add(documentA);

  testDocumentEditEventRepos.reset();
  testDocumentEditEventRepos.add(createDocumentEditEvent({documentUrn: "document:A", version: 1, collabSessionId: documentA.sessionId}));
  testDocumentEditEventRepos.add(createDocumentEditEvent({documentUrn: "document:A", version: 2, collabSessionId: documentA.sessionId}));

  // WHEN
  await executeHandler({
    handler,
    body: {
      documentUrn: "document:A",
      version: 2,
      steps: [5, 6, 7],
      clientID: 123
    },
    eventExtra: {
      requestContext: {
        connectionId: "connection:A"
      }
    }
  });

  // THEN
  const res = await testDocumentEditEventRepos.findGreaterVersions(documentA.urn, documentA.sessionId, 1);
  expect(res.isOk()).toBe(true);
  res.map(data => {
    expect(data.length).toBe(2);
    expect(data[1]["version"]).toBe(5);
  });
});

test("Test add too big changes", async () => {

  // GIVEN
  const documentA: CollabDocument = createDocument({urn: "document:A"});
  testDocumentRepos.reset();
  testDocumentRepos.add(documentA);

  testDocumentEditEventRepos.reset();
  testDocumentEditEventRepos.add(createDocumentEditEvent({documentUrn: "document:A", version: 1, collabSessionId: documentA.sessionId}));

  // WHEN
  await executeHandler({
    handler,
    body: {
      documentUrn: "document:A",
      version: 2,
      steps: [5, 6, 7, "T".repeat(parseInt(process.env.MAX_DOCUMENT_SIZE) + 1)],
      clientID: 123
    },
    eventExtra: {
      requestContext: {
        connectionId: "connection:A"
      }
    }
  });

  // THEN
  const res = await testDocumentEditEventRepos.findGreaterVersions(documentA.urn, documentA.sessionId, 1);
  expect(res.isOk()).toBe(true);
  res.map(data => {
    expect(data.length).toBe(0);
  });

  const doc = await testDocumentRepos.getByDocumentUrn("document:A");
  expect(doc.isOk()).toBe(true);
  doc.map(data => {
    expect(data.isCollabDisabled).toBe(true);
  });
});

test("Test add changes compressed", async () => {

  // GIVEN
  const documentA: CollabDocument = createDocument({urn: "document:A"});
  testDocumentRepos.reset();
  testDocumentRepos.add(documentA);

  testDocumentEditEventRepos.reset();
  testDocumentEditEventRepos.add(createDocumentEditEvent({documentUrn: "document:A", version: 1, collabSessionId: documentA.sessionId}));
  testDocumentEditEventRepos.add(createDocumentEditEvent({documentUrn: "document:A", version: 2, collabSessionId: documentA.sessionId}));

  testPayloadChunkRepos.reset();

  // compress payload
  const payload = {
    documentUrn: "document:A",
    version: 2,
    steps: [5, 6, 7],
    clientID: 123
  };
  const payloadString = JSON.stringify(payload);
  const chunks = [];
  const deflate = new pako.Deflate({
    chunkSize: 20,
    to: "string"
  });
  deflate.onData = (chunk) => chunks.push(Buffer.from(chunk).toString("base64"));
  deflate.push(payloadString, true);

  console.log("chunks", chunks);

  const promises = chunks.map(async (chunk, idx) => {
    return await executeHandler({
      handler,
      body: {
        compression: {
          payloadId: "123",
          numChunks: chunks.length,
          chunkIdx: idx,
          chunkData: chunk
        }
      },
      eventExtra: {
        requestContext: {
          connectionId: "connection:A"
        }
      }
    });
  });

  await Promise.all(promises);

  // THEN
  const res = await testDocumentEditEventRepos.findGreaterVersions(documentA.urn, documentA.sessionId, 1);
  expect(res.isOk()).toBe(true);
  res.map(data => {
    expect(data.length).toBe(2);
    expect(data[1]["version"]).toBe(5);
  });

  const records = await testPayloadChunkRepos.findByPayloadId("123");
  expect(records.isOk()).toBe(true);
  records.map(data => {
    expect(data.length).toBe(chunks.length - 1);
  });
});

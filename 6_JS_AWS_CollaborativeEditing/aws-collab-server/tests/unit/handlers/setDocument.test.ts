import { executeHandler, testConnectedClientRepos, testDocumentRepos, createDocument, testPayloadChunkRepos } from "./lib";
import { handler } from "../../../src/handlers/setDocument";
import { ConnectedClient, CollabDocument } from "../../../src/clients";
const pako = require("pako");

test("Test set document", async () => {

  testDocumentRepos.reset();
  testConnectedClientRepos.reset();
  const connectedClient: ConnectedClient = {
    connectionId: "connection:A",
    documentUrn: "document:A"
  };
  testConnectedClientRepos.add(connectedClient);

  await executeHandler({
    handler,
    body: {
      document: {
        urn: "document:A",
        version: 1,
        content: "version1"
      }
    },
    eventExtra: {
      requestContext: {
        connectionId: "connection:A"
      }
    }
  });

  const doc = await testDocumentRepos.getByDocumentUrn("document:A");
  expect(doc.isOk()).toBe(true);
  doc.map(data => {
    expect(data.version).toBe(1);
  });
});

test("Test set document too big and document exists", async () => {

  const documentA: CollabDocument = createDocument({urn: "document:A"});
  testDocumentRepos.reset();
  testDocumentRepos.add(documentA);

  testConnectedClientRepos.reset();
  const connectedClient: ConnectedClient = {
    connectionId: "connection:A",
    documentUrn: "document:A"
  };
  testConnectedClientRepos.add(connectedClient);

  await executeHandler({
    handler,
    body: {
      document: {
        urn: "document:A",
        version: 1,
        content: "T".repeat(parseInt(process.env.MAX_DOCUMENT_SIZE) + 1)
      }
    },
    eventExtra: {
      requestContext: {
        connectionId: "connection:A"
      }
    }
  });

  const doc = await testDocumentRepos.getByDocumentUrn("document:A");
  expect(doc.isOk()).toBe(true);
  doc.map(data => {
    expect(data.isCollabDisabled).toBe(true);
  });
});

test("Test set document too big", async () => {

  testDocumentRepos.reset();
  testConnectedClientRepos.reset();
  const connectedClient: ConnectedClient = {
    connectionId: "connection:A",
    documentUrn: "document:A"
  };
  testConnectedClientRepos.add(connectedClient);

  await executeHandler({
    handler,
    body: {
      document: {
        urn: "document:A",
        version: 1,
        content: "T".repeat(parseInt(process.env.MAX_DOCUMENT_SIZE) + 1)
      }
    },
    eventExtra: {
      requestContext: {
        connectionId: "connection:A"
      }
    }
  });

  const doc = await testDocumentRepos.getByDocumentUrn("document:A");
  expect(doc.isOk()).toBe(false);
});

test("Test set document compressed", async () => {

  testDocumentRepos.reset();
  testConnectedClientRepos.reset();
  testPayloadChunkRepos.reset();
  const connectedClient: ConnectedClient = {
    connectionId: "connection:A",
    documentUrn: "document:A"
  };
  testConnectedClientRepos.add(connectedClient);

  const payload = {
    document: {
      urn: "document:A",
      version: 1,
      content: "version1"
    }
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

  const doc = await testDocumentRepos.getByDocumentUrn("document:A");
  expect(doc.isOk()).toBe(true);
  doc.map(data => {
    expect(data.version).toBe(1);
  });
});

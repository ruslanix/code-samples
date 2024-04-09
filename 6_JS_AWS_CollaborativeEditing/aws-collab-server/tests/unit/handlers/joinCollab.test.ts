import {
  executeHandler,
  testConnectedClientRepos,
  createConnectedClient,
  testDocumentRepos,
  testPayloadChunkRepos
} from "./lib";
import { handler } from "../../../src/handlers/joinCollab";
import * as jwt from "jsonwebtoken";
const pako = require("pako");

test("Test join collab", async () => {

  testConnectedClientRepos.reset();
  testDocumentRepos.reset();

  const token = jwt.sign(
    {
      sub: "user:A",
      iss: "some-company-client-id",
      aud: "document:A",
      name: "Bob"
    } as any,
    process.env.JWT_TOKEN_SECRET,
    { expiresIn: 300 }
  );


  const res = await executeHandler({
    handler,
    body: {
      document: {
        urn: "document:A",
        version: 1,
        content: "version1"
      },
      authToken: token
    },
    eventExtra: {
      requestContext: {
        connectionId: "connection:A",
        authorizer: {
          sub: "user:A",
          iss: "some-company-client-id",
          name: "Bob"
        }
      }
    }
  });
  expect(res.data).toHaveProperty("documentUrn");
  expect(res.data).toHaveProperty("connectionId");

  const findRes = await testConnectedClientRepos.findByDocumentUrn("document:A");
  expect(findRes.isOk()).toBe(true);
  findRes.map(data => {
    expect(data.length).toBe(1);
  });

  const doc = await testDocumentRepos.getByDocumentUrn("document:A");
  expect(doc.isOk()).toBe(true);
});

test("Test join collab when same user already exists", async () => {

  testConnectedClientRepos.reset();
  testDocumentRepos.reset();

  testConnectedClientRepos.add(createConnectedClient({
    identity: "user:A",
    documentUrn: "document:A"
  }));

  const token = jwt.sign(
    {
      sub: "user:A",
      iss: "some-company-client-id",
      aud: "document:A",
      name: "Bob"
    } as any,
    process.env.JWT_TOKEN_SECRET,
    { expiresIn: 300 }
  );


  const res = await executeHandler({
    handler,
    body: {
      document: {
        urn: "document:A",
        version: 1,
        content: "version1"
      },
      authToken: token
    },
    eventExtra: {
      requestContext: {
        connectionId: "connection:A",
        authorizer: {
          sub: "user:A",
          iss: "some-company-client-id",
          name: "Bob"
        }
      }
    }
  });
  expect(res.data).toHaveProperty("documentUrn");
  expect(res.data).toHaveProperty("connectionId");

  const findRes = await testConnectedClientRepos.findByDocumentUrn("document:A");
  expect(findRes.isOk()).toBe(true);
  findRes.map(data => {
    expect(data.length).toBe(1);
  });

  const doc = await testDocumentRepos.getByDocumentUrn("document:A");
  expect(doc.isOk()).toBe(true);
});

test("Test join collab compressed", async () => {

  testConnectedClientRepos.reset();
  testDocumentRepos.reset();
  testPayloadChunkRepos.reset();

  const token = jwt.sign(
    {
      sub: "user:A",
      iss: "some-company-client-id",
      aud: "document:A",
      name: "Bob"
    } as any,
    process.env.JWT_TOKEN_SECRET,
    { expiresIn: 300 }
  );

  // compress payload
  const payload = {
    document: {
      urn: "document:A",
      version: 1,
      content: "version1"
    },
    authToken: token
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
          connectionId: "connection:A",
          authorizer: {
            sub: "user:A",
            iss: "some-company-client-id",
            name: "Bob"
          }
        }
      }
    });
  });

  await Promise.all(promises);

  const findRes = await testConnectedClientRepos.findByDocumentUrn("document:A");
  expect(findRes.isOk()).toBe(true);
  findRes.map(data => {
    expect(data.length).toBe(1);
  });

  const doc = await testDocumentRepos.getByDocumentUrn("document:A");
  expect(doc.isOk()).toBe(true);
});

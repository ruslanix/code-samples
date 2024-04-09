import { DocumentReposDynamo, CollabDocument } from "../../../src/clients";
import { logIfError } from "@company/cloud-stack-services-common";
import AWS from "aws-sdk";

const dynamoDbClient = new AWS.DynamoDB.DocumentClient({
  endpoint: process.env.AWS_DYNAMODB_ENDPOINT || undefined,
  region: process.env.AWS_REGION || process.env.STACK_REGION || undefined,
});

const repos = new DocumentReposDynamo(dynamoDbClient, process.env.AWS_DYNAMODB_DOCUMENTS_TABLE);

test("Test add, find",  async () => {
  // GIVEN
  const doc1: CollabDocument = {urn: "documentA", version: 1, content: "test"};
  const doc2: CollabDocument = {urn: "documentB", version: 2, content: "test"};

  // remove first
  await repos.remove(doc1.urn);
  await repos.remove(doc2.urn);

  // WHEN / THEN
  let res = await repos.add(doc1);
  logIfError(res);
  expect(res.isOk()).toBe(true);

  res = await repos.add(doc2);
  logIfError(res);
  expect(res.isOk()).toBe(true);

  let findRes = await repos.getByDocumentUrn("documentA");
  logIfError(findRes);
  expect(findRes.isOk()).toBe(true);
  findRes.map(data => {
    expect(data.version).toBe(1);
    expect(data.sessionId).toBeDefined();
  });

  findRes = await repos.getByDocumentUrn("documentB");
  logIfError(findRes);
  expect(findRes.isOk()).toBe(true);
  findRes.map(data => {
    expect(data.version).toBe(2);
    expect(data.sessionId).toBeDefined();
  });

  findRes = await repos.getByDocumentUrn("documentC");
  expect(findRes.isOk()).toBe(false);
});

test("Test add duplications",  async () => {
  // GIVEN
  const doc1: CollabDocument = {urn: "documentA", version: 1, content: "test"};

  // remove first
  await repos.remove(doc1.urn);
  let res = await repos.add(doc1);
  logIfError(res);
  expect(res.isOk()).toBe(true);

  // WHEN / THEN
  res = await repos.add(doc1);
  expect(res.isOk()).toBe(false);
  const e = res.unsafelyUnwrapErr();
  expect(e.cause.name).toBe("ConditionalCheckFailedException");
});

test("Test update",  async () => {
  // GIVEN
  const doc1: CollabDocument = {urn: "documentA", version: 1, content: "test"};
  const doc2: CollabDocument = {urn: "documentA", version: 2, content: "test2"};

  // remove first
  await repos.remove(doc1.urn);
  let res = await repos.add(doc1);
  logIfError(res);
  expect(res.isOk()).toBe(true);

  // WHEN / THEN
  // try update with wrong prev version
  res = await repos.update(doc2, 2);
  expect(res.isOk()).toBe(false);
  const e = res.unsafelyUnwrapErr();
  expect(e.cause.name).toBe("ConditionalCheckFailedException");

  // update
  res = await repos.update(doc2, 1);
  expect(res.isOk()).toBe(true);

  const findRes = await repos.getByDocumentUrn("documentA");
  logIfError(findRes);
  expect(findRes.isOk()).toBe(true);
  findRes.map(data => {
    expect(data.version).toBe(2);
    expect(data.content).toBe("test2");
  });
});

test("Test update statuses",  async () => {
  // GIVEN
  const doc1: CollabDocument = {urn: "documentA", version: 1, content: "test"};

  // remove first
  await repos.remove(doc1.urn);
  let res = await repos.add(doc1);
  logIfError(res);
  expect(res.isOk()).toBe(true);

  // WHEN / THEN
  // update isCollabDisabled
  res = await repos.updateStatuses({...doc1, isCollabDisabled: false});
  expect(res.isOk()).toBe(true);

  let findRes = await repos.getByDocumentUrn("documentA");
  logIfError(findRes);
  expect(findRes.isOk()).toBe(true);
  findRes.map(data => {
    expect(data.isCollabDisabled).toBe(false);
  });
});

test("Test updateOrInsertVersion",  async () => {
  const doc: CollabDocument = {urn: "documentA", version: 1, content: "version1"};
  await repos.remove(doc.urn);

  // Insert
  let res = await repos.updateOrInsertVersion(doc);
  logIfError(res);
  expect(res.isOk()).toBe(true);

  let findRes = await repos.getByDocumentUrn("documentA");
  logIfError(findRes);
  expect(findRes.isOk()).toBe(true);
  findRes.map(data => {
    expect(data.version).toBe(1);
    expect(data.content).toBe("version1");
  });

  // Should not update if prev version
  doc.content = "version2";
  res = await repos.updateOrInsertVersion(doc);
  logIfError(res);
  expect(res.isOk()).toBe(true);

  findRes = await repos.getByDocumentUrn("documentA");
  logIfError(findRes);
  expect(findRes.isOk()).toBe(true);
  findRes.map(data => {
    expect(data.version).toBe(1);
    expect(data.content).toBe("version1");
  });

  // Should update if new version
  doc.version = 2;
  res = await repos.updateOrInsertVersion(doc);
  logIfError(res);
  expect(res.isOk()).toBe(true);

  findRes = await repos.getByDocumentUrn("documentA");
  logIfError(findRes);
  expect(findRes.isOk()).toBe(true);
  findRes.map(data => {
    expect(data.version).toBe(2);
    expect(data.content).toBe("version2");
  });
});


test("Test updateOrInsertVersion with statuses fields",  async () => {
  const doc: CollabDocument = {
    urn: "documentA",
    version: 1,
    content: "version1",
    isCollabDisabled: true
  };
  await repos.remove(doc.urn);

  // Insert
  let res = await repos.updateOrInsertVersion(doc);
  logIfError(res);
  expect(res.isOk()).toBe(true);

  let findRes = await repos.getByDocumentUrn("documentA");
  logIfError(findRes);
  expect(findRes.isOk()).toBe(true);
  findRes.map(data => {
    expect(data.isCollabDisabled).toBe(true);
  });

  // Should update if new version
  doc.version = 2;
  doc.isCollabDisabled = false;
  res = await repos.updateOrInsertVersion(doc);
  logIfError(res);
  expect(res.isOk()).toBe(true);

  findRes = await repos.getByDocumentUrn("documentA");
  logIfError(findRes);
  expect(findRes.isOk()).toBe(true);
  findRes.map(data => {
    expect(data.isCollabDisabled).toBe(false);
  });
});
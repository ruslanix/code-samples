import { DocumentEditEventReposDynamo, DocumentEditEvent } from "../../../src/clients";
import { logIfError } from "@company/cloud-stack-services-common";
import AWS from "aws-sdk";

const dynamoDbClient = new AWS.DynamoDB.DocumentClient({
  endpoint: process.env.AWS_DYNAMODB_ENDPOINT || undefined,
  region: process.env.AWS_REGION || process.env.STACK_REGION || undefined,
});

const repos = new DocumentEditEventReposDynamo(dynamoDbClient, process.env.AWS_DYNAMODB_DOCUMENT_EDIT_EVENTS_TABLE);

test("Test add, find",  async () => {
  // GIVEN
  const eventA1: DocumentEditEvent = {documentUrn: "documentA", version: 1, sessionVersion: "sessionA_1"};
  const eventA2: DocumentEditEvent = {documentUrn: "documentA", version: 2, sessionVersion: "sessionA_2"};
  const eventA3: DocumentEditEvent = {documentUrn: "documentA", version: 2, sessionVersion: "sessionC_2"};
  const eventB: DocumentEditEvent = {documentUrn: "documentB", version: 1, sessionVersion: "sessionB_1"};

  // remove first
  await repos.remove(eventA1);
  await repos.remove(eventA2);
  await repos.remove(eventA3);
  await repos.remove(eventB);

  // WHEN / THEN
  let res = await repos.add(eventA1);
  logIfError(res);
  expect(res.isOk()).toBe(true);

  res = await repos.add(eventA2);
  logIfError(res);
  expect(res.isOk()).toBe(true);

  res = await repos.add(eventA3);
  logIfError(res);
  expect(res.isOk()).toBe(true);

  res = await repos.add(eventB);
  logIfError(res);
  expect(res.isOk()).toBe(true);

  let findRes = await repos.findGreaterVersions("documentA", "sessionA", 0);
  logIfError(findRes);
  expect(findRes.isOk()).toBe(true);
  findRes.map(data => {
    expect(data.length).toBe(2);
  });

  findRes = await repos.findGreaterVersions("documentA", "sessionA", 1);
  logIfError(findRes);
  expect(findRes.isOk()).toBe(true);
  findRes.map(data => {
    expect(data.length).toBe(1);
  });

  findRes = await repos.findGreaterVersions("documentA", "sessionA", 2);
  logIfError(findRes);
  expect(findRes.isOk()).toBe(true);
  findRes.map(data => {
    expect(data.length).toBe(0);
  });

  findRes = await repos.findGreaterVersions("documentA", "sessionC", 0);
  logIfError(findRes);
  expect(findRes.isOk()).toBe(true);
  findRes.map(data => {
    expect(data.length).toBe(1);
  });
});

test("Test duplications",  async () => {
  // GIVEN
  const eventA1: DocumentEditEvent = {documentUrn: "documentA", version: 1, sessionVersion: "sessionA_1"};

  // remove first
  await repos.remove(eventA1);
  let res = await repos.add(eventA1);
  logIfError(res);
  expect(res.isOk()).toBe(true);

  // WHEN / THEN
  res = await repos.add(eventA1);
  expect(res.isOk()).toBe(false);
  const e = res.unsafelyUnwrapErr();
  expect(e.cause.name).toBe("ConditionalCheckFailedException");
});

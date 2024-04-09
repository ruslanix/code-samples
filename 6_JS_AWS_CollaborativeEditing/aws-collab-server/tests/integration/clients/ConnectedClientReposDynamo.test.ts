import { ConnectedClientReposDynamo, ConnectedClient } from "../../../src/clients";
import { logIfError } from "@company/cloud-stack-services-common";
import AWS from "aws-sdk";

const dynamoDbClient = new AWS.DynamoDB.DocumentClient({
  endpoint: process.env.AWS_DYNAMODB_ENDPOINT || undefined,
  region: process.env.AWS_REGION || process.env.STACK_REGION || undefined,
});

const repos = new ConnectedClientReposDynamo(dynamoDbClient, process.env.AWS_DYNAMODB_CONNECTED_CLIENTS_TABLE);

test("Test add find remove",  async () => {
  const connectetClientA: ConnectedClient = {connectionId: "connectionA", documentUrn: "urnA"};
  const connectetClientB: ConnectedClient = {connectionId: "connectionB", documentUrn: "urnA"};

  let res = await repos.add(connectetClientA);
  logIfError(res);
  expect(res.isOk()).toBe(true);

  res = await repos.add(connectetClientB);
  logIfError(res);
  expect(res.isOk()).toBe(true);

  const findRes = await repos.findByDocumentUrn("urnA");
  logIfError(findRes);
  expect(findRes.isOk()).toBe(true);
  findRes.map(data => {
    expect(data.length).toBe(2);
  });

  let connection = await repos.getByDocAndConnection("urnA", "connectionA");
  expect(connection.isOk()).toBe(true);
  connection = await repos.getByDocAndConnection("urnA", "connectionUnknown");
  expect(connection.isOk()).toBe(false);

  res = await repos.remove(connectetClientA);
  logIfError(res);
  expect(res.isOk()).toBe(true);

  res = await repos.remove(connectetClientB);
  logIfError(res);
  expect(res.isOk()).toBe(true);

  const findRes2 = await repos.findByDocumentUrn("urnA");
  logIfError(findRes2);
  expect(findRes2.isOk()).toBe(true);
  findRes2.map(data => {
    expect(data.length).toBe(0);
  });
});

test("Test removeAll",  async () => {
  const connectetClientA: ConnectedClient = {connectionId: "connectionA", documentUrn: "urnA"};
  const connectetClientB: ConnectedClient = {connectionId: "connectionA", documentUrn: "urnB"};

  let res = await repos.add(connectetClientA);
  logIfError(res);
  expect(res.isOk()).toBe(true);

  res = await repos.add(connectetClientB);
  logIfError(res);
  expect(res.isOk()).toBe(true);

  res = await repos.removeAllByConnectionId("connectionA");
  logIfError(res);
  expect(res.isOk()).toBe(true);

  const findRes = await repos.findByDocumentUrn("urnA");
  logIfError(findRes);
  expect(findRes.isOk()).toBe(true);
  findRes.map(data => {
    expect(data.length).toBe(0);
  });
});
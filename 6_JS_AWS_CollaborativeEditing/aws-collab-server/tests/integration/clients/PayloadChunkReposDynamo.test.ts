import { PayloadChunk, PayloadChunkReposDynamo } from "../../../src/clients";
import { logIfError } from "@company/cloud-stack-services-common";
import AWS from "aws-sdk";

const dynamoDbClient = new AWS.DynamoDB.DocumentClient({
  endpoint: process.env.AWS_DYNAMODB_ENDPOINT || undefined,
  region: process.env.AWS_REGION || process.env.STACK_REGION || undefined,
});

const repos = new PayloadChunkReposDynamo(dynamoDbClient, process.env.AWS_DYNAMODB_PAYLOAD_CHUNKS_TABLE);

test("Test add, find",  async () => {
  // GIVEN
  const payloadA = String(Date.now());
  const payloadB = String(Date.now()) + "B";
  const chunkA1: PayloadChunk = {payloadId: payloadA, idx: 1, data: "dsfsdf"};
  const chunkA2: PayloadChunk = {payloadId: payloadA, idx: 2, data: "dsfsdf"};
  const chunkB1: PayloadChunk = {payloadId: payloadB, idx: 1, data: "dsfsdf"};

  // WHEN / THEN
  let res = await repos.add(chunkA1);
  logIfError(res);
  expect(res.isOk()).toBe(true);

  res = await repos.add(chunkA2);
  logIfError(res);
  expect(res.isOk()).toBe(true);

  res = await repos.add(chunkB1);
  logIfError(res);
  expect(res.isOk()).toBe(true);

  const findRes = await repos.findByPayloadId(payloadA);
  logIfError(findRes);
  expect(findRes.isOk()).toBe(true);
  findRes.map(data => {
    expect(data.length).toBe(2);
  });
});

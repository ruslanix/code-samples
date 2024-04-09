import * as AWS from "aws-sdk";
import { Result } from "true-myth";
import { Exception } from "@company/cloud-stack-services-common";

export type PayloadChunk = {
  payloadId: string;
  idx: number;
  data: string;
  ttl?: number;
};

export interface PayloadChunkRepos {
  add(chunk: PayloadChunk): Promise<Result<true, Exception>>;
  findByPayloadId(id: string): Promise<Result<PayloadChunk[], Exception>>;
}

export class PayloadChunkReposDynamo implements PayloadChunkRepos {
  private readonly client: AWS.DynamoDB.DocumentClient;
  private readonly tableName: string;

  constructor(client: AWS.DynamoDB.DocumentClient, tableName: string) {
    this.client = client;
    this.tableName = tableName;
  }

  async add(chunk: PayloadChunk): Promise<Result<true, Exception>> {

    if (!chunk.ttl) {
      chunk.ttl = Date.now() / 1000 + 60 * 60 * 24;
    }

    const params: AWS.DynamoDB.DocumentClient.PutItemInput = {
      TableName: this.tableName,
      Item: {...chunk},
      ConditionExpression: "attribute_not_exists(payloadId)"
    };

    try {
      await this.client.put(params).promise();
      return Result.ok(true);
    } catch (e) {
      return Result.err(new Exception("DynamoDb error", e.statusCode || 500, e));
    }
  }

  async findByPayloadId(payloadId: string): Promise<Result<PayloadChunk[], Exception>> {
    const params: AWS.DynamoDB.DocumentClient.QueryInput = {
      TableName: this.tableName,
      KeyConditionExpression: "payloadId = :payloadId",
      ExpressionAttributeValues: {
        ":payloadId": payloadId
      }
    };

    try {
      let data: AWS.DynamoDB.DocumentClient.QueryOutput  = await this.client.query(params).promise();
      let items = data.Items;

      while (data.LastEvaluatedKey) {
        params.ExclusiveStartKey = data.LastEvaluatedKey;
        data = await this.client.query(params).promise();
        items = [...items, ...data.Items];
      }

      return Result.ok(items as PayloadChunk[]);
    } catch (e) {
      return Result.err(new Exception("DynamoDb error", e.statusCode || 500, e));
    }
  }
}

/**
 * Using memory, useful for testing
 */
export class PayloadChunkReposMemory implements PayloadChunkRepos {
  records: PayloadChunk[];

  constructor(records?: PayloadChunk[]) {
    this.records = records || [];
  }

  reset() {
    this.records = [];
  }

  async add(chunk: PayloadChunk): Promise<Result<true, Exception>> {
    const r = this.records.find(r => r.payloadId == chunk.payloadId && r.idx == chunk.idx);
    if (!r) {
      this.records.push(chunk);
    }

    return Result.ok(true);
  }

  async findByPayloadId(payloadId: string): Promise<Result<PayloadChunk[], Exception>> {
    const records = this.records.filter(r => r.payloadId === payloadId);
    return Result.ok(records);
  }
}
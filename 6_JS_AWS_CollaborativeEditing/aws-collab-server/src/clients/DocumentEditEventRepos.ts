import * as AWS from "aws-sdk";
import { Result } from "true-myth";
import { Exception } from "@company/cloud-stack-services-common";

export type DocumentEditEvent = {
  documentUrn: string;
  /**
   * This is a range(sort) key
   * - concatination of {CollabDocument.sessionId}_{version}
   * Check CollabDocument.sessionId for more info
   */
  sessionVersion: string;
  /**
   * This field partially duplicates sessionVersion but it is convenient to have it independently too
   */
  version: number;
  date?: string;
  /**
   * {
   *  clientID: string,
   *  steps[]: Prosemirror change steps
   * }
   */
  event?: any;
  ttl?: number;
};

/**
 * Clients to documents connections
 * Represents current document collaborators
 */
export interface DocumentEditEventRepos {
  /**
   * Add
   */
  add(documentEvent: DocumentEditEvent): Promise<Result<true, Exception>>;

  /**
   * Find document events with greater versions
   */
  findGreaterVersions(documentUrn: string, collabSessionId: string, version: number): Promise<Result<AWS.DynamoDB.DocumentClient.AttributeMap[], Exception>>;

  /**
   * Remove
   */
  remove(documentEvent: DocumentEditEvent): Promise<Result<true, Exception>>;
}

/**
 * ConnectedClientRepos using AWS DynamoDB table.
 */
export class DocumentEditEventReposDynamo implements DocumentEditEventRepos {
  private readonly client: AWS.DynamoDB.DocumentClient;
  private readonly tableName: string;

  constructor(client: AWS.DynamoDB.DocumentClient, tableName: string) {
    this.client = client;
    this.tableName = tableName;
  }

  /**
   * Should not allow to create document event with existing version number
   */
  async add(documentEvent: DocumentEditEvent): Promise<Result<true, Exception>> {
    const params: AWS.DynamoDB.DocumentClient.PutItemInput = {
      TableName: this.tableName,
      Item: {...documentEvent},
      // documentUrn and sessionVersion - are keys, so will not allow to add with the same version
      ConditionExpression: "attribute_not_exists(documentUrn)"
    };

    try {
      await this.client.put(params).promise();
      return Result.ok(true);
    } catch (e) {
      return Result.err(new Exception("DynamoDb error", e.statusCode || 500, e));
    }
  }

  async findGreaterVersions(documentUrn: string, collabSessionId: string, version: number): Promise<Result<AWS.DynamoDB.DocumentClient.AttributeMap[], Exception>> {
    // First find document events with greater version
    // then filter by current collab session id
    const params: AWS.DynamoDB.DocumentClient.QueryInput = {
      TableName: this.tableName,
      IndexName: "byVersion",
      KeyConditionExpression: "documentUrn = :documentUrn AND version > :version",
      FilterExpression: "begins_with(sessionVersion, :session)",
      ExpressionAttributeValues: {
        ":documentUrn": documentUrn,
        ":version": version,
        ":session": collabSessionId
      }
    };

    try {
      const data  = await this.client.query(params).promise();
      return Result.ok(data.Items);
    } catch (e) {
      return Result.err(new Exception("DynamoDb error", e.statusCode || 500, e));
    }
  }

  async remove(documentEvent: DocumentEditEvent): Promise<Result<true, Exception>> {
    const params: AWS.DynamoDB.DocumentClient.DeleteItemInput = {
      TableName: this.tableName,
      Key: {
        documentUrn: documentEvent.documentUrn,
        sessionVersion: documentEvent.sessionVersion
      }
    };

    try {
      await this.client.delete(params).promise();
      return Result.ok(true);
    } catch (e) {
      return Result.err(new Exception("DynamoDb error", e.statusCode || 500, e));
    }
  }
}

/**
 * ClientRepos using memory, useful for testing
 */
export class DocumentEditEventReposMemory implements DocumentEditEventRepos {
  records: DocumentEditEvent[];

  constructor(records?: DocumentEditEvent[]) {
    this.records = records || [];
  }

  getRecords() {
    return this.records;
  }

  reset() {
    this.records = [];
  }

  async add(record: DocumentEditEvent): Promise<Result<true, Exception>> {
    const r = this.records.find(r => r.documentUrn === record.documentUrn && r.event === record.event && r.version === record.version);
    if (!r) {
      this.records.push(record);
    }

    return Result.ok(true);
  }

  async findGreaterVersions(documentUrn: string, collabSessionId: string, version: number): Promise<Result<AWS.DynamoDB.DocumentClient.AttributeMap[], Exception>> {
    const records = this.records.filter(
      (r: DocumentEditEvent) => r.documentUrn === documentUrn && r.version > version && r.sessionVersion.indexOf(collabSessionId) === 0
    );

    return Result.ok(records);
  }

  async remove(record: DocumentEditEvent): Promise<Result<true, Exception>> {
    this.records = this.records.filter(
      (r: DocumentEditEvent) => r.documentUrn !== record.documentUrn || r.event !== record.event || r.version !== record.version
    );

    return Result.ok(true);
  }
}
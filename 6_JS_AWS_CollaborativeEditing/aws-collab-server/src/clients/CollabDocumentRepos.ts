import * as AWS from "aws-sdk";
import { Result } from "true-myth";
import { Exception, LookupException } from "@company/cloud-stack-services-common";

export type CollabDocument = {
  urn: string;
  content: string;
  version: number;
  /**
   * Set to false in case if doc size exceeds limit or other error case
   */
  isCollabDisabled?: boolean;
  /**
   * Collab session indicates collaboration between clients on the same document
   * Session starts (and sessionId generated) when first client joins collab and insert document
   * Session ends when all clients disconnected and document removed
   *
   * We need this additional field to distinguish and ignore
   * DocumentEditEvent records that might left from previous collab session
   *
   * Outdated DocumentEditEvent will be removed by ttl automatically
   * Without this field we will have to manually remove all related DocumentEditEvent after document removal
   * (when all clients disconnected)
   *
   * Please note that document urn is still partition key,
   * so it is not possible to have in db two documents with the same urn (not possible to have two collab session with same document)
   * We need seesionId only to start new collaboration with a clean slate and don't interfere with outdated changes
   */
  sessionId?: string;
};

export interface DocumentRepos {
  add(document: CollabDocument): Promise<Result<true, Exception>>;
  /**
   * Update document status fields not related to content and versions
   *
   * @param document
   */
  updateStatuses(document: CollabDocument): Promise<Result<true, Exception>>;
  /**
   * Need to pass prevVersion to avoid situation when someone already updated document since the time when we read it
   * So, we check that version stayed the same when we update document
   *
   * @param document
   * @param prevVersion
   */
  update(document: CollabDocument, prevVersion: number): Promise<Result<true, Exception>>;
  /**
   * Insert new document or update prev if new version available
   *
   * @param document
   */
  updateOrInsertVersion(newDocument: CollabDocument): Promise<Result<true, Exception>>;
  remove(documentUrn: string): Promise<Result<true, Exception>>;
  getByDocumentUrn(urn: string): Promise<Result<CollabDocument, LookupException>>;
}

export class DocumentReposDynamo implements DocumentRepos {
  private readonly client: AWS.DynamoDB.DocumentClient;
  private readonly tableName: string;

  constructor(client: AWS.DynamoDB.DocumentClient, tableName: string) {
    this.client = client;
    this.tableName = tableName;
  }

  async add(document: CollabDocument): Promise<Result<true, Exception>> {

    if (!document.sessionId) {
      document.sessionId = String(Date.now());
    }

    const params: AWS.DynamoDB.DocumentClient.PutItemInput = {
      TableName: this.tableName,
      Item: {...document},
      ConditionExpression: "attribute_not_exists(urn)"
    };

    if (document.isCollabDisabled !== undefined) {
      params.Item["isCollabDisabled"] = document.isCollabDisabled;
    }

    try {
      await this.client.put(params).promise();
      return Result.ok(true);
    } catch (e) {
      return Result.err(new Exception("DynamoDb error", e.statusCode || 500, e));
    }
  }

  async updateStatuses(document: CollabDocument): Promise<Result<true, Exception>> {

    let updateExpression = "";
    const attributeValues = {};

    if (document.isCollabDisabled !== undefined) {
      updateExpression = "set isCollabDisabled = :isCollabDisabled";
      attributeValues[":isCollabDisabled"] = document.isCollabDisabled;
    }

    // Return if nothing to update
    if (!updateExpression.length) {
      return Result.ok(true);
    }

    const params: AWS.DynamoDB.DocumentClient.Update = {
      TableName: this.tableName,
      Key: {
        urn: document.urn
      },
      UpdateExpression: updateExpression,
      ExpressionAttributeValues: attributeValues
    };

    try {
      await this.client.update(params).promise();
      return Result.ok(true);
    } catch (e) {
      return Result.err(new Exception("DynamoDb error", e.statusCode || 500, e));
    }
  }

  async update(document: CollabDocument, prevVersion: number): Promise<Result<true, Exception>> {
    const params: AWS.DynamoDB.DocumentClient.Update = {
      TableName: this.tableName,
      Key: {
        urn: document.urn
      },
      ConditionExpression: "version = :prevVersion",
      UpdateExpression: "set content = :content, version = :version",
      ExpressionAttributeValues: {
        ":content": document.content,
        ":version": document.version,
        ":prevVersion": prevVersion
      }
    };

    if (document.isCollabDisabled !== undefined) {
      params.UpdateExpression += ", isCollabDisabled = :isCollabDisabled";
      params.ExpressionAttributeValues[":isCollabDisabled"] = document.isCollabDisabled;
    }

    try {
      await this.client.update(params).promise();
      return Result.ok(true);
    } catch (e) {
      return Result.err(new Exception("DynamoDb error", e.statusCode || 500, e));
    }
  }

  async updateOrInsertVersion(newDocument: CollabDocument): Promise<Result<true, Exception>> {
    const prevDocument = (await this.getByDocumentUrn(newDocument.urn)).unwrapOr(undefined);

    if (prevDocument && prevDocument.version < newDocument.version) {
      return this.update(newDocument, prevDocument.version);
    } else if (!prevDocument) {
      return this.add(newDocument);
    } else {
      // Or just update statuses
      this.updateStatuses(newDocument);
    }

    return Result.ok(true);
  }

  async remove(documentUrn: string): Promise<Result<true, Exception>> {
    const params: AWS.DynamoDB.DocumentClient.DeleteItemInput = {
      TableName: this.tableName,
      Key: {
        urn: documentUrn
      }
    };

    try {
      await this.client.delete(params).promise();
      return Result.ok(true);
    } catch (e) {
      return Result.err(new Exception("DynamoDb error", e.statusCode || 500, e));
    }
  }

  async getByDocumentUrn(urn: string): Promise<Result<CollabDocument, LookupException>> {
    const params: AWS.DynamoDB.DocumentClient.GetItemInput = {
      TableName: this.tableName,
      Key: {urn: urn}
    };

    try {
      const res  = await this.client.get(params).promise();
      if (!res || !res.Item) {
        return Result.err(LookupException.newNotFound());
      }

      return Result.ok(res.Item as CollabDocument);
    } catch (e) {
      return Result.err(new LookupException("DynamoDb error", e.statusCode || 500, e));
    }
  }
}

/**
 * ConnectedClientReposMemory using memory, useful for testing
 */
export class DocumentReposMemory implements DocumentRepos {
  records: CollabDocument[];

  constructor(records?: CollabDocument[]) {
    this.records = records || [];
  }

  reset() {
    this.records = [];
  }

  async add(document: CollabDocument): Promise<Result<true, Exception>> {
    const r = this.records.find(r => r.urn == document.urn);
    if (!r) {
      this.records.push(document);
    }

    return Result.ok(true);
  }

  async updateStatuses(document: CollabDocument): Promise<Result<true, Exception>> {
    let updated = false;
    this.records = this.records.filter(r => {
      if (r.urn == document.urn) {
        r.isCollabDisabled = document.isCollabDisabled;
        updated = true;
      }

      return r;
    });

    return updated ? Result.ok(true) : Result.err(new Exception("Can't find"));
  }

  async update(document: CollabDocument, prevVersion: number): Promise<Result<true, Exception>> {
    let updated = false;
    this.records = this.records.filter(r => {
      if (r.urn == document.urn && r.version == prevVersion) {
        r.content = document.content;
        r.version = document.version;
        updated = true;
      }

      return r;
    });

    return updated ? Result.ok(true) : Result.err(new Exception("Can't find"));
  }

  async updateOrInsertVersion(newDocument: CollabDocument): Promise<Result<true, Exception>> {
    const prevDocument = (await this.getByDocumentUrn(newDocument.urn)).unwrapOr(undefined);

    if (prevDocument && prevDocument.version < newDocument.version) {
      return this.update(newDocument, prevDocument.version);
    } else if (!prevDocument) {
      return this.add(newDocument);
    }

    return Result.ok(true);
  }

  async remove(documentUrn: string): Promise<Result<true, Exception>> {
    this.records = this.records.filter(r => r.urn !== documentUrn );
    return Result.ok(true);
  }

  async getByDocumentUrn(urn: string): Promise<Result<CollabDocument, LookupException>> {
    const r: CollabDocument = this.records.find(r => r.urn == urn);
    return r ? Result.ok(r) : Result.err(new LookupException("Can't find"));
  }
}
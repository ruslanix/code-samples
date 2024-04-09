import * as AWS from "aws-sdk";
import { Result } from "true-myth";
import { Exception, LookupException } from "@company/cloud-stack-services-common";

export type ConnectedClient = {
  connectionId: string;
  documentUrn: string;
  /**
   * implementation specific identifier. E.g. in Company we might put a personId in here.
   */
  identity?: string;
  displayName?: string;
  /**
   * Position of the users cursor in the document
   */
  cursorPosition?: number;
  ttl?: number;
};

/**
 * Clients to documents connections
 * Represents current document collaborators
 */
export interface ConnectedClientRepos {
  /**
   * Add connection by client websocket connectionId and document URN
   */
  add(connectedClient: ConnectedClient): Promise<Result<true, Exception>>;

  /**
   * Update connected client
   */
  update(connectedClient: ConnectedClient): Promise<Result<true, Exception>>;

  /**
   * Remove connection by client websocket connectionId and document URN
   */
  remove(connectedClient: ConnectedClient): Promise<Result<true, Exception>>;

  /**
   * Cleanup all connection for client connectionId
   */
  removeAllByConnectionId(connectionId: string): Promise<Result<true, Exception>>;

  /**
   * Find connected clients by documentUrn
   */
  findByDocumentUrn(documentUrn: string): Promise<Result<ConnectedClient[], Exception>>;

  /**
   *
   * Find connected clients by connectionId
   */
  findByConnectionId(connectionId: string): Promise<Result<ConnectedClient[], Exception>>;

  /**
   * Check if user and document connected
   */
  getByDocAndConnection(documentUrn: string, connectionId: string): Promise<Result<ConnectedClient, Exception>>;
}

/**
 * ConnectedClientRepos using AWS DynamoDB table.
 */
export class ConnectedClientReposDynamo implements ConnectedClientRepos {
  private readonly client: AWS.DynamoDB.DocumentClient;
  private readonly tableName: string;

  constructor(client: AWS.DynamoDB.DocumentClient, tableName: string) {
    this.client = client;
    this.tableName = tableName;
  }

  async add(connectedClient: ConnectedClient): Promise<Result<true, Exception>> {
    if (!connectedClient.ttl) {
      connectedClient.ttl = Date.now() / 1000 + 60 * 60 * 24;
    }

    const params: AWS.DynamoDB.DocumentClient.PutItemInput = {
      TableName: this.tableName,
      Item: {...connectedClient}
    };

    try {
      await this.client.put(params).promise();
      return Result.ok(true);
    } catch (e) {
      return Result.err(new Exception("DynamoDb error", e.statusCode || 500, e));
    }
  }

  /**
   * For now only update cursorPosition
   */
  async update(connectedClient: ConnectedClient): Promise<Result<true, Exception>> {
    const params: AWS.DynamoDB.DocumentClient.Update = {
      TableName: this.tableName,
      Key: {
        connectionId: connectedClient.connectionId,
        documentUrn: connectedClient.documentUrn
      },
      UpdateExpression: "set cursorPosition = :position",
      ExpressionAttributeValues: {
        ":position": connectedClient.cursorPosition
      }
    };

    try {
      await this.client.update(params).promise();
      return Result.ok(true);
    } catch (e) {
      return Result.err(new Exception("DynamoDb error", e.statusCode || 500, e));
    }
  }

  async remove(connectedClient: ConnectedClient): Promise<Result<true, Exception>> {
    const params: AWS.DynamoDB.DocumentClient.DeleteItemInput = {
      TableName: this.tableName,
      Key: {
        connectionId: connectedClient.connectionId,
        documentUrn: connectedClient.documentUrn
      }
    };

    try {
      await this.client.delete(params).promise();
      return Result.ok(true);
    } catch (e) {
      return Result.err(new Exception("DynamoDb error", e.statusCode || 500, e));
    }
  }

  async removeAllByConnectionId(connectionId: string): Promise<Result<true, Exception>> {
    const items = await this.findByConnectionId(connectionId);
    if (!items.isOk()) {
      return Result.err(items.unsafelyUnwrapErr());
    }

    // @TODO: use BatchWrite instead
    const promises = items.unsafelyUnwrap().map(async (i: ConnectedClient) => {
      const p = await this.remove(i);
      if (!p.isOk()) {
        throw p.unsafelyUnwrapErr();
      }
      return p;
    });

    try {
      await Promise.all(promises);
    } catch (e) {
      return Result.err(e);
    }

    return Result.ok(true);
  }

  async findByDocumentUrn(documentUrn: string): Promise<Result<ConnectedClient[], Exception>> {
    const params: AWS.DynamoDB.DocumentClient.QueryInput = {
      TableName: this.tableName,
      IndexName: "byDocumentUrn",
      KeyConditionExpression: "documentUrn = :urn",
      ExpressionAttributeValues: {
        ":urn": documentUrn
      }
    };

    try {
      const data  = await this.client.query(params).promise();
      return Result.ok(data.Items as ConnectedClient[]);
    } catch (e) {
      return Result.err(new Exception("DynamoDb error", e.statusCode || 500, e));
    }
  }

  async findByConnectionId(connectionId: string): Promise<Result<ConnectedClient[], Exception>> {
    const params: AWS.DynamoDB.DocumentClient.QueryInput = {
      TableName: this.tableName,
      KeyConditionExpression: "connectionId = :id",
      ExpressionAttributeValues: {
        ":id": connectionId
      }
    };

    try {
      const data  = await this.client.query(params).promise();
      return Result.ok(data.Items as ConnectedClient[]);
    } catch (e) {
      return Result.err(new Exception("DynamoDb error", e.statusCode || 500, e));
    }
  }

  async getByDocAndConnection(documentUrn: string, connectionId: string): Promise<Result<ConnectedClient, Exception>> {
    const params: AWS.DynamoDB.DocumentClient.GetItemInput = {
      TableName: this.tableName,
      Key: {
        connectionId: connectionId,
        documentUrn: documentUrn
      }
    };

    try {
      const res  = await this.client.get(params).promise();
      if (!res || !res.Item) {
        return Result.err(LookupException.newNotFound());
      }

      return Result.ok(res.Item as ConnectedClient);
    } catch (e) {
      return Result.err(new LookupException("DynamoDb error", e.statusCode || 500, e));
    }
  }
}

/**
 * ClientRepos using memory, useful for testing
 */
export class ConnectedClientReposMemory implements ConnectedClientRepos {
  records: ConnectedClient[];

  constructor(records?: ConnectedClient[]) {
    this.records = records || [];
  }

  reset() {
    this.records = [];
  }

  async add(connectedClient: ConnectedClient): Promise<Result<true, Exception>> {
    const r = this.records.find(r => r == connectedClient);
    if (!r) {
      this.records.push(connectedClient);
    }

    return Result.ok(true);
  }

  async update(connectedClient: ConnectedClient): Promise<Result<true, Exception>> {
    let updated = false;
    this.records = this.records.filter(r => {
      if (r.connectionId == connectedClient.connectionId) {
        r.cursorPosition = connectedClient.cursorPosition;
        updated = true;
      }

      return r;
    });

    return updated ? Result.ok(true) : Result.err(new Exception("Can't find"));
  }

  async remove(connectedClient: ConnectedClient): Promise<Result<true, Exception>> {
    this.records = this.records.filter(r => r.connectionId !== connectedClient.connectionId && r.documentUrn !== connectedClient.documentUrn );
    return Result.ok(true);
  }

  async removeAllByConnectionId(connectionId: string): Promise<Result<true, Exception>> {
    this.records = this.records.filter(r => r.connectionId != connectionId);
    return Result.ok(true);
  }

  async findByDocumentUrn(documentUrn: string): Promise<Result<ConnectedClient[], Exception>> {
    const records = this.records.filter(r => r.documentUrn === documentUrn);
    return Result.ok(records);
  }

  async findByConnectionId(connectionId: string): Promise<Result<ConnectedClient[], Exception>> {
    const records = this.records.filter(r => r.connectionId === connectionId);
    return Result.ok(records);
  }

  async getByDocAndConnection(documentUrn: string, connectionId: string): Promise<Result<ConnectedClient, Exception>> {
    const r: ConnectedClient = this.records.find(r => r.documentUrn == documentUrn && r.connectionId === connectionId);
    return r ? Result.ok(r) : Result.err(new LookupException("Can't find"));
  }
}
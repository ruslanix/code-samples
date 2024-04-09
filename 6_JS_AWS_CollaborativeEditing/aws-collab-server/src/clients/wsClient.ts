import * as AWS from "aws-sdk";
import { Result } from "true-myth";
import { Exception } from "@company/cloud-stack-services-common";

/**
 * Send messages to websocket clients
 */
export interface WebsocketClient {
  /**
   * Send message to websocket client
   * connectionId - id from websocket @connect event handler event AWS.APIGatewayEventRequestContext::connectionId
   */
  send(connectionId: string, payload: any): Promise<Result<true, Exception>>;
}

/**
 * Implementation using AWS.ApiGatewayManagementApi
 */
export class WebsocketClientApiGateway implements WebsocketClient {
  private readonly apiClient: AWS.ApiGatewayManagementApi;

  constructor(apiClient: AWS.ApiGatewayManagementApi) {
    this.apiClient = apiClient;
  }

  async send(connectionId: string, payload: any): Promise<Result<true, Exception>> {
    if (typeof payload !== "string") {
      payload = JSON.stringify(payload);
    }
    try {
      await this.apiClient.postToConnection({
        ConnectionId: connectionId,
        Data: payload
      }).promise();
      return Result.ok(true);
    } catch (e) {
      return Result.err(new Exception(`Websocket exception for connectionId ${connectionId}`, e.statusCode || 500, e));
    }
  }
}
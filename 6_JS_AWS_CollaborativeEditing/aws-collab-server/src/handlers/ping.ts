import { APIGatewayProxyHandler } from "aws-lambda";
import {
  getWebsocketClient
} from "../clients";

/**
 * Ping/pong to detect stalled connections
 */
export const handler: APIGatewayProxyHandler = async (event, _context) => {

  await getWebsocketClient(event).send(event.requestContext.connectionId, {
    action: "pong"
  });

  return {
    statusCode: 200,
    body: ""
  };
};

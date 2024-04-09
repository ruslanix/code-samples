import { APIGatewayProxyHandler } from "aws-lambda";

/**
 * Need this $connect stub to invoke authorizer on connect
 */
export const handler: APIGatewayProxyHandler = async (event, _context) => {
  console.log("Got connection", event.requestContext.connectionId);
  return {
    statusCode: 200,
    body: event.requestContext.connectionId
  };
};
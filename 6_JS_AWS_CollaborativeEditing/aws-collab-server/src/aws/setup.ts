import * as AWS from "aws-sdk";

export const dynamoDbClient = new AWS.DynamoDB.DocumentClient({
    endpoint: process.env.AWS_DYNAMODB_ENDPOINT || undefined,
    region: process.env.AWS_REGION || process.env.STACK_REGION || undefined,
    // we need this to prevent this error https://forums.aws.amazon.com/thread.jspa?threadID=90137
    // ProseMirror Step JSON sometimes contains empty values for properties, for example when copy/paste image
    // title property might be empty
    convertEmptyValues: true
});

export const apiGatewayClient = new AWS.ApiGatewayManagementApi({
    apiVersion: "2018-11-29",
    endpoint: process.env.API_GATEWAY_MANAGEMENT_API_ENDPOINT_CUSTOM || process.env.API_GATEWAY_MANAGEMENT_API_ENDPOINT
});
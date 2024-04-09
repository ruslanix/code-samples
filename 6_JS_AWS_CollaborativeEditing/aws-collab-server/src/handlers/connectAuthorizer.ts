import { CustomAuthorizerHandler, CustomAuthorizerResult } from "aws-lambda";
import * as jwt from "jsonwebtoken";

/**
 * Verify connection token
 * Token payload then will be saved in event context and might be acheaved in other lambda handlers like:
 * - event.requestContext.authorizer
 */
export const handler: CustomAuthorizerHandler = async (event) => {
  console.log("Connect authorizer Received event:", JSON.stringify(event, undefined, 2));

  const tokenValue = event.queryStringParameters.authToken ? event.queryStringParameters.authToken : undefined;

  if (!tokenValue) {
    console.log("Missing authorizationToken");
    throw "Unauthorized";
  }

  try {
    const decoded = jwt.decode(tokenValue);
    if (!decoded["iss"] || !decoded["sub"] || !decoded["name"]) {
      console.log("Invalid token -- missing iss|sub|aud|name");
      throw "Unauthorized";
    }

    jwt.verify(tokenValue, process.env.JWT_TOKEN_SECRET);

    console.log("Token is OK, generation allow policy");

    return generatePolicy(decoded, "allow", event.methodArn);
  } catch (e) {
    console.log("event", event);
    console.error(e);
    throw "Unauthorized";
  }
};

function generatePolicy(payload, effect, resource): CustomAuthorizerResult {
  const authResponse: any = {};

  authResponse.principalId = payload.sub;

  const policyDocument: any = {};
  policyDocument.Version = "2012-10-17";
  policyDocument.Statement = [];

  const statementOne: any = {};
  statementOne.Action = "execute-api:Invoke";
  statementOne.Effect = effect;
  statementOne.Resource = resource;
  policyDocument.Statement[0] = statementOne;

  authResponse.policyDocument = policyDocument;
  authResponse.context = {
    sub: payload.sub,
    iss: payload.iss,
    name: payload.name
  };

  console.log("Generated policy: ", JSON.stringify(authResponse, undefined, 2));

  return authResponse;
}

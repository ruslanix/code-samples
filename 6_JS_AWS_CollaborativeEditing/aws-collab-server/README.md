# Collab server based on AWS

Please check serverless.yml for main AWS configuration

## Development

### Using AWS

It's often easiest to simlpy use "real" AWS and deploy this package.

#### 1) Set up your local computer with an AWS profile.
https://docs.aws.amazon.com/cli/latest/userguide/cli-configure-profiles.html

E.g. I might create a profile called `aws-dev`.

#### 2) Congifure .env
Copy `.env` to `.env.development` and edit it as necessary. e.g. change the region, etc. Also add this line:

```
AWS_PROFILE = aws-dev
```

(Replace with your actual profile name).


#### 3) Deploy the stack:

Note that dev:deploy use env.development and pass this vars to AWS.
You probably need to comment out vars related to local dev:
```
# AWS_DYNAMODB_ENDPOINT = "http://127.0.0.1:6103"
# API_GATEWAY_MANAGEMENT_API_ENDPOINT_CUSTOM  = ""
```
It is better to disable lambda function cloud watch for previosly deployed stack because of possible role errors (check enable/disable cloud watch below)
> An error occurred: WebsocketsDeploymentStage - CloudWatch Logs role ARN must be set in account settings to enable logging

Run deploy:

```
$ yarn run dev:deploy
```

Deploy should return api endpoint, something like: wss://abcdefg.execute-api.eu-west-1.amazonaws.com/dev
Check this link to get lates url: https://eu-west-1.console.aws.amazon.com/apigateway/home?region=eu-west-1#/apis/abcdefg/stages/dev

Local ws endpoint in case of using offline (address that need to be used in collab client): `ws://localhost:3001`

Manually Connect to websocket
```
$ wscat  -c wss://abcdefg.execute-api.eu-west-1.amazonaws.com/dev
```

Check content editor StoryBook->Collab Editing for the client

#### Possible deploy errors

> CloudFormation cannot update a stack when a custom-named resource requires replacing
This usually happens when there are changes in dynamo db table structure or something like this. Official way is to rename that resource (for example add build number). In case of table - change it name in .env.development

#### Configure logging for deployed lambda function (CloudWatch)
Go to:
AWS -> Api Gateway -> select your stack -> Stages -> dev -> Log\Tracing tab -> Enable CloudWatch Logs

### Using Local DynamoDB

You can also use the [serverless-dynamodb-local plugin](https://www.npmjs.com/package/serverless-dynamodb-local). This runs a DynamoDB server on localhost on port `6103`.

#### 1) Init

```
$ npm run dev:offline:db
```

####  2) Anywhere else you need to use the table (e.g. when developing the `accounts` package),
you can set the DynamoDB endpoint in the `.env` file:

```
AWS_DYNAMODB_ENDPOINT = "http://127.0.0.1:6103"
```

### Run offline

```
$ yarn run dev:offline
```
or
```
$ yarn run dev:offline -- --printOutput
```

This will run offline lambda, dynamoDb, dynamoDb streams
Pay attention to this vars in env.development

```
AWS_DYNAMODB_ENDPOINT  = "http://127.0.0.1:6103"
API_GATEWAY_MANAGEMENT_API_ENDPOINT_CUSTOM  = "http://localhost:3001"
```

Local ws address that need to be used in collab client: `ws://localhost:3001`

### Run tests

####  1) Integrational tests
```
$ yarn run dev:offline:db
$ yarn run test-integration
```

Don't forget to update dynamo db endpoint for env.test
```
AWS_DYNAMODB_ENDPOINT = "http://127.0.0.1:6103"
```
####  2) unit tests
```
$ yarn run test
```

## Dev notes

If addingn new table - don't forget to add corresponding permission to `iamRoleStatements` in serverless.yml
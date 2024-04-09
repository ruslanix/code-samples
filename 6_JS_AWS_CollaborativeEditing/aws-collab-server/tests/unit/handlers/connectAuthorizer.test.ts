import { handler } from "../../../src/handlers/connectAuthorizer";
import * as jwt from "jsonwebtoken";

test("Test connect authorizer", async () => {
  const token = jwt.sign(
    {
      sub: "test",
      iss: "some-company-client-id",
      name: "Bob"
    } as any,
    process.env.JWT_TOKEN_SECRET,
    { expiresIn: 300 }
  );

  const event = {
    queryStringParameters: {
      authToken: token
    }
  } as any;

  const res = await handler(event, undefined as any, undefined as any);
  expect(res).toHaveProperty("principalId", "test");
  expect(res).toHaveProperty("context.iss");
  expect(res).toHaveProperty("context.sub");
});


test("Test connect authorizer failure", async () => {
  const token = jwt.sign(
    {
      sub: "test",
      iss: "some-company-client-id",
      name: "Bob"
    } as any,
    "wrong-secret",
    { expiresIn: 300 }
  );

  const event = {
    queryStringParameters: {
      authToken: token
    }
  } as any;

  expect(handler(event, undefined as any, undefined as any)).rejects.toBe("Unauthorized");
});
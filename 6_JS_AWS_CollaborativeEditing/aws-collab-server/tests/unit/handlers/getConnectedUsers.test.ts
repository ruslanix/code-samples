import { executeHandler, testConnectedClientRepos, createConnectedClient, mockWSClient_send } from "./lib";
import { handler } from "../../../src/handlers/getConnectedUsers";
import { Result } from "true-myth";

test("Test getConnectedUsers", async () => {

  // GIVEN
  testConnectedClientRepos.reset();
  testConnectedClientRepos.add(createConnectedClient({documentUrn: "document:A"}));
  testConnectedClientRepos.add(createConnectedClient({documentUrn: "document:A"}));
  testConnectedClientRepos.add(createConnectedClient({documentUrn: "document:B"}));

  mockWSClient_send.mockResolvedValue(Result.ok(true));

  // WHEN
  await executeHandler({
    handler,
    body: {
      documentUrn: "document:A"
    },
    eventExtra: {
      requestContext: {
        connectionId: "connection:A"
      }
    }
  });

  // THEN
  expect(mockWSClient_send.mock.calls.length).toBe(1);
  expect(mockWSClient_send.mock.calls[0][0]).toBe("connection:A");
  expect(mockWSClient_send.mock.calls[0][1]["action"]).toBe("users");
  expect(mockWSClient_send.mock.calls[0][1]["users"].length).toBe(2);
});

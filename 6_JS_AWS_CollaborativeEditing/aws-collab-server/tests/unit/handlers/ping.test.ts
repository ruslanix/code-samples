import {
  executeHandler,
  mockWSClient_send
} from "./lib";
import { handler } from "../../../src/handlers/ping";
import { Result } from "true-myth";

test("Ping test", async () => {

  // GIVEN
  mockWSClient_send.mockReset();
  mockWSClient_send.mockResolvedValue(Result.ok(true));

  // WHEN
  await executeHandler({
    handler,
    eventExtra: {
      requestContext: {
        connectionId: "connection:A"
      }
    }
  });

  // THEN
  expect(mockWSClient_send.mock.calls.length).toBe(1);
  expect(mockWSClient_send.mock.calls[0][0]).toBe("connection:A");
  expect(mockWSClient_send.mock.calls[0][1]["action"]).toBe("pong");
});

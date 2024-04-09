import { executeHandler, testConnectedClientRepos, testDocumentRepos, createDocument } from "./lib";
import { handler } from "../../../src/handlers/leaveCollab";
import { ConnectedClient } from "../../../src/clients";

test("Test leave collab", async () => {

  testDocumentRepos.reset();
  testConnectedClientRepos.reset();

  testDocumentRepos.add(createDocument({urn: "document:A"}));

  const connectedClient: ConnectedClient = {
    connectionId: "connection:A",
    documentUrn: "document:A"
  };
  testConnectedClientRepos.add(connectedClient);

  const res = await executeHandler({
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
  expect(res.data).toHaveProperty("documentUrn");
  expect(res.data).toHaveProperty("connectionId");

  const findRes = await testConnectedClientRepos.findByDocumentUrn("document:A");
  expect(findRes.isOk()).toBe(true);
  findRes.map(data => {
    expect(data.length).toBe(0);
  });

  // document:A has no more connection - should be removed
  let docRes = await testDocumentRepos.getByDocumentUrn("document:A");
  expect(docRes.isOk()).toBe(false);
});

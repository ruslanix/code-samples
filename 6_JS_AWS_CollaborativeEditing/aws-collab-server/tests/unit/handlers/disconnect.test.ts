import { executeHandler, testConnectedClientRepos, testDocumentRepos, createDocument } from "./lib";
import { handler } from "../../../src/handlers/disconnect";
import { ConnectedClient } from "../../../src/clients";

test("Test disconnect", async () => {

  testDocumentRepos.reset();
  testConnectedClientRepos.reset();

  testDocumentRepos.add(createDocument({urn: "document:A"}));
  testDocumentRepos.add(createDocument({urn: "document:B"}));

  const connectedClient1: ConnectedClient = {
    connectionId: "connection:A",
    documentUrn: "document:A"
  };
  const connectedClient2: ConnectedClient = {
    connectionId: "connection:A",
    documentUrn: "document:B"
  };
  const connectedClient3: ConnectedClient = {
    connectionId: "connection:B",
    documentUrn: "document:B"
  };
  testConnectedClientRepos.add(connectedClient1);
  testConnectedClientRepos.add(connectedClient2);
  testConnectedClientRepos.add(connectedClient3);

  const res = await executeHandler({
    handler,
    eventExtra: {
      requestContext: {
        connectionId: "connection:A"
      }
    }
  });
  expect(res.statusCode).toBe(200);

  let findRes = await testConnectedClientRepos.findByDocumentUrn("document:A");
  expect(findRes.isOk()).toBe(true);
  findRes.map(data => {
    expect(data.length).toBe(0);
  });
  findRes = await testConnectedClientRepos.findByDocumentUrn("document:B");
  expect(findRes.isOk()).toBe(true);
  findRes.map(data => {
    expect(data.length).toBe(1);
  });

  // document:A has no more connection - should be removed
  let docRes = await testDocumentRepos.getByDocumentUrn("document:A");
  expect(docRes.isOk()).toBe(false);

  // document:B has another connection - should stay
  docRes = await testDocumentRepos.getByDocumentUrn("document:B");
  expect(docRes.isOk()).toBe(true);
});

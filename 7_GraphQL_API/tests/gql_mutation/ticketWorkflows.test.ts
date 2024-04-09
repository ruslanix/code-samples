import {queryAdminAPI} from "../../../support/gql_client";

describe('Test ticket workflows mutations', () => {

  const selector = `
    id
    title
    description
    activeRevision {
      id
      flowChart {
        nodes
        edges
      }
      created_at
    }
    draftRevision {
      id
      flowChart {
        nodes
        edges
      }
      created_at
    }
    created_at
    updated_at
    deleted_at
  `;

  const createTestWorkflow = async () => {
    const createResponse = await queryAdminAPI(`
      mutation {
        createTicketWorkflow(workflow: {
          title: "Test title"
        }) {
          id
        }
      }
    `);

    const id  = createResponse.body.data.createTicketWorkflow.id;

    expect(id).toBeDefined();

    return id;
  }

  it('create ticket workflow', async () => {
    const createMutation = `
      mutation {
        createTicketWorkflow(workflow: {
          title: "Test title"
          description: "Test description"
        }) {
          ${selector}
        }
      }
    `;

    const createResponse = await queryAdminAPI(createMutation);

    expect(createResponse.body.data.createTicketWorkflow.title).toEqual('Test title');
    expect(createResponse.body.data.createTicketWorkflow.description).toEqual('Test description');
    expect(createResponse.body.data.createTicketWorkflow.activeRevision).toBeNull();
    expect(createResponse.body.data.createTicketWorkflow.draftRevision).toBeNull();
    expect(createResponse.body.data.createTicketWorkflow.created_at).not.toBeNull();
    expect(createResponse.body.data.createTicketWorkflow.updated_at).not.toBeNull();
    expect(createResponse.body.data.createTicketWorkflow.deleted_at).toBeNull();
  });

  it('update ticket workflow', async () => {

    const id = await createTestWorkflow();

    const updateMutation = `
      mutation {
        updateTicketWorkflow(id: ${id} workflow: {
          title: "Test title updated"
          description: "Test description updated"
        }) {
          ${selector}
        }
      }
    `;

    const updateResponse = await queryAdminAPI(updateMutation);

    expect(updateResponse.body.data.updateTicketWorkflow.title).toEqual('Test title updated');
    expect(updateResponse.body.data.updateTicketWorkflow.description).toEqual('Test description updated');
    expect(updateResponse.body.data.updateTicketWorkflow.activeRevision).toBeNull();
    expect(updateResponse.body.data.updateTicketWorkflow.draftRevision).toBeNull();
    expect(updateResponse.body.data.updateTicketWorkflow.updated_at).not.toBeNull();
    expect(updateResponse.body.data.updateTicketWorkflow.deleted_at).toBeNull();
  });

  it('Delete / restore ticket workflow', async () => {

    const id = await createTestWorkflow();

    let response = await queryAdminAPI(`
      mutation {
        deleteTicketWorkflow(id: ${id}) {
          ${selector}
        }
      }
    `);
    expect(response.body.data.deleteTicketWorkflow.deleted_at).not.toBeNull();

    response = await queryAdminAPI(`
      mutation {
        restoreTicketWorkflow(id: ${id}) {
          ${selector}
        }
      }
    `);
    expect(response.body.data.restoreTicketWorkflow.deleted_at).toBeNull();
  });
});

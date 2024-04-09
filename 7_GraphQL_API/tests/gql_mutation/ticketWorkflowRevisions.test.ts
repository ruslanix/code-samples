import {queryAdminAPI} from "../../../support/gql_client";

describe('Test ticket workflow revisions mutations', () => {

  const selector = `
    id
    title
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

  it('create draft revision', async () => {
    const id = await createTestWorkflow();

    const mutation = `
      mutation {
        createWorkflowDraftRevision(workflowId: ${id}, flowChart: {
          nodes: "{\\"a\\": 1}"
          edges: "{\\"b\\": 2}"
        }) {
          ${selector}
        }
      }
    `;

    const response = await queryAdminAPI(mutation);

    expect(response.body.data.createWorkflowDraftRevision.id).toEqual(id);
    expect(response.body.data.createWorkflowDraftRevision.activeRevision).toBeNull();

    expect(response.body.data.createWorkflowDraftRevision.draftRevision).toMatchObject({
      id: expect.any(String),
      flowChart: {
        nodes: '{\"a\": 1}',
        edges: '{\"b\": 2}'
      },
      created_at: expect.any(String)
    });
  });

  it('shule not be possible create draft revision if already exist', async () => {
    const id = await createTestWorkflow();

    const mutation = `
      mutation {
        createWorkflowDraftRevision(workflowId: ${id}, flowChart: {
          nodes: "{\\"a\\": 1}"
          edges: "{\\"b\\": 2}"
        }) {
          ${selector}
        }
      }
    `;

    let response = await queryAdminAPI(mutation);
    expect(response.body.data.createWorkflowDraftRevision.activeRevision).toBeNull();
    expect(response.body.data.createWorkflowDraftRevision.draftRevision).not.toBeNull();

    response = await queryAdminAPI(mutation);
    expect(response.body.errors[0].extensions.validation).toEqual({
      "workflowId": ["admin.workflows.validation.draft_already_exist"],
    });
  });

  it('create new draft revision when previous has been published', async () => {
    const id = await createTestWorkflow();

    // Create draft
    const createMutation = `
      mutation {
        createWorkflowDraftRevision(workflowId: ${id}, flowChart: {
          nodes: "{\\"a\\": 1}"
          edges: "{\\"b\\": 2}"
        }) {
          ${selector}
        }
      }
    `;

    let response = await queryAdminAPI(createMutation);
    expect(response.body.data.createWorkflowDraftRevision.activeRevision).toBeNull();
    expect(response.body.data.createWorkflowDraftRevision.draftRevision).not.toBeNull();
    const revisionId = response.body.data.createWorkflowDraftRevision.draftRevision.id;

    // Publish draft
    const publishMutation = () => `
      mutation {
        publishWorkflowDraftRevision(workflowId: ${id}) {
          ${selector}
        }
      }
    `;

    response = await queryAdminAPI(publishMutation());
    expect(response.body.data.publishWorkflowDraftRevision.activeRevision.id).toEqual(revisionId);
    expect(response.body.data.publishWorkflowDraftRevision.draftRevision).toBeNull();

    // Create another draft
    response = await queryAdminAPI(createMutation);
    expect(response.body.data.createWorkflowDraftRevision.activeRevision.id).toEqual(revisionId);
    expect(response.body.data.createWorkflowDraftRevision.draftRevision).not.toBeNull();
    const revision2Id = response.body.data.createWorkflowDraftRevision.draftRevision.id;

    // Publish draft 2
    response = await queryAdminAPI(publishMutation());
    expect(response.body.data.publishWorkflowDraftRevision.activeRevision.id).toEqual(revision2Id);
    expect(response.body.data.publishWorkflowDraftRevision.draftRevision).toBeNull();
  });

  it('update draft revision', async () => {
    const id = await createTestWorkflow();

    const mutation = `
      mutation {
        createWorkflowDraftRevision(workflowId: ${id}, flowChart: {
          nodes: "{\\"a\\": 1}"
          edges: "{\\"b\\": 2}"
        }) {
          ${selector}
        }
      }
    `;

    let response = await queryAdminAPI(mutation);
    expect(response.body.data.createWorkflowDraftRevision.activeRevision).toBeNull();
    expect(response.body.data.createWorkflowDraftRevision.draftRevision).not.toBeNull();
    const revisionId = response.body.data.createWorkflowDraftRevision.draftRevision.id;

    const updateMutation = `
      mutation {
        updateWorkflowDraftRevision(workflowId: ${id} flowChart: {
          nodes: "{\\"a\\": 10}"
          edges: "{\\"b\\": 20}"
        }) {
          ${selector}
        }
      }
    `;

    response = await queryAdminAPI(updateMutation);
    expect(response.body.data.updateWorkflowDraftRevision.activeRevision).toBeNull();
    expect(response.body.data.updateWorkflowDraftRevision.draftRevision).toMatchObject({
      id: revisionId,
      flowChart: {
        nodes: '{\"a\": 10}',
        edges: '{\"b\": 20}'
      },
      created_at: expect.any(String)
    });
  });

  it('should not be possible to update draft revision if not exist', async () => {
    const id = await createTestWorkflow();
    const updateMutation = `
      mutation {
        updateWorkflowDraftRevision(workflowId: ${id} flowChart: {
          nodes: "{\\"a\\": 10}"
          edges: "{\\"b\\": 20}"
        }) {
          ${selector}
        }
      }
    `;

    const response = await queryAdminAPI(updateMutation);
    expect(response.body.errors[0].extensions.validation).toEqual({
      "workflowId": ["admin.workflows.validation.draft_not_exist"],
    });
  });

  it('publish draft revision', async () => {
    const id = await createTestWorkflow();

    const mutation = `
      mutation {
        createWorkflowDraftRevision(workflowId: ${id}, flowChart: {
          nodes: "{\\"a\\": 1}"
          edges: "{\\"b\\": 2}"
        }) {
          ${selector}
        }
      }
    `;

    let response = await queryAdminAPI(mutation);
    expect(response.body.data.createWorkflowDraftRevision.activeRevision).toBeNull();
    expect(response.body.data.createWorkflowDraftRevision.draftRevision).not.toBeNull();
    const revisionId = response.body.data.createWorkflowDraftRevision.draftRevision.id;

    const updateMutation = `
      mutation {
        publishWorkflowDraftRevision(workflowId: ${id}) {
          ${selector}
        }
      }
    `;

    response = await queryAdminAPI(updateMutation);
    expect(response.body.data.publishWorkflowDraftRevision.draftRevision).toBeNull();
    expect(response.body.data.publishWorkflowDraftRevision.activeRevision).toMatchObject({
      id: revisionId,
      flowChart: {
        nodes: '{\"a\": 1}',
        edges: '{\"b\": 2}'
      },
      created_at: expect.any(String)
    });
  });

  it('should not be possible to publish draft revision if not exist', async () => {
    const id = await createTestWorkflow();
    const updateMutation = `
      mutation {
        publishWorkflowDraftRevision(workflowId: ${id}) {
          ${selector}
        }
      }
    `;

    const response = await queryAdminAPI(updateMutation);
    expect(response.body.errors[0].extensions.validation).toEqual({
      "workflowId": ["admin.workflows.validation.draft_not_exist"],
    });
  });
});

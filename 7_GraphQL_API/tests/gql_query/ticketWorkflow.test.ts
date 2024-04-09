import {queryAdminAPI} from '../../../support/gql_client';

describe('ticket workflow', () => {
  it('returns workflow', async () => {
    const query = `
      query {
        ticketWorkflow(id: 4) {
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
          allRevisions(page: 1) {
            totalCount
            results {
              id
              flowChart {
                nodes
                edges
              }
              created_at
            }
          }
          created_at
          updated_at
          deleted_at
        }
      }
    `;

    const response = await queryAdminAPI(query);
    expect(response).toMatchSnapshot();
  });
});

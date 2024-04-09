import {queryAdminAPI} from '../../../support/gql_client';

describe('ticket workflows', () => {
  it('returns workflows', async () => {
    const query = `
      query {
        ticketWorkflows {
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
        }
      }
    `;

    const response = await queryAdminAPI(query);
    expect(response).toMatchSnapshot();
  });

  it('returns deleted workflows', async () => {
    const query = `
      query {
        ticketWorkflows(filter: {isDeleted: true}) {
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
        }
      }
    `;

    const response = await queryAdminAPI(query);
    expect(response).toMatchSnapshot();
  });
});

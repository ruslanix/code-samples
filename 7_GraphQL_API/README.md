# GraphQL API

[GraphQL API](https://graphql.org/) example based on Laravel [Lighthouse](https://lighthouse-php.com/) framework.

I suggest to use GraphQL in rich api's that includes a lof of:
- queries of different entities and their relations: select users with emails, organizations and etc ...
- paginated queries with filters, parameters
- mutations: save user, save organization etc ..

Usually such API's used for communication between FE and BE parts of single page application. I.e. React on FE + Symfony / Laravel as BE + Graphql API as communication standard.

For service level communication regular REST API is more preferable. For example for communication between application and search indexation service.

Folder contains:
- `workflows.graphql` - describe API and types
- `TicketWorkflowsResolver` - example of API controller
- `App\GraphQL\Validators` - validation example
- `Models`
- `tests` - API test using [Jest testing framework](https://jestjs.io/)
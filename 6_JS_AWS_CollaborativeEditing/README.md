# JS AWS Collaborative editing POC (proof of concept)

There is internal content management system that allows to create and publish articles, news, guides and other publish content on company portal.
My task was to implement POC of collaborative editing. Real-time collaborative editing allows multiple people to edit the same document at the same time, see each others cursors and changes immideatly. Something similar as Goodle Docs online editing.

Solution based on:
- [Prosemirror rich-text editor](https://prosemirror.net/) and it core [collab](https://prosemirror.net/docs/guide/#collab) module. This module may generate the stream of changesets on every editor change. These changeset stream may be applied immediately to local document, and then sent to peers, which merge in these changes automatically (without manual conflict resolution), so that editing can proceed uninterrupted, and the documents keep converging.
- AWS (Amazon Web Services) as communication collab sever which stream changesets between editor instances ( clients / peers)
- communication done through [AWS Websocket Gateway](https://docs.aws.amazon.com/apigateway/latest/developerguide/apigateway-websocket-api.html)

## `aws-collab-server` folder - collab server

To work with and set up AWS infrastructure - [Servreless Framework](https://www.serverless.com/framework) used. It allows to define all required AWS resources in yml file and efficiently allocate them. Please check `aws-collab-server/serverless.yml` file where resources defined:
- Authentication and roles setup
- Websocket gateway API setup
- DynamoDB tables
- Lamda functions that process API and DB callbacks
- etc ...

## `prosemirror-client` folder

Contains Prosemirror plugin that communicates with collab server. Main logic located in `/plugins/collab/Collab Websocket.ts` file. Also you can find   [Storybook](https://storybook.js.org/) story that use this plugin.

There were a lot of challenged parts. One of it - copy / paste large images. AWS websocket has some limit for payload size for one call. When editor receive base64 encoded image data - we can't just stream it as one piece. We have to zip it, split by chunks and then stream chunks and reassemble on each peers.
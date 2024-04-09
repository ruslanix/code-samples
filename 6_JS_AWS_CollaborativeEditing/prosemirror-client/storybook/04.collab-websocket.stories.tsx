import * as React from 'react';
import { storiesOf } from '@storybook/react';

import { CollabWebSocketConnectionManagerSimple, CollabOptions } from '@company/content-editor';
import {} from '@company/content-editor'
import * as jwt  from "jsonwebtoken";
import { ArticleEditor } from '@company/product-content-editor';

const CollabWebsocketDemo = () => {

  // Setup constants
  const DOC_PREFIX = prompt(
    'Type something uniq for document names prefix. Use same prefix for storybook collaboration',
    String(Math.floor(Math.random()*1000))
  );
  console.log("Doc prefix", DOC_PREFIX);

  const WEBSOCKET_URL = prompt('Type websocket API url.', "wss://abcdefg.execute-api.eu-west-1.amazonaws.com/dev");
  //const WEBSOCKET_URL = prompt('Type websocket API url.', "ws://localhost:3001");
  const JWT_TOKEN_SECRET = prompt('JWT secret', "ABCDEFG");

  const DOC = "Example";
  const USER = "User_" + Math.floor(Math.random()*1000);

  const PAYLOAD = {
    sub: USER,
    iss: "some-company-client-id",
    name: USER,
    aud: DOC_PREFIX + DOC
  };
  const TOKEN = jwt.sign(
    PAYLOAD,
    JWT_TOKEN_SECRET,
    { expiresIn: 30000 }
  );

  // Setup editors
  const editorRef = React.useRef(null);

  const wsManager = new CollabWebSocketConnectionManagerSimple(WEBSOCKET_URL, TOKEN);
  const collabOpt: CollabOptions = {
    documentUrn: DOC_PREFIX + DOC,
    auth: TOKEN,
    userIdentity: USER,
    connectionManager: wsManager
  };

  return (
    <div>
      <h3>Document prefix: `{DOC_PREFIX}`. Use same prefix if you want to edit this document in another storybook instance</h3>
      <h3>User `{USER}` editor:</h3>
      <ArticleEditor
        ref={editorRef}
        options={{ initialContent: '<h1>Test document</h1>' }}
        useCollab={collabOpt}
      />

    </div>
  );
};

storiesOf('KB Editor', module).add('CollabWebSocket', () => <CollabWebsocketDemo />);

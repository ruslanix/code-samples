import { Plugin } from 'prosemirror-state';
import { Decoration, DecorationSet } from 'prosemirror-view';
import { CollabUser } from './CollabWebsocket';

/**
 * Draw collab users cursors
 */
export function getCursorPlugin(): Plugin {
  const cursorsPlugin: Plugin = new Plugin({
    state: {
      init() {
        return [];
      },
      apply(tr, state) {
        const meta = tr.getMeta(cursorsPlugin);
        if (meta && meta.users) {
          return meta.users;
        }
        return state;
      }
    },
    props: {
      decorations(state) {
        const users = cursorsPlugin.getState(state);
        return DecorationSet.create(
          state.doc,
          users
            .filter((user: CollabUser) => user.cursorPosition && user.cursorPosition >= 0)
            .map((user: CollabUser) => {
              const cursorEl = document.createElement('span');
              cursorEl.classList.add('cursor');
              cursorEl.style.borderLeftColor = user.cursorColor;
              cursorEl.title = user.displayName;
              return Decoration.widget(
                user.cursorPosition,
                cursorEl,
                {
                  // need this for some optimization
                  key: `${user.identity}_${user.cursorPosition}`
                }
              );
            })
        );
      }
    }
  });

  return cursorsPlugin;
}

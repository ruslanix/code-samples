import { EditorEventBus } from '../../../utils/EventBus';
import { throttle } from '../../../helpers';


/**
 * Responsibilites:
 * - create singlenton webscoket connection to AWS API that used by all editors
 * - check and close stalled connection
 * - notify editors when connection alive so editors could join collab and do their stuff
 */
export interface CollabWebSocketConnectionManager {
  /**
   * Event fired when connection is live
   * General Idea - send this event every xxx  seconds
   * - all subscribers notified if connectin online and could check if they need to join collab and etc
   * - subscribers should listen WebScoket.onClose event and switch their status off and wait onOnline again
   *
   * @TODO:
   * - maybe fire once when websocket connection established
   */
  onOnline(handler: (websocket: WebSocket) => void): void;

  /**
   *
   * Subscribe
   */
  onOffline(handler: () => void): void;

  /**
   *
   * Unsubscribe
   */
  unsibscribeOnline(handler: (websocket: WebSocket) => void): void;

  /**
   *
   * Unsubscribe offline
   */
  unsibscribeOffline(handler: () => void): void;

  /**
   * Check if online (or at least socket in `CONNECTING` status )
   */
  isOnline(): boolean;

  /**
   * Try to connect. Force connection
   */
  connect(): void;
}

export class CollabWebSocketConnectionManagerSimple implements CollabWebSocketConnectionManager {
  pingMessageDelay = 5000;
  closeInactiveDelay = 30000;
  tickDelay = 500;

  apiUrl: string;
  jwt: string;
  socket: WebSocket|undefined;
  eventBus: EditorEventBus;
  lastResponseAt: number;
  nextConnectionAttemptAt: number;
  connectionAttemptsCnt: number;

  constructor(apiUrl: string, jwt: string) {
    this.apiUrl = apiUrl;
    this.jwt = jwt;
    this.socket = undefined;
    this.lastResponseAt = 0;
    this.nextConnectionAttemptAt = 0;
    this.connectionAttemptsCnt = 0;

    this.eventBus = new EditorEventBus();

    // @TODO: clearInterval if no subscribers
    setInterval(this.tick.bind(this), 500);
  }

  /**
   * Subscribe
   */
  onOnline(handler: (websocket: WebSocket) => void) {
    this.eventBus.subscribe("online", handler);
  }

  /**
   * Subscribe
   */
  onOffline(handler: () => void) {
    this.eventBus.subscribe("offline", handler);
  }

  /**
   *
   * Unsubscribe
   */
  unsibscribeOnline(handler: (websocket: WebSocket) => void) {
    this.eventBus.unsubscribe("online", handler);
  }

  /**
   *
   * Unsubscribe
   */
  unsibscribeOffline(handler: () => void) {
    this.eventBus.unsubscribe("offline", handler);
  }

  /**
   * Check if online (or at least socket in `CONNECTING` status )
   * It is not strict method because connection might be still in OPEN state even if real internet connection lost
   */
  isOnline(): boolean {
    return (this.socket && this.socket.readyState !== WebSocket.CLOSED && this.socket.readyState !== WebSocket.CLOSING) as boolean;
  }

  connect(): void {
    if (this.isOnline()) {
      return;
    }

    this.socket = new WebSocket(`${this.apiUrl}?authToken=${this.jwt}`);
    this.socket.addEventListener('message', (event: MessageEvent) => {
      this.lastResponseAt = Date.now();
    });
    this.socket.addEventListener('close', (event: CloseEvent) => {
      // it might be event from previously closed socket, trying to detect this and ignore
      if (this.isOnline()) {
        return;
      }
      this.goOffline();
    });
  }

  private tick = throttle(() => {
    const socket = this.socket as WebSocket;

    // If no subscribers - don't do anything
    if (!this.eventBus.hasListeners()) {
      this.lastResponseAt = 0;
      return;
    }

    if (socket && socket.readyState === WebSocket.OPEN) {

      // Reset some
      this.nextConnectionAttemptAt = 0;
      this.connectionAttemptsCnt = 0;

      // Check stalled connections
      // Initialize lastResponseAt if it was reset
      if (!this.lastResponseAt) {
        this.lastResponseAt = Date.now();
      }

      if (Date.now() - this.lastResponseAt > this.closeInactiveDelay) {
        console.log('[CollabWebSocketManagerSimple] stalled connection detected. Closing it.');
        socket.close();
        this.goOffline();
        return;
      }

      if (Date.now() - this.lastResponseAt > this.pingMessageDelay) {
        this.ping();
      }

      // Notify online
      this.eventBus.publish("online", socket);

    } else if (!socket || socket.readyState === WebSocket.CLOSED || socket.readyState === WebSocket.CLOSING) {

      if (!this.nextConnectionAttemptAt) {
        this.nextConnectionAttemptAt = Date.now();
      }

      if (Date.now() >= this.nextConnectionAttemptAt) {
        console.log('[CollabWebSocketManagerSimple] creating new websocket');

        this.lastResponseAt = 0;
        this.connectionAttemptsCnt++;
        this.nextConnectionAttemptAt = Date.now() + this.getBackoffDelay(this.connectionAttemptsCnt);

        this.connect();
      }
    }
  }, 500);

  private ping = throttle(() => {
    (this.socket as WebSocket).send(JSON.stringify({
      action: "ping"
    }));
  }, this.pingMessageDelay);

  private goOffline() {
    this.socket = undefined;
    this.lastResponseAt = 0;
    // Notify offline
    this.eventBus.publish("offline");
  }

  private getBackoffDelay(retryCnt: number): number {
    if (retryCnt > 4) {
      return 10000;
    } else if (retryCnt > 3) {
      return 5000;
    } else if (retryCnt > 2) {
      return 3000;
    } else if (retryCnt > 1) {
      return 2000;
    }

    return 1000;
  }
}

export default CollabWebSocketConnectionManagerSimple;

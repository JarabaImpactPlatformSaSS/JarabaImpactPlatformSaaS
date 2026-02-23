/**
 * @file
 * js/messaging-client.js
 *
 * WebSocket client for Jaraba Secure Messaging.
 * Handles real-time message delivery, typing indicators, presence updates,
 * and read receipts over a WebSocket connection.
 *
 * Features:
 * - CSRF token authentication via /session/token.
 * - Auto-reconnect with exponential backoff (1s, 2s, 4s, 8s, max 30s).
 * - Frame types: message.new, message.read, typing, presence.
 * - Heartbeat keep-alive (30s interval).
 * - Event-driven architecture with pub/sub.
 *
 * Ref: Doc Tecnico #178 - Sprint 4 Frontend
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Messaging WebSocket client singleton.
   *
   * @namespace Drupal.jarabaMessaging
   */
  Drupal.jarabaMessaging = Drupal.jarabaMessaging || {};

  /**
   * Frame types used in the WebSocket protocol.
   *
   * @enum {string}
   */
  var FRAME_TYPES = {
    MESSAGE_NEW: 'message.new',
    MESSAGE_READ: 'message.read',
    MESSAGE_EDIT: 'message.edit',
    MESSAGE_DELETE: 'message.delete',
    TYPING: 'typing',
    PRESENCE: 'presence',
    ERROR: 'error',
    ACK: 'ack',
    PING: 'ping',
    PONG: 'pong'
  };

  /**
   * Reconnect configuration.
   */
  var RECONNECT = {
    BASE_DELAY: 1000,
    MAX_DELAY: 30000,
    MULTIPLIER: 2
  };

  /**
   * Heartbeat interval in milliseconds.
   */
  var HEARTBEAT_INTERVAL = 30000;

  /**
   * CSRF token cache.
   */
  var csrfToken = null;
  var csrfTokenPromise = null;

  /**
   * Fetch CSRF token from Drupal session endpoint.
   *
   * @return {Promise<string>} The CSRF token.
   */
  function fetchCsrfToken() {
    if (csrfToken) {
      return Promise.resolve(csrfToken);
    }
    if (csrfTokenPromise) {
      return csrfTokenPromise;
    }
    csrfTokenPromise = fetch('/session/token', {
      credentials: 'same-origin'
    })
      .then(function (response) {
        if (!response.ok) {
          throw new Error(Drupal.t('Failed to fetch CSRF token'));
        }
        return response.text();
      })
      .then(function (token) {
        csrfToken = token;
        csrfTokenPromise = null;
        return token;
      })
      .catch(function (error) {
        csrfTokenPromise = null;
        throw error;
      });
    return csrfTokenPromise;
  }

  /**
   * WebSocket client class.
   *
   * @param {Object} config
   * @param {string} config.wsUrl - WebSocket server URL.
   * @param {number} config.userId - Current user ID.
   * @param {number} config.tenantId - Tenant ID.
   * @param {string} config.apiBase - REST API base path.
   */
  function MessagingClient(config) {
    this.wsUrl = config.wsUrl;
    this.userId = config.userId;
    this.tenantId = config.tenantId;
    this.apiBase = config.apiBase || '/api/v1/messaging';
    this.socket = null;
    this.reconnectAttempt = 0;
    this.reconnectTimer = null;
    this.heartbeatTimer = null;
    this.isConnecting = false;
    this.isDestroyed = false;
    this.listeners = {};
  }

  /**
   * Connect to the WebSocket server.
   */
  MessagingClient.prototype.connect = function () {
    var self = this;

    if (this.isDestroyed || this.isConnecting) {
      return;
    }

    if (this.socket && this.socket.readyState === WebSocket.OPEN) {
      return;
    }

    this.isConnecting = true;

    fetchCsrfToken()
      .then(function (token) {
        if (self.isDestroyed) {
          return;
        }

        var url = self.wsUrl;
        if (url.indexOf('?') === -1) {
          url += '?token=' + encodeURIComponent(token);
        } else {
          url += '&token=' + encodeURIComponent(token);
        }
        url += '&user_id=' + encodeURIComponent(self.userId);
        url += '&tenant_id=' + encodeURIComponent(self.tenantId);

        self.socket = new WebSocket(url);
        self.socket.onopen = self._onOpen.bind(self);
        self.socket.onmessage = self._onMessage.bind(self);
        self.socket.onclose = self._onClose.bind(self);
        self.socket.onerror = self._onError.bind(self);
      })
      .catch(function (error) {
        self.isConnecting = false;
        console.error('[JarabaMessaging]', Drupal.t('Connection failed:'), error);
        self._scheduleReconnect();
      });
  };

  /**
   * Handle WebSocket open event.
   *
   * @private
   */
  MessagingClient.prototype._onOpen = function () {
    this.isConnecting = false;
    this.reconnectAttempt = 0;
    this._startHeartbeat();
    this._emit('connected', { userId: this.userId });
    console.info('[JarabaMessaging]', Drupal.t('Connected to messaging server.'));
  };

  /**
   * Handle incoming WebSocket message.
   *
   * @param {MessageEvent} event
   * @private
   */
  MessagingClient.prototype._onMessage = function (event) {
    var frame;
    try {
      frame = JSON.parse(event.data);
    } catch (e) {
      console.warn('[JarabaMessaging]', Drupal.t('Invalid frame received.'));
      return;
    }

    if (frame.type === FRAME_TYPES.PONG) {
      return;
    }

    switch (frame.type) {
      case FRAME_TYPES.MESSAGE_NEW:
        this._emit('message:new', frame.payload);
        break;

      case FRAME_TYPES.MESSAGE_READ:
        this._emit('message:read', frame.payload);
        break;

      case FRAME_TYPES.MESSAGE_EDIT:
        this._emit('message:edit', frame.payload);
        break;

      case FRAME_TYPES.MESSAGE_DELETE:
        this._emit('message:delete', frame.payload);
        break;

      case FRAME_TYPES.TYPING:
        this._emit('typing', frame.payload);
        break;

      case FRAME_TYPES.PRESENCE:
        this._emit('presence', frame.payload);
        break;

      case FRAME_TYPES.ACK:
        this._emit('ack', frame.payload);
        break;

      case FRAME_TYPES.ERROR:
        console.error('[JarabaMessaging]', Drupal.t('Server error:'), frame.payload);
        this._emit('error', frame.payload);
        break;

      default:
        console.warn('[JarabaMessaging]', Drupal.t('Unknown frame type: @type', { '@type': frame.type }));
    }
  };

  /**
   * Handle WebSocket close event.
   *
   * @param {CloseEvent} event
   * @private
   */
  MessagingClient.prototype._onClose = function (event) {
    this.isConnecting = false;
    this._stopHeartbeat();

    if (!this.isDestroyed) {
      this._emit('disconnected', { code: event.code, reason: event.reason });
      console.info('[JarabaMessaging]', Drupal.t('Disconnected. Reconnecting...'));
      this._scheduleReconnect();
    }
  };

  /**
   * Handle WebSocket error event.
   *
   * @param {Event} event
   * @private
   */
  MessagingClient.prototype._onError = function (event) {
    console.error('[JarabaMessaging]', Drupal.t('WebSocket error.'), event);
    this._emit('error', { type: 'connection', event: event });
  };

  /**
   * Schedule a reconnect with exponential backoff.
   *
   * @private
   */
  MessagingClient.prototype._scheduleReconnect = function () {
    var self = this;

    if (this.isDestroyed || this.reconnectTimer) {
      return;
    }

    var delay = Math.min(
      RECONNECT.BASE_DELAY * Math.pow(RECONNECT.MULTIPLIER, this.reconnectAttempt),
      RECONNECT.MAX_DELAY
    );

    this.reconnectAttempt++;

    console.info('[JarabaMessaging]', Drupal.t('Reconnecting in @delay ms (attempt @attempt)', {
      '@delay': delay,
      '@attempt': this.reconnectAttempt
    }));

    this._emit('reconnecting', {
      attempt: this.reconnectAttempt,
      delay: delay
    });

    this.reconnectTimer = setTimeout(function () {
      self.reconnectTimer = null;
      self.connect();
    }, delay);
  };

  /**
   * Start the heartbeat ping interval.
   *
   * @private
   */
  MessagingClient.prototype._startHeartbeat = function () {
    var self = this;
    this._stopHeartbeat();
    this.heartbeatTimer = setInterval(function () {
      self.send(FRAME_TYPES.PING, {});
    }, HEARTBEAT_INTERVAL);
  };

  /**
   * Stop the heartbeat ping interval.
   *
   * @private
   */
  MessagingClient.prototype._stopHeartbeat = function () {
    if (this.heartbeatTimer) {
      clearInterval(this.heartbeatTimer);
      this.heartbeatTimer = null;
    }
  };

  /**
   * Send a frame to the WebSocket server.
   *
   * @param {string} type - Frame type from FRAME_TYPES.
   * @param {Object} payload - Frame payload.
   */
  MessagingClient.prototype.send = function (type, payload) {
    if (!this.socket || this.socket.readyState !== WebSocket.OPEN) {
      console.warn('[JarabaMessaging]', Drupal.t('Cannot send: not connected.'));
      return false;
    }

    var frame = JSON.stringify({
      type: type,
      payload: payload,
      timestamp: new Date().toISOString()
    });

    this.socket.send(frame);
    return true;
  };

  /**
   * Send a typing indicator for a conversation.
   *
   * @param {string} conversationUuid - The conversation UUID.
   */
  MessagingClient.prototype.sendTyping = function (conversationUuid) {
    this.send(FRAME_TYPES.TYPING, {
      conversation_uuid: conversationUuid,
      user_id: this.userId
    });
  };

  /**
   * Send a read receipt for a conversation.
   *
   * @param {string} conversationUuid - The conversation UUID.
   */
  MessagingClient.prototype.sendReadReceipt = function (conversationUuid) {
    this.send(FRAME_TYPES.MESSAGE_READ, {
      conversation_uuid: conversationUuid,
      user_id: this.userId
    });
  };

  /**
   * Subscribe to an event.
   *
   * @param {string} event - Event name.
   * @param {Function} callback - Event handler.
   * @return {Function} Unsubscribe function.
   */
  MessagingClient.prototype.on = function (event, callback) {
    if (!this.listeners[event]) {
      this.listeners[event] = [];
    }
    this.listeners[event].push(callback);

    var self = this;
    return function () {
      self.off(event, callback);
    };
  };

  /**
   * Unsubscribe from an event.
   *
   * @param {string} event - Event name.
   * @param {Function} callback - Event handler to remove.
   */
  MessagingClient.prototype.off = function (event, callback) {
    if (!this.listeners[event]) {
      return;
    }
    this.listeners[event] = this.listeners[event].filter(function (cb) {
      return cb !== callback;
    });
  };

  /**
   * Emit an event to all subscribers.
   *
   * @param {string} event - Event name.
   * @param {*} data - Event data.
   * @private
   */
  MessagingClient.prototype._emit = function (event, data) {
    var callbacks = this.listeners[event] || [];
    for (var i = 0; i < callbacks.length; i++) {
      try {
        callbacks[i](data);
      } catch (error) {
        console.error('[JarabaMessaging]', Drupal.t('Event handler error:'), error);
      }
    }
  };

  /**
   * Make an authenticated REST API call.
   *
   * @param {string} endpoint - API endpoint path (relative to apiBase).
   * @param {Object} options - Fetch options.
   * @return {Promise<Object>} Parsed JSON response.
   */
  MessagingClient.prototype.apiCall = function (endpoint, options) {
    var self = this;
    options = options || {};

    return fetchCsrfToken().then(function (token) {
      var url = self.apiBase + endpoint;
      var headers = options.headers || {};
      headers['X-CSRF-Token'] = token;
      headers['Content-Type'] = headers['Content-Type'] || 'application/json';
      headers['Accept'] = 'application/json';

      return fetch(url, {
        method: options.method || 'GET',
        headers: headers,
        body: options.body ? JSON.stringify(options.body) : undefined,
        credentials: 'same-origin'
      }).then(function (response) {
        if (!response.ok) {
          throw new Error(Drupal.t('API request failed: @status', {
            '@status': response.status
          }));
        }
        return response.json();
      });
    });
  };

  /**
   * Destroy the client, close connection, clean up timers.
   */
  MessagingClient.prototype.destroy = function () {
    this.isDestroyed = true;
    this._stopHeartbeat();

    if (this.reconnectTimer) {
      clearTimeout(this.reconnectTimer);
      this.reconnectTimer = null;
    }

    if (this.socket) {
      this.socket.onclose = null;
      this.socket.onerror = null;
      this.socket.onmessage = null;
      this.socket.onopen = null;
      if (this.socket.readyState === WebSocket.OPEN || this.socket.readyState === WebSocket.CONNECTING) {
        this.socket.close(1000, 'Client destroyed');
      }
      this.socket = null;
    }

    this.listeners = {};
  };

  /**
   * Drupal behavior: Initialize WebSocket messaging client.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.jarabaMessagingClient = {
    attach: function (context) {
      var elements = once('jaraba-messaging-client', '[data-ws-url]', context);
      if (!elements.length) {
        return;
      }

      var container = elements[0];
      var wsUrl = container.getAttribute('data-ws-url');
      var userId = parseInt(container.getAttribute('data-user-id'), 10);
      var tenantId = parseInt(container.getAttribute('data-tenant-id'), 10);
      var apiBase = container.getAttribute('data-api-base') || '/api/v1/messaging';

      if (!wsUrl || !userId) {
        console.warn('[JarabaMessaging]', Drupal.t('Missing WebSocket URL or user ID.'));
        return;
      }

      // Create the client singleton.
      Drupal.jarabaMessaging.client = new MessagingClient({
        wsUrl: wsUrl,
        userId: userId,
        tenantId: tenantId,
        apiBase: apiBase
      });

      // Connect.
      Drupal.jarabaMessaging.client.connect();

      // Disconnect on page unload.
      window.addEventListener('beforeunload', function () {
        if (Drupal.jarabaMessaging.client) {
          Drupal.jarabaMessaging.client.destroy();
        }
      });
    },

    detach: function (context, settings, trigger) {
      if (trigger === 'unload' && Drupal.jarabaMessaging.client) {
        Drupal.jarabaMessaging.client.destroy();
        Drupal.jarabaMessaging.client = null;
      }
    }
  };

  // Expose FRAME_TYPES publicly.
  Drupal.jarabaMessaging.FRAME_TYPES = FRAME_TYPES;

})(Drupal, drupalSettings, once);

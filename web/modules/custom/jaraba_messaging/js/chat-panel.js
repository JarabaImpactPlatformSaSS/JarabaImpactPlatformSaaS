/**
 * @file
 * js/chat-panel.js
 *
 * Chat panel UI management. Handles conversation selection, message rendering,
 * scroll management, and modal triggers. Works both in the full messaging page
 * and the slide-in chat panel on other pages.
 *
 * Ref: Doc Tecnico #178 - Sprint 4 Frontend
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Maximum messages to render at once before paginating.
   */
  var MESSAGES_PER_PAGE = 50;

  /**
   * Scroll threshold (px from bottom) to auto-scroll on new message.
   */
  var AUTO_SCROLL_THRESHOLD = 150;

  /**
   * Drupal behavior: Chat panel and messaging page UI.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.jarabaChatPanel = {
    attach: function (context) {
      // Full messaging page.
      var messagingPages = once('jaraba-chat-panel-page', '.messaging-layout', context);
      messagingPages.forEach(function (container) {
        initMessagingPage(container);
      });

      // Slide-in chat panel.
      var panels = once('jaraba-chat-panel-slide', '.chat-panel', context);
      panels.forEach(function (panel) {
        initChatPanel(panel);
      });
    }
  };

  /**
   * Initialize the full messaging page.
   *
   * @param {HTMLElement} container - The .messaging-layout element.
   */
  function initMessagingPage(container) {
    var state = {
      activeConversationId: null,
      messages: [],
      page: 0,
      isLoadingMessages: false,
      container: container
    };

    var emptyState = container.querySelector('[data-messaging-empty-state]');
    var threadContainer = container.querySelector('[data-messaging-thread]');
    var messageArea = container.querySelector('[data-message-area]');
    var messageList = container.querySelector('[data-message-list]');
    var scrollAnchor = container.querySelector('[data-scroll-anchor]');
    var scrollBottomBtn = container.querySelector('[data-scroll-to-bottom]');
    var loadMoreBtn = container.querySelector('[data-load-more]');
    var threadTitle = container.querySelector('[data-thread-title]');
    var threadParticipants = container.querySelector('[data-thread-participants]');

    // Listen for conversation selection.
    container.addEventListener('click', function (event) {
      var item = event.target.closest('[data-conversation-id]');
      if (!item || !item.closest('.conversation-list')) {
        return;
      }

      var conversationId = item.getAttribute('data-conversation-id');
      selectConversation(conversationId);
    });

    // Scroll-to-bottom button.
    if (scrollBottomBtn) {
      scrollBottomBtn.addEventListener('click', function () {
        scrollToBottom(messageArea);
      });
    }

    // Show/hide scroll-to-bottom based on scroll position.
    if (messageArea) {
      messageArea.addEventListener('scroll', function () {
        var distanceFromBottom = messageArea.scrollHeight - messageArea.scrollTop - messageArea.clientHeight;
        if (scrollBottomBtn) {
          scrollBottomBtn.style.display = distanceFromBottom > AUTO_SCROLL_THRESHOLD ? 'flex' : 'none';
        }
      });
    }

    // Load more messages.
    if (loadMoreBtn) {
      loadMoreBtn.addEventListener('click', function () {
        if (state.activeConversationId && !state.isLoadingMessages) {
          loadMessages(state.activeConversationId, state.page + 1);
        }
      });
    }

    // Search button.
    var searchBtn = container.querySelector('[data-action="search-messages"]');
    if (searchBtn) {
      searchBtn.addEventListener('click', function () {
        openSearchOverlay(container, state.activeConversationId);
      });
    }

    // Listen for WebSocket events.
    if (Drupal.jarabaMessaging && Drupal.jarabaMessaging.client) {
      var client = Drupal.jarabaMessaging.client;

      client.on('message:new', function (payload) {
        if (payload.conversation_uuid === state.activeConversationId) {
          appendMessage(payload);
          autoScrollIfNeeded(messageArea);

          // Mark as read.
          client.sendReadReceipt(state.activeConversationId);
        }
      });

      client.on('message:edit', function (payload) {
        if (payload.conversation_uuid === state.activeConversationId) {
          updateMessageInDom(payload);
        }
      });

      client.on('message:delete', function (payload) {
        if (payload.conversation_uuid === state.activeConversationId) {
          markMessageDeleted(payload.message_id);
        }
      });
    }

    /**
     * Select a conversation and load its messages.
     *
     * @param {string} conversationId - Conversation UUID.
     */
    function selectConversation(conversationId) {
      if (state.activeConversationId === conversationId) {
        return;
      }

      state.activeConversationId = conversationId;
      state.messages = [];
      state.page = 0;

      // Update sidebar selection.
      var items = container.querySelectorAll('.conversation-list__item');
      items.forEach(function (item) {
        item.setAttribute('aria-selected', item.getAttribute('data-conversation-id') === conversationId ? 'true' : 'false');
      });

      // Show thread, hide empty state.
      if (emptyState) {
        emptyState.style.display = 'none';
      }
      if (threadContainer) {
        threadContainer.style.display = 'flex';
      }

      // Clear current messages.
      if (messageList) {
        messageList.innerHTML = '';
      }

      // Load conversation details and messages.
      loadConversationDetails(conversationId);
      loadMessages(conversationId, 0);

      // Mobile: switch views.
      var sidebar = container.querySelector('.messaging-layout__sidebar');
      var main = container.querySelector('.messaging-layout__main');
      if (window.innerWidth < 768) {
        if (sidebar) {
          sidebar.classList.remove('is-active');
        }
        if (main) {
          main.classList.add('is-active');
        }
      }
    }

    /**
     * Load conversation details (title, participants).
     *
     * @param {string} conversationId - Conversation UUID.
     */
    function loadConversationDetails(conversationId) {
      if (!Drupal.jarabaMessaging || !Drupal.jarabaMessaging.client) {
        return;
      }

      Drupal.jarabaMessaging.client.apiCall('/conversations/' + conversationId)
        .then(function (data) {
          if (threadTitle) {
            threadTitle.textContent = data.title || Drupal.t('Conversation');
          }
          if (threadParticipants && data.participants) {
            var names = data.participants.map(function (p) {
              return p.display_name;
            });
            threadParticipants.textContent = names.join(', ');
          }
        })
        .catch(function (error) {
          console.error('[JarabaChatPanel]', Drupal.t('Failed to load conversation details.'), error);
        });
    }

    /**
     * Load messages for a conversation.
     *
     * @param {string} conversationId - Conversation UUID.
     * @param {number} page - Page number (0-based).
     */
    function loadMessages(conversationId, page) {
      if (state.isLoadingMessages) {
        return;
      }

      state.isLoadingMessages = true;

      var endpoint = '/conversations/' + conversationId + '/messages?page=' + page + '&limit=' + MESSAGES_PER_PAGE;

      Drupal.jarabaMessaging.client.apiCall(endpoint)
        .then(function (data) {
          state.page = page;
          state.isLoadingMessages = false;

          var messages = data.messages || data.data || [];

          if (page === 0) {
            // Initial load: render and scroll to bottom.
            messages.forEach(function (msg) {
              appendMessage(msg);
            });
            scrollToBottom(messageArea);

            // Mark as read.
            if (Drupal.jarabaMessaging.client) {
              Drupal.jarabaMessaging.client.sendReadReceipt(conversationId);
            }
          } else {
            // Load more: prepend and maintain scroll position.
            var previousScrollHeight = messageArea ? messageArea.scrollHeight : 0;
            messages.reverse().forEach(function (msg) {
              prependMessage(msg);
            });
            if (messageArea) {
              messageArea.scrollTop = messageArea.scrollHeight - previousScrollHeight;
            }
          }

          // Show/hide load more button.
          if (loadMoreBtn) {
            loadMoreBtn.style.display = messages.length >= MESSAGES_PER_PAGE ? 'flex' : 'none';
          }
        })
        .catch(function (error) {
          state.isLoadingMessages = false;
          console.error('[JarabaChatPanel]', Drupal.t('Failed to load messages.'), error);
        });
    }

    /**
     * Append a message bubble to the message list.
     *
     * @param {Object} message - Message data object.
     */
    function appendMessage(message) {
      if (!messageList) {
        return;
      }
      var bubble = createMessageBubble(message);
      messageList.appendChild(bubble);
    }

    /**
     * Prepend a message bubble to the message list.
     *
     * @param {Object} message - Message data object.
     */
    function prependMessage(message) {
      if (!messageList) {
        return;
      }
      var bubble = createMessageBubble(message);
      messageList.insertBefore(bubble, messageList.firstChild);
    }

    /**
     * Update a message in the DOM after edit.
     *
     * @param {Object} payload - Edited message payload.
     */
    function updateMessageInDom(payload) {
      var bubble = messageList ? messageList.querySelector('[data-message-id="' + payload.message_id + '"]') : null;
      if (!bubble) {
        return;
      }
      var bodyEl = bubble.querySelector('.message-bubble__body');
      if (bodyEl && payload.body) {
        bodyEl.innerHTML = Drupal.checkPlain(payload.body);
      }
      // Add edited indicator.
      var footer = bubble.querySelector('.message-bubble__footer');
      if (footer && !footer.querySelector('.message-bubble__edited')) {
        var edited = document.createElement('span');
        edited.className = 'message-bubble__edited';
        edited.title = Drupal.t('Edited');
        edited.textContent = Drupal.t('edited');
        footer.appendChild(edited);
      }
    }

    /**
     * Mark a message as deleted in the DOM.
     *
     * @param {number|string} messageId - Message ID to mark deleted.
     */
    function markMessageDeleted(messageId) {
      var bubble = messageList ? messageList.querySelector('[data-message-id="' + messageId + '"]') : null;
      if (!bubble) {
        return;
      }
      bubble.classList.add('message-bubble--deleted');
      var bodyEl = bubble.querySelector('.message-bubble__body');
      if (bodyEl) {
        bodyEl.innerHTML = '<p class="message-bubble__deleted-text"><em>' + Drupal.t('This message was deleted.') + '</em></p>';
      }
      // Remove action menu.
      var actions = bubble.querySelector('.message-bubble__actions');
      if (actions) {
        actions.remove();
      }
    }

    // Check URL for pre-selected conversation.
    var urlParams = new URLSearchParams(window.location.search);
    var preselected = urlParams.get('conversation');
    if (preselected) {
      selectConversation(preselected);
    }
  }

  /**
   * Initialize the slide-in chat panel.
   *
   * @param {HTMLElement} panel - The .chat-panel element.
   */
  function initChatPanel(panel) {
    var toggle = panel.querySelector('.chat-panel__toggle');
    var closeBtn = panel.querySelector('.chat-panel__close');
    var overlay = panel.querySelector('[data-chat-panel-overlay]');
    var content = panel.querySelector('.chat-panel__content');
    var listView = panel.querySelector('[data-chat-panel-view="list"]');
    var threadView = panel.querySelector('[data-chat-panel-view="thread"]');
    var backBtn = panel.querySelector('.chat-panel__back');

    function openPanel() {
      panel.setAttribute('data-panel-state', 'open');
      if (content) {
        content.setAttribute('aria-hidden', 'false');
      }
      if (toggle) {
        toggle.setAttribute('aria-expanded', 'true');
        toggle.setAttribute('aria-label', Drupal.t('Close chat'));
      }
    }

    function closePanel() {
      panel.setAttribute('data-panel-state', 'closed');
      if (content) {
        content.setAttribute('aria-hidden', 'true');
      }
      if (toggle) {
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', Drupal.t('Open chat'));
      }
    }

    function showListView() {
      if (listView) {
        listView.style.display = 'flex';
      }
      if (threadView) {
        threadView.style.display = 'none';
      }
    }

    function showThreadView() {
      if (listView) {
        listView.style.display = 'none';
      }
      if (threadView) {
        threadView.style.display = 'flex';
      }
    }

    if (toggle) {
      toggle.addEventListener('click', function () {
        var isOpen = panel.getAttribute('data-panel-state') === 'open';
        if (isOpen) {
          closePanel();
        } else {
          openPanel();
        }
      });
    }

    if (closeBtn) {
      closeBtn.addEventListener('click', closePanel);
    }

    if (overlay) {
      overlay.addEventListener('click', closePanel);
    }

    if (backBtn) {
      backBtn.addEventListener('click', showListView);
    }

    // Close on Escape key.
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && panel.getAttribute('data-panel-state') === 'open') {
        closePanel();
      }
    });

    // Conversation item click within panel.
    panel.addEventListener('click', function (event) {
      var item = event.target.closest('[data-conversation-id]');
      if (!item || !item.closest('.conversation-list')) {
        return;
      }
      showThreadView();
    });
  }

  /**
   * Create a message bubble DOM element.
   *
   * @param {Object} msg - Message data object.
   * @return {HTMLElement} The message bubble element.
   */
  function createMessageBubble(msg) {
    var bubble = document.createElement('div');
    var userId = Drupal.jarabaMessaging && Drupal.jarabaMessaging.client
      ? Drupal.jarabaMessaging.client.userId
      : 0;

    var isOwn = parseInt(msg.sender_id, 10) === userId;
    var isSystem = msg.type === 'system';

    var classes = ['message-bubble'];
    if (isSystem) {
      classes.push('message-bubble--system');
    } else if (isOwn) {
      classes.push('message-bubble--own');
    } else {
      classes.push('message-bubble--other');
    }
    if (msg.is_deleted) {
      classes.push('message-bubble--deleted');
    }
    if (msg.status === 'sending') {
      classes.push('message-bubble--sending');
    }

    bubble.className = classes.join(' ');
    bubble.setAttribute('data-message-id', msg.id || msg.message_id || '');
    bubble.setAttribute('data-sender-id', msg.sender_id || '');
    bubble.setAttribute('role', 'article');

    var time = msg.created_at ? new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '';
    bubble.setAttribute('aria-label', (msg.sender_name || '') + ', ' + time);

    var html = '';

    // Sender name for others' messages.
    if (!isOwn && !isSystem && msg.sender_name) {
      html += '<span class="message-bubble__sender">' + Drupal.checkPlain(msg.sender_name) + '</span>';
    }

    // Body.
    html += '<div class="message-bubble__body">';
    if (msg.is_deleted) {
      html += '<p class="message-bubble__deleted-text"><em>' + Drupal.t('This message was deleted.') + '</em></p>';
    } else {
      html += Drupal.checkPlain(msg.body || '');
    }
    html += '</div>';

    // Footer.
    html += '<div class="message-bubble__footer">';
    html += '<time class="message-bubble__time" datetime="' + Drupal.checkPlain(msg.created_at || '') + '">' + Drupal.checkPlain(time) + '</time>';
    if (msg.is_edited) {
      html += '<span class="message-bubble__edited" title="' + Drupal.t('Edited') + '">' + Drupal.t('edited') + '</span>';
    }
    html += '</div>';

    // Actions for own messages.
    if (isOwn && !msg.is_deleted && !isSystem) {
      html += '<div class="message-bubble__actions">';
      html += '<button class="message-bubble__actions-trigger" type="button" aria-label="' + Drupal.t('Message options') + '" aria-haspopup="true" aria-expanded="false">';
      html += '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="19" r="2"/></svg>';
      html += '</button>';
      html += '<ul class="message-bubble__actions-menu" role="menu" style="display: none;">';
      html += '<li role="menuitem"><button class="message-bubble__action-btn" type="button" data-action="edit-message" data-message-id="' + (msg.id || msg.message_id || '') + '">' + Drupal.t('Edit') + '</button></li>';
      html += '<li role="menuitem"><button class="message-bubble__action-btn message-bubble__action-btn--danger" type="button" data-action="delete-message" data-message-id="' + (msg.id || msg.message_id || '') + '">' + Drupal.t('Delete') + '</button></li>';
      html += '</ul>';
      html += '</div>';
    }

    // Reactions.
    if (msg.reactions && msg.reactions.length) {
      html += '<div class="message-bubble__reactions">';
      msg.reactions.forEach(function (reaction) {
        var activeClass = reaction.is_own ? ' message-bubble__reaction--active' : '';
        html += '<button class="message-bubble__reaction' + activeClass + '" type="button" data-action="toggle-reaction" data-emoji="' + Drupal.checkPlain(reaction.emoji) + '" data-message-id="' + (msg.id || msg.message_id || '') + '" aria-label="' + Drupal.checkPlain(reaction.emoji) + ' ' + reaction.count + '">';
        html += '<span class="message-bubble__reaction-emoji">' + Drupal.checkPlain(reaction.emoji) + '</span>';
        html += '<span class="message-bubble__reaction-count">' + reaction.count + '</span>';
        html += '</button>';
      });
      html += '</div>';
    }

    bubble.innerHTML = html;
    return bubble;
  }

  /**
   * Scroll a container to the bottom.
   *
   * @param {HTMLElement} container - Scrollable container.
   */
  function scrollToBottom(container) {
    if (!container) {
      return;
    }
    container.scrollTop = container.scrollHeight;
  }

  /**
   * Auto-scroll if user is near the bottom.
   *
   * @param {HTMLElement} container - Scrollable container.
   */
  function autoScrollIfNeeded(container) {
    if (!container) {
      return;
    }
    var distanceFromBottom = container.scrollHeight - container.scrollTop - container.clientHeight;
    if (distanceFromBottom < AUTO_SCROLL_THRESHOLD) {
      scrollToBottom(container);
    }
  }

  /**
   * Open the search overlay for a conversation.
   *
   * @param {HTMLElement} container - Parent container.
   * @param {string} conversationId - Active conversation UUID.
   */
  function openSearchOverlay(container, conversationId) {
    // Create search overlay if not present.
    var overlay = container.querySelector('.search-overlay');
    if (!overlay) {
      overlay = document.createElement('div');
      overlay.className = 'search-overlay';
      overlay.innerHTML =
        '<div class="search-overlay__header">' +
        '<div class="search-overlay__input-wrapper">' +
        '<span class="search-overlay__input-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span>' +
        '<input type="search" class="search-overlay__input" placeholder="' + Drupal.t('Search messages...') + '" aria-label="' + Drupal.t('Search messages') + '" autocomplete="off">' +
        '</div>' +
        '<button class="search-overlay__close" type="button" aria-label="' + Drupal.t('Close search') + '"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>' +
        '</div>' +
        '<div class="search-overlay__results"></div>';

      container.querySelector('.messaging-layout__main').appendChild(overlay);
    }

    // Show overlay.
    overlay.classList.add('search-overlay--active');

    var input = overlay.querySelector('.search-overlay__input');
    var closeBtn = overlay.querySelector('.search-overlay__close');

    if (input) {
      input.focus();
      input.value = '';
    }

    if (closeBtn) {
      closeBtn.addEventListener('click', function () {
        overlay.classList.remove('search-overlay--active');
      });
    }
  }

})(Drupal, drupalSettings, once);

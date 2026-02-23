/**
 * @file
 * js/conversation-list.js
 *
 * Conversation list filtering, search, and unread badge updates.
 * Handles real-time updates to conversation order and unread counts
 * via WebSocket events.
 *
 * Ref: Doc Tecnico #178 - Sprint 4 Frontend
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Debounce delay for search input (ms).
   */
  var SEARCH_DEBOUNCE = 300;

  /**
   * Drupal behavior: Conversation list management.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.jarabaConversationList = {
    attach: function (context) {
      var lists = once('jaraba-conversation-list', '[data-conversation-list]', context);
      lists.forEach(function (listEl) {
        initConversationList(listEl);
      });
    }
  };

  /**
   * Initialize a conversation list.
   *
   * @param {HTMLElement} listEl - The .conversation-list element.
   */
  function initConversationList(listEl) {
    var searchInput = listEl.querySelector('[data-conversation-search]');
    var itemsList = listEl.querySelector('.conversation-list__items');
    var newConversationBtn = listEl.querySelector('[data-action="new-conversation"]');

    // Search/filter functionality.
    if (searchInput) {
      var debounceTimer = null;

      searchInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () {
          filterConversations(itemsList, searchInput.value.trim().toLowerCase());
        }, SEARCH_DEBOUNCE);
      });

      // Clear search on Escape.
      searchInput.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
          searchInput.value = '';
          filterConversations(itemsList, '');
          searchInput.blur();
        }
      });
    }

    // Keyboard navigation for conversation items.
    if (itemsList) {
      itemsList.addEventListener('keydown', function (event) {
        var currentItem = event.target.closest('.conversation-list__item');
        if (!currentItem) {
          return;
        }

        var items = Array.from(itemsList.querySelectorAll('.conversation-list__item:not([style*="display: none"])'));
        var currentIndex = items.indexOf(currentItem);

        switch (event.key) {
          case 'ArrowDown':
            event.preventDefault();
            if (currentIndex < items.length - 1) {
              items[currentIndex + 1].focus();
            }
            break;

          case 'ArrowUp':
            event.preventDefault();
            if (currentIndex > 0) {
              items[currentIndex - 1].focus();
            }
            break;

          case 'Enter':
          case ' ':
            event.preventDefault();
            currentItem.click();
            break;

          case 'Home':
            event.preventDefault();
            if (items.length) {
              items[0].focus();
            }
            break;

          case 'End':
            event.preventDefault();
            if (items.length) {
              items[items.length - 1].focus();
            }
            break;
        }
      });
    }

    // New conversation button.
    if (newConversationBtn) {
      newConversationBtn.addEventListener('click', function () {
        openNewConversationDialog();
      });
    }

    // Listen for WebSocket events to update the list in real time.
    if (Drupal.jarabaMessaging && Drupal.jarabaMessaging.client) {
      var client = Drupal.jarabaMessaging.client;

      // New message: update preview, badge, and reorder.
      client.on('message:new', function (payload) {
        updateConversationPreview(itemsList, payload);
        updateUnreadBadge(listEl, payload.conversation_uuid);
        moveConversationToTop(itemsList, payload.conversation_uuid);
      });

      // Read receipt: clear unread badge for conversation.
      client.on('message:read', function (payload) {
        clearUnreadBadge(itemsList, payload.conversation_uuid);
        updateTotalUnread(listEl);
      });

      // Presence updates: could update online indicators.
      client.on('presence', function (payload) {
        updatePresenceInList(itemsList, payload);
      });
    }
  }

  /**
   * Filter conversation items by search query.
   *
   * @param {HTMLElement} itemsList - The conversation list UL element.
   * @param {string} query - Lowercase search query.
   */
  function filterConversations(itemsList, query) {
    if (!itemsList) {
      return;
    }

    var items = itemsList.querySelectorAll('.conversation-list__item');
    var visibleCount = 0;

    items.forEach(function (item) {
      if (!query) {
        item.style.display = '';
        visibleCount++;
        return;
      }

      var name = item.querySelector('.conversation-list__name');
      var preview = item.querySelector('.conversation-list__preview');
      var nameText = name ? name.textContent.toLowerCase() : '';
      var previewText = preview ? preview.textContent.toLowerCase() : '';

      if (nameText.indexOf(query) !== -1 || previewText.indexOf(query) !== -1) {
        item.style.display = '';
        visibleCount++;
      } else {
        item.style.display = 'none';
      }
    });

    // Show empty state if no results.
    var emptyState = itemsList.querySelector('.conversation-list__empty');
    if (emptyState) {
      emptyState.style.display = visibleCount === 0 && query ? '' : 'none';
    }
  }

  /**
   * Update conversation preview text and time for a new message.
   *
   * @param {HTMLElement} itemsList - The conversation list UL element.
   * @param {Object} payload - New message payload.
   */
  function updateConversationPreview(itemsList, payload) {
    if (!itemsList) {
      return;
    }

    var item = itemsList.querySelector('[data-conversation-id="' + payload.conversation_uuid + '"]');
    if (!item) {
      return;
    }

    // Update preview text.
    var preview = item.querySelector('.conversation-list__preview');
    if (preview && payload.body) {
      var truncated = payload.body.length > 80 ? payload.body.substring(0, 80) + '...' : payload.body;
      preview.textContent = (payload.sender_name ? payload.sender_name + ': ' : '') + truncated;
    }

    // Update time.
    var timeEl = item.querySelector('.conversation-list__time');
    if (timeEl && payload.created_at) {
      var date = new Date(payload.created_at);
      timeEl.textContent = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
      timeEl.setAttribute('datetime', payload.created_at);
    }
  }

  /**
   * Increment the unread badge for a conversation.
   *
   * @param {HTMLElement} listEl - The .conversation-list element.
   * @param {string} conversationUuid - Conversation UUID.
   */
  function updateUnreadBadge(listEl, conversationUuid) {
    var itemsList = listEl.querySelector('.conversation-list__items');
    if (!itemsList) {
      return;
    }

    var item = itemsList.querySelector('[data-conversation-id="' + conversationUuid + '"]');
    if (!item) {
      return;
    }

    // Check if this conversation is currently active (selected).
    if (item.getAttribute('aria-selected') === 'true') {
      return;
    }

    // Add unread class.
    item.classList.add('conversation-list__item--unread');

    // Update or create badge.
    var badge = item.querySelector('[data-unread-badge]');
    if (badge) {
      var count = parseInt(badge.textContent, 10) || 0;
      count++;
      badge.textContent = count > 99 ? '99+' : count;
    } else {
      var previewRow = item.querySelector('.conversation-list__preview-row');
      if (previewRow) {
        badge = document.createElement('span');
        badge.className = 'conversation-list__unread-badge';
        badge.setAttribute('data-unread-badge', '');
        badge.textContent = '1';
        previewRow.appendChild(badge);
      }
    }

    // Update total unread.
    updateTotalUnread(listEl);
  }

  /**
   * Clear the unread badge for a conversation.
   *
   * @param {HTMLElement} itemsList - The conversation list UL element.
   * @param {string} conversationUuid - Conversation UUID.
   */
  function clearUnreadBadge(itemsList, conversationUuid) {
    if (!itemsList) {
      return;
    }

    var item = itemsList.querySelector('[data-conversation-id="' + conversationUuid + '"]');
    if (!item) {
      return;
    }

    item.classList.remove('conversation-list__item--unread');
    var badge = item.querySelector('[data-unread-badge]');
    if (badge) {
      badge.remove();
    }
  }

  /**
   * Recalculate and update the total unread count.
   *
   * @param {HTMLElement} listEl - The .conversation-list element.
   */
  function updateTotalUnread(listEl) {
    var badges = listEl.querySelectorAll('[data-unread-badge]');
    var total = 0;
    badges.forEach(function (badge) {
      var text = badge.textContent.replace('+', '');
      total += parseInt(text, 10) || 0;
    });

    var totalEl = listEl.querySelector('[data-unread-total]');
    if (totalEl) {
      if (total > 0) {
        totalEl.textContent = total > 99 ? '99+' : total;
        totalEl.style.display = '';
      } else {
        totalEl.style.display = 'none';
      }
    }

    // Also update the chat panel toggle badge if present.
    var panelBadge = document.querySelector('[data-chat-panel-badge]');
    if (panelBadge) {
      if (total > 0) {
        panelBadge.textContent = total > 99 ? '99+' : total;
        panelBadge.style.display = '';
      } else {
        panelBadge.style.display = 'none';
      }
    }

    // Also update widget badge.
    var widgetBadge = document.querySelector('[data-widget-badge]');
    if (widgetBadge) {
      if (total > 0) {
        widgetBadge.textContent = total > 99 ? '99+' : total;
        widgetBadge.style.display = '';
      } else {
        widgetBadge.style.display = 'none';
      }
    }
  }

  /**
   * Move a conversation item to the top of the list.
   *
   * @param {HTMLElement} itemsList - The conversation list UL element.
   * @param {string} conversationUuid - Conversation UUID.
   */
  function moveConversationToTop(itemsList, conversationUuid) {
    if (!itemsList) {
      return;
    }

    var item = itemsList.querySelector('[data-conversation-id="' + conversationUuid + '"]');
    if (!item || item === itemsList.firstElementChild) {
      return;
    }

    itemsList.insertBefore(item, itemsList.firstElementChild);
  }

  /**
   * Update presence indicators within the conversation list.
   *
   * @param {HTMLElement} itemsList - The conversation list UL element.
   * @param {Object} payload - Presence payload { user_id, status }.
   */
  function updatePresenceInList(itemsList, payload) {
    if (!itemsList || !payload.user_id || !payload.status) {
      return;
    }

    // Update presence badge on relevant avatar elements.
    var badges = itemsList.querySelectorAll('[data-presence-user="' + payload.user_id + '"]');
    badges.forEach(function (badge) {
      badge.className = 'presence-badge presence-badge--avatar presence-badge--' + payload.status;
    });
  }

  /**
   * Open the new conversation dialog.
   * Placeholder: triggers a custom event that other modules can listen for.
   */
  function openNewConversationDialog() {
    var event = new CustomEvent('jaraba-messaging:new-conversation', {
      bubbles: true,
      detail: {}
    });
    document.dispatchEvent(event);

    // Fallback: log if no handler is registered.
    console.info('[JarabaConversationList]', Drupal.t('New conversation requested.'));
  }

})(Drupal, drupalSettings, once);

/**
 * @file
 * js/message-composer.js
 *
 * Message composer behavior: auto-growing textarea, file attachment trigger,
 * send on Enter (Shift+Enter for newline), character counter, typing
 * indicator emission via WebSocket.
 *
 * Ref: Doc Tecnico #178 - Sprint 4 Frontend
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Typing indicator debounce (ms) - how often to emit typing events.
   */
  var TYPING_DEBOUNCE = 2000;

  /**
   * Character count warning threshold (percentage of max).
   */
  var CHAR_WARNING_THRESHOLD = 0.9;

  /**
   * Drupal behavior: Message composer management.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.jarabaMessageComposer = {
    attach: function (context) {
      var composers = once('jaraba-message-composer', '[data-message-composer]', context);
      composers.forEach(function (composerEl) {
        initComposer(composerEl);
      });
    }
  };

  /**
   * Initialize a message composer instance.
   *
   * @param {HTMLElement} composerEl - The .message-composer element.
   */
  function initComposer(composerEl) {
    var textarea = composerEl.querySelector('[data-composer-textarea]');
    var sendBtn = composerEl.querySelector('[data-action="send-message"]');
    var attachBtn = composerEl.querySelector('[data-action="attach-file"]');
    var fileInput = composerEl.querySelector('[data-file-input]');
    var charCurrent = composerEl.querySelector('[data-char-current]');
    var charCount = composerEl.querySelector('[data-char-count]');
    var editBar = composerEl.querySelector('[data-composer-edit-bar]');
    var cancelEditBtn = composerEl.querySelector('[data-action="cancel-edit"]');
    var attachmentsArea = composerEl.querySelector('[data-composer-attachments]');

    var typingTimer = null;
    var lastTypingEmit = 0;
    var editingMessageId = null;
    var queuedFiles = [];

    if (!textarea || !sendBtn) {
      return;
    }

    var maxLength = parseInt(textarea.getAttribute('maxlength'), 10) || 5000;

    /**
     * Auto-grow the textarea to fit content.
     */
    function autoGrow() {
      textarea.style.height = 'auto';
      var scrollHeight = textarea.scrollHeight;
      var maxHeight = parseInt(getComputedStyle(textarea).maxHeight, 10) || 160;
      textarea.style.height = Math.min(scrollHeight, maxHeight) + 'px';
    }

    /**
     * Update the character counter.
     */
    function updateCharCount() {
      var length = textarea.value.length;

      if (charCurrent) {
        charCurrent.textContent = length;
      }

      if (charCount) {
        charCount.classList.remove('message-composer__char-count--warning', 'message-composer__char-count--error');

        if (length >= maxLength) {
          charCount.classList.add('message-composer__char-count--error');
        } else if (length >= maxLength * CHAR_WARNING_THRESHOLD) {
          charCount.classList.add('message-composer__char-count--warning');
        }
      }
    }

    /**
     * Update the send button enabled/disabled state.
     */
    function updateSendButton() {
      var hasText = textarea.value.trim().length > 0;
      var hasFiles = queuedFiles.length > 0;
      sendBtn.disabled = !(hasText || hasFiles);
    }

    /**
     * Emit a typing indicator event via WebSocket.
     */
    function emitTypingIndicator() {
      var now = Date.now();
      if (now - lastTypingEmit < TYPING_DEBOUNCE) {
        return;
      }

      lastTypingEmit = now;

      if (Drupal.jarabaMessaging && Drupal.jarabaMessaging.client) {
        // Get the active conversation UUID from the parent layout.
        var layoutEl = composerEl.closest('[data-ws-url]') || composerEl.closest('.messaging-layout');
        if (!layoutEl) {
          return;
        }

        var activeItem = layoutEl.querySelector('.conversation-list__item[aria-selected="true"]');
        if (activeItem) {
          var conversationUuid = activeItem.getAttribute('data-conversation-id');
          Drupal.jarabaMessaging.client.sendTyping(conversationUuid);
        }
      }
    }

    /**
     * Send the message (or save edit).
     */
    function sendMessage() {
      var body = textarea.value.trim();
      if (!body && queuedFiles.length === 0) {
        return;
      }

      if (!Drupal.jarabaMessaging || !Drupal.jarabaMessaging.client) {
        console.warn('[JarabaComposer]', Drupal.t('Messaging client not available.'));
        return;
      }

      var client = Drupal.jarabaMessaging.client;

      // Get active conversation UUID.
      var layoutEl = composerEl.closest('[data-ws-url]') || composerEl.closest('.messaging-layout');
      if (!layoutEl) {
        return;
      }

      var activeItem = layoutEl.querySelector('.conversation-list__item[aria-selected="true"]');
      if (!activeItem) {
        console.warn('[JarabaComposer]', Drupal.t('No conversation selected.'));
        return;
      }

      var conversationUuid = activeItem.getAttribute('data-conversation-id');

      if (editingMessageId) {
        // Edit existing message.
        client.apiCall('/conversations/' + conversationUuid + '/messages/' + editingMessageId, {
          method: 'PATCH',
          body: { body: body }
        })
          .then(function () {
            cancelEdit();
          })
          .catch(function (error) {
            console.error('[JarabaComposer]', Drupal.t('Failed to edit message.'), error);
          });
      } else {
        // Send new message.
        // Optimistic UI: emit event immediately.
        var tempId = 'temp-' + Date.now();
        var optimisticMessage = {
          id: tempId,
          body: body,
          sender_id: client.userId,
          sender_name: Drupal.t('You'),
          created_at: new Date().toISOString(),
          is_own: true,
          is_edited: false,
          is_deleted: false,
          reactions: [],
          type: 'user',
          status: 'sending',
          conversation_uuid: conversationUuid
        };

        // Dispatch local event for optimistic rendering.
        var event = new CustomEvent('jaraba-messaging:optimistic-message', {
          bubbles: true,
          detail: optimisticMessage
        });
        composerEl.dispatchEvent(event);

        client.apiCall('/conversations/' + conversationUuid + '/messages', {
          method: 'POST',
          body: { body: body }
        })
          .then(function (response) {
            // Replace temp message with real one.
            var replaceEvent = new CustomEvent('jaraba-messaging:message-confirmed', {
              bubbles: true,
              detail: { temp_id: tempId, message: response }
            });
            composerEl.dispatchEvent(replaceEvent);
          })
          .catch(function (error) {
            console.error('[JarabaComposer]', Drupal.t('Failed to send message.'), error);
            // Mark as failed.
            var failEvent = new CustomEvent('jaraba-messaging:message-failed', {
              bubbles: true,
              detail: { temp_id: tempId, error: error }
            });
            composerEl.dispatchEvent(failEvent);
          });
      }

      // Clear textarea.
      textarea.value = '';
      autoGrow();
      updateCharCount();
      updateSendButton();

      // Clear file queue.
      queuedFiles = [];
      if (attachmentsArea) {
        attachmentsArea.innerHTML = '';
        attachmentsArea.style.display = 'none';
      }

      textarea.focus();
    }

    /**
     * Enter edit mode for a specific message.
     *
     * @param {string} messageId - The message ID to edit.
     * @param {string} currentBody - The current message body text.
     */
    function enterEditMode(messageId, currentBody) {
      editingMessageId = messageId;
      textarea.value = currentBody;
      autoGrow();
      updateCharCount();
      updateSendButton();

      if (editBar) {
        editBar.style.display = 'flex';
      }

      textarea.focus();
    }

    /**
     * Cancel edit mode.
     */
    function cancelEdit() {
      editingMessageId = null;
      textarea.value = '';
      autoGrow();
      updateCharCount();
      updateSendButton();

      if (editBar) {
        editBar.style.display = 'none';
      }
    }

    /**
     * Add a file to the attachment queue and show preview.
     *
     * @param {File} file - The file to queue.
     */
    function addFileToQueue(file) {
      queuedFiles.push(file);
      updateSendButton();

      if (attachmentsArea) {
        attachmentsArea.style.display = 'flex';

        var preview = document.createElement('div');
        preview.className = 'attachment-preview';
        preview.innerHTML =
          '<div class="attachment-preview__thumbnail">' +
          '<span class="attachment-preview__thumbnail-icon" aria-hidden="true">' +
          '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>' +
          '</span>' +
          '</div>' +
          '<div class="attachment-preview__info">' +
          '<span class="attachment-preview__name">' + Drupal.checkPlain(file.name) + '</span>' +
          '<span class="attachment-preview__meta">' + formatFileSize(file.size) + '</span>' +
          '</div>' +
          '<button class="attachment-preview__remove" type="button" aria-label="' + Drupal.t('Remove file') + '">' +
          '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>' +
          '</button>';

        // Show image thumbnail if applicable.
        if (file.type && file.type.startsWith('image/')) {
          var reader = new FileReader();
          reader.onload = function (e) {
            var thumb = preview.querySelector('.attachment-preview__thumbnail');
            if (thumb) {
              thumb.innerHTML = '<img src="' + e.target.result + '" class="attachment-preview__thumbnail-img" alt="' + Drupal.checkPlain(file.name) + '">';
            }
          };
          reader.readAsDataURL(file);
        }

        // Remove button handler.
        var removeBtn = preview.querySelector('.attachment-preview__remove');
        removeBtn.addEventListener('click', function () {
          var index = queuedFiles.indexOf(file);
          if (index > -1) {
            queuedFiles.splice(index, 1);
          }
          preview.remove();
          updateSendButton();
          if (queuedFiles.length === 0 && attachmentsArea) {
            attachmentsArea.style.display = 'none';
          }
        });

        attachmentsArea.appendChild(preview);
      }
    }

    // --- Event listeners ---

    // Auto-grow + char count on input.
    textarea.addEventListener('input', function () {
      autoGrow();
      updateCharCount();
      updateSendButton();
      emitTypingIndicator();
    });

    // Enter to send, Shift+Enter for newline.
    textarea.addEventListener('keydown', function (event) {
      if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        sendMessage();
      }
    });

    // Send button click.
    sendBtn.addEventListener('click', function () {
      sendMessage();
    });

    // Attach button: trigger file input.
    if (attachBtn && fileInput) {
      attachBtn.addEventListener('click', function () {
        fileInput.click();
      });

      fileInput.addEventListener('change', function () {
        var files = fileInput.files;
        for (var i = 0; i < files.length; i++) {
          addFileToQueue(files[i]);
        }
        // Reset input so same file can be selected again.
        fileInput.value = '';
      });
    }

    // Cancel edit button.
    if (cancelEditBtn) {
      cancelEditBtn.addEventListener('click', function () {
        cancelEdit();
      });
    }

    // Listen for edit-message action from message bubbles.
    var parentContainer = composerEl.closest('.messaging-layout__thread') || composerEl.closest('.chat-panel__thread-view');
    if (parentContainer) {
      parentContainer.addEventListener('click', function (event) {
        var editBtn = event.target.closest('[data-action="edit-message"]');
        if (editBtn) {
          var messageId = editBtn.getAttribute('data-message-id');
          var bubbleEl = editBtn.closest('.message-bubble');
          if (bubbleEl) {
            var bodyEl = bubbleEl.querySelector('.message-bubble__body');
            var currentBody = bodyEl ? bodyEl.textContent.trim() : '';
            enterEditMode(messageId, currentBody);
          }
        }

        // Toggle action menus.
        var trigger = event.target.closest('.message-bubble__actions-trigger');
        if (trigger) {
          var menu = trigger.nextElementSibling;
          if (menu) {
            var isVisible = menu.style.display !== 'none';
            // Close all other menus first.
            parentContainer.querySelectorAll('.message-bubble__actions-menu').forEach(function (m) {
              m.style.display = 'none';
            });
            menu.style.display = isVisible ? 'none' : 'block';
            trigger.setAttribute('aria-expanded', isVisible ? 'false' : 'true');
          }
        }

        // Delete message action.
        var deleteBtn = event.target.closest('[data-action="delete-message"]');
        if (deleteBtn) {
          var msgId = deleteBtn.getAttribute('data-message-id');
          if (confirm(Drupal.t('Are you sure you want to delete this message?'))) {
            deleteMessage(msgId);
          }
        }
      });

      // Close action menus when clicking elsewhere.
      document.addEventListener('click', function (event) {
        if (!event.target.closest('.message-bubble__actions')) {
          parentContainer.querySelectorAll('.message-bubble__actions-menu').forEach(function (m) {
            m.style.display = 'none';
          });
          parentContainer.querySelectorAll('.message-bubble__actions-trigger').forEach(function (t) {
            t.setAttribute('aria-expanded', 'false');
          });
        }
      });
    }

    /**
     * Delete a message via API.
     *
     * @param {string} messageId - The message ID to delete.
     */
    function deleteMessage(messageId) {
      if (!Drupal.jarabaMessaging || !Drupal.jarabaMessaging.client) {
        return;
      }

      var layoutEl = composerEl.closest('[data-ws-url]') || composerEl.closest('.messaging-layout');
      if (!layoutEl) {
        return;
      }

      var activeItem = layoutEl.querySelector('.conversation-list__item[aria-selected="true"]');
      if (!activeItem) {
        return;
      }

      var conversationUuid = activeItem.getAttribute('data-conversation-id');

      Drupal.jarabaMessaging.client.apiCall('/conversations/' + conversationUuid + '/messages/' + messageId, {
        method: 'DELETE'
      })
        .catch(function (error) {
          console.error('[JarabaComposer]', Drupal.t('Failed to delete message.'), error);
        });
    }

    // Expose enterEditMode for external use.
    composerEl.enterEditMode = enterEditMode;
    composerEl.cancelEdit = cancelEdit;

    // Listen for typing indicator from WebSocket.
    if (Drupal.jarabaMessaging && Drupal.jarabaMessaging.client) {
      Drupal.jarabaMessaging.client.on('typing', function (payload) {
        var typingIndicator = composerEl.closest('.messaging-layout__thread, .chat-panel__thread-view');
        if (!typingIndicator) {
          return;
        }

        var indicator = typingIndicator.querySelector('[data-typing-indicator]');
        var typingText = typingIndicator.querySelector('[data-typing-text]');

        if (indicator && typingText) {
          typingText.textContent = Drupal.t('@name is typing...', {
            '@name': payload.user_name || Drupal.t('Someone')
          });
          indicator.style.display = 'block';

          // Auto-hide after 3 seconds.
          clearTimeout(typingTimer);
          typingTimer = setTimeout(function () {
            indicator.style.display = 'none';
          }, 3000);
        }
      });
    }
  }

  /**
   * Format a file size in bytes to a human-readable string.
   *
   * @param {number} bytes - File size in bytes.
   * @return {string} Formatted size string.
   */
  function formatFileSize(bytes) {
    if (bytes === 0) {
      return '0 B';
    }
    var units = ['B', 'KB', 'MB', 'GB'];
    var i = Math.floor(Math.log(bytes) / Math.log(1024));
    var size = (bytes / Math.pow(1024, i)).toFixed(1);
    return size + ' ' + units[i];
  }

})(Drupal, drupalSettings, once);

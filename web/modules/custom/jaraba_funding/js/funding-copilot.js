/**
 * @file
 * Funding Copilot — Floating chat widget for funding assistance.
 *
 * Handles widget toggle, message sending via API, message display with
 * user/assistant bubbles, suggestion chips, loading indicators and history.
 *
 * Funding Intelligence module.
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.fundingCopilot = {
    attach: function (context) {
      once('funding-copilot', '#funding-copilot', context).forEach(function (widget) {
        var toggleBtn = widget.querySelector('#funding-copilot-toggle');
        var closeBtn = widget.querySelector('#funding-copilot-close');
        var panel = widget.querySelector('#funding-copilot-panel');
        var messagesContainer = widget.querySelector('#funding-copilot-messages');
        var inputField = widget.querySelector('#funding-copilot-input');
        var sendBtn = widget.querySelector('#funding-copilot-send');
        var suggestionsContainer = widget.querySelector('#funding-copilot-suggestions');

        var isOpen = false;
        var isLoading = false;
        var historyLoaded = false;

        // ========================================
        // Widget toggle (open/close chat).
        // ========================================
        if (toggleBtn) {
          toggleBtn.addEventListener('click', function () {
            if (isOpen) {
              closePanel();
            } else {
              openPanel();
            }
          });
        }

        if (closeBtn) {
          closeBtn.addEventListener('click', function () {
            closePanel();
          });
        }

        /**
         * Open the chat panel.
         */
        function openPanel() {
          if (panel) {
            panel.style.display = 'flex';
          }
          isOpen = true;
          if (toggleBtn) {
            toggleBtn.setAttribute('aria-expanded', 'true');
          }
          if (inputField) {
            inputField.focus();
          }
          if (!historyLoaded) {
            loadHistory();
            historyLoaded = true;
          }
        }

        /**
         * Close the chat panel.
         */
        function closePanel() {
          if (panel) {
            panel.style.display = 'none';
          }
          isOpen = false;
          if (toggleBtn) {
            toggleBtn.setAttribute('aria-expanded', 'false');
          }
        }

        // ========================================
        // Message sending.
        // ========================================
        if (sendBtn) {
          sendBtn.addEventListener('click', function () {
            sendMessage();
          });
        }

        // Enter key to send.
        if (inputField) {
          inputField.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
              e.preventDefault();
              sendMessage();
            }
          });
        }

        /**
         * Send a message to the copilot API.
         */
        function sendMessage() {
          if (isLoading || !inputField) {
            return;
          }

          var message = inputField.value.trim();
          if (!message) {
            return;
          }

          // Display user message.
          appendMessage('user', message);
          inputField.value = '';

          // Hide suggestions after first message.
          if (suggestionsContainer) {
            suggestionsContainer.style.display = 'none';
          }

          // Show loading.
          isLoading = true;
          var loadingEl = appendLoading();

          var apiUrl = (drupalSettings.fundingIntelligence && drupalSettings.fundingIntelligence.apiCopilotUrl)
            || '/api/v1/funding/copilot';

          fetch(apiUrl, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ message: message })
          })
            .then(function (response) {
              if (!response.ok) {
                throw new Error('HTTP ' + response.status);
              }
              return response.json();
            })
            .then(function (data) {
              removeLoading(loadingEl);
              isLoading = false;

              if (data.success && data.data && data.data.response) {
                appendMessage('assistant', data.data.response);
              } else {
                appendMessage('assistant', data.message || Drupal.t('Lo siento, no pude procesar tu consulta. Intenta de nuevo.'));
              }
            })
            .catch(function (error) {
              console.warn('Funding Copilot: Failed to send message', error);
              removeLoading(loadingEl);
              isLoading = false;
              appendMessage('assistant', Drupal.t('Error de conexion. Intenta de nuevo.'));
            });
        }

        // ========================================
        // Message display.
        // ========================================

        /**
         * Append a message bubble to the messages area.
         *
         * @param {string} role
         *   Either 'user' or 'assistant'.
         * @param {string} content
         *   Message content text.
         */
        function appendMessage(role, content) {
          if (!messagesContainer) {
            return;
          }

          var messageEl = document.createElement('div');
          messageEl.className = 'funding-copilot__message funding-copilot__message--' + role;

          var contentEl = document.createElement('div');
          contentEl.className = 'funding-copilot__message-content';
          contentEl.textContent = content;

          messageEl.appendChild(contentEl);
          messagesContainer.appendChild(messageEl);

          // Scroll to bottom.
          messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        /**
         * Append a loading indicator.
         *
         * @return {HTMLElement}
         *   The loading element.
         */
        function appendLoading() {
          if (!messagesContainer) {
            return null;
          }

          var loadingEl = document.createElement('div');
          loadingEl.className = 'funding-copilot__message funding-copilot__message--loading';

          var dotsEl = document.createElement('div');
          dotsEl.className = 'funding-copilot__loading-dots';
          dotsEl.innerHTML = '<span></span><span></span><span></span>';

          loadingEl.appendChild(dotsEl);
          messagesContainer.appendChild(loadingEl);
          messagesContainer.scrollTop = messagesContainer.scrollHeight;

          return loadingEl;
        }

        /**
         * Remove a loading indicator.
         *
         * @param {HTMLElement} loadingEl
         *   The loading element to remove.
         */
        function removeLoading(loadingEl) {
          if (loadingEl && loadingEl.parentNode) {
            loadingEl.parentNode.removeChild(loadingEl);
          }
        }

        // ========================================
        // Suggestion chips.
        // ========================================
        if (suggestionsContainer) {
          suggestionsContainer.querySelectorAll('.funding-copilot__suggestion-chip').forEach(function (chip) {
            chip.addEventListener('click', function () {
              var chipMessage = chip.getAttribute('data-message');
              if (chipMessage && inputField) {
                inputField.value = chipMessage;
                sendMessage();
              }
            });
          });
        }

        // ========================================
        // History loading on open.
        // ========================================

        /**
         * Load conversation history from API.
         */
        function loadHistory() {
          var historyUrl = (drupalSettings.fundingIntelligence && drupalSettings.fundingIntelligence.apiCopilotHistoryUrl)
            || '/api/v1/funding/copilot/history';

          fetch(historyUrl, {
            method: 'GET',
            headers: {
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            }
          })
            .then(function (response) {
              if (!response.ok) {
                throw new Error('HTTP ' + response.status);
              }
              return response.json();
            })
            .then(function (data) {
              if (data.success && data.data && data.data.length > 0) {
                data.data.forEach(function (msg) {
                  appendMessage(msg.role || 'assistant', msg.content || '');
                });
              }
            })
            .catch(function (error) {
              console.warn('Funding Copilot: Failed to load history', error);
            });
        }
      });
    }
  };

})(Drupal, drupalSettings, once);

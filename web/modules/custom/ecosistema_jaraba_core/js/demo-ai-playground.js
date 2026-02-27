/**
 * @file
 * AI Playground — Demo interactiva de capacidades IA.
 *
 * Gestiona la interacción con escenarios preconfigurados y el widget
 * del copilot público. Consume el endpoint definido en drupalSettings.
 *
 * S1-06: Endpoint copilot leído de drupalSettings (no hardcoded).
 * S1-03: Incluye CSRF token en todas las peticiones POST.
 * S5-09: Typing indicator mientras espera respuesta IA.
 * S5-15: Keydown handlers (Enter/Space) en scenario cards.
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * CSRF token cacheado (S5-09: evitar fetch repetido).
   */
  var cachedCsrfToken = null;

  Drupal.behaviors.demoAiPlayground = {
    // S8-06: Cleanup en detach.
    detach(context, settings, trigger) {
      if (trigger === 'unload') {
        once.remove('demo-ai-playground', '[data-demo-playground]', context);
      }
    },
    attach(context) {
      once('demo-ai-playground', '[data-demo-playground]', context).forEach(function (container) {
        var config = drupalSettings.demoPlayground || {};
        var endpoint = config.copilotEndpoint || '';
        var maxMessages = config.maxMessages || 10;
        var messageCount = 0;

        // Botones de escenario.
        var scenarioButtons = container.querySelectorAll('[data-scenario-prompt]');
        var chatOutput = container.querySelector('[data-chat-output]');
        var chatInput = container.querySelector('[data-chat-input]');
        var sendButton = container.querySelector('[data-chat-send]');

        if (!endpoint || !chatOutput) {
          return;
        }

        /**
         * Obtiene el CSRF token de Drupal (S1-03), cacheado.
         */
        async function getCsrfToken() {
          if (!cachedCsrfToken) {
            var response = await fetch(Drupal.url('session/token'));
            cachedCsrfToken = await response.text();
          }
          return cachedCsrfToken;
        }

        /**
         * S5-09: Muestra indicador de escritura.
         */
        function showTypingIndicator() {
          var indicator = document.createElement('div');
          indicator.classList.add('demo-playground__message', 'demo-playground__message--typing');
          indicator.setAttribute('data-typing-indicator', '');
          indicator.setAttribute('aria-label', Drupal.t('El asistente está escribiendo'));
          indicator.innerHTML = '<span class="demo-playground__typing-dot"></span>'
            + '<span class="demo-playground__typing-dot"></span>'
            + '<span class="demo-playground__typing-dot"></span>';
          chatOutput.appendChild(indicator);
          chatOutput.scrollTop = chatOutput.scrollHeight;
        }

        /**
         * S5-09: Oculta indicador de escritura.
         */
        function hideTypingIndicator() {
          var indicator = chatOutput.querySelector('[data-typing-indicator]');
          if (indicator) {
            indicator.remove();
          }
        }

        /**
         * Envía un mensaje al copilot público.
         */
        async function sendMessage(message) {
          if (!message || messageCount >= maxMessages) {
            return;
          }

          messageCount++;
          appendMessage('user', message);

          // S5-09: Mostrar typing indicator.
          showTypingIndicator();
          if (sendButton) sendButton.disabled = true;
          if (chatInput) chatInput.disabled = true;

          try {
            var csrfToken = await getCsrfToken();
            var response = await fetch(endpoint, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken,
              },
              body: JSON.stringify({ message: message }),
            });

            var data = await response.json();

            hideTypingIndicator();

            if (data.rate_limited) {
              appendMessage('system', Drupal.t('Has alcanzado el límite de consultas. Inténtalo de nuevo en un minuto.'));
              return;
            }

            if (data.response) {
              appendMessage('assistant', data.response);
            }
          }
          catch (error) {
            hideTypingIndicator();
            appendMessage('system', Drupal.t('Error de conexión. Inténtalo de nuevo.'));
          }
          finally {
            // Restaurar input.
            if (sendButton) sendButton.disabled = false;
            if (chatInput) chatInput.disabled = false;
          }

          if (messageCount >= maxMessages) {
            disableInput();
          }
        }

        /**
         * Muestra un mensaje en el chat.
         */
        function appendMessage(role, text) {
          var messageEl = document.createElement('div');
          messageEl.classList.add('demo-playground__message', 'demo-playground__message--' + role);
          messageEl.textContent = text;
          chatOutput.appendChild(messageEl);
          chatOutput.scrollTop = chatOutput.scrollHeight;
        }

        /**
         * Desactiva el input tras alcanzar el limite.
         */
        function disableInput() {
          if (chatInput) {
            chatInput.disabled = true;
            chatInput.placeholder = Drupal.t('Límite de mensajes alcanzado.');
          }
          if (sendButton) {
            sendButton.disabled = true;
          }
        }

        // Event listeners — scenario cards.
        scenarioButtons.forEach(function (button) {
          function handleScenario() {
            var prompt = button.getAttribute('data-scenario-prompt');
            if (prompt) {
              if (chatInput) {
                chatInput.value = '';
              }
              sendMessage(prompt);
            }
          }

          button.addEventListener('click', handleScenario);

          // S5-15: Keydown handler para Enter y Space (role="button" requiere ambos).
          button.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
              e.preventDefault();
              handleScenario();
            }
          });
        });

        if (sendButton && chatInput) {
          sendButton.addEventListener('click', function () {
            var message = chatInput.value.trim();
            if (message) {
              chatInput.value = '';
              sendMessage(message);
            }
          });

          chatInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
              e.preventDefault();
              sendButton.click();
            }
          });
        }
      });
    },
  };
})(Drupal, drupalSettings, once);

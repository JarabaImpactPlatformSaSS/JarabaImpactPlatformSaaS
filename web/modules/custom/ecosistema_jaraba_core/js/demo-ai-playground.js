/**
 * @file
 * AI Playground — Demo interactiva de capacidades IA.
 *
 * Gestiona la interacción con escenarios preconfigurados y el widget
 * del copilot público. Consume el endpoint definido en drupalSettings.
 *
 * S1-06: Endpoint copilot leído de drupalSettings (no hardcoded).
 * S1-03: Incluye CSRF token en todas las peticiones POST.
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.demoAiPlayground = {
    attach(context) {
      once('demo-ai-playground', '[data-demo-playground]', context).forEach(function (container) {
        const config = drupalSettings.demoPlayground || {};
        const endpoint = config.copilotEndpoint || '';
        const maxMessages = config.maxMessages || 10;
        let messageCount = 0;

        // Botones de escenario.
        const scenarioButtons = container.querySelectorAll('[data-scenario-prompt]');
        const chatOutput = container.querySelector('[data-chat-output]');
        const chatInput = container.querySelector('[data-chat-input]');
        const sendButton = container.querySelector('[data-chat-send]');

        if (!endpoint || !chatOutput) {
          return;
        }

        /**
         * Obtiene el CSRF token de Drupal (S1-03).
         */
        async function getCsrfToken() {
          const response = await fetch(Drupal.url('session/token'));
          return response.text();
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

          try {
            const csrfToken = await getCsrfToken();
            const response = await fetch(endpoint, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken,
              },
              body: JSON.stringify({ message: message }),
            });

            const data = await response.json();

            if (data.rate_limited) {
              appendMessage('system', Drupal.t('Has alcanzado el limite de consultas. Intentalo de nuevo en un minuto.'));
              return;
            }

            if (data.response) {
              appendMessage('assistant', data.response);
            }
          }
          catch (error) {
            appendMessage('system', Drupal.t('Error de conexion. Intentalo de nuevo.'));
          }

          if (messageCount >= maxMessages) {
            disableInput();
          }
        }

        /**
         * Muestra un mensaje en el chat.
         */
        function appendMessage(role, text) {
          const messageEl = document.createElement('div');
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
            chatInput.placeholder = Drupal.t('Limite de mensajes alcanzado.');
          }
          if (sendButton) {
            sendButton.disabled = true;
          }
        }

        // Event listeners.
        scenarioButtons.forEach(function (button) {
          button.addEventListener('click', function () {
            const prompt = button.getAttribute('data-scenario-prompt');
            if (prompt) {
              if (chatInput) {
                chatInput.value = '';
              }
              sendMessage(prompt);
            }
          });
        });

        if (sendButton && chatInput) {
          sendButton.addEventListener('click', function () {
            const message = chatInput.value.trim();
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

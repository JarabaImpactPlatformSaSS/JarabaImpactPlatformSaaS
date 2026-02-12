/**
 * @file
 * Conecta el FAB existente (_copilot-fab.html.twig) con la API
 * del Copilot de Empleabilidad.
 *
 * PROPOSITO:
 * Integra el widget flotante de copilot con el endpoint
 * POST /api/v1/copilot/employability/chat y genera
 * quick action chips contextuales segun la pagina.
 *
 * ESTRUCTURA:
 * - Inicializa el FAB panel con sugerencias contextuales
 * - Envia mensajes al backend via fetch API
 * - Renderiza respuestas con formato HTML
 * - Muestra chips de modo para cambiar contexto
 *
 * SPEC: 20260120b S10
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.employabilityCopilot = {
    attach: function (context) {
      var fab = context.querySelector('[data-copilot-fab]');
      if (!fab) {
        return;
      }

      var elements = once('employability-copilot', fab, context);
      if (!elements.length) {
        return;
      }

      var panel = fab.querySelector('[data-copilot-panel]');
      var messagesContainer = fab.querySelector('[data-copilot-messages]');
      var input = fab.querySelector('[data-copilot-input]');
      var sendBtn = fab.querySelector('[data-copilot-send]');
      var chipsContainer = fab.querySelector('[data-copilot-chips]');

      if (!panel || !messagesContainer || !input) {
        return;
      }

      var currentMode = null;

      // Cargar sugerencias contextuales.
      loadSuggestions();

      // Enviar mensaje al hacer click o Enter.
      if (sendBtn) {
        sendBtn.addEventListener('click', function () {
          sendMessage();
        });
      }

      if (input) {
        input.addEventListener('keydown', function (e) {
          if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
          }
        });
      }

      /**
       * Carga sugerencias contextuales desde la API.
       */
      function loadSuggestions() {
        var routeName = drupalSettings.path ? drupalSettings.path.currentRoute : '';

        fetch('/api/v1/copilot/employability/suggestions?route=' + encodeURIComponent(routeName))
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (chipsContainer && data.suggestions) {
              renderChips(data.suggestions);
            }
          })
          .catch(function () {
            // Sugerencias por defecto si falla la API.
            if (chipsContainer) {
              renderChips([
                { label: Drupal.t('Analizar mi perfil'), mode: 'profile_coach' },
                { label: Drupal.t('Buscar empleo'), mode: 'job_advisor' },
                { label: Drupal.t('Ayuda'), mode: 'faq' }
              ]);
            }
          });
      }

      /**
       * Renderiza chips de sugerencia.
       */
      function renderChips(suggestions) {
        chipsContainer.innerHTML = '';
        suggestions.forEach(function (s) {
          var chip = document.createElement('button');
          chip.type = 'button';
          chip.className = 'ej-copilot__chip';
          chip.textContent = s.label;
          chip.dataset.mode = s.mode || '';
          chip.addEventListener('click', function () {
            currentMode = s.mode || null;
            input.value = s.label;
            sendMessage();
          });
          chipsContainer.appendChild(chip);
        });
      }

      /**
       * Envia el mensaje al backend.
       */
      function sendMessage() {
        var message = input.value.trim();
        if (!message) return;

        // Mostrar mensaje del usuario.
        appendMessage('user', message);
        input.value = '';

        // Mostrar indicador de typing.
        var typingId = appendMessage('assistant', '...', 'typing');

        fetch('/api/v1/copilot/employability/chat', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({
            message: message,
            mode: currentMode
          })
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          // Eliminar typing indicator.
          removeMessage(typingId);

          if (data.success) {
            appendMessage('assistant', data.response, null, data.mode_label);
            // Resetear modo para siguiente mensaje.
            currentMode = null;
          } else {
            appendMessage('assistant', data.error || Drupal.t('Error procesando tu mensaje.'), 'error');
          }
        })
        .catch(function () {
          removeMessage(typingId);
          appendMessage('assistant', Drupal.t('Error de conexion. Intentalo de nuevo.'), 'error');
        });
      }

      /**
       * Agrega un mensaje al contenedor de chat.
       */
      function appendMessage(role, text, className, modeLabel) {
        var msgEl = document.createElement('div');
        var msgId = 'msg-' + Date.now();
        msgEl.id = msgId;
        msgEl.className = 'ej-copilot__message ej-copilot__message--' + role;
        if (className) {
          msgEl.classList.add('ej-copilot__message--' + className);
        }

        var content = '';
        if (modeLabel) {
          content += '<span class="ej-copilot__mode-badge">' + Drupal.checkPlain(modeLabel) + '</span>';
        }
        content += '<div class="ej-copilot__message-text">' + formatResponse(text) + '</div>';

        msgEl.innerHTML = content;
        messagesContainer.appendChild(msgEl);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;

        return msgId;
      }

      /**
       * Elimina un mensaje por ID.
       */
      function removeMessage(msgId) {
        var el = document.getElementById(msgId);
        if (el) el.remove();
      }

      /**
       * Formatea la respuesta del copilot (markdown basico).
       */
      function formatResponse(text) {
        if (!text) return '';
        // Sanitizar HTML.
        text = Drupal.checkPlain(text);
        // Markdown basico: bold, italic, line breaks.
        text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        text = text.replace(/\*(.*?)\*/g, '<em>$1</em>');
        text = text.replace(/\n/g, '<br>');
        return text;
      }
    }
  };

})(Drupal, drupalSettings, once);

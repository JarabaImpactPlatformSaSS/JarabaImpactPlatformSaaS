/**
 * @file
 * Copilot Chat Widget - Drupal behavior con Alpine.js para reactividad.
 *
 * Implementa:
 * - Conexion SSE a /api/v1/copilot/chat/stream
 * - Indicador visual de modo (icono + color + label)
 * - Animacion de "Copiloto pensando..."
 * - Botones de feedback (util/no util) post-respuesta
 * - CTAs contextuales desde la respuesta JSON
 * - Historial de sesion (sessionStorage)
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Colores por modo del copiloto.
   */
  var MODE_COLORS = {
    coach: '#8b5cf6',
    consultor: '#3b82f6',
    sparring: '#f97316',
    cfo: '#10b981',
    fiscal: '#6366f1',
    laboral: '#06b6d4',
    devil: '#ef4444',
    vpc_designer: '#ec4899',
    customer_discovery: '#14b8a6',
    pattern_expert: '#a855f7',
    pivot_advisor: '#f59e0b',
    landing_copilot: '#FF8C42'
  };

  /**
   * Labels por modo del copiloto.
   */
  var MODE_LABELS = {
    coach: Drupal.t('Coach Emocional'),
    consultor: Drupal.t('Consultor Tactico'),
    sparring: Drupal.t('Sparring Partner'),
    cfo: Drupal.t('CFO Sintetico'),
    fiscal: Drupal.t('Experto Tributario'),
    laboral: Drupal.t('Seguridad Social'),
    devil: Drupal.t('Abogado del Diablo'),
    vpc_designer: Drupal.t('VPC Designer'),
    customer_discovery: Drupal.t('Customer Discovery'),
    pattern_expert: Drupal.t('Pattern Expert'),
    pivot_advisor: Drupal.t('Pivot Advisor'),
    landing_copilot: Drupal.t('Asesor Jaraba')
  };

  /**
   * Session storage key.
   */
  var SESSION_KEY = 'copilot_chat_history';

  Drupal.behaviors.copilotChatWidget = {
    attach: function (context) {
      once('copilot-chat', '.copilot-chat', context).forEach(function (el) {
        initChatWidget(el);
      });
    }
  };

  /**
   * Inicializa el widget de chat.
   */
  function initChatWidget(el) {
    var state = {
      messages: loadHistory(),
      input: '',
      mode: null,
      modeLabel: '',
      modeColor: '',
      streaming: false,
      thinking: false,
      isOpen: el.classList.contains('copilot-chat--inline'),
      currentResponse: ''
    };

    var messagesContainer = el.querySelector('.copilot-chat__messages');
    var inputField = el.querySelector('.copilot-chat__input-field');
    var sendBtn = el.querySelector('.copilot-chat__send-btn');
    var toggleBtn = el.querySelector('.copilot-chat__toggle');
    var body = el.querySelector('.copilot-chat__body');
    var modeIndicator = el.querySelector('.copilot-chat__mode-indicator');

    // Renderizar historial existente.
    renderMessages();

    // Toggle FAB.
    if (toggleBtn) {
      toggleBtn.addEventListener('click', function () {
        state.isOpen = !state.isOpen;
        el.classList.toggle('copilot-chat--open', state.isOpen);
      });
    }

    // Enviar mensaje.
    if (sendBtn) {
      sendBtn.addEventListener('click', function () {
        sendMessage();
      });
    }

    if (inputField) {
      inputField.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
          e.preventDefault();
          sendMessage();
        }
      });
    }

    /**
     * Envia un mensaje al endpoint SSE.
     */
    function sendMessage() {
      var text = inputField ? inputField.value.trim() : '';
      if (!text || state.streaming) {
        return;
      }

      // Agregar mensaje del usuario.
      state.messages.push({
        role: 'user',
        text: text,
        timestamp: Date.now()
      });
      inputField.value = '';
      state.input = '';
      state.streaming = true;
      state.thinking = true;
      state.currentResponse = '';

      renderMessages();
      scrollToBottom();

      // Llamar al endpoint SSE.
      fetch(Drupal.url('api/v1/copilot/chat/stream'), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
          message: text,
          context: drupalSettings.jarabaCopilot || {}
        })
      }).then(function (response) {
        if (!response.ok) {
          throw new Error('HTTP ' + response.status);
        }

        var reader = response.body.getReader();
        var decoder = new TextDecoder();
        var buffer = '';

        function readChunk() {
          reader.read().then(function (result) {
            if (result.done) {
              finishStreaming();
              return;
            }

            buffer += decoder.decode(result.value, { stream: true });
            var lines = buffer.split('\n');
            buffer = lines.pop() || '';

            var currentEvent = '';
            for (var i = 0; i < lines.length; i++) {
              var line = lines[i];
              if (line.startsWith('event: ')) {
                currentEvent = line.substring(7);
              }
              else if (line.startsWith('data: ')) {
                try {
                  var data = JSON.parse(line.substring(6));
                  handleSSEEvent(currentEvent, data);
                }
                catch (e) {
                  // Skip invalid JSON.
                }
              }
            }

            readChunk();
          }).catch(function () {
            finishStreaming();
          });
        }

        readChunk();
      }).catch(function (err) {
        state.thinking = false;
        state.streaming = false;
        state.messages.push({
          role: 'assistant',
          text: Drupal.t('Lo siento, no pude procesar tu consulta. Intentalo de nuevo.'),
          mode: 'error',
          timestamp: Date.now()
        });
        renderMessages();
        saveHistory();
      });
    }

    /**
     * Maneja eventos SSE.
     */
    function handleSSEEvent(event, data) {
      switch (event) {
        case 'mode':
          state.mode = data.mode;
          state.modeLabel = MODE_LABELS[data.mode] || data.mode;
          state.modeColor = MODE_COLORS[data.mode] || '#64748b';
          updateModeIndicator();
          break;

        case 'thinking':
          state.thinking = data.status;
          renderThinkingIndicator();
          break;

        case 'chunk':
          state.thinking = false;
          state.currentResponse += (state.currentResponse ? ' ' : '') + data.text;
          renderStreamingResponse();
          scrollToBottom();
          break;

        case 'done':
          finishStreaming(data);
          break;

        case 'error':
          state.thinking = false;
          state.streaming = false;
          state.messages.push({
            role: 'assistant',
            text: data.message || Drupal.t('Error inesperado.'),
            mode: 'error',
            timestamp: Date.now()
          });
          renderMessages();
          saveHistory();
          break;
      }
    }

    /**
     * Finaliza el streaming.
     */
    function finishStreaming(data) {
      state.thinking = false;
      state.streaming = false;

      if (state.currentResponse) {
        state.messages.push({
          role: 'assistant',
          text: state.currentResponse,
          mode: state.mode || 'consultor',
          suggestions: data ? data.suggestions || [] : [],
          provider: data ? data.provider || '' : '',
          timestamp: Date.now()
        });
        state.currentResponse = '';
      }

      renderMessages();
      saveHistory();
      scrollToBottom();
    }

    /**
     * Renderiza todos los mensajes.
     */
    function renderMessages() {
      if (!messagesContainer) {
        return;
      }

      var html = '';
      state.messages.forEach(function (msg, idx) {
        if (msg.role === 'user') {
          html += '<div class="copilot-chat__message copilot-chat__message--user">';
          html += '<div class="copilot-chat__message-content">' + escapeHtml(msg.text) + '</div>';
          html += '</div>';
        }
        else {
          var modeColor = MODE_COLORS[msg.mode] || '#64748b';
          var modeLabel = MODE_LABELS[msg.mode] || msg.mode || '';
          html += '<div class="copilot-chat__message copilot-chat__message--assistant">';
          if (modeLabel) {
            html += '<div class="copilot-chat__message-mode" style="color: ' + modeColor + ';">';
            html += '<span class="copilot-chat__mode-dot" style="background: ' + modeColor + ';"></span> ';
            html += escapeHtml(modeLabel);
            html += '</div>';
          }
          html += '<div class="copilot-chat__message-content">' + escapeHtml(msg.text) + '</div>';

          // CTAs/Sugerencias — soporta strings y objetos {label, url}.
          if (msg.suggestions && msg.suggestions.length > 0) {
            html += '<div class="copilot-chat__suggestions">';
            msg.suggestions.forEach(function (s) {
              var item = typeof s === 'string' ? { label: s } : s;
              if (item.url) {
                // Enlace directo — botón-link que navega a la URL.
                var isExternal = item.url.indexOf('http') === 0 && item.url.indexOf(window.location.hostname) === -1;
                html += '<a class="copilot-chat__suggestion-btn copilot-chat__suggestion-btn--link" href="' + escapeHtml(item.url) + '"';
                if (isExternal) { html += ' target="_blank" rel="noopener noreferrer"'; }
                html += '>' + escapeHtml(item.label);
                html += ' <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>';
                html += '</a>';
              } else {
                html += '<button class="copilot-chat__suggestion-btn" data-suggestion="' + escapeHtml(item.label) + '">' + escapeHtml(item.label) + '</button>';
              }
            });
            html += '</div>';
          }

          // Feedback buttons.
          html += '<div class="copilot-chat__feedback" data-msg-idx="' + idx + '">';
          html += '<button class="copilot-chat__feedback-btn copilot-chat__feedback-btn--up" data-feedback="1" title="' + Drupal.t('Util') + '">';
          html += '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/></svg>';
          html += '</button>';
          html += '<button class="copilot-chat__feedback-btn copilot-chat__feedback-btn--down" data-feedback="0" title="' + Drupal.t('No util') + '">';
          html += '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 15v4a3 3 0 0 0 3 3l4-9V2H5.72a2 2 0 0 0-2 1.7l-1.38 9a2 2 0 0 0 2 2.3zm7-13h2.67A2.31 2.31 0 0 1 22 4v7a2.31 2.31 0 0 1-2.33 2H17"/></svg>';
          html += '</button>';
          html += '</div>';

          html += '</div>';
        }
      });

      // Indicador de pensando.
      if (state.thinking) {
        html += '<div class="copilot-chat__message copilot-chat__message--thinking">';
        html += '<div class="copilot-chat__thinking-dots">';
        html += '<span></span><span></span><span></span>';
        html += '</div>';
        html += '<span class="copilot-chat__thinking-text">' + Drupal.t('Copiloto pensando...') + '</span>';
        html += '</div>';
      }

      // Respuesta en streaming.
      if (state.currentResponse && !state.thinking) {
        var currentModeColor = MODE_COLORS[state.mode] || '#64748b';
        var currentModeLabel = MODE_LABELS[state.mode] || '';
        html += '<div class="copilot-chat__message copilot-chat__message--assistant copilot-chat__message--streaming">';
        if (currentModeLabel) {
          html += '<div class="copilot-chat__message-mode" style="color: ' + currentModeColor + ';">';
          html += '<span class="copilot-chat__mode-dot" style="background: ' + currentModeColor + ';"></span> ';
          html += escapeHtml(currentModeLabel);
          html += '</div>';
        }
        html += '<div class="copilot-chat__message-content">' + escapeHtml(state.currentResponse);
        html += '<span class="copilot-chat__cursor"></span>';
        html += '</div>';
        html += '</div>';
      }

      messagesContainer.innerHTML = html;

      // Bind suggestion buttons.
      messagesContainer.querySelectorAll('.copilot-chat__suggestion-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
          if (inputField) {
            inputField.value = btn.getAttribute('data-suggestion');
            sendMessage();
          }
        });
      });

      // FIX-023: Bind feedback buttons with message index for context.
      messagesContainer.querySelectorAll('.copilot-chat__feedback-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var feedbackValue = parseInt(btn.getAttribute('data-feedback'), 10);
          var feedbackContainer = btn.closest('.copilot-chat__feedback');
          var msgIdx = feedbackContainer ? parseInt(feedbackContainer.getAttribute('data-msg-idx'), 10) : -1;
          sendFeedback(feedbackValue, msgIdx);
          if (feedbackContainer) {
            feedbackContainer.innerHTML = '<span class="copilot-chat__feedback-sent">' + Drupal.t('Gracias por tu feedback') + '</span>';
          }
        });
      });
    }

    /**
     * Renderiza solo el indicador de pensando.
     */
    function renderThinkingIndicator() {
      renderMessages();
      scrollToBottom();
    }

    /**
     * Renderiza la respuesta en streaming.
     */
    function renderStreamingResponse() {
      renderMessages();
    }

    /**
     * Actualiza el indicador de modo.
     */
    function updateModeIndicator() {
      if (!modeIndicator) {
        return;
      }
      modeIndicator.style.setProperty('--mode-color', state.modeColor);
      var label = modeIndicator.querySelector('.copilot-chat__mode-label');
      var dot = modeIndicator.querySelector('.copilot-chat__mode-dot');
      if (label) {
        label.textContent = state.modeLabel;
      }
      if (dot) {
        dot.style.background = state.modeColor;
      }
      modeIndicator.classList.add('copilot-chat__mode-indicator--active');
    }

    /**
     * FIX-023: Envia feedback al servidor con datos completos.
     *
     * @param {number} value 1=util, 0=no util.
     * @param {number} msgIdx Indice del mensaje en state.messages.
     */
    function sendFeedback(value, msgIdx) {
      var assistantMsg = state.messages[msgIdx] || {};
      var userMsg = '';
      // Buscar el mensaje de usuario anterior al asistente.
      for (var i = msgIdx - 1; i >= 0; i--) {
        if (state.messages[i] && state.messages[i].role === 'user') {
          userMsg = state.messages[i].text || '';
          break;
        }
      }

      fetch(Drupal.url('api/v1/copilot/feedback'), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
          rating: value ? 'up' : 'down',
          message_id: assistantMsg.timestamp ? String(assistantMsg.timestamp) : null,
          user_message: userMsg,
          assistant_response: assistantMsg.text || '',
          context: {
            mode: assistantMsg.mode || '',
            source: 'emprendimiento'
          }
        })
      }).catch(function () {
        // Silent fail.
      });
    }

    /**
     * Scroll al fondo del contenedor de mensajes.
     */
    function scrollToBottom() {
      if (messagesContainer) {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
      }
    }

    /**
     * Carga historial de sesion.
     */
    function loadHistory() {
      try {
        var data = sessionStorage.getItem(SESSION_KEY);
        return data ? JSON.parse(data) : [];
      }
      catch (e) {
        return [];
      }
    }

    /**
     * Guarda historial de sesion.
     */
    function saveHistory() {
      try {
        // Mantener solo los ultimos 50 mensajes.
        var toSave = state.messages.slice(-50);
        sessionStorage.setItem(SESSION_KEY, JSON.stringify(toSave));
      }
      catch (e) {
        // Silent fail.
      }
    }

    /**
     * Escapa HTML.
     */
    function escapeHtml(str) {
      if (!str) {
        return '';
      }
      var div = document.createElement('div');
      div.appendChild(document.createTextNode(str));
      return div.innerHTML;
    }
  }

})(Drupal, once);

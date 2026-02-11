/**
 * @file
 * FAQ Bot widget — Chat contextual público para clientes del tenant (G114-4).
 *
 * FAB flotante en /ayuda que responde preguntas usando exclusivamente
 * la KB del tenant (FAQs + Políticas) vía Qdrant.
 *
 * Patrón basado en contextual-copilot.js.
 */

(function (Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.faqBot = {
    attach: function (context) {
      var widgets = context.querySelectorAll('[data-faq-bot]:not(.faq-bot--processed)');

      /**
       * Parsea Markdown básico a HTML.
       * Soporta: enlaces [texto](url), negritas **texto**, saltos de línea.
       */
      function parseMarkdown(text) {
        if (!text) return '';
        // 1. Proteger enlaces.
        var linkPlaceholders = [];
        var html = text.replace(/\[([^\]]+)\]\(([^)]+)\)/g, function (match, linkText, url) {
          var placeholder = '__LINK_' + linkPlaceholders.length + '__';
          var safeText = escapeHtml(linkText);
          linkPlaceholders.push('<a href="' + url + '" class="faq-bot__link" target="_blank">' + safeText + '</a>');
          return placeholder;
        });
        // 2. Escapar HTML.
        html = escapeHtml(html);
        // 3. Restaurar enlaces.
        linkPlaceholders.forEach(function (link, i) {
          html = html.replace('__LINK_' + i + '__', link);
        });
        // 4. Negritas.
        html = html.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
        // 5. Saltos de línea.
        html = html.replace(/\n/g, '<br>');
        return html;
      }

      function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
      }

      widgets.forEach(function (widget) {
        widget.classList.add('faq-bot--processed');

        var fab = widget.querySelector('.faq-bot__fab');
        var panel = widget.querySelector('.faq-bot__panel');
        var closeBtn = widget.querySelector('.faq-bot__close');
        var input = widget.querySelector('[data-faq-input]');
        var sendBtn = widget.querySelector('[data-faq-send]');
        var messagesContainer = widget.querySelector('[data-faq-messages]');
        var suggestionsContainer = widget.querySelector('[data-faq-suggestions]');
        var tenantId = parseInt(widget.dataset.tenantId, 10) || 0;

        // Session ID persistido en localStorage.
        var SESSION_KEY = 'faq_bot_session_' + tenantId;
        var sessionId = localStorage.getItem(SESSION_KEY) || '';

        // Historial de conversación local.
        var conversationHistory = [];
        var MAX_HISTORY = 6;

        // Toggle panel.
        if (fab) {
          fab.addEventListener('click', function () {
            var isOpen = panel.getAttribute('aria-hidden') === 'false';
            togglePanel(!isOpen);
          });
        }

        if (closeBtn) {
          closeBtn.addEventListener('click', function () {
            togglePanel(false);
          });
        }

        function togglePanel(open) {
          panel.setAttribute('aria-hidden', !open);
          if (fab) {
            fab.setAttribute('aria-expanded', open);
          }
          widget.classList.toggle('faq-bot--open', open);
          if (open && input) {
            setTimeout(function () { input.focus(); }, 100);
          }
        }

        // Suggestion chips.
        if (suggestionsContainer) {
          suggestionsContainer.addEventListener('click', function (e) {
            var chip = e.target.closest('[data-faq-suggestion]');
            if (!chip) return;
            var text = chip.dataset.faqSuggestion;
            if (input) {
              input.value = text;
            }
            sendMessage();
          });
        }

        // Send message.
        if (sendBtn && input) {
          sendBtn.addEventListener('click', sendMessage);
          input.addEventListener('keypress', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
              e.preventDefault();
              sendMessage();
            }
          });
        }

        function sendMessage() {
          var text = input.value.trim();
          if (!text) return;

          addMessage('user', text);

          // Guardar en historial.
          conversationHistory.push({ role: 'user', content: text });
          if (conversationHistory.length > MAX_HISTORY) {
            conversationHistory.shift();
          }

          input.value = '';

          // Ocultar suggestions iniciales.
          if (suggestionsContainer) {
            suggestionsContainer.style.display = 'none';
          }

          showTypingIndicator();

          // Llamar API.
          fetch('/api/v1/help/chat', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json'
            },
            body: JSON.stringify({
              message: text,
              session_id: sessionId || undefined,
              tenant_id: tenantId
            })
          })
            .then(function (response) {
              if (response.status === 429) {
                hideTypingIndicator();
                addMessage('assistant', Drupal.t('Has alcanzado el límite de consultas. Inténtalo de nuevo en un minuto.'));
                return null;
              }
              return response.json();
            })
            .then(function (data) {
              if (!data) return;

              hideTypingIndicator();

              if (data.success && data.data) {
                var responseData = data.data;

                // Guardar session ID.
                if (responseData.session_id) {
                  sessionId = responseData.session_id;
                  localStorage.setItem(SESSION_KEY, sessionId);
                }

                // Añadir respuesta.
                addMessage('assistant', responseData.text, {
                  sources: responseData.sources || [],
                  escalate: responseData.escalate || false,
                  suggestions: responseData.suggestions || []
                });

                // Guardar en historial.
                conversationHistory.push({ role: 'assistant', content: responseData.text });
                if (conversationHistory.length > MAX_HISTORY) {
                  conversationHistory.shift();
                }
              } else {
                addMessage('assistant', data.error || Drupal.t('Lo siento, ha ocurrido un error. Inténtalo de nuevo.'));
              }
            })
            .catch(function (error) {
              hideTypingIndicator();
              console.error('FAQ Bot API error:', error);
              addMessage('assistant', Drupal.t('Lo siento, no pude conectar con el servidor. Inténtalo de nuevo.'));
            });
        }

        function addMessage(role, text, meta) {
          meta = meta || {};
          var wrapper = document.createElement('div');
          wrapper.className = 'faq-bot__message faq-bot__message--' + role;

          var bubble = document.createElement('div');
          bubble.className = 'faq-bot__bubble';
          bubble.innerHTML = parseMarkdown(text);
          wrapper.appendChild(bubble);

          // Sources bajo la respuesta del asistente.
          if (role === 'assistant' && meta.sources && meta.sources.length > 0) {
            var sourcesDiv = document.createElement('div');
            sourcesDiv.className = 'faq-bot__sources';
            sourcesDiv.innerHTML = '<span class="faq-bot__sources-label">' + Drupal.t('Fuentes:') + '</span>';
            meta.sources.forEach(function (source) {
              if (source.type === 'faq' && source.id) {
                var link = document.createElement('a');
                link.className = 'faq-bot__source-link';
                link.href = '/ayuda/' + source.id;
                link.textContent = source.question || Drupal.t('Ver artículo');
                sourcesDiv.appendChild(link);
              }
            });
            wrapper.appendChild(sourcesDiv);
          }

          // Escalation banner.
          if (role === 'assistant' && meta.escalate) {
            var banner = document.createElement('div');
            banner.className = 'faq-bot__escalation';
            banner.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>'
              + '<span>' + Drupal.t('Te recomendamos contactar con nuestro equipo de soporte para una atención personalizada.') + '</span>';
            wrapper.appendChild(banner);
          }

          // Rating thumbs (para respuestas del asistente).
          if (role === 'assistant' && !meta.escalate) {
            var rating = document.createElement('div');
            rating.className = 'faq-bot__rating';
            rating.innerHTML = '<span class="faq-bot__rating-label">' + Drupal.t('¿Te fue útil?') + '</span>'
              + '<button class="faq-bot__rating-btn faq-bot__rating-btn--up" data-rating="up" title="' + Drupal.t('Sí, útil') + '">'
              + '<svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3H14zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/></svg>'
              + '</button>'
              + '<button class="faq-bot__rating-btn faq-bot__rating-btn--down" data-rating="down" title="' + Drupal.t('No, mejorar') + '">'
              + '<svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M10 15v4a3 3 0 0 0 3 3l4-9V2H5.72a2 2 0 0 0-2 1.7l-1.38 9a2 2 0 0 0 2 2.3H10zm7-13h3a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-3"/></svg>'
              + '</button>';
            wrapper.appendChild(rating);

            // Rating handlers.
            rating.querySelectorAll('.faq-bot__rating-btn').forEach(function (btn) {
              btn.addEventListener('click', function () {
                var ratingValue = this.dataset.rating;
                rating.innerHTML = '<span class="faq-bot__rating-thanks">' + Drupal.t('¡Gracias por tu feedback!') + '</span>';

                fetch('/api/v1/help/chat/feedback', {
                  method: 'POST',
                  headers: { 'Content-Type': 'application/json' },
                  body: JSON.stringify({
                    rating: ratingValue,
                    session_id: sessionId,
                    message_text: text
                  })
                }).catch(function (err) { console.warn('FAQ Bot feedback error:', err); });
              });
            });
          }

          // Follow-up suggestions.
          if (role === 'assistant' && meta.suggestions && meta.suggestions.length > 0) {
            var sugDiv = document.createElement('div');
            sugDiv.className = 'faq-bot__follow-suggestions';
            meta.suggestions.forEach(function (s) {
              var btn = document.createElement('button');
              btn.className = 'faq-bot__suggestion-chip';
              btn.textContent = s.label;
              btn.addEventListener('click', function () {
                sugDiv.remove();
                input.value = s.label;
                sendMessage();
              });
              sugDiv.appendChild(btn);
            });
            wrapper.appendChild(sugDiv);
          }

          messagesContainer.appendChild(wrapper);

          // Auto-scroll.
          setTimeout(function () {
            wrapper.scrollIntoView({ behavior: 'smooth', block: 'end' });
          }, 50);
        }

        function showTypingIndicator() {
          var typing = document.createElement('div');
          typing.className = 'faq-bot__message faq-bot__message--assistant faq-bot__typing';
          typing.innerHTML = '<div class="faq-bot__bubble"><span class="faq-bot__dot"></span><span class="faq-bot__dot"></span><span class="faq-bot__dot"></span></div>';
          messagesContainer.appendChild(typing);
          typing.scrollIntoView({ behavior: 'smooth' });
        }

        function hideTypingIndicator() {
          var typing = messagesContainer.querySelector('.faq-bot__typing');
          if (typing) typing.remove();
        }
      });
    }
  };

})(Drupal, drupalSettings);

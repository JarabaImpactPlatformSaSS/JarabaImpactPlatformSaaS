/**
 * @file
 * Contextual Copilot FAB - Reutilizable por Avatar.
 *
 * Maneja la interacción del FAB de asistente contextual:
 * - Toggle panel abierto/cerrado
 * - Quick actions por avatar
 * - Chat con agente IA
 * - Rating de respuestas
 */

(function (Drupal) {
  'use strict';

  Drupal.behaviors.contextualCopilot = {
    attach: function (context, settings) {
      const copilots = context.querySelectorAll('.contextual-copilot:not(.processed)');

      /**
       * Parsea Markdown básico a HTML (patrón AgroConecta).
       * Soporta: enlaces [texto](url), negritas **texto**
       */
      function parseMarkdown(text) {
        if (!text) return '';
        // 1. Extraer enlaces y protegerlos
        const linkPlaceholders = [];
        let html = text.replace(/\[([^\]]+)\]\(([^)]+)\)/g, (match, linkText, url) => {
          const placeholder = `__LINK_${linkPlaceholders.length}__`;
          // Escapar texto pero mantener URL
          const safeText = escapeHtml(linkText);
          linkPlaceholders.push(`<a href="${url}" class="copilot-link" target="_blank">${safeText}</a>`);
          return placeholder;
        });
        // 2. Escapar HTML del resto
        html = escapeHtml(html);
        // 3. Restaurar enlaces
        linkPlaceholders.forEach((link, i) => {
          html = html.replace(`__LINK_${i}__`, link);
        });
        // 4. Convertir **negrita**
        html = html.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
        // 5. Saltos de línea
        html = html.replace(/\n/g, '<br>');
        return html;
      }

      function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
      }

      copilots.forEach(function (copilot) {
        copilot.classList.add('processed');

        const fab = copilot.querySelector('.copilot-fab');
        const panel = copilot.querySelector('.copilot-panel');
        const closeBtn = copilot.querySelector('.copilot-close');
        const input = copilot.querySelector('.copilot-input');
        const sendBtn = copilot.querySelector('.copilot-send');
        const messagesContainer = copilot.querySelector('.chat-messages');
        const actionsGrid = copilot.querySelector('.actions-grid');

        const agentContext = copilot.dataset.agent || 'general';
        const avatarType = copilot.dataset.avatar || 'general';

        // Toggle panel
        fab.addEventListener('click', function () {
          const isOpen = panel.getAttribute('aria-hidden') === 'false';
          togglePanel(!isOpen);
        });

        closeBtn.addEventListener('click', function () {
          togglePanel(false);
        });

        function togglePanel(open) {
          panel.setAttribute('aria-hidden', !open);
          fab.setAttribute('aria-expanded', open);
          copilot.classList.toggle('is-open', open);

          if (open && input) {
            setTimeout(() => input.focus(), 100);
          }
        }

        // Quick actions
        if (actionsGrid) {
          actionsGrid.addEventListener('click', function (e) {
            const btn = e.target.closest('.action-btn');
            if (!btn) return;

            const action = btn.dataset.action;
            handleQuickAction(action);
          });
        }

        // Send message
        if (sendBtn && input) {
          sendBtn.addEventListener('click', sendMessage);
          input.addEventListener('keypress', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
              e.preventDefault();
              sendMessage();
            }
          });
        }

        function handleQuickAction(action) {
          // Map action to message - Premium Sales Oriented
          const actionMessages = {
            // Demos por vertical - Nuevas acciones premium
            'demo_empleo': Drupal.t('Quiero ver una demo de cómo Jaraba me ayuda a encontrar empleo con IA'),
            'demo_emprendimiento': Drupal.t('Muéstrame cómo puedo validar mi idea de negocio en Jaraba'),
            'demo_commerce': Drupal.t('¿Cómo sería mi tienda digital en Jaraba?'),
            'demo_b2b': Drupal.t('Soy de una organización y quiero ver el panel de gestión'),
            'register': 'REDIRECT:/user/register',  // Acción especial: redirige
            // Acciones legacy (mantener compatibilidad)
            'search_jobs': Drupal.t('Buscar ofertas de empleo'),
            'improve_cv': Drupal.t('Quiero mejorar mi CV'),
            'recommendations': Drupal.t('Muéstrame recomendaciones'),
            'interview_prep': Drupal.t('Ayúdame a preparar entrevistas'),
            'search_candidates': Drupal.t('Buscar candidatos'),
            'post_job': Drupal.t('Publicar nueva oferta'),
            'screen_applications': Drupal.t('Filtrar candidaturas'),
            'analytics': Drupal.t('Ver analytics'),
            'analyze_canvas': Drupal.t('Analizar mi Canvas'),
            'generate_canvas': Drupal.t('Generar Canvas con IA'),
            'next_step': Drupal.t('¿Cuál es mi próximo paso?'),
            'find_mentor': Drupal.t('Buscar mentor'),
            'add_product': Drupal.t('Añadir nuevo producto'),
            'view_orders': Drupal.t('Ver mis pedidos'),
            'optimize_listing': Drupal.t('Optimizar ficha de producto'),
            'view_mentees': Drupal.t('Ver mis mentorizados'),
            'schedule_session': Drupal.t('Programar sesión'),
            'review_canvas': Drupal.t('Revisar Canvas de mentorizado'),
            'send_feedback': Drupal.t('Enviar feedback'),
            'help': Drupal.t('Necesito ayuda'),
            'explore': Drupal.t('Quiero explorar la plataforma'),
          };

          const userMessage = actionMessages[action] || action;

          // Manejar redirecciones especiales
          if (userMessage.startsWith('REDIRECT:')) {
            window.location.href = userMessage.replace('REDIRECT:', '');
            return;
          }

          // Use the same sendMessage flow but with the action message
          input.value = userMessage;
          sendMessage();
        }

        // Historial de conversación para mantener contexto
        const conversationHistory = [];
        const MAX_HISTORY = 6; // Últimos 6 mensajes (3 turnos user/assistant)

        // CSRF token cache for authenticated requests.
        let csrfTokenCache = null;

        /**
         * Obtiene el CSRF token de Drupal para peticiones autenticadas.
         * @returns {Promise<string>} Token CSRF.
         */
        function getCsrfToken() {
          if (csrfTokenCache) {
            return Promise.resolve(csrfTokenCache);
          }
          return fetch(Drupal.url('session/token'))
            .then(function (resp) { return resp.text(); })
            .then(function (token) {
              csrfTokenCache = token;
              return token;
            });
        }

        function sendMessage() {
          const text = input.value.trim();
          if (!text) return;

          addMessage('user', text);

          // Guardar mensaje del usuario en historial
          conversationHistory.push({ role: 'user', content: text });
          if (conversationHistory.length > MAX_HISTORY) {
            conversationHistory.shift();
          }

          input.value = '';

          // Hide quick actions
          const actionsContainer = copilot.querySelector('.copilot-actions');
          if (actionsContainer) {
            actionsContainer.style.display = 'none';
          }

          showTypingIndicator();

          // Determine which endpoint to use based on authentication
          const isAuthenticated = drupalSettings.user && drupalSettings.user.uid > 0;

          const payload = JSON.stringify({
            message: text,
            history: conversationHistory.slice(0, -1),
            context: {
              current_page: window.location.pathname,
              avatar: avatarType,
              agent: agentContext,
            },
          });

          // Authenticated users need CSRF token; public endpoint does not.
          const tokenPromise = isAuthenticated
            ? getCsrfToken()
            : Promise.resolve(null);

          tokenPromise.then(function (csrfToken) {
            const endpoint = isAuthenticated
              ? '/api/v1/copilot/chat?_format=json'
              : '/api/v1/public-copilot/chat?_format=json';

            const headers = {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
            };
            if (csrfToken) {
              headers['X-CSRF-Token'] = csrfToken;
            }

            return fetch(endpoint, {
              method: 'POST',
              headers: headers,
              body: payload,
            });
          })
            .then(response => {
              if (!response.ok) {
                // 403 = missing permission, 500 = server error — read body for details.
                return response.json().catch(() => ({})).then(errData => {
                  throw new Error(errData.message || errData.error || response.statusText);
                });
              }
              return response.json();
            })
            .then(data => {
              hideTypingIndicator();
              if (data.success && data.data) {
                const responseText = data.data.response;
                addMessage('assistant', responseText);

                // Guardar respuesta del asistente en historial
                conversationHistory.push({ role: 'assistant', content: responseText });
                if (conversationHistory.length > MAX_HISTORY) {
                  conversationHistory.shift();
                }

                // Handle suggestions if provided
                if (data.data.suggestions && data.data.suggestions.length > 0) {
                  showSuggestionButtons(data.data.suggestions);
                }
              } else {
                addMessage('assistant', data.error || Drupal.t('Lo siento, ha ocurrido un error. Inténtalo de nuevo.'));
              }
            })
            .catch(error => {
              hideTypingIndicator();
              console.error('Copilot API error:', error);
              addMessage('assistant', Drupal.t('Lo siento, no pude conectar con el servidor. Inténtalo de nuevo.'));
            });
        }

        function showSuggestionButtons(suggestions) {
          const suggestionsDiv = document.createElement('div');
          suggestionsDiv.className = 'chat-suggestions';
          suggestions.forEach(s => {
            // Normalizar: strings planos → {label: string}
            const item = typeof s === 'string' ? { label: s } : s;

            // Si tiene URL, renderizar como enlace directo (botón-link)
            if (item.url) {
              const link = document.createElement('a');
              link.className = 'suggestion-btn suggestion-btn--link';
              link.href = item.url;
              link.textContent = item.label;
              // Solo abrir en nueva pestaña si es URL externa
              if (item.url.startsWith('http') && !item.url.includes(window.location.hostname)) {
                link.target = '_blank';
                link.rel = 'noopener noreferrer';
              }
              suggestionsDiv.appendChild(link);
              return;
            }

            // Botón de sugerencia clásico (envía mensaje o redirige por acción)
            const btn = document.createElement('button');
            btn.className = 'suggestion-btn';
            btn.textContent = item.label;
            btn.addEventListener('click', () => {
              suggestionsDiv.remove();
              if (item.action === 'register' || item.action === 'view_plans') {
                window.location.href = item.action === 'register' ? '/user/register' : '/planes';
              } else {
                input.value = item.label;
                sendMessage();
              }
            });
            suggestionsDiv.appendChild(btn);
          });
          messagesContainer.appendChild(suggestionsDiv);
          suggestionsDiv.scrollIntoView({ behavior: 'smooth' });
        }

        function addMessage(role, text) {
          const wrapper = document.createElement('div');
          wrapper.className = `chat-message chat-message--${role}`;

          const bubble = document.createElement('div');
          bubble.className = 'message-bubble';
          // Parsear markdown para enlaces clickeables (patrón AgroConecta)
          bubble.innerHTML = parseMarkdown(text);
          wrapper.appendChild(bubble);

          // Add rating for assistant messages
          if (role === 'assistant') {
            const rating = document.createElement('div');
            rating.className = 'response-rating';
            rating.innerHTML = `
              <span class="rating-label">${Drupal.t('¿Te fue útil?')}</span>
              <button class="rating-btn rating-up" data-rating="up" title="${Drupal.t('Sí, útil')}">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3H14zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/></svg>
              </button>
              <button class="rating-btn rating-down" data-rating="down" title="${Drupal.t('No, mejorar')}">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M10 15v4a3 3 0 0 0 3 3l4-9V2H5.72a2 2 0 0 0-2 1.7l-1.38 9a2 2 0 0 0 2 2.3H10zm7-13h3a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-3"/></svg>
              </button>
            `;
            wrapper.appendChild(rating);

            // Rating handlers - send to API
            rating.querySelectorAll('.rating-btn').forEach(btn => {
              btn.addEventListener('click', function () {
                const ratingValue = this.dataset.rating;
                rating.innerHTML = `<span class="rating-thanks">${Drupal.t('¡Gracias por tu feedback!')}</span>`;

                // Send feedback to backend for AI learning
                fetch('/api/v1/copilot/feedback', {
                  method: 'POST',
                  headers: { 'Content-Type': 'application/json' },
                  body: JSON.stringify({
                    rating: ratingValue,
                    user_message: wrapper.previousElementSibling?.querySelector('.message-bubble')?.textContent || '',
                    assistant_response: text,
                    context: {
                      page: window.location.pathname,
                      avatar: avatarType,
                      agent: agentContext,
                    },
                  }),
                }).catch(err => console.warn('Feedback error:', err));
              });
            });
          }

          messagesContainer.appendChild(wrapper);

          // Auto-scroll
          setTimeout(() => {
            wrapper.scrollIntoView({ behavior: 'smooth', block: 'end' });
          }, 50);
        }

        function showTypingIndicator() {
          const typing = document.createElement('div');
          typing.className = 'chat-message chat-message--assistant typing-indicator';
          typing.innerHTML = '<div class="message-bubble"><span class="dot"></span><span class="dot"></span><span class="dot"></span></div>';
          messagesContainer.appendChild(typing);
          typing.scrollIntoView({ behavior: 'smooth' });
        }

        function hideTypingIndicator() {
          const typing = messagesContainer.querySelector('.typing-indicator');
          if (typing) typing.remove();
        }

        function getContextualResponse(action, avatar) {
          // Contextual responses based on avatar and action
          const responses = {
            'jobseeker': {
              'search_jobs': Drupal.t('He encontrado 15 ofertas que coinciden con tu perfil. Las 3 con mejor match son: Desarrollador Full Stack (92%), Project Manager (87%), y Analista de Datos (85%). ¿Quieres ver los detalles?'),
              'improve_cv': Drupal.t('Analizando tu CV... Detecté 3 áreas de mejora: 1) Añade métricas de impacto, 2) Optimiza palabras clave para ATS, 3) Incluye certificaciones recientes. ¿Empezamos?'),
              'recommendations': Drupal.t('Basado en tu experiencia y habilidades, te recomiendo explorar roles en: Gestión de Proyectos, Análisis de Negocio, y Liderazgo de Equipos Técnicos.'),
            },
            'recruiter': {
              'search_candidates': Drupal.t('Hay 28 candidatos que coinciden con tu última búsqueda. 5 están activamente buscando empleo y tienen match >80%. ¿Filtro por disponibilidad inmediata?'),
              'post_job': Drupal.t('¡Perfecto! Para publicar una oferta efectiva, necesito: título, nivel de experiencia, y 3 requisitos principales. ¿Empezamos?'),
            },
            'entrepreneur': {
              'analyze_canvas': Drupal.t('Analizando tu Business Model Canvas... Detecto oportunidades en: Segmentos de Clientes (podrías diversificar), y Canales (considera marketplace). ¿Profundizamos?'),
              'next_step': Drupal.t('Tu próximo paso recomendado: Validar tu propuesta de valor con 5 clientes potenciales antes de invertir en desarrollo. ¿Te ayudo a preparar el guion de entrevista?'),
            },
          };

          return responses[avatar]?.[action] || Drupal.t('Procesando tu solicitud. Dame un momento para analizar la mejor forma de ayudarte...');
        }
      });
    }
  };

})(Drupal);

/**
 * @file
 * JavaScript behaviors para el Copilot Chat Widget AgroConecta.
 *
 * Fase 59 — Producer Copilot Chat Widget.
 * State machine para el chat UI, API fetch, quick actions.
 */

(function (Drupal, drupalSettings, once) {
    'use strict';

    // CSRF token cache for POST/DELETE requests.
    var _csrfToken = null;
    function getCsrfToken() {
        if (_csrfToken) return Promise.resolve(_csrfToken);
        return fetch('/session/token')
            .then(function (r) { return r.text(); })
            .then(function (token) { _csrfToken = token; return token; });
    }

    // Estados del chat
    var STATE = {
        IDLE: 'idle',
        SENDING: 'sending',
        RECEIVING: 'receiving',
        ERROR: 'error'
    };

    /**
     * Behavior: Copilot Chat Widget.
     */
    Drupal.behaviors.agroCopilotChat = {
        attach: function (context) {
            once('agro-copilot-chat', '.agro-copilot-fab', context).forEach(function (fab) {
                var chat = document.getElementById('agro-copilot-chat');
                if (!chat) return;

                var messagesContainer = chat.querySelector('.agro-copilot-chat__messages');
                var input = chat.querySelector('.agro-copilot-chat__input');
                var sendBtn = chat.querySelector('.agro-copilot-chat__send');
                var closeBtn = chat.querySelector('.agro-copilot-chat__close');
                var typingIndicator = chat.querySelector('.agro-copilot-chat__typing');
                var quickActions = chat.querySelectorAll('.agro-copilot-chat__quick-btn');

                var state = STATE.IDLE;
                var conversationId = null;

                // Toggle chat
                fab.addEventListener('click', function () {
                    var isOpen = chat.classList.contains('agro-copilot-chat--open');
                    if (isOpen) {
                        closeChat(chat, fab);
                    } else {
                        openChat(chat, fab, messagesContainer);
                    }
                });

                // Close button
                if (closeBtn) {
                    closeBtn.addEventListener('click', function () {
                        closeChat(chat, fab);
                    });
                }

                // Send message
                if (sendBtn) {
                    sendBtn.addEventListener('click', function () {
                        if (state === STATE.SENDING || state === STATE.RECEIVING) return;
                        var text = input.value.trim();
                        if (!text) return;
                        sendMessage(text, messagesContainer, input, sendBtn, typingIndicator);
                    });
                }

                // Enter to send (Shift+Enter for newline)
                if (input) {
                    input.addEventListener('keydown', function (e) {
                        if (e.key === 'Enter' && !e.shiftKey) {
                            e.preventDefault();
                            sendBtn.click();
                        }
                    });

                    // Auto-resize textarea
                    input.addEventListener('input', function () {
                        input.style.height = 'auto';
                        input.style.height = Math.min(input.scrollHeight, 100) + 'px';
                    });
                }

                // Quick actions
                quickActions.forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var action = btn.dataset.action;
                        if (!action) return;

                        var actionMap = {
                            'describe': Drupal.t('Genera una descripción para mi producto'),
                            'price': Drupal.t('Sugiere un precio competitivo'),
                            'review': Drupal.t('Ayúdame a responder una reseña'),
                            'help': Drupal.t('¿Qué puedes hacer por mí?')
                        };

                        var text = actionMap[action] || action;
                        input.value = text;
                        sendBtn.click();
                    });
                });

                /**
                 * Enviar mensaje al copilot.
                 */
                function sendMessage(text, container, inputEl, sendButton, typing) {
                    state = STATE.SENDING;
                    sendButton.disabled = true;

                    // Render user message
                    appendMessage(container, text, 'user');
                    inputEl.value = '';
                    inputEl.style.height = 'auto';

                    // Show typing
                    if (typing) typing.classList.add('agro-copilot-chat__typing--visible');

                    // API call
                    getCsrfToken().then(function (token) {
                        return fetch('/api/v1/agro/copilot/chat', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-Token': token
                            },
                            body: JSON.stringify({
                                message: text,
                                conversation_id: conversationId,
                                context: getCopilotContext()
                            })
                        });
                    })
                        .then(function (response) {
                            if (!response.ok) throw new Error('API error');
                            return response.json();
                        })
                        .then(function (data) {
                            state = STATE.IDLE;
                            sendButton.disabled = false;
                            if (typing) typing.classList.remove('agro-copilot-chat__typing--visible');

                            // Track conversation
                            if (data.conversation_id) conversationId = data.conversation_id;

                            // Render bot response
                            if (data.response) {
                                appendMessage(container, data.response, 'bot');
                            }

                            // Quick follow-ups
                            if (data.suggestions && data.suggestions.length > 0) {
                                renderSuggestions(container, data.suggestions, inputEl, sendButton);
                            }
                        })
                        .catch(function (err) {
                            state = STATE.ERROR;
                            sendButton.disabled = false;
                            if (typing) typing.classList.remove('agro-copilot-chat__typing--visible');

                            appendMessage(container,
                                Drupal.t('Lo siento, ha ocurrido un error. Inténtalo de nuevo.'),
                                'system'
                            );

                            // Auto-recover after 2 seconds
                            setTimeout(function () {
                                state = STATE.IDLE;
                            }, 2000);
                        });
                }
            });
        }
    };

    /**
     * Añade un mensaje al chat.
     */
    function appendMessage(container, text, type) {
        var msg = document.createElement('div');
        msg.className = 'agro-copilot-chat__message agro-copilot-chat__message--' + type;

        if (type === 'bot') {
            msg.innerHTML = renderMarkdown(text);
        } else {
            msg.textContent = text;
        }

        container.appendChild(msg);
        container.scrollTop = container.scrollHeight;
    }

    /**
     * Renderiza sugerencias de seguimiento.
     */
    function renderSuggestions(container, suggestions, inputEl, sendButton) {
        var wrapper = document.createElement('div');
        wrapper.className = 'agro-copilot-chat__quick-actions';
        wrapper.style.border = 'none';
        wrapper.style.padding = '0';

        suggestions.forEach(function (suggestion) {
            var btn = document.createElement('button');
            btn.className = 'agro-copilot-chat__quick-btn';
            btn.textContent = suggestion;
            btn.addEventListener('click', function () {
                inputEl.value = suggestion;
                sendButton.click();
                wrapper.remove();
            });
            wrapper.appendChild(btn);
        });

        container.appendChild(wrapper);
        container.scrollTop = container.scrollHeight;
    }

    /**
     * Renderizado básico de markdown.
     */
    function renderMarkdown(text) {
        return Drupal.checkPlain(text)
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/`(.*?)`/g, '<code>$1</code>')
            .replace(/^- (.*)/gm, '<li>$1</li>')
            .replace(/(<li>.*<\/li>)/s, '<ul>$1</ul>')
            .replace(/\n{2,}/g, '</p><p>')
            .replace(/\n/g, '<br>')
            .replace(/^(.*)$/, '<p>$1</p>');
    }

    /**
     * Abre el chat.
     */
    function openChat(chat, fab, container) {
        chat.classList.add('agro-copilot-chat--open');
        fab.classList.add('agro-copilot-fab--open');
        // Force animation
        requestAnimationFrame(function () {
            chat.style.transform = 'translateY(0)';
            chat.style.opacity = '1';
        });
        // Focus input
        var input = chat.querySelector('.agro-copilot-chat__input');
        if (input) setTimeout(function () { input.focus(); }, 300);
    }

    /**
     * Cierra el chat.
     */
    function closeChat(chat, fab) {
        chat.classList.remove('agro-copilot-chat--open');
        fab.classList.remove('agro-copilot-fab--open');
    }

    /**
     * Obtiene el contexto actual del copilot.
     */
    function getCopilotContext() {
        return {
            page: window.location.pathname,
            tenant_id: drupalSettings.jaraba ? drupalSettings.jaraba.tenantId : null,
            product_id: document.querySelector('[data-product-id]')
                ? document.querySelector('[data-product-id]').dataset.productId
                : null
        };
    }

})(Drupal, drupalSettings, once);

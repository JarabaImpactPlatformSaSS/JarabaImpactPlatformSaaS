/**
 * @file
 * JavaScript behaviors para el Sales Chat Widget de consumidores.
 *
 * Sprint AC5-17/18 — Sales Agent Chat Widget.
 * Widget conversacional con product cards inline y add-to-cart.
 *
 * DIRECTRIZ: Todos los textos visibles usan Drupal.t() para i18n.
 * DIRECTRIZ: No usar emojis — solo texto o iconos SVG.
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

    /**
     * Behavior: Sales Agent Chat Widget.
     */
    Drupal.behaviors.agroSalesChat = {
        attach: function (context) {
            once('agro-sales-chat', '.agro-sales-fab', context).forEach(function (fab) {
                var chat = document.getElementById('agro-sales-chat');
                if (!chat) return;

                var messagesContainer = chat.querySelector('.agro-sales-chat__messages');
                var input = chat.querySelector('.agro-sales-chat__input');
                var sendBtn = chat.querySelector('.agro-sales-chat__send');
                var closeBtn = chat.querySelector('.agro-sales-chat__close');
                var typingIndicator = chat.querySelector('.agro-sales-chat__typing');
                var suggestionsContainer = chat.querySelector('.agro-sales-chat__suggestions');

                var sessionId = getSessionId();
                var conversationId = null;
                var isSending = false;

                // Toggle chat
                fab.addEventListener('click', function () {
                    if (chat.classList.contains('agro-sales-chat--open')) {
                        chat.classList.remove('agro-sales-chat--open');
                        fab.classList.remove('agro-sales-fab--open');
                    } else {
                        chat.classList.add('agro-sales-chat--open');
                        fab.classList.add('agro-sales-fab--open');
                        if (input) setTimeout(function () { input.focus(); }, 350);

                        // Enviar saludo inicial si no hay mensajes
                        if (messagesContainer && messagesContainer.children.length === 0) {
                            sendMessageToAgent(Drupal.t('¡Hola!'));
                        }
                    }
                });

                // Close button
                if (closeBtn) {
                    closeBtn.addEventListener('click', function () {
                        chat.classList.remove('agro-sales-chat--open');
                        fab.classList.remove('agro-sales-fab--open');
                    });
                }

                // Send message
                if (sendBtn) {
                    sendBtn.addEventListener('click', function () {
                        if (isSending) return;
                        var text = input.value.trim();
                        if (!text) return;
                        appendMessage(messagesContainer, text, 'user');
                        input.value = '';
                        input.style.height = 'auto';
                        sendMessageToAgent(text);
                    });
                }

                // Enter to send
                if (input) {
                    input.addEventListener('keydown', function (e) {
                        if (e.key === 'Enter' && !e.shiftKey) {
                            e.preventDefault();
                            sendBtn.click();
                        }
                    });

                    input.addEventListener('input', function () {
                        input.style.height = 'auto';
                        input.style.height = Math.min(input.scrollHeight, 100) + 'px';
                    });
                }

                /**
                 * Envía mensaje al Sales Agent API.
                 */
                function sendMessageToAgent(text) {
                    isSending = true;
                    if (sendBtn) sendBtn.disabled = true;
                    if (typingIndicator) typingIndicator.classList.add('agro-sales-chat__typing--visible');

                    getCsrfToken().then(function (token) {
                        return fetch('/api/v1/sales/chat', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-Token': token
                            },
                            body: JSON.stringify({
                                message: text,
                                session_id: sessionId,
                                tenant_id: drupalSettings.jaraba ? drupalSettings.jaraba.tenantId : null,
                                page: window.location.pathname,
                                product_id: getProductIdFromPage()
                            })
                        });
                    })
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            isSending = false;
                            if (sendBtn) sendBtn.disabled = false;
                            if (typingIndicator) typingIndicator.classList.remove('agro-sales-chat__typing--visible');

                            if (data.conversation_id) conversationId = data.conversation_id;

                            // Render respuesta del agente
                            if (data.response) {
                                appendMessage(messagesContainer, data.response, 'bot');
                            }

                            // Render product cards
                            if (data.products && data.products.length > 0) {
                                renderProductCards(messagesContainer, data.products);
                            }

                            // Render suggestions
                            if (data.suggestions && data.suggestions.length > 0) {
                                renderSuggestions(data.suggestions);
                            }
                        })
                        .catch(function () {
                            isSending = false;
                            if (sendBtn) sendBtn.disabled = false;
                            if (typingIndicator) typingIndicator.classList.remove('agro-sales-chat__typing--visible');
                            appendMessage(messagesContainer,
                                Drupal.t('Disculpa, ha ocurrido un error. Inténtalo de nuevo.'), 'bot');
                        });
                }

                /**
                 * Renderiza suggestion chips.
                 */
                function renderSuggestions(suggestions) {
                    if (!suggestionsContainer) return;
                    suggestionsContainer.innerHTML = '';

                    suggestions.forEach(function (text) {
                        var chip = document.createElement('button');
                        chip.className = 'agro-sales-chat__suggestion-chip';
                        chip.textContent = text;
                        chip.addEventListener('click', function () {
                            appendMessage(messagesContainer, text, 'user');
                            suggestionsContainer.innerHTML = '';
                            sendMessageToAgent(text);
                        });
                        suggestionsContainer.appendChild(chip);
                    });
                }
            });
        }
    };

    /**
     * Añade un mensaje al chat.
     */
    function appendMessage(container, text, type) {
        if (!container) return;
        var msg = document.createElement('div');
        msg.className = 'agro-sales-chat__message agro-sales-chat__message--' + type;

        if (type === 'bot') {
            // Markdown básico
            msg.innerHTML = Drupal.checkPlain(text)
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\n/g, '<br>');
        } else {
            msg.textContent = text;
        }

        container.appendChild(msg);
        container.scrollTop = container.scrollHeight;
    }

    /**
     * Renderiza product cards inline en el chat.
     */
    function renderProductCards(container, products) {
        products.forEach(function (product) {
            var card = document.createElement('div');
            card.className = 'agro-sales-chat__product-card';
            card.innerHTML =
                '<div class="agro-sales-chat__product-info">' +
                '<div class="name">' + escapeHtml(product.name) + '</div>' +
                (product.price ? '<div class="price">' + product.price + ' &euro;</div>' : '') +
                (product.origin ? '<div class="origin">' + escapeHtml(product.origin) + '</div>' : '') +
                '</div>' +
                '<button class="agro-sales-chat__product-add" data-product-id="' + product.id + '">' +
                Drupal.t('Añadir') +
                '</button>';

            // Add to cart handler
            var addBtn = card.querySelector('.agro-sales-chat__product-add');
            if (addBtn) {
                addBtn.addEventListener('click', function () {
                    addToCartFromChat(product.id, addBtn);
                });
            }

            container.appendChild(card);
        });

        container.scrollTop = container.scrollHeight;
    }

    /**
     * Añade al carrito desde el chat.
     */
    function addToCartFromChat(productId, btn) {
        btn.disabled = true;
        btn.textContent = Drupal.t('Añadiendo...');

        getCsrfToken().then(function (token) {
            return fetch('/api/v1/sales/cart/add', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': token
                },
                body: JSON.stringify({ product_id: productId, quantity: 1 })
            });
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    btn.textContent = Drupal.t('Añadido');
                    btn.classList.add('agro-sales-chat__product-add--added');
                } else {
                    btn.textContent = Drupal.t('Error');
                    btn.disabled = false;
                }
            })
            .catch(function () {
                btn.textContent = Drupal.t('Añadir');
                btn.disabled = false;
            });
    }

    /**
     * Obtiene un ID de sesión persistente.
     */
    function getSessionId() {
        var key = 'agro_sales_session';
        var id = localStorage.getItem(key);
        if (!id) {
            id = 'sales_' + Date.now() + '_' + Math.random().toString(36).substr(2, 8);
            localStorage.setItem(key, id);
        }
        return id;
    }

    /**
     * Obtiene product ID de la página actual.
     */
    function getProductIdFromPage() {
        var el = document.querySelector('[data-product-id]');
        return el ? el.dataset.productId : null;
    }

    /**
     * Escapa HTML.
     */
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }

})(Drupal, drupalSettings, once);

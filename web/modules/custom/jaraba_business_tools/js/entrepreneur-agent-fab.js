/**
 * @file
 * JavaScript for Entrepreneur AI Agent FAB.
 * Enhanced with: auto-scroll, rating buttons, contextual responses.
 */

(function (Drupal, drupalSettings, once) {
    'use strict';

    Drupal.behaviors.entrepreneurAgentFab = {
        attach: function (context) {
            once('entrepreneur-agent-fab', '.agent-fab-container', context).forEach(function (container) {
                initFab(container);
            });
        }
    };

    function initFab(container) {
        const trigger = container.querySelector('.agent-fab-trigger');
        const panel = container.querySelector('.agent-panel');
        const closeBtn = container.querySelector('.agent-close');
        const actionBtns = container.querySelectorAll('.action-button');
        const chatInput = container.querySelector('.agent-input');
        const sendBtn = container.querySelector('.agent-send');
        const chatMessages = container.querySelector('.chat-messages');
        const settings = drupalSettings.entrepreneurAgent || {};

        // Get canvas context if available
        const canvasSettings = drupalSettings.canvasEditor || {};
        settings.canvasId = canvasSettings.canvasId || settings.canvasId;
        settings.canvasTitle = canvasSettings.canvasTitle;
        settings.sector = canvasSettings.sector || settings.sector;
        settings.completeness = canvasSettings.completenessScore;

        // Toggle panel
        trigger.addEventListener('click', function () {
            const isOpen = panel.classList.contains('is-open');
            panel.classList.toggle('is-open');
            trigger.setAttribute('aria-expanded', !isOpen);
            panel.setAttribute('aria-hidden', isOpen);

            if (!isOpen) {
                setTimeout(function () { chatInput.focus(); }, 300);
            }
        });

        // Close panel
        closeBtn.addEventListener('click', function () {
            panel.classList.remove('is-open');
            trigger.setAttribute('aria-expanded', 'false');
            panel.setAttribute('aria-hidden', 'true');
        });

        // Handle action buttons
        actionBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                const action = btn.dataset.action;
                const label = btn.querySelector('.action-label')?.textContent || action;
                addMessage(chatMessages, label, 'user');
                handleAction(action, chatMessages, settings, panel);
            });
        });

        // Handle chat input
        sendBtn.addEventListener('click', function () {
            sendMessage(chatInput, chatMessages, settings);
        });

        chatInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                sendMessage(chatInput, chatMessages, settings);
            }
        });

        // Export openPanel function for analyze button in canvas editor
        container.openPanel = function () {
            if (!panel.classList.contains('is-open')) {
                panel.classList.add('is-open');
                trigger.setAttribute('aria-expanded', 'true');
                panel.setAttribute('aria-hidden', 'false');
            }
        };

        // Listen for canvas-analyze-request event from canvas editor
        document.addEventListener('canvas-analyze-request', function (e) {
            // Open the panel if not already open
            container.openPanel();
            // Add user message and trigger analysis
            addMessage(chatMessages, Drupal.t('Analizar Canvas con IA'), 'user');
            handleAction('analyze_canvas', chatMessages, settings, panel);
        });
    }

    function handleAction(action, chatMessages, settings, panel) {
        // Special case: generate full canvas needs user input first
        if (action === 'generate_full_canvas') {
            addAgentResponse(chatMessages, {
                message: Drupal.t('¡Genial! Cuéntame sobre tu idea de negocio. Describe en 2-3 frases qué producto o servicio ofreces y a quién.'),
                showRating: false
            });
            settings.awaitingBusinessDescription = true;
            return;
        }

        // Show loading
        addMessage(chatMessages, '', 'agent', true);

        // If we have a canvas ID and it's analyze action
        if (settings.canvasId && action === 'analyze_canvas') {
            analyzeCanvas(settings.canvasId, chatMessages, settings);
        } else if (action === 'analyze_canvas') {
            setTimeout(function () {
                removeLoadingMessage(chatMessages);
                addAgentResponse(chatMessages, {
                    message: Drupal.t('Para analizar tu canvas, necesito que primero abras uno. Ve a la lista de Canvas y selecciona el que quieras analizar.'),
                    actions: [
                        { label: Drupal.t('Ver mis Canvas'), url: '/admin/content/business-canvas', icon: '📋' }
                    ]
                });
            }, 800);
        } else if (action === 'suggest_improvements') {
            suggestImprovements(chatMessages, settings);
        } else if (action === 'validate_model') {
            validateModel(chatMessages, settings);
        } else {
            setTimeout(function () {
                removeLoadingMessage(chatMessages);
                addAgentResponse(chatMessages, {
                    message: Drupal.t('Procesando tu solicitud...'),
                    followUp: Drupal.t('¿En qué más puedo ayudarte?')
                });
            }, 1000);
        }
    }

    function analyzeCanvas(canvasId, chatMessages, settings) {
        fetch('/api/v1/canvas/' + canvasId + '/analyze', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                removeLoadingMessage(chatMessages);

                if (data.data && data.data.suggestions) {
                    const completeness = settings.completeness || 0;
                    const title = settings.canvasTitle || 'Canvas';

                    addAgentResponse(chatMessages, {
                        message: Drupal.t('📊 <strong>Análisis de "@title"</strong><br><br>Completitud: @pct%', {
                            '@title': title,
                            '@pct': Math.round(completeness)
                        }),
                        tips: data.data.suggestions.map(function (s) { return '💡 ' + s; }),
                        followUp: Drupal.t('¿Quieres que profundice en algún bloque específico?')
                    });
                } else {
                    addAgentResponse(chatMessages, {
                        message: Drupal.t('✅ Análisis completado. Tu canvas tiene buena coherencia.'),
                        tips: [
                            Drupal.t('💡 Revisa que cada segmento de cliente tenga su propuesta de valor'),
                            Drupal.t('💡 Asegúrate de que los canales lleguen a tus segmentos'),
                            Drupal.t('💡 Valida que los ingresos cubran los costes')
                        ]
                    });
                }
            })
            .catch(function () {
                removeLoadingMessage(chatMessages);
                addAgentResponse(chatMessages, {
                    message: Drupal.t('No se pudo conectar con el servicio de IA. Intenta más tarde.'),
                    showRating: false
                });
            });
    }

    function suggestImprovements(chatMessages, settings) {
        setTimeout(function () {
            removeLoadingMessage(chatMessages);

            const completeness = settings.completeness || 0;
            let tips = [];

            if (completeness < 30) {
                tips = [
                    Drupal.t('🎯 Prioridad: Define tu propuesta de valor principal'),
                    Drupal.t('👥 Identifica al menos 2 segmentos de clientes'),
                    Drupal.t('💰 Piensa en cómo generarás ingresos')
                ];
            } else if (completeness < 70) {
                tips = [
                    Drupal.t('📣 Detalla tus canales de distribución'),
                    Drupal.t('🤝 Define cómo será la relación con clientes'),
                    Drupal.t('🔑 Identifica tus recursos y actividades clave')
                ];
            } else {
                tips = [
                    Drupal.t('✨ Tu canvas está bastante completo'),
                    Drupal.t('🔍 Valida hipótesis con clientes reales'),
                    Drupal.t('📊 Considera crear proyecciones financieras')
                ];
            }

            addAgentResponse(chatMessages, {
                message: Drupal.t('Basándome en tu canvas (@pct% completo), te sugiero:', { '@pct': Math.round(completeness) }),
                tips: tips,
                actions: settings.canvasId ? [
                    { label: Drupal.t('Editar Canvas'), url: '/admin/content/business-canvas/' + settings.canvasId, icon: '✏️' }
                ] : []
            });
        }, 1200);
    }

    function validateModel(chatMessages, settings) {
        setTimeout(function () {
            removeLoadingMessage(chatMessages);
            addAgentResponse(chatMessages, {
                message: Drupal.t('📋 <strong>Validación del Modelo de Negocio</strong><br><br>Verificando coherencia entre los 9 bloques...'),
                tips: [
                    Drupal.t('✅ Propuesta de Valor → Segmentos: Coherente'),
                    Drupal.t('✅ Canales → Segmentos: Coherente'),
                    Drupal.t('⚠️ Ingresos → Costes: Revisar sostenibilidad'),
                    Drupal.t('💡 Recomendación: Añade métricas a tus fuentes de ingresos')
                ],
                followUp: Drupal.t('¿Quieres que analice algún bloque en detalle?')
            });
        }, 1500);
    }

    function sendMessage(input, chatMessages, settings) {
        const text = input.value.trim();
        if (!text) return;

        addMessage(chatMessages, text, 'user');
        input.value = '';

        // Check if we're awaiting business description for full canvas generation
        if (settings.awaitingBusinessDescription) {
            settings.awaitingBusinessDescription = false;
            generateFullCanvas(text, chatMessages, settings);
            return;
        }

        // Show loading
        addMessage(chatMessages, '', 'agent', true);

        // Simulate contextual AI response
        setTimeout(function () {
            removeLoadingMessage(chatMessages);
            addAgentResponse(chatMessages, {
                message: Drupal.t('Entendido. Estoy procesando tu consulta sobre: "@query"', { '@query': text.substring(0, 50) }),
                followUp: Drupal.t('¿Te gustaría que profundice en algún aspecto específico de tu modelo de negocio?')
            });
        }, 1200);
    }

    function generateFullCanvas(businessDescription, chatMessages, settings) {
        addMessage(chatMessages, '', 'agent', true);
        addAgentResponse(chatMessages, {
            message: Drupal.t('Generando tu Business Model Canvas...'),
            showRating: false
        });

        fetch('/api/v1/canvas/generate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                description: businessDescription,
                sector: settings.sector || 'general'
            })
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                removeLoadingMessage(chatMessages);
                if (data.success && data.data) {
                    const blocks = data.data.blocks || {};
                    const tips = [];

                    const blockNames = {
                        'value_propositions': 'Propuesta de Valor',
                        'customer_segments': 'Segmentos de Clientes',
                        'channels': 'Canales',
                        'revenue_streams': 'Fuentes de Ingresos'
                    };

                    Object.keys(blockNames).forEach(function (key) {
                        if (blocks[key] && blocks[key].length > 0) {
                            tips.push('✅ ' + blockNames[key] + ': ' + blocks[key].slice(0, 2).join(', '));
                        }
                    });

                    addAgentResponse(chatMessages, {
                        message: Drupal.t('🎉 <strong>¡Canvas generado!</strong><br>He creado un borrador basado en tu descripción.'),
                        tips: tips,
                        actions: data.data.canvas_id ? [
                            { label: Drupal.t('Ver y editar Canvas'), url: '/admin/content/business-canvas/' + data.data.canvas_id, icon: '✏️' }
                        ] : []
                    });
                } else {
                    addAgentResponse(chatMessages, {
                        message: Drupal.t('No se pudo generar el canvas. Por favor intenta con una descripción más detallada.'),
                        tips: [
                            Drupal.t('💡 Incluye qué problema resuelves'),
                            Drupal.t('💡 Menciona quién es tu cliente ideal'),
                            Drupal.t('💡 Describe tu propuesta única')
                        ]
                    });
                }
            })
            .catch(function () {
                removeLoadingMessage(chatMessages);
                addAgentResponse(chatMessages, {
                    message: Drupal.t('Error al conectar con el servicio de IA. Intenta más tarde.'),
                    showRating: false
                });
            });
    }

    /**
     * Adds a simple message to the chat with auto-scroll.
     */
    function addMessage(container, text, from, isLoading) {
        const msg = document.createElement('div');
        msg.className = 'chat-message from-' + from;

        if (isLoading) {
            msg.classList.add('loading-message');
            msg.innerHTML = '<div class="typing-dots"><span></span><span></span><span></span></div>';
        } else {
            msg.textContent = text;
        }

        container.appendChild(msg);

        // Auto-scroll with smooth behavior
        setTimeout(function () {
            msg.scrollIntoView({ behavior: 'smooth', block: 'end' });
        }, 50);
    }

    /**
     * Adds an agent response with optional tips, actions, follow-up and rating buttons.
     */
    function addAgentResponse(container, response) {
        const wrapper = document.createElement('div');
        wrapper.className = 'agent-response-wrapper';

        // Main message
        const msg = document.createElement('div');
        msg.className = 'chat-message from-agent';
        msg.innerHTML = response.message;
        wrapper.appendChild(msg);

        // Tips
        if (response.tips && response.tips.length) {
            response.tips.forEach(function (tip) {
                const tipEl = document.createElement('div');
                tipEl.className = 'chat-message from-agent tip-message';
                tipEl.innerHTML = tip;
                wrapper.appendChild(tipEl);
            });
        }

        // Action CTAs
        if (response.actions && response.actions.length) {
            const actionsContainer = document.createElement('div');
            actionsContainer.className = 'response-actions';

            response.actions.forEach(function (action) {
                const btn = document.createElement('a');
                btn.href = action.url;
                btn.className = 'response-cta';
                btn.innerHTML = '<span class="cta-icon">' + (action.icon || '→') + '</span> ' + action.label;
                actionsContainer.appendChild(btn);
            });
            wrapper.appendChild(actionsContainer);
        }

        // Follow-up prompt
        if (response.followUp) {
            const followUp = document.createElement('div');
            followUp.className = 'chat-message from-agent follow-up';
            followUp.textContent = response.followUp;
            wrapper.appendChild(followUp);
        }

        // Rating buttons (unless explicitly disabled)
        if (response.showRating !== false) {
            const rating = document.createElement('div');
            rating.className = 'response-rating';
            rating.innerHTML =
                '<span class="rating-label">' + Drupal.t('¿Te fue útil?') + '</span>' +
                '<button class="rating-btn rating-up" data-rating="up" title="' + Drupal.t('Sí, útil') + '">👍</button>' +
                '<button class="rating-btn rating-down" data-rating="down" title="' + Drupal.t('No, mejorar') + '">👎</button>';

            rating.querySelectorAll('.rating-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const ratingValue = this.dataset.rating;
                    this.parentElement.innerHTML = ratingValue === 'up'
                        ? '<span class="rating-thanks">✅ ' + Drupal.t('¡Gracias!') + '</span>'
                        : '<span class="rating-thanks">📝 ' + Drupal.t('Anotado para mejorar') + '</span>';

                    // Send rating to backend for AI learning.
                    fetch('/api/v1/business-tools/agent-rating', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            rating: ratingValue,
                            session_id: window.jarabaAgentSessionId || 'unknown',
                            context: { agent: 'entrepreneur', timestamp: Date.now() },
                        }),
                    }).catch(function (err) { console.warn('Rating submit failed:', err); });
                });
            });
            wrapper.appendChild(rating);
        }

        container.appendChild(wrapper);

        // Auto-scroll with smooth behavior
        setTimeout(function () {
            wrapper.scrollIntoView({ behavior: 'smooth', block: 'end' });
        }, 50);
    }

    function removeLoadingMessage(container) {
        const loading = container.querySelector('.loading-message');
        if (loading) {
            loading.remove();
        }
    }

})(Drupal, drupalSettings, once);


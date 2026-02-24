/**
 * @file
 * JavaScript for Employability AI Agent FAB.
 * Enhanced with: auto-scroll, rating buttons, action CTAs, contextual responses.
 */

(function (Drupal) {
    'use strict';

    // Cache CSRF token for reuse across requests.
    var _csrfTokenPromise = null;
    function getCsrfToken() {
        if (!_csrfTokenPromise) {
            _csrfTokenPromise = fetch('/session/token')
                .then(function (response) { return response.text(); });
        }
        return _csrfTokenPromise;
    }

    Drupal.behaviors.employabilityAgentFab = {
        attach: function (context) {
            const containers = context.querySelectorAll('.agent-fab-container');

            containers.forEach(container => {
                if (container.dataset.initialized) return;
                container.dataset.initialized = 'true';

                const trigger = container.querySelector('.agent-fab-trigger');
                const panel = container.querySelector('.agent-panel');
                const closeBtn = container.querySelector('.agent-close');
                const actionButtons = container.querySelectorAll('.action-button');
                const input = container.querySelector('.agent-input');
                const sendBtn = container.querySelector('.agent-send');
                const chatMessages = container.querySelector('.chat-messages');
                const agentChat = container.querySelector('.agent-chat');
                const agentId = container.dataset.agent;

                // Get current page context
                const pageContext = getPageContext();

                // Get onboarding data from drupalSettings
                const settings = drupalSettings.employabilityAgent || {};
                const onboardingData = settings.onboarding;
                const softSuggestion = settings.softSuggestion;

                // Toggle panel
                trigger.addEventListener('click', () => {
                    const isOpen = panel.classList.contains('is-open');
                    panel.classList.toggle('is-open');
                    trigger.setAttribute('aria-expanded', !isOpen);
                    panel.setAttribute('aria-hidden', isOpen);

                    if (!isOpen) {
                        setTimeout(() => input?.focus(), 300);

                        // Show onboarding message first time in session
                        if (onboardingData && !sessionStorage.getItem('fab_welcomed')) {
                            sessionStorage.setItem('fab_welcomed', 'true');
                            showOnboardingMessage(chatMessages, agentChat, onboardingData);
                        }

                        // Show pending proactive action (Fase 9).
                        if (window._pendingProactiveAction) {
                            showProactiveMessage(chatMessages, agentChat, window._pendingProactiveAction);
                            trigger.classList.remove('has-proactive');
                            window._pendingProactiveAction = null;
                        }
                    }
                });

                // Close panel
                closeBtn?.addEventListener('click', () => {
                    panel.classList.remove('is-open');
                    trigger.setAttribute('aria-expanded', 'false');
                    panel.setAttribute('aria-hidden', 'true');
                });

                // Close on outside click
                document.addEventListener('click', (e) => {
                    if (!container.contains(e.target) && panel.classList.contains('is-open')) {
                        panel.classList.remove('is-open');
                        trigger.setAttribute('aria-expanded', 'false');
                        panel.setAttribute('aria-hidden', 'true');
                    }
                });

                // Action buttons
                actionButtons.forEach(btn => {
                    btn.addEventListener('click', () => {
                        const actionId = btn.dataset.action;
                        const actionLabel = btn.querySelector('.action-label').textContent;

                        addMessage(chatMessages, agentChat, actionLabel, 'user');
                        executeAgentAction(agentId, actionId, chatMessages, agentChat, pageContext);
                    });
                });

                // Send message
                const sendMessage = () => {
                    const message = input?.value.trim();
                    if (!message) return;

                    addMessage(chatMessages, agentChat, message, 'user');
                    input.value = '';

                    // Show loading indicator.
                    const loadingId = 'loading-' + Date.now();
                    const loadingMsg = document.createElement('div');
                    loadingMsg.className = 'chat-message from-agent loading-message';
                    loadingMsg.id = loadingId;
                    loadingMsg.innerHTML = '<span class="typing-dots"><span>.</span><span>.</span><span>.</span></span>';
                    chatMessages.appendChild(loadingMsg);
                    loadingMsg.scrollIntoView({ behavior: 'smooth', block: 'end' });

                    // Call Self-Discovery context API for proactive responses.
                    getCsrfToken().then(function (csrfToken) {
                    fetch('/api/v1/self-discovery/copilot/context', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': csrfToken,
                        },
                        body: JSON.stringify({ query: message })
                    })
                        .then(res => res.json())
                        .then(data => {
                            const loading = document.getElementById(loadingId);
                            if (loading) loading.remove();

                            if (data.success && data.response) {
                                addAgentResponse(chatMessages, agentChat, data.response);
                            } else {
                                // Fallback to generic response.
                                addAgentResponse(chatMessages, agentChat, {
                                    message: Drupal.t('Entendido. Estoy analizando tu consulta sobre: "@query"', { '@query': message }),
                                    followUp: Drupal.t('¿Te gustaría que profundice en algún aspecto?')
                                });
                            }
                        })
                        .catch(() => {
                            const loading = document.getElementById(loadingId);
                            if (loading) loading.remove();

                            // Fallback on error.
                            addAgentResponse(chatMessages, agentChat, {
                                message: Drupal.t('Lo siento, no pude procesar tu consulta. Inténtalo de nuevo.'),
                            });
                        });
                    }); // getCsrfToken
                };

                sendBtn?.addEventListener('click', sendMessage);
                input?.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        sendMessage();
                    }
                });

                // Also handle URL hash #coach on page load
                if (window.location.hash === '#coach' || window.location.hash === '#recruiter' || window.location.hash === '#tutor') {
                    setTimeout(() => {
                        panel.classList.add('is-open');
                        trigger.setAttribute('aria-expanded', 'true');
                        panel.setAttribute('aria-hidden', 'false');
                    }, 500);
                }

                // Proactive actions polling (Plan Elevación Empleabilidad v1 — Fase 9).
                checkProactiveActions(trigger, panel, chatMessages, agentChat, input);
                setInterval(() => {
                    checkProactiveActions(trigger, panel, chatMessages, agentChat, input);
                }, 300000); // Every 5 minutes
            });

            // =================================================================
            // Global listener for contextual menu Coach IA links
            // Must be OUTSIDE the containers.forEach to work regardless of scope
            // =================================================================
            document.addEventListener('click', (e) => {
                const agentLink = e.target.closest('.employability-menu [data-agent]');
                if (agentLink) {
                    e.preventDefault();
                    // Find the FAB container and open its panel
                    const fabContainer = document.querySelector('.agent-fab-container');
                    if (fabContainer) {
                        const panel = fabContainer.querySelector('.agent-panel');
                        const trigger = fabContainer.querySelector('.agent-fab-trigger');
                        const input = fabContainer.querySelector('.agent-input');

                        if (panel && !panel.classList.contains('is-open')) {
                            panel.classList.add('is-open');
                            trigger?.setAttribute('aria-expanded', 'true');
                            panel.setAttribute('aria-hidden', 'false');
                            setTimeout(() => input?.focus(), 300);
                        }
                    }
                }
            });

            /**
             * Gets current page context for contextual responses.
             */
            function getPageContext() {
                const path = window.location.pathname;
                const segments = path.split('/').filter(s => s);

                return {
                    path: path,
                    section: segments[0] || 'home',
                    isProfile: path.includes('my-profile') || path.includes('user'),
                    isJobs: path.includes('jobs') || path.includes('job'),
                    isApplications: path.includes('applications') || path.includes('candidaturas'),
                    isCourses: path.includes('courses') || path.includes('cursos'),
                    language: document.documentElement.lang || 'es'
                };
            }

            /**
             * Shows the onboarding diagnosis message (first time in session).
             * Based on the 5-phase Lucía Framework for career development.
             */
            function showOnboardingMessage(container, scrollContainer, onboarding) {
                if (!onboarding) return;

                const wrapper = document.createElement('div');
                wrapper.className = 'onboarding-message fade-in';

                // Phase indicator with progress ring
                const phaseIndicator = document.createElement('div');
                phaseIndicator.className = 'phase-indicator';
                var phaseNum = parseInt(onboarding.phase_indicator?.phase, 10) || 1;
                var phaseEmoji = Drupal.checkPlain(String(onboarding.phase_indicator?.emoji || ''));
                var phaseName = Drupal.checkPlain(onboarding.phase_indicator?.name || 'Evaluando');
                var completeness = Math.max(0, Math.min(100, parseInt(onboarding.phase_indicator?.completeness, 10) || 0));
                phaseIndicator.innerHTML =
                    '<div class="phase-badge phase-' + phaseNum + '">' +
                        '<span class="phase-emoji">' + phaseEmoji + '</span>' +
                        '<span class="phase-name">' + phaseName + '</span>' +
                    '</div>' +
                    '<div class="completeness-bar">' +
                        '<div class="completeness-fill" style="width: ' + completeness + '%"></div>' +
                    '</div>' +
                    '<span class="completeness-label">' + completeness + '% completitud</span>';
                wrapper.appendChild(phaseIndicator);

                // Main message
                const mainMsg = document.createElement('div');
                mainMsg.className = 'chat-message from-agent onboarding-main';
                mainMsg.innerHTML = '<strong>' + Drupal.checkPlain(onboarding.greeting) + '</strong><br>' + Drupal.checkPlain(onboarding.main_message);
                wrapper.appendChild(mainMsg);

                // Itinerary steps
                if (onboarding.itinerary && onboarding.itinerary.steps) {
                    const itinerary = document.createElement('div');
                    itinerary.className = 'itinerary-card';
                    var stepsHtml = onboarding.itinerary.steps.map(function (step, i) {
                        return '<li class="itinerary-step">' +
                            '<span class="step-number">' + (i + 1) + '</span>' +
                            '<span class="step-text">' + Drupal.checkPlain(step) + '</span>' +
                        '</li>';
                    }).join('');
                    itinerary.innerHTML =
                        '<div class="itinerary-header">' +
                            '<span class="itinerary-icon">\uD83D\uDDFA\uFE0F</span>' +
                            '<span class="itinerary-title">' + Drupal.checkPlain(onboarding.itinerary.name) + '</span>' +
                        '</div>' +
                        '<ul class="itinerary-steps">' + stepsHtml + '</ul>';
                    wrapper.appendChild(itinerary);
                }

                // Primary action CTA
                if (onboarding.primary_action) {
                    const cta = document.createElement('a');
                    cta.href = onboarding.primary_action.url;
                    cta.className = 'onboarding-cta primary-cta';
                    cta.innerHTML =
                        '<span class="cta-icon">' + Drupal.checkPlain(onboarding.primary_action.icon || '\u2192') + '</span>' +
                        '<span class="cta-label">' + Drupal.checkPlain(onboarding.primary_action.label) + '</span>';
                    wrapper.appendChild(cta);
                }

                // Motivation
                if (onboarding.motivation) {
                    const motivation = document.createElement('div');
                    motivation.className = 'motivation-message';
                    motivation.innerHTML = '<span class="motivation-icon">\uD83D\uDCAA</span> ' + Drupal.checkPlain(onboarding.motivation);
                    wrapper.appendChild(motivation);
                }

                // Follow-up
                if (onboarding.follow_up) {
                    const followUp = document.createElement('div');
                    followUp.className = 'chat-message from-agent follow-up';
                    followUp.textContent = onboarding.follow_up;
                    wrapper.appendChild(followUp);
                }

                container.appendChild(wrapper);

                // Auto-scroll with smooth behavior
                setTimeout(() => {
                    wrapper.scrollIntoView({ behavior: 'smooth', block: 'end' });
                }, 100);
            }

            /**
             * Adds a message to the chat with auto-scroll.
             */
            function addMessage(container, scrollContainer, text, sender) {
                const msg = document.createElement('div');
                msg.className = `chat-message from-${sender}`;
                msg.textContent = text;
                container.appendChild(msg);

                // Auto-scroll to bottom with smooth behavior
                setTimeout(() => {
                    msg.scrollIntoView({ behavior: 'smooth', block: 'end' });
                }, 50);
            }


            /**
             * Adds an agent response with optional CTAs and rating.
             */
            function addAgentResponse(container, scrollContainer, response) {
                const wrapper = document.createElement('div');
                wrapper.className = 'agent-response-wrapper';

                // Main message
                const msg = document.createElement('div');
                msg.className = 'chat-message from-agent';
                msg.innerHTML = Drupal.checkPlain(response.message)
                    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                    .replace(/\n/g, '<br>');
                wrapper.appendChild(msg);

                // Tips
                if (response.tips && response.tips.length) {
                    response.tips.forEach(tip => {
                        const tipEl = document.createElement('div');
                        tipEl.className = 'chat-message from-agent tip-message';
                        tipEl.textContent = tip;
                        wrapper.appendChild(tipEl);
                    });
                }

                // Action CTAs
                if (response.actions && response.actions.length) {
                    const actionsContainer = document.createElement('div');
                    actionsContainer.className = 'response-actions';

                    response.actions.forEach(action => {
                        const btn = document.createElement('a');
                        btn.href = action.url;
                        btn.className = 'response-cta';
                        btn.innerHTML = '<span class="cta-icon">' + Drupal.checkPlain(action.icon || '\u2192') + '</span> ' + Drupal.checkPlain(action.label);
                        btn.addEventListener('click', (e) => {
                            if (action.url.startsWith('#')) {
                                e.preventDefault();
                                // Handle internal actions
                            }
                        });
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

                // Rating buttons
                const rating = document.createElement('div');
                rating.className = 'response-rating';
                rating.innerHTML = `
          <span class="rating-label">${Drupal.t('¿Te fue útil?')}</span>
          <button class="rating-btn rating-up" data-rating="up" title="${Drupal.t('Sí, útil')}">👍</button>
          <button class="rating-btn rating-down" data-rating="down" title="${Drupal.t('No, mejorar')}">👎</button>
        `;

                rating.querySelectorAll('.rating-btn').forEach(btn => {
                    btn.addEventListener('click', function () {
                        const ratingValue = this.dataset.rating;
                        this.parentElement.innerHTML = ratingValue === 'up'
                            ? `<span class="rating-thanks">✅ ${Drupal.t('¡Gracias!')}</span>`
                            : `<span class="rating-thanks">📝 ${Drupal.t('Anotado para mejorar')}</span>`;

                        // Enviar rating al backend.
                        getCsrfToken().then(function (csrfToken) {
                            fetch('/api/v1/job-board/agent-rating', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-Token': csrfToken,
                                },
                                body: JSON.stringify({
                                    rating: ratingValue,
                                    session_id: window.jarabaAgentSessionId || 'unknown',
                                    context: { agent: currentAgent, timestamp: Date.now() },
                                }),
                            }).catch(err => console.warn('Rating submit failed:', err));
                        });
                    });
                });
                wrapper.appendChild(rating);

                container.appendChild(wrapper);

                // Auto-scroll with smooth behavior
                setTimeout(() => {
                    wrapper.scrollIntoView({ behavior: 'smooth', block: 'end' });
                }, 50);
            }

            /**
             * Executes an agent action with contextual response.
             */
            function executeAgentAction(agentId, actionId, chatContainer, scrollContainer, pageContext) {
                // Show loading
                const loadingId = 'loading-' + Date.now();
                const loadingMsg = document.createElement('div');
                loadingMsg.className = 'chat-message from-agent loading-message';
                loadingMsg.id = loadingId;
                loadingMsg.innerHTML = '<span class="typing-dots"><span>.</span><span>.</span><span>.</span></span>';
                chatContainer.appendChild(loadingMsg);
                loadingMsg.scrollIntoView({ behavior: 'smooth', block: 'end' });

                // Get contextual response
                setTimeout(() => {
                    const loading = document.getElementById(loadingId);
                    if (loading) loading.remove();

                    const response = getContextualResponse(agentId, actionId, pageContext);
                    addAgentResponse(chatContainer, scrollContainer, response);
                }, 1200);
            }

            /**
             * Get contextual response based on action and page context.
             */
            function getContextualResponse(agentId, actionId, ctx) {
                const responses = {
                    career_coach: {
                        analyze_profile: {
                            message: ctx.isProfile
                                ? Drupal.t('Analizando tu perfil actual...<br><br>📊 <strong>Completitud: 65%</strong><br>Tu perfil tiene buen potencial pero faltan elementos clave.')
                                : Drupal.t('Para analizar tu perfil, necesito que vayas a tu página de perfil.'),
                            tips: [
                                Drupal.t('💡 Añade un resumen profesional de 2-3 líneas'),
                                Drupal.t('💡 Incluye al menos 5 habilidades técnicas'),
                                Drupal.t('💡 Sube una foto profesional')
                            ],
                            actions: [
                                { label: Drupal.t('Editar mi perfil'), url: '/my-profile/edit', icon: '✏️' },
                                { label: Drupal.t('Ver ofertas recomendadas'), url: '/jobs', icon: '💼' }
                            ],
                            followUp: Drupal.t('¿Quieres que te ayude a mejorar alguna sección específica?')
                        },
                        improve_cv: {
                            message: Drupal.t('Aquí tienes mis recomendaciones para un CV impactante:'),
                            tips: [
                                Drupal.t('📝 Usa verbos de acción: "Lideré", "Implementé", "Optimicé"'),
                                Drupal.t('📊 Cuantifica logros: "Aumenté ventas un 25%"'),
                                Drupal.t('🎯 Adapta cada CV a la oferta específica'),
                                Drupal.t('📏 Máximo 2 páginas, idealmente 1')
                            ],
                            actions: [
                                { label: Drupal.t('Descargar plantilla CV'), url: '#cv-template', icon: '📄' },
                                { label: Drupal.t('Generar CV automático'), url: '/my-profile/cv', icon: '🤖' }
                            ]
                        },
                        interview_prep: {
                            message: ctx.isApplications
                                ? Drupal.t('¡Veo que tienes candidaturas activas! Te preparo para las entrevistas.')
                                : Drupal.t('Te ayudo a preparar tu próxima entrevista.'),
                            tips: [
                                Drupal.t('🔍 Investiga la empresa antes'),
                                Drupal.t('💬 Prepara 3 preguntas para el entrevistador'),
                                Drupal.t('⏰ Llega 10 minutos antes'),
                                Drupal.t('👔 Vístete acorde a la cultura')
                            ],
                            actions: [
                                { label: Drupal.t('Ver mis candidaturas'), url: '/my-applications', icon: '📬' },
                                { label: Drupal.t('Simular entrevista'), url: '#mock-interview', icon: '🎤' }
                            ]
                        },
                        suggest_courses: {
                            message: ctx.isCourses
                                ? Drupal.t('¡Estás en el catálogo! Te recomiendo estos cursos basándome en tu perfil:')
                                : Drupal.t('Según tu perfil y las tendencias del mercado, te recomiendo:'),
                            tips: [
                                Drupal.t('🎓 "Comunicación Efectiva" - 4h - Esencial'),
                                Drupal.t('💻 "Habilidades Digitales" - 6h - Demandado'),
                                Drupal.t('🚀 "Liderazgo y Gestión" - 8h - Crecimiento')
                            ],
                            actions: [
                                { label: Drupal.t('Ver catálogo completo'), url: '/courses', icon: '📚' },
                                { label: Drupal.t('Mis cursos activos'), url: '/my-courses', icon: '🎓' }
                            ]
                        },
                        motivation: {
                            message: Drupal.t('💪 <strong>¡Tú puedes!</strong><br><br>Cada paso que das te acerca más a tu objetivo. La constancia es la clave del éxito.'),
                            tips: [
                                Drupal.t('✨ Dedica 30 minutos al día a buscar ofertas'),
                                Drupal.t('🔄 Actualiza tu perfil cada semana'),
                                Drupal.t('🎉 Celebra cada pequeño avance')
                            ],
                            actions: [
                                { label: Drupal.t('Explorar ofertas'), url: '/jobs', icon: '💼' }
                            ],
                            followUp: Drupal.t('Recuerda: el rechazo es redirección. ¡Tu trabajo ideal te está buscando!')
                        }
                    },
                    recruiter_assistant: {
                        screen_candidates: {
                            message: Drupal.t('He analizado los candidatos de tus ofertas activas:'),
                            tips: [
                                Drupal.t('✅ 12 candidatos cumplen requisitos mínimos'),
                                Drupal.t('🔍 8 requieren revisión manual'),
                                Drupal.t('❌ 5 no cumplen criterios básicos')
                            ],
                            actions: [
                                { label: Drupal.t('Ver candidatos filtrados'), url: '/employer/candidates', icon: '👥' },
                                { label: Drupal.t('Ajustar criterios'), url: '#adjust-criteria', icon: '⚙️' }
                            ]
                        },
                        rank_applicants: {
                            message: Drupal.t('Ranking de candidatos por compatibilidad:'),
                            tips: [
                                Drupal.t('🥇 María García - 95% match - 5 años experiencia'),
                                Drupal.t('🥈 Carlos López - 88% match - Certificaciones'),
                                Drupal.t('🥉 Ana Martínez - 82% match - Referencias')
                            ],
                            actions: [
                                { label: Drupal.t('Ver perfiles completos'), url: '/employer/candidates', icon: '👤' },
                                { label: Drupal.t('Programar entrevistas'), url: '#schedule', icon: '📅' }
                            ]
                        },
                        optimize_jd: {
                            message: Drupal.t('Tu oferta puede mejorar significativamente:'),
                            tips: [
                                Drupal.t('💰 Añade rango salarial → +75% postulaciones'),
                                Drupal.t('🏠 Menciona teletrabajo/híbrido → +60%'),
                                Drupal.t('📊 Describe beneficios concretos')
                            ],
                            actions: [
                                { label: Drupal.t('Editar ofertas'), url: '/employer/jobs', icon: '✏️' }
                            ]
                        },
                        suggest_questions: {
                            message: Drupal.t('Preguntas recomendadas por categoría:'),
                            tips: [
                                Drupal.t('🔧 Técnica: "Describe un proyecto desafiante"'),
                                Drupal.t('🤝 Comportamental: "¿Cómo manejas conflictos?"'),
                                Drupal.t('🎯 Cultural: "¿Qué valores buscas en un trabajo?"')
                            ]
                        },
                        process_analytics: {
                            message: Drupal.t('Métricas de tu proceso de selección:'),
                            tips: [
                                Drupal.t('⏱️ Tiempo medio contratación: 23 días (-5 vs anterior)'),
                                Drupal.t('✅ Tasa aceptación ofertas: 78% (+12%)'),
                                Drupal.t('👥 Candidatos por oferta: 34 (estable)')
                            ],
                            actions: [
                                { label: Drupal.t('Ver dashboard'), url: '/employer/analytics', icon: '📊' }
                            ]
                        }
                    },
                    learning_tutor: {
                        ask_question: {
                            message: Drupal.t('¡Estoy aquí para ayudarte! Escribe tu duda sobre el curso actual y te la resuelvo con ejemplos prácticos.'),
                            actions: ctx.isCourses ? [
                                { label: Drupal.t('Ver mi progreso'), url: '/my-courses', icon: '📊' }
                            ] : []
                        },
                        explain_concept: {
                            message: Drupal.t('¿Qué concepto te gustaría que te explique?<br><br>Puedo explicártelo de forma sencilla con analogías y ejemplos del mundo real.'),
                            followUp: Drupal.t('Escribe el término o tema que no entiendes.')
                        },
                        suggest_path: {
                            message: Drupal.t('Tu ruta de aprendizaje personalizada:'),
                            tips: [
                                Drupal.t('1️⃣ Completa "Fundamentos JS" (65% → 2h restantes)'),
                                Drupal.t('2️⃣ Siguiente: "React Básico" (bloqueado)'),
                                Drupal.t('3️⃣ Proyecto: To-Do App')
                            ],
                            actions: [
                                { label: Drupal.t('Continuar curso'), url: '/my-courses', icon: '▶️' },
                                { label: Drupal.t('Ver catálogo'), url: '/courses', icon: '📚' }
                            ],
                            followUp: Drupal.t('Tiempo estimado para completar: 3 semanas a tu ritmo')
                        },
                        study_tips: {
                            message: Drupal.t('Técnicas de estudio basadas en ciencia:'),
                            tips: [
                                Drupal.t('🍅 Pomodoro: 25 min estudio + 5 descanso'),
                                Drupal.t('🔄 Repetición espaciada: revisa a 1, 3, 7 días'),
                                Drupal.t('✍️ Notas activas: reformula con tus palabras'),
                                Drupal.t('🎯 Práctica activa: crea proyectos, enseña a otros')
                            ]
                        },
                        motivation_boost: {
                            message: Drupal.t('🔥 <strong>¡5 días de racha de estudio!</strong><br><br>Eso te pone por encima del 90% de estudiantes. Tu constancia es admirable.'),
                            tips: [
                                Drupal.t('🏆 3 cursos completados'),
                                Drupal.t('⏱️ 47 horas de estudio total'),
                                Drupal.t('📈 89% tasa de aprobación')
                            ],
                            actions: [
                                { label: Drupal.t('Ver mis certificados'), url: '/my-certificates', icon: '🏆' }
                            ],
                            followUp: Drupal.t('Reto: completa 2 lecciones hoy y desbloqueas un badge')
                        }
                    }
                };

                return responses[agentId]?.[actionId] || {
                    message: Drupal.t('Acción completada. ¿En qué más puedo ayudarte?')
                };
            }

            /**
             * Checks for proactive AI actions and shows notification dot on FAB.
             * Plan Elevación Empleabilidad v1 — Fase 9.
             */
            function checkProactiveActions(trigger, panel, chatContainer, scrollContainer, input) {
                fetch('/api/v1/copilot/employability/proactive', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (!data.has_action || !data.action) {
                        trigger.classList.remove('has-proactive');
                        return;
                    }

                    var action = data.action;
                    trigger.classList.add('has-proactive');
                    window._pendingProactiveAction = action;

                    // Auto-expand if channel requires it.
                    if (action.channel === 'fab_expand' && !panel.classList.contains('is-open')) {
                        panel.classList.add('is-open');
                        trigger.setAttribute('aria-expanded', 'true');
                        panel.setAttribute('aria-hidden', 'false');
                        setTimeout(function () { if (input) input.focus(); }, 300);
                        showProactiveMessage(chatContainer, scrollContainer, action);
                        trigger.classList.remove('has-proactive');
                        window._pendingProactiveAction = null;
                    }
                })
                .catch(function () {
                    // Silent fail — proactive is non-critical.
                });
            }

            /**
             * Shows a proactive action message in the chat panel.
             * Plan Elevación Empleabilidad v1 — Fase 9.
             */
            function showProactiveMessage(container, scrollContainer, action) {
                var wrapper = document.createElement('div');
                wrapper.className = 'agent-response-wrapper proactive-message';

                var msg = document.createElement('div');
                msg.className = 'chat-message from-agent proactive';
                msg.innerHTML = '<span class="proactive-badge">' + Drupal.t('Sugerencia') + '</span><br>' + Drupal.checkPlain(action.message);
                wrapper.appendChild(msg);

                if (action.cta_label && action.cta_url) {
                    var actionsContainer = document.createElement('div');
                    actionsContainer.className = 'response-actions';

                    var cta = document.createElement('a');
                    cta.href = action.cta_url;
                    cta.className = 'response-cta proactive-cta';
                    cta.innerHTML = '<span class="cta-icon">\u2192</span> ' + Drupal.checkPlain(action.cta_label);
                    actionsContainer.appendChild(cta);

                    var dismiss = document.createElement('button');
                    dismiss.className = 'response-cta proactive-dismiss';
                    dismiss.textContent = Drupal.t('Ahora no');
                    dismiss.addEventListener('click', function () {
                        getCsrfToken().then(function (csrfToken) {
                            fetch('/api/v1/copilot/employability/proactive', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-Token': csrfToken,
                                },
                                body: JSON.stringify({ rule_id: action.rule_id, action: 'dismiss' })
                            }).catch(function () {});
                        });
                        wrapper.classList.add('fade-out');
                        setTimeout(function () { wrapper.remove(); }, 300);
                    });
                    actionsContainer.appendChild(dismiss);

                    wrapper.appendChild(actionsContainer);
                }

                container.appendChild(wrapper);
                setTimeout(function () {
                    wrapper.scrollIntoView({ behavior: 'smooth', block: 'end' });
                }, 100);
            }
        }
    };

})(Drupal);

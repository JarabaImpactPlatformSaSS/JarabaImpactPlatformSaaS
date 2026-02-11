/**
 * @file diagnostic-scoring-engine.js
 * @description Motor de scoring para Diagnóstico Express TTV
 * 
 * PROPÓSITO:
 * Implementa el algoritmo de scoring 100% client-side para el Diagnóstico Express
 * con Time-to-Value (TTV) < 60 segundos. El cálculo se realiza en navegador
 * para latencia cero y feedback instantáneo.
 * 
 * BASADO EN:
 * - docs/tecnicos/20260115b-Diagnostico_Express_TTV_Especificacion_Tecnica_Claude.md
 * 
 * @version 1.0.0
 * @author IA Asistente - Jaraba Impact Platform
 * @license MIT
 */

(function (Drupal, drupalSettings, once) {
    'use strict';

    /**
     * CONFIGURACIÓN DE PESOS POR VERTICAL
     * Cada vertical tiene sus propios pesos y preguntas
     */
    const VERTICAL_CONFIG = {
        // Vertical Empleabilidad Digital (Avatar: Lucía +45)
        empleabilidad: {
            weights: {
                linkedin: 0.40,    // 40% - Presencia profesional
                cv: 0.30,          // 30% - CV/Currículum
                search: 0.30       // 30% - Estrategia de búsqueda
            },
            maxScores: {
                linkedin: 3,
                cv: 2,
                search: 3
            },
            profiles: {
                0: { type: 'invisible', label: 'Perfil Invisible', gap: 'linkedin', color: '#DC3545' },
                1: { type: 'invisible', label: 'Perfil Invisible', gap: 'linkedin', color: '#DC3545' },
                2: { type: 'invisible', label: 'Perfil Invisible', gap: 'linkedin', color: '#DC3545' },
                3: { type: 'reactive', label: 'Buscador Reactivo', gap: 'search', color: '#FFC107' },
                4: { type: 'reactive', label: 'Buscador Reactivo', gap: 'search', color: '#FFC107' },
                5: { type: 'passive', label: 'Profesional Pasivo', gap: 'personal_brand', color: '#17A2B8' },
                6: { type: 'passive', label: 'Profesional Pasivo', gap: 'personal_brand', color: '#17A2B8' },
                7: { type: 'active', label: 'Candidato Activo', gap: 'strategy', color: '#28A745' },
                8: { type: 'optimized', label: 'Perfil Optimizado', gap: 'advanced', color: '#28A745' },
                9: { type: 'optimized', label: 'Perfil Optimizado', gap: 'advanced', color: '#28A745' },
                10: { type: 'champion', label: 'Digital Champion', gap: 'none', color: '#233D63' }
            },
            impactData: {
                linkedin: 'El 87% de los reclutadores revisa LinkedIn antes de contactar',
                cv: 'Un CV personalizado aumenta las respuestas un 50%',
                search: 'Una estrategia multicanal multiplica por 3 las oportunidades'
            }
        },

        // Vertical Emprendimiento Digital (Avatar: Javier)
        emprendimiento: {
            weights: {
                presence: 0.25,      // 25% - Presencia digital
                operations: 0.25,    // 25% - Volumen de operaciones
                automation: 0.25,    // 25% - Nivel de automatización
                ai_adoption: 0.25    // 25% - Adopción de IA
            },
            maxScores: {
                presence: 3,
                operations: 3,
                automation: 3,
                ai_adoption: 3
            },
            economicMultipliers: {
                presence: { base: 500, message: 'clientes potenciales no alcanzados' },
                operations: { base: 2000, message: 'en eficiencia operativa perdida' },
                automation: { base: 1500, message: 'en tiempo manual evitable' },
                ai_adoption: { base: 3000, message: 'en oportunidades de innovación' }
            },
            maturityLevels: {
                0: { level: 'analog', label: 'Negocio Analógico', color: '#DC3545' },
                1: { level: 'analog', label: 'Negocio Analógico', color: '#DC3545' },
                2: { level: 'basic', label: 'Digitalización Básica', color: '#FFC107' },
                3: { level: 'basic', label: 'Digitalización Básica', color: '#FFC107' },
                4: { level: 'intermediate', label: 'Digital Intermedio', color: '#17A2B8' },
                5: { level: 'intermediate', label: 'Digital Intermedio', color: '#17A2B8' },
                6: { level: 'advanced', label: 'Digital Avanzado', color: '#28A745' },
                7: { level: 'advanced', label: 'Digital Avanzado', color: '#28A745' },
                8: { level: 'leader', label: 'Líder Digital', color: '#233D63' },
                9: { level: 'leader', label: 'Líder Digital', color: '#233D63' },
                10: { level: 'innovator', label: 'Innovador Digital', color: '#233D63' }
            }
        }
    };

    /**
     * Motor principal de scoring
     */
    const DiagnosticScoringEngine = {

        /**
         * Calcula el score para Vertical Empleabilidad
         * 
         * @param {Object} answers - Respuestas del usuario
         * @param {number} answers.linkedin - Valor 0-3 para LinkedIn
         * @param {number} answers.cv - Valor 0-2 para CV
         * @param {number} answers.search - Valor 0-3 para estrategia de búsqueda
         * @returns {Object} Resultado del diagnóstico
         */
        calculateEmpleabilidadScore: function (answers) {
            const config = VERTICAL_CONFIG.empleabilidad;
            const weights = config.weights;
            const maxScores = config.maxScores;

            // Normalización ponderada
            const linkedinNorm = (answers.linkedin / maxScores.linkedin) * weights.linkedin;
            const cvNorm = (answers.cv / maxScores.cv) * weights.cv;
            const searchNorm = (answers.search / maxScores.search) * weights.search;

            const totalNorm = linkedinNorm + cvNorm + searchNorm;
            const score = Math.round(totalNorm * 10);

            // Obtener perfil según score
            const profile = config.profiles[score];

            // Determinar gap principal
            const gaps = {
                linkedin: answers.linkedin / maxScores.linkedin,
                cv: answers.cv / maxScores.cv,
                search: answers.search / maxScores.search
            };

            const primaryGap = Object.entries(gaps).reduce((a, b) =>
                a[1] < b[1] ? a : b
            )[0];

            return {
                score: score,
                profileType: profile.type,
                profileLabel: profile.label,
                profileColor: profile.color,
                primaryGap: primaryGap,
                impactData: config.impactData[primaryGap],
                suggestedAction: this._getEmpleabilidadAction(primaryGap, score),
                vertical: 'empleabilidad',
                timestamp: new Date().toISOString()
            };
        },

        /**
         * Calcula el score para Vertical Emprendimiento
         * 
         * @param {Object} answers - Respuestas del usuario
         * @param {number} answers.presence - Valor 0-3 para presencia digital
         * @param {number} answers.operations - Valor 0-3 para operaciones
         * @param {number} answers.automation - Valor 0-3 para automatización
         * @param {number} answers.ai_adoption - Valor 0-3 para adopción IA
         * @param {number} monthlyRevenue - Facturación mensual estimada
         * @returns {Object} Resultado del diagnóstico con impacto económico
         */
        calculateEmprendimientoScore: function (answers, monthlyRevenue = 5000) {
            const config = VERTICAL_CONFIG.emprendimiento;
            const weights = config.weights;
            const maxScores = config.maxScores;

            // Normalización ponderada
            const presenceNorm = (answers.presence / maxScores.presence) * weights.presence;
            const opsNorm = (answers.operations / maxScores.operations) * weights.operations;
            const autoNorm = (answers.automation / maxScores.automation) * weights.automation;
            const aiNorm = (answers.ai_adoption / maxScores.ai_adoption) * weights.ai_adoption;

            const totalNorm = presenceNorm + opsNorm + autoNorm + aiNorm;
            const score = Math.round(totalNorm * 10);

            // Obtener nivel de madurez
            const maturity = config.maturityLevels[score];

            // Calcular impacto económico (dinero que está dejando de ganar)
            const economicImpact = this._calculateEconomicImpact(answers, monthlyRevenue);

            // Determinar gap principal
            const gaps = {
                presence: answers.presence / maxScores.presence,
                operations: answers.operations / maxScores.operations,
                automation: answers.automation / maxScores.automation,
                ai_adoption: answers.ai_adoption / maxScores.ai_adoption
            };

            const primaryGap = Object.entries(gaps).reduce((a, b) =>
                a[1] < b[1] ? a : b
            )[0];

            return {
                score: score,
                maturityLevel: maturity.level,
                maturityLabel: maturity.label,
                maturityColor: maturity.color,
                primaryGap: primaryGap,
                economicImpact: economicImpact,
                suggestedAction: this._getEmprendimientoAction(primaryGap, score),
                vertical: 'emprendimiento',
                timestamp: new Date().toISOString()
            };
        },

        /**
         * Calcula el impacto económico del gap digital
         * 
         * @param {Object} answers - Respuestas del usuario
         * @param {number} monthlyRevenue - Facturación mensual
         * @returns {Object} Desglose del impacto económico
         */
        _calculateEconomicImpact: function (answers, monthlyRevenue) {
            const config = VERTICAL_CONFIG.emprendimiento;
            const multipliers = config.economicMultipliers;

            // Factor basado en ingresos (escala logarítmica para evitar números absurdos)
            const revenueFactor = Math.log10(monthlyRevenue + 1) / 4;

            // Calcular coste de oportunidad por cada área
            const breakdown = {};
            let totalAnnual = 0;

            Object.keys(multipliers).forEach(key => {
                const gap = 3 - answers[key]; // Diferencia con el máximo
                const cost = gap * multipliers[key].base * revenueFactor;
                breakdown[key] = {
                    monthlyLoss: Math.round(cost),
                    annualLoss: Math.round(cost * 12),
                    message: multipliers[key].message
                };
                totalAnnual += Math.round(cost * 12);
            });

            return {
                totalMonthly: Math.round(totalAnnual / 12),
                totalAnnual: totalAnnual,
                breakdown: breakdown,
                formattedMonthly: this._formatCurrency(totalAnnual / 12),
                formattedAnnual: this._formatCurrency(totalAnnual)
            };
        },

        /**
         * Obtiene la acción sugerida para Empleabilidad
         * @private
         */
        _getEmpleabilidadAction: function (gap, score) {
            const actions = {
                linkedin: {
                    low: 'Crea tu perfil de LinkedIn con foto profesional hoy',
                    medium: 'Optimiza tu titular de LinkedIn con palabras clave',
                    high: 'Activa el modo "Open to Work" con visibilidad selectiva'
                },
                cv: {
                    low: 'Digitaliza tu currículum en formato ATS-friendly',
                    medium: 'Añade métricas y logros cuantificables a tu CV',
                    high: 'Personaliza tu CV para cada sector objetivo'
                },
                search: {
                    low: 'Regístrate en las 3 plataformas principales de empleo',
                    medium: 'Configura alertas de empleo personalizadas',
                    high: 'Desarrolla una estrategia de networking activo'
                }
            };

            const level = score <= 3 ? 'low' : (score <= 6 ? 'medium' : 'high');
            return actions[gap] ? actions[gap][level] : 'Completa tu diagnóstico para recomendaciones';
        },

        /**
         * Obtiene la acción sugerida para Emprendimiento
         * @private
         */
        _getEmprendimientoAction: function (gap, score) {
            const actions = {
                presence: {
                    low: 'Crea tu web profesional con formulario de contacto',
                    medium: 'Optimiza tu ficha de Google My Business',
                    high: 'Implementa una estrategia de contenidos SEO'
                },
                operations: {
                    low: 'Digitaliza tu catálogo de productos/servicios',
                    medium: 'Implementa un CRM básico para gestión de clientes',
                    high: 'Integra tu sistema de gestión con pasarela de pagos'
                },
                automation: {
                    low: 'Automatiza el envío de facturas y recordatorios',
                    medium: 'Configura respuestas automáticas en email/WhatsApp',
                    high: 'Implementa workflows de nurturing con Make.com'
                },
                ai_adoption: {
                    low: 'Usa IA para generar descripciones de productos',
                    medium: 'Implementa un chatbot de atención básica',
                    high: 'Integra agentes IA para marketing automatizado'
                }
            };

            const level = score <= 3 ? 'low' : (score <= 6 ? 'medium' : 'high');
            return actions[gap] ? actions[gap][level] : 'Completa tu diagnóstico para recomendaciones';
        },

        /**
         * Formatea un número como moneda EUR
         * @private
         */
        _formatCurrency: function (amount) {
            return new Intl.NumberFormat('es-ES', {
                style: 'currency',
                currency: 'EUR',
                maximumFractionDigits: 0
            }).format(amount);
        }
    };

    /**
     * Animación de carga "falsa" para efecto psicológico
     * El cálculo real es instantáneo, pero la animación genera valor percibido
     */
    const LoadingAnimation = {
        messages: {
            empleabilidad: [
                'Analizando tu presencia profesional...',
                'Evaluando tu estrategia de búsqueda...',
                'Generando recomendaciones personalizadas...'
            ],
            emprendimiento: [
                'Analizando tu huella digital...',
                'Calculando el impacto económico...',
                'Identificando oportunidades de mejora...'
            ]
        },

        /**
         * Ejecuta la animación de carga
         * 
         * @param {string} vertical - 'empleabilidad' o 'emprendimiento'
         * @param {Function} onProgress - Callback con progreso (0-100) y mensaje
         * @param {Function} onComplete - Callback al completar
         */
        run: async function (vertical, onProgress, onComplete) {
            const messages = this.messages[vertical] || this.messages.empleabilidad;
            const totalSteps = 20;
            const stepDelay = 50; // Total: 1 segundo

            for (let i = 0; i <= totalSteps; i++) {
                const progress = (i / totalSteps) * 100;
                const messageIndex = Math.min(
                    Math.floor(progress / 33),
                    messages.length - 1
                );

                onProgress(progress, messages[messageIndex]);
                await this._delay(stepDelay);
            }

            // Pausa dramática antes de mostrar resultado
            await this._delay(500);
            onComplete();
        },

        _delay: function (ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        }
    };

    /**
     * Gestor del formulario de diagnóstico
     */
    const DiagnosticForm = {
        init: function (context) {
            const forms = once('diagnostic-form', '.diagnostic-express-form', context);
            forms.forEach(form => this._attachHandlers(form));
        },

        _attachHandlers: function (form) {
            const vertical = form.dataset.vertical || 'empleabilidad';
            const submitBtn = form.querySelector('.diagnostic-submit');

            if (submitBtn) {
                submitBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this._processForm(form, vertical);
                });
            }

            // Auto-submit cuando todas las respuestas están seleccionadas
            const inputs = form.querySelectorAll('input[type="radio"]');
            inputs.forEach(input => {
                input.addEventListener('change', () => {
                    if (this._allQuestionsAnswered(form)) {
                        this._processForm(form, vertical);
                    }
                });
            });
        },

        _allQuestionsAnswered: function (form) {
            const questions = form.querySelectorAll('[data-question]');
            let allAnswered = true;

            questions.forEach(q => {
                const name = q.dataset.question;
                const checked = form.querySelector(`input[name="${name}"]:checked`);
                if (!checked) allAnswered = false;
            });

            return allAnswered;
        },

        _collectAnswers: function (form) {
            const answers = {};
            const questions = form.querySelectorAll('[data-question]');

            questions.forEach(q => {
                const name = q.dataset.question;
                const checked = form.querySelector(`input[name="${name}"]:checked`);
                answers[name] = checked ? parseInt(checked.value, 10) : 0;
            });

            return answers;
        },

        _processForm: function (form, vertical) {
            const answers = this._collectAnswers(form);
            const resultContainer = form.closest('.diagnostic-widget').querySelector('.diagnostic-result');
            const loadingContainer = form.closest('.diagnostic-widget').querySelector('.diagnostic-loading');

            // Ocultar formulario, mostrar loading
            form.style.display = 'none';
            if (loadingContainer) loadingContainer.style.display = 'block';

            // Ejecutar animación y luego calcular
            LoadingAnimation.run(
                vertical,
                (progress, message) => {
                    // Actualizar barra de progreso
                    const progressBar = loadingContainer?.querySelector('.progress-bar');
                    const messageEl = loadingContainer?.querySelector('.loading-message');

                    if (progressBar) progressBar.style.width = progress + '%';
                    if (messageEl) messageEl.textContent = message;
                },
                () => {
                    // Calcular resultado
                    let result;
                    if (vertical === 'empleabilidad') {
                        result = DiagnosticScoringEngine.calculateEmpleabilidadScore(answers);
                    } else {
                        const revenue = parseInt(form.dataset.revenue, 10) || 5000;
                        result = DiagnosticScoringEngine.calculateEmprendimientoScore(answers, revenue);
                    }

                    // Mostrar resultado
                    if (loadingContainer) loadingContainer.style.display = 'none';
                    if (resultContainer) {
                        resultContainer.style.display = 'block';
                        this._renderResult(resultContainer, result, vertical);
                    }

                    // Emitir evento para integraciones
                    document.dispatchEvent(new CustomEvent('diagnosticCompleted', {
                        detail: result
                    }));

                    // Guardar en localStorage para recuperación
                    localStorage.setItem('jaraba_diagnostic_result', JSON.stringify(result));
                }
            );
        },

        _renderResult: function (container, result, vertical) {
            // Animar score de 0 al valor final
            this._animateScore(container, result.score);

            // Actualizar elementos del resultado
            const labelEl = container.querySelector('.result-profile-label');
            if (labelEl) {
                labelEl.textContent = result.profileLabel || result.maturityLabel;
                labelEl.style.color = result.profileColor || result.maturityColor;
            }

            const gapEl = container.querySelector('.result-primary-gap');
            if (gapEl) {
                gapEl.textContent = this._formatGapLabel(result.primaryGap);
            }

            const actionEl = container.querySelector('.result-suggested-action');
            if (actionEl) {
                actionEl.textContent = result.suggestedAction;
            }

            // Específico para emprendimiento: mostrar impacto económico
            if (vertical === 'emprendimiento' && result.economicImpact) {
                const impactEl = container.querySelector('.result-economic-impact');
                if (impactEl) {
                    impactEl.innerHTML = `
            <div class="impact-annual">
              <span class="impact-label">Estás dejando de ganar</span>
              <span class="impact-value">${result.economicImpact.formattedAnnual}</span>
              <span class="impact-period">al año</span>
            </div>
          `;
                }
            }

            // Específico para empleabilidad: dato de impacto
            if (vertical === 'empleabilidad' && result.impactData) {
                const dataEl = container.querySelector('.result-impact-data');
                if (dataEl) {
                    dataEl.textContent = result.impactData;
                }
            }
        },

        _animateScore: function (container, targetScore) {
            const scoreEl = container.querySelector('.result-score-value');
            if (!scoreEl) return;

            let current = 0;
            const increment = targetScore / 20;
            const interval = setInterval(() => {
                current += increment;
                if (current >= targetScore) {
                    current = targetScore;
                    clearInterval(interval);
                }
                scoreEl.textContent = Math.round(current);
            }, 50);
        },

        _formatGapLabel: function (gap) {
            const labels = {
                linkedin: 'Tu perfil de LinkedIn',
                cv: 'Tu currículum',
                search: 'Tu estrategia de búsqueda',
                presence: 'Tu presencia digital',
                operations: 'Tus operaciones',
                automation: 'Tu automatización',
                ai_adoption: 'Tu adopción de IA'
            };
            return labels[gap] || gap;
        }
    };

    /**
     * Drupal Behavior para inicialización
     */
    Drupal.behaviors.diagnosticScoringEngine = {
        attach: function (context, settings) {
            DiagnosticForm.init(context);
        }
    };

    // Exponer para uso externo
    window.JarabaDiagnosticEngine = {
        calculateEmpleabilidad: DiagnosticScoringEngine.calculateEmpleabilidadScore.bind(DiagnosticScoringEngine),
        calculateEmprendimiento: DiagnosticScoringEngine.calculateEmprendimientoScore.bind(DiagnosticScoringEngine),
        VERTICAL_CONFIG: VERTICAL_CONFIG
    };

})(Drupal, drupalSettings, once);

/**
 * @file
 * JavaScript para el dashboard de experimentos A/B.
 *
 * PROPÓSITO:
 * Gestiona la interactividad del dashboard de experimentos:
 * - Partículas animadas en el header
 * - Acciones start/pause/stop vía API
 * - Filtrado de experimentos
 * - Actualización de métricas en tiempo real
 *
 * ESPECIFICACIÓN: Gap 2 - Plan Elevación Clase Mundial
 * ARQUITECTURA: Drupal behaviors con once()
 *
 * @package jaraba_page_builder
 */

(function (Drupal, drupalSettings, once) {
    'use strict';

    /**
     * Behavior para partículas del header del dashboard.
     *
     * Dibuja partículas animadas con colores corporativos Jaraba.
     */
    Drupal.behaviors.experimentDashboardParticles = {
        attach: function (context) {
            once('experiment-particles', '#experiments-particles', context).forEach(function (canvas) {
                initParticles(canvas);
            });
        }
    };

    /**
     * Behavior para acciones de experimentos (start/pause/stop).
     */
    Drupal.behaviors.experimentDashboardActions = {
        attach: function (context) {
            // Botón iniciar experimento
            once('start-experiment', '.js-start-experiment', context).forEach(function (button) {
                button.addEventListener('click', function () {
                    var experimentId = this.dataset.experimentId;
                    performExperimentAction(experimentId, 'start', this);
                });
            });

            // Botón pausar experimento
            once('pause-experiment', '.js-pause-experiment', context).forEach(function (button) {
                button.addEventListener('click', function () {
                    var experimentId = this.dataset.experimentId;
                    performExperimentAction(experimentId, 'stop', this);
                });
            });
        }
    };

    /**
     * Behavior para filtros de experimentos.
     */
    Drupal.behaviors.experimentDashboardFilters = {
        attach: function (context) {
            once('filter-status', '#filter-status', context).forEach(function (select) {
                select.addEventListener('change', function () {
                    filterExperiments(this.value);
                });
            });
        }
    };

    // NOTA: El comportamiento de crear experimento se maneja via data-slide-panel
    // attributes en el template. Ver ecosistema_jaraba_theme/js/slide-panel.js

    /**
     * Abre el diálogo para declarar un ganador.
     *
     * @param {string} experimentId
     *   ID del experimento.
     * @param {HTMLElement} button
     *   Botón que disparó la acción.
     */
    function openWinnerDialog(experimentId, button) {
        var card = button.closest('.experiment-card');
        if (!card) return;

        // Obtener variantes del data attribute o de elementos dentro de la card.
        var variantsContainer = card.querySelector('.variants-list, [data-variants]');
        var variantOptions = '';

        if (variantsContainer && variantsContainer.dataset.variants) {
            // Si las variantes están en un data attribute JSON.
            try {
                var variants = JSON.parse(variantsContainer.dataset.variants);
                variants.forEach(function (v) {
                    variantOptions += '<option value="' + v.id + '">' + v.name + ' (' + v.rate + '%)</option>';
                });
            } catch (e) {
                console.error('Error parsing variants:', e);
            }
        } else {
            // Buscar elementos de variante dentro de la card.
            var variantEls = card.querySelectorAll('[data-variant-id]');
            variantEls.forEach(function (el) {
                var id = el.dataset.variantId;
                var name = el.querySelector('.variant-name')?.textContent || ('Variante ' + id);
                var rate = el.querySelector('.variant-rate')?.textContent || '';
                variantOptions += '<option value="' + id + '">' + name + ' ' + rate + '</option>';
            });
        }

        if (!variantOptions) {
            alert(Drupal.t('No hay variantes disponibles para este experimento.'));
            return;
        }

        // Crear diálogo modal.
        var overlay = document.createElement('div');
        overlay.className = 'winner-dialog-overlay';
        overlay.innerHTML = [
            '<div class="winner-dialog">',
            '  <h3>' + Drupal.t('Declarar Ganador') + '</h3>',
            '  <p>' + Drupal.t('Selecciona la variante ganadora. El experimento se completará.') + '</p>',
            '  <select id="winner-variant-select">',
            '    <option value="">' + Drupal.t('-- Seleccionar --') + '</option>',
            variantOptions,
            '  </select>',
            '  <div class="winner-dialog-actions">',
            '    <button type="button" class="btn-cancel">' + Drupal.t('Cancelar') + '</button>',
            '    <button type="button" class="btn-confirm">' + Drupal.t('Confirmar') + '</button>',
            '  </div>',
            '</div>'
        ].join('');

        document.body.appendChild(overlay);

        // Event handlers.
        overlay.querySelector('.btn-cancel').addEventListener('click', function () {
            overlay.remove();
        });
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) overlay.remove();
        });
        overlay.querySelector('.btn-confirm').addEventListener('click', function () {
            var variantId = overlay.querySelector('#winner-variant-select').value;
            if (!variantId) {
                alert(Drupal.t('Selecciona una variante.'));
                return;
            }
            overlay.remove();
            declareWinner(experimentId, variantId);
        });
    }

    /**
     * Declara un ganador para el experimento vía API.
     *
     * @param {string} experimentId
     *   ID del experimento.
     * @param {string} variantId
     *   ID de la variante ganadora.
     */
    function declareWinner(experimentId, variantId) {
        var apiUrl = drupalSettings.experimentDashboard?.apiBaseUrl || '/api/v1/experiments';
        var url = apiUrl + '/' + experimentId + '/declare-winner';

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify({ variant_id: parseInt(variantId, 10) })
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error(Drupal.t('Error al declarar ganador'));
                }
                return response.json();
            })
            .then(function (data) {
                if (data.success) {
                    alert(Drupal.t('¡Ganador declarado! El experimento ha sido completado.'));
                    window.location.reload();
                } else {
                    throw new Error(data.error || Drupal.t('Error desconocido'));
                }
            })
            .catch(function (error) {
                console.error('Error:', error);
                alert(error.message);
            });
    }

    /**
     * Inicializa las partículas animadas en el canvas.
     *
     * @param {HTMLCanvasElement} canvas
     *   El elemento canvas donde dibujar.
     */
    function initParticles(canvas) {
        var ctx = canvas.getContext('2d');
        var particles = [];
        var particleCount = 50;

        // Ajustar tamaño del canvas
        function resizeCanvas() {
            canvas.width = canvas.parentElement.offsetWidth;
            canvas.height = canvas.parentElement.offsetHeight;
        }
        resizeCanvas();
        window.addEventListener('resize', resizeCanvas);

        // Colores corporativos Jaraba (con transparencia)
        var colors = [
            'rgba(35, 61, 99, 0.4)',   // Corporate
            'rgba(0, 169, 165, 0.4)',  // Innovation
            'rgba(255, 140, 66, 0.3)' // Impulse
        ];

        // Crear partículas
        for (var i = 0; i < particleCount; i++) {
            particles.push({
                x: Math.random() * canvas.width,
                y: Math.random() * canvas.height,
                radius: Math.random() * 3 + 1,
                color: colors[Math.floor(Math.random() * colors.length)],
                speedX: (Math.random() - 0.5) * 0.5,
                speedY: (Math.random() - 0.5) * 0.5
            });
        }

        // Animar
        function animate() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            particles.forEach(function (p) {
                // Mover
                p.x += p.speedX;
                p.y += p.speedY;

                // Rebote en bordes
                if (p.x < 0 || p.x > canvas.width) p.speedX *= -1;
                if (p.y < 0 || p.y > canvas.height) p.speedY *= -1;

                // Dibujar
                ctx.beginPath();
                ctx.arc(p.x, p.y, p.radius, 0, Math.PI * 2);
                ctx.fillStyle = p.color;
                ctx.fill();
            });

            requestAnimationFrame(animate);
        }
        animate();
    }

    /**
     * Ejecuta una acción sobre un experimento vía API.
     *
     * @param {string} experimentId
     *   ID del experimento.
     * @param {string} action
     *   Acción a realizar: 'start', 'stop'.
     * @param {HTMLElement} button
     *   Botón que disparó la acción.
     */
    function performExperimentAction(experimentId, action, button) {
        var apiUrl = drupalSettings.experimentDashboard.apiBaseUrl || '/api/v1/experiments';
        var url = apiUrl + '/' + experimentId + '/' + action;

        // Deshabilitar botón
        button.disabled = true;
        var originalText = button.textContent;
        button.textContent = Drupal.t('Procesando...');

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error(Drupal.t('Error al procesar la acción'));
                }
                return response.json();
            })
            .then(function (data) {
                // Recargar la página para ver cambios
                window.location.reload();
            })
            .catch(function (error) {
                console.error('Error:', error);
                button.disabled = false;
                button.textContent = originalText;
                alert(error.message);
            });
    }

    /**
     * Filtra los experimentos por estado.
     *
     * @param {string} status
     *   Estado a filtrar (vacío = todos).
     */
    function filterExperiments(status) {
        var cards = document.querySelectorAll('.experiment-card');

        cards.forEach(function (card) {
            if (!status || card.classList.contains('experiment-card--' + status)) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    }

})(Drupal, drupalSettings, once);

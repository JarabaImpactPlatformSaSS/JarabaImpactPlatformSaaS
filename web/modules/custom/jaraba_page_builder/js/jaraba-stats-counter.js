/**
 * @file
 * Jaraba Stats Counter - Interactividad con Intersection Observer.
 *
 * Anima los números de estadísticas de 0 al valor final cuando el bloque
 * es visible en el viewport. Usa event delegation y Intersection Observer.
 *
 * @see docs/arquitectura/2026-02-08_plan_elevacion_page_builder_clase_mundial.md
 */

(function (Drupal) {
    'use strict';

    /**
     * Anima un contador numérico con ease-out cubic.
     *
     * @param {Element} counterEl - Elemento con data-target-value.
     */
    function animateCounter(counterEl) {
        var target = parseFloat(counterEl.getAttribute('data-target-value')) || 0;
        var suffix = counterEl.getAttribute('data-suffix') || '';
        var prefix = counterEl.getAttribute('data-prefix') || '';
        var duration = 2000;
        var startTime = performance.now();

        function update(currentTime) {
            var elapsed = currentTime - startTime;
            var progress = Math.min(elapsed / duration, 1);
            var eased = 1 - Math.pow(1 - progress, 3);
            var current = Math.round(target * eased);
            counterEl.textContent = prefix + current.toLocaleString() + suffix;

            if (progress < 1) {
                requestAnimationFrame(update);
            }
        }
        requestAnimationFrame(update);
    }

    /**
     * Inicializa los contadores de estadísticas en el contexto dado.
     *
     * @param {Element} context - Contexto DOM.
     */
    function initStatsCounters(context) {
        var containers = context.querySelectorAll('.jaraba-stats--counter');

        containers.forEach(function (container) {
            if (container.dataset.jarabaStatsInit) {
                return;
            }
            container.dataset.jarabaStatsInit = 'true';

            var counters = container.querySelectorAll('[data-target-value]');
            if (!counters.length) return;

            if (typeof IntersectionObserver !== 'undefined') {
                var observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            counters.forEach(animateCounter);
                            observer.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.3 });
                observer.observe(container);
            } else {
                counters.forEach(animateCounter);
            }
        });
    }

    // Drupal behavior para páginas públicas
    Drupal.behaviors.jarabaStatsCounter = {
        attach: function (context) {
            initStatsCounters(context);
        }
    };

    // Inicialización inmediata para contextos no-Drupal (iframe GrapesJS)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initStatsCounters(document);
        });
    } else {
        initStatsCounters(document);
    }

    // Exponer para GrapesJS
    window.jarabaInitStatsCounters = initStatsCounters;

})(Drupal);

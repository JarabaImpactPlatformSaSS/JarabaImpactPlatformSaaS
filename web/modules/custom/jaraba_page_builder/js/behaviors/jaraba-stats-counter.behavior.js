/**
 * @file
 * Behavior: Contador de estadísticas con IntersectionObserver.
 *
 * Anima los números de 0 al valor final cuando el bloque es visible en viewport.
 * Usa ease-out cubic para desaceleración natural.
 *
 * Selector: .jaraba-stats--counter [data-target-value]
 *
 * @see grapesjs-jaraba-blocks.js → statsCounterScript
 */

(function (Drupal, once) {
    'use strict';

    Drupal.behaviors.jarabaStatsCounter = {
        attach: function (context) {
            var containers = once('jaraba-stats-counter', '.jaraba-stats--counter', context);

            containers.forEach(function (container) {
                var counters = container.querySelectorAll('[data-target-value]');
                if (!counters.length) return;

                /**
                 * Anima un elemento contador de 0 al valor objetivo.
                 * @param {HTMLElement} counterEl - Elemento con data-target-value.
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
                        // Ease-out cubic
                        var eased = 1 - Math.pow(1 - progress, 3);
                        var current = Math.round(target * eased);
                        counterEl.textContent = prefix + current.toLocaleString() + suffix;

                        if (progress < 1) {
                            requestAnimationFrame(update);
                        }
                    }
                    requestAnimationFrame(update);
                }

                // IntersectionObserver para activar al entrar en viewport
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

                    // Guardar referencia para cleanup en detach
                    container._jarabaStatsObserver = observer;
                } else {
                    // Fallback: animar inmediatamente
                    counters.forEach(animateCounter);
                }
            });
        },

        detach: function (context, settings, trigger) {
            if (trigger !== 'unload') return;
            var containers = context.querySelectorAll
                ? context.querySelectorAll('.jaraba-stats--counter')
                : [];
            containers.forEach(function (container) {
                if (container._jarabaStatsObserver) {
                    container._jarabaStatsObserver.disconnect();
                    delete container._jarabaStatsObserver;
                }
            });
        }
    };

})(Drupal, once);

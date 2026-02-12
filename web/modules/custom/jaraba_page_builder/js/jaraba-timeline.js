/**
 * @file
 * Jaraba Timeline - Animación scroll-triggered con efecto staggered.
 *
 * Los ítems del timeline aparecen progresivamente al hacer scroll,
 * usando Intersection Observer con delays escalonados (200ms entre cada ítem).
 *
 * @see docs/arquitectura/2026-02-08_plan_elevacion_page_builder_clase_mundial.md
 */

(function (Drupal) {
    'use strict';

    /**
     * Inicializa los timelines con animación scroll-triggered.
     *
     * @param {Element} context - Contexto DOM.
     */
    function initTimelines(context) {
        var containers = context.querySelectorAll('.jaraba-timeline');

        containers.forEach(function (container) {
            if (container.dataset.jarabaTimelineInit) {
                return;
            }
            container.dataset.jarabaTimelineInit = 'true';

            var items = container.querySelectorAll('.jaraba-timeline__item');
            if (!items.length) return;

            // Estado inicial: ocultos
            items.forEach(function (item) {
                item.style.opacity = '0';
                item.style.transform = 'translateY(30px)';
                item.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            });

            if (typeof IntersectionObserver !== 'undefined') {
                var observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            var item = entry.target;
                            var itemDelay = parseInt(item.getAttribute('data-delay') || '0', 10);
                            setTimeout(function () {
                                item.style.opacity = '1';
                                item.style.transform = 'translateY(0)';
                            }, itemDelay);
                            observer.unobserve(item);
                        }
                    });
                }, { threshold: 0.2 });

                items.forEach(function (item, index) {
                    item.setAttribute('data-delay', String(index * 200));
                    observer.observe(item);
                });
            } else {
                // Fallback: mostrar todo
                items.forEach(function (item) {
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                });
            }
        });
    }

    // Drupal behavior
    Drupal.behaviors.jarabaTimeline = {
        attach: function (context) {
            initTimelines(context);
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initTimelines(document);
        });
    } else {
        initTimelines(document);
    }

    window.jarabaInitTimelines = initTimelines;

})(Drupal);

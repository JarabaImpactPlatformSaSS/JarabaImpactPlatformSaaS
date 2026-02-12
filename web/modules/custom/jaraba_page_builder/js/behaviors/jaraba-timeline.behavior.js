/**
 * @file
 * Behavior: Timeline con animación scroll-triggered.
 *
 * Anima los ítems del timeline con efecto staggered (escalonado)
 * al hacerse visibles en el viewport mediante IntersectionObserver.
 *
 * Selector: .jaraba-timeline .jaraba-timeline__item
 *
 * @see grapesjs-jaraba-blocks.js → timelineScript
 */

(function (Drupal, once) {
    'use strict';

    Drupal.behaviors.jarabaTimeline = {
        attach: function (context) {
            var timelines = once('jaraba-timeline', '.jaraba-timeline', context);

            timelines.forEach(function (container) {
                var items = container.querySelectorAll('.jaraba-timeline__item');
                if (!items.length) return;

                // Estado inicial: ocultos con desplazamiento
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

                    // Asignar delay escalonado a cada ítem
                    items.forEach(function (item, index) {
                        item.setAttribute('data-delay', String(index * 200));
                        observer.observe(item);
                    });

                    // Guardar referencia para cleanup
                    container._jarabaTimelineObserver = observer;
                } else {
                    // Fallback: mostrar todo
                    items.forEach(function (item) {
                        item.style.opacity = '1';
                        item.style.transform = 'translateY(0)';
                    });
                }
            });
        },

        detach: function (context, settings, trigger) {
            if (trigger !== 'unload') return;
            var timelines = context.querySelectorAll
                ? context.querySelectorAll('.jaraba-timeline')
                : [];
            timelines.forEach(function (container) {
                if (container._jarabaTimelineObserver) {
                    container._jarabaTimelineObserver.disconnect();
                    delete container._jarabaTimelineObserver;
                }
            });
        }
    };

})(Drupal, once);

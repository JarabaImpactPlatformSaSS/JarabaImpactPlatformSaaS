/**
 * @file
 * JavaScript base del Page Builder.
 */

(function (Drupal, once) {
    'use strict';

    /**
     * Namespace para el Page Builder.
     */
    Drupal.jarabaPageBuilder = Drupal.jarabaPageBuilder || {};

    /**
     * Inicializa componentes del Page Builder.
     */
    Drupal.behaviors.jarabaPageBuilder = {
        attach: function (context) {
            // Animación fade-in para bloques al hacer scroll.
            once('page-builder-animate', '[class*="jaraba-"]', context)
                .forEach(function (element) {
                    Drupal.jarabaPageBuilder.observeElement(element);
                });
        }
    };

    /**
     * Observa elementos para animación al entrar en viewport.
     *
     * @param {HTMLElement} element
     *   El elemento a observar.
     */
    Drupal.jarabaPageBuilder.observeElement = function (element) {
        if (!('IntersectionObserver' in window)) {
            element.classList.add('is-visible');
            return;
        }

        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });

        observer.observe(element);
    };

    /**
     * Contador animado para stats.
     *
     * @param {HTMLElement} element
     *   El elemento con el número a animar.
     */
    Drupal.jarabaPageBuilder.animateCounter = function (element) {
        const target = parseInt(element.dataset.count, 10);
        const duration = 2000;
        const startTime = performance.now();

        function updateCounter(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);

            // Easing function (ease-out).
            const easeOut = 1 - Math.pow(1 - progress, 3);
            const current = Math.round(target * easeOut);

            element.textContent = current.toLocaleString('es-ES');

            if (progress < 1) {
                requestAnimationFrame(updateCounter);
            }
        }

        requestAnimationFrame(updateCounter);
    };

})(Drupal, once);

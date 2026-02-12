/**
 * @file
 * Lenis smooth scroll integration for landing pages.
 *
 * F12 — Lenis Integration Premium.
 * Solo se activa en páginas frontend (no admin).
 * Respeta prefers-reduced-motion para accesibilidad.
 */
(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.lenisScroll = {
    attach: function (context) {
      once('lenis-init', 'body', context).forEach(function (body) {
        // Respetar preferencias de accesibilidad.
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
          return;
        }

        // Solo activar en páginas frontend (no admin).
        if (body.classList.contains('path-admin')) {
          return;
        }

        // Verificar que Lenis está disponible.
        if (typeof Lenis === 'undefined') {
          return;
        }

        var lenis = new Lenis({
          duration: 1.2,
          easing: function (t) {
            return Math.min(1, 1.001 - Math.pow(2, -10 * t));
          },
          smooth: true,
          smoothTouch: false
        });

        function raf(time) {
          lenis.raf(time);
          requestAnimationFrame(raf);
        }
        requestAnimationFrame(raf);

        // Exponer instancia para integración con otros behaviors.
        window.lenisInstance = lenis;
      });
    }
  };

})(Drupal, once);

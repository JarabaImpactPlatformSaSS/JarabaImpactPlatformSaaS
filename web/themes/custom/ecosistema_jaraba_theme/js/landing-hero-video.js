/**
 * @file
 * landing-hero-video.js — Video hero autoplay con IntersectionObserver.
 *
 * Pausa el video cuando no visible. Respeta prefers-reduced-motion
 * mostrando solo el poster image.
 *
 * DIRECTRICES: Drupal.behaviors, once(), a11y (reduced-motion)
 */
(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.jarabaHeroVideo = {
    attach: function (context) {
      var sections = once('hero-video', '[data-hero-video]', context);

      if (!sections.length) {
        return;
      }

      var prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

      sections.forEach(function (section) {
        var video = section.querySelector('[data-hero-video-el]');
        if (!video) {
          return;
        }

        // Reduced motion: pause and show poster.
        if (prefersReduced) {
          video.pause();
          video.removeAttribute('autoplay');
          return;
        }

        // Mobile data saver: check connection API.
        if (navigator.connection && navigator.connection.saveData) {
          video.pause();
          video.removeAttribute('autoplay');
          return;
        }

        // IntersectionObserver: pause when not visible.
        if ('IntersectionObserver' in window) {
          var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
              if (entry.isIntersecting) {
                video.play().catch(function () {
                  // Autoplay blocked — silent fail.
                });
              } else {
                video.pause();
              }
            });
          }, { threshold: 0.25 });

          observer.observe(video);
        }
      });
    }
  };
}(Drupal, once));

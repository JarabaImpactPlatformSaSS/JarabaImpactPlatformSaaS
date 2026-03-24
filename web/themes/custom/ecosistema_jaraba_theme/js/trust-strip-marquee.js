/**
 * @file trust-strip-marquee.js
 * Trust strip marquee behavior — pause on hover/focus, respect reduced motion.
 *
 * @see _trust-strip.html.twig
 * @see _trust-strip.scss
 */
(function (Drupal, once) {

  'use strict';

  Drupal.behaviors.trustStripMarquee = {
    attach: function (context) {
      once('trust-strip-marquee', '[data-trust-marquee]', context).forEach(function (track) {
        var slides = track.querySelectorAll('.trust-strip__slide');

        // Pause animation on hover.
        track.addEventListener('mouseenter', function () {
          slides.forEach(function (slide) {
            slide.style.animationPlayState = 'paused';
          });
        });
        track.addEventListener('mouseleave', function () {
          slides.forEach(function (slide) {
            slide.style.animationPlayState = 'running';
          });
        });

        // Pause on focus-within for keyboard navigation.
        track.addEventListener('focusin', function () {
          slides.forEach(function (slide) {
            slide.style.animationPlayState = 'paused';
          });
        });
        track.addEventListener('focusout', function () {
          slides.forEach(function (slide) {
            slide.style.animationPlayState = 'running';
          });
        });

        // Respect prefers-reduced-motion.
        var motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
        function applyMotionPreference(mq) {
          var state = mq.matches ? 'paused' : 'running';
          slides.forEach(function (slide) {
            slide.style.animationPlayState = state;
          });
        }

        applyMotionPreference(motionQuery);
        motionQuery.addEventListener('change', applyMotionPreference);
      });
    }
  };

})(Drupal, once);

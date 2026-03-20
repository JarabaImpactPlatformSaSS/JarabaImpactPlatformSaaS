/**
 * @file
 * landing-counters.js — Animated counter-up effect for metrics.
 *
 * Uses IntersectionObserver to animate numbers when scrolled into view.
 * Respects prefers-reduced-motion.
 *
 * DIRECTRICES: Drupal.behaviors, once(), a11y (reduced-motion)
 */

(function (Drupal, once) {
  'use strict';

  /**
   * easeOutQuad easing function.
   *
   * @param {number} t - Progress value between 0 and 1.
   * @return {number} Eased progress value.
   */
  function easeOutQuad(t) {
    return t * (2 - t);
  }

  /**
   * Format a number with locale-aware thousands separator.
   *
   * Uses 'es-ES' locale so thousands use dot separator (e.g., "1.234").
   *
   * @param {number} value - The number to format.
   * @return {string} Formatted number string.
   */
  function formatNumber(value) {
    return value.toLocaleString('es-ES');
  }

  /**
   * Animate a single counter element from 0 to its target value.
   *
   * @param {HTMLElement} el - The counter element.
   */
  function animateCounter(el) {
    var target = parseFloat(el.dataset.counterTarget);
    var suffix = el.dataset.counterSuffix || '';
    var prefix = el.dataset.counterPrefix || '';
    var duration = 2000;
    var startTime = null;
    var prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (isNaN(target)) {
      return;
    }

    if (prefersReduced) {
      el.textContent = prefix + formatNumber(target) + suffix;
      return;
    }

    function step(timestamp) {
      if (!startTime) {
        startTime = timestamp;
      }

      var elapsed = timestamp - startTime;
      var progress = Math.min(elapsed / duration, 1);
      var eased = easeOutQuad(progress);
      var current = Math.round(eased * target);

      el.textContent = prefix + formatNumber(current) + suffix;

      if (progress < 1) {
        requestAnimationFrame(step);
      } else {
        el.textContent = prefix + formatNumber(target) + suffix;
      }
    }

    requestAnimationFrame(step);
  }

  Drupal.behaviors.jarabaLandingCounters = {
    attach: function (context) {
      var elements = once(
        'jaraba-counter',
        '[data-counter-target]',
        context
      );

      if (!elements.length) {
        return;
      }

      if (!('IntersectionObserver' in window)) {
        elements.forEach(function (el) {
          animateCounter(el);
        });
        return;
      }

      var observer = new IntersectionObserver(
        function (entries) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting) {
              animateCounter(entry.target);
              observer.unobserve(entry.target);
            }
          });
        },
        {
          threshold: 0.2,
        }
      );

      elements.forEach(function (el) {
        observer.observe(el);
      });
    },
  };
}(Drupal, once));

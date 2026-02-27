/**
 * @file
 * JavaScript behaviors for Success Cases pages.
 *
 * Uses Drupal.behaviors + once() to avoid duplication.
 * Handles: smooth scroll on filter click, counter animations.
 *
 * @ingroup jaraba_success_cases
 */
(function (Drupal, once) {
  'use strict';

  /**
   * Animate counter values on the metrics bar when visible.
   */
  Drupal.behaviors.successCasesCounters = {
    attach: function (context) {
      once('sc-counter', '[data-counter]', context).forEach(function (el) {
        var target = el.getAttribute('data-counter');
        // Only animate if it looks like a number.
        var numericValue = parseFloat(target.replace(/[^0-9.,]/g, ''));
        if (isNaN(numericValue)) {
          return;
        }

        var prefix = target.match(/^[^0-9]*/)[0] || '';
        var suffix = target.match(/[^0-9.,]*$/)[0] || '';
        var duration = 1500;
        var start = 0;
        var startTime = null;

        // Use IntersectionObserver for lazy animation.
        var observer = new IntersectionObserver(function (entries) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting) {
              observer.unobserve(el);
              requestAnimationFrame(function step(timestamp) {
                if (!startTime) startTime = timestamp;
                var progress = Math.min((timestamp - startTime) / duration, 1);
                // Ease-out cubic.
                var eased = 1 - Math.pow(1 - progress, 3);
                var current = Math.round(start + (numericValue - start) * eased);
                el.textContent = prefix + current.toLocaleString('es-ES') + suffix;
                if (progress < 1) {
                  requestAnimationFrame(step);
                } else {
                  // Final value — use original text to preserve formatting.
                  el.textContent = target;
                }
              });
            }
          });
        }, { threshold: 0.3 });

        observer.observe(el);
      });
    }
  };

  /**
   * Add smooth scroll behavior when clicking vertical filter buttons.
   */
  Drupal.behaviors.successCasesFilters = {
    attach: function (context) {
      once('sc-filters', '.sc-filters', context).forEach(function (nav) {
        // Scroll active filter into view.
        var activeBtn = nav.querySelector('.sc-filters__btn--active');
        if (activeBtn) {
          activeBtn.scrollIntoView({ block: 'nearest', inline: 'center', behavior: 'smooth' });
        }
      });
    }
  };

})(Drupal, once);

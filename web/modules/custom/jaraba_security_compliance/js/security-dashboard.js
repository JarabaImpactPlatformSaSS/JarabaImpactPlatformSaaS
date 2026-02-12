/**
 * @file
 * JavaScript for the Security Dashboard.
 *
 * Provides:
 * - Auto-refresh of audit events
 * - Compliance gauge animation
 * - Interactive framework cards
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Security Dashboard behavior.
   *
   * Initializes gauge animation and auto-refresh for the security dashboard.
   */
  Drupal.behaviors.jarabaSecurityDashboard = {
    attach: function (context) {
      // Initialize compliance gauge animation.
      once('security-dashboard-gauge', '.security-dashboard__gauge', context).forEach(function (gauge) {
        var score = parseInt(gauge.getAttribute('data-score'), 10) || 0;
        var fill = gauge.querySelector('.security-dashboard__gauge-fill');
        var numberEl = gauge.querySelector('.security-dashboard__gauge-number');

        if (fill && numberEl) {
          // Animate the gauge fill.
          var circumference = 2 * Math.PI * 54; // r=54
          var targetOffset = (score / 100) * circumference;

          // Start from 0 and animate to target.
          fill.style.strokeDasharray = '0 ' + circumference;

          requestAnimationFrame(function () {
            fill.style.transition = 'stroke-dasharray 1.5s ease-out';
            fill.style.strokeDasharray = targetOffset + ' ' + circumference;
          });

          // Animate the number.
          animateNumber(numberEl, 0, score, 1500);
        }
      });

      // Initialize auto-refresh.
      once('security-dashboard-refresh', '.security-dashboard', context).forEach(function (dashboard) {
        var settings = drupalSettings.securityDashboard || {};
        var interval = settings.refreshInterval || 30000;

        if (interval > 0) {
          setInterval(function () {
            // Reload the events section only.
            var eventsTable = dashboard.querySelector('.security-dashboard__events-table');
            if (eventsTable && document.visibilityState === 'visible') {
              Drupal.ajax({
                url: window.location.href,
                wrapper: '',
                method: 'replaceWith'
              });
            }
          }, interval);
        }
      });

      // Framework card interactions.
      once('security-dashboard-framework-cards', '.security-dashboard__framework-card', context).forEach(function (card) {
        card.addEventListener('click', function () {
          card.classList.toggle('security-dashboard__framework-card--expanded');
        });
      });
    }
  };

  /**
   * Animates a number from start to end over duration milliseconds.
   *
   * @param {HTMLElement} element
   *   The element containing the number.
   * @param {number} start
   *   Starting value.
   * @param {number} end
   *   Ending value.
   * @param {number} duration
   *   Animation duration in ms.
   */
  function animateNumber(element, start, end, duration) {
    var startTime = null;

    function step(timestamp) {
      if (!startTime) {
        startTime = timestamp;
      }
      var progress = Math.min((timestamp - startTime) / duration, 1);
      var current = Math.round(start + (end - start) * easeOutCubic(progress));
      element.textContent = current;

      if (progress < 1) {
        requestAnimationFrame(step);
      }
    }

    requestAnimationFrame(step);
  }

  /**
   * Ease-out cubic easing function.
   *
   * @param {number} t
   *   Progress (0 to 1).
   * @return {number}
   *   Eased value.
   */
  function easeOutCubic(t) {
    return 1 - Math.pow(1 - t, 3);
  }

})(Drupal, drupalSettings, once);

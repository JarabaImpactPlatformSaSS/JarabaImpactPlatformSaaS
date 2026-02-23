/**
 * @file
 * Retention Dashboard - Interactividad del dashboard de retencion verticalizada.
 *
 * PROPOSITO:
 * - Animacion de contadores de estadisticas.
 * - Highlight del mes actual en el heatmap.
 * - Tooltips en celdas del heatmap.
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Stat counter animation.
   */
  Drupal.behaviors.csRetentionStatCounters = {
    attach: function (context) {
      once('cs-retention-counters', '.cs-retention__stat-value', context).forEach(function (el) {
        var target = parseInt(el.getAttribute('data-count'), 10);
        if (isNaN(target)) {
          return;
        }

        var duration = 800;
        var start = 0;
        var startTime = null;

        function animate(timestamp) {
          if (!startTime) {
            startTime = timestamp;
          }
          var progress = Math.min((timestamp - startTime) / duration, 1);
          var eased = 1 - Math.pow(1 - progress, 3);
          el.textContent = Math.round(start + (target - start) * eased);

          if (progress < 1) {
            requestAnimationFrame(animate);
          }
        }

        requestAnimationFrame(animate);
      });
    }
  };

  /**
   * Heatmap current month highlight.
   */
  Drupal.behaviors.csRetentionHeatmapHighlight = {
    attach: function (context) {
      once('cs-heatmap-highlight', '.cs-heatmap__grid', context).forEach(function (grid) {
        var settings = drupalSettings.jarabaCs || {};
        var currentMonth = settings.currentMonth || new Date().getMonth() + 1;

        var cells = grid.querySelectorAll('.cs-heatmap__cell[data-month="' + currentMonth + '"]');
        cells.forEach(function (cell) {
          cell.classList.add('cs-heatmap__cell--current');
        });

        // Highlight header too.
        var headers = grid.querySelectorAll('.cs-heatmap__cell--header');
        if (headers[currentMonth - 1]) {
          headers[currentMonth - 1].classList.add('cs-heatmap__cell--current-header');
        }
      });
    }
  };

  /**
   * Heatmap cell tooltip on hover.
   */
  Drupal.behaviors.csRetentionHeatmapTooltips = {
    attach: function (context) {
      once('cs-heatmap-tooltips', '.cs-heatmap__cell[data-month]', context).forEach(function (cell) {
        var title = cell.getAttribute('title');
        if (!title) {
          return;
        }

        cell.addEventListener('mouseenter', function () {
          cell.setAttribute('aria-label', title);
        });
      });
    }
  };

})(Drupal, drupalSettings, once);

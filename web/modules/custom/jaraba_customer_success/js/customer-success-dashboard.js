/**
 * @file
 * Customer Success Dashboard - Interactividad frontend.
 *
 * PROPÓSITO:
 * Comportamiento JS para el dashboard del CSM:
 * - Tooltips en barras de score.
 * - Animación de counters en stats cards.
 * - Filtrado inline de health scores.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Anima los valores numéricos de las stat cards.
   */
  Drupal.behaviors.csStatCounters = {
    attach: function (context) {
      once('cs-stat-counters', '.cs-stat-card__value', context).forEach(function (el) {
        var target = parseInt(el.textContent, 10);
        if (isNaN(target)) {
          return;
        }

        var current = 0;
        var increment = Math.ceil(target / 30);
        var timer = setInterval(function () {
          current += increment;
          if (current >= target) {
            current = target;
            clearInterval(timer);
          }
          el.textContent = el.textContent.includes('%')
            ? current + '%'
            : current.toString();
        }, 30);
      });
    }
  };

  /**
   * Tooltips en componentes del health score breakdown.
   */
  Drupal.behaviors.csHealthTooltips = {
    attach: function (context) {
      once('cs-health-tooltips', '.cs-health-card__component', context).forEach(function (el) {
        var label = el.querySelector('.cs-health-card__component-label');
        var value = el.querySelector('.cs-health-card__component-value');
        if (label && value) {
          el.setAttribute('title', label.textContent.trim() + ': ' + value.textContent.trim() + '/100');
        }
      });
    }
  };

  /**
   * Filtrado de health scores por categoría en el dashboard.
   */
  Drupal.behaviors.csHealthFilter = {
    attach: function (context) {
      once('cs-health-filter', '.cs-distribution__legend-item', context).forEach(function (el) {
        el.style.cursor = 'pointer';
        el.addEventListener('click', function () {
          var items = context.querySelectorAll('.cs-health-list__item');
          var category = '';

          if (el.classList.contains('cs-distribution__legend-item--healthy')) {
            category = 'healthy';
          } else if (el.classList.contains('cs-distribution__legend-item--neutral')) {
            category = 'neutral';
          } else if (el.classList.contains('cs-distribution__legend-item--at-risk')) {
            category = 'at_risk';
          } else if (el.classList.contains('cs-distribution__legend-item--critical')) {
            category = 'critical';
          }

          items.forEach(function (item) {
            if (!category || item.classList.contains('cs-health-list__item--' + category)) {
              item.style.display = '';
            } else {
              item.style.display = 'none';
            }
          });
        });
      });
    }
  };

})(Drupal, once);

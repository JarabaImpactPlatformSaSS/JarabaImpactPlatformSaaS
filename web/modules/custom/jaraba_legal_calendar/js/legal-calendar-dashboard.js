(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Behavior para el dashboard de agenda juridica.
   */
  Drupal.behaviors.legalCalendarDashboard = {
    attach: function (context) {
      once('legal-calendar-dashboard', '.legal-calendar-dashboard', context).forEach(function (el) {
        var settings = drupalSettings.jarabaLegalCalendar || {};
        var apiBase = settings.apiBase || '/api/v1/legal/calendar';

        // Highlight overdue deadlines.
        el.querySelectorAll('.ej-deadline-card--overdue').forEach(function (card) {
          card.setAttribute('aria-label', Drupal.t('Plazo vencido'));
        });
      });
    },
  };
})(Drupal, drupalSettings, once);

/**
 * @file
 * LegalCases — Dashboard behavior.
 *
 * Estructura: Drupal behavior para el dashboard de expedientes juridicos.
 * Logica: Filtrado dinamico, carga AJAX y UX del dashboard.
 * Directriz: Usar Drupal.behaviors + once() siempre.
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.legalCasesDashboard = {
    attach: function (context) {
      once('legal-cases-dashboard', '.legal-cases-dashboard', context).forEach(function (el) {
        var settings = drupalSettings.jarabaLegalCases || {};
        // Dashboard initialization — future: filters, search, real-time updates
      });
    },
  };
})(Drupal, drupalSettings, once);

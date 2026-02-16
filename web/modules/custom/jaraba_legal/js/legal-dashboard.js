/**
 * @file
 * Jaraba Legal — Dashboard interactivity.
 *
 * Gestiona la interactividad del dashboard legal:
 * actualización de métricas, filtros y navegación.
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.jarabaLegalDashboard = {
    attach: function (context) {
      once('jaraba-legal-dashboard', '.jaraba-legal-dashboard', context).forEach(function (element) {
        // Dashboard initialization placeholder.
        // FASE 5: Implementar carga dinámica de métricas.
      });
    }
  };

})(Drupal, once);

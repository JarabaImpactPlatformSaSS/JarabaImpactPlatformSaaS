/**
 * @file
 * JavaScript del dashboard de Disaster Recovery.
 *
 * Proporciona interactividad al dashboard: auto-refresco de metricas,
 * graficos de estado y actualizaciones en tiempo real.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Comportamiento del dashboard DR.
   */
  Drupal.behaviors.jarabaDrDashboard = {
    attach: function (context) {
      once('jaraba-dr-dashboard', '.jaraba-dr-dashboard', context).forEach(function (element) {
        // Stub: logica de dashboard implementada en fases posteriores.
        console.log('Jaraba DR Dashboard inicializado.');
      });
    }
  };

})(Drupal, once);

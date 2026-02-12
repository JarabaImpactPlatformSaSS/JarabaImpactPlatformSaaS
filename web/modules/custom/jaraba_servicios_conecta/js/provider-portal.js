/**
 * @file
 * ServiciosConecta — Provider Portal behavior.
 *
 * Estructura: Drupal behavior para el portal del profesional.
 * Lógica: Dashboard interactivo, acciones rápidas, calendario.
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.serviciosProviderPortal = {
    attach: function (context) {
      once('servicios-provider-portal', '.servicios-provider-dashboard', context).forEach(function (el) {
        // Inicialización del portal del profesional
      });
    },
  };
})(Drupal, once);

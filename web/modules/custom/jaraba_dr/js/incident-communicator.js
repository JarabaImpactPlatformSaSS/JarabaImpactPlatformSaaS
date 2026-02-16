/**
 * @file
 * JavaScript del comunicador de incidentes.
 *
 * Proporciona interactividad para la gestion de comunicaciones
 * de incidentes: envio de notificaciones, actualizacion de estado.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Comportamiento del comunicador de incidentes.
   */
  Drupal.behaviors.jarabaDrIncidentCommunicator = {
    attach: function (context) {
      once('jaraba-dr-incident-communicator', '.dr-incident-communicator', context).forEach(function (element) {
        // Stub: logica de comunicador implementada en fases posteriores.
        console.log('Jaraba DR Incident Communicator inicializado.');
      });
    }
  };

})(Drupal, once);

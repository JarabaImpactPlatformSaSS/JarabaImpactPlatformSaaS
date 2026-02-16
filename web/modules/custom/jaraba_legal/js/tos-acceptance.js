/**
 * @file
 * Jaraba Legal — ToS acceptance modal.
 *
 * Gestiona el modal de aceptación de Terms of Service:
 * carga del contenido, scroll tracking y submit de aceptación.
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.jarabaLegalTosAcceptance = {
    attach: function (context) {
      once('jaraba-legal-tos', '.tos-acceptance', context).forEach(function (element) {
        // ToS acceptance initialization placeholder.
        // FASE 5: Implementar modal bloqueante de aceptación.
      });
    }
  };

})(Drupal, once);

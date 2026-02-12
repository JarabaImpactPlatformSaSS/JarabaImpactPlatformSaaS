/**
 * @file
 * Logica interactiva para el portal de desarrolladores.
 *
 * Gestiona la interfaz del portal de desarrolladores de conectores,
 * incluyendo filtrado de conectores y acciones rapidas.
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.jarabaIntegrationsDeveloperPortal = {
    attach: function (context) {
      once('developer-portal', '.developer-portal', context).forEach(function (portal) {

        // Resaltar fila al hover en la tabla de conectores.
        var rows = portal.querySelectorAll('.developer-portal__connector-row');
        rows.forEach(function (row) {
          row.addEventListener('mouseenter', function () {
            row.classList.add('developer-portal__connector-row--hover');
          });
          row.addEventListener('mouseleave', function () {
            row.classList.remove('developer-portal__connector-row--hover');
          });
        });

        // Confirmacion antes de enviar a revision.
        var submitLinks = portal.querySelectorAll('.developer-portal__action-link--primary');
        submitLinks.forEach(function (link) {
          link.addEventListener('click', function (e) {
            if (!confirm(Drupal.t('Se enviara el conector a revision. Continuar?'))) {
              e.preventDefault();
            }
          });
        });
      });
    }
  };

})(Drupal, once);

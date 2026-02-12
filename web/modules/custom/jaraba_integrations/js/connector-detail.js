/**
 * @file
 * Logica interactiva para la pagina de detalle de un conector.
 *
 * Gestiona tabs de informacion, changelog y reviews
 * en la vista detallada de un conector del marketplace.
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.jarabaIntegrationsConnectorDetail = {
    attach: function (context) {
      once('connector-detail', '.connector-detail', context).forEach(function (detail) {

        // Tab switching.
        var tabs = detail.querySelectorAll('.connector-detail__tab');
        var panels = detail.querySelectorAll('.connector-detail__panel');

        tabs.forEach(function (tab) {
          tab.addEventListener('click', function () {
            var targetId = tab.dataset.target;

            // Desactivar todos los tabs y paneles.
            tabs.forEach(function (t) {
              t.classList.remove('connector-detail__tab--active');
              t.setAttribute('aria-selected', 'false');
            });
            panels.forEach(function (p) {
              p.hidden = true;
            });

            // Activar el seleccionado.
            tab.classList.add('connector-detail__tab--active');
            tab.setAttribute('aria-selected', 'true');
            var targetPanel = document.getElementById(targetId);
            if (targetPanel) {
              targetPanel.hidden = false;
            }
          });
        });
      });
    }
  };

})(Drupal, once);

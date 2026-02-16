/**
 * @file
 * JavaScript de la pagina de estado publica.
 *
 * Proporciona auto-refresco de la pagina de estado segun el intervalo
 * configurado en data-refresh-seconds.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Comportamiento de la status page.
   */
  Drupal.behaviors.jarabaDrStatusPage = {
    attach: function (context) {
      once('jaraba-dr-status-page', '.jaraba-dr-status-page', context).forEach(function (element) {
        var refreshSeconds = parseInt(element.getAttribute('data-refresh-seconds'), 10) || 60;

        // Auto-refresco de la pagina.
        setInterval(function () {
          window.location.reload();
        }, refreshSeconds * 1000);
      });
    }
  };

})(Drupal, once);

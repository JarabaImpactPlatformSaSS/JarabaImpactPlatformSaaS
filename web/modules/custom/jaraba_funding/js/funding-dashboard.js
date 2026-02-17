/**
 * @file
 * Comportamientos JavaScript del dashboard de Fondos y Subvenciones.
 *
 * Estructura: Behavior de Drupal que inicializa interacciones del
 *   dashboard de fondos: filtros, busqueda y actualizaciones AJAX.
 *
 * Logica: Lee la configuracion del backend desde drupalSettings.jarabaFunding
 *   y habilita interacciones cliente como filtrado de convocatorias
 *   y actualizacion de estados via API REST.
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Behavior principal del dashboard de fondos.
   */
  Drupal.behaviors.jarabaFunding = {
    attach: function (context) {
      var elements = once('jaraba-funding-init', '.funding-dashboard', context);
      if (!elements.length) {
        return;
      }

      var config = drupalSettings.jarabaFunding || {};
      var apiBase = config.apiBase || '/api/v1/funding';

      // Inicializar interacciones del dashboard
      Drupal.jarabaFunding = {
        apiBase: apiBase,

        /**
         * Realiza una peticion a la API de fondos.
         */
        apiRequest: function (endpoint, options) {
          options = options || {};
          options.headers = options.headers || {};
          options.headers['Content-Type'] = 'application/json';

          return fetch(apiBase + endpoint, options)
            .then(function (response) {
              return response.json();
            });
        },
      };
    },
  };

})(Drupal, drupalSettings, once);

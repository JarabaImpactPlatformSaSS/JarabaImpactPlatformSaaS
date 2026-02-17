/**
 * @file
 * Jaraba Multi-Region — Dashboard behavior.
 *
 * ESTRUCTURA:
 * Behavior de Drupal que inicializa el dashboard de administracion multi-region.
 * Se adjunta al contexto DOM mediante el patron once() para evitar duplicados.
 *
 * LOGICA:
 * Lee la configuracion base de drupalSettings.jarabaMultiregion y expone
 * un namespace Drupal.jarabaMultiregion con utilidades de API REST para
 * comunicacion con los endpoints del modulo (regiones, tipos de cambio,
 * reglas fiscales, validacion VIES).
 *
 * SINTAXIS:
 * IIFE con Drupal, drupalSettings y once como dependencias.
 * Patron Drupal.behaviors para integracion con el ciclo de vida del DOM.
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.jarabaMultiregion = {
    /**
     * Inicializa el dashboard multi-region.
     *
     * ESTRUCTURA: Metodo attach del behavior, ejecutado por Drupal en cada
     *   ciclo de adjuncion (carga de pagina, AJAX, BigPipe).
     *
     * LOGICA:
     * 1. Busca elementos .region-admin no procesados via once().
     * 2. Lee configuracion de drupalSettings (apiBase).
     * 3. Expone Drupal.jarabaMultiregion con metodo apiRequest para
     *    comunicacion con los endpoints REST del modulo.
     *
     * SINTAXIS: once() previene re-inicializacion en ciclos AJAX.
     *
     * @param {HTMLElement} context
     *   Contexto DOM donde buscar elementos.
     */
    attach: function (context) {
      var elements = once('jaraba-multiregion-init', '.region-admin', context);
      if (!elements.length) {
        return;
      }

      var config = drupalSettings.jarabaMultiregion || {};
      var apiBase = config.apiBase || '/api/v1';

      /**
       * Namespace publico del modulo multi-region.
       *
       * ESTRUCTURA: Objeto expuesto en Drupal.jarabaMultiregion con la
       *   configuracion base y metodos utilitarios de API.
       *
       * LOGICA: apiRequest() encapsula fetch() con cabeceras JSON y
       *   parseo automatico de respuesta. Los componentes del dashboard
       *   consumen este namespace para operaciones AJAX.
       */
      Drupal.jarabaMultiregion = {
        apiBase: apiBase,

        /**
         * Realiza una peticion a la API REST del modulo.
         *
         * @param {string} endpoint
         *   Ruta relativa al apiBase (ej: '/regions/current').
         * @param {Object} options
         *   Opciones adicionales para fetch() (method, body, headers).
         * @return {Promise}
         *   Promise que resuelve con el JSON de la respuesta.
         */
        apiRequest: function (endpoint, options) {
          options = options || {};
          options.headers = options.headers || {};
          options.headers['Content-Type'] = 'application/json';
          return fetch(apiBase + endpoint, options)
            .then(function (response) { return response.json(); });
        },
      };
    },
  };

})(Drupal, drupalSettings, once);

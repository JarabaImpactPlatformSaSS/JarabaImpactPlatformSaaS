/**
 * @file
 * Cargador diferido de CSS no-crítico.
 *
 * Este script convierte los stylesheets con media="print" a media="all"
 * después de que la página haya cargado, permitiendo renderizado no-bloqueante.
 *
 * Patrón recomendado por web.dev para eliminar CSS render-blocking.
 *
 * @see https://web.dev/defer-non-critical-css/
 */

(function (Drupal) {
    'use strict';

    /**
     * Comportamiento para carga diferida de CSS.
     *
     * @type {Drupal~behavior}
     */
    Drupal.behaviors.jarabaCriticalCssLoader = {
        attach: function (context, settings) {
            // Solo ejecutar una vez en el documento completo.
            if (context !== document) {
                return;
            }

            // Verificar si el CSS crítico está habilitado.
            if (!settings.jarabaPerformance?.criticalCss?.enabled) {
                return;
            }

            // Función para cargar stylesheets diferidos.
            var loadDeferredStylesheets = function () {
                // Buscar todos los stylesheets marcados para carga diferida.
                var deferredLinks = document.querySelectorAll(
                    'link[rel="stylesheet"][media="print"]:not(.css-loaded)'
                );

                deferredLinks.forEach(function (link) {
                    // Cambiar media a "all" para activar el stylesheet.
                    link.media = 'all';
                    link.classList.add('css-loaded');
                });

                // Registrar en consola para debugging en desarrollo.
                if (deferredLinks.length > 0 && window.console) {
                    console.log(
                        '[Jaraba Performance] ' + deferredLinks.length + ' stylesheets cargados de forma diferida.'
                    );
                }
            };

            // Ejecutar después de que la página termine de cargar.
            if (document.readyState === 'complete') {
                loadDeferredStylesheets();
            } else {
                window.addEventListener('load', loadDeferredStylesheets);
            }
        }
    };

})(Drupal);

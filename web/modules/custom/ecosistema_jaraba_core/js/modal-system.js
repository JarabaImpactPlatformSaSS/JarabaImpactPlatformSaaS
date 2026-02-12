/**
 * @file
 * Sistema de modales unificado para CRUD sin abandonar la pagina.
 *
 * PROPOSITO:
 * Behavior que detecta links con class="use-ajax" y
 * data-dialog-type="modal", configura Drupal.dialog con opciones
 * por defecto y callback on close para refrescar contenido.
 *
 * ESTRUCTURA:
 * - Detecta enlaces con data-dialog-type="modal"
 * - Configura ancho y clase dialog por defecto
 * - Escucha evento dialog:afterclose para refrescar contenido
 *
 * SINTAXIS:
 * Se registra como Drupal.behaviors para re-ejecutar en AJAX.
 */
(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.jarabaModalSystem = {
    attach: function (context) {
      // Configurar enlaces modales con opciones por defecto.
      var modalLinks = once('jaraba-modal', '[data-dialog-type="modal"]', context);
      modalLinks.forEach(function (link) {
        // Asegurar que tiene la clase use-ajax.
        if (!link.classList.contains('use-ajax')) {
          link.classList.add('use-ajax');
        }

        // Aplicar opciones de dialog por defecto si no tiene.
        if (!link.getAttribute('data-dialog-options')) {
          var options = {
            width: 700,
            dialogClass: 'ej-modal',
            autoResize: true,
            modal: true
          };
          link.setAttribute('data-dialog-options', JSON.stringify(options));
        }

        // Asegurar formato correcto del URL para AJAX.
        var href = link.getAttribute('href');
        if (href && href.indexOf('_wrapper_format') === -1) {
          var separator = href.indexOf('?') === -1 ? '?' : '&';
          link.setAttribute('href', href + separator + '_wrapper_format=drupal_modal');
        }
      });

      // Escuchar cierre de modales para refrescar contenido.
      if (!document.body.dataset.jarabaModalListener) {
        document.body.dataset.jarabaModalListener = 'true';

        // Usar jQuery event ya que Drupal.dialog usa jQuery.
        if (window.jQuery) {
          jQuery(window).on('dialog:afterclose', function () {
            // Refrescar el contenido principal de la pagina.
            var mainContent = document.querySelector('[data-drupal-selector="main-content"], .ej-page__content, main .content');
            if (mainContent && typeof Drupal.ajax !== 'undefined') {
              // Intentar refrescar via AJAX si hay un comando disponible.
              var currentPath = window.location.pathname;
              try {
                var ajax = Drupal.ajax({
                  url: currentPath,
                  wrapper: mainContent.id || 'main-content',
                  method: 'replaceWith'
                });
                // Solo ejecutar si tiene wrapper valido.
                if (mainContent.id) {
                  ajax.execute();
                } else {
                  // Fallback: recargar pagina completa.
                  window.location.reload();
                }
              }
              catch (e) {
                window.location.reload();
              }
            }
          });
        }
      }
    }
  };

})(Drupal, once);

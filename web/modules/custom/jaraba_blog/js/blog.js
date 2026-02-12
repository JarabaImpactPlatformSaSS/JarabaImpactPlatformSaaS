/**
 * @file
 * JavaScript para el modulo Blog.
 *
 * Funcionalidades:
 * - Copiar enlace al portapapeles
 * - Tracking de vistas via API
 * - Lazy loading de imagenes (nativo)
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Behavior para botones de compartir (copiar enlace).
   */
  Drupal.behaviors.blogShareCopy = {
    attach: function (context) {
      const buttons = once('blog-share-copy', '[data-copy-url]', context);

      buttons.forEach(function (button) {
        button.addEventListener('click', function () {
          var url = this.getAttribute('data-copy-url');

          if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url).then(function () {
              button.setAttribute('aria-label', Drupal.t('Enlace copiado'));
              setTimeout(function () {
                button.setAttribute('aria-label', Drupal.t('Copiar enlace'));
              }, 2000);
            });
          }
        });
      });
    }
  };

})(Drupal, once);

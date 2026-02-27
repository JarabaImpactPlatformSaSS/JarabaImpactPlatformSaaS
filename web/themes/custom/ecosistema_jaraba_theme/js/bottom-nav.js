/**
 * @file
 * Bottom Navigation — Deteccion de item activo y accion FAB crear.
 *
 * Marca el item activo basado en el pathname actual y gestiona
 * el boton central FAB (Crear) que dispara un evento custom para
 * que el modal-system muestre opciones de creacion contextuales.
 *
 * DIRECTIVAS:
 * - DRUPAL-BEHAVIORS-001: Drupal.behaviors.bottomNav
 * - ONCE-PATTERN-001: once('bottom-nav', selector, context)
 * - i18n: Sin textos hardcodeados (labels en Twig con |trans)
 *
 * CSS: _mobile-components.scss lineas 150-282 (ya compilado)
 */
(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.bottomNav = {
    attach: function (context) {
      var navs = once('bottom-nav', '.bottom-nav', context);
      if (!navs.length) {
        return;
      }

      var nav = navs[0];
      var currentPath = window.location.pathname;

      // Marcar item activo basado en la ruta actual
      var links = nav.querySelectorAll('.bottom-nav__link');
      links.forEach(function (link) {
        var href = link.getAttribute('href');
        if (href && href !== '#' && currentPath.indexOf(href) === 0) {
          link.classList.add('is-active');
          link.setAttribute('aria-current', 'page');
        }
      });

      // FAB crear — dispara evento custom para modal-system
      var fabLink = nav.querySelector('[data-action="quick-create"]');
      if (fabLink) {
        fabLink.addEventListener('click', function (e) {
          e.preventDefault();
          document.dispatchEvent(new CustomEvent('jaraba:quick-create', {
            detail: { source: 'bottom-nav' }
          }));
        });
      }
    }
  };

})(Drupal, once);

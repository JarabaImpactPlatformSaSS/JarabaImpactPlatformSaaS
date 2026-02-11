/**
 * @file
 * Jaraba Addons - Catalog page behavior.
 *
 * ESTRUCTURA:
 * Drupal behavior que gestiona la interactividad de la página del catálogo
 * de add-ons. Implementa IntersectionObserver para animaciones de entrada
 * de las tarjetas y gestiona el filtro por tipo vía URL query params.
 *
 * LÓGICA:
 * - IntersectionObserver: Observa las tarjetas .ej-addons-card y les añade
 *   la clase --visible cuando entran en el viewport (threshold: 0.1).
 * - Filter chips: Los chips de filtro usan navegación estándar (href) para
 *   el filtrado server-side, pero se interceptan para añadir smooth UX.
 *
 * RELACIONES:
 * - addon-catalog.js <- jaraba_addons.libraries.yml (registrado en)
 * - addon-catalog.js -> addons-catalog.html.twig (interactúa con)
 * - addon-catalog.js -> _catalog.scss (clases CSS esperadas)
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Behavior: Catálogo de add-ons.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.jarabaAddonsCatalog = {
    attach: function (context) {

      // -----------------------------------------------------------------
      // IntersectionObserver: Animación de entrada para tarjetas.
      // -----------------------------------------------------------------
      var cards = once('addons-catalog-cards', '.ej-addons-card', context);

      if (cards.length > 0 && 'IntersectionObserver' in window) {
        var observer = new IntersectionObserver(
          function (entries) {
            entries.forEach(function (entry) {
              if (entry.isIntersecting) {
                // Delay escalonado por índice de la tarjeta.
                var card = entry.target;
                var index = Array.prototype.indexOf.call(
                  card.parentElement.children,
                  card
                );
                var delay = Math.min(index * 80, 400);

                setTimeout(function () {
                  card.classList.add('ej-addons-card--visible');
                }, delay);

                observer.unobserve(card);
              }
            });
          },
          {
            threshold: 0.1,
            rootMargin: '0px 0px -40px 0px'
          }
        );

        cards.forEach(function (card) {
          observer.observe(card);
        });
      }
      else {
        // Fallback: Si IntersectionObserver no está disponible,
        // mostrar todas las tarjetas inmediatamente.
        cards.forEach(function (card) {
          card.classList.add('ej-addons-card--visible');
        });
      }

      // -----------------------------------------------------------------
      // Filter chips: Actualizar estado activo en navegación client-side.
      // -----------------------------------------------------------------
      var filterChips = once('addons-filter-chips', '.ej-addons-catalog__filter-chip', context);

      filterChips.forEach(function (chip) {
        chip.addEventListener('click', function (e) {
          // Dejar que la navegación estándar funcione (href).
          // Solo añadimos feedback visual inmediato.
          var allChips = document.querySelectorAll('.ej-addons-catalog__filter-chip');
          allChips.forEach(function (c) {
            c.classList.remove('ej-addons-catalog__filter-chip--active');
          });
          chip.classList.add('ej-addons-catalog__filter-chip--active');
        });
      });

    }
  };

})(Drupal, once);

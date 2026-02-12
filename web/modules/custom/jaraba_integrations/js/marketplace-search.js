/**
 * @file
 * Busqueda y filtrado del marketplace de integraciones.
 *
 * Proporciona busqueda en tiempo real y filtrado por categorias
 * para el marketplace de conectores.
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.jarabaIntegrationsMarketplaceSearch = {
    attach: function (context) {
      once('marketplace-search', '#marketplace-search', context).forEach(function (input) {
        var grid = document.getElementById('marketplace-grid');
        var debounceTimer = null;

        input.addEventListener('input', function () {
          clearTimeout(debounceTimer);
          debounceTimer = setTimeout(function () {
            var query = input.value.toLowerCase().trim();
            if (!grid) {
              return;
            }

            var cards = grid.querySelectorAll('.integrations-connector-card');
            cards.forEach(function (card) {
              var name = (card.dataset.name || '').toLowerCase();
              var category = (card.dataset.category || '').toLowerCase();
              var visible = !query || name.indexOf(query) !== -1 || category.indexOf(query) !== -1;
              card.style.display = visible ? '' : 'none';
            });
          }, 250);
        });
      });

      // Filtrado por categorias.
      once('marketplace-categories', '.integrations-marketplace__category-btn', context).forEach(function (btn) {
        btn.addEventListener('click', function () {
          var category = btn.dataset.category;
          var grid = document.getElementById('marketplace-grid');
          if (!grid) {
            return;
          }

          // Actualizar boton activo.
          document.querySelectorAll('.integrations-marketplace__category-btn').forEach(function (b) {
            b.classList.remove('integrations-marketplace__category-btn--active');
          });
          btn.classList.add('integrations-marketplace__category-btn--active');

          // Filtrar cards.
          var cards = grid.querySelectorAll('.integrations-connector-card');
          cards.forEach(function (card) {
            if (category === 'all' || card.dataset.category === category) {
              card.style.display = '';
            }
            else {
              card.style.display = 'none';
            }
          });
        });
      });
    }
  };

})(Drupal, once);

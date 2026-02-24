/**
 * @file
 * ComercioConecta — Marketplace JavaScript.
 *
 * Estructura: Comportamientos Drupal para el marketplace público.
 * Lógica: Gestiona la galería de imágenes, el selector de ordenación,
 *   y el selector de variaciones de producto.
 *
 * DIRECTRIZ: Todos los textos en Drupal.t() para traducibilidad.
 */

(function (Drupal) {
  'use strict';

  /**
   * Comportamiento: Selector de ordenación del marketplace.
   *
   * Lógica: Al cambiar el select de ordenación, redirige con el
   *   parámetro sort en la URL conservando los filtros existentes.
   */
  Drupal.behaviors.comercioSortControl = {
    attach: function (context) {
      const sortSelect = context.querySelector('[data-sort-control]');
      if (!sortSelect || sortSelect.dataset.comercioInit) return;
      sortSelect.dataset.comercioInit = 'true';

      sortSelect.addEventListener('change', function () {
        const url = new URL(window.location.href);
        url.searchParams.set('sort', this.value);
        url.searchParams.delete('page'); // Reset paginación al cambiar orden
        window.location.href = url.toString();
      });
    }
  };

  /**
   * Comportamiento: Galería de imágenes del producto.
   *
   * Lógica: Click en thumbnail cambia la imagen principal.
   *   Gestiona el estado aria-selected para accesibilidad.
   */
  Drupal.behaviors.comercioGallery = {
    attach: function (context) {
      const thumbs = context.querySelectorAll('.comercio-gallery__thumb');
      const mainImage = context.querySelector('#comercio-main-image');
      if (!mainImage || thumbs.length === 0) return;

      thumbs.forEach(function (thumb) {
        if (thumb.dataset.comercioInit) return;
        thumb.dataset.comercioInit = 'true';

        thumb.addEventListener('click', function () {
          // Actualizar imagen principal
          const newSrc = this.dataset.imageSrc;
          if (newSrc) {
            mainImage.src = newSrc;
          }

          // Actualizar estado activo
          thumbs.forEach(function (t) {
            t.classList.remove('comercio-gallery__thumb--active');
            t.setAttribute('aria-selected', 'false');
          });
          this.classList.add('comercio-gallery__thumb--active');
          this.setAttribute('aria-selected', 'true');
        });
      });
    }
  };

  /**
   * Comportamiento: Selector de variaciones de producto.
   *
   * Lógica: Al seleccionar una variación, actualiza el precio
   *   y la disponibilidad mostrados. Cambia el SKU visible.
   */
  Drupal.behaviors.comercioVariations = {
    attach: function (context) {
      const variationBtns = context.querySelectorAll('.comercio-variation-btn');
      if (variationBtns.length === 0) return;

      variationBtns.forEach(function (btn) {
        if (btn.dataset.comercioInit) return;
        btn.dataset.comercioInit = 'true';

        btn.addEventListener('click', function () {
          const price = this.dataset.variationPrice;
          const stock = parseInt(this.dataset.variationStock, 10);
          const sku = this.dataset.variationSku;

          // Actualizar estado activo
          variationBtns.forEach(function (b) {
            b.classList.remove('comercio-variation-btn--active');
          });
          this.classList.add('comercio-variation-btn--active');

          // Actualizar precio en la página
          const priceEl = context.querySelector('.comercio-product-detail__price-current');
          if (priceEl && price) {
            const formatted = parseFloat(price).toLocaleString('es-ES', {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2
            });
            priceEl.textContent = formatted + ' €';
          }

          // Actualizar SKU
          const skuEl = context.querySelector('.comercio-product-detail__sku');
          if (skuEl && sku) {
            skuEl.textContent = Drupal.t('SKU') + ': ' + sku;
          }

          // Actualizar disponibilidad
          const availEl = context.querySelector('.comercio-product-detail__availability');
          if (availEl) {
            if (stock > 0) {
              availEl.className = 'comercio-product-detail__availability comercio-product-detail__availability--in-stock';
              if (stock <= 5) {
                availEl.textContent = Drupal.t('En stock') + ' ';
                var small = document.createElement('small');
                small.textContent = '(' + Drupal.t('últimas @count unidades', {'@count': stock}) + ')';
                availEl.appendChild(small);
              } else {
                availEl.textContent = Drupal.t('En stock');
              }
            } else {
              availEl.className = 'comercio-product-detail__availability comercio-product-detail__availability--out';
              availEl.textContent = Drupal.t('Agotado');
            }
          }
        });
      });
    }
  };

  /**
   * Comportamiento: Toggle de filtros en móvil.
   *
   * Lógica: En pantallas pequeñas, la sidebar de filtros se oculta
   *   y se muestra como un panel deslizante al pulsar un botón.
   */
  Drupal.behaviors.comercioMobileFilters = {
    attach: function (context) {
      const sidebar = context.querySelector('.comercio-marketplace-sidebar');
      if (!sidebar) return;

      // Solo en móvil: crear botón toggle si no existe
      if (window.innerWidth < 992 && !context.querySelector('.comercio-filter-toggle')) {
        const sortBar = context.querySelector('.comercio-sort-bar');
        if (sortBar) {
          const toggleBtn = document.createElement('button');
          toggleBtn.className = 'comercio-btn comercio-btn--outline comercio-btn--small comercio-filter-toggle';
          toggleBtn.textContent = Drupal.t('Filtros');
          toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('comercio-marketplace-sidebar--open');
            sidebar.style.display = sidebar.classList.contains('comercio-marketplace-sidebar--open') ? 'block' : '';
          });
          sortBar.querySelector('.comercio-sort-bar__controls').prepend(toggleBtn);
        }
      }
    }
  };

})(Drupal);

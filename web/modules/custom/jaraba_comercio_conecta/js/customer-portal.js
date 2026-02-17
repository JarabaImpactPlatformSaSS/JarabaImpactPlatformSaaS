/**
 * @file
 * ComercioConecta — Customer Portal JavaScript.
 *
 * Estructura: Comportamientos Drupal para el portal del consumidor.
 * Lógica: Gestiona acciones de wishlist (añadir/quitar) y navegación
 *   del portal del cliente.
 *
 * DIRECTRIZ: Todos los textos en Drupal.t() para traducibilidad.
 */

(function (Drupal) {
  'use strict';

  /**
   * Comportamiento: Eliminar item de wishlist.
   *
   * Lógica: Al pulsar el botón de eliminar en un item de wishlist,
   *   llama a la API para eliminarlo y actualiza la UI.
   */
  Drupal.behaviors.comercioWishlistRemove = {
    attach: function (context) {
      var removeBtns = context.querySelectorAll('[data-action="remove-from-wishlist"]');
      if (removeBtns.length === 0) return;

      removeBtns.forEach(function (btn) {
        if (btn.dataset.comercioInit) return;
        btn.dataset.comercioInit = 'true';

        btn.addEventListener('click', function () {
          var productId = this.dataset.productId;
          var card = this.closest('.comercio-wishlist__card');

          if (!productId) return;

          btn.disabled = true;
          btn.textContent = Drupal.t('Eliminando...');

          fetch('/api/v1/comercio/wishlist/remove', {
            method: 'DELETE',
            headers: {
              'Content-Type': 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ product_id: parseInt(productId, 10) }),
          })
            .then(function (response) { return response.json(); })
            .then(function (result) {
              if (result.data && result.data.success) {
                if (card) {
                  card.style.opacity = '0';
                  card.style.transform = 'scale(0.95)';
                  card.style.transition = 'all 300ms ease';
                  setTimeout(function () {
                    card.remove();
                    // Comprobar si la lista queda vacía
                    var grid = document.querySelector('.comercio-wishlist__grid');
                    if (grid && grid.children.length === 0) {
                      window.location.reload();
                    }
                  }, 300);
                }
              } else {
                btn.disabled = false;
                btn.textContent = Drupal.t('Eliminar');
              }
            })
            .catch(function () {
              btn.disabled = false;
              btn.textContent = Drupal.t('Eliminar');
            });
        });
      });
    }
  };

  /**
   * Comportamiento: Añadir a wishlist desde marketplace/producto.
   *
   * Lógica: Botones con data-action="add-to-wishlist" en cualquier
   *   página del marketplace. Llama a la API y muestra feedback visual.
   */
  Drupal.behaviors.comercioWishlistAdd = {
    attach: function (context) {
      var addBtns = context.querySelectorAll('[data-action="add-to-wishlist"]');
      if (addBtns.length === 0) return;

      addBtns.forEach(function (btn) {
        if (btn.dataset.comercioInit) return;
        btn.dataset.comercioInit = 'true';

        btn.addEventListener('click', function () {
          var productId = this.dataset.productId;
          if (!productId) return;

          btn.disabled = true;
          var originalText = btn.textContent;

          fetch('/api/v1/comercio/wishlist/add', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ product_id: parseInt(productId, 10) }),
          })
            .then(function (response) { return response.json(); })
            .then(function (result) {
              if (result.data && result.data.success) {
                btn.textContent = Drupal.t('En tu lista');
                btn.classList.add('comercio-wishlist-btn--active');
              } else {
                btn.disabled = false;
                btn.textContent = originalText;
              }
            })
            .catch(function () {
              btn.disabled = false;
              btn.textContent = originalText;
            });
        });
      });
    }
  };

})(Drupal);

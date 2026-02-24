/**
 * @file
 * ComercioConecta — Merchant Portal JavaScript.
 *
 * Estructura: Comportamientos Drupal para el portal del comerciante.
 * Lógica: Gestiona acciones del dashboard, pedidos recibidos,
 *   actualización de estado y navegación del portal.
 *
 * DIRECTRIZ: Todos los textos en Drupal.t() para traducibilidad.
 */

(function (Drupal) {
  'use strict';

  // CSRF token cache for POST/PATCH/DELETE requests.
  var _csrfToken = null;
  function getCsrfToken() {
    if (_csrfToken) return Promise.resolve(_csrfToken);
    return fetch('/session/token')
      .then(function (r) { return r.text(); })
      .then(function (token) { _csrfToken = token; return token; });
  }

  /**
   * Comportamiento: Cambio de estado de pedido desde el portal.
   *
   * Lógica: Los selectores de estado en la tabla de pedidos del
   *   comerciante envían PATCH a la API para actualizar el estado
   *   del pedido.
   */
  Drupal.behaviors.comercioMerchantOrderStatus = {
    attach: function (context) {
      var statusSelects = context.querySelectorAll('[data-order-status-select]');
      if (statusSelects.length === 0) return;

      statusSelects.forEach(function (select) {
        if (select.dataset.comercioInit) return;
        select.dataset.comercioInit = 'true';

        select.addEventListener('change', function () {
          var orderId = this.dataset.orderId;
          var newStatus = this.value;

          select.disabled = true;

          getCsrfToken().then(function (token) {
            fetch('/api/v1/comercio/orders/' + orderId + '/status', {
              method: 'PATCH',
              headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': token,
              },
              body: JSON.stringify({ status: newStatus }),
            })
              .then(function (response) { return response.json(); })
              .then(function (result) {
                select.disabled = false;
                if (result.data && result.data.success) {
                  // Actualizar el badge visual
                  var row = select.closest('tr');
                  if (row) {
                    var badge = row.querySelector('.comercio-merchant-orders__status');
                    if (badge) {
                      badge.className = 'comercio-merchant-orders__status comercio-merchant-orders__status--' + newStatus;
                    }
                  }
                } else {
                  var msg = (result.meta && result.meta.message) || Drupal.t('Error actualizando estado.');
                  alert(msg);
                }
              })
              .catch(function () {
                select.disabled = false;
                alert(Drupal.t('Error de conexión.'));
              });
          });
        });
      });
    }
  };

  /**
   * Comportamiento: Tabs de navegación del portal merchant.
   *
   * Lógica: Marca la pestaña activa basándose en la URL actual.
   */
  Drupal.behaviors.comercioMerchantNav = {
    attach: function (context) {
      var navLinks = context.querySelectorAll('.comercio-merchant-nav__link');
      if (navLinks.length === 0) return;

      var currentPath = window.location.pathname;
      navLinks.forEach(function (link) {
        if (link.dataset.comercioInit) return;
        link.dataset.comercioInit = 'true';

        var href = link.getAttribute('href');
        if (href && currentPath.startsWith(href)) {
          link.classList.add('comercio-merchant-nav__link--active');
        }
      });
    }
  };

  /**
   * Comportamiento: Slide panel para configuración.
   *
   * Lógica: Los botones con data-slide-panel abren un panel lateral
   *   para edición de secciones de configuración.
   */
  Drupal.behaviors.comercioMerchantSlidePanel = {
    attach: function (context) {
      var panelBtns = context.querySelectorAll('[data-slide-panel]');
      if (panelBtns.length === 0) return;

      panelBtns.forEach(function (btn) {
        if (btn.dataset.comercioInit) return;
        btn.dataset.comercioInit = 'true';

        btn.addEventListener('click', function () {
          var panelId = this.dataset.slidePanel;
          var panel = document.getElementById(panelId);
          if (panel) {
            panel.classList.toggle('slide-panel--open');
          }
        });
      });
    }
  };

})(Drupal);

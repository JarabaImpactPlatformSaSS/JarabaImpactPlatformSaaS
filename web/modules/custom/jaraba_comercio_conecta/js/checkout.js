/**
 * @file
 * ComercioConecta — Checkout JavaScript.
 *
 * Estructura: Comportamientos Drupal para el flujo de checkout.
 * Lógica: Gestiona actualización de cantidades, aplicación de cupones,
 *   eliminación de items y procesamiento de pago via API.
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
   * Comportamiento: Actualización de cantidades en checkout.
   *
   * Lógica: Al cambiar la cantidad de un item, llama a la API de carrito
   *   para actualizar la cantidad y refresca los totales.
   */
  Drupal.behaviors.comercioCheckoutQuantity = {
    attach: function (context) {
      var qtyInputs = context.querySelectorAll('.comercio-checkout__qty-input');
      if (qtyInputs.length === 0) return;

      qtyInputs.forEach(function (input) {
        if (input.dataset.comercioInit) return;
        input.dataset.comercioInit = 'true';

        var debounceTimer;
        input.addEventListener('change', function () {
          var itemId = this.dataset.itemId;
          var quantity = parseInt(this.value, 10);

          if (isNaN(quantity) || quantity < 1) {
            this.value = 1;
            quantity = 1;
          }

          clearTimeout(debounceTimer);
          debounceTimer = setTimeout(function () {
            _comercioUpdateCartItem(itemId, quantity);
          }, 300);
        });
      });
    }
  };

  /**
   * Comportamiento: Aplicación de cupón de descuento.
   *
   * Lógica: Al pulsar el botón de aplicar cupón, envía el código a la API
   *   y muestra mensaje de éxito o error.
   */
  Drupal.behaviors.comercioCheckoutCoupon = {
    attach: function (context) {
      var couponBtn = context.querySelector('.comercio-checkout__coupon-btn');
      if (!couponBtn || couponBtn.dataset.comercioInit) return;
      couponBtn.dataset.comercioInit = 'true';

      couponBtn.addEventListener('click', function () {
        var couponInput = context.querySelector('.comercio-checkout__coupon-input');
        if (!couponInput) return;

        var code = couponInput.value.trim();
        if (!code) {
          _comercioShowMessage(Drupal.t('Introduce un código de cupón.'), 'warning');
          return;
        }

        couponBtn.disabled = true;
        couponBtn.textContent = Drupal.t('Aplicando...');

        getCsrfToken().then(function (token) {
          fetch('/api/v1/comercio/cart/coupon', { // AUDIT-CONS-N07: Added API versioning prefix.
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
              'X-CSRF-Token': token,
            },
            body: JSON.stringify({ code: code }),
          })
            .then(function (response) { return response.json(); })
            .then(function (result) {
              if (result.data && result.data.success) {
                _comercioShowMessage(Drupal.t('Cupón aplicado correctamente.'), 'success');
                window.location.reload();
              } else {
                var msg = (result.meta && result.meta.message) || Drupal.t('Cupón no válido.');
                _comercioShowMessage(msg, 'error');
              }
            })
            .catch(function () {
              _comercioShowMessage(Drupal.t('Error al aplicar el cupón. Inténtalo de nuevo.'), 'error');
            })
            .finally(function () {
              couponBtn.disabled = false;
              couponBtn.textContent = Drupal.t('Aplicar');
            });
        });
      });
    }
  };

  /**
   * Comportamiento: Botón de pago.
   *
   * Lógica: Al pulsar el botón de pagar, envía una solicitud de pago
   *   a la API. Si el pago es exitoso, redirige a la confirmación.
   *   Si falla, muestra un mensaje de error.
   */
  Drupal.behaviors.comercioCheckoutPay = {
    attach: function (context) {
      var payBtn = context.querySelector('.comercio-checkout__pay-btn');
      if (!payBtn || payBtn.dataset.comercioInit) return;
      payBtn.dataset.comercioInit = 'true';

      payBtn.addEventListener('click', function () {
        payBtn.disabled = true;
        var originalText = payBtn.textContent;
        payBtn.textContent = Drupal.t('Procesando pago...');
        payBtn.classList.add('comercio-checkout__pay-btn--loading');

        getCsrfToken().then(function (token) {
          fetch('/checkout/procesar', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
              'X-CSRF-Token': token,
            },
            body: JSON.stringify({
              payment_method: 'stripe',
            }),
          })
            .then(function (response) { return response.json(); })
            .then(function (result) {
              if (result.data && result.data.order_id) {
                window.location.href = '/checkout/confirmacion/' + result.data.order_id;
              } else {
                var msg = (result.meta && result.meta.message) || Drupal.t('Error procesando el pago.');
                _comercioShowMessage(msg, 'error');
                payBtn.disabled = false;
                payBtn.textContent = originalText;
                payBtn.classList.remove('comercio-checkout__pay-btn--loading');
              }
            })
            .catch(function () {
              _comercioShowMessage(Drupal.t('Error de conexión. Inténtalo de nuevo.'), 'error');
              payBtn.disabled = false;
              payBtn.textContent = originalText;
              payBtn.classList.remove('comercio-checkout__pay-btn--loading');
            });
        });
      });
    }
  };

  /**
   * Actualiza la cantidad de un item en el carrito via API.
   *
   * @param {string} itemId - ID del item de carrito.
   * @param {number} quantity - Nueva cantidad.
   */
  function _comercioUpdateCartItem(itemId, quantity) {
    getCsrfToken().then(function (token) {
      fetch('/api/v1/comercio/cart/update/' + itemId, { // AUDIT-CONS-N07: Added API versioning prefix.
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-Token': token,
        },
        body: JSON.stringify({ quantity: quantity }),
      })
        .then(function (response) { return response.json(); })
        .then(function (result) {
          if (result.data && result.data.success) {
            window.location.reload();
          } else {
            _comercioShowMessage(Drupal.t('Error actualizando cantidad.'), 'error');
          }
        })
        .catch(function () {
          _comercioShowMessage(Drupal.t('Error de conexión.'), 'error');
        });
    });
  }

  /**
   * Muestra un mensaje temporal en la zona de checkout.
   *
   * @param {string} message - Texto del mensaje.
   * @param {string} type - Tipo: 'success', 'error', 'warning'.
   */
  function _comercioShowMessage(message, type) {
    var existing = document.querySelector('.comercio-checkout__message');
    if (existing) {
      existing.remove();
    }

    var msgEl = document.createElement('div');
    msgEl.className = 'comercio-checkout__message comercio-checkout__message--' + type;
    msgEl.setAttribute('role', 'alert');
    msgEl.textContent = message;

    var checkout = document.querySelector('.comercio-checkout');
    if (checkout) {
      checkout.insertBefore(msgEl, checkout.firstChild);
    }

    setTimeout(function () {
      if (msgEl.parentNode) {
        msgEl.classList.add('comercio-checkout__message--fade');
        setTimeout(function () { msgEl.remove(); }, 300);
      }
    }, 5000);
  }

})(Drupal);

/**
 * @file
 * ComercioConecta — Checkout JavaScript con Stripe real.
 *
 * Estructura: Comportamientos Drupal para el flujo de checkout marketplace.
 * Logica: Gestiona actualizacion de cantidades, aplicacion de cupones,
 *   creacion de PaymentIntent en el servidor y confirmacion via Stripe.js.
 *
 * DIRECTRIZ: Todos los textos en Drupal.t() para traducibilidad.
 * ROUTE-LANGPREFIX-001: Todas las URLs via drupalSettings (Url::fromRoute).
 * STRIPE-ENV-UNIFY-001: Clave publica via drupalSettings (no hardcoded).
 */

(function (Drupal, drupalSettings) {
  'use strict';

  // CSRF token cache for POST/PATCH/DELETE requests (CSRF-JS-CACHE-001).
  var _csrfToken = null;
  function getCsrfToken() {
    if (_csrfToken) return Promise.resolve(_csrfToken);
    return fetch('/session/token')
      .then(function (r) { return r.text(); })
      .then(function (token) { _csrfToken = token; return token; });
  }

  // ROUTE-LANGPREFIX-001: URLs resolved server-side via Url::fromRoute()
  // and injected via drupalSettings in CheckoutController::checkoutPage().
  var _urls = (drupalSettings.comercioCheckout || {});
  var _processPaymentUrl = _urls.processPaymentUrl || '/comercio-local/checkout/payment';
  var _confirmationBaseUrl = _urls.confirmationBaseUrl || '/comercio-local/checkout/confirmacion/__ORDER_ID__';
  var _couponUrl = _urls.couponUrl || '/api/v1/comercio/cart/coupon';
  var _cartUpdateBaseUrl = _urls.cartUpdateBaseUrl || '/api/v1/comercio/cart/update/';
  var _stripePublicKey = _urls.stripePublicKey || '';

  // Stripe instance (lazy-initialized).
  var _stripe = null;

  function getStripe() {
    if (_stripe) return _stripe;
    if (_stripePublicKey && typeof Stripe !== 'undefined') {
      _stripe = Stripe(_stripePublicKey);
    }
    return _stripe;
  }

  /**
   * Comportamiento: Actualizacion de cantidades en checkout.
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
   * Comportamiento: Aplicacion de cupon de descuento.
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
          _comercioShowMessage(Drupal.t('Introduce un codigo de cupon.'), 'warning');
          return;
        }

        couponBtn.disabled = true;
        couponBtn.textContent = Drupal.t('Aplicando...');

        getCsrfToken().then(function (token) {
          fetch(_couponUrl, {
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
                _comercioShowMessage(Drupal.t('Cupon aplicado correctamente.'), 'success');
                window.location.reload();
              } else {
                var msg = (result.meta && result.meta.message) || Drupal.t('Cupon no valido.');
                _comercioShowMessage(msg, 'error');
              }
            })
            .catch(function () {
              _comercioShowMessage(Drupal.t('Error al aplicar el cupon. Intentalo de nuevo.'), 'error');
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
   * Comportamiento: Boton de pago con Stripe real.
   *
   * Flujo:
   * 1. POST al servidor para crear pedido + PaymentIntent
   * 2. Servidor devuelve client_secret
   * 3. stripe.confirmCardPayment() con el client_secret
   * 4. Redirigir a confirmacion si OK
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
          // Paso 1: Crear pedido + PaymentIntent en el servidor
          fetch(_processPaymentUrl, {
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
              if (!result.data || !result.data.client_secret) {
                var msg = (result.error && result.error.message) || Drupal.t('Error procesando el pago.');
                _comercioShowMessage(msg, 'error');
                payBtn.disabled = false;
                payBtn.textContent = originalText;
                payBtn.classList.remove('comercio-checkout__pay-btn--loading');
                return;
              }

              var stripe = getStripe();
              if (!stripe) {
                // Sin Stripe.js (simulacion en dev): redirigir directamente
                window.location.href = _confirmationBaseUrl.replace('__ORDER_ID__', result.data.order_id);
                return;
              }

              // Paso 2: Confirmar pago con Stripe.js
              // confirmPayment() soporta TODOS los métodos de pago
              // (tarjeta, Apple Pay, Google Pay, Link, SEPA, Bizum via Stripe).
              // Migrado de confirmCardPayment() para automatic_payment_methods.
              payBtn.textContent = Drupal.t('Confirmando con banco...');

              stripe.confirmPayment({
                clientSecret: result.data.client_secret,
                confirmParams: {
                  return_url: _confirmationBaseUrl.replace('__ORDER_ID__', result.data.order_id),
                },
                redirect: 'if_required',
              }).then(function (stripeResult) {
                if (stripeResult.error) {
                  _comercioShowMessage(stripeResult.error.message, 'error');
                  payBtn.disabled = false;
                  payBtn.textContent = originalText;
                  payBtn.classList.remove('comercio-checkout__pay-btn--loading');
                } else if (stripeResult.paymentIntent && stripeResult.paymentIntent.status === 'succeeded') {
                  // Paso 3: Exito - redirigir a confirmacion
                  payBtn.textContent = Drupal.t('Pago completado!');
                  _comercioShowMessage(Drupal.t('Pago procesado correctamente.'), 'success');
                  setTimeout(function () {
                    window.location.href = _confirmationBaseUrl.replace('__ORDER_ID__', result.data.order_id);
                  }, 1000);
                } else {
                  _comercioShowMessage(Drupal.t('El pago requiere verificacion adicional.'), 'warning');
                  payBtn.disabled = false;
                  payBtn.textContent = originalText;
                  payBtn.classList.remove('comercio-checkout__pay-btn--loading');
                }
              });
            })
            .catch(function () {
              _comercioShowMessage(Drupal.t('Error de conexion. Intentalo de nuevo.'), 'error');
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
   */
  function _comercioUpdateCartItem(itemId, quantity) {
    getCsrfToken().then(function (token) {
      fetch(_cartUpdateBaseUrl + itemId, {
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
          _comercioShowMessage(Drupal.t('Error de conexion.'), 'error');
        });
    });
  }

  /**
   * Muestra un mensaje temporal en la zona de checkout.
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

})(Drupal, drupalSettings);

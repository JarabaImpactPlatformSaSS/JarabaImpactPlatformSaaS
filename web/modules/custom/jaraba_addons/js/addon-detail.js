/**
 * @file
 * Jaraba Addons - Detail page behavior.
 *
 * ESTRUCTURA:
 * Drupal behavior que gestiona la interactividad de la página de detalle
 * de un add-on. Implementa el toggle de precios (mensual/anual) y el
 * handler del botón de suscripción que hace POST a la API REST.
 *
 * LÓGICA:
 * - Pricing toggle: Alterna entre precios mensual/anual actualizando
 *   el DOM (precio, periodo) y el data-billing-cycle del botón de
 *   suscripción.
 * - Subscribe: Hace POST a /api/v1/addons/{addon_id}/subscribe con
 *   el billing_cycle seleccionado. Muestra feedback visual.
 *
 * RELACIONES:
 * - addon-detail.js <- jaraba_addons.libraries.yml (registrado en)
 * - addon-detail.js -> addons-detail.html.twig (interactúa con)
 * - addon-detail.js -> AddonApiController::subscribe() (API call)
 * - addon-detail.js -> _detail.scss (clases CSS esperadas)
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Behavior: Detalle de add-on.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.jarabaAddonsDetail = {
    attach: function (context) {

      // -----------------------------------------------------------------
      // Pricing toggle: Mensual / Anual.
      // -----------------------------------------------------------------
      var toggleBtns = once('addons-pricing-toggle', '.ej-addons-detail__toggle-btn', context);

      toggleBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
          var cycle = btn.getAttribute('data-cycle');
          var container = btn.closest('.ej-addons-detail');
          if (!container) {
            return;
          }

          // Actualizar estado de botones toggle.
          var allToggles = container.querySelectorAll('.ej-addons-detail__toggle-btn');
          allToggles.forEach(function (t) {
            t.classList.remove('ej-addons-detail__toggle-btn--active');
            t.setAttribute('aria-checked', 'false');
          });
          btn.classList.add('ej-addons-detail__toggle-btn--active');
          btn.setAttribute('aria-checked', 'true');

          // Actualizar precio mostrado.
          var priceEl = container.querySelector('.ej-addons-detail__price-amount');
          var periodEl = container.querySelector('.ej-addons-detail__price-period');

          if (priceEl) {
            var price;
            if (cycle === 'yearly') {
              price = parseFloat(priceEl.getAttribute('data-price-yearly')) || 0;
            }
            else {
              price = parseFloat(priceEl.getAttribute('data-price-monthly')) || 0;
            }
            priceEl.textContent = formatPrice(price) + ' \u20AC';
          }

          if (periodEl) {
            if (cycle === 'yearly') {
              periodEl.textContent = periodEl.getAttribute('data-period-yearly') || '/year';
            }
            else {
              periodEl.textContent = periodEl.getAttribute('data-period-monthly') || '/month';
            }
          }

          // Actualizar billing_cycle en el botón de suscripción.
          var subscribeBtn = container.querySelector('.ej-addons-detail__subscribe-btn');
          if (subscribeBtn) {
            subscribeBtn.setAttribute('data-billing-cycle', cycle);
          }
        });
      });

      // -----------------------------------------------------------------
      // Subscribe button: POST a la API.
      // -----------------------------------------------------------------
      var subscribeBtns = once('addons-subscribe-btn', '.ej-addons-detail__subscribe-btn', context);

      subscribeBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
          var addonId = btn.getAttribute('data-addon-id');
          var billingCycle = btn.getAttribute('data-billing-cycle') || 'monthly';

          if (!addonId) {
            return;
          }

          // Deshabilitar botón durante la petición.
          btn.disabled = true;
          var originalText = btn.textContent;
          btn.textContent = Drupal.t('Processing...');

          var url = Drupal.url('api/v1/addons/' + addonId + '/subscribe');

          fetch(url, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
              billing_cycle: billingCycle
            })
          })
            .then(function (response) {
              return response.json().then(function (data) {
                return { status: response.status, body: data };
              });
            })
            .then(function (result) {
              if (result.status >= 200 && result.status < 300) {
                // Suscripción exitosa: reemplazar botón por aviso.
                var ctaSection = btn.closest('.ej-addons-detail__cta-section');
                if (ctaSection) {
                  ctaSection.innerHTML =
                    '<div class="ej-addons-detail__subscribed-notice">' +
                    '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
                    '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>' +
                    '<polyline points="22 4 12 14.01 9 11.01"/>' +
                    '</svg>' +
                    '<span>' + Drupal.t('Successfully subscribed! You now have access to this add-on.') + '</span>' +
                    '</div>';
                }
              }
              else {
                // Error: mostrar mensaje del servidor.
                var errorMsg = Drupal.t('An error occurred. Please try again.');
                if (result.body && result.body.errors && result.body.errors.length > 0) {
                  errorMsg = result.body.errors[0].message || errorMsg;
                }
                btn.disabled = false;
                btn.textContent = originalText;
                alert(errorMsg);
              }
            })
            .catch(function () {
              btn.disabled = false;
              btn.textContent = originalText;
              alert(Drupal.t('A network error occurred. Please check your connection and try again.'));
            });
        });
      });

      // -----------------------------------------------------------------
      // Helper: Formatear precio con comas y 2 decimales (EU format).
      // -----------------------------------------------------------------
      function formatPrice(value) {
        return value
          .toFixed(2)
          .replace('.', ',')
          .replace(/\B(?=(\d{3})+(?!\d))/g, '.');
      }

    }
  };

})(Drupal, once);

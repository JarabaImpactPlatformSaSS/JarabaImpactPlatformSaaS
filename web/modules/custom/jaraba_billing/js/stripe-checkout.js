/**
 * @file
 * Stripe Embedded Checkout behavior.
 *
 * STRIPE-CHECKOUT-001 §5.4: Carga Stripe.js desde CDN (PCI compliance),
 * crea una Checkout Session via API, y monta el formulario embebido.
 *
 * Convenciones:
 * - ROUTE-LANGPREFIX-001: sessionUrl via drupalSettings (no hardcoded).
 * - CSRF-JS-CACHE-001: Token de /session/token cacheado.
 * - INNERHTML-XSS-001: Drupal.checkPlain() para datos de API.
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * CSRF token cache (CSRF-JS-CACHE-001).
   *
   * @type {string|null}
   */
  var csrfToken = null;

  /**
   * Obtiene el CSRF token, cacheandolo para reutilizar.
   *
   * @return {Promise<string>}
   */
  function getCsrfToken() {
    if (csrfToken) {
      return Promise.resolve(csrfToken);
    }
    return fetch(Drupal.url('session/token'), {
      credentials: 'same-origin',
    })
      .then(function (response) {
        return response.text();
      })
      .then(function (token) {
        csrfToken = token;
        return token;
      });
  }

  /**
   * Carga Stripe.js dinámicamente desde CDN (PCI compliance).
   *
   * @param {string} publicKey
   *   La clave publica de Stripe.
   * @return {Promise<object>}
   *   Instancia de Stripe.
   */
  function loadStripe(publicKey) {
    return new Promise(function (resolve, reject) {
      if (window.Stripe) {
        resolve(window.Stripe(publicKey));
        return;
      }

      var script = document.createElement('script');
      script.src = 'https://js.stripe.com/v3/';
      script.async = true;
      script.onload = function () {
        if (window.Stripe) {
          resolve(window.Stripe(publicKey));
        }
        else {
          reject(new Error('Stripe.js loaded but Stripe object not available.'));
        }
      };
      script.onerror = function () {
        reject(new Error('Failed to load Stripe.js from CDN.'));
      };
      document.head.appendChild(script);
    });
  }

  /**
   * Muestra un mensaje de error en el contenedor de checkout.
   *
   * @param {HTMLElement} container
   *   El contenedor del checkout.
   * @param {string} message
   *   Mensaje de error (ya sanitizado o via Drupal.t).
   */
  function showError(container, message) {
    var errorEl = container.querySelector('.checkout-error');
    if (!errorEl) {
      errorEl = document.createElement('div');
      errorEl.className = 'checkout-error';
      errorEl.setAttribute('role', 'alert');
      container.prepend(errorEl);
    }
    errorEl.textContent = message;
    errorEl.style.display = 'block';
  }

  /**
   * Oculta el mensaje de error.
   *
   * @param {HTMLElement} container
   */
  function hideError(container) {
    var errorEl = container.querySelector('.checkout-error');
    if (errorEl) {
      errorEl.style.display = 'none';
    }
  }

  /**
   * Muestra/oculta el spinner de carga.
   *
   * @param {HTMLElement} container
   * @param {boolean} show
   */
  function toggleLoading(container, show) {
    var spinner = container.querySelector('.checkout-loading');
    var mountPoint = container.querySelector('#stripe-checkout-mount');
    if (spinner) {
      spinner.style.display = show ? 'flex' : 'none';
    }
    if (mountPoint) {
      mountPoint.style.display = show ? 'none' : 'block';
    }
  }

  /**
   * Behavior principal de Stripe Checkout.
   */
  Drupal.behaviors.stripeCheckout = {
    attach: function (context) {
      var containers = once('stripe-checkout', '#checkout-container', context);
      if (!containers.length) {
        return;
      }

      var container = containers[0];
      var settings = drupalSettings.stripeCheckout || {};

      if (!settings.publicKey || !settings.sessionUrl || !settings.planId) {
        showError(container, Drupal.t('Configuracion de checkout incompleta. Recarga la pagina.'));
        return;
      }

      // El formulario de datos del cliente.
      var form = container.querySelector('#checkout-customer-form');
      if (!form) {
        // Si no hay formulario (usuario ya autenticado), iniciar checkout directo.
        this.initCheckout(container, settings, {});
        return;
      }

      var self = this;
      form.addEventListener('submit', function (e) {
        e.preventDefault();

        var emailInput = form.querySelector('[name="email"]');
        var businessInput = form.querySelector('[name="business_name"]');

        if (!emailInput || !emailInput.value.trim()) {
          showError(container, Drupal.t('El email es obligatorio.'));
          return;
        }
        if (!businessInput || !businessInput.value.trim()) {
          showError(container, Drupal.t('El nombre de empresa es obligatorio.'));
          return;
        }

        // Validacion basica de email.
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(emailInput.value.trim())) {
          showError(container, Drupal.t('Introduce un email valido.'));
          return;
        }

        hideError(container);

        // Deshabilitar boton submit.
        var submitBtn = form.querySelector('[type="submit"]');
        if (submitBtn) {
          submitBtn.disabled = true;
          submitBtn.textContent = Drupal.t('Procesando...');
        }

        self.initCheckout(container, settings, {
          email: emailInput.value.trim(),
          businessName: businessInput.value.trim(),
        });
      });
    },

    /**
     * Inicia el flujo de Stripe Embedded Checkout.
     *
     * @param {HTMLElement} container
     * @param {object} settings
     * @param {object} customerData
     */
    initCheckout: function (container, settings, customerData) {
      toggleLoading(container, true);

      Promise.all([
        loadStripe(settings.publicKey),
        getCsrfToken(),
      ])
        .then(function (results) {
          var stripe = results[0];
          var token = results[1];

          var body = {
            planId: settings.planId,
            cycle: settings.cycle || 'monthly',
            email: customerData.email || '',
            businessName: customerData.businessName || '',
          };

          return fetch(settings.sessionUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-Token': token,
            },
            body: JSON.stringify(body),
          })
            .then(function (response) {
              return response.json();
            })
            .then(function (data) {
              if (data.error) {
                throw new Error(data.error);
              }
              return stripe.initEmbeddedCheckout({
                clientSecret: data.clientSecret,
              });
            })
            .then(function (checkout) {
              toggleLoading(container, false);

              // Ocultar el formulario de datos del cliente.
              var form = container.querySelector('#checkout-customer-form');
              if (form) {
                form.style.display = 'none';
              }

              // Montar el checkout embebido.
              var mountPoint = container.querySelector('#stripe-checkout-mount');
              if (mountPoint) {
                checkout.mount(mountPoint);
              }
            });
        })
        .catch(function (error) {
          toggleLoading(container, false);
          showError(container, error.message || Drupal.t('Error al iniciar el checkout. Intentalo de nuevo.'));

          // Re-habilitar boton submit.
          var submitBtn = container.querySelector('[type="submit"]');
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = Drupal.t('Continuar al pago');
          }
        });
    },
  };

})(Drupal, drupalSettings, once);

/**
 * @file
 * stripe-portal.js — Stripe Customer Portal redirect.
 *
 * Conecta el boton [data-portal-trigger] con el endpoint
 * /api/v1/billing/portal-session para abrir el portal de Stripe
 * donde el usuario puede gestionar su suscripcion (cancel, upgrade,
 * metodo de pago).
 *
 * Directrices:
 * - CSRF-API-001: Token de /session/token cacheado.
 * - ROUTE-LANGPREFIX-001: returnUrl via data attribute (generado con path()).
 * - Drupal.t() para traducciones JS.
 * - Vanilla JS + Drupal.behaviors (NO React/Vue/Angular).
 */
(function (Drupal, once) {

  'use strict';

  Drupal.behaviors.stripePortal = {
    attach: function (context) {
      var buttons = once('stripe-portal', '[data-portal-trigger]', context);

      buttons.forEach(function (button) {
        button.addEventListener('click', function () {
          var btn = this;
          var returnUrl = btn.getAttribute('data-portal-return-url') || '/';

          // Estado loading.
          btn.disabled = true;
          btn.classList.add('is-loading');
          var originalText = btn.textContent;
          btn.textContent = Drupal.t('Redirigiendo...');

          // Obtener CSRF token (CSRF-JS-CACHE-001).
          fetch('/session/token')
            .then(function (res) { return res.text(); })
            .then(function (csrfToken) {
              return fetch('/api/v1/billing/portal-session', {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json',
                  'X-CSRF-Token': csrfToken,
                },
                body: JSON.stringify({
                  return_url: window.location.origin + returnUrl,
                }),
              });
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
              if (data.success && data.data && data.data.url) {
                window.location.href = data.data.url;
              }
              else {
                btn.disabled = false;
                btn.classList.remove('is-loading');
                btn.textContent = originalText;
                Drupal.announce(
                  Drupal.t('No se pudo abrir el portal de pagos. Inténtalo de nuevo.'),
                  'assertive'
                );
              }
            })
            .catch(function () {
              btn.disabled = false;
              btn.classList.remove('is-loading');
              btn.textContent = originalText;
              Drupal.announce(
                Drupal.t('Error de conexión. Inténtalo de nuevo.'),
                'assertive'
              );
            });
        });
      });
    },
  };

})(Drupal, once);

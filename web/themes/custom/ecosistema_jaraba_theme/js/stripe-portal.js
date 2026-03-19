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

  /**
   * Muestra un mensaje de error visible debajo del boton del portal.
   *
   * Crea un elemento <p> con estilo de alerta que desaparece tras 8s.
   * Tambien usa Drupal.announce() para screen readers (WCAG).
   */
  function showPortalError(btn, message) {
    // Eliminar error anterior si existe.
    var prev = btn.parentElement.querySelector('.subscription-card__portal-error');
    if (prev) {
      prev.remove();
    }

    var errorEl = document.createElement('p');
    errorEl.className = 'subscription-card__portal-error';
    errorEl.textContent = message;
    errorEl.style.cssText = 'color: var(--ej-color-danger, #EF4444); font-size: 0.8125rem; margin: 0.5rem 0 0; padding: 0.5rem 0.75rem; background: color-mix(in srgb, var(--ej-color-danger, #EF4444) 8%, transparent); border-radius: 6px;';
    btn.parentElement.appendChild(errorEl);

    Drupal.announce(message, 'assertive');

    setTimeout(function () {
      if (errorEl.parentElement) {
        errorEl.remove();
      }
    }, 8000);
  }

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
            .then(function (res) {
              if (!res.ok) {
                throw new Error('HTTP ' + res.status);
              }
              return res.json();
            })
            .then(function (data) {
              if (data.success && data.data && data.data.url) {
                window.location.href = data.data.url;
              }
              else {
                btn.disabled = false;
                btn.classList.remove('is-loading');
                btn.textContent = originalText;
                var msg = Drupal.t('El portal de pagos no está disponible en este momento. Contacta con soporte si el problema persiste.');
                showPortalError(btn, msg);
              }
            })
            .catch(function () {
              btn.disabled = false;
              btn.classList.remove('is-loading');
              btn.textContent = originalText;
              var msg = Drupal.t('No se pudo conectar con el servicio de pagos. Inténtalo de nuevo.');
              showPortalError(btn, msg);
            });
        });
      });
    },
  };

})(Drupal, once);

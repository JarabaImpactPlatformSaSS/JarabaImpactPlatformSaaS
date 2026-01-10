/**
 * @file
 * JavaScript para integración del Stripe Customer Portal.
 *
 * Este script maneja los botones de "Gestionar Suscripción" en el dashboard
 * del tenant, creando una sesión del portal de clientes y redirigiendo
 * al usuario.
 *
 * FLUJO:
 * 1. Usuario hace click en el botón
 * 2. Se envía POST a /api/stripe/portal-session
 * 3. Backend crea sesión en Stripe y devuelve URL
 * 4. Se redirige al usuario al portal de Stripe
 * 5. Al terminar, Stripe redirige de vuelta al dashboard
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Comportamiento Drupal para el portal de facturación.
   */
  Drupal.behaviors.ecosistemaBillingPortal = {
    attach: function (context, settings) {
      // Buscar todos los botones con el atributo data-billing-portal
      const buttons = once('billing-portal', '[data-billing-portal]', context);

      buttons.forEach(function (button) {
        button.addEventListener('click', async function (e) {
          e.preventDefault();

          // Mostrar estado de carga
          const originalText = button.innerHTML;
          button.innerHTML = '<span class="billing-loading">Cargando...</span>';
          button.setAttribute('disabled', 'disabled');

          try {
            // Obtener URL de retorno del atributo o usar la actual
            const returnUrl = button.getAttribute('data-return-url') ||
              window.location.pathname;

            // Crear sesión del portal
            const response = await fetch('/api/stripe/portal-session', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
              },
              credentials: 'same-origin',
              body: JSON.stringify({
                return_url: window.location.origin + returnUrl,
              }),
            });

            const data = await response.json();

            if (data.success && data.url) {
              // Redirigir al portal de Stripe
              window.location.href = data.url;
            } else {
              // Mostrar error
              Drupal.ecosistemaBillingPortal.showError(
                button,
                data.error || 'No se pudo acceder al portal de pagos.'
              );
              // Restaurar botón
              button.innerHTML = originalText;
              button.removeAttribute('disabled');
            }

          } catch (error) {
            console.error('Error accessing billing portal:', error);
            Drupal.ecosistemaBillingPortal.showError(
              button,
              'Error de conexión. Por favor, inténtalo de nuevo.'
            );
            // Restaurar botón
            button.innerHTML = originalText;
            button.removeAttribute('disabled');
          }
        });
      });
    },
  };

  /**
   * Objeto con utilidades del módulo de billing.
   */
  Drupal.ecosistemaBillingPortal = Drupal.ecosistemaBillingPortal || {};

  /**
   * Muestra un mensaje de error cerca del botón.
   *
   * @param {Element} button
   *   El elemento botón.
   * @param {string} message
   *   El mensaje de error a mostrar.
   */
  Drupal.ecosistemaBillingPortal.showError = function (button, message) {
    // Buscar o crear contenedor de errores
    let errorContainer = button.parentElement.querySelector('.billing-error');

    if (!errorContainer) {
      errorContainer = document.createElement('div');
      errorContainer.className = 'billing-error messages messages--error';
      button.parentElement.appendChild(errorContainer);
    }

    errorContainer.textContent = message;
    errorContainer.style.display = 'block';

    // Ocultar después de 5 segundos
    setTimeout(function () {
      errorContainer.style.display = 'none';
    }, 5000);
  };

})(Drupal, drupalSettings, once);

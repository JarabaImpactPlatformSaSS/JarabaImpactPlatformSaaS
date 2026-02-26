/**
 * @file
 * lead-magnet.js — Drupal behavior para el formulario de Lead Magnet.
 *
 * PROPÓSITO:
 * Captura email + avatar_type desde el formulario de Lead Magnet y envía
 * un POST a /api/v1/public/subscribe. Maneja estados de loading, error,
 * y éxito con transiciones suaves.
 *
 * DIRECTRICES CUMPLIDAS:
 * - Drupal.behaviors pattern (no scripts sueltos)
 * - once() para evitar doble attach
 * - Drupal.t() para mensajes traducibles
 * - CSRF token desde /session/token (core)
 * - No dependencias externas (vanilla JS fetch)
 *
 * @see templates/partials/_lead-magnet.html.twig
 * @see \Drupal\jaraba_email\Controller\PublicSubscribeController
 */
(function (Drupal, once) {
  'use strict';

  /**
   * Token CSRF cacheado para evitar múltiples requests.
   * @type {string|null}
   */
  let cachedCsrfToken = null;

  /**
   * Obtiene el token CSRF de Drupal.
   *
   * @returns {Promise<string>} Token CSRF.
   */
  async function getCsrfToken() {
    if (cachedCsrfToken) {
      return cachedCsrfToken;
    }
    const response = await fetch('/session/token');
    if (!response.ok) {
      throw new Error('Failed to fetch CSRF token');
    }
    cachedCsrfToken = await response.text();
    return cachedCsrfToken;
  }

  /**
   * Behavior principal del formulario de Lead Magnet.
   *
   * Se adjunta a cualquier elemento con [data-lead-magnet-form].
   * Maneja validación client-side, submit asíncrono, y transiciones
   * entre estados (form → loading → success/error).
   */
  Drupal.behaviors.jarabaLeadMagnet = {
    attach(context) {
      once('lead-magnet', '[data-lead-magnet-form]', context).forEach(
        function (form) {
          var btnSubmit = form.querySelector('[data-lead-magnet-submit]');
          var inputEmail = form.querySelector('[data-lead-magnet-email]');
          var selectAvatar = form.querySelector('[data-lead-magnet-avatar]');
          var checkGdpr = form.querySelector('[data-lead-magnet-gdpr]');
          var successEl = form
            .closest('.lead-magnet')
            .querySelector('[data-lead-magnet-success]');
          var tenantId = parseInt(form.dataset.tenantId, 10) || 5;
          var originalBtnText = btnSubmit ? btnSubmit.textContent : '';

          /**
           * Actualiza el estado visual del botón.
           *
           * @param {string} state - 'loading' | 'error' | 'reset'
           * @param {string} [message] - Mensaje a mostrar.
           */
          function setBtnState(state, message) {
            if (!btnSubmit) return;
            switch (state) {
              case 'loading':
                btnSubmit.disabled = true;
                btnSubmit.classList.add('lead-magnet__btn--loading');
                btnSubmit.textContent = message || Drupal.t('Enviando…');
                break;
              case 'error':
                btnSubmit.disabled = false;
                btnSubmit.classList.remove('lead-magnet__btn--loading');
                btnSubmit.classList.add('lead-magnet__btn--error');
                btnSubmit.textContent =
                  message || Drupal.t('Error. Inténtalo de nuevo.');
                // Reset a original tras 3 segundos.
                setTimeout(function () {
                  btnSubmit.classList.remove('lead-magnet__btn--error');
                  btnSubmit.textContent = originalBtnText;
                }, 3000);
                break;
              case 'reset':
                btnSubmit.disabled = false;
                btnSubmit.classList.remove(
                  'lead-magnet__btn--loading',
                  'lead-magnet__btn--error'
                );
                btnSubmit.textContent = originalBtnText;
                break;
            }
          }

          /**
           * Muestra la sección de éxito con animación.
           */
          function showSuccess() {
            form.style.opacity = '0';
            form.style.transform = 'translateY(-10px)';
            setTimeout(function () {
              form.hidden = true;
              if (successEl) {
                successEl.hidden = false;
                successEl.style.opacity = '0';
                successEl.style.transform = 'translateY(10px)';
                // Forzar reflow para que la transición se aplique.
                void successEl.offsetHeight;
                successEl.style.transition =
                  'opacity 0.4s ease, transform 0.4s ease';
                successEl.style.opacity = '1';
                successEl.style.transform = 'translateY(0)';
              }
            }, 300);
          }

          // ─── Event Listener ─────────────────────────────────────────
          form.addEventListener('submit', async function (e) {
            e.preventDefault();

            var email = inputEmail ? inputEmail.value.trim() : '';
            var avatar = selectAvatar ? selectAvatar.value : '';
            var gdprChecked = checkGdpr ? checkGdpr.checked : false;

            // Validación client-side.
            if (!email || !avatar || !gdprChecked) {
              // Resaltar campos vacíos.
              if (!email && inputEmail) {
                inputEmail.classList.add('lead-magnet__input--error');
              }
              if (!avatar && selectAvatar) {
                selectAvatar.classList.add('lead-magnet__select--error');
              }
              return;
            }

            // Limpiar posibles clases de error.
            if (inputEmail)
              inputEmail.classList.remove('lead-magnet__input--error');
            if (selectAvatar)
              selectAvatar.classList.remove('lead-magnet__select--error');

            setBtnState('loading');

            try {
              var csrfToken = await getCsrfToken();

              var response = await fetch('/api/v1/public/subscribe', {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json',
                  'X-CSRF-Token': csrfToken,
                },
                body: JSON.stringify({
                  email: email,
                  source: 'kit_impulso_digital',
                  tags: [avatar],
                  tenant_id: tenantId,
                }),
              });

              if (response.ok) {
                showSuccess();
              } else {
                var errorData = {};
                try {
                  errorData = await response.json();
                } catch (_) {
                  // No es JSON, usar mensaje genérico.
                }
                setBtnState(
                  'error',
                  errorData.error || Drupal.t('Error al enviar. Reintenta.')
                );
              }
            } catch (networkError) {
              setBtnState('error', Drupal.t('Error de conexión. Verifica tu internet.'));
            }
          });

          // ─── Limpiar error visual al interactuar ─────────────────────
          if (inputEmail) {
            inputEmail.addEventListener('input', function () {
              inputEmail.classList.remove('lead-magnet__input--error');
            });
          }
          if (selectAvatar) {
            selectAvatar.addEventListener('change', function () {
              selectAvatar.classList.remove('lead-magnet__select--error');
            });
          }
        }
      );
    },
  };
})(Drupal, once);

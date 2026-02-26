/**
 * @file
 * lead-magnet.js — Drupal behavior para Lead Magnet con slide-panel.
 *
 * PROPÓSITO:
 * Captura nombre + email + consentimiento RGPD desde formularios de Lead Magnet
 * dentro de slide-panels. Envía POST a /api/v1/public/subscribe.
 * Maneja estados: form → loading → success/error.
 *
 * DESIGN DECISIONS (Auditoría Meta-Sitio Feb 2026):
 * - Antes: captura solo email + avatar_type
 * - Ahora: captura nombre + email + vertical desde data-attributes
 * - Soporte para múltiples formularios en la misma página
 * - Post-submit: muestra estado de éxito con enlace de descarga
 *
 * DIRECTRICES CUMPLIDAS:
 * - Drupal.behaviors pattern (no scripts sueltos)
 * - once() para evitar doble attach
 * - Drupal.t() para mensajes traducibles
 * - CSRF token desde /session/token (core)
 * - No dependencias externas (vanilla JS fetch)
 *
 * @see templates/partials/_landing-lead-magnet.html.twig
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
   * Compatible con la estructura slide-panel + formulario inline.
   */
  Drupal.behaviors.jarabaLeadMagnet = {
    attach(context) {
      once('lead-magnet', '[data-lead-magnet-form]', context).forEach(
        function (form) {
          const inputName = form.querySelector('input[name="name"]');
          const inputEmail = form.querySelector('input[name="email"]');
          const checkGdpr = form.querySelector('input[name="gdpr_consent"]');
          const btnSubmit = form.querySelector('button[type="submit"]');
          const successEl = form.querySelector('.lead-magnet-form__success');
          const errorEl = form.querySelector('.lead-magnet-form__error');
          const vertical = form.dataset.vertical || '';
          const resourceUrl = form.dataset.resourceUrl || '';
          const originalBtnText = btnSubmit ? btnSubmit.innerHTML : '';

          /**
           * Actualiza el estado visual del botón de submit.
           *
           * @param {string} state - 'loading' | 'error' | 'reset'
           * @param {string} [message] - Mensaje opcional.
           */
          function setBtnState(state, message) {
            if (!btnSubmit) return;
            switch (state) {
              case 'loading':
                btnSubmit.disabled = true;
                btnSubmit.classList.add('lead-magnet-form__submit--loading');
                btnSubmit.textContent = message || Drupal.t('Enviando…');
                break;
              case 'error':
                btnSubmit.disabled = false;
                btnSubmit.classList.remove('lead-magnet-form__submit--loading');
                btnSubmit.innerHTML = originalBtnText;
                if (errorEl) {
                  errorEl.hidden = false;
                  errorEl.querySelector('p').textContent =
                    message || Drupal.t('Error al enviar. Inténtalo de nuevo.');
                }
                setTimeout(function () {
                  if (errorEl) errorEl.hidden = true;
                }, 5000);
                break;
              case 'reset':
                btnSubmit.disabled = false;
                btnSubmit.classList.remove('lead-magnet-form__submit--loading');
                btnSubmit.innerHTML = originalBtnText;
                break;
            }
          }

          /**
           * Muestra la sección de éxito y oculta el formulario.
           */
          function showSuccess() {
            // Hide form fields.
            const fields = form.querySelectorAll(
              '.lead-magnet-form__field, .lead-magnet-form__intro, .lead-magnet-form__submit'
            );
            fields.forEach(function (el) {
              el.style.opacity = '0';
              el.style.transition = 'opacity 0.3s ease';
              setTimeout(function () { el.hidden = true; }, 300);
            });

            // Show success state.
            setTimeout(function () {
              if (successEl) {
                successEl.hidden = false;
                successEl.style.opacity = '0';
                successEl.style.transform = 'translateY(10px)';
                void successEl.offsetHeight;
                successEl.style.transition =
                  'opacity 0.4s ease, transform 0.4s ease';
                successEl.style.opacity = '1';
                successEl.style.transform = 'translateY(0)';
              }
            }, 350);
          }

          // ─── Event Listener ─────────────────────────────────────────
          form.addEventListener('submit', async function (e) {
            e.preventDefault();

            const email = inputEmail ? inputEmail.value.trim() : '';
            const name = inputName ? inputName.value.trim() : '';
            const gdprChecked = checkGdpr ? checkGdpr.checked : false;

            // Validación client-side.
            if (!email || !gdprChecked) {
              if (!email && inputEmail) {
                inputEmail.classList.add('form-email--error');
                inputEmail.focus();
              }
              if (!gdprChecked && checkGdpr) {
                checkGdpr.parentElement.classList.add('lead-magnet-form__gdpr-label--error');
              }
              return;
            }

            // Limpiar errores.
            if (inputEmail) inputEmail.classList.remove('form-email--error');
            if (checkGdpr) {
              checkGdpr.parentElement.classList.remove('lead-magnet-form__gdpr-label--error');
            }
            if (errorEl) errorEl.hidden = true;

            setBtnState('loading');

            try {
              const csrfToken = await getCsrfToken();

              const response = await fetch('/api/v1/public/subscribe', {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json',
                  'X-CSRF-Token': csrfToken,
                },
                body: JSON.stringify({
                  email: email,
                  name: name,
                  source: 'lead_magnet_' + vertical,
                  tags: [vertical, 'lead_magnet'],
                  resource_url: resourceUrl,
                }),
              });

              if (response.ok) {
                showSuccess();
              } else {
                let errorData = {};
                try {
                  errorData = await response.json();
                } catch (_) {
                  // Not JSON, use generic message.
                }
                setBtnState(
                  'error',
                  errorData.error || Drupal.t('Error al enviar. Reintenta.')
                );
              }
            } catch (networkError) {
              setBtnState(
                'error',
                Drupal.t('Error de conexión. Verifica tu internet.')
              );
            }
          });

          // ─── Limpiar error visual al interactuar ─────────────────────
          if (inputEmail) {
            inputEmail.addEventListener('input', function () {
              inputEmail.classList.remove('form-email--error');
            });
          }
        }
      );
    },
  };
})(Drupal, once);

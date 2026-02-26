/**
 * @file contact-form.js
 * Drupal behavior para el formulario de contacto público.
 *
 * Envía datos via fetch() a /api/v1/public/contact con:
 * - CSRF token (cached)
 * - Flood rate limiting del lado del servidor
 * - Honeypot anti-bot
 * - GDPR consent validation
 *
 * DIRECTRICES:
 * - Drupal.behaviors + once()
 * - Drupal.t() para strings traducibles
 * - No jQuery
 */

(function (Drupal, once) {
  'use strict';

  let csrfToken = null;

  Drupal.behaviors.jarabaContactForm = {
    attach: function (context) {
      once('jaraba-contact-form', '#contact-form', context).forEach(function (form) {
        var submitBtn = form.querySelector('#contact-submit');
        var submitText = form.querySelector('.contact-page__submit-text');
        var submitLoading = form.querySelector('.contact-page__submit-loading');
        var successEl = form.querySelector('#contact-success') || document.getElementById('contact-success');
        var errorEl = form.querySelector('#contact-error') || document.getElementById('contact-error');

        form.addEventListener('submit', function (e) {
          e.preventDefault();

          // Honeypot check
          var hp = form.querySelector('input[name="website"]');
          if (hp && hp.value) {
            return;
          }

          // Validate required fields
          var name = form.querySelector('#contact-name');
          var email = form.querySelector('#contact-email');
          var message = form.querySelector('#contact-message');
          var gdpr = form.querySelector('#contact-gdpr');

          if (!name || !name.value.trim()) {
            showError(Drupal.t('Por favor, introduce tu nombre.'));
            name && name.focus();
            return;
          }

          if (!email || !email.value.trim() || !isValidEmail(email.value)) {
            showError(Drupal.t('Por favor, introduce un email válido.'));
            email && email.focus();
            return;
          }

          if (!message || !message.value.trim()) {
            showError(Drupal.t('Por favor, escribe tu mensaje.'));
            message && message.focus();
            return;
          }

          if (!gdpr || !gdpr.checked) {
            showError(Drupal.t('Debes aceptar la política de privacidad.'));
            return;
          }

          // Set loading state
          submitBtn.disabled = true;
          if (submitText) submitText.hidden = true;
          if (submitLoading) submitLoading.hidden = false;
          hideMessages();

          var subject = form.querySelector('#contact-subject');

          var payload = {
            name: name.value.trim(),
            email: email.value.trim(),
            subject: subject ? subject.value : '',
            message: message.value.trim(),
            gdpr_consent: true
          };

          getToken().then(function (token) {
            return fetch('/api/v1/public/contact', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': token
              },
              body: JSON.stringify(payload)
            });
          }).then(function (response) {
            return response.json().then(function (data) {
              return { ok: response.ok, data: data };
            });
          }).then(function (result) {
            if (result.ok) {
              if (successEl) successEl.hidden = false;
              form.reset();
            } else {
              showError(result.data.message || Drupal.t('Error al enviar el mensaje. Inténtalo de nuevo.'));
            }
          }).catch(function () {
            showError(Drupal.t('Error de conexión. Comprueba tu conexión a internet.'));
          }).finally(function () {
            submitBtn.disabled = false;
            if (submitText) submitText.hidden = false;
            if (submitLoading) submitLoading.hidden = true;
          });
        });

        function showError(msg) {
          if (errorEl) {
            errorEl.textContent = msg;
            errorEl.hidden = false;
          }
        }

        function hideMessages() {
          if (successEl) successEl.hidden = true;
          if (errorEl) errorEl.hidden = true;
        }

        function isValidEmail(val) {
          return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);
        }

        function getToken() {
          if (csrfToken) {
            return Promise.resolve(csrfToken);
          }
          return fetch('/session/token')
            .then(function (r) { return r.text(); })
            .then(function (token) {
              csrfToken = token;
              return token;
            });
        }
      });
    }
  };

})(Drupal, once);

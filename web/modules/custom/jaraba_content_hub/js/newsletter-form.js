/**
 * @file
 * Newsletter form AJAX handler.
 *
 * Envía suscripciones al endpoint API con CSRF token (CSRF-API-001).
 * Muestra feedback inline sin recargar la página.
 */
(function (Drupal) {
  'use strict';

  Drupal.behaviors.jarabaNewsletterForm = {
    attach: function (context) {
      const forms = context.querySelectorAll
        ? context.querySelectorAll('[data-newsletter-form]')
        : [];

      forms.forEach(function (form) {
        if (form.dataset.jarabaNewsletter) {
          return;
        }
        form.dataset.jarabaNewsletter = 'attached';

        form.addEventListener('submit', function (e) {
          e.preventDefault();

          const emailInput = form.querySelector('input[type="email"]');
          const button = form.querySelector('button[type="submit"]');
          const messageDiv = form.closest('.sidebar-widget')
            .querySelector('.newsletter-form__message');
          const email = emailInput ? emailInput.value.trim() : '';

          if (!email) {
            return;
          }

          // Deshabilitar durante envío.
          button.disabled = true;
          button.textContent = Drupal.t('Sending...');
          messageDiv.textContent = '';
          messageDiv.className = 'newsletter-form__message';

          // Obtener CSRF token y enviar (CSRF-JS-CACHE-001).
          fetch('/session/token')
            .then(function (res) { return res.text(); })
            .then(function (token) {
              return fetch(form.action, {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json',
                  'X-CSRF-Token': token,
                },
                body: JSON.stringify({ email: email }),
              });
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
              if (data.success) {
                messageDiv.textContent = data.message;
                messageDiv.classList.add('newsletter-form__message--success');
                emailInput.value = '';
              } else {
                messageDiv.textContent = data.message || Drupal.t('An error occurred.');
                messageDiv.classList.add('newsletter-form__message--error');
              }
            })
            .catch(function () {
              messageDiv.textContent = Drupal.t('Connection error. Please try again.');
              messageDiv.classList.add('newsletter-form__message--error');
            })
            .finally(function () {
              button.disabled = false;
              button.textContent = Drupal.t('Subscribe');
            });
        });
      });
    },
  };
})(Drupal);

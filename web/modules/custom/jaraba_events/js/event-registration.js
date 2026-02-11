/**
 * @file
 * Validación client-side del formulario de registro de eventos.
 *
 * ESTRUCTURA:
 * Behavior de Drupal que añade validación en tiempo real a los campos
 * del formulario de registro. Complementa la validación server-side.
 *
 * LÓGICA:
 * - Valida nombre (mín 2 caracteres) y email (formato válido).
 * - Muestra/oculta mensajes de error inline.
 * - Previene submit si hay errores.
 */
(function (Drupal, once) {

  'use strict';

  Drupal.behaviors.jarabaEventsRegistration = {
    attach: function (context) {
      once('jaraba-reg-form', '.ej-event-register__form', context).forEach(function (form) {

        var nameInput = form.querySelector('#reg-name');
        var emailInput = form.querySelector('#reg-email');

        function validateField(input, validatorFn) {
          var field = input.closest('.ej-event-register__field');
          var existingError = field.querySelector('.ej-event-register__error-msg-js');

          var errorMsg = validatorFn(input.value.trim());

          if (errorMsg) {
            field.classList.add('ej-event-register__field--error');
            if (!existingError) {
              var span = document.createElement('span');
              span.className = 'ej-event-register__error-msg ej-event-register__error-msg-js';
              span.setAttribute('role', 'alert');
              span.textContent = errorMsg;
              field.appendChild(span);
            } else {
              existingError.textContent = errorMsg;
            }
            return false;
          }

          field.classList.remove('ej-event-register__field--error');
          if (existingError) {
            existingError.remove();
          }
          return true;
        }

        function validateName(value) {
          if (!value) { return Drupal.t('Name is required.'); }
          if (value.length < 2) { return Drupal.t('Name must be at least 2 characters.'); }
          return null;
        }

        function validateEmail(value) {
          if (!value) { return Drupal.t('Email is required.'); }
          var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
          if (!emailRegex.test(value)) { return Drupal.t('Please enter a valid email address.'); }
          return null;
        }

        if (nameInput) {
          nameInput.addEventListener('blur', function () {
            validateField(nameInput, validateName);
          });
        }

        if (emailInput) {
          emailInput.addEventListener('blur', function () {
            validateField(emailInput, validateEmail);
          });
        }

        form.addEventListener('submit', function (e) {
          var nameValid = nameInput ? validateField(nameInput, validateName) : true;
          var emailValid = emailInput ? validateField(emailInput, validateEmail) : true;

          if (!nameValid || !emailValid) {
            e.preventDefault();
            var firstError = form.querySelector('.ej-event-register__field--error input');
            if (firstError) {
              firstError.focus();
            }
          }
        });
      });
    }
  };

})(Drupal, once);

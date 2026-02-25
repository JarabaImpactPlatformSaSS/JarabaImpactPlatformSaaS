/**
 * @file
 * Behavior para el formulario de descarga de la Guia del Participante.
 *
 * Intercepta submit de [data-guia-form], valida client-side,
 * POST a /api/v1/andalucia-ei/guia-download, muestra feedback, localStorage tracking.
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.aeiGuiaForm = {
    attach: function (context) {
      once('aei-guia-form', '[data-guia-form]', context).forEach(function (form) {

        // Check if user already downloaded.
        var storageKey = 'aei_guia_downloaded';
        if (localStorage.getItem(storageKey)) {
          showFeedback(form, 'success', Drupal.t('Ya has solicitado la guía. Revisa tu bandeja de correo.'));
          disableForm(form);
          return;
        }

        form.addEventListener('submit', function (e) {
          e.preventDefault();

          // Client-side validation.
          var nombre = form.querySelector('[name="nombre"]');
          var email = form.querySelector('[name="email"]');
          var consentimiento = form.querySelector('[name="consentimiento"]');

          clearFeedback(form);

          if (!nombre || !nombre.value.trim()) {
            showFeedback(form, 'error', Drupal.t('Por favor, introduce tu nombre.'));
            nombre.focus();
            return;
          }

          if (!email || !email.value.trim()) {
            showFeedback(form, 'error', Drupal.t('Por favor, introduce tu email.'));
            email.focus();
            return;
          }

          // Basic email pattern.
          if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
            showFeedback(form, 'error', Drupal.t('Por favor, introduce un email válido.'));
            email.focus();
            return;
          }

          if (!consentimiento || !consentimiento.checked) {
            showFeedback(form, 'error', Drupal.t('Debes aceptar recibir información del programa.'));
            return;
          }

          // Disable submit while processing.
          var submitBtn = form.querySelector('[type="submit"]');
          if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = Drupal.t('Enviando...');
          }

          // POST to API.
          var payload = {
            nombre: nombre.value.trim(),
            email: email.value.trim()
          };

          fetch('/api/v1/andalucia-ei/guia-download', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
          })
          .then(function (response) {
            return response.json().then(function (data) {
              return { status: response.status, data: data };
            });
          })
          .then(function (result) {
            if (result.status === 200 && result.data.success) {
              showFeedback(form, 'success', result.data.message || Drupal.t('¡Guía enviada! Revisa tu bandeja de correo.'));
              localStorage.setItem(storageKey, Date.now().toString());
              disableForm(form);
            } else {
              showFeedback(form, 'error', result.data.message || Drupal.t('Ha ocurrido un error. Inténtalo de nuevo.'));
              if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = Drupal.t('Descargar guía');
              }
            }
          })
          .catch(function () {
            showFeedback(form, 'error', Drupal.t('Error de conexión. Inténtalo de nuevo.'));
            if (submitBtn) {
              submitBtn.disabled = false;
              submitBtn.textContent = Drupal.t('Descargar guía');
            }
          });
        });
      });
    }
  };

  function showFeedback(form, type, message) {
    clearFeedback(form);
    var el = document.createElement('div');
    el.className = 'aei-guia__feedback aei-guia__feedback--' + type;
    el.textContent = message;
    el.setAttribute('role', type === 'error' ? 'alert' : 'status');
    form.insertBefore(el, form.firstChild);
  }

  function clearFeedback(form) {
    var existing = form.querySelectorAll('.aei-guia__feedback');
    existing.forEach(function (el) {
      el.remove();
    });
  }

  function disableForm(form) {
    var inputs = form.querySelectorAll('input, button');
    inputs.forEach(function (input) {
      input.disabled = true;
    });
  }

})(Drupal, once);

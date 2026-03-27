/**
 * @file
 * Formulario dual de certificación — Drupal.behaviors.
 *
 * Gestiona campos condicionales profesional/entidad y envío via fetch.
 * Honeypot anti-spam ya en HTML. CSRF no necesario (anónimo público).
 *
 * @see CertificacionLandingController::submit()
 */
(function (Drupal) {
  'use strict';

  Drupal.behaviors.certificacionForm = {
    attach: function (context) {
      var form = context.querySelector ? context.querySelector('#cert-contact-form') : null;
      if (!form || form.dataset.certFormAttached) {
        return;
      }
      form.dataset.certFormAttached = 'true';

      var radios = form.querySelectorAll('input[name="tipo"]');
      var profFields = form.querySelector('.cert-form__conditional--profesional');
      var entFields = form.querySelector('.cert-form__conditional--entidad');

      function toggleFields() {
        var checked = form.querySelector('input[name="tipo"]:checked');
        if (!checked) return;
        var tipo = checked.value;
        profFields.style.display = tipo === 'profesional' ? '' : 'none';
        entFields.style.display = tipo === 'entidad' ? '' : 'none';
      }

      radios.forEach(function (r) {
        r.addEventListener('change', toggleFields);
      });
      toggleFields();

      form.addEventListener('submit', function (e) {
        e.preventDefault();
        var btn = form.querySelector('.cert-form__submit');
        var msg = form.querySelector('.cert-form__message');

        if (btn.disabled) return;
        btn.disabled = true;
        btn.textContent = Drupal.t('Enviando...');

        var data = new FormData(form);

        fetch(form.action, {
          method: 'POST',
          body: data,
        })
          .then(function (r) { return r.json(); })
          .then(function (json) {
            msg.style.display = 'block';
            msg.className = 'cert-form__message cert-form__message--' + (json.success ? 'success' : 'error');
            msg.textContent = json.message;
            if (json.success) {
              form.reset();
              toggleFields();
            }
            btn.disabled = false;
            btn.textContent = Drupal.t('Enviar solicitud') + ' \u2192';
          })
          .catch(function () {
            msg.style.display = 'block';
            msg.className = 'cert-form__message cert-form__message--error';
            msg.textContent = Drupal.t('Error al enviar. Inténtalo de nuevo.');
            btn.disabled = false;
            btn.textContent = Drupal.t('Enviar solicitud') + ' \u2192';
          });
      });
    },
  };

  /**
   * Exit-intent lead magnet popup.
   *
   * LANDING-CONVERSION-SCORE-001 criterio #15.
   * Muestra popup cuando el cursor sale por la parte superior de la ventana.
   * Solo una vez por sesión (localStorage).
   */
  Drupal.behaviors.certExitIntent = {
    attach: function (context) {
      var popup = context.querySelector ? context.querySelector('#cert-exit-intent') : null;
      if (!popup || popup.dataset.exitAttached) {
        return;
      }
      popup.dataset.exitAttached = 'true';

      var STORAGE_KEY = 'cert_exit_intent_shown';
      if (localStorage.getItem(STORAGE_KEY)) {
        return;
      }

      var shown = false;
      document.addEventListener('mouseleave', function (e) {
        if (e.clientY < 10 && !shown) {
          shown = true;
          localStorage.setItem(STORAGE_KEY, '1');
          popup.setAttribute('aria-hidden', 'false');
        }
      });

      var close = popup.querySelector('.cert-exit-intent__close');
      var backdrop = popup.querySelector('.cert-exit-intent__backdrop');
      function hide() { popup.setAttribute('aria-hidden', 'true'); }
      if (close) close.addEventListener('click', hide);
      if (backdrop) backdrop.addEventListener('click', hide);

      var exitForm = popup.querySelector('.cert-exit-intent__form');
      if (exitForm) {
        exitForm.addEventListener('submit', function (e) {
          e.preventDefault();
          var data = new FormData(exitForm);
          fetch(exitForm.action, { method: 'POST', body: data })
            .then(function () { hide(); })
            .catch(function () { hide(); });
        });
      }
    },
  };
})(Drupal);

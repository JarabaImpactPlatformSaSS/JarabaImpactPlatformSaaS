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
})(Drupal);

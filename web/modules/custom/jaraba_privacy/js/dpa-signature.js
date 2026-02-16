/**
 * @file
 * JavaScript de firma electrónica de DPA.
 *
 * Gestiona el modal de firma DPA y el envío al endpoint API.
 * El modal es bloqueante: el tenant no puede acceder al panel sin DPA.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Comportamiento de firma DPA.
   */
  Drupal.behaviors.jarabaDpaSignature = {
    attach: function (context) {
      once('jaraba-dpa-signature', '.jaraba-dpa-signature', context).forEach(function (element) {
        var form = element.querySelector('.dpa-signature__form');
        if (!form) {
          return;
        }

        form.addEventListener('submit', function (e) {
          e.preventDefault();
          Drupal.jarabaDpaSignature.submitSignature(form);
        });
      });
    }
  };

  /**
   * Utilidades de firma DPA.
   */
  Drupal.jarabaDpaSignature = {

    /**
     * Envía la firma al API.
     */
    submitSignature: function (form) {
      var signerName = form.querySelector('[name="signer_name"]');
      var signerRole = form.querySelector('[name="signer_role"]');
      var submitBtn = form.querySelector('[type="submit"]');

      if (!signerName || !signerRole || !signerName.value.trim() || !signerRole.value.trim()) {
        return;
      }

      submitBtn.disabled = true;
      submitBtn.textContent = Drupal.t('Firmando...');

      fetch('/api/v1/dpa/sign', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          signer_name: signerName.value.trim(),
          signer_role: signerRole.value.trim()
        })
      })
      .then(function (response) { return response.json(); })
      .then(function (data) {
        if (data.success) {
          Drupal.jarabaDpaSignature.onSuccess(data.data);
        }
        else {
          Drupal.jarabaDpaSignature.onError(data.error, submitBtn);
        }
      })
      .catch(function (error) {
        Drupal.jarabaDpaSignature.onError({ message: error.message }, submitBtn);
      });
    },

    /**
     * Callback de firma exitosa.
     */
    onSuccess: function (data) {
      var modal = document.querySelector('.jaraba-dpa-signature');
      if (modal) {
        modal.innerHTML = '<div class="dpa-signature__success">' +
          '<h3>' + Drupal.t('DPA firmado correctamente') + '</h3>' +
          '<p>' + Drupal.t('Versión: @version — Hash: @hash', {
            '@version': data.version,
            '@hash': data.dpa_hash ? data.dpa_hash.substring(0, 16) + '...' : ''
          }) + '</p>' +
          '</div>';

        setTimeout(function () {
          window.location.reload();
        }, 2000);
      }
    },

    /**
     * Callback de error en firma.
     */
    onError: function (error, submitBtn) {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = Drupal.t('Firmar DPA');
      }
      alert(Drupal.t('Error al firmar el DPA: @message', {
        '@message': error.message || 'Error desconocido'
      }));
    }
  };

})(Drupal, once);

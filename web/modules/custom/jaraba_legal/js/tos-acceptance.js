/**
 * @file
 * JavaScript del modal de aceptacion de Terms of Service.
 *
 * Gestiona el modal bloqueante de aceptacion de ToS:
 * - Verificacion de pendencia via data-tos-pending.
 * - Formulario con checkbox, nombre y rol obligatorios.
 * - Envio al endpoint API y recarga en caso de exito.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Comportamiento de aceptacion de ToS.
   */
  Drupal.behaviors.jarabaTosAcceptance = {
    attach: function (context) {
      once('jaraba-tos-acceptance', '[data-tos-pending]', context).forEach(function (element) {
        var isPending = element.getAttribute('data-tos-pending');
        if (isPending !== 'true') {
          return;
        }

        // Mostrar el modal de aceptacion.
        var modal = element.querySelector('.tos-status__modal');
        if (modal) {
          modal.classList.add('tos-status__modal--active');
        }

        // Inicializar el formulario.
        var form = element.querySelector('.tos-status__modal-form');
        if (form) {
          Drupal.jarabaTosAcceptance.initForm(form, modal);
        }
      });
    }
  };

  /**
   * Utilidades de aceptacion de ToS.
   */
  Drupal.jarabaTosAcceptance = {

    /**
     * Inicializa el formulario de aceptacion.
     */
    initForm: function (form, modal) {
      var checkbox = form.querySelector('[name="tos_accept"]');
      var nameField = form.querySelector('[name="signer_name"]');
      var roleField = form.querySelector('[name="signer_role"]');
      var submitBtn = form.querySelector('[type="submit"]');
      var errorEl = form.querySelector('.tos-status__modal-error');

      if (!checkbox || !nameField || !roleField || !submitBtn) {
        return;
      }

      // Habilitar/deshabilitar boton segun checkbox.
      checkbox.addEventListener('change', function () {
        submitBtn.disabled = !checkbox.checked;
      });

      // Enviar formulario.
      form.addEventListener('submit', function (e) {
        e.preventDefault();

        // Validar campos obligatorios.
        if (!checkbox.checked) {
          Drupal.jarabaTosAcceptance.showError(errorEl, Drupal.t('Debe aceptar los terminos de servicio.'));
          return;
        }

        if (!nameField.value.trim()) {
          Drupal.jarabaTosAcceptance.showError(errorEl, Drupal.t('El nombre es obligatorio.'));
          return;
        }

        if (!roleField.value.trim()) {
          Drupal.jarabaTosAcceptance.showError(errorEl, Drupal.t('El rol es obligatorio.'));
          return;
        }

        // Deshabilitar boton y enviar.
        submitBtn.disabled = true;
        submitBtn.textContent = Drupal.t('Aceptando...');
        Drupal.jarabaTosAcceptance.hideError(errorEl);

        Drupal.jarabaTosAcceptance.submitAcceptance({
          signer_name: nameField.value.trim(),
          signer_role: roleField.value.trim()
        }, submitBtn, errorEl);
      });
    },

    /**
     * Envia la aceptacion al endpoint API.
     */
    submitAcceptance: function (data, submitBtn, errorEl) {
      fetch('/api/v1/legal/tos/accept', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      })
      .then(function (response) { return response.json(); })
      .then(function (result) {
        if (result.success) {
          Drupal.jarabaTosAcceptance.onSuccess();
        }
        else {
          var message = result.error && result.error.message
            ? result.error.message
            : Drupal.t('Error al aceptar los terminos de servicio.');
          Drupal.jarabaTosAcceptance.onError(message, submitBtn, errorEl);
        }
      })
      .catch(function (error) {
        Drupal.jarabaTosAcceptance.onError(
          Drupal.t('Error de conexion: @message', { '@message': error.message }),
          submitBtn,
          errorEl
        );
      });
    },

    /**
     * Callback de aceptacion exitosa.
     */
    onSuccess: function () {
      window.location.reload();
    },

    /**
     * Callback de error en aceptacion.
     */
    onError: function (message, submitBtn, errorEl) {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = Drupal.t('Aceptar ToS');
      }
      Drupal.jarabaTosAcceptance.showError(errorEl, message);
    },

    /**
     * Muestra el mensaje de error.
     */
    showError: function (errorEl, message) {
      if (errorEl) {
        errorEl.textContent = message;
        errorEl.classList.add('tos-status__modal-error--visible');
      }
    },

    /**
     * Oculta el mensaje de error.
     */
    hideError: function (errorEl) {
      if (errorEl) {
        errorEl.textContent = '';
        errorEl.classList.remove('tos-status__modal-error--visible');
      }
    }
  };

})(Drupal, once);

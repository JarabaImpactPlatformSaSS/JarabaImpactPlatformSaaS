/**
 * @file
 * JavaScript del formulario del canal de denuncias.
 *
 * Gestiona el formulario de reporte anonimo y la consulta
 * de estado por codigo de seguimiento.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Comportamiento del formulario whistleblower.
   */
  Drupal.behaviors.jarabaWhistleblowerForm = {
    attach: function (context) {
      once('jaraba-whistleblower-form', '.whistleblower-panel', context).forEach(function (element) {
        Drupal.jarabaWhistleblower = Drupal.jarabaWhistleblower || {};

        // Inicializar formulario de reporte.
        var reportForm = element.querySelector('.whistleblower-panel__report-form');
        if (reportForm) {
          Drupal.jarabaWhistleblower.initReportForm(reportForm, element);
        }

        // Inicializar consulta de estado.
        var statusForm = element.querySelector('.whistleblower-panel__status-form');
        if (statusForm) {
          Drupal.jarabaWhistleblower.initStatusForm(statusForm, element);
        }
      });
    }
  };

  /**
   * Utilidades del canal de denuncias.
   */
  Drupal.jarabaWhistleblower = Drupal.jarabaWhistleblower || {};

  /**
   * Inicializa el formulario de reporte.
   */
  Drupal.jarabaWhistleblower.initReportForm = function (form, panel) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();

      var categorySelect = form.querySelector('[name="category"]');
      var descriptionField = form.querySelector('[name="description"]');
      var severitySelect = form.querySelector('[name="severity"]');
      var submitBtn = form.querySelector('[type="submit"]');

      // Validar campos obligatorios.
      if (!categorySelect || !descriptionField || !severitySelect) {
        return;
      }

      if (!descriptionField.value.trim()) {
        alert(Drupal.t('La descripcion es obligatoria.'));
        return;
      }

      // Deshabilitar boton y enviar.
      submitBtn.disabled = true;
      submitBtn.textContent = Drupal.t('Enviando reporte...');

      var reportData = {
        category: categorySelect.value,
        description: descriptionField.value.trim(),
        severity: severitySelect.value,
        is_anonymous: true
      };

      fetch('/api/v1/legal/whistleblower/report', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(reportData)
      })
      .then(function (response) { return response.json(); })
      .then(function (data) {
        if (data.success && data.data && data.data.tracking_code) {
          Drupal.jarabaWhistleblower.onReportSuccess(panel, data.data);
        }
        else {
          var message = data.error && data.error.message
            ? data.error.message
            : Drupal.t('Error al enviar el reporte.');
          Drupal.jarabaWhistleblower.onReportError(message, submitBtn);
        }
      })
      .catch(function (error) {
        Drupal.jarabaWhistleblower.onReportError(
          Drupal.t('Error de conexion: @message', { '@message': error.message }),
          submitBtn
        );
      });
    });
  };

  /**
   * Callback de reporte exitoso.
   *
   * Muestra el codigo de seguimiento al usuario.
   */
  Drupal.jarabaWhistleblower.onReportSuccess = function (panel, data) {
    var resultContainer = panel.querySelector('.whistleblower-panel__result');
    if (!resultContainer) {
      return;
    }

    // Mostrar codigo de seguimiento.
    resultContainer.innerHTML = '<div class="whistleblower-panel__success">' +
      '<h4>' + Drupal.t('Reporte enviado correctamente') + '</h4>' +
      '<p>' + Drupal.t('Guarde este codigo para consultar el estado de su reporte:') + '</p>' +
      '<div class="whistleblower-panel__tracking-code">' + data.tracking_code + '</div>' +
      '<p class="whistleblower-panel__tracking-notice">' +
      Drupal.t('Este codigo es la unica forma de consultar su reporte de forma anonima. No lo comparta.') +
      '</p>' +
      '</div>';

    resultContainer.hidden = false;

    // Ocultar formulario.
    var form = panel.querySelector('.whistleblower-panel__report-form');
    if (form) {
      form.hidden = true;
    }
  };

  /**
   * Callback de error en reporte.
   */
  Drupal.jarabaWhistleblower.onReportError = function (message, submitBtn) {
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.textContent = Drupal.t('Enviar reporte');
    }
    alert(Drupal.t('Error: @message', { '@message': message }));
  };

  /**
   * Inicializa el formulario de consulta de estado.
   */
  Drupal.jarabaWhistleblower.initStatusForm = function (form, panel) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();

      var codeField = form.querySelector('[name="tracking_code"]');
      var submitBtn = form.querySelector('[type="submit"]');

      if (!codeField || !codeField.value.trim()) {
        alert(Drupal.t('Introduzca el codigo de seguimiento.'));
        return;
      }

      var trackingCode = codeField.value.trim();

      // Deshabilitar boton.
      submitBtn.disabled = true;
      submitBtn.textContent = Drupal.t('Consultando...');

      fetch('/api/v1/legal/whistleblower/' + encodeURIComponent(trackingCode) + '/status', {
        headers: { 'Content-Type': 'application/json' }
      })
      .then(function (response) { return response.json(); })
      .then(function (data) {
        submitBtn.disabled = false;
        submitBtn.textContent = Drupal.t('Consultar estado');

        if (data.success && data.data) {
          Drupal.jarabaWhistleblower.renderStatusResult(panel, data.data);
        }
        else {
          var statusResult = panel.querySelector('.whistleblower-panel__status-result');
          if (statusResult) {
            statusResult.innerHTML = '<p class="whistleblower-panel__not-found">' +
              Drupal.t('No se encontro ningun reporte con ese codigo.') +
              '</p>';
            statusResult.hidden = false;
          }
        }
      })
      .catch(function (error) {
        submitBtn.disabled = false;
        submitBtn.textContent = Drupal.t('Consultar estado');
        alert(Drupal.t('Error de conexion: @message', { '@message': error.message }));
      });
    });
  };

  /**
   * Renderiza el resultado de la consulta de estado.
   */
  Drupal.jarabaWhistleblower.renderStatusResult = function (panel, data) {
    var statusResult = panel.querySelector('.whistleblower-panel__status-result');
    if (!statusResult) {
      return;
    }

    var statusLabels = {
      received: Drupal.t('Recibido'),
      investigating: Drupal.t('En investigacion'),
      resolved: Drupal.t('Resuelto'),
      dismissed: Drupal.t('Desestimado')
    };

    var statusLabel = statusLabels[data.status] || data.status;

    var html = '<div class="whistleblower-panel__status-info">' +
      '<p><strong>' + Drupal.t('Codigo:') + '</strong> ' + data.tracking_code + '</p>' +
      '<p><strong>' + Drupal.t('Estado:') + '</strong> ' + statusLabel + '</p>' +
      '<p><strong>' + Drupal.t('Categoria:') + '</strong> ' + data.category + '</p>' +
      '<p><strong>' + Drupal.t('Severidad:') + '</strong> ' + data.severity + '</p>';

    if (data.resolution) {
      html += '<p><strong>' + Drupal.t('Resolucion:') + '</strong> ' + data.resolution + '</p>';
    }

    html += '</div>';

    statusResult.innerHTML = html;
    statusResult.hidden = false;
  };

})(Drupal, once);

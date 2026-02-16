/**
 * @file
 * Legal Intelligence Hub — Gestion de alertas frontend.
 *
 * CRUD de alertas del profesional: crear, listar, activar/desactivar,
 * eliminar. Las acciones se ejecutan via AJAX contra /api/v1/legal/alerts.
 * El formulario de creacion se muestra en slide-panel.
 */

(function (Drupal, drupalSettings, once) {

  'use strict';

  /**
   * URL base de la API de alertas.
   *
   * @type {string}
   */
  var alertsUrl = (drupalSettings.legalIntelligence && drupalSettings.legalIntelligence.alertsUrl)
    ? drupalSettings.legalIntelligence.alertsUrl
    : '/api/v1/legal/alerts';

  /**
   * Tipos de alerta disponibles con sus etiquetas traducidas.
   *
   * @type {Object<string, string>}
   */
  var alertTypeLabels = {
    resolution_annulled: Drupal.t('Resolution annulled'),
    criteria_change: Drupal.t('Criteria change'),
    new_relevant_doctrine: Drupal.t('New relevant doctrine'),
    legislation_modified: Drupal.t('Legislation modified'),
    procedural_deadline: Drupal.t('Procedural deadline'),
    tjue_spain_impact: Drupal.t('CJEU impact on Spain'),
    tedh_spain: Drupal.t('ECHR against Spain'),
    edpb_guideline: Drupal.t('EDPB guideline'),
    transposition_deadline: Drupal.t('Transposition deadline'),
    ag_conclusions: Drupal.t('AG conclusions')
  };

  Drupal.behaviors.legalAlerts = {
    attach: function (context) {
      once('legal-alerts', '.legal-alerts', context).forEach(function (container) {
        // Cargar alertas existentes al montar el contenedor.
        loadAlerts(container);

        // Delegacion de eventos en el contenedor.
        container.addEventListener('click', function (e) {
          var toggleBtn = e.target.closest('[data-alert-toggle]');
          if (toggleBtn) {
            e.preventDefault();
            toggleAlert(toggleBtn);
            return;
          }

          var deleteBtn = e.target.closest('[data-alert-delete]');
          if (deleteBtn) {
            e.preventDefault();
            if (confirm(Drupal.t('Are you sure you want to delete this alert?'))) {
              deleteAlert(deleteBtn);
            }
            return;
          }

          var createBtn = e.target.closest('[data-alert-create]');
          if (createBtn) {
            e.preventDefault();
            showCreateForm(container);
            return;
          }
        });

        // Delegacion de submit en formularios de creacion.
        container.addEventListener('submit', function (e) {
          var form = e.target.closest('.legal-alert-form');
          if (form) {
            e.preventDefault();
            createAlert(form, container);
          }
        });
      });
    }
  };

  /**
   * Carga las alertas del usuario actual desde la API.
   *
   * @param {HTMLElement} container - Contenedor .legal-alerts.
   */
  function loadAlerts(container) {
    var listEl = container.querySelector('.legal-alerts__list');
    if (!listEl) {
      return;
    }

    apiRequest('GET')
      .then(function (data) {
        if (!data.success) {
          return;
        }

        var alerts = data.data || [];
        var countEl = container.querySelector('.legal-alerts__count');
        if (countEl) {
          countEl.textContent = Drupal.t('@count alerts', { '@count': alerts.length });
        }

        if (alerts.length === 0) {
          listEl.innerHTML = '<p class="legal-alerts__empty">' +
            Drupal.t('No alerts configured. Create one to receive notifications.') + '</p>';
          return;
        }

        listEl.innerHTML = '';
        alerts.forEach(function (alert) {
          listEl.appendChild(renderAlertCard(alert));
        });
      });
  }

  /**
   * Renderiza una tarjeta de alerta como elemento DOM.
   *
   * @param {Object} alert - Datos de la alerta.
   * @returns {HTMLElement} Elemento DOM de la tarjeta.
   */
  function renderAlertCard(alert) {
    var severity = alert.severity || 'medium';
    var isActive = alert.is_active;

    var card = document.createElement('div');
    card.className = 'legal-alert-card legal-alert-card--' + severity;
    card.setAttribute('data-alert-id', alert.id);

    var typeLabel = alertTypeLabels[alert.alert_type] || alert.alert_type;
    var statusLabel = isActive ? Drupal.t('Active') : Drupal.t('Paused');
    var statusClass = isActive ? ' is-active' : '';
    var toggleLabel = isActive ? Drupal.t('Pause') : Drupal.t('Activate');
    var triggerText = Drupal.t('Triggered @count times', { '@count': alert.trigger_count || 0 });

    card.innerHTML =
      '<div class="legal-alert-card__header">' +
        '<h4 class="legal-alert-card__title">' + Drupal.checkPlain(alert.label) + '</h4>' +
        '<span class="legal-alert-card__status' + statusClass + '">' + statusLabel + '</span>' +
      '</div>' +
      '<div class="legal-alert-card__meta">' +
        '<span>' + Drupal.checkPlain(typeLabel) + '</span>' +
        '<span>' + triggerText + '</span>' +
      '</div>' +
      '<div class="legal-alert-card__actions">' +
        '<button class="btn btn--sm btn--ghost' + statusClass + '" ' +
          'data-alert-toggle="' + alert.id + '" ' +
          'aria-label="' + Drupal.t('Toggle alert') + '">' +
          toggleLabel +
        '</button>' +
        '<button class="btn btn--sm btn--ghost btn--danger" ' +
          'data-alert-delete="' + alert.id + '" ' +
          'aria-label="' + Drupal.t('Delete alert') + '">' +
          Drupal.t('Delete') +
        '</button>' +
      '</div>';

    return card;
  }

  /**
   * Muestra el formulario de creacion de alerta.
   *
   * @param {HTMLElement} container - Contenedor .legal-alerts.
   */
  function showCreateForm(container) {
    // No duplicar formulario.
    if (container.querySelector('.legal-alert-form')) {
      return;
    }

    var form = document.createElement('form');
    form.className = 'legal-alert-form';

    // Construir select de tipos.
    var typeOptions = '';
    Object.keys(alertTypeLabels).forEach(function (key) {
      typeOptions += '<option value="' + key + '">' + Drupal.checkPlain(alertTypeLabels[key]) + '</option>';
    });

    form.innerHTML =
      '<div class="legal-alert-form__group">' +
        '<label class="legal-alert-form__label" for="alert-label">' + Drupal.t('Alert name') + '</label>' +
        '<input type="text" id="alert-label" name="label" class="legal-alert-form__input" ' +
          'placeholder="' + Drupal.t('e.g., New tax doctrine alerts') + '" required>' +
      '</div>' +
      '<div class="legal-alert-form__group">' +
        '<label class="legal-alert-form__label" for="alert-type">' + Drupal.t('Alert type') + '</label>' +
        '<select id="alert-type" name="alert_type" class="legal-alert-form__select">' +
          typeOptions +
        '</select>' +
      '</div>' +
      '<div class="legal-alert-form__group">' +
        '<label class="legal-alert-form__label" for="alert-severity">' + Drupal.t('Severity') + '</label>' +
        '<select id="alert-severity" name="severity" class="legal-alert-form__select">' +
          '<option value="critical">' + Drupal.t('Critical') + '</option>' +
          '<option value="high">' + Drupal.t('High') + '</option>' +
          '<option value="medium" selected>' + Drupal.t('Medium') + '</option>' +
          '<option value="low">' + Drupal.t('Low') + '</option>' +
        '</select>' +
      '</div>' +
      '<div class="legal-alert-form__error" style="display:none;"></div>' +
      '<button type="submit" class="legal-alert-form__submit">' + Drupal.t('Create alert') + '</button>';

    // Insertar al inicio de la lista.
    var listEl = container.querySelector('.legal-alerts__list');
    if (listEl) {
      container.insertBefore(form, listEl);
    }
    else {
      container.appendChild(form);
    }

    // Focus en el primer campo.
    var labelInput = form.querySelector('#alert-label');
    if (labelInput) {
      labelInput.focus();
    }
  }

  /**
   * Crea una nueva alerta via API.
   *
   * @param {HTMLFormElement} form - Formulario de creacion.
   * @param {HTMLElement} container - Contenedor .legal-alerts.
   */
  function createAlert(form, container) {
    var label = form.querySelector('[name="label"]').value.trim();
    var alertType = form.querySelector('[name="alert_type"]').value;
    var severity = form.querySelector('[name="severity"]').value;
    var errorEl = form.querySelector('.legal-alert-form__error');
    var submitBtn = form.querySelector('.legal-alert-form__submit');

    if (!label) {
      showError(errorEl, Drupal.t('Alert name is required.'));
      return;
    }

    // Deshabilitar boton durante la peticion.
    submitBtn.disabled = true;

    apiRequest('POST', {
      label: label,
      alert_type: alertType,
      severity: severity,
      channels: ['in_app', 'email']
    })
    .then(function (data) {
      submitBtn.disabled = false;

      if (!data.success) {
        showError(errorEl, data.error || Drupal.t('Could not create alert.'));
        return;
      }

      // Eliminar formulario y recargar lista.
      form.remove();
      loadAlerts(container);
    })
    .catch(function () {
      submitBtn.disabled = false;
      showError(errorEl, Drupal.t('Network error. Please try again.'));
    });
  }

  /**
   * Alterna estado activo/inactivo de una alerta.
   *
   * @param {HTMLElement} btn - Boton de toggle.
   */
  function toggleAlert(btn) {
    var alertId = btn.dataset.alertToggle;
    var isActive = btn.classList.contains('is-active');

    apiRequest('PATCH', { id: parseInt(alertId, 10), is_active: !isActive })
      .then(function (data) {
        if (!data.success) {
          return;
        }

        // Actualizar UI.
        btn.classList.toggle('is-active');
        btn.textContent = isActive ? Drupal.t('Activate') : Drupal.t('Pause');

        var card = btn.closest('.legal-alert-card');
        if (card) {
          var statusEl = card.querySelector('.legal-alert-card__status');
          if (statusEl) {
            statusEl.classList.toggle('is-active');
            statusEl.textContent = isActive ? Drupal.t('Paused') : Drupal.t('Active');
          }
        }
      });
  }

  /**
   * Elimina una alerta.
   *
   * @param {HTMLElement} btn - Boton de eliminar.
   */
  function deleteAlert(btn) {
    var alertId = btn.dataset.alertDelete;

    apiRequest('DELETE', { id: parseInt(alertId, 10) })
      .then(function (data) {
        if (data.success) {
          var card = btn.closest('.legal-alert-card');
          if (card) {
            card.remove();
          }
        }
      });
  }

  /**
   * Ejecuta una peticion a la API de alertas con cabeceras adecuadas.
   *
   * Incluye X-Requested-With para CSRF protection de Drupal y
   * Content-Type para payloads JSON.
   *
   * @param {string} method - Metodo HTTP (GET, POST, PATCH, DELETE).
   * @param {Object} [body] - Payload JSON (para POST, PATCH, DELETE).
   * @returns {Promise<Object>} Promesa con la respuesta parseada.
   */
  function apiRequest(method, body) {
    var options = {
      method: method,
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin'
    };

    if (body && method !== 'GET') {
      options.body = JSON.stringify(body);
    }

    return fetch(alertsUrl, options)
      .then(function (response) {
        return response.json();
      })
      .catch(function (err) {
        return { success: false, error: err.message || 'Network error' };
      });
  }

  /**
   * Muestra un mensaje de error en el formulario.
   *
   * @param {HTMLElement} el - Elemento de error.
   * @param {string} message - Mensaje de error.
   */
  function showError(el, message) {
    if (el) {
      el.textContent = message;
      el.style.display = 'block';
    }
  }

})(Drupal, drupalSettings, once);

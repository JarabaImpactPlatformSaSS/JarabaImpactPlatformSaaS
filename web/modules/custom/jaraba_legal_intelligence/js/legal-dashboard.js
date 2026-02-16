/**
 * @file
 * Legal Intelligence Hub — Dashboard del profesional frontend.
 *
 * Gestiona la pagina de dashboard del usuario profesional:
 * eliminacion de marcadores (bookmarks), activacion/desactivacion
 * y eliminacion de alertas, y busquedas recientes.
 *
 * NO confundir con legal-admin.js que gestiona el dashboard de
 * administracion del sistema.
 *
 * Los endpoints API utilizados:
 *   DELETE /api/v1/legal/bookmark   — Eliminar marcador.
 *   PATCH  /api/v1/legal/alerts     — Alternar estado de alerta.
 *   DELETE /api/v1/legal/alerts     — Eliminar alerta.
 */

(function (Drupal, drupalSettings, once) {

  'use strict';

  /**
   * URLs base de los endpoints API del dashboard profesional.
   *
   * Se inyectan via drupalSettings.legalDashboard o se usan valores
   * por defecto.
   *
   * @type {Object<string, string>}
   */
  var apiUrls = (drupalSettings.legalDashboard)
    ? drupalSettings.legalDashboard
    : {
        bookmarkUrl: '/api/v1/legal/bookmark',
        alertsUrl: '/api/v1/legal/alerts'
      };

  /**
   * Duracion de la animacion de fade-out en milisegundos.
   *
   * @type {number}
   */
  var FADE_DURATION = 300;

  Drupal.behaviors.legalDashboard = {
    attach: function (context) {
      once('legal-dashboard', '.legal-professional-dashboard', context).forEach(function (dashboard) {

        // Delegacion de eventos: eliminacion de marcadores.
        dashboard.addEventListener('click', function (e) {
          var removeBookmarkBtn = e.target.closest('[data-remove-bookmark]');
          if (removeBookmarkBtn) {
            e.preventDefault();
            handleRemoveBookmark(removeBookmarkBtn, dashboard);
            return;
          }

          var toggleAlertBtn = e.target.closest('[data-toggle-alert]');
          if (toggleAlertBtn) {
            e.preventDefault();
            handleToggleAlert(toggleAlertBtn);
            return;
          }

          var deleteAlertBtn = e.target.closest('[data-delete-alert]');
          if (deleteAlertBtn) {
            e.preventDefault();
            handleDeleteAlert(deleteAlertBtn, dashboard);
            return;
          }
        });
      });
    }
  };

  /**
   * Elimina un marcador (bookmark) de resolucion via API.
   *
   * Envia DELETE a /api/v1/legal/bookmark con el resolution_id.
   * En caso de exito, aplica animacion de fade-out y elimina la
   * tarjeta del DOM. Si era el ultimo marcador, muestra estado vacio.
   *
   * @param {HTMLElement} btn - Boton con atributo data-remove-bookmark.
   * @param {HTMLElement} dashboard - Contenedor .legal-professional-dashboard.
   */
  function handleRemoveBookmark(btn, dashboard) {
    var resolutionId = btn.dataset.removeBookmark;
    if (!resolutionId) {
      return;
    }

    // Deshabilitar boton durante la peticion.
    btn.disabled = true;

    apiRequest(apiUrls.bookmarkUrl, 'DELETE', { resolution_id: resolutionId })
      .then(function (data) {
        if (!data.success) {
          btn.disabled = false;
          return;
        }

        // Animacion de fade-out y eliminacion del DOM.
        var card = btn.closest('.legal-resolution-card, .legal-bookmark-card');
        if (card) {
          fadeOutAndRemove(card, function () {
            checkEmptyState(dashboard, '.legal-dashboard-bookmarks', Drupal.t('No bookmarks yet. Bookmark resolutions from search results to access them quickly.'));
          });
        }

        // Mensaje de confirmacion al usuario.
        showStatusMessage(Drupal.t('Bookmark removed'));
      })
      .catch(function () {
        btn.disabled = false;
        showStatusMessage(Drupal.t('Could not remove bookmark. Please try again.'), 'error');
      });
  }

  /**
   * Alterna el estado activo/inactivo de una alerta via API.
   *
   * Envia PATCH a /api/v1/legal/alerts con alert_id y el nuevo
   * valor de is_active. Actualiza la clase .is-active del badge
   * de estado en la UI.
   *
   * @param {HTMLElement} btn - Boton con atributo data-toggle-alert.
   */
  function handleToggleAlert(btn) {
    var alertId = btn.dataset.toggleAlert;
    if (!alertId) {
      return;
    }

    var isCurrentlyActive = btn.classList.contains('is-active');

    // Deshabilitar boton durante la peticion.
    btn.disabled = true;

    apiRequest(apiUrls.alertsUrl, 'PATCH', {
      alert_id: parseInt(alertId, 10),
      is_active: !isCurrentlyActive
    })
    .then(function (data) {
      btn.disabled = false;

      if (!data.success) {
        return;
      }

      // Actualizar estado del boton.
      btn.classList.toggle('is-active');
      btn.textContent = isCurrentlyActive ? Drupal.t('Activate') : Drupal.t('Pause');

      // Actualizar badge de estado en la tarjeta padre.
      var card = btn.closest('.legal-alert-card, .legal-dashboard-alert');
      if (card) {
        var badge = card.querySelector('.legal-alert-card__status, .legal-dashboard-alert__badge');
        if (badge) {
          badge.classList.toggle('is-active');
          badge.textContent = isCurrentlyActive ? Drupal.t('Paused') : Drupal.t('Active');
        }
      }
    })
    .catch(function () {
      btn.disabled = false;
    });
  }

  /**
   * Elimina una alerta tras confirmacion del usuario.
   *
   * Muestra dialogo de confirmacion. Si el usuario acepta, envia
   * DELETE a /api/v1/legal/alerts con alert_id. Elimina la tarjeta
   * del DOM y comprueba estado vacio.
   *
   * @param {HTMLElement} btn - Boton con atributo data-delete-alert.
   * @param {HTMLElement} dashboard - Contenedor .legal-professional-dashboard.
   */
  function handleDeleteAlert(btn, dashboard) {
    if (!confirm(Drupal.t('Delete this alert?'))) {
      return;
    }

    var alertId = btn.dataset.deleteAlert;
    if (!alertId) {
      return;
    }

    // Deshabilitar boton durante la peticion.
    btn.disabled = true;

    apiRequest(apiUrls.alertsUrl, 'DELETE', { alert_id: parseInt(alertId, 10) })
      .then(function (data) {
        if (!data.success) {
          btn.disabled = false;
          return;
        }

        // Animacion de fade-out y eliminacion del DOM.
        var card = btn.closest('.legal-alert-card, .legal-dashboard-alert');
        if (card) {
          fadeOutAndRemove(card, function () {
            checkEmptyState(dashboard, '.legal-dashboard-alerts', Drupal.t('No alerts configured. Create alerts to receive notifications about legal changes.'));
          });
        }
      })
      .catch(function () {
        btn.disabled = false;
        showStatusMessage(Drupal.t('Could not delete alert. Please try again.'), 'error');
      });
  }

  /**
   * Aplica animacion de fade-out a un elemento y lo elimina del DOM.
   *
   * Usa transicion CSS opacity durante FADE_DURATION ms.
   * Ejecuta callback opcional tras la eliminacion.
   *
   * @param {HTMLElement} el - Elemento a eliminar.
   * @param {Function} [callback] - Funcion a ejecutar tras la eliminacion.
   */
  function fadeOutAndRemove(el, callback) {
    el.style.transition = 'opacity ' + FADE_DURATION + 'ms ease, transform ' + FADE_DURATION + 'ms ease';
    el.style.opacity = '0';
    el.style.transform = 'translateX(-10px)';

    setTimeout(function () {
      el.remove();
      if (typeof callback === 'function') {
        callback();
      }
    }, FADE_DURATION);
  }

  /**
   * Comprueba si una seccion del dashboard esta vacia y muestra
   * un mensaje de estado vacio si corresponde.
   *
   * Busca dentro del contenedor la seccion indicada por el selector.
   * Si no quedan tarjetas hijas (.legal-resolution-card, .legal-alert-card,
   * .legal-bookmark-card, .legal-dashboard-alert), inserta un parrafo
   * con el mensaje de estado vacio.
   *
   * @param {HTMLElement} dashboard - Contenedor .legal-professional-dashboard.
   * @param {string} sectionSelector - Selector CSS de la seccion a verificar.
   * @param {string} message - Mensaje de estado vacio traducido.
   */
  function checkEmptyState(dashboard, sectionSelector, message) {
    var section = dashboard.querySelector(sectionSelector);
    if (!section) {
      return;
    }

    var listEl = section.querySelector('.legal-dashboard-bookmarks__list, .legal-dashboard-alerts__list, .legal-alerts__list');
    var target = listEl || section;

    var remainingCards = target.querySelectorAll('.legal-resolution-card, .legal-alert-card, .legal-bookmark-card, .legal-dashboard-alert');
    if (remainingCards.length === 0) {
      // Limpiar contenido restante y mostrar estado vacio.
      target.innerHTML = '<p class="legal-dashboard__empty-state">' + Drupal.checkPlain(message) + '</p>';
    }
  }

  /**
   * Muestra un mensaje de estado al usuario usando el sistema de
   * mensajes de Drupal si esta disponible.
   *
   * @param {string} message - Mensaje traducido a mostrar.
   * @param {string} [type='status'] - Tipo de mensaje: 'status', 'warning', 'error'.
   */
  function showStatusMessage(message, type) {
    type = type || 'status';

    // Usar Drupal.announce para accesibilidad.
    if (Drupal.announce) {
      Drupal.announce(message);
    }

    // Intentar usar el sistema de mensajes nativo de Drupal.
    if (Drupal.message) {
      var messages = new Drupal.message();
      messages.add(message, { type: type });
      return;
    }

    // Fallback: insertar mensaje manualmente en la region de mensajes.
    var messagesRegion = document.querySelector('[data-drupal-messages], .messages-list, .region-highlighted');
    if (messagesRegion) {
      var msgEl = document.createElement('div');
      msgEl.className = 'messages messages--' + type;
      msgEl.setAttribute('role', type === 'error' ? 'alert' : 'status');
      msgEl.setAttribute('aria-live', 'polite');
      msgEl.textContent = message;
      messagesRegion.appendChild(msgEl);

      // Auto-eliminar despues de 5 segundos.
      setTimeout(function () {
        if (msgEl.parentNode) {
          fadeOutAndRemove(msgEl);
        }
      }, 5000);
    }
  }

  /**
   * Ejecuta una peticion HTTP a la API con cabeceras adecuadas.
   *
   * Incluye X-Requested-With para proteccion CSRF de Drupal,
   * Content-Type para payloads JSON y credentials: 'same-origin'
   * para autenticacion basada en sesion.
   *
   * @param {string} url - URL del endpoint API.
   * @param {string} method - Metodo HTTP (GET, POST, PATCH, DELETE).
   * @param {Object} [body] - Payload JSON para POST, PATCH, DELETE.
   * @returns {Promise<Object>} Promesa con la respuesta parseada.
   */
  function apiRequest(url, method, body) {
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

    return fetch(url, options)
      .then(function (response) {
        return response.json();
      })
      .catch(function (err) {
        return { success: false, error: err.message || 'Network error' };
      });
  }

})(Drupal, drupalSettings, once);

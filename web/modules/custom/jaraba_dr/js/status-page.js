/**
 * @file
 * JavaScript de la página de estado pública.
 *
 * Proporciona auto-refresco de datos de estado, contador de tiempo
 * desde última actualización y transiciones suaves al cambiar estado
 * de los componentes.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Espacio de nombres para funciones de status page.
   */
  Drupal.jarabaDr = Drupal.jarabaDr || {};

  /**
   * Comportamiento de la página de estado pública.
   */
  Drupal.behaviors.jarabaStatusPage = {
    attach: function (context) {
      once('jaraba-status-page', '.status-page', context).forEach(function (element) {
        var refreshSeconds = parseInt(element.getAttribute('data-auto-refresh'), 10) || 30;

        // Almacenar timestamp de última actualización.
        Drupal.jarabaDr.statusLastUpdated = Date.now();

        // Iniciar auto-refresco via API (sin recargar página).
        Drupal.jarabaDr.statusInterval = setInterval(function () {
          Drupal.jarabaDr.refreshStatusData(element);
        }, refreshSeconds * 1000);

        // Iniciar contador de "Actualizado hace X segundos".
        Drupal.jarabaDr.startUpdateCounter(element);

        // Cargar datos iniciales.
        Drupal.jarabaDr.refreshStatusData(element);
      });
    }
  };

  /**
   * Recarga los datos de estado via API sin recargar la página.
   *
   * @param {HTMLElement} container
   *   Elemento raíz de la status page.
   */
  Drupal.jarabaDr.refreshStatusData = function (container) {
    fetch('/api/v1/dr/services', {
      headers: { 'Content-Type': 'application/json' }
    })
    .then(function (response) { return response.json(); })
    .then(function (data) {
      if (data.success && data.data) {
        Drupal.jarabaDr.updateComponentRows(container, data.data);
        Drupal.jarabaDr.statusLastUpdated = Date.now();
      }
    })
    .catch(function (error) {
      console.warn('Error al actualizar estado de servicios:', error);
    });
  };

  /**
   * Actualiza las filas de componentes con los nuevos datos.
   * Aplica transiciones suaves intercambiando clases CSS.
   *
   * @param {HTMLElement} container
   *   Elemento raíz de la status page.
   * @param {Object} services
   *   Datos de servicios del API.
   */
  Drupal.jarabaDr.updateComponentRows = function (container, services) {
    var components = container.querySelectorAll('.status-page__component');

    components.forEach(function (component) {
      var serviceId = component.getAttribute('data-service-id');
      if (!serviceId || !services[serviceId]) {
        return;
      }

      var service = services[serviceId];
      var badge = component.querySelector('.status-page__badge');

      if (badge) {
        // Eliminar clases de estado anteriores.
        var statusClasses = [
          'status-page__badge--operational',
          'status-page__badge--degraded',
          'status-page__badge--partial_outage',
          'status-page__badge--major_outage',
          'status-page__badge--maintenance'
        ];
        statusClasses.forEach(function (cls) {
          badge.classList.remove(cls);
        });

        // Aplicar nueva clase de estado.
        badge.classList.add('status-page__badge--' + service.status);
        badge.textContent = Drupal.jarabaDr.getStatusLabel(service.status);
      }
    });
  };

  /**
   * Devuelve la etiqueta traducida para un estado de servicio.
   *
   * @param {string} status
   *   Código de estado.
   *
   * @return {string}
   *   Etiqueta traducida.
   */
  Drupal.jarabaDr.getStatusLabel = function (status) {
    var labels = {
      operational: Drupal.t('Operativo'),
      degraded: Drupal.t('Degradado'),
      partial_outage: Drupal.t('Interrupción parcial'),
      major_outage: Drupal.t('Interrupción mayor'),
      maintenance: Drupal.t('Mantenimiento')
    };
    return labels[status] || status;
  };

  /**
   * Inicia el contador de "Actualizado hace X segundos".
   *
   * @param {HTMLElement} container
   *   Elemento raíz de la status page.
   */
  Drupal.jarabaDr.startUpdateCounter = function (container) {
    var counterElement = container.querySelector('.status-page__refresh-counter');
    if (!counterElement) {
      return;
    }

    setInterval(function () {
      var elapsed = Math.floor((Date.now() - Drupal.jarabaDr.statusLastUpdated) / 1000);
      counterElement.textContent = Drupal.t('Actualizado hace @seconds segundos', {
        '@seconds': elapsed
      });
    }, 1000);
  };

})(Drupal, once);

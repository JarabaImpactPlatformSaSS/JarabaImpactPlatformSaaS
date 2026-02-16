/**
 * @file
 * JavaScript de la vista de detalle de un incidente DR.
 *
 * Proporciona carga dinámica del detalle de un incidente,
 * renderizado del timeline de actualizaciones, componentes
 * afectados y log de comunicaciones.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Espacio de nombres para funciones DR.
   */
  Drupal.jarabaDr = Drupal.jarabaDr || {};

  /**
   * Comportamiento de detalle de incidente.
   */
  Drupal.behaviors.jarabaIncidentDetail = {
    attach: function (context) {
      once('jaraba-incident-detail', '.incident-detail', context).forEach(function (element) {
        var incidentId = element.getAttribute('data-incident-id');
        if (!incidentId) {
          return;
        }

        // Cargar detalle del incidente via API.
        Drupal.jarabaDr.loadIncidentDetail(element, incidentId);
      });
    }
  };

  /**
   * Carga el detalle completo de un incidente.
   *
   * @param {HTMLElement} container
   *   Elemento raíz del detalle del incidente.
   * @param {string} incidentId
   *   ID del incidente a cargar.
   */
  Drupal.jarabaDr.loadIncidentDetail = function (container, incidentId) {
    fetch('/api/v1/dr/incidents/' + encodeURIComponent(incidentId), {
      headers: { 'Content-Type': 'application/json' }
    })
    .then(function (response) { return response.json(); })
    .then(function (data) {
      if (data.success && data.data) {
        Drupal.jarabaDr.renderIncidentDetail(container, data.data);
      }
    })
    .catch(function (error) {
      console.warn('Error al cargar detalle del incidente:', error);
    });
  };

  /**
   * Renderiza el detalle completo del incidente.
   *
   * @param {HTMLElement} container
   *   Elemento raíz del detalle.
   * @param {Object} incident
   *   Datos del incidente del API.
   */
  Drupal.jarabaDr.renderIncidentDetail = function (container, incident) {
    // Renderizar timeline de actualizaciones.
    Drupal.jarabaDr.renderTimeline(container, incident);

    // Renderizar componentes afectados.
    Drupal.jarabaDr.renderAffectedComponents(container, incident);

    // Renderizar log de comunicaciones.
    Drupal.jarabaDr.renderCommunicationLog(container, incident);
  };

  /**
   * Renderiza el timeline de actualizaciones del incidente.
   *
   * @param {HTMLElement} container
   *   Elemento raíz del detalle.
   * @param {Object} incident
   *   Datos del incidente.
   */
  Drupal.jarabaDr.renderTimeline = function (container, incident) {
    var timeline = container.querySelector('.incident-timeline__list');
    if (!timeline) {
      return;
    }

    // Limpiar contenido previo del timeline.
    timeline.innerHTML = '';

    // Si hay updates disponibles, renderizar cada uno.
    var updates = incident.updates || [];
    if (updates.length === 0) {
      var empty = document.createElement('p');
      empty.className = 'incident-timeline__empty';
      empty.textContent = Drupal.t('No hay actualizaciones registradas.');
      timeline.appendChild(empty);
      return;
    }

    updates.forEach(function (update) {
      var entry = document.createElement('div');
      entry.className = 'incident-timeline__entry incident-timeline__entry--' + (incident.severity || 'p4_cosmetic');

      var header = document.createElement('div');
      header.className = 'incident-timeline__header';

      var timestamp = document.createElement('span');
      timestamp.className = 'incident-timeline__timestamp';
      timestamp.textContent = new Date(update.timestamp * 1000).toLocaleString('es-ES');

      var severity = document.createElement('span');
      severity.className = 'incident-timeline__severity incident-timeline__severity--' + (incident.severity || 'p4_cosmetic');
      severity.textContent = update.status || incident.severity;

      header.appendChild(timestamp);
      header.appendChild(severity);
      entry.appendChild(header);

      var message = document.createElement('p');
      message.className = 'incident-timeline__message';
      message.textContent = update.message || '';
      entry.appendChild(message);

      timeline.appendChild(entry);
    });
  };

  /**
   * Renderiza la lista de componentes afectados.
   *
   * @param {HTMLElement} container
   *   Elemento raíz del detalle.
   * @param {Object} incident
   *   Datos del incidente.
   */
  Drupal.jarabaDr.renderAffectedComponents = function (container, incident) {
    var section = container.querySelector('.incident-detail__affected');
    if (!section) {
      return;
    }

    var services = incident.affected_services || [];
    if (services.length === 0) {
      section.innerHTML = '<p class="incident-detail__empty">' +
        Drupal.t('No hay componentes afectados registrados.') + '</p>';
      return;
    }

    var list = document.createElement('ul');
    list.className = 'incident-detail__affected-list';

    services.forEach(function (service) {
      var item = document.createElement('li');
      item.className = 'incident-detail__affected-item';
      item.textContent = service;
      list.appendChild(item);
    });

    section.innerHTML = '';
    section.appendChild(list);
  };

  /**
   * Renderiza el log de comunicaciones del incidente.
   *
   * @param {HTMLElement} container
   *   Elemento raíz del detalle.
   * @param {Object} incident
   *   Datos del incidente.
   */
  Drupal.jarabaDr.renderCommunicationLog = function (container, incident) {
    var section = container.querySelector('.incident-detail__communications');
    if (!section) {
      return;
    }

    var log = incident.communication_log || [];
    if (log.length === 0) {
      section.innerHTML = '<p class="incident-detail__empty">' +
        Drupal.t('No hay comunicaciones registradas.') + '</p>';
      return;
    }

    section.innerHTML = '';

    log.forEach(function (entry) {
      var comm = document.createElement('div');
      comm.className = 'incident-timeline__communication';

      var channel = document.createElement('span');
      channel.className = 'incident-timeline__communication-channel';
      channel.textContent = entry.channel || 'sistema';

      var timestamp = document.createElement('span');
      timestamp.className = 'incident-timeline__timestamp';
      timestamp.textContent = ' — ' + new Date(entry.timestamp * 1000).toLocaleString('es-ES');

      var message = document.createElement('p');
      message.className = 'incident-timeline__message';
      message.textContent = entry.message || '';

      comm.appendChild(channel);
      comm.appendChild(timestamp);
      comm.appendChild(message);
      section.appendChild(comm);
    });
  };

})(Drupal, once);

/**
 * @file
 * JavaScript del dashboard de administración de Disaster Recovery.
 *
 * Proporciona interactividad al dashboard: carga de estado de backups,
 * incidentes recientes, resultados de tests DR y codificación por color
 * según estado.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Espacio de nombres para funciones DR.
   */
  Drupal.jarabaDr = Drupal.jarabaDr || {};

  /**
   * Comportamiento del dashboard DR.
   */
  Drupal.behaviors.jarabaDrDashboard = {
    attach: function (context) {
      once('jaraba-dr-dashboard', '.jaraba-dr-dashboard', context).forEach(function (element) {
        // Inicializar componentes del dashboard.
        Drupal.jarabaDr.initBackupStatus(element);
        Drupal.jarabaDr.initIncidents(element);
        Drupal.jarabaDr.initTestResults(element);
      });
    }
  };

  /**
   * Inicializa el widget de estado de backups.
   *
   * @param {HTMLElement} dashboard
   *   Elemento raíz del dashboard.
   */
  Drupal.jarabaDr.initBackupStatus = function (dashboard) {
    var section = dashboard.querySelector('.dr-dashboard__backups');
    if (!section) {
      return;
    }

    // Cargar estado de backups via API.
    fetch('/api/v1/dr/status', {
      headers: { 'Content-Type': 'application/json' }
    })
    .then(function (response) { return response.json(); })
    .then(function (data) {
      if (data.success && data.data) {
        Drupal.jarabaDr.renderBackupStatus(section, data.data);
      }
    })
    .catch(function (error) {
      console.warn('Error al cargar estado de backups:', error);
    });
  };

  /**
   * Renderiza el estado de backups en la sección.
   *
   * @param {HTMLElement} container
   *   Contenedor de la sección de backups.
   * @param {Object} data
   *   Datos de estado del API.
   */
  Drupal.jarabaDr.renderBackupStatus = function (container, data) {
    var cards = container.querySelectorAll('.backup-status__card');
    cards.forEach(function (card) {
      var status = card.getAttribute('data-status');
      if (status) {
        // Aplicar clase de estado.
        card.classList.add('backup-status__card--' + status);
      }
    });

    // Actualizar timestamp de última actualización si existe.
    var timestamp = container.querySelector('.dr-dashboard__updated');
    if (timestamp && data.last_updated) {
      timestamp.textContent = Drupal.t('Última actualización: @time', {
        '@time': new Date(data.last_updated * 1000).toLocaleString('es-ES')
      });
    }
  };

  /**
   * Inicializa el widget de incidentes recientes.
   *
   * @param {HTMLElement} dashboard
   *   Elemento raíz del dashboard.
   */
  Drupal.jarabaDr.initIncidents = function (dashboard) {
    var section = dashboard.querySelector('.dr-dashboard__incidents');
    if (!section) {
      return;
    }

    // Cargar incidentes activos via API.
    fetch('/api/v1/dr/incidents', {
      headers: { 'Content-Type': 'application/json' }
    })
    .then(function (response) { return response.json(); })
    .then(function (data) {
      if (data.success && data.data) {
        Drupal.jarabaDr.renderIncidents(section, data.data);
      }
    })
    .catch(function (error) {
      console.warn('Error al cargar incidentes:', error);
    });
  };

  /**
   * Renderiza la lista de incidentes.
   *
   * @param {HTMLElement} container
   *   Contenedor de la sección de incidentes.
   * @param {Array} incidents
   *   Lista de incidentes del API.
   */
  Drupal.jarabaDr.renderIncidents = function (container, incidents) {
    var list = container.querySelector('.incident-timeline__list');
    if (!list || !Array.isArray(incidents)) {
      return;
    }

    // Aplicar clases de severidad a cada entrada.
    incidents.forEach(function (incident) {
      var entry = list.querySelector('[data-incident-id="' + incident.id + '"]');
      if (entry && incident.severity) {
        entry.classList.add('incident-timeline__entry--' + incident.severity);
      }
    });
  };

  /**
   * Inicializa el widget de resultados de tests DR.
   *
   * @param {HTMLElement} dashboard
   *   Elemento raíz del dashboard.
   */
  Drupal.jarabaDr.initTestResults = function (dashboard) {
    var section = dashboard.querySelector('.dr-dashboard__tests');
    if (!section) {
      return;
    }

    // Cargar resultados de tests via API.
    fetch('/api/v1/dr/tests', {
      headers: { 'Content-Type': 'application/json' }
    })
    .then(function (response) { return response.json(); })
    .then(function (data) {
      if (data.success && data.data) {
        Drupal.jarabaDr.renderTestResults(section, data.data);
      }
    })
    .catch(function (error) {
      console.warn('Error al cargar resultados de tests DR:', error);
    });
  };

  /**
   * Renderiza los resultados de tests DR.
   *
   * @param {HTMLElement} container
   *   Contenedor de la sección de tests.
   * @param {Array} tests
   *   Lista de resultados de tests del API.
   */
  Drupal.jarabaDr.renderTestResults = function (container, tests) {
    var cards = container.querySelectorAll('.dr-test');
    if (!cards.length || !Array.isArray(tests)) {
      return;
    }

    // Aplicar clases de estado a cada tarjeta.
    cards.forEach(function (card) {
      var status = card.getAttribute('data-status');
      if (status) {
        card.classList.add('dr-test--' + status);
      }
    });
  };

})(Drupal, once);

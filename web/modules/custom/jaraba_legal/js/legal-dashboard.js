/**
 * @file
 * JavaScript del dashboard de compliance legal.
 *
 * Proporciona interactividad al dashboard: carga de estado ToS,
 * metricas SLA, uso de recursos y actualizacion de widgets.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Comportamiento del dashboard legal.
   */
  Drupal.behaviors.jarabaLegalDashboard = {
    attach: function (context) {
      once('jaraba-legal-dashboard', '.jaraba-legal-dashboard', context).forEach(function (element) {
        Drupal.jarabaLegal = Drupal.jarabaLegal || {};

        // Inicializar componentes del dashboard.
        Drupal.jarabaLegal.initTosStatus(element);
        Drupal.jarabaLegal.initSlaMetrics(element);
        Drupal.jarabaLegal.initUsageStatus(element);
      });
    }
  };

  /**
   * Utilidades del dashboard legal.
   */
  Drupal.jarabaLegal = Drupal.jarabaLegal || {};

  /**
   * Inicializa el widget de estado ToS.
   *
   * Consulta el endpoint de ToS actual y actualiza el badge de estado.
   */
  Drupal.jarabaLegal.initTosStatus = function (dashboard) {
    var tosSection = dashboard.querySelector('.legal-dashboard__card--tos');
    if (!tosSection) {
      return;
    }

    fetch('/api/v1/legal/tos/current', {
      headers: { 'Content-Type': 'application/json' }
    })
    .then(function (response) { return response.json(); })
    .then(function (data) {
      if (data.success && data.data) {
        Drupal.jarabaLegal.renderTosStatus(tosSection, data.data);
      }
    })
    .catch(function (error) {
      console.warn('Error al cargar estado ToS:', error);
    });
  };

  /**
   * Renderiza el estado ToS en el widget.
   */
  Drupal.jarabaLegal.renderTosStatus = function (container, data) {
    var badge = container.querySelector('.tos-status__badge');
    if (!badge) {
      return;
    }

    // Limpiar clases de estado previas.
    badge.className = 'tos-status__badge';

    if (data.accepted) {
      badge.classList.add('tos-status__badge--accepted');
      badge.textContent = Drupal.t('Aceptado');
    }
    else if (data.active_version) {
      badge.classList.add('tos-status__badge--pending');
      badge.textContent = Drupal.t('Pendiente de aceptacion');
    }
    else {
      badge.classList.add('tos-status__badge--expired');
      badge.textContent = Drupal.t('Sin version activa');
    }

    // Actualizar version mostrada.
    var versionEl = container.querySelector('.tos-status__version');
    if (versionEl && data.active_version) {
      versionEl.textContent = 'v' + data.active_version;
    }
  };

  /**
   * Inicializa el widget de metricas SLA.
   *
   * Consulta el endpoint de SLA actual y renderiza gauge + metricas.
   */
  Drupal.jarabaLegal.initSlaMetrics = function (dashboard) {
    var slaSection = dashboard.querySelector('.legal-dashboard__card--sla');
    if (!slaSection) {
      return;
    }

    // Obtener tenant_id del atributo data del dashboard.
    var tenantId = dashboard.getAttribute('data-tenant-id') || '0';

    fetch('/api/v1/legal/sla/' + tenantId + '/current', {
      headers: { 'Content-Type': 'application/json' }
    })
    .then(function (response) { return response.json(); })
    .then(function (data) {
      if (data.success && data.data) {
        Drupal.jarabaLegal.renderSlaMetrics(slaSection, data.data);
      }
    })
    .catch(function (error) {
      console.warn('Error al cargar metricas SLA:', error);
    });
  };

  /**
   * Renderiza las metricas SLA en el widget.
   */
  Drupal.jarabaLegal.renderSlaMetrics = function (container, data) {
    // Actualizar gauge si existe.
    var gauge = container.querySelector('.sla-metrics__gauge');
    if (gauge && data.uptime_percentage !== undefined) {
      gauge.style.setProperty('--sla-percentage', data.uptime_percentage + '%');
    }

    // Actualizar texto del gauge.
    var gaugeText = container.querySelector('.sla-metrics__gauge-text');
    if (gaugeText && data.uptime_percentage !== undefined) {
      gaugeText.textContent = data.uptime_percentage + '%';
    }

    // Actualizar valores de metricas.
    var uptimeEl = container.querySelector('[data-metric="uptime"]');
    if (uptimeEl && data.uptime_percentage !== undefined) {
      uptimeEl.textContent = data.uptime_percentage + '%';
    }

    var downtimeEl = container.querySelector('[data-metric="downtime"]');
    if (downtimeEl && data.downtime_minutes !== undefined) {
      downtimeEl.textContent = data.downtime_minutes + ' min';
    }

    var creditEl = container.querySelector('[data-metric="credit"]');
    if (creditEl && data.credit_percentage !== undefined) {
      creditEl.textContent = data.credit_percentage + '%';
    }
  };

  /**
   * Inicializa el widget de estado de uso de recursos.
   *
   * Consulta el endpoint de AUP/usage y renderiza barras de progreso.
   */
  Drupal.jarabaLegal.initUsageStatus = function (dashboard) {
    var usageSection = dashboard.querySelector('.legal-dashboard__card--usage');
    if (!usageSection) {
      return;
    }

    fetch('/api/v1/legal/aup/usage', {
      headers: { 'Content-Type': 'application/json' }
    })
    .then(function (response) { return response.json(); })
    .then(function (data) {
      if (data.success && data.data) {
        Drupal.jarabaLegal.renderUsageStatus(usageSection, data.data);
      }
    })
    .catch(function (error) {
      console.warn('Error al cargar estado de uso:', error);
    });
  };

  /**
   * Renderiza el estado de uso en el widget.
   */
  Drupal.jarabaLegal.renderUsageStatus = function (container, limits) {
    if (!Array.isArray(limits)) {
      return;
    }

    limits.forEach(function (limit) {
      var barFill = container.querySelector('[data-usage-type="' + limit.limit_type + '"] .aup-usage__bar-fill');
      if (barFill) {
        var percentage = Math.min(limit.percentage, 100);
        barFill.style.width = percentage + '%';

        // Limpiar clases de estado.
        barFill.className = 'aup-usage__bar-fill';

        if (limit.exceeded) {
          barFill.classList.add('aup-usage__bar-fill--exceeded');
        }
        else if (limit.near_limit) {
          barFill.classList.add('aup-usage__bar-fill--warning');
        }
        else {
          barFill.classList.add('aup-usage__bar-fill--safe');
        }
      }

      // Actualizar valor numerico.
      var valueEl = container.querySelector('[data-usage-type="' + limit.limit_type + '"] .aup-usage__bar-value');
      if (valueEl) {
        valueEl.textContent = limit.current_usage + ' / ' + limit.limit_value + ' (' + limit.percentage + '%)';
      }
    });
  };

})(Drupal, once);

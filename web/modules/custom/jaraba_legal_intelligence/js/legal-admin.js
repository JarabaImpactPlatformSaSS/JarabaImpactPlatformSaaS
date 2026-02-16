/**
 * @file
 * Legal Intelligence Hub — Dashboard admin frontend.
 *
 * Auto-refresco de estadísticas y estado de fuentes cada 30 segundos.
 * Manejo AJAX de botones de sincronización forzada con feedback visual.
 * Los endpoints API son inyectados via drupalSettings.legalAdmin.
 */

(function (Drupal, drupalSettings, once) {

  'use strict';

  /**
   * Intervalo de refresco automático en milisegundos.
   *
   * @type {number}
   */
  var REFRESH_INTERVAL = 30000;

  /**
   * URLs de los endpoints API del dashboard admin.
   *
   * @type {Object<string, string>}
   */
  var apiUrls = (drupalSettings.legalAdmin)
    ? drupalSettings.legalAdmin
    : { statsUrl: '/api/v1/admin/legal/stats', sourcesUrl: '/api/v1/admin/legal/sources' };

  Drupal.behaviors.legalAdmin = {
    attach: function (context) {
      once('legal-admin', '.legal-admin-dashboard', context).forEach(function (dashboard) {
        // Cargar datos iniciales.
        refreshStats(dashboard);
        refreshSources(dashboard);

        // Auto-refresco periódico.
        var intervalId = setInterval(function () {
          refreshStats(dashboard);
          refreshSources(dashboard);
        }, REFRESH_INTERVAL);

        // Limpiar intervalo si el dashboard se desmonta.
        dashboard.addEventListener('destroy', function () {
          clearInterval(intervalId);
        });

        // Delegación de eventos para botones de sync.
        dashboard.addEventListener('click', function (e) {
          var syncBtn = e.target.closest('[data-sync-source]');
          if (syncBtn) {
            e.preventDefault();
            handleSyncClick(syncBtn);
          }
        });
      });
    }
  };

  /**
   * Refresca las estadísticas KPI del dashboard.
   *
   * @param {HTMLElement} dashboard - Contenedor del dashboard.
   */
  function refreshStats(dashboard) {
    var kpiSection = dashboard.querySelector('.legal-admin-dashboard__kpis');
    if (!kpiSection) {
      return;
    }

    fetch(apiUrls.statsUrl, {
      method: 'GET',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin'
    })
    .then(function (response) { return response.json(); })
    .then(function (data) {
      if (!data.success) {
        return;
      }

      var stats = data.data;
      updateKpiValue(kpiSection, 0, formatNumber(stats.total_resolutions || 0));
      updateKpiValue(kpiSection, 1, formatNumber(stats.national_count || 0));
      updateKpiValue(kpiSection, 2, formatNumber(stats.eu_count || 0));
      updateKpiValue(kpiSection, 3, String(stats.pipeline_errors || 0));
    })
    .catch(function () {
      // Silenciar errores de refresco — se reintentará en el próximo ciclo.
    });
  }

  /**
   * Refresca el estado de las fuentes de datos.
   *
   * @param {HTMLElement} dashboard - Contenedor del dashboard.
   */
  function refreshSources(dashboard) {
    var sourcesSection = dashboard.querySelector('.legal-admin-dashboard__sources');
    if (!sourcesSection) {
      return;
    }

    fetch(apiUrls.sourcesUrl, {
      method: 'GET',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin'
    })
    .then(function (response) { return response.json(); })
    .then(function (data) {
      if (!data.success || !data.data) {
        return;
      }

      var sources = data.data;
      sources.forEach(function (source) {
        var card = sourcesSection.querySelector('[data-source-id="' + source.machine_name + '"]');
        if (!card) {
          return;
        }

        // Actualizar contadores.
        var docsEl = card.querySelector('.legal-source-status__docs');
        if (docsEl) {
          docsEl.textContent = Drupal.t('@count documents', { '@count': source.total_documents });
        }

        var errorsEl = card.querySelector('.legal-source-status__errors');
        if (errorsEl) {
          errorsEl.textContent = source.error_count > 0
            ? Drupal.t('@count errors', { '@count': source.error_count })
            : '';
          errorsEl.style.display = source.error_count > 0 ? '' : 'none';
        }

        // Actualizar badge de estado.
        var badgeEl = card.querySelector('.legal-source-status__badge');
        if (badgeEl) {
          badgeEl.classList.toggle('is-active', source.is_active);
          badgeEl.textContent = source.is_active ? Drupal.t('Active') : Drupal.t('Inactive');
        }
      });
    })
    .catch(function () {
      // Silenciar errores de refresco.
    });
  }

  /**
   * Maneja el clic en un botón de sincronización forzada.
   *
   * @param {HTMLElement} btn - Botón de sync con data-sync-source.
   */
  function handleSyncClick(btn) {
    var sourceId = btn.dataset.syncSource;
    if (!sourceId) {
      return;
    }

    // Feedback visual: deshabilitar botón y mostrar spinner.
    var originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = Drupal.t('Syncing...');
    btn.classList.add('is-syncing');

    // Navegar a la ruta de sync (que redirige de vuelta al dashboard).
    window.location.href = '/admin/config/jaraba/legal-intelligence/sync/' + sourceId;
  }

  /**
   * Actualiza el valor de un KPI por índice.
   *
   * @param {HTMLElement} section - Sección de KPIs.
   * @param {number} index - Índice del KPI (0-based).
   * @param {string} value - Nuevo valor a mostrar.
   */
  function updateKpiValue(section, index, value) {
    var kpis = section.querySelectorAll('.legal-admin-dashboard__kpi-value');
    if (kpis[index]) {
      kpis[index].textContent = value;
    }
  }

  /**
   * Formatea un número con separador de miles.
   *
   * @param {number} num - Número a formatear.
   * @returns {string} Número formateado.
   */
  function formatNumber(num) {
    return Number(num).toLocaleString();
  }

})(Drupal, drupalSettings, once);

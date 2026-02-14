/**
 * @file
 * Admin Center Finance — Dashboard initializer.
 *
 * Fetches metrics from API and hydrates scorecards, metrics table,
 * and tenant analytics DataTable.
 *
 * F6 — Doc 181 / Spec f104 §FASE 4.
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.adminCenterFinanceInit = {
    attach(context) {
      once('ac-finance-init', '.admin-center-finance', context).forEach(el => {
        const settings = drupalSettings.adminCenter || {};
        const metricsUrl = settings.financeMetricsUrl || '/api/v1/admin/finance/metrics';
        const tenantsUrl = settings.financeTenantsUrl || '/api/v1/admin/finance/tenants';

        // Fetch and render metrics.
        fetchMetrics(metricsUrl);

        // Init tenant DataTable.
        initTenantTable(tenantsUrl);
      });
    },
  };

  /**
   * Fetch finance metrics and populate scorecards + metrics table.
   */
  async function fetchMetrics(url) {
    try {
      const res = await fetch(url, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });
      const json = await res.json();

      if (!json.success) return;

      const data = json.data;

      // Populate scorecards.
      if (data.scorecards) {
        Object.entries(data.scorecards).forEach(([key, card]) => {
          const el = document.querySelector(`[data-metric="${key}"]`);
          if (!el) return;

          const valueEl = el.querySelector('[data-value]');
          if (valueEl) {
            valueEl.textContent = formatValue(card.value, card.format);
          }
        });
      }

      // Populate metrics table.
      if (data.metrics_table) {
        renderMetricsTable(data.metrics_table);
      }
    }
    catch (err) {
      // Silently fail — skeleton stays visible.
    }
  }

  /**
   * Render the SaaS metrics table with benchmark bars.
   */
  function renderMetricsTable(metrics) {
    const tbody = document.getElementById('finance-metrics-body');
    if (!tbody) return;

    let html = '';
    metrics.forEach(m => {
      const healthClass = `ac-finance__health ac-finance__health--${m.health}`;
      const healthLabel = getHealthLabel(m.health);
      const displayValue = m.value !== 0
        ? `${formatNumber(m.value)}${m.unit}`
        : '—';

      const benchmarkText = m.benchmark_good
        ? `${m.key === 'cac_payback' || m.key === 'logo_churn' || m.key === 'revenue_churn' ? '<' : '>'}${m.benchmark_good}${m.unit}`
        : '—';

      html += '<tr>';
      html += `<td class="ac-finance__metric-name">${escapeHtml(String(m.label))}</td>`;
      html += `<td class="ac-finance__metric-value"><strong>${displayValue}</strong></td>`;
      html += `<td class="ac-finance__metric-benchmark">${benchmarkText}</td>`;
      html += `<td><span class="${healthClass}">${healthLabel}</span></td>`;
      html += '</tr>';
    });

    tbody.innerHTML = html;
  }

  /**
   * Initialize tenant analytics as a simple rendered table (not DataTable).
   */
  async function initTenantTable(url) {
    const container = document.getElementById('finance-tenant-table');
    if (!container) return;

    try {
      const res = await fetch(url, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });
      const json = await res.json();

      if (!json.success || !Array.isArray(json.data) || json.data.length === 0) {
        container.innerHTML = `<p class="ac-datatable__empty">${Drupal.t('No hay datos de analytics por tenant disponibles.')}</p>`;
        return;
      }

      let html = '<div class="ac-datatable__table-wrap"><table class="ac-datatable__table"><thead><tr>';
      html += `<th class="ac-datatable__th">${Drupal.t('Tenant')}</th>`;
      html += `<th class="ac-datatable__th">${Drupal.t('MRR')}</th>`;
      html += `<th class="ac-datatable__th">${Drupal.t('LTV')}</th>`;
      html += `<th class="ac-datatable__th">${Drupal.t('CAC')}</th>`;
      html += `<th class="ac-datatable__th">${Drupal.t('LTV:CAC')}</th>`;
      html += `<th class="ac-datatable__th">${Drupal.t('Estado')}</th>`;
      html += '</tr></thead><tbody>';

      json.data.forEach(t => {
        const healthClass = `ac-datatable__badge ac-datatable__badge--${t.health_status || 'unknown'}`;
        html += '<tr class="ac-datatable__row">';
        html += `<td class="ac-datatable__td"><strong>${escapeHtml(t.name || '')}</strong></td>`;
        html += `<td class="ac-datatable__td">${formatCurrency(t.mrr || 0)}</td>`;
        html += `<td class="ac-datatable__td">${formatCurrency(t.ltv || 0)}</td>`;
        html += `<td class="ac-datatable__td">${formatCurrency(t.cac || 0)}</td>`;
        html += `<td class="ac-datatable__td">${formatNumber(t.ltv_cac_ratio || 0)}x</td>`;
        html += `<td class="ac-datatable__td"><span class="${healthClass}">${escapeHtml(t.health_status || '—')}</span></td>`;
        html += '</tr>';
      });

      html += '</tbody></table></div>';
      container.innerHTML = html;
    }
    catch (err) {
      container.innerHTML = `<p class="ac-datatable__empty">${Drupal.t('Error al cargar analytics.')}</p>`;
    }
  }

  // Helpers.
  function formatValue(value, format) {
    if (format === 'currency') return formatCurrency(value);
    if (format === 'number') return formatNumber(value);
    return String(value);
  }

  function formatCurrency(val) {
    return new Intl.NumberFormat('es-ES', {
      style: 'currency',
      currency: 'EUR',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(val);
  }

  function formatNumber(val) {
    return new Intl.NumberFormat('es-ES', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 2,
    }).format(val);
  }

  function getHealthLabel(health) {
    const labels = {
      good: Drupal.t('Saludable'),
      warning: Drupal.t('Atención'),
      danger: Drupal.t('Crítico'),
      neutral: '—',
    };
    return labels[health] || '—';
  }

  function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

})(Drupal, drupalSettings, once);

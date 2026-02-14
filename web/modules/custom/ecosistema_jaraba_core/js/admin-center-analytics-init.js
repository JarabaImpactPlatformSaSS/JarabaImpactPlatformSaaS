/**
 * @file
 * Admin Center Analytics — Dashboard initializer.
 *
 * Fetches overview data and renders Chart.js charts + AI telemetry table.
 *
 * F6 — Doc 181 / Spec f104 §FASE 6.
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.adminCenterAnalyticsInit = {
    attach(context) {
      once('ac-analytics-init', '.admin-center-analytics', context).forEach(() => {
        const settings = drupalSettings.adminCenter || {};

        fetchOverview(settings.analyticsOverviewUrl || '/api/v1/admin/analytics/overview');
        fetchAiTelemetry(settings.analyticsAiUrl || '/api/v1/admin/analytics/ai');
      });
    },
  };

  // ===========================================================================
  // OVERVIEW
  // ===========================================================================

  async function fetchOverview(url) {
    try {
      const res = await fetch(url, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });
      const json = await res.json();
      if (!json.success) return;

      const data = json.data;

      // Populate scorecards.
      if (data.scorecards) {
        Object.entries(data.scorecards).forEach(([key, val]) => {
          const card = document.querySelector(`[data-scorecard="${key}"]`);
          if (!card) return;
          const valueEl = card.querySelector('[data-value]');
          if (valueEl) {
            valueEl.textContent = key === 'ai_cost_30d'
              ? formatCurrency(val)
              : formatNumber(val);
          }
        });
      }

      // Render charts if Chart.js loaded.
      if (typeof Chart !== 'undefined') {
        if (data.mrr_trend) renderLineChart('chart-mrr-trend', data.mrr_trend, 'MRR (€)', '#233d63');
        if (data.tenant_growth) renderLineChart('chart-tenant-growth', data.tenant_growth, Drupal.t('Tenants'), '#00a9a5');
        if (data.activity_trend) renderBarChart('chart-activity-trend', data.activity_trend, Drupal.t('Eventos'), '#ff8c42');
      }
    }
    catch (err) {
      // Silently fail.
    }
  }

  // ===========================================================================
  // AI TELEMETRY
  // ===========================================================================

  async function fetchAiTelemetry(url) {
    try {
      const res = await fetch(url, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });
      const json = await res.json();
      if (!json.success) return;

      const data = json.data;

      // Totals.
      const invEl = document.querySelector('[data-stat="ai_invocations"]');
      if (invEl) invEl.textContent = formatNumber(data.totals?.invocations || 0);

      const latEl = document.querySelector('[data-stat="ai_avg_latency"]');
      if (latEl) latEl.textContent = formatNumber(data.totals?.avg_latency || 0);

      // Update scorecard cost if available from AI data.
      const costCard = document.querySelector('[data-scorecard="ai_cost_30d"]');
      if (costCard && data.totals?.cost) {
        const v = costCard.querySelector('[data-value]');
        if (v) v.textContent = formatCurrency(data.totals.cost);
      }

      // Render agents table.
      renderAiTable(data.agents || []);
    }
    catch (err) {
      // Silently fail.
    }
  }

  function renderAiTable(agents) {
    const tbody = document.getElementById('ai-agents-body');
    if (!tbody) return;

    if (agents.length === 0) {
      tbody.innerHTML = `<tr><td colspan="5" class="ac-datatable__empty">${Drupal.t('No hay datos de telemetria AI disponibles.')}</td></tr>`;
      return;
    }

    let html = '';
    agents.forEach(a => {
      const successRate = parseFloat(a.success_rate || 0).toFixed(1);
      const healthClass = successRate >= 95 ? 'good' : (successRate >= 80 ? 'warning' : 'danger');

      html += '<tr>';
      html += `<td class="ac-finance__metric-name">${escapeHtml(a.agent_id || '')}</td>`;
      html += `<td class="ac-finance__metric-value">${formatNumber(a.total_invocations || 0)}</td>`;
      html += `<td><span class="ac-finance__health ac-finance__health--${healthClass}">${successRate}%</span></td>`;
      html += `<td class="ac-finance__metric-value">${formatNumber(Math.round(a.avg_latency_ms || 0))}ms</td>`;
      html += `<td class="ac-finance__metric-value">${formatCurrency(a.total_cost || 0)}</td>`;
      html += '</tr>';
    });

    tbody.innerHTML = html;
  }

  // ===========================================================================
  // CHART RENDERERS
  // ===========================================================================

  function renderLineChart(canvasId, trendData, label, color) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;

    new Chart(ctx, {
      type: 'line',
      data: {
        labels: trendData.labels,
        datasets: [{
          label: label,
          data: trendData.data,
          borderColor: color,
          backgroundColor: color + '1a',
          fill: true,
          tension: 0.3,
          pointRadius: 3,
          pointHoverRadius: 5,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
        },
        scales: {
          x: {
            grid: { display: false },
            ticks: { font: { size: 11 }, color: '#757575' },
          },
          y: {
            beginAtZero: true,
            grid: { color: '#f5f5f5' },
            ticks: { font: { size: 11 }, color: '#757575' },
          },
        },
      },
    });
  }

  function renderBarChart(canvasId, trendData, label, color) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;

    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: trendData.labels,
        datasets: [{
          label: label,
          data: trendData.data,
          backgroundColor: color + '99',
          borderRadius: 4,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
        },
        scales: {
          x: {
            grid: { display: false },
            ticks: { font: { size: 11 }, color: '#757575' },
          },
          y: {
            beginAtZero: true,
            grid: { color: '#f5f5f5' },
            ticks: { font: { size: 11 }, color: '#757575' },
          },
        },
      },
    });
  }

  // ===========================================================================
  // HELPERS
  // ===========================================================================

  function formatCurrency(val) {
    return new Intl.NumberFormat('es-ES', {
      style: 'currency', currency: 'EUR',
      minimumFractionDigits: 0, maximumFractionDigits: 2,
    }).format(val);
  }

  function formatNumber(val) {
    return new Intl.NumberFormat('es-ES').format(val);
  }

  function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

})(Drupal, drupalSettings, once);

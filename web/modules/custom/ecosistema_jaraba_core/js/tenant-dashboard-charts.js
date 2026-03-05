/**
 * @file
 * Tenant Dashboard Charts - Chart.js Integration
 *
 * P1-04: Renderiza gráficos de tendencias del dashboard del tenant
 * usando datos inyectados via drupalSettings (ROUTE-LANGPREFIX-001).
 *
 * Datos esperados en drupalSettings.tenantDashboard.charts:
 * - sales: {type, labels, datasets, summary}
 * - mrr: {type, labels, datasets, summary}
 * - customers: {type, labels, datasets}
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.tenantDashboardCharts = {
    attach: function (context) {
      var charts = (drupalSettings.tenantDashboard || {}).charts;
      if (!charts) {
        return;
      }

      once('tenant-dashboard-charts', '.tenant-chart-container', context).forEach(function (container) {
        var chartType = container.dataset.chartType;
        var chartData = charts[chartType];

        if (chartData) {
          Drupal.tenantCharts.render(container, chartData);
        }
        else {
          Drupal.tenantCharts.showEmpty(container);
        }
      });
    }
  };

  Drupal.tenantCharts = {

    /**
     * Opciones base de Chart.js.
     */
    baseOptions: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: true,
          position: 'bottom',
          labels: {
            padding: 20,
            usePointStyle: true,
            font: { family: "'Inter', sans-serif", size: 12 }
          }
        },
        tooltip: {
          backgroundColor: 'rgba(30, 41, 59, 0.95)',
          titleFont: { family: "'Inter', sans-serif", size: 14, weight: 'bold' },
          bodyFont: { family: "'Inter', sans-serif", size: 12 },
          padding: 12,
          cornerRadius: 8,
          displayColors: true,
          callbacks: {}
        }
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: {
            font: { family: "'Inter', sans-serif", size: 11 },
            color: '#94a3b8',
            maxRotation: 45
          }
        },
        y: {
          beginAtZero: true,
          grid: { color: 'rgba(148, 163, 184, 0.1)' },
          ticks: {
            font: { family: "'Inter', sans-serif", size: 11 },
            color: '#94a3b8'
          }
        }
      }
    },

    /**
     * Renderiza un gráfico dentro de su contenedor.
     */
    render: function (container, chartData) {
      var canvas = container.querySelector('canvas');
      if (!canvas) {
        return;
      }

      var ctx = canvas.getContext('2d');
      var options = JSON.parse(JSON.stringify(this.baseOptions));
      var locale = (drupalSettings.tenantDashboard || {}).locale || 'es-ES';

      // Callback de tooltip para formato moneda.
      if (chartData.type === 'bar' || chartData.type === 'line') {
        options.plugins.tooltip.callbacks = {
          label: function (context) {
            var label = context.dataset.label || '';
            var value = context.parsed.y;
            if (typeof value === 'number') {
              value = value.toLocaleString(locale, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
            return label + ': ' + value + ' \u20AC';
          }
        };
      }

      // Opciones específicas por tipo.
      if (chartData.type === 'line') {
        options.elements = {
          point: { radius: 3, hoverRadius: 6 },
          line: { borderWidth: 2 }
        };
      }

      if (chartData.type === 'bar') {
        options.plugins.legend.display = chartData.datasets.length > 1;
      }

      // Verificar si hay datos reales (no todos cero).
      var hasData = false;
      for (var d = 0; d < chartData.datasets.length; d++) {
        var dataArr = chartData.datasets[d].data;
        for (var i = 0; i < dataArr.length; i++) {
          if (dataArr[i] > 0) {
            hasData = true;
            break;
          }
        }
        if (hasData) {
          break;
        }
      }

      if (!hasData) {
        this.showEmpty(container);
        return;
      }

      new Chart(ctx, {
        type: chartData.type,
        data: {
          labels: chartData.labels,
          datasets: chartData.datasets
        },
        options: options
      });

      // Renderizar resumen si existe.
      if (chartData.summary) {
        this.renderSummary(container, chartData.summary, locale);
      }
    },

    /**
     * Renderiza el resumen debajo del gráfico.
     */
    renderSummary: function (container, summary, locale) {
      var summaryEl = container.querySelector('.tenant-chart-summary');
      if (!summaryEl) {
        return;
      }

      var labels = {
        total: Drupal.t('Total'),
        average: Drupal.t('Promedio'),
        current: Drupal.t('Actual'),
        growth: Drupal.t('Crecimiento')
      };

      var html = '';
      for (var key in summary) {
        if (!summary.hasOwnProperty(key)) {
          continue;
        }
        var value = summary[key];
        var label = labels[key] || key;
        var formatted;

        if (key === 'growth') {
          var cls = value > 0 ? 'trend--up' : (value < 0 ? 'trend--down' : '');
          var sign = value > 0 ? '+' : '';
          formatted = '<span class="tenant-chart-summary__trend ' + cls + '">' + sign + value + '%</span>';
        }
        else if (typeof value === 'number') {
          formatted = value.toLocaleString(locale, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' \u20AC';
        }
        else {
          formatted = Drupal.checkPlain(String(value));
        }

        html += '<div class="tenant-chart-summary__item">';
        html += '<span class="tenant-chart-summary__label">' + Drupal.checkPlain(label) + '</span>';
        html += '<span class="tenant-chart-summary__value">' + formatted + '</span>';
        html += '</div>';
      }

      summaryEl.innerHTML = html;
    },

    /**
     * Muestra estado vacío cuando no hay datos.
     */
    showEmpty: function (container) {
      var canvasWrapper = container.querySelector('.tenant-chart-canvas-wrapper');
      if (canvasWrapper) {
        canvasWrapper.innerHTML =
          '<div class="tenant-chart-empty">' +
          '<span class="tenant-chart-empty__icon" aria-hidden="true">' +
          '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--ej-color-text-muted, #94a3b8)" stroke-width="1.5">' +
          '<path d="M3 3v18h18"/><path d="M7 16l4-8 4 4 6-6"/>' +
          '</svg>' +
          '</span>' +
          '<span class="tenant-chart-empty__text">' + Drupal.t('A\u00fan no hay datos suficientes') + '</span>' +
          '<span class="tenant-chart-empty__hint">' + Drupal.t('Los datos aparecer\u00e1n a medida que tu negocio genere actividad.') + '</span>' +
          '</div>';
      }
    }

  };

})(Drupal, drupalSettings, once);

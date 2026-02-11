/**
 * @file
 * Tenant Dashboard Charts - Chart.js Integration
 *
 * Gestiona los gráficos de tendencias del dashboard del tenant
 * utilizando Chart.js para visualización de datos.
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Comportamiento de gráficos del Tenant Dashboard.
   */
  Drupal.behaviors.tenantDashboardCharts = {
    attach: function (context, settings) {
      once('tenant-dashboard-charts', '.tenant-chart-container', context).forEach(function (container) {
        Drupal.tenantCharts.init(container);
      });
    }
  };

  /**
   * Namespace para gráficos del tenant.
   */
  Drupal.tenantCharts = {

    /**
     * Configuración por defecto de Chart.js.
     */
    defaultOptions: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: true,
          position: 'bottom',
          labels: {
            padding: 20,
            usePointStyle: true,
            font: {
              family: "'Inter', sans-serif",
              size: 12
            }
          }
        },
        tooltip: {
          backgroundColor: 'rgba(30, 41, 59, 0.95)',
          titleFont: {
            family: "'Inter', sans-serif",
            size: 14,
            weight: 'bold'
          },
          bodyFont: {
            family: "'Inter', sans-serif",
            size: 12
          },
          padding: 12,
          cornerRadius: 8,
          displayColors: true
        }
      },
      scales: {
        x: {
          grid: {
            display: false
          },
          ticks: {
            font: {
              family: "'Inter', sans-serif",
              size: 11
            },
            color: '#94a3b8'
          }
        },
        y: {
          beginAtZero: true,
          grid: {
            color: 'rgba(148, 163, 184, 0.1)'
          },
          ticks: {
            font: {
              family: "'Inter', sans-serif",
              size: 11
            },
            color: '#94a3b8'
          }
        }
      }
    },

    /**
     * Inicializa los gráficos.
     */
    init: function (container) {
      const chartType = container.dataset.chartType;
      const chartId = container.dataset.chartId;
      const canvas = container.querySelector('canvas');

      if (!canvas) {
        console.warn('No canvas found in chart container');
        return;
      }

      // Cargar datos desde la API.
      this.loadChartData(chartType, function (data) {
        if (data && data.success) {
          Drupal.tenantCharts.renderChart(canvas, data.chart, data.summary);
        } else {
          Drupal.tenantCharts.showError(container);
        }
      });
    },

    /**
     * Carga datos desde la API.
     */
    loadChartData: function (chartType, callback) {
      const endpoints = {
        sales: '/api/tenant/analytics/sales',
        mrr: '/api/tenant/analytics/mrr',
        customers: '/api/tenant/analytics/customers'
      };

      const url = endpoints[chartType];
      if (!url) {
        callback(null);
        return;
      }

      fetch(url, {
        method: 'GET',
        headers: {
          'Accept': 'application/json'
        },
        credentials: 'same-origin'
      })
        .then(function (response) {
          return response.json();
        })
        .then(function (data) {
          callback(data);
        })
        .catch(function (error) {
          console.error('Error loading chart data:', error);
          callback(null);
        });
    },

    /**
     * Renderiza el gráfico con Chart.js.
     */
    renderChart: function (canvas, chartConfig, summary) {
      const ctx = canvas.getContext('2d');

      // Configuración específica por tipo de gráfico.
      let options = JSON.parse(JSON.stringify(this.defaultOptions));

      if (chartConfig.type === 'line') {
        options.elements = {
          point: {
            radius: 3,
            hoverRadius: 6,
            backgroundColor: '#3b82f6'
          },
          line: {
            borderWidth: 2
          }
        };
      }

      if (chartConfig.type === 'bar') {
        options.plugins.legend.display = chartConfig.datasets.length > 1;
      }

      // Crear el gráfico.
      new Chart(ctx, {
        type: chartConfig.type,
        data: {
          labels: chartConfig.labels,
          datasets: chartConfig.datasets
        },
        options: options
      });

      // Mostrar resumen si existe.
      if (summary) {
        this.updateSummary(canvas.closest('.tenant-chart-container'), summary);
      }
    },

    /**
     * Actualiza el resumen del gráfico.
     */
    updateSummary: function (container, summary) {
      const summaryEl = container.querySelector('.tenant-chart-summary');
      if (!summaryEl) return;

      let html = '';
      for (const [key, value] of Object.entries(summary)) {
        const label = this.formatLabel(key);
        const formattedValue = typeof value === 'number' ? 
          (key.includes('growth') ? value + '%' : '€' + value.toLocaleString('es-ES', { minimumFractionDigits: 2 })) : 
          value;
        
        html += '<div class="tenant-chart-summary__item">';
        html += '<span class="tenant-chart-summary__label">' + label + '</span>';
        html += '<span class="tenant-chart-summary__value">' + formattedValue + '</span>';
        html += '</div>';
      }

      summaryEl.innerHTML = html;
    },

    /**
     * Formatea etiquetas de resumen.
     */
    formatLabel: function (key) {
      const labels = {
        total: 'Total',
        average: 'Promedio',
        current: 'Actual',
        growth: 'Crecimiento'
      };
      return labels[key] || key;
    },

    /**
     * Muestra error en el contenedor.
     */
    showError: function (container) {
      container.innerHTML = '<div class="tenant-chart-error">' +
        '<span class="tenant-chart-error__icon">⚠️</span>' +
        '<span class="tenant-chart-error__text">No se pudieron cargar los datos</span>' +
        '</div>';
    }
  };

})(Drupal, drupalSettings, once);

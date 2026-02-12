/**
 * @file
 * Dashboard de uso con integración Chart.js.
 *
 * Renderiza gráficos de evolución de uso y costes,
 * y gestiona el selector de periodo temporal.
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Paleta de colores para los datasets de gráficos.
   */
  var CHART_COLORS = [
    'rgba(54, 162, 235, 0.8)',
    'rgba(255, 99, 132, 0.8)',
    'rgba(75, 192, 192, 0.8)',
    'rgba(255, 206, 86, 0.8)',
    'rgba(153, 102, 255, 0.8)',
    'rgba(255, 159, 64, 0.8)',
    'rgba(199, 199, 199, 0.8)'
  ];

  var CHART_BORDERS = [
    'rgba(54, 162, 235, 1)',
    'rgba(255, 99, 132, 1)',
    'rgba(75, 192, 192, 1)',
    'rgba(255, 206, 86, 1)',
    'rgba(153, 102, 255, 1)',
    'rgba(255, 159, 64, 1)',
    'rgba(199, 199, 199, 1)'
  ];

  /**
   * Behavior para inicializar el dashboard de uso.
   */
  Drupal.behaviors.jarabaUsageDashboard = {
    attach: function (context) {
      var elements = once('jaraba-usage-dashboard', '.usage-dashboard', context);

      elements.forEach(function (element) {
        var settings = drupalSettings.jarabaUsageBilling || {};
        var chartData = settings.chartData || { labels: [], datasets: [] };

        // Inicializar gráfico principal de uso.
        var mainCanvas = element.querySelector('#usage-chart-main');
        if (mainCanvas && typeof Chart !== 'undefined') {
          initUsageChart(mainCanvas, chartData);
        }

        // Inicializar gráfico de costes.
        var costCanvas = element.querySelector('#usage-chart-cost');
        if (costCanvas && typeof Chart !== 'undefined') {
          var costSummary = settings.costSummary || {};
          initCostChart(costCanvas, costSummary);
        }

        // Inicializar selector de periodo.
        initPeriodSelector(element, settings.tenantId);
      });
    }
  };

  /**
   * Inicializa el gráfico de evolución de uso.
   *
   * @param {HTMLCanvasElement} canvas - Elemento canvas.
   * @param {Object} chartData - Datos con labels y datasets.
   */
  function initUsageChart(canvas, chartData) {
    var datasets = [];

    if (chartData.datasets && chartData.datasets.length > 0) {
      chartData.datasets.forEach(function (dataset, index) {
        var colorIndex = index % CHART_COLORS.length;
        datasets.push({
          label: dataset.label || 'Metric ' + index,
          data: dataset.data || [],
          backgroundColor: CHART_COLORS[colorIndex],
          borderColor: CHART_BORDERS[colorIndex],
          borderWidth: 2,
          fill: false,
          tension: 0.3
        });
      });
    }

    new Chart(canvas, {
      type: 'line',
      data: {
        labels: chartData.labels || [],
        datasets: datasets
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          title: {
            display: true,
            text: Drupal.t('Evolución de Uso'),
            font: { size: 16 }
          },
          legend: {
            position: 'bottom'
          }
        },
        scales: {
          x: {
            title: {
              display: true,
              text: Drupal.t('Periodo')
            }
          },
          y: {
            title: {
              display: true,
              text: Drupal.t('Cantidad')
            },
            beginAtZero: true
          }
        }
      }
    });
  }

  /**
   * Inicializa el gráfico de desglose de costes.
   *
   * @param {HTMLCanvasElement} canvas - Elemento canvas.
   * @param {Object} costSummary - Resumen de costes por métrica.
   */
  function initCostChart(canvas, costSummary) {
    var labels = [];
    var data = [];
    var colors = [];
    var index = 0;

    for (var metric in costSummary) {
      if (costSummary.hasOwnProperty(metric)) {
        labels.push(metric);
        data.push(costSummary[metric].cost || 0);
        colors.push(CHART_COLORS[index % CHART_COLORS.length]);
        index++;
      }
    }

    if (labels.length === 0) {
      return;
    }

    new Chart(canvas, {
      type: 'doughnut',
      data: {
        labels: labels,
        datasets: [{
          data: data,
          backgroundColor: colors,
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          title: {
            display: true,
            text: Drupal.t('Desglose de Costes'),
            font: { size: 16 }
          },
          legend: {
            position: 'bottom'
          }
        }
      }
    });
  }

  /**
   * Inicializa el selector de periodo.
   *
   * @param {HTMLElement} container - Contenedor del dashboard.
   * @param {number} tenantId - ID del tenant actual.
   */
  function initPeriodSelector(container, tenantId) {
    var select = container.querySelector('#usage-period');
    if (!select) {
      return;
    }

    select.addEventListener('change', function () {
      var period = this.value;
      var url = new URL(window.location.href);
      url.searchParams.set('period', period);

      if (tenantId) {
        url.searchParams.set('tenant_id', tenantId);
      }

      window.location.href = url.toString();
    });
  }

})(Drupal, drupalSettings, once);

/**
 * @file
 * NPS Results Charts - Graficos Chart.js para resultados NPS.
 *
 * PROPOSITO:
 * - Gauge semicircular del NPS score (-100 a +100).
 * - Grafico de linea de tendencia temporal.
 * - Grafico de barras de distribucion (promoters/passives/detractors).
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * NPS Gauge Chart - semicircular gauge.
   */
  Drupal.behaviors.csNpsGaugeChart = {
    attach: function (context) {
      once('cs-nps-gauge', '#nps-gauge-chart', context).forEach(function (canvas) {
        if (typeof Chart === 'undefined') {
          return;
        }

        var settings = drupalSettings.jarabaCs || {};
        var npsScore = settings.npsScore || 0;

        // Normalize NPS (-100 to +100) to 0-200 for gauge.
        var normalized = npsScore + 100;

        new Chart(canvas, {
          type: 'doughnut',
          data: {
            datasets: [{
              data: [normalized, 200 - normalized],
              backgroundColor: [
                npsScore >= 50 ? 'var(--ej-color-success, #16a34a)' :
                npsScore >= 0 ? 'var(--ej-color-warning, #f59e0b)' :
                npsScore >= -50 ? '#f97316' :
                'var(--ej-color-error, #dc2626)',
                '#e2e8f0'
              ],
              borderWidth: 0
            }]
          },
          options: {
            circumference: 180,
            rotation: 270,
            cutout: '75%',
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
              legend: { display: false },
              tooltip: { enabled: false }
            }
          }
        });
      });
    }
  };

  /**
   * NPS Trend Line Chart.
   */
  Drupal.behaviors.csNpsTrendChart = {
    attach: function (context) {
      once('cs-nps-trend', '#nps-trend-chart', context).forEach(function (canvas) {
        if (typeof Chart === 'undefined') {
          return;
        }

        var settings = drupalSettings.jarabaCs || {};
        var trendData = settings.trendData || [];

        if (trendData.length === 0) {
          return;
        }

        var labels = trendData.map(function (d) { return d.month; });
        var scores = trendData.map(function (d) { return d.score; });
        var responses = trendData.map(function (d) { return d.responses; });

        new Chart(canvas, {
          type: 'line',
          data: {
            labels: labels,
            datasets: [
              {
                label: Drupal.t('NPS Score'),
                data: scores,
                borderColor: 'var(--ej-color-primary, #2563eb)',
                backgroundColor: 'rgba(37, 99, 235, 0.1)',
                fill: true,
                tension: 0.3,
                pointRadius: 4,
                pointHoverRadius: 6
              },
              {
                label: Drupal.t('Responses'),
                data: responses,
                borderColor: 'var(--ej-color-text-muted, #64748b)',
                borderDash: [5, 5],
                fill: false,
                tension: 0.3,
                pointRadius: 3,
                yAxisID: 'y1'
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: true,
            interaction: {
              mode: 'index',
              intersect: false
            },
            scales: {
              y: {
                min: -100,
                max: 100,
                title: {
                  display: true,
                  text: Drupal.t('NPS Score')
                },
                grid: {
                  color: '#e2e8f0'
                }
              },
              y1: {
                position: 'right',
                title: {
                  display: true,
                  text: Drupal.t('Responses')
                },
                grid: {
                  display: false
                }
              },
              x: {
                grid: {
                  color: '#e2e8f0'
                }
              }
            },
            plugins: {
              legend: {
                position: 'bottom'
              }
            }
          }
        });
      });
    }
  };

})(Drupal, drupalSettings, once);

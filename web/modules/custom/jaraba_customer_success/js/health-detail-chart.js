/**
 * @file
 * Health Detail Charts - Graficos Chart.js para vista detallada de salud.
 *
 * PROPOSITO:
 * - Grafico de linea del historial de health score.
 * - Grafico radar de adopcion de features.
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Health Score History Line Chart.
   */
  Drupal.behaviors.csHealthHistoryChart = {
    attach: function (context) {
      once('cs-health-history', '#health-history-chart', context).forEach(function (canvas) {
        if (typeof Chart === 'undefined') {
          return;
        }

        var settings = drupalSettings.jarabaCs || {};
        var historyData = settings.healthHistory || [];

        if (historyData.length === 0) {
          return;
        }

        var labels = historyData.map(function (d) { return d.date; });
        var scores = historyData.map(function (d) { return d.score; });

        // Calculate threshold lines.
        var thresholds = settings.healthThresholds || {
          healthy: 80,
          neutral: 60,
          at_risk: 40
        };

        new Chart(canvas, {
          type: 'line',
          data: {
            labels: labels,
            datasets: [
              {
                label: Drupal.t('Health Score'),
                data: scores,
                borderColor: 'var(--ej-color-primary, #2563eb)',
                backgroundColor: 'rgba(37, 99, 235, 0.1)',
                fill: true,
                tension: 0.3,
                pointRadius: 3,
                pointHoverRadius: 6,
                borderWidth: 2
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
              y: {
                min: 0,
                max: 100,
                title: {
                  display: true,
                  text: Drupal.t('Score')
                },
                grid: {
                  color: '#e2e8f0'
                }
              },
              x: {
                title: {
                  display: true,
                  text: Drupal.t('Date')
                },
                grid: {
                  color: '#e2e8f0'
                }
              }
            },
            plugins: {
              legend: {
                position: 'bottom'
              },
              annotation: {
                annotations: {
                  healthyLine: {
                    type: 'line',
                    yMin: thresholds.healthy,
                    yMax: thresholds.healthy,
                    borderColor: '#16a34a',
                    borderWidth: 1,
                    borderDash: [5, 5],
                    label: {
                      content: Drupal.t('Healthy'),
                      display: true,
                      position: 'end',
                      font: { size: 10 }
                    }
                  },
                  neutralLine: {
                    type: 'line',
                    yMin: thresholds.neutral,
                    yMax: thresholds.neutral,
                    borderColor: '#f59e0b',
                    borderWidth: 1,
                    borderDash: [5, 5],
                    label: {
                      content: Drupal.t('Neutral'),
                      display: true,
                      position: 'end',
                      font: { size: 10 }
                    }
                  },
                  atRiskLine: {
                    type: 'line',
                    yMin: thresholds.at_risk,
                    yMax: thresholds.at_risk,
                    borderColor: '#dc2626',
                    borderWidth: 1,
                    borderDash: [5, 5],
                    label: {
                      content: Drupal.t('At Risk'),
                      display: true,
                      position: 'end',
                      font: { size: 10 }
                    }
                  }
                }
              }
            }
          }
        });
      });
    }
  };

  /**
   * Feature Adoption Radar Chart.
   */
  Drupal.behaviors.csFeatureAdoptionChart = {
    attach: function (context) {
      once('cs-feature-adoption', '#feature-adoption-chart', context).forEach(function (canvas) {
        if (typeof Chart === 'undefined') {
          return;
        }

        var settings = drupalSettings.jarabaCs || {};
        var adoptionData = settings.featureAdoption || [];

        if (adoptionData.length === 0) {
          return;
        }

        var labels = adoptionData.map(function (d) { return d.name; });
        var values = adoptionData.map(function (d) { return d.percentage; });

        new Chart(canvas, {
          type: 'radar',
          data: {
            labels: labels,
            datasets: [{
              label: Drupal.t('Adoption %'),
              data: values,
              borderColor: 'var(--ej-color-primary, #2563eb)',
              backgroundColor: 'rgba(37, 99, 235, 0.15)',
              borderWidth: 2,
              pointRadius: 4,
              pointBackgroundColor: 'var(--ej-color-primary, #2563eb)'
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
              r: {
                min: 0,
                max: 100,
                ticks: {
                  stepSize: 25,
                  font: { size: 10 }
                },
                grid: {
                  color: '#e2e8f0'
                },
                angleLines: {
                  color: '#e2e8f0'
                }
              }
            },
            plugins: {
              legend: {
                display: false
              }
            }
          }
        });
      });
    }
  };

})(Drupal, drupalSettings, once);

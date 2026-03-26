/**
 * @file
 * Analytics Hub Dashboard — Chart.js integration.
 *
 * Renderiza graficos de tendencia de trafico y distribucion de dispositivos
 * usando datos inyectados via drupalSettings.analyticsHub.
 *
 * @see \Drupal\jaraba_analytics\Controller\AnalyticsHubController::dashboard()
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.analyticsHub = {
    attach: function (context) {
      var elements = once('analytics-hub', '.analytics-hub', context);
      if (!elements.length) {
        return;
      }

      var data = drupalSettings.analyticsHub || {};

      // -- Traffic Trend Line Chart --
      var trafficCanvas = document.getElementById('analytics-hub-traffic-chart');
      if (trafficCanvas && typeof Chart !== 'undefined') {
        var trendData = data.trafficTrend || [];
        var labels = [];
        var visits = [];
        var unique = [];

        for (var i = 0; i < trendData.length; i++) {
          labels.push(trendData[i].date || '');
          visits.push(trendData[i].visits || 0);
          unique.push(trendData[i].unique || 0);
        }

        var primaryColor = getComputedStyle(document.documentElement)
          .getPropertyValue('--ej-primary').trim() || '#233d63';
        var accentColor = getComputedStyle(document.documentElement)
          .getPropertyValue('--ej-accent').trim() || '#ff8c42';

        new Chart(trafficCanvas.getContext('2d'), {
          type: 'line',
          data: {
            labels: labels,
            datasets: [
              {
                label: Drupal.t('Visitas'),
                data: visits,
                borderColor: primaryColor,
                backgroundColor: primaryColor + '1A',
                fill: true,
                tension: 0.3,
                pointRadius: 2,
                pointHoverRadius: 5
              },
              {
                label: Drupal.t('Visitantes unicos'),
                data: unique,
                borderColor: accentColor,
                backgroundColor: 'transparent',
                borderDash: [5, 5],
                fill: false,
                tension: 0.3,
                pointRadius: 2,
                pointHoverRadius: 5
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
              mode: 'index',
              intersect: false
            },
            plugins: {
              legend: {
                position: 'bottom',
                labels: {
                  usePointStyle: true,
                  padding: 16
                }
              },
              tooltip: {
                backgroundColor: 'rgba(35, 61, 99, 0.95)',
                titleFont: { weight: '600' },
                padding: 12,
                cornerRadius: 8
              }
            },
            scales: {
              x: {
                grid: { display: false },
                ticks: {
                  maxTicksLimit: 10,
                  font: { size: 11 }
                }
              },
              y: {
                beginAtZero: true,
                grid: { color: 'rgba(0,0,0,0.06)' },
                ticks: {
                  font: { size: 11 }
                }
              }
            }
          }
        });
      }

      // -- Device Breakdown Donut Chart --
      var deviceCanvas = document.getElementById('analytics-hub-device-chart');
      if (deviceCanvas && typeof Chart !== 'undefined') {
        var deviceData = data.deviceBreakdown || {};
        var devicePrimary = getComputedStyle(document.documentElement)
          .getPropertyValue('--ej-primary').trim() || '#233d63';
        var deviceAccent = getComputedStyle(document.documentElement)
          .getPropertyValue('--ej-accent').trim() || '#ff8c42';
        var deviceSuccess = getComputedStyle(document.documentElement)
          .getPropertyValue('--ej-success').trim() || '#00a9a5';

        new Chart(deviceCanvas.getContext('2d'), {
          type: 'doughnut',
          data: {
            labels: [
              Drupal.t('Escritorio'),
              Drupal.t('Movil'),
              Drupal.t('Tablet')
            ],
            datasets: [{
              data: [
                deviceData.desktop || 0,
                deviceData.mobile || 0,
                deviceData.tablet || 0
              ],
              backgroundColor: [devicePrimary, deviceAccent, deviceSuccess],
              borderWidth: 2,
              borderColor: '#fff',
              hoverOffset: 6
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
              legend: { display: false },
              tooltip: {
                backgroundColor: 'rgba(35, 61, 99, 0.95)',
                padding: 10,
                cornerRadius: 8,
                callbacks: {
                  label: function (tooltipItem) {
                    return tooltipItem.label + ': ' + tooltipItem.raw + '%';
                  }
                }
              }
            }
          }
        });
      }
    }
  };

})(Drupal, drupalSettings, once);

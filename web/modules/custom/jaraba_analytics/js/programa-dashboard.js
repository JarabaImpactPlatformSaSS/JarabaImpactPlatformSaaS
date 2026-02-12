/**
 * @file
 * Program Dashboard — Grant Burn Rate chart + report generation.
 *
 * Features:
 * - Chart.js doughnut chart for burn rate visualization.
 * - Refresh button fetches latest grant status from API.
 * - Report generation buttons trigger PDF download.
 *
 * F7 — Doc 182.
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.programaDashboard = {
    attach: function (context) {
      once('programa-dashboard', '.programa-dashboard', context).forEach(function (el) {
        var config = drupalSettings.programaDashboard || {};
        new ProgramaDashboard(el, config);
      });
    }
  };

  function ProgramaDashboard(el, config) {
    this.el = el;
    this.config = config;
    this.grantStatusUrl = config.grantStatusUrl || '/api/v1/programa/grant-status';
    this.reportGenerateUrl = config.reportGenerateUrl || '/api/v1/programa/reports/generate';

    this.initBurnRateChart(config.grantSummary || {});
    this.initTimelineChart(config.grantSummary || {});
    this.bindRefresh();
    this.bindReportButtons();
  }

  /**
   * Initialize burn rate doughnut chart.
   */
  ProgramaDashboard.prototype.initBurnRateChart = function (grantSummary) {
    var canvas = this.el.querySelector('#grant-burn-chart');
    if (!canvas || typeof Chart === 'undefined') return;

    var burnRate = grantSummary.burn_rate || {};
    var actual = burnRate.burn_rate || 0;
    var expected = burnRate.expected_rate || 0;
    var remaining = Math.max(0, 100 - actual);

    var ctx = canvas.getContext('2d');

    this.chart = new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: [
          Drupal.t('Ejecutado'),
          Drupal.t('Disponible')
        ],
        datasets: [{
          data: [actual, remaining],
          backgroundColor: [
            burnRate.alert ? '#ef4444' : '#233D63',
            '#E0E0E0'
          ],
          borderWidth: 0,
          cutout: '70%'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              padding: 16,
              usePointStyle: true,
              font: { size: 12 }
            }
          },
          tooltip: {
            callbacks: {
              label: function (context) {
                return context.label + ': ' + context.parsed + '%';
              }
            }
          }
        }
      },
      plugins: [{
        id: 'centerText',
        afterDraw: function (chart) {
          var width = chart.width;
          var height = chart.height;
          var ctx2 = chart.ctx;
          ctx2.restore();

          // Main value.
          ctx2.font = 'bold 24px Inter, sans-serif';
          ctx2.textBaseline = 'middle';
          ctx2.textAlign = 'center';
          ctx2.fillStyle = burnRate.alert ? '#ef4444' : '#233D63';
          ctx2.fillText(actual + '%', width / 2, height / 2 - 10);

          // Label.
          ctx2.font = '12px Inter, sans-serif';
          ctx2.fillStyle = '#757575';
          ctx2.fillText(Drupal.t('Burn Rate'), width / 2, height / 2 + 14);

          // Expected line.
          ctx2.font = '11px Inter, sans-serif';
          ctx2.fillStyle = '#999';
          ctx2.fillText(Drupal.t('Esperado') + ': ' + expected + '%', width / 2, height / 2 + 30);

          ctx2.save();
        }
      }]
    });
  };

  /**
   * Initialize burn rate timeline chart (expected vs actual over months).
   *
   * Uses server-side timeline data from GrantTrackingService::buildTimeline()
   * when available. Falls back to client-side generation.
   */
  ProgramaDashboard.prototype.initTimelineChart = function (grantSummary) {
    var canvas = this.el.querySelector('#grant-timeline-chart');
    if (!canvas || typeof Chart === 'undefined') return;

    var burnRate = grantSummary.burn_rate || {};
    var totalGrant = grantSummary.total || 0;
    var spentAmount = grantSummary.spent || 0;
    var timeline = grantSummary.timeline || {};

    var months;
    var expectedData;
    var actualData;

    if (timeline.labels && timeline.labels.length > 0) {
      // Use server-side computed timeline data.
      months = timeline.labels;
      expectedData = timeline.expected || [];
      actualData = timeline.actual || [];
    } else {
      // Fallback: client-side generation.
      months = [
        Drupal.t('Ene'), Drupal.t('Feb'), Drupal.t('Mar'), Drupal.t('Abr'),
        Drupal.t('May'), Drupal.t('Jun'), Drupal.t('Jul'), Drupal.t('Ago'),
        Drupal.t('Sep'), Drupal.t('Oct'), Drupal.t('Nov'), Drupal.t('Dic')
      ];

      var currentMonth = new Date().getMonth();

      expectedData = [];
      for (var i = 0; i < 12; i++) {
        expectedData.push(Math.round((totalGrant / 12) * (i + 1)));
      }

      actualData = [];
      var monthlyAvg = spentAmount > 0 && currentMonth >= 0
        ? spentAmount / (currentMonth + 1) : 0;
      for (var j = 0; j <= currentMonth; j++) {
        actualData.push(Math.round(monthlyAvg * (j + 1)));
      }
      if (actualData.length > 0) {
        actualData[actualData.length - 1] = spentAmount;
      }
    }

    var ctx = canvas.getContext('2d');

    this.timelineChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: months,
        datasets: [
          {
            label: Drupal.t('Esperado'),
            data: expectedData,
            borderColor: '#94a3b8',
            backgroundColor: 'rgba(148, 163, 184, 0.1)',
            borderDash: [6, 4],
            borderWidth: 2,
            pointRadius: 0,
            fill: false,
            tension: 0.1
          },
          {
            label: Drupal.t('Ejecutado'),
            data: actualData,
            borderColor: burnRate.alert ? '#ef4444' : '#233D63',
            backgroundColor: burnRate.alert ? 'rgba(239, 68, 68, 0.1)' : 'rgba(35, 61, 99, 0.1)',
            borderWidth: 2.5,
            pointRadius: 3,
            pointBackgroundColor: burnRate.alert ? '#ef4444' : '#233D63',
            fill: true,
            tension: 0.3
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
            position: 'top',
            labels: {
              padding: 16,
              usePointStyle: true,
              font: { size: 12 }
            }
          },
          tooltip: {
            callbacks: {
              label: function (context) {
                var value = context.parsed.y || 0;
                return context.dataset.label + ': ' + value.toLocaleString('es-ES') + ' EUR';
              }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: function (value) {
                if (value >= 1000) {
                  return (value / 1000).toLocaleString('es-ES') + 'k';
                }
                return value.toLocaleString('es-ES');
              },
              font: { size: 11 }
            },
            grid: {
              color: 'rgba(0, 0, 0, 0.06)'
            }
          },
          x: {
            ticks: {
              font: { size: 11 }
            },
            grid: {
              display: false
            }
          }
        }
      }
    });
  };

  /**
   * Bind refresh button.
   */
  ProgramaDashboard.prototype.bindRefresh = function () {
    var self = this;
    var btn = this.el.querySelector('#refresh-grant');
    if (!btn) return;

    btn.addEventListener('click', function () {
      btn.disabled = true;
      btn.textContent = Drupal.t('Actualizando...');

      fetch(self.grantStatusUrl, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          btn.disabled = false;
          btn.textContent = Drupal.t('Actualizar Datos');

          if (data.success && data.data) {
            self.updateGrantDisplay(data.data);
          }
        })
        .catch(function () {
          btn.disabled = false;
          btn.textContent = Drupal.t('Actualizar Datos');
        });
    });
  };

  /**
   * Update grant display with new data from API refresh.
   */
  ProgramaDashboard.prototype.updateGrantDisplay = function (data) {
    // Update doughnut chart.
    if (this.chart && data.burn_rate) {
      var actual = data.burn_rate.burn_rate || 0;
      var remaining = Math.max(0, 100 - actual);
      this.chart.data.datasets[0].data = [actual, remaining];
      this.chart.data.datasets[0].backgroundColor[0] = data.burn_rate.alert ? '#ef4444' : '#233D63';
      this.chart.update();
    }

    // Update timeline chart if server-side data available.
    if (this.timelineChart && data.timeline) {
      var timeline = data.timeline;
      if (timeline.labels && timeline.labels.length > 0) {
        this.timelineChart.data.labels = timeline.labels;
        this.timelineChart.data.datasets[0].data = timeline.expected || [];
        this.timelineChart.data.datasets[1].data = timeline.actual || [];

        var alertColor = (data.burn_rate && data.burn_rate.alert) ? '#ef4444' : '#233D63';
        this.timelineChart.data.datasets[1].borderColor = alertColor;
        this.timelineChart.data.datasets[1].pointBackgroundColor = alertColor;
        this.timelineChart.data.datasets[1].backgroundColor = (data.burn_rate && data.burn_rate.alert)
          ? 'rgba(239, 68, 68, 0.1)' : 'rgba(35, 61, 99, 0.1)';

        this.timelineChart.update();
      }
    }

    // Update KPI stats text in the grant stats card.
    var burnRateEl = this.el.querySelector('.programa-grant-stat__value--alert, .programa-grant-card--stats .programa-grant-stat:nth-child(4) .programa-grant-stat__value');
    if (burnRateEl && data.burn_rate) {
      burnRateEl.textContent = data.burn_rate.burn_rate + '%';
      if (data.burn_rate.alert) {
        burnRateEl.classList.add('programa-grant-stat__value--alert');
      } else {
        burnRateEl.classList.remove('programa-grant-stat__value--alert');
      }
    }
  };

  /**
   * Bind report generation buttons.
   */
  ProgramaDashboard.prototype.bindReportButtons = function () {
    var self = this;

    this.el.querySelectorAll('[data-generate-report]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var type = btn.getAttribute('data-generate-report');
        self.generateReport(type, btn);
      });
    });
  };

  /**
   * Generate a report via API.
   */
  ProgramaDashboard.prototype.generateReport = function (type, btn) {
    var originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = Drupal.t('Generando...');

    fetch(this.reportGenerateUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({
        type: type,
        data: {
          program_name: Drupal.t('Programa Formativo'),
          period: new Date().toLocaleDateString('es-ES', { month: '2-digit', year: 'numeric' })
        }
      })
    })
      .then(function (r) { return r.json(); })
      .then(function (result) {
        btn.disabled = false;
        btn.textContent = originalText;

        if (result.success) {
          if (typeof Drupal.Message !== 'undefined') {
            var messages = new Drupal.Message();
            messages.add(Drupal.t('Informe generado correctamente: @file', { '@file': result.filename || '' }), { type: 'status' });
          }
        } else {
          if (typeof Drupal.Message !== 'undefined') {
            var msgs = new Drupal.Message();
            msgs.add(result.error || Drupal.t('Error generando informe.'), { type: 'error' });
          }
        }
      })
      .catch(function () {
        btn.disabled = false;
        btn.textContent = originalText;
      });
  };

})(Drupal, drupalSettings, once);

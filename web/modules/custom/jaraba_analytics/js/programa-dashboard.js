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
   * Update grant display with new data.
   */
  ProgramaDashboard.prototype.updateGrantDisplay = function (data) {
    if (this.chart && data.burn_rate) {
      var actual = data.burn_rate.burn_rate || 0;
      var remaining = Math.max(0, 100 - actual);
      this.chart.data.datasets[0].data = [actual, remaining];
      this.chart.data.datasets[0].backgroundColor[0] = data.burn_rate.alert ? '#ef4444' : '#233D63';
      this.chart.update();
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

/**
 * @file
 * Revenue Dashboard — Chart.js visualizations.
 *
 * GAP-REVENUE-DASH: Renders MRR trend and tenant distribution charts.
 * ROUTE-LANGPREFIX-001: API URL from drupalSettings.
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.revenueDashboard = {
    attach(context) {
      once('revenue-dashboard', '[data-revenue-dashboard]', context).forEach(function (el) {
        var data = drupalSettings.revenueDashboard || {};
        initRevenueChart(data.monthly_revenue || {});
        initDistributionChart(data.tenant_distribution || {});
      });
    }
  };

  /**
   * Renders monthly revenue bar chart.
   */
  function initRevenueChart(monthlyRevenue) {
    var canvas = document.getElementById('revenueChart');
    if (!canvas || typeof Chart === 'undefined') {
      return;
    }

    var labels = Object.keys(monthlyRevenue);
    var values = Object.values(monthlyRevenue);

    new Chart(canvas.getContext('2d'), {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [{
          label: Drupal.t('Revenue'),
          data: values,
          backgroundColor: getComputedStyle(document.documentElement)
            .getPropertyValue('--ej-color-primary').trim() || '#FF8C42',
          borderRadius: 4
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function (ctx) {
                return ctx.parsed.y.toLocaleString('es-ES', {
                  style: 'currency',
                  currency: 'EUR'
                });
              }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: function (val) {
                return val.toLocaleString('es-ES') + ' €';
              }
            }
          }
        }
      }
    });
  }

  /**
   * Renders tenant distribution doughnut chart.
   */
  function initDistributionChart(distribution) {
    var canvas = document.getElementById('distributionChart');
    if (!canvas || typeof Chart === 'undefined') {
      return;
    }

    var statusLabels = {
      'active': Drupal.t('Activo'),
      'trial': Drupal.t('Trial'),
      'past_due': Drupal.t('Pago pendiente'),
      'suspended': Drupal.t('Suspendido'),
      'cancelled': Drupal.t('Cancelado'),
      'pending': Drupal.t('Pendiente'),
      'unknown': Drupal.t('Desconocido')
    };

    var statusColors = {
      'active': '#10B981',
      'trial': '#3B82F6',
      'past_due': '#F59E0B',
      'suspended': '#EF4444',
      'cancelled': '#6B7280',
      'pending': '#8B5CF6',
      'unknown': '#D1D5DB'
    };

    var labels = [];
    var values = [];
    var colors = [];

    for (var status in distribution) {
      if (distribution.hasOwnProperty(status)) {
        labels.push(statusLabels[status] || status);
        values.push(distribution[status]);
        colors.push(statusColors[status] || '#D1D5DB');
      }
    }

    new Chart(canvas.getContext('2d'), {
      type: 'doughnut',
      data: {
        labels: labels,
        datasets: [{
          data: values,
          backgroundColor: colors,
          borderWidth: 2,
          borderColor: '#fff'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'right',
            labels: { usePointStyle: true, padding: 12 }
          }
        }
      }
    });
  }

})(Drupal, drupalSettings, once);

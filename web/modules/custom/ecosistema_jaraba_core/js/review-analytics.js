/**
 * @file
 * Review Analytics Dashboard — Chart.js charts and interactive filters.
 *
 * B-05: Dashboard admin con graficas interactivas.
 * ROUTE-LANGPREFIX-001: Uses Drupal.url() for all fetch calls.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * B-05: Rating trend chart.
   */
  Drupal.behaviors.reviewAnalyticsTrend = {
    attach: function (context) {
      once('review-trend-chart', '.review-analytics__trend-canvas', context).forEach(function (canvas) {
        var trendData = canvas.dataset.trend;
        if (!trendData) {
          return;
        }

        try {
          var data = JSON.parse(trendData);
          if (!data.length) {
            return;
          }

          var labels = data.map(function (d) { return d.date; });
          var values = data.map(function (d) { return parseFloat(d.average); });

          new Chart(canvas, {
            type: 'line',
            data: {
              labels: labels,
              datasets: [{
                label: Drupal.t('Average Rating'),
                data: values,
                borderColor: '#4a90d9',
                backgroundColor: 'rgba(74, 144, 217, 0.1)',
                fill: true,
                tension: 0.3,
                pointRadius: 3,
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              scales: {
                y: {
                  min: 1,
                  max: 5,
                  ticks: { stepSize: 0.5 }
                }
              },
              plugins: {
                legend: { display: false }
              }
            }
          });
        }
        catch (e) {
          // Silently fail.
        }
      });
    }
  };

  /**
   * B-05: Distribution donut chart.
   */
  Drupal.behaviors.reviewAnalyticsDistribution = {
    attach: function (context) {
      once('review-dist-chart', '.review-analytics__dist-canvas', context).forEach(function (canvas) {
        var distData = canvas.dataset.distribution;
        if (!distData) {
          return;
        }

        try {
          var data = JSON.parse(distData);
          var labels = ['5★', '4★', '3★', '2★', '1★'];
          var values = [data['5'] || 0, data['4'] || 0, data['3'] || 0, data['2'] || 0, data['1'] || 0];
          var colors = ['#22c55e', '#84cc16', '#eab308', '#f97316', '#ef4444'];

          new Chart(canvas, {
            type: 'doughnut',
            data: {
              labels: labels,
              datasets: [{
                data: values,
                backgroundColor: colors,
                borderWidth: 2,
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                legend: {
                  position: 'bottom',
                  labels: { padding: 12 }
                }
              }
            }
          });
        }
        catch (e) {
          // Silently fail.
        }
      });
    }
  };

  /**
   * B-05: Period filter for analytics dashboard.
   */
  Drupal.behaviors.reviewAnalyticsFilter = {
    attach: function (context) {
      once('review-analytics-filter', '.review-analytics__period-select', context).forEach(function (sel) {
        sel.addEventListener('change', function () {
          var days = this.value;
          var currentUrl = new URL(window.location.href);
          currentUrl.searchParams.set('days', days);
          window.location.href = currentUrl.toString();
        });
      });
    }
  };

  /**
   * B-05: Auto-refresh dashboard every 5 minutes.
   */
  Drupal.behaviors.reviewAnalyticsAutoRefresh = {
    attach: function (context) {
      once('review-analytics-refresh', '.review-analytics', context).forEach(function () {
        setInterval(function () {
          if (document.visibilityState === 'visible') {
            window.location.reload();
          }
        }, 300000);
      });
    }
  };

})(Drupal, once);

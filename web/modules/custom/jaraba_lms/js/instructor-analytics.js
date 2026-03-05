/**
 * @file
 * P2-08: Charts de analytics para instructor LMS.
 *
 * Renderiza graficos Chart.js con datos de drupalSettings.lmsAnalytics.
 * INNERHTML-XSS-001: No usa innerHTML.
 * ROUTE-LANGPREFIX-001: Sin URLs hardcoded.
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.lmsInstructorAnalytics = {
    attach: function (context) {
      once('lms-analytics-charts', '.lms-analytics', context).forEach(function () {
        var data = drupalSettings.lmsAnalytics || {};

        if (typeof Chart === 'undefined') {
          return;
        }

        // Enrollment trend chart.
        var enrollmentCanvas = document.getElementById('lms-enrollment-chart');
        if (enrollmentCanvas && data.enrollmentTrend) {
          var enrollCtx = enrollmentCanvas.getContext('2d');
          var labels = data.enrollmentTrend.map(function (d) { return d.label; });
          var values = data.enrollmentTrend.map(function (d) { return d.value; });

          new Chart(enrollCtx, {
            type: 'bar',
            data: {
              labels: labels,
              datasets: [{
                label: Drupal.t('Nuevas matriculas'),
                data: values,
                backgroundColor: 'rgba(35, 61, 99, 0.7)',
                borderColor: 'rgb(35, 61, 99)',
                borderWidth: 1,
                borderRadius: 4,
              }],
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                legend: { display: false },
              },
              scales: {
                y: {
                  beginAtZero: true,
                  ticks: { stepSize: 1 },
                },
              },
            },
          });
        }

        // Engagement chart.
        var engagementCanvas = document.getElementById('lms-engagement-chart');
        if (engagementCanvas && data.engagementChart) {
          var engCtx = engagementCanvas.getContext('2d');
          var engLabels = data.engagementChart.map(function (d) { return d.label; });
          var engValues = data.engagementChart.map(function (d) { return d.value; });

          new Chart(engCtx, {
            type: 'line',
            data: {
              labels: engLabels,
              datasets: [{
                label: Drupal.t('Interacciones'),
                data: engValues,
                borderColor: 'rgb(0, 169, 165)',
                backgroundColor: 'rgba(0, 169, 165, 0.1)',
                fill: true,
                tension: 0.3,
                pointRadius: 4,
                pointHoverRadius: 6,
              }],
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                legend: { display: false },
              },
              scales: {
                y: {
                  beginAtZero: true,
                  ticks: { stepSize: 1 },
                },
              },
            },
          });
        }
      });
    },
  };

})(Drupal, drupalSettings, once);

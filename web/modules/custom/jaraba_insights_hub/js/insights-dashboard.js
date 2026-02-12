/**
 * @file
 * Insights Dashboard — Tab switching, date range, and AJAX data loading.
 *
 * Handles interactive elements of the Insights Hub dashboard including
 * tab navigation between panels, date range filtering, auto-refresh,
 * and dynamic KPI value updates via the API.
 *
 * Fase 7 — Insights Hub.
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.insightsDashboard = {
    attach: function (context) {
      once('insights-dashboard', '.ej-insights', context).forEach(function (container) {
        var tabs = container.querySelectorAll('.ej-insights__tab');
        var panels = container.querySelectorAll('.insights-panel');
        var dateSelectors = container.querySelectorAll('.ej-insights__date-range');

        // Tab switching.
        tabs.forEach(function (tab) {
          tab.addEventListener('click', function (e) {
            e.preventDefault();
            var target = tab.getAttribute('data-tab');

            tabs.forEach(function (t) {
              t.classList.remove('ej-insights__tab--active');
            });
            tab.classList.add('ej-insights__tab--active');

            panels.forEach(function (p) {
              p.style.display = p.classList.contains('insights-panel--' + target) ? 'block' : 'none';
            });
          });
        });

        // Date range selector.
        dateSelectors.forEach(function (selector) {
          selector.addEventListener('change', function () {
            var range = selector.value;
            loadDashboardData(range);
          });
        });

        // Auto-refresh every 5 minutes (300000 ms).
        setInterval(function () {
          var activeRange = container.querySelector('.ej-insights__date-range');
          if (activeRange) {
            loadDashboardData(activeRange.value || '7d');
          }
        }, 300000);

        /**
         * Load dashboard data from the API.
         *
         * @param {string} dateRange
         *   Date range identifier (e.g. '7d', '30d', '90d').
         */
        function loadDashboardData(dateRange) {
          var url = (drupalSettings.insightsHub && drupalSettings.insightsHub.apiSummaryUrl)
            || '/api/v1/insights/summary';

          fetch(url + '?date_range=' + encodeURIComponent(dateRange), {
            headers: {
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            }
          })
            .then(function (response) { return response.json(); })
            .then(function (data) {
              if (data.success) {
                updatePanels(data.data);
              }
            })
            .catch(function (error) {
              console.warn('Insights Hub: Failed to load dashboard data', error);
            });
        }

        /**
         * Update KPI panel values from API response data.
         *
         * @param {Object} data
         *   Response data with KPI keys matching data-kpi attributes.
         */
        function updatePanels(data) {
          container.querySelectorAll('[data-kpi]').forEach(function (el) {
            var key = el.getAttribute('data-kpi');
            if (data[key] !== undefined) {
              el.textContent = data[key];
            }
          });
        }
      });
    }
  };

})(Drupal, drupalSettings, once);

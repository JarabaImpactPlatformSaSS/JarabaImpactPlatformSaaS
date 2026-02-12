/**
 * @file
 * Cohort Analysis - Interactive retention heatmap.
 *
 * Implements Drupal behavior with:
 * - AJAX data loading when cohort/granularity is changed.
 * - Color-coding retention cells based on percentage (green-to-red gradient).
 * - Tooltip on hover showing exact values.
 *
 * Follows project conventions: Drupal.behaviors, once(), Drupal.t().
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Behavior for Cohort Analysis.
   */
  Drupal.behaviors.jarabaCohortAnalysis = {
    attach: function (context) {
      once('cohort-analysis', '.cohort-analysis', context).forEach(function (container) {
        var apiBase = container.dataset.apiBase || '/api/v1/analytics/cohort';
        var cohortSelect = container.querySelector('#cohort-select');
        var granularitySelect = container.querySelector('#cohort-granularity');
        var tooltip = container.querySelector('.cohort-analysis__tooltip');

        /**
         * Initialise the component.
         */
        function init() {
          colorCodeCells();
          computeSummaryMetrics();
          setupEventListeners();
          setupTooltip();
        }

        // ================================================================
        // Event listeners
        // ================================================================

        /**
         * Wire up selector changes and export button.
         */
        function setupEventListeners() {
          if (cohortSelect) {
            cohortSelect.addEventListener('change', function () {
              loadCohortData();
            });
          }
          if (granularitySelect) {
            granularitySelect.addEventListener('change', function () {
              loadCohortData();
            });
          }

          var exportBtn = container.querySelector('.cohort-analysis__export-btn');
          if (exportBtn) {
            exportBtn.addEventListener('click', function () {
              exportCSV();
            });
          }
        }

        // ================================================================
        // AJAX data loading
        // ================================================================

        /**
         * Load retention data via AJAX for the selected cohort.
         */
        function loadCohortData() {
          var cohortId = cohortSelect ? cohortSelect.value : '';
          var granularity = granularitySelect ? granularitySelect.value : 'weekly';

          if (!cohortId) {
            return;
          }

          container.classList.add('is-loading');

          var url = apiBase + '?cohort_id=' + encodeURIComponent(cohortId) +
            '&granularity=' + encodeURIComponent(granularity);

          fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
          })
            .then(function (response) { return response.json(); })
            .then(function (data) {
              if (data.success && data.data) {
                renderRetentionTable(data.data);
                computeSummaryMetrics();
              }
              container.classList.remove('is-loading');
            })
            .catch(function (error) {
              console.error('[Cohort Analysis] Error loading data:', error);
              container.classList.remove('is-loading');
            });
        }

        // ================================================================
        // Render retention table from AJAX response
        // ================================================================

        /**
         * Re-render the retention table with new data.
         *
         * @param {Object} data
         *   The response payload containing retention_data and week_headers.
         */
        function renderRetentionTable(data) {
          var retentionData = data.retention_data || [];
          var weekHeaders = data.week_headers || [];

          // Build header.
          var thead = container.querySelector('.cohort-analysis__table thead tr');
          if (!thead) {
            return;
          }

          var headerHtml = '<th class="cohort-analysis__th cohort-analysis__th--cohort">' +
            Drupal.t('Cohort') + '</th>' +
            '<th class="cohort-analysis__th cohort-analysis__th--users">' +
            Drupal.t('Users') + '</th>';

          weekHeaders.forEach(function (wk) {
            headerHtml += '<th class="cohort-analysis__th">' + escapeHtml(wk) + '</th>';
          });

          thead.innerHTML = headerHtml;

          // Build body.
          var tbody = container.querySelector('.cohort-analysis__tbody');
          if (!tbody) {
            return;
          }

          if (retentionData.length === 0) {
            tbody.innerHTML = '<tr><td class="cohort-analysis__empty" colspan="' +
              (weekHeaders.length + 2) + '">' +
              Drupal.t('No cohort data available. Select a cohort to begin analysis.') +
              '</td></tr>';
            return;
          }

          var bodyHtml = '';
          retentionData.forEach(function (row) {
            bodyHtml += '<tr class="cohort-analysis__row">';
            bodyHtml += '<td class="cohort-analysis__cell cohort-analysis__cell--label">' +
              escapeHtml(row.cohort_label) + '</td>';
            bodyHtml += '<td class="cohort-analysis__cell cohort-analysis__cell--users">' +
              formatNumber(row.users) + '</td>';

            if (row.weeks) {
              row.weeks.forEach(function (week) {
                bodyHtml += '<td class="cohort-analysis__cell cohort-analysis__cell--retention"' +
                  ' data-retention="' + week.rate + '"' +
                  ' data-retained="' + week.retained + '"' +
                  ' data-week="' + week.week + '"' +
                  ' aria-label="' + week.rate + '% ' + Drupal.t('retention') + '">' +
                  '<span class="cohort-analysis__cell-value">' + week.rate + '%</span>' +
                  '</td>';
              });
            }

            bodyHtml += '</tr>';
          });

          tbody.innerHTML = bodyHtml;

          // Re-apply coloring.
          colorCodeCells();
        }

        // ================================================================
        // Heatmap cell coloring
        // ================================================================

        /**
         * Apply background colors to retention cells based on data-retention.
         *
         * Uses a green (100%) to red (0%) gradient computed per cell for
         * precise coloring beyond the CSS fallback ranges.
         */
        function colorCodeCells() {
          var cells = container.querySelectorAll('.cohort-analysis__cell--retention');
          cells.forEach(function (cell) {
            var rate = parseFloat(cell.getAttribute('data-retention'));
            if (isNaN(rate)) {
              return;
            }

            var bg = getRetentionColor(rate);
            cell.style.backgroundColor = bg.background;
            cell.style.color = bg.text;
            cell.style.textShadow = bg.shadow;
          });
        }

        /**
         * Compute heatmap color for a retention percentage.
         *
         * @param {number} rate Retention percentage 0-100.
         * @returns {Object} { background, text, shadow }
         */
        function getRetentionColor(rate) {
          // Clamp.
          rate = Math.max(0, Math.min(100, rate));

          // Color stops: 0%=red, 25%=orange, 50%=yellow, 75%=light-green, 100%=green.
          var stops = [
            { pct: 0,   r: 229, g: 57,  b: 53  }, // #e53935
            { pct: 25,  r: 239, g: 140, b: 58  }, // #ef8c3a
            { pct: 50,  r: 212, g: 195, b: 65  }, // #d4c341
            { pct: 75,  r: 102, g: 187, b: 106 }, // #66bb6a
            { pct: 100, r: 27,  g: 122, b: 61  }  // #1b7a3d
          ];

          // Find the two stops we're between.
          var lower = stops[0];
          var upper = stops[stops.length - 1];
          for (var i = 0; i < stops.length - 1; i++) {
            if (rate >= stops[i].pct && rate <= stops[i + 1].pct) {
              lower = stops[i];
              upper = stops[i + 1];
              break;
            }
          }

          var range = upper.pct - lower.pct;
          var factor = range === 0 ? 0 : (rate - lower.pct) / range;

          var r = Math.round(lower.r + factor * (upper.r - lower.r));
          var g = Math.round(lower.g + factor * (upper.g - lower.g));
          var b = Math.round(lower.b + factor * (upper.b - lower.b));

          var background = 'rgb(' + r + ',' + g + ',' + b + ')';

          // Determine text color based on luminance.
          var luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
          var text = luminance > 0.55 ? '#1b5e20' : '#ffffff';
          var shadow = luminance > 0.55 ? 'none' : '0 1px 2px rgba(0,0,0,0.3)';

          return { background: background, text: text, shadow: shadow };
        }

        // ================================================================
        // Tooltip
        // ================================================================

        /**
         * Setup mouse-hover tooltip for retention cells.
         */
        function setupTooltip() {
          if (!tooltip) {
            return;
          }

          var tooltipCohort = tooltip.querySelector('.cohort-analysis__tooltip-cohort');
          var tooltipWeek = tooltip.querySelector('.cohort-analysis__tooltip-week');
          var tooltipRetained = tooltip.querySelector('.cohort-analysis__tooltip-retained');
          var tooltipRate = tooltip.querySelector('.cohort-analysis__tooltip-rate');

          container.addEventListener('mouseover', function (e) {
            var cell = e.target.closest('.cohort-analysis__cell--retention');
            if (!cell) {
              return;
            }

            var row = cell.closest('.cohort-analysis__row');
            var cohortLabel = row ?
              row.querySelector('.cohort-analysis__cell--label').textContent.trim() : '';
            var week = cell.getAttribute('data-week') || '';
            var retained = cell.getAttribute('data-retained') || '';
            var rate = cell.getAttribute('data-retention') || '';

            if (tooltipCohort) { tooltipCohort.textContent = cohortLabel; }
            if (tooltipWeek) { tooltipWeek.textContent = Drupal.t('Week') + ' ' + week; }
            if (tooltipRetained) { tooltipRetained.textContent = Drupal.t('Retained') + ': ' + formatNumber(parseInt(retained, 10) || 0); }
            if (tooltipRate) { tooltipRate.textContent = Drupal.t('Rate') + ': ' + rate + '%'; }

            tooltip.setAttribute('aria-hidden', 'false');
          });

          container.addEventListener('mousemove', function (e) {
            if (tooltip.getAttribute('aria-hidden') === 'false') {
              var x = e.clientX + 12;
              var y = e.clientY + 12;

              // Keep tooltip inside viewport.
              var rect = tooltip.getBoundingClientRect();
              if (x + rect.width > window.innerWidth) {
                x = e.clientX - rect.width - 12;
              }
              if (y + rect.height > window.innerHeight) {
                y = e.clientY - rect.height - 12;
              }

              tooltip.style.left = x + 'px';
              tooltip.style.top = y + 'px';
            }
          });

          container.addEventListener('mouseout', function (e) {
            var cell = e.target.closest('.cohort-analysis__cell--retention');
            if (cell) {
              tooltip.setAttribute('aria-hidden', 'true');
            }
          });
        }

        // ================================================================
        // Summary metrics
        // ================================================================

        /**
         * Compute and render summary metrics from current table data.
         */
        function computeSummaryMetrics() {
          var cells = container.querySelectorAll('.cohort-analysis__cell--retention');
          var rows = container.querySelectorAll('.cohort-analysis__row');

          if (cells.length === 0 || rows.length === 0) {
            return;
          }

          // Average retention at week 4 (index 3).
          var week4Rates = [];
          rows.forEach(function (row) {
            var retentionCells = row.querySelectorAll('.cohort-analysis__cell--retention');
            if (retentionCells.length > 3) {
              var rate = parseFloat(retentionCells[3].getAttribute('data-retention'));
              if (!isNaN(rate)) {
                week4Rates.push(rate);
              }
            }
          });

          var avgRetention = week4Rates.length > 0 ?
            (week4Rates.reduce(function (a, b) { return a + b; }, 0) / week4Rates.length).toFixed(1) : '--';

          // Best performing cohort (highest avg retention across all weeks).
          var bestCohort = '--';
          var bestAvg = -1;
          rows.forEach(function (row) {
            var retentionCells = row.querySelectorAll('.cohort-analysis__cell--retention');
            if (retentionCells.length === 0) {
              return;
            }
            var sum = 0;
            var count = 0;
            retentionCells.forEach(function (c) {
              var rate = parseFloat(c.getAttribute('data-retention'));
              if (!isNaN(rate)) {
                sum += rate;
                count++;
              }
            });
            var avg = count > 0 ? sum / count : 0;
            if (avg > bestAvg) {
              bestAvg = avg;
              bestCohort = row.querySelector('.cohort-analysis__cell--label') ?
                row.querySelector('.cohort-analysis__cell--label').textContent.trim() : '--';
            }
          });

          // Average churn rate (100 - average of all retention values).
          var allRates = [];
          cells.forEach(function (c) {
            var rate = parseFloat(c.getAttribute('data-retention'));
            if (!isNaN(rate)) {
              allRates.push(rate);
            }
          });
          var avgChurn = allRates.length > 0 ?
            (100 - allRates.reduce(function (a, b) { return a + b; }, 0) / allRates.length).toFixed(1) : '--';

          // Total users tracked.
          var totalUsers = 0;
          var userCells = container.querySelectorAll('.cohort-analysis__cell--users');
          userCells.forEach(function (c) {
            var num = parseInt(c.textContent.replace(/[^\d]/g, ''), 10);
            if (!isNaN(num)) {
              totalUsers += num;
            }
          });

          // Update DOM.
          setMetric('avg-retention', avgRetention + '%');
          setMetric('best-cohort', bestCohort);
          setMetric('churn-rate', avgChurn + '%');
          setMetric('total-users', formatNumber(totalUsers));
        }

        /**
         * Set a summary metric value by data-metric attribute.
         *
         * @param {string} metric The data-metric key.
         * @param {string} value Display value.
         */
        function setMetric(metric, value) {
          var el = container.querySelector('[data-metric="' + metric + '"]');
          if (el) {
            el.textContent = value;
          }
        }

        // ================================================================
        // CSV Export
        // ================================================================

        /**
         * Export the current retention table data as a CSV download.
         */
        function exportCSV() {
          var table = container.querySelector('.cohort-analysis__table');
          if (!table) {
            return;
          }

          var csvRows = [];

          // Header row.
          var headers = [];
          table.querySelectorAll('thead th').forEach(function (th) {
            headers.push('"' + th.textContent.trim().replace(/"/g, '""') + '"');
          });
          csvRows.push(headers.join(','));

          // Data rows.
          table.querySelectorAll('tbody .cohort-analysis__row').forEach(function (row) {
            var cols = [];
            row.querySelectorAll('.cohort-analysis__cell').forEach(function (cell) {
              cols.push('"' + cell.textContent.trim().replace(/"/g, '""') + '"');
            });
            csvRows.push(cols.join(','));
          });

          var csv = csvRows.join('\n');
          var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
          var url = URL.createObjectURL(blob);
          var link = document.createElement('a');
          link.setAttribute('href', url);
          link.setAttribute('download', 'cohort-analysis.csv');
          link.style.display = 'none';
          document.body.appendChild(link);
          link.click();
          document.body.removeChild(link);
          URL.revokeObjectURL(url);
        }

        // ================================================================
        // Utilities
        // ================================================================

        /**
         * Format number with locale separators.
         *
         * @param {number} num
         * @returns {string}
         */
        function formatNumber(num) {
          return new Intl.NumberFormat('es-ES').format(num);
        }

        /**
         * Escape HTML to prevent XSS.
         *
         * @param {string} text
         * @returns {string}
         */
        function escapeHtml(text) {
          var div = document.createElement('div');
          div.textContent = text;
          return div.innerHTML;
        }

        // Boot.
        init();
      });
    }
  };

})(Drupal, drupalSettings, once);

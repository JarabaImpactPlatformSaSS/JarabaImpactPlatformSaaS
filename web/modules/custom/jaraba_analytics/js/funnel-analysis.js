/**
 * @file
 * Funnel Analysis - Interactive funnel visualization.
 *
 * Implements Drupal behavior with:
 * - AJAX data loading when funnel/period is changed.
 * - Animated bar widths on load/update.
 * - Drop-off percentage indicators between steps.
 *
 * Follows project conventions: Drupal.behaviors, once(), Drupal.t().
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Behavior for Funnel Analysis.
   */
  Drupal.behaviors.jarabaFunnelAnalysis = {
    attach: function (context) {
      once('funnel-analysis', '.funnel-analysis', context).forEach(function (container) {
        var apiBase = container.dataset.apiBase || '/api/v1/analytics/funnel';
        var funnelSelect = container.querySelector('#funnel-select');
        var periodSelect = container.querySelector('#funnel-period');

        /**
         * Initialise the component.
         */
        function init() {
          animateBarsOnLoad();
          classifyDropOffs();
          setupEventListeners();
        }

        // ================================================================
        // Event listeners
        // ================================================================

        /**
         * Wire up selector changes and export button.
         */
        function setupEventListeners() {
          if (funnelSelect) {
            funnelSelect.addEventListener('change', function () {
              loadFunnelData();
            });
          }
          if (periodSelect) {
            periodSelect.addEventListener('change', function () {
              loadFunnelData();
            });
          }

          var exportBtn = container.querySelector('.funnel-analysis__export-btn');
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
         * Load funnel data via AJAX for the selected funnel and period.
         */
        function loadFunnelData() {
          var funnelId = funnelSelect ? funnelSelect.value : '';
          var period = periodSelect ? periodSelect.value : '30d';

          if (!funnelId) {
            return;
          }

          container.classList.add('is-loading');

          var url = apiBase + '?funnel_id=' + encodeURIComponent(funnelId) +
            '&period=' + encodeURIComponent(period);

          fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
          })
            .then(function (response) { return response.json(); })
            .then(function (data) {
              if (data.success && data.data) {
                renderFunnel(data.data);
              }
              container.classList.remove('is-loading');
            })
            .catch(function (error) {
              console.error('[Funnel Analysis] Error loading data:', error);
              container.classList.remove('is-loading');
            });
        }

        // ================================================================
        // Render funnel from AJAX response
        // ================================================================

        /**
         * Re-render the complete funnel visualization and table.
         *
         * @param {Object} data
         *   The response payload containing funnel_data (array of steps).
         */
        function renderFunnel(data) {
          var steps = data.funnel_data || data.steps || [];
          if (steps.length === 0) {
            renderEmptyState();
            return;
          }

          renderVisualization(steps);
          renderDetailsTable(steps);
          updateOverallConversion(steps);
          classifyDropOffs();

          // Trigger animation after render.
          requestAnimationFrame(function () {
            animateBarsOnLoad();
          });
        }

        /**
         * Render the visual funnel bars.
         *
         * @param {Array} steps
         */
        function renderVisualization(steps) {
          var chart = container.querySelector('.funnel-analysis__chart');
          if (!chart) {
            return;
          }

          var firstCount = steps[0].count || 1;
          var html = '';

          steps.forEach(function (step, index) {
            var widthPct = (step.count / firstCount * 100).toFixed(1);

            html += '<div class="funnel-step"' +
              ' data-step-index="' + index + '"' +
              ' data-count="' + step.count + '"' +
              ' data-rate="' + step.rate + '"' +
              ' style="--funnel-step-width: ' + widthPct + '%;">';

            html += '<div class="funnel-step__bar-container">';
            html += '<div class="funnel-step__bar"' +
              ' aria-label="' + escapeHtml(step.step_name) + ': ' + formatNumber(step.count) + ' (' + step.rate + '%)">';
            html += '<span class="funnel-step__count">' + formatNumber(step.count) + '</span>';
            html += '</div>';
            html += '</div>';

            html += '<div class="funnel-step__info">';
            html += '<span class="funnel-step__label">' + escapeHtml(step.step_name) + '</span>';
            html += '<span class="funnel-step__rate">' + step.rate + '%</span>';
            html += '</div>';

            // Drop-off indicator (skip for last step).
            if (index < steps.length - 1) {
              var dropOffRate = step.drop_off_rate || 0;
              var dropOff = step.drop_off || 0;

              html += '<div class="funnel-step__drop-off" data-drop-off="' + dropOffRate + '">';
              html += '<span class="funnel-step__drop-off-arrow" aria-hidden="true"></span>';
              html += '<span class="funnel-step__drop-off-value">';
              html += '-' + formatNumber(dropOff) + ' (' + dropOffRate + '%)';
              html += '</span>';
              html += '</div>';
            }

            html += '</div>';
          });

          chart.innerHTML = html;
        }

        /**
         * Render the details breakdown table.
         *
         * @param {Array} steps
         */
        function renderDetailsTable(steps) {
          var tbody = container.querySelector('.funnel-analysis__tbody');
          if (!tbody) {
            return;
          }

          var firstCount = steps[0].count || 1;
          var html = '';

          steps.forEach(function (step, index) {
            var barWidth = (step.count / firstCount * 100).toFixed(1);

            html += '<tr class="funnel-analysis__row" data-step-index="' + index + '">';

            html += '<td class="funnel-analysis__td funnel-analysis__td--step">';
            html += '<span class="funnel-analysis__step-number">' + (index + 1) + '</span>';
            html += escapeHtml(step.step_name);
            html += '</td>';

            html += '<td class="funnel-analysis__td funnel-analysis__td--number">' +
              formatNumber(step.count) + '</td>';

            html += '<td class="funnel-analysis__td funnel-analysis__td--number">';
            html += '<span class="funnel-analysis__rate-badge">' + step.rate + '%</span>';
            html += '</td>';

            html += '<td class="funnel-analysis__td funnel-analysis__td--number funnel-analysis__td--drop-off">';
            html += (step.drop_off != null) ? '-' + formatNumber(step.drop_off) : '--';
            html += '</td>';

            html += '<td class="funnel-analysis__td funnel-analysis__td--number funnel-analysis__td--drop-off-rate">';
            html += (step.drop_off_rate != null) ? step.drop_off_rate + '%' : '--';
            html += '</td>';

            html += '<td class="funnel-analysis__td funnel-analysis__td--bar-cell">';
            html += '<div class="funnel-analysis__mini-bar">';
            html += '<div class="funnel-analysis__mini-bar-fill" style="width: ' + barWidth + '%;"></div>';
            html += '</div>';
            html += '</td>';

            html += '</tr>';
          });

          tbody.innerHTML = html;
        }

        /**
         * Update the overall conversion badge.
         *
         * @param {Array} steps
         */
        function updateOverallConversion(steps) {
          var badge = container.querySelector('[data-metric="overall-conversion"]');
          if (!badge || steps.length === 0) {
            return;
          }

          var first = steps[0].count || 0;
          var last = steps[steps.length - 1].count || 0;
          var rate = first > 0 ? (last / first * 100).toFixed(1) : 0;
          badge.textContent = rate + '%';
        }

        /**
         * Render empty state when no data is available.
         */
        function renderEmptyState() {
          var chart = container.querySelector('.funnel-analysis__chart');
          if (chart) {
            chart.innerHTML = '<div class="funnel-analysis__empty">' +
              '<p>' + Drupal.t('No funnel data available. Select a funnel to begin analysis.') + '</p>' +
              '</div>';
          }

          var tbody = container.querySelector('.funnel-analysis__tbody');
          if (tbody) {
            tbody.innerHTML = '<tr><td class="funnel-analysis__empty-row" colspan="6">' +
              Drupal.t('No step data available.') + '</td></tr>';
          }

          var badge = container.querySelector('[data-metric="overall-conversion"]');
          if (badge) {
            badge.textContent = '--';
          }
        }

        // ================================================================
        // Bar animation
        // ================================================================

        /**
         * Trigger the CSS bar-grow animation on funnel step bars.
         *
         * Removes and re-adds the animate class to restart the animation
         * even when data changes without a full page reload.
         */
        function animateBarsOnLoad() {
          container.classList.remove('funnel-analysis--animate');

          // Force reflow to restart animation.
          void container.offsetWidth;

          container.classList.add('funnel-analysis--animate');
        }

        // ================================================================
        // Drop-off classification
        // ================================================================

        /**
         * Classify drop-off indicators as critical when above threshold.
         *
         * Adds the --critical modifier to drop-offs exceeding 40%.
         */
        function classifyDropOffs() {
          var dropOffs = container.querySelectorAll('.funnel-step__drop-off');
          dropOffs.forEach(function (el) {
            var rate = parseFloat(el.getAttribute('data-drop-off'));
            el.classList.remove('funnel-step__drop-off--critical');
            if (!isNaN(rate) && rate > 40) {
              el.classList.add('funnel-step__drop-off--critical');
            }
          });
        }

        // ================================================================
        // CSV Export
        // ================================================================

        /**
         * Export the current funnel data as a CSV download.
         */
        function exportCSV() {
          var table = container.querySelector('.funnel-analysis__table');
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
          table.querySelectorAll('tbody .funnel-analysis__row').forEach(function (row) {
            var cols = [];
            row.querySelectorAll('.funnel-analysis__td').forEach(function (cell) {
              cols.push('"' + cell.textContent.trim().replace(/"/g, '""') + '"');
            });
            csvRows.push(cols.join(','));
          });

          var csv = csvRows.join('\n');
          var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
          var url = URL.createObjectURL(blob);
          var link = document.createElement('a');
          link.setAttribute('href', url);
          link.setAttribute('download', 'funnel-analysis.csv');
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

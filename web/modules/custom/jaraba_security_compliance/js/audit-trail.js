/**
 * @file
 * JavaScript for the Audit Trail page.
 *
 * Provides:
 * - Client-side filtering of audit events
 * - CSV export functionality
 * - Search within events
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Audit Trail behavior.
   *
   * Initializes filtering and export for the audit trail.
   */
  Drupal.behaviors.jarabaAuditTrail = {
    attach: function (context) {
      once('audit-trail-filters', '.audit-trail', context).forEach(function (container) {
        var rows = container.querySelectorAll('.audit-trail__row');
        var severityFilter = container.querySelector('[data-filter="severity"]');
        var dateStartFilter = container.querySelector('[data-filter="date-start"]');
        var dateEndFilter = container.querySelector('[data-filter="date-end"]');
        var searchFilter = container.querySelector('[data-filter="search"]');
        var exportBtn = container.querySelector('[data-action="export-csv"]');

        /**
         * Apply all active filters to the table rows.
         */
        function applyFilters() {
          var severity = severityFilter ? severityFilter.value : '';
          var dateStart = dateStartFilter ? dateStartFilter.value : '';
          var dateEnd = dateEndFilter ? dateEndFilter.value : '';
          var searchTerm = searchFilter ? searchFilter.value.toLowerCase() : '';

          var startTimestamp = dateStart ? new Date(dateStart).getTime() / 1000 : 0;
          var endTimestamp = dateEnd ? (new Date(dateEnd).getTime() / 1000) + 86399 : Infinity;

          var visibleCount = 0;

          rows.forEach(function (row) {
            var rowSeverity = row.getAttribute('data-severity') || '';
            var rowTimestamp = parseInt(row.getAttribute('data-timestamp'), 10) || 0;
            var rowEventType = (row.getAttribute('data-event-type') || '').toLowerCase();
            var rowActor = (row.getAttribute('data-actor') || '').toLowerCase();

            var matchesSeverity = !severity || rowSeverity === severity;
            var matchesDateStart = rowTimestamp >= startTimestamp;
            var matchesDateEnd = rowTimestamp <= endTimestamp;
            var matchesSearch = !searchTerm ||
              rowEventType.indexOf(searchTerm) !== -1 ||
              rowActor.indexOf(searchTerm) !== -1;

            var visible = matchesSeverity && matchesDateStart && matchesDateEnd && matchesSearch;
            row.style.display = visible ? '' : 'none';

            if (visible) {
              visibleCount++;
            }
          });

          // Update count display.
          var countEl = container.querySelector('.audit-trail__count');
          if (countEl) {
            countEl.textContent = Drupal.t('Total: @count eventos', { '@count': visibleCount });
          }
        }

        // Bind filter events.
        if (severityFilter) {
          severityFilter.addEventListener('change', applyFilters);
        }
        if (dateStartFilter) {
          dateStartFilter.addEventListener('change', applyFilters);
        }
        if (dateEndFilter) {
          dateEndFilter.addEventListener('change', applyFilters);
        }
        if (searchFilter) {
          var searchTimeout;
          searchFilter.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(applyFilters, 300);
          });
        }

        // CSV Export.
        if (exportBtn) {
          exportBtn.addEventListener('click', function () {
            var settings = drupalSettings.auditTrail || {};
            var endpoint = settings.exportEndpoint || '/api/v1/security/audit/export';

            // Build URL with current filter params.
            var params = new URLSearchParams();
            if (dateStartFilter && dateStartFilter.value) {
              params.set('start_date', dateStartFilter.value);
            }
            if (dateEndFilter && dateEndFilter.value) {
              params.set('end_date', dateEndFilter.value);
            }
            if (severityFilter && severityFilter.value) {
              params.set('severity', severityFilter.value);
            }

            var url = endpoint;
            var queryString = params.toString();
            if (queryString) {
              url += '?' + queryString;
            }

            window.location.href = url;
          });
        }
      });
    }
  };

})(Drupal, drupalSettings, once);

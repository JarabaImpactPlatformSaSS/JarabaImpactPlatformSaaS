/**
 * @file
 * Journey Dashboard behaviors.
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.journeyDashboard = {
    attach: function (context) {
      once('journey-dashboard-init', '.journey-dashboard', context).forEach(function (el) {
        // Auto-refresh every 60 seconds.
        var refreshInterval = 60000;
        var timer = setInterval(function () {
          if (document.hidden) {
            return;
          }
          // Only refresh if still on the page.
          if (!document.querySelector('.journey-dashboard')) {
            clearInterval(timer);
            return;
          }
          Drupal.ajax({
            url: window.location.href,
            event: 'journey-refresh',
            progress: { type: 'none' }
          });
        }, refreshInterval);

        // Filter at-risk table.
        var filterInput = el.querySelector('[data-journey-filter]');
        if (filterInput) {
          filterInput.addEventListener('input', function () {
            var query = this.value.toLowerCase();
            var rows = el.querySelectorAll('.journey-table tbody tr');
            rows.forEach(function (row) {
              var text = row.textContent.toLowerCase();
              row.style.display = text.indexOf(query) !== -1 ? '' : 'none';
            });
          });
        }
      });
    }
  };

})(Drupal, once);

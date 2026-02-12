/**
 * @file
 * Reseller Portal Dashboard (G117-4) - Client-side behaviors.
 *
 * Provides:
 * - Auto-refresh of portal data every 5 minutes.
 * - Table search/filter functionality for tenants.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Drupal behavior for the Reseller Portal.
   */
  Drupal.behaviors.resellerPortal = {
    attach: function (context) {
      // -----------------------------------------------------------------
      // Table search/filter
      // -----------------------------------------------------------------
      var searchInputs = once(
        'reseller-search',
        '#reseller-tenant-search',
        context
      );

      searchInputs.forEach(function (input) {
        input.addEventListener('input', function () {
          var query = this.value.toLowerCase().trim();
          var rows = document.querySelectorAll('.reseller-tenants-table__row');

          rows.forEach(function (row) {
            var tenantName = row.getAttribute('data-tenant-name') || '';
            if (query === '' || tenantName.indexOf(query) !== -1) {
              row.classList.remove('reseller-tenants-table__row--hidden');
            }
            else {
              row.classList.add('reseller-tenants-table__row--hidden');
            }
          });
        });
      });

      // -----------------------------------------------------------------
      // Auto-refresh every 5 minutes
      // -----------------------------------------------------------------
      if (!window._resellerPortalInterval) {
        window._resellerPortalInterval = setInterval(function () {
          // Only refresh if the portal is still in the DOM.
          if (document.querySelector('.reseller-portal')) {
            refreshResellerPortal();
          }
          else {
            // Portal was removed from DOM, clear interval.
            clearInterval(window._resellerPortalInterval);
            window._resellerPortalInterval = null;
          }
        }, 300000); // 5 minutes = 300000ms
      }
    },

    /**
     * Detach behavior to clean up intervals.
     */
    detach: function (context, settings, trigger) {
      if (trigger === 'unload' && window._resellerPortalInterval) {
        clearInterval(window._resellerPortalInterval);
        window._resellerPortalInterval = null;
      }
    }
  };

  /**
   * Fetches fresh portal data and updates the DOM.
   */
  function refreshResellerPortal() {
    fetch(window.location.href, {
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
      .then(function (response) {
        return response.text();
      })
      .then(function (html) {
        var parser = new DOMParser();
        var doc = parser.parseFromString(html, 'text/html');

        // Update metrics cards.
        var newMetrics = doc.querySelector('.reseller-metrics');
        var currentMetrics = document.querySelector('.reseller-metrics');
        if (newMetrics && currentMetrics) {
          currentMetrics.innerHTML = newMetrics.innerHTML;
        }

        // Update health metrics.
        var newHealth = doc.querySelector('.reseller-portal__health');
        var currentHealth = document.querySelector('.reseller-portal__health');
        if (newHealth && currentHealth) {
          currentHealth.innerHTML = newHealth.innerHTML;
        }

        // Update tenants table body.
        var newTable = doc.querySelector('#reseller-tenants-table tbody');
        var currentTable = document.querySelector('#reseller-tenants-table tbody');
        if (newTable && currentTable) {
          currentTable.innerHTML = newTable.innerHTML;
        }

        // Update commissions summary.
        var newCommissions = doc.querySelector('.reseller-commissions__summary');
        var currentCommissions = document.querySelector('.reseller-commissions__summary');
        if (newCommissions && currentCommissions) {
          currentCommissions.innerHTML = newCommissions.innerHTML;
        }

        // Re-apply the current search filter.
        var searchInput = document.getElementById('reseller-tenant-search');
        if (searchInput && searchInput.value.trim() !== '') {
          searchInput.dispatchEvent(new Event('input'));
        }

        // Re-attach Drupal behaviors on updated content.
        var portal = document.querySelector('.reseller-portal');
        if (portal) {
          Drupal.attachBehaviors(portal);
        }
      })
      .catch(function (error) {
        // eslint-disable-next-line no-console
        console.error('Reseller portal refresh failed:', error);
      });
  }

})(Drupal, once);

/**
 * @file
 * Compliance Dashboard (G115-1) - Client-side behaviors.
 *
 * Provides:
 * - Auto-refresh of audit events every 30 seconds.
 * - Filtering events by severity level.
 * - Collapsible control sections per framework.
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Drupal behavior for the Compliance Dashboard.
   */
  Drupal.behaviors.complianceDashboard = {
    attach: function (context) {
      // -----------------------------------------------------------------
      // Collapsible control sections
      // -----------------------------------------------------------------
      var groupHeaders = once(
        'compliance-collapse',
        '.compliance-control-group__header',
        context
      );

      groupHeaders.forEach(function (header) {
        header.addEventListener('click', function () {
          var expanded = this.getAttribute('aria-expanded') === 'true';
          var targetId = this.getAttribute('aria-controls');
          var body = document.getElementById(targetId);

          if (body) {
            if (expanded) {
              body.setAttribute('hidden', '');
              this.setAttribute('aria-expanded', 'false');
            }
            else {
              body.removeAttribute('hidden');
              this.setAttribute('aria-expanded', 'true');
            }
          }
        });
      });

      // -----------------------------------------------------------------
      // Severity filter
      // -----------------------------------------------------------------
      var filterElements = once(
        'compliance-filter',
        '#severity-filter',
        context
      );

      filterElements.forEach(function (filterSelect) {
        filterSelect.addEventListener('change', function () {
          var selected = this.value;
          var rows = document.querySelectorAll('.compliance-event-row');

          rows.forEach(function (row) {
            var severity = row.getAttribute('data-severity');
            if (selected === 'all' || severity === selected) {
              row.classList.remove('compliance-event-row--hidden');
            }
            else {
              row.classList.add('compliance-event-row--hidden');
            }
          });
        });
      });

      // -----------------------------------------------------------------
      // Manual refresh button
      // -----------------------------------------------------------------
      var refreshButtons = once(
        'compliance-refresh',
        '#compliance-refresh',
        context
      );

      refreshButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
          refreshAuditEvents(btn);
        });
      });

      // -----------------------------------------------------------------
      // Auto-refresh every 30 seconds
      // -----------------------------------------------------------------
      if (!window._complianceDashboardInterval) {
        var interval = (drupalSettings.complianceDashboard &&
          drupalSettings.complianceDashboard.refreshInterval) || 30000;

        window._complianceDashboardInterval = setInterval(function () {
          // Only refresh if the dashboard is still in the DOM.
          if (document.getElementById('audit-events-table')) {
            var btn = document.getElementById('compliance-refresh');
            refreshAuditEvents(btn);
          }
          else {
            // Dashboard was removed from DOM, clear interval.
            clearInterval(window._complianceDashboardInterval);
            window._complianceDashboardInterval = null;
          }
        }, interval);
      }
    },

    /**
     * Detach behavior to clean up intervals.
     */
    detach: function (context, settings, trigger) {
      if (trigger === 'unload' && window._complianceDashboardInterval) {
        clearInterval(window._complianceDashboardInterval);
        window._complianceDashboardInterval = null;
      }
    }
  };

  /**
   * Fetches fresh audit events and updates the table.
   *
   * @param {HTMLElement|null} btn
   *   The refresh button element, if available.
   */
  function refreshAuditEvents(btn) {
    if (btn) {
      btn.disabled = true;
      btn.textContent = Drupal.t('Actualizando...');
    }

    // Reload the full page via AJAX to get fresh server-side data.
    // A dedicated JSON endpoint can be added later for efficiency.
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

        // Update events table body.
        var newBody = doc.getElementById('audit-events-body');
        var currentBody = document.getElementById('audit-events-body');
        if (newBody && currentBody) {
          currentBody.innerHTML = newBody.innerHTML;
        }

        // Update stat values.
        updateStatElement('compliance-score', doc);
        updateStatElement('total-audit-logs', doc);
        updateStatElement('critical-events', doc);

        // Re-apply the current severity filter.
        var filterSelect = document.getElementById('severity-filter');
        if (filterSelect && filterSelect.value !== 'all') {
          filterSelect.dispatchEvent(new Event('change'));
        }

        // Re-attach Drupal behaviors on the new content.
        var eventsSection = document.querySelector('.compliance-dashboard__events');
        if (eventsSection) {
          Drupal.attachBehaviors(eventsSection);
        }

        if (btn) {
          btn.disabled = false;
          btn.textContent = Drupal.t('Actualizar');
        }
      })
      .catch(function (error) {
        // eslint-disable-next-line no-console
        console.error('Compliance dashboard refresh failed:', error);
        if (btn) {
          btn.disabled = false;
          btn.textContent = Drupal.t('Error - Reintentar');
          setTimeout(function () {
            btn.textContent = Drupal.t('Actualizar');
          }, 3000);
        }
      });
  }

  /**
   * Updates a stat element from parsed HTML document.
   *
   * @param {string} elementId
   *   The DOM element ID to update.
   * @param {Document} sourceDoc
   *   The parsed HTML document containing fresh values.
   */
  function updateStatElement(elementId, sourceDoc) {
    var newEl = sourceDoc.getElementById(elementId);
    var currentEl = document.getElementById(elementId);
    if (newEl && currentEl) {
      currentEl.textContent = newEl.textContent;
    }
  }

})(Drupal, drupalSettings, once);

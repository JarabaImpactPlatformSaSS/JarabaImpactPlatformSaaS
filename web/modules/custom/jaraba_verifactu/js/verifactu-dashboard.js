/**
 * @file
 * VeriFactu dashboard behaviors.
 *
 * Provides real-time stats updates via fetch API and auto-refresh.
 * Uses Drupal.behaviors and once() per platform convention.
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Auto-refresh interval in milliseconds (30 seconds).
   */
  const REFRESH_INTERVAL_MS = 30000;

  /**
   * Dashboard auto-refresh behavior.
   */
  Drupal.behaviors.verifactuDashboard = {
    _timerId: null,

    attach: function (context) {
      const containers = once('verifactu-dashboard', '.verifactu-stats', context);
      if (!containers.length) {
        return;
      }

      const container = containers[0];
      this._startAutoRefresh(container);
      this._attachFilterHandlers(context);
      this._attachRetryHandlers(context);
    },

    detach: function (context, settings, trigger) {
      if (trigger === 'unload' && this._timerId) {
        clearInterval(this._timerId);
        this._timerId = null;
      }
    },

    /**
     * Starts auto-refresh of dashboard statistics.
     */
    _startAutoRefresh: function (container) {
      if (this._timerId) {
        return;
      }

      this._timerId = setInterval(function () {
        // Only refresh if the page is visible.
        if (document.hidden) {
          return;
        }
        Drupal.behaviors.verifactuDashboard._fetchStats(container);
      }, REFRESH_INTERVAL_MS);
    },

    /**
     * Fetches updated statistics from the API.
     */
    _fetchStats: function (container) {
      const baseUrl = drupalSettings.path ? drupalSettings.path.baseUrl : '/';
      const url = baseUrl + 'api/v1/verifactu/audit/stats';

      fetch(url, {
        credentials: 'same-origin',
        headers: {
          'Accept': 'application/json',
        },
      })
        .then(function (response) {
          if (!response.ok) {
            throw new Error('Stats fetch failed: ' + response.status);
          }
          return response.json();
        })
        .then(function (json) {
          if (json.success && json.data) {
            Drupal.behaviors.verifactuDashboard._updateStatsCards(container, json.data);
          }
        })
        .catch(function () {
          // Silently fail — stats will refresh on next interval.
        });
    },

    /**
     * Updates stats card values in the DOM.
     */
    _updateStatsCards: function (container, data) {
      var cards = container.querySelectorAll('.verifactu-stats__card');
      cards.forEach(function (card) {
        var valueEl = card.querySelector('.verifactu-stats__card-value');
        if (!valueEl) {
          return;
        }

        if (card.classList.contains('verifactu-stats__card--records') && data.total_records !== undefined) {
          valueEl.textContent = data.total_records;
          Drupal.behaviors.verifactuDashboard._updateMiniStats(card, {
            accepted: data.accepted_records,
            pending: data.pending_records,
            rejected: data.rejected_records,
          });
        }
        else if (card.classList.contains('verifactu-stats__card--batches') && data.total_batches !== undefined) {
          valueEl.textContent = data.total_batches;
          Drupal.behaviors.verifactuDashboard._updateMiniStats(card, {
            accepted: data.sent_batches,
            pending: data.pending_batches,
            rejected: data.failed_batches,
          });
        }
      });
    },

    /**
     * Updates mini stat counters within a card.
     */
    _updateMiniStats: function (card, values) {
      var miniElements = card.querySelectorAll('.verifactu-stats__mini');
      miniElements.forEach(function (el) {
        if (el.classList.contains('verifactu-stats__mini--accepted') && values.accepted !== undefined) {
          el.firstChild.textContent = values.accepted + ' ';
        }
        else if (el.classList.contains('verifactu-stats__mini--pending') && values.pending !== undefined) {
          el.firstChild.textContent = values.pending + ' ';
        }
        else if (el.classList.contains('verifactu-stats__mini--rejected') && values.rejected !== undefined) {
          el.firstChild.textContent = values.rejected + ' ';
        }
      });
    },

    /**
     * Attaches handlers for records filter form.
     */
    _attachFilterHandlers: function (context) {
      var applyBtns = once('verifactu-filter-apply', '[data-verifactu-filter-apply]', context);
      applyBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
          var filtersContainer = btn.closest('[data-verifactu-filters]');
          if (!filtersContainer) {
            return;
          }

          var params = new URLSearchParams(window.location.search);
          var filterElements = filtersContainer.querySelectorAll('[data-verifactu-filter]');
          filterElements.forEach(function (el) {
            var key = el.getAttribute('data-verifactu-filter');
            var value = el.value;
            if (value) {
              params.set(key, value);
            }
            else {
              params.delete(key);
            }
          });

          // Reset to page 1 when filters change.
          params.delete('page');

          var newUrl = window.location.pathname + '?' + params.toString();
          window.location.href = newUrl;
        });
      });
    },

    /**
     * Attaches handlers for batch retry buttons.
     */
    _attachRetryHandlers: function (context) {
      var retryBtns = once('verifactu-retry', '[data-verifactu-retry-batch]', context);
      retryBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
          var batchId = btn.getAttribute('data-verifactu-retry-batch');
          if (!batchId) {
            return;
          }

          btn.disabled = true;
          btn.textContent = Drupal.t('Retrying...');

          var baseUrl = drupalSettings.path ? drupalSettings.path.baseUrl : '/';
          var url = baseUrl + 'api/v1/verifactu/remisions/' + batchId + '/retry';
          var csrfToken = drupalSettings.verifactu && drupalSettings.verifactu.csrfToken
            ? drupalSettings.verifactu.csrfToken
            : '';

          fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
              'Accept': 'application/json',
              'Content-Type': 'application/json',
              'X-CSRF-Token': csrfToken,
            },
          })
            .then(function (response) {
              return response.json();
            })
            .then(function (json) {
              if (json.success) {
                btn.textContent = Drupal.t('Queued');
                // Refresh the page after a short delay to show updated status.
                setTimeout(function () {
                  window.location.reload();
                }, 1500);
              }
              else {
                btn.textContent = Drupal.t('Failed');
                btn.disabled = false;
              }
            })
            .catch(function () {
              btn.textContent = Drupal.t('Error');
              btn.disabled = false;
            });
        });
      });
    },
  };

})(Drupal, drupalSettings, once);

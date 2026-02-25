/**
 * @file
 * Profile section manager — handles delete actions for section entities.
 *
 * Add/Edit buttons use data-slide-panel attributes handled by slide-panel.js.
 * This script only manages delete confirmations and card removal.
 */
(function (Drupal) {
  'use strict';

  var csrfTokenPromise = null;

  function getCsrfToken() {
    if (!csrfTokenPromise) {
      csrfTokenPromise = fetch('/session/token').then(function (r) {
        return r.text();
      });
    }
    return csrfTokenPromise;
  }

  Drupal.behaviors.profileSectionManager = {
    attach: function (context) {
      context.querySelectorAll('[data-section-delete]').forEach(function (btn) {
        if (btn.dataset.initialized) {
          return;
        }
        btn.dataset.initialized = 'true';

        btn.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();

          if (!confirm(Drupal.t('Are you sure you want to delete this record?'))) {
            return;
          }

          var url = btn.dataset.sectionDelete;

          getCsrfToken().then(function (token) {
            fetch(url, {
              method: 'POST',
              headers: {'X-CSRF-Token': token},
              credentials: 'same-origin'
            }).then(function (response) {
              if (response.ok) {
                var card = btn.closest('[data-entity-id]');
                if (card) {
                  // Update badge count before removing.
                  var container = card.closest('.current-items, .current-education');
                  var badge = container ? container.querySelector('.badge') : null;
                  if (badge) {
                    var count = parseInt(badge.textContent, 10);
                    if (!isNaN(count) && count > 0) {
                      badge.textContent = count - 1;
                    }
                  }
                  card.remove();
                }
              }
            });
          });
        });
      });
    }
  };

})(Drupal);

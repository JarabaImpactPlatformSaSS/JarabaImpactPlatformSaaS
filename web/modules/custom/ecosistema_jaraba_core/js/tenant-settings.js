/**
 * @file
 * Client-side search filter for tenant settings hub.
 *
 * Drupal.behaviors pattern. Uses data-* attributes for DOM selection.
 * ROUTE-LANGPREFIX-001: No hardcoded URLs.
 * INNERHTML-XSS-001: No innerHTML usage.
 */
(function (Drupal) {
  'use strict';

  Drupal.behaviors.tenantSettingsSearch = {
    attach: function (context) {
      var searchInput = context.querySelector('[data-tenant-settings-search]');
      if (!searchInput || searchInput.dataset.tenantSettingsInitialized) {
        return;
      }
      searchInput.dataset.tenantSettingsInitialized = 'true';

      var cards = context.querySelectorAll('[data-section-id]');
      var noResults = context.querySelector('[data-tenant-settings-no-results]');

      searchInput.addEventListener('input', function () {
        var query = this.value.toLowerCase().trim();
        var visibleCount = 0;

        cards.forEach(function (card) {
          var label = (card.dataset.sectionLabel || '').toLowerCase();
          var description = (card.dataset.sectionDescription || '').toLowerCase();
          var matches = !query || label.indexOf(query) !== -1 || description.indexOf(query) !== -1;

          card.hidden = !matches;
          if (matches) {
            visibleCount++;
          }
        });

        if (noResults) {
          noResults.hidden = visibleCount > 0 || !query;
        }
      });
    }
  };

})(Drupal);

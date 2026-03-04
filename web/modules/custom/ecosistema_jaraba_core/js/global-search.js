/**
 * @file
 * Global search behavior with autocomplete.
 *
 * GAP-SEARCH-FACET: Provides typeahead autocomplete for /buscar.
 * ROUTE-LANGPREFIX-001: API URL via drupalSettings, never hardcoded.
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.globalSearch = {
    attach: function (context) {
      var containers = once('global-search', '[data-global-search]', context);
      if (!containers.length) {
        return;
      }

      containers.forEach(function (container) {
        var input = container.querySelector('[data-search-input]');
        var dropdown = container.querySelector('[data-search-autocomplete]');
        if (!input || !dropdown) {
          return;
        }

        var apiUrl = (drupalSettings.globalSearch && drupalSettings.globalSearch.apiUrl)
          ? drupalSettings.globalSearch.apiUrl
          : '/api/v1/search';
        var debounceTimer = null;
        var minChars = 3;

        input.addEventListener('input', function () {
          clearTimeout(debounceTimer);
          var query = input.value.trim();

          if (query.length < minChars) {
            hideDropdown();
            return;
          }

          debounceTimer = setTimeout(function () {
            fetchSuggestions(query);
          }, 300);
        });

        input.addEventListener('keydown', function (e) {
          if (e.key === 'Escape') {
            hideDropdown();
          }
        });

        document.addEventListener('click', function (e) {
          if (!container.contains(e.target)) {
            hideDropdown();
          }
        });

        function fetchSuggestions(query) {
          var url = apiUrl + '?q=' + encodeURIComponent(query) + '&limit=5';

          fetch(url, {
            headers: { 'Accept': 'application/json' }
          })
            .then(function (response) {
              return response.json();
            })
            .then(function (data) {
              renderSuggestions(data.results || [], query);
            })
            .catch(function () {
              hideDropdown();
            });
        }

        function renderSuggestions(results, query) {
          if (!results.length) {
            hideDropdown();
            return;
          }

          var html = '';
          results.forEach(function (item) {
            var title = Drupal.checkPlain(item.title || '');
            var vertical = Drupal.checkPlain(item.vertical || '');
            html += '<a href="' + Drupal.checkPlain(item.url || '#') + '" class="global-search__suggestion" role="option">';
            html += '<span class="global-search__suggestion-title">' + title + '</span>';
            html += '<span class="global-search__suggestion-vertical">' + vertical + '</span>';
            html += '</a>';
          });

          dropdown.innerHTML = html;
          dropdown.hidden = false;
        }

        function hideDropdown() {
          dropdown.innerHTML = '';
          dropdown.hidden = true;
        }
      });
    }
  };

})(Drupal, drupalSettings, once);

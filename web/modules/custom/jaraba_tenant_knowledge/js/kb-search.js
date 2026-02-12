/**
 * @file
 * KB Search - Live search with debounce, dropdown results, keyboard navigation.
 *
 * PROPÓSITO:
 * Proporciona búsqueda en vivo para la base de conocimiento con
 * autocompletado, debounce, y navegación por teclado.
 *
 * DIRECTRICES:
 * - Drupal.behaviors + once() pattern
 * - Debounce de 300ms para evitar peticiones excesivas
 * - Navegación por teclado (flechas + Enter + Escape)
 * - Accesible con aria-attributes
 */
(function (Drupal, once) {
  'use strict';

  /**
   * Debounce utility.
   */
  function debounce(fn, delay) {
    var timer;
    return function () {
      var context = this;
      var args = arguments;
      clearTimeout(timer);
      timer = setTimeout(function () {
        fn.apply(context, args);
      }, delay);
    };
  }

  Drupal.behaviors.kbSearch = {
    attach: function (context) {
      var inputs = once('kb-search', '[data-kb-search-input]', context);

      inputs.forEach(function (input) {
        var form = input.closest('form');
        if (!form) {
          return;
        }

        var autocomplete = form.querySelector('[data-kb-autocomplete]');
        if (!autocomplete) {
          return;
        }

        var selectedIndex = -1;
        var results = [];

        /**
         * Fetch search results from API.
         */
        var fetchResults = debounce(function () {
          var query = input.value.trim();
          if (query.length < 2) {
            hideDropdown();
            return;
          }

          fetch('/api/v1/kb/search?q=' + encodeURIComponent(query) + '&limit=5')
            .then(function (response) {
              return response.json();
            })
            .then(function (data) {
              if (data.success && data.data && data.data.length > 0) {
                results = data.data;
                renderDropdown(results, query);
              } else {
                hideDropdown();
              }
            })
            .catch(function () {
              hideDropdown();
            });
        }, 300);

        /**
         * Render autocomplete dropdown.
         */
        function renderDropdown(items, query) {
          selectedIndex = -1;
          var html = '<ul class="kb-search-dropdown__list" role="listbox">';

          items.forEach(function (item, index) {
            var title = highlightMatch(item.title, query);
            html += '<li class="kb-search-dropdown__item" role="option" data-index="' + index + '">';
            html += '<a href="/ayuda/kb/articulo/' + encodeURIComponent(item.slug) + '" class="kb-search-dropdown__link">';
            html += '<span class="kb-search-dropdown__title">' + title + '</span>';
            if (item.summary) {
              var summary = item.summary.length > 80 ? item.summary.substring(0, 80) + '...' : item.summary;
              html += '<span class="kb-search-dropdown__summary">' + Drupal.checkPlain(summary) + '</span>';
            }
            html += '</a></li>';
          });

          html += '</ul>';
          autocomplete.innerHTML = html;
          autocomplete.hidden = false;
          autocomplete.setAttribute('role', 'listbox');
        }

        /**
         * Highlight matching text.
         */
        function highlightMatch(text, query) {
          var escaped = Drupal.checkPlain(text);
          var queryEscaped = Drupal.checkPlain(query);
          var regex = new RegExp('(' + queryEscaped.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
          return escaped.replace(regex, '<mark class="kb-search-dropdown__highlight">$1</mark>');
        }

        /**
         * Hide dropdown.
         */
        function hideDropdown() {
          autocomplete.hidden = true;
          autocomplete.innerHTML = '';
          results = [];
          selectedIndex = -1;
        }

        /**
         * Navigate dropdown items.
         */
        function navigateDropdown(direction) {
          var items = autocomplete.querySelectorAll('.kb-search-dropdown__item');
          if (items.length === 0) {
            return;
          }

          // Remove previous selection.
          if (selectedIndex >= 0 && items[selectedIndex]) {
            items[selectedIndex].classList.remove('kb-search-dropdown__item--active');
          }

          // Update index.
          if (direction === 'down') {
            selectedIndex = selectedIndex < items.length - 1 ? selectedIndex + 1 : 0;
          } else {
            selectedIndex = selectedIndex > 0 ? selectedIndex - 1 : items.length - 1;
          }

          // Apply selection.
          items[selectedIndex].classList.add('kb-search-dropdown__item--active');
          items[selectedIndex].scrollIntoView({ block: 'nearest' });
        }

        // Event: input change.
        input.addEventListener('input', fetchResults);

        // Event: keyboard navigation.
        input.addEventListener('keydown', function (e) {
          if (autocomplete.hidden) {
            return;
          }

          switch (e.key) {
            case 'ArrowDown':
              e.preventDefault();
              navigateDropdown('down');
              break;

            case 'ArrowUp':
              e.preventDefault();
              navigateDropdown('up');
              break;

            case 'Enter':
              if (selectedIndex >= 0 && results[selectedIndex]) {
                e.preventDefault();
                window.location.href = '/ayuda/kb/articulo/' + encodeURIComponent(results[selectedIndex].slug);
              }
              break;

            case 'Escape':
              hideDropdown();
              break;
          }
        });

        // Event: click outside to close dropdown.
        document.addEventListener('click', function (e) {
          if (!form.contains(e.target)) {
            hideDropdown();
          }
        });

        // Event: focus to show results if they exist.
        input.addEventListener('focus', function () {
          if (results.length > 0) {
            autocomplete.hidden = false;
          }
        });
      });
    }
  };

})(Drupal, once);

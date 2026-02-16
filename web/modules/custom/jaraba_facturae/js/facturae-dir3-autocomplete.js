/**
 * @file
 * DIR3 directory autocomplete behavior.
 *
 * Provides autocomplete search against the DIR3 REST API for
 * selecting organizational units (Oficina Contable, Organo Gestor,
 * Unidad Tramitadora) during B2G invoice creation.
 *
 * Spec: Doc 180, Seccion 4.4.
 * Plan: FASE 8, entregable F8-4.
 */
(function (Drupal, once) {
  'use strict';

  /**
   * DIR3 autocomplete behavior.
   */
  Drupal.behaviors.facturaeDir3Autocomplete = {
    attach: function (context) {
      once('facturae-dir3', '[data-dir3-autocomplete]', context).forEach(function (container) {
        var input = container.querySelector('[data-dir3-input]');
        var resultsList = container.querySelector('[data-dir3-results]');
        var spinner = container.querySelector('[data-dir3-spinner]');
        var typeSelect = container.querySelector('[data-dir3-type]');
        var selectedList = container.querySelector('[data-dir3-selected-list]');
        var debounceTimer = null;

        if (!input || !resultsList) {
          return;
        }

        // Debounced search on input.
        input.addEventListener('input', function () {
          clearTimeout(debounceTimer);
          var query = input.value.trim();

          if (query.length < 3) {
            clearResults();
            return;
          }

          debounceTimer = setTimeout(function () {
            searchDir3(query);
          }, 300);
        });

        // Keyboard navigation.
        input.addEventListener('keydown', function (e) {
          var items = resultsList.querySelectorAll('[role="option"]');
          var active = resultsList.querySelector('[aria-selected="true"]');
          var index = active ? Array.from(items).indexOf(active) : -1;

          if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (index < items.length - 1) {
              setActiveItem(items, index + 1);
            }
          } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (index > 0) {
              setActiveItem(items, index - 1);
            }
          } else if (e.key === 'Enter') {
            e.preventDefault();
            if (active) {
              selectUnit(JSON.parse(active.getAttribute('data-unit')));
            }
          } else if (e.key === 'Escape') {
            clearResults();
          }
        });

        // Close results when clicking outside.
        document.addEventListener('click', function (e) {
          if (!container.contains(e.target)) {
            clearResults();
          }
        });

        // Type filter change triggers new search.
        if (typeSelect) {
          typeSelect.addEventListener('change', function () {
            var query = input.value.trim();
            if (query.length >= 3) {
              searchDir3(query);
            }
          });
        }

        /**
         * Searches DIR3 units via REST API.
         */
        function searchDir3(query) {
          if (spinner) {
            spinner.style.display = 'inline-block';
          }

          var type = typeSelect ? typeSelect.value : 'all';
          var url = '/api/v1/facturae/dir3/search?q=' + encodeURIComponent(query) + '&type=' + encodeURIComponent(type);

          fetch(url, {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
          })
            .then(function (response) { return response.json(); })
            .then(function (data) {
              if (spinner) {
                spinner.style.display = 'none';
              }

              if (!data.success) {
                clearResults();
                return;
              }

              renderResults(data.data || []);
            })
            .catch(function () {
              if (spinner) {
                spinner.style.display = 'none';
              }
              clearResults();
            });
        }

        /**
         * Renders search results in the dropdown.
         */
        function renderResults(units) {
          resultsList.innerHTML = '';
          input.setAttribute('aria-expanded', units.length > 0 ? 'true' : 'false');

          if (units.length === 0) {
            var emptyItem = document.createElement('li');
            emptyItem.className = 'facturae-dir3-search__result-empty';
            emptyItem.textContent = Drupal.t('No results found.');
            resultsList.appendChild(emptyItem);
            return;
          }

          units.forEach(function (unit, idx) {
            var item = document.createElement('li');
            item.className = 'facturae-dir3-search__result-item';
            item.setAttribute('role', 'option');
            item.setAttribute('aria-selected', 'false');
            item.setAttribute('data-unit', JSON.stringify(unit));
            item.id = 'dir3-result-' + idx;

            var typeLabels = {
              '01': 'OC',
              '02': 'OG',
              '03': 'UT',
              '04': 'OP'
            };

            item.innerHTML =
              '<span class="facturae-dir3-search__result-code">' + escapeHtml(unit.code || '') + '</span>' +
              '<span class="facturae-dir3-search__result-name">' + escapeHtml(unit.name || '') + '</span>' +
              '<span class="facturae-dir3-search__result-type">' + escapeHtml(typeLabels[unit.type] || unit.type || '') + '</span>';

            item.addEventListener('click', function () {
              selectUnit(unit);
            });

            resultsList.appendChild(item);
          });
        }

        /**
         * Selects a DIR3 unit and adds it to the selected list.
         */
        function selectUnit(unit) {
          clearResults();
          input.value = '';

          if (!selectedList) {
            return;
          }

          // Check for duplicates.
          var existing = selectedList.querySelector('[data-code="' + unit.code + '"]');
          if (existing) {
            return;
          }

          var chip = document.createElement('div');
          chip.className = 'facturae-dir3-search__selected-chip';
          chip.setAttribute('data-code', unit.code);
          chip.innerHTML =
            '<span class="facturae-dir3-search__chip-code">' + escapeHtml(unit.code) + '</span>' +
            '<span class="facturae-dir3-search__chip-name">' + escapeHtml(unit.name || '') + '</span>' +
            '<button type="button" class="facturae-dir3-search__chip-remove" aria-label="' + Drupal.t('Remove') + '">&times;</button>';

          chip.querySelector('.facturae-dir3-search__chip-remove').addEventListener('click', function () {
            chip.remove();
          });

          selectedList.appendChild(chip);
        }

        /**
         * Sets the active item in the results list.
         */
        function setActiveItem(items, index) {
          items.forEach(function (item) {
            item.setAttribute('aria-selected', 'false');
          });
          items[index].setAttribute('aria-selected', 'true');
          items[index].scrollIntoView({ block: 'nearest' });
          input.setAttribute('aria-activedescendant', items[index].id);
        }

        /**
         * Clears the results dropdown.
         */
        function clearResults() {
          resultsList.innerHTML = '';
          input.setAttribute('aria-expanded', 'false');
          input.removeAttribute('aria-activedescendant');
        }

        /**
         * Escapes HTML to prevent XSS.
         */
        function escapeHtml(str) {
          var div = document.createElement('div');
          div.appendChild(document.createTextNode(str));
          return div.innerHTML;
        }
      });
    }
  };

})(Drupal, once);

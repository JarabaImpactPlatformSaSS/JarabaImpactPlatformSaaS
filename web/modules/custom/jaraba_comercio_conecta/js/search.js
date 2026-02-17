/**
 * @file
 * ComercioConecta — Search JavaScript.
 *
 * Estructura: Comportamientos Drupal para la busqueda del marketplace.
 * Lógica: Gestiona autocompletado, envío de búsqueda y navegación
 *   por teclado del dropdown de sugerencias.
 *
 * DIRECTRIZ: Todos los textos en Drupal.t() para traducibilidad.
 */

(function (Drupal) {
  'use strict';

  /**
   * Comportamiento: Barra de busqueda con autocompletado.
   *
   * Lógica: Al escribir en el input de busqueda, llama al API de
   *   autocompletado tras 300ms de debounce. Muestra sugerencias
   *   en un dropdown. Enter o click en el botón navega a resultados.
   */
  Drupal.behaviors.comercioSearch = {
    attach: function (context) {
      var searchInput = context.querySelector('[data-search-input]');
      var searchBtn = context.querySelector('[data-search-submit]');
      var autocompleteEl = context.querySelector('[data-search-autocomplete]');

      if (!searchInput || searchInput.dataset.comercioInit) return;
      searchInput.dataset.comercioInit = 'true';

      var debounceTimer;
      var activeIndex = -1;

      searchInput.addEventListener('input', function () {
        var query = this.value.trim();
        clearTimeout(debounceTimer);

        if (query.length < 2) {
          _hideAutocomplete(autocompleteEl);
          return;
        }

        debounceTimer = setTimeout(function () {
          _fetchAutocomplete(query, autocompleteEl, searchInput);
        }, 300);
      });

      searchInput.addEventListener('keydown', function (e) {
        var items = autocompleteEl ? autocompleteEl.querySelectorAll('.comercio-search__autocomplete-item') : [];

        if (e.key === 'ArrowDown' && items.length > 0) {
          e.preventDefault();
          activeIndex = Math.min(activeIndex + 1, items.length - 1);
          _highlightItem(items, activeIndex);
        } else if (e.key === 'ArrowUp' && items.length > 0) {
          e.preventDefault();
          activeIndex = Math.max(activeIndex - 1, 0);
          _highlightItem(items, activeIndex);
        } else if (e.key === 'Enter') {
          e.preventDefault();
          if (activeIndex >= 0 && items[activeIndex]) {
            searchInput.value = items[activeIndex].textContent.trim();
          }
          _doSearch(searchInput.value.trim());
        } else if (e.key === 'Escape') {
          _hideAutocomplete(autocompleteEl);
        }
      });

      if (searchBtn) {
        searchBtn.addEventListener('click', function () {
          _doSearch(searchInput.value.trim());
        });
      }

      // Cerrar autocomplete al hacer click fuera
      document.addEventListener('click', function (e) {
        if (!e.target.closest('.comercio-search__header')) {
          _hideAutocomplete(autocompleteEl);
        }
      });
    }
  };

  function _fetchAutocomplete(query, container, input) {
    fetch('/api/v1/comercio/search/autocomplete?q=' + encodeURIComponent(query), {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
      .then(function (r) { return r.json(); })
      .then(function (result) {
        if (!result.data || result.data.length === 0) {
          _hideAutocomplete(container);
          return;
        }

        var html = '';
        result.data.forEach(function (item) {
          html += '<div class="comercio-search__autocomplete-item" data-value="' +
            item.title.replace(/"/g, '&quot;') + '">' + item.title + '</div>';
        });

        container.innerHTML = html;
        container.classList.add('comercio-search__autocomplete--visible');

        container.querySelectorAll('.comercio-search__autocomplete-item').forEach(function (el) {
          el.addEventListener('click', function () {
            input.value = this.dataset.value;
            _hideAutocomplete(container);
            _doSearch(input.value);
          });
        });
      })
      .catch(function () {
        _hideAutocomplete(container);
      });
  }

  function _hideAutocomplete(container) {
    if (container) {
      container.classList.remove('comercio-search__autocomplete--visible');
      container.innerHTML = '';
    }
  }

  function _highlightItem(items, index) {
    items.forEach(function (el, i) {
      el.classList.toggle('comercio-search__autocomplete-item--active', i === index);
    });
  }

  function _doSearch(query) {
    if (query) {
      window.location.href = '/comercio-local/buscar?q=' + encodeURIComponent(query);
    }
  }

})(Drupal);

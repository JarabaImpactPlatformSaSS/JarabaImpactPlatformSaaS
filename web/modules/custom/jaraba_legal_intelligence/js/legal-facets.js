/**
 * @file
 * Legal Intelligence Hub — Filtros facetados interactivos.
 *
 * Gestiona los chips de filtro (jurisdiccion, tipo, fuente, fecha, organo),
 * el selector de scope (Nacional/UE/Todo), la limpieza de filtros activos,
 * y dispara eventos para re-ejecutar busquedas.
 *
 * DIRECTRIZ: Textos traducibles con Drupal.t().
 */

(function (Drupal, once) {

  'use strict';

  Drupal.behaviors.legalFacets = {
    attach: function (context) {
      once('legal-facets', '.legal-facets', context).forEach(function (facetsContainer) {

        // Click en chips de faceta.
        facetsContainer.addEventListener('click', function (e) {
          // Chip de faceta.
          var chip = e.target.closest('.legal-facets__chip');
          if (chip) {
            e.preventDefault();
            chip.classList.toggle('legal-facets__chip--active');
            dispatchFilters(facetsContainer);
            updateActiveFilterBar(facetsContainer);
            return;
          }

          // Boton de scope (Nacional/UE/Todo).
          var scopeBtn = e.target.closest('.legal-facets__scope-btn');
          if (scopeBtn) {
            e.preventDefault();
            facetsContainer.querySelectorAll('.legal-facets__scope-btn').forEach(function (b) {
              b.classList.remove('legal-facets__scope-btn--active');
            });
            scopeBtn.classList.add('legal-facets__scope-btn--active');
            var scope = scopeBtn.dataset.scope || 'all';
            document.dispatchEvent(new CustomEvent('legal-scope-changed', { detail: scope }));
            return;
          }

          // Boton limpiar todos los filtros.
          var clearBtn = e.target.closest('.legal-facets__active-clear');
          if (clearBtn) {
            e.preventDefault();
            facetsContainer.querySelectorAll('.legal-facets__chip--active').forEach(function (c) {
              c.classList.remove('legal-facets__chip--active');
            });
            dispatchFilters(facetsContainer);
            updateActiveFilterBar(facetsContainer);
            return;
          }

          // Boton eliminar filtro individual.
          var removeBtn = e.target.closest('.legal-facets__active-chip-remove');
          if (removeBtn) {
            e.preventDefault();
            var facet = removeBtn.dataset.facet;
            var value = removeBtn.dataset.value;
            var targetChip = facetsContainer.querySelector(
              '.legal-facets__chip[data-facet="' + facet + '"][data-value="' + value + '"]'
            );
            if (targetChip) {
              targetChip.classList.remove('legal-facets__chip--active');
            }
            dispatchFilters(facetsContainer);
            updateActiveFilterBar(facetsContainer);
            return;
          }

          // Toggle de grupo collapsible.
          var groupHeader = e.target.closest('.legal-facets__group-header');
          if (groupHeader) {
            var group = groupHeader.closest('.legal-facets__group');
            if (group) {
              group.classList.toggle('legal-facets__group--collapsed');
            }
          }
        });

        // Cambios en inputs de fecha.
        var dateInputs = facetsContainer.querySelectorAll('.legal-facets__date-range input[type="date"]');
        dateInputs.forEach(function (input) {
          input.addEventListener('change', function () {
            dispatchFilters(facetsContainer);
          });
        });
      });
    }
  };

  /**
   * Recopila filtros activos y dispara evento.
   *
   * @param {HTMLElement} container - Contenedor de facetas.
   */
  function dispatchFilters(container) {
    var filters = collectActiveFilters(container);
    document.dispatchEvent(new CustomEvent('legal-facets-changed', { detail: filters }));
  }

  /**
   * Recopila filtros activos de los chips seleccionados y campos de fecha.
   *
   * @param {HTMLElement} container - Contenedor de facetas.
   * @return {Object} Filtros activos agrupados por faceta.
   */
  function collectActiveFilters(container) {
    var filters = {};

    // Chips activos.
    var activeChips = container.querySelectorAll('.legal-facets__chip--active');
    activeChips.forEach(function (chip) {
      var facet = chip.dataset.facet;
      var value = chip.dataset.value;
      if (facet && value) {
        if (!filters[facet]) {
          filters[facet] = [];
        }
        filters[facet].push(value);
      }
    });

    // Rango de fechas.
    var dateFrom = container.querySelector('input[name="date_from"]');
    var dateTo = container.querySelector('input[name="date_to"]');
    if (dateFrom && dateFrom.value) {
      filters.date_from = [dateFrom.value];
    }
    if (dateTo && dateTo.value) {
      filters.date_to = [dateTo.value];
    }

    return filters;
  }

  /**
   * Actualiza la barra de filtros activos con chips removibles.
   *
   * @param {HTMLElement} container - Contenedor de facetas.
   */
  function updateActiveFilterBar(container) {
    var activeBar = container.querySelector('.legal-facets__active');
    if (!activeBar) {
      return;
    }

    var activeChips = container.querySelectorAll('.legal-facets__chip--active');
    if (activeChips.length === 0) {
      activeBar.innerHTML = '';
      activeBar.style.display = 'none';
      return;
    }

    activeBar.style.display = 'flex';
    var html = '';
    activeChips.forEach(function (chip) {
      var facet = chip.dataset.facet || '';
      var value = chip.dataset.value || '';
      html += '<span class="legal-facets__active-chip">';
      html += Drupal.checkPlain(value);
      html += '<button class="legal-facets__active-chip-remove" data-facet="' + facet + '" data-value="' + value + '" aria-label="' + Drupal.t('Remove filter') + '">&times;</button>';
      html += '</span>';
    });
    html += '<button class="legal-facets__active-clear">' + Drupal.t('Clear all') + '</button>';
    activeBar.innerHTML = html;
  }

})(Drupal, once);

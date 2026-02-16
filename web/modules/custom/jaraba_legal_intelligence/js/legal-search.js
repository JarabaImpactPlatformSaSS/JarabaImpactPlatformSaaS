/**
 * @file
 * Legal Intelligence Hub — Busqueda semantica frontend.
 *
 * Gestiona el formulario de busqueda con debounce, typeahead,
 * envio AJAX al endpoint /api/v1/legal/search y renderizado
 * dinamico de resultados. Escucha cambios de facetas y scope
 * para re-ejecutar busquedas con filtros.
 *
 * DIRECTRIZ: Textos traducibles con Drupal.t().
 */

(function (Drupal, drupalSettings, once) {

  'use strict';

  /**
   * Estado actual de la busqueda.
   * Mantiene la query y filtros para re-buscar al cambiar facetas.
   */
  var searchState = {
    query: '',
    filters: {},
    scope: 'all'
  };

  /**
   * Behavior principal de busqueda del Legal Intelligence Hub.
   */
  Drupal.behaviors.legalSearch = {
    attach: function (context) {
      once('legal-search', '.legal-search__form', context).forEach(function (form) {
        var input = form.querySelector('.legal-search__input');
        var submitBtn = form.querySelector('.legal-search__submit');
        var resultsContainer = document.querySelector('.legal-search__results');
        var debounceTimer = null;

        if (!input || !resultsContainer) {
          return;
        }

        // Debounce de 400ms para busqueda en tiempo real.
        input.addEventListener('input', function () {
          clearTimeout(debounceTimer);
          debounceTimer = setTimeout(function () {
            var query = input.value.trim();
            if (query.length >= 3) {
              searchState.query = query;
              performSearch(resultsContainer);
            }
          }, 400);
        });

        // Envio del formulario (Enter o boton).
        form.addEventListener('submit', function (e) {
          e.preventDefault();
          clearTimeout(debounceTimer);
          var query = input.value.trim();
          if (query.length >= 1) {
            searchState.query = query;
            performSearch(resultsContainer);
          }
        });

        // Escuchar cambios de facetas desde legal-facets.js.
        document.addEventListener('legal-facets-changed', function (e) {
          searchState.filters = flattenFilters(e.detail || {});
          if (searchState.query) {
            performSearch(resultsContainer);
          }
        });

        // Escuchar cambios de scope (Nacional/UE/Todo).
        document.addEventListener('legal-scope-changed', function (e) {
          searchState.scope = e.detail || 'all';
          if (searchState.query) {
            performSearch(resultsContainer);
          }
        });
      });
    }
  };

  /**
   * Aplana filtros de facetas agrupados a filtros planos para la API.
   *
   * Las facetas envian {source_id: ['cendoj', 'boe'], jurisdiction: ['fiscal']}.
   * La API espera filtros planos con un solo valor por campo.
   * Si hay multiples valores, se usa el primero.
   *
   * @param {Object} grouped - Filtros agrupados por faceta.
   * @return {Object} Filtros planos para la API.
   */
  function flattenFilters(grouped) {
    var flat = {};
    Object.keys(grouped).forEach(function (key) {
      if (grouped[key] && grouped[key].length > 0) {
        flat[key] = grouped[key][0];
      }
    });
    return flat;
  }

  /**
   * Ejecuta busqueda semantica via API REST.
   *
   * @param {HTMLElement} container - Contenedor de resultados.
   */
  function performSearch(container) {
    container.innerHTML = '<div class="legal-search__loading">' + Drupal.t('Searching...') + '</div>';

    var apiUrl = drupalSettings.legalIntelligence
      ? drupalSettings.legalIntelligence.searchUrl
      : '/api/v1/legal/search';

    fetch(apiUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({
        query: searchState.query,
        filters: searchState.filters,
        scope: searchState.scope
      })
    })
    .then(function (response) {
      return response.json();
    })
    .then(function (data) {
      if (data.success && data.data) {
        renderResults(data.data, container);
        updateFacetCounts(data.data.facets || {});
      }
      else {
        var errorMsg = data.error || Drupal.t('No results found for your query.');
        container.innerHTML = '<div class="legal-search__empty">' +
          Drupal.checkPlain(errorMsg) + '</div>';
      }
    })
    .catch(function () {
      container.innerHTML = '<div class="legal-search__error">' +
        Drupal.t('An error occurred. Please try again.') + '</div>';
    });
  }

  /**
   * Renderiza resultados de busqueda en el contenedor.
   *
   * @param {Object} data - Datos de respuesta de la API.
   * @param {HTMLElement} container - Contenedor de resultados.
   */
  function renderResults(data, container) {
    if (!data.results || data.results.length === 0) {
      container.innerHTML = '<div class="legal-search__empty">' +
        Drupal.t('No results found for your query.') + '</div>';
      return;
    }

    var html = '<div class="legal-search__results-header">';
    html += '<div class="legal-search__results-count">' +
      Drupal.t('@count results found', { '@count': data.total || data.results.length }) +
      '</div>';
    html += '</div>';

    html += '<div class="legal-search__results-list">';
    data.results.forEach(function (result) {
      html += renderResultCard(result);
    });
    html += '</div>';

    container.innerHTML = html;
    Drupal.attachBehaviors(container);
  }

  /**
   * Renderiza una tarjeta de resultado individual.
   *
   * @param {Object} result - Datos de una resolucion.
   * @return {string} HTML de la tarjeta.
   */
  function renderResultCard(result) {
    var statusClass = result.status_legal || 'vigente';
    var isEu = result.is_eu || false;
    var flagIcon = isEu ? '🇪🇺' : '🇪🇸';

    var html = '<article class="legal-resolution-card" data-resolution-id="' + (result.id || 0) + '">';

    // Header: titulo + badge.
    html += '<div class="legal-resolution-card__header">';
    html += '<h3 class="legal-resolution-card__title">' +
      '<span class="legal-resolution-card__flag">' + flagIcon + '</span> ' +
      Drupal.checkPlain(result.title || '') + '</h3>';
    html += '<span class="legal-resolution-card__badge legal-resolution-card__badge--' + statusClass + '">' +
      Drupal.checkPlain(statusClass) + '</span>';
    html += '</div>';

    // Metadata.
    html += '<div class="legal-resolution-card__meta">';
    if (result.issuing_body) {
      html += '<span>' + Drupal.checkPlain(result.issuing_body) + '</span>';
    }
    if (result.external_ref) {
      html += '<span>' + Drupal.checkPlain(result.external_ref) + '</span>';
    }
    if (result.date_issued) {
      html += '<span>' + Drupal.checkPlain(result.date_issued) + '</span>';
    }
    if (result.jurisdiction) {
      html += '<span>' + Drupal.checkPlain(result.jurisdiction) + '</span>';
    }
    html += '</div>';

    // Resumen IA.
    if (result.abstract_ai) {
      html += '<div class="legal-resolution-card__abstract">' + Drupal.checkPlain(result.abstract_ai) + '</div>';
    }

    // Topics como chips.
    if (result.topics && result.topics.length > 0) {
      html += '<div class="legal-resolution-card__topics">';
      result.topics.slice(0, 5).forEach(function (topic) {
        html += '<span class="legal-resolution-card__topics-chip">' + Drupal.checkPlain(topic) + '</span>';
      });
      html += '</div>';
    }

    // Score de relevancia.
    if (result.score && result.score > 0) {
      var scorePercent = Math.round(result.score * 100);
      html += '<div class="legal-resolution-card__score">';
      html += '<span class="legal-resolution-card__score-label">' + Drupal.t('Relevance') + '</span>';
      html += '<div class="legal-resolution-card__score-bar"><div class="legal-resolution-card__score-bar-fill" style="width:' + scorePercent + '%"></div></div>';
      html += '<span class="legal-resolution-card__score-value">' + scorePercent + '%</span>';
      html += '</div>';
    }

    // Acciones.
    html += '<div class="legal-resolution-card__actions">';
    var detailUrl = '/legal/' + encodeURIComponent(result.source_id || '') + '/' + encodeURIComponent(result.external_ref || '');
    html += '<a href="' + detailUrl + '" class="btn btn--sm btn--outline">' + Drupal.t('View detail') + '</a>';
    html += '<button class="btn btn--sm btn--primary" data-slide-panel="cite-' + (result.id || 0) + '" data-slide-panel-url="/legal/cite/' + (result.id || 0) + '/formal?ajax=1" data-slide-panel-title="' + Drupal.t('Insert citation') + '" data-slide-panel-size="--large">' + Drupal.t('Cite') + '</button>';
    html += '<button class="btn btn--sm btn--ghost" data-legal-bookmark="' + (result.id || 0) + '" aria-pressed="false" aria-label="' + Drupal.t('Bookmark') + '">&#9734;</button>';
    html += '</div>';

    html += '</article>';
    return html;
  }

  /**
   * Actualiza contadores de facetas en los chips del sidebar.
   *
   * @param {Object} facets - Facetas con counts {field: {value: count}}.
   */
  function updateFacetCounts(facets) {
    Object.keys(facets).forEach(function (field) {
      Object.keys(facets[field]).forEach(function (value) {
        var chip = document.querySelector('.legal-facets__chip[data-facet="' + field + '"][data-value="' + value + '"]');
        if (chip) {
          var countEl = chip.querySelector('.legal-facets__chip-count');
          if (countEl) {
            countEl.textContent = facets[field][value];
          }
        }
      });
    });
  }

})(Drupal, drupalSettings, once);

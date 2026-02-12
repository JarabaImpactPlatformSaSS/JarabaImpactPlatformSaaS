/**
 * @file
 * Lógica de búsqueda y filtrado para la página "Mis Páginas".
 *
 * Funcionalidades:
 * - Búsqueda por título en tiempo real (debounced)
 * - Filtro por estado (Publicado/Borrador)
 * - Filtro por plantilla
 * - Contador de resultados dinámico
 * - Estado "sin resultados" con mensaje
 */
(function (Drupal, once) {
    'use strict';

    Drupal.behaviors.myPagesFilter = {
        attach: function (context) {
            var pages = once('my-pages-filter', '.my-pages', context);
            if (!pages.length) return;

            var searchInput = document.getElementById('my-pages-search');
            var statusFilter = document.getElementById('my-pages-status-filter');
            var templateFilter = document.getElementById('my-pages-template-filter');
            var grid = document.getElementById('my-pages-grid');
            var resultsCount = document.getElementById('my-pages-results-count');
            var noResults = document.getElementById('my-pages-no-results');

            if (!grid) return;

            var cards = grid.querySelectorAll('.my-pages__card');
            var debounceTimer = null;

            /**
             * Elimina diacríticos para búsqueda tolerante (á→a, ñ→n).
             */
            function normalize(str) {
                return str.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
            }

            /**
             * Aplica todos los filtros activos simultáneamente.
             */
            function applyFilters() {
                var query = searchInput ? normalize(searchInput.value.trim()) : '';
                var status = statusFilter ? statusFilter.value : '';
                var template = templateFilter ? templateFilter.value : '';

                var visibleCount = 0;

                cards.forEach(function (card) {
                    var titleMatch = !query || normalize(card.getAttribute('data-title') || '').indexOf(query) !== -1;
                    var statusMatch = !status || card.getAttribute('data-status') === status;
                    var templateMatch = !template || card.getAttribute('data-template') === template;

                    if (titleMatch && statusMatch && templateMatch) {
                        card.style.display = '';
                        card.style.opacity = '1';
                        card.style.transform = '';
                        visibleCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                // Actualizar contador.
                if (resultsCount) {
                    resultsCount.textContent = visibleCount + (visibleCount === 1 ? ' página' : ' páginas');
                }

                // Mostrar/ocultar estado sin resultados.
                if (noResults) {
                    noResults.hidden = visibleCount > 0;
                }
                if (grid) {
                    grid.style.display = visibleCount > 0 ? '' : 'none';
                }
            }

            // Event listeners.
            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(applyFilters, 150);
                });

                searchInput.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') {
                        searchInput.value = '';
                        applyFilters();
                    }
                });
            }

            if (statusFilter) {
                statusFilter.addEventListener('change', applyFilters);
            }

            if (templateFilter) {
                templateFilter.addEventListener('change', applyFilters);
            }
        },
    };
})(Drupal, once);

/**
 * @file
 * Search & Discovery - AgroConecta
 *
 * Componente frontend para búsqueda y descubrimiento:
 * - Búsqueda full-text con debounce
 * - Autocompletado de sugerencias
 * - Filtros por categoría y precio
 * - Paginación "Cargar más"
 * - Navegación de categorías y colecciones
 */
(function (Drupal, drupalSettings, once) {
    'use strict';

    var API_BASE = '/api/v1/agro';
    var DEBOUNCE_MS = 350;

    // ===================================================
    // Utilidades
    // ===================================================

    function debounce(fn, delay) {
        var timer;
        return function () {
            var ctx = this, args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () { fn.apply(ctx, args); }, delay);
        };
    }

    function renderProductCard(product) {
        var ratingHtml = '';
        if (product.average_rating > 0) {
            var stars = '';
            var rounded = Math.round(product.average_rating);
            for (var i = 1; i <= 5; i++) {
                stars += '<span class="agro-stars__star ' + (i <= rounded ? 'agro-stars__star--filled' : 'agro-stars__star--empty') + '">★</span>';
            }
            ratingHtml = '<div class="agro-product-card__rating">' +
                '<span class="agro-stars">' + stars + '</span>' +
                '<span class="agro-product-card__review-count">(' + product.review_count + ')</span>' +
                '</div>';
        }

        return '<article class="agro-product-card" data-product-id="' + product.id + '">' +
            '<div class="agro-product-card__image agro-product-card__image--placeholder"></div>' +
            '<div class="agro-product-card__body">' +
            '<h3 class="agro-product-card__name">' + Drupal.checkPlain(product.name) + '</h3>' +
            '<div class="agro-product-card__price">' + parseFloat(product.price).toFixed(2).replace('.', ',') + ' €</div>' +
            ratingHtml +
            '</div></article>';
    }

    function showLoader(el) {
        if (el) el.style.display = 'flex';
    }

    function hideLoader(el) {
        if (el) el.style.display = 'none';
    }

    // ===================================================
    // Behavior: Search Page
    // ===================================================

    Drupal.behaviors.agroconectaSearch = {
        attach: function (context) {
            var roots = once('agro-search', '#agro-search-root', context);
            if (!roots.length) return;
            var root = roots[0];

            var input = root.querySelector('#agro-search-input');
            var sortSelect = root.querySelector('#agro-search-sort');
            var resultsGrid = root.querySelector('#agro-search-results');
            var metaContainer = root.querySelector('#agro-search-meta');
            var loader = root.querySelector('#agro-search-loader');
            var pagination = root.querySelector('#agro-search-pagination');
            var loadMoreBtn = root.querySelector('#agro-search-load-more');
            var autocompleteBox = root.querySelector('#agro-search-autocomplete');
            var filtersContainer = root.querySelector('#agro-search-active-filters');
            var minPriceInput = root.querySelector('#agro-filter-min-price');
            var maxPriceInput = root.querySelector('#agro-filter-max-price');

            var state = {
                query: (drupalSettings.agroSearch || {}).query || '',
                sort: (drupalSettings.agroSearch || {}).sort || 'relevance',
                offset: 0,
                limit: 24,
                total: 0,
                categoryId: null,
                categoryName: null,
                minPrice: null,
                maxPrice: null,
                loading: false
            };

            // Si hay resultados iniciales, actualizar total.
            if (drupalSettings.agroSearch && drupalSettings.agroSearch.initialResults) {
                state.total = drupalSettings.agroSearch.initialResults.total || 0;
                updatePagination();
            }

            // --- Búsqueda ---
            function doSearch(append) {
                if (state.loading) return;
                state.loading = true;
                showLoader(loader);

                var params = 'q=' + encodeURIComponent(state.query) +
                    '&sort=' + state.sort +
                    '&limit=' + state.limit +
                    '&offset=' + (append ? state.offset : 0);

                if (state.categoryId) params += '&category_id=' + state.categoryId;
                if (state.minPrice) params += '&min_price=' + state.minPrice;
                if (state.maxPrice) params += '&max_price=' + state.maxPrice;

                fetch(API_BASE + '/search?' + params)
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        state.total = data.total || 0;
                        if (!append) {
                            state.offset = 0;
                            resultsGrid.innerHTML = '';
                        }

                        if (data.results && data.results.length > 0) {
                            var html = '';
                            data.results.forEach(function (product) {
                                html += renderProductCard(product);
                            });
                            resultsGrid.insertAdjacentHTML('beforeend', html);
                            state.offset += data.results.length;

                            // Animate entry.
                            var newCards = resultsGrid.querySelectorAll('.agro-product-card');
                            var startIdx = append ? (newCards.length - data.results.length) : 0;
                            for (var ci = startIdx; ci < newCards.length; ci++) {
                                (function (card, delay) {
                                    card.style.opacity = '0';
                                    card.style.transform = 'translateY(12px)';
                                    setTimeout(function () {
                                        card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                                        card.style.opacity = '1';
                                        card.style.transform = 'translateY(0)';
                                    }, delay);
                                })(newCards[ci], (ci - startIdx) * 40);
                            }
                        } else if (!append) {
                            resultsGrid.innerHTML = '<div class="agro-search-empty">' +
                                '<span class="agro-search-empty__icon">🔍</span>' +
                                '<p>' + Drupal.t('No se encontraron resultados.') + '</p></div>';
                        }

                        updateMeta();
                        updatePagination();
                        updateActiveFilters();
                    })
                    .catch(function () {
                        resultsGrid.innerHTML = '<div class="agro-search-empty">' +
                            '<p>' + Drupal.t('Error al buscar. Inténtalo de nuevo.') + '</p></div>';
                    })
                    .finally(function () {
                        state.loading = false;
                        hideLoader(loader);
                    });
            }

            function updateMeta() {
                if (metaContainer) {
                    metaContainer.innerHTML = '<span class="agro-search-meta__count">' +
                        state.total + ' ' + Drupal.t('productos encontrados') + '</span>';
                }
            }

            function updatePagination() {
                if (pagination) {
                    pagination.style.display = (state.offset < state.total) ? 'block' : 'none';
                }
            }

            function updateActiveFilters() {
                if (!filtersContainer) return;
                var html = '';
                if (state.categoryName) {
                    html += '<span class="agro-filter-chip">' + Drupal.checkPlain(state.categoryName) +
                        ' <span class="agro-filter-chip__remove" data-clear="category">✕</span></span>';
                }
                if (state.minPrice) {
                    html += '<span class="agro-filter-chip">' + Drupal.t('Min') + ': ' + state.minPrice + '€' +
                        ' <span class="agro-filter-chip__remove" data-clear="min_price">✕</span></span>';
                }
                if (state.maxPrice) {
                    html += '<span class="agro-filter-chip">' + Drupal.t('Max') + ': ' + state.maxPrice + '€' +
                        ' <span class="agro-filter-chip__remove" data-clear="max_price">✕</span></span>';
                }
                filtersContainer.innerHTML = html;

                // Bind remove.
                filtersContainer.querySelectorAll('.agro-filter-chip__remove').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var key = this.dataset.clear;
                        if (key === 'category') {
                            state.categoryId = null;
                            state.categoryName = null;
                            root.querySelectorAll('.agro-search-categories__link--active')
                                .forEach(function (l) { l.classList.remove('agro-search-categories__link--active'); });
                        } else if (key === 'min_price') {
                            state.minPrice = null;
                            if (minPriceInput) minPriceInput.value = '';
                        } else if (key === 'max_price') {
                            state.maxPrice = null;
                            if (maxPriceInput) maxPriceInput.value = '';
                        }
                        doSearch(false);
                    });
                });
            }

            // --- Event Listeners ---
            var debouncedSearch = debounce(function () {
                state.query = input.value.trim();
                doSearch(false);
            }, DEBOUNCE_MS);

            if (input) {
                input.addEventListener('input', function () {
                    debouncedSearch();
                    // Autocomplete.
                    var val = this.value.trim();
                    if (val.length >= 2) {
                        fetch(API_BASE + '/search/autocomplete?q=' + encodeURIComponent(val))
                            .then(function (r) { return r.json(); })
                            .then(function (suggestions) {
                                if (!suggestions || !suggestions.length) {
                                    autocompleteBox.classList.remove('agro-search-autocomplete--visible');
                                    return;
                                }
                                var html = '';
                                suggestions.forEach(function (s) {
                                    html += '<div class="agro-search-autocomplete__item" data-value="' +
                                        Drupal.checkPlain(s.value) + '">' + Drupal.checkPlain(s.label) + '</div>';
                                });
                                autocompleteBox.innerHTML = html;
                                autocompleteBox.classList.add('agro-search-autocomplete--visible');

                                autocompleteBox.querySelectorAll('.agro-search-autocomplete__item')
                                    .forEach(function (item) {
                                        item.addEventListener('click', function () {
                                            input.value = this.dataset.value;
                                            state.query = this.dataset.value;
                                            autocompleteBox.classList.remove('agro-search-autocomplete--visible');
                                            doSearch(false);
                                        });
                                    });
                            });
                    } else {
                        autocompleteBox.classList.remove('agro-search-autocomplete--visible');
                    }
                });

                // Close autocomplete on blur.
                document.addEventListener('click', function (e) {
                    if (!autocompleteBox.contains(e.target) && e.target !== input) {
                        autocompleteBox.classList.remove('agro-search-autocomplete--visible');
                    }
                });
            }

            if (sortSelect) {
                sortSelect.addEventListener('change', function () {
                    state.sort = this.value;
                    doSearch(false);
                });
            }

            if (loadMoreBtn) {
                loadMoreBtn.addEventListener('click', function () {
                    doSearch(true);
                });
            }

            // Category filter links.
            root.querySelectorAll('.agro-search-categories__link').forEach(function (link) {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    root.querySelectorAll('.agro-search-categories__link--active')
                        .forEach(function (l) { l.classList.remove('agro-search-categories__link--active'); });
                    this.classList.add('agro-search-categories__link--active');

                    state.categoryId = this.dataset.categoryId;
                    state.categoryName = this.querySelector('.agro-search-categories__name')
                        ? this.querySelector('.agro-search-categories__name').textContent.trim()
                        : this.textContent.trim();
                    doSearch(false);
                });
            });

            // Price filter.
            var debouncedPrice = debounce(function () {
                state.minPrice = minPriceInput && minPriceInput.value ? minPriceInput.value : null;
                state.maxPrice = maxPriceInput && maxPriceInput.value ? maxPriceInput.value : null;
                doSearch(false);
            }, 600);

            if (minPriceInput) minPriceInput.addEventListener('input', debouncedPrice);
            if (maxPriceInput) maxPriceInput.addEventListener('input', debouncedPrice);
        }
    };

    // ===================================================
    // Behavior: Category Page
    // ===================================================

    Drupal.behaviors.agroconectaCategory = {
        attach: function (context) {
            var roots = once('agro-category', '#agro-category-root', context);
            if (!roots.length) return;
            var root = roots[0];

            var sortSelect = root.querySelector('#agro-category-sort');
            var resultsGrid = root.querySelector('#agro-category-results');
            var loader = root.querySelector('#agro-category-loader');
            var loadMoreBtn = root.querySelector('#agro-category-load-more');

            var settings = drupalSettings.agroCategory || {};
            var state = {
                categoryId: settings.categoryId,
                sort: 'newest',
                offset: settings.initialCount || 0,
                limit: 24,
                total: settings.totalProducts || 0,
                loading: false
            };

            function loadProducts(append) {
                if (state.loading || !state.categoryId) return;
                state.loading = true;
                showLoader(loader);

                var url = API_BASE + '/categories/' + state.categoryId + '/products' +
                    '?sort=' + state.sort + '&limit=' + state.limit + '&offset=' + (append ? state.offset : 0);

                fetch(url)
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        state.total = data.total || 0;
                        if (!append) {
                            state.offset = 0;
                            resultsGrid.innerHTML = '';
                        }

                        if (data.results && data.results.length > 0) {
                            var html = '';
                            data.results.forEach(function (product) {
                                html += renderProductCard(product);
                            });
                            resultsGrid.insertAdjacentHTML('beforeend', html);
                            state.offset += data.results.length;
                        }

                        var paginationEl = root.querySelector('#agro-category-pagination');
                        if (paginationEl) {
                            paginationEl.style.display = (state.offset < state.total) ? 'block' : 'none';
                        }
                    })
                    .catch(function () {
                        resultsGrid.innerHTML = '<div class="agro-search-empty">' +
                            '<p>' + Drupal.t('Error al cargar productos.') + '</p></div>';
                    })
                    .finally(function () {
                        state.loading = false;
                        hideLoader(loader);
                    });
            }

            if (sortSelect) {
                sortSelect.addEventListener('change', function () {
                    state.sort = this.value;
                    loadProducts(false);
                });
            }

            if (loadMoreBtn) {
                loadMoreBtn.addEventListener('click', function () {
                    loadProducts(true);
                });
            }
        }
    };

    // ===================================================
    // Behavior: Collection Page
    // ===================================================

    Drupal.behaviors.agroconectaCollection = {
        attach: function (context) {
            var roots = once('agro-collection', '#agro-collection-root', context);
            if (!roots.length) return;
            var root = roots[0];

            var loadMoreBtn = root.querySelector('#agro-collection-load-more');
            var resultsGrid = root.querySelector('#agro-collection-results');

            var settings = drupalSettings.agroCollection || {};
            var state = {
                collectionId: settings.collectionId,
                offset: settings.initialCount || 0,
                limit: 24,
                total: settings.totalProducts || 0,
                loading: false
            };

            if (loadMoreBtn) {
                loadMoreBtn.addEventListener('click', function () {
                    if (state.loading || !state.collectionId) return;
                    state.loading = true;

                    fetch(API_BASE + '/collections/' + state.collectionId + '?limit=' + state.limit + '&offset=' + state.offset)
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            if (data.results && data.results.length > 0) {
                                var html = '';
                                data.results.forEach(function (product) {
                                    html += renderProductCard(product);
                                });
                                resultsGrid.insertAdjacentHTML('beforeend', html);
                                state.offset += data.results.length;
                            }

                            var paginationEl = root.querySelector('#agro-collection-pagination');
                            if (paginationEl) {
                                paginationEl.style.display = (state.offset < state.total) ? 'block' : 'none';
                            }
                        })
                        .finally(function () {
                            state.loading = false;
                        });
                });
            }
        }
    };

    // ===================================================
    // Animaciones globales de entrada para product cards
    // ===================================================

    Drupal.behaviors.agroconectaSearchAnimations = {
        attach: function (context) {
            var cards = once('search-animate', '.agro-product-card, .agro-collection-card', context);
            cards.forEach(function (card, index) {
                card.style.opacity = '0';
                card.style.transform = 'translateY(10px)';
                setTimeout(function () {
                    card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 40);
            });
        }
    };

})(Drupal, drupalSettings, once);

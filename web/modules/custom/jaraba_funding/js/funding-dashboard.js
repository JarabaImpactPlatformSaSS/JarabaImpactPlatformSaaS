/**
 * @file
 * Funding Dashboard — Tab switching, search, results loading and stats.
 *
 * Handles the interactive elements of the Funding Intelligence dashboard
 * including tab navigation, search form submission, filter handling,
 * results rendering with match cards, and auto-refresh.
 *
 * Funding Intelligence module.
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.fundingDashboard = {
    attach: function (context) {
      once('funding-dashboard', '.ej-funding', context).forEach(function (container) {
        var tabs = container.querySelectorAll('.ej-funding__tab');
        var panels = container.querySelectorAll('.funding-panel');
        var searchForm = container.querySelector('#funding-search-form');
        var resultsContent = container.querySelector('#funding-results-content');
        var resultsLoading = container.querySelector('#funding-results-loading');
        var resultsEmpty = container.querySelector('#funding-results-empty');
        var resultsError = container.querySelector('#funding-results-error');
        var matchesContent = container.querySelector('#funding-matches-content');
        var matchesLoading = container.querySelector('#funding-matches-loading');
        var matchesEmpty = container.querySelector('#funding-matches-empty');
        var statsContainer = container.querySelector('#funding-stats');
        var refreshInterval = null;

        // ========================================
        // Tab switching with ARIA.
        // ========================================
        tabs.forEach(function (tab) {
          tab.addEventListener('click', function (e) {
            e.preventDefault();
            var target = tab.getAttribute('data-tab');

            tabs.forEach(function (t) {
              t.classList.remove('ej-funding__tab--active');
              t.setAttribute('aria-selected', 'false');
            });
            tab.classList.add('ej-funding__tab--active');
            tab.setAttribute('aria-selected', 'true');

            panels.forEach(function (p) {
              p.style.display = p.classList.contains('funding-panel--' + target) ? 'block' : 'none';
            });

            // Load matches when switching to matches tab.
            if (target === 'matches') {
              loadMatches();
            }
          });
        });

        // ========================================
        // Search form submission.
        // ========================================
        if (searchForm) {
          searchForm.addEventListener('submit', function (e) {
            e.preventDefault();
            submitSearch();
          });
        }

        /**
         * Collect filter values from the search form.
         *
         * @return {Object}
         *   Object with filter key-value pairs.
         */
        function collectFilters() {
          var filters = {};
          var qInput = container.querySelector('#funding-search-input');
          var regionSelect = container.querySelector('#funding-filter-region');
          var sectorSelect = container.querySelector('#funding-filter-sector');
          var tipoSelect = container.querySelector('#funding-filter-tipo');
          var estadoSelect = container.querySelector('#funding-filter-estado');
          var desdeInput = container.querySelector('#funding-filter-desde');
          var hastaInput = container.querySelector('#funding-filter-hasta');

          if (qInput && qInput.value.trim()) {
            filters.q = qInput.value.trim();
          }
          if (regionSelect && regionSelect.value) {
            filters.region = regionSelect.value;
          }
          if (sectorSelect && sectorSelect.value) {
            filters.sector = sectorSelect.value;
          }
          if (tipoSelect && tipoSelect.value) {
            filters.tipo = tipoSelect.value;
          }
          if (estadoSelect && estadoSelect.value) {
            filters.estado = estadoSelect.value;
          }
          if (desdeInput && desdeInput.value) {
            filters.deadline_from = desdeInput.value;
          }
          if (hastaInput && hastaInput.value) {
            filters.deadline_to = hastaInput.value;
          }

          return filters;
        }

        /**
         * Build query string from filter object.
         *
         * @param {Object} params
         *   Key-value pairs.
         *
         * @return {string}
         *   URL query string.
         */
        function buildQueryString(params) {
          var parts = [];
          Object.keys(params).forEach(function (key) {
            parts.push(encodeURIComponent(key) + '=' + encodeURIComponent(params[key]));
          });
          return parts.length > 0 ? '?' + parts.join('&') : '';
        }

        /**
         * Submit a search query via the API.
         */
        function submitSearch() {
          var filters = collectFilters();

          showSearchLoading();

          var baseUrl = (drupalSettings.fundingIntelligence && drupalSettings.fundingIntelligence.apiSearchUrl)
            || '/api/v1/funding/calls';

          fetch(baseUrl + buildQueryString(filters), {
            method: 'GET',
            headers: {
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            }
          })
            .then(function (response) {
              if (!response.ok) {
                throw new Error('HTTP ' + response.status);
              }
              return response.json();
            })
            .then(function (data) {
              if (data.success && data.data && data.data.length > 0) {
                showSearchResults(data.data);
              } else if (data.success && (!data.data || data.data.length === 0)) {
                showSearchEmpty();
              } else {
                showSearchError(data.message || Drupal.t('Error al buscar convocatorias.'));
              }
            })
            .catch(function (error) {
              console.warn('Funding Intelligence: Failed to search', error);
              showSearchError(Drupal.t('Error de conexion. Intente de nuevo.'));
            });
        }

        /**
         * Show search loading indicator.
         */
        function showSearchLoading() {
          if (resultsLoading) {
            resultsLoading.style.display = 'block';
          }
          if (resultsContent) {
            resultsContent.style.display = 'none';
          }
          if (resultsEmpty) {
            resultsEmpty.style.display = 'none';
          }
          if (resultsError) {
            resultsError.style.display = 'none';
          }
        }

        /**
         * Display search results as cards.
         *
         * @param {Array} calls
         *   Array of funding call objects.
         */
        function showSearchResults(calls) {
          if (resultsLoading) {
            resultsLoading.style.display = 'none';
          }
          if (resultsEmpty) {
            resultsEmpty.style.display = 'none';
          }
          if (resultsError) {
            resultsError.style.display = 'none';
          }
          if (resultsContent) {
            resultsContent.style.display = 'block';
            resultsContent.innerHTML = buildCallCardsHtml(calls);
          }
        }

        /**
         * Show empty state for search results.
         */
        function showSearchEmpty() {
          if (resultsLoading) {
            resultsLoading.style.display = 'none';
          }
          if (resultsContent) {
            resultsContent.style.display = 'none';
          }
          if (resultsError) {
            resultsError.style.display = 'none';
          }
          if (resultsEmpty) {
            resultsEmpty.style.display = 'block';
          }
        }

        /**
         * Display search error message.
         *
         * @param {string} message
         *   Error message to display.
         */
        function showSearchError(message) {
          if (resultsLoading) {
            resultsLoading.style.display = 'none';
          }
          if (resultsContent) {
            resultsContent.style.display = 'none';
          }
          if (resultsEmpty) {
            resultsEmpty.style.display = 'none';
          }
          if (resultsError) {
            resultsError.style.display = 'block';
            var errorText = resultsError.querySelector('.funding-results__error-text');
            if (errorText) {
              errorText.textContent = message;
            }
          }
        }

        /**
         * Build HTML for funding call cards.
         *
         * @param {Array} calls
         *   Array of call objects.
         *
         * @return {string}
         *   HTML string of cards.
         */
        function buildCallCardsHtml(calls) {
          var html = '<div class="funding-results__grid">';

          calls.forEach(function (call) {
            var statusClass = call.status === 'abierta' ? 'open' : (call.status === 'proxima' ? 'upcoming' : 'closed');

            html += '<article class="funding-call-card" data-call-id="' + Drupal.checkPlain(String(call.id || '')) + '">';
            html += '<div class="funding-call-card__header">';
            html += '<h3 class="funding-call-card__title">' + Drupal.checkPlain(call.title || '') + '</h3>';
            html += '<span class="funding-call-card__status funding-call-card__status--' + statusClass + '">' + Drupal.checkPlain(call.status || '') + '</span>';
            html += '</div>';
            html += '<div class="funding-call-card__info">';
            if (call.region) {
              html += '<span class="funding-call-card__region">' + Drupal.checkPlain(call.region) + '</span>';
            }
            if (call.deadline) {
              html += '<span class="funding-call-card__deadline">' + Drupal.t('Plazo') + ': ' + Drupal.checkPlain(call.deadline) + '</span>';
            }
            if (call.amount) {
              html += '<span class="funding-call-card__amount">' + Drupal.checkPlain(call.amount) + '</span>';
            }
            html += '</div>';
            html += '<div class="funding-call-card__actions">';
            html += '<button class="funding-call-card__action" data-action="detail" data-call-id="' + Drupal.checkPlain(String(call.id || '')) + '">' + Drupal.t('Ver detalle') + '</button>';
            html += '</div>';
            html += '</article>';
          });

          html += '</div>';
          return html;
        }

        // ========================================
        // Matches tab loading.
        // ========================================

        /**
         * Load matches via API.
         */
        function loadMatches() {
          if (matchesLoading) {
            matchesLoading.style.display = 'block';
          }
          if (matchesContent) {
            matchesContent.style.display = 'none';
          }
          if (matchesEmpty) {
            matchesEmpty.style.display = 'none';
          }

          var matchesUrl = (drupalSettings.fundingIntelligence && drupalSettings.fundingIntelligence.apiMatchesUrl)
            || '/api/v1/funding/matches';

          fetch(matchesUrl, {
            method: 'GET',
            headers: {
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            }
          })
            .then(function (response) {
              if (!response.ok) {
                throw new Error('HTTP ' + response.status);
              }
              return response.json();
            })
            .then(function (data) {
              if (matchesLoading) {
                matchesLoading.style.display = 'none';
              }
              if (data.success && data.data && data.data.length > 0) {
                if (matchesContent) {
                  matchesContent.style.display = 'block';
                  matchesContent.innerHTML = buildMatchCardsHtml(data.data);
                }
              } else {
                if (matchesEmpty) {
                  matchesEmpty.style.display = 'block';
                }
              }
            })
            .catch(function (error) {
              console.warn('Funding Intelligence: Failed to load matches', error);
              if (matchesLoading) {
                matchesLoading.style.display = 'none';
              }
              if (matchesEmpty) {
                matchesEmpty.style.display = 'block';
              }
            });
        }

        /**
         * Build HTML for match cards.
         *
         * @param {Array} matches
         *   Array of match objects with score, call and breakdown.
         *
         * @return {string}
         *   HTML string of match cards.
         */
        function buildMatchCardsHtml(matches) {
          var html = '<div class="funding-matches__grid">';

          matches.forEach(function (match) {
            var scoreClass = match.score >= 80 ? 'high' : (match.score >= 60 ? 'medium' : 'low');

            html += '<article class="funding-match-card" data-call-id="' + Drupal.checkPlain(String(match.call_id || '')) + '">';

            // Score badge.
            html += '<div class="funding-match-card__score funding-match-card__score--' + scoreClass + '">';
            html += '<span class="funding-match-card__score-value">' + Math.round(match.score) + '</span>';
            html += '<span class="funding-match-card__score-label">' + Drupal.t('Match') + '</span>';
            html += '</div>';

            // Body.
            html += '<div class="funding-match-card__body">';
            html += '<div class="funding-match-card__header">';
            html += '<h3 class="funding-match-card__title">' + Drupal.checkPlain(match.title || '') + '</h3>';
            if (match.region) {
              html += '<span class="funding-match-card__region-badge">' + Drupal.checkPlain(match.region) + '</span>';
            }
            html += '</div>';

            // Info.
            html += '<div class="funding-match-card__info">';
            if (match.deadline) {
              html += '<div class="funding-match-card__info-item"><span class="funding-match-card__info-label">' + Drupal.t('Plazo') + ':</span> ' + Drupal.checkPlain(match.deadline) + '</div>';
            }
            if (match.amount) {
              html += '<div class="funding-match-card__info-item"><span class="funding-match-card__info-label">' + Drupal.t('Importe') + ':</span> ' + Drupal.checkPlain(match.amount) + '</div>';
            }
            html += '</div>';

            // Breakdown bars.
            if (match.breakdown) {
              html += '<div class="funding-match-card__breakdown">';
              var criteria = ['region', 'beneficiary', 'sector', 'size', 'requirements'];
              var labels = {
                region: Drupal.t('Region'),
                beneficiary: Drupal.t('Beneficiario'),
                sector: Drupal.t('Sector'),
                size: Drupal.t('Tamano'),
                requirements: Drupal.t('Requisitos')
              };
              criteria.forEach(function (key) {
                var value = match.breakdown[key] || 0;
                html += '<div class="funding-match-card__breakdown-item">';
                html += '<span class="funding-match-card__breakdown-label">' + labels[key] + '</span>';
                html += '<div class="funding-match-card__breakdown-bar"><div class="funding-match-card__breakdown-fill" style="width:' + value + '%;"></div></div>';
                html += '</div>';
              });
              html += '</div>';
            }

            html += '</div>';

            // Actions.
            html += '<div class="funding-match-card__actions">';
            html += '<button class="funding-match-card__action funding-match-card__action--detail" data-action="detail">' + Drupal.t('Ver detalle') + '</button>';
            html += '<button class="funding-match-card__action funding-match-card__action--interested" data-action="interested">' + Drupal.t('Me interesa') + '</button>';
            html += '<button class="funding-match-card__action funding-match-card__action--dismiss" data-action="dismiss">' + Drupal.t('Descartar') + '</button>';
            html += '</div>';

            html += '</article>';
          });

          html += '</div>';
          return html;
        }

        // ========================================
        // Stats loading.
        // ========================================

        /**
         * Load dashboard stats via API.
         */
        function loadStats() {
          var statsUrl = (drupalSettings.fundingIntelligence && drupalSettings.fundingIntelligence.apiStatsUrl)
            || '/api/v1/funding/stats';

          fetch(statsUrl, {
            method: 'GET',
            headers: {
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            }
          })
            .then(function (response) {
              if (!response.ok) {
                throw new Error('HTTP ' + response.status);
              }
              return response.json();
            })
            .then(function (data) {
              if (data.success && data.data && statsContainer) {
                statsContainer.innerHTML = buildStatsHtml(data.data);
              }
            })
            .catch(function (error) {
              console.warn('Funding Intelligence: Failed to load stats', error);
            });
        }

        /**
         * Build HTML for stats KPI cards.
         *
         * @param {Object} stats
         *   Stats data object.
         *
         * @return {string}
         *   HTML string.
         */
        function buildStatsHtml(stats) {
          var html = '<div class="funding-stats__grid">';

          if (stats.total_calls !== undefined) {
            html += '<div class="funding-stats__card">';
            html += '<span class="funding-stats__value">' + stats.total_calls + '</span>';
            html += '<span class="funding-stats__label">' + Drupal.t('Convocatorias activas') + '</span>';
            html += '</div>';
          }

          if (stats.total_matches !== undefined) {
            html += '<div class="funding-stats__card">';
            html += '<span class="funding-stats__value">' + stats.total_matches + '</span>';
            html += '<span class="funding-stats__label">' + Drupal.t('Matches encontrados') + '</span>';
            html += '</div>';
          }

          if (stats.total_amount !== undefined) {
            html += '<div class="funding-stats__card">';
            html += '<span class="funding-stats__value">' + stats.total_amount + '</span>';
            html += '<span class="funding-stats__label">' + Drupal.t('Importe total disponible') + '</span>';
            html += '</div>';
          }

          if (stats.upcoming_deadlines !== undefined) {
            html += '<div class="funding-stats__card">';
            html += '<span class="funding-stats__value">' + stats.upcoming_deadlines + '</span>';
            html += '<span class="funding-stats__label">' + Drupal.t('Plazos proximos') + '</span>';
            html += '</div>';
          }

          html += '</div>';
          return html;
        }

        // ========================================
        // Auto-refresh every 5 minutes.
        // ========================================
        function startAutoRefresh() {
          refreshInterval = setInterval(function () {
            loadStats();
          }, 300000);
        }

        // Initial load.
        loadStats();
        startAutoRefresh();
      });
    }
  };

})(Drupal, drupalSettings, once);

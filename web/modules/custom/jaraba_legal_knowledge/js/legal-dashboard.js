/**
 * @file
 * Legal Dashboard — Query submission, AJAX results loading, and tab switching.
 *
 * Handles the interactive elements of the Legal Knowledge query page including
 * tab navigation between panels, query form submission, and dynamic results
 * loading via the API.
 *
 * Legal Knowledge module.
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.legalDashboard = {
    attach: function (context) {
      once('legal-dashboard', '.ej-legal', context).forEach(function (container) {
        var tabs = container.querySelectorAll('.ej-legal__tab');
        var panels = container.querySelectorAll('.legal-panel');
        var queryForm = container.querySelector('#legal-query-form');
        var resultsPanel = container.querySelector('#legal-results-panel');
        var resultsContent = container.querySelector('#legal-results-content');
        var resultsLoading = container.querySelector('#legal-results-loading');
        var resultsEmpty = container.querySelector('#legal-results-empty');
        var resultsError = container.querySelector('#legal-results-error');

        // ========================================
        // Tab switching.
        // ========================================
        tabs.forEach(function (tab) {
          tab.addEventListener('click', function (e) {
            e.preventDefault();
            var target = tab.getAttribute('data-tab');

            tabs.forEach(function (t) {
              t.classList.remove('ej-legal__tab--active');
              t.setAttribute('aria-selected', 'false');
            });
            tab.classList.add('ej-legal__tab--active');
            tab.setAttribute('aria-selected', 'true');

            panels.forEach(function (p) {
              p.style.display = p.classList.contains('legal-panel--' + target) ? 'block' : 'none';
            });
          });
        });

        // ========================================
        // Query form submission.
        // ========================================
        if (queryForm) {
          queryForm.addEventListener('submit', function (e) {
            e.preventDefault();
            submitLegalQuery();
          });
        }

        /**
         * Submit a legal query via the API.
         */
        function submitLegalQuery() {
          var queryInput = container.querySelector('#legal-query-input');
          var scopeSelect = container.querySelector('#legal-filter-scope');
          var subjectSelect = container.querySelector('#legal-filter-subject');

          if (!queryInput || !queryInput.value.trim()) {
            return;
          }

          var payload = {
            query: queryInput.value.trim()
          };

          if (scopeSelect && scopeSelect.value) {
            payload.scope = scopeSelect.value;
          }

          if (subjectSelect && subjectSelect.value) {
            payload.subject_areas = [subjectSelect.value];
          }

          showLoading();

          var url = (drupalSettings.legalKnowledge && drupalSettings.legalKnowledge.apiQueryUrl)
            || '/api/v1/legal/query';

          fetch(url, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
          })
            .then(function (response) {
              if (!response.ok) {
                throw new Error('HTTP ' + response.status);
              }
              return response.json();
            })
            .then(function (data) {
              if (data.success) {
                showResults(data.data);
              } else {
                showError(data.message || Drupal.t('Error al procesar la consulta.'));
              }
            })
            .catch(function (error) {
              console.warn('Legal Knowledge: Failed to submit query', error);
              showError(Drupal.t('Error de conexion. Intente de nuevo.'));
            });
        }

        /**
         * Show loading indicator.
         */
        function showLoading() {
          if (resultsLoading) {
            resultsLoading.style.display = 'block';
          }
          if (resultsContent) {
            resultsContent.style.display = 'none';
          }
          if (resultsError) {
            resultsError.style.display = 'none';
          }
        }

        /**
         * Display query results.
         *
         * @param {Object} data
         *   The response data containing answer, citations, confidence.
         */
        function showResults(data) {
          if (resultsLoading) {
            resultsLoading.style.display = 'none';
          }
          if (resultsError) {
            resultsError.style.display = 'none';
          }
          if (resultsContent) {
            resultsContent.style.display = 'block';
            resultsContent.innerHTML = buildResponseHtml(data);
          }
        }

        /**
         * Display error message.
         *
         * @param {string} message
         *   Error message to display.
         */
        function showError(message) {
          if (resultsLoading) {
            resultsLoading.style.display = 'none';
          }
          if (resultsContent) {
            resultsContent.style.display = 'none';
          }
          if (resultsError) {
            resultsError.style.display = 'block';
            var errorText = resultsError.querySelector('.legal-results__error-text');
            if (errorText) {
              errorText.textContent = message;
            }
          }
        }

        /**
         * Build HTML from response data.
         *
         * @param {Object} data
         *   Response data object.
         *
         * @return {string}
         *   HTML string.
         */
        function buildResponseHtml(data) {
          var html = '<article class="legal-response">';

          // Confidence.
          if (data.confidence) {
            var level = data.confidence >= 0.7 ? 'high' : (data.confidence >= 0.4 ? 'medium' : 'low');
            html += '<div class="legal-response__confidence">';
            html += '<span class="legal-response__confidence-label">' + Drupal.t('Confianza') + ':</span> ';
            html += '<span class="legal-response__confidence-value legal-response__confidence-value--' + level + '">';
            html += Math.round(data.confidence * 100) + '%</span>';
            html += '</div>';
          }

          // Answer.
          html += '<div class="legal-response__answer">' + (data.answer || '') + '</div>';

          // Citations.
          if (data.citations && data.citations.length > 0) {
            html += '<div class="legal-response__citations">';
            html += '<h3 class="legal-response__citations-title">' + Drupal.t('Fuentes normativas') + '</h3>';
            html += '<ul class="legal-response__citations-list">';
            data.citations.forEach(function (citation) {
              html += '<li class="legal-response__citation-item">';
              html += '<div class="legal-citation">';
              if (citation.boe_url) {
                html += '<a href="' + Drupal.checkPlain(citation.boe_url) + '" target="_blank" rel="noopener noreferrer" class="legal-citation__link">';
                html += Drupal.checkPlain(citation.title || '') + '</a>';
              } else {
                html += '<span class="legal-citation__title">' + Drupal.checkPlain(citation.title || '') + '</span>';
              }
              if (citation.article) {
                html += ' <span class="legal-citation__article">' + Drupal.t('Art.') + ' ' + Drupal.checkPlain(citation.article) + '</span>';
              }
              if (citation.publication_date) {
                html += ' <span class="legal-citation__date">(' + Drupal.checkPlain(citation.publication_date) + ')</span>';
              }
              html += '</div>';
              html += '</li>';
            });
            html += '</ul></div>';
          }

          // Disclaimer.
          html += '<div class="legal-response__disclaimer" role="alert">';
          html += '<strong>' + Drupal.t('Aviso legal') + ':</strong> ';
          html += Drupal.t('Esta informacion tiene caracter orientativo y no constituye asesoramiento juridico profesional. Consulte siempre con un abogado o asesor fiscal cualificado antes de tomar decisiones legales.');
          html += '</div>';

          html += '</article>';
          return html;
        }
      });
    }
  };

})(Drupal, drupalSettings, once);

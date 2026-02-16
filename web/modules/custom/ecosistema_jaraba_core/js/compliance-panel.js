/**
 * @file
 * Compliance Panel Unificado — AJAX refresh y UI interactions.
 *
 * ESTRUCTURA:
 * Behavior de Drupal para el Panel de Compliance Unificado cross-modulo.
 * Refresca automaticamente los KPIs via API y actualiza el DOM.
 *
 * LOGICA:
 * - Auto-refresh configurable via drupalSettings.compliancePanel.refreshInterval.
 * - Boton manual de refresh con feedback visual (spinner).
 * - Actualiza score, grado, KPIs y alertas sin recargar pagina.
 *
 * RELACIONES:
 * - API endpoint: /api/v1/compliance/overview
 * - CompliancePanelController::apiOverview()
 * - Template: compliance-panel.html.twig
 *
 * Spec: Plan Stack Compliance Legal N1 — FASE 12.
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Grade-to-CSS modifier mapping.
   */
  const GRADE_MODIFIERS = {
    A: 'excellent',
    B: 'good',
    C: 'acceptable',
    D: 'warning',
    F: 'critical',
  };

  /**
   * Module labels for display.
   */
  const MODULE_LABELS = {
    jaraba_privacy: Drupal.t('Privacidad y RGPD'),
    jaraba_legal: Drupal.t('Legal y Contratos'),
    jaraba_dr: Drupal.t('Continuidad (DR)'),
  };

  /**
   * Alert type labels.
   */
  const ALERT_LABELS = {
    critical: Drupal.t('Critico'),
    warning: Drupal.t('Alerta'),
  };

  Drupal.behaviors.jarabaCompliancePanel = {
    attach: function (context) {
      once('jarabaCompliancePanel', '.compliance-panel', context).forEach(function (panel) {
        var settings = drupalSettings.compliancePanel || {};
        var endpoint = settings.refreshEndpoint || '/api/v1/compliance/overview';
        var interval = settings.refreshInterval || 60000;
        var refreshBtn = panel.querySelector('#compliance-panel-refresh');
        var isRefreshing = false;

        /**
         * Fetches fresh compliance data from the API.
         */
        function fetchData() {
          if (isRefreshing) {
            return;
          }
          isRefreshing = true;

          if (refreshBtn) {
            refreshBtn.classList.add('compliance-panel__refresh-btn--loading');
            refreshBtn.disabled = true;
          }

          fetch(endpoint, {
            method: 'GET',
            headers: {
              Accept: 'application/json',
            },
            credentials: 'same-origin',
          })
            .then(function (response) {
              if (!response.ok) {
                throw new Error('HTTP ' + response.status);
              }
              return response.json();
            })
            .then(function (json) {
              if (json.success && json.data) {
                updatePanel(json.data);
              }
            })
            .catch(function (error) {
              // eslint-disable-next-line no-console
              console.warn('[CompliancePanel] Refresh failed:', error.message);
            })
            .finally(function () {
              isRefreshing = false;
              if (refreshBtn) {
                refreshBtn.classList.remove('compliance-panel__refresh-btn--loading');
                refreshBtn.disabled = false;
              }
            });
        }

        /**
         * Updates the panel DOM with fresh data.
         *
         * @param {object} data - The compliance overview data.
         */
        function updatePanel(data) {
          updateScore(data.score, data.grade);
          updateKpis(data.kpis);
          updateAlerts(data.alerts);
        }

        /**
         * Updates the global score and grade display.
         */
        function updateScore(score, grade) {
          var scoreEl = panel.querySelector('#compliance-panel-score');
          var gradeEl = panel.querySelector('#compliance-panel-grade');
          var scoreSection = panel.querySelector('.compliance-panel__score');
          var fillBar = panel.querySelector('.compliance-panel__score-fill');

          if (scoreEl) {
            scoreEl.textContent = score;
          }

          if (gradeEl) {
            gradeEl.textContent = grade;
            // Update grade modifier class.
            Object.values(GRADE_MODIFIERS).forEach(function (mod) {
              gradeEl.classList.remove('compliance-panel__grade--' + mod);
            });
            gradeEl.classList.add('compliance-panel__grade--' + (GRADE_MODIFIERS[grade] || 'critical'));
          }

          if (scoreSection) {
            Object.values(GRADE_MODIFIERS).forEach(function (mod) {
              scoreSection.classList.remove('compliance-panel__score--' + mod);
            });
            scoreSection.classList.add('compliance-panel__score--' + (GRADE_MODIFIERS[grade] || 'critical'));
          }

          if (fillBar) {
            fillBar.style.width = score + '%';
          }

          panel.dataset.complianceScore = score;
        }

        /**
         * Updates KPI cards with fresh values.
         */
        function updateKpis(kpis) {
          var container = panel.querySelector('#compliance-panel-kpis');
          if (!container || !Array.isArray(kpis)) {
            return;
          }

          kpis.forEach(function (kpi) {
            var card = container.querySelector('[data-kpi-key="' + kpi.key + '"]');
            if (!card) {
              return;
            }

            // Update status modifier.
            ['good', 'warning', 'critical', 'not_available'].forEach(function (status) {
              card.classList.remove('compliance-panel__kpi--' + status);
            });
            card.classList.add('compliance-panel__kpi--' + kpi.status);

            // Update value.
            var valueEl = card.querySelector('.compliance-panel__kpi-value');
            if (valueEl) {
              if (kpi.status === 'not_available') {
                valueEl.textContent = '\u2014';
                valueEl.classList.add('compliance-panel__kpi-value--na');
              }
              else {
                valueEl.textContent = kpi.value + '%';
                valueEl.classList.remove('compliance-panel__kpi-value--na');
              }
            }

            // Update indicator.
            var indicator = card.querySelector('.compliance-panel__kpi-indicator');
            if (indicator) {
              ['good', 'warning', 'critical', 'not_available'].forEach(function (status) {
                indicator.classList.remove('compliance-panel__kpi-indicator--' + status);
              });
              indicator.classList.add('compliance-panel__kpi-indicator--' + kpi.status);
            }
          });
        }

        /**
         * Updates the alerts list with fresh data.
         */
        function updateAlerts(alerts) {
          var container = panel.querySelector('#compliance-panel-alerts');
          var countEl = panel.querySelector('.compliance-panel__alerts-count');
          if (!container) {
            return;
          }

          if (countEl) {
            if (alerts && alerts.length > 0) {
              countEl.textContent = alerts.length;
              countEl.style.display = '';
            }
            else {
              countEl.style.display = 'none';
            }
          }

          if (!alerts || alerts.length === 0) {
            container.innerHTML =
              '<div class="compliance-panel__alerts-empty">' +
              '<p>' + Drupal.t('No hay alertas de compliance activas. Todos los indicadores dentro de umbrales.') + '</p>' +
              '</div>';
            return;
          }

          var html = '';
          alerts.forEach(function (alert) {
            var typeMod = alert.type || 'warning';
            html +=
              '<div class="compliance-panel__alert compliance-panel__alert--' + typeMod + '">' +
              '<span class="compliance-panel__alert-badge compliance-panel__alert-badge--' + typeMod + '">' +
              (ALERT_LABELS[typeMod] || typeMod) +
              '</span>' +
              '<div class="compliance-panel__alert-content">' +
              '<span class="compliance-panel__alert-module">' +
              (MODULE_LABELS[alert.module] || alert.module) +
              '</span>' +
              '<span class="compliance-panel__alert-message">' + Drupal.checkPlain(alert.message) + '</span>' +
              '</div>' +
              '<span class="compliance-panel__alert-value">' + alert.value + '%</span>' +
              '</div>';
          });
          container.innerHTML = html;
        }

        // Manual refresh button.
        if (refreshBtn) {
          refreshBtn.addEventListener('click', fetchData);
        }

        // Auto-refresh interval.
        if (interval > 0) {
          setInterval(fetchData, interval);
        }
      });
    },
  };
})(Drupal, drupalSettings, once);

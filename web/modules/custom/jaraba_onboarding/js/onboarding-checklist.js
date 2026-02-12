/**
 * @file
 * Onboarding Checklist - Interactive checklist behavior.
 *
 * Implements Drupal behavior with:
 * - Click-to-complete interaction for checklist items.
 * - AJAX step completion via API.
 * - Progress bar update on completion.
 *
 * Follows project conventions: Drupal.behaviors, once(), Drupal.t().
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Behavior for Onboarding Checklist.
   */
  Drupal.behaviors.jarabaOnboardingChecklist = {
    attach: function (context) {
      once('onboarding-checklist', '.onboarding-dashboard__checklist', context).forEach(function (container) {
        var apiBase = '/api/v1/onboarding';
        var settings = drupalSettings.jarabaOnboarding || {};

        /**
         * Initialise the component.
         */
        function init() {
          setupClickHandlers();
        }

        // ================================================================
        // Event handlers
        // ================================================================

        /**
         * Wire up click handlers for checklist action buttons.
         */
        function setupClickHandlers() {
          var buttons = container.querySelectorAll('.onboarding-dashboard__checklist-action');
          buttons.forEach(function (btn) {
            btn.addEventListener('click', function (e) {
              e.preventDefault();
              var stepId = btn.getAttribute('data-step-id');
              if (stepId) {
                completeStep(stepId, btn);
              }
            });
          });
        }

        // ================================================================
        // AJAX step completion
        // ================================================================

        /**
         * Complete a step via the API.
         *
         * @param {string} stepId
         *   The step identifier.
         * @param {HTMLElement} btn
         *   The button element that was clicked.
         */
        function completeStep(stepId, btn) {
          // Find the active progress ID from settings.
          var progressData = settings.progressPercentage !== undefined ? settings : {};
          var progress = (drupalSettings.jarabaOnboarding || {}).progress || [];
          var progressId = progress.length > 0 ? progress[0].id : null;

          if (!progressId) {
            console.warn('[Onboarding] No active progress found.');
            return;
          }

          btn.disabled = true;
          btn.textContent = Drupal.t('Completing...');

          var url = apiBase + '/progress/' + progressId + '/step';

          fetch(url, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ step_id: stepId })
          })
            .then(function (response) { return response.json(); })
            .then(function (data) {
              if (data.success) {
                markStepComplete(stepId, btn);
                updateProgressBar(data.data.progress);

                // Dispatch custom event for celebration.
                var event = new CustomEvent('onboarding:stepComplete', {
                  detail: {
                    stepId: stepId,
                    progress: data.data.progress,
                    achievements: data.data.achievements
                  }
                });
                document.dispatchEvent(event);
              }
              else {
                btn.disabled = false;
                btn.textContent = Drupal.t('Complete');
                console.error('[Onboarding] Error:', data.error);
              }
            })
            .catch(function (error) {
              btn.disabled = false;
              btn.textContent = Drupal.t('Complete');
              console.error('[Onboarding] Network error:', error);
            });
        }

        /**
         * Mark a checklist item as visually completed.
         *
         * @param {string} stepId
         *   The step identifier.
         * @param {HTMLElement} btn
         *   The button to remove.
         */
        function markStepComplete(stepId, btn) {
          var item = container.querySelector('[data-step-id="' + stepId + '"]');
          if (item) {
            item.classList.add('onboarding-dashboard__checklist-item--completed');
            var icon = item.querySelector('.onboarding-dashboard__checklist-icon');
            if (icon) {
              icon.innerHTML = '&#10003;';
            }
          }
          if (btn && btn.parentNode) {
            btn.remove();
          }
        }

        /**
         * Update the progress bar with new data.
         *
         * @param {Array} progress
         *   Updated progress data from API.
         */
        function updateProgressBar(progress) {
          if (!progress || progress.length === 0) {
            return;
          }

          var latest = progress[0];
          var pct = latest.progress_percentage || 0;

          var progressSection = document.querySelector('.onboarding-dashboard__progress');
          if (!progressSection) {
            return;
          }

          var valueEl = progressSection.querySelector('.onboarding-dashboard__progress-value');
          if (valueEl) {
            valueEl.textContent = pct + '%';
          }

          var fillEl = progressSection.querySelector('.onboarding-dashboard__progress-fill');
          if (fillEl) {
            fillEl.style.width = pct + '%';
          }

          var barEl = progressSection.querySelector('.onboarding-dashboard__progress-bar');
          if (barEl) {
            barEl.setAttribute('aria-valuenow', String(pct));
          }
        }

        // Boot.
        init();
      });
    }
  };

})(Drupal, drupalSettings, once);

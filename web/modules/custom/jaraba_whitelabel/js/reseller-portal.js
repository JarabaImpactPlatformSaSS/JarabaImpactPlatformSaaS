/**
 * @file
 * JavaScript for the reseller/partner portal.
 *
 * Handles dashboard interactions including tenant search/filter,
 * commission period selector and onboarding step navigation.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Reseller dashboard behavior.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.jarabaResellerDashboard = {
    attach: function (context) {
      // --- Tenant search/filter ---
      once('reseller-dashboard-search', '#tenant-search-input', context).forEach(function (searchInput) {
        var tableBody = document.getElementById('tenants-table-body');
        if (!tableBody) {
          return;
        }

        searchInput.addEventListener('input', function () {
          var query = searchInput.value.toLowerCase().trim();
          var rows = tableBody.querySelectorAll('.reseller-dashboard__row');

          rows.forEach(function (row) {
            var cells = row.querySelectorAll('.reseller-dashboard__td');
            var text = '';
            cells.forEach(function (cell) {
              text += ' ' + cell.textContent.toLowerCase();
            });

            row.style.display = query === '' || text.indexOf(query) !== -1 ? '' : 'none';
          });
        });
      });

      // --- Commission period selector ---
      once('reseller-commission-period', '#commission-period', context).forEach(function (select) {
        select.addEventListener('change', function () {
          var period = select.value;

          // In a full implementation, this would fetch commission data
          // for the selected period via AJAX and update the chart area.
          if (typeof console !== 'undefined') {
            console.log('Commission period changed:', period);
          }

          var chartArea = document.getElementById('commission-chart-area');
          if (chartArea) {
            var placeholder = chartArea.querySelector('.reseller-dashboard__chart-placeholder');
            if (placeholder) {
              var periodLabels = {
                current: Drupal.t('Current Month'),
                last: Drupal.t('Last Month'),
                quarter: Drupal.t('This Quarter'),
                year: Drupal.t('This Year')
              };
              placeholder.textContent = Drupal.t('Loading data for: @period...', {
                '@period': periodLabels[period] || period
              });
            }
          }
        });
      });
    }
  };

  /**
   * Reseller onboarding behavior.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.jarabaResellerOnboarding = {
    attach: function (context) {
      once('reseller-onboarding', '.reseller-onboarding', context).forEach(function (root) {
        var panels = root.querySelectorAll('.reseller-onboarding__panel');
        var totalSteps = panels.length;
        var currentStep = parseInt(root.getAttribute('data-current-step') || '0', 10);

        var prevBtn = root.querySelector('.reseller-onboarding__btn--prev');
        var nextBtn = root.querySelector('.reseller-onboarding__btn--next');

        /**
         * Shows the panel for the given step index.
         *
         * @param {number} step - Step index to show.
         */
        function showStep(step) {
          currentStep = step;
          root.setAttribute('data-current-step', step);

          // Update panels.
          panels.forEach(function (panel, idx) {
            panel.classList.toggle('reseller-onboarding__panel--active', idx === step);
          });

          // Update progress circles.
          root.querySelectorAll('.reseller-onboarding__progress-step').forEach(function (el, idx) {
            el.classList.toggle('reseller-onboarding__progress-step--active', idx === step);
          });

          // Update buttons.
          if (prevBtn) {
            prevBtn.disabled = step === 0;
          }
          if (nextBtn) {
            nextBtn.style.display = step >= totalSteps - 1 ? 'none' : '';
          }
        }

        // Previous step.
        if (prevBtn) {
          prevBtn.addEventListener('click', function () {
            if (currentStep > 0) {
              showStep(currentStep - 1);
            }
          });
        }

        // Next step.
        if (nextBtn) {
          nextBtn.addEventListener('click', function () {
            if (currentStep < totalSteps - 1) {
              // Validate current step before proceeding.
              if (validateStep(root, currentStep)) {
                showStep(currentStep + 1);
              }
            }
          });
        }

        /**
         * Validates the current onboarding step.
         *
         * @param {HTMLElement} rootEl - The onboarding root element.
         * @param {number} step - The step index to validate.
         * @return {boolean} Whether the step is valid.
         */
        function validateStep(rootEl, step) {
          // Step 0: Company info - require company name and email.
          if (step === 0) {
            var companyName = rootEl.querySelector('#onboard-company-name');
            var contactEmail = rootEl.querySelector('#onboard-contact-email');

            if (companyName && !companyName.value.trim()) {
              companyName.focus();
              return false;
            }
            if (contactEmail && !contactEmail.value.trim()) {
              contactEmail.focus();
              return false;
            }
          }

          // Step 1: Agreement - require checkbox.
          if (step === 1) {
            var checkbox = rootEl.querySelector('[name="accept_agreement"]');
            if (checkbox && !checkbox.checked) {
              checkbox.focus();
              return false;
            }
          }

          return true;
        }

        // Progress circle click navigation.
        root.querySelectorAll('.reseller-onboarding__progress-step').forEach(function (el, idx) {
          el.style.cursor = 'pointer';
          el.addEventListener('click', function () {
            // Only allow navigating to completed or current steps.
            if (idx <= currentStep) {
              showStep(idx);
            }
          });
        });

        // Initialise display.
        showStep(currentStep);
      });
    }
  };

})(Drupal, once);

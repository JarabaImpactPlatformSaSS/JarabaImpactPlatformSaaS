/**
 * @file
 * JavaScript for the branding wizard page.
 *
 * Handles step navigation, colour picker sync, live preview updates
 * and form validation.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Branding wizard behavior.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.jarabaBrandingWizard = {
    attach: function (context) {
      once('branding-wizard', '.branding-wizard', context).forEach(function (wizard) {
        var totalSteps = wizard.querySelectorAll('.branding-wizard__panel').length;
        var currentStep = parseInt(wizard.getAttribute('data-current-step') || '0', 10);

        var prevBtn = wizard.querySelector('.branding-wizard__btn--prev');
        var nextBtn = wizard.querySelector('.branding-wizard__btn--next');
        var saveBtn = wizard.querySelector('.branding-wizard__btn--save');

        /**
         * Shows the panel for the given step index.
         *
         * @param {number} step - Step index to show.
         */
        function showStep(step) {
          currentStep = step;
          wizard.setAttribute('data-current-step', step);

          // Update panels.
          wizard.querySelectorAll('.branding-wizard__panel').forEach(function (panel, idx) {
            panel.classList.toggle('branding-wizard__panel--active', idx === step);
          });

          // Update step nav.
          wizard.querySelectorAll('.branding-wizard__step').forEach(function (stepEl, idx) {
            stepEl.classList.toggle('branding-wizard__step--active', idx === step);
            stepEl.classList.toggle('branding-wizard__step--completed', idx < step);
          });

          // Update progress bar.
          var progressBar = wizard.querySelector('.branding-wizard__progress-bar');
          if (progressBar && totalSteps > 1) {
            var percentage = (step / (totalSteps - 1)) * 100;
            progressBar.style.width = percentage + '%';
          }

          // Update buttons.
          if (prevBtn) {
            prevBtn.disabled = step === 0;
          }
          if (nextBtn && saveBtn) {
            if (step === totalSteps - 1) {
              nextBtn.style.display = 'none';
              saveBtn.style.display = '';
            }
            else {
              nextBtn.style.display = '';
              saveBtn.style.display = 'none';
            }
          }
        }

        // Step navigation click handlers.
        if (prevBtn) {
          prevBtn.addEventListener('click', function () {
            if (currentStep > 0) {
              showStep(currentStep - 1);
            }
          });
        }

        if (nextBtn) {
          nextBtn.addEventListener('click', function () {
            if (currentStep < totalSteps - 1) {
              showStep(currentStep + 1);
            }
          });
        }

        // Step label click navigation.
        wizard.querySelectorAll('.branding-wizard__step').forEach(function (stepEl) {
          stepEl.addEventListener('click', function () {
            var stepIndex = parseInt(stepEl.getAttribute('data-step'), 10);
            if (!isNaN(stepIndex)) {
              showStep(stepIndex);
            }
          });
        });

        // Colour picker sync: keep type=color and text input in sync.
        wizard.querySelectorAll('.branding-wizard__color-group').forEach(function (group) {
          var colorInput = group.querySelector('.branding-wizard__color-input');
          var textInput = group.querySelector('.branding-wizard__color-text');

          if (colorInput && textInput) {
            colorInput.addEventListener('input', function () {
              textInput.value = colorInput.value.toUpperCase();
              updateSwatchPreview(wizard);
            });

            textInput.addEventListener('input', function () {
              var val = textInput.value;
              if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
                colorInput.value = val;
                updateSwatchPreview(wizard);
              }
            });
          }
        });

        /**
         * Updates the swatch preview colours from form fields.
         *
         * @param {HTMLElement} wizardEl - The wizard root element.
         */
        function updateSwatchPreview(wizardEl) {
          var primaryInput = wizardEl.querySelector('#branding-primary-color');
          var secondaryInput = wizardEl.querySelector('#branding-secondary-color');
          var primarySwatch = wizardEl.querySelector('.branding-wizard__swatch--primary');
          var secondarySwatch = wizardEl.querySelector('.branding-wizard__swatch--secondary');

          if (primaryInput && primarySwatch) {
            primarySwatch.style.backgroundColor = primaryInput.value;
          }
          if (secondaryInput && secondarySwatch) {
            secondarySwatch.style.backgroundColor = secondaryInput.value;
          }
        }

        // Logo preview update.
        var logoInput = wizard.querySelector('#branding-logo-url');
        if (logoInput) {
          logoInput.addEventListener('change', function () {
            var preview = wizard.querySelector('.branding-wizard__logo-preview');
            var noLogo = wizard.querySelector('.branding-wizard__no-logo');
            var url = logoInput.value.trim();

            if (url && preview) {
              preview.src = url;
              preview.style.display = '';
              if (noLogo) {
                noLogo.style.display = 'none';
              }
            }
          });
        }

        // Save button.
        if (saveBtn) {
          saveBtn.addEventListener('click', function () {
            var tenantId = wizard.getAttribute('data-tenant-id');
            if (!tenantId) {
              return;
            }

            var formData = {
              tenant_id: parseInt(tenantId, 10),
              logo_url: (wizard.querySelector('#branding-logo-url') || {}).value || '',
              favicon_url: (wizard.querySelector('#branding-favicon-url') || {}).value || '',
              primary_color: (wizard.querySelector('#branding-primary-color') || {}).value || '',
              secondary_color: (wizard.querySelector('#branding-secondary-color') || {}).value || '',
              custom_css: (wizard.querySelector('#branding-custom-css') || {}).value || '',
              custom_footer_html: (wizard.querySelector('#branding-footer-html') || {}).value || '',
              hide_powered_by: (wizard.querySelector('[name="hide_powered_by"]') || {}).checked || false
            };

            // In a full implementation, this would POST to the API.
            // For now, log the form data.
            if (typeof console !== 'undefined') {
              console.log('Branding wizard save:', formData);
            }
          });
        }

        // Initialise the correct step on load.
        showStep(currentStep);
      });
    }
  };

})(Drupal, once);

/**
 * @file
 * Onboarding Tours - Guided tour integration.
 *
 * Renders contextual help tooltips based on the current route.
 * Tours are defined server-side in OnboardingContextualHelpService
 * and passed via drupalSettings.
 *
 * Follows project conventions: Drupal.behaviors, once(), Drupal.t().
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Behavior for Onboarding Tours.
   */
  Drupal.behaviors.jarabaOnboardingTours = {
    attach: function (context) {
      once('onboarding-tours', 'body', context).forEach(function (body) {
        var settings = drupalSettings.jarabaOnboarding || {};
        var helpSteps = settings.contextualHelp || [];
        var currentStepIndex = 0;
        var overlayEl = null;
        var tooltipEl = null;

        /**
         * Initialise the tour system.
         */
        function init() {
          if (helpSteps.length === 0) {
            return;
          }

          // Check if user has dismissed tours for this route.
          var routeName = settings.routeName || '';
          var dismissed = sessionStorage.getItem('onboarding_tour_dismissed_' + routeName);
          if (dismissed === 'true') {
            return;
          }

          createOverlay();
          createTooltip();
          showStep(0);
        }

        // ================================================================
        // Overlay
        // ================================================================

        /**
         * Create the tour overlay element.
         */
        function createOverlay() {
          overlayEl = document.createElement('div');
          overlayEl.className = 'onboarding-tour__overlay';
          overlayEl.setAttribute('aria-hidden', 'true');
          overlayEl.addEventListener('click', function () {
            dismissTour();
          });
          body.appendChild(overlayEl);
        }

        // ================================================================
        // Tooltip
        // ================================================================

        /**
         * Create the tour tooltip element.
         */
        function createTooltip() {
          tooltipEl = document.createElement('div');
          tooltipEl.className = 'onboarding-tour__tooltip';
          tooltipEl.setAttribute('role', 'dialog');
          tooltipEl.setAttribute('aria-label', Drupal.t('Guided tour'));
          tooltipEl.innerHTML =
            '<div class="onboarding-tour__tooltip-header">' +
              '<strong class="onboarding-tour__tooltip-title"></strong>' +
              '<button type="button" class="onboarding-tour__tooltip-close" ' +
                'aria-label="' + Drupal.t('Close tour') + '">&times;</button>' +
            '</div>' +
            '<p class="onboarding-tour__tooltip-body"></p>' +
            '<div class="onboarding-tour__tooltip-footer">' +
              '<span class="onboarding-tour__tooltip-counter"></span>' +
              '<div class="onboarding-tour__tooltip-actions">' +
                '<button type="button" class="onboarding-tour__tooltip-prev">' +
                  Drupal.t('Previous') + '</button>' +
                '<button type="button" class="onboarding-tour__tooltip-next">' +
                  Drupal.t('Next') + '</button>' +
              '</div>' +
            '</div>';

          body.appendChild(tooltipEl);

          // Wire up button events.
          var closeBtn = tooltipEl.querySelector('.onboarding-tour__tooltip-close');
          if (closeBtn) {
            closeBtn.addEventListener('click', dismissTour);
          }

          var prevBtn = tooltipEl.querySelector('.onboarding-tour__tooltip-prev');
          if (prevBtn) {
            prevBtn.addEventListener('click', function () {
              if (currentStepIndex > 0) {
                showStep(currentStepIndex - 1);
              }
            });
          }

          var nextBtn = tooltipEl.querySelector('.onboarding-tour__tooltip-next');
          if (nextBtn) {
            nextBtn.addEventListener('click', function () {
              if (currentStepIndex < helpSteps.length - 1) {
                showStep(currentStepIndex + 1);
              }
              else {
                dismissTour();
              }
            });
          }
        }

        /**
         * Show a specific tour step.
         *
         * @param {number} index
         *   Step index to display.
         */
        function showStep(index) {
          if (index < 0 || index >= helpSteps.length) {
            return;
          }

          currentStepIndex = index;
          var step = helpSteps[index];

          // Update tooltip content.
          var titleEl = tooltipEl.querySelector('.onboarding-tour__tooltip-title');
          var bodyEl = tooltipEl.querySelector('.onboarding-tour__tooltip-body');
          var counterEl = tooltipEl.querySelector('.onboarding-tour__tooltip-counter');
          var prevBtn = tooltipEl.querySelector('.onboarding-tour__tooltip-prev');
          var nextBtn = tooltipEl.querySelector('.onboarding-tour__tooltip-next');

          if (titleEl) { titleEl.textContent = step.title || ''; }
          if (bodyEl) { bodyEl.textContent = step.body || ''; }
          if (counterEl) {
            counterEl.textContent = (index + 1) + ' / ' + helpSteps.length;
          }

          // Show/hide prev button.
          if (prevBtn) {
            prevBtn.style.display = index > 0 ? '' : 'none';
          }

          // Update next button text for last step.
          if (nextBtn) {
            nextBtn.textContent = index === helpSteps.length - 1
              ? Drupal.t('Finish')
              : Drupal.t('Next');
          }

          // Position tooltip near the target element.
          positionTooltip(step);

          // Show overlay and tooltip.
          if (overlayEl) {
            overlayEl.style.display = 'block';
          }
          tooltipEl.style.display = 'block';
        }

        /**
         * Position the tooltip near the target selector.
         *
         * @param {Object} step
         *   Step configuration with selector and position.
         */
        function positionTooltip(step) {
          var targetEl = step.selector ? document.querySelector(step.selector) : null;

          if (targetEl) {
            var rect = targetEl.getBoundingClientRect();
            var position = step.position || 'bottom';

            switch (position) {
              case 'top':
                tooltipEl.style.top = (window.scrollY + rect.top - tooltipEl.offsetHeight - 12) + 'px';
                tooltipEl.style.left = (window.scrollX + rect.left + rect.width / 2 - tooltipEl.offsetWidth / 2) + 'px';
                break;

              case 'right':
                tooltipEl.style.top = (window.scrollY + rect.top + rect.height / 2 - tooltipEl.offsetHeight / 2) + 'px';
                tooltipEl.style.left = (window.scrollX + rect.right + 12) + 'px';
                break;

              case 'left':
                tooltipEl.style.top = (window.scrollY + rect.top + rect.height / 2 - tooltipEl.offsetHeight / 2) + 'px';
                tooltipEl.style.left = (window.scrollX + rect.left - tooltipEl.offsetWidth - 12) + 'px';
                break;

              case 'bottom':
              default:
                tooltipEl.style.top = (window.scrollY + rect.bottom + 12) + 'px';
                tooltipEl.style.left = (window.scrollX + rect.left + rect.width / 2 - tooltipEl.offsetWidth / 2) + 'px';
                break;
            }

            // Highlight target.
            targetEl.classList.add('onboarding-tour__highlight');
          }
          else {
            // Center on screen if no target.
            tooltipEl.style.top = '50%';
            tooltipEl.style.left = '50%';
            tooltipEl.style.transform = 'translate(-50%, -50%)';
          }
        }

        /**
         * Dismiss the tour and clean up.
         */
        function dismissTour() {
          if (overlayEl) {
            overlayEl.style.display = 'none';
          }
          if (tooltipEl) {
            tooltipEl.style.display = 'none';
          }

          // Remove highlights.
          var highlighted = document.querySelectorAll('.onboarding-tour__highlight');
          highlighted.forEach(function (el) {
            el.classList.remove('onboarding-tour__highlight');
          });

          // Remember dismissal.
          var routeName = settings.routeName || '';
          if (routeName) {
            sessionStorage.setItem('onboarding_tour_dismissed_' + routeName, 'true');
          }
        }

        // Boot.
        init();
      });
    }
  };

})(Drupal, drupalSettings, once);

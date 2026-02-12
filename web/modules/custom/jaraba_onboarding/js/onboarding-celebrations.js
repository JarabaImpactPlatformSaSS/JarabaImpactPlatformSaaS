/**
 * @file
 * Onboarding Celebrations - Confetti animation on step completion.
 *
 * Listens for the custom 'onboarding:stepComplete' event and
 * triggers a visual celebration with confetti particles.
 *
 * Follows project conventions: Drupal.behaviors, once(), Drupal.t().
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Behavior for Onboarding Celebrations.
   */
  Drupal.behaviors.jarabaOnboardingCelebrations = {
    attach: function (context) {
      once('onboarding-celebrations', 'body', context).forEach(function (body) {

        /**
         * Initialise celebrations listener.
         */
        function init() {
          document.addEventListener('onboarding:stepComplete', function (e) {
            var detail = e.detail || {};
            showCelebration(detail);
          });
        }

        // ================================================================
        // Celebration modal
        // ================================================================

        /**
         * Show the celebration overlay.
         *
         * @param {Object} detail
         *   Event detail with stepId, progress, achievements.
         */
        function showCelebration(detail) {
          // Create overlay.
          var overlay = document.createElement('div');
          overlay.className = 'onboarding-celebration';
          overlay.setAttribute('role', 'alert');
          overlay.setAttribute('aria-live', 'assertive');

          overlay.innerHTML =
            '<div class="onboarding-celebration__content">' +
              '<div class="onboarding-celebration__confetti" aria-hidden="true"></div>' +
              '<div class="onboarding-celebration__icon">&#10003;</div>' +
              '<h3 class="onboarding-celebration__title">' +
                Drupal.t('Step Completed!') + '</h3>' +
              '<p class="onboarding-celebration__message">' +
                Drupal.t('Great job! Keep going.') + '</p>' +
              '<button type="button" class="onboarding-celebration__close">' +
                Drupal.t('Continue') + '</button>' +
            '</div>';

          body.appendChild(overlay);

          // Generate confetti particles.
          launchConfetti(overlay.querySelector('.onboarding-celebration__confetti'));

          // Auto-close after 4 seconds.
          var autoCloseTimer = setTimeout(function () {
            closeCelebration(overlay);
          }, 4000);

          // Close on button click.
          var closeBtn = overlay.querySelector('.onboarding-celebration__close');
          if (closeBtn) {
            closeBtn.addEventListener('click', function () {
              clearTimeout(autoCloseTimer);
              closeCelebration(overlay);
            });
          }
        }

        /**
         * Close the celebration overlay with animation.
         *
         * @param {HTMLElement} overlay
         *   The celebration overlay element.
         */
        function closeCelebration(overlay) {
          overlay.classList.add('onboarding-celebration--closing');
          setTimeout(function () {
            if (overlay.parentNode) {
              overlay.parentNode.removeChild(overlay);
            }
          }, 300);
        }

        // ================================================================
        // Confetti particles
        // ================================================================

        /**
         * Launch confetti particles into the container.
         *
         * @param {HTMLElement} container
         *   The container for confetti particles.
         */
        function launchConfetti(container) {
          if (!container) {
            return;
          }

          var colors = [
            'var(--ej-color-primary, #2E7D32)',
            'var(--ej-color-accent, #FF9800)',
            '#4CAF50',
            '#2196F3',
            '#E91E63',
            '#FFC107',
            '#9C27B0',
          ];

          var particleCount = 40;

          for (var i = 0; i < particleCount; i++) {
            var particle = document.createElement('div');
            particle.className = 'onboarding-celebration__particle';
            particle.style.backgroundColor = colors[i % colors.length];
            particle.style.left = Math.random() * 100 + '%';
            particle.style.animationDelay = (Math.random() * 0.5) + 's';
            particle.style.animationDuration = (1.5 + Math.random() * 1.5) + 's';

            // Randomize shape.
            if (Math.random() > 0.5) {
              particle.style.borderRadius = '50%';
            }

            container.appendChild(particle);
          }
        }

        // Boot.
        init();
      });
    }
  };

})(Drupal, drupalSettings, once);

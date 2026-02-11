/**
 * @file
 * Back to Top Button - Premium UX.
 */
(function (Drupal, once) {
    'use strict';

    Drupal.behaviors.backToTop = {
        attach: function (context) {
            once('back-to-top', 'body', context).forEach(function () {
                // Create button element
                const button = document.createElement('button');
                button.className = 'back-to-top-btn';
                button.setAttribute('aria-label', Drupal.t('Volver arriba'));
                button.innerHTML = `
          <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="18 15 12 9 6 15"></polyline>
          </svg>
        `;
                document.body.appendChild(button);

                // Show/hide based on scroll position
                let isVisible = false;
                const scrollThreshold = 300;

                function toggleVisibility() {
                    const shouldShow = window.scrollY > scrollThreshold;
                    if (shouldShow !== isVisible) {
                        isVisible = shouldShow;
                        button.classList.toggle('is-visible', isVisible);
                    }
                }

                // Throttled scroll handler
                let ticking = false;
                window.addEventListener('scroll', function () {
                    if (!ticking) {
                        requestAnimationFrame(function () {
                            toggleVisibility();
                            ticking = false;
                        });
                        ticking = true;
                    }
                }, { passive: true });

                // Click handler - smooth scroll to top
                button.addEventListener('click', function () {
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                });

                // Initial check
                toggleVisibility();
            });
        }
    };
})(Drupal, once);

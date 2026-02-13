/**
 * @file
 * Floating "Back to top" button for admin pages.
 *
 * Appears after scrolling 400px, smooth scrolls to top on click.
 */
(function (Drupal, once) {
    'use strict';

    Drupal.behaviors.ejBackToTop = {
        attach: function (context) {
            once('ej-back-to-top', 'body', context).forEach(function () {
                // Create button element.
                var btn = document.createElement('button');
                btn.className = 'ej-back-to-top';
                btn.setAttribute('type', 'button');
                btn.setAttribute('aria-label', Drupal.t('Volver arriba'));
                btn.setAttribute('title', Drupal.t('Volver arriba'));
                btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="22" height="22">' +
                    '<path fill-rule="evenodd" d="M9.47 6.47a.75.75 0 011.06 0l4.25 4.25a.75.75 0 11-1.06 1.06L10 8.06l-3.72 3.72a.75.75 0 01-1.06-1.06l4.25-4.25z" clip-rule="evenodd"/>' +
                    '</svg>';
                document.body.appendChild(btn);

                // Show/hide based on scroll position.
                var scrollThreshold = 400;
                var ticking = false;

                function updateVisibility() {
                    if (window.pageYOffset > scrollThreshold) {
                        btn.classList.add('is-visible');
                    } else {
                        btn.classList.remove('is-visible');
                    }
                    ticking = false;
                }

                window.addEventListener('scroll', function () {
                    if (!ticking) {
                        window.requestAnimationFrame(updateVisibility);
                        ticking = true;
                    }
                }, { passive: true });

                // Scroll to top on click.
                btn.addEventListener('click', function () {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });

                // Initial check.
                updateVisibility();
            });
        }
    };

})(Drupal, once);

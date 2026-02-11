/**
 * @file
 * Mobile menu functionality for landing page header.
 *
 * Usa Drupal.behaviors para re-attach en AJAX y soportar todos los headers.
 */
(function (Drupal) {
    'use strict';

    Drupal.behaviors.jarabaMobileMenu = {
        attach: function (context, settings) {
            // Solo ejecutar una vez por toggle
            const toggles = context.querySelectorAll ?
                context.querySelectorAll('.landing-header__toggle:not([data-jaraba-menu-attached])') :
                [];

            // Si no hay toggles en este contexto, buscar en document
            let effectiveToggles = toggles.length ? toggles :
                document.querySelectorAll('.landing-header__toggle:not([data-jaraba-menu-attached])');

            effectiveToggles.forEach(function (toggle) {
                const overlay = document.querySelector('.mobile-menu-overlay');

                if (!overlay) {
                    return;
                }

                // Marcar como procesado
                toggle.setAttribute('data-jaraba-menu-attached', 'true');

                // Toggle mobile menu - usar capture para prioridad alta
                toggle.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation(); // Prevenir TODOS los otros handlers

                    const isOpen = toggle.classList.contains('is-active');
                    if (isOpen) {
                        closeMobileMenu(toggle, overlay);
                    } else {
                        openMobileMenu(toggle, overlay);
                    }
                }, true); // capture: true para máxima prioridad

                // Close on overlay click (outside menu content)
                overlay.addEventListener('click', function (e) {
                    if (e.target === overlay) {
                        closeMobileMenu(toggle, overlay);
                    }
                });

                // Close on escape key
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape' && toggle.classList.contains('is-active')) {
                        closeMobileMenu(toggle, overlay);
                    }
                });

                // Close menu on nav link click
                overlay.querySelectorAll('a').forEach(function (link) {
                    link.addEventListener('click', function () {
                        closeMobileMenu(toggle, overlay);
                    });
                });
            });

            function openMobileMenu(toggle, overlay) {
                toggle.classList.add('is-active');
                toggle.setAttribute('aria-expanded', 'true');
                overlay.classList.add('is-open');
                overlay.setAttribute('aria-hidden', 'false');
                document.body.classList.add('mobile-menu-open');
                document.body.style.overflow = 'hidden';
            }

            function closeMobileMenu(toggle, overlay) {
                toggle.classList.remove('is-active');
                toggle.setAttribute('aria-expanded', 'false');
                overlay.classList.remove('is-open');
                overlay.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('mobile-menu-open');
                document.body.style.overflow = '';
            }

            // Handle window resize - close menu on desktop
            let resizeTimer;
            window.addEventListener('resize', function () {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function () {
                    const activeToggle = document.querySelector('.landing-header__toggle.is-active');
                    const overlay = document.querySelector('.mobile-menu-overlay');
                    if (window.innerWidth >= 992 && activeToggle && overlay) {
                        closeMobileMenu(activeToggle, overlay);
                    }
                }, 100);
            });
        }
    };

})(Drupal);

/**
 * @file
 * Behavior: Navegación premium con submenús desplegables.
 *
 * Gestiona la interactividad de navegaciones creadas con el Page Builder:
 * - Submenús desplegables con hover (desktop) y click (touch/mobile).
 * - Hamburger toggle para mobile responsive.
 * - Cierre automático al hacer click fuera del submenú.
 * - Soporte de teclado: Escape cierra submenús, flechas navegan.
 *
 * Selector: .jaraba-navigation
 *
 * @see grapesjs-jaraba-blocks.js -> jaraba-navigation addType
 */

(function (Drupal, once) {
    'use strict';

    Drupal.behaviors.jarabaNavigation = {
        attach: function (context) {
            var navContainers = once('jaraba-navigation', '.jaraba-navigation', context);

            navContainers.forEach(function (container) {
                var items = container.querySelectorAll('.jaraba-navigation__item');

                // -------------------------------------------------------
                // Desktop: hover para abrir/cerrar submenús.
                // -------------------------------------------------------
                items.forEach(function (item) {
                    var submenu = item.querySelector('.jaraba-navigation__submenu');
                    if (!submenu) return;

                    // Guardar referencia para cleanup.
                    item._jarabaNavEnter = function () {
                        submenu.style.opacity = '1';
                        submenu.style.visibility = 'visible';
                        submenu.style.transform = 'translateY(0)';
                    };

                    item._jarabaNavLeave = function () {
                        submenu.style.opacity = '0';
                        submenu.style.visibility = 'hidden';
                        submenu.style.transform = 'translateY(-10px)';
                    };

                    item.addEventListener('mouseenter', item._jarabaNavEnter);
                    item.addEventListener('mouseleave', item._jarabaNavLeave);

                    // Touch: click para toggle en dispositivos táctiles.
                    var link = item.querySelector('.jaraba-navigation__link');
                    if (link) {
                        item._jarabaNavTouch = function (e) {
                            // Solo interceptar en touch si hay submenú.
                            if (window.matchMedia('(hover: none)').matches) {
                                e.preventDefault();
                                var isVisible = submenu.style.visibility === 'visible';
                                // Cerrar todos los submenús primero.
                                items.forEach(function (otherItem) {
                                    var otherSub = otherItem.querySelector('.jaraba-navigation__submenu');
                                    if (otherSub) {
                                        otherSub.style.opacity = '0';
                                        otherSub.style.visibility = 'hidden';
                                        otherSub.style.transform = 'translateY(-10px)';
                                    }
                                });
                                // Toggle el actual.
                                if (!isVisible) {
                                    submenu.style.opacity = '1';
                                    submenu.style.visibility = 'visible';
                                    submenu.style.transform = 'translateY(0)';
                                }
                            }
                        };
                        link.addEventListener('click', item._jarabaNavTouch);
                    }
                });

                // -------------------------------------------------------
                // Keyboard: Escape cierra submenús abiertos.
                // -------------------------------------------------------
                container._jarabaNavKeydown = function (e) {
                    if (e.key === 'Escape') {
                        items.forEach(function (item) {
                            var submenu = item.querySelector('.jaraba-navigation__submenu');
                            if (submenu) {
                                submenu.style.opacity = '0';
                                submenu.style.visibility = 'hidden';
                                submenu.style.transform = 'translateY(-10px)';
                            }
                        });
                    }
                };
                container.addEventListener('keydown', container._jarabaNavKeydown);

                // -------------------------------------------------------
                // Click outside: cerrar submenús al hacer click fuera.
                // -------------------------------------------------------
                container._jarabaNavOutside = function (e) {
                    if (!container.contains(e.target)) {
                        items.forEach(function (item) {
                            var submenu = item.querySelector('.jaraba-navigation__submenu');
                            if (submenu) {
                                submenu.style.opacity = '0';
                                submenu.style.visibility = 'hidden';
                                submenu.style.transform = 'translateY(-10px)';
                            }
                        });
                    }
                };
                document.addEventListener('click', container._jarabaNavOutside);
            });
        },

        detach: function (context, settings, trigger) {
            if (trigger !== 'unload') return;

            var navContainers = context.querySelectorAll
                ? context.querySelectorAll('.jaraba-navigation')
                : [];

            navContainers.forEach(function (container) {
                // Limpiar event listener de keydown.
                if (container._jarabaNavKeydown) {
                    container.removeEventListener('keydown', container._jarabaNavKeydown);
                    delete container._jarabaNavKeydown;
                }

                // Limpiar event listener de click outside.
                if (container._jarabaNavOutside) {
                    document.removeEventListener('click', container._jarabaNavOutside);
                    delete container._jarabaNavOutside;
                }

                // Limpiar event listeners de items.
                var items = container.querySelectorAll('.jaraba-navigation__item');
                items.forEach(function (item) {
                    if (item._jarabaNavEnter) {
                        item.removeEventListener('mouseenter', item._jarabaNavEnter);
                        delete item._jarabaNavEnter;
                    }
                    if (item._jarabaNavLeave) {
                        item.removeEventListener('mouseleave', item._jarabaNavLeave);
                        delete item._jarabaNavLeave;
                    }
                    var link = item.querySelector('.jaraba-navigation__link');
                    if (link && item._jarabaNavTouch) {
                        link.removeEventListener('click', item._jarabaNavTouch);
                        delete item._jarabaNavTouch;
                    }
                });
            });
        }
    };

})(Drupal, once);

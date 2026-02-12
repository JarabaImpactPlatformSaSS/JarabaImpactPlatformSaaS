/**
 * @file
 * Behavior: Pestañas de contenido con accesibilidad ARIA.
 *
 * Gestiona la navegación por pestañas con soporte de teclado
 * (flechas izquierda/derecha) y atributos ARIA correctos.
 *
 * Selector: .jaraba-tabs
 *
 * @see grapesjs-jaraba-blocks.js → tabsScript
 */

(function (Drupal, once) {
    'use strict';

    Drupal.behaviors.jarabaTabs = {
        attach: function (context) {
            var tabContainers = once('jaraba-tabs', '.jaraba-tabs', context);

            tabContainers.forEach(function (container) {
                var tabs = container.querySelectorAll('[role="tab"]');
                var panels = container.querySelectorAll('[role="tabpanel"]');
                if (!tabs.length) return;

                /**
                 * Activa una pestaña y muestra su panel.
                 * @param {HTMLElement} tab - Elemento tab a activar.
                 */
                function activateTab(tab) {
                    var targetId = tab.getAttribute('aria-controls');

                    // Desactivar todas las pestañas
                    tabs.forEach(function (t) {
                        t.classList.remove('jaraba-tabs__tab--active');
                        t.setAttribute('aria-selected', 'false');
                        t.setAttribute('tabindex', '-1');
                        t.style.borderBottom = '3px solid transparent';
                        t.style.color = 'var(--ej-text-muted, #64748b)';
                    });

                    // Ocultar todos los paneles
                    panels.forEach(function (p) {
                        p.style.display = 'none';
                        p.setAttribute('aria-hidden', 'true');
                    });

                    // Activar pestaña
                    tab.classList.add('jaraba-tabs__tab--active');
                    tab.setAttribute('aria-selected', 'true');
                    tab.setAttribute('tabindex', '0');
                    tab.style.borderBottom = '3px solid var(--ej-color-corporate, #233D63)';
                    tab.style.color = 'var(--ej-text-primary, #1e293b)';
                    tab.focus();

                    // Mostrar panel correspondiente
                    var targetPanel = container.querySelector('#' + targetId);
                    if (targetPanel) {
                        targetPanel.style.display = 'block';
                        targetPanel.setAttribute('aria-hidden', 'false');
                    }
                }

                // Click handler
                tabs.forEach(function (tab) {
                    tab.addEventListener('click', function () {
                        activateTab(tab);
                    });
                });

                // Navegación por teclado (flechas izquierda/derecha)
                var tabList = container.querySelector('[role="tablist"]');
                if (tabList) {
                    tabList.addEventListener('keydown', function (e) {
                        var tabsArray = Array.from(tabs);
                        var currentIndex = tabsArray.indexOf(document.activeElement);
                        if (currentIndex === -1) return;

                        var newIndex = -1;
                        if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
                            e.preventDefault();
                            newIndex = (currentIndex + 1) % tabsArray.length;
                        } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
                            e.preventDefault();
                            newIndex = (currentIndex - 1 + tabsArray.length) % tabsArray.length;
                        } else if (e.key === 'Home') {
                            e.preventDefault();
                            newIndex = 0;
                        } else if (e.key === 'End') {
                            e.preventDefault();
                            newIndex = tabsArray.length - 1;
                        }

                        if (newIndex !== -1) {
                            activateTab(tabsArray[newIndex]);
                        }
                    });
                }
            });
        }
    };

})(Drupal, once);

/**
 * @file
 * Jaraba Tabs Content - Interactividad de pestañas con ARIA.
 *
 * Gestiona la navegación por pestañas mostrando/ocultando paneles
 * de contenido. Incluye soporte completo ARIA (role="tablist",
 * role="tab", role="tabpanel", aria-selected, aria-hidden).
 *
 * @see docs/arquitectura/2026-02-08_plan_elevacion_page_builder_clase_mundial.md
 */

(function (Drupal) {
    'use strict';

    /**
     * Inicializa las pestañas en el contexto dado.
     *
     * @param {Element} context - Contexto DOM.
     */
    function initTabs(context) {
        var containers = context.querySelectorAll('.jaraba-tabs');

        containers.forEach(function (container) {
            if (container.dataset.jarabaTabsInit) {
                return;
            }
            container.dataset.jarabaTabsInit = 'true';

            // Event delegation
            container.addEventListener('click', function (event) {
                var tab = event.target.closest('[role="tab"]');
                if (!tab) return;

                var targetId = tab.getAttribute('aria-controls');
                var allTabs = container.querySelectorAll('[role="tab"]');
                var allPanels = container.querySelectorAll('[role="tabpanel"]');

                // Desactivar todas las pestañas
                allTabs.forEach(function (t) {
                    t.classList.remove('jaraba-tabs__tab--active');
                    t.setAttribute('aria-selected', 'false');
                    t.setAttribute('tabindex', '-1');
                    t.style.borderBottom = '3px solid transparent';
                    t.style.color = 'var(--ej-text-muted, #64748b)';
                });

                // Ocultar todos los paneles
                allPanels.forEach(function (p) {
                    p.style.display = 'none';
                    p.setAttribute('aria-hidden', 'true');
                });

                // Activar pestaña seleccionada
                tab.classList.add('jaraba-tabs__tab--active');
                tab.setAttribute('aria-selected', 'true');
                tab.setAttribute('tabindex', '0');
                tab.style.borderBottom = '3px solid var(--ej-color-corporate, #233D63)';
                tab.style.color = 'var(--ej-text-primary, #1e293b)';

                // Mostrar panel correspondiente
                var targetPanel = container.querySelector('#' + targetId);
                if (targetPanel) {
                    targetPanel.style.display = 'block';
                    targetPanel.setAttribute('aria-hidden', 'false');
                }
            });

            // Soporte de teclado: flechas izquierda/derecha
            container.addEventListener('keydown', function (event) {
                var tab = event.target.closest('[role="tab"]');
                if (!tab) return;

                var allTabs = Array.from(container.querySelectorAll('[role="tab"]'));
                var currentIndex = allTabs.indexOf(tab);

                if (event.key === 'ArrowRight') {
                    event.preventDefault();
                    var nextIndex = (currentIndex + 1) % allTabs.length;
                    allTabs[nextIndex].click();
                    allTabs[nextIndex].focus();
                } else if (event.key === 'ArrowLeft') {
                    event.preventDefault();
                    var prevIndex = (currentIndex - 1 + allTabs.length) % allTabs.length;
                    allTabs[prevIndex].click();
                    allTabs[prevIndex].focus();
                }
            });
        });
    }

    // Drupal behavior
    Drupal.behaviors.jarabaTabsContent = {
        attach: function (context) {
            initTabs(context);
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initTabs(document);
        });
    } else {
        initTabs(document);
    }

    window.jarabaInitTabs = initTabs;

})(Drupal);

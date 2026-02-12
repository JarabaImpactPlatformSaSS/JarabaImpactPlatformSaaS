/**
 * @file
 * Jaraba Pricing Toggle - Interactividad de switch Mensual/Anual.
 *
 * Alterna la visualización de precios entre periodos de facturación.
 * Emite un CustomEvent 'jaraba:pricing-change' para que otros componentes
 * (como tablas de precios) puedan reaccionar al cambio.
 *
 * @see docs/arquitectura/2026-02-08_plan_elevacion_page_builder_clase_mundial.md
 */

(function (Drupal) {
    'use strict';

    /**
     * Inicializa los toggles de precios en el contexto dado.
     *
     * @param {Element} context - Contexto DOM.
     */
    function initPricingToggles(context) {
        var containers = context.querySelectorAll('.jaraba-pricing--toggle');

        containers.forEach(function (container) {
            if (container.dataset.jarabaPricingInit) {
                return;
            }
            container.dataset.jarabaPricingInit = 'true';

            // Event delegation para los botones de toggle
            container.addEventListener('click', function (event) {
                var option = event.target.closest('.jaraba-pricing-toggle__option');
                if (!option) return;

                var allOptions = container.querySelectorAll('.jaraba-pricing-toggle__option');

                // Desactivar todos
                allOptions.forEach(function (opt) {
                    opt.classList.remove('jaraba-pricing-toggle__option--active');
                    opt.setAttribute('aria-selected', 'false');
                    opt.style.color = 'var(--ej-text-muted, #64748b)';
                    opt.style.background = 'transparent';
                });

                // Activar el seleccionado
                option.classList.add('jaraba-pricing-toggle__option--active');
                option.setAttribute('aria-selected', 'true');
                option.style.color = 'white';
                option.style.background = 'var(--ej-color-corporate, #233D63)';

                // Emitir evento custom
                var period = option.getAttribute('data-period');
                container.dispatchEvent(new CustomEvent('jaraba:pricing-change', {
                    bubbles: true,
                    detail: { period: period },
                }));
            });
        });
    }

    // Drupal behavior para páginas públicas
    Drupal.behaviors.jarabaPricingToggle = {
        attach: function (context) {
            initPricingToggles(context);
        }
    };

    // Inicialización para contextos no-Drupal
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initPricingToggles(document);
        });
    } else {
        initPricingToggles(document);
    }

    window.jarabaInitPricingToggles = initPricingToggles;

})(Drupal);

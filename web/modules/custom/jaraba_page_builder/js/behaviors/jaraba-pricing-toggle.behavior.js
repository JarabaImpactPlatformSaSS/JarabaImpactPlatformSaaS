/**
 * @file
 * Behavior: Toggle de precios Mensual/Anual.
 *
 * Alterna la visualización de precios entre períodos.
 * Emite un CustomEvent 'jaraba:pricing-change' para que otros
 * componentes (pricing-table) reaccionen.
 *
 * Selector: .jaraba-pricing--toggle
 *
 * @see grapesjs-jaraba-blocks.js → pricingToggleScript
 */

(function (Drupal, once) {
    'use strict';

    Drupal.behaviors.jarabaPricingToggle = {
        attach: function (context) {
            var toggles = once('jaraba-pricing-toggle', '.jaraba-pricing--toggle', context);

            toggles.forEach(function (container) {
                var options = container.querySelectorAll('.jaraba-pricing-toggle__option');
                if (!options.length) return;

                options.forEach(function (option) {
                    option.addEventListener('click', function () {
                        // Desactivar todas las opciones
                        options.forEach(function (opt) {
                            opt.classList.remove('jaraba-pricing-toggle__option--active');
                            opt.style.color = 'var(--ej-text-muted, #64748b)';
                            opt.style.background = 'transparent';
                        });

                        // Activar la opción clickeada
                        option.classList.add('jaraba-pricing-toggle__option--active');
                        option.style.color = 'white';
                        option.style.background = 'var(--ej-color-corporate, #233D63)';

                        // Emitir evento custom para pricing-table
                        var period = option.getAttribute('data-period');
                        container.dispatchEvent(new CustomEvent('jaraba:pricing-change', {
                            bubbles: true,
                            detail: { period: period }
                        }));

                        // Anunciar cambio para accesibilidad
                        if (Drupal.announce) {
                            Drupal.announce(
                                Drupal.t('Precios actualizados a @period', { '@period': period })
                            );
                        }
                    });
                });
            });
        }
    };

})(Drupal, once);

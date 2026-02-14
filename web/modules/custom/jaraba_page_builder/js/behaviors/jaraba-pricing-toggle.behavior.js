/**
 * @file
 * Behavior: Toggle de precios Mensual/Anual.
 *
 * Alterna la visualización de precios entre períodos.
 * Emite un CustomEvent 'jaraba:pricing-change' para que otros
 * componentes (pricing-table) reaccionen.
 *
 * Accesibilidad: keyboard navigation (Enter/Space), role="radiogroup",
 * aria-checked, focus-visible.
 *
 * Selector: .jaraba-pricing--toggle
 *
 * @see grapesjs-jaraba-blocks.js → pricingToggleScript
 */

(function (Drupal, once) {
    'use strict';

    /**
     * Activate a pricing option and deactivate siblings.
     *
     * @param {NodeList} options - All toggle option elements.
     * @param {Element} activeOption - The option to activate.
     * @param {Element} container - The parent container for events.
     */
    function activateOption(options, activeOption, container) {
        // Desactivar todas las opciones
        options.forEach(function (opt) {
            opt.classList.remove('jaraba-pricing-toggle__option--active');
            opt.style.color = 'var(--ej-text-muted, #64748b)';
            opt.style.background = 'transparent';
            opt.setAttribute('aria-checked', 'false');
            opt.setAttribute('tabindex', '-1');
        });

        // Activar la opción seleccionada
        activeOption.classList.add('jaraba-pricing-toggle__option--active');
        activeOption.style.color = 'white';
        activeOption.style.background = 'var(--ej-color-corporate, #233D63)';
        activeOption.setAttribute('aria-checked', 'true');
        activeOption.setAttribute('tabindex', '0');
        activeOption.focus();

        // Emitir evento custom para pricing-table
        var period = activeOption.getAttribute('data-period');
        container.dispatchEvent(new CustomEvent('jaraba:pricing-change', {
            bubbles: true,
            detail: { period: period }
        }));

        // Anunciar cambio para lectores de pantalla
        if (Drupal.announce) {
            Drupal.announce(
                Drupal.t('Precios actualizados a @period', { '@period': period })
            );
        }
    }

    Drupal.behaviors.jarabaPricingToggle = {
        attach: function (context) {
            var toggles = once('jaraba-pricing-toggle', '.jaraba-pricing--toggle', context);

            toggles.forEach(function (container) {
                var options = container.querySelectorAll('.jaraba-pricing-toggle__option');
                if (!options.length) return;

                // ARIA: marcar como radiogroup
                var toggleGroup = container.querySelector('.jaraba-pricing-toggle__group') || container;
                toggleGroup.setAttribute('role', 'radiogroup');
                toggleGroup.setAttribute('aria-label', Drupal.t('Seleccionar período de facturación'));

                // Inicializar ARIA en cada opción
                options.forEach(function (option, index) {
                    option.setAttribute('role', 'radio');
                    var isActive = option.classList.contains('jaraba-pricing-toggle__option--active');
                    option.setAttribute('aria-checked', String(isActive));
                    option.setAttribute('tabindex', isActive ? '0' : '-1');
                });

                // Evento click
                options.forEach(function (option) {
                    option.addEventListener('click', function () {
                        activateOption(options, option, container);
                    });
                });

                // Evento keyboard: Enter, Space, Arrow keys
                container.addEventListener('keydown', function (event) {
                    var target = event.target.closest('.jaraba-pricing-toggle__option');
                    if (!target) return;

                    var optionsArray = Array.from(options);
                    var currentIndex = optionsArray.indexOf(target);

                    switch (event.key) {
                        case 'Enter':
                        case ' ':
                            event.preventDefault();
                            activateOption(options, target, container);
                            break;

                        case 'ArrowRight':
                        case 'ArrowDown':
                            event.preventDefault();
                            var nextIndex = (currentIndex + 1) % optionsArray.length;
                            activateOption(options, optionsArray[nextIndex], container);
                            break;

                        case 'ArrowLeft':
                        case 'ArrowUp':
                            event.preventDefault();
                            var prevIndex = (currentIndex - 1 + optionsArray.length) % optionsArray.length;
                            activateOption(options, optionsArray[prevIndex], container);
                            break;
                    }
                });
            });
        }
    };

})(Drupal, once);

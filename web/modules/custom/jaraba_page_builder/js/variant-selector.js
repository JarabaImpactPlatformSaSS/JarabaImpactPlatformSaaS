/**
 * @file
 * Selector visual de variantes de Header/Footer.
 *
 * Reemplaza los <select> planos con cards clickeables con thumbnails SVG.
 * Al seleccionar una variante:
 * 1. Actualiza el estado visual (is-active) de las cards
 * 2. Guarda la variante en SiteConfig via API
 * 3. Dispara evento global para que GrapesJS actualice el canvas
 *
 * Depende de:
 * - drupalSettings.canvasEditor.csrfToken
 * - drupalSettings.canvasEditor.headerConfig / footerConfig
 */
(function (Drupal, drupalSettings, once) {
    'use strict';

    Drupal.behaviors.variantSelector = {
        attach: function (context) {
            var containers = once('variant-selector', '#variant-selector', context);
            if (!containers.length) return;

            var container = containers[0];
            var config = drupalSettings.canvasEditor || {};

            // Inicializar click handlers en todas las cards.
            container.querySelectorAll('.variant-selector__card').forEach(function (card) {
                card.addEventListener('click', function () {
                    var variant = card.dataset.variant;
                    var type = card.dataset.variantType;

                    if (!variant || !type) return;

                    // No hacer nada si ya está activa.
                    if (card.classList.contains('is-active')) return;

                    // Feedback visual inmediato.
                    var grid = card.closest('.variant-selector__grid');
                    grid.querySelectorAll('.variant-selector__card').forEach(function (c) {
                        c.classList.remove('is-active');
                    });
                    card.classList.add('is-active');

                    // Guardar en backend.
                    saveVariant(type, variant, card, config);
                });
            });
        }
    };

    /**
     * Guarda la variante seleccionada en SiteConfig via API.
     *
     * @param {string} type - 'header' o 'footer'.
     * @param {string} variant - Nombre de la variante.
     * @param {HTMLElement} card - Card clickeada (para feedback visual).
     * @param {Object} config - Configuración del editor.
     */
    function saveVariant(type, variant, card, config) {
        card.classList.add('is-selecting');

        fetch('/api/v1/site-config/' + type + '-variant', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': config.csrfToken || ''
            },
            body: JSON.stringify({ variant: variant })
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Error al guardar ' + type);
                }

                card.classList.remove('is-selecting');

                // Mostrar confirmación via save-status del editor.
                showStatus(
                    type === 'header'
                        ? Drupal.t('✓ Encabezado actualizado')
                        : Drupal.t('✓ Pie de página actualizado')
                );

                // Disparar evento global para que GrapesJS actualice el canvas.
                document.dispatchEvent(new CustomEvent('jaraba:variant-changed', {
                    detail: { type: type, variant: variant }
                }));
            })
            .catch(function (error) {
                console.error('Variant Selector: Error guardando', error);
                card.classList.remove('is-selecting');
                showStatus(Drupal.t('Error al guardar'), true);
            });
    }

    /**
     * Muestra un estado temporal en la barra de estado del editor.
     *
     * @param {string} message - Mensaje a mostrar.
     * @param {boolean} isError - Si es un error.
     */
    function showStatus(message, isError) {
        var statusEl = document.getElementById('save-status');
        if (!statusEl) return;

        var textEl = statusEl.querySelector('.canvas-editor__status-text');
        if (textEl) textEl.textContent = message;

        statusEl.hidden = false;
        statusEl.classList.toggle('is-error', !!isError);

        setTimeout(function () {
            statusEl.hidden = true;
            statusEl.classList.remove('is-error');
        }, isError ? 3000 : 2000);
    }

})(Drupal, drupalSettings, once);

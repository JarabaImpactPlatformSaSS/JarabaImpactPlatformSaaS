/**
 * @file
 * Visual Customizer JS for Tenant Theme Form.
 * 
 * Handles vertical tabs behavior and live preview.
 */

(function (Drupal, once) {
    'use strict';

    Drupal.behaviors.visualCustomizer = {
        attach: function (context, settings) {
            // Initialize vertical tabs if not already done by core
            once('visual-customizer-init', '.jaraba-theme-customizer', context).forEach(function (form) {
                // Enhance color pickers with preview
                const colorInputs = form.querySelectorAll('input[type="color"]');
                colorInputs.forEach(function (input) {
                    input.addEventListener('change', function (e) {
                        const varName = '--ej-color-' + e.target.name.replace('color_', '');
                        document.documentElement.style.setProperty(varName, e.target.value);
                    });
                });

                // Handle preview button
                const previewBtn = form.querySelector('[data-action="preview"]');
                if (previewBtn) {
                    previewBtn.addEventListener('click', function (e) {
                        e.preventDefault();
                        Drupal.announce(Drupal.t('Preview applied. Submit to save changes.'));
                    });
                }

                console.log('Visual Customizer initialized');
            });
        }
    };

})(Drupal, once);

/**
 * @file
 * Logica interactiva para el wizard de instalacion de conectores.
 *
 * Gestiona las transiciones de paso, validacion cliente
 * y feedback visual durante el proceso de instalacion.
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.jarabaIntegrationsInstallWizard = {
    attach: function (context) {
      once('install-wizard', '.connector-install-wizard', context).forEach(function (wizard) {

        // Animar transiciones de paso.
        var steps = wizard.querySelectorAll('.connector-install-wizard__step');
        steps.forEach(function (step) {
          if (step.classList.contains('connector-install-wizard__step--active')) {
            step.style.opacity = '0';
            requestAnimationFrame(function () {
              step.style.transition = 'opacity var(--ej-transition, 150ms ease)';
              step.style.opacity = '1';
            });
          }
        });

        // Validacion visual de campos requeridos.
        var requiredFields = wizard.querySelectorAll('[required]');
        requiredFields.forEach(function (field) {
          field.addEventListener('blur', function () {
            if (!field.value.trim()) {
              field.classList.add('connector-install-wizard__field--error');
            }
            else {
              field.classList.remove('connector-install-wizard__field--error');
            }
          });
        });
      });
    }
  };

})(Drupal, once);

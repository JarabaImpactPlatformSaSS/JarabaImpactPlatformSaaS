/**
 * @file
 * JavaScript del editor visual de flujos de agentes IA.
 *
 * Comportamientos:
 * - Drag and drop de pasos desde la paleta.
 * - Validacion en tiempo real de la configuracion JSON.
 * - Conexion visual entre pasos.
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Behavior del Flow Builder.
   */
  Drupal.behaviors.agentFlowBuilder = {
    attach: function (context) {
      // Validacion de JSON en textarea de flow_config.
      once('agent-flow-json-validate', 'textarea[name*="flow_config"]', context).forEach(function (textarea) {
        var feedbackEl = document.createElement('div');
        feedbackEl.className = 'agent-flow-json-feedback';
        feedbackEl.style.fontSize = '0.85em';
        feedbackEl.style.marginTop = '4px';
        textarea.parentNode.appendChild(feedbackEl);

        textarea.addEventListener('input', function () {
          var value = this.value.trim();
          if (!value) {
            feedbackEl.textContent = '';
            feedbackEl.style.color = '';
            return;
          }

          try {
            JSON.parse(value);
            feedbackEl.textContent = Drupal.t('JSON valido');
            feedbackEl.style.color = '#43a047';
          }
          catch (e) {
            feedbackEl.textContent = Drupal.t('JSON invalido: @error', { '@error': e.message });
            feedbackEl.style.color = '#e53935';
          }
        });
      });

      // Validacion de JSON en textarea de trigger_config.
      once('agent-flow-trigger-validate', 'textarea[name*="trigger_config"]', context).forEach(function (textarea) {
        var feedbackEl = document.createElement('div');
        feedbackEl.className = 'agent-flow-json-feedback';
        feedbackEl.style.fontSize = '0.85em';
        feedbackEl.style.marginTop = '4px';
        textarea.parentNode.appendChild(feedbackEl);

        textarea.addEventListener('input', function () {
          var value = this.value.trim();
          if (!value) {
            feedbackEl.textContent = '';
            feedbackEl.style.color = '';
            return;
          }

          try {
            JSON.parse(value);
            feedbackEl.textContent = Drupal.t('JSON valido');
            feedbackEl.style.color = '#43a047';
          }
          catch (e) {
            feedbackEl.textContent = Drupal.t('JSON invalido: @error', { '@error': e.message });
            feedbackEl.style.color = '#e53935';
          }
        });
      });
    }
  };

})(Drupal, drupalSettings, once);

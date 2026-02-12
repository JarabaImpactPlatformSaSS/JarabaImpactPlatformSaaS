/**
 * @file
 * Logica interactiva para el dashboard de flujos de agentes IA.
 *
 * Gestiona ejecucion bajo demanda, polling de estado,
 * y uso de templates predefinidos.
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.jarabaAgentFlowDashboard = {
    attach: function (context) {

      // Boton de ejecucion en las cards de flujo.
      once('flow-execute', '.agent-flow-card__execute-btn', context).forEach(function (btn) {
        btn.addEventListener('click', function () {
          var flowId = btn.dataset.flowId;
          if (!flowId) {
            return;
          }

          btn.disabled = true;
          btn.textContent = Drupal.t('Ejecutando...');

          fetch('/api/v1/agent-flows/' + flowId + '/execute', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
            },
          })
          .then(function (response) { return response.json(); })
          .then(function (data) {
            if (data.execution_id) {
              btn.textContent = Drupal.t('Ejecutado');
              btn.classList.add('agent-flow-card__execute-btn--success');
            }
            else {
              btn.textContent = Drupal.t('Error');
              btn.classList.add('agent-flow-card__execute-btn--error');
            }
          })
          .catch(function () {
            btn.textContent = Drupal.t('Error');
            btn.classList.add('agent-flow-card__execute-btn--error');
          })
          .finally(function () {
            setTimeout(function () {
              btn.disabled = false;
              btn.textContent = Drupal.t('Ejecutar');
              btn.classList.remove('agent-flow-card__execute-btn--success', 'agent-flow-card__execute-btn--error');
            }, 3000);
          });
        });
      });

      // Boton de usar template.
      once('flow-template', '.agent-flow-template-card__use-btn', context).forEach(function (btn) {
        btn.addEventListener('click', function () {
          var templateId = btn.dataset.templateId;
          if (templateId) {
            window.location.href = '/admin/content/agent-flows/add?template=' + encodeURIComponent(templateId);
          }
        });
      });
    }
  };

})(Drupal, once);

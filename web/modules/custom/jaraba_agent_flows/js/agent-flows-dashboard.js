/**
 * @file
 * JavaScript del dashboard de flujos de agentes IA.
 *
 * Comportamientos:
 * - Auto-refresh de metricas via API.
 * - Ejecucion de flujos desde botones.
 * - Aplicacion de templates.
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Behavior principal del dashboard de Agent Flows.
   */
  Drupal.behaviors.agentFlowDashboard = {
    attach: function (context) {
      var settings = drupalSettings.agentFlowDashboard || {};
      var apiBase = settings.apiBase || '/api/v1/agent-flows';
      var refreshInterval = settings.refreshInterval || 30000;

      // Botones de ejecucion.
      once('agent-flow-execute', '.agent-flow-card__execute-btn', context).forEach(function (btn) {
        btn.addEventListener('click', function (e) {
          e.preventDefault();
          var flowId = this.getAttribute('data-flow-id');
          if (!flowId) return;

          this.disabled = true;
          this.textContent = Drupal.t('Ejecutando...');

          fetch(apiBase + '/' + flowId + '/execute', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
          })
          .then(function (response) { return response.json(); })
          .then(function (data) {
            if (data.error) {
              Drupal.announce(Drupal.t('Error: @error', { '@error': data.error }));
            } else {
              Drupal.announce(Drupal.t('Flujo ejecutado correctamente. ID: @id', { '@id': data.execution_id }));
              // Refresh page after a short delay.
              setTimeout(function () { window.location.reload(); }, 2000);
            }
          })
          .catch(function () {
            Drupal.announce(Drupal.t('Error al ejecutar el flujo.'));
          })
          .finally(function () {
            btn.disabled = false;
            btn.textContent = Drupal.t('Ejecutar');
          });
        });
      });

      // Botones de uso de template.
      once('agent-flow-template', '.agent-flow-template-card__use-btn', context).forEach(function (btn) {
        btn.addEventListener('click', function (e) {
          e.preventDefault();
          var templateId = this.getAttribute('data-template-id');
          if (!templateId) return;

          // Redirect to creation form with template pre-selected.
          window.location.href = '/admin/content/agent-flows/add?template=' + encodeURIComponent(templateId);
        });
      });

      // Auto-refresh (solo una vez por pagina).
      once('agent-flow-refresh', 'body', context).forEach(function () {
        if (refreshInterval > 0) {
          setInterval(function () {
            // Solo refrescar si la pestana esta activa.
            if (!document.hidden) {
              // Podria implementar un refresh parcial via API.
              // Por ahora, solo un indicador visual.
            }
          }, refreshInterval);
        }
      });
    }
  };

})(Drupal, drupalSettings, once);

/**
 * @file
 * Comportamientos del dashboard de Agentes Autonomos.
 *
 * Inicializa el dashboard, animaciones de contadores y polling
 * en tiempo real para aprobaciones pendientes.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Intervalo de polling para aprobaciones pendientes (30 segundos).
   *
   * @type {number}
   */
  var POLLING_INTERVAL = 30000;

  /**
   * Duracion de la animacion de contadores en milisegundos.
   *
   * @type {number}
   */
  var COUNTER_ANIMATION_DURATION = 800;

  /**
   * Anima un elemento numerico desde 0 hasta su valor objetivo.
   *
   * @param {HTMLElement} element
   *   Elemento DOM que contiene el valor numerico.
   */
  function animateCounter(element) {
    var targetValue = parseInt(element.textContent, 10) || 0;
    if (targetValue === 0) {
      return;
    }

    var startTime = null;
    element.textContent = '0';

    function step(timestamp) {
      if (!startTime) {
        startTime = timestamp;
      }
      var progress = Math.min((timestamp - startTime) / COUNTER_ANIMATION_DURATION, 1);
      // Easing: ease-out cubico para animacion suave.
      var eased = 1 - Math.pow(1 - progress, 3);
      element.textContent = Math.floor(eased * targetValue).toString();

      if (progress < 1) {
        requestAnimationFrame(step);
      }
      else {
        element.textContent = targetValue.toString();
      }
    }

    requestAnimationFrame(step);
  }

  /**
   * Realiza polling al endpoint de aprobaciones pendientes.
   *
   * @param {HTMLElement} dashboardEl
   *   Elemento raiz del dashboard.
   */
  function pollPendingApprovals(dashboardEl) {
    var approvalCounter = dashboardEl.querySelector('.agents-stat-card--approvals .agents-stat-card__value');
    if (!approvalCounter) {
      return;
    }

    fetch('/api/v1/agents/approvals/pending', {
      method: 'GET',
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json'
      }
    })
    .then(function (response) {
      if (!response.ok) {
        throw new Error('Error al consultar aprobaciones pendientes');
      }
      return response.json();
    })
    .then(function (data) {
      var count = data.count || data.total || 0;
      var currentCount = parseInt(approvalCounter.textContent, 10) || 0;

      // Solo actualizar si el valor ha cambiado.
      if (count !== currentCount) {
        approvalCounter.textContent = count.toString();
        // Efecto visual de actualizacion.
        approvalCounter.classList.add('agents-stat-card__value--updated');
        setTimeout(function () {
          approvalCounter.classList.remove('agents-stat-card__value--updated');
        }, 1500);
      }
    })
    .catch(function () {
      // Fallo silencioso — el polling reintentara en el siguiente ciclo.
    });
  }

  /**
   * Comportamiento principal del dashboard de Agentes IA.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.jarabaAgents = {
    attach: function (context) {
      once('jaraba-agents-init', '.agents-dashboard', context).forEach(function (dashboardEl) {

        // Animar contadores de estadisticas al cargar.
        var counterElements = dashboardEl.querySelectorAll('.agents-stat-card__value');
        counterElements.forEach(function (el) {
          animateCounter(el);
        });

        // Configurar polling de aprobaciones pendientes cada 30 segundos.
        var pollingTimer = setInterval(function () {
          // No hacer polling si la pestana no esta visible.
          if (document.hidden) {
            return;
          }
          // Verificar que el dashboard sigue en el DOM.
          if (!document.querySelector('.agents-dashboard')) {
            clearInterval(pollingTimer);
            return;
          }
          pollPendingApprovals(dashboardEl);
        }, POLLING_INTERVAL);

      });
    }
  };

})(Drupal, once);

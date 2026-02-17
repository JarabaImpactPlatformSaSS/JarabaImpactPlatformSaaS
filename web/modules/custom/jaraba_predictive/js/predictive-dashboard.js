/**
 * @file
 * Comportamientos del dashboard de Modelos Predictivos.
 *
 * Inicializa tabs, animaciones de contadores, gauges de riesgo
 * y polling en tiempo real para metricas del dashboard.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Intervalo de polling para metricas del dashboard (60 segundos).
   *
   * @type {number}
   */
  var POLLING_INTERVAL = 60000;

  /**
   * Duracion de la animacion de contadores en milisegundos.
   *
   * @type {number}
   */
  var COUNTER_ANIMATION_DURATION = 900;

  /**
   * Circunferencia del circulo del gauge (2 * PI * 52).
   *
   * @type {number}
   */
  var GAUGE_CIRCUMFERENCE = 326.73;

  /**
   * Anima un elemento numerico desde 0 hasta su valor objetivo.
   *
   * @param {HTMLElement} element
   *   Elemento DOM que contiene el valor numerico.
   */
  function animateCounter(element) {
    var targetValue = parseInt(element.getAttribute('data-counter-target') || element.textContent, 10) || 0;
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
   * Inicializa el gauge SVG circular de riesgo.
   *
   * Lee data-value y anima stroke-dashoffset proporcionalmente.
   *
   * @param {HTMLElement} gaugeEl
   *   Elemento .risk-gauge con data-value.
   */
  function initRiskGauge(gaugeEl) {
    var value = parseInt(gaugeEl.getAttribute('data-value'), 10) || 0;
    var circle = gaugeEl.querySelector('.risk-gauge__circle');
    if (!circle) {
      return;
    }

    // Calcular el offset basado en el porcentaje (0-100).
    var offset = GAUGE_CIRCUMFERENCE - (value / 100) * GAUGE_CIRCUMFERENCE;

    // Iniciar con el circulo vacio y animar.
    circle.style.strokeDashoffset = GAUGE_CIRCUMFERENCE.toString();

    // Forzar un reflow para que la transicion CSS funcione.
    void gaugeEl.offsetWidth;

    // Animar al valor objetivo despues de un breve retraso.
    setTimeout(function () {
      circle.style.strokeDashoffset = offset.toString();
    }, 150);
  }

  /**
   * Gestiona el cambio entre tabs del dashboard.
   *
   * @param {HTMLElement} dashboardEl
   *   Elemento raiz del dashboard.
   */
  function initTabs(dashboardEl) {
    var tabs = dashboardEl.querySelectorAll('.predictions-dashboard__tab');
    var panels = dashboardEl.querySelectorAll('.predictions-dashboard__panel');

    tabs.forEach(function (tab) {
      tab.addEventListener('click', function () {
        var targetPanel = tab.getAttribute('data-tab');

        // Desactivar todos los tabs.
        tabs.forEach(function (t) {
          t.classList.remove('predictions-dashboard__tab--active');
          t.setAttribute('aria-selected', 'false');
        });

        // Ocultar todos los paneles.
        panels.forEach(function (p) {
          p.classList.remove('predictions-dashboard__panel--active');
          p.setAttribute('hidden', '');
        });

        // Activar el tab seleccionado.
        tab.classList.add('predictions-dashboard__tab--active');
        tab.setAttribute('aria-selected', 'true');

        // Mostrar el panel correspondiente.
        var panel = dashboardEl.querySelector('[data-panel="' + targetPanel + '"]');
        if (panel) {
          panel.classList.add('predictions-dashboard__panel--active');
          panel.removeAttribute('hidden');

          // Inicializar gauges del panel si no se han inicializado.
          var gauges = panel.querySelectorAll('.risk-gauge:not([data-initialized])');
          gauges.forEach(function (gauge) {
            initRiskGauge(gauge);
            gauge.setAttribute('data-initialized', 'true');
          });
        }
      });
    });
  }

  /**
   * Inicializa barras de chart CSS simples para datos de tendencia.
   *
   * @param {HTMLElement} dashboardEl
   *   Elemento raiz del dashboard.
   */
  function initBarCharts(dashboardEl) {
    var bars = dashboardEl.querySelectorAll('.prediction-card__meter-fill');
    bars.forEach(function (bar) {
      var targetWidth = bar.style.width;
      bar.style.width = '0%';

      // Forzar reflow.
      void bar.offsetWidth;

      // Animar al ancho objetivo.
      setTimeout(function () {
        bar.style.width = targetWidth;
      }, 200);
    });
  }

  /**
   * Realiza polling al endpoint de metricas del dashboard.
   *
   * @param {HTMLElement} dashboardEl
   *   Elemento raiz del dashboard.
   */
  function pollDashboardMetrics(dashboardEl) {
    fetch('/api/v1/predictions/dashboard/metrics', {
      method: 'GET',
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json'
      }
    })
    .then(function (response) {
      if (!response.ok) {
        throw new Error('Error al consultar metricas predictivas');
      }
      return response.json();
    })
    .then(function (data) {
      // Mapeo de stat keys a sus selectores.
      var statMap = {
        churn_count: '[data-stat="churn"] .predictions-dashboard__stat-value',
        lead_count: '[data-stat="leads"] .predictions-dashboard__stat-value',
        forecast_count: '[data-stat="forecasts"] .predictions-dashboard__stat-value',
        anomaly_count: '[data-stat="anomalies"] .predictions-dashboard__stat-value'
      };

      Object.keys(statMap).forEach(function (key) {
        if (typeof data[key] !== 'undefined') {
          var el = dashboardEl.querySelector(statMap[key]);
          if (el) {
            var newValue = parseInt(data[key], 10) || 0;
            var currentValue = parseInt(el.textContent, 10) || 0;

            if (newValue !== currentValue) {
              el.textContent = newValue.toString();
              // Efecto visual de actualizacion.
              el.classList.add('predictions-dashboard__stat-value--updated');
              setTimeout(function () {
                el.classList.remove('predictions-dashboard__stat-value--updated');
              }, 1500);
            }
          }
        }
      });
    })
    .catch(function () {
      // Fallo silencioso — el polling reintentara en el siguiente ciclo.
    });
  }

  /**
   * Inicializa las acciones rapidas del sidebar.
   *
   * @param {HTMLElement} dashboardEl
   *   Elemento raiz del dashboard.
   */
  function initQuickActions(dashboardEl) {
    var actionButtons = dashboardEl.querySelectorAll('[data-action]');

    actionButtons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var action = btn.getAttribute('data-action');
        var endpoint = null;

        switch (action) {
          case 'run-churn':
            endpoint = '/api/v1/predictions/churn/run';
            break;
          case 'score-leads':
            endpoint = '/api/v1/predictions/leads/score';
            break;
          case 'generate-forecast':
            endpoint = '/api/v1/predictions/forecast/generate';
            break;
          case 'trigger-retention':
            var predictionId = btn.getAttribute('data-prediction-id');
            endpoint = '/api/v1/predictions/churn/' + predictionId + '/retention';
            break;
          case 'convert-lead':
            var scoreId = btn.getAttribute('data-score-id');
            endpoint = '/api/v1/predictions/leads/' + scoreId + '/convert';
            break;
        }

        if (!endpoint) {
          return;
        }

        // Deshabilitar boton durante la peticion.
        btn.disabled = true;
        btn.style.opacity = '0.6';

        fetch(endpoint, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
          }
        })
        .then(function (response) {
          if (!response.ok) {
            throw new Error('Error en la accion: ' + action);
          }
          return response.json();
        })
        .then(function (data) {
          // Refrescar metricas tras accion exitosa.
          pollDashboardMetrics(dashboardEl);
          if (data.message) {
            // Podria integrar con el sistema de mensajes de Drupal.
            // Por ahora, log en consola.
            Drupal.announce(data.message);
          }
        })
        .catch(function (err) {
          // Fallo silencioso con log.
          if (typeof console !== 'undefined' && console.warn) {
            console.warn('Jaraba Predictive: accion fallida —', err.message);
          }
        })
        .finally(function () {
          btn.disabled = false;
          btn.style.opacity = '';
        });
      });
    });
  }

  /**
   * Comportamiento principal del dashboard de Modelos Predictivos.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.jarabaPredictiveDashboard = {
    attach: function (context) {
      once('jaraba-predictive-init', '.predictions-dashboard', context).forEach(function (dashboardEl) {

        // 1. Inicializar tabs.
        initTabs(dashboardEl);

        // 2. Animar contadores de estadisticas al cargar.
        var counterElements = dashboardEl.querySelectorAll('.predictions-dashboard__stat-value');
        counterElements.forEach(function (el) {
          animateCounter(el);
        });

        // 3. Inicializar risk gauges del panel activo.
        var visibleGauges = dashboardEl.querySelectorAll('.predictions-dashboard__panel--active .risk-gauge');
        visibleGauges.forEach(function (gauge) {
          initRiskGauge(gauge);
          gauge.setAttribute('data-initialized', 'true');
        });

        // 4. Inicializar barras de chart CSS.
        initBarCharts(dashboardEl);

        // 5. Inicializar acciones rapidas.
        initQuickActions(dashboardEl);

        // 6. Configurar polling de metricas cada 60 segundos.
        var pollingTimer = setInterval(function () {
          // No hacer polling si la pestana no esta visible.
          if (document.hidden) {
            return;
          }
          // Verificar que el dashboard sigue en el DOM.
          if (!document.querySelector('.predictions-dashboard')) {
            clearInterval(pollingTimer);
            return;
          }
          pollDashboardMetrics(dashboardEl);
        }, POLLING_INTERVAL);

      });
    }
  };

})(Drupal, once);

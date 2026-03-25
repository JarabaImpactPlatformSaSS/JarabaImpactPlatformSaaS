(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.aeiCalculadoraPE = {
    attach: function (context) {
      once('aei-calculadora-pe', '[data-calculadora-pe]', context).forEach(function (container) {
        var config = drupalSettings.jarabaAndaluciaEi || {};
        var calcData = config.calculadora || {};
        var packPricing = calcData.packPricing || {};
        var gastosFijos = calcData.gastosFijos || 144;

        // Build pack selector
        var packKeys = Object.keys(packPricing);
        if (packKeys.length === 0) return;

        // Create UI
        var html = '<div class="calculadora-pe">';
        html += '<h3 class="calculadora-pe__title">' + Drupal.t('Calculadora de Punto de Equilibrio') + '</h3>';

        // Pack selector
        html += '<div class="calculadora-pe__selector">';
        html += '<label>' + Drupal.t('Tu pack') + '</label>';
        html += '<select class="calculadora-pe__pack-select">';
        packKeys.forEach(function(key) {
          var label = key.replace(/_/g, ' ');
          label = label.charAt(0).toUpperCase() + label.slice(1);
          html += '<option value="' + Drupal.checkPlain(key) + '">' + Drupal.checkPlain(label) + '</option>';
        });
        html += '</select></div>';

        // Tier selector
        html += '<div class="calculadora-pe__selector">';
        html += '<label>' + Drupal.t('Modalidad') + '</label>';
        html += '<select class="calculadora-pe__tier-select">';
        html += '<option value="basico">' + Drupal.t('Básico') + '</option>';
        html += '<option value="estandar" selected>' + Drupal.t('Estándar') + '</option>';
        html += '<option value="premium">' + Drupal.t('Premium') + '</option>';
        html += '</select></div>';

        // Results area
        html += '<div class="calculadora-pe__results" data-results></div>';
        html += '</div>';

        container.innerHTML = html;

        // Calculate function
        function calculate() {
          var pack = container.querySelector('.calculadora-pe__pack-select').value;
          var tier = container.querySelector('.calculadora-pe__tier-select').value;
          var resultsEl = container.querySelector('[data-results]');

          var pricing = packPricing[pack] || {};
          var precio = pricing[tier] || pricing.basico || 150;

          var pe = Math.ceil(gastosFijos / precio);

          var resultHtml = '<div class="calculadora-pe__summary">';
          resultHtml += '<div class="calculadora-pe__kpi"><span class="calculadora-pe__kpi-value">' + gastosFijos + '€</span>';
          resultHtml += '<span class="calculadora-pe__kpi-label">' + Drupal.t('Gastos fijos/mes') + '</span></div>';
          resultHtml += '<div class="calculadora-pe__kpi"><span class="calculadora-pe__kpi-value">' + precio + '€</span>';
          resultHtml += '<span class="calculadora-pe__kpi-label">' + Drupal.t('Precio/cliente') + '</span></div>';
          resultHtml += '<div class="calculadora-pe__kpi calculadora-pe__kpi--highlight"><span class="calculadora-pe__kpi-value">' + pe + '</span>';
          resultHtml += '<span class="calculadora-pe__kpi-label">' + Drupal.t('Clientes para punto equilibrio') + '</span></div>';
          resultHtml += '</div>';

          // Scenarios
          resultHtml += '<div class="calculadora-pe__escenarios">';
          resultHtml += '<h4>' + Drupal.t('Escenarios de ingresos') + '</h4>';
          [1, 2, 3, 5].forEach(function(n) {
            var ingresos = n * precio;
            var beneficio = ingresos - gastosFijos;
            var clase = beneficio >= 0 ? 'positivo' : 'negativo';
            resultHtml += '<div class="calculadora-pe__escenario calculadora-pe__escenario--' + clase + '">';
            resultHtml += '<span>' + n + ' ' + Drupal.t('clientes') + '</span>';
            resultHtml += '<span>' + ingresos + '€ ' + Drupal.t('ingresos') + '</span>';
            resultHtml += '<span class="calculadora-pe__beneficio">' + (beneficio >= 0 ? '+' : '') + beneficio + '€</span>';
            resultHtml += '</div>';
          });
          resultHtml += '</div>';

          resultsEl.innerHTML = resultHtml;
        }

        // Bind events
        container.querySelector('.calculadora-pe__pack-select').addEventListener('change', calculate);
        container.querySelector('.calculadora-pe__tier-select').addEventListener('change', calculate);

        // Initial calculation
        calculate();
      });
    }
  };
})(Drupal, drupalSettings, once);

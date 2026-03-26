/**
 * @file
 * A/B testing: aplica variante visual al CTA hero de reclutamiento.
 *
 * Depende de variant-tracker.js que asigna la variante via API y pone
 * data-ab-variant en el contenedor [data-ab-experiment].
 *
 * Este behavior observa el atributo data-ab-variant y aplica el texto
 * y color de la variante asignada usando los datos de drupalSettings.
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Color map: nombre logico -> CSS custom property.
   */
  var COLOR_MAP = {
    'naranja-impulso': 'var(--ej-color-naranja-impulso, #FF8C42)',
    'verde-innovacion': 'var(--ej-color-verde-innovacion, #00A9A5)',
    'azul-corporativo': 'var(--ej-color-azul-corporativo, #233D63)'
  };

  Drupal.behaviors.aeiAbHeroCta = {
    attach: function (context) {
      var config = drupalSettings.aeiAbTest;
      if (!config || !config.variants) {
        return;
      }

      once('aei-ab-hero', '.js-ab-hero-cta', context).forEach(function (ctaEl) {
        var container = ctaEl.closest('[data-ab-experiment]');
        if (!container) {
          return;
        }

        // variant-tracker.js asigna data-ab-variant de forma asincrona.
        // Observamos el atributo para reaccionar cuando se establezca.
        var applied = false;

        function applyVariant(variantKey) {
          if (applied || !variantKey) {
            return;
          }
          var variantConfig = config.variants[variantKey];
          if (!variantConfig) {
            return;
          }
          applied = true;

          // Aplicar texto del CTA.
          if (variantConfig.cta_text) {
            ctaEl.textContent = variantConfig.cta_text;
          }

          // Aplicar color del CTA.
          if (variantConfig.cta_color && COLOR_MAP[variantConfig.cta_color]) {
            ctaEl.style.setProperty('background-color', COLOR_MAP[variantConfig.cta_color]);
          }
        }

        // Caso 1: variant-tracker.js ya aplico el atributo (raro pero posible en cache).
        var existing = container.getAttribute('data-ab-variant');
        if (existing) {
          applyVariant(existing);
          return;
        }

        // Caso 2: observar cambios en el atributo (caso normal — async fetch).
        if (typeof MutationObserver !== 'undefined') {
          var observer = new MutationObserver(function (mutations) {
            for (var i = 0; i < mutations.length; i++) {
              if (mutations[i].attributeName === 'data-ab-variant') {
                var key = container.getAttribute('data-ab-variant');
                if (key) {
                  applyVariant(key);
                  observer.disconnect();
                }
                break;
              }
            }
          });
          observer.observe(container, { attributes: true, attributeFilter: ['data-ab-variant'] });

          // Safety timeout: desconectar despues de 10s si no llega respuesta.
          setTimeout(function () {
            observer.disconnect();
          }, 10000);
        }
      });
    }
  };

})(Drupal, drupalSettings, once);

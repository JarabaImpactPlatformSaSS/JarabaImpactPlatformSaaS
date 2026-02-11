/**
 * @file
 * Client-side variant tracker para A/B testing.
 *
 * LÓGICA:
 * Se usa en páginas frontend para:
 * 1. Solicitar asignación de variante al servidor.
 * 2. Aplicar cambios visuales según la variante asignada.
 * 3. Enviar conversiones al interactuar con CTAs marcados.
 *
 * USO:
 * Añadir data-ab-experiment="machine_name" al contenedor.
 * Añadir data-ab-convert="machine_name" a los botones de conversión.
 */
(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.jarabaAbVariantTracker = {
    attach: function (context) {

      // Asignar variantes a experimentos presentes en la página
      once('ab-assign', '[data-ab-experiment]', context).forEach(function (el) {
        var experimentName = el.getAttribute('data-ab-experiment');
        if (!experimentName) { return; }

        fetch(Drupal.url('api/v1/ab-testing/assign'), {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          body: JSON.stringify({ experiment: experimentName })
        })
        .then(function (res) { return res.json(); })
        .then(function (json) {
          if (json.data && json.data.variant_key) {
            el.setAttribute('data-ab-variant', json.data.variant_key);
            el.classList.add('ab-variant--' + json.data.variant_key);
          }
        })
        .catch(function () {
          // Silently fail — show default content.
        });
      });

      // Registrar conversiones en CTAs
      once('ab-convert', '[data-ab-convert]', context).forEach(function (el) {
        el.addEventListener('click', function () {
          var experimentName = el.getAttribute('data-ab-convert');
          var revenue = parseFloat(el.getAttribute('data-ab-revenue') || '0');

          fetch(Drupal.url('api/v1/ab-testing/convert'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ experiment: experimentName, revenue: revenue })
          }).catch(function () {
            // Silently fail.
          });
        });
      });
    }
  };
})(Drupal, once);

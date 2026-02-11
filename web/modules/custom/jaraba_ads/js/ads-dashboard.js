/**
 * @file
 * Ads Dashboard behavior: IntersectionObserver animation for KPI cards.
 *
 * ESTRUCTURA:
 * Drupal behavior que detecta la entrada de KPI cards y platform cards
 * en el viewport mediante IntersectionObserver, activando la clase CSS
 * de animacion para la transicion de entrada (fade-in + translateY).
 *
 * LOGICA:
 * 1. Selecciona todos los elementos con [data-ads-animate].
 * 2. Crea un IntersectionObserver con threshold 0.1.
 * 3. Cuando un elemento entra en el viewport, aplica la clase
 *    'ej-ads-kpi__card--visible' con un delay escalonado.
 * 4. Usa Drupal.behaviors + once() para evitar re-adjuntar en AJAX.
 *
 * RELACIONES:
 * - ads-dashboard.js <- jaraba_ads.libraries.yml (cargado por)
 * - ads-dashboard.js -> _dashboard.scss (clases CSS animadas)
 * - ads-dashboard.js -> ads-dashboard.html.twig (data attributes)
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.jarabaAdsDashboard = {
    attach: function (context) {
      var elements = once('jaraba-ads-animate', '[data-ads-animate]', context);

      if (!elements.length) {
        return;
      }

      // Fallback for browsers without IntersectionObserver.
      if (typeof IntersectionObserver === 'undefined') {
        elements.forEach(function (el) {
          el.classList.add('ej-ads-kpi__card--visible');
        });
        return;
      }

      var observer = new IntersectionObserver(
        function (entries) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting) {
              var el = entry.target;
              var index = elements.indexOf(el);
              var delay = Math.max(0, index) * 80;

              setTimeout(function () {
                el.classList.add('ej-ads-kpi__card--visible');
              }, delay);

              observer.unobserve(el);
            }
          });
        },
        {
          threshold: 0.1,
          rootMargin: '0px 0px -40px 0px'
        }
      );

      elements.forEach(function (el) {
        observer.observe(el);
      });
    }
  };

})(Drupal, once);

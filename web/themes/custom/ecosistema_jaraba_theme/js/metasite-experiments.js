/**
 * @file metasite-experiments.js
 * A/B Testing Frontend para meta-sitios via PageExperiment entity.
 *
 * Funcionalidades:
 * - Asigna variante A/B al visitante (cookie persistente 30 días)
 * - Aplica variante al DOM (cambio de CTA text, color, layout)
 * - Envía evento experiment_view al dataLayer
 * - API: registra impresiones y conversiones vía /api/experiments
 *
 * Compatible con ExperimentService y ExperimentApiController del backend.
 *
 * @see Drupal\jaraba_page_builder\Service\ExperimentService
 * @see Drupal\jaraba_page_builder\Controller\ExperimentApiController
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  window.dataLayer = window.dataLayer || [];

  /**
   * Genera/lee cookie de variante de experimento.
   */
  function getOrSetVariant(experimentId, variants) {
    var cookieName = 'jb_exp_' + experimentId;
    var existing = document.cookie.split('; ').find(function (c) {
      return c.startsWith(cookieName + '=');
    });

    if (existing) {
      return existing.split('=')[1];
    }

    // Asignar variante aleatoria con pesos
    var totalWeight = variants.reduce(function (sum, v) { return sum + (v.weight || 50); }, 0);
    var rand = Math.random() * totalWeight;
    var cumulative = 0;
    var assigned = variants[0].id;

    for (var i = 0; i < variants.length; i++) {
      cumulative += (variants[i].weight || 50);
      if (rand <= cumulative) {
        assigned = variants[i].id;
        break;
      }
    }

    // Cookie 30 days
    var expires = new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toUTCString();
    document.cookie = cookieName + '=' + assigned + ';path=/;expires=' + expires + ';SameSite=Lax';

    return assigned;
  }

  /**
   * Aplica variante al DOM.
   */
  function applyVariant(variant) {
    if (!variant || !variant.changes) return;

    variant.changes.forEach(function (change) {
      var el = document.querySelector(change.selector);
      if (!el) return;

      if (change.type === 'text') el.textContent = change.value;
      else if (change.type === 'html') el.innerHTML = change.value;
      else if (change.type === 'style') Object.assign(el.style, change.value);
      else if (change.type === 'class-add') el.classList.add(change.value);
      else if (change.type === 'class-remove') el.classList.remove(change.value);
      else if (change.type === 'attribute') el.setAttribute(change.attr, change.value);
      else if (change.type === 'href') el.setAttribute('href', change.value);
    });
  }

  /**
   * Registra impresión vía API.
   */
  function recordImpression(experimentId, variantId) {
    fetch('/api/experiments/' + experimentId + '/impression', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ variant_id: variantId })
    }).catch(function () { /* silent fail */ });
  }

  /**
   * Behavior: Experiment Assignment.
   */
  Drupal.behaviors.metasiteExperiments = {
    attach: function (context) {
      if (context !== document) return;

      // Lee experimentos activos desde drupalSettings
      var experiments = (drupalSettings.experiments || []);
      if (!experiments.length) return;

      experiments.forEach(function (exp) {
        if (!exp.id || !exp.variants || !exp.variants.length) return;

        var variantId = getOrSetVariant(exp.id, exp.variants);
        var variant = exp.variants.find(function (v) { return v.id === variantId; });

        if (variant) {
          applyVariant(variant);
          recordImpression(exp.id, variantId);

          // DataLayer event
          window.dataLayer.push({
            event: 'experiment_view',
            experiment_id: exp.id,
            experiment_name: exp.name || '',
            variant_id: variantId,
            variant_name: variant.name || ''
          });

          // Marca body con clase de variante
          document.body.classList.add('experiment-' + exp.id + '-variant-' + variantId);
        }
      });

      // Track conversiones en CTAs de la variante
      once('exp-conversion', '[data-experiment-goal]', context).forEach(function (el) {
        el.addEventListener('click', function () {
          var goalExp = el.getAttribute('data-experiment-id');
          var goalVariant = el.getAttribute('data-experiment-variant');

          if (goalExp) {
            window.dataLayer.push({
              event: 'experiment_conversion',
              experiment_id: goalExp,
              variant_id: goalVariant || 'unknown',
              conversion_element: el.tagName + '#' + (el.id || el.className.split(' ')[0])
            });

            fetch('/api/experiments/' + goalExp + '/conversion', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ variant_id: goalVariant })
            }).catch(function () {});
          }
        });
      });
    }
  };

})(Drupal, drupalSettings, once);

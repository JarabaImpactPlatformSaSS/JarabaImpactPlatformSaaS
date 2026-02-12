/**
 * @file
 * Validador de accesibilidad ARIA en tiempo real para el Canvas Editor.
 *
 * Analiza el contenido del editor y muestra violations/warnings
 * al usuario mientras edita, permitiendo corregir antes de publicar.
 *
 * DIRECTRICES:
 * - Todos los strings via Drupal.t()
 * - Usa once() para evitar doble attach
 * - Respeta prefers-reduced-motion
 * - WCAG 2.1 AA
 *
 * @see AccessibilityValidatorService.php (validacion server-side)
 * @see docs/implementacion/2026-02-12_Plan_Cierre_Gaps_Specs_20260126_v1.md P0-02
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Reglas ARIA evaluadas client-side.
   *
   * Complementa la validacion server-side con checks que
   * solo son posibles en el navegador (computed styles, etc.).
   */
  var RULES = {
    imgAlt: {
      selector: 'img:not([alt])',
      level: 'A',
      impact: 'critical',
      message: Drupal.t('Las imagenes deben tener atributo alt'),
    },
    buttonName: {
      selector: 'button:empty:not([aria-label]):not([aria-labelledby]):not([title])',
      level: 'A',
      impact: 'critical',
      message: Drupal.t('Los botones deben tener nombre accesible'),
    },
    linkName: {
      selector: 'a[href]:empty:not([aria-label]):not([aria-labelledby]):not([title])',
      level: 'A',
      impact: 'critical',
      message: Drupal.t('Los enlaces deben tener nombre accesible'),
    },
    formLabel: {
      selector: 'input:not([type="hidden"]):not([aria-label]):not([aria-labelledby]), select:not([aria-label]):not([aria-labelledby]), textarea:not([aria-label]):not([aria-labelledby])',
      level: 'A',
      impact: 'critical',
      message: Drupal.t('Los campos de formulario deben tener etiqueta'),
    },
    tabindex: {
      selector: '[tabindex]:not([tabindex="0"]):not([tabindex="-1"])',
      level: 'AA',
      impact: 'serious',
      message: Drupal.t('Evitar tabindex positivos (alteran el orden natural del foco)'),
    },
    ariaHidden: {
      selector: '[aria-hidden="true"] a[href], [aria-hidden="true"] button, [aria-hidden="true"] input',
      level: 'A',
      impact: 'critical',
      message: Drupal.t('Elementos interactivos no deben estar dentro de aria-hidden'),
    },
  };

  /**
   * Valida un contenedor HTML contra las reglas ARIA.
   *
   * @param {HTMLElement} container
   *   Elemento DOM a validar.
   *
   * @return {Object}
   *   Resultado con violations, warnings, passes, score y level.
   */
  function validateContainer(container) {
    var violations = [];
    var passes = [];

    Object.keys(RULES).forEach(function (ruleId) {
      var rule = RULES[ruleId];
      var elements = container.querySelectorAll(rule.selector);

      if (elements.length > 0) {
        violations.push({
          rule: ruleId,
          level: rule.level,
          impact: rule.impact,
          message: rule.message,
          count: elements.length,
          elements: Array.from(elements).slice(0, 5).map(function (el) {
            return {
              tag: el.tagName.toLowerCase(),
              id: el.id || null,
              className: el.className || null,
            };
          }),
        });
      } else {
        passes.push({
          rule: ruleId,
          level: rule.level,
          message: rule.message,
        });
      }
    });

    // Verificar jerarquia de encabezados.
    var headings = container.querySelectorAll('h1, h2, h3, h4, h5, h6');
    var headingLevels = Array.from(headings).map(function (h) {
      return parseInt(h.tagName.charAt(1));
    });

    for (var i = 1; i < headingLevels.length; i++) {
      if (headingLevels[i] - headingLevels[i - 1] > 1) {
        violations.push({
          rule: 'headingOrder',
          level: 'A',
          impact: 'moderate',
          message: Drupal.t('Salto de nivel de encabezado: h@from a h@to', {
            '@from': headingLevels[i - 1],
            '@to': headingLevels[i],
          }),
          count: 1,
        });
        break;
      }
    }
    if (headingLevels.length > 1 && !violations.some(function (v) { return v.rule === 'headingOrder'; })) {
      passes.push({
        rule: 'headingOrder',
        level: 'A',
        message: Drupal.t('Jerarquia de encabezados correcta'),
      });
    }

    // Calcular score.
    var totalRules = Object.keys(RULES).length + 1; // +1 por heading order
    var score = Math.round((passes.length / totalRules) * 100);

    // Determinar nivel WCAG.
    var hasAViolations = violations.some(function (v) { return v.level === 'A'; });
    var hasAAViolations = violations.some(function (v) { return v.level === 'AA'; });
    var level = 'AAA';
    if (hasAAViolations) level = 'A';
    if (hasAViolations) level = 'none';

    return {
      violations: violations,
      passes: passes,
      score: score,
      level: level,
      timestamp: new Date().toISOString(),
    };
  }

  /**
   * Renderiza el badge de accesibilidad en el Canvas Editor.
   *
   * @param {Object} result
   *   Resultado de validacion.
   * @param {HTMLElement} badge
   *   Elemento badge donde mostrar el resultado.
   */
  function renderBadge(result, badge) {
    var color = result.violations.length === 0 ? 'var(--ej-color-success, #22c55e)' :
                result.violations.some(function (v) { return v.impact === 'critical'; }) ?
                'var(--ej-color-danger, #ef4444)' : 'var(--ej-color-warning, #f59e0b)';

    badge.style.backgroundColor = color;
    badge.textContent = result.violations.length === 0
      ? Drupal.t('WCAG @level', { '@level': result.level })
      : result.violations.length + ' ' + Drupal.t('violations');
    badge.title = Drupal.t('Score: @score/100 | Nivel: @level | @count violations', {
      '@score': result.score,
      '@level': result.level,
      '@count': result.violations.length,
    });
  }

  /**
   * Behavior principal: Validacion de accesibilidad en el Canvas Editor.
   */
  Drupal.behaviors.jarabaAccessibilityValidator = {
    attach: function (context) {
      once('a11yValidator', '.canvas-editor-page', context).forEach(function (page) {
        // Crear badge de accesibilidad en el toolbar del editor.
        var toolbar = page.querySelector('.gjs-pn-commands') ||
                      page.querySelector('.jaraba-canvas-toolbar');
        if (!toolbar) {
          return;
        }

        var badge = document.createElement('button');
        badge.className = 'jaraba-a11y-badge';
        badge.setAttribute('aria-label', Drupal.t('Estado de accesibilidad'));
        badge.style.cssText = [
          'padding: 4px 12px',
          'border-radius: 12px',
          'font-size: 11px',
          'font-weight: 600',
          'color: white',
          'border: none',
          'cursor: pointer',
          'margin-left: 8px',
        ].join(';');
        toolbar.appendChild(badge);

        // Validar periodicamente el contenido del iframe.
        var validateInterval = setInterval(function () {
          var iframe = page.querySelector('.gjs-frame');
          if (!iframe || !iframe.contentDocument) {
            return;
          }

          var body = iframe.contentDocument.body;
          if (!body) {
            return;
          }

          var result = validateContainer(body);
          renderBadge(result, badge);
        }, 5000);

        // Click en badge muestra detalles.
        badge.addEventListener('click', function () {
          var iframe = page.querySelector('.gjs-frame');
          if (!iframe || !iframe.contentDocument || !iframe.contentDocument.body) {
            return;
          }

          var result = validateContainer(iframe.contentDocument.body);

          // Enviar al server para validacion completa.
          fetch('/api/v1/page-builder/accessibility/validate', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
              html: iframe.contentDocument.body.innerHTML,
            }),
          })
          .then(function (response) { return response.json(); })
          .then(function (serverResult) {
            // Combinar resultados client + server.
            var combined = serverResult.data || result;
            console.table(combined.violations);
            // TODO: Mostrar en slide-panel cuando se implemente.
          })
          .catch(function () {
            // Usar resultado client-side si falla el server.
            console.table(result.violations);
          });
        });

        // Limpiar al destruir.
        page.addEventListener('DOMNodeRemoved', function () {
          clearInterval(validateInterval);
        }, { once: true });
      });
    }
  };

  // Exponer funcion para uso externo.
  Drupal.jarabaAccessibilityValidator = {
    validate: validateContainer,
  };

})(Drupal, once);

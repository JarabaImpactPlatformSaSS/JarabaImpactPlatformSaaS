/**
 * @file
 * Herramienta de verificacion de contraste WCAG para el Canvas Editor.
 *
 * Calcula ratios de contraste en tiempo real entre texto y fondo,
 * y reporta violations contra WCAG 2.1 AA (4.5:1) y AAA (7:1).
 *
 * DIRECTRICES:
 * - Todos los strings via Drupal.t()
 * - Respeta prefers-reduced-motion
 * - WCAG 2.1 AA minimo
 *
 * @see docs/implementacion/2026-02-12_Plan_Cierre_Gaps_Specs_20260126_v1.md P0-03
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Convierte un color CSS a componentes RGB.
   *
   * Soporta: rgb(), rgba(), hex (#RGB, #RRGGBB, #RRGGBBAA).
   *
   * @param {string} color
   *   Color CSS como string.
   *
   * @return {Object|null}
   *   Objeto {r, g, b} con valores 0-255, o null si no se puede parsear.
   */
  function parseColor(color) {
    if (!color || color === 'transparent' || color === 'rgba(0, 0, 0, 0)') {
      return null;
    }

    // rgb() o rgba().
    var match = color.match(/rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/);
    if (match) {
      return {
        r: parseInt(match[1]),
        g: parseInt(match[2]),
        b: parseInt(match[3]),
      };
    }

    // Hex.
    var hex = color.replace('#', '');
    if (hex.length === 3) {
      hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
    }
    if (hex.length >= 6) {
      return {
        r: parseInt(hex.substring(0, 2), 16),
        g: parseInt(hex.substring(2, 4), 16),
        b: parseInt(hex.substring(4, 6), 16),
      };
    }

    return null;
  }

  /**
   * Calcula la luminancia relativa de un color segun WCAG 2.1.
   *
   * @param {Object} color
   *   Objeto {r, g, b} con valores 0-255.
   *
   * @return {number}
   *   Luminancia relativa (0-1).
   */
  function getRelativeLuminance(color) {
    var rs = color.r / 255;
    var gs = color.g / 255;
    var bs = color.b / 255;

    var r = rs <= 0.03928 ? rs / 12.92 : Math.pow((rs + 0.055) / 1.055, 2.4);
    var g = gs <= 0.03928 ? gs / 12.92 : Math.pow((gs + 0.055) / 1.055, 2.4);
    var b = bs <= 0.03928 ? bs / 12.92 : Math.pow((bs + 0.055) / 1.055, 2.4);

    return 0.2126 * r + 0.7152 * g + 0.0722 * b;
  }

  /**
   * Calcula el ratio de contraste entre dos colores segun WCAG 2.1.
   *
   * @param {Object} fg
   *   Color del texto {r, g, b}.
   * @param {Object} bg
   *   Color del fondo {r, g, b}.
   *
   * @return {number}
   *   Ratio de contraste (1:1 a 21:1).
   */
  function getContrastRatio(fg, bg) {
    var l1 = getRelativeLuminance(fg);
    var l2 = getRelativeLuminance(bg);
    var lighter = Math.max(l1, l2);
    var darker = Math.min(l1, l2);
    return (lighter + 0.05) / (darker + 0.05);
  }

  /**
   * Obtiene el color de fondo efectivo de un elemento,
   * subiendo por el arbol DOM si es transparente.
   *
   * @param {HTMLElement} element
   *   Elemento a inspeccionar.
   *
   * @return {Object|null}
   *   Color de fondo {r, g, b} o null.
   */
  function getEffectiveBackground(element) {
    var el = element;
    while (el && el !== document.documentElement) {
      var style = window.getComputedStyle(el);
      var bg = parseColor(style.backgroundColor);
      if (bg) {
        return bg;
      }
      el = el.parentElement;
    }
    // Default: blanco.
    return { r: 255, g: 255, b: 255 };
  }

  /**
   * Analiza todos los elementos de texto en un contenedor
   * y devuelve violations de contraste.
   *
   * @param {HTMLElement} container
   *   Contenedor a analizar.
   *
   * @return {Object}
   *   Resultado con violations, passes, y estadisticas.
   */
  function checkContrast(container) {
    var violations = [];
    var passes = [];

    // Selectores de elementos que contienen texto visible.
    var textSelectors = 'p, span, h1, h2, h3, h4, h5, h6, a, button, li, td, th, label, dt, dd, figcaption, blockquote';
    var elements = container.querySelectorAll(textSelectors);

    elements.forEach(function (el) {
      // Ignorar elementos ocultos.
      var style = window.getComputedStyle(el);
      if (style.display === 'none' || style.visibility === 'hidden' || style.opacity === '0') {
        return;
      }

      // Ignorar elementos vacios.
      if (el.textContent.trim() === '') {
        return;
      }

      var fg = parseColor(style.color);
      var bg = getEffectiveBackground(el);

      if (!fg || !bg) {
        return;
      }

      var ratio = getContrastRatio(fg, bg);
      var fontSize = parseFloat(style.fontSize);
      var fontWeight = parseInt(style.fontWeight) || 400;
      var isLargeText = fontSize >= 18 || (fontSize >= 14 && fontWeight >= 700);

      // WCAG 2.1 AA: 4.5:1 normal, 3:1 large text.
      var aaThreshold = isLargeText ? 3 : 4.5;
      // WCAG 2.1 AAA: 7:1 normal, 4.5:1 large text.
      var aaaThreshold = isLargeText ? 4.5 : 7;

      var entry = {
        element: el.tagName.toLowerCase() + (el.className ? '.' + el.className.split(' ')[0] : ''),
        text: el.textContent.trim().substring(0, 50),
        foreground: 'rgb(' + fg.r + ',' + fg.g + ',' + fg.b + ')',
        background: 'rgb(' + bg.r + ',' + bg.g + ',' + bg.b + ')',
        ratio: Math.round(ratio * 100) / 100,
        isLargeText: isLargeText,
        aaRequired: aaThreshold,
        aaaRequired: aaaThreshold,
      };

      if (ratio < aaThreshold) {
        entry.level = 'AA';
        entry.impact = 'serious';
        entry.message = Drupal.t('Ratio @ratio:1 insuficiente (minimo @required:1 para @size)', {
          '@ratio': entry.ratio,
          '@required': aaThreshold,
          '@size': isLargeText ? Drupal.t('texto grande') : Drupal.t('texto normal'),
        });
        violations.push(entry);
      } else if (ratio < aaaThreshold) {
        entry.level = 'AAA';
        entry.passesAA = true;
        passes.push(entry);
      } else {
        entry.level = 'AAA';
        entry.passesAA = true;
        entry.passesAAA = true;
        passes.push(entry);
      }
    });

    return {
      violations: violations,
      passes: passes,
      totalChecked: violations.length + passes.length,
      score: (violations.length + passes.length) > 0
        ? Math.round((passes.length / (violations.length + passes.length)) * 100)
        : 100,
    };
  }

  /**
   * Behavior: Contrast Checker en el Canvas Editor.
   *
   * Agrega un boton en el toolbar del editor que ejecuta
   * el chequeo de contraste y muestra los resultados.
   */
  Drupal.behaviors.jarabaContrastChecker = {
    attach: function (context) {
      once('contrastChecker', '.canvas-editor-page', context).forEach(function (page) {
        var toolbar = page.querySelector('.gjs-pn-commands') ||
                      page.querySelector('.jaraba-canvas-toolbar');
        if (!toolbar) {
          return;
        }

        // Crear boton de contraste.
        var btn = document.createElement('button');
        btn.className = 'jaraba-contrast-btn';
        btn.setAttribute('aria-label', Drupal.t('Verificar contraste WCAG'));
        btn.title = Drupal.t('Verificar contraste de colores');
        btn.textContent = 'Aa';
        btn.style.cssText = [
          'padding: 4px 10px',
          'border-radius: 4px',
          'font-size: 12px',
          'font-weight: 700',
          'background: var(--ej-color-surface-alt, #f3f4f6)',
          'color: var(--ej-color-text, #1f2937)',
          'border: 1px solid var(--ej-color-border, #e5e7eb)',
          'cursor: pointer',
          'margin-left: 4px',
        ].join(';');
        toolbar.appendChild(btn);

        btn.addEventListener('click', function () {
          var iframe = page.querySelector('.gjs-frame');
          if (!iframe || !iframe.contentDocument || !iframe.contentDocument.body) {
            return;
          }

          var result = checkContrast(iframe.contentDocument.body);

          // Actualizar visual del boton.
          if (result.violations.length === 0) {
            btn.style.backgroundColor = 'var(--ej-color-success, #22c55e)';
            btn.style.color = 'white';
            btn.title = Drupal.t('Todos los textos cumplen WCAG AA (@count verificados)', {
              '@count': result.totalChecked,
            });
          } else {
            btn.style.backgroundColor = 'var(--ej-color-danger, #ef4444)';
            btn.style.color = 'white';
            btn.title = Drupal.t('@count violations de contraste encontradas', {
              '@count': result.violations.length,
            });
          }

          // Log detallado en consola.
          if (result.violations.length > 0) {
            console.group(Drupal.t('Violations de contraste WCAG'));
            console.table(result.violations.map(function (v) {
              return {
                Elemento: v.element,
                Texto: v.text,
                Ratio: v.ratio + ':1',
                Requerido: v.aaRequired + ':1',
                Problema: v.message,
              };
            }));
            console.groupEnd();
          }
        });
      });
    }
  };

  // Exponer funciones para uso externo.
  Drupal.jarabaContrastChecker = {
    checkContrast: checkContrast,
    getContrastRatio: getContrastRatio,
    parseColor: parseColor,
  };

})(Drupal, once);

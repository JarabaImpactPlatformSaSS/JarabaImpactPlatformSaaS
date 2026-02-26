/**
 * @file language-switcher.js
 * Toggle dropdown del selector de idioma en meta-sitios.
 *
 * Funcionalidades:
 * - Click toggle del dropdown
 * - Cierre al hacer click fuera
 * - Cierre con tecla Escape
 * - Navegación con teclado (flechas arriba/abajo)
 */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var switchers = document.querySelectorAll('.language-switcher');

    switchers.forEach(function (switcher) {
      var toggle = switcher.querySelector('.language-switcher__toggle');
      var dropdown = switcher.querySelector('.language-switcher__dropdown');

      if (!toggle || !dropdown) return;

      // Toggle dropdown al hacer click.
      toggle.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var isExpanded = toggle.getAttribute('aria-expanded') === 'true';
        toggle.setAttribute('aria-expanded', String(!isExpanded));
      });

      // Cerrar al hacer click fuera.
      document.addEventListener('click', function (e) {
        if (!switcher.contains(e.target)) {
          toggle.setAttribute('aria-expanded', 'false');
        }
      });

      // Cerrar con Escape.
      switcher.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
          toggle.setAttribute('aria-expanded', 'false');
          toggle.focus();
        }

        // Navegación con flechas dentro del dropdown.
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
          e.preventDefault();
          var options = dropdown.querySelectorAll('.language-switcher__option');
          var focused = dropdown.querySelector(':focus');
          var index = Array.prototype.indexOf.call(options, focused);

          if (e.key === 'ArrowDown') {
            index = index < options.length - 1 ? index + 1 : 0;
          } else {
            index = index > 0 ? index - 1 : options.length - 1;
          }

          options[index].focus();
        }
      });
    });
  });
})();

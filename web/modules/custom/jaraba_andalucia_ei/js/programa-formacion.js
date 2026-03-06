/**
 * @file
 * Programa Formación - Comportamientos de cursos.
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.programaFormacion = {
    attach: function (context) {
      // Animate progress bars on load.
      once('programa-formacion-progress', '.course-card__progress-fill', context).forEach(function (bar) {
        var targetWidth = bar.style.width;
        bar.style.width = '0%';
        requestAnimationFrame(function () {
          bar.style.transition = 'width 0.8s ease-out';
          bar.style.width = targetWidth;
        });
      });
    }
  };

})(Drupal, once);

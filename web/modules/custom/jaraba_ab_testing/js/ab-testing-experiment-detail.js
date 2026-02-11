/**
 * @file
 * Comportamientos JS del detalle de experimento A/B.
 */
(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.jarabaAbExperimentDetail = {
    attach: function (context) {
      // Resaltar fila ganadora en la tabla de variantes
      once('ab-winner-rows', '.ej-ab-detail__variant--winner', context).forEach(function (row) {
        row.style.transition = 'background 0.5s ease';
      });
    }
  };
})(Drupal, once);

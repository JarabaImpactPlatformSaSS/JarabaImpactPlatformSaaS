/**
 * @file
 * knowledge-revision.js — Comportamientos para historial y diff de revisiones.
 *
 * PROPÓSITO:
 * - Formulario de comparación: construye URL con older/newer seleccionados
 * - Resalta la fila actual en la tabla de revisiones
 *
 * G114-2: Versionado de artículos con diff visual
 */
(function (Drupal, once) {
  'use strict';

  /**
   * Formulario de comparación de revisiones.
   *
   * Intercepta el submit del formulario y navega a la URL de comparación
   * con los parámetros older/newer seleccionados.
   */
  Drupal.behaviors.knowledgeRevisionCompare = {
    attach: function (context) {
      once('knowledge-revision-compare', '#knowledge-revision-compare-form', context).forEach(function (form) {
        form.addEventListener('submit', function (e) {
          e.preventDefault();

          var older = form.querySelector('input[name="older"]:checked');
          var newer = form.querySelector('input[name="newer"]:checked');

          if (!older || !newer) {
            return;
          }

          if (older.value === newer.value) {
            alert(Drupal.t('Selecciona dos revisiones diferentes para comparar.'));
            return;
          }

          // Construir URL de comparación reemplazando placeholders.
          var compareRoute = form.getAttribute('data-compare-route') || '';
          var url = compareRoute
            .replace('__OLDER__', older.value)
            .replace('__NEWER__', newer.value);

          if (url) {
            window.location.href = url;
          }
        });
      });
    }
  };

})(Drupal, once);

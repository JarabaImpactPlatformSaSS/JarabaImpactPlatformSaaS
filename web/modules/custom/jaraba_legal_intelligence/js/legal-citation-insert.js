/**
 * @file
 * Legal Intelligence Hub — Insercion de citas en expediente.
 *
 * Gestiona la seleccion de formato de cita (formal, resumida,
 * bibliografica, nota al pie) y la insercion en expedientes
 * del Buzon de Confianza via slide-panel.
 */

(function (Drupal, drupalSettings, once) {

  'use strict';

  Drupal.behaviors.legalCitationInsert = {
    attach: function (context) {
      once('legal-citation-insert', '.legal-citation-insert', context).forEach(function (panel) {
        var formatBtns = panel.querySelectorAll('[data-citation-format]');
        var previewEl = panel.querySelector('.legal-citation-insert__preview');
        var insertBtn = panel.querySelector('.legal-citation-insert__submit');

        formatBtns.forEach(function (btn) {
          btn.addEventListener('click', function (e) {
            e.preventDefault();
            formatBtns.forEach(function (b) { b.classList.remove('is-active'); });
            btn.classList.add('is-active');

            if (previewEl) {
              previewEl.textContent = btn.dataset.citationText || '';
            }
          });
        });

        if (insertBtn) {
          insertBtn.addEventListener('click', function (e) {
            e.preventDefault();
            var activeFormat = panel.querySelector('[data-citation-format].is-active');
            var expedienteSelect = panel.querySelector('[name="expediente_id"]');

            if (!activeFormat || !expedienteSelect) {
              return;
            }

            insertCitation(
              panel.dataset.resolutionId,
              activeFormat.dataset.citationFormat,
              expedienteSelect.value
            );
          });
        }
      });
    }
  };

  /**
   * Inserta cita en expediente via API.
   *
   * @param {string} resolutionId - ID de la resolucion.
   * @param {string} format - Formato de cita.
   * @param {string} expedienteId - ID del expediente destino.
   */
  function insertCitation(resolutionId, format, expedienteId) {
    fetch('/api/v1/legal/bookmark', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({
        resolution_id: resolutionId,
        format: format,
        expediente_id: expedienteId
      })
    })
    .then(function (response) { return response.json(); })
    .then(function (data) {
      if (data.success) {
        Drupal.announce(Drupal.t('Citation inserted successfully.'));
      }
    })
    .catch(function () {
      Drupal.announce(Drupal.t('Error inserting citation.'));
    });
  }

})(Drupal, drupalSettings, once);

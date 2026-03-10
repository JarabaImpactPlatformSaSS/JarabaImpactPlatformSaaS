/**
 * @file
 * Firma masiva — selección y sello de documentos en lote.
 *
 * Sprint 4 — Plan Maestro Andalucía +ei Clase Mundial.
 * Gestiona la selección múltiple de documentos y la firma por sello empresa.
 *
 * INNERHTML-XSS-001: No se usa innerHTML con datos de API.
 * CSRF-JS-CACHE-001: Token CSRF cacheado.
 * ROUTE-LANGPREFIX-001: URLs vía drupalSettings.
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  let csrfToken = null;

  /**
   * Obtiene token CSRF y lo cachea.
   */
  async function getCsrfToken() {
    if (csrfToken) {
      return csrfToken;
    }
    const response = await fetch('/session/token');
    csrfToken = await response.text();
    return csrfToken;
  }

  Drupal.behaviors.jarabaFirmaMasiva = {
    attach: function (context) {
      const containers = once('firma-masiva', '.firma-masiva', context);
      containers.forEach(initFirmaMasiva);
    }
  };

  /**
   * Inicializa un contenedor de firma masiva.
   */
  function initFirmaMasiva(container) {
    const selectAll = container.querySelector('.firma-masiva__select-all input[type="checkbox"]');
    const checkboxes = container.querySelectorAll('.firma-masiva__doc-check');
    const btnSello = container.querySelector('.firma-masiva__btn-sello');
    const selectedCountEl = container.querySelector('.firma-masiva__selected-count');
    const progressContainer = container.querySelector('.firma-masiva__progress');
    const progressFill = container.querySelector('.firma-masiva__progress-bar-fill');
    const progressText = container.querySelector('.firma-masiva__progress-text');
    const filterBtns = container.querySelectorAll('.firma-masiva__filter-btn');
    const config = (drupalSettings.jarabaAndaluciaEi || {}).firmaMasiva || {};

    // Select all toggle.
    if (selectAll) {
      selectAll.addEventListener('change', function () {
        const visibleCheckboxes = container.querySelectorAll('.firma-masiva__doc-check:not([style*="display: none"])');
        visibleCheckboxes.forEach(function (cb) {
          cb.checked = selectAll.checked;
        });
        updateSelectedCount();
      });
    }

    // Individual checkbox changes.
    checkboxes.forEach(function (cb) {
      cb.addEventListener('change', updateSelectedCount);
    });

    // Filter buttons.
    filterBtns.forEach(function (btn) {
      btn.addEventListener('click', function () {
        filterBtns.forEach(function (b) { b.classList.remove('is-active'); });
        btn.classList.add('is-active');
        var filterValue = btn.getAttribute('data-filter');
        var rows = container.querySelectorAll('tr[data-categoria]');
        rows.forEach(function (row) {
          if (!filterValue || filterValue === 'all' || row.getAttribute('data-categoria') === filterValue) {
            row.style.display = '';
          }
          else {
            row.style.display = 'none';
            var cb = row.querySelector('.firma-masiva__doc-check');
            if (cb) {
              cb.checked = false;
            }
          }
        });
        updateSelectedCount();
      });
    });

    // Sello button.
    if (btnSello) {
      btnSello.addEventListener('click', async function () {
        var selectedIds = getSelectedDocIds();
        if (selectedIds.length === 0) {
          return;
        }

        btnSello.disabled = true;
        if (progressContainer) {
          progressContainer.style.display = '';
        }

        var total = selectedIds.length;
        var completed = 0;

        for (var i = 0; i < selectedIds.length; i++) {
          try {
            var token = await getCsrfToken();
            var response = await fetch(config.firmarSelloUrl, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': token
              },
              body: JSON.stringify({ documento_id: selectedIds[i] })
            });

            if (!response.ok) {
              var errorData = await response.json().catch(function () { return {}; });
              Drupal.announce(
                Drupal.t('Error firmando documento @id: @msg', {
                  '@id': selectedIds[i],
                  '@msg': errorData.message || response.statusText
                })
              );
            }
          }
          catch (err) {
            Drupal.announce(Drupal.t('Error de red al firmar documento.'));
          }

          completed++;
          var pct = Math.round((completed / total) * 100);
          if (progressFill) {
            progressFill.style.width = pct + '%';
          }
          if (progressText) {
            progressText.textContent = Drupal.t('@completed de @total', {
              '@completed': completed,
              '@total': total
            });
          }
        }

        btnSello.disabled = false;

        // Reload page to reflect changes.
        if (completed === total) {
          setTimeout(function () {
            window.location.reload();
          }, 1000);
        }
      });
    }

    /**
     * Actualiza el contador de seleccionados.
     */
    function updateSelectedCount() {
      var count = getSelectedDocIds().length;
      if (selectedCountEl) {
        selectedCountEl.textContent = Drupal.t('@count seleccionados', { '@count': count });
      }
      if (btnSello) {
        btnSello.disabled = count === 0;
      }
    }

    /**
     * Obtiene IDs de documentos seleccionados.
     */
    function getSelectedDocIds() {
      var ids = [];
      checkboxes.forEach(function (cb) {
        if (cb.checked && cb.closest('tr').style.display !== 'none') {
          ids.push(parseInt(cb.value, 10));
        }
      });
      return ids;
    }

    // Init.
    updateSelectedCount();
  }

})(Drupal, drupalSettings, once);

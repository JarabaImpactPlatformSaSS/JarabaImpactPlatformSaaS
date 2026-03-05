/**
 * @file
 * P1-06: Merchant CSV import handler.
 *
 * Drag & drop file upload with CSRF token (CSRF-JS-CACHE-001).
 * XSS: Drupal.checkPlain() for API responses (INNERHTML-XSS-001).
 * URLs: via drupalSettings (ROUTE-LANGPREFIX-001).
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  var csrfToken = null;

  Drupal.behaviors.merchantImport = {
    attach: function (context) {
      once('merchant-import', '[data-import-dropzone]', context).forEach(function (dropzone) {
        Drupal.merchantImport.init(dropzone);
      });
    }
  };

  Drupal.merchantImport = {

    init: function (dropzone) {
      var fileInput = document.getElementById('csv-file-input');
      if (!fileInput) {
        return;
      }

      // Fetch CSRF token (CSRF-JS-CACHE-001).
      fetch('/session/token', { credentials: 'same-origin' })
        .then(function (r) { return r.text(); })
        .then(function (token) { csrfToken = token; });

      // Click to select file.
      dropzone.addEventListener('click', function (e) {
        if (e.target !== fileInput) {
          fileInput.click();
        }
      });

      // File selected.
      fileInput.addEventListener('change', function () {
        if (fileInput.files.length > 0) {
          Drupal.merchantImport.handleFile(fileInput.files[0]);
        }
      });

      // Drag & drop.
      dropzone.addEventListener('dragover', function (e) {
        e.preventDefault();
        dropzone.classList.add('comercio-import__upload-zone--dragover');
      });

      dropzone.addEventListener('dragleave', function () {
        dropzone.classList.remove('comercio-import__upload-zone--dragover');
      });

      dropzone.addEventListener('drop', function (e) {
        e.preventDefault();
        dropzone.classList.remove('comercio-import__upload-zone--dragover');
        if (e.dataTransfer.files.length > 0) {
          Drupal.merchantImport.handleFile(e.dataTransfer.files[0]);
        }
      });
    },

    handleFile: function (file) {
      // Validate extension.
      if (!file.name.toLowerCase().endsWith('.csv')) {
        alert(Drupal.t('Solo se aceptan archivos CSV.'));
        return;
      }

      // Validate size (5MB max).
      if (file.size > 5 * 1024 * 1024) {
        alert(Drupal.t('El archivo supera el tamaño máximo de 5MB.'));
        return;
      }

      this.showProgress();
      this.uploadFile(file);
    },

    uploadFile: function (file) {
      var config = drupalSettings.comercioImport || {};
      var url = config.processUrl || '/api/v1/comercio/import/process';

      var formData = new FormData();
      formData.append('csv_file', file);

      var headers = { 'Accept': 'application/json' };
      if (csrfToken) {
        headers['X-CSRF-Token'] = csrfToken;
      }

      fetch(url, {
        method: 'POST',
        headers: headers,
        body: formData,
        credentials: 'same-origin'
      })
        .then(function (response) { return response.json(); })
        .then(function (data) {
          Drupal.merchantImport.hideProgress();
          Drupal.merchantImport.showResults(data);
        })
        .catch(function (error) {
          Drupal.merchantImport.hideProgress();
          Drupal.merchantImport.showResults({
            success: false,
            error: Drupal.t('Error de conexión. Inténtalo de nuevo.')
          });
        });
    },

    showProgress: function () {
      var el = document.getElementById('import-progress');
      var results = document.getElementById('import-results');
      if (el) {
        el.style.display = 'block';
      }
      if (results) {
        results.style.display = 'none';
      }
    },

    hideProgress: function () {
      var el = document.getElementById('import-progress');
      if (el) {
        el.style.display = 'none';
      }
    },

    showResults: function (data) {
      var resultsEl = document.getElementById('import-results');
      if (!resultsEl) {
        return;
      }

      resultsEl.style.display = 'block';

      if (data.success) {
        var successEl = document.getElementById('import-count-success');
        var skippedEl = document.getElementById('import-count-skipped');
        var errorsEl = document.getElementById('import-errors');

        if (successEl) {
          successEl.textContent = data.imported || 0;
        }
        if (skippedEl) {
          skippedEl.textContent = data.skipped || 0;
        }

        // Show errors if any (INNERHTML-XSS-001).
        if (errorsEl && data.errors && data.errors.length > 0) {
          var html = '<ul class="comercio-import__error-list">';
          for (var i = 0; i < Math.min(data.errors.length, 10); i++) {
            html += '<li>' + Drupal.checkPlain(data.errors[i]) + '</li>';
          }
          if (data.errors.length > 10) {
            html += '<li>' + Drupal.t('...y @count errores más.', { '@count': data.errors.length - 10 }) + '</li>';
          }
          html += '</ul>';
          errorsEl.innerHTML = html;
        }
      }
      else {
        resultsEl.innerHTML = '<div class="comercio-import__error-alert">' +
          Drupal.checkPlain(data.error || Drupal.t('Error desconocido.')) + '</div>';
      }
    }

  };

})(Drupal, drupalSettings, once);

/**
 * @file
 * Scheduled Reports - Report list interactions and preview.
 *
 * Handles the report list page interactions including preview modal,
 * status toggling, and report actions.
 *
 * @see templates/analytics-scheduled-reports.html.twig
 */

(function (Drupal, drupalSettings, once) {

  'use strict';

  /**
   * Scheduled Reports behavior.
   *
   * Initializes report list interactions and preview modal.
   */
  Drupal.behaviors.jarabaScheduledReports = {
    attach: function (context) {
      var reportElements = once('jaraba-scheduled-reports', '.analytics-reports', context);

      if (!reportElements.length) {
        return;
      }

      reportElements.forEach(function (reportsEl) {
        initPreviewModal(reportsEl);
      });
    }
  };

  /**
   * Initializes the report preview modal.
   *
   * @param {HTMLElement} reportsEl
   *   The reports root element.
   */
  function initPreviewModal(reportsEl) {
    var modal = reportsEl.querySelector('.analytics-reports__preview-modal');
    if (!modal) {
      return;
    }

    var overlay = modal.querySelector('.analytics-reports__preview-overlay');
    var closeBtn = modal.querySelector('[data-action="close-preview"]');
    var previewBody = modal.querySelector('.analytics-reports__preview-body');

    // Preview buttons.
    reportsEl.addEventListener('click', function (e) {
      var previewBtn = e.target.closest('[data-action="preview-report"]');
      if (previewBtn) {
        var reportId = previewBtn.getAttribute('data-report-id');
        openPreview(modal, previewBody, reportId);
      }
    });

    // Close modal.
    if (closeBtn) {
      closeBtn.addEventListener('click', function () {
        closePreview(modal);
      });
    }

    if (overlay) {
      overlay.addEventListener('click', function () {
        closePreview(modal);
      });
    }

    // Close on Escape key.
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !modal.classList.contains('analytics-reports__preview-modal--hidden')) {
        closePreview(modal);
      }
    });
  }

  /**
   * Opens the preview modal and fetches report data.
   *
   * @param {HTMLElement} modal
   *   The modal element.
   * @param {HTMLElement} previewBody
   *   The preview body container.
   * @param {string} reportId
   *   The report ID to preview.
   */
  function openPreview(modal, previewBody, reportId) {
    modal.classList.remove('analytics-reports__preview-modal--hidden');
    modal.setAttribute('aria-hidden', 'false');

    previewBody.innerHTML = '<div class="analytics-reports__preview-loading">' +
      Drupal.t('Loading preview...') + '</div>';

    fetch('/api/v1/analytics/scheduled-reports/' + reportId + '/preview', {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function (response) { return response.json(); })
    .then(function (result) {
      if (result.error) {
        previewBody.innerHTML = '<div class="analytics-reports__preview-error">' +
          Drupal.checkPlain(result.error) + '</div>';
        return;
      }

      var report = result.report || {};
      var html = '<div class="analytics-reports__preview-report">';
      html += '<h4 class="analytics-reports__preview-report-title">' +
        Drupal.checkPlain(report.title || '') + '</h4>';
      html += '<p class="analytics-reports__preview-report-meta">' +
        Drupal.t('Schedule: @type', { '@type': report.schedule_type || '' }) + '</p>';

      if (report.generated_at) {
        var date = new Date(report.generated_at * 1000);
        html += '<p class="analytics-reports__preview-report-date">' +
          Drupal.t('Generated: @date', { '@date': date.toLocaleString() }) + '</p>';
      }

      html += '<div class="analytics-reports__preview-report-config">';
      html += '<h5>' + Drupal.t('Configuration') + '</h5>';
      html += '<pre class="analytics-reports__preview-json">' +
        Drupal.checkPlain(JSON.stringify(report.config || {}, null, 2)) + '</pre>';
      html += '</div>';

      html += '</div>';
      previewBody.innerHTML = html;
    })
    .catch(function () {
      previewBody.innerHTML = '<div class="analytics-reports__preview-error">' +
        Drupal.t('Failed to load preview.') + '</div>';
    });
  }

  /**
   * Closes the preview modal.
   *
   * @param {HTMLElement} modal
   *   The modal element.
   */
  function closePreview(modal) {
    modal.classList.add('analytics-reports__preview-modal--hidden');
    modal.setAttribute('aria-hidden', 'true');
  }

})(Drupal, drupalSettings, once);

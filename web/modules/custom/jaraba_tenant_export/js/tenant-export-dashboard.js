/**
 * @file
 * Tenant Export Dashboard — Drupal behavior.
 *
 * Handles section selection, export requests, progress polling,
 * and history rendering via the Tenant Export API.
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  const API_BASE = drupalSettings.jarabaTenantExport?.apiBase || '/api/v1/tenant-export';
  const POLL_INTERVAL = 3000; // 3 seconds

  Drupal.behaviors.tenantExportDashboard = {
    attach: function (context) {
      once('tenant-export-init', '#tenant-export-app', context).forEach(function (el) {
        initDashboard(el);
      });
    }
  };

  function initDashboard(container) {
    var config = drupalSettings.jarabaTenantExport || {};
    renderSections(config.sections || {});
    renderHistory(config.exports || []);
    setupRequestButton(config);
    checkRateLimit(config);
  }

  /**
   * Renders section checkboxes.
   */
  function renderSections(sections) {
    var list = document.getElementById('export-sections-list');
    if (!list) return;

    var html = '';
    var defaults = drupalSettings.jarabaTenantExport?.defaultSections || ['core', 'analytics', 'knowledge', 'operational', 'files'];

    Object.keys(sections).forEach(function (key) {
      var section = sections[key];
      var checked = defaults.indexOf(key) !== -1 ? 'checked' : '';
      var disabled = section.available === false ? 'ej-export__section-item--disabled' : '';

      html += '<label class="ej-export__section-item ' + disabled + '">';
      html += '<input type="checkbox" name="sections[]" value="' + key + '" ' + checked;
      if (!section.available) html += ' disabled';
      html += '>';
      html += '<div>';
      html += '<div class="ej-export__section-label">' + Drupal.checkPlain(section.label) + '</div>';
      html += '<div class="ej-export__section-desc">' + Drupal.checkPlain(section.description) + '</div>';
      html += '</div>';
      html += '</label>';
    });

    list.innerHTML = html;
  }

  /**
   * Renders export history.
   */
  function renderHistory(exports) {
    var list = document.getElementById('export-history-list');
    var emptyState = document.getElementById('export-empty-state');
    if (!list) return;

    if (!exports || exports.length === 0) {
      list.innerHTML = '';
      if (emptyState) emptyState.style.display = 'block';
      return;
    }

    if (emptyState) emptyState.style.display = 'none';

    var html = '';
    exports.forEach(function (exp) {
      var date = exp.created ? new Date(exp.created * 1000).toLocaleString() : '-';
      var size = exp.file_size > 0 ? formatBytes(exp.file_size) : '-';

      html += '<div class="ej-export__history-item">';
      html += '<div class="ej-export__history-info">';
      html += '<div class="ej-export__history-date">' + date + '</div>';
      html += '<div class="ej-export__history-meta">' + Drupal.t('Type') + ': ' + Drupal.checkPlain(exp.type) + ' | ' + Drupal.t('Size') + ': ' + size + '</div>';
      html += '</div>';
      html += '<span class="ej-export__status ej-export__status--' + exp.status + '">' + Drupal.checkPlain(exp.status_label || exp.status) + '</span>';

      if (exp.is_downloadable && exp.download_token) {
        html += '<div class="ej-export__history-actions">';
        html += '<a class="ej-export__download-btn" href="' + API_BASE + '/' + exp.download_token + '/download">' + Drupal.t('Download') + '</a>';
        html += '</div>';
      }
      else if (['queued', 'collecting', 'packaging'].indexOf(exp.status) !== -1) {
        html += '<div class="ej-export__history-actions">';
        html += '<span class="ej-export__progress-percent">' + exp.progress + '%</span>';
        html += '</div>';
        startPolling(exp.id);
      }

      html += '</div>';
    });

    list.innerHTML = html;
  }

  /**
   * Sets up the request button click handler.
   */
  function setupRequestButton(config) {
    var btn = document.getElementById('export-submit-btn');
    if (!btn) return;

    if (!config.canRequest) {
      btn.disabled = true;
    }

    btn.addEventListener('click', function () {
      btn.disabled = true;
      btn.textContent = Drupal.t('Requesting...');

      var selectedSections = [];
      document.querySelectorAll('#export-sections-list input[type="checkbox"]:checked').forEach(function (cb) {
        selectedSections.push(cb.value);
      });

      var exportType = document.getElementById('export-type')?.value || 'full';

      fetch(API_BASE + '/request', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin',
        body: JSON.stringify({
          type: exportType,
          sections: selectedSections
        })
      })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (data.success) {
          btn.textContent = Drupal.t('Export Requested');
          if (data.data?.record_id) {
            startPolling(data.data.record_id);
          }
          // Refresh history after a moment.
          setTimeout(refreshHistory, 1000);
        } else {
          btn.disabled = false;
          btn.textContent = Drupal.t('Request Export');
          alert(data.message || Drupal.t('Request failed.'));
        }
      })
      .catch(function (err) {
        btn.disabled = false;
        btn.textContent = Drupal.t('Request Export');
        console.error('Export request failed:', err);
      });
    });
  }

  /**
   * Checks and displays rate limit info.
   */
  function checkRateLimit(config) {
    var rateDiv = document.getElementById('export-rate-limit');
    var retrySpan = document.getElementById('export-retry-after');

    if (!config.canRequest && config.rateLimitInfo?.retry_after_formatted) {
      if (rateDiv) rateDiv.style.display = 'block';
      if (retrySpan) retrySpan.textContent = config.rateLimitInfo.retry_after_formatted;
    }
  }

  /**
   * Polls export status every N seconds.
   */
  var activePollers = {};

  function startPolling(recordId) {
    if (activePollers[recordId]) return;

    activePollers[recordId] = setInterval(function () {
      fetch(API_BASE + '/' + recordId + '/status', {
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (!data.success) return;

        var exp = data.data;
        updateProgressUI(exp);

        if (['completed', 'failed', 'expired', 'cancelled'].indexOf(exp.status) !== -1) {
          clearInterval(activePollers[recordId]);
          delete activePollers[recordId];
          refreshHistory();
        }
      })
      .catch(function () {
        // Silently ignore polling errors.
      });
    }, POLL_INTERVAL);
  }

  /**
   * Updates progress UI elements.
   */
  function updateProgressUI(exp) {
    var activeSection = document.getElementById('export-active-section');
    if (!activeSection) return;

    if (['queued', 'collecting', 'packaging'].indexOf(exp.status) !== -1) {
      var phaseLabels = {
        'core': Drupal.t('Collecting core data...'),
        'analytics': Drupal.t('Collecting analytics...'),
        'knowledge': Drupal.t('Collecting knowledge base...'),
        'operational': Drupal.t('Collecting operational data...'),
        'vertical': Drupal.t('Collecting vertical data...'),
        'files': Drupal.t('Collecting files...'),
        'packaging': Drupal.t('Creating ZIP package...'),
        'complete': Drupal.t('Done!')
      };

      activeSection.innerHTML = '<div class="ej-export__progress">' +
        '<div class="ej-export__progress-header">' +
        '<span class="ej-export__progress-label">' + Drupal.t('Progress') + '</span>' +
        '<span class="ej-export__progress-percent">' + exp.progress + '%</span>' +
        '</div>' +
        '<div class="ej-export__progress-bar">' +
        '<div class="ej-export__progress-fill" style="width: ' + exp.progress + '%;"></div>' +
        '</div>' +
        '<p class="ej-export__progress-phase">' + (phaseLabels[exp.current_phase] || exp.current_phase) + '</p>' +
        '</div>';
    }
    else if (exp.status === 'completed' && exp.is_downloadable) {
      activeSection.innerHTML = '<div class="ej-export__download-card">' +
        '<div class="ej-export__download-info">' +
        '<h3 class="ej-export__download-title">' + Drupal.t('Export Ready') + '</h3>' +
        '<p class="ej-export__download-meta">' + formatBytes(exp.file_size) + '</p>' +
        '</div>' +
        '<a class="ej-export__download-btn" href="' + API_BASE + '/' + exp.download_token + '/download">' + Drupal.t('Download ZIP') + '</a>' +
        '</div>';
    }
    else {
      activeSection.innerHTML = '';
    }
  }

  /**
   * Refreshes the history list from the API.
   */
  function refreshHistory() {
    fetch(API_BASE + '/history', {
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function (res) { return res.json(); })
    .then(function (data) {
      if (data.success) {
        renderHistory(data.data || []);
      }
    })
    .catch(function () {});
  }

  /**
   * Formats bytes to human-readable string.
   */
  function formatBytes(bytes) {
    if (bytes >= 1073741824) return (bytes / 1073741824).toFixed(2) + ' GB';
    if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + ' MB';
    if (bytes >= 1024) return (bytes / 1024).toFixed(2) + ' KB';
    return bytes + ' B';
  }

})(Drupal, drupalSettings, once);

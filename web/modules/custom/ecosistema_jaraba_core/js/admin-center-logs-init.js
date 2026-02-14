/**
 * @file
 * Admin Center Logs — Activity log viewer initializer.
 *
 * Fetches combined audit + system logs with filters and pagination.
 *
 * F6 — Doc 181 / Spec f104 §FASE 6.
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  let currentSource = 'all';
  let currentSeverity = '';
  let currentQuery = '';
  let currentOffset = 0;
  const PAGE_SIZE = 30;
  let searchTimeout = null;

  Drupal.behaviors.adminCenterLogsInit = {
    attach(context) {
      once('ac-logs-init', '.admin-center-logs', context).forEach(() => {
        const settings = drupalSettings.adminCenter || {};
        const logsUrl = settings.logsApiUrl || '/api/v1/admin/logs';

        fetchLogs(logsUrl);
        initSourceTabs(logsUrl);
        initSeverityFilters(logsUrl);
        initSearch(logsUrl);
      });
    },
  };

  // ===========================================================================
  // FETCH & RENDER
  // ===========================================================================

  async function fetchLogs(baseUrl) {
    const container = document.getElementById('logs-list');
    const pagination = document.getElementById('logs-pagination');
    if (!container) return;

    const params = new URLSearchParams();
    params.set('source', currentSource);
    if (currentSeverity) params.set('severity', currentSeverity);
    if (currentQuery) params.set('q', currentQuery);
    params.set('limit', PAGE_SIZE);
    params.set('offset', currentOffset);

    try {
      const res = await fetch(baseUrl + '?' + params.toString(), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });
      const json = await res.json();

      if (!json.success || !json.data || json.data.length === 0) {
        container.innerHTML = `<div class="ac-alerts__empty"><p>${Drupal.t('No se encontraron logs con los filtros actuales.')}</p></div>`;
        if (pagination) pagination.innerHTML = '';
        return;
      }

      let html = '<div class="ac-logs__table-wrap"><table class="ac-logs__table"><thead><tr>';
      html += `<th class="ac-logs__th ac-logs__th--time">${Drupal.t('Hora')}</th>`;
      html += `<th class="ac-logs__th ac-logs__th--severity">${Drupal.t('Nivel')}</th>`;
      html += `<th class="ac-logs__th ac-logs__th--source">${Drupal.t('Fuente')}</th>`;
      html += `<th class="ac-logs__th ac-logs__th--type">${Drupal.t('Tipo')}</th>`;
      html += `<th class="ac-logs__th ac-logs__th--message">${Drupal.t('Mensaje')}</th>`;
      html += `<th class="ac-logs__th ac-logs__th--actor">${Drupal.t('Actor')}</th>`;
      html += '</tr></thead><tbody>';

      json.data.forEach(log => {
        html += renderLogRow(log);
      });

      html += '</tbody></table></div>';
      container.innerHTML = html;

      // Pagination.
      if (pagination && json.meta) {
        renderPagination(pagination, json.meta, baseUrl);
      }
    }
    catch (err) {
      container.innerHTML = `<div class="ac-alerts__empty"><p>${Drupal.t('Error al cargar logs.')}</p></div>`;
    }
  }

  function renderLogRow(log) {
    const severityClass = `ac-logs__severity ac-logs__severity--${log.severity}`;
    const sourceClass = log.source === 'audit' ? 'ac-logs__source--audit' : 'ac-logs__source--system';
    const message = truncate(log.message || '', 120);
    const actor = log.actor || (log.actor_id ? `uid:${log.actor_id}` : '—');

    return `<tr class="ac-logs__row ac-logs__row--${log.severity}">
      <td class="ac-logs__td ac-logs__td--time">${escapeHtml(log.time_ago)}</td>
      <td class="ac-logs__td"><span class="${severityClass}">${escapeHtml(log.severity)}</span></td>
      <td class="ac-logs__td"><span class="ac-logs__source ${sourceClass}">${escapeHtml(log.source)}</span></td>
      <td class="ac-logs__td ac-logs__td--type">${escapeHtml(log.event_type)}</td>
      <td class="ac-logs__td ac-logs__td--message" title="${escapeHtml(log.message || '')}">${escapeHtml(message)}</td>
      <td class="ac-logs__td ac-logs__td--actor">${escapeHtml(actor)}</td>
    </tr>`;
  }

  // ===========================================================================
  // FILTERS
  // ===========================================================================

  function initSourceTabs(logsUrl) {
    document.querySelectorAll('[data-source]').forEach(tab => {
      tab.addEventListener('click', () => {
        document.querySelectorAll('.ac-logs__source-tab').forEach(t => {
          t.classList.remove('ac-logs__source-tab--active');
        });
        tab.classList.add('ac-logs__source-tab--active');
        currentSource = tab.dataset.source;
        currentOffset = 0;
        fetchLogs(logsUrl);
      });
    });
  }

  function initSeverityFilters(logsUrl) {
    document.querySelectorAll('[data-severity]').forEach(chip => {
      chip.addEventListener('click', () => {
        document.querySelectorAll('.ac-logs__severity-filters .ac-alerts__filter-chip').forEach(c => {
          c.classList.remove('ac-alerts__filter-chip--active');
        });
        chip.classList.add('ac-alerts__filter-chip--active');
        currentSeverity = chip.dataset.severity;
        currentOffset = 0;
        fetchLogs(logsUrl);
      });
    });
  }

  function initSearch(logsUrl) {
    const input = document.getElementById('logs-search');
    if (!input) return;

    input.addEventListener('input', () => {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        currentQuery = input.value.trim();
        currentOffset = 0;
        fetchLogs(logsUrl);
      }, 400);
    });
  }

  // ===========================================================================
  // PAGINATION
  // ===========================================================================

  function renderPagination(container, meta, baseUrl) {
    const total = meta.total || 0;
    const pages = Math.ceil(total / PAGE_SIZE);
    const currentPage = Math.floor(currentOffset / PAGE_SIZE) + 1;

    if (pages <= 1) {
      container.innerHTML = '';
      return;
    }

    let html = '<div class="ac-alerts__pagination-controls">';
    html += `<span class="ac-alerts__pagination-info">${total} ${Drupal.t('registros')}</span>`;

    if (currentPage > 1) {
      html += `<button type="button" class="ac-alerts__page-btn" data-page="${currentPage - 1}">&laquo;</button>`;
    }

    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(pages, startPage + 4);

    for (let i = startPage; i <= endPage; i++) {
      const active = i === currentPage ? ' ac-alerts__page-btn--active' : '';
      html += `<button type="button" class="ac-alerts__page-btn${active}" data-page="${i}">${i}</button>`;
    }

    if (currentPage < pages) {
      html += `<button type="button" class="ac-alerts__page-btn" data-page="${currentPage + 1}">&raquo;</button>`;
    }
    html += '</div>';

    container.innerHTML = html;

    container.querySelectorAll('[data-page]').forEach(btn => {
      btn.addEventListener('click', () => {
        currentOffset = (parseInt(btn.dataset.page, 10) - 1) * PAGE_SIZE;
        fetchLogs(baseUrl);
      });
    });
  }

  // ===========================================================================
  // HELPERS
  // ===========================================================================

  function truncate(str, maxLen) {
    return str.length > maxLen ? str.substring(0, maxLen) + '...' : str;
  }

  function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

})(Drupal, drupalSettings, once);

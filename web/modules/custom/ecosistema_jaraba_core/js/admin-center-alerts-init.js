/**
 * @file
 * Admin Center Alerts — Dashboard initializer.
 *
 * Fetches alert summary, alert list, and playbooks from API
 * and hydrates scorecards, alert cards, and playbook grid.
 *
 * F6 — Doc 181 / Spec f104 §FASE 5.
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  let currentFilter = 'all';
  let currentOffset = 0;
  const PAGE_SIZE = 20;

  Drupal.behaviors.adminCenterAlertsInit = {
    attach(context) {
      once('ac-alerts-init', '.admin-center-alerts', context).forEach(() => {
        const settings = drupalSettings.adminCenter || {};

        // Fetch and render summary scorecards.
        fetchSummary(settings.alertsSummaryUrl || '/api/v1/admin/alerts/summary');

        // Fetch and render alert list.
        fetchAlerts(settings.alertsListUrl || '/api/v1/admin/alerts');

        // Fetch and render playbooks.
        fetchPlaybooks(settings.playbooksListUrl || '/api/v1/admin/playbooks');

        // Bind filter chips.
        initFilters(settings.alertsListUrl || '/api/v1/admin/alerts');

        // Bind alert actions in slide-panel.
        initSlideActions();
      });
    },
  };

  // ===========================================================================
  // SCORECARDS
  // ===========================================================================

  async function fetchSummary(url) {
    try {
      const res = await fetch(url, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });
      const json = await res.json();

      if (!json.success) return;

      const data = json.data;

      // Populate scorecard values.
      if (data.counts) {
        Object.entries(data.counts).forEach(([key, val]) => {
          const card = document.querySelector(`[data-scorecard="${key}"]`);
          if (card) {
            const valueEl = card.querySelector('[data-value]');
            if (valueEl) {
              valueEl.textContent = val;
            }
          }
        });
      }

      // Populate playbook stats.
      const activePb = document.querySelector('[data-stat="active_playbooks"]');
      if (activePb) activePb.textContent = data.active_playbooks || 0;

      const runningExec = document.querySelector('[data-stat="running_executions"]');
      if (runningExec) runningExec.textContent = data.running_executions || 0;
    }
    catch (err) {
      // Silently fail.
    }
  }

  // ===========================================================================
  // ALERT LIST
  // ===========================================================================

  async function fetchAlerts(baseUrl) {
    const container = document.getElementById('alerts-list');
    const pagination = document.getElementById('alerts-pagination');
    if (!container) return;

    const params = new URLSearchParams();
    if (currentFilter !== 'all') {
      params.set('severity', currentFilter);
    }
    params.set('limit', PAGE_SIZE);
    params.set('offset', currentOffset);

    const url = baseUrl + '?' + params.toString();

    try {
      const res = await fetch(url, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });
      const json = await res.json();

      if (!json.success || !json.data || json.data.length === 0) {
        container.innerHTML = `<div class="ac-alerts__empty">
          <p>${Drupal.t('No hay alertas activas. El sistema esta funcionando correctamente.')}</p>
        </div>`;
        if (pagination) pagination.innerHTML = '';
        return;
      }

      let html = '';
      json.data.forEach(alert => {
        html += renderAlertCard(alert);
      });
      container.innerHTML = html;

      // Pagination.
      if (pagination && json.meta) {
        renderPagination(pagination, json.meta, baseUrl);
      }

      // Bind card actions.
      bindCardActions(container);
    }
    catch (err) {
      container.innerHTML = `<div class="ac-alerts__empty">
        <p>${Drupal.t('Error al cargar alertas.')}</p>
      </div>`;
    }
  }

  function renderAlertCard(alert) {
    const severityIcon = {
      critical: 'alert-triangle',
      warning: 'alert-circle',
      info: 'info',
    };
    const icon = severityIcon[alert.severity] || 'bell';

    const tenantBadge = alert.tenant_label
      ? `<span class="ac-alerts__alert-tenant">${escapeHtml(alert.tenant_label)}</span>`
      : '';

    return `<div class="ac-alerts__alert-card ac-alerts__alert-card--${alert.severity}" data-alert-id="${alert.id}">
      <div class="ac-alerts__alert-icon ac-alerts__alert-icon--${alert.severity}">
        <svg width="18" height="18"><use href="#icon-${icon}"/></svg>
      </div>
      <div class="ac-alerts__alert-body">
        <div class="ac-alerts__alert-header">
          <span class="ac-alerts__severity-badge ac-alerts__severity-badge--${alert.severity}">${escapeHtml(alert.severity_label)}</span>
          <span class="ac-alerts__type-badge">${escapeHtml(alert.alert_type_label)}</span>
          ${tenantBadge}
          <span class="ac-alerts__alert-time">${escapeHtml(alert.time_ago)}</span>
        </div>
        <div class="ac-alerts__alert-title">${escapeHtml(alert.title)}</div>
        <div class="ac-alerts__alert-metric">
          <span>${Drupal.t('Valor')}: <strong>${escapeHtml(alert.metric_value || '—')}</strong></span>
          <span>${Drupal.t('Umbral')}: ${escapeHtml(alert.threshold || '—')}</span>
        </div>
      </div>
      <div class="ac-alerts__alert-actions">
        <button type="button" class="ac-alerts__action-btn" data-action="view" data-slide-panel data-slide-panel-url="/admin/jaraba/center/alerts/${alert.id}/panel" data-slide-panel-title="${escapeHtml(alert.title)}" title="${Drupal.t('Ver detalle')}">
          <svg width="16" height="16"><use href="#icon-eye"/></svg>
        </button>
        <button type="button" class="ac-alerts__action-btn ac-alerts__action-btn--resolve" data-action="resolve" data-alert-id="${alert.id}" title="${Drupal.t('Resolver')}">
          <svg width="16" height="16"><use href="#icon-check-circle"/></svg>
        </button>
        <button type="button" class="ac-alerts__action-btn ac-alerts__action-btn--dismiss" data-action="dismiss" data-alert-id="${alert.id}" title="${Drupal.t('Descartar')}">
          <svg width="16" height="16"><use href="#icon-x-circle"/></svg>
        </button>
      </div>
    </div>`;
  }

  function bindCardActions(container) {
    container.querySelectorAll('[data-action="resolve"]').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.stopPropagation();
        const alertId = btn.dataset.alertId;
        updateAlertState(alertId, 'resolved');
      });
    });

    container.querySelectorAll('[data-action="dismiss"]').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.stopPropagation();
        const alertId = btn.dataset.alertId;
        updateAlertState(alertId, 'dismissed');
      });
    });
  }

  async function updateAlertState(alertId, newState) {
    try {
      const res = await fetch(`/api/v1/admin/alerts/${alertId}/state`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ state: newState }),
      });
      const json = await res.json();

      if (json.success) {
        // Remove the card from the list with fade animation.
        const card = document.querySelector(`[data-alert-id="${alertId}"]`);
        if (card) {
          card.style.opacity = '0';
          card.style.transform = 'translateX(20px)';
          setTimeout(() => {
            card.remove();
            // Refresh summary.
            const settings = drupalSettings.adminCenter || {};
            fetchSummary(settings.alertsSummaryUrl || '/api/v1/admin/alerts/summary');
          }, 300);
        }
      }
    }
    catch (err) {
      // Silently fail.
    }
  }

  // ===========================================================================
  // FILTERS
  // ===========================================================================

  function initFilters(alertsUrl) {
    const filterContainer = document.getElementById('alerts-filters');
    if (!filterContainer) return;

    filterContainer.addEventListener('click', (e) => {
      const chip = e.target.closest('[data-filter]');
      if (!chip) return;

      // Update active state.
      filterContainer.querySelectorAll('.ac-alerts__filter-chip').forEach(c => {
        c.classList.remove('ac-alerts__filter-chip--active');
      });
      chip.classList.add('ac-alerts__filter-chip--active');

      currentFilter = chip.dataset.filter;
      currentOffset = 0;
      fetchAlerts(alertsUrl);
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
    html += `<span class="ac-alerts__pagination-info">${total} ${Drupal.t('alertas')}</span>`;

    if (currentPage > 1) {
      html += `<button type="button" class="ac-alerts__page-btn" data-page="${currentPage - 1}">&laquo;</button>`;
    }
    for (let i = 1; i <= Math.min(pages, 5); i++) {
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
        fetchAlerts(baseUrl);
      });
    });
  }

  // ===========================================================================
  // PLAYBOOKS
  // ===========================================================================

  async function fetchPlaybooks(url) {
    const container = document.getElementById('playbooks-grid');
    if (!container) return;

    try {
      const res = await fetch(url, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });
      const json = await res.json();

      if (!json.success || !json.data || json.data.length === 0) {
        container.innerHTML = `<p class="ac-datatable__empty">${Drupal.t('No hay playbooks configurados.')}</p>`;
        return;
      }

      let html = '';
      json.data.forEach(pb => {
        html += renderPlaybookCard(pb);
      });
      container.innerHTML = html;

      // Bind execute buttons.
      container.querySelectorAll('[data-execute-playbook]').forEach(btn => {
        btn.addEventListener('click', () => executePlaybook(btn.dataset.executePlaybook));
      });
    }
    catch (err) {
      container.innerHTML = `<p class="ac-datatable__empty">${Drupal.t('Error al cargar playbooks.')}</p>`;
    }
  }

  function renderPlaybookCard(pb) {
    const statusClass = pb.status === 'active' ? 'good' : (pb.status === 'paused' ? 'warning' : 'neutral');
    const statusLabel = {
      active: Drupal.t('Activo'),
      paused: Drupal.t('Pausado'),
      archived: Drupal.t('Archivado'),
    };
    const priorityClass = {
      urgent: 'danger',
      high: 'impulse',
      medium: 'corporate',
      low: 'neutral',
    };

    return `<div class="ac-alerts__playbook-card">
      <div class="ac-alerts__playbook-header">
        <span class="ac-alerts__playbook-name">${escapeHtml(pb.name)}</span>
        <span class="ac-finance__health ac-finance__health--${statusClass}">${statusLabel[pb.status] || pb.status}</span>
      </div>
      <div class="ac-alerts__playbook-meta">
        <span class="ac-alerts__playbook-trigger">${escapeHtml(pb.trigger_type_label)}</span>
        <span class="ac-alerts__playbook-priority ac-alerts__playbook-priority--${priorityClass[pb.priority] || 'neutral'}">${escapeHtml(pb.priority)}</span>
      </div>
      <div class="ac-alerts__playbook-stats">
        <span>${pb.steps_count} ${Drupal.t('pasos')}</span>
        <span>${pb.execution_count} ${Drupal.t('ejecuciones')}</span>
        <span>${pb.success_rate}% ${Drupal.t('exito')}</span>
      </div>
      <div class="ac-alerts__playbook-actions">
        ${pb.status === 'active' ? `<button type="button" class="admin-center__btn admin-center__btn--sm admin-center__btn--secondary" data-execute-playbook="${pb.id}">${Drupal.t('Ejecutar')}</button>` : ''}
        ${pb.auto_execute ? `<span class="ac-alerts__auto-badge">${Drupal.t('Auto')}</span>` : ''}
      </div>
    </div>`;
  }

  async function executePlaybook(playbookId) {
    try {
      const res = await fetch(`/api/v1/admin/playbooks/${playbookId}/execute`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({}),
      });
      const json = await res.json();

      if (json.success) {
        // Refresh summary for running_executions count.
        const settings = drupalSettings.adminCenter || {};
        fetchSummary(settings.alertsSummaryUrl || '/api/v1/admin/alerts/summary');
      }
    }
    catch (err) {
      // Silently fail.
    }
  }

  // ===========================================================================
  // SLIDE PANEL ACTIONS
  // ===========================================================================

  function initSlideActions() {
    document.addEventListener('click', (e) => {
      const btn = e.target.closest('[data-alert-action]');
      if (!btn) return;

      const action = btn.dataset.alertAction;
      const alertId = btn.dataset.alertId;

      updateAlertState(alertId, action).then(() => {
        // Close slide panel if open.
        const panel = document.querySelector('.slide-panel');
        if (panel) {
          const closeBtn = panel.querySelector('[data-slide-panel-close]');
          if (closeBtn) closeBtn.click();
        }
      });
    });
  }

  // ===========================================================================
  // HELPERS
  // ===========================================================================

  function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

})(Drupal, drupalSettings, once);

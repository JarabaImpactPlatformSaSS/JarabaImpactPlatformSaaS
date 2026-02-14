/**
 * @file
 * Admin Center Settings — Configuration page initializer.
 *
 * Manages tabbed navigation and CRUD operations for:
 *   1. General platform settings (form save via POST).
 *   2. Billing plans list (read-only).
 *   3. Integrations status cards.
 *   4. API Keys (create, list, revoke).
 *
 * F6 — Doc 181 / Spec f104 §FASE 7.
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.adminCenterSettingsInit = {
    attach(context) {
      once('ac-settings-init', '.admin-center-settings', context).forEach(() => {
        const settings = drupalSettings.adminCenter || {};

        initTabs();
        fetchOverview(settings.settingsOverviewUrl || '/api/v1/admin/settings');
        initGeneralForm(settings.settingsGeneralSaveUrl || '/api/v1/admin/settings/general');
        initApiKeyActions(settings);
      });
    },
  };

  // ===========================================================================
  // TABS
  // ===========================================================================

  function initTabs() {
    document.querySelectorAll('.ac-settings__tab').forEach(tab => {
      tab.addEventListener('click', () => {
        // Deactivate all.
        document.querySelectorAll('.ac-settings__tab').forEach(t => {
          t.classList.remove('ac-settings__tab--active');
          t.setAttribute('aria-selected', 'false');
        });
        document.querySelectorAll('.ac-settings__panel').forEach(p => {
          p.classList.remove('ac-settings__panel--active');
          p.hidden = true;
        });

        // Activate clicked.
        tab.classList.add('ac-settings__tab--active');
        tab.setAttribute('aria-selected', 'true');

        const panelId = 'panel-' + tab.dataset.tab;
        const panel = document.getElementById(panelId);
        if (panel) {
          panel.classList.add('ac-settings__panel--active');
          panel.hidden = false;
        }
      });
    });
  }

  // ===========================================================================
  // FETCH OVERVIEW
  // ===========================================================================

  async function fetchOverview(url) {
    try {
      const res = await fetch(url, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });
      const json = await res.json();
      if (!json.success) return;

      const data = json.data;

      // Populate general form.
      if (data.general) populateGeneralForm(data.general);

      // Render plans.
      if (data.plans) renderPlans(data.plans);

      // Render integrations.
      if (data.integrations) renderIntegrations(data.integrations);

      // Render API keys.
      if (data.api_keys) renderApiKeys(data.api_keys);
    }
    catch (err) {
      // Silently fail.
    }
  }

  // ===========================================================================
  // GENERAL SETTINGS
  // ===========================================================================

  function populateGeneralForm(settings) {
    const fields = {
      'setting-platform-name': 'platform_name',
      'setting-primary-domain': 'primary_domain',
      'setting-support-email': 'support_email',
      'setting-default-language': 'default_language',
      'setting-timezone': 'timezone',
    };

    Object.entries(fields).forEach(([elId, key]) => {
      const el = document.getElementById(elId);
      if (el && settings[key]) el.value = settings[key];
    });
  }

  function initGeneralForm(saveUrl) {
    const form = document.getElementById('settings-general-form');
    if (!form) return;

    form.addEventListener('submit', async (e) => {
      e.preventDefault();

      const btn = document.getElementById('btn-save-general');
      const status = document.getElementById('save-status');
      if (btn) btn.disabled = true;
      if (status) status.textContent = Drupal.t('Guardando...');

      const formData = new FormData(form);
      const body = {};
      formData.forEach((val, key) => { body[key] = val; });

      try {
        const res = await fetch(saveUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: JSON.stringify(body),
        });
        const json = await res.json();

        if (json.success) {
          if (status) {
            status.textContent = Drupal.t('Guardado');
            status.classList.add('ac-settings__save-status--success');
            setTimeout(() => {
              status.textContent = '';
              status.classList.remove('ac-settings__save-status--success');
            }, 3000);
          }
        }
        else {
          if (status) status.textContent = Drupal.t('Error al guardar.');
        }
      }
      catch (err) {
        if (status) status.textContent = Drupal.t('Error de conexión.');
      }
      finally {
        if (btn) btn.disabled = false;
      }
    });
  }

  // ===========================================================================
  // PLANS
  // ===========================================================================

  function renderPlans(plans) {
    const container = document.getElementById('plans-list');
    if (!container) return;

    if (plans.length === 0) {
      container.innerHTML = `<div class="ac-alerts__empty"><p>${Drupal.t('No hay planes configurados.')}</p></div>`;
      return;
    }

    let html = '';
    plans.forEach(plan => {
      const priceMonthly = plan.price_monthly ? formatCurrency(plan.price_monthly) : '—';
      const priceAnnual = plan.price_annual ? formatCurrency(plan.price_annual) : '—';
      const userLimit = plan.user_limit || '—';
      const storageGb = plan.storage_gb ? plan.storage_gb + ' GB' : '—';
      const statusClass = plan.status ? 'ac-settings__plan-status--active' : 'ac-settings__plan-status--inactive';
      const statusLabel = plan.status ? Drupal.t('Activo') : Drupal.t('Inactivo');

      html += `<div class="ac-settings__plan-card">
        <div class="ac-settings__plan-header">
          <h3 class="ac-settings__plan-name">${escapeHtml(plan.label)}</h3>
          <span class="ac-settings__plan-status ${statusClass}">${statusLabel}</span>
        </div>
        <div class="ac-settings__plan-pricing">
          <div class="ac-settings__plan-price">
            <span class="ac-settings__plan-price-value">${priceMonthly}</span>
            <span class="ac-settings__plan-price-period">/${Drupal.t('mes')}</span>
          </div>
          <div class="ac-settings__plan-price ac-settings__plan-price--annual">
            <span class="ac-settings__plan-price-value">${priceAnnual}</span>
            <span class="ac-settings__plan-price-period">/${Drupal.t('año')}</span>
          </div>
        </div>
        <div class="ac-settings__plan-features">
          <div class="ac-settings__plan-feature">
            <span class="ac-settings__plan-feature-label">${Drupal.t('Usuarios')}</span>
            <span class="ac-settings__plan-feature-value">${escapeHtml(String(userLimit))}</span>
          </div>
          <div class="ac-settings__plan-feature">
            <span class="ac-settings__plan-feature-label">${Drupal.t('Almacenamiento')}</span>
            <span class="ac-settings__plan-feature-value">${escapeHtml(storageGb)}</span>
          </div>
        </div>
      </div>`;
    });

    container.innerHTML = html;
  }

  // ===========================================================================
  // INTEGRATIONS
  // ===========================================================================

  function renderIntegrations(integrations) {
    const container = document.getElementById('integrations-list');
    if (!container) return;

    if (integrations.length === 0) {
      container.innerHTML = `<div class="ac-alerts__empty"><p>${Drupal.t('No hay integraciones configuradas.')}</p></div>`;
      return;
    }

    let html = '';
    integrations.forEach(intg => {
      const statusClass = `ac-settings__intg-status--${intg.status}`;
      const statusLabels = {
        active: Drupal.t('Conectado'),
        not_configured: Drupal.t('Pendiente'),
        error: Drupal.t('Error'),
      };

      html += `<div class="ac-settings__intg-card">
        <div class="ac-settings__intg-icon ac-settings__intg-icon--${intg.id}">
          <span class="ac-settings__intg-icon-inner">${escapeHtml(intg.label.charAt(0))}</span>
        </div>
        <div class="ac-settings__intg-body">
          <h4 class="ac-settings__intg-name">${escapeHtml(intg.label)}</h4>
          <p class="ac-settings__intg-detail">${escapeHtml(intg.details)}</p>
        </div>
        <span class="ac-settings__intg-status ${statusClass}">
          ${statusLabels[intg.status] || escapeHtml(intg.status)}
        </span>
      </div>`;
    });

    container.innerHTML = html;
  }

  // ===========================================================================
  // API KEYS
  // ===========================================================================

  function renderApiKeys(keys) {
    const container = document.getElementById('apikeys-list');
    if (!container) return;

    const activeKeys = keys.filter(k => k.status === 'active');
    const revokedKeys = keys.filter(k => k.status === 'revoked');

    if (keys.length === 0) {
      container.innerHTML = `<div class="ac-alerts__empty"><p>${Drupal.t('No hay API keys creadas.')}</p></div>`;
      return;
    }

    let html = '<table class="ac-settings__apikeys-table"><thead><tr>';
    html += `<th>${Drupal.t('Nombre')}</th>`;
    html += `<th>${Drupal.t('Prefijo')}</th>`;
    html += `<th>${Drupal.t('Alcance')}</th>`;
    html += `<th>${Drupal.t('Creada')}</th>`;
    html += `<th>${Drupal.t('Estado')}</th>`;
    html += `<th>${Drupal.t('Acciones')}</th>`;
    html += '</tr></thead><tbody>';

    keys.forEach(key => {
      const scopeLabels = { read: Drupal.t('Lectura'), write: Drupal.t('Lectura + Escritura'), admin: Drupal.t('Admin') };
      const statusClass = key.status === 'active' ? 'ac-settings__key-status--active' : 'ac-settings__key-status--revoked';

      html += '<tr>';
      html += `<td class="ac-settings__key-label">${escapeHtml(key.label)}</td>`;
      html += `<td><code class="ac-settings__key-prefix">${escapeHtml(key.key_prefix || '')}</code></td>`;
      html += `<td>${scopeLabels[key.scope] || escapeHtml(key.scope)}</td>`;
      html += `<td class="ac-settings__key-date">${escapeHtml(formatDate(key.created))}</td>`;
      html += `<td><span class="${statusClass}">${escapeHtml(key.status)}</span></td>`;
      html += '<td>';
      if (key.status === 'active') {
        html += `<button type="button" class="ac-btn ac-btn--danger ac-btn--xs" data-revoke-key="${escapeHtml(key.id)}">${Drupal.t('Revocar')}</button>`;
      }
      else {
        html += `<span class="ac-settings__key-revoked-label">${Drupal.t('Revocada')}</span>`;
      }
      html += '</td></tr>';
    });

    html += '</tbody></table>';
    container.innerHTML = html;

    // Bind revoke buttons.
    container.querySelectorAll('[data-revoke-key]').forEach(btn => {
      btn.addEventListener('click', () => {
        const keyId = btn.dataset.revokeKey;
        revokeKey(keyId, btn);
      });
    });
  }

  function initApiKeyActions(settings) {
    const btnCreate = document.getElementById('btn-create-apikey');
    const form = document.getElementById('apikey-form');
    const btnConfirm = document.getElementById('btn-confirm-apikey');
    const btnCancel = document.getElementById('btn-cancel-apikey');
    const createdEl = document.getElementById('apikey-created');
    const btnCopy = document.getElementById('btn-copy-apikey');
    const btnDismiss = document.getElementById('btn-dismiss-apikey');
    const apiKeysUrl = settings.settingsApiKeysUrl || '/api/v1/admin/settings/api-keys';

    if (btnCreate && form) {
      btnCreate.addEventListener('click', () => {
        form.hidden = false;
        btnCreate.hidden = true;
      });
    }

    if (btnCancel && form) {
      btnCancel.addEventListener('click', () => {
        form.hidden = true;
        if (btnCreate) btnCreate.hidden = false;
      });
    }

    if (btnConfirm) {
      btnConfirm.addEventListener('click', async () => {
        const label = document.getElementById('apikey-label').value.trim();
        const scope = document.getElementById('apikey-scope').value;

        if (!label) return;

        btnConfirm.disabled = true;

        try {
          const res = await fetch(apiKeysUrl, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ label, scope }),
          });
          const json = await res.json();

          if (json.success && json.data.key) {
            // Show the key once.
            if (createdEl) {
              createdEl.hidden = false;
              const valueEl = document.getElementById('apikey-value');
              if (valueEl) valueEl.textContent = json.data.key;
            }
            if (form) form.hidden = true;

            // Refresh list.
            refreshApiKeys(apiKeysUrl);
          }
        }
        catch (err) {
          // Silent.
        }
        finally {
          btnConfirm.disabled = false;
        }
      });
    }

    if (btnCopy) {
      btnCopy.addEventListener('click', () => {
        const valueEl = document.getElementById('apikey-value');
        if (valueEl && navigator.clipboard) {
          navigator.clipboard.writeText(valueEl.textContent);
        }
      });
    }

    if (btnDismiss && createdEl) {
      btnDismiss.addEventListener('click', () => {
        createdEl.hidden = true;
        if (btnCreate) btnCreate.hidden = false;
        // Clear form.
        const labelInput = document.getElementById('apikey-label');
        if (labelInput) labelInput.value = '';
      });
    }
  }

  async function revokeKey(keyId, btn) {
    const apiKeysUrl = drupalSettings.adminCenter?.settingsApiKeysUrl || '/api/v1/admin/settings/api-keys';
    btn.disabled = true;

    try {
      const res = await fetch(apiKeysUrl + '/' + keyId + '/revoke', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });
      const json = await res.json();
      if (json.success) {
        refreshApiKeys(apiKeysUrl);
      }
    }
    catch (err) {
      btn.disabled = false;
    }
  }

  async function refreshApiKeys(url) {
    try {
      const res = await fetch(url, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });
      const json = await res.json();
      if (json.success) renderApiKeys(json.data);
    }
    catch (err) {
      // Silent.
    }
  }

  // ===========================================================================
  // HELPERS
  // ===========================================================================

  function formatCurrency(val) {
    return new Intl.NumberFormat('es-ES', {
      style: 'currency', currency: 'EUR',
      minimumFractionDigits: 0, maximumFractionDigits: 2,
    }).format(val);
  }

  function formatDate(isoStr) {
    if (!isoStr) return '—';
    try {
      return new Date(isoStr).toLocaleDateString('es-ES', {
        day: '2-digit', month: 'short', year: 'numeric',
      });
    }
    catch (e) {
      return isoStr;
    }
  }

  function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

})(Drupal, drupalSettings, once);

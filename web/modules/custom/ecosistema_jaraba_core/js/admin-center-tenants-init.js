/**
 * @file
 * Admin Center Tenants — DataTable initializer.
 *
 * Configures the declarative DataTable on /admin/jaraba/center/tenants
 * with columns, filters, and row actions from drupalSettings.
 *
 * F6 — Doc 181 / Spec f104 §FASE 2.
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.adminCenterTenantsInit = {
    attach(context) {
      once('ac-tenants-init', '#tenants-datatable', context).forEach(el => {
        const settings = drupalSettings.adminCenter || {};

        // Set data attributes before DataTable auto-init.
        el.dataset.datatableUrl = settings.tenantsApiUrl || '/api/v1/admin/tenants';
        el.dataset.datatableExport = settings.tenantsExportUrl || '/api/v1/admin/tenants/export';

        // Column definitions.
        el.dataset.datatableColumns = JSON.stringify([
          {
            key: 'label',
            label: Drupal.t('Nombre'),
            sortable: true,
            type: 'link',
            urlKey: 'url',
          },
          {
            key: 'status',
            label: Drupal.t('Estado'),
            sortable: true,
            type: 'status',
          },
          {
            key: 'plan',
            label: Drupal.t('Plan'),
            sortable: false,
          },
          {
            key: 'members',
            label: Drupal.t('Miembros'),
            sortable: false,
          },
          {
            key: 'created',
            label: Drupal.t('Creado'),
            sortable: true,
          },
        ]);

        // Row actions.
        el.dataset.datatableActions = JSON.stringify([
          {
            action: 'view',
            label: Drupal.t('Ver'),
            slidePanel: 'tenant-detail',
            slidePanelUrl: (settings.tenantsDetailUrl || '/api/v1/admin/tenants/{id}')
              .replace('{id}', '{id}'),
            slidePanelTitle: Drupal.t('Detalle: {label}'),
          },
          {
            action: 'impersonate',
            label: Drupal.t('Impersonar'),
          },
        ]);

        // Now manually trigger DataTable init since data-attrs are set post-load.
        if (Drupal.AdminCenterDataTable) {
          el._datatable = new Drupal.AdminCenterDataTable(el);

          // Add filter chips for status.
          el._datatable.setFilters('status', [
            { value: 'active', label: Drupal.t('Activos') },
            { value: 'trial', label: Drupal.t('En Trial') },
            { value: 'suspended', label: Drupal.t('Suspendidos') },
          ]);
        }

        // Listen for row actions.
        el.addEventListener('datatable:action', function (e) {
          const { action, row } = e.detail;

          if (action === 'impersonate' && row) {
            if (!confirm(Drupal.t('¿Impersonar como admin de "@tenant"?', { '@tenant': row.label }))) {
              return;
            }

            fetch(`/api/v1/admin/tenants/${row.id}/impersonate`, {
              method: 'POST',
              headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json',
              },
            })
            .then(res => res.json())
            .then(json => {
              if (json.success && json.data && json.data.redirect) {
                window.location.href = json.data.redirect;
              }
              else {
                alert(json.error?.message || Drupal.t('Error al impersonar.'));
              }
            })
            .catch(() => {
              alert(Drupal.t('Error de red al impersonar.'));
            });
          }

          if (action === 'view' && row) {
            // Open slide-panel with HTML from the panel route.
            if (typeof Drupal.behaviors.slidePanel !== 'undefined') {
              Drupal.behaviors.slidePanel.open({
                id: 'tenant-detail',
                url: `/admin/jaraba/center/tenants/${row.id}/panel`,
                title: Drupal.t('Detalle: @name', { '@name': row.label }),
              });
            }
          }
        });
      });
    },
  };

})(Drupal, drupalSettings, once);

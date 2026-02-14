/**
 * @file
 * Admin Center Users — DataTable initializer.
 *
 * Configures the declarative DataTable on /admin/jaraba/center/users
 * with columns, filters, and row actions.
 *
 * F6 — Doc 181 / Spec f104 §FASE 3.
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.adminCenterUsersInit = {
    attach(context) {
      once('ac-users-init', '#users-datatable', context).forEach(el => {
        const settings = drupalSettings.adminCenter || {};

        // Set data attributes before DataTable init.
        el.dataset.datatableUrl = settings.usersApiUrl || '/api/v1/admin/users';

        // Column definitions.
        el.dataset.datatableColumns = JSON.stringify([
          {
            key: 'name',
            label: Drupal.t('Usuario'),
            sortable: true,
            type: 'link',
            urlKey: 'url',
          },
          {
            key: 'email',
            label: Drupal.t('Email'),
            sortable: false,
          },
          {
            key: 'tenant',
            label: Drupal.t('Tenant'),
            sortable: false,
          },
          {
            key: 'role_label',
            label: Drupal.t('Rol'),
            sortable: false,
            type: 'status',
          },
          {
            key: 'status',
            label: Drupal.t('Estado'),
            sortable: true,
            type: 'status',
          },
          {
            key: 'last_access',
            label: Drupal.t('Ultimo Acceso'),
            sortable: true,
          },
        ]);

        // Row actions.
        el.dataset.datatableActions = JSON.stringify([
          {
            action: 'view',
            label: Drupal.t('Ver'),
          },
          {
            action: 'force_logout',
            label: Drupal.t('Cerrar Sesion'),
          },
        ]);

        // Manually trigger DataTable init.
        if (Drupal.AdminCenterDataTable) {
          el._datatable = new Drupal.AdminCenterDataTable(el);

          // Filter chips: status.
          el._datatable.setFilters('status', [
            { value: '1', label: Drupal.t('Activos') },
            { value: '0', label: Drupal.t('Bloqueados') },
          ]);
        }

        // Listen for row actions.
        el.addEventListener('datatable:action', function (e) {
          const { action, row } = e.detail;

          if (action === 'view' && row) {
            if (typeof Drupal.behaviors.slidePanel !== 'undefined') {
              Drupal.behaviors.slidePanel.open({
                id: 'user-detail',
                url: `/admin/jaraba/center/users/${row.id}/panel`,
                title: Drupal.t('Usuario: @name', { '@name': row.name }),
              });
            }
          }

          if (action === 'force_logout' && row) {
            if (!confirm(Drupal.t('¿Cerrar todas las sesiones de "@user"?', { '@user': row.name }))) {
              return;
            }

            fetch(`/api/v1/admin/users/${row.id}/sessions`, {
              method: 'DELETE',
              headers: {
                'X-Requested-With': 'XMLHttpRequest',
              },
            })
            .then(res => res.json())
            .then(json => {
              if (json.success) {
                const count = json.data?.sessions_deleted || 0;
                alert(Drupal.t('@count sesiones cerradas.', { '@count': count }));
              }
              else {
                alert(json.error?.message || Drupal.t('Error al cerrar sesiones.'));
              }
            })
            .catch(() => {
              alert(Drupal.t('Error de red.'));
            });
          }
        });
      });
    },
  };

})(Drupal, drupalSettings, once);

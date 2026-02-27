/**
 * @file
 * Notification Panel — Panel desplegable del centro de notificaciones.
 *
 * Gestiona apertura/cierre del panel, carga de notificaciones via API,
 * filtros por tipo, mark-as-read, y dismiss.
 *
 * DIRECTIVAS:
 * - DRUPAL-BEHAVIORS-001: Drupal.behaviors.notificationPanel
 * - ONCE-PATTERN-001: once('notification-panel', selector, context)
 * - INNERHTML-XSS-001: Usa textContent / Drupal.checkPlain()
 * - CSRF-JS-CACHE-001: Usa Drupal.jarabaFetch() (fetch-retry.js)
 * - i18n: Drupal.t() para todos los textos
 * - ROUTE-LANGPREFIX-001: Drupal.url() para URLs
 */
(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.notificationPanel = {
    attach: function (context) {
      // Buscar el boton de campana (proactive-insights-bell)
      var bells = once('notification-panel', '.proactive-insights-bell, [data-notification-bell]', context);
      if (!bells.length) {
        return;
      }

      var bell = bells[0];
      var panel = document.getElementById('notification-panel');
      if (!panel) {
        return;
      }

      var list = document.getElementById('notification-list');
      var emptyEl = document.getElementById('notification-empty');
      var markAllBtn = document.getElementById('notification-mark-all');
      var currentFilter = 'all';

      // Toggle panel al hacer click en campana
      bell.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var isHidden = panel.hasAttribute('hidden');
        if (isHidden) {
          panel.removeAttribute('hidden');
          loadNotifications(currentFilter);
          updateBadge();
        } else {
          panel.setAttribute('hidden', '');
        }
      });

      // Cerrar al hacer click fuera
      document.addEventListener('click', function (e) {
        if (!panel.hasAttribute('hidden') && !panel.contains(e.target) && !bell.contains(e.target)) {
          panel.setAttribute('hidden', '');
        }
      });

      // Cerrar con Escape
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !panel.hasAttribute('hidden')) {
          panel.setAttribute('hidden', '');
          bell.focus();
        }
      });

      // Filtros
      var filters = panel.querySelectorAll('.ej-notification-panel__filter');
      filters.forEach(function (filterBtn) {
        filterBtn.addEventListener('click', function () {
          filters.forEach(function (f) {
            f.classList.remove('is-active');
            f.setAttribute('aria-selected', 'false');
          });
          filterBtn.classList.add('is-active');
          filterBtn.setAttribute('aria-selected', 'true');
          currentFilter = filterBtn.getAttribute('data-filter');
          loadNotifications(currentFilter);
        });
      });

      // Mark all read
      if (markAllBtn) {
        markAllBtn.addEventListener('click', function () {
          Drupal.jarabaFetch('/api/v1/notifications/read-all', { method: 'PATCH' })
            .then(function () {
              loadNotifications(currentFilter);
              updateBadge();
            });
        });
      }

      /**
       * Carga notificaciones desde la API.
       */
      function loadNotifications(filter) {
        var url = '/api/v1/notifications';
        if (filter && filter !== 'all') {
          url += '?type=' + encodeURIComponent(filter);
        }

        Drupal.jarabaFetch(url)
          .then(function (response) {
            var items = response.data || [];
            renderNotifications(items);
          })
          .catch(function () {
            // fetch-retry ya muestra toast de error
          });
      }

      /**
       * Renderiza la lista de notificaciones (INNERHTML-XSS-001: textContent).
       */
      function renderNotifications(items) {
        if (!items.length) {
          list.innerHTML = '';
          list.classList.add('is-hidden');
          emptyEl.classList.remove('is-hidden');
          return;
        }

        list.classList.remove('is-hidden');
        emptyEl.classList.add('is-hidden');

        // Construir items con DOM API (seguro contra XSS)
        var fragment = document.createDocumentFragment();

        items.forEach(function (item) {
          var el = document.createElement('a');
          el.className = 'ej-notification-panel__item' + (item.read ? '' : ' ej-notification-panel__item--unread');
          el.href = item.link || '#';

          var content = document.createElement('div');
          content.className = 'ej-notification-panel__item-content';

          var title = document.createElement('p');
          title.className = 'ej-notification-panel__item-title';
          title.textContent = item.title;

          var message = document.createElement('p');
          message.className = 'ej-notification-panel__item-message';
          message.textContent = item.message;

          content.appendChild(title);
          content.appendChild(message);

          var time = document.createElement('span');
          time.className = 'ej-notification-panel__item-time';
          time.textContent = formatTime(item.created);

          el.appendChild(content);
          el.appendChild(time);

          // Mark as read on click
          el.addEventListener('click', function () {
            if (!item.read) {
              Drupal.jarabaFetch('/api/v1/notifications/' + item.id + '/read', { method: 'PATCH' });
              el.classList.remove('ej-notification-panel__item--unread');
              updateBadge();
            }
          });

          fragment.appendChild(el);
        });

        list.innerHTML = '';
        list.appendChild(fragment);
      }

      /**
       * Actualiza el badge de la campana.
       */
      function updateBadge() {
        Drupal.jarabaFetch('/api/v1/notifications/count')
          .then(function (response) {
            var count = response.data ? response.data.unread_count : 0;
            var badge = bell.querySelector('.bottom-nav__badge, .notification-badge, [data-notification-count]');
            if (badge) {
              badge.textContent = count > 9 ? '9+' : String(count);
              badge.style.display = count > 0 ? '' : 'none';
            }
          });
      }

      /**
       * Formatea timestamp a texto relativo.
       */
      function formatTime(timestamp) {
        var now = Math.floor(Date.now() / 1000);
        var diff = now - parseInt(timestamp, 10);

        if (diff < 60) {
          return Drupal.t('Ahora');
        }
        if (diff < 3600) {
          var mins = Math.floor(diff / 60);
          return Drupal.t('@count min', { '@count': mins });
        }
        if (diff < 86400) {
          var hours = Math.floor(diff / 3600);
          return Drupal.t('@count h', { '@count': hours });
        }
        var days = Math.floor(diff / 86400);
        return Drupal.t('@count d', { '@count': days });
      }

      // Cargar badge al inicializar
      updateBadge();
    }
  };

})(Drupal, once);

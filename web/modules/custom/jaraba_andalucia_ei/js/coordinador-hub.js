/**
 * @file
 * Hub Coordinador Andalucía +ei — JS Behavior.
 *
 * Tabs, data loading via API, CRUD actions.
 * ROUTE-LANGPREFIX-001: URLs via drupalSettings.
 * CSRF-JS-CACHE-001: Token from /session/token.
 * INNERHTML-XSS-001: Drupal.checkPlain() for all API data.
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  var csrfToken = null;
  var hubConfig = (drupalSettings.jarabaAndaluciaEi || {}).hub || {};
  var apiUrls = hubConfig.apiUrls || {};

  /**
   * Fetches CSRF token and caches it (CSRF-JS-CACHE-001).
   */
  function getCsrfToken() {
    if (csrfToken) {
      return Promise.resolve(csrfToken);
    }
    return fetch(Drupal.url('session/token'))
      .then(function (r) { return r.text(); })
      .then(function (token) {
        csrfToken = token;
        return token;
      });
  }

  /**
   * Safe fetch wrapper for GET JSON.
   */
  function apiGet(url) {
    return fetch(url, {
      headers: { 'Accept': 'application/json' },
      credentials: 'same-origin'
    }).then(function (r) { return r.json(); });
  }

  /**
   * Safe fetch wrapper for POST JSON with CSRF.
   */
  function apiPost(url, data) {
    return getCsrfToken().then(function (token) {
      return fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-Token': token
        },
        credentials: 'same-origin',
        body: JSON.stringify(data || {})
      });
    }).then(function (r) { return r.json(); });
  }

  /**
   * Shows a notification in the ARIA live region.
   */
  function notify(container, message, type) {
    var el = container.querySelector('[data-notifications]');
    if (!el) return;
    var cls = type === 'error' ? 'hub-coordinador__notification--error' : 'hub-coordinador__notification--success';
    el.innerHTML = '<div class="hub-coordinador__notification ' + cls + '">' + Drupal.checkPlain(message) + '</div>';
    setTimeout(function () { el.innerHTML = ''; }, 5000);
  }

  /**
   * Formats a Unix timestamp to locale date string.
   */
  function formatDate(timestamp) {
    if (!timestamp) return '-';
    var d = new Date(parseInt(timestamp, 10) * 1000);
    return d.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
  }

  /**
   * Replaces __ID__ placeholder in URL template.
   */
  function urlWithId(template, id) {
    return template ? template.replace('__ID__', id) : '';
  }

  Drupal.behaviors.coordinadorHub = {
    attach: function (context) {
      var hubs = once('coordinador-hub', '[data-coordinador-hub]', context);
      if (!hubs.length) return;

      hubs.forEach(function (container) {
        initTabs(container);
        loadSolicitudes(container);
      });
    }
  };

  /**
   * Initializes tab switching with keyboard navigation.
   */
  function initTabs(container) {
    var tabs = container.querySelectorAll('[role="tab"]');
    var panels = container.querySelectorAll('[role="tabpanel"]');

    tabs.forEach(function (tab) {
      tab.addEventListener('click', function () {
        var target = tab.getAttribute('data-tab');

        tabs.forEach(function (t) {
          t.classList.remove('hub-coordinador__tab--active');
          t.setAttribute('aria-selected', 'false');
        });
        tab.classList.add('hub-coordinador__tab--active');
        tab.setAttribute('aria-selected', 'true');

        panels.forEach(function (p) {
          var panelName = p.getAttribute('data-panel');
          if (panelName === target) {
            p.classList.add('hub-coordinador__panel--active');
            p.removeAttribute('hidden');
          } else {
            p.classList.remove('hub-coordinador__panel--active');
            p.setAttribute('hidden', '');
          }
        });

        // Lazy load panel data.
        if (target === 'solicitudes') loadSolicitudes(container);
        if (target === 'participantes') loadParticipants(container);
        if (target === 'sesiones') loadSessions(container);
      });

      // Keyboard navigation (arrows).
      tab.addEventListener('keydown', function (e) {
        var tabArray = Array.from(tabs);
        var idx = tabArray.indexOf(tab);
        if (e.key === 'ArrowRight' && idx < tabArray.length - 1) {
          tabArray[idx + 1].focus();
          tabArray[idx + 1].click();
        } else if (e.key === 'ArrowLeft' && idx > 0) {
          tabArray[idx - 1].focus();
          tabArray[idx - 1].click();
        }
      });
    });
  }

  /**
   * Loads solicitudes into the table.
   */
  function loadSolicitudes(container, estado, offset) {
    if (!apiUrls.solicitudes) return;
    estado = estado || '';
    offset = offset || 0;

    var url = apiUrls.solicitudes + '?_format=json&limit=20&offset=' + offset;
    if (estado) url += '&estado=' + encodeURIComponent(estado);

    var tbody = container.querySelector('[data-tbody="solicitudes"]');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="6" class="hub-coordinador__loading">' + Drupal.t('Cargando...') + '</td></tr>';

    apiGet(url).then(function (res) {
      if (!res.success || !res.data || !res.data.items) {
        tbody.innerHTML = '<tr><td colspan="6">' + Drupal.t('Error al cargar solicitudes.') + '</td></tr>';
        return;
      }

      var items = res.data.items;
      if (items.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6">' + Drupal.t('No hay solicitudes.') + '</td></tr>';
        return;
      }

      var html = '';
      items.forEach(function (item) {
        html += '<tr>';
        html += '<td>' + Drupal.checkPlain(item.nombre) + '</td>';
        html += '<td>' + Drupal.checkPlain(item.email) + '</td>';
        html += '<td>' + Drupal.checkPlain(item.provincia) + '</td>';
        html += '<td><span class="hub-coordinador__badge hub-coordinador__badge--' + Drupal.checkPlain(item.estado) + '">' + Drupal.checkPlain(item.estado) + '</span></td>';
        html += '<td>' + formatDate(item.created) + '</td>';
        html += '<td class="hub-coordinador__actions">';
        if (item.estado === 'pendiente' || item.estado === 'contactado') {
          html += '<button class="hub-coordinador__action-btn hub-coordinador__action-btn--approve" data-action="approve" data-id="' + item.id + '">' + Drupal.t('Aprobar') + '</button>';
          html += '<button class="hub-coordinador__action-btn hub-coordinador__action-btn--reject" data-action="reject" data-id="' + item.id + '">' + Drupal.t('Rechazar') + '</button>';
        }
        html += '</td>';
        html += '</tr>';
      });
      tbody.innerHTML = html;

      // Bind action buttons.
      bindSolicitudActions(container);
    }).catch(function () {
      tbody.innerHTML = '<tr><td colspan="6">' + Drupal.t('Error de conexión.') + '</td></tr>';
    });

    // Bind filter.
    var filterSelect = container.querySelector('[data-filter="estado"]');
    if (filterSelect && !filterSelect.dataset.bound) {
      filterSelect.dataset.bound = 'true';
      filterSelect.addEventListener('change', function () {
        loadSolicitudes(container, filterSelect.value);
      });
    }
  }

  /**
   * Binds approve/reject button actions.
   */
  function bindSolicitudActions(container) {
    container.querySelectorAll('[data-action="approve"]').forEach(function (btn) {
      if (btn.dataset.bound) return;
      btn.dataset.bound = 'true';
      btn.addEventListener('click', function () {
        var id = btn.dataset.id;
        if (!apiUrls.solicitudApprove) return;
        if (!confirm(Drupal.t('¿Aprobar esta solicitud y crear participante?'))) return;

        btn.disabled = true;
        apiPost(urlWithId(apiUrls.solicitudApprove, id)).then(function (res) {
          notify(container, res.message, res.success ? 'success' : 'error');
          if (res.success) loadSolicitudes(container);
        }).catch(function () {
          notify(container, Drupal.t('Error de conexión.'), 'error');
          btn.disabled = false;
        });
      });
    });

    container.querySelectorAll('[data-action="reject"]').forEach(function (btn) {
      if (btn.dataset.bound) return;
      btn.dataset.bound = 'true';
      btn.addEventListener('click', function () {
        var id = btn.dataset.id;
        if (!apiUrls.solicitudReject) return;
        var reason = prompt(Drupal.t('Motivo del rechazo:'));
        if (reason === null) return;

        btn.disabled = true;
        apiPost(urlWithId(apiUrls.solicitudReject, id), { reason: reason }).then(function (res) {
          notify(container, res.message, res.success ? 'success' : 'error');
          if (res.success) loadSolicitudes(container);
        }).catch(function () {
          notify(container, Drupal.t('Error de conexión.'), 'error');
          btn.disabled = false;
        });
      });
    });
  }

  /**
   * Loads participants into the table.
   */
  function loadParticipants(container, fase, search, offset) {
    if (!apiUrls.participants) return;
    fase = fase || '';
    search = search || '';
    offset = offset || 0;

    var url = apiUrls.participants + '?_format=json&limit=20&offset=' + offset;
    if (fase) url += '&fase=' + encodeURIComponent(fase);
    if (search) url += '&search=' + encodeURIComponent(search);

    var tbody = container.querySelector('[data-tbody="participantes"]');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="5" class="hub-coordinador__loading">' + Drupal.t('Cargando...') + '</td></tr>';

    apiGet(url).then(function (res) {
      if (!res.success || !res.data || !res.data.items) {
        tbody.innerHTML = '<tr><td colspan="5">' + Drupal.t('Error al cargar participantes.') + '</td></tr>';
        return;
      }

      var items = res.data.items;
      if (items.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5">' + Drupal.t('No hay participantes.') + '</td></tr>';
        return;
      }

      var html = '';
      items.forEach(function (item) {
        html += '<tr>';
        html += '<td>' + Drupal.checkPlain(item.dni_nie) + '</td>';
        html += '<td>' + Drupal.checkPlain(item.nombre) + '</td>';
        html += '<td><span class="hub-coordinador__badge hub-coordinador__badge--' + Drupal.checkPlain(item.fase_actual) + '">' + Drupal.checkPlain(item.fase_actual) + '</span></td>';
        html += '<td>' + formatDate(item.changed) + '</td>';
        html += '<td class="hub-coordinador__actions">';
        if (item.fase_actual !== 'baja') {
          html += '<button class="hub-coordinador__action-btn" data-action="change-phase" data-id="' + item.id + '">' + Drupal.t('Cambiar fase') + '</button>';
        }
        html += '</td>';
        html += '</tr>';
      });
      tbody.innerHTML = html;

      bindParticipantActions(container);
    }).catch(function () {
      tbody.innerHTML = '<tr><td colspan="5">' + Drupal.t('Error de conexión.') + '</td></tr>';
    });

    // Bind filters.
    var faseFilter = container.querySelector('[data-filter="fase"]');
    if (faseFilter && !faseFilter.dataset.bound) {
      faseFilter.dataset.bound = 'true';
      faseFilter.addEventListener('change', function () {
        var searchInput = container.querySelector('[data-filter="search"]');
        loadParticipants(container, faseFilter.value, searchInput ? searchInput.value : '');
      });
    }

    var searchInput = container.querySelector('[data-filter="search"]');
    if (searchInput && !searchInput.dataset.bound) {
      searchInput.dataset.bound = 'true';
      var debounceTimer;
      searchInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () {
          loadParticipants(container, faseFilter ? faseFilter.value : '', searchInput.value);
        }, 400);
      });
    }
  }

  /**
   * Binds participant phase change actions.
   */
  function bindParticipantActions(container) {
    container.querySelectorAll('[data-action="change-phase"]').forEach(function (btn) {
      if (btn.dataset.bound) return;
      btn.dataset.bound = 'true';
      btn.addEventListener('click', function () {
        var id = btn.dataset.id;
        if (!apiUrls.changePhase) return;

        var phases = hubConfig.phases || ['atencion', 'insercion', 'baja'];
        var newPhase = prompt(Drupal.t('Nueva fase (@phases):', { '@phases': phases.join(', ') }));
        if (!newPhase || phases.indexOf(newPhase) === -1) {
          if (newPhase !== null) notify(container, Drupal.t('Fase no válida.'), 'error');
          return;
        }

        btn.disabled = true;
        apiPost(urlWithId(apiUrls.changePhase, id), { phase: newPhase }).then(function (res) {
          notify(container, res.message, res.success ? 'success' : 'error');
          if (res.success) loadParticipants(container);
        }).catch(function () {
          notify(container, Drupal.t('Error de conexión.'), 'error');
          btn.disabled = false;
        });
      });
    });
  }

  /**
   * Loads sessions into the table.
   */
  function loadSessions(container) {
    if (!apiUrls.sessions) return;

    var tbody = container.querySelector('[data-tbody="sesiones"]');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="4" class="hub-coordinador__loading">' + Drupal.t('Cargando...') + '</td></tr>';

    apiGet(apiUrls.sessions + '?_format=json&days=30').then(function (res) {
      if (!res.success || !res.data || !res.data.sessions) {
        tbody.innerHTML = '<tr><td colspan="4">' + Drupal.t('Error al cargar sesiones.') + '</td></tr>';
        return;
      }

      var sessions = res.data.sessions;
      if (sessions.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4">' + Drupal.t('No hay sesiones próximas.') + '</td></tr>';
        return;
      }

      var html = '';
      sessions.forEach(function (s) {
        html += '<tr>';
        html += '<td>#' + s.session_number + '</td>';
        html += '<td>' + Drupal.checkPlain(s.mentor_name) + '</td>';
        html += '<td>' + Drupal.checkPlain(s.mentee_name) + '</td>';
        html += '<td><span class="hub-coordinador__badge hub-coordinador__badge--' + Drupal.checkPlain(s.status) + '">' + Drupal.checkPlain(s.status) + '</span></td>';
        html += '</tr>';
      });
      tbody.innerHTML = html;
    }).catch(function () {
      tbody.innerHTML = '<tr><td colspan="4">' + Drupal.t('Error de conexión.') + '</td></tr>';
    });
  }

})(Drupal, drupalSettings, once);

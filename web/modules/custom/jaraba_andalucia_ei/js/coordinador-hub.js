/**
 * @file
 * Hub Coordinador Andalucia +ei — JS Behavior (Clase Mundial).
 *
 * Tabs, data loading via API, CRUD actions, modal dialogs, pagination.
 * ROUTE-LANGPREFIX-001: URLs via drupalSettings.
 * CSRF-JS-CACHE-001: Token from /session/token.
 * INNERHTML-XSS-001: Drupal.checkPlain() for all API data.
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  var csrfToken = null;
  var csrfTokenTimestamp = 0;
  var CSRF_TOKEN_TTL_MS = 3600000; // 1h refresh — Drupal session tokens last ~24h.
  var hubConfig = (drupalSettings.jarabaAndaluciaEi || {}).hub || {};
  var apiUrls = hubConfig.apiUrls || {};
  var phaseLabels = hubConfig.phaseLabels || {};
  var ITEMS_PER_PAGE = 20;
  var AUTO_REFRESH_MS = 60000;
  var autoRefreshTimer = null;

  // ─── API helpers ─────────────────────────────────────────────────────────

  /**
   * CSRF-JS-CACHE-001: Token cached with TTL. Refresh after 1h to avoid
   * stale token failures on long-lived dashboard sessions.
   */
  function getCsrfToken() {
    var now = Date.now();
    if (csrfToken && (now - csrfTokenTimestamp) < CSRF_TOKEN_TTL_MS) {
      return Promise.resolve(csrfToken);
    }
    return fetch(Drupal.url('session/token'))
      .then(function (r) { return r.text(); })
      .then(function (token) {
        csrfToken = token;
        csrfTokenTimestamp = Date.now();
        return token;
      });
  }

  function apiGet(url) {
    return fetch(url, {
      headers: { 'Accept': 'application/json' },
      credentials: 'same-origin'
    }).then(function (r) { return r.json(); });
  }

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

  // ─── UI helpers ──────────────────────────────────────────────────────────

  function notify(container, message, type) {
    var el = container.querySelector('[data-notifications]');
    if (!el) { return; }
    var cls = type === 'error'
      ? 'hub-coordinador__notification--error'
      : 'hub-coordinador__notification--success';
    el.innerHTML = '<div class="hub-coordinador__notification ' + cls + '">'
      + Drupal.checkPlain(message) + '</div>';
    setTimeout(function () { el.innerHTML = ''; }, 5000);
  }

  function formatDate(timestamp) {
    if (!timestamp) { return '-'; }
    var d = new Date(parseInt(timestamp, 10) * 1000);
    return d.toLocaleDateString('es-ES', {
      day: '2-digit', month: '2-digit', year: 'numeric'
    });
  }

  function formatDateTime(timestamp) {
    if (!timestamp) { return '-'; }
    var d = new Date(parseInt(timestamp, 10) * 1000);
    return d.toLocaleDateString('es-ES', {
      day: '2-digit', month: '2-digit', year: 'numeric',
      hour: '2-digit', minute: '2-digit'
    });
  }

  function urlWithId(template, id) {
    return template ? template.replace('__ID__', id) : '';
  }

  function getPhaseLabel(phase) {
    return phaseLabels[phase] || phase;
  }

  // ─── Modal dialog ────────────────────────────────────────────────────────

  var activeModalResolve = null;

  /**
   * Opens modal with sanitized body HTML.
   *
   * INNERHTML-XSS-001: bodyHtml is constructed internally from Drupal.t()
   * and Drupal.checkPlain() — never from raw API response data.
   * Focus trap: Tab/Shift+Tab cycle within modal focusable elements.
   */
  function openModal(container, title, bodyHtml) {
    var modal = container.querySelector('[data-modal]');
    if (!modal) { return Promise.reject(); }

    modal.querySelector('[data-modal-title]').textContent = title;
    modal.querySelector('[data-modal-body]').innerHTML = bodyHtml;
    modal.removeAttribute('hidden');

    // Focus first interactive element or close button.
    var firstFocusable = modal.querySelector('select, textarea, input, button');
    if (firstFocusable) {
      setTimeout(function () { firstFocusable.focus(); }, 50);
    }

    // Trap focus inside modal (WCAG 2.1 AA — keyboard trap).
    document.body.style.overflow = 'hidden';
    modal._focusTrapHandler = function (e) {
      if (e.key !== 'Tab') { return; }
      var focusable = modal.querySelectorAll(
        'button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
      );
      if (focusable.length === 0) { return; }
      var first = focusable[0];
      var last = focusable[focusable.length - 1];
      if (e.shiftKey && document.activeElement === first) {
        e.preventDefault();
        last.focus();
      } else if (!e.shiftKey && document.activeElement === last) {
        e.preventDefault();
        first.focus();
      }
    };
    modal.addEventListener('keydown', modal._focusTrapHandler);

    return new Promise(function (resolve) {
      activeModalResolve = resolve;
    });
  }

  var modalTriggerElement = null;

  function closeModal(container, result) {
    var modal = container.querySelector('[data-modal]');
    if (modal) {
      // Remove focus trap handler.
      if (modal._focusTrapHandler) {
        modal.removeEventListener('keydown', modal._focusTrapHandler);
        modal._focusTrapHandler = null;
      }
      modal.setAttribute('hidden', '');
    }
    document.body.style.overflow = '';
    // Restore focus to trigger element (WCAG 2.1 AA).
    if (modalTriggerElement && modalTriggerElement.focus) {
      modalTriggerElement.focus();
      modalTriggerElement = null;
    }
    if (activeModalResolve) {
      activeModalResolve(result || null);
      activeModalResolve = null;
    }
  }

  function initModal(container) {
    var modal = container.querySelector('[data-modal]');
    if (!modal) { return; }

    // Close buttons.
    modal.querySelectorAll('[data-modal-close]').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        closeModal(container, null);
      });
    });

    // Confirm button.
    var confirmBtn = modal.querySelector('[data-modal-confirm]');
    if (confirmBtn) {
      confirmBtn.addEventListener('click', function (e) {
        e.preventDefault();
        // Collect form data from modal body.
        var body = modal.querySelector('[data-modal-body]');
        var data = {};
        body.querySelectorAll('select, textarea, input').forEach(function (el) {
          if (el.name) {
            data[el.name] = el.value;
          }
        });
        closeModal(container, data);
      });
    }

    // Escape key.
    modal.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        closeModal(container, null);
      }
    });
  }

  // ─── Pagination ──────────────────────────────────────────────────────────

  function renderPagination(container, panelName, total, offset, loadFn) {
    var paginationEl = container.querySelector('[data-pagination="' + panelName + '"]');
    if (!paginationEl) { return; }

    var totalPages = Math.ceil(total / ITEMS_PER_PAGE);
    var currentPage = Math.floor(offset / ITEMS_PER_PAGE) + 1;

    if (totalPages <= 1) {
      paginationEl.innerHTML = '';
      return;
    }

    var html = '';
    html += '<button class="hub-coordinador__page-btn" data-page-action="prev"'
      + (currentPage <= 1 ? ' disabled' : '') + '>'
      + Drupal.t('Anterior') + '</button>';
    html += '<span class="hub-coordinador__page-info">'
      + Drupal.t('Pagina @current de @total', {
        '@current': currentPage,
        '@total': totalPages
      }) + '</span>';
    html += '<button class="hub-coordinador__page-btn" data-page-action="next"'
      + (currentPage >= totalPages ? ' disabled' : '') + '>'
      + Drupal.t('Siguiente') + '</button>';

    paginationEl.innerHTML = html;

    // Bind pagination buttons.
    paginationEl.querySelector('[data-page-action="prev"]').addEventListener('click', function () {
      if (currentPage > 1) {
        loadFn((currentPage - 2) * ITEMS_PER_PAGE);
      }
    });
    paginationEl.querySelector('[data-page-action="next"]').addEventListener('click', function () {
      if (currentPage < totalPages) {
        loadFn(currentPage * ITEMS_PER_PAGE);
      }
    });
  }

  // ─── Skeleton HTML ───────────────────────────────────────────────────────

  function skeletonRow(cols) {
    return '<tr><td colspan="' + cols + '" class="hub-coordinador__loading">'
      + '<div class="hub-coordinador__skeleton">'
      + '<div class="hub-coordinador__skeleton-line"></div>'
      + '<div class="hub-coordinador__skeleton-line hub-coordinador__skeleton-line--short"></div>'
      + '</div></td></tr>';
  }

  function emptyRow(cols, message) {
    return '<tr><td colspan="' + cols + '" class="hub-coordinador__empty">'
      + Drupal.checkPlain(message) + '</td></tr>';
  }

  // ─── Main behavior ──────────────────────────────────────────────────────

  Drupal.behaviors.coordinadorHub = {
    attach: function (context) {
      var hubs = once('coordinador-hub', '[data-coordinador-hub]', context);
      if (!hubs.length) { return; }

      hubs.forEach(function (container) {
        initModal(container);
        initTabs(container);
        initStickyTabs(container);
        initKpiNavigation(container);
        initCsvExport(container);
        loadSolicitudes(container, '', 0);
        handleHashNavigation(container);
        startAutoRefresh(container);
      });
    },
    detach: function (context, settings, trigger) {
      if (trigger !== 'unload') { return; }
      // Stop auto-refresh when page unloads to avoid memory leak.
      if (autoRefreshTimer) {
        clearInterval(autoRefreshTimer);
        autoRefreshTimer = null;
      }
    }
  };

  // ─── Auto-refresh KPIs ───────────────────────────────────────────────────

  function startAutoRefresh(container) {
    if (autoRefreshTimer) {
      clearInterval(autoRefreshTimer);
    }
    autoRefreshTimer = setInterval(function () {
      refreshKpis(container);
    }, AUTO_REFRESH_MS);
  }

  // Store previous KPI values for trend indicators.
  var previousKpis = {};

  function refreshKpis(container) {
    if (!apiUrls.kpis) { return; }

    apiGet(apiUrls.kpis + '?_format=json').then(function (res) {
      if (!res.success || !res.data) { return; }
      var data = res.data;

      var mapping = {
        active_participants: data.active_participants,
        pending_solicitudes: data.pending_solicitudes,
        insertion_rate: data.insertion_rate + '%',
        completed_sessions: data.completed_sessions,
        total_participants: data.total_participants
      };

      // Numeric values for trend comparison.
      var numericValues = {
        active_participants: data.active_participants,
        pending_solicitudes: data.pending_solicitudes,
        insertion_rate: data.insertion_rate,
        completed_sessions: data.completed_sessions,
        total_participants: data.total_participants
      };

      Object.keys(mapping).forEach(function (key) {
        var el = container.querySelector('[data-kpi="' + key + '"]');
        if (el && el.textContent !== String(mapping[key])) {
          el.textContent = mapping[key];
          el.classList.add('hub-coordinador__kpi-value--updated');
          setTimeout(function () {
            el.classList.remove('hub-coordinador__kpi-value--updated');
          }, 1500);
        }

        // Update trend indicator.
        var card = el ? el.closest('.hub-coordinador__kpi-card') : null;
        if (card && previousKpis[key] !== undefined) {
          var trendEl = card.querySelector('.hub-coordinador__kpi-trend');
          if (!trendEl) {
            trendEl = document.createElement('span');
            trendEl.className = 'hub-coordinador__kpi-trend';
            var dataEl = card.querySelector('.hub-coordinador__kpi-data');
            if (dataEl) { dataEl.appendChild(trendEl); }
          }
          var prev = previousKpis[key];
          var curr = numericValues[key];
          if (curr > prev) {
            trendEl.className = 'hub-coordinador__kpi-trend hub-coordinador__kpi-trend--up';
            trendEl.textContent = '\u2191 +' + (curr - prev);
          }
          else if (curr < prev) {
            trendEl.className = 'hub-coordinador__kpi-trend hub-coordinador__kpi-trend--down';
            trendEl.textContent = '\u2193 ' + (curr - prev);
          }
          else {
            trendEl.textContent = '';
          }
        }
      });

      // Store current values for next comparison.
      Object.keys(numericValues).forEach(function (key) {
        previousKpis[key] = numericValues[key];
      });
    }).catch(function () {
      // Silent fail for background refresh.
    });
  }

  // ─── KPI Card Navigation ─────────────────────────────────────────────────

  function navigateToTab(container, tabName, filterKey, filterValue) {
    var tab = container.querySelector('[data-tab="' + tabName + '"]');
    if (!tab) { return; }

    // Activate target tab.
    tab.click();

    // Apply filter if specified — wait for DOM update after tab switch.
    if (filterKey && filterValue) {
      setTimeout(function () {
        var filter = container.querySelector(
          '[data-panel="' + tabName + '"] [data-filter="' + filterKey + '"]'
        );
        if (filter) {
          filter.value = filterValue;
          filter.dispatchEvent(new Event('change'));
        }
      }, 50);
    }

    // Scroll tabs into view for mobile.
    tab.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
  }

  function initKpiNavigation(container) {
    container.querySelectorAll('[data-kpi-target]').forEach(function (card) {
      card.addEventListener('click', function () {
        navigateToTab(
          container,
          card.dataset.kpiTarget,
          card.dataset.kpiFilterKey || null,
          card.dataset.kpiFilterValue || null
        );
      });
    });
  }

  // ─── Tabs ────────────────────────────────────────────────────────────────

  /**
   * Detects when the sticky tab bar is "stuck" and adds a shadow class.
   *
   * Uses a sentinel <div> placed just before the tabs. When the sentinel
   * scrolls out of view (IntersectionObserver threshold 0), the tabs are
   * stuck. Adds .hub-coordinador__tabs--stuck which enables the ::after
   * shadow gradient.
   */
  /**
   * Measures the sticky header height and sets --hub-sticky-offset on
   * the hub container. The CSS uses this variable for the tabs'
   * position:sticky top value so they sit just below the header.
   *
   * Recalculates on resize (header height changes on mobile/toolbar).
   */
  function syncStickyOffset(container) {
    var header = document.querySelector('.landing-header');
    if (!header) { return; }

    function update() {
      var h = header.getBoundingClientRect().height;
      container.style.setProperty('--hub-sticky-offset', Math.round(h) + 'px');
    }

    update();
    window.addEventListener('resize', update, { passive: true });
  }

  function initStickyTabs(container) {
    var stickyWrapper = container.querySelector('.hub-coordinador__tabs-sticky');
    if (!stickyWrapper) { return; }

    // Set --hub-sticky-offset so tabs stick below the header.
    syncStickyOffset(container);

    // Create a zero-height sentinel element above the sticky wrapper.
    var sentinel = document.createElement('div');
    sentinel.className = 'hub-coordinador__tabs-sentinel';
    sentinel.setAttribute('aria-hidden', 'true');
    stickyWrapper.parentNode.insertBefore(sentinel, stickyWrapper);

    var observer = new IntersectionObserver(function (entries) {
      // When sentinel is NOT intersecting, the tabs are stuck at top.
      stickyWrapper.classList.toggle('hub-coordinador__tabs-sticky--stuck', !entries[0].isIntersecting);
    }, { threshold: 0 });

    observer.observe(sentinel);
  }

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
          }
          else {
            p.classList.remove('hub-coordinador__panel--active');
            p.setAttribute('hidden', '');
          }
        });

        // Lazy load panel data.
        if (target === 'solicitudes') { loadSolicitudes(container, '', 0); }
        if (target === 'participantes') { loadParticipants(container, '', '', 0); }
        if (target === 'sesiones') { loadSessions(container); }
        if (target === 'documentacion') { loadDocumentacion(container, '', '', 0); }

        // height:'auto' makes FullCalendar immune to hidden-container bugs,
        // but updateSize() still needed if viewport changed while on another tab.
        if (target === 'calendario') {
          var calContainer = container.querySelector('[data-calendar-container]');
          if (calContainer && calContainer._fcCalendar) {
            calContainer._fcCalendar.updateSize();
          }
        }
      });

      // Keyboard navigation (arrows).
      tab.addEventListener('keydown', function (e) {
        var tabArray = Array.from(tabs);
        var idx = tabArray.indexOf(tab);
        if (e.key === 'ArrowRight' && idx < tabArray.length - 1) {
          tabArray[idx + 1].focus();
          tabArray[idx + 1].click();
        }
        else if (e.key === 'ArrowLeft' && idx > 0) {
          tabArray[idx - 1].focus();
          tabArray[idx - 1].click();
        }
      });
    });
  }

  // ─── Hash Navigation (Copilot CTA deep-links) ──────────────────────────

  function handleHashNavigation(container) {
    function navigateToHash() {
      var hash = window.location.hash;
      if (!hash || !hash.startsWith('#panel-')) { return; }
      var panelName = hash.replace('#panel-', '');
      var tab = container.querySelector('[data-tab="' + panelName + '"]');
      if (tab) {
        tab.click();
        tab.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      }
    }
    navigateToHash();
    window.addEventListener('hashchange', navigateToHash);
  }

  // ─── Solicitudes ─────────────────────────────────────────────────────────

  function loadSolicitudes(container, estado, offset) {
    if (!apiUrls.solicitudes) { return; }
    estado = estado || '';
    offset = offset || 0;

    var url = apiUrls.solicitudes + '?_format=json&limit=' + ITEMS_PER_PAGE + '&offset=' + offset;
    if (estado) { url += '&estado=' + encodeURIComponent(estado); }

    var tbody = container.querySelector('[data-tbody="solicitudes"]');
    if (!tbody) { return; }
    tbody.innerHTML = skeletonRow(6);

    apiGet(url).then(function (res) {
      if (!res.success || !res.data || !res.data.items) {
        tbody.innerHTML = emptyRow(6, Drupal.t('Error al cargar solicitudes.'));
        return;
      }

      var items = res.data.items;
      var total = res.data.total || items.length;

      if (items.length === 0) {
        tbody.innerHTML = emptyRow(6, Drupal.t('No hay solicitudes.'));
        renderPagination(container, 'solicitudes', 0, 0, function () {});
        return;
      }

      var html = '';
      items.forEach(function (item) {
        html += '<tr>';
        html += '<td>' + Drupal.checkPlain(item.nombre) + '</td>';
        html += '<td>' + Drupal.checkPlain(item.email) + '</td>';
        html += '<td>' + Drupal.checkPlain(item.provincia) + '</td>';
        html += '<td><span class="hub-coordinador__badge hub-coordinador__badge--'
          + Drupal.checkPlain(item.estado) + '">'
          + Drupal.checkPlain(item.estado) + '</span></td>';
        html += '<td>' + formatDate(item.created) + '</td>';
        html += '<td class="hub-coordinador__actions">';
        if (item.estado === 'pendiente' || item.estado === 'contactado') {
          html += '<button class="hub-coordinador__action-btn hub-coordinador__action-btn--approve" '
            + 'data-action="approve" data-id="' + item.id + '">'
            + Drupal.t('Aprobar') + '</button>';
          html += '<button class="hub-coordinador__action-btn hub-coordinador__action-btn--reject" '
            + 'data-action="reject" data-id="' + item.id + '" '
            + 'data-nombre="' + Drupal.checkPlain(item.nombre) + '">'
            + Drupal.t('Rechazar') + '</button>';
        }
        html += '</td>';
        html += '</tr>';
      });
      tbody.innerHTML = html;

      bindSolicitudActions(container);
      renderPagination(container, 'solicitudes', total, offset, function (newOffset) {
        loadSolicitudes(container, estado, newOffset);
      });
    }).catch(function () {
      tbody.innerHTML = emptyRow(6, Drupal.t('Error de conexion.'));
    });

    // Bind filter.
    var filterSelect = container.querySelector('[data-filter="estado"]');
    if (filterSelect && !filterSelect.dataset.bound) {
      filterSelect.dataset.bound = 'true';
      filterSelect.addEventListener('change', function () {
        loadSolicitudes(container, filterSelect.value, 0);
      });
    }
  }

  function bindSolicitudActions(container) {
    container.querySelectorAll('[data-action="approve"]').forEach(function (btn) {
      if (btn.dataset.bound) { return; }
      btn.dataset.bound = 'true';
      btn.addEventListener('click', function () {
        var id = btn.dataset.id;
        if (!apiUrls.solicitudApprove) { return; }
        modalTriggerElement = btn;

        openModal(container,
          Drupal.t('Aprobar solicitud'),
          '<p>' + Drupal.t('Se creara un nuevo participante a partir de esta solicitud. ¿Confirmar aprobacion?') + '</p>'
        ).then(function (result) {
          if (!result && result !== null) { return; }
          if (result === null) { return; }

          btn.disabled = true;
          apiPost(urlWithId(apiUrls.solicitudApprove, id)).then(function (res) {
            notify(container, res.message, res.success ? 'success' : 'error');
            if (res.success) { loadSolicitudes(container, '', 0); }
          }).catch(function () {
            notify(container, Drupal.t('Error de conexion.'), 'error');
            btn.disabled = false;
          });
        });
      });
    });

    container.querySelectorAll('[data-action="reject"]').forEach(function (btn) {
      if (btn.dataset.bound) { return; }
      btn.dataset.bound = 'true';
      btn.addEventListener('click', function () {
        var id = btn.dataset.id;
        var nombre = btn.dataset.nombre || '';
        if (!apiUrls.solicitudReject) { return; }
        modalTriggerElement = btn;

        openModal(container,
          Drupal.t('Rechazar solicitud de @name', { '@name': nombre }),
          '<label for="reject-reason">' + Drupal.t('Motivo del rechazo') + '</label>'
          + '<textarea id="reject-reason" name="reason" rows="3" '
          + 'placeholder="' + Drupal.t('Indique el motivo...') + '"></textarea>'
        ).then(function (result) {
          if (!result || !result.reason) { return; }

          btn.disabled = true;
          apiPost(urlWithId(apiUrls.solicitudReject, id), { reason: result.reason }).then(function (res) {
            notify(container, res.message, res.success ? 'success' : 'error');
            if (res.success) { loadSolicitudes(container, '', 0); }
          }).catch(function () {
            notify(container, Drupal.t('Error de conexion.'), 'error');
            btn.disabled = false;
          });
        });
      });
    });
  }

  // ─── Participantes ───────────────────────────────────────────────────────

  function loadParticipants(container, fase, search, offset) {
    if (!apiUrls.participants) { return; }
    fase = fase || '';
    search = search || '';
    offset = offset || 0;

    var url = apiUrls.participants + '?_format=json&limit=' + ITEMS_PER_PAGE + '&offset=' + offset;
    if (fase) { url += '&fase=' + encodeURIComponent(fase); }
    if (search) { url += '&search=' + encodeURIComponent(search); }

    var tbody = container.querySelector('[data-tbody="participantes"]');
    if (!tbody) { return; }
    tbody.innerHTML = skeletonRow(5);

    apiGet(url).then(function (res) {
      if (!res.success || !res.data || !res.data.items) {
        tbody.innerHTML = emptyRow(5, Drupal.t('Error al cargar participantes.'));
        return;
      }

      var items = res.data.items;
      var total = res.data.total || items.length;

      if (items.length === 0) {
        tbody.innerHTML = emptyRow(5, Drupal.t('No hay participantes.'));
        renderPagination(container, 'participantes', 0, 0, function () {});
        return;
      }

      var html = '';
      items.forEach(function (item) {
        html += '<tr>';
        html += '<td>' + Drupal.checkPlain(item.dni_nie) + '</td>';
        html += '<td>' + Drupal.checkPlain(item.nombre) + '</td>';
        html += '<td><span class="hub-coordinador__badge hub-coordinador__badge--'
          + Drupal.checkPlain(item.fase_actual) + '">'
          + Drupal.checkPlain(getPhaseLabel(item.fase_actual)) + '</span></td>';
        html += '<td>' + formatDate(item.changed) + '</td>';
        html += '<td class="hub-coordinador__actions">';
        if (item.fase_actual !== 'baja') {
          html += '<button class="hub-coordinador__action-btn" '
            + 'data-action="change-phase" data-id="' + item.id + '" '
            + 'data-current-phase="' + Drupal.checkPlain(item.fase_actual) + '" '
            + 'data-nombre="' + Drupal.checkPlain(item.nombre || item.dni_nie) + '">'
            + Drupal.t('Cambiar fase') + '</button>';
        }
        html += '</td>';
        html += '</tr>';
      });
      tbody.innerHTML = html;

      bindParticipantActions(container);
      renderPagination(container, 'participantes', total, offset, function (newOffset) {
        loadParticipants(container, fase, search, newOffset);
      });
    }).catch(function () {
      tbody.innerHTML = emptyRow(5, Drupal.t('Error de conexion.'));
    });

    // Bind filters.
    var faseFilter = container.querySelector('[data-filter="fase"]');
    if (faseFilter && !faseFilter.dataset.bound) {
      faseFilter.dataset.bound = 'true';
      faseFilter.addEventListener('change', function () {
        var searchInput = container.querySelector('[data-filter="search"]');
        loadParticipants(container, faseFilter.value, searchInput ? searchInput.value : '', 0);
      });
    }

    var searchInput = container.querySelector('[data-filter="search"]');
    if (searchInput && !searchInput.dataset.bound) {
      searchInput.dataset.bound = 'true';
      var debounceTimer;
      searchInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () {
          loadParticipants(container, faseFilter ? faseFilter.value : '', searchInput.value, 0);
        }, 400);
      });
    }
  }

  function bindParticipantActions(container) {
    container.querySelectorAll('[data-action="change-phase"]').forEach(function (btn) {
      if (btn.dataset.bound) { return; }
      btn.dataset.bound = 'true';
      btn.addEventListener('click', function () {
        var id = btn.dataset.id;
        var currentPhase = btn.dataset.currentPhase || '';
        var nombre = btn.dataset.nombre || '';
        if (!apiUrls.changePhase) { return; }
        modalTriggerElement = btn;

        var phases = hubConfig.phases || ['acogida', 'diagnostico', 'atencion', 'insercion', 'seguimiento', 'baja'];

        var optionsHtml = '';
        phases.forEach(function (p) {
          if (p !== currentPhase) {
            optionsHtml += '<option value="' + Drupal.checkPlain(p) + '">'
              + Drupal.checkPlain(getPhaseLabel(p)) + '</option>';
          }
        });

        openModal(container,
          Drupal.t('Cambiar fase de @name', { '@name': nombre }),
          '<p>' + Drupal.t('Fase actual: @phase', { '@phase': getPhaseLabel(currentPhase) }) + '</p>'
          + '<label for="new-phase">' + Drupal.t('Nueva fase') + '</label>'
          + '<select id="new-phase" name="phase">'
          + '<option value="">' + Drupal.t('Seleccionar fase...') + '</option>'
          + optionsHtml
          + '</select>'
        ).then(function (result) {
          if (!result || !result.phase) { return; }

          btn.disabled = true;
          apiPost(urlWithId(apiUrls.changePhase, id), { phase: result.phase }).then(function (res) {
            notify(container, res.message, res.success ? 'success' : 'error');
            if (res.success) {
              var faseFilter = container.querySelector('[data-filter="fase"]');
              var searchInput = container.querySelector('[data-filter="search"]');
              loadParticipants(
                container,
                faseFilter ? faseFilter.value : '',
                searchInput ? searchInput.value : '',
                0
              );
            }
          }).catch(function () {
            notify(container, Drupal.t('Error de conexion.'), 'error');
            btn.disabled = false;
          });
        });
      });
    });
  }

  // ─── Sesiones ────────────────────────────────────────────────────────────

  function loadSessions(container) {
    if (!apiUrls.sessions) { return; }

    var tbody = container.querySelector('[data-tbody="sesiones"]');
    if (!tbody) { return; }
    tbody.innerHTML = skeletonRow(4);

    apiGet(apiUrls.sessions + '?_format=json&days=30').then(function (res) {
      if (!res.success || !res.data || !res.data.sessions) {
        tbody.innerHTML = emptyRow(4, Drupal.t('Error al cargar sesiones.'));
        return;
      }

      var sessions = res.data.sessions;
      if (sessions.length === 0) {
        tbody.innerHTML = emptyRow(4, Drupal.t('No hay sesiones recientes.'));
        return;
      }

      var html = '';
      sessions.forEach(function (s) {
        html += '<tr>';
        html += '<td>#' + s.session_number + '</td>';
        html += '<td>' + Drupal.checkPlain(s.mentor_name) + '</td>';
        html += '<td>' + Drupal.checkPlain(s.mentee_name) + '</td>';
        html += '<td><span class="hub-coordinador__badge hub-coordinador__badge--'
          + Drupal.checkPlain(s.status) + '">'
          + Drupal.checkPlain(s.status) + '</span></td>';
        html += '</tr>';
      });
      tbody.innerHTML = html;
    }).catch(function () {
      tbody.innerHTML = emptyRow(4, Drupal.t('Error de conexion.'));
    });
  }

  // ─── Documentacion ──────────────────────────────────────────────────────

  function loadDocumentacion(container, estadoDoc, search, offset) {
    if (!apiUrls.documentacion) { return; }
    estadoDoc = estadoDoc || '';
    search = search || '';
    offset = offset || 0;

    var url = apiUrls.documentacion + '?_format=json&limit=' + ITEMS_PER_PAGE + '&offset=' + offset;
    if (estadoDoc) { url += '&estado_doc=' + encodeURIComponent(estadoDoc); }
    if (search) { url += '&search=' + encodeURIComponent(search); }

    var tbody = container.querySelector('[data-tbody="documentacion"]');
    if (!tbody) { return; }
    tbody.innerHTML = skeletonRow(8);

    apiGet(url).then(function (res) {
      if (!res.success || !res.data || !res.data.items) {
        tbody.innerHTML = emptyRow(8, Drupal.t('Error al cargar documentacion.'));
        return;
      }

      var items = res.data.items;
      var total = res.data.total || items.length;

      if (items.length === 0) {
        tbody.innerHTML = emptyRow(8, Drupal.t('No hay participantes con documentacion.'));
        renderPagination(container, 'documentacion', 0, 0, function () {});
        return;
      }

      var html = '';
      items.forEach(function (item) {
        html += '<tr>';
        // Participante.
        html += '<td><div class="hub-coordinador__doc-participante">'
          + '<strong>' + Drupal.checkPlain(item.nombre) + '</strong>'
          + '<small>' + Drupal.checkPlain(item.dni_nie) + '</small>'
          + '</div></td>';
        // Fase.
        html += '<td><span class="hub-coordinador__badge hub-coordinador__badge--'
          + Drupal.checkPlain(item.fase_actual) + '">'
          + Drupal.checkPlain(getPhaseLabel(item.fase_actual)) + '</span></td>';
        // Docs STO.
        html += '<td>' + item.sto_completos + '/' + item.sto_requeridos + '</td>';
        // Completitud (barra de progreso).
        html += '<td><div class="hub-coordinador__doc-progress">'
          + '<div class="hub-coordinador__doc-progress-bar" style="width:' + item.completitud + '%"></div>'
          + '<span class="hub-coordinador__doc-progress-text">' + item.completitud + '%</span>'
          + '</div></td>';
        // Acuerdo.
        html += '<td>' + (item.acuerdo_firmado
          ? '<span class="hub-coordinador__doc-check hub-coordinador__doc-check--ok" title="' + Drupal.t('Firmado') + '">&#10003;</span>'
          : '<span class="hub-coordinador__doc-check hub-coordinador__doc-check--pending" title="' + Drupal.t('Pendiente') + '">&#10007;</span>')
          + '</td>';
        // DACI.
        html += '<td>' + (item.daci_firmado
          ? '<span class="hub-coordinador__doc-check hub-coordinador__doc-check--ok" title="' + Drupal.t('Firmado') + '">&#10003;</span>'
          : '<span class="hub-coordinador__doc-check hub-coordinador__doc-check--pending" title="' + Drupal.t('Pendiente') + '">&#10007;</span>')
          + '</td>';
        // Incentivo.
        html += '<td>' + (item.incentivo_recibido
          ? '<span class="hub-coordinador__doc-check hub-coordinador__doc-check--ok" title="' + Drupal.t('Recibido') + '">&#10003;</span>'
          : '<span class="hub-coordinador__doc-check hub-coordinador__doc-check--pending" title="' + Drupal.t('Pendiente') + '">&#8212;</span>')
          + '</td>';
        // Estado documental.
        var docStatusLabel = {
          'completo': Drupal.t('Completo'),
          'incompleto': Drupal.t('Incompleto'),
          'pendiente_revision': Drupal.t('Revision')
        };
        html += '<td><span class="hub-coordinador__badge hub-coordinador__badge--doc-'
          + Drupal.checkPlain(item.doc_status) + '">'
          + Drupal.checkPlain(docStatusLabel[item.doc_status] || item.doc_status)
          + '</span></td>';
        html += '</tr>';
      });
      tbody.innerHTML = html;

      renderPagination(container, 'documentacion', total, offset, function (newOffset) {
        loadDocumentacion(container, estadoDoc, search, newOffset);
      });
    }).catch(function () {
      tbody.innerHTML = emptyRow(8, Drupal.t('Error de conexion.'));
    });

    // Bind filters.
    var estadoFilter = container.querySelector('[data-filter="estado_doc"]');
    if (estadoFilter && !estadoFilter.dataset.bound) {
      estadoFilter.dataset.bound = 'true';
      estadoFilter.addEventListener('change', function () {
        var searchInput = container.querySelector('[data-filter="search_doc"]');
        loadDocumentacion(container, estadoFilter.value, searchInput ? searchInput.value : '', 0);
      });
    }

    var searchInput = container.querySelector('[data-filter="search_doc"]');
    if (searchInput && !searchInput.dataset.bound) {
      searchInput.dataset.bound = 'true';
      var debounceTimer;
      searchInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () {
          loadDocumentacion(container, estadoFilter ? estadoFilter.value : '', searchInput.value, 0);
        }, 400);
      });
    }
  }

  // ─── CSV Export ────────────────────────────────────────────────────────

  function initCsvExport(container) {
    container.querySelectorAll('[data-export]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var target = btn.dataset.export;
        var table = container.querySelector('[data-table="' + target + '"]');
        if (!table) { return; }

        var rows = [];
        // Header row.
        var headers = [];
        table.querySelectorAll('thead th').forEach(function (th) {
          headers.push('"' + th.textContent.trim().replace(/"/g, '""') + '"');
        });
        rows.push(headers.join(';'));

        // Data rows.
        table.querySelectorAll('tbody tr').forEach(function (tr) {
          // Skip skeleton/empty rows.
          if (tr.querySelector('.hub-coordinador__loading') || tr.querySelector('.hub-coordinador__empty')) {
            return;
          }
          var cells = [];
          tr.querySelectorAll('td').forEach(function (td) {
            var text = td.textContent.trim().replace(/\s+/g, ' ').replace(/"/g, '""');
            cells.push('"' + text + '"');
          });
          if (cells.length > 0) {
            rows.push(cells.join(';'));
          }
        });

        if (rows.length <= 1) {
          notify(container, Drupal.t('No hay datos para exportar.'), 'error');
          return;
        }

        // BOM for Excel UTF-8 compatibility.
        var csvContent = '\uFEFF' + rows.join('\r\n');
        var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'coordinador-' + target + '-' + new Date().toISOString().slice(0, 10) + '.csv';
        a.style.display = 'none';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);

        notify(container, Drupal.t('@count registros exportados.', { '@count': rows.length - 1 }), 'success');
      });
    });
  }

})(Drupal, drupalSettings, once);

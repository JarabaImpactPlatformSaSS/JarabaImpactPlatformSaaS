/**
 * @file
 * Calendario interactivo para el Hub Coordinador Andalucia +ei.
 *
 * FullCalendar 6.x integration con drag-and-drop, filtros y keyboard nav.
 * Patron: Drupal.behaviors (admin context — core/once disponible).
 *
 * INNERHTML-XSS-001: Drupal.checkPlain() para datos de API.
 * ROUTE-LANGPREFIX-001: URLs via drupalSettings.
 * CSRF-API-001: Token de /session/token cacheado.
 */

(function (Drupal, drupalSettings, once) {
  'use strict';



  // Cache CSRF token (TTL 1h).
  let csrfToken = null;
  let csrfTokenTimestamp = 0;
  const CSRF_TOKEN_TTL = 3600000;

  /**
   * Obtiene CSRF token cacheado.
   */
  async function getCsrfToken() {
    const now = Date.now();
    if (csrfToken && (now - csrfTokenTimestamp) < CSRF_TOKEN_TTL) {
      return csrfToken;
    }
    try {
      const resp = await fetch(Drupal.url('session/token'));
      if (resp.ok) {
        csrfToken = await resp.text();
        csrfTokenTimestamp = now;
      }
    }
    catch (e) {
      // Fallback: continue without token (will fail on POST).
    }
    return csrfToken;
  }

  /**
   * Detecta vista inicial segun viewport.
   */
  function getInitialView() {
    return window.innerWidth < 768 ? 'listWeek' : 'dayGridMonth';
  }

  /**
   * Mapea modalidad a color CSS class.
   */
  const MODALIDAD_COLORS = {
    presencial: 'var(--ej-color-verde-innovacion, #00A9A5)',
    online: 'var(--ej-color-azul-corporativo, #233D63)',
    mixta: 'var(--ej-color-naranja-impulso, #FF8C42)'
  };

  /**
   * Labels traducidos para modalidades.
   */
  const MODALIDAD_LABELS = {
    presencial: Drupal.t('Presencial'),
    online: Drupal.t('Online'),
    mixta: Drupal.t('Mixta')
  };

  /**
   * Behavior principal del calendario.
   */
  Drupal.behaviors.coordinadorCalendar = {
    attach: function (context) {
      var hubConfig = (drupalSettings.jarabaAndaluciaEi || {}).hub || {};
      var apiUrls = hubConfig.apiUrls || {};
      var calendarConfig = hubConfig.calendarConfig || {};

      // Hub coordinador: explicit container in Twig template.
      var containers = once('coordinador-calendar', '[data-calendar-container]', context);
      containers.forEach(function (el) {
        initCalendar(el, apiUrls, calendarConfig);
      });

      // Admin collection: inject toggle + calendar above entity list table.
      var tables = once('coordinador-calendar-admin', '.entity-list-builder table, .views-table', context);
      if (tables.length && !containers.length && apiUrls.calendarEvents) {
        tables.forEach(function (table) {
          injectAdminCalendarToggle(table, apiUrls, calendarConfig);
        });
      }
    }
  };

  /**
   * Injects a table/calendar toggle on admin collection pages.
   */
  function injectAdminCalendarToggle(table, apiUrls, calendarConfig) {
    var parent = table.parentNode;
    if (!parent) { return; }

    // Create toggle bar.
    var toggleBar = document.createElement('div');
    toggleBar.className = 'hub-coordinador__calendar-toggle-bar';
    toggleBar.innerHTML =
      '<button type="button" class="hub-coordinador__action-btn hub-coordinador__action-btn--active" data-view-toggle="table">' +
        Drupal.checkPlain(Drupal.t('Tabla')) +
      '</button>' +
      '<button type="button" class="hub-coordinador__action-btn" data-view-toggle="calendar">' +
        Drupal.checkPlain(Drupal.t('Calendario')) +
      '</button>';
    parent.insertBefore(toggleBar, table);

    // Create calendar container (hidden initially).
    var calContainer = document.createElement('div');
    calContainer.className = 'hub-coordinador__calendar-layout';
    calContainer.setAttribute('data-calendar-container', '');
    calContainer.style.display = 'none';

    var calMain = document.createElement('div');
    calMain.className = 'hub-coordinador__calendar-main';
    calMain.setAttribute('data-calendar-render', '');
    calContainer.appendChild(calMain);
    parent.insertBefore(calContainer, table.nextSibling);

    var calendarInitialized = false;

    // Toggle handler.
    toggleBar.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-view-toggle]');
      if (!btn) { return; }

      var view = btn.getAttribute('data-view-toggle');
      toggleBar.querySelectorAll('[data-view-toggle]').forEach(function (b) {
        b.classList.toggle('hub-coordinador__action-btn--active', b === btn);
      });

      if (view === 'calendar') {
        table.style.display = 'none';
        calContainer.style.display = '';
        if (!calendarInitialized) {
          initCalendar(calContainer, apiUrls, calendarConfig);
          calendarInitialized = true;
        }
      }
      else {
        table.style.display = '';
        calContainer.style.display = 'none';
      }
    });
  }

  /**
   * Inicializa FullCalendar en el contenedor.
   */
  function initCalendar(el, apiUrls, calendarConfig) {
    if (typeof FullCalendar === 'undefined') {
      el.innerHTML = '<p style="padding:2rem;color:#EF4444">' +
        Drupal.checkPlain(Drupal.t('Error: FullCalendar no se ha cargado.')) + '</p>';
      return;
    }

    var calendarEl = el.querySelector('[data-calendar-render]');
    if (!calendarEl) {
      calendarEl = el;
    }

    var calendar = new FullCalendar.Calendar(calendarEl, {
      locale: 'es',
      timeZone: 'Europe/Madrid',
      initialView: getInitialView(),
      // height:'auto' avoids FullCalendar's ScrollGrid liquid layout which
      // caches cell heights on render(). In a tabbed UI where the calendar
      // panel starts [hidden], the ScrollGrid gets 0-height cells that
      // persist even after the panel becomes visible. 'auto' makes the
      // grid size itself by content — immune to hidden-container bugs.
      height: 'auto',
      stickyHeaderDates: true,
      nowIndicator: true,
      dayMaxEvents: 3,
      eventDisplay: 'block',
      fixedWeekCount: false,

      headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
      },

      buttonText: {
        today: Drupal.t('Hoy'),
        month: Drupal.t('Mes'),
        week: Drupal.t('Semana'),
        day: Drupal.t('Dia'),
        list: Drupal.t('Lista')
      },

      // JSON event source — FullCalendar auto-sends start/end params.
      events: {
        url: apiUrls.calendarEvents || '',
        extraParams: function () {
          return getActiveFilters(el);
        },
        failure: function () {
          showNotification(Drupal.t('Error al cargar los eventos del calendario.'), 'error');
        }
      },

      // Drag-and-drop.
      editable: true,
      eventStartEditable: true,
      eventDurationEditable: false,

      // Date selection — create events by clicking/dragging.
      selectable: true,
      selectMirror: true,
      unselectAuto: true,

      // Click on empty date → create session.
      dateClick: function (info) {
        handleDateClick(info, apiUrls);
      },

      // Drag range selection (timeGrid) → create session with time range.
      select: function (info) {
        handleDateSelect(info, apiUrls);
      },

      // Block drag on completed/cancelled.
      eventAllow: function (dropInfo, draggedEvent) {
        var estado = (draggedEvent.extendedProps || {}).estado || '';
        return estado !== 'completada' && estado !== 'cancelada';
      },

      // Confirm + POST on drop.
      eventDrop: function (info) {
        handleEventDrop(info, apiUrls);
      },

      // Click: show details with edit/duplicate actions.
      eventClick: function (info) {
        handleEventClick(info, apiUrls);
      },

      // Custom event rendering.
      eventDidMount: function (info) {
        renderEventExtras(info);
      },

      // Responsive view switch.
      windowResize: function (view) {
        var currentView = calendar.view.type;
        var idealView = getInitialView();
        if (window.innerWidth < 768 && currentView.indexOf('Grid') !== -1) {
          calendar.changeView('listWeek');
        }
        else if (window.innerWidth >= 768 && currentView === 'listWeek') {
          calendar.changeView('dayGridMonth');
        }
      }
    });

    calendar.render();

    // Store reference for filters.
    el._fcCalendar = calendar;

    // Wire filters.
    initFilters(el, calendar);

    // Wire iCal subscribe button.
    initICalSubscribe(el, apiUrls);

    // Wire print button.
    initPrint(el);
  }

  /**
   * Handles click on empty date cell — opens session creation form.
   *
   * Uses the existing slide-panel pattern (data-slide-panel).
   * ROUTE-LANGPREFIX-001: URL from drupalSettings.
   */
  function handleDateClick(info, apiUrls) {
    // In timeGrid views, select handler takes priority (has time info).
    if (info.view.type.indexOf('timeGrid') !== -1) {
      return;
    }
    openCreateSessionPanel(apiUrls, info.dateStr, null, null);
  }

  /**
   * Handles date range selection (timeGrid views).
   *
   * When user drags to select a time range, opens creation form
   * with fecha + hora_inicio + hora_fin pre-filled.
   */
  function handleDateSelect(info, apiUrls) {
    // Only act on timeGrid selections (with time component).
    if (info.view.type.indexOf('timeGrid') === -1) {
      return;
    }
    var fecha = formatDate(info.start);
    var horaInicio = formatTime(info.start);
    var horaFin = formatTime(info.end);
    openCreateSessionPanel(apiUrls, fecha, horaInicio, horaFin);
  }

  /**
   * Opens the session creation slide-panel with pre-filled date/time.
   *
   * Leverages the existing slide-panel infrastructure:
   * 1. Creates a temporary <a data-slide-panel> element
   * 2. Appends query params for fecha, hora_inicio, hora_fin
   * 3. Triggers click to open via the existing slide-panel behavior
   *
   * INNERHTML-XSS-001: No user input in URL construction.
   */
  function openCreateSessionPanel(apiUrls, fecha, horaInicio, horaFin) {
    var baseUrl = apiUrls.sessionCreate;
    if (!baseUrl) {
      return;
    }

    // Build URL with query parameters for date pre-fill.
    var separator = baseUrl.indexOf('?') !== -1 ? '&' : '?';
    var url = baseUrl + separator + 'fecha=' + encodeURIComponent(fecha);
    if (horaInicio) {
      url += '&hora_inicio=' + encodeURIComponent(horaInicio);
    }
    if (horaFin) {
      url += '&hora_fin=' + encodeURIComponent(horaFin);
    }

    // Create ephemeral link and trigger slide-panel.
    var link = document.createElement('a');
    link.href = url;
    link.setAttribute('data-slide-panel', '');
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();

    // Cleanup.
    setTimeout(function () { link.remove(); }, 100);
  }

  /**
   * Handles event drop (drag-and-drop reschedule).
   */
  async function handleEventDrop(info, apiUrls) {
    var event = info.event;
    var props = event.extendedProps || {};
    var newDate = formatDate(event.start);
    var newStart = formatTime(event.start);
    var newEnd = event.end ? formatTime(event.end) : null;

    var msg = Drupal.t('Reprogramar "@title" al @date @time?', {
      '@title': event.title,
      '@date': newDate,
      '@time': newStart
    });

    if (!confirm(msg)) {
      info.revert();
      return;
    }

    var url = (apiUrls.sessionReschedule || '').replace('__ID__', event.id);
    if (!url) {
      info.revert();
      return;
    }

    try {
      var token = await getCsrfToken();
      var resp = await fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': token || ''
        },
        body: JSON.stringify({
          newDate: newDate,
          newTimeStart: newStart,
          newTimeEnd: newEnd
        })
      });

      var result = await resp.json();
      if (result.success) {
        showNotification(Drupal.t('Sesion reprogramada correctamente.'), 'success');
      }
      else {
        showNotification(result.message || Drupal.t('Error al reprogramar.'), 'error');
        info.revert();
      }
    }
    catch (e) {
      showNotification(Drupal.t('Error de conexion al reprogramar.'), 'error');
      info.revert();
    }
  }

  /**
   * Handles event click — show detail popover.
   */
  function handleEventClick(info, hubApiUrls) {
    info.jsEvent.preventDefault();
    var event = info.event;
    var props = event.extendedProps || {};
    var el = info.el;
    hubApiUrls = hubApiUrls || {};

    // Remove existing popovers.
    document.querySelectorAll('.hub-cal-popover').forEach(function (p) { p.remove(); });

    var popover = document.createElement('div');
    popover.className = 'hub-cal-popover';
    popover.setAttribute('role', 'dialog');
    popover.setAttribute('aria-label', event.title);

    var modalidad = MODALIDAD_LABELS[props.modalidad] || props.modalidad;
    var plazas = (props.plazas_ocupadas || 0) + '/' + (props.max_plazas || 0);

    // Build capacity percentage.
    var maxP = props.max_plazas || 0;
    var occP = props.plazas_ocupadas || 0;
    var capPct = maxP > 0 ? Math.round((occP / maxP) * 100) : 0;
    var capClass = capPct > 90 ? 'critical' : capPct > 70 ? 'warning' : 'ok';

    // Time display.
    var timeStr = '';
    if (event.start) {
      timeStr = formatTime(event.start);
      if (event.end) {
        timeStr += ' - ' + formatTime(event.end);
      }
    }

    popover.innerHTML =
      '<div class="hub-cal-popover__header">' +
        '<div class="hub-cal-popover__header-content">' +
          '<span class="hub-cal-popover__modalidad-dot" style="background:' + (MODALIDAD_COLORS[props.modalidad] || '#233D63') + '"></span>' +
          '<strong>' + Drupal.checkPlain(event.title) + '</strong>' +
        '</div>' +
        '<button class="hub-cal-popover__close" aria-label="' + Drupal.t('Cerrar') + '">&times;</button>' +
      '</div>' +
      (timeStr ? '<div class="hub-cal-popover__time">' + Drupal.checkPlain(timeStr) + '</div>' : '') +
      '<div class="hub-cal-popover__body">' +
        '<div class="hub-cal-popover__row"><span class="hub-cal-popover__label">' + Drupal.t('Modalidad') + '</span><span class="hub-cal-popover__value">' + Drupal.checkPlain(modalidad) + '</span></div>' +
        '<div class="hub-cal-popover__row"><span class="hub-cal-popover__label">' + Drupal.t('Estado') + '</span><span class="hub-cal-popover__badge hub-cal-popover__badge--' + Drupal.checkPlain(props.estado || '') + '">' + Drupal.checkPlain(props.estado || '') + '</span></div>' +
        (maxP > 0 ? '<div class="hub-cal-popover__row hub-cal-popover__row--capacity"><span class="hub-cal-popover__label">' + Drupal.t('Plazas') + '</span><div class="hub-cal-popover__capacity"><span>' + Drupal.checkPlain(plazas) + '</span><div class="hub-cal-popover__capacity-bar"><div class="hub-cal-popover__capacity-fill hub-cal-popover__capacity-fill--' + capClass + '" style="width:' + capPct + '%"></div></div></div></div>' : '') +
        (props.facilitador_nombre ? '<div class="hub-cal-popover__row"><span class="hub-cal-popover__label">' + Drupal.t('Facilitador') + '</span><span class="hub-cal-popover__value">' + Drupal.checkPlain(props.facilitador_nombre) + '</span></div>' : '') +
        (props.fase_programa ? '<div class="hub-cal-popover__row"><span class="hub-cal-popover__label">' + Drupal.t('Fase') + '</span><span class="hub-cal-popover__value">' + Drupal.checkPlain(props.fase_programa) + '</span></div>' : '') +
        (props.lugar_descripcion ? '<div class="hub-cal-popover__row"><span class="hub-cal-popover__label">' + Drupal.t('Lugar') + '</span><span class="hub-cal-popover__value">' + Drupal.checkPlain(props.lugar_descripcion) + '</span></div>' : '') +
      '</div>' +
      '<div class="hub-cal-popover__actions">' +
        (hubApiUrls.sessionEdit ? '<a href="' + Drupal.checkPlain(hubApiUrls.sessionEdit.replace('99999999', event.id)) + '" class="hub-cal-popover__action" data-slide-panel>' + Drupal.t('Editar') + '</a>' : '') +
        (hubApiUrls.sessionCreate ? '<a href="' + Drupal.checkPlain(hubApiUrls.sessionCreate) + '?duplicar=' + event.id + '" class="hub-cal-popover__action hub-cal-popover__action--secondary" data-slide-panel>' + Drupal.t('Duplicar') + '</a>' : '') +
      '</div>';

    document.body.appendChild(popover);

    // Position near the event element.
    var rect = el.getBoundingClientRect();
    popover.style.position = 'fixed';
    popover.style.top = Math.min(rect.bottom + 4, window.innerHeight - popover.offsetHeight - 8) + 'px';
    popover.style.left = Math.min(rect.left, window.innerWidth - popover.offsetWidth - 8) + 'px';
    popover.style.zIndex = '10000';

    // Close handlers.
    popover.querySelector('.hub-cal-popover__close').addEventListener('click', function () {
      popover.remove();
    });
    document.addEventListener('click', function handler(e) {
      if (!popover.contains(e.target) && !el.contains(e.target)) {
        popover.remove();
        document.removeEventListener('click', handler);
      }
    });
    document.addEventListener('keydown', function handler(e) {
      if (e.key === 'Escape') {
        popover.remove();
        document.removeEventListener('keydown', handler);
      }
    });
  }

  /**
   * Renders extra visuals on each event (capacity bar, modalidad indicator, badges).
   *
   * Premium rendering: modalidad color strip, estado badge, phase indicator,
   * capacity progress bar, facilitador avatar placeholder.
   */
  function renderEventExtras(info) {
    var props = info.event.extendedProps || {};
    var el = info.el;

    // Modalidad border-left color (thicker for premium feel).
    var color = MODALIDAD_COLORS[props.modalidad] || MODALIDAD_COLORS.presencial;
    el.style.borderLeft = '4px solid ' + color;
    el.style.borderRadius = '0 6px 6px 0';

    // Add modalidad CSS class for background tinting.
    if (props.modalidad) {
      el.classList.add('hub-cal-event--' + props.modalidad);
    }

    // Estado-based styling.
    if (props.estado === 'completada') {
      el.classList.add('hub-cal-event--completada');
    }
    else if (props.estado === 'cancelada') {
      el.classList.add('hub-cal-event--cancelada');
    }

    // Premium extras only in grid/time views (not list).
    var main = el.querySelector('.fc-event-main') || el;
    if (info.view.type !== 'listWeek') {

      // Modalidad icon badge (top-right corner).
      var modalidadIcon = document.createElement('span');
      modalidadIcon.className = 'hub-cal-event__modalidad-icon';
      modalidadIcon.setAttribute('aria-hidden', 'true');
      modalidadIcon.textContent = props.modalidad === 'online' ? '\u{1F4BB}' :
        props.modalidad === 'mixta' ? '\u{1F504}' : '\u{1F4CD}';
      modalidadIcon.title = MODALIDAD_LABELS[props.modalidad] || '';
      main.appendChild(modalidadIcon);

      // Capacity bar with color coding.
      if (props.max_plazas > 0) {
        var pct = Math.min(100, Math.round((props.plazas_ocupadas / props.max_plazas) * 100));
        var bar = document.createElement('div');
        bar.className = 'hub-cal-event__capacity-bar';
        var fill = document.createElement('div');
        fill.className = 'hub-cal-event__capacity-fill';
        fill.style.width = pct + '%';

        // Color code: green < 70%, orange 70-90%, red > 90%.
        if (pct > 90) {
          fill.classList.add('hub-cal-event__capacity-fill--critical');
        } else if (pct > 70) {
          fill.classList.add('hub-cal-event__capacity-fill--warning');
        }

        bar.appendChild(fill);
        bar.title = props.plazas_ocupadas + '/' + props.max_plazas + ' ' + Drupal.t('plazas');
        main.appendChild(bar);
      }

      // Facilitador mini-badge.
      if (props.facilitador_nombre) {
        var facBadge = document.createElement('span');
        facBadge.className = 'hub-cal-event__facilitador';
        facBadge.textContent = props.facilitador_nombre.split(' ').map(function(w) { return w[0]; }).join('').substring(0, 2).toUpperCase();
        facBadge.title = Drupal.checkPlain(props.facilitador_nombre);
        main.appendChild(facBadge);
      }
    }
    else {
      // List view: add badges inline.
      if (props.modalidad) {
        var badge = document.createElement('span');
        badge.className = 'hub-cal-event__list-badge hub-cal-event__list-badge--' + props.modalidad;
        badge.textContent = MODALIDAD_LABELS[props.modalidad] || props.modalidad;
        main.appendChild(badge);
      }
      if (props.estado && props.estado !== 'programada') {
        var estadoBadge = document.createElement('span');
        estadoBadge.className = 'hub-cal-event__list-badge hub-cal-event__list-badge--estado';
        estadoBadge.textContent = Drupal.checkPlain(props.estado);
        main.appendChild(estadoBadge);
      }
    }
  }

  /**
   * Initializes filter selects.
   */
  function initFilters(container, calendar) {
    var filters = container.querySelectorAll('[data-calendar-filter]');
    filters.forEach(function (select) {
      select.addEventListener('change', function () {
        calendar.refetchEvents();
      });
    });
  }

  /**
   * Gets active filter values from select elements.
   */
  function getActiveFilters(container) {
    var params = {};
    var filters = container.querySelectorAll('[data-calendar-filter]');
    filters.forEach(function (select) {
      var key = select.getAttribute('data-calendar-filter');
      var val = select.value;
      if (val) {
        params[key] = val;
      }
    });
    return params;
  }

  /**
   * Wire iCal subscribe button.
   */
  function initICalSubscribe(container, apiUrls) {
    var btn = container.querySelector('[data-calendar-subscribe]');
    if (!btn || !apiUrls.calendarSubscribe) {
      return;
    }

    btn.addEventListener('click', async function (e) {
      e.preventDefault();
      try {
        var resp = await fetch(apiUrls.calendarSubscribe);
        var data = await resp.json();
        if (data.url) {
          await navigator.clipboard.writeText(data.url);
          showNotification(Drupal.t('URL de suscripcion copiada al portapapeles.'), 'success');
        }
      }
      catch (err) {
        showNotification(Drupal.t('Error al obtener la URL de suscripcion.'), 'error');
      }
    });
  }

  /**
   * Wire print button.
   */
  function initPrint(container) {
    var btn = container.querySelector('[data-calendar-print]');
    if (!btn) {
      return;
    }
    btn.addEventListener('click', function () {
      window.print();
    });
  }

  /**
   * Shows a notification in the hub notifications area.
   */
  function showNotification(message, type) {
    var area = document.querySelector('[data-notifications]');
    if (!area) {
      return;
    }

    var notification = document.createElement('div');
    notification.className = 'hub-coordinador__notification hub-coordinador__notification--' + (type || 'info');
    notification.textContent = message;
    area.appendChild(notification);

    setTimeout(function () {
      notification.classList.add('hub-coordinador__notification--leaving');
      setTimeout(function () { notification.remove(); }, 300);
    }, 4000);
  }

  /**
   * Formats a Date to Y-m-d.
   */
  function formatDate(date) {
    if (!date) { return ''; }
    var y = date.getFullYear();
    var m = String(date.getMonth() + 1).padStart(2, '0');
    var d = String(date.getDate()).padStart(2, '0');
    return y + '-' + m + '-' + d;
  }

  /**
   * Formats a Date to HH:MM.
   */
  function formatTime(date) {
    if (!date) { return ''; }
    var h = String(date.getHours()).padStart(2, '0');
    var m = String(date.getMinutes()).padStart(2, '0');
    return h + ':' + m;
  }

})(Drupal, drupalSettings, once);

/**
 * @file
 * Timeline Interactive Premium - Gráfico animado e interactivo.
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  // Variables globales para interactividad.
  let chartPoints = [];
  let hoveredPoint = null;
  let chartCanvas = null;
  let chartCtx = null;

  Drupal.behaviors.timelineInteractive = {
    attach: function (context) {
      const container = context.querySelector('#timeline-container');
      const addButton = context.querySelector('#add-event');
      chartCanvas = context.querySelector('#timeline-chart');

      if (!container) return;

      once('timeline-init', container, context).forEach(function () {
        // Cargar eventos.
        const events = JSON.parse(localStorage.getItem('timelineEvents') || '[]');
        if (events.length > 0) {
          renderEvents(container, events);
          renderTimelineChart(events, true);
        }

        // Botón añadir evento.
        if (addButton) {
          addButton.addEventListener('click', openAddEventPanel);
        }
      });
    }
  };

  /**
   * Abre el panel para añadir evento.
   */
  function openAddEventPanel() {
    const panelId = 'add-event-panel';
    let panel = document.getElementById(panelId);

    if (!panel) {
      panel = createEventPanel(panelId);
      document.body.appendChild(panel);
      Drupal.behaviors.slidePanel.attach(document);
    }

    Drupal.behaviors.slidePanel.open(panelId);
  }

  /**
   * Crea el panel de añadir evento.
   */
  function createEventPanel(panelId) {
    const panel = document.createElement('div');
    panel.className = 'slide-panel slide-panel--medium';
    panel.id = panelId;
    panel.setAttribute('aria-hidden', 'true');
    panel.setAttribute('role', 'dialog');
    panel.setAttribute('aria-modal', 'true');

    panel.innerHTML = `
      <div class="slide-panel__overlay" data-close-panel="${panelId}"></div>
      <div class="slide-panel__content">
        <header class="slide-panel__header">
          <h2 class="slide-panel__title">${Drupal.t('Añadir Evento')}</h2>
          <button type="button" class="slide-panel__close" data-close-panel="${panelId}" aria-label="${Drupal.t('Cerrar')}">
            <svg viewBox="0 0 24 24" width="24" height="24">
              <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </button>
        </header>
        <div class="slide-panel__body" id="${panelId}-body">
          <form id="add-event-form" class="event-form">
            <!-- FASE 1: Momento -->
            <fieldset class="event-form__phase">
              <legend class="event-form__phase-title">
                <span class="phase-number">1</span>
                ${Drupal.t('Momento')}
              </legend>
              <div class="form-group">
                <label for="event-date">${Drupal.t('Fecha/Período')}</label>
                <input type="text" id="event-date" name="date" placeholder="${Drupal.t('Ej: 2020 o Verano 2018')}" required>
              </div>
              <div class="form-group">
                <label for="event-title">${Drupal.t('Título del evento')}</label>
                <input type="text" id="event-title" name="title" required>
              </div>
              <div class="form-group">
                <label>${Drupal.t('Tipo de momento')}</label>
                <div class="radio-group">
                  <label class="radio-label">
                    <input type="radio" name="type" value="high" checked> 
                    <span class="radio-text radio-text--high">⬆️ ${Drupal.t('Álgido')}</span>
                  </label>
                  <label class="radio-label">
                    <input type="radio" name="type" value="low"> 
                    <span class="radio-text radio-text--low">⬇️ ${Drupal.t('Bajo')}</span>
                  </label>
                </div>
              </div>
              <div class="form-group">
                <label>${Drupal.t('Categoría')}</label>
                <div class="radio-group">
                  <label class="radio-label">
                    <input type="radio" name="category" value="personal" checked> 
                    ${Drupal.t('Personal')}
                  </label>
                  <label class="radio-label">
                    <input type="radio" name="category" value="professional"> 
                    ${Drupal.t('Profesional')}
                  </label>
                </div>
              </div>
            </fieldset>

            <!-- FASE 2: Acontecimiento -->
            <fieldset class="event-form__phase">
              <legend class="event-form__phase-title">
                <span class="phase-number">2</span>
                ${Drupal.t('Acontecimiento')}
              </legend>
              <div class="form-group">
                <label for="event-description">${Drupal.t('Descripción detallada')}</label>
                <textarea id="event-description" name="description" rows="3" placeholder="${Drupal.t('¿Qué ocurrió? ¿Cómo te sentiste?')}"></textarea>
              </div>
              <div class="form-group">
                <label>${Drupal.t('Factores de satisfacción')}</label>
                <div class="checkbox-group">
                  <label class="checkbox-label">
                    <input type="checkbox" name="factors[]" value="achievement"> 
                    <svg class="factor-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="6"/><path d="M12 14v6M9 17l3 3 3-3"/></svg>
                    ${Drupal.t('Logro')}
                  </label>
                  <label class="checkbox-label">
                    <input type="checkbox" name="factors[]" value="recognition"> 
                    <svg class="factor-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12,2 15,9 22,9 17,14 19,21 12,17 5,21 7,14 2,9 9,9"/></svg>
                    ${Drupal.t('Reconocimiento')}
                  </label>
                  <label class="checkbox-label">
                    <input type="checkbox" name="factors[]" value="autonomy"> 
                    <svg class="factor-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    ${Drupal.t('Autonomía')}
                  </label>
                  <label class="checkbox-label">
                    <input type="checkbox" name="factors[]" value="purpose"> 
                    <svg class="factor-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>
                    ${Drupal.t('Propósito')}
                  </label>
                  <label class="checkbox-label">
                    <input type="checkbox" name="factors[]" value="growth"> 
                    <svg class="factor-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                    ${Drupal.t('Crecimiento')}
                  </label>
                  <label class="checkbox-label">
                    <input type="checkbox" name="factors[]" value="connection"> 
                    <svg class="factor-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    ${Drupal.t('Conexión')}
                  </label>
                </div>
              </div>
              <div class="form-group">
                <label for="event-skills">${Drupal.t('Competencias demostradas')}</label>
                <input type="text" id="event-skills" name="skills" placeholder="${Drupal.t('Ej: liderazgo, comunicación, análisis...')}">
                <small class="form-hint">${Drupal.t('Separa las competencias con comas')}</small>
              </div>
            </fieldset>

            <!-- FASE 3: Autoconocimiento -->
            <fieldset class="event-form__phase">
              <legend class="event-form__phase-title">
                <span class="phase-number">3</span>
                ${Drupal.t('Autoconocimiento')}
              </legend>
              <div class="form-group">
                <label for="event-learnings">${Drupal.t('¿Qué aprendiste de este momento?')}</label>
                <textarea id="event-learnings" name="learnings" rows="2" placeholder="${Drupal.t('Reflexiona sobre este momento...')}"></textarea>
              </div>
              <div class="form-group">
                <label for="event-values">${Drupal.t('Valores que se manifestaron')}</label>
                <input type="text" id="event-values" name="values" placeholder="${Drupal.t('Ej: honestidad, creatividad, perseverancia...')}">
              </div>
              <div class="form-group">
                <label for="event-patterns">${Drupal.t('¿Ves algún patrón?')}</label>
                <textarea id="event-patterns" name="patterns" rows="2" placeholder="${Drupal.t('¿Este momento se conecta con otros de tu vida?')}"></textarea>
              </div>
            </fieldset>
          </form>
        </div>
        <footer class="slide-panel__footer">
          <button type="button" class="btn btn--secondary" data-close-panel="${panelId}">
            ${Drupal.t('Cancelar')}
          </button>
          <button type="button" class="btn btn--primary" id="${panelId}-submit">
            ${Drupal.t('Guardar Evento')}
          </button>
        </footer>
      </div>
    `;

    setTimeout(function () {
      const submitBtn = panel.querySelector('#' + panelId + '-submit');
      if (submitBtn) {
        submitBtn.addEventListener('click', function () {
          saveEvent(panelId);
        });
      }
    }, 100);

    return panel;
  }

  /**
   * Guarda un evento.
   */
  function saveEvent(panelId) {
    const form = document.getElementById('add-event-form');
    if (!form) return;

    const formData = new FormData(form);

    // Capturar checkboxes de factores.
    const factors = [];
    form.querySelectorAll('input[name="factors[]"]:checked').forEach(cb => {
      factors.push(cb.value);
    });

    const event = {
      // Fase 1: Momento
      date: formData.get('date'),
      title: formData.get('title'),
      type: formData.get('type'),
      category: formData.get('category'),
      // Fase 2: Acontecimiento
      description: formData.get('description'),
      factors: factors,
      skills: formData.get('skills') ? formData.get('skills').split(',').map(s => s.trim()) : [],
      // Fase 3: Autoconocimiento
      learnings: formData.get('learnings'),
      values: formData.get('values') ? formData.get('values').split(',').map(v => v.trim()) : [],
      patterns: formData.get('patterns'),
      // Metadata
      id: Date.now(),
      phase: 3 // Indicar que tiene las 3 fases completas
    };

    if (!event.date || !event.title) {
      alert(Drupal.t('Por favor completa los campos obligatorios.'));
      return;
    }

    // Sync to backend API, then update localStorage.
    fetch('/api/v1/self-discovery/timeline/events?_format=json', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify(event)
    })
    .then(function (res) { return res.json(); })
    .then(function (data) {
      if (data.success && data.id) {
        event.id = data.id;
      }
    })
    .catch(function () {
      // Silently fail — localStorage fallback active.
    })
    .finally(function () {
      const events = JSON.parse(localStorage.getItem('timelineEvents') || '[]');
      events.push(event);
      localStorage.setItem('timelineEvents', JSON.stringify(events));

      const container = document.getElementById('timeline-container');
      renderEvents(container, events);
      renderTimelineChart(events, true);

      Drupal.behaviors.slidePanel.close(panelId);
      form.reset();
    });
  }

  /**
   * Renderiza los eventos en el timeline.
   */
  function renderEvents(container, events) {
    if (!container) return;

    events.sort(function (a, b) {
      return String(a.date).localeCompare(String(b.date));
    });

    let html = '<div class="tl-events">';

    events.forEach(function (event) {
      const typeClass = event.type === 'high' ? 'tl-event--high' : 'tl-event--low';
      const categoryClass = event.category === 'professional' ? 'tl-event--professional' : 'tl-event--personal';

      html += `
        <div class="tl-event ${typeClass} ${categoryClass}" data-event-id="${event.id}">
          <div class="tl-event__marker">
            ${event.type === 'high' ? '⬆️' : '⬇️'}
          </div>
          <div class="tl-event__content">
            <span class="tl-event__date">${event.date}</span>
            <h3 class="tl-event__title">${event.title}</h3>
            ${event.description ? `<p class="tl-event__description">${event.description}</p>` : ''}
            ${event.learnings ? `<p class="tl-event__learnings"><em>${event.learnings}</em></p>` : ''}
            <span class="tl-event__category">${event.category === 'professional' ? Drupal.t('Profesional') : Drupal.t('Personal')}</span>
          </div>
          <button type="button" class="tl-event__delete" onclick="deleteTimelineEvent(${event.id})" title="${Drupal.t('Eliminar')}">✕</button>
        </div>
      `;
    });

    html += '</div>';
    container.innerHTML = html;
  }

  /**
   * Elimina un evento.
   */
  window.deleteTimelineEvent = function (eventId) {
    if (!confirm(Drupal.t('¿Eliminar este evento?'))) return;

    // Sync delete to backend API.
    if (eventId && typeof eventId === 'number' && eventId < 1e12) {
      fetch('/api/v1/self-discovery/timeline/events/' + eventId, {
        method: 'DELETE',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      }).catch(function () { /* localStorage fallback */ });
    }

    let events = JSON.parse(localStorage.getItem('timelineEvents') || '[]');
    events = events.filter(function (e) { return e.id !== eventId; });
    localStorage.setItem('timelineEvents', JSON.stringify(events));

    const container = document.getElementById('timeline-container');
    if (events.length > 0) {
      renderEvents(container, events);
      renderTimelineChart(events, true);
    } else {
      container.innerHTML = `
        <div class="tl-empty-state">
          <p>${Drupal.t('Aún no has añadido eventos a tu línea de vida.')}</p>
          <p>${Drupal.t('Comienza añadiendo momentos significativos de tu historia personal y profesional.')}</p>
        </div>
      `;
      // Limpiar canvas.
      const canvas = document.getElementById('timeline-chart');
      if (canvas) {
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
      }
    }
  };

  /**
   * Renderiza el gráfico de línea de vida (versión premium).
   */
  function renderTimelineChart(events, animate) {
    const canvas = document.getElementById('timeline-chart');
    if (!canvas || events.length === 0) return;

    const ctx = canvas.getContext('2d');
    const width = canvas.width;
    const height = canvas.height;
    const paddingTop = 50;
    const paddingBottom = 80;
    const paddingLR = 60;
    const chartWidth = width - paddingLR * 2;
    const centerY = paddingTop + (height - paddingTop - paddingBottom) / 2;
    const amplitude = (height - paddingTop - paddingBottom) / 2 - 30;

    // Ordenar y calcular puntos.
    const sortedEvents = [...events].sort((a, b) => String(a.date).localeCompare(String(b.date)));
    const step = chartWidth / (sortedEvents.length + 1);

    chartPoints = sortedEvents.map((event, i) => ({
      x: paddingLR + step * (i + 1),
      y: event.type === 'high' ? centerY - amplitude : centerY + amplitude,
      event: event,
      radius: 14
    }));

    // Dibujar base.
    function drawBase() {
      ctx.clearRect(0, 0, width, height);

      // Fondo gradiente.
      const bgGrad = ctx.createLinearGradient(0, 0, 0, height);
      bgGrad.addColorStop(0, '#F0FDF4');
      bgGrad.addColorStop(0.5, '#F8FAFC');
      bgGrad.addColorStop(1, '#FEF2F2');
      ctx.fillStyle = bgGrad;
      ctx.fillRect(0, 0, width, height);

      // Zonas.
      ctx.fillStyle = 'rgba(16, 185, 129, 0.06)';
      ctx.fillRect(paddingLR, paddingTop, chartWidth, centerY - paddingTop);
      ctx.fillStyle = 'rgba(239, 68, 68, 0.06)';
      ctx.fillRect(paddingLR, centerY, chartWidth, height - centerY - paddingBottom);

      // Línea central.
      ctx.strokeStyle = '#94A3B8';
      ctx.lineWidth = 1;
      ctx.setLineDash([4, 4]);
      ctx.beginPath();
      ctx.moveTo(paddingLR, centerY);
      ctx.lineTo(width - paddingLR, centerY);
      ctx.stroke();
      ctx.setLineDash([]);

      // Labels.
      ctx.fillStyle = '#10B981';
      ctx.font = 'bold 11px Inter, sans-serif';
      ctx.textAlign = 'left';
      ctx.fillText('↑ ' + Drupal.t('Álgido'), paddingLR + 5, paddingTop + 15);
      ctx.fillStyle = '#EF4444';
      ctx.fillText('↓ ' + Drupal.t('Bajo'), paddingLR + 5, height - paddingBottom + 15);

      // Eje X.
      ctx.strokeStyle = '#CBD5E1';
      ctx.lineWidth = 2;
      ctx.beginPath();
      ctx.moveTo(paddingLR, height - paddingBottom + 30);
      ctx.lineTo(width - paddingLR, height - paddingBottom + 30);
      ctx.stroke();
    }

    // Dibujar línea con progreso.
    function drawLine(progress) {
      if (chartPoints.length === 0) return;

      const lineGrad = ctx.createLinearGradient(paddingLR, 0, width - paddingLR, 0);
      lineGrad.addColorStop(0, 'rgba(35, 61, 99, 0.7)');
      lineGrad.addColorStop(0.5, 'rgba(0, 169, 165, 0.8)');
      lineGrad.addColorStop(1, 'rgba(35, 61, 99, 0.7)');

      ctx.strokeStyle = lineGrad;
      ctx.lineWidth = 3;
      ctx.lineCap = 'round';
      ctx.lineJoin = 'round';
      ctx.beginPath();
      ctx.moveTo(paddingLR, centerY);

      const totalSegments = chartPoints.length + 1;
      const segmentsToDraw = Math.floor(progress * totalSegments);

      for (let i = 0; i < Math.min(segmentsToDraw, chartPoints.length); i++) {
        if (i === 0) {
          ctx.lineTo(chartPoints[0].x, chartPoints[0].y);
        } else {
          const prevX = chartPoints[i - 1].x;
          const prevY = chartPoints[i - 1].y;
          const currX = chartPoints[i].x;
          const currY = chartPoints[i].y;
          const cpX = (prevX + currX) / 2;
          ctx.bezierCurveTo(cpX, prevY, cpX, currY, currX, currY);
        }
      }

      if (segmentsToDraw >= chartPoints.length) {
        ctx.lineTo(width - paddingLR, centerY);
      }

      ctx.stroke();
    }

    // Dibujar puntos.
    function drawPoints() {
      chartPoints.forEach((point, i) => {
        const isHovered = hoveredPoint === i;
        const radius = isHovered ? 18 : point.radius;

        // Sombra.
        ctx.shadowColor = point.event.type === 'high' ? 'rgba(16, 185, 129, 0.4)' : 'rgba(239, 68, 68, 0.4)';
        ctx.shadowBlur = isHovered ? 15 : 8;

        // Círculo.
        ctx.beginPath();
        ctx.arc(point.x, point.y, radius, 0, Math.PI * 2);
        ctx.fillStyle = point.event.type === 'high' ? '#10B981' : '#EF4444';
        ctx.fill();
        ctx.shadowBlur = 0;
        ctx.strokeStyle = '#FFFFFF';
        ctx.lineWidth = 3;
        ctx.stroke();

        // Número.
        ctx.fillStyle = '#FFFFFF';
        ctx.font = 'bold 12px Inter, sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(i + 1, point.x, point.y);

        // Fecha.
        ctx.fillStyle = '#334155';
        ctx.font = '10px Inter, sans-serif';
        ctx.textBaseline = 'top';
        ctx.fillText(point.event.date, point.x, height - paddingBottom + 35);

        // Título.
        ctx.fillStyle = '#1E293B';
        ctx.font = isHovered ? 'bold 11px Inter, sans-serif' : '10px Inter, sans-serif';
        const maxLen = 18;
        const title = point.event.title.length > maxLen ? point.event.title.substring(0, maxLen - 1) + '…' : point.event.title;
        const titleY = point.event.type === 'high' ? point.y - 26 : point.y + 26;
        ctx.fillText(title, point.x, titleY);
      });
    }

    // Tooltip.
    function drawTooltip() {
      if (hoveredPoint === null) return;
      const point = chartPoints[hoveredPoint];
      const tooltipW = 200;
      const tooltipH = 70;
      let tooltipX = point.x - tooltipW / 2;
      let tooltipY = point.event.type === 'high' ? point.y - 80 : point.y + 35;

      if (tooltipX < 10) tooltipX = 10;
      if (tooltipX + tooltipW > width - 10) tooltipX = width - tooltipW - 10;

      ctx.shadowColor = 'rgba(0, 0, 0, 0.15)';
      ctx.shadowBlur = 10;
      ctx.fillStyle = '#FFFFFF';
      ctx.beginPath();
      ctx.roundRect(tooltipX, tooltipY, tooltipW, tooltipH, 8);
      ctx.fill();
      ctx.shadowBlur = 0;

      ctx.strokeStyle = point.event.type === 'high' ? '#10B981' : '#EF4444';
      ctx.lineWidth = 2;
      ctx.stroke();

      ctx.fillStyle = '#1E293B';
      ctx.font = 'bold 12px Inter, sans-serif';
      ctx.textAlign = 'left';
      ctx.textBaseline = 'top';
      ctx.fillText(point.event.title, tooltipX + 10, tooltipY + 10);

      ctx.fillStyle = '#64748B';
      ctx.font = '10px Inter, sans-serif';
      ctx.fillText(point.event.date + ' • ' + (point.event.type === 'high' ? Drupal.t('Momento Álgido') : Drupal.t('Momento Bajo')), tooltipX + 10, tooltipY + 28);

      if (point.event.description) {
        const desc = point.event.description.length > 40 ? point.event.description.substring(0, 37) + '...' : point.event.description;
        ctx.fillText(desc, tooltipX + 10, tooltipY + 46);
      }
    }

    // Render completo.
    function render(progress) {
      drawBase();
      drawLine(progress);
      if (progress >= 1) {
        drawPoints();
        drawTooltip();
      }
    }

    // Animación.
    if (animate && chartPoints.length > 0) {
      let start = null;
      const duration = 800;

      function animateFrame(timestamp) {
        if (!start) start = timestamp;
        const elapsed = timestamp - start;
        const progress = Math.min(elapsed / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        render(eased);

        if (progress < 1) {
          requestAnimationFrame(animateFrame);
        } else {
          drawPoints();
          drawTooltip();
        }
      }

      requestAnimationFrame(animateFrame);
    } else {
      render(1);
    }

    // Eventos de mouse.
    canvas.onmousemove = function (e) {
      const rect = canvas.getBoundingClientRect();
      const scaleX = canvas.width / rect.width;
      const scaleY = canvas.height / rect.height;
      const x = (e.clientX - rect.left) * scaleX;
      const y = (e.clientY - rect.top) * scaleY;

      let found = null;
      chartPoints.forEach((point, i) => {
        const dist = Math.sqrt((x - point.x) ** 2 + (y - point.y) ** 2);
        if (dist < 25) found = i;
      });

      if (found !== hoveredPoint) {
        hoveredPoint = found;
        canvas.style.cursor = found !== null ? 'pointer' : 'default';
        render(1);
      }
    };

    canvas.onmouseleave = function () {
      if (hoveredPoint !== null) {
        hoveredPoint = null;
        render(1);
      }
    };

    canvas.onclick = function () {
      if (hoveredPoint !== null) {
        const point = chartPoints[hoveredPoint];
        alert(Drupal.t('Evento: ') + point.event.title + '\n' + Drupal.t('Fecha: ') + point.event.date);
      }
    };
  }

  // Cargar al inicio.
  document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('timeline-container');
    if (container) {
      const events = JSON.parse(localStorage.getItem('timelineEvents') || '[]');
      if (events.length > 0) {
        renderEvents(container, events);
        renderTimelineChart(events, true);
      }
    }
  });

  window.renderTimelineChart = renderTimelineChart;

})(Drupal, drupalSettings, once);

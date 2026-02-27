/**
 * @file
 * Demo Dashboard — Chart.js, tracking de acciones y modal de conversión.
 *
 * S2-02: Extraído del inline <script> del template.
 * S1-03: CSRF token en todas las peticiones POST.
 * S1-06: Endpoints leídos de drupalSettings (no hardcoded).
 * S5-05: Modal ARIA — focus trap, Escape handler.
 * S5-09: Loading indicator en submit de conversión.
 * S5-10: Error feedback visible al usuario en conversión.
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * CSRF token cacheado por sesión de página (S5-09: evitar fetch repetido).
   */
  let cachedCsrfToken = null;

  async function getCsrfToken() {
    if (!cachedCsrfToken) {
      cachedCsrfToken = await fetch(Drupal.url('session/token')).then(function (r) { return r.text(); });
    }
    return cachedCsrfToken;
  }

  Drupal.behaviors.demoDashboard = {
    // S8-06: Cleanup en detach para liberar recursos.
    detach(context, settings, trigger) {
      if (trigger === 'unload') {
        once.remove('demo-dashboard', '[data-demo-dashboard]', context);
      }
    },
    attach(context) {
      once('demo-dashboard', '[data-demo-dashboard]', context).forEach(function (container) {
        const sessionId = container.getAttribute('data-session-id');
        const config = drupalSettings.demo || {};
        const salesHistory = config.salesHistory || [];

        // -----------------------------------------------------------------
        // S8-10: Chart.js lazy-loaded — solo carga si el canvas existe.
        // -----------------------------------------------------------------
        const chartCanvas = container.querySelector('#salesChart');
        if (salesHistory.length > 0 && chartCanvas) {
          initChart(chartCanvas, salesHistory);
        }

        // -----------------------------------------------------------------
        // Tracking de acciones (con CSRF — S1-03)
        // -----------------------------------------------------------------
        container.querySelectorAll('[data-action]').forEach(function (card) {
          card.addEventListener('click', function () {
            const action = this.getAttribute('data-action');
            trackDemoAction(sessionId, action);
          });
        });

        // -----------------------------------------------------------------
        // S5-05: Modal de conversión — ARIA focus trap + Escape
        // -----------------------------------------------------------------
        const modal = document.querySelector('[data-demo-convert-modal]');
        if (modal) {
          var focusableSelector = 'input, button, [tabindex]:not([tabindex="-1"])';
          var firstFocusable = null;
          var lastFocusable = null;

          function updateFocusableElements() {
            var focusables = modal.querySelectorAll(focusableSelector);
            var visible = Array.prototype.filter.call(focusables, function (el) {
              return !el.disabled && el.offsetParent !== null;
            });
            firstFocusable = visible[0] || null;
            lastFocusable = visible[visible.length - 1] || null;
          }

          function openModal() {
            modal.classList.add('demo-convert-modal--open');
            modal.setAttribute('aria-hidden', 'false');
            updateFocusableElements();
            if (firstFocusable) {
              firstFocusable.focus();
            }
          }

          function closeModal() {
            modal.classList.remove('demo-convert-modal--open');
            modal.setAttribute('aria-hidden', 'true');
            // Devolver el foco al botón que abrió el modal.
            var opener = document.querySelector('[data-demo-convert-open]');
            if (opener) {
              opener.focus();
            }
          }

          // Abrir modal.
          document.querySelectorAll('[data-demo-convert-open]').forEach(function (btn) {
            btn.addEventListener('click', openModal);
          });

          // Cerrar modal (botones close + overlay).
          modal.querySelectorAll('[data-demo-convert-close]').forEach(function (el) {
            el.addEventListener('click', closeModal);
          });

          // S5-05: Escape handler.
          document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modal.classList.contains('demo-convert-modal--open')) {
              closeModal();
            }
          });

          // S5-05: Focus trap.
          modal.addEventListener('keydown', function (e) {
            if (e.key !== 'Tab') {
              return;
            }
            updateFocusableElements();
            if (e.shiftKey) {
              if (document.activeElement === firstFocusable) {
                e.preventDefault();
                if (lastFocusable) lastFocusable.focus();
              }
            } else {
              if (document.activeElement === lastFocusable) {
                e.preventDefault();
                if (firstFocusable) firstFocusable.focus();
              }
            }
          });

          // Submit conversión.
          var form = modal.querySelector('[data-demo-convert-form]');
          if (form) {
            form.addEventListener('submit', function (e) {
              e.preventDefault();
              var email = modal.querySelector('[data-demo-convert-email]').value;
              convertDemo(sessionId, email, modal);
            });
          }
        }

        // -----------------------------------------------------------------
        // S7-06: Session countdown timer
        // -----------------------------------------------------------------
        var countdownEl = container.querySelector('[data-countdown-display]');
        var countdownContainer = container.querySelector('[data-demo-countdown]');
        var expiresTimestamp = config.expires || 0;

        if (countdownEl && expiresTimestamp > 0) {
          var WARNING_THRESHOLD = 300; // 5 minutos.

          function updateCountdown() {
            var now = Math.floor(Date.now() / 1000);
            var remaining = expiresTimestamp - now;

            if (remaining <= 0) {
              countdownEl.textContent = '00:00';
              if (countdownContainer) {
                countdownContainer.classList.add('demo-countdown--warning');
              }
              return;
            }

            var minutes = Math.floor(remaining / 60);
            var seconds = remaining % 60;
            countdownEl.textContent = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');

            if (remaining <= WARNING_THRESHOLD && countdownContainer) {
              countdownContainer.classList.add('demo-countdown--warning');
            }

            setTimeout(updateCountdown, 1000);
          }

          updateCountdown();
        }
      });
    },
  };

  /**
   * Registra una acción de demo via API (con CSRF).
   */
  async function trackDemoAction(sessionId, action) {
    try {
      var csrfToken = await getCsrfToken();
      fetch(Drupal.url('api/v1/demo/track'), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': csrfToken,
        },
        body: JSON.stringify({
          session_id: sessionId,
          action: action,
        }),
      });
    }
    catch (error) {
      // Silencioso — el tracking no debe bloquear la UX.
    }
  }

  /**
   * Convierte la demo a cuenta real (con CSRF).
   *
   * S5-09: Loading indicator en submit.
   * S5-10: Error feedback visible al usuario.
   */
  async function convertDemo(sessionId, email, modal) {
    var submitBtn = modal.querySelector('[data-demo-convert-submit]');
    var feedback = modal.querySelector('[data-demo-convert-feedback]');
    var originalBtnText = submitBtn ? submitBtn.textContent : '';

    // S5-09: Loading state.
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.textContent = Drupal.t('Procesando...');
    }
    if (feedback) {
      feedback.textContent = '';
      feedback.classList.remove('demo-convert-modal__feedback--error');
    }

    try {
      var csrfToken = await getCsrfToken();
      var response = await fetch(Drupal.url('api/v1/demo/convert'), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': csrfToken,
        },
        body: JSON.stringify({
          session_id: sessionId,
          email: email,
        }),
      });

      var data = await response.json();

      if (data.success && data.redirect_url) {
        window.location.href = data.redirect_url;
        return;
      }

      // S5-10: Mostrar error al usuario.
      if (feedback) {
        feedback.textContent = data.error || Drupal.t('No se pudo crear la cuenta. Inténtalo de nuevo.');
        feedback.classList.add('demo-convert-modal__feedback--error');
      }
    }
    catch (error) {
      // S5-10: Error de red.
      if (feedback) {
        feedback.textContent = Drupal.t('Error de conexión. Comprueba tu conexión a internet.');
        feedback.classList.add('demo-convert-modal__feedback--error');
      }
    }
    finally {
      // Restaurar botón.
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = originalBtnText;
      }
    }
  }

  /**
   * S8-10: Lazy-load Chart.js y renderizar gráfico de ventas.
   */
  function initChart(canvas, salesHistory) {
    function renderChart() {
      var ctx = canvas.getContext('2d');
      var primaryColor = getComputedStyle(document.documentElement)
        .getPropertyValue('--ej-color-primary').trim() || '#233D63';

      new Chart(ctx, {
        type: 'line',
        data: {
          labels: salesHistory.map(function (d) { return d.date.slice(5); }),
          datasets: [{
            label: Drupal.t('Ingresos (€)'),
            data: salesHistory.map(function (d) { return d.revenue; }),
            borderColor: primaryColor,
            backgroundColor: primaryColor + '1A',
            fill: true,
            tension: 0.4,
          }],
        },
        options: {
          responsive: true,
          animation: window.matchMedia('(prefers-reduced-motion: reduce)').matches
            ? false
            : { duration: 750 },
          plugins: { legend: { display: false } },
          scales: { y: { beginAtZero: true } },
        },
      });
    }

    // HAL-DEMO-V3-FRONT-004: Chart.js cargado via ecosistema_jaraba_core/chartjs
    // con SRI hash — ya no se necesita lazy-load manual sin integridad.
    if (typeof Chart !== 'undefined') {
      renderChart();
    }
    else {
      canvas.parentElement.innerHTML = '<p style="text-align:center;color:#999">' +
        Drupal.t('No se pudo cargar el gráfico.') + '</p>';
    }
  }

})(Drupal, drupalSettings, once);

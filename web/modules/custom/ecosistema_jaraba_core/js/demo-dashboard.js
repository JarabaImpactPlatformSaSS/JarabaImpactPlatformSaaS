/**
 * @file
 * Demo Dashboard — Chart.js, tracking de acciones y modal de conversión.
 *
 * S2-02: Extraído del inline <script> del template.
 * S1-03: CSRF token en todas las peticiones POST.
 * S1-06: Endpoints leídos de drupalSettings (no hardcoded).
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.demoDashboard = {
    attach(context) {
      once('demo-dashboard', '[data-demo-dashboard]', context).forEach(function (container) {
        const sessionId = container.getAttribute('data-session-id');
        const config = drupalSettings.demo || {};
        const salesHistory = config.salesHistory || [];

        // -----------------------------------------------------------------
        // Chart.js: Gráfico de ventas
        // -----------------------------------------------------------------
        const chartCanvas = container.querySelector('#salesChart');
        if (salesHistory.length > 0 && chartCanvas && typeof Chart !== 'undefined') {
          const ctx = chartCanvas.getContext('2d');
          // Usar colores del tema via CSS custom properties.
          const primaryColor = getComputedStyle(document.documentElement)
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
              plugins: { legend: { display: false } },
              scales: { y: { beginAtZero: true } },
            },
          });
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
        // Modal de conversión
        // -----------------------------------------------------------------
        const modal = document.querySelector('[data-demo-convert-modal]');
        if (modal) {
          // Abrir modal.
          document.querySelectorAll('[data-demo-convert-open]').forEach(function (btn) {
            btn.addEventListener('click', function () {
              modal.classList.add('demo-convert-modal--open');
            });
          });

          // Cerrar modal.
          modal.querySelectorAll('[data-demo-convert-close]').forEach(function (el) {
            el.addEventListener('click', function () {
              modal.classList.remove('demo-convert-modal--open');
            });
          });

          // Submit conversión.
          const form = modal.querySelector('[data-demo-convert-form]');
          if (form) {
            form.addEventListener('submit', function (e) {
              e.preventDefault();
              const email = modal.querySelector('[data-demo-convert-email]').value;
              convertDemo(sessionId, email);
            });
          }
        }
      });
    },
  };

  /**
   * Registra una acción de demo via API (con CSRF).
   */
  async function trackDemoAction(sessionId, action) {
    try {
      const csrfToken = await fetch(Drupal.url('session/token')).then(function (r) { return r.text(); });
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
   */
  async function convertDemo(sessionId, email) {
    try {
      const csrfToken = await fetch(Drupal.url('session/token')).then(function (r) { return r.text(); });
      const response = await fetch(Drupal.url('api/v1/demo/convert'), {
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

      const data = await response.json();

      if (data.success && data.redirect_url) {
        window.location.href = data.redirect_url;
      }
    }
    catch (error) {
      // Gestión básica de error.
    }
  }

})(Drupal, drupalSettings, once);

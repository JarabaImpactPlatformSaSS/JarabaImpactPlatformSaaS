/**
 * @file
 * Agent Dashboard — Interactividad del dashboard de agente de soporte.
 *
 * Gestiona:
 * - Filtrado de cola por estado/prioridad
 * - Claim (auto-asignación) de tickets
 * - Refresh automático via SSE
 * - Vistas guardadas
 *
 * DIRECTRICES:
 * - CSRF-JS-CACHE-001: Token cacheado
 * - INNERHTML-XSS-001: Drupal.checkPlain()
 * - ROUTE-LANGPREFIX-001: URLs via drupalSettings
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  let csrfTokenPromise = null;

  function getCsrfToken() {
    if (!csrfTokenPromise) {
      csrfTokenPromise = fetch(Drupal.url('session/token'))
        .then((r) => r.text());
    }
    return csrfTokenPromise;
  }

  /**
   * Agent Dashboard behavior.
   */
  Drupal.behaviors.supportAgentDashboard = {
    attach(context) {
      once('support-agent-dashboard', '.support-agent-dashboard', context).forEach((dashEl) => {
        initQueueFilters(dashEl);
        initClaimButtons(dashEl);
        initSavedViews(dashEl);
        initSse(dashEl);
        initRefresh(dashEl);
      });
    },
  };

  /**
   * Initializes queue filter tabs.
   */
  function initQueueFilters(dashEl) {
    const filters = dashEl.querySelectorAll('.support-agent-dashboard__filter');
    const items = dashEl.querySelectorAll('.support-queue-item');

    filters.forEach((btn) => {
      btn.addEventListener('click', () => {
        filters.forEach((f) => f.classList.remove('support-agent-dashboard__filter--active'));
        btn.classList.add('support-agent-dashboard__filter--active');

        const filter = btn.dataset.filter;
        items.forEach((item) => {
          if (filter === 'all') {
            item.style.display = '';
            return;
          }

          const status = item.dataset.status || '';
          const priority = item.dataset.priority || '';
          let visible = false;

          switch (filter) {
            case 'unassigned':
              visible = !item.querySelector('[data-support-action="claim"]')?.hidden;
              break;
            case 'mine':
              visible = item.querySelector('[data-support-action="claim"]') === null;
              break;
            case 'urgent':
              visible = priority === 'urgent' || priority === 'high';
              break;
            case 'sla-breach':
              visible = item.querySelector('.support-sla--breached') !== null;
              break;
            default:
              visible = true;
          }

          item.style.display = visible ? '' : 'none';
        });
      });
    });
  }

  /**
   * Initializes claim (auto-assign) buttons.
   */
  function initClaimButtons(dashEl) {
    dashEl.querySelectorAll('[data-support-action="claim"]').forEach((btn) => {
      btn.addEventListener('click', async (e) => {
        e.preventDefault();
        e.stopPropagation();

        const ticketId = btn.dataset.ticketId;
        if (!ticketId) return;

        try {
          btn.disabled = true;
          const token = await getCsrfToken();
          const response = await fetch(
            drupalSettings.jarabaSupport.apiBaseUrl + '/' + ticketId,
            {
              method: 'PATCH',
              headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': token },
              body: JSON.stringify({ assignee_uid: drupalSettings.user?.uid }),
            }
          );

          if (response.ok) {
            btn.closest('.support-queue-item')?.classList.add('support-queue-item--claimed');
            btn.remove();
          }
        } catch (error) {
          Drupal.announce(error.message, 'assertive');
        } finally {
          btn.disabled = false;
        }
      });
    });
  }

  /**
   * Initializes saved view selection.
   */
  function initSavedViews(dashEl) {
    dashEl.querySelectorAll('[data-view-id]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const viewId = btn.dataset.viewId;
        if (viewId) {
          const url = new URL(window.location.href);
          url.searchParams.set('view', viewId);
          window.location.href = url.toString();
        }
      });
    });
  }

  /**
   * Initializes SSE for real-time queue updates.
   */
  function initSse(dashEl) {
    const sseUrl = drupalSettings.jarabaSupport?.agentSseUrl;
    if (!sseUrl || typeof EventSource === 'undefined') return;

    const eventSource = new EventSource(sseUrl);

    eventSource.addEventListener('queue_update', () => {
      window.location.reload();
    });

    eventSource.onerror = () => {
      eventSource.close();
    };

    window.addEventListener('beforeunload', () => eventSource.close());
  }

  /**
   * Initializes manual refresh button.
   */
  function initRefresh(dashEl) {
    dashEl.querySelectorAll('[data-support-action="refresh-queue"]').forEach((btn) => {
      btn.addEventListener('click', () => {
        window.location.reload();
      });
    });
  }

})(Drupal, drupalSettings, once);

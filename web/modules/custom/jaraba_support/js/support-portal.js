/**
 * @file
 * Support Portal — Interactividad del portal de soporte.
 *
 * Gestiona:
 * - Filtrado de tickets por estado (client-side)
 * - Apertura del modal de creación vía slide-panel
 * - Búsqueda instantánea (fuzzy match en subject)
 *
 * DIRECTRICES:
 * - CSRF-JS-CACHE-001: Token cacheado para API calls
 * - INNERHTML-XSS-001: Drupal.checkPlain() para texto dinámico
 * - ROUTE-LANGPREFIX-001: URLs via drupalSettings
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  let csrfTokenPromise = null;

  /**
   * Retrieves CSRF token with caching.
   */
  function getCsrfToken() {
    if (!csrfTokenPromise) {
      csrfTokenPromise = fetch(Drupal.url('session/token'))
        .then((r) => r.text());
    }
    return csrfTokenPromise;
  }

  /**
   * Support Portal behavior.
   */
  Drupal.behaviors.supportPortal = {
    attach(context) {
      once('support-portal', '.support-portal', context).forEach((portalEl) => {
        initFilters(portalEl);
        initCreateButton(portalEl);
      });
    },
  };

  /**
   * Initializes quick filter tabs.
   */
  function initFilters(portalEl) {
    const filters = portalEl.querySelectorAll('[data-filter]');
    const ticketCards = portalEl.querySelectorAll('.support-ticket-card');

    filters.forEach((btn) => {
      btn.addEventListener('click', () => {
        // Update active state.
        filters.forEach((f) => f.classList.remove('support-portal__filter--active'));
        btn.classList.add('support-portal__filter--active');

        const filter = btn.dataset.filter;
        ticketCards.forEach((card) => {
          if (filter === 'all') {
            card.style.display = '';
          } else {
            const status = card.dataset.status || '';
            card.style.display = status.startsWith(filter) ? '' : 'none';
          }
        });
      });
    });
  }

  /**
   * Initializes the create ticket button (opens slide-panel).
   */
  function initCreateButton(portalEl) {
    const createBtns = portalEl.querySelectorAll('[data-support-action="create-ticket"]');
    createBtns.forEach((btn) => {
      btn.addEventListener('click', () => {
        const createUrl = drupalSettings.jarabaSupport?.createUrl;
        if (createUrl && typeof Drupal.slidePanelOpen === 'function') {
          Drupal.slidePanelOpen(createUrl, {
            title: Drupal.t('Create Support Ticket'),
            width: '640px',
          });
        } else if (createUrl) {
          window.location.href = createUrl;
        }
      });
    });
  }

})(Drupal, drupalSettings, once);

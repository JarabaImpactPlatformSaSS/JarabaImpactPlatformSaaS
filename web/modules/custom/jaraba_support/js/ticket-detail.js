/**
 * @file
 * Ticket Detail — Interactividad de la vista de detalle del ticket.
 *
 * Gestiona:
 * - Envío de respuestas vía API (AJAX)
 * - Toggle nota interna
 * - SSE para actualizaciones en tiempo real
 * - Acciones de agente (asignar, cerrar, merge)
 * - Encuesta CSAT interactiva
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
   * Ticket Detail behavior.
   */
  Drupal.behaviors.supportTicketDetail = {
    attach(context) {
      once('support-ticket-detail', '.support-ticket-detail', context).forEach((detailEl) => {
        initReplyForm(detailEl);
        initAgentActions(detailEl);
        initSse(detailEl);
        initCsat(detailEl);
      });
    },
  };

  /**
   * Initializes the reply form with AJAX submission.
   */
  function initReplyForm(detailEl) {
    const form = detailEl.querySelector('[data-support-reply-form]');
    if (!form) return;

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const submitBtn = form.querySelector('[type="submit"]');
      const originalText = submitBtn.innerHTML;

      try {
        submitBtn.disabled = true;
        submitBtn.innerHTML = Drupal.t('Sending...');

        const ticketId = drupalSettings.jarabaSupport?.ticketId;
        if (!ticketId) return;

        const body = form.querySelector('textarea[name="body"]').value.trim();
        if (!body) return;

        const isInternalNote = form.querySelector('[name="is_internal_note"]')?.checked || false;
        const token = await getCsrfToken();

        const response = await fetch(drupalSettings.jarabaSupport.apiBaseUrl + '/' + ticketId + '/messages', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': token,
          },
          body: JSON.stringify({
            body: body,
            is_internal_note: isInternalNote,
          }),
        });

        if (!response.ok) {
          const err = await response.json();
          throw new Error(err.message || Drupal.t('Failed to send reply'));
        }

        // Clear form and reload the page to show new message.
        form.querySelector('textarea[name="body"]').value = '';
        window.location.reload();
      } catch (error) {
        Drupal.announce(error.message, 'assertive');
      } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
      }
    });
  }

  /**
   * Initializes agent action buttons.
   */
  function initAgentActions(detailEl) {
    const ticketId = drupalSettings.jarabaSupport?.ticketId;
    if (!ticketId) return;

    // Close ticket.
    detailEl.querySelectorAll('[data-support-action="close"]').forEach((btn) => {
      btn.addEventListener('click', async () => {
        if (!confirm(Drupal.t('Are you sure you want to close this ticket?'))) return;
        try {
          const token = await getCsrfToken();
          await fetch(drupalSettings.jarabaSupport.apiBaseUrl + '/' + ticketId + '/resolve', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': token },
            body: JSON.stringify({ resolution_notes: '' }),
          });
          window.location.reload();
        } catch (error) {
          Drupal.announce(error.message, 'assertive');
        }
      });
    });

    // Assign to me (sidebar).
    detailEl.querySelectorAll('[data-support-action="assign-self"]').forEach((btn) => {
      btn.addEventListener('click', async () => {
        try {
          const token = await getCsrfToken();
          const response = await fetch(drupalSettings.jarabaSupport.apiBaseUrl + '/' + ticketId, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': token },
            body: JSON.stringify({ assignee_uid: drupalSettings.user?.uid }),
          });
          if (response.ok) window.location.reload();
        } catch (error) {
          Drupal.announce(error.message, 'assertive');
        }
      });
    });
  }

  /**
   * Initializes Server-Sent Events for real-time updates.
   */
  function initSse(detailEl) {
    const sseUrl = drupalSettings.jarabaSupport?.sseUrl;
    if (!sseUrl || typeof EventSource === 'undefined') return;

    const eventSource = new EventSource(sseUrl);

    eventSource.addEventListener('ticket_update', (e) => {
      try {
        const data = JSON.parse(e.data);
        if (data.ticket_id == drupalSettings.jarabaSupport.ticketId) {
          window.location.reload();
        }
      } catch (err) {
        // Ignore malformed events.
      }
    });

    eventSource.addEventListener('new_message', (e) => {
      try {
        const data = JSON.parse(e.data);
        if (data.ticket_id == drupalSettings.jarabaSupport.ticketId) {
          window.location.reload();
        }
      } catch (err) {
        // Ignore.
      }
    });

    eventSource.onerror = () => {
      eventSource.close();
    };

    // Cleanup on page unload.
    window.addEventListener('beforeunload', () => eventSource.close());
  }

  /**
   * Initializes CSAT survey interactions.
   */
  function initCsat(detailEl) {
    const csatEl = detailEl.querySelector('[data-support-csat]');
    if (!csatEl) return;

    // Star rating hover/select.
    const stars = csatEl.querySelectorAll('.support-csat__star');
    stars.forEach((star, index) => {
      star.addEventListener('mouseenter', () => {
        stars.forEach((s, i) => {
          s.classList.toggle('support-csat__star--active', i <= index);
        });
      });

      star.addEventListener('click', () => {
        stars.forEach((s, i) => {
          s.classList.toggle('support-csat__star--selected', i <= index);
        });
      });
    });

    // Reset hover state on mouse leave.
    const starsContainer = csatEl.querySelector('.support-csat__stars');
    if (starsContainer) {
      starsContainer.addEventListener('mouseleave', () => {
        stars.forEach((s) => {
          if (!s.classList.contains('support-csat__star--selected')) {
            s.classList.remove('support-csat__star--active');
          }
        });
      });
    }

    // CES effort scale.
    csatEl.querySelectorAll('.support-csat__effort-option').forEach((opt) => {
      opt.addEventListener('click', () => {
        csatEl.querySelectorAll('.support-csat__effort-option').forEach((o) =>
          o.classList.remove('support-csat__effort-option--selected'));
        opt.classList.add('support-csat__effort-option--selected');
      });
    });

    // Submit.
    const form = csatEl.querySelector('[data-support-csat-form]');
    if (form) {
      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const ticketId = csatEl.dataset.ticketId;
        const formData = new FormData(form);

        try {
          const token = await getCsrfToken();
          const response = await fetch(
            drupalSettings.jarabaSupport.apiBaseUrl + '/' + ticketId + '/satisfaction',
            {
              method: 'POST',
              headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': token },
              body: JSON.stringify({
                satisfaction_rating: parseInt(formData.get('satisfaction_rating'), 10),
                effort_score: parseInt(formData.get('effort_score'), 10) || null,
                satisfaction_comment: formData.get('satisfaction_comment') || '',
              }),
            }
          );

          if (response.ok) {
            form.style.display = 'none';
            csatEl.querySelector('[data-support-csat-thanks]').style.display = '';
          }
        } catch (error) {
          Drupal.announce(error.message, 'assertive');
        }
      });
    }
  }

})(Drupal, drupalSettings, once);

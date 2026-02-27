/**
 * @file
 * Ticket Create — Formulario de creación con deflexión KB.
 *
 * Gestiona:
 * - Deflexión: búsqueda automática en KB cuando el usuario escribe el subject
 * - Envío del formulario vía API
 * - Feedback visual de envío
 *
 * DIRECTRICES:
 * - CSRF-JS-CACHE-001: Token cacheado
 * - ROUTE-LANGPREFIX-001: URLs via drupalSettings
 */

(function (Drupal, drupalSettings) {
  'use strict';

  let csrfTokenPromise = null;
  let deflectionTimer = null;

  function getCsrfToken() {
    if (!csrfTokenPromise) {
      csrfTokenPromise = fetch(Drupal.url('session/token'))
        .then((r) => r.text());
    }
    return csrfTokenPromise;
  }

  /**
   * Ticket Create behavior.
   */
  Drupal.behaviors.supportTicketCreate = {
    attach(context) {
      const forms = context.querySelectorAll
        ? context.querySelectorAll('[data-support-create-form]')
        : [];

      forms.forEach((form) => {
        if (form.dataset.supportInitialized) return;
        form.dataset.supportInitialized = 'true';

        initDeflection(form);
        initFormSubmit(form);
        initCancel(form);
      });
    },
  };

  /**
   * Initializes KB deflection on subject field.
   */
  function initDeflection(form) {
    const subjectInput = form.querySelector('[data-support-deflection-trigger]');
    const container = form.closest('.support-ticket-create')
      ?.querySelector('[data-support-deflection-container]');

    if (!subjectInput || !container) return;

    subjectInput.addEventListener('input', () => {
      clearTimeout(deflectionTimer);
      const query = subjectInput.value.trim();

      if (query.length < 5) {
        container.style.display = 'none';
        return;
      }

      deflectionTimer = setTimeout(async () => {
        try {
          const apiUrl = drupalSettings.jarabaSupport?.deflectionUrl;
          if (!apiUrl) return;

          const token = await getCsrfToken();
          const response = await fetch(apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': token },
            body: JSON.stringify({ query: query }),
          });

          if (!response.ok) return;

          const data = await response.json();
          if (data.results && data.results.length > 0) {
            container.style.display = '';
            // Populate results.
            const list = container.querySelector('.support-deflection__list');
            if (list) {
              list.innerHTML = '';
              data.results.forEach((article) => {
                const li = document.createElement('li');
                li.className = 'support-deflection__item';
                li.innerHTML =
                  '<a href="' + Drupal.checkPlain(article.url) + '" ' +
                  'class="support-deflection__link" target="_blank" rel="noopener">' +
                  '<span class="support-deflection__article-title">' +
                  Drupal.checkPlain(article.title) + '</span>' +
                  (article.excerpt
                    ? '<p class="support-deflection__excerpt">' +
                      Drupal.checkPlain(article.excerpt) + '</p>'
                    : '') +
                  '</a>';
                list.appendChild(li);
              });
            }
          } else {
            container.style.display = 'none';
          }
        } catch (e) {
          // Silent fail — deflection is optional.
        }
      }, 500);
    });

    // "Problem solved" button.
    container.addEventListener('click', (e) => {
      if (e.target.closest('[data-support-action="deflection-solved"]')) {
        if (typeof Drupal.slidePanelClose === 'function') {
          Drupal.slidePanelClose();
        } else {
          window.history.back();
        }
      }
    });
  }

  /**
   * Initializes form submission via API.
   */
  function initFormSubmit(form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const submitBtn = form.querySelector('[type="submit"]');
      const originalHtml = submitBtn.innerHTML;

      try {
        submitBtn.disabled = true;
        submitBtn.innerHTML = Drupal.t('Submitting...');

        const formData = new FormData(form);
        const token = await getCsrfToken();

        const payload = {
          subject: formData.get('subject'),
          description: formData.get('description'),
          category: formData.get('category') || '',
          priority: formData.get('priority') || 'medium',
        };

        const apiUrl = drupalSettings.jarabaSupport?.apiBaseUrl;
        if (!apiUrl) throw new Error('API URL not configured');

        const response = await fetch(apiUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': token },
          body: JSON.stringify(payload),
        });

        if (!response.ok) {
          const err = await response.json();
          throw new Error(err.message || Drupal.t('Failed to create ticket'));
        }

        const result = await response.json();

        // Redirect to the new ticket or reload portal.
        if (result.data?.id) {
          window.location.href = drupalSettings.jarabaSupport.portalUrl || '/support';
        } else {
          window.location.reload();
        }
      } catch (error) {
        Drupal.announce(error.message, 'assertive');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalHtml;
      }
    });
  }

  /**
   * Initializes cancel button.
   */
  function initCancel(form) {
    form.querySelectorAll('[data-support-action="cancel"]').forEach((btn) => {
      btn.addEventListener('click', () => {
        if (typeof Drupal.slidePanelClose === 'function') {
          Drupal.slidePanelClose();
        } else {
          window.history.back();
        }
      });
    });
  }

})(Drupal, drupalSettings);

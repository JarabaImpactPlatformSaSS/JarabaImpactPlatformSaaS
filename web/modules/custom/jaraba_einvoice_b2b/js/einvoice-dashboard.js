/**
 * @file
 * E-Invoice B2B Dashboard behaviors.
 *
 * Auto-refresh stats, document actions (send), filter controls.
 * Spec: Doc 181, Section 7.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Escapes HTML to prevent XSS.
   */
  function escapeHtml(text) {
    const el = document.createElement('span');
    el.textContent = text || '';
    return el.innerHTML;
  }

  /**
   * Dashboard stats auto-refresh (30s interval).
   */
  Drupal.behaviors.einvoiceDashboardStats = {
    attach: function (context) {
      once('einvoice-stats-refresh', '[data-auto-refresh]', context).forEach(function (el) {
        var interval = parseInt(el.dataset.autoRefresh, 10) * 1000 || 30000;

        setInterval(function () {
          var tenantId = el.dataset.tenantId || '';
          var url = Drupal.url('api/v1/einvoice/dashboard');
          if (tenantId) {
            url += '?tenant_id=' + encodeURIComponent(tenantId);
          }

          fetch(url, {
            headers: { 'Accept': 'application/json' }
          })
            .then(function (resp) { return resp.json(); })
            .then(function (json) {
              if (!json.success || !json.data) return;
              var data = json.data;

              el.querySelectorAll('[data-stat]').forEach(function (card) {
                var key = card.dataset.stat;
                var valueEl = card.querySelector('.einvoice-stats__value');
                if (valueEl && data[key] !== undefined) {
                  valueEl.textContent = data[key];
                }
                if (key === 'overdue') {
                  card.classList.toggle('einvoice-stats__card--alert', data[key] > 0);
                  card.classList.toggle('einvoice-stats__card--ok', data[key] === 0);
                }
              });
            })
            .catch(function () { /* Silently fail on refresh. */ });
        }, interval);
      });
    }
  };

  /**
   * Document send action.
   */
  Drupal.behaviors.einvoiceDocumentActions = {
    attach: function (context) {
      once('einvoice-send-action', '[data-action="send"]', context).forEach(function (btn) {
        btn.addEventListener('click', function (e) {
          e.preventDefault();
          var documentId = btn.dataset.documentId;
          if (!documentId) return;

          btn.disabled = true;
          btn.textContent = Drupal.t('Sending...');

          var csrfUrl = Drupal.url('session/token');
          fetch(csrfUrl)
            .then(function (resp) { return resp.text(); })
            .then(function (token) {
              return fetch(Drupal.url('api/v1/einvoice/send/' + documentId), {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json',
                  'X-CSRF-Token': token,
                  'Accept': 'application/json'
                },
                body: JSON.stringify({})
              });
            })
            .then(function (resp) { return resp.json(); })
            .then(function (json) {
              if (json.success) {
                btn.textContent = Drupal.t('Sent');
                btn.classList.add('einvoice-detail__action--success');
              } else {
                btn.textContent = Drupal.t('Failed');
                btn.disabled = false;
                var msg = json.meta && json.meta.error ? json.meta.error : Drupal.t('Send failed.');
                alert(escapeHtml(msg));
              }
            })
            .catch(function () {
              btn.textContent = Drupal.t('Error');
              btn.disabled = false;
            });
        });
      });
    }
  };

})(Drupal, once);

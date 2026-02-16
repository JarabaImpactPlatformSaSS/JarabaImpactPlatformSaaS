/**
 * @file
 * Facturae dashboard behaviors.
 *
 * Handles:
 * - Auto-refresh of statistics (30s interval).
 * - Document filter form submission.
 * - Sign/Send actions on document detail.
 * - Documents table population from API.
 *
 * Spec: Doc 180, Seccion 7.
 * Plan: FASE 8, entregable F8-4.
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Auto-refresh dashboard stats every 30 seconds.
   */
  Drupal.behaviors.facturaeDashboardStats = {
    attach: function (context) {
      once('facturae-stats-refresh', '.facturae-stats', context).forEach(function (el) {
        var interval = setInterval(function () {
          if (document.hidden) {
            return;
          }
          fetch('/api/v1/facturae/documents?limit=0', {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
          })
            .then(function (response) { return response.json(); })
            .then(function (data) {
              if (!data.success) {
                return;
              }
              var total = data.meta && data.meta.total ? data.meta.total : 0;
              var totalEl = el.querySelector('[data-stat="total"]');
              if (totalEl) {
                totalEl.textContent = total;
              }
            })
            .catch(function () {
              // Silent failure — next interval will retry.
            });
        }, 30000);

        // Cleanup on page unload.
        window.addEventListener('beforeunload', function () {
          clearInterval(interval);
        });
      });
    }
  };

  /**
   * Document filter handlers for documents listing page.
   */
  Drupal.behaviors.facturaeDocumentFilters = {
    attach: function (context) {
      once('facturae-filters', '[data-action="apply-filters"]', context).forEach(function (btn) {
        btn.addEventListener('click', function () {
          var params = new URLSearchParams();
          var filters = document.querySelectorAll('[data-filter]');
          filters.forEach(function (filter) {
            var value = filter.value;
            if (value) {
              params.set(filter.getAttribute('data-filter'), value);
            }
          });
          loadDocuments(params);
        });
      });

      // Pagination handlers.
      once('facturae-pagination', '[data-action="prev-page"], [data-action="next-page"]', context).forEach(function (btn) {
        btn.addEventListener('click', function () {
          var action = btn.getAttribute('data-action');
          var currentOffset = parseInt(btn.closest('.facturae-documents__pagination').getAttribute('data-offset') || '0', 10);
          var limit = 20;
          var newOffset = action === 'next-page' ? currentOffset + limit : Math.max(0, currentOffset - limit);
          var params = new URLSearchParams(window.location.search);
          params.set('offset', newOffset.toString());
          loadDocuments(params);
        });
      });

      // Initial load if on documents page.
      once('facturae-initial-load', '#facturae-documents-body', context).forEach(function () {
        loadDocuments(new URLSearchParams());
      });
    }
  };

  /**
   * Sign and Send to FACe action handlers.
   */
  Drupal.behaviors.facturaeDocumentActions = {
    attach: function (context) {
      once('facturae-sign', '[data-action="sign"]', context).forEach(function (btn) {
        btn.addEventListener('click', function () {
          var docId = btn.getAttribute('data-document-id');
          btn.disabled = true;
          btn.textContent = Drupal.t('Signing...');

          fetch('/api/v1/facturae/documents/' + docId + '/sign', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
              'Accept': 'application/json',
              'X-CSRF-Token': drupalSettings.jaraba_facturae ? drupalSettings.jaraba_facturae.csrfToken : ''
            }
          })
            .then(function (response) { return response.json(); })
            .then(function (data) {
              if (data.success) {
                window.location.reload();
              } else {
                btn.disabled = false;
                btn.textContent = Drupal.t('Sign Document');
                alert(Drupal.t('Signing failed: @error', { '@error': data.message || 'Unknown error' }));
              }
            })
            .catch(function () {
              btn.disabled = false;
              btn.textContent = Drupal.t('Sign Document');
            });
        });
      });

      once('facturae-send', '[data-action="send-face"]', context).forEach(function (btn) {
        btn.addEventListener('click', function () {
          var docId = btn.getAttribute('data-document-id');
          if (!confirm(Drupal.t('Send this document to FACe? This action cannot be undone.'))) {
            return;
          }
          btn.disabled = true;
          btn.textContent = Drupal.t('Sending...');

          fetch('/api/v1/facturae/documents/' + docId + '/send', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
              'Accept': 'application/json',
              'X-CSRF-Token': drupalSettings.jaraba_facturae ? drupalSettings.jaraba_facturae.csrfToken : ''
            }
          })
            .then(function (response) { return response.json(); })
            .then(function (data) {
              if (data.success) {
                window.location.reload();
              } else {
                btn.disabled = false;
                btn.textContent = Drupal.t('Send to FACe');
                alert(Drupal.t('FACe submission failed: @error', { '@error': data.message || 'Unknown error' }));
              }
            })
            .catch(function () {
              btn.disabled = false;
              btn.textContent = Drupal.t('Send to FACe');
            });
        });
      });
    }
  };

  /**
   * XML preview tab switching.
   */
  Drupal.behaviors.facturaeXmlPreview = {
    attach: function (context) {
      once('facturae-xml-tabs', '[data-xml-tab]', context).forEach(function (tab) {
        tab.addEventListener('click', function () {
          var target = tab.getAttribute('data-xml-tab');
          // Deactivate all tabs.
          document.querySelectorAll('[data-xml-tab]').forEach(function (t) {
            t.classList.remove('facturae-xml-preview__tab--active');
            t.setAttribute('aria-selected', 'false');
          });
          // Hide all content.
          document.querySelectorAll('[data-xml-content]').forEach(function (c) {
            c.classList.add('facturae-xml-preview__content--hidden');
          });
          // Activate selected.
          tab.classList.add('facturae-xml-preview__tab--active');
          tab.setAttribute('aria-selected', 'true');
          var content = document.querySelector('[data-xml-content="' + target + '"]');
          if (content) {
            content.classList.remove('facturae-xml-preview__content--hidden');
          }
        });
      });
    }
  };

  /**
   * Loads documents from API and populates the table.
   */
  function loadDocuments(params) {
    if (!params.has('limit')) {
      params.set('limit', '20');
    }

    fetch('/api/v1/facturae/documents?' + params.toString(), {
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json' }
    })
      .then(function (response) { return response.json(); })
      .then(function (data) {
        if (!data.success) {
          return;
        }

        var tbody = document.getElementById('facturae-documents-body');
        if (!tbody) {
          return;
        }

        tbody.innerHTML = '';
        var documents = data.data || [];

        documents.forEach(function (doc) {
          var row = document.createElement('tr');
          row.innerHTML =
            '<td><a href="/admin/jaraba/fiscal/facturae/documents/' + doc.id + '">' + escapeHtml(doc.facturae_number || '—') + '</a></td>' +
            '<td>' + escapeHtml(doc.buyer_name || '—') + '</td>' +
            '<td class="facturae-documents__amount">' + escapeHtml(doc.total_invoice_amount || '0.00') + ' &euro;</td>' +
            '<td>' + escapeHtml(doc.issue_date || '—') + '</td>' +
            '<td><span class="fiscal-badge fiscal-badge--' + escapeHtml(doc.status || 'draft') + ' fiscal-badge--sm">' + escapeHtml(doc.status || 'draft') + '</span></td>' +
            '<td><span class="fiscal-badge fiscal-badge--' + escapeHtml(doc.face_status || 'not_sent') + ' fiscal-badge--sm">' + escapeHtml(doc.face_status || 'not_sent') + '</span></td>' +
            '<td class="facturae-documents__col-hide-mobile">' + escapeHtml(doc.signature_status || 'unsigned') + '</td>' +
            '<td class="facturae-documents__col-hide-mobile">' + escapeHtml(doc.face_registry_number || '—') + '</td>' +
            '<td><a href="/admin/jaraba/fiscal/facturae/documents/' + doc.id + '">' + Drupal.t('View') + '</a></td>';
          tbody.appendChild(row);
        });

        // Update pagination.
        var total = data.meta ? data.meta.total : 0;
        var offset = parseInt(params.get('offset') || '0', 10);
        var limit = parseInt(params.get('limit') || '20', 10);
        var pageInfo = document.getElementById('facturae-page-info');
        if (pageInfo) {
          var start = offset + 1;
          var end = Math.min(offset + limit, total);
          pageInfo.textContent = start + '-' + end + ' / ' + total;
        }

        var prevBtn = document.querySelector('[data-action="prev-page"]');
        var nextBtn = document.querySelector('[data-action="next-page"]');
        if (prevBtn) {
          prevBtn.disabled = offset <= 0;
        }
        if (nextBtn) {
          nextBtn.disabled = offset + limit >= total;
        }

        var pagination = document.querySelector('.facturae-documents__pagination');
        if (pagination) {
          pagination.setAttribute('data-offset', offset.toString());
        }
      })
      .catch(function () {
        // Silent failure.
      });
  }

  /**
   * Escapes HTML to prevent XSS.
   */
  function escapeHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
  }

})(Drupal, drupalSettings, once);

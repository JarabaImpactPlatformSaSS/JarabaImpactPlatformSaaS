/**
 * @file
 * E-Invoice delivery status updates and morosity interactions.
 *
 * Handles delivery status polling and morosity report interactions.
 * Spec: Doc 181, Section 7.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Delivery status polling for pending documents.
   */
  Drupal.behaviors.einvoiceDeliveryStatus = {
    attach: function (context) {
      once('einvoice-delivery-poll', '.einvoice-delivery[data-document-id]', context).forEach(function (el) {
        var documentId = el.dataset.documentId;
        var status = el.dataset.status || 'pending';

        if (status !== 'pending' && status !== 'sent') return;

        var pollInterval = setInterval(function () {
          fetch(Drupal.url('api/v1/einvoice/documents/' + documentId), {
            headers: { 'Accept': 'application/json' }
          })
            .then(function (resp) { return resp.json(); })
            .then(function (json) {
              if (!json.success || !json.data) return;
              var data = json.data;

              var statusEl = el.querySelector('.einvoice-delivery__status-text');
              if (statusEl) {
                statusEl.textContent = data.delivery_status;
              }

              el.className = el.className.replace(/einvoice-delivery__indicator--\w+/, '');
              var indicator = el.querySelector('.einvoice-delivery__indicator');
              if (indicator) {
                indicator.className = 'einvoice-delivery__indicator einvoice-delivery__indicator--' + data.delivery_status;
              }

              if (data.delivery_status === 'delivered' || data.delivery_status === 'failed') {
                clearInterval(pollInterval);
              }
            })
            .catch(function () { /* Silent. */ });
        }, 15000);
      });
    }
  };

  /**
   * Morosity report — severity row highlighting.
   */
  Drupal.behaviors.einvoiceMorosityReport = {
    attach: function (context) {
      once('einvoice-morosity-rows', '.einvoice-morosity__row', context).forEach(function (row) {
        row.addEventListener('click', function () {
          var invoiceNumber = row.querySelector('td:first-child');
          if (invoiceNumber) {
            row.classList.toggle('einvoice-morosity__row--selected');
          }
        });
      });
    }
  };

})(Drupal, once);

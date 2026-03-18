/**
 * @file
 * bizum-checkout.js — Inicia flujo de pago Bizum via Redsys.
 *
 * Conecta el botón [data-bizum-trigger] con el endpoint
 * /api/v1/payments/bizum/initiate. Al recibir la respuesta,
 * redirige al usuario a la pasarela Redsys para completar Bizum.
 *
 * Directrices:
 * - CSRF-API-001: Token de /session/token.
 * - ROUTE-LANGPREFIX-001: endpoint via data attribute.
 * - Drupal.t() para traducciones JS.
 * - Vanilla JS + Drupal.behaviors.
 */
(function (Drupal, once) {

  'use strict';

  Drupal.behaviors.bizumCheckout = {
    attach: function (context) {
      var buttons = once('bizum-checkout', '[data-bizum-trigger]', context);

      buttons.forEach(function (button) {
        button.addEventListener('click', function () {
          var btn = this;
          var amount = parseFloat(btn.getAttribute('data-bizum-amount'));
          var orderRef = btn.getAttribute('data-bizum-order-ref');
          var endpoint = btn.getAttribute('data-bizum-endpoint');

          if (!amount || !orderRef || !endpoint) {
            return;
          }

          btn.disabled = true;
          btn.classList.add('is-loading');
          var originalHTML = btn.innerHTML;
          btn.textContent = Drupal.t('Conectando con Bizum...');

          fetch('/session/token')
            .then(function (res) { return res.text(); })
            .then(function (csrfToken) {
              return fetch(endpoint, {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json',
                  'X-CSRF-Token': csrfToken,
                },
                body: JSON.stringify({
                  amount: amount,
                  order_ref: orderRef,
                }),
              });
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
              if (data.form_url && data.ds_merchant_parameters && data.ds_signature) {
                // Crear form oculto y submit a Redsys.
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = data.form_url;
                form.style.display = 'none';

                var fields = {
                  'Ds_SignatureVersion': data.ds_signature_version || 'HMAC_SHA256_V1',
                  'Ds_MerchantParameters': data.ds_merchant_parameters,
                  'Ds_Signature': data.ds_signature,
                };

                Object.keys(fields).forEach(function (name) {
                  var input = document.createElement('input');
                  input.type = 'hidden';
                  input.name = name;
                  input.value = fields[name];
                  form.appendChild(input);
                });

                document.body.appendChild(form);
                form.submit();
              }
              else {
                btn.disabled = false;
                btn.classList.remove('is-loading');
                btn.innerHTML = originalHTML;
                Drupal.announce(
                  data.error || Drupal.t('No se pudo iniciar el pago Bizum.'),
                  'assertive'
                );
              }
            })
            .catch(function () {
              btn.disabled = false;
              btn.classList.remove('is-loading');
              btn.innerHTML = originalHTML;
              Drupal.announce(
                Drupal.t('Error de conexión. Inténtalo de nuevo.'),
                'assertive'
              );
            });
        });
      });
    },
  };

})(Drupal, once);

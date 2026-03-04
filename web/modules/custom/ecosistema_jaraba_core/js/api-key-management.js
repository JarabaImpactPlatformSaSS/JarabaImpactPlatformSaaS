/**
 * @file
 * API Key management behaviors.
 *
 * GAP-API-KEYS: Self-service API key CRUD.
 * CSRF-JS-CACHE-001: CSRF token from /session/token.
 * ROUTE-LANGPREFIX-001: URLs via drupalSettings.
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  var csrfToken = null;

  function getCsrfToken() {
    if (csrfToken) {
      return Promise.resolve(csrfToken);
    }
    return fetch(Drupal.url('session/token'))
      .then(function (r) { return r.text(); })
      .then(function (token) {
        csrfToken = token;
        return token;
      });
  }

  Drupal.behaviors.apiKeyManagement = {
    attach: function (context) {
      var containers = once('api-key-mgmt', '[data-api-keys]', context);
      if (!containers.length) return;

      containers.forEach(function (container) {
        var createBtn = container.querySelector('[data-api-keys-create]');
        var form = container.querySelector('[data-api-keys-form]');
        var newKeyDisplay = container.querySelector('[data-api-keys-new]');
        var labelInput = container.querySelector('[data-api-key-label]');
        var scopeSelect = container.querySelector('[data-api-key-scope]');
        var submitBtn = container.querySelector('[data-api-key-submit]');
        var cancelBtn = container.querySelector('[data-api-key-cancel]');
        var dismissBtn = container.querySelector('[data-api-key-dismiss]');
        var copyBtn = container.querySelector('[data-api-key-copy]');
        var keyValueEl = container.querySelector('[data-api-key-value]');

        var baseUrl = (drupalSettings.apiKeys && drupalSettings.apiKeys.baseUrl)
          ? drupalSettings.apiKeys.baseUrl
          : '/api/v1/api-keys';

        // Show create form.
        if (createBtn) {
          createBtn.addEventListener('click', function () {
            form.hidden = false;
            labelInput.value = '';
            labelInput.focus();
          });
        }

        // Cancel create.
        if (cancelBtn) {
          cancelBtn.addEventListener('click', function () {
            form.hidden = true;
          });
        }

        // Submit create.
        if (submitBtn) {
          submitBtn.addEventListener('click', function () {
            var label = labelInput.value.trim();
            if (!label) {
              labelInput.focus();
              return;
            }

            submitBtn.disabled = true;

            getCsrfToken().then(function (token) {
              return fetch(baseUrl, {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json',
                  'X-CSRF-Token': token
                },
                body: JSON.stringify({
                  label: label,
                  scope: scopeSelect.value
                })
              });
            })
              .then(function (r) { return r.json(); })
              .then(function (data) {
                submitBtn.disabled = false;
                if (data.success && data.data && data.data.key) {
                  form.hidden = true;
                  keyValueEl.textContent = data.data.key;
                  newKeyDisplay.hidden = false;
                }
              })
              .catch(function () {
                submitBtn.disabled = false;
              });
          });
        }

        // Dismiss new key display.
        if (dismissBtn) {
          dismissBtn.addEventListener('click', function () {
            newKeyDisplay.hidden = true;
            window.location.reload();
          });
        }

        // Copy to clipboard.
        if (copyBtn) {
          copyBtn.addEventListener('click', function () {
            var text = keyValueEl.textContent;
            if (navigator.clipboard) {
              navigator.clipboard.writeText(text);
            }
          });
        }

        // Revoke buttons.
        container.querySelectorAll('[data-api-key-revoke]').forEach(function (btn) {
          btn.addEventListener('click', function () {
            var keyId = this.getAttribute('data-api-key-revoke');
            if (!confirm(Drupal.t('¿Revocar esta API key? Las aplicaciones que la usen dejarán de funcionar.'))) {
              return;
            }

            getCsrfToken().then(function (token) {
              return fetch(baseUrl + '/' + keyId + '/revoke', {
                method: 'POST',
                headers: { 'X-CSRF-Token': token }
              });
            })
              .then(function (r) { return r.json(); })
              .then(function (data) {
                if (data.success) {
                  var row = container.querySelector('[data-key-row="' + keyId + '"]');
                  if (row) row.remove();
                }
              });
          });
        });

        // Rotate buttons.
        container.querySelectorAll('[data-api-key-rotate]').forEach(function (btn) {
          btn.addEventListener('click', function () {
            var keyId = this.getAttribute('data-api-key-rotate');
            if (!confirm(Drupal.t('¿Rotar esta API key? La key anterior dejará de funcionar inmediatamente.'))) {
              return;
            }

            getCsrfToken().then(function (token) {
              return fetch(baseUrl + '/' + keyId + '/rotate', {
                method: 'POST',
                headers: { 'X-CSRF-Token': token }
              });
            })
              .then(function (r) { return r.json(); })
              .then(function (data) {
                if (data.success && data.data && data.data.key) {
                  keyValueEl.textContent = data.data.key;
                  newKeyDisplay.hidden = false;
                }
              });
          });
        });
      });
    }
  };

})(Drupal, drupalSettings, once);

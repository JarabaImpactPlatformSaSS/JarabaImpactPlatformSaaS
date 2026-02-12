/**
 * @file
 * JavaScript for the domain management page.
 *
 * Handles add domain AJAX, DNS verification, token copy to clipboard
 * and domain removal confirmation.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Domain management behavior.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.jarabaDomainManagement = {
    attach: function (context) {
      once('domain-management', '.domain-management', context).forEach(function (root) {
        var tenantId = root.getAttribute('data-tenant-id');

        // --- Add domain ---
        var addBtn = root.querySelector('#add-domain-btn');
        var domainInput = root.querySelector('#new-domain-input');
        var feedback = root.querySelector('#add-domain-feedback');

        if (addBtn && domainInput) {
          addBtn.addEventListener('click', function () {
            var domain = domainInput.value.trim();

            if (!domain) {
              showFeedback(feedback, Drupal.t('Please enter a domain name.'), 'error');
              return;
            }

            // Basic format validation.
            var domainPattern = /^[a-zA-Z0-9]([a-zA-Z0-9\-]*[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9\-]*[a-zA-Z0-9])?)+$/;
            if (!domainPattern.test(domain)) {
              showFeedback(feedback, Drupal.t('Invalid domain format. Example: app.example.com'), 'error');
              return;
            }

            addBtn.disabled = true;
            showFeedback(feedback, Drupal.t('Adding domain...'), 'info');

            fetch('/api/v1/whitelabel/domains', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json'
              },
              body: JSON.stringify({
                tenant_id: parseInt(tenantId, 10),
                domain: domain
              })
            })
              .then(function (response) {
                return response.json().then(function (data) {
                  return { status: response.status, data: data };
                });
              })
              .then(function (result) {
                if (result.status === 201 && result.data.status === 'ok') {
                  showFeedback(feedback, Drupal.t('Domain added successfully. Refresh to see the updated list.'), 'success');
                  domainInput.value = '';
                }
                else {
                  showFeedback(feedback, result.data.message || Drupal.t('Failed to add domain.'), 'error');
                }
              })
              .catch(function () {
                showFeedback(feedback, Drupal.t('An error occurred. Please try again.'), 'error');
              })
              .finally(function () {
                addBtn.disabled = false;
              });
          });
        }

        // --- Verify DNS buttons ---
        root.querySelectorAll('.domain-management__btn--verify').forEach(function (btn) {
          btn.addEventListener('click', function () {
            var domainId = btn.getAttribute('data-domain-id');
            btn.disabled = true;
            btn.textContent = Drupal.t('Verifying...');

            // Toggle DNS instructions visibility.
            var instructionsRow = root.querySelector('[data-dns-for="' + domainId + '"]');
            if (instructionsRow) {
              instructionsRow.style.display = instructionsRow.style.display === 'none' ? '' : 'none';
            }

            // In a full implementation, this would call a verification endpoint.
            // Simulate a brief delay then reset the button.
            setTimeout(function () {
              btn.disabled = false;
              btn.textContent = Drupal.t('Verify DNS');
            }, 2000);
          });
        });

        // --- Copy verification token ---
        root.querySelectorAll('.domain-management__btn--copy-token').forEach(function (btn) {
          btn.addEventListener('click', function () {
            var token = btn.getAttribute('data-token');
            if (!token) {
              return;
            }

            if (navigator.clipboard && navigator.clipboard.writeText) {
              navigator.clipboard.writeText(token).then(function () {
                var originalText = btn.textContent;
                btn.textContent = Drupal.t('Copied!');
                setTimeout(function () {
                  btn.textContent = originalText;
                }, 2000);
              });
            }
            else {
              // Fallback for older browsers.
              var textarea = document.createElement('textarea');
              textarea.value = token;
              textarea.style.position = 'fixed';
              textarea.style.left = '-9999px';
              document.body.appendChild(textarea);
              textarea.select();
              document.execCommand('copy');
              document.body.removeChild(textarea);

              var originalText = btn.textContent;
              btn.textContent = Drupal.t('Copied!');
              setTimeout(function () {
                btn.textContent = originalText;
              }, 2000);
            }
          });
        });

        // --- Remove domain ---
        root.querySelectorAll('.domain-management__btn--remove').forEach(function (btn) {
          btn.addEventListener('click', function () {
            var domainName = btn.getAttribute('data-domain-name');
            var confirmed = confirm(
              Drupal.t('Are you sure you want to remove the domain "@domain"? This action cannot be undone.', {
                '@domain': domainName
              })
            );

            if (!confirmed) {
              return;
            }

            btn.disabled = true;
            btn.textContent = Drupal.t('Removing...');

            // In a full implementation, this would call a DELETE endpoint.
            // For now, log the action.
            if (typeof console !== 'undefined') {
              console.log('Remove domain:', btn.getAttribute('data-domain-id'));
            }

            setTimeout(function () {
              btn.disabled = false;
              btn.textContent = Drupal.t('Remove');
            }, 1500);
          });
        });

        /**
         * Displays feedback text with a style class.
         *
         * @param {HTMLElement|null} el - The feedback container.
         * @param {string} message - The message to display.
         * @param {string} type - One of 'success', 'error', 'info'.
         */
        function showFeedback(el, message, type) {
          if (!el) {
            return;
          }
          el.textContent = message;
          el.className = 'domain-management__add-feedback domain-management__add-feedback--' + type;
        }
      });
    }
  };

})(Drupal, once);

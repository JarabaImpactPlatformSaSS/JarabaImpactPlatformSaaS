/**
 * @file
 * Inline AI sparkle button triggers for entity forms.
 *
 * GAP-AUD-009: Attaches sparkle buttons to form fields declared in
 * drupalSettings.inlineAiFields. Clicking a sparkle fetches AI suggestions
 * and renders them as clickable chips.
 *
 * Directives:
 * - CSRF-JS-CACHE-001: Cached CSRF token promise.
 * - ROUTE-LANGPREFIX-001: Drupal.url() for all fetch URLs.
 * - INNERHTML-XSS-001: Drupal.checkPlain() for all user-visible text.
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  // Cached CSRF token promise (CSRF-JS-CACHE-001).
  let csrfTokenPromise = null;

  function getCsrfToken() {
    if (!csrfTokenPromise) {
      csrfTokenPromise = fetch(Drupal.url('session/token'))
        .then(function (response) { return response.text(); });
    }
    return csrfTokenPromise;
  }

  Drupal.behaviors.inlineAiTrigger = {
    attach: function (context) {
      var fields = drupalSettings.inlineAiFields || [];
      var entityType = drupalSettings.inlineAiEntityType || '';

      if (!fields.length || !entityType) {
        return;
      }

      fields.forEach(function (fieldName) {
        // Find the field input/textarea element.
        var selectors = [
          '[name="' + fieldName + '[0][value]"]',
          '[name="' + fieldName + '"]',
          '#edit-' + fieldName.replace(/_/g, '-') + '-0-value',
          '#edit-' + fieldName.replace(/_/g, '-')
        ];

        var fieldEl = null;
        for (var i = 0; i < selectors.length; i++) {
          var candidates = context.querySelectorAll(selectors[i]);
          if (candidates.length > 0) {
            fieldEl = candidates[0];
            break;
          }
        }

        if (!fieldEl) {
          return;
        }

        // Use once() to prevent duplicate buttons.
        var onceResult = once('inline-ai', fieldEl, context);
        if (!onceResult.length) {
          return;
        }

        // Create sparkle button.
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'inline-ai-trigger';
        btn.setAttribute('aria-label', Drupal.t('AI suggestions for @field', { '@field': fieldName }));
        btn.innerHTML = '&#10022;'; // Unicode sparkle ✦
        btn.title = Drupal.t('Get AI suggestions');

        // Insert button next to the field.
        var wrapper = fieldEl.closest('.form-item') || fieldEl.parentNode;
        wrapper.style.position = 'relative';
        wrapper.appendChild(btn);

        // Click handler.
        btn.addEventListener('click', function () {
          // Remove any existing panel.
          var existingPanel = wrapper.querySelector('.inline-ai-panel');
          if (existingPanel) {
            existingPanel.remove();
            return;
          }

          btn.classList.add('is-loading');

          getCsrfToken().then(function (token) {
            return fetch(Drupal.url('api/v1/inline-ai/suggest'), {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': token
              },
              body: JSON.stringify({
                field: fieldName,
                value: fieldEl.value || '',
                entity_type: entityType,
                context: {}
              })
            });
          }).then(function (response) {
            btn.classList.remove('is-loading');
            if (!response.ok) {
              throw new Error('HTTP ' + response.status);
            }
            return response.json();
          }).then(function (data) {
            var suggestions = data.suggestions || [];
            if (!suggestions.length) {
              return;
            }

            // Create suggestion panel.
            var panel = document.createElement('div');
            panel.className = 'inline-ai-panel';

            suggestions.forEach(function (suggestion) {
              var chip = document.createElement('button');
              chip.type = 'button';
              chip.className = 'inline-ai-chip';
              // XSS protection: Drupal.checkPlain() (INNERHTML-XSS-001).
              chip.textContent = suggestion;

              chip.addEventListener('click', function () {
                fieldEl.value = suggestion;
                fieldEl.dispatchEvent(new Event('change', { bubbles: true }));
                fieldEl.dispatchEvent(new Event('input', { bubbles: true }));
                panel.remove();
              });

              panel.appendChild(chip);
            });

            wrapper.appendChild(panel);
          }).catch(function (error) {
            btn.classList.remove('is-loading');
            // Silent fail — logged server-side.
          });
        });
      });
    }
  };

})(Drupal, drupalSettings, once);

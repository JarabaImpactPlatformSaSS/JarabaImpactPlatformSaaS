/**
 * @file
 * Domain Guide — interactive behaviors for /my-settings/domain.
 *
 * Copy-to-clipboard, provider accordion, FAQ toggle.
 * ROUTE-LANGPREFIX-001: No hardcoded URLs.
 * INNERHTML-XSS-001: No user data via innerHTML.
 */
(function (Drupal) {
  'use strict';

  Drupal.behaviors.domainGuide = {
    attach: function (context) {

      // ── Copy to clipboard ───────────────────────────────────────────────
      var copyBtns = context.querySelectorAll
        ? context.querySelectorAll('[data-domain-copy]')
        : [];

      copyBtns.forEach(function (btn) {
        if (btn.dataset.domainGuideBound) {
          return;
        }
        btn.dataset.domainGuideBound = '1';

        btn.addEventListener('click', function (e) {
          e.preventDefault();
          var value = btn.dataset.domainCopy;
          if (!value) {
            return;
          }

          if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(value).then(function () {
              showCopyFeedback(btn);
            });
          } else {
            // Fallback for older browsers.
            var textarea = document.createElement('textarea');
            textarea.value = value;
            textarea.style.position = 'fixed';
            textarea.style.left = '-9999px';
            document.body.appendChild(textarea);
            textarea.select();
            try {
              document.execCommand('copy');
              showCopyFeedback(btn);
            } catch (_) {
              // Silently fail.
            }
            document.body.removeChild(textarea);
          }
        });
      });

      function showCopyFeedback(btn) {
        btn.classList.add('domain-guide__copy-btn--copied');
        btn.setAttribute('aria-label', Drupal.t('Copiado'));

        setTimeout(function () {
          btn.classList.remove('domain-guide__copy-btn--copied');
          btn.setAttribute('aria-label', Drupal.t('Copiar valor CNAME'));
        }, 2000);
      }

      // ── Provider accordion ──────────────────────────────────────────────
      var providerToggles = context.querySelectorAll
        ? context.querySelectorAll('[data-domain-provider-toggle]')
        : [];

      providerToggles.forEach(function (toggle) {
        if (toggle.dataset.domainGuideBound) {
          return;
        }
        toggle.dataset.domainGuideBound = '1';

        toggle.addEventListener('click', function () {
          var providerId = toggle.dataset.domainProviderToggle;
          var body = document.getElementById('provider-' + providerId);
          if (!body) {
            return;
          }

          var isOpen = toggle.getAttribute('aria-expanded') === 'true';

          // Close all other providers.
          providerToggles.forEach(function (otherToggle) {
            var otherId = otherToggle.dataset.domainProviderToggle;
            var otherBody = document.getElementById('provider-' + otherId);
            if (otherToggle !== toggle && otherBody) {
              otherToggle.setAttribute('aria-expanded', 'false');
              otherBody.hidden = true;
              otherToggle.closest('.domain-guide__provider').classList.remove('domain-guide__provider--open');
            }
          });

          // Toggle current.
          toggle.setAttribute('aria-expanded', String(!isOpen));
          body.hidden = isOpen;
          toggle.closest('.domain-guide__provider').classList.toggle('domain-guide__provider--open', !isOpen);
        });
      });

      // ── FAQ accordion ───────────────────────────────────────────────────
      var faqToggles = context.querySelectorAll
        ? context.querySelectorAll('[data-domain-faq-toggle]')
        : [];

      faqToggles.forEach(function (toggle) {
        if (toggle.dataset.domainGuideBound) {
          return;
        }
        toggle.dataset.domainGuideBound = '1';

        toggle.addEventListener('click', function () {
          var faqId = toggle.dataset.domainFaqToggle;
          var answer = document.getElementById('faq-' + faqId);
          if (!answer) {
            return;
          }

          var isOpen = toggle.getAttribute('aria-expanded') === 'true';
          toggle.setAttribute('aria-expanded', String(!isOpen));
          answer.hidden = isOpen;
          toggle.closest('.domain-guide__faq').classList.toggle('domain-guide__faq--open', !isOpen);
        });
      });
    }
  };

})(Drupal);

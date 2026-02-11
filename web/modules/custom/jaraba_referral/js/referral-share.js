/**
 * @file
 * Referral code sharing functionality.
 */
(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.jarabaReferralShare = {
    attach: function (context) {
      // Copy URL to clipboard
      once('ref-copy', '.ej-referral-code__copy-btn', context).forEach(function (btn) {
        btn.addEventListener('click', function () {
          var targetSelector = btn.getAttribute('data-copy-target');
          var input = document.querySelector(targetSelector);
          if (!input) { return; }

          if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(input.value).then(function () {
              var originalText = btn.textContent;
              btn.textContent = Drupal.t('Copied!');
              setTimeout(function () {
                btn.textContent = originalText;
              }, 2000);
            });
          } else {
            // Fallback for older browsers.
            input.select();
            document.execCommand('copy');
            var originalText = btn.textContent;
            btn.textContent = Drupal.t('Copied!');
            setTimeout(function () {
              btn.textContent = originalText;
            }, 2000);
          }
        });
      });
    }
  };
})(Drupal, once);

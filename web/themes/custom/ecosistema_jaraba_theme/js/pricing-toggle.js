/**
 * @file pricing-toggle.js
 * Billing toggle behavior for pricing pages.
 *
 * Switches between monthly and annual pricing display on pricing cards.
 * Uses data attributes on .pricing-card elements to read prices.
 *
 * DIRECTIVES:
 * - Vanilla JS + Drupal.behaviors (no frameworks)
 * - once() to prevent duplicate attachment
 * - Drupal.t() for translatable strings
 * - No hardcoded URLs or prices
 *
 * @see pricing-page.html.twig
 */
(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.pricingBillingToggle = {
    attach: function (context) {
      var toggles = once('pricing-toggle', '.pricing-toggle', context);
      if (!toggles.length) {
        return;
      }

      toggles.forEach(function (toggleContainer) {
        var buttons = toggleContainer.querySelectorAll('.pricing-toggle__option');
        var cards = document.querySelectorAll('.pricing-card[data-tier]');

        buttons.forEach(function (button) {
          button.addEventListener('click', function () {
            var billing = this.getAttribute('data-billing');

            // Update toggle active state.
            buttons.forEach(function (btn) {
              btn.classList.remove('pricing-toggle__option--active');
              btn.setAttribute('aria-pressed', 'false');
            });
            this.classList.add('pricing-toggle__option--active');
            this.setAttribute('aria-pressed', 'true');

            // Update price display on each card.
            cards.forEach(function (card) {
              var monthlyEl = card.querySelector('.pricing-card__amount--monthly');
              var yearlyEl = card.querySelector('.pricing-card__amount--yearly');
              var billingNote = card.querySelector('.pricing-card__billing-note--yearly');

              if (!monthlyEl || !yearlyEl) {
                return;
              }

              if (billing === 'yearly') {
                monthlyEl.hidden = true;
                yearlyEl.hidden = false;
                if (billingNote) {
                  billingNote.hidden = false;
                }
              } else {
                monthlyEl.hidden = false;
                yearlyEl.hidden = true;
                if (billingNote) {
                  billingNote.hidden = true;
                }
              }
            });
          });
        });
      });
    }
  };

})(Drupal, once);

/**
 * @file landing-sticky-cta.js
 * Sticky CTA bar visibility behavior — IntersectionObserver.
 *
 * Shows the sticky CTA bar when hero AND final-cta are out of viewport.
 * Hides it when either is visible (to avoid CTA duplication).
 *
 * DIRECTRICES: OBSERVER-SCROLL-ROOT-001 (viewport default, NO custom root).
 */

(function (Drupal) {
  'use strict';

  Drupal.behaviors.landingStickyCta = {
    attach: function (context) {
      const stickyBar = context.querySelector
        ? context.querySelector('#landing-sticky-cta')
        : null;

      if (!stickyBar || stickyBar.dataset.stickyInit) {
        return;
      }
      stickyBar.dataset.stickyInit = '1';

      const hero = document.querySelector('.landing-hero');
      const finalCta = document.querySelector('.landing-final-cta');

      if (!hero) {
        return;
      }

      var heroVisible = true;
      var finalCtaVisible = false;

      function updateVisibility() {
        var shouldShow = !heroVisible && !finalCtaVisible;
        stickyBar.classList.toggle('is-visible', shouldShow);
        stickyBar.setAttribute('aria-hidden', shouldShow ? 'false' : 'true');
      }

      var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.target === hero) {
            heroVisible = entry.isIntersecting;
          } else if (entry.target === finalCta) {
            finalCtaVisible = entry.isIntersecting;
          }
          updateVisibility();
        });
      }, { threshold: 0.1 });

      observer.observe(hero);
      if (finalCta) {
        observer.observe(finalCta);
      }
    }
  };

})(Drupal);

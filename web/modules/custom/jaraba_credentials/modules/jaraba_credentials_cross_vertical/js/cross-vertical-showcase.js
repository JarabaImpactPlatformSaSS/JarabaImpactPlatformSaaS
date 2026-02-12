(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.crossVerticalShowcase = {
    attach: function (context) {
      once('cross-vertical-init', '.cross-vertical-showcase', context).forEach(function (container) {
        var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        // Animate progress bars.
        var cards = container.querySelectorAll('.cross-vertical-showcase__card');
        cards.forEach(function (card) {
          var fill = card.querySelector('.cross-vertical-showcase__progress-fill');
          if (!fill) {
            return;
          }

          var targetWidth = fill.style.width;
          fill.style.width = '0%';

          if (prefersReducedMotion) {
            fill.style.width = targetWidth;
            return;
          }

          var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
              if (entry.isIntersecting) {
                requestAnimationFrame(function () {
                  fill.style.width = targetWidth;
                });
                observer.unobserve(entry.target);
              }
            });
          }, { threshold: 0.2 });

          observer.observe(card);
        });

        // Keyboard navigation between cards.
        cards.forEach(function (card, index) {
          card.addEventListener('keydown', function (e) {
            var target = null;
            if (e.key === 'ArrowRight' && index < cards.length - 1) {
              target = cards[index + 1];
            }
            if (e.key === 'ArrowLeft' && index > 0) {
              target = cards[index - 1];
            }
            if (e.key === 'ArrowDown') {
              // Jump to next row (approximate 3 columns).
              var nextIndex = Math.min(index + 3, cards.length - 1);
              target = cards[nextIndex];
            }
            if (e.key === 'ArrowUp') {
              var prevIndex = Math.max(index - 3, 0);
              target = cards[prevIndex];
            }
            if (target) {
              e.preventDefault();
              target.focus();
            }
          });
        });
      });
    }
  };

})(Drupal, once);

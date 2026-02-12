(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.stackProgress = {
    attach: function (context) {
      once('stack-progress-init', '.stack-progress-card', context).forEach(function (card) {
        var progressFill = card.querySelector('.stack-progress-card__progress-fill');
        if (!progressFill) {
          return;
        }

        // Animate progress bar on visibility.
        var targetWidth = progressFill.style.width;
        progressFill.style.width = '0%';

        var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (prefersReducedMotion) {
          progressFill.style.width = targetWidth;
          return;
        }

        var observer = new IntersectionObserver(function (entries) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting) {
              requestAnimationFrame(function () {
                progressFill.style.width = targetWidth;
              });
              observer.unobserve(entry.target);
            }
          });
        }, { threshold: 0.3 });

        observer.observe(card);

        // Keyboard navigation for card actions.
        card.addEventListener('keydown', function (e) {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            var link = card.querySelector('a');
            if (link) {
              link.click();
            }
          }
        });
      });
    }
  };

})(Drupal, once);

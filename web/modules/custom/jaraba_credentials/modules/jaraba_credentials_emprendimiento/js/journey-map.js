(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.emprendimientoJourneyMap = {
    attach: function (context) {
      once('journey-map-init', '.emprendimiento-journey', context).forEach(function (container) {
        var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        // Animate phase progress bars on scroll.
        var phases = container.querySelectorAll('.emprendimiento-journey__phase');
        phases.forEach(function (phase) {
          var fill = phase.querySelector('.emprendimiento-journey__phase-fill');
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

          observer.observe(phase);
        });

        // Keyboard navigation between phases.
        phases.forEach(function (phase, index) {
          phase.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowDown' && index < phases.length - 1) {
              e.preventDefault();
              phases[index + 1].focus();
            }
            if (e.key === 'ArrowUp' && index > 0) {
              e.preventDefault();
              phases[index - 1].focus();
            }
          });
        });
      });
    }
  };

})(Drupal, once);

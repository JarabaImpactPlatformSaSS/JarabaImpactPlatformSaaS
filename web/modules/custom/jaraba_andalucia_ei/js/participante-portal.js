/**
 * @file
 * JavaScript behaviors para el portal de participante Andalucia +ei.
 *
 * 5 behaviors:
 * 1. aeiPortalFadeUp — IntersectionObserver para [data-effect="fade-up"]
 * 2. aeiPortalGauge — Anima SVG stroke-dashoffset del health gauge
 * 3. aeiPortalCounters — Contadores animados para [data-counter]
 * 4. aeiTimelineExpand — Expand/collapse de fases del timeline
 * 5. aeiPortalFab — Toggle FAB proactivo
 */

(function (Drupal, once) {
  'use strict';

  var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // =========================================================================
  // 1. Fade-Up on Scroll
  // =========================================================================
  Drupal.behaviors.aeiPortalFadeUp = {
    attach: function (context) {
      once('aei-portal-fade-up', '[data-effect="fade-up"]', context).forEach(function (el) {
        if (prefersReducedMotion) {
          el.classList.add('aei-visible');
          return;
        }

        var delay = parseInt(el.getAttribute('data-delay') || '0', 10);

        var observer = new IntersectionObserver(function (entries) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting) {
              setTimeout(function () {
                el.classList.add('aei-visible');
              }, delay);
              observer.unobserve(el);
            }
          });
        }, { threshold: 0.1 });

        observer.observe(el);
      });
    }
  };

  // =========================================================================
  // 2. Health Score Gauge Animation
  // =========================================================================
  Drupal.behaviors.aeiPortalGauge = {
    attach: function (context) {
      once('aei-portal-gauge', '.aei-hero__gauge-fill', context).forEach(function (circle) {
        if (prefersReducedMotion) {
          return;
        }

        var dashArray = parseFloat(circle.getAttribute('stroke-dasharray')) || 314.159;
        var targetOffset = parseFloat(circle.getAttribute('stroke-dashoffset')) || 0;

        // Start fully hidden.
        circle.style.strokeDashoffset = dashArray;
        circle.style.transition = 'none';

        var observer = new IntersectionObserver(function (entries) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting) {
              // Force reflow then animate.
              void circle.offsetWidth;
              circle.style.transition = 'stroke-dashoffset 1.5s cubic-bezier(0.65, 0, 0.35, 1)';
              circle.style.strokeDashoffset = targetOffset;
              observer.unobserve(circle);
            }
          });
        }, { threshold: 0.3 });

        observer.observe(circle);
      });
    }
  };

  // =========================================================================
  // 3. Counter Animation
  // =========================================================================
  Drupal.behaviors.aeiPortalCounters = {
    attach: function (context) {
      once('aei-portal-counter', '[data-counter]', context).forEach(function (el) {
        var raw = el.getAttribute('data-counter') || '';
        // Extract numeric value (e.g., "12.5h" -> 12.5, "+200" -> 200)
        var match = raw.match(/([+-]?)(\d+\.?\d*)/);
        if (!match) {
          return;
        }

        var prefix = raw.match(/^[^0-9+-]*/)[0] || '';
        var sign = match[1] || '';
        var target = parseFloat(match[2]);
        var suffix = raw.replace(/^[^0-9+-]*[+-]?\d+\.?\d*/, '');
        var isDecimal = match[2].indexOf('.') !== -1;

        if (prefersReducedMotion) {
          return; // Keep original text.
        }

        var observer = new IntersectionObserver(function (entries) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting) {
              animateCounter(el, target, prefix, sign, suffix, isDecimal);
              observer.unobserve(el);
            }
          });
        }, { threshold: 0.3 });

        observer.observe(el);
      });
    }
  };

  function animateCounter(el, target, prefix, sign, suffix, isDecimal) {
    var duration = 1200;
    var start = performance.now();

    function update(now) {
      var elapsed = now - start;
      var progress = Math.min(elapsed / duration, 1);
      // Ease out cubic.
      var eased = 1 - Math.pow(1 - progress, 3);
      var current = target * eased;
      var display = isDecimal ? current.toFixed(1) : Math.round(current);

      el.textContent = prefix + sign + display + suffix;

      if (progress < 1) {
        requestAnimationFrame(update);
      } else {
        el.textContent = prefix + sign + (isDecimal ? target.toFixed(1) : Math.round(target)) + suffix;
      }
    }

    requestAnimationFrame(update);
  }

  // =========================================================================
  // 4. Timeline Phase Expand/Collapse
  // =========================================================================
  Drupal.behaviors.aeiTimelineExpand = {
    attach: function (context) {
      once('aei-timeline-expand', '.aei-timeline__phase-header', context).forEach(function (header) {
        var phase = header.closest('.aei-timeline__phase');
        if (!phase) {
          return;
        }

        var steps = phase.querySelector('.aei-timeline__steps');
        if (!steps) {
          return;
        }

        // Set initial state — active/completed phases start expanded.
        var isActive = phase.classList.contains('aei-timeline__phase--active');
        var isCompleted = phase.classList.contains('aei-timeline__phase--completed');

        if (!isActive && !isCompleted) {
          steps.style.display = 'none';
          header.setAttribute('aria-expanded', 'false');
        } else {
          header.setAttribute('aria-expanded', 'true');
        }

        header.style.cursor = 'pointer';
        header.setAttribute('role', 'button');
        header.setAttribute('tabindex', '0');

        function toggleSteps() {
          var expanded = header.getAttribute('aria-expanded') === 'true';
          if (expanded) {
            steps.style.display = 'none';
            header.setAttribute('aria-expanded', 'false');
          } else {
            steps.style.display = '';
            header.setAttribute('aria-expanded', 'true');
          }
        }

        header.addEventListener('click', toggleSteps);
        header.addEventListener('keydown', function (e) {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            toggleSteps();
          }
        });
      });
    }
  };

  // =========================================================================
  // 5. Proactive AI FAB
  // =========================================================================
  Drupal.behaviors.aeiPortalFab = {
    attach: function (context) {
      once('aei-portal-fab', '.aei-portal__fab-trigger', context).forEach(function (trigger) {
        var fab = trigger.closest('.aei-portal__fab');
        if (!fab) {
          return;
        }

        var content = fab.querySelector('.aei-portal__fab-content');
        if (!content) {
          return;
        }

        trigger.addEventListener('click', function () {
          var expanded = trigger.getAttribute('aria-expanded') === 'true';
          trigger.setAttribute('aria-expanded', !expanded);
          content.hidden = expanded;
        });

        // Close on outside click.
        document.addEventListener('click', function (e) {
          if (!fab.contains(e.target)) {
            trigger.setAttribute('aria-expanded', 'false');
            content.hidden = true;
          }
        });

        // Close on Escape.
        document.addEventListener('keydown', function (e) {
          if (e.key === 'Escape') {
            trigger.setAttribute('aria-expanded', 'false');
            content.hidden = true;
          }
        });
      });
    }
  };

})(Drupal, once);

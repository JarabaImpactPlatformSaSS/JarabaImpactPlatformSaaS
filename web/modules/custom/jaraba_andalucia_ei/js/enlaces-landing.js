/**
 * @file
 * Link-in-bio premium page — Andalucía +ei.
 *
 * JS-STANDALONE-MULTITENANT-001: IIFE standalone, sin Drupal.behaviors
 * ni core/once para máxima compatibilidad multi-tenant.
 *
 * Features:
 *   1. Count-up animation on stats (IntersectionObserver + rAF).
 *   2. Scroll reveal (IntersectionObserver → .aei-enlaces--visible).
 *   3. FAQ accordion (smooth maxHeight + opacity on <details>).
 *   4. Countdown timer (from drupalSettings.aeiEnlaces).
 *   5. prefers-reduced-motion respected throughout.
 */
(function () {
  'use strict';

  // Guard: prevent double execution.
  if (window.__aeiEnlacesLanding) {
    return;
  }
  window.__aeiEnlacesLanding = true;

  var prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // ============================================================
  // 1. COUNT-UP ANIMATION
  // ============================================================
  function animateCountUp(el) {
    var raw = parseInt(el.getAttribute('data-countup'), 10);
    var suffix = el.getAttribute('data-suffix') || '';
    if (isNaN(raw) || raw <= 0) {
      return;
    }

    var duration = 1200;
    var startTime = null;

    function easeOutCubic(t) {
      return 1 - Math.pow(1 - t, 3);
    }

    function step(timestamp) {
      if (!startTime) {
        startTime = timestamp;
      }
      var elapsed = timestamp - startTime;
      var progress = Math.min(elapsed / duration, 1);
      var easedProgress = easeOutCubic(progress);
      var current = Math.round(easedProgress * raw);

      el.textContent = current.toLocaleString('es-ES') + suffix;

      if (progress < 1) {
        requestAnimationFrame(step);
      }
    }

    // Set initial value to 0 before animation starts.
    el.textContent = '0' + suffix;
    requestAnimationFrame(step);
  }

  function initCountUp() {
    var els = document.querySelectorAll('.aei-enlaces__stat-value[data-countup]');
    if (!els.length) {
      return;
    }

    if (prefersReducedMotion) {
      // Show final values immediately.
      return;
    }

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          animateCountUp(entry.target);
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.3 });

    els.forEach(function (el) {
      observer.observe(el);
    });
  }

  // ============================================================
  // 2. SCROLL REVEAL
  // ============================================================
  function initScrollReveal() {
    var els = document.querySelectorAll('.aei-enlaces--reveal');
    if (!els.length || prefersReducedMotion) {
      // If reduced motion, show all immediately.
      if (prefersReducedMotion) {
        els.forEach(function (el) {
          el.classList.add('aei-enlaces--visible');
        });
      }
      return;
    }

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('aei-enlaces--visible');
          observer.unobserve(entry.target);
        }
      });
    }, {
      threshold: 0.12,
      rootMargin: '0px 0px -40px 0px'
    });

    els.forEach(function (el) {
      observer.observe(el);
    });
  }

  // ============================================================
  // 3. TESTIMONIAL CAROUSEL (arrows + dots)
  // ============================================================
  function initCarousel() {
    var scroll = document.querySelector('.aei-enlaces__testimonials-scroll');
    var cards = document.querySelectorAll('.aei-enlaces__testimonial-card');
    var dots = document.querySelectorAll('.aei-enlaces__carousel-dot');
    var prevBtn = document.querySelector('.aei-enlaces__carousel-arrow--prev');
    var nextBtn = document.querySelector('.aei-enlaces__carousel-arrow--next');

    if (!scroll || cards.length < 2) {
      return;
    }

    var currentIndex = 0;

    function scrollToCard(index) {
      if (index < 0 || index >= cards.length) {
        return;
      }
      currentIndex = index;
      cards[index].scrollIntoView({
        behavior: prefersReducedMotion ? 'auto' : 'smooth',
        block: 'nearest',
        inline: 'start'
      });
      updateDots(index);
    }

    function updateDots(activeIndex) {
      dots.forEach(function (dot, i) {
        if (i === activeIndex) {
          dot.classList.add('aei-enlaces__carousel-dot--active');
          dot.setAttribute('aria-selected', 'true');
        }
        else {
          dot.classList.remove('aei-enlaces__carousel-dot--active');
          dot.setAttribute('aria-selected', 'false');
        }
      });
    }

    // Arrow click handlers.
    if (prevBtn) {
      prevBtn.addEventListener('click', function () {
        scrollToCard(currentIndex - 1);
      });
    }

    if (nextBtn) {
      nextBtn.addEventListener('click', function () {
        scrollToCard(currentIndex + 1);
      });
    }

    // Dot click handlers.
    dots.forEach(function (dot) {
      dot.addEventListener('click', function () {
        var slideIndex = parseInt(dot.getAttribute('data-slide'), 10);
        scrollToCard(slideIndex);
      });
    });

    // Sync dots on manual scroll (swipe/drag).
    var scrollObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          var index = Array.prototype.indexOf.call(cards, entry.target);
          if (index !== -1) {
            currentIndex = index;
            updateDots(index);
          }
        }
      });
    }, {
      root: scroll,
      threshold: 0.6
    });

    cards.forEach(function (card) {
      scrollObserver.observe(card);
    });
  }

  // ============================================================
  // 4. FAQ ACCORDION
  // ============================================================
  function initFaqAccordion() {
    var items = document.querySelectorAll('.aei-enlaces__faq-item');
    if (!items.length) {
      return;
    }

    items.forEach(function (details) {
      var summary = details.querySelector('.aei-enlaces__faq-question');
      var answer = details.querySelector('.aei-enlaces__faq-answer');
      if (!summary || !answer) {
        return;
      }

      summary.addEventListener('click', function (e) {
        e.preventDefault();

        if (details.hasAttribute('open')) {
          // Close: animate then remove open.
          answer.style.maxHeight = answer.scrollHeight + 'px';
          requestAnimationFrame(function () {
            answer.style.maxHeight = '0';
            answer.style.opacity = '0';
          });

          var onTransitionEnd = function () {
            details.removeAttribute('open');
            answer.removeEventListener('transitionend', onTransitionEnd);
          };

          if (prefersReducedMotion) {
            details.removeAttribute('open');
          }
          else {
            answer.addEventListener('transitionend', onTransitionEnd);
          }
        }
        else {
          // Open: set open then animate.
          details.setAttribute('open', '');

          if (prefersReducedMotion) {
            answer.style.maxHeight = 'none';
            answer.style.opacity = '1';
            return;
          }

          // Force layout recalc.
          var scrollH = answer.scrollHeight;
          answer.style.maxHeight = '0';
          answer.style.opacity = '0';

          requestAnimationFrame(function () {
            answer.style.maxHeight = scrollH + 'px';
            answer.style.opacity = '1';
          });
        }
      });
    });
  }

  // ============================================================
  // 5. COUNTDOWN TIMER (only visible when < 14 days remain)
  // ============================================================
  var COUNTDOWN_THRESHOLD_DAYS = 14;

  function initCountdown() {
    var settings = window.drupalSettings && window.drupalSettings.aeiEnlaces;
    if (!settings || !settings.mostrarCountdown || !settings.fechaLimite) {
      return;
    }

    var container = document.querySelector('.aei-enlaces__countdown');
    if (!container) {
      return;
    }

    var daysEl = container.querySelector('.aei-enlaces__countdown-days');
    var hoursEl = container.querySelector('.aei-enlaces__countdown-hours');
    var minsEl = container.querySelector('.aei-enlaces__countdown-mins');
    var badge = document.querySelector('.aei-enlaces__urgency-badge');

    if (!daysEl || !hoursEl || !minsEl) {
      return;
    }

    var deadline = new Date(settings.fechaLimite + 'T23:59:59').getTime();

    function update() {
      var now = Date.now();
      var diff = deadline - now;

      if (diff <= 0) {
        // Deadline passed: change badge to "Cerrado".
        if (badge) {
          badge.textContent = 'Cerrado';
          badge.style.color = '';
        }
        container.classList.add('aei-enlaces__countdown--hidden');
        return false;
      }

      var days = Math.floor(diff / (1000 * 60 * 60 * 24));
      var hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
      var mins = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

      if (days < COUNTDOWN_THRESHOLD_DAYS) {
        // Show countdown only when urgency is real (< 14 days).
        container.classList.remove('aei-enlaces__countdown--hidden');
        daysEl.textContent = days;
        hoursEl.textContent = hours;
        minsEl.textContent = mins;

        // Update badge to reflect urgency.
        if (badge) {
          badge.textContent = days <= 3 ? '¡Últimos días!' : 'Cierre próximo';
        }
      }
      // else: countdown stays hidden, "Pre-lanzamiento" badge remains.

      return true;
    }

    // Initial update.
    if (update() && !prefersReducedMotion) {
      // Update every 60 seconds.
      setInterval(update, 60000);
    }
  }

  // ============================================================
  // INIT
  // ============================================================
  function init() {
    initCountUp();
    initScrollReveal();
    initCarousel();
    initFaqAccordion();
    initCountdown();
  }

  // Run on DOMContentLoaded or immediately if already loaded.
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  }
  else {
    init();
  }

})();

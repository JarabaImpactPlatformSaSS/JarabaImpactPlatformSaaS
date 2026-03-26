/**
 * @file
 * Prueba Gratuita landing page — Andalucía +ei.
 *
 * JS-STANDALONE-MULTITENANT-001: IIFE standalone, sin Drupal.behaviors
 * ni core/once para máxima compatibilidad multi-tenant.
 *
 * Features:
 *   1. Scroll reveal (IntersectionObserver).
 *   2. Sticky CTA bar (IntersectionObserver on hero).
 *   3. FAQ accordion (smooth maxHeight + opacity).
 *   4. Count-up animation on stats.
 *   5. Success state (auto-scroll + URL cleanup).
 *   6. prefers-reduced-motion respected throughout.
 */
(function () {
  'use strict';

  if (window.__aeiPruebaGratuita) {
    return;
  }
  window.__aeiPruebaGratuita = true;

  var prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // ============================================================
  // 1. SCROLL REVEAL
  // ============================================================
  function initScrollReveal() {
    var els = document.querySelectorAll('.prueba-gratuita--reveal');
    if (!els.length) {
      return;
    }

    if (prefersReducedMotion) {
      els.forEach(function (el) {
        el.classList.add('prueba-gratuita--visible');
      });
      return;
    }

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('prueba-gratuita--visible');
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
  // 2. STICKY CTA BAR
  // ============================================================
  function initStickyBar() {
    var hero = document.querySelector('.prueba-gratuita__hero');
    var bar = document.querySelector('.prueba-gratuita__sticky-bar');
    if (!hero || !bar) {
      return;
    }

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          bar.classList.remove('prueba-gratuita__sticky-bar--visible');
        }
        else {
          bar.classList.add('prueba-gratuita__sticky-bar--visible');
        }
      });
    }, { threshold: 0 });

    observer.observe(hero);
  }

  // ============================================================
  // 3. FAQ ACCORDION
  // ============================================================
  function initFaqAccordion() {
    var items = document.querySelectorAll('.prueba-gratuita__faq-item');
    if (!items.length) {
      return;
    }

    items.forEach(function (details) {
      var summary = details.querySelector('.prueba-gratuita__faq-question');
      var answer = details.querySelector('.prueba-gratuita__faq-answer');
      if (!summary || !answer) {
        return;
      }

      summary.addEventListener('click', function (e) {
        e.preventDefault();

        if (details.hasAttribute('open')) {
          answer.style.maxHeight = answer.scrollHeight + 'px';
          requestAnimationFrame(function () {
            answer.style.maxHeight = '0';
            answer.style.opacity = '0';
          });

          var onEnd = function () {
            details.removeAttribute('open');
            answer.removeEventListener('transitionend', onEnd);
          };

          if (prefersReducedMotion) {
            details.removeAttribute('open');
          }
          else {
            answer.addEventListener('transitionend', onEnd);
          }
        }
        else {
          details.setAttribute('open', '');

          if (prefersReducedMotion) {
            answer.style.maxHeight = 'none';
            answer.style.opacity = '1';
            return;
          }

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
  // 4. COUNT-UP ANIMATION
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
      var current = Math.round(easeOutCubic(progress) * raw);

      el.textContent = current.toLocaleString('es-ES') + suffix;

      if (progress < 1) {
        requestAnimationFrame(step);
      }
    }

    el.textContent = '0' + suffix;
    requestAnimationFrame(step);
  }

  function initCountUp() {
    var els = document.querySelectorAll('.prueba-gratuita__stat-value[data-countup]');
    if (!els.length || prefersReducedMotion) {
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
  // 5. SUCCESS STATE
  // ============================================================
  function initSuccessState() {
    var settings = window.drupalSettings && window.drupalSettings.aeiPruebaGratuita;
    if (!settings || !settings.showSuccess) {
      return;
    }

    var successEl = document.querySelector('.prueba-gratuita__success');
    if (successEl) {
      successEl.scrollIntoView({
        behavior: prefersReducedMotion ? 'auto' : 'smooth',
        block: 'center'
      });
    }

    // Clean URL: remove ?ok=1 without reload.
    if (window.history && window.history.replaceState) {
      var cleanUrl = window.location.pathname + window.location.hash;
      window.history.replaceState(null, '', cleanUrl);
    }
  }

  // ============================================================
  // INIT
  // ============================================================
  function init() {
    initScrollReveal();
    initStickyBar();
    initFaqAccordion();
    initCountUp();
    initSuccessState();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  }
  else {
    init();
  }

})();

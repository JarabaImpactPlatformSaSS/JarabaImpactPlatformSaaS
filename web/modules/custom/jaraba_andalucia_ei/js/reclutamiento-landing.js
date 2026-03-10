/**
 * @file
 * Microinteracciones para la landing de reclutamiento Andalucía +ei.
 *
 * JS-STANDALONE-MULTITENANT-001: IIFE standalone, sin Drupal.behaviors
 * ni core/once para máxima compatibilidad multi-tenant.
 *
 * Features:
 * 1. Count-up animation en hero stats
 * 2. Scroll reveal (IntersectionObserver)
 * 3. FAQ smooth accordion
 * 4. Sticky urgency bar
 * 5. WhatsApp floating button
 * 6. Countdown deadline timer (P0-4)
 * 7. Video play/pause control (P1-3)
 * 8. Inline pre-qualification form (P0-3)
 */
(function () {
  'use strict';

  // Guard: solo ejecutar una vez.
  if (window.__aeiRecLanding) return;
  window.__aeiRecLanding = true;

  // =========================================================================
  // P1-1: PREFERS-REDUCED-MOTION CHECK
  // =========================================================================
  var prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // =========================================================================
  // 1. COUNT-UP ANIMATION — Hero stats
  // =========================================================================
  function animateCountUp(el) {
    // P1-1: Skip animation if user prefers reduced motion.
    if (prefersReducedMotion) return;

    var text = (el.textContent || '').trim();
    // Parse: "45", "528 €", "40%", "22.222", "46%", "50"
    var match = text.match(/([\d.]+)/);
    if (!match) return;

    var raw = match[1];
    var target = parseFloat(raw.replace('.', ''));
    var hasThousandSep = raw.indexOf('.') !== -1 && raw.length > 4;
    var suffix = text.replace(raw, '').trim();
    var duration = 1500;
    var startTime = null;

    function step(ts) {
      if (!startTime) startTime = ts;
      var progress = Math.min((ts - startTime) / duration, 1);
      // Ease out cubic.
      var eased = 1 - Math.pow(1 - progress, 3);
      var current = Math.round(eased * target);

      if (hasThousandSep) {
        el.textContent = current.toLocaleString('es-ES') + (suffix ? ' ' + suffix : '');
      } else {
        el.textContent = current + (suffix ? ' ' + suffix : '');
      }

      if (progress < 1) {
        requestAnimationFrame(step);
      }
    }

    // Reset to 0 before animating.
    el.textContent = '0' + (suffix ? ' ' + suffix : '');
    requestAnimationFrame(step);
  }

  // =========================================================================
  // 2. SCROLL REVEAL — IntersectionObserver fade-in
  // =========================================================================
  var revealObserver = null;
  // P1-1: Skip scroll reveal if reduced motion.
  if ('IntersectionObserver' in window && !prefersReducedMotion) {
    revealObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('aei-rec--visible');
          revealObserver.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
  }

  // =========================================================================
  // 3. FAQ SMOOTH ACCORDION
  // =========================================================================
  function initFaqAccordion() {
    var faqItems = document.querySelectorAll('.aei-rec__faq-item');
    faqItems.forEach(function (details) {
      var summary = details.querySelector('.aei-rec__faq-question');
      var answer = details.querySelector('.aei-rec__faq-answer');
      if (!summary || !answer) return;

      summary.addEventListener('click', function (e) {
        e.preventDefault();
        if (details.open) {
          // Close with animation.
          answer.style.maxHeight = answer.scrollHeight + 'px';
          requestAnimationFrame(function () {
            answer.style.maxHeight = '0';
            answer.style.opacity = '0';
          });
          setTimeout(function () {
            details.open = false;
            answer.style.maxHeight = '';
            answer.style.opacity = '';
          }, prefersReducedMotion ? 0 : 300);
        } else {
          // Open with animation.
          details.open = true;
          if (prefersReducedMotion) return;
          var h = answer.scrollHeight;
          answer.style.maxHeight = '0';
          answer.style.opacity = '0';
          requestAnimationFrame(function () {
            answer.style.maxHeight = h + 'px';
            answer.style.opacity = '1';
          });
          setTimeout(function () {
            answer.style.maxHeight = '';
          }, 300);
        }
      });
    });
  }

  // =========================================================================
  // 4. STICKY URGENCY BAR
  // =========================================================================
  function initStickyBar() {
    var bar = document.querySelector('.aei-rec__sticky-bar');
    var hero = document.querySelector('.aei-rec__hero');
    if (!bar || !hero) return;

    var stickyObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          bar.classList.remove('aei-rec__sticky-bar--visible');
        } else {
          bar.classList.add('aei-rec__sticky-bar--visible');
        }
      });
    }, { threshold: 0 });

    stickyObserver.observe(hero);
  }

  // =========================================================================
  // 5. VIDEO PRELOAD OPTIMIZATION (mobile)
  // =========================================================================
  function optimizeVideo() {
    var video = document.querySelector('.aei-rec__hero-video');
    if (!video) return;

    if (window.innerWidth <= 768) {
      video.preload = 'metadata';
    }

    // P1-1: Pause autoplay video if user prefers reduced motion.
    if (prefersReducedMotion) {
      video.pause();
      video.removeAttribute('autoplay');
    }
  }


  // =========================================================================
  // 7. VIDEO PLAY/PAUSE CONTROL (P1-3)
  // =========================================================================
  function initVideoControl() {
    var btn = document.querySelector('.aei-rec__hero-video-control');
    var video = document.querySelector('.aei-rec__hero-video');
    if (!btn || !video) return;

    var pauseIcon = btn.querySelector('.aei-rec__hero-video-icon--pause');
    var playIcon = btn.querySelector('.aei-rec__hero-video-icon--play');
    var labelPlay = btn.getAttribute('data-label-play') || 'Reproducir vídeo';
    var labelPause = btn.getAttribute('data-label-pause') || 'Pausar vídeo';

    function updateState() {
      var paused = video.paused;
      if (pauseIcon) pauseIcon.style.display = paused ? 'none' : '';
      if (playIcon) playIcon.style.display = paused ? '' : 'none';
      btn.setAttribute('aria-label', paused ? labelPlay : labelPause);
    }

    btn.addEventListener('click', function () {
      if (video.paused) {
        video.play();
      } else {
        video.pause();
      }
      updateState();
    });

    // Sync state if video auto-paused (e.g., by reduced-motion check).
    updateState();
  }

  // =========================================================================
  // INIT
  // =========================================================================
  function init() {
    // Count-up: observe hero stats + social proof + edicion stats.
    var countUpObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          var vals = entry.target.querySelectorAll(
            '.aei-rec__hero-stat-value, .aei-rec__social-proof-value, .aei-rec__edicion-stat-value, .aei-rec__equipo-impacto-valor'
          );
          vals.forEach(animateCountUp);
          countUpObserver.unobserve(entry.target);
        }
      });
    }, { threshold: 0.3 });

    var heroStats = document.querySelector('.aei-rec__hero-stats');
    if (heroStats) countUpObserver.observe(heroStats);
    var socialProof = document.querySelector('.aei-rec__social-proof-grid');
    if (socialProof) countUpObserver.observe(socialProof);
    var edicionStats = document.querySelector('.aei-rec__edicion-stats');
    if (edicionStats) countUpObserver.observe(edicionStats);
    var equipoImpacto = document.querySelector('.aei-rec__equipo-impacto');
    if (equipoImpacto) countUpObserver.observe(equipoImpacto);

    // Scroll reveal: all sections.
    if (revealObserver) {
      document.querySelectorAll(
        '.aei-rec__beneficio-card, .aei-rec__sede-card, .aei-rec__paso, ' +
        '.aei-rec__equipo-card, .aei-rec__social-proof-card, ' +
        '.aei-rec__testimonio-card, .aei-rec__lead-magnet, ' +
        '.aei-rec__edicion-anterior, .aei-rec__faq-item'
      ).forEach(function (el) {
        el.classList.add('aei-rec--reveal');
        revealObserver.observe(el);
      });
    }

    // P1-1: If reduced motion, make all reveal elements visible immediately.
    if (prefersReducedMotion) {
      document.querySelectorAll('.aei-rec--reveal').forEach(function (el) {
        el.classList.add('aei-rec--visible');
      });
    }

    initFaqAccordion();
    initStickyBar();
    optimizeVideo();
    initVideoControl();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

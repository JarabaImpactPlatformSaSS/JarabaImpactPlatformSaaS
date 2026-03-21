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
  // 6. COUNTDOWN DEADLINE TIMER (P0-4)
  // =========================================================================
  function initCountdown() {
    var container = document.querySelector('.aei-rec__countdown');
    if (!container) return;

    // drupalSettings may not exist in standalone multi-tenant context.
    var settings = (window.drupalSettings && window.drupalSettings.aeiReclutamiento) || {};
    if (!settings.mostrarCountdown || !settings.fechaLimite) {
      container.style.display = 'none';
      return;
    }

    var deadline = new Date(settings.fechaLimite + 'T23:59:59').getTime();
    if (isNaN(deadline)) {
      container.style.display = 'none';
      return;
    }

    var daysEl = container.querySelector('.aei-rec__countdown-days');
    var hoursEl = container.querySelector('.aei-rec__countdown-hours');
    var minsEl = container.querySelector('.aei-rec__countdown-mins');

    function update() {
      var now = Date.now();
      var diff = deadline - now;

      if (diff <= 0) {
        container.style.display = 'none';
        return;
      }

      var days = Math.floor(diff / 86400000);
      var hours = Math.floor((diff % 86400000) / 3600000);
      var mins = Math.floor((diff % 3600000) / 60000);

      if (daysEl) daysEl.textContent = days;
      if (hoursEl) hoursEl.textContent = hours;
      if (minsEl) minsEl.textContent = mins;
    }

    update();
    // P1-1: Static text if reduced motion, no ticking animation.
    if (!prefersReducedMotion) {
      setInterval(update, 60000);
    }
  }

  // =========================================================================
  // 8. PRE-QUALIFICATION INLINE FORM (P0-3)
  // =========================================================================
  function initPrequalify() {
    var form = document.querySelector('.aei-rec__prequalify');
    if (!form) return;

    var questions = form.querySelectorAll('.aei-rec__prequalify-question');
    var feedback = form.querySelector('.aei-rec__prequalify-feedback');
    var ctaWrap = form.querySelector('.aei-rec__prequalify-cta-wrap');
    if (!questions.length || !feedback || !ctaWrap) return;

    var answers = {};

    questions.forEach(function (q) {
      var btns = q.querySelectorAll('.aei-rec__prequalify-btn');
      var key = q.getAttribute('data-question');

      btns.forEach(function (btn) {
        btn.addEventListener('click', function () {
          // Visual selection.
          btns.forEach(function (b) { b.classList.remove('aei-rec__prequalify-btn--active'); });
          btn.classList.add('aei-rec__prequalify-btn--active');
          btn.setAttribute('aria-pressed', 'true');
          btns.forEach(function (b) {
            if (b !== btn) b.setAttribute('aria-pressed', 'false');
          });

          answers[key] = btn.getAttribute('data-value') === 'si';
          evaluatePrequalify(answers, feedback, ctaWrap);
        });
      });
    });
  }

  function evaluatePrequalify(answers, feedback, ctaWrap) {
    var keys = Object.keys(answers);
    if (keys.length < 3) {
      feedback.style.display = 'none';
      return;
    }

    var score = 0;
    if (answers.residencia) score++;
    if (answers.sae) score++;
    if (answers.colectivo) score++;

    feedback.style.display = '';
    var icon = feedback.querySelector('.aei-rec__prequalify-feedback-icon');
    var text = feedback.querySelector('.aei-rec__prequalify-feedback-text');
    var cta = ctaWrap.querySelector('a');

    if (score === 3) {
      feedback.className = 'aei-rec__prequalify-feedback aei-rec__prequalify-feedback--success';
      if (icon) icon.textContent = '';
      if (text) text.textContent = window.Drupal ? Drupal.t('Cumples todos los requisitos. Solicita tu plaza ahora.') : 'Cumples todos los requisitos. Solicita tu plaza ahora.';
      if (cta) {
        cta.textContent = window.Drupal ? Drupal.t('Solicitar mi plaza') : 'Solicitar mi plaza';
        cta.className = 'aei-rec__cta aei-rec__cta--primary';
      }
    }
    else if (score >= 1) {
      feedback.className = 'aei-rec__prequalify-feedback aei-rec__prequalify-feedback--partial';
      if (icon) icon.textContent = '';
      if (text) text.textContent = window.Drupal ? Drupal.t('Es posible que cumplas los requisitos. Solicita y lo verificamos contigo.') : 'Es posible que cumplas los requisitos. Solicita y lo verificamos contigo.';
      if (cta) {
        cta.textContent = window.Drupal ? Drupal.t('Solicitar y verificar') : 'Solicitar y verificar';
        cta.className = 'aei-rec__cta aei-rec__cta--primary';
      }
    }
    else {
      feedback.className = 'aei-rec__prequalify-feedback aei-rec__prequalify-feedback--no';
      if (icon) icon.textContent = '';
      if (text) text.textContent = window.Drupal ? Drupal.t('Puede que este programa no sea para ti, pero pregúntanos.') : 'Puede que este programa no sea para ti, pero pregúntanos.';
      if (cta) {
        cta.textContent = window.Drupal ? Drupal.t('Pregúntanos por WhatsApp') : 'Pregúntanos por WhatsApp';
        cta.className = 'aei-rec__cta aei-rec__cta--whatsapp';
        cta.href = 'https://wa.me/34623174304?text=%C2%BFPuedo%20participar%20en%20el%20programa%20T-Acompa%C3%B1amos%3F';
        cta.target = '_blank';
        cta.rel = 'noopener noreferrer';
      }
    }
    ctaWrap.style.display = '';
  }

  // =========================================================================
  // 5. VIDEO PRELOAD OPTIMIZATION (mobile)
  // =========================================================================
  function optimizeVideo() {
    var video = document.querySelector('.aei-rec__hero-video');
    if (!video) return;

    // P1-1: Pause autoplay video if user prefers reduced motion.
    if (prefersReducedMotion) {
      video.pause();
      video.removeAttribute('autoplay');
      return;
    }

    // Mobile 3G/saveData: don't load video at all — poster is enough.
    var conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
    var isSlow = conn && (conn.saveData || conn.effectiveType === '2g' || conn.effectiveType === 'slow-2g' || conn.effectiveType === '3g');

    if (isSlow) {
      video.removeAttribute('autoplay');
      video.preload = 'none';
      video.pause();
    }
    else if (window.innerWidth <= 768) {
      video.preload = 'metadata';
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
  // 9. LEAD MAGNET INLINE EMAIL CAPTURE
  // =========================================================================
  function initLeadMagnetInline() {
    var form = document.querySelector('.aei-rec__leadmagnet-form');
    if (!form) return;

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var emailInput = form.querySelector('.aei-rec__leadmagnet-email');
      var submitBtn = form.querySelector('.aei-rec__leadmagnet-submit');
      var email = (emailInput.value || '').trim();

      if (!email || email.indexOf('@') === -1) return;

      // Disable while submitting.
      submitBtn.disabled = true;
      submitBtn.textContent = '...';

      // CSRF token for Drupal API.
      var tokenUrl = '/session/token';
      fetch(tokenUrl)
        .then(function (r) { return r.text(); })
        .then(function (token) {
          return fetch('/api/v1/public/subscribe', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-Token': token,
            },
            body: JSON.stringify({
              email: email,
              source: 'lead_magnet_andalucia_ei',
              tags: ['andalucia_ei', 'lead_magnet', 'guia_participante'],
            }),
          });
        })
        .then(function (resp) {
          if (resp.ok || resp.status === 200 || resp.status === 201) {
            // Success: show confirmation and redirect to guide.
            var guiaUrl = form.getAttribute('data-guia-url') || '/andalucia-ei/guia-participante';
            form.innerHTML =
              '<p class="aei-rec__leadmagnet-success">' +
              (window.Drupal ? Drupal.t('¡Enviado! Descargando tu guía...') : '¡Enviado! Descargando tu guía...') +
              '</p>';
            setTimeout(function () {
              window.location.href = guiaUrl;
            }, 1200);
          } else {
            // Error: fallback to direct download.
            submitBtn.disabled = false;
            submitBtn.textContent = window.Drupal ? Drupal.t('Descargar') : 'Descargar';
            var guiaUrl = form.getAttribute('data-guia-url') || '/andalucia-ei/guia-participante';
            window.location.href = guiaUrl;
          }
        })
        .catch(function () {
          // Network error: fallback to direct download.
          submitBtn.disabled = false;
          var guiaUrl = form.getAttribute('data-guia-url') || '/andalucia-ei/guia-participante';
          window.location.href = guiaUrl;
        });
    });
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
        '.aei-rec__edicion-anterior, .aei-rec__faq-item, ' +
        '.aei-rec__painpoint-card, .aei-rec__comparativa, ' +
        '.aei-rec__prequalify, .aei-rec__sector-tag'
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
    initCountdown();
    initPrequalify();
    initLeadMagnetInline();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

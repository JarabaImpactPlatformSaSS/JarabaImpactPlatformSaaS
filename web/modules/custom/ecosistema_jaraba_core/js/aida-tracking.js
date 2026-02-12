/**
 * @file aida-tracking.js
 * AIDA funnel tracking behaviors para el visitor journey.
 *
 * Implementa tracking de eventos del funnel AIDA:
 * - Awareness: page_view (ya en analytics-tracker.js), cta_click
 * - Interest: scroll_depth, content_engagement
 * - Desire: lead_magnet_start/complete (ya en lead-magnet.js)
 * - Action: signup_start, signup_complete
 *
 * Eventos nuevos implementados aqui:
 * - cta_click: click en elementos con data-track-cta
 * - scroll_depth: 25%, 50%, 75%, 100% de scroll
 * - signup_start: al cargar pagina de registro
 * - signup_complete: al completar registro (redirect con ?welcome=1)
 *
 * @see docs/implementacion/2026-02-12_F3_Visitor_Journey_Complete_Doc178_Implementacion.md
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Behavior: CTA Click Tracking.
   * Tracks clicks on any element with data-track-cta attribute.
   *
   * Usage in Twig:
   *   <a href="/registro" data-track-cta="hero_register" data-track-position="hero">
   *     Empieza gratis
   *   </a>
   */
  Drupal.behaviors.aidaCtaTracking = {
    attach: function (context) {
      var ctas = once('aida-cta-track', '[data-track-cta]', context);
      ctas.forEach(function (el) {
        el.addEventListener('click', function () {
          var ctaId = el.dataset.trackCta || '';
          var position = el.dataset.trackPosition || '';
          var ctaText = el.textContent ? el.textContent.trim().substring(0, 80) : '';

          trackEvent('cta_click', {
            cta_id: ctaId,
            cta_text: ctaText,
            position: position,
            vertical: window.jarabaVertical || '',
            page_url: window.location.pathname
          });
        });
      });
    }
  };

  /**
   * Behavior: Scroll Depth Tracking.
   * Fires events at 25%, 50%, 75%, and 100% scroll depth.
   */
  Drupal.behaviors.aidaScrollDepth = {
    attach: function (context) {
      once('aida-scroll-depth', 'body', context).forEach(function () {
        var milestones = [25, 50, 75, 100];
        var tracked = {};

        function getScrollPercent() {
          var docHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
          if (docHeight <= 0) return 100;
          return Math.round((window.scrollY / docHeight) * 100);
        }

        var scrollTimeout;
        window.addEventListener('scroll', function () {
          clearTimeout(scrollTimeout);
          scrollTimeout = setTimeout(function () {
            var pct = getScrollPercent();
            milestones.forEach(function (milestone) {
              if (pct >= milestone && !tracked[milestone]) {
                tracked[milestone] = true;
                trackEvent('scroll_depth', {
                  depth: milestone,
                  vertical: window.jarabaVertical || '',
                  page_url: window.location.pathname
                });
              }
            });
          }, 150);
        }, { passive: true });
      });
    }
  };

  /**
   * Behavior: Signup Start/Complete Tracking.
   * Detects registration page load and completion.
   */
  Drupal.behaviors.aidaSignupTracking = {
    attach: function (context) {
      once('aida-signup-track', 'body', context).forEach(function () {
        var path = window.location.pathname;
        var params = new URLSearchParams(window.location.search);

        // Track signup_start when on registration page
        if (path === '/user/register' || path === '/registro') {
          var source = params.get('source') || '';
          var vertical = params.get('vertical') || window.jarabaVertical || '';

          trackEvent('signup_start', {
            method: detectSignupMethod(),
            vertical: vertical,
            source: source,
            page_url: path
          });
        }

        // Track signup_complete when redirected after registration
        if (params.get('welcome') === '1' || path.indexOf('/welcome') === 0) {
          trackEvent('signup_complete', {
            vertical: window.jarabaVertical || '',
            method: 'registration',
            page_url: path
          });
        }
      });
    }
  };

  /**
   * Behavior: Content Engagement Tracking.
   * Tracks time spent on page and visible sections.
   */
  Drupal.behaviors.aidaContentEngagement = {
    attach: function (context) {
      once('aida-engagement', 'body', context).forEach(function () {
        var startTime = Date.now();

        // Track time on page when leaving
        function trackTimeOnPage() {
          var timeSpent = Math.round((Date.now() - startTime) / 1000);
          if (timeSpent < 3) return; // Ignore bounces

          trackEvent('content_engagement', {
            time_spent_seconds: timeSpent,
            vertical: window.jarabaVertical || '',
            page_url: window.location.pathname
          });
        }

        // Use visibilitychange for reliable tracking
        document.addEventListener('visibilitychange', function () {
          if (document.visibilityState === 'hidden') {
            trackTimeOnPage();
          }
        });

        // Fallback for page unload
        window.addEventListener('pagehide', trackTimeOnPage);
      });
    }
  };

  /**
   * Detect signup method available on the page.
   */
  function detectSignupMethod() {
    if (document.querySelector('.social-auth-google, [data-provider="google"]')) {
      return 'google_available';
    }
    if (document.querySelector('.social-auth-linkedin, [data-provider="linkedin"]')) {
      return 'linkedin_available';
    }
    return 'email';
  }

  /**
   * Track event via analytics system.
   * Uses Drupal.jarabaAnalytics.track if available,
   * falls back to dataLayer push for GA4.
   */
  function trackEvent(eventName, params) {
    // Primary: jaraba_analytics tracker
    if (typeof Drupal.jarabaAnalytics !== 'undefined' && typeof Drupal.jarabaAnalytics.track === 'function') {
      Drupal.jarabaAnalytics.track(eventName, params);
      return;
    }

    // Secondary: jaraba_pixels direct
    if (typeof window.jarabaPixels !== 'undefined' && typeof window.jarabaPixels.track === 'function') {
      window.jarabaPixels.track(eventName, params);
      return;
    }

    // Fallback: dataLayer for GA4
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({
      event: eventName,
      event_params: params
    });
  }

})(Drupal, drupalSettings, once);

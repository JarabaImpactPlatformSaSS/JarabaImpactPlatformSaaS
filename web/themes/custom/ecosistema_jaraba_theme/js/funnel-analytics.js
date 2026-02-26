/**
 * @file
 * funnel-analytics.js
 *
 * Enhanced funnel analytics tracking for meta-site conversion optimization.
 * Sprint 5 — Optimización Continua (#20).
 *
 * NOTE: Scroll depth tracking is handled by jaraba_heatmap/heatmap-tracker.js
 * to avoid duplicate listeners. This module focuses on conversion-specific events.
 *
 * Tracks:
 * - Page views with referrer + UTM params
 * - CTA clicks with position and label
 * - Form submissions
 * - Time on page
 * - Product demo tab interactions
 *
 * Events are sent via navigator.sendBeacon to /api/v1/analytics/event
 * for reliable tracking (survives page unload).
 */
(function (Drupal, once) {
  'use strict';

  var ANALYTICS_ENDPOINT = '/api/v1/analytics/event';
  var sessionId = 'fs_' + Date.now() + '_' + Math.random().toString(36).substr(2, 6);
  var pageEntryTime = Date.now();

  // Respect Do Not Track (aligned with jaraba_heatmap).
  if (navigator.doNotTrack === '1' || window.doNotTrack === '1') {
    return;
  }

  /**
   * Sends an analytics event.
   */
  function trackEvent(eventName, data) {
    var payload = Object.assign({
      event: eventName,
      session_id: sessionId,
      url: window.location.pathname,
      referrer: document.referrer || '',
      timestamp: new Date().toISOString(),
      viewport: window.innerWidth + 'x' + window.innerHeight,
    }, data || {});

    // Extract UTM params.
    var params = new URLSearchParams(window.location.search);
    ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'].forEach(function (p) {
      if (params.get(p)) payload[p] = params.get(p);
    });

    // Use sendBeacon for reliability.
    if (navigator.sendBeacon) {
      navigator.sendBeacon(ANALYTICS_ENDPOINT, JSON.stringify(payload));
    } else {
      fetch(ANALYTICS_ENDPOINT, {
        method: 'POST',
        body: JSON.stringify(payload),
        headers: { 'Content-Type': 'application/json' },
        keepalive: true,
      }).catch(function () {});
    }
  }

  Drupal.behaviors.funnelAnalytics = {
    attach: function (context) {
      once('funnel-analytics', 'body', context).forEach(function () {

        // 1. Page view.
        trackEvent('page_view', { title: document.title });

        // NOTE: Scroll depth tracking delegated to jaraba_heatmap/heatmap-tracker.js
        // to avoid duplicate scroll listeners (Sprint 5 dedup fix).

        // 3. CTA click tracking.
        document.addEventListener('click', function (e) {
          var cta = e.target.closest('[data-track-cta]');
          if (cta) {
            trackEvent('cta_click', {
              cta_id: cta.getAttribute('data-track-cta'),
              cta_text: cta.textContent.trim().substring(0, 80),
              position: cta.getAttribute('data-track-position') || 'unknown',
              href: cta.getAttribute('href') || '',
            });
          }
        });

        // 4. Product demo tab interactions.
        document.addEventListener('click', function (e) {
          var tab = e.target.closest('[data-demo-tab]');
          if (tab) {
            trackEvent('demo_interaction', {
              tab: tab.getAttribute('data-demo-tab'),
            });
          }
        });

        // 5. Time on page (on unload).
        window.addEventListener('beforeunload', function () {
          var timeOnPage = Math.round((Date.now() - pageEntryTime) / 1000);
          trackEvent('page_exit', {
            time_on_page_seconds: timeOnPage,
          });
        });

        // 6. Form submit tracking.
        document.addEventListener('submit', function (e) {
          var form = e.target;
          if (form.id) {
            trackEvent('form_submit', {
              form_id: form.id,
            });
          }
        });

      });
    }
  };

})(Drupal, once);

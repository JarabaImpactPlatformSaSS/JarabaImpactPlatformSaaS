/**
 * @file
 * Web Vitals RUM Tracker — Core Web Vitals collection via PerformanceObserver.
 *
 * Collects LCP, FCP, CLS, INP, and TTFB metrics from real users
 * and sends them to the Insights Hub API endpoint via sendBeacon/fetch.
 *
 * Fase 7 — Insights Hub.
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.insightsWebVitals = {
    attach: function (context) {
      once('insights-web-vitals', 'body', context).forEach(function () {
        // Only run on actual page views, not AJAX.
        if (!drupalSettings.insightsHub || !drupalSettings.insightsHub.enabled) {
          return;
        }

        var endpoint = drupalSettings.insightsHub.webVitalsEndpoint || '/api/v1/insights/web-vitals';
        var tenantId = drupalSettings.insightsHub.tenantId || null;

        // Collect metrics using PerformanceObserver.
        var metrics = {};

        // LCP — Largest Contentful Paint.
        try {
          new PerformanceObserver(function (list) {
            var entries = list.getEntries();
            var lastEntry = entries[entries.length - 1];
            metrics.lcp = lastEntry.startTime;
            sendMetric('LCP', lastEntry.startTime);
          }).observe({ type: 'largest-contentful-paint', buffered: true });
        }
        catch (e) { /* Browser doesn't support this observer type. */ }

        // FCP — First Contentful Paint.
        try {
          new PerformanceObserver(function (list) {
            var entries = list.getEntries();
            entries.forEach(function (entry) {
              if (entry.name === 'first-contentful-paint') {
                metrics.fcp = entry.startTime;
                sendMetric('FCP', entry.startTime);
              }
            });
          }).observe({ type: 'paint', buffered: true });
        }
        catch (e) {}

        // CLS — Cumulative Layout Shift.
        try {
          var clsValue = 0;
          new PerformanceObserver(function (list) {
            list.getEntries().forEach(function (entry) {
              if (!entry.hadRecentInput) {
                clsValue += entry.value;
              }
            });
            metrics.cls = clsValue;
          }).observe({ type: 'layout-shift', buffered: true });

          // Send CLS on page hide.
          document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'hidden' && metrics.cls !== undefined) {
              sendMetric('CLS', metrics.cls);
            }
          });
        }
        catch (e) {}

        // INP — Interaction to Next Paint.
        try {
          var inpValue = 0;
          new PerformanceObserver(function (list) {
            list.getEntries().forEach(function (entry) {
              if (entry.duration > inpValue) {
                inpValue = entry.duration;
              }
            });
            metrics.inp = inpValue;
          }).observe({ type: 'event', buffered: true, durationThreshold: 16 });

          document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'hidden' && metrics.inp) {
              sendMetric('INP', metrics.inp);
            }
          });
        }
        catch (e) {}

        // TTFB — Time to First Byte.
        try {
          var navEntries = performance.getEntriesByType('navigation');
          if (navEntries.length > 0) {
            var ttfb = navEntries[0].responseStart;
            metrics.ttfb = ttfb;
            sendMetric('TTFB', ttfb);
          }
        }
        catch (e) {}

        /**
         * Send a single metric to the API endpoint.
         *
         * @param {string} name
         *   Metric name (LCP, FCP, CLS, INP, TTFB).
         * @param {number} value
         *   Measured value.
         */
        function sendMetric(name, value) {
          var data = {
            page_url: window.location.pathname,
            metric_name: name,
            metric_value: Math.round(name === 'CLS' ? value * 1000 : value),
            device_type: getDeviceType(),
            connection_type: getConnectionType(),
            browser: getBrowser(),
            tenant_id: tenantId
          };

          // Use sendBeacon for reliability on page unload.
          if (navigator.sendBeacon) {
            navigator.sendBeacon(endpoint, JSON.stringify(data));
          }
          else {
            fetch(endpoint, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify(data),
              keepalive: true
            }).catch(function () {});
          }
        }

        /**
         * Determine device type from viewport width.
         *
         * @return {string}
         *   'mobile', 'tablet', or 'desktop'.
         */
        function getDeviceType() {
          var width = window.innerWidth;
          if (width < 768) {
            return 'mobile';
          }
          if (width < 1024) {
            return 'tablet';
          }
          return 'desktop';
        }

        /**
         * Get the effective connection type from the Network Information API.
         *
         * @return {string}
         *   Connection type or 'unknown'.
         */
        function getConnectionType() {
          var conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
          return conn ? conn.effectiveType || 'unknown' : 'unknown';
        }

        /**
         * Simple browser detection from User-Agent string.
         *
         * @return {string}
         *   Browser name.
         */
        function getBrowser() {
          var ua = navigator.userAgent;
          if (ua.indexOf('Chrome') > -1) {
            return 'Chrome';
          }
          if (ua.indexOf('Firefox') > -1) {
            return 'Firefox';
          }
          if (ua.indexOf('Safari') > -1) {
            return 'Safari';
          }
          if (ua.indexOf('Edge') > -1) {
            return 'Edge';
          }
          return 'Other';
        }
      });
    }
  };

})(Drupal, drupalSettings, once);

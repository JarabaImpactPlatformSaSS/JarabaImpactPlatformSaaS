/**
 * @file
 * Core Web Vitals tracking (HAL-AI-10).
 *
 * Measures LCP, CLS, INP and reports via dataLayer for analytics.
 * Uses PerformanceObserver API (no external dependencies).
 */

(function (Drupal) {
  'use strict';

  /**
   * Reports a CWV metric to the dataLayer.
   *
   * @param {string} name - Metric name (LCP, CLS, INP, FCP, TTFB).
   * @param {number} value - Metric value in ms (or unitless for CLS).
   * @param {string} rating - 'good', 'needs-improvement', or 'poor'.
   */
  function reportMetric(name, value, rating) {
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({
      event: 'cwv_metric',
      cwv_name: name,
      cwv_value: Math.round(value * 100) / 100,
      cwv_rating: rating,
      cwv_page: window.location.pathname,
    });
  }

  /**
   * Rates a metric against Web Vitals thresholds.
   */
  function rate(name, value) {
    const thresholds = {
      LCP: [2500, 4000],
      CLS: [0.1, 0.25],
      INP: [200, 500],
      FCP: [1800, 3000],
      TTFB: [800, 1800],
    };
    const [good, poor] = thresholds[name] || [0, 0];
    if (value <= good) return 'good';
    if (value <= poor) return 'needs-improvement';
    return 'poor';
  }

  // --- LCP (Largest Contentful Paint) ---
  if ('PerformanceObserver' in window) {
    try {
      let lcpValue = 0;
      const lcpObserver = new PerformanceObserver(function (list) {
        const entries = list.getEntries();
        const last = entries[entries.length - 1];
        lcpValue = last.startTime;
      });
      lcpObserver.observe({ type: 'largest-contentful-paint', buffered: true });

      // Report on page visibility change (user navigates away).
      document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden' && lcpValue > 0) {
          reportMetric('LCP', lcpValue, rate('LCP', lcpValue));
        }
      }, { once: true });
    }
    catch (e) {
      // LCP not supported in this browser.
    }

    // --- CLS (Cumulative Layout Shift) ---
    try {
      let clsValue = 0;
      let sessionValue = 0;
      let sessionEntries = [];
      const clsObserver = new PerformanceObserver(function (list) {
        for (const entry of list.getEntries()) {
          if (!entry.hadRecentInput) {
            const firstEntry = sessionEntries[0];
            const lastEntry = sessionEntries[sessionEntries.length - 1];
            if (sessionValue &&
                entry.startTime - lastEntry.startTime < 1000 &&
                entry.startTime - firstEntry.startTime < 5000) {
              sessionValue += entry.value;
              sessionEntries.push(entry);
            } else {
              sessionValue = entry.value;
              sessionEntries = [entry];
            }
            if (sessionValue > clsValue) {
              clsValue = sessionValue;
            }
          }
        }
      });
      clsObserver.observe({ type: 'layout-shift', buffered: true });

      document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden' && clsValue >= 0) {
          reportMetric('CLS', clsValue, rate('CLS', clsValue));
        }
      }, { once: true });
    }
    catch (e) {
      // CLS not supported.
    }

    // --- INP (Interaction to Next Paint) ---
    try {
      let inpValue = 0;
      const inpObserver = new PerformanceObserver(function (list) {
        for (const entry of list.getEntries()) {
          if (entry.duration > inpValue) {
            inpValue = entry.duration;
          }
        }
      });
      inpObserver.observe({ type: 'event', buffered: true, durationThreshold: 16 });

      document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden' && inpValue > 0) {
          reportMetric('INP', inpValue, rate('INP', inpValue));
        }
      }, { once: true });
    }
    catch (e) {
      // INP not supported.
    }

    // --- FCP (First Contentful Paint) ---
    try {
      const fcpObserver = new PerformanceObserver(function (list) {
        const entries = list.getEntries();
        for (const entry of entries) {
          if (entry.name === 'first-contentful-paint') {
            reportMetric('FCP', entry.startTime, rate('FCP', entry.startTime));
            fcpObserver.disconnect();
          }
        }
      });
      fcpObserver.observe({ type: 'paint', buffered: true });
    }
    catch (e) {
      // FCP not supported.
    }

    // --- TTFB (Time to First Byte) ---
    try {
      const navEntries = performance.getEntriesByType('navigation');
      if (navEntries.length > 0) {
        const ttfb = navEntries[0].responseStart;
        reportMetric('TTFB', ttfb, rate('TTFB', ttfb));
      }
    }
    catch (e) {
      // TTFB not available.
    }
  }

})(Drupal);

/**
 * @file
 * Error Tracker — Client-side JavaScript error collection.
 *
 * Captures global errors, unhandled promise rejections, and console.error
 * calls, then sends them to the Insights Hub error endpoint. Includes
 * deduplication to avoid flooding the API with repeated errors.
 *
 * Fase 7 — Insights Hub.
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.insightsErrorTracker = {
    attach: function (context) {
      once('insights-error-tracker', 'body', context).forEach(function () {
        if (!drupalSettings.insightsHub || !drupalSettings.insightsHub.enabled) {
          return;
        }

        var endpoint = drupalSettings.insightsHub.errorEndpoint || '/api/v1/insights/errors';
        var tenantId = drupalSettings.insightsHub.tenantId || null;
        var sentErrors = {};

        // Global error handler.
        window.addEventListener('error', function (event) {
          reportError({
            error_type: 'js',
            severity: 'error',
            message: event.message || 'Unknown error',
            stack_trace: event.error ? event.error.stack : null,
            source_url: event.filename || '',
            line_number: event.lineno || 0,
            column_number: event.colno || 0,
            page_url: window.location.pathname,
            tenant_id: tenantId
          });
        });

        // Unhandled promise rejection handler.
        window.addEventListener('unhandledrejection', function (event) {
          var message = 'Unhandled Promise Rejection';
          var stack = null;
          if (event.reason) {
            message = event.reason.message || String(event.reason);
            stack = event.reason.stack || null;
          }
          reportError({
            error_type: 'js',
            severity: 'warning',
            message: message,
            stack_trace: stack,
            page_url: window.location.pathname,
            tenant_id: tenantId
          });
        });

        // Console.error intercept.
        var originalConsoleError = console.error;
        console.error = function () {
          var args = Array.prototype.slice.call(arguments);
          var message = args.map(function (arg) {
            return typeof arg === 'object' ? JSON.stringify(arg) : String(arg);
          }).join(' ');

          reportError({
            error_type: 'js',
            severity: 'warning',
            message: '[console.error] ' + message.substring(0, 500),
            page_url: window.location.pathname,
            tenant_id: tenantId
          });

          originalConsoleError.apply(console, arguments);
        };

        /**
         * Report an error to the API, with deduplication.
         *
         * @param {Object} data
         *   Error data payload.
         */
        function reportError(data) {
          // Dedup: don't send the same error message twice per page load.
          var hash = simpleHash(data.message + (data.stack_trace || ''));
          if (sentErrors[hash]) {
            return;
          }
          sentErrors[hash] = true;

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
         * Simple string hash for deduplication.
         *
         * @param {string} str
         *   String to hash.
         *
         * @return {string}
         *   Base-36 hash string.
         */
        function simpleHash(str) {
          var hash = 0;
          for (var i = 0; i < str.length; i++) {
            var chr = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + chr;
            hash |= 0;
          }
          return hash.toString(36);
        }
      });
    }
  };

})(Drupal, drupalSettings, once);

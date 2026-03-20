/**
 * @file
 * BigPipe-safe replacement for Drupal's once() when used with drupalSettings.
 *
 * BIGPIPE-TIMING-001: once() marks elements as processed on first attach, even
 * if drupalSettings isn't ready yet (BigPipe streams it later). This helper
 * only marks the element as processed AFTER the callback succeeds.
 *
 * Usage:
 *   Drupal.behaviors.myBehavior = {
 *     attach: function(context) {
 *       Drupal.bigpipeSafeOnce('my-id', '.my-selector', context, function(el) {
 *         var settings = drupalSettings.mySettings;
 *         if (!settings) return false; // Return false = don't mark as processed
 *         // ... do work ...
 *         return true; // Return true = mark as processed
 *       });
 *     }
 *   };
 */
(function () {
  'use strict';

  // Wait for Drupal global to be available (BigPipe may stream it later).
  function register() {
    if (typeof Drupal === 'undefined') {
      // Drupal.js hasn't loaded yet — retry when DOM is ready.
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', register);
      } else {
        // DOM ready but Drupal still not defined — wait briefly.
        setTimeout(register, 50);
      }
      return;
    }

    /**
     * Process elements only when callback returns true.
     *
     * @param {string} id - Unique identifier for this processing.
     * @param {string} selector - CSS selector for target elements.
     * @param {Element} context - The attach context.
     * @param {function} callback - Function receiving each element. Return true to mark done.
     */
    Drupal.bigpipeSafeOnce = function (id, selector, context, callback) {
      var root = (context && context.querySelectorAll) ? context : document;
      var elements = root.querySelectorAll(selector);

      elements.forEach(function (el) {
        var attr = 'data-bp-' + id;
        if (el.hasAttribute(attr)) {
          return; // Already processed.
        }

        var result = callback(el);
        if (result === true) {
          el.setAttribute(attr, 'done');
        }
        // If false or undefined, don't mark — will retry on next attach.
      });
    };
  }

  register();
})();

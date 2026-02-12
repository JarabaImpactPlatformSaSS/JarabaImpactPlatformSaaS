/**
 * @file
 * Online/offline detection and connectivity indicator.
 *
 * Monitors navigator.onLine status and displays a bar
 * at the top of the page when the user loses connectivity.
 * Shows a brief "reconnected" message when back online.
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.jarabaPwaOfflineIndicator = {
    attach: function (context) {
      var indicators = once('jaraba-pwa-offline-indicator', 'body', context);

      indicators.forEach(function () {
        initOfflineIndicator();
      });
    }
  };

  /**
   * Initializes the offline/online indicator.
   */
  function initOfflineIndicator() {
    var offlineBar = document.querySelector('[data-pwa-offline-indicator]');
    var onlineBar = document.querySelector('[data-pwa-online-indicator]');
    var wasOffline = false;

    /**
     * Shows the offline indicator bar.
     */
    function showOffline() {
      wasOffline = true;
      document.body.classList.add('pwa-is-offline');
      document.body.classList.remove('pwa-is-online');

      if (offlineBar) {
        offlineBar.removeAttribute('hidden');
        offlineBar.classList.add('pwa-offline-indicator--visible');
      }

      if (onlineBar) {
        onlineBar.setAttribute('hidden', '');
        onlineBar.classList.remove('pwa-online-indicator--visible');
      }
    }

    /**
     * Hides the offline indicator and briefly shows the online message.
     */
    function showOnline() {
      document.body.classList.remove('pwa-is-offline');
      document.body.classList.add('pwa-is-online');

      if (offlineBar) {
        offlineBar.classList.remove('pwa-offline-indicator--visible');
        offlineBar.setAttribute('hidden', '');
      }

      // Only show the "reconnected" message if we were actually offline.
      if (wasOffline && onlineBar) {
        onlineBar.removeAttribute('hidden');
        onlineBar.classList.add('pwa-online-indicator--visible');

        // Auto-hide after 3 seconds.
        setTimeout(function () {
          onlineBar.classList.remove('pwa-online-indicator--visible');
          setTimeout(function () {
            onlineBar.setAttribute('hidden', '');
          }, 300);
        }, 3000);

        wasOffline = false;
      }
    }

    // Listen to online/offline events.
    window.addEventListener('online', showOnline);
    window.addEventListener('offline', showOffline);

    // Check initial state.
    if (!navigator.onLine) {
      showOffline();
    }
  }

})(Drupal, once);

/**
 * @file
 * Push notification opt-in dialog behavior.
 *
 * Manages the user consent flow for push notifications:
 * 1. Checks if push is supported and permission state.
 * 2. Shows opt-in dialog if permission is 'default' (not yet asked).
 * 3. Handles accept/dismiss with the Push API + VAPID.
 * 4. Sends subscription to the server via /api/v1/pwa/push/subscribe.
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.jarabaPwaPushOptin = {
    attach: function (context) {
      var dialogs = once('jaraba-pwa-push-optin', '[data-pwa-push-optin]', context);

      dialogs.forEach(function (dialog) {
        initPushOptin(dialog);
      });
    }
  };

  /**
   * Initializes the push opt-in dialog.
   *
   * @param {HTMLElement} dialog - The opt-in dialog element.
   */
  function initPushOptin(dialog) {
    // Check browser support.
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
      return;
    }

    var vapidKey = dialog.getAttribute('data-vapid-key');
    if (!vapidKey) {
      return;
    }

    var acceptBtn = dialog.querySelector('[data-pwa-push-accept]');
    var dismissBtn = dialog.querySelector('[data-pwa-push-dismiss]');

    // Check current permission state.
    if (Notification.permission === 'granted') {
      // Already granted, subscribe silently if needed.
      subscribeToPush(vapidKey);
      return;
    }

    if (Notification.permission === 'denied') {
      // User has blocked notifications, do not show dialog.
      return;
    }

    // Permission is 'default' - show opt-in dialog after a delay.
    var dismissedKey = 'jaraba_pwa_push_dismissed';
    var dismissed = localStorage.getItem(dismissedKey);
    if (dismissed) {
      var dismissedAt = parseInt(dismissed, 10);
      var daysSinceDismissed = (Date.now() - dismissedAt) / (1000 * 60 * 60 * 24);
      // Do not show again for 7 days after dismissal.
      if (daysSinceDismissed < 7) {
        return;
      }
    }

    // Show dialog after 5 seconds.
    setTimeout(function () {
      dialog.removeAttribute('hidden');
      dialog.classList.add('pwa-push-optin--visible');
    }, 5000);

    // Accept handler.
    if (acceptBtn) {
      acceptBtn.addEventListener('click', function () {
        hideDialog(dialog);
        subscribeToPush(vapidKey);
      });
    }

    // Dismiss handler.
    if (dismissBtn) {
      dismissBtn.addEventListener('click', function () {
        hideDialog(dialog);
        localStorage.setItem(dismissedKey, Date.now().toString());
      });
    }
  }

  /**
   * Hides the opt-in dialog.
   *
   * @param {HTMLElement} dialog - The dialog element to hide.
   */
  function hideDialog(dialog) {
    dialog.classList.remove('pwa-push-optin--visible');
    dialog.classList.add('pwa-push-optin--hiding');
    setTimeout(function () {
      dialog.setAttribute('hidden', '');
      dialog.classList.remove('pwa-push-optin--hiding');
    }, 300);
  }

  /**
   * Subscribes to push notifications via the Push API.
   *
   * @param {string} vapidKey - The VAPID public key (base64url).
   */
  function subscribeToPush(vapidKey) {
    navigator.serviceWorker.ready.then(function (registration) {
      return registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(vapidKey)
      });
    }).then(function (subscription) {
      return sendSubscriptionToServer(subscription);
    }).catch(function (error) {
      if (typeof console !== 'undefined') {
        console.error('[Jaraba PWA] Push subscription failed:', error);
      }
    });
  }

  /**
   * Sends the push subscription to the server.
   *
   * @param {PushSubscription} subscription - The browser push subscription.
   * @return {Promise} The fetch promise.
   */
  function sendSubscriptionToServer(subscription) {
    var data = subscription.toJSON();

    return fetch('/api/v1/pwa/push/subscribe', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        endpoint: data.endpoint,
        keys: {
          auth: data.keys.auth,
          p256dh: data.keys.p256dh
        }
      })
    }).then(function (response) {
      if (!response.ok) {
        throw new Error('Server responded with ' + response.status);
      }
      return response.json();
    });
  }

  /**
   * Converts a base64url string to a Uint8Array for the Push API.
   *
   * @param {string} base64String - The base64url-encoded string.
   * @return {Uint8Array} The decoded byte array.
   */
  function urlBase64ToUint8Array(base64String) {
    var padding = '='.repeat((4 - base64String.length % 4) % 4);
    var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    var rawData = window.atob(base64);
    var outputArray = new Uint8Array(rawData.length);
    for (var i = 0; i < rawData.length; i++) {
      outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
  }

})(Drupal, drupalSettings, once);

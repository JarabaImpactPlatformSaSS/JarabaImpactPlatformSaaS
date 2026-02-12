/**
 * @file
 * Background sync status indicator.
 *
 * Monitors pending sync actions and displays a status bar
 * when there are offline actions waiting to be synced.
 * Triggers sync when connectivity is restored.
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.jarabaPwaSyncStatus = {
    attach: function (context) {
      var elements = once('jaraba-pwa-sync-status', 'body', context);

      elements.forEach(function () {
        initSyncStatus();
      });
    }
  };

  /**
   * Initializes the background sync status monitor.
   */
  function initSyncStatus() {
    var syncQueue = [];
    var isSyncing = false;
    var statusElement = null;

    /**
     * Creates the sync status indicator element.
     *
     * @return {HTMLElement} The status indicator element.
     */
    function createStatusElement() {
      if (statusElement) {
        return statusElement;
      }

      statusElement = document.createElement('div');
      statusElement.className = 'pwa-sync-status';
      statusElement.setAttribute('role', 'status');
      statusElement.setAttribute('aria-live', 'polite');
      statusElement.setAttribute('hidden', '');
      statusElement.innerHTML =
        '<div class="pwa-sync-status__content">' +
        '<span class="pwa-sync-status__spinner" aria-hidden="true"></span>' +
        '<span class="pwa-sync-status__message"></span>' +
        '<span class="pwa-sync-status__count"></span>' +
        '</div>';

      document.body.appendChild(statusElement);
      return statusElement;
    }

    /**
     * Updates the sync status display.
     *
     * @param {number} pendingCount - Number of pending sync actions.
     * @param {boolean} syncing - Whether sync is currently in progress.
     */
    function updateStatus(pendingCount, syncing) {
      var el = createStatusElement();
      var messageEl = el.querySelector('.pwa-sync-status__message');
      var countEl = el.querySelector('.pwa-sync-status__count');

      if (pendingCount === 0 && !syncing) {
        el.setAttribute('hidden', '');
        el.classList.remove('pwa-sync-status--visible');
        return;
      }

      el.removeAttribute('hidden');
      el.classList.add('pwa-sync-status--visible');

      if (syncing) {
        el.classList.add('pwa-sync-status--syncing');
        messageEl.textContent = Drupal.t('Syncing offline changes...');
      }
      else {
        el.classList.remove('pwa-sync-status--syncing');
        messageEl.textContent = Drupal.t('Waiting to sync');
      }

      countEl.textContent = '(' + pendingCount + ')';
    }

    /**
     * Queues an action for background sync.
     *
     * @param {Object} action - The sync action to queue.
     */
    function queueAction(action) {
      syncQueue.push(action);
      // Store in localStorage for persistence.
      try {
        localStorage.setItem('jaraba_pwa_sync_queue', JSON.stringify(syncQueue));
      }
      catch (e) {
        // Storage full or unavailable.
      }
      updateStatus(syncQueue.length, false);
    }

    /**
     * Processes the sync queue by sending actions to the server.
     */
    function processSyncQueue() {
      if (isSyncing || syncQueue.length === 0 || !navigator.onLine) {
        return;
      }

      isSyncing = true;
      updateStatus(syncQueue.length, true);

      fetch('/api/v1/pwa/sync', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ actions: syncQueue })
      })
        .then(function (response) {
          if (response.ok) {
            return response.json();
          }
          throw new Error('Sync failed: ' + response.status);
        })
        .then(function (data) {
          if (data.success) {
            syncQueue = [];
            try {
              localStorage.removeItem('jaraba_pwa_sync_queue');
            }
            catch (e) {
              // Ignore storage errors.
            }
          }
          updateStatus(syncQueue.length, false);
          isSyncing = false;
        })
        .catch(function () {
          updateStatus(syncQueue.length, false);
          isSyncing = false;
        });
    }

    // Load persisted queue from localStorage.
    try {
      var stored = localStorage.getItem('jaraba_pwa_sync_queue');
      if (stored) {
        syncQueue = JSON.parse(stored);
        updateStatus(syncQueue.length, false);
      }
    }
    catch (e) {
      syncQueue = [];
    }

    // Process when coming back online.
    window.addEventListener('online', function () {
      setTimeout(processSyncQueue, 1000);
    });

    // Try to process on load if online.
    if (navigator.onLine && syncQueue.length > 0) {
      setTimeout(processSyncQueue, 2000);
    }

    // Expose queue method globally.
    Drupal.jarabaPwa = Drupal.jarabaPwa || {};
    Drupal.jarabaPwa.queueAction = queueAction;
    Drupal.jarabaPwa.processSyncQueue = processSyncQueue;
  }

})(Drupal, drupalSettings, once);

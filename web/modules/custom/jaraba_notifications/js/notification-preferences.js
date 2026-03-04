/**
 * @file
 * Notification Preferences toggle handler.
 *
 * ROUTE-LANGPREFIX-001: API URL via drupalSettings, NUNCA hardcoded.
 * CSRF-JS-CACHE-001: Token cacheado en variable del modulo.
 * INNERHTML-XSS-001: No se inserta HTML — solo checkbox state change.
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  let csrfToken = null;

  /**
   * Fetches CSRF token from Drupal session endpoint.
   */
  async function getCsrfToken() {
    if (csrfToken) {
      return csrfToken;
    }
    const response = await fetch(Drupal.url('session/token'));
    csrfToken = await response.text();
    return csrfToken;
  }

  Drupal.behaviors.notificationPreferences = {
    attach: function (context) {
      var apiUrl = drupalSettings.jarabaNotifications
        ? drupalSettings.jarabaNotifications.preferencesApiUrl
        : null;

      if (!apiUrl) {
        return;
      }

      var toggles = once('notif-pref', '.js-notif-pref-toggle', context);

      toggles.forEach(function (checkbox) {
        checkbox.addEventListener('change', async function () {
          var type = this.dataset.type;
          var channel = this.dataset.channel;
          var enabled = this.checked;
          var toggle = this;

          toggle.disabled = true;

          try {
            var token = await getCsrfToken();
            var response = await fetch(apiUrl, {
              method: 'PATCH',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': token
              },
              body: JSON.stringify({
                type: type,
                channel: channel,
                enabled: enabled
              })
            });

            if (!response.ok) {
              // Revert on failure.
              toggle.checked = !enabled;
              Drupal.announce(Drupal.t('Error al actualizar la preferencia.'));
            }
          }
          catch (err) {
            toggle.checked = !enabled;
            Drupal.announce(Drupal.t('Error de conexion.'));
          }
          finally {
            toggle.disabled = false;
          }
        });
      });
    }
  };

})(Drupal, drupalSettings, once);

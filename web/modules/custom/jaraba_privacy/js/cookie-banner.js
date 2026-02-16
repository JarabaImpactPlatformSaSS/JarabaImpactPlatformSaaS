/**
 * @file
 * JavaScript del banner de cookies LSSI-CE / ePrivacy.
 *
 * Gestiona la interacción del banner de consentimiento de cookies:
 * - Aceptar todas / Rechazar todas / Personalizar.
 * - Envío del consentimiento al endpoint API.
 * - Ocultación del banner tras acción.
 * - Persistencia en localStorage como fallback.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Comportamiento del banner de cookies.
   */
  Drupal.behaviors.jarabaCookieBanner = {
    attach: function (context) {
      once('jaraba-cookie-banner', '#jaraba-cookie-banner', context).forEach(function (banner) {
        var apiEndpoint = banner.getAttribute('data-api-endpoint') || '/api/v1/cookies/consent';
        var expiryDays = parseInt(banner.getAttribute('data-expiry-days'), 10) || 365;

        // Verificar si ya hay consentimiento en localStorage.
        if (Drupal.jarabaCookieBanner.hasLocalConsent()) {
          banner.remove();
          return;
        }

        // Aceptar todas.
        banner.querySelector('[data-action="accept-all"]').addEventListener('click', function () {
          Drupal.jarabaCookieBanner.submitConsent(apiEndpoint, {
            analytics: true,
            marketing: true,
            functional: true,
            thirdparty: true
          }, expiryDays, banner);
        });

        // Rechazar todas.
        banner.querySelector('[data-action="reject-all"]').addEventListener('click', function () {
          Drupal.jarabaCookieBanner.submitConsent(apiEndpoint, {
            analytics: false,
            marketing: false,
            functional: false,
            thirdparty: false
          }, expiryDays, banner);
        });

        // Personalizar: mostrar categorías.
        var customizeBtn = banner.querySelector('[data-action="customize"]');
        var categoriesPanel = document.getElementById('jaraba-cookie-categories');
        var saveBtn = banner.querySelector('[data-action="save"]');

        customizeBtn.addEventListener('click', function () {
          categoriesPanel.hidden = false;
          customizeBtn.hidden = true;
          saveBtn.hidden = false;
        });

        // Guardar preferencias personalizadas.
        saveBtn.addEventListener('click', function () {
          var checkboxes = categoriesPanel.querySelectorAll('.jaraba-cookie-banner__checkbox');
          var consent = {};

          checkboxes.forEach(function (cb) {
            var category = cb.getAttribute('data-category');
            if (category && category !== 'necessary') {
              consent[category] = cb.checked;
            }
          });

          Drupal.jarabaCookieBanner.submitConsent(apiEndpoint, consent, expiryDays, banner);
        });
      });
    }
  };

  /**
   * Utilidades del banner de cookies.
   */
  Drupal.jarabaCookieBanner = {

    /**
     * Envía el consentimiento al API y oculta el banner.
     */
    submitConsent: function (endpoint, consent, expiryDays, banner) {
      fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(consent)
      })
      .then(function (response) { return response.json(); })
      .then(function (data) {
        if (data.success) {
          Drupal.jarabaCookieBanner.setLocalConsent(consent, expiryDays);
          Drupal.jarabaCookieBanner.hideBanner(banner);
        }
      })
      .catch(function () {
        // Fallback: guardar en localStorage aunque falle el API.
        Drupal.jarabaCookieBanner.setLocalConsent(consent, expiryDays);
        Drupal.jarabaCookieBanner.hideBanner(banner);
      });
    },

    /**
     * Oculta el banner con animación.
     */
    hideBanner: function (banner) {
      banner.style.transition = 'opacity 300ms ease, transform 300ms ease';
      banner.style.opacity = '0';
      banner.style.transform = 'translateY(20px)';
      setTimeout(function () {
        banner.remove();
      }, 300);
    },

    /**
     * Guarda el consentimiento en localStorage.
     */
    setLocalConsent: function (consent, expiryDays) {
      try {
        var data = {
          consent: consent,
          timestamp: Date.now(),
          expiry: Date.now() + (expiryDays * 86400000)
        };
        localStorage.setItem('jaraba_cookie_consent', JSON.stringify(data));
      }
      catch (e) {
        // localStorage no disponible.
      }
    },

    /**
     * Verifica si ya existe consentimiento local vigente.
     */
    hasLocalConsent: function () {
      try {
        var stored = localStorage.getItem('jaraba_cookie_consent');
        if (!stored) {
          return false;
        }
        var data = JSON.parse(stored);
        return data.expiry && data.expiry > Date.now();
      }
      catch (e) {
        return false;
      }
    }
  };

})(Drupal, once);

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
        var apiEndpoint = banner.getAttribute('data-api-endpoint') || (drupalSettings.jarabaCookieBanner || {}).apiEndpoint;
        // Store endpoint for reopenBanner() when banner is no longer in DOM.
        Drupal.jarabaCookieBanner._apiEndpoint = apiEndpoint;
        var expiryDays = parseInt(banner.getAttribute('data-expiry-days'), 10) || 365;

        // Verificar si ya hay consentimiento en localStorage.
        if (Drupal.jarabaCookieBanner.hasLocalConsent()) {
          banner.remove();
          return;
        }

        // Restaurar preferencias anteriores si el usuario reabrió el banner.
        var previousConsent = Drupal.jarabaCookieBanner._restorePreviousConsent(banner);

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

        // Cerrar con Escape (WCAG 2.1 — dismissible).
        document.addEventListener('keydown', function (e) {
          if (e.key === 'Escape' && banner.parentNode) {
            Drupal.jarabaCookieBanner.submitConsent(apiEndpoint, {
              analytics: false,
              marketing: false,
              functional: false,
              thirdparty: false
            }, expiryDays, banner);
          }
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

    // Endpoints inicializados desde drupalSettings (disponibles sin banner en DOM).
    _apiEndpoint: (drupalSettings.jarabaCookieBanner || {}).apiEndpoint || null,
    _revokeEndpoint: (drupalSettings.jarabaCookieBanner || {}).revokeEndpoint || null,


    /**
     * Envía el consentimiento al API y oculta el banner.
     */
    submitConsent: function (endpoint, consent, expiryDays, banner) {
      // Guardar localmente y ocultar SIEMPRE primero (UX instantanea).
      // La persistencia server-side es best-effort, no bloquea la UX.
      Drupal.jarabaCookieBanner.setLocalConsent(consent, expiryDays);
      Drupal.jarabaCookieBanner.hideBanner(banner);

      // Persistir en servidor de forma asincrona (best-effort).
      fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(consent)
      }).catch(function () {
        // Server-side persistence failed — localStorage already saved.
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
    },

    /**
     * Restaura preferencias anteriores en los checkboxes del banner.
     *
     * Lee sessionStorage (escrito por reopenBanner) y pre-marca los checkboxes
     * con las preferencias que el usuario ya tenía. Abre el panel de categorías
     * automáticamente para que pueda editar directamente.
     */
    _restorePreviousConsent: function (banner) {
      try {
        var stored = sessionStorage.getItem('jaraba_cookie_consent_previous');
        sessionStorage.removeItem('jaraba_cookie_consent_previous');
        if (!stored) {
          return null;
        }
        var data = JSON.parse(stored);
        var consent = data.consent || {};

        // Pre-marcar checkboxes según las preferencias anteriores.
        var categoriesPanel = document.getElementById('jaraba-cookie-categories');
        if (categoriesPanel) {
          var checkboxes = categoriesPanel.querySelectorAll('.jaraba-cookie-banner__checkbox');
          checkboxes.forEach(function (cb) {
            var category = cb.getAttribute('data-category');
            if (category && category !== 'necessary' && consent[category] !== undefined) {
              cb.checked = consent[category];
            }
          });

          // Abrir el panel de categorías directamente.
          categoriesPanel.hidden = false;
          var customizeBtn = banner.querySelector('[data-action="customize"]');
          var saveBtn = banner.querySelector('[data-action="save"]');
          if (customizeBtn) { customizeBtn.hidden = true; }
          if (saveBtn) { saveBtn.hidden = false; }
        }

        return consent;
      }
      catch (e) {
        return null;
      }
    },

    /**
     * Revoca el consentimiento y reabre el banner (RGPD Art. 7.3).
     *
     * Envia un registro de revocacion al servidor, limpia localStorage
     * y recarga la pagina para que hook_page_bottom() inyecte el banner.
     */
    reopenBanner: function () {
      try {
        // Preservar preferencias anteriores para pre-rellenar el banner.
        var previous = localStorage.getItem('jaraba_cookie_consent');
        if (previous) {
          sessionStorage.setItem('jaraba_cookie_consent_previous', previous);
        }
        localStorage.removeItem('jaraba_cookie_consent');
      }
      catch (e) {
        // localStorage no disponible.
      }

      var revokeEndpoint = Drupal.jarabaCookieBanner._revokeEndpoint;
      if (!revokeEndpoint) {
        location.reload();
        return;
      }

      fetch(revokeEndpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: '{}'
      })
      .finally(function () {
        location.reload();
      });
    }
  };

})(Drupal, once);

/**
 * @file visitor-vertical-detection.js
 * Deteccion client-side de la vertical del visitante anonimo.
 *
 * Implementa una cascada de deteccion:
 * 1. UTM explicito (?utm_vertical=agroconecta) → prioridad maxima
 * 2. Path de la URL actual (/emprendimiento/*, /agroconecta/*)
 * 3. Referrer keywords (google search terms)
 * 4. sessionStorage previo (visita anterior en la misma sesion)
 * 5. Default: null (muestra selector visual)
 *
 * El resultado se almacena en sessionStorage como 'jaraba_detected_vertical'
 * y se expone como window.jarabaVertical para integracion con otros scripts.
 *
 * Eventos que dispara:
 * - visitor_vertical_detected: cuando se detecta una vertical
 *
 * @see docs/implementacion/2026-02-12_F3_Visitor_Journey_Complete_Doc178_Implementacion.md
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Storage key for detected vertical.
   */
  var STORAGE_KEY = 'jaraba_detected_vertical';

  /**
   * Storage key for detection source.
   */
  var SOURCE_KEY = 'jaraba_detection_source';

  /**
   * Map of URL path prefixes to verticals.
   */
  var PATH_MAP = {
    '/emprendimiento': 'emprendimiento',
    '/emprender': 'emprendimiento',
    '/agroconecta': 'agroconecta',
    '/agro': 'agroconecta',
    '/comercioconecta': 'comercioconecta',
    '/comercio': 'comercioconecta',
    '/serviciosconecta': 'serviciosconecta',
    '/servicios': 'serviciosconecta',
    '/empleabilidad': 'empleabilidad',
    '/empleo': 'empleabilidad',
    '/talento': 'empleabilidad'
  };

  /**
   * Map of referrer keywords to verticals.
   * Detects vertical from search engine referrer queries.
   */
  var KEYWORD_MAP = {
    // Emprendimiento
    'emprender': 'emprendimiento',
    'emprendimiento': 'emprendimiento',
    'startup': 'emprendimiento',
    'negocio digital': 'emprendimiento',
    'crear empresa': 'emprendimiento',
    'plan de negocio': 'emprendimiento',
    'madurez digital': 'emprendimiento',
    'business model canvas': 'emprendimiento',

    // AgroConecta
    'vender online campo': 'agroconecta',
    'venta directa agricultor': 'agroconecta',
    'tienda online campo': 'agroconecta',
    'agroconecta': 'agroconecta',
    'venta productos agricolas': 'agroconecta',
    'comercio rural': 'agroconecta',

    // ComercioConecta
    'seo local': 'comercioconecta',
    'negocio local online': 'comercioconecta',
    'google maps negocio': 'comercioconecta',
    'comercio local': 'comercioconecta',
    'tienda local': 'comercioconecta',
    'qr carta': 'comercioconecta',

    // ServiciosConecta
    'freelance gestion': 'serviciosconecta',
    'presupuesto servicios': 'serviciosconecta',
    'gestion clientes': 'serviciosconecta',
    'propuesta profesional': 'serviciosconecta',
    'agenda citas': 'serviciosconecta',

    // Empleabilidad
    'buscar empleo': 'empleabilidad',
    'empleo digital': 'empleabilidad',
    'cv online': 'empleabilidad',
    'linkedin perfil': 'empleabilidad',
    'empleabilidad': 'empleabilidad',
    'mejorar cv': 'empleabilidad',
    'buscar trabajo': 'empleabilidad'
  };

  /**
   * Valid verticals set.
   */
  var VALID_VERTICALS = [
    'emprendimiento',
    'agroconecta',
    'comercioconecta',
    'serviciosconecta',
    'empleabilidad'
  ];

  /**
   * Behavior: Visitor Vertical Detection.
   */
  Drupal.behaviors.visitorVerticalDetection = {
    attach: function (context) {
      once('visitor-vertical-detection', 'body', context).forEach(function () {
        var result = detect();

        if (result.vertical) {
          // Store in sessionStorage
          try {
            sessionStorage.setItem(STORAGE_KEY, result.vertical);
            sessionStorage.setItem(SOURCE_KEY, result.source);
          }
          catch (e) {
            // sessionStorage not available (private mode, etc.)
          }

          // Expose globally
          window.jarabaVertical = result.vertical;
          window.jarabaVerticalSource = result.source;

          // Dispatch custom event for other scripts
          document.dispatchEvent(new CustomEvent('jaraba:vertical-detected', {
            detail: result
          }));

          // Track via analytics if available
          if (typeof Drupal.jarabaAnalytics !== 'undefined' && typeof Drupal.jarabaAnalytics.track === 'function') {
            Drupal.jarabaAnalytics.track('visitor_vertical_detected', {
              vertical: result.vertical,
              source: result.source
            });
          }

          // Highlight in vertical selector if present
          highlightVerticalCard(result.vertical);
        }
      });
    }
  };

  /**
   * Detect vertical using cascading strategy.
   *
   * @returns {{vertical: string|null, source: string}}
   */
  function detect() {
    var result;

    // 1. UTM explicit parameter (highest priority)
    result = detectFromUTM();
    if (result) return { vertical: result, source: 'utm' };

    // 2. URL path prefix
    result = detectFromPath();
    if (result) return { vertical: result, source: 'path' };

    // 3. Referrer keywords
    result = detectFromReferrer();
    if (result) return { vertical: result, source: 'referrer' };

    // 4. Previous sessionStorage detection
    result = detectFromStorage();
    if (result) return { vertical: result, source: 'session' };

    // 5. drupalSettings from backend detection
    result = detectFromSettings();
    if (result) return { vertical: result, source: 'backend' };

    return { vertical: null, source: 'none' };
  }

  /**
   * Detect from UTM parameters.
   */
  function detectFromUTM() {
    var params = new URLSearchParams(window.location.search);

    // Explicit vertical parameter
    var utmVertical = params.get('utm_vertical') || params.get('vertical');
    if (utmVertical && VALID_VERTICALS.indexOf(utmVertical) !== -1) {
      return utmVertical;
    }

    // Source-based detection
    var utmSource = params.get('utm_source') || '';
    var utmCampaign = params.get('utm_campaign') || '';
    var combined = (utmSource + ' ' + utmCampaign).toLowerCase();

    for (var i = 0; i < VALID_VERTICALS.length; i++) {
      if (combined.indexOf(VALID_VERTICALS[i]) !== -1) {
        return VALID_VERTICALS[i];
      }
    }

    return null;
  }

  /**
   * Detect from current URL path.
   */
  function detectFromPath() {
    var path = window.location.pathname.toLowerCase();
    var paths = Object.keys(PATH_MAP);

    for (var i = 0; i < paths.length; i++) {
      if (path.indexOf(paths[i]) === 0) {
        return PATH_MAP[paths[i]];
      }
    }

    return null;
  }

  /**
   * Detect from referrer search keywords.
   */
  function detectFromReferrer() {
    var referrer = document.referrer;
    if (!referrer) return null;

    // Only check search engine referrers
    var searchEngines = ['google.', 'bing.', 'duckduckgo.', 'yahoo.', 'ecosia.'];
    var isSearch = searchEngines.some(function (engine) {
      return referrer.indexOf(engine) !== -1;
    });

    if (!isSearch) return null;

    // Extract query from referrer URL
    try {
      var refUrl = new URL(referrer);
      var query = (refUrl.searchParams.get('q') || refUrl.searchParams.get('query') || '').toLowerCase();

      if (!query) return null;

      var keywords = Object.keys(KEYWORD_MAP);
      for (var i = 0; i < keywords.length; i++) {
        if (query.indexOf(keywords[i]) !== -1) {
          return KEYWORD_MAP[keywords[i]];
        }
      }
    }
    catch (e) {
      // Invalid referrer URL.
    }

    return null;
  }

  /**
   * Detect from sessionStorage (previous detection in same session).
   */
  function detectFromStorage() {
    try {
      var stored = sessionStorage.getItem(STORAGE_KEY);
      if (stored && VALID_VERTICALS.indexOf(stored) !== -1) {
        return stored;
      }
    }
    catch (e) {
      // sessionStorage not available.
    }
    return null;
  }

  /**
   * Detect from backend drupalSettings.
   */
  function detectFromSettings() {
    var settings = drupalSettings.jarabaVertical || {};
    if (settings.detected && VALID_VERTICALS.indexOf(settings.detected) !== -1) {
      return settings.detected;
    }
    return null;
  }

  /**
   * Highlight the detected vertical in the homepage selector.
   */
  function highlightVerticalCard(vertical) {
    var card = document.querySelector('.vertical-card[data-vertical="' + vertical + '"]');
    if (card && !card.classList.contains('vertical-card--highlighted')) {
      card.classList.add('vertical-card--highlighted');
    }
  }

})(Drupal, drupalSettings, once);

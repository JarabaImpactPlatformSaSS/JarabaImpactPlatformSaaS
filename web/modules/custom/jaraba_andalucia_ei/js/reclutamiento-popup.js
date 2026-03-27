/**
 * @file
 * Popup dual de captación Andalucía +ei para meta-sitios corporativos.
 *
 * POPUP-DUAL-SELECTOR-001: Selector participante/negocio piloto.
 * POPUP-SHARED-DISMISS-001: localStorage key unificada entre metasitios.
 * FUNNEL-COMPLETENESS-001: data-track-cta + data-track-position en todos los CTAs.
 * INNERHTML-XSS-001: Drupal.checkPlain() para valores dinámicos.
 * NO-HARDCODE-PRICE-001: Datos desde drupalSettings, no hardcodeados.
 * ICON-CANVAS-INLINE-002: SVGs con hex explícito de paleta Jaraba.
 *
 * Se muestra UNA vez por sesión en la home de los meta-sitios:
 * plataformadeecosistemas.es, pepejaraba.com, jarabaimpact.com,
 * plataformadeecosistemas.com (si mostrar_popup_saas=true).
 * Dismiss guarda flag en localStorage con TTL configurable para no repetir.
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  var STORAGE_KEY = 'aei_rec_popup_dismissed';

  // ICON-CANVAS-INLINE-002: SVG inline con hex de paleta Jaraba.
  // NUNCA currentColor — se renderizan via innerHTML donde herencia es impredecible.
  var ICONS = {
    users: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none">' +
      '<circle cx="9" cy="7" r="4" fill="#00A9A5"/>' +
      '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" fill="#00A9A5" opacity="0.3"/>' +
      '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="#00A9A5" stroke-width="2"/>' +
      '<path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="#00A9A5" stroke-width="2" opacity="0.5"/>' +
      '</svg>',
    building: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none">' +
      '<rect x="4" y="2" width="16" height="20" rx="2" ry="2" fill="#FF8C42" opacity="0.2"/>' +
      '<rect x="4" y="2" width="16" height="20" rx="2" ry="2" stroke="#FF8C42" stroke-width="2"/>' +
      '<path d="M9 22v-4h6v4" stroke="#FF8C42" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>' +
      '<rect x="7" y="5" width="2.5" height="2.5" rx="0.5" fill="#FF8C42" opacity="0.5"/>' +
      '<rect x="10.75" y="5" width="2.5" height="2.5" rx="0.5" fill="#FF8C42" opacity="0.5"/>' +
      '<rect x="14.5" y="5" width="2.5" height="2.5" rx="0.5" fill="#FF8C42" opacity="0.5"/>' +
      '<rect x="7" y="9.5" width="2.5" height="2.5" rx="0.5" fill="#FF8C42" opacity="0.5"/>' +
      '<rect x="10.75" y="9.5" width="2.5" height="2.5" rx="0.5" fill="#FF8C42" opacity="0.5"/>' +
      '<rect x="14.5" y="9.5" width="2.5" height="2.5" rx="0.5" fill="#FF8C42" opacity="0.5"/>' +
      '<rect x="7" y="14" width="2.5" height="2.5" rx="0.5" fill="#FF8C42" opacity="0.5"/>' +
      '<rect x="14.5" y="14" width="2.5" height="2.5" rx="0.5" fill="#FF8C42" opacity="0.5"/>' +
      '</svg>',
    arrowLeft: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none">' +
      '<line x1="19" y1="12" x2="5" y2="12" stroke="#233D63" stroke-width="1.5" stroke-linecap="round"/>' +
      '<polyline points="12 19 5 12 12 5" stroke="#233D63" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>' +
      '</svg>',
    instagram: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#233D63" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">' +
      '<rect x="2" y="2" width="20" height="20" rx="5" ry="5"/>' +
      '<path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/>' +
      '<line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>' +
      '</svg>',
    globe: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none">' +
      '<circle cx="12" cy="12" r="10" stroke="#233D63" stroke-width="1.5"/>' +
      '<line x1="2" y1="12" x2="22" y2="12" stroke="#233D63" stroke-width="1"/>' +
      '<path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z" stroke="#00A9A5" stroke-width="1.5"/>' +
      '</svg>',
    star: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none">' +
      '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" stroke="#FF8C42" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>' +
      '</svg>',
    clipboard: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none">' +
      '<rect x="9" y="9" width="13" height="13" rx="2" stroke="#233D63" stroke-width="1.5"/>' +
      '<path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1" stroke="#233D63" stroke-width="1.5" stroke-linecap="round"/>' +
      '</svg>',
    cart: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none">' +
      '<path d="M16.5 9.4l-9-5.19M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z" stroke="#233D63" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>' +
      '<polyline points="3.27 6.96 12 12.01 20.73 6.96" stroke="#FF8C42" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>' +
      '<line x1="12" y1="22.08" x2="12" y2="12" stroke="#FF8C42" stroke-width="1.5"/>' +
      '</svg>'
  };

  /**
   * Sends a tracking event via sendBeacon (fire-and-forget).
   *
   * Respects Do Not Track. Consistent with funnel-analytics.js pattern.
   */
  function trackPopupEvent(eventType, data) {
    if (navigator.doNotTrack === '1') {
      return;
    }
    var payload = {
      event: eventType,
      timestamp: new Date().toISOString(),
      page_url: window.location.href,
      session_id: (window.jarabaAnalytics && window.jarabaAnalytics.sessionId) ? window.jarabaAnalytics.sessionId : null,
      data: data || {}
    };
    try {
      navigator.sendBeacon(
        '/api/v1/analytics/event',
        new Blob([JSON.stringify(payload)], { type: 'application/json' })
      );
    }
    catch (e) { /* Analytics must never break the popup. */ }
  }

  /**
   * Builds UTM-suffixed href from a base URL.
   */
  function buildHref(baseUrl, utmParams) {
    if (!utmParams) {
      return Drupal.checkPlain(baseUrl);
    }
    var separator = baseUrl.indexOf('?') !== -1 ? '&' : '?';
    return Drupal.checkPlain(baseUrl + separator + utmParams);
  }

  Drupal.behaviors.aeiRecPopup = {
    attach: function (context) {
      if (context !== document) {
        return;
      }

      var settings = drupalSettings.aeiRecPopup || {};
      if (!settings.solicitarUrl) {
        return;
      }

      // POPUP-SHARED-DISMISS-001: localStorage con TTL configurable (default 48h).
      var ttlMs = (settings.ttlHours || 48) * 60 * 60 * 1000;
      try {
        var stored = JSON.parse(localStorage.getItem(STORAGE_KEY));
        if (stored && stored.ts && (Date.now() - stored.ts) < ttlMs) {
          return;
        }
      }
      catch (e) {
        // First visit or corrupted data — continue.
      }

      var delayMs = settings.delayMs || 3000;
      setTimeout(function () {
        Drupal.behaviors.aeiRecPopup._show(settings);
      }, delayMs);
    },

    /**
     * Shows the popup with the dual selector (POPUP-DUAL-SELECTOR-001).
     *
     * If negocioEnabled=false, shows the original participante-only popup.
     */
    _show: function (settings) {
      var overlay = document.createElement('div');
      overlay.className = 'aei-popup__overlay';
      overlay.setAttribute('role', 'dialog');
      overlay.setAttribute('aria-modal', 'true');
      overlay.setAttribute('aria-label', Drupal.t('Programa Andalucía +ei — Captación 2ª Edición'));

      var card = document.createElement('div');
      card.className = 'aei-popup';
      overlay.appendChild(card);

      var negocioEnabled = settings.negocioEnabled !== false;
      var tasa = settings.tasaInsercion || 46;

      if (negocioEnabled) {
        // Show dual selector.
        card.innerHTML = this._buildSelector(tasa);
      }
      else {
        // Show participante-only path (backwards compatible).
        card.innerHTML = this._buildPathParticipante(settings);
      }

      document.body.appendChild(overlay);

      // Animate in.
      requestAnimationFrame(function () {
        overlay.classList.add('aei-popup__overlay--visible');
      });

      // Track impression.
      trackPopupEvent('popup_impression', {
        popup: 'aei_dual',
        step: negocioEnabled ? 'selector' : 'participante_only',
        host: window.location.hostname
      });

      // Bind close and navigation handlers.
      this._bindClose(overlay);
      if (negocioEnabled) {
        this._bindSelector(overlay, card, settings);
      }

      // Re-attach Drupal behaviors for dynamic CTA tracking (aida-tracking.js).
      Drupal.attachBehaviors(overlay);

      // Focus trap: focus the close button.
      var closeBtn = overlay.querySelector('.aei-popup__close');
      if (closeBtn) {
        closeBtn.focus();
      }
    },

    /**
     * Builds the dual selector HTML (Step 1).
     */
    _buildSelector: function (tasa) {
      return '<button class="aei-popup__close" aria-label="' + Drupal.t('Cerrar') + '">&times;</button>' +
        '<div class="aei-popup__badge">' + Drupal.t('Programa oficial · 2ª Edición') + '</div>' +
        '<h2 class="aei-popup__title">' + Drupal.t('Andalucía +ei') + '</h2>' +
        '<p class="aei-popup__subtitle">' + Drupal.t('Emprendimiento Aumentado con Inteligencia Artificial') + '</p>' +
        '<p class="aei-popup__desc">' + Drupal.t('¿Qué te interesa?') + '</p>' +
        '<div class="aei-popup__selector">' +
          '<button class="aei-popup__selector-option aei-popup__selector-option--participante" ' +
            'data-popup-path="participante" ' +
            'data-track-cta="aei_popup_select_participante" ' +
            'data-track-position="popup_selector">' +
            '<span class="aei-popup__selector-icon">' + ICONS.users + '</span>' +
            '<span class="aei-popup__selector-label">' + Drupal.t('Busco empleo') + '</span>' +
            '<span class="aei-popup__selector-hint">' + Drupal.t('Formación + orientación + incentivo') + '</span>' +
          '</button>' +
          '<button class="aei-popup__selector-option aei-popup__selector-option--negocio" ' +
            'data-popup-path="negocio" ' +
            'data-track-cta="aei_popup_select_negocio" ' +
            'data-track-position="popup_selector">' +
            '<span class="aei-popup__selector-icon">' + ICONS.building + '</span>' +
            '<span class="aei-popup__selector-label">' + Drupal.t('Tengo un negocio') + '</span>' +
            '<span class="aei-popup__selector-hint">' + Drupal.t('Digitalización gratuita para su empresa') + '</span>' +
          '</button>' +
        '</div>' +
        '<div class="aei-popup__social-proof">' +
          '<span class="aei-popup__social-proof-value">' + Drupal.checkPlain(String(tasa)) + '%</span>' +
          '<span class="aei-popup__social-proof-label">' + Drupal.t('de inserción laboral en la 1ª Edición') + '</span>' +
        '</div>' +
        '<p class="aei-popup__legal">' + Drupal.t('PIIL — Colectivos Vulnerables 2025. Junta de Andalucía + FSE+.') + '</p>';
    },

    /**
     * Builds the participante path HTML (Step 2a).
     */
    _buildPathParticipante: function (settings) {
      var modulePath = Drupal.checkPlain(settings.modulePath || '');
      var plazas = settings.plazasRestantes || 45;
      var incentivo = settings.incentivoEuros || 528;
      var tasa = settings.tasaInsercion || 46;
      var landingHref = buildHref(settings.landingUrl, settings.utmParams);
      var solicitarHref = buildHref(settings.solicitarUrl, settings.utmParams);

      return '<button class="aei-popup__close" aria-label="' + Drupal.t('Cerrar') + '">&times;</button>' +
        '<button class="aei-popup__back" aria-label="' + Drupal.t('Volver al selector') + '">' +
          ICONS.arrowLeft + '<span>' + Drupal.t('Volver') + '</span>' +
        '</button>' +
        '<picture class="aei-popup__hero">' +
          '<source srcset="' + modulePath + '/images/reclutamiento-popup-hero.webp" type="image/webp">' +
          '<img src="' + modulePath + '/images/reclutamiento-popup-hero.png" ' +
            'alt="' + Drupal.t('Grupo diverso de personas colaborando en un programa de inserción laboral en Andalucía') + '" ' +
            'width="520" height="293" loading="eager">' +
        '</picture>' +
        '<h2 class="aei-popup__title">' + Drupal.t('¿Buscas empleo en Andalucía?') + '</h2>' +
        '<p class="aei-popup__desc" id="aei-popup-desc-participante">' +
          Drupal.t('Programa gratuito de inserción laboral con orientación personalizada, formación certificada, mentoría con IA y un incentivo de @incentivo €. Financiado por la Junta de Andalucía y la Unión Europea.', { '@incentivo': incentivo }) +
        '</p>' +
        '<div class="aei-popup__stats">' +
          '<div class="aei-popup__stat">' +
            '<span class="aei-popup__stat-value">' + Drupal.checkPlain(String(plazas)) + '</span>' +
            '<span class="aei-popup__stat-label">' + Drupal.t('plazas') + '</span>' +
          '</div>' +
          '<div class="aei-popup__stat">' +
            '<span class="aei-popup__stat-value">' + Drupal.checkPlain(String(incentivo)) + ' €</span>' +
            '<span class="aei-popup__stat-label">' + Drupal.t('incentivo') + '</span>' +
          '</div>' +
          '<div class="aei-popup__stat aei-popup__stat--highlight">' +
            '<span class="aei-popup__stat-value">' + Drupal.checkPlain(String(tasa)) + '%</span>' +
            '<span class="aei-popup__stat-label">' + Drupal.t('inserción 1ª Ed.') + '</span>' +
          '</div>' +
        '</div>' +
        '<div class="aei-popup__actions">' +
          '<a href="' + landingHref + '" class="aei-popup__cta aei-popup__cta--primary" ' +
            'data-track-cta="aei_popup_ver_programa" data-track-position="popup_participante">' +
            Drupal.t('Ver programa completo') +
          '</a>' +
          '<a href="' + solicitarHref + '" class="aei-popup__cta aei-popup__cta--secondary" ' +
            'data-track-cta="aei_popup_solicitar" data-track-position="popup_participante">' +
            Drupal.t('Solicitar plaza') +
          '</a>' +
        '</div>' +
        '<p class="aei-popup__legal">' + Drupal.t('PIIL — Colectivos Vulnerables 2025. Junta de Andalucía + FSE+.') + '</p>';
    },

    /**
     * Builds the negocio piloto path HTML (Step 2b).
     */
    _buildPathNegocio: function (settings) {
      var modulePath = Drupal.checkPlain(settings.modulePath || '');
      var serviciosCount = settings.serviciosCount || 5;
      var valorMercado = settings.valorMercadoAnual || 2400;
      var pruebaHref = buildHref(settings.pruebaGratuitaUrl, settings.utmParams);

      // Servicios list — the 5 core services offered to pilot businesses.
      var servicios = [
        { icon: ICONS.instagram, label: Drupal.t('Gestión de redes sociales') },
        { icon: ICONS.globe, label: Drupal.t('Creación de página web') },
        { icon: ICONS.star, label: Drupal.t('Gestión de reseñas Google') },
        { icon: ICONS.clipboard, label: Drupal.t('Administración digital') },
        { icon: ICONS.cart, label: Drupal.t('Tienda online') }
      ];

      var serviciosHtml = '';
      var count = Math.min(serviciosCount, servicios.length);
      for (var i = 0; i < count; i++) {
        serviciosHtml += '<li class="aei-popup__servicio">' +
          '<span class="aei-popup__servicio-icon">' + servicios[i].icon + '</span>' +
          '<span>' + servicios[i].label + '</span>' +
          '</li>';
      }

      return '<button class="aei-popup__close" aria-label="' + Drupal.t('Cerrar') + '">&times;</button>' +
        '<button class="aei-popup__back" aria-label="' + Drupal.t('Volver al selector') + '">' +
          ICONS.arrowLeft + '<span>' + Drupal.t('Volver') + '</span>' +
        '</button>' +
        '<picture class="aei-popup__hero">' +
          '<source srcset="' + modulePath + '/images/negocio-popup-hero.webp" type="image/webp">' +
          '<img src="' + modulePath + '/images/negocio-popup-hero.png" ' +
            'alt="' + Drupal.t('Empresaria andaluza mostrando su negocio digitalizado con tablet') + '" ' +
            'width="520" height="293" loading="eager">' +
        '</picture>' +
        '<div class="aei-popup__negocio-header">' +
          '<h2 class="aei-popup__title">' + Drupal.t('Digitalice su negocio gratis') + '</h2>' +
          '<p class="aei-popup__desc" id="aei-popup-desc-negocio">' +
            Drupal.t('@count servicios de digitalización sin coste para su empresa, con supervisión profesional y tecnología de IA.', { '@count': serviciosCount }) +
          '</p>' +
        '</div>' +
        '<ul class="aei-popup__servicios">' + serviciosHtml + '</ul>' +
        '<div class="aei-popup__negocio-value">' +
          '<span class="aei-popup__negocio-value-crossed">' +
            Drupal.t('+@valor €/año en el mercado', { '@valor': Drupal.checkPlain(String(valorMercado)) }) +
          '</span>' +
          '<span class="aei-popup__negocio-value-free">' + Drupal.t('0 € durante el programa') + '</span>' +
        '</div>' +
        '<div class="aei-popup__actions">' +
          '<a href="' + pruebaHref + '" class="aei-popup__cta aei-popup__cta--primary" ' +
            'data-track-cta="aei_popup_prueba_gratuita" data-track-position="popup_negocio">' +
            Drupal.t('Ver servicios gratuitos') +
          '</a>' +
        '</div>' +
        '<p class="aei-popup__legal">' +
          Drupal.t('Servicios prestados por participantes del programa bajo supervisión profesional. PIIL — Junta de Andalucía + FSE+.') +
        '</p>';
    },

    /**
     * Binds selector button clicks to show the appropriate path.
     */
    _bindSelector: function (overlay, card, settings) {
      var self = this;
      card.addEventListener('click', function (e) {
        var option = e.target.closest('[data-popup-path]');
        if (!option) {
          return;
        }
        var path = option.getAttribute('data-popup-path');

        trackPopupEvent('popup_path_selected', {
          popup: 'aei_dual',
          path: path
        });

        // Transition: fade out card content, rebuild, fade in.
        card.style.opacity = '0';
        card.style.transform = 'translateY(4px)';

        setTimeout(function () {
          if (path === 'negocio') {
            card.innerHTML = self._buildPathNegocio(settings);
          }
          else {
            card.innerHTML = self._buildPathParticipante(settings);
          }

          // Bind back button.
          self._bindBack(overlay, card, settings);
          // Bind close on new content.
          self._bindClose(overlay);

          // Fade in.
          requestAnimationFrame(function () {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
          });

          // Re-attach Drupal behaviors for CTA tracking.
          Drupal.attachBehaviors(overlay);

          // Focus management for accessibility.
          var closeBtn = card.querySelector('.aei-popup__close');
          if (closeBtn) {
            closeBtn.focus();
          }
        }, 200);
      });
    },

    /**
     * Binds back button to return to selector.
     */
    _bindBack: function (overlay, card, settings) {
      var self = this;
      var backBtn = card.querySelector('.aei-popup__back');
      if (!backBtn) {
        return;
      }
      backBtn.addEventListener('click', function () {
        var tasa = settings.tasaInsercion || 46;

        trackPopupEvent('popup_back', {
          popup: 'aei_dual',
          from_path: card.querySelector('.aei-popup__negocio-header') ? 'negocio' : 'participante'
        });

        card.style.opacity = '0';
        card.style.transform = 'translateY(4px)';

        setTimeout(function () {
          card.innerHTML = self._buildSelector(tasa);
          self._bindSelector(overlay, card, settings);
          self._bindClose(overlay);

          requestAnimationFrame(function () {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
          });

          Drupal.attachBehaviors(overlay);

          var closeBtn = card.querySelector('.aei-popup__close');
          if (closeBtn) {
            closeBtn.focus();
          }
        }, 200);
      });
    },

    /**
     * Binds close handlers (button, backdrop, Escape key).
     */
    _bindClose: function (overlay) {
      var closeBtn = overlay.querySelector('.aei-popup__close');
      if (!closeBtn) {
        return;
      }

      var showTime = Date.now();

      var closeFn = function () {
        overlay.classList.remove('aei-popup__overlay--visible');

        trackPopupEvent('popup_dismissed', {
          popup: 'aei_dual',
          dismissed_after_ms: Date.now() - showTime
        });

        try {
          localStorage.setItem(STORAGE_KEY, JSON.stringify({ ts: Date.now() }));
        }
        catch (e) { /* no-op */ }

        setTimeout(function () {
          overlay.remove();
        }, 300);
      };

      closeBtn.addEventListener('click', closeFn);

      // Backdrop click — only on the overlay itself, not on the card.
      overlay.addEventListener('click', function (e) {
        if (e.target === overlay) {
          closeFn();
        }
      });

      // Escape key.
      var escHandler = function (e) {
        if (e.key === 'Escape') {
          closeFn();
          document.removeEventListener('keydown', escHandler);
        }
      };
      document.addEventListener('keydown', escHandler);
    }
  };
})(Drupal, drupalSettings, once);

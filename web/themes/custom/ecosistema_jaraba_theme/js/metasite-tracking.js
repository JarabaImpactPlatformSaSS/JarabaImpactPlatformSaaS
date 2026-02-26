/**
 * @file metasite-tracking.js
 * DataLayer + Conversion Tracking para meta-sitios PED S.L. / Jaraba Impact
 *
 * Registra eventos en window.dataLayer para:
 * - Clicks en CTAs principales (Demo, Contacto, Registro)
 * - Envíos de formularia (contacto, suscripción, lead magnet)
 * - Scroll depth (25%, 50%, 75%, 100%)
 * - Cross-pollination clicks (vertical cards)
 * - Engagement time (10s, 30s, 60s, 120s)
 *
 * DIRECTRICES:
 * - No hardcodea IDs de GTM; sólo empuja al dataLayer
 * - Respeta CSP: no inyecta scripts externos
 * - Compatible con Drupal behaviors
 */
(function (Drupal, once) {
  'use strict';

  // Asegurar dataLayer global
  window.dataLayer = window.dataLayer || [];

  /**
   * Push event al dataLayer con metadata de meta-sitio.
   */
  function pushEvent(eventName, params) {
    var tenantClass = document.body.className.match(/meta-site-tenant-(\d+)/);
    var tenant = tenantClass ? tenantClass[1] : 'default';

    // Detectar meta-sitio por tenant
    var metaSite = 'unknown';
    if (tenant === '7') metaSite = 'plataformadeecosistemas';
    else if (tenant === '6') metaSite = 'jarabaimpact';
    else if (document.body.classList.contains('meta-site')) metaSite = 'pepejaraba';

    window.dataLayer.push(Object.assign({
      event: eventName,
      meta_site: metaSite,
      tenant_id: tenant,
      page_url: window.location.href,
      page_title: document.title,
      timestamp: new Date().toISOString()
    }, params || {}));
  }

  /**
   * Behavior: CTA Click Tracking.
   */
  Drupal.behaviors.metasiteCTATracking = {
    attach: function (context) {
      // CTAs principales (botones con clase btn, links en hero/CTA sections)
      var ctaSelectors = [
        '.ji-hero a',
        '.ped-hero a',
        '.ji-cta-block a',
        '.ped-cta-saas a',
        '.hero-landing a[class*="btn"]',
        'a[href*="contacto"]',
        'a[href*="demo"]',
        'a[href*="register"]',
        'a[href*="registro"]',
        'a[href*="pricing"]'
      ];

      once('metasite-cta', ctaSelectors.join(','), context).forEach(function (el) {
        el.addEventListener('click', function (e) {
          var text = (el.textContent || '').trim().substring(0, 60);
          var href = el.getAttribute('href') || '';
          var section = el.closest('section');
          var sectionClass = section ? section.className.split(' ')[0] : 'unknown';

          pushEvent('cta_click', {
            cta_text: text,
            cta_url: href,
            cta_section: sectionClass,
            cta_type: href.indexOf('demo') !== -1 ? 'demo_request'
              : href.indexOf('contacto') !== -1 ? 'contact'
              : href.indexOf('register') !== -1 || href.indexOf('registro') !== -1 ? 'register'
              : href.indexOf('pricing') !== -1 ? 'pricing'
              : 'general'
          });
        });
      });
    }
  };

  /**
   * Behavior: Cross-Pollination Click Tracking.
   */
  Drupal.behaviors.metasiteCrossPollTracking = {
    attach: function (context) {
      once('metasite-crosspoll', '.cross-pollination__card', context).forEach(function (card) {
        card.addEventListener('click', function () {
          var vertical = card.getAttribute('data-vertical') || 'unknown';
          var trackAction = card.getAttribute('data-track-action') || 'click';

          pushEvent('cross_pollination_click', {
            vertical: vertical,
            action: trackAction
          });
        });
      });
    }
  };

  /**
   * Behavior: Form Submission Tracking.
   */
  Drupal.behaviors.metasiteFormTracking = {
    attach: function (context) {
      var formSelectors = [
        'form[action*="subscribe"]',
        'form[action*="contact"]',
        'form[action*="lead"]',
        '#ped-contact-form',
        '#ji-contact-form',
        '.lead-magnet form'
      ];

      once('metasite-form', formSelectors.join(','), context).forEach(function (form) {
        form.addEventListener('submit', function () {
          var formId = form.getAttribute('id') || form.getAttribute('action') || 'unknown';

          pushEvent('form_submit', {
            form_id: formId,
            form_type: formId.indexOf('subscribe') !== -1 ? 'subscribe'
              : formId.indexOf('contact') !== -1 ? 'contact'
              : formId.indexOf('lead') !== -1 ? 'lead_magnet'
              : 'other'
          });
        });
      });
    }
  };

  /**
   * Behavior: Scroll Depth Tracking.
   */
  Drupal.behaviors.metasiteScrollTracking = {
    attach: function (context) {
      if (context !== document) return;

      var thresholds = [25, 50, 75, 100];
      var triggered = {};

      function checkScroll() {
        var scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        var docHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        if (docHeight <= 0) return;

        var pct = Math.round((scrollTop / docHeight) * 100);

        thresholds.forEach(function (t) {
          if (pct >= t && !triggered[t]) {
            triggered[t] = true;
            pushEvent('scroll_depth', { depth_pct: t });
          }
        });
      }

      var scrollTimer = null;
      window.addEventListener('scroll', function () {
        if (scrollTimer) clearTimeout(scrollTimer);
        scrollTimer = setTimeout(checkScroll, 200);
      }, { passive: true });
    }
  };

  /**
   * Behavior: Engagement Time Tracking.
   */
  Drupal.behaviors.metasiteEngagementTracking = {
    attach: function (context) {
      if (context !== document) return;

      var milestones = [10, 30, 60, 120];
      var startTime = Date.now();

      milestones.forEach(function (seconds) {
        setTimeout(function () {
          if (!document.hidden) {
            pushEvent('engagement_time', { seconds: seconds });
          }
        }, seconds * 1000);
      });
    }
  };

  /**
   * Behavior: Section View Tracking (IntersectionObserver).
   */
  Drupal.behaviors.metasiteSectionTracking = {
    attach: function (context) {
      if (context !== document || !window.IntersectionObserver) return;

      var sectionSelectors = [
        '.ped-hero', '.ped-cifras', '.ped-motores', '.ped-audiencia', '.ped-partners',
        '.ji-hero', '.ji-section', '.ji-cta-block',
        '.hero-landing', '.vertical-selector', '.features-section',
        '.stats-section', '.lead-magnet', '.cross-pollination', '.testimonials'
      ];

      var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            var section = entry.target;
            var sectionName = section.className.split(' ')[0] || 'unknown';

            pushEvent('section_view', { section: sectionName });
            observer.unobserve(section);
          }
        });
      }, { threshold: 0.3 });

      sectionSelectors.forEach(function (sel) {
        var elements = context.querySelectorAll(sel);
        elements.forEach(function (el) { observer.observe(el); });
      });
    }
  };

})(Drupal, once);

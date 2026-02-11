/**
 * @file
 * Banner de consentimiento de cookies GDPR/RGPD.
 *
 * Implementa el patrón Zero-Block: scripts de marketing/analytics
 * se bloquean hasta que el usuario otorga consentimiento explícito.
 *
 * @module jaraba_analytics/consent-banner
 */

(function (Drupal, drupalSettings) {
  'use strict';

  const COOKIE_NAME = 'jaraba_visitor_id';
  const CONSENT_KEY = 'jaraba_consent';
  const API_BASE = '/api/consent';

  /**
   * Comportamiento principal del banner de consentimiento.
   */
  Drupal.behaviors.jarabaConsentBanner = {
    attach: function (context, settings) {
      // Verificar si once está disponible (Drupal 10).
      if (typeof once !== 'undefined') {
        once('jarabaConsentBanner', 'body', context).forEach(function () {
          initConsentBanner();
        });
      } else if (context === document && !document.body.hasAttribute('data-consent-initialized')) {
        // Fallback para cuando once no está disponible.
        document.body.setAttribute('data-consent-initialized', 'true');
        initConsentBanner();
      }
    }
  };

  /**
   * Inicializar el banner de consentimiento.
   */
  async function initConsentBanner() {
    // Verificar si ya hay consentimiento guardado.
    const localConsent = localStorage.getItem(CONSENT_KEY);
    if (localConsent) {
      const consent = JSON.parse(localConsent);
      if (consent.granted) {
        applyConsent(consent.categories);
        return;
      }
    }

    // Consultar API por estado actual.
    try {
      const response = await fetch(API_BASE + '/status', {
        method: 'GET',
        credentials: 'same-origin',
      });
      const data = await response.json();

      if (!data.banner_required) {
        applyConsent(data.categories);
        return;
      }
    } catch (error) {
      console.warn('[Consent] Error fetching status:', error);
    }

    // Mostrar banner.
    showBanner();
  }

  /**
   * Mostrar el banner de cookies.
   */
  function showBanner() {
    const banner = document.createElement('div');
    banner.className = 'consent-banner';
    banner.setAttribute('role', 'dialog');
    banner.setAttribute('aria-modal', 'true');
    banner.setAttribute('aria-labelledby', 'consent-title');

    banner.innerHTML = `
      <div class="consent-banner__content">
        <div class="consent-banner__icon">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <path d="M12 6v6l4 2"/>
          </svg>
        </div>
        <div class="consent-banner__text">
          <h2 id="consent-title" class="consent-banner__title">
            ${Drupal.t('Usamos cookies')}
          </h2>
          <p class="consent-banner__description">
            ${Drupal.t('Utilizamos cookies propias y de terceros para mejorar tu experiencia. Puedes aceptar todas o configurar tus preferencias.')}
          </p>
        </div>
        <div class="consent-banner__actions">
          <button type="button" class="consent-banner__btn consent-banner__btn--primary" data-consent="accept-all">
            ${Drupal.t('Aceptar todas')}
          </button>
          <button type="button" class="consent-banner__btn consent-banner__btn--secondary" data-consent="necessary-only">
            ${Drupal.t('Solo necesarias')}
          </button>
          <button type="button" class="consent-banner__btn consent-banner__btn--link" data-consent="configure">
            ${Drupal.t('Configurar')}
          </button>
        </div>
      </div>
    `;

    document.body.appendChild(banner);

    // Animación de entrada.
    requestAnimationFrame(() => {
      banner.classList.add('consent-banner--visible');
    });

    // Event listeners.
    banner.querySelector('[data-consent="accept-all"]').addEventListener('click', () => {
      saveConsent({ analytics: true, marketing: true, functional: true });
      hideBanner(banner);
    });

    banner.querySelector('[data-consent="necessary-only"]').addEventListener('click', () => {
      saveConsent({ analytics: false, marketing: false, functional: false });
      hideBanner(banner);
    });

    banner.querySelector('[data-consent="configure"]').addEventListener('click', () => {
      showConfigureModal(banner);
    });
  }

  /**
   * Mostrar modal de configuración granular.
   */
  function showConfigureModal(banner) {
    const modal = document.createElement('div');
    modal.className = 'consent-modal';

    modal.innerHTML = `
      <div class="consent-modal__overlay"></div>
      <div class="consent-modal__content" role="dialog" aria-modal="true">
        <h3 class="consent-modal__title">${Drupal.t('Configurar Cookies')}</h3>
        
        <div class="consent-modal__categories">
          <div class="consent-category consent-category--disabled">
            <div class="consent-category__header">
              <span class="consent-category__name">${Drupal.t('Necesarias')}</span>
              <span class="consent-category__badge">${Drupal.t('Siempre activas')}</span>
            </div>
            <p class="consent-category__description">
              ${Drupal.t('Cookies esenciales para el funcionamiento del sitio.')}
            </p>
          </div>
          
          <div class="consent-category">
            <div class="consent-category__header">
              <span class="consent-category__name">${Drupal.t('Funcionales')}</span>
              <label class="consent-toggle">
                <input type="checkbox" id="consent-functional" checked>
                <span class="consent-toggle__slider"></span>
              </label>
            </div>
            <p class="consent-category__description">
              ${Drupal.t('Mejoran la funcionalidad y personalización.')}
            </p>
          </div>
          
          <div class="consent-category">
            <div class="consent-category__header">
              <span class="consent-category__name">${Drupal.t('Analytics')}</span>
              <label class="consent-toggle">
                <input type="checkbox" id="consent-analytics">
                <span class="consent-toggle__slider"></span>
              </label>
            </div>
            <p class="consent-category__description">
              ${Drupal.t('Nos ayudan a entender cómo usas el sitio.')}
            </p>
          </div>
          
          <div class="consent-category">
            <div class="consent-category__header">
              <span class="consent-category__name">${Drupal.t('Marketing')}</span>
              <label class="consent-toggle">
                <input type="checkbox" id="consent-marketing">
                <span class="consent-toggle__slider"></span>
              </label>
            </div>
            <p class="consent-category__description">
              ${Drupal.t('Permiten mostrarte publicidad relevante.')}
            </p>
          </div>
        </div>
        
        <div class="consent-modal__actions">
          <button type="button" class="consent-banner__btn consent-banner__btn--secondary" data-action="cancel">
            ${Drupal.t('Cancelar')}
          </button>
          <button type="button" class="consent-banner__btn consent-banner__btn--primary" data-action="save">
            ${Drupal.t('Guardar preferencias')}
          </button>
        </div>
      </div>
    `;

    document.body.appendChild(modal);

    // Animación de entrada.
    requestAnimationFrame(() => {
      modal.classList.add('consent-modal--visible');
    });

    // Event listeners.
    modal.querySelector('[data-action="cancel"]').addEventListener('click', () => {
      hideModal(modal);
    });

    modal.querySelector('[data-action="save"]').addEventListener('click', () => {
      const categories = {
        functional: modal.querySelector('#consent-functional').checked,
        analytics: modal.querySelector('#consent-analytics').checked,
        marketing: modal.querySelector('#consent-marketing').checked,
      };
      saveConsent(categories);
      hideModal(modal);
      hideBanner(banner);
    });

    modal.querySelector('.consent-modal__overlay').addEventListener('click', () => {
      hideModal(modal);
    });
  }

  /**
   * Guardar consentimiento en API y localStorage.
   */
  async function saveConsent(categories) {
    // Guardar en localStorage inmediatamente.
    const consentData = {
      granted: true,
      categories: categories,
      timestamp: Date.now(),
    };
    localStorage.setItem(CONSENT_KEY, JSON.stringify(consentData));

    // Aplicar consentimiento.
    applyConsent(categories);

    // Enviar a API.
    try {
      await fetch(API_BASE + '/grant', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'same-origin',
        body: JSON.stringify(categories),
      });
    } catch (error) {
      console.warn('[Consent] Error saving to API:', error);
    }
  }

  /**
   * Aplicar consentimiento: habilitar scripts bloqueados.
   */
  function applyConsent(categories) {
    // Zero-Block Pattern: habilitar scripts según categoría.
    if (categories.analytics) {
      enableScripts('analytics');
    }
    if (categories.marketing) {
      enableScripts('marketing');
    }

    // Dispatch evento para que otros módulos reaccionen.
    document.dispatchEvent(new CustomEvent('jaraba:consent:granted', {
      detail: { categories: categories },
    }));
  }

  /**
   * Habilitar scripts de una categoría.
   */
  function enableScripts(category) {
    const scripts = document.querySelectorAll(`script[data-consent-category="${category}"]`);
    scripts.forEach(script => {
      const newScript = document.createElement('script');
      if (script.src) {
        newScript.src = script.src;
      } else {
        newScript.textContent = script.textContent;
      }
      script.parentNode.replaceChild(newScript, script);
    });
  }

  /**
   * Ocultar el banner con animación.
   */
  function hideBanner(banner) {
    banner.classList.remove('consent-banner--visible');
    banner.addEventListener('transitionend', () => {
      banner.remove();
    });
  }

  /**
   * Ocultar el modal con animación.
   */
  function hideModal(modal) {
    modal.classList.remove('consent-modal--visible');
    modal.addEventListener('transitionend', () => {
      modal.remove();
    });
  }

})(Drupal, drupalSettings);

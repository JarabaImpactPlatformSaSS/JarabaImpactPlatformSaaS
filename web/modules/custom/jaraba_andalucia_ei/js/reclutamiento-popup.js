/**
 * @file
 * Popup de reclutamiento Andalucía +ei para meta-sitios corporativos.
 *
 * Se muestra UNA vez por sesión en la home de los meta-sitios:
 * plataformadeecosistemas.es, pepejaraba.com, jarabaimpact.com.
 * Dismiss guarda flag en sessionStorage para no repetir.
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  var STORAGE_KEY = 'aei_rec_popup_dismissed';
  var DELAY_MS = 3000;

  Drupal.behaviors.aeiRecPopup = {
    attach: function (context) {
      if (context !== document) {
        return;
      }

      // Solo si no se ha dismisseado en esta sesión.
      try {
        if (sessionStorage.getItem(STORAGE_KEY)) {
          return;
        }
      }
      catch (e) {
        return;
      }

      var settings = drupalSettings.aeiRecPopup || {};
      if (!settings.solicitarUrl) {
        return;
      }

      setTimeout(function () {
        Drupal.behaviors.aeiRecPopup._show(settings);
      }, DELAY_MS);
    },

    _show: function (settings) {
      var overlay = document.createElement('div');
      overlay.className = 'aei-popup__overlay';
      overlay.setAttribute('role', 'dialog');
      overlay.setAttribute('aria-modal', 'true');
      overlay.setAttribute('aria-label', Drupal.t('Programa gratuito de inserción laboral'));

      var modulePath = settings.modulePath || '';
      overlay.innerHTML =
        '<div class="aei-popup">' +
          '<button class="aei-popup__close" aria-label="' + Drupal.t('Cerrar') + '">&times;</button>' +
          '<picture class="aei-popup__hero">' +
            '<source srcset="' + Drupal.checkPlain(modulePath) + '/images/reclutamiento-popup-hero.webp" type="image/webp">' +
            '<img src="' + Drupal.checkPlain(modulePath) + '/images/reclutamiento-popup-hero.png" alt="' + Drupal.t('Grupo diverso de personas colaborando en un programa de inserción laboral en Andalucía') + '" width="520" height="293" loading="eager">' +
          '</picture>' +
          '<div class="aei-popup__badge">' + Drupal.t('Programa oficial') + '</div>' +
          '<h2 class="aei-popup__title">' + Drupal.t('¿Buscas empleo en Andalucía?') + '</h2>' +
          '<p class="aei-popup__desc">' +
            Drupal.t('Programa gratuito de inserción laboral con orientación personalizada, formación certificada, mentoría con IA y un incentivo de 528 €. Financiado por la Junta de Andalucía y la Unión Europea.') +
          '</p>' +
          '<div class="aei-popup__stats">' +
            '<div class="aei-popup__stat"><span class="aei-popup__stat-value">45</span><span class="aei-popup__stat-label">' + Drupal.t('plazas') + '</span></div>' +
            '<div class="aei-popup__stat"><span class="aei-popup__stat-value">528 €</span><span class="aei-popup__stat-label">' + Drupal.t('incentivo') + '</span></div>' +
            '<div class="aei-popup__stat"><span class="aei-popup__stat-value">100%</span><span class="aei-popup__stat-label">' + Drupal.t('gratuito') + '</span></div>' +
          '</div>' +
          '<div class="aei-popup__actions">' +
            '<a href="' + Drupal.checkPlain(settings.landingUrl) + '" class="aei-popup__cta aei-popup__cta--primary">' + Drupal.t('Ver programa completo') + '</a>' +
            '<a href="' + Drupal.checkPlain(settings.solicitarUrl) + '" class="aei-popup__cta aei-popup__cta--secondary">' + Drupal.t('Solicitar plaza') + '</a>' +
          '</div>' +
          '<p class="aei-popup__legal">' + Drupal.t('PIIL — Colectivos Vulnerables 2025. Junta de Andalucía + FSE+.') + '</p>' +
        '</div>';

      document.body.appendChild(overlay);

      // Animate in.
      requestAnimationFrame(function () {
        overlay.classList.add('aei-popup__overlay--visible');
      });

      // Close handlers.
      var closeBtn = overlay.querySelector('.aei-popup__close');
      var closeFn = function () {
        overlay.classList.remove('aei-popup__overlay--visible');
        try {
          sessionStorage.setItem(STORAGE_KEY, '1');
        }
        catch (e) { /* no-op */ }
        setTimeout(function () {
          overlay.remove();
        }, 300);
      };

      closeBtn.addEventListener('click', closeFn);
      overlay.addEventListener('click', function (e) {
        if (e.target === overlay) {
          closeFn();
        }
      });

      // Escape key.
      document.addEventListener('keydown', function onEsc(e) {
        if (e.key === 'Escape') {
          closeFn();
          document.removeEventListener('keydown', onEsc);
        }
      });

      // Focus trap: focus the close button.
      closeBtn.focus();
    }
  };
})(Drupal, drupalSettings, once);

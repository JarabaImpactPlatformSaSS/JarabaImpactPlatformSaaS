/**
 * @file js/whatsapp-fab-contextual.js
 * Widget WhatsApp FAB — aparición progresiva y mensajes contextuales.
 *
 * Lee configuración de drupalSettings.jarabaWhatsApp (ZERO-REGION-003)
 * e implementa:
 * - Aparición progresiva (delay + scroll threshold)
 * - Mensaje contextual por vertical/página (WA-CONTEXTUAL-001)
 * - Toggle panel expandido en desktop
 * - Tracking analytics (CTA-TRACKING-001)
 *
 * Directrices:
 * - Vanilla JS + Drupal.behaviors (NO React/Vue/Angular)
 * - URLs via drupalSettings (ROUTE-LANGPREFIX-001)
 * - XSS: textContent, NUNCA innerHTML (INNERHTML-XSS-001)
 * - Traducciones: textos desde drupalSettings (ya traducidos en PHP)
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.whatsappFabContextual = {
    attach: function (context) {
      once('whatsapp-fab-ctx', '.whatsapp-fab', context).forEach(function (fab) {
        var config = drupalSettings.jarabaWhatsApp || {};
        if (!config.enabled) {
          return;
        }

        var delay = (config.delay || 5) * 1000;
        var scrollThreshold = config.scrollThreshold || 30;
        var currentContext = config.currentContext || 'default';
        var messages = config.messages || {};
        var number = config.number || '';

        // Resolver mensaje contextual — fallback cascada.
        var message = messages[currentContext] || messages['default'] || '';

        // Inyectar mensaje en tooltip (textContent = XSS safe).
        var tooltip = fab.querySelector('.whatsapp-fab__tooltip');
        if (tooltip && message) {
          tooltip.textContent = message;
        }

        // Inyectar en panel expandido.
        var expandedMsg = fab.querySelector('.whatsapp-fab__expanded-message');
        if (expandedMsg && message) {
          expandedMsg.textContent = message;
        }

        // Construir URL con mensaje contextual.
        var waUrl = 'https://wa.me/' + number;
        if (message) {
          waUrl += '?text=' + encodeURIComponent(message);
        }

        // Actualizar href del CTA del panel expandido.
        var expandedCta = fab.querySelector('.whatsapp-fab__expanded-cta');
        if (expandedCta) {
          expandedCta.href = waUrl;
        }

        // Actualizar href del botón principal (móvil: link directo).
        var mainButton = fab.querySelector('.whatsapp-fab__button');
        if (mainButton) {
          mainButton.href = waUrl;
        }

        // ── Aparición progresiva ──
        var shown = false;
        var delayPassed = false;
        var scrollPassed = scrollThreshold === 0;

        function showFab() {
          if (delayPassed && scrollPassed && !shown) {
            shown = true;
            fab.classList.add('whatsapp-fab--visible');
          }
        }

        setTimeout(function () {
          delayPassed = true;
          showFab();
        }, delay);

        if (!scrollPassed) {
          var onScroll = function () {
            var docHeight = document.documentElement.scrollHeight - window.innerHeight;
            if (docHeight <= 0) {
              scrollPassed = true;
              showFab();
              window.removeEventListener('scroll', onScroll);
              return;
            }
            var pct = (window.scrollY / docHeight) * 100;
            if (pct >= scrollThreshold) {
              scrollPassed = true;
              showFab();
              window.removeEventListener('scroll', onScroll);
            }
          };
          window.addEventListener('scroll', onScroll, { passive: true });
        }

        // ── Toggle panel expandido (solo desktop) ──
        var expandedPanel = fab.querySelector('.whatsapp-fab__expanded');
        if (mainButton && expandedPanel) {
          mainButton.addEventListener('click', function (e) {
            // En móvil, dejar que el link wa.me/ funcione normalmente.
            if (window.innerWidth < 769) {
              return;
            }
            e.preventDefault();
            var isOpen = expandedPanel.classList.toggle('whatsapp-fab__expanded--open');
            mainButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
          });

          // Botón cerrar.
          var closeBtn = fab.querySelector('.whatsapp-fab__close');
          if (closeBtn) {
            closeBtn.addEventListener('click', function () {
              expandedPanel.classList.remove('whatsapp-fab__expanded--open');
              mainButton.setAttribute('aria-expanded', 'false');
            });
          }

          // Cerrar al hacer click fuera.
          document.addEventListener('click', function (e) {
            if (!fab.contains(e.target) && expandedPanel.classList.contains('whatsapp-fab__expanded--open')) {
              expandedPanel.classList.remove('whatsapp-fab__expanded--open');
              mainButton.setAttribute('aria-expanded', 'false');
            }
          });

          // Cerrar con Escape.
          document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && expandedPanel.classList.contains('whatsapp-fab__expanded--open')) {
              expandedPanel.classList.remove('whatsapp-fab__expanded--open');
              mainButton.setAttribute('aria-expanded', 'false');
              mainButton.focus();
            }
          });
        }
      });
    }
  };

})(Drupal, drupalSettings, once);

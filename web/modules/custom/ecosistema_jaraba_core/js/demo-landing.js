/**
 * @file
 * Demo Landing — Showcase hero tabs y social proof.
 *
 * Gestiona la navegación por tabs del showcase de verticales y
 * la interacción de los profile cards.
 *
 * S5-15: Keydown handlers (Enter/Space) en tabs.
 * WCAG 2.1: Tabs con role="tablist", aria-selected, focus management.
 */
(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.demoLanding = {
    attach: function (context) {
      once('demo-landing-showcase', '.demo-landing__showcase', context).forEach(function (showcase) {
        var tabs = showcase.querySelectorAll('[data-showcase-tab]');
        var panels = showcase.querySelectorAll('[data-showcase-panel]');

        if (!tabs.length || !panels.length) {
          return;
        }

        function activateTab(tabId) {
          tabs.forEach(function (tab) {
            var isActive = tab.getAttribute('data-showcase-tab') === tabId;
            tab.classList.toggle('demo-landing__showcase-tab--active', isActive);
            tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
          });

          panels.forEach(function (panel) {
            var isActive = panel.getAttribute('data-showcase-panel') === tabId;
            panel.classList.toggle('demo-landing__showcase-panel--active', isActive);
            panel.style.display = isActive ? '' : 'none';
          });
        }

        tabs.forEach(function (tab) {
          tab.addEventListener('click', function () {
            activateTab(this.getAttribute('data-showcase-tab'));
          });
        });

        // WCAG: Arrow key navigation entre tabs.
        var tabList = showcase.querySelector('[role="tablist"]');
        if (tabList) {
          tabList.addEventListener('keydown', function (e) {
            var tabsArr = Array.prototype.slice.call(tabs);
            var idx = tabsArr.indexOf(document.activeElement);
            if (idx === -1) {
              return;
            }

            var next = -1;
            if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
              next = (idx + 1) % tabsArr.length;
            } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
              next = (idx - 1 + tabsArr.length) % tabsArr.length;
            } else if (e.key === 'Home') {
              next = 0;
            } else if (e.key === 'End') {
              next = tabsArr.length - 1;
            }

            if (next >= 0) {
              e.preventDefault();
              tabsArr[next].focus();
              activateTab(tabsArr[next].getAttribute('data-showcase-tab'));
            }
          });
        }
      });
    },

    detach: function (context, settings, trigger) {
      if (trigger === 'unload') {
        once.remove('demo-landing-showcase', '.demo-landing__showcase', context);
      }
    },
  };

  // S15-04: Exit-intent — muestra mini-CTA al detectar abandono.
  Drupal.behaviors.demoExitIntent = {
    attach: function (context) {
      once('demo-exit-intent', 'body', context).forEach(function () {
        // Solo activar en la landing de demo.
        if (!document.querySelector('.demo-landing')) { return; }

        var shown = false;
        var EXIT_INTENT_KEY = 'jaraba_demo_exit_shown';

        // No mostrar si ya se mostró en esta sesión.
        if (sessionStorage.getItem(EXIT_INTENT_KEY)) { return; }

        // Exit-intent: cursor sale por arriba del viewport.
        document.addEventListener('mouseout', function (e) {
          if (shown) { return; }
          if (e.clientY > 20) { return; }
          if (e.relatedTarget || e.toElement) { return; }
          showExitBanner();
        });

        // Scroll-depth: 80% de la página.
        var scrollTriggered = false;
        window.addEventListener('scroll', function () {
          if (shown || scrollTriggered) { return; }
          var scrollPct = (window.scrollY + window.innerHeight) / document.documentElement.scrollHeight;
          if (scrollPct >= 0.8) {
            scrollTriggered = true;
            showExitBanner();
          }
        }, { passive: true });

        function showExitBanner() {
          if (shown) { return; }
          shown = true;
          sessionStorage.setItem(EXIT_INTENT_KEY, '1');

          var banner = document.createElement('div');
          banner.className = 'demo-exit-banner';
          banner.setAttribute('role', 'alert');
          banner.innerHTML = '<div class="demo-exit-banner__content">'
            + '<span class="demo-exit-banner__text">' + Drupal.t('¿Ya te vas? Prueba la demo en 60 segundos — sin registro') + '</span>'
            + '<a href="#demo-profiles" class="demo-exit-banner__cta">' + Drupal.t('Ver demos') + ' &rarr;</a>'
            + '<button class="demo-exit-banner__close" type="button" aria-label="' + Drupal.t('Cerrar') + '">&times;</button>'
            + '</div>';
          document.body.appendChild(banner);

          // Animación de entrada.
          requestAnimationFrame(function () {
            banner.classList.add('demo-exit-banner--visible');
          });

          banner.querySelector('.demo-exit-banner__close').addEventListener('click', function () {
            banner.classList.remove('demo-exit-banner--visible');
            setTimeout(function () { banner.remove(); }, 300);
          });
        }
      });
    },
  };

})(Drupal, once);

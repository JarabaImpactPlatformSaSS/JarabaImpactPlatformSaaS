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

})(Drupal, once);

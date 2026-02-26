/**
 * @file
 * product-demo.js
 *
 * Tab switching for the product demo section on the homepage.
 * Sprint 4 — Remaining Audit Items (#9).
 *
 * Uses Drupal.behaviors + once() for proper lifecycle.
 */
(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.productDemo = {
    attach: function (context) {
      once('product-demo', '.product-demo__tabs', context).forEach(function (tabList) {
        const tabs = tabList.querySelectorAll('[data-demo-tab]');
        const showcase = tabList.closest('.product-demo__showcase');
        if (!showcase) return;

        tabs.forEach(function (tab) {
          tab.addEventListener('click', function () {
            const targetPanel = tab.getAttribute('data-demo-tab');

            // Update tab states.
            tabs.forEach(function (t) {
              t.classList.remove('product-demo__tab--active');
              t.setAttribute('aria-selected', 'false');
            });
            tab.classList.add('product-demo__tab--active');
            tab.setAttribute('aria-selected', 'true');

            // Show/hide panels.
            showcase.querySelectorAll('[data-demo-panel]').forEach(function (panel) {
              if (panel.getAttribute('data-demo-panel') === targetPanel) {
                panel.style.display = '';
                panel.setAttribute('aria-hidden', 'false');
              } else {
                panel.style.display = 'none';
                panel.setAttribute('aria-hidden', 'true');
              }
            });
          });
        });
      });
    }
  };

})(Drupal, once);

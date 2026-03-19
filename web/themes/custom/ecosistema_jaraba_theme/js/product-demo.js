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

      // Carrusel de casos de uso — dots + auto-rotación.
      once('product-demo-carousel', '.product-demo__usecase-carousel', context).forEach(function (carousel) {
        var usecases = carousel.querySelectorAll('[data-usecase]');
        var dots = carousel.querySelectorAll('[data-usecase-goto]');
        var currentIndex = 0;
        var autoTimer = null;

        function showUsecase(index) {
          usecases.forEach(function (uc, i) {
            if (i === index) {
              uc.style.display = '';
              uc.classList.add('product-demo__usecase--active');
            } else {
              uc.style.display = 'none';
              uc.classList.remove('product-demo__usecase--active');
            }
          });
          dots.forEach(function (dot, i) {
            dot.classList.toggle('product-demo__usecase-dot--active', i === index);
          });
          currentIndex = index;
        }

        dots.forEach(function (dot) {
          dot.addEventListener('click', function () {
            var idx = parseInt(dot.getAttribute('data-usecase-goto'), 10);
            showUsecase(idx);
            // Reiniciar auto-rotación al interactuar.
            clearInterval(autoTimer);
            autoTimer = setInterval(function () {
              showUsecase((currentIndex + 1) % usecases.length);
            }, 6000);
          });
        });

        // Auto-rotar cada 6 segundos.
        if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
          autoTimer = setInterval(function () {
            showUsecase((currentIndex + 1) % usecases.length);
          }, 6000);
        }
      });
    }
  };

})(Drupal, once);

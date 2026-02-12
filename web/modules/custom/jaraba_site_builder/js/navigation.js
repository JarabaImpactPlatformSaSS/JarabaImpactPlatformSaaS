/**
 * @file
 * Comportamientos JavaScript para la navegacion global (Doc 177).
 *
 * - Header sticky con IntersectionObserver
 * - Header autohide al hacer scroll
 * - Mobile menu off-canvas (abrir/cerrar/overlay/ESC)
 * - Topbar con boton de cerrar y persistencia localStorage
 * - Mega menu accesibilidad por teclado
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Header sticky: anade clase 'is-scrolled' al pasar un umbral de scroll.
   */
  Drupal.behaviors.jarabaStickyHeader = {
    attach: function (context) {
      once('jaraba-sticky-header', '.jaraba-header--sticky', context)
        .forEach(function (header) {
          var offset = parseInt(header.getAttribute('data-sticky-offset') || '0', 10);
          var scrollThreshold = offset > 0 ? offset : 10;
          var lastKnownScrollY = 0;
          var ticking = false;

          function onScroll() {
            lastKnownScrollY = window.scrollY;
            if (!ticking) {
              window.requestAnimationFrame(function () {
                if (lastKnownScrollY > scrollThreshold) {
                  header.classList.add('is-scrolled');
                } else {
                  header.classList.remove('is-scrolled');
                }
                ticking = false;
              });
              ticking = true;
            }
          }

          window.addEventListener('scroll', onScroll, { passive: true });
        });
    }
  };

  /**
   * Header autohide: oculta al bajar, muestra al subir.
   */
  Drupal.behaviors.jarabaAutoHideHeader = {
    attach: function (context) {
      once('jaraba-autohide-header', '.jaraba-header--autohide', context)
        .forEach(function (header) {
          var lastScrollY = 0;
          var threshold = 5;
          var ticking = false;

          function onScroll() {
            var currentScrollY = window.scrollY;
            if (!ticking) {
              window.requestAnimationFrame(function () {
                if (currentScrollY > lastScrollY && currentScrollY > 100) {
                  header.classList.add('is-hidden');
                } else if (currentScrollY < lastScrollY - threshold) {
                  header.classList.remove('is-hidden');
                }
                lastScrollY = currentScrollY;
                ticking = false;
              });
              ticking = true;
            }
          }

          window.addEventListener('scroll', onScroll, { passive: true });
        });
    }
  };

  /**
   * Mobile menu off-canvas: toggle, overlay click, ESC key, accordion submenus.
   */
  Drupal.behaviors.jarabaMobileMenu = {
    attach: function (context) {
      once('jaraba-mobile-menu', '.jaraba-mobile-menu', context)
        .forEach(function (menu) {
          var toggleBtns = context.querySelectorAll('[data-toggle="mobile-menu"]');
          var closeBtn = menu.querySelector('.jaraba-mobile-menu__close');
          var overlay = menu.querySelector('.jaraba-mobile-menu__overlay');
          var subToggles = menu.querySelectorAll('.jaraba-mobile-menu__toggle');

          function openMenu() {
            menu.classList.add('is-open');
            document.body.classList.add('mobile-menu-open');
            if (closeBtn) {
              closeBtn.focus();
            }
            toggleBtns.forEach(function (btn) {
              btn.setAttribute('aria-expanded', 'true');
            });
          }

          function closeMenu() {
            menu.classList.remove('is-open');
            document.body.classList.remove('mobile-menu-open');
            toggleBtns.forEach(function (btn) {
              btn.setAttribute('aria-expanded', 'false');
              btn.focus();
            });
          }

          toggleBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
              if (menu.classList.contains('is-open')) {
                closeMenu();
              } else {
                openMenu();
              }
            });
          });

          if (closeBtn) {
            closeBtn.addEventListener('click', closeMenu);
          }

          if (overlay) {
            overlay.addEventListener('click', closeMenu);
          }

          document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && menu.classList.contains('is-open')) {
              closeMenu();
            }
          });

          // Accordion submenus.
          subToggles.forEach(function (toggle) {
            toggle.addEventListener('click', function () {
              var expanded = toggle.getAttribute('aria-expanded') === 'true';
              var sublist = toggle.closest('.jaraba-mobile-menu__item')
                .querySelector('.jaraba-mobile-menu__sublist');

              toggle.setAttribute('aria-expanded', String(!expanded));
              if (sublist) {
                sublist.setAttribute('aria-hidden', String(expanded));
              }
            });
          });
        });
    }
  };

  /**
   * Topbar: boton de cerrar con persistencia en localStorage.
   */
  Drupal.behaviors.jarabaTopbar = {
    attach: function (context) {
      once('jaraba-topbar', '.jaraba-topbar', context)
        .forEach(function (topbar) {
          var closeBtn = topbar.querySelector('.jaraba-topbar__close');
          var storageKey = 'jaraba_topbar_dismissed';

          // Verificar si ya fue cerrado en esta sesion.
          if (sessionStorage.getItem(storageKey) === 'true') {
            topbar.classList.add('is-hidden');
            return;
          }

          if (closeBtn) {
            closeBtn.addEventListener('click', function () {
              topbar.classList.add('is-hidden');
              sessionStorage.setItem(storageKey, 'true');
            });
          }
        });
    }
  };

  /**
   * Mega menu: accesibilidad por teclado (ESC para cerrar).
   */
  Drupal.behaviors.jarabaMegaMenu = {
    attach: function (context) {
      once('jaraba-mega-menu', '.jaraba-nav__item--has-dropdown', context)
        .forEach(function (item) {
          item.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
              var link = item.querySelector('.jaraba-nav__link');
              if (link) {
                link.focus();
              }
              item.blur();
            }
          });
        });
    }
  };

})(Drupal, once);

/**
 * @file
 * Preset Picker JS — PRESET-PICKER-001.
 *
 * Standalone init (no Drupal.behaviors / no core/once) to avoid CSP
 * blocking once.js on multi-tenant subdomains which kills the entire
 * attachBehaviors chain.
 *
 * Uses MutationObserver to handle BigPipe late delivery.
 */

(function () {
  'use strict';

  function initPresetPicker() {
    var grid = document.querySelector('.jaraba-preset-picker');
    if (!grid || grid.hasAttribute('data-preset-picker-init')) {
      return;
    }
    grid.setAttribute('data-preset-picker-init', '1');

    var stickyBar = document.querySelector('[data-preset-sticky]');
    var stickyName = stickyBar ? stickyBar.querySelector('[data-sticky-name]') : null;
    var stickySwatches = stickyBar ? stickyBar.querySelector('[data-sticky-swatches]') : null;
    var stickyToggle = stickyBar ? stickyBar.querySelector('.preset-sticky-bar__toggle') : null;
    var applyCheckbox = document.querySelector('.preset-apply-checkbox');

    // -----------------------------------------------------------------
    // LIGHTBOX — Created at body level to escape stacking contexts.
    // -----------------------------------------------------------------
    var lightbox = document.createElement('div');
    lightbox.className = 'preset-lightbox';
    lightbox.hidden = true;
    lightbox.setAttribute('role', 'dialog');
    lightbox.setAttribute('aria-modal', 'true');

    var backdrop = document.createElement('div');
    backdrop.className = 'preset-lightbox__backdrop';

    var lbContainer = document.createElement('div');
    lbContainer.className = 'preset-lightbox__container';

    var closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'preset-lightbox__close';
    closeBtn.textContent = '\u00D7';

    var lbImg = document.createElement('img');
    lbImg.className = 'preset-lightbox__img';

    var lbCaption = document.createElement('div');
    lbCaption.className = 'preset-lightbox__caption';

    lbContainer.appendChild(closeBtn);
    lbContainer.appendChild(lbImg);
    lbContainer.appendChild(lbCaption);
    lightbox.appendChild(backdrop);
    lightbox.appendChild(lbContainer);
    document.body.appendChild(lightbox);

    function openLightbox(src, title) {
      lbImg.src = src;
      lbImg.alt = title;
      lbCaption.textContent = title;
      lightbox.hidden = false;
      document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
      lightbox.hidden = true;
      document.body.style.overflow = '';
    }

    closeBtn.addEventListener('click', closeLightbox);
    backdrop.addEventListener('click', closeLightbox);
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        closeLightbox();
      }
    });

    // Remove server-rendered lightbox (trapped inside details element).
    var serverLb = document.querySelector('[data-preset-lightbox]');
    if (serverLb) {
      serverLb.parentNode.removeChild(serverLb);
    }

    // -----------------------------------------------------------------
    // ZOOM BUTTONS — Direct listeners, stop everything.
    // -----------------------------------------------------------------
    grid.querySelectorAll('.preset-picker-zoom').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        openLightbox(btn.dataset.src, btn.dataset.title);
      });
      btn.addEventListener('mousedown', function (e) {
        e.preventDefault();
        e.stopPropagation();
      });
    });

    // -----------------------------------------------------------------
    // FILTER PILLS — Transicion animada.
    // -----------------------------------------------------------------
    document.addEventListener('click', function (e) {
      var pill = e.target.closest('.preset-filter-pill');
      if (!pill) {
        return;
      }
      var vertical = pill.dataset.vertical;
      document.querySelectorAll('.preset-filter-pill').forEach(function (p) {
        p.classList.remove('is-active');
      });
      pill.classList.add('is-active');

      document.querySelectorAll('.preset-picker-card').forEach(function (card) {
        var shouldHide = vertical !== 'todos' && card.dataset.vertical !== vertical;
        var isHidden = card.getAttribute('data-hidden') === 'true';

        if (shouldHide && !isHidden) {
          card.setAttribute('data-animating', '');
          card.setAttribute('data-hidden', 'true');
          setTimeout(function () {
            card.removeAttribute('data-animating');
            var wrapper = card.closest('.form-type-radio, .js-form-type-radio');
            if (wrapper) {
              wrapper.style.display = 'none';
            }
          }, 260);
        }
        else if (!shouldHide && isHidden) {
          var wrapper = card.closest('.form-type-radio, .js-form-type-radio');
          if (wrapper) {
            wrapper.style.display = '';
          }
          void card.offsetHeight;
          card.setAttribute('data-animating', '');
          card.setAttribute('data-hidden', 'false');
          setTimeout(function () {
            card.removeAttribute('data-animating');
          }, 260);
        }
      });
    });

    // -----------------------------------------------------------------
    // CARD CLICK — Sync radio + sticky bar.
    // -----------------------------------------------------------------
    grid.addEventListener('click', function (e) {
      if (e.target.closest('.preset-picker-zoom')) {
        return;
      }
      var card = e.target.closest('.preset-picker-card');
      if (!card) {
        return;
      }
      var wrapper = card.closest('.form-type-radio, .js-form-type-radio');
      if (!wrapper) {
        return;
      }
      var input = wrapper.querySelector('input[type="radio"]');
      if (input) {
        input.checked = true;
        input.dispatchEvent(new Event('change', {bubbles: true}));
      }
      grid.querySelectorAll('.preset-picker-card').forEach(function (c) {
        c.classList.remove('is-selected');
      });
      card.classList.add('is-selected');

      updateStickyBar(card);
    });

    // -----------------------------------------------------------------
    // STICKY BAR.
    // -----------------------------------------------------------------
    function updateStickyBar(card) {
      if (!stickyBar) {
        return;
      }
      var isNone = card.classList.contains('preset-picker-card--none');
      if (isNone) {
        stickyBar.hidden = true;
        return;
      }

      var titleEl = card.querySelector('.preset-picker-title');
      var swatchEls = card.querySelectorAll('.preset-picker-swatch');

      if (stickyName && titleEl) {
        stickyName.textContent = titleEl.textContent;
      }
      if (stickySwatches) {
        while (stickySwatches.firstChild) {
          stickySwatches.removeChild(stickySwatches.firstChild);
        }
        swatchEls.forEach(function (sw) {
          stickySwatches.appendChild(sw.cloneNode(true));
        });
      }

      if (stickyToggle && applyCheckbox) {
        var existingCb = stickyToggle.querySelector('input[type="checkbox"]');
        if (!existingCb) {
          stickyToggle.appendChild(applyCheckbox);
        }
      }

      stickyBar.hidden = false;
    }

    // -----------------------------------------------------------------
    // INIT — Mark currently selected card + sticky bar.
    // -----------------------------------------------------------------
    var checked = grid.querySelector('input[type="radio"]:checked');
    if (checked) {
      var wrapper = checked.closest('.form-type-radio, .js-form-type-radio');
      if (wrapper) {
        var pc = wrapper.querySelector('.preset-picker-card');
        if (pc) {
          pc.classList.add('is-selected');
          updateStickyBar(pc);
        }
      }
    }
  }

  // -------------------------------------------------------------------
  // Bootstrap: run on DOMContentLoaded + MutationObserver for BigPipe.
  // -------------------------------------------------------------------
  function tryInit() {
    if (document.querySelector('.jaraba-preset-picker')) {
      initPresetPicker();
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', tryInit);
  }
  else {
    tryInit();
  }

  // BigPipe may inject the form after DOMContentLoaded.
  var observer = new MutationObserver(function () {
    if (document.querySelector('.jaraba-preset-picker:not([data-preset-picker-init])')) {
      initPresetPicker();
    }
  });
  observer.observe(document.documentElement, {childList: true, subtree: true});

})();

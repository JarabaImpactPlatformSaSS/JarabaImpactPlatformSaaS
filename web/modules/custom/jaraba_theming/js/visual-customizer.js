/**
 * @file
 * Visual Customizer JS for Tenant Theme Form.
 *
 * Handles live preview of theme changes via CSS custom properties.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Map of form field names to CSS custom properties.
   *
   * Field names MUST match the PHP form element keys exactly.
   * @see TenantThemeCustomizerForm::buildForm()
   */
  var colorMap = {
    color_primary: '--ej-color-primary',
    color_secondary: '--ej-color-secondary',
    color_accent: '--ej-color-accent',
    color_success: '--ej-color-success',
    color_warning: '--ej-color-warning',
    color_error: '--ej-color-error',
    color_bg_body: '--ej-bg-body',
    color_bg_surface: '--ej-bg-surface',
    color_text: '--ej-color-body'
  };

  var typographyMap = {
    font_headings: '--ej-font-headings',
    font_body: '--ej-font-body'
  };

  var sizeMap = {
    card_border_radius: '--ej-border-radius',
    button_border_radius: '--ej-btn-radius'
  };

  Drupal.behaviors.visualCustomizer = {
    attach: function (context) {
      once('visual-customizer-init', '.jaraba-theme-customizer', context).forEach(function (form) {
        // Live preview on color input change.
        form.querySelectorAll('input[type="color"]').forEach(function (input) {
          input.addEventListener('input', function () {
            var cssVar = colorMap[input.name];
            if (cssVar) {
              document.documentElement.style.setProperty(cssVar, input.value);
            }
          });
        });

        // Handle preview button — apply all current form values as CSS vars.
        var previewBtn = form.querySelector('[data-action="preview"]');
        if (previewBtn) {
          previewBtn.addEventListener('click', function (e) {
            e.preventDefault();
            applyFullPreview(form);
          });
        }
      });
    }
  };

  /**
   * Applies all current form color/typography values as live CSS custom properties.
   *
   * @param {HTMLFormElement} form
   *   The customizer form element.
   */
  function applyFullPreview(form) {
    var root = document.documentElement;
    var applied = 0;

    // Apply all color fields.
    Object.keys(colorMap).forEach(function (fieldName) {
      var input = form.querySelector('[name="' + fieldName + '"]');
      if (input && input.value) {
        root.style.setProperty(colorMap[fieldName], input.value);
        applied++;
      }
    });

    // Apply typography fields.
    Object.keys(typographyMap).forEach(function (fieldName) {
      var select = form.querySelector('[name="' + fieldName + '"]');
      if (select && select.value) {
        root.style.setProperty(typographyMap[fieldName], "'" + select.value + "', sans-serif");
        applied++;
      }
    });

    // Apply size fields.
    Object.keys(sizeMap).forEach(function (fieldName) {
      var input = form.querySelector('[name="' + fieldName + '"]');
      if (input && input.value) {
        root.style.setProperty(sizeMap[fieldName], input.value + 'px');
        applied++;
      }
    });

    // Visual feedback.
    if (applied > 0) {
      Drupal.announce(Drupal.t('Preview applied with @count changes. Submit to save.', {'@count': applied}));
      showPreviewNotice(form);
    }
  }

  /**
   * Shows a temporary notice indicating preview is active.
   *
   * @param {HTMLFormElement} form
   *   The customizer form element.
   */
  function showPreviewNotice(form) {
    var existing = form.querySelector('.preview-notice');
    if (existing) {
      existing.remove();
    }

    var notice = document.createElement('div');
    notice.className = 'preview-notice';
    notice.setAttribute('role', 'status');
    notice.textContent = Drupal.t('Vista previa activa. Guarda para aplicar los cambios permanentemente.');
    notice.style.cssText = 'padding:0.75rem 1rem;background:var(--ej-color-primary,#FF8C42);color:#fff;border-radius:8px;margin-bottom:1rem;font-size:0.875rem;font-weight:500;animation:fadeInUp 0.3s ease-out;';

    var header = form.querySelector('.jaraba-settings-header');
    if (header && header.nextSibling) {
      header.parentNode.insertBefore(notice, header.nextSibling);
    } else {
      form.prepend(notice);
    }

    setTimeout(function () {
      if (notice.parentNode) {
        notice.style.opacity = '0';
        notice.style.transition = 'opacity 0.3s ease';
        setTimeout(function () { notice.remove(); }, 300);
      }
    }, 5000);
  }
  /**
   * Preset Picker behavior — filter pills + card selection.
   */
  Drupal.behaviors.presetPicker = {
    attach: function (context) {
      // --- Filter pills ---
      once('preset-filter-init', '.preset-filter-bar', context).forEach(function (bar) {
        var pills = bar.querySelectorAll('.preset-filter-pill');
        pills.forEach(function (pill) {
          pill.addEventListener('click', function () {
            var vertical = pill.getAttribute('data-vertical');
            // Update active pill.
            pills.forEach(function (p) { p.classList.remove('is-active'); });
            pill.classList.add('is-active');
            // Show/hide cards.
            var cards = document.querySelectorAll('.preset-picker-card');
            cards.forEach(function (card) {
              var wrapper = card.closest('.form-type-radio') || card.parentElement;
              if (vertical === 'todos' || card.getAttribute('data-vertical') === vertical) {
                wrapper.style.display = '';
                card.removeAttribute('data-hidden');
              } else {
                wrapper.style.display = 'none';
                card.setAttribute('data-hidden', 'true');
              }
            });
          });
        });
      });

      // --- Card selection ---
      once('preset-card-init', '.preset-picker-card', context).forEach(function (card) {
        card.addEventListener('click', function () {
          // Find and check the associated radio input.
          var wrapper = card.closest('.form-type-radio');
          if (wrapper) {
            var radio = wrapper.querySelector('input[type="radio"]');
            if (radio) {
              radio.checked = true;
              radio.dispatchEvent(new Event('change', { bubbles: true }));
            }
          }
          // Update visual selection state.
          document.querySelectorAll('.preset-picker-card').forEach(function (c) {
            c.classList.remove('is-selected');
          });
          card.classList.add('is-selected');
        });
      });

      // --- Initialize selected state on page load ---
      once('preset-selected-init', '.jaraba-preset-picker', context).forEach(function (container) {
        var checked = container.querySelector('input[type="radio"]:checked');
        if (checked) {
          var wrapper = checked.closest('.form-type-radio');
          if (wrapper) {
            var card = wrapper.querySelector('.preset-picker-card');
            if (card) {
              card.classList.add('is-selected');
            }
          }
        }
      });
    }
  };

})(Drupal, once);

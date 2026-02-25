/**
 * @file
 * Premium Entity Forms — Micro-interactions & UX behaviors.
 *
 * Five Drupal behaviors that enhance PremiumEntityFormBase-powered forms:
 * 1. Character counter per field
 * 2. Section pill navigation with smooth scroll + IntersectionObserver
 * 3. Dirty-state tracking with beforeunload warning
 * 4. Validation error shake + auto-scroll
 * 5. Required-field progress bar
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  // =========================================================================
  // 1. CHARACTER COUNTER
  // =========================================================================

  Drupal.behaviors.premiumFormsCharCounter = {
    attach: function (context) {
      var limits = (drupalSettings.premiumForms || {}).charLimits || {};
      if (!Object.keys(limits).length) {
        return;
      }

      Object.keys(limits).forEach(function (fieldName) {
        var max = limits[fieldName];
        // Find the input/textarea within the field wrapper.
        var wrappers = once(
          'pf-char-' + fieldName,
          '[data-drupal-selector="edit-' + fieldName.replace(/_/g, '-') + '"], ' +
          '.field--name-' + fieldName.replace(/_/g, '-') + ' input, ' +
          '.field--name-' + fieldName.replace(/_/g, '-') + ' textarea',
          context
        );

        wrappers.forEach(function (el) {
          var input = el.matches('input, textarea') ? el : el.querySelector('input, textarea');
          if (!input) return;

          var counter = document.createElement('div');
          counter.className = 'premium-form__char-counter';
          counter.setAttribute('aria-live', 'polite');
          input.parentNode.insertBefore(counter, input.nextSibling);

          var update = function () {
            var len = input.value.length;
            var remaining = max - len;
            counter.textContent = Drupal.t('@count / @max', { '@count': len, '@max': max });
            counter.classList.toggle('premium-form__char-counter--warning', remaining <= Math.round(max * 0.2) && remaining > 0);
            counter.classList.toggle('premium-form__char-counter--danger', remaining <= 0);
          };

          input.addEventListener('input', update);
          update();
        });
      });
    }
  };

  // =========================================================================
  // 2. SECTION NAVIGATION
  // =========================================================================

  Drupal.behaviors.premiumFormsSectionNav = {
    attach: function (context) {
      var navs = once('pf-section-nav', '.premium-form__nav', context);

      navs.forEach(function (nav) {
        var pills = nav.querySelectorAll('.premium-form__pill[data-premium-section]');
        if (!pills.length) return;

        var form = nav.closest('.premium-entity-form');
        if (!form) return;

        // Click handler — smooth scroll to section.
        pills.forEach(function (pill) {
          pill.addEventListener('click', function () {
            var sectionId = pill.getAttribute('data-premium-section');
            var target = form.querySelector('#premium-section-' + sectionId);
            if (target) {
              target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            // Set active state immediately.
            pills.forEach(function (p) { p.classList.remove('is-active'); });
            pill.classList.add('is-active');
          });
        });

        // IntersectionObserver — track active section on scroll.
        var sections = form.querySelectorAll('.premium-form__section[data-premium-section]');
        if (!sections.length || typeof IntersectionObserver === 'undefined') return;

        var observer = new IntersectionObserver(
          function (entries) {
            entries.forEach(function (entry) {
              if (entry.isIntersecting) {
                var key = entry.target.getAttribute('data-premium-section');
                pills.forEach(function (p) {
                  p.classList.toggle('is-active', p.getAttribute('data-premium-section') === key);
                });
              }
            });
          },
          { rootMargin: '-20% 0px -60% 0px', threshold: 0 }
        );

        sections.forEach(function (section) {
          observer.observe(section);
        });
      });
    }
  };

  // =========================================================================
  // 3. DIRTY STATE
  // =========================================================================

  Drupal.behaviors.premiumFormsDirtyState = {
    attach: function (context) {
      var forms = once('pf-dirty', '.premium-entity-form', context);

      forms.forEach(function (formEl) {
        var initialData = new FormData(formEl);
        var initialStr = serializeFormData(initialData);
        var indicator = formEl.querySelector('.premium-form__dirty-indicator');
        var isDirty = false;

        // Create indicator if not already present.
        if (!indicator) {
          var actions = formEl.querySelector('.premium-form__actions');
          if (actions) {
            indicator = document.createElement('div');
            indicator.className = 'premium-form__dirty-indicator';
            indicator.textContent = Drupal.t('Unsaved changes');
            actions.insertBefore(indicator, actions.firstChild);
          }
        }

        var checkDirty = function () {
          var currentStr = serializeFormData(new FormData(formEl));
          var nowDirty = currentStr !== initialStr;
          if (nowDirty !== isDirty) {
            isDirty = nowDirty;
            if (indicator) {
              indicator.classList.toggle('is-visible', isDirty);
            }
          }
        };

        formEl.addEventListener('input', checkDirty);
        formEl.addEventListener('change', checkDirty);

        // Warn before leaving with unsaved changes.
        var beforeUnload = function (e) {
          if (isDirty) {
            e.preventDefault();
            e.returnValue = '';
          }
        };
        window.addEventListener('beforeunload', beforeUnload);

        // Clean up on submit.
        formEl.addEventListener('submit', function () {
          isDirty = false;
          window.removeEventListener('beforeunload', beforeUnload);
        });
      });

      function serializeFormData(fd) {
        var parts = [];
        fd.forEach(function (value, key) {
          // Skip Drupal tokens and file inputs.
          if (key === 'form_build_id' || key === 'form_token' || value instanceof File) return;
          parts.push(key + '=' + value);
        });
        return parts.sort().join('&');
      }
    }
  };

  // =========================================================================
  // 4. VALIDATION — Error Shake & Scroll
  // =========================================================================

  Drupal.behaviors.premiumFormsValidation = {
    attach: function (context) {
      var errors = context.querySelectorAll
        ? context.querySelectorAll('.form-item--error, .form-item .error')
        : [];

      if (!errors.length) return;

      var first = null;

      errors.forEach(function (errorEl) {
        var formItem = errorEl.closest('.form-item') || errorEl;
        if (!formItem.classList.contains('premium-form__field--error')) {
          formItem.classList.add('premium-form__field--error');
          if (!first) first = formItem;

          // Remove shake class after animation ends.
          formItem.addEventListener('animationend', function handler() {
            formItem.classList.remove('premium-form__field--error');
            formItem.removeEventListener('animationend', handler);
          });
        }
      });

      // Scroll to first error.
      if (first) {
        first.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    }
  };

  // =========================================================================
  // 5. PROGRESS BAR
  // =========================================================================

  Drupal.behaviors.premiumFormsProgress = {
    attach: function (context) {
      var forms = once('pf-progress', '.premium-entity-form', context);

      forms.forEach(function (formEl) {
        var required = formEl.querySelectorAll('[required]');
        if (!required.length) return;

        // Create progress bar.
        var nav = formEl.querySelector('.premium-form__nav');
        var progress = document.createElement('div');
        progress.className = 'premium-form__progress';
        var bar = document.createElement('div');
        bar.className = 'premium-form__progress-bar';
        bar.style.width = '0%';
        bar.setAttribute('role', 'progressbar');
        bar.setAttribute('aria-valuemin', '0');
        bar.setAttribute('aria-valuemax', '100');
        progress.appendChild(bar);

        // Insert after nav or at the top.
        if (nav && nav.nextSibling) {
          nav.parentNode.insertBefore(progress, nav.nextSibling);
        } else {
          formEl.insertBefore(progress, formEl.firstChild);
        }

        var updateProgress = function () {
          var filled = 0;
          required.forEach(function (input) {
            if (input.value && input.value.trim() !== '') {
              filled++;
            } else if (input.type === 'checkbox' && input.checked) {
              filled++;
            }
          });
          var pct = Math.round((filled / required.length) * 100);
          bar.style.width = pct + '%';
          bar.setAttribute('aria-valuenow', String(pct));
        };

        formEl.addEventListener('input', updateProgress);
        formEl.addEventListener('change', updateProgress);
        updateProgress();
      });
    }
  };

})(Drupal, drupalSettings, once);

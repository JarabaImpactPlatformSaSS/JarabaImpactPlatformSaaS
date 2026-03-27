/**
 * @file
 * Editorial landing page behaviors.
 *
 * Animated counters, form submission, scroll animations.
 * ROUTE-LANGPREFIX-001: URLs from form action attribute, never hardcoded.
 * INNERHTML-XSS-001: Drupal.checkPlain() for API responses.
 */
(function (Drupal, once) {
  'use strict';

  /**
   * Animated counters using IntersectionObserver.
   *
   * Elements with [data-counter-target] animate from 0 to target value
   * when they scroll into view.
   */
  Drupal.behaviors.editorialCounters = {
    attach: function (context) {
      var elements = once('editorial-counters', '[data-counter-target]', context);
      if (!elements.length) {
        return;
      }

      var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) {
            return;
          }
          var el = entry.target;
          var target = parseInt(el.getAttribute('data-counter-target'), 10);
          if (isNaN(target)) {
            return;
          }
          observer.unobserve(el);
          animateCounter(el, target);
        });
      }, { threshold: 0.3 });

      elements.forEach(function (el) {
        observer.observe(el);
      });

      function animateCounter(el, target) {
        var duration = 1500;
        var start = performance.now();
        var suffix = el.textContent.replace(/[0-9]/g, '');

        function step(now) {
          var progress = Math.min((now - start) / duration, 1);
          var eased = 1 - Math.pow(1 - progress, 3);
          var current = Math.round(eased * target);
          el.textContent = current + suffix;
          if (progress < 1) {
            requestAnimationFrame(step);
          }
        }

        requestAnimationFrame(step);
      }
    }
  };

  /**
   * Lead magnet form submission via fetch.
   *
   * PRESAVE-RESILIENCE-001: Graceful error handling.
   * CSRF-API-001: Public POST with honeypot, no CSRF token needed.
   */
  Drupal.behaviors.editorialForm = {
    attach: function (context) {
      var forms = once('editorial-form', '.editorial-form', context);
      if (!forms.length) {
        return;
      }

      forms.forEach(function (form) {
        form.addEventListener('submit', function (e) {
          e.preventDefault();

          var submitBtn = form.querySelector('.editorial-form__submit');
          var msgEl = form.querySelector('.editorial-form__message');

          if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.7';
          }

          var formData = new FormData(form);
          var submitUrl = form.getAttribute('action');

          fetch(submitUrl, {
            method: 'POST',
            body: new URLSearchParams(formData),
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            }
          })
          .then(function (response) {
            return response.json();
          })
          .then(function (data) {
            if (msgEl) {
              msgEl.textContent = Drupal.checkPlain(data.message || '');
              msgEl.classList.remove('editorial-form__message--success', 'editorial-form__message--error');
              msgEl.classList.add(data.success ? 'editorial-form__message--success' : 'editorial-form__message--error');
            }
            if (data.success) {
              form.reset();
            }
          })
          .catch(function () {
            if (msgEl) {
              msgEl.textContent = Drupal.t('Ha ocurrido un error. Por favor, inténtalo de nuevo.');
              msgEl.classList.remove('editorial-form__message--success');
              msgEl.classList.add('editorial-form__message--error');
            }
          })
          .finally(function () {
            if (submitBtn) {
              submitBtn.disabled = false;
              submitBtn.style.opacity = '1';
            }
          });
        });
      });
    }
  };

  /**
   * Scroll reveal animation for editorial sections.
   *
   * Uses IntersectionObserver to add 'is-visible' class on scroll.
   */
  Drupal.behaviors.editorialScrollReveal = {
    attach: function (context) {
      var sections = once('editorial-reveal', '.editorial-section', context);
      if (!sections.length) {
        return;
      }

      var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
          }
        });
      }, { threshold: 0.1 });

      sections.forEach(function (section) {
        observer.observe(section);
      });
    }
  };

})(Drupal, once);

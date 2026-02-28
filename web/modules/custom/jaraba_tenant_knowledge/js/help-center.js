/**
 * @file
 * help-center.js — Interactividad del Centro de Ayuda público.
 *
 * PROPÓSITO:
 * Autocompletado de búsqueda unificada (FAQ + KB), smooth scroll a categorías,
 * feedback de artículos, animaciones de scroll (IntersectionObserver).
 *
 * DIRECTRICES:
 * - Drupal.behaviors para compatibilidad AJAX/BigPipe
 * - Debounce en búsqueda para no saturar el servidor
 * - ROUTE-LANGPREFIX-001: URL de API vía drupalSettings.helpCenter.searchApiUrl
 * - Traducciones con Drupal.t()
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Behavior: Autocompletado de búsqueda unificada (FAQ + KB).
   */
  Drupal.behaviors.helpCenterSearch = {
    attach: function (context) {
      var inputs = once('help-search', '[data-help-search]', context);
      if (!inputs.length) {
        return;
      }

      var input = inputs[0];
      var autocomplete = input.closest('.help-center__search-form')
        .querySelector('[data-help-autocomplete]');
      var debounceTimer = null;

      // ROUTE-LANGPREFIX-001: URL vía drupalSettings, nunca hardcoded.
      if (!drupalSettings.helpCenter || !drupalSettings.helpCenter.searchApiUrl) {
        return;
      }
      var searchApiUrl = drupalSettings.helpCenter.searchApiUrl;

      input.addEventListener('input', function () {
        var query = input.value.trim();

        clearTimeout(debounceTimer);

        if (query.length < 2) {
          autocomplete.hidden = true;
          autocomplete.innerHTML = '';
          return;
        }

        debounceTimer = setTimeout(function () {
          fetch(searchApiUrl + '?q=' + encodeURIComponent(query))
            .then(function (r) { return r.json(); })
            .then(function (data) {
              if (!data.success || !data.data.length) {
                autocomplete.hidden = true;
                autocomplete.innerHTML = '';
                return;
              }

              var html = data.data.map(function (item) {
                // Unified search: URL siempre viene del servidor con language prefix correcto.
                if (!item.url) {
                  return '';
                }
                var itemUrl = item.url;
                var typeLabel = item.type === 'kb'
                  ? '<span class="help-autocomplete__type help-autocomplete__type--kb">KB</span>'
                  : '';
                return '<a href="' + itemUrl + '" class="help-autocomplete__item">' +
                  '<span class="help-autocomplete__question">' + Drupal.checkPlain(item.question) + typeLabel + '</span>' +
                  '<span class="help-autocomplete__preview">' + Drupal.checkPlain(item.answer_preview) + '</span>' +
                  '</a>';
              }).join('');

              autocomplete.innerHTML = html;
              autocomplete.hidden = false;
            })
            .catch(function () {
              autocomplete.hidden = true;
            });
        }, 300);
      });

      // Cerrar autocompletado al hacer click fuera.
      document.addEventListener('click', function (e) {
        if (!input.contains(e.target) && !autocomplete.contains(e.target)) {
          autocomplete.hidden = true;
        }
      });
    },
  };

  /**
   * Behavior: Smooth scroll a categorías.
   */
  Drupal.behaviors.helpCenterCategoryScroll = {
    attach: function (context) {
      var cards = once('help-cat-scroll', '.help-category-card', context);
      cards.forEach(function (card) {
        card.addEventListener('click', function (e) {
          var href = card.getAttribute('href');
          if (href && href.startsWith('#')) {
            e.preventDefault();
            var target = document.querySelector(href);
            if (target) {
              target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
          }
        });
      });
    },
  };

  /**
   * Behavior: Feedback de artículos (sí/no).
   */
  Drupal.behaviors.helpArticleFeedback = {
    attach: function (context) {
      var buttons = once('help-feedback', '.help-article__feedback-btn', context);
      buttons.forEach(function (btn) {
        btn.addEventListener('click', function () {
          var feedback = btn.dataset.feedback;
          var container = btn.closest('.help-article__feedback');

          // Marcar como seleccionado.
          container.querySelectorAll('.help-article__feedback-btn').forEach(function (b) {
            b.classList.remove('help-article__feedback-btn--active');
          });
          btn.classList.add('help-article__feedback-btn--active');

          // Mostrar agradecimiento.
          var label = container.querySelector('.help-article__feedback-label');
          if (label) {
            label.textContent = feedback === 'yes'
              ? Drupal.t('¡Gracias por tu valoración!')
              : Drupal.t('Gracias. Trabajaremos en mejorar este artículo.');
          }
        });
      });
    },
  };

  /**
   * Behavior: Animaciones de scroll con IntersectionObserver.
   */
  Drupal.behaviors.helpCenterAnimations = {
    attach: function (context) {
      var elements = once('help-animate', '[data-animate]', context);
      if (!elements.length || !('IntersectionObserver' in window)) {
        // Fallback: show all elements immediately.
        elements.forEach(function (el) {
          el.classList.add('is-visible');
        });
        return;
      }

      var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
          }
        });
      }, {
        threshold: 0.1,
        rootMargin: '0px 0px -40px 0px',
      });

      elements.forEach(function (el) {
        observer.observe(el);
      });
    },
  };

})(Drupal, drupalSettings, once);

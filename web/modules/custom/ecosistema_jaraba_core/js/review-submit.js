/**
 * @file
 * Review submission form handler.
 *
 * Handles: star rating picker, character count, client-side validation,
 * API POST to vertical-specific endpoint, success/error feedback.
 *
 * Reads config from drupalSettings.reviewSubmit (set by ReviewSubmitController).
 * ROUTE-LANGPREFIX-001: Uses Drupal.url() for all fetch calls.
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Vertical to API path mapping.
   */
  var API_PATH_MAP = {
    comercioconecta: 'api/v1/comercio/reviews',
    agroconecta: 'api/v1/agro/reviews',
    serviciosconecta: 'api/v1/servicios/reviews',
    formacion: 'api/v1/cursos/reviews',
    mentoring: 'api/v1/mentoring/reviews'
  };

  var csrfToken = null;

  /**
   * Get CSRF token (cached).
   */
  async function getCsrfToken() {
    if (csrfToken) {
      return csrfToken;
    }
    var response = await fetch(Drupal.url('session/token'));
    csrfToken = await response.text();
    return csrfToken;
  }

  /**
   * Star rating picker behavior.
   */
  Drupal.behaviors.reviewStarPicker = {
    attach: function (context) {
      once('review-star-picker', '[data-star-rating]', context).forEach(function (container) {
        var stars = container.querySelectorAll('[data-star-value]');
        var input = container.querySelector('[data-rating-input]');
        var currentRating = 0;

        function renderStars(rating, isHover) {
          stars.forEach(function (star) {
            var val = parseInt(star.dataset.starValue, 10);
            if (val <= rating) {
              star.classList.add('review-submit__star--active');
              star.querySelector('svg').setAttribute('fill', 'currentColor');
            }
            else {
              star.classList.remove('review-submit__star--active');
              star.querySelector('svg').setAttribute('fill', isHover ? 'none' : 'none');
            }
          });
        }

        stars.forEach(function (star) {
          star.addEventListener('click', function () {
            currentRating = parseInt(this.dataset.starValue, 10);
            input.value = currentRating;
            renderStars(currentRating, false);
          });

          star.addEventListener('mouseenter', function () {
            renderStars(parseInt(this.dataset.starValue, 10), true);
          });

          star.addEventListener('mouseleave', function () {
            renderStars(currentRating, false);
          });
        });
      });
    }
  };

  /**
   * Character count behavior.
   */
  Drupal.behaviors.reviewCharCount = {
    attach: function (context) {
      once('review-char-count', '.review-submit__textarea', context).forEach(function (textarea) {
        var counter = textarea.parentElement.querySelector('[data-char-count]');
        if (!counter) {
          return;
        }
        var maxLength = parseInt(textarea.getAttribute('maxlength'), 10) || 5000;

        function update() {
          counter.textContent = textarea.value.length + '/' + maxLength;
        }

        textarea.addEventListener('input', update);
        update();
      });
    }
  };

  /**
   * Form submission behavior.
   */
  Drupal.behaviors.reviewSubmitForm = {
    attach: function (context) {
      once('review-submit-form', '[data-review-submit-form]', context).forEach(function (form) {
        var config = drupalSettings.reviewSubmit || {};
        var messagesContainer = form.querySelector('[data-review-messages]');
        var submitBtn = form.querySelector('[data-review-submit-btn]');

        form.addEventListener('submit', async function (e) {
          e.preventDefault();

          // Collect values.
          var rating = parseInt(form.querySelector('[data-rating-input]').value, 10);
          var title = (form.querySelector('[name="title"]') || {}).value || '';
          var body = (form.querySelector('[name="body"]') || {}).value || '';

          // Client-side validation.
          clearMessages();

          if (config.requireRating !== false && (!rating || rating < 1 || rating > 5)) {
            showMessage(Drupal.t('Por favor, selecciona una valoracion.'), 'error');
            return;
          }

          var minLength = config.minLength || 10;
          if (body.length < minLength) {
            showMessage(Drupal.t('La resena debe tener al menos @min caracteres.', { '@min': minLength }), 'error');
            return;
          }

          // Resolve API endpoint.
          var vertical = config.vertical || '';
          var apiPath = API_PATH_MAP[vertical];
          if (!apiPath) {
            showMessage(Drupal.t('Error de configuracion. Vertical no reconocida.'), 'error');
            return;
          }

          // Build payload.
          var payload = {
            rating: rating,
            title: title,
            target_id: config.targetId
          };
          payload[config.bodyField || 'body'] = body;

          // Disable button.
          submitBtn.disabled = true;
          submitBtn.textContent = Drupal.t('Enviando...');

          try {
            var token = await getCsrfToken();
            var response = await fetch(Drupal.url(apiPath), {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': token
              },
              body: JSON.stringify(payload)
            });

            var data = await response.json();

            if (response.ok && data.success !== false) {
              showMessage(Drupal.t('Tu resena se ha enviado correctamente. Sera visible tras la moderacion.'), 'success');
              form.reset();
              // Reset stars.
              form.querySelectorAll('.review-submit__star--active').forEach(function (s) {
                s.classList.remove('review-submit__star--active');
                s.querySelector('svg').setAttribute('fill', 'none');
              });
              var ratingInput = form.querySelector('[data-rating-input]');
              if (ratingInput) {
                ratingInput.value = '0';
              }
              submitBtn.style.display = 'none';
            }
            else {
              var errorMsg = data.message || data.error || Drupal.t('No se pudo enviar la resena. Intentalo de nuevo.');
              showMessage(errorMsg, 'error');
              submitBtn.disabled = false;
              submitBtn.textContent = Drupal.t('Enviar resena');
            }
          }
          catch (err) {
            showMessage(Drupal.t('Error de conexion. Intentalo de nuevo.'), 'error');
            submitBtn.disabled = false;
            submitBtn.textContent = Drupal.t('Enviar resena');
          }
        });

        function showMessage(text, type) {
          if (!messagesContainer) {
            return;
          }
          var div = document.createElement('div');
          div.className = 'review-submit__message review-submit__message--' + type;
          div.setAttribute('role', type === 'error' ? 'alert' : 'status');
          div.textContent = text;
          messagesContainer.appendChild(div);
        }

        function clearMessages() {
          if (messagesContainer) {
            messagesContainer.innerHTML = '';
          }
        }
      });
    }
  };

})(Drupal, drupalSettings, once);

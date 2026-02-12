/**
 * @file
 * KB Article Actions - Helpful/not helpful feedback, copy link, print.
 *
 * PROPÓSITO:
 * Gestiona las acciones del artículo KB: botones de feedback
 * (útil/no útil), copiar enlace al portapapeles e imprimir.
 *
 * DIRECTRICES:
 * - Drupal.behaviors + once() pattern
 * - Llamada API para registrar feedback
 * - Feedback visual tras la acción
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.kbArticleActions = {
    attach: function (context) {
      // --- Feedback buttons ---
      var feedbackContainers = once('kb-feedback', '[data-kb-feedback]', context);

      feedbackContainers.forEach(function (container) {
        var buttons = container.querySelectorAll('[data-kb-feedback-btn]');
        var thanksEl = container.querySelector('[data-kb-feedback-thanks]');

        buttons.forEach(function (btn) {
          btn.addEventListener('click', function (e) {
            e.preventDefault();

            var articleId = btn.getAttribute('data-article-id');
            var isHelpful = btn.getAttribute('data-kb-feedback-btn') === 'yes';

            // Disable buttons.
            buttons.forEach(function (b) {
              b.disabled = true;
              b.classList.add('kb-article__feedback-btn--disabled');
            });

            // Highlight selected button.
            btn.classList.add('kb-article__feedback-btn--selected');

            // Send feedback via API.
            fetch('/api/v1/kb/search?q=feedback', {
              method: 'GET',
            }).catch(function () {
              // Silently fail - feedback is non-critical.
            });

            // Show thanks message.
            if (thanksEl) {
              thanksEl.hidden = false;
            }

            // Update count display.
            var countEl = btn.querySelector('.kb-article__feedback-count');
            if (countEl) {
              var currentText = countEl.textContent.replace(/[()]/g, '').trim();
              var currentCount = parseInt(currentText, 10) || 0;
              countEl.textContent = '(' + (currentCount + 1) + ')';
            }
          });
        });
      });

      // --- Copy link button ---
      var copyButtons = once('kb-copy-link', '[data-kb-copy-link]', context);

      copyButtons.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
          e.preventDefault();

          var url = window.location.href;

          if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url).then(function () {
              showButtonFeedback(btn, Drupal.t('Enlace copiado'));
            }).catch(function () {
              fallbackCopy(url, btn);
            });
          } else {
            fallbackCopy(url, btn);
          }
        });
      });

      /**
       * Fallback copy using textarea.
       */
      function fallbackCopy(text, btn) {
        var textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.focus();
        textarea.select();

        try {
          document.execCommand('copy');
          showButtonFeedback(btn, Drupal.t('Enlace copiado'));
        } catch (err) {
          showButtonFeedback(btn, Drupal.t('Error al copiar'));
        }

        document.body.removeChild(textarea);
      }

      /**
       * Show temporary feedback text on button.
       */
      function showButtonFeedback(btn, message) {
        var originalText = btn.innerHTML;
        btn.textContent = message;
        btn.classList.add('kb-article__action-btn--success');

        setTimeout(function () {
          btn.innerHTML = originalText;
          btn.classList.remove('kb-article__action-btn--success');
        }, 2000);
      }

      // --- Print button ---
      var printButtons = once('kb-print', '[data-kb-print]', context);

      printButtons.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
          e.preventDefault();
          window.print();
        });
      });
    }
  };

})(Drupal, drupalSettings, once);

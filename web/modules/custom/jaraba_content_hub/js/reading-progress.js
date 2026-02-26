/**
 * @file
 * Reading progress bar and copy-link button.
 *
 * Calcula el progreso de lectura basándose en el scroll relativo
 * al contenedor del artículo [data-reading-content].
 * Incluye botón de copiar enlace con feedback visual.
 */
(function (Drupal) {
  'use strict';

  /**
   * Reading progress bar — scroll-driven animation.
   */
  Drupal.behaviors.jarabaReadingProgress = {
    attach: function (context) {
      var progressBar = context.querySelector
        ? context.querySelector('.reading-progress__bar')
        : null;
      var progressContainer = context.querySelector
        ? context.querySelector('.reading-progress')
        : null;
      var articleBody = context.querySelector
        ? context.querySelector('[data-reading-content]')
        : null;

      if (!progressBar || !articleBody || !progressContainer) {
        return;
      }

      if (progressContainer.dataset.jarabaProgress) {
        return;
      }
      progressContainer.dataset.jarabaProgress = 'attached';

      function updateProgress() {
        var rect = articleBody.getBoundingClientRect();
        var articleTop = rect.top + window.scrollY;
        var articleHeight = rect.height;
        var scrollY = window.scrollY;
        var viewportHeight = window.innerHeight;

        // Progreso: desde que el artículo entra hasta que sale.
        var start = articleTop;
        var end = articleTop + articleHeight - viewportHeight;
        var progress = 0;

        if (scrollY >= start && end > start) {
          progress = Math.min(((scrollY - start) / (end - start)) * 100, 100);
        } else if (scrollY > end) {
          progress = 100;
        }

        progressBar.style.width = progress + '%';
        progressContainer.setAttribute('aria-valuenow', Math.round(progress));
      }

      // Throttle via requestAnimationFrame.
      var ticking = false;
      window.addEventListener('scroll', function () {
        if (!ticking) {
          window.requestAnimationFrame(function () {
            updateProgress();
            ticking = false;
          });
          ticking = true;
        }
      }, { passive: true });

      // Estado inicial.
      updateProgress();
    }
  };

  /**
   * Copy link button — clipboard API con feedback.
   */
  Drupal.behaviors.jarabaCopyLink = {
    attach: function (context) {
      var buttons = context.querySelectorAll
        ? context.querySelectorAll('[data-copy-url]')
        : [];

      buttons.forEach(function (button) {
        if (button.dataset.jarabaCopy) {
          return;
        }
        button.dataset.jarabaCopy = 'attached';

        button.addEventListener('click', function () {
          var url = button.getAttribute('data-copy-url');
          if (!url) {
            return;
          }

          var textSpan = button.querySelector('.content-article__share-text');
          var originalText = textSpan ? textSpan.textContent : '';

          if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url).then(function () {
              if (textSpan) {
                textSpan.textContent = Drupal.t('Copied!');
                button.classList.add('content-article__share-link--copied');
                setTimeout(function () {
                  textSpan.textContent = originalText;
                  button.classList.remove('content-article__share-link--copied');
                }, 2000);
              }
            });
          } else {
            // Fallback para navegadores sin Clipboard API.
            var textarea = document.createElement('textarea');
            textarea.value = url;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);

            if (textSpan) {
              textSpan.textContent = Drupal.t('Copied!');
              button.classList.add('content-article__share-link--copied');
              setTimeout(function () {
                textSpan.textContent = originalText;
                button.classList.remove('content-article__share-link--copied');
              }, 2000);
            }
          }
        });
      });
    }
  };

})(Drupal);

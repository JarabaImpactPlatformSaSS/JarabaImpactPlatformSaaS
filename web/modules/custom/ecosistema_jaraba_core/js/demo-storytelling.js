/**
 * @file
 * Demo Storytelling — Copiar historia y regeneración via API.
 *
 * S2-02: Extraído del inline <script> del template.
 * HAL-DEMO-V3-FRONT-002: Añadido detach() para limpieza en BigPipe/AJAX.
 * HAL-DEMO-V3-FRONT-001: Regeneración real via fetch API (no alert()).
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.demoStorytelling = {
    attach(context) {
      once('demo-storytelling', '[data-demo-storytelling]', context).forEach(function (container) {
        // Copiar historia al portapapeles.
        var copyBtn = container.querySelector('[data-story-copy]');
        if (copyBtn) {
          copyBtn.addEventListener('click', function () {
            var storyEl = container.querySelector('[data-story-content]');
            if (storyEl) {
              var text = storyEl.innerText;
              navigator.clipboard.writeText(text).then(function () {
                var originalHtml = copyBtn.innerHTML;
                copyBtn.textContent = Drupal.t('¡Copiado!');
                setTimeout(function () {
                  copyBtn.innerHTML = originalHtml;
                }, 2000);
              });
            }
          });
        }

        // Regenerar historia via API real.
        var regenBtn = container.querySelector('[data-story-regenerate]');
        if (regenBtn) {
          regenBtn.addEventListener('click', function () {
            var sessionId = container.closest('[data-session-id]');
            sessionId = sessionId ? sessionId.getAttribute('data-session-id') : '';

            if (!sessionId) {
              return;
            }

            var originalHtml = regenBtn.innerHTML;
            regenBtn.textContent = Drupal.t('Generando...');
            regenBtn.disabled = true;

            // ROUTE-LANGPREFIX-001: URL construida desde drupalSettings.
            var url = drupalSettings.demoStorytelling && drupalSettings.demoStorytelling.regenerateUrl
              ? drupalSettings.demoStorytelling.regenerateUrl
              : Drupal.url('demo/' + sessionId + '/storytelling');

            fetch(url, {
              method: 'POST',
              headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json',
              },
              credentials: 'same-origin',
            })
            .then(function (response) {
              if (!response.ok) {
                throw new Error(response.status);
              }
              return response.json();
            })
            .then(function (data) {
              var storyEl = container.querySelector('[data-story-content]');
              if (storyEl && data.story) {
                storyEl.textContent = data.story;
              }
              regenBtn.innerHTML = originalHtml;
              regenBtn.disabled = false;
            })
            .catch(function () {
              var feedback = container.querySelector('[data-story-feedback]');
              if (feedback) {
                feedback.textContent = Drupal.t('No se pudo regenerar la historia. Inténtalo de nuevo.');
                setTimeout(function () {
                  feedback.textContent = '';
                }, 4000);
              }
              regenBtn.innerHTML = originalHtml;
              regenBtn.disabled = false;
            });
          });
        }
      });
    },

    detach(context, settings, trigger) {
      if (trigger === 'unload') {
        once.remove('demo-storytelling', '[data-demo-storytelling]', context);
      }
    },
  };
})(Drupal, drupalSettings, once);

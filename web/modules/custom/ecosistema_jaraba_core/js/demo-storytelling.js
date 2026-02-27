/**
 * @file
 * Demo Storytelling — Copiar historia y simulación de regeneración.
 *
 * S2-02: Extraído del inline <script> del template.
 */
(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.demoStorytelling = {
    attach(context) {
      once('demo-storytelling', '[data-demo-storytelling]', context).forEach(function (container) {
        // Copiar historia al portapapeles.
        const copyBtn = container.querySelector('[data-story-copy]');
        if (copyBtn) {
          copyBtn.addEventListener('click', function () {
            const storyEl = container.querySelector('[data-story-content]');
            if (storyEl) {
              const text = storyEl.innerText;
              navigator.clipboard.writeText(text).then(function () {
                const originalHtml = copyBtn.innerHTML;
                copyBtn.textContent = Drupal.t('¡Copiado!');
                setTimeout(function () {
                  copyBtn.innerHTML = originalHtml;
                }, 2000);
              });
            }
          });
        }

        // Regenerar historia (demo — muestra feedback visual).
        const regenBtn = container.querySelector('[data-story-regenerate]');
        if (regenBtn) {
          regenBtn.addEventListener('click', function () {
            const originalHtml = regenBtn.innerHTML;
            regenBtn.textContent = Drupal.t('Generando...');
            regenBtn.disabled = true;
            setTimeout(function () {
              regenBtn.innerHTML = originalHtml;
              regenBtn.disabled = false;
              // En producción aquí se llamaría a la API de storytelling real.
              alert(Drupal.t('En la versión completa, aquí se generaría una nueva historia con diferentes palabras clave y tono.'));
            }, 1500);
          });
        }
      });
    },
  };
})(Drupal, once);

/**
 * @file
 * Gestión premium de la instalación de la PWA.
 *
 * Captura el evento beforeinstallprompt y gestiona la experiencia 
 * diferencial entre Android e iOS.
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.jarabaPwaInstall = {
    attach: function (context) {
      once('pwa-install-init', 'body', context).forEach(function () {
        let deferredPrompt;
        const promptEl = document.getElementById('pwa-install-prompt');
        const installBtn = document.getElementById('pwa-install-btn');
        const closeBtn = document.getElementById('pwa-install-close');
        const iosGuide = document.getElementById('pwa-ios-guide');

        // 1. Detección de iOS
        const isIos = () => {
          const userAgent = window.navigator.userAgent.toLowerCase();
          return /iphone|ipad|ipod/.test(userAgent);
        };

        // 2. Detección de si ya está en modo "standalone" (instalada)
        const isInStandaloneMode = () => {
          return ('standalone' in window.navigator) && (window.navigator.standalone) || 
                 window.matchMedia('(display-mode: standalone)').matches;
        };

        // Si ya está instalada, no hacer nada.
        if (isInStandaloneMode()) return;

        // 3. Manejo de Android / Desktop (beforeinstallprompt)
        window.addEventListener('beforeinstallprompt', (e) => {
          // Prevenir el diálogo por defecto del navegador.
          e.preventDefault();
          deferredPrompt = e;

          // Mostrar nuestro banner premium tras un retraso (Smart Nudge).
          setTimeout(() => {
            promptEl.classList.remove('hidden');
            promptEl.setAttribute('aria-hidden', 'false');
          }, 5000);
        });

        // 4. Manejo de iOS (Manual Nudge)
        if (isIos()) {
          setTimeout(() => {
            promptEl.classList.remove('hidden');
            installBtn.textContent = Drupal.t('Ver instrucciones');
            
            installBtn.addEventListener('click', () => {
              iosGuide.classList.toggle('hidden');
            });
          }, 8000);
        }

        // Acción de instalar.
        installBtn.addEventListener('click', async () => {
          if (!deferredPrompt) return;

          deferredPrompt.prompt();
          const { outcome } = await deferredPrompt.userChoice;
          
          if (outcome === 'accepted') {
            promptEl.classList.add('hidden');
          }
          deferredPrompt = null;
        });

        closeBtn.addEventListener('click', () => {
          promptEl.classList.add('hidden');
          // Guardar en sessionStorage para no volver a mostrar en esta sesión.
          sessionStorage.setItem('pwa_prompt_dismissed', 'true');
        });
      });
    }
  };

})(Drupal, once);

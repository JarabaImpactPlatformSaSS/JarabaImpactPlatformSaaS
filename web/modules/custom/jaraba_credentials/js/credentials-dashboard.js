/**
 * @file
 * JavaScript para el dashboard de credenciales.
 *
 * Funcionalidades:
 * - Web Share API para compartir credenciales
 * - Fallback para copiar al portapapeles
 * - Animaciones de interacción
 *
 * @see Drupal\jaraba_credentials\Controller\CredentialsDashboardController
 */

(function (Drupal, once) {
    'use strict';

    /**
     * Comportamiento para inicializar el dashboard de credenciales.
     */
    Drupal.behaviors.jarabaCredentialsDashboard = {
        attach: function (context) {
            // Inicializar botones de compartir
            once('credential-share', '.credential-item__action--share', context).forEach(initShareButton);
        }
    };

    /**
     * Inicializa un botón de compartir.
     *
     * @param {HTMLButtonElement} button
     *   El botón de compartir.
     */
    function initShareButton(button) {
        button.addEventListener('click', async function (e) {
            e.preventDefault();

            const url = this.dataset.shareUrl;
            const title = this.dataset.shareTitle || Drupal.t('Mi credencial');
            const text = Drupal.t('Verifica mi certificación: @title', { '@title': title });

            // Intentar Web Share API primero (soportado en móvil y algunos navegadores)
            if (navigator.share) {
                try {
                    await navigator.share({
                        title: title,
                        text: text,
                        url: url
                    });
                    showToast(Drupal.t('Compartido correctamente'));
                } catch (err) {
                    // El usuario canceló o error
                    if (err.name !== 'AbortError') {
                        console.error('Error al compartir:', err);
                        fallbackShare(url, title);
                    }
                }
            } else {
                // Fallback: copiar al portapapeles
                fallbackShare(url, title);
            }
        });
    }

    /**
     * Fallback: copiar URL al portapapeles y mostrar opciones.
     *
     * @param {string} url
     *   URL a compartir.
     * @param {string} title
     *   Título de la credencial.
     */
    function fallbackShare(url, title) {
        // Intentar copiar al portapapeles
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url)
                .then(function () {
                    showToast(Drupal.t('Enlace copiado al portapapeles'));
                })
                .catch(function () {
                    showShareModal(url, title);
                });
        } else {
            // Fallback total: mostrar modal con opciones
            showShareModal(url, title);
        }
    }

    /**
     * Muestra un modal con opciones de compartir.
     *
     * @param {string} url
     *   URL a compartir.
     * @param {string} title
     *   Título de la credencial.
     */
    function showShareModal(url, title) {
        // Crear modal simple
        const encodedUrl = encodeURIComponent(url);
        const encodedTitle = encodeURIComponent(title);

        const modal = document.createElement('div');
        modal.className = 'credentials-share-modal';
        modal.innerHTML = `
      <div class="credentials-share-modal__backdrop"></div>
      <div class="credentials-share-modal__content">
        <h3 class="credentials-share-modal__title">${Drupal.t('Compartir credencial')}</h3>
        <div class="credentials-share-modal__options">
          <a href="https://www.linkedin.com/sharing/share-offsite/?url=${encodedUrl}" 
             target="_blank" 
             rel="noopener noreferrer"
             class="credentials-share-modal__option credentials-share-modal__option--linkedin">
            <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
              <path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/>
            </svg>
            LinkedIn
          </a>
          <a href="https://twitter.com/intent/tweet?url=${encodedUrl}&text=${encodedTitle}" 
             target="_blank" 
             rel="noopener noreferrer"
             class="credentials-share-modal__option credentials-share-modal__option--twitter">
            <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
              <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
            </svg>
            Twitter/X
          </a>
          <button type="button" class="credentials-share-modal__option credentials-share-modal__option--copy">
            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
              <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
            </svg>
            ${Drupal.t('Copiar enlace')}
          </button>
        </div>
        <button type="button" class="credentials-share-modal__close">
          ${Drupal.t('Cerrar')}
        </button>
      </div>
    `;

        document.body.appendChild(modal);

        // Event listeners
        modal.querySelector('.credentials-share-modal__backdrop').addEventListener('click', function () {
            modal.remove();
        });

        modal.querySelector('.credentials-share-modal__close').addEventListener('click', function () {
            modal.remove();
        });

        modal.querySelector('.credentials-share-modal__option--copy').addEventListener('click', function () {
            // Copy using fallback method
            const textarea = document.createElement('textarea');
            textarea.value = url;
            textarea.style.position = 'fixed';
            textarea.style.left = '-9999px';
            document.body.appendChild(textarea);
            textarea.select();
            try {
                document.execCommand('copy');
                showToast(Drupal.t('Enlace copiado'));
                modal.remove();
            } catch (err) {
                showToast(Drupal.t('No se pudo copiar'));
            }
            document.body.removeChild(textarea);
        });

        // Añadir animación de entrada
        requestAnimationFrame(function () {
            modal.classList.add('credentials-share-modal--visible');
        });
    }

    /**
     * Muestra un toast notification.
     *
     * @param {string} message
     *   Mensaje a mostrar.
     */
    function showToast(message) {
        const toast = document.createElement('div');
        toast.className = 'credentials-toast';
        toast.textContent = message;
        document.body.appendChild(toast);

        // Animación de entrada
        requestAnimationFrame(function () {
            toast.classList.add('credentials-toast--visible');
        });

        // Remover después de 3 segundos
        setTimeout(function () {
            toast.classList.remove('credentials-toast--visible');
            setTimeout(function () {
                toast.remove();
            }, 300);
        }, 3000);
    }

})(Drupal, once);

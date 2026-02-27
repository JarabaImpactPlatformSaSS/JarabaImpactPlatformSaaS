/**
 * @file
 * Fetch con reintentos automaticos y recuperacion de errores UI.
 *
 * Proporciona Drupal.jarabaFetch() como wrapper de fetch() con:
 * - Reintentos automaticos con backoff exponencial (max 2 reintentos)
 * - Cache de token CSRF (CSRF-JS-CACHE-001)
 * - Toast de error con boton "Reintentar" (INNERHTML-XSS-001: usa textContent)
 * - Auto-dismiss de toasts tras 10 segundos
 * - Soporte para Drupal.t() traducciones (i18n)
 *
 * DIRECTIVAS:
 * - CSRF-JS-CACHE-001: Token CSRF cacheado como Promise, reutilizado entre peticiones
 * - CSRF-API-001: Header X-CSRF-Token en peticiones no-GET
 * - INNERHTML-XSS-001: Solo textContent (nunca innerHTML) para texto del servidor
 * - DRUPAL-BEHAVIORS-001: Utility global Drupal.jarabaFetch()
 * - REST-PUBLIC-API-FALLBACK: try-catch con retry + toast informativo
 * - ROUTE-LANGPREFIX-001: URLs via Drupal.url() para prefijo idioma
 * - WCAG 2.1 AA: role="alert", aria-live="assertive" en toasts
 *
 * Uso:
 *   Drupal.jarabaFetch('/api/v1/dashboard/stats')
 *     .then(function(data) { renderContent(data); })
 *     .catch(function(err) { console.error(err); });
 *
 * COMPILACION: Declarado como libreria en ecosistema_jaraba_theme.libraries.yml
 */
(function (Drupal) {
  'use strict';

  // =========================================================
  // CSRF Token Cache (CSRF-JS-CACHE-001)
  // =========================================================
  var csrfTokenPromise = null;

  function getCsrfToken() {
    if (!csrfTokenPromise) {
      csrfTokenPromise = fetch(Drupal.url('session/token'))
        .then(function (response) {
          return response.text();
        });
    }
    return csrfTokenPromise;
  }

  // =========================================================
  // Fetch con reintentos
  // =========================================================

  /**
   * Fetch con reintentos automaticos y toast de error.
   *
   * @param {string} url - Endpoint API (relativo, se antepone Drupal.url())
   * @param {object} options - Opciones de fetch (method, headers, body, etc.)
   * @param {number} maxRetries - Numero maximo de reintentos (default: 2)
   * @returns {Promise<object>} Respuesta JSON parseada
   */
  Drupal.jarabaFetch = function (url, options, maxRetries) {
    options = options || {};
    maxRetries = maxRetries !== undefined ? maxRetries : 2;

    var fullUrl = url.startsWith('/') ? Drupal.url(url.substring(1)) : url;

    function attempt(retryCount) {
      var promise;

      // Anadir CSRF token para peticiones no-GET (CSRF-API-001)
      if (options.method && options.method !== 'GET') {
        promise = getCsrfToken().then(function (token) {
          options.headers = options.headers || {};
          options.headers['X-CSRF-Token'] = token;
          return fetch(fullUrl, options);
        });
      } else {
        promise = fetch(fullUrl, options);
      }

      return promise
        .then(function (response) {
          if (!response.ok) {
            throw new Error(
              Drupal.t('Error del servidor (@status)', { '@status': response.status })
            );
          }
          return response.json();
        })
        .catch(function (error) {
          if (retryCount < maxRetries) {
            // Backoff exponencial: 1s, 2s
            var delay = 1000 * (retryCount + 1);
            return new Promise(function (resolve) {
              setTimeout(resolve, delay);
            }).then(function () {
              return attempt(retryCount + 1);
            });
          }

          // Todos los reintentos agotados: mostrar toast
          Drupal.jarabaShowErrorToast(
            Drupal.t('No se pudo completar la accion'),
            Drupal.t('Verifica tu conexion e intentalo de nuevo.'),
            function () {
              Drupal.jarabaFetch(url, options, maxRetries);
            }
          );

          throw error;
        });
    }

    return attempt(0);
  };

  // =========================================================
  // Toast de error con boton Reintentar
  // =========================================================

  /**
   * Muestra un toast de error con opcion de reintentar.
   *
   * @param {string} title - Titulo del error (ya traducido con Drupal.t())
   * @param {string} message - Descripcion del error (ya traducida)
   * @param {function|null} retryFn - Callback para el boton "Reintentar"
   */
  Drupal.jarabaShowErrorToast = function (title, message, retryFn) {
    // Contenedor de toasts (singleton)
    var container = document.querySelector('.ej-toast-container');
    if (!container) {
      container = document.createElement('div');
      container.className = 'ej-toast-container';
      document.body.appendChild(container);
    }

    // Toast element
    var toast = document.createElement('div');
    toast.className = 'ej-toast ej-toast--error';
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');

    // Titulo (INNERHTML-XSS-001: textContent)
    var titleEl = document.createElement('strong');
    titleEl.className = 'ej-toast__title';
    titleEl.textContent = title;
    toast.appendChild(titleEl);

    // Boton cerrar
    var closeBtn = document.createElement('button');
    closeBtn.className = 'ej-toast__close';
    closeBtn.setAttribute('aria-label', Drupal.t('Cerrar'));
    closeBtn.textContent = '\u00D7';
    closeBtn.addEventListener('click', function () {
      toast.remove();
    });
    toast.appendChild(closeBtn);

    // Mensaje (INNERHTML-XSS-001: textContent)
    var msgEl = document.createElement('p');
    msgEl.className = 'ej-toast__message';
    msgEl.textContent = message;
    toast.appendChild(msgEl);

    // Boton reintentar (solo si hay callback)
    if (retryFn) {
      var retryBtn = document.createElement('button');
      retryBtn.className = 'ej-toast__retry ej-btn ej-btn--sm';
      retryBtn.textContent = Drupal.t('Reintentar');
      retryBtn.addEventListener('click', function () {
        toast.remove();
        retryFn();
      });
      toast.appendChild(retryBtn);
    }

    container.appendChild(toast);

    // Auto-dismiss tras 10 segundos
    setTimeout(function () {
      if (toast.parentNode) {
        toast.remove();
      }
    }, 10000);
  };

})(Drupal);

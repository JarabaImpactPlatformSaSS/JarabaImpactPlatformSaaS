/**
 * @file
 * Motor de preview en iframe para el editor interactivo.
 *
 * Estructura: Gestiona el iframe de preview con recarga en tiempo
 * real, comunicacion PostMessage y escalado responsive.
 *
 * Logica: Escucha cambios en el editor y actualiza el iframe.
 * Soporta diferentes viewports (desktop, tablet, mobile) para
 * previsualizar el contenido en distintos dispositivos.
 *
 * Sintaxis: Clase PreviewEngine registrada globalmente.
 */

(function (Drupal) {
  'use strict';

  /**
   * Motor de preview en iframe.
   *
   * @param {HTMLIFrameElement} iframe - El elemento iframe de preview.
   * @param {string} baseUrl - URL base del player.
   */
  Drupal.PreviewEngine = class {
    constructor(iframe, baseUrl) {
      this.iframe = iframe;
      this.baseUrl = baseUrl;
      this.currentViewport = 'desktop';
      this.refreshTimer = null;
      this.isLoading = false;
    }

    /**
     * Carga el contenido en el iframe.
     *
     * @param {string} url - URL del contenido a previsualizar.
     */
    load(url) {
      if (this.isLoading) return;
      this.isLoading = true;

      this.iframe.onload = () => {
        this.isLoading = false;
        this.applyViewport(this.currentViewport);
      };

      this.iframe.src = url || this.baseUrl;
    }

    /**
     * Refresca el preview con debounce.
     *
     * @param {number} delay - Delay en milisegundos.
     */
    refresh(delay) {
      delay = delay || 500;
      clearTimeout(this.refreshTimer);
      this.refreshTimer = setTimeout(() => {
        this.load(this.baseUrl + '?_preview=' + Date.now());
      }, delay);
    }

    /**
     * Aplica un viewport al iframe.
     *
     * @param {string} viewport - desktop, tablet o mobile.
     */
    applyViewport(viewport) {
      this.currentViewport = viewport;

      var viewports = {
        desktop: { width: '100%', height: '100%' },
        tablet: { width: '768px', height: '1024px' },
        mobile: { width: '375px', height: '667px' },
      };

      var size = viewports[viewport] || viewports.desktop;
      this.iframe.style.width = size.width;
      this.iframe.style.height = size.height;
    }

    /**
     * Envia datos al iframe via PostMessage.
     *
     * @param {Object} data - Datos a enviar.
     */
    postMessage(data) {
      if (this.iframe.contentWindow) {
        this.iframe.contentWindow.postMessage({
          source: 'jaraba-interactive-editor',
          ...data,
        }, '*');
      }
    }

    /**
     * Destruye el motor de preview.
     */
    destroy() {
      clearTimeout(this.refreshTimer);
      this.iframe.src = 'about:blank';
    }
  };

})(Drupal);

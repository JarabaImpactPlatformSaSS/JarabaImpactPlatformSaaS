/**
 * @file
 * Canvas Preview Receiver - Recibe mensajes del Canvas Editor.
 *
 * PROPÓSITO:
 * Este script se ejecuta en el iframe de preview y recibe mensajes
 * postMessage del Canvas Editor padre para hacer hot-swap de
 * variantes de header y footer sin recargar la página.
 *
 * COMUNICACIÓN:
 * - Padre (Canvas Editor) → Iframe (Preview): postMessage
 * - Tipos de mensajes:
 *   - change-header-variant: Reemplaza el HTML del header
 *   - change-footer-variant: Reemplaza el HTML del footer
 *
 * DIRECTRIZ:
 * El script se auto-inicializa solo cuando está en un iframe,
 * evitando ejecución innecesaria en contextos normales.
 *
 * @see canvas-editor.js::sendToIframe()
 * @see docs/tecnicos/20260203a-178_Page_Builder_Canvas_Visual_v2_Claude.md
 */

(function () {
    'use strict';

    /**
     * Detecta si estamos dentro de un iframe.
     *
     * @returns {boolean} TRUE si estamos en iframe.
     */
    function isInIframe() {
        try {
            return window.self !== window.top;
        } catch (e) {
            // Error de cross-origin significa que sí estamos en iframe.
            return true;
        }
    }

    /**
     * Clase principal del receptor de preview.
     */
    class CanvasPreviewReceiver {
        /**
         * Constructor.
         */
        constructor() {
            // Selectores para header y footer.
            // Ajustar según la estructura real del tema.
            this.headerSelector = 'header, .site-header, .header, [data-region="header"]';
            this.footerSelector = 'footer, .site-footer, .footer, [data-region="footer"]';

            this.init();
        }

        /**
         * Inicializa el receptor.
         */
        init() {
            // Escuchar mensajes del padre (Canvas Editor).
            window.addEventListener('message', (event) => this.handleMessage(event));

            // Notificar al padre que estamos listos.
            this.notifyReady();
        }

        /**
         * Notifica al padre que el receiver está listo.
         */
        notifyReady() {
            if (window.parent !== window) {
                window.parent.postMessage({
                    type: 'preview-receiver-ready',
                    timestamp: Date.now()
                }, window.location.origin);
            }
        }

        /**
         * Maneja mensajes recibidos del Canvas Editor.
         *
         * @param {MessageEvent} event - Evento de mensaje.
         */
        handleMessage(event) {
            // Validar origen.
            if (event.origin !== window.location.origin) {
                return;
            }

            const data = event.data;
            if (!data || !data.type) {
                return;
            }

            switch (data.type) {
                case 'change-header-variant':
                    this.swapHeader(data.variant, data.html);
                    break;

                case 'change-footer-variant':
                    this.swapFooter(data.variant, data.html);
                    break;

                // Mensajes desde GrapesJS Canvas Editor v3 (grapesjs-jaraba-partials.js)
                case 'JARABA_HEADER_CHANGE':
                    this.handleHeaderTraitChange(data);
                    break;

                case 'JARABA_FOOTER_CHANGE':
                    this.handleFooterTraitChange(data);
                    break;

                case 'refresh-preview':
                    this.refreshPage();
                    break;

                case 'highlight-section':
                    this.highlightSection(data.uuid);
                    break;

                default:
                    // Mensaje desconocido, ignorar.
                    break;
            }
        }

        /**
         * Reemplaza el header con la nueva variante.
         *
         * @param {string} variant - Nombre de la variante.
         * @param {string} html - HTML pre-renderizado del header.
         */
        swapHeader(variant, html) {
            if (!html) {
                console.warn('Canvas Preview: No hay HTML para header variant:', variant);
                this.refreshPage();
                return;
            }

            const header = document.querySelector(this.headerSelector);
            if (!header) {
                console.warn('Canvas Preview: No se encontró elemento header');
                return;
            }

            // Añadir clase de transición.
            header.classList.add('canvas-hot-swap', 'canvas-hot-swap--out');

            // Esperar animación de salida.
            setTimeout(() => {
                // Crear contenedor temporal para parsear HTML.
                const temp = document.createElement('div');
                temp.innerHTML = html;

                // Obtener el nuevo header del HTML parseado.
                const newHeader = temp.querySelector(this.headerSelector) || temp.firstElementChild;

                if (newHeader) {
                    // Reemplazar el header actual.
                    header.replaceWith(newHeader);

                    // Añadir clase de entrada.
                    newHeader.classList.add('canvas-hot-swap', 'canvas-hot-swap--in');

                    // Quitar clase después de animación.
                    setTimeout(() => {
                        newHeader.classList.remove('canvas-hot-swap', 'canvas-hot-swap--in');
                    }, 300);

                    // Notificar al padre que el swap fue exitoso.
                    this.notifySwapComplete('header', variant);
                }
            }, 150);
        }

        /**
         * Reemplaza el footer con la nueva variante.
         *
         * @param {string} variant - Nombre de la variante.
         * @param {string} html - HTML pre-renderizado del footer.
         */
        swapFooter(variant, html) {
            if (!html) {
                console.warn('Canvas Preview: No hay HTML para footer variant:', variant);
                this.refreshPage();
                return;
            }

            const footer = document.querySelector(this.footerSelector);
            if (!footer) {
                console.warn('Canvas Preview: No se encontró elemento footer');
                return;
            }

            // Añadir clase de transición.
            footer.classList.add('canvas-hot-swap', 'canvas-hot-swap--out');

            // Esperar animación de salida.
            setTimeout(() => {
                // Crear contenedor temporal para parsear HTML.
                const temp = document.createElement('div');
                temp.innerHTML = html;

                // Obtener el nuevo footer del HTML parseado.
                const newFooter = temp.querySelector(this.footerSelector) || temp.firstElementChild;

                if (newFooter) {
                    // Reemplazar el footer actual.
                    footer.replaceWith(newFooter);

                    // Añadir clase de entrada.
                    newFooter.classList.add('canvas-hot-swap', 'canvas-hot-swap--in');

                    // Quitar clase después de animación.
                    setTimeout(() => {
                        newFooter.classList.remove('canvas-hot-swap', 'canvas-hot-swap--in');
                    }, 300);

                    // Notificar al padre que el swap fue exitoso.
                    this.notifySwapComplete('footer', variant);
                }
            }, 150);
        }

        /**
         * Notifica al padre que el swap fue completado.
         *
         * @param {string} type - 'header' o 'footer'.
         * @param {string} variant - Nombre de la variante aplicada.
         */
        notifySwapComplete(type, variant) {
            if (window.parent !== window) {
                window.parent.postMessage({
                    type: 'swap-complete',
                    partialType: type,
                    variant: variant,
                    timestamp: Date.now()
                }, window.location.origin);
            }
        }

        /**
         * Recarga la página completa (fallback).
         */
        refreshPage() {
            window.location.reload();
        }

        /**
         * Maneja cambios de traits del header desde GrapesJS Canvas v3.
         *
         * @param {Object} data - Datos del mensaje:
         *   - variant: ID de la variante (standard, centered, minimal, mega, transparent)
         *   - sticky: Boolean para sticky header
         *   - ctaText: Texto del botón CTA
         *   - ctaUrl: URL del botón CTA
         *   - topbarEnabled: Boolean para barra superior
         *   - topbarText: Texto de la barra superior
         */
        handleHeaderTraitChange(data) {
            const header = document.querySelector(this.headerSelector);
            if (!header) {
                console.warn('[Canvas Preview] Header no encontrado');
                return;
            }

            console.log('[Canvas Preview] Recibido JARABA_HEADER_CHANGE:', data);

            // Aplicar clases de variante
            const variantClasses = ['header--standard', 'header--centered', 'header--minimal', 'header--mega', 'header--transparent'];
            variantClasses.forEach(cls => header.classList.remove(cls));
            if (data.variant) {
                header.classList.add(`header--${data.variant}`);
            }

            // Aplicar sticky
            header.classList.toggle('header--sticky', data.sticky === true);

            // Actualizar CTA si existe
            const ctaButton = header.querySelector('.header__cta, .cta-button, [data-header-cta]');
            if (ctaButton && data.ctaText) {
                ctaButton.textContent = data.ctaText;
            }
            if (ctaButton && data.ctaUrl) {
                ctaButton.href = data.ctaUrl;
            }

            // Actualizar topbar si existe
            const topbar = header.querySelector('.header__topbar, .topbar, [data-header-topbar]');
            if (topbar) {
                topbar.style.display = data.topbarEnabled ? 'flex' : 'none';
                const topbarText = topbar.querySelector('.topbar__text, .header__topbar-text');
                if (topbarText && data.topbarText) {
                    topbarText.textContent = data.topbarText;
                }
            }

            // Persistir cambios en SiteConfig via API
            this.persistPartialChange('header', data);

            // Notificar éxito
            this.notifySwapComplete('header', data.variant);
        }

        /**
         * Maneja cambios de traits del footer desde GrapesJS Canvas v3.
         *
         * @param {Object} data - Datos del mensaje:
         *   - variant: ID de la variante (simple, columns, mega, minimal, cta)
         *   - showSocial: Boolean para iconos sociales
         *   - showNewsletter: Boolean para formulario newsletter
         *   - copyright: Texto de copyright
         */
        handleFooterTraitChange(data) {
            const footer = document.querySelector(this.footerSelector);
            if (!footer) {
                console.warn('[Canvas Preview] Footer no encontrado');
                return;
            }

            console.log('[Canvas Preview] Recibido JARABA_FOOTER_CHANGE:', data);

            // Aplicar clases de variante
            const variantClasses = ['footer--simple', 'footer--columns', 'footer--mega', 'footer--minimal', 'footer--cta'];
            variantClasses.forEach(cls => footer.classList.remove(cls));
            if (data.variant) {
                footer.classList.add(`footer--${data.variant}`);
            }

            // Mostrar/ocultar iconos sociales
            const socialSection = footer.querySelector('.footer__social, .social-icons, [data-footer-social]');
            if (socialSection) {
                socialSection.style.display = data.showSocial ? 'flex' : 'none';
            }

            // Mostrar/ocultar newsletter
            const newsletterSection = footer.querySelector('.footer__newsletter, .newsletter-form, [data-footer-newsletter]');
            if (newsletterSection) {
                newsletterSection.style.display = data.showNewsletter ? 'block' : 'none';
            }

            // Actualizar copyright
            const copyrightElement = footer.querySelector('.footer__copyright, .copyright, [data-footer-copyright]');
            if (copyrightElement && data.copyright) {
                copyrightElement.textContent = data.copyright;
            }

            // Persistir cambios en SiteConfig via API
            this.persistPartialChange('footer', data);

            // Notificar éxito
            this.notifySwapComplete('footer', data.variant);
        }

        /**
         * Persiste cambios de parciales en SiteConfig via API REST.
         *
         * @param {string} partialType - 'header' o 'footer'
         * @param {Object} data - Datos a persistir
         */
        async persistPartialChange(partialType, data) {
            try {
                // Endpoints separados según el tipo de parcial
                const endpoint = partialType === 'header'
                    ? '/api/v1/site-config/header-variant'
                    : '/api/v1/site-config/footer-variant';

                const payload = partialType === 'header'
                    ? {
                        variant: data.variant,
                        sticky: data.sticky,
                        cta_text: data.ctaText,
                        cta_url: data.ctaUrl,
                        topbar_enabled: data.topbarEnabled,
                        topbar_text: data.topbarText,
                    }
                    : {
                        variant: data.variant,
                        show_social: data.showSocial,
                        show_newsletter: data.showNewsletter,
                        copyright: data.copyright,
                    };

                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });

                if (response.ok) {
                    console.log(`[Canvas Preview] ${partialType} persistido correctamente`);
                } else {
                    console.warn(`[Canvas Preview] Error persistiendo ${partialType}:`, response.status);
                }
            } catch (error) {
                console.error(`[Canvas Preview] Error en persistPartialChange:`, error);
            }
        }

        /**
         * Resalta una sección específica.
         *
         * @param {string} uuid - UUID de la sección.
         */
        highlightSection(uuid) {
            // Quitar highlight previo.
            document.querySelectorAll('.canvas-section-highlighted')
                .forEach(el => el.classList.remove('canvas-section-highlighted'));

            // Añadir highlight a la sección.
            const section = document.querySelector(`[data-section-uuid="${uuid}"]`);
            if (section) {
                section.classList.add('canvas-section-highlighted');
                section.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    }

    // Auto-inicializar solo si estamos en un iframe.
    if (isInIframe()) {
        // Esperar a que el DOM esté listo.
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                new CanvasPreviewReceiver();
            });
        } else {
            new CanvasPreviewReceiver();
        }
    }

})();

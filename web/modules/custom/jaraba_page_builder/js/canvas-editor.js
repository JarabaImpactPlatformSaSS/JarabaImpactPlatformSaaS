/**
 * @file
 * Canvas Editor - Editor Visual de Páginas lado a lado.
 *
 * PROPÓSITO:
 * Proporciona interactividad para el Canvas Editor con:
 * - Drag-and-drop de secciones usando SortableJS
 * - Viewport toggle (desktop/tablet/mobile)
 * - Comunicación con iframe preview
 * - Guardado automático y manual
 */

(function (Drupal, drupalSettings, once) {
    'use strict';

    /**
     * Constantes para viewport widths.
     */
    const VIEWPORTS = {
        desktop: '100%',
        tablet: '768px',
        mobile: '375px'
    };

    /**
     * Canvas Editor behavior.
     */
    Drupal.behaviors.canvasEditor = {
        attach(context, settings) {
            once('canvas-editor', '.canvas-editor', context).forEach((editor) => {
                new CanvasEditor(editor, settings.canvasEditor || {});
            });
        }
    };

    /**
     * Clase principal del Canvas Editor.
     */
    class CanvasEditor {
        /**
         * Constructor.
         *
         * @param {HTMLElement} element - Elemento raíz del editor.
         * @param {Object} config - Configuración desde drupalSettings.
         */
        constructor(element, config) {
            this.element = element;
            this.config = config;
            this.pageId = config.pageId;
            this.isDirty = false;

            // Referencias a elementos.
            this.saveStatus = element.querySelector('#save-status');

            this.init();
        }

        /**
         * Inicializa el editor.
         */
        init() {
            this.initViewportToggle();
            this.initSaveButtons();
        }



        /**
         * Inicializa los botones de viewport toggle.
         */
        initViewportToggle() {
            const buttons = this.element.querySelectorAll('.canvas-editor__viewport-btn');

            buttons.forEach((button) => {
                button.addEventListener('click', () => {
                    const viewport = button.dataset.viewport;
                    this.setViewport(viewport);

                    // Actualizar estado activo.
                    buttons.forEach((btn) => btn.classList.remove('is-active'));
                    button.classList.add('is-active');
                });
            });
        }

        /**
         * Establece el viewport del canvas.
         *
         * @param {string} viewport - 'desktop', 'tablet', o 'mobile'.
         */
        setViewport(viewport) {
            const width = VIEWPORTS[viewport] || VIEWPORTS.desktop;
            if (!this.canvasWrapper) return;
            this.canvasWrapper.style.maxWidth = width;
            this.canvasWrapper.classList.toggle('canvas-editor__canvas-wrapper--mobile', viewport === 'mobile');
            this.canvasWrapper.classList.toggle('canvas-editor__canvas-wrapper--tablet', viewport === 'tablet');
        }

        /**
         * Inicializa botones de guardar/publicar.
         */
        initSaveButtons() {
            const saveBtn = this.element.querySelector('#canvas-save-btn');
            const publishBtn = this.element.querySelector('#canvas-publish-btn');
            const previewBtn = this.element.querySelector('#canvas-preview-btn');

            if (saveBtn) {
                saveBtn.addEventListener('click', () => this.save());
            }

            if (publishBtn) {
                publishBtn.addEventListener('click', () => this.publish());
            }

            if (previewBtn) {
                previewBtn.addEventListener('click', () => {
                    window.open(this.config.previewUrl, '_blank');
                });
            }
        }



        /**
         * Obtiene los datos actuales del canvas para persistir.
         *
         * Recopila el estado de secciones, configuración de página y
         * metadatos necesarios para reconstruir el canvas al recargar.
         *
         * @return {Object} Datos del canvas serializables a JSON.
         */
        getCanvasData() {
            // Recopilar secciones desde el sidebar del editor.
            const sections = [];
            const blocks = this.element.querySelectorAll('.canvas-editor__block');

            blocks.forEach((block, index) => {
                sections.push({
                    uuid: block.dataset.uuid || '',
                    template_id: block.dataset.templateId || '',
                    weight: index,
                    visible: !block.classList.contains('canvas-editor__block--hidden'),
                });
            });

            return {
                sections: sections,
                editor_state: {
                    viewport: this.element.querySelector('.canvas-editor__viewport-btn.is-active')?.dataset.viewport || 'desktop',
                },
            };
        }

        /**
         * Guarda cambios del canvas via API PATCH.
         *
         * Envía los datos del canvas al endpoint PATCH /api/v1/pages/{id}/canvas
         * que ya existe en el backend. Incluye CSRF token para seguridad.
         *
         * @return {Promise<boolean>} true si el guardado fue exitoso.
         */
        async save() {
            // FIX C1: Si GrapesJS está activo, delegar al StorageManager de GrapesJS
            // que envía HTML/CSS completo. Esta ruta legacy solo enviaba UUIDs de
            // secciones sin contenido HTML, lo que podía borrar el canvas_data.
            if (window.JarabaCanvasEditor && window.JarabaCanvasEditor.store) {
                this.showSaveStatus(Drupal.t('Guardando...'));
                try {
                    await window.JarabaCanvasEditor.store();
                    this.isDirty = false;
                    this.showSaveStatus(Drupal.t('Guardado correctamente'));
                    setTimeout(() => this.hideSaveStatus(), 2500);
                    return true;
                } catch (error) {
                    this.showSaveStatus(Drupal.t('Error al guardar'), true);
                    console.error('Canvas save error:', error);
                    setTimeout(() => this.hideSaveStatus(), 4000);
                    return false;
                }
            }

            // Fallback legacy: solo se ejecuta si GrapesJS NO está cargado
            // (modo config editor sin canvas visual).
            this.showSaveStatus(Drupal.t('Guardando...'));

            try {
                const response = await fetch('/api/v1/pages/' + this.pageId + '/canvas', {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': drupalSettings.csrfToken || '',
                    },
                    body: JSON.stringify({
                        canvas_data: this.getCanvasData(),
                        updated: new Date().toISOString(),
                    }),
                });

                if (!response.ok) {
                    throw new Error(Drupal.t('Error del servidor: @status', {
                        '@status': response.status,
                    }));
                }

                this.isDirty = false;
                this.showSaveStatus(Drupal.t('Guardado correctamente'));
                setTimeout(() => this.hideSaveStatus(), 2500);
                return true;
            }
            catch (error) {
                this.showSaveStatus(Drupal.t('Error al guardar'), true);
                console.error('Canvas save error:', error);
                setTimeout(() => this.hideSaveStatus(), 4000);
                return false;
            }
        }

        /**
         * Publica la página del canvas.
         *
         * Primero guarda cambios pendientes si los hay, después envía
         * petición POST al endpoint de publicación. Al publicar, muestra
         * la URL pública resultante al usuario.
         */
        async publish() {
            // Guardar cambios pendientes antes de publicar.
            if (this.isDirty) {
                var saved = await this.save();
                if (!saved) {
                    return;
                }
            }

            this.showSaveStatus(Drupal.t('Publicando...'));

            try {
                const response = await fetch('/api/v1/pages/' + this.pageId + '/publish', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': drupalSettings.csrfToken || '',
                    },
                });

                if (!response.ok) {
                    throw new Error(Drupal.t('Error del servidor: @status', {
                        '@status': response.status,
                    }));
                }

                const data = await response.json();
                this.showSaveStatus(Drupal.t('Publicado correctamente'));

                // Mostrar URL pública si el backend la devuelve.
                if (data.url) {
                    this.showPublishUrl(data.url);
                }

                setTimeout(() => this.hideSaveStatus(), 3000);
            }
            catch (error) {
                this.showSaveStatus(Drupal.t('Error al publicar'), true);
                console.error('Canvas publish error:', error);
                setTimeout(() => this.hideSaveStatus(), 4000);
            }
        }

        /**
         * Muestra la URL pública después de publicar.
         *
         * Crea un enlace temporal bajo el status bar para que el usuario
         * pueda acceder directamente a la página publicada.
         *
         * @param {string} url - URL pública de la página.
         */
        showPublishUrl(url) {
            var existing = this.element.querySelector('.canvas-editor__publish-url');
            if (existing) {
                existing.remove();
            }

            var container = document.createElement('div');
            container.className = 'canvas-editor__publish-url';
            container.innerHTML = '<a href="' + url + '" target="_blank" rel="noopener">'
                + Drupal.t('Ver página publicada') + ' →</a>';

            if (this.saveStatus && this.saveStatus.parentNode) {
                this.saveStatus.parentNode.insertBefore(container, this.saveStatus.nextSibling);
            }

            // Auto-ocultar después de 10 segundos.
            setTimeout(function () {
                if (container.parentNode) {
                    container.remove();
                }
            }, 10000);
        }

        /**
         * Muestra estado de guardado.
         *
         * @param {string} message - Mensaje a mostrar.
         * @param {boolean} isError - Si es un error.
         */
        showSaveStatus(message, isError = false) {
            if (!this.saveStatus) return;

            const textEl = this.saveStatus.querySelector('.canvas-editor__status-text');
            if (textEl) textEl.textContent = message;

            this.saveStatus.hidden = false;
            this.saveStatus.classList.toggle('is-error', isError);
        }

        /**
         * Oculta estado de guardado.
         */
        hideSaveStatus() {
            if (this.saveStatus) {
                this.saveStatus.hidden = true;
            }
        }

    }

})(Drupal, drupalSettings, once);

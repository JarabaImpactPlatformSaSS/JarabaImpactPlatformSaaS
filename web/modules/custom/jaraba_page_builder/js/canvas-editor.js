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
         * Guarda cambios.
         */
        async save() {
            this.showSaveStatus(Drupal.t('Guardando...'));

            // TODO: Implementar guardado completo.
            setTimeout(() => {
                this.hideSaveStatus();
                this.isDirty = false;
            }, 1000);
        }

        /**
         * Publica la página.
         */
        async publish() {
            // TODO: Implementar publicación.
            console.log('Publicar página:', this.pageId);
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

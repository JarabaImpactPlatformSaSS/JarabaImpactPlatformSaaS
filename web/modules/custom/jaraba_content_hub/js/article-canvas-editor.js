/**
 * @file
 * Article Canvas Editor — Bridge JS para GrapesJS en artículos.
 *
 * PROPÓSITO:
 * Conecta el editor GrapesJS (engine del Page Builder) con los endpoints
 * API del Content Hub. Gestiona:
 * - Carga inicial de datos canvas del artículo
 * - Storage Manager custom apuntando a /api/v1/articles/{id}/canvas
 * - Save/Publish con feedback visual
 * - Viewport toggle
 * - Undo/Redo sincronizado con GrapesJS
 * - Slide-panel de metadatos
 *
 * REUTILIZACIÓN:
 * El engine GrapesJS se inicializa automáticamente por el behavior de
 * jaraba_page_builder/grapesjs-canvas al detectar [data-jaraba-canvas-container].
 * Este archivo solo reconfigura el StorageManager y los botones.
 *
 * DIRECTRICES:
 * - CSRF-JS-CACHE-001: Promise cacheada para token
 * - INNERHTML-XSS-001: Drupal.checkPlain() para texto dinámico
 * - ROUTE-LANGPREFIX-001: URLs via drupalSettings (no hardcoded)
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Article Canvas Editor behavior.
   */
  Drupal.behaviors.articleCanvasEditor = {
    attach(context) {
      once('article-canvas-editor', '.article-canvas-editor', context).forEach((editorEl) => {
        new ArticleCanvasEditor(editorEl);
      });
    },
  };

  /**
   * Clase principal del Article Canvas Editor.
   */
  class ArticleCanvasEditor {
    /**
     * @param {HTMLElement} element - Elemento raíz .article-canvas-editor
     */
    constructor(element) {
      this.element = element;
      this.config = drupalSettings.articleCanvasEditor || {};
      this.canvasConfig = drupalSettings.jarabaCanvas || {};
      this.articleId = this.config.articleId;
      this.csrfToken = this.canvasConfig.csrfToken || this.config.csrfToken || '';
      this.isDirty = false;

      // Referencias DOM.
      this.saveStatus = element.querySelector('#save-status');
      this.saveStatusText = this.saveStatus?.querySelector('.article-canvas-editor__save-status-text');

      this.init();
    }

    /**
     * Inicializa el editor.
     */
    init() {
      this.initViewportToggle();
      this.initSaveButtons();
      this.initMetaPanel();
      this.waitForGrapesJS();
    }

    /**
     * Espera a que GrapesJS esté listo y reconfigura el StorageManager.
     */
    waitForGrapesJS() {
      const maxAttempts = 50;
      let attempts = 0;

      const checkInterval = setInterval(() => {
        attempts++;

        if (window.jarabaCanvasEditor && window.jarabaCanvasEditor.editor) {
          clearInterval(checkInterval);
          this.gjsEditor = window.jarabaCanvasEditor.editor;
          this.onGrapesJSReady();
        } else if (attempts >= maxAttempts) {
          clearInterval(checkInterval);
          console.warn('[Article Canvas] GrapesJS no se inicializó después de', maxAttempts, 'intentos');
        }
      }, 200);
    }

    /**
     * Callback cuando GrapesJS está listo.
     * Reconfigura el StorageManager para apuntar a los endpoints de artículos.
     */
    onGrapesJSReady() {
      this.setupArticleStorageManager();
      this.loadCanvasData();
      this.setupUndoRedo();
      this.setupAutoSave();

      console.log('[Article Canvas] Editor listo para artículo', this.articleId);
    }

    /**
     * Configura el StorageManager custom para artículos.
     * Reemplaza el StorageManager del Page Builder con endpoints del Content Hub.
     */
    setupArticleStorageManager() {
      const self = this;

      this.gjsEditor.StorageManager.add('jaraba-article-rest', {
        async store(data) {
          try {
            const apiUrl = Drupal.url('api/v1/articles/' + self.articleId + '/canvas');
            const response = await fetch(apiUrl, {
              method: 'PATCH',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': self.csrfToken,
              },
              body: JSON.stringify({
                components: data.components,
                styles: data.styles,
                html: self.gjsEditor.getHtml(),
                css: self.gjsEditor.getCss(),
              }),
            });

            if (!response.ok) {
              throw new Error('Error al guardar: ' + response.status);
            }

            const result = await response.json();
            self.isDirty = false;
            self.showSaveStatus(Drupal.t('Guardado correctamente'));
            setTimeout(() => self.hideSaveStatus(), 2500);

            return result;
          } catch (error) {
            console.error('[Article Canvas] Error guardando:', error);
            self.showSaveStatus(Drupal.t('Error al guardar'), true);
            setTimeout(() => self.hideSaveStatus(), 4000);
            throw error;
          }
        },

        async load() {
          // La carga inicial se hace por separado en loadCanvasData().
          return {};
        },
      });

      this.gjsEditor.StorageManager.setCurrent('jaraba-article-rest');
    }

    /**
     * Carga datos canvas del artículo desde la API.
     */
    async loadCanvasData() {
      try {
        const apiUrl = Drupal.url('api/v1/articles/' + this.articleId + '/canvas');
        const response = await fetch(apiUrl, {
          headers: {
            'Content-Type': 'application/json',
          },
        });

        if (!response.ok) {
          console.warn('[Article Canvas] No se pudieron cargar datos:', response.status);
          return;
        }

        const data = await response.json();

        // Si hay componentes, cargarlos en el editor.
        if (data.components && data.components.length > 0) {
          this.gjsEditor.setComponents(data.components);
        } else if (data.html) {
          // Fallback: cargar HTML directo.
          this.gjsEditor.setComponents(data.html);
        }

        // Cargar estilos si existen.
        if (data.styles && data.styles.length > 0) {
          this.gjsEditor.setStyle(data.styles);
        } else if (data.css) {
          this.gjsEditor.setStyle(data.css);
        }

        // Ocultar loading skeleton.
        const skeleton = document.getElementById('gjs-loading-skeleton');
        if (skeleton) {
          skeleton.style.display = 'none';
        }
      } catch (error) {
        console.error('[Article Canvas] Error cargando datos:', error);
      }
    }

    /**
     * Configura undo/redo sincronizado con el UndoManager de GrapesJS.
     */
    setupUndoRedo() {
      const undoBtn = this.element.querySelector('#canvas-undo-btn');
      const redoBtn = this.element.querySelector('#canvas-redo-btn');
      const um = this.gjsEditor.UndoManager;

      if (undoBtn) {
        undoBtn.addEventListener('click', () => {
          um.undo();
          this.updateUndoRedoState(undoBtn, redoBtn, um);
        });
      }

      if (redoBtn) {
        redoBtn.addEventListener('click', () => {
          um.redo();
          this.updateUndoRedoState(undoBtn, redoBtn, um);
        });
      }

      // Escuchar cambios para actualizar estado de botones.
      this.gjsEditor.on('change:changesCount', () => {
        this.isDirty = true;
        this.updateUndoRedoState(undoBtn, redoBtn, um);
      });
    }

    /**
     * Actualiza el estado disabled/enabled de los botones undo/redo.
     */
    updateUndoRedoState(undoBtn, redoBtn, um) {
      if (undoBtn) undoBtn.disabled = !um.hasUndo();
      if (redoBtn) redoBtn.disabled = !um.hasRedo();
    }

    /**
     * Configura auto-save con debounce de 5 segundos.
     */
    setupAutoSave() {
      let autoSaveTimer = null;

      this.gjsEditor.on('change:changesCount', () => {
        if (autoSaveTimer) clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(async () => {
          if (this.isDirty) {
            await this.save();
          }
        }, 5000);
      });
    }

    /**
     * Inicializa viewport toggle.
     */
    initViewportToggle() {
      const trigger = this.element.querySelector('#viewport-dropdown-trigger');
      const panel = this.element.querySelector('#viewport-dropdown-panel');

      if (!trigger || !panel) return;

      // Toggle panel.
      trigger.addEventListener('click', () => {
        const isOpen = panel.getAttribute('aria-hidden') !== 'true';
        panel.setAttribute('aria-hidden', isOpen ? 'true' : 'false');
        trigger.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
      });

      // Cerrar al hacer click fuera.
      document.addEventListener('click', (e) => {
        if (!trigger.contains(e.target) && !panel.contains(e.target)) {
          panel.setAttribute('aria-hidden', 'true');
          trigger.setAttribute('aria-expanded', 'false');
        }
      });

      // Botones de viewport.
      const buttons = panel.querySelectorAll('.article-canvas-editor__viewport-btn');
      buttons.forEach((button) => {
        button.addEventListener('click', () => {
          const viewport = button.dataset.viewport;

          // Cambiar viewport en GrapesJS si está disponible.
          if (this.gjsEditor) {
            this.gjsEditor.setDevice(viewport);
          }

          // Actualizar estado activo.
          buttons.forEach((btn) => btn.classList.remove('is-active'));
          button.classList.add('is-active');

          // Actualizar label del trigger.
          const label = this.element.querySelector('#viewport-trigger-label');
          if (label) {
            label.textContent = button.title.match(/\((.+)\)/)?.[1] || viewport;
          }

          // Cerrar panel.
          panel.setAttribute('aria-hidden', 'true');
          trigger.setAttribute('aria-expanded', 'false');
        });
      });
    }

    /**
     * Inicializa botones de guardar/publicar/preview.
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
     * Inicializa el panel de metadatos (slide-panel).
     */
    initMetaPanel() {
      const toggleBtn = this.element.querySelector('#article-meta-toggle-btn');
      const panel = this.element.querySelector('#article-meta-panel');

      if (!toggleBtn || !panel) return;

      toggleBtn.addEventListener('click', () => {
        const isOpen = panel.getAttribute('aria-hidden') !== 'true';
        panel.setAttribute('aria-hidden', isOpen ? 'true' : 'false');
        panel.classList.toggle('is-open', !isOpen);
      });

      // Cerrar con botón X.
      const closeBtn = panel.querySelector('[data-slide-panel-close]');
      if (closeBtn) {
        closeBtn.addEventListener('click', () => {
          panel.setAttribute('aria-hidden', 'true');
          panel.classList.remove('is-open');
        });
      }
    }

    /**
     * Guarda cambios del canvas via GrapesJS StorageManager.
     */
    async save() {
      if (this.gjsEditor) {
        this.showSaveStatus(Drupal.t('Guardando...'));
        try {
          await this.gjsEditor.store();
          return true;
        } catch (error) {
          console.error('[Article Canvas] Error en save:', error);
          return false;
        }
      }
      return false;
    }

    /**
     * Publica el artículo: guarda canvas + cambia status a published.
     */
    async publish() {
      // Guardar cambios pendientes.
      if (this.isDirty) {
        const saved = await this.save();
        if (!saved) return;
      }

      this.showSaveStatus(Drupal.t('Publicando...'));

      try {
        const apiUrl = Drupal.url('api/v1/content/articles/' + this.articleId + '/publish');
        const response = await fetch(apiUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': this.csrfToken,
          },
        });

        if (!response.ok) {
          throw new Error('Error al publicar: ' + response.status);
        }

        this.showSaveStatus(Drupal.t('Publicado correctamente'));
        setTimeout(() => this.hideSaveStatus(), 3000);

        // Actualizar badge de estado.
        const badge = this.element.querySelector('.article-canvas-editor__status-badge');
        if (badge) {
          badge.textContent = 'published';
          badge.className = 'article-canvas-editor__status-badge article-canvas-editor__status-badge--published';
        }
      } catch (error) {
        console.error('[Article Canvas] Error publicando:', error);
        this.showSaveStatus(Drupal.t('Error al publicar'), true);
        setTimeout(() => this.hideSaveStatus(), 4000);
      }
    }

    /**
     * Muestra el indicador de estado de guardado.
     *
     * @param {string} message - Mensaje a mostrar.
     * @param {boolean} isError - Si es un error.
     */
    showSaveStatus(message, isError = false) {
      if (!this.saveStatus) return;
      this.saveStatus.hidden = false;
      if (this.saveStatusText) {
        this.saveStatusText.textContent = message;
      }
      this.saveStatus.classList.toggle('article-canvas-editor__save-status--error', isError);
    }

    /**
     * Oculta el indicador de estado.
     */
    hideSaveStatus() {
      if (this.saveStatus) {
        this.saveStatus.hidden = true;
      }
    }
  }

})(Drupal, drupalSettings, once);

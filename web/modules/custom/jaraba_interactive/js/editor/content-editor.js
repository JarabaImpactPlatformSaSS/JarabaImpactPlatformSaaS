/**
 * @file
 * Orquestador principal del editor de contenido interactivo.
 *
 * Estructura: Coordina los sub-editores por tipo de contenido,
 * gestiona el guardado via API REST, y sincroniza el estado
 * entre el arbol de estructura, el canvas y el preview.
 *
 * Logica: Carga dinamicamente el sub-editor correspondiente al
 * content_type del contenido. Cada sub-editor renderiza su interfaz
 * en el canvas y expone metodos getData() y setData(). El orquestador
 * maneja save, publish y preview globalmente.
 *
 * Sintaxis: Clase ContentEditor con Drupal.behaviors.
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Orquestador del editor de contenido interactivo.
   */
  Drupal.ContentEditor = class {
    constructor(container) {
      this.container = container;
      this.config = drupalSettings.jarabaInteractiveEditor || {};
      this.contentId = this.config.contentId;
      this.contentType = this.config.contentType;
      this.contentData = this.config.contentData || {};
      this.settings = this.config.settings || {};
      this.isDirty = false;
      this.subEditor = null;

      // Referencias DOM.
      this.canvas = container.querySelector('#editor-canvas');
      this.treePanel = container.querySelector('#content-tree');
      this.addButtons = container.querySelector('#add-buttons');
      this.settingsPanel = container.querySelector('#settings-panel');
      this.titleInput = container.querySelector('.interactive-editor__title-input');
      this.saveBtn = container.querySelector('.interactive-editor__save-btn');
      this.publishBtn = container.querySelector('.interactive-editor__publish-btn');
      this.previewBtn = container.querySelector('.interactive-editor__preview-btn');
      this.previewPanel = container.querySelector('#preview-panel');
      this.previewIframe = container.querySelector('#preview-iframe');
      this.previewClose = container.querySelector('.interactive-editor__preview-close');
      this.difficultySelect = container.querySelector('.interactive-editor__difficulty-select');

      this.init();
    }

    /**
     * Inicializa el editor y carga el sub-editor apropiado.
     */
    init() {
      // Listeners globales.
      this.saveBtn.addEventListener('click', () => this.save());
      this.publishBtn.addEventListener('click', () => this.togglePublish());
      this.previewBtn.addEventListener('click', () => this.togglePreview());
      this.titleInput.addEventListener('input', () => { this.isDirty = true; });
      this.difficultySelect.addEventListener('change', () => { this.isDirty = true; });

      if (this.previewClose) {
        this.previewClose.addEventListener('click', () => this.closePreview());
      }

      // Atajo de teclado Ctrl+S para guardar.
      document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
          e.preventDefault();
          this.save();
        }
      });

      // Cargar sub-editor segun tipo.
      this.loadSubEditor(this.contentType);

      // Renderizar settings globales.
      this.renderGlobalSettings();
    }

    /**
     * Carga el sub-editor correspondiente al tipo de contenido.
     *
     * @param {string} contentType - ID del tipo de contenido.
     */
    loadSubEditor(contentType) {
      const editorMap = {
        'question_set': Drupal.QuestionSetEditor,
        'interactive_video': Drupal.InteractiveVideoEditor,
        'course_presentation': Drupal.CoursePresentationEditor,
        'branching_scenario': Drupal.BranchingScenarioEditor,
        'drag_and_drop': Drupal.DragAndDropEditor,
        'essay': Drupal.EssayEditor,
      };

      const EditorClass = editorMap[contentType];
      if (EditorClass) {
        this.subEditor = new EditorClass(this.canvas, this.contentData, this.settings, this);
      }
      else {
        this.canvas.innerHTML = '<p class="interactive-editor__no-editor">' +
          Drupal.t('No hay editor disponible para el tipo: @type', { '@type': contentType }) +
          '</p>';
      }
    }

    /**
     * Renderiza los settings globales en el panel lateral.
     */
    renderGlobalSettings() {
      const schema = this.config.schema || {};
      const settingsSchema = schema.settings?.properties || {};

      let html = '';
      for (const [key, def] of Object.entries(settingsSchema)) {
        const value = this.settings[key] ?? def.default ?? '';
        const label = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());

        if (def.type === 'boolean') {
          html += '<label class="interactive-editor__setting">' +
            '<input type="checkbox" data-setting="' + key + '" ' + (value ? 'checked' : '') + '> ' +
            label + '</label>';
        }
        else if (def.type === 'integer' || def.type === 'number') {
          html += '<label class="interactive-editor__setting">' +
            label + '<input type="number" data-setting="' + key + '" value="' + value + '">' +
            '</label>';
        }
        else if (def.enum) {
          html += '<label class="interactive-editor__setting">' + label +
            '<select data-setting="' + key + '">';
          def.enum.forEach(opt => {
            html += '<option value="' + opt + '"' + (opt === value ? ' selected' : '') + '>' + opt + '</option>';
          });
          html += '</select></label>';
        }
      }

      this.settingsPanel.innerHTML = html;

      // Listeners para settings.
      this.settingsPanel.querySelectorAll('[data-setting]').forEach(input => {
        input.addEventListener('change', () => {
          const key = input.dataset.setting;
          if (input.type === 'checkbox') {
            this.settings[key] = input.checked;
          }
          else if (input.type === 'number') {
            this.settings[key] = parseInt(input.value, 10);
          }
          else {
            this.settings[key] = input.value;
          }
          this.isDirty = true;
        });
      });
    }

    /**
     * Guarda el contenido via API REST.
     */
    async save() {
      const data = this.subEditor ? this.subEditor.getData() : this.contentData;
      const title = this.titleInput.value.trim();
      const difficulty = this.difficultySelect.value;

      this.saveBtn.disabled = true;
      const btnText = this.saveBtn.querySelector('span');
      const originalText = btnText.textContent;
      btnText.textContent = Drupal.t('Guardando...');

      try {
        const response = await fetch(this.config.apiBaseUrl + '/' + this.contentId, {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: JSON.stringify({
            title: title,
            content_data: data,
            settings: this.settings,
            difficulty: difficulty,
          }),
        });

        if (!response.ok) {
          throw new Error(Drupal.t('Error al guardar'));
        }

        this.isDirty = false;
        btnText.textContent = Drupal.t('Guardado');
        setTimeout(() => { btnText.textContent = originalText; }, 2000);

        // Actualizar preview si esta abierto.
        if (this.previewPanel.style.display !== 'none') {
          this.refreshPreview();
        }
      }
      catch (error) {
        btnText.textContent = Drupal.t('Error');
        setTimeout(() => { btnText.textContent = originalText; }, 3000);
        console.error('Error al guardar:', error);
      }
      finally {
        this.saveBtn.disabled = false;
      }
    }

    /**
     * Alterna el estado de publicacion.
     */
    async togglePublish() {
      const newStatus = !this.config.status;

      try {
        const response = await fetch(this.config.apiBaseUrl + '/' + this.contentId + '/status', {
          method: 'PATCH',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: JSON.stringify({ status: newStatus ? 1 : 0 }),
        });

        if (!response.ok) throw new Error('Error');

        this.config.status = newStatus;
        this.publishBtn.classList.toggle('is-published', newStatus);
        const span = this.publishBtn.querySelector('span');
        span.textContent = newStatus ? Drupal.t('Publicado') : Drupal.t('Borrador');
      }
      catch (error) {
        console.error('Error al cambiar estado:', error);
      }
    }

    /**
     * Alterna el panel de preview.
     */
    togglePreview() {
      if (this.previewPanel.style.display === 'none') {
        this.previewPanel.style.display = 'flex';
        this.refreshPreview();
      }
      else {
        this.closePreview();
      }
    }

    /**
     * Refresca el iframe de preview.
     */
    refreshPreview() {
      this.previewIframe.src = this.config.previewUrl + '?_=' + Date.now();
    }

    /**
     * Cierra el panel de preview.
     */
    closePreview() {
      this.previewPanel.style.display = 'none';
      this.previewIframe.src = 'about:blank';
    }

    /**
     * Marca el editor como dirty (cambios sin guardar).
     */
    markDirty() {
      this.isDirty = true;
    }
  };

  /**
   * Behavior principal del editor.
   */
  Drupal.behaviors.interactiveContentEditor = {
    attach: function (context) {
      once('interactive-editor', '#interactive-editor', context).forEach(function (element) {
        element._editor = new Drupal.ContentEditor(element);
      });
    }
  };

})(Drupal, drupalSettings, once);

/**
 * @file
 * Sub-editor para videos interactivos (Interactive Video).
 *
 * Estructura: Gestiona la URL del video, imagen poster, lista de
 * capitulos con tiempos de inicio/fin, y lista de checkpoints con
 * timestamp, tipo (quiz, overlay, decision) y contenido asociado.
 * Cada seccion es colapsable e independiente.
 *
 * Logica: Los capitulos definen segmentos de navegacion; los
 * checkpoints definen puntos de interaccion. Al cambiar el tipo
 * de checkpoint se re-renderiza su panel de contenido. getData()
 * serializa toda la configuracion del video interactivo.
 *
 * Sintaxis: Clase InteractiveVideoEditor con Drupal.behaviors,
 * Drupal.t() para traducciones y metodos getData()/setData().
 *
 * AUDIT-CONS-N12: Migrated from IIFE to Drupal.behaviors for AJAX compatibility.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Behavior para registrar la clase InteractiveVideoEditor.
   *
   * AUDIT-CONS-N12: Drupal.behaviors ensures the class is available
   * after AJAX-loaded content. once() prevents re-registration.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.jarabaInteractiveVideoEditor = {
    attach: function (context) {
      // AUDIT-CONS-N12: Register class once, available for all AJAX contexts.
      once('jaraba-interactive-video-editor', 'body', context).forEach(function () {

        /**
         * Editor de video interactivo.
         *
         * @param {HTMLElement} container - Contenedor del canvas del editor.
         * @param {Object} contentData - Datos existentes del contenido.
         * @param {Object} settings - Configuraciones globales.
         * @param {Object} parentEditor - Referencia al orquestador padre.
         */
        Drupal.InteractiveVideoEditor = class {
          constructor(container, contentData, settings, parentEditor) {
            this.container = container;
            this.contentData = contentData || {};
            this.settings = settings || {};
            this.parentEditor = parentEditor;
            this.nextChapterId = 1;
            this.nextCheckpointId = 1;

            this.render();
          }

          /**
           * Renderiza la interfaz principal del editor de video.
           */
          render() {
            this.container.innerHTML = '';

            // Seccion de video URL y poster.
            var videoSection = document.createElement('div');
            videoSection.className = 'ive-section ive-section--video';
            videoSection.innerHTML =
              '<h3 class="ive-section__title">' + Drupal.t('Configuracion del video') + '</h3>' +
              '<label class="ive-field">' + Drupal.t('URL del video') +
                '<input type="url" class="ive-field__input ive-field__video-url" value="' + this.escapeHtml(this.contentData.video_url || '') + '" placeholder="https://...">' +
              '</label>' +
              '<label class="ive-field">' + Drupal.t('URL del poster') +
                '<input type="url" class="ive-field__input ive-field__poster-url" value="' + this.escapeHtml(this.contentData.poster_url || '') + '" placeholder="https://...">' +
              '</label>';
            this.container.appendChild(videoSection);

            // Listeners para campos de video.
            var self = this;
            videoSection.querySelector('.ive-field__video-url').addEventListener('input', function () {
              self.parentEditor.markDirty();
            });
            videoSection.querySelector('.ive-field__poster-url').addEventListener('input', function () {
              self.parentEditor.markDirty();
            });

            // Seccion de capitulos.
            var chaptersSection = document.createElement('div');
            chaptersSection.className = 'ive-section ive-section--chapters';
            chaptersSection.innerHTML =
              '<div class="ive-section__header">' +
                '<h3 class="ive-section__title">' + Drupal.t('Capitulos') + '</h3>' +
                '<button type="button" class="ive-section__add-btn ive-add-chapter">' + Drupal.t('Agregar capitulo') + '</button>' +
              '</div>';
            this.container.appendChild(chaptersSection);

            this.chapterListEl = document.createElement('div');
            this.chapterListEl.className = 'ive-chapter-list';
            chaptersSection.appendChild(this.chapterListEl);

            chaptersSection.querySelector('.ive-add-chapter').addEventListener('click', () => {
              this.addChapter();
            });

            // Seccion de checkpoints.
            var checkpointsSection = document.createElement('div');
            checkpointsSection.className = 'ive-section ive-section--checkpoints';
            checkpointsSection.innerHTML =
              '<div class="ive-section__header">' +
                '<h3 class="ive-section__title">' + Drupal.t('Checkpoints') + '</h3>' +
                '<button type="button" class="ive-section__add-btn ive-add-checkpoint">' + Drupal.t('Agregar checkpoint') + '</button>' +
              '</div>';
            this.container.appendChild(checkpointsSection);

            this.checkpointListEl = document.createElement('div');
            this.checkpointListEl.className = 'ive-checkpoint-list';
            checkpointsSection.appendChild(this.checkpointListEl);

            checkpointsSection.querySelector('.ive-add-checkpoint').addEventListener('click', () => {
              this.addCheckpoint();
            });

            // Cargar datos existentes.
            if (this.contentData.chapters || this.contentData.checkpoints) {
              this.setData(this.contentData);
            }
          }

          /**
           * Agrega un nuevo capitulo a la lista.
           *
           * @param {Object} data - Datos opcionales del capitulo.
           */
          addChapter(data) {
            var id = (data && data.id) || ('ch_' + this.nextChapterId++);
            var chapter = {
              id: id,
              title: (data && data.title) || '',
              start_time: (data && data.start_time) || 0,
              end_time: (data && data.end_time) || 0,
            };

            var row = document.createElement('div');
            row.className = 'ive-chapter';
            row.dataset.chapterId = chapter.id;
            row.innerHTML =
              '<div class="ive-chapter__fields">' +
                '<input type="text" class="ive-chapter__title" value="' + this.escapeHtml(chapter.title) + '" placeholder="' + Drupal.t('Titulo del capitulo') + '">' +
                '<input type="number" class="ive-chapter__start" min="0" step="0.1" value="' + chapter.start_time + '" placeholder="' + Drupal.t('Inicio (s)') + '">' +
                '<input type="number" class="ive-chapter__end" min="0" step="0.1" value="' + chapter.end_time + '" placeholder="' + Drupal.t('Fin (s)') + '">' +
              '</div>' +
              '<button type="button" class="ive-chapter__remove" title="' + Drupal.t('Eliminar capitulo') + '">&times;</button>';
            this.chapterListEl.appendChild(row);

            // Listeners.
            var self = this;
            row.querySelector('.ive-chapter__title').addEventListener('input', function () { self.parentEditor.markDirty(); });
            row.querySelector('.ive-chapter__start').addEventListener('input', function () { self.parentEditor.markDirty(); });
            row.querySelector('.ive-chapter__end').addEventListener('input', function () { self.parentEditor.markDirty(); });
            row.querySelector('.ive-chapter__remove').addEventListener('click', function () {
              row.remove();
              self.parentEditor.markDirty();
            });

            this.parentEditor.markDirty();
          }

          /**
           * Agrega un nuevo checkpoint a la lista.
           *
           * @param {Object} data - Datos opcionales del checkpoint.
           */
          addCheckpoint(data) {
            var id = (data && data.id) || ('cp_' + this.nextCheckpointId++);
            var checkpoint = {
              id: id,
              timestamp: (data && data.timestamp) || 0,
              type: (data && data.type) || 'quiz',
              content: (data && data.content) || {},
            };

            var panel = document.createElement('div');
            panel.className = 'ive-checkpoint';
            panel.dataset.checkpointId = checkpoint.id;
            panel.innerHTML =
              '<div class="ive-checkpoint__header">' +
                '<span class="ive-checkpoint__label">' + Drupal.t('Checkpoint') + ' ' + checkpoint.id + '</span>' +
                '<button type="button" class="ive-checkpoint__remove" title="' + Drupal.t('Eliminar checkpoint') + '">&times;</button>' +
              '</div>' +
              '<div class="ive-checkpoint__fields">' +
                '<label class="ive-field">' + Drupal.t('Timestamp (segundos)') +
                  '<input type="number" class="ive-checkpoint__timestamp" min="0" step="0.1" value="' + checkpoint.timestamp + '">' +
                '</label>' +
                '<label class="ive-field">' + Drupal.t('Tipo') +
                  '<select class="ive-checkpoint__type">' +
                    '<option value="quiz"' + (checkpoint.type === 'quiz' ? ' selected' : '') + '>' + Drupal.t('Quiz') + '</option>' +
                    '<option value="overlay"' + (checkpoint.type === 'overlay' ? ' selected' : '') + '>' + Drupal.t('Overlay informativo') + '</option>' +
                    '<option value="decision"' + (checkpoint.type === 'decision' ? ' selected' : '') + '>' + Drupal.t('Decision') + '</option>' +
                  '</select>' +
                '</label>' +
              '</div>' +
              '<div class="ive-checkpoint__content-area"></div>';
            this.checkpointListEl.appendChild(panel);

            // Renderizar contenido segun tipo.
            this.renderCheckpointContent(panel, checkpoint);

            // Listeners.
            var self = this;
            panel.querySelector('.ive-checkpoint__timestamp').addEventListener('input', function () { self.parentEditor.markDirty(); });
            panel.querySelector('.ive-checkpoint__type').addEventListener('change', function (e) {
              checkpoint.type = e.target.value;
              checkpoint.content = {};
              self.renderCheckpointContent(panel, checkpoint);
              self.parentEditor.markDirty();
            });
            panel.querySelector('.ive-checkpoint__remove').addEventListener('click', function () {
              panel.remove();
              self.parentEditor.markDirty();
            });

            this.parentEditor.markDirty();
          }

          /**
           * Renderiza el area de contenido de un checkpoint segun su tipo.
           *
           * @param {HTMLElement} panel - Panel del checkpoint.
           * @param {Object} checkpoint - Datos del checkpoint.
           */
          renderCheckpointContent(panel, checkpoint) {
            var area = panel.querySelector('.ive-checkpoint__content-area');
            area.innerHTML = '';
            var content = checkpoint.content || {};
            var self = this;

            if (checkpoint.type === 'quiz') {
              area.innerHTML =
                '<label class="ive-field">' + Drupal.t('Pregunta') +
                  '<input type="text" class="ive-cp-question" value="' + this.escapeHtml(content.question || '') + '">' +
                '</label>' +
                '<div class="ive-cp-options"></div>' +
                '<button type="button" class="ive-cp-add-option">' + Drupal.t('Agregar opcion') + '</button>' +
                '<label class="ive-field">' + Drupal.t('Respuesta correcta (ID de opcion)') +
                  '<input type="text" class="ive-cp-correct" value="' + this.escapeHtml(content.correct_answer || '') + '">' +
                '</label>';

              var optContainer = area.querySelector('.ive-cp-options');
              (content.options || []).forEach(function (opt) {
                self.addCheckpointOption(optContainer, opt);
              });

              area.querySelector('.ive-cp-add-option').addEventListener('click', function () {
                self.addCheckpointOption(optContainer, { id: '', text: '' });
                self.parentEditor.markDirty();
              });
              area.querySelector('.ive-cp-question').addEventListener('input', function () { self.parentEditor.markDirty(); });
              area.querySelector('.ive-cp-correct').addEventListener('input', function () { self.parentEditor.markDirty(); });
            }
            else if (checkpoint.type === 'overlay') {
              area.innerHTML =
                '<label class="ive-field">' + Drupal.t('Texto') +
                  '<textarea class="ive-cp-text" rows="3">' + this.escapeHtml(content.text || '') + '</textarea>' +
                '</label>' +
                '<label class="ive-field">' + Drupal.t('URL de imagen') +
                  '<input type="url" class="ive-cp-image" value="' + this.escapeHtml(content.image_url || '') + '">' +
                '</label>';
              area.querySelector('.ive-cp-text').addEventListener('input', function () { self.parentEditor.markDirty(); });
              area.querySelector('.ive-cp-image').addEventListener('input', function () { self.parentEditor.markDirty(); });
            }
            else if (checkpoint.type === 'decision') {
              area.innerHTML =
                '<label class="ive-field">' + Drupal.t('Texto de la decision') +
                  '<textarea class="ive-cp-text" rows="2">' + this.escapeHtml(content.text || '') + '</textarea>' +
                '</label>' +
                '<div class="ive-cp-options"></div>' +
                '<button type="button" class="ive-cp-add-option">' + Drupal.t('Agregar opcion') + '</button>';

              var decContainer = area.querySelector('.ive-cp-options');
              (content.options || []).forEach(function (opt) {
                self.addCheckpointOption(decContainer, opt);
              });

              area.querySelector('.ive-cp-add-option').addEventListener('click', function () {
                self.addCheckpointOption(decContainer, { id: '', text: '' });
                self.parentEditor.markDirty();
              });
              area.querySelector('.ive-cp-text').addEventListener('input', function () { self.parentEditor.markDirty(); });
            }
          }

          /**
           * Agrega una fila de opcion dentro de un checkpoint.
           *
           * @param {HTMLElement} container - Contenedor de opciones.
           * @param {Object} opt - Datos de la opcion.
           */
          addCheckpointOption(container, opt) {
            var row = document.createElement('div');
            row.className = 'ive-cp-option-row';
            row.innerHTML =
              '<input type="text" class="ive-cp-option-id" value="' + this.escapeHtml(opt.id || '') + '" placeholder="' + Drupal.t('ID') + '">' +
              '<input type="text" class="ive-cp-option-text" value="' + this.escapeHtml(opt.text || '') + '" placeholder="' + Drupal.t('Texto de opcion') + '">' +
              '<button type="button" class="ive-cp-option-remove" title="' + Drupal.t('Eliminar') + '">&times;</button>';
            container.appendChild(row);

            var self = this;
            row.querySelector('.ive-cp-option-id').addEventListener('input', function () { self.parentEditor.markDirty(); });
            row.querySelector('.ive-cp-option-text').addEventListener('input', function () { self.parentEditor.markDirty(); });
            row.querySelector('.ive-cp-option-remove').addEventListener('click', function () {
              row.remove();
              self.parentEditor.markDirty();
            });
          }

          /**
           * Recolecta los datos actuales del editor de video.
           *
           * @return {Object} Estructura con video_url, poster_url, chapters, checkpoints, settings.
           */
          getData() {
            var videoUrl = this.container.querySelector('.ive-field__video-url').value;
            var posterUrl = this.container.querySelector('.ive-field__poster-url').value;

            // Recolectar capitulos.
            var chapters = [];
            this.chapterListEl.querySelectorAll('.ive-chapter').forEach(function (row) {
              chapters.push({
                id: row.dataset.chapterId,
                title: row.querySelector('.ive-chapter__title').value,
                start_time: parseFloat(row.querySelector('.ive-chapter__start').value) || 0,
                end_time: parseFloat(row.querySelector('.ive-chapter__end').value) || 0,
              });
            });

            // Recolectar checkpoints.
            var checkpoints = [];
            var self = this;
            this.checkpointListEl.querySelectorAll('.ive-checkpoint').forEach(function (panel) {
              var type = panel.querySelector('.ive-checkpoint__type').value;
              var cp = {
                id: panel.dataset.checkpointId,
                timestamp: parseFloat(panel.querySelector('.ive-checkpoint__timestamp').value) || 0,
                type: type,
                content: self.getCheckpointContent(panel, type),
              };
              checkpoints.push(cp);
            });

            return {
              video_url: videoUrl,
              poster_url: posterUrl,
              chapters: chapters,
              checkpoints: checkpoints,
              settings: this.settings,
            };
          }

          /**
           * Extrae el contenido de un panel de checkpoint segun su tipo.
           *
           * @param {HTMLElement} panel - Panel del checkpoint.
           * @param {string} type - Tipo del checkpoint.
           * @return {Object} Contenido del checkpoint.
           */
          getCheckpointContent(panel, type) {
            var area = panel.querySelector('.ive-checkpoint__content-area');
            var content = {};

            if (type === 'quiz') {
              content.question = (area.querySelector('.ive-cp-question') || {}).value || '';
              content.correct_answer = (area.querySelector('.ive-cp-correct') || {}).value || '';
              content.options = [];
              area.querySelectorAll('.ive-cp-option-row').forEach(function (row) {
                content.options.push({
                  id: row.querySelector('.ive-cp-option-id').value,
                  text: row.querySelector('.ive-cp-option-text').value,
                });
              });
            }
            else if (type === 'overlay') {
              content.text = (area.querySelector('.ive-cp-text') || {}).value || '';
              content.image_url = (area.querySelector('.ive-cp-image') || {}).value || '';
            }
            else if (type === 'decision') {
              content.text = (area.querySelector('.ive-cp-text') || {}).value || '';
              content.options = [];
              area.querySelectorAll('.ive-cp-option-row').forEach(function (row) {
                content.options.push({
                  id: row.querySelector('.ive-cp-option-id').value,
                  text: row.querySelector('.ive-cp-option-text').value,
                });
              });
            }

            return content;
          }

          /**
           * Carga datos existentes en el editor.
           *
           * @param {Object} data - Datos del video interactivo.
           */
          setData(data) {
            if (data.video_url) {
              this.container.querySelector('.ive-field__video-url').value = data.video_url;
            }
            if (data.poster_url) {
              this.container.querySelector('.ive-field__poster-url').value = data.poster_url;
            }

            this.chapterListEl.innerHTML = '';
            if (data.chapters && Array.isArray(data.chapters)) {
              data.chapters.forEach((ch) => {
                this.addChapter(ch);
              });
            }

            this.checkpointListEl.innerHTML = '';
            if (data.checkpoints && Array.isArray(data.checkpoints)) {
              data.checkpoints.forEach((cp) => {
                this.addCheckpoint(cp);
              });
            }

            if (data.settings) {
              this.settings = data.settings;
            }
          }

          /**
           * Escapa caracteres HTML para prevenir inyeccion.
           *
           * @param {string} str - Cadena a escapar.
           * @return {string} Cadena escapada.
           */
          escapeHtml(str) {
            if (!str) return '';
            return String(str)
              .replace(/&/g, '&amp;')
              .replace(/</g, '&lt;')
              .replace(/>/g, '&gt;')
              .replace(/"/g, '&quot;');
          }
        };

      });
    }
  };

})(Drupal, once);

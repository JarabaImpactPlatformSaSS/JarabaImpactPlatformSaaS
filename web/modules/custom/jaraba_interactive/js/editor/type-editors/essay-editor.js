/**
 * @file
 * Sub-editor para ensayos (Essay).
 *
 * Estructura: Gestiona el prompt del ensayo, instrucciones, rubrica
 * con criterios de evaluacion (cada uno con niveles de desempeno),
 * y material de referencia. La rubrica es una lista dinamica de
 * criterios, cada uno con su propia lista de niveles.
 *
 * Logica: Los criterios de la rubrica definen como se evalua el
 * ensayo. Cada criterio tiene descripcion, puntaje maximo, peso
 * y niveles con etiqueta y puntaje. getData() serializa prompt,
 * instrucciones, rubrica completa y material de referencia.
 *
 * Sintaxis: Clase EssayEditor con Drupal.behaviors, Drupal.t() para
 * cadenas traducibles y metodos getData()/setData().
 *
 * AUDIT-CONS-N12: Migrated from IIFE to Drupal.behaviors for AJAX compatibility.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Behavior para registrar la clase EssayEditor.
   *
   * AUDIT-CONS-N12: Drupal.behaviors ensures the class is available
   * after AJAX-loaded content. once() prevents re-registration.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.jarabaEssayEditor = {
    attach: function (context) {
      // AUDIT-CONS-N12: Register class once, available for all AJAX contexts.
      once('jaraba-essay-editor', 'body', context).forEach(function () {

        /**
         * Editor de ensayos.
         *
         * @param {HTMLElement} container - Contenedor del canvas del editor.
         * @param {Object} contentData - Datos existentes del contenido.
         * @param {Object} settings - Configuraciones globales.
         * @param {Object} parentEditor - Referencia al orquestador padre.
         */
        Drupal.EssayEditor = class {
          constructor(container, contentData, settings, parentEditor) {
            this.container = container;
            this.contentData = contentData || {};
            this.settings = settings || {};
            this.parentEditor = parentEditor;
            this.nextCriterionId = 1;

            this.render();
          }

          /**
           * Renderiza la interfaz principal del editor de ensayos.
           */
          render() {
            this.container.innerHTML = '';

            // Seccion de prompt.
            var promptSection = document.createElement('div');
            promptSection.className = 'ese-section ese-section--prompt';
            promptSection.innerHTML =
              '<h3 class="ese-section__title">' + Drupal.t('Prompt del ensayo') + '</h3>' +
              '<label class="ese-field">' + Drupal.t('Prompt') +
                '<textarea class="ese-field__textarea ese-prompt" rows="4" placeholder="' + Drupal.t('Escriba el tema o pregunta del ensayo...') + '">' +
                  this.escapeHtml(this.contentData.prompt || '') +
                '</textarea>' +
              '</label>';
            this.container.appendChild(promptSection);

            promptSection.querySelector('.ese-prompt').addEventListener('input', () => {
              this.parentEditor.markDirty();
            });

            // Seccion de instrucciones.
            var instructionsSection = document.createElement('div');
            instructionsSection.className = 'ese-section ese-section--instructions';
            instructionsSection.innerHTML =
              '<h3 class="ese-section__title">' + Drupal.t('Instrucciones') + '</h3>' +
              '<label class="ese-field">' + Drupal.t('Instrucciones para el estudiante') +
                '<textarea class="ese-field__textarea ese-instructions" rows="4" placeholder="' + Drupal.t('Detalle las instrucciones y requisitos...') + '">' +
                  this.escapeHtml(this.contentData.instructions || '') +
                '</textarea>' +
              '</label>';
            this.container.appendChild(instructionsSection);

            instructionsSection.querySelector('.ese-instructions').addEventListener('input', () => {
              this.parentEditor.markDirty();
            });

            // Seccion de rubrica.
            var rubricSection = document.createElement('div');
            rubricSection.className = 'ese-section ese-section--rubric';
            rubricSection.innerHTML =
              '<div class="ese-section__header">' +
                '<h3 class="ese-section__title">' + Drupal.t('Rubrica de evaluacion') + '</h3>' +
                '<button type="button" class="ese-section__add-btn ese-add-criterion">' + Drupal.t('Agregar criterio') + '</button>' +
              '</div>';
            this.container.appendChild(rubricSection);

            this.rubricListEl = document.createElement('div');
            this.rubricListEl.className = 'ese-rubric-list';
            rubricSection.appendChild(this.rubricListEl);

            rubricSection.querySelector('.ese-add-criterion').addEventListener('click', () => {
              this.addCriterion();
            });

            // Seccion de material de referencia.
            var refSection = document.createElement('div');
            refSection.className = 'ese-section ese-section--reference';
            refSection.innerHTML =
              '<h3 class="ese-section__title">' + Drupal.t('Material de referencia') + '</h3>' +
              '<label class="ese-field">' + Drupal.t('Material de referencia (opcional)') +
                '<textarea class="ese-field__textarea ese-reference" rows="4" placeholder="' + Drupal.t('Textos, enlaces o recursos de apoyo...') + '">' +
                  this.escapeHtml(this.contentData.reference_material || '') +
                '</textarea>' +
              '</label>';
            this.container.appendChild(refSection);

            refSection.querySelector('.ese-reference').addEventListener('input', () => {
              this.parentEditor.markDirty();
            });

            // Cargar datos existentes de rubrica.
            if (this.contentData.rubric && this.contentData.rubric.length > 0) {
              this.contentData.rubric.forEach((c) => {
                this.addCriterion(c);
              });
            }
          }

          /**
           * Agrega un nuevo criterio a la rubrica.
           *
           * @param {Object} data - Datos opcionales del criterio.
           */
          addCriterion(data) {
            var id = (data && data.id) || ('crit_' + this.nextCriterionId++);
            var criterion = {
              id: id,
              criterion: (data && data.criterion) || '',
              description: (data && data.description) || '',
              max_points: (data && data.max_points) || 10,
              weight: (data && data.weight) || 1,
              levels: (data && data.levels) || [],
            };

            var panel = document.createElement('div');
            panel.className = 'ese-criterion';
            panel.dataset.criterionId = criterion.id;

            panel.innerHTML =
              '<div class="ese-criterion__header">' +
                '<span class="ese-criterion__id">' + Drupal.t('Criterio: @id', { '@id': criterion.id }) + '</span>' +
                '<button type="button" class="ese-criterion__remove" title="' + Drupal.t('Eliminar criterio') + '">&times;</button>' +
              '</div>' +
              '<div class="ese-criterion__body">' +
                '<label class="ese-field">' + Drupal.t('Nombre del criterio') +
                  '<input type="text" class="ese-criterion__name" value="' + this.escapeHtml(criterion.criterion) + '" placeholder="' + Drupal.t('Ej: Coherencia argumentativa') + '">' +
                '</label>' +
                '<label class="ese-field">' + Drupal.t('Descripcion') +
                  '<textarea class="ese-criterion__description" rows="2" placeholder="' + Drupal.t('Describa que se evalua en este criterio...') + '">' +
                    this.escapeHtml(criterion.description) +
                  '</textarea>' +
                '</label>' +
                '<div class="ese-criterion__row">' +
                  '<label class="ese-field">' + Drupal.t('Puntaje maximo') +
                    '<input type="number" class="ese-criterion__max-points" min="1" value="' + criterion.max_points + '">' +
                  '</label>' +
                  '<label class="ese-field">' + Drupal.t('Peso') +
                    '<input type="number" class="ese-criterion__weight" min="0" step="0.1" value="' + criterion.weight + '">' +
                  '</label>' +
                '</div>' +
                '<div class="ese-criterion__levels-section">' +
                  '<div class="ese-criterion__levels-header">' +
                    '<h4 class="ese-criterion__levels-title">' + Drupal.t('Niveles de desempeno') + '</h4>' +
                    '<button type="button" class="ese-criterion__add-level">' + Drupal.t('Agregar nivel') + '</button>' +
                  '</div>' +
                  '<div class="ese-criterion__levels-list"></div>' +
                '</div>' +
              '</div>';

            this.rubricListEl.appendChild(panel);

            // Renderizar niveles existentes.
            var levelsContainer = panel.querySelector('.ese-criterion__levels-list');
            if (criterion.levels.length > 0) {
              criterion.levels.forEach((level) => {
                this.addLevel(levelsContainer, level);
              });
            }

            // Listeners.
            var self = this;
            panel.querySelector('.ese-criterion__remove').addEventListener('click', function () {
              panel.remove();
              self.parentEditor.markDirty();
            });

            panel.querySelector('.ese-criterion__add-level').addEventListener('click', function () {
              self.addLevel(levelsContainer, {});
              self.parentEditor.markDirty();
            });

            // Listeners de cambio en campos del criterio.
            var fields = panel.querySelectorAll('.ese-criterion__name, .ese-criterion__description, .ese-criterion__max-points, .ese-criterion__weight');
            fields.forEach(function (field) {
              field.addEventListener('input', function () { self.parentEditor.markDirty(); });
            });

            this.parentEditor.markDirty();
          }

          /**
           * Agrega un nivel de desempeno a un criterio.
           *
           * @param {HTMLElement} container - Contenedor de niveles del criterio.
           * @param {Object} data - Datos opcionales del nivel.
           */
          addLevel(container, data) {
            var level = {
              label: (data && data.label) || '',
              description: (data && data.description) || '',
              points: (data && data.points) || 0,
            };

            var row = document.createElement('div');
            row.className = 'ese-level';
            row.innerHTML =
              '<input type="text" class="ese-level__label" value="' + this.escapeHtml(level.label) + '" placeholder="' + Drupal.t('Ej: Excelente') + '">' +
              '<input type="text" class="ese-level__description" value="' + this.escapeHtml(level.description) + '" placeholder="' + Drupal.t('Descripcion del nivel') + '">' +
              '<input type="number" class="ese-level__points" min="0" value="' + level.points + '" placeholder="' + Drupal.t('Pts') + '" title="' + Drupal.t('Puntos para este nivel') + '">' +
              '<button type="button" class="ese-level__remove" title="' + Drupal.t('Eliminar nivel') + '">&times;</button>';
            container.appendChild(row);

            var self = this;
            row.querySelector('.ese-level__label').addEventListener('input', function () { self.parentEditor.markDirty(); });
            row.querySelector('.ese-level__description').addEventListener('input', function () { self.parentEditor.markDirty(); });
            row.querySelector('.ese-level__points').addEventListener('input', function () { self.parentEditor.markDirty(); });
            row.querySelector('.ese-level__remove').addEventListener('click', function () {
              row.remove();
              self.parentEditor.markDirty();
            });
          }

          /**
           * Recolecta los datos actuales del editor de ensayos.
           *
           * @return {Object} Estructura con prompt, instructions, rubric, reference_material, settings.
           */
          getData() {
            var prompt = this.container.querySelector('.ese-prompt').value;
            var instructions = this.container.querySelector('.ese-instructions').value;
            var referenceMaterial = this.container.querySelector('.ese-reference').value;

            var rubric = [];
            this.rubricListEl.querySelectorAll('.ese-criterion').forEach(function (panel) {
              var levels = [];
              panel.querySelectorAll('.ese-level').forEach(function (row) {
                levels.push({
                  label: row.querySelector('.ese-level__label').value,
                  description: row.querySelector('.ese-level__description').value,
                  points: parseInt(row.querySelector('.ese-level__points').value, 10) || 0,
                });
              });

              rubric.push({
                id: panel.dataset.criterionId,
                criterion: panel.querySelector('.ese-criterion__name').value,
                description: panel.querySelector('.ese-criterion__description').value,
                max_points: parseInt(panel.querySelector('.ese-criterion__max-points').value, 10) || 10,
                weight: parseFloat(panel.querySelector('.ese-criterion__weight').value) || 1,
                levels: levels,
              });
            });

            return {
              prompt: prompt,
              instructions: instructions,
              rubric: rubric,
              reference_material: referenceMaterial,
              settings: this.settings,
            };
          }

          /**
           * Carga datos existentes en el editor.
           *
           * @param {Object} data - Datos del ensayo.
           */
          setData(data) {
            if (data.prompt) {
              this.container.querySelector('.ese-prompt').value = data.prompt;
            }
            if (data.instructions) {
              this.container.querySelector('.ese-instructions').value = data.instructions;
            }
            if (data.reference_material) {
              this.container.querySelector('.ese-reference').value = data.reference_material;
            }

            this.rubricListEl.innerHTML = '';
            this.nextCriterionId = 1;

            if (data.rubric && Array.isArray(data.rubric)) {
              data.rubric.forEach((c) => {
                var numMatch = String(c.id).match(/(\d+)$/);
                if (numMatch) {
                  var num = parseInt(numMatch[1], 10) + 1;
                  if (num > this.nextCriterionId) this.nextCriterionId = num;
                }
                this.addCriterion(c);
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

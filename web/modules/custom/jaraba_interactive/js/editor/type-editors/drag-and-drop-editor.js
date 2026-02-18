/**
 * @file
 * Sub-editor para actividades de arrastrar y soltar (Drag and Drop).
 *
 * Estructura: Gestiona zonas de drop (con ID, etiqueta, posicion,
 * flag de multiples y pista), items arrastrables (con ID, texto,
 * imagen, zonas correctas y feedback), y una imagen de fondo.
 * Cada seccion tiene su propio panel con agregar/eliminar.
 *
 * Logica: Las zonas de drop definen los destinos validos; los items
 * arrastrables referencian zonas correctas por ID. Al agregar o
 * eliminar zonas se actualizan los selectores de zonas correctas
 * en los items. getData() recolecta zonas, items, fondo y settings.
 *
 * Sintaxis: Clase DragAndDropEditor con Drupal.behaviors, Drupal.t()
 * para cadenas traducibles y metodos getData()/setData().
 *
 * AUDIT-CONS-N12: Migrated from IIFE to Drupal.behaviors for AJAX compatibility.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Behavior para registrar la clase DragAndDropEditor.
   *
   * AUDIT-CONS-N12: Drupal.behaviors ensures the class is available
   * after AJAX-loaded content. once() prevents re-registration.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.jarabaDragAndDropEditor = {
    attach: function (context) {
      // AUDIT-CONS-N12: Register class once, available for all AJAX contexts.
      once('jaraba-drag-and-drop-editor', 'body', context).forEach(function () {

        /**
         * Editor de arrastrar y soltar.
         *
         * @param {HTMLElement} container - Contenedor del canvas del editor.
         * @param {Object} contentData - Datos existentes del contenido.
         * @param {Object} settings - Configuraciones globales.
         * @param {Object} parentEditor - Referencia al orquestador padre.
         */
        Drupal.DragAndDropEditor = class {
          constructor(container, contentData, settings, parentEditor) {
            this.container = container;
            this.contentData = contentData || {};
            this.settings = settings || {};
            this.parentEditor = parentEditor;
            this.nextZoneId = 1;
            this.nextDraggableId = 1;

            this.render();
          }

          /**
           * Renderiza la interfaz principal del editor de drag and drop.
           */
          render() {
            this.container.innerHTML = '';

            // Seccion de imagen de fondo.
            var bgSection = document.createElement('div');
            bgSection.className = 'dde-section dde-section--background';
            bgSection.innerHTML =
              '<h3 class="dde-section__title">' + Drupal.t('Imagen de fondo') + '</h3>' +
              '<label class="dde-field">' + Drupal.t('URL de la imagen de fondo') +
                '<input type="url" class="dde-field__input dde-bg-image" value="' + this.escapeHtml(this.contentData.background_image || '') + '" placeholder="https://...">' +
              '</label>';
            this.container.appendChild(bgSection);

            bgSection.querySelector('.dde-bg-image').addEventListener('input', () => {
              this.parentEditor.markDirty();
            });

            // Seccion de zonas de drop.
            var zonesSection = document.createElement('div');
            zonesSection.className = 'dde-section dde-section--zones';
            zonesSection.innerHTML =
              '<div class="dde-section__header">' +
                '<h3 class="dde-section__title">' + Drupal.t('Zonas de destino') + '</h3>' +
                '<button type="button" class="dde-section__add-btn dde-add-zone">' + Drupal.t('Agregar zona') + '</button>' +
              '</div>';
            this.container.appendChild(zonesSection);

            this.zoneListEl = document.createElement('div');
            this.zoneListEl.className = 'dde-zone-list';
            zonesSection.appendChild(this.zoneListEl);

            zonesSection.querySelector('.dde-add-zone').addEventListener('click', () => {
              this.addZone();
            });

            // Seccion de items arrastrables.
            var itemsSection = document.createElement('div');
            itemsSection.className = 'dde-section dde-section--draggables';
            itemsSection.innerHTML =
              '<div class="dde-section__header">' +
                '<h3 class="dde-section__title">' + Drupal.t('Items arrastrables') + '</h3>' +
                '<button type="button" class="dde-section__add-btn dde-add-draggable">' + Drupal.t('Agregar item') + '</button>' +
              '</div>';
            this.container.appendChild(itemsSection);

            this.draggableListEl = document.createElement('div');
            this.draggableListEl.className = 'dde-draggable-list';
            itemsSection.appendChild(this.draggableListEl);

            itemsSection.querySelector('.dde-add-draggable').addEventListener('click', () => {
              this.addDraggable();
            });

            // Cargar datos existentes.
            if (this.contentData.drop_zones || this.contentData.draggables) {
              this.setData(this.contentData);
            }
          }

          /**
           * Agrega una nueva zona de drop.
           *
           * @param {Object} data - Datos opcionales de la zona.
           */
          addZone(data) {
            var id = (data && data.id) || ('zone_' + this.nextZoneId++);
            var zone = {
              id: id,
              label: (data && data.label) || '',
              position: (data && data.position) || { x: 0, y: 0, width: 100, height: 100 },
              accepts_multiple: (data && data.accepts_multiple) || false,
              hint: (data && data.hint) || '',
            };

            var panel = document.createElement('div');
            panel.className = 'dde-zone';
            panel.dataset.zoneId = zone.id;
            panel.innerHTML =
              '<div class="dde-zone__header">' +
                '<span class="dde-zone__id">' + Drupal.t('Zona: @id', { '@id': zone.id }) + '</span>' +
                '<button type="button" class="dde-zone__remove" title="' + Drupal.t('Eliminar zona') + '">&times;</button>' +
              '</div>' +
              '<div class="dde-zone__body">' +
                '<label class="dde-field">' + Drupal.t('Etiqueta') +
                  '<input type="text" class="dde-zone__label" value="' + this.escapeHtml(zone.label) + '">' +
                '</label>' +
                '<div class="dde-zone__position-row">' +
                  '<label class="dde-field dde-field--small">' + Drupal.t('X') +
                    '<input type="number" class="dde-zone__pos-x" min="0" value="' + zone.position.x + '">' +
                  '</label>' +
                  '<label class="dde-field dde-field--small">' + Drupal.t('Y') +
                    '<input type="number" class="dde-zone__pos-y" min="0" value="' + zone.position.y + '">' +
                  '</label>' +
                  '<label class="dde-field dde-field--small">' + Drupal.t('Ancho') +
                    '<input type="number" class="dde-zone__pos-w" min="1" value="' + zone.position.width + '">' +
                  '</label>' +
                  '<label class="dde-field dde-field--small">' + Drupal.t('Alto') +
                    '<input type="number" class="dde-zone__pos-h" min="1" value="' + zone.position.height + '">' +
                  '</label>' +
                '</div>' +
                '<label class="dde-field dde-field--inline">' +
                  '<input type="checkbox" class="dde-zone__accepts-multiple"' + (zone.accepts_multiple ? ' checked' : '') + '> ' +
                  Drupal.t('Acepta multiples items') +
                '</label>' +
                '<label class="dde-field">' + Drupal.t('Pista') +
                  '<input type="text" class="dde-zone__hint" value="' + this.escapeHtml(zone.hint) + '">' +
                '</label>' +
              '</div>';
            this.zoneListEl.appendChild(panel);

            // Listeners.
            var self = this;
            panel.querySelector('.dde-zone__remove').addEventListener('click', function () {
              panel.remove();
              self.refreshZoneSelectors();
              self.parentEditor.markDirty();
            });

            var inputs = panel.querySelectorAll('input');
            inputs.forEach(function (input) {
              input.addEventListener('input', function () { self.parentEditor.markDirty(); });
              input.addEventListener('change', function () { self.parentEditor.markDirty(); });
            });

            this.refreshZoneSelectors();
            this.parentEditor.markDirty();
          }

          /**
           * Agrega un nuevo item arrastrable.
           *
           * @param {Object} data - Datos opcionales del item.
           */
          addDraggable(data) {
            var id = (data && data.id) || ('drag_' + this.nextDraggableId++);
            var item = {
              id: id,
              text: (data && data.text) || '',
              image_url: (data && data.image_url) || '',
              correct_zones: (data && data.correct_zones) || [],
              feedback_correct: (data && data.feedback_correct) || '',
              feedback_incorrect: (data && data.feedback_incorrect) || '',
            };

            var panel = document.createElement('div');
            panel.className = 'dde-draggable';
            panel.dataset.draggableId = item.id;
            panel.innerHTML =
              '<div class="dde-draggable__header">' +
                '<span class="dde-draggable__id">' + Drupal.t('Item: @id', { '@id': item.id }) + '</span>' +
                '<button type="button" class="dde-draggable__remove" title="' + Drupal.t('Eliminar item') + '">&times;</button>' +
              '</div>' +
              '<div class="dde-draggable__body">' +
                '<label class="dde-field">' + Drupal.t('Texto') +
                  '<input type="text" class="dde-draggable__text" value="' + this.escapeHtml(item.text) + '">' +
                '</label>' +
                '<label class="dde-field">' + Drupal.t('URL de imagen') +
                  '<input type="url" class="dde-draggable__image" value="' + this.escapeHtml(item.image_url) + '">' +
                '</label>' +
                '<div class="dde-draggable__zones-section">' +
                  '<label class="dde-field">' + Drupal.t('Zonas correctas') + '</label>' +
                  '<div class="dde-draggable__zone-checkboxes"></div>' +
                '</div>' +
                '<label class="dde-field">' + Drupal.t('Feedback correcto') +
                  '<input type="text" class="dde-draggable__fb-correct" value="' + this.escapeHtml(item.feedback_correct) + '">' +
                '</label>' +
                '<label class="dde-field">' + Drupal.t('Feedback incorrecto') +
                  '<input type="text" class="dde-draggable__fb-incorrect" value="' + this.escapeHtml(item.feedback_incorrect) + '">' +
                '</label>' +
              '</div>';
            this.draggableListEl.appendChild(panel);

            // Poblar checkboxes de zonas.
            this.populateZoneCheckboxes(panel, item.correct_zones);

            // Listeners.
            var self = this;
            panel.querySelector('.dde-draggable__remove').addEventListener('click', function () {
              panel.remove();
              self.parentEditor.markDirty();
            });

            var inputs = panel.querySelectorAll('input[type="text"], input[type="url"]');
            inputs.forEach(function (input) {
              input.addEventListener('input', function () { self.parentEditor.markDirty(); });
            });

            this.parentEditor.markDirty();
          }

          /**
           * Puebla los checkboxes de zonas correctas en un item arrastrable.
           *
           * @param {HTMLElement} panel - Panel del item arrastrable.
           * @param {Array} correctZones - Array de IDs de zonas correctas.
           */
          populateZoneCheckboxes(panel, correctZones) {
            var container = panel.querySelector('.dde-draggable__zone-checkboxes');
            container.innerHTML = '';
            var self = this;

            var zones = this.zoneListEl.querySelectorAll('.dde-zone');
            if (zones.length === 0) {
              container.innerHTML = '<span class="dde-draggable__no-zones">' + Drupal.t('No hay zonas definidas aun.') + '</span>';
              return;
            }

            zones.forEach(function (zonePanel) {
              var zoneId = zonePanel.dataset.zoneId;
              var zoneLabel = zonePanel.querySelector('.dde-zone__label').value || zoneId;
              var isChecked = correctZones.indexOf(zoneId) !== -1;

              var label = document.createElement('label');
              label.className = 'dde-draggable__zone-checkbox';
              label.innerHTML =
                '<input type="checkbox" value="' + zoneId + '"' + (isChecked ? ' checked' : '') + '> ' +
                self.escapeHtml(zoneLabel);
              container.appendChild(label);

              label.querySelector('input').addEventListener('change', function () {
                self.parentEditor.markDirty();
              });
            });
          }

          /**
           * Actualiza los selectores de zonas correctas en todos los items.
           */
          refreshZoneSelectors() {
            var self = this;
            this.draggableListEl.querySelectorAll('.dde-draggable').forEach(function (panel) {
              // Recolectar zonas actualmente seleccionadas.
              var currentZones = [];
              panel.querySelectorAll('.dde-draggable__zone-checkboxes input:checked').forEach(function (cb) {
                currentZones.push(cb.value);
              });
              self.populateZoneCheckboxes(panel, currentZones);
            });
          }

          /**
           * Recolecta los datos actuales del editor de drag and drop.
           *
           * @return {Object} Estructura con drop_zones, draggables, background_image, settings.
           */
          getData() {
            var dropZones = [];
            this.zoneListEl.querySelectorAll('.dde-zone').forEach(function (panel) {
              dropZones.push({
                id: panel.dataset.zoneId,
                label: panel.querySelector('.dde-zone__label').value,
                position: {
                  x: parseInt(panel.querySelector('.dde-zone__pos-x').value, 10) || 0,
                  y: parseInt(panel.querySelector('.dde-zone__pos-y').value, 10) || 0,
                  width: parseInt(panel.querySelector('.dde-zone__pos-w').value, 10) || 100,
                  height: parseInt(panel.querySelector('.dde-zone__pos-h').value, 10) || 100,
                },
                accepts_multiple: panel.querySelector('.dde-zone__accepts-multiple').checked,
                hint: panel.querySelector('.dde-zone__hint').value,
              });
            });

            var draggables = [];
            this.draggableListEl.querySelectorAll('.dde-draggable').forEach(function (panel) {
              var correctZones = [];
              panel.querySelectorAll('.dde-draggable__zone-checkboxes input:checked').forEach(function (cb) {
                correctZones.push(cb.value);
              });

              draggables.push({
                id: panel.dataset.draggableId,
                text: panel.querySelector('.dde-draggable__text').value,
                image_url: panel.querySelector('.dde-draggable__image').value,
                correct_zones: correctZones,
                feedback_correct: panel.querySelector('.dde-draggable__fb-correct').value,
                feedback_incorrect: panel.querySelector('.dde-draggable__fb-incorrect').value,
              });
            });

            var bgImage = this.container.querySelector('.dde-bg-image').value;

            return {
              drop_zones: dropZones,
              draggables: draggables,
              background_image: bgImage,
              settings: this.settings,
            };
          }

          /**
           * Carga datos existentes en el editor.
           *
           * @param {Object} data - Datos del drag and drop.
           */
          setData(data) {
            this.zoneListEl.innerHTML = '';
            this.draggableListEl.innerHTML = '';
            this.nextZoneId = 1;
            this.nextDraggableId = 1;

            if (data.background_image) {
              this.container.querySelector('.dde-bg-image').value = data.background_image;
            }

            // Primero cargar zonas para que esten disponibles para items.
            if (data.drop_zones && Array.isArray(data.drop_zones)) {
              data.drop_zones.forEach((z) => {
                var numMatch = String(z.id).match(/(\d+)$/);
                if (numMatch) {
                  var num = parseInt(numMatch[1], 10) + 1;
                  if (num > this.nextZoneId) this.nextZoneId = num;
                }
                this.addZone(z);
              });
            }

            // Luego cargar items.
            if (data.draggables && Array.isArray(data.draggables)) {
              data.draggables.forEach((d) => {
                var numMatch = String(d.id).match(/(\d+)$/);
                if (numMatch) {
                  var num = parseInt(numMatch[1], 10) + 1;
                  if (num > this.nextDraggableId) this.nextDraggableId = num;
                }
                this.addDraggable(d);
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

/**
 * @file
 * Sub-editor para escenarios de ramificacion (Branching Scenario).
 *
 * Estructura: Gestiona un arbol de nodos de decision donde cada nodo
 * tiene titulo, contenido, opciones con destino a otros nodos, flag
 * de nodo final, mensaje y tipo de finalizacion. Incluye un selector
 * de nodo inicial. Los nodos se muestran como paneles en lista plana
 * con indicadores de conexion.
 *
 * Logica: Cada opcion en un nodo referencia un target_node por ID.
 * Al agregar/eliminar nodos se actualiza el pool de targets validos.
 * Los nodos finales (is_end=true) no necesitan opciones. getData()
 * serializa el arbol completo con start_node, nodes[] y settings.
 *
 * Sintaxis: Clase BranchingScenarioEditor con patron IIFE,
 * Drupal.t() para cadenas traducibles y metodos getData()/setData().
 */

(function (Drupal) {
  'use strict';

  /**
   * Editor de escenarios de ramificacion.
   *
   * @param {HTMLElement} container - Contenedor del canvas del editor.
   * @param {Object} contentData - Datos existentes del contenido.
   * @param {Object} settings - Configuraciones globales.
   * @param {Object} parentEditor - Referencia al orquestador padre.
   */
  Drupal.BranchingScenarioEditor = class {
    constructor(container, contentData, settings, parentEditor) {
      this.container = container;
      this.contentData = contentData || {};
      this.settings = settings || {};
      this.parentEditor = parentEditor;
      this.nextNodeId = 1;
      this.nodes = [];

      this.render();
    }

    /**
     * Renderiza la interfaz principal del editor de ramificacion.
     */
    render() {
      this.container.innerHTML = '';

      // Selector de nodo inicial.
      var startSection = document.createElement('div');
      startSection.className = 'bse-start-section';
      startSection.innerHTML =
        '<label class="bse-field">' + Drupal.t('Nodo inicial') +
          '<select class="bse-start-node-select">' +
            '<option value="">' + Drupal.t('-- Seleccionar --') + '</option>' +
          '</select>' +
        '</label>';
      this.container.appendChild(startSection);
      this.startSelect = startSection.querySelector('.bse-start-node-select');

      this.startSelect.addEventListener('change', () => {
        this.parentEditor.markDirty();
      });

      // Toolbar de nodos.
      var toolbar = document.createElement('div');
      toolbar.className = 'bse-toolbar';
      toolbar.innerHTML =
        '<h3 class="bse-toolbar__title">' + Drupal.t('Nodos') + '</h3>' +
        '<button type="button" class="bse-toolbar__add-btn">' + Drupal.t('Agregar nodo') + '</button>';
      this.container.appendChild(toolbar);

      toolbar.querySelector('.bse-toolbar__add-btn').addEventListener('click', () => {
        this.addNode();
      });

      // Contenedor de lista de nodos.
      this.listEl = document.createElement('div');
      this.listEl.className = 'bse-node-list';
      this.container.appendChild(this.listEl);

      // Cargar datos existentes.
      if (this.contentData.nodes && this.contentData.nodes.length > 0) {
        this.setData(this.contentData);
      }
    }

    /**
     * Actualiza el selector de nodo inicial y los selectores de target.
     */
    refreshNodeSelectors() {
      var currentStart = this.startSelect.value;
      var optionsHtml = '<option value="">' + Drupal.t('-- Seleccionar --') + '</option>';

      this.nodes.forEach(function (node) {
        optionsHtml += '<option value="' + node.id + '">' + (node.title || node.id) + '</option>';
      });

      this.startSelect.innerHTML = optionsHtml;
      this.startSelect.value = currentStart;

      // Actualizar selectores de target_node en todas las opciones.
      var targetSelects = this.listEl.querySelectorAll('.bse-option__target');
      targetSelects.forEach(function (sel) {
        var currentVal = sel.value;
        sel.innerHTML = optionsHtml;
        sel.value = currentVal;
      });
    }

    /**
     * Agrega un nuevo nodo al escenario.
     *
     * @param {Object} data - Datos opcionales para precargar el nodo.
     */
    addNode(data) {
      var id = (data && data.id) || ('node_' + this.nextNodeId++);
      var node = {
        id: id,
        title: (data && data.title) || '',
        content: (data && data.content) || {},
        options: (data && data.options) || [],
        is_end: (data && data.is_end) || false,
        end_message: (data && data.end_message) || '',
        end_type: (data && data.end_type) || '',
      };

      this.nodes.push(node);

      var panel = document.createElement('div');
      panel.className = 'bse-node';
      panel.dataset.nodeId = node.id;

      panel.innerHTML =
        '<div class="bse-node__header">' +
          '<span class="bse-node__id">' + Drupal.t('Nodo: @id', { '@id': node.id }) + '</span>' +
          '<button type="button" class="bse-node__remove" title="' + Drupal.t('Eliminar nodo') + '">&times;</button>' +
        '</div>' +
        '<div class="bse-node__body">' +
          '<label class="bse-field">' + Drupal.t('Titulo') +
            '<input type="text" class="bse-node__title" value="' + this.escapeHtml(node.title) + '">' +
          '</label>' +
          '<label class="bse-field">' + Drupal.t('Texto del contenido') +
            '<textarea class="bse-node__content-text" rows="3">' + this.escapeHtml(node.content.text || '') + '</textarea>' +
          '</label>' +
          '<label class="bse-field">' + Drupal.t('URL de imagen') +
            '<input type="url" class="bse-node__content-image" value="' + this.escapeHtml(node.content.image_url || '') + '">' +
          '</label>' +
          '<label class="bse-field bse-field--inline">' +
            '<input type="checkbox" class="bse-node__is-end"' + (node.is_end ? ' checked' : '') + '> ' +
            Drupal.t('Es nodo final') +
          '</label>' +
          '<div class="bse-node__end-fields" style="' + (node.is_end ? '' : 'display:none') + '">' +
            '<label class="bse-field">' + Drupal.t('Mensaje final') +
              '<textarea class="bse-node__end-message" rows="2">' + this.escapeHtml(node.end_message) + '</textarea>' +
            '</label>' +
            '<label class="bse-field">' + Drupal.t('Tipo de final') +
              '<select class="bse-node__end-type">' +
                '<option value="">' + Drupal.t('-- Seleccionar --') + '</option>' +
                '<option value="success"' + (node.end_type === 'success' ? ' selected' : '') + '>' + Drupal.t('Exito') + '</option>' +
                '<option value="failure"' + (node.end_type === 'failure' ? ' selected' : '') + '>' + Drupal.t('Fallo') + '</option>' +
                '<option value="neutral"' + (node.end_type === 'neutral' ? ' selected' : '') + '>' + Drupal.t('Neutral') + '</option>' +
              '</select>' +
            '</label>' +
          '</div>' +
          '<div class="bse-node__options-section" style="' + (node.is_end ? 'display:none' : '') + '">' +
            '<div class="bse-node__options-header">' +
              '<h4 class="bse-node__options-title">' + Drupal.t('Opciones de decision') + '</h4>' +
              '<button type="button" class="bse-node__add-option">' + Drupal.t('Agregar opcion') + '</button>' +
            '</div>' +
            '<div class="bse-node__options-list"></div>' +
          '</div>' +
        '</div>';

      this.listEl.appendChild(panel);

      // Toggle nodo final.
      var self = this;
      var isEndCheckbox = panel.querySelector('.bse-node__is-end');
      isEndCheckbox.addEventListener('change', function () {
        var endFields = panel.querySelector('.bse-node__end-fields');
        var optSection = panel.querySelector('.bse-node__options-section');
        endFields.style.display = this.checked ? '' : 'none';
        optSection.style.display = this.checked ? 'none' : '';
        node.is_end = this.checked;
        self.parentEditor.markDirty();
      });

      // Eliminar nodo.
      panel.querySelector('.bse-node__remove').addEventListener('click', function () {
        self.nodes = self.nodes.filter(function (n) { return n.id !== node.id; });
        panel.remove();
        self.refreshNodeSelectors();
        self.parentEditor.markDirty();
      });

      // Agregar opcion.
      panel.querySelector('.bse-node__add-option').addEventListener('click', function () {
        var optList = panel.querySelector('.bse-node__options-list');
        self.addOption(optList, node.id, {});
        self.parentEditor.markDirty();
      });

      // Listeners de cambio en campos.
      var inputs = panel.querySelectorAll('input, textarea, select');
      inputs.forEach(function (field) {
        field.addEventListener('input', function () { self.parentEditor.markDirty(); });
        field.addEventListener('change', function () { self.parentEditor.markDirty(); });
      });

      // Renderizar opciones existentes.
      var optList = panel.querySelector('.bse-node__options-list');
      if (node.options.length > 0) {
        node.options.forEach(function (opt) {
          self.addOption(optList, node.id, opt);
        });
      }

      this.refreshNodeSelectors();
      this.parentEditor.markDirty();
    }

    /**
     * Agrega una fila de opcion de decision a un nodo.
     *
     * @param {HTMLElement} container - Contenedor de opciones del nodo.
     * @param {string} nodeId - ID del nodo padre.
     * @param {Object} opt - Datos de la opcion.
     */
    addOption(container, nodeId, opt) {
      var row = document.createElement('div');
      row.className = 'bse-option';
      row.innerHTML =
        '<input type="text" class="bse-option__text" value="' + this.escapeHtml(opt.text || '') + '" placeholder="' + Drupal.t('Texto de opcion') + '">' +
        '<select class="bse-option__target"><option value="">' + Drupal.t('-- Destino --') + '</option></select>' +
        '<input type="number" class="bse-option__points" min="0" value="' + (opt.points || 0) + '" placeholder="' + Drupal.t('Puntos') + '" title="' + Drupal.t('Puntos') + '">' +
        '<input type="text" class="bse-option__feedback" value="' + this.escapeHtml(opt.feedback || '') + '" placeholder="' + Drupal.t('Feedback') + '">' +
        '<button type="button" class="bse-option__remove" title="' + Drupal.t('Eliminar opcion') + '">&times;</button>';
      container.appendChild(row);

      // Poblar targets.
      var targetSelect = row.querySelector('.bse-option__target');
      this.nodes.forEach(function (n) {
        var option = document.createElement('option');
        option.value = n.id;
        option.textContent = n.title || n.id;
        if (opt.target_node === n.id) {
          option.selected = true;
        }
        targetSelect.appendChild(option);
      });

      // Listeners.
      var self = this;
      row.querySelector('.bse-option__text').addEventListener('input', function () { self.parentEditor.markDirty(); });
      row.querySelector('.bse-option__target').addEventListener('change', function () { self.parentEditor.markDirty(); });
      row.querySelector('.bse-option__points').addEventListener('input', function () { self.parentEditor.markDirty(); });
      row.querySelector('.bse-option__feedback').addEventListener('input', function () { self.parentEditor.markDirty(); });
      row.querySelector('.bse-option__remove').addEventListener('click', function () {
        row.remove();
        self.parentEditor.markDirty();
      });
    }

    /**
     * Recolecta los datos actuales del escenario de ramificacion.
     *
     * @return {Object} Estructura con start_node, nodes y settings.
     */
    getData() {
      var nodes = [];
      var panels = this.listEl.querySelectorAll('.bse-node');

      panels.forEach(function (panel) {
        var isEnd = panel.querySelector('.bse-node__is-end').checked;
        var options = [];

        if (!isEnd) {
          panel.querySelectorAll('.bse-option').forEach(function (row) {
            options.push({
              text: row.querySelector('.bse-option__text').value,
              target_node: row.querySelector('.bse-option__target').value,
              points: parseInt(row.querySelector('.bse-option__points').value, 10) || 0,
              feedback: row.querySelector('.bse-option__feedback').value,
            });
          });
        }

        nodes.push({
          id: panel.dataset.nodeId,
          title: panel.querySelector('.bse-node__title').value,
          content: {
            text: panel.querySelector('.bse-node__content-text').value,
            image_url: panel.querySelector('.bse-node__content-image').value,
          },
          options: options,
          is_end: isEnd,
          end_message: isEnd ? panel.querySelector('.bse-node__end-message').value : '',
          end_type: isEnd ? panel.querySelector('.bse-node__end-type').value : '',
        });
      });

      return {
        start_node: this.startSelect.value,
        nodes: nodes,
        settings: this.settings,
      };
    }

    /**
     * Carga datos existentes en el editor.
     *
     * @param {Object} data - Datos del escenario de ramificacion.
     */
    setData(data) {
      this.nodes = [];
      this.listEl.innerHTML = '';
      this.nextNodeId = 1;

      if (data.nodes && Array.isArray(data.nodes)) {
        // Calcular el siguiente ID disponible.
        data.nodes.forEach((n) => {
          var numMatch = String(n.id).match(/(\d+)$/);
          if (numMatch) {
            var num = parseInt(numMatch[1], 10) + 1;
            if (num > this.nextNodeId) {
              this.nextNodeId = num;
            }
          }
        });

        data.nodes.forEach((n) => {
          this.addNode(n);
        });
      }

      if (data.start_node) {
        this.startSelect.value = data.start_node;
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

})(Drupal);

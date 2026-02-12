/**
 * @file
 * Sub-editor para conjuntos de preguntas (Question Set).
 *
 * Estructura: Gestiona una lista ordenada de preguntas, cada una con
 * tipo, opciones, respuesta correcta, puntos, pista y explicacion.
 * Soporta agregar, eliminar y reordenar preguntas via drag-and-drop
 * o botones de posicion.
 *
 * Logica: Cada pregunta se renderiza como un panel colapsable con
 * campos especificos segun el tipo seleccionado. El cambio de tipo
 * re-renderiza las opciones de esa pregunta. getData() recorre todos
 * los paneles y recolecta los valores actuales.
 *
 * Sintaxis: Clase QuestionSetEditor con patron IIFE y Drupal.t()
 * para strings traducibles. Expone getData() y setData().
 */

(function (Drupal) {
  'use strict';

  /**
   * Editor de conjuntos de preguntas.
   *
   * @param {HTMLElement} container - Contenedor del canvas del editor.
   * @param {Object} contentData - Datos existentes del contenido.
   * @param {Object} settings - Configuraciones globales.
   * @param {Object} parentEditor - Referencia al orquestador padre.
   */
  Drupal.QuestionSetEditor = class {
    constructor(container, contentData, settings, parentEditor) {
      this.container = container;
      this.contentData = contentData || {};
      this.settings = settings || {};
      this.parentEditor = parentEditor;
      this.questions = [];
      this.nextId = 1;

      this.render();
    }

    /**
     * Renderiza la interfaz principal del editor de preguntas.
     */
    render() {
      this.container.innerHTML = '';

      // Toolbar superior.
      var toolbar = document.createElement('div');
      toolbar.className = 'qse-toolbar';
      toolbar.innerHTML =
        '<h3 class="qse-toolbar__title">' + Drupal.t('Preguntas') + '</h3>' +
        '<button type="button" class="qse-toolbar__add-btn">' + Drupal.t('Agregar pregunta') + '</button>';
      this.container.appendChild(toolbar);

      toolbar.querySelector('.qse-toolbar__add-btn').addEventListener('click', () => {
        this.addQuestion();
      });

      // Contenedor de lista de preguntas.
      this.listEl = document.createElement('div');
      this.listEl.className = 'qse-question-list';
      this.container.appendChild(this.listEl);

      // Cargar datos existentes.
      if (this.contentData.questions && this.contentData.questions.length > 0) {
        this.setData(this.contentData);
      }
    }

    /**
     * Agrega una nueva pregunta vacia a la lista.
     *
     * @param {Object} data - Datos opcionales para precargar la pregunta.
     */
    addQuestion(data) {
      var id = this.nextId++;
      var question = {
        id: id,
        text: (data && data.text) || '',
        type: (data && data.type) || 'multiple_choice',
        options: (data && data.options) || [],
        correct_answer: (data && data.correct_answer) || '',
        points: (data && data.points) || 1,
        hint: (data && data.hint) || '',
        explanation: (data && data.explanation) || '',
      };
      this.questions.push(question);
      this.renderQuestion(question);
      this.parentEditor.markDirty();
    }

    /**
     * Renderiza un panel individual de pregunta en la lista.
     *
     * @param {Object} question - Datos de la pregunta.
     */
    renderQuestion(question) {
      var panel = document.createElement('div');
      panel.className = 'qse-question';
      panel.dataset.questionId = question.id;

      var headerHtml =
        '<div class="qse-question__header">' +
          '<span class="qse-question__number">' + Drupal.t('Pregunta @num', { '@num': question.id }) + '</span>' +
          '<div class="qse-question__header-actions">' +
            '<button type="button" class="qse-question__move-up" title="' + Drupal.t('Mover arriba') + '">&uarr;</button>' +
            '<button type="button" class="qse-question__move-down" title="' + Drupal.t('Mover abajo') + '">&darr;</button>' +
            '<button type="button" class="qse-question__remove" title="' + Drupal.t('Eliminar') + '">&times;</button>' +
          '</div>' +
        '</div>';

      var bodyHtml =
        '<div class="qse-question__body">' +
          '<label class="qse-question__label">' + Drupal.t('Texto de la pregunta') +
            '<textarea class="qse-question__text" rows="2">' + this.escapeHtml(question.text) + '</textarea>' +
          '</label>' +
          '<div class="qse-question__row">' +
            '<label class="qse-question__label">' + Drupal.t('Tipo') +
              '<select class="qse-question__type">' +
                '<option value="multiple_choice"' + (question.type === 'multiple_choice' ? ' selected' : '') + '>' + Drupal.t('Opcion multiple') + '</option>' +
                '<option value="true_false"' + (question.type === 'true_false' ? ' selected' : '') + '>' + Drupal.t('Verdadero/Falso') + '</option>' +
                '<option value="short_answer"' + (question.type === 'short_answer' ? ' selected' : '') + '>' + Drupal.t('Respuesta corta') + '</option>' +
                '<option value="fill_blanks"' + (question.type === 'fill_blanks' ? ' selected' : '') + '>' + Drupal.t('Completar espacios') + '</option>' +
              '</select>' +
            '</label>' +
            '<label class="qse-question__label">' + Drupal.t('Puntos') +
              '<input type="number" class="qse-question__points" min="0" value="' + question.points + '">' +
            '</label>' +
          '</div>' +
          '<div class="qse-question__options-area"></div>' +
          '<label class="qse-question__label">' + Drupal.t('Pista') +
            '<input type="text" class="qse-question__hint" value="' + this.escapeHtml(question.hint) + '">' +
          '</label>' +
          '<label class="qse-question__label">' + Drupal.t('Explicacion') +
            '<textarea class="qse-question__explanation" rows="2">' + this.escapeHtml(question.explanation) + '</textarea>' +
          '</label>' +
        '</div>';

      panel.innerHTML = headerHtml + bodyHtml;
      this.listEl.appendChild(panel);

      // Renderizar opciones segun tipo.
      this.renderOptionsForType(panel, question);

      // Listeners de cabecera.
      panel.querySelector('.qse-question__remove').addEventListener('click', () => {
        this.removeQuestion(question.id);
      });
      panel.querySelector('.qse-question__move-up').addEventListener('click', () => {
        this.moveQuestion(question.id, -1);
      });
      panel.querySelector('.qse-question__move-down').addEventListener('click', () => {
        this.moveQuestion(question.id, 1);
      });

      // Listener de cambio de tipo.
      panel.querySelector('.qse-question__type').addEventListener('change', (e) => {
        question.type = e.target.value;
        this.renderOptionsForType(panel, question);
        this.parentEditor.markDirty();
      });

      // Listeners de cambio en campos.
      var self = this;
      panel.querySelector('.qse-question__text').addEventListener('input', function () {
        self.parentEditor.markDirty();
      });
      panel.querySelector('.qse-question__points').addEventListener('input', function () {
        self.parentEditor.markDirty();
      });
      panel.querySelector('.qse-question__hint').addEventListener('input', function () {
        self.parentEditor.markDirty();
      });
      panel.querySelector('.qse-question__explanation').addEventListener('input', function () {
        self.parentEditor.markDirty();
      });
    }

    /**
     * Renderiza las opciones especificas segun el tipo de pregunta.
     *
     * @param {HTMLElement} panel - Panel de la pregunta.
     * @param {Object} question - Datos de la pregunta.
     */
    renderOptionsForType(panel, question) {
      var area = panel.querySelector('.qse-question__options-area');
      area.innerHTML = '';

      if (question.type === 'multiple_choice') {
        var addOptBtn = document.createElement('button');
        addOptBtn.type = 'button';
        addOptBtn.className = 'qse-question__add-option';
        addOptBtn.textContent = Drupal.t('Agregar opcion');

        var optList = document.createElement('div');
        optList.className = 'qse-question__options-list';
        area.appendChild(optList);
        area.appendChild(addOptBtn);

        var options = question.options.length > 0 ? question.options : [{ text: '', is_correct: false }];
        options.forEach((opt, idx) => {
          this.renderOptionRow(optList, question, opt, idx);
        });

        addOptBtn.addEventListener('click', () => {
          var newOpt = { text: '', is_correct: false };
          this.renderOptionRow(optList, question, newOpt, optList.children.length);
          this.parentEditor.markDirty();
        });
      }
      else if (question.type === 'true_false') {
        var correctVal = question.correct_answer || 'true';
        area.innerHTML =
          '<label class="qse-question__label">' + Drupal.t('Respuesta correcta') +
            '<select class="qse-question__tf-answer">' +
              '<option value="true"' + (correctVal === 'true' ? ' selected' : '') + '>' + Drupal.t('Verdadero') + '</option>' +
              '<option value="false"' + (correctVal === 'false' ? ' selected' : '') + '>' + Drupal.t('Falso') + '</option>' +
            '</select>' +
          '</label>';
        area.querySelector('.qse-question__tf-answer').addEventListener('change', () => {
          this.parentEditor.markDirty();
        });
      }
      else if (question.type === 'short_answer') {
        area.innerHTML =
          '<label class="qse-question__label">' + Drupal.t('Respuesta correcta') +
            '<input type="text" class="qse-question__short-answer" value="' + this.escapeHtml(question.correct_answer) + '">' +
          '</label>';
        area.querySelector('.qse-question__short-answer').addEventListener('input', () => {
          this.parentEditor.markDirty();
        });
      }
      else if (question.type === 'fill_blanks') {
        area.innerHTML =
          '<label class="qse-question__label">' + Drupal.t('Respuesta(s) correcta(s) (separadas por coma)') +
            '<input type="text" class="qse-question__blanks-answer" value="' + this.escapeHtml(question.correct_answer) + '">' +
          '</label>' +
          '<p class="qse-question__help">' + Drupal.t('Use *asteriscos* en el texto para marcar los espacios en blanco.') + '</p>';
        area.querySelector('.qse-question__blanks-answer').addEventListener('input', () => {
          this.parentEditor.markDirty();
        });
      }
    }

    /**
     * Renderiza una fila de opcion de respuesta multiple.
     *
     * @param {HTMLElement} container - Contenedor de opciones.
     * @param {Object} question - Datos de la pregunta.
     * @param {Object} opt - Datos de la opcion.
     * @param {number} idx - Indice de la opcion.
     */
    renderOptionRow(container, question, opt, idx) {
      var row = document.createElement('div');
      row.className = 'qse-question__option-row';
      row.innerHTML =
        '<input type="radio" name="q-' + question.id + '-correct" class="qse-question__option-correct"' +
          (opt.is_correct ? ' checked' : '') + ' title="' + Drupal.t('Marcar como correcta') + '">' +
        '<input type="text" class="qse-question__option-text" value="' + this.escapeHtml(opt.text) + '" placeholder="' + Drupal.t('Opcion @num', { '@num': idx + 1 }) + '">' +
        '<button type="button" class="qse-question__option-remove" title="' + Drupal.t('Eliminar opcion') + '">&times;</button>';
      container.appendChild(row);

      row.querySelector('.qse-question__option-text').addEventListener('input', () => {
        this.parentEditor.markDirty();
      });
      row.querySelector('.qse-question__option-correct').addEventListener('change', () => {
        this.parentEditor.markDirty();
      });
      row.querySelector('.qse-question__option-remove').addEventListener('click', () => {
        row.remove();
        this.parentEditor.markDirty();
      });
    }

    /**
     * Elimina una pregunta de la lista.
     *
     * @param {number} id - ID de la pregunta a eliminar.
     */
    removeQuestion(id) {
      this.questions = this.questions.filter(function (q) { return q.id !== id; });
      var panel = this.listEl.querySelector('[data-question-id="' + id + '"]');
      if (panel) {
        panel.remove();
      }
      this.parentEditor.markDirty();
    }

    /**
     * Mueve una pregunta hacia arriba o abajo en la lista.
     *
     * @param {number} id - ID de la pregunta a mover.
     * @param {number} direction - Direccion: -1 (arriba) o 1 (abajo).
     */
    moveQuestion(id, direction) {
      var idx = this.questions.findIndex(function (q) { return q.id === id; });
      var newIdx = idx + direction;
      if (newIdx < 0 || newIdx >= this.questions.length) return;

      // Intercambiar en el array.
      var temp = this.questions[idx];
      this.questions[idx] = this.questions[newIdx];
      this.questions[newIdx] = temp;

      // Intercambiar en el DOM.
      var panels = this.listEl.querySelectorAll('.qse-question');
      var currentPanel = panels[idx];
      var targetPanel = panels[newIdx];

      if (direction === -1) {
        this.listEl.insertBefore(currentPanel, targetPanel);
      }
      else {
        this.listEl.insertBefore(targetPanel, currentPanel);
      }

      this.parentEditor.markDirty();
    }

    /**
     * Recolecta los datos actuales de todas las preguntas.
     *
     * @return {Object} Estructura con questions y settings.
     */
    getData() {
      var questions = [];
      var panels = this.listEl.querySelectorAll('.qse-question');

      panels.forEach(function (panel) {
        var type = panel.querySelector('.qse-question__type').value;
        var q = {
          text: panel.querySelector('.qse-question__text').value,
          type: type,
          options: [],
          correct_answer: '',
          points: parseInt(panel.querySelector('.qse-question__points').value, 10) || 1,
          hint: panel.querySelector('.qse-question__hint').value,
          explanation: panel.querySelector('.qse-question__explanation').value,
        };

        if (type === 'multiple_choice') {
          var optRows = panel.querySelectorAll('.qse-question__option-row');
          optRows.forEach(function (row) {
            q.options.push({
              text: row.querySelector('.qse-question__option-text').value,
              is_correct: row.querySelector('.qse-question__option-correct').checked,
            });
          });
          var correctOpt = q.options.find(function (o) { return o.is_correct; });
          q.correct_answer = correctOpt ? correctOpt.text : '';
        }
        else if (type === 'true_false') {
          var tfSelect = panel.querySelector('.qse-question__tf-answer');
          q.correct_answer = tfSelect ? tfSelect.value : 'true';
        }
        else if (type === 'short_answer') {
          var saInput = panel.querySelector('.qse-question__short-answer');
          q.correct_answer = saInput ? saInput.value : '';
        }
        else if (type === 'fill_blanks') {
          var fbInput = panel.querySelector('.qse-question__blanks-answer');
          q.correct_answer = fbInput ? fbInput.value : '';
        }

        questions.push(q);
      });

      return {
        questions: questions,
        settings: this.settings,
      };
    }

    /**
     * Carga datos existentes en el editor.
     *
     * @param {Object} data - Datos con array de questions.
     */
    setData(data) {
      this.questions = [];
      this.listEl.innerHTML = '';

      if (data.questions && Array.isArray(data.questions)) {
        data.questions.forEach((q) => {
          this.addQuestion(q);
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

})(Drupal);

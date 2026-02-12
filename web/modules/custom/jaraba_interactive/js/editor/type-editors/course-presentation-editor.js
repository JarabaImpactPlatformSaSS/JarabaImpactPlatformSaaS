/**
 * @file
 * Sub-editor para presentaciones de curso (Course Presentation).
 *
 * Estructura: Gestiona una lista ordenada de diapositivas, cada una
 * con titulo, selector de layout, campos de contenido (texto, imagen,
 * video, codigo), quiz opcional, notas del presentador y flag de
 * requerida. Soporta agregar, eliminar y reordenar diapositivas.
 *
 * Logica: Cada diapositiva se renderiza como un panel colapsable.
 * El cambio de layout ajusta dinamicamente los campos visibles.
 * getData() serializa todas las diapositivas con su contenido.
 * La lista se mantiene sincronizada con el DOM.
 *
 * Sintaxis: Clase CoursePresentationEditor con patron IIFE,
 * Drupal.t() para cadenas traducibles y metodos getData()/setData().
 */

(function (Drupal) {
  'use strict';

  /**
   * Editor de presentaciones de curso.
   *
   * @param {HTMLElement} container - Contenedor del canvas del editor.
   * @param {Object} contentData - Datos existentes del contenido.
   * @param {Object} settings - Configuraciones globales.
   * @param {Object} parentEditor - Referencia al orquestador padre.
   */
  Drupal.CoursePresentationEditor = class {
    constructor(container, contentData, settings, parentEditor) {
      this.container = container;
      this.contentData = contentData || {};
      this.settings = settings || {};
      this.parentEditor = parentEditor;
      this.slideCount = 0;

      this.layoutOptions = [
        { value: 'full', label: Drupal.t('Completa') },
        { value: 'split', label: Drupal.t('Dividida') },
        { value: 'title_only', label: Drupal.t('Solo titulo') },
        { value: 'media_left', label: Drupal.t('Media izquierda') },
        { value: 'media_right', label: Drupal.t('Media derecha') },
        { value: 'quiz', label: Drupal.t('Quiz') },
      ];

      this.render();
    }

    /**
     * Renderiza la interfaz principal del editor de presentaciones.
     */
    render() {
      this.container.innerHTML = '';

      // Toolbar superior.
      var toolbar = document.createElement('div');
      toolbar.className = 'cpe-toolbar';
      toolbar.innerHTML =
        '<h3 class="cpe-toolbar__title">' + Drupal.t('Diapositivas') + '</h3>' +
        '<span class="cpe-toolbar__count"></span>' +
        '<button type="button" class="cpe-toolbar__add-btn">' + Drupal.t('Agregar diapositiva') + '</button>';
      this.container.appendChild(toolbar);

      this.countEl = toolbar.querySelector('.cpe-toolbar__count');

      toolbar.querySelector('.cpe-toolbar__add-btn').addEventListener('click', () => {
        this.addSlide();
      });

      // Contenedor de lista de diapositivas.
      this.listEl = document.createElement('div');
      this.listEl.className = 'cpe-slide-list';
      this.container.appendChild(this.listEl);

      // Cargar datos existentes.
      if (this.contentData.slides && this.contentData.slides.length > 0) {
        this.setData(this.contentData);
      }

      this.updateCount();
    }

    /**
     * Actualiza el contador de diapositivas visible.
     */
    updateCount() {
      var total = this.listEl.querySelectorAll('.cpe-slide').length;
      this.countEl.textContent = Drupal.t('@count diapositiva(s)', { '@count': total });
    }

    /**
     * Agrega una nueva diapositiva a la lista.
     *
     * @param {Object} data - Datos opcionales para precargar la diapositiva.
     */
    addSlide(data) {
      this.slideCount++;
      var num = this.slideCount;
      var slide = {
        title: (data && data.title) || '',
        layout: (data && data.layout) || 'full',
        content: (data && data.content) || {},
        quiz: (data && data.quiz) || null,
        required: (data && data.required) || false,
        speaker_notes: (data && data.speaker_notes) || '',
      };

      var panel = document.createElement('div');
      panel.className = 'cpe-slide';
      panel.dataset.slideNum = num;

      // Construir opciones de layout.
      var layoutOpts = '';
      this.layoutOptions.forEach(function (opt) {
        layoutOpts += '<option value="' + opt.value + '"' + (slide.layout === opt.value ? ' selected' : '') + '>' + opt.label + '</option>';
      });

      panel.innerHTML =
        '<div class="cpe-slide__header">' +
          '<span class="cpe-slide__number">' + Drupal.t('Diapositiva @num', { '@num': num }) + '</span>' +
          '<div class="cpe-slide__header-actions">' +
            '<button type="button" class="cpe-slide__move-up" title="' + Drupal.t('Mover arriba') + '">&uarr;</button>' +
            '<button type="button" class="cpe-slide__move-down" title="' + Drupal.t('Mover abajo') + '">&darr;</button>' +
            '<button type="button" class="cpe-slide__remove" title="' + Drupal.t('Eliminar') + '">&times;</button>' +
          '</div>' +
        '</div>' +
        '<div class="cpe-slide__body">' +
          '<div class="cpe-slide__row">' +
            '<label class="cpe-slide__label">' + Drupal.t('Titulo') +
              '<input type="text" class="cpe-slide__title" value="' + this.escapeHtml(slide.title) + '">' +
            '</label>' +
            '<label class="cpe-slide__label">' + Drupal.t('Layout') +
              '<select class="cpe-slide__layout">' + layoutOpts + '</select>' +
            '</label>' +
          '</div>' +
          '<div class="cpe-slide__content-area">' +
            '<label class="cpe-slide__label">' + Drupal.t('Texto') +
              '<textarea class="cpe-slide__text" rows="3">' + this.escapeHtml(slide.content.text || '') + '</textarea>' +
            '</label>' +
            '<label class="cpe-slide__label">' + Drupal.t('URL de imagen') +
              '<input type="url" class="cpe-slide__image-url" value="' + this.escapeHtml(slide.content.image_url || '') + '">' +
            '</label>' +
            '<label class="cpe-slide__label">' + Drupal.t('URL de video') +
              '<input type="url" class="cpe-slide__video-url" value="' + this.escapeHtml(slide.content.video_url || '') + '">' +
            '</label>' +
            '<label class="cpe-slide__label">' + Drupal.t('Codigo') +
              '<textarea class="cpe-slide__code" rows="3">' + this.escapeHtml(slide.content.code || '') + '</textarea>' +
            '</label>' +
          '</div>' +
          '<div class="cpe-slide__quiz-area">' +
            '<label class="cpe-slide__label cpe-slide__label--inline">' +
              '<input type="checkbox" class="cpe-slide__quiz-enabled"' + (slide.quiz ? ' checked' : '') + '> ' +
              Drupal.t('Incluir quiz en esta diapositiva') +
            '</label>' +
            '<div class="cpe-slide__quiz-fields" style="' + (slide.quiz ? '' : 'display:none') + '">' +
              '<label class="cpe-slide__label">' + Drupal.t('Pregunta del quiz') +
                '<input type="text" class="cpe-slide__quiz-question" value="' + this.escapeHtml(slide.quiz ? slide.quiz.question || '' : '') + '">' +
              '</label>' +
              '<label class="cpe-slide__label">' + Drupal.t('Respuesta correcta') +
                '<input type="text" class="cpe-slide__quiz-answer" value="' + this.escapeHtml(slide.quiz ? slide.quiz.correct_answer || '' : '') + '">' +
              '</label>' +
            '</div>' +
          '</div>' +
          '<div class="cpe-slide__meta-area">' +
            '<label class="cpe-slide__label cpe-slide__label--inline">' +
              '<input type="checkbox" class="cpe-slide__required"' + (slide.required ? ' checked' : '') + '> ' +
              Drupal.t('Diapositiva requerida') +
            '</label>' +
            '<label class="cpe-slide__label">' + Drupal.t('Notas del presentador') +
              '<textarea class="cpe-slide__speaker-notes" rows="2">' + this.escapeHtml(slide.speaker_notes) + '</textarea>' +
            '</label>' +
          '</div>' +
        '</div>';

      this.listEl.appendChild(panel);

      // Listeners de acciones de cabecera.
      var self = this;
      panel.querySelector('.cpe-slide__remove').addEventListener('click', function () {
        panel.remove();
        self.updateCount();
        self.parentEditor.markDirty();
      });
      panel.querySelector('.cpe-slide__move-up').addEventListener('click', function () {
        self.moveSlide(panel, -1);
      });
      panel.querySelector('.cpe-slide__move-down').addEventListener('click', function () {
        self.moveSlide(panel, 1);
      });

      // Toggle de quiz.
      panel.querySelector('.cpe-slide__quiz-enabled').addEventListener('change', function () {
        var quizFields = panel.querySelector('.cpe-slide__quiz-fields');
        quizFields.style.display = this.checked ? '' : 'none';
        self.parentEditor.markDirty();
      });

      // Listeners de cambio general.
      var dirtyFields = panel.querySelectorAll('input, textarea, select');
      dirtyFields.forEach(function (field) {
        field.addEventListener('input', function () { self.parentEditor.markDirty(); });
        field.addEventListener('change', function () { self.parentEditor.markDirty(); });
      });

      this.updateCount();
      this.parentEditor.markDirty();
    }

    /**
     * Mueve una diapositiva hacia arriba o abajo.
     *
     * @param {HTMLElement} panel - Panel de la diapositiva.
     * @param {number} direction - Direccion: -1 (arriba) o 1 (abajo).
     */
    moveSlide(panel, direction) {
      var panels = Array.from(this.listEl.querySelectorAll('.cpe-slide'));
      var idx = panels.indexOf(panel);
      var newIdx = idx + direction;
      if (newIdx < 0 || newIdx >= panels.length) return;

      if (direction === -1) {
        this.listEl.insertBefore(panel, panels[newIdx]);
      }
      else {
        this.listEl.insertBefore(panels[newIdx], panel);
      }

      this.parentEditor.markDirty();
    }

    /**
     * Recolecta los datos actuales de todas las diapositivas.
     *
     * @return {Object} Estructura con slides y settings.
     */
    getData() {
      var slides = [];
      var panels = this.listEl.querySelectorAll('.cpe-slide');

      panels.forEach(function (panel) {
        var quizEnabled = panel.querySelector('.cpe-slide__quiz-enabled').checked;
        var quiz = null;
        if (quizEnabled) {
          quiz = {
            question: panel.querySelector('.cpe-slide__quiz-question').value,
            correct_answer: panel.querySelector('.cpe-slide__quiz-answer').value,
          };
        }

        slides.push({
          title: panel.querySelector('.cpe-slide__title').value,
          layout: panel.querySelector('.cpe-slide__layout').value,
          content: {
            text: panel.querySelector('.cpe-slide__text').value,
            image_url: panel.querySelector('.cpe-slide__image-url').value,
            video_url: panel.querySelector('.cpe-slide__video-url').value,
            code: panel.querySelector('.cpe-slide__code').value,
          },
          quiz: quiz,
          required: panel.querySelector('.cpe-slide__required').checked,
          speaker_notes: panel.querySelector('.cpe-slide__speaker-notes').value,
        });
      });

      return {
        slides: slides,
        settings: this.settings,
      };
    }

    /**
     * Carga datos existentes en el editor.
     *
     * @param {Object} data - Datos con array de slides.
     */
    setData(data) {
      this.listEl.innerHTML = '';
      this.slideCount = 0;

      if (data.slides && Array.isArray(data.slides)) {
        data.slides.forEach((s) => {
          this.addSlide(s);
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

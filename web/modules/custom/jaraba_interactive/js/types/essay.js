/**
 * @file
 * Motor JS del editor de ensayos.
 *
 * Estructura: Gestiona el area de escritura del ensayo con
 * contador de palabras, guardado de borradores y envio.
 * Soporta formato basico (negrita, cursiva, subrayado).
 *
 * Logica: El usuario escribe en el textarea. Se cuenta palabras
 * en tiempo real y se valida contra los limites configurados.
 * El borrador se guarda en localStorage. Al enviar, se emite
 * evento de completitud con el texto.
 *
 * Sintaxis: Clase EssayEngine con Drupal.behaviors.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Motor del editor de ensayos.
   *
   * @param {HTMLElement} container - Contenedor .essay
   */
  Drupal.EssayEngine = class {
    constructor(container) {
      this.container = container;
      this.settings = JSON.parse(container.dataset.settings || '{}');
      this.rubric = JSON.parse(container.dataset.rubric || '[]');

      // Referencias DOM.
      this.textarea = container.querySelector('.essay__textarea');
      this.wordCountEl = container.querySelector('.essay__word-count-current');
      this.submitBtn = container.querySelector('.essay__submit-btn');
      this.saveDraftBtn = container.querySelector('.essay__save-draft');
      this.resultsPanel = container.querySelector('.essay__results');

      // Estado.
      this.wordCount = 0;
      this.isDirty = false;
      this.draftKey = 'jaraba_essay_draft_' + (container.closest('[data-content-id]')?.dataset.contentId || 'unknown');

      this.init();
    }

    /**
     * Inicializa listeners y carga borrador.
     */
    init() {
      // Contador de palabras en tiempo real.
      this.textarea.addEventListener('input', () => this.onTextInput());

      // Enviar ensayo.
      if (this.submitBtn) {
        this.submitBtn.addEventListener('click', () => this.submit());
      }

      // Guardar borrador.
      if (this.saveDraftBtn) {
        this.saveDraftBtn.addEventListener('click', () => this.saveDraft());
      }

      // Toolbar de formato.
      this.container.querySelectorAll('.essay__toolbar-btn').forEach(btn => {
        btn.addEventListener('click', () => this.applyFormat(btn.dataset.action));
      });

      // Cargar borrador si existe.
      this.loadDraft();

      // Conteo inicial.
      this.updateWordCount();
    }

    /**
     * Maneja la entrada de texto.
     */
    onTextInput() {
      this.isDirty = true;
      this.updateWordCount();
    }

    /**
     * Actualiza el contador de palabras.
     */
    updateWordCount() {
      const text = this.textarea.value.trim();
      this.wordCount = text.length > 0 ? text.split(/\s+/).length : 0;

      if (this.wordCountEl) {
        this.wordCountEl.textContent = this.wordCount;
      }

      // Validar limites.
      const minWords = this.settings.min_words || 0;
      const maxWords = this.settings.max_words || 0;

      this.textarea.classList.remove('is-under-limit', 'is-over-limit', 'is-valid-length');

      if (minWords > 0 && this.wordCount < minWords) {
        this.textarea.classList.add('is-under-limit');
      }
      else if (maxWords > 0 && this.wordCount > maxWords) {
        this.textarea.classList.add('is-over-limit');
      }
      else if (minWords > 0) {
        this.textarea.classList.add('is-valid-length');
      }

      // Habilitar/deshabilitar boton de envio.
      if (this.submitBtn) {
        const isValidLength = (minWords === 0 || this.wordCount >= minWords) &&
                              (maxWords === 0 || this.wordCount <= maxWords);
        this.submitBtn.disabled = !isValidLength || this.wordCount === 0;
      }
    }

    /**
     * Aplica formato basico al texto seleccionado.
     *
     * @param {string} action - Tipo de formato: bold, italic, underline.
     */
    applyFormat(action) {
      const start = this.textarea.selectionStart;
      const end = this.textarea.selectionEnd;
      const selected = this.textarea.value.substring(start, end);

      if (!selected) return;

      let wrapped;
      switch (action) {
        case 'bold':
          wrapped = '**' + selected + '**';
          break;
        case 'italic':
          wrapped = '_' + selected + '_';
          break;
        case 'underline':
          wrapped = '<u>' + selected + '</u>';
          break;
        default:
          return;
      }

      this.textarea.value = this.textarea.value.substring(0, start) + wrapped + this.textarea.value.substring(end);
      this.textarea.focus();
      this.textarea.setSelectionRange(start, start + wrapped.length);
      this.isDirty = true;
    }

    /**
     * Guarda el borrador en localStorage.
     */
    saveDraft() {
      try {
        localStorage.setItem(this.draftKey, JSON.stringify({
          text: this.textarea.value,
          timestamp: Date.now(),
        }));
        this.isDirty = false;

        // Feedback visual.
        if (this.saveDraftBtn) {
          const originalText = this.saveDraftBtn.querySelector('span');
          if (originalText) {
            const saved = originalText.textContent;
            originalText.textContent = Drupal.t('Guardado');
            setTimeout(() => { originalText.textContent = saved; }, 2000);
          }
        }
      }
      catch (e) {
        console.warn('Error al guardar borrador:', e);
      }
    }

    /**
     * Carga el borrador desde localStorage.
     */
    loadDraft() {
      try {
        const draft = localStorage.getItem(this.draftKey);
        if (draft) {
          const data = JSON.parse(draft);
          if (data.text) {
            this.textarea.value = data.text;
            this.updateWordCount();
          }
        }
      }
      catch (e) {
        console.warn('Error al cargar borrador:', e);
      }
    }

    /**
     * Envia el ensayo para evaluacion.
     */
    submit() {
      const text = this.textarea.value.trim();
      if (!text) return;

      // Limpiar borrador.
      try {
        localStorage.removeItem(this.draftKey);
      }
      catch (e) {
        // Ignorar errores de limpieza.
      }

      // Emitir evento de completitud.
      const event = new CustomEvent('interactive:completed', {
        detail: {
          responses: {
            text: text,
            word_count: this.wordCount,
            criterion_scores: {},
          },
          type: 'essay',
        }
      });
      this.container.dispatchEvent(event);

      // Deshabilitar textarea.
      this.textarea.disabled = true;
      if (this.submitBtn) {
        this.submitBtn.disabled = true;
      }
    }

    /**
     * Obtiene las respuestas (texto del ensayo).
     *
     * @return {Object} Datos del ensayo.
     */
    getResponses() {
      return {
        text: this.textarea.value,
        word_count: this.wordCount,
        criterion_scores: {},
      };
    }
  };

  /**
   * Behavior para inicializar el editor de ensayos.
   */
  Drupal.behaviors.essayEngine = {
    attach: function (context) {
      once('essay-engine', '.essay', context).forEach(function (element) {
        element._engine = new Drupal.EssayEngine(element);
      });
    }
  };

})(Drupal, once);

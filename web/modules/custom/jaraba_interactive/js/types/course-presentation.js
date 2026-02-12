/**
 * @file
 * Motor JS del visor de presentaciones interactivas.
 *
 * Estructura: Gestiona la navegacion entre slides, quizzes embebidos,
 * indicadores de progreso y soporte de teclado.
 *
 * Logica: Las slides se muestran secuencialmente con transiciones.
 * Las slides con quiz requieren respuesta antes de avanzar si
 * required=true. El progreso se trackea por dots y contador.
 *
 * Sintaxis: Clase CoursePresentationEngine con Drupal.behaviors.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Motor del visor de presentaciones.
   *
   * @param {HTMLElement} container - Contenedor .course-presentation
   */
  Drupal.CoursePresentationEngine = class {
    constructor(container) {
      this.container = container;
      this.slides = container.querySelectorAll('.course-presentation__slide');
      this.settings = JSON.parse(container.dataset.settings || '{}');
      this.currentIndex = 0;
      this.totalSlides = this.slides.length;
      this.responses = {};

      // Referencias al DOM.
      this.prevBtn = container.querySelector('.course-presentation__nav-btn--prev');
      this.nextBtn = container.querySelector('.course-presentation__nav-btn--next');
      this.dots = container.querySelectorAll('.course-presentation__dot');
      this.counter = container.querySelector('.course-presentation__current');

      this.init();
    }

    /**
     * Inicializa los event listeners y el estado.
     */
    init() {
      // Navegacion con botones.
      this.prevBtn.addEventListener('click', () => this.goTo(this.currentIndex - 1));
      this.nextBtn.addEventListener('click', () => this.goTo(this.currentIndex + 1));

      // Navegacion con dots.
      this.dots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
          if (this.settings.navigation === 'sequential' && index > this.currentIndex + 1) {
            return;
          }
          this.goTo(index);
        });
      });

      // Navegacion con teclado.
      if (this.settings.enable_keyboard !== false) {
        document.addEventListener('keydown', (e) => this.onKeyDown(e));
      }

      // Inicializar quizzes embebidos.
      this.initQuizzes();

      this.updateNavigation();
    }

    /**
     * Navega a una slide especifica.
     *
     * @param {number} index - Indice de la slide destino.
     */
    goTo(index) {
      if (index < 0 || index >= this.totalSlides) {
        return;
      }

      // Verificar si la slide actual requiere interaccion.
      if (index > this.currentIndex) {
        const currentSlide = this.slides[this.currentIndex];
        if (currentSlide.dataset.required === 'true') {
          const quiz = currentSlide.querySelector('.course-presentation__quiz');
          if (quiz && !this.responses[currentSlide.dataset.slideId]) {
            quiz.classList.add('is-required');
            return;
          }
        }
      }

      // Transicion.
      this.slides[this.currentIndex].classList.remove('is-active');
      this.slides[index].classList.add('is-active');
      this.currentIndex = index;

      this.updateNavigation();

      // Verificar completitud.
      if (index === this.totalSlides - 1) {
        this.checkCompletion();
      }
    }

    /**
     * Actualiza el estado de la navegacion.
     */
    updateNavigation() {
      // Botones prev/next.
      this.prevBtn.disabled = this.currentIndex === 0;
      this.nextBtn.disabled = this.currentIndex === this.totalSlides - 1;

      // Dots activos.
      this.dots.forEach((dot, index) => {
        dot.classList.toggle('is-active', index === this.currentIndex);
        dot.classList.toggle('is-completed', index < this.currentIndex);
      });

      // Contador.
      if (this.counter) {
        this.counter.textContent = this.currentIndex + 1;
      }
    }

    /**
     * Maneja eventos de teclado.
     *
     * @param {KeyboardEvent} e - Evento de teclado.
     */
    onKeyDown(e) {
      // Solo si el foco esta en el container o no hay foco especifico.
      if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
        e.preventDefault();
        this.goTo(this.currentIndex + 1);
      }
      else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
        e.preventDefault();
        this.goTo(this.currentIndex - 1);
      }
    }

    /**
     * Inicializa los quizzes embebidos en slides.
     */
    initQuizzes() {
      this.container.querySelectorAll('.course-presentation__quiz').forEach(quiz => {
        const slideEl = quiz.closest('.course-presentation__slide');
        const slideId = slideEl.dataset.slideId;

        quiz.querySelectorAll('.course-presentation__quiz-input').forEach(input => {
          input.addEventListener('change', () => {
            this.responses[slideId] = input.value;
            quiz.classList.remove('is-required');
            quiz.classList.add('is-answered');
          });
        });
      });
    }

    /**
     * Verifica si la presentacion esta completada.
     */
    checkCompletion() {
      const event = new CustomEvent('interactive:completed', {
        detail: { responses: this.responses, type: 'course_presentation' }
      });
      this.container.dispatchEvent(event);
    }

    /**
     * Obtiene las respuestas recolectadas.
     *
     * @return {Object} Mapa de respuestas por slide ID.
     */
    getResponses() {
      return this.responses;
    }
  };

  /**
   * Behavior para inicializar el visor de presentaciones.
   */
  Drupal.behaviors.coursePresentationEngine = {
    attach: function (context) {
      once('course-presentation', '.course-presentation', context).forEach(function (element) {
        element._engine = new Drupal.CoursePresentationEngine(element);
      });
    }
  };

})(Drupal, once);

/**
 * @file
 * Motor JS del reproductor de video interactivo.
 *
 * Estructura: Gestiona la reproduccion de video HTML5 con checkpoints
 * interactivos que se activan en timestamps especificos. Soporta
 * capítulos para navegacion y controles personalizados.
 *
 * Logica: Monitorea el currentTime del video y pausa en cada checkpoint.
 * Muestra overlay con quiz/info/decision segun el tipo de checkpoint.
 * Recolecta respuestas y calcula progreso parcial.
 *
 * Sintaxis: Clase InteractiveVideoEngine registrada en Drupal.behaviors.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Motor del reproductor de video interactivo.
   *
   * @param {HTMLElement} container - Contenedor .interactive-video
   */
  Drupal.InteractiveVideoEngine = class {
    constructor(container) {
      // Elemento contenedor y datos.
      this.container = container;
      this.videoEl = container.querySelector('.interactive-video__element');
      this.checkpoints = JSON.parse(container.dataset.checkpoints || '[]');
      this.chapters = JSON.parse(container.dataset.chapters || '[]');
      this.settings = JSON.parse(container.dataset.settings || '{}');

      // Estado interno.
      this.currentCheckpointIndex = 0;
      this.completedCheckpoints = new Set();
      this.responses = {};
      this.isPlaying = false;

      // Referencias a elementos del DOM.
      this.overlay = container.querySelector('.interactive-video__overlay');
      this.progressFill = container.querySelector('.interactive-video__progress-fill');
      this.timeDisplay = container.querySelector('.interactive-video__time');
      this.playBtn = container.querySelector('.interactive-video__play-btn');

      this.init();
    }

    /**
     * Inicializa el reproductor y los event listeners.
     */
    init() {
      // Ordenar checkpoints por timestamp.
      this.checkpoints.sort((a, b) => a.timestamp - b.timestamp);

      // Eventos del video.
      this.videoEl.addEventListener('timeupdate', () => this.onTimeUpdate());
      this.videoEl.addEventListener('ended', () => this.onVideoEnded());
      this.videoEl.addEventListener('loadedmetadata', () => this.onMetadataLoaded());

      // Controles.
      this.playBtn.addEventListener('click', () => this.togglePlay());

      // Barra de progreso clickeable.
      const progressBar = this.container.querySelector('.interactive-video__progress-bar');
      if (progressBar) {
        progressBar.addEventListener('click', (e) => this.seekTo(e));
      }

      // Capítulos clickeables.
      const chapterItems = this.container.querySelectorAll('.interactive-video__chapter-item');
      chapterItems.forEach(item => {
        item.addEventListener('click', () => {
          const startTime = parseFloat(item.dataset.start);
          this.videoEl.currentTime = startTime;
        });
      });

      // Pantalla completa.
      const fullscreenBtn = this.container.querySelector('.interactive-video__fullscreen-btn');
      if (fullscreenBtn) {
        fullscreenBtn.addEventListener('click', () => this.toggleFullscreen());
      }
    }

    /**
     * Alterna reproduccion/pausa del video.
     */
    togglePlay() {
      if (this.videoEl.paused) {
        this.videoEl.play();
        this.isPlaying = true;
        this.playBtn.classList.add('is-playing');
      }
      else {
        this.videoEl.pause();
        this.isPlaying = false;
        this.playBtn.classList.remove('is-playing');
      }
    }

    /**
     * Actualiza la interfaz en cada frame del video.
     * Verifica si se ha alcanzado un checkpoint.
     */
    onTimeUpdate() {
      const currentTime = this.videoEl.currentTime;
      const duration = this.videoEl.duration;

      // Actualizar barra de progreso.
      if (duration > 0) {
        const progress = (currentTime / duration) * 100;
        this.progressFill.style.width = progress + '%';
      }

      // Actualizar display de tiempo.
      this.timeDisplay.textContent = this.formatTime(currentTime) + ' / ' + this.formatTime(duration);

      // Verificar checkpoints.
      this.checkForCheckpoint(currentTime);
    }

    /**
     * Verifica si se ha alcanzado un checkpoint y lo activa.
     *
     * @param {number} currentTime - Tiempo actual del video en segundos.
     */
    checkForCheckpoint(currentTime) {
      for (const checkpoint of this.checkpoints) {
        if (this.completedCheckpoints.has(checkpoint.id)) {
          continue;
        }

        // Tolerancia de 0.5 segundos.
        if (Math.abs(currentTime - checkpoint.timestamp) < 0.5) {
          this.activateCheckpoint(checkpoint);
          break;
        }
      }
    }

    /**
     * Activa un checkpoint: pausa el video y muestra el overlay.
     *
     * @param {Object} checkpoint - Datos del checkpoint.
     */
    activateCheckpoint(checkpoint) {
      this.videoEl.pause();
      this.isPlaying = false;

      const overlayTitle = this.overlay.querySelector('.interactive-video__overlay-title');
      const overlayBody = this.overlay.querySelector('.interactive-video__overlay-body');
      const overlayActions = this.overlay.querySelector('.interactive-video__overlay-actions');

      overlayTitle.textContent = checkpoint.title || Drupal.t('Checkpoint');
      overlayBody.innerHTML = '';
      overlayActions.innerHTML = '';

      switch (checkpoint.type) {
        case 'quiz':
          this.renderQuizCheckpoint(checkpoint, overlayBody, overlayActions);
          break;
        case 'overlay':
          this.renderOverlayCheckpoint(checkpoint, overlayBody, overlayActions);
          break;
        case 'decision':
          this.renderDecisionCheckpoint(checkpoint, overlayBody, overlayActions);
          break;
      }

      this.overlay.style.display = 'flex';
    }

    /**
     * Renderiza un checkpoint de tipo quiz.
     */
    renderQuizCheckpoint(checkpoint, body, actions) {
      const content = checkpoint.content || {};
      const question = document.createElement('p');
      question.className = 'interactive-video__quiz-question';
      question.textContent = content.question || '';
      body.appendChild(question);

      const optionsContainer = document.createElement('div');
      optionsContainer.className = 'interactive-video__quiz-options';

      (content.options || []).forEach(option => {
        const label = document.createElement('label');
        label.className = 'interactive-video__quiz-option';
        label.innerHTML =
          '<input type="radio" name="checkpoint-' + checkpoint.id + '" value="' + option.id + '">' +
          '<span>' + option.text + '</span>';
        optionsContainer.appendChild(label);
      });
      body.appendChild(optionsContainer);

      const submitBtn = document.createElement('button');
      submitBtn.className = 'interactive-video__quiz-submit';
      submitBtn.textContent = Drupal.t('Responder');
      submitBtn.addEventListener('click', () => {
        const selected = body.querySelector('input[name="checkpoint-' + checkpoint.id + '"]:checked');
        if (selected) {
          this.responses[checkpoint.id] = selected.value;
          this.completeCheckpoint(checkpoint);
        }
      });
      actions.appendChild(submitBtn);
    }

    /**
     * Renderiza un checkpoint de tipo overlay informativo.
     */
    renderOverlayCheckpoint(checkpoint, body, actions) {
      const content = checkpoint.content || {};
      if (content.text) {
        const text = document.createElement('p');
        text.textContent = content.text;
        body.appendChild(text);
      }
      if (content.image_url) {
        const img = document.createElement('img');
        img.src = content.image_url;
        img.alt = checkpoint.title || '';
        body.appendChild(img);
      }

      const continueBtn = document.createElement('button');
      continueBtn.className = 'interactive-video__continue';
      continueBtn.textContent = Drupal.t('Continuar');
      continueBtn.addEventListener('click', () => this.completeCheckpoint(checkpoint));
      actions.appendChild(continueBtn);
    }

    /**
     * Renderiza un checkpoint de tipo decision.
     */
    renderDecisionCheckpoint(checkpoint, body, actions) {
      const content = checkpoint.content || {};
      if (content.text) {
        const text = document.createElement('p');
        text.textContent = content.text;
        body.appendChild(text);
      }

      (content.options || []).forEach(option => {
        const btn = document.createElement('button');
        btn.className = 'interactive-video__decision-option';
        btn.textContent = option.text;
        btn.addEventListener('click', () => {
          this.responses[checkpoint.id] = option.id;
          this.completeCheckpoint(checkpoint);
        });
        actions.appendChild(btn);
      });
    }

    /**
     * Marca un checkpoint como completado y reanuda el video.
     *
     * @param {Object} checkpoint - Datos del checkpoint completado.
     */
    completeCheckpoint(checkpoint) {
      this.completedCheckpoints.add(checkpoint.id);
      this.overlay.style.display = 'none';

      // Reanudar video.
      this.videoEl.play();
      this.isPlaying = true;
    }

    /**
     * Maneja el fin del video.
     */
    onVideoEnded() {
      this.isPlaying = false;
      this.playBtn.classList.remove('is-playing');

      // Emitir evento de completitud.
      const event = new CustomEvent('interactive:completed', {
        detail: { responses: this.responses, type: 'interactive_video' }
      });
      this.container.dispatchEvent(event);
    }

    /**
     * Formatea segundos a MM:SS.
     *
     * @param {number} seconds - Tiempo en segundos.
     * @return {string} Tiempo formateado.
     */
    formatTime(seconds) {
      if (isNaN(seconds)) return '0:00';
      const mins = Math.floor(seconds / 60);
      const secs = Math.floor(seconds % 60);
      return mins + ':' + (secs < 10 ? '0' : '') + secs;
    }

    /**
     * Navega a una posicion en la barra de progreso.
     *
     * @param {MouseEvent} e - Evento de click.
     */
    seekTo(e) {
      if (!this.settings.allow_rewind && e.offsetX / e.target.offsetWidth * this.videoEl.duration < this.videoEl.currentTime) {
        return;
      }
      const rect = e.target.getBoundingClientRect();
      const percent = (e.clientX - rect.left) / rect.width;
      this.videoEl.currentTime = percent * this.videoEl.duration;
    }

    /**
     * Alterna modo pantalla completa.
     */
    toggleFullscreen() {
      if (document.fullscreenElement) {
        document.exitFullscreen();
      }
      else {
        this.container.requestFullscreen();
      }
    }

    /**
     * Actualiza la interfaz cuando se cargan los metadatos del video.
     */
    onMetadataLoaded() {
      this.timeDisplay.textContent = '0:00 / ' + this.formatTime(this.videoEl.duration);
    }

    /**
     * Obtiene las respuestas recolectadas.
     *
     * @return {Object} Mapa de respuestas por checkpoint ID.
     */
    getResponses() {
      return this.responses;
    }
  };

  /**
   * Behavior para inicializar el reproductor de video interactivo.
   */
  Drupal.behaviors.interactiveVideoEngine = {
    attach: function (context) {
      once('interactive-video', '.interactive-video', context).forEach(function (element) {
        element._engine = new Drupal.InteractiveVideoEngine(element);
      });
    }
  };

})(Drupal, once);

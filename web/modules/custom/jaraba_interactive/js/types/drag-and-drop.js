/**
 * @file
 * Motor JS del ejercicio de arrastrar y soltar.
 *
 * Estructura: Gestiona el drag-and-drop nativo de HTML5 con zonas
 * de destino e items arrastrables. Soporta snap-to-zone y
 * feedback visual inmediato o al finalizar.
 *
 * Logica: Los items se arrastran desde el panel a las zonas.
 * Se valida la colocacion contra las correct_zones definidas.
 * El feedback puede ser inmediato o mostrado al verificar.
 *
 * Sintaxis: Clase DragAndDropEngine con Drupal.behaviors.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Motor del ejercicio drag-and-drop.
   *
   * @param {HTMLElement} container - Contenedor .drag-and-drop
   */
  Drupal.DragAndDropEngine = class {
    constructor(container) {
      this.container = container;
      this.settings = JSON.parse(container.dataset.settings || '{}');

      // Estado de colocacion: {itemId: zoneId}.
      this.placements = {};
      this.isChecked = false;

      // Referencias DOM.
      this.zones = container.querySelectorAll('.drag-and-drop__zone');
      this.items = container.querySelectorAll('.drag-and-drop__item');
      this.itemsPanel = container.querySelector('.drag-and-drop__items');
      this.checkBtn = container.querySelector('.drag-and-drop__check-btn');
      this.resetBtn = container.querySelector('.drag-and-drop__reset-btn');
      this.feedbackPanel = container.querySelector('.drag-and-drop__feedback');

      this.init();
    }

    /**
     * Inicializa los listeners de drag-and-drop.
     */
    init() {
      // Configurar items como draggables.
      this.items.forEach(item => {
        item.addEventListener('dragstart', (e) => this.onDragStart(e));
        item.addEventListener('dragend', (e) => this.onDragEnd(e));

        // Soporte tactil.
        item.addEventListener('touchstart', (e) => this.onTouchStart(e), { passive: false });
        item.addEventListener('touchmove', (e) => this.onTouchMove(e), { passive: false });
        item.addEventListener('touchend', (e) => this.onTouchEnd(e));
      });

      // Configurar zonas como drop targets.
      this.zones.forEach(zone => {
        zone.addEventListener('dragover', (e) => this.onDragOver(e));
        zone.addEventListener('dragenter', (e) => this.onDragEnter(e));
        zone.addEventListener('dragleave', (e) => this.onDragLeave(e));
        zone.addEventListener('drop', (e) => this.onDrop(e));
      });

      // Controles.
      if (this.checkBtn) {
        this.checkBtn.addEventListener('click', () => this.checkAnswers());
      }
      if (this.resetBtn) {
        this.resetBtn.addEventListener('click', () => this.reset());
      }
    }

    /**
     * Maneja el inicio del arrastre.
     *
     * @param {DragEvent} e - Evento de drag.
     */
    onDragStart(e) {
      if (this.isChecked) return;

      const item = e.currentTarget;
      e.dataTransfer.setData('text/plain', item.dataset.itemId);
      e.dataTransfer.effectAllowed = 'move';
      item.classList.add('is-dragging');
    }

    /**
     * Maneja el fin del arrastre.
     *
     * @param {DragEvent} e - Evento de drag.
     */
    onDragEnd(e) {
      e.currentTarget.classList.remove('is-dragging');
    }

    /**
     * Permite el drop en la zona.
     *
     * @param {DragEvent} e - Evento de drag.
     */
    onDragOver(e) {
      e.preventDefault();
      e.dataTransfer.dropEffect = 'move';
    }

    /**
     * Visual feedback al entrar en una zona.
     *
     * @param {DragEvent} e - Evento de drag.
     */
    onDragEnter(e) {
      e.preventDefault();
      if (this.settings.highlight_zones !== false) {
        e.currentTarget.classList.add('is-highlight');
      }
    }

    /**
     * Quita el highlight al salir de una zona.
     *
     * @param {DragEvent} e - Evento de drag.
     */
    onDragLeave(e) {
      e.currentTarget.classList.remove('is-highlight');
    }

    /**
     * Maneja el drop de un item en una zona.
     *
     * @param {DragEvent} e - Evento de drop.
     */
    onDrop(e) {
      e.preventDefault();
      const zone = e.currentTarget;
      zone.classList.remove('is-highlight');

      const itemId = e.dataTransfer.getData('text/plain');
      const item = this.container.querySelector('[data-item-id="' + itemId + '"]');

      if (!item) return;

      const zoneId = zone.dataset.zoneId;

      // Verificar si la zona acepta multiples items.
      if (!zone.dataset.acceptsMultiple && zone.querySelector('.drag-and-drop__item')) {
        // Devolver el item existente al panel.
        const existingItem = zone.querySelector('.drag-and-drop__item');
        this.itemsPanel.appendChild(existingItem);
        delete this.placements[existingItem.dataset.itemId];
      }

      // Mover item a la zona.
      const zoneItems = zone.querySelector('.drag-and-drop__zone-items');
      zoneItems.appendChild(item);
      this.placements[itemId] = zoneId;

      // Feedback inmediato si esta configurado.
      if (this.settings.show_feedback === 'immediate') {
        this.checkSingleItem(item, zoneId);
      }
    }

    /**
     * Verifica un solo item (feedback inmediato).
     *
     * @param {HTMLElement} item - Elemento del item.
     * @param {string} zoneId - ID de la zona donde fue colocado.
     */
    checkSingleItem(item, zoneId) {
      const correctZones = JSON.parse(item.dataset.correctZones || '[]');
      const isCorrect = correctZones.includes(zoneId);

      item.classList.toggle('is-correct', isCorrect);
      item.classList.toggle('is-incorrect', !isCorrect);
    }

    /**
     * Verifica todas las respuestas.
     */
    checkAnswers() {
      this.isChecked = true;
      let correctCount = 0;
      let totalCount = this.items.length;

      this.items.forEach(item => {
        const itemId = item.dataset.itemId;
        const correctZones = JSON.parse(item.dataset.correctZones || '[]');
        const placedZone = this.placements[itemId] || null;
        const isCorrect = placedZone !== null && correctZones.includes(placedZone);

        item.classList.toggle('is-correct', isCorrect);
        item.classList.toggle('is-incorrect', !isCorrect);

        if (isCorrect) correctCount++;
      });

      // Mostrar resultado.
      if (this.feedbackPanel) {
        const percentage = totalCount > 0 ? Math.round((correctCount / totalCount) * 100) : 0;
        this.feedbackPanel.innerHTML =
          '<p class="drag-and-drop__feedback-score">' +
          correctCount + ' / ' + totalCount + ' ' + Drupal.t('correctos') +
          ' (' + percentage + '%)</p>';
        this.feedbackPanel.style.display = 'block';
      }

      // Emitir evento de completitud.
      const event = new CustomEvent('interactive:completed', {
        detail: { responses: this.placements, type: 'drag_and_drop' }
      });
      this.container.dispatchEvent(event);
    }

    /**
     * Reinicia el ejercicio.
     */
    reset() {
      this.isChecked = false;
      this.placements = {};

      // Devolver todos los items al panel.
      this.items.forEach(item => {
        item.classList.remove('is-correct', 'is-incorrect', 'is-dragging');
        this.itemsPanel.appendChild(item);
      });

      if (this.feedbackPanel) {
        this.feedbackPanel.style.display = 'none';
      }
    }

    // --- Soporte tactil basico ---

    /**
     * Maneja el inicio del toque.
     *
     * @param {TouchEvent} e - Evento tactil.
     */
    onTouchStart(e) {
      if (this.isChecked) return;
      const item = e.currentTarget;
      item.classList.add('is-dragging');
      this._touchItem = item;
      this._touchStartX = e.touches[0].clientX;
      this._touchStartY = e.touches[0].clientY;
    }

    /**
     * Maneja el movimiento tactil.
     *
     * @param {TouchEvent} e - Evento tactil.
     */
    onTouchMove(e) {
      e.preventDefault();
      if (!this._touchItem) return;

      const touch = e.touches[0];
      const dx = touch.clientX - this._touchStartX;
      const dy = touch.clientY - this._touchStartY;
      this._touchItem.style.transform = 'translate(' + dx + 'px, ' + dy + 'px)';
    }

    /**
     * Maneja el fin del toque.
     *
     * @param {TouchEvent} e - Evento tactil.
     */
    onTouchEnd(e) {
      if (!this._touchItem) return;

      this._touchItem.style.transform = '';
      this._touchItem.classList.remove('is-dragging');

      // Detectar zona bajo el punto de soltar.
      const touch = e.changedTouches[0];
      const dropTarget = document.elementFromPoint(touch.clientX, touch.clientY);
      const zone = dropTarget ? dropTarget.closest('.drag-and-drop__zone') : null;

      if (zone) {
        const zoneId = zone.dataset.zoneId;
        const itemId = this._touchItem.dataset.itemId;
        const zoneItems = zone.querySelector('.drag-and-drop__zone-items');
        zoneItems.appendChild(this._touchItem);
        this.placements[itemId] = zoneId;
      }

      this._touchItem = null;
    }

    /**
     * Obtiene las respuestas (colocaciones).
     *
     * @return {Object} Mapa {itemId: zoneId}.
     */
    getResponses() {
      return this.placements;
    }
  };

  /**
   * Behavior para inicializar el ejercicio drag-and-drop.
   */
  Drupal.behaviors.dragAndDropEngine = {
    attach: function (context) {
      once('drag-and-drop', '.drag-and-drop', context).forEach(function (element) {
        element._engine = new Drupal.DragAndDropEngine(element);
      });
    }
  };

})(Drupal, once);

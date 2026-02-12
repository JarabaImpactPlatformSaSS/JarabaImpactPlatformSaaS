/**
 * @file
 * Motor JS del escenario ramificado.
 *
 * Estructura: Gestiona la navegacion por un arbol de decisiones
 * con nodos conectados. Cada nodo presenta opciones que llevan
 * a diferentes caminos con puntuacion acumulada.
 *
 * Logica: El usuario navega eligiendo opciones. Se acumulan puntos
 * segun la calidad de las decisiones. Al llegar a un nodo terminal
 * se muestra la puntuacion final y el camino recorrido.
 *
 * Sintaxis: Clase BranchingScenarioEngine con Drupal.behaviors.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Motor del escenario ramificado.
   *
   * @param {HTMLElement} container - Contenedor .branching-scenario
   */
  Drupal.BranchingScenarioEngine = class {
    constructor(container) {
      this.container = container;
      this.startNode = container.dataset.startNode;
      this.nodes = JSON.parse(container.dataset.nodes || '[]');
      this.settings = JSON.parse(container.dataset.settings || '{}');

      // Indexar nodos por ID para acceso rapido.
      this.nodesById = {};
      this.nodes.forEach(node => {
        this.nodesById[node.id] = node;
      });

      // Estado del recorrido.
      this.path = [];
      this.totalScore = 0;
      this.currentNodeId = this.startNode;

      // Referencias DOM.
      this.pathTrail = container.querySelector('.branching-scenario__path-trail');
      this.scoreValue = container.querySelector('.branching-scenario__score-value');
      this.restartBtn = container.querySelector('.branching-scenario__restart');
      this.scoreContainer = container.querySelector('.branching-scenario__score');

      this.init();
    }

    /**
     * Inicializa los event listeners.
     */
    init() {
      // Listeners para opciones de decision.
      this.container.querySelectorAll('.branching-scenario__option').forEach(btn => {
        btn.addEventListener('click', (e) => this.onOptionClick(e));
      });

      // Boton de reinicio.
      if (this.restartBtn) {
        this.restartBtn.addEventListener('click', () => this.restart());
      }

      // Mostrar nodo inicial.
      this.showNode(this.startNode);
    }

    /**
     * Muestra un nodo especifico y oculta los demas.
     *
     * @param {string} nodeId - ID del nodo a mostrar.
     */
    showNode(nodeId) {
      // Ocultar todos los nodos.
      this.container.querySelectorAll('.branching-scenario__node').forEach(node => {
        node.classList.remove('is-active');
      });

      // Mostrar el nodo seleccionado.
      const nodeEl = this.container.querySelector('[data-node-id="' + nodeId + '"]');
      if (nodeEl) {
        nodeEl.classList.add('is-active');
        this.currentNodeId = nodeId;

        // Si es nodo terminal, mostrar controles finales.
        if (nodeEl.dataset.isEnd === 'true') {
          this.onScenarioEnd(nodeEl);
        }
      }

      // Actualizar visualizacion del camino.
      this.updatePathVisualization();
    }

    /**
     * Maneja el click en una opcion de decision.
     *
     * @param {Event} e - Evento de click.
     */
    onOptionClick(e) {
      const btn = e.currentTarget;
      const optionId = btn.dataset.optionId;
      const targetNode = btn.dataset.target;
      const points = parseInt(btn.dataset.points || '0', 10);

      // Registrar la decision en el camino.
      this.path.push({
        node_id: this.currentNodeId,
        option_id: optionId,
        points: points,
      });

      this.totalScore += points;

      // Mostrar feedback si esta configurado.
      if (this.settings.show_score_per_decision) {
        this.showDecisionFeedback(btn, points);
      }

      // Navegar al nodo destino.
      this.showNode(targetNode);
    }

    /**
     * Muestra feedback visual por decision.
     *
     * @param {HTMLElement} btn - Boton de la opcion elegida.
     * @param {number} points - Puntos obtenidos.
     */
    showDecisionFeedback(btn, points) {
      const feedback = document.createElement('span');
      feedback.className = 'branching-scenario__feedback';
      feedback.textContent = points > 0 ? '+' + points : points.toString();
      feedback.classList.add(points > 0 ? 'is-positive' : 'is-neutral');
      btn.appendChild(feedback);

      setTimeout(() => feedback.remove(), 1500);
    }

    /**
     * Maneja el fin del escenario (nodo terminal alcanzado).
     *
     * @param {HTMLElement} nodeEl - Elemento del nodo terminal.
     */
    onScenarioEnd(nodeEl) {
      // Mostrar puntuacion.
      if (this.scoreContainer) {
        this.scoreContainer.style.display = 'flex';
        this.scoreValue.textContent = this.totalScore;
      }

      // Mostrar boton de reinicio.
      if (this.restartBtn) {
        this.restartBtn.style.display = 'flex';
      }

      // Emitir evento de completitud.
      const event = new CustomEvent('interactive:completed', {
        detail: {
          responses: { path: this.path },
          type: 'branching_scenario',
          total_score: this.totalScore,
        }
      });
      this.container.dispatchEvent(event);
    }

    /**
     * Actualiza la visualizacion del camino recorrido.
     */
    updatePathVisualization() {
      if (!this.pathTrail) {
        return;
      }

      this.pathTrail.innerHTML = '';
      this.path.forEach((decision, index) => {
        const node = this.nodesById[decision.node_id];
        if (!node) return;

        const step = document.createElement('div');
        step.className = 'branching-scenario__path-step';
        step.innerHTML =
          '<span class="branching-scenario__path-number">' + (index + 1) + '</span>' +
          '<span class="branching-scenario__path-title">' + node.title + '</span>';
        this.pathTrail.appendChild(step);
      });

      // Agregar nodo actual.
      const currentNode = this.nodesById[this.currentNodeId];
      if (currentNode) {
        const current = document.createElement('div');
        current.className = 'branching-scenario__path-step is-current';
        current.innerHTML =
          '<span class="branching-scenario__path-number">' + (this.path.length + 1) + '</span>' +
          '<span class="branching-scenario__path-title">' + currentNode.title + '</span>';
        this.pathTrail.appendChild(current);
      }
    }

    /**
     * Reinicia el escenario al nodo inicial.
     */
    restart() {
      this.path = [];
      this.totalScore = 0;

      if (this.scoreContainer) {
        this.scoreContainer.style.display = 'none';
      }
      if (this.restartBtn) {
        this.restartBtn.style.display = 'none';
      }

      this.showNode(this.startNode);
    }

    /**
     * Obtiene las respuestas (camino recorrido).
     *
     * @return {Object} Objeto con path y total_score.
     */
    getResponses() {
      return { path: this.path };
    }
  };

  /**
   * Behavior para inicializar el escenario ramificado.
   */
  Drupal.behaviors.branchingScenarioEngine = {
    attach: function (context) {
      once('branching-scenario', '.branching-scenario', context).forEach(function (element) {
        element._engine = new Drupal.BranchingScenarioEngine(element);
      });
    }
  };

})(Drupal, once);

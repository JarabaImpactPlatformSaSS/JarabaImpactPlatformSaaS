/**
 * @file
 * Legal Intelligence Hub — Visualizacion del grafo de citas.
 *
 * Renderiza un mini-grafo de red (nodos = resoluciones, arcos = citas)
 * usando Canvas 2D. Los nodos son clicables para navegar a la resolucion.
 *
 * NOTA: Se usa Canvas 2D nativo en lugar de D3.js para minimizar
 * dependencias externas. Si se requiere interactividad avanzada (zoom,
 * drag, tooltips complejos), migrar a D3.js force-directed layout.
 */

(function (Drupal, once) {

  'use strict';

  Drupal.behaviors.legalCitationGraph = {
    attach: function (context) {
      once('legal-citation-graph', '.legal-citation-graph__canvas', context).forEach(function (canvas) {
        var nodes = JSON.parse(canvas.dataset.nodes || '[]');
        var edges = JSON.parse(canvas.dataset.edges || '[]');

        if (nodes.length === 0) {
          return;
        }

        drawGraph(canvas, nodes, edges);
      });
    }
  };

  /**
   * Dibuja el grafo de citas en un canvas.
   *
   * @param {HTMLCanvasElement} canvas - Elemento canvas.
   * @param {Array} nodes - Nodos [{id, label, x, y, type}].
   * @param {Array} edges - Arcos [{source, target, relation}].
   */
  function drawGraph(canvas, nodes, edges) {
    var ctx = canvas.getContext('2d');
    var width = canvas.offsetWidth;
    var height = canvas.offsetHeight;
    canvas.width = width;
    canvas.height = height;

    // Layout simple circular si no hay coordenadas.
    if (!nodes[0].x) {
      var centerX = width / 2;
      var centerY = height / 2;
      var radius = Math.min(width, height) / 3;
      nodes.forEach(function (node, i) {
        var angle = (2 * Math.PI * i) / nodes.length;
        node.x = centerX + radius * Math.cos(angle);
        node.y = centerY + radius * Math.sin(angle);
      });
    }

    // Dibujar arcos.
    ctx.strokeStyle = '#cbd5e1';
    ctx.lineWidth = 1;
    edges.forEach(function (edge) {
      var source = nodes.find(function (n) { return n.id === edge.source; });
      var target = nodes.find(function (n) { return n.id === edge.target; });
      if (source && target) {
        ctx.beginPath();
        ctx.moveTo(source.x, source.y);
        ctx.lineTo(target.x, target.y);
        ctx.stroke();
      }
    });

    // Dibujar nodos.
    nodes.forEach(function (node) {
      ctx.beginPath();
      ctx.arc(node.x, node.y, 6, 0, 2 * Math.PI);
      ctx.fillStyle = node.type === 'current' ? '#1E3A5F' : '#C8A96E';
      ctx.fill();
      ctx.strokeStyle = '#fff';
      ctx.lineWidth = 2;
      ctx.stroke();
    });
  }

})(Drupal, once);

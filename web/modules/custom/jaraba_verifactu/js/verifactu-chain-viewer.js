/**
 * @file
 * VeriFactu hash chain visualization using Canvas 2D.
 *
 * Renders a visual representation of the SHA-256 hash chain
 * showing linked nodes with arrows, colored by verification status.
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Visual configuration constants.
   */
  var CONFIG = {
    nodeWidth: 160,
    nodeHeight: 64,
    nodeRadius: 8,
    nodePadding: 16,
    arrowGap: 40,
    colors: {
      valid: '#0891B2',
      broken: '#DC2626',
      pending: '#F59E0B',
      background: '#FFFFFF',
      border: '#E5E7EB',
      text: '#1A1A2E',
      textSecondary: '#6B7280',
      hashText: '#6B7280',
    },
    fonts: {
      label: '600 12px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
      hash: '11px ui-monospace, "Cascadia Code", "Fira Code", Menlo, monospace',
      number: '600 13px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
    },
  };

  /**
   * Chain viewer behavior.
   */
  Drupal.behaviors.verifactuChainViewer = {
    attach: function (context) {
      var canvases = once('verifactu-chain-viewer', '[data-verifactu-chain-canvas]', context);
      canvases.forEach(function (canvas) {
        Drupal.behaviors.verifactuChainViewer._initCanvas(canvas);
      });
    },

    /**
     * Initializes the chain viewer canvas.
     */
    _initCanvas: function (canvas) {
      var dataAttr = canvas.getAttribute('data-verifactu-chain-canvas');
      var records;

      try {
        records = JSON.parse(dataAttr);
      }
      catch (e) {
        return;
      }

      if (!Array.isArray(records) || records.length === 0) {
        return;
      }

      var ctx = canvas.getContext('2d');
      if (!ctx) {
        return;
      }

      // Calculate canvas dimensions.
      var totalWidth = records.length * (CONFIG.nodeWidth + CONFIG.arrowGap) - CONFIG.arrowGap + CONFIG.nodePadding * 2;
      var totalHeight = CONFIG.nodeHeight + CONFIG.nodePadding * 2;

      // Handle high-DPI displays.
      var dpr = window.devicePixelRatio || 1;
      canvas.width = totalWidth * dpr;
      canvas.height = totalHeight * dpr;
      canvas.style.width = totalWidth + 'px';
      canvas.style.height = totalHeight + 'px';
      ctx.scale(dpr, dpr);

      // Enable scrolling if canvas is wider than container.
      canvas.parentElement.style.overflowX = 'auto';

      this._drawChain(ctx, records, totalWidth, totalHeight);
    },

    /**
     * Draws the complete hash chain visualization.
     */
    _drawChain: function (ctx, records, width, height) {
      // Clear canvas.
      ctx.fillStyle = CONFIG.colors.background;
      ctx.fillRect(0, 0, width, height);

      var startX = CONFIG.nodePadding;
      var startY = CONFIG.nodePadding;

      for (var i = 0; i < records.length; i++) {
        var record = records[i];
        var x = startX + i * (CONFIG.nodeWidth + CONFIG.arrowGap);

        // Draw arrow between nodes (except before first).
        if (i > 0) {
          this._drawArrow(
            ctx,
            x - CONFIG.arrowGap,
            startY + CONFIG.nodeHeight / 2,
            x,
            startY + CONFIG.nodeHeight / 2,
            record.chain_valid !== false ? CONFIG.colors.valid : CONFIG.colors.broken
          );
        }

        // Draw node.
        this._drawNode(ctx, x, startY, record);
      }
    },

    /**
     * Draws a single chain node (record block).
     */
    _drawNode: function (ctx, x, y, record) {
      var status = record.chain_valid !== false ? 'valid' : 'broken';
      var borderColor = status === 'valid' ? CONFIG.colors.valid : CONFIG.colors.broken;

      // Rounded rectangle.
      ctx.beginPath();
      ctx.roundRect(x, y, CONFIG.nodeWidth, CONFIG.nodeHeight, CONFIG.nodeRadius);
      ctx.fillStyle = CONFIG.colors.background;
      ctx.fill();
      ctx.strokeStyle = borderColor;
      ctx.lineWidth = 2;
      ctx.stroke();

      // Top border accent.
      ctx.beginPath();
      ctx.moveTo(x + CONFIG.nodeRadius, y);
      ctx.lineTo(x + CONFIG.nodeWidth - CONFIG.nodeRadius, y);
      ctx.strokeStyle = borderColor;
      ctx.lineWidth = 3;
      ctx.stroke();

      // Invoice number.
      ctx.font = CONFIG.fonts.number;
      ctx.fillStyle = CONFIG.colors.text;
      ctx.textAlign = 'center';
      ctx.fillText(
        record.numero_factura || '#' + (record.id || '?'),
        x + CONFIG.nodeWidth / 2,
        y + 22
      );

      // Hash (abbreviated).
      ctx.font = CONFIG.fonts.hash;
      ctx.fillStyle = CONFIG.colors.hashText;
      var hash = record.hash_record || '';
      ctx.fillText(
        hash.substring(0, 12) + (hash.length > 12 ? '...' : ''),
        x + CONFIG.nodeWidth / 2,
        y + 38
      );

      // Status label.
      ctx.font = CONFIG.fonts.label;
      ctx.fillStyle = borderColor;
      ctx.fillText(
        status === 'valid' ? '\u2713' : '\u2717',
        x + CONFIG.nodeWidth / 2,
        y + 54
      );
    },

    /**
     * Draws an arrow between two chain nodes.
     */
    _drawArrow: function (ctx, fromX, fromY, toX, toY, color) {
      var headLength = 8;

      ctx.beginPath();
      ctx.moveTo(fromX, fromY);
      ctx.lineTo(toX - headLength, toY);
      ctx.strokeStyle = color;
      ctx.lineWidth = 2;
      ctx.stroke();

      // Arrowhead.
      ctx.beginPath();
      ctx.moveTo(toX, toY);
      ctx.lineTo(toX - headLength, toY - headLength / 2);
      ctx.lineTo(toX - headLength, toY + headLength / 2);
      ctx.closePath();
      ctx.fillStyle = color;
      ctx.fill();
    },
  };

})(Drupal, drupalSettings, once);

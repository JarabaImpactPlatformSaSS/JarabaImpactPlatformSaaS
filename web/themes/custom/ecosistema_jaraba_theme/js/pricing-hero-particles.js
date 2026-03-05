/**
 * @file
 * Pricing Hero Particles — Canvas-based particle network for pricing pages.
 *
 * Renders floating particles with connecting lines using corporate palette.
 * Follows the same architecture as blog-hero-particles.js.
 *
 * Performance:
 * - IntersectionObserver pauses animation when off-screen
 * - prefers-reduced-motion respected
 * - Spatial grid optimization for O(n) connections
 *
 * @see blog-hero-particles.js
 */

(function (Drupal) {
  'use strict';

  Drupal.behaviors.pricingHeroParticles = {
    attach: function (context) {
      var canvases = context.querySelectorAll
        ? context.querySelectorAll('.pricing-hero__particles, .pricing-hub-hero__particles')
        : [];
      if (!canvases.length) return;

      // Respect reduced motion preference.
      if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
      }

      canvases.forEach(function (canvas) {
        if (canvas.dataset.initialized) return;
        canvas.dataset.initialized = 'true';

        var ctx = canvas.getContext('2d');

        // Resize canvas to fill container.
        function resize() {
          canvas.width = canvas.offsetWidth;
          canvas.height = canvas.offsetHeight;
        }
        resize();
        window.addEventListener('resize', resize);

        var animationId = null;
        var isVisible = true;

        // Corporate palette particles — subtle white/naranja on azul-corporativo.
        var particleCount = 35;
        var connectionDistance = 110;
        var colors = [
          'rgba(255, 255, 255, 0.30)',
          'rgba(255, 255, 255, 0.20)',
          'rgba(255, 140, 66, 0.20)',
          'rgba(255, 140, 66, 0.12)',
          'rgba(0, 169, 165, 0.15)'
        ];

        // Create initial particles.
        var particles = [];
        for (var i = 0; i < particleCount; i++) {
          particles.push({
            x: Math.random() * (canvas.width || 800),
            y: Math.random() * (canvas.height || 400),
            radius: Math.random() * 2 + 0.6,
            color: colors[Math.floor(Math.random() * colors.length)],
            vx: (Math.random() - 0.5) * 0.3,
            vy: (Math.random() - 0.5) * 0.25
          });
        }

        // Spatial grid for O(n) connection drawing.
        function buildGrid() {
          var grid = {};
          var cellSize = connectionDistance;
          particles.forEach(function (p, idx) {
            var key = Math.floor(p.x / cellSize) + ',' + Math.floor(p.y / cellSize);
            if (!grid[key]) grid[key] = [];
            grid[key].push(idx);
          });
          return grid;
        }

        // Animation loop.
        function animate() {
          if (!isVisible) {
            animationId = requestAnimationFrame(animate);
            return;
          }

          ctx.clearRect(0, 0, canvas.width, canvas.height);

          // Update and draw particles.
          particles.forEach(function (p) {
            p.x += p.vx;
            p.y += p.vy;

            // Wrap around edges.
            if (p.x < 0) p.x = canvas.width;
            if (p.x > canvas.width) p.x = 0;
            if (p.y < 0) p.y = canvas.height;
            if (p.y > canvas.height) p.y = 0;

            ctx.beginPath();
            ctx.arc(p.x, p.y, p.radius, 0, Math.PI * 2);
            ctx.fillStyle = p.color;
            ctx.fill();
          });

          // Draw connections using spatial grid.
          var grid = buildGrid();
          var maxDistSq = connectionDistance * connectionDistance;
          var cellSize = connectionDistance;

          particles.forEach(function (p, idx) {
            var cx = Math.floor(p.x / cellSize);
            var cy = Math.floor(p.y / cellSize);

            for (var gx = -1; gx <= 1; gx++) {
              for (var gy = -1; gy <= 1; gy++) {
                var neighbors = grid[(cx + gx) + ',' + (cy + gy)];
                if (!neighbors) continue;

                neighbors.forEach(function (nIdx) {
                  if (nIdx <= idx) return;
                  var n = particles[nIdx];
                  var dx = p.x - n.x;
                  var dy = p.y - n.y;
                  var distSq = dx * dx + dy * dy;

                  if (distSq < maxDistSq) {
                    var dist = Math.sqrt(distSq);
                    var opacity = 0.08 * (1 - dist / connectionDistance);
                    ctx.beginPath();
                    ctx.moveTo(p.x, p.y);
                    ctx.lineTo(n.x, n.y);
                    ctx.strokeStyle = 'rgba(255, 255, 255, ' + opacity + ')';
                    ctx.lineWidth = 0.5;
                    ctx.stroke();
                  }
                });
              }
            }
          });

          animationId = requestAnimationFrame(animate);
        }

        // Pause animation when off-screen.
        var observer = new IntersectionObserver(function (entries) {
          isVisible = entries[0].isIntersecting;
        }, { threshold: 0.1 });
        observer.observe(canvas);

        animate();

        // Store references for cleanup.
        canvas._animationId = animationId;
        canvas._resizeHandler = resize;
        canvas._observer = observer;
      });
    },

    detach: function (context, settings, trigger) {
      if (trigger !== 'unload') return;
      var canvases = context.querySelectorAll
        ? context.querySelectorAll('.pricing-hero__particles, .pricing-hub-hero__particles')
        : [];
      canvases.forEach(function (canvas) {
        if (canvas._animationId) {
          cancelAnimationFrame(canvas._animationId);
        }
        if (canvas._resizeHandler) {
          window.removeEventListener('resize', canvas._resizeHandler);
        }
        if (canvas._observer) {
          canvas._observer.disconnect();
        }
        delete canvas.dataset.initialized;
      });
    }
  };

})(Drupal);

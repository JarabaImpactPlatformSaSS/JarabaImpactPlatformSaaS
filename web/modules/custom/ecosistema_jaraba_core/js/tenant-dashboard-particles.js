/**
 * @file
 * Particle animation for Tenant Dashboard hero header.
 *
 * Renders subtle floating particles on a <canvas> element.
 * Uses requestAnimationFrame for smooth 60fps rendering.
 * Respects prefers-reduced-motion accessibility setting.
 */
(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.tenantDashboardParticles = {
    attach: function (context) {
      once('dashboard-particles', '[data-dashboard-particles]', context).forEach(function (canvas) {
        // Respect reduced motion preference.
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
          return;
        }

        var ctx = canvas.getContext('2d');
        if (!ctx) {
          return;
        }

        var particles = [];
        var PARTICLE_COUNT = 35;
        var animationId = null;

        function resize() {
          var rect = canvas.parentElement.getBoundingClientRect();
          canvas.width = rect.width;
          canvas.height = rect.height;
        }

        function createParticle() {
          return {
            x: Math.random() * canvas.width,
            y: Math.random() * canvas.height,
            radius: Math.random() * 2 + 0.5,
            vx: (Math.random() - 0.5) * 0.4,
            vy: (Math.random() - 0.5) * 0.3,
            opacity: Math.random() * 0.4 + 0.1
          };
        }

        function init() {
          resize();
          particles = [];
          for (var i = 0; i < PARTICLE_COUNT; i++) {
            particles.push(createParticle());
          }
        }

        function draw() {
          ctx.clearRect(0, 0, canvas.width, canvas.height);

          for (var i = 0; i < particles.length; i++) {
            var p = particles[i];

            // Move.
            p.x += p.vx;
            p.y += p.vy;

            // Wrap around edges.
            if (p.x < -5) { p.x = canvas.width + 5; }
            if (p.x > canvas.width + 5) { p.x = -5; }
            if (p.y < -5) { p.y = canvas.height + 5; }
            if (p.y > canvas.height + 5) { p.y = -5; }

            // Draw dot.
            ctx.beginPath();
            ctx.arc(p.x, p.y, p.radius, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(255, 255, 255, ' + p.opacity + ')';
            ctx.fill();
          }

          // Draw connections between nearby particles.
          for (var a = 0; a < particles.length; a++) {
            for (var b = a + 1; b < particles.length; b++) {
              var dx = particles[a].x - particles[b].x;
              var dy = particles[a].y - particles[b].y;
              var dist = Math.sqrt(dx * dx + dy * dy);

              if (dist < 100) {
                var lineOpacity = (1 - dist / 100) * 0.12;
                ctx.beginPath();
                ctx.moveTo(particles[a].x, particles[a].y);
                ctx.lineTo(particles[b].x, particles[b].y);
                ctx.strokeStyle = 'rgba(255, 255, 255, ' + lineOpacity + ')';
                ctx.lineWidth = 0.5;
                ctx.stroke();
              }
            }
          }

          animationId = requestAnimationFrame(draw);
        }

        init();
        draw();

        // Resize handler.
        var resizeTimer;
        window.addEventListener('resize', function () {
          clearTimeout(resizeTimer);
          resizeTimer = setTimeout(function () {
            resize();
          }, 150);
        });

        // Cleanup on detach.
        canvas._dashboardCleanup = function () {
          if (animationId) {
            cancelAnimationFrame(animationId);
          }
        };
      });
    },

    detach: function (context, settings, trigger) {
      if (trigger === 'unload') {
        var canvases = context.querySelectorAll ? context.querySelectorAll('[data-dashboard-particles]') : [];
        canvases.forEach(function (canvas) {
          if (canvas._dashboardCleanup) {
            canvas._dashboardCleanup();
          }
        });
      }
    }
  };

})(Drupal, once);

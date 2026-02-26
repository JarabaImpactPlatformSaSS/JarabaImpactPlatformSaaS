/**
 * @file
 * Blog Hero Particles — Canvas-based particle network animation.
 *
 * Renders floating particles with connecting lines inside the blog hero
 * section. Uses requestAnimationFrame and respects prefers-reduced-motion.
 * Pauses when the canvas is not visible (Intersection Observer).
 */

(function (Drupal) {
  'use strict';

  Drupal.behaviors.blogHeroParticles = {
    attach: function (context) {
      const canvas = context.querySelector('#blog-hero-particles');
      if (!canvas || canvas.dataset.initialized) return;

      // Respect reduced motion preference.
      if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
      }

      canvas.dataset.initialized = 'true';
      const ctx = canvas.getContext('2d');

      // Resize canvas to fill container.
      function resize() {
        canvas.width = canvas.offsetWidth;
        canvas.height = canvas.offsetHeight;
      }
      resize();
      window.addEventListener('resize', resize);

      let animationId = null;
      let isVisible = true;

      // Particle configuration — fewer & subtler than dashboard for hero.
      const particles = [];
      const particleCount = 40;
      const connectionDistance = 120;
      const colors = [
        'rgba(255,255,255,0.35)',
        'rgba(255,255,255,0.25)',
        'rgba(255,255,255,0.15)',
        'rgba(255,255,255,0.10)'
      ];

      // Create initial particles.
      for (let i = 0; i < particleCount; i++) {
        particles.push({
          x: Math.random() * (canvas.width || 800),
          y: Math.random() * (canvas.height || 400),
          radius: Math.random() * 2.5 + 0.8,
          color: colors[Math.floor(Math.random() * colors.length)],
          vx: (Math.random() - 0.5) * 0.4,
          vy: (Math.random() - 0.5) * 0.3
        });
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

        // Draw connections between nearby particles.
        for (var i = 0; i < particles.length; i++) {
          for (var j = i + 1; j < particles.length; j++) {
            var dx = particles[i].x - particles[j].x;
            var dy = particles[i].y - particles[j].y;
            var dist = Math.sqrt(dx * dx + dy * dy);

            if (dist < connectionDistance) {
              ctx.beginPath();
              ctx.moveTo(particles[i].x, particles[i].y);
              ctx.lineTo(particles[j].x, particles[j].y);
              ctx.strokeStyle = 'rgba(255,255,255,' + (0.12 * (1 - dist / connectionDistance)) + ')';
              ctx.lineWidth = 0.6;
              ctx.stroke();
            }
          }
        }

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
    },

    detach: function (context, settings, trigger) {
      if (trigger !== 'unload') return;
      var canvas = context.querySelector('#blog-hero-particles');
      if (!canvas) return;
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
    }
  };

})(Drupal);

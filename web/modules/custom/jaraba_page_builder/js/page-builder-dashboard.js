/**
 * @file
 * JavaScript para el Dashboard Principal del Page Builder.
 *
 * Incluye:
 * - Partículas animadas en el header (corporate blue)
 * - Animación de contadores para estadísticas
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Behavior para partículas del dashboard.
   */
  Drupal.behaviors.pageBuilderParticles = {
    attach: function (context) {
      once('pb-particles', '#page-builder-particles', context).forEach(function (canvas) {
        initParticles(canvas);
      });
    }
  };

  /**
   * Behavior para animación de contadores.
   */
  Drupal.behaviors.pageBuilderCounters = {
    attach: function (context) {
      once('pb-counters', '.dashboard-stat__value[data-count]', context).forEach(function (el) {
        animateCounter(el);
      });
    }
  };

  /**
   * Inicializa partículas en el canvas del header.
   */
  function initParticles(canvas) {
    const ctx = canvas.getContext('2d');
    const particles = [];
    const particleCount = 40;

    // Colores corporativos (azules)
    const colors = [
      'rgba(0, 82, 155, 0.5)',   // --ej-corporate-primary
      'rgba(0, 102, 179, 0.4)',  // --ej-corporate-secondary
      'rgba(59, 130, 246, 0.3)', // blue-500
      'rgba(147, 197, 253, 0.2)' // blue-300
    ];

    // Ajustar tamaño del canvas
    function resize() {
      const rect = canvas.parentElement.getBoundingClientRect();
      canvas.width = rect.width;
      canvas.height = rect.height;
    }
    resize();
    window.addEventListener('resize', resize);

    // Crear partículas
    for (let i = 0; i < particleCount; i++) {
      particles.push({
        x: Math.random() * canvas.width,
        y: Math.random() * canvas.height,
        radius: Math.random() * 3 + 1,
        color: colors[Math.floor(Math.random() * colors.length)],
        vx: (Math.random() - 0.5) * 0.5,
        vy: (Math.random() - 0.5) * 0.5
      });
    }

    // Animación
    function animate() {
      ctx.clearRect(0, 0, canvas.width, canvas.height);

      particles.forEach(function (p) {
        // Mover
        p.x += p.vx;
        p.y += p.vy;

        // Rebotar en bordes
        if (p.x < 0 || p.x > canvas.width) p.vx *= -1;
        if (p.y < 0 || p.y > canvas.height) p.vy *= -1;

        // Dibujar
        ctx.beginPath();
        ctx.arc(p.x, p.y, p.radius, 0, Math.PI * 2);
        ctx.fillStyle = p.color;
        ctx.fill();
      });

      // Dibujar conexiones
      for (let i = 0; i < particles.length; i++) {
        for (let j = i + 1; j < particles.length; j++) {
          const dx = particles[i].x - particles[j].x;
          const dy = particles[i].y - particles[j].y;
          const dist = Math.sqrt(dx * dx + dy * dy);

          if (dist < 120) {
            ctx.beginPath();
            ctx.moveTo(particles[i].x, particles[i].y);
            ctx.lineTo(particles[j].x, particles[j].y);
            ctx.strokeStyle = `rgba(0, 82, 155, ${0.15 * (1 - dist / 120)})`;
            ctx.lineWidth = 0.5;
            ctx.stroke();
          }
        }
      }

      requestAnimationFrame(animate);
    }

    animate();
  }

  /**
   * Anima un contador desde 0 hasta su valor final.
   */
  function animateCounter(el) {
    const target = parseInt(el.dataset.count, 10);
    const duration = 1500;
    const startTime = performance.now();

    function update(currentTime) {
      const elapsed = currentTime - startTime;
      const progress = Math.min(elapsed / duration, 1);
      
      // Easing function (ease-out)
      const eased = 1 - Math.pow(1 - progress, 3);
      const current = Math.floor(eased * target);

      el.textContent = current.toLocaleString('es-ES');

      if (progress < 1) {
        requestAnimationFrame(update);
      } else {
        el.textContent = target.toLocaleString('es-ES');
      }
    }

    requestAnimationFrame(update);
  }

})(Drupal, once);

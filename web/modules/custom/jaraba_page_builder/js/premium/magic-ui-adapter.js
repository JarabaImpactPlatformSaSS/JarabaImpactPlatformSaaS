/**
 * @file
 * Adapter JavaScript para bloques premium Magic UI.
 *
 * Conecta los efectos visuales de Magic UI con Drupal.behaviors.
 * Incluye:
 * - Marquee infinite scroll
 * - Bento Grid hover effects
 * - Particle Hero (canvas particles)
 *
 * DIRECTRICES:
 * - Respeta prefers-reduced-motion
 * - Todos los strings via Drupal.t()
 * - Usa once() para evitar doble attach
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Verifica si el usuario prefiere movimiento reducido.
   *
   * @return {boolean}
   *   TRUE si se debe reducir movimiento.
   */
  function prefersReducedMotion() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  }

  /**
   * Marquee Logos - Duplicacion de contenido para loop infinito.
   *
   * Duplica los items del track para crear el efecto de scroll
   * infinito sin saltos. El CSS animation se encarga del movimiento.
   */
  Drupal.behaviors.jarabaMagicUiMarquee = {
    attach: function (context) {
      once('marquee', '.jaraba-marquee', context).forEach(function (section) {
        var track = section.querySelector('.jaraba-marquee__track');
        if (!track) {
          return;
        }

        // Si prefiere movimiento reducido, no duplicar ni animar.
        if (prefersReducedMotion()) {
          track.style.animation = 'none';
          track.style.flexWrap = 'wrap';
          track.style.justifyContent = 'center';
          return;
        }

        // Duplicar items para loop seamless.
        var items = track.innerHTML;
        track.innerHTML = items + items;

        // Pausar al hover para accesibilidad.
        section.addEventListener('mouseenter', function () {
          track.style.animationPlayState = 'paused';
        });
        section.addEventListener('mouseleave', function () {
          track.style.animationPlayState = 'running';
        });
      });
    }
  };

  /**
   * Bento Grid - Hover spotlight effect.
   *
   * Crea un efecto de spotlight que sigue el cursor sobre
   * los items del bento grid.
   */
  Drupal.behaviors.jarabaMagicUiBentoGrid = {
    attach: function (context) {
      if (prefersReducedMotion()) {
        return;
      }

      once('bentoGrid', '.jaraba-bento-grid__item', context).forEach(function (item) {
        item.addEventListener('mousemove', function (e) {
          var rect = item.getBoundingClientRect();
          var x = e.clientX - rect.left;
          var y = e.clientY - rect.top;
          item.style.setProperty('--spotlight-x', x + 'px');
          item.style.setProperty('--spotlight-y', y + 'px');
        });
      });
    }
  };

  /**
   * Particle Hero - Particulas animadas en canvas.
   *
   * Genera particulas simples que flotan en el fondo de la seccion hero.
   * Rendimiento optimizado: usa requestAnimationFrame y se detiene
   * cuando la seccion no es visible.
   */
  Drupal.behaviors.jarabaMagicUiParticleHero = {
    attach: function (context) {
      if (prefersReducedMotion()) {
        return;
      }

      once('particleHero', '.jaraba-particle-hero', context).forEach(function (section) {
        var canvasContainer = section.querySelector('.jaraba-particle-hero__canvas');
        if (!canvasContainer) {
          return;
        }

        var canvas = document.createElement('canvas');
        canvas.style.cssText = 'position:absolute;inset:0;width:100%;height:100%;';
        canvas.setAttribute('aria-hidden', 'true');
        canvasContainer.appendChild(canvas);

        var ctx = canvas.getContext('2d');
        var particles = [];
        var isVisible = false;
        var animationId = null;
        var particleCount = 50;

        function resize() {
          canvas.width = section.offsetWidth;
          canvas.height = section.offsetHeight;
        }

        function createParticle() {
          return {
            x: Math.random() * canvas.width,
            y: Math.random() * canvas.height,
            size: Math.random() * 3 + 1,
            speedX: (Math.random() - 0.5) * 0.5,
            speedY: (Math.random() - 0.5) * 0.5,
            opacity: Math.random() * 0.5 + 0.1,
          };
        }

        function initParticles() {
          particles = [];
          for (var i = 0; i < particleCount; i++) {
            particles.push(createParticle());
          }
        }

        function animate() {
          if (!isVisible) {
            return;
          }

          ctx.clearRect(0, 0, canvas.width, canvas.height);

          particles.forEach(function (p) {
            p.x += p.speedX;
            p.y += p.speedY;

            // Wrap around edges.
            if (p.x < 0) p.x = canvas.width;
            if (p.x > canvas.width) p.x = 0;
            if (p.y < 0) p.y = canvas.height;
            if (p.y > canvas.height) p.y = 0;

            ctx.beginPath();
            ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(255, 255, 255, ' + p.opacity + ')';
            ctx.fill();
          });

          animationId = requestAnimationFrame(animate);
        }

        // IntersectionObserver para rendimiento.
        var observer = new IntersectionObserver(function (entries) {
          entries.forEach(function (entry) {
            isVisible = entry.isIntersecting;
            if (isVisible) {
              animate();
            } else if (animationId) {
              cancelAnimationFrame(animationId);
              animationId = null;
            }
          });
        }, { threshold: 0.1 });

        resize();
        initParticles();
        observer.observe(section);

        window.addEventListener('resize', function () {
          resize();
          initParticles();
        }, { passive: true });
      });
    }
  };

  /**
   * Animated Background - Gradient shift pausable.
   *
   * Permite pausar la animacion de gradiente al hacer hover
   * para accesibilidad.
   */
  Drupal.behaviors.jarabaMagicUiAnimatedBg = {
    attach: function (context) {
      once('animatedBg', '.jaraba-animated-bg', context).forEach(function (section) {
        var gradient = section.querySelector('.jaraba-animated-bg__gradient');
        if (!gradient) {
          return;
        }

        if (prefersReducedMotion()) {
          gradient.style.animation = 'none';
          return;
        }

        // Pausar al hover.
        section.addEventListener('mouseenter', function () {
          gradient.style.animationPlayState = 'paused';
        });
        section.addEventListener('mouseleave', function () {
          gradient.style.animationPlayState = 'running';
        });
      });
    }
  };

})(Drupal, once);

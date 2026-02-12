/**
 * @file
 * Adapter JavaScript para bloques premium Aceternity UI.
 *
 * Conecta los efectos visuales de Aceternity UI con Drupal.behaviors.
 * Incluye:
 * - Hover Glow (seguimiento de cursor)
 * - Card Flip 3D (click toggle)
 * - Comparison Slider (drag interactivo)
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
   * Hover Glow Effect.
   *
   * Sigue la posicion del cursor para crear un efecto de resplandor
   * en tarjetas interactivas. Usa CSS custom properties para posicionar
   * el radial-gradient definido en aceternity.css.
   */
  Drupal.behaviors.jarabaAceternityHoverGlow = {
    attach: function (context) {
      if (prefersReducedMotion()) {
        return;
      }

      once('hoverGlow', '.jaraba-hover-glow__card', context).forEach(function (card) {
        card.addEventListener('mousemove', function (e) {
          var rect = card.getBoundingClientRect();
          var x = ((e.clientX - rect.left) / rect.width) * 100;
          var y = ((e.clientY - rect.top) / rect.height) * 100;
          card.style.setProperty('--mouse-x', x + '%');
          card.style.setProperty('--mouse-y', y + '%');
        });

        card.addEventListener('mouseleave', function () {
          card.style.removeProperty('--mouse-x');
          card.style.removeProperty('--mouse-y');
        });
      });
    }
  };

  /**
   * Card Flip 3D.
   *
   * Toggle de volteo en tarjetas 3D al hacer click.
   * Accesible via teclado (Enter/Space).
   */
  Drupal.behaviors.jarabaAceternityCardFlip = {
    attach: function (context) {
      once('cardFlip', '.jaraba-card-flip', context).forEach(function (card) {
        // Asegurar accesibilidad.
        if (!card.hasAttribute('tabindex')) {
          card.setAttribute('tabindex', '0');
        }
        if (!card.hasAttribute('role')) {
          card.setAttribute('role', 'button');
        }
        card.setAttribute('aria-label', Drupal.t('Pulsa para voltear la tarjeta'));

        function flip() {
          var isFlipped = card.classList.toggle('is-flipped');
          card.setAttribute('aria-pressed', isFlipped ? 'true' : 'false');
        }

        card.addEventListener('click', flip);
        card.addEventListener('keydown', function (e) {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            flip();
          }
        });
      });
    }
  };

  /**
   * Glassmorphism focus management.
   *
   * Asegura que las tarjetas glassmorphism tengan focus-visible
   * correcto con alto contraste.
   */
  Drupal.behaviors.jarabaAceternityGlassmorphism = {
    attach: function (context) {
      once('glassmorphism', '.jaraba-glassmorphism__card', context).forEach(function (card) {
        // Asegurar que las tarjetas interactivas son focusables.
        var links = card.querySelectorAll('a, button');
        if (links.length === 0 && !card.hasAttribute('tabindex')) {
          card.setAttribute('tabindex', '0');
        }
      });
    }
  };

  /**
   * Video Background Hero.
   *
   * Pausa el video de fondo si el usuario prefiere movimiento reducido,
   * o cuando la seccion no es visible (rendimiento).
   */
  Drupal.behaviors.jarabaAceternityVideoHero = {
    attach: function (context) {
      once('videoHero', '.jaraba-video-bg-hero', context).forEach(function (section) {
        var video = section.querySelector('.jaraba-video-bg-hero__video');
        if (!video) {
          return;
        }

        // Pausar si prefiere movimiento reducido.
        if (prefersReducedMotion()) {
          video.pause();
          return;
        }

        // IntersectionObserver para pausar fuera de viewport.
        var observer = new IntersectionObserver(function (entries) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting) {
              video.play();
            } else {
              video.pause();
            }
          });
        }, { threshold: 0.25 });

        observer.observe(section);
      });
    }
  };

})(Drupal, once);

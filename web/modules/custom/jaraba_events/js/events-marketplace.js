/**
 * @file
 * Comportamientos JS para el marketplace de eventos.
 *
 * ESTRUCTURA:
 * Behavior de Drupal que inicializa interacciones del marketplace:
 * animación de entrada de tarjetas y scroll suave a filtros.
 *
 * LÓGICA:
 * - Intersection Observer para animación de entrada de tarjetas.
 * - Mantiene el scroll en la sección de grid al cambiar filtros.
 */
(function (Drupal, once) {

  'use strict';

  Drupal.behaviors.jarabaEventsMarketplace = {
    attach: function (context) {
      // Animación de entrada de tarjetas
      once('jaraba-event-cards', '.ej-event-card', context).forEach(function (card, index) {
        if ('IntersectionObserver' in window) {
          card.style.opacity = '0';
          card.style.transform = 'translateY(20px)';
          card.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
          card.style.transitionDelay = (index % 3 * 0.1) + 's';

          var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
              if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
                observer.unobserve(entry.target);
              }
            });
          }, { threshold: 0.1 });

          observer.observe(card);
        }
      });
    }
  };

})(Drupal, once);

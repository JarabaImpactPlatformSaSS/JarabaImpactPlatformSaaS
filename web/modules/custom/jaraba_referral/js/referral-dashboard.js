/**
 * @file
 * Comportamientos JS del dashboard de referidos.
 */
(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.jarabaReferralDashboard = {
    attach: function (context) {
      // Animación de entrada de KPI cards
      once('ref-kpi-cards', '.ej-referral-dashboard__kpi-card', context).forEach(function (card, i) {
        if ('IntersectionObserver' in window) {
          card.style.opacity = '0';
          card.style.transform = 'translateY(16px)';
          card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
          card.style.transitionDelay = (i * 0.08) + 's';

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

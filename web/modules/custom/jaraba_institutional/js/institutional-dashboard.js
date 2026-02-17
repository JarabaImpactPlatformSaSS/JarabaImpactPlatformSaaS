/**
 * @file
 * Comportamiento del dashboard de Programas Institucionales.
 *
 * Estructura: Drupal.behaviors para inicializar el dashboard,
 *   gestionar filtros y actualizar datos via API REST.
 *
 * Logica: Lee datos iniciales desde drupalSettings.jarabaInstitutional.
 *   Proporciona filtrado en cliente y actualizacion via fetch API.
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.jarabaInstitutional = {
    attach(context) {
      once('jaraba-institutional', '.institutional-dashboard', context).forEach((dashboard) => {
        const config = drupalSettings.jarabaInstitutional || {};
        const apiBase = config.apiBase || '/api/v1/institutional';

        // Inicializar contadores de estadisticas con animacion.
        dashboard.querySelectorAll('.institutional-stat-card__value').forEach((el) => {
          const target = parseFloat(el.textContent) || 0;
          if (target > 0 && !el.dataset.animated) {
            el.dataset.animated = 'true';
            // Animacion simple de conteo ascendente.
            let current = 0;
            const isPercentage = el.textContent.includes('%');
            const step = target / 30;
            const interval = setInterval(() => {
              current += step;
              if (current >= target) {
                current = target;
                clearInterval(interval);
              }
              el.textContent = isPercentage
                ? current.toFixed(1) + '%'
                : Math.round(current).toString();
            }, 30);
          }
        });

        // Manejador de clic en tarjetas de programa.
        dashboard.querySelectorAll('.program-card').forEach((card) => {
          card.addEventListener('click', () => {
            const programId = card.dataset.programId;
            if (programId) {
              window.location.href = `${apiBase}/programs/${programId}`;
            }
          });
        });

        Drupal.announce(Drupal.t('Dashboard de programas institucionales cargado.'));
      });
    },
  };
})(Drupal, drupalSettings, once);

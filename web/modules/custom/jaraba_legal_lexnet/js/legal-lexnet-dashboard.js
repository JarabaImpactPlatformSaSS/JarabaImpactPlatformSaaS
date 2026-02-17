/**
 * @file
 * legal-lexnet-dashboard.js — Dashboard de LexNET JarabaLex.
 *
 * Estructura: Drupal behavior con once() para el dashboard de LexNET.
 * Logica: Inicializa filtros y sincronizacion de notificaciones.
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.jarabaLegalLexnetDashboard = {
    attach(context) {
      once('lexnet-dashboard', '.legal-lexnet-dashboard', context).forEach(function (element) {
        var settings = drupalSettings.jarabaLegalLexnet || {};
        var apiBase = settings.apiBase || '/api/v1/legal/lexnet';

        // Filtros de estado.
        var statusFilters = element.querySelectorAll('[data-lexnet-filter]');
        statusFilters.forEach(function (filter) {
          filter.addEventListener('click', function () {
            var status = this.dataset.lexnetFilter;
            statusFilters.forEach(function (f) { f.classList.remove('active'); });
            this.classList.add('active');

            var cards = element.querySelectorAll('.ej-lexnet-notification');
            cards.forEach(function (card) {
              if (status === 'all' || card.classList.contains('ej-lexnet-notification--' + status)) {
                card.style.display = '';
              } else {
                card.style.display = 'none';
              }
            });
          });
        });
      });
    },
  };

})(Drupal, drupalSettings, once);

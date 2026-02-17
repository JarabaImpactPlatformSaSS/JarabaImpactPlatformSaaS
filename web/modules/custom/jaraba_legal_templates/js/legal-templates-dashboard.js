/**
 * @file
 * legal-templates-dashboard.js — Dashboard de Plantillas JarabaLex.
 *
 * Estructura: Drupal behavior con once() para el dashboard de plantillas.
 * Logica: Inicializa filtros por tipo de plantilla.
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.jarabaLegalTemplatesDashboard = {
    attach(context) {
      once('templates-dashboard', '.legal-templates-dashboard', context).forEach(function (element) {
        var settings = drupalSettings.jarabaLegalTemplates || {};
        var apiBase = settings.apiBase || '/api/v1/legal/templates';

        // Filtros de tipo.
        var typeFilters = element.querySelectorAll('[data-templates-filter]');
        typeFilters.forEach(function (filter) {
          filter.addEventListener('click', function () {
            var type = this.dataset.templatesFilter;
            typeFilters.forEach(function (f) { f.classList.remove('ej-filter-chip--active'); });
            this.classList.add('ej-filter-chip--active');

            var cards = element.querySelectorAll('.ej-template-card');
            cards.forEach(function (card) {
              if (type === 'all' || card.classList.contains('ej-template-card--' + type)) {
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

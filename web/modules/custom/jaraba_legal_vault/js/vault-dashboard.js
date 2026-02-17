/**
 * @file
 * vault-dashboard.js — Dashboard de la Boveda Documental JarabaLex.
 *
 * Estructura: Drupal behavior con once() para el dashboard de documentos.
 * Logica: Inicializa filtros, busqueda y acciones sobre documentos.
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Behavior del dashboard de la boveda documental.
   */
  Drupal.behaviors.jarabaLegalVaultDashboard = {
    attach(context) {
      once('vault-dashboard', '.legal-vault-dashboard', context).forEach(function (element) {
        const settings = drupalSettings.jarabaLegalVault || {};
        const apiBase = settings.apiBase || '/api/v1/vault';

        // Inicializar busqueda de documentos.
        const searchInput = element.querySelector('[data-vault-search]');
        if (searchInput) {
          searchInput.addEventListener('input', Drupal.debounce(function () {
            const query = searchInput.value.toLowerCase();
            const cards = element.querySelectorAll('.ej-document-card');
            cards.forEach(function (card) {
              const title = (card.querySelector('.ej-document-card__title') || {}).textContent || '';
              const filename = (card.querySelector('.ej-document-card__filename') || {}).textContent || '';
              const match = title.toLowerCase().includes(query) || filename.toLowerCase().includes(query);
              card.style.display = match ? '' : 'none';
            });
          }, 300));
        }

        // Inicializar filtros de estado.
        const statusFilters = element.querySelectorAll('[data-vault-filter]');
        statusFilters.forEach(function (filter) {
          filter.addEventListener('click', function () {
            const status = this.dataset.vaultFilter;
            statusFilters.forEach(function (f) { f.classList.remove('active'); });
            this.classList.add('active');

            const cards = element.querySelectorAll('.ej-document-card');
            cards.forEach(function (card) {
              if (status === 'all' || card.classList.contains('ej-document-card--' + status)) {
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

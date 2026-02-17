/**
 * @file
 * legal-billing-dashboard.js — Dashboard de Facturacion Legal JarabaLex.
 *
 * Estructura: Drupal behavior con once() para el dashboard de facturacion.
 * Logica: Inicializa filtros, busqueda y stats de facturacion.
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Behavior del dashboard de facturacion legal.
   */
  Drupal.behaviors.jarabaLegalBillingDashboard = {
    attach(context) {
      once('billing-dashboard', '.legal-billing-dashboard', context).forEach(function (element) {
        const settings = drupalSettings.jarabaLegalBilling || {};
        const apiBase = settings.apiBase || '/api/v1/legal/billing';

        // Inicializar filtros de estado.
        const statusFilters = element.querySelectorAll('[data-billing-filter]');
        statusFilters.forEach(function (filter) {
          filter.addEventListener('click', function () {
            const status = this.dataset.billingFilter;
            statusFilters.forEach(function (f) { f.classList.remove('active'); });
            this.classList.add('active');

            const cards = element.querySelectorAll('.ej-invoice-card');
            cards.forEach(function (card) {
              if (status === 'all' || card.classList.contains('ej-invoice-card--' + status)) {
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

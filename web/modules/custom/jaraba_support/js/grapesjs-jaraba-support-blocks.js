/**
 * @file
 * GrapesJS Support Blocks — Bloques custom de soporte para Page Builder.
 *
 * Registra componentes del módulo de soporte como bloques arrastrables
 * en el editor GrapesJS del Page Builder:
 * - Widget de tickets recientes del tenant
 * - Widget de KPIs de soporte
 * - Botón "Crear ticket"
 * - FAQ / Knowledge base widget
 *
 * DIRECTRICES:
 * - Depende de jaraba_page_builder/grapesjs-canvas (engine GrapesJS)
 * - Categoría: "Soporte" en el panel de bloques
 * - ROUTE-LANGPREFIX-001: URLs via drupalSettings
 */

(function (Drupal, drupalSettings) {
  'use strict';

  /**
   * Register support blocks when GrapesJS is ready.
   */
  Drupal.behaviors.grapesJsSupportBlocks = {
    attach() {
      if (typeof window.jarabaGrapesJsReady !== 'function') return;

      window.jarabaGrapesJsReady((editor) => {
        if (editor._supportBlocksRegistered) return;
        editor._supportBlocksRegistered = true;

        const blockManager = editor.BlockManager;
        const category = Drupal.t('Support');

        // Block: Recent Tickets Widget.
        blockManager.add('support-recent-tickets', {
          label: Drupal.t('Recent Tickets'),
          category: category,
          content: {
            type: 'support-recent-tickets',
            tagName: 'div',
            classes: ['jaraba-widget', 'jaraba-widget--support-tickets'],
            attributes: {
              'data-jaraba-widget': 'support-recent-tickets',
              'data-count': '5',
            },
            components: '<div class="jaraba-widget__placeholder">' +
              '<span class="jaraba-widget__icon">&#128203;</span>' +
              '<span class="jaraba-widget__label">' +
              Drupal.checkPlain(Drupal.t('Recent Support Tickets')) +
              '</span></div>',
          },
          attributes: { class: 'gjs-block-support' },
        });

        // Block: Support KPIs Widget.
        blockManager.add('support-kpi-widget', {
          label: Drupal.t('Support KPIs'),
          category: category,
          content: {
            type: 'support-kpi-widget',
            tagName: 'div',
            classes: ['jaraba-widget', 'jaraba-widget--support-kpis'],
            attributes: {
              'data-jaraba-widget': 'support-kpis',
            },
            components: '<div class="jaraba-widget__placeholder">' +
              '<span class="jaraba-widget__icon">&#128202;</span>' +
              '<span class="jaraba-widget__label">' +
              Drupal.checkPlain(Drupal.t('Support KPI Dashboard')) +
              '</span></div>',
          },
          attributes: { class: 'gjs-block-support' },
        });

        // Block: Create Ticket Button.
        blockManager.add('support-create-btn', {
          label: Drupal.t('Create Ticket Button'),
          category: category,
          content: {
            type: 'support-create-button',
            tagName: 'a',
            classes: ['btn', 'btn--primary', 'jaraba-support-btn'],
            attributes: {
              href: '#',
              'data-support-action': 'create-ticket',
            },
            components: Drupal.checkPlain(Drupal.t('Create Support Ticket')),
          },
          attributes: { class: 'gjs-block-support' },
        });

        // Block: FAQ Widget.
        blockManager.add('support-faq-widget', {
          label: Drupal.t('FAQ Widget'),
          category: category,
          content: {
            type: 'support-faq-widget',
            tagName: 'div',
            classes: ['jaraba-widget', 'jaraba-widget--support-faq'],
            attributes: {
              'data-jaraba-widget': 'support-faq',
              'data-category': '',
              'data-count': '5',
            },
            components: '<div class="jaraba-widget__placeholder">' +
              '<span class="jaraba-widget__icon">&#10067;</span>' +
              '<span class="jaraba-widget__label">' +
              Drupal.checkPlain(Drupal.t('FAQ / Knowledge Base')) +
              '</span></div>',
          },
          attributes: { class: 'gjs-block-support' },
        });
      });
    },
  };

})(Drupal, drupalSettings);

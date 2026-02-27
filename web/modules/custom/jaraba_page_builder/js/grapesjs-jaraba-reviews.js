/**
 * @file
 * GrapesJS Plugin: Review Widget Block.
 *
 * Registers a configurable review summary widget for embedding in
 * landing pages via the Page Builder canvas editor.
 *
 * Editor: Fetches review stats from API and renders live preview.
 * Public: Server-side rendered by PageContentViewBuilder (no JS needed).
 *
 * REV-PHASE6: Widget de resenas para GrapesJS.
 */
(function (Drupal) {
  'use strict';

  /**
   * Label map for entity types (used in editor preview).
   * i18n: All labels wrapped in Drupal.t() for translation.
   */
  var ENTITY_TYPE_LABELS = {
    merchant_profile: Drupal.t('Producto / Comercio'),
    producer_profile: Drupal.t('Productor Agricola'),
    provider_profile: Drupal.t('Proveedor de Servicios'),
    lms_course: Drupal.t('Curso'),
    mentoring_session: Drupal.t('Sesion de Mentoring'),
  };

  /**
   * Generates star HTML for a given rating.
   *
   * @param {number} rating - Rating value (0-5).
   * @returns {string} HTML string with star characters.
   */
  function generateStarsHtml(rating) {
    var html = '<span class="review-widget-stars" aria-label="' + rating.toFixed(1) + ' de 5" style="font-size: 1.5rem; color: var(--ej-color-warning, #F59E0B); letter-spacing: 2px;">';
    for (var i = 1; i <= 5; i++) {
      html += i <= Math.round(rating) ? '\u2605' : '\u2606';
    }
    html += '</span>';
    return html;
  }

  /**
   * Generates distribution bars HTML.
   *
   * @param {Object} distribution - { 1: count, 2: count, ..., 5: count }.
   * @param {number} total - Total review count.
   * @returns {string} HTML string.
   */
  function generateDistributionHtml(distribution, total) {
    var html = '<div class="review-widget__distribution" style="margin-top: 1rem;">';
    for (var star = 5; star >= 1; star--) {
      var count = (distribution && distribution[star]) || 0;
      var pct = total > 0 ? Math.round((count / total) * 100) : 0;
      html += '<div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">';
      html += '<span style="min-width: 2.5rem; font-size: 0.8rem; color: var(--ej-text-muted, #64748b);">' + star + ' \u2605</span>';
      html += '<div style="flex: 1; height: 8px; background: var(--ej-bg-tertiary, #e2e8f0); border-radius: 4px; overflow: hidden;">';
      html += '<div style="width: ' + pct + '%; height: 100%; background: var(--ej-color-warning, #F59E0B); border-radius: 4px;"></div>';
      html += '</div>';
      html += '<span style="min-width: 2rem; text-align: right; font-size: 0.75rem; color: var(--ej-text-muted, #64748b);">' + count + '</span>';
      html += '</div>';
    }
    html += '</div>';
    return html;
  }

  /**
   * Embedded script for GrapesJS editor context.
   * Fetches review stats from API and renders preview.
   */
  var reviewWidgetScript = function () {
    var el = this;
    var entityType = el.getAttribute('data-entity-type');
    var entityId = el.getAttribute('data-entity-id');

    if (!entityType || !entityId || entityId === '0') {
      el.innerHTML = '<div style="text-align: center; padding: 2rem; color: var(--ej-text-muted, #64748b);">' +
        '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin: 0 auto 0.5rem;"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>' +
        '<p style="margin: 0; font-size: 0.9rem;">Configura el tipo de entidad y el ID en las propiedades del bloque.</p></div>';
      return;
    }

    el.innerHTML = '<div style="text-align: center; padding: 1rem; color: var(--ej-text-muted, #64748b);">Cargando resenas...</div>';

    // Build API URL using Drupal.url() if available.
    var apiPath = '/api/v1/reviews/stats/' + entityType + '/' + entityId;
    var apiUrl = (typeof Drupal !== 'undefined' && Drupal.url) ? Drupal.url(apiPath) : apiPath;

    var xhr = new XMLHttpRequest();
    xhr.open('GET', apiUrl, true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.onload = function () {
      if (xhr.status === 200) {
        try {
          var response = JSON.parse(xhr.responseText);
          if (response.success && response.data) {
            renderPreview(el, response.data, entityType);
          } else {
            renderPlaceholder(el, entityType, entityId);
          }
        } catch (e) {
          renderPlaceholder(el, entityType, entityId);
        }
      } else {
        renderPlaceholder(el, entityType, entityId);
      }
    };
    xhr.onerror = function () {
      renderPlaceholder(el, entityType, entityId);
    };
    xhr.send();

    function renderPreview(container, data, type) {
      var avg = parseFloat(data.average) || 0;
      var count = parseInt(data.count, 10) || 0;
      var labels = {
        merchant_profile: 'Producto / Comercio',
        producer_profile: 'Productor Agricola',
        provider_profile: 'Proveedor de Servicios',
        lms_course: 'Curso',
        mentoring_session: 'Sesion de Mentoring',
      };
      var typeLabel = labels[type] || type;
      var html = '<div style="padding: 1.5rem; background: var(--ej-bg-secondary, #f8fafc); border-radius: 12px;">';
      html += '<div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">';
      html += '<span style="font-size: 2.5rem; font-weight: 800; color: var(--ej-text-primary, #1e293b);">' + avg.toFixed(1) + '</span>';
      html += '<div>';
      // Stars
      html += '<span style="font-size: 1.2rem; color: var(--ej-color-warning, #F59E0B); letter-spacing: 1px;">';
      for (var i = 1; i <= 5; i++) {
        html += i <= Math.round(avg) ? '\u2605' : '\u2606';
      }
      html += '</span>';
      html += '<div style="font-size: 0.8rem; color: var(--ej-text-muted, #64748b);">' + count + ' resenas</div>';
      html += '</div></div>';

      // Distribution bars
      if (data.distribution) {
        for (var s = 5; s >= 1; s--) {
          var c = data.distribution[s] || 0;
          var pct = count > 0 ? Math.round((c / count) * 100) : 0;
          html += '<div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">';
          html += '<span style="min-width: 2.5rem; font-size: 0.75rem; color: var(--ej-text-muted, #64748b);">' + s + ' \u2605</span>';
          html += '<div style="flex: 1; height: 6px; background: var(--ej-bg-tertiary, #e2e8f0); border-radius: 3px; overflow: hidden;">';
          html += '<div style="width: ' + pct + '%; height: 100%; background: var(--ej-color-warning, #F59E0B); border-radius: 3px;"></div>';
          html += '</div>';
          html += '<span style="min-width: 1.5rem; text-align: right; font-size: 0.7rem; color: var(--ej-text-muted, #64748b);">' + c + '</span>';
          html += '</div>';
        }
      }

      html += '<div style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid var(--ej-border-color, #e2e8f0); font-size: 0.75rem; color: var(--ej-text-muted, #64748b);">';
      html += '<span style="background: var(--ej-color-success-light, #dcfce7); color: var(--ej-color-success, #16a34a); padding: 0.15rem 0.5rem; border-radius: 4px; font-size: 0.7rem;">' + typeLabel + '</span>';
      html += '</div></div>';

      container.innerHTML = html;
    }

    function renderPlaceholder(container, type, id) {
      var labels = {
        merchant_profile: 'Producto / Comercio',
        producer_profile: 'Productor Agricola',
        provider_profile: 'Proveedor de Servicios',
        lms_course: 'Curso',
        mentoring_session: 'Sesion de Mentoring',
      };
      var typeLabel = labels[type] || type;
      container.innerHTML = '<div style="padding: 1.5rem; background: var(--ej-bg-secondary, #f8fafc); border-radius: 12px; text-align: center;">' +
        '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--ej-color-warning, #F59E0B)" stroke-width="2" style="margin: 0 auto 0.5rem;"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>' +
        '<p style="margin: 0 0 0.25rem; font-size: 0.9rem; font-weight: 600; color: var(--ej-text-primary, #1e293b);">Widget de Resenas</p>' +
        '<p style="margin: 0; font-size: 0.75rem; color: var(--ej-text-muted, #64748b);">' + typeLabel + ' #' + id + '</p>' +
        '<p style="margin: 0.5rem 0 0; font-size: 0.7rem; color: var(--ej-text-muted, #94a3b8);">Las resenas se mostraran en la pagina publicada.</p>' +
        '</div>';
    }
  };

  /**
   * GrapesJS plugin registration.
   */
  var jarabaReviewsPlugin = function (editor) {
    var domComponents = editor.DomComponents;
    var blockManager = editor.BlockManager;

    // -------------------------------------------------------------------------
    // Component Type: jaraba-review-widget
    // -------------------------------------------------------------------------
    domComponents.addType('jaraba-review-widget', {
      extend: 'default',

      model: {
        defaults: {
          tagName: 'div',
          droppable: false,
          copyable: true,
          removable: true,
          classes: ['jaraba-review-widget'],
          script: reviewWidgetScript,
          attributes: {
            'data-jaraba-review-widget': '',
            'data-entity-type': '',
            'data-entity-id': '0',
            'data-max-reviews': '3',
            'data-show-summary': 'true',
          },
          traits: [
            {
              type: 'select',
              name: 'data-entity-type',
              label: Drupal.t('Tipo de entidad'),
              options: [
                { value: '', name: Drupal.t('-- Seleccionar --') },
                { value: 'merchant_profile', name: Drupal.t('Producto / Comercio') },
                { value: 'producer_profile', name: Drupal.t('Productor Agricola') },
                { value: 'provider_profile', name: Drupal.t('Proveedor de Servicios') },
                { value: 'lms_course', name: Drupal.t('Curso') },
                { value: 'mentoring_session', name: Drupal.t('Sesion de Mentoring') },
              ],
            },
            {
              type: 'number',
              name: 'data-entity-id',
              label: Drupal.t('ID de la entidad'),
              min: 0,
            },
            {
              type: 'number',
              name: 'data-max-reviews',
              label: Drupal.t('Max resenas a mostrar'),
              default: 3,
              min: 1,
              max: 10,
            },
            {
              type: 'checkbox',
              name: 'data-show-summary',
              label: Drupal.t('Mostrar resumen de puntuacion'),
              valueTrue: 'true',
              valueFalse: 'false',
            },
          ],
        },

        init: function () {
          // Re-render on trait changes.
          this.on('change:attributes:data-entity-type', this.triggerUpdate);
          this.on('change:attributes:data-entity-id', this.triggerUpdate);
          this.on('change:attributes:data-max-reviews', this.triggerUpdate);
          this.on('change:attributes:data-show-summary', this.triggerUpdate);
        },

        triggerUpdate: function () {
          this.trigger('reviewWidget:change');
        },
      },

      isComponent: function (el) {
        if (el.hasAttribute && el.hasAttribute('data-jaraba-review-widget')) {
          return { type: 'jaraba-review-widget' };
        }
      },

      view: {
        init: function () {
          this.listenTo(this.model, 'reviewWidget:change', this.updateView);
        },
        onRender: function () {
          this.el.style.minHeight = '100px';
          // The embedded script handles rendering.
        },
        updateView: function () {
          // Re-run the embedded script to fetch fresh data.
          reviewWidgetScript.call(this.el);
        },
      },
    });

    // -------------------------------------------------------------------------
    // Block Registration in Social category.
    // -------------------------------------------------------------------------
    blockManager.add('jaraba-review-widget', {
      label: Drupal.t('Widget de Resenas'),
      category: 'social',
      attributes: { class: 'gjs-block-review' },
      media: '<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/><circle cx="12" cy="12" r="10" stroke-opacity="0.3"/></svg>',
      content: {
        type: 'jaraba-review-widget',
      },
    });
  };

  // Register as GrapesJS plugin.
  if (typeof grapesjs !== 'undefined') {
    grapesjs.plugins.add('jaraba-reviews-block', jarabaReviewsPlugin);
  }

  // Also expose for deferred loading.
  window.jarabaReviewsPlugin = jarabaReviewsPlugin;

})(Drupal);

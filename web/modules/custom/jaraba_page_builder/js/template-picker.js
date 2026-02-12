/**
 * @file
 * Template Picker JavaScript - Page Builder
 *
 * Maneja el filtrado de plantillas, animaciones del picker y preview responsivo.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Viewport breakpoints para preview responsivo.
   */
  const VIEWPORTS = {
    desktop: { width: '100%', icon: '🖥️', label: 'Desktop' },
    tablet: { width: '768px', icon: '📱', label: 'Tablet' },
    mobile: { width: '375px', icon: '📱', label: 'Mobile' }
  };

  /**
   * Template Picker behavior.
   */
  Drupal.behaviors.jarabaTemplatePicker = {
    attach: function (context) {
      // Initialize template filters
      once('template-picker-filters', '.template-picker__filters', context).forEach(function (filtersContainer) {
        const filters = filtersContainer.querySelectorAll('.template-picker__filter');
        const grid = document.querySelector('.template-picker__grid');
        const cards = grid ? grid.querySelectorAll('.template-card') : [];

        filters.forEach(function (filter) {
          filter.addEventListener('click', function (event) {
            event.preventDefault();

            // Update active state
            filters.forEach(f => f.classList.remove('is-active'));
            filter.classList.add('is-active');

            // Get category to filter
            const category = filter.dataset.filter;

            // Filter cards by category (respecting current search)
            const searchInput = document.getElementById('template-search');
            const searchQuery = searchInput ? searchInput.value.toLowerCase() : '';

            Drupal.behaviors.jarabaTemplatePicker.filterCards(cards, category, searchQuery);
          });
        });
      });

      // Initialize search functionality
      once('template-picker-search', '#template-search', context).forEach(function (searchInput) {
        const grid = document.querySelector('.template-picker__grid');
        const cards = grid ? grid.querySelectorAll('.template-card') : [];
        const filters = document.querySelectorAll('.template-picker__filter');

        searchInput.addEventListener('input', function () {
          const query = this.value.toLowerCase();
          const activeFilter = document.querySelector('.template-picker__filter.is-active');
          const category = activeFilter ? activeFilter.dataset.filter : 'all';

          Drupal.behaviors.jarabaTemplatePicker.filterCards(cards, category, query);
        });
      });

      // Initialize template card hover effects
      once('template-card-hover', '.template-card', context).forEach(function (card) {
        const preview = card.querySelector('.template-card__preview img');

        if (preview) {
          card.addEventListener('mouseenter', function () {
            preview.style.transform = 'scale(1.05)';
          });

          card.addEventListener('mouseleave', function () {
            preview.style.transform = 'scale(1)';
          });
        }
      });

      // Initialize preview modal
      once('template-preview-btn', '.template-card__action--preview', context).forEach(function (btn) {
        btn.addEventListener('click', function (event) {
          event.preventDefault();
          const templateId = btn.dataset.templateId;
          const previewUrl = btn.href;

          // Create modal for preview
          Drupal.behaviors.jarabaTemplatePicker.openPreviewModal(previewUrl, templateId);
        });
      });
    },

    /**
     * Filtra las tarjetas por categoría y término de búsqueda.
     * Combina ambos criterios para mostrar solo las que coinciden.
     *
     * @param {NodeList} cards - Lista de tarjetas de plantilla
     * @param {string} category - Categoría activa ('all' para todas)
     * @param {string} searchQuery - Término de búsqueda en minúsculas
     */
    filterCards: function (cards, category, searchQuery) {
      cards.forEach(function (card) {
        const cardCategory = card.dataset.category;
        const cardName = card.querySelector('.template-card__name');
        const cardDescription = card.querySelector('.template-card__description');

        // Obtener texto para búsqueda
        const name = cardName ? cardName.textContent.toLowerCase() : '';
        const description = cardDescription ? cardDescription.textContent.toLowerCase() : '';

        // Comprobar si coincide con búsqueda
        const matchesSearch = !searchQuery ||
          name.includes(searchQuery) ||
          description.includes(searchQuery);

        // Comprobar si coincide con categoría
        const matchesCategory = category === 'all' || cardCategory === category;

        // Mostrar solo si coincide con ambos criterios
        if (matchesCategory && matchesSearch) {
          card.style.display = '';
          setTimeout(function () {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
          }, 50);
        } else {
          card.style.opacity = '0';
          card.style.transform = 'translateY(10px)';
          setTimeout(function () {
            card.style.display = 'none';
          }, 300);
        }
      });
    },

    /**
     * Open preview modal for template.
     */
    openPreviewModal: function (url, templateId) {
      // Check if modal already exists
      let modal = document.querySelector('.template-preview-modal');

      if (!modal) {
        modal = document.createElement('div');
        modal.className = 'template-preview-modal';
        modal.innerHTML = `
          <div class="template-preview-modal__backdrop"></div>
          <div class="template-preview-modal__content">
            <div class="template-preview-modal__header">
              <div class="template-preview-modal__viewport-toggle">
                <button type="button" class="viewport-btn viewport-btn--desktop is-active" data-viewport="desktop" title="${Drupal.t('Desktop')}">
                  🖥️
                </button>
                <button type="button" class="viewport-btn viewport-btn--tablet" data-viewport="tablet" title="${Drupal.t('Tablet')}">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="4" y="2" width="16" height="20" rx="2"/><line x1="12" y1="18" x2="12" y2="18"/>
                  </svg>
                </button>
                <button type="button" class="viewport-btn viewport-btn--mobile" data-viewport="mobile" title="${Drupal.t('Mobile')}">
                  📱
                </button>
              </div>
              <button class="template-preview-modal__close" aria-label="${Drupal.t('Cerrar')}">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <line x1="18" y1="6" x2="6" y2="18"></line>
                  <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
              </button>
            </div>
            <div class="template-preview-modal__iframe-container">
              <iframe class="template-preview-modal__iframe" src="" frameborder="0"></iframe>
            </div>
          </div>
        `;
        document.body.appendChild(modal);

        // Add styles for viewport toggle
        if (!document.getElementById('viewport-toggle-styles')) {
          const style = document.createElement('style');
          style.id = 'viewport-toggle-styles';
          style.textContent = `
            .template-preview-modal__header {
              display: flex;
              justify-content: space-between;
              align-items: center;
              padding: 12px 16px;
              background: #f8fafc;
              border-bottom: 1px solid #e2e8f0;
            }
            .template-preview-modal__viewport-toggle {
              display: flex;
              gap: 8px;
              background: white;
              border-radius: 8px;
              padding: 4px;
              box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .viewport-btn {
              display: flex;
              align-items: center;
              justify-content: center;
              width: 40px;
              height: 36px;
              border: none;
              background: transparent;
              border-radius: 6px;
              cursor: pointer;
              font-size: 16px;
              transition: all 0.2s;
              color: #64748b;
            }
            .viewport-btn:hover {
              background: #f1f5f9;
              color: #334155;
            }
            .viewport-btn.is-active {
              background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
              color: white;
              box-shadow: 0 2px 6px rgba(102, 126, 234, 0.4);
            }
            .viewport-btn svg {
              width: 18px;
              height: 18px;
            }
            .template-preview-modal__iframe-container {
              flex: 1;
              display: flex;
              justify-content: center;
              align-items: flex-start;
              background: #f1f5f9;
              padding: 20px;
              overflow: auto;
            }
            .template-preview-modal__iframe {
              background: white;
              box-shadow: 0 4px 20px rgba(0,0,0,0.15);
              border-radius: 8px;
              transition: width 0.3s ease;
              width: 100%;
              height: 100%;
            }
            .template-preview-modal__content {
              display: flex;
              flex-direction: column;
              height: 90vh;
              width: 95vw;
              max-width: 1400px;
              background: white;
              border-radius: 12px;
              overflow: hidden;
              position: relative;
            }
            .template-preview-modal__close {
              display: flex;
              align-items: center;
              justify-content: center;
              width: 36px;
              height: 36px;
              border: none;
              background: #f1f5f9;
              border-radius: 8px;
              cursor: pointer;
              color: #64748b;
              transition: all 0.2s;
            }
            .template-preview-modal__close:hover {
              background: #e2e8f0;
              color: #1e293b;
            }
          `;
          document.head.appendChild(style);
        }

        // Close on backdrop click
        modal.querySelector('.template-preview-modal__backdrop').addEventListener('click', function () {
          Drupal.behaviors.jarabaTemplatePicker.closePreviewModal();
        });

        // Close on button click
        modal.querySelector('.template-preview-modal__close').addEventListener('click', function () {
          Drupal.behaviors.jarabaTemplatePicker.closePreviewModal();
        });

        // Close on escape key
        document.addEventListener('keydown', function (event) {
          if (event.key === 'Escape') {
            Drupal.behaviors.jarabaTemplatePicker.closePreviewModal();
          }
        });

        // Viewport toggle functionality
        modal.querySelectorAll('.viewport-btn').forEach(function (btn) {
          btn.addEventListener('click', function () {
            const viewport = btn.dataset.viewport;
            Drupal.behaviors.jarabaTemplatePicker.setViewport(viewport);

            // Update active state
            modal.querySelectorAll('.viewport-btn').forEach(b => b.classList.remove('is-active'));
            btn.classList.add('is-active');

            // Save preference
            localStorage.setItem('jaraba_preview_viewport', viewport);
          });
        });
      }

      // Restore saved viewport preference
      const savedViewport = localStorage.getItem('jaraba_preview_viewport') || 'desktop';
      this.setViewport(savedViewport);
      modal.querySelectorAll('.viewport-btn').forEach(function (btn) {
        btn.classList.toggle('is-active', btn.dataset.viewport === savedViewport);
      });

      // Set iframe source and show modal
      const iframe = modal.querySelector('.template-preview-modal__iframe');
      iframe.src = url;
      modal.classList.add('is-open');
      document.body.style.overflow = 'hidden';
    },

    /**
     * Set viewport size for preview iframe.
     */
    setViewport: function (viewport) {
      const modal = document.querySelector('.template-preview-modal');
      if (!modal) return;

      const iframe = modal.querySelector('.template-preview-modal__iframe');
      const container = modal.querySelector('.template-preview-modal__iframe-container');

      if (!iframe) return;

      const widths = {
        desktop: '100%',
        tablet: '768px',
        mobile: '375px'
      };

      const width = widths[viewport] || '100%';
      iframe.style.maxWidth = width;

      // Adjust container alignment
      if (viewport !== 'desktop') {
        container.style.justifyContent = 'center';
      } else {
        container.style.justifyContent = 'stretch';
      }
    },

    /**
     * Close preview modal.
     */
    closePreviewModal: function () {
      const modal = document.querySelector('.template-preview-modal');
      if (modal) {
        modal.classList.remove('is-open');
        const iframe = modal.querySelector('.template-preview-modal__iframe');
        iframe.src = '';
        document.body.style.overflow = '';
      }
    }
  };

})(Drupal, once);


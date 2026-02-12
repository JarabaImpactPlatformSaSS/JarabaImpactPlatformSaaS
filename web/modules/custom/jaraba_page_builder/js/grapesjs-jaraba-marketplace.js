/**
 * @file
 * Jaraba Template Marketplace — Plugin GrapesJS (Sprint C1).
 *
 * Slide-panel integrado en el Canvas Editor que permite explorar, filtrar
 * y usar templates del TemplateRegistryService directamente en el editor.
 *
 * ARQUITECTURA:
 * - Fetch a /api/v1/page-builder/templates?format=gallery
 * - Slide-panel con filtros por categoría + búsqueda por texto
 * - Badges de plan (free / premium / locked)
 * - Inserción de template en canvas via editor.addComponents()
 * - Patrón reutilizado de grapesjs-jaraba-assets.js
 *
 * @see docs/planificacion/20260209-Plan_Mejoras_Page_Site_Builder_v3.md §C1
 */

(function (Drupal, drupalSettings, once) {
    'use strict';

    /**
     * Configuración del Marketplace.
     */
    const CONFIG = {
        apiEndpoint: '/api/v1/page-builder/templates',
        panelId: 'jaraba-template-marketplace',
        panelWidth: 480,
    };

    /**
     * Estado interno del Marketplace.
     */
    let state = {
        isOpen: false,
        loading: false,
        categories: [],
        allTemplates: [],
        filteredTemplates: [],
        activeCategory: 'all',
        searchQuery: '',
        error: null,
    };

    /* ------------------------------------------------------------------ */
    /*  Plugin init                                                       */
    /* ------------------------------------------------------------------ */

    /**
     * Inicializa el plugin del Marketplace para GrapesJS.
     *
     * @param {Object} editor — Instancia de GrapesJS.
     */
    function initMarketplacePlugin(editor) {
        // Registrar comando para abrir/cerrar.
        editor.Commands.add('jaraba:marketplace', {
            run() { openMarketplace(); },
            stop() { closeMarketplace(); },
        });

        // Listener del botón en toolbar.
        const triggerBtn = document.getElementById('marketplace-toggle-btn');
        if (triggerBtn) {
            triggerBtn.addEventListener('click', () => {
                if (state.isOpen) {
                    closeMarketplace();
                } else {
                    openMarketplace();
                }
            });
        }

        console.log(Drupal.t('[Jaraba C1] Template Marketplace Plugin inicializado.'));
    }

    /* ------------------------------------------------------------------ */
    /*  Abrir / Cerrar                                                    */
    /* ------------------------------------------------------------------ */

    /**
     * Abre el slide-panel del Marketplace.
     */
    function openMarketplace() {
        if (state.isOpen) return;
        state.isOpen = true;

        let panel = document.getElementById(CONFIG.panelId);
        if (!panel) {
            panel = createMarketplacePanel();
            document.body.appendChild(panel);
        }

        // Abrir con animación.
        requestAnimationFrame(() => {
            panel.classList.add('slide-panel--open');
            document.body.classList.add('slide-panel-open');
        });

        // Activar toggle btn.
        const btn = document.getElementById('marketplace-toggle-btn');
        if (btn) btn.classList.add('is-active');

        // Cargar datos si es la primera vez.
        if (state.categories.length === 0) {
            loadTemplates();
        }

        setupPanelEvents(panel);
    }

    /**
     * Cierra el slide-panel del Marketplace.
     */
    function closeMarketplace() {
        const panel = document.getElementById(CONFIG.panelId);
        if (panel) {
            panel.classList.remove('slide-panel--open');
        }
        document.body.classList.remove('slide-panel-open');
        state.isOpen = false;

        const btn = document.getElementById('marketplace-toggle-btn');
        if (btn) btn.classList.remove('is-active');
    }

    /* ------------------------------------------------------------------ */
    /*  Crear Panel                                                       */
    /* ------------------------------------------------------------------ */

    /**
     * Crea la estructura del slide-panel del Marketplace.
     *
     * @returns {HTMLElement} El panel creado.
     */
    function createMarketplacePanel() {
        const panel = document.createElement('div');
        panel.id = CONFIG.panelId;
        panel.className = 'slide-panel slide-panel--right jaraba-marketplace';
        panel.setAttribute('role', 'dialog');
        panel.setAttribute('aria-modal', 'true');
        panel.setAttribute('aria-labelledby', 'marketplace-title');

        panel.style.cssText = `
            position: fixed;
            top: 0;
            right: 0;
            width: ${CONFIG.panelWidth}px;
            height: 100vh;
            background: var(--ej-bg-surface, #1e293b);
            z-index: 99999;
            box-shadow: -4px 0 24px rgba(0,0,0,0.35);
            display: flex;
            flex-direction: column;
            transform: translateX(100%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        `;

        injectMarketplaceStyles();

        panel.innerHTML = `
            <div class="slide-panel__overlay jaraba-marketplace__overlay" data-close-panel></div>
            <div class="slide-panel__container jaraba-marketplace__container">
                <!-- Header -->
                <header class="jaraba-marketplace__header">
                    <h2 id="marketplace-title" class="jaraba-marketplace__title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/>
                            <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/>
                            <path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"/>
                            <path d="M2 7h20"/>
                            <path d="M22 7v3a2 2 0 0 1-2 2a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 16 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 12 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 8 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 4 12a2 2 0 0 1-2-2V7"/>
                        </svg>
                        ${Drupal.t('Template Marketplace')}
                    </h2>
                    <button type="button" class="jaraba-marketplace__close" data-close-panel aria-label="${Drupal.t('Cerrar')}">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6L6 18M6 6l12 12"/>
                        </svg>
                    </button>
                </header>

                <!-- Search -->
                <div class="jaraba-marketplace__search-bar">
                    <svg class="jaraba-marketplace__search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="m21 21-4.3-4.3"/>
                    </svg>
                    <input type="text"
                           id="marketplace-search"
                           class="jaraba-marketplace__search-input"
                           placeholder="${Drupal.t('Buscar plantillas...')}"
                           autocomplete="off" />
                </div>

                <!-- Category filters -->
                <nav class="jaraba-marketplace__filters" id="marketplace-filters" role="tablist" aria-label="${Drupal.t('Categorías')}">
                    <button type="button" class="jaraba-marketplace__filter is-active" data-category="all" role="tab" aria-selected="true">
                        ${Drupal.t('Todas')}
                    </button>
                    <!-- Se rellenan dinámicamente -->
                </nav>

                <!-- Grid -->
                <div class="jaraba-marketplace__body">
                    <div class="jaraba-marketplace__grid" id="marketplace-grid"></div>
                    <div class="jaraba-marketplace__empty" id="marketplace-empty" style="display:none;">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                            <path d="M3 9h18"/>
                            <path d="M9 21V9"/>
                        </svg>
                        <p>${Drupal.t('No se encontraron plantillas.')}</p>
                    </div>
                    <div class="jaraba-marketplace__loading" id="marketplace-loading" style="display:none;">
                        <div class="jaraba-marketplace__spinner"></div>
                        <p>${Drupal.t('Cargando plantillas...')}</p>
                    </div>
                </div>
            </div>
        `;

        return panel;
    }

    /* ------------------------------------------------------------------ */
    /*  Estilos                                                           */
    /* ------------------------------------------------------------------ */

    /**
     * Inyecta estilos CSS para el Marketplace slide-panel.
     */
    function injectMarketplaceStyles() {
        if (document.getElementById('jaraba-marketplace-styles')) return;

        const style = document.createElement('style');
        style.id = 'jaraba-marketplace-styles';
        style.innerHTML = `
            /* — Animaciones — */
            @keyframes jaraba-spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
            @keyframes jaraba-fade-in-up {
                from { opacity: 0; transform: translateY(12px); }
                to { opacity: 1; transform: translateY(0); }
            }

            /* — Panel — */
            #${CONFIG.panelId}.slide-panel--open {
                transform: translateX(0) !important;
            }
            #${CONFIG.panelId} .jaraba-marketplace__overlay {
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,0.45);
                z-index: -1;
            }
            #${CONFIG.panelId} .jaraba-marketplace__container {
                display: flex;
                flex-direction: column;
                height: 100%;
                background: var(--ej-bg-surface, #1e293b);
                color: var(--ej-text-primary, #f1f5f9);
                overflow: hidden;
            }

            /* — Header — */
            #${CONFIG.panelId} .jaraba-marketplace__header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 16px 20px;
                background: linear-gradient(135deg, #8b5cf6, #6d28d9);
                color: white;
                flex-shrink: 0;
            }
            #${CONFIG.panelId} .jaraba-marketplace__title {
                display: flex;
                align-items: center;
                gap: 10px;
                margin: 0;
                font-size: 16px;
                font-weight: 700;
                letter-spacing: -0.01em;
            }
            #${CONFIG.panelId} .jaraba-marketplace__close {
                background: rgba(255,255,255,0.18);
                border: none;
                border-radius: 8px;
                padding: 8px;
                cursor: pointer;
                color: white;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: background 0.15s;
            }
            #${CONFIG.panelId} .jaraba-marketplace__close:hover {
                background: rgba(255,255,255,0.3);
            }

            /* — Search — */
            #${CONFIG.panelId} .jaraba-marketplace__search-bar {
                position: relative;
                padding: 14px 16px;
                background: var(--ej-bg-surface-darker, #0f172a);
                border-bottom: 1px solid var(--ej-border, #334155);
                flex-shrink: 0;
            }
            #${CONFIG.panelId} .jaraba-marketplace__search-icon {
                position: absolute;
                left: 28px;
                top: 50%;
                transform: translateY(-50%);
                stroke: var(--ej-text-muted, #94a3b8);
                pointer-events: none;
            }
            #${CONFIG.panelId} .jaraba-marketplace__search-input {
                width: 100%;
                padding: 10px 14px 10px 40px;
                background: var(--ej-bg-surface, #1e293b);
                border: 1px solid var(--ej-border, #475569);
                border-radius: 10px;
                color: var(--ej-text-primary, #f1f5f9);
                font-size: 14px;
                transition: border-color 0.15s;
            }
            #${CONFIG.panelId} .jaraba-marketplace__search-input::placeholder {
                color: var(--ej-text-muted, #64748b);
            }
            #${CONFIG.panelId} .jaraba-marketplace__search-input:focus {
                outline: none;
                border-color: #8b5cf6;
                box-shadow: 0 0 0 3px rgba(139,92,246,0.15);
            }

            /* — Filters — */
            #${CONFIG.panelId} .jaraba-marketplace__filters {
                display: flex;
                gap: 6px;
                padding: 10px 16px;
                background: var(--ej-bg-surface-darker, #0f172a);
                overflow-x: auto;
                flex-shrink: 0;
                scrollbar-width: none;
            }
            #${CONFIG.panelId} .jaraba-marketplace__filters::-webkit-scrollbar {
                display: none;
            }
            #${CONFIG.panelId} .jaraba-marketplace__filter {
                padding: 6px 14px;
                border-radius: 20px;
                border: 1px solid var(--ej-border, #475569);
                background: transparent;
                color: var(--ej-text-muted, #94a3b8);
                font-size: 12px;
                font-weight: 500;
                cursor: pointer;
                white-space: nowrap;
                transition: all 0.15s;
            }
            #${CONFIG.panelId} .jaraba-marketplace__filter:hover {
                background: var(--ej-bg-surface, #1e293b);
                color: var(--ej-text-primary, #f1f5f9);
            }
            #${CONFIG.panelId} .jaraba-marketplace__filter.is-active {
                background: #8b5cf6;
                color: white;
                border-color: #8b5cf6;
            }

            /* — Body / Grid — */
            #${CONFIG.panelId} .jaraba-marketplace__body {
                flex: 1;
                overflow-y: auto;
                padding: 16px;
            }
            #${CONFIG.panelId} .jaraba-marketplace__grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }

            /* — Card — */
            #${CONFIG.panelId} .jaraba-marketplace__card {
                position: relative;
                background: var(--ej-bg-surface-darker, #0f172a);
                border: 1px solid var(--ej-border, #334155);
                border-radius: 12px;
                overflow: hidden;
                cursor: pointer;
                transition: all 0.2s ease;
                animation: jaraba-fade-in-up 0.3s ease both;
            }
            #${CONFIG.panelId} .jaraba-marketplace__card:hover {
                border-color: #8b5cf6;
                box-shadow: 0 4px 16px rgba(139,92,246,0.15);
                transform: translateY(-2px);
            }
            #${CONFIG.panelId} .jaraba-marketplace__card--locked {
                opacity: 0.6;
                cursor: not-allowed;
            }
            #${CONFIG.panelId} .jaraba-marketplace__card--locked:hover {
                border-color: var(--ej-border, #334155);
                box-shadow: none;
                transform: none;
            }
            #${CONFIG.panelId} .jaraba-marketplace__card-preview {
                aspect-ratio: 16/10;
                background: var(--ej-bg-surface, #1e293b);
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 12px;
                overflow: hidden;
            }
            #${CONFIG.panelId} .jaraba-marketplace__card-preview svg {
                width: 100%;
                height: 100%;
                max-width: 64px;
                max-height: 64px;
                opacity: 0.5;
            }
            #${CONFIG.panelId} .jaraba-marketplace__card-preview img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            #${CONFIG.panelId} .jaraba-marketplace__card-info {
                padding: 10px 12px;
            }
            #${CONFIG.panelId} .jaraba-marketplace__card-name {
                font-size: 12px;
                font-weight: 600;
                color: var(--ej-text-primary, #f1f5f9);
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            #${CONFIG.panelId} .jaraba-marketplace__card-desc {
                font-size: 11px;
                color: var(--ej-text-muted, #94a3b8);
                margin-top: 2px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            /* — Badge plan — */
            #${CONFIG.panelId} .jaraba-marketplace__badge {
                position: absolute;
                top: 8px;
                right: 8px;
                padding: 2px 8px;
                border-radius: 10px;
                font-size: 10px;
                font-weight: 700;
                letter-spacing: 0.04em;
                text-transform: uppercase;
            }
            #${CONFIG.panelId} .jaraba-marketplace__badge--free {
                background: rgba(16,185,129,0.15);
                color: #10b981;
            }
            #${CONFIG.panelId} .jaraba-marketplace__badge--premium {
                background: rgba(139,92,246,0.15);
                color: #a78bfa;
            }
            #${CONFIG.panelId} .jaraba-marketplace__badge--locked {
                background: rgba(239,68,68,0.15);
                color: #ef4444;
            }

            /* — Use button overlay — */
            #${CONFIG.panelId} .jaraba-marketplace__card-use {
                position: absolute;
                inset: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                background: rgba(139,92,246,0.85);
                opacity: 0;
                transition: opacity 0.2s ease;
            }
            #${CONFIG.panelId} .jaraba-marketplace__card:not(.jaraba-marketplace__card--locked):hover .jaraba-marketplace__card-use {
                opacity: 1;
            }
            #${CONFIG.panelId} .jaraba-marketplace__card-use-btn {
                padding: 8px 20px;
                background: white;
                color: #6d28d9;
                border: none;
                border-radius: 8px;
                font-size: 13px;
                font-weight: 700;
                cursor: pointer;
                transition: transform 0.15s;
            }
            #${CONFIG.panelId} .jaraba-marketplace__card-use-btn:hover {
                transform: scale(1.05);
            }

            /* — Empty / Loading — */
            #${CONFIG.panelId} .jaraba-marketplace__empty,
            #${CONFIG.panelId} .jaraba-marketplace__loading {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 60px 20px;
                text-align: center;
                color: var(--ej-text-muted, #94a3b8);
            }
            #${CONFIG.panelId} .jaraba-marketplace__empty svg {
                margin-bottom: 16px;
                opacity: 0.4;
            }
            #${CONFIG.panelId} .jaraba-marketplace__spinner {
                width: 32px;
                height: 32px;
                border: 3px solid var(--ej-border, #475569);
                border-top-color: #8b5cf6;
                border-radius: 50%;
                animation: jaraba-spin 0.8s linear infinite;
                margin-bottom: 16px;
            }

            /* — Counter chip — */
            #${CONFIG.panelId} .jaraba-marketplace__counter {
                padding: 8px 16px;
                font-size: 12px;
                color: var(--ej-text-muted, #64748b);
                border-top: 1px solid var(--ej-border, #334155);
                background: var(--ej-bg-surface-darker, #0f172a);
                flex-shrink: 0;
                text-align: center;
            }
        `;
        document.head.appendChild(style);
    }

    /* ------------------------------------------------------------------ */
    /*  Eventos                                                           */
    /* ------------------------------------------------------------------ */

    /**
     * Configura eventos del panel (cerrar, buscar, filtrar).
     *
     * @param {HTMLElement} panel — El slide-panel.
     */
    function setupPanelEvents(panel) {
        // Cerrar con overlay o botón X (solo bind una vez).
        if (!panel._eventsReady) {
            panel.querySelectorAll('[data-close-panel]').forEach(el => {
                el.addEventListener('click', closeMarketplace);
            });

            // ESC handler.
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && state.isOpen) {
                    closeMarketplace();
                }
            });

            // Búsqueda con debounce.
            const searchInput = panel.querySelector('#marketplace-search');
            if (searchInput) {
                let timeout;
                searchInput.addEventListener('input', (e) => {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => {
                        state.searchQuery = e.target.value.toLowerCase();
                        applyFilters();
                    }, 250);
                });
            }

            panel._eventsReady = true;
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Data loading                                                      */
    /* ------------------------------------------------------------------ */

    /**
     * Carga templates desde la API.
     */
    async function loadTemplates() {
        state.loading = true;
        showLoading(true);

        try {
            const response = await fetch(`${CONFIG.apiEndpoint}?format=gallery`, {
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();

            // Normalizar: la API puede devolver {data: {categories: [...]}} o {categories: [...]}.
            const galleryData = data.data || data;
            state.categories = galleryData.categories || [];

            // Flatten todas las templates con su categoría.
            state.allTemplates = [];
            state.categories.forEach(cat => {
                (cat.templates || []).forEach(tpl => {
                    state.allTemplates.push({
                        ...tpl,
                        categoryId: cat.id,
                        categoryLabel: cat.label,
                        categoryIcon: cat.icon || '',
                    });
                });
            });

            renderCategoryFilters();
            applyFilters();

        } catch (err) {
            console.error('[Jaraba Marketplace] Error cargando templates:', err);
            state.error = err.message;
            showError();
        } finally {
            state.loading = false;
            showLoading(false);
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Filtrado                                                          */
    /* ------------------------------------------------------------------ */

    /**
     * Aplica filtros de categoría y búsqueda sobre las templates cargadas.
     */
    function applyFilters() {
        let filtered = [...state.allTemplates];

        // Filtro por categoría.
        if (state.activeCategory !== 'all') {
            filtered = filtered.filter(t => t.categoryId === state.activeCategory);
        }

        // Filtro por búsqueda.
        if (state.searchQuery) {
            const q = state.searchQuery;
            filtered = filtered.filter(t =>
                (t.label || '').toLowerCase().includes(q) ||
                (t.description || '').toLowerCase().includes(q) ||
                (t.categoryLabel || '').toLowerCase().includes(q)
            );
        }

        state.filteredTemplates = filtered;
        renderTemplateGrid();
    }

    /* ------------------------------------------------------------------ */
    /*  Rendering                                                         */
    /* ------------------------------------------------------------------ */

    /**
     * Renderiza los botones de categoría.
     */
    function renderCategoryFilters() {
        const container = document.getElementById('marketplace-filters');
        if (!container) return;

        // Conteo por categoría.
        const counts = {};
        state.allTemplates.forEach(t => {
            counts[t.categoryId] = (counts[t.categoryId] || 0) + 1;
        });

        let html = `
            <button type="button"
                    class="jaraba-marketplace__filter is-active"
                    data-category="all"
                    role="tab"
                    aria-selected="true">
                ${Drupal.t('Todas')} (${state.allTemplates.length})
            </button>
        `;

        state.categories.forEach(cat => {
            const count = counts[cat.id] || 0;
            if (count === 0) return;

            html += `
                <button type="button"
                        class="jaraba-marketplace__filter"
                        data-category="${cat.id}"
                        role="tab"
                        aria-selected="false">
                    ${cat.label} (${count})
                </button>
            `;
        });

        container.innerHTML = html;

        // Bind click events.
        container.querySelectorAll('.jaraba-marketplace__filter').forEach(btn => {
            btn.addEventListener('click', () => {
                container.querySelectorAll('.jaraba-marketplace__filter').forEach(b => {
                    b.classList.remove('is-active');
                    b.setAttribute('aria-selected', 'false');
                });
                btn.classList.add('is-active');
                btn.setAttribute('aria-selected', 'true');

                state.activeCategory = btn.dataset.category;
                applyFilters();
            });
        });
    }

    /**
     * Renderiza el grid de template cards.
     */
    function renderTemplateGrid() {
        const grid = document.getElementById('marketplace-grid');
        const empty = document.getElementById('marketplace-empty');
        if (!grid) return;

        if (state.filteredTemplates.length === 0) {
            grid.innerHTML = '';
            if (empty) empty.style.display = 'flex';
            updateCounter(0);
            return;
        }

        if (empty) empty.style.display = 'none';

        grid.innerHTML = state.filteredTemplates.map((tpl, idx) => {
            const isLocked = !tpl.is_accessible;
            const isPremium = tpl.is_premium;

            // Badge.
            let badgeHtml = '';
            if (isLocked) {
                badgeHtml = `<span class="jaraba-marketplace__badge jaraba-marketplace__badge--locked">🔒 ${Drupal.t('Plan')}</span>`;
            } else if (isPremium) {
                badgeHtml = `<span class="jaraba-marketplace__badge jaraba-marketplace__badge--premium">★ Pro</span>`;
            } else {
                badgeHtml = `<span class="jaraba-marketplace__badge jaraba-marketplace__badge--free">${Drupal.t('Free')}</span>`;
            }

            // Preview: imagen si existe, sino placeholder SVG.
            let previewHtml;
            if (tpl.preview_image) {
                previewHtml = `<img src="${tpl.preview_image}" alt="${tpl.label}" loading="lazy" />`;
            } else {
                previewHtml = `
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <rect x="3" y="3" width="18" height="18" rx="2"/>
                        <path d="M3 9h18"/>
                        <path d="M9 21V9"/>
                    </svg>
                `;
            }

            // Stagger animation delay.
            const delay = Math.min(idx * 0.05, 0.5);

            return `
                <div class="jaraba-marketplace__card ${isLocked ? 'jaraba-marketplace__card--locked' : ''}"
                     data-template-id="${tpl.id}"
                     data-locked="${isLocked}"
                     style="animation-delay: ${delay}s"
                     tabindex="0"
                     role="button"
                     aria-label="${tpl.label}${isLocked ? ' — ' + Drupal.t('Requiere plan superior') : ''}">
                    <div class="jaraba-marketplace__card-preview">
                        ${previewHtml}
                    </div>
                    ${badgeHtml}
                    <div class="jaraba-marketplace__card-info">
                        <div class="jaraba-marketplace__card-name" title="${tpl.label}">${tpl.label}</div>
                        ${tpl.description ? `<div class="jaraba-marketplace__card-desc" title="${tpl.description}">${tpl.description}</div>` : ''}
                    </div>
                    ${!isLocked ? `
                        <div class="jaraba-marketplace__card-use" aria-hidden="true">
                            <button type="button" class="jaraba-marketplace__card-use-btn" data-use-template="${tpl.id}">
                                ${Drupal.t('Usar plantilla')}
                            </button>
                        </div>
                    ` : ''}
                </div>
            `;
        }).join('');

        updateCounter(state.filteredTemplates.length);

        // Bind use buttons.
        grid.querySelectorAll('[data-use-template]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const templateId = btn.dataset.useTemplate;
                useTemplate(templateId);
            });
        });

        // Bind card click (solo si no está locked).
        grid.querySelectorAll('.jaraba-marketplace__card:not(.jaraba-marketplace__card--locked)').forEach(card => {
            card.addEventListener('click', () => {
                const templateId = card.dataset.templateId;
                useTemplate(templateId);
            });
            card.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    const templateId = card.dataset.templateId;
                    useTemplate(templateId);
                }
            });
        });
    }

    /* ------------------------------------------------------------------ */
    /*  Usar template                                                     */
    /* ------------------------------------------------------------------ */

    /**
     * Usa un template: lo carga de la API y lo inserta en el Canvas.
     *
     * @param {string} templateId — ID del template.
     */
    async function useTemplate(templateId) {
        const editor = window.jarabaCanvasEditor?.editor;
        if (!editor) {
            console.error('[Jaraba Marketplace] Editor GrapesJS no disponible.');
            return;
        }

        try {
            // Fetch template completo con HTML.
            const response = await fetch(`${CONFIG.apiEndpoint}/${templateId}`, {
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();
            const tplData = data.data || data;

            if (!tplData.html && !tplData.content) {
                throw new Error(Drupal.t('La plantilla no tiene contenido.'));
            }

            const html = tplData.html || tplData.content;
            const css = tplData.css || tplData.styles || '';

            // Insertar en el Canvas.
            editor.addComponents(html);

            // Añadir estilos si los tiene.
            if (css) {
                const existingCss = editor.getCss() || '';
                editor.setStyle(existingCss + '\n' + css);
            }

            // Toast de confirmación.
            showInsertToast(tplData.label || templateId);

            // Cerrar marketplace.
            closeMarketplace();

        } catch (err) {
            console.error('[Jaraba Marketplace] Error usando template:', err);
            alert(Drupal.t('Error al cargar la plantilla: @error', { '@error': err.message }));
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Helpers UI                                                        */
    /* ------------------------------------------------------------------ */

    /**
     * Muestra/oculta el estado de carga.
     *
     * @param {boolean} show
     */
    function showLoading(show) {
        const loading = document.getElementById('marketplace-loading');
        const grid = document.getElementById('marketplace-grid');
        if (loading) loading.style.display = show ? 'flex' : 'none';
        if (grid) grid.style.display = show ? 'none' : 'grid';
    }

    /**
     * Muestra estado de error.
     */
    function showError() {
        const grid = document.getElementById('marketplace-grid');
        if (grid) {
            grid.innerHTML = `
                <div style="grid-column:1/-1;text-align:center;padding:40px;color:#ef4444;">
                    <p>${Drupal.t('Error al cargar las plantillas.')}</p>
                    <button type="button" onclick="document.getElementById('marketplace-grid').innerHTML='';location.reload();"
                            style="margin-top:8px;padding:8px 16px;background:#8b5cf6;color:white;border:none;border-radius:8px;cursor:pointer;">
                        ${Drupal.t('Reintentar')}
                    </button>
                </div>
            `;
        }
    }

    /**
     * Actualiza el contador de templates visibles.
     *
     * @param {number} count
     */
    function updateCounter(count) {
        let counter = document.querySelector('.jaraba-marketplace__counter');
        if (!counter) {
            const panel = document.getElementById(CONFIG.panelId);
            if (!panel) return;
            counter = document.createElement('div');
            counter.className = 'jaraba-marketplace__counter';
            panel.querySelector('.jaraba-marketplace__container').appendChild(counter);
        }
        counter.textContent = Drupal.t('@count plantilla(s) disponible(s)', { '@count': count });
    }

    /**
     * Muestra un toast de confirmación al insertar un template.
     *
     * @param {string} templateName
     */
    function showInsertToast(templateName) {
        // Reusar el toast del drag & drop si existe.
        const prev = document.querySelector('.jaraba-drop-toast');
        if (prev) prev.remove();

        const toast = document.createElement('div');
        toast.className = 'jaraba-drop-toast';
        toast.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2.5"
                 stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M20 6L9 17l-5-5"/>
            </svg>
            <span>${Drupal.t('Plantilla "@name" añadida', { '@name': templateName })}</span>
        `;
        document.body.appendChild(toast);

        // Auto-remove tras animación.
        setTimeout(() => {
            if (toast.parentNode) toast.remove();
        }, 3500);
    }

    /* ------------------------------------------------------------------ */
    /*  Drupal behavior                                                   */
    /* ------------------------------------------------------------------ */

    /**
     * Drupal behavior para inicializar el plugin de Templates Marketplace.
     */
    Drupal.behaviors.jarabaTemplateMarketplace = {
        attach: function (context) {
            if (window.jarabaCanvasEditor && window.jarabaCanvasEditor.editor) {
                const editor = window.jarabaCanvasEditor.editor;

                // Prevenir inicialización múltiple.
                if (!editor._jarabaMarketplaceInitialized) {
                    initMarketplacePlugin(editor);
                    editor._jarabaMarketplaceInitialized = true;
                }
            }
        },
    };

})(Drupal, drupalSettings, once);

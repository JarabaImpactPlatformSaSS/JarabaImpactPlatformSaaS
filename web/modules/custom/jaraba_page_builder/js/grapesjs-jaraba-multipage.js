/**
 * @file
 * Jaraba Multi-Page Editor — Plugin GrapesJS (Sprint C2).
 *
 * Tabs de páginas tipo IDE en la parte superior del Canvas Editor.
 * Permite editar múltiples páginas del sitio sin abandonar el editor,
 * con estado en memoria, indicador dirty, y guardado masivo.
 *
 * ARQUITECTURA:
 * - Tab bar insertada debajo del header del Canvas Editor
 * - Map<pageId, PageState> para gestionar estado de cada página
 * - Fetch GET/PATCH /api/v1/pages/{id}/canvas para leer/escribir
 * - Integración con Site Builder tree via evento jaraba:tree:openPage
 *
 * @see docs/planificacion/20260209-Plan_Mejoras_Page_Site_Builder_v3.md §9
 */

(function (Drupal, drupalSettings, once) {
    'use strict';

    /* ------------------------------------------------------------------ */
    /*  Configuración                                                     */
    /* ------------------------------------------------------------------ */

    const CONFIG = {
        canvasApiBase: '/api/v1/pages',
        treeApiBase: '/api/v1/site',
        maxTabs: 8,
        tabBarId: 'page-tabs',
        autosaveDebounceMs: 2000,
    };

    /* ------------------------------------------------------------------ */
    /*  Clase principal                                                   */
    /* ------------------------------------------------------------------ */

    /**
     * Editor multi-página con tabs tipo IDE.
     */
    class JarabaMultiPageEditor {
        /**
         * @param {Object} editor — Instancia de GrapesJS.
         */
        constructor(editor) {
            this.editor = editor;
            /** @type {Map<string, PageState>} pageId → estado */
            this.pages = new Map();
            this.activePageId = null;
            this.tabOrder = [];  // Orden de las tabs (mutable).
            this._dirty = new Set();  // IDs de páginas con cambios sin guardar.
            this._saving = false;

            this._init();
        }

        /* -------------------------------------------------------------- */
        /*  Init                                                          */
        /* -------------------------------------------------------------- */

        /**
         * Inicializa el plugin: crea tab bar, registra comandos, eventos.
         */
        _init() {
            this._createTabBar();
            this._registerCommands();
            this._bindEditorEvents();
            this._bindKeyboardShortcuts();
            this._openInitialPage();

            console.log(Drupal.t('[Jaraba C2] Multi-Page Editor inicializado.'));
        }

        /**
         * Crea el contenedor de tabs en el DOM.
         */
        _createTabBar() {
            const header = document.querySelector('.canvas-editor__header');
            if (!header) return;

            // Crear nav si no existe.
            let tabBar = document.getElementById(CONFIG.tabBarId);
            if (!tabBar) {
                tabBar = document.createElement('nav');
                tabBar.id = CONFIG.tabBarId;
                tabBar.className = 'canvas-editor__page-tabs';
                tabBar.setAttribute('role', 'tablist');
                tabBar.setAttribute('aria-label', Drupal.t('Páginas abiertas'));

                // Insertar después del header.
                header.parentNode.insertBefore(tabBar, header.nextSibling);
            }

            // Inyectar estilos.
            this._injectStyles();
        }

        /**
         * Registra comandos GrapesJS para multi-page.
         */
        _registerCommands() {
            const self = this;

            this.editor.Commands.add('jaraba:multipage:open', {
                run(editor, sender, opts = {}) {
                    if (opts.pageId) {
                        self.openPage(opts.pageId, opts.title);
                    }
                },
            });

            this.editor.Commands.add('jaraba:multipage:save-all', {
                run() {
                    self.saveAll();
                },
            });

            this.editor.Commands.add('jaraba:multipage:close', {
                run(editor, sender, opts = {}) {
                    if (opts.pageId) {
                        self.closePage(opts.pageId);
                    }
                },
            });
        }

        /**
         * Vincula eventos del editor para tracking de cambios (dirty state).
         */
        _bindEditorEvents() {
            const self = this;

            // Marcar página actual como dirty en cualquier cambio.
            const markDirty = () => {
                if (self.activePageId) {
                    self._dirty.add(self.activePageId);
                    self._updateTabUI();
                }
            };

            this.editor.on('component:add', markDirty);
            this.editor.on('component:remove', markDirty);
            this.editor.on('component:update', markDirty);
            this.editor.on('style:change', markDirty);
            this.editor.on('canvas:drop', markDirty);

            // Evento personalizado para integración con Site Builder tree.
            document.addEventListener('jaraba:tree:openPage', (e) => {
                if (e.detail && e.detail.pageId) {
                    self.openPage(
                        String(e.detail.pageId),
                        e.detail.title || Drupal.t('Página')
                    );
                }
            });

            // Interceptar Ctrl+S para guardar todas las páginas dirty.
            document.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                    e.preventDefault();
                    self.saveAll();
                }
            });
        }

        /**
         * Keyboard shortcuts para tabs.
         */
        _bindKeyboardShortcuts() {
            const self = this;

            document.addEventListener('keydown', (e) => {
                // Ctrl+Tab / Ctrl+Shift+Tab → siguiente/anterior tab.
                if (e.ctrlKey && e.key === 'Tab') {
                    e.preventDefault();
                    const idx = self.tabOrder.indexOf(self.activePageId);
                    if (idx === -1) return;

                    let nextIdx;
                    if (e.shiftKey) {
                        nextIdx = idx === 0 ? self.tabOrder.length - 1 : idx - 1;
                    } else {
                        nextIdx = idx === self.tabOrder.length - 1 ? 0 : idx + 1;
                    }
                    self.switchToPage(self.tabOrder[nextIdx]);
                }

                // Ctrl+W → cerrar tab actual.
                if ((e.ctrlKey || e.metaKey) && e.key === 'w') {
                    // Solo si hay más de una tab abierta.
                    if (self.tabOrder.length > 1) {
                        e.preventDefault();
                        self.closePage(self.activePageId);
                    }
                }
            });
        }

        /**
         * Abre la página inicial (la que ya está cargada en el canvas).
         */
        _openInitialPage() {
            // Detectar la página actual del canvas.
            const pageId = drupalSettings.jarabaCanvas?.pageId ||
                drupalSettings.jarabaSiteBuilder?.currentPageId;
            const pageTitle = drupalSettings.jarabaCanvas?.pageTitle ||
                document.querySelector('.canvas-editor__page-title')?.textContent?.trim() ||
                Drupal.t('Página principal');

            if (pageId) {
                // La página ya está cargada — capturo su estado actual.
                this.pages.set(String(pageId), {
                    components: this.editor.getComponents(),
                    styles: this.editor.getStyle(),
                    html: '',
                    css: '',
                    title: pageTitle,
                    loaded: true,
                });
                this.activePageId = String(pageId);
                this.tabOrder.push(String(pageId));
                this._updateTabUI();
            }
        }

        /* -------------------------------------------------------------- */
        /*  API pública                                                   */
        /* -------------------------------------------------------------- */

        /**
         * Abre una página en una nueva tab.
         *
         * @param {string} pageId — ID de la entidad de página.
         * @param {string} title — Título para mostrar en la tab.
         */
        async openPage(pageId, title = '') {
            pageId = String(pageId);

            // Si ya está abierta → switch.
            if (this.pages.has(pageId)) {
                this.switchToPage(pageId);
                return;
            }

            // Límite de tabs.
            if (this.tabOrder.length >= CONFIG.maxTabs) {
                alert(Drupal.t('Máximo @count pestañas abiertas. Cierra alguna antes.', {
                    '@count': CONFIG.maxTabs,
                }));
                return;
            }

            try {
                // Fetch canvas data.
                const response = await fetch(`${CONFIG.canvasApiBase}/${pageId}/canvas`, {
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const data = await response.json();

                // Guardar en el Map.
                this.pages.set(pageId, {
                    components: data.components || [],
                    styles: data.styles || [],
                    html: data.html || '',
                    css: data.css || '',
                    title: title || Drupal.t('Página @id', { '@id': pageId }),
                    loaded: true,
                });

                this.tabOrder.push(pageId);
                this.switchToPage(pageId);

            } catch (err) {
                console.error('[Jaraba C2] Error abriendo página:', err);
                alert(Drupal.t('Error al abrir la página: @error', { '@error': err.message }));
            }
        }

        /**
         * Cambia entre tabs guardando el estado actual en memoria.
         *
         * @param {string} pageId — ID de la página destino.
         */
        switchToPage(pageId) {
            pageId = String(pageId);

            if (pageId === this.activePageId) return;
            if (!this.pages.has(pageId)) return;

            // 1. Guardar estado de la página actual en memoria.
            if (this.activePageId && this.pages.has(this.activePageId)) {
                const currentState = this.pages.get(this.activePageId);
                currentState.components = this.editor.getComponents();
                currentState.styles = this.editor.getStyle();
                currentState.html = this.editor.getHtml();
                currentState.css = this.editor.getCss();
            }

            // 2. Cargar la nueva página en el canvas.
            const pageData = this.pages.get(pageId);

            // Usar HTML/CSS si están disponibles, sino componentes.
            if (pageData.html) {
                this.editor.setComponents(pageData.html);
            } else if (pageData.components) {
                this.editor.setComponents(pageData.components);
            }

            if (pageData.css) {
                this.editor.setStyle(pageData.css);
            } else if (pageData.styles) {
                this.editor.setStyle(pageData.styles);
            }

            // 3. Actualizar estado.
            this.activePageId = pageId;
            this._updateTabUI();

            console.log(`[Jaraba C2] Switched to page ${pageId}: "${pageData.title}"`);
        }

        /**
         * Cierra una tab. Pregunta antes si hay cambios sin guardar.
         *
         * @param {string} pageId — ID de la página a cerrar.
         */
        async closePage(pageId) {
            pageId = String(pageId);

            // No puede cerrar la última tab.
            if (this.tabOrder.length <= 1) {
                return;
            }

            // Confirmar si dirty.
            if (this._dirty.has(pageId)) {
                const pageData = this.pages.get(pageId);
                const title = pageData?.title || pageId;
                const proceed = confirm(
                    Drupal.t('¿Cerrar "@title"? Hay cambios sin guardar.', { '@title': title })
                );
                if (!proceed) return;
            }

            // Determinar siguiente tab activa.
            const idx = this.tabOrder.indexOf(pageId);
            this.tabOrder.splice(idx, 1);
            this.pages.delete(pageId);
            this._dirty.delete(pageId);

            // Activar la tab adyacente.
            if (pageId === this.activePageId) {
                const nextIdx = Math.min(idx, this.tabOrder.length - 1);
                this.activePageId = null;  // Reset para forzar switch.
                this.switchToPage(this.tabOrder[nextIdx]);
            } else {
                this._updateTabUI();
            }
        }

        /**
         * Guarda todas las páginas con cambios pendientes (dirty).
         */
        async saveAll() {
            if (this._saving) return;
            this._saving = true;

            // Actualizar estado de la página activa.
            if (this.activePageId && this.pages.has(this.activePageId)) {
                const current = this.pages.get(this.activePageId);
                current.components = this.editor.getComponents();
                current.styles = this.editor.getStyle();
                current.html = this.editor.getHtml();
                current.css = this.editor.getCss();
            }

            const dirtyIds = [...this._dirty];
            if (dirtyIds.length === 0) {
                this._saving = false;
                this._showToast(Drupal.t('Nada que guardar'));
                return;
            }

            let successCount = 0;
            let errorCount = 0;

            for (const pid of dirtyIds) {
                const pageData = this.pages.get(pid);
                if (!pageData) continue;

                try {
                    const response = await fetch(`${CONFIG.canvasApiBase}/${pid}/canvas`, {
                        method: 'PATCH',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': drupalSettings.jarabaCanvas?.csrfToken || '',
                        },
                        body: JSON.stringify({
                            components: JSON.parse(
                                JSON.stringify(pageData.components || [])
                            ),
                            styles: JSON.parse(
                                JSON.stringify(pageData.styles || [])
                            ),
                            html: pageData.html || '',
                            css: pageData.css || '',
                        }),
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }

                    this._dirty.delete(pid);
                    successCount++;

                } catch (err) {
                    console.error(`[Jaraba C2] Error guardando página ${pid}:`, err);
                    errorCount++;
                }
            }

            this._saving = false;
            this._updateTabUI();

            if (errorCount > 0) {
                this._showToast(
                    Drupal.t('@ok guardada(s), @err con error', {
                        '@ok': successCount,
                        '@err': errorCount,
                    }),
                    'error'
                );
            } else {
                this._showToast(
                    Drupal.t('@count página(s) guardada(s)', { '@count': successCount })
                );
            }
        }

        /* -------------------------------------------------------------- */
        /*  UI rendering                                                  */
        /* -------------------------------------------------------------- */

        /**
         * Actualiza la barra de tabs completa.
         */
        _updateTabUI() {
            const tabBar = document.getElementById(CONFIG.tabBarId);
            if (!tabBar) return;

            let html = '';

            this.tabOrder.forEach(pid => {
                const pageData = this.pages.get(pid);
                if (!pageData) return;

                const isActive = pid === this.activePageId;
                const isDirty = this._dirty.has(pid);

                html += `
                    <div class="canvas-editor__page-tab ${isActive ? 'is-active' : ''} ${isDirty ? 'is-dirty' : ''}"
                         data-page-id="${pid}"
                         role="tab"
                         aria-selected="${isActive}"
                         tabindex="${isActive ? '0' : '-1'}"
                         title="${pageData.title}${isDirty ? ' — ' + Drupal.t('Sin guardar') : ''}">
                        <span class="canvas-editor__page-tab-title">${this._escapeHtml(pageData.title)}</span>
                        ${isDirty ? '<span class="canvas-editor__page-tab-dirty" aria-label="' + Drupal.t('Cambios sin guardar') + '"></span>' : ''}
                        ${this.tabOrder.length > 1 ? `
                            <button type="button"
                                    class="canvas-editor__page-tab-close"
                                    data-close-tab="${pid}"
                                    aria-label="${Drupal.t('Cerrar @title', { '@title': pageData.title })}"
                                    title="${Drupal.t('Cerrar')}">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                    <path d="M18 6L6 18M6 6l12 12"/>
                                </svg>
                            </button>
                        ` : ''}
                    </div>
                `;
            });

            // Botón "+" para nueva página.
            html += `
                <button type="button"
                        class="canvas-editor__page-tab-new"
                        id="multipage-new-tab"
                        title="${Drupal.t('Abrir otra página')}"
                        aria-label="${Drupal.t('Abrir otra página')}">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                </button>
            `;

            tabBar.innerHTML = html;

            // Bind eventos.
            tabBar.querySelectorAll('.canvas-editor__page-tab').forEach(tab => {
                tab.addEventListener('click', (e) => {
                    // No switch si se hizo click en el botón de cerrar.
                    if (e.target.closest('[data-close-tab]')) return;
                    this.switchToPage(tab.dataset.pageId);
                });
            });

            tabBar.querySelectorAll('[data-close-tab]').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.closePage(btn.dataset.closeTab);
                });
            });

            // Botón nueva tab — abre selector de páginas del site tree.
            const newTabBtn = tabBar.querySelector('#multipage-new-tab');
            if (newTabBtn) {
                newTabBtn.addEventListener('click', () => this._showPagePicker());
            }
        }

        /**
         * Muestra un selector de páginas del site tree para abrir como tab.
         */
        async _showPagePicker() {
            try {
                const response = await fetch(`${CONFIG.treeApiBase}/tree`, {
                    credentials: 'same-origin',
                });
                if (!response.ok) throw new Error(`HTTP ${response.status}`);

                const data = await response.json();
                const treeData = data.data || data.tree || data;

                // Construir un diálogo simple.
                this._showPickerDialog(treeData);

            } catch (err) {
                console.error('[Jaraba C2] Error cargando site tree:', err);
                alert(Drupal.t('Error al cargar el árbol de páginas.'));
            }
        }

        /**
         * Muestra un diálogo para seleccionar una página del árbol.
         *
         * @param {Array} treeData — Datos del árbol de páginas.
         */
        _showPickerDialog(treeData) {
            // Quitar diálogo previo si existe.
            const prev = document.getElementById('multipage-picker');
            if (prev) prev.remove();

            const dialog = document.createElement('div');
            dialog.id = 'multipage-picker';
            dialog.className = 'multipage-picker';
            dialog.setAttribute('role', 'dialog');
            dialog.setAttribute('aria-modal', 'true');
            dialog.setAttribute('aria-labelledby', 'multipage-picker-title');

            // Flatten tree para listar todas las páginas.
            const pages = this._flattenTree(treeData);

            // Excluir las ya abiertas.
            const openIds = new Set(this.tabOrder);
            const available = pages.filter(p => !openIds.has(String(p.id)));

            let listHtml = '';
            if (available.length === 0) {
                listHtml = `<p class="multipage-picker__empty">${Drupal.t('No hay más páginas disponibles.')}</p>`;
            } else {
                listHtml = available.map(p => `
                    <button type="button"
                            class="multipage-picker__item"
                            data-page-id="${p.id}"
                            data-page-title="${this._escapeHtml(p.title)}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/>
                            <polyline points="14,2 14,8 20,8"/>
                        </svg>
                        <span>${this._escapeHtml(p.title)}</span>
                        ${p.path ? `<small>${this._escapeHtml(p.path)}</small>` : ''}
                    </button>
                `).join('');
            }

            dialog.innerHTML = `
                <div class="multipage-picker__backdrop" data-close-picker></div>
                <div class="multipage-picker__panel">
                    <header class="multipage-picker__header">
                        <h3 id="multipage-picker-title">${Drupal.t('Abrir página')}</h3>
                        <button type="button" class="multipage-picker__close" data-close-picker aria-label="${Drupal.t('Cerrar')}">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M18 6L6 18M6 6l12 12"/>
                            </svg>
                        </button>
                    </header>
                    <div class="multipage-picker__list">
                        ${listHtml}
                    </div>
                </div>
            `;

            document.body.appendChild(dialog);

            // Eventos.
            dialog.querySelectorAll('[data-close-picker]').forEach(el => {
                el.addEventListener('click', () => dialog.remove());
            });

            dialog.querySelectorAll('.multipage-picker__item').forEach(btn => {
                btn.addEventListener('click', () => {
                    const pid = btn.dataset.pageId;
                    const ptitle = btn.dataset.pageTitle;
                    dialog.remove();
                    this.openPage(pid, ptitle);
                });
            });

            // ESC para cerrar.
            const escHandler = (e) => {
                if (e.key === 'Escape') {
                    dialog.remove();
                    document.removeEventListener('keydown', escHandler);
                }
            };
            document.addEventListener('keydown', escHandler);
        }

        /* -------------------------------------------------------------- */
        /*  Helpers                                                       */
        /* -------------------------------------------------------------- */

        /**
         * Aplana un árbol de páginas en una lista plana.
         *
         * @param {Array|Object} tree — Nodos del árbol.
         * @returns {Array<{id, title, path}>}
         */
        _flattenTree(tree) {
            const result = [];
            const items = Array.isArray(tree) ? tree : (tree.children || tree.items || [tree]);

            const walk = (nodes, depth = 0) => {
                if (!Array.isArray(nodes)) return;
                nodes.forEach(node => {
                    if (node.page_id || node.pageId || node.id) {
                        result.push({
                            id: node.page_id || node.pageId || node.id,
                            title: node.title || node.label || node.name || '',
                            path: node.path || node.url || '',
                            depth,
                        });
                    }
                    if (node.children) {
                        walk(node.children, depth + 1);
                    }
                });
            };

            walk(items);
            return result;
        }

        /**
         * Escapa HTML para prevenir XSS.
         *
         * @param {string} str
         * @returns {string}
         */
        _escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str || '';
            return div.innerHTML;
        }

        /**
         * Muestra un toast de confirmación.
         *
         * @param {string} message
         * @param {string} type — 'success' | 'error'
         */
        _showToast(message, type = 'success') {
            const prev = document.querySelector('.jaraba-multipage-toast');
            if (prev) prev.remove();

            const toast = document.createElement('div');
            toast.className = `jaraba-multipage-toast jaraba-multipage-toast--${type}`;
            const iconSvg = type === 'error'
                ? '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>'
                : '<path d="M20 6L9 17l-5-5"/>';

            toast.innerHTML = `
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    ${iconSvg}
                </svg>
                <span>${message}</span>
            `;
            document.body.appendChild(toast);

            setTimeout(() => {
                if (toast.parentNode) toast.remove();
            }, 3500);
        }

        /* -------------------------------------------------------------- */
        /*  Estilos                                                       */
        /* -------------------------------------------------------------- */

        /**
         * Inyecta estilos CSS para las tabs y el picker.
         */
        _injectStyles() {
            if (document.getElementById('jaraba-multipage-styles')) return;

            const style = document.createElement('style');
            style.id = 'jaraba-multipage-styles';
            style.innerHTML = `
                /* — Tab bar — */
                .canvas-editor__page-tabs {
                    display: flex;
                    align-items: stretch;
                    gap: 0;
                    background: var(--ej-bg-surface-darker, #0f172a);
                    border-bottom: 1px solid var(--ej-border, #1e293b);
                    padding: 0 8px;
                    overflow-x: auto;
                    scrollbar-width: none;
                    flex-shrink: 0;
                    height: 36px;
                }
                .canvas-editor__page-tabs::-webkit-scrollbar { display: none; }

                /* — Tab — */
                .canvas-editor__page-tab {
                    display: flex;
                    align-items: center;
                    gap: 6px;
                    padding: 0 14px;
                    font-size: 12px;
                    font-weight: 500;
                    color: var(--ej-text-muted, #94a3b8);
                    cursor: pointer;
                    white-space: nowrap;
                    border-bottom: 2px solid transparent;
                    border-right: 1px solid var(--ej-border, #1e293b);
                    transition: all 0.15s ease;
                    position: relative;
                    user-select: none;
                }
                .canvas-editor__page-tab:hover {
                    color: var(--ej-text-primary, #e2e8f0);
                    background: rgba(255,255,255,0.03);
                }
                .canvas-editor__page-tab.is-active {
                    color: var(--ej-text-primary, #f1f5f9);
                    background: var(--ej-bg-surface, #1e293b);
                    border-bottom-color: #8b5cf6;
                    font-weight: 600;
                }

                /* — Dirty dot — */
                .canvas-editor__page-tab-dirty {
                    width: 6px;
                    height: 6px;
                    border-radius: 50%;
                    background: #f59e0b;
                    flex-shrink: 0;
                }

                /* — Close button — */
                .canvas-editor__page-tab-close {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 2px;
                    border: none;
                    background: transparent;
                    color: inherit;
                    border-radius: 4px;
                    cursor: pointer;
                    opacity: 0;
                    transition: all 0.15s;
                    margin-left: 2px;
                }
                .canvas-editor__page-tab:hover .canvas-editor__page-tab-close,
                .canvas-editor__page-tab.is-active .canvas-editor__page-tab-close {
                    opacity: 0.6;
                }
                .canvas-editor__page-tab-close:hover {
                    opacity: 1 !important;
                    background: rgba(239,68,68,0.15);
                    color: #ef4444;
                }

                /* — Tab title — */
                .canvas-editor__page-tab-title {
                    max-width: 140px;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }

                /* — New tab button — */
                .canvas-editor__page-tab-new {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    width: 32px;
                    min-width: 32px;
                    border: none;
                    background: transparent;
                    color: var(--ej-text-muted, #64748b);
                    cursor: pointer;
                    border-radius: 4px;
                    transition: all 0.15s;
                    margin: 4px;
                }
                .canvas-editor__page-tab-new:hover {
                    background: rgba(255,255,255,0.06);
                    color: var(--ej-text-primary, #f1f5f9);
                }

                /* — Toast — */
                .jaraba-multipage-toast {
                    position: fixed;
                    bottom: 24px;
                    left: 50%;
                    transform: translateX(-50%);
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    padding: 10px 20px;
                    border-radius: 10px;
                    font-size: 13px;
                    font-weight: 600;
                    z-index: 100000;
                    animation: jaraba-toast-in 0.3s ease;
                    pointer-events: none;
                }
                .jaraba-multipage-toast--success {
                    background: rgba(16,185,129,0.15);
                    color: #10b981;
                    border: 1px solid rgba(16,185,129,0.3);
                    backdrop-filter: blur(8px);
                }
                .jaraba-multipage-toast--error {
                    background: rgba(239,68,68,0.15);
                    color: #ef4444;
                    border: 1px solid rgba(239,68,68,0.3);
                    backdrop-filter: blur(8px);
                }
                @keyframes jaraba-toast-in {
                    from { opacity: 0; transform: translateX(-50%) translateY(12px); }
                    to { opacity: 1; transform: translateX(-50%) translateY(0); }
                }

                /* — Page Picker dialog — */
                .multipage-picker {
                    position: fixed;
                    inset: 0;
                    z-index: 100001;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .multipage-picker__backdrop {
                    position: absolute;
                    inset: 0;
                    background: rgba(0,0,0,0.5);
                    backdrop-filter: blur(4px);
                }
                .multipage-picker__panel {
                    position: relative;
                    background: var(--ej-bg-surface, #1e293b);
                    border: 1px solid var(--ej-border, #334155);
                    border-radius: 16px;
                    width: 420px;
                    max-height: 480px;
                    display: flex;
                    flex-direction: column;
                    box-shadow: 0 20px 60px rgba(0,0,0,0.4);
                    animation: jaraba-fade-in-up 0.2s ease;
                }
                @keyframes jaraba-fade-in-up {
                    from { opacity: 0; transform: translateY(12px); }
                    to { opacity: 1; transform: translateY(0); }
                }
                .multipage-picker__header {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    padding: 16px 20px;
                    border-bottom: 1px solid var(--ej-border, #334155);
                }
                .multipage-picker__header h3 {
                    margin: 0;
                    font-size: 15px;
                    font-weight: 700;
                    color: var(--ej-text-primary, #f1f5f9);
                }
                .multipage-picker__close {
                    background: transparent;
                    border: none;
                    color: var(--ej-text-muted, #94a3b8);
                    cursor: pointer;
                    padding: 4px;
                    border-radius: 6px;
                    display: flex;
                    transition: all 0.15s;
                }
                .multipage-picker__close:hover {
                    background: rgba(255,255,255,0.06);
                    color: var(--ej-text-primary, #f1f5f9);
                }
                .multipage-picker__list {
                    overflow-y: auto;
                    padding: 8px;
                }
                .multipage-picker__item {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    width: 100%;
                    padding: 10px 14px;
                    border: none;
                    background: transparent;
                    color: var(--ej-text-primary, #f1f5f9);
                    font-size: 13px;
                    cursor: pointer;
                    border-radius: 8px;
                    text-align: left;
                    transition: all 0.15s;
                }
                .multipage-picker__item:hover {
                    background: rgba(139,92,246,0.1);
                }
                .multipage-picker__item svg {
                    flex-shrink: 0;
                    stroke: var(--ej-text-muted, #64748b);
                }
                .multipage-picker__item span {
                    flex: 1;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    white-space: nowrap;
                }
                .multipage-picker__item small {
                    color: var(--ej-text-muted, #64748b);
                    font-size: 11px;
                    flex-shrink: 0;
                }
                .multipage-picker__empty {
                    text-align: center;
                    padding: 40px 20px;
                    color: var(--ej-text-muted, #94a3b8);
                    font-size: 13px;
                }
            `;
            document.head.appendChild(style);
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Drupal behavior                                                   */
    /* ------------------------------------------------------------------ */

    Drupal.behaviors.jarabaMultiPageEditor = {
        attach: function (context) {
            if (window.jarabaCanvasEditor && window.jarabaCanvasEditor.editor) {
                const editor = window.jarabaCanvasEditor.editor;

                if (!editor._jarabaMultiPageInitialized) {
                    window.jarabaMultiPageEditor = new JarabaMultiPageEditor(editor);
                    editor._jarabaMultiPageInitialized = true;
                }
            }
        },
    };

})(Drupal, drupalSettings, once);

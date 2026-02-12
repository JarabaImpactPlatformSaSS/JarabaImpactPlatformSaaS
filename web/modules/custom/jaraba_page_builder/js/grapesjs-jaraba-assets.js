/**
 * @file
 * Jaraba Assets Manager - Plugin GrapesJS.
 *
 * Intercepta el Asset Manager de GrapesJS y lo reemplaza por un
 * slide-panel premium que integra la Media Library de Drupal.
 *
 * @see docs/tecnicos/20260204-Media_Browser_Integration.md
 */

(function (Drupal, drupalSettings, once) {
    'use strict';

    /**
     * Configuración del Asset Browser.
     */
    const CONFIG = {
        apiEndpoint: '/api/v1/page-builder/assets',
        defaultLimit: 24,
        allowedTypes: ['image', 'video', 'document', 'all'],
        thumbnailFallback: '/core/misc/icons/787878/file.svg',
    };

    /**
     * State del Asset Browser.
     */
    let state = {
        isOpen: false,
        currentType: 'all',
        searchQuery: '',
        items: [],
        selectedItems: [],
        loading: false,
        offset: 0,
        total: 0,
        callback: null, // Callback de GrapesJS para insertar asset
        target: null,   // Componente destino
    };

    /**
     * Inicializa el plugin de Assets para GrapesJS.
     *
     * @param {Object} editor - Instancia de GrapesJS.
     */
    function initAssetsPlugin(editor) {
        // CRÍTICO: Sobrescribir el comando 'open-assets' ANTES de que GrapesJS lo use
        // Esto previene que se abra el modal nativo
        editor.Commands.add('open-assets', {
            run(editor, sender, opts = {}) {
                // Capturar opciones para el callback
                if (opts.select && typeof opts.select === 'function') {
                    state.callback = opts.select;
                }
                state.target = opts.target || null;

                // Abrir nuestro slide-panel en lugar del modal nativo
                openAssetBrowser();
            },
            stop(editor) {
                // Cerrar slide-panel si está abierto
                closeAssetBrowser();
            }
        });

        // Interceptar evento 'asset:open' como respaldo
        editor.on('asset:open', (options) => {
            // Solo abrir si no está ya abierto (evitar duplicados)
            if (!state.isOpen) {
                options = options || {};
                if (options.select && typeof options.select === 'function') {
                    state.callback = options.select;
                }
                state.target = options.target || null;
                openAssetBrowser();
            }
        });

        console.log('Jaraba Assets Plugin inicializado (comando open-assets sobrescrito).');
    }

    /**
     * Abre el slide-panel del Asset Browser.
     */
    function openAssetBrowser() {
        if (state.isOpen) return;

        state.isOpen = true;
        state.selectedItems = [];

        // CRÍTICO: Cerrar INMEDIATAMENTE cualquier modal nativo de GrapesJS
        closeNativeGrapesJSModal();

        // Crear estructura del slide-panel si no existe.
        let panel = document.getElementById('jaraba-asset-browser');
        if (!panel) {
            panel = createAssetBrowserPanel();
            document.body.appendChild(panel);
        }

        // Mostrar panel.
        panel.classList.add('slide-panel--open');
        document.body.classList.add('slide-panel-open');

        // Cargar assets.
        loadAssets();

        // Focus trap y eventos.
        setupPanelEvents(panel);
    }

    /**
     * Cierra cualquier modal nativo de GrapesJS que pueda estar abierto.
     * GrapesJS usa el selector .gjs-am-assets o .gjs-mdl-dialog para su modal.
     */
    function closeNativeGrapesJSModal() {
        // Obtener el editor si existe
        const editor = window.jarabaCanvasEditor?.editor ||
            (typeof grapesjs !== 'undefined' && grapesjs.editors ? grapesjs.editors[0] : null);

        if (editor) {
            // Método 1: Cerrar el modal de GrapesJS via API
            try {
                if (editor.Modal && editor.Modal.isOpen()) {
                    editor.Modal.close();
                    console.log('Modal nativo de GrapesJS cerrado via API.');
                }
            } catch (e) {
                console.log('No se pudo cerrar modal via API:', e);
            }
        }

        // Método 2: Ocultar via CSS los elementos del modal nativo
        const nativeModalSelectors = [
            '.gjs-mdl-dialog',
            '.gjs-am-assets',
            '.gjs-am-assets-cont',
            '.gjs-mdl-container',
            '.gjs-mdl-content'
        ];

        nativeModalSelectors.forEach(selector => {
            document.querySelectorAll(selector).forEach(el => {
                el.style.display = 'none';
                el.style.visibility = 'hidden';
            });
        });

        // Método 3: Inyectar CSS global para ocultar permanentemente
        if (!document.getElementById('jaraba-hide-grapes-modal')) {
            const style = document.createElement('style');
            style.id = 'jaraba-hide-grapes-modal';
            style.innerHTML = `
                /* Ocultar modal nativo de GrapesJS Asset Manager */
                .gjs-mdl-dialog .gjs-am-assets,
                .gjs-mdl-dialog .gjs-am-assets-cont,
                .gjs-am-preview-cont,
                .gjs-am-assets-header,
                .gjs-am-add-asset,
                .gjs-mdl-dialog[data-gjs-open="true"] {
                    display: none !important;
                    visibility: hidden !important;
                    pointer-events: none !important;
                }
                /* Cerrar el diálogo completo cuando el slide-panel está abierto */
                body.slide-panel-open .gjs-mdl-container {
                    display: none !important;
                }
            `;
            document.head.appendChild(style);
        }
    }

    /**
     * Crea la estructura del slide-panel del Asset Browser.
     *
     * @returns {HTMLElement} El panel creado.
     */
    function createAssetBrowserPanel() {
        const panel = document.createElement('div');
        panel.id = 'jaraba-asset-browser';
        panel.className = 'slide-panel slide-panel--right jaraba-asset-browser';
        panel.setAttribute('role', 'dialog');
        panel.setAttribute('aria-modal', 'true');
        panel.setAttribute('aria-labelledby', 'asset-browser-title');

        // Estilos inline para garantizar visibilidad (fallback si CSS no carga)
        panel.style.cssText = `
            position: fixed;
            top: 0;
            right: 0;
            width: 420px;
            height: 100vh;
            background: #1e293b;
            z-index: 99999;
            box-shadow: -4px 0 20px rgba(0,0,0,0.3);
            display: flex;
            flex-direction: column;
            transform: translateX(100%);
            transition: transform 0.3s ease;
        `;

        // Inyectar CSS adicional para contraste y estilos del panel
        injectAssetBrowserStyles();

        panel.innerHTML = `
            <div class="slide-panel__overlay" data-close-panel style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:-1;"></div>
            <div class="slide-panel__container jaraba-asset-browser__container" style="display:flex !important;flex-direction:column !important;height:100% !important;background:#1e293b !important;color:#f1f5f9 !important;overflow:hidden !important;">
                <header class="slide-panel__header jaraba-asset-browser__header" style="display:flex !important;align-items:center !important;justify-content:space-between !important;padding:16px 20px !important;background:linear-gradient(135deg,#f97316,#ea580c) !important;color:white !important;flex-shrink:0 !important;min-height:56px !important;position:relative !important;z-index:10 !important;">
                    <h2 id="asset-browser-title" class="slide-panel__title" style="display:flex !important;align-items:center !important;gap:10px !important;margin:0 !important;font-size:16px !important;font-weight:600 !important;color:white !important;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                            <circle cx="8.5" cy="8.5" r="1.5"/>
                            <path d="m21 15-5-5L5 21"/>
                        </svg>
                        ${Drupal.t('Librería de Medios')}
                    </h2>
                    <button type="button" class="slide-panel__close jaraba-asset-browser__close-btn" data-close-panel aria-label="${Drupal.t('Cerrar')}" style="background:rgba(255,255,255,0.2) !important;border:none !important;border-radius:6px !important;padding:8px !important;cursor:pointer !important;color:white !important;display:flex !important;align-items:center !important;justify-content:center !important;min-width:36px !important;min-height:36px !important;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6L6 18M6 6l12 12"/>
                        </svg>
                    </button>
                </header>

                <div class="jaraba-asset-browser__toolbar" style="padding:16px;background:#0f172a;border-bottom:1px solid #334155;">
                    <div class="jaraba-asset-browser__search" style="position:relative;margin-bottom:12px;">
                        <svg class="jaraba-asset-browser__search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.3-4.3"/>
                        </svg>
                        <input type="text" 
                               class="jaraba-asset-browser__search-input" 
                               placeholder="${Drupal.t('Buscar imágenes...')}"
                               id="asset-search-input"
                               style="width:100%;padding:10px 10px 10px 40px;background:#1e293b;border:1px solid #475569;border-radius:8px;color:#f1f5f9;font-size:14px;">
                    </div>
                    <button type="button" class="jaraba-asset-browser__upload-btn" id="asset-upload-btn" style="width:100%;padding:10px 16px;background:linear-gradient(135deg,#f97316,#ea580c);border:none;border-radius:8px;color:white;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="17,8 12,3 7,8"/>
                            <line x1="12" y1="3" x2="12" y2="15"/>
                        </svg>
                        ${Drupal.t('Subir archivo')}
                    </button>
                </div>

                <div class="jaraba-asset-browser__filters" style="display:flex;gap:8px;padding:12px 16px;background:#0f172a;">
                    <button type="button" class="jaraba-asset-browser__filter-btn active" data-filter="all" style="padding:6px 14px;border-radius:20px;border:1px solid #475569;background:#1e293b;color:#f1f5f9;font-size:13px;cursor:pointer;">${Drupal.t('Todos')}</button>
                    <button type="button" class="jaraba-asset-browser__filter-btn" data-filter="image" style="padding:6px 14px;border-radius:20px;border:1px solid #475569;background:transparent;color:#94a3b8;font-size:13px;cursor:pointer;">${Drupal.t('Imágenes')}</button>
                    <button type="button" class="jaraba-asset-browser__filter-btn" data-filter="video" style="padding:6px 14px;border-radius:20px;border:1px solid #475569;background:transparent;color:#94a3b8;font-size:13px;cursor:pointer;">${Drupal.t('Vídeos')}</button>
                </div>

                <div class="slide-panel__body jaraba-asset-browser__body" style="flex:1;overflow-y:auto;padding:16px;">
                    <div class="jaraba-asset-browser__grid" id="asset-grid" style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;">
                        <!-- Los assets se cargarán aquí dinámicamente -->
                    </div>
                    <div class="jaraba-asset-browser__empty" id="asset-empty" style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:60px 20px;text-align:center;color:#94a3b8;">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom:16px;opacity:0.5;">
                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                            <circle cx="8.5" cy="8.5" r="1.5"/>
                            <path d="m21 15-5-5L5 21"/>
                        </svg>
                        <p style="margin:0;font-size:14px;color:#94a3b8;">${Drupal.t('No se encontraron medios.')}</p>
                        <p style="margin:8px 0 0;font-size:13px;color:#64748b;">${Drupal.t('Arrastra archivos aquí o usa el botón de subir.')}</p>
                    </div>
                    <div class="jaraba-asset-browser__loading" id="asset-loading" style="display:none;padding:40px;text-align:center;color:#94a3b8;">
                        <div style="width:32px;height:32px;border:3px solid #475569;border-top-color:#f97316;border-radius:50%;animation:spin 1s linear infinite;margin:0 auto 16px;"></div>
                        <p style="margin:0;">${Drupal.t('Cargando medios...')}</p>
                    </div>
                </div>
            </div>
        `;

        return panel;
    }

    /**
     * Inyecta estilos CSS para el Asset Browser.
     */
    function injectAssetBrowserStyles() {
        if (document.getElementById('jaraba-asset-browser-styles')) return;

        const style = document.createElement('style');
        style.id = 'jaraba-asset-browser-styles';
        style.innerHTML = `
            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
            #jaraba-asset-browser.slide-panel--open {
                transform: translateX(0) !important;
            }
            #jaraba-asset-browser input::placeholder {
                color: #64748b;
            }
            #jaraba-asset-browser input:focus {
                outline: none;
                border-color: #f97316;
            }
            #jaraba-asset-browser .jaraba-asset-browser__filter-btn.active,
            #jaraba-asset-browser .jaraba-asset-browser__filter-btn:hover {
                background: #1e293b !important;
                color: #f1f5f9 !important;
            }
            #jaraba-asset-browser .jaraba-asset-browser__upload-btn:hover {
                filter: brightness(1.1);
            }
        `;
        document.head.appendChild(style);
    }

    /**
     * Configura los eventos del panel.
     *
     * @param {HTMLElement} panel - El panel del asset browser.
     */
    function setupPanelEvents(panel) {
        // Cerrar con overlay o botón X.
        panel.querySelectorAll('[data-close-panel]').forEach(el => {
            el.addEventListener('click', closeAssetBrowser);
        });

        // Cerrar con ESC.
        const escHandler = (e) => {
            if (e.key === 'Escape' && state.isOpen) {
                closeAssetBrowser();
                document.removeEventListener('keydown', escHandler);
            }
        };
        document.addEventListener('keydown', escHandler);

        // Búsqueda con debounce (con validación).
        const searchInput = panel.querySelector('#asset-search-input');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    state.searchQuery = e.target.value;
                    state.offset = 0;
                    loadAssets();
                }, 300);
            });
        }

        // Filtros de tipo (con nuevo selector).
        panel.querySelectorAll('.jaraba-asset-browser__filter-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                panel.querySelectorAll('.jaraba-asset-browser__filter-btn').forEach(b => {
                    b.classList.remove('active');
                    b.style.background = 'transparent';
                    b.style.color = '#94a3b8';
                });
                btn.classList.add('active');
                btn.style.background = '#1e293b';
                btn.style.color = '#f1f5f9';
                state.currentType = btn.dataset.filter;
                state.offset = 0;
                loadAssets();
            });
        });

        // Botón de subir (con validación).
        const uploadBtn = panel.querySelector('#asset-upload-btn');
        if (uploadBtn) {
            uploadBtn.addEventListener('click', () => {
                // Crear input file dinámicamente si no existe
                let fileInput = document.getElementById('jaraba-asset-file-input');
                if (!fileInput) {
                    fileInput = document.createElement('input');
                    fileInput.type = 'file';
                    fileInput.id = 'jaraba-asset-file-input';
                    fileInput.accept = 'image/*,video/mp4,video/webm,.pdf';
                    fileInput.multiple = true;
                    fileInput.style.display = 'none';
                    fileInput.addEventListener('change', handleFileUpload);
                    document.body.appendChild(fileInput);
                }
                fileInput.click();
            });
        }

        // Los demás event listeners se configuran solo si existen los elementos
        const selectBtn = panel.querySelector('#asset-select-btn');
        if (selectBtn) {
            selectBtn.addEventListener('click', handleSelectAsset);
        }

        // Drag and drop (solo si existen los elementos).
        const container = panel.querySelector('.jaraba-asset-browser__container');
        if (container) {
            container.addEventListener('dragover', (e) => {
                e.preventDefault();
                container.classList.add('drag-over');
            });

            container.addEventListener('dragleave', (e) => {
                if (!container.contains(e.relatedTarget)) {
                    container.classList.remove('drag-over');
                }
            });

            container.addEventListener('drop', (e) => {
                e.preventDefault();
                container.classList.remove('drag-over');
                if (e.dataTransfer.files.length > 0) {
                    handleFileUpload({ target: { files: e.dataTransfer.files } });
                }
            });
        }
    }

    /**
     * Cierra el slide-panel del Asset Browser.
 */
    function closeAssetBrowser() {
        const panel = document.getElementById('jaraba-asset-browser');
        if (panel) {
            panel.classList.remove('slide-panel--open');
        }
        document.body.classList.remove('slide-panel-open');
        state.isOpen = false;
        state.callback = null;
        state.target = null;
    }

    /**
     * Carga assets desde la API.
     */
    async function loadAssets() {
        state.loading = true;
        state.offset = 0;

        const grid = document.getElementById('asset-grid');
        const loading = document.getElementById('asset-loading');
        const empty = document.getElementById('asset-empty');

        grid.innerHTML = '';
        loading.style.display = 'flex';
        empty.style.display = 'none';

        try {
            const params = new URLSearchParams({
                type: state.currentType,
                search: state.searchQuery,
                limit: CONFIG.defaultLimit,
                offset: state.offset,
            });

            const response = await fetch(`${CONFIG.apiEndpoint}?${params} `);
            if (!response.ok) throw new Error('Error al cargar assets');

            const data = await response.json();
            state.items = data.items || [];
            state.total = data.total || 0;

            renderAssets();
        } catch (error) {
            console.error('Error cargando assets:', error);
            grid.innerHTML = `< p class="jaraba-asset-browser__error" > ${Drupal.t('Error al cargar los medios')}</p > `;
        } finally {
            state.loading = false;
            loading.style.display = 'none';
        }
    }

    /**
     * Carga más assets (scroll infinito).
     */
    async function loadMoreAssets() {
        if (state.loading || state.items.length >= state.total) return;

        state.loading = true;
        state.offset += CONFIG.defaultLimit;

        try {
            const params = new URLSearchParams({
                type: state.currentType,
                search: state.searchQuery,
                limit: CONFIG.defaultLimit,
                offset: state.offset,
            });

            const response = await fetch(`${CONFIG.apiEndpoint}?${params} `);
            if (!response.ok) throw new Error('Error al cargar más assets');

            const data = await response.json();
            state.items = [...state.items, ...(data.items || [])];

            renderAssets();
        } catch (error) {
            console.error('Error cargando más assets:', error);
        } finally {
            state.loading = false;
        }
    }

    /**
     * Renderiza los assets en el grid.
     */
    function renderAssets() {
        const grid = document.getElementById('asset-grid');
        const empty = document.getElementById('asset-empty');

        if (state.items.length === 0) {
            empty.style.display = 'flex';
            grid.innerHTML = '';
            return;
        }

        empty.style.display = 'none';

        grid.innerHTML = state.items.map(asset => `
            < div class="jaraba-asset-browser__item ${state.selectedItems.includes(asset.id) ? 'is-selected' : ''}"
        data - asset - id="${asset.id}"
        data - asset - url="${asset.url}"
        data - asset - name="${asset.name}"
        tabindex = "0" >
                <div class="jaraba-asset-browser__item-preview">
                    <img src="${asset.thumbnail || CONFIG.thumbnailFallback}" 
                         alt="${asset.name}"
                         loading="lazy">
                    ${asset.type === 'video' ? '<span class="jaraba-asset-browser__item-badge">Video</span>' : ''}
                </div>
                <div class="jaraba-asset-browser__item-info">
                    <span class="jaraba-asset-browser__item-name" title="${asset.name}">${asset.name}</span>
                    ${asset.width && asset.height ? `<span class="jaraba-asset-browser__item-size">${asset.width}×${asset.height}</span>` : ''}
                </div>
                <div class="jaraba-asset-browser__item-check">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                        <polyline points="20,6 9,17 4,12"/>
                    </svg>
                </div>
            </div >
            `).join('');

        // Eventos de selección.
        grid.querySelectorAll('.jaraba-asset-browser__item').forEach(item => {
            item.addEventListener('click', () => toggleAssetSelection(item));
            item.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    toggleAssetSelection(item);
                }
            });
        });

        updateSelectButton();
    }

    /**
     * Toggle la selección de un asset.
     *
     * @param {HTMLElement} item - Elemento del asset.
     */
    function toggleAssetSelection(item) {
        const assetId = parseInt(item.dataset.assetId, 10);

        if (state.selectedItems.includes(assetId)) {
            state.selectedItems = state.selectedItems.filter(id => id !== assetId);
            item.classList.remove('is-selected');
        } else {
            // Para imágenes en GrapesJS solo permitimos una selección.
            state.selectedItems = [assetId];
            document.querySelectorAll('.jaraba-asset-browser__item').forEach(el => el.classList.remove('is-selected'));
            item.classList.add('is-selected');
        }

        updateSelectButton();
    }

    /**
     * Actualiza el botón de seleccionar.
     */
    function updateSelectButton() {
        const btn = document.getElementById('asset-select-btn');
        if (btn) {
            btn.disabled = state.selectedItems.length === 0;
            if (state.selectedItems.length > 0) {
                btn.textContent = Drupal.t('Seleccionar (@count)', { '@count': state.selectedItems.length });
            } else {
                btn.textContent = Drupal.t('Seleccionar');
            }
        }
    }

    /**
     * Maneja la selección del asset.
     */
    function handleSelectAsset() {
        if (state.selectedItems.length === 0) return;

        // Obtener el asset seleccionado.
        const selectedAsset = state.items.find(a => a.id === state.selectedItems[0]);
        if (!selectedAsset) return;

        // Llamar al callback de GrapesJS con el asset.
        if (state.callback && typeof state.callback === 'function') {
            state.callback({
                src: selectedAsset.url,
                name: selectedAsset.name,
                width: selectedAsset.width,
                height: selectedAsset.height,
            });
        }

        // También podemos añadir el asset al Asset Manager de GrapesJS.
        if (window.jarabaCanvasEditor && window.jarabaCanvasEditor.editor) {
            window.jarabaCanvasEditor.editor.AssetManager.add({
                src: selectedAsset.url,
                name: selectedAsset.name,
                width: selectedAsset.width,
                height: selectedAsset.height,
            });
        }

        closeAssetBrowser();
    }

    /**
     * Maneja el upload de archivos via input.
     *
     * @param {Event} e - Evento change del input file.
     */
    function handleFileUpload(e) {
        if (e.target.files.length) {
            uploadFiles(e.target.files);
        }
    }

    /**
     * Sube archivos a la API.
     *
     * @param {FileList} files - Lista de archivos a subir.
     */
    async function uploadFiles(files) {
        const loading = document.getElementById('asset-loading');
        loading.style.display = 'flex';
        loading.querySelector('span').textContent = Drupal.t('Subiendo...');

        let successCount = 0;

        for (const file of files) {
            try {
                const formData = new FormData();
                formData.append('file', file);

                const response = await fetch(CONFIG.apiEndpoint, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-Token': drupalSettings.jarabaCanvas?.csrfToken || '',
                    },
                });

                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(error.error || 'Error al subir');
                }

                const data = await response.json();
                if (data.success && data.asset) {
                    state.items.unshift(data.asset);
                    successCount++;
                }
            } catch (error) {
                console.error(`Error subiendo ${file.name}: `, error);
                Drupal.announce(Drupal.t('Error al subir @file', { '@file': file.name }));
            }
        }

        loading.style.display = 'none';
        loading.querySelector('span').textContent = Drupal.t('Cargando...');

        if (successCount > 0) {
            renderAssets();
            Drupal.announce(Drupal.t('@count archivo(s) subido(s)', { '@count': successCount }));
        }

        // Limpiar input file.
        document.getElementById('asset-file-input').value = '';
    }

    /**
     * Drupal behavior para inicializar el plugin de Assets.
     */
    Drupal.behaviors.jarabaAssetsBrowser = {
        attach: function (context) {
            // Solo inicializar si hay un editor GrapesJS.
            if (window.jarabaCanvasEditor && window.jarabaCanvasEditor.editor) {
                const editor = window.jarabaCanvasEditor.editor;

                // Prevenir inicialización múltiple.
                if (!editor._jarabaAssetsInitialized) {
                    initAssetsPlugin(editor);
                    editor._jarabaAssetsInitialized = true;
                }
            }
        },
    };

})(Drupal, drupalSettings, once);

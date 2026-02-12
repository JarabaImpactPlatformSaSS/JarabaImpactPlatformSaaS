/**
 * @file
 * Plugin Command Palette para GrapesJS - Jaraba Page Builder.
 *
 * Implementa un Command Palette tipo VS Code (Ctrl+K) para acceso rápido a:
 * - Bloques
 * - Comandos del editor
 * - Acciones rápidas
 *
 * @requires grapesjs
 * @see docs/arquitectura/2026-02-06_plan_elevacion_page_builder_clase_mundial.md
 */

(function (Drupal, drupalSettings) {
    'use strict';

    /**
     * Plugin Command Palette para GrapesJS.
     */
    const commandPalettePlugin = (editor, opts = {}) => {
        const defaults = {
            shortcut: 'ctrl+k',
            placeholder: Drupal.t('Buscar bloques, comandos...'),
        };
        const options = { ...defaults, ...opts };

        // Crear contenedor del Command Palette
        const createPaletteHTML = () => {
            return `
                <div class="jaraba-cmd-palette" style="display: none;">
                    <div class="jaraba-cmd-palette__overlay"></div>
                    <div class="jaraba-cmd-palette__container">
                        <div class="jaraba-cmd-palette__header">
                            <input type="text" 
                                   class="jaraba-cmd-palette__input" 
                                   placeholder="${options.placeholder}"
                                   autocomplete="off" />
                        </div>
                        <div class="jaraba-cmd-palette__results"></div>
                        <div class="jaraba-cmd-palette__footer">
                            <span>↑↓ ${Drupal.t('Navegar')}</span>
                            <span>↵ ${Drupal.t('Seleccionar')}</span>
                            <span>Esc ${Drupal.t('Cerrar')}</span>
                        </div>
                    </div>
                </div>
            `;
        };

        // Estilos del Command Palette
        const injectStyles = () => {
            const styleId = 'jaraba-cmd-palette-styles';
            if (document.getElementById(styleId)) return;

            const styles = document.createElement('style');
            styles.id = styleId;
            styles.textContent = `
                .jaraba-cmd-palette {
                    position: fixed;
                    inset: 0;
                    z-index: 10000;
                    display: flex;
                    align-items: flex-start;
                    justify-content: center;
                    padding-top: 15vh;
                }
                .jaraba-cmd-palette__overlay {
                    position: absolute;
                    inset: 0;
                    background: rgba(15, 23, 42, 0.6);
                    backdrop-filter: blur(4px);
                }
                .jaraba-cmd-palette__container {
                    position: relative;
                    width: 100%;
                    max-width: 560px;
                    background: white;
                    border-radius: 16px;
                    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
                    overflow: hidden;
                    animation: cmdPaletteIn 0.15s ease-out;
                }
                @keyframes cmdPaletteIn {
                    from {
                        opacity: 0;
                        transform: scale(0.95) translateY(-10px);
                    }
                    to {
                        opacity: 1;
                        transform: scale(1) translateY(0);
                    }
                }
                .jaraba-cmd-palette__header {
                    padding: 1rem;
                    border-bottom: 1px solid #e2e8f0;
                }
                .jaraba-cmd-palette__input {
                    width: 100%;
                    padding: 0.75rem 1rem;
                    font-size: 1.125rem;
                    border: none;
                    outline: none;
                    background: #f8fafc;
                    border-radius: 8px;
                    font-family: var(--ej-font-family, 'Inter', sans-serif);
                }
                .jaraba-cmd-palette__input::placeholder {
                    color: #94a3b8;
                }
                .jaraba-cmd-palette__results {
                    max-height: 400px;
                    overflow-y: auto;
                    padding: 0.5rem;
                }
                .jaraba-cmd-palette__item {
                    display: flex;
                    align-items: center;
                    gap: 0.75rem;
                    padding: 0.75rem 1rem;
                    border-radius: 8px;
                    cursor: pointer;
                    transition: background 0.1s;
                }
                .jaraba-cmd-palette__item:hover,
                .jaraba-cmd-palette__item--selected {
                    background: #f1f5f9;
                }
                .jaraba-cmd-palette__item--selected {
                    background: linear-gradient(135deg, #233D63 0%, #00A9A5 100%);
                    color: white;
                }
                .jaraba-cmd-palette__item-icon {
                    width: 36px;
                    height: 36px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background: #e2e8f0;
                    border-radius: 8px;
                    font-size: 1.25rem;
                }
                .jaraba-cmd-palette__item--selected .jaraba-cmd-palette__item-icon {
                    background: rgba(255, 255, 255, 0.2);
                }
                .jaraba-cmd-palette__item-content {
                    flex: 1;
                }
                .jaraba-cmd-palette__item-title {
                    font-weight: 600;
                    color: #1e293b;
                }
                .jaraba-cmd-palette__item--selected .jaraba-cmd-palette__item-title {
                    color: white;
                }
                .jaraba-cmd-palette__item-category {
                    font-size: 0.75rem;
                    color: #64748b;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
                .jaraba-cmd-palette__item--selected .jaraba-cmd-palette__item-category {
                    color: rgba(255, 255, 255, 0.8);
                }
                .jaraba-cmd-palette__footer {
                    display: flex;
                    gap: 1.5rem;
                    padding: 0.75rem 1rem;
                    background: #f8fafc;
                    border-top: 1px solid #e2e8f0;
                    font-size: 0.75rem;
                    color: #64748b;
                }
                .jaraba-cmd-palette__empty {
                    padding: 2rem;
                    text-align: center;
                    color: #64748b;
                }
                .jaraba-cmd-palette__category-header {
                    padding: 0.5rem 1rem;
                    font-size: 0.7rem;
                    font-weight: 700;
                    color: #64748b;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                }
            `;
            document.head.appendChild(styles);
        };

        // Obtener bloques disponibles
        const getBlocks = () => {
            const blocks = [];
            const blockManager = editor.BlockManager;

            blockManager.getAll().forEach((block) => {
                // category puede ser un Object (GrapesJS Category model)
                const rawCategory = block.get('category');
                const categoryStr = typeof rawCategory === 'string' ? rawCategory :
                    (rawCategory?.id || rawCategory?.get?.('id') || 'basic');
                blocks.push({
                    type: 'block',
                    id: block.get('id'),
                    label: String(block.get('label') || block.get('id') || ''),
                    category: categoryStr,
                    icon: '📦',
                    action: () => {
                        // Insertar bloque en el canvas
                        const component = editor.addComponents(block.get('content'));
                        if (component && component.length) {
                            editor.select(component[0]);
                        }
                    },
                });
            });

            return blocks;
        };

        // Obtener comandos disponibles
        const getCommands = () => {
            return [
                { type: 'command', id: 'undo', label: Drupal.t('Deshacer'), icon: '↩️', category: 'acciones', action: () => editor.runCommand('core:undo') },
                { type: 'command', id: 'redo', label: Drupal.t('Rehacer'), icon: '↪️', category: 'acciones', action: () => editor.runCommand('core:redo') },
                { type: 'command', id: 'clear', label: Drupal.t('Limpiar Canvas'), icon: '🗑️', category: 'acciones', action: () => editor.runCommand('core:canvas-clear') },
                { type: 'command', id: 'preview', label: Drupal.t('Vista Previa'), icon: '👁️', category: 'vista', action: () => editor.runCommand('core:preview') },
                { type: 'command', id: 'fullscreen', label: Drupal.t('Pantalla Completa'), icon: '⛶', category: 'vista', action: () => editor.runCommand('core:fullscreen') },
                { type: 'command', id: 'save', label: Drupal.t('Guardar'), icon: '💾', category: 'acciones', action: () => editor.runCommand('jaraba:save') },
                { type: 'command', id: 'seo', label: Drupal.t('Panel SEO'), icon: '📈', category: 'paneles', action: () => editor.runCommand('toggle-seo-panel') },
                { type: 'command', id: 'export', label: Drupal.t('Exportar HTML'), icon: '📄', category: 'acciones', action: () => editor.runCommand('export-template') },
            ];
        };

        // Búsqueda fuzzy simple
        const fuzzyMatch = (query, text) => {
            // Proteger contra valores no-string (ej: category puede ser un Object)
            const queryStr = String(query || '');
            const textStr = String(text || '');
            const queryLower = queryStr.toLowerCase();
            const textLower = textStr.toLowerCase();

            if (textLower.includes(queryLower)) return true;

            let queryIndex = 0;
            for (let i = 0; i < textLower.length && queryIndex < queryLower.length; i++) {
                if (textLower[i] === queryLower[queryIndex]) {
                    queryIndex++;
                }
            }
            return queryIndex === queryLower.length;
        };

        // Filtrar resultados
        const filterResults = (query) => {
            const blocks = getBlocks();
            const commands = getCommands();
            const allItems = [...commands, ...blocks];

            if (!query.trim()) {
                // Mostrar comandos primero, luego bloques recientes
                return allItems.slice(0, 12);
            }

            return allItems.filter(item =>
                fuzzyMatch(query, item.label) ||
                fuzzyMatch(query, item.category)
            ).slice(0, 15);
        };

        // Renderizar resultados
        const renderResults = (items, container, selectedIndex = 0) => {
            if (!items.length) {
                container.innerHTML = `<div class="jaraba-cmd-palette__empty">${Drupal.t('No se encontraron resultados')}</div>`;
                return;
            }

            // Agrupar por tipo
            const grouped = {
                command: items.filter(i => i.type === 'command'),
                block: items.filter(i => i.type === 'block'),
            };

            let html = '';
            let globalIndex = 0;

            if (grouped.command.length) {
                html += `<div class="jaraba-cmd-palette__category-header">${Drupal.t('Comandos')}</div>`;
                grouped.command.forEach((item) => {
                    const isSelected = globalIndex === selectedIndex;
                    html += `
                        <div class="jaraba-cmd-palette__item ${isSelected ? 'jaraba-cmd-palette__item--selected' : ''}" 
                             data-index="${globalIndex}" 
                             data-id="${item.id}" 
                             data-type="${item.type}">
                            <div class="jaraba-cmd-palette__item-icon">${item.icon}</div>
                            <div class="jaraba-cmd-palette__item-content">
                                <div class="jaraba-cmd-palette__item-title">${item.label}</div>
                                <div class="jaraba-cmd-palette__item-category">${item.category}</div>
                            </div>
                        </div>
                    `;
                    globalIndex++;
                });
            }

            if (grouped.block.length) {
                html += `<div class="jaraba-cmd-palette__category-header">${Drupal.t('Bloques')}</div>`;
                grouped.block.forEach((item) => {
                    const isSelected = globalIndex === selectedIndex;
                    html += `
                        <div class="jaraba-cmd-palette__item ${isSelected ? 'jaraba-cmd-palette__item--selected' : ''}" 
                             data-index="${globalIndex}" 
                             data-id="${item.id}" 
                             data-type="${item.type}">
                            <div class="jaraba-cmd-palette__item-icon">${item.icon}</div>
                            <div class="jaraba-cmd-palette__item-content">
                                <div class="jaraba-cmd-palette__item-title">${item.label}</div>
                                <div class="jaraba-cmd-palette__item-category">${item.category}</div>
                            </div>
                        </div>
                    `;
                    globalIndex++;
                });
            }

            container.innerHTML = html;
        };

        // Estado del palette
        let paletteEl = null;
        let inputEl = null;
        let resultsEl = null;
        let currentItems = [];
        let selectedIndex = 0;

        // Abrir Command Palette
        const open = () => {
            if (!paletteEl) {
                document.body.insertAdjacentHTML('beforeend', createPaletteHTML());
                paletteEl = document.querySelector('.jaraba-cmd-palette');
                inputEl = paletteEl.querySelector('.jaraba-cmd-palette__input');
                resultsEl = paletteEl.querySelector('.jaraba-cmd-palette__results');

                // Eventos
                paletteEl.querySelector('.jaraba-cmd-palette__overlay').addEventListener('click', close);

                inputEl.addEventListener('input', (e) => {
                    currentItems = filterResults(e.target.value);
                    selectedIndex = 0;
                    renderResults(currentItems, resultsEl, selectedIndex);
                });

                inputEl.addEventListener('keydown', handleKeydown);

                resultsEl.addEventListener('click', (e) => {
                    const item = e.target.closest('.jaraba-cmd-palette__item');
                    if (item) {
                        executeItem(parseInt(item.dataset.index, 10));
                    }
                });
            }

            paletteEl.style.display = 'flex';
            inputEl.value = '';
            selectedIndex = 0;
            currentItems = filterResults('');
            renderResults(currentItems, resultsEl, selectedIndex);

            setTimeout(() => inputEl.focus(), 50);
        };

        // Cerrar Command Palette
        const close = () => {
            if (paletteEl) {
                paletteEl.style.display = 'none';
            }
        };

        // Ejecutar item seleccionado
        const executeItem = (index) => {
            const item = currentItems[index];
            if (item && item.action) {
                close();
                item.action();
            }
        };

        // Manejar teclado
        const handleKeydown = (e) => {
            switch (e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    selectedIndex = Math.min(selectedIndex + 1, currentItems.length - 1);
                    renderResults(currentItems, resultsEl, selectedIndex);
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    selectedIndex = Math.max(selectedIndex - 1, 0);
                    renderResults(currentItems, resultsEl, selectedIndex);
                    break;
                case 'Enter':
                    e.preventDefault();
                    executeItem(selectedIndex);
                    break;
                case 'Escape':
                    e.preventDefault();
                    close();
                    break;
            }
        };

        // Registrar comando en GrapesJS
        editor.Commands.add('open-command-palette', {
            run: () => open(),
            stop: () => close(),
        });

        // Inyectar estilos
        injectStyles();

        // Registrar atajo de teclado global
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                if (paletteEl && paletteEl.style.display === 'flex') {
                    close();
                } else {
                    open();
                }
            }
        });

        console.log('Jaraba Command Palette Plugin inicializado.');
    };

    // Registrar plugin en GrapesJS
    if (typeof grapesjs !== 'undefined') {
        grapesjs.plugins.add('jaraba-command-palette', commandPalettePlugin);
    }

})(Drupal, drupalSettings);

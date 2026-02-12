/**
 * @file
 * JavaScript para el gestor del árbol de páginas.
 *
 * Funcionalidades:
 * - Drag & Drop con SortableJS
 * - Expand/Collapse de nodos
 * - API calls para reordenar
 * - Slide-panel para edición
 *
 * Cumple con directrices:
 * - Drupal.t() para textos
 * - Drupal.behaviors para inicialización
 */

(function (Drupal, drupalSettings, once) {
    'use strict';

    /**
     * Behavior para el gestor del árbol de páginas.
     */
    Drupal.behaviors.siteTreeManager = {
        attach: function (context, settings) {
            const managers = once('site-tree-manager', '[data-tree-manager]', context);

            managers.forEach(function (manager) {
                new SiteTreeManager(manager, settings.jaraba_site_builder || {});
            });
        }
    };

    /**
     * Clase para gestionar el árbol de páginas.
     */
    class SiteTreeManager {
        constructor(element, config) {
            this.element = element;
            this.config = config;
            this.apiBase = config.apiBase || '/api/v1/site';
            this.canEdit = config.canEdit || false;
            this.labels = config.labels || {};
            this.statusBar = element.querySelector('[data-status-bar]');
            this.sortables = [];

            this.init();
        }

        init() {
            this.bindEvents();

            if (this.canEdit && typeof Sortable !== 'undefined') {
                this.initSortable();
            }
        }

        bindEvents() {
            // Expandir/contraer nodos
            this.element.addEventListener('click', (e) => {
                const toggleBtn = e.target.closest('[data-action="toggle"]');
                if (toggleBtn) {
                    this.toggleNode(toggleBtn);
                    return;
                }

                // Botones de acción
                const actionBtn = e.target.closest('[data-action]');
                if (actionBtn) {
                    const action = actionBtn.dataset.action;
                    const nodeId = actionBtn.dataset.nodeId;

                    switch (action) {
                        case 'edit':
                            this.editNode(nodeId);
                            break;
                        case 'add-child':
                            this.addChild(nodeId);
                            break;
                        case 'add-root':
                            this.addRootPage();
                            break;
                        case 'remove':
                            this.removeNode(nodeId);
                            break;
                        case 'expand-all':
                            this.expandAll();
                            break;
                        case 'collapse-all':
                            this.collapseAll();
                            break;
                        case 'bulk-publish':
                            this.bulkUpdateStatus('published');
                            break;
                        case 'bulk-draft':
                            this.bulkUpdateStatus('draft');
                            break;
                        case 'bulk-archive':
                            this.bulkUpdateStatus('archived');
                            break;
                        case 'select-node':
                            this.toggleNodeSelection(actionBtn);
                            break;
                    }
                }
            });

            // Inicializar selección bulk.
            this.selectedNodes = new Set();
        }

        /**
         * Alterna la selección de un nodo para operaciones bulk.
         */
        toggleNodeSelection(button) {
            const nodeId = button.dataset.nodeId;
            const item = button.closest('.site-tree__item');

            if (this.selectedNodes.has(nodeId)) {
                this.selectedNodes.delete(nodeId);
                item.classList.remove('is-selected');
                button.setAttribute('aria-pressed', 'false');
            } else {
                this.selectedNodes.add(nodeId);
                item.classList.add('is-selected');
                button.setAttribute('aria-pressed', 'true');
            }

            this.updateBulkActionsVisibility();
        }

        /**
         * Muestra/oculta los botones de acciones bulk.
         */
        updateBulkActionsVisibility() {
            const bulkBar = this.element.querySelector('[data-bulk-actions]');
            const countEl = this.element.querySelector('[data-bulk-count]');

            if (bulkBar) {
                bulkBar.hidden = this.selectedNodes.size === 0;
            }
            if (countEl) {
                countEl.textContent = this.selectedNodes.size;
            }
        }

        /**
         * Actualiza el estado de los nodos seleccionados en bloque.
         */
        async bulkUpdateStatus(newStatus) {
            if (this.selectedNodes.size === 0) {
                return;
            }

            const statusLabels = {
                'published': Drupal.t('publicar'),
                'draft': Drupal.t('pasar a borrador'),
                'archived': Drupal.t('archivar'),
            };

            const confirmMsg = Drupal.t('¿@action @count páginas seleccionadas?', {
                '@action': statusLabels[newStatus] || newStatus,
                '@count': this.selectedNodes.size,
            });

            if (!confirm(confirmMsg)) {
                return;
            }

            this.setStatus('saving');

            try {
                const response = await this.apiCall('POST', '/pages/bulk-status', {
                    node_ids: Array.from(this.selectedNodes).map(Number),
                    status: newStatus,
                });

                if (response.success) {
                    this.setStatus('saved');
                    this.selectedNodes.clear();
                    window.location.reload();
                } else {
                    this.setStatus('error');
                    alert(response.error || Drupal.t('Error al actualizar'));
                }
            } catch (error) {
                this.setStatus('error');
                console.error('Bulk update error:', error);
            }
        }

        /**
         * Inicializa SortableJS para drag & drop.
         */
        initSortable() {
            const lists = this.element.querySelectorAll('[data-sortable]');

            lists.forEach((list) => {
                const sortable = new Sortable(list, {
                    group: 'site-tree',
                    animation: 150,
                    handle: '.site-tree__handle',
                    ghostClass: 'sortable-ghost',
                    dragClass: 'sortable-drag',
                    fallbackOnBody: true,
                    swapThreshold: 0.65,

                    onEnd: (evt) => {
                        this.onDragEnd(evt);
                    }
                });

                this.sortables.push(sortable);
            });
        }

        /**
         * Callback cuando termina el drag.
         */
        async onDragEnd(evt) {
            // Construir nuevo árbol desde el DOM
            const tree = this.buildTreeFromDOM();

            this.setStatus('saving');

            try {
                const response = await this.apiCall('POST', '/tree/reorder', { nodes: tree });

                if (response.success) {
                    this.setStatus('saved');
                } else {
                    this.setStatus('error');
                    console.error('Error reordering:', response.error);
                }
            } catch (error) {
                this.setStatus('error');
                console.error('Error reordering:', error);
            }
        }

        /**
         * Construye el árbol desde el DOM actual.
         */
        buildTreeFromDOM() {
            const rootList = this.element.querySelector('.site-tree[data-sortable]');
            if (!rootList) return [];

            return this.buildNodeList(rootList);
        }

        buildNodeList(ul) {
            const nodes = [];

            ul.querySelectorAll(':scope > .site-tree__item').forEach((li) => {
                const node = {
                    id: parseInt(li.dataset.nodeId),
                    children: []
                };

                const childList = li.querySelector(':scope > .site-tree__children');
                if (childList) {
                    node.children = this.buildNodeList(childList);
                }

                nodes.push(node);
            });

            return nodes;
        }

        /**
         * Expand/collapse un nodo.
         */
        toggleNode(button) {
            const item = button.closest('.site-tree__item');
            const isExpanded = button.getAttribute('aria-expanded') === 'true';

            button.setAttribute('aria-expanded', !isExpanded);
            item.dataset.collapsed = isExpanded ? 'true' : 'false';
        }

        expandAll() {
            this.element.querySelectorAll('[data-action="toggle"]').forEach((btn) => {
                btn.setAttribute('aria-expanded', 'true');
                btn.closest('.site-tree__item').dataset.collapsed = 'false';
            });
        }

        collapseAll() {
            this.element.querySelectorAll('[data-action="toggle"]').forEach((btn) => {
                btn.setAttribute('aria-expanded', 'false');
                btn.closest('.site-tree__item').dataset.collapsed = 'true';
            });
        }

        /**
         * Abre el panel de edición para un nodo.
         *
         * Delegado al sistema global de slide-panel via data-slide-panel-url.
         * Este método se mantiene como fallback para programmatic access.
         */
        editNode(nodeId) {
            const editUrl = `/admin/structure/site-builder/tree/${nodeId}/edit-ajax`;
            const title = Drupal.t('Editar nodo');

            // Usar el sistema global de slide-panel si está disponible.
            if (Drupal.behaviors.slidePanel && Drupal.behaviors.slidePanel.open) {
                Drupal.behaviors.slidePanel.open(editUrl, title);
            } else {
                window.location.href = `/admin/structure/site-builder/tree/${nodeId}/edit`;
            }
        }

        /**
         * Añade un hijo a un nodo.
         */
        async addChild(parentNodeId) {
            await this.openPageSelector(parentNodeId);
        }

        /**
         * Añade una página raíz.
         */
        async addRootPage() {
            await this.openPageSelector(null);
        }

        /**
         * Abre el selector de páginas disponibles.
         * @param {number|null} parentNodeId - ID del nodo padre (null para raíz)
         */
        async openPageSelector(parentNodeId) {
            const panel = document.getElementById('page-edit-panel');
            if (!panel) return;

            const content = panel.querySelector('[data-panel-content]');
            const title = panel.querySelector('.slide-panel__title');

            if (title) {
                title.textContent = parentNodeId
                    ? Drupal.t('Añadir subpágina')
                    : Drupal.t('Añadir página al árbol');
            }

            content.innerHTML = `<div class="loading-spinner">${Drupal.t('Cargando páginas disponibles...')}</div>`;

            panel.hidden = false;
            document.body.classList.add('slide-panel-open');
            panel.dataset.parentNodeId = parentNodeId || '';

            try {
                // Cargar páginas disponibles del Page Builder
                const response = await this.apiCall('GET', '/pages/available');

                if (response.success && response.data && response.data.length > 0) {
                    content.innerHTML = this.renderPageSelector(response.data, parentNodeId);
                    this.bindPageSelectorEvents(content, parentNodeId);
                } else if (response.success) {
                    content.innerHTML = `
                        <div class="empty-state">
                            <p>${Drupal.t('No hay páginas disponibles para añadir.')}</p>
                            <p>${Drupal.t('Crea páginas en el Page Builder primero.')}</p>
                        </div>
                    `;
                } else {
                    content.innerHTML = `<div class="error-message">${response.error || Drupal.t('Error al cargar páginas')}</div>`;
                }
            } catch (error) {
                console.error('Error loading pages:', error);
                content.innerHTML = `<div class="error-message">${Drupal.t('Error de conexión')}</div>`;
            }
        }

        /**
         * Renderiza el selector de páginas.
         */
        renderPageSelector(pages, parentNodeId) {
            let html = '<div class="page-selector">';
            html += `<div class="page-selector__header">
                <input type="search" class="page-selector__search" placeholder="${Drupal.t('Buscar página...')}" />
            </div>`;
            html += '<ul class="page-selector__list">';

            pages.forEach(page => {
                const statusClass = page.status === 'published' ? 'status--published' : 'status--draft';
                const statusLabel = page.status === 'published' ? Drupal.t('Publicado') : Drupal.t('Borrador');

                html += `
                    <li class="page-selector__item" data-page-id="${page.id}">
                        <button type="button" class="page-selector__button" data-action="select-page" data-page-id="${page.id}">
                            <span class="page-selector__title">${this.escapeHtml(page.title)}</span>
                            <span class="page-selector__meta">
                                <span class="page-selector__type">${this.escapeHtml(page.type || 'Página')}</span>
                                <span class="page-selector__status ${statusClass}">${statusLabel}</span>
                            </span>
                        </button>
                    </li>
                `;
            });

            html += '</ul></div>';
            return html;
        }

        /**
         * Vincula eventos del selector de páginas.
         */
        bindPageSelectorEvents(container, parentNodeId) {
            // Búsqueda
            const searchInput = container.querySelector('.page-selector__search');
            if (searchInput) {
                searchInput.addEventListener('input', (e) => {
                    const query = e.target.value.toLowerCase();
                    container.querySelectorAll('.page-selector__item').forEach(item => {
                        const title = item.querySelector('.page-selector__title').textContent.toLowerCase();
                        item.hidden = !title.includes(query);
                    });
                });
            }

            // Selección de página
            container.addEventListener('click', async (e) => {
                const selectBtn = e.target.closest('[data-action="select-page"]');
                if (selectBtn) {
                    const pageId = parseInt(selectBtn.dataset.pageId);
                    await this.addPageToTree(pageId, parentNodeId);
                }
            });
        }

        /**
         * Añade una página al árbol.
         */
        async addPageToTree(pageId, parentNodeId) {
            this.setStatus('saving');
            this.closePanel();

            try {
                const data = {
                    page_id: pageId,
                    parent_id: parentNodeId || null
                };

                const response = await this.apiCall('POST', '/pages', data);

                if (response.success) {
                    this.setStatus('saved');
                    // Recargar la página para ver el nuevo nodo
                    window.location.reload();
                } else {
                    this.setStatus('error');
                    alert(response.error || Drupal.t('Error al añadir página'));
                }
            } catch (error) {
                this.setStatus('error');
                console.error('Error adding page:', error);
            }
        }

        /**
         * Cierra el panel.
         */
        closePanel() {
            const panel = document.getElementById('page-edit-panel');
            if (panel) {
                panel.hidden = true;
                document.body.classList.remove('slide-panel-open');
            }
        }

        /**
         * Escapa HTML para prevenir XSS.
         */
        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        /**
         * Elimina un nodo del árbol.
         */
        async removeNode(nodeId) {
            const confirmMessage = this.labels.confirmRemove || Drupal.t('¿Eliminar esta página del árbol?');

            if (!confirm(confirmMessage)) {
                return;
            }

            this.setStatus('saving');

            try {
                const response = await this.apiCall('DELETE', `/pages/${nodeId}`);

                if (response.success) {
                    // Eliminar del DOM
                    const item = this.element.querySelector(`[data-node-id="${nodeId}"]`);
                    if (item) {
                        item.remove();
                    }
                    this.setStatus('saved');
                } else {
                    this.setStatus('error');
                    alert(response.error || Drupal.t('Error al eliminar'));
                }
            } catch (error) {
                this.setStatus('error');
                console.error('Error removing:', error);
            }
        }

        /**
         * Actualiza el indicador de estado.
         */
        setStatus(status) {
            if (!this.statusBar) return;

            this.statusBar.querySelectorAll('[data-status]').forEach((el) => {
                el.hidden = el.dataset.status !== status;
            });

            // Auto-ocultar mensaje de error después de 5s
            if (status === 'error') {
                setTimeout(() => this.setStatus('saved'), 5000);
            }
        }

        /**
         * Realiza una llamada a la API.
         */
        async apiCall(method, endpoint, data = null) {
            const url = this.apiBase + endpoint;
            const options = {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                credentials: 'same-origin'
            };

            if (data && method !== 'GET') {
                options.body = JSON.stringify(data);
            }

            const response = await fetch(url, options);
            return response.json();
        }
    }

    // Exponer globalmente para debugging
    window.SiteTreeManager = SiteTreeManager;

})(Drupal, drupalSettings, once);

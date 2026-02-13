/**
 * @file
 * Canvas Editor - Editor visual de secciones Multi-Block.
 *
 * PROPÓSITO:
 * Interfaz visual side-by-side para gestionar secciones
 * de páginas multi-block con preview en tiempo real.
 *
 * CARACTERÍSTICAS:
 * - Layout de 2 columnas: Lista de bloques + Canvas preview
 * - Drag-and-drop con SortableJS
 * - Preview en iframe con viewport toggle
 * - Actualización automática del preview tras cambios
 *
 * DEPENDENCIAS:
 * - SortableJS (CDN)
 * - Alpine.js
 *
 * @package Drupal\jaraba_page_builder
 */

(function (Drupal, once) {
    'use strict';

    /**
     * API Service para operaciones CRUD de secciones.
     */
    const SectionApi = {
        baseUrl: '/api/v1/pages',

        /**
         * Lista las secciones de una página.
         */
        async list(pageId) {
            const response = await fetch(`${this.baseUrl}/${pageId}/sections`, {
                method: 'GET',
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }
            });
            return response.json();
        },

        /**
         * Añade una nueva sección.
         */
        async add(pageId, templateId, content = {}, weight = null) {
            const response = await fetch(`${this.baseUrl}/${pageId}/sections`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ template_id: templateId, content, weight })
            });
            return response.json();
        },

        /**
         * Actualiza una sección existente.
         */
        async update(pageId, uuid, data) {
            const response = await fetch(`${this.baseUrl}/${pageId}/sections/${uuid}`, {
                method: 'PUT',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            });
            return response.json();
        },

        /**
         * Elimina una sección.
         */
        async delete(pageId, uuid) {
            const response = await fetch(`${this.baseUrl}/${pageId}/sections/${uuid}`, {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }
            });
            return response.json();
        },

        /**
         * Reordena las secciones.
         */
        async reorder(pageId, order) {
            const response = await fetch(`${this.baseUrl}/${pageId}/sections/reorder`, {
                method: 'PUT',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ order })
            });
            return response.json();
        },

        /**
         * Obtiene templates disponibles.
         */
        async getTemplates() {
            const response = await fetch('/api/v1/page-builder/section-templates', {
                method: 'GET',
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }
            });
            return response.json();
        }
    };

    /**
     * Componente Alpine.js para el Canvas Editor.
     */
    document.addEventListener('alpine:init', () => {
        Alpine.data('sectionEditor', () => ({
            // Estado básico
            pageId: null,
            sections: [],
            templates: [],
            loading: true,
            saving: false,
            selectedSection: null,
            showAddPanel: false,
            showEditPanel: false,
            sortableInstance: null,

            // Estado Canvas Editor
            previewUrl: '',
            currentViewport: 'desktop',
            previewLoading: true,
            templateSearch: '',
            filteredTemplates: [],

            /**
             * Inicializa el editor.
             */
            async init() {
                this.pageId = this.$el.dataset.pageId;
                this.previewUrl = this.$el.dataset.previewUrl || '';

                // Restaurar viewport guardado
                const savedViewport = localStorage.getItem('jaraba_canvas_viewport');
                if (savedViewport) {
                    this.currentViewport = savedViewport;
                }

                if (!this.pageId) {
                    console.error('[CanvasEditor] No pageId found');
                    return;
                }

                await this.loadSections();
                await this.loadTemplates();
                this.filteredTemplates = this.templates;
                this.initSortable();
                this.loading = false;
            },

            /**
             * Carga las secciones desde la API.
             */
            async loadSections() {
                try {
                    const result = await SectionApi.list(this.pageId);
                    if (result.success) {
                        this.sections = result.sections || [];
                    } else {
                        console.error('[CanvasEditor] Error loading sections:', result);
                    }
                } catch (error) {
                    console.error('[CanvasEditor] API error:', error);
                }
            },

            /**
             * Carga los templates disponibles.
             */
            async loadTemplates() {
                try {
                    const result = await SectionApi.getTemplates();
                    if (result.success) {
                        this.templates = result.templates || [];
                        this.filteredTemplates = this.templates;
                    }
                } catch (error) {
                    console.error('[CanvasEditor] Error loading templates:', error);
                }
            },

            /**
             * Inicializa SortableJS para drag-and-drop.
             */
            initSortable() {
                const container = this.$refs.sectionsContainer;
                if (!container || typeof Sortable === 'undefined') {
                    console.warn('[CanvasEditor] SortableJS not available or no container');
                    return;
                }

                this.sortableInstance = Sortable.create(container, {
                    animation: 250,
                    handle: '.canvas-editor__drag-handle',
                    ghostClass: 'canvas-editor__block--ghost',
                    chosenClass: 'canvas-editor__block--chosen',
                    dragClass: 'canvas-editor__block--drag',
                    onEnd: async (evt) => {
                        await this.handleReorder();
                    }
                });
            },

            /**
             * Maneja el reordenamiento después del drag.
             */
            async handleReorder() {
                const items = this.$refs.sectionsContainer.querySelectorAll('[data-uuid]');
                const order = Array.from(items).map(item => item.dataset.uuid);

                this.saving = true;
                try {
                    const result = await SectionApi.reorder(this.pageId, order);
                    if (result.success) {
                        this.sections = result.sections || this.sections;
                        this.showToast(Drupal.t('Orden actualizado'), 'success');
                        this.refreshPreview();
                    }
                } catch (error) {
                    console.error('[CanvasEditor] Reorder error:', error);
                    this.showToast(Drupal.t('Error al reordenar'), 'error');
                }
                this.saving = false;
            },

            // ─────────────────────────────────────────────────────────────────
            // CANVAS EDITOR: Viewport y Preview
            // ─────────────────────────────────────────────────────────────────

            /**
             * Cambia el viewport del preview (desktop/tablet/mobile).
             */
            setViewport(viewport) {
                this.currentViewport = viewport;

                // Actualizar botones activos
                document.querySelectorAll('.canvas-editor__viewport-btn').forEach(btn => {
                    btn.classList.toggle('is-active', btn.dataset.viewport === viewport);
                });

                // Guardar preferencia
                localStorage.setItem('jaraba_canvas_viewport', viewport);
            },

            /**
             * Refresca el iframe de preview.
             */
            refreshPreview() {
                const iframe = this.$refs.previewIframe;
                if (iframe) {
                    this.previewLoading = true;
                    // Añadir timestamp para evitar cache
                    const url = new URL(this.previewUrl, window.location.origin);
                    url.searchParams.set('_canvas_refresh', Date.now());
                    iframe.src = url.toString();
                }
            },

            /**
             * Callback cuando el iframe termina de cargar.
             */
            onPreviewLoad() {
                this.previewLoading = false;
            },

            /**
             * Hace scroll a un bloque específico en el preview.
             */
            scrollToBlockInPreview(uuid) {
                const iframe = this.$refs.previewIframe;
                if (iframe && iframe.contentWindow) {
                    try {
                        // Intentar comunicar con el iframe via postMessage
                        iframe.contentWindow.postMessage({
                            type: 'JARABA_SCROLL_TO_BLOCK',
                            uuid: uuid
                        }, '*');
                    } catch (e) {
                        console.warn('[CanvasEditor] Cannot access iframe content');
                    }
                }
            },

            // ─────────────────────────────────────────────────────────────────
            // Templates: Filtrado y búsqueda
            // ─────────────────────────────────────────────────────────────────

            /**
             * Filtra templates por búsqueda.
             */
            filterTemplates() {
                const query = this.templateSearch.toLowerCase().trim();
                if (!query) {
                    this.filteredTemplates = this.templates;
                    return;
                }

                this.filteredTemplates = this.templates.filter(template => {
                    return template.label.toLowerCase().includes(query) ||
                        template.id.toLowerCase().includes(query) ||
                        (template.category && template.category.toLowerCase().includes(query));
                });
            },

            // ─────────────────────────────────────────────────────────────────
            // Paneles y CRUD
            // ─────────────────────────────────────────────────────────────────

            /**
             * Abre el panel para añadir sección.
             */
            openAddPanel() {
                this.templateSearch = '';
                this.filteredTemplates = this.templates;
                this.showAddPanel = true;
                if (window.JarabaSlidePanel) {
                    window.JarabaSlidePanel.open('section-add-panel');
                }
            },

            /**
             * Abre el panel para editar una sección.
             */
            openEditPanel(section) {
                this.selectedSection = { ...section };
                this.showEditPanel = true;
                if (window.JarabaSlidePanel) {
                    window.JarabaSlidePanel.open('section-edit-panel');
                }
            },

            /**
             * Añade una nueva sección.
             */
            async addSection(templateId) {
                this.saving = true;
                try {
                    const result = await SectionApi.add(this.pageId, templateId);
                    if (result.success) {
                        await this.loadSections();
                        this.showToast(Drupal.t('Bloque añadido'), 'success');
                        this.closeAddPanel();
                        this.refreshPreview();
                    }
                } catch (error) {
                    console.error('[CanvasEditor] Add error:', error);
                    this.showToast(Drupal.t('Error al añadir bloque'), 'error');
                }
                this.saving = false;
            },

            /**
             * Guarda cambios en una sección.
             */
            async saveSection() {
                if (!this.selectedSection) return;

                this.saving = true;
                try {
                    const result = await SectionApi.update(
                        this.pageId,
                        this.selectedSection.uuid,
                        { content: this.selectedSection.content }
                    );
                    if (result.success) {
                        await this.loadSections();
                        this.showToast(Drupal.t('Bloque guardado'), 'success');
                        this.closeEditPanel();
                        this.refreshPreview();
                    }
                } catch (error) {
                    console.error('[CanvasEditor] Save error:', error);
                    this.showToast(Drupal.t('Error al guardar'), 'error');
                }
                this.saving = false;
            },

            /**
             * Elimina una sección.
             */
            async deleteSection(uuid) {
                if (!confirm(Drupal.t('¿Eliminar este bloque?'))) return;

                this.saving = true;
                try {
                    const result = await SectionApi.delete(this.pageId, uuid);
                    if (result.success) {
                        await this.loadSections();
                        this.showToast(Drupal.t('Bloque eliminado'), 'success');
                        this.refreshPreview();
                    }
                } catch (error) {
                    console.error('[CanvasEditor] Delete error:', error);
                    this.showToast(Drupal.t('Error al eliminar'), 'error');
                }
                this.saving = false;
            },

            /**
             * Alterna la visibilidad de una sección.
             */
            async toggleVisibility(section) {
                this.saving = true;
                try {
                    const result = await SectionApi.update(
                        this.pageId,
                        section.uuid,
                        { visible: !section.visible }
                    );
                    if (result.success) {
                        await this.loadSections();
                        this.refreshPreview();
                    }
                } catch (error) {
                    console.error('[CanvasEditor] Toggle visibility error:', error);
                }
                this.saving = false;
            },

            /**
             * Cierra el panel de añadir.
             */
            closeAddPanel() {
                this.showAddPanel = false;
                if (window.JarabaSlidePanel) {
                    window.JarabaSlidePanel.close();
                }
            },

            /**
             * Cierra el panel de editar.
             */
            closeEditPanel() {
                this.showEditPanel = false;
                this.selectedSection = null;
                if (window.JarabaSlidePanel) {
                    window.JarabaSlidePanel.close();
                }
            },

            /**
             * Muestra un toast de notificación.
             */
            showToast(message, type = 'info') {
                if (Drupal.announce) {
                    Drupal.announce(message);
                }
                console.log(`[CanvasEditor][${type}] ${message}`);
            },

            /**
             * Obtiene el label del template.
             */
            getTemplateLabel(templateId) {
                const template = this.templates.find(t => t.id === templateId);
                return template ? template.label : templateId;
            },

            /**
             * Obtiene el thumbnail del template.
             */
            getTemplateThumbnail(templateId) {
                const template = this.templates.find(t => t.id === templateId);
                return template?.thumbnail || '/modules/custom/jaraba_page_builder/images/placeholder.png';
            },

            // ─────────────────────────────────────────────────────────────────
            // Campos dinámicos: renderizado según fields_schema
            // ─────────────────────────────────────────────────────────────────

            /**
             * Obtiene los campos del schema para el template de la sección seleccionada.
             *
             * Transforma las properties del JSON Schema en un array plano de
             * objetos campo con {name, type, title, widget, options, ...}.
             *
             * @return {Array} Array de definiciones de campo.
             */
            getEditableFields() {
                if (!this.selectedSection) return [];

                const template = this.templates.find(
                    t => t.id === this.selectedSection.template_id
                );

                if (!template || !template.fields_schema || !template.fields_schema.properties) {
                    return [];
                }

                const schema = template.fields_schema;
                const required = schema.required || [];

                return Object.entries(schema.properties).map(([name, def]) => ({
                    name: name,
                    type: def.type || 'string',
                    title: def.title || name.replace(/_/g, ' '),
                    description: def.description || '',
                    widget: def['ui:widget'] || this.inferWidget(def),
                    placeholder: def['ui:placeholder'] || '',
                    required: required.includes(name),
                    options: def.enum || [],
                    min: def.minimum,
                    max: def.maximum,
                    maxLength: def.maxLength,
                    defaultValue: def.default,
                }));
            },

            /**
             * Infiere el widget apropiado según el tipo y formato del campo.
             *
             * @param {Object} fieldDef - Definición JSON Schema del campo.
             * @return {string} Identificador del widget.
             */
            inferWidget(fieldDef) {
                if (fieldDef.enum && fieldDef.enum.length > 0) return 'select';
                if (fieldDef.format === 'uri') return 'url';
                if (fieldDef.format === 'email') return 'email';
                if (fieldDef.format === 'image') return 'image-upload';
                if (fieldDef.type === 'boolean') return 'checkbox';
                if (fieldDef.type === 'number' || fieldDef.type === 'integer') return 'number';
                if (fieldDef.type === 'text') return 'textarea';
                if (fieldDef.maxLength && fieldDef.maxLength > 200) return 'textarea';
                return 'text';
            },

            /**
             * Obtiene el valor de un campo desde el contenido de la sección.
             *
             * @param {string} fieldName - Nombre del campo.
             * @return {*} Valor del campo o cadena vacía.
             */
            getFieldValue(fieldName) {
                if (!this.selectedSection || !this.selectedSection.content) return '';

                var content = this.selectedSection.content;
                if (typeof content === 'string') {
                    try {
                        content = JSON.parse(content);
                    } catch (e) {
                        return '';
                    }
                }

                return content[fieldName] !== undefined ? content[fieldName] : '';
            },

            /**
             * Establece el valor de un campo en el contenido de la sección.
             *
             * Parsea el contenido si es string JSON, actualiza el campo,
             * y guarda el resultado como objeto para que la API lo reciba correctamente.
             *
             * @param {string} fieldName - Nombre del campo.
             * @param {*} value - Nuevo valor.
             */
            setFieldValue(fieldName, value) {
                if (!this.selectedSection) return;

                var content = this.selectedSection.content;
                if (typeof content === 'string') {
                    try {
                        content = JSON.parse(content);
                    } catch (e) {
                        content = {};
                    }
                }
                if (typeof content !== 'object' || content === null) {
                    content = {};
                }

                content[fieldName] = value;
                this.selectedSection.content = content;
            }
        }));
    });

    /**
     * Behavior de Drupal para inicializar el Canvas Editor.
     */
    Drupal.behaviors.jarabaCanvasEditor = {
        attach: function (context, settings) {
            // Inicializar en contenedores .canvas-editor (nuevo) y .section-editor (retrocompatibilidad)
            once('canvas-editor', '.canvas-editor, .section-editor', context).forEach(function (element) {
                console.log('[CanvasEditor] Initialized for page:', element.dataset.pageId);
            });
        }
    };

})(Drupal, once);

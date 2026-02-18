/**
 * @file
 * Jaraba Canvas Editor v3 - Motor GrapesJS.
 *
 * Constructor visual de páginas clase mundial con:
 * - Drag-and-drop de bloques al canvas
 * - Edición inline de texto
 * - Undo/Redo ilimitado
 * - Auto-save con debounce
 * - Preview responsive (mobile/tablet/desktop)
 *
 * @see docs/tecnicos/20260204b-Canvas_Editor_v3_Arquitectura_Maestra.md
 */

(function (Drupal, drupalSettings, once) {
    'use strict';

    /**
     * Configuración por defecto del editor GrapesJS Jaraba.
     */
    const DEFAULT_CONFIG = {
        // Container del editor
        container: '#gjs-editor',
        // Altura del editor
        height: '100%',
        width: 'auto',
        // Desactivar storage por defecto (usamos REST custom)
        storageManager: false,
        // Desactivar modal nativo del Asset Manager
        // El plugin grapesjs-jaraba-assets.js lo reemplaza con un slide-panel premium
        assetManager: {
            embedAsBase64: false,
            // Desactivar apertura automática del modal nativo
            custom: true,
            // No mostrar assets por defecto
            autoAdd: false,
        },
        // Canvas configuration - Estilos inyectados según documentación oficial GrapesJS
        // https://github.com/GrapesJS/grapesjs/blob/master/src/canvas/config/config.ts
        canvas: {
            // Estilos externos inyectados en el <head> del iframe
            styles: [
                // FE-07: CSS compilado del tema (main.css era un duplicado eliminado en PERF-02).
                '/themes/custom/ecosistema_jaraba_theme/css/ecosistema-jaraba-theme.css',
                // CSS compilado de bloques del Page Builder (features, faq, stats, tabs, countdown, timeline, testimonials-3d, pricing).
                '/modules/custom/jaraba_page_builder/css/jaraba-page-builder.css',
                // CSS base para bloques (hero, cta, grid, buttons, media, etc.) — FASE 1.
                '/modules/custom/jaraba_page_builder/css/page-builder-core.css',
                // CSS independientes de bloques con SCSS propio — FASE 1.
                '/modules/custom/jaraba_page_builder/css/navigation.css',
                '/modules/custom/jaraba_page_builder/css/product-card.css',
                '/modules/custom/jaraba_page_builder/css/social-links.css',
                '/modules/custom/jaraba_page_builder/css/contact-form.css',
                // CSS premium: Aceternity UI + Magic UI — efectos especiales (3D, parallax, glassmorphism, orbits, beams, etc.).
                '/modules/custom/jaraba_page_builder/css/premium/aceternity.css',
                '/modules/custom/jaraba_page_builder/css/premium/magic-ui.css',
                // Google Fonts — Outfit (principal del SaaS) + Inter + Plus Jakarta Sans (premium).
                'https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap',
            ],
            scripts: [],
        },
        // Device manager para responsive — Sprint C3: 8 presets
        deviceManager: {
            devices: [
                { id: 'desktop-xl', name: 'Escritorio XL', width: '' },
                { id: 'desktop', name: 'Escritorio', width: '1280px', widthMedia: '1440px' },
                { id: 'laptop', name: 'Portátil', width: '1024px', widthMedia: '1280px' },
                { id: 'tablet-landscape', name: 'Tablet H', width: '1024px', widthMedia: '1024px', height: '768px' },
                { id: 'tablet', name: 'Tablet', width: '768px', widthMedia: '992px', height: '1024px' },
                { id: 'mobile-lg', name: 'Móvil Grande', width: '428px', widthMedia: '480px' },
                { id: 'mobile', name: 'Móvil', width: '375px', widthMedia: '480px' },
                { id: 'mobile-sm', name: 'Móvil S', width: '320px', widthMedia: '320px' },
            ],
        },
        // Panels configuration (vacío, usamos layout custom)
        panels: {
            defaults: [],
        },
        // Block manager - Panel izquierdo
        blockManager: {
            appendTo: '#gjs-blocks-container',
            blocks: [],
        },
        // Style manager - Panel derecho
        styleManager: {
            appendTo: '#gjs-styles-container',
            sectors: [
                {
                    name: 'Diseño',
                    open: true,
                    buildProps: ['width', 'height', 'min-height', 'margin', 'padding'],
                },
                {
                    name: 'Tipografía',
                    buildProps: ['font-family', 'font-size', 'font-weight', 'color', 'text-align'],
                },
                {
                    name: 'Fondo',
                    buildProps: ['background-color', 'background-image'],
                },
            ],
        },
        // Traits manager - Panel derecho
        traitManager: {
            appendTo: '#gjs-traits-container',
        },
        // Layer manager - Panel derecho
        layerManager: {
            appendTo: '#gjs-layers-container',
        },
        // Selector manager - Estilos por componente (no por clase)
        // Ver: https://grapesjs.com/docs/modules/Components.html#component-first-styling
        selectorManager: {
            appendTo: '',
            componentFirst: true, // Estilos se aplican solo al componente seleccionado
        },
        // Internacionalización - Textos en español
        i18n: {
            locale: 'es',
            detectLocale: false,
            messages: {
                es: {
                    // Style Manager
                    styleManager: {
                        empty: 'Selecciona un elemento para editar sus estilos',
                        layer: 'Capa',
                        fileButton: 'Imágenes',
                        sectors: {
                            general: 'General',
                            layout: 'Diseño',
                            typography: 'Tipografía',
                            decorations: 'Decoraciones',
                            extra: 'Extra',
                            flex: 'Flex',
                            dimension: 'Dimensiones',
                        },
                        properties: {
                            'font-family': 'Tipografía',
                            'font-size': 'Tamaño fuente',
                            'font-weight': 'Peso fuente',
                            'color': 'Color',
                            'background-color': 'Color de fondo',
                            'background-image': 'Imagen de fondo',
                            'width': 'Ancho',
                            'height': 'Alto',
                            'min-height': 'Alto mínimo',
                            'margin': 'Margen',
                            'padding': 'Relleno',
                            'text-align': 'Alineación',
                            'border-radius': 'Radio de borde',
                            'border': 'Borde',
                        },
                    },
                    // Block Manager
                    blockManager: {
                        labels: {
                            'text': 'Texto',
                            'link': 'Enlace',
                            'image': 'Imagen',
                            'video': 'Vídeo',
                            'map': 'Mapa',
                        },
                        categories: {
                            // Categorías básicas
                            Basic: 'Básicos',
                            Extra: 'Extras',
                            Forms: 'Formularios',
                            // Categorías del proyecto Jaraba
                            premium: 'Premium',
                            commerce: 'Comercio',
                            hero: 'Hero',
                            content: 'Contenido',
                            features: 'Características',
                            cta: 'Llamada a la Acción',
                            conversion: 'Conversión',
                            layout: 'Diseño',
                            media: 'Multimedia',
                            forms: 'Formularios',
                            social_proof: 'Prueba Social',
                            trust: 'Confianza',
                            timeline: 'Línea de Tiempo',
                            lms: 'Formación',
                            training: 'Capacitación',
                        },
                    },
                    // Panels
                    panels: {
                        buttons: {
                            titles: {
                                preview: 'Vista previa',
                                fullscreen: 'Pantalla completa',
                                'sw-visibility': 'Mostrar bordes',
                                'export-template': 'Exportar código',
                                'open-sm': 'Estilos',
                                'open-layers': 'Capas',
                                'open-blocks': 'Bloques',
                            },
                        },
                    },
                    // Device Manager
                    deviceManager: {
                        device: 'Dispositivo',
                        devices: {
                            desktop: 'Escritorio',
                            tablet: 'Tableta',
                            mobileLandscape: 'Móvil horizontal',
                            mobilePortrait: 'Móvil',
                        },
                    },
                    // Selector Manager
                    selectorManager: {
                        label: 'Clases',
                        selected: 'Seleccionado',
                        emptyState: 'Estado vacío',
                        states: {
                            hover: 'Hover',
                            active: 'Activo',
                            'nth-of-type(2n)': 'Par',
                        },
                    },
                    // Trait Manager
                    traitManager: {
                        empty: 'Selecciona un elemento para ver sus propiedades',
                        label: 'Propiedades',
                        traits: {
                            labels: {
                                id: 'ID',
                                alt: 'Texto alternativo',
                                title: 'Título',
                                href: 'Enlace',
                            },
                        },
                    },
                    // DOM Components
                    domComponents: {
                        names: {
                            '': 'Caja',
                            wrapper: 'Cuerpo',
                            text: 'Texto',
                            comment: 'Comentario',
                            image: 'Imagen',
                            video: 'Vídeo',
                            label: 'Etiqueta',
                            link: 'Enlace',
                            map: 'Mapa',
                            tfoot: 'Pie de tabla',
                            tbody: 'Cuerpo de tabla',
                            thead: 'Cabecera de tabla',
                            table: 'Tabla',
                            row: 'Fila',
                            cell: 'Celda',
                        },
                    },
                    // Asset Manager
                    assetManager: {
                        addButton: 'Añadir imagen',
                        inputPlh: 'URL de la imagen...',
                        modalTitle: 'Seleccionar imagen',
                        uploadTitle: 'Arrastra archivos aquí o haz clic para subir',
                    },
                },
            },
        },
    };

    /**
     * Clase principal del Canvas Editor Jaraba.
     */
    class JarabaCanvasEditor {
        /**
         * Constructor del editor.
         *
         * @param {HTMLElement} container - Elemento contenedor del editor.
         * @param {Object} options - Opciones de configuración.
         */
        constructor(container, options = {}) {
            this.container = container;
            // CRITICAL FIX: Always use CSS selector string for container.
            // Passing a DOM element can cause GrapesJS to fail silently
            // when Drupal's behavior lifecycle re-processes the context.
            this.options = {
                ...DEFAULT_CONFIG,
                ...options,
                container: '#gjs-editor',
            };
            this.editor = null;
            this.isInitialized = false;
            this.pageId = drupalSettings.jarabaCanvas?.pageId || null;
            this.tenantId = drupalSettings.jarabaCanvas?.tenantId || null;
            this.vertical = drupalSettings.jarabaCanvas?.vertical || 'generic';
            this.autoSaveTimeout = null;
            this.autoSaveDelay = 5000; // 5 segundos
            this.isDirty = false;

            this.init();
        }

        /**
         * Inicializa el editor GrapesJS.
         */
        init() {
            // Verificar que GrapesJS está disponible
            if (typeof grapesjs === 'undefined') {
                console.error('[Jaraba Canvas] GrapesJS no está cargado. Verifica las dependencias.');
                return;
            }

            // Prevenir doble inicialización
            if (this.isInitialized) {
                console.warn('[Jaraba Canvas] Editor ya inicializado, omitiendo.');
                return;
            }

            // Inyectar Design Tokens del tenant en el canvas
            this.injectDesignTokens();

            // Actualizar indicador de carga
            this.updateLoadingStep('Inicializando GrapesJS...');

            // Inicializar GrapesJS con selector CSS string (más robusto que DOM element)
            console.log('[Jaraba Canvas] Llamando grapesjs.init() con container:', this.options.container);
            this.editor = grapesjs.init(this.options);

            // Verificar que el editor se inicializó correctamente
            if (!this.editor || !this.editor.BlockManager) {
                console.error('[Jaraba Canvas] GrapesJS init falló. BlockManager no disponible.');
                return;
            }

            console.log('[Jaraba Canvas] GrapesJS inicializado. Módulos:', {
                BlockManager: !!this.editor.BlockManager,
                DomComponents: !!this.editor.DomComponents,
                Canvas: !!this.editor.Canvas,
                Commands: !!this.editor.Commands,
            });

            this.isInitialized = true;

            // Medir altura real del toolbar y configurar CSS variable
            this.updateToolbarHeight();

            // ─── Configuración inmediata (no necesita 'load' event) ───

            // Configurar Storage Manager custom (REST)
            this.setupStorageManager();

            // Configurar auto-save
            this.setupAutoSave();

            // Registrar comando jaraba:save
            const self = this;
            this.editor.Commands.add('jaraba:save', {
                async run(editor) {
                    try {
                        await editor.store();
                        console.log('[Jaraba Canvas] Guardado via jaraba:save');
                    } catch (error) {
                        console.error('[Jaraba Canvas] Error en jaraba:save:', error);
                    }
                },
            });

            // Configurar eventos del canvas (inyección de estilos, etc.)
            this.setupEventListeners();

            // Configurar tabs del panel derecho (Estilos, Propiedades, Capas)
            this.setupPanelTabs();

            // Configurar toggle de viewport (Desktop, Tablet, Mobile)
            this.setupViewportToggle();

            // Configurar controles de historial (Undo/Redo)
            this.setupHistoryControls();

            // Sprint A3: Configurar mejoras visuales de drag & drop
            this.setupDragDropPolish();

            // ─── Carga diferida (cuando el editor esté listo) ───

            this.updateLoadingStep('Cargando bloques y plugins...');

            // Cargar bloques dinámicos desde el servidor
            try {
                this.loadBlocks();
            } catch (e) {
                console.error('[Jaraba Canvas] Error cargando bloques:', e);
            }

            // Ejecutar plugins registrados de forma segura
            this.loadPlugin('jaraba-blocks');
            this.loadPlugin('jaraba-legal-blocks');
            this.loadPlugin('jaraba-seo');
            this.loadPlugin('jaraba-ai');
            this.loadPlugin('jaraba-partials');
            this.loadPlugin('jaraba-command-palette');

            // Registrar componentes Jaraba custom
            try {
                this.registerJarabaComponents();
            } catch (e) {
                console.error('[Jaraba Canvas] Error registrando componentes:', e);
            }

            // Cargar contenido existente
            this.updateLoadingStep('Cargando contenido...');
            this.loadContent();

            // Ocultar skeleton de carga
            this.hideLoadingSkeleton();

            console.log('[Jaraba Canvas] Jaraba Canvas Editor v3 inicializado correctamente.');

            // Exponer editor globalmente para E2E tests y plugins externos
            window.editor = this.editor;
        }

        /**
         * Carga un plugin GrapesJS de forma segura con manejo de errores.
         *
         * @param {string} pluginName - Nombre del plugin registrado.
         */
        loadPlugin(pluginName) {
            try {
                const plugin = grapesjs.plugins.get(pluginName);
                if (plugin) {
                    plugin(this.editor);
                    console.log(`[Jaraba Canvas] Plugin ${pluginName} ejecutado.`);
                } else {
                    console.warn(`[Jaraba Canvas] Plugin ${pluginName} no encontrado.`);
                }
            } catch (e) {
                console.error(`[Jaraba Canvas] Error ejecutando plugin ${pluginName}:`, e);
            }
        }

        /**
         * Mide la altura real del toolbar y la establece como CSS variable
         * para que el layout del canvas se ajuste dinámicamente.
         */
        updateToolbarHeight() {
            const saasHeader = document.querySelector('.canvas-editor__saas-header');
            const toolbar = document.querySelector('.canvas-editor__toolbar');
            let totalHeight = 0;

            if (saasHeader) totalHeight += saasHeader.offsetHeight;
            if (toolbar) totalHeight += toolbar.offsetHeight;

            if (totalHeight > 0) {
                document.documentElement.style.setProperty(
                    '--jaraba-editor-toolbar-height',
                    `${totalHeight}px`
                );
                console.log(`[Jaraba Canvas] Altura del toolbar: ${totalHeight}px`);
            }
        }

        /**
         * Actualiza el texto del paso de carga en el skeleton.
         *
         * @param {string} stepText - Texto descriptivo del paso actual.
         */
        updateLoadingStep(stepText) {
            const stepsEl = document.getElementById('gjs-loading-steps');
            if (stepsEl) {
                stepsEl.innerHTML = `<div class="gjs-loading-step">${stepText}</div>`;
            }
        }

        /**
         * Oculta el skeleton de carga con animación de fade-out.
         */
        hideLoadingSkeleton() {
            const skeleton = document.getElementById('gjs-loading-skeleton');
            if (skeleton) {
                setTimeout(() => {
                    skeleton.classList.add('is-hidden');
                    setTimeout(() => skeleton.remove(), 400);
                }, 300);
            }
        }

        /**
         * Inyecta los Design Tokens del tenant en el iframe del canvas.
         */
        injectDesignTokens() {
            const tokens = drupalSettings.jarabaCanvas?.designTokens || {};
            let cssVars = ':root {\n';

            Object.entries(tokens).forEach(([key, value]) => {
                cssVars += `  --${key}: ${value};\n`;
            });

            cssVars += '}\n';

            // Los Design Tokens se inyectarán cuando el canvas esté listo
            this.designTokensCss = cssVars;
        }

        /**
         * Inyecta estilos del tema en el iframe del canvas.
         * Puede ser llamado múltiples veces (es idempotente).
         */
        injectCanvasStyles() {
            const self = this;
            const canvas = this.editor.Canvas;
            if (!canvas) return;

            const frame = canvas.getFrameEl();
            if (!frame || !frame.contentDocument) return;

            const doc = frame.contentDocument;

            // Verificar si ya se inyectaron los estilos
            if (doc.querySelector('link[data-jaraba-injected]')) {
                console.log('Estilos ya inyectados, omitiendo...');
                return;
            }

            // 1. Inyectar Design Tokens
            const tokenStyle = doc.createElement('style');
            tokenStyle.setAttribute('data-jaraba-injected', 'tokens');
            tokenStyle.innerHTML = self.designTokensCss;
            doc.head.appendChild(tokenStyle);

            // 2. Inyectar CSS del tema principal
            const themeLink = doc.createElement('link');
            themeLink.rel = 'stylesheet';
            themeLink.setAttribute('data-jaraba-injected', 'theme');
            themeLink.href = '/themes/custom/ecosistema_jaraba_theme/css/ecosistema-jaraba-theme.css';
            doc.head.appendChild(themeLink);

            // 3. Inyectar CSS de bloques del Page Builder
            const pbCssFiles = [
                '/modules/custom/jaraba_page_builder/css/jaraba-page-builder.css',
                '/modules/custom/jaraba_page_builder/css/page-builder-core.css',
                '/modules/custom/jaraba_page_builder/css/navigation.css',
                '/modules/custom/jaraba_page_builder/css/product-card.css',
                '/modules/custom/jaraba_page_builder/css/social-links.css',
                '/modules/custom/jaraba_page_builder/css/contact-form.css',
                '/modules/custom/jaraba_page_builder/css/premium/aceternity.css',
                '/modules/custom/jaraba_page_builder/css/premium/magic-ui.css',
            ];
            pbCssFiles.forEach((cssHref, idx) => {
                const link = doc.createElement('link');
                link.rel = 'stylesheet';
                link.setAttribute('data-jaraba-injected', 'pb-css-' + idx);
                link.href = cssHref;
                doc.head.appendChild(link);
            });

            // 4. Inyectar Google Fonts — Outfit (principal) + Inter + Plus Jakarta Sans
            const fontLink = doc.createElement('link');
            fontLink.rel = 'stylesheet';
            fontLink.setAttribute('data-jaraba-injected', 'fonts');
            fontLink.href = 'https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap';
            doc.head.appendChild(fontLink);

            console.log('Estilos del tema inyectados en el canvas de GrapesJS.');
        }

        /**
         * Registra los componentes Jaraba custom en GrapesJS.
         */
        registerJarabaComponents() {
            // Componentes estructurales (header, footer, content-zone)
            // Se registran en grapesjs-jaraba-partials.js

            // Componentes de bloques
            // Se registran en grapesjs-jaraba-blocks.js
        }

        /**
         * Carga los bloques disponibles desde el servidor.
         */
        async loadBlocks() {
            try {
                const response = await fetch(`/api/v1/page-builder/blocks?tenant=${this.tenantId}`);
                if (!response.ok) throw new Error('Error al cargar bloques');

                const blocks = await response.json();
                const blockManager = this.editor.BlockManager || this.editor.Blocks;

                if (!blockManager) {
                    console.error('[Jaraba Canvas] BlockManager no disponible. Editor no inicializado correctamente.');
                    return;
                }

                blocks.forEach((block) => {
                    blockManager.add(block.id, {
                        label: block.label,
                        category: block.category,
                        media: block.media || this.getDefaultBlockIcon(block.category),
                        content: block.content,
                        attributes: {
                            'data-block-id': block.id,
                            'data-block-schema': JSON.stringify(block.schema || {}),
                        },
                    });
                });

                console.log(`${blocks.length} bloques cargados.`);
            } catch (error) {
                console.error('Error cargando bloques:', error);
                // Cargar bloques básicos de fallback
                this.loadFallbackBlocks();
            }
        }

        /**
         * Carga bloques básicos de fallback si falla el servidor.
         */
        loadFallbackBlocks() {
            const blockManager = this.editor.BlockManager || this.editor.Blocks;

            if (!blockManager) {
                console.error('[Jaraba Canvas] BlockManager no disponible para fallback blocks.');
                return;
            }

            blockManager.add('jaraba-text', {
                label: 'Texto',
                category: 'Básicos',
                media: '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M5,4V7H10.5V19H13.5V7H19V4H5Z"/></svg>',
                content: '<div data-gjs-type="text" class="jaraba-text">Haz clic para editar este texto</div>',
            });

            blockManager.add('jaraba-heading', {
                label: 'Título',
                category: 'Básicos',
                media: '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M3,4H5V10H9V4H11V18H9V12H5V18H3V4M13,8H15V18H17V8H19V6H13V8Z"/></svg>',
                content: '<h2 data-gjs-type="text" class="jaraba-heading">Título de sección</h2>',
            });

            blockManager.add('jaraba-button', {
                label: 'Botón',
                category: 'Básicos',
                media: '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M12,20C7.59,20 4,16.41 4,12C4,7.59 7.59,4 12,4C16.41,4 20,7.59 20,12C20,16.41 16.41,20 12,20M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z"/></svg>',
                content: '<a href="#" class="jaraba-button jaraba-button--primary">Botón CTA</a>',
            });

            console.log('Bloques de fallback cargados.');
        }

        /**
         * Obtiene icono por defecto según la categoría del bloque.
         *
         * @param {string} category - Categoría del bloque.
         * @returns {string} SVG del icono.
         */
        getDefaultBlockIcon(category) {
            const icons = {
                hero: '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M21,3H3C1.89,3 1,3.89 1,5V19A2,2 0 0,0 3,21H21C22.11,21 23,20.11 23,19V5C23,3.89 22.11,3 21,3M21,19H3V5H21V19Z"/></svg>',
                features: '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M3,11H11V3H3V11M5,5H9V9H5V5M13,21H21V13H13V21M15,15H19V19H15V15M3,21H11V13H3V21M5,15H9V19H5V15M13,3V11H21V3H13M19,9H15V5H19V9Z"/></svg>',
                cta: '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22A10,10 0 0,1 2,12A10,10 0 0,1 12,2M12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20A8,8 0 0,0 20,12A8,8 0 0,0 12,4M11,17V16H9V14H13V13H11A2,2 0 0,1 9,11V10A2,2 0 0,1 11,8H12V7H14V8H15V10H11V11H13A2,2 0 0,1 15,13V14A2,2 0 0,1 13,16H12V17H10Z"/></svg>',
            };

            return icons[category] || '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M19,5V19H5V5H19M21,3H3V21H21V3Z"/></svg>';
        }

        /**
         * Configura el Storage Manager custom para guardar via REST.
         */
        setupStorageManager() {
            const self = this;

            this.editor.StorageManager.add('jaraba-rest', {
                async store(data) {
                    try {
                        const response = await fetch(`/api/v1/pages/${self.pageId}/canvas`, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-Token': drupalSettings.jarabaCanvas?.csrfToken || '',
                            },
                            body: JSON.stringify({
                                components: data.components,
                                styles: data.styles,
                                html: self.editor.getHtml(),
                                css: self.editor.getCss(),
                            }),
                        });

                        if (!response.ok) throw new Error('Error al guardar');

                        self.isDirty = false;
                        Drupal.announce(Drupal.t('Cambios guardados'));

                        return data;
                    } catch (error) {
                        console.error('Error guardando canvas:', error);
                        Drupal.announce(Drupal.t('Error al guardar. Reintentando...'));
                        throw error;
                    }
                },

                async load() {
                    try {
                        const response = await fetch(`/api/v1/pages/${self.pageId}/canvas`);
                        if (!response.ok) throw new Error('Error al cargar');

                        return await response.json();
                    } catch (error) {
                        console.error('Error cargando canvas:', error);
                        return {};
                    }
                },
            });

            // Activar storage custom
            this.editor.StorageManager.setCurrent('jaraba-rest');
        }

        /**
         * Configura auto-save con debounce.
         */
        setupAutoSave() {
            this.editor.on('change:changesCount', () => {
                this.isDirty = true;

                // Limpiar timeout anterior
                if (this.autoSaveTimeout) {
                    clearTimeout(this.autoSaveTimeout);
                }

                // Programar auto-save
                this.autoSaveTimeout = setTimeout(() => {
                    if (this.isDirty) {
                        this.save();
                    }
                }, this.autoSaveDelay);
            });
        }

        /**
         * Guarda el contenido del canvas.
         */
        async save() {
            try {
                await this.editor.store();
                console.log('Canvas guardado correctamente.');
            } catch (error) {
                console.error('Error en save():', error);
                // Reintentar con backoff exponencial
                setTimeout(() => this.save(), 2000);
            }
        }

        /**
         * Configura event listeners del editor.
         */
        setupEventListeners() {
            const self = this;

            // Cuando el canvas esté listo, inyectar estilos del tema
            this.editor.on('canvas:frame:load', ({ window }) => {
                const doc = window.document;

                // 1. Inyectar Design Tokens (CSS dinámico, no disponible como archivo estático)
                const tokenStyle = doc.createElement('style');
                tokenStyle.innerHTML = self.designTokensCss;
                doc.head.appendChild(tokenStyle);

                // FIX M5: Los CSS del tema, Page Builder y Google Fonts ya se inyectan
                // via canvas.styles en la config de GrapesJS (líneas 42-59).
                // No re-inyectar aquí para evitar doble carga y conflictos de especificidad.

                // 2. Inyectar mocks de Drupal y once() para scripts premium
                const drupalMock = doc.createElement('script');
                drupalMock.textContent = `
                    // Mock de Drupal para el iframe del canvas
                    window.Drupal = window.Drupal || {};
                    Drupal.behaviors = Drupal.behaviors || {};
                    Drupal.t = function(str) { return str; };
                    Drupal.announce = function() {};
                    
                    // Mock de once() para evitar re-ejecución
                    window.once = function(id, selector, context) {
                        context = context || document;
                        var elements = context.querySelectorAll(selector);
                        var result = [];
                        elements.forEach(function(el) {
                            var key = 'once_' + id;
                            if (!el.dataset[key]) {
                                el.dataset[key] = 'true';
                                result.push(el);
                            }
                        });
                        return result;
                    };
                `;
                doc.head.appendChild(drupalMock);

                // 3. Inyectar script de bloques premium (animaciones, efectos)
                const premiumScript = doc.createElement('script');
                premiumScript.src = '/modules/custom/jaraba_page_builder/js/premium-blocks.js';
                premiumScript.onload = () => {
                    // Ejecutar behaviors después de que el script cargue
                    Object.keys(window.Drupal.behaviors).forEach(key => {
                        if (typeof window.Drupal.behaviors[key].attach === 'function') {
                            try {
                                window.Drupal.behaviors[key].attach(doc);
                            } catch (e) {
                                console.warn('Error ejecutando behavior ' + key + ':', e);
                            }
                        }
                    });
                    console.log('Premium blocks inicializados en el canvas.');
                };
                doc.body.appendChild(premiumScript);

                console.log('Estilos del tema inyectados en el canvas de GrapesJS.');
            });

            // Notificar cambios en header/footer (persistencia global)
            // IMPORTANTE: Validar que el modelo existe antes de acceder a sus propiedades
            // para evitar TypeError cuando GrapesJS dispara el evento sin modelo válido
            this.editor.on('component:update', (model) => {
                // Guard clause: verificar que el modelo es válido
                if (!model || typeof model.get !== 'function') {
                    return;
                }

                const type = model.get('type');
                if (type === 'jaraba-header' || type === 'jaraba-footer') {
                    self.notifyGlobalChange(type, model.getAttributes());
                }
            });

            // Re-ejecutar behaviors premium cuando se añade un nuevo componente
            // Esto asegura que bloques con animaciones (Animated Beam, Parallax, etc.)
            // se inicialicen correctamente al arrastrarlos al canvas
            this.editor.on('component:add', () => {
                const canvas = self.editor.Canvas;
                if (!canvas) return;

                const frame = canvas.getFrameEl();
                if (!frame || !frame.contentWindow) return;

                const iframeWindow = frame.contentWindow;
                const iframeDoc = frame.contentDocument;

                // Re-ejecutar behaviors con un pequeño delay para permitir el render
                setTimeout(() => {
                    if (iframeWindow.Drupal && iframeWindow.Drupal.behaviors) {
                        Object.keys(iframeWindow.Drupal.behaviors).forEach(key => {
                            if (typeof iframeWindow.Drupal.behaviors[key].attach === 'function') {
                                try {
                                    iframeWindow.Drupal.behaviors[key].attach(iframeDoc);
                                } catch (e) {
                                    // Silently ignore - algunos behaviors pueden fallar en re-attach
                                }
                            }
                        });
                    }
                }, 100);
            });

            // Keyboard shortcuts
            document.addEventListener('keydown', (e) => {
                // Ctrl+S = Guardar
                if (e.ctrlKey && !e.shiftKey && e.key === 's') {
                    e.preventDefault();
                    self.save();
                }
                // Ctrl+P = Vista previa
                if (e.ctrlKey && !e.shiftKey && e.key === 'p') {
                    e.preventDefault();
                    const previewBtn = document.querySelector('[data-action="preview"]');
                    if (previewBtn) previewBtn.click();
                }
                // Ctrl+Shift+P = Publicar
                if (e.ctrlKey && e.shiftKey && e.key === 'P') {
                    e.preventDefault();
                    const publishBtn = document.querySelector('[data-action="publish"]');
                    if (publishBtn) publishBtn.click();
                }
            });
        }

        /**
         * Notifica cambios en componentes globales (header/footer).
         *
         * @param {string} type - Tipo de componente ('jaraba-header' | 'jaraba-footer').
         * @param {Object} attributes - Atributos actualizados.
         */
        notifyGlobalChange(type, attributes) {
            // Mostrar toast de advertencia
            const message = type === 'jaraba-header'
                ? Drupal.t('Los cambios en el encabezado se aplicarán a TODAS las páginas de tu sitio.')
                : Drupal.t('Los cambios en el pie de página se aplicarán a TODAS las páginas de tu sitio.');

            // Enviar al iframe de preview via postMessage
            const iframe = document.querySelector('.canvas-editor__preview iframe');
            if (iframe && iframe.contentWindow) {
                iframe.contentWindow.postMessage({
                    type: type === 'jaraba-header' ? 'JARABA_HEADER_CHANGE' : 'JARABA_FOOTER_CHANGE',
                    variant: attributes['header-type'] || attributes['footer-type'],
                    attributes: attributes,
                }, '*');
            }
        }

        /**
         * Carga el contenido existente de la página.
         * 
         * Implementa un mecanismo de retry robusto para inyectar el HTML
         * del template, evitando race conditions con GrapesJS.
         */
        async loadContent() {
            const self = this;

            try {
                const data = await this.editor.load();

                // Si ya hay componentes, GrapesJS los cargó automáticamente
                if (data && data.components && data.components.length > 0) {
                    console.log('Contenido cargado correctamente (componentes).');
                    return;
                }

                // Si hay HTML del template pero no hay componentes,
                // usar retry loop para inyectar de forma robusta
                if (data && data.html && data.html.trim().length > 0) {
                    console.log('HTML del template detectado, iniciando inyección...');

                    // Helper para obtener componentes del canvas de forma segura
                    const getCanvasComponents = () => {
                        if (self.editor.DomComponents) {
                            const wrapper = self.editor.DomComponents.getWrapper();
                            if (wrapper) {
                                // GrapesJS v0.21.x usa components() no getComponents()
                                if (typeof wrapper.components === 'function') {
                                    return wrapper.components();
                                }
                                if (typeof wrapper.getComponents === 'function') {
                                    return wrapper.getComponents();
                                }
                            }
                            return { length: 0 };
                        }
                        // Fallback: usar getComponents() si está disponible
                        if (typeof self.editor.getComponents === 'function') {
                            return self.editor.getComponents();
                        }
                        return { length: 0 };
                    };

                    // Función para inyectar con verificación
                    const injectTemplate = (attempt = 1) => {
                        const maxAttempts = 5;
                        const delay = 100; // 100ms entre intentos

                        // Verificar que el canvas sigue vacío
                        if (getCanvasComponents().length === 0) {
                            console.log(`[Jaraba Canvas] Intento ${attempt}: Inyectando HTML del template...`);
                            self.editor.setComponents(data.html);

                            // También cargar CSS si existe
                            if (data.css) {
                                self.editor.setStyle(data.css);
                            }

                            // Verificar que la inyección funcionó
                            setTimeout(() => {
                                const count = getCanvasComponents().length;
                                if (count > 0) {
                                    console.log(`Template pre-cargado correctamente (${count} componentes).`);
                                    // Re-inyectar estilos después de cargar el template
                                    self.injectCanvasStyles();
                                } else if (attempt < maxAttempts) {
                                    // Si falló, reintentar
                                    console.log(`Inyección no persistió, reintentando...`);
                                    setTimeout(() => injectTemplate(attempt + 1), delay);
                                } else {
                                    console.warn('No se pudo inyectar el template después de múltiples intentos.');
                                }
                            }, 50);
                        } else {
                            console.log('El canvas ya tiene contenido, omitiendo inyección.');
                        }
                    };

                    // Iniciar inyección después de un breve delay
                    setTimeout(injectTemplate, 100);
                }
            } catch (error) {
                console.error('Error cargando contenido:', error);
            }
        }

        /**
         * Los tabs controlan qué contenedor se muestra:
         * - styles → #gjs-styles-container
         * - traits → #gjs-traits-container
         * - layers → #gjs-layers-container
         */
        setupPanelTabs() {
            const tabs = document.querySelectorAll('.jaraba-grapesjs-tab');
            const containers = {
                styles: document.getElementById('gjs-styles-container'),
                traits: document.getElementById('gjs-traits-container'),
                layers: document.getElementById('gjs-layers-container'),
                design: document.getElementById('gjs-design-container'),
            };

            // Verificar que existen los elementos
            if (tabs.length === 0) {
                console.warn('Tabs del panel derecho no encontrados.');
                return;
            }

            tabs.forEach((tab) => {
                tab.addEventListener('click', () => {
                    const panelId = tab.dataset.panel;

                    // 1. Remover is-active de todos los tabs
                    tabs.forEach((t) => t.classList.remove('is-active'));

                    // 2. Añadir is-active al tab clickeado
                    tab.classList.add('is-active');

                    // 3. Ocultar todos los contenedores
                    Object.values(containers).forEach((container) => {
                        if (container) {
                            container.hidden = true;
                        }
                    });

                    // 4. Mostrar el contenedor correspondiente
                    if (containers[panelId]) {
                        containers[panelId].hidden = false;
                    }

                    console.log(`Tab "${panelId}" activado.`);
                });
            });

            console.log('Tabs del panel derecho configurados correctamente.');
        }

        /**
         * Configura botones de viewport (8 presets) + custom slider + rotación.
         *
         * Sprint C3: Conecta 8 botones del toolbar con el Device Manager,
         * slider de ancho personalizado, y toggle de rotación.
         *
         * @see grapesjs-jaraba-canvas.js deviceManager
         */
        setupViewportToggle() {
            const self = this;
            const trigger = document.getElementById('viewport-dropdown-trigger');
            const panel = document.getElementById('viewport-dropdown-panel');
            const buttons = document.querySelectorAll('.canvas-editor__viewport-btn:not(.canvas-editor__viewport-btn--rotate)');

            if (!trigger || !panel || buttons.length === 0) {
                console.warn(Drupal.t('Viewport dropdown no encontrado.'));
                return;
            }

            // Preset → ancho mapa para sincronizar el slider.
            const deviceWidths = {
                'desktop-xl': 1920,
                'desktop': 1280,
                'laptop': 1024,
                'tablet-landscape': 1024,
                'tablet': 768,
                'mobile-lg': 428,
                'mobile': 375,
                'mobile-sm': 320,
            };

            // Mapa viewport → icono tipo para actualizar el trigger
            const deviceIcons = {
                'desktop-xl': 'monitor',
                'desktop': 'monitor',
                'laptop': 'laptop',
                'tablet-landscape': 'tablet',
                'tablet': 'tablet',
                'mobile-lg': 'smartphone',
                'mobile': 'smartphone',
                'mobile-sm': 'smartphone',
            };

            // Posicionar panel fixed debajo del trigger
            const positionPanel = () => {
                const rect = trigger.getBoundingClientRect();
                panel.style.top = (rect.bottom + 6) + 'px';
                panel.style.left = Math.max(0, rect.left + rect.width / 2 - 130) + 'px';
            };

            // Toggle dropdown abierto/cerrado
            const togglePanel = () => {
                const isOpen = panel.classList.toggle('is-open');
                trigger.setAttribute('aria-expanded', isOpen);
                panel.setAttribute('aria-hidden', !isOpen);
                if (isOpen) positionPanel();
            };

            const closePanel = () => {
                panel.classList.remove('is-open');
                trigger.setAttribute('aria-expanded', 'false');
                panel.setAttribute('aria-hidden', 'true');
            };

            // Actualizar trigger con viewport activo
            const updateTrigger = (viewport, width) => {
                const triggerLabel = document.getElementById('viewport-trigger-label');
                const triggerIcon = document.getElementById('viewport-trigger-icon');
                if (triggerLabel) {
                    triggerLabel.textContent = width + 'px';
                }
                if (triggerIcon && deviceIcons[viewport]) {
                    // Actualizar icono clonando del botón activo
                    const activeBtn = document.querySelector('.canvas-editor__viewport-btn[data-viewport="' + viewport + '"]');
                    if (activeBtn) {
                        const icon = activeBtn.querySelector('svg, img');
                        if (icon) {
                            triggerIcon.innerHTML = '';
                            triggerIcon.appendChild(icon.cloneNode(true));
                        }
                    }
                }
            };

            // Click en trigger → toggle panel
            trigger.addEventListener('click', (e) => {
                e.stopPropagation();
                togglePanel();
            });

            // Cerrar al hacer click fuera
            document.addEventListener('click', (e) => {
                if (!trigger.contains(e.target) && !panel.contains(e.target)) {
                    closePanel();
                }
            });

            // Cerrar con Escape
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') closePanel();
            });

            const getSliderElements = () => ({
                slider: document.getElementById('viewport-custom-width'),
                output: document.getElementById('viewport-custom-value'),
            });

            // 1. Botones de preset.
            buttons.forEach((btn) => {
                btn.addEventListener('click', () => {
                    const viewport = btn.dataset.viewport;

                    // Actualizar clases de botones.
                    buttons.forEach((b) => b.classList.remove('is-active'));
                    btn.classList.add('is-active');

                    // Cambiar dispositivo en GrapesJS.
                    if (self.editor && self.editor.Devices) {
                        self.editor.Devices.select(viewport);
                    }

                    // Sincronizar slider con el preset seleccionado.
                    const { slider, output } = getSliderElements();
                    const width = deviceWidths[viewport] || 1920;
                    if (slider) {
                        slider.value = width;
                        if (output) {
                            output.textContent = width + 'px';
                        }
                    }

                    // Actualizar trigger y cerrar panel
                    updateTrigger(viewport, width);
                    closePanel();
                });
            });

            // 2. Slider de ancho custom — bind diferido.
            const bindSlider = () => {
                const { slider, output } = getSliderElements();
                if (!slider) return false;

                slider.addEventListener('input', () => {
                    const width = slider.value;
                    const out = document.getElementById('viewport-custom-value');
                    if (out) {
                        out.textContent = width + 'px';
                    }

                    // Deseleccionar presets activos.
                    buttons.forEach((b) => b.classList.remove('is-active'));

                    // Actualizar trigger label
                    const triggerLabel = document.getElementById('viewport-trigger-label');
                    if (triggerLabel) {
                        triggerLabel.textContent = width + 'px';
                    }

                    // Aplicar ancho custom al canvas de GrapesJS.
                    if (self.editor) {
                        const canvasFrame = self.editor.Canvas.getFrameEl();
                        if (canvasFrame) {
                            const wrapper = canvasFrame.parentElement;
                            if (wrapper) {
                                wrapper.style.width = width + 'px';
                                wrapper.style.transition = 'width 0.15s ease';
                            }
                        }
                    }
                });
                return true;
            };

            // 3. Botón de rotación — bind diferido.
            const bindRotate = () => {
                const rotateBtn = document.getElementById('viewport-rotate-btn');
                if (!rotateBtn) return false;

                let isRotated = false;
                rotateBtn.addEventListener('click', () => {
                    isRotated = !isRotated;
                    rotateBtn.classList.toggle('is-active', isRotated);

                    if (self.editor) {
                        const canvasFrame = self.editor.Canvas.getFrameEl();
                        if (canvasFrame) {
                            const wrapper = canvasFrame.parentElement;
                            if (wrapper) {
                                const currentW = wrapper.style.width || wrapper.offsetWidth + 'px';
                                const currentH = wrapper.style.height || wrapper.offsetHeight + 'px';
                                wrapper.style.width = currentH;
                                wrapper.style.height = currentW;
                                wrapper.style.transition = 'width 0.2s ease, height 0.2s ease';
                            }
                        }
                    }
                });
                return true;
            };

            // Intentar bind inmediato, si falla reintentar con retardo.
            if (!bindSlider() || !bindRotate()) {
                setTimeout(() => {
                    bindSlider();
                    bindRotate();
                }, 500);
            }

            console.log(Drupal.t('Viewport: @count presets configurados (dropdown).', { '@count': buttons.length }));
        }

        /**
         * Configura los controles de historial (Undo/Redo).
         * 
         * Conecta los botones del toolbar con el UndoManager de GrapesJS
         * para deshacer y rehacer cambios en el canvas.
         */
        setupHistoryControls() {
            const self = this;
            const undoBtn = document.getElementById('canvas-undo-btn');
            const redoBtn = document.getElementById('canvas-redo-btn');

            if (!undoBtn || !redoBtn) {
                console.warn('Botones de historial no encontrados.');
                return;
            }

            // Función para actualizar estado de los botones
            const updateButtonStates = () => {
                if (!self.editor || !self.editor.UndoManager) return;

                const um = self.editor.UndoManager;
                const hasUndo = um.hasUndo();
                const hasRedo = um.hasRedo();

                // Actualizar botón Undo
                undoBtn.disabled = !hasUndo;
                undoBtn.classList.toggle('has-actions', hasUndo);

                // Actualizar botón Redo
                redoBtn.disabled = !hasRedo;
                redoBtn.classList.toggle('has-actions', hasRedo);
            };

            // Event listener para Undo
            undoBtn.addEventListener('click', () => {
                if (self.editor && self.editor.UndoManager.hasUndo()) {
                    self.editor.runCommand('core:undo');
                    console.log('Undo ejecutado');
                }
            });

            // Event listener para Redo
            redoBtn.addEventListener('click', () => {
                if (self.editor && self.editor.UndoManager.hasRedo()) {
                    self.editor.runCommand('core:redo');
                    console.log('Redo ejecutado');
                }
            });

            // Escuchar cambios en el historial para actualizar estado de botones
            // GrapesJS dispara 'undo' y 'redo' cuando se ejecutan, y 'component:*' en cambios
            this.editor.on('undo redo component:add component:remove component:update', updateButtonStates);

            // Actualizar estado inicial después de que el editor esté listo
            this.editor.on('load', updateButtonStates);

            // También actualizar cuando se cargue contenido y periódicamente
            setTimeout(updateButtonStates, 1000);

            console.log('Controles de historial (Undo/Redo) configurados correctamente.');
        }

        // -------------------------------------------------------------------
        // Sprint A3: Drag & Drop Polish
        // -------------------------------------------------------------------

        /**
         * Configura mejoras visuales de drag & drop.
         *
         * - Ghost element premium (CSS class toggle durante arrastre).
         * - Spring animation para componentes recién insertados.
         * - Toast de confirmación con nombre del bloque.
         *
         * @see docs/planificacion/20260209-Plan_Mejoras_Page_Site_Builder_v3.md §5
         */
        setupDragDropPolish() {
            const self = this;
            const editor = this.editor;

            // 1. Body class toggle al arrastrar (para CSS targeting global).
            editor.on('block:drag:start', () => {
                document.body.classList.add('jaraba-is-dragging');
            });

            editor.on('block:drag:stop', () => {
                document.body.classList.remove('jaraba-is-dragging');
            });

            // 2. Spring animation al añadir componente nuevo.
            editor.on('component:add', (component) => {
                // Delay breve para que el DOM renderice.
                setTimeout(() => {
                    try {
                        const el = component.getEl();
                        if (el && el.classList) {
                            el.classList.add('jaraba-component--just-added');
                            // Limpiar clase tras la animación (400ms).
                            setTimeout(() => {
                                el.classList.remove('jaraba-component--just-added');
                            }, 450);
                        }
                    } catch (e) {
                        // Silencioso si el componente no tiene elemento DOM.
                    }
                }, 50);
            });

            // 3. Toast de confirmación al soltar un bloque.
            editor.on('block:drag:stop', (component, block) => {
                if (!component || !block) return;

                const blockLabel = block.get('label') || block.get('id') || 'Bloque';
                this.showDropToast(blockLabel);
            });

            console.log('[Jaraba A3] Drag & Drop Polish configurado.');
        }

        /**
         * Muestra un toast de confirmación al insertar un bloque.
         *
         * @param {string} blockLabel Nombre del bloque insertado.
         */
        showDropToast(blockLabel) {
            // Eliminar toast anterior si existe.
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
                <span>${Drupal.t('@block añadido', { '@block': blockLabel })}</span>
            `;

            document.body.appendChild(toast);

            // Auto-remover tras la animación (1.5s).
            setTimeout(() => {
                toast.remove();
            }, 1600);
        }

        /**
         * Destruye el editor y limpia recursos.
         */
        destroy() {
            if (this.autoSaveTimeout) {
                clearTimeout(this.autoSaveTimeout);
            }
            if (this.editor) {
                this.editor.destroy();
            }
        }
    }

    /**
     * Drupal behavior para inicializar el Canvas Editor GrapesJS.
     */
    Drupal.behaviors.jarabaCanvasEditorGrapesJS = {
        attach: function (context) {
            const containers = once('jaraba-grapesjs-canvas', '#gjs-editor', context);

            containers.forEach((container) => {
                // Verificar que estamos en modo canvas
                if (drupalSettings.jarabaCanvas?.editorMode !== 'canvas') {
                    console.log('[Jaraba Canvas] Modo canvas desactivado. Usando editor legacy.');
                    return;
                }

                // Prevenir doble inicialización si el editor ya existe y está activo
                if (window.jarabaCanvasEditor && window.jarabaCanvasEditor.isInitialized) {
                    console.log('[Jaraba Canvas] Editor ya activo, omitiendo re-inicialización.');
                    return;
                }

                // Inicializar editor (el container se fuerza a '#gjs-editor' dentro del constructor)
                window.jarabaCanvasEditor = new JarabaCanvasEditor(container);
            });
        },
        detach: function (context, settings, trigger) {
            // CRITICAL FIX: Solo destruir el editor en unload real de la página.
            // Drupal puede llamar detach con trigger='serialize' o sin trigger
            // durante procesos AJAX, lo que destruiría el editor prematuramente.
            if (trigger === 'unload' && window.jarabaCanvasEditor) {
                // Verificar que el contexto contiene el editor (no un sub-contexto AJAX)
                const editorEl = context.querySelector ? context.querySelector('#gjs-editor') : null;
                if (editorEl || context === document) {
                    console.log('[Jaraba Canvas] Destruyendo editor (unload).');
                    window.jarabaCanvasEditor.destroy();
                    window.jarabaCanvasEditor = null;
                }
            }
        },
    };

})(Drupal, drupalSettings, once);

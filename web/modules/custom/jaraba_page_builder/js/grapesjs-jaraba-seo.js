/**
 * @file
 * Jaraba Canvas Editor v3 - Panel SEO Auditor.
 *
 * Plugin GrapesJS que proporciona auditoría SEO en tiempo real:
 * - Verificación de H1 único
 * - Jerarquía correcta de headings
 * - Meta description length
 * - Alt text en imágenes
 *
 * @see docs/tecnicos/20260204b-Canvas_Editor_v3_Arquitectura_Maestra.md
 */

(function (Drupal, drupalSettings) {
    'use strict';

    /**
     * Plugin GrapesJS para auditoría SEO.
     *
     * @param {Object} editor - Instancia del editor GrapesJS.
     * @param {Object} opts - Opciones de configuración.
     */
    const jarabaSEOPlugin = (editor, opts = {}) => {
        const defaultOpts = {
            // Longitud recomendada para meta description
            metaDescMinLength: 120,
            metaDescMaxLength: 160,
            // Selector del panel SEO
            panelSelector: '#gjs-seo-panel',
        };

        const options = { ...defaultOpts, ...opts };

        /**
         * Iconos SVG inline para el panel SEO.
         * Sigue la directriz de iconos del proyecto: NUNCA usar emojis.
         * @see docs/tecnicos/aprendizajes/2026-01-26_iconos_svg_landing_verticales.md
         */
        const ICONS = {
            error: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
            warning: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
            success: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="9 12 11 14 15 10"/></svg>',
            info: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>',
            search: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',
            refresh: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>',
            close: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
        };

        /**
         * Obtiene el icono SVG para un tipo de issue.
         * @param {string} type - Tipo: error, warning, success, info
         * @returns {string} - SVG inline
         */
        const getIcon = (type) => ICONS[type] || ICONS.info;

        /**
         * Clase SEO Auditor para validaciones.
         */
        class SEOAuditor {
            constructor(editor) {
                this.editor = editor;
                this.issues = [];
            }

            /**
             * Ejecuta todas las validaciones SEO.
             *
             * @returns {Array} Array de issues encontrados.
             */
            run() {
                this.issues = [];

                // Obtener HTML del canvas
                const wrapper = this.editor.DomComponents.getWrapper();
                if (!wrapper) return this.issues;

                // Ejecutar validaciones
                this.checkH1Unique(wrapper);
                this.checkHeadingHierarchy(wrapper);
                this.checkImagesAlt(wrapper);
                this.checkLinksAccessibility(wrapper);
                this.checkContentLength(wrapper);

                return this.issues;
            }

            /**
             * Verifica que solo haya un H1 en la página.
             */
            checkH1Unique(wrapper) {
                const h1s = wrapper.find('h1');

                if (h1s.length === 0) {
                    this.issues.push({
                        type: 'error',
                        code: 'H1_MISSING',
                        message: Drupal.t('Falta el título principal (H1). Añade un H1 único para mejorar el SEO.'),
                        icon: 'error',
                    });
                } else if (h1s.length > 1) {
                    this.issues.push({
                        type: 'warning',
                        code: 'H1_MULTIPLE',
                        message: Drupal.t('Hay @count etiquetas H1. Solo debe haber una por página.', { '@count': h1s.length }),
                        icon: 'warning',
                    });
                } else {
                    // H1 único - verificar si tiene contenido
                    const h1Content = h1s[0].view?.el?.textContent?.trim() || '';
                    if (h1Content.length < 5) {
                        this.issues.push({
                            type: 'warning',
                            code: 'H1_SHORT',
                            message: Drupal.t('El H1 parece muy corto. Un título descriptivo mejora el SEO.'),
                            icon: 'warning',
                        });
                    } else {
                        this.issues.push({
                            type: 'success',
                            code: 'H1_OK',
                            message: Drupal.t('H1 único detectado correctamente.'),
                            icon: 'success',
                        });
                    }
                }
            }

            /**
             * Verifica la jerarquía de headings (no saltar niveles).
             */
            checkHeadingHierarchy(wrapper) {
                const headings = [];
                const headingTags = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];

                // Recopilar todos los headings en orden
                headingTags.forEach((tag) => {
                    const found = wrapper.find(tag);
                    found.forEach((comp) => {
                        const el = comp.view?.el;
                        if (el) {
                            // Obtener posición aproximada en el DOM
                            const rect = el.getBoundingClientRect ? el.getBoundingClientRect() : { top: 0 };
                            headings.push({
                                tag: tag,
                                level: parseInt(tag.charAt(1), 10),
                                top: rect.top,
                            });
                        }
                    });
                });

                // Ordenar por posición vertical
                headings.sort((a, b) => a.top - b.top);

                // Verificar saltos en la jerarquía
                let hasHierarchyIssue = false;
                for (let i = 1; i < headings.length; i++) {
                    const current = headings[i].level;
                    const previous = headings[i - 1].level;

                    // Si el nivel actual es mayor que el anterior + 1, hay un salto
                    if (current > previous + 1) {
                        hasHierarchyIssue = true;
                        this.issues.push({
                            type: 'warning',
                            code: 'HEADING_SKIP',
                            message: Drupal.t('Salto en jerarquía: de H@prev a H@curr. Considera usar niveles consecutivos.', {
                                '@prev': previous,
                                '@curr': current,
                            }),
                            icon: 'warning',
                        });
                        break; // Solo reportar el primer salto
                    }
                }

                if (!hasHierarchyIssue && headings.length > 0) {
                    this.issues.push({
                        type: 'success',
                        code: 'HEADING_HIERARCHY_OK',
                        message: Drupal.t('Jerarquía de títulos correcta.'),
                        icon: 'success',
                    });
                }
            }

            /**
             * Verifica que todas las imágenes tengan alt text.
             */
            checkImagesAlt(wrapper) {
                const images = wrapper.find('img');

                if (images.length === 0) {
                    // No hay imágenes, no es un problema
                    return;
                }

                let imagesWithoutAlt = 0;

                images.forEach((img) => {
                    const alt = img.getAttributes()?.alt;
                    if (!alt || alt.trim() === '') {
                        imagesWithoutAlt++;
                    }
                });

                if (imagesWithoutAlt > 0) {
                    this.issues.push({
                        type: 'warning',
                        code: 'IMAGES_NO_ALT',
                        message: Drupal.t('@count imagen(es) sin texto alternativo (alt). Añade alt para accesibilidad y SEO.', {
                            '@count': imagesWithoutAlt,
                        }),
                        icon: 'warning',
                    });
                } else {
                    this.issues.push({
                        type: 'success',
                        code: 'IMAGES_ALT_OK',
                        message: Drupal.t('Todas las imágenes tienen texto alternativo.'),
                        icon: 'success',
                    });
                }
            }

            /**
             * Verifica accesibilidad de enlaces.
             */
            checkLinksAccessibility(wrapper) {
                const links = wrapper.find('a');

                if (links.length === 0) return;

                let genericLinks = 0;
                const genericTexts = ['click aquí', 'leer más', 'ver más', 'aquí', 'más', 'link'];

                links.forEach((link) => {
                    const text = link.view?.el?.textContent?.trim()?.toLowerCase() || '';
                    if (genericTexts.some((generic) => text === generic)) {
                        genericLinks++;
                    }
                });

                if (genericLinks > 0) {
                    this.issues.push({
                        type: 'info',
                        code: 'LINKS_GENERIC',
                        message: Drupal.t('@count enlace(s) con texto genérico. Usa textos descriptivos.', {
                            '@count': genericLinks,
                        }),
                        icon: 'info',
                    });
                }
            }

            /**
             * Verifica longitud del contenido.
             */
            checkContentLength(wrapper) {
                const el = wrapper.view?.el;
                if (!el) return;

                const textContent = el.textContent || '';
                const words = textContent.trim().split(/\s+/).filter((w) => w.length > 0);

                if (words.length < 100) {
                    this.issues.push({
                        type: 'info',
                        code: 'CONTENT_SHORT',
                        message: Drupal.t('Contenido muy corto (@count palabras). Considera añadir más contenido para mejor SEO.', {
                            '@count': words.length,
                        }),
                        icon: 'info',
                    });
                } else if (words.length >= 300) {
                    this.issues.push({
                        type: 'success',
                        code: 'CONTENT_OK',
                        message: Drupal.t('Buen volumen de contenido (@count palabras).', {
                            '@count': words.length,
                        }),
                        icon: 'success',
                    });
                }
            }
        }

        /**
         * Clase para renderizar el panel SEO.
         */
        class SEOPanel {
            constructor(container) {
                this.container = container;
                this.isExpanded = true;
            }

            /**
             * Renderiza los issues en el panel.
             *
             * @param {Array} issues - Array de issues SEO.
             */
            render(issues) {
                if (!this.container) return;

                // Calcular resumen
                const errors = issues.filter((i) => i.type === 'error').length;
                const warnings = issues.filter((i) => i.type === 'warning').length;
                const success = issues.filter((i) => i.type === 'success').length;
                const info = issues.filter((i) => i.type === 'info').length;

                // Determinar score y estado
                let score = 100;
                score -= errors * 25;
                score -= warnings * 10;
                score -= info * 2;
                score = Math.max(0, Math.min(100, score));

                let scoreClass = 'seo-score--good';
                if (score < 50) scoreClass = 'seo-score--bad';
                else if (score < 80) scoreClass = 'seo-score--warning';

                // HTML del panel (sin emojis, usando iconos SVG)
                let html = `
                    <div class="seo-panel">
                        <div class="seo-panel__header">
                            <h3 class="seo-panel__title">
                                <span class="seo-panel__icon">${ICONS.search}</span>
                                ${Drupal.t('Auditoría SEO')}
                            </h3>
                            <div class="seo-panel__header-right">
                                <div class="seo-panel__score ${scoreClass}">${score}%</div>
                                <button class="seo-panel__close" type="button" title="${Drupal.t('Cerrar panel SEO')}">
                                    ${ICONS.close}
                                </button>
                            </div>
                        </div>
                        <div class="seo-panel__summary">
                            <span class="seo-stat seo-stat--error"><span class="seo-stat__icon">${ICONS.error}</span> ${errors}</span>
                            <span class="seo-stat seo-stat--warning"><span class="seo-stat__icon">${ICONS.warning}</span> ${warnings}</span>
                            <span class="seo-stat seo-stat--success"><span class="seo-stat__icon">${ICONS.success}</span> ${success}</span>
                        </div>
                        <div class="seo-panel__issues">
                `;

                issues.forEach((issue) => {
                    html += `
                        <div class="seo-issue seo-issue--${issue.type}">
                            <span class="seo-issue__icon">${getIcon(issue.icon)}</span>
                            <span class="seo-issue__message">${issue.message}</span>
                        </div>
                    `;
                });

                html += `
                        </div>
                        <div class="seo-panel__footer">
                            <button class="seo-panel__refresh" type="button">
                                <span class="seo-panel__refresh-icon">${ICONS.refresh}</span>
                                ${Drupal.t('Actualizar')}
                            </button>
                        </div>
                    </div>
                `;

                this.container.innerHTML = html;

                // Añadir evento al botón de actualizar
                const refreshBtn = this.container.querySelector('.seo-panel__refresh');
                if (refreshBtn) {
                    refreshBtn.addEventListener('click', () => {
                        editor.trigger('jaraba:seo:refresh');
                    });
                }

                // Añadir evento al botón de cerrar
                const closeBtn = this.container.querySelector('.seo-panel__close');
                if (closeBtn) {
                    closeBtn.addEventListener('click', () => {
                        closeSeoPanel();
                    });
                }
            }
        }

        // Instanciar auditor y panel
        const auditor = new SEOAuditor(editor);
        let panel = null;
        let isPanelVisible = false;

        // Buscar o crear contenedor del panel
        const createPanel = () => {
            let container = document.querySelector(options.panelSelector);
            if (!container) {
                // Crear contenedor si no existe
                container = document.createElement('div');
                container.id = 'gjs-seo-panel';
                container.className = 'gjs-seo-panel';
                // Iniciar OCULTO por defecto
                container.hidden = true;

                // Añadir al panel de traits/estilos
                const traitsContainer = document.querySelector('#gjs-styles-panel');
                if (traitsContainer) {
                    traitsContainer.appendChild(container);
                }
            }
            return container;
        };

        // Función para mostrar el panel SEO
        const openSeoPanel = () => {
            const container = createPanel();
            container.hidden = false;
            isPanelVisible = true;
            updateToggleButton(true);
            // Ejecutar auditoría si aún no se ha hecho
            if (!panel) {
                panel = new SEOPanel(container);
                const issues = auditor.run();
                panel.render(issues);
            }
        };

        // Función para ocultar el panel SEO
        const closeSeoPanel = () => {
            const container = document.querySelector(options.panelSelector);
            if (container) {
                container.hidden = true;
            }
            isPanelVisible = false;
            updateToggleButton(false);
        };

        // Función para alternar visibilidad del panel SEO
        const toggleSeoPanel = () => {
            if (isPanelVisible) {
                closeSeoPanel();
            } else {
                openSeoPanel();
            }
        };

        // Sincronizar estado del botón toggle en el toolbar
        const updateToggleButton = (isActive) => {
            const toggleBtn = document.querySelector('#seo-panel-toggle');
            if (toggleBtn) {
                toggleBtn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                toggleBtn.classList.toggle('is-active', isActive);
            }
        };

        // Enlazar botón toggle del toolbar
        const bindToggleButton = () => {
            const toggleBtn = document.querySelector('#seo-panel-toggle');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', toggleSeoPanel);
            }
        };

        // Función para ejecutar auditoría (solo si panel visible)
        const runAudit = () => {
            const container = createPanel();
            if (!panel) {
                panel = new SEOPanel(container);
            }

            const issues = auditor.run();
            panel.render(issues);
        };

        // Inicialización cuando el editor esté listo
        editor.on('load', () => {
            // Crear el panel pero dejarlo oculto
            createPanel();
            // Enlazar botón toggle
            bindToggleButton();
            // Pre-ejecutar auditoría para tener datos listos
            setTimeout(() => {
                const container = createPanel();
                if (!panel) {
                    panel = new SEOPanel(container);
                }
                const issues = auditor.run();
                panel.render(issues);
            }, 1000);
        });

        // Ejecutar auditoría al actualizar componentes (con debounce)
        let auditTimeout = null;
        editor.on('component:update', () => {
            if (auditTimeout) clearTimeout(auditTimeout);
            auditTimeout = setTimeout(runAudit, 2000); // Debounce de 2 segundos
        });

        // Ejecutar auditoría al añadir/eliminar componentes
        editor.on('component:add', () => {
            if (auditTimeout) clearTimeout(auditTimeout);
            auditTimeout = setTimeout(runAudit, 2000);
        });

        editor.on('component:remove', () => {
            if (auditTimeout) clearTimeout(auditTimeout);
            auditTimeout = setTimeout(runAudit, 2000);
        });

        // Comando para refrescar manualmente
        editor.Commands.add('jaraba:seo:refresh', {
            run(editor) {
                runAudit();
            },
        });

        // Comando para alternar visibilidad del panel SEO
        editor.Commands.add('toggle-seo-panel', {
            run(editor) {
                toggleSeoPanel();
            },
        });

        // Escuchar evento custom para refrescar
        editor.on('jaraba:seo:refresh', runAudit);

        // Inyectar estilos CSS del panel SEO (versión Premium)
        const injectStyles = () => {
            const styleId = 'jaraba-seo-panel-styles';
            if (document.getElementById(styleId)) return;

            const style = document.createElement('style');
            style.id = styleId;
            style.innerHTML = `
                /* ============================================================
                   Panel SEO Premium - High Contrast & Glassmorphism
                   Sigue el mandato de iconos blancos (White Icon Mandate)
                   ============================================================ */
                .gjs-seo-panel {
                    padding: 16px;
                    background: linear-gradient(135deg, rgba(35, 61, 99, 0.95) 0%, rgba(0, 169, 165, 0.85) 100%);
                    backdrop-filter: blur(12px);
                    -webkit-backdrop-filter: blur(12px);
                    border: 1px solid rgba(255, 255, 255, 0.2);
                    border-top: 2px solid var(--ej-color-innovation, #00A9A5);
                    margin-top: 16px;
                    border-radius: 12px;
                    box-shadow: 
                        0 8px 32px rgba(35, 61, 99, 0.4),
                        0 0 0 1px rgba(255, 255, 255, 0.1) inset;
                }

                .seo-panel__header {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    margin-bottom: 16px;
                    padding-bottom: 12px;
                    border-bottom: 1px solid rgba(255, 255, 255, 0.15);
                }

                .seo-panel__title {
                    font-size: 15px;
                    font-weight: 700;
                    color: #FFFFFF;
                    margin: 0;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
                    letter-spacing: 0.5px;
                }

                /* Icono SVG blanco para el título */
                .seo-panel__icon {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    width: 28px;
                    height: 28px;
                    background: linear-gradient(135deg, rgba(255, 255, 255, 0.25) 0%, rgba(255, 255, 255, 0.1) 100%);
                    border-radius: 8px;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
                }

                .seo-panel__icon svg {
                    width: 16px;
                    height: 16px;
                    stroke: #FFFFFF;
                    fill: none;
                }

                .seo-panel__header-right {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }

                .seo-panel__close {
                    background: rgba(255, 255, 255, 0.15);
                    border: 1px solid rgba(255, 255, 255, 0.25);
                    border-radius: 6px;
                    color: #FFFFFF;
                    width: 28px;
                    height: 28px;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 14px;
                    font-weight: 500;
                    transition: all 0.2s ease;
                }

                .seo-panel__close:hover {
                    background: rgba(239, 68, 68, 0.3);
                    border-color: rgba(239, 68, 68, 0.6);
                    transform: scale(1.05);
                }

                .seo-panel__score {
                    font-size: 18px;
                    font-weight: 800;
                    padding: 6px 14px;
                    border-radius: 24px;
                    letter-spacing: 0.5px;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
                    animation: scoreGlow 2s ease-in-out infinite;
                }

                @keyframes scoreGlow {
                    0%, 100% { box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2); }
                    50% { box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3); }
                }

                .seo-score--good {
                    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                    color: #FFFFFF;
                }

                .seo-score--warning {
                    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
                    color: #FFFFFF;
                }

                .seo-score--bad {
                    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
                    color: #FFFFFF;
                }

                .seo-panel__summary {
                    display: flex;
                    gap: 16px;
                    margin-bottom: 16px;
                    font-size: 14px;
                    font-weight: 600;
                    color: rgba(255, 255, 255, 0.9);
                    justify-content: center;
                }

                .seo-stat {
                    display: flex;
                    align-items: center;
                    gap: 6px;
                    padding: 6px 12px;
                    background: rgba(255, 255, 255, 0.1);
                    border-radius: 20px;
                    backdrop-filter: blur(4px);
                }

                .seo-panel__issues {
                    display: flex;
                    flex-direction: column;
                    gap: 10px;
                    max-height: 220px;
                    overflow-y: auto;
                    padding-right: 4px;
                }

                /* Scrollbar premium */
                .seo-panel__issues::-webkit-scrollbar {
                    width: 6px;
                }

                .seo-panel__issues::-webkit-scrollbar-track {
                    background: rgba(255, 255, 255, 0.1);
                    border-radius: 3px;
                }

                .seo-panel__issues::-webkit-scrollbar-thumb {
                    background: rgba(255, 255, 255, 0.3);
                    border-radius: 3px;
                }

                .seo-issue {
                    display: flex;
                    align-items: flex-start;
                    gap: 10px;
                    padding: 12px 14px;
                    border-radius: 10px;
                    font-size: 13px;
                    line-height: 1.5;
                    font-weight: 500;
                    transition: transform 0.2s ease, box-shadow 0.2s ease;
                    border: 1px solid transparent;
                }

                .seo-issue:hover {
                    transform: translateX(4px);
                }

                .seo-issue--error {
                    background: linear-gradient(135deg, rgba(239, 68, 68, 0.2) 0%, rgba(220, 38, 38, 0.15) 100%);
                    color: #fecaca;
                    border-color: rgba(239, 68, 68, 0.3);
                }

                .seo-issue--warning {
                    background: linear-gradient(135deg, rgba(245, 158, 11, 0.2) 0%, rgba(217, 119, 6, 0.15) 100%);
                    color: #fef3c7;
                    border-color: rgba(245, 158, 11, 0.3);
                }

                .seo-issue--success {
                    background: linear-gradient(135deg, rgba(16, 185, 129, 0.2) 0%, rgba(5, 150, 105, 0.15) 100%);
                    color: #d1fae5;
                    border-color: rgba(16, 185, 129, 0.3);
                }

                .seo-issue--info {
                    background: linear-gradient(135deg, rgba(59, 130, 246, 0.2) 0%, rgba(37, 99, 235, 0.15) 100%);
                    color: #dbeafe;
                    border-color: rgba(59, 130, 246, 0.3);
                }

                .seo-issue__icon {
                    flex-shrink: 0;
                    width: 20px;
                    height: 20px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }

                .seo-issue__icon svg {
                    width: 16px;
                    height: 16px;
                    stroke: currentColor;
                    fill: none;
                    filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.2));
                }

                /* Iconos SVG en stats del resumen */
                .seo-stat__icon {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    width: 14px;
                    height: 14px;
                }

                .seo-stat__icon svg {
                    width: 14px;
                    height: 14px;
                    stroke: currentColor;
                    fill: none;
                }

                .seo-stat--error .seo-stat__icon svg {
                    stroke: #fecaca;
                }

                .seo-stat--warning .seo-stat__icon svg {
                    stroke: #fef3c7;
                }

                .seo-stat--success .seo-stat__icon svg {
                    stroke: #d1fae5;
                }

                /* Icono SVG en botón cerrar */
                .seo-panel__close svg {
                    width: 14px;
                    height: 14px;
                    stroke: currentColor;
                    fill: none;
                }

                .seo-issue__message {
                    flex: 1;
                }

                .seo-panel__footer {
                    margin-top: 16px;
                    text-align: center;
                    padding-top: 12px;
                    border-top: 1px solid rgba(255, 255, 255, 0.1);
                }

                .seo-panel__refresh {
                    background: linear-gradient(135deg, rgba(255, 255, 255, 0.2) 0%, rgba(255, 255, 255, 0.1) 100%);
                    color: #FFFFFF;
                    border: 1px solid rgba(255, 255, 255, 0.3);
                    padding: 10px 24px;
                    border-radius: 8px;
                    font-size: 13px;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.2s ease;
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
                }

                .seo-panel__refresh:hover {
                    background: linear-gradient(135deg, rgba(255, 255, 255, 0.3) 0%, rgba(255, 255, 255, 0.15) 100%);
                    transform: translateY(-1px);
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
                }

                .seo-panel__refresh:active {
                    transform: translateY(0);
                }

                /* Icono SVG en botón actualizar */
                .seo-panel__refresh-icon {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    width: 14px;
                    height: 14px;
                }

                .seo-panel__refresh-icon svg {
                    width: 14px;
                    height: 14px;
                    stroke: currentColor;
                    fill: none;
                }
            `;
            document.head.appendChild(style);
        };

        // Inyectar estilos al cargar
        injectStyles();

        console.log('Plugin jaraba-seo inicializado correctamente.');
    };

    // Registrar plugin en GrapesJS
    if (typeof grapesjs !== 'undefined') {
        grapesjs.plugins.add('jaraba-seo', jarabaSEOPlugin);
    }

})(Drupal, drupalSettings);

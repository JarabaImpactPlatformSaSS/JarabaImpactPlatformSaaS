/**
 * @file
 * Jaraba Canvas Editor v3 - Adaptador de Bloques.
 *
 * Registra los 67 bloques de contenido como componentes GrapesJS.
 * Cada bloque mantiene sus traits editables y schema JSON.
 *
 * @see docs/tecnicos/20260204b-Canvas_Editor_v3_Arquitectura_Maestra.md
 */

(function (Drupal, drupalSettings) {
    'use strict';

    /**
     * Plugin GrapesJS para registrar bloques Jaraba.
     *
     * @param {Object} editor - Instancia del editor GrapesJS.
     * @param {Object} opts - Opciones de configuración.
     */
    const jarabaBlocksPlugin = (editor, opts = {}) => {
        const domComponents = editor.DomComponents;
        const blockManager = editor.BlockManager;

        /**
         * Registra el tipo de componente genérico 'jaraba-block'.
         */
        domComponents.addType('jaraba-block', {
            // Extender el tipo 'default'
            extend: 'default',

            model: {
                defaults: {
                    tagName: 'section',
                    draggable: '[data-gjs-type=jaraba-content-zone]',
                    droppable: false,
                    copyable: true,
                    removable: true,
                    resizable: false,
                    // Clase base
                    classes: ['jaraba-block'],
                    // Traits dinámicos según schema
                    traits: [],
                    // Atributos data-*
                    attributes: {
                        'data-block-type': '',
                        'data-block-category': '',
                    },
                },

                init() {
                    // Cargar traits desde schema
                    this.loadTraitsFromSchema();
                    // Escuchar cambios en traits
                    this.on('change:attributes', this.onTraitChange);
                },

                /**
                 * Carga los traits editables desde el schema del bloque.
                 */
                loadTraitsFromSchema() {
                    const schemaStr = this.getAttributes()['data-block-schema'];
                    if (!schemaStr) return;

                    try {
                        const schema = JSON.parse(schemaStr);
                        const traits = [];

                        // Convertir schema a traits GrapesJS
                        Object.entries(schema).forEach(([key, config]) => {
                            traits.push(this.schemaToTrait(key, config));
                        });

                        this.set('traits', traits);
                    } catch (e) {
                        console.warn('Error parseando schema:', e);
                    }
                },

                /**
                 * Convierte una entrada del schema a un trait GrapesJS.
                 *
                 * @param {string} key - Nombre del campo.
                 * @param {Object} config - Configuración del campo.
                 * @returns {Object} Trait GrapesJS.
                 */
                schemaToTrait(key, config) {
                    const trait = {
                        name: key,
                        label: config.label || key,
                    };

                    switch (config.type) {
                        case 'text':
                        case 'string':
                            trait.type = 'text';
                            trait.placeholder = config.placeholder || '';
                            break;

                        case 'textarea':
                            trait.type = 'text';
                            trait.changeProp = 1;
                            break;

                        case 'select':
                            trait.type = 'select';
                            trait.options = config.options || [];
                            break;

                        case 'checkbox':
                        case 'boolean':
                            trait.type = 'checkbox';
                            break;

                        case 'color':
                            trait.type = 'color';
                            break;

                        case 'number':
                            trait.type = 'number';
                            trait.min = config.min || 0;
                            trait.max = config.max || 100;
                            break;

                        case 'file':
                        case 'image':
                            trait.type = 'text'; // Por ahora, URL manual
                            trait.placeholder = 'URL de la imagen';
                            break;

                        default:
                            trait.type = 'text';
                    }

                    return trait;
                },

                /**
                 * Maneja cambios en traits.
                 */
                onTraitChange() {
                    // Marcar como dirty para auto-save
                    this.em.trigger('change:changesCount');
                },
            },

            view: {
                events: {
                    dblclick: 'onDblClick',
                },

                onDblClick() {
                    // Abrir panel de edición del bloque
                    this.em.trigger('component:selected', this.model);
                },
            },
        });

        /**
         * Componente custom de navegación WORLD-CLASS.
         * Características:
         * - Iconos opcionales por enlace (usando iconos duotone del sistema)
         * - Soporte multinivel (hasta 2 niveles con dropdowns)
         * - Gestión visual de elementos
         * Sigue mejores prácticas GrapesJS: changeProp + listeners de propiedades.
         */
        domComponents.addType('jaraba-navigation', {
            extend: 'default',

            model: {
                defaults: {
                    tagName: 'nav',
                    draggable: true,
                    droppable: false,
                    copyable: true,
                    removable: true,
                    classes: ['jaraba-navigation', 'jaraba-nav-premium'],
                    // Propiedades del componente
                    itemCount: 4,
                    // Enlace 1
                    link1_text: Drupal.t('Inicio'),
                    link1_url: '#',
                    link1_icon: 'home',
                    link1_has_submenu: false,
                    link1_sub1_text: '',
                    link1_sub1_url: '',
                    link1_sub2_text: '',
                    link1_sub2_url: '',
                    // Enlace 2
                    link2_text: Drupal.t('Servicios'),
                    link2_url: '#servicios',
                    link2_icon: 'briefcase',
                    link2_has_submenu: true,
                    link2_sub1_text: Drupal.t('Consultoría'),
                    link2_sub1_url: '/consultoria',
                    link2_sub2_text: Drupal.t('Formación'),
                    link2_sub2_url: '/formacion',
                    // Enlace 3
                    link3_text: Drupal.t('Nosotros'),
                    link3_url: '#nosotros',
                    link3_icon: 'users',
                    link3_has_submenu: false,
                    link3_sub1_text: '',
                    link3_sub1_url: '',
                    link3_sub2_text: '',
                    link3_sub2_url: '',
                    // Enlace 4
                    link4_text: Drupal.t('Contacto'),
                    link4_url: '#contacto',
                    link4_icon: 'mail',
                    link4_has_submenu: false,
                    link4_sub1_text: '',
                    link4_sub1_url: '',
                    link4_sub2_text: '',
                    link4_sub2_url: '',
                    // Iconos disponibles para selector
                    iconOptions: [
                        { id: 'none', label: Drupal.t('Sin icono') },
                        { id: 'home', label: '🏠 ' + Drupal.t('Inicio') },
                        { id: 'briefcase', label: '💼 ' + Drupal.t('Servicios') },
                        { id: 'users', label: '👥 ' + Drupal.t('Nosotros') },
                        { id: 'mail', label: '✉️ ' + Drupal.t('Contacto') },
                        { id: 'phone', label: '📞 ' + Drupal.t('Teléfono') },
                        { id: 'shopping-cart', label: '🛒 ' + Drupal.t('Tienda') },
                        { id: 'book', label: '📖 ' + Drupal.t('Blog') },
                        { id: 'calendar', label: '📅 ' + Drupal.t('Eventos') },
                        { id: 'graduation', label: '🎓 ' + Drupal.t('Formación') },
                        { id: 'star', label: '⭐ ' + Drupal.t('Destacado') },
                    ],
                    // Traits estáticos, vinculados a propiedades
                    traits: [
                        {
                            type: 'number',
                            name: 'itemCount',
                            label: Drupal.t('Número de enlaces'),
                            min: 1,
                            max: 6,
                            changeProp: true,
                        },
                        // === ENLACE 1 ===
                        { type: 'text', name: 'link1_text', label: '📍 ' + Drupal.t('Enlace') + ' 1 - ' + Drupal.t('Texto'), changeProp: true },
                        { type: 'text', name: 'link1_url', label: Drupal.t('Enlace') + ' 1 - URL', changeProp: true, placeholder: '/' },
                        {
                            type: 'select', name: 'link1_icon', label: Drupal.t('Enlace') + ' 1 - ' + Drupal.t('Icono'), changeProp: true,
                            options: [
                                { id: 'none', label: Drupal.t('Sin icono') },
                                { id: 'home', label: '🏠 Inicio' },
                                { id: 'briefcase', label: '💼 Servicios' },
                                { id: 'users', label: '👥 Nosotros' },
                                { id: 'mail', label: '✉️ Contacto' },
                                { id: 'phone', label: '📞 Teléfono' },
                                { id: 'shopping-cart', label: '🛒 Tienda' },
                                { id: 'book', label: '📖 Blog' },
                                { id: 'calendar', label: '📅 Eventos' },
                            ],
                        },
                        { type: 'checkbox', name: 'link1_has_submenu', label: Drupal.t('Enlace') + ' 1 - ' + Drupal.t('Tiene submenú'), changeProp: true },
                        { type: 'text', name: 'link1_sub1_text', label: '↳ ' + Drupal.t('Subenlace') + ' 1.1', changeProp: true },
                        { type: 'text', name: 'link1_sub1_url', label: '↳ ' + Drupal.t('Subenlace') + ' 1.1 URL', changeProp: true },
                        { type: 'text', name: 'link1_sub2_text', label: '↳ ' + Drupal.t('Subenlace') + ' 1.2', changeProp: true },
                        { type: 'text', name: 'link1_sub2_url', label: '↳ ' + Drupal.t('Subenlace') + ' 1.2 URL', changeProp: true },
                        // === ENLACE 2 ===
                        { type: 'text', name: 'link2_text', label: '📍 ' + Drupal.t('Enlace') + ' 2 - ' + Drupal.t('Texto'), changeProp: true },
                        { type: 'text', name: 'link2_url', label: Drupal.t('Enlace') + ' 2 - URL', changeProp: true, placeholder: '/' },
                        {
                            type: 'select', name: 'link2_icon', label: Drupal.t('Enlace') + ' 2 - ' + Drupal.t('Icono'), changeProp: true,
                            options: [
                                { id: 'none', label: Drupal.t('Sin icono') },
                                { id: 'home', label: '🏠 Inicio' },
                                { id: 'briefcase', label: '💼 Servicios' },
                                { id: 'users', label: '👥 Nosotros' },
                                { id: 'mail', label: '✉️ Contacto' },
                                { id: 'phone', label: '📞 Teléfono' },
                                { id: 'shopping-cart', label: '🛒 Tienda' },
                                { id: 'book', label: '📖 Blog' },
                                { id: 'calendar', label: '📅 Eventos' },
                            ],
                        },
                        { type: 'checkbox', name: 'link2_has_submenu', label: Drupal.t('Enlace') + ' 2 - ' + Drupal.t('Tiene submenú'), changeProp: true },
                        { type: 'text', name: 'link2_sub1_text', label: '↳ ' + Drupal.t('Subenlace') + ' 2.1', changeProp: true },
                        { type: 'text', name: 'link2_sub1_url', label: '↳ ' + Drupal.t('Subenlace') + ' 2.1 URL', changeProp: true },
                        { type: 'text', name: 'link2_sub2_text', label: '↳ ' + Drupal.t('Subenlace') + ' 2.2', changeProp: true },
                        { type: 'text', name: 'link2_sub2_url', label: '↳ ' + Drupal.t('Subenlace') + ' 2.2 URL', changeProp: true },
                        // === ENLACE 3 ===
                        { type: 'text', name: 'link3_text', label: '📍 ' + Drupal.t('Enlace') + ' 3 - ' + Drupal.t('Texto'), changeProp: true },
                        { type: 'text', name: 'link3_url', label: Drupal.t('Enlace') + ' 3 - URL', changeProp: true, placeholder: '/' },
                        {
                            type: 'select', name: 'link3_icon', label: Drupal.t('Enlace') + ' 3 - ' + Drupal.t('Icono'), changeProp: true,
                            options: [
                                { id: 'none', label: Drupal.t('Sin icono') },
                                { id: 'home', label: '🏠 Inicio' },
                                { id: 'briefcase', label: '💼 Servicios' },
                                { id: 'users', label: '👥 Nosotros' },
                                { id: 'mail', label: '✉️ Contacto' },
                                { id: 'phone', label: '📞 Teléfono' },
                                { id: 'shopping-cart', label: '🛒 Tienda' },
                                { id: 'book', label: '📖 Blog' },
                                { id: 'calendar', label: '📅 Eventos' },
                            ],
                        },
                        { type: 'checkbox', name: 'link3_has_submenu', label: Drupal.t('Enlace') + ' 3 - ' + Drupal.t('Tiene submenú'), changeProp: true },
                        { type: 'text', name: 'link3_sub1_text', label: '↳ ' + Drupal.t('Subenlace') + ' 3.1', changeProp: true },
                        { type: 'text', name: 'link3_sub1_url', label: '↳ ' + Drupal.t('Subenlace') + ' 3.1 URL', changeProp: true },
                        { type: 'text', name: 'link3_sub2_text', label: '↳ ' + Drupal.t('Subenlace') + ' 3.2', changeProp: true },
                        { type: 'text', name: 'link3_sub2_url', label: '↳ ' + Drupal.t('Subenlace') + ' 3.2 URL', changeProp: true },
                        // === ENLACE 4 ===
                        { type: 'text', name: 'link4_text', label: '📍 ' + Drupal.t('Enlace') + ' 4 - ' + Drupal.t('Texto'), changeProp: true },
                        { type: 'text', name: 'link4_url', label: Drupal.t('Enlace') + ' 4 - URL', changeProp: true, placeholder: '/' },
                        {
                            type: 'select', name: 'link4_icon', label: Drupal.t('Enlace') + ' 4 - ' + Drupal.t('Icono'), changeProp: true,
                            options: [
                                { id: 'none', label: Drupal.t('Sin icono') },
                                { id: 'home', label: '🏠 Inicio' },
                                { id: 'briefcase', label: '💼 Servicios' },
                                { id: 'users', label: '👥 Nosotros' },
                                { id: 'mail', label: '✉️ Contacto' },
                                { id: 'phone', label: '📞 Teléfono' },
                                { id: 'shopping-cart', label: '🛒 Tienda' },
                                { id: 'book', label: '📖 Blog' },
                                { id: 'calendar', label: '📅 Eventos' },
                            ],
                        },
                        { type: 'checkbox', name: 'link4_has_submenu', label: Drupal.t('Enlace') + ' 4 - ' + Drupal.t('Tiene submenú'), changeProp: true },
                        { type: 'text', name: 'link4_sub1_text', label: '↳ ' + Drupal.t('Subenlace') + ' 4.1', changeProp: true },
                        { type: 'text', name: 'link4_sub1_url', label: '↳ ' + Drupal.t('Subenlace') + ' 4.1 URL', changeProp: true },
                        { type: 'text', name: 'link4_sub2_text', label: '↳ ' + Drupal.t('Subenlace') + ' 4.2', changeProp: true },
                        { type: 'text', name: 'link4_sub2_url', label: '↳ ' + Drupal.t('Subenlace') + ' 4.2 URL', changeProp: true },
                    ],
                    styles: `
                        display: flex;
                        gap: 1.5rem;
                        padding: 1rem;
                        font-family: var(--ej-font-family, 'Inter', sans-serif);
                        align-items: center;
                    `,
                },

                init() {
                    // Escuchar cambios en propiedades (patrón GrapesJS correcto)
                    this.on('change:itemCount', this.rebuildContent);
                    for (let i = 1; i <= 6; i++) {
                        this.on(`change:link${i}_text`, this.triggerRebuild);
                        this.on(`change:link${i}_url`, this.triggerRebuild);
                        this.on(`change:link${i}_icon`, this.triggerRebuild);
                        this.on(`change:link${i}_has_submenu`, this.triggerRebuild);
                        this.on(`change:link${i}_sub1_text`, this.triggerRebuild);
                        this.on(`change:link${i}_sub1_url`, this.triggerRebuild);
                        this.on(`change:link${i}_sub2_text`, this.triggerRebuild);
                        this.on(`change:link${i}_sub2_url`, this.triggerRebuild);
                    }
                },

                triggerRebuild() {
                    this.trigger('navigation:rebuild');
                },

                /**
                 * Genera el icono SVG inline basado en el nombre
                 */
                getIconSvg(iconName) {
                    const icons = {
                        'home': '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9,22 9,12 15,12 15,22"/></svg>',
                        'briefcase': '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>',
                        'users': '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
                        'mail': '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>',
                        'phone': '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>',
                        'shopping-cart': '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>',
                        'book': '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>',
                        'calendar': '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
                        'graduation': '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m22 9-10-4L2 9l10 4 10-4v6"/><path d="M6 10.6V16a6 3 0 0 0 12 0v-5.4"/></svg>',
                        'star': '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"/></svg>',
                    };
                    return icons[iconName] || '';
                },

                /**
                 * Genera el HTML de navegación con estilos inline.
                 * @returns {string} HTML string.
                 */
                getNavigationHtml() {
                    const count = this.get('itemCount') || 4;
                    let linksHtml = '';

                    // Estilos inline para consistencia
                    const linkStyle = 'display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1rem; color: var(--ej-text-primary, #1e293b); text-decoration: none; font-weight: 500; transition: color 0.2s;';
                    const iconStyle = 'display: flex; align-items: center;';
                    const itemStyle = 'position: relative;';
                    const submenuStyle = 'position: absolute; top: 100%; left: 0; background: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); padding: 0.5rem 0; min-width: 180px; opacity: 0; visibility: hidden; transform: translateY(-10px); transition: all 0.2s;';
                    const dropdownIconStyle = 'margin-left: 0.25rem;';

                    for (let i = 1; i <= count; i++) {
                        const text = this.get(`link${i}_text`) || `${Drupal.t('Enlace')} ${i}`;
                        const url = this.get(`link${i}_url`) || '#';
                        const iconName = this.get(`link${i}_icon`) || 'none';
                        const hasSubmenu = this.get(`link${i}_has_submenu`);

                        const iconHtml = iconName !== 'none' ? `<span class="jaraba-navigation__icon">${this.getIconSvg(iconName)}</span>` : '';

                        if (hasSubmenu) {
                            let submenuHtml = `<div class="jaraba-navigation__submenu">`;

                            for (let j = 1; j <= 2; j++) {
                                const subText = this.get(`link${i}_sub${j}_text`);
                                const subUrl = this.get(`link${i}_sub${j}_url`) || '#';
                                if (subText) {
                                    submenuHtml += `<a href="${subUrl}" class="jaraba-navigation__link">${subText}</a>`;
                                }
                            }
                            submenuHtml += '</div>';

                            linksHtml += `
                                <div class="jaraba-navigation__item">
                                    <a href="${url}" class="jaraba-navigation__link">
                                        ${iconHtml}${text}
                                        <svg class="jaraba-navigation__dropdown-icon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6,9 12,15 18,9"/></svg>
                                    </a>
                                    ${submenuHtml}
                                </div>`;
                        } else {
                            linksHtml += `<a href="${url}" class="jaraba-navigation__link">${iconHtml}${text}</a>`;
                        }
                    }
                    return linksHtml;
                },

                /**
                 * Reconstruye el contenido usando components() para serialización.
                 */
                rebuildContent() {
                    const html = this.getNavigationHtml();
                    this.components(html);
                    // Trigger view update for styling
                    this.trigger('navigation:styled');
                },
            },

            view: {
                init() {
                    this.listenTo(this.model, 'navigation:styled', this.applyInlineStyles);
                    this.listenTo(this.model, 'change:itemCount', this.onModelChange);
                },

                onRender() {
                    // Apply styles after GrapesJS renders components
                    setTimeout(() => this.applyInlineStyles(), 0);
                },

                onModelChange() {
                    // Trigger rebuild from model
                    this.model.rebuildContent();
                },

                /**
                 * Aplica estilos inline a los elementos DOM para consistencia visual.
                 */
                applyInlineStyles() {
                    const linkStyle = 'display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1rem; color: var(--ej-text-primary, #1e293b); text-decoration: none; font-weight: 500; transition: color 0.2s;';
                    const iconStyle = 'display: flex; align-items: center;';
                    const itemStyle = 'position: relative;';
                    const submenuStyle = 'position: absolute; top: 100%; left: 0; background: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); padding: 0.5rem 0; min-width: 180px; opacity: 0; visibility: hidden; transform: translateY(-10px); transition: all 0.2s; z-index: 100;';
                    const dropdownIconStyle = 'margin-left: 0.25rem;';

                    // Apply container styles
                    const containerStyle = this.model.get('styles');
                    if (containerStyle) {
                        this.el.setAttribute('style', containerStyle);
                    }

                    // Apply link styles
                    this.el.querySelectorAll('.jaraba-navigation__link').forEach(link => {
                        link.setAttribute('style', linkStyle);
                    });

                    // Apply icon styles
                    this.el.querySelectorAll('.jaraba-navigation__icon').forEach(icon => {
                        icon.setAttribute('style', iconStyle);
                    });

                    // Apply item styles
                    this.el.querySelectorAll('.jaraba-navigation__item').forEach(item => {
                        item.setAttribute('style', itemStyle);

                        // Add hover listeners for dropdowns
                        item.addEventListener('mouseenter', () => {
                            const dropdown = item.querySelector('.jaraba-navigation__submenu');
                            if (dropdown) {
                                dropdown.style.opacity = '1';
                                dropdown.style.visibility = 'visible';
                                dropdown.style.transform = 'translateY(0)';
                            }
                        });
                        item.addEventListener('mouseleave', () => {
                            const dropdown = item.querySelector('.jaraba-navigation__submenu');
                            if (dropdown) {
                                dropdown.style.opacity = '0';
                                dropdown.style.visibility = 'hidden';
                                dropdown.style.transform = 'translateY(-10px)';
                            }
                        });
                    });

                    // Apply submenu styles
                    this.el.querySelectorAll('.jaraba-navigation__submenu').forEach(submenu => {
                        submenu.setAttribute('style', submenuStyle);
                    });

                    // Apply dropdown icon styles
                    this.el.querySelectorAll('.jaraba-navigation__dropdown-icon').forEach(icon => {
                        icon.setAttribute('style', dropdownIconStyle);
                    });
                },
            },
        });

        /**
         * Componente custom de botón configurable.
         * Traits: texto, URL, estilo, target.
         */
        domComponents.addType('jaraba-button', {
            extend: 'link',

            model: {
                defaults: {
                    tagName: 'a',
                    draggable: true,
                    droppable: false,
                    copyable: true,
                    removable: true,
                    classes: ['jaraba-button'],
                    // Propiedades del botón
                    buttonText: Drupal.t('Llamada a Acción'),
                    buttonStyle: 'primary',
                    attributes: {
                        href: '#',
                        target: '_self',
                    },
                    traits: [
                        {
                            type: 'text',
                            name: 'buttonText',
                            label: Drupal.t('Texto del botón'),
                            changeProp: true,
                        },
                        {
                            type: 'text',
                            name: 'href',
                            label: 'URL',
                        },
                        {
                            type: 'select',
                            name: 'buttonStyle',
                            label: Drupal.t('Estilo'),
                            changeProp: true,
                            options: [
                                { id: 'primary', label: Drupal.t('Primario') },
                                { id: 'secondary', label: Drupal.t('Secundario') },
                                { id: 'outline', label: Drupal.t('Contorno') },
                            ],
                        },
                        {
                            type: 'select',
                            name: 'target',
                            label: Drupal.t('Abrir en'),
                            options: [
                                { id: '_self', label: Drupal.t('Misma ventana') },
                                { id: '_blank', label: Drupal.t('Nueva ventana') },
                            ],
                        },
                    ],
                },

                init() {
                    this.on('change:buttonText', this.updateButtonText);
                    this.on('change:buttonStyle', this.updateButtonStyle);
                    this.updateButtonText();
                    this.updateButtonStyle();
                },

                updateButtonText() {
                    const text = this.get('buttonText') || Drupal.t('Llamada a Acción');
                    this.components(text);
                },

                updateButtonStyle() {
                    const style = this.get('buttonStyle');
                    let css = 'display: inline-block; padding: 12px 24px; font-family: var(--ej-font-family, Inter, sans-serif); font-weight: 600; text-decoration: none; border-radius: 8px; transition: all 0.2s; cursor: pointer;';

                    switch (style) {
                        case 'primary':
                            css += 'background: var(--ej-color-impulse, #FF8C42); color: white; border: none;';
                            break;
                        case 'secondary':
                            css += 'background: var(--ej-color-corporate, #233D63); color: white; border: none;';
                            break;
                        case 'outline':
                            css += 'background: transparent; color: var(--ej-color-corporate, #233D63); border: 2px solid var(--ej-color-corporate, #233D63);';
                            break;
                    }

                    this.setStyle(css);
                },

                setStyle(css) {
                    this.addAttributes({ style: css });
                },
            },

            view: {
                onRender() {
                    this.model.updateButtonStyle();
                },
            },
        });

        /**
         * Componente FAQ Accordion con JavaScript interactivo.
         * Usa la propiedad 'script' de GrapesJS para inyectar JS en el HTML exportado.
         * @see https://grapesjs.com/docs/modules/Components-js.html
         */
        const faqScript = function () {
            // Script que se ejecuta en el canvas y en el HTML exportado
            const items = this.querySelectorAll('.jaraba-faq__item');
            items.forEach(function (item) {
                const button = item.querySelector('.jaraba-faq__toggle');
                const answer = item.querySelector('.jaraba-faq__answer');
                const icon = button ? button.querySelector('span') : null;

                if (button && answer) {
                    button.addEventListener('click', function () {
                        const isOpen = item.classList.toggle('jaraba-faq__item--open');
                        if (icon) {
                            icon.textContent = isOpen ? '−' : '+';
                        }
                        answer.style.maxHeight = isOpen ? answer.scrollHeight + 'px' : '0';
                    });
                }
            });
        };

        domComponents.addType('jaraba-faq', {
            extend: 'default',

            model: {
                defaults: {
                    tagName: 'div',
                    droppable: false,
                    copyable: true,
                    removable: true,
                    classes: ['jaraba-faq'],
                    // Propiedades de los FAQs - array de objetos {question, answer}
                    faqTitle: Drupal.t('Preguntas Frecuentes'),
                    faqItems: [
                        {
                            question: Drupal.t('¿Cómo empiezo a usar el servicio?'),
                            answer: Drupal.t('Regístrate gratis y comienza a explorar todas las funcionalidades. Nuestro equipo te guiará paso a paso.'),
                        },
                        {
                            question: Drupal.t('¿Cuáles son los planes de precios?'),
                            answer: Drupal.t('Ofrecemos planes flexibles adaptados a diferentes necesidades. Consulta nuestra página de precios.'),
                        },
                        {
                            question: Drupal.t('¿Ofrecen soporte técnico?'),
                            answer: Drupal.t('Sí, nuestro equipo de soporte está disponible 24/7 para ayudarte con cualquier consulta.'),
                        },
                    ],
                    // Script que se inyecta en el HTML exportado
                    script: faqScript,
                    // Estilos inline que se aplican al componente
                    styles: `
                        .jaraba-faq__answer {
                            max-height: 0;
                            overflow: hidden;
                            transition: max-height 0.3s ease-out;
                        }
                        .jaraba-faq__item--open .jaraba-faq__answer {
                            max-height: 500px;
                        }
                    `,
                    traits: [
                        {
                            type: 'text',
                            name: 'faqTitle',
                            label: Drupal.t('Título de la sección'),
                            changeProp: true,
                        },
                        {
                            type: 'number',
                            name: 'faqCount',
                            label: Drupal.t('Número de FAQs'),
                            default: 3,
                            min: 1,
                            max: 20,
                            changeProp: true,
                        },
                        {
                            type: 'button',
                            name: 'addFaq',
                            label: Drupal.t('➕ Añadir Pregunta'),
                            text: Drupal.t('Añadir FAQ'),
                            full: true,
                            command: 'jaraba:faq-add',
                        },
                        {
                            type: 'button',
                            name: 'editFaqContent',
                            label: Drupal.t('✏️ Editar Contenido'),
                            text: Drupal.t('Abrir Editor'),
                            full: true,
                            command: 'jaraba:faq-edit-modal',
                        },
                    ],
                },

                init() {
                    this.on('change:faqTitle', this.updateFaqContent);
                    this.on('change:faqItems', this.updateFaqContent);
                    this.on('change:faqCount', this.onFaqCountChange);
                    // Inicializar contenido
                    this.updateFaqContent();
                },

                /**
                 * Cuando cambia el número de FAQs, ajustar el array.
                 */
                onFaqCountChange() {
                    const count = parseInt(this.get('faqCount'), 10) || 3;
                    let items = [...(this.get('faqItems') || [])];

                    // Añadir items si falta
                    while (items.length < count) {
                        items.push({
                            question: Drupal.t('Nueva pregunta @num', { '@num': items.length + 1 }),
                            answer: Drupal.t('Escribe aquí la respuesta...'),
                        });
                    }

                    // Recortar si sobran
                    if (items.length > count) {
                        items = items.slice(0, count);
                    }

                    this.set('faqItems', items, { silent: true });
                    // Trigger view update
                    this.trigger('faqContent:change');
                },

                /**
                 * Genera el HTML del FAQ basándose en los datos.
                 * @returns {string} HTML string.
                 */
                getFaqHtml() {
                    const title = this.get('faqTitle') || Drupal.t('Preguntas Frecuentes');
                    const items = this.get('faqItems') || [];

                    let html = `<h2 style="text-align: center; color: var(--ej-text-primary, #1e293b); margin-bottom: 2rem;">${title}</h2>`;

                    items.forEach((item) => {
                        html += `
                            <div class="jaraba-faq__item" style="border-bottom: 1px solid var(--ej-border-color, #e2e8f0); padding: 1.25rem 0;">
                                <button class="jaraba-faq__toggle" style="display: flex; justify-content: space-between; width: 100%; background: none; border: none; cursor: pointer; font-size: 1.125rem; font-weight: 600; color: var(--ej-text-primary, #1e293b); text-align: left; padding: 0;">
                                    ${item.question}
                                    <span style="font-size: 1.5rem; line-height: 1; transition: transform 0.2s;">+</span>
                                </button>
                                <div class="jaraba-faq__answer" style="max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out;">
                                    <p style="color: var(--ej-text-muted, #64748b); margin: 0; padding-top: 1rem; line-height: 1.6;">${item.answer}</p>
                                </div>
                            </div>`;
                    });

                    return html;
                },

                /**
                 * Backward compatibility - still update visual when props change.
                 */
                updateFaqContent() {
                    this.trigger('faqContent:change');
                },
            },

            isComponent(el) {
                if (el.classList && el.classList.contains('jaraba-faq')) {
                    return { type: 'jaraba-faq' };
                }
            },

            view: {
                init() {
                    this.listenTo(this.model, 'faqContent:change', this.updateView);
                    this.listenTo(this.model, 'change:faqTitle', this.updateView);
                    this.listenTo(this.model, 'change:faqItems', this.updateView);
                },

                onRender() {
                    this.updateView();
                },

                /**
                 * Actualiza el innerHTML directamente para mantener estilos.
                 */
                updateView() {
                    const html = this.model.getFaqHtml();
                    this.el.innerHTML = html;
                    // Re-bind accordion events
                    faqScript.call(this.el);
                },
            },
        });

        /**
         * Comando: Añadir FAQ item.
         */
        editor.Commands.add('jaraba:faq-add', {
            run(editor) {
                const component = editor.getSelected();
                if (component && component.get('type') === 'jaraba-faq') {
                    const items = [...(component.get('faqItems') || [])];
                    items.push({
                        question: Drupal.t('Nueva pregunta'),
                        answer: Drupal.t('Escribe aquí la respuesta...'),
                    });
                    component.set('faqItems', items);
                    component.set('faqCount', items.length);

                    // Feedback visual
                    console.log('[FAQ] Añadida nueva pregunta. Total:', items.length);
                }
            },
        });

        /**
         * Comando: Abrir modal de edición de FAQ.
         */
        editor.Commands.add('jaraba:faq-edit-modal', {
            run(editor) {
                const component = editor.getSelected();
                if (!component || component.get('type') !== 'jaraba-faq') return;

                const items = component.get('faqItems') || [];

                // Crear el HTML del modal
                let formHtml = '<div style="max-height: 400px; overflow-y: auto; padding: 1rem;">';

                items.forEach((item, index) => {
                    formHtml += `
                        <div style="margin-bottom: 1.5rem; padding: 1rem; background: #f8f9fa; border-radius: 8px; border: 1px solid #dee2e6;">
                            <h4 style="margin: 0 0 0.5rem 0; color: #495057;">FAQ ${index + 1}</h4>
                            <div style="margin-bottom: 0.5rem;">
                                <label style="display: block; font-weight: 600; margin-bottom: 0.25rem;">❓ Pregunta:</label>
                                <input type="text" id="faq-q-${index}" value="${item.question.replace(/"/g, '&quot;')}" 
                                    style="width: 100%; padding: 0.5rem; border: 1px solid #ced4da; border-radius: 4px; box-sizing: border-box;">
                            </div>
                            <div>
                                <label style="display: block; font-weight: 600; margin-bottom: 0.25rem;">💬 Respuesta:</label>
                                <textarea id="faq-a-${index}" rows="3" 
                                    style="width: 100%; padding: 0.5rem; border: 1px solid #ced4da; border-radius: 4px; box-sizing: border-box; resize: vertical;">${item.answer}</textarea>
                            </div>
                            <button type="button" data-delete="${index}" 
                                style="margin-top: 0.5rem; background: #dc3545; color: white; border: none; padding: 0.25rem 0.5rem; border-radius: 4px; cursor: pointer;">
                                🗑️ Eliminar
                            </button>
                        </div>`;
                });

                formHtml += '</div>';

                // Crear el modal
                const modal = editor.Modal;
                modal.setTitle(Drupal.t('Editar Contenido FAQ'));
                modal.setContent(`
                    <div id="faq-editor-form">
                        ${formHtml}
                        <div style="padding: 1rem; border-top: 1px solid #dee2e6; text-align: right;">
                            <button type="button" id="faq-save-btn" 
                                style="background: var(--ej-color-impulse, #FF8C42); color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer; font-weight: 600;">
                                💾 Guardar Cambios
                            </button>
                        </div>
                    </div>
                `);
                modal.open();

                // Event handlers
                const modalContent = modal.getContentEl();

                // Save button
                modalContent.querySelector('#faq-save-btn').addEventListener('click', () => {
                    const newItems = [];
                    let i = 0;
                    let qEl = modalContent.querySelector(`#faq-q-${i}`);
                    while (qEl) {
                        const aEl = modalContent.querySelector(`#faq-a-${i}`);
                        newItems.push({
                            question: qEl.value,
                            answer: aEl.value,
                        });
                        i++;
                        qEl = modalContent.querySelector(`#faq-q-${i}`);
                    }

                    component.set('faqItems', newItems);
                    component.set('faqCount', newItems.length);
                    modal.close();
                    console.log('[FAQ] Guardados', newItems.length, 'items');
                });

                // Delete buttons
                modalContent.querySelectorAll('[data-delete]').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        const idx = parseInt(e.target.dataset.delete, 10);
                        const currentItems = [...(component.get('faqItems') || [])];
                        currentItems.splice(idx, 1);
                        component.set('faqItems', currentItems);
                        component.set('faqCount', currentItems.length);
                        // Re-open modal with updated content
                        editor.Commands.run('jaraba:faq-edit-modal');
                    });
                });
            },
        });

        // =====================================================================
        // DUAL ARCHITECTURE: Componentes Interactivos con script property
        // Cada componente tiene:
        //   1. Script function (se inyecta en HTML exportado)
        //   2. domComponents.addType con view.onRender
        //   3. Archivo Drupal.behaviors separado para páginas públicas
        // =====================================================================

        /**
         * Script: Contador de estadísticas con Intersection Observer.
         * Anima los números de 0 al valor final cuando el bloque es visible.
         * Usa función regular (no arrow) — `this` = elemento DOM del componente.
         *
         * @see docs/arquitectura/2026-02-08_plan_elevacion_page_builder_clase_mundial.md
         */
        const statsCounterScript = function () {
            const el = this;
            const counters = el.querySelectorAll('[data-target-value]');
            if (!counters.length) return;

            // Función de animación de conteo
            function animateCounter(counterEl) {
                const target = parseFloat(counterEl.getAttribute('data-target-value')) || 0;
                const suffix = counterEl.getAttribute('data-suffix') || '';
                const prefix = counterEl.getAttribute('data-prefix') || '';
                const duration = 2000; // 2 segundos
                const startTime = performance.now();

                function update(currentTime) {
                    const elapsed = currentTime - startTime;
                    const progress = Math.min(elapsed / duration, 1);
                    // Ease-out cubic para desaceleración natural
                    const eased = 1 - Math.pow(1 - progress, 3);
                    const current = Math.round(target * eased);
                    counterEl.textContent = prefix + current.toLocaleString() + suffix;

                    if (progress < 1) {
                        requestAnimationFrame(update);
                    }
                }
                requestAnimationFrame(update);
            }

            // Intersection Observer para trigger al scroll
            if (typeof IntersectionObserver !== 'undefined') {
                const observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            counters.forEach(animateCounter);
                            observer.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.3 });
                observer.observe(el);
            } else {
                // Fallback: animar inmediatamente
                counters.forEach(animateCounter);
            }
        };

        /**
         * Componente: Contador de Estadísticas con Intersection Observer.
         */
        domComponents.addType('jaraba-stats-counter', {
            extend: 'default',

            model: {
                defaults: {
                    tagName: 'section',
                    droppable: false,
                    copyable: true,
                    removable: true,
                    classes: ['jaraba-stats', 'jaraba-stats--counter'],
                    script: statsCounterScript,
                    // Model-level defaults para que this.get() funcione con changeProp
                    statsTitle: Drupal.t('Nuestro Impacto'),
                    stat1Value: 500,
                    stat1Label: Drupal.t('Clientes Satisfechos'),
                    stat1Suffix: '+',
                    stat2Value: 98,
                    stat2Label: Drupal.t('Tasa de Éxito'),
                    stat2Suffix: '%',
                    stat3Value: 24,
                    stat3Label: Drupal.t('Soporte Disponible'),
                    stat3Suffix: '/7',
                    stat4Value: 15,
                    stat4Label: Drupal.t('Años de Experiencia'),
                    stat4Suffix: '+',
                    traits: [
                        {
                            type: 'text',
                            name: 'statsTitle',
                            label: Drupal.t('Título del bloque'),
                            default: Drupal.t('Nuestro Impacto'),
                            changeProp: true,
                        },
                        {
                            type: 'number',
                            name: 'stat1Value',
                            label: Drupal.t('Valor estadística 1'),
                            default: 500,
                            changeProp: true,
                        },
                        {
                            type: 'text',
                            name: 'stat1Label',
                            label: Drupal.t('Etiqueta estadística 1'),
                            default: Drupal.t('Clientes Satisfechos'),
                            changeProp: true,
                        },
                        {
                            type: 'text',
                            name: 'stat1Suffix',
                            label: Drupal.t('Sufijo (ej: +, %)'),
                            default: '+',
                            changeProp: true,
                        },
                        {
                            type: 'number',
                            name: 'stat2Value',
                            label: Drupal.t('Valor estadística 2'),
                            default: 98,
                            changeProp: true,
                        },
                        {
                            type: 'text',
                            name: 'stat2Label',
                            label: Drupal.t('Etiqueta estadística 2'),
                            default: Drupal.t('Tasa de Éxito'),
                            changeProp: true,
                        },
                        {
                            type: 'text',
                            name: 'stat2Suffix',
                            label: Drupal.t('Sufijo 2'),
                            default: '%',
                            changeProp: true,
                        },
                        {
                            type: 'number',
                            name: 'stat3Value',
                            label: Drupal.t('Valor estadística 3'),
                            default: 24,
                            changeProp: true,
                        },
                        {
                            type: 'text',
                            name: 'stat3Label',
                            label: Drupal.t('Etiqueta estadística 3'),
                            default: Drupal.t('Soporte Disponible'),
                            changeProp: true,
                        },
                        {
                            type: 'text',
                            name: 'stat3Suffix',
                            label: Drupal.t('Sufijo 3'),
                            default: '/7',
                            changeProp: true,
                        },
                        {
                            type: 'number',
                            name: 'stat4Value',
                            label: Drupal.t('Valor estadística 4'),
                            default: 15,
                            changeProp: true,
                        },
                        {
                            type: 'text',
                            name: 'stat4Label',
                            label: Drupal.t('Etiqueta estadística 4'),
                            default: Drupal.t('Años de Experiencia'),
                            changeProp: true,
                        },
                        {
                            type: 'text',
                            name: 'stat4Suffix',
                            label: Drupal.t('Sufijo 4'),
                            default: '+',
                            changeProp: true,
                        },
                    ],
                },

                init() {
                    // Escuchar cambios en todos los traits
                    this.on('change:statsTitle', this.updateContent);
                    for (let i = 1; i <= 4; i++) {
                        this.on(`change:stat${i}Value`, this.updateContent);
                        this.on(`change:stat${i}Label`, this.updateContent);
                        this.on(`change:stat${i}Suffix`, this.updateContent);
                    }
                    this.updateContent();
                },

                /**
                 * Genera el HTML del bloque de estadísticas.
                 * @returns {string} HTML string.
                 */
                getStatsHtml() {
                    const colors = [
                        'var(--ej-color-corporate, #233D63)',
                        'var(--ej-color-innovation, #00A9A5)',
                        'var(--ej-color-impulse, #FF8C42)',
                        'var(--ej-color-danger, #E63946)',
                    ];
                    const title = this.get('statsTitle') || '';
                    let html = '';
                    if (title) {
                        html += `<h2 style="text-align: center; font-size: 2rem; color: var(--ej-text-primary, #1e293b); margin-bottom: 2rem;">${title}</h2>`;
                    }
                    html += '<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 2rem; max-width: 1000px; margin: 0 auto; text-align: center;">';

                    for (let i = 1; i <= 4; i++) {
                        const value = this.get(`stat${i}Value`) || 0;
                        const label = this.get(`stat${i}Label`) || '';
                        const suffix = this.get(`stat${i}Suffix`) || '';

                        html += `<div>
                            <span class="jaraba-stats__number" data-target-value="${value}" data-suffix="${suffix}" style="font-size: 3rem; font-weight: 800; color: ${colors[i - 1]}; display: block;">0${suffix}</span>
                            <span style="display: block; margin-top: 0.5rem; color: var(--ej-text-muted, #64748b); font-size: 0.9rem; font-weight: 500;">${label}</span>
                        </div>`;
                    }

                    html += '</div>';
                    return html;
                },

                updateContent() {
                    this.trigger('statsContent:change');
                },
            },

            isComponent(el) {
                if (el.classList && el.classList.contains('jaraba-stats--counter')) {
                    return { type: 'jaraba-stats-counter' };
                }
            },

            view: {
                init() {
                    this.listenTo(this.model, 'statsContent:change', this.updateView);
                },
                onRender() {
                    this.updateView();
                },
                updateView() {
                    const html = this.model.getStatsHtml();
                    this.el.innerHTML = html;
                    this.el.style.background = 'var(--ej-bg-secondary, #f8fafc)';
                    this.el.style.padding = '4rem 2rem';
                    // Ejecutar script de conteo en el editor
                    statsCounterScript.call(this.el);
                },
            },
        });

        /**
         * Script: Toggle de precios Mensual/Anual.
         * Alterna la visualización de precios entre mensual y anual.
         */
        const pricingToggleScript = function () {
            const el = this;
            const options = el.querySelectorAll('.jaraba-pricing-toggle__option');
            const indicator = el.querySelector('.jaraba-pricing-toggle__indicator');

            options.forEach(function (option, index) {
                option.addEventListener('click', function () {
                    // Actualizar estados activos
                    options.forEach(function (opt) {
                        opt.classList.remove('jaraba-pricing-toggle__option--active');
                        opt.style.color = 'var(--ej-text-muted, #64748b)';
                        opt.style.background = 'transparent';
                    });
                    option.classList.add('jaraba-pricing-toggle__option--active');
                    option.style.color = 'white';
                    option.style.background = 'var(--ej-color-corporate, #233D63)';

                    // Emitir evento custom para que otros componentes reaccionen
                    const period = option.getAttribute('data-period');
                    el.dispatchEvent(new CustomEvent('jaraba:pricing-change', {
                        bubbles: true,
                        detail: { period: period },
                    }));
                });
            });
        };

        /**
         * Componente: Toggle de precios Mensual/Anual.
         */
        domComponents.addType('jaraba-pricing-toggle', {
            extend: 'default',

            model: {
                defaults: {
                    tagName: 'div',
                    droppable: false,
                    copyable: true,
                    removable: true,
                    classes: ['jaraba-pricing', 'jaraba-pricing--toggle'],
                    script: pricingToggleScript,
                    monthlyLabel: Drupal.t('Mensual'),
                    annualLabel: Drupal.t('Anual'),
                    discountText: '-20%',
                    savingsText: Drupal.t('Ahorra 2 meses con el plan anual'),
                    traits: [
                        {
                            type: 'text',
                            name: 'monthlyLabel',
                            label: Drupal.t('Texto mensual'),
                            changeProp: true,
                        },
                        {
                            type: 'text',
                            name: 'annualLabel',
                            label: Drupal.t('Texto anual'),
                            changeProp: true,
                        },
                        {
                            type: 'text',
                            name: 'discountText',
                            label: Drupal.t('Texto de descuento'),
                            changeProp: true,
                        },
                        {
                            type: 'text',
                            name: 'savingsText',
                            label: Drupal.t('Texto ahorro'),
                            changeProp: true,
                        },
                    ],
                },

                init() {
                    this.on('change:monthlyLabel', this.updateContent);
                    this.on('change:annualLabel', this.updateContent);
                    this.on('change:discountText', this.updateContent);
                    this.on('change:savingsText', this.updateContent);
                    this.updateContent();
                },

                getToggleHtml() {
                    const monthly = this.get('monthlyLabel') || Drupal.t('Mensual');
                    const annual = this.get('annualLabel') || Drupal.t('Anual');
                    const discount = this.get('discountText') || '-20%';
                    const savings = this.get('savingsText') || '';

                    return `
                        <div style="display: inline-flex; align-items: center; gap: 0.5rem; background: var(--ej-bg-secondary, #f1f5f9); padding: 0.375rem; border-radius: 50px; margin-bottom: 1rem;" role="tablist" aria-label="${Drupal.t('Seleccionar periodo de facturación')}">
                            <span class="jaraba-pricing-toggle__option jaraba-pricing-toggle__option--active" data-period="monthly" role="tab" aria-selected="true" tabindex="0" style="padding: 0.75rem 1.5rem; background: var(--ej-color-corporate, #233D63); color: white; border-radius: 50px; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">${monthly}</span>
                            <span class="jaraba-pricing-toggle__option" data-period="annual" role="tab" aria-selected="false" tabindex="0" style="padding: 0.75rem 1.5rem; color: var(--ej-text-muted, #64748b); cursor: pointer; border-radius: 50px; transition: all 0.3s ease;">${annual} <span style="background: var(--ej-color-innovation, #00A9A5); color: white; padding: 0.125rem 0.5rem; border-radius: 10px; font-size: 0.7rem; margin-left: 0.25rem;">${discount}</span></span>
                        </div>
                        <p style="color: var(--ej-text-muted, #64748b); font-size: 0.875rem; margin: 0;">${savings}</p>`;
                },

                updateContent() {
                    this.trigger('toggleContent:change');
                },
            },

            isComponent(el) {
                if (el.classList && el.classList.contains('jaraba-pricing--toggle')) {
                    return { type: 'jaraba-pricing-toggle' };
                }
            },

            view: {
                init() {
                    this.listenTo(this.model, 'toggleContent:change', this.updateView);
                },
                onRender() {
                    this.updateView();
                },
                updateView() {
                    const html = this.model.getToggleHtml();
                    this.el.innerHTML = html;
                    this.el.style.textAlign = 'center';
                    this.el.style.padding = '2rem';
                    pricingToggleScript.call(this.el);
                },
            },
        });

        /**
         * Script: Navegación por pestañas.
         * Muestra/oculta paneles de contenido según la pestaña activa.
         */
        const tabsScript = function () {
            const el = this;
            const tabs = el.querySelectorAll('[role="tab"]');
            const panels = el.querySelectorAll('[role="tabpanel"]');

            tabs.forEach(function (tab) {
                tab.addEventListener('click', function () {
                    const targetId = tab.getAttribute('aria-controls');

                    // Desactivar todas las pestañas
                    tabs.forEach(function (t) {
                        t.classList.remove('jaraba-tabs__tab--active');
                        t.setAttribute('aria-selected', 'false');
                        t.style.borderBottom = '3px solid transparent';
                        t.style.color = 'var(--ej-text-muted, #64748b)';
                    });

                    // Ocultar todos los paneles
                    panels.forEach(function (p) {
                        p.style.display = 'none';
                        p.setAttribute('aria-hidden', 'true');
                    });

                    // Activar la pestaña clickeada
                    tab.classList.add('jaraba-tabs__tab--active');
                    tab.setAttribute('aria-selected', 'true');
                    tab.style.borderBottom = '3px solid var(--ej-color-corporate, #233D63)';
                    tab.style.color = 'var(--ej-text-primary, #1e293b)';

                    // Mostrar el panel correspondiente
                    var targetPanel = el.querySelector('#' + targetId);
                    if (targetPanel) {
                        targetPanel.style.display = 'block';
                        targetPanel.setAttribute('aria-hidden', 'false');
                    }
                });
            });
        };

        /**
         * Componente: Pestañas de contenido.
         */
        domComponents.addType('jaraba-tabs', {
            extend: 'default',

            model: {
                defaults: {
                    tagName: 'div',
                    droppable: false,
                    copyable: true,
                    removable: true,
                    classes: ['jaraba-tabs'],
                    script: tabsScript,
                    tabCount: 3,
                    tab1Title: Drupal.t('Características'),
                    tab1Content: Drupal.t('Descubre todas las características de nuestra plataforma diseñadas para impulsar tu crecimiento.'),
                    tab2Title: Drupal.t('Beneficios'),
                    tab2Content: Drupal.t('Maximiza tu productividad con herramientas inteligentes y automatizaciones.'),
                    tab3Title: Drupal.t('Casos de Éxito'),
                    tab3Content: Drupal.t('Más de 500 empresas ya confían en nosotros para transformar su negocio.'),
                    traits: [
                        {
                            type: 'text', name: 'tab1Title', label: Drupal.t('Título pestaña 1'), changeProp: true,
                        },
                        {
                            type: 'text', name: 'tab1Content', label: Drupal.t('Contenido pestaña 1'), changeProp: true,
                        },
                        {
                            type: 'text', name: 'tab2Title', label: Drupal.t('Título pestaña 2'), changeProp: true,
                        },
                        {
                            type: 'text', name: 'tab2Content', label: Drupal.t('Contenido pestaña 2'), changeProp: true,
                        },
                        {
                            type: 'text', name: 'tab3Title', label: Drupal.t('Título pestaña 3'), changeProp: true,
                        },
                        {
                            type: 'text', name: 'tab3Content', label: Drupal.t('Contenido pestaña 3'), changeProp: true,
                        },
                    ],
                },

                init() {
                    for (let i = 1; i <= 3; i++) {
                        this.on(`change:tab${i}Title`, this.updateContent);
                        this.on(`change:tab${i}Content`, this.updateContent);
                    }
                    this.updateContent();
                },

                getTabsHtml() {
                    let tabsHtml = '<div class="jaraba-tabs__nav" role="tablist" style="display: flex; gap: 0; border-bottom: 1px solid var(--ej-border-color, #e2e8f0); margin-bottom: 2rem;">';
                    let panelsHtml = '';

                    for (let i = 1; i <= 3; i++) {
                        const title = this.get(`tab${i}Title`) || `Tab ${i}`;
                        const content = this.get(`tab${i}Content`) || '';
                        const isActive = i === 1;
                        const panelId = `jaraba-tab-panel-${i}`;

                        tabsHtml += `<button class="jaraba-tabs__tab${isActive ? ' jaraba-tabs__tab--active' : ''}" role="tab" aria-selected="${isActive}" aria-controls="${panelId}" tabindex="${isActive ? '0' : '-1'}" style="padding: 1rem 1.5rem; background: none; border: none; cursor: pointer; font-weight: 600; font-size: 1rem; border-bottom: 3px solid ${isActive ? 'var(--ej-color-corporate, #233D63)' : 'transparent'}; color: ${isActive ? 'var(--ej-text-primary, #1e293b)' : 'var(--ej-text-muted, #64748b)'}; transition: all 0.2s;">${title}</button>`;

                        panelsHtml += `<div id="${panelId}" class="jaraba-tabs__panel" role="tabpanel" aria-hidden="${!isActive}" style="display: ${isActive ? 'block' : 'none'}; padding: 1rem 0;">
                            <p style="color: var(--ej-text-primary, #1e293b); font-size: 1.1rem; line-height: 1.7; margin: 0;">${content}</p>
                        </div>`;
                    }

                    tabsHtml += '</div>';
                    return tabsHtml + panelsHtml;
                },

                updateContent() {
                    this.trigger('tabsContent:change');
                },
            },

            isComponent(el) {
                if (el.classList && el.classList.contains('jaraba-tabs')) {
                    return { type: 'jaraba-tabs' };
                }
            },

            view: {
                init() {
                    this.listenTo(this.model, 'tabsContent:change', this.updateView);
                },
                onRender() {
                    this.updateView();
                },
                updateView() {
                    const html = this.model.getTabsHtml();
                    this.el.innerHTML = html;
                    this.el.style.maxWidth = '800px';
                    this.el.style.margin = '0 auto';
                    this.el.style.padding = '2rem';
                    tabsScript.call(this.el);
                },
            },
        });

        /**
         * Script: Temporizador de cuenta regresiva.
         * Actualiza cada segundo mostrando días, horas, minutos y segundos.
         */
        const countdownScript = function () {
            const el = this;
            const endDateStr = el.getAttribute('data-end-date');
            if (!endDateStr) return;

            var daysEl = el.querySelector('[data-unit="days"]');
            var hoursEl = el.querySelector('[data-unit="hours"]');
            var minutesEl = el.querySelector('[data-unit="minutes"]');
            var secondsEl = el.querySelector('[data-unit="seconds"]');

            function updateCountdown() {
                var endDate = new Date(endDateStr).getTime();
                var now = new Date().getTime();
                var diff = endDate - now;

                if (diff <= 0) {
                    if (daysEl) daysEl.textContent = '0';
                    if (hoursEl) hoursEl.textContent = '0';
                    if (minutesEl) minutesEl.textContent = '0';
                    if (secondsEl) secondsEl.textContent = '0';
                    return;
                }

                var days = Math.floor(diff / (1000 * 60 * 60 * 24));
                var hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                var minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                var seconds = Math.floor((diff % (1000 * 60)) / 1000);

                if (daysEl) daysEl.textContent = String(days).padStart(2, '0');
                if (hoursEl) hoursEl.textContent = String(hours).padStart(2, '0');
                if (minutesEl) minutesEl.textContent = String(minutes).padStart(2, '0');
                if (secondsEl) secondsEl.textContent = String(seconds).padStart(2, '0');
            }

            updateCountdown();
            setInterval(updateCountdown, 1000);
        };

        /**
         * Componente: Temporizador de Cuenta Regresiva.
         */
        domComponents.addType('jaraba-countdown', {
            extend: 'default',

            model: {
                defaults: {
                    tagName: 'div',
                    droppable: false,
                    copyable: true,
                    removable: true,
                    classes: ['jaraba-countdown'],
                    script: countdownScript,
                    countdownTitle: Drupal.t('La oferta termina en'),
                    // Fecha por defecto: 7 días desde ahora
                    endDate: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
                    traits: [
                        {
                            type: 'text',
                            name: 'countdownTitle',
                            label: Drupal.t('Título'),
                            changeProp: true,
                        },
                        {
                            type: 'text',
                            name: 'endDate',
                            label: Drupal.t('Fecha fin (YYYY-MM-DD)'),
                            placeholder: '2026-12-31',
                            changeProp: true,
                        },
                    ],
                },

                init() {
                    this.on('change:countdownTitle', this.updateContent);
                    this.on('change:endDate', this.updateContent);
                    this.updateContent();
                },

                getCountdownHtml() {
                    const title = this.get('countdownTitle') || '';
                    const endDate = this.get('endDate') || '';
                    const units = [
                        { key: 'days', label: Drupal.t('Días') },
                        { key: 'hours', label: Drupal.t('Horas') },
                        { key: 'minutes', label: Drupal.t('Minutos') },
                        { key: 'seconds', label: Drupal.t('Segundos') },
                    ];

                    let html = `<h3 style="text-align: center; color: white; margin-bottom: 2rem; font-size: 1.5rem; font-weight: 700;">${title}</h3>`;
                    html += '<div style="display: flex; justify-content: center; gap: 1.5rem; flex-wrap: wrap;">';

                    units.forEach(function (unit) {
                        html += `<div style="text-align: center;">
                            <span data-unit="${unit.key}" style="display: block; font-size: 3.5rem; font-weight: 800; color: white; background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); border-radius: 16px; padding: 1rem 1.5rem; min-width: 90px; line-height: 1;">00</span>
                            <span style="display: block; margin-top: 0.5rem; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 2px; color: rgba(255,255,255,0.8);">${unit.label}</span>
                        </div>`;
                    });

                    html += '</div>';
                    return html;
                },

                updateContent() {
                    this.trigger('countdownContent:change');
                },
            },

            isComponent(el) {
                if (el.classList && el.classList.contains('jaraba-countdown')) {
                    return { type: 'jaraba-countdown' };
                }
            },

            view: {
                init() {
                    this.listenTo(this.model, 'countdownContent:change', this.updateView);
                },
                onRender() {
                    this.updateView();
                },
                updateView() {
                    const html = this.model.getCountdownHtml();
                    this.el.innerHTML = html;
                    this.el.setAttribute('data-end-date', this.model.get('endDate') || '');
                    this.el.style.background = 'linear-gradient(135deg, var(--ej-color-corporate, #233D63) 0%, var(--ej-color-innovation, #00A9A5) 100%)';
                    this.el.style.padding = '4rem 2rem';
                    this.el.style.borderRadius = '20px';
                    countdownScript.call(this.el);
                },
            },
        });

        /**
         * Script: Timeline con animación scroll-triggered.
         * Anima los ítems del timeline al hacerse visibles con efecto staggered.
         */
        const timelineScript = function () {
            const el = this;
            const items = el.querySelectorAll('.jaraba-timeline__item');
            if (!items.length) return;

            // Establecer estado inicial: ocultos
            items.forEach(function (item) {
                item.style.opacity = '0';
                item.style.transform = 'translateY(30px)';
                item.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            });

            if (typeof IntersectionObserver !== 'undefined') {
                var delay = 0;
                var observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            var item = entry.target;
                            var itemDelay = parseInt(item.getAttribute('data-delay') || '0', 10);
                            setTimeout(function () {
                                item.style.opacity = '1';
                                item.style.transform = 'translateY(0)';
                            }, itemDelay);
                            observer.unobserve(item);
                        }
                    });
                }, { threshold: 0.2 });

                items.forEach(function (item, index) {
                    item.setAttribute('data-delay', String(index * 200));
                    observer.observe(item);
                });
            } else {
                // Fallback: mostrar todo
                items.forEach(function (item) {
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                });
            }
        };

        /**
         * Componente: Timeline con animación escalonada.
         */
        domComponents.addType('jaraba-timeline', {
            extend: 'default',

            model: {
                defaults: {
                    tagName: 'div',
                    droppable: false,
                    copyable: true,
                    removable: true,
                    classes: ['jaraba-timeline'],
                    script: timelineScript,
                    timelineTitle: Drupal.t('Nuestro Recorrido'),
                    item1Year: '2020',
                    item1Title: Drupal.t('Fundación'),
                    item1Desc: Drupal.t('Nace nuestra visión de transformar el ecosistema digital.'),
                    item2Year: '2022',
                    item2Title: Drupal.t('Expansión'),
                    item2Desc: Drupal.t('Alcanzamos 100 clientes en 5 países.'),
                    item3Year: '2024',
                    item3Title: Drupal.t('Innovación IA'),
                    item3Desc: Drupal.t('Lanzamos nuestra plataforma con inteligencia artificial integrada.'),
                    item4Year: '2026',
                    item4Title: Drupal.t('Clase Mundial'),
                    item4Desc: Drupal.t('Más de 500 clientes confían en nosotros.'),
                    traits: [
                        { type: 'text', name: 'timelineTitle', label: Drupal.t('Título'), changeProp: true },
                        { type: 'text', name: 'item1Year', label: Drupal.t('Año 1'), changeProp: true },
                        { type: 'text', name: 'item1Title', label: Drupal.t('Título 1'), changeProp: true },
                        { type: 'text', name: 'item1Desc', label: Drupal.t('Descripción 1'), changeProp: true },
                        { type: 'text', name: 'item2Year', label: Drupal.t('Año 2'), changeProp: true },
                        { type: 'text', name: 'item2Title', label: Drupal.t('Título 2'), changeProp: true },
                        { type: 'text', name: 'item2Desc', label: Drupal.t('Descripción 2'), changeProp: true },
                        { type: 'text', name: 'item3Year', label: Drupal.t('Año 3'), changeProp: true },
                        { type: 'text', name: 'item3Title', label: Drupal.t('Título 3'), changeProp: true },
                        { type: 'text', name: 'item3Desc', label: Drupal.t('Descripción 3'), changeProp: true },
                        { type: 'text', name: 'item4Year', label: Drupal.t('Año 4'), changeProp: true },
                        { type: 'text', name: 'item4Title', label: Drupal.t('Título 4'), changeProp: true },
                        { type: 'text', name: 'item4Desc', label: Drupal.t('Descripción 4'), changeProp: true },
                    ],
                },

                init() {
                    for (let i = 1; i <= 4; i++) {
                        this.on(`change:item${i}Year`, this.updateContent);
                        this.on(`change:item${i}Title`, this.updateContent);
                        this.on(`change:item${i}Desc`, this.updateContent);
                    }
                    this.on('change:timelineTitle', this.updateContent);
                    this.updateContent();
                },

                getTimelineHtml() {
                    const title = this.get('timelineTitle') || '';
                    let html = `<h2 style="text-align: center; color: var(--ej-text-primary, #1e293b); margin-bottom: 3rem; font-size: 2rem;">${title}</h2>`;
                    html += '<div class="jaraba-timeline__track" style="position: relative; max-width: 700px; margin: 0 auto; padding-left: 2rem;">';

                    // Línea vertical
                    html += '<div style="position: absolute; left: 0; top: 0; bottom: 0; width: 3px; background: linear-gradient(180deg, var(--ej-color-corporate, #233D63) 0%, var(--ej-color-innovation, #00A9A5) 100%); border-radius: 2px;"></div>';

                    for (let i = 1; i <= 4; i++) {
                        const year = this.get(`item${i}Year`) || '';
                        const itemTitle = this.get(`item${i}Title`) || '';
                        const desc = this.get(`item${i}Desc`) || '';

                        html += `<div class="jaraba-timeline__item" style="position: relative; padding: 0 0 2.5rem 2rem;">
                            <span style="display: inline-block; background: var(--ej-bg-secondary, #f1f5f9); color: var(--ej-color-corporate, #233D63); padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.8rem; font-weight: 700; margin-bottom: 0.5rem;">${year}</span>
                            <h4 style="color: var(--ej-text-primary, #1e293b); margin: 0.5rem 0 0.25rem; font-size: 1.125rem;">${itemTitle}</h4>
                            <p style="color: var(--ej-text-muted, #64748b); margin: 0; line-height: 1.6; font-size: 0.95rem;">${desc}</p>
                        </div>`;
                    }

                    html += '</div>';
                    return html;
                },

                updateContent() {
                    this.trigger('timelineContent:change');
                },
            },

            isComponent(el) {
                if (el.classList && el.classList.contains('jaraba-timeline')) {
                    return { type: 'jaraba-timeline' };
                }
            },

            view: {
                init() {
                    this.listenTo(this.model, 'timelineContent:change', this.updateView);
                },
                onRender() {
                    this.updateView();
                },
                updateView() {
                    const html = this.model.getTimelineHtml();
                    this.el.innerHTML = html;
                    this.el.style.padding = '4rem 2rem';
                    // Ejecutar animación en el editor
                    timelineScript.call(this.el);
                },
            },
        });

        /**
         * Registra categorías de bloques.
         * 'Básico' primero para fácil acceso a elementos de tipografía.
         *
         * ═══════════════════════════════════════════════════════════════
         * Sprint PB-4: Component Types con Traits Configurables
         * Commerce, Social, Contact, Pricing
         * ═══════════════════════════════════════════════════════════════
         */

        /**
         * Componente: Product Card con traits configurables.
         */
        domComponents.addType('jaraba-product-card', {
            extend: 'default',
            model: {
                defaults: {
                    tagName: 'div',
                    droppable: false,
                    copyable: true,
                    removable: true,
                    classes: ['jaraba-product'],
                    productName: Drupal.t('Nombre del Producto'),
                    productPrice: '49.00',
                    productBadge: Drupal.t('NUEVO'),
                    productDesc: Drupal.t('Descripción breve del producto destacado.'),
                    productBuyUrl: '#',
                    productCurrency: '€',
                    traits: [
                        { type: 'text', name: 'productName', label: Drupal.t('Nombre') },
                        { type: 'text', name: 'productPrice', label: Drupal.t('Precio') },
                        { type: 'text', name: 'productCurrency', label: Drupal.t('Moneda') },
                        { type: 'text', name: 'productBadge', label: Drupal.t('Etiqueta') },
                        { type: 'text', name: 'productDesc', label: Drupal.t('Descripción') },
                        { type: 'text', name: 'productBuyUrl', label: Drupal.t('URL Compra') },
                    ],
                },
                getProductHtml() {
                    const name = this.get('productName');
                    const price = this.get('productPrice');
                    const currency = this.get('productCurrency');
                    const badge = this.get('productBadge');
                    const desc = this.get('productDesc');
                    const buyUrl = this.get('productBuyUrl');
                    return '<div style="aspect-ratio: 4/3; background: linear-gradient(135deg, var(--ej-bg-secondary, #f1f5f9) 0%, var(--ej-border-color, #e2e8f0) 100%); display: flex; align-items: center; justify-content: center;"><span style="font-size: 3rem; opacity: 0.5;">📦</span></div>' +
                        '<div style="padding: 1.5rem;">' +
                        '<span style="background: var(--ej-color-innovation, #00A9A5); color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.7rem; font-weight: 600;">' + badge + '</span>' +
                        '<h4 style="color: var(--ej-text-primary, #1e293b); margin: 0.75rem 0 0.5rem; font-size: 1.125rem;">' + name + '</h4>' +
                        '<p style="color: var(--ej-text-muted, #64748b); font-size: 0.9rem; margin-bottom: 1rem;">' + desc + '</p>' +
                        '<div style="display: flex; align-items: center; justify-content: space-between;">' +
                        '<span style="font-size: 1.5rem; font-weight: 800; color: var(--ej-color-corporate, #233D63);">' + currency + price + '</span>' +
                        '<a href="' + buyUrl + '" style="background: var(--ej-color-impulse, #FF8C42); color: white; padding: 0.5rem 1.25rem; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.85rem;">' + Drupal.t('Comprar') + '</a>' +
                        '</div></div>';
                },
                init() {
                    this.on('change:productName change:productPrice change:productCurrency change:productBadge change:productDesc change:productBuyUrl', function () {
                        this.trigger('productContent:change');
                    });
                },
            },
            isComponent(el) {
                if (el.classList && el.classList.contains('jaraba-product')) {
                    return { type: 'jaraba-product-card' };
                }
            },
            view: {
                init() { this.listenTo(this.model, 'productContent:change', this.updateView); },
                onRender() { this.updateView(); },
                updateView() {
                    this.el.innerHTML = this.model.getProductHtml();
                    Object.assign(this.el.style, { background: 'white', borderRadius: '16px', overflow: 'hidden', boxShadow: '0 4px 15px rgba(0,0,0,0.08)', maxWidth: '320px' });
                },
            },
        });

        /**
         * Componente: Social Links con URLs configurables.
         */
        domComponents.addType('jaraba-social-links', {
            extend: 'default',
            model: {
                defaults: {
                    tagName: 'div',
                    droppable: false,
                    copyable: true,
                    removable: true,
                    classes: ['jaraba-social'],
                    facebookUrl: 'https://facebook.com',
                    twitterUrl: 'https://x.com',
                    instagramUrl: 'https://instagram.com',
                    linkedinUrl: 'https://linkedin.com',
                    youtubeUrl: 'https://youtube.com',
                    traits: [
                        { type: 'text', name: 'facebookUrl', label: Drupal.t('Facebook URL') },
                        { type: 'text', name: 'twitterUrl', label: Drupal.t('X / Twitter URL') },
                        { type: 'text', name: 'instagramUrl', label: Drupal.t('Instagram URL') },
                        { type: 'text', name: 'linkedinUrl', label: Drupal.t('LinkedIn URL') },
                        { type: 'text', name: 'youtubeUrl', label: Drupal.t('YouTube URL') },
                    ],
                },
                getSocialHtml() {
                    var ls = 'width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; text-decoration: none; font-weight: 700;';
                    return '<a href="' + this.get('facebookUrl') + '" target="_blank" rel="noopener" style="' + ls + ' background: #1877f2;">f</a>' +
                        '<a href="' + this.get('twitterUrl') + '" target="_blank" rel="noopener" style="' + ls + ' background: #1da1f2;">X</a>' +
                        '<a href="' + this.get('instagramUrl') + '" target="_blank" rel="noopener" style="' + ls + ' background: linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888);">IG</a>' +
                        '<a href="' + this.get('linkedinUrl') + '" target="_blank" rel="noopener" style="' + ls + ' background: #0077b5;">in</a>' +
                        '<a href="' + this.get('youtubeUrl') + '" target="_blank" rel="noopener" style="' + ls + ' background: #ff0000;">YT</a>';
                },
                init() {
                    this.on('change:facebookUrl change:twitterUrl change:instagramUrl change:linkedinUrl change:youtubeUrl', function () {
                        this.trigger('socialContent:change');
                    });
                },
            },
            isComponent(el) {
                if (el.classList && el.classList.contains('jaraba-social')) {
                    return { type: 'jaraba-social-links' };
                }
            },
            view: {
                init() { this.listenTo(this.model, 'socialContent:change', this.updateView); },
                onRender() { this.updateView(); },
                updateView() {
                    this.el.innerHTML = this.model.getSocialHtml();
                    Object.assign(this.el.style, { display: 'flex', justifyContent: 'center', gap: '1rem', padding: '2rem' });
                },
            },
        });

        /**
         * Componente: Contact Form con traits configurables.
         */
        domComponents.addType('jaraba-contact-form', {
            extend: 'default',
            model: {
                defaults: {
                    tagName: 'section',
                    droppable: false,
                    copyable: true,
                    removable: true,
                    classes: ['jaraba-contact', 'jaraba-contact--form'],
                    contactTitle: Drupal.t('Contáctanos'),
                    contactSubtitle: Drupal.t('Te responderemos en menos de 24 horas'),
                    contactEmail: 'contacto@ejemplo.com',
                    showName: true,
                    showPhone: false,
                    buttonText: Drupal.t('Enviar Mensaje'),
                    traits: [
                        { type: 'text', name: 'contactTitle', label: Drupal.t('Título') },
                        { type: 'text', name: 'contactSubtitle', label: Drupal.t('Subtítulo') },
                        { type: 'text', name: 'contactEmail', label: Drupal.t('Email destino') },
                        { type: 'checkbox', name: 'showName', label: Drupal.t('Campo Nombre') },
                        { type: 'checkbox', name: 'showPhone', label: Drupal.t('Campo Teléfono') },
                        { type: 'text', name: 'buttonText', label: Drupal.t('Texto botón') },
                    ],
                },
                getFormHtml() {
                    var is = 'padding: 1rem; border: 1px solid var(--ej-border-color, #e2e8f0); border-radius: 10px; font-size: 1rem; outline: none;';
                    var fields = '';
                    if (this.get('showName')) {
                        fields += '<input type="text" placeholder="' + Drupal.t('Nombre completo') + '" style="' + is + '"/>';
                    }
                    fields += '<input type="email" placeholder="' + Drupal.t('Correo electrónico') + '" style="' + is + '"/>';
                    if (this.get('showPhone')) {
                        fields += '<input type="tel" placeholder="' + Drupal.t('Teléfono') + '" style="' + is + '"/>';
                    }
                    fields += '<textarea rows="4" placeholder="' + Drupal.t('Tu mensaje...') + '" style="' + is + ' resize: vertical;"></textarea>';
                    return '<h3 style="color: var(--ej-text-primary, #1e293b); margin-bottom: 0.5rem; text-align: center;">' + this.get('contactTitle') + '</h3>' +
                        '<p style="color: var(--ej-text-muted, #64748b); text-align: center; margin-bottom: 2rem; font-size: 0.9rem;">' + this.get('contactSubtitle') + '</p>' +
                        '<form style="display: flex; flex-direction: column; gap: 1rem;">' + fields +
                        '<button type="button" style="background: linear-gradient(135deg, var(--ej-color-corporate, #233D63) 0%, var(--ej-color-innovation, #00A9A5) 100%); color: white; padding: 1rem; border-radius: 10px; border: none; font-size: 1rem; font-weight: 600; cursor: pointer;">' + this.get('buttonText') + '</button>' +
                        '</form>';
                },
                init() {
                    this.on('change:contactTitle change:contactSubtitle change:contactEmail change:showName change:showPhone change:buttonText', function () {
                        this.trigger('formContent:change');
                    });
                },
            },
            isComponent(el) {
                if (el.classList && el.classList.contains('jaraba-contact--form')) {
                    return { type: 'jaraba-contact-form' };
                }
            },
            view: {
                init() { this.listenTo(this.model, 'formContent:change', this.updateView); },
                onRender() { this.updateView(); },
                updateView() {
                    this.el.innerHTML = this.model.getFormHtml();
                    Object.assign(this.el.style, { background: 'white', borderRadius: '20px', padding: '3rem', maxWidth: '500px', boxShadow: '0 10px 40px rgba(0,0,0,0.08)' });
                },
            },
        });

        /**
         * Componente: Pricing Table con traits configurables para planes.
         */
        domComponents.addType('jaraba-pricing-table', {
            extend: 'default',
            model: {
                defaults: {
                    tagName: 'section',
                    droppable: false,
                    copyable: true,
                    removable: true,
                    classes: ['jaraba-pricing-table'],
                    plan1Name: Drupal.t('Básico'),
                    plan1Price: '19',
                    plan1Features: Drupal.t('5 usuarios, 10GB, Soporte email'),
                    plan2Name: Drupal.t('Profesional'),
                    plan2Price: '49',
                    plan2Features: Drupal.t('25 usuarios, 100GB, Soporte prioritario, API'),
                    plan2Featured: true,
                    plan3Name: Drupal.t('Enterprise'),
                    plan3Price: '99',
                    plan3Features: Drupal.t('Ilimitado, 1TB, Soporte 24/7, API, SLA'),
                    pricingCurrency: '€',
                    pricingPeriod: Drupal.t('/mes'),
                    traits: [
                        { type: 'text', name: 'pricingCurrency', label: Drupal.t('Moneda') },
                        { type: 'text', name: 'pricingPeriod', label: Drupal.t('Período') },
                        { type: 'text', name: 'plan1Name', label: Drupal.t('Plan 1 - Nombre') },
                        { type: 'text', name: 'plan1Price', label: Drupal.t('Plan 1 - Precio') },
                        { type: 'text', name: 'plan1Features', label: Drupal.t('Plan 1 - Features (coma)') },
                        { type: 'text', name: 'plan2Name', label: Drupal.t('Plan 2 - Nombre') },
                        { type: 'text', name: 'plan2Price', label: Drupal.t('Plan 2 - Precio') },
                        { type: 'text', name: 'plan2Features', label: Drupal.t('Plan 2 - Features (coma)') },
                        { type: 'checkbox', name: 'plan2Featured', label: Drupal.t('Plan 2 - Destacado') },
                        { type: 'text', name: 'plan3Name', label: Drupal.t('Plan 3 - Nombre') },
                        { type: 'text', name: 'plan3Price', label: Drupal.t('Plan 3 - Precio') },
                        { type: 'text', name: 'plan3Features', label: Drupal.t('Plan 3 - Features (coma)') },
                    ],
                },
                getPlanCardHtml(name, price, features, isFeatured) {
                    var currency = this.get('pricingCurrency');
                    var period = this.get('pricingPeriod');
                    var featureList = features.split(',').map(function (f) { return f.trim(); }).filter(Boolean);
                    var bg = isFeatured
                        ? 'background: linear-gradient(135deg, var(--ej-color-corporate, #233D63) 0%, var(--ej-color-innovation, #00A9A5) 100%); color: white; transform: scale(1.05);'
                        : 'background: white; color: var(--ej-text-primary, #1e293b);';
                    var textMuted = isFeatured ? 'opacity: 0.8;' : 'color: var(--ej-text-muted, #64748b);';
                    var btnStyle = isFeatured
                        ? 'background: white; color: var(--ej-color-corporate, #233D63);'
                        : 'background: var(--ej-color-corporate, #233D63); color: white;';
                    var badge = isFeatured ? '<span style="background: var(--ej-color-impulse, #FF8C42); color: white; padding: 0.25rem 1rem; border-radius: 20px; font-size: 0.7rem; font-weight: 600;">' + Drupal.t('POPULAR') + '</span>' : '';
                    var separator = isFeatured ? 'rgba(255,255,255,0.2)' : 'var(--ej-border-color, #e2e8f0)';
                    var featuresHtml = featureList.map(function (f) {
                        return '<li style="padding: 0.5rem 0; border-bottom: 1px solid ' + separator + '; display: flex; align-items: center; gap: 0.5rem;"><span>✓</span> ' + f + '</li>';
                    }).join('');

                    return '<div style="' + bg + ' border-radius: 16px; padding: 2rem; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.1); flex: 1; min-width: 250px;">' +
                        badge +
                        '<h4 style="font-size: 1.25rem; margin: 1rem 0 0.5rem;">' + name + '</h4>' +
                        '<div style="margin-bottom: 1.5rem;"><span style="font-size: 2.5rem; font-weight: 800;">' + currency + price + '</span><span style="' + textMuted + ' font-size: 0.9rem;">' + period + '</span></div>' +
                        '<ul style="list-style: none; padding: 0; margin: 0 0 2rem; text-align: left;">' + featuresHtml + '</ul>' +
                        '<a href="#" style="' + btnStyle + ' padding: 0.75rem 2rem; border-radius: 10px; text-decoration: none; font-weight: 600; display: inline-block;">' + Drupal.t('Elegir Plan') + '</a>' +
                        '</div>';
                },
                getPricingHtml() {
                    return this.getPlanCardHtml(this.get('plan1Name'), this.get('plan1Price'), this.get('plan1Features'), false) +
                        this.getPlanCardHtml(this.get('plan2Name'), this.get('plan2Price'), this.get('plan2Features'), this.get('plan2Featured')) +
                        this.getPlanCardHtml(this.get('plan3Name'), this.get('plan3Price'), this.get('plan3Features'), false);
                },
                init() {
                    this.on('change:plan1Name change:plan1Price change:plan1Features change:plan2Name change:plan2Price change:plan2Features change:plan2Featured change:plan3Name change:plan3Price change:plan3Features change:pricingCurrency change:pricingPeriod', function () {
                        this.trigger('pricingContent:change');
                    });
                },
            },
            isComponent(el) {
                if (el.classList && el.classList.contains('jaraba-pricing-table')) {
                    return { type: 'jaraba-pricing-table' };
                }
            },
            view: {
                init() { this.listenTo(this.model, 'pricingContent:change', this.updateView); },
                onRender() { this.updateView(); },
                updateView() {
                    this.el.innerHTML = this.model.getPricingHtml();
                    Object.assign(this.el.style, { display: 'flex', gap: '2rem', padding: '2rem', justifyContent: 'center', alignItems: 'flex-start', flexWrap: 'wrap' });
                },
            },
        });

        /**
         * Registra categorías de bloques.
         * 'Básico' primero para fácil acceso a elementos de tipografía.
         */
        const categories = [
            { id: 'basic', label: Drupal.t('Básico'), open: true },
            { id: 'hero', label: Drupal.t('Hero'), open: false },
            { id: 'features', label: Drupal.t('Características'), open: false },
            { id: 'cta', label: Drupal.t('Llamadas a Acción'), open: false },
            { id: 'stats', label: Drupal.t('Estadísticas'), open: false },
            { id: 'testimonials', label: Drupal.t('Testimonios'), open: false },
            { id: 'pricing', label: Drupal.t('Precios'), open: false },
            { id: 'team', label: Drupal.t('Equipo'), open: false },
            { id: 'faq', label: Drupal.t('FAQ'), open: false },
            { id: 'tabs', label: Drupal.t('Pestañas'), open: false },
            { id: 'countdown', label: Drupal.t('Cuenta Regresiva'), open: false },
            { id: 'timeline', label: Drupal.t('Timeline'), open: false },
            { id: 'contact', label: Drupal.t('Contacto'), open: false },
            { id: 'content', label: Drupal.t('Contenido'), open: false },
            { id: 'media', label: Drupal.t('Multimedia'), open: false },
            { id: 'premium', label: Drupal.t('Premium'), open: false },
            { id: 'commerce', label: Drupal.t('Comercio'), open: false },
            { id: 'social', label: Drupal.t('Social'), open: false },
            { id: 'advanced', label: Drupal.t('Avanzado'), open: false },
            { id: 'utilities', label: Drupal.t('Utilidades'), open: false },
        ];

        categories.forEach((cat) => {
            blockManager.getCategories().add(cat);
        });

        /**
         * Registra bloques básicos de tipografía.
         * Usan variables CSS del tema para respetar la configuración del usuario.
         */
        const basicBlocks = [
            {
                id: 'heading-h1',
                label: Drupal.t('H1 Título'),
                category: 'basic',
                content: `<h1 style="font-family: var(--ej-font-family, 'Outfit', sans-serif); font-size: var(--ej-h1-size, 2.5rem); font-weight: 700; color: var(--ej-text-primary, #1e293b); margin-bottom: 1rem;">${Drupal.t('Título Principal')}</h1>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 12h8M4 18V6M12 18V6M17 10l3 2-3 2M20 18V6"/>
                </svg>`,
            },
            {
                id: 'heading-h2',
                label: Drupal.t('H2 Subtítulo'),
                category: 'basic',
                content: `<h2 style="font-family: var(--ej-font-family, 'Outfit', sans-serif); font-size: var(--ej-h2-size, 2rem); font-weight: 600; color: var(--ej-text-primary, #1e293b); margin-bottom: 0.75rem;">${Drupal.t('Subtítulo de Sección')}</h2>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 12h8M4 18V6M12 18V6"/>
                    <path d="M21 18h-4c0-4 4-3 4-6 0-1.5-2-2.5-4-1"/>
                </svg>`,
            },
            {
                id: 'heading-h3',
                label: Drupal.t('H3 Encabezado'),
                category: 'basic',
                content: `<h3 style="font-family: var(--ej-font-family, 'Outfit', sans-serif); font-size: var(--ej-h3-size, 1.5rem); font-weight: 600; color: var(--ej-text-primary, #1e293b); margin-bottom: 0.5rem;">${Drupal.t('Encabezado Terciario')}</h3>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 12h8M4 18V6M12 18V6"/>
                    <path d="M17.5 10.5c1.7-1 3.5 0 3.5 1.5a2 2 0 01-2 2m2 0a2 2 0 01-2 2c-1.7 0-3.5-1-3.5-2"/>
                </svg>`,
            },
            {
                id: 'heading-h4',
                label: Drupal.t('H4 Título Menor'),
                category: 'basic',
                content: `<h4 style="font-family: var(--ej-font-family, 'Outfit', sans-serif); font-size: var(--ej-h4-size, 1.25rem); font-weight: 600; color: var(--ej-text-primary, #1e293b); margin-bottom: 0.5rem;">${Drupal.t('Título Cuaternario')}</h4>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 12h8M4 18V6M12 18V6"/>
                    <path d="M17 10v4h4M21 10v8"/>
                </svg>`,
            },
            {
                id: 'paragraph',
                label: Drupal.t('Párrafo'),
                category: 'basic',
                content: `<p style="font-family: var(--ej-font-family, 'Inter', sans-serif); font-size: 1rem; line-height: 1.6; color: var(--ej-text-muted, #475569); margin-bottom: 1rem;">${Drupal.t('Este es un párrafo de texto. Haz doble clic para editarlo.')}</p>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 6h16M4 10h16M4 14h10"/>
                </svg>`,
            },
            {
                id: 'text-block',
                label: Drupal.t('Bloque de Texto'),
                category: 'basic',
                content: `<div style="font-family: var(--ej-font-family, 'Inter', sans-serif); color: var(--ej-text-muted, #475569); padding: 1rem;">
                    <p style="margin-bottom: 0.75rem;">${Drupal.t('Primer párrafo del bloque de texto.')}</p>
                    <p>${Drupal.t('Segundo párrafo. Edita este contenido.')}</p>
                </div>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                    <path d="M7 8h10M7 12h10M7 16h6"/>
                </svg>`,
            },
            {
                id: 'button-primary',
                label: Drupal.t('Botón Primario'),
                category: 'basic',
                // Usar componente custom con traits configurables
                content: { type: 'jaraba-button', buttonStyle: 'primary' },
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="8" width="18" height="8" rx="4"/>
                    <path d="M8 12h8"/>
                </svg>`,
            },
            {
                id: 'button-secondary',
                label: Drupal.t('Botón Secundario'),
                category: 'basic',
                // Usar componente custom con traits configurables
                content: { type: 'jaraba-button', buttonStyle: 'secondary', buttonText: Drupal.t('Saber Más') },
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="8" width="18" height="8" rx="4"/>
                </svg>`,
            },
            {
                id: 'link',
                label: Drupal.t('Enlace'),
                category: 'basic',
                content: `<a href="#" style="color: var(--ej-color-innovation, #00A9A5); font-family: var(--ej-font-family, 'Inter', sans-serif); text-decoration: underline; transition: opacity 0.2s;">${Drupal.t('Enlace de texto')}</a>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/>
                    <path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/>
                </svg>`,
            },
            {
                id: 'divider',
                label: Drupal.t('Separador'),
                category: 'basic',
                content: `<hr style="border: none; height: 1px; background: var(--ej-border-color, #e2e8f0); margin: 2rem 0;"/>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 12h16"/>
                </svg>`,
            },
            {
                id: 'spacer',
                label: Drupal.t('Espaciador'),
                category: 'basic',
                content: `<div style="height: 48px;"></div>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 5v14M5 12h14"/>
                </svg>`,
            },
            {
                id: 'navigation',
                label: Drupal.t('Navegación'),
                category: 'basic',
                // Usar componente custom con traits configurables
                content: { type: 'jaraba-navigation' },
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 6h16M4 12h16M4 18h16"/>
                </svg>`,
            },
        ];

        // Registrar bloques básicos
        basicBlocks.forEach((block) => {
            blockManager.add(block.id, {
                label: block.label,
                category: block.category,
                content: block.content,
                media: block.media,
                attributes: { class: 'gjs-block-basic' },
            });
        });

        /**
         * Bloques Premium - Layout (Grid)
         */
        const layoutBlocks = [
            {
                id: 'grid-2-cols',
                label: Drupal.t('Grid 2 Columnas'),
                category: 'layout',
                content: `<div class="jaraba-grid jaraba-grid--2" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 2rem; padding: 2rem;">
                    <div class="jaraba-grid__col" style="background: var(--ej-bg-secondary, #f8fafc); padding: 2rem; border-radius: 12px;">
                        <h3 style="color: var(--ej-text-primary, #1e293b); margin-bottom: 1rem;">${Drupal.t('Columna 1')}</h3>
                        <p style="color: var(--ej-text-muted, #64748b);">${Drupal.t('Contenido de la primera columna')}</p>
                    </div>
                    <div class="jaraba-grid__col" style="background: var(--ej-bg-secondary, #f8fafc); padding: 2rem; border-radius: 12px;">
                        <h3 style="color: var(--ej-text-primary, #1e293b); margin-bottom: 1rem;">${Drupal.t('Columna 2')}</h3>
                        <p style="color: var(--ej-text-muted, #64748b);">${Drupal.t('Contenido de la segunda columna')}</p>
                    </div>
                </div>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="8" height="18" rx="1"/>
                    <rect x="13" y="3" width="8" height="18" rx="1"/>
                </svg>`,
            },
            {
                id: 'grid-3-cols',
                label: Drupal.t('Grid 3 Columnas'),
                category: 'layout',
                content: `<div class="jaraba-grid jaraba-grid--3" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; padding: 2rem;">
                    <div class="jaraba-grid__col" style="background: var(--ej-bg-secondary, #f8fafc); padding: 1.5rem; border-radius: 12px; text-align: center;">
                        <span style="font-size: 2rem; display: block; margin-bottom: 1rem;">🚀</span>
                        <h4 style="color: var(--ej-text-primary, #1e293b);">${Drupal.t('Característica 1')}</h4>
                    </div>
                    <div class="jaraba-grid__col" style="background: var(--ej-bg-secondary, #f8fafc); padding: 1.5rem; border-radius: 12px; text-align: center;">
                        <span style="font-size: 2rem; display: block; margin-bottom: 1rem;">💡</span>
                        <h4 style="color: var(--ej-text-primary, #1e293b);">${Drupal.t('Característica 2')}</h4>
                    </div>
                    <div class="jaraba-grid__col" style="background: var(--ej-bg-secondary, #f8fafc); padding: 1.5rem; border-radius: 12px; text-align: center;">
                        <span style="font-size: 2rem; display: block; margin-bottom: 1rem;">⭐</span>
                        <h4 style="color: var(--ej-text-primary, #1e293b);">${Drupal.t('Característica 3')}</h4>
                    </div>
                </div>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="3" width="5" height="18" rx="1"/>
                    <rect x="9" y="3" width="6" height="18" rx="1"/>
                    <rect x="17" y="3" width="5" height="18" rx="1"/>
                </svg>`,
            },
            {
                id: 'grid-4-cols',
                label: Drupal.t('Grid 4 Columnas'),
                category: 'layout',
                content: `<div class="jaraba-grid jaraba-grid--4" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; padding: 2rem;">
                    <div class="jaraba-grid__col" style="background: var(--ej-bg-secondary, #f8fafc); padding: 1rem; border-radius: 8px; text-align: center;">
                        <span style="font-weight: 700; color: var(--ej-color-corporate, #233D63);">01</span>
                    </div>
                    <div class="jaraba-grid__col" style="background: var(--ej-bg-secondary, #f8fafc); padding: 1rem; border-radius: 8px; text-align: center;">
                        <span style="font-weight: 700; color: var(--ej-color-corporate, #233D63);">02</span>
                    </div>
                    <div class="jaraba-grid__col" style="background: var(--ej-bg-secondary, #f8fafc); padding: 1rem; border-radius: 8px; text-align: center;">
                        <span style="font-weight: 700; color: var(--ej-color-corporate, #233D63);">03</span>
                    </div>
                    <div class="jaraba-grid__col" style="background: var(--ej-bg-secondary, #f8fafc); padding: 1rem; border-radius: 8px; text-align: center;">
                        <span style="font-weight: 700; color: var(--ej-color-corporate, #233D63);">04</span>
                    </div>
                </div>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="3" width="4" height="18" rx="1"/>
                    <rect x="7" y="3" width="4" height="18" rx="1"/>
                    <rect x="12" y="3" width="4" height="18" rx="1"/>
                    <rect x="17" y="3" width="5" height="18" rx="1"/>
                </svg>`,
            },
        ];

        /**
         * Bloques Premium - Hero Sections
         */
        const heroBlocks = [
            {
                id: 'hero-centered',
                label: Drupal.t('Hero Centrado'),
                category: 'hero',
                content: `<section class="jaraba-hero jaraba-hero--centered" style="background: linear-gradient(135deg, var(--ej-color-corporate, #233D63) 0%, var(--ej-color-innovation, #00A9A5) 100%); text-align: center; padding: 6rem 2rem; color: white;">
                    <h1 style="font-size: 3.5rem; font-weight: 800; margin-bottom: 1.5rem; max-width: 800px; margin-left: auto; margin-right: auto;">${Drupal.t('Tu Título Impactante')}</h1>
                    <p style="font-size: 1.25rem; opacity: 0.9; max-width: 600px; margin: 0 auto 2rem;">${Drupal.t('Describe tu propuesta de valor en una frase concisa y convincente.')}</p>
                    <div style="display: flex; gap: 1rem; justify-content: center;">
                        <a href="#" class="jaraba-button jaraba-button--primary" style="background: white; color: var(--ej-color-corporate, #233D63); padding: 0.875rem 2rem; border-radius: 8px; font-weight: 600; text-decoration: none;">${Drupal.t('Empezar Ahora')}</a>
                        <a href="#" class="jaraba-button jaraba-button--outline" style="border: 2px solid white; color: white; padding: 0.875rem 2rem; border-radius: 8px; font-weight: 600; text-decoration: none;">${Drupal.t('Saber Más')}</a>
                    </div>
                </section>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="4" width="20" height="16" rx="2"/>
                    <path d="M12 9v3M9 12h6"/>
                </svg>`,
            },
            {
                id: 'hero-split',
                label: Drupal.t('Hero 50/50'),
                category: 'hero',
                content: `<section class="jaraba-hero jaraba-hero--split" style="display: grid; grid-template-columns: 1fr 1fr; min-height: 500px; gap: 0;">
                    <div style="background: var(--ej-color-corporate, #233D63); padding: 4rem; display: flex; flex-direction: column; justify-content: center; color: white;">
                        <h1 style="font-size: 2.75rem; font-weight: 800; margin-bottom: 1rem;">${Drupal.t('Transforma tu Negocio')}</h1>
                        <p style="font-size: 1.125rem; opacity: 0.9; margin-bottom: 2rem;">${Drupal.t('Con nuestras soluciones innovadoras impulsadas por IA.')}</p>
                        <a href="#" style="background: var(--ej-color-innovation, #00A9A5); color: white; padding: 0.875rem 2rem; border-radius: 8px; font-weight: 600; text-decoration: none; width: fit-content;">${Drupal.t('Comenzar')}</a>
                    </div>
                    <div style="background: linear-gradient(135deg, var(--ej-color-innovation, #00A9A5) 0%, var(--ej-color-corporate, #233D63) 100%); display: flex; align-items: center; justify-content: center;">
                        <span style="font-size: 6rem; opacity: 0.5;">📈</span>
                    </div>
                </section>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="4" width="20" height="16" rx="2"/>
                    <path d="M12 4v16"/>
                </svg>`,
            },
            {
                id: 'hero-video',
                label: Drupal.t('Hero con Video'),
                category: 'hero',
                content: `<section class="jaraba-hero jaraba-hero--video" style="position: relative; min-height: 600px; background: #0f172a; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                    <div style="position: absolute; inset: 0; background: linear-gradient(180deg, rgba(15,23,42,0.8) 0%, rgba(15,23,42,0.95) 100%);"></div>
                    <div style="position: relative; z-index: 1; text-align: center; color: white; max-width: 800px; padding: 2rem;">
                        <h1 style="font-size: 3rem; font-weight: 800; margin-bottom: 1.5rem;">${Drupal.t('Experiencia Inmersiva')}</h1>
                        <p style="font-size: 1.25rem; opacity: 0.9; margin-bottom: 2rem;">${Drupal.t('Añade un video de fondo para capturar la atención de tus visitantes.')}</p>
                        <button style="width: 80px; height: 80px; border-radius: 50%; background: var(--ej-color-innovation, #00A9A5); border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                            <span style="font-size: 2rem; margin-left: 4px;">▶</span>
                        </button>
                    </div>
                </section>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="4" width="20" height="16" rx="2"/>
                    <polygon points="10,8 16,12 10,16" fill="currentColor"/>
                </svg>`,
            },
        ];

        /**
         * Bloques Premium - Contenido
         */
        const contentBlocks = [
            {
                id: 'testimonial',
                label: Drupal.t('Testimonial'),
                category: 'testimonials',
                content: `<div class="jaraba-testimonial" style="background: var(--ej-bg-secondary, #f8fafc); border-radius: 16px; padding: 2rem; max-width: 600px;">
                    <blockquote style="font-size: 1.125rem; font-style: italic; color: var(--ej-text-primary, #1e293b); margin-bottom: 1.5rem; line-height: 1.7;">"${Drupal.t('Este producto ha transformado completamente la forma en que trabajamos. Increíble experiencia.')}"</blockquote>
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 56px; height: 56px; border-radius: 50%; background: var(--ej-color-corporate, #233D63); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1.25rem;">MJ</div>
                        <div>
                            <p style="font-weight: 600; color: var(--ej-text-primary, #1e293b); margin: 0;">${Drupal.t('María Jiménez')}</p>
                            <p style="font-size: 0.875rem; color: var(--ej-text-muted, #64748b); margin: 0;">${Drupal.t('CEO, Empresa Tech')}</p>
                        </div>
                    </div>
                </div>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V21z"/>
                    <path d="M15 21c3 0 7-1 7-8V5c0-1.25-.757-2.017-2-2h-4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2h.75c0 2.25.25 4-2.75 4v3c0 1 0 1 1 1z"/>
                </svg>`,
            },
            {
                id: 'faq-accordion',
                label: Drupal.t('FAQ Accordion'),
                category: 'faq',
                // Usar el tipo de componente jaraba-faq que tiene script property
                content: {
                    type: 'jaraba-faq',
                    content: `
                        <h2 style="text-align: center; color: var(--ej-text-primary, #1e293b); margin-bottom: 2rem;">${Drupal.t('Preguntas Frecuentes')}</h2>
                        <div class="jaraba-faq__item" style="border-bottom: 1px solid var(--ej-border-color, #e2e8f0); padding: 1.25rem 0;">
                            <button class="jaraba-faq__toggle" style="display: flex; justify-content: space-between; width: 100%; background: none; border: none; cursor: pointer; font-size: 1.125rem; font-weight: 600; color: var(--ej-text-primary, #1e293b); text-align: left; padding: 0;">
                                ${Drupal.t('¿Cómo empiezo a usar el servicio?')}
                                <span style="font-size: 1.5rem; line-height: 1; transition: transform 0.2s;">+</span>
                            </button>
                            <div class="jaraba-faq__answer" style="max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out;">
                                <p style="color: var(--ej-text-muted, #64748b); margin: 0; padding-top: 1rem; line-height: 1.6;">${Drupal.t('Regístrate gratis y comienza a explorar todas las funcionalidades. Nuestro equipo te guiará paso a paso.')}</p>
                            </div>
                        </div>
                        <div class="jaraba-faq__item" style="border-bottom: 1px solid var(--ej-border-color, #e2e8f0); padding: 1.25rem 0;">
                            <button class="jaraba-faq__toggle" style="display: flex; justify-content: space-between; width: 100%; background: none; border: none; cursor: pointer; font-size: 1.125rem; font-weight: 600; color: var(--ej-text-primary, #1e293b); text-align: left; padding: 0;">
                                ${Drupal.t('¿Cuáles son los planes de precios?')}
                                <span style="font-size: 1.5rem; line-height: 1; transition: transform 0.2s;">+</span>
                            </button>
                            <div class="jaraba-faq__answer" style="max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out;">
                                <p style="color: var(--ej-text-muted, #64748b); margin: 0; padding-top: 1rem; line-height: 1.6;">${Drupal.t('Ofrecemos planes flexibles adaptados a diferentes necesidades. Consulta nuestra página de precios.')}</p>
                            </div>
                        </div>
                        <div class="jaraba-faq__item" style="border-bottom: 1px solid var(--ej-border-color, #e2e8f0); padding: 1.25rem 0;">
                            <button class="jaraba-faq__toggle" style="display: flex; justify-content: space-between; width: 100%; background: none; border: none; cursor: pointer; font-size: 1.125rem; font-weight: 600; color: var(--ej-text-primary, #1e293b); text-align: left; padding: 0;">
                                ${Drupal.t('¿Ofrecen soporte técnico?')}
                                <span style="font-size: 1.5rem; line-height: 1; transition: transform 0.2s;">+</span>
                            </button>
                            <div class="jaraba-faq__answer" style="max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out;">
                                <p style="color: var(--ej-text-muted, #64748b); margin: 0; padding-top: 1rem; line-height: 1.6;">${Drupal.t('Sí, nuestro equipo de soporte está disponible 24/7 para ayudarte con cualquier consulta.')}</p>
                            </div>
                        </div>
                    `,
                    style: { 'max-width': '800px', margin: '0 auto' },
                },
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/>
                    <path d="M12 17h.01"/>
                </svg>`,
            },
            {
                id: 'team-member',
                label: Drupal.t('Miembro del Equipo'),
                category: 'team',
                content: `<div class="jaraba-team-member" style="text-align: center; max-width: 280px;">
                    <div style="width: 160px; height: 160px; border-radius: 50%; background: linear-gradient(135deg, var(--ej-color-corporate, #233D63) 0%, var(--ej-color-innovation, #00A9A5) 100%); margin: 0 auto 1.5rem; display: flex; align-items: center; justify-content: center;">
                        <span style="font-size: 4rem; color: white; opacity: 0.8;">👤</span>
                    </div>
                    <h3 style="color: var(--ej-text-primary, #1e293b); margin-bottom: 0.25rem; font-size: 1.25rem;">${Drupal.t('Ana García')}</h3>
                    <p style="color: var(--ej-color-innovation, #00A9A5); font-weight: 500; margin-bottom: 1rem;">${Drupal.t('Directora de Innovación')}</p>
                    <p style="color: var(--ej-text-muted, #64748b); font-size: 0.875rem; line-height: 1.5;">${Drupal.t('Experta en transformación digital con más de 10 años de experiencia.')}</p>
                    <div style="display: flex; gap: 0.75rem; justify-content: center; margin-top: 1rem;">
                        <a href="#" style="width: 36px; height: 36px; border-radius: 50%; background: var(--ej-bg-secondary, #f1f5f9); display: flex; align-items: center; justify-content: center; color: var(--ej-text-muted, #64748b);">in</a>
                        <a href="#" style="width: 36px; height: 36px; border-radius: 50%; background: var(--ej-bg-secondary, #f1f5f9); display: flex; align-items: center; justify-content: center; color: var(--ej-text-muted, #64748b);">X</a>
                    </div>
                </div>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>`,
            },
            {
                id: 'feature-cards',
                label: Drupal.t('Grid de Características'),
                category: 'features',
                content: `<section class="jaraba-features" style="padding: 4rem 2rem;">
                    <h2 style="text-align: center; color: var(--ej-text-primary, #1e293b); margin-bottom: 3rem; font-size: 2rem;">${Drupal.t('Nuestras Características')}</h2>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem; max-width: 1200px; margin: 0 auto;">
                        <div style="background: white; border: 1px solid var(--ej-border-color, #e2e8f0); border-radius: 12px; padding: 2rem; text-align: center; transition: box-shadow 0.3s;">
                            <div style="width: 64px; height: 64px; border-radius: 12px; background: linear-gradient(135deg, var(--ej-color-innovation, #00A9A5) 0%, var(--ej-color-corporate, #233D63) 100%); margin: 0 auto 1.5rem; display: flex; align-items: center; justify-content: center;">
                                <span style="font-size: 1.75rem; color: white;">⚡</span>
                            </div>
                            <h3 style="color: var(--ej-text-primary, #1e293b); margin-bottom: 0.75rem;">${Drupal.t('Rápido')}</h3>
                            <p style="color: var(--ej-text-muted, #64748b); font-size: 0.9rem;">${Drupal.t('Rendimiento optimizado para la mejor experiencia.')}</p>
                        </div>
                        <div style="background: white; border: 1px solid var(--ej-border-color, #e2e8f0); border-radius: 12px; padding: 2rem; text-align: center;">
                            <div style="width: 64px; height: 64px; border-radius: 12px; background: linear-gradient(135deg, var(--ej-color-innovation, #00A9A5) 0%, var(--ej-color-corporate, #233D63) 100%); margin: 0 auto 1.5rem; display: flex; align-items: center; justify-content: center;">
                                <span style="font-size: 1.75rem; color: white;">🔒</span>
                            </div>
                            <h3 style="color: var(--ej-text-primary, #1e293b); margin-bottom: 0.75rem;">${Drupal.t('Seguro')}</h3>
                            <p style="color: var(--ej-text-muted, #64748b); font-size: 0.9rem;">${Drupal.t('Protección de datos de nivel empresarial.')}</p>
                        </div>
                        <div style="background: white; border: 1px solid var(--ej-border-color, #e2e8f0); border-radius: 12px; padding: 2rem; text-align: center;">
                            <div style="width: 64px; height: 64px; border-radius: 12px; background: linear-gradient(135deg, var(--ej-color-innovation, #00A9A5) 0%, var(--ej-color-corporate, #233D63) 100%); margin: 0 auto 1.5rem; display: flex; align-items: center; justify-content: center;">
                                <span style="font-size: 1.75rem; color: white;">🎯</span>
                            </div>
                            <h3 style="color: var(--ej-text-primary, #1e293b); margin-bottom: 0.75rem;">${Drupal.t('Preciso')}</h3>
                            <p style="color: var(--ej-text-muted, #64748b); font-size: 0.9rem;">${Drupal.t('Resultados exactos gracias a IA avanzada.')}</p>
                        </div>
                    </div>
                </section>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7" rx="1"/>
                    <rect x="14" y="3" width="7" height="7" rx="1"/>
                    <rect x="3" y="14" width="7" height="7" rx="1"/>
                    <rect x="14" y="14" width="7" height="7" rx="1"/>
                </svg>`,
            },
        ];

        /**
         * Bloques Premium - CTA (Call to Action)
         */
        const ctaBlocks = [
            {
                id: 'cta-centered',
                label: Drupal.t('CTA Centrado'),
                category: 'cta',
                content: `<section class="jaraba-cta jaraba-cta--centered" style="background: linear-gradient(135deg, var(--ej-color-impulse, #FF8C42) 0%, var(--ej-color-passion, #E63946) 100%); text-align: center; padding: 5rem 2rem; color: white;">
                    <h2 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 1rem; max-width: 700px; margin-left: auto; margin-right: auto;">${Drupal.t('¿Listo para dar el siguiente paso?')}</h2>
                    <p style="font-size: 1.25rem; opacity: 0.95; max-width: 550px; margin: 0 auto 2rem;">${Drupal.t('Únete a miles de profesionales que ya están transformando su carrera.')}</p>
                    <a href="#" style="display: inline-block; background: white; color: var(--ej-color-impulse, #FF8C42); padding: 1rem 2.5rem; border-radius: 8px; font-weight: 700; text-decoration: none; font-size: 1.125rem; transition: transform 0.2s, box-shadow 0.2s;">${Drupal.t('Comenzar Ahora')}</a>
                </section>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="6" width="20" height="12" rx="2"/>
                    <path d="M12 10v4M10 12h4"/>
                </svg>`,
            },
            {
                id: 'cta-split',
                label: Drupal.t('CTA 50/50'),
                category: 'cta',
                content: `<section class="jaraba-cta jaraba-cta--split" style="display: grid; grid-template-columns: 1fr 1fr; min-height: 400px; gap: 0;">
                    <div style="background: var(--ej-color-corporate, #233D63); padding: 4rem; display: flex; flex-direction: column; justify-content: center; color: white;">
                        <span style="font-size: 0.875rem; text-transform: uppercase; letter-spacing: 2px; opacity: 0.8; margin-bottom: 1rem;">${Drupal.t('Oferta Especial')}</span>
                        <h2 style="font-size: 2.25rem; font-weight: 800; margin-bottom: 1rem;">${Drupal.t('50% de descuento en tu primer mes')}</h2>
                        <p style="font-size: 1rem; opacity: 0.9; margin-bottom: 2rem;">${Drupal.t('Aprovecha esta oportunidad única para comenzar tu transformación.')}</p>
                        <a href="#" style="background: var(--ej-color-impulse, #FF8C42); color: white; padding: 1rem 2rem; border-radius: 8px; font-weight: 600; text-decoration: none; width: fit-content;">${Drupal.t('Reclamar Oferta')}</a>
                    </div>
                    <div style="background: linear-gradient(135deg, var(--ej-color-innovation, #00A9A5) 0%, var(--ej-color-corporate, #233D63) 100%); display: flex; align-items: center; justify-content: center;">
                        <span style="font-size: 8rem; opacity: 0.3;">🎁</span>
                    </div>
                </section>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="6" width="20" height="12" rx="2"/>
                    <path d="M12 6v12"/>
                </svg>`,
            },
            {
                id: 'cta-banner',
                label: Drupal.t('CTA Banner'),
                category: 'cta',
                content: `<div class="jaraba-cta jaraba-cta--banner" style="background: var(--ej-color-corporate, #233D63); display: flex; justify-content: space-between; align-items: center; padding: 1.5rem 3rem; gap: 2rem; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 1rem; color: white;">
                        <span style="font-size: 1.5rem;">⚡</span>
                        <div>
                            <p style="font-weight: 700; margin: 0; font-size: 1.125rem;">${Drupal.t('¡Últimas plazas disponibles!')}</p>
                            <p style="margin: 0; opacity: 0.8; font-size: 0.875rem;">${Drupal.t('La oferta termina en 24 horas')}</p>
                        </div>
                    </div>
                    <a href="#" style="background: var(--ej-color-impulse, #FF8C42); color: white; padding: 0.875rem 2rem; border-radius: 8px; font-weight: 600; text-decoration: none; white-space: nowrap;">${Drupal.t('Reservar Ahora')}</a>
                </div>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="8" width="20" height="8" rx="2"/>
                    <path d="M6 12h12"/>
                </svg>`,
            },
        ];

        /**
         * Bloques Premium - Stats
         */
        const statsBlocks = [
            {
                id: 'stats-counter',
                label: Drupal.t('Contador Stats'),
                category: 'stats',
                // Dual Architecture: usa tipo de componente con script property
                content: { type: 'jaraba-stats-counter' },
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 20V10M12 20V4M6 20v-6"/>
                </svg>`,
            },
            {
                id: 'stats-progress',
                label: Drupal.t('Barras de Progreso'),
                category: 'stats',
                content: `<section class="jaraba-stats jaraba-stats--progress" style="background: white; padding: 3rem; max-width: 600px; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                    <h3 style="color: var(--ej-text-primary, #1e293b); margin-bottom: 2rem; text-align: center;">${Drupal.t('Nuestras Competencias')}</h3>
                    <div style="margin-bottom: 1.5rem;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span style="color: var(--ej-text-primary, #1e293b); font-weight: 500;">${Drupal.t('Innovación')}</span>
                            <span style="color: var(--ej-text-muted, #64748b);">95%</span>
                        </div>
                        <div style="height: 8px; background: var(--ej-bg-secondary, #e2e8f0); border-radius: 4px; overflow: hidden;">
                            <div style="width: 95%; height: 100%; background: linear-gradient(90deg, var(--ej-color-corporate, #233D63), var(--ej-color-innovation, #00A9A5)); border-radius: 4px;"></div>
                        </div>
                    </div>
                    <div style="margin-bottom: 1.5rem;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span style="color: var(--ej-text-primary, #1e293b); font-weight: 500;">${Drupal.t('Calidad')}</span>
                            <span style="color: var(--ej-text-muted, #64748b);">88%</span>
                        </div>
                        <div style="height: 8px; background: var(--ej-bg-secondary, #e2e8f0); border-radius: 4px; overflow: hidden;">
                            <div style="width: 88%; height: 100%; background: linear-gradient(90deg, var(--ej-color-impulse, #FF8C42), var(--ej-color-passion, #E63946)); border-radius: 4px;"></div>
                        </div>
                    </div>
                    <div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span style="color: var(--ej-text-primary, #1e293b); font-weight: 500;">${Drupal.t('Satisfacción')}</span>
                            <span style="color: var(--ej-text-muted, #64748b);">99%</span>
                        </div>
                        <div style="height: 8px; background: var(--ej-bg-secondary, #e2e8f0); border-radius: 4px; overflow: hidden;">
                            <div style="width: 99%; height: 100%; background: var(--ej-color-innovation, #00A9A5); border-radius: 4px;"></div>
                        </div>
                    </div>
                </section>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="6" width="18" height="4" rx="1"/>
                    <rect x="3" y="14" width="18" height="4" rx="1"/>
                </svg>`,
            },
            {
                id: 'stats-comparison',
                label: Drupal.t('Antes/Después'),
                category: 'stats',
                content: `<section class="jaraba-stats jaraba-stats--comparison" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; padding: 3rem; max-width: 900px; margin: 0 auto;">
                    <div style="background: var(--ej-bg-secondary, #f8fafc); border-radius: 16px; padding: 2rem; text-align: center; border: 2px dashed var(--ej-border-color, #e2e8f0);">
                        <span style="display: inline-block; background: var(--ej-text-muted, #94a3b8); color: white; padding: 0.25rem 1rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; margin-bottom: 1.5rem;">${Drupal.t('ANTES')}</span>
                        <span style="font-size: 3.5rem; font-weight: 800; color: var(--ej-text-muted, #94a3b8); display: block; margin-bottom: 0.5rem;">2h</span>
                        <p style="color: var(--ej-text-muted, #64748b); margin: 0;">${Drupal.t('Tiempo promedio de respuesta')}</p>
                    </div>
                    <div style="background: linear-gradient(135deg, var(--ej-color-corporate, #233D63) 0%, var(--ej-color-innovation, #00A9A5) 100%); border-radius: 16px; padding: 2rem; text-align: center; color: white; position: relative; overflow: hidden;">
                        <span style="display: inline-block; background: white; color: var(--ej-color-innovation, #00A9A5); padding: 0.25rem 1rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; margin-bottom: 1.5rem;">${Drupal.t('DESPUÉS')}</span>
                        <span style="font-size: 3.5rem; font-weight: 800; display: block; margin-bottom: 0.5rem;">5min</span>
                        <p style="margin: 0; opacity: 0.9;">${Drupal.t('Tiempo promedio de respuesta')}</p>
                    </div>
                </section>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M16 3h5v5M8 21H3v-5M21 3l-9 9M3 21l9-9"/>
                </svg>`,
            },
        ];

        /**
         * Bloques Premium - Pricing
         */
        const pricingBlocks = [
            {
                id: 'pricing-single',
                label: Drupal.t('Precio Destacado'),
                category: 'pricing',
                content: `<div class="jaraba-pricing jaraba-pricing--single" style="background: linear-gradient(135deg, var(--ej-color-corporate, #233D63) 0%, var(--ej-color-innovation, #00A9A5) 100%); border-radius: 24px; padding: 3rem; max-width: 380px; text-align: center; color: white; position: relative; overflow: hidden;">
                    <span style="position: absolute; top: 1rem; right: 1rem; background: var(--ej-color-impulse, #FF8C42); padding: 0.25rem 1rem; border-radius: 20px; font-size: 0.75rem; font-weight: 700;">${Drupal.t('MÁS POPULAR')}</span>
                    <h3 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem;">${Drupal.t('Plan Pro')}</h3>
                    <p style="opacity: 0.8; margin-bottom: 1.5rem; font-size: 0.9rem;">${Drupal.t('Todo lo que necesitas para crecer')}</p>
                    <div style="margin-bottom: 2rem;">
                        <span style="font-size: 3.5rem; font-weight: 800;">€49</span>
                        <span style="opacity: 0.8;">/${Drupal.t('mes')}</span>
                    </div>
                    <ul style="text-align: left; list-style: none; padding: 0; margin: 0 0 2rem 0;">
                        <li style="padding: 0.75rem 0; border-bottom: 1px solid rgba(255,255,255,0.2); display: flex; align-items: center; gap: 0.75rem;"><span>✓</span> ${Drupal.t('Acceso ilimitado')}</li>
                        <li style="padding: 0.75rem 0; border-bottom: 1px solid rgba(255,255,255,0.2); display: flex; align-items: center; gap: 0.75rem;"><span>✓</span> ${Drupal.t('Soporte prioritario 24/7')}</li>
                        <li style="padding: 0.75rem 0; border-bottom: 1px solid rgba(255,255,255,0.2); display: flex; align-items: center; gap: 0.75rem;"><span>✓</span> ${Drupal.t('Integraciones premium')}</li>
                        <li style="padding: 0.75rem 0; display: flex; align-items: center; gap: 0.75rem;"><span>✓</span> ${Drupal.t('Analytics avanzados')}</li>
                    </ul>
                    <a href="#" style="display: block; background: white; color: var(--ej-color-corporate, #233D63); padding: 1rem; border-radius: 12px; font-weight: 700; text-decoration: none; font-size: 1rem;">${Drupal.t('Comenzar Prueba Gratis')}</a>
                </div>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="5" y="2" width="14" height="20" rx="2"/>
                    <path d="M12 6v4M10 10h4"/>
                </svg>`,
            },
            {
                id: 'pricing-comparison',
                label: Drupal.t('Tabla de Precios'),
                category: 'pricing',
                content: `<section class="jaraba-pricing jaraba-pricing--comparison" style="padding: 4rem 2rem;">
                    <h2 style="text-align: center; color: var(--ej-text-primary, #1e293b); margin-bottom: 3rem;">${Drupal.t('Elige tu Plan')}</h2>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; max-width: 1000px; margin: 0 auto;">
                        <div style="background: white; border: 1px solid var(--ej-border-color, #e2e8f0); border-radius: 16px; padding: 2rem; text-align: center;">
                            <h4 style="color: var(--ej-text-primary, #1e293b); margin-bottom: 0.5rem;">${Drupal.t('Starter')}</h4>
                            <div style="margin: 1.5rem 0;"><span style="font-size: 2.5rem; font-weight: 800; color: var(--ej-text-primary, #1e293b);">€19</span><span style="color: var(--ej-text-muted, #64748b);">/${Drupal.t('mes')}</span></div>
                            <a href="#" style="display: block; border: 2px solid var(--ej-color-corporate, #233D63); color: var(--ej-color-corporate, #233D63); padding: 0.75rem; border-radius: 8px; text-decoration: none; font-weight: 600;">${Drupal.t('Elegir Plan')}</a>
                        </div>
                        <div style="background: linear-gradient(135deg, var(--ej-color-corporate, #233D63) 0%, var(--ej-color-innovation, #00A9A5) 100%); border-radius: 16px; padding: 2rem; text-align: center; color: white; transform: scale(1.05); box-shadow: 0 20px 40px rgba(0,0,0,0.15);">
                            <span style="background: var(--ej-color-impulse, #FF8C42); padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.7rem; font-weight: 700;">${Drupal.t('RECOMENDADO')}</span>
                            <h4 style="margin: 0.75rem 0 0.5rem;">${Drupal.t('Pro')}</h4>
                            <div style="margin: 1.5rem 0;"><span style="font-size: 2.5rem; font-weight: 800;">€49</span><span style="opacity: 0.8;">/${Drupal.t('mes')}</span></div>
                            <a href="#" style="display: block; background: white; color: var(--ej-color-corporate, #233D63); padding: 0.75rem; border-radius: 8px; text-decoration: none; font-weight: 600;">${Drupal.t('Elegir Plan')}</a>
                        </div>
                        <div style="background: white; border: 1px solid var(--ej-border-color, #e2e8f0); border-radius: 16px; padding: 2rem; text-align: center;">
                            <h4 style="color: var(--ej-text-primary, #1e293b); margin-bottom: 0.5rem;">${Drupal.t('Enterprise')}</h4>
                            <div style="margin: 1.5rem 0;"><span style="font-size: 2.5rem; font-weight: 800; color: var(--ej-text-primary, #1e293b);">€99</span><span style="color: var(--ej-text-muted, #64748b);">/${Drupal.t('mes')}</span></div>
                            <a href="#" style="display: block; border: 2px solid var(--ej-color-corporate, #233D63); color: var(--ej-color-corporate, #233D63); padding: 0.75rem; border-radius: 8px; text-decoration: none; font-weight: 600;">${Drupal.t('Elegir Plan')}</a>
                        </div>
                    </div>
                </section>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="4" width="6" height="16" rx="1"/>
                    <rect x="9" y="2" width="6" height="18" rx="1"/>
                    <rect x="16" y="6" width="6" height="14" rx="1"/>
                </svg>`,
            },
            {
                id: 'pricing-toggle',
                label: Drupal.t('Toggle Mensual/Anual'),
                category: 'pricing',
                // Dual Architecture: usa tipo de componente con script property
                content: { type: 'jaraba-pricing-toggle' },
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="1" y="5" width="22" height="14" rx="7"/>
                    <circle cx="16" cy="12" r="4" fill="currentColor"/>
                </svg>`,
            },
            {
                id: 'pricing-table',
                label: Drupal.t('Tabla de Precios'),
                category: 'pricing',
                // Sprint PB-4: usa tipo de componente con traits configurables
                content: { type: 'jaraba-pricing-table' },
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M3 15h18M9 3v18M15 3v18"/></svg>`,
            },
        ];

        /**
         * Bloques Interactivos - Pestañas, Countdown, Timeline.
         * Todos usan Dual Architecture con tipo de componente.
         */
        const interactiveBlocks = [
            {
                id: 'tabs-content',
                label: Drupal.t('Pestañas de Contenido'),
                category: 'tabs',
                content: { type: 'jaraba-tabs' },
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                    <path d="M3 9h18"/>
                    <path d="M9 3v6"/>
                    <path d="M15 3v6"/>
                </svg>`,
            },
            {
                id: 'countdown-timer',
                label: Drupal.t('Cuenta Regresiva'),
                category: 'countdown',
                content: { type: 'jaraba-countdown' },
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>`,
            },
            {
                id: 'timeline',
                label: Drupal.t('Timeline'),
                category: 'timeline',
                content: { type: 'jaraba-timeline' },
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="2" x2="12" y2="22"/>
                    <circle cx="12" cy="6" r="2"/>
                    <circle cx="12" cy="12" r="2"/>
                    <circle cx="12" cy="18" r="2"/>
                    <path d="M14 6h4M14 12h4M14 18h4"/>
                </svg>`,
            },
        ];

        /**
         * Bloques Premium - Contact
         */
        const contactBlocks = [
            {
                id: 'contact-form',
                label: Drupal.t('Formulario Contacto'),
                category: 'contact',
                // Sprint PB-4: usa tipo de componente con traits configurables
                content: { type: 'jaraba-contact-form' },
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="5" width="18" height="14" rx="2"/>
                    <path d="M3 7l9 6 9-6"/>
                </svg>`,
            },
            {
                id: 'contact-info',
                label: Drupal.t('Info de Contacto'),
                category: 'contact',
                content: `<section class="jaraba-contact jaraba-contact--info" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem; padding: 3rem; max-width: 900px; margin: 0 auto;">
                    <div style="text-align: center;">
                        <div style="width: 64px; height: 64px; background: linear-gradient(135deg, var(--ej-color-corporate, #233D63) 0%, var(--ej-color-innovation, #00A9A5) 100%); border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                            <span style="font-size: 1.5rem; color: white;">📍</span>
                        </div>
                        <h4 style="color: var(--ej-text-primary, #1e293b); margin-bottom: 0.5rem;">${Drupal.t('Dirección')}</h4>
                        <p style="color: var(--ej-text-muted, #64748b); font-size: 0.9rem; margin: 0;">${Drupal.t('Calle Innovación, 123')}<br/>Madrid, España</p>
                    </div>
                    <div style="text-align: center;">
                        <div style="width: 64px; height: 64px; background: linear-gradient(135deg, var(--ej-color-impulse, #FF8C42) 0%, var(--ej-color-passion, #E63946) 100%); border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                            <span style="font-size: 1.5rem; color: white;">📞</span>
                        </div>
                        <h4 style="color: var(--ej-text-primary, #1e293b); margin-bottom: 0.5rem;">${Drupal.t('Teléfono')}</h4>
                        <p style="color: var(--ej-text-muted, #64748b); font-size: 0.9rem; margin: 0;">+34 900 123 456</p>
                    </div>
                    <div style="text-align: center;">
                        <div style="width: 64px; height: 64px; background: var(--ej-color-innovation, #00A9A5); border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                            <span style="font-size: 1.5rem; color: white;">✉️</span>
                        </div>
                        <h4 style="color: var(--ej-text-primary, #1e293b); margin-bottom: 0.5rem;">${Drupal.t('Email')}</h4>
                        <p style="color: var(--ej-text-muted, #64748b); font-size: 0.9rem; margin: 0;">hola@ejemplo.com</p>
                    </div>
                </section>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/>
                    <circle cx="12" cy="10" r="3"/>
                </svg>`,
            },
            {
                id: 'contact-cta',
                label: Drupal.t('CTA Calendario'),
                category: 'contact',
                content: `<section class="jaraba-contact jaraba-contact--cta" style="display: grid; grid-template-columns: 1fr 1fr; gap: 0; max-width: 900px; margin: 0 auto; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.1);">
                    <div style="background: var(--ej-color-corporate, #233D63); padding: 3rem; color: white;">
                        <h3 style="font-size: 1.75rem; margin-bottom: 1rem;">${Drupal.t('Agenda una llamada')}</h3>
                        <p style="opacity: 0.9; margin-bottom: 2rem; line-height: 1.6;">${Drupal.t('Reserva 30 minutos con nuestro equipo para discutir cómo podemos ayudarte.')}</p>
                        <ul style="list-style: none; padding: 0; margin: 0;">
                            <li style="display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem 0;"><span>✓</span> ${Drupal.t('Sin compromiso')}</li>
                            <li style="display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem 0;"><span>✓</span> ${Drupal.t('Asesoramiento personalizado')}</li>
                            <li style="display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem 0;"><span>✓</span> ${Drupal.t('Respuestas inmediatas')}</li>
                        </ul>
                    </div>
                    <div style="background: white; padding: 3rem; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center;">
                        <div style="font-size: 4rem; margin-bottom: 1rem;">📅</div>
                        <a href="#" style="background: var(--ej-color-impulse, #FF8C42); color: white; padding: 1rem 2rem; border-radius: 10px; text-decoration: none; font-weight: 600;">${Drupal.t('Ver Disponibilidad')}</a>
                    </div>
                </section>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2"/>
                    <path d="M16 2v4M8 2v4M3 10h18"/>
                </svg>`,
            },
        ];

        /**
         * Bloques Premium - Media
         */
        const mediaBlocks = [
            {
                id: 'image-gallery',
                label: Drupal.t('Galería Imágenes'),
                category: 'media',
                content: `<section class="jaraba-media jaraba-media--gallery" style="display: grid; grid-template-columns: repeat(3, 1fr); grid-template-rows: repeat(2, 200px); gap: 1rem; padding: 2rem;">
                    <div style="background: linear-gradient(135deg, var(--ej-color-corporate, #233D63) 0%, var(--ej-color-innovation, #00A9A5) 100%); border-radius: 12px; grid-row: span 2; display: flex; align-items: center; justify-content: center;">
                        <span style="font-size: 3rem; opacity: 0.5;">🖼️</span>
                    </div>
                    <div style="background: var(--ej-bg-secondary, #f1f5f9); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                        <span style="font-size: 2rem; opacity: 0.5;">🖼️</span>
                    </div>
                    <div style="background: var(--ej-bg-secondary, #f1f5f9); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                        <span style="font-size: 2rem; opacity: 0.5;">🖼️</span>
                    </div>
                    <div style="background: var(--ej-bg-secondary, #f1f5f9); border-radius: 12px; grid-column: span 2; display: flex; align-items: center; justify-content: center;">
                        <span style="font-size: 2rem; opacity: 0.5;">🖼️</span>
                    </div>
                </section>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                    <circle cx="8.5" cy="8.5" r="1.5"/>
                    <path d="M21 15l-5-5L5 21"/>
                </svg>`,
            },
            {
                id: 'video-embed',
                label: Drupal.t('Video Embed'),
                category: 'media',
                content: `<div class="jaraba-media jaraba-media--video" style="position: relative; aspect-ratio: 16/9; max-width: 800px; margin: 0 auto; border-radius: 16px; overflow: hidden; background: #0f172a;">
                    <div style="position: absolute; inset: 0; background: linear-gradient(180deg, rgba(0,0,0,0.2) 0%, rgba(0,0,0,0.6) 100%);"></div>
                    <div style="position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; color: white;">
                        <button style="width: 80px; height: 80px; border-radius: 50%; background: var(--ej-color-impulse, #FF8C42); border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; margin-bottom: 1rem; transition: transform 0.2s;">
                            <span style="font-size: 2rem; margin-left: 4px;">▶</span>
                        </button>
                        <p style="font-weight: 600; margin: 0;">${Drupal.t('Ver Video')}</p>
                    </div>
                </div>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="4" width="20" height="16" rx="2"/>
                    <polygon points="10,8 16,12 10,16" fill="currentColor"/>
                </svg>`,
            },
            {
                id: 'image-text-overlay',
                label: Drupal.t('Imagen con Texto'),
                category: 'media',
                content: `<div class="jaraba-media jaraba-media--overlay" style="position: relative; min-height: 400px; border-radius: 20px; overflow: hidden; background: linear-gradient(135deg, var(--ej-color-corporate, #233D63) 0%, var(--ej-color-innovation, #00A9A5) 100%);">
                    <div style="position: absolute; inset: 0; background: linear-gradient(180deg, rgba(0,0,0,0) 0%, rgba(0,0,0,0.7) 100%);"></div>
                    <div style="position: absolute; bottom: 0; left: 0; right: 0; padding: 3rem; color: white;">
                        <span style="display: inline-block; background: var(--ej-color-impulse, #FF8C42); padding: 0.25rem 1rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; margin-bottom: 1rem;">${Drupal.t('DESTACADO')}</span>
                        <h3 style="font-size: 2rem; font-weight: 800; margin-bottom: 0.75rem;">${Drupal.t('Título sobre la imagen')}</h3>
                        <p style="opacity: 0.9; max-width: 500px; margin: 0; line-height: 1.6;">${Drupal.t('Descripción que aparece sobre la imagen con un gradiente de fondo.')}</p>
                    </div>
                </div>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                    <path d="M7 15h10M7 11h6"/>
                </svg>`,
            },
        ];

        /**
         * Bloques Commerce - Tienda/Pagos
         */
        const commerceBlocks = [
            {
                id: 'product-card',
                label: Drupal.t('Card Producto'),
                category: 'commerce',
                // Sprint PB-4: usa tipo de componente con traits configurables
                content: { type: 'jaraba-product-card' },
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>`,
            },
            {
                id: 'product-grid',
                label: Drupal.t('Grid Productos'),
                category: 'commerce',
                content: `<section class="jaraba-products" style="padding: 3rem 2rem;">
                    <h2 style="text-align: center; color: var(--ej-text-primary, #1e293b); margin-bottom: 2rem;">${Drupal.t('Productos Destacados')}</h2>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; max-width: 1000px; margin: 0 auto;">
                        <div style="background: white; border-radius: 12px; padding: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.06); text-align: center;">
                            <div style="aspect-ratio: 1; background: var(--ej-bg-secondary, #f1f5f9); border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-bottom: 1rem;"><span style="font-size: 2rem;">📦</span></div>
                            <h4 style="margin: 0 0 0.5rem; color: var(--ej-text-primary, #1e293b);">${Drupal.t('Producto 1')}</h4>
                            <span style="font-weight: 700; color: var(--ej-color-corporate, #233D63);">€29</span>
                        </div>
                        <div style="background: white; border-radius: 12px; padding: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.06); text-align: center;">
                            <div style="aspect-ratio: 1; background: var(--ej-bg-secondary, #f1f5f9); border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-bottom: 1rem;"><span style="font-size: 2rem;">📦</span></div>
                            <h4 style="margin: 0 0 0.5rem; color: var(--ej-text-primary, #1e293b);">${Drupal.t('Producto 2')}</h4>
                            <span style="font-weight: 700; color: var(--ej-color-corporate, #233D63);">€49</span>
                        </div>
                        <div style="background: white; border-radius: 12px; padding: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.06); text-align: center;">
                            <div style="aspect-ratio: 1; background: var(--ej-bg-secondary, #f1f5f9); border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-bottom: 1rem;"><span style="font-size: 2rem;">📦</span></div>
                            <h4 style="margin: 0 0 0.5rem; color: var(--ej-text-primary, #1e293b);">${Drupal.t('Producto 3')}</h4>
                            <span style="font-weight: 700; color: var(--ej-color-corporate, #233D63);">€39</span>
                        </div>
                    </div>
                </section>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>`,
            },
            {
                id: 'cart-summary',
                label: Drupal.t('Resumen Carrito'),
                category: 'commerce',
                content: `<div class="jaraba-cart" style="background: white; border-radius: 16px; padding: 2rem; max-width: 400px; box-shadow: 0 4px 15px rgba(0,0,0,0.08);">
                    <h3 style="color: var(--ej-text-primary, #1e293b); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;"><span>🛒</span> ${Drupal.t('Tu Carrito')}</h3>
                    <div style="border-bottom: 1px solid var(--ej-border-color, #e2e8f0); padding-bottom: 1rem; margin-bottom: 1rem;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;"><span>${Drupal.t('Subtotal')}</span><span>€97.00</span></div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;"><span>${Drupal.t('Envío')}</span><span style="color: var(--ej-color-innovation, #00A9A5);">${Drupal.t('Gratis')}</span></div>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 1.25rem; font-weight: 700; color: var(--ej-text-primary, #1e293b); margin-bottom: 1.5rem;"><span>${Drupal.t('Total')}</span><span>€97.00</span></div>
                    <button style="width: 100%; background: linear-gradient(135deg, var(--ej-color-corporate, #233D63), var(--ej-color-innovation, #00A9A5)); color: white; border: none; padding: 1rem; border-radius: 10px; font-weight: 600; cursor: pointer;">${Drupal.t('Finalizar Compra')}</button>
                </div>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg>`,
            },
            {
                id: 'payment-methods',
                label: Drupal.t('Métodos de Pago'),
                category: 'commerce',
                content: `<div class="jaraba-payments" style="text-align: center; padding: 2rem;">
                    <p style="color: var(--ej-text-muted, #64748b); margin-bottom: 1rem; font-size: 0.9rem;">${Drupal.t('Métodos de pago aceptados')}</p>
                    <div style="display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap;">
                        <span style="background: var(--ej-bg-secondary, #f1f5f9); padding: 0.75rem 1.25rem; border-radius: 8px; font-weight: 600; color: #1a1f71;">VISA</span>
                        <span style="background: var(--ej-bg-secondary, #f1f5f9); padding: 0.75rem 1.25rem; border-radius: 8px; font-weight: 600; color: #eb001b;">MC</span>
                        <span style="background: var(--ej-bg-secondary, #f1f5f9); padding: 0.75rem 1.25rem; border-radius: 8px; font-weight: 600; color: #003087;">PayPal</span>
                        <span style="background: var(--ej-bg-secondary, #f1f5f9); padding: 0.75rem 1.25rem; border-radius: 8px; font-weight: 600; color: #635bff;">Stripe</span>
                    </div>
                </div>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><path d="M1 10h22"/></svg>`,
            },
        ];

        /**
         * Bloques Social - Redes y Confianza
         */
        const socialBlocks = [
            {
                id: 'social-links',
                label: Drupal.t('Redes Sociales'),
                category: 'social',
                // Sprint PB-4: usa tipo de componente con traits configurables
                content: { type: 'jaraba-social-links' },
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg>`,
            },
            {
                id: 'social-proof',
                label: Drupal.t('Social Proof'),
                category: 'social',
                content: `<div class="jaraba-proof" style="text-align: center; padding: 2rem; background: var(--ej-bg-secondary, #f8fafc); border-radius: 16px;">
                    <p style="color: var(--ej-text-muted, #64748b); margin-bottom: 1.5rem; font-size: 0.9rem;">${Drupal.t('Visto en')}</p>
                    <div style="display: flex; justify-content: center; gap: 3rem; align-items: center; flex-wrap: wrap; opacity: 0.6;">
                        <span style="font-size: 1.5rem; font-weight: 700; color: var(--ej-text-primary, #1e293b);">Forbes</span>
                        <span style="font-size: 1.5rem; font-weight: 700; color: var(--ej-text-primary, #1e293b);">TechCrunch</span>
                        <span style="font-size: 1.5rem; font-weight: 700; color: var(--ej-text-primary, #1e293b);">El País</span>
                        <span style="font-size: 1.5rem; font-weight: 700; color: var(--ej-text-primary, #1e293b);">Expansión</span>
                    </div>
                </div>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>`,
            },
            {
                id: 'sharing-buttons',
                label: Drupal.t('Botones Compartir'),
                category: 'social',
                content: `<div class="jaraba-share" style="display: flex; gap: 0.75rem; align-items: center; padding: 1rem;">
                    <span style="color: var(--ej-text-muted, #64748b); font-size: 0.9rem;">${Drupal.t('Compartir:')}</span>
                    <button style="background: #1877f2; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.85rem; cursor: pointer;">Facebook</button>
                    <button style="background: #1da1f2; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.85rem; cursor: pointer;">Twitter</button>
                    <button style="background: #0077b5; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.85rem; cursor: pointer;">LinkedIn</button>
                    <button style="background: #25d366; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.85rem; cursor: pointer;">WhatsApp</button>
                </div>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="M8.59 13.51l6.83 3.98M15.41 6.51l-6.82 3.98"/></svg>`,
            },
            {
                id: 'logo-carousel',
                label: Drupal.t('Carousel Logos'),
                category: 'social',
                content: `<div class="jaraba-logos" style="padding: 3rem 2rem; background: var(--ej-bg-secondary, #f8fafc);">
                    <p style="text-align: center; color: var(--ej-text-muted, #64748b); margin-bottom: 2rem; font-size: 0.875rem;">${Drupal.t('Confían en nosotros')}</p>
                    <div style="display: flex; justify-content: center; gap: 4rem; align-items: center; flex-wrap: wrap;">
                        <div style="width: 100px; height: 40px; background: var(--ej-border-color, #e2e8f0); border-radius: 4px; display: flex; align-items: center; justify-content: center; font-weight: 600; color: var(--ej-text-muted, #94a3b8);">Logo 1</div>
                        <div style="width: 100px; height: 40px; background: var(--ej-border-color, #e2e8f0); border-radius: 4px; display: flex; align-items: center; justify-content: center; font-weight: 600; color: var(--ej-text-muted, #94a3b8);">Logo 2</div>
                        <div style="width: 100px; height: 40px; background: var(--ej-border-color, #e2e8f0); border-radius: 4px; display: flex; align-items: center; justify-content: center; font-weight: 600; color: var(--ej-text-muted, #94a3b8);">Logo 3</div>
                        <div style="width: 100px; height: 40px; background: var(--ej-border-color, #e2e8f0); border-radius: 4px; display: flex; align-items: center; justify-content: center; font-weight: 600; color: var(--ej-text-muted, #94a3b8);">Logo 4</div>
                        <div style="width: 100px; height: 40px; background: var(--ej-border-color, #e2e8f0); border-radius: 4px; display: flex; align-items: center; justify-content: center; font-weight: 600; color: var(--ej-text-muted, #94a3b8);">Logo 5</div>
                    </div>
                </div>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="10" rx="2"/><circle cx="12" cy="12" r="3"/></svg>`,
            },
            {
                id: 'trust-badges',
                label: Drupal.t('Badges Confianza'),
                category: 'social',
                content: `<div class="jaraba-badges" style="display: flex; justify-content: center; gap: 2rem; padding: 2rem; flex-wrap: wrap;">
                    <div style="text-align: center;">
                        <div style="width: 64px; height: 64px; background: var(--ej-color-innovation, #00A9A5); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.5rem;"><span style="color: white; font-size: 1.5rem;">✓</span></div>
                        <span style="font-size: 0.75rem; color: var(--ej-text-muted, #64748b);">${Drupal.t('SSL Seguro')}</span>
                    </div>
                    <div style="text-align: center;">
                        <div style="width: 64px; height: 64px; background: var(--ej-color-corporate, #233D63); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.5rem;"><span style="color: white; font-size: 1.5rem;">🔒</span></div>
                        <span style="font-size: 0.75rem; color: var(--ej-text-muted, #64748b);">${Drupal.t('Pago Seguro')}</span>
                    </div>
                    <div style="text-align: center;">
                        <div style="width: 64px; height: 64px; background: var(--ej-color-impulse, #FF8C42); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.5rem;"><span style="color: white; font-size: 1.5rem;">⭐</span></div>
                        <span style="font-size: 0.75rem; color: var(--ej-text-muted, #64748b);">${Drupal.t('4.9/5 Rating')}</span>
                    </div>
                    <div style="text-align: center;">
                        <div style="width: 64px; height: 64px; background: var(--ej-color-passion, #E63946); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.5rem;"><span style="color: white; font-size: 1.5rem;">🎁</span></div>
                        <span style="font-size: 0.75rem; color: var(--ej-text-muted, #64748b);">${Drupal.t('Garantía 30 días')}</span>
                    </div>
                </div>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>`,
            },
        ];

        /**
         * Bloques Advanced - Contenido Avanzado
         */
        const advancedBlocks = [
            {
                id: 'timeline',
                label: Drupal.t('Timeline'),
                category: 'advanced',
                content: `<div class="jaraba-timeline" style="padding: 2rem; max-width: 600px;">
                    <div style="display: flex; gap: 1rem; margin-bottom: 2rem;">
                        <div style="width: 12px; height: 12px; background: var(--ej-color-innovation, #00A9A5); border-radius: 50%; margin-top: 5px; flex-shrink: 0;"></div>
                        <div style="border-left: 2px solid var(--ej-border-color, #e2e8f0); padding-left: 1.5rem; padding-bottom: 2rem;">
                            <span style="color: var(--ej-color-innovation, #00A9A5); font-size: 0.75rem; font-weight: 600;">2024</span>
                            <h4 style="margin: 0.5rem 0; color: var(--ej-text-primary, #1e293b);">${Drupal.t('Hito 1')}</h4>
                            <p style="color: var(--ej-text-muted, #64748b); margin: 0; font-size: 0.9rem;">${Drupal.t('Descripción del primer hito.')}</p>
                        </div>
                    </div>
                    <div style="display: flex; gap: 1rem; margin-bottom: 2rem;">
                        <div style="width: 12px; height: 12px; background: var(--ej-color-corporate, #233D63); border-radius: 50%; margin-top: 5px; flex-shrink: 0;"></div>
                        <div style="border-left: 2px solid var(--ej-border-color, #e2e8f0); padding-left: 1.5rem; padding-bottom: 2rem;">
                            <span style="color: var(--ej-color-corporate, #233D63); font-size: 0.75rem; font-weight: 600;">2025</span>
                            <h4 style="margin: 0.5rem 0; color: var(--ej-text-primary, #1e293b);">${Drupal.t('Hito 2')}</h4>
                            <p style="color: var(--ej-text-muted, #64748b); margin: 0; font-size: 0.9rem;">${Drupal.t('Descripción del segundo hito.')}</p>
                        </div>
                    </div>
                    <div style="display: flex; gap: 1rem;">
                        <div style="width: 12px; height: 12px; background: var(--ej-color-impulse, #FF8C42); border-radius: 50%; margin-top: 5px; flex-shrink: 0;"></div>
                        <div style="padding-left: 1.5rem;">
                            <span style="color: var(--ej-color-impulse, #FF8C42); font-size: 0.75rem; font-weight: 600;">2026</span>
                            <h4 style="margin: 0.5rem 0; color: var(--ej-text-primary, #1e293b);">${Drupal.t('Hito 3')}</h4>
                            <p style="color: var(--ej-text-muted, #64748b); margin: 0; font-size: 0.9rem;">${Drupal.t('Descripción del tercer hito.')}</p>
                        </div>
                    </div>
                </div>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="5" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="19" r="2"/><path d="M12 7v3M12 14v3"/></svg>`,
            },
            {
                id: 'tabs-content',
                label: Drupal.t('Tabs Contenido'),
                category: 'advanced',
                content: `<div class="jaraba-tabs" style="max-width: 700px;">
                    <div style="display: flex; border-bottom: 2px solid var(--ej-border-color, #e2e8f0);">
                        <button style="padding: 1rem 2rem; background: none; border: none; border-bottom: 2px solid var(--ej-color-corporate, #233D63); margin-bottom: -2px; color: var(--ej-color-corporate, #233D63); font-weight: 600; cursor: pointer;">${Drupal.t('Tab 1')}</button>
                        <button style="padding: 1rem 2rem; background: none; border: none; color: var(--ej-text-muted, #64748b); cursor: pointer;">${Drupal.t('Tab 2')}</button>
                        <button style="padding: 1rem 2rem; background: none; border: none; color: var(--ej-text-muted, #64748b); cursor: pointer;">${Drupal.t('Tab 3')}</button>
                    </div>
                    <div style="padding: 2rem; background: var(--ej-bg-secondary, #f8fafc);">
                        <h4 style="color: var(--ej-text-primary, #1e293b); margin-bottom: 0.5rem;">${Drupal.t('Contenido Tab 1')}</h4>
                        <p style="color: var(--ej-text-muted, #64748b); margin: 0;">${Drupal.t('Este es el contenido del primer tab. Haz clic para cambiar.')}</p>
                    </div>
                </div>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h4M12 6h8M4 12h8M14 12h6M4 18h6M12 18h8"/></svg>`,
            },
            {
                id: 'table-data',
                label: Drupal.t('Tabla Datos'),
                category: 'advanced',
                content: `<div class="jaraba-table" style="overflow-x: auto; padding: 1rem;">
                    <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">
                        <thead>
                            <tr style="background: var(--ej-color-corporate, #233D63); color: white;">
                                <th style="padding: 1rem; text-align: left;">${Drupal.t('Nombre')}</th>
                                <th style="padding: 1rem; text-align: left;">${Drupal.t('Categoría')}</th>
                                <th style="padding: 1rem; text-align: right;">${Drupal.t('Precio')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr style="border-bottom: 1px solid var(--ej-border-color, #e2e8f0);">
                                <td style="padding: 1rem;">${Drupal.t('Producto A')}</td>
                                <td style="padding: 1rem;">${Drupal.t('Electrónica')}</td>
                                <td style="padding: 1rem; text-align: right;">€199</td>
                            </tr>
                            <tr style="border-bottom: 1px solid var(--ej-border-color, #e2e8f0);">
                                <td style="padding: 1rem;">${Drupal.t('Producto B')}</td>
                                <td style="padding: 1rem;">${Drupal.t('Software')}</td>
                                <td style="padding: 1rem; text-align: right;">€49</td>
                            </tr>
                            <tr>
                                <td style="padding: 1rem;">${Drupal.t('Producto C')}</td>
                                <td style="padding: 1rem;">${Drupal.t('Servicios')}</td>
                                <td style="padding: 1rem; text-align: right;">€299</td>
                            </tr>
                        </tbody>
                    </table>
                </div>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M3 15h18M9 3v18"/></svg>`,
            },
            {
                id: 'code-embed',
                label: Drupal.t('Código Embed'),
                category: 'advanced',
                content: `<div class="jaraba-code" style="background: #1e293b; border-radius: 12px; padding: 1.5rem; max-width: 600px;">
                    <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem;">
                        <span style="width: 12px; height: 12px; background: #ff5f57; border-radius: 50%;"></span>
                        <span style="width: 12px; height: 12px; background: #febc2e; border-radius: 50%;"></span>
                        <span style="width: 12px; height: 12px; background: #28c840; border-radius: 50%;"></span>
                    </div>
                    <pre style="color: #e2e8f0; font-family: monospace; font-size: 0.875rem; margin: 0; overflow-x: auto;">
<code>${Drupal.t('// Tu código aquí')}
const example = () => {
  return 'Hello World';
};</code></pre>
                </div>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16,18 22,12 16,6"/><polyline points="8,6 2,12 8,18"/></svg>`,
            },
            {
                id: 'map-embed',
                label: Drupal.t('Mapa Embed'),
                category: 'advanced',
                content: `<div class="jaraba-map" style="aspect-ratio: 16/9; background: linear-gradient(135deg, var(--ej-bg-secondary, #f1f5f9) 0%, var(--ej-border-color, #e2e8f0) 100%); border-radius: 16px; display: flex; flex-direction: column; align-items: center; justify-content: center; max-width: 800px;">
                    <span style="font-size: 3rem; margin-bottom: 1rem;">📍</span>
                    <p style="color: var(--ej-text-muted, #64748b); margin: 0;">${Drupal.t('Inserta tu iframe de Google Maps aquí')}</p>
                </div>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>`,
            },
        ];

        /**
         * Bloques Utilities - Alertas y Funcionales
         */
        const utilitiesBlocks = [
            {
                id: 'alert-banner',
                label: Drupal.t('Banner Alerta'),
                category: 'utilities',
                content: `<div class="jaraba-alert" style="background: linear-gradient(135deg, var(--ej-color-impulse, #FF8C42) 0%, var(--ej-color-passion, #E63946) 100%); color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center;">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <span style="font-size: 1.25rem;">🎉</span>
                        <span style="font-weight: 600;">${Drupal.t('¡Oferta especial! 20% de descuento este fin de semana')}</span>
                    </div>
                    <button style="background: white; color: var(--ej-color-impulse, #FF8C42); border: none; padding: 0.5rem 1rem; border-radius: 6px; font-weight: 600; cursor: pointer;">${Drupal.t('Ver Oferta')}</button>
                </div>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><path d="M12 9v4M12 17h.01"/></svg>`,
            },
            {
                id: 'countdown',
                label: Drupal.t('Countdown'),
                category: 'utilities',
                content: `<div class="jaraba-countdown" style="text-align: center; padding: 3rem 2rem; background: var(--ej-color-corporate, #233D63); color: white; border-radius: 16px;">
                    <p style="margin-bottom: 1.5rem; opacity: 0.9;">${Drupal.t('La oferta termina en:')}</p>
                    <div style="display: flex; justify-content: center; gap: 1.5rem;">
                        <div><span style="font-size: 3rem; font-weight: 800; display: block;">02</span><span style="font-size: 0.75rem; opacity: 0.7;">${Drupal.t('DÍAS')}</span></div>
                        <span style="font-size: 3rem; font-weight: 300; opacity: 0.5;">:</span>
                        <div><span style="font-size: 3rem; font-weight: 800; display: block;">14</span><span style="font-size: 0.75rem; opacity: 0.7;">${Drupal.t('HORAS')}</span></div>
                        <span style="font-size: 3rem; font-weight: 300; opacity: 0.5;">:</span>
                        <div><span style="font-size: 3rem; font-weight: 800; display: block;">32</span><span style="font-size: 0.75rem; opacity: 0.7;">${Drupal.t('MIN')}</span></div>
                        <span style="font-size: 3rem; font-weight: 300; opacity: 0.5;">:</span>
                        <div><span style="font-size: 3rem; font-weight: 800; display: block;">15</span><span style="font-size: 0.75rem; opacity: 0.7;">${Drupal.t('SEG')}</span></div>
                    </div>
                </div>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/></svg>`,
            },
            {
                id: 'step-wizard',
                label: Drupal.t('Wizard Pasos'),
                category: 'utilities',
                content: `<div class="jaraba-wizard" style="padding: 2rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; max-width: 600px; margin: 0 auto;">
                        <div style="text-align: center;">
                            <div style="width: 40px; height: 40px; background: var(--ej-color-innovation, #00A9A5); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; margin: 0 auto 0.5rem;">1</div>
                            <span style="font-size: 0.75rem; color: var(--ej-color-innovation, #00A9A5);">${Drupal.t('Datos')}</span>
                        </div>
                        <div style="flex: 1; height: 2px; background: var(--ej-color-innovation, #00A9A5); margin: 0 1rem;"></div>
                        <div style="text-align: center;">
                            <div style="width: 40px; height: 40px; background: var(--ej-color-innovation, #00A9A5); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; margin: 0 auto 0.5rem;">2</div>
                            <span style="font-size: 0.75rem; color: var(--ej-color-innovation, #00A9A5);">${Drupal.t('Pago')}</span>
                        </div>
                        <div style="flex: 1; height: 2px; background: var(--ej-border-color, #e2e8f0); margin: 0 1rem;"></div>
                        <div style="text-align: center;">
                            <div style="width: 40px; height: 40px; background: var(--ej-border-color, #e2e8f0); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--ej-text-muted, #64748b); font-weight: 700; margin: 0 auto 0.5rem;">3</div>
                            <span style="font-size: 0.75rem; color: var(--ej-text-muted, #64748b);">${Drupal.t('Confirmación')}</span>
                        </div>
                    </div>
                </div>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="5" cy="12" r="3"/><circle cx="19" cy="12" r="3"/><path d="M8 12h8"/></svg>`,
            },
            {
                id: 'newsletter-form',
                label: Drupal.t('Newsletter'),
                category: 'utilities',
                content: `<div class="jaraba-newsletter" style="background: var(--ej-bg-secondary, #f8fafc); border-radius: 16px; padding: 3rem; text-align: center; max-width: 500px;">
                    <span style="font-size: 2.5rem; display: block; margin-bottom: 1rem;">📧</span>
                    <h3 style="color: var(--ej-text-primary, #1e293b); margin-bottom: 0.5rem;">${Drupal.t('Suscríbete a nuestra newsletter')}</h3>
                    <p style="color: var(--ej-text-muted, #64748b); margin-bottom: 1.5rem; font-size: 0.9rem;">${Drupal.t('Recibe las últimas novedades directamente en tu email.')}</p>
                    <form style="display: flex; gap: 0.5rem;">
                        <input type="email" placeholder="${Drupal.t('tu@email.com')}" style="flex: 1; padding: 1rem; border: 1px solid var(--ej-border-color, #e2e8f0); border-radius: 10px; font-size: 1rem;"/>
                        <button type="button" style="background: var(--ej-color-corporate, #233D63); color: white; border: none; padding: 1rem 1.5rem; border-radius: 10px; font-weight: 600; cursor: pointer;">${Drupal.t('Suscribir')}</button>
                    </form>
                </div>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg>`,
            },
            {
                id: 'cookie-banner',
                label: Drupal.t('Banner Cookies'),
                category: 'utilities',
                content: `<div class="jaraba-cookie" style="background: var(--ej-color-corporate, #233D63); color: white; padding: 1.5rem 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <div style="display: flex; align-items: center; gap: 1rem; flex: 1;">
                        <span style="font-size: 1.5rem;">🍪</span>
                        <p style="margin: 0; font-size: 0.9rem; opacity: 0.9;">${Drupal.t('Usamos cookies para mejorar tu experiencia. Al continuar navegando, aceptas nuestra')}<a href="#" style="color: var(--ej-color-innovation, #00A9A5);"> ${Drupal.t('política de cookies')}</a>.</p>
                    </div>
                    <div style="display: flex; gap: 0.75rem;">
                        <button style="background: transparent; color: white; border: 1px solid white; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer;">${Drupal.t('Rechazar')}</button>
                        <button style="background: var(--ej-color-innovation, #00A9A5); color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; font-weight: 600; cursor: pointer;">${Drupal.t('Aceptar')}</button>
                    </div>
                </div>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="8" cy="9" r="1" fill="currentColor"/><circle cx="15" cy="12" r="1" fill="currentColor"/><circle cx="10" cy="15" r="1" fill="currentColor"/></svg>`,
            },
        ];

        /**
         * Bloques Premium Extra - Efectos Avanzados
         */
        const premiumExtraBlocks = [
            {
                id: 'glassmorphism-card',
                label: Drupal.t('Card Glassmorphism'),
                category: 'premium',
                content: `<div class="jaraba-glass" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.2); border-radius: 24px; padding: 2.5rem; max-width: 400px; box-shadow: 0 8px 32px rgba(0,0,0,0.1);">
                    <div style="width: 60px; height: 60px; background: linear-gradient(135deg, var(--ej-color-innovation, #00A9A5), var(--ej-color-corporate, #233D63)); border-radius: 16px; display: flex; align-items: center; justify-content: center; margin-bottom: 1.5rem;">
                        <span style="color: white; font-size: 1.5rem;">✨</span>
                    </div>
                    <h3 style="color: var(--ej-text-primary, #1e293b); margin-bottom: 1rem; font-size: 1.5rem;">${Drupal.t('Diseño Premium')}</h3>
                    <p style="color: var(--ej-text-muted, #64748b); margin-bottom: 1.5rem; line-height: 1.6;">${Drupal.t('Efecto glassmorphism con desenfoque de fondo para interfaces modernas y elegantes.')}</p>
                    <button style="background: linear-gradient(135deg, var(--ej-color-corporate, #233D63), var(--ej-color-innovation, #00A9A5)); color: white; border: none; padding: 0.875rem 1.75rem; border-radius: 12px; font-weight: 600; cursor: pointer;">${Drupal.t('Explorar')}</button>
                </div>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="4" opacity="0.5"/><rect x="6" y="6" width="12" height="12" rx="2"/></svg>`,
            },
            {
                id: 'parallax-section',
                label: Drupal.t('Sección Parallax'),
                category: 'premium',
                content: `<section class="jaraba-parallax" style="position: relative; min-height: 400px; background: linear-gradient(135deg, var(--ej-color-corporate, #233D63) 0%, var(--ej-color-innovation, #00A9A5) 100%); display: flex; align-items: center; justify-content: center; overflow: hidden;">
                    <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; opacity: 0.1;">
                        <div style="position: absolute; width: 200px; height: 200px; border: 2px solid white; border-radius: 50%; top: 20%; left: 10%;"></div>
                        <div style="position: absolute; width: 150px; height: 150px; border: 2px solid white; border-radius: 50%; bottom: 20%; right: 15%;"></div>
                        <div style="position: absolute; width: 100px; height: 100px; background: rgba(255,255,255,0.1); border-radius: 50%; top: 40%; right: 30%;"></div>
                    </div>
                    <div style="text-align: center; color: white; z-index: 1; padding: 2rem;">
                        <h2 style="font-size: 3rem; font-weight: 800; margin-bottom: 1rem; text-shadow: 0 2px 10px rgba(0,0,0,0.2);">${Drupal.t('Sección Impactante')}</h2>
                        <p style="font-size: 1.25rem; opacity: 0.9; max-width: 600px; margin: 0 auto 2rem;">${Drupal.t('Crea secciones con efecto parallax para destacar tu contenido más importante.')}</p>
                        <button style="background: white; color: var(--ej-color-corporate, #233D63); border: none; padding: 1rem 2rem; border-radius: 50px; font-weight: 700; font-size: 1rem; cursor: pointer;">${Drupal.t('Descubrir Más')}</button>
                    </div>
                </section>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 15l6-6 4 4 8-8"/></svg>`,
            },
            {
                id: 'flip-card-3d',
                label: Drupal.t('Card 3D Flip'),
                category: 'premium',
                content: `<div class="jaraba-flip" style="perspective: 1000px; width: 300px; height: 400px;">
                    <div style="position: relative; width: 100%; height: 100%; transition: transform 0.8s; transform-style: preserve-3d;">
                        <div style="position: absolute; width: 100%; height: 100%; backface-visibility: hidden; background: linear-gradient(135deg, var(--ej-color-corporate, #233D63), var(--ej-color-innovation, #00A9A5)); border-radius: 20px; display: flex; flex-direction: column; align-items: center; justify-content: center; color: white; padding: 2rem;">
                            <span style="font-size: 4rem; margin-bottom: 1rem;">🎯</span>
                            <h3 style="font-size: 1.5rem; margin-bottom: 0.5rem;">${Drupal.t('Frente')}</h3>
                            <p style="opacity: 0.8; text-align: center;">${Drupal.t('Pasa el cursor para ver el reverso')}</p>
                        </div>
                        <div style="position: absolute; width: 100%; height: 100%; backface-visibility: hidden; background: white; border-radius: 20px; transform: rotateY(180deg); display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 2rem; box-shadow: 0 10px 40px rgba(0,0,0,0.15);">
                            <h3 style="color: var(--ej-text-primary, #1e293b); margin-bottom: 1rem;">${Drupal.t('Reverso')}</h3>
                            <p style="color: var(--ej-text-muted, #64748b); text-align: center; margin-bottom: 1.5rem;">${Drupal.t('Aquí puedes añadir más detalles o una llamada a la acción.')}</p>
                            <button style="background: var(--ej-color-impulse, #FF8C42); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 600;">${Drupal.t('Acción')}</button>
                        </div>
                    </div>
                </div>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>`,
            },
            {
                id: 'animated-counter',
                label: Drupal.t('Contador Animado'),
                category: 'premium',
                content: `<div class="jaraba-counter" style="display: flex; justify-content: center; gap: 4rem; padding: 4rem 2rem; background: var(--ej-bg-secondary, #f8fafc); flex-wrap: wrap;">
                    <div style="text-align: center;">
                        <span style="font-size: 4rem; font-weight: 900; background: linear-gradient(135deg, var(--ej-color-corporate, #233D63), var(--ej-color-innovation, #00A9A5)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; display: block;">500+</span>
                        <span style="color: var(--ej-text-muted, #64748b); font-size: 1rem; font-weight: 500;">${Drupal.t('Clientes Activos')}</span>
                    </div>
                    <div style="text-align: center;">
                        <span style="font-size: 4rem; font-weight: 900; background: linear-gradient(135deg, var(--ej-color-innovation, #00A9A5), var(--ej-color-impulse, #FF8C42)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; display: block;">98%</span>
                        <span style="color: var(--ej-text-muted, #64748b); font-size: 1rem; font-weight: 500;">${Drupal.t('Satisfacción')}</span>
                    </div>
                    <div style="text-align: center;">
                        <span style="font-size: 4rem; font-weight: 900; background: linear-gradient(135deg, var(--ej-color-impulse, #FF8C42), var(--ej-color-passion, #E63946)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; display: block;">24/7</span>
                        <span style="color: var(--ej-text-muted, #64748b); font-size: 1rem; font-weight: 500;">${Drupal.t('Soporte')}</span>
                    </div>
                </div>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20V10M18 20V4M6 20v-4"/></svg>`,
            },
            {
                id: 'video-testimonial',
                label: Drupal.t('Testimonio Video'),
                category: 'premium',
                content: `<div class="jaraba-video-testimonial" style="display: flex; gap: 2rem; align-items: center; padding: 3rem; background: white; border-radius: 24px; box-shadow: 0 10px 40px rgba(0,0,0,0.08); max-width: 800px; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 280px; aspect-ratio: 16/9; background: linear-gradient(135deg, var(--ej-color-corporate, #233D63), var(--ej-color-innovation, #00A9A5)); border-radius: 16px; display: flex; align-items: center; justify-content: center; position: relative;">
                        <div style="width: 80px; height: 80px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 4px 20px rgba(0,0,0,0.2);">
                            <span style="color: var(--ej-color-corporate, #233D63); font-size: 2rem; margin-left: 5px;">▶</span>
                        </div>
                    </div>
                    <div style="flex: 1; min-width: 280px;">
                        <div style="display: flex; gap: 0.25rem; margin-bottom: 1rem; color: #fbbf24;">⭐⭐⭐⭐⭐</div>
                        <p style="color: var(--ej-text-primary, #1e293b); font-size: 1.125rem; line-height: 1.7; margin-bottom: 1.5rem; font-style: italic;">"${Drupal.t('Esta plataforma ha transformado completamente la forma en que gestionamos nuestro negocio. Increíble.')}"</p>
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="width: 50px; height: 50px; background: var(--ej-bg-secondary, #f1f5f9); border-radius: 50%; display: flex; align-items: center; justify-content: center;">👤</div>
                            <div>
                                <strong style="color: var(--ej-text-primary, #1e293b); display: block;">${Drupal.t('María García')}</strong>
                                <span style="color: var(--ej-text-muted, #64748b); font-size: 0.875rem;">${Drupal.t('CEO, TechStartup')}</span>
                            </div>
                        </div>
                    </div>
                </div>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M10 9l5 3-5 3V9z"/></svg>`,
            },
            {
                id: 'features-list',
                label: Drupal.t('Lista Features'),
                category: 'premium',
                content: `<div class="jaraba-features" style="padding: 3rem 2rem; max-width: 600px;">
                    <h3 style="color: var(--ej-text-primary, #1e293b); margin-bottom: 2rem; font-size: 1.5rem;">${Drupal.t('Todo lo que necesitas')}</h3>
                    <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                        <div style="display: flex; align-items: flex-start; gap: 1rem;">
                            <div style="width: 28px; height: 28px; background: var(--ej-color-innovation, #00A9A5); border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;"><span style="color: white; font-size: 0.875rem;">✓</span></div>
                            <div><strong style="color: var(--ej-text-primary, #1e293b);">${Drupal.t('Configuración instantánea')}</strong><p style="color: var(--ej-text-muted, #64748b); margin: 0.25rem 0 0; font-size: 0.9rem;">${Drupal.t('Comienza en menos de 5 minutos')}</p></div>
                        </div>
                        <div style="display: flex; align-items: flex-start; gap: 1rem;">
                            <div style="width: 28px; height: 28px; background: var(--ej-color-innovation, #00A9A5); border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;"><span style="color: white; font-size: 0.875rem;">✓</span></div>
                            <div><strong style="color: var(--ej-text-primary, #1e293b);">${Drupal.t('Soporte 24/7')}</strong><p style="color: var(--ej-text-muted, #64748b); margin: 0.25rem 0 0; font-size: 0.9rem;">${Drupal.t('Equipo experto siempre disponible')}</p></div>
                        </div>
                        <div style="display: flex; align-items: flex-start; gap: 1rem;">
                            <div style="width: 28px; height: 28px; background: var(--ej-color-innovation, #00A9A5); border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;"><span style="color: white; font-size: 0.875rem;">✓</span></div>
                            <div><strong style="color: var(--ej-text-primary, #1e293b);">${Drupal.t('Integraciones ilimitadas')}</strong><p style="color: var(--ej-text-muted, #64748b); margin: 0.25rem 0 0; font-size: 0.9rem;">${Drupal.t('Conecta con tus herramientas favoritas')}</p></div>
                        </div>
                        <div style="display: flex; align-items: flex-start; gap: 1rem;">
                            <div style="width: 28px; height: 28px; background: var(--ej-color-innovation, #00A9A5); border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;"><span style="color: white; font-size: 0.875rem;">✓</span></div>
                            <div><strong style="color: var(--ej-text-primary, #1e293b);">${Drupal.t('Garantía de satisfacción')}</strong><p style="color: var(--ej-text-muted, #64748b); margin: 0.25rem 0 0; font-size: 0.9rem;">${Drupal.t('30 días de prueba sin compromiso')}</p></div>
                        </div>
                    </div>
                </div>`,
                media: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>`,
            },
        ];

        // Registrar bloques de layout
        layoutBlocks.forEach((block) => {
            blockManager.add(block.id, {
                label: block.label,
                category: block.category,
                content: block.content,
                media: block.media,
                attributes: { class: 'gjs-block-layout' },
            });
        });

        // Registrar bloques hero
        heroBlocks.forEach((block) => {
            blockManager.add(block.id, {
                label: block.label,
                category: block.category,
                content: block.content,
                media: block.media,
                attributes: { class: 'gjs-block-hero' },
            });
        });

        // Registrar bloques de contenido
        contentBlocks.forEach((block) => {
            blockManager.add(block.id, {
                label: block.label,
                category: block.category,
                content: block.content,
                media: block.media,
                attributes: { class: 'gjs-block-content' },
            });
        });

        // Registrar bloques CTA
        ctaBlocks.forEach((block) => {
            blockManager.add(block.id, {
                label: block.label,
                category: block.category,
                content: block.content,
                media: block.media,
                attributes: { class: 'gjs-block-cta' },
            });
        });

        // Registrar bloques Stats
        statsBlocks.forEach((block) => {
            blockManager.add(block.id, {
                label: block.label,
                category: block.category,
                content: block.content,
                media: block.media,
                attributes: { class: 'gjs-block-stats' },
            });
        });

        // Registrar bloques Pricing
        pricingBlocks.forEach((block) => {
            blockManager.add(block.id, {
                label: block.label,
                category: block.category,
                content: block.content,
                media: block.media,
                attributes: { class: 'gjs-block-pricing' },
            });
        });

        // Registrar bloques Contact
        contactBlocks.forEach((block) => {
            blockManager.add(block.id, {
                label: block.label,
                category: block.category,
                content: block.content,
                media: block.media,
                attributes: { class: 'gjs-block-contact' },
            });
        });

        // Registrar bloques Media
        mediaBlocks.forEach((block) => {
            blockManager.add(block.id, {
                label: block.label,
                category: block.category,
                content: block.content,
                media: block.media,
                attributes: { class: 'gjs-block-media' },
            });
        });

        // Registrar bloques Commerce
        commerceBlocks.forEach((block) => {
            blockManager.add(block.id, {
                label: block.label,
                category: block.category,
                content: block.content,
                media: block.media,
                attributes: { class: 'gjs-block-commerce' },
            });
        });

        // Registrar bloques Social
        socialBlocks.forEach((block) => {
            blockManager.add(block.id, {
                label: block.label,
                category: block.category,
                content: block.content,
                media: block.media,
                attributes: { class: 'gjs-block-social' },
            });
        });

        // Registrar bloques Advanced
        advancedBlocks.forEach((block) => {
            blockManager.add(block.id, {
                label: block.label,
                category: block.category,
                content: block.content,
                media: block.media,
                attributes: { class: 'gjs-block-advanced' },
            });
        });

        // Registrar bloques Utilities
        utilitiesBlocks.forEach((block) => {
            blockManager.add(block.id, {
                label: block.label,
                category: block.category,
                content: block.content,
                media: block.media,
                attributes: { class: 'gjs-block-utilities' },
            });
        });

        // Registrar bloques Interactivos (Dual Architecture)
        interactiveBlocks.forEach((block) => {
            blockManager.add(block.id, {
                label: block.label,
                category: block.category,
                content: block.content,
                media: block.media,
                attributes: { class: 'gjs-block-interactive' },
            });
        });

        // Registrar bloques Premium Extra
        premiumExtraBlocks.forEach((block) => {
            blockManager.add(block.id, {
                label: block.label,
                category: block.category,
                content: block.content,
                media: block.media,
                attributes: { class: 'gjs-block-premium' },
            });
        });

        const totalBlocks = basicBlocks.length + layoutBlocks.length + heroBlocks.length + contentBlocks.length + ctaBlocks.length + statsBlocks.length + pricingBlocks.length + contactBlocks.length + mediaBlocks.length + commerceBlocks.length + socialBlocks.length + advancedBlocks.length + utilitiesBlocks.length + premiumExtraBlocks.length;
        console.log('🎯 Jaraba Blocks Plugin: ' + totalBlocks + ' bloques estáticos cargados.');

        /**
         * Carga dinámica de bloques desde Template Registry API.
         * 
         * Esta función consulta el SSoT (Single Source of Truth) y sincroniza
         * los bloques del canvas con el catálogo central de templates.
         * 
         * @param {Object} blockManager - GrapesJS Block Manager.
         */
        const loadBlocksFromRegistry = async (blockManager) => {
            const apiUrl = '/api/v1/page-builder/templates?format=blocks';

            try {
                const response = await fetch(apiUrl, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const result = await response.json();

                if (!result.success || !Array.isArray(result.data)) {
                    throw new Error('Respuesta API inválida');
                }

                const apiBlocks = result.data;
                let newBlocksCount = 0;
                let lockedBlocksCount = 0;

                apiBlocks.forEach((block) => {
                    // Solo añadir si no existe ya (evitar duplicados con bloques estáticos)
                    const existingBlock = blockManager.get(block.id);
                    if (!existingBlock) {
                        const isLocked = block.isLocked || false;
                        if (isLocked) lockedBlocksCount++;

                        // Modificar label para bloques bloqueados
                        const blockLabel = isLocked
                            ? `🔒 ${block.label}`
                            : block.label;

                        blockManager.add(block.id, {
                            label: blockLabel,
                            category: block.category,
                            content: isLocked ? '' : block.content, // No content si está bloqueado
                            media: block.media || '<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>',
                            attributes: {
                                ...block.attributes,
                                'data-is-locked': isLocked ? 'true' : 'false',
                                'data-required-plan': block.requiredPlan || 'free',
                            },
                            // Deshabilitar arrastre si está bloqueado
                            disable: isLocked,
                        });
                        newBlocksCount++;
                    }
                });

                console.log(`✅ Template Registry SSoT: ${apiBlocks.length} templates, ${newBlocksCount} nuevos bloques (${lockedBlocksCount} bloqueados por plan).`);

                // Dispatch evento para que otros módulos sepan que los bloques están listos
                document.dispatchEvent(new CustomEvent('jaraba:blocks:loaded', {
                    detail: {
                        staticBlocks: totalBlocks,
                        apiBlocks: apiBlocks.length,
                        newBlocks: newBlocksCount,
                        lockedBlocks: lockedBlocksCount,
                        total: totalBlocks + newBlocksCount,
                    },
                }));

            } catch (error) {
                console.warn('⚠️ Template Registry no disponible, usando bloques estáticos:', error.message);
                // Los bloques estáticos ya están cargados, fallback silencioso
            }
        };

        /**
         * Tracking de uso de bloques para analytics.
         * 
         * Envía evento cuando un bloque es añadido al canvas.
         */
        const setupBlockAnalytics = () => {
            editor.on('block:drag:stop', (component, block) => {
                if (!component || !block) return;

                const blockId = block.get('id') || 'unknown';
                const blockCategory = block.get('category') || 'uncategorized';

                // Tracking básico a consola (preparado para backend)
                console.log(`📊 Block Used: ${blockId} (${blockCategory})`);

                // Enviar a API si está disponible
                if (drupalSettings.jaraba_page_builder?.analyticsEnabled) {
                    fetch('/api/v1/page-builder/analytics/block-used', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({
                            block_id: blockId,
                            category: blockCategory,
                            page_id: drupalSettings.jaraba_page_builder?.pageId || null,
                            timestamp: new Date().toISOString(),
                        }),
                    }).catch(() => {
                        // Silencioso si falla
                    });
                }
            });
        };

        // Ejecutar carga dinámica tras inicialización del canvas
        editor.on('load', () => {
            // Delay mínimo para asegurar que el DOM está listo
            setTimeout(() => {
                try {
                    loadBlocksFromRegistry(blockManager);
                } catch (e) {
                    console.warn('[Jaraba Blocks] Error cargando registry:', e.message);
                }
                try {
                    setupBlockAnalytics();
                } catch (e) {
                    // Silencioso.
                }
            }, 100);

            // Sprint A2: Actualizar thumbnails a duotone (independiente del registry).
            setTimeout(() => {
                try {
                    if (Drupal.jarabaThumbnails) {
                        upgradeThumbnailsToDuotone(blockManager);
                        console.log('[Jaraba Thumbnails] Upgrade duotone ejecutado.');
                    } else {
                        console.warn('[Jaraba Thumbnails] Registry no disponible.');
                    }
                } catch (e) {
                    console.warn('[Jaraba Thumbnails] Error:', e.message);
                }
            }, 300);
        });

        /**
         * Sprint A2: Actualiza los thumbnails de todos los bloques registrados
         * a la versión duotone del registro centralizado.
         *
         * Solo reemplaza si existe una versión duotone en el registro.
         * Los bloques sin match conservan su SVG inline original.
         *
         * @param {Object} bm Block Manager de GrapesJS.
         */
        const upgradeThumbnailsToDuotone = (bm) => {
            const allBlocks = bm.getAll();
            let upgraded = 0;

            allBlocks.forEach((block) => {
                const blockId = block.getId();
                const duotoneSvg = Drupal.jarabaThumbnails.get(blockId);
                if (duotoneSvg) {
                    block.set('media', duotoneSvg);
                    upgraded++;
                }
            });

            if (upgraded > 0) {
                console.log(`[Jaraba Thumbnails] ${upgraded} bloques actualizados a duotone.`);
            }

            // Inyectar iconos de categoría con delay adicional.
            // Las categorías se renderizan al DOM ligeramente después de los bloques.
            setTimeout(() => {
                injectCategoryIcons();
                // Retry por si el sidebar aún no está completo.
                setTimeout(() => injectCategoryIcons(), 1000);
            }, 400);
        };

        /**
         * Sprint A2: Inyecta iconos SVG duotone en los encabezados de categoría
         * del block manager del sidebar.
         *
         * Estructura DOM de GrapesJS:
         *   .gjs-block-category
         *     .gjs-title
         *       i.gjs-caret-icon.fa.fa-caret-right
         *       "Básico"  ← text node
         */
        const injectCategoryIcons = () => {
            const container = document.getElementById('gjs-blocks-container');
            if (!container) {
                console.warn('[Jaraba Thumbnails] #gjs-blocks-container no encontrado.');
                return;
            }

            const categories = container.querySelectorAll('.gjs-block-category');
            console.log(`[Jaraba Thumbnails] injectCategoryIcons: ${categories.length} categorías encontradas.`);

            // Mapa de etiquetas UI → IDs de categoría.
            // Usa strings directos (no Drupal.t()) ya que los nombres vienen del DOM.
            const categoryMap = {
                'Básico': 'basic',
                'Hero': 'hero',
                'Características': 'features',
                'Llamadas a Acción': 'cta',
                'Estadísticas': 'stats',
                'Testimonios': 'testimonials',
                'Precios': 'pricing',
                'Equipo': 'team',
                'FAQ': 'faq',
                'Pestañas': 'tabs',
                'Cuenta Regresiva': 'countdown',
                'Timeline': 'timeline',
                'Contacto': 'contact',
                'Contenido': 'content',
                'Multimedia': 'media',
                'Premium': 'premium',
                'Comercio': 'commerce',
                'Social': 'social',
                'Avanzado': 'advanced',
                'Utilidades': 'utilities',
                'Diseño': 'layout',
                'Layout': 'layout',
            };

            let injected = 0;

            categories.forEach((catEl) => {
                const titleEl = catEl.querySelector('.gjs-title');
                if (!titleEl) return;

                // Extraer solo el texto del nodo hijo texto (no el del <i> caret).
                let text = '';
                titleEl.childNodes.forEach((node) => {
                    if (node.nodeType === Node.TEXT_NODE) {
                        const t = node.textContent.trim();
                        if (t) text = t;
                    }
                });

                if (!text) return;

                const catId = categoryMap[text];
                if (!catId) return;

                const catSvg = Drupal.jarabaThumbnails.getCategory(catId);
                if (!catSvg) return;

                // No duplicar iconos si ya inyectado.
                if (titleEl.querySelector('.jaraba-cat-icon')) return;

                // Crear wrapper del icono.
                const iconWrapper = document.createElement('span');
                iconWrapper.className = 'jaraba-cat-icon';
                iconWrapper.innerHTML = catSvg
                    .replace('width="40"', 'width="18"')
                    .replace('height="40"', 'height="18"');

                // Insertar después del <i> caret y antes del texto.
                const caretIcon = titleEl.querySelector('.gjs-caret-icon');
                if (caretIcon && caretIcon.nextSibling) {
                    titleEl.insertBefore(iconWrapper, caretIcon.nextSibling);
                } else {
                    titleEl.insertBefore(iconWrapper, titleEl.firstChild);
                }

                injected++;
            });

            if (injected > 0) {
                console.log(`[Jaraba Thumbnails] ${injected} iconos de categoría inyectados.`);
            }
        };
    };

    // Registrar plugin en GrapesJS
    if (typeof grapesjs !== 'undefined') {
        grapesjs.plugins.add('jaraba-blocks', jarabaBlocksPlugin);
    }

})(Drupal, drupalSettings);

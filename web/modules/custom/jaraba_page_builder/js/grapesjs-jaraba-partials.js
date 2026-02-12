/**
 * @file
 * Jaraba Canvas Editor v3 - Componentes Parciales.
 *
 * Registra los componentes estructurales (header, footer, content-zone)
 * que NO son arrastrables pero SÍ son editables via panel de traits.
 *
 * @see docs/tecnicos/20260204b-Canvas_Editor_v3_Arquitectura_Maestra.md
 */

(function (Drupal, drupalSettings) {
    'use strict';

    /**
     * Plugin GrapesJS para componentes parciales Jaraba.
     *
     * @param {Object} editor - Instancia del editor GrapesJS.
     * @param {Object} opts - Opciones de configuración.
     */
    const jarabaPartialsPlugin = (editor, opts = {}) => {
        const domComponents = editor.DomComponents;

        // Obtener variantes desde drupalSettings con validación de array
        const rawHeaderVariants = opts.headerVariants || drupalSettings.jarabaCanvas?.headerVariants;
        const headerVariants = Array.isArray(rawHeaderVariants) ? rawHeaderVariants : [
            { id: 'standard', label: 'Estándar' },
            { id: 'centered', label: 'Centrado' },
            { id: 'minimal', label: 'Mínimo' },
            { id: 'mega', label: 'Mega Menú' },
            { id: 'transparent', label: 'Transparente' },
        ];

        const rawFooterVariants = opts.footerVariants || drupalSettings.jarabaCanvas?.footerVariants;
        const footerVariants = Array.isArray(rawFooterVariants) ? rawFooterVariants : [
            { id: 'simple', label: 'Simple' },
            { id: 'columns', label: 'Columnas' },
            { id: 'mega', label: 'Mega Footer' },
            { id: 'minimal', label: 'Mínimo' },
            { id: 'cta', label: 'Con CTA' },
        ];

        /**
         * Componente HEADER - Editable pero NO arrastrable.
         */
        domComponents.addType('jaraba-header', {
            model: {
                defaults: {
                    tagName: 'header',
                    // Restricciones de interacción
                    draggable: false,
                    droppable: false,
                    removable: false,
                    copyable: false,
                    selectable: true,
                    hoverable: true,
                    highlightable: true,
                    // Clases
                    classes: ['jaraba-header'],
                    // Traits editables
                    traits: [
                        {
                            type: 'select',
                            name: 'header-type',
                            label: 'Tipo de Encabezado',
                            options: headerVariants.map(v => ({ value: v.id, name: v.label })),
                        },
                        {
                            type: 'checkbox',
                            name: 'sticky',
                            label: 'Fijo al hacer scroll',
                        },
                        {
                            type: 'text',
                            name: 'cta-text',
                            label: 'Texto del botón CTA',
                            placeholder: 'Contactar',
                        },
                        {
                            type: 'text',
                            name: 'cta-url',
                            label: 'URL del botón CTA',
                            placeholder: '/contacto',
                        },
                        {
                            type: 'checkbox',
                            name: 'topbar-enabled',
                            label: 'Mostrar barra superior',
                        },
                        {
                            type: 'text',
                            name: 'topbar-text',
                            label: 'Texto barra superior',
                            placeholder: '🎉 Oferta especial',
                        },
                        {
                            type: 'button',
                            name: 'edit-menu',
                            label: 'Editar Menú →',
                            text: 'Abrir Editor de Menú',
                            command: 'jaraba:open-menu-editor',
                        },
                    ],
                    // Atributos data-*
                    attributes: {
                        'data-gjs-type': 'jaraba-header',
                        'data-partial-type': 'header',
                    },
                },

                init() {
                    // Escuchar cambios en traits para hot-swap
                    this.on('change:attributes', this.onAttributeChange);
                },

                /**
                 * Maneja cambios en atributos para hot-swap.
                 */
                onAttributeChange() {
                    const attrs = this.getAttributes();
                    const headerType = attrs['header-type'];

                    // Notificar al iframe de preview
                    this.notifyPreview('JARABA_HEADER_CHANGE', {
                        variant: headerType,
                        sticky: attrs.sticky === 'true',
                        ctaText: attrs['cta-text'],
                        ctaUrl: attrs['cta-url'],
                        topbarEnabled: attrs['topbar-enabled'] === 'true',
                        topbarText: attrs['topbar-text'],
                    });

                    // Mostrar toast de advertencia (cambio global)
                    this.showGlobalChangeWarning('encabezado');
                },

                /**
                 * Notifica al iframe de preview via postMessage.
                 *
                 * @param {string} type - Tipo de mensaje.
                 * @param {Object} data - Datos del mensaje.
                 */
                notifyPreview(type, data) {
                    const iframe = document.querySelector('.canvas-editor__preview iframe');
                    if (iframe && iframe.contentWindow) {
                        iframe.contentWindow.postMessage({ type, ...data }, '*');
                    }
                },

                /**
                 * Muestra advertencia de cambio global.
                 *
                 * @param {string} element - Nombre del elemento.
                 */
                showGlobalChangeWarning(element) {
                    const message = Drupal.t('Los cambios en el @element se aplicarán a TODAS las páginas de tu sitio.', {
                        '@element': element,
                    });

                    // Mostrar toast si existe el sistema de notificaciones
                    if (typeof Drupal.announce === 'function') {
                        Drupal.announce(message);
                    }

                    // También mostrar en consola para debugging
                    console.info('[Jaraba Canvas] Cambio global:', message);
                },
            },

            view: {
                events: {
                    click: 'onClick',
                },

                onClick(e) {
                    // Seleccionar el componente para edición
                    this.em.setSelected(this.model);
                    e.stopPropagation();
                },
            },
        });

        /**
         * Componente FOOTER - Editable pero NO arrastrable.
         */
        domComponents.addType('jaraba-footer', {
            model: {
                defaults: {
                    tagName: 'footer',
                    draggable: false,
                    droppable: false,
                    removable: false,
                    copyable: false,
                    selectable: true,
                    hoverable: true,
                    highlightable: true,
                    classes: ['jaraba-footer'],
                    traits: [
                        {
                            type: 'select',
                            name: 'footer-type',
                            label: 'Tipo de Pie de Página',
                            options: footerVariants.map(v => ({ value: v.id, name: v.label })),
                        },
                        {
                            type: 'checkbox',
                            name: 'show-social',
                            label: 'Mostrar iconos sociales',
                        },
                        {
                            type: 'checkbox',
                            name: 'show-newsletter',
                            label: 'Mostrar formulario newsletter',
                        },
                        {
                            type: 'text',
                            name: 'copyright',
                            label: 'Texto copyright',
                            placeholder: '© 2026 Mi Empresa',
                        },
                    ],
                    attributes: {
                        'data-gjs-type': 'jaraba-footer',
                        'data-partial-type': 'footer',
                    },
                },

                init() {
                    this.on('change:attributes', this.onAttributeChange);
                },

                onAttributeChange() {
                    const attrs = this.getAttributes();
                    const footerType = attrs['footer-type'];

                    this.notifyPreview('JARABA_FOOTER_CHANGE', {
                        variant: footerType,
                        showSocial: attrs['show-social'] === 'true',
                        showNewsletter: attrs['show-newsletter'] === 'true',
                        copyright: attrs.copyright,
                    });

                    this.showGlobalChangeWarning('pie de página');
                },

                notifyPreview(type, data) {
                    const iframe = document.querySelector('.canvas-editor__preview iframe');
                    if (iframe && iframe.contentWindow) {
                        iframe.contentWindow.postMessage({ type, ...data }, '*');
                    }
                },

                showGlobalChangeWarning(element) {
                    const message = Drupal.t('Los cambios en el @element se aplicarán a TODAS las páginas de tu sitio.', {
                        '@element': element,
                    });
                    if (typeof Drupal.announce === 'function') {
                        Drupal.announce(message);
                    }
                    console.info('[Jaraba Canvas] Cambio global:', message);
                },
            },

            view: {
                events: {
                    click: 'onClick',
                },

                onClick(e) {
                    this.em.setSelected(this.model);
                    e.stopPropagation();
                },
            },
        });

        /**
         * Componente CONTENT-ZONE - Zona droppable para bloques.
         */
        domComponents.addType('jaraba-content-zone', {
            model: {
                defaults: {
                    tagName: 'main',
                    // NO arrastrable, pero SÍ acepta bloques
                    draggable: false,
                    droppable: true,
                    removable: false,
                    copyable: false,
                    selectable: false,
                    classes: ['jaraba-content-zone'],
                    attributes: {
                        'data-gjs-type': 'jaraba-content-zone',
                        'role': 'main',
                    },
                },
            },

            view: {
                onRender() {
                    // Añadir indicador visual de drop zone cuando está vacío
                    if (!this.model.components().length) {
                        this.el.innerHTML = `
              <div class="jaraba-content-zone__empty">
                <p>${Drupal.t('Arrastra bloques aquí para construir tu página')}</p>
              </div>
            `;
                    }
                },
            },
        });

        /**
         * Comando para abrir el editor de menú.
         */
        editor.Commands.add('jaraba:open-menu-editor', {
            run(editor, sender) {
                // Usar el sistema de modales de Drupal
                if (typeof Drupal.dialog === 'function') {
                    const dialogContent = document.createElement('div');
                    dialogContent.id = 'jaraba-menu-editor-modal';
                    dialogContent.innerHTML = `
            <p>${Drupal.t('Cargando editor de menú...')}</p>
          `;

                    const dialog = Drupal.dialog(dialogContent, {
                        title: Drupal.t('Editor de Navegación'),
                        width: 800,
                        height: 600,
                        buttons: [
                            {
                                text: Drupal.t('Cerrar'),
                                click: function () {
                                    $(this).dialog('close');
                                },
                            },
                        ],
                    });

                    dialog.showModal();

                    // Cargar contenido del editor de menú via AJAX
                    fetch('/admin/structure/site-menu/editor?ajax=1')
                        .then(response => response.text())
                        .then(html => {
                            dialogContent.innerHTML = html;
                            Drupal.attachBehaviors(dialogContent);
                        })
                        .catch(error => {
                            dialogContent.innerHTML = `
                <p class="error">${Drupal.t('Error al cargar el editor de menú.')}</p>
              `;
                            console.error('Error cargando menu editor:', error);
                        });
                } else {
                    // Fallback: abrir en nueva pestaña
                    window.open('/admin/structure/site-menu', '_blank');
                }
            },
        });

        console.log('Jaraba Partials Plugin inicializado.');
    };

    // Registrar plugin en GrapesJS
    if (typeof grapesjs !== 'undefined') {
        grapesjs.plugins.add('jaraba-partials', jarabaPartialsPlugin);
    }

})(Drupal, drupalSettings);

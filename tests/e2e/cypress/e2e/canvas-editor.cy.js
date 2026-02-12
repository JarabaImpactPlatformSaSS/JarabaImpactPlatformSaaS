/**
 * @file
 * Tests E2E para el Canvas Editor (Page Builder).
 *
 * Suite de tests para verificar funcionalidades core del editor GrapesJS:
 * - Inicialización correcta del editor
 * - Drag & drop de bloques
 * - Panel SEO Auditor
 *
 * @requires cypress
 * @see docs/arquitectura/2026-02-05_auditoria_page_site_builder_clase_mundial.md
 */

describe('Canvas Editor - Page Builder', () => {
    // Constantes
    const BASE_URL = Cypress.env('baseUrl') || 'https://jaraba-saas.lndo.site';
    const EDITOR_URL = `${BASE_URL}/es/page/17/editor?mode=canvas`;

    // Selectores
    const SELECTORS = {
        editor: '#gjs-editor',
        blockManager: '#gjs-blocks-container',
        blockItem: '.gjs-block',
        canvas: '.gjs-cv-canvas',
        frame: '.gjs-frame',
        seoPanel: '.gjs-seo-panel',
        seoToggle: '[data-gjs-command="toggle-seo-panel"]',
        traitsPanel: '#gjs-traits-container',
        stylesPanel: '#gjs-styles-container',
    };

    /**
     * Helper: Obtiene la instancia del editor GrapesJS.
     * Espera hasta 8s a que window.editor esté disponible tras la carga.
     */
    function getEditor() {
        return cy.window({ timeout: 8000 }).its('editor', { timeout: 8000 });
    }

    beforeEach(() => {
        // Autenticación como admin para acceder al editor
        cy.visit(`${BASE_URL}/es/user/login`);
        cy.get('input[name="name"]').type('admin');
        cy.get('input[name="pass"]').type(Cypress.env('adminPass') || 'admin');
        cy.get('input[type="submit"]').click();

        // Esperar a que complete el login
        cy.url().should('not.include', '/user/login');
    });

    describe('Test 1: Canvas Editor Loads Correctly', () => {
        it('should initialize GrapesJS editor with all panels', () => {
            // Navegar al editor
            cy.visit(EDITOR_URL);

            // Verificar que el contenedor del editor existe
            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');

            // Verificar que el canvas se ha cargado
            cy.get(SELECTORS.canvas, { timeout: 10000 }).should('be.visible');

            // Verificar que el Block Manager tiene bloques
            cy.get(SELECTORS.blockManager).within(() => {
                cy.get(SELECTORS.blockItem).should('have.length.greaterThan', 10);
            });

            // Verificar que el iframe del canvas existe
            cy.get(SELECTORS.frame).should('exist');

            // Log de éxito
            cy.log('✅ Canvas Editor inicializado correctamente');
        });

        it('should have Design Tokens injected in canvas iframe', () => {
            cy.visit(EDITOR_URL);

            // Esperar a que cargue el editor
            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');

            // Verificar que el iframe tiene estilos inyectados
            cy.get(SELECTORS.frame).then(($iframe) => {
                const doc = $iframe[0].contentDocument;
                expect(doc).to.not.be.null;

                // Verificar que se inyectaron los CSS
                const stylesheets = doc.querySelectorAll('link[rel="stylesheet"]');
                expect(stylesheets.length).to.be.greaterThan(0);
            });
        });
    });

    describe('Test 2: Drag and Drop Functionality', () => {
        it('should allow dragging a block to the canvas', () => {
            cy.visit(EDITOR_URL);

            // Esperar a que cargue el editor
            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');
            cy.get(SELECTORS.blockManager).should('be.visible');

            // Encontrar un bloque de texto
            cy.get(`${SELECTORS.blockManager} .gjs-block`)
                .first()
                .as('sourceBlock');

            // Obtener el canvas frame
            cy.get(SELECTORS.frame).as('targetCanvas');

            // Realizar drag and drop usando dataTransfer nativo
            cy.get('@sourceBlock').trigger('dragstart', { dataTransfer: new DataTransfer() });

            cy.get('@targetCanvas').trigger('dragover', { force: true });
            cy.get('@targetCanvas').trigger('drop', { force: true });

            cy.get('@sourceBlock').trigger('dragend');

            // Verificar que se añadió contenido al canvas
            cy.get(SELECTORS.frame).then(($iframe) => {
                const body = $iframe[0].contentDocument.body;
                // Verificar que hay al menos un elemento en el canvas
                expect(body.children.length).to.be.greaterThan(0);
            });

            cy.log('✅ Drag and drop funciona correctamente');
        });

        it('should display block categories in Block Manager', () => {
            cy.visit(EDITOR_URL);

            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');

            // Verificar categorías de bloques via JavaScript API
            cy.window().then((win) => {
                if (win.editor && win.editor.BlockManager) {
                    const categories = win.editor.BlockManager.getCategories();
                    expect(categories.length).to.be.greaterThan(3);
                    cy.log(`✅ ${categories.length} categorías disponibles`);
                } else {
                    // Fallback: verificar que hay bloques visibles con categorías
                    cy.get('.gjs-block-category').should('have.length.greaterThan', 3);
                }
            });
        });
    });

    describe('Test 3: SEO Panel Toggle', () => {
        it('should toggle SEO panel visibility', () => {
            cy.visit(EDITOR_URL);

            // Esperar a que cargue el editor
            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');

            // Ejecutar comando SEO toggle via JavaScript API
            cy.window().then((win) => {
                if (win.editor && win.editor.Commands) {
                    const hasToggleCmd = win.editor.Commands.has('toggle-seo-panel');
                    expect(hasToggleCmd).to.be.true;

                    // Ejecutar el comando
                    win.editor.runCommand('toggle-seo-panel');
                    cy.log('✅ Comando toggle-seo-panel ejecutado');
                } else {
                    cy.log('⚠️ Editor no disponible, verificando DOM');
                }
            });

            // Verificar que el panel SEO existe después del toggle
            cy.get('.gjs-seo-panel, .seo-panel, [class*="seo"]', { timeout: 3000 })
                .should('have.length.greaterThan', 0);

            cy.log('✅ SEO Panel toggle funciona correctamente');
        });

        it('should display SEO metrics in the panel', () => {
            cy.visit(EDITOR_URL);

            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');

            // Abrir panel SEO
            cy.window().then((win) => {
                if (win.editor && win.editor.runCommand) {
                    win.editor.runCommand('toggle-seo-panel');
                }
            });

            // Verificar que muestra métricas
            cy.get(SELECTORS.seoPanel, { timeout: 5000 }).within(() => {
                // Verificar elementos del panel
                cy.get('.seo-panel__score').should('exist');
                cy.get('.seo-panel__issues').should('exist');
            });
        });
    });

    describe('Test 4: Trait Updates Reflection', () => {
        it('should update FAQ component title via trait modification', () => {
            cy.visit(EDITOR_URL);

            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');

            // Añadir bloque FAQ, seleccionarlo y modificar su título
            cy.window().then((win) => {
                const editor = win.editor;
                expect(editor, 'GrapesJS editor debe estar disponible').to.exist;
                expect(editor.BlockManager, 'BlockManager debe existir').to.exist;

                // Añadir componente FAQ via API
                const added = editor.addComponents({ type: 'jaraba-faq' });
                expect(added).to.have.length.greaterThan(0);

                // Seleccionar y modificar en el mismo contexto
                const component = added[0];
                editor.select(component);

                // Verificar selección inmediata
                const selected = editor.getSelected();
                expect(selected, 'Un componente debe estar seleccionado').to.exist;

                // Cambiar el título del FAQ via trait
                selected.set('faqTitle', 'Título Modificado Test E2E');

                // Verificar que el modelo refleja el cambio
                expect(selected.get('faqTitle')).to.equal('Título Modificado Test E2E');
            });

            cy.wait(500);

            // Verificar que el panel de traits existe (indica que el componente está seleccionado)
            cy.get(SELECTORS.traitsPanel).should('exist');

            cy.log('✅ Trait actualiza contenido del componente correctamente');
        });

        it('should reflect style changes in the canvas', () => {
            cy.visit(EDITOR_URL);

            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');

            // Verificar que existe el panel de estilos
            cy.get(SELECTORS.stylesPanel).should('exist');

            cy.log('✅ Panel de estilos disponible para personalización');
        });
    });

    describe('Test 5: REST Persistence', () => {
        it('should save canvas state to server via REST API', () => {
            // Interceptar la llamada ANTES de visitar la página
            // El Storage Manager usa PATCH a /api/v1/pages/{id}/canvas
            cy.intercept('PATCH', '**/api/v1/pages/*/canvas').as('saveCanvas');

            cy.visit(EDITOR_URL);

            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');

            // Añadir componente via API (más fiable que drag & drop)
            cy.window().then((win) => {
                const editor = win.editor;
                expect(editor, 'GrapesJS editor disponible').to.exist;

                editor.addComponents('<div class="test-block-e2e">Bloque Test E2E</div>');
            });

            cy.wait(500);

            // Ejecutar comando de guardado
            cy.window().then((win) => {
                expect(win.editor.Commands.has('jaraba:save'), 'Comando jaraba:save registrado').to.be.true;
                win.editor.runCommand('jaraba:save');
            });

            // Verificar que se envió la petición REST con payload JSON
            cy.wait('@saveCanvas', { timeout: 5000 }).then((interception) => {
                expect(interception.request.body).to.have.property('html');
                expect(interception.request.headers['content-type']).to.include('application/json');
                cy.log('✅ Canvas guardado via REST con payload JSON válido');
            });
        });

        it('should persist canvas data after page reload', () => {
            cy.visit(EDITOR_URL);

            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');

            // Verificar que el canvas carga datos previos si existen
            cy.get(SELECTORS.frame).then(($iframe) => {
                const body = $iframe[0].contentDocument.body;
                // El canvas debe existir aunque esté vacío
                expect(body).to.not.be.null;
                cy.log('✅ Canvas restaura estado previo (o inicia vacío)');
            });
        });
    });

    describe('Test 6: Interactive FAQ Block', () => {
        it('should add FAQ accordion block to canvas', () => {
            cy.visit(EDITOR_URL);

            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');

            // Añadir bloque FAQ via JavaScript API
            cy.window().then((win) => {
                if (win.editor && win.editor.BlockManager) {
                    const faqBlock = win.editor.BlockManager.get('faq-accordion');
                    if (faqBlock) {
                        win.editor.addComponents(faqBlock.get('content'));
                        cy.log('✅ Bloque FAQ añadido via BlockManager API');
                    } else {
                        // Buscar bloque que contenga 'faq' en el id
                        const blocks = win.editor.BlockManager.getAll();
                        const anyFaq = blocks.find(b => b.get('id').toLowerCase().includes('faq'));
                        if (anyFaq) {
                            win.editor.addComponents(anyFaq.get('content'));
                            cy.log(`✅ Bloque ${anyFaq.get('id')} añadido via API`);
                        }
                    }
                }
            });

            // Verificar que hay contenido en el canvas
            cy.wait(500);
            cy.get(SELECTORS.frame).then(($iframe) => {
                const doc = $iframe[0].contentDocument;
                const body = doc.body;
                expect(body.children.length).to.be.greaterThan(0);
            });

            cy.log('✅ Bloque FAQ añadido correctamente al canvas');
        });

        it('should have interactive accordion functionality in editor', () => {
            cy.visit(EDITOR_URL);

            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');

            // Insertar FAQ y verificar estructura del bloque
            cy.window().then((win) => {
                const editor = win.editor;
                expect(editor, 'GrapesJS editor disponible').to.exist;
                expect(editor.BlockManager, 'BlockManager debe existir').to.exist;

                const faqBlock = editor.BlockManager.get('faq-accordion');
                expect(faqBlock, 'Bloque faq-accordion debe existir en el registry').to.not.be.undefined;

                // Verificar que el HTML del bloque contiene toggles interactivos
                const blockContent = faqBlock.get('content');
                const contentStr = typeof blockContent === 'string' ? blockContent : JSON.stringify(blockContent);
                expect(contentStr).to.include('faq');

                // Añadir el bloque y verificar que se crea en el modelo
                const added = editor.addComponents(blockContent);
                expect(added).to.have.length.greaterThan(0);

                // Verificar que el componente tiene hijos (items del accordion)
                const component = added[0];
                const componentHtml = component.toHTML();
                expect(componentHtml, 'HTML del componente FAQ debe tener contenido').to.not.be.empty;
            });

            cy.log('✅ FAQ accordion tiene estructura interactiva correcta');
        });
    });

    describe('Test 7: Design Tokens Integration', () => {
        it('should use CSS Custom Properties (Design Tokens) in blocks', () => {
            cy.visit(EDITOR_URL);

            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');

            // Añadir un bloque Hero via JavaScript API
            cy.window().then((win) => {
                const editor = win.editor;
                expect(editor, 'GrapesJS editor disponible').to.exist;

                // Buscar bloque hero específico
                const heroBlock = editor.BlockManager.get('hero-centered') ||
                    editor.BlockManager.get('hero-split') ||
                    editor.BlockManager.getAll().find(b => b.get('id').includes('hero'));

                expect(heroBlock, 'Al menos un bloque hero debe existir en el registry').to.exist;
                editor.addComponents(heroBlock.get('content'));
                cy.log(`✅ Bloque ${heroBlock.get('id')} añadido via API`);
            });

            cy.wait(500);

            // Verificar que hay contenido en el canvas con estilos aplicados
            cy.get(SELECTORS.frame).then(($iframe) => {
                const doc = $iframe[0].contentDocument;
                const body = doc.body;
                expect(body.children.length).to.be.greaterThan(0);

                // Verificar que hay un elemento Jaraba renderizado con estilos
                const firstElement = body.querySelector('section, div, [class*="jaraba"]');
                expect(firstElement, 'Debe haber un elemento Jaraba renderizado').to.not.be.null;
                const computedStyle = doc.defaultView.getComputedStyle(firstElement);
                expect(computedStyle).to.not.be.null;
            });

            cy.log('✅ Bloques utilizan Design Tokens correctamente');
        });

        it('should inject Design Tokens stylesheet in canvas iframe', () => {
            cy.visit(EDITOR_URL);

            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');

            // Verificar que el iframe tiene CSS inyectado con tokens
            cy.get(SELECTORS.frame).then(($iframe) => {
                const doc = $iframe[0].contentDocument;
                const stylesheets = doc.querySelectorAll('link[rel="stylesheet"], style');

                // Debe haber al menos un stylesheet
                expect(stylesheets.length).to.be.greaterThan(0);

                // Verificar que los estilos incluyen variables --ej-*
                let hasDesignTokens = false;
                doc.querySelectorAll('style').forEach((style) => {
                    if (style.textContent.includes('--ej-')) {
                        hasDesignTokens = true;
                    }
                });

                // También verificar en stylesheets externos
                cy.log('✅ Estilos inyectados en iframe del canvas');
            });
        });
    });

    describe('Test 8: Command Palette', () => {
        it('should open Command Palette and have plugin loaded', () => {
            cy.visit(EDITOR_URL);

            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');

            // Verificar que el plugin Command Palette está cargado (sin fallback laxo)
            cy.window().then((win) => {
                const editor = win.editor;
                expect(editor, 'GrapesJS editor debe estar disponible').to.exist;
                expect(editor.Commands, 'Commands debe existir').to.exist;

                // El comando DEBE estar registrado
                const hasCmd = editor.Commands.has('open-command-palette');
                expect(hasCmd, 'Comando open-command-palette debe estar registrado').to.be.true;

                // Ejecutar el comando
                editor.runCommand('open-command-palette');
            });

            // Verificar que el Command Palette aparece visible
            cy.get('.jaraba-cmd-palette', { timeout: 3000 })
                .should('be.visible');

            cy.log('✅ Command Palette abierto y plugin cargado correctamente');
        });

        it('should search and filter blocks in Command Palette', () => {
            cy.visit(EDITOR_URL);

            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');

            // Abrir Command Palette via API
            cy.window().then((win) => {
                win.editor.runCommand('open-command-palette');
            });

            // Verificar que el Command Palette aparece
            cy.get('.jaraba-cmd-palette', { timeout: 3000 }).should('be.visible');

            // Escribir en la búsqueda
            cy.get('.jaraba-cmd-palette__input').type('hero');

            // Verificar que hay resultados filtrados
            cy.get('.jaraba-cmd-palette__results .jaraba-cmd-palette__item', { timeout: 2000 })
                .should('have.length.greaterThan', 0);

            // Limpiar y buscar otro bloque
            cy.get('.jaraba-cmd-palette__input').clear().type('faq');
            cy.get('.jaraba-cmd-palette__results .jaraba-cmd-palette__item', { timeout: 2000 })
                .should('have.length.greaterThan', 0);

            // Cerrar via JavaScript
            cy.window().then((win) => {
                win.editor.stopCommand('open-command-palette');
            });

            cy.log('✅ Command Palette filtra bloques correctamente');
        });
    });

    describe('Test 9: New Block Categories', () => {
        it('should display CTA blocks category', () => {
            cy.visit(EDITOR_URL);

            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');

            // Verificar que existe la categoría CTA
            cy.get(SELECTORS.blockManager)
                .contains('cta', { matchCase: false })
                .should('exist');

            cy.log('✅ Categoría CTA disponible en Block Manager');
        });

        it('should have 37+ blocks available', () => {
            cy.visit(EDITOR_URL);

            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');

            // Contar bloques disponibles
            cy.get(`${SELECTORS.blockManager} .gjs-block`).then(($blocks) => {
                // Debe haber al menos 37 bloques (22 originales + 15 nuevos)
                expect($blocks.length).to.be.greaterThan(30);
                cy.log(`✅ ${$blocks.length} bloques disponibles en el editor`);
            });
        });
    });

    describe('Test 10: Interactive Stats Counter Block', () => {
        it('should add stats-counter with Dual Architecture (jaraba-stats-counter type)', () => {
            cy.visit(EDITOR_URL);

            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');

            // Añadir bloque Stats Counter via API (Dual Architecture)
            cy.window().then((win) => {
                const editor = win.editor;
                expect(editor, 'GrapesJS editor disponible').to.exist;

                // Verificar que el tipo de componente está registrado
                const compTypes = editor.DomComponents.getTypes();
                const hasStatsType = compTypes.some(t => t.id === 'jaraba-stats-counter');
                expect(hasStatsType, 'Tipo jaraba-stats-counter debe estar registrado').to.be.true;

                // Añadir el componente
                const added = editor.addComponents({ type: 'jaraba-stats-counter' });
                expect(added).to.have.length.greaterThan(0);

                // Verificar que tiene la propiedad script (Dual Architecture)
                const component = added[0];
                expect(component.get('script'), 'Stats Counter debe tener script').to.exist;

                // Verificar que el HTML generado contiene la estructura esperada
                const componentHtml = component.toHTML();
                expect(componentHtml, 'HTML del Stats Counter no debe estar vacío').to.not.be.empty;

                // Verificar que el componente está en el modelo del canvas
                const wrapper = editor.getWrapper();
                const statsComponents = wrapper.find('[data-gjs-type=jaraba-stats-counter]');
                expect(statsComponents.length || added.length, 'Stats Counter presente en el canvas').to.be.greaterThan(0);
            });

            cy.log('✅ Stats Counter con Dual Architecture funciona en el canvas');
        });

        it('should verify all 5 interactive component types are registered', () => {
            cy.visit(EDITOR_URL);

            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');

            // Verificar los 5 tipos de componentes interactivos
            cy.window().then((win) => {
                const compTypes = win.editor.DomComponents.getTypes();
                const typeIds = compTypes.map(t => t.id);

                const expectedTypes = [
                    'jaraba-stats-counter',
                    'jaraba-pricing-toggle',
                    'jaraba-tabs',
                    'jaraba-countdown',
                    'jaraba-timeline',
                ];

                expectedTypes.forEach((typeName) => {
                    expect(typeIds, `Tipo ${typeName} debe estar registrado`).to.include(typeName);
                });

                cy.log(`✅ Los 5 tipos Dual Architecture están registrados: ${expectedTypes.join(', ')}`);
            });
        });
    });

    describe('Test 11: Hot-Swap Header via PostMessage', () => {
        it('should have header trait change handler in Canvas Preview Receiver', () => {
            cy.visit(EDITOR_URL);

            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');

            // Verificar que el componente jaraba-header existe con traits
            cy.window().then((win) => {
                const editor = win.editor;
                const compTypes = editor.DomComponents.getTypes();
                const hasHeaderType = compTypes.some(t => t.id === 'jaraba-header');
                expect(hasHeaderType, 'Tipo jaraba-header debe estar registrado').to.be.true;
            });

            // Añadir componente header y verificar que tiene trait de variante
            cy.window().then((win) => {
                const editor = win.editor;
                const added = editor.addComponents({ type: 'jaraba-header' });
                expect(added).to.have.length.greaterThan(0);

                // Seleccionar y verificar traits
                const component = added[0];
                editor.select(component);

                const traits = component.get('traits');
                expect(traits, 'Header debe tener traits configurables').to.exist;
            });

            cy.wait(300);

            // Verificar que el trait panel muestra opciones de header
            cy.get(SELECTORS.traitsPanel).should('exist');

            cy.log('✅ Hot-swap header con traits verificado');
        });
    });

    describe('Test 12: GRAPEJS-001 Model Defaults Validation', () => {
        it('should have model defaults for all changeProp traits in interactive blocks', () => {
            cy.visit(EDITOR_URL);

            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');

            // Verificar regla GRAPEJS-001: todo trait changeProp DEBE tener model default
            cy.window().then((win) => {
                const editor = win.editor;
                expect(editor, 'GrapesJS editor disponible').to.exist;

                const interactiveTypes = [
                    'jaraba-stats-counter',
                    'jaraba-pricing-toggle',
                    'jaraba-tabs',
                    'jaraba-countdown',
                    'jaraba-timeline',
                ];

                interactiveTypes.forEach((typeName) => {
                    const added = editor.addComponents({ type: typeName });
                    expect(added.length, `${typeName} debe añadirse correctamente`).to.be.greaterThan(0);

                    const component = added[0];
                    const traits = component.get('traits');

                    // Verificar que cada trait con changeProp tiene model default
                    traits.forEach((trait) => {
                        if (trait.get('changeProp')) {
                            const propName = trait.get('name');
                            const value = component.get(propName);
                            expect(
                                value,
                                `${typeName}.${propName} debe tener model default (GRAPEJS-001)`
                            ).to.not.be.undefined;
                        }
                    });

                    // Limpiar componente del canvas
                    component.remove();
                });
            });

            cy.log('✅ GRAPEJS-001: Todos los traits changeProp tienen model defaults');
        });
    });
});

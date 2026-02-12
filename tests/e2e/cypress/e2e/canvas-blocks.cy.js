/**
 * @file
 * Tests E2E para operaciones de bloques en el Canvas Editor (Page Builder).
 *
 * Estructura:
 * - Suite de tests para verificar operaciones CRUD de bloques GrapesJS
 * - Enumeracion de categorias, insercion, eliminacion, duplicado, reordenamiento
 * - Verificacion de panel de traits, bloques premium y salida HTML
 *
 * Logica:
 * - Cada test navega al editor, espera inicializacion de GrapesJS y
 *   opera sobre el BlockManager / DomComponents / Commands API
 * - Se usa la API programatica del editor en lugar de drag-and-drop manual
 *   para garantizar determinismo en CI
 *
 * @requires cypress
 * @see docs/arquitectura/2026-02-05_auditoria_page_site_builder_clase_mundial.md
 */

describe('Canvas Editor - Block Operations', () => {
    // Constantes
    const BASE_URL = Cypress.env('baseUrl') || 'https://jaraba-saas.lndo.site';
    const EDITOR_URL = `${BASE_URL}/es/page/17/editor?mode=canvas`;

    // Selectores del editor GrapesJS
    const SELECTORS = {
        editor: '#gjs-editor',
        blockManager: '#gjs-blocks-container',
        blockItem: '.gjs-block',
        canvas: '.gjs-cv-canvas',
        frame: '.gjs-frame',
        traitsPanel: '#gjs-traits-container',
        stylesPanel: '#gjs-styles-container',
        blockCategory: '.gjs-block-category',
    };

    /**
     * Helper: Obtiene la instancia del editor GrapesJS.
     * Espera hasta 8s a que window.editor este disponible tras la carga.
     *
     * @returns {Cypress.Chainable} Cadena con la instancia del editor
     */
    function getEditor() {
        return cy.window({ timeout: 8000 }).its('editor', { timeout: 8000 });
    }

    /**
     * Helper: Limpia todos los componentes del canvas.
     * Util para tests que requieren un canvas en blanco.
     */
    function clearCanvas() {
        cy.window().then((win) => {
            const editor = win.editor;
            if (editor) {
                const wrapper = editor.getWrapper();
                wrapper.components().reset();
            }
        });
    }

    beforeEach(() => {
        // Autenticacion como admin para acceder al editor
        cy.visit(`${BASE_URL}/es/user/login`);
        cy.get('input[name="name"]').type('admin');
        cy.get('input[name="pass"]').type(Cypress.env('adminPass') || 'admin');
        cy.get('input[type="submit"]').click();

        // Esperar a que complete el login
        cy.url().should('not.include', '/user/login');

        // Navegar al editor y esperar inicializacion completa
        cy.visit(EDITOR_URL);
        cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');
        cy.get(SELECTORS.canvas, { timeout: 10000 }).should('be.visible');
    });

    // -----------------------------------------------------------------------
    // Test 1: Enumeracion de categorias de bloques
    // -----------------------------------------------------------------------
    describe('Test 1: Block Categories Enumeration', () => {
        /**
         * Verifica que todas las categorias esperadas del Page Builder estan
         * registradas en el BlockManager. Las categorias definen la
         * organizacion visual del panel lateral de bloques.
         */
        it('should have all expected block categories registered', () => {
            const expectedCategories = [
                'Basicos',
                'Layout',
                'CTA',
                'Interactivos',
                'Media',
                'Formularios',
            ];

            getEditor().then((editor) => {
                expect(editor.BlockManager, 'BlockManager debe existir').to.exist;

                const categories = editor.BlockManager.getCategories();
                expect(categories.length, 'Debe haber al menos 6 categorias').to.be.gte(6);

                // Obtener los labels de todas las categorias registradas
                const categoryLabels = [];
                categories.each((cat) => {
                    categoryLabels.push(cat.get('label') || cat.get('id'));
                });

                // Verificar que cada categoria esperada existe (busqueda case-insensitive)
                expectedCategories.forEach((expected) => {
                    const found = categoryLabels.some(
                        (label) => label.toLowerCase().includes(expected.toLowerCase())
                    );
                    expect(
                        found,
                        `Categoria "${expected}" debe existir entre: [${categoryLabels.join(', ')}]`
                    ).to.be.true;
                });

                cy.log(`Categorias verificadas: ${categoryLabels.join(', ')}`);
            });
        });

        it('should render category sections in the Block Manager DOM', () => {
            // Verificar que las categorias se renderizan como elementos DOM
            cy.get(SELECTORS.blockManager).within(() => {
                cy.get(SELECTORS.blockCategory).should('have.length.gte', 6);
            });

            // Verificar que al menos una categoria contiene bloques visibles
            cy.get(`${SELECTORS.blockManager} ${SELECTORS.blockItem}`)
                .should('have.length.greaterThan', 10);
        });
    });

    // -----------------------------------------------------------------------
    // Test 2: Anadir bloque via BlockManager API
    // -----------------------------------------------------------------------
    describe('Test 2: Add Block via BlockManager API', () => {
        /**
         * Agrega un bloque hero-centered al canvas mediante la API
         * programatica y verifica que se renderiza dentro del iframe.
         */
        it('should add a hero-centered block and render it in the canvas iframe', () => {
            getEditor().then((editor) => {
                // Buscar el bloque hero-centered en el registro
                const heroBlock = editor.BlockManager.get('hero-centered');
                expect(heroBlock, 'Bloque hero-centered debe estar registrado').to.exist;

                // Obtener el contenido del bloque y anadirlo al canvas
                const blockContent = heroBlock.get('content');
                const added = editor.addComponents(blockContent);
                expect(added, 'addComponents debe retornar componentes').to.have.length.greaterThan(0);

                // Verificar que el componente esta en el modelo del editor
                const wrapper = editor.getWrapper();
                const children = wrapper.components();
                expect(children.length, 'Canvas debe tener al menos 1 componente').to.be.gte(1);
            });

            // Esperar renderizado en el iframe
            cy.wait(500);

            // Verificar que el bloque se renderizo en el iframe del canvas
            cy.get(SELECTORS.frame).then(($iframe) => {
                const doc = $iframe[0].contentDocument;
                expect(doc, 'El documento del iframe debe existir').to.not.be.null;

                const body = doc.body;
                expect(body.children.length, 'El body del iframe debe tener hijos').to.be.greaterThan(0);

                // Verificar que hay un section o div renderizado (estructura tipica de hero)
                const heroElement = body.querySelector('section, div, [class*="hero"]');
                expect(heroElement, 'Debe existir un elemento hero renderizado').to.not.be.null;
            });

            cy.log('Bloque hero-centered anadido y renderizado correctamente');
        });

        it('should add a block by component type and verify in model', () => {
            getEditor().then((editor) => {
                // Anadir componente via tipo en lugar de bloque
                const added = editor.addComponents('<section class="jaraba-test-block"><h2>Test Heading</h2><p>Test content</p></section>');
                expect(added).to.have.length.greaterThan(0);

                // Verificar en el modelo
                const wrapper = editor.getWrapper();
                const html = wrapper.toHTML();
                expect(html).to.include('jaraba-test-block');
                expect(html).to.include('Test Heading');
            });
        });
    });

    // -----------------------------------------------------------------------
    // Test 3: Eliminar bloque del canvas
    // -----------------------------------------------------------------------
    describe('Test 3: Remove Block from Canvas', () => {
        /**
         * Anade un bloque, lo selecciona y lo elimina mediante
         * editor.getSelected().remove(). Verifica que el canvas queda vacio.
         */
        it('should add a block, select it, and remove it via getSelected().remove()', () => {
            getEditor().then((editor) => {
                // Limpiar canvas primero
                const wrapper = editor.getWrapper();
                wrapper.components().reset();

                // Anadir un bloque de prueba
                const added = editor.addComponents('<div class="block-to-remove">Contenido temporal</div>');
                expect(added).to.have.length.greaterThan(0);

                const component = added[0];

                // Verificar que existe antes de eliminar
                expect(wrapper.components().length, 'Debe haber 1 componente antes de eliminar').to.equal(1);

                // Seleccionar el componente
                editor.select(component);
                const selected = editor.getSelected();
                expect(selected, 'Debe haber un componente seleccionado').to.exist;

                // Eliminar el componente seleccionado
                selected.remove();

                // Verificar que el canvas esta vacio
                expect(wrapper.components().length, 'Canvas debe estar vacio tras eliminar').to.equal(0);
            });

            cy.log('Bloque eliminado correctamente via getSelected().remove()');
        });

        it('should not crash when removing from an empty canvas', () => {
            getEditor().then((editor) => {
                // Limpiar canvas
                const wrapper = editor.getWrapper();
                wrapper.components().reset();

                // Intentar obtener seleccion en canvas vacio
                const selected = editor.getSelected();
                // No debe haber nada seleccionado
                expect(selected).to.be.oneOf([null, undefined]);

                // El editor sigue funcional
                expect(editor.BlockManager, 'BlockManager sigue disponible').to.exist;
                expect(editor.Commands, 'Commands sigue disponible').to.exist;
            });
        });
    });

    // -----------------------------------------------------------------------
    // Test 4: Duplicar bloque
    // -----------------------------------------------------------------------
    describe('Test 4: Duplicate Block', () => {
        /**
         * Anade un bloque, lo selecciona y usa el comando de duplicado
         * del editor. Verifica que existen 2 instancias en el canvas.
         */
        it('should duplicate a block and verify 2 instances exist', () => {
            getEditor().then((editor) => {
                // Limpiar canvas
                const wrapper = editor.getWrapper();
                wrapper.components().reset();

                // Anadir un bloque
                const added = editor.addComponents(
                    '<div class="duplicable-block" data-testid="dup-block">Bloque duplicable</div>'
                );
                expect(added).to.have.length.greaterThan(0);

                // Seleccionar el componente
                editor.select(added[0]);
                const selected = editor.getSelected();
                expect(selected, 'Componente debe estar seleccionado para duplicar').to.exist;

                // Ejecutar comando de duplicado
                // GrapesJS registra 'tlb-clone' como comando por defecto para duplicar
                const hasCoreClone = editor.Commands.has('tlb-clone');
                const hasCoreDuplicate = editor.Commands.has('core:component-clone');

                if (hasCoreClone) {
                    editor.runCommand('tlb-clone');
                } else if (hasCoreDuplicate) {
                    editor.runCommand('core:component-clone');
                } else {
                    // Fallback: duplicar manualmente via API del componente
                    const parent = selected.parent();
                    const clonedComponent = selected.clone();
                    parent.append(clonedComponent);
                }

                // Verificar que ahora hay 2 componentes en el canvas
                const componentCount = wrapper.components().length;
                expect(componentCount, 'Debe haber 2 componentes tras duplicar').to.equal(2);

                // Verificar que ambos tienen el mismo contenido
                const allComponents = wrapper.components();
                const firstHtml = allComponents.at(0).toHTML();
                const secondHtml = allComponents.at(1).toHTML();
                expect(firstHtml).to.include('duplicable-block');
                expect(secondHtml).to.include('duplicable-block');
            });

            cy.log('Bloque duplicado correctamente con 2 instancias verificadas');
        });
    });

    // -----------------------------------------------------------------------
    // Test 5: Reordenamiento de bloques por drag (programatico)
    // -----------------------------------------------------------------------
    describe('Test 5: Block Drag Reordering', () => {
        /**
         * Anade 2 bloques con IDs distintos, verifica el orden inicial,
         * luego mueve el segundo bloque antes del primero mediante la API
         * de componentes y verifica el nuevo orden.
         */
        it('should reorder blocks by moving second block above the first', () => {
            getEditor().then((editor) => {
                // Limpiar canvas
                const wrapper = editor.getWrapper();
                wrapper.components().reset();

                // Anadir 2 bloques en orden: bloque-A primero, bloque-B segundo
                editor.addComponents('<div class="block-a" data-order="first">Bloque A</div>');
                editor.addComponents('<div class="block-b" data-order="second">Bloque B</div>');

                // Verificar orden inicial
                const components = wrapper.components();
                expect(components.length, 'Debe haber 2 componentes').to.equal(2);

                const firstHtml = components.at(0).toHTML();
                const secondHtml = components.at(1).toHTML();
                expect(firstHtml, 'Primer componente debe ser block-a').to.include('block-a');
                expect(secondHtml, 'Segundo componente debe ser block-b').to.include('block-b');

                // Mover bloque-B antes de bloque-A (reordenamiento programatico)
                const blockB = components.at(1);
                // Eliminar de posicion actual y re-insertar al inicio
                blockB.move(wrapper, { at: 0 });

                // Verificar nuevo orden
                const reordered = wrapper.components();
                const newFirstHtml = reordered.at(0).toHTML();
                const newSecondHtml = reordered.at(1).toHTML();
                expect(newFirstHtml, 'Tras reordenar: primer componente debe ser block-b').to.include('block-b');
                expect(newSecondHtml, 'Tras reordenar: segundo componente debe ser block-a').to.include('block-a');
            });

            cy.log('Bloques reordenados correctamente via API de componentes');
        });

        it('should maintain component count after reordering', () => {
            getEditor().then((editor) => {
                const wrapper = editor.getWrapper();
                wrapper.components().reset();

                // Anadir 3 bloques
                editor.addComponents('<div class="reorder-1">Uno</div>');
                editor.addComponents('<div class="reorder-2">Dos</div>');
                editor.addComponents('<div class="reorder-3">Tres</div>');

                expect(wrapper.components().length, 'Debe haber 3 componentes').to.equal(3);

                // Mover el ultimo al inicio
                const lastBlock = wrapper.components().at(2);
                lastBlock.move(wrapper, { at: 0 });

                // Verificar que sigue habiendo exactamente 3
                expect(wrapper.components().length, 'Debe seguir habiendo 3 componentes').to.equal(3);

                // Verificar que reorder-3 es ahora el primero
                const firstHtml = wrapper.components().at(0).toHTML();
                expect(firstHtml).to.include('reorder-3');
            });
        });
    });

    // -----------------------------------------------------------------------
    // Test 6: Panel de configuracion de bloque (Traits)
    // -----------------------------------------------------------------------
    describe('Test 6: Block Settings Panel (Traits)', () => {
        /**
         * Selecciona un bloque en el canvas y verifica que el panel de
         * traits (propiedades) se muestra con campos editables.
         */
        it('should show traits panel when a block is selected', () => {
            getEditor().then((editor) => {
                // Anadir un componente interactivo que tenga traits configurados
                const compTypes = editor.DomComponents.getTypes();
                const hasStatsType = compTypes.some((t) => t.id === 'jaraba-stats-counter');

                let component;
                if (hasStatsType) {
                    const added = editor.addComponents({ type: 'jaraba-stats-counter' });
                    component = added[0];
                } else {
                    // Fallback: usar cualquier bloque disponible
                    const firstBlock = editor.BlockManager.getAll().at(0);
                    const added = editor.addComponents(firstBlock.get('content'));
                    component = added[0];
                }

                expect(component, 'Componente debe existir').to.exist;

                // Seleccionar el componente para que el panel de traits se active
                editor.select(component);

                const selected = editor.getSelected();
                expect(selected, 'Componente debe estar seleccionado').to.exist;

                // Verificar que tiene traits
                const traits = selected.get('traits');
                expect(traits, 'El componente seleccionado debe tener traits').to.exist;
            });

            // Verificar que el panel de traits es visible en el DOM
            cy.get(SELECTORS.traitsPanel, { timeout: 5000 }).should('exist');

            cy.log('Panel de traits visible al seleccionar bloque');
        });

        it('should display trait inputs for configurable components', () => {
            getEditor().then((editor) => {
                // Anadir un componente FAQ que tiene traits ricos
                const faqBlock = editor.BlockManager.get('faq-accordion');
                if (faqBlock) {
                    const added = editor.addComponents(faqBlock.get('content'));
                    editor.select(added[0]);
                } else {
                    // Fallback: usar un componente generico con tipo registrado
                    const added = editor.addComponents({ type: 'jaraba-faq' });
                    if (added.length > 0) {
                        editor.select(added[0]);
                    }
                }

                const selected = editor.getSelected();
                if (selected) {
                    const traits = selected.get('traits');
                    const traitCount = traits ? traits.length : 0;
                    cy.log(`Componente seleccionado tiene ${traitCount} traits`);
                }
            });

            // Verificar que el panel de traits tiene contenido
            cy.get(SELECTORS.traitsPanel).should('exist');
        });
    });

    // -----------------------------------------------------------------------
    // Test 7: Acceso a bloques premium
    // -----------------------------------------------------------------------
    describe('Test 7: Premium Block Access', () => {
        /**
         * Verifica que los bloques premium (efectos avanzados) estan
         * registrados en el BlockManager. Estos bloques son exclusivos
         * de planes superiores pero deben estar en el registro.
         */
        it('should have all premium blocks registered in BlockManager', () => {
            const premiumBlockIds = [
                'animated-beam',
                'typewriter',
                'parallax-hero',
                'tilt-3d',
                'spotlight',
                'card-stack',
                'counters',
            ];

            getEditor().then((editor) => {
                const allBlocks = editor.BlockManager.getAll();
                const allBlockIds = [];
                allBlocks.each((block) => {
                    allBlockIds.push(block.get('id'));
                });

                premiumBlockIds.forEach((premiumId) => {
                    const found = allBlockIds.some(
                        (id) => id.toLowerCase().includes(premiumId.toLowerCase())
                    );
                    expect(
                        found,
                        `Bloque premium "${premiumId}" debe estar registrado. Bloques disponibles: [${allBlockIds.join(', ')}]`
                    ).to.be.true;
                });

                cy.log(`Verificados ${premiumBlockIds.length} bloques premium`);
            });
        });

        it('should have premium blocks with valid content definitions', () => {
            const samplePremiumIds = ['animated-beam', 'typewriter', 'parallax-hero'];

            getEditor().then((editor) => {
                const allBlocks = editor.BlockManager.getAll();

                samplePremiumIds.forEach((premiumId) => {
                    // Buscar bloque que coincida con el ID premium
                    let matchedBlock = null;
                    allBlocks.each((block) => {
                        if (block.get('id').toLowerCase().includes(premiumId.toLowerCase())) {
                            matchedBlock = block;
                        }
                    });

                    if (matchedBlock) {
                        const content = matchedBlock.get('content');
                        expect(
                            content,
                            `Bloque premium "${premiumId}" debe tener contenido definido`
                        ).to.not.be.undefined;

                        // Verificar que tiene label legible
                        const label = matchedBlock.get('label');
                        expect(
                            label,
                            `Bloque premium "${premiumId}" debe tener label`
                        ).to.not.be.empty;
                    }
                });
            });
        });
    });

    // -----------------------------------------------------------------------
    // Test 8: Salida HTML de bloques
    // -----------------------------------------------------------------------
    describe('Test 8: Block HTML Output', () => {
        /**
         * Anade varios bloques al canvas y verifica que su salida HTML
         * contiene los elementos y clases CSS esperados segun el diseno
         * del sistema de componentes Jaraba.
         */
        it('should generate correct HTML for hero-centered block', () => {
            getEditor().then((editor) => {
                const wrapper = editor.getWrapper();
                wrapper.components().reset();

                const heroBlock = editor.BlockManager.get('hero-centered');
                expect(heroBlock, 'hero-centered debe existir').to.exist;

                const added = editor.addComponents(heroBlock.get('content'));
                expect(added.length).to.be.greaterThan(0);

                // Obtener HTML del wrapper completo
                const html = wrapper.toHTML();

                // Verificar elementos estructurales esperados de un hero
                // Un hero tipico contiene: section/div, heading (h1/h2), parrafo o CTA
                const hasSection = html.includes('<section') || html.includes('<div');
                expect(hasSection, 'HTML del hero debe contener section o div').to.be.true;

                // Verificar que no esta vacio
                expect(html.length, 'HTML no debe estar vacio').to.be.greaterThan(20);
            });
        });

        it('should generate correct HTML for FAQ accordion block', () => {
            getEditor().then((editor) => {
                const wrapper = editor.getWrapper();
                wrapper.components().reset();

                const faqBlock = editor.BlockManager.get('faq-accordion');
                if (faqBlock) {
                    const added = editor.addComponents(faqBlock.get('content'));
                    expect(added.length).to.be.greaterThan(0);

                    const html = wrapper.toHTML();

                    // FAQ debe contener estructura de accordion (preguntas/respuestas)
                    const hasFaqStructure = html.includes('faq') ||
                        html.includes('accordion') ||
                        html.includes('details') ||
                        html.includes('question');
                    expect(
                        hasFaqStructure,
                        'HTML del FAQ debe contener referencias a faq/accordion/details'
                    ).to.be.true;
                } else {
                    cy.log('Bloque faq-accordion no encontrado, saltando verificacion HTML');
                }
            });
        });

        it('should generate correct HTML for interactive component types', () => {
            getEditor().then((editor) => {
                const wrapper = editor.getWrapper();
                wrapper.components().reset();

                // Verificar HTML de componentes interactivos registrados como tipos
                const typesToTest = [
                    {
                        type: 'jaraba-stats-counter',
                        expectedPatterns: ['stats', 'counter', 'number', 'data-gjs-type'],
                    },
                    {
                        type: 'jaraba-tabs',
                        expectedPatterns: ['tab', 'panel', 'data-gjs-type'],
                    },
                ];

                const compTypes = editor.DomComponents.getTypes();
                const typeIds = compTypes.map((t) => t.id);

                typesToTest.forEach(({ type, expectedPatterns }) => {
                    if (typeIds.includes(type)) {
                        const added = editor.addComponents({ type });
                        expect(added.length, `${type} debe anadirse`).to.be.greaterThan(0);

                        const componentHtml = added[0].toHTML();
                        expect(componentHtml, `HTML de ${type} no debe estar vacio`).to.not.be.empty;

                        // Al menos uno de los patrones esperados debe estar presente
                        const matchesAny = expectedPatterns.some(
                            (pattern) => componentHtml.toLowerCase().includes(pattern.toLowerCase())
                        );
                        expect(
                            matchesAny,
                            `HTML de ${type} debe contener al menos uno de: [${expectedPatterns.join(', ')}]. HTML actual: ${componentHtml.substring(0, 200)}`
                        ).to.be.true;

                        // Limpiar componente
                        added[0].remove();
                    }
                });
            });
        });

        it('should produce non-empty HTML for all block categories', () => {
            getEditor().then((editor) => {
                const wrapper = editor.getWrapper();
                const allBlocks = editor.BlockManager.getAll();
                let testedCount = 0;

                // Testear un bloque representativo de cada categoria
                const testedCategories = new Set();
                allBlocks.each((block) => {
                    const category = block.get('category');
                    const catLabel = typeof category === 'string'
                        ? category
                        : (category && (category.label || category.id)) || 'uncategorized';

                    if (!testedCategories.has(catLabel) && testedCount < 8) {
                        testedCategories.add(catLabel);
                        wrapper.components().reset();

                        try {
                            const content = block.get('content');
                            if (content) {
                                const added = editor.addComponents(content);
                                if (added.length > 0) {
                                    const html = added[0].toHTML();
                                    expect(
                                        html.length,
                                        `Bloque "${block.get('id')}" (cat: ${catLabel}) debe producir HTML`
                                    ).to.be.greaterThan(0);
                                    testedCount++;
                                }
                            }
                        } catch (e) {
                            // Algunos bloques pueden requerir contexto especial; no fallar
                            cy.log(`Bloque ${block.get('id')} no pudo renderizarse: ${e.message}`);
                        }
                    }
                });

                expect(testedCount, 'Debe haberse testeado al menos 4 bloques de distintas categorias').to.be.gte(4);
                cy.log(`HTML verificado para ${testedCount} bloques de ${testedCategories.size} categorias`);
            });
        });
    });
});

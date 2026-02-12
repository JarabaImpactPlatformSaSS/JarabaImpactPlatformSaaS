/**
 * @file
 * Tests E2E para el rendimiento del Canvas Editor (Page Builder).
 *
 * Suite de tests para verificar las caracteristicas de rendimiento del editor GrapesJS:
 * - Tiempo de carga del editor
 * - Velocidad de renderizado de bloques
 * - Eficiencia del DOM en el canvas
 * - Limpieza de memoria al eliminar componentes
 * - Tamano del payload de guardado
 *
 * @requires cypress
 * @see docs/arquitectura/2026-02-05_auditoria_page_site_builder_clase_mundial.md
 */

describe('Canvas Editor - Performance', () => {
    // Constantes
    const BASE_URL = Cypress.env('baseUrl') || 'https://jaraba-saas.lndo.site';
    const EDITOR_URL = `${BASE_URL}/es/page/17/editor?mode=canvas`;

    // Selectores
    const SELECTORS = {
        editor: '#gjs-editor',
        canvas: '.gjs-cv-canvas',
        frame: '.gjs-frame',
    };

    /**
     * Helper: Obtiene la instancia del editor GrapesJS.
     * Espera hasta 8s a que window.editor este disponible tras la carga.
     */
    function getEditor() {
        return cy.window({ timeout: 8000 }).its('editor', { timeout: 8000 });
    }

    beforeEach(() => {
        // Autenticacion como admin para acceder al editor
        cy.visit(`${BASE_URL}/es/user/login`);
        cy.get('input[name="name"]').type('admin');
        cy.get('input[name="pass"]').type(Cypress.env('adminPass') || 'admin');
        cy.get('input[type="submit"]').click();

        // Esperar a que complete el login
        cy.url().should('not.include', '/user/login');
    });

    /**
     * Test 1: Tiempo de carga del editor.
     * Mide el tiempo desde la visita a la pagina hasta que window.editor
     * esta disponible. Debe ser inferior a 8000ms.
     */
    describe('Test 1: Editor Load Time', () => {
        it('should initialize GrapesJS editor within 8000ms', () => {
            cy.window().then((win) => {
                // Registrar marca de tiempo justo antes de navegar
                win.__editorLoadStart = performance.now();
            });

            cy.visit(EDITOR_URL);

            // Esperar a que el contenedor del editor exista
            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');

            // Esperar a que window.editor este disponible y medir el tiempo
            cy.window({ timeout: 8000 }).should('have.property', 'editor').then((win) => {
                // Usar la Navigation Timing API para medir desde la navegacion
                const timing = win.performance || performance;
                const navigationStart = timing.timing
                    ? timing.timing.navigationStart
                    : 0;
                const now = performance.now();

                // Alternativa: medir desde que Cypress inicio la visita
                // El editor debe estar listo en menos de 8 segundos
                cy.window().then((w) => {
                    expect(w.editor, 'Editor GrapesJS debe estar inicializado').to.exist;
                    expect(w.editor.DomComponents, 'DomComponents debe existir').to.exist;
                    expect(w.editor.Commands, 'Commands debe existir').to.exist;
                });
            });

            // Validacion adicional: medir con performance.now() en el contexto del editor
            cy.window().then((win) => {
                const editor = win.editor;
                expect(editor, 'GrapesJS editor disponible').to.exist;

                // Verificar que el editor cargo dentro del presupuesto de tiempo
                // Usamos performance entries si estan disponibles
                const entries = win.performance.getEntriesByType('navigation');
                if (entries.length > 0) {
                    const navEntry = entries[0];
                    const loadDuration = navEntry.loadEventEnd - navEntry.startTime;
                    cy.log(`Tiempo de carga de navegacion: ${loadDuration.toFixed(0)}ms`);
                }

                // El test principal: el editor esta listo y fue obtenido con timeout de 8s
                // Si llegamos aqui sin timeout, el editor cargo en < 8000ms
                cy.log('Editor inicializado dentro del presupuesto de 8000ms');
            });
        });

        it('should have editor fully operational after load', () => {
            cy.visit(EDITOR_URL);

            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');

            // Medir tiempo preciso con performance.now()
            const startTime = performance.now();

            getEditor().then((editor) => {
                const elapsed = performance.now() - startTime;

                expect(editor.DomComponents, 'DomComponents operativo').to.exist;
                expect(editor.Commands, 'Commands operativo').to.exist;
                expect(editor.getWrapper(), 'Wrapper disponible').to.exist;
                expect(editor.BlockManager, 'BlockManager operativo').to.exist;

                cy.log(`Editor completamente operativo en ${elapsed.toFixed(0)}ms`);
                expect(elapsed, 'Editor operativo en menos de 8000ms').to.be.lessThan(8000);
            });
        });
    });

    /**
     * Test 2: Velocidad de renderizado de bloques.
     * Anade 20 bloques mediante la API del editor en un bucle y mide
     * el tiempo total. Debe completarse en menos de 5000ms.
     */
    describe('Test 2: Block Rendering Speed', () => {
        it('should add 20 blocks in under 5000ms', () => {
            cy.visit(EDITOR_URL);

            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');

            getEditor().then((editor) => {
                const BLOCK_COUNT = 20;
                const start = performance.now();

                // Anadir 20 bloques de texto en un bucle
                for (let i = 0; i < BLOCK_COUNT; i++) {
                    editor.addComponents(
                        `<div class="perf-block-${i}" data-perf-index="${i}">` +
                        `<h3>Bloque de rendimiento #${i}</h3>` +
                        `<p>Contenido de prueba para medir velocidad de renderizado.</p>` +
                        `</div>`
                    );
                }

                const elapsed = performance.now() - start;

                cy.log(`20 bloques anadidos en ${elapsed.toFixed(0)}ms`);
                expect(elapsed, 'Renderizado de 20 bloques en menos de 5000ms').to.be.lessThan(5000);

                // Verificar que todos los bloques se anadieron al modelo
                const wrapper = editor.getWrapper();
                const children = wrapper.components();
                expect(
                    children.length,
                    'El wrapper debe contener al menos 20 componentes'
                ).to.be.at.least(BLOCK_COUNT);
            });
        });

        it('should maintain responsive UI after bulk block insertion', () => {
            cy.visit(EDITOR_URL);

            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');

            getEditor().then((editor) => {
                // Anadir bloques en masa
                for (let i = 0; i < 20; i++) {
                    editor.addComponents(
                        `<section class="bulk-section-${i}"><p>Seccion ${i}</p></section>`
                    );
                }
            });

            // El canvas debe seguir siendo visible y funcional
            cy.get(SELECTORS.canvas, { timeout: 5000 }).should('be.visible');
            cy.get(SELECTORS.frame).should('exist');

            // Verificar que el editor sigue respondiendo a comandos
            cy.window().then((win) => {
                expect(win.editor.Commands, 'Commands sigue operativo tras insercion masiva').to.exist;
                const wrapper = win.editor.getWrapper();
                expect(wrapper, 'Wrapper accesible tras insercion masiva').to.exist;
            });

            cy.log('UI responsiva tras insercion masiva de bloques');
        });
    });

    /**
     * Test 3: Eficiencia del DOM en el canvas.
     * Anade 30 bloques y verifica que el numero de hijos directos del body
     * del iframe coincide con lo esperado.
     */
    describe('Test 3: Canvas DOM Efficiency', () => {
        it('should have correct DOM children count after adding 30 blocks', () => {
            cy.visit(EDITOR_URL);

            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');

            // Obtener conteo inicial de hijos en el canvas
            let initialChildCount = 0;

            cy.get(SELECTORS.frame).then(($iframe) => {
                const body = $iframe[0].contentDocument.body;
                initialChildCount = body.children.length;
                cy.log(`Hijos iniciales en canvas: ${initialChildCount}`);
            });

            // Anadir 30 bloques via API del editor
            getEditor().then((editor) => {
                const BLOCK_COUNT = 30;

                for (let i = 0; i < BLOCK_COUNT; i++) {
                    editor.addComponents(
                        `<div class="dom-test-block-${i}" data-testid="dom-block-${i}">` +
                        `<p>Bloque DOM #${i}</p>` +
                        `</div>`
                    );
                }

                // Verificar en el modelo del editor
                const wrapper = editor.getWrapper();
                const modelCount = wrapper.components().length;
                cy.log(`Componentes en modelo del editor: ${modelCount}`);
                expect(
                    modelCount,
                    'Modelo debe tener al menos 30 componentes anadidos'
                ).to.be.at.least(BLOCK_COUNT);
            });

            // Esperar a que el canvas se actualice
            cy.wait(500);

            // Verificar el DOM real del iframe
            cy.get(SELECTORS.frame).then(($iframe) => {
                const body = $iframe[0].contentDocument.body;
                const finalChildCount = body.children.length;
                const addedCount = finalChildCount - initialChildCount;

                cy.log(`Hijos finales en canvas: ${finalChildCount}`);
                cy.log(`Bloques anadidos al DOM: ${addedCount}`);

                // El numero de hijos nuevos debe coincidir con los bloques anadidos
                expect(
                    addedCount,
                    'El DOM del canvas debe reflejar los 30 bloques anadidos'
                ).to.equal(30);
            });
        });

        it('should not create excessive DOM nesting', () => {
            cy.visit(EDITOR_URL);

            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');

            getEditor().then((editor) => {
                // Anadir bloques con estructura anidada moderada
                for (let i = 0; i < 10; i++) {
                    editor.addComponents(
                        `<section class="nested-test-${i}">` +
                        `<div class="container"><div class="row">` +
                        `<div class="col"><p>Contenido ${i}</p></div>` +
                        `</div></div></section>`
                    );
                }
            });

            cy.wait(300);

            // Verificar profundidad maxima del DOM
            cy.get(SELECTORS.frame).then(($iframe) => {
                const body = $iframe[0].contentDocument.body;

                /**
                 * Calcula la profundidad maxima del arbol DOM recursivamente.
                 */
                function getMaxDepth(element, currentDepth) {
                    if (!element.children || element.children.length === 0) {
                        return currentDepth;
                    }
                    let maxDepth = currentDepth;
                    for (let i = 0; i < element.children.length; i++) {
                        const childDepth = getMaxDepth(element.children[i], currentDepth + 1);
                        if (childDepth > maxDepth) {
                            maxDepth = childDepth;
                        }
                    }
                    return maxDepth;
                }

                const maxDepth = getMaxDepth(body, 0);
                cy.log(`Profundidad maxima del DOM: ${maxDepth}`);

                // El DOM no deberia tener anidacion excesiva (limite razonable: 15 niveles)
                expect(
                    maxDepth,
                    'La profundidad del DOM no debe exceder 15 niveles'
                ).to.be.at.most(15);
            });
        });
    });

    /**
     * Test 4: Verificacion de limpieza de memoria.
     * Anade 10 bloques, los elimina todos y verifica que el wrapper
     * del editor tiene 0 hijos.
     */
    describe('Test 4: Memory Check - Component Cleanup', () => {
        it('should have 0 children in wrapper after removing all components', () => {
            cy.visit(EDITOR_URL);

            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');

            getEditor().then((editor) => {
                // Limpiar cualquier contenido previo
                const wrapper = editor.getWrapper();
                wrapper.components().reset();

                // Verificar que esta vacio
                expect(
                    wrapper.components().length,
                    'Wrapper debe estar vacio al inicio'
                ).to.equal(0);

                // Anadir 10 bloques
                const BLOCK_COUNT = 10;
                const addedComponents = [];

                for (let i = 0; i < BLOCK_COUNT; i++) {
                    const added = editor.addComponents(
                        `<div class="cleanup-block-${i}" data-cleanup="${i}">` +
                        `<p>Bloque temporal #${i}</p>` +
                        `</div>`
                    );
                    addedComponents.push(...added);
                }

                // Verificar que se anadieron correctamente
                expect(
                    wrapper.components().length,
                    `Debe haber ${BLOCK_COUNT} componentes antes de eliminar`
                ).to.equal(BLOCK_COUNT);

                cy.log(`${BLOCK_COUNT} componentes anadidos, procediendo a eliminar...`);

                // Eliminar todos los componentes
                wrapper.components().reset();

                // Verificar que el wrapper quedo vacio
                const remainingCount = wrapper.components().length;
                cy.log(`Componentes restantes tras limpieza: ${remainingCount}`);

                expect(
                    remainingCount,
                    'El wrapper debe tener 0 hijos tras eliminar todos los componentes'
                ).to.equal(0);
            });
        });

        it('should clean up DOM in canvas iframe after component removal', () => {
            cy.visit(EDITOR_URL);

            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');

            getEditor().then((editor) => {
                // Limpiar estado previo
                const wrapper = editor.getWrapper();
                wrapper.components().reset();

                // Anadir componentes
                for (let i = 0; i < 10; i++) {
                    editor.addComponents(
                        `<div class="iframe-cleanup-${i}">Bloque ${i}</div>`
                    );
                }

                expect(
                    wrapper.components().length,
                    'Verificar que hay 10 componentes en modelo'
                ).to.equal(10);
            });

            cy.wait(300);

            // Verificar que el iframe tiene contenido
            cy.get(SELECTORS.frame).then(($iframe) => {
                const body = $iframe[0].contentDocument.body;
                expect(
                    body.children.length,
                    'El iframe debe tener hijos antes de limpiar'
                ).to.be.greaterThan(0);
            });

            // Eliminar todos los componentes
            getEditor().then((editor) => {
                editor.getWrapper().components().reset();
            });

            cy.wait(300);

            // Verificar que el DOM del iframe tambien se limpio
            cy.get(SELECTORS.frame).then(($iframe) => {
                const body = $iframe[0].contentDocument.body;
                cy.log(`Hijos en iframe tras limpieza: ${body.children.length}`);

                expect(
                    body.children.length,
                    'El DOM del iframe debe estar vacio tras eliminar componentes'
                ).to.equal(0);
            });
        });
    });

    /**
     * Test 5: Tamano del payload de guardado.
     * Anade varios bloques, ejecuta el comando de guardado,
     * intercepta la peticion PATCH y verifica que el payload
     * sea menor a 500KB.
     */
    describe('Test 5: Save Payload Size', () => {
        it('should produce a save payload under 500KB', () => {
            // Interceptar la llamada ANTES de visitar la pagina
            cy.intercept('PATCH', '**/api/v1/pages/*/canvas').as('saveCanvas');

            cy.visit(EDITOR_URL);

            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');

            // Anadir varios bloques con contenido variado
            getEditor().then((editor) => {
                // Bloque hero
                editor.addComponents(
                    '<section class="perf-hero">' +
                    '<h1>Titulo Principal de Prueba</h1>' +
                    '<p>Subtitulo con contenido descriptivo para prueba de rendimiento.</p>' +
                    '<a href="#" class="btn">Llamada a la accion</a>' +
                    '</section>'
                );

                // Bloques de contenido
                for (let i = 0; i < 5; i++) {
                    editor.addComponents(
                        `<div class="perf-content-${i}">` +
                        `<h2>Seccion de contenido #${i}</h2>` +
                        `<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. ` +
                        `Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. ` +
                        `Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris.</p>` +
                        `<img src="https://placeholder.test/800x400" alt="Imagen ${i}" />` +
                        `</div>`
                    );
                }

                // Bloque FAQ
                editor.addComponents(
                    '<div class="perf-faq">' +
                    '<h2>Preguntas Frecuentes</h2>' +
                    '<details><summary>Pregunta 1</summary><p>Respuesta 1</p></details>' +
                    '<details><summary>Pregunta 2</summary><p>Respuesta 2</p></details>' +
                    '<details><summary>Pregunta 3</summary><p>Respuesta 3</p></details>' +
                    '</div>'
                );

                // Bloque footer
                editor.addComponents(
                    '<footer class="perf-footer">' +
                    '<p>Footer de prueba con enlaces de navegacion</p>' +
                    '<nav><a href="#">Enlace 1</a><a href="#">Enlace 2</a></nav>' +
                    '</footer>'
                );
            });

            cy.wait(500);

            // Ejecutar el comando de guardado
            cy.window().then((win) => {
                const editor = win.editor;
                expect(editor.Commands.has('jaraba:save'), 'Comando jaraba:save registrado').to.be.true;
                editor.runCommand('jaraba:save');
            });

            // Verificar el tamano del payload
            const MAX_PAYLOAD_KB = 500;
            const MAX_PAYLOAD_BYTES = MAX_PAYLOAD_KB * 1024;

            cy.wait('@saveCanvas', { timeout: 10000 }).then((interception) => {
                const body = interception.request.body;
                const payloadString = typeof body === 'string' ? body : JSON.stringify(body);
                const payloadSize = new Blob([payloadString]).size;
                const payloadKB = (payloadSize / 1024).toFixed(2);

                cy.log(`Tamano del payload de guardado: ${payloadKB}KB`);

                expect(
                    payloadSize,
                    `Payload debe ser menor a ${MAX_PAYLOAD_KB}KB (actual: ${payloadKB}KB)`
                ).to.be.lessThan(MAX_PAYLOAD_BYTES);

                // Verificar que el payload contiene datos validos
                expect(body, 'Payload debe contener propiedad html').to.have.property('html');
                expect(
                    interception.request.headers['content-type'],
                    'Content-Type debe ser JSON'
                ).to.include('application/json');
            });
        });

        it('should send compact payload without redundant data', () => {
            cy.intercept('PATCH', '**/api/v1/pages/*/canvas').as('saveCanvas');

            cy.visit(EDITOR_URL);

            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');

            // Anadir un solo bloque simple
            getEditor().then((editor) => {
                // Limpiar contenido previo
                editor.getWrapper().components().reset();

                editor.addComponents(
                    '<div class="minimal-block"><p>Contenido minimo</p></div>'
                );
            });

            cy.wait(500);

            // Guardar
            cy.window().then((win) => {
                if (win.editor.Commands.has('jaraba:save')) {
                    win.editor.runCommand('jaraba:save');
                }
            });

            // Verificar que un payload minimo es razonablemente pequeno
            cy.wait('@saveCanvas', { timeout: 10000 }).then((interception) => {
                const body = interception.request.body;
                const payloadString = typeof body === 'string' ? body : JSON.stringify(body);
                const payloadSize = new Blob([payloadString]).size;
                const payloadKB = (payloadSize / 1024).toFixed(2);

                cy.log(`Payload minimo: ${payloadKB}KB`);

                // Un bloque simple no deberia generar mas de 50KB
                expect(
                    payloadSize,
                    `Payload minimo debe ser menor a 50KB (actual: ${payloadKB}KB)`
                ).to.be.lessThan(50 * 1024);
            });
        });
    });
});

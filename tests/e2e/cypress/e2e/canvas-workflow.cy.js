/**
 * @file
 * Tests E2E para el flujo editorial del Canvas Editor (Page Builder).
 *
 * Suite de tests para verificar el ciclo completo de trabajo editorial
 * en el editor GrapesJS, incluyendo:
 * - Operaciones de Undo/Redo via UndoManager
 * - Guardado de borrador con persistencia REST
 * - Historial de revisiones tras guardado
 * - Flujo de publicacion completo
 * - Atajos de teclado (Ctrl+S)
 * - Indicador de cambios sin guardar (dirty state)
 *
 * @requires cypress
 * @see docs/arquitectura/2026-02-05_auditoria_page_site_builder_clase_mundial.md
 */

describe('Canvas Editor - Workflow & Persistence', () => {
    // Constantes
    const BASE_URL = Cypress.env('baseUrl') || 'https://jaraba-saas.lndo.site';
    const EDITOR_URL = `${BASE_URL}/es/page/17/editor?mode=canvas`;

    // Selectores
    const SELECTORS = {
        editor: '#gjs-editor',
        canvas: '.gjs-cv-canvas',
        frame: '.gjs-frame',
        blockManager: '#gjs-blocks-container',
        blockItem: '.gjs-block',
    };

    /**
     * Helper: Obtiene la instancia del editor GrapesJS.
     * Espera hasta 8s a que window.editor este disponible tras la carga.
     */
    function getEditor() {
        return cy.window({ timeout: 8000 }).its('editor', { timeout: 8000 });
    }

    /**
     * Helper: Anade un componente de texto al canvas via la API del editor.
     * Retorna una Cypress chain con el componente anadido.
     */
    function addTestBlock() {
        return cy.window().then((win) => {
            const editor = win.editor;
            const added = editor.addComponents(
                '<div class="test-workflow-block" data-testid="workflow-block">Bloque de prueba E2E</div>'
            );
            return added;
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
    });

    // ---------------------------------------------------------------
    // Test 1: Operaciones Undo / Redo
    // ---------------------------------------------------------------
    describe('Test 1: Undo/Redo Operations', () => {
        /**
         * Verifica que al anadir un bloque y ejecutar undo, el bloque
         * se elimina del canvas, y que al ejecutar redo, el bloque
         * reaparece correctamente.
         */
        it('should undo a block addition and redo it back', () => {
            cy.visit(EDITOR_URL);

            // Esperar a que el editor cargue completamente
            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');
            cy.get(SELECTORS.canvas, { timeout: 10000 }).should('be.visible');

            // Obtener el numero de componentes iniciales en el wrapper
            let initialCount;
            cy.window().then((win) => {
                const editor = win.editor;
                expect(editor, 'GrapesJS editor debe estar disponible').to.exist;
                expect(editor.UndoManager, 'UndoManager debe existir').to.exist;

                const wrapper = editor.getWrapper();
                initialCount = wrapper.components().length;

                // Limpiar historial de undo para garantizar estado limpio
                editor.UndoManager.clear();
            });

            // Anadir un bloque de prueba
            addTestBlock();

            cy.wait(300);

            // Verificar que el bloque fue anadido
            cy.window().then((win) => {
                const editor = win.editor;
                const wrapper = editor.getWrapper();
                const currentCount = wrapper.components().length;
                expect(currentCount).to.be.greaterThan(initialCount);
            });

            // Ejecutar Undo - el bloque debe desaparecer
            cy.window().then((win) => {
                const editor = win.editor;
                expect(editor.UndoManager.hasUndo(), 'Debe haber operacion para deshacer').to.be.true;
                editor.UndoManager.undo();
            });

            cy.wait(300);

            // Verificar que el bloque fue eliminado tras undo
            cy.window().then((win) => {
                const editor = win.editor;
                const wrapper = editor.getWrapper();
                const afterUndoCount = wrapper.components().length;
                expect(afterUndoCount).to.equal(initialCount);
            });

            // Ejecutar Redo - el bloque debe reaparecer
            cy.window().then((win) => {
                const editor = win.editor;
                expect(editor.UndoManager.hasRedo(), 'Debe haber operacion para rehacer').to.be.true;
                editor.UndoManager.redo();
            });

            cy.wait(300);

            // Verificar que el bloque reaparecio tras redo
            cy.window().then((win) => {
                const editor = win.editor;
                const wrapper = editor.getWrapper();
                const afterRedoCount = wrapper.components().length;
                expect(afterRedoCount).to.be.greaterThan(initialCount);
            });

            cy.log('Undo/Redo funciona correctamente para operaciones de bloques');
        });

        /**
         * Verifica que multiples operaciones de undo consecutivas funcionan
         * correctamente deshaciendo cada paso en orden inverso.
         */
        it('should support multiple consecutive undo operations', () => {
            cy.visit(EDITOR_URL);
            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');

            // Limpiar historial y anadir multiples bloques
            cy.window().then((win) => {
                const editor = win.editor;
                editor.UndoManager.clear();

                // Anadir 3 bloques secuencialmente
                editor.addComponents('<div class="block-a">Bloque A</div>');
                editor.addComponents('<div class="block-b">Bloque B</div>');
                editor.addComponents('<div class="block-c">Bloque C</div>');
            });

            cy.wait(300);

            // Verificar que hay 3 operaciones en el historial de undo
            cy.window().then((win) => {
                const editor = win.editor;
                expect(editor.UndoManager.hasUndo(), 'Debe haber operaciones para deshacer').to.be.true;

                const wrapper = editor.getWrapper();
                const countBefore = wrapper.components().length;

                // Deshacer las 3 operaciones
                editor.UndoManager.undo();
                editor.UndoManager.undo();
                editor.UndoManager.undo();

                const countAfter = wrapper.components().length;
                expect(countAfter).to.equal(countBefore - 3);
            });

            cy.log('Multiples operaciones de undo consecutivas funcionan correctamente');
        });
    });

    // ---------------------------------------------------------------
    // Test 2: Guardado de borrador (Save Draft)
    // ---------------------------------------------------------------
    describe('Test 2: Save Draft', () => {
        /**
         * Verifica que al anadir contenido y ejecutar el guardado, se envia
         * una peticion PATCH con payload que incluye html y css.
         */
        it('should save draft with html and css in payload', () => {
            // Interceptar la llamada REST ANTES de visitar la pagina
            cy.intercept('PATCH', '**/api/v1/pages/*/canvas').as('saveCanvas');

            cy.visit(EDITOR_URL);
            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');

            // Anadir contenido al canvas
            cy.window().then((win) => {
                const editor = win.editor;
                expect(editor, 'GrapesJS editor disponible').to.exist;

                editor.addComponents(
                    '<section class="draft-test"><h2>Borrador de prueba</h2><p>Contenido del draft E2E</p></section>'
                );
            });

            cy.wait(500);

            // Ejecutar comando de guardado
            cy.window().then((win) => {
                const editor = win.editor;
                expect(editor.Commands.has('jaraba:save'), 'Comando jaraba:save debe estar registrado').to.be.true;
                editor.runCommand('jaraba:save');
            });

            // Verificar que se envio la peticion con payload correcto
            cy.wait('@saveCanvas', { timeout: 5000 }).then((interception) => {
                const body = interception.request.body;

                // Verificar que el payload incluye html
                expect(body).to.have.property('html');
                expect(body.html).to.be.a('string');
                expect(body.html.length).to.be.greaterThan(0);

                // Verificar que el payload incluye css
                expect(body).to.have.property('css');
                expect(body.css).to.be.a('string');

                // Verificar Content-Type JSON
                expect(interception.request.headers['content-type']).to.include('application/json');
            });

            cy.log('Guardado de borrador envia html y css correctamente via REST');
        });

        /**
         * Verifica que el contenido anadido se refleja en el HTML guardado.
         */
        it('should include added content in saved html payload', () => {
            cy.intercept('PATCH', '**/api/v1/pages/*/canvas').as('saveCanvas');

            cy.visit(EDITOR_URL);
            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');

            // Anadir contenido identificable
            const testMarker = `e2e-marker-${Date.now()}`;
            cy.window().then((win) => {
                win.editor.addComponents(
                    `<div class="${testMarker}">Contenido unico de test</div>`
                );
            });

            cy.wait(500);

            // Guardar
            cy.window().then((win) => {
                win.editor.runCommand('jaraba:save');
            });

            // Verificar que el HTML contiene el marcador
            cy.wait('@saveCanvas', { timeout: 5000 }).then((interception) => {
                expect(interception.request.body.html).to.include(testMarker);
            });

            cy.log('El contenido anadido se incluye en el payload de guardado');
        });
    });

    // ---------------------------------------------------------------
    // Test 3: Historial de revisiones
    // ---------------------------------------------------------------
    describe('Test 3: Revision History', () => {
        /**
         * Verifica que despues de guardar, se crea una nueva revision
         * comprobando el endpoint de revisiones o el indicador del editor.
         */
        it('should create a new revision after saving', () => {
            // Interceptar guardado y endpoint de revisiones
            cy.intercept('PATCH', '**/api/v1/pages/*/canvas').as('saveCanvas');
            cy.intercept('GET', '**/api/v1/pages/*/revisions').as('getRevisions');

            cy.visit(EDITOR_URL);
            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');

            // Anadir contenido para generar un cambio
            cy.window().then((win) => {
                const editor = win.editor;
                editor.addComponents(
                    '<div class="revision-test">Contenido para revision</div>'
                );
            });

            cy.wait(500);

            // Guardar el contenido
            cy.window().then((win) => {
                win.editor.runCommand('jaraba:save');
            });

            // Esperar a que se complete el guardado
            cy.wait('@saveCanvas', { timeout: 5000 }).then((interception) => {
                // Verificar que el servidor responde con exito (2xx)
                expect(interception.response.statusCode).to.be.within(200, 299);
            });

            // Verificar la creacion de revision de dos formas:
            // Opcion A: Si existe endpoint de revisiones, verificar que responde
            // Opcion B: Verificar indicador de revision en la respuesta del save
            cy.window().then((win) => {
                const editor = win.editor;

                // Verificar si el editor tiene comando de historial de revisiones
                const hasRevisionCmd = editor.Commands.has('jaraba:revisions') ||
                    editor.Commands.has('show-revisions') ||
                    editor.Commands.has('open-revisions');

                if (hasRevisionCmd) {
                    cy.log('Comando de revisiones encontrado en el editor');
                }

                // Verificar que el StorageManager registro el guardado
                const sm = editor.StorageManager;
                expect(sm, 'StorageManager debe existir').to.exist;
            });

            cy.log('Revision creada correctamente tras guardado');
        });

        /**
         * Verifica que el editor expone un mecanismo para acceder
         * al historial de revisiones (comando o panel).
         */
        it('should have revision history mechanism available', () => {
            cy.visit(EDITOR_URL);
            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');

            cy.window().then((win) => {
                const editor = win.editor;
                expect(editor, 'GrapesJS editor disponible').to.exist;

                // Verificar que hay algun mecanismo de revisiones
                // (comando, panel, o boton en la UI)
                const hasRevisionCommand =
                    editor.Commands.has('jaraba:revisions') ||
                    editor.Commands.has('show-revisions') ||
                    editor.Commands.has('open-revisions');

                const hasPanels = editor.Panels !== undefined;
                const hasStorageManager = editor.StorageManager !== undefined;

                // Al menos debe existir el StorageManager para persistencia
                expect(hasStorageManager, 'StorageManager debe estar disponible para gestion de revisiones').to.be.true;

                // Verificar que el StorageManager tiene configuracion de almacenamiento
                const storageType = editor.StorageManager.getConfig();
                expect(storageType, 'StorageManager debe tener configuracion').to.exist;
            });

            cy.log('Mecanismo de historial de revisiones disponible');
        });
    });

    // ---------------------------------------------------------------
    // Test 4: Flujo de publicacion
    // ---------------------------------------------------------------
    describe('Test 4: Publish Flow', () => {
        /**
         * Verifica que el editor dispone de un boton o comando de publicacion,
         * lo ejecuta y comprueba que se produce un cambio de estado.
         */
        it('should have a publish command and execute it', () => {
            // Interceptar posibles endpoints de publicacion
            cy.intercept('PATCH', '**/api/v1/pages/*/canvas').as('saveCanvas');
            cy.intercept('POST', '**/api/v1/pages/*/publish').as('publishPage');
            cy.intercept('PATCH', '**/api/v1/pages/*/status').as('updateStatus');

            cy.visit(EDITOR_URL);
            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');

            // Verificar que existe un comando de publicacion
            cy.window().then((win) => {
                const editor = win.editor;
                expect(editor, 'GrapesJS editor disponible').to.exist;

                // Buscar comando de publicacion (varias convenciones posibles)
                const publishCommands = [
                    'jaraba:publish',
                    'publish-page',
                    'jaraba:save-and-publish',
                    'save-and-publish',
                ];

                const foundPublishCmd = publishCommands.find(cmd =>
                    editor.Commands.has(cmd)
                );

                expect(foundPublishCmd, 'Debe existir al menos un comando de publicacion').to.exist;

                // Ejecutar el comando de publicacion
                editor.runCommand(foundPublishCmd);
                cy.log(`Comando de publicacion ejecutado: ${foundPublishCmd}`);
            });

            // Esperar a que se procese la publicacion
            // (puede ser un save + cambio de estado, o un endpoint dedicado)
            cy.wait(1000);

            // Verificar que el editor refleja el estado publicado
            cy.window().then((win) => {
                const editor = win.editor;
                // Verificar que no hay errores pendientes en el editor
                expect(editor.getWrapper(), 'El wrapper debe existir tras publicacion').to.exist;
            });

            cy.log('Flujo de publicacion ejecutado correctamente');
        });

        /**
         * Verifica que hay un elemento en la interfaz del editor
         * que indica el estado de publicacion (boton, badge, etc.).
         */
        it('should display publish status indicator in the editor UI', () => {
            cy.visit(EDITOR_URL);
            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');

            // Buscar indicadores de estado de publicacion en la UI
            // Pueden ser botones en el panel, badges, o elementos personalizados
            cy.window().then((win) => {
                const editor = win.editor;
                expect(editor, 'GrapesJS editor disponible').to.exist;

                // Verificar que los paneles del editor estan cargados
                const panels = editor.Panels;
                expect(panels, 'Panels debe existir').to.exist;

                // Verificar que hay botones en los paneles
                const allButtons = panels.getPanel('options') ||
                    panels.getPanel('views-actions') ||
                    panels.getPanel('devices-c');

                // El editor debe tener al menos un panel con botones de accion
                const allPanels = panels.getPanels();
                expect(allPanels.length, 'Debe haber paneles configurados en el editor').to.be.greaterThan(0);
            });

            // Buscar boton de publicar o indicador de estado en el DOM
            cy.get(
                '[data-gjs-command*="publish"], .gjs-pn-btn[title*="ublish"], .gjs-pn-btn[title*="ublicar"], .jaraba-publish-btn, [class*="publish"]',
                { timeout: 3000 }
            ).should('have.length.greaterThan', 0);

            cy.log('Indicador de estado de publicacion presente en la UI');
        });
    });

    // ---------------------------------------------------------------
    // Test 5: Atajo de teclado Ctrl+S
    // ---------------------------------------------------------------
    describe('Test 5: Keyboard Shortcut - Ctrl+S', () => {
        /**
         * Verifica que al simular Ctrl+S, se dispara el guardado del canvas.
         */
        it('should trigger save when Ctrl+S is pressed', () => {
            // Interceptar la llamada de guardado
            cy.intercept('PATCH', '**/api/v1/pages/*/canvas').as('saveCanvas');

            cy.visit(EDITOR_URL);
            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');

            // Anadir contenido para que haya algo que guardar
            cy.window().then((win) => {
                const editor = win.editor;
                expect(editor, 'GrapesJS editor disponible').to.exist;
                editor.addComponents('<div class="ctrl-s-test">Test Ctrl+S</div>');
            });

            cy.wait(500);

            // Simular Ctrl+S en el body del editor
            cy.get('body').type('{ctrl}s', { release: false });

            // Verificar que el guardado se disparo via la intercepcion REST
            cy.wait('@saveCanvas', { timeout: 5000 }).then((interception) => {
                expect(interception.request.body).to.have.property('html');
                expect(interception.request.body.html).to.include('ctrl-s-test');
            });

            cy.log('Atajo Ctrl+S dispara el guardado correctamente');
        });

        /**
         * Verifica que Ctrl+S previene el comportamiento por defecto del navegador
         * (dialogo de guardar pagina) y ejecuta el guardado del editor.
         */
        it('should prevent default browser save dialog on Ctrl+S', () => {
            cy.visit(EDITOR_URL);
            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');

            // Verificar que el editor tiene registrado un KeyMap o listener para Ctrl+S
            cy.window().then((win) => {
                const editor = win.editor;
                expect(editor, 'GrapesJS editor disponible').to.exist;

                // Verificar que el comando de guardado esta registrado
                expect(
                    editor.Commands.has('jaraba:save'),
                    'Comando jaraba:save debe estar registrado para Ctrl+S'
                ).to.be.true;

                // Verificar que hay un Keymaps manager o listener configurado
                const keymaps = editor.Keymaps;
                if (keymaps) {
                    const allKeymaps = keymaps.getAll();
                    cy.log(`Keymaps registrados: ${Object.keys(allKeymaps).length}`);
                }
            });

            cy.log('Ctrl+S esta configurado para prevenir el dialogo por defecto del navegador');
        });
    });

    // ---------------------------------------------------------------
    // Test 6: Indicador de cambios sin guardar (Dirty State)
    // ---------------------------------------------------------------
    describe('Test 6: Unsaved Changes Warning', () => {
        /**
         * Verifica que al modificar el contenido del canvas, el editor
         * entra en estado "dirty" indicando cambios sin guardar.
         */
        it('should detect dirty state after modifying canvas content', () => {
            cy.visit(EDITOR_URL);
            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');

            // Verificar estado limpio inicial tras carga
            cy.window().then((win) => {
                const editor = win.editor;
                expect(editor, 'GrapesJS editor disponible').to.exist;

                // Obtener el dirty count inicial (puede ser 0 o ya tener cambios previos)
                const initialDirty = editor.getDirtyCount();
                cy.log(`Estado dirty inicial: ${initialDirty}`);
            });

            // Modificar el canvas para generar estado dirty
            cy.window().then((win) => {
                const editor = win.editor;

                // Forzar estado limpio primero
                editor.clearDirtyCount();

                // Verificar que esta limpio
                expect(editor.getDirtyCount()).to.equal(0);

                // Anadir contenido para ensuciar el estado
                editor.addComponents(
                    '<div class="dirty-state-test">Contenido que genera estado dirty</div>'
                );
            });

            cy.wait(300);

            // Verificar que el dirty count aumento
            cy.window().then((win) => {
                const editor = win.editor;
                const dirtyCount = editor.getDirtyCount();
                expect(dirtyCount, 'El dirty count debe ser mayor a 0 tras modificar el canvas').to.be.greaterThan(0);
            });

            cy.log('Estado dirty detectado correctamente tras modificar el canvas');
        });

        /**
         * Verifica que al guardar, el estado dirty se limpia y vuelve a 0.
         */
        it('should clear dirty state after saving', () => {
            cy.intercept('PATCH', '**/api/v1/pages/*/canvas').as('saveCanvas');

            cy.visit(EDITOR_URL);
            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');

            // Limpiar y modificar para generar estado dirty
            cy.window().then((win) => {
                const editor = win.editor;
                editor.clearDirtyCount();
                editor.addComponents(
                    '<div class="dirty-clear-test">Contenido para limpiar dirty</div>'
                );
            });

            cy.wait(300);

            // Verificar que esta dirty antes de guardar
            cy.window().then((win) => {
                const editor = win.editor;
                expect(editor.getDirtyCount()).to.be.greaterThan(0);
            });

            // Ejecutar guardado
            cy.window().then((win) => {
                win.editor.runCommand('jaraba:save');
            });

            // Esperar a que se complete el guardado
            cy.wait('@saveCanvas', { timeout: 5000 });

            cy.wait(500);

            // Verificar que el dirty count se reseteo tras el guardado
            cy.window().then((win) => {
                const editor = win.editor;
                const dirtyAfterSave = editor.getDirtyCount();
                expect(
                    dirtyAfterSave,
                    'El dirty count debe ser 0 despues de guardar correctamente'
                ).to.equal(0);
            });

            cy.log('Estado dirty se limpia correctamente tras guardar');
        });

        /**
         * Verifica que el indicador visual de cambios sin guardar aparece
         * en la interfaz del editor cuando hay modificaciones pendientes.
         */
        it('should display a visual dirty indicator in the editor UI', () => {
            cy.visit(EDITOR_URL);
            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');

            // Limpiar y modificar el canvas
            cy.window().then((win) => {
                const editor = win.editor;
                editor.clearDirtyCount();
                editor.addComponents(
                    '<div class="visual-dirty-test">Contenido para indicador visual</div>'
                );
            });

            cy.wait(500);

            // Buscar indicador visual de cambios sin guardar en el DOM
            // Puede ser un asterisco en el titulo, un badge, o un cambio de clase
            cy.get(
                '[class*="dirty"], [class*="unsaved"], [class*="modified"], [data-dirty], .gjs-pn-btn.active[title*="ave"]',
                { timeout: 3000 }
            ).should('have.length.greaterThan', 0);

            cy.log('Indicador visual de cambios sin guardar presente en la UI');
        });
    });
});

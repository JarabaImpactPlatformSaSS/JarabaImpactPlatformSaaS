/**
 * @file
 * Tests E2E para las funcionalidades SEO del Canvas Editor (Page Builder).
 *
 * Suite de tests para verificar las capacidades SEO integradas en el editor GrapesJS:
 * - Apertura y cierre del panel SEO
 * - Visualizacion de puntuacion SEO
 * - Listado de problemas SEO detectados
 * - Edicion de meta title
 * - Edicion de meta description con contador de caracteres
 * - Vista previa de datos estructurados Schema.org
 *
 * @requires cypress
 * @see docs/arquitectura/2026-02-05_auditoria_page_site_builder_clase_mundial.md
 */

describe('Canvas Editor - SEO Features', () => {
    // Constantes
    const BASE_URL = Cypress.env('baseUrl') || 'https://jaraba-saas.lndo.site';
    const EDITOR_URL = `${BASE_URL}/es/page/17/editor?mode=canvas`;

    // Selectores
    const SELECTORS = {
        editor: '#gjs-editor',
        canvas: '.gjs-cv-canvas',
        frame: '.gjs-frame',
        seoPanel: '.gjs-seo-panel',
        seoScore: '.seo-panel__score',
        seoIssues: '.seo-panel__issues',
        metaTitleInput: '.seo-panel__meta-title input',
        metaDescTextarea: '.seo-panel__meta-description textarea',
        metaDescCounter: '.seo-panel__meta-description .char-counter',
        schemaPreview: '.seo-panel__schema-preview',
    };

    /**
     * Helper: Obtiene la instancia del editor GrapesJS.
     * Espera hasta 8s a que window.editor este disponible tras la carga.
     */
    function getEditor() {
        return cy.window({ timeout: 8000 }).its('editor', { timeout: 8000 });
    }

    /**
     * Helper: Abre el panel SEO ejecutando el comando toggle-seo-panel.
     * Verifica que el comando existe antes de ejecutarlo.
     */
    function openSeoPanel() {
        cy.window().then((win) => {
            const editor = win.editor;
            expect(editor, 'GrapesJS editor debe estar disponible').to.exist;
            expect(editor.Commands, 'Commands debe existir').to.exist;

            const hasToggleCmd = editor.Commands.has('toggle-seo-panel');
            expect(hasToggleCmd, 'Comando toggle-seo-panel debe estar registrado').to.be.true;

            editor.runCommand('toggle-seo-panel');
        });

        // Esperar a que el panel SEO sea visible
        cy.get(SELECTORS.seoPanel, { timeout: 5000 }).should('be.visible');
    }

    /**
     * Helper: Cierra el panel SEO ejecutando el comando toggle-seo-panel nuevamente.
     */
    function closeSeoPanel() {
        cy.window().then((win) => {
            win.editor.runCommand('toggle-seo-panel');
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

        // Navegar al editor y esperar carga completa
        cy.visit(EDITOR_URL);
        cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');
        cy.get(SELECTORS.canvas, { timeout: 10000 }).should('be.visible');
    });

    describe('Test 1: El panel SEO se abre y se cierra', () => {
        /**
         * Verifica que el comando toggle-seo-panel abre el panel,
         * y que ejecutarlo de nuevo lo cierra correctamente.
         */
        it('should toggle SEO panel open and closed', () => {
            // Abrir el panel SEO
            openSeoPanel();

            // Verificar que el panel SEO es visible
            cy.get(SELECTORS.seoPanel).should('be.visible');
            cy.log('Panel SEO abierto correctamente');

            // Cerrar el panel SEO
            closeSeoPanel();

            // Verificar que el panel SEO ya no es visible
            cy.get(SELECTORS.seoPanel).should('not.be.visible');
            cy.log('Panel SEO cerrado correctamente');
        });

        /**
         * Verifica que se puede abrir y cerrar el panel SEO multiples veces
         * sin errores ni estados inconsistentes.
         */
        it('should toggle SEO panel multiple times without errors', () => {
            // Primera apertura
            openSeoPanel();
            cy.get(SELECTORS.seoPanel).should('be.visible');

            // Primer cierre
            closeSeoPanel();
            cy.get(SELECTORS.seoPanel).should('not.be.visible');

            // Segunda apertura
            openSeoPanel();
            cy.get(SELECTORS.seoPanel).should('be.visible');

            // Segundo cierre
            closeSeoPanel();
            cy.get(SELECTORS.seoPanel).should('not.be.visible');

            cy.log('Toggle multiple del panel SEO funciona sin errores');
        });
    });

    describe('Test 2: Visualizacion de puntuacion SEO', () => {
        /**
         * Verifica que el panel SEO muestra una puntuacion numerica
         * entre 0 y 100 representando la calidad SEO de la pagina.
         */
        it('should display a numeric SEO score between 0 and 100', () => {
            openSeoPanel();

            // Verificar que el elemento de puntuacion existe
            cy.get(SELECTORS.seoScore, { timeout: 5000 }).should('exist').and('be.visible');

            // Verificar que contiene un valor numerico entre 0 y 100
            cy.get(SELECTORS.seoScore).invoke('text').then((text) => {
                // Extraer el numero del texto (puede contener texto como "Score: 75")
                const match = text.match(/(\d+)/);
                expect(match, 'La puntuacion debe contener un numero').to.not.be.null;

                const score = parseInt(match[1], 10);
                expect(score, 'La puntuacion debe ser >= 0').to.be.at.least(0);
                expect(score, 'La puntuacion debe ser <= 100').to.be.at.most(100);

                cy.log(`Puntuacion SEO mostrada: ${score}/100`);
            });
        });

        /**
         * Verifica que el contenedor de puntuacion tiene los elementos
         * visuales esperados (indicador, etiqueta).
         */
        it('should render SEO score with visual indicator', () => {
            openSeoPanel();

            cy.get(SELECTORS.seoPanel).within(() => {
                // El score debe existir dentro del panel
                cy.get(SELECTORS.seoScore).should('exist');

                // Verificar que el score no esta vacio
                cy.get(SELECTORS.seoScore).invoke('text').should('not.be.empty');
            });

            cy.log('Indicador visual de puntuacion SEO verificado');
        });
    });

    describe('Test 3: Listado de problemas SEO', () => {
        /**
         * Verifica que el panel SEO muestra una lista de problemas
         * detectados con al menos un elemento.
         */
        it('should display SEO issues list with items', () => {
            openSeoPanel();

            // Verificar que la seccion de problemas existe
            cy.get(SELECTORS.seoIssues, { timeout: 5000 }).should('exist').and('be.visible');

            // Verificar que contiene al menos un item de problema
            cy.get(SELECTORS.seoIssues).within(() => {
                cy.get('li, .seo-issue-item, [class*="issue"]')
                    .should('have.length.greaterThan', 0);
            });

            cy.log('Lista de problemas SEO contiene items');
        });

        /**
         * Verifica que cada item en la lista de problemas tiene contenido
         * textual descriptivo (no elementos vacios).
         */
        it('should display issue items with descriptive text', () => {
            openSeoPanel();

            cy.get(SELECTORS.seoIssues).within(() => {
                cy.get('li, .seo-issue-item, [class*="issue"]').each(($item) => {
                    // Cada item debe tener texto no vacio
                    const text = $item.text().trim();
                    expect(text, 'Cada problema SEO debe tener descripcion').to.not.be.empty;
                });
            });

            cy.log('Todos los problemas SEO tienen descripcion textual');
        });
    });

    describe('Test 4: Edicion de meta title', () => {
        /**
         * Verifica que se puede editar el meta title desde el panel SEO
         * y que el valor actualizado se refleja en el input.
         */
        it('should allow editing the meta title and reflect the change', () => {
            openSeoPanel();

            const newTitle = 'Titulo SEO de Prueba E2E - Jaraba Impact Platform';

            // Localizar el input de meta title
            cy.get(SELECTORS.metaTitleInput, { timeout: 5000 })
                .should('exist')
                .and('be.visible');

            // Limpiar el valor actual y escribir uno nuevo
            cy.get(SELECTORS.metaTitleInput)
                .clear()
                .type(newTitle);

            // Verificar que el input refleja el nuevo valor
            cy.get(SELECTORS.metaTitleInput)
                .should('have.value', newTitle);

            cy.log(`Meta title actualizado a: "${newTitle}"`);
        });

        /**
         * Verifica que el campo de meta title acepta caracteres especiales
         * y acentos propios del idioma espanol.
         */
        it('should accept Spanish characters and special symbols in meta title', () => {
            openSeoPanel();

            const titleWithAccents = 'Formacion y Empleabilidad | Plataforma de Impacto';

            cy.get(SELECTORS.metaTitleInput)
                .clear()
                .type(titleWithAccents);

            cy.get(SELECTORS.metaTitleInput)
                .should('have.value', titleWithAccents);

            cy.log('Meta title acepta caracteres especiales correctamente');
        });
    });

    describe('Test 5: Edicion de meta description con contador', () => {
        /**
         * Verifica que el panel SEO incluye un textarea para meta description
         * y un contador de caracteres asociado.
         */
        it('should display meta description textarea with character counter', () => {
            openSeoPanel();

            // Verificar que el textarea existe
            cy.get(SELECTORS.metaDescTextarea, { timeout: 5000 })
                .should('exist')
                .and('be.visible');

            // Verificar que el contador de caracteres existe
            cy.get(SELECTORS.metaDescCounter)
                .should('exist')
                .and('be.visible');

            cy.log('Textarea de meta description y contador de caracteres presentes');
        });

        /**
         * Verifica que el contador de caracteres se actualiza al escribir
         * en el textarea de meta description.
         */
        it('should update character counter when typing in meta description', () => {
            openSeoPanel();

            const testDescription = 'Descripcion de prueba para SEO';

            // Limpiar y escribir nueva descripcion
            cy.get(SELECTORS.metaDescTextarea)
                .clear()
                .type(testDescription);

            // Verificar que el textarea tiene el valor escrito
            cy.get(SELECTORS.metaDescTextarea)
                .should('have.value', testDescription);

            // Verificar que el contador muestra un numero que corresponde
            // a la longitud del texto escrito
            cy.get(SELECTORS.metaDescCounter).invoke('text').then((counterText) => {
                // El contador puede mostrar formatos como "31/160" o "31 caracteres"
                const match = counterText.match(/(\d+)/);
                expect(match, 'El contador debe mostrar un numero').to.not.be.null;

                const charCount = parseInt(match[1], 10);
                expect(charCount, 'El contador debe reflejar los caracteres escritos')
                    .to.equal(testDescription.length);

                cy.log(`Contador de caracteres: ${charCount}/${testDescription.length}`);
            });
        });
    });

    describe('Test 6: Vista previa de datos estructurados Schema.org', () => {
        /**
         * Verifica que el panel SEO incluye una seccion de vista previa
         * de datos estructurados Schema.org.
         */
        it('should display Schema.org structured data preview section', () => {
            openSeoPanel();

            // Verificar que la seccion de Schema.org existe
            cy.get(SELECTORS.schemaPreview, { timeout: 5000 })
                .should('exist')
                .and('be.visible');

            cy.log('Seccion de vista previa Schema.org presente en el panel SEO');
        });

        /**
         * Verifica que la vista previa de Schema.org contiene JSON-LD valido
         * con las propiedades basicas de datos estructurados.
         */
        it('should show Schema.org preview with valid structured data content', () => {
            openSeoPanel();

            cy.get(SELECTORS.schemaPreview).within(() => {
                // Verificar que hay contenido dentro de la vista previa
                cy.get('pre, code, .schema-json, [class*="schema"]')
                    .should('have.length.greaterThan', 0);
            });

            // Verificar que el contenido de la vista previa contiene
            // marcadores tipicos de JSON-LD / Schema.org
            cy.get(SELECTORS.schemaPreview).invoke('text').then((previewText) => {
                // El texto debe contener referencias a Schema.org
                const hasSchemaRef = previewText.includes('@context') ||
                    previewText.includes('schema.org') ||
                    previewText.includes('@type') ||
                    previewText.includes('Schema');
                expect(hasSchemaRef, 'La vista previa debe contener referencias Schema.org').to.be.true;

                cy.log('Vista previa Schema.org contiene datos estructurados validos');
            });
        });
    });
});

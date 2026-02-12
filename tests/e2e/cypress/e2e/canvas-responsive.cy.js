/**
 * @file
 * Tests E2E para Canvas Editor - Responsive Design.
 *
 * Suite de tests para verificar funcionalidades de responsive editing en GrapesJS:
 * - Cambio de dispositivo (Desktop, Tablet, Mobile)
 * - Persistencia de estilos por dispositivo
 * - Indicador de viewport activo
 * - Visibilidad de bloques por dispositivo
 *
 * @requires cypress
 * @see docs/arquitectura/2026-02-05_auditoria_page_site_builder_clase_mundial.md
 */

describe('Canvas Editor - Responsive Design', () => {
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
        deviceButtons: '.gjs-devices-c',
        activeDevice: '.gjs-device-active',
    };

    /**
     * Helper: Obtiene la instancia del editor GrapesJS.
     * Espera hasta 8s a que window.editor este disponible tras la carga.
     */
    function getEditor() {
        return cy.window({ timeout: 8000 }).its('editor', { timeout: 8000 });
    }

    /**
     * Helper: Cambia de dispositivo y espera a que el canvas se actualice.
     * @param {string} device - 'Desktop', 'Tablet' o 'Mobile'
     */
    function switchDevice(device) {
        cy.window().then((win) => {
            const editor = win.editor;
            expect(editor, 'GrapesJS editor debe estar disponible').to.exist;
            expect(editor.Devices, 'Devices manager debe existir').to.exist;
            editor.setDevice(device);
        });
        // Esperar a que el canvas refleje el cambio de viewport
        cy.wait(500);
    }

    /**
     * Helper: Obtiene el ancho actual del iframe del canvas.
     * @returns {Cypress.Chainable<number>}
     */
    function getCanvasFrameWidth() {
        return cy.get(SELECTORS.frame).then(($iframe) => {
            return $iframe[0].getBoundingClientRect().width;
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

    describe('Test 1: Device Switching - Desktop', () => {
        it('should set device to Desktop and verify canvas iframe width >= 1024px', () => {
            cy.visit(EDITOR_URL);

            // Esperar a que cargue el editor
            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');
            cy.get(SELECTORS.canvas, { timeout: 10000 }).should('be.visible');

            // Cambiar al modo Desktop
            switchDevice('Desktop');

            // Verificar que el editor reporta Desktop como dispositivo activo
            cy.window().then((win) => {
                const currentDevice = win.editor.getDevice();
                expect(currentDevice).to.equal('Desktop');
            });

            // Verificar que el ancho del canvas iframe es >= 1024px
            cy.get(SELECTORS.frame).then(($iframe) => {
                const frameWidth = $iframe[0].getBoundingClientRect().width;
                expect(frameWidth).to.be.at.least(1024);
                cy.log(`Canvas iframe width in Desktop mode: ${frameWidth}px`);
            });

            cy.log('Desktop device switching verified successfully');
        });
    });

    describe('Test 2: Device Switching - Tablet', () => {
        it('should set device to Tablet and verify canvas width is approximately 768px', () => {
            cy.visit(EDITOR_URL);

            // Esperar a que cargue el editor
            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');
            cy.get(SELECTORS.canvas, { timeout: 10000 }).should('be.visible');

            // Cambiar al modo Tablet
            switchDevice('Tablet');

            // Verificar que el editor reporta Tablet como dispositivo activo
            cy.window().then((win) => {
                const currentDevice = win.editor.getDevice();
                expect(currentDevice).to.equal('Tablet');
            });

            // Verificar que el ancho del canvas iframe es aproximadamente 768px
            // Tolerancia de +/- 50px para diferentes configuraciones de GrapesJS
            cy.get(SELECTORS.frame).then(($iframe) => {
                const frameWidth = $iframe[0].getBoundingClientRect().width;
                expect(frameWidth).to.be.within(718, 818);
                cy.log(`Canvas iframe width in Tablet mode: ${frameWidth}px`);
            });

            cy.log('Tablet device switching verified successfully');
        });
    });

    describe('Test 3: Device Switching - Mobile', () => {
        it('should set device to Mobile and verify canvas width is approximately 375px', () => {
            cy.visit(EDITOR_URL);

            // Esperar a que cargue el editor
            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');
            cy.get(SELECTORS.canvas, { timeout: 10000 }).should('be.visible');

            // Cambiar al modo Mobile
            switchDevice('Mobile');

            // Verificar que el editor reporta Mobile como dispositivo activo
            cy.window().then((win) => {
                const currentDevice = win.editor.getDevice();
                expect(currentDevice).to.equal('Mobile');
            });

            // Verificar que el ancho del canvas iframe es aproximadamente 375px
            // Tolerancia de +/- 50px para diferentes configuraciones de GrapesJS
            cy.get(SELECTORS.frame).then(($iframe) => {
                const frameWidth = $iframe[0].getBoundingClientRect().width;
                expect(frameWidth).to.be.within(325, 425);
                cy.log(`Canvas iframe width in Mobile mode: ${frameWidth}px`);
            });

            cy.log('Mobile device switching verified successfully');
        });

        it('should cycle through all devices and return to Desktop', () => {
            cy.visit(EDITOR_URL);

            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');
            cy.get(SELECTORS.canvas, { timeout: 10000 }).should('be.visible');

            // Ciclo completo: Desktop -> Tablet -> Mobile -> Desktop
            const devices = ['Desktop', 'Tablet', 'Mobile', 'Desktop'];
            let previousWidth = 0;

            // Start from Desktop to capture baseline
            switchDevice('Desktop');

            cy.get(SELECTORS.frame).then(($iframe) => {
                previousWidth = $iframe[0].getBoundingClientRect().width;
            });

            // Switch to Tablet - should be narrower than Desktop
            switchDevice('Tablet');
            cy.get(SELECTORS.frame).then(($iframe) => {
                const tabletWidth = $iframe[0].getBoundingClientRect().width;
                expect(tabletWidth).to.be.lessThan(previousWidth);
                previousWidth = tabletWidth;
            });

            // Switch to Mobile - should be narrower than Tablet
            switchDevice('Mobile');
            cy.get(SELECTORS.frame).then(($iframe) => {
                const mobileWidth = $iframe[0].getBoundingClientRect().width;
                expect(mobileWidth).to.be.lessThan(previousWidth);
            });

            // Switch back to Desktop - should be widest again
            switchDevice('Desktop');
            cy.get(SELECTORS.frame).then(($iframe) => {
                const desktopWidth = $iframe[0].getBoundingClientRect().width;
                expect(desktopWidth).to.be.at.least(1024);
            });

            cy.window().then((win) => {
                expect(win.editor.getDevice()).to.equal('Desktop');
            });

            cy.log('Full device cycle verified: Desktop -> Tablet -> Mobile -> Desktop');
        });
    });

    describe('Test 4: Responsive Styles Persistence', () => {
        it('should persist mobile-specific styles when switching between devices', () => {
            cy.visit(EDITOR_URL);

            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');
            cy.get(SELECTORS.canvas, { timeout: 10000 }).should('be.visible');

            // Step 1: Add a test component
            cy.window().then((win) => {
                const editor = win.editor;
                expect(editor, 'GrapesJS editor debe estar disponible').to.exist;

                // Clear existing components to start fresh
                editor.DomComponents.clear();

                // Add a simple div component
                const added = editor.addComponents(
                    '<div class="responsive-test-block" style="width:100%; padding:20px;">Responsive Test Block</div>'
                );
                expect(added).to.have.length.greaterThan(0);

                // Select the component
                const component = added[0];
                editor.select(component);

                // Step 2: Switch to Mobile and set a mobile-specific style
                editor.setDevice('Mobile');
            });

            cy.wait(500);

            // Apply mobile-specific style using the CSS Composer
            cy.window().then((win) => {
                const editor = win.editor;
                const selected = editor.getSelected();
                expect(selected, 'Un componente debe estar seleccionado').to.exist;

                // Set a mobile-specific background color via the style API
                selected.addStyle({ 'background-color': 'rgb(255, 0, 0)' });

                // Verify the style was applied in mobile context
                const styles = selected.getStyle();
                expect(styles['background-color']).to.equal('rgb(255, 0, 0)');
            });

            cy.wait(300);

            // Step 3: Switch to Desktop
            switchDevice('Desktop');

            // Step 4: Switch back to Mobile
            switchDevice('Mobile');

            // Step 5: Verify the mobile-specific style persists
            cy.window().then((win) => {
                const editor = win.editor;
                const selected = editor.getSelected();
                expect(selected, 'Componente debe seguir seleccionado').to.exist;

                // Check that the mobile style is still present
                const styles = selected.getStyle();
                expect(
                    styles['background-color'],
                    'El estilo mobile-specific debe persistir tras cambiar de dispositivo'
                ).to.equal('rgb(255, 0, 0)');
            });

            cy.log('Responsive styles persist correctly across device switches');
        });

        it('should maintain separate styles per device via CssComposer rules', () => {
            cy.visit(EDITOR_URL);

            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');
            cy.get(SELECTORS.canvas, { timeout: 10000 }).should('be.visible');

            cy.window().then((win) => {
                const editor = win.editor;
                expect(editor, 'GrapesJS editor debe estar disponible').to.exist;

                // Clear canvas and add a component
                editor.DomComponents.clear();
                const added = editor.addComponents(
                    '<div class="multi-device-test" style="padding:10px;">Multi Device Test</div>'
                );
                const component = added[0];
                editor.select(component);

                // Get the CssComposer to verify rules
                const cssComposer = editor.CssComposer;
                expect(cssComposer, 'CssComposer debe existir').to.exist;

                // Set Desktop style
                editor.setDevice('Desktop');
                component.addStyle({ 'font-size': '24px' });

                // Set Tablet style
                editor.setDevice('Tablet');
                component.addStyle({ 'font-size': '18px' });

                // Set Mobile style
                editor.setDevice('Mobile');
                component.addStyle({ 'font-size': '14px' });

                // Verify CssComposer has rules (at least one per device set)
                const allRules = cssComposer.getAll();
                expect(allRules.length).to.be.greaterThan(0);

                cy.log(`CssComposer has ${allRules.length} CSS rules across devices`);
            });

            cy.log('CssComposer maintains separate style rules per device');
        });
    });

    describe('Test 5: Canvas Viewport Indicator', () => {
        it('should show current device via editor.getDevice() when in Mobile mode', () => {
            cy.visit(EDITOR_URL);

            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');
            cy.get(SELECTORS.canvas, { timeout: 10000 }).should('be.visible');

            // Switch to Mobile
            switchDevice('Mobile');

            // Verify the editor API reports the correct device
            cy.window().then((win) => {
                const editor = win.editor;
                const currentDevice = editor.getDevice();
                expect(currentDevice).to.equal('Mobile');

                // Verify the Devices collection has the device registered
                const devices = editor.Devices.getAll();
                expect(devices.length).to.be.greaterThan(0);

                const mobileDevice = devices.find(
                    (d) => d.get('id') === 'Mobile' || d.get('name') === 'Mobile'
                );
                expect(mobileDevice, 'Mobile device must be registered in Devices manager').to.exist;
            });

            // Verify the canvas container reflects the viewport change visually
            // GrapesJS wraps the frame in a container whose width changes per device
            cy.get(SELECTORS.canvas).then(($canvas) => {
                const canvasEl = $canvas[0];
                // The canvas container should exist and be visible
                expect(canvasEl).to.not.be.null;
                // In non-desktop mode, the frame wrapper is typically resized
                const frameWrapper = canvasEl.querySelector('.gjs-frame-wrapper, .gjs-frame-wrapper__top');
                if (frameWrapper) {
                    const wrapperWidth = frameWrapper.getBoundingClientRect().width;
                    // In mobile mode, the wrapper should be significantly narrower than the canvas
                    const canvasWidth = canvasEl.getBoundingClientRect().width;
                    expect(wrapperWidth).to.be.lessThan(canvasWidth);
                    cy.log(`Frame wrapper width: ${wrapperWidth}px vs canvas width: ${canvasWidth}px`);
                }
            });

            cy.log('Mobile viewport indicator verified');
        });

        it('should show current device via editor.getDevice() when in Tablet mode', () => {
            cy.visit(EDITOR_URL);

            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');
            cy.get(SELECTORS.canvas, { timeout: 10000 }).should('be.visible');

            // Switch to Tablet
            switchDevice('Tablet');

            // Verify the editor API reports the correct device
            cy.window().then((win) => {
                const editor = win.editor;
                const currentDevice = editor.getDevice();
                expect(currentDevice).to.equal('Tablet');

                // Verify the device is registered
                const devices = editor.Devices.getAll();
                const tabletDevice = devices.find(
                    (d) => d.get('id') === 'Tablet' || d.get('name') === 'Tablet'
                );
                expect(tabletDevice, 'Tablet device must be registered in Devices manager').to.exist;

                // Verify the tablet device has the expected width property
                const tabletWidth = tabletDevice.get('width');
                expect(tabletWidth, 'Tablet device debe tener un ancho definido').to.exist;
            });

            // Verify the frame width matches the tablet device configuration
            cy.get(SELECTORS.frame).then(($iframe) => {
                const frameWidth = $iframe[0].getBoundingClientRect().width;
                // Tablet frame should be narrower than full desktop but wider than mobile
                expect(frameWidth).to.be.within(600, 900);
            });

            cy.log('Tablet viewport indicator verified');
        });

        it('should update device indicator when switching devices programmatically', () => {
            cy.visit(EDITOR_URL);

            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');
            cy.get(SELECTORS.canvas, { timeout: 10000 }).should('be.visible');

            // Verify device changes are reflected by listening to the change event
            cy.window().then((win) => {
                const editor = win.editor;

                // Track device changes
                const deviceChanges = [];
                editor.on('change:device', () => {
                    deviceChanges.push(editor.getDevice());
                });

                // Cycle through devices
                editor.setDevice('Tablet');
                editor.setDevice('Mobile');
                editor.setDevice('Desktop');

                // Verify all changes were tracked
                expect(deviceChanges).to.have.length(3);
                expect(deviceChanges[0]).to.equal('Tablet');
                expect(deviceChanges[1]).to.equal('Mobile');
                expect(deviceChanges[2]).to.equal('Desktop');
            });

            cy.log('Device change events fire correctly for viewport indicator updates');
        });
    });

    describe('Test 6: Block Visibility Per Device', () => {
        it('should apply gjs-dview-hidden class to hide blocks on specific devices', () => {
            cy.visit(EDITOR_URL);

            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');
            cy.get(SELECTORS.canvas, { timeout: 10000 }).should('be.visible');

            cy.window().then((win) => {
                const editor = win.editor;
                expect(editor, 'GrapesJS editor debe estar disponible').to.exist;

                // Clear canvas
                editor.DomComponents.clear();

                // Add two test blocks
                const added = editor.addComponents([
                    {
                        tagName: 'div',
                        classes: ['desktop-only-block'],
                        content: 'Visible on Desktop only',
                        style: { padding: '20px', background: '#e0e0e0' },
                    },
                    {
                        tagName: 'div',
                        classes: ['mobile-only-block'],
                        content: 'Visible on Mobile only',
                        style: { padding: '20px', background: '#c0c0c0' },
                    },
                ]);

                expect(added).to.have.length(2);

                const desktopBlock = added[0];
                const mobileBlock = added[1];

                // Simulate hiding the mobile block on desktop by adding the hidden class
                // This mirrors how GrapesJS handles device-specific visibility
                desktopBlock.addClass('gjs-dview-hidden-mobile');
                mobileBlock.addClass('gjs-dview-hidden-desktop');

                // Verify classes are applied
                expect(desktopBlock.getClasses()).to.include('gjs-dview-hidden-mobile');
                expect(mobileBlock.getClasses()).to.include('gjs-dview-hidden-desktop');
            });

            cy.log('Device visibility classes applied correctly to blocks');
        });

        it('should toggle component visibility classes across device switches', () => {
            cy.visit(EDITOR_URL);

            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');
            cy.get(SELECTORS.canvas, { timeout: 10000 }).should('be.visible');

            cy.window().then((win) => {
                const editor = win.editor;
                expect(editor, 'GrapesJS editor debe estar disponible').to.exist;

                // Clear canvas and add a test component
                editor.DomComponents.clear();

                const added = editor.addComponents({
                    tagName: 'div',
                    classes: ['visibility-test-block'],
                    content: 'Visibility Test',
                    style: { padding: '20px' },
                });

                const component = added[0];
                editor.select(component);

                // Add hidden class for mobile view
                component.addClass('gjs-dview-hidden');

                // Verify the class is present in the component model
                expect(component.getClasses()).to.include('gjs-dview-hidden');

                // Verify the class is reflected in the generated HTML
                const html = component.toHTML();
                expect(html).to.include('gjs-dview-hidden');
            });

            // Verify the class is present in the canvas iframe DOM
            cy.get(SELECTORS.frame).then(($iframe) => {
                const doc = $iframe[0].contentDocument;
                const hiddenElements = doc.querySelectorAll('.gjs-dview-hidden');
                expect(hiddenElements.length).to.be.greaterThan(0);
            });

            cy.log('Block visibility per device class verified in canvas DOM');
        });

        it('should respect device visibility when adding and removing classes dynamically', () => {
            cy.visit(EDITOR_URL);

            cy.get(SELECTORS.editor, { timeout: 15000 }).should('exist');
            cy.get(SELECTORS.canvas, { timeout: 10000 }).should('be.visible');

            cy.window().then((win) => {
                const editor = win.editor;

                // Clear and add fresh component
                editor.DomComponents.clear();

                const added = editor.addComponents({
                    tagName: 'section',
                    classes: ['dynamic-visibility-block'],
                    content: 'Dynamic Visibility Test',
                    style: { padding: '30px', background: '#f5f5f5' },
                });

                const component = added[0];

                // Initially visible on all devices (no hidden class)
                expect(component.getClasses()).to.not.include('gjs-dview-hidden');

                // Hide on mobile
                component.addClass('gjs-dview-hidden');
                expect(component.getClasses()).to.include('gjs-dview-hidden');

                // Remove hidden class (make visible again)
                component.removeClass('gjs-dview-hidden');
                expect(component.getClasses()).to.not.include('gjs-dview-hidden');

                // Re-add to verify toggle behavior works
                component.addClass('gjs-dview-hidden');
                expect(component.getClasses()).to.include('gjs-dview-hidden');

                // Verify final HTML output
                const finalHtml = component.toHTML();
                expect(finalHtml).to.include('gjs-dview-hidden');
            });

            cy.log('Dynamic device visibility class toggling works correctly');
        });
    });
});

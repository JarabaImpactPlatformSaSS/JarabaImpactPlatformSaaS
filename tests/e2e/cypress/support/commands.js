// ***********************************************************
// Custom Cypress Commands for Jaraba Impact Platform
// ***********************************************************

/**
 * Login como admin de Drupal
 */
Cypress.Commands.add('loginAsAdmin', () => {
    const username = Cypress.env('adminUsername');
    const password = Cypress.env('adminPassword');

    cy.session([username, password], () => {
        // Ignorar errores de Drupal durante el login
        cy.on('uncaught:exception', (err) => {
            if (err.message.includes('Drupal is not defined')) {
                return false; // No fallar el test
            }
        });

        cy.visit('/user/login');
        cy.get('#edit-name', { timeout: 15000 }).should('be.visible').type(username);
        cy.get('#edit-pass').type(password);
        cy.get('#edit-submit').click();

        // Esperar a que el login sea exitoso
        cy.url({ timeout: 15000 }).should('not.include', '/user/login');
    }, {
        // Validar la sesión
        validate() {
            cy.getCookie('SESS').should('exist');
        }
    });
});

/**
 * Login como usuario de test
 */
Cypress.Commands.add('loginAsUser', (email, password) => {
    cy.session([email, password], () => {
        cy.visit('/user/login');
        cy.get('#edit-name').type(email);
        cy.get('#edit-pass').type(password);
        cy.get('#edit-submit').click();
        cy.url().should('not.include', '/user/login');
    });
});

/**
 * Logout de Drupal
 */
Cypress.Commands.add('logout', () => {
    cy.visit('/user/logout');
    cy.url().should('include', '/user/login');
});

/**
 * Verificar que el header existe con variante específica
 */
Cypress.Commands.add('verifyHeader', (variant = 'classic') => {
    cy.get('.header').should('exist');
    cy.get(`.header--${variant}`).should('exist');
});

/**
 * Verificar que el hero existe con variante específica
 */
Cypress.Commands.add('verifyHero', (variant = 'split') => {
    cy.get('.hero').should('exist');
    cy.get(`.hero--${variant}`).should('exist');
});

/**
 * Verificar cards visibles
 */
Cypress.Commands.add('verifyCards', (count = 1) => {
    cy.get('.card').should('have.length.at.least', count);
});

/**
 * Navegar al Visual Picker
 */
Cypress.Commands.add('goToThemeCustomizer', () => {
    cy.visit('/admin/appearance/theme-customizer');
});

/**
 * Seleccionar Industry Preset
 */
Cypress.Commands.add('selectIndustryPreset', (presetId) => {
    cy.get('.jaraba-preset-picker').find(`input[value="${presetId}"]`).check();
    cy.get('#edit-apply-preset').check();
    cy.get('#edit-submit').click();
    cy.contains('Preset').should('exist');
});

/**
 * Verificar CSS variable tiene valor esperado
 */
Cypress.Commands.add('verifyCssVar', (varName, expectedValue) => {
    cy.document().then((doc) => {
        const value = getComputedStyle(doc.documentElement).getPropertyValue(varName).trim();
        expect(value).to.include(expectedValue);
    });
});

/**
 * Esperar a que Drupal esté listo (Big Pipe, etc.)
 */
Cypress.Commands.add('waitForDrupal', () => {
    // Esperar a que BigPipe termine
    cy.get('body').should('not.have.class', 'big-pipe-loading');
    // Esperar a que AJAX termine
    cy.window().then((win) => {
        if (win.Drupal && win.Drupal.ajax) {
            cy.wrap(win.Drupal.ajax.instances).should('have.length', 0);
        }
    });
});

/**
 * Tomar screenshot con nombre descriptivo
 */
Cypress.Commands.add('takeScreenshot', (name) => {
    cy.screenshot(`jaraba-${name}`, { capture: 'viewport' });
});

/**
 * Verificar accesibilidad básica
 */
Cypress.Commands.add('checkA11y', () => {
    cy.get('html').should('have.attr', 'lang');
    cy.get('main, [role="main"]').should('exist');
    cy.get('h1').should('have.length.at.least', 1);
    cy.get('img').each(($img) => {
        cy.wrap($img).should('have.attr', 'alt');
    });
});

/**
 * Verificar responsive en viewport específico
 */
Cypress.Commands.add('checkResponsive', (viewport) => {
    const viewports = {
        mobile: [375, 667],
        tablet: [768, 1024],
        desktop: [1280, 800],
    };
    const [width, height] = viewports[viewport] || viewport;
    cy.viewport(width, height);
});

/**
 * Esperar petición API y verificar respuesta
 */
Cypress.Commands.add('waitForApi', (alias, statusCode = 200) => {
    cy.wait(`@${alias}`).its('response.statusCode').should('eq', statusCode);
});

/**
 * Crear contenido interactivo
 */
Cypress.Commands.add('createInteractiveContent', (title, type = 'question_set') => {
    cy.visit('/es/admin/content/interactive/add');
    cy.get('#edit-title-0-value').type(title);
    cy.get('#edit-content-type-0-value').select(type);
    cy.get('#edit-submit').click();
    cy.url().should('include', '/interactive/');
});

/**
 * Abrir slide panel por acción
 */
Cypress.Commands.add('openSlidePanel', (action) => {
    cy.get(`[data-action="${action}"]`).first().click();
    cy.get('.slide-panel, [class*="slide-panel"]').should('be.visible');
});

/**
 * Cerrar slide panel
 */
Cypress.Commands.add('closeSlidePanel', () => {
    cy.get('.slide-panel__close, [data-action="close-panel"]').click();
    cy.get('.slide-panel').should('not.be.visible');
});

/**
 * Verificar player interactivo cargado
 */
Cypress.Commands.add('verifyInteractivePlayer', () => {
    cy.get('.interactive-player').should('exist');
    cy.get('.interactive-player__header').should('be.visible');
    cy.get('#btn-next, #btn-previous').should('exist');
});

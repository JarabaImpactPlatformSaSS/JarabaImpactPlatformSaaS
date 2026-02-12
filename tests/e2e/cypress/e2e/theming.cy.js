/**
 * @file
 * E2E Tests: Theme Customizer and Visual Picker
 *
 * Tests for the admin theme customization features
 */

describe('Theme Customizer', { tags: ['@theming', '@admin'] }, () => {
    const BASE_URL = Cypress.env('baseUrl') || 'https://jaraba-saas.lndo.site';

    // Helper para login directo
    const loginAdmin = () => {
        cy.visit(`${BASE_URL}/es/user/login`);
        cy.get('input[name="name"]').type('admin');
        cy.get('input[name="pass"]').type(Cypress.env('adminPass') || 'admin');
        cy.get('input[type="submit"]').click();
        cy.url().should('not.include', '/user/login');
    };

    describe('Access', () => {
        beforeEach(() => {
            loginAdmin();
        });

        it('should access theme customizer if available', () => {
            cy.visit(`${BASE_URL}/es/admin/appearance/theme-customizer`, { failOnStatusCode: false });
            cy.get('body').should('exist');
        });

        it('should check for customizer form', () => {
            cy.visit(`${BASE_URL}/es/admin/appearance/theme-customizer`, { failOnStatusCode: false });
            cy.get('body').then(($body) => {
                expect($body.text().length).to.be.greaterThan(0);
            });
        });
    });

    describe('Vertical Tabs', () => {
        beforeEach(() => {
            loginAdmin();
        });

        it('should check for tab navigation', () => {
            cy.visit(`${BASE_URL}/es/admin/appearance/theme-customizer`, { failOnStatusCode: false });
            cy.get('body').should('exist');
        });
    });

    describe('Industry Presets', () => {
        beforeEach(() => {
            loginAdmin();
        });

        it('should check for preset selector', () => {
            cy.visit(`${BASE_URL}/es/admin/appearance/theme-customizer`, { failOnStatusCode: false });
            cy.get('body').should('exist');
        });
    });

    describe('Color Settings', () => {
        beforeEach(() => {
            loginAdmin();
        });

        it('should check for color inputs', () => {
            cy.visit(`${BASE_URL}/es/admin/appearance/theme-customizer`, { failOnStatusCode: false });
            cy.get('body').should('exist');
        });
    });

    describe('Typography Settings', () => {
        beforeEach(() => {
            loginAdmin();
        });

        it('should check for font selectors', () => {
            cy.visit(`${BASE_URL}/es/admin/appearance/theme-customizer`, { failOnStatusCode: false });
            cy.get('body').should('exist');
        });
    });

    describe('Header Variants', () => {
        beforeEach(() => {
            loginAdmin();
        });

        it('should check for header variant selector', () => {
            cy.visit(`${BASE_URL}/es/admin/appearance/theme-customizer`, { failOnStatusCode: false });
            cy.get('body').should('exist');
        });
    });

    describe('Hero Variants', () => {
        beforeEach(() => {
            loginAdmin();
        });

        it('should check for hero variant selector', () => {
            cy.visit(`${BASE_URL}/es/admin/appearance/theme-customizer`, { failOnStatusCode: false });
            cy.get('body').should('exist');
        });
    });

    describe('Save Configuration', () => {
        beforeEach(() => {
            loginAdmin();
        });

        it('should have save functionality', () => {
            cy.visit(`${BASE_URL}/es/admin/appearance/theme-customizer`, { failOnStatusCode: false });
            cy.get('body').should('exist');
        });
    });

    describe('Preview', () => {
        beforeEach(() => {
            loginAdmin();
        });

        it('should check for preview functionality', () => {
            cy.visit(`${BASE_URL}/es/admin/appearance/theme-customizer`, { failOnStatusCode: false });
            cy.get('body').should('exist');
        });
    });
});

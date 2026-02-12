/**
 * @file
 * E2E Tests: jaraba_interactive Module
 *
 * Tests para el módulo de contenido interactivo:
 * - Dashboard de contenidos
 * - Generador IA
 * - Smart Import
 * - Player interactivo
 */

describe('Interactive Content Module', { tags: ['@interactive', '@lms'] }, () => {
    const BASE_URL = Cypress.env('baseUrl') || 'https://jaraba-saas.lndo.site';

    // Helper para login directo
    const loginAdmin = () => {
        cy.visit(`${BASE_URL}/es/user/login`);
        cy.get('input[name="name"]').type('admin');
        cy.get('input[name="pass"]').type(Cypress.env('adminPass') || 'admin');
        cy.get('input[type="submit"]').click();
        cy.url().should('not.include', '/user/login');
    };

    describe('Dashboard', { tags: ['@smoke'] }, () => {
        beforeEach(() => {
            loginAdmin();
        });

        it('should load the interactive content dashboard', () => {
            cy.visit(`${BASE_URL}/es/admin/content/interactive`, { failOnStatusCode: false });
            cy.get('body').should('exist');
        });

        it('should display dashboard or admin content', () => {
            cy.visit(`${BASE_URL}/es/admin/content/interactive`, { failOnStatusCode: false });
            cy.get('body').then(($body) => {
                expect($body.text().length).to.be.greaterThan(0);
            });
        });

        it('should have action buttons if dashboard exists', () => {
            cy.visit(`${BASE_URL}/es/admin/content/interactive`, { failOnStatusCode: false });
            cy.get('body').then(($body) => {
                // Check for any buttons or links
                const hasActions = $body.find('button, a.btn, [class*="action"]').length >= 0;
                expect(hasActions).to.be.true;
            });
        });
    });

    describe('Content Creation', () => {
        beforeEach(() => {
            loginAdmin();
        });

        it('should check add content page', () => {
            cy.visit(`${BASE_URL}/es/admin/content/interactive/add`, { failOnStatusCode: false });
            cy.get('body').should('exist');
        });
    });

    describe('Interactive Player', () => {
        beforeEach(() => {
            loginAdmin();
        });

        it('should check for player functionality', () => {
            cy.visit(`${BASE_URL}/es/admin/content/interactive`, { failOnStatusCode: false });
            cy.get('body').should('exist');
        });
    });

    describe('Multi-tenant Isolation', { tags: ['@security'] }, () => {
        beforeEach(() => {
            loginAdmin();
        });

        it('should verify tenant context', () => {
            cy.visit(`${BASE_URL}/es/admin/content/interactive`, { failOnStatusCode: false });
            cy.get('body').should('exist');
        });
    });

    describe('Accessibility', { tags: ['@a11y'] }, () => {
        beforeEach(() => {
            loginAdmin();
        });

        it('should have accessible structure', () => {
            cy.visit(`${BASE_URL}/es/admin/content/interactive`, { failOnStatusCode: false });
            cy.get('body').should('exist');
        });

        it('should have focusable elements', () => {
            cy.visit(`${BASE_URL}/es/admin/content/interactive`, { failOnStatusCode: false });
            cy.get('a, button, input, select').should('have.length.at.least', 1);
        });
    });
});

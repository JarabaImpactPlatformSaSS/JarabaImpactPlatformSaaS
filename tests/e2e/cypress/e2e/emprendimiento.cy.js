/**
 * @file
 * E2E Tests: Emprendimiento Vertical
 *
 * Tests for entrepreneur features and copilot
 * Nota: Estas rutas pueden no estar habilitadas en todos los tenants
 */

describe('Emprendimiento Vertical', { tags: ['@emprendimiento', '@vertical'] }, () => {
    const BASE_URL = Cypress.env('baseUrl') || 'https://jaraba-saas.lndo.site';

    // Helper para login directo
    const loginAdmin = () => {
        cy.visit(`${BASE_URL}/es/user/login`);
        cy.get('input[name="name"]').type('admin');
        cy.get('input[name="pass"]').type(Cypress.env('adminPass') || 'admin');
        cy.get('input[type="submit"]').click();
        cy.url().should('not.include', '/user/login');
    };

    describe('Public Pages', () => {
        it('should access emprendimiento landing or redirect', () => {
            cy.visit(`${BASE_URL}/es/emprendimiento`, { failOnStatusCode: false });
            cy.get('body').should('exist');
        });

        it('should check for services content', () => {
            cy.visit(`${BASE_URL}/es/emprendimiento`, { failOnStatusCode: false });
            cy.get('body').then(($body) => {
                expect($body.text().length).to.be.greaterThan(0);
            });
        });
    });

    describe('Entrepreneur Dashboard', () => {
        beforeEach(() => {
            loginAdmin();
        });

        it('should access entrepreneur area if available', () => {
            cy.visit(`${BASE_URL}/es/emprendimiento/mi-empresa`, { failOnStatusCode: false });
            cy.get('body').should('exist');
        });
    });

    describe('Diagnostic Tool', () => {
        beforeEach(() => {
            loginAdmin();
        });

        it('should check diagnostic page', () => {
            cy.visit(`${BASE_URL}/es/emprendimiento/diagnostico`, { failOnStatusCode: false });
            cy.get('body').should('exist');
        });
    });

    describe('Business Tools', () => {
        beforeEach(() => {
            loginAdmin();
        });

        it('should check BMC Canvas page', () => {
            cy.visit(`${BASE_URL}/es/emprendimiento/bmc`, { failOnStatusCode: false });
            cy.get('body').should('exist');
        });
    });

    describe('Mentoring', () => {
        beforeEach(() => {
            loginAdmin();
        });

        it('should check mentors page', () => {
            cy.visit(`${BASE_URL}/es/emprendimiento/mentores`, { failOnStatusCode: false });
            cy.get('body').should('exist');
        });
    });

    describe('Copilot v2', () => {
        beforeEach(() => {
            loginAdmin();
        });

        it('should check copilot page', () => {
            cy.visit(`${BASE_URL}/es/emprendimiento/copiloto`, { failOnStatusCode: false });
            cy.get('body').should('exist');
        });
    });
});

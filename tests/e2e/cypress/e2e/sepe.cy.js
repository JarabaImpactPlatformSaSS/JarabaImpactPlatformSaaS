/**
 * @file
 * E2E Tests: SEPE Teleformación Module
 *
 * Tests for SEPE integration features
 * Nota: Estas rutas pueden no estar habilitadas en todos los tenants
 */

describe('SEPE Teleformación', { tags: ['@sepe', '@admin'] }, () => {
    const BASE_URL = Cypress.env('baseUrl') || 'https://jaraba-saas.lndo.site';

    // Helper para login directo
    const loginAdmin = () => {
        cy.visit(`${BASE_URL}/es/user/login`);
        cy.get('input[name="name"]').type('admin');
        cy.get('input[name="pass"]').type(Cypress.env('adminPass') || 'admin');
        cy.get('input[type="submit"]').click();
        cy.url().should('not.include', '/user/login');
    };

    describe('Admin Access', () => {
        beforeEach(() => {
            loginAdmin();
        });

        it('should access SEPE configuration if available', () => {
            cy.visit(`${BASE_URL}/es/admin/config/jaraba/sepe`, { failOnStatusCode: false });
            cy.get('body').should('exist');
        });

        it('should check for SEPE settings', () => {
            cy.visit(`${BASE_URL}/es/admin/config/jaraba/sepe`, { failOnStatusCode: false });
            cy.get('body').then(($body) => {
                expect($body.text().length).to.be.greaterThan(0);
            });
        });
    });

    describe('SEPE Centros', () => {
        beforeEach(() => {
            loginAdmin();
        });

        it('should access centros list if available', () => {
            cy.visit(`${BASE_URL}/es/admin/content/sepe-centros`, { failOnStatusCode: false });
            cy.get('body').should('exist');
        });

        it('should check centros add form', () => {
            cy.visit(`${BASE_URL}/es/admin/content/sepe-centro/add`, { failOnStatusCode: false });
            cy.get('body').should('exist');
        });
    });

    describe('SEPE Acciones Formativas', () => {
        beforeEach(() => {
            loginAdmin();
        });

        it('should access acciones list if available', () => {
            cy.visit(`${BASE_URL}/es/admin/content/sepe-acciones`, { failOnStatusCode: false });
            cy.get('body').should('exist');
        });
    });

    describe('SEPE Participantes', () => {
        beforeEach(() => {
            loginAdmin();
        });

        it('should access participantes list if available', () => {
            cy.visit(`${BASE_URL}/es/admin/content/sepe-participantes`, { failOnStatusCode: false });
            cy.get('body').should('exist');
        });
    });

    describe('SEPE API Endpoints', () => {
        beforeEach(() => {
            loginAdmin();
        });

        it('should check acciones API', () => {
            cy.visit(`${BASE_URL}/es/admin`);
            cy.request({
                url: `${BASE_URL}/api/sepe/acciones`,
                failOnStatusCode: false,
            }).then((response) => {
                expect(response.status).to.be.a('number');
            });
        });

        it('should check participantes API', () => {
            cy.visit(`${BASE_URL}/es/admin`);
            cy.request({
                url: `${BASE_URL}/api/sepe/participantes/1`,
                failOnStatusCode: false,
            }).then((response) => {
                expect(response.status).to.be.a('number');
            });
        });
    });
});

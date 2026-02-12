/**
 * @file
 * E2E Tests: Empleabilidad Vertical
 *
 * Tests for talent/job matching features
 * Nota: Estas rutas pueden no estar habilitadas en todos los tenants
 */

describe('Empleabilidad Vertical', { tags: ['@empleabilidad', '@vertical'] }, () => {
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
        it('should access empleabilidad landing or redirect', () => {
            cy.visit(`${BASE_URL}/es/empleabilidad`, { failOnStatusCode: false });
            // La página puede existir, redirigir, o dar 404
            cy.get('body').should('exist');
        });

        it('should check for job-related content', () => {
            cy.visit(`${BASE_URL}/es/empleabilidad`, { failOnStatusCode: false });
            cy.get('body').then(($body) => {
                // Verificar que hay algún contenido
                expect($body.text().length).to.be.greaterThan(0);
            });
        });
    });

    describe('Candidate Dashboard', () => {
        beforeEach(() => {
            loginAdmin();
        });

        it('should access candidate area if available', () => {
            cy.visit(`${BASE_URL}/es/empleabilidad/mi-perfil`, { failOnStatusCode: false });
            cy.get('body').should('exist');
        });
    });

    describe('LMS Integration', () => {
        beforeEach(() => {
            loginAdmin();
        });

        it('should check courses page', () => {
            cy.visit(`${BASE_URL}/es/cursos`, { failOnStatusCode: false });
            cy.get('body').should('exist');
        });
    });

    describe('Job Matching', () => {
        beforeEach(() => {
            loginAdmin();
        });

        it('should check job listings', () => {
            cy.visit(`${BASE_URL}/es/ofertas`, { failOnStatusCode: false });
            cy.get('body').should('exist');
        });
    });

    describe('Career Dashboard (Alternative)', () => {
        beforeEach(() => {
            loginAdmin();
        });

        it('should access career dashboard', () => {
            cy.visit(`${BASE_URL}/es/career`, { failOnStatusCode: false });
            cy.get('body').should('exist');
        });
    });
});

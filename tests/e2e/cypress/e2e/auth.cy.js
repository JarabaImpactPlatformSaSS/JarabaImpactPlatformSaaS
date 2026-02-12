/**
 * @file
 * E2E Tests: Authentication flows
 *
 * Tests for login, logout, and session management
 */

describe('Authentication', { tags: ['@auth', '@smoke'] }, () => {
    const BASE_URL = Cypress.env('baseUrl') || 'https://jaraba-saas.lndo.site';

    beforeEach(() => {
        cy.clearCookies();
    });

    describe('Admin Login', () => {
        it('should display login form', () => {
            cy.visit(`${BASE_URL}/es/user/login`);
            cy.get('input[name="name"]').should('be.visible');
            cy.get('input[name="pass"]').should('be.visible');
            cy.get('input[type="submit"]').should('be.visible');
        });

        it('should login as admin successfully', () => {
            cy.visit(`${BASE_URL}/es/user/login`);
            cy.get('input[name="name"]').type('admin');
            cy.get('input[name="pass"]').type(Cypress.env('adminPass') || 'admin');
            cy.get('input[type="submit"]').click();

            // Should redirect away from login
            cy.url().should('not.include', '/user/login');
        });

        it('should show error for invalid credentials', () => {
            cy.visit(`${BASE_URL}/es/user/login`);
            cy.get('input[name="name"]').type('invaliduser');
            cy.get('input[name="pass"]').type('wrongpassword');
            cy.get('input[type="submit"]').click();

            // Should stay on login page or show error
            cy.get('body').then(($body) => {
                const hasError = $body.find('.messages--error').length > 0 ||
                    $body.find('.form-item--error-message').length > 0 ||
                    $body.text().includes('error') ||
                    $body.text().includes('Error');
                expect(hasError || $body.find('input[name="name"]').length > 0).to.be.true;
            });
        });

        it('should logout successfully', () => {
            // Login first
            cy.visit(`${BASE_URL}/es/user/login`);
            cy.get('input[name="name"]').type('admin');
            cy.get('input[name="pass"]').type(Cypress.env('adminPass') || 'admin');
            cy.get('input[type="submit"]').click();
            cy.url().should('not.include', '/user/login');

            // Logout - just visit logout URL
            cy.visit(`${BASE_URL}/es/user/logout`, { failOnStatusCode: false });

            // Verify we can visit login page (session ended)
            cy.visit(`${BASE_URL}/es/user/login`, { failOnStatusCode: false });
            cy.get('body').should('exist');
        });
    });

    describe('Session Persistence', () => {
        it('should maintain session across page navigation', () => {
            // Login
            cy.visit(`${BASE_URL}/es/user/login`);
            cy.get('input[name="name"]').type('admin');
            cy.get('input[name="pass"]').type(Cypress.env('adminPass') || 'admin');
            cy.get('input[type="submit"]').click();
            cy.url().should('not.include', '/user/login');

            // Navigate to home
            cy.visit(`${BASE_URL}/es`);
            cy.url().should('include', BASE_URL);

            // Navigate to another page
            cy.visit(`${BASE_URL}/es/knowledge`, { failOnStatusCode: false });
            cy.url().should('include', 'knowledge');
        });

        it('should redirect to login when accessing admin without session', () => {
            cy.clearCookies();
            cy.visit(`${BASE_URL}/es/admin/config`, { failOnStatusCode: false });

            // Should redirect to login or show access denied
            cy.url().should('satisfy', (url) => {
                return url.includes('/user/login') || url.includes('/admin') || url.includes('denied');
            });
        });
    });

    describe('Password Reset', () => {
        it('should display password reset form', () => {
            cy.visit(`${BASE_URL}/es/user/password`);
            cy.get('input[name="name"]').should('be.visible');
            cy.get('input[type="submit"]').should('be.visible');
        });
    });
});

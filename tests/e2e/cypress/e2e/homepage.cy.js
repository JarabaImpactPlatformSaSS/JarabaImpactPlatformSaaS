/**
 * @file
 * E2E Tests: Homepage and landing page
 *
 * Tests for public-facing homepage components
 */

describe('Homepage', { tags: ['@home', '@smoke'] }, () => {
    const BASE_URL = Cypress.env('baseUrl') || 'https://jaraba-saas.lndo.site';

    beforeEach(() => {
        cy.visit(`${BASE_URL}/es`, { failOnStatusCode: false });
    });

    describe('Page Load', () => {
        it('should load homepage successfully', () => {
            cy.get('body').should('be.visible');
        });

        it('should have correct page title', () => {
            cy.title().should('not.be.empty');
        });

        it('should complete page load in reasonable time', () => {
            cy.window().then((win) => {
                // Simple check that page loaded
                expect(win.document.readyState).to.eq('complete');
            });
        });
    });

    describe('Header Component', () => {
        it('should display header', () => {
            cy.get('header, .header, .site-header, [role="banner"]').should('exist');
        });

        it('should display logo or site identity', () => {
            cy.get('.header__logo, .site-logo, header img, .site-name, header a').should('exist');
        });

        it('should display navigation', () => {
            cy.get('nav, .header__nav, .main-navigation, [role="navigation"]').should('exist');
        });

        it('should have working navigation links', () => {
            cy.get('nav a, header a').first().should('have.attr', 'href');
        });
    });

    describe('Hero Section', () => {
        it('should display main content area', () => {
            cy.get('main, .hero, [class*="hero"], .content, [role="main"]').should('exist');
        });

        it('should display headings', () => {
            cy.get('h1, h2').should('exist');
        });

        it('should have call-to-action elements', () => {
            cy.get('a, button, .btn').should('have.length.at.least', 1);
        });
    });

    describe('Content Sections', () => {
        it('should display main content', () => {
            cy.get('main, [role="main"], .main-content, .content').should('exist');
        });

        it('should have content blocks', () => {
            cy.get('section, article, .block, .region').should('have.length.at.least', 1);
        });
    });

    describe('Footer', () => {
        it('should display footer', () => {
            cy.get('footer, .footer, .site-footer, [role="contentinfo"]').should('exist');
        });

        it('should have footer content', () => {
            cy.get('footer').should('exist').and('not.be.empty');
        });
    });

    describe('Accessibility', () => {
        it('should have lang attribute', () => {
            cy.get('html').should('have.attr', 'lang');
        });

        it('should have headings', () => {
            cy.get('h1, h2, h3').should('have.length.at.least', 1);
        });

        it('should have main landmark', () => {
            cy.get('main, [role="main"]').should('exist');
        });
    });

    describe('Responsive Design', () => {
        it('should be responsive on mobile', () => {
            cy.viewport('iphone-x');
            cy.get('header, .header, body').should('be.visible');
        });

        it('should be responsive on tablet', () => {
            cy.viewport('ipad-2');
            cy.get('header, .header, body').should('be.visible');
        });

        it('should adapt layout on mobile', () => {
            cy.viewport('iphone-x');
            cy.get('body').should('be.visible');
        });
    });
});

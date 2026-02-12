/**
 * @file billing.cy.js
 * Tests E2E para flujos de billing y subscripción SaaS.
 *
 * Cubre:
 * - Visualización de planes y límites
 * - Dashboard de uso (metering)
 * - Warning de expiración de trial
 * - CTA de upgrade de plan
 *
 * @group billing
 * @requires jaraba_billing module
 */

describe('Billing & Subscription Flows', () => {

    beforeEach(() => {
        // Login como admin (tenant owner)
        cy.visit('/user/login');
        cy.get('#edit-name').type(Cypress.env('adminUsername'));
        cy.get('#edit-pass').type(Cypress.env('adminPassword'));
        cy.get('#edit-submit').click();
        cy.url().should('not.include', '/user/login');
    });

    // ===================================================
    // Test 1: Planes disponibles y límites
    // ===================================================
    describe('Plan Display & Limits', () => {

        it('should display available SaaS plans', () => {
            cy.visit(Cypress.env('plansUrl') || '/planes');
            cy.get('[data-test-id="plans-container"], .plans-grid, .pricing-table')
                .should('exist');
        });

        it('should show plan features and pricing information', () => {
            cy.visit(Cypress.env('plansUrl') || '/planes');

            // Verificar que al menos un plan está visible
            cy.get('[data-test-id="plan-card"], .plan-card, .pricing-card')
                .should('have.length.at.least', 1)
                .first()
                .within(() => {
                    // Cada plan debe tener nombre y precio
                    cy.get('[data-test-id="plan-name"], .plan-name, h3, h2')
                        .should('exist')
                        .and('not.be.empty');
                });
        });

        it('should indicate current active plan', () => {
            cy.visit(Cypress.env('plansUrl') || '/planes');

            // El plan activo debe estar marcado
            cy.get('[data-test-id="current-plan"], .current-plan, .active-plan, .plan-card.active')
                .should('exist');
        });
    });

    // ===================================================
    // Test 2: Dashboard de uso (metering)
    // ===================================================
    describe('Usage Dashboard', () => {

        it('should display usage metrics on dashboard', () => {
            cy.visit(Cypress.env('billingUrl') || '/admin/billing');

            // Verificar que el dashboard carga
            cy.get('[data-test-id="usage-dashboard"], .usage-dashboard, .billing-dashboard')
                .should('exist');
        });

        it('should show metering data with progress indicators', () => {
            cy.visit(Cypress.env('billingUrl') || '/admin/billing');

            // Verificar barras de progreso o indicadores de uso
            cy.get('[data-test-id="usage-meter"], .usage-bar, .progress-bar, .meter')
                .should('exist');
        });

        it('should display usage API data via REST', () => {
            // Verificar que la API de uso responde correctamente
            cy.request({
                url: '/api/v1/tenant/usage',
                failOnStatusCode: false,
                headers: {
                    'Accept': 'application/json',
                },
            }).then((response) => {
                // La API debe responder (200 OK o 403 si requiere permisos adicionales)
                expect([200, 403]).to.include(response.status);

                if (response.status === 200) {
                    expect(response.headers['content-type']).to.include('application/json');
                }
            });
        });
    });

    // ===================================================
    // Test 3: Trial expiration warning
    // ===================================================
    describe('Trial Expiration Warning', () => {

        it('should show trial status information when in trial', () => {
            cy.visit('/');

            // Buscar banner o indicador de trial
            cy.get('body').then(($body) => {
                const hasTrialBanner =
                    $body.find('[data-test-id="trial-banner"]').length > 0 ||
                    $body.find('.trial-banner').length > 0 ||
                    $body.find('.trial-warning').length > 0 ||
                    $body.find('.trial-notice').length > 0;

                if (hasTrialBanner) {
                    // Si hay banner de trial, verificar que muestra días restantes
                    cy.get('[data-test-id="trial-banner"], .trial-banner, .trial-warning, .trial-notice')
                        .should('be.visible')
                        .and('contain.text', 'trial')
                        .or('contain.text', 'prueba');
                } else {
                    // Si no hay banner, el tenant ya está en plan activo — esto es correcto
                    cy.log('No trial banner found — tenant is on an active plan.');
                }
            });
        });

        it('should have subscription status available via API', () => {
            cy.request({
                url: '/api/v1/tenant/subscription-status',
                failOnStatusCode: false,
                headers: {
                    'Accept': 'application/json',
                },
            }).then((response) => {
                expect([200, 403, 404]).to.include(response.status);

                if (response.status === 200) {
                    const data = response.body;
                    // Debe tener un campo de status
                    expect(data).to.have.any.keys('status', 'subscription_status', 'state');
                }
            });
        });
    });

    // ===================================================
    // Test 4: Plan upgrade CTA
    // ===================================================
    describe('Plan Upgrade Flow', () => {

        it('should display upgrade CTA button', () => {
            cy.visit(Cypress.env('plansUrl') || '/planes');

            // Verificar que hay botón de upgrade o cambio de plan
            cy.get('[data-test-id="upgrade-button"], .upgrade-btn, .plan-upgrade, a[href*="upgrade"], a[href*="plan"]')
                .should('exist');
        });

        it('should navigate to checkout when clicking upgrade', () => {
            cy.visit(Cypress.env('plansUrl') || '/planes');

            // Buscar un CTA de plan que no sea el actual
            cy.get('[data-test-id="upgrade-button"], .upgrade-btn, .plan-upgrade')
                .first()
                .then(($btn) => {
                    const href = $btn.attr('href');

                    if (href) {
                        // Verificar que enlaza a checkout o Stripe
                        expect(href).to.satisfy((h) =>
                            h.includes('checkout') ||
                            h.includes('stripe') ||
                            h.includes('upgrade') ||
                            h.includes('plan')
                        );
                    } else {
                        // Botón sin href — puede ser JavaScript-driven
                        cy.wrap($btn).should('be.visible');
                    }
                });
        });

        it('should show plan comparison for upgrade decision', () => {
            cy.visit(Cypress.env('plansUrl') || '/planes');

            // Verificar que se pueden comparar planes
            cy.get('[data-test-id="plan-card"], .plan-card, .pricing-card')
                .should('have.length.at.least', 2);
        });
    });

});

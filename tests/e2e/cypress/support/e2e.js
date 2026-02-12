// ***********************************************************
// Support file for Cypress E2E tests
// Loaded automatically before test files
// ***********************************************************

import './commands';

// Suppress uncaught exceptions from Drupal/third-party scripts
Cypress.on('uncaught:exception', (err, runnable) => {
    // Ignore common Drupal/JS errors that don't affect tests
    if (
        err.message.includes('ResizeObserver loop') ||
        err.message.includes('Drupal') ||
        err.message.includes('jQuery') ||
        err.message.includes('Script error')
    ) {
        return false;
    }
    return true;
});

// Log test info to console
beforeEach(() => {
    cy.log(`Running: ${Cypress.currentTest.title}`);
});

// Clear cookies/storage between tests if needed
afterEach(() => {
    // Preserve Drupal session cookies
    cy.getCookies().then((cookies) => {
        const sessionCookies = cookies.filter(c =>
            c.name.startsWith('SESS') || c.name.startsWith('SSESS')
        );
        sessionCookies.forEach(c => {
            cy.setCookie(c.name, c.value);
        });
    });
});

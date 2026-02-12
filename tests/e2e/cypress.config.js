module.exports = {
    e2e: {
        baseUrl: 'https://jaraba-saas.lndo.site',
        viewportWidth: 1280,
        viewportHeight: 800,
        defaultCommandTimeout: 10000,
        pageLoadTimeout: 30000,
        video: true,
        screenshotOnRunFailure: true,
        retries: {
            runMode: 2,
            openMode: 0,
        },
        specPattern: 'cypress/e2e/**/*.cy.{js,ts}',
        supportFile: 'cypress/support/e2e.js',
        fixturesFolder: 'cypress/fixtures',
        screenshotsFolder: 'cypress/screenshots',
        videosFolder: 'cypress/videos',

        env: {
            adminUsername: 'admin',
            adminPassword: 'admin',
            testUserEmail: 'test@jaraba.com',
            testUserPassword: 'test123',
            empleabilidadUrl: '/empleabilidad',
            emprendimientoUrl: '/emprendimiento',
            agroconectaUrl: '/agroconecta',
            apiTimeout: 15000,
            billingUrl: '/admin/billing',
            plansUrl: '/planes',
        },

        setupNodeEvents(on, config) {
            on('task', {
                log(message) {
                    console.log(message);
                    return null;
                },
            });
            return config;
        },
    },
};

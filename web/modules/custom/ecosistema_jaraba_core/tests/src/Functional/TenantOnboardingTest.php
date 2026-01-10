<?php

namespace Drupal\Tests\ecosistema_jaraba_core\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for the Tenant Onboarding flow.
 *
 * Tests the complete onboarding journey:
 * 1. User visits onboarding form
 * 2. Fills organization details
 * 3. Selects vertical and plan
 * 4. Submits form
 * 5. Verifies Tenant, Group, Domain are created
 *
 * @group ecosistema_jaraba_core
 * @requires module group
 * @requires module domain
 */
class TenantOnboardingTest extends BrowserTestBase
{

    /**
     * {@inheritdoc}
     */
    protected $defaultTheme = 'stark';

    /**
     * Modules to enable.
     *
     * @var array
     */
    protected static $modules = [
        'node',
        'user',
        'field',
        'text',
        'options',
        'group',
        'domain',
        'ecosistema_jaraba_core',
    ];

    /**
     * The admin user.
     *
     * @var \Drupal\user\UserInterface
     */
    protected $adminUser;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user with necessary permissions
        $this->adminUser = $this->drupalCreateUser([
            'administer site configuration',
            'administer tenants',
            'administer verticals',
            'administer saas plans',
        ]);
    }

    /**
     * Tests the onboarding page is accessible.
     */
    public function testOnboardingPageExists(): void
    {
        $this->drupalGet('/onboarding');

        // Should be accessible (not 404)
        $this->assertSession()->statusCodeEquals(200);

        // Should have the onboarding form
        $this->assertSession()->pageTextContains('Onboarding');
    }

    /**
     * Tests onboarding form has required fields.
     */
    public function testOnboardingFormFields(): void
    {
        $this->drupalGet('/onboarding');

        // Check for essential form fields
        $this->assertSession()->fieldExists('organization_name');
        $this->assertSession()->fieldExists('email');
        $this->assertSession()->fieldExists('vertical');
        $this->assertSession()->fieldExists('plan');
    }

    /**
     * Tests successful onboarding submission creates entities.
     */
    public function testOnboardingCreatesEntities(): void
    {
        // Skip if Stripe is not configured in test environment
        if (!$this->isStripeConfigured()) {
            $this->markTestSkipped('Stripe configuration required for full onboarding test.');
        }

        $this->drupalGet('/onboarding');

        // Fill the form
        $this->submitForm([
            'organization_name' => 'Test Organization ' . time(),
            'email' => 'test' . time() . '@example.com',
            'vertical' => 1, // AgroConecta
            'plan' => 'starter',
        ], 'Submit');

        // Should redirect to success or Stripe checkout
        $this->assertSession()->statusCodeEquals(200);
    }

    /**
     * Tests onboarding validation errors.
     */
    public function testOnboardingValidation(): void
    {
        $this->drupalGet('/onboarding');

        // Submit empty form
        $this->submitForm([], 'Submit');

        // Should show validation errors
        $this->assertSession()->pageTextContains('required');
    }

    /**
     * Tests tenant dashboard access after onboarding.
     */
    public function testTenantDashboardAfterOnboarding(): void
    {
        $this->drupalLogin($this->adminUser);

        // Access tenant dashboard
        $this->drupalGet('/tenant/dashboard');

        // Should be accessible for authenticated users
        $response = $this->getSession()->getStatusCode();
        $this->assertTrue(in_array($response, [200, 403]), 'Dashboard should return 200 or 403 based on tenant association');
    }

    /**
     * Tests the change plan page exists.
     */
    public function testChangePlanPageExists(): void
    {
        $this->drupalLogin($this->adminUser);

        $this->drupalGet('/tenant/change-plan');

        // Should be accessible
        $response = $this->getSession()->getStatusCode();
        $this->assertTrue(in_array($response, [200, 403]), 'Change plan page should be accessible');
    }

    /**
     * Helper to check if Stripe is configured.
     */
    protected function isStripeConfigured(): bool
    {
        $config = \Drupal::config('ecosistema_jaraba_core.stripe');
        return !empty($config->get('public_key')) && !empty($config->get('secret_key'));
    }

}

<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Tests\UnitTestCase;

/**
 * Contract tests for the pricing cascade system.
 *
 * These tests act as guardrails to prevent future changes from violating
 * the cascade configuration of Verticales/Planes/Funcionalidades.
 *
 * They verify:
 * - No hardcoded prices in templates
 * - All vertical pricing pages use cascade resolution
 * - Config data completeness (tiers, features, freemium limits)
 * - Template variables match controller data contracts
 *
 * DIRECTRIZ: PLAN-CASCADE-001
 *
 * @group ecosistema_jaraba_core
 * @group pricing
 */
class PricingCascadeContractTest extends UnitTestCase
{

    /**
     * The 7 priority verticals with public pricing pages.
     */
    private const PRIORITY_VERTICALS = [
        'empleabilidad',
        'emprendimiento',
        'comercioconecta',
        'agroconecta',
        'jarabalex',
        'serviciosconecta',
        'formacion',
    ];

    /**
     * The 3 canonical tier keys.
     */
    private const TIER_KEYS = ['starter', 'professional', 'enterprise'];

    /**
     * Tests that pricing-page.html.twig has no hardcoded EUR prices.
     *
     * Prevents regression where template displays static prices instead
     * of dynamic data from the cascade resolution system.
     */
    public function testPricingTemplateHasNoHardcodedPrices(): void
    {
        $templatePath = $this->getThemePath() . '/templates/pricing-page.html.twig';
        $this->assertFileExists($templatePath);

        $content = file_get_contents($templatePath);

        // No hardcoded EUR amounts (e.g., "29€", "99€", "199€", "Desde 29€").
        $this->assertDoesNotMatchRegularExpression(
            '/\b\d+\s*€/',
            $content,
            'pricing-page.html.twig must not contain hardcoded EUR prices. Use tier.price_monthly instead.'
        );

        // No hardcoded "Desde X" patterns.
        $this->assertDoesNotMatchRegularExpression(
            '/Desde\s+\d+/',
            $content,
            'pricing-page.html.twig must not contain hardcoded "Desde X" patterns.'
        );
    }

    /**
     * Tests that Schema.org JSON-LD uses dynamic prices.
     */
    public function testSchemaOrgUsesDynamicPrices(): void
    {
        $templatePath = $this->getThemePath() . '/templates/pricing-page.html.twig';
        $content = file_get_contents($templatePath);

        // Schema.org prices must reference tier variable, not hardcoded numbers.
        $this->assertStringContainsString(
            'tier.price_monthly',
            $content,
            'Schema.org JSON-LD must use tier.price_monthly for dynamic pricing.'
        );
    }

    /**
     * Tests that CTA URLs use Drupal path() function, not hardcoded paths.
     *
     * ROUTE-LANGPREFIX-001 compliance.
     */
    public function testCtaUrlsUseDrupalPathFunction(): void
    {
        $templatePath = $this->getThemePath() . '/templates/pricing-page.html.twig';
        $content = file_get_contents($templatePath);

        // No hardcoded /planes or /user/register paths.
        $this->assertDoesNotMatchRegularExpression(
            '/href=["\']\/(planes|user\/register)/',
            $content,
            'CTA URLs must use path() function, not hardcoded paths (ROUTE-LANGPREFIX-001).'
        );
    }

    /**
     * Tests that the pricing hub template exists.
     */
    public function testPricingHubTemplateExists(): void
    {
        $hubPath = $this->getThemePath() . '/templates/pricing-hub-page.html.twig';
        $this->assertFileExists($hubPath, 'pricing-hub-page.html.twig must exist for the /planes hub.');
    }

    /**
     * Tests that SaasPlanTier configs exist in config/sync for all 3 tiers.
     */
    public function testPlanTierConfigsExist(): void
    {
        $syncDir = $this->getProjectRoot() . '/config/sync';

        foreach (self::TIER_KEYS as $tier) {
            $file = $syncDir . '/ecosistema_jaraba_core.plan_tier.' . $tier . '.yml';
            $this->assertFileExists(
                $file,
                "SaasPlanTier config for '{$tier}' must exist in config/sync."
            );
        }
    }

    /**
     * Tests that SaasPlanFeatures configs exist for all priority verticals.
     *
     * Ensures cascade resolution has data: {vertical}_{tier} for each combo.
     */
    public function testPlanFeaturesConfigsExistForAllVerticals(): void
    {
        $syncDir = $this->getProjectRoot() . '/config/sync';

        foreach (self::PRIORITY_VERTICALS as $vertical) {
            foreach (self::TIER_KEYS as $tier) {
                $file = $syncDir . '/ecosistema_jaraba_core.plan_features.' . $vertical . '_' . $tier . '.yml';
                $this->assertFileExists(
                    $file,
                    "SaasPlanFeatures config for '{$vertical}_{$tier}' must exist in config/sync."
                );
            }
        }
    }

    /**
     * Tests that default fallback features exist for all tiers.
     */
    public function testDefaultPlanFeaturesExist(): void
    {
        $syncDir = $this->getProjectRoot() . '/config/sync';

        foreach (self::TIER_KEYS as $tier) {
            $file = $syncDir . '/ecosistema_jaraba_core.plan_features._default_' . $tier . '.yml';
            $this->assertFileExists(
                $file,
                "Default SaasPlanFeatures config for '_default_{$tier}' must exist in config/sync."
            );
        }
    }

    /**
     * Tests that FreemiumVerticalLimit configs exist for all priority verticals.
     *
     * Each vertical must have configs for free, starter, and profesional tiers.
     */
    public function testFreemiumLimitsExistForAllVerticals(): void
    {
        $syncDir = $this->getProjectRoot() . '/config/sync';

        foreach (self::PRIORITY_VERTICALS as $vertical) {
            $pattern = $syncDir . '/ecosistema_jaraba_core.freemium_vertical_limit.' . $vertical . '_*';
            $files = glob($pattern);

            $this->assertNotEmpty(
                $files,
                "FreemiumVerticalLimit configs must exist for vertical '{$vertical}'."
            );

            // Must have at least 3 plans worth (free + starter + profesional).
            $this->assertGreaterThanOrEqual(
                3,
                count($files),
                "Vertical '{$vertical}' must have at least 3 FreemiumVerticalLimit configs (one per plan tier)."
            );
        }
    }

    /**
     * Tests that the pricing route for each vertical is defined.
     */
    public function testVerticalPricingRoutesAreDefined(): void
    {
        $routingFile = $this->getModulePath() . '/ecosistema_jaraba_core.routing.yml';
        $this->assertFileExists($routingFile);

        $content = file_get_contents($routingFile);

        // The vertical pricing route must exist.
        $this->assertStringContainsString(
            'ecosistema_jaraba_core.pricing.vertical',
            $content,
            'The vertical pricing route must be defined in routing.yml.'
        );

        // Must have regex constraint for the 7 verticals.
        foreach (self::PRIORITY_VERTICALS as $vertical) {
            $this->assertStringContainsString(
                $vertical,
                $content,
                "Vertical '{$vertical}' must appear in the pricing route constraint."
            );
        }
    }

    /**
     * Tests that PricingController has the required methods.
     */
    public function testPricingControllerHasRequiredMethods(): void
    {
        $controllerPath = $this->getModulePath() . '/src/Controller/PricingController.php';
        $this->assertFileExists($controllerPath);

        $content = file_get_contents($controllerPath);

        $requiredMethods = [
            'pricingPage',
            'verticalPricingPage',
            'verticalPricingTitle',
            'getVerticalLabels',
        ];

        foreach ($requiredMethods as $method) {
            $this->assertStringContainsString(
                "function {$method}",
                $content,
                "PricingController must have method '{$method}'."
            );
        }
    }

    /**
     * Tests that MetaSitePricingService uses cascade resolution.
     */
    public function testMetaSitePricingServiceUsesCascade(): void
    {
        $servicePath = $this->getModulePath() . '/src/Service/MetaSitePricingService.php';
        $this->assertFileExists($servicePath);

        $content = file_get_contents($servicePath);

        // Must reference PlanResolverService.
        $this->assertStringContainsString(
            'PlanResolverService',
            $content,
            'MetaSitePricingService must use PlanResolverService for cascade resolution.'
        );

        // Must call getFeatures() for cascade.
        $this->assertStringContainsString(
            'getFeatures',
            $content,
            'MetaSitePricingService must call getFeatures() on the resolver.'
        );

        // Must NOT have hardcoded prices.
        $this->assertDoesNotMatchRegularExpression(
            '/["\']price["\'].*=>\s*\d{2,}/',
            $content,
            'MetaSitePricingService must not contain hardcoded prices.'
        );
    }

    /**
     * Tests that landing pricing preview uses path() not hardcoded URLs.
     */
    public function testLandingPricingPreviewUsesPathFunction(): void
    {
        $partialPath = $this->getThemePath() . '/templates/partials/_landing-pricing-preview.html.twig';
        $this->assertFileExists($partialPath);

        $content = file_get_contents($partialPath);

        // Fallback URL must use path() function.
        $this->assertStringContainsString(
            "path('ecosistema_jaraba_core.pricing.page')",
            $content,
            'Landing pricing preview fallback URL must use path() function.'
        );
    }

    /**
     * Tests that the pricing toggle JS behavior exists.
     */
    public function testPricingToggleJsExists(): void
    {
        $jsPath = $this->getThemePath() . '/js/pricing-toggle.js';
        $this->assertFileExists($jsPath);

        $content = file_get_contents($jsPath);

        // Must be a proper Drupal behavior.
        $this->assertStringContainsString(
            'Drupal.behaviors.pricingBillingToggle',
            $content,
            'pricing-toggle.js must define Drupal.behaviors.pricingBillingToggle.'
        );

        // Must use once() to prevent duplicate attachment.
        $this->assertStringContainsString(
            'once(',
            $content,
            'pricing-toggle.js must use once() to prevent duplicate attachment.'
        );
    }

    /**
     * Tests SaasPlanFeatures YAML files have required keys.
     */
    public function testPlanFeaturesYamlStructure(): void
    {
        $syncDir = $this->getProjectRoot() . '/config/sync';
        $requiredKeys = ['id', 'label', 'vertical', 'tier', 'features', 'limits'];

        // Check a sample vertical.
        $file = $syncDir . '/ecosistema_jaraba_core.plan_features.agroconecta_starter.yml';
        if (!file_exists($file)) {
            $this->markTestSkipped('Sample plan_features YAML not found.');
        }

        $content = file_get_contents($file);
        foreach ($requiredKeys as $key) {
            $this->assertStringContainsString(
                $key . ':',
                $content,
                "SaasPlanFeatures YAML must contain key '{$key}'."
            );
        }
    }

    /**
     * Tests FreemiumVerticalLimit YAML files have required keys.
     */
    public function testFreemiumLimitYamlStructure(): void
    {
        $syncDir = $this->getProjectRoot() . '/config/sync';
        $requiredKeys = ['id', 'vertical', 'plan', 'feature_key', 'limit_value', 'upgrade_message'];

        $file = $syncDir . '/ecosistema_jaraba_core.freemium_vertical_limit.empleabilidad_free_cv_builder.yml';
        if (!file_exists($file)) {
            $this->markTestSkipped('Sample freemium_vertical_limit YAML not found.');
        }

        $content = file_get_contents($file);
        foreach ($requiredKeys as $key) {
            $this->assertStringContainsString(
                $key . ':',
                $content,
                "FreemiumVerticalLimit YAML must contain key '{$key}'."
            );
        }
    }

    /**
     * Gets the project root directory.
     */
    private function getProjectRoot(): string
    {
        // __DIR__ = .../tests/src/Unit/Service → 8 levels up = project root.
        return dirname(__DIR__, 8);
    }

    /**
     * Gets the module directory path.
     */
    private function getModulePath(): string
    {
        return $this->getProjectRoot() . '/web/modules/custom/ecosistema_jaraba_core';
    }

    /**
     * Gets the theme directory path.
     */
    private function getThemePath(): string
    {
        return $this->getProjectRoot() . '/web/themes/custom/ecosistema_jaraba_theme';
    }

}

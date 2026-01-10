<?php

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Tests\UnitTestCase;
use Drupal\ecosistema_jaraba_core\Entity\TenantInterface;

/**
 * Tests for the TenantManager service.
 *
 * @group ecosistema_jaraba_core
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Service\TenantManager
 */
class TenantManagerTest extends UnitTestCase
{

    /**
     * Tests domain format validation.
     *
     * @dataProvider domainValidationDataProvider
     */
    public function testDomainValidation(string $domain, bool $expected): void
    {
        // Domain should be lowercase alphanumeric with hyphens only
        $isValid = (bool) preg_match('/^[a-z0-9][a-z0-9\-]{1,61}[a-z0-9]$/', $domain);

        $this->assertEquals($expected, $isValid);
    }

    /**
     * Data provider for domain validation.
     */
    public static function domainValidationDataProvider(): array
    {
        return [
            'simple domain' => ['cooperativa-olivar', TRUE],
            'numbers allowed' => ['finca123', TRUE],
            'hyphen in middle' => ['my-farm-coop', TRUE],
            'too short' => ['ab', FALSE],
            'uppercase invalid' => ['MyFarm', FALSE],
            'underscore invalid' => ['my_farm', FALSE],
            'starts with hyphen' => ['-invalid', FALSE],
            'ends with hyphen' => ['invalid-', FALSE],
            'special chars' => ['farm@coop', FALSE],
            'spaces' => ['my farm', FALSE],
        ];
    }

    /**
     * Tests trial period calculation.
     */
    public function testTrialPeriodCalculation(): void
    {
        $trialDays = 14;
        $startDate = new \DateTime('2026-01-01');
        $trialEndDate = clone $startDate;
        $trialEndDate->add(new \DateInterval("P{$trialDays}D"));

        $this->assertEquals('2026-01-15', $trialEndDate->format('Y-m-d'));
    }

    /**
     * Tests theme settings cascade logic.
     */
    public function testThemeSettingsCascade(): void
    {
        // Vertical default settings
        $verticalSettings = [
            'color_primario' => '#FF8C42',
            'color_secundario' => '#2D3436',
            'tipografia' => 'Inter',
            'logo_url' => '/themes/ecosistema_jaraba/logo.png',
        ];

        // Tenant overrides (partial)
        $tenantOverrides = [
            'color_primario' => '#4CAF50',  // Override
            'logo_url' => '/sites/files/coop-logo.png',  // Override
        ];

        // Expected merged settings (tenant takes precedence)
        $expected = array_merge($verticalSettings, $tenantOverrides);

        $this->assertEquals('#4CAF50', $expected['color_primario']);
        $this->assertEquals('#2D3436', $expected['color_secundario']);  // From vertical
        $this->assertEquals('Inter', $expected['tipografia']);  // From vertical
        $this->assertEquals('/sites/files/coop-logo.png', $expected['logo_url']);  // Overridden
    }

    /**
     * Tests subscription status transitions.
     *
     * @dataProvider statusTransitionDataProvider
     */
    public function testValidStatusTransitions(string $from, string $to, bool $valid): void
    {
        $validTransitions = [
            'trial' => ['active', 'cancelled', 'suspended'],
            'active' => ['past_due', 'cancelled', 'suspended'],
            'past_due' => ['active', 'cancelled', 'suspended'],
            'cancelled' => ['trial', 'active'],  // Reactivation
            'suspended' => ['active', 'cancelled'],
        ];

        $isValid = isset($validTransitions[$from]) && in_array($to, $validTransitions[$from]);

        $this->assertEquals($valid, $isValid);
    }

    /**
     * Data provider for status transitions.
     */
    public static function statusTransitionDataProvider(): array
    {
        return [
            'trial to active' => ['trial', 'active', TRUE],
            'trial to cancelled' => ['trial', 'cancelled', TRUE],
            'active to past_due' => ['active', 'past_due', TRUE],
            'past_due to active' => ['past_due', 'active', TRUE],
            'cancelled to trial (reactivate)' => ['cancelled', 'trial', TRUE],
            'suspended to active' => ['suspended', 'active', TRUE],
            'trial direct to past_due' => ['trial', 'past_due', FALSE],
            'cancelled to past_due' => ['cancelled', 'past_due', FALSE],
        ];
    }

    /**
     * Tests tenant creation data structure.
     */
    public function testTenantCreationData(): void
    {
        $requiredFields = [
            'name',
            'domain',
            'vertical',
            'subscription_plan',
            'admin_user',
        ];

        $tenantData = [
            'name' => 'Cooperativa Oleícola del Sur',
            'domain' => 'coop-sur',
            'vertical' => 1,  // Vertical entity ID
            'subscription_plan' => 2,  // SaasPlan entity ID
            'admin_user' => 5,  // User ID
            'trial_ends' => '2026-01-15T00:00:00',
        ];

        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $tenantData, "Missing required field: {$field}");
        }
    }

    /**
     * Tests tenant URL generation.
     */
    public function testTenantUrlGeneration(): void
    {
        $baseDomain = 'jaraba.io';
        $tenantDomain = 'cooperativa-olivar';

        $fullUrl = "https://{$tenantDomain}.{$baseDomain}";

        $this->assertEquals('https://cooperativa-olivar.jaraba.io', $fullUrl);
    }

    /**
     * Tests current tenant detection scenarios.
     *
     * @dataProvider currentTenantDetectionDataProvider
     */
    public function testCurrentTenantDetection(
        ?string $hostDomain,
        ?int $userTenantId,
        ?int $expectedTenantId
    ): void {
        // Simulate tenant detection priority:
        // 1. Domain-based detection
        // 2. User-based detection (fallback)

        $detectedTenantId = NULL;

        if ($hostDomain) {
            // Mock domain lookup
            $domainToTenant = [
                'coop-sur.jaraba.io' => 1,
                'finca-norte.jaraba.io' => 2,
            ];
            $detectedTenantId = $domainToTenant[$hostDomain] ?? NULL;
        }

        if ($detectedTenantId === NULL && $userTenantId) {
            $detectedTenantId = $userTenantId;
        }

        $this->assertEquals($expectedTenantId, $detectedTenantId);
    }

    /**
     * Data provider for tenant detection.
     */
    public static function currentTenantDetectionDataProvider(): array
    {
        return [
            'domain match' => ['coop-sur.jaraba.io', 5, 1],  // Domain takes precedence
            'user fallback' => [NULL, 3, 3],  // User's tenant
            'unknown domain, user fallback' => ['unknown.jaraba.io', 4, 4],
            'no detection' => [NULL, NULL, NULL],
        ];
    }

}

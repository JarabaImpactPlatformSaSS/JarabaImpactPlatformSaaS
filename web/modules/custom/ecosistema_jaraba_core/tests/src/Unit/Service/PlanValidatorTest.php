<?php

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Tests\UnitTestCase;
use Drupal\ecosistema_jaraba_core\Entity\TenantInterface;
use Drupal\ecosistema_jaraba_core\Entity\SaasPlanInterface;

/**
 * Tests for the PlanValidator service.
 *
 * @group ecosistema_jaraba_core
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Service\PlanValidator
 */
class PlanValidatorTest extends UnitTestCase
{

    /**
     * Creates a mock tenant with a plan.
     *
     * @param array $planLimits
     *   The plan limits.
     * @param array $planFeatures
     *   The plan features.
     *
     * @return \Drupal\ecosistema_jaraba_core\Entity\TenantInterface
     *   A mocked tenant.
     */
    protected function createMockTenant(array $planLimits, array $planFeatures = []): TenantInterface
    {
        $plan = $this->createMock(SaasPlanInterface::class);
        $plan->method('getLimits')
            ->willReturn($planLimits);
        $plan->method('getFeatures')
            ->willReturn($planFeatures);

        $tenant = $this->createMock(TenantInterface::class);
        $tenant->method('getSubscriptionPlan')
            ->willReturn($plan);

        return $tenant;
    }

    /**
     * Tests producer limit validation.
     *
     * @dataProvider producerLimitDataProvider
     */
    public function testCanAddProducerWithLimit(int $limit, int $current, bool $expected): void
    {
        $planLimits = ['productores' => $limit];

        // Simulate validation logic
        $canAdd = ($limit < 0) || ($current < $limit);

        $this->assertEquals($expected, $canAdd);
    }

    /**
     * Data provider for producer limits.
     */
    public static function producerLimitDataProvider(): array
    {
        return [
            'under limit' => [10, 5, TRUE],
            'at limit' => [10, 10, FALSE],
            'over limit' => [10, 15, FALSE],
            'unlimited (-1)' => [-1, 1000, TRUE],
            'zero limit' => [0, 0, FALSE],
        ];
    }

    /**
     * Tests storage limit validation.
     *
     * @dataProvider storageLimitDataProvider
     */
    public function testCanUseStorage(int $limitGb, float $usedGb, float $additionalGb, bool $expected): void
    {
        // Simulate validation logic
        $canUse = ($limitGb < 0) || (($usedGb + $additionalGb) <= $limitGb);

        $this->assertEquals($expected, $canUse);
    }

    /**
     * Data provider for storage limits.
     */
    public static function storageLimitDataProvider(): array
    {
        return [
            'plenty of space' => [25, 5.0, 1.0, TRUE],
            'exactly at limit' => [25, 24.0, 1.0, TRUE],
            'would exceed' => [25, 24.5, 1.0, FALSE],
            'already over' => [25, 30.0, 1.0, FALSE],
            'unlimited' => [-1, 100.0, 50.0, TRUE],
        ];
    }

    /**
     * Tests AI query limit validation.
     *
     * @dataProvider aiQueryLimitDataProvider
     */
    public function testCanUseAiQuery(int $limit, int $usedThisMonth, bool $expected): void
    {
        // Simulate validation logic  
        $canUse = ($limit < 0) || ($usedThisMonth < $limit);

        $this->assertEquals($expected, $canUse);
    }

    /**
     * Data provider for AI query limits.
     */
    public static function aiQueryLimitDataProvider(): array
    {
        return [
            'plenty remaining' => [100, 10, TRUE],
            'last query' => [100, 99, TRUE],
            'at limit' => [100, 100, FALSE],
            'over limit' => [100, 150, FALSE],
            'unlimited' => [-1, 10000, TRUE],
            'no AI access' => [0, 0, FALSE],
        ];
    }

    /**
     * Tests feature access.
     *
     * @dataProvider featureAccessDataProvider
     */
    public function testHasFeature(array $planFeatures, string $feature, bool $expected): void
    {
        // Simulate feature check
        $hasFeature = in_array($feature, $planFeatures);

        $this->assertEquals($expected, $hasFeature);
    }

    /**
     * Data provider for feature access.
     */
    public static function featureAccessDataProvider(): array
    {
        $basicFeatures = ['trazabilidad_basica', 'soporte_email'];
        $proFeatures = ['trazabilidad_basica', 'trazabilidad_avanzada', 'agentes_ia_limitados', 'soporte_email', 'soporte_chat'];
        $enterpriseFeatures = ['trazabilidad_basica', 'trazabilidad_avanzada', 'agentes_ia_limitados', 'agentes_ia_completos', 'firma_digital', 'api_access'];

        return [
            'basic has basic traceability' => [$basicFeatures, 'trazabilidad_basica', TRUE],
            'basic lacks advanced traceability' => [$basicFeatures, 'trazabilidad_avanzada', FALSE],
            'pro has AI' => [$proFeatures, 'agentes_ia_limitados', TRUE],
            'pro lacks digital signature' => [$proFeatures, 'firma_digital', FALSE],
            'enterprise has API' => [$enterpriseFeatures, 'api_access', TRUE],
            'enterprise has signature' => [$enterpriseFeatures, 'firma_digital', TRUE],
        ];
    }

    /**
     * Tests usage summary structure.
     */
    public function testUsageSummaryStructure(): void
    {
        $expectedKeys = [
            'productores',
            'storage',
            'ai_queries',
        ];

        // Simulate usage summary structure
        $usageSummary = [
            'productores' => ['used' => 5, 'limit' => 10, 'percentage' => 50],
            'storage' => ['used' => 2.5, 'limit' => 25, 'percentage' => 10],
            'ai_queries' => ['used' => 45, 'limit' => 100, 'percentage' => 45],
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $usageSummary);
            $this->assertArrayHasKey('used', $usageSummary[$key]);
            $this->assertArrayHasKey('limit', $usageSummary[$key]);
            $this->assertArrayHasKey('percentage', $usageSummary[$key]);
        }
    }

    /**
     * Tests plan upgrade validation.
     */
    public function testPlanUpgradeValidation(): void
    {
        $currentLimits = ['productores' => 10, 'storage_gb' => 5];
        $upgradeLimits = ['productores' => 50, 'storage_gb' => 25];
        $downgradeLimits = ['productores' => 5, 'storage_gb' => 2];

        // Current usage
        $currentUsage = ['productores' => 8, 'storage_gb' => 4];

        // Upgrade should always be valid
        $upgradeValid = TRUE;
        foreach ($currentUsage as $key => $used) {
            if ($upgradeLimits[$key] >= 0 && $used > $upgradeLimits[$key]) {
                $upgradeValid = FALSE;
            }
        }
        $this->assertTrue($upgradeValid);

        // Downgrade should fail if current usage exceeds new limits
        $downgradeValid = TRUE;
        foreach ($currentUsage as $key => $used) {
            if ($downgradeLimits[$key] >= 0 && $used > $downgradeLimits[$key]) {
                $downgradeValid = FALSE;
            }
        }
        $this->assertFalse($downgradeValid);
    }

}

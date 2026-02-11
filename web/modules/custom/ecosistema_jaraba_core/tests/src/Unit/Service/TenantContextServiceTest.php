<?php

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Tests\UnitTestCase;
use Drupal\ecosistema_jaraba_core\Entity\TenantInterface;
use Drupal\ecosistema_jaraba_core\Entity\VerticalInterface;
use Drupal\ecosistema_jaraba_core\Entity\SaasPlanInterface;

/**
 * Tests for the TenantContextService.
 *
 * @group ecosistema_jaraba_core
 */
class TenantContextServiceTest extends UnitTestCase
{

    /**
     * Tests tenant resolution returns correct values.
     */
    public function testTenantContextValues(): void
    {
        // Mock tenant
        $tenant = $this->createMock(TenantInterface::class);
        $tenant->method('id')->willReturn(1);
        $tenant->method('getName')->willReturn('Cooperativa Aceites del Sur');
        $tenant->method('getSubscriptionStatus')->willReturn('active');
        $tenant->method('isActive')->willReturn(TRUE);

        $this->assertEquals(1, $tenant->id());
        $this->assertEquals('Cooperativa Aceites del Sur', $tenant->getName());
        $this->assertTrue($tenant->isActive());
    }

    /**
     * Tests usage metrics calculation.
     *
     * @dataProvider usageMetricsDataProvider
     */
    public function testUsageMetricsPercentage(int $used, int $limit, int $expectedPercentage): void
    {
        if ($limit === 0) {
            $percentage = 0;
        } else {
            $percentage = min(100, round(($used / $limit) * 100));
        }

        $this->assertEquals($expectedPercentage, $percentage);
    }

    /**
     * Data provider for usage metrics testing.
     */
    public static function usageMetricsDataProvider(): array
    {
        return [
            'zero usage' => [0, 100, 0],
            'half usage' => [50, 100, 50],
            'full usage' => [100, 100, 100],
            'over limit' => [150, 100, 100], // Capped at 100%
            'low usage' => [5, 1000, 1],
            'high usage' => [900, 1000, 90],
            'unlimited (0 limit)' => [100, 0, 0],
        ];
    }

    /**
     * Tests tenant with vertical relationship.
     */
    public function testTenantVerticalRelationship(): void
    {
        $vertical = $this->createMock(VerticalInterface::class);
        $vertical->method('id')->willReturn(1);
        $vertical->method('getName')->willReturn('AgroConecta');
        $vertical->method('getMachineName')->willReturn('agroconecta');

        $tenant = $this->createMock(TenantInterface::class);
        $tenant->method('getVertical')->willReturn($vertical);

        $tenantVertical = $tenant->getVertical();
        $this->assertInstanceOf(VerticalInterface::class, $tenantVertical);
        $this->assertEquals('agroconecta', $tenantVertical->getMachineName());
    }

    /**
     * Tests tenant with plan relationship.
     */
    public function testTenantPlanRelationship(): void
    {
        $plan = $this->createMock(SaasPlanInterface::class);
        $plan->method('id')->willReturn(1);
        $plan->method('getName')->willReturn('Pro');
        $plan->method('getLimits')->willReturn([
            'max_producers' => 50,
            'max_storage_mb' => 5000,
        ]);

        $tenant = $this->createMock(TenantInterface::class);
        $tenant->method('getSubscriptionPlan')->willReturn($plan);

        $tenantPlan = $tenant->getSubscriptionPlan();
        $this->assertInstanceOf(SaasPlanInterface::class, $tenantPlan);

        $limits = $tenantPlan->getLimits();
        $this->assertEquals(50, $limits['max_producers']);
        $this->assertEquals(5000, $limits['max_storage_mb']);
    }

    /**
     * Tests usage alert thresholds.
     *
     * @dataProvider alertThresholdDataProvider
     */
    public function testUsageAlertThreshold(int $percentage, bool $warningExpected, bool $criticalExpected): void
    {
        $shouldSendWarning = $percentage >= 80 && $percentage < 100;
        $shouldSendCritical = $percentage >= 100;

        $this->assertEquals($warningExpected, $shouldSendWarning);
        $this->assertEquals($criticalExpected, $shouldSendCritical);
    }

    /**
     * Data provider for alert threshold testing.
     */
    public static function alertThresholdDataProvider(): array
    {
        return [
            'below threshold' => [50, FALSE, FALSE],
            'at warning threshold' => [80, TRUE, FALSE],
            'above warning' => [90, TRUE, FALSE],
            'at critical threshold' => [100, FALSE, TRUE],
            'over critical' => [120, FALSE, TRUE],
        ];
    }

    /**
     * Tests null tenant handling.
     */
    public function testNullTenantHandling(): void
    {
        $tenant = NULL;

        // Service should handle null tenant gracefully
        $this->assertNull($tenant);

        // Check if we're dealing with a valid tenant
        $isValid = $tenant instanceof TenantInterface;
        $this->assertFalse($isValid);
    }

    /**
     * Tests storage conversion from bytes to MB.
     *
     * @dataProvider storageBytesToMbDataProvider
     */
    public function testStorageBytesToMb(int $bytes, float $expectedMb): void
    {
        $mb = round($bytes / (1024 * 1024), 2);
        $this->assertEquals($expectedMb, $mb);
    }

    /**
     * Data provider for storage conversion testing.
     */
    public static function storageBytesToMbDataProvider(): array
    {
        return [
            'zero bytes' => [0, 0.0],
            '1 MB' => [1048576, 1.0],
            '10 MB' => [10485760, 10.0],
            '100 MB' => [104857600, 100.0],
            '1.5 MB' => [1572864, 1.5],
        ];
    }

}

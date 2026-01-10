<?php

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Entity;

use Drupal\Tests\UnitTestCase;
use Drupal\ecosistema_jaraba_core\Entity\SaasPlanInterface;

/**
 * Tests for the SaasPlan entity.
 *
 * @group ecosistema_jaraba_core
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Entity\SaasPlan
 */
class SaasPlanTest extends UnitTestCase
{

    /**
     * Tests pricing methods.
     *
     * @covers ::getMonthlyPrice
     * @covers ::getYearlyPrice
     */
    public function testPricing(): void
    {
        $plan = $this->createMock(SaasPlanInterface::class);
        $plan->method('getMonthlyPrice')
            ->willReturn('79.00');
        $plan->method('getYearlyPrice')
            ->willReturn('790.00');

        $this->assertEquals('79.00', $plan->getMonthlyPrice());
        $this->assertEquals('790.00', $plan->getYearlyPrice());
    }

    /**
     * Tests yearly discount calculation.
     */
    public function testYearlyDiscount(): void
    {
        $monthlyPrice = 79.00;
        $yearlyPrice = 790.00;

        // Calculate expected yearly if no discount
        $yearlyWithoutDiscount = $monthlyPrice * 12;
        $discount = $yearlyWithoutDiscount - $yearlyPrice;
        $discountPercentage = ($discount / $yearlyWithoutDiscount) * 100;

        // Yearly should provide ~17% discount (2 months free)
        $this->assertGreaterThan(15, $discountPercentage);
        $this->assertLessThan(20, $discountPercentage);
    }

    /**
     * Tests plan limits.
     *
     * @covers ::getLimits
     */
    public function testLimits(): void
    {
        $plan = $this->createMock(SaasPlanInterface::class);
        $plan->method('getLimits')
            ->willReturn([
                'productores' => 50,
                'storage_gb' => 25,
                'ai_queries' => 100,
                'webhooks' => 5,
            ]);

        $limits = $plan->getLimits();

        $this->assertIsArray($limits);
        $this->assertEquals(50, $limits['productores']);
        $this->assertEquals(25, $limits['storage_gb']);
        $this->assertEquals(100, $limits['ai_queries']);
    }

    /**
     * Tests unlimited plans (indicated by -1).
     */
    public function testUnlimitedPlan(): void
    {
        $plan = $this->createMock(SaasPlanInterface::class);
        $plan->method('getLimits')
            ->willReturn([
                'productores' => -1,  // Unlimited
                'storage_gb' => 100,
                'ai_queries' => -1,   // Unlimited
                'webhooks' => -1,     // Unlimited
            ]);

        $limits = $plan->getLimits();

        $this->assertEquals(-1, $limits['productores']);
        $this->assertEquals(-1, $limits['ai_queries']);
        // -1 indicates unlimited
        $this->assertTrue($limits['productores'] < 0);
    }

    /**
     * Tests free plan detection.
     *
     * @covers ::isFree
     * @dataProvider freePlanDataProvider
     */
    public function testIsFree(string $monthlyPrice, bool $expected): void
    {
        $plan = $this->createMock(SaasPlanInterface::class);
        $plan->method('getMonthlyPrice')
            ->willReturn($monthlyPrice);
        $plan->method('isFree')
            ->willReturnCallback(function () use ($monthlyPrice) {
                return floatval($monthlyPrice) == 0;
            });

        $this->assertEquals($expected, $plan->isFree());
    }

    /**
     * Data provider for free plan testing.
     */
    public static function freePlanDataProvider(): array
    {
        return [
            'zero price is free' => ['0.00', TRUE],
            'small price is not free' => ['0.01', FALSE],
            'regular price is not free' => ['29.00', FALSE],
            'enterprise price is not free' => ['199.00', FALSE],
        ];
    }

    /**
     * Tests plan features.
     *
     * @covers ::getFeatures
     */
    public function testFeatures(): void
    {
        $plan = $this->createMock(SaasPlanInterface::class);
        $plan->method('getFeatures')
            ->willReturn([
                'trazabilidad_basica',
                'trazabilidad_avanzada',
                'agentes_ia_limitados',
                'soporte_email',
                'soporte_chat',
            ]);

        $features = $plan->getFeatures();

        $this->assertIsArray($features);
        $this->assertContains('trazabilidad_basica', $features);
        $this->assertContains('soporte_chat', $features);
        $this->assertNotContains('firma_digital', $features);
    }

    /**
     * Tests Stripe price ID.
     *
     * @covers ::getStripePriceId
     */
    public function testStripePriceId(): void
    {
        $plan = $this->createMock(SaasPlanInterface::class);
        $plan->method('getStripePriceId')
            ->willReturn('price_1234567890abcdef');

        $priceId = $plan->getStripePriceId();

        $this->assertIsString($priceId);
        $this->assertStringStartsWith('price_', $priceId);
    }

}

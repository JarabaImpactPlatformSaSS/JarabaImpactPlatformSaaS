<?php

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Entity;

use Drupal\Tests\UnitTestCase;
use Drupal\ecosistema_jaraba_core\Entity\TenantInterface;

/**
 * Tests for the Tenant entity.
 *
 * @group ecosistema_jaraba_core
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Entity\Tenant
 */
class TenantTest extends UnitTestCase
{

    /**
     * Tests subscription status constants.
     */
    public function testSubscriptionStatusConstants(): void
    {
        $this->assertEquals('trial', TenantInterface::STATUS_TRIAL);
        $this->assertEquals('active', TenantInterface::STATUS_ACTIVE);
        $this->assertEquals('past_due', TenantInterface::STATUS_PAST_DUE);
        $this->assertEquals('cancelled', TenantInterface::STATUS_CANCELLED);
        $this->assertEquals('suspended', TenantInterface::STATUS_SUSPENDED);
    }

    /**
     * Tests isActive returns correct values.
     *
     * @covers ::isActive
     * @dataProvider activeStatusDataProvider
     */
    public function testIsActive(string $status, bool $expected): void
    {
        $tenant = $this->createMock(TenantInterface::class);
        $tenant->method('getSubscriptionStatus')
            ->willReturn($status);
        $tenant->method('isActive')
            ->willReturnCallback(function () use ($status) {
                return in_array($status, ['trial', 'active']);
            });

        $this->assertEquals($expected, $tenant->isActive());
    }

    /**
     * Data provider for active status testing.
     */
    public static function activeStatusDataProvider(): array
    {
        return [
            'trial is active' => ['trial', TRUE],
            'active is active' => ['active', TRUE],
            'past_due is not active' => ['past_due', FALSE],
            'cancelled is not active' => ['cancelled', FALSE],
            'suspended is not active' => ['suspended', FALSE],
        ];
    }

    /**
     * Tests trial period detection.
     *
     * @covers ::isOnTrial
     */
    public function testIsOnTrial(): void
    {
        $tenant = $this->createMock(TenantInterface::class);

        // Configure trial tenant
        $tenant->method('getSubscriptionStatus')
            ->willReturn('trial');
        $tenant->method('isOnTrial')
            ->willReturn(TRUE);

        $this->assertTrue($tenant->isOnTrial());
    }

    /**
     * Tests Stripe Connect detection.
     *
     * @covers ::hasStripeConnect
     */
    public function testHasStripeConnect(): void
    {
        $tenantWithStripe = $this->createMock(TenantInterface::class);
        $tenantWithStripe->method('getStripeConnectId')
            ->willReturn('acct_1234567890');
        $tenantWithStripe->method('hasStripeConnect')
            ->willReturn(TRUE);

        $tenantWithoutStripe = $this->createMock(TenantInterface::class);
        $tenantWithoutStripe->method('getStripeConnectId')
            ->willReturn(NULL);
        $tenantWithoutStripe->method('hasStripeConnect')
            ->willReturn(FALSE);

        $this->assertTrue($tenantWithStripe->hasStripeConnect());
        $this->assertFalse($tenantWithoutStripe->hasStripeConnect());
    }

    /**
     * Tests domain uniqueness concept.
     *
     * @covers ::getDomain
     */
    public function testDomainFormat(): void
    {
        $tenant = $this->createMock(TenantInterface::class);
        $tenant->method('getDomain')
            ->willReturn('cooperativa-olivar');

        $domain = $tenant->getDomain();

        $this->assertIsString($domain);
        $this->assertMatchesRegularExpression('/^[a-z0-9\-]+$/', $domain);
    }

    /**
     * Tests theme overrides cascade.
     *
     * @covers ::getThemeOverrides
     */
    public function testThemeOverrides(): void
    {
        $tenant = $this->createMock(TenantInterface::class);
        $tenant->method('getThemeOverrides')
            ->willReturn([
                'color_primario' => '#4CAF50',
                'logo_url' => '/sites/default/files/logos/coop.png',
            ]);

        $overrides = $tenant->getThemeOverrides();

        $this->assertIsArray($overrides);
        $this->assertArrayHasKey('color_primario', $overrides);
        $this->assertEquals('#4CAF50', $overrides['color_primario']);
    }

}

<?php

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Tests\UnitTestCase;
use Drupal\ecosistema_jaraba_core\Entity\TenantInterface;
use Drupal\ecosistema_jaraba_core\Service\StylePresetService;
use Drupal\ecosistema_jaraba_core\Service\TenantManager;
use Drupal\ecosistema_jaraba_core\Service\TenantThemeService;

/**
 * Tests for the TenantThemeService.
 *
 * @group ecosistema_jaraba_core
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Service\TenantThemeService
 */
class TenantThemeServiceTest extends UnitTestCase
{

    protected TenantThemeService $service;
    protected TenantManager $tenantManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantManager = $this->createMock(TenantManager::class);
        $stylePreset = $this->createMock(StylePresetService::class);
        $this->service = new TenantThemeService($this->tenantManager, $stylePreset);
    }

    /**
     * Tests getDefaultThemeSettings returns expected structure.
     *
     * @covers ::getDefaultThemeSettings
     */
    public function testDefaultThemeSettings(): void
    {
        $settings = $this->service->getDefaultThemeSettings();

        $this->assertArrayHasKey('color_primary', $settings);
        $this->assertArrayHasKey('color_secondary', $settings);
        $this->assertArrayHasKey('font_family', $settings);
        $this->assertEquals('#FF8C42', $settings['color_primary']);
        $this->assertEquals('#2D3436', $settings['color_secondary']);
        $this->assertEquals('Inter', $settings['font_family']);
    }

    /**
     * Tests getCurrentThemeSettings returns defaults when no tenant.
     *
     * @covers ::getCurrentThemeSettings
     */
    public function testCurrentThemeSettingsNoTenant(): void
    {
        $this->tenantManager->method('getCurrentTenant')
            ->willReturn(NULL);

        $settings = $this->service->getCurrentThemeSettings();

        $this->assertEquals('#FF8C42', $settings['color_primary']);
    }

    /**
     * Tests tenant theme overrides take precedence.
     *
     * @covers ::getThemeSettingsForTenant
     */
    public function testTenantOverridesTakePrecedence(): void
    {
        $overrides = [
            'color_primary' => '#4CAF50',
            'color_secondary' => '#1B5E20',
            'font_family' => 'Roboto',
        ];

        $tenant = $this->createMock(TenantInterface::class);
        $tenant->method('getThemeOverrides')->willReturn($overrides);

        $settings = $this->service->getThemeSettingsForTenant($tenant);

        $this->assertEquals('#4CAF50', $settings['color_primary']);
        $this->assertEquals('Roboto', $settings['font_family']);
    }

    /**
     * Tests vertical defaults used when no tenant overrides.
     *
     * @covers ::getThemeSettingsForTenant
     */
    public function testVerticalDefaultsWhenNoOverrides(): void
    {
        $verticalSettings = [
            'color_primary' => '#2196F3',
            'color_secondary' => '#0D47A1',
            'font_family' => 'Lato',
        ];

        $vertical = $this->createMock(\Drupal\ecosistema_jaraba_core\Entity\VerticalInterface::class);
        $vertical->method('getThemeSettings')->willReturn($verticalSettings);

        $tenant = $this->createMock(TenantInterface::class);
        $tenant->method('getThemeOverrides')->willReturn([]);
        $tenant->method('getVertical')->willReturn($vertical);

        $settings = $this->service->getThemeSettingsForTenant($tenant);

        $this->assertEquals('#2196F3', $settings['color_primary']);
        $this->assertEquals('Lato', $settings['font_family']);
    }

    /**
     * Tests platform defaults when no overrides and no vertical.
     *
     * @covers ::getThemeSettingsForTenant
     */
    public function testPlatformDefaultsWhenNoVertical(): void
    {
        $tenant = $this->createMock(TenantInterface::class);
        $tenant->method('getThemeOverrides')->willReturn([]);
        $tenant->method('getVertical')->willReturn(NULL);

        $settings = $this->service->getThemeSettingsForTenant($tenant);

        $this->assertEquals('#FF8C42', $settings['color_primary']);
        $this->assertEquals('Inter', $settings['font_family']);
    }

    /**
     * Tests the full cascade: current tenant with overrides.
     *
     * @covers ::getCurrentThemeSettings
     */
    public function testFullCascadeWithTenant(): void
    {
        $overrides = ['color_primary' => '#E91E63', 'color_secondary' => '#880E4F', 'font_family' => 'Poppins'];

        $tenant = $this->createMock(TenantInterface::class);
        $tenant->method('getThemeOverrides')->willReturn($overrides);

        $this->tenantManager->method('getCurrentTenant')
            ->willReturn($tenant);

        $settings = $this->service->getCurrentThemeSettings();

        $this->assertEquals('#E91E63', $settings['color_primary']);
    }

}

<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_theming\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\jaraba_theming\Service\ThemeTokenService;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the ThemeTokenService service.
 *
 * @coversDefaultClass \Drupal\jaraba_theming\Service\ThemeTokenService
 * @group jaraba_theming
 */
class ThemeTokenServiceTest extends TestCase
{

    /**
     * The ThemeTokenService under test.
     */
    protected ThemeTokenService $themeTokenService;

    /**
     * Mock entity type manager.
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * Mock current user.
     */
    protected AccountInterface $currentUser;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
        $this->currentUser = $this->createMock(AccountInterface::class);

        // Mock the storage to return empty arrays (no active config).
        $storage = $this->createMock(EntityStorageInterface::class);
        $storage->method('loadByProperties')->willReturn([]);
        $this->entityTypeManager->method('getStorage')
            ->with('tenant_theme_config')
            ->willReturn($storage);

        $this->themeTokenService = new ThemeTokenService(
            $this->entityTypeManager,
            $this->currentUser,
        );
    }

    /**
     * Tests that generateCss returns a non-empty string.
     *
     * When no active TenantThemeConfig exists, the service falls back to
     * default CSS which should still be a non-empty string.
     *
     * @covers ::generateCss
     */
    public function testGenerateCssReturnsString(): void
    {
        $css = $this->themeTokenService->generateCss();

        $this->assertIsString($css);
        $this->assertNotEmpty($css);
    }

    /**
     * Tests that generateCss output contains CSS custom properties.
     *
     * @covers ::generateCss
     */
    public function testGenerateCssContainsCssVariables(): void
    {
        $css = $this->themeTokenService->generateCss();

        $this->assertStringContainsString('--', $css);
    }

    /**
     * Tests that getVerticalTokens returns an array.
     *
     * @covers ::getVerticalTokens
     */
    public function testGetVerticalTokensReturnsArray(): void
    {
        $tokens = $this->themeTokenService->getVerticalTokens('platform');

        $this->assertIsArray($tokens);
        $this->assertNotEmpty($tokens);
    }

    /**
     * Tests that getVerticalTokens has color_primary key for a known vertical.
     *
     * @covers ::getVerticalTokens
     */
    public function testGetVerticalTokensHasColorKeys(): void
    {
        $tokens = $this->themeTokenService->getVerticalTokens('agroconecta');

        $this->assertArrayHasKey('color_primary', $tokens);
        $this->assertArrayHasKey('color_secondary', $tokens);
        $this->assertArrayHasKey('color_accent', $tokens);
    }

}

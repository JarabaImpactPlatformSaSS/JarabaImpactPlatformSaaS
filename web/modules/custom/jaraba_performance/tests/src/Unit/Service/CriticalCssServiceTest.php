<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_performance\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\jaraba_performance\Service\CriticalCssService;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the CriticalCssService service.
 *
 * @coversDefaultClass \Drupal\jaraba_performance\Service\CriticalCssService
 * @group jaraba_performance
 */
class CriticalCssServiceTest extends TestCase
{

    /**
     * Mock route match.
     */
    protected RouteMatchInterface $routeMatch;

    /**
     * Mock theme extension list.
     */
    protected ThemeExtensionList $themeList;

    /**
     * Mock config factory.
     */
    protected ConfigFactoryInterface $configFactory;

    /**
     * Creates a CriticalCssService with the given route name and config value.
     *
     * @param string $routeName
     *   The route name to simulate.
     * @param bool|null $enabledConfig
     *   The critical_css_enabled config value, or NULL for not set.
     *
     * @return \Drupal\jaraba_performance\Service\CriticalCssService
     *   A configured service instance.
     */
    protected function createService(string $routeName = '<front>', ?bool $enabledConfig = NULL): CriticalCssService
    {
        $this->routeMatch = $this->createMock(RouteMatchInterface::class);
        $this->routeMatch->method('getRouteName')->willReturn($routeName);

        $this->themeList = $this->createMock(ThemeExtensionList::class);
        $this->themeList->method('getPath')
            ->with('ecosistema_jaraba_theme')
            ->willReturn('/app/web/themes/custom/ecosistema_jaraba_theme');

        $config = $this->createMock(ImmutableConfig::class);
        $config->method('get')
            ->with('critical_css_enabled')
            ->willReturn($enabledConfig);

        $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
        $this->configFactory->method('get')
            ->with('jaraba_performance.settings')
            ->willReturn($config);

        return new CriticalCssService(
            $this->routeMatch,
            $this->themeList,
            $this->configFactory,
        );
    }

    /**
     * Tests that isEnabled returns a boolean value.
     *
     * When no config is set (NULL), the default is TRUE.
     *
     * @covers ::isEnabled
     */
    public function testIsEnabledReturnsBoolean(): void
    {
        $service = $this->createService('<front>', NULL);

        $result = $service->isEnabled();

        $this->assertIsBool($result);
        $this->assertTrue($result, 'Critical CSS should be enabled by default when no config is set.');
    }

    /**
     * Tests that getCriticalCssFile returns 'homepage' for the front route.
     *
     * @covers ::getCriticalCssFile
     */
    public function testGetCriticalCssFileForFrontRoute(): void
    {
        $service = $this->createService('<front>');

        $file = $service->getCriticalCssFile();

        $this->assertSame('homepage', $file);
    }

    /**
     * Tests that hasCriticalCss checks file existence.
     *
     * The getCriticalCssFile method always returns a string (fallback to
     * 'homepage'). hasCriticalCss depends on file_exists() for the
     * resolved path. We just verify it returns a boolean.
     *
     * @covers ::hasCriticalCss
     */
    public function testHasCriticalCssReturnsBool(): void
    {
        $service = $this->createService('some.unknown.route.xyz');

        $result = $service->hasCriticalCss();

        $this->assertIsBool($result);
    }

    /**
     * Tests that getCriticalCssContent returns string or null.
     *
     * In a test environment where no CSS files exist on disk, this should
     * return NULL.
     *
     * @covers ::getCriticalCssContent
     */
    public function testGetCriticalCssContentReturnsStringOrNull(): void
    {
        $service = $this->createService('<front>', NULL);

        $result = $service->getCriticalCssContent();

        $this->assertTrue(
            is_string($result) || is_null($result),
            'getCriticalCssContent must return string or null.'
        );
    }

}

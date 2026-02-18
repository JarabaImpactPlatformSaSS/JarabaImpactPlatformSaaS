<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_mobile\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_mobile\Service\DeepLinkResolverService;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests for the DeepLinkResolverService.
 *
 * @coversDefaultClass \Drupal\jaraba_mobile\Service\DeepLinkResolverService
 * @group jaraba_mobile
 */
class DeepLinkResolverServiceTest extends UnitTestCase {

  /**
   * The service under test.
   */
  protected DeepLinkResolverService $service;

  /**
   * Mocked tenant context.
   */
  protected TenantContextService $tenantContext;

  /**
   * Mocked route provider.
   */
  protected RouteProviderInterface $routeProvider;

  /**
   * Mocked request stack.
   */
  protected RequestStack $requestStack;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->tenantContext = $this->createMock(TenantContextService::class);
    $this->routeProvider = $this->createMock(RouteProviderInterface::class);
    $this->requestStack = $this->createMock(RequestStack::class);

    // Mock request for base URL resolution.
    $request = $this->createMock(Request::class);
    $request->method('getSchemeAndHttpHost')
      ->willReturn('https://app.pepejaraba.com');
    $this->requestStack->method('getCurrentRequest')
      ->willReturn($request);

    // Mock config for deep_link_base_url.
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')
      ->willReturnCallback(function (string $key) {
        if ($key === 'deep_link_base_url') {
          return 'https://app.pepejaraba.com';
        }
        return NULL;
      });

    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory->method('get')
      ->with('jaraba_mobile.settings')
      ->willReturn($config);

    $container = new ContainerBuilder();
    $container->set('config.factory', $configFactory);
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $this->service = new DeepLinkResolverService(
      $this->tenantContext,
      $this->routeProvider,
      $this->requestStack,
    );
  }

  /**
   * Tests resolving a jobs deep link.
   *
   * @covers ::resolve
   */
  public function testResolveJobsDeepLink(): void {
    $result = $this->service->resolve('jaraba://jobs/123');

    $this->assertTrue($result['valid']);
    $this->assertSame('jaraba_job_board.job_detail', $result['route']);
    $this->assertSame('123', $result['params']['job_posting']);
    $this->assertStringContainsString('/jobs/123', $result['url']);
  }

  /**
   * Tests resolving a courses deep link.
   *
   * @covers ::resolve
   */
  public function testResolveCoursesDeepLink(): void {
    $result = $this->service->resolve('jaraba://courses/456');

    $this->assertTrue($result['valid']);
    $this->assertSame('jaraba_lms.course_detail', $result['route']);
    $this->assertSame('456', $result['params']['course']);
    $this->assertStringContainsString('/courses/456', $result['url']);
  }

  /**
   * Tests resolving a notifications deep link (no ID parameter).
   *
   * @covers ::resolve
   */
  public function testResolveNotificationsDeepLink(): void {
    $result = $this->service->resolve('jaraba://notifications');

    $this->assertTrue($result['valid']);
    $this->assertSame('jaraba_mobile.push_history', $result['route']);
    $this->assertEmpty($result['params']);
    $this->assertStringContainsString('/notifications', $result['url']);
  }

  /**
   * Tests resolving a dashboard deep link.
   *
   * @covers ::resolve
   */
  public function testResolveDashboardDeepLink(): void {
    $result = $this->service->resolve('jaraba://dashboard');

    $this->assertTrue($result['valid']);
    $this->assertSame('ecosistema_jaraba_core.tenant_dashboard', $result['route']);
    $this->assertEmpty($result['params']);
  }

  /**
   * Tests resolving a profile deep link with user ID.
   *
   * @covers ::resolve
   */
  public function testResolveProfileDeepLink(): void {
    $result = $this->service->resolve('jaraba://profile/42');

    $this->assertTrue($result['valid']);
    $this->assertSame('entity.user.canonical', $result['route']);
    $this->assertSame('42', $result['params']['user']);
  }

  /**
   * Tests resolve returns invalid for unknown resource.
   *
   * @covers ::resolve
   */
  public function testResolveReturnsInvalidForUnknownResource(): void {
    $result = $this->service->resolve('jaraba://unknown/123');

    $this->assertFalse($result['valid']);
    $this->assertEmpty($result['route']);
    $this->assertEmpty($result['params']);
  }

  /**
   * Tests resolve returns invalid for empty path.
   *
   * @covers ::resolve
   */
  public function testResolveReturnsInvalidForEmptyPath(): void {
    $result = $this->service->resolve('jaraba://');

    $this->assertFalse($result['valid']);
    $this->assertEmpty($result['route']);
  }

  /**
   * Tests generateLink creates correct deep link URI for jobs route.
   *
   * @covers ::generateLink
   */
  public function testGenerateLinkForJobsRoute(): void {
    $link = $this->service->generateLink(
      'jaraba_job_board.job_detail',
      ['job_posting' => '123']
    );

    $this->assertSame('jaraba://jobs/123', $link);
  }

  /**
   * Tests generateLink for a route with no parameter.
   *
   * @covers ::generateLink
   */
  public function testGenerateLinkForDashboard(): void {
    $link = $this->service->generateLink('ecosistema_jaraba_core.tenant_dashboard');

    $this->assertSame('jaraba://dashboard', $link);
  }

  /**
   * Tests generateLink fallback for unknown route name.
   *
   * @covers ::generateLink
   */
  public function testGenerateLinkFallbackForUnknownRoute(): void {
    $link = $this->service->generateLink('some.custom.route');

    // Fallback: route name with dots replaced by slashes.
    $this->assertSame('jaraba://some/custom/route', $link);
  }

  /**
   * Tests getUniversalLink converts deep link to HTTPS URL.
   *
   * @covers ::getUniversalLink
   */
  public function testGetUniversalLinkConvertsToHttpsUrl(): void {
    $url = $this->service->getUniversalLink('jaraba://products/789');

    $this->assertStringStartsWith('https://', $url);
    $this->assertStringContainsString('/products/789', $url);
  }

  /**
   * Tests getUniversalLink returns base URL for invalid deep link.
   *
   * @covers ::getUniversalLink
   */
  public function testGetUniversalLinkReturnsBaseUrlForInvalidLink(): void {
    $url = $this->service->getUniversalLink('jaraba://unknown/resource');

    // The URL should be the base URL (since 'unknown' is not a valid resource).
    $this->assertStringStartsWith('https://', $url);
  }

  /**
   * Tests that resolve and generateLink are inverse operations.
   *
   * @covers ::resolve
   * @covers ::generateLink
   */
  public function testResolveAndGenerateLinkAreInverse(): void {
    $originalLink = 'jaraba://orders/55';
    $resolved = $this->service->resolve($originalLink);
    $regenerated = $this->service->generateLink($resolved['route'], $resolved['params']);

    $this->assertSame($originalLink, $regenerated);
  }

}

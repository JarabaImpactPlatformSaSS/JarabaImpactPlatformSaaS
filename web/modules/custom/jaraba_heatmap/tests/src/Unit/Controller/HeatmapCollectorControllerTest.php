<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_heatmap\Unit\Controller;

use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_heatmap\Controller\HeatmapCollectorController;
use Drupal\jaraba_heatmap\Service\HeatmapCollectorService;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests for the HeatmapCollectorController.
 *
 * @coversDefaultClass \Drupal\jaraba_heatmap\Controller\HeatmapCollectorController
 * @group jaraba_heatmap
 */
class HeatmapCollectorControllerTest extends UnitTestCase {

  /**
   * The controller under test.
   */
  protected HeatmapCollectorController $controller;

  /**
   * Mocked collector service.
   */
  protected HeatmapCollectorService $collector;

  /**
   * Mocked tenant context.
   */
  protected TenantContextService $tenantContext;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->collector = $this->createMock(HeatmapCollectorService::class);
    $this->tenantContext = $this->createMock(TenantContextService::class);

    $this->controller = new HeatmapCollectorController(
      $this->collector,
      $this->tenantContext,
    );

    // ControllerBase needs a container for getLogger().
    $container = new \Drupal\Core\DependencyInjection\ContainerBuilder();
    $loggerFactory = $this->createMock(\Drupal\Core\Logger\LoggerChannelFactoryInterface::class);
    $loggerFactory->method('get')->willReturn($this->createMock(\Psr\Log\LoggerInterface::class));
    $container->set('logger.factory', $loggerFactory);
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }

  /**
   * Tests that GET requests are rejected with 405.
   *
   * @covers ::collect
   */
  public function testCollectRejectsGetRequests(): void {
    $request = Request::create('/heatmap/collect', 'GET');

    $response = $this->controller->collect($request);

    $this->assertSame(405, $response->getStatusCode());
  }

  /**
   * Tests that invalid JSON payload returns 400.
   *
   * @covers ::collect
   */
  public function testCollectReturnsBadRequestForInvalidJson(): void {
    $request = Request::create('/heatmap/collect', 'POST', [], [], [], [], 'not-json');

    $response = $this->controller->collect($request);

    $this->assertSame(400, $response->getStatusCode());
  }

  /**
   * Tests that payload without events array returns 400.
   *
   * @covers ::collect
   */
  public function testCollectReturnsBadRequestWhenEventsEmpty(): void {
    $payload = json_encode(['session_id' => 'abc', 'page' => '/test']);
    $request = Request::create('/heatmap/collect', 'POST', [], [], [], [], $payload);

    $response = $this->controller->collect($request);

    $this->assertSame(400, $response->getStatusCode());
  }

  /**
   * Tests that payload missing session_id returns 400.
   *
   * @covers ::collect
   */
  public function testCollectReturnsBadRequestWhenSessionIdMissing(): void {
    $payload = json_encode([
      'page' => '/test',
      'events' => [['t' => 'click', 'x' => 100, 'y' => 200]],
    ]);
    $request = Request::create('/heatmap/collect', 'POST', [], [], [], [], $payload);

    $response = $this->controller->collect($request);

    $this->assertSame(400, $response->getStatusCode());
  }

  /**
   * Tests that missing tenant context returns 403.
   *
   * @covers ::collect
   */
  public function testCollectReturnsForbiddenWithoutTenant(): void {
    $this->tenantContext->method('getCurrentTenantId')->willReturn(NULL);

    $payload = json_encode([
      'session_id' => 'sess-123',
      'page' => '/productos',
      'events' => [['t' => 'click', 'x' => 100, 'y' => 200]],
    ]);
    $request = Request::create('/heatmap/collect', 'POST', [], [], [], [], $payload);

    $response = $this->controller->collect($request);

    $this->assertSame(403, $response->getStatusCode());
  }

  /**
   * Tests that valid request returns 204 and processes events.
   *
   * @covers ::collect
   */
  public function testCollectReturns204ForValidPayload(): void {
    $this->tenantContext->method('getCurrentTenantId')->willReturn(1);

    $this->collector->expects($this->once())
      ->method('processEvents')
      ->with($this->callback(function (array $payload) {
        return $payload['session_id'] === 'sess-123'
          && $payload['page'] === '/productos'
          && $payload['tenant_id'] === 1
          && is_array($payload['events']);
      }));

    $payload = json_encode([
      'session_id' => 'sess-123',
      'page' => '/productos',
      'events' => [['t' => 'click', 'x' => 100, 'y' => 200]],
    ]);
    $request = Request::create('/heatmap/collect', 'POST', [], [], [], [], $payload);

    $response = $this->controller->collect($request);

    $this->assertSame(204, $response->getStatusCode());
    $this->assertEmpty($response->getContent());
    $cacheControl = $response->headers->get('Cache-Control');
    $this->assertStringContainsString('no-store', $cacheControl);
    $this->assertStringContainsString('no-cache', $cacheControl);
  }

  /**
   * Tests that collector exception does not break response (204 still returned).
   *
   * @covers ::collect
   */
  public function testCollectReturns204EvenWhenCollectorThrows(): void {
    $this->tenantContext->method('getCurrentTenantId')->willReturn(1);

    $this->collector->method('processEvents')
      ->willThrowException(new \RuntimeException('DB error'));

    $payload = json_encode([
      'session_id' => 'sess-123',
      'page' => '/productos',
      'events' => [['t' => 'click', 'x' => 100, 'y' => 200]],
    ]);
    $request = Request::create('/heatmap/collect', 'POST', [], [], [], [], $payload);

    $response = $this->controller->collect($request);

    // Should still return 204 to not block the client.
    $this->assertSame(204, $response->getStatusCode());
  }

}

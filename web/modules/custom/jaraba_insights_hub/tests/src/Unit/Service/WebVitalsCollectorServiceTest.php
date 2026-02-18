<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_insights_hub\Unit\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Entity\TenantInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_insights_hub\Service\WebVitalsCollectorService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for the WebVitalsCollectorService.
 *
 * @coversDefaultClass \Drupal\jaraba_insights_hub\Service\WebVitalsCollectorService
 * @group jaraba_insights_hub
 */
class WebVitalsCollectorServiceTest extends UnitTestCase {

  /**
   * The service under test.
   */
  protected WebVitalsCollectorService $service;

  /**
   * Mocked entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mocked tenant context service.
   */
  protected TenantContextService $tenantContext;

  /**
   * Mocked logger.
   */
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->tenantContext = $this->createMock(TenantContextService::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new WebVitalsCollectorService(
      $this->entityTypeManager,
      $this->tenantContext,
      $this->logger,
    );
  }

  /**
   * Tests validateMetric returns TRUE for valid LCP data.
   *
   * @covers ::validateMetric
   */
  public function testValidateMetricAcceptsValidLcp(): void {
    $data = [
      'page_url' => 'https://example.com/page',
      'metric_name' => 'LCP',
      'metric_value' => 2500,
    ];

    $this->assertTrue($this->service->validateMetric($data));
  }

  /**
   * Tests validateMetric returns TRUE for all valid metric names.
   *
   * @covers ::validateMetric
   * @dataProvider validMetricNamesProvider
   */
  public function testValidateMetricAcceptsAllValidNames(string $metricName): void {
    $data = [
      'page_url' => 'https://example.com/page',
      'metric_name' => $metricName,
      'metric_value' => 100,
    ];

    $this->assertTrue($this->service->validateMetric($data));
  }

  /**
   * Provides valid metric names.
   */
  public static function validMetricNamesProvider(): array {
    return [
      'LCP' => ['LCP'],
      'INP' => ['INP'],
      'CLS' => ['CLS'],
      'FCP' => ['FCP'],
      'TTFB' => ['TTFB'],
    ];
  }

  /**
   * Tests validateMetric rejects missing page_url.
   *
   * @covers ::validateMetric
   */
  public function testValidateMetricRejectsMissingPageUrl(): void {
    $data = [
      'metric_name' => 'LCP',
      'metric_value' => 2500,
    ];

    $this->assertFalse($this->service->validateMetric($data));
  }

  /**
   * Tests validateMetric rejects missing metric_name.
   *
   * @covers ::validateMetric
   */
  public function testValidateMetricRejectsMissingMetricName(): void {
    $data = [
      'page_url' => 'https://example.com/page',
      'metric_value' => 2500,
    ];

    $this->assertFalse($this->service->validateMetric($data));
  }

  /**
   * Tests validateMetric rejects missing metric_value.
   *
   * @covers ::validateMetric
   */
  public function testValidateMetricRejectsMissingMetricValue(): void {
    $data = [
      'page_url' => 'https://example.com/page',
      'metric_name' => 'LCP',
    ];

    $this->assertFalse($this->service->validateMetric($data));
  }

  /**
   * Tests validateMetric rejects invalid metric name.
   *
   * @covers ::validateMetric
   */
  public function testValidateMetricRejectsInvalidMetricName(): void {
    $data = [
      'page_url' => 'https://example.com/page',
      'metric_name' => 'INVALID',
      'metric_value' => 100,
    ];

    $this->assertFalse($this->service->validateMetric($data));
  }

  /**
   * Tests validateMetric rejects negative metric_value.
   *
   * @covers ::validateMetric
   */
  public function testValidateMetricRejectsNegativeValue(): void {
    $data = [
      'page_url' => 'https://example.com/page',
      'metric_name' => 'LCP',
      'metric_value' => -100,
    ];

    $this->assertFalse($this->service->validateMetric($data));
  }

  /**
   * Tests validateMetric rejects values exceeding max for LCP.
   *
   * @covers ::validateMetric
   */
  public function testValidateMetricRejectsExcessiveValue(): void {
    $data = [
      'page_url' => 'https://example.com/page',
      'metric_name' => 'LCP',
      'metric_value' => 70000,
    ];

    $this->assertFalse($this->service->validateMetric($data));
  }

  /**
   * Tests validateMetric rejects CLS value exceeding 100.
   *
   * @covers ::validateMetric
   */
  public function testValidateMetricRejectsExcessiveClsValue(): void {
    $data = [
      'page_url' => 'https://example.com/page',
      'metric_name' => 'CLS',
      'metric_value' => 150,
    ];

    $this->assertFalse($this->service->validateMetric($data));
  }

  /**
   * Tests validateMetric rejects invalid device_type.
   *
   * @covers ::validateMetric
   */
  public function testValidateMetricRejectsInvalidDeviceType(): void {
    $data = [
      'page_url' => 'https://example.com/page',
      'metric_name' => 'LCP',
      'metric_value' => 2500,
      'device_type' => 'smartwatch',
    ];

    $this->assertFalse($this->service->validateMetric($data));
  }

  /**
   * Tests validateMetric accepts valid device_type.
   *
   * @covers ::validateMetric
   * @dataProvider validDeviceTypesProvider
   */
  public function testValidateMetricAcceptsValidDeviceTypes(string $deviceType): void {
    $data = [
      'page_url' => 'https://example.com/page',
      'metric_name' => 'LCP',
      'metric_value' => 2500,
      'device_type' => $deviceType,
    ];

    $this->assertTrue($this->service->validateMetric($data));
  }

  /**
   * Provides valid device types.
   */
  public static function validDeviceTypesProvider(): array {
    return [
      'desktop' => ['desktop'],
      'mobile' => ['mobile'],
      'tablet' => ['tablet'],
    ];
  }

  /**
   * Tests collect returns FALSE when validation fails.
   *
   * @covers ::collect
   */
  public function testCollectReturnsFalseOnInvalidData(): void {
    $result = $this->service->collect([
      'page_url' => '',
      'metric_name' => '',
      'metric_value' => 0,
    ]);

    $this->assertFalse($result);
  }

  /**
   * Tests collect returns FALSE when tenant is not resolved.
   *
   * @covers ::collect
   */
  public function testCollectReturnsFalseWithoutTenant(): void {
    $this->tenantContext->method('getCurrentTenant')->willReturn(NULL);

    $result = $this->service->collect([
      'page_url' => 'https://example.com/page',
      'metric_name' => 'LCP',
      'metric_value' => 2500,
    ]);

    $this->assertFalse($result);
  }

  /**
   * Tests collect stores valid metric data and returns TRUE.
   *
   * @covers ::collect
   */
  public function testCollectStoresValidMetricSuccessfully(): void {
    // Mock tenant — must use TenantInterface (return type of getCurrentTenant).
    $tenant = $this->createMock(TenantInterface::class);
    $tenant->method('id')->willReturn(1);
    $this->tenantContext->method('getCurrentTenant')->willReturn($tenant);

    // Mock entity storage.
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->expects($this->once())->method('save');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('create')
      ->with($this->callback(function (array $values) {
        return $values['tenant_id'] === 1
          && $values['page_url'] === 'https://example.com/page'
          && $values['metric_name'] === 'LCP'
          && $values['metric_value'] === 2500.0
          && $values['metric_rating'] === 'good';
      }))
      ->willReturn($entity);

    $this->entityTypeManager->method('getStorage')
      ->with('web_vitals_metric')
      ->willReturn($storage);

    $result = $this->service->collect([
      'page_url' => 'https://example.com/page',
      'metric_name' => 'LCP',
      'metric_value' => 2500,
    ]);

    $this->assertTrue($result);
  }

  /**
   * Tests that LCP rating is calculated correctly.
   *
   * LCP thresholds: good <= 2500, needs-improvement <= 4000, poor > 4000.
   *
   * @covers ::collect
   */
  public function testLcpRatingCalculation(): void {
    $tenant = $this->createMock(TenantInterface::class);
    $tenant->method('id')->willReturn(1);
    $this->tenantContext->method('getCurrentTenant')->willReturn($tenant);

    $capturedRatings = [];

    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('save')->willReturn(NULL);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('create')
      ->willReturnCallback(function (array $values) use ($entity, &$capturedRatings) {
        $capturedRatings[] = $values['metric_rating'];
        return $entity;
      });

    $this->entityTypeManager->method('getStorage')
      ->with('web_vitals_metric')
      ->willReturn($storage);

    // Good: 2500ms.
    $this->service->collect([
      'page_url' => 'https://example.com',
      'metric_name' => 'LCP',
      'metric_value' => 2500,
    ]);

    // Needs improvement: 3000ms.
    $this->service->collect([
      'page_url' => 'https://example.com',
      'metric_name' => 'LCP',
      'metric_value' => 3000,
    ]);

    // Poor: 5000ms.
    $this->service->collect([
      'page_url' => 'https://example.com',
      'metric_name' => 'LCP',
      'metric_value' => 5000,
    ]);

    $this->assertSame('good', $capturedRatings[0]);
    $this->assertSame('needs-improvement', $capturedRatings[1]);
    $this->assertSame('poor', $capturedRatings[2]);
  }

  /**
   * Tests collect returns FALSE on entity save exception.
   *
   * @covers ::collect
   */
  public function testCollectReturnsFalseOnSaveException(): void {
    $tenant = $this->createMock(TenantInterface::class);
    $tenant->method('id')->willReturn(1);
    $this->tenantContext->method('getCurrentTenant')->willReturn($tenant);

    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('save')
      ->willThrowException(new \Exception('Database error'));

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('create')->willReturn($entity);

    $this->entityTypeManager->method('getStorage')
      ->with('web_vitals_metric')
      ->willReturn($storage);

    $this->logger->expects($this->once())
      ->method('error')
      ->with(
        $this->stringContains('Error almacenando Web Vital'),
        $this->anything()
      );

    $result = $this->service->collect([
      'page_url' => 'https://example.com/page',
      'metric_name' => 'LCP',
      'metric_value' => 2500,
    ]);

    $this->assertFalse($result);
  }

}

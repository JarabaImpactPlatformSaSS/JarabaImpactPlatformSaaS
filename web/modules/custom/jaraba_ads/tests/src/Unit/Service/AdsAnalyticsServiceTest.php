<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ads\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_ads\Entity\AdCampaign;
use Drupal\jaraba_ads\Service\AdsAnalyticsService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para AdsAnalyticsService.
 *
 * Verifica la logica de metricas de anuncios por tenant,
 * rendimiento de campanas y gasto por plataforma.
 *
 * @covers \Drupal\jaraba_ads\Service\AdsAnalyticsService
 * @group jaraba_ads
 */
class AdsAnalyticsServiceTest extends UnitTestCase {

  protected AdsAnalyticsService $service;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected $tenantContext;
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->tenantContext = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['getCurrentTenantId'])
      ->getMock();
    $this->tenantContext->method('getCurrentTenantId')->willReturn(1);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new AdsAnalyticsService(
      $this->entityTypeManager,
      $this->tenantContext,
      $this->logger,
    );
  }

  /**
   * Tests que getTenantAdMetrics devuelve estructura correcta sin datos.
   */
  public function testGetTenantAdMetricsEmpty(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('ad_campaign')
      ->willReturn($storage);

    $result = $this->service->getTenantAdMetrics(1);

    $this->assertIsArray($result);
  }

  /**
   * Tests que getCampaignPerformance devuelve array vacio con campana inexistente.
   */
  public function testGetCampaignPerformanceNotFound(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(999)->willReturn(NULL);

    $this->entityTypeManager->method('getStorage')
      ->with('ad_campaign')
      ->willReturn($storage);

    $result = $this->service->getCampaignPerformance(999);

    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * Tests que getCampaignPerformance devuelve metricas con campana existente.
   */
  public function testGetCampaignPerformanceWithData(): void {
    $impressionsField = new \stdClass();
    $impressionsField->value = 10000;
    $clicksField = new \stdClass();
    $clicksField->value = 500;
    $conversionsField = new \stdClass();
    $conversionsField->value = 50;
    $spendField = new \stdClass();
    $spendField->value = 250.00;
    $budgetTotalField = new \stdClass();
    $budgetTotalField->value = 1000.00;
    $platformField = new \stdClass();
    $platformField->value = 'meta';
    $statusField = new \stdClass();
    $statusField->value = 'active';
    $ctrField = new \stdClass();
    $ctrField->value = 5.0;
    $cpcField = new \stdClass();
    $cpcField->value = 0.50;
    $roasField = new \stdClass();
    $roasField->value = 3.5;

    $campaign = $this->createMock(AdCampaign::class);
    $campaign->method('id')->willReturn(1);
    $campaign->method('label')->willReturn('Test Campaign');
    $campaign->method('getBudgetUtilization')->willReturn(25.0);
    $campaign->method('get')->willReturnMap([
      ['impressions', $impressionsField],
      ['clicks', $clicksField],
      ['conversions', $conversionsField],
      ['spend_to_date', $spendField],
      ['budget_total', $budgetTotalField],
      ['platform', $platformField],
      ['status', $statusField],
      ['ctr', $ctrField],
      ['cpc', $cpcField],
      ['roas', $roasField],
    ]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(1)->willReturn($campaign);

    $this->entityTypeManager->method('getStorage')
      ->with('ad_campaign')
      ->willReturn($storage);

    $result = $this->service->getCampaignPerformance(1);

    $this->assertIsArray($result);
    $this->assertNotEmpty($result);
  }

  /**
   * Tests que getAdSpendByPlatform devuelve array vacio sin campanas.
   */
  public function testGetAdSpendByPlatformEmpty(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('ad_campaign')
      ->willReturn($storage);

    $result = $this->service->getAdSpendByPlatform(1);

    $this->assertIsArray($result);
  }

}

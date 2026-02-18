<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_crm\Unit\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_crm\Service\CrmForecastingService;
use Drupal\jaraba_crm\Service\OpportunityService;
use Drupal\jaraba_crm\Service\PipelineStageService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para CrmForecastingService.
 *
 * @covers \Drupal\jaraba_crm\Service\CrmForecastingService
 * @group jaraba_crm
 */
class CrmForecastingServiceTest extends UnitTestCase {

  protected CrmForecastingService $service;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected OpportunityService $opportunityService;
  protected PipelineStageService $pipelineStageService;
  protected LoggerInterface $logger;
  protected CacheBackendInterface $cache;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->opportunityService = $this->createMock(OpportunityService::class);
    $this->pipelineStageService = $this->createMock(PipelineStageService::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->cache = $this->createMock(CacheBackendInterface::class);

    $this->service = new CrmForecastingService(
      $this->entityTypeManager,
      $this->opportunityService,
      $this->pipelineStageService,
      $this->logger,
      $this->cache,
    );
  }

  /**
   * Tests win rate sin oportunidades cerradas.
   */
  public function testGetWinRateNoClosedDeals(): void {
    $this->opportunityService->method('count')
      ->willReturn(0);

    $result = $this->service->getWinRate(1);
    $this->assertEquals(0.0, $result);
  }

  /**
   * Tests win rate con datos.
   */
  public function testGetWinRateWithData(): void {
    $this->opportunityService->method('count')
      ->willReturnMap([
        [1, 'won', 3],
        [1, 'lost', 7],
      ]);

    $result = $this->service->getWinRate(1);
    $this->assertEquals(30.0, $result);
  }

  /**
   * Tests weighted pipeline.
   */
  public function testGetWeightedPipeline(): void {
    $this->opportunityService->method('getWeightedPipelineValue')
      ->with(1)
      ->willReturn(15000.50);

    $result = $this->service->getWeightedPipeline(1);
    $this->assertEquals(15000.50, $result);
  }

  /**
   * Tests avg deal size sin datos.
   */
  public function testGetAvgDealSizeEmpty(): void {
    $storage = $this->createMock(\Drupal\Core\Entity\EntityStorageInterface::class);
    $query = $this->createMock(\Drupal\Core\Entity\Query\QueryInterface::class);

    $query->method('accessCheck')->willReturn($query);
    $query->method('condition')->willReturn($query);
    $query->method('execute')->willReturn([]);

    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('crm_opportunity')
      ->willReturn($storage);

    $result = $this->service->getAvgDealSize(1);
    $this->assertEquals(0.0, $result);
  }

  /**
   * Tests sales cycle avg sin datos.
   */
  public function testGetSalesCycleAvgEmpty(): void {
    $storage = $this->createMock(\Drupal\Core\Entity\EntityStorageInterface::class);
    $query = $this->createMock(\Drupal\Core\Entity\Query\QueryInterface::class);

    $query->method('accessCheck')->willReturn($query);
    $query->method('condition')->willReturn($query);
    $query->method('execute')->willReturn([]);

    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('crm_opportunity')
      ->willReturn($storage);

    $result = $this->service->getSalesCycleAvg(1);
    $this->assertEquals(0.0, $result);
  }

}

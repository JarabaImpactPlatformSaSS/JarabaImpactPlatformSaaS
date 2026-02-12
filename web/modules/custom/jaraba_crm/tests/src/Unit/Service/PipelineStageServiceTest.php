<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_crm\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_crm\Entity\PipelineStage;
use Drupal\jaraba_crm\Service\PipelineStageService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para PipelineStageService.
 *
 * @covers \Drupal\jaraba_crm\Service\PipelineStageService
 * @group jaraba_crm
 */
class PipelineStageServiceTest extends UnitTestCase {

  protected PipelineStageService $service;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected EntityStorageInterface $storage;
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->storage = $this->createMock(EntityStorageInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->with('crm_pipeline_stage')
      ->willReturn($this->storage);

    $this->service = new PipelineStageService(
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * Tests obtener etapas de un tenant sin resultados.
   */
  public function testGetStagesForTenantEmpty(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturn($query);
    $query->method('condition')->willReturn($query);
    $query->method('sort')->willReturn($query);
    $query->method('execute')->willReturn([]);

    $this->storage->method('getQuery')->willReturn($query);

    $result = $this->service->getStagesForTenant(1);
    $this->assertEmpty($result);
  }

  /**
   * Tests obtener etapas de un tenant con resultados.
   */
  public function testGetStagesForTenantWithResults(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturn($query);
    $query->method('condition')->willReturn($query);
    $query->method('sort')->willReturn($query);
    $query->method('execute')->willReturn([1, 2]);

    $stage1 = $this->createMock(PipelineStage::class);
    $stage2 = $this->createMock(PipelineStage::class);

    $this->storage->method('getQuery')->willReturn($query);
    $this->storage->method('loadMultiple')
      ->with([1, 2])
      ->willReturn([$stage1, $stage2]);

    $result = $this->service->getStagesForTenant(1);
    $this->assertCount(2, $result);
  }

  /**
   * Tests obtener etapa por ID cuando existe.
   */
  public function testGetStageByIdExists(): void {
    $stage = $this->createMock(PipelineStage::class);
    $this->storage->method('load')
      ->with(5)
      ->willReturn($stage);

    $result = $this->service->getStageById(5);
    $this->assertInstanceOf(PipelineStage::class, $result);
  }

  /**
   * Tests obtener etapa por ID cuando no existe.
   */
  public function testGetStageByIdNotFound(): void {
    $this->storage->method('load')
      ->with(999)
      ->willReturn(NULL);

    $result = $this->service->getStageById(999);
    $this->assertNull($result);
  }

  /**
   * Tests contar etapas.
   */
  public function testCount(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturn($query);
    $query->method('condition')->willReturn($query);
    $query->method('count')->willReturn($query);
    $query->method('execute')->willReturn(5);

    $this->storage->method('getQuery')->willReturn($query);

    $result = $this->service->count(1);
    $this->assertEquals(5, $result);
  }

}

<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ads\Unit\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_ads\Service\CampaignManagerService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para CampaignManagerService.
 *
 * Verifica la logica de creacion, actualizacion de estado,
 * sincronizacion de metricas y listado de campanas publicitarias.
 *
 * @covers \Drupal\jaraba_ads\Service\CampaignManagerService
 * @group jaraba_ads
 */
class CampaignManagerServiceTest extends UnitTestCase {

  protected CampaignManagerService $service;
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

    $this->service = new CampaignManagerService(
      $this->entityTypeManager,
      $this->tenantContext,
      $this->logger,
    );
  }

  /**
   * Tests que createCampaign crea una campana correctamente.
   */
  public function testCreateCampaignSuccess(): void {
    $campaign = $this->createMock(ContentEntityInterface::class);
    $campaign->method('id')->willReturn(1);
    $campaign->method('save')->willReturn(1);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('create')->willReturn($campaign);

    $this->entityTypeManager->method('getStorage')
      ->with('ad_campaign')
      ->willReturn($storage);

    $result = $this->service->createCampaign([
      'name' => 'Test Campaign',
      'platform' => 'meta',
      'budget' => 1000.00,
      'tenant_id' => 1,
    ]);

    $this->assertNotNull($result);
  }

  /**
   * Tests que updateStatus devuelve NULL con campana inexistente.
   */
  public function testUpdateStatusCampaignNotFound(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(999)->willReturn(NULL);

    $this->entityTypeManager->method('getStorage')
      ->with('ad_campaign')
      ->willReturn($storage);

    $result = $this->service->updateStatus(999, 'paused');

    $this->assertNull($result);
  }

  /**
   * Tests que updateStatus actualiza el estado correctamente.
   */
  public function testUpdateStatusSuccess(): void {
    $statusField = new \stdClass();
    $statusField->value = 'active';

    $campaign = $this->createMock(ContentEntityInterface::class);
    $campaign->method('id')->willReturn(1);
    $campaign->method('get')->willReturnMap([
      ['status', $statusField],
    ]);
    $campaign->expects($this->once())
      ->method('set')
      ->with('status', 'paused');
    $campaign->method('save')->willReturn(1);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(1)->willReturn($campaign);

    $this->entityTypeManager->method('getStorage')
      ->with('ad_campaign')
      ->willReturn($storage);

    $result = $this->service->updateStatus(1, 'paused');

    $this->assertNotNull($result);
  }

  /**
   * Tests que syncMetrics devuelve NULL con campana inexistente.
   */
  public function testSyncMetricsCampaignNotFound(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(999)->willReturn(NULL);

    $this->entityTypeManager->method('getStorage')
      ->with('ad_campaign')
      ->willReturn($storage);

    $result = $this->service->syncMetrics(999, []);

    $this->assertNull($result);
  }

  /**
   * Tests que getTenantCampaigns devuelve array vacio sin campanas.
   */
  public function testGetTenantCampaignsEmpty(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('ad_campaign')
      ->willReturn($storage);

    $result = $this->service->getTenantCampaigns(1);

    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

}

<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ads\Unit\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_ads\Service\AdsAudienceSyncService;
use Drupal\jaraba_crm\Service\ContactService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para AdsAudienceSyncService.
 *
 * @covers \Drupal\jaraba_ads\Service\AdsAudienceSyncService
 * @group jaraba_ads
 */
class AdsAudienceSyncServiceTest extends UnitTestCase {

  protected AdsAudienceSyncService $service;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected ContactService $contactService;
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->contactService = $this->createMock(ContactService::class);
    $this->contactService->method('list')->willReturn([]);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new AdsAudienceSyncService(
      $this->entityTypeManager,
      $this->contactService,
      $this->logger,
    );
  }

  /**
   * Tests sincronizar audiencia inexistente.
   */
  public function testSyncAudienceNotFound(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(999)->willReturn(NULL);

    $this->entityTypeManager->method('getStorage')
      ->with('ads_audience_sync')
      ->willReturn($storage);

    $result = $this->service->syncAudience(999);
    $this->assertFalse($result['success']);
    $this->assertEquals(0, $result['member_count']);
  }

  /**
   * Tests sincronizar audiencia existente.
   */
  public function testSyncAudienceSuccess(): void {
    $platformField = new \stdClass();
    $platformField->value = 'meta';
    $sourceTypeField = new \stdClass();
    $sourceTypeField->value = 'crm_contacts';
    $memberCountField = new \stdClass();
    $memberCountField->value = 150;
    $tenantField = new \stdClass();
    $tenantField->target_id = 1;

    $audience = $this->createMock(ContentEntityInterface::class);
    $audience->method('get')->willReturnMap([
      ['platform', $platformField],
      ['source_type', $sourceTypeField],
      ['member_count', $memberCountField],
      ['tenant_id', $tenantField],
    ]);
    $audience->method('label')->willReturn('Test Audience');
    $audience->expects($this->atLeastOnce())->method('save');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(1)->willReturn($audience);

    $this->entityTypeManager->method('getStorage')
      ->with('ads_audience_sync')
      ->willReturn($storage);

    $result = $this->service->syncAudience(1);
    $this->assertTrue($result['success']);
  }

  /**
   * Tests obtener audiencias para tenant sin resultados.
   */
  public function testGetAudiencesForTenantEmpty(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('ads_audience_sync')
      ->willReturn($storage);

    $result = $this->service->getAudiencesForTenant(1);
    $this->assertEmpty($result);
  }

  /**
   * Tests eliminar audiencia inexistente.
   */
  public function testDeleteSyncedAudienceNotFound(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(999)->willReturn(NULL);

    $this->entityTypeManager->method('getStorage')
      ->with('ads_audience_sync')
      ->willReturn($storage);

    $result = $this->service->deleteSyncedAudience(999);
    $this->assertFalse($result);
  }

  /**
   * Tests eliminar audiencia existente.
   */
  public function testDeleteSyncedAudienceSuccess(): void {
    $audience = $this->createMock(ContentEntityInterface::class);
    $audience->method('label')->willReturn('Audience to Delete');
    $audience->expects($this->once())->method('delete');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(1)->willReturn($audience);

    $this->entityTypeManager->method('getStorage')
      ->with('ads_audience_sync')
      ->willReturn($storage);

    $result = $this->service->deleteSyncedAudience(1);
    $this->assertTrue($result);
  }

}

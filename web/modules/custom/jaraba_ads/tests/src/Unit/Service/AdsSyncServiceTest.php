<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ads\Unit\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_ads\Service\AdsSyncService;
use Drupal\jaraba_ads\Service\GoogleAdsClientService;
use Drupal\jaraba_ads\Service\MetaAdsClientService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para AdsSyncService.
 *
 * Verifica la logica de sincronizacion de cuentas publicitarias,
 * metricas de campanas y estado de la ultima sincronizacion.
 *
 * @covers \Drupal\jaraba_ads\Service\AdsSyncService
 * @group jaraba_ads
 */
class AdsSyncServiceTest extends UnitTestCase {

  protected AdsSyncService $service;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected MetaAdsClientService $metaAdsClient;
  protected GoogleAdsClientService $googleAdsClient;
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->metaAdsClient = $this->createMock(MetaAdsClientService::class);
    $this->googleAdsClient = $this->createMock(GoogleAdsClientService::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new AdsSyncService(
      $this->entityTypeManager,
      $this->metaAdsClient,
      $this->googleAdsClient,
      $this->logger,
    );
  }

  /**
   * Tests que syncAllAccounts devuelve estructura correcta sin cuentas.
   */
  public function testSyncAllAccountsEmptyTenant(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('ads_account')
      ->willReturn($storage);

    $result = $this->service->syncAllAccounts(1);

    $this->assertIsArray($result);
    $this->assertArrayHasKey('total', $result);
    $this->assertEquals(0, $result['total']);
  }

  /**
   * Tests que syncAccount devuelve resultado para cuenta inexistente.
   */
  public function testSyncAccountNotFound(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(999)->willReturn(NULL);

    $this->entityTypeManager->method('getStorage')
      ->with('ads_account')
      ->willReturn($storage);

    $result = $this->service->syncAccount(999);

    $this->assertIsArray($result);
    $this->assertArrayHasKey('success', $result);
    $this->assertFalse($result['success']);
  }

  /**
   * Tests que syncAccount procesa cuenta Meta correctamente.
   */
  public function testSyncAccountMetaSuccess(): void {
    $platformField = new \stdClass();
    $platformField->value = 'meta';
    $tokenField = new \stdClass();
    $tokenField->value = 'valid_token';
    $externalField = new \stdClass();
    $externalField->value = 'ext_meta_123';

    $account = $this->createMock(ContentEntityInterface::class);
    $account->method('id')->willReturn(1);
    $account->method('get')->willReturnMap([
      ['platform', $platformField],
      ['access_token', $tokenField],
      ['external_account_id', $externalField],
    ]);
    $account->method('set')->willReturnSelf();
    $account->method('save')->willReturn(1);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(1)->willReturn($account);

    $this->entityTypeManager->method('getStorage')
      ->with('ads_account')
      ->willReturn($storage);

    $this->metaAdsClient->method('getCampaigns')
      ->willReturn([
        ['id' => 'camp_1', 'name' => 'Campaign 1'],
      ]);

    $result = $this->service->syncAccount(1);

    $this->assertIsArray($result);
    $this->assertArrayHasKey('success', $result);
  }

  /**
   * Tests que syncCampaignMetrics devuelve resultado para cuenta inexistente.
   */
  public function testSyncCampaignMetricsAccountNotFound(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(999)->willReturn(NULL);

    $this->entityTypeManager->method('getStorage')
      ->with('ads_account')
      ->willReturn($storage);

    $result = $this->service->syncCampaignMetrics(999, '2026-01-15');

    $this->assertIsArray($result);
  }

  /**
   * Tests que getLastSyncStatus devuelve estructura correcta sin datos.
   */
  public function testGetLastSyncStatusEmpty(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('ads_account')
      ->willReturn($storage);

    $result = $this->service->getLastSyncStatus(1);

    $this->assertIsArray($result);
  }

}

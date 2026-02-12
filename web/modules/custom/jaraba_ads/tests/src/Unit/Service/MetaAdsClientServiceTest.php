<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ads\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_ads\Service\MetaAdsClientService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para MetaAdsClientService.
 *
 * @covers \Drupal\jaraba_ads\Service\MetaAdsClientService
 * @group jaraba_ads
 */
class MetaAdsClientServiceTest extends UnitTestCase {

  protected MetaAdsClientService $service;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected ConfigFactoryInterface $configFactory;
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturnMap([
      ['meta_app_id', 'test_app_id'],
      ['meta_app_secret', 'test_app_secret'],
    ]);

    $this->configFactory->method('get')
      ->with('jaraba_ads.settings')
      ->willReturn($config);

    $this->service = new MetaAdsClientService(
      $this->entityTypeManager,
      $this->configFactory,
      $this->logger,
    );
  }

  /**
   * Tests obtener campanas con cuenta inexistente.
   */
  public function testGetCampaignsNonExistentAccount(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(999)->willReturn(NULL);

    $this->entityTypeManager->method('getStorage')
      ->with('ads_account')
      ->willReturn($storage);

    $result = $this->service->getCampaigns(999);
    $this->assertEmpty($result);
  }

  /**
   * Tests obtener campanas con cuenta sin token.
   */
  public function testGetCampaignsWithoutToken(): void {
    $account = $this->createMock(ContentEntityInterface::class);
    $tokenField = new \stdClass();
    $tokenField->value = NULL;
    $externalField = new \stdClass();
    $externalField->value = 'ext_123';

    $account->method('get')->willReturnMap([
      ['access_token', $tokenField],
      ['external_account_id', $externalField],
    ]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(1)->willReturn($account);

    $this->entityTypeManager->method('getStorage')
      ->with('ads_account')
      ->willReturn($storage);

    $result = $this->service->getCampaigns(1);
    $this->assertEmpty($result);
  }

  /**
   * Tests obtener metricas devuelve estructura correcta.
   */
  public function testGetCampaignMetricsReturnsStructure(): void {
    $result = $this->service->getCampaignMetrics('camp_123', '2026-01-01', '2026-01-31');
    $this->assertArrayHasKey('impressions', $result);
    $this->assertArrayHasKey('clicks', $result);
    $this->assertArrayHasKey('conversions', $result);
    $this->assertArrayHasKey('spend', $result);
    $this->assertArrayHasKey('ctr', $result);
    $this->assertArrayHasKey('cpc', $result);
  }

  /**
   * Tests crear audiencia custom con cuenta inexistente.
   */
  public function testCreateCustomAudienceAccountNotFound(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(999)->willReturn(NULL);

    $this->entityTypeManager->method('getStorage')
      ->with('ads_account')
      ->willReturn($storage);

    $result = $this->service->createCustomAudience(999, 'Test Audience', ['a@test.com']);
    $this->assertFalse($result['success']);
  }

  /**
   * Tests crear audiencia custom con emails validos.
   */
  public function testCreateCustomAudienceWithEmails(): void {
    $account = $this->createMock(ContentEntityInterface::class);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(1)->willReturn($account);

    $this->entityTypeManager->method('getStorage')
      ->with('ads_account')
      ->willReturn($storage);

    $emails = ['user1@test.com', 'user2@test.com', 'user3@test.com'];
    $result = $this->service->createCustomAudience(1, 'CRM Audience', $emails);

    $this->assertTrue($result['success']);
    $this->assertEquals(3, $result['member_count']);
  }

  /**
   * Tests subir conversiones offline con cuenta inexistente.
   */
  public function testUploadOfflineConversionsAccountNotFound(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(999)->willReturn(NULL);

    $this->entityTypeManager->method('getStorage')
      ->with('ads_account')
      ->willReturn($storage);

    $result = $this->service->uploadOfflineConversions(999, []);
    $this->assertFalse($result['success']);
    $this->assertEquals(0, $result['uploaded_count']);
  }

  /**
   * Tests renovar token con cuenta sin refresh_token.
   */
  public function testRefreshAccessTokenWithoutRefreshToken(): void {
    $account = $this->createMock(ContentEntityInterface::class);
    $refreshField = new \stdClass();
    $refreshField->value = NULL;

    $account->method('get')->willReturnMap([
      ['refresh_token', $refreshField],
    ]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(1)->willReturn($account);

    $this->entityTypeManager->method('getStorage')
      ->with('ads_account')
      ->willReturn($storage);

    $result = $this->service->refreshAccessToken(1);
    $this->assertFalse($result);
  }

}

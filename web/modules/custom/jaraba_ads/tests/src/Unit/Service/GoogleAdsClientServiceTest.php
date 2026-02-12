<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ads\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_ads\Service\GoogleAdsClientService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para GoogleAdsClientService.
 *
 * Verifica la logica de comunicacion con la API de Google Ads,
 * incluyendo listado de campanas, metricas, audiencias Customer Match,
 * subida de conversiones offline y renovacion de tokens.
 *
 * @covers \Drupal\jaraba_ads\Service\GoogleAdsClientService
 * @group jaraba_ads
 */
class GoogleAdsClientServiceTest extends UnitTestCase {

  protected GoogleAdsClientService $service;
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
      ['google_developer_token', 'test_dev_token'],
      ['google_client_id', 'test_client_id'],
      ['google_client_secret', 'test_client_secret'],
    ]);

    $this->configFactory->method('get')
      ->with('jaraba_ads.settings')
      ->willReturn($config);

    $this->service = new GoogleAdsClientService(
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
   * Tests obtener campanas con cuenta sin token de acceso.
   */
  public function testGetCampaignsWithoutAccessToken(): void {
    $account = $this->createMock(ContentEntityInterface::class);
    $tokenField = new \stdClass();
    $tokenField->value = NULL;
    $externalField = new \stdClass();
    $externalField->value = 'ext_456';

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
   * Tests que getCampaignMetrics devuelve estructura correcta.
   */
  public function testGetCampaignMetricsReturnsStructure(): void {
    $result = $this->service->getCampaignMetrics('camp_456', '2026-01-01', '2026-01-31');
    $this->assertArrayHasKey('impressions', $result);
    $this->assertArrayHasKey('clicks', $result);
    $this->assertArrayHasKey('conversions', $result);
    $this->assertArrayHasKey('spend', $result);
    $this->assertArrayHasKey('ctr', $result);
    $this->assertArrayHasKey('cpc', $result);
  }

  /**
   * Tests crear audiencia Customer Match con cuenta inexistente.
   */
  public function testCreateCustomerMatchAudienceAccountNotFound(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(999)->willReturn(NULL);

    $this->entityTypeManager->method('getStorage')
      ->with('ads_account')
      ->willReturn($storage);

    $result = $this->service->createCustomerMatchAudience(999, 'Test Audience', ['a@test.com']);
    $this->assertArrayHasKey('success', $result);
    $this->assertFalse($result['success']);
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

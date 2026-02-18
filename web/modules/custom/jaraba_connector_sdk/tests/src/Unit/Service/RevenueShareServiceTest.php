<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_connector_sdk\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_connector_sdk\Service\RevenueShareService;
use Drupal\jaraba_integrations\Entity\Connector;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Tests for RevenueShareService.
 *
 * @group jaraba_connector_sdk
 * @coversDefaultClass \Drupal\jaraba_connector_sdk\Service\RevenueShareService
 */
class RevenueShareServiceTest extends UnitTestCase {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected TenantContextService $tenantContext;
  protected ClientInterface $httpClient;
  protected ConfigFactoryInterface $configFactory;
  protected LoggerInterface $logger;
  protected RevenueShareService $service;
  protected EntityStorageInterface $storage;
  protected StateInterface $state;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->tenantContext = $this->createMock(TenantContextService::class);
    $this->httpClient = $this->createMock(ClientInterface::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->storage = $this->createMock(EntityStorageInterface::class);
    $this->state = $this->createMock(StateInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->with('connector')
      ->willReturn($this->storage);

    // Set up config mock with tier data.
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')
      ->willReturnCallback(function (string $key) {
        $data = [
          'revenue_tiers' => [
            'standard' => [
              'developer_pct' => 70,
              'platform_pct' => 30,
              'requirement' => 'Certified connector',
            ],
            'premium' => [
              'developer_pct' => 80,
              'platform_pct' => 20,
              'requirement' => 'Rating > 4.5 and 100+ installs',
            ],
            'strategic' => [
              'developer_pct' => 85,
              'platform_pct' => 15,
              'requirement' => 'Partnership agreement',
            ],
          ],
          'stripe_connect_enabled' => FALSE,
        ];
        return $data[$key] ?? NULL;
      });

    $this->configFactory->method('get')
      ->with('jaraba_connector_sdk.settings')
      ->willReturn($config);

    // Mock container for \Drupal::state().
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    $container->set('state', $this->state);
    \Drupal::setContainer($container);

    $this->service = new RevenueShareService(
      $this->entityTypeManager,
      $this->tenantContext,
      $this->httpClient,
      $this->configFactory,
      $this->logger,
    );
  }

  /**
   * Tests that a connector with no special qualifications gets standard tier.
   *
   * @covers ::calculateShare
   */
  public function testCalculateShareStandardTier(): void {
    $connector = $this->createConnectorMock(0, 3.0);
    $this->storage->method('load')->with(1)->willReturn($connector);

    // No ratings stored.
    $this->state->method('get')
      ->with('jaraba_connector_sdk.rating.1', [])
      ->willReturn([]);

    $result = $this->service->calculateShare(1);

    $this->assertEquals(RevenueShareService::TIER_STANDARD, $result['tier']);
    $this->assertEquals(70, $result['developer_pct']);
    $this->assertEquals(30, $result['platform_pct']);
  }

  /**
   * Tests that a connector with high rating and installs gets premium tier.
   *
   * @covers ::calculateShare
   */
  public function testCalculateSharePremiumTier(): void {
    $connector = $this->createConnectorMock(150, 4.8);
    $this->storage->method('load')->with(2)->willReturn($connector);

    // Ratings that average > 4.5.
    $ratings = [
      1 => ['rating' => 5, 'review' => NULL, 'timestamp' => time()],
      2 => ['rating' => 5, 'review' => NULL, 'timestamp' => time()],
      3 => ['rating' => 4, 'review' => NULL, 'timestamp' => time()],
      4 => ['rating' => 5, 'review' => NULL, 'timestamp' => time()],
    ];
    $this->state->method('get')
      ->with('jaraba_connector_sdk.rating.2', [])
      ->willReturn($ratings);

    $result = $this->service->calculateShare(2);

    $this->assertEquals(RevenueShareService::TIER_PREMIUM, $result['tier']);
    $this->assertEquals(80, $result['developer_pct']);
    $this->assertEquals(20, $result['platform_pct']);
  }

  /**
   * Tests that strategic tier defaults to standard without manual override.
   *
   * Strategic tier requires a partnership agreement (manual flag) which
   * cannot be automatically determined from ratings/installs alone.
   *
   * @covers ::calculateShare
   */
  public function testCalculateShareStrategicTierFallsToStandard(): void {
    // Even with excellent metrics, strategic requires manual partnership.
    $connector = $this->createConnectorMock(500, 4.9);
    $this->storage->method('load')->with(3)->willReturn($connector);

    $ratings = [
      1 => ['rating' => 5, 'review' => NULL, 'timestamp' => time()],
      2 => ['rating' => 5, 'review' => NULL, 'timestamp' => time()],
    ];
    $this->state->method('get')
      ->with('jaraba_connector_sdk.rating.3', [])
      ->willReturn($ratings);

    $result = $this->service->calculateShare(3);

    // Premium (not strategic) because strategic requires partnership agreement.
    $this->assertEquals(RevenueShareService::TIER_PREMIUM, $result['tier']);
    $this->assertEquals(80, $result['developer_pct']);
    $this->assertEquals(20, $result['platform_pct']);
    $this->assertEquals(500, $result['install_count']);
  }

  /**
   * Creates a mock Connector entity with given install count.
   *
   * @param int $installCount
   *   The install count.
   * @param float $rating
   *   The average rating (not used directly, stored in state).
   *
   * @return \Drupal\jaraba_integrations\Entity\Connector
   *   The mock connector.
   */
  protected function createConnectorMock(int $installCount, float $rating): Connector {
    $connector = $this->createMock(Connector::class);

    $installCountField = new \stdClass();
    $installCountField->value = $installCount;

    $connector->method('id')->willReturn(1);
    $connector->method('get')
      ->willReturnCallback(function (string $field) use ($installCountField) {
        if ($field === 'install_count') {
          return $installCountField;
        }
        return (object) ['value' => ''];
      });

    return $connector;
  }

}

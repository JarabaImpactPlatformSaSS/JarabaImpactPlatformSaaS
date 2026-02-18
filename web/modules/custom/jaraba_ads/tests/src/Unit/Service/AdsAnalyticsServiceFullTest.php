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
 * Comprehensive tests for AdsAnalyticsService.
 *
 * Covers tenant ad metrics aggregation, campaign performance retrieval,
 * platform spend distribution, edge cases, and averages computation.
 *
 * @coversDefaultClass \Drupal\jaraba_ads\Service\AdsAnalyticsService
 * @group jaraba_ads
 */
class AdsAnalyticsServiceFullTest extends UnitTestCase {

  /**
   * The service under test.
   */
  protected AdsAnalyticsService $service;

  /**
   * Mock entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock tenant context.
   */
  protected object $tenantContext;

  /**
   * Mock logger.
   */
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
   * Creates a mock AdCampaign entity with configurable field values.
   *
   * @param array $fields
   *   Map of field_name => value.
   *
   * @return \Drupal\jaraba_ads\Entity\AdCampaign|\PHPUnit\Framework\MockObject\MockObject
   *   Mocked campaign entity.
   */
  protected function createMockCampaign(array $fields): AdCampaign {
    $campaign = $this->createMock(AdCampaign::class);

    $campaign->method('id')->willReturn($fields['id'] ?? 1);
    $campaign->method('label')->willReturn($fields['label'] ?? 'Test Campaign');

    if (isset($fields['budget_utilization'])) {
      $campaign->method('getBudgetUtilization')->willReturn($fields['budget_utilization']);
    }

    $campaign->method('get')->willReturnCallback(function (string $fieldName) use ($fields): object {
      $fieldItem = new \stdClass();
      $fieldItem->value = $fields[$fieldName] ?? NULL;
      return $fieldItem;
    });

    return $campaign;
  }

  /**
   * Helper to set up storage mock with query returning given campaign IDs.
   *
   * @param array $campaignIds
   *   Entity IDs returned by the query.
   * @param \Drupal\jaraba_ads\Entity\AdCampaign[] $campaigns
   *   Entities returned by loadMultiple.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   *   The configured mock storage.
   */
  protected function setUpStorageWithQuery(array $campaignIds, array $campaigns): EntityStorageInterface {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn($campaignIds);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    if (!empty($campaignIds)) {
      $storage->method('loadMultiple')->with($campaignIds)->willReturn($campaigns);
    }

    $this->entityTypeManager->method('getStorage')
      ->with('ad_campaign')
      ->willReturn($storage);

    return $storage;
  }

  /**
   * @covers ::getTenantAdMetrics
   */
  public function testGetTenantAdMetricsReturnsZeroesWhenNoCampaigns(): void {
    $this->setUpStorageWithQuery([], []);

    $result = $this->service->getTenantAdMetrics(1);

    $this->assertIsArray($result);
    $this->assertEquals(0, $result['total_campaigns']);
    $this->assertEquals(0, $result['active_campaigns']);
    $this->assertEquals(0.0, $result['total_spend']);
    $this->assertEquals(0, $result['total_impressions']);
    $this->assertEquals(0, $result['total_clicks']);
    $this->assertEquals(0, $result['total_conversions']);
    $this->assertEquals(0.0, $result['avg_ctr']);
    $this->assertEquals(0.0, $result['avg_cpc']);
    $this->assertEquals(0.0, $result['avg_roas']);
    $this->assertEquals(0.0, $result['total_budget']);
  }

  /**
   * @covers ::getTenantAdMetrics
   */
  public function testGetTenantAdMetricsAggregatesMultipleCampaigns(): void {
    $campaign1 = $this->createMockCampaign([
      'id' => 1,
      'status' => 'active',
      'spend_to_date' => 100.50,
      'impressions' => 5000,
      'clicks' => 250,
      'conversions' => 25,
      'budget_total' => 500.00,
      'ctr' => 5.0,
      'cpc' => 0.40,
      'roas' => 3.5,
      'platform' => 'google_ads',
    ]);

    $campaign2 = $this->createMockCampaign([
      'id' => 2,
      'status' => 'paused',
      'spend_to_date' => 200.00,
      'impressions' => 8000,
      'clicks' => 400,
      'conversions' => 40,
      'budget_total' => 1000.00,
      'ctr' => 5.0,
      'cpc' => 0.50,
      'roas' => 4.0,
      'platform' => 'meta_ads',
    ]);

    $this->setUpStorageWithQuery([1, 2], [1 => $campaign1, 2 => $campaign2]);

    $result = $this->service->getTenantAdMetrics(1);

    $this->assertEquals(2, $result['total_campaigns']);
    $this->assertEquals(1, $result['active_campaigns']);
    $this->assertEquals(300.50, $result['total_spend']);
    $this->assertEquals(13000, $result['total_impressions']);
    $this->assertEquals(650, $result['total_clicks']);
    $this->assertEquals(65, $result['total_conversions']);
    $this->assertEquals(1500.00, $result['total_budget']);
    // avg_ctr = (5.0 + 5.0) / 2 = 5.0.
    $this->assertEquals(5.0, $result['avg_ctr']);
    // avg_cpc = (0.40 + 0.50) / 2 = 0.45.
    $this->assertEquals(0.45, $result['avg_cpc']);
    // avg_roas = (3.5 + 4.0) / 2 = 3.75.
    $this->assertEquals(3.75, $result['avg_roas']);
  }

  /**
   * @covers ::getTenantAdMetrics
   */
  public function testGetTenantAdMetricsSkipsZeroCtrCpcRoasInAverages(): void {
    $campaign1 = $this->createMockCampaign([
      'id' => 1,
      'status' => 'active',
      'spend_to_date' => 50.0,
      'impressions' => 1000,
      'clicks' => 100,
      'conversions' => 10,
      'budget_total' => 200.00,
      'ctr' => 10.0,
      'cpc' => 0.50,
      'roas' => 2.0,
      'platform' => 'google_ads',
    ]);

    // Campaign with zero CTR/CPC/ROAS (should be excluded from averages).
    $campaign2 = $this->createMockCampaign([
      'id' => 2,
      'status' => 'draft',
      'spend_to_date' => 0.0,
      'impressions' => 0,
      'clicks' => 0,
      'conversions' => 0,
      'budget_total' => 100.00,
      'ctr' => 0,
      'cpc' => 0,
      'roas' => 0,
      'platform' => 'meta_ads',
    ]);

    $this->setUpStorageWithQuery([1, 2], [1 => $campaign1, 2 => $campaign2]);

    $result = $this->service->getTenantAdMetrics(1);

    $this->assertEquals(2, $result['total_campaigns']);
    $this->assertEquals(1, $result['active_campaigns']);
    // Only campaign1 has non-zero values, so average = campaign1's values.
    $this->assertEquals(10.0, $result['avg_ctr']);
    $this->assertEquals(0.50, $result['avg_cpc']);
    $this->assertEquals(2.0, $result['avg_roas']);
  }

  /**
   * @covers ::getTenantAdMetrics
   */
  public function testGetTenantAdMetricsHandlesNullFieldValues(): void {
    $campaign = $this->createMockCampaign([
      'id' => 1,
      'status' => 'active',
      'spend_to_date' => NULL,
      'impressions' => NULL,
      'clicks' => NULL,
      'conversions' => NULL,
      'budget_total' => NULL,
      'ctr' => NULL,
      'cpc' => NULL,
      'roas' => NULL,
      'platform' => 'google_ads',
    ]);

    $this->setUpStorageWithQuery([1], [1 => $campaign]);

    $result = $this->service->getTenantAdMetrics(1);

    $this->assertEquals(1, $result['total_campaigns']);
    $this->assertEquals(1, $result['active_campaigns']);
    $this->assertEquals(0.0, $result['total_spend']);
    $this->assertEquals(0, $result['total_impressions']);
    $this->assertEquals(0, $result['total_clicks']);
    $this->assertEquals(0, $result['total_conversions']);
  }

  /**
   * @covers ::getCampaignPerformance
   */
  public function testGetCampaignPerformanceReturnsEmptyForNonexistentCampaign(): void {
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
   * @covers ::getCampaignPerformance
   */
  public function testGetCampaignPerformanceReturnsFullMetrics(): void {
    $campaign = $this->createMockCampaign([
      'id' => 42,
      'label' => 'Summer Sale',
      'platform' => 'meta_ads',
      'status' => 'active',
      'budget_total' => 5000.00,
      'spend_to_date' => 2500.00,
      'budget_utilization' => 50.0,
      'impressions' => 100000,
      'clicks' => 5000,
      'conversions' => 500,
      'ctr' => 5.0,
      'cpc' => 0.50,
      'roas' => 8.0,
    ]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(42)->willReturn($campaign);

    $this->entityTypeManager->method('getStorage')
      ->with('ad_campaign')
      ->willReturn($storage);

    $result = $this->service->getCampaignPerformance(42);

    $this->assertNotEmpty($result);
    $this->assertEquals(42, $result['id']);
    $this->assertEquals('Summer Sale', $result['label']);
    $this->assertEquals('meta_ads', $result['platform']);
    $this->assertEquals('active', $result['status']);
    $this->assertEquals(5000.00, $result['budget_total']);
    $this->assertEquals(2500.00, $result['spend_to_date']);
    $this->assertEquals(50.0, $result['budget_utilization']);
    $this->assertEquals(100000, $result['impressions']);
    $this->assertEquals(5000, $result['clicks']);
    $this->assertEquals(500, $result['conversions']);
    $this->assertEquals(5.0, $result['ctr']);
    $this->assertEquals(0.50, $result['cpc']);
    $this->assertEquals(8.0, $result['roas']);
  }

  /**
   * @covers ::getAdSpendByPlatform
   */
  public function testGetAdSpendByPlatformReturnsDefaultStructureWhenEmpty(): void {
    $this->setUpStorageWithQuery([], []);

    $result = $this->service->getAdSpendByPlatform(1);

    $this->assertIsArray($result);
    $this->assertArrayHasKey('google_ads', $result);
    $this->assertArrayHasKey('meta_ads', $result);
    $this->assertArrayHasKey('linkedin_ads', $result);
    $this->assertArrayHasKey('tiktok_ads', $result);

    foreach ($result as $platformData) {
      $this->assertEquals(0.0, $platformData['spend']);
      $this->assertEquals(0, $platformData['campaigns']);
      $this->assertEquals(0, $platformData['impressions']);
      $this->assertEquals(0, $platformData['clicks']);
      $this->assertEquals(0, $platformData['conversions']);
    }
  }

  /**
   * @covers ::getAdSpendByPlatform
   */
  public function testGetAdSpendByPlatformAggregatesByPlatform(): void {
    $campaign1 = $this->createMockCampaign([
      'id' => 1,
      'platform' => 'google_ads',
      'spend_to_date' => 500.00,
      'impressions' => 10000,
      'clicks' => 500,
      'conversions' => 50,
    ]);

    $campaign2 = $this->createMockCampaign([
      'id' => 2,
      'platform' => 'google_ads',
      'spend_to_date' => 300.00,
      'impressions' => 6000,
      'clicks' => 300,
      'conversions' => 30,
    ]);

    $campaign3 = $this->createMockCampaign([
      'id' => 3,
      'platform' => 'meta_ads',
      'spend_to_date' => 1000.00,
      'impressions' => 20000,
      'clicks' => 1000,
      'conversions' => 100,
    ]);

    $this->setUpStorageWithQuery(
      [1, 2, 3],
      [1 => $campaign1, 2 => $campaign2, 3 => $campaign3]
    );

    $result = $this->service->getAdSpendByPlatform(1);

    // Google Ads: 500 + 300 = 800.
    $this->assertEquals(800.00, $result['google_ads']['spend']);
    $this->assertEquals(2, $result['google_ads']['campaigns']);
    $this->assertEquals(16000, $result['google_ads']['impressions']);
    $this->assertEquals(800, $result['google_ads']['clicks']);
    $this->assertEquals(80, $result['google_ads']['conversions']);

    // Meta Ads: only campaign3.
    $this->assertEquals(1000.00, $result['meta_ads']['spend']);
    $this->assertEquals(1, $result['meta_ads']['campaigns']);
    $this->assertEquals(20000, $result['meta_ads']['impressions']);

    // LinkedIn and TikTok untouched.
    $this->assertEquals(0.0, $result['linkedin_ads']['spend']);
    $this->assertEquals(0, $result['tiktok_ads']['campaigns']);
  }

  /**
   * @covers ::getAdSpendByPlatform
   */
  public function testGetAdSpendByPlatformHandlesUnknownPlatform(): void {
    $campaign = $this->createMockCampaign([
      'id' => 1,
      'platform' => 'twitter_ads',
      'spend_to_date' => 250.00,
      'impressions' => 5000,
      'clicks' => 200,
      'conversions' => 20,
    ]);

    $this->setUpStorageWithQuery([1], [1 => $campaign]);

    $result = $this->service->getAdSpendByPlatform(1);

    // Unknown platform should be dynamically added.
    $this->assertArrayHasKey('twitter_ads', $result);
    $this->assertEquals(250.00, $result['twitter_ads']['spend']);
    $this->assertEquals(1, $result['twitter_ads']['campaigns']);
  }

}

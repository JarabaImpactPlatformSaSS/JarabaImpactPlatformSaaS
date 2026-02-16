<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_legal_intelligence\Unit\Service;

use Drupal\ai\AiProviderPluginManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_legal_intelligence\Service\LegalSearchService;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Tests for LegalSearchService.
 *
 * @coversDefaultClass \Drupal\jaraba_legal_intelligence\Service\LegalSearchService
 * @group jaraba_legal_intelligence
 */
class LegalSearchServiceTest extends UnitTestCase {

  /**
   * The service being tested.
   *
   * @var \Drupal\jaraba_legal_intelligence\Service\LegalSearchService
   */
  protected LegalSearchService $service;

  /**
   * Mock AI provider plugin manager.
   *
   * @var \Drupal\ai\AiProviderPluginManager|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $aiProvider;

  /**
   * Mock tenant context service.
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\TenantContextService|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $tenantContext;

  /**
   * Mock entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * Mock HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $httpClient;

  /**
   * Mock config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $configFactory;

  /**
   * Mock logger.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->aiProvider = $this->createMock(AiProviderPluginManager::class);
    $this->tenantContext = $this->createMock(TenantContextService::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->httpClient = $this->createMock(ClientInterface::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturnMap([
      ['qdrant_url', 'http://qdrant:6333'],
      ['qdrant_collection_national', 'legal_intelligence'],
      ['qdrant_collection_eu', 'legal_intelligence_eu'],
      ['plan_limits.starter.searches_per_month', 50],
      ['max_results', 20],
      ['score_threshold', 0.65],
      ['qdrant_api_key', ''],
      ['limits', NULL],
    ]);
    $this->configFactory->method('get')
      ->with('jaraba_legal_intelligence.settings')
      ->willReturn($config);

    // TenantContext returns NULL (no tenant = allow search).
    $this->tenantContext->method('getCurrentTenant')->willReturn(NULL);

    $this->service = new LegalSearchService(
      $this->aiProvider,
      $this->tenantContext,
      $this->entityTypeManager,
      $this->httpClient,
      $this->configFactory,
      $this->logger,
    );
  }

  /**
   * @covers ::search
   */
  public function testSearchWithEmptyQueryReturnsFalse(): void {
    $result = $this->service->search('');

    $this->assertFalse($result['success']);
    $this->assertSame([], $result['results']);
    $this->assertSame(0, $result['total']);
    $this->assertArrayHasKey('error', $result);
  }

  /**
   * @covers ::search
   */
  public function testSearchReturnsExpectedStructure(): void {
    // An empty query returns the standard structure with all expected keys.
    $result = $this->service->search('');

    $this->assertArrayHasKey('success', $result);
    $this->assertArrayHasKey('results', $result);
    $this->assertArrayHasKey('total', $result);
    $this->assertArrayHasKey('facets', $result);
    $this->assertArrayHasKey('error', $result);
  }

  /**
   * @covers ::search
   */
  public function testSearchValidatesQueryNotEmpty(): void {
    // Whitespace-only query should also be treated as empty after trim.
    $result = $this->service->search('   ');

    $this->assertFalse($result['success']);
    $this->assertSame('Query is empty.', $result['error']);
  }

  /**
   * @covers ::search
   */
  public function testReferencePatternDetectionDgt(): void {
    // DGT reference pattern: V0123-24.
    // This triggers lookupByReference which uses entity storage.
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')
      ->with(['external_ref' => 'V0123-24'])
      ->willReturn([]);

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('legal_resolution')
      ->willReturn($storage);

    $result = $this->service->search('V0123-24');

    // Even with no results found, pattern detection triggers exact lookup.
    $this->assertTrue($result['success']);
    $this->assertSame(0, $result['total']);
  }

  /**
   * @covers ::search
   */
  public function testReferencePatternDetectionSts(): void {
    // TS reference pattern: STS 1234/2024.
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')
      ->with(['external_ref' => 'STS 1234/2024'])
      ->willReturn([]);

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('legal_resolution')
      ->willReturn($storage);

    $result = $this->service->search('STS 1234/2024');

    $this->assertTrue($result['success']);
    $this->assertSame(0, $result['total']);
  }

  /**
   * @covers ::search
   */
  public function testReferencePatternDetectionEcli(): void {
    // ECLI reference pattern: ECLI:EU:C:2013:164.
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')
      ->with(['external_ref' => 'ECLI:EU:C:2013:164'])
      ->willReturn([]);

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('legal_resolution')
      ->willReturn($storage);

    $result = $this->service->search('ECLI:EU:C:2013:164');

    $this->assertTrue($result['success']);
    $this->assertSame(0, $result['total']);
  }

}

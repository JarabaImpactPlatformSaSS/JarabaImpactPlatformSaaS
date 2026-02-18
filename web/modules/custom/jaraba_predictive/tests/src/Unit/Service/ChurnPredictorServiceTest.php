<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_predictive\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_predictive\Service\ChurnPredictorService;
use Drupal\jaraba_predictive\Service\FeatureStoreService;
use Drupal\jaraba_predictive\Service\RetentionWorkflowService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for ChurnPredictorService.
 *
 * @coversDefaultClass \Drupal\jaraba_predictive\Service\ChurnPredictorService
 * @group jaraba_predictive
 */
class ChurnPredictorServiceTest extends UnitTestCase {

  /**
   * The service under test.
   *
   * @var \Drupal\jaraba_predictive\Service\ChurnPredictorService
   */
  protected ChurnPredictorService $service;

  /**
   * Mock entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * Mock logger.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * Mock config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $configFactory;

  /**
   * Mock tenant context service.
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\TenantContextService|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $tenantContext;

  /**
   * Mock feature store service.
   *
   * @var \Drupal\jaraba_predictive\Service\FeatureStoreService|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $featureStore;

  /**
   * Mock retention workflow service.
   *
   * @var \Drupal\jaraba_predictive\Service\RetentionWorkflowService|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $retentionWorkflow;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->tenantContext = $this->createMock(TenantContextService::class);
    $this->featureStore = $this->createMock(FeatureStoreService::class);
    $this->retentionWorkflow = $this->createMock(RetentionWorkflowService::class);

    // Default config setup: returns NULL for all config keys so the service
    // falls back to its default weights and model version.
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturn(NULL);
    $this->configFactory->method('get')
      ->with('jaraba_predictive.settings')
      ->willReturn($config);

    $this->service = new ChurnPredictorService(
      $this->entityTypeManager,
      $this->logger,
      $this->configFactory,
      $this->tenantContext,
      $this->featureStore,
      $this->retentionWorkflow,
    );
  }

  /**
   * @covers ::calculateChurnRisk
   */
  public function testCalculateChurnRiskThrowsOnInvalidTenant(): void {
    $groupStorage = $this->createMock(EntityStorageInterface::class);
    $groupStorage->method('load')->with(999)->willReturn(NULL);

    $this->entityTypeManager->method('getStorage')
      ->willReturnMap([
        ['group', $groupStorage],
      ]);

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Tenant con ID 999 no encontrado.');

    $this->service->calculateChurnRisk(999);
  }

  /**
   * @covers ::calculateChurnRisk
   */
  public function testCalculateChurnRiskReturnsValidPrediction(): void {
    $tenantId = 42;

    // --- Group storage: tenant exists ---
    $tenantEntity = $this->createMock(\Drupal\Core\Entity\EntityInterface::class);
    $groupStorage = $this->createMock(EntityStorageInterface::class);
    $groupStorage->method('load')->with($tenantId)->willReturn($tenantEntity);

    // --- User storage for inactivity score ---
    $accessField = (object) ['value' => time() - (5 * 86400)];
    $userEntity = $this->createMock(ContentEntityInterface::class);
    $userEntity->method('get')->with('access')->willReturn($accessField);

    $userQuery = $this->createMock(QueryInterface::class);
    $userQuery->method('accessCheck')->willReturnSelf();
    $userQuery->method('condition')->willReturnSelf();
    $userQuery->method('sort')->willReturnSelf();
    $userQuery->method('range')->willReturnSelf();
    $userQuery->method('execute')->willReturn([10]);

    $userStorage = $this->createMock(EntityStorageInterface::class);
    $userStorage->method('getQuery')->willReturn($userQuery);
    $userStorage->method('loadMultiple')->with([10])->willReturn([10 => $userEntity]);

    // --- Churn prediction storage: for calculateAccuracyConfidence + create ---
    $churnQuery = $this->createMock(QueryInterface::class);
    $churnQuery->method('accessCheck')->willReturnSelf();
    $churnQuery->method('condition')->willReturnSelf();
    $churnQuery->method('count')->willReturnSelf();
    // count query returns 0 (no previous predictions).
    $churnQuery->method('execute')->willReturn(0);

    $predictionEntity = $this->createMock(\Drupal\Core\Entity\EntityInterface::class);
    $predictionEntity->method('save')->willReturn(1);

    $churnStorage = $this->createMock(EntityStorageInterface::class);
    $churnStorage->method('getQuery')->willReturn($churnQuery);
    $churnStorage->method('create')->willReturn($predictionEntity);

    $this->entityTypeManager->method('getStorage')
      ->willReturnMap([
        ['group', $groupStorage],
        ['user', $userStorage],
        ['churn_prediction', $churnStorage],
      ]);

    $result = $this->service->calculateChurnRisk($tenantId);

    $this->assertArrayHasKey('prediction', $result);
    $this->assertArrayHasKey('risk_score', $result);
    $this->assertIsInt($result['risk_score']);
    $this->assertGreaterThanOrEqual(0, $result['risk_score']);
    $this->assertLessThanOrEqual(100, $result['risk_score']);
  }

  /**
   * @covers ::calculateChurnRisk
   */
  public function testCalculateChurnRiskPersistsPrediction(): void {
    $tenantId = 7;

    $tenantEntity = $this->createMock(\Drupal\Core\Entity\EntityInterface::class);
    $groupStorage = $this->createMock(EntityStorageInterface::class);
    $groupStorage->method('load')->with($tenantId)->willReturn($tenantEntity);

    // User storage (inactivity calculation).
    $userQuery = $this->createMock(QueryInterface::class);
    $userQuery->method('accessCheck')->willReturnSelf();
    $userQuery->method('condition')->willReturnSelf();
    $userQuery->method('sort')->willReturnSelf();
    $userQuery->method('range')->willReturnSelf();
    // No active users -- triggers 80.0 inactivity score.
    $userQuery->method('execute')->willReturn([]);

    $userStorage = $this->createMock(EntityStorageInterface::class);
    $userStorage->method('getQuery')->willReturn($userQuery);

    // Churn prediction storage.
    $churnQuery = $this->createMock(QueryInterface::class);
    $churnQuery->method('accessCheck')->willReturnSelf();
    $churnQuery->method('condition')->willReturnSelf();
    $churnQuery->method('count')->willReturnSelf();
    $churnQuery->method('execute')->willReturn(0);

    $predictionEntity = $this->createMock(\Drupal\Core\Entity\EntityInterface::class);
    // save() must be called exactly once.
    $predictionEntity->expects($this->once())->method('save');

    $churnStorage = $this->createMock(EntityStorageInterface::class);
    $churnStorage->method('getQuery')->willReturn($churnQuery);
    $churnStorage->method('create')->willReturn($predictionEntity);

    $this->entityTypeManager->method('getStorage')
      ->willReturnMap([
        ['group', $groupStorage],
        ['user', $userStorage],
        ['churn_prediction', $churnStorage],
      ]);

    $this->service->calculateChurnRisk($tenantId);
  }

  /**
   * @covers ::calculateChurnRisk
   */
  public function testCalculateChurnRiskScoreIsClamped(): void {
    $tenantId = 1;

    $tenantEntity = $this->createMock(\Drupal\Core\Entity\EntityInterface::class);
    $groupStorage = $this->createMock(EntityStorageInterface::class);
    $groupStorage->method('load')->with($tenantId)->willReturn($tenantEntity);

    // User storage: zero last-access produces 90.0 inactivity score.
    $accessField = (object) ['value' => 0];
    $userEntity = $this->createMock(ContentEntityInterface::class);
    $userEntity->method('get')->with('access')->willReturn($accessField);

    $userQuery = $this->createMock(QueryInterface::class);
    $userQuery->method('accessCheck')->willReturnSelf();
    $userQuery->method('condition')->willReturnSelf();
    $userQuery->method('sort')->willReturnSelf();
    $userQuery->method('range')->willReturnSelf();
    $userQuery->method('execute')->willReturn([1]);

    $userStorage = $this->createMock(EntityStorageInterface::class);
    $userStorage->method('getQuery')->willReturn($userQuery);
    $userStorage->method('loadMultiple')->with([1])->willReturn([1 => $userEntity]);

    $churnQuery = $this->createMock(QueryInterface::class);
    $churnQuery->method('accessCheck')->willReturnSelf();
    $churnQuery->method('condition')->willReturnSelf();
    $churnQuery->method('count')->willReturnSelf();
    $churnQuery->method('execute')->willReturn(0);

    $predictionEntity = $this->createMock(\Drupal\Core\Entity\EntityInterface::class);
    $predictionEntity->method('save')->willReturn(1);

    $churnStorage = $this->createMock(EntityStorageInterface::class);
    $churnStorage->method('getQuery')->willReturn($churnQuery);
    $churnStorage->method('create')->willReturn($predictionEntity);

    $this->entityTypeManager->method('getStorage')
      ->willReturnMap([
        ['group', $groupStorage],
        ['user', $userStorage],
        ['churn_prediction', $churnStorage],
      ]);

    $result = $this->service->calculateChurnRisk($tenantId);

    // The risk_score must always be clamped between 0 and 100.
    $this->assertGreaterThanOrEqual(0, $result['risk_score']);
    $this->assertLessThanOrEqual(100, $result['risk_score']);
  }

  /**
   * @covers ::getChurnTrend
   */
  public function testGetChurnTrendReturnsEmptyWhenNoPredictions(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('churn_prediction')
      ->willReturn($storage);

    $trend = $this->service->getChurnTrend(42, 90);

    $this->assertIsArray($trend);
    $this->assertEmpty($trend);
  }

  /**
   * @covers ::getChurnTrend
   */
  public function testGetChurnTrendReturnsSortedData(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([100, 101]);

    // Create two prediction entities.
    $pred1 = $this->createPredictionEntity(100, '2025-12-01T10:00:00', 45, 'medium');
    $pred2 = $this->createPredictionEntity(101, '2025-12-15T10:00:00', 72, 'high');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);
    $storage->method('loadMultiple')
      ->with([100, 101])
      ->willReturn([100 => $pred1, 101 => $pred2]);

    $this->entityTypeManager->method('getStorage')
      ->with('churn_prediction')
      ->willReturn($storage);

    $trend = $this->service->getChurnTrend(42, 90);

    $this->assertCount(2, $trend);
    $this->assertEquals(100, $trend[0]['id']);
    $this->assertEquals(45, $trend[0]['risk_score']);
    $this->assertEquals('medium', $trend[0]['risk_level']);
    $this->assertEquals(101, $trend[1]['id']);
    $this->assertEquals(72, $trend[1]['risk_score']);
    $this->assertEquals('high', $trend[1]['risk_level']);
  }

  /**
   * @covers ::getHighRiskTenants
   */
  public function testGetHighRiskTenantsReturnsEmptyWhenNoneFound(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('churn_prediction')
      ->willReturn($storage);

    $result = $this->service->getHighRiskTenants(20);

    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * @covers ::getHighRiskTenants
   */
  public function testGetHighRiskTenantsReturnsSerializedData(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([200]);

    // Build a rich prediction entity with all the fields getHighRiskTenants reads.
    $pred = $this->createHighRiskPredictionEntity(
      id: 200,
      tenantTargetId: 42,
      riskScore: 88,
      riskLevel: 'critical',
      contributingFactors: json_encode([['factor' => 'inactivity', 'score' => 90, 'weight' => 0.3]]),
      recommendedActions: json_encode([['action' => 'executive_outreach', 'priority' => 'urgent']]),
      modelVersion: 'heuristic_v1',
      created: '2025-12-20T10:00:00',
    );

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);
    $storage->method('loadMultiple')->with([200])->willReturn([200 => $pred]);

    $this->entityTypeManager->method('getStorage')
      ->with('churn_prediction')
      ->willReturn($storage);

    $results = $this->service->getHighRiskTenants(20);

    $this->assertCount(1, $results);
    $this->assertEquals(200, $results[0]['id']);
    $this->assertEquals(42, $results[0]['tenant_id']);
    $this->assertEquals(88, $results[0]['risk_score']);
    $this->assertEquals('critical', $results[0]['risk_level']);
    $this->assertIsArray($results[0]['contributing_factors']);
    $this->assertIsArray($results[0]['recommended_actions']);
    $this->assertEquals('heuristic_v1', $results[0]['model_version']);
  }

  /**
   * @covers ::getHighRiskTenants
   */
  public function testGetHighRiskTenantsRespectsLimit(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->expects($this->once())
      ->method('range')
      ->with(0, 5)
      ->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('churn_prediction')
      ->willReturn($storage);

    $this->service->getHighRiskTenants(5);
  }

  /**
   * @covers ::calculateChurnRisk
   */
  public function testCalculateChurnRiskLogsResult(): void {
    $tenantId = 15;

    $tenantEntity = $this->createMock(\Drupal\Core\Entity\EntityInterface::class);
    $groupStorage = $this->createMock(EntityStorageInterface::class);
    $groupStorage->method('load')->with($tenantId)->willReturn($tenantEntity);

    // User storage: no users -- 80.0 inactivity score.
    $userQuery = $this->createMock(QueryInterface::class);
    $userQuery->method('accessCheck')->willReturnSelf();
    $userQuery->method('condition')->willReturnSelf();
    $userQuery->method('sort')->willReturnSelf();
    $userQuery->method('range')->willReturnSelf();
    $userQuery->method('execute')->willReturn([]);

    $userStorage = $this->createMock(EntityStorageInterface::class);
    $userStorage->method('getQuery')->willReturn($userQuery);

    $churnQuery = $this->createMock(QueryInterface::class);
    $churnQuery->method('accessCheck')->willReturnSelf();
    $churnQuery->method('condition')->willReturnSelf();
    $churnQuery->method('count')->willReturnSelf();
    $churnQuery->method('execute')->willReturn(0);

    $predictionEntity = $this->createMock(\Drupal\Core\Entity\EntityInterface::class);
    $predictionEntity->method('save')->willReturn(1);

    $churnStorage = $this->createMock(EntityStorageInterface::class);
    $churnStorage->method('getQuery')->willReturn($churnQuery);
    $churnStorage->method('create')->willReturn($predictionEntity);

    $this->entityTypeManager->method('getStorage')
      ->willReturnMap([
        ['group', $groupStorage],
        ['user', $userStorage],
        ['churn_prediction', $churnStorage],
      ]);

    $this->logger->expects($this->once())
      ->method('info')
      ->with(
        'Churn prediction calculated for tenant @id: score=@score, level=@level',
        $this->callback(function (array $context) use ($tenantId): bool {
          return $context['@id'] === $tenantId
            && isset($context['@score'])
            && isset($context['@level']);
        }),
      );

    $this->service->calculateChurnRisk($tenantId);
  }

  /**
   * Helper: creates a mock ChurnPrediction entity for trend queries.
   */
  protected function createPredictionEntity(int $id, string $createdDate, int $riskScore, string $riskLevel): object {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('id')->willReturn($id);

    $entity->method('get')
      ->willReturnMap([
        ['created', (object) ['value' => $createdDate]],
        ['risk_score', (object) ['value' => $riskScore]],
        ['risk_level', (object) ['value' => $riskLevel]],
      ]);

    return $entity;
  }

  /**
   * Helper: creates a mock ChurnPrediction entity for high-risk queries.
   */
  protected function createHighRiskPredictionEntity(
    int $id,
    int $tenantTargetId,
    int $riskScore,
    string $riskLevel,
    string $contributingFactors,
    string $recommendedActions,
    string $modelVersion,
    string $created,
  ): object {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('id')->willReturn($id);

    $entity->method('get')
      ->willReturnMap([
        ['tenant_id', (object) ['target_id' => $tenantTargetId, 'value' => $tenantTargetId]],
        ['risk_score', (object) ['value' => $riskScore]],
        ['risk_level', (object) ['value' => $riskLevel]],
        ['contributing_factors', (object) ['value' => $contributingFactors]],
        ['recommended_actions', (object) ['value' => $recommendedActions]],
        ['model_version', (object) ['value' => $modelVersion]],
        ['created', (object) ['value' => $created]],
      ]);

    return $entity;
  }

}

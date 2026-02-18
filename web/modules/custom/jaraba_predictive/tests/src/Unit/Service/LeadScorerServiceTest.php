<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_predictive\Unit\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_predictive\Service\LeadScorerService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for LeadScorerService.
 *
 * @coversDefaultClass \Drupal\jaraba_predictive\Service\LeadScorerService
 * @group jaraba_predictive
 */
class LeadScorerServiceTest extends UnitTestCase {

  /**
   * The service under test.
   *
   * @var \Drupal\jaraba_predictive\Service\LeadScorerService
   */
  protected LeadScorerService $service;

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
   * Mock tenant context service.
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\TenantContextService|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $tenantContext;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->tenantContext = $this->createMock(TenantContextService::class);

    $this->service = new LeadScorerService(
      $this->entityTypeManager,
      $this->logger,
      $this->tenantContext,
    );
  }

  /**
   * @covers ::scoreUser
   */
  public function testScoreUserThrowsOnInvalidUser(): void {
    $userStorage = $this->createMock(EntityStorageInterface::class);
    $userStorage->method('load')->with(999)->willReturn(NULL);

    $this->entityTypeManager->method('getStorage')
      ->willReturnMap([
        ['user', $userStorage],
      ]);

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Usuario con ID 999 no encontrado.');

    $this->service->scoreUser(999);
  }

  /**
   * @covers ::scoreUser
   */
  public function testScoreUserCreatesNewLeadScore(): void {
    $userId = 10;

    // --- User storage: user exists and has recent access ---
    $accessField = (object) ['value' => time() - (2 * 86400)];
    $userEntity = $this->createMock(ContentEntityInterface::class);
    $userEntity->method('get')->with('access')->willReturn($accessField);

    $userStorage = $this->createMock(EntityStorageInterface::class);
    $userStorage->method('load')
      ->willReturnMap([
        [$userId, $userEntity],
      ]);

    // --- Lead score storage: no existing lead score ---
    $leadQuery = $this->createMock(QueryInterface::class);
    $leadQuery->method('accessCheck')->willReturnSelf();
    $leadQuery->method('condition')->willReturnSelf();
    $leadQuery->method('sort')->willReturnSelf();
    $leadQuery->method('range')->willReturnSelf();
    // No existing lead score found.
    $leadQuery->method('execute')->willReturn([]);

    $leadScoreEntity = $this->createMock(ContentEntityInterface::class);
    $leadScoreEntity->expects($this->once())->method('save');

    $leadStorage = $this->createMock(EntityStorageInterface::class);
    $leadStorage->method('getQuery')->willReturn($leadQuery);
    $leadStorage->method('create')->willReturn($leadScoreEntity);

    $this->entityTypeManager->method('getStorage')
      ->willReturnMap([
        ['user', $userStorage],
        ['lead_score', $leadStorage],
      ]);

    $result = $this->service->scoreUser($userId);

    $this->assertArrayHasKey('lead_score', $result);
    $this->assertSame($leadScoreEntity, $result['lead_score']);
  }

  /**
   * @covers ::scoreUser
   */
  public function testScoreUserUpdatesExistingLeadScore(): void {
    $userId = 20;

    // User entity with recent access.
    $accessField = (object) ['value' => time()];
    $userEntity = $this->createMock(ContentEntityInterface::class);
    $userEntity->method('get')->with('access')->willReturn($accessField);

    $userStorage = $this->createMock(EntityStorageInterface::class);
    $userStorage->method('load')
      ->willReturnMap([
        [$userId, $userEntity],
      ]);

    // Existing lead score entity.
    $leadQuery = $this->createMock(QueryInterface::class);
    $leadQuery->method('accessCheck')->willReturnSelf();
    $leadQuery->method('condition')->willReturnSelf();
    $leadQuery->method('sort')->willReturnSelf();
    $leadQuery->method('range')->willReturnSelf();
    // Existing lead score ID found.
    $leadQuery->method('execute')->willReturn([50]);

    $existingEntity = $this->createMock(ContentEntityInterface::class);
    // set() must be called for updated fields.
    $existingEntity->expects($this->atLeast(4))->method('set');
    $existingEntity->expects($this->once())->method('save');

    $leadStorage = $this->createMock(EntityStorageInterface::class);
    $leadStorage->method('getQuery')->willReturn($leadQuery);
    $leadStorage->method('load')->with(50)->willReturn($existingEntity);
    // create() should NOT be called when updating.
    $leadStorage->expects($this->never())->method('create');

    $this->entityTypeManager->method('getStorage')
      ->willReturnMap([
        ['user', $userStorage],
        ['lead_score', $leadStorage],
      ]);

    $result = $this->service->scoreUser($userId);

    $this->assertArrayHasKey('lead_score', $result);
    $this->assertSame($existingEntity, $result['lead_score']);
  }

  /**
   * @covers ::scoreUser
   */
  public function testScoreUserLogsResult(): void {
    $userId = 30;

    $accessField = (object) ['value' => time() - (10 * 86400)];
    $userEntity = $this->createMock(ContentEntityInterface::class);
    $userEntity->method('get')->with('access')->willReturn($accessField);

    $userStorage = $this->createMock(EntityStorageInterface::class);
    $userStorage->method('load')
      ->willReturnMap([
        [$userId, $userEntity],
      ]);

    $leadQuery = $this->createMock(QueryInterface::class);
    $leadQuery->method('accessCheck')->willReturnSelf();
    $leadQuery->method('condition')->willReturnSelf();
    $leadQuery->method('sort')->willReturnSelf();
    $leadQuery->method('range')->willReturnSelf();
    $leadQuery->method('execute')->willReturn([]);

    $leadScoreEntity = $this->createMock(ContentEntityInterface::class);
    $leadScoreEntity->method('save')->willReturn(1);

    $leadStorage = $this->createMock(EntityStorageInterface::class);
    $leadStorage->method('getQuery')->willReturn($leadQuery);
    $leadStorage->method('create')->willReturn($leadScoreEntity);

    $this->entityTypeManager->method('getStorage')
      ->willReturnMap([
        ['user', $userStorage],
        ['lead_score', $leadStorage],
      ]);

    $this->logger->expects($this->once())
      ->method('info')
      ->with(
        'Lead score calculated for user @id: total=@total, qualification=@qual',
        $this->callback(function (array $context) use ($userId): bool {
          return $context['@id'] === $userId
            && isset($context['@total'])
            && isset($context['@qual']);
        }),
      );

    $this->service->scoreUser($userId);
  }

  /**
   * @covers ::getTopLeads
   */
  public function testGetTopLeadsReturnsEmptyWhenNoLeads(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('lead_score')
      ->willReturn($storage);

    $result = $this->service->getTopLeads(20);

    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * @covers ::getTopLeads
   */
  public function testGetTopLeadsReturnsSerializedData(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([300, 301]);

    $lead1 = $this->createLeadScoreEntity(
      id: 300,
      userTargetId: 10,
      totalScore: 85,
      qualification: 'sales_ready',
      scoreBreakdown: json_encode(['engagement' => ['score' => 90, 'weight' => 0.4]]),
      lastActivity: '2025-12-20T08:00:00',
      modelVersion: 'heuristic_v1',
      calculatedAt: '2025-12-20T08:00:00',
      created: '2025-12-01T00:00:00',
    );
    $lead2 = $this->createLeadScoreEntity(
      id: 301,
      userTargetId: 11,
      totalScore: 60,
      qualification: 'hot',
      scoreBreakdown: json_encode(['engagement' => ['score' => 60, 'weight' => 0.4]]),
      lastActivity: '2025-12-19T12:00:00',
      modelVersion: 'heuristic_v1',
      calculatedAt: '2025-12-19T12:00:00',
      created: '2025-12-01T00:00:00',
    );

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);
    $storage->method('loadMultiple')
      ->with([300, 301])
      ->willReturn([300 => $lead1, 301 => $lead2]);

    $this->entityTypeManager->method('getStorage')
      ->with('lead_score')
      ->willReturn($storage);

    $results = $this->service->getTopLeads(20);

    $this->assertCount(2, $results);
    $this->assertEquals(300, $results[0]['id']);
    $this->assertEquals(10, $results[0]['user_id']);
    $this->assertEquals(85, $results[0]['total_score']);
    $this->assertEquals('sales_ready', $results[0]['qualification']);
    $this->assertIsArray($results[0]['score_breakdown']);
    $this->assertEquals('heuristic_v1', $results[0]['model_version']);

    $this->assertEquals(301, $results[1]['id']);
    $this->assertEquals(60, $results[1]['total_score']);
    $this->assertEquals('hot', $results[1]['qualification']);
  }

  /**
   * @covers ::getTopLeads
   */
  public function testGetTopLeadsRespectsLimit(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->expects($this->once())
      ->method('range')
      ->with(0, 3)
      ->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('lead_score')
      ->willReturn($storage);

    $this->service->getTopLeads(3);
  }

  /**
   * @covers ::getLeadsByQualification
   */
  public function testGetLeadsByQualificationThrowsOnInvalidValue(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Cualificacion invalida: invalid_value');

    $this->service->getLeadsByQualification('invalid_value');
  }

  /**
   * @covers ::getLeadsByQualification
   */
  public function testGetLeadsByQualificationReturnsEmptyWhenNoneFound(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('lead_score')
      ->willReturn($storage);

    $result = $this->service->getLeadsByQualification('hot');

    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * @covers ::getLeadsByQualification
   */
  public function testGetLeadsByQualificationReturnsFilteredResults(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->expects($this->atLeastOnce())
      ->method('condition')
      ->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([400]);

    $lead = $this->createLeadScoreEntity(
      id: 400,
      userTargetId: 50,
      totalScore: 30,
      qualification: 'warm',
      scoreBreakdown: json_encode(['engagement' => ['score' => 40, 'weight' => 0.4]]),
      lastActivity: '2025-12-15T10:00:00',
      modelVersion: 'heuristic_v1',
      calculatedAt: '2025-12-15T10:00:00',
      created: '2025-12-01T00:00:00',
    );

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);
    $storage->method('loadMultiple')
      ->with([400])
      ->willReturn([400 => $lead]);

    $this->entityTypeManager->method('getStorage')
      ->with('lead_score')
      ->willReturn($storage);

    $results = $this->service->getLeadsByQualification('warm');

    $this->assertCount(1, $results);
    $this->assertEquals(400, $results[0]['id']);
    $this->assertEquals('warm', $results[0]['qualification']);
    $this->assertEquals(30, $results[0]['total_score']);
  }

  /**
   * @covers ::getLeadsByQualification
   *
   * Validates that all four valid qualification values are accepted.
   *
   * @dataProvider validQualificationsProvider
   */
  public function testGetLeadsByQualificationAcceptsAllValidValues(string $qualification): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('lead_score')
      ->willReturn($storage);

    // Should not throw.
    $result = $this->service->getLeadsByQualification($qualification);
    $this->assertIsArray($result);
  }

  /**
   * Data provider for valid qualification levels.
   *
   * @return array
   *   Test cases with qualification string.
   */
  public static function validQualificationsProvider(): array {
    return [
      'cold' => ['cold'],
      'warm' => ['warm'],
      'hot' => ['hot'],
      'sales_ready' => ['sales_ready'],
    ];
  }

  /**
   * @covers ::scoreUser
   */
  public function testScoreUserScoreIsClamped(): void {
    $userId = 55;

    // User with zero access time -- triggers 5.0 engagement score.
    $accessField = (object) ['value' => 0];
    $userEntity = $this->createMock(ContentEntityInterface::class);
    $userEntity->method('get')->with('access')->willReturn($accessField);

    $userStorage = $this->createMock(EntityStorageInterface::class);
    $userStorage->method('load')
      ->willReturnMap([
        [$userId, $userEntity],
      ]);

    $leadQuery = $this->createMock(QueryInterface::class);
    $leadQuery->method('accessCheck')->willReturnSelf();
    $leadQuery->method('condition')->willReturnSelf();
    $leadQuery->method('sort')->willReturnSelf();
    $leadQuery->method('range')->willReturnSelf();
    $leadQuery->method('execute')->willReturn([]);

    $createdValues = [];
    $leadScoreEntity = $this->createMock(ContentEntityInterface::class);
    $leadScoreEntity->method('save')->willReturn(1);

    $leadStorage = $this->createMock(EntityStorageInterface::class);
    $leadStorage->method('getQuery')->willReturn($leadQuery);
    $leadStorage->method('create')
      ->willReturnCallback(function (array $values) use ($leadScoreEntity, &$createdValues) {
        $createdValues = $values;
        return $leadScoreEntity;
      });

    $this->entityTypeManager->method('getStorage')
      ->willReturnMap([
        ['user', $userStorage],
        ['lead_score', $leadStorage],
      ]);

    $this->service->scoreUser($userId);

    // The total_score passed to create() must be between 0 and 100.
    $this->assertArrayHasKey('total_score', $createdValues);
    $this->assertGreaterThanOrEqual(0, $createdValues['total_score']);
    $this->assertLessThanOrEqual(100, $createdValues['total_score']);
  }

  /**
   * Helper: creates a mock LeadScore entity with all serializable fields.
   */
  protected function createLeadScoreEntity(
    int $id,
    int $userTargetId,
    int $totalScore,
    string $qualification,
    string $scoreBreakdown,
    string $lastActivity,
    string $modelVersion,
    string $calculatedAt,
    string $created,
  ): object {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('id')->willReturn($id);

    $entity->method('get')
      ->willReturnMap([
        ['user_id', (object) ['target_id' => $userTargetId, 'value' => $userTargetId]],
        ['total_score', (object) ['value' => $totalScore]],
        ['qualification', (object) ['value' => $qualification]],
        ['score_breakdown', (object) ['value' => $scoreBreakdown]],
        ['last_activity', (object) ['value' => $lastActivity]],
        ['model_version', (object) ['value' => $modelVersion]],
        ['calculated_at', (object) ['value' => $calculatedAt]],
        ['created', (object) ['value' => $created]],
      ]);

    return $entity;
  }

}

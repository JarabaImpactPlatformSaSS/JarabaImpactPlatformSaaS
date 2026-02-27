<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ai_agents\Unit\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\State\StateInterface;
use Drupal\jaraba_ai_agents\Service\AutonomousAgentService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for AutonomousAgentService (GAP-L5-F).
 *
 * @coversDefaultClass \Drupal\jaraba_ai_agents\Service\AutonomousAgentService
 * @group jaraba_ai_agents
 */
class AutonomousAgentServiceTest extends TestCase {

  /**
   * The service under test.
   */
  protected AutonomousAgentService $service;

  /**
   * Mock entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock queue factory.
   */
  protected QueueFactory $queueFactory;

  /**
   * Mock state.
   */
  protected StateInterface $state;

  /**
   * Mock logger.
   */
  protected LoggerInterface $logger;

  /**
   * Mock storage.
   */
  protected EntityStorageInterface $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->queueFactory = $this->createMock(QueueFactory::class);
    $this->state = $this->createMock(StateInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->storage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->with('autonomous_session')
      ->willReturn($this->storage);

    $this->service = new AutonomousAgentService(
      $this->entityTypeManager,
      $this->queueFactory,
      $this->state,
      $this->logger,
    );
  }

  /**
   * Creates a mock session entity.
   */
  protected function createMockSession(array $values = []): ContentEntityInterface {
    $session = $this->createMock(ContentEntityInterface::class);

    $defaults = [
      'id' => '1',
      'agent_type' => 'reputation_monitor',
      'status' => 'active',
      'execution_count' => 0,
      'consecutive_failures' => 0,
      'total_cost' => 0.0,
      'tenant_id' => 'tenant-1',
      'constraints' => '{"cost_ceiling":10.0,"max_runtime":3600,"max_executions":100}',
    ];

    $values = array_merge($defaults, $values);

    $session->method('id')->willReturn($values['id']);

    // Field-based getters.
    $fieldMap = [];
    foreach ($values as $key => $value) {
      $fieldItem = $this->createMock(FieldItemListInterface::class);
      $fieldItem->value = $value;
      $fieldMap[] = [$key, $fieldItem];
    }
    $session->method('get')->willReturnMap($fieldMap);

    // Direct method calls for entities with getters.
    if (method_exists($session, 'getAgentType')) {
      $session->method('getAgentType')->willReturn($values['agent_type']);
    }

    return $session;
  }

  /**
   * @covers ::createSession
   */
  public function testCreateSessionRejectsInvalidAgentType(): void {
    $result = $this->service->createSession('invalid_type', 'tenant-1', 'objectives: test');
    $this->assertNull($result);
  }

  /**
   * @covers ::createSession
   */
  public function testCreateSessionAcceptsValidTypes(): void {
    $validTypes = ['reputation_monitor', 'content_curator', 'kb_maintainer', 'churn_prevention'];

    foreach ($validTypes as $type) {
      $mockSession = $this->createMock(ContentEntityInterface::class);
      $mockSession->method('id')->willReturn('1');

      $this->storage->expects($this->atLeastOnce())
        ->method('create')
        ->willReturn($mockSession);

      $result = $this->service->createSession($type, 'tenant-1', 'objectives: test');
      $this->assertEquals(1, $result, "Failed for type: {$type}");
    }
  }

  /**
   * @covers ::activateSession
   */
  public function testActivateSessionNotFoundReturnsFalse(): void {
    $this->storage->method('load')->with(999)->willReturn(NULL);
    $this->assertFalse($this->service->activateSession(999));
  }

  /**
   * @covers ::activateSession
   */
  public function testActivateSessionWrongStatusReturnsFalse(): void {
    $session = $this->createMockSession(['status' => 'completed']);
    $this->storage->method('load')->with(1)->willReturn($session);
    $this->assertFalse($this->service->activateSession(1));
  }

  /**
   * @covers ::enqueueHeartbeats
   */
  public function testEnqueueHeartbeatsRateLimited(): void {
    // State says last heartbeat was 10 seconds ago (within interval).
    $this->state->method('get')
      ->with('jaraba_ai_agents.autonomous.last_heartbeat', 0)
      ->willReturn(time() - 10);

    // Queue should never be called.
    $this->queueFactory->expects($this->never())->method('get');

    $this->service->enqueueHeartbeats();
  }

  /**
   * Tests constants are properly defined.
   */
  public function testConstants(): void {
    $this->assertEquals(3, AutonomousAgentService::MAX_CONSECUTIVE_FAILURES);
    $this->assertEquals(300, AutonomousAgentService::DEFAULT_HEARTBEAT_INTERVAL);
  }

  /**
   * @covers ::getStats
   */
  public function testGetStatsReturnsStructuredData(): void {
    $queryMock = $this->createMock(\Drupal\Core\Entity\Query\QueryInterface::class);
    $queryMock->method('accessCheck')->willReturnSelf();
    $queryMock->method('sort')->willReturnSelf();
    $queryMock->method('condition')->willReturnSelf();
    $queryMock->method('execute')->willReturn([]);

    $this->storage->method('getQuery')->willReturn($queryMock);
    $this->storage->method('loadMultiple')->with([])->willReturn([]);

    $stats = $this->service->getStats('tenant-1');

    $this->assertArrayHasKey('total', $stats);
    $this->assertArrayHasKey('active', $stats);
    $this->assertArrayHasKey('paused', $stats);
    $this->assertArrayHasKey('completed', $stats);
    $this->assertArrayHasKey('escalated', $stats);
    $this->assertArrayHasKey('total_cost', $stats);
    $this->assertArrayHasKey('total_heartbeats', $stats);
    $this->assertEquals(0, $stats['total']);
  }

  /**
   * @covers ::createSession
   */
  public function testCreateSessionDefaultConstraints(): void {
    $capturedData = NULL;
    $mockSession = $this->createMock(ContentEntityInterface::class);
    $mockSession->method('id')->willReturn('1');

    $this->storage->method('create')
      ->willReturnCallback(function (array $data) use ($mockSession, &$capturedData) {
        $capturedData = $data;
        return $mockSession;
      });

    $this->service->createSession('reputation_monitor', 'tenant-1', 'objectives: test');

    $this->assertNotNull($capturedData);
    $constraints = json_decode($capturedData['constraints'], TRUE);
    $this->assertEquals(10.0, $constraints['cost_ceiling']);
    $this->assertEquals(3600, $constraints['max_runtime']);
    $this->assertEquals(100, $constraints['max_executions']);
    $this->assertEquals(3, $constraints['escalation_rules']['on_failure_count']);
  }

  /**
   * @covers ::createSession
   */
  public function testCreateSessionCustomConstraintsOverrideDefaults(): void {
    $capturedData = NULL;
    $mockSession = $this->createMock(ContentEntityInterface::class);
    $mockSession->method('id')->willReturn('1');

    $this->storage->method('create')
      ->willReturnCallback(function (array $data) use ($mockSession, &$capturedData) {
        $capturedData = $data;
        return $mockSession;
      });

    $this->service->createSession('content_curator', 'tenant-1', 'objectives: test', [
      'cost_ceiling' => 25.0,
      'max_runtime' => 7200,
    ]);

    $constraints = json_decode($capturedData['constraints'], TRUE);
    $this->assertEquals(25.0, $constraints['cost_ceiling']);
    $this->assertEquals(7200, $constraints['max_runtime']);
  }

}

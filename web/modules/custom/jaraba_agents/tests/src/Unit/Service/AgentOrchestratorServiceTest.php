<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_agents\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_agents\Service\AgentMetricsCollectorService;
use Drupal\jaraba_agents\Service\AgentOrchestratorService;
use Drupal\jaraba_agents\Service\ApprovalManagerService;
use Drupal\jaraba_agents\Service\GuardrailsEnforcerService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for AgentOrchestratorService.
 *
 * @coversDefaultClass \Drupal\jaraba_agents\Service\AgentOrchestratorService
 * @group jaraba_agents
 */
class AgentOrchestratorServiceTest extends UnitTestCase {

  /**
   * The service being tested.
   *
   * @var \Drupal\jaraba_agents\Service\AgentOrchestratorService
   */
  protected AgentOrchestratorService $service;

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
   * Mock guardrails service.
   *
   * @var \Drupal\jaraba_agents\Service\GuardrailsEnforcerService|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $guardrails;

  /**
   * Mock metrics service.
   *
   * @var \Drupal\jaraba_agents\Service\AgentMetricsCollectorService|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $metrics;

  /**
   * Mock approval manager service.
   *
   * @var \Drupal\jaraba_agents\Service\ApprovalManagerService|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $approvalManager;

  /**
   * Mock agent entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $agentStorage;

  /**
   * Mock execution entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $executionStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->guardrails = $this->createMock(GuardrailsEnforcerService::class);
    $this->metrics = $this->createMock(AgentMetricsCollectorService::class);
    $this->approvalManager = $this->createMock(ApprovalManagerService::class);

    $this->agentStorage = $this->createMock(EntityStorageInterface::class);
    $this->executionStorage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $entityType) {
        return match ($entityType) {
          'autonomous_agent' => $this->agentStorage,
          'agent_execution' => $this->executionStorage,
          default => $this->createMock(EntityStorageInterface::class),
        };
      });

    $this->service = new AgentOrchestratorService(
      $this->entityTypeManager,
      $this->logger,
      $this->guardrails,
      $this->metrics,
      $this->approvalManager,
    );
  }

  /**
   * Creates a mock entity with configurable field values.
   *
   * @param array $fields
   *   Associative array of field_name => value or field_name => ['target_id' => id].
   * @param int|null $id
   *   Entity ID to return from id().
   * @param string|null $label
   *   Label to return from label().
   *
   * @return object|\PHPUnit\Framework\MockObject\MockObject
   *   Mock entity.
   */
  protected function createMockEntity(array $fields = [], ?int $id = NULL, ?string $label = NULL): object {
    $entity = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['id', 'label', 'get', 'set', 'save', 'hasField'])
      ->getMock();

    if ($id !== NULL) {
      $entity->method('id')->willReturn($id);
    }

    if ($label !== NULL) {
      $entity->method('label')->willReturn($label);
    }

    $entity->method('hasField')->willReturnCallback(function (string $fieldName) use ($fields): bool {
      return array_key_exists($fieldName, $fields);
    });

    $entity->method('get')->willReturnCallback(function (string $fieldName) use ($fields): object {
      $fieldItem = new \stdClass();
      if (isset($fields[$fieldName])) {
        $value = $fields[$fieldName];
        if (is_array($value) && isset($value['target_id'])) {
          $fieldItem->target_id = $value['target_id'];
          $fieldItem->value = $value['target_id'];
        }
        else {
          $fieldItem->value = $value;
          $fieldItem->target_id = $value;
        }
      }
      else {
        $fieldItem->value = NULL;
        $fieldItem->target_id = NULL;
      }
      return $fieldItem;
    });

    return $entity;
  }

  /**
   * @covers ::execute
   */
  public function testExecuteReturnsErrorWhenAgentNotFound(): void {
    $this->agentStorage->method('load')->with(999)->willReturn(NULL);

    $result = $this->service->execute(999);

    $this->assertFalse($result['success']);
    $this->assertArrayHasKey('error', $result);
    $this->assertStringContainsString('999', $result['error']);
  }

  /**
   * @covers ::execute
   */
  public function testExecuteReturnsErrorWhenGuardrailsFail(): void {
    $agent = $this->createMockEntity([
      'tenant_id' => ['target_id' => 1],
    ], 1, 'Test Agent');

    $this->agentStorage->method('load')->with(1)->willReturn($agent);

    $this->guardrails->method('enforce')->with($agent)->willReturn([
      'passed' => FALSE,
      'violations' => ['Token budget exceeded', 'Cost limit reached'],
    ]);

    $result = $this->service->execute(1);

    $this->assertFalse($result['success']);
    $this->assertArrayHasKey('violations', $result);
    $this->assertCount(2, $result['violations']);
  }

  /**
   * @covers ::execute
   */
  public function testExecuteSuccessfullyCreatesRunningExecution(): void {
    $agent = $this->createMockEntity([
      'tenant_id' => ['target_id' => 5],
    ], 1, 'Test Agent');

    $execution = $this->createMockEntity([
      'status' => 'running',
    ], 42);

    $this->agentStorage->method('load')->with(1)->willReturn($agent);

    $this->guardrails->method('enforce')->willReturn([
      'passed' => TRUE,
      'violations' => [],
    ]);

    // L1 agent: no approval flow.
    $this->guardrails->method('getLevel')->willReturn('L1');

    $this->executionStorage->method('create')->willReturn($execution);

    $this->metrics->expects($this->once())
      ->method('record')
      ->with(42, $this->isType('array'));

    $result = $this->service->execute(1, 'user_request', ['context' => 'test']);

    $this->assertTrue($result['success']);
    $this->assertEquals(42, $result['execution_id']);
    $this->assertEquals('running', $result['status']);
  }

  /**
   * @covers ::execute
   */
  public function testExecuteL2AgentRequiresApproval(): void {
    $agent = $this->createMockEntity([
      'tenant_id' => ['target_id' => 5],
    ], 1, 'Agent L2');

    $execution = $this->createMockEntity([
      'status' => 'running',
    ], 100);

    // Create a separate mock for the paused-execution load.
    $pausedExecution = $this->createMockEntity([
      'status' => 'running',
    ], 100);

    $this->agentStorage->method('load')->with(1)->willReturn($agent);

    $this->guardrails->method('enforce')->willReturn([
      'passed' => TRUE,
      'violations' => [],
    ]);

    $this->guardrails->method('getLevel')->willReturn('L2');

    $this->guardrails->method('check')->willReturn([
      'requires_approval' => TRUE,
      'reason' => 'High-risk action detected',
    ]);

    $this->executionStorage->method('create')->willReturn($execution);

    // The pause() call inside execute() will call transitionStatus() -> load().
    $this->executionStorage->method('load')->with(100)->willReturn($pausedExecution);

    $this->approvalManager->method('requestApproval')->willReturn([
      'success' => TRUE,
      'approval_id' => 55,
    ]);

    $result = $this->service->execute(1, 'schedule');

    $this->assertTrue($result['success']);
    $this->assertEquals(100, $result['execution_id']);
    $this->assertEquals('awaiting_approval', $result['status']);
    $this->assertEquals(55, $result['approval_id']);
  }

  /**
   * @covers ::execute
   */
  public function testExecuteL2AgentWithoutApprovalRequired(): void {
    $agent = $this->createMockEntity([
      'tenant_id' => ['target_id' => 5],
    ], 1, 'Agent L2');

    $execution = $this->createMockEntity([
      'status' => 'running',
    ], 77);

    $this->agentStorage->method('load')->with(1)->willReturn($agent);

    $this->guardrails->method('enforce')->willReturn([
      'passed' => TRUE,
      'violations' => [],
    ]);

    $this->guardrails->method('getLevel')->willReturn('L2');

    // No approval required for this action.
    $this->guardrails->method('check')->willReturn([
      'requires_approval' => FALSE,
    ]);

    $this->executionStorage->method('create')->willReturn($execution);

    $this->metrics->expects($this->once())->method('record');

    $result = $this->service->execute(1);

    $this->assertTrue($result['success']);
    $this->assertEquals('running', $result['status']);
  }

  /**
   * @covers ::execute
   */
  public function testExecuteHandlesExceptionGracefully(): void {
    $this->agentStorage->method('load')->willThrowException(new \RuntimeException('DB error'));

    $result = $this->service->execute(1);

    $this->assertFalse($result['success']);
    $this->assertArrayHasKey('error', $result);
  }

  /**
   * @covers ::getStatus
   */
  public function testGetStatusReturnsExecutionData(): void {
    $execution = $this->createMockEntity([
      'agent_id' => ['target_id' => 3],
      'status' => 'running',
      'trigger_type' => 'user_request',
      'started_at' => '2026-02-18T10:00:00',
      'actions_taken' => '["action_a","action_b"]',
      'tokens_used' => 1500,
      'cost' => 0.0025,
    ], 50);

    $this->executionStorage->method('load')->with(50)->willReturn($execution);

    $result = $this->service->getStatus(50);

    $this->assertTrue($result['success']);
    $this->assertEquals(50, $result['execution_id']);
    $this->assertEquals(3, $result['agent_id']);
    $this->assertEquals('running', $result['status']);
    $this->assertEquals('user_request', $result['trigger_type']);
    $this->assertCount(2, $result['actions_taken']);
    $this->assertEquals(1500, $result['tokens_used']);
    $this->assertEquals(0.0025, $result['cost']);
  }

  /**
   * @covers ::getStatus
   */
  public function testGetStatusReturnsErrorForMissingExecution(): void {
    $this->executionStorage->method('load')->with(999)->willReturn(NULL);

    $result = $this->service->getStatus(999);

    $this->assertFalse($result['success']);
    $this->assertArrayHasKey('error', $result);
    $this->assertStringContainsString('999', $result['error']);
  }

  /**
   * @covers ::getStatus
   */
  public function testGetStatusHandlesException(): void {
    $this->executionStorage->method('load')->willThrowException(new \RuntimeException('Connection lost'));

    $result = $this->service->getStatus(1);

    $this->assertFalse($result['success']);
    $this->assertArrayHasKey('error', $result);
  }

  /**
   * @covers ::pause
   */
  public function testPauseTransitionsRunningToPaused(): void {
    $execution = $this->createMockEntity([
      'status' => 'running',
    ], 10);

    $this->executionStorage->method('load')->with(10)->willReturn($execution);

    $result = $this->service->pause(10);

    $this->assertTrue($result['success']);
    $this->assertEquals('paused', $result['status']);
    $this->assertEquals('running', $result['previous_status']);
  }

  /**
   * @covers ::pause
   */
  public function testPauseFailsForCompletedExecution(): void {
    $execution = $this->createMockEntity([
      'status' => 'completed',
    ], 10);

    $this->executionStorage->method('load')->with(10)->willReturn($execution);

    $result = $this->service->pause(10);

    $this->assertFalse($result['success']);
    $this->assertArrayHasKey('error', $result);
  }

  /**
   * @covers ::resume
   */
  public function testResumeTransitionsPausedToRunning(): void {
    $execution = $this->createMockEntity([
      'status' => 'paused',
    ], 20);

    $this->executionStorage->method('load')->with(20)->willReturn($execution);

    $result = $this->service->resume(20);

    $this->assertTrue($result['success']);
    $this->assertEquals('running', $result['status']);
    $this->assertEquals('paused', $result['previous_status']);
  }

  /**
   * @covers ::resume
   */
  public function testResumeFailsForRunningExecution(): void {
    $execution = $this->createMockEntity([
      'status' => 'running',
    ], 20);

    $this->executionStorage->method('load')->with(20)->willReturn($execution);

    // running -> running is not a valid transition.
    $result = $this->service->resume(20);

    $this->assertFalse($result['success']);
    $this->assertArrayHasKey('error', $result);
  }

  /**
   * @covers ::cancel
   */
  public function testCancelTransitionsRunningToCancelled(): void {
    $execution = $this->createMockEntity([
      'status' => 'running',
    ], 30);

    $this->executionStorage->method('load')->with(30)->willReturn($execution);

    $result = $this->service->cancel(30);

    $this->assertTrue($result['success']);
    $this->assertEquals('cancelled', $result['status']);
    $this->assertEquals('running', $result['previous_status']);
  }

  /**
   * @covers ::cancel
   */
  public function testCancelTransitionsPausedToCancelled(): void {
    $execution = $this->createMockEntity([
      'status' => 'paused',
    ], 30);

    $this->executionStorage->method('load')->with(30)->willReturn($execution);

    $result = $this->service->cancel(30);

    $this->assertTrue($result['success']);
    $this->assertEquals('cancelled', $result['status']);
    $this->assertEquals('paused', $result['previous_status']);
  }

  /**
   * @covers ::cancel
   */
  public function testCancelFailsForAlreadyCancelledExecution(): void {
    $execution = $this->createMockEntity([
      'status' => 'cancelled',
    ], 30);

    $this->executionStorage->method('load')->with(30)->willReturn($execution);

    $result = $this->service->cancel(30);

    $this->assertFalse($result['success']);
    $this->assertArrayHasKey('error', $result);
  }

  /**
   * @covers ::cancel
   */
  public function testCancelFailsForFailedExecution(): void {
    $execution = $this->createMockEntity([
      'status' => 'failed',
    ], 30);

    $this->executionStorage->method('load')->with(30)->willReturn($execution);

    $result = $this->service->cancel(30);

    $this->assertFalse($result['success']);
    $this->assertArrayHasKey('error', $result);
  }

  /**
   * Tests that transition returns error when execution not found.
   *
   * @covers ::pause
   */
  public function testTransitionReturnsErrorForMissingExecution(): void {
    $this->executionStorage->method('load')->with(999)->willReturn(NULL);

    $result = $this->service->pause(999);

    $this->assertFalse($result['success']);
    $this->assertArrayHasKey('error', $result);
    $this->assertStringContainsString('999', $result['error']);
  }

  /**
   * @covers ::getActiveExecutions
   */
  public function testGetActiveExecutionsReturnsResults(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([10, 20]);

    $this->executionStorage->method('getQuery')->willReturn($query);

    $exec1 = $this->createMockEntity([
      'agent_id' => ['target_id' => 1],
      'status' => 'running',
      'trigger_type' => 'user_request',
      'started_at' => '2026-02-18T10:00:00',
      'tokens_used' => 500,
      'cost' => 0.001,
      'tenant_id' => ['target_id' => 5],
    ], 10);

    $exec2 = $this->createMockEntity([
      'agent_id' => ['target_id' => 2],
      'status' => 'paused',
      'trigger_type' => 'schedule',
      'started_at' => '2026-02-18T09:00:00',
      'tokens_used' => 1000,
      'cost' => 0.002,
      'tenant_id' => ['target_id' => 5],
    ], 20);

    $this->executionStorage->method('loadMultiple')
      ->with([10, 20])
      ->willReturn([10 => $exec1, 20 => $exec2]);

    $results = $this->service->getActiveExecutions();

    $this->assertCount(2, $results);
    $this->assertEquals(10, $results[0]['execution_id']);
    $this->assertEquals('running', $results[0]['status']);
    $this->assertEquals(20, $results[1]['execution_id']);
    $this->assertEquals('paused', $results[1]['status']);
    $this->assertEquals(5, $results[0]['tenant_id']);
  }

  /**
   * @covers ::getActiveExecutions
   */
  public function testGetActiveExecutionsReturnsEmptyWhenNoResults(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->executionStorage->method('getQuery')->willReturn($query);

    $results = $this->service->getActiveExecutions();

    $this->assertEmpty($results);
  }

  /**
   * @covers ::getActiveExecutions
   */
  public function testGetActiveExecutionsHandlesException(): void {
    $this->executionStorage->method('getQuery')
      ->willThrowException(new \RuntimeException('Storage unavailable'));

    $results = $this->service->getActiveExecutions();

    $this->assertEmpty($results);
  }

  /**
   * @covers ::execute
   */
  public function testExecuteWithDefaultTriggerType(): void {
    $agent = $this->createMockEntity([
      'tenant_id' => ['target_id' => 1],
    ], 1, 'Default Trigger Agent');

    $execution = $this->createMockEntity([], 10);

    $this->agentStorage->method('load')->with(1)->willReturn($agent);
    $this->guardrails->method('enforce')->willReturn(['passed' => TRUE, 'violations' => []]);
    $this->guardrails->method('getLevel')->willReturn('L0');
    $this->executionStorage->method('create')->willReturn($execution);

    $result = $this->service->execute(1);

    $this->assertTrue($result['success']);
  }

  /**
   * Tests that L0 agent goes through normal execution (no approval check).
   *
   * @covers ::execute
   */
  public function testExecuteL0AgentSkipsApprovalFlow(): void {
    $agent = $this->createMockEntity([
      'tenant_id' => ['target_id' => 1],
    ], 1, 'L0 Agent');

    $execution = $this->createMockEntity([], 88);

    $this->agentStorage->method('load')->with(1)->willReturn($agent);
    $this->guardrails->method('enforce')->willReturn(['passed' => TRUE, 'violations' => []]);
    $this->guardrails->method('getLevel')->willReturn('L0');
    $this->executionStorage->method('create')->willReturn($execution);

    // Approval manager should never be called for L0.
    $this->approvalManager->expects($this->never())->method('requestApproval');

    $result = $this->service->execute(1);

    $this->assertTrue($result['success']);
    $this->assertEquals('running', $result['status']);
  }

  /**
   * Tests that L1 agent skips approval flow.
   *
   * @covers ::execute
   */
  public function testExecuteL1AgentSkipsApprovalFlow(): void {
    $agent = $this->createMockEntity([
      'tenant_id' => ['target_id' => 1],
    ], 1, 'L1 Agent');

    $execution = $this->createMockEntity([], 89);

    $this->agentStorage->method('load')->with(1)->willReturn($agent);
    $this->guardrails->method('enforce')->willReturn(['passed' => TRUE, 'violations' => []]);
    $this->guardrails->method('getLevel')->willReturn('L1');
    $this->executionStorage->method('create')->willReturn($execution);

    $this->approvalManager->expects($this->never())->method('requestApproval');

    $result = $this->service->execute(1);

    $this->assertTrue($result['success']);
    $this->assertEquals('running', $result['status']);
  }

}

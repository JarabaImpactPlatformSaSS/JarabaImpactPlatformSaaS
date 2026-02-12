<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_agent_flows\Unit\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\jaraba_agent_flows\Service\AgentFlowExecutionService;
use Drupal\jaraba_ai_agents\Service\WorkflowExecutorService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para AgentFlowExecutionService.
 *
 * @covers \Drupal\jaraba_agent_flows\Service\AgentFlowExecutionService
 * @group jaraba_agent_flows
 */
class AgentFlowExecutionServiceTest extends UnitTestCase {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected WorkflowExecutorService $workflowExecutor;
  protected LoggerInterface $logger;
  protected AgentFlowExecutionService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->workflowExecutor = $this->createMock(WorkflowExecutorService::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new AgentFlowExecutionService(
      $this->entityTypeManager,
      $this->workflowExecutor,
      $this->logger,
    );
  }

  /**
   * Tests executeFlow returns NULL when flow not found.
   */
  public function testExecuteFlowNotFound(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(999)->willReturn(NULL);
    $this->entityTypeManager->method('getStorage')
      ->with('agent_flow')
      ->willReturn($storage);

    $this->assertNull($this->service->executeFlow(999));
  }

  /**
   * Tests executeFlow returns NULL when flow is not active.
   */
  public function testExecuteFlowNotActive(): void {
    $statusField = new \stdClass();
    $statusField->value = 'draft';

    $flow = $this->createMock(ContentEntityInterface::class);
    $flow->method('get')
      ->willReturnMap([
        ['flow_status', $statusField],
      ]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(1)->willReturn($flow);
    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $type) use ($storage) {
        return $storage;
      });

    $this->assertNull($this->service->executeFlow(1));
  }

  /**
   * Tests getExecutionResult returns empty when execution not found.
   */
  public function testGetExecutionResultNotFound(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(999)->willReturn(NULL);
    $this->entityTypeManager->method('getStorage')
      ->with('agent_flow_execution')
      ->willReturn($storage);

    $result = $this->service->getExecutionResult(999);
    $this->assertArrayHasKey('error', $result);
    $this->assertEquals('Ejecucion no encontrada.', $result['error']);
  }

}

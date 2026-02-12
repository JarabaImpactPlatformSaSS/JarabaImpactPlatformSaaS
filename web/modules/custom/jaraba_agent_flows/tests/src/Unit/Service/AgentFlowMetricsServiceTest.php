<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_agent_flows\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_agent_flows\Service\AgentFlowMetricsService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para AgentFlowMetricsService.
 *
 * @covers \Drupal\jaraba_agent_flows\Service\AgentFlowMetricsService
 * @group jaraba_agent_flows
 */
class AgentFlowMetricsServiceTest extends UnitTestCase {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected LoggerInterface $logger;
  protected AgentFlowMetricsService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new AgentFlowMetricsService(
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * Tests getDashboardMetrics returns default structure.
   */
  public function testGetDashboardMetricsReturnsDefaults(): void {
    $storage = $this->createMock(EntityStorageInterface::class);

    $queryMock = $this->createMock(\Drupal\Core\Entity\Query\QueryInterface::class);
    $queryMock->method('accessCheck')->willReturnSelf();
    $queryMock->method('count')->willReturnSelf();
    $queryMock->method('condition')->willReturnSelf();
    $queryMock->method('execute')->willReturn(0);

    $storage->method('getQuery')->willReturn($queryMock);

    $this->entityTypeManager->method('getStorage')
      ->willReturn($storage);

    $metrics = $this->service->getDashboardMetrics(NULL);

    $this->assertArrayHasKey('total_flows', $metrics);
    $this->assertArrayHasKey('total_executions', $metrics);
    $this->assertArrayHasKey('success_rate', $metrics);
    $this->assertArrayHasKey('avg_duration', $metrics);
  }

}

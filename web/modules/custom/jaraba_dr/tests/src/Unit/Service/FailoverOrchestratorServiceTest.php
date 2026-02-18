<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_dr\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\jaraba_dr\Service\FailoverOrchestratorService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for FailoverOrchestratorService.
 *
 * Covers failover initiation, status tracking, secondary health checks,
 * cancellation logic, and edge cases like concurrent failovers.
 *
 * @coversDefaultClass \Drupal\jaraba_dr\Service\FailoverOrchestratorService
 * @group jaraba_dr
 */
class FailoverOrchestratorServiceTest extends UnitTestCase {

  /**
   * The service under test.
   */
  protected FailoverOrchestratorService $service;

  /**
   * Mock entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock config factory.
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Mock state.
   */
  protected StateInterface $state;

  /**
   * Mock logger.
   */
  protected LoggerInterface $logger;

  /**
   * Mock config.
   */
  protected ImmutableConfig $config;

  /**
   * Tracking array for state values set during tests.
   *
   * @var array
   */
  protected array $stateStore = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set up Drupal container for TranslatableMarkup::__toString().
    $container = new \Drupal\Core\DependencyInjection\ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->state = $this->createMock(StateInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->config = $this->createMock(ImmutableConfig::class);

    $this->configFactory->method('get')
      ->with('jaraba_dr.settings')
      ->willReturn($this->config);

    // Default config: no secondary URL (single-node mode).
    $this->config->method('get')->willReturnCallback(function (?string $key) {
      return match ($key) {
        'secondary_url' => '',
        'secondary_health_endpoint' => '/health',
        'secondary_timeout_ms' => 5000,
        'secondary_max_latency_ms' => 2000,
        default => NULL,
      };
    });

    // Wire up state mock with tracking storage.
    $this->stateStore = [];

    $this->state->method('get')->willReturnCallback(function (string $key, $default = NULL) {
      return $this->stateStore[$key] ?? $default;
    });

    $this->state->method('set')->willReturnCallback(function (string $key, $value): void {
      $this->stateStore[$key] = $value;
    });

    // Mock DR test result storage for createFailoverTestResult.
    $drStorage = $this->createMock(EntityStorageInterface::class);
    $entity = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['save'])
      ->getMock();
    $drStorage->method('create')->willReturn($entity);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $type) use ($drStorage) {
        return match ($type) {
          'dr_test_result' => $drStorage,
          default => $this->createMock(EntityStorageInterface::class),
        };
      });

    $this->service = new FailoverOrchestratorService(
      $this->entityTypeManager,
      $this->configFactory,
      $this->state,
      $this->logger,
    );
  }

  // -----------------------------------------------------------------------
  // getFailoverStatus() tests
  // -----------------------------------------------------------------------

  /**
   * @covers ::getFailoverStatus
   */
  public function testGetFailoverStatusReturnsIdleByDefault(): void {
    $result = $this->service->getFailoverStatus();

    $this->assertEquals(FailoverOrchestratorService::STATUS_IDLE, $result['status']);
    $this->assertEquals('', $result['reason']);
    $this->assertEquals(0, $result['started_at']);
    $this->assertIsArray($result['steps']);
  }

  /**
   * @covers ::getFailoverStatus
   */
  public function testGetFailoverStatusReflectsStateValues(): void {
    $this->stateStore[FailoverOrchestratorService::STATE_KEY] = [
      'status' => FailoverOrchestratorService::STATUS_SWITCHING,
      'reason' => 'Primary down',
      'started_at' => 1700000000,
    ];

    $this->stateStore[FailoverOrchestratorService::STATE_LOG_KEY] = [
      ['action' => 'initiate', 'description' => 'Started'],
    ];

    $result = $this->service->getFailoverStatus();

    $this->assertEquals(FailoverOrchestratorService::STATUS_SWITCHING, $result['status']);
    $this->assertEquals('Primary down', $result['reason']);
    $this->assertEquals(1700000000, $result['started_at']);
    $this->assertCount(1, $result['steps']);
  }

  // -----------------------------------------------------------------------
  // checkSecondaryHealth() tests
  // -----------------------------------------------------------------------

  /**
   * @covers ::checkSecondaryHealth
   */
  public function testCheckSecondaryHealthReturnHealthyInSingleNodeMode(): void {
    // Default config has empty secondary_url (single-node mode).
    $result = $this->service->checkSecondaryHealth();

    $this->assertTrue($result['healthy']);
    $this->assertIsInt($result['latency_ms']);
    $this->assertArrayHasKey('last_sync', $result);
    $this->assertArrayHasKey('details', $result);
    $this->assertEquals('single_node', $result['details']['mode']);
  }

  // -----------------------------------------------------------------------
  // initiateFailover() tests
  // -----------------------------------------------------------------------

  /**
   * @covers ::initiateFailover
   */
  public function testInitiateFailoverRejectsWhenAlreadyInProgress(): void {
    // Simulate a failover already in progress.
    $this->stateStore[FailoverOrchestratorService::STATE_KEY] = [
      'status' => FailoverOrchestratorService::STATUS_SWITCHING,
      'reason' => 'Previous failover',
      'started_at' => time(),
    ];

    $result = $this->service->initiateFailover('New attempt');

    $this->assertFalse($result['success']);
    $this->assertEquals(FailoverOrchestratorService::STATUS_SWITCHING, $result['status']);
    $this->assertEmpty($result['steps']);
  }

  /**
   * @covers ::initiateFailover
   */
  public function testInitiateFailoverRejectsWhenInitiating(): void {
    $this->stateStore[FailoverOrchestratorService::STATE_KEY] = [
      'status' => FailoverOrchestratorService::STATUS_INITIATING,
      'reason' => 'Already starting',
      'started_at' => time(),
    ];

    $result = $this->service->initiateFailover('Another attempt');

    $this->assertFalse($result['success']);
  }

  /**
   * @covers ::initiateFailover
   */
  public function testInitiateFailoverRejectsWhenCheckingSecondary(): void {
    $this->stateStore[FailoverOrchestratorService::STATE_KEY] = [
      'status' => FailoverOrchestratorService::STATUS_CHECKING_SECONDARY,
      'reason' => 'Checking health',
      'started_at' => time(),
    ];

    $result = $this->service->initiateFailover('Yet another');

    $this->assertFalse($result['success']);
  }

  /**
   * @covers ::initiateFailover
   */
  public function testInitiateFailoverRejectsWhenVerifying(): void {
    $this->stateStore[FailoverOrchestratorService::STATE_KEY] = [
      'status' => FailoverOrchestratorService::STATUS_VERIFYING,
      'reason' => 'Post-switch verification',
      'started_at' => time(),
    ];

    $result = $this->service->initiateFailover('Blocked');

    $this->assertFalse($result['success']);
  }

  /**
   * @covers ::initiateFailover
   */
  public function testInitiateFailoverSucceedsInSingleNodeMode(): void {
    // In single-node mode (no secondary URL), health check returns healthy,
    // so failover should complete successfully.
    $result = $this->service->initiateFailover('DB replication lag detected');

    $this->assertTrue($result['success']);
    $this->assertEquals(FailoverOrchestratorService::STATUS_COMPLETED, $result['status']);
    $this->assertNotEmpty($result['steps']);
    $this->assertIsInt($result['duration_seconds']);
    $this->assertGreaterThanOrEqual(0, $result['duration_seconds']);
  }

  /**
   * @covers ::initiateFailover
   */
  public function testInitiateFailoverLogsStepsSequentially(): void {
    $result = $this->service->initiateFailover('Manual test');

    // Should have at minimum: initiate, health_check, switch_traffic,
    // dns_update, verify, complete.
    $this->assertGreaterThanOrEqual(5, count($result['steps']));

    // Verify each step has required keys.
    foreach ($result['steps'] as $step) {
      $this->assertArrayHasKey('timestamp', $step);
      $this->assertArrayHasKey('action', $step);
      $this->assertArrayHasKey('description', $step);
    }
  }

  /**
   * @covers ::initiateFailover
   */
  public function testInitiateFailoverAllowedAfterPreviousCompleted(): void {
    $this->stateStore[FailoverOrchestratorService::STATE_KEY] = [
      'status' => FailoverOrchestratorService::STATUS_COMPLETED,
      'reason' => 'Previous completed',
      'started_at' => time() - 600,
    ];

    $result = $this->service->initiateFailover('New failover after completed');

    $this->assertTrue($result['success']);
  }

  /**
   * @covers ::initiateFailover
   */
  public function testInitiateFailoverAllowedAfterPreviousFailed(): void {
    $this->stateStore[FailoverOrchestratorService::STATE_KEY] = [
      'status' => FailoverOrchestratorService::STATUS_FAILED,
      'reason' => 'Previous failed',
      'started_at' => time() - 600,
    ];

    $result = $this->service->initiateFailover('Retry after failure');

    $this->assertTrue($result['success']);
  }

  /**
   * @covers ::initiateFailover
   */
  public function testInitiateFailoverAllowedAfterCancelled(): void {
    $this->stateStore[FailoverOrchestratorService::STATE_KEY] = [
      'status' => FailoverOrchestratorService::STATUS_CANCELLED,
      'reason' => 'Previously cancelled',
      'started_at' => time() - 600,
    ];

    $result = $this->service->initiateFailover('New attempt after cancel');

    $this->assertTrue($result['success']);
  }

  /**
   * @covers ::initiateFailover
   */
  public function testInitiateFailoverUpdatesStateToCompleted(): void {
    $this->service->initiateFailover('State tracking test');

    $stateData = $this->stateStore[FailoverOrchestratorService::STATE_KEY] ?? [];
    $this->assertEquals(FailoverOrchestratorService::STATUS_COMPLETED, $stateData['status']);
  }

  // -----------------------------------------------------------------------
  // cancelFailover() tests
  // -----------------------------------------------------------------------

  /**
   * @covers ::cancelFailover
   */
  public function testCancelFailoverSucceedsWhenInitiating(): void {
    $this->stateStore[FailoverOrchestratorService::STATE_KEY] = [
      'status' => FailoverOrchestratorService::STATUS_INITIATING,
      'reason' => 'Starting',
      'started_at' => time(),
    ];

    $result = $this->service->cancelFailover('Operator aborted');

    $this->assertTrue($result['success']);
    $this->assertEquals(FailoverOrchestratorService::STATUS_INITIATING, $result['previous_status']);

    // State should reflect cancelled.
    $stateData = $this->stateStore[FailoverOrchestratorService::STATE_KEY];
    $this->assertEquals(FailoverOrchestratorService::STATUS_CANCELLED, $stateData['status']);
  }

  /**
   * @covers ::cancelFailover
   */
  public function testCancelFailoverSucceedsWhenSwitching(): void {
    $this->stateStore[FailoverOrchestratorService::STATE_KEY] = [
      'status' => FailoverOrchestratorService::STATUS_SWITCHING,
      'reason' => 'Switching traffic',
      'started_at' => time(),
    ];

    $result = $this->service->cancelFailover('Emergency rollback');

    $this->assertTrue($result['success']);
    $this->assertEquals(FailoverOrchestratorService::STATUS_SWITCHING, $result['previous_status']);
  }

  /**
   * @covers ::cancelFailover
   */
  public function testCancelFailoverFailsWhenIdle(): void {
    // Default state is idle.
    $result = $this->service->cancelFailover('Nothing to cancel');

    $this->assertFalse($result['success']);
    $this->assertEquals(FailoverOrchestratorService::STATUS_IDLE, $result['previous_status']);
  }

  /**
   * @covers ::cancelFailover
   */
  public function testCancelFailoverFailsWhenAlreadyCompleted(): void {
    $this->stateStore[FailoverOrchestratorService::STATE_KEY] = [
      'status' => FailoverOrchestratorService::STATUS_COMPLETED,
      'reason' => 'Already done',
      'started_at' => time() - 600,
    ];

    $result = $this->service->cancelFailover('Too late');

    $this->assertFalse($result['success']);
    $this->assertEquals(FailoverOrchestratorService::STATUS_COMPLETED, $result['previous_status']);
  }

  /**
   * @covers ::cancelFailover
   */
  public function testCancelFailoverFailsWhenAlreadyCancelled(): void {
    $this->stateStore[FailoverOrchestratorService::STATE_KEY] = [
      'status' => FailoverOrchestratorService::STATUS_CANCELLED,
      'reason' => 'Already cancelled',
      'started_at' => time() - 600,
    ];

    $result = $this->service->cancelFailover('Double cancel');

    $this->assertFalse($result['success']);
  }

  /**
   * @covers ::cancelFailover
   */
  public function testCancelFailoverFailsWhenFailed(): void {
    $this->stateStore[FailoverOrchestratorService::STATE_KEY] = [
      'status' => FailoverOrchestratorService::STATUS_FAILED,
      'reason' => 'Already failed',
      'started_at' => time() - 600,
    ];

    $result = $this->service->cancelFailover('Cannot cancel failed');

    $this->assertFalse($result['success']);
  }

  /**
   * @covers ::cancelFailover
   */
  public function testCancelFailoverLogsWarning(): void {
    $this->stateStore[FailoverOrchestratorService::STATE_KEY] = [
      'status' => FailoverOrchestratorService::STATUS_CHECKING_SECONDARY,
      'reason' => 'Health check',
      'started_at' => time(),
    ];

    $this->logger->expects($this->atLeastOnce())
      ->method('warning')
      ->with(
        $this->stringContains('FAILOVER CANCELADO'),
        $this->callback(fn(array $ctx) => str_contains($ctx['@reason'], 'Operator override'))
      );

    $this->service->cancelFailover('Operator override');
  }

  // -----------------------------------------------------------------------
  // Constants verification
  // -----------------------------------------------------------------------

  /**
   * Tests that all status constants have expected values.
   */
  public function testStatusConstantsAreDefined(): void {
    $this->assertEquals('idle', FailoverOrchestratorService::STATUS_IDLE);
    $this->assertEquals('initiating', FailoverOrchestratorService::STATUS_INITIATING);
    $this->assertEquals('checking_secondary', FailoverOrchestratorService::STATUS_CHECKING_SECONDARY);
    $this->assertEquals('switching', FailoverOrchestratorService::STATUS_SWITCHING);
    $this->assertEquals('verifying', FailoverOrchestratorService::STATUS_VERIFYING);
    $this->assertEquals('completed', FailoverOrchestratorService::STATUS_COMPLETED);
    $this->assertEquals('failed', FailoverOrchestratorService::STATUS_FAILED);
    $this->assertEquals('cancelled', FailoverOrchestratorService::STATUS_CANCELLED);
  }

  /**
   * Tests state key constants.
   */
  public function testStateKeyConstantsAreDefined(): void {
    $this->assertEquals('jaraba_dr.failover_status', FailoverOrchestratorService::STATE_KEY);
    $this->assertEquals('jaraba_dr.failover_log', FailoverOrchestratorService::STATE_LOG_KEY);
  }

}

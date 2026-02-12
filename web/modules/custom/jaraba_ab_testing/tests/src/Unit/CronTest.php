<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ab_testing\Unit;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\jaraba_ab_testing\Service\ResultCalculationService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

// Load the .module file for access to procedural functions.
require_once dirname(__DIR__, 3) . '/jaraba_ab_testing.module';

/**
 * Unit tests for jaraba_ab_testing hook_cron auto-winner evaluation.
 *
 * @group jaraba_ab_testing
 */
class CronTest extends TestCase {

  /**
   * Mocked state service.
   */
  protected $state;

  /**
   * Mocked result calculation service.
   */
  protected $resultCalc;

  /**
   * Mocked entity type manager.
   */
  protected $entityTypeManager;

  /**
   * Mocked logger.
   */
  protected $logger;

  /**
   * Current mock time.
   */
  protected int $currentTime = 1700000000;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->state = $this->createMock(StateInterface::class);
    $this->resultCalc = $this->createMock(ResultCalculationService::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $loggerFactory->method('get')->willReturn($this->logger);

    $time = $this->createMock(TimeInterface::class);
    $time->method('getRequestTime')->willReturn($this->currentTime);

    $container = new ContainerBuilder();
    $container->set('state', $this->state);
    $container->set('datetime.time', $time);
    $container->set('entity_type.manager', $this->entityTypeManager);
    $container->set('jaraba_ab_testing.result_calculation', $this->resultCalc);
    $container->set('logger.factory', $loggerFactory);
    \Drupal::setContainer($container);
  }

  /**
   * Tests auto-winner runs when more than 6 hours since last run.
   *
   * @covers ::_jaraba_ab_testing_cron_auto_winner
   */
  public function testAutoWinnerRunsAfter6Hours(): void {
    $sevenHoursAgo = $this->currentTime - (7 * 3600);

    $this->state->method('get')
      ->with('jaraba_ab_testing.last_auto_winner_check', 0)
      ->willReturn($sevenHoursAgo);

    // Mock: no active experiments.
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('ab_experiment')
      ->willReturn($storage);

    $this->state->expects($this->once())
      ->method('set')
      ->with('jaraba_ab_testing.last_auto_winner_check', $this->currentTime);

    _jaraba_ab_testing_cron_auto_winner($this->currentTime);
  }

  /**
   * Tests auto-winner skips when less than 6 hours since last run.
   *
   * @covers ::_jaraba_ab_testing_cron_auto_winner
   */
  public function testAutoWinnerSkipsBefore6Hours(): void {
    $twoHoursAgo = $this->currentTime - (2 * 3600);

    $this->state->method('get')
      ->with('jaraba_ab_testing.last_auto_winner_check', 0)
      ->willReturn($twoHoursAgo);

    // entityTypeManager should never be called.
    $this->entityTypeManager->expects($this->never())->method('getStorage');

    _jaraba_ab_testing_cron_auto_winner($this->currentTime);
  }

  /**
   * Tests auto-winner evaluates active experiments.
   *
   * @covers ::_jaraba_ab_testing_cron_auto_winner
   */
  public function testAutoWinnerEvaluatesExperiments(): void {
    $this->state->method('get')
      ->with('jaraba_ab_testing.last_auto_winner_check', 0)
      ->willReturn(0);

    // Mock: 2 active experiments.
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([10, 20]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('ab_experiment')
      ->willReturn($storage);

    // Experiment 10: winner found, experiment 20: no winner.
    $this->resultCalc->expects($this->exactly(2))
      ->method('checkAutoStop')
      ->willReturnMap([
        [10, TRUE],
        [20, FALSE],
      ]);

    $this->state->expects($this->once())
      ->method('set')
      ->with('jaraba_ab_testing.last_auto_winner_check', $this->currentTime);

    _jaraba_ab_testing_cron_auto_winner($this->currentTime);
  }

  /**
   * Tests auto-winner handles individual experiment errors gracefully.
   *
   * @covers ::_jaraba_ab_testing_cron_auto_winner
   */
  public function testAutoWinnerHandlesExperimentErrors(): void {
    $this->state->method('get')
      ->with('jaraba_ab_testing.last_auto_winner_check', 0)
      ->willReturn(0);

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([10]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('ab_experiment')
      ->willReturn($storage);

    // Experiment 10 throws error, should be caught.
    $this->resultCalc->method('checkAutoStop')
      ->willThrowException(new \Exception('DB error'));

    $this->logger->expects($this->once())
      ->method('error')
      ->with(
        'Error evaluating experiment @id: @message',
        $this->callback(function ($ctx) {
          return $ctx['@id'] === 10 && $ctx['@message'] === 'DB error';
        })
      );

    // Should still update state after error.
    $this->state->expects($this->once())
      ->method('set')
      ->with('jaraba_ab_testing.last_auto_winner_check', $this->currentTime);

    _jaraba_ab_testing_cron_auto_winner($this->currentTime);
  }

}

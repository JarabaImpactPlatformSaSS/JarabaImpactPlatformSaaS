<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ab_testing\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\jaraba_ab_testing\Service\ExperimentOrchestratorService;
use Drupal\jaraba_ab_testing\Service\ResultCalculationService;
use Drupal\jaraba_ab_testing\Service\StatisticalEngineService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for ExperimentOrchestratorService.
 *
 * @group jaraba_ab_testing
 * @coversDefaultClass \Drupal\jaraba_ab_testing\Service\ExperimentOrchestratorService
 */
class ExperimentOrchestratorServiceTest extends TestCase {

  /**
   * The service under test.
   */
  protected ExperimentOrchestratorService $service;

  /**
   * Mocked entity type manager.
   */
  protected $entityTypeManager;

  /**
   * Mocked result calculation service.
   */
  protected $resultCalc;

  /**
   * Mocked statistical engine.
   */
  protected $statEngine;

  /**
   * Mocked mail manager.
   */
  protected $mailManager;

  /**
   * Mocked logger.
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->resultCalc = $this->createMock(ResultCalculationService::class);
    $this->statEngine = $this->createMock(StatisticalEngineService::class);
    $this->mailManager = $this->createMock(MailManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new ExperimentOrchestratorService(
      $this->entityTypeManager,
      $this->resultCalc,
      $this->statEngine,
      $this->mailManager,
      $this->logger,
    );
  }

  /**
   * Tests evaluateAll returns empty summary when no active experiments.
   *
   * @covers ::evaluateAll
   */
  public function testEvaluateAllNoExperiments(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('ab_experiment')
      ->willReturn($storage);

    $result = $this->service->evaluateAll();

    $this->assertSame(0, $result['evaluated']);
    $this->assertSame(0, $result['winners_declared']);
  }

  /**
   * Tests evaluateAll processes multiple experiments.
   *
   * @covers ::evaluateAll
   */
  public function testEvaluateAllProcessesExperiments(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([1, 2, 3]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('ab_experiment')
      ->willReturn($storage);

    // Experiment 1: has results, auto-stop → winner.
    // Experiment 2: has results, no auto-stop → skipped.
    // Experiment 3: no results → not ready.
    $this->resultCalc->method('calculateResults')
      ->willReturnMap([
        [1, ['variants' => ['a' => 100, 'b' => 120]]],
        [2, ['variants' => ['a' => 50, 'b' => 55]]],
        [3, []],
      ]);

    $this->resultCalc->method('checkAutoStop')
      ->willReturnMap([
        [1, TRUE],
        [2, FALSE],
      ]);

    $result = $this->service->evaluateAll();

    $this->assertSame(3, $result['evaluated']);
    $this->assertSame(1, $result['winners_declared']);
    $this->assertSame(2, $result['skipped']);
    $this->assertSame(0, $result['errors']);
  }

  /**
   * Tests evaluateAll handles experiment errors gracefully.
   *
   * @covers ::evaluateAll
   */
  public function testEvaluateAllHandlesErrors(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([1]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('ab_experiment')
      ->willReturn($storage);

    $this->resultCalc->method('calculateResults')
      ->willThrowException(new \Exception('DB error'));

    $this->logger->expects($this->once())->method('error');

    $result = $this->service->evaluateAll();

    $this->assertSame(1, $result['evaluated']);
    $this->assertSame(1, $result['errors']);
  }

  /**
   * Tests evaluateExperiment returns not_ready when no results.
   *
   * @covers ::evaluateExperiment
   */
  public function testEvaluateExperimentNotReady(): void {
    $this->resultCalc->method('calculateResults')
      ->with(42)
      ->willReturn([]);

    $result = $this->service->evaluateExperiment(42);
    $this->assertSame('not_ready', $result);
  }

  /**
   * Tests evaluateExperiment returns winner_declared when significant.
   *
   * @covers ::evaluateExperiment
   */
  public function testEvaluateExperimentWinnerDeclared(): void {
    $this->resultCalc->method('calculateResults')
      ->with(10)
      ->willReturn(['some' => 'results']);

    $this->resultCalc->method('checkAutoStop')
      ->with(10)
      ->willReturn(TRUE);

    $result = $this->service->evaluateExperiment(10);
    $this->assertSame('winner_declared', $result);
  }

}

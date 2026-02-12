<?php

declare(strict_types=1);

namespace Drupal\jaraba_ab_testing\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Orchestrates automatic experiment evaluation and winner declaration.
 *
 * Coordinates the automated lifecycle of A/B experiments:
 * 1. Identifies experiments eligible for auto-evaluation.
 * 2. Delegates statistical analysis to ResultCalculationService.
 * 3. Declares winners when significance is reached.
 * 4. Sends notification emails to experiment owners.
 *
 * Ref: Spec 20260130b §8.4
 */
class ExperimentOrchestratorService {

  /**
   * Entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Result calculation service.
   */
  protected ResultCalculationService $resultCalculation;

  /**
   * Statistical engine service.
   */
  protected StatisticalEngineService $statisticalEngine;

  /**
   * Mail manager.
   */
  protected MailManagerInterface $mailManager;

  /**
   * Logger.
   */
  protected LoggerInterface $logger;

  /**
   * Constructs an ExperimentOrchestratorService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\jaraba_ab_testing\Service\ResultCalculationService $result_calculation
   *   Result calculation service.
   * @param \Drupal\jaraba_ab_testing\Service\StatisticalEngineService $statistical_engine
   *   Statistical engine service.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   Mail manager for notifications.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger channel.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ResultCalculationService $result_calculation,
    StatisticalEngineService $statistical_engine,
    MailManagerInterface $mail_manager,
    LoggerInterface $logger,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->resultCalculation = $result_calculation;
    $this->statisticalEngine = $statistical_engine;
    $this->mailManager = $mail_manager;
    $this->logger = $logger;
  }

  /**
   * Evaluates all eligible experiments for auto-winner declaration.
   *
   * @return array
   *   Summary with keys:
   *   - evaluated: Total experiments evaluated.
   *   - winners_declared: Number of experiments where winner was declared.
   *   - skipped: Number of experiments not ready (insufficient data/time).
   *   - errors: Number of experiments that encountered errors.
   */
  public function evaluateAll(): array {
    $summary = [
      'evaluated' => 0,
      'winners_declared' => 0,
      'skipped' => 0,
      'errors' => 0,
    ];

    try {
      $experimentIds = $this->entityTypeManager
        ->getStorage('ab_experiment')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 'active')
        ->condition('auto_complete', TRUE)
        ->execute();

      if (empty($experimentIds)) {
        return $summary;
      }

      foreach ($experimentIds as $experimentId) {
        $summary['evaluated']++;
        try {
          $result = $this->evaluateExperiment((int) $experimentId);
          if ($result === 'winner_declared') {
            $summary['winners_declared']++;
          }
          else {
            $summary['skipped']++;
          }
        }
        catch (\Exception $e) {
          $summary['errors']++;
          $this->logger->error('Error orchestrating experiment @id: @message', [
            '@id' => $experimentId,
            '@message' => $e->getMessage(),
          ]);
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Orchestrator failed to query experiments: @message', [
        '@message' => $e->getMessage(),
      ]);
    }

    return $summary;
  }

  /**
   * Evaluates a single experiment for auto-winner.
   *
   * Checks if the experiment has reached statistical significance
   * and declares a winner if conditions are met.
   *
   * @param int $experimentId
   *   Experiment entity ID.
   *
   * @return string
   *   Result: 'winner_declared', 'not_ready', or 'no_significance'.
   */
  public function evaluateExperiment(int $experimentId): string {
    // Calculate results.
    $results = $this->resultCalculation->calculateResults($experimentId);

    if (empty($results)) {
      return 'not_ready';
    }

    // Check auto-stop conditions (sample size, runtime, significance).
    $shouldStop = $this->resultCalculation->checkAutoStop($experimentId);

    if (!$shouldStop) {
      return 'not_ready';
    }

    $this->logger->info('Auto-winner declared for experiment @id via orchestrator.', [
      '@id' => $experimentId,
    ]);

    return 'winner_declared';
  }

  /**
   * Sends a winner notification email.
   *
   * @param int $experimentId
   *   Experiment entity ID.
   * @param string $winnerVariantId
   *   The winning variant identifier.
   * @param string $recipientEmail
   *   Email address to send notification to.
   */
  public function sendWinnerNotification(int $experimentId, string $winnerVariantId, string $recipientEmail): void {
    try {
      $experiment = $this->entityTypeManager
        ->getStorage('ab_experiment')
        ->load($experimentId);

      if (!$experiment) {
        return;
      }

      $params = [
        'experiment_name' => $experiment->label(),
        'experiment_id' => $experimentId,
        'winner_variant' => $winnerVariantId,
      ];

      $this->mailManager->mail(
        'jaraba_ab_testing',
        'experiment_winner',
        $recipientEmail,
        'es',
        $params,
      );

      $this->logger->info('Winner notification sent for experiment @id to @email.', [
        '@id' => $experimentId,
        '@email' => $recipientEmail,
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to send winner notification for experiment @id: @message', [
        '@id' => $experimentId,
        '@message' => $e->getMessage(),
      ]);
    }
  }

}

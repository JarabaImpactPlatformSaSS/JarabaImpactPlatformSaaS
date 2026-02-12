<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de validacion BMC con logica de semaforos.
 *
 * Para cada bloque del Business Model Canvas:
 * - RED:    ratio < 0.33 (pocas hipotesis validadas)
 * - YELLOW: 0.33 <= ratio < 0.66
 * - GREEN:  ratio >= 0.66
 * - GRAY:   sin hipotesis (bloque no explorado)
 */
class BmcValidationService {

  /**
   * Los 9 bloques del Business Model Canvas.
   */
  const BMC_BLOCKS = [
    'CS' => 'Customer Segments',
    'VP' => 'Value Propositions',
    'CH' => 'Channels',
    'CR' => 'Customer Relationships',
    'RS' => 'Revenue Streams',
    'KR' => 'Key Resources',
    'KA' => 'Key Activities',
    'KP' => 'Key Partnerships',
    'C$' => 'Cost Structure',
  ];

  protected EntityTypeManagerInterface $entityTypeManager;
  protected LoggerInterface $logger;

  /**
   * Constructor.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    LoggerInterface $logger
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $logger;
  }

  /**
   * Obtiene el estado de validacion completo del BMC para un usuario.
   *
   * @param int $userId
   *   ID del usuario.
   *
   * @return array
   *   Estado por bloque con semaforos.
   */
  public function getValidationState(int $userId): array {
    $storage = $this->entityTypeManager->getStorage('hypothesis');
    $blocks = [];
    $totalValidated = 0;
    $totalHypotheses = 0;

    foreach (self::BMC_BLOCKS as $code => $label) {
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->condition('bmc_block', $code);

      $allIds = $query->execute();
      $total = count($allIds);

      $validated = 0;
      if ($total > 0) {
        $validatedQuery = $storage->getQuery()
          ->accessCheck(FALSE)
          ->condition('user_id', $userId)
          ->condition('bmc_block', $code)
          ->condition('validation_status', 'VALIDATED');
        $validated = (int) $validatedQuery->count()->execute();
      }

      $ratio = $total > 0 ? $validated / $total : 0;
      $semaphore = $this->calculateSemaphore($total, $ratio);

      $blocks[$code] = [
        'code' => $code,
        'label' => $label,
        'total_hypotheses' => $total,
        'validated_hypotheses' => $validated,
        'ratio' => round($ratio, 2),
        'semaphore' => $semaphore,
        'percentage' => round($ratio * 100),
      ];

      $totalValidated += $validated;
      $totalHypotheses += $total;
    }

    $overallRatio = $totalHypotheses > 0 ? $totalValidated / $totalHypotheses : 0;

    return [
      'blocks' => $blocks,
      'overall' => [
        'total_hypotheses' => $totalHypotheses,
        'validated_hypotheses' => $totalValidated,
        'ratio' => round($overallRatio, 2),
        'percentage' => round($overallRatio * 100),
        'semaphore' => $this->calculateSemaphore($totalHypotheses, $overallRatio),
      ],
    ];
  }

  /**
   * Calcula el color del semaforo.
   */
  protected function calculateSemaphore(int $total, float $ratio): string {
    if ($total === 0) {
      return 'GRAY';
    }
    if ($ratio >= 0.66) {
      return 'GREEN';
    }
    if ($ratio >= 0.33) {
      return 'YELLOW';
    }
    return 'RED';
  }

  /**
   * Obtiene el log de pivots de un usuario.
   *
   * @param int $userId
   *   ID del usuario.
   *
   * @return array
   *   Historial de decisiones de pivot.
   */
  public function getPivotLog(int $userId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('experiment');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->condition('status', 'COMPLETED')
        ->condition('decision', ['PIVOT', 'ZOOM_IN', 'ZOOM_OUT', 'KILL'], 'IN')
        ->sort('changed', 'DESC')
        ->execute();

      $pivots = [];
      foreach ($storage->loadMultiple($ids) as $experiment) {
        $hypothesisStatement = '';
        $hypothesisId = $experiment->get('hypothesis')->target_id;
        if ($hypothesisId) {
          $hypothesis = $this->entityTypeManager->getStorage('hypothesis')->load($hypothesisId);
          if ($hypothesis) {
            $hypothesisStatement = $hypothesis->get('statement')->value;
          }
        }

        $pivots[] = [
          'experiment_id' => (int) $experiment->id(),
          'experiment_title' => $experiment->get('title')->value,
          'decision' => $experiment->get('decision')->value,
          'hypothesis_id' => $hypothesisId,
          'hypothesis_statement' => $hypothesisStatement,
          'observations' => $experiment->get('observations')->value,
          'next_steps' => $experiment->get('next_steps')->value,
          'date' => (int) $experiment->get('changed')->value,
        ];
      }

      return $pivots;
    }
    catch (\Exception $e) {
      $this->logger->error('Error getting pivot log: @msg', ['@msg' => $e->getMessage()]);
      return [];
    }
  }

}

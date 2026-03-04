<?php

declare(strict_types=1);

namespace Drupal\jaraba_pilot_manager\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de evaluacion de programas piloto.
 *
 * ESTRUCTURA:
 * Calcula metricas de conversion, NPS, y probabilidad de conversion
 * para programas piloto y sus tenants inscritos.
 *
 * LOGICA:
 * - evaluatePilot(): Agrega metricas de todos los tenants de un programa.
 * - evaluateTenant(): Extrae metricas individuales de un tenant.
 * - getConversionProbability(): Calcula probabilidad ponderada de conversion.
 * - generateReport(): Genera informe completo con metricas agregadas.
 */
class PilotEvaluatorService {

  /**
   * Constructs a PilotEvaluatorService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param object|null $activationTracking
   *   Optional activation tracking service.
   * @param object|null $retentionCalculator
   *   Optional retention calculator service.
   * @param \Psr\Log\LoggerInterface|null $logger
   *   The logger channel.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ?object $activationTracking = NULL,
    protected ?object $retentionCalculator = NULL,
    protected ?LoggerInterface $logger = NULL,
  ) {}

  /**
   * Evaluates a pilot program's aggregate metrics.
   *
   * @param object $program
   *   The pilot program entity.
   *
   * @return array
   *   Associative array with program_id, total_enrolled, total_converted,
   *   conversion_rate, and avg_nps.
   */
  public function evaluatePilot(object $program): array {
    $tenants = $this->loadPilotTenants((int) $program->id());
    $totalEnrolled = count($tenants);
    $converted = 0;
    $totalNps = 0.0;
    $npsCount = 0;

    foreach ($tenants as $tenant) {
      if (($tenant->get('status')->value ?? '') === 'converted') {
        $converted++;
      }
    }

    // Load feedback for NPS calculation.
    try {
      $feedbackStorage = $this->entityTypeManager->getStorage('pilot_feedback');
      foreach ($tenants as $tenant) {
        $feedbackIds = $feedbackStorage->getQuery()
          ->accessCheck(FALSE)
          ->condition('pilot_tenant', $tenant->id())
          ->condition('feedback_type', 'nps')
          ->execute();
        $feedbacks = $feedbackIds !== [] ? $feedbackStorage->loadMultiple($feedbackIds) : [];
        foreach ($feedbacks as $feedback) {
          /** @var \Drupal\Core\Entity\ContentEntityInterface $feedback */
          $score = $feedback->get('score')->value;
          if ($score !== NULL) {
            $totalNps += (float) $score;
            $npsCount++;
          }
        }
      }
    }
    catch (\Throwable) {
      // Feedback loading should not break evaluation.
    }

    $conversionRate = $totalEnrolled > 0 ? $converted / $totalEnrolled : 0.0;

    return [
      'program_id' => $program->id(),
      'total_enrolled' => $totalEnrolled,
      'total_converted' => $converted,
      'conversion_rate' => round($conversionRate, 4),
      'avg_nps' => $npsCount > 0 ? round($totalNps / $npsCount, 1) : 0.0,
    ];
  }

  /**
   * Evaluates an individual pilot tenant's metrics.
   *
   * @param object $tenant
   *   The pilot tenant entity.
   *
   * @return array
   *   Associative array with tenant metrics.
   */
  public function evaluateTenant(object $tenant): array {
    return [
      'tenant_id' => $tenant->id(),
      'activation_score' => (float) ($tenant->get('activation_score')->value ?? 0),
      'retention_d30' => (float) ($tenant->get('retention_d30')->value ?? 0),
      'engagement_score' => (float) ($tenant->get('engagement_score')->value ?? 0),
      'churn_risk' => $tenant->get('churn_risk')->value ?? 'unknown',
      'onboarding_completed' => (bool) ($tenant->get('onboarding_completed')->value ?? FALSE),
    ];
  }

  /**
   * Calculates the probability of conversion for a tenant.
   *
   * Uses a weighted scoring model:
   * - Activation score: 30%
   * - Retention D30: 30%
   * - Engagement score: 25%
   * - Onboarding completed: 15% bonus
   *
   * @param object $tenant
   *   The pilot tenant entity.
   *
   * @return float
   *   Probability between 0.0 and 1.0.
   */
  public function getConversionProbability(object $tenant): float {
    $activation = (float) ($tenant->get('activation_score')->value ?? 0);
    $retention = (float) ($tenant->get('retention_d30')->value ?? 0);
    $engagement = (float) ($tenant->get('engagement_score')->value ?? 0);
    $onboarded = (bool) ($tenant->get('onboarding_completed')->value ?? FALSE);

    // Weighted score.
    $score = ($activation * 0.3) + ($retention * 0.3) + ($engagement * 0.25) + ($onboarded ? 15.0 : 0.0);
    return min(max($score / 100.0, 0.0), 1.0);
  }

  /**
   * Generates a complete evaluation report for a pilot program.
   *
   * @param object $program
   *   The pilot program entity.
   *
   * @return array
   *   Report with program metrics, individual tenant evaluations,
   *   and generation timestamp.
   */
  public function generateReport(object $program): array {
    $evaluation = $this->evaluatePilot($program);
    $tenants = $this->loadPilotTenants((int) $program->id());
    $tenantEvals = [];
    foreach ($tenants as $tenant) {
      $tenantEvals[] = $this->evaluateTenant($tenant);
    }

    return [
      'program' => $evaluation,
      'tenants' => $tenantEvals,
      'generated_at' => date('Y-m-d\TH:i:s'),
    ];
  }

  /**
   * Loads pilot tenants for a given program.
   *
   * @param int $programId
   *   The pilot program entity ID.
   *
   * @return array
   *   Array of PilotTenant entities.
   */
  protected function loadPilotTenants(int $programId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('pilot_tenant');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('pilot_program', $programId)
        ->execute();
      return $ids !== [] ? $storage->loadMultiple($ids) : [];
    }
    catch (\Throwable) {
      return [];
    }
  }

}

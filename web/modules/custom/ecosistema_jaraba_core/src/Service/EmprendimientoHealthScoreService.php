<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Health score calculator specific to the Emprendimiento vertical.
 *
 * Calculates user-level health across 5 weighted dimensions and provides
 * 8 aggregate KPIs for the vertical.
 *
 * Dimensions:
 * - canvas_completeness (25%): BmcValidationService semaphore score
 * - hypothesis_validation (30%): Validated / total hypotheses
 * - experiment_velocity (15%): Experiments in last 30 days
 * - copilot_engagement (15%): Copilot conversation usage
 * - funding_readiness (15%): Funding applications + mentor sessions
 *
 * Plan Elevación Emprendimiento v2 — Fase 1 (G1).
 */
class EmprendimientoHealthScoreService {

  /**
   * Health score dimensions with weights.
   */
  protected const DIMENSIONS = [
    'canvas_completeness' => ['weight' => 0.25, 'label' => 'Completitud del canvas'],
    'hypothesis_validation' => ['weight' => 0.30, 'label' => 'Validación de hipótesis'],
    'experiment_velocity' => ['weight' => 0.15, 'label' => 'Velocidad de experimentación'],
    'copilot_engagement' => ['weight' => 0.15, 'label' => 'Uso del copilot'],
    'funding_readiness' => ['weight' => 0.15, 'label' => 'Preparación para financiación'],
  ];

  /**
   * Target values for vertical KPIs.
   */
  protected const KPI_TARGETS = [
    'startup_survival_rate' => 80,
    'time_to_mvp' => 60,
    'hypothesis_validation_rate' => 50,
    'activation_rate' => 60,
    'mentor_engagement' => 40,
    'nps' => 50,
    'arpu' => 12,
    'conversion_free_paid' => 8,
  ];

  /**
   * Constructor.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly Connection $database,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Calculates health score for a single user.
   *
   * @return array
   *   Array with: user_id, overall_score (0-100), category (healthy|neutral|
   *   at_risk|critical), dimensions (per-dimension breakdown).
   */
  public function calculateUserHealth(int $userId): array {
    $dimensions = [];
    $overall = 0.0;

    foreach (self::DIMENSIONS as $key => $config) {
      $score = $this->calculateDimension($userId, $key);
      $weighted = round($score * $config['weight']);
      $dimensions[$key] = [
        'score' => $score,
        'weight' => $config['weight'],
        'label' => $config['label'],
        'weighted' => $weighted,
      ];
      $overall += $score * $config['weight'];
    }

    $overallScore = (int) round($overall);

    return [
      'user_id' => $userId,
      'overall_score' => $overallScore,
      'category' => $this->categorize($overallScore),
      'dimensions' => $dimensions,
    ];
  }

  /**
   * Calculates aggregate KPIs for the Emprendimiento vertical.
   *
   * @return array
   *   Array of KPIs with: value, target, status (on_track|behind|ahead).
   */
  public function calculateVerticalKpis(): array {
    $kpis = [];

    $kpis['startup_survival_rate'] = $this->calculateStartupSurvivalRate();
    $kpis['time_to_mvp'] = $this->calculateTimeToMvp();
    $kpis['hypothesis_validation_rate'] = $this->calculateHypothesisValidationRate();
    $kpis['activation_rate'] = $this->calculateActivationRate();
    $kpis['mentor_engagement'] = $this->calculateMentorEngagement();
    $kpis['nps'] = $this->calculateNps();
    $kpis['arpu'] = $this->calculateArpu();
    $kpis['conversion_free_paid'] = $this->calculateConversionRate();

    return $kpis;
  }

  /**
   * Calculates a single dimension score (0-100).
   */
  protected function calculateDimension(int $userId, string $key): int {
    return match ($key) {
      'canvas_completeness' => $this->calculateCanvasCompleteness($userId),
      'hypothesis_validation' => $this->calculateHypothesisValidation($userId),
      'experiment_velocity' => $this->calculateExperimentVelocity($userId),
      'copilot_engagement' => $this->calculateCopilotEngagement($userId),
      'funding_readiness' => $this->calculateFundingReadiness($userId),
      default => 0,
    };
  }

  /**
   * Canvas completeness dimension (0-100).
   *
   * Uses BmcValidationService semaphore score averaged over 9 blocks.
   * GREEN=100, YELLOW=60, RED=20, GRAY=0.
   */
  protected function calculateCanvasCompleteness(int $userId): int {
    if (!\Drupal::hasService('jaraba_copilot_v2.bmc_validation')) {
      return 0;
    }

    try {
      $canvases = $this->entityTypeManager
        ->getStorage('business_model_canvas')
        ->loadByProperties(['user_id' => $userId]);

      if (empty($canvases)) {
        return 0;
      }

      $canvas = reset($canvases);
      $validation = \Drupal::service('jaraba_copilot_v2.bmc_validation')
        ->validateCanvas($canvas);

      $semaphoreScores = [
        'GREEN' => 100,
        'YELLOW' => 60,
        'RED' => 20,
        'GRAY' => 0,
      ];

      $totalScore = 0;
      $blockCount = 0;
      foreach ($validation['blocks'] ?? [] as $block) {
        $status = strtoupper($block['status'] ?? 'GRAY');
        $totalScore += $semaphoreScores[$status] ?? 0;
        $blockCount++;
      }

      return $blockCount > 0 ? (int) round($totalScore / $blockCount) : 0;
    }
    catch (\Exception $e) {
      return 0;
    }
  }

  /**
   * Hypothesis validation dimension (0-100).
   *
   * Score = (validated / total) * 100.
   */
  protected function calculateHypothesisValidation(int $userId): int {
    try {
      $hypotheses = $this->entityTypeManager
        ->getStorage('hypothesis')
        ->loadByProperties(['user_id' => $userId]);

      $total = count($hypotheses);
      if ($total === 0) {
        return 0;
      }

      $validated = 0;
      foreach ($hypotheses as $hypothesis) {
        $status = strtoupper($hypothesis->get('status')->value ?? '');
        if ($status === 'VALIDATED') {
          $validated++;
        }
      }

      return (int) round(($validated / $total) * 100);
    }
    catch (\Exception $e) {
      return 0;
    }
  }

  /**
   * Experiment velocity dimension (0-100).
   *
   * Score = min(100, experiments_last_30d * 20).
   */
  protected function calculateExperimentVelocity(int $userId): int {
    try {
      $thirtyDaysAgo = \Drupal::time()->getRequestTime() - (30 * 86400);

      $count = (int) $this->entityTypeManager
        ->getStorage('experiment')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->condition('created', $thirtyDaysAgo, '>=')
        ->count()
        ->execute();

      return min(100, $count * 20);
    }
    catch (\Exception $e) {
      return 0;
    }
  }

  /**
   * Copilot engagement dimension (0-100).
   *
   * Scoring: conversation count (max 50) + recent usage (max 50).
   */
  protected function calculateCopilotEngagement(int $userId): int {
    try {
      $conversations = $this->entityTypeManager
        ->getStorage('copilot_conversation')
        ->loadByProperties(['user_id' => $userId]);

      $total = count($conversations);
      if ($total === 0) {
        return 0;
      }

      // Conversation count: 5 conversations = 50 points.
      $countScore = min(50, $total * 10);

      // Recent usage within 7 days.
      $now = \Drupal::time()->getRequestTime();
      $recentCount = 0;
      foreach ($conversations as $conv) {
        $created = (int) ($conv->get('created')->value ?? 0);
        if (($now - $created) <= (7 * 86400)) {
          $recentCount++;
        }
      }
      $recentScore = min(50, $recentCount * 25);

      return min(100, $countScore + $recentScore);
    }
    catch (\Exception $e) {
      return 0;
    }
  }

  /**
   * Funding readiness dimension (0-100).
   *
   * Scoring: funding applications (max 50) + mentor sessions (max 50).
   */
  protected function calculateFundingReadiness(int $userId): int {
    $score = 0;

    // Funding applications: 2 = 50 points.
    try {
      $fundingApps = (int) $this->entityTypeManager
        ->getStorage('funding_application')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->count()
        ->execute();
      $score += min(50, $fundingApps * 25);
    }
    catch (\Exception $e) {
      // funding_application entity may not exist.
    }

    // Mentor sessions: 2 = 50 points.
    try {
      $mentorSessions = (int) $this->entityTypeManager
        ->getStorage('mentoring_session')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->count()
        ->execute();
      $score += min(50, $mentorSessions * 25);
    }
    catch (\Exception $e) {
      // mentoring_session entity may not exist.
    }

    return min(100, $score);
  }

  /**
   * KPI: Startup survival rate (% with activity >6 months).
   */
  protected function calculateStartupSurvivalRate(): array {
    try {
      $totalProfiles = (int) $this->entityTypeManager
        ->getStorage('entrepreneur_profile')
        ->getQuery()
        ->accessCheck(FALSE)
        ->count()
        ->execute();

      if ($totalProfiles === 0) {
        return ['value' => 0, 'unit' => '%', 'target' => 80, 'status' => 'behind', 'label' => 'Tasa de supervivencia'];
      }

      $sixMonthsAgo = \Drupal::time()->getRequestTime() - (180 * 86400);
      $activeCount = (int) $this->entityTypeManager
        ->getStorage('entrepreneur_profile')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('created', $sixMonthsAgo, '<=')
        ->condition('status', 1)
        ->count()
        ->execute();

      $oldProfiles = (int) $this->entityTypeManager
        ->getStorage('entrepreneur_profile')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('created', $sixMonthsAgo, '<=')
        ->count()
        ->execute();

      $rate = $oldProfiles > 0 ? round(($activeCount / $oldProfiles) * 100, 1) : 0;
      $target = self::KPI_TARGETS['startup_survival_rate'];

      return [
        'value' => $rate,
        'unit' => '%',
        'target' => $target,
        'status' => $rate >= $target ? 'on_track' : 'behind',
        'label' => 'Tasa de supervivencia',
      ];
    }
    catch (\Exception $e) {
      return ['value' => 0, 'unit' => '%', 'target' => 80, 'status' => 'behind', 'label' => 'Tasa de supervivencia'];
    }
  }

  /**
   * KPI: Average time to MVP (days).
   */
  protected function calculateTimeToMvp(): array {
    try {
      $experiments = $this->entityTypeManager
        ->getStorage('experiment')
        ->loadByProperties(['decision' => 'VALIDATED']);

      if (empty($experiments)) {
        return ['value' => 0, 'unit' => 'días', 'target' => 60, 'status' => 'on_track', 'label' => 'Tiempo a MVP'];
      }

      $totalDays = 0;
      $count = 0;
      foreach ($experiments as $experiment) {
        $userId = (int) ($experiment->get('user_id')->target_id ?? 0);
        if (!$userId) {
          continue;
        }

        // Get idea registration date from entrepreneur_profile.
        $profiles = $this->entityTypeManager
          ->getStorage('entrepreneur_profile')
          ->loadByProperties(['uid' => $userId]);
        if (empty($profiles)) {
          continue;
        }

        $profile = reset($profiles);
        $ideaDate = (int) ($profile->get('created')->value ?? 0);
        $validatedDate = (int) ($experiment->get('changed')->value ?? 0);

        if ($ideaDate > 0 && $validatedDate > $ideaDate) {
          $totalDays += ($validatedDate - $ideaDate) / 86400;
          $count++;
        }
      }

      $avgDays = $count > 0 ? round($totalDays / $count) : 0;
      $target = self::KPI_TARGETS['time_to_mvp'];

      return [
        'value' => $avgDays,
        'unit' => 'días',
        'target' => $target,
        'status' => $avgDays <= $target ? 'on_track' : 'behind',
        'label' => 'Tiempo a MVP',
      ];
    }
    catch (\Exception $e) {
      return ['value' => 0, 'unit' => 'días', 'target' => 60, 'status' => 'on_track', 'label' => 'Tiempo a MVP'];
    }
  }

  /**
   * KPI: Hypothesis validation rate.
   */
  protected function calculateHypothesisValidationRate(): array {
    try {
      $totalHypotheses = (int) $this->entityTypeManager
        ->getStorage('hypothesis')
        ->getQuery()
        ->accessCheck(FALSE)
        ->count()
        ->execute();

      if ($totalHypotheses === 0) {
        return ['value' => 0, 'unit' => '%', 'target' => 50, 'status' => 'behind', 'label' => 'Tasa de validación'];
      }

      $validated = (int) $this->entityTypeManager
        ->getStorage('hypothesis')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 'VALIDATED')
        ->count()
        ->execute();

      $rate = round(($validated / $totalHypotheses) * 100, 1);
      $target = self::KPI_TARGETS['hypothesis_validation_rate'];

      return [
        'value' => $rate,
        'unit' => '%',
        'target' => $target,
        'status' => $rate >= $target ? 'on_track' : 'behind',
        'label' => 'Tasa de validación',
      ];
    }
    catch (\Exception $e) {
      return ['value' => 0, 'unit' => '%', 'target' => 50, 'status' => 'behind', 'label' => 'Tasa de validación'];
    }
  }

  /**
   * KPI: Activation rate (% with canvas >=50%).
   */
  protected function calculateActivationRate(): array {
    try {
      $totalProfiles = (int) $this->entityTypeManager
        ->getStorage('entrepreneur_profile')
        ->getQuery()
        ->accessCheck(FALSE)
        ->count()
        ->execute();

      if ($totalProfiles === 0) {
        return ['value' => 0, 'unit' => '%', 'target' => 60, 'status' => 'behind', 'label' => 'Tasa de activación'];
      }

      if (!\Drupal::hasService('jaraba_copilot_v2.bmc_validation')) {
        return ['value' => 0, 'unit' => '%', 'target' => 60, 'status' => 'behind', 'label' => 'Tasa de activación'];
      }

      $canvases = $this->entityTypeManager
        ->getStorage('business_model_canvas')
        ->loadMultiple();

      $activated = 0;
      $bmcValidation = \Drupal::service('jaraba_copilot_v2.bmc_validation');
      foreach ($canvases as $canvas) {
        $validation = $bmcValidation->validateCanvas($canvas);
        $completeness = $validation['overall_percentage'] ?? 0;
        if ($completeness >= 50) {
          $activated++;
        }
      }

      $rate = round(($activated / $totalProfiles) * 100, 1);
      $target = self::KPI_TARGETS['activation_rate'];

      return [
        'value' => $rate,
        'unit' => '%',
        'target' => $target,
        'status' => $rate >= $target ? 'on_track' : 'behind',
        'label' => 'Tasa de activación',
      ];
    }
    catch (\Exception $e) {
      return ['value' => 0, 'unit' => '%', 'target' => 60, 'status' => 'behind', 'label' => 'Tasa de activación'];
    }
  }

  /**
   * KPI: Mentor engagement (% with >=1 mentor session).
   */
  protected function calculateMentorEngagement(): array {
    try {
      $totalProfiles = (int) $this->entityTypeManager
        ->getStorage('entrepreneur_profile')
        ->getQuery()
        ->accessCheck(FALSE)
        ->count()
        ->execute();

      if ($totalProfiles === 0) {
        return ['value' => 0, 'unit' => '%', 'target' => 40, 'status' => 'behind', 'label' => 'Engagement mentoring'];
      }

      $result = $this->database->query(
        "SELECT DISTINCT user_id FROM {mentoring_session}"
      );
      $mentored = 0;
      foreach ($result as $row) {
        $mentored++;
      }

      $rate = round(($mentored / $totalProfiles) * 100, 1);
      $target = self::KPI_TARGETS['mentor_engagement'];

      return [
        'value' => $rate,
        'unit' => '%',
        'target' => $target,
        'status' => $rate >= $target ? 'on_track' : 'behind',
        'label' => 'Engagement mentoring',
      ];
    }
    catch (\Exception $e) {
      return ['value' => 0, 'unit' => '%', 'target' => 40, 'status' => 'behind', 'label' => 'Engagement mentoring'];
    }
  }

  /**
   * KPI: NPS (Net Promoter Score).
   */
  protected function calculateNps(): array {
    if (!\Drupal::hasService('jaraba_customer_success.nps_survey')) {
      return ['value' => 0, 'unit' => 'score', 'target' => 50, 'status' => 'behind', 'label' => 'NPS Emprendedor'];
    }

    try {
      $nps = \Drupal::service('jaraba_customer_success.nps_survey')
        ->getScore('emprendimiento');
      $score = $nps ?? 0;
      $target = self::KPI_TARGETS['nps'];

      return [
        'value' => $score,
        'unit' => 'score',
        'target' => $target,
        'status' => $score >= $target ? 'on_track' : 'behind',
        'label' => 'NPS Emprendedor',
      ];
    }
    catch (\Exception $e) {
      return ['value' => 0, 'unit' => 'score', 'target' => 50, 'status' => 'behind', 'label' => 'NPS Emprendedor'];
    }
  }

  /**
   * KPI: ARPU (Average Revenue Per User).
   */
  protected function calculateArpu(): array {
    if (!\Drupal::hasService('jaraba_foc.metrics_calculator')) {
      return ['value' => 0, 'unit' => 'EUR/mes', 'target' => 12, 'status' => 'behind', 'label' => 'ARPU Emprendimiento'];
    }

    try {
      $arpu = (float) \Drupal::service('jaraba_foc.metrics_calculator')->calculateARPU();
      $target = self::KPI_TARGETS['arpu'];

      return [
        'value' => round($arpu, 2),
        'unit' => 'EUR/mes',
        'target' => $target,
        'status' => $arpu >= $target ? 'on_track' : 'behind',
        'label' => 'ARPU Emprendimiento',
      ];
    }
    catch (\Exception $e) {
      return ['value' => 0, 'unit' => 'EUR/mes', 'target' => 12, 'status' => 'behind', 'label' => 'ARPU Emprendimiento'];
    }
  }

  /**
   * KPI: Free to Paid conversion rate.
   */
  protected function calculateConversionRate(): array {
    try {
      $totalTriggers = (int) $this->database->query(
        "SELECT COUNT(*) FROM {upgrade_trigger_events} WHERE vertical = :v",
        [':v' => 'emprendimiento']
      )->fetchField();

      $conversions = (int) $this->database->query(
        "SELECT COUNT(*) FROM {upgrade_trigger_events} WHERE vertical = :v AND converted = 1",
        [':v' => 'emprendimiento']
      )->fetchField();

      $rate = $totalTriggers > 0 ? round(($conversions / $totalTriggers) * 100, 1) : 0;
      $target = self::KPI_TARGETS['conversion_free_paid'];

      return [
        'value' => $rate,
        'unit' => '%',
        'target' => $target,
        'status' => $rate >= $target ? 'on_track' : 'behind',
        'label' => 'Conversión Free→Paid',
      ];
    }
    catch (\Exception $e) {
      return ['value' => 0, 'unit' => '%', 'target' => 8, 'status' => 'behind', 'label' => 'Conversión Free→Paid'];
    }
  }

  /**
   * Categorizes an overall score into health categories.
   */
  protected function categorize(int $score): string {
    if ($score >= 80) {
      return 'healthy';
    }
    if ($score >= 60) {
      return 'neutral';
    }
    if ($score >= 40) {
      return 'at_risk';
    }
    return 'critical';
  }

}

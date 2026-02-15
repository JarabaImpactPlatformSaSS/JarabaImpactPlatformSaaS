<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Health score calculator specific to the Empleabilidad vertical.
 *
 * Calculates user-level health across 5 weighted dimensions and provides
 * 8 aggregate KPIs for the vertical.
 *
 * Plan Elevación Empleabilidad v1 — Fase 10.
 */
class EmployabilityHealthScoreService {

  /**
   * Health score dimensions with weights.
   */
  protected const DIMENSIONS = [
    'profile_completeness' => ['weight' => 0.25, 'label' => 'Completitud de perfil'],
    'application_activity' => ['weight' => 0.30, 'label' => 'Actividad de aplicaciones'],
    'copilot_engagement' => ['weight' => 0.15, 'label' => 'Uso del copilot'],
    'training_progress' => ['weight' => 0.15, 'label' => 'Progreso formativo'],
    'credential_advancement' => ['weight' => 0.15, 'label' => 'Avance en credenciales'],
  ];

  /**
   * Target values for vertical KPIs.
   */
  protected const KPI_TARGETS = [
    'insertion_rate' => 40,
    'time_to_employment' => 90,
    'activation_rate' => 60,
    'engagement_rate' => 45,
    'nps' => 50,
    'arpu' => 15,
    'conversion_free_paid' => 8,
    'churn_rate' => 5,
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
   * Calculates aggregate KPIs for the Empleabilidad vertical.
   *
   * @return array
   *   Array of KPIs with: value, target, status (on_track|behind|ahead).
   */
  public function calculateVerticalKpis(): array {
    $kpis = [];

    $kpis['insertion_rate'] = $this->calculateInsertionRate();
    $kpis['time_to_employment'] = $this->calculateTimeToEmployment();
    $kpis['activation_rate'] = $this->calculateActivationRate();
    $kpis['engagement_rate'] = $this->calculateEngagementRate();
    $kpis['nps'] = $this->calculateNps();
    $kpis['arpu'] = $this->calculateArpu();
    $kpis['conversion_free_paid'] = $this->calculateConversionRate();
    $kpis['churn_rate'] = $this->calculateChurnRate();

    return $kpis;
  }

  /**
   * Calculates a single dimension score (0-100).
   */
  protected function calculateDimension(int $userId, string $key): int {
    return match ($key) {
      'profile_completeness' => $this->calculateProfileCompleteness($userId),
      'application_activity' => $this->calculateApplicationActivity($userId),
      'copilot_engagement' => $this->calculateCopilotEngagement($userId),
      'training_progress' => $this->calculateTrainingProgress($userId),
      'credential_advancement' => $this->calculateCredentialAdvancement($userId),
      default => 0,
    };
  }

  /**
   * Profile completeness dimension (0-100).
   *
   * Directly uses ProfileCompletionService percentage.
   */
  protected function calculateProfileCompleteness(int $userId): int {
    if (!\Drupal::hasService('jaraba_candidate.profile_completion')) {
      return 0;
    }

    try {
      $completion = \Drupal::service('jaraba_candidate.profile_completion')
        ->calculateCompletion($userId);
      return (int) ($completion['percentage'] ?? 0);
    }
    catch (\Exception $e) {
      return 0;
    }
  }

  /**
   * Application activity dimension (0-100).
   *
   * Scoring: applications count (max 40) + response rate (max 30) +
   * recency (max 30).
   */
  protected function calculateApplicationActivity(int $userId): int {
    try {
      $applications = $this->entityTypeManager
        ->getStorage('job_application')
        ->loadByProperties(['candidate_id' => $userId]);

      $total = count($applications);
      if ($total === 0) {
        return 0;
      }

      // Application count score: 10 apps = 40 points.
      $countScore = min(40, (int) ($total * 4));

      // Response rate: % with positive status.
      $positive = 0;
      $latestChanged = 0;
      foreach ($applications as $app) {
        $status = $app->get('status')->value ?? '';
        if (in_array($status, ['shortlisted', 'interviewed', 'offered', 'hired'])) {
          $positive++;
        }
        $changed = (int) ($app->get('changed')->value ?? 0);
        if ($changed > $latestChanged) {
          $latestChanged = $changed;
        }
      }
      $responseRate = $total > 0 ? ($positive / $total) : 0;
      $responseScore = min(30, (int) ($responseRate * 100 * 0.3));

      // Recency: activity within last 7 days = 30, 30 days = 15, else 0.
      $now = \Drupal::time()->getRequestTime();
      $daysSinceActivity = $latestChanged > 0 ? ($now - $latestChanged) / 86400 : 999;
      $recencyScore = 0;
      if ($daysSinceActivity <= 7) {
        $recencyScore = 30;
      }
      elseif ($daysSinceActivity <= 30) {
        $recencyScore = 15;
      }

      return min(100, $countScore + $responseScore + $recencyScore);
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
   * Training progress dimension (0-100).
   *
   * Scoring: enrolled courses (max 20) + average progress (max 50) +
   * completed courses (max 30).
   */
  protected function calculateTrainingProgress(int $userId): int {
    try {
      $enrollments = $this->entityTypeManager
        ->getStorage('lms_enrollment')
        ->loadByProperties(['user_id' => $userId]);

      $total = count($enrollments);
      if ($total === 0) {
        return 0;
      }

      $enrolledScore = min(20, $total * 5);

      $totalProgress = 0;
      $completed = 0;
      foreach ($enrollments as $enrollment) {
        $progress = (int) ($enrollment->get('progress')->value ?? 0);
        $totalProgress += $progress;
        if ($progress >= 100) {
          $completed++;
        }
      }

      $avgProgress = $total > 0 ? $totalProgress / $total : 0;
      $progressScore = min(50, (int) ($avgProgress * 0.5));
      $completedScore = min(30, $completed * 10);

      return min(100, $enrolledScore + $progressScore + $completedScore);
    }
    catch (\Exception $e) {
      return 0;
    }
  }

  /**
   * Credential advancement dimension (0-100).
   *
   * Scoring: badges earned (max 50) + certificates (max 50).
   */
  protected function calculateCredentialAdvancement(int $userId): int {
    $score = 0;

    // Badges.
    try {
      $badges = $this->entityTypeManager
        ->getStorage('user_badge')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->count()
        ->execute();
      $score += min(50, (int) $badges * 10);
    }
    catch (\Exception $e) {
      // user_badge entity may not exist.
    }

    // Certificates (completed courses with certificate).
    try {
      $certs = $this->entityTypeManager
        ->getStorage('credential')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->count()
        ->execute();
      $score += min(50, (int) $certs * 15);
    }
    catch (\Exception $e) {
      // credential entity may not exist.
    }

    return min(100, $score);
  }

  /**
   * KPI: Insertion rate (% candidates hired).
   */
  protected function calculateInsertionRate(): array {
    try {
      $totalCandidates = (int) $this->entityTypeManager
        ->getStorage('candidate_profile')
        ->getQuery()
        ->accessCheck(FALSE)
        ->count()
        ->execute();

      $hiredCount = (int) $this->entityTypeManager
        ->getStorage('job_application')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 'hired')
        ->count()
        ->execute();

      $rate = $totalCandidates > 0 ? round(($hiredCount / $totalCandidates) * 100, 1) : 0;
      $target = self::KPI_TARGETS['insertion_rate'];

      return [
        'value' => $rate,
        'unit' => '%',
        'target' => $target,
        'status' => $rate >= $target ? 'on_track' : 'behind',
        'label' => 'Tasa de inserción',
      ];
    }
    catch (\Exception $e) {
      return ['value' => 0, 'unit' => '%', 'target' => 40, 'status' => 'behind', 'label' => 'Tasa de inserción'];
    }
  }

  /**
   * KPI: Average time to employment (days).
   */
  protected function calculateTimeToEmployment(): array {
    try {
      $hiredApps = $this->entityTypeManager
        ->getStorage('job_application')
        ->loadByProperties(['status' => 'hired']);

      if (empty($hiredApps)) {
        return ['value' => 0, 'unit' => 'days', 'target' => 90, 'status' => 'on_track', 'label' => 'Tiempo a empleo'];
      }

      $totalDays = 0;
      $count = 0;
      foreach ($hiredApps as $app) {
        $created = (int) ($app->get('created')->value ?? 0);
        $changed = (int) ($app->get('changed')->value ?? 0);
        if ($created > 0 && $changed > $created) {
          $totalDays += ($changed - $created) / 86400;
          $count++;
        }
      }

      $avgDays = $count > 0 ? round($totalDays / $count) : 0;
      $target = self::KPI_TARGETS['time_to_employment'];

      return [
        'value' => $avgDays,
        'unit' => 'days',
        'target' => $target,
        'status' => $avgDays <= $target ? 'on_track' : 'behind',
        'label' => 'Tiempo a empleo',
      ];
    }
    catch (\Exception $e) {
      return ['value' => 0, 'unit' => 'days', 'target' => 90, 'status' => 'on_track', 'label' => 'Tiempo a empleo'];
    }
  }

  /**
   * KPI: Activation rate (% with profile >=70%).
   */
  protected function calculateActivationRate(): array {
    try {
      $totalProfiles = (int) $this->entityTypeManager
        ->getStorage('candidate_profile')
        ->getQuery()
        ->accessCheck(FALSE)
        ->count()
        ->execute();

      if ($totalProfiles === 0) {
        return ['value' => 0, 'unit' => '%', 'target' => 60, 'status' => 'behind', 'label' => 'Tasa de activación'];
      }

      $profiles = $this->entityTypeManager
        ->getStorage('candidate_profile')
        ->loadMultiple();

      $activated = 0;
      foreach ($profiles as $profile) {
        if (method_exists($profile, 'getCompletionPercent') && $profile->getCompletionPercent() >= 70) {
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
   * KPI: Engagement rate (% activated users with 3+ applications).
   */
  protected function calculateEngagementRate(): array {
    try {
      $totalProfiles = (int) $this->entityTypeManager
        ->getStorage('candidate_profile')
        ->getQuery()
        ->accessCheck(FALSE)
        ->count()
        ->execute();

      if ($totalProfiles === 0) {
        return ['value' => 0, 'unit' => '%', 'target' => 45, 'status' => 'behind', 'label' => 'Tasa de engagement'];
      }

      // Count users with 3+ applications.
      $result = $this->database->query(
        "SELECT candidate_id, COUNT(*) as app_count FROM {job_application} GROUP BY candidate_id HAVING COUNT(*) >= 3"
      );
      $engagedCount = 0;
      foreach ($result as $row) {
        $engagedCount++;
      }

      $rate = round(($engagedCount / $totalProfiles) * 100, 1);
      $target = self::KPI_TARGETS['engagement_rate'];

      return [
        'value' => $rate,
        'unit' => '%',
        'target' => $target,
        'status' => $rate >= $target ? 'on_track' : 'behind',
        'label' => 'Tasa de engagement',
      ];
    }
    catch (\Exception $e) {
      return ['value' => 0, 'unit' => '%', 'target' => 45, 'status' => 'behind', 'label' => 'Tasa de engagement'];
    }
  }

  /**
   * KPI: NPS (Net Promoter Score).
   */
  protected function calculateNps(): array {
    if (!\Drupal::hasService('jaraba_customer_success.nps_survey')) {
      return ['value' => 0, 'unit' => 'score', 'target' => 50, 'status' => 'behind', 'label' => 'NPS Candidato'];
    }

    try {
      $nps = \Drupal::service('jaraba_customer_success.nps_survey')
        ->getScore('empleabilidad');
      $score = $nps ?? 0;
      $target = self::KPI_TARGETS['nps'];

      return [
        'value' => $score,
        'unit' => 'score',
        'target' => $target,
        'status' => $score >= $target ? 'on_track' : 'behind',
        'label' => 'NPS Candidato',
      ];
    }
    catch (\Exception $e) {
      return ['value' => 0, 'unit' => 'score', 'target' => 50, 'status' => 'behind', 'label' => 'NPS Candidato'];
    }
  }

  /**
   * KPI: ARPU (Average Revenue Per User).
   */
  protected function calculateArpu(): array {
    if (!\Drupal::hasService('jaraba_foc.metrics_calculator')) {
      return ['value' => 0, 'unit' => 'EUR/month', 'target' => 15, 'status' => 'behind', 'label' => 'ARPU Empleabilidad'];
    }

    try {
      $arpu = (float) \Drupal::service('jaraba_foc.metrics_calculator')->calculateARPU();
      $target = self::KPI_TARGETS['arpu'];

      return [
        'value' => round($arpu, 2),
        'unit' => 'EUR/month',
        'target' => $target,
        'status' => $arpu >= $target ? 'on_track' : 'behind',
        'label' => 'ARPU Empleabilidad',
      ];
    }
    catch (\Exception $e) {
      return ['value' => 0, 'unit' => 'EUR/month', 'target' => 15, 'status' => 'behind', 'label' => 'ARPU Empleabilidad'];
    }
  }

  /**
   * KPI: Free to Paid conversion rate.
   */
  protected function calculateConversionRate(): array {
    try {
      $totalTriggers = (int) $this->database->query(
        "SELECT COUNT(*) FROM {upgrade_trigger_log} WHERE vertical = :v",
        [':v' => 'empleabilidad']
      )->fetchField();

      $conversions = (int) $this->database->query(
        "SELECT COUNT(*) FROM {upgrade_trigger_log} WHERE vertical = :v AND converted = 1",
        [':v' => 'empleabilidad']
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
   * KPI: Churn rate (% paid users cancelling within 30 days).
   */
  protected function calculateChurnRate(): array {
    try {
      $thirtyDaysAgo = \Drupal::time()->getRequestTime() - (30 * 86400);

      $cancelled = (int) $this->database->query(
        "SELECT COUNT(*) FROM {tenant_subscription_log} WHERE vertical = :v AND action = 'cancel' AND created > :cutoff",
        [':v' => 'empleabilidad', ':cutoff' => $thirtyDaysAgo]
      )->fetchField();

      $totalPaid = (int) $this->database->query(
        "SELECT COUNT(*) FROM {tenant_subscription_log} WHERE vertical = :v AND action = 'subscribe' AND created > :cutoff",
        [':v' => 'empleabilidad', ':cutoff' => $thirtyDaysAgo]
      )->fetchField();

      $rate = $totalPaid > 0 ? round(($cancelled / $totalPaid) * 100, 1) : 0;
      $target = self::KPI_TARGETS['churn_rate'];

      return [
        'value' => $rate,
        'unit' => '%',
        'target' => $target,
        'status' => $rate <= $target ? 'on_track' : 'behind',
        'label' => 'Churn Rate',
      ];
    }
    catch (\Exception $e) {
      return ['value' => 0, 'unit' => '%', 'target' => 5, 'status' => 'on_track', 'label' => 'Churn Rate'];
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

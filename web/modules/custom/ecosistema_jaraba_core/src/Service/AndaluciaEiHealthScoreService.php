<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Health score calculator specific to the Andalucía +ei vertical.
 *
 * Calculates user-level health across 5 weighted dimensions and provides
 * 8 aggregate KPIs for the vertical.
 *
 * Dimensions:
 * - Progreso horas orientación (25%)
 * - Progreso horas formación (30%)
 * - Engagement IA Copilot (20%)
 * - Completitud STO (10%)
 * - Velocidad de progresión (15%)
 *
 * Plan Elevación Andalucía +ei v1 — Fase 8.
 *
 * @see \Drupal\ecosistema_jaraba_core\Service\EmployabilityHealthScoreService
 */
class AndaluciaEiHealthScoreService {

  /**
   * Health score dimensions with weights.
   */
  protected const DIMENSIONS = [
    'orientation_hours' => ['weight' => 0.25, 'label' => 'Progreso horas orientación'],
    'training_hours' => ['weight' => 0.30, 'label' => 'Progreso horas formación'],
    'copilot_engagement' => ['weight' => 0.20, 'label' => 'Engagement IA Copilot'],
    'sto_completeness' => ['weight' => 0.10, 'label' => 'Completitud STO'],
    'progression_speed' => ['weight' => 0.15, 'label' => 'Velocidad de progresión'],
  ];

  /**
   * Target values for vertical KPIs.
   */
  protected const KPI_TARGETS = [
    'insertion_rate' => 60,
    'time_to_insertion' => 120,
    'training_completion_rate' => 80,
    'ia_engagement_rate' => 70,
    'sto_sync_success_rate' => 95,
    'participant_nps' => 50,
    'conversion_free_paid' => 10,
    'churn_rate' => 8,
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
    $participant = $this->getParticipant($userId);
    if (!$participant) {
      return [
        'user_id' => $userId,
        'overall_score' => 0,
        'category' => 'critical',
        'dimensions' => [],
      ];
    }

    $dimensions = [];
    $overall = 0.0;

    foreach (self::DIMENSIONS as $key => $config) {
      $score = $this->calculateDimension($userId, $key, $participant);
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
   * Calculates aggregate KPIs for the Andalucía +ei vertical.
   *
   * @return array
   *   Array of KPIs with: value, target, status (on_track|behind|ahead).
   */
  public function calculateVerticalKpis(): array {
    $kpis = [];

    $kpis['insertion_rate'] = $this->calculateInsertionRate();
    $kpis['time_to_insertion'] = $this->calculateTimeToInsertion();
    $kpis['training_completion_rate'] = $this->calculateTrainingCompletionRate();
    $kpis['ia_engagement_rate'] = $this->calculateIaEngagementRate();
    $kpis['sto_sync_success_rate'] = $this->calculateStoSyncRate();
    $kpis['participant_nps'] = $this->calculateNps();
    $kpis['conversion_free_paid'] = $this->calculateConversionRate();
    $kpis['churn_rate'] = $this->calculateChurnRate();

    return $kpis;
  }

  /**
   * Calculates a single dimension score (0-100).
   */
  protected function calculateDimension(int $userId, string $key, $participant): int {
    return match ($key) {
      'orientation_hours' => $this->calculateOrientationHours($participant),
      'training_hours' => $this->calculateTrainingHours($participant),
      'copilot_engagement' => $this->calculateCopilotEngagement($userId),
      'sto_completeness' => $this->calculateStoCompleteness($participant),
      'progression_speed' => $this->calculateProgressionSpeed($participant),
      default => 0,
    };
  }

  /**
   * Orientation hours dimension (0-100).
   *
   * Target: 10h total orientation. Score = (total / 10) * 100, capped at 100.
   */
  protected function calculateOrientationHours($participant): int {
    $total = (float) ($participant->get('horas_mentoria_ia')->value ?? 0)
      + (float) ($participant->get('horas_mentoria_humana')->value ?? 0)
      + (float) ($participant->get('horas_orientacion_ind')->value ?? 0)
      + (float) ($participant->get('horas_orientacion_grup')->value ?? 0);

    return min(100, (int) round(($total / 10) * 100));
  }

  /**
   * Training hours dimension (0-100).
   *
   * Target: 50h formation. Score = (horas_formacion / 50) * 100, capped at 100.
   */
  protected function calculateTrainingHours($participant): int {
    $training = (float) ($participant->get('horas_formacion')->value ?? 0);
    return min(100, (int) round(($training / 50) * 100));
  }

  /**
   * Copilot engagement dimension (0-100).
   *
   * Target: 3 sessions/week. Score based on IA hours vs expected.
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
      $recentScore = min(50, $recentCount * 17);

      return min(100, $countScore + $recentScore);
    }
    catch (\Exception $e) {
      return 0;
    }
  }

  /**
   * STO completeness dimension (0-100).
   *
   * Binary: synced = 100, not synced = 0.
   */
  protected function calculateStoCompleteness($participant): int {
    if (!$participant->hasField('sto_sync_status')) {
      return 0;
    }
    $status = $participant->get('sto_sync_status')->value ?? '';
    return $status === 'synced' ? 100 : 0;
  }

  /**
   * Progression speed dimension (0-100).
   *
   * Compares active weeks vs expected weeks for current phase.
   * Expected: 12 weeks atencion, 8 weeks insercion.
   */
  protected function calculateProgressionSpeed($participant): int {
    $created = (int) ($participant->get('created')->value ?? 0);
    if (!$created) {
      return 50;
    }

    $fase = $participant->get('fase_actual')->value ?? 'atencion';
    $expectedWeeks = $fase === 'insercion' ? 8 : 12;

    $weeksActive = (\Drupal::time()->getRequestTime() - $created) / (7 * 86400);
    if ($weeksActive <= 0) {
      return 100;
    }

    // Calculate total progress as a ratio.
    $totalOrientation = (float) ($participant->get('horas_mentoria_ia')->value ?? 0)
      + (float) ($participant->get('horas_mentoria_humana')->value ?? 0)
      + (float) ($participant->get('horas_orientacion_ind')->value ?? 0)
      + (float) ($participant->get('horas_orientacion_grup')->value ?? 0);
    $training = (float) ($participant->get('horas_formacion')->value ?? 0);

    // Expected pace: 10h orientation + 50h training over expectedWeeks.
    $expectedHoursPerWeek = 60 / $expectedWeeks;
    $actualHoursPerWeek = ($totalOrientation + $training) / $weeksActive;

    $ratio = $expectedHoursPerWeek > 0 ? ($actualHoursPerWeek / $expectedHoursPerWeek) : 0;
    return min(100, (int) round($ratio * 100));
  }

  /**
   * KPI: Insertion rate (% participants reaching insertion phase).
   */
  protected function calculateInsertionRate(): array {
    try {
      $totalParticipants = (int) $this->entityTypeManager
        ->getStorage('programa_participante_ei')
        ->getQuery()
        ->accessCheck(FALSE)
        ->count()
        ->execute();

      $insertedCount = (int) $this->entityTypeManager
        ->getStorage('programa_participante_ei')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('fase_actual', 'insercion')
        ->count()
        ->execute();

      $rate = $totalParticipants > 0 ? round(($insertedCount / $totalParticipants) * 100, 1) : 0;
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
      return ['value' => 0, 'unit' => '%', 'target' => 60, 'status' => 'behind', 'label' => 'Tasa de inserción'];
    }
  }

  /**
   * KPI: Average time to insertion (days).
   */
  protected function calculateTimeToInsertion(): array {
    try {
      $inserted = $this->entityTypeManager
        ->getStorage('programa_participante_ei')
        ->loadByProperties(['fase_actual' => 'insercion']);

      if (empty($inserted)) {
        return ['value' => 0, 'unit' => 'days', 'target' => 120, 'status' => 'on_track', 'label' => 'Tiempo a inserción'];
      }

      $totalDays = 0;
      $count = 0;
      foreach ($inserted as $p) {
        $created = (int) ($p->get('created')->value ?? 0);
        $changed = (int) ($p->get('changed')->value ?? 0);
        if ($created > 0 && $changed > $created) {
          $totalDays += ($changed - $created) / 86400;
          $count++;
        }
      }

      $avgDays = $count > 0 ? round($totalDays / $count) : 0;
      $target = self::KPI_TARGETS['time_to_insertion'];

      return [
        'value' => $avgDays,
        'unit' => 'days',
        'target' => $target,
        'status' => $avgDays <= $target ? 'on_track' : 'behind',
        'label' => 'Tiempo a inserción',
      ];
    }
    catch (\Exception $e) {
      return ['value' => 0, 'unit' => 'days', 'target' => 120, 'status' => 'on_track', 'label' => 'Tiempo a inserción'];
    }
  }

  /**
   * KPI: Training completion rate (% completing 50h).
   */
  protected function calculateTrainingCompletionRate(): array {
    try {
      $participants = $this->entityTypeManager
        ->getStorage('programa_participante_ei')
        ->loadMultiple();

      $total = count($participants);
      if ($total === 0) {
        return ['value' => 0, 'unit' => '%', 'target' => 80, 'status' => 'behind', 'label' => 'Completitud formación'];
      }

      $completed = 0;
      foreach ($participants as $p) {
        $hours = (float) ($p->get('horas_formacion')->value ?? 0);
        if ($hours >= 50) {
          $completed++;
        }
      }

      $rate = round(($completed / $total) * 100, 1);
      $target = self::KPI_TARGETS['training_completion_rate'];

      return [
        'value' => $rate,
        'unit' => '%',
        'target' => $target,
        'status' => $rate >= $target ? 'on_track' : 'behind',
        'label' => 'Completitud formación',
      ];
    }
    catch (\Exception $e) {
      return ['value' => 0, 'unit' => '%', 'target' => 80, 'status' => 'behind', 'label' => 'Completitud formación'];
    }
  }

  /**
   * KPI: IA engagement rate (% using copilot weekly).
   */
  protected function calculateIaEngagementRate(): array {
    try {
      $participants = $this->entityTypeManager
        ->getStorage('programa_participante_ei')
        ->loadMultiple();

      $total = count($participants);
      if ($total === 0) {
        return ['value' => 0, 'unit' => '%', 'target' => 70, 'status' => 'behind', 'label' => 'Engagement IA'];
      }

      $engaged = 0;
      foreach ($participants as $p) {
        $iaHours = (float) ($p->get('horas_mentoria_ia')->value ?? 0);
        if ($iaHours > 0) {
          $engaged++;
        }
      }

      $rate = round(($engaged / $total) * 100, 1);
      $target = self::KPI_TARGETS['ia_engagement_rate'];

      return [
        'value' => $rate,
        'unit' => '%',
        'target' => $target,
        'status' => $rate >= $target ? 'on_track' : 'behind',
        'label' => 'Engagement IA',
      ];
    }
    catch (\Exception $e) {
      return ['value' => 0, 'unit' => '%', 'target' => 70, 'status' => 'behind', 'label' => 'Engagement IA'];
    }
  }

  /**
   * KPI: STO sync success rate.
   */
  protected function calculateStoSyncRate(): array {
    try {
      $participants = $this->entityTypeManager
        ->getStorage('programa_participante_ei')
        ->loadMultiple();

      $total = count($participants);
      if ($total === 0) {
        return ['value' => 0, 'unit' => '%', 'target' => 95, 'status' => 'behind', 'label' => 'Sync STO'];
      }

      $synced = 0;
      foreach ($participants as $p) {
        if ($p->hasField('sto_sync_status')) {
          $status = $p->get('sto_sync_status')->value ?? '';
          if ($status === 'synced') {
            $synced++;
          }
        }
      }

      $rate = round(($synced / $total) * 100, 1);
      $target = self::KPI_TARGETS['sto_sync_success_rate'];

      return [
        'value' => $rate,
        'unit' => '%',
        'target' => $target,
        'status' => $rate >= $target ? 'on_track' : 'behind',
        'label' => 'Sync STO',
      ];
    }
    catch (\Exception $e) {
      return ['value' => 0, 'unit' => '%', 'target' => 95, 'status' => 'behind', 'label' => 'Sync STO'];
    }
  }

  /**
   * KPI: NPS (Net Promoter Score).
   */
  protected function calculateNps(): array {
    if (!\Drupal::hasService('jaraba_customer_success.nps_survey')) {
      return ['value' => 0, 'unit' => 'score', 'target' => 50, 'status' => 'behind', 'label' => 'NPS Participante'];
    }

    try {
      $nps = \Drupal::service('jaraba_customer_success.nps_survey')
        ->getScore('andalucia_ei');
      $score = $nps ?? 0;
      $target = self::KPI_TARGETS['participant_nps'];

      return [
        'value' => $score,
        'unit' => 'score',
        'target' => $target,
        'status' => $score >= $target ? 'on_track' : 'behind',
        'label' => 'NPS Participante',
      ];
    }
    catch (\Exception $e) {
      return ['value' => 0, 'unit' => 'score', 'target' => 50, 'status' => 'behind', 'label' => 'NPS Participante'];
    }
  }

  /**
   * KPI: Free to Paid conversion rate.
   */
  protected function calculateConversionRate(): array {
    try {
      $totalTriggers = (int) $this->database->query(
        "SELECT COUNT(*) FROM {upgrade_trigger_log} WHERE vertical = :v",
        [':v' => 'andalucia_ei']
      )->fetchField();

      $conversions = (int) $this->database->query(
        "SELECT COUNT(*) FROM {upgrade_trigger_log} WHERE vertical = :v AND converted = 1",
        [':v' => 'andalucia_ei']
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
      return ['value' => 0, 'unit' => '%', 'target' => 10, 'status' => 'behind', 'label' => 'Conversión Free→Paid'];
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
        [':v' => 'andalucia_ei', ':cutoff' => $thirtyDaysAgo]
      )->fetchField();

      $totalPaid = (int) $this->database->query(
        "SELECT COUNT(*) FROM {tenant_subscription_log} WHERE vertical = :v AND action = 'subscribe' AND created > :cutoff",
        [':v' => 'andalucia_ei', ':cutoff' => $thirtyDaysAgo]
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
      return ['value' => 0, 'unit' => '%', 'target' => 8, 'status' => 'on_track', 'label' => 'Churn Rate'];
    }
  }

  /**
   * Categorizes an overall score into health categories.
   */
  protected function categorize(int $score): string {
    if ($score >= 76) {
      return 'healthy';
    }
    if ($score >= 51) {
      return 'neutral';
    }
    if ($score >= 26) {
      return 'at_risk';
    }
    return 'critical';
  }

  /**
   * Loads the participant entity for a user.
   */
  protected function getParticipant(int $userId) {
    try {
      $participants = $this->entityTypeManager
        ->getStorage('programa_participante_ei')
        ->loadByProperties(['user_id' => $userId]);

      return !empty($participants) ? reset($participants) : NULL;
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

}

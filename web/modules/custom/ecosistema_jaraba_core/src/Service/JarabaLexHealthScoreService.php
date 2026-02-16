<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Health score service para el vertical JarabaLex.
 *
 * Calcula un score de salud (0-100) del usuario basado en 5 dimensiones
 * ponderadas y KPIs del vertical. Categoriza usuarios como healthy,
 * neutral, at_risk o critical para intervenciones proactivas.
 *
 * Dimensiones (weights suman 1.0):
 * - search_activity (0.30): Frecuencia de busquedas juridicas
 * - bookmark_engagement (0.20): Resoluciones guardadas y organizadas
 * - alert_utilization (0.20): Alertas configuradas y activas
 * - copilot_engagement (0.15): Interacciones con el copiloto legal
 * - citation_workflow (0.15): Citas insertadas en expedientes
 *
 * Plan Elevacion JarabaLex v1 — Fase 10.
 *
 * @see \Drupal\ecosistema_jaraba_core\Service\AndaluciaEiHealthScoreService
 */
class JarabaLexHealthScoreService {

  /**
   * 5 dimensiones ponderadas del health score.
   */
  protected const DIMENSIONS = [
    'search_activity' => ['weight' => 0.30, 'label' => 'Actividad de busqueda juridica'],
    'bookmark_engagement' => ['weight' => 0.20, 'label' => 'Engagement con resoluciones guardadas'],
    'alert_utilization' => ['weight' => 0.20, 'label' => 'Utilizacion de alertas juridicas'],
    'copilot_engagement' => ['weight' => 0.15, 'label' => 'Engagement con copiloto legal'],
    'citation_workflow' => ['weight' => 0.15, 'label' => 'Flujo de citas en expedientes'],
  ];

  /**
   * 8 KPIs del vertical.
   */
  protected const KPI_TARGETS = [
    'searches_per_user_month' => 20,
    'bookmarks_per_user' => 10,
    'alerts_per_user' => 3,
    'citations_per_user' => 5,
    'copilot_sessions_month' => 8,
    'conversion_free_paid' => 12,
    'user_retention_30d' => 65,
    'nps_score' => 45,
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
   * Calcula el health score de un usuario.
   *
   * @param int $userId
   *   ID del usuario.
   *
   * @return array
   *   Array con user_id, overall_score (0-100), category y dimensions.
   */
  public function calculateUserHealth(int $userId): array {
    $dimensions = [];
    $overallScore = 0;

    foreach (self::DIMENSIONS as $key => $meta) {
      $score = $this->calculateDimension($userId, $key);
      $weightedScore = $score * $meta['weight'];
      $overallScore += $weightedScore;

      $dimensions[$key] = [
        'score' => $score,
        'weight' => $meta['weight'],
        'weighted_score' => round($weightedScore, 1),
        'label' => $meta['label'],
      ];
    }

    $overallScore = (int) round($overallScore);

    return [
      'user_id' => $userId,
      'overall_score' => $overallScore,
      'category' => $this->categorize($overallScore),
      'dimensions' => $dimensions,
    ];
  }

  /**
   * Calcula KPIs del vertical.
   *
   * @return array
   *   Array de KPIs con value, target, status, label, unit.
   */
  public function calculateVerticalKpis(): array {
    $kpis = [];

    $kpis['searches_per_user_month'] = $this->calculateSearchesPerUser();
    $kpis['bookmarks_per_user'] = $this->calculateBookmarksPerUser();
    $kpis['alerts_per_user'] = $this->calculateAlertsPerUser();
    $kpis['citations_per_user'] = $this->calculateCitationsPerUser();
    $kpis['copilot_sessions_month'] = $this->calculateCopilotSessions();
    $kpis['conversion_free_paid'] = $this->calculateConversionRate();
    $kpis['user_retention_30d'] = $this->calculateRetention();
    $kpis['nps_score'] = $this->calculateNps();

    return $kpis;
  }

  /**
   * Calcula score de una dimension individual.
   */
  protected function calculateDimension(int $userId, string $key): int {
    return match ($key) {
      'search_activity' => $this->calculateSearchActivity($userId),
      'bookmark_engagement' => $this->calculateBookmarkEngagement($userId),
      'alert_utilization' => $this->calculateAlertUtilization($userId),
      'copilot_engagement' => $this->calculateCopilotEngagement($userId),
      'citation_workflow' => $this->calculateCitationWorkflow($userId),
      default => 0,
    };
  }

  /**
   * Actividad de busqueda: (busquedas_mes / target) * 100.
   */
  protected function calculateSearchActivity(int $userId): int {
    try {
      if (!\Drupal::hasService('ecosistema_jaraba_core.jarabalex_feature_gate')) {
        return 0;
      }
      $featureGate = \Drupal::service('ecosistema_jaraba_core.jarabalex_feature_gate');
      $result = $featureGate->check($userId, 'searches_per_month');
      $used = $result->used ?? 0;
      $target = self::KPI_TARGETS['searches_per_user_month'];
      return min(100, (int) round(($used / max(1, $target)) * 100));
    }
    catch (\Exception $e) {
      return 0;
    }
  }

  /**
   * Engagement con bookmarks: (bookmarks / target) * 100.
   */
  protected function calculateBookmarkEngagement(int $userId): int {
    try {
      $bookmarks = $this->entityTypeManager->getStorage('legal_bookmark')
        ->loadByProperties(['user_id' => $userId]);
      $count = count($bookmarks);
      $target = self::KPI_TARGETS['bookmarks_per_user'];
      return min(100, (int) round(($count / max(1, $target)) * 100));
    }
    catch (\Exception $e) {
      return 0;
    }
  }

  /**
   * Utilizacion de alertas: (alertas_activas / target) * 100.
   */
  protected function calculateAlertUtilization(int $userId): int {
    try {
      $alerts = $this->entityTypeManager->getStorage('legal_alert')
        ->loadByProperties(['user_id' => $userId, 'is_active' => TRUE]);
      $count = count($alerts);
      $target = self::KPI_TARGETS['alerts_per_user'];
      return min(100, (int) round(($count / max(1, $target)) * 100));
    }
    catch (\Exception $e) {
      return 0;
    }
  }

  /**
   * Engagement copiloto: sessions via AI observability.
   */
  protected function calculateCopilotEngagement(int $userId): int {
    try {
      $stateKey = "jarabalex_copilot_sessions_{$userId}";
      $sessions = (int) \Drupal::state()->get($stateKey, 0);
      $target = self::KPI_TARGETS['copilot_sessions_month'];
      return min(100, (int) round(($sessions / max(1, $target)) * 100));
    }
    catch (\Exception $e) {
      return 0;
    }
  }

  /**
   * Flujo de citas: (citas_insertadas / target) * 100.
   */
  protected function calculateCitationWorkflow(int $userId): int {
    try {
      $citations = $this->entityTypeManager->getStorage('legal_citation')
        ->loadByProperties(['user_id' => $userId]);
      $count = count($citations);
      $target = self::KPI_TARGETS['citations_per_user'];
      return min(100, (int) round(($count / max(1, $target)) * 100));
    }
    catch (\Exception $e) {
      return 0;
    }
  }

  // ==================================================================
  // KPI calculators — Vertical level.
  // ==================================================================

  protected function calculateSearchesPerUser(): array {
    $target = self::KPI_TARGETS['searches_per_user_month'];
    $value = 0;
    try {
      $today = date('Y-m-d');
      $count = $this->database->select('jarabalex_feature_usage', 'u')
        ->condition('feature_key', 'searches_per_month')
        ->condition('usage_date', $today)
        ->countQuery()
        ->execute()
        ->fetchField();
      $value = (int) $count;
    }
    catch (\Exception $e) {
      // Table may not exist yet.
    }
    return [
      'value' => $value,
      'target' => $target,
      'status' => $value >= $target ? 'on_track' : ($value >= $target * 0.7 ? 'behind' : 'critical'),
      'label' => 'Busquedas/usuario/mes',
      'unit' => 'count',
    ];
  }

  protected function calculateBookmarksPerUser(): array {
    $target = self::KPI_TARGETS['bookmarks_per_user'];
    return ['value' => 0, 'target' => $target, 'status' => 'behind', 'label' => 'Bookmarks/usuario', 'unit' => 'count'];
  }

  protected function calculateAlertsPerUser(): array {
    $target = self::KPI_TARGETS['alerts_per_user'];
    return ['value' => 0, 'target' => $target, 'status' => 'behind', 'label' => 'Alertas/usuario', 'unit' => 'count'];
  }

  protected function calculateCitationsPerUser(): array {
    $target = self::KPI_TARGETS['citations_per_user'];
    return ['value' => 0, 'target' => $target, 'status' => 'behind', 'label' => 'Citas/usuario', 'unit' => 'count'];
  }

  protected function calculateCopilotSessions(): array {
    $target = self::KPI_TARGETS['copilot_sessions_month'];
    return ['value' => 0, 'target' => $target, 'status' => 'behind', 'label' => 'Sesiones copilot/mes', 'unit' => 'count'];
  }

  protected function calculateConversionRate(): array {
    $target = self::KPI_TARGETS['conversion_free_paid'];
    return ['value' => 0, 'target' => $target, 'status' => 'behind', 'label' => 'Conversion free→paid', 'unit' => '%'];
  }

  protected function calculateRetention(): array {
    $target = self::KPI_TARGETS['user_retention_30d'];
    return ['value' => 0, 'target' => $target, 'status' => 'behind', 'label' => 'Retencion 30 dias', 'unit' => '%'];
  }

  protected function calculateNps(): array {
    $target = self::KPI_TARGETS['nps_score'];
    return ['value' => 0, 'target' => $target, 'status' => 'behind', 'label' => 'NPS', 'unit' => 'score'];
  }

  /**
   * Categoriza un score.
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

}

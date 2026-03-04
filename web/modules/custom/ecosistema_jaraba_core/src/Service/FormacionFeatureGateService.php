<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\ValueObject\FeatureGateResult;
use Psr\Log\LoggerInterface;

/**
 * Servicio de Feature Gating para el vertical Formacion (LMS).
 *
 * Verifica limites de uso por plan consultando FreemiumVerticalLimit
 * y el contador de uso en la tabla {formacion_feature_usage}.
 *
 * Features gestionadas:
 * - courses_limit: Cursos creados por tenant
 * - learning_paths_limit: Rutas de aprendizaje
 * - certificates_limit: Certificados emitidos/mes
 * - enrollments_limit: Matriculas activas
 * - copilot_uses_per_month: Consultas IA mensuales
 *
 * @see \Drupal\ecosistema_jaraba_core\Entity\FreemiumVerticalLimit
 * @see \Drupal\ecosistema_jaraba_core\Service\UpgradeTriggerService
 */
class FormacionFeatureGateService {

  /**
   * Vertical ID constante.
   */
  protected const VERTICAL = 'formacion';

  /**
   * Mapa de upgrade de planes.
   */
  protected const PLAN_UPGRADE = [
    'free' => 'starter',
    'starter' => 'profesional',
    'profesional' => 'business',
  ];

  /**
   * Features con periodo mensual (Y-m) en vez de diario (Y-m-d).
   */
  protected const MONTHLY_FEATURES = [
    'certificates_limit',
    'copilot_uses_per_month',
  ];

  /**
   * Features acumulativas (sin periodo, contador total).
   */
  protected const CUMULATIVE_FEATURES = [
    'courses_limit',
    'learning_paths_limit',
    'enrollments_limit',
  ];

  /**
   * Constructor.
   */
  public function __construct(
    protected UpgradeTriggerService $upgradeTriggerService,
    protected Connection $database,
    protected AccountProxyInterface $currentUser,
    protected LoggerInterface $logger,
    protected readonly TenantContextService $tenantContext,
  ) {
  }

  /**
   * Verifica si el usuario puede usar una feature del vertical formacion.
   *
   * @param int $userId
   *   El ID del usuario.
   * @param string $featureKey
   *   La feature a verificar (courses_limit, enrollments_limit, etc.).
   * @param string|null $plan
   *   Plan del usuario. Si NULL, se resuelve internamente.
   *
   * @return \Drupal\ecosistema_jaraba_core\ValueObject\FeatureGateResult
   *   Resultado con allowed, remaining, limit, upgradeMessage.
   */
  public function check(int $userId, string $featureKey, ?string $plan = NULL): FeatureGateResult {
    $plan = $plan ?? $this->getUserPlan($userId);

    // Obtener limite configurado.
    $limitEntity = $this->upgradeTriggerService->getVerticalLimit(self::VERTICAL, $plan, $featureKey);

    if (!$limitEntity) {
      // Sin configuracion de limite = permitido sin restriccion.
      return FeatureGateResult::allowed($featureKey, $plan);
    }

    $limitValue = (int) $limitEntity->get('limit_value');

    // -1 = ilimitado.
    if ($limitValue === -1) {
      return FeatureGateResult::allowed($featureKey, $plan, -1, -1);
    }

    // 0 = feature no incluida en este plan.
    if ($limitValue === 0) {
      $upgradePlan = self::PLAN_UPGRADE[$plan] ?? 'starter';
      return FeatureGateResult::denied(
        $featureKey,
        $plan,
        0,
        0,
        $limitEntity->get('upgrade_message') ?: 'Esta funcion no esta disponible en tu plan actual.',
        $upgradePlan,
      );
    }

    // Verificar uso actual.
    $used = $this->getUsageCount($userId, $featureKey);
    $remaining = max(0, $limitValue - $used);

    if ($remaining <= 0) {
      $upgradePlan = self::PLAN_UPGRADE[$plan] ?? 'starter';
      return FeatureGateResult::denied(
        $featureKey,
        $plan,
        $limitValue,
        $used,
        $limitEntity->get('upgrade_message') ?: 'Has alcanzado el limite de tu plan.',
        $upgradePlan,
      );
    }

    return FeatureGateResult::allowed($featureKey, $plan, $remaining, $limitValue, $used);
  }

  /**
   * Registra un uso de la feature.
   *
   * Para features acumulativas incrementa el contador total.
   * Para features mensuales incrementa el contador del mes actual.
   *
   * @param int $userId
   *   El ID del usuario.
   * @param string $featureKey
   *   La feature utilizada.
   */
  public function recordUsage(int $userId, string $featureKey): void {
    $period = $this->getUsagePeriod($featureKey);

    $this->ensureTable();

    // Upsert: incrementar contador o crear registro.
    $existing = $this->database->select('formacion_feature_usage', 'u')
      ->fields('u', ['id', 'usage_count'])
      ->condition('user_id', $userId)
      ->condition('feature_key', $featureKey)
      ->condition('usage_date', $period)
      ->execute()
      ->fetchObject();

    if ($existing) {
      $this->database->update('formacion_feature_usage')
        ->fields(['usage_count' => $existing->usage_count + 1])
        ->condition('id', $existing->id)
        ->execute();
    }
    else {
      $this->database->insert('formacion_feature_usage')
        ->fields([
          'user_id' => $userId,
          'feature_key' => $featureKey,
          'usage_date' => $period,
          'usage_count' => 1,
        ])
        ->execute();
    }

    $this->logger->info('Feature usage recorded: @feature for user @user (period: @period)', [
      '@feature' => $featureKey,
      '@user' => $userId,
      '@period' => $period,
    ]);
  }

  /**
   * Obtiene el plan actual del usuario para el vertical formacion.
   *
   * Resuelve el plan via tenant del usuario. Fallback a 'free'.
   *
   * @param int $userId
   *   El ID del usuario.
   *
   * @return string
   *   ID del plan (free, starter, profesional, business).
   */
  public function getUserPlan(int $userId): string {
    try {
      $tenant = $this->tenantContext->getCurrentTenant();

      if ($tenant) {
        $plan = $tenant->getSubscriptionPlan();
        if ($plan) {
          return $plan->id();
        }
      }
    }
    catch (\Throwable $e) {
      // Servicio no disponible — asumir free.
    }

    return 'free';
  }

  /**
   * Obtiene el contador de uso para una feature.
   *
   * @param int $userId
   *   El ID del usuario.
   * @param string $featureKey
   *   La feature a consultar.
   *
   * @return int
   *   Numero de usos en el periodo actual.
   */
  protected function getUsageCount(int $userId, string $featureKey): int {
    $this->ensureTable();
    $period = $this->getUsagePeriod($featureKey);

    $count = $this->database->select('formacion_feature_usage', 'u')
      ->fields('u', ['usage_count'])
      ->condition('user_id', $userId)
      ->condition('feature_key', $featureKey)
      ->condition('usage_date', $period)
      ->execute()
      ->fetchField();

    return $count ? (int) $count : 0;
  }

  /**
   * Determina el periodo de uso segun el tipo de feature.
   *
   * @param string $featureKey
   *   La clave de la feature.
   *
   * @return string
   *   Y-m-d (diario), Y-m (mensual), o 'cumulative'.
   */
  protected function getUsagePeriod(string $featureKey): string {
    if (in_array($featureKey, self::CUMULATIVE_FEATURES, TRUE)) {
      return 'cumulative';
    }

    if (in_array($featureKey, self::MONTHLY_FEATURES, TRUE)) {
      return date('Y-m');
    }

    return date('Y-m-d');
  }

  /**
   * Asegura que la tabla de uso existe.
   */
  protected function ensureTable(): void {
    if (!$this->database->schema()->tableExists('formacion_feature_usage')) {
      $this->database->schema()->createTable('formacion_feature_usage', [
        'description' => 'Tracking de uso de features del vertical formacion.',
        'fields' => [
          'id' => ['type' => 'serial', 'unsigned' => TRUE, 'not null' => TRUE],
          'user_id' => ['type' => 'int', 'unsigned' => TRUE, 'not null' => TRUE],
          'feature_key' => ['type' => 'varchar', 'length' => 128, 'not null' => TRUE],
          'usage_date' => ['type' => 'varchar', 'length' => 10, 'not null' => TRUE],
          'usage_count' => ['type' => 'int', 'unsigned' => TRUE, 'not null' => TRUE, 'default' => 0],
        ],
        'primary key' => ['id'],
        'indexes' => [
          'user_feature_date' => ['user_id', 'feature_key', 'usage_date'],
          'feature_date' => ['feature_key', 'usage_date'],
        ],
      ]);
    }
  }

}

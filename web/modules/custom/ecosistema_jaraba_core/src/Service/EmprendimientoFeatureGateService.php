<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\ValueObject\FeatureGateResult;
use Psr\Log\LoggerInterface;

/**
 * Servicio de Feature Gating para el vertical Emprendimiento.
 *
 * Verifica limites de uso por plan consultando FreemiumVerticalLimit
 * y el contador de uso diario en la tabla {emprendimiento_feature_usage}.
 * Integra con UpgradeTriggerService para disparar modales de upgrade.
 *
 * Plan Elevacion Emprendimiento v1 — Fase 4
 *
 * Features gestionadas:
 * - hypotheses_active: Hipotesis activas
 * - experiments_monthly: Experimentos mensuales
 * - copilot_sessions_daily: Sesiones copilot por dia
 * - mentoring_sessions_monthly: Sesiones mentoring mensuales
 * - bmc_drafts: Borradores BMC
 * - calculadora_uses: Usos calculadora
 *
 * @see \Drupal\ecosistema_jaraba_core\Entity\FreemiumVerticalLimit
 * @see \Drupal\ecosistema_jaraba_core\Service\UpgradeTriggerService
 */
class EmprendimientoFeatureGateService {

  /**
   * Vertical ID constante.
   */
  protected const VERTICAL = 'emprendimiento';

  /**
   * Mapa de upgrade de planes.
   */
  protected const PLAN_UPGRADE = [
    'free' => 'starter',
    'starter' => 'profesional',
    'profesional' => 'business',
  ];

  /**
   * Constructor.
   */
  public function __construct(
    protected UpgradeTriggerService $upgradeTriggerService,
    protected Connection $database,
    protected AccountProxyInterface $currentUser,
    protected LoggerInterface $logger,
  ) {
  }

  /**
   * Verifica si el usuario puede usar una feature del vertical emprendimiento.
   *
   * @param int $userId
   *   El ID del usuario.
   * @param string $featureKey
   *   La feature a verificar (hypotheses_active, experiments_monthly, etc.).
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
      // Fire upgrade trigger on denial.
      // Plan Elevación Emprendimiento v2 — Fase 7 (G7).
      try {
        $tenant = \Drupal::service('ecosistema_jaraba_core.tenant_context')->getCurrentTenant();
        if ($tenant) {
          $this->upgradeTriggerService->fire('limit_reached', $tenant, [
            'feature_key' => $featureKey,
            'vertical' => 'emprendimiento',
          ]);
        }
      }
      catch (\Exception $e) {
        // Silently fail - trigger is non-critical.
      }

      $upgradePlan = self::PLAN_UPGRADE[$plan] ?? 'starter';
      $message = 'Has alcanzado el limite de tu plan.';
      if ($limitEntity) {
        $entityMessage = $limitEntity->get('upgrade_message');
        if ($entityMessage) {
          $message = $entityMessage;
        }
      }
      return FeatureGateResult::denied(
        $featureKey,
        $plan,
        $limitValue,
        $used,
        $message,
        $upgradePlan,
      );
    }

    return FeatureGateResult::allowed($featureKey, $plan, $remaining, $limitValue, $used);
  }

  /**
   * Registra un uso de la feature (incrementa contador diario).
   *
   * @param int $userId
   *   El ID del usuario.
   * @param string $featureKey
   *   La feature utilizada.
   */
  public function recordUsage(int $userId, string $featureKey): void {
    $today = date('Y-m-d');

    $this->ensureTable();

    // Upsert: incrementar contador o crear registro.
    $existing = $this->database->select('emprendimiento_feature_usage', 'u')
      ->fields('u', ['id', 'usage_count'])
      ->condition('user_id', $userId)
      ->condition('feature_key', $featureKey)
      ->condition('usage_date', $today)
      ->execute()
      ->fetchObject();

    if ($existing) {
      $this->database->update('emprendimiento_feature_usage')
        ->fields(['usage_count' => $existing->usage_count + 1])
        ->condition('id', $existing->id)
        ->execute();
    }
    else {
      $this->database->insert('emprendimiento_feature_usage')
        ->fields([
          'user_id' => $userId,
          'feature_key' => $featureKey,
          'usage_date' => $today,
          'usage_count' => 1,
        ])
        ->execute();
    }

    $this->logger->info('Feature usage recorded: @feature for user @user (date: @date)', [
      '@feature' => $featureKey,
      '@user' => $userId,
      '@date' => $today,
    ]);
  }

  /**
   * Obtiene el plan actual del usuario para el vertical emprendimiento.
   *
   * Resuelve el plan via tenant del usuario. Fallback a 'free'.
   *
   * @param int $userId
   *   El ID del usuario.
   *
   * @return string
   *   ID del plan (free, starter, profesional, business, enterprise).
   */
  public function getUserPlan(int $userId): string {
    try {
      /** @var \Drupal\ecosistema_jaraba_core\Service\TenantContextService $tenantContext */
      $tenantContext = \Drupal::service('ecosistema_jaraba_core.tenant_context');
      $tenant = $tenantContext->getCurrentTenant();

      if ($tenant) {
        $plan = $tenant->getSubscriptionPlan();
        if ($plan) {
          return $plan->id();
        }
      }
    }
    catch (\Exception $e) {
      // Servicio no disponible — asumir free.
    }

    return 'free';
  }

  /**
   * Obtiene el contador de uso diario para una feature.
   *
   * @param int $userId
   *   El ID del usuario.
   * @param string $featureKey
   *   La feature a consultar.
   *
   * @return int
   *   Numero de usos hoy.
   */
  protected function getUsageCount(int $userId, string $featureKey): int {
    $this->ensureTable();
    $today = date('Y-m-d');

    $count = $this->database->select('emprendimiento_feature_usage', 'u')
      ->fields('u', ['usage_count'])
      ->condition('user_id', $userId)
      ->condition('feature_key', $featureKey)
      ->condition('usage_date', $today)
      ->execute()
      ->fetchField();

    return $count ? (int) $count : 0;
  }

  /**
   * Asegura que la tabla de uso existe.
   */
  protected function ensureTable(): void {
    if (!$this->database->schema()->tableExists('emprendimiento_feature_usage')) {
      $this->database->schema()->createTable('emprendimiento_feature_usage', [
        'description' => 'Tracking de uso diario de features del vertical emprendimiento.',
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

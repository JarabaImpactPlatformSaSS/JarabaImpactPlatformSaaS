<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\ValueObject\FeatureGateResult;
use Psr\Log\LoggerInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;

/**
 * Servicio de Feature Gating para el vertical JarabaLex.
 *
 * Verifica límites de uso por plan consultando FreemiumVerticalLimit
 * y el contador de uso diario en la tabla {jarabalex_feature_usage}.
 * Integra con UpgradeTriggerService para disparar modales de upgrade.
 *
 * Plan Elevación JarabaLex v1 — Fase 4
 *
 * Features gestionadas:
 * - searches_per_month: Búsquedas jurídicas por mes
 * - max_alerts: Alertas jurídicas activas
 * - max_bookmarks: Resoluciones guardadas
 * - citation_insert: Inserciones de citas automatizadas
 * - digest_access: Acceso a digest semanal
 * - api_access: Acceso a la API REST
 *
 * @see \Drupal\ecosistema_jaraba_core\Entity\FreemiumVerticalLimit
 * @see \Drupal\ecosistema_jaraba_core\Service\UpgradeTriggerService
 */
class JarabaLexFeatureGateService {

  /**
   * Vertical ID constante.
   */
  protected const VERTICAL = 'jarabalex';

  /**
   * Mapa de upgrade de planes.
   */
  protected const PLAN_UPGRADE = [
    'free' => 'starter',
    'starter' => 'profesional',
    'profesional' => 'business',
  ];

  /**
   * Mapa de feature_key a trigger_type para UpgradeTriggerService.
   */
  protected const FEATURE_TRIGGER_MAP = [
    'searches_per_month' => 'search_limit_reached',
    'max_alerts' => 'alert_limit_reached',
    'citation_insert' => 'citation_blocked',
    'digest_access' => 'digest_blocked',
    'api_access' => 'api_blocked',
  ];

  /**
   * Constructor.
   */
  public function __construct(
    protected UpgradeTriggerService $upgradeTriggerService,
    protected Connection $database,
    protected AccountProxyInterface $currentUser,
    protected LoggerInterface $logger,
    protected readonly TenantContextService $tenantContext, // AUDIT-CONS-N10: Proper DI for tenant context.
  ) {
  }

  /**
   * Verifica si el usuario puede usar una feature del vertical JarabaLex.
   *
   * @param int $userId
   *   El ID del usuario.
   * @param string $featureKey
   *   La feature a verificar (searches_per_month, max_alerts, etc.).
   * @param string|null $plan
   *   Plan del usuario. Si NULL, se resuelve internamente.
   *
   * @return \Drupal\ecosistema_jaraba_core\ValueObject\FeatureGateResult
   *   Resultado con allowed, remaining, limit, upgradeMessage.
   */
  public function check(int $userId, string $featureKey, ?string $plan = NULL): FeatureGateResult {
    $plan = $plan ?? $this->getUserPlan($userId);

    // Obtener límite configurado.
    $limitEntity = $this->upgradeTriggerService->getVerticalLimit(self::VERTICAL, $plan, $featureKey);

    if (!$limitEntity) {
      // Sin configuración de límite = permitido sin restricción.
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
      $result = FeatureGateResult::denied(
        $featureKey,
        $plan,
        0,
        0,
        $limitEntity->get('upgrade_message') ?: 'Esta función no está disponible en tu plan actual.',
        $upgradePlan,
      );
      $this->fireDeniedTrigger($userId, $featureKey, $plan, 0, 0);
      return $result;
    }

    // Verificar uso actual.
    $used = $this->getUsageCount($userId, $featureKey);
    $remaining = max(0, $limitValue - $used);

    if ($remaining <= 0) {
      $upgradePlan = self::PLAN_UPGRADE[$plan] ?? 'starter';
      $result = FeatureGateResult::denied(
        $featureKey,
        $plan,
        $limitValue,
        $used,
        $limitEntity->get('upgrade_message') ?: 'Has alcanzado el límite de tu plan.',
        $upgradePlan,
      );
      $this->fireDeniedTrigger($userId, $featureKey, $plan, $limitValue, $used);
      return $result;
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
    $existing = $this->database->select('jarabalex_feature_usage', 'u')
      ->fields('u', ['id', 'usage_count'])
      ->condition('user_id', $userId)
      ->condition('feature_key', $featureKey)
      ->condition('usage_date', $today)
      ->execute()
      ->fetchObject();

    if ($existing) {
      $this->database->update('jarabalex_feature_usage')
        ->fields(['usage_count' => $existing->usage_count + 1])
        ->condition('id', $existing->id)
        ->execute();
    }
    else {
      $this->database->insert('jarabalex_feature_usage')
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
   * Obtiene el plan actual del usuario para el vertical JarabaLex.
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
      $tenantContext = $this->tenantContext;
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
   *   Número de usos hoy.
   */
  protected function getUsageCount(int $userId, string $featureKey): int {
    $this->ensureTable();
    $today = date('Y-m-d');

    $count = $this->database->select('jarabalex_feature_usage', 'u')
      ->fields('u', ['usage_count'])
      ->condition('user_id', $userId)
      ->condition('feature_key', $featureKey)
      ->condition('usage_date', $today)
      ->execute()
      ->fetchField();

    return $count ? (int) $count : 0;
  }

  /**
   * Dispara un trigger de upgrade cuando se deniega una feature.
   *
   * @param int $userId
   *   El ID del usuario.
   * @param string $featureKey
   *   La feature denegada.
   * @param string $plan
   *   Plan actual del usuario.
   * @param int $limit
   *   Límite configurado.
   * @param int $used
   *   Uso actual.
   */
  protected function fireDeniedTrigger(int $userId, string $featureKey, string $plan, int $limit, int $used): void {
    $triggerType = self::FEATURE_TRIGGER_MAP[$featureKey] ?? NULL;
    if (!$triggerType) {
      return;
    }

    try {
      $tenantContext = $this->tenantContext;
      $tenant = $tenantContext->getCurrentTenant();

      if (!$tenant) {
        return;
      }

      $this->upgradeTriggerService->fire($triggerType, $tenant, [
        'feature_key' => $featureKey,
        'current_usage' => $used,
        'limit_value' => $limit,
        'user_id' => $userId,
        'vertical' => self::VERTICAL,
      ]);
    }
    catch (\Exception $e) {
      $this->logger->warning('JarabaLex FeatureGate: Error disparando trigger @type: @error', [
        '@type' => $triggerType,
        '@error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Asegura que la tabla de uso existe.
   */
  protected function ensureTable(): void {
    if (!$this->database->schema()->tableExists('jarabalex_feature_usage')) {
      $this->database->schema()->createTable('jarabalex_feature_usage', [
        'description' => 'Tracking de uso diario de features del vertical JarabaLex.',
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

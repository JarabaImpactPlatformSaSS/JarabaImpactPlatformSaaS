<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\ValueObject\FeatureGateResult;
use Psr\Log\LoggerInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;

/**
 * Servicio de Feature Gating para el vertical ComercioConecta.
 *
 * Verifica limites de uso por plan consultando FreemiumVerticalLimit
 * y el contador de uso en la tabla {comercioconecta_feature_usage}.
 * Integra con UpgradeTriggerService para disparar modales de upgrade.
 *
 * Plan Elevacion ComercioConecta Clase Mundial v1 — Fase 1
 *
 * Features gestionadas:
 * - products: Productos publicados (10/50/-1)
 * - flash_offers_active: Ofertas flash activas (2/10/-1)
 * - copilot_uses_per_month: Usos mensuales del copilot IA (5/50/-1)
 * - qr_codes: Codigos QR generados (0/10/-1)
 * - seo_local_audit: Auditoria SEO local (0/1/-1)
 * - analytics_advanced: Analytics avanzados (0/0/-1)
 * - pos_integration: Integracion TPV (0/0/-1)
 *
 * @see \Drupal\ecosistema_jaraba_core\Entity\FreemiumVerticalLimit
 * @see \Drupal\ecosistema_jaraba_core\Service\UpgradeTriggerService
 */
class ComercioConectaFeatureGateService {

  /**
   * Vertical ID constante.
   */
  protected const VERTICAL = 'comercioconecta';

  /**
   * Mapa de upgrade de planes.
   */
  protected const PLAN_UPGRADE = [
    'free' => 'starter',
    'starter' => 'profesional',
    'profesional' => 'enterprise',
  ];

  /**
   * Features que se miden por acumulacion total (no diario/mensual).
   */
  protected const CUMULATIVE_FEATURES = [
    'products',
  ];

  /**
   * Features que se miden por periodo mensual.
   */
  protected const MONTHLY_FEATURES = [
    'flash_offers_active',
    'copilot_uses_per_month',
  ];

  /**
   * Features binarias (0 = no disponible, -1 = disponible).
   */
  protected const BINARY_FEATURES = [
    'qr_codes',
    'seo_local_audit',
    'analytics_advanced',
    'pos_integration',
  ];

  /**
   * Constructor.
   */
  public function __construct(
    protected readonly UpgradeTriggerService $upgradeTriggerService,
    protected readonly Connection $database,
    protected readonly AccountProxyInterface $currentUser,
    protected readonly LoggerInterface $logger,
    protected readonly TenantContextService $tenantContext, // AUDIT-CONS-N10: Proper DI for tenant context.
  ) {
  }

  /**
   * Verifica si el usuario puede usar una feature del vertical ComercioConecta.
   *
   * @param int $userId
   *   El ID del usuario.
   * @param string $featureKey
   *   La feature a verificar (products, flash_offers_active, etc.).
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
        $limitEntity->get('upgrade_message') ?: $this->getDefaultUpgradeMessage($featureKey),
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
        $limitEntity->get('upgrade_message') ?: $this->getDefaultUpgradeMessage($featureKey),
        $upgradePlan,
      );
    }

    return FeatureGateResult::allowed($featureKey, $plan, $remaining, $limitValue, $used);
  }

  /**
   * Verifica feature y dispara UpgradeTrigger si denegado.
   *
   * Metodo de conveniencia que combina check() + fire() en una sola
   * llamada para los servicios que necesitan ambos comportamientos.
   *
   * @param int $userId
   *   El ID del usuario.
   * @param string $featureKey
   *   La feature a verificar.
   * @param array $context
   *   Contexto adicional para el trigger.
   *
   * @return \Drupal\ecosistema_jaraba_core\ValueObject\FeatureGateResult
   *   Resultado de la verificacion.
   */
  public function checkAndFire(int $userId, string $featureKey, array $context = []): FeatureGateResult {
    $result = $this->check($userId, $featureKey);

    if (!$result->isAllowed()) {
      $this->fireUpgradeTrigger($featureKey, $userId, $context);
    }

    return $result;
  }

  /**
   * Registra un uso de la feature (incrementa contador).
   *
   * Para features mensuales, agrupa por YYYY-MM.
   * Para features acumulativas, usa 'cumulative' como fecha.
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
    $existing = $this->database->select('comercioconecta_feature_usage', 'u')
      ->fields('u', ['id', 'usage_count'])
      ->condition('user_id', $userId)
      ->condition('feature_key', $featureKey)
      ->condition('usage_date', $period)
      ->execute()
      ->fetchObject();

    if ($existing) {
      $this->database->update('comercioconecta_feature_usage')
        ->fields(['usage_count' => $existing->usage_count + 1])
        ->condition('id', $existing->id)
        ->execute();
    }
    else {
      $this->database->insert('comercioconecta_feature_usage')
        ->fields([
          'user_id' => $userId,
          'feature_key' => $featureKey,
          'usage_date' => $period,
          'usage_count' => 1,
        ])
        ->execute();
    }

    $this->logger->info('ComercioConecta feature usage recorded: @feature for user @user (period: @period)', [
      '@feature' => $featureKey,
      '@user' => $userId,
      '@period' => $period,
    ]);
  }

  /**
   * Obtiene el plan actual del usuario para el vertical ComercioConecta.
   *
   * Resuelve el plan via tenant del usuario. Fallback a 'free'.
   *
   * @param int $userId
   *   El ID del usuario.
   *
   * @return string
   *   ID del plan (free, starter, profesional, enterprise).
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
   * Obtiene los usos restantes para una feature.
   *
   * @param int $userId
   *   El ID del usuario.
   * @param string $featureKey
   *   La feature a consultar.
   *
   * @return int
   *   Usos restantes (-1 si ilimitado, 0 si agotado).
   */
  public function getRemainingUsage(int $userId, string $featureKey): int {
    $plan = $this->getUserPlan($userId);
    $limitEntity = $this->upgradeTriggerService->getVerticalLimit(self::VERTICAL, $plan, $featureKey);

    if (!$limitEntity) {
      return -1;
    }

    $limitValue = (int) $limitEntity->get('limit_value');
    if ($limitValue === -1) {
      return -1;
    }
    if ($limitValue === 0) {
      return 0;
    }

    $used = $this->getUsageCount($userId, $featureKey);
    return max(0, $limitValue - $used);
  }

  /**
   * Resetea contadores mensuales para el primer dia del mes.
   *
   * Ejecutar via cron el dia 1 de cada mes.
   */
  public function resetMonthlyUsage(): void {
    $this->ensureTable();

    $previousMonth = date('Y-m', strtotime('-1 month'));

    $deleted = $this->database->delete('comercioconecta_feature_usage')
      ->condition('usage_date', $previousMonth . '%', 'LIKE')
      ->execute();

    $this->logger->info('ComercioConecta monthly usage reset: @count records deleted for period @period', [
      '@count' => $deleted,
      '@period' => $previousMonth,
    ]);
  }

  /**
   * Obtiene el porcentaje de comision para un comerciante segun su plan.
   *
   * @param int $userId
   *   El ID del comerciante.
   *
   * @return float
   *   Porcentaje de comision (15.0, 10.0, 5.0).
   */
  public function getCommissionRate(int $userId): float {
    $plan = $this->getUserPlan($userId);
    $limitEntity = $this->upgradeTriggerService->getVerticalLimit(self::VERTICAL, $plan, 'commission_pct');

    if (!$limitEntity) {
      return 15.0;
    }

    return (float) $limitEntity->get('limit_value');
  }

  /**
   * Obtiene el contador de uso actual para una feature.
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

    if (in_array($featureKey, self::CUMULATIVE_FEATURES, TRUE)) {
      // Para features acumulativas, contar entidades directamente.
      return $this->getCumulativeCount($userId, $featureKey);
    }

    $count = $this->database->select('comercioconecta_feature_usage', 'u')
      ->fields('u', ['usage_count'])
      ->condition('user_id', $userId)
      ->condition('feature_key', $featureKey)
      ->condition('usage_date', $period)
      ->execute()
      ->fetchField();

    return $count ? (int) $count : 0;
  }

  /**
   * Obtiene el conteo acumulativo real de entidades.
   *
   * Para 'products': cuenta ProductRetail activos del usuario.
   *
   * @param int $userId
   *   El ID del usuario.
   * @param string $featureKey
   *   La feature acumulativa.
   *
   * @return int
   *   Conteo actual de entidades.
   */
  protected function getCumulativeCount(int $userId, string $featureKey): int {
    try {
      $entityTypeManager = \Drupal::entityTypeManager();

      if ($featureKey === 'products') {
        $count = $entityTypeManager
          ->getStorage('product_retail')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('user_id', $userId)
          ->condition('status', 1)
          ->count()
          ->execute();
        return (int) $count;
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('Error counting cumulative @feature for user @user: @error', [
        '@feature' => $featureKey,
        '@user' => $userId,
        '@error' => $e->getMessage(),
      ]);
    }

    return 0;
  }

  /**
   * Obtiene el periodo de uso segun el tipo de feature.
   *
   * @param string $featureKey
   *   La feature key.
   *
   * @return string
   *   Periodo: 'YYYY-MM' para mensuales, 'cumulative' para acumulativos.
   */
  protected function getUsagePeriod(string $featureKey): string {
    if (in_array($featureKey, self::CUMULATIVE_FEATURES, TRUE)) {
      return 'cumulative';
    }

    if (in_array($featureKey, self::MONTHLY_FEATURES, TRUE)) {
      return date('Y-m');
    }

    // Default: diario.
    return date('Y-m-d');
  }

  /**
   * Dispara un UpgradeTrigger para la feature denegada.
   *
   * @param string $featureKey
   *   La feature denegada.
   * @param int $userId
   *   El ID del usuario.
   * @param array $context
   *   Contexto adicional.
   */
  protected function fireUpgradeTrigger(string $featureKey, int $userId, array $context = []): void {
    try {
      $triggerType = 'comercio_' . $featureKey . '_limit_reached';
      $tenantContext = $this->tenantContext;
      $tenant = $tenantContext->getCurrentTenant();

      if ($tenant) {
        $this->upgradeTriggerService->fire($triggerType, $tenant, array_merge($context, [
          'feature_key' => $featureKey,
          'vertical' => self::VERTICAL,
          'user_id' => $userId,
        ]));
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('Error firing upgrade trigger for @feature: @error', [
        '@feature' => $featureKey,
        '@error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Obtiene un mensaje de upgrade por defecto para una feature.
   *
   * @param string $featureKey
   *   La feature key.
   *
   * @return string
   *   Mensaje de upgrade localizable.
   */
  protected function getDefaultUpgradeMessage(string $featureKey): string {
    $messages = [
      'products' => 'Has alcanzado el limite de productos de tu plan. Actualiza para publicar mas.',
      'flash_offers_active' => 'Has alcanzado el limite de ofertas flash activas. Actualiza para crear mas ofertas.',
      'copilot_uses_per_month' => 'Has agotado tus consultas IA del mes. Actualiza para mas usos.',
      'qr_codes' => 'Los codigos QR no estan disponibles en tu plan actual.',
      'seo_local_audit' => 'La auditoria SEO local no esta disponible en tu plan actual.',
      'analytics_advanced' => 'Los analytics avanzados no estan disponibles en tu plan actual.',
      'pos_integration' => 'La integracion TPV no esta disponible en tu plan actual.',
    ];

    return $messages[$featureKey] ?? 'Esta funcion no esta disponible en tu plan actual.';
  }

  /**
   * Asegura que la tabla de uso existe.
   */
  protected function ensureTable(): void {
    if (!$this->database->schema()->tableExists('comercioconecta_feature_usage')) {
      $this->database->schema()->createTable('comercioconecta_feature_usage', [
        'description' => 'Tracking de uso de features del vertical ComercioConecta.',
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

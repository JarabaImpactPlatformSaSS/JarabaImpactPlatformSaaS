<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\ValueObject\FeatureGateResult;
use Psr\Log\LoggerInterface;

/**
 * Servicio de Feature Gating para el vertical ServiciosConecta.
 *
 * Verifica limites de uso por plan consultando FreemiumVerticalLimit
 * y el contador de uso en la tabla {serviciosconecta_feature_usage}.
 * Integra con UpgradeTriggerService para disparar modales de upgrade.
 *
 * Plan Elevacion ServiciosConecta Clase Mundial v1 — Fase 0
 *
 * Features gestionadas:
 * - services: Servicios publicados (3/10/-1)
 * - bookings_per_month: Reservas mensuales recibidas (10/50/-1)
 * - calendar_sync: Sincronizacion con calendario externo (binario)
 * - buzon_confianza: Buzon de confianza (binario)
 * - firma_digital: Firma digital de contratos (binario)
 * - ai_triage: Triage IA de solicitudes (binario)
 * - video_conferencing: Videoconferencia integrada (binario)
 * - analytics_dashboard: Dashboard de analytics avanzado (binario)
 *
 * @see \Drupal\ecosistema_jaraba_core\Entity\FreemiumVerticalLimit
 * @see \Drupal\ecosistema_jaraba_core\Service\UpgradeTriggerService
 */
class ServiciosConectaFeatureGateService {

  /**
   * Vertical ID constante.
   */
  protected const VERTICAL = 'serviciosconecta';

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
    'services',
  ];

  /**
   * Features que se miden por periodo mensual.
   */
  protected const MONTHLY_FEATURES = [
    'bookings_per_month',
  ];

  /**
   * Features binarias (0 = no disponible, -1 = disponible).
   */
  protected const BINARY_FEATURES = [
    'calendar_sync',
    'buzon_confianza',
    'firma_digital',
    'ai_triage',
    'video_conferencing',
    'analytics_dashboard',
  ];

  /**
   * Constructor.
   */
  public function __construct(
    protected readonly UpgradeTriggerService $upgradeTriggerService,
    protected readonly Connection $database,
    protected readonly AccountProxyInterface $currentUser,
    protected readonly LoggerInterface $logger,
  ) {
  }

  /**
   * Verifica si el usuario puede usar una feature del vertical ServiciosConecta.
   *
   * @param int $userId
   *   El ID del usuario.
   * @param string $featureKey
   *   La feature a verificar (services, bookings_per_month, etc.).
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
    $existing = $this->database->select('serviciosconecta_feature_usage', 'u')
      ->fields('u', ['id', 'usage_count'])
      ->condition('user_id', $userId)
      ->condition('feature_key', $featureKey)
      ->condition('usage_date', $period)
      ->execute()
      ->fetchObject();

    if ($existing) {
      $this->database->update('serviciosconecta_feature_usage')
        ->fields(['usage_count' => $existing->usage_count + 1])
        ->condition('id', $existing->id)
        ->execute();
    }
    else {
      $this->database->insert('serviciosconecta_feature_usage')
        ->fields([
          'user_id' => $userId,
          'feature_key' => $featureKey,
          'usage_date' => $period,
          'usage_count' => 1,
        ])
        ->execute();
    }

    $this->logger->info('ServiciosConecta feature usage recorded: @feature for user @user (period: @period)', [
      '@feature' => $featureKey,
      '@user' => $userId,
      '@period' => $period,
    ]);
  }

  /**
   * Obtiene el plan actual del usuario para el vertical ServiciosConecta.
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

    $deleted = $this->database->delete('serviciosconecta_feature_usage')
      ->condition('usage_date', $previousMonth . '%', 'LIKE')
      ->execute();

    $this->logger->info('ServiciosConecta monthly usage reset: @count records deleted for period @period', [
      '@count' => $deleted,
      '@period' => $previousMonth,
    ]);
  }

  /**
   * Obtiene el porcentaje de comision para un profesional segun su plan.
   *
   * @param int $userId
   *   El ID del profesional.
   *
   * @return float
   *   Porcentaje de comision (default 10.0).
   */
  public function getCommissionRate(int $userId): float {
    $plan = $this->getUserPlan($userId);
    $limitEntity = $this->upgradeTriggerService->getVerticalLimit(self::VERTICAL, $plan, 'commission_pct');

    if (!$limitEntity) {
      return 10.0;
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

    $count = $this->database->select('serviciosconecta_feature_usage', 'u')
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
   * Para 'services': cuenta ServiceOffering activos del usuario
   * a traves de su ProviderProfile.
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

      if ($featureKey === 'services') {
        // Primero obtener el ProviderProfile del usuario.
        $providerIds = $entityTypeManager
          ->getStorage('provider_profile')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('uid', $userId)
          ->execute();

        if (empty($providerIds)) {
          return 0;
        }

        $count = $entityTypeManager
          ->getStorage('service_offering')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('provider_id', array_values($providerIds), 'IN')
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
      $triggerType = 'servicios_' . $featureKey . '_limit_reached';
      $tenantContext = \Drupal::service('ecosistema_jaraba_core.tenant_context');
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
      'services' => 'Has alcanzado el limite de servicios de tu plan. Actualiza para publicar mas.',
      'bookings_per_month' => 'Has alcanzado el limite de reservas mensuales. Actualiza para no perder clientes.',
      'calendar_sync' => 'La sincronizacion con calendario externo no esta disponible en tu plan actual.',
      'buzon_confianza' => 'El buzon de confianza no esta disponible en tu plan actual.',
      'firma_digital' => 'La firma digital no esta disponible en tu plan actual.',
      'ai_triage' => 'El triage IA de solicitudes no esta disponible en tu plan actual.',
      'video_conferencing' => 'La videoconferencia integrada no esta disponible en tu plan actual.',
      'analytics_dashboard' => 'El dashboard de analytics avanzado no esta disponible en tu plan actual.',
    ];

    return $messages[$featureKey] ?? 'Esta funcion no esta disponible en tu plan actual.';
  }

  /**
   * Asegura que la tabla de uso existe.
   */
  protected function ensureTable(): void {
    if (!$this->database->schema()->tableExists('serviciosconecta_feature_usage')) {
      $this->database->schema()->createTable('serviciosconecta_feature_usage', [
        'description' => 'Tracking de uso de features del vertical ServiciosConecta.',
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

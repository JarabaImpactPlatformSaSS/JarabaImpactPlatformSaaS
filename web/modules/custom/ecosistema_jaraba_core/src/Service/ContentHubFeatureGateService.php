<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\ValueObject\FeatureGateResult;
use Psr\Log\LoggerInterface;

/**
 * Servicio de Feature Gating para el vertical Content Hub.
 *
 * Verifica limites de uso por plan consultando FreemiumVerticalLimit
 * y el contador de uso en la tabla {content_hub_feature_usage}.
 *
 * Features gestionadas:
 * - articles_limit: Articulos publicados por tenant
 * - categories_limit: Categorias creadas
 * - authors_limit: Autores editoriales
 *
 * @see \Drupal\ecosistema_jaraba_core\Entity\FreemiumVerticalLimit
 * @see \Drupal\ecosistema_jaraba_core\Service\UpgradeTriggerService
 */
class ContentHubFeatureGateService {

  /**
   * Vertical ID constante.
   */
  protected const VERTICAL = 'jaraba_content_hub';

  /**
   * Mapa de upgrade de planes.
   */
  protected const PLAN_UPGRADE = [
    'free' => 'starter',
    'starter' => 'profesional',
    'profesional' => 'business',
  ];

  /**
   * Features acumulativas (sin periodo, contador total).
   */
  protected const CUMULATIVE_FEATURES = [
    'articles_limit',
    'categories_limit',
    'authors_limit',
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
   * Verifica si el usuario puede usar una feature del vertical content hub.
   *
   * @param int $userId
   *   El ID del usuario.
   * @param string $featureKey
   *   La feature a verificar (articles_limit, categories_limit, etc.).
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
      return FeatureGateResult::allowed($featureKey, $plan);
    }

    $limitValue = (int) $limitEntity->get('limit_value');

    if ($limitValue === -1) {
      return FeatureGateResult::allowed($featureKey, $plan, -1, -1);
    }

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
   * @param int $userId
   *   El ID del usuario.
   * @param string $featureKey
   *   La feature utilizada.
   */
  public function recordUsage(int $userId, string $featureKey): void {
    $period = $this->getUsagePeriod($featureKey);

    $this->ensureTable();

    $existing = $this->database->select('content_hub_feature_usage', 'u')
      ->fields('u', ['id', 'usage_count'])
      ->condition('user_id', $userId)
      ->condition('feature_key', $featureKey)
      ->condition('usage_date', $period)
      ->execute()
      ->fetchObject();

    if ($existing) {
      $this->database->update('content_hub_feature_usage')
        ->fields(['usage_count' => $existing->usage_count + 1])
        ->condition('id', $existing->id)
        ->execute();
    }
    else {
      $this->database->insert('content_hub_feature_usage')
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
   * Obtiene el plan actual del usuario.
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

    $count = $this->database->select('content_hub_feature_usage', 'u')
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
   *   'cumulative' para features acumulativas, Y-m-d para diarias.
   */
  protected function getUsagePeriod(string $featureKey): string {
    if (in_array($featureKey, self::CUMULATIVE_FEATURES, TRUE)) {
      return 'cumulative';
    }

    return date('Y-m-d');
  }

  /**
   * Asegura que la tabla de uso existe.
   */
  protected function ensureTable(): void {
    if (!$this->database->schema()->tableExists('content_hub_feature_usage')) {
      $this->database->schema()->createTable('content_hub_feature_usage', [
        'description' => 'Tracking de uso de features del vertical content hub.',
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

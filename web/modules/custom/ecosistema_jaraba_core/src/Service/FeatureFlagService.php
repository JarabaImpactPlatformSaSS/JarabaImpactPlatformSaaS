<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Entity\FeatureFlag;
use Psr\Log\LoggerInterface;

/**
 * Central feature flag broker (HAL-AI-11).
 *
 * Resolves whether a feature flag is active for the current context.
 * Supports 5 scopes: global, vertical, tenant, plan, percentage.
 *
 * Usage in PHP:
 *   $featureFlags->isEnabled('ai_proactive_insights', $tenantId);
 *
 * Usage in Twig (via TwigExtension):
 *   {% if feature_flag('ai_proactive_insights') %}...{% endif %}
 */
class FeatureFlagService {

  /**
   * Constructor.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ?TenantContextService $tenantContext = NULL,
    protected ?LoggerInterface $logger = NULL,
  ) {}

  /**
   * Checks if a feature flag is enabled for the given context.
   *
   * @param string $flagId
   *   The feature flag machine name.
   * @param int|null $tenantId
   *   Optional tenant ID override. If NULL, resolved from TenantContextService.
   * @param string $planId
   *   Optional plan tier. If empty, resolved from tenant.
   * @param string $verticalId
   *   Optional vertical. If empty, resolved from tenant.
   *
   * @return bool
   *   TRUE if the flag is active for the current context.
   */
  public function isEnabled(string $flagId, ?int $tenantId = NULL, string $planId = '', string $verticalId = ''): bool {
    $flag = $this->loadFlag($flagId);

    if (!$flag || !$flag->isEnabled()) {
      return FALSE;
    }

    $scope = $flag->getScope();
    $conditions = $flag->getConditions();

    return match ($scope) {
      'global' => TRUE,
      'plan' => $this->checkPlanCondition($conditions, $planId, $tenantId),
      'tenant' => $this->checkTenantCondition($conditions, $tenantId),
      'vertical' => $this->checkVerticalCondition($conditions, $verticalId),
      'percentage' => $this->checkPercentageRollout($conditions, $tenantId),
      default => FALSE,
    };
  }

  /**
   * Gets all feature flags.
   *
   * @return \Drupal\ecosistema_jaraba_core\Entity\FeatureFlag[]
   *   Array of all feature flags.
   */
  public function getAll(): array {
    try {
      return $this->entityTypeManager->getStorage('feature_flag')->loadMultiple();
    } catch (\Exception $e) {
      return [];
    }
  }

  /**
   * Gets all enabled flags for a tenant context.
   *
   * @param int|null $tenantId
   *   The tenant ID.
   *
   * @return string[]
   *   Array of enabled flag IDs.
   */
  public function getEnabledFlags(?int $tenantId = NULL): array {
    $enabled = [];
    foreach ($this->getAll() as $flag) {
      if ($this->isEnabled($flag->id(), $tenantId)) {
        $enabled[] = $flag->id();
      }
    }
    return $enabled;
  }

  /**
   * Loads a feature flag by ID.
   */
  protected function loadFlag(string $flagId): ?FeatureFlag {
    try {
      $flag = $this->entityTypeManager->getStorage('feature_flag')->load($flagId);
      return $flag instanceof FeatureFlag ? $flag : NULL;
    } catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * Checks plan-based condition.
   */
  protected function checkPlanCondition(array $conditions, string $planId, ?int $tenantId): bool {
    $allowedPlans = $conditions['plans'] ?? [];
    if (empty($allowedPlans)) {
      return TRUE;
    }

    // Resolve plan from tenant if not provided.
    if (empty($planId) && $tenantId) {
      $planId = $this->resolveTenantPlan($tenantId);
    }

    return in_array($planId, $allowedPlans, TRUE);
  }

  /**
   * Checks tenant-based condition.
   */
  protected function checkTenantCondition(array $conditions, ?int $tenantId): bool {
    $allowedTenants = $conditions['tenant_ids'] ?? [];
    if (empty($allowedTenants)) {
      return TRUE;
    }

    $tenantId = $tenantId ?? $this->resolveCurrentTenantId();

    return in_array($tenantId, $allowedTenants, TRUE);
  }

  /**
   * Checks vertical-based condition.
   */
  protected function checkVerticalCondition(array $conditions, string $verticalId): bool {
    $allowedVerticals = $conditions['verticals'] ?? [];
    if (empty($allowedVerticals)) {
      return TRUE;
    }

    return in_array($verticalId, $allowedVerticals, TRUE);
  }

  /**
   * Checks percentage-based rollout using deterministic hash.
   */
  protected function checkPercentageRollout(array $conditions, ?int $tenantId): bool {
    $percentage = (int) ($conditions['percentage'] ?? 0);
    if ($percentage >= 100) {
      return TRUE;
    }
    if ($percentage <= 0) {
      return FALSE;
    }

    $tenantId = $tenantId ?? $this->resolveCurrentTenantId();
    // Deterministic: same tenant always gets same result for same flag.
    $hash = crc32("feature_flag_{$tenantId}");
    $bucket = abs($hash) % 100;

    return $bucket < $percentage;
  }

  /**
   * Resolves current tenant ID from TenantContextService.
   */
  protected function resolveCurrentTenantId(): ?int {
    if (!$this->tenantContext) {
      return NULL;
    }

    try {
      $tenant = $this->tenantContext->getCurrentTenant();
      return $tenant ? (int) $tenant->id() : NULL;
    } catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * Resolves tenant plan from entity.
   */
  protected function resolveTenantPlan(int $tenantId): string {
    try {
      $tenant = $this->entityTypeManager->getStorage('tenant')->load($tenantId);
      if ($tenant && $tenant->hasField('plan')) {
        return $tenant->get('plan')->value ?? 'starter';
      }
    } catch (\Exception $e) {
      // Fallback.
    }
    return 'starter';
  }

}

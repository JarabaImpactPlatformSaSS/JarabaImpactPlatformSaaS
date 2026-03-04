<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\ecosistema_jaraba_core\Entity\FairUsePolicy;
use Psr\Log\LoggerInterface;

/**
 * Fair Use Policy enforcement orchestrator.
 *
 * Central service for evaluating resource usage against configured
 * FairUsePolicy ConfigEntities. Handles tier-specific thresholds,
 * burst tolerance, grace periods, and enforcement decisions.
 *
 * Flow:
 *   1. Load FairUsePolicy (tier-specific -> _global fallback)
 *   2. Read resource limit via PlanResolverService
 *   3. Read current usage via TenantMeteringService or caller
 *   4. Calculate usage %, apply burst tolerance + grace period
 *   5. Return enforcement decision
 *   6. Set throttle/block flags via State API (SELF-HEALING-STATE-001)
 */
class FairUsePolicyService {

  /**
   * Default overage prices — fallback when no policy loaded.
   */
  protected const DEFAULT_OVERAGE_PRICES = [
    'api_calls' => 0.0001,
    'ai_tokens' => 0.00002,
    'storage_mb' => 0.001,
    'orders' => 0.50,
    'products' => 0.10,
    'customers' => 0.05,
    'emails_sent' => 0.001,
    'bandwidth_gb' => 0.05,
  ];

  /**
   * Default warning thresholds — fallback when no policy loaded.
   */
  protected const DEFAULT_THRESHOLDS = [70, 85, 95];

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected StateInterface $state,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Evaluates resource usage against Fair Use Policy.
   *
   * @param string $tenantId
   *   The tenant ID.
   * @param string $tier
   *   Canonical tier key (starter, professional, enterprise).
   * @param string $resource
   *   Resource key (ai_queries, copilot_uses_per_month, storage_gb, etc.).
   * @param int|float $currentUsage
   *   Current usage count for the resource in the billing period.
   * @param int $limit
   *   The plan limit for the resource (-1 = unlimited).
   *
   * @return array
   *   Decision array with keys:
   *   - decision: string (allow, warn, throttle, soft_block, hard_block)
   *   - allowed: bool
   *   - usage_pct: float
   *   - limit: int
   *   - effective_limit: int (limit + burst tolerance)
   *   - current: int|float
   *   - threshold_level: string (none, warning, critical, exceeded)
   *   - message: string|null
   */
  public function evaluate(string $tenantId, string $tier, string $resource, int|float $currentUsage, int $limit): array {
    // Unlimited resources bypass enforcement.
    if ($limit === -1) {
      return [
        'decision' => 'allow',
        'allowed' => TRUE,
        'usage_pct' => 0.0,
        'limit' => -1,
        'effective_limit' => -1,
        'current' => $currentUsage,
        'threshold_level' => 'none',
        'message' => NULL,
      ];
    }

    // Zero limit = resource not available.
    if ($limit === 0) {
      return [
        'decision' => 'hard_block',
        'allowed' => FALSE,
        'usage_pct' => 100.0,
        'limit' => 0,
        'effective_limit' => 0,
        'current' => $currentUsage,
        'threshold_level' => 'exceeded',
        'message' => "Resource {$resource} is not included in the {$tier} plan.",
      ];
    }

    $policy = $this->loadPolicy($tier);
    $burstPct = $policy ? $policy->getBurstTolerancePct() : 0;
    $effectiveLimit = (int) ($limit * (1 + $burstPct / 100));
    $usagePct = ($currentUsage / $limit) * 100;
    $thresholds = $policy ? $policy->getWarningThresholds() : self::DEFAULT_THRESHOLDS;

    // Determine threshold level.
    $thresholdLevel = 'none';
    if ($currentUsage > $effectiveLimit) {
      $thresholdLevel = 'exceeded';
    }
    elseif (count($thresholds) >= 3 && $usagePct >= $thresholds[2]) {
      $thresholdLevel = 'critical';
    }
    elseif (count($thresholds) >= 2 && $usagePct >= $thresholds[1]) {
      $thresholdLevel = 'critical';
    }
    elseif (count($thresholds) >= 1 && $usagePct >= $thresholds[0]) {
      $thresholdLevel = 'warning';
    }

    // Check grace period for exceeded.
    if ($thresholdLevel === 'exceeded' && $policy) {
      $graceHours = $policy->getGracePeriodHours();
      if ($graceHours > 0 && $this->isInGracePeriod($tenantId, $resource, $graceHours)) {
        // Downgrade to critical during grace.
        $thresholdLevel = 'critical';
      }
      else {
        // Start grace period if not already started.
        $this->startGracePeriod($tenantId, $resource);
      }
    }

    // Determine enforcement action.
    $decision = 'allow';
    if ($thresholdLevel !== 'none') {
      $decision = $policy
        ? $policy->getEnforcementAction($resource, $thresholdLevel)
        : 'warn';
    }

    $allowed = !in_array($decision, ['soft_block', 'hard_block'], TRUE);

    // Set State API flags for throttle/block (SELF-HEALING-STATE-001).
    if (in_array($decision, ['throttle', 'soft_block', 'hard_block'], TRUE)) {
      $this->setEnforcementFlag($tenantId, $resource, $decision);
    }

    // Log warnings.
    if ($thresholdLevel !== 'none') {
      $this->logger->warning('Fair Use: tenant=@tenant tier=@tier resource=@resource usage=@pct% level=@level decision=@decision', [
        '@tenant' => $tenantId,
        '@tier' => $tier,
        '@resource' => $resource,
        '@pct' => round($usagePct, 1),
        '@level' => $thresholdLevel,
        '@decision' => $decision,
      ]);
    }

    $message = NULL;
    if ($thresholdLevel !== 'none') {
      $message = "Tenant {$tenantId}: {$resource} at " . round($usagePct, 1) . "% ({$thresholdLevel}). Decision: {$decision}.";
    }

    return [
      'decision' => $decision,
      'allowed' => $allowed,
      'usage_pct' => round($usagePct, 1),
      'limit' => $limit,
      'effective_limit' => $effectiveLimit,
      'current' => $currentUsage,
      'threshold_level' => $thresholdLevel,
      'message' => $message,
    ];
  }

  /**
   * Gets warning thresholds for a tier.
   *
   * @param string $tier
   *   Canonical tier key.
   *
   * @return array
   *   Sorted threshold percentages.
   */
  public function getThresholds(string $tier): array {
    $policy = $this->loadPolicy($tier);
    return $policy ? $policy->getWarningThresholds() : self::DEFAULT_THRESHOLDS;
  }

  /**
   * Gets the overage unit price for a metric and tier.
   *
   * @param string $metric
   *   The metering metric key.
   * @param string $tier
   *   Canonical tier key (optional, for tier-specific pricing).
   *
   * @return float
   *   Unit price in EUR.
   */
  public function getUnitPrice(string $metric, string $tier = '_global'): float {
    $policy = $this->loadPolicy($tier);
    if ($policy) {
      $price = $policy->getOverageUnitPrice($metric, -1.0);
      if ($price >= 0) {
        return $price;
      }
    }

    return self::DEFAULT_OVERAGE_PRICES[$metric] ?? 0.0;
  }

  /**
   * Gets all overage unit prices for a tier.
   *
   * @param string $tier
   *   Canonical tier key.
   *
   * @return array
   *   Map of metric => price EUR.
   */
  public function getAllUnitPrices(string $tier = '_global'): array {
    $policy = $this->loadPolicy($tier);
    if ($policy) {
      $prices = $policy->getOverageUnitPrices();
      if (!empty($prices)) {
        return $prices;
      }
    }

    return self::DEFAULT_OVERAGE_PRICES;
  }

  /**
   * Checks if an enforcement flag is active for a tenant+resource.
   *
   * @param string $tenantId
   *   The tenant ID.
   * @param string $resource
   *   The resource key.
   *
   * @return string|null
   *   Active enforcement action (throttle, soft_block, hard_block), or NULL.
   */
  public function getActiveEnforcement(string $tenantId, string $resource): ?string {
    $key = "fair_use:enforcement:{$tenantId}:{$resource}";
    $data = $this->state->get($key);

    if (!is_array($data)) {
      return NULL;
    }

    // TTL auto-expiry: 2 hours.
    if (isset($data['timestamp']) && (time() - $data['timestamp']) > 7200) {
      $this->state->delete($key);
      return NULL;
    }

    return $data['action'] ?? NULL;
  }

  /**
   * Clears enforcement flag for a tenant+resource.
   */
  public function clearEnforcement(string $tenantId, string $resource): void {
    $this->state->delete("fair_use:enforcement:{$tenantId}:{$resource}");
    $this->state->delete("fair_use:grace:{$tenantId}:{$resource}");
  }

  /**
   * Loads the FairUsePolicy for a tier with _global fallback.
   *
   * @param string $tier
   *   Canonical tier key.
   *
   * @return \Drupal\ecosistema_jaraba_core\Entity\FairUsePolicy|null
   *   The policy entity, or NULL if none found.
   */
  protected function loadPolicy(string $tier): ?FairUsePolicy {
    try {
      $storage = $this->entityTypeManager->getStorage('fair_use_policy');

      // Tier-specific policy.
      $entity = $storage->load($tier);
      if ($entity instanceof FairUsePolicy && $entity->status()) {
        return $entity;
      }

      // Global fallback.
      if ($tier !== '_global') {
        $entity = $storage->load('_global');
        if ($entity instanceof FairUsePolicy && $entity->status()) {
          return $entity;
        }
      }
    }
    catch (\Throwable $e) {
      $this->logger->error('FairUsePolicyService: failed to load policy for tier @tier: @msg', [
        '@tier' => $tier,
        '@msg' => $e->getMessage(),
      ]);
    }

    return NULL;
  }

  /**
   * Sets an enforcement flag in State API with TTL.
   */
  protected function setEnforcementFlag(string $tenantId, string $resource, string $action): void {
    $key = "fair_use:enforcement:{$tenantId}:{$resource}";
    $this->state->set($key, [
      'action' => $action,
      'timestamp' => time(),
      'tenant_id' => $tenantId,
      'resource' => $resource,
    ]);
  }

  /**
   * Checks if tenant is within grace period for a resource.
   */
  protected function isInGracePeriod(string $tenantId, string $resource, int $graceHours): bool {
    $key = "fair_use:grace:{$tenantId}:{$resource}";
    $graceStart = $this->state->get($key);

    if (!$graceStart) {
      return FALSE;
    }

    $graceEnd = $graceStart + ($graceHours * 3600);
    return time() < $graceEnd;
  }

  /**
   * Starts grace period for a tenant+resource if not already started.
   */
  protected function startGracePeriod(string $tenantId, string $resource): void {
    $key = "fair_use:grace:{$tenantId}:{$resource}";
    if (!$this->state->get($key)) {
      $this->state->set($key, time());
    }
  }

}

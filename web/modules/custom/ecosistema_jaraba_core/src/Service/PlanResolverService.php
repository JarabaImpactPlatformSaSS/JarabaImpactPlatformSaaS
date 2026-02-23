<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Entity\SaasPlanFeatures;
use Drupal\ecosistema_jaraba_core\Entity\SaasPlanTierInterface;
use Psr\Log\LoggerInterface;

/**
 * Central broker for plan tier resolution, features and limits.
 *
 * Replaces hardcoded plan capabilities across the platform by reading
 * from SaasPlanTier and SaasPlanFeatures ConfigEntities.
 *
 * Resolution cascade for getFeatures():
 *   1. Specific: {vertical}_{tier} (e.g. agroconecta_professional)
 *   2. Default: _default_{tier} (e.g. _default_professional)
 *   3. NULL (no config found)
 */
class PlanResolverService {

  /**
   * Cached alias-to-tier_key map, built lazily.
   *
   * @var array|null
   */
  protected ?array $aliasMap = NULL;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Normalizes a plan name to its canonical tier key.
   *
   * Looks up SaasPlanTier aliases to resolve names like "basico", "basic",
   * "free", "Pro" into canonical keys (starter, professional, enterprise).
   *
   * @param string $planName
   *   Raw plan name from any source (Stripe, user input, migration, etc.).
   *
   * @return string
   *   Canonical tier key, or the original lowercased name if no alias matches.
   */
  public function normalize(string $planName): string {
    $normalized = mb_strtolower(trim($planName));
    if ($normalized === '') {
      return 'starter';
    }

    // Direct match: the planName IS a tier key.
    $tier = $this->entityTypeManager->getStorage('saas_plan_tier')->load($normalized);
    if ($tier instanceof SaasPlanTierInterface) {
      return $tier->getTierKey() ?: $normalized;
    }

    // Alias lookup.
    $map = $this->getAliasMap();
    if (isset($map[$normalized])) {
      return $map[$normalized];
    }

    $this->logger->notice('PlanResolver: No tier found for plan name "@name", returning as-is.', [
      '@name' => $planName,
    ]);

    return $normalized;
  }

  /**
   * Gets the SaasPlanFeatures config for a vertical+tier combination.
   *
   * Cascade: specific → _default → NULL.
   *
   * @param string $vertical
   *   Machine name of the vertical (e.g. 'agroconecta', 'empleabilidad').
   * @param string $tier
   *   Canonical tier key (e.g. 'starter', 'professional', 'enterprise').
   *
   * @return \Drupal\ecosistema_jaraba_core\Entity\SaasPlanFeatures|null
   *   The matching config entity, or NULL if none found.
   */
  public function getFeatures(string $vertical, string $tier): ?SaasPlanFeatures {
    $storage = $this->entityTypeManager->getStorage('saas_plan_features');

    // 1. Specific vertical+tier.
    $specificId = $vertical . '_' . $tier;
    $entity = $storage->load($specificId);
    if ($entity instanceof SaasPlanFeatures && $entity->status()) {
      return $entity;
    }

    // 2. Default fallback.
    $defaultId = '_default_' . $tier;
    $entity = $storage->load($defaultId);
    if ($entity instanceof SaasPlanFeatures && $entity->status()) {
      return $entity;
    }

    $this->logger->notice('PlanResolver: No features config for vertical="@v" tier="@t".', [
      '@v' => $vertical,
      '@t' => $tier,
    ]);

    return NULL;
  }

  /**
   * Checks a specific numeric limit for a vertical+tier.
   *
   * @param string $vertical
   *   Machine name of the vertical.
   * @param string $tier
   *   Canonical tier key.
   * @param string $limitKey
   *   Key of the limit (e.g. 'max_pages', 'storage_gb', 'ai_queries').
   * @param int $default
   *   Default value if no config or key found.
   *
   * @return int
   *   The limit value (-1 for unlimited, 0 for disabled, >0 for max count).
   */
  public function checkLimit(string $vertical, string $tier, string $limitKey, int $default = 0): int {
    $features = $this->getFeatures($vertical, $tier);
    if ($features === NULL) {
      return $default;
    }

    return $features->getLimit($limitKey, $default);
  }

  /**
   * Checks if a feature is enabled for a vertical+tier.
   *
   * @param string $vertical
   *   Machine name of the vertical.
   * @param string $tier
   *   Canonical tier key.
   * @param string $featureKey
   *   Feature key to check (e.g. 'seo_advanced', 'ab_testing').
   *
   * @return bool
   *   TRUE if the feature is enabled.
   */
  public function hasFeature(string $vertical, string $tier, string $featureKey): bool {
    $features = $this->getFeatures($vertical, $tier);
    if ($features === NULL) {
      return FALSE;
    }

    return $features->hasFeature($featureKey);
  }

  /**
   * Resolves tier key from a Stripe Price ID.
   *
   * Searches all SaasPlanTier entities for matching stripe_price_monthly
   * or stripe_price_yearly fields.
   *
   * @param string $stripePriceId
   *   The Stripe Price ID (e.g. 'price_1abc...').
   *
   * @return string|null
   *   Canonical tier key, or NULL if no match found.
   */
  public function resolveFromStripePriceId(string $stripePriceId): ?string {
    if ($stripePriceId === '') {
      return NULL;
    }

    $tiers = $this->entityTypeManager->getStorage('saas_plan_tier')->loadMultiple();
    /** @var \Drupal\ecosistema_jaraba_core\Entity\SaasPlanTierInterface $tier */
    foreach ($tiers as $tier) {
      if ($tier->getStripePriceMonthly() === $stripePriceId || $tier->getStripePriceYearly() === $stripePriceId) {
        return $tier->getTierKey();
      }
    }

    return NULL;
  }

  /**
   * Returns plan capabilities as a flat array, compatible with QuotaManagerService.
   *
   * Merges features (as booleans) and limits into a single array.
   * This provides a drop-in replacement for the hardcoded $capabilities array.
   *
   * @param string $vertical
   *   Machine name of the vertical.
   * @param string $tier
   *   Canonical tier key.
   *
   * @return array
   *   Merged capabilities array, e.g.:
   *   ['max_pages' => 25, 'seo_advanced' => TRUE, 'analytics' => TRUE, ...]
   */
  public function getPlanCapabilities(string $vertical, string $tier): array {
    $features = $this->getFeatures($vertical, $tier);
    if ($features === NULL) {
      return [];
    }

    $capabilities = $features->getLimits();

    // Add boolean features.
    foreach ($features->getFeatures() as $featureKey) {
      $capabilities[$featureKey] = TRUE;
    }

    return $capabilities;
  }

  /**
   * Builds the alias map lazily from all SaasPlanTier entities.
   *
   * @return array
   *   Map of lowercased alias → canonical tier key.
   */
  protected function getAliasMap(): array {
    if ($this->aliasMap !== NULL) {
      return $this->aliasMap;
    }

    $this->aliasMap = [];
    $tiers = $this->entityTypeManager->getStorage('saas_plan_tier')->loadMultiple();
    /** @var \Drupal\ecosistema_jaraba_core\Entity\SaasPlanTierInterface $tier */
    foreach ($tiers as $tier) {
      $tierKey = $tier->getTierKey();
      foreach ($tier->getAliases() as $alias) {
        $this->aliasMap[mb_strtolower(trim($alias))] = $tierKey;
      }
    }

    return $this->aliasMap;
  }

  /**
   * Invalidates the cached alias map.
   *
   * Call this when SaasPlanTier entities are created, updated, or deleted.
   */
  public function resetAliasCache(): void {
    $this->aliasMap = NULL;
  }

}

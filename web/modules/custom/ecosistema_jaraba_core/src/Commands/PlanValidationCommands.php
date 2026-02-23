<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drush\Commands\DrushCommands;

/**
 * Drush commands for validating SaaS plan configuration completeness.
 *
 * Verifies that all expected SaasPlanTier and SaasPlanFeatures
 * ConfigEntities exist and are correctly configured.
 */
class PlanValidationCommands extends DrushCommands {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
  ) {
    parent::__construct();
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Validates completeness of SaaS plan configuration.
   *
   * Checks that:
   * - All expected tiers exist (starter, professional, enterprise)
   * - Default features exist for each tier
   * - Vertical-specific features are properly configured
   * - Stripe Price IDs are not empty (warning)
   * - Limits have reasonable values
   *
   * @command jaraba:validate-plans
   * @aliases jvp
   * @usage jaraba:validate-plans
   *   Validates all plan tier and feature configurations.
   */
  public function validatePlans(): void {
    $errors = [];
    $warnings = [];
    $info = [];

    // 1. Check tiers.
    $tierStorage = $this->entityTypeManager->getStorage('saas_plan_tier');
    $tiers = $tierStorage->loadMultiple();
    $expectedTiers = ['starter', 'professional', 'enterprise'];
    $foundTierKeys = [];

    foreach ($tiers as $tier) {
      /** @var \Drupal\ecosistema_jaraba_core\Entity\SaasPlanTierInterface $tier */
      $tierKey = $tier->getTierKey();
      $foundTierKeys[] = $tierKey;
      $info[] = "Tier '{$tier->id()}': key={$tierKey}, aliases=" . implode(',', $tier->getAliases());

      if ($tier->getStripePriceMonthly() === '' || str_starts_with($tier->getStripePriceMonthly(), 'price_PLACEHOLDER')) {
        $warnings[] = "Tier '{$tier->id()}' has no real Stripe monthly price ID.";
      }
      if ($tier->getStripePriceYearly() === '' || str_starts_with($tier->getStripePriceYearly(), 'price_PLACEHOLDER')) {
        $warnings[] = "Tier '{$tier->id()}' has no real Stripe yearly price ID.";
      }
    }

    foreach ($expectedTiers as $expected) {
      if (!in_array($expected, $foundTierKeys, TRUE)) {
        $errors[] = "Missing expected tier: {$expected}";
      }
    }

    // 2. Check default features for each tier.
    $featuresStorage = $this->entityTypeManager->getStorage('saas_plan_features');
    foreach ($expectedTiers as $tier) {
      $defaultId = '_default_' . $tier;
      $entity = $featuresStorage->load($defaultId);
      if (!$entity) {
        $errors[] = "Missing default features config: {$defaultId}";
      }
      else {
        /** @var \Drupal\ecosistema_jaraba_core\Entity\SaasPlanFeatures $entity */
        $limitsCount = count($entity->getLimits());
        $featuresCount = count($entity->getFeatures());
        $info[] = "Default '{$defaultId}': {$featuresCount} features, {$limitsCount} limits";
      }
    }

    // 3. Check all vertical-specific features.
    $allFeatures = $featuresStorage->loadMultiple();
    $verticals = [];
    foreach ($allFeatures as $entity) {
      /** @var \Drupal\ecosistema_jaraba_core\Entity\SaasPlanFeatures $entity */
      $vertical = $entity->getVertical();
      $tier = $entity->getTier();

      if ($vertical !== '_default') {
        $verticals[$vertical][] = $tier;
      }

      if (!$entity->status()) {
        $warnings[] = "Features config '{$entity->id()}' is disabled.";
      }

      // Validate limits are integers.
      foreach ($entity->getLimits() as $key => $value) {
        if (!is_numeric($value)) {
          $errors[] = "Features '{$entity->id()}' has non-numeric limit '{$key}': {$value}";
        }
      }
    }

    foreach ($verticals as $vertical => $tiers) {
      $info[] = "Vertical '{$vertical}': " . count($tiers) . " tier(s) configured (" . implode(', ', $tiers) . ")";
      foreach ($expectedTiers as $expected) {
        if (!in_array($expected, $tiers, TRUE)) {
          $warnings[] = "Vertical '{$vertical}' is missing tier '{$expected}'.";
        }
      }
    }

    // 4. Output results.
    $this->io()->title('SaaS Plan Configuration Validation');

    if (!empty($info)) {
      $this->io()->section('Configuration Summary');
      foreach ($info as $msg) {
        $this->io()->text("  {$msg}");
      }
    }

    if (!empty($warnings)) {
      $this->io()->section('Warnings');
      foreach ($warnings as $msg) {
        $this->io()->warning($msg);
      }
    }

    if (!empty($errors)) {
      $this->io()->section('Errors');
      foreach ($errors as $msg) {
        $this->io()->error($msg);
      }
      $this->logger()->error('Plan validation failed with ' . count($errors) . ' error(s).');
    }
    else {
      $this->io()->success('All plan configurations are valid. ' . count($allFeatures) . ' feature configs, ' . count($tiers) . ' tiers.');
    }
  }

}

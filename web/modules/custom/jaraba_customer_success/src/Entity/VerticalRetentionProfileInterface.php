<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Interface for Vertical Retention Profile entities.
 */
interface VerticalRetentionProfileInterface extends ContentEntityInterface, EntityChangedInterface {

  public const STATUS_ACTIVE = 'active';
  public const STATUS_INACTIVE = 'inactive';

  public const VERTICAL_AGRO = 'agroconecta';
  public const VERTICAL_COMERCIO = 'comercioconecta';
  public const VERTICAL_SERVICIOS = 'serviciosconecta';
  public const VERTICAL_EMPLEABILIDAD = 'empleabilidad';
  public const VERTICAL_EMPRENDIMIENTO = 'emprendimiento';

  /**
   * Gets the vertical machine ID.
   */
  public function getVerticalId(): string;

  /**
   * Gets the human-readable label.
   */
  public function getLabel(): string;

  /**
   * Gets the seasonality calendar (12-month array).
   *
   * @return array<int, array{month: int, risk_level: string, label: string, adjustment: float}>
   */
  public function getSeasonalityCalendar(): array;

  /**
   * Gets the seasonal adjustment factor for a given month.
   *
   * @param int $month
   *   Month number (1-12).
   *
   * @return float
   *   Adjustment factor (-0.30 to +0.30).
   */
  public function getSeasonalAdjustment(int $month): float;

  /**
   * Gets the churn risk signals configuration.
   *
   * @return array<int, array{signal_id: string, metric: string, operator: string, threshold: mixed, lookback_days: int, weight: float, description: string}>
   */
  public function getChurnRiskSignals(): array;

  /**
   * Gets the health score weights for this vertical.
   *
   * @return array{engagement: int, adoption: int, satisfaction: int, support: int, growth: int}
   */
  public function getHealthScoreWeights(): array;

  /**
   * Gets the list of critical features for this vertical.
   *
   * @return string[]
   */
  public function getCriticalFeatures(): array;

  /**
   * Gets the re-engagement triggers.
   */
  public function getReengagementTriggers(): array;

  /**
   * Gets the upsell signals.
   */
  public function getUpsellSignals(): array;

  /**
   * Gets the seasonal offers.
   */
  public function getSeasonalOffers(): array;

  /**
   * Gets the expected usage pattern per month.
   *
   * @return array<int, string>
   *   Map of month (1-12) => expected usage level (low/medium/high).
   */
  public function getExpectedUsagePattern(): array;

  /**
   * Gets maximum inactivity days before churn classification.
   */
  public function getMaxInactivityDays(): int;

  /**
   * Gets playbook overrides (trigger_type => playbook_id).
   */
  public function getPlaybookOverrides(): array;

  /**
   * Checks if the profile is active.
   */
  public function isActive(): bool;

}

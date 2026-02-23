<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface for Seasonal Churn Prediction entities.
 *
 * Append-only: predictions are never edited or deleted once created.
 */
interface SeasonalChurnPredictionInterface extends ContentEntityInterface {

  public const URGENCY_NONE = 'none';
  public const URGENCY_LOW = 'low';
  public const URGENCY_MEDIUM = 'medium';
  public const URGENCY_HIGH = 'high';
  public const URGENCY_CRITICAL = 'critical';

  /**
   * Gets the tenant ID.
   */
  public function getTenantId(): string;

  /**
   * Gets the vertical ID at time of prediction.
   */
  public function getVerticalId(): string;

  /**
   * Gets the prediction month (YYYY-MM).
   */
  public function getPredictionMonth(): string;

  /**
   * Gets the base churn probability (0.00 - 1.00).
   */
  public function getBaseProbability(): float;

  /**
   * Gets the seasonal adjustment factor.
   */
  public function getSeasonalAdjustment(): float;

  /**
   * Gets the final adjusted probability.
   */
  public function getAdjustedProbability(): float;

  /**
   * Gets the seasonal context data.
   */
  public function getSeasonalContext(): array;

  /**
   * Gets the intervention urgency level.
   */
  public function getInterventionUrgency(): string;

}

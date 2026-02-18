<?php

declare(strict_types=1);

namespace Drupal\jaraba_security_compliance\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Interface for the RiskAssessment entity (ISO 27001).
 *
 * Defines accessor methods for the ISO 27001 risk assessment register,
 * including asset, threat, vulnerability, likelihood/impact scoring,
 * risk level classification, and treatment strategy.
 */
interface RiskAssessmentInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the asset name being assessed.
   *
   * @return string
   *   The asset name.
   */
  public function getAsset(): string;

  /**
   * Gets the identified threat.
   *
   * @return string
   *   The threat description.
   */
  public function getThreat(): string;

  /**
   * Gets the identified vulnerability.
   *
   * @return string
   *   The vulnerability description.
   */
  public function getVulnerability(): string;

  /**
   * Gets the likelihood score (1-5).
   *
   * @return int
   *   The likelihood score.
   */
  public function getLikelihood(): int;

  /**
   * Gets the impact score (1-5).
   *
   * @return int
   *   The impact score.
   */
  public function getImpact(): int;

  /**
   * Gets the computed risk score (likelihood * impact).
   *
   * @return int
   *   The risk score (1-25).
   */
  public function getRiskScore(): int;

  /**
   * Gets the risk level classification.
   *
   * @return string
   *   The risk level: low, medium, high, or critical.
   */
  public function getRiskLevel(): string;

  /**
   * Gets the risk treatment strategy.
   *
   * @return string
   *   The treatment: accept, mitigate, transfer, or avoid.
   */
  public function getTreatment(): string;

  /**
   * Gets the residual risk score after treatment.
   *
   * @return int
   *   The residual risk score.
   */
  public function getResidualRisk(): int;

  /**
   * Gets the risk status.
   *
   * @return string
   *   The status: open, mitigating, accepted, or closed.
   */
  public function getStatus(): string;

  /**
   * Gets the tenant ID.
   *
   * @return int|null
   *   The tenant ID, or NULL if no tenant is associated.
   */
  public function getTenantId(): ?int;

}

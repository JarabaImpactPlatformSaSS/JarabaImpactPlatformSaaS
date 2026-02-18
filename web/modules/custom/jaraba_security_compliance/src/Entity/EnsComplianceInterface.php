<?php

declare(strict_types=1);

namespace Drupal\jaraba_security_compliance\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Interface for the EnsCompliance entity.
 *
 * Defines accessor methods for ENS (Esquema Nacional de Seguridad)
 * compliance measure tracking, including measure identification,
 * category, required level, current implementation status, and
 * evidence documentation.
 */
interface EnsComplianceInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the ENS measure identifier (e.g. "org.1", "op.acc.5").
   *
   * @return string
   *   The measure identifier.
   */
  public function getMeasureId(): string;

  /**
   * Gets the measure category.
   *
   * @return string
   *   The category: organizational, operational, or protection.
   */
  public function getCategory(): string;

  /**
   * Gets the measure name.
   *
   * @return string
   *   The descriptive measure name.
   */
  public function getMeasureName(): string;

  /**
   * Gets the required ENS level for this measure.
   *
   * @return string
   *   The required level: basic, medium, or high.
   */
  public function getRequiredLevel(): string;

  /**
   * Gets the current implementation status.
   *
   * @return string
   *   The status: implemented, partial, not_implemented, or not_applicable.
   */
  public function getCurrentStatus(): string;

  /**
   * Gets the evidence type.
   *
   * @return string
   *   The evidence type: automated, manual, or hybrid.
   */
  public function getEvidenceType(): string;

  /**
   * Gets the responsible person or team.
   *
   * @return string
   *   The responsible party name.
   */
  public function getResponsible(): string;

  /**
   * Gets the tenant ID.
   *
   * @return int|null
   *   The tenant ID, or NULL if no tenant is associated.
   */
  public function getTenantId(): ?int;

}

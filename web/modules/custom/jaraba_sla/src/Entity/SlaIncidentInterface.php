<?php

declare(strict_types=1);

namespace Drupal\jaraba_sla\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Interface for SLA Incident entities.
 */
interface SlaIncidentInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the incident component.
   *
   * @return string
   *   The component identifier.
   */
  public function getComponent(): string;

  /**
   * Gets the severity level.
   *
   * @return string
   *   The severity (sev1, sev2, sev3, sev4).
   */
  public function getSeverity(): string;

  /**
   * Gets the incident status.
   *
   * @return string
   *   The status (investigating, identified, monitoring, resolved, postmortem).
   */
  public function getStatus(): string;

  /**
   * Gets the incident duration in minutes.
   *
   * @return float|null
   *   Duration in minutes, or NULL if not yet resolved.
   */
  public function getDurationMinutes(): ?float;

  /**
   * Gets the tenant ID.
   *
   * @return int|null
   *   The tenant entity ID.
   */
  public function getTenantId(): ?int;

}

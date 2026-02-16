<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_export\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Interface para la entidad TenantExportRecord.
 */
interface TenantExportRecordInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Comprueba si la exportación está completada.
   */
  public function isCompleted(): bool;

  /**
   * Comprueba si la exportación ha expirado.
   */
  public function isExpired(): bool;

  /**
   * Comprueba si la exportación se puede descargar.
   */
  public function isDownloadable(): bool;

  /**
   * Obtiene el progreso actual (0-100).
   */
  public function getProgress(): int;

  /**
   * Obtiene el estado legible.
   */
  public function getStatusLabel(): string;

}

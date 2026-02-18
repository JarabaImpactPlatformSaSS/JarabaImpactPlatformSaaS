<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Interfaz para la entidad AgroShipment.
 *
 * Define los métodos necesarios para gestionar envíos físicos asociados
 * a pedidos del marketplace AgroConecta.
 *
 * F5 — Doc 51 §2.1.
 */
interface AgroShipmentInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Obtiene el número de envío (SHP-YYYY-NNNNN).
   */
  public function getShipmentNumber(): string;

  /**
   * Obtiene el ID del transportista (carrier).
   */
  public function getCarrierId(): string;

  /**
   * Obtiene el número de seguimiento (tracking number).
   */
  public function getTrackingNumber(): ?string;

  /**
   * Obtiene el estado actual del envío.
   */
  public function getState(): string;

  /**
   * Obtiene el coste real del envío.
   */
  public function getShippingCost(): float;

  /**
   * Indica si el envío requiere cadena de frío.
   */
  public function isRefrigerated(): bool;

  /**
   * Obtiene la fecha de creación.
   */
  public function getCreatedTime(): int;

}

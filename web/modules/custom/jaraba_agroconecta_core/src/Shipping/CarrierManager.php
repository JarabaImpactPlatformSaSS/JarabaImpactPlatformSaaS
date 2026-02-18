<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Shipping;

/**
 * Manager for carrier adapters.
 *
 * This service registry collects all enabled carriers and provides
 * a unified access point for shipping operations.
 */
class CarrierManager {

  /**
   * List of registered carrier adapters.
   *
   * @var \Drupal\jaraba_agroconecta_core\Shipping\CarrierAdapterInterface[]
   */
  protected array $carriers = [];

  /**
   * Registers a carrier adapter.
   */
  public function addCarrier(CarrierAdapterInterface $carrier): void {
    $this->carriers[$carrier->getCarrierId()] = $carrier;
  }

  /**
   * Gets a carrier adapter by ID.
   */
  public function getCarrier(string $carrierId): ?CarrierAdapterInterface {
    return $this->carriers[$carrierId] ?? NULL;
  }

  /**
   * Gets all available carriers.
   */
  public function getAvailableCarriers(): array {
    return $this->carriers;
  }

}

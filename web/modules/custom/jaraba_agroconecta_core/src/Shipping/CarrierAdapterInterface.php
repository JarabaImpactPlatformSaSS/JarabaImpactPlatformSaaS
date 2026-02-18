<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Shipping;

/**
 * Interface for AgroConecta carrier adapters.
 *
 * All carrier integrations must implement this interface to ensure a 
 * unified experience for label generation and tracking.
 *
 * F5 — Doc 51 §5.1.
 */
interface CarrierAdapterInterface {

  /**
   * Returns the unique ID of the carrier (e.g., mrw, seur).
   */
  public function getCarrierId(): string;

  /**
   * Returns the human-readable label of the carrier.
   */
  public function getLabel(): string;

  /**
   * Creates a shipment request to the carrier API.
   *
   * @param array $data
   *   Shipment data including weight, dimensions, origin, and destination.
   *
   * @return array
   *   Result with tracking_number and label_url (PDF).
   */
  public function createShipment(array $data): array;

  /**
   * Fetches the latest tracking status for a tracking number.
   *
   * @param string $trackingNumber
   *   The carrier's tracking number.
   *
   * @return array
   *   Normalized tracking data.
   */
  public function getTrackingStatus(string $trackingNumber): array;

  /**
   * Cancels a shipment if it hasn't been picked up yet.
   *
   * @param string $trackingNumber
   *   The carrier's tracking number.
   *
   * @return bool
   *   TRUE if cancelled successfully.
   */
  public function cancelShipment(string $trackingNumber): bool;

}

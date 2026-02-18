<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Shipping;

use Drupal\Core\Logger\LoggerChannelInterface;
use GuzzleHttp\ClientInterface;

/**
 * Base class for carrier adapters.
 */
abstract class BaseCarrierAdapter implements CarrierAdapterInterface {

  public function __construct(
    protected ClientInterface $httpClient,
    protected LoggerChannelInterface $logger,
    protected array $config = [],
  ) {}

  /**
   * {@inheritdoc}
   */
  abstract public function getCarrierId(): string;

  /**
   * {@inheritdoc}
   */
  abstract public function getLabel(): string;

  /**
   * Helper to normalize states to internal system states.
   */
  protected function normalizeState(string $carrierState): string {
    // Basic mapping, should be overridden by child classes.
    return 'in_transit';
  }

}

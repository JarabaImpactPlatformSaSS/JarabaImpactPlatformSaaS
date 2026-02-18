<?php

declare(strict_types=1);

namespace Drupal\jaraba_connector_sdk\Sdk;

/**
 * Abstract base class for third-party connectors.
 *
 * Provides sensible defaults for every ConnectorInterface method except
 * getManifest(), which each concrete connector MUST implement to describe
 * its identity and capabilities.
 *
 * Usage:
 * @code
 * class MyConnector extends BaseConnector {
 *   public function getManifest(): array {
 *     return [
 *       'machine_name' => 'my_connector',
 *       'display_name' => 'My Connector',
 *       'version'      => '1.0.0',
 *       'category'     => 'analytics',
 *     ];
 *   }
 * }
 * @endcode
 */
abstract class BaseConnector implements ConnectorInterface {

  /**
   * {@inheritdoc}
   */
  public function install(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function uninstall(): void {
    // Default: nothing to clean up.
  }

  /**
   * {@inheritdoc}
   */
  public function configure(array $settings): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function sync(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function handleWebhook(array $payload): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus(): string {
    return 'active';
  }

  /**
   * {@inheritdoc}
   */
  public function test(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   *
   * Subclasses MUST override this method.
   */
  abstract public function getManifest(): array;

}

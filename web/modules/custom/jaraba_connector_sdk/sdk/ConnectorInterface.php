<?php

declare(strict_types=1);

namespace Drupal\jaraba_connector_sdk\Sdk;

/**
 * Contract that all third-party connectors must implement.
 *
 * This interface defines the lifecycle and operational methods that the
 * platform invokes when managing a connector installation. Developers
 * extending the platform via the Connector SDK MUST implement this
 * interface (or extend BaseConnector which provides sensible defaults).
 */
interface ConnectorInterface {

  /**
   * Installs the connector for the current tenant.
   *
   * Called once when a tenant adds the connector from the marketplace.
   * Use this to provision external resources, create API tokens, etc.
   *
   * @return bool
   *   TRUE if installation succeeded.
   */
  public function install(): bool;

  /**
   * Uninstalls the connector for the current tenant.
   *
   * Called when a tenant removes the connector. Use this to revoke
   * tokens, clean up external resources, etc.
   */
  public function uninstall(): void;

  /**
   * Configures the connector with tenant-specific settings.
   *
   * @param array $settings
   *   Key-value settings (e.g. API keys, endpoint URLs).
   *
   * @return bool
   *   TRUE if configuration was accepted and validated.
   */
  public function configure(array $settings): bool;

  /**
   * Performs a data synchronisation cycle.
   *
   * @return array
   *   Sync results with keys like 'synced', 'errors', 'timestamp'.
   */
  public function sync(): array;

  /**
   * Handles an incoming webhook payload from the external service.
   *
   * @param array $payload
   *   Decoded webhook payload.
   *
   * @return array
   *   Processing result with at least a 'status' key.
   */
  public function handleWebhook(array $payload): array;

  /**
   * Returns the current operational status of the connector.
   *
   * @return string
   *   One of: 'active', 'inactive', 'error', 'pending_config'.
   */
  public function getStatus(): string;

  /**
   * Runs a self-test to verify the connector is working correctly.
   *
   * @return bool
   *   TRUE if the connector passes its self-test.
   */
  public function test(): bool;

  /**
   * Returns the connector manifest describing its capabilities.
   *
   * @return array
   *   Manifest with keys: machine_name, display_name, version,
   *   category, capabilities, config_fields, permissions.
   */
  public function getManifest(): array;

}

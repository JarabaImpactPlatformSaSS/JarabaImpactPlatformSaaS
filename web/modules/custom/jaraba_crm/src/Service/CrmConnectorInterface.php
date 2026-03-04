<?php

declare(strict_types=1);

namespace Drupal\jaraba_crm\Service;

/**
 * Interface for external CRM connector plugins.
 *
 * GAP-CRM: Defines the contract for syncing data between
 * the Jaraba platform and external CRMs (HubSpot, Salesforce, etc.).
 *
 * Each implementation handles one external CRM system.
 * Connectors are registered as tagged services and resolved
 * by CrmSyncOrchestratorService based on tenant configuration.
 */
interface CrmConnectorInterface {

  /**
   * Gets the machine name of this connector.
   *
   * @return string
   *   Connector ID (e.g., 'hubspot', 'salesforce').
   */
  public function getId(): string;

  /**
   * Gets the human-readable label.
   *
   * @return string
   *   Display name (e.g., 'HubSpot', 'Salesforce').
   */
  public function getLabel(): string;

  /**
   * Syncs a contact/user to the external CRM.
   *
   * @param int $userId
   *   Drupal user ID.
   *
   * @return bool
   *   TRUE if sync succeeded.
   */
  public function syncContact(int $userId): bool;

  /**
   * Syncs a deal/opportunity from a subscription.
   *
   * @param int $subscriptionId
   *   Subscription or opportunity entity ID.
   *
   * @return bool
   *   TRUE if sync succeeded.
   */
  public function syncDeal(int $subscriptionId): bool;

  /**
   * Gets the connection status for the current tenant.
   *
   * @return array
   *   Status array with keys:
   *   - connected: bool
   *   - last_sync: string|null (ISO 8601 timestamp)
   *   - error: string|null (last error message)
   *   - stats: array (contacts_synced, deals_synced counts)
   */
  public function getStatus(): array;

  /**
   * Tests the connection with provided credentials.
   *
   * @param array $credentials
   *   Connector-specific credentials.
   *
   * @return bool
   *   TRUE if connection test passes.
   */
  public function testConnection(array $credentials): bool;

}

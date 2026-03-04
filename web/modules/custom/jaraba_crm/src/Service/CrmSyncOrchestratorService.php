<?php

declare(strict_types=1);

namespace Drupal\jaraba_crm\Service;

use Psr\Log\LoggerInterface;

/**
 * Orchestrates CRM sync across all registered connectors.
 *
 * GAP-CRM: Resolves the active connector for the current tenant
 * and delegates sync operations.
 *
 * TENANT-001: Each tenant configures their own CRM connector.
 */
class CrmSyncOrchestratorService {

  /**
   * Registered connectors keyed by ID.
   *
   * @var array<string, CrmConnectorInterface>
   */
  protected array $connectors = [];

  /**
   * Constructor.
   */
  public function __construct(
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Registers a CRM connector.
   *
   * Called by the service container via tagged service collector.
   *
   * @param CrmConnectorInterface $connector
   *   The connector to register.
   */
  public function addConnector(CrmConnectorInterface $connector): void {
    $this->connectors[$connector->getId()] = $connector;
  }

  /**
   * Gets all registered connectors.
   *
   * @return array<string, CrmConnectorInterface>
   *   Connectors keyed by ID.
   */
  public function getConnectors(): array {
    return $this->connectors;
  }

  /**
   * Gets a specific connector by ID.
   *
   * @param string $connectorId
   *   Connector machine name (e.g., 'hubspot', 'salesforce').
   *
   * @return CrmConnectorInterface|null
   *   The connector, or NULL if not found.
   */
  public function getConnector(string $connectorId): ?CrmConnectorInterface {
    return $this->connectors[$connectorId] ?? NULL;
  }

  /**
   * Syncs a contact to the active CRM connector for the tenant.
   *
   * @param int $userId
   *   Drupal user ID.
   * @param string $connectorId
   *   Which connector to use.
   *
   * @return bool
   *   TRUE if sync succeeded.
   */
  public function syncContact(int $userId, string $connectorId): bool {
    $connector = $this->getConnector($connectorId);
    if (!$connector) {
      $this->logger->warning('CRM connector @id not found', ['@id' => $connectorId]);
      return FALSE;
    }

    return $connector->syncContact($userId);
  }

  /**
   * Syncs a deal/opportunity to the active CRM connector.
   *
   * @param int $opportunityId
   *   Opportunity entity ID.
   * @param string $connectorId
   *   Which connector to use.
   *
   * @return bool
   *   TRUE if sync succeeded.
   */
  public function syncDeal(int $opportunityId, string $connectorId): bool {
    $connector = $this->getConnector($connectorId);
    if (!$connector) {
      return FALSE;
    }

    return $connector->syncDeal($opportunityId);
  }

  /**
   * Gets status from all registered connectors.
   *
   * @return array<string, array>
   *   Status arrays keyed by connector ID.
   */
  public function getAllStatuses(): array {
    $statuses = [];
    foreach ($this->connectors as $id => $connector) {
      $statuses[$id] = array_merge(
        ['id' => $id, 'label' => $connector->getLabel()],
        $connector->getStatus()
      );
    }
    return $statuses;
  }

}

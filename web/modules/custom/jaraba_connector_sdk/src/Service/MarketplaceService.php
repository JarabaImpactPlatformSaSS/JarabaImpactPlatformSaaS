<?php

declare(strict_types=1);

namespace Drupal\jaraba_connector_sdk\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_integrations\Entity\Connector;
use Drupal\jaraba_integrations\Service\ConnectorInstallerService;
use Drupal\jaraba_integrations\Service\ConnectorRegistryService;

/**
 * Marketplace service for the Connector SDK.
 *
 * Provides high-level operations for the certified-connector marketplace:
 * listing, detail, rating, install/uninstall, and configuration. Delegates
 * low-level entity operations to ConnectorRegistryService and
 * ConnectorInstallerService from jaraba_integrations.
 */
class MarketplaceService {

  /**
   * Constructs the MarketplaceService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\ecosistema_jaraba_core\Service\TenantContextService $tenantContext
   *   The tenant context service.
   * @param \Drupal\jaraba_integrations\Service\ConnectorRegistryService $connectorRegistry
   *   The connector registry service.
   * @param \Drupal\jaraba_integrations\Service\ConnectorInstallerService $connectorInstaller
   *   The connector installer service.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly TenantContextService $tenantContext,
    protected readonly ConnectorRegistryService $connectorRegistry,
    protected readonly ConnectorInstallerService $connectorInstaller,
  ) {}

  /**
   * Lists certified connectors with pagination, category filter, and search.
   *
   * @param int $page
   *   Zero-based page number.
   * @param int $limit
   *   Items per page.
   * @param string|null $category
   *   Optional category filter.
   * @param string|null $search
   *   Optional search query.
   *
   * @return array
   *   Array with keys:
   *   - items: array of serialised connector data
   *   - total: int total matching connectors
   *   - page: int current page
   */
  public function listCertified(int $page = 0, int $limit = 20, ?string $category = NULL, ?string $search = NULL): array {
    $storage = $this->entityTypeManager->getStorage('connector');

    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('publish_status', ConnectorCertifierService::STATUS_CERTIFIED)
      ->sort('name', 'ASC');

    if ($category !== NULL && $category !== '') {
      $query->condition('category', $category);
    }

    // Count total before pagination.
    $countQuery = clone $query;
    $total = (int) $countQuery->count()->execute();

    // Apply pagination.
    $query->range($page * $limit, $limit);
    $ids = $query->execute();

    $connectors = $ids ? $storage->loadMultiple($ids) : [];

    // Apply search filter in-memory if needed (name/description text search).
    if ($search !== NULL && $search !== '') {
      $searchLower = mb_strtolower($search);
      $connectors = array_filter($connectors, function (Connector $c) use ($searchLower) {
        $name = mb_strtolower($c->getName());
        $desc = mb_strtolower($c->get('description')->value ?? '');
        return str_contains($name, $searchLower) || str_contains($desc, $searchLower);
      });
      $total = count($connectors);
    }

    $items = array_map(fn(Connector $c) => $this->serializeConnector($c), $connectors);

    return [
      'items' => array_values($items),
      'total' => $total,
      'page' => $page,
    ];
  }

  /**
   * Returns full details for a single connector.
   *
   * @param int $connectorId
   *   The connector entity ID.
   *
   * @return array|null
   *   Connector detail array, or NULL if not found.
   */
  public function getDetail(int $connectorId): ?array {
    $connector = $this->loadConnector($connectorId);
    if (!$connector) {
      return NULL;
    }

    $data = $this->serializeConnector($connector);
    $data['config_schema'] = $connector->getConfigSchema();
    $data['docs_url'] = $connector->get('docs_url')->value ?? '';
    $data['supported_events'] = json_decode($connector->get('supported_events')->value ?? '[]', TRUE) ?? [];

    return $data;
  }

  /**
   * Records a user rating for a connector.
   *
   * Stores rating in the connector entity's internal state. In a full
   * implementation this would use a dedicated rating entity; here we
   * update the denormalised aggregate on the connector itself.
   *
   * @param int $connectorId
   *   The connector entity ID.
   * @param int $userId
   *   The rating user's ID.
   * @param int $rating
   *   Rating value (1-5).
   * @param string|null $review
   *   Optional text review.
   *
   * @return bool
   *   TRUE if the rating was recorded.
   */
  public function rate(int $connectorId, int $userId, int $rating, ?string $review = NULL): bool {
    if ($rating < 1 || $rating > 5) {
      return FALSE;
    }

    $connector = $this->loadConnector($connectorId);
    if (!$connector) {
      return FALSE;
    }

    // Store rating data in the state system keyed by connector + user.
    $stateKey = 'jaraba_connector_sdk.rating.' . $connectorId;
    $state = \Drupal::state();
    $ratings = $state->get($stateKey, []);

    $ratings[$userId] = [
      'rating' => $rating,
      'review' => $review,
      'timestamp' => time(),
    ];

    $state->set($stateKey, $ratings);

    return TRUE;
  }

  /**
   * Installs a connector for the current tenant.
   *
   * Delegates to ConnectorInstallerService.
   *
   * @param int $connectorId
   *   The connector entity ID.
   *
   * @return array
   *   Installation result with keys: success, installation_id, status.
   */
  public function install(int $connectorId): array {
    $connector = $this->loadConnector($connectorId);
    if (!$connector) {
      return [
        'success' => FALSE,
        'error' => 'Connector not found.',
      ];
    }

    $tenantId = $this->getTenantId();
    if (!$tenantId) {
      return [
        'success' => FALSE,
        'error' => 'Tenant context required.',
      ];
    }

    $installation = $this->connectorInstaller->install($connector, $tenantId);
    if (!$installation) {
      return [
        'success' => FALSE,
        'error' => 'Connector already installed or installation failed.',
      ];
    }

    return [
      'success' => TRUE,
      'installation_id' => $installation->id(),
      'status' => $installation->getInstallationStatus(),
    ];
  }

  /**
   * Uninstalls a connector for the current tenant.
   *
   * Delegates to ConnectorInstallerService.
   *
   * @param int $connectorId
   *   The connector entity ID.
   *
   * @return bool
   *   TRUE if uninstalled successfully.
   */
  public function uninstall(int $connectorId): bool {
    $connector = $this->loadConnector($connectorId);
    if (!$connector) {
      return FALSE;
    }

    $tenantId = $this->getTenantId();
    if (!$tenantId) {
      return FALSE;
    }

    return $this->connectorInstaller->uninstall($connector, $tenantId);
  }

  /**
   * Configures an installed connector for the current tenant.
   *
   * @param int $connectorId
   *   The connector entity ID.
   * @param array $settings
   *   Configuration settings to apply.
   *
   * @return bool
   *   TRUE if configuration was applied.
   */
  public function configure(int $connectorId, array $settings): bool {
    $connector = $this->loadConnector($connectorId);
    if (!$connector) {
      return FALSE;
    }

    $tenantId = $this->getTenantId();
    if (!$tenantId) {
      return FALSE;
    }

    $installation = $this->connectorInstaller->getInstallation($connector, $tenantId);
    if (!$installation) {
      return FALSE;
    }

    $this->connectorInstaller->configure($installation, $settings);
    return TRUE;
  }

  /**
   * Gets the status of a connector for the current tenant.
   *
   * @param int $connectorId
   *   The connector entity ID.
   *
   * @return array
   *   Status array with keys: installed, status, connector_id.
   */
  public function getStatus(int $connectorId): array {
    $connector = $this->loadConnector($connectorId);
    if (!$connector) {
      return [
        'installed' => FALSE,
        'status' => 'not_found',
        'connector_id' => $connectorId,
      ];
    }

    $tenantId = $this->getTenantId();
    if (!$tenantId) {
      return [
        'installed' => FALSE,
        'status' => 'no_tenant',
        'connector_id' => $connectorId,
      ];
    }

    $installation = $this->connectorInstaller->getInstallation($connector, $tenantId);
    if (!$installation) {
      return [
        'installed' => FALSE,
        'status' => 'not_installed',
        'connector_id' => $connectorId,
      ];
    }

    return [
      'installed' => TRUE,
      'status' => $installation->getInstallationStatus(),
      'connector_id' => $connectorId,
      'installation_id' => $installation->id(),
    ];
  }

  /**
   * Serialises a Connector entity for API output.
   *
   * @param \Drupal\jaraba_integrations\Entity\Connector $connector
   *   The connector entity.
   *
   * @return array
   *   Serialised connector data.
   */
  protected function serializeConnector(Connector $connector): array {
    // Calculate average rating from state.
    $avgRating = $this->getAverageRating((int) $connector->id());

    return [
      'id' => (int) $connector->id(),
      'name' => $connector->getName(),
      'machine_name' => $connector->get('machine_name')->value ?? '',
      'description' => $connector->get('description')->value ?? '',
      'category' => $connector->getCategory(),
      'icon' => $connector->getIcon(),
      'logo_url' => $connector->get('logo_url')->value ?? '',
      'auth_type' => $connector->getAuthType(),
      'version' => $connector->get('version')->value ?? '1.0.0',
      'provider' => $connector->get('provider')->value ?? '',
      'install_count' => (int) ($connector->get('install_count')->value ?? 0),
      'rating' => $avgRating,
      'status' => $connector->getPublishStatus(),
    ];
  }

  /**
   * Calculates the average rating for a connector.
   *
   * @param int $connectorId
   *   The connector entity ID.
   *
   * @return float
   *   Average rating (0.0 if no ratings).
   */
  protected function getAverageRating(int $connectorId): float {
    $stateKey = 'jaraba_connector_sdk.rating.' . $connectorId;
    $ratings = \Drupal::state()->get($stateKey, []);

    if (empty($ratings)) {
      return 0.0;
    }

    $values = array_column($ratings, 'rating');
    return round(array_sum($values) / count($values), 2);
  }

  /**
   * Loads a Connector entity by ID.
   *
   * @param int $connectorId
   *   The connector entity ID.
   *
   * @return \Drupal\jaraba_integrations\Entity\Connector|null
   *   The connector entity, or NULL if not found.
   */
  protected function loadConnector(int $connectorId): ?Connector {
    $storage = $this->entityTypeManager->getStorage('connector');
    $entity = $storage->load($connectorId);
    return $entity instanceof Connector ? $entity : NULL;
  }

  /**
   * Obtains the current tenant ID.
   *
   * @return string|null
   *   The tenant group ID, or NULL if unavailable.
   */
  protected function getTenantId(): ?string {
    try {
      $tenant = $this->tenantContext->getCurrentTenant();
      if ($tenant) {
        $group = $tenant->getGroup();
        return $group ? (string) $group->id() : NULL;
      }
    }
    catch (\Exception $e) {
      // No tenant context available.
    }
    return NULL;
  }

}

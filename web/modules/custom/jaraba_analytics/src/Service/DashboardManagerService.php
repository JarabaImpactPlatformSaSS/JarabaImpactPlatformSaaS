<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de gestion de dashboards de analytics.
 *
 * PROPOSITO:
 * Proporciona operaciones CRUD y consultas para dashboards de analytics,
 * incluyendo obtencion por usuario, tenant y dashboard predeterminado.
 *
 * LOGICA:
 * - getDashboard: carga un dashboard por ID y devuelve datos serializados.
 * - getUserDashboards: obtiene dashboards de un usuario, opcionalmente
 *   filtrados por tenant, incluyendo dashboards compartidos.
 * - getDefaultDashboard: busca el dashboard predeterminado para un tenant.
 * - saveDashboardLayout: actualiza la configuracion de layout de un dashboard.
 */
class DashboardManagerService {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Constructor.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LoggerInterface $logger,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
  }

  /**
   * Gets a dashboard by ID.
   *
   * @param int $id
   *   The dashboard entity ID.
   *
   * @return array|null
   *   Serialized dashboard data, or NULL if not found.
   */
  public function getDashboard(int $id): ?array {
    try {
      $storage = $this->entityTypeManager->getStorage('analytics_dashboard');
      /** @var \Drupal\jaraba_analytics\Entity\AnalyticsDashboard|null $dashboard */
      $dashboard = $storage->load($id);

      if (!$dashboard) {
        return NULL;
      }

      return [
        'id' => (int) $dashboard->id(),
        'name' => $dashboard->getName(),
        'description' => $dashboard->getDescription(),
        'layout_config' => $dashboard->getLayoutConfig(),
        'is_default' => $dashboard->isDefault(),
        'is_shared' => $dashboard->isShared(),
        'owner_id' => $dashboard->getOwnerId(),
        'tenant_id' => $dashboard->getTenantId(),
        'dashboard_status' => $dashboard->getDashboardStatus(),
        'created' => (int) $dashboard->get('created')->value,
        'changed' => (int) $dashboard->get('changed')->value,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to load dashboard @id: @message', [
        '@id' => $id,
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Gets dashboards for a specific user.
   *
   * Returns dashboards owned by the user plus shared dashboards
   * within the specified tenant.
   *
   * @param int $userId
   *   The user ID.
   * @param int|null $tenantId
   *   Optional tenant ID to filter by.
   *
   * @return array
   *   Array of serialized dashboard data.
   */
  public function getUserDashboards(int $userId, ?int $tenantId = NULL): array {
    try {
      $storage = $this->entityTypeManager->getStorage('analytics_dashboard');

      // Get dashboards owned by user.
      $ownedQuery = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('owner_id', $userId)
        ->condition('dashboard_status', 'active')
        ->sort('created', 'DESC');

      if ($tenantId !== NULL) {
        $ownedQuery->condition('tenant_id', $tenantId);
      }

      $ownedIds = $ownedQuery->execute();

      // Get shared dashboards in the tenant.
      $sharedIds = [];
      if ($tenantId !== NULL) {
        $sharedQuery = $storage->getQuery()
          ->accessCheck(TRUE)
          ->condition('is_shared', TRUE)
          ->condition('dashboard_status', 'active')
          ->condition('tenant_id', $tenantId)
          ->sort('created', 'DESC');

        $sharedIds = $sharedQuery->execute();
      }

      $allIds = array_unique(array_merge(array_values($ownedIds), array_values($sharedIds)));
      $dashboards = $storage->loadMultiple($allIds);

      $results = [];
      /** @var \Drupal\jaraba_analytics\Entity\AnalyticsDashboard $dashboard */
      foreach ($dashboards as $dashboard) {
        $results[] = [
          'id' => (int) $dashboard->id(),
          'name' => $dashboard->getName(),
          'description' => $dashboard->getDescription(),
          'is_default' => $dashboard->isDefault(),
          'is_shared' => $dashboard->isShared(),
          'owner_id' => $dashboard->getOwnerId(),
          'dashboard_status' => $dashboard->getDashboardStatus(),
          'created' => (int) $dashboard->get('created')->value,
        ];
      }

      return $results;
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to load user dashboards for user @uid: @message', [
        '@uid' => $userId,
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Gets the default dashboard for a tenant.
   *
   * @param int|null $tenantId
   *   The tenant ID.
   *
   * @return array|null
   *   Serialized dashboard data, or NULL if no default exists.
   */
  public function getDefaultDashboard(?int $tenantId = NULL): ?array {
    try {
      $storage = $this->entityTypeManager->getStorage('analytics_dashboard');
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('is_default', TRUE)
        ->condition('dashboard_status', 'active')
        ->range(0, 1);

      if ($tenantId !== NULL) {
        $query->condition('tenant_id', $tenantId);
      }

      $ids = $query->execute();

      if (empty($ids)) {
        return NULL;
      }

      $id = (int) reset($ids);
      return $this->getDashboard($id);
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to load default dashboard: @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Saves a dashboard layout configuration.
   *
   * @param int $dashboardId
   *   The dashboard entity ID.
   * @param array $layout
   *   The layout configuration array.
   *
   * @return bool
   *   TRUE if saved successfully, FALSE otherwise.
   */
  public function saveDashboardLayout(int $dashboardId, array $layout): bool {
    try {
      $storage = $this->entityTypeManager->getStorage('analytics_dashboard');
      /** @var \Drupal\jaraba_analytics\Entity\AnalyticsDashboard|null $dashboard */
      $dashboard = $storage->load($dashboardId);

      if (!$dashboard) {
        $this->logger->warning('Dashboard @id not found for layout update.', [
          '@id' => $dashboardId,
        ]);
        return FALSE;
      }

      $dashboard->set('layout_config', json_encode($layout, JSON_UNESCAPED_UNICODE));
      $dashboard->save();

      $this->logger->info('Dashboard @id layout updated successfully.', [
        '@id' => $dashboardId,
      ]);
      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to save dashboard @id layout: @message', [
        '@id' => $dashboardId,
        '@message' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

}

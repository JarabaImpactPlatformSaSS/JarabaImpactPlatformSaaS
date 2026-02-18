<?php

declare(strict_types=1);

namespace Drupal\jaraba_integrations\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\jaraba_integrations\Service\ConnectorRegistryService;
use Drupal\jaraba_integrations\Service\ConnectorInstallerService;
use Drupal\jaraba_integrations\Service\ConnectorHealthCheckService;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;

/**
 * REST API controller para conectores.
 *
 * PROPÓSITO:
 * Endpoints JSON para integración programática:
 * - GET /api/v1/integrations/connectors — Listar conectores.
 * - GET /api/v1/integrations/connectors/{id} — Detalle.
 * - POST /api/v1/integrations/connectors/{id}/install — Instalar.
 * - POST /api/v1/integrations/connectors/{id}/uninstall — Desinstalar.
 * - GET /api/v1/integrations/connectors/{id}/health — Health check.
 */
class ConnectorApiController extends ControllerBase {

  public function __construct(
    protected ConnectorRegistryService $connectorRegistry,
    protected ConnectorInstallerService $connectorInstaller,
    protected ConnectorHealthCheckService $healthCheck,
    protected readonly TenantContextService $tenantContext, // AUDIT-CONS-N10: Proper DI for tenant context.
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_integrations.connector_registry'),
      $container->get('jaraba_integrations.connector_installer'),
      $container->get('jaraba_integrations.health_check'),
      $container->get('ecosistema_jaraba_core.tenant_context'), // AUDIT-CONS-N10: Proper DI for tenant context.
    );
  }

  /**
   * Listar conectores publicados.
   */
  public function listConnectors(Request $request): JsonResponse {
    $category = $request->query->get('category', '');
    $search = $request->query->get('q', '');

    if (!empty($search)) {
      $connectors = $this->connectorRegistry->searchConnectors($search);
    }
    elseif (!empty($category)) {
      $connectors = $this->connectorRegistry->getByCategory($category);
    }
    else {
      $connectors = $this->connectorRegistry->getPublishedConnectors();
    }

    $data = array_map(fn($c) => $this->serializeConnector($c), $connectors);

    return new JsonResponse(['success' => TRUE, 'data' => array_values($data), 'meta' => ['total' => count($data)]]);
  }

  /**
   * Obtener detalle de un conector.
   */
  public function getConnector(string $connector_id): JsonResponse {
    $storage = $this->entityTypeManager()->getStorage('connector');
    $connector = $storage->load($connector_id);

    if (!$connector) {
      return // AUDIT-CONS-N08: Standardized JSON envelope.
        new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Connector not found']], 404);
    }

    return new JsonResponse(['success' => TRUE, 'data' => $this->serializeConnector($connector),
    ]);
  }

  /**
   * Instalar un conector.
   */
  public function install(string $connector_id, Request $request): JsonResponse {
    $storage = $this->entityTypeManager()->getStorage('connector');
    $connector = $storage->load($connector_id);

    if (!$connector) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Connector not found']], 404);
    }

    $tenant_id = $this->getTenantId();
    if (!$tenant_id) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Tenant context required']], 400);
    }

    $installation = $this->connectorInstaller->install($connector, $tenant_id);
    if (!$installation) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Connector already installed']], 409);
    }

    return new JsonResponse([
      'data' => [
        'id' => $installation->id(),
        'connector_id' => $connector_id,
        'status' => $installation->getInstallationStatus(),
      ], 'meta' => ['timestamp' => time()]], 201);
  }

  /**
   * Desinstalar un conector.
   */
  public function uninstall(string $connector_id, Request $request): JsonResponse {
    $storage = $this->entityTypeManager()->getStorage('connector');
    $connector = $storage->load($connector_id);

    if (!$connector) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Connector not found']], 404);
    }

    $tenant_id = $this->getTenantId();
    if (!$tenant_id) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Tenant context required']], 400);
    }

    if ($this->connectorInstaller->uninstall($connector, $tenant_id)) {
      return new JsonResponse(['success' => TRUE, 'data' => ['message' => 'Connector uninstalled successfully'], 'meta' => ['timestamp' => time()]]);
    }

    return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Connector not installed']], 404);
  }

  /**
   * Health check de un conector.
   */
  public function healthCheck(string $connector_id): JsonResponse {
    $storage = $this->entityTypeManager()->getStorage('connector');
    $connector = $storage->load($connector_id);

    if (!$connector) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Connector not found']], 404);
    }

    $tenant_id = $this->getTenantId();
    if (!$tenant_id) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Tenant context required']], 400);
    }

    $installation = $this->connectorInstaller->getInstallation($connector, $tenant_id);
    if (!$installation) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Connector not installed']], 404);
    }

    $result = $this->healthCheck->checkInstallation($installation);

    return new JsonResponse(['success' => TRUE, 'data' => $result, 'meta' => ['timestamp' => time()]]);
  }

  /**
   * Serializa un conector para JSON.
   */
  protected function serializeConnector($connector): array {
    return [
      'id' => $connector->id(),
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
      'docs_url' => $connector->get('docs_url')->value ?? '',
    ];
  }

  /**
   * Obtiene el tenant_id del usuario actual.
   */
  protected function getTenantId(): ?string {
    try {
      $tenant_context = $this->tenantContext;
      $tenant = $tenant_context->getCurrentTenant();
      if ($tenant) {
        $group = $tenant->getGroup();
        return $group ? (string) $group->id() : NULL;
      }
    }
    catch (\Exception $e) {
      // Sin contexto de tenant.
    }
    return NULL;
  }

}

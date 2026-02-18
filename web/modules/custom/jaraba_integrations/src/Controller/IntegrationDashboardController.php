<?php

declare(strict_types=1);

namespace Drupal\jaraba_integrations\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\jaraba_integrations\Entity\Connector;
use Drupal\jaraba_integrations\Service\ConnectorRegistryService;
use Drupal\jaraba_integrations\Service\ConnectorInstallerService;
use Drupal\jaraba_integrations\Service\ConnectorHealthCheckService;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;

/**
 * Controlador frontend para el dashboard de integraciones del tenant.
 *
 * PROPÓSITO:
 * Página limpia en /integraciones con catálogo de conectores,
 * instalaciones del tenant, detalle de conector y gestión de webhooks.
 *
 * DIRECTRICES:
 * - Template limpio sin regiones Drupal ({% include %} parciales).
 * - Todos los textos traducibles con $this->t().
 * - AJAX para instalar/desinstalar (isXmlHttpRequest check).
 * - Body class 'page-integrations' vía hook_preprocess_html().
 */
class IntegrationDashboardController extends ControllerBase implements ContainerInjectionInterface {

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
   * Dashboard principal de integraciones (/integraciones).
   *
   * LÓGICA:
   * 1. Obtiene conectores publicados del catálogo.
   * 2. Obtiene instalaciones del tenant actual.
   * 3. Cruza datos para marcar instalados.
   * 4. Renderiza template con grid de cards.
   */
  public function dashboard(Request $request): array {
    $tenant_id = $this->getTenantId();
    $category_filter = $request->query->get('category', '');
    $search_query = $request->query->get('q', '');

    // Obtener conectores.
    if (!empty($search_query)) {
      $connectors = $this->connectorRegistry->searchConnectors($search_query);
    }
    elseif (!empty($category_filter)) {
      $connectors = $this->connectorRegistry->getByCategory($category_filter);
    }
    else {
      $connectors = $this->connectorRegistry->getPublishedConnectors();
    }

    // Obtener instalaciones del tenant.
    $installations = [];
    $installed_ids = [];
    if ($tenant_id) {
      $tenant_installations = $this->connectorInstaller->getTenantInstallations($tenant_id);
      foreach ($tenant_installations as $installation) {
        $connector_id = $installation->get('connector_id')->target_id;
        $installations[$connector_id] = $installation;
        $installed_ids[] = $connector_id;
      }
    }

    // Categorías con conteo.
    $categories = $this->connectorRegistry->getCategoryCounts();

    // Estadísticas.
    $stats = [
      'total_available' => count($this->connectorRegistry->getPublishedConnectors()),
      'total_installed' => count($installed_ids),
      'active_installations' => count(array_filter(
        $installations,
        fn($i) => $i->isActive()
      )),
    ];

    return [
      '#theme' => 'jaraba_integrations_dashboard',
      '#connectors' => $connectors,
      '#installed' => $installations,
      '#categories' => $categories,
      '#stats' => $stats,
      '#tenant_id' => $tenant_id,
      '#attached' => [
        'library' => ['jaraba_integrations/dashboard'],
      ],
      '#cache' => [
        'tags' => ['connector_list'],
        'contexts' => ['user', 'url.query_args'],
      ],
    ];
  }

  /**
   * Detalle de un conector (/integraciones/{connector}).
   */
  public function connectorDetail(Connector $connector): array {
    $tenant_id = $this->getTenantId();
    $installation = NULL;
    $health = NULL;

    if ($tenant_id) {
      $installation = $this->connectorInstaller->getInstallation($connector, $tenant_id);
      if ($installation) {
        $health_status = $installation->get('health_status')->value;
        $health = $health_status ? json_decode($health_status, TRUE) : NULL;
      }
    }

    return [
      '#theme' => 'jaraba_integrations_connector_card',
      '#connector' => $connector,
      '#is_installed' => $installation !== NULL,
      '#installation' => $installation,
      '#attached' => [
        'library' => ['jaraba_integrations/dashboard'],
      ],
    ];
  }

  /**
   * Title callback para detalle de conector.
   */
  public function connectorTitle(Connector $connector): string {
    return $connector->getName();
  }

  /**
   * Instala un conector para el tenant actual (POST).
   */
  public function installConnector(Connector $connector, Request $request): RedirectResponse {
    $tenant_id = $this->getTenantId();
    if (!$tenant_id) {
      $this->messenger()->addError($this->t('No se pudo determinar el tenant actual.'));
      return $this->redirect('jaraba_integrations.frontend.dashboard');
    }

    $installation = $this->connectorInstaller->install($connector, $tenant_id);
    if ($installation) {
      $this->messenger()->addStatus($this->t('Conector %name instalado correctamente.', [
        '%name' => $connector->getName(),
      ]));

      // Si requiere configuración, redirigir a configurar.
      if ($connector->getAuthType() !== Connector::AUTH_NONE) {
        return $this->redirect('jaraba_integrations.frontend.connector_configure', [
          'connector' => $connector->id(),
        ]);
      }
    }
    else {
      $this->messenger()->addWarning($this->t('El conector %name ya está instalado.', [
        '%name' => $connector->getName(),
      ]));
    }

    return $this->redirect('jaraba_integrations.frontend.dashboard');
  }

  /**
   * Desinstala un conector para el tenant actual (POST).
   */
  public function uninstallConnector(Connector $connector, Request $request): RedirectResponse {
    $tenant_id = $this->getTenantId();
    if (!$tenant_id) {
      $this->messenger()->addError($this->t('No se pudo determinar el tenant actual.'));
      return $this->redirect('jaraba_integrations.frontend.dashboard');
    }

    if ($this->connectorInstaller->uninstall($connector, $tenant_id)) {
      $this->messenger()->addStatus($this->t('Conector %name desinstalado.', [
        '%name' => $connector->getName(),
      ]));
    }
    else {
      $this->messenger()->addWarning($this->t('El conector %name no estaba instalado.', [
        '%name' => $connector->getName(),
      ]));
    }

    return $this->redirect('jaraba_integrations.frontend.dashboard');
  }

  /**
   * Panel de webhooks del tenant (/integraciones/webhooks).
   */
  public function webhooks(): array {
    $tenant_id = $this->getTenantId();
    $subscriptions = [];

    if ($tenant_id) {
      $storage = $this->entityTypeManager()->getStorage('webhook_subscription');
      $ids = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('tenant_id', $tenant_id)
        ->sort('created', 'DESC')
        ->execute();

      if ($ids) {
        $subscriptions = $storage->loadMultiple($ids);
      }
    }

    return [
      '#theme' => 'jaraba_integrations_webhook_log',
      '#subscriptions' => $subscriptions,
      '#recent_deliveries' => [],
      '#attached' => [
        'library' => ['jaraba_integrations/dashboard'],
      ],
    ];
  }

  /**
   * Obtiene el tenant_id del usuario actual.
   */
  protected function getTenantId(): ?string {
    // Usar el servicio de contexto de tenant de ecosistema_jaraba_core.
    try {
      $tenant_context = $this->tenantContext;
      $tenant = $tenant_context->getCurrentTenant();
      if ($tenant) {
        $group = $tenant->getGroup();
        return $group ? (string) $group->id() : NULL;
      }
    }
    catch (\Exception $e) {
      // Fallback: sin contexto de tenant.
    }

    return NULL;
  }

}

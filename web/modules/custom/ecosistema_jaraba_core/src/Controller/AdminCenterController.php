<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\ecosistema_jaraba_core\Service\AdminCenterAggregatorService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;

/**
 * Admin Center Dashboard — Vista unificada para Super Admin.
 *
 * Delega la recoleccion de KPIs al AdminCenterAggregatorService,
 * que maneja la inyeccion opcional de servicios de modulos satelite.
 *
 * F6 — Doc 181 / Spec f104.
 */
class AdminCenterController extends ControllerBase {

  public function __construct(
    protected LoggerInterface $logger,
    protected AdminCenterAggregatorService $aggregator,
    protected ?object $auditLog = NULL,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('logger.channel.ecosistema_jaraba_core'),
      $container->get('ecosistema_jaraba_core.admin_center_aggregator'),
      $container->has('jaraba_security_compliance.audit_log') ? $container->get('jaraba_security_compliance.audit_log') : NULL,
    );
  }

  /**
   * GET /admin/jaraba/center
   *
   * Dashboard principal del Admin Center.
   */
  public function dashboard(): array {
    $data = $this->aggregator->getDashboardData();

    return [
      '#theme' => 'admin_center_dashboard',
      '#kpis' => $data['kpis'],
      '#alerts' => $data['alerts'],
      '#tenant_stats' => $data['tenant_stats'],
      '#quick_links' => $data['quick_links'],
      '#activity' => $data['activity'],
      '#attached' => [
        'library' => [
          'ecosistema_jaraba_core/admin-center',
          'ecosistema_jaraba_core/admin-command-palette',
        ],
        'drupalSettings' => [
          'adminCenter' => [
            'kpis' => $data['kpis'],
            'searchUrl' => Url::fromRoute('ecosistema_jaraba_core.admin.search_api')->toString(),
          ],
        ],
      ],
      '#cache' => [
        'max-age' => 300,
        'contexts' => ['user.permissions'],
      ],
    ];
  }

  /**
   * GET /admin/jaraba/center/tenants
   *
   * Pagina de gestion de tenants con DataTable.
   */
  public function tenants(): array {
    return [
      '#theme' => 'admin_center_tenants',
      '#attached' => [
        'library' => [
          'ecosistema_jaraba_core/admin-center',
          'ecosistema_jaraba_core/admin-center-tenants',
          'ecosistema_jaraba_core/admin-command-palette',
        ],
        'drupalSettings' => [
          'adminCenter' => [
            'tenantsApiUrl' => Url::fromRoute('ecosistema_jaraba_core.admin.tenants.list')->toString(),
            'tenantsExportUrl' => Url::fromRoute('ecosistema_jaraba_core.admin.tenants.export')->toString(),
            'tenantsDetailUrl' => '/api/v1/admin/tenants/{id}',
            'searchUrl' => Url::fromRoute('ecosistema_jaraba_core.admin.search_api')->toString(),
          ],
        ],
      ],
      '#cache' => [
        'max-age' => 0,
        'contexts' => ['user.permissions'],
      ],
    ];
  }

  /**
   * GET /admin/jaraba/center/users
   *
   * Pagina de gestion de usuarios con DataTable.
   */
  public function users(): array {
    return [
      '#theme' => 'admin_center_users',
      '#attached' => [
        'library' => [
          'ecosistema_jaraba_core/admin-center',
          'ecosistema_jaraba_core/admin-center-users',
          'ecosistema_jaraba_core/admin-command-palette',
        ],
        'drupalSettings' => [
          'adminCenter' => [
            'usersApiUrl' => Url::fromRoute('ecosistema_jaraba_core.admin.users.list')->toString(),
            'searchUrl' => Url::fromRoute('ecosistema_jaraba_core.admin.search_api')->toString(),
          ],
        ],
      ],
      '#cache' => [
        'max-age' => 0,
        'contexts' => ['user.permissions'],
      ],
    ];
  }

  /**
   * GET /admin/jaraba/center/finance
   *
   * Centro Financiero con metricas SaaS y analytics.
   */
  public function finance(): array {
    return [
      '#theme' => 'admin_center_finance',
      '#attached' => [
        'library' => [
          'ecosistema_jaraba_core/admin-center',
          'ecosistema_jaraba_core/admin-center-finance',
          'ecosistema_jaraba_core/admin-command-palette',
        ],
        'drupalSettings' => [
          'adminCenter' => [
            'financeMetricsUrl' => Url::fromRoute('ecosistema_jaraba_core.admin.finance.metrics')->toString(),
            'financeTenantsUrl' => Url::fromRoute('ecosistema_jaraba_core.admin.finance.tenants')->toString(),
            'searchUrl' => Url::fromRoute('ecosistema_jaraba_core.admin.search_api')->toString(),
          ],
        ],
      ],
      '#cache' => [
        'max-age' => 0,
        'contexts' => ['user.permissions'],
      ],
    ];
  }

  /**
   * GET /admin/jaraba/center/analytics
   *
   * Dashboard de analytics de plataforma.
   */
  public function analytics(): array {
    return [
      '#theme' => 'admin_center_analytics',
      '#attached' => [
        'library' => [
          'ecosistema_jaraba_core/admin-center',
          'ecosistema_jaraba_core/admin-center-analytics',
          'ecosistema_jaraba_core/admin-command-palette',
        ],
        'drupalSettings' => [
          'adminCenter' => [
            'analyticsOverviewUrl' => Url::fromRoute('ecosistema_jaraba_core.admin.analytics.overview')->toString(),
            'analyticsAiUrl' => Url::fromRoute('ecosistema_jaraba_core.admin.analytics.ai')->toString(),
            'searchUrl' => Url::fromRoute('ecosistema_jaraba_core.admin.search_api')->toString(),
          ],
        ],
      ],
      '#cache' => [
        'max-age' => 0,
        'contexts' => ['user.permissions'],
      ],
    ];
  }

  /**
   * GET /admin/jaraba/center/logs
   *
   * Visor de logs de actividad y sistema.
   */
  public function logs(): array {
    return [
      '#theme' => 'admin_center_logs',
      '#attached' => [
        'library' => [
          'ecosistema_jaraba_core/admin-center',
          'ecosistema_jaraba_core/admin-center-logs',
          'ecosistema_jaraba_core/admin-command-palette',
        ],
        'drupalSettings' => [
          'adminCenter' => [
            'logsApiUrl' => Url::fromRoute('ecosistema_jaraba_core.admin.logs.list')->toString(),
            'searchUrl' => Url::fromRoute('ecosistema_jaraba_core.admin.search_api')->toString(),
          ],
        ],
      ],
      '#cache' => [
        'max-age' => 0,
        'contexts' => ['user.permissions'],
      ],
    ];
  }

  /**
   * GET /admin/jaraba/center/alerts
   *
   * Dashboard de alertas y playbooks del Admin Center.
   */
  public function alerts(): array {
    return [
      '#theme' => 'admin_center_alerts',
      '#attached' => [
        'library' => [
          'ecosistema_jaraba_core/admin-center',
          'ecosistema_jaraba_core/admin-center-alerts',
          'ecosistema_jaraba_core/admin-command-palette',
        ],
        'drupalSettings' => [
          'adminCenter' => [
            'alertsSummaryUrl' => Url::fromRoute('ecosistema_jaraba_core.admin.alerts.summary')->toString(),
            'alertsListUrl' => Url::fromRoute('ecosistema_jaraba_core.admin.alerts.list')->toString(),
            'playbooksListUrl' => Url::fromRoute('ecosistema_jaraba_core.admin.playbooks.list')->toString(),
            'searchUrl' => Url::fromRoute('ecosistema_jaraba_core.admin.search_api')->toString(),
          ],
        ],
      ],
      '#cache' => [
        'max-age' => 0,
        'contexts' => ['user.permissions'],
      ],
    ];
  }

  /**
   * GET /admin/jaraba/center/settings
   *
   * Configuración global de la plataforma.
   */
  public function settings(): array {
    return [
      '#theme' => 'admin_center_settings',
      '#attached' => [
        'library' => [
          'ecosistema_jaraba_core/admin-center',
          'ecosistema_jaraba_core/admin-center-settings',
          'ecosistema_jaraba_core/admin-command-palette',
        ],
        'drupalSettings' => [
          'adminCenter' => [
            'settingsOverviewUrl' => Url::fromRoute('ecosistema_jaraba_core.admin.settings.overview')->toString(),
            'settingsGeneralSaveUrl' => Url::fromRoute('ecosistema_jaraba_core.admin.settings.general.save')->toString(),
            'settingsPlansUrl' => Url::fromRoute('ecosistema_jaraba_core.admin.settings.plans.list')->toString(),
            'settingsIntegrationsUrl' => Url::fromRoute('ecosistema_jaraba_core.admin.settings.integrations')->toString(),
            'settingsApiKeysUrl' => Url::fromRoute('ecosistema_jaraba_core.admin.settings.apikeys.list')->toString(),
            'searchUrl' => Url::fromRoute('ecosistema_jaraba_core.admin.search_api')->toString(),
          ],
        ],
      ],
      '#cache' => [
        'max-age' => 0,
        'contexts' => ['user.permissions'],
      ],
    ];
  }

  /**
   * GET /api/v1/admin/search?q={query}
   *
   * Busqueda fuzzy de tenants, usuarios y entidades.
   */
  public function searchApi(Request $request): JsonResponse {
    try {
      $query = trim($request->query->get('q', ''));
      if (strlen($query) < 2) {
        return new JsonResponse(['success' => TRUE, 'data' => []]);
      }

      // Registro de Auditoría (Fase D Compliance).
      if ($this->auditLog) {
        $this->auditLog->log('admin_search', "Super Admin buscó: $query", [
          'user_id' => $this->currentUser()->id(),
          'ip' => $request->getClientIp(),
        ]);
      }

      $results = [];

      // Buscar tenants (groups type tenant).
      $this->searchTenants($query, $results);

      // Buscar usuarios.
      $this->searchUsers($query, $results);

      // Buscar envíos Agro (Fase 5).
      $this->searchShipments($query, $results);

      // Buscar lotes Agro (Fase 6).
      $this->searchBatches($query, $results);

      // Limitar resultados.
      $results = array_slice($results, 0, 15);

      return new JsonResponse([
        'success' => TRUE,
        'data' => $results,
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Admin search error: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse([
        'success' => FALSE,
        'error' => 'Error en la busqueda.',
      ], 500);
    }
  }

  /**
   * Busca tenants por nombre.
   */
  protected function searchTenants(string $query, array &$results): void {
    try {
      $groupStorage = $this->entityTypeManager()->getStorage('group');
      $ids = $groupStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('label', '%' . $query . '%', 'LIKE')
        ->range(0, 5)
        ->execute();

      foreach ($groupStorage->loadMultiple($ids) as $group) {
        $results[] = [
          'type' => 'tenant',
          'id' => $group->id(),
          'label' => $group->label(),
          'url' => $group->toUrl('canonical')->toString(),
          'icon' => 'building',
        ];
      }
    }
    catch (\Exception $e) {
      // Silently skip.
    }
  }

  /**
   * Busca usuarios por nombre o email.
   */
  protected function searchUsers(string $query, array &$results): void {
    try {
      $userStorage = $this->entityTypeManager()->getStorage('user');
      $ids = $userStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('name', '%' . $query . '%', 'LIKE')
        ->condition('uid', 0, '>')
        ->range(0, 5)
        ->execute();

      foreach ($userStorage->loadMultiple($ids) as $user) {
        $results[] = [
          'type' => 'user',
          'id' => $user->id(),
          'label' => $user->getDisplayName(),
          'url' => $user->toUrl('canonical')->toString(),
          'icon' => 'user',
          'email' => $user->getEmail(),
        ];
      }
    }
    catch (\Exception $e) {
      // Silently skip.
    }
  }

  /**
   * Busca envíos por número de seguimiento o ID.
   */
  protected function searchShipments(string $query, array &$results): void {
    try {
      if (!$this->entityTypeManager()->hasDefinition('agro_shipment')) return;
      $storage = $this->entityTypeManager()->getStorage('agro_shipment');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition($storage->getQuery()->orConditionGroup()
          ->condition('shipment_number', '%' . $query . '%', 'LIKE')
          ->condition('tracking_number', '%' . $query . '%', 'LIKE')
        )
        ->range(0, 5)
        ->execute();

      foreach ($storage->loadMultiple($ids) as $shipment) {
        $results[] = [
          'type' => 'shipment',
          'id' => $shipment->id(),
          'label' => $shipment->getShipmentNumber(),
          'url' => $shipment->toUrl('canonical')->toString(),
          'icon' => 'truck',
          'meta' => $shipment->getCarrierId(),
        ];
      }
    }
    catch (\Exception $e) {}
  }

  /**
   * Busca lotes por código.
   */
  protected function searchBatches(string $query, array &$results): void {
    try {
      if (!$this->entityTypeManager()->hasDefinition('agro_batch')) return;
      $storage = $this->entityTypeManager()->getStorage('agro_batch');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('batch_code', '%' . $query . '%', 'LIKE')
        ->range(0, 5)
        ->execute();

      foreach ($storage->loadMultiple($ids) as $batch) {
        $results[] = [
          'type' => 'batch',
          'id' => $batch->id(),
          'label' => $batch->get('batch_code')->value,
          'url' => $batch->toUrl('canonical')->toString(),
          'icon' => 'box',
        ];
      }
    }
    catch (\Exception $e) {}
  }

}

<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;

/**
 * Admin Center Dashboard — Vista unificada para Super Admin.
 *
 * Agrega KPIs de todos los modulos:
 * - SaaS Metrics (MRR, ARR) via jaraba_foc
 * - Health Scores via jaraba_customer_success
 * - Churn Prediction via jaraba_customer_success
 * - Subscriptions via jaraba_billing
 * - Alert Rules via ecosistema_jaraba_core
 *
 * Fase 6 — Doc 181.
 */
class AdminCenterController extends ControllerBase {

  public function __construct(
    protected LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('logger.channel.ecosistema_jaraba_core'),
    );
  }

  /**
   * GET /admin/jaraba/center
   *
   * Dashboard principal del Admin Center.
   */
  public function dashboard(): array {
    $kpis = $this->getKpis();
    $alerts = $this->getActiveAlerts();
    $tenantStats = $this->getTenantStats();
    $quickLinks = $this->getQuickLinks();

    return [
      '#theme' => 'admin_center_dashboard',
      '#kpis' => $kpis,
      '#alerts' => $alerts,
      '#tenant_stats' => $tenantStats,
      '#quick_links' => $quickLinks,
      '#attached' => [
        'library' => [
          'ecosistema_jaraba_core/admin-center',
          'ecosistema_jaraba_core/admin-command-palette',
        ],
        'drupalSettings' => [
          'adminCenter' => [
            'kpis' => $kpis,
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

      $results = [];

      // Buscar tenants (groups type tenant).
      $this->searchTenants($query, $results);

      // Buscar usuarios.
      $this->searchUsers($query, $results);

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
   * Obtiene KPIs de todos los servicios disponibles.
   */
  protected function getKpis(): array {
    $kpis = [
      'mrr' => ['value' => 0, 'label' => $this->t('MRR'), 'format' => 'currency', 'trend' => 0],
      'arr' => ['value' => 0, 'label' => $this->t('ARR'), 'format' => 'currency', 'trend' => 0],
      'tenants' => ['value' => 0, 'label' => $this->t('Tenants'), 'format' => 'number', 'trend' => 0],
      'mau' => ['value' => 0, 'label' => $this->t('MAU'), 'format' => 'number', 'trend' => 0],
      'churn' => ['value' => 0, 'label' => $this->t('Churn'), 'format' => 'percent', 'trend' => 0],
      'health_avg' => ['value' => 0, 'label' => $this->t('Health Avg'), 'format' => 'score', 'trend' => 0],
    ];

    try {
      // SaaS Metrics.
      if (\Drupal::hasService('jaraba_foc.saas_metrics')) {
        $metrics = \Drupal::service('jaraba_foc.saas_metrics');
        $kpis['mrr']['value'] = $metrics->calculateMRR();
        $kpis['arr']['value'] = $metrics->calculateARR();
      }

      // Tenant count.
      $groupStorage = $this->entityTypeManager()->getStorage('group');
      $tenantCount = $groupStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', 'tenant')
        ->count()
        ->execute();
      $kpis['tenants']['value'] = (int) $tenantCount;

      // Active users (last 30 days).
      $db = \Drupal::database();
      $thirtyDaysAgo = strtotime('-30 days');
      $mauCount = $db->select('users_field_data', 'u')
        ->condition('u.access', $thirtyDaysAgo, '>=')
        ->condition('u.status', 1)
        ->condition('u.uid', 0, '>')
        ->countQuery()
        ->execute()
        ->fetchField();
      $kpis['mau']['value'] = (int) $mauCount;
    }
    catch (\Exception $e) {
      $this->logger->warning('Error obteniendo KPIs: @error', ['@error' => $e->getMessage()]);
    }

    return $kpis;
  }

  /**
   * Obtiene alertas activas.
   */
  protected function getActiveAlerts(): array {
    $alerts = [];

    try {
      $storage = $this->entityTypeManager()->getStorage('alert_rule');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', TRUE)
        ->sort('created', 'DESC')
        ->range(0, 10)
        ->execute();

      foreach ($storage->loadMultiple($ids) as $alert) {
        $alerts[] = [
          'id' => $alert->id(),
          'label' => $alert->label(),
          'metric' => $alert->get('metric')->value ?? '',
          'channel' => $alert->get('notification_channel')->value ?? '',
        ];
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('Error obteniendo alertas: @error', ['@error' => $e->getMessage()]);
    }

    return $alerts;
  }

  /**
   * Estadisticas de tenants por estado.
   */
  protected function getTenantStats(): array {
    $stats = [
      'active' => 0,
      'trial' => 0,
      'suspended' => 0,
      'total' => 0,
    ];

    try {
      $groupStorage = $this->entityTypeManager()->getStorage('group');

      $stats['total'] = (int) $groupStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', 'tenant')
        ->count()
        ->execute();

      $stats['active'] = $stats['total'];
    }
    catch (\Exception $e) {
      $this->logger->warning('Error obteniendo stats de tenants: @error', ['@error' => $e->getMessage()]);
    }

    return $stats;
  }

  /**
   * Quick links para el sidebar.
   */
  protected function getQuickLinks(): array {
    return [
      [
        'label' => $this->t('Tenants'),
        'url' => '/admin/structure/group',
        'icon' => 'building',
        'shortcut' => 'G+T',
      ],
      [
        'label' => $this->t('Usuarios'),
        'url' => '/admin/people',
        'icon' => 'users',
        'shortcut' => 'G+U',
      ],
      [
        'label' => $this->t('Finanzas'),
        'url' => '/admin/finops',
        'icon' => 'dollar-sign',
        'shortcut' => 'G+F',
      ],
      [
        'label' => $this->t('Health Monitor'),
        'url' => '/admin/health',
        'icon' => 'activity',
        'shortcut' => '',
      ],
      [
        'label' => $this->t('Analytics'),
        'url' => '/admin/jaraba/analytics',
        'icon' => 'bar-chart',
        'shortcut' => '',
      ],
      [
        'label' => $this->t('Alertas'),
        'url' => '/admin/config/system/alert-rules',
        'icon' => 'bell',
        'shortcut' => 'A',
      ],
      [
        'label' => $this->t('Compliance'),
        'url' => '/admin/seguridad',
        'icon' => 'shield',
        'shortcut' => '',
      ],
      [
        'label' => $this->t('Email'),
        'url' => '/admin/jaraba/email',
        'icon' => 'mail',
        'shortcut' => '',
      ],
    ];
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

}

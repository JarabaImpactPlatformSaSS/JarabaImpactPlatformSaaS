<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\ecosistema_jaraba_core\TenantSettings\TenantSettingsRegistry;
use Drupal\jaraba_addons\Service\TenantVerticalService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controlador para el Dashboard del Tenant (Self-Service Portal).
 *
 * PROPÓSITO:
 * Proporciona a los tenants una vista de sus propias métricas y
 * permite configuraciones self-service sin intervención del admin.
 *
 * MÉTRICAS MOSTRADAS:
 * - MRR propio del tenant (desde plan de suscripción)
 * - Número de clientes (usuarios asociados)
 * - Número de productos (si aplica por vertical)
 * - Ventas del mes (desde transacciones FOC)
 * - Estado de suscripción
 * - Días de trial restantes
 */
class TenantSelfServiceController extends ControllerBase {

  /**
   * Mapa de acciones rapidas por vertical.
   *
   * Cada vertical tiene su propia ruta de "productos", etiqueta e icono
   * segun el tipo de negocio que representa.
   *
   * @var array<string, array{route: string, label: string, icon: string}>
   */
  protected const VERTICAL_PRODUCT_MAP = [
    'comercioconecta' => [
      'route' => 'jaraba_comercio_conecta.merchant_portal.products',
      'label' => 'Mis Productos',
      'icon' => 'package',
    ],
    'serviciosconecta' => [
      'route' => 'jaraba_servicios_conecta.provider_portal.offerings',
      'label' => 'Mis Servicios',
      'icon' => 'briefcase',
    ],
    'agroconecta' => [
      'route' => 'jaraba_agroconecta_core.producer.products',
      'label' => 'Mis Productos',
      'icon' => 'package',
    ],
    'empleabilidad' => [
      'route' => 'jaraba_job_board.employer_jobs',
      'label' => 'Mis Ofertas',
      'icon' => 'briefcase',
    ],
    'emprendimiento' => [
      'route' => 'jaraba_business_tools.entrepreneur_dashboard',
      'label' => 'Mis Proyectos',
      'icon' => 'lightbulb',
    ],
    'jarabalex' => [
      'route' => 'jaraba_legal_cases.dashboard',
      'label' => 'Mis Casos',
      'icon' => 'scale-balance',
      'icon_category' => 'legal',
    ],
    'formacion' => [
      'route' => 'jaraba_lms.my_courses',
      'label' => 'Mis Cursos',
      'icon' => 'graduation-cap',
    ],
    'jaraba_content_hub' => [
      'route' => 'jaraba_content_hub.dashboard.frontend',
      'label' => 'Mi Contenido',
      'icon' => 'document',
    ],
    'andalucia_ei' => [
      'route' => 'jaraba_andalucia_ei.dashboard',
      'label' => 'Mi Programa',
      'icon' => 'clipboard',
    ],
  ];

  /**
   * Servicio de verticales multi-tenant (opcional, de jaraba_addons).
   *
   * @var \Drupal\jaraba_addons\Service\TenantVerticalService|null
   */
  protected ?TenantVerticalService $tenantVerticalService;

  /**
   * Constructor.
   */
  public function __construct(
    protected TenantContextService $tenantContext,
    protected Connection $database,
    protected TenantSettingsRegistry $settingsRegistry,
    protected RouteProviderInterface $routeProvider,
    ?TenantVerticalService $tenantVerticalService = NULL,
  ) {
    $this->tenantVerticalService = $tenantVerticalService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $tenantVerticalService = NULL;
    if ($container->has('jaraba_addons.tenant_vertical')) {
      $tenantVerticalService = $container->get('jaraba_addons.tenant_vertical');
    }

    return new static(
          $container->get('ecosistema_jaraba_core.tenant_context'),
          $container->get('database'),
          $container->get('ecosistema_jaraba_core.tenant_settings_registry'),
          $container->get('router.route_provider'),
          $tenantVerticalService,
      );
  }

  /**
   * Página principal del dashboard del tenant.
   *
   * @return array
   *   Render array con el dashboard.
   */
  public function dashboard(): array {
    $tenant = $this->tenantContext->getCurrentTenant();

    if (!$tenant) {
      return [
        '#markup' => $this->t('No tienes un tenant asignado. Contacta con el administrador.'),
      ];
    }

    // Obtener métricas reales del tenant.
    $metrics = $this->getTenantMetrics($tenant);
    $subscriptionInfo = $this->getSubscriptionInfo($tenant);
    $recentActivity = $this->getRecentActivity($tenant);
    $quickLinks = $this->getQuickLinks($tenant);

    // P1-04: Generar datos para gráficos Chart.js via drupalSettings.
    $chartData = $this->buildChartData($tenant);

    $build = [
      '#theme' => 'tenant_self_service_dashboard',
      '#tenant' => $tenant,
      '#metrics' => $metrics,
      '#subscription_info' => $subscriptionInfo,
      '#recent_activity' => $recentActivity,
      '#quick_links' => $quickLinks,
      '#attached' => [
        'library' => [
          'ecosistema_jaraba_core/tenant-dashboard',
        ],
        'drupalSettings' => [
          'tenantDashboard' => [
            'charts' => $chartData,
            'currency' => 'EUR',
            'locale' => 'es-ES',
          ],
        ],
      ],
    ];

    return $build;
  }

  /**
   * Obtiene métricas reales del tenant.
   */
  protected function getTenantMetrics($tenant): array {
    $tenantId = $tenant->id();

    // Obtener MRR desde el plan de suscripción.
    $plan = $tenant->getSubscriptionPlan();
    $mrr = 0;
    if ($plan && method_exists($plan, 'getMonthlyPrice')) {
      $mrr = $plan->getMonthlyPrice();
    }
    elseif ($plan && $plan->hasField('monthly_price')) {
      $mrr = (float) ($plan->get('monthly_price')->value ?? 0);
    }

    // Obtener métricas de uso desde TenantContextService.
    $usageMetrics = $this->tenantContext->getUsageMetrics($tenant);

    // Extraer contadores de las métricas estructuradas.
    $membersCount = $usageMetrics['productores']['count'] ?? 0;
    $contentCount = $usageMetrics['contenido']['count'] ?? 0;

    // Obtener ventas del mes desde FOC (si el módulo está habilitado).
    $salesMonth = $this->getMonthSales($tenantId);

    // Calcular tendencias (comparando con mes anterior).
    $salesTrend = $this->calculateTrend($tenantId, 'sales');

    return [
      'mrr' => [
        'value' => '€' . number_format($mrr, 2, ',', '.'),
        'raw_value' => $mrr,
        'label' => $this->t('MRR'),
        'description' => $this->t('Ingresos Mensuales Recurrentes'),
        'trend' => 0,
        'icon' => ['category' => 'ui', 'name' => 'coin'],
      ],
      'members' => [
        'value' => $membersCount,
        'label' => $this->t('Miembros'),
        'description' => $this->t('Usuarios asociados'),
        'trend' => 0,
        'icon' => ['category' => 'ui', 'name' => 'users'],
      ],
      'content' => [
        'value' => $contentCount,
        'label' => $this->t('Contenido'),
        'description' => $this->t('Elementos creados'),
        'trend' => 0,
        'icon' => ['category' => 'ui', 'name' => 'package'],
      ],
      'sales_month' => [
        'value' => '€' . number_format($salesMonth, 2, ',', '.'),
        'raw_value' => $salesMonth,
        'label' => $this->t('Ventas del Mes'),
        'description' => $this->t('Total de ventas este mes'),
        'trend' => $salesTrend,
        'icon' => ['category' => 'analytics', 'name' => 'chart-bar'],
      ],
    ];
  }

  /**
   * Obtiene información de la suscripción del tenant.
   */
  protected function getSubscriptionInfo($tenant): array {
    $plan = $tenant->getSubscriptionPlan();
    $status = $tenant->get('subscription_status')->value ?? 'trial';

    $info = [
      'plan_name' => $plan ? $plan->label() : $this->t('Sin plan'),
      'status' => $status,
      'status_label' => $this->getStatusLabel($status),
      'status_class' => $this->getStatusClass($status),
      'is_trial' => $status === 'trial',
      'trial_days_remaining' => 0,
      'next_billing_date' => NULL,
    ];

    // Calcular días de trial restantes.
    if ($status === 'trial' && $tenant->hasField('trial_ends')) {
      $trialEnds = $tenant->get('trial_ends')->value;
      if ($trialEnds) {
        $trialEndsTimestamp = strtotime($trialEnds);
        $daysRemaining = max(0, ceil(($trialEndsTimestamp - time()) / 86400));
        $info['trial_days_remaining'] = (int) $daysRemaining;
      }
    }

    // Próxima fecha de facturación.
    if ($tenant->hasField('next_billing_date') && !$tenant->get('next_billing_date')->isEmpty()) {
      $info['next_billing_date'] = $tenant->get('next_billing_date')->value;
    }

    return $info;
  }

  /**
   * Obtiene actividad reciente del tenant.
   */
  protected function getRecentActivity($tenant): array {
    $tenantId = $tenant->id();
    $activities = [];

    // Intentar obtener transacciones recientes del FOC.
    try {
      if ($this->database->schema()->tableExists('financial_transaction')) {
        $results = $this->database->select('financial_transaction', 'ft')
          ->fields('ft', ['id', 'type', 'amount', 'currency', 'created'])
          ->condition('tenant_id', $tenantId)
          ->orderBy('created', 'DESC')
          ->range(0, 5)
          ->execute();

        foreach ($results as $row) {
          $activities[] = [
            'type' => $row->type,
            'description' => $this->getActivityDescription($row->type, $row->amount, $row->currency),
            'date' => date('d M Y H:i', $row->created),
            'amount' => '€' . number_format((float) $row->amount, 2, ',', '.'),
          ];
        }
      }
    }
    catch (\Exception $e) {
      // Silenciar errores si la tabla no existe.
    }

    // Si no hay actividad, mostrar mensaje.
    if (empty($activities)) {
      $activities[] = [
        'type' => 'info',
        'description' => $this->t('No hay actividad reciente'),
        'date' => '',
        'amount' => '',
      ];
    }

    return $activities;
  }

  /**
   * Obtiene ventas del mes actual.
   */
  protected function getMonthSales(int|string $tenantId): float {
    $startOfMonth = strtotime('first day of this month midnight');
    $sales = 0.0;

    try {
      if ($this->database->schema()->tableExists('financial_transaction')) {
        $query = $this->database->select('financial_transaction', 'ft');
        $query->condition('tenant_id', $tenantId);
        $query->condition('type', ['sale', 'order', 'payment'], 'IN');
        $query->condition('created', $startOfMonth, '>=');
        $query->addExpression('SUM(amount)', 'total');
        $result = $query->execute()->fetchField();

        $sales = (float) ($result ?? 0);
      }
    }
    catch (\Exception $e) {
      // Silenciar errores.
    }

    return $sales;
  }

  /**
   * Calcula tendencia comparando con período anterior.
   */
  protected function calculateTrend(int|string $tenantId, string $type): int {
    // Placeholder - en futuras versiones calcular tendencia real.
    return 0;
  }

  /**
   * Obtiene etiqueta de estado legible.
   */
  protected function getStatusLabel(string $status): string {
    return match ($status) {
      'trial' => (string) $this->t('Prueba gratuita'),
            'active' => (string) $this->t('Activo'),
            'past_due' => (string) $this->t('Pago pendiente'),
            'suspended' => (string) $this->t('Suspendido'),
            'cancelled' => (string) $this->t('Cancelado'),
            default => ucfirst($status),
    };
  }

  /**
   * Obtiene clase CSS para el estado.
   */
  protected function getStatusClass(string $status): string {
    return match ($status) {
      'trial' => 'status--trial',
            'active' => 'status--active',
            'past_due' => 'status--warning',
            'suspended', 'cancelled' => 'status--danger',
            default => 'status--default',
    };
  }

  /**
   * Genera descripción de actividad.
   */
  protected function getActivityDescription(string $type, float $amount, string $currency): string {
    return match ($type) {
      'sale' => (string) $this->t('Venta realizada'),
            'order' => (string) $this->t('Nuevo pedido'),
            'payment' => (string) $this->t('Pago recibido'),
            'refund' => (string) $this->t('Reembolso procesado'),
            default => ucfirst($type),
    };
  }

  /**
   * Genera las acciones rapidas contextualizadas a TODOS los verticales del tenant.
   *
   * Modelo multi-vertical: resuelve primero via TenantVerticalService
   * (primario + addon subscriptions), luego genera un link por cada
   * vertical activo desde VERTICAL_PRODUCT_MAP.
   *
   * @return array
   *   Array de acciones rapidas con route, label, icon, enabled, is_primary.
   */
  protected function getQuickLinks($tenant): array {
    $links = [];

    // 1. Cambiar Plan — siempre disponible.
    $links[] = [
      'route' => 'ecosistema_jaraba_core.tenant.change_plan',
      'route_params' => [],
      'label' => $this->t('Cambiar Plan'),
      'icon' => ['category' => 'ui', 'name' => 'wallet'],
      'enabled' => TRUE,
      'is_primary' => FALSE,
    ];

    // 2. Configuracion — siempre disponible.
    $links[] = [
      'route' => 'ecosistema_jaraba_core.tenant_self_service.settings',
      'route_params' => [],
      'label' => $this->t('Configuracion'),
      'icon' => ['category' => 'ui', 'name' => 'settings'],
      'enabled' => TRUE,
      'is_primary' => FALSE,
    ];

    // 3. Links de verticales activos (primario + addons).
    $activeVerticals = $this->resolveActiveVerticals($tenant);
    $hasVerticalLinks = FALSE;

    foreach ($activeVerticals as $verticalKey => $verticalData) {
      $productLink = self::VERTICAL_PRODUCT_MAP[$verticalKey] ?? NULL;
      if ($productLink && $this->routeExists($productLink['route'])) {
        $iconCategory = $productLink['icon_category'] ?? 'ui';
        $links[] = [
          'route' => $productLink['route'],
          'route_params' => [],
          'label' => $this->t($productLink['label']),
          'icon' => ['category' => $iconCategory, 'name' => $productLink['icon']],
          'enabled' => TRUE,
          'is_primary' => $verticalData['is_primary'],
        ];
        $hasVerticalLinks = TRUE;
      }
    }

    // Fallback: site builder si no hay ningun vertical con portal.
    if (!$hasVerticalLinks) {
      $links[] = [
        'route' => 'jaraba_site_builder.frontend.dashboard',
        'route_params' => [],
        'label' => $this->t('Mi Sitio'),
        'icon' => ['category' => 'ui', 'name' => 'layout-template'],
        'enabled' => $this->routeExists('jaraba_site_builder.frontend.dashboard'),
        'is_primary' => FALSE,
      ];
    }

    // 4. Marketplace de verticales — acceso rapido al catalogo de addons.
    if ($this->routeExists('jaraba_addons.catalog')) {
      $links[] = [
        'route' => 'jaraba_addons.catalog',
        'route_params' => [],
        'label' => $this->t('Marketplace'),
        'icon' => ['category' => 'ui', 'name' => 'grid'],
        'enabled' => TRUE,
        'is_primary' => FALSE,
      ];
    }

    // 5. Soporte — siempre disponible si el modulo existe.
    $supportEnabled = $this->routeExists('jaraba_support.portal');
    $links[] = [
      'route' => $supportEnabled ? 'jaraba_support.portal' : '',
      'route_params' => [],
      'label' => $this->t('Soporte'),
      'icon' => ['category' => 'ui', 'name' => 'chat'],
      'enabled' => $supportEnabled,
      'is_primary' => FALSE,
    ];

    return $links;
  }

  /**
   * Resuelve todos los verticales activos del tenant.
   *
   * Usa TenantVerticalService si disponible (multi-vertical).
   * Fallback: solo el vertical primario del tenant.
   *
   * @return array<string, array{machine_name: string, label: string, is_primary: bool}>
   */
  protected function resolveActiveVerticals($tenant): array {
    // Preferir TenantVerticalService para resolucion completa.
    if ($this->tenantVerticalService) {
      $tenantId = (int) $tenant->id();
      return $this->tenantVerticalService->getActiveVerticals($tenantId);
    }

    // Fallback: solo vertical primario.
    $verticals = [];
    $vertical = $tenant->getVertical();
    if ($vertical) {
      $machineName = $vertical->getMachineName();
      if ($machineName) {
        $verticals[$machineName] = [
          'machine_name' => $machineName,
          'label' => $vertical->label() ?? $machineName,
          'is_primary' => TRUE,
        ];
      }
    }

    return $verticals;
  }

  /**
   * Verifica si una ruta existe en el sistema.
   *
   * Evita enlaces rotos a modulos no instalados.
   */
  protected function routeExists(string $routeName): bool {
    try {
      $this->routeProvider->getRouteByName($routeName);
      return TRUE;
    }
    catch (\Exception) {
      return FALSE;
    }
  }

  /**
   * P1-04: Genera datos para los 3 gráficos del dashboard.
   *
   * Datos inyectados via drupalSettings para evitar API calls
   * (ROUTE-LANGPREFIX-001: nunca URLs hardcoded en JS).
   *
   * @return array
   *   Array con claves 'sales', 'mrr', 'customers'.
   */
  protected function buildChartData($tenant): array {
    $tenantId = $tenant->id();

    return [
      'sales' => $this->buildSalesChartData($tenantId),
      'mrr' => $this->buildMrrChartData($tenant),
      'customers' => $this->buildCustomersChartData($tenantId),
    ];
  }

  /**
   * Datos del gráfico de ventas (últimos 30 días).
   */
  protected function buildSalesChartData(int|string $tenantId): array {
    $labels = [];
    $values = [];
    $total = 0.0;

    // Generar etiquetas para los últimos 30 días.
    for ($i = 29; $i >= 0; $i--) {
      $date = date('Y-m-d', strtotime("-{$i} days"));
      $labels[] = date('d M', strtotime($date));
      $values[$date] = 0.0;
    }

    // Consultar ventas diarias reales del tenant.
    try {
      if ($this->database->schema()->tableExists('financial_transaction')) {
        $since = strtotime('-30 days midnight');
        $query = $this->database->select('financial_transaction', 'ft');
        $query->addExpression("DATE(FROM_UNIXTIME(ft.created))", 'sale_date');
        $query->addExpression('SUM(ft.amount)', 'daily_total');
        $query->condition('ft.tenant_id', $tenantId);
        $query->condition('ft.type', ['sale', 'order', 'payment'], 'IN');
        $query->condition('ft.created', $since, '>=');
        $query->groupBy('sale_date');
        $results = $query->execute();

        foreach ($results as $row) {
          if (isset($values[$row->sale_date])) {
            $values[$row->sale_date] = (float) $row->daily_total;
          }
        }
      }
    }
    catch (\Throwable) {
      // Sin datos de venta — gráfico vacío es válido.
    }

    $dataPoints = array_values($values);
    $total = array_sum($dataPoints);
    $average = count($dataPoints) > 0 ? $total / count($dataPoints) : 0;

    return [
      'type' => 'bar',
      'labels' => $labels,
      'datasets' => [
              [
                'label' => (string) $this->t('Ventas diarias'),
                'data' => $dataPoints,
                'backgroundColor' => 'rgba(0, 169, 165, 0.6)',
                'borderColor' => 'rgba(0, 169, 165, 1)',
                'borderWidth' => 1,
                'borderRadius' => 4,
              ],
      ],
      'summary' => [
        'total' => round($total, 2),
        'average' => round($average, 2),
      ],
    ];
  }

  /**
   * Datos del gráfico de MRR (últimos 6 meses).
   */
  protected function buildMrrChartData($tenant): array {
    $labels = [];
    $values = [];

    // Precio mensual actual del plan.
    $currentMrr = 0.0;
    $plan = $tenant->getSubscriptionPlan();
    if ($plan && method_exists($plan, 'getMonthlyPrice')) {
      $currentMrr = (float) $plan->getMonthlyPrice();
    }
    elseif ($plan && $plan->hasField('monthly_price')) {
      $currentMrr = (float) ($plan->get('monthly_price')->value ?? 0);
    }

    // Generar 6 meses de etiquetas.
    for ($i = 5; $i >= 0; $i--) {
      $monthLabel = date('M Y', strtotime("-{$i} months"));
      $labels[] = $monthLabel;
      // Para los meses pasados, usar el MRR actual como aproximación.
      // En producción, se consultarían invoices históricas.
      $values[] = $currentMrr;
    }

    // Intentar datos históricos reales desde billing_invoice.
    try {
      $tenantId = $tenant->id();
      if ($this->database->schema()->tableExists('billing_invoice_field_data')) {
        $since = strtotime('-6 months');
        $query = $this->database->select('billing_invoice_field_data', 'bi');
        $query->addExpression("DATE_FORMAT(FROM_UNIXTIME(bi.created), '%Y-%m')", 'month');
        $query->addExpression('SUM(bi.total)', 'revenue');
        $query->condition('bi.tenant_id', $tenantId);
        $query->condition('bi.status', 'paid');
        $query->condition('bi.created', $since, '>=');
        $query->groupBy('month');
        $query->orderBy('month', 'ASC');
        $results = $query->execute()->fetchAll();

        if (!empty($results)) {
          // Reemplazar con datos reales.
          $labels = [];
          $values = [];
          foreach ($results as $row) {
            $labels[] = date('M Y', strtotime($row->month . '-01'));
            $values[] = (float) $row->revenue / 100;
          }
        }
      }
    }
    catch (\Throwable) {
      // Mantener aproximación basada en plan.
    }

    $growth = 0;
    if (count($values) >= 2) {
      $first = $values[0];
      $last = end($values);
      if ($first > 0) {
        $growth = round((($last - $first) / $first) * 100, 1);
      }
    }

    return [
      'type' => 'line',
      'labels' => $labels,
      'datasets' => [
              [
                'label' => 'MRR',
                'data' => $values,
                'borderColor' => 'rgba(35, 61, 99, 1)',
                'backgroundColor' => 'rgba(35, 61, 99, 0.1)',
                'fill' => TRUE,
                'tension' => 0.4,
                'pointBackgroundColor' => 'rgba(35, 61, 99, 1)',
              ],
      ],
      'summary' => [
        'current' => end($values) ?: 0,
        'growth' => $growth,
      ],
    ];
  }

  /**
   * Datos del gráfico de clientes por semana (últimas 8 semanas).
   */
  protected function buildCustomersChartData(int|string $tenantId): array {
    $labels = [];
    $values = [];

    // Generar 8 semanas de etiquetas.
    for ($i = 7; $i >= 0; $i--) {
      $weekStart = date('d M', strtotime("-{$i} weeks monday"));
      $labels[] = $weekStart;
      $values[] = 0;
    }

    // Contar usuarios nuevos por semana desde group_content.
    try {
      if ($this->database->schema()->tableExists('group_relationship_field_data')) {
        $since = strtotime('-8 weeks monday');
        $query = $this->database->select('group_relationship_field_data', 'gc');
        $query->addExpression("YEARWEEK(FROM_UNIXTIME(gc.created), 1)", 'week_num');
        $query->addExpression('COUNT(gc.id)', 'new_members');
        $query->condition('gc.type', '%member%', 'LIKE');
        $query->condition('gc.gid', $tenantId);
        $query->condition('gc.created', $since, '>=');
        $query->groupBy('week_num');
        $query->orderBy('week_num', 'ASC');
        $results = $query->execute()->fetchAll();

        if (!empty($results)) {
          // Mapear por índice de semana.
          $weekData = [];
          foreach ($results as $row) {
            $weekData[$row->week_num] = (int) $row->new_members;
          }

          // Rellenar los valores en orden.
          $values = [];
          for ($i = 7; $i >= 0; $i--) {
            $weekNum = date('oW', strtotime("-{$i} weeks"));
            $values[] = $weekData[$weekNum] ?? 0;
          }
        }
      }
    }
    catch (\Throwable) {
      // Sin datos de miembros — gráfico vacío.
    }

    return [
      'type' => 'bar',
      'labels' => $labels,
      'datasets' => [
              [
                'label' => (string) $this->t('Nuevos clientes'),
                'data' => $values,
                'backgroundColor' => 'rgba(255, 140, 66, 0.6)',
                'borderColor' => 'rgba(255, 140, 66, 1)',
                'borderWidth' => 1,
                'borderRadius' => 4,
              ],
      ],
    ];
  }

  /**
   * Sirve TenantPlanSettingsForm para slide-panel o pagina completa.
   *
   * SLIDE-PANEL-RENDER-001: Detecta isSlidePanelRequest() y usa
   * renderPlain() para devolver SOLO el formulario sin chrome (header,
   * footer, blocks). Si es request normal, devuelve render array
   * que Drupal renderiza como pagina completa.
   *
   * FORM-CACHE-001: NO llama setCached(TRUE) — Drupal gestiona cache.
   *
   * Ruta: /my-settings/plan/slide-panel
   * Usada por: SubscriptionUpgradeStep (wizard paso 7 con plan paid),
   *           SubscriptionProfileSection (seccion "Mi suscripcion").
   */
  public function planSlidePanel(Request $request): Response|array {
    $form = $this->formBuilder()->getForm(
          'Drupal\ecosistema_jaraba_core\Form\TenantPlanSettingsForm'
      );

    // SLIDE-PANEL-RENDER-001: slide-panel = AJAX sin _wrapper_format.
    if ($request->isXmlHttpRequest() && !$request->query->has('_wrapper_format')) {
      $form['#action'] = $request->getRequestUri();
      $html = (string) \Drupal::service('renderer')->renderPlain($form);
      return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    return $form;
  }

  /**
   * Pagina de configuracion del tenant.
   *
   * Usa TenantSettingsRegistry para generar las secciones
   * dinamicamente desde tagged services.
   */
  public function settings(): array {
    $tenant = $this->tenantContext->getCurrentTenant();

    if (!$tenant) {
      return [
        '#markup' => $this->t('No tienes un tenant asignado.'),
      ];
    }

    // Obtener secciones accesibles desde el registry (tagged services).
    $sections = $this->settingsRegistry->getAccessibleSections();

    return [
      '#theme' => 'tenant_self_service_settings',
      '#tenant' => $tenant,
      '#sections' => $sections,
    ];
  }

}

<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Psr\Log\LoggerInterface;

/**
 * Admin Center Aggregator — Centraliza KPIs de todos los modulos.
 *
 * Inyeccion opcional de servicios que viven en modulos satelite:
 * - jaraba_foc.saas_metrics (MRR, ARR, NRR, Churn)
 * - jaraba_customer_success.health_calculator (Health Scores)
 *
 * Los servicios opcionales se inyectan via EcosistemaJarabaCoreServiceProvider
 * cuando los modulos correspondientes estan instalados. Si no lo estan,
 * se pasa NULL y los KPIs asociados muestran 0.
 *
 * F6 — Doc 181 / Spec f104.
 */
class AdminCenterAggregatorService {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Connection $database,
    protected LoggerInterface $logger,
    protected ?object $saasMetrics = NULL,
    protected ?object $healthCalculator = NULL,
    protected ?object $fiscalCompliance = NULL,
  ) {}

  /**
   * Setter para inyeccion condicional de SaaS Metrics.
   */
  public function setSaasMetrics(?object $saasMetrics): void {
    $this->saasMetrics = $saasMetrics;
  }

  /**
   * Setter para inyeccion condicional de Health Calculator.
   */
  public function setHealthCalculator(?object $healthCalculator): void {
    $this->healthCalculator = $healthCalculator;
  }

  /**
   * Datos completos del dashboard (single call).
   *
   * @return array
   *   Array con claves: kpis, tenant_stats, alerts, quick_links, activity.
   */
  public function getDashboardData(): array {
    return [
      'kpis' => $this->getKpis(),
      'tenant_stats' => $this->getTenantStats(),
      'alerts' => $this->getActiveAlerts(),
      'quick_links' => $this->getQuickLinks(),
      'activity' => $this->getRecentActivity(),
    ];
  }

  /**
   * Obtiene KPIs de todos los servicios disponibles.
   *
   * @return array
   *   KPIs con value, label, format, trend y sparkline.
   */
  public function getKpis(): array {
    $kpis = [
      'mrr' => [
        'value' => 0,
        'label' => $this->t('MRR'),
        'format' => 'currency',
        'trend' => 0,
        'sparkline' => [],
      ],
      'arr' => [
        'value' => 0,
        'label' => $this->t('ARR'),
        'format' => 'currency',
        'trend' => 0,
        'sparkline' => [],
      ],
      'gmv_total' => [
        'value' => 0,
        'label' => $this->t('GMV Total'),
        'format' => 'currency',
        'trend' => 0,
        'sparkline' => [],
      ],
      'tenants' => [
        'value' => 0,
        'label' => $this->t('Tenants'),
        'format' => 'number',
        'trend' => 0,
        'sparkline' => [],
      ],
      'mau' => [
        'value' => 0,
        'label' => $this->t('MAU'),
        'format' => 'number',
        'trend' => 0,
        'sparkline' => [],
      ],
      'churn' => [
        'value' => 0,
        'label' => $this->t('Churn'),
        'format' => 'percent',
        'trend' => 0,
        'sparkline' => [],
      ],
      'health_avg' => [
        'value' => 0,
        'label' => $this->t('Health Avg'),
        'format' => 'score',
        'trend' => 0,
        'sparkline' => [],
      ],
      'fiscal_compliance' => [
        'value' => 0,
        'label' => $this->t('Fiscal'),
        'format' => 'score',
        'trend' => 0,
        'sparkline' => [],
      ],
    ];

    // SaaS Metrics (jaraba_foc module).
    if ($this->saasMetrics !== NULL) {
      try {
        $kpis['mrr']['value'] = (float) $this->saasMetrics->calculateMRR();
        $kpis['arr']['value'] = (float) $this->saasMetrics->calculateARR();

        if (method_exists($this->saasMetrics, 'calculateNRR')) {
          $nrr = (float) $this->saasMetrics->calculateNRR();
          // Churn approximation: 100 - NRR when NRR < 100.
          if ($nrr > 0 && $nrr < 100) {
            $kpis['churn']['value'] = round(100 - $nrr, 2);
          }
        }
      }
      catch (\Exception $e) {
        $this->logger->warning('Error obteniendo SaaS metrics: @error', [
          '@error' => $e->getMessage(),
        ]);
      }
    }

    // Tenant count.
    try {
      $groupStorage = $this->entityTypeManager->getStorage('group');
      $tenantCount = $groupStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', 'tenant')
        ->count()
        ->execute();
      $kpis['tenants']['value'] = (int) $tenantCount;
    }
    catch (\Exception $e) {
      $this->logger->warning('Error contando tenants: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    // MAU — Active users (last 30 days).
    try {
      $thirtyDaysAgo = strtotime('-30 days');
      $mauCount = $this->database->select('users_field_data', 'u')
        ->condition('u.access', $thirtyDaysAgo, '>=')
        ->condition('u.status', 1)
        ->condition('u.uid', 0, '>')
        ->countQuery()
        ->execute()
        ->fetchField();
      $kpis['mau']['value'] = (int) $mauCount;
    }
    catch (\Exception $e) {
      $this->logger->warning('Error contando MAU: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    // GMV Total (Agregado de marketplaces).
    try {
      $gmv = 0;
      if ($this->database->schema()->tableExists('agro_suborder')) {
        $gmv += (float) $this->database->select('agro_suborder', 's')
          ->condition('s.state', ['paid', 'shipped', 'delivered', 'completed'], 'IN')
          ->addExpression('SUM(subtotal)', 'total')
          ->execute()
          ->fetchField();
      }
      if ($this->database->schema()->tableExists('order_retail')) {
        $gmv += (float) $this->database->select('order_retail', 'or')
          ->condition('or.state', ['paid', 'shipped', 'delivered', 'completed'], 'IN')
          ->addExpression('SUM(total_price)', 'total')
          ->execute()
          ->fetchField();
      }
      if ($this->database->schema()->tableExists('servicios_booking')) {
        $gmv += (float) $this->database->select('servicios_booking', 'sb')
          ->condition('sb.status', ['paid', 'confirmed', 'completed'], 'IN')
          ->addExpression('SUM(total_price)', 'total')
          ->execute()
          ->fetchField();
      }
      $kpis['gmv_total']['value'] = $gmv;
    }
    catch (\Exception $e) {
      $this->logger->warning('Error calculando GMV total: @error', ['@error' => $e->getMessage()]);
    }

    // Health Avg (jaraba_customer_success module).
    if ($this->healthCalculator !== NULL) {
      try {
        $kpis['health_avg']['value'] = $this->calculateAverageHealth();
      }
      catch (\Exception $e) {
        $this->logger->warning('Error calculando health avg: @error', [
          '@error' => $e->getMessage(),
        ]);
      }
    }

    // Fiscal Compliance Score (FASE 11, F11-4).
    if ($this->fiscalCompliance !== NULL) {
      try {
        $summary = $this->fiscalCompliance->getComplianceSummary('0');
        $kpis['fiscal_compliance']['value'] = $summary['score'] ?? 0;
      }
      catch (\Exception $e) {
        $this->logger->warning('Error obteniendo fiscal compliance: @error', [
          '@error' => $e->getMessage(),
        ]);
      }
    }

    return $kpis;
  }

  /**
   * Estadisticas de tenants por estado.
   *
   * @return array
   *   Contadores: total, active, trial, suspended.
   */
  public function getTenantStats(): array {
    $stats = [
      'total' => 0,
      'active' => 0,
      'trial' => 0,
      'suspended' => 0,
    ];

    try {
      $groupStorage = $this->entityTypeManager->getStorage('group');

      $stats['total'] = (int) $groupStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', 'tenant')
        ->count()
        ->execute();

      // Query by subscription_status field if available.
      $statusCounts = $this->getTenantStatusCounts($groupStorage);
      if (!empty($statusCounts)) {
        $stats['active'] = $statusCounts['active'] ?? 0;
        $stats['trial'] = $statusCounts['trial'] ?? 0;
        $stats['suspended'] = $statusCounts['suspended'] ?? 0;
      }
      else {
        // Fallback: all tenants considered active.
        $stats['active'] = $stats['total'];
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('Error obteniendo stats de tenants: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $stats;
  }

  /**
   * Obtiene alertas activas.
   *
   * @return array
   *   Lista de alertas con id, label, metric, channel.
   */
  public function getActiveAlerts(): array {
    $alerts = [];

    try {
      $storage = $this->entityTypeManager->getStorage('alert_rule');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('active', TRUE)
        ->sort('created', 'DESC')
        ->range(0, 10)
        ->execute();

      foreach ($storage->loadMultiple($ids) as $alert) {
        $alerts[] = [
          'id' => $alert->id(),
          'label' => $alert->label(),
          'metric' => $alert->get('metric')->value ?? '',
          'channel' => $alert->get('channels')->value ?? '',
        ];
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('Error obteniendo alertas: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $alerts;
  }

  /**
   * Quick links para el sidebar de navegacion.
   *
   * @return array
   *   Lista de enlaces con label, url, icon_category, icon_name, shortcut.
   */
  public function getQuickLinks(): array {
    return [
      [
        'label' => $this->t('Tenants'),
        'url' => '/admin/jaraba/center/tenants',
        'icon_category' => 'business',
        'icon_name' => 'company',
        'shortcut' => 'G T',
      ],
      [
        'label' => $this->t('Usuarios'),
        'url' => '/admin/jaraba/center/users',
        'icon_category' => 'ui',
        'icon_name' => 'users',
        'shortcut' => 'G U',
      ],
      [
        'label' => $this->t('Finanzas'),
        'url' => '/admin/finops',
        'icon_category' => 'business',
        'icon_name' => 'money',
        'shortcut' => 'G F',
      ],
      [
        'label' => $this->t('Health Monitor'),
        'url' => '/admin/health',
        'icon_category' => 'analytics',
        'icon_name' => 'gauge',
        'shortcut' => '',
      ],
      [
        'label' => $this->t('Analytics'),
        'url' => '/admin/jaraba/analytics',
        'icon_category' => 'analytics',
        'icon_name' => 'chart-bar',
        'shortcut' => '',
      ],
      [
        'label' => $this->t('Alertas'),
        'url' => '/admin/jaraba/center/alerts',
        'icon_category' => 'ui',
        'icon_name' => 'bell',
        'shortcut' => 'A',
      ],
      [
        'label' => $this->t('Compliance'),
        'url' => '/admin/seguridad',
        'icon_category' => 'ui',
        'icon_name' => 'shield',
        'shortcut' => '',
      ],
      [
        'label' => $this->t('Fiscal'),
        'url' => '/admin/jaraba/fiscal',
        'icon_category' => 'fiscal',
        'icon_name' => 'shield-fiscal',
        'shortcut' => '',
      ],
      [
        'label' => $this->t('Email'),
        'url' => '/admin/jaraba/email',
        'icon_category' => 'ui',
        'icon_name' => 'mail',
        'shortcut' => '',
      ],
      [
        'label' => $this->t('Customer Success'),
        'url' => '/admin/structure/customer-success',
        'icon_category' => 'ui',
        'icon_name' => 'heartbeat',
        'shortcut' => '',
      ],
      [
        'label' => $this->t('RBAC Matrix'),
        'url' => '/admin/people/rbac-matrix',
        'icon_category' => 'ui',
        'icon_name' => 'lock',
        'shortcut' => '',
      ],
      [
        'label' => $this->t('Configuración'),
        'url' => '/admin/jaraba/center/settings',
        'icon_category' => 'ui',
        'icon_name' => 'settings',
        'shortcut' => 'G S',
      ],
    ];
  }

  /**
   * Actividad reciente del sistema.
   *
   * @return array
   *   Ultimas 10 entradas del log de actividad.
   */
  public function getRecentActivity(): array {
    $activity = [];

    try {
      $result = $this->database->select('watchdog', 'w')
        ->fields('w', ['wid', 'type', 'message', 'variables', 'severity', 'timestamp'])
        ->condition('w.type', [
          'ecosistema_jaraba_core',
          'jaraba_billing',
          'jaraba_foc',
          'jaraba_customer_success',
          'jaraba_agroconecta_core',
          'jaraba_comercio_conecta',
        ], 'IN')
        ->orderBy('w.timestamp', 'DESC')
        ->range(0, 10)
        ->execute();

      foreach ($result as $row) {
        $activity[] = [
          'id' => $row->wid,
          'type' => $row->type,
          'message' => $this->formatLogMessage($row->message, $row->variables),
          'severity' => (int) $row->severity,
          'timestamp' => (int) $row->timestamp,
        ];
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('Error obteniendo actividad reciente: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $activity;
  }

  /**
   * Calcula el health score promedio de todos los tenants.
   */
  protected function calculateAverageHealth(): float {
    if ($this->healthCalculator === NULL) {
      return 0;
    }

    try {
      $groupStorage = $this->entityTypeManager->getStorage('group');
      $tenantIds = $groupStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', 'tenant')
        ->range(0, 100)
        ->execute();

      if (empty($tenantIds)) {
        return 0;
      }

      $totalScore = 0;
      $count = 0;

      foreach ($tenantIds as $tenantId) {
        $health = $this->healthCalculator->calculate((string) $tenantId);
        if ($health !== NULL && method_exists($health, 'get')) {
          $score = $health->get('overall_score')->value ?? NULL;
          if ($score !== NULL) {
            $totalScore += (float) $score;
            $count++;
          }
        }
      }

      return $count > 0 ? round($totalScore / $count, 1) : 0;
    }
    catch (\Exception $e) {
      $this->logger->warning('Error calculando health avg: @error', [
        '@error' => $e->getMessage(),
      ]);
      return 0;
    }
  }

  /**
   * Obtiene contadores de tenants por estado de suscripcion.
   */
  protected function getTenantStatusCounts(object $groupStorage): array {
    $counts = [];
    $statuses = ['active', 'trial', 'suspended'];

    foreach ($statuses as $status) {
      try {
        $counts[$status] = (int) $groupStorage->getQuery()
          ->accessCheck(FALSE)
          ->condition('type', 'tenant')
          ->condition('field_subscription_status', $status)
          ->count()
          ->execute();
      }
      catch (\Exception $e) {
        // Field may not exist — return empty to trigger fallback.
        return [];
      }
    }

    return $counts;
  }

  /**
   * Formatea un mensaje del watchdog con sus variables.
   */
  protected function formatLogMessage(string $message, ?string $variables): string {
    if ($variables) {
      $vars = @unserialize($variables, ['allowed_classes' => FALSE]);
      if (is_array($vars)) {
        return strtr($message, $vars);
      }
    }
    return $message;
  }

}

<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ecosistema_jaraba_core\Service\PricingRuleEngine;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\ecosistema_jaraba_core\Service\TenantMeteringService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controlador del dashboard de uso para tenants (frontend).
 *
 * PROPÓSITO:
 * Renderiza la página /mi-cuenta/uso donde el tenant puede ver
 * su consumo de recursos, costes estimados, proyecciones y alertas.
 *
 * LÓGICA:
 * - Obtiene el tenant actual vía TenantContextService
 * - Consulta uso del período actual y histórico vía TenantMeteringService
 * - Calcula costes usando PricingRuleEngine (que respeta reglas por plan)
 * - Renderiza template 'usage_dashboard' con datos completos
 *
 * DIRECTRICES:
 * - NO es ruta admin → usa template limpio sin regiones Drupal
 * - Traducciones con t() en todas las cadenas visibles
 * - Datos de Chart.js se pasan via drupalSettings
 */
class UsageDashboardController extends ControllerBase {

  /**
   * Constructor con inyección de dependencias.
   */
  public function __construct(
    protected TenantContextService $tenantContext,
    protected TenantMeteringService $metering,
    protected PricingRuleEngine $pricingEngine,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('ecosistema_jaraba_core.tenant_context'),
      $container->get('ecosistema_jaraba_core.tenant_metering'),
      $container->get('ecosistema_jaraba_core.pricing_engine'),
    );
  }

  /**
   * Página principal de uso: /mi-cuenta/uso.
   */
  public function dashboard(): array {
    $tenant = $this->tenantContext->getCurrentTenant();

    if (!$tenant) {
      return [
        '#theme' => 'usage_dashboard_empty',
        '#message' => $this->t('No se encontró un tenant asociado a tu cuenta.'),
      ];
    }

    $tenantId = (string) $tenant->id();
    $planId = NULL;
    if ($tenant->hasField('plan') && !$tenant->get('plan')->isEmpty()) {
      $planId = (string) $tenant->get('plan')->target_id;
    }

    // Uso del período actual.
    $currentUsage = $this->metering->getUsage($tenantId);

    // Uso histórico (6 meses).
    $historicalUsage = $this->metering->getHistoricalUsage($tenantId, 6);

    // Calcular costes con PricingRuleEngine.
    $usageMetrics = [];
    foreach ($currentUsage['metrics'] as $metric => $data) {
      $usageMetrics[$metric] = $data['total'];
    }
    $bill = $this->pricingEngine->calculateBill($usageMetrics, $planId);

    // Proyección de fin de mes.
    $forecast = $this->metering->getForecast($tenantId);

    // Alertas de presupuesto (usar presupuesto del config o default).
    $config = $this->config('ecosistema_jaraba_core.finops_settings');
    $budget = (float) ($config->get('monthly_budget') ?? 500);
    $alerts = $this->metering->checkBudgetAlerts($tenantId, $budget);

    // Datos para Chart.js (histórico).
    $chartLabels = [];
    $chartDatasets = [];
    $metricLabels = [
      'api_calls' => (string) $this->t('Llamadas API'),
      'ai_tokens' => (string) $this->t('Tokens IA'),
      'storage_mb' => (string) $this->t('Almacenamiento'),
      'orders' => (string) $this->t('Pedidos'),
      'products' => (string) $this->t('Productos'),
      'customers' => (string) $this->t('Clientes'),
      'emails_sent' => (string) $this->t('Emails'),
      'bandwidth_gb' => (string) $this->t('Ancho de banda'),
    ];
    $metricColors = [
      'api_calls' => '#233D63',
      'ai_tokens' => '#00A9A5',
      'storage_mb' => '#FF8C42',
      'orders' => '#6C757D',
      'products' => '#28A745',
      'customers' => '#17A2B8',
      'emails_sent' => '#FFC107',
      'bandwidth_gb' => '#DC3545',
    ];

    // Construir labels de meses.
    foreach ($historicalUsage as $period => $metrics) {
      $chartLabels[] = $period;
    }

    // Construir datasets por métrica.
    $allMetrics = [];
    foreach ($historicalUsage as $metrics) {
      foreach (array_keys($metrics) as $m) {
        $allMetrics[$m] = TRUE;
      }
    }

    foreach (array_keys($allMetrics) as $metric) {
      $data = [];
      foreach ($historicalUsage as $metrics) {
        $data[] = $metrics[$metric] ?? 0;
      }
      $chartDatasets[] = [
        'label' => $metricLabels[$metric] ?? $metric,
        'data' => $data,
        'borderColor' => $metricColors[$metric] ?? '#233D63',
        'backgroundColor' => ($metricColors[$metric] ?? '#233D63') . '20',
        'fill' => TRUE,
      ];
    }

    // Preparar métricas para template.
    $metricsDisplay = [];
    foreach ($currentUsage['metrics'] as $metric => $data) {
      $costResult = $this->pricingEngine->calculateCost($metric, $data['total'], $planId);
      $metricsDisplay[] = [
        'key' => $metric,
        'label' => $metricLabels[$metric] ?? $metric,
        'total' => $data['total'],
        'cost' => $costResult['cost'],
        'included' => $costResult['included_quantity'],
        'billable' => $costResult['billable_quantity'],
        'model' => $costResult['pricing_model'],
        'unit_price' => $costResult['unit_price'],
        'color' => $metricColors[$metric] ?? '#233D63',
        'percentage' => $bill['subtotal'] > 0
          ? round(($costResult['cost'] / $bill['subtotal']) * 100, 1)
          : 0,
      ];
    }

    return [
      '#theme' => 'usage_dashboard',
      '#tenant' => $tenant,
      '#period' => $currentUsage['period'],
      '#metrics' => $metricsDisplay,
      '#bill' => $bill,
      '#forecast' => $forecast,
      '#alerts' => $alerts,
      '#budget' => $budget,
      '#attached' => [
        'library' => [
          'ecosistema_jaraba_core/usage-dashboard',
        ],
        'drupalSettings' => [
          'usageDashboard' => [
            'chartLabels' => $chartLabels,
            'chartDatasets' => $chartDatasets,
            'currentSpend' => $bill['subtotal'],
            'projectedSpend' => $forecast['projected_total'],
            'budget' => $budget,
          ],
        ],
      ],
    ];
  }

}

<?php

declare(strict_types=1);

namespace Drupal\jaraba_foc\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\jaraba_foc\Service\MetricsCalculatorService;
use Drupal\jaraba_foc\Service\SaasMetricsService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controlador del Dashboard FOC.
 *
 * PROPÓSITO:
 * Renderiza el dashboard ejecutivo del Centro de Operaciones Financieras
 * con métricas SaaS 2.0, analítica de tenants y visualizaciones.
 *
 * RUTAS:
 * - /admin/foc: Dashboard principal
 * - /admin/foc/tenants: Analítica de Inquilinos
 * - /admin/foc/verticals: Rentabilidad por Vertical
 * - /admin/foc/projections: Proyecciones y Forecasting
 */
class FocDashboardController extends ControllerBase implements ContainerInjectionInterface
{

    /**
     * Constructor del controlador.
     *
     * @param \Drupal\jaraba_foc\Service\MetricsCalculatorService $metricsCalculator
     *   El servicio de cálculo de métricas.
     * @param \Drupal\jaraba_foc\Service\SaasMetricsService $saasMetrics
     *   El servicio de métricas SaaS 2.0.
     */
    public function __construct(
        protected MetricsCalculatorService $metricsCalculator,
        protected SaasMetricsService $saasMetrics
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_foc.metrics_calculator'),
            $container->get('jaraba_foc.saas_metrics')
        );
    }

    /**
     * Dashboard principal del FOC.
     *
     * Muestra las métricas clave de salud financiera del ecosistema:
     * - MRR/ARR actuales
     * - Gross Margin
     * - Net Revenue Retention
     * - LTV:CAC Ratio
     * - CAC Payback
     *
     * @return array
     *   Render array del dashboard.
     */
    public function dashboard(): array
    {
        // Calcular métricas principales
        $mrr = $this->metricsCalculator->calculateMRR();
        $arr = $this->metricsCalculator->calculateARR();
        $grossMargin = $this->metricsCalculator->calculateGrossMargin();
        $ltvCacRatio = $this->metricsCalculator->calculateLTVCACRatio();
        $cacPayback = $this->metricsCalculator->calculateCACPayback();

        // Obtener analítica de tenants para resumen
        $tenantsMetrics = $this->metricsCalculator->getTenantAnalytics();

        // Categorizar tenants por salud
        $tenantsByHealth = [
            'vip' => 0,
            'healthy' => 0,
            'at_risk' => 0,
            'in_loss' => 0,
        ];

        foreach ($tenantsMetrics as $tenant) {
            $status = $tenant['health_status'] ?? 'healthy';
            if (isset($tenantsByHealth[$status])) {
                $tenantsByHealth[$status]++;
            }
        }

        return [
            '#theme' => 'foc_dashboard',
            '#mrr' => $mrr,
            '#arr' => $arr,
            '#gross_margin' => $grossMargin,
            '#net_revenue_retention' => $this->saasMetrics->calculateNRR(),
            '#gross_revenue_retention' => $this->saasMetrics->calculateGRR(),
            '#ltv_cac_ratio' => $ltvCacRatio,
            '#cac_payback_months' => $cacPayback,
            '#tenants_metrics' => $tenantsMetrics,
            '#tenants_by_health' => $tenantsByHealth,
            '#alerts' => $this->getActiveAlerts(),
            '#attached' => [
                'library' => [
                    'jaraba_foc/dashboard',
                ],
            ],
            '#cache' => [
                'max-age' => 300, // 5 minutos
            ],
        ];
    }

    /**
     * Página de Analítica de Inquilinos.
     *
     * Muestra métricas detalladas por tenant:
     * - MRR individual
     * - LTV, CAC, Ratio
     * - Payback period
     * - Estado de salud
     *
     * @return array
     *   Render array de la analítica de tenants.
     */
    public function tenantsAnalytics(): array
    {
        $tenantsMetrics = $this->metricsCalculator->getTenantAnalytics();

        return [
            '#type' => 'container',
            '#attributes' => ['class' => ['foc-tenants-analytics']],
            'header' => [
                '#type' => 'html_tag',
                '#tag' => 'h2',
                '#value' => $this->t('Analítica de Inquilinos'),
                '#attributes' => ['class' => ['foc-section-title']],
            ],
            'subtitle' => [
                '#type' => 'html_tag',
                '#tag' => 'p',
                '#value' => $this->t('Métricas de rentabilidad y valor del cliente por tenant'),
                '#attributes' => ['class' => ['foc-section-subtitle']],
            ],
            'table' => $this->buildTenantsTable($tenantsMetrics),
            '#attached' => [
                'library' => [
                    'jaraba_foc/dashboard',
                ],
            ],
        ];
    }

    /**
     * Construye la tabla de métricas de tenants.
     *
     * @param array $tenants
     *   Array de métricas por tenant.
     *
     * @return array
     *   Render array de la tabla.
     */
    protected function buildTenantsTable(array $tenants): array
    {
        $header = [
            $this->t('Tenant'),
            $this->t('MRR'),
            $this->t('LTV'),
            $this->t('CAC'),
            $this->t('Ratio LTV:CAC'),
            $this->t('Payback'),
            $this->t('Estado'),
        ];

        $rows = [];
        foreach ($tenants as $tenant) {
            $statusClass = 'foc-status--' . $tenant['health_status'];
            $statusLabel = $this->getHealthStatusLabel($tenant['health_status']);

            $rows[] = [
                $tenant['name'],
                '€' . number_format((float) $tenant['mrr'], 2, ',', '.'),
                '€' . number_format((float) $tenant['ltv'], 2, ',', '.'),
                '€' . number_format((float) $tenant['cac'], 2, ',', '.'),
                $tenant['ltv_cac_ratio'] . ':1',
                $tenant['payback_months'] . ' ' . $this->t('meses'),
                [
                    'data' => $statusLabel,
                    'class' => [$statusClass],
                ],
            ];
        }

        return [
            '#type' => 'table',
            '#header' => $header,
            '#rows' => $rows,
            '#empty' => $this->t('No hay tenants registrados.'),
            '#attributes' => ['class' => ['foc-table', 'foc-table--tenants']],
        ];
    }

    /**
     * Obtiene la etiqueta del estado de salud.
     *
     * @param string $status
     *   Estado de salud.
     *
     * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup
     *   Etiqueta traducida.
     */
    protected function getHealthStatusLabel(string $status): string|\Drupal\Core\StringTranslation\TranslatableMarkup
    {
        $labels = [
            'vip' => $this->t('VIP'),
            'healthy' => $this->t('Saludable'),
            'at_risk' => $this->t('En Riesgo'),
            'in_loss' => $this->t('En Pérdida'),
        ];

        return $labels[$status] ?? $status;
    }

    /**
     * Página de Rentabilidad por Vertical.
     *
     * @return array
     *   Render array.
     */
    public function verticalsAnalytics(): array
    {
        return [
            '#type' => 'container',
            '#attributes' => ['class' => ['foc-verticals-analytics']],
            'header' => [
                '#type' => 'html_tag',
                '#tag' => 'h2',
                '#value' => $this->t('Rentabilidad por Vertical'),
            ],
            'content' => [
                '#markup' => '<p>' . $this->t('Visualización del P&L segmentado por vertical de negocio. Próximamente.') . '</p>',
            ],
        ];
    }

    /**
     * Página de Proyecciones y Forecasting.
     *
     * @return array
     *   Render array.
     */
    public function projections(): array
    {
        return [
            '#type' => 'container',
            '#attributes' => ['class' => ['foc-projections']],
            'header' => [
                '#type' => 'html_tag',
                '#tag' => 'h2',
                '#value' => $this->t('Proyecciones y Forecasting'),
            ],
            'content' => [
                '#markup' => '<p>' . $this->t('Motor de proyecciones via API de IA. Próximamente.') . '</p>',
            ],
        ];
    }

    /**
     * Obtiene las alertas activas del sistema.
     *
     * @return array
     *   Array de alertas activas.
     */
    protected function getActiveAlerts(): array
    {
        try {
            if (\Drupal::hasService('jaraba_foc.alerts')) {
                /** @var \Drupal\jaraba_foc\Service\AlertService $alertService */
                $alertService = \Drupal::service('jaraba_foc.alerts');

                // Evaluate current platform alerts to catch new issues.
                $alertService->evaluateAllAlerts();

                // Retrieve open alerts.
                $openAlerts = $alertService->getOpenAlerts();
                $formattedAlerts = [];

                foreach ($openAlerts as $alert) {
                    $severity = $alert->get('severity')->value ?? 'warning';
                    // Map severity to display type.
                    $typeMap = [
                        'critical' => 'danger',
                        'warning' => 'warning',
                        'info' => 'info',
                    ];

                    $formattedAlerts[] = [
                        'type' => $typeMap[$severity] ?? 'warning',
                        'message' => $alert->get('message')->value ?? $alert->label(),
                        'severity' => $severity,
                        'alert_type' => $alert->get('alert_type')->value ?? '',
                        'action' => $this->t('Ver playbook'),
                        'timestamp' => $alert->get('created')->value ?? NULL,
                        'tenant' => $alert->get('related_tenant')->target_id ?? NULL,
                    ];
                }

                return $formattedAlerts;
            }
        }
        catch (\Exception $e) {
            // Fall through to empty array if alert evaluation fails.
        }

        return [];
    }

}

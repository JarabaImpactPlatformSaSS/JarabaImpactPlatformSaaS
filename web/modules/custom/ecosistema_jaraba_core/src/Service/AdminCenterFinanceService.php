<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Psr\Log\LoggerInterface;

/**
 * Admin Center Finance Aggregator — Centraliza metricas financieras.
 *
 * Inyeccion opcional de servicios FOC:
 * - jaraba_foc.saas_metrics (MRR, ARR, NRR, GRR, Churn, LTV, CAC)
 * - jaraba_foc.metrics_calculator (Tenant-level analytics)
 *
 * F6 — Doc 181 / Spec f104 §FASE 4.
 */
class AdminCenterFinanceService {

  use StringTranslationTrait;

  /**
   * SaaS benchmark thresholds.
   */
  protected const BENCHMARKS = [
    'grr' => ['good' => 90, 'warning' => 80, 'unit' => '%', 'direction' => 'higher'],
    'nrr' => ['good' => 105, 'warning' => 95, 'unit' => '%', 'direction' => 'higher'],
    'logo_churn' => ['good' => 5, 'warning' => 10, 'unit' => '%', 'direction' => 'lower'],
    'revenue_churn' => ['good' => 3, 'warning' => 7, 'unit' => '%', 'direction' => 'lower'],
    'ltv_cac_ratio' => ['good' => 3, 'warning' => 1.5, 'unit' => 'x', 'direction' => 'higher'],
    'cac_payback' => ['good' => 12, 'warning' => 18, 'unit' => 'mo', 'direction' => 'lower'],
    'gross_margin' => ['good' => 70, 'warning' => 60, 'unit' => '%', 'direction' => 'higher'],
  ];

  public function __construct(
    protected LoggerInterface $logger,
    protected ?object $saasMetrics = NULL,
    protected ?object $metricsCalculator = NULL,
  ) {}

  /**
   * Setter para inyeccion condicional de SaaS Metrics.
   */
  public function setSaasMetrics(?object $saasMetrics): void {
    $this->saasMetrics = $saasMetrics;
  }

  /**
   * Setter para inyeccion condicional de Metrics Calculator.
   */
  public function setMetricsCalculator(?object $metricsCalculator): void {
    $this->metricsCalculator = $metricsCalculator;
  }

  /**
   * Datos completos del dashboard financiero.
   *
   * @return array
   *   Array con claves: scorecards, metrics_table, tenant_analytics.
   */
  public function getFinanceDashboardData(): array {
    return [
      'scorecards' => $this->getScorecards(),
      'metrics_table' => $this->getMetricsTable(),
      'tenant_analytics' => $this->getTenantAnalytics(),
    ];
  }

  /**
   * Scorecards principales: MRR, ARR, con trend.
   */
  public function getScorecards(): array {
    $cards = [
      'mrr' => [
        'label' => $this->t('MRR'),
        'value' => 0,
        'format' => 'currency',
        'trend' => 0,
      ],
      'arr' => [
        'label' => $this->t('ARR'),
        'value' => 0,
        'format' => 'currency',
        'trend' => 0,
      ],
      'active_customers' => [
        'label' => $this->t('Clientes Activos'),
        'value' => 0,
        'format' => 'number',
        'trend' => 0,
      ],
      'arpu' => [
        'label' => $this->t('ARPU'),
        'value' => 0,
        'format' => 'currency',
        'trend' => 0,
      ],
    ];

    if ($this->saasMetrics !== NULL) {
      try {
        $snapshot = method_exists($this->saasMetrics, 'getMetricsSnapshot')
          ? $this->saasMetrics->getMetricsSnapshot()
          : [];

        if (!empty($snapshot)) {
          $cards['mrr']['value'] = (float) ($snapshot['mrr'] ?? 0);
          $cards['arr']['value'] = (float) ($snapshot['arr'] ?? 0);
          $cards['active_customers']['value'] = (int) ($snapshot['active_customers'] ?? 0);
          $cards['arpu']['value'] = (float) ($snapshot['arpu'] ?? 0);
        }
        else {
          $cards['mrr']['value'] = (float) $this->saasMetrics->calculateMRR();
          $cards['arr']['value'] = (float) $this->saasMetrics->calculateARR();
          $cards['arpu']['value'] = (float) $this->saasMetrics->calculateARPU();
        }
      }
      catch (\Exception $e) {
        $this->logger->warning('Error obteniendo scorecards: @error', [
          '@error' => $e->getMessage(),
        ]);
      }
    }

    return $cards;
  }

  /**
   * Tabla de metricas SaaS con benchmarks.
   *
   * @return array
   *   Lista de metricas con value, benchmark, health.
   */
  public function getMetricsTable(): array {
    $metrics = [];

    $metricDefs = [
      'grr' => ['label' => $this->t('GRR'), 'method' => 'calculateGRR'],
      'nrr' => ['label' => $this->t('NRR'), 'method' => 'calculateNRR'],
      'logo_churn' => ['label' => $this->t('Logo Churn'), 'method' => 'calculateLogoChurn'],
      'ltv_cac_ratio' => ['label' => $this->t('LTV:CAC'), 'method' => 'calculateLTVCACRatio'],
      'gross_margin' => ['label' => $this->t('Gross Margin'), 'method' => 'calculateGrossMargin'],
    ];

    foreach ($metricDefs as $key => $def) {
      $value = 0;
      if ($this->saasMetrics !== NULL && method_exists($this->saasMetrics, $def['method'])) {
        try {
          $value = (float) $this->saasMetrics->{$def['method']}();
        }
        catch (\Exception $e) {
          // Value stays 0.
        }
      }

      $benchmark = self::BENCHMARKS[$key] ?? NULL;
      $health = $this->calculateHealth($value, $benchmark);

      $metrics[] = [
        'key' => $key,
        'label' => $def['label'],
        'value' => $value,
        'unit' => $benchmark['unit'] ?? '',
        'benchmark_good' => $benchmark['good'] ?? 0,
        'benchmark_warning' => $benchmark['warning'] ?? 0,
        'health' => $health,
      ];
    }

    // Additional metrics from calculator.
    if ($this->metricsCalculator !== NULL) {
      try {
        $ltv = (float) $this->metricsCalculator->calculateLTV();
        $cac = method_exists($this->metricsCalculator, 'calculateCAC')
          ? (float) $this->metricsCalculator->calculateCAC()
          : 0;
        $payback = method_exists($this->metricsCalculator, 'calculateCACPayback')
          ? (float) $this->metricsCalculator->calculateCACPayback()
          : 0;

        $metrics[] = [
          'key' => 'ltv',
          'label' => $this->t('LTV'),
          'value' => $ltv,
          'unit' => '€',
          'benchmark_good' => 0,
          'benchmark_warning' => 0,
          'health' => 'neutral',
        ];
        $metrics[] = [
          'key' => 'cac',
          'label' => $this->t('CAC'),
          'value' => $cac,
          'unit' => '€',
          'benchmark_good' => 0,
          'benchmark_warning' => 0,
          'health' => 'neutral',
        ];
        $metrics[] = [
          'key' => 'cac_payback',
          'label' => $this->t('CAC Payback'),
          'value' => $payback,
          'unit' => 'mo',
          'benchmark_good' => self::BENCHMARKS['cac_payback']['good'],
          'benchmark_warning' => self::BENCHMARKS['cac_payback']['warning'],
          'health' => $this->calculateHealth($payback, self::BENCHMARKS['cac_payback']),
        ];
      }
      catch (\Exception $e) {
        $this->logger->warning('Error obteniendo metricas calculator: @error', [
          '@error' => $e->getMessage(),
        ]);
      }
    }

    return $metrics;
  }

  /**
   * Analytics por tenant (for DataTable).
   *
   * @return array
   *   Lista de tenants con metricas financieras.
   */
  public function getTenantAnalytics(): array {
    if ($this->metricsCalculator === NULL || !method_exists($this->metricsCalculator, 'getTenantAnalytics')) {
      return [];
    }

    try {
      return $this->metricsCalculator->getTenantAnalytics();
    }
    catch (\Exception $e) {
      $this->logger->warning('Error obteniendo tenant analytics: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Calcula el estado de salud de una metrica vs benchmark.
   *
   * @return string
   *   'good', 'warning', 'danger', 'neutral'.
   */
  protected function calculateHealth(float $value, ?array $benchmark): string {
    if ($benchmark === NULL || $value === 0.0) {
      return 'neutral';
    }

    $direction = $benchmark['direction'] ?? 'higher';

    if ($direction === 'higher') {
      if ($value >= $benchmark['good']) {
        return 'good';
      }
      if ($value >= $benchmark['warning']) {
        return 'warning';
      }
      return 'danger';
    }

    // Lower is better (churn, payback).
    if ($value <= $benchmark['good']) {
      return 'good';
    }
    if ($value <= $benchmark['warning']) {
      return 'warning';
    }
    return 'danger';
  }

}

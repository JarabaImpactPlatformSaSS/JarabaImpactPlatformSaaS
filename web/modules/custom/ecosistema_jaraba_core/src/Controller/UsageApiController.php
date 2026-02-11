<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ecosistema_jaraba_core\Service\PricingRuleEngine;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\ecosistema_jaraba_core\Service\TenantMeteringService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API REST para datos de uso y facturación del tenant.
 *
 * PROPÓSITO:
 * Endpoints JSON para que el frontend de uso pueda consumir datos
 * de forma asíncrona (Chart.js, filtros, exportación).
 *
 * LÓGICA:
 * - Todos los endpoints requieren autenticación (usuario logueado)
 * - Resuelven el tenant del usuario actual automáticamente
 * - Los endpoints admin requieren permiso 'view platform analytics'
 *
 * DIRECTRICES:
 * - Respuestas JSON estándar con keys: success, data, meta
 * - Códigos HTTP: 200 (ok), 403 (sin permiso), 404 (no tenant)
 */
class UsageApiController extends ControllerBase {

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
   * GET /api/v1/usage/current — Uso del período actual.
   */
  public function currentUsage(): JsonResponse {
    $tenant = $this->tenantContext->getCurrentTenant();
    if (!$tenant) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant no encontrado'], 404);
    }

    $usage = $this->metering->getUsage((string) $tenant->id());

    return new JsonResponse([
      'success' => TRUE,
      'data' => $usage,
      'meta' => ['timestamp' => time()],
    ]);
  }

  /**
   * GET /api/v1/usage/history — Uso histórico por meses.
   */
  public function historicalUsage(Request $request): JsonResponse {
    $tenant = $this->tenantContext->getCurrentTenant();
    if (!$tenant) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant no encontrado'], 404);
    }

    $months = (int) $request->query->get('months', 6);
    $months = max(1, min(24, $months));
    $history = $this->metering->getHistoricalUsage((string) $tenant->id(), $months);

    return new JsonResponse([
      'success' => TRUE,
      'data' => $history,
      'meta' => ['months' => $months, 'timestamp' => time()],
    ]);
  }

  /**
   * GET /api/v1/usage/bill — Factura estimada del período actual.
   */
  public function currentBill(): JsonResponse {
    $tenant = $this->tenantContext->getCurrentTenant();
    if (!$tenant) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant no encontrado'], 404);
    }

    $tenantId = (string) $tenant->id();
    $planId = NULL;
    if ($tenant->hasField('plan') && !$tenant->get('plan')->isEmpty()) {
      $planId = (string) $tenant->get('plan')->target_id;
    }

    $usage = $this->metering->getUsage($tenantId);
    $usageMetrics = [];
    foreach ($usage['metrics'] as $metric => $data) {
      $usageMetrics[$metric] = $data['total'];
    }

    $bill = $this->pricingEngine->calculateBill($usageMetrics, $planId);

    return new JsonResponse([
      'success' => TRUE,
      'data' => $bill,
      'meta' => ['period' => $usage['period'], 'timestamp' => time()],
    ]);
  }

  /**
   * GET /api/v1/usage/forecast — Proyección de costes fin de mes.
   */
  public function forecast(): JsonResponse {
    $tenant = $this->tenantContext->getCurrentTenant();
    if (!$tenant) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant no encontrado'], 404);
    }

    $forecast = $this->metering->getForecast((string) $tenant->id());

    return new JsonResponse([
      'success' => TRUE,
      'data' => $forecast,
      'meta' => ['timestamp' => time()],
    ]);
  }

  /**
   * GET /api/v1/usage/alerts — Alertas de presupuesto activas.
   */
  public function budgetAlerts(): JsonResponse {
    $tenant = $this->tenantContext->getCurrentTenant();
    if (!$tenant) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant no encontrado'], 404);
    }

    $config = $this->config('ecosistema_jaraba_core.finops_settings');
    $budget = (float) ($config->get('monthly_budget') ?? 500);
    $alerts = $this->metering->checkBudgetAlerts((string) $tenant->id(), $budget);

    return new JsonResponse([
      'success' => TRUE,
      'data' => $alerts,
      'meta' => ['budget' => $budget, 'timestamp' => time()],
    ]);
  }

  /**
   * GET /api/v1/usage/metrics/{metric} — Detalle de una métrica.
   */
  public function metricDetail(string $metric): JsonResponse {
    $tenant = $this->tenantContext->getCurrentTenant();
    if (!$tenant) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant no encontrado'], 404);
    }

    $tenantId = (string) $tenant->id();
    $planId = NULL;
    if ($tenant->hasField('plan') && !$tenant->get('plan')->isEmpty()) {
      $planId = (string) $tenant->get('plan')->target_id;
    }

    $usage = $this->metering->getUsage($tenantId);
    $total = $usage['metrics'][$metric]['total'] ?? 0;

    $costResult = $this->pricingEngine->calculateCost($metric, (float) $total, $planId);

    // Histórico de esta métrica por mes.
    $history = $this->metering->getHistoricalUsage($tenantId, 6);
    $metricHistory = [];
    foreach ($history as $period => $metrics) {
      $metricHistory[$period] = $metrics[$metric] ?? 0;
    }

    return new JsonResponse([
      'success' => TRUE,
      'data' => [
        'metric' => $metric,
        'current_total' => $total,
        'pricing' => $costResult,
        'history' => $metricHistory,
      ],
      'meta' => ['period' => $usage['period'], 'timestamp' => time()],
    ]);
  }

  /**
   * GET /api/v1/usage/pricing-rules — Reglas de precios del plan actual.
   */
  public function pricingRules(): JsonResponse {
    $tenant = $this->tenantContext->getCurrentTenant();
    if (!$tenant) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant no encontrado'], 404);
    }

    $planId = NULL;
    if ($tenant->hasField('plan') && !$tenant->get('plan')->isEmpty()) {
      $planId = (string) $tenant->get('plan')->target_id;
    }

    $rules = $this->pricingEngine->getRulesForPlan($planId);
    $rulesData = [];

    foreach ($rules as $metric => $rule) {
      $rulesData[$metric] = [
        'name' => $rule->label(),
        'metric_type' => $metric,
        'pricing_model' => $rule->get('pricing_model')->value,
        'unit_price' => (float) $rule->get('unit_price')->value,
        'included_quantity' => (float) $rule->get('included_quantity')->value,
        'tiers' => $rule->getDecodedTiers(),
        'currency' => $rule->get('currency')->value ?? 'EUR',
      ];
    }

    return new JsonResponse([
      'success' => TRUE,
      'data' => $rulesData,
      'meta' => ['plan_id' => $planId, 'timestamp' => time()],
    ]);
  }

}

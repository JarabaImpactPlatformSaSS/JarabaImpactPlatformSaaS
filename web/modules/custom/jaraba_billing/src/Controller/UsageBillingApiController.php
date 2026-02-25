<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\jaraba_billing\Service\PlanValidator;
use Drupal\jaraba_billing\Service\TenantMeteringService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * REST API Controller para usage billing — spec 111 §5.
 *
 * 7 endpoints para registro de uso, consultas y estimaciones de coste.
 */
class UsageBillingApiController extends ControllerBase implements ContainerInjectionInterface {

  public function __construct(
    protected TenantMeteringService $metering,
    protected PlanValidator $planValidator,
    protected LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_billing.tenant_metering'),
      $container->get('jaraba_billing.plan_validator'),
      $container->get('logger.channel.jaraba_billing'),
    );
  }

  /**
   * Obtiene el tenant_id del usuario actual.
   */
  protected function getCurrentTenantId(): ?int {
    $user = $this->currentUser();
    if (!$user || $user->isAnonymous()) {
      return NULL;
    }
    $userEntity = $this->entityTypeManager()->getStorage('user')->load($user->id());
    if ($userEntity && $userEntity->hasField('field_tenant') && !$userEntity->get('field_tenant')->isEmpty()) {
      return (int) $userEntity->get('field_tenant')->target_id;
    }
    return NULL;
  }

  /**
   * POST /api/v1/usage/record — Registrar evento de uso.
   */
  public function recordUsage(Request $request): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    $body = json_decode($request->getContent(), TRUE) ?? [];
    $metric = $body['metric'] ?? NULL;
    $value = (float) ($body['value'] ?? 0);

    if (!$metric || $value <= 0) {
      return new JsonResponse(['success' => FALSE, 'error' => 'metric and positive value are required'], 400);
    }

    try {
      $this->metering->record((string) $tenantId, $metric, $value, $body['metadata'] ?? []);

      // Also create a BillingUsageRecord entity for Stripe sync.
      $storage = $this->entityTypeManager()->getStorage('billing_usage_record');
      $record = $storage->create([
        'tenant_id' => $tenantId,
        'metric_key' => $metric,
        'quantity' => $value,
        'unit' => $body['unit'] ?? $metric,
        'period_start' => strtotime(date('Y-m-01')),
        'period_end' => strtotime(date('Y-m-t 23:59:59')),
        'source' => $body['source'] ?? 'api',
        'subscription_item_id' => $body['subscription_item_id'] ?? NULL,
        'idempotency_key' => $body['idempotency_key'] ?? NULL,
        'billing_period' => date('Y-m'),
      ]);
      $record->save();

      return new JsonResponse(['success' => TRUE, 'data' => [
        'record_id' => (int) $record->id(),
        'metric' => $metric,
        'value' => $value,
      ]]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error recording usage: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => 'Internal error'], 500);
    }
  }

  /**
   * GET /api/v1/usage/current — Uso actual del periodo.
   */
  public function getCurrentUsage(): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    try {
      $usage = $this->metering->getUsage((string) $tenantId);
      return new JsonResponse(['success' => TRUE, 'data' => $usage]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error getting current usage: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => 'Internal error'], 500);
    }
  }

  /**
   * GET /api/v1/usage/history — Histórico de uso.
   */
  public function getUsageHistory(Request $request): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    $months = (int) ($request->query->get('months') ?? 6);
    $months = min(max($months, 1), 24);

    try {
      $history = $this->metering->getHistoricalUsage((string) $tenantId, $months);
      return new JsonResponse(['success' => TRUE, 'data' => [
        'tenant_id' => $tenantId,
        'months' => $months,
        'history' => $history,
      ]]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error getting usage history: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => 'Internal error'], 500);
    }
  }

  /**
   * GET /api/v1/usage/breakdown — Desglose por métrica.
   */
  public function getUsageBreakdown(Request $request): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    $metric = $request->query->get('metric');

    try {
      $usage = $this->metering->getUsage((string) $tenantId);

      if ($metric && isset($usage['metrics'][$metric])) {
        $data = [
          'metric' => $metric,
          'details' => $usage['metrics'][$metric],
        ];
      }
      else {
        $data = [
          'metrics' => $usage['metrics'],
          'total_cost' => $usage['total_cost'],
        ];
      }

      return new JsonResponse(['success' => TRUE, 'data' => $data]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error getting usage breakdown: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => 'Internal error'], 500);
    }
  }

  /**
   * GET /api/v1/usage/forecast — Proyección de uso/coste.
   */
  public function getUsageForecast(): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    try {
      $forecast = $this->metering->getForecast((string) $tenantId);
      return new JsonResponse(['success' => TRUE, 'data' => $forecast]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error getting usage forecast: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => 'Internal error'], 500);
    }
  }

  /**
   * GET /api/v1/pricing/my-plan — Reglas de pricing del plan actual.
   */
  public function getMyPlan(): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    try {
      $tenant = $this->entityTypeManager()->getStorage('tenant')->load($tenantId);
      if (!$tenant) {
        return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 404);
      }

      $plan = $tenant->getSubscriptionPlan();
      $data = [
        'tenant_id' => $tenantId,
        'plan' => $plan ? [
          'name' => $plan->getName(),
          'features' => $plan->getFeatures(),
          'limits' => $plan->getLimits(),
        ] : NULL,
        'status' => $tenant->get('subscription_status')->value ?? 'none',
        'usage_summary' => $this->planValidator->getUsageSummary($tenant),
      ];

      return new JsonResponse(['success' => TRUE, 'data' => $data]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error getting plan info: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => 'Internal error'], 500);
    }
  }

  /**
   * GET /api/v1/pricing/estimate — Estimar coste para uso dado.
   */
  public function estimateCost(Request $request): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    try {
      $bill = $this->metering->calculateBill((string) $tenantId);
      $forecast = $this->metering->getForecast((string) $tenantId);

      return new JsonResponse(['success' => TRUE, 'data' => [
        'current_bill' => $bill,
        'forecast' => $forecast,
      ]]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error estimating cost: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => 'Internal error'], 500);
    }
  }

}

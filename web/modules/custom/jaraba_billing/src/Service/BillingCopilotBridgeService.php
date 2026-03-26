<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_copilot_v2\Service\CopilotBridgeInterface;
use Psr\Log\LoggerInterface;

/**
 * Billing Copilot Bridge — contextualiza el copilot con datos financieros.
 */
class BillingCopilotBridgeService implements CopilotBridgeInterface {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    protected RevenueMetricsService $revenueMetrics,
    protected ?TenantSubscriptionService $subscriptionService = NULL,
    protected ?ExpansionRevenueService $expansionRevenue = NULL,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getVerticalKey(): string {
    return '__global__';
  }

  /**
   * {@inheritdoc}
   *
   * @return array<string, mixed>
   */
  public function getRelevantContext(int $userId): array {
    $context = [
      'vertical' => 'billing',
      'has_billing_data' => FALSE,
      'revenue_snapshot' => [],
      'subscription_status' => '',
      'recent_invoices' => [],
      'churn_rate' => 0.0,
    ];

    try {
      // Revenue snapshot.
      $snapshot = $this->revenueMetrics->getDashboardSnapshot();
      $context['revenue_snapshot'] = [
        'mrr' => $snapshot['mrr'] ?? 0,
        'arr' => $snapshot['arr'] ?? 0,
        'active_subscriptions' => $snapshot['active_subscriptions'] ?? 0,
        'churn_rate' => $snapshot['churn_rate'] ?? 0,
      ];
      $context['churn_rate'] = (float) ($snapshot['churn_rate'] ?? 0);
      $context['has_billing_data'] = ($snapshot['active_subscriptions'] ?? 0) > 0;

      // Recent invoices (last 5).
      $invoiceIds = $this->entityTypeManager
        ->getStorage('billing_invoice')
        ->getQuery()
        ->accessCheck(TRUE)
        ->sort('created', 'DESC')
        ->range(0, 5)
        ->execute();

      if ($invoiceIds !== []) {
        $invoices = $this->entityTypeManager
          ->getStorage('billing_invoice')
          ->loadMultiple($invoiceIds);
        foreach ($invoices as $invoice) {
          if (!$invoice instanceof ContentEntityInterface) {
            continue;
          }
          $context['recent_invoices'][] = [
            'number' => $invoice->get('invoice_number')->value ?? '',
            'status' => $invoice->get('status')->value ?? '',
            'total' => (float) ($invoice->get('total')->value ?? 0),
            'currency' => $invoice->get('currency')->value ?? 'EUR',
          ];
        }
      }

      // Revenue by plan distribution.
      $byPlan = $this->revenueMetrics->getRevenueByPlan();
      if ($byPlan !== []) {
        $context['revenue_by_plan'] = $byPlan;
      }

      // Tenant distribution.
      $distribution = $this->revenueMetrics->getTenantDistribution();
      if ($distribution !== []) {
        $context['tenant_distribution'] = $distribution;
      }

      $context['_system_prompt_addition'] = $this->buildSystemPrompt($context);

    }
    catch (\Exception $e) {
      $this->logger->warning('Billing CopilotBridge error: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $context;
  }

  /**
   * {@inheritdoc}
   *
   * @return array<string, mixed>|null
   */
  public function getSoftSuggestion(int $userId): ?array {
    try {
      $snapshot = $this->revenueMetrics->getDashboardSnapshot();
      $churnRate = (float) ($snapshot['churn_rate'] ?? 0);

      if ($churnRate > 5.0) {
        return [
          'message' => sprintf(
            'Tu tasa de churn es del %.1f%%, por encima del umbral recomendado (5%%). Revisa las predicciones de churn para actuar proactivamente.',
            $churnRate,
          ),
          'cta' => [
            'label' => 'Ver predicciones',
            'route' => 'jaraba_predictive.dashboard',
          ],
          'trigger' => 'high_churn_rate',
        ];
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('Billing soft suggestion error: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return NULL;
  }

  /**
   * Builds billing-specific system prompt.
   *
   * @param array<string, mixed> $context
   *   The billing context data.
   */
  protected function buildSystemPrompt(array $context): string {
    $prompt = "Tienes acceso a datos financieros y de facturacion. ";

    if ($context['revenue_snapshot'] !== []) {
      $r = $context['revenue_snapshot'];
      $prompt .= sprintf(
        "MRR: %.0f EUR. ARR: %.0f EUR. Suscripciones activas: %d. Churn rate: %.1f%%. ",
        $r['mrr'],
        $r['arr'],
        $r['active_subscriptions'],
        $r['churn_rate'],
      );
    }

    if (isset($context['tenant_distribution']) && $context['tenant_distribution'] !== []) {
      $prompt .= "Distribucion de tenants: ";
      foreach ($context['tenant_distribution'] as $status => $count) {
        $prompt .= sprintf("%s=%d ", $status, $count);
      }
      $prompt .= ". ";
    }

    $prompt .= "Usa estos datos para dar recomendaciones financieras contextualizadas. ";
    $prompt .= "Enfocate en reducir churn, optimizar revenue y detectar oportunidades de expansion. ";
    $prompt .= "NUNCA inventes datos financieros.";

    return $prompt;
  }

}

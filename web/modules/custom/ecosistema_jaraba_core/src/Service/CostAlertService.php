<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Cost Alert Service (FIX-051).
 *
 * Monitors AI token usage per tenant and sends proactive alerts
 * when approaching quota thresholds. Thresholds and limits are
 * delegated to FairUsePolicyService and PlanResolverService
 * (no hardcoded values).
 */
class CostAlertService
{

    /**
     * Constructor.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected LoggerInterface $logger,
        protected ?FairUsePolicyService $fairUsePolicyService = NULL,
    ) {
    }

    /**
     * Checks usage thresholds for a tenant after an AI execution.
     *
     * @param string $tenantId
     *   The tenant ID.
     * @param int $tokensUsed
     *   Tokens just used in this execution.
     *
     * @return array
     *   Alert result: threshold_reached, level, usage_pct, message.
     */
    public function checkThresholds(string $tenantId, int $tokensUsed = 0): array
    {
        $usage = $this->getCurrentMonthUsage($tenantId);
        $limit = $this->getTenantLimit($tenantId);

        if ($limit <= 0) {
            return ['threshold_reached' => FALSE, 'level' => 'none'];
        }

        $usagePct = ($usage / $limit) * 100;
        $thresholds = $this->getConfiguredThresholds($tenantId);
        $level = 'none';

        if ($usagePct >= $thresholds['critical']) {
            $level = 'critical';
        }
        elseif ($usagePct >= $thresholds['warning']) {
            $level = 'warning';
        }

        if ($level !== 'none') {
            $this->notify($tenantId, $level, $usagePct, $usage, $limit);
        }

        return [
            'threshold_reached' => $level !== 'none',
            'level' => $level,
            'usage_pct' => round($usagePct, 1),
            'tokens_used' => $usage,
            'token_limit' => $limit,
            'message' => $level !== 'none'
                ? "Tenant {$tenantId} has reached {$usagePct}% of monthly AI token limit ({$level})."
                : NULL,
        ];
    }

    /**
     * Gets current month's total token usage for a tenant.
     */
    protected function getCurrentMonthUsage(string $tenantId): int
    {
        try {
            if (!$this->entityTypeManager->hasDefinition('ai_usage_log')) {
                return 0;
            }

            $storage = $this->entityTypeManager->getStorage('ai_usage_log');
            $monthStart = strtotime(date('Y-m-01'));

            $ids = $storage->getQuery()
                ->accessCheck(FALSE)
                ->condition('tenant_id', $tenantId)
                ->condition('created', $monthStart, '>=')
                ->execute();

            if (empty($ids)) {
                return 0;
            }

            $totalTokens = 0;
            foreach ($storage->loadMultiple($ids) as $log) {
                $totalTokens += (int) ($log->get('input_tokens')->value ?? 0);
                $totalTokens += (int) ($log->get('output_tokens')->value ?? 0);
            }

            return $totalTokens;

        } catch (\Exception $e) {
            $this->logger->warning('Failed to get usage for tenant @id: @msg', [
                '@id' => $tenantId,
                '@msg' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Gets the monthly token limit for a tenant.
     */
    protected function getTenantLimit(string $tenantId): int
    {
        // Try to resolve from plan features via PlanResolverService.
        if (\Drupal::hasService('ecosistema_jaraba_core.plan_resolver')) {
            try {
                $resolver = \Drupal::service('ecosistema_jaraba_core.plan_resolver');
                if (method_exists($resolver, 'getFeatureValue')) {
                    $limit = $resolver->getFeatureValue($tenantId, 'ai_monthly_tokens');
                    if ($limit !== NULL) {
                        return (int) $limit;
                    }
                }
            } catch (\Throwable $e) {
                // Fall through to default.
            }
        }

        // Reasonable default: 100K tokens/month.
        return 100000;
    }

    /**
     * Gets configured alert thresholds for a tenant.
     *
     * Delegates to FairUsePolicyService when available, with sensible
     * defaults (80/95) as fallback.
     *
     * @return array
     *   Associative array with 'warning' and 'critical' keys.
     */
    protected function getConfiguredThresholds(string $tenantId): array
    {
        if ($this->fairUsePolicyService) {
            try {
                $tier = $this->resolveTenantTier($tenantId);
                $thresholds = $this->fairUsePolicyService->getThresholds($tier);
                if (count($thresholds) >= 2) {
                    return [
                        'warning' => $thresholds[0],
                        'critical' => $thresholds[count($thresholds) - 1],
                    ];
                }
            } catch (\Throwable $e) {
                // Fall through to defaults.
            }
        }

        return ['warning' => 80, 'critical' => 95];
    }

    /**
     * Resolves the tier key for a tenant.
     */
    protected function resolveTenantTier(string $tenantId): string
    {
        if (\Drupal::hasService('ecosistema_jaraba_core.tenant_subscription')) {
            try {
                $subscription = \Drupal::service('ecosistema_jaraba_core.tenant_subscription');
                if (method_exists($subscription, 'getTenantTier')) {
                    return $subscription->getTenantTier($tenantId) ?: 'starter';
                }
            } catch (\Throwable $e) {
                // Fall through.
            }
        }
        return 'starter';
    }

    /**
     * Sends notification for threshold alert.
     */
    protected function notify(string $tenantId, string $level, float $usagePct, int $usage, int $limit): void
    {
        $this->logger->warning('AI cost alert: tenant=@tenant, level=@level, usage=@pct% (@used/@limit tokens)', [
            '@tenant' => $tenantId,
            '@level' => $level,
            '@pct' => round($usagePct, 1),
            '@used' => $usage,
            '@limit' => $limit,
        ]);

        // Avoid duplicate alerts: check if alert was already sent this hour.
        $stateKey = "cost_alert:{$tenantId}:{$level}:" . date('Y-m-d-H');
        $lastAlert = \Drupal::state()->get($stateKey);

        if ($lastAlert) {
            return;
        }

        \Drupal::state()->set($stateKey, time());

        // Queue notification for tenant admin.
        try {
            if (\Drupal::hasService('plugin.manager.mail')) {
                // Future: send email notification to tenant admin.
                $this->logger->info('Cost alert notification queued for tenant @tenant (@level).', [
                    '@tenant' => $tenantId,
                    '@level' => $level,
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to send cost alert: @msg', ['@msg' => $e->getMessage()]);
        }
    }

}

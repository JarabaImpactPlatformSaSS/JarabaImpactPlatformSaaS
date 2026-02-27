<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\State\StateInterface;
use Psr\Log\LoggerInterface;

/**
 * Proactive Insights orchestrator (S3-03: HAL-AI-06).
 *
 * Runs via cron every 6 hours, enqueuing insight generation jobs
 * for all active tenants. The actual AI analysis is performed by
 * ProactiveInsightEngineWorker (QueueWorker).
 *
 * Insight types:
 * - usage_anomaly: Usage spikes or drops.
 * - churn_risk: Tenant inactive 14+ days.
 * - content_gap: Categories without recent articles.
 * - seo_opportunity: Pages without meta description.
 * - quota_warning: Usage > 80% of plan.
 */
class ProactiveInsightsService
{

    /**
     * Interval between insight runs (6 hours in seconds).
     */
    protected const RUN_INTERVAL = 21600;

    /**
     * Constructor.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected QueueFactory $queueFactory,
        protected StateInterface $state,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * Enqueues insight generation for all active tenants.
     *
     * Called from hook_cron(). Respects RUN_INTERVAL to avoid excessive runs.
     */
    public function runCron(): void
    {
        $lastRun = (int) $this->state->get('jaraba_proactive_insights.last_run', 0);
        $now = time();

        if (($now - $lastRun) < self::RUN_INTERVAL) {
            return;
        }

        $this->state->set('jaraba_proactive_insights.last_run', $now);

        try {
            $tenants = $this->getActiveTenants();
            $queue = $this->queueFactory->get('proactive_insight_engine');
            $enqueued = 0;

            foreach ($tenants as $tenant) {
                $tenantId = (int) $tenant->id();
                $adminUserId = $this->getAdminUserId($tenant);

                if ($adminUserId > 0) {
                    // Enqueue rule-based checks.
                    $this->enqueueRuleBasedInsights($queue, $tenantId, $adminUserId);
                    $enqueued++;

                    // Enqueue AI-powered analysis.
                    $queue->createItem([
                        'tenant_id' => $tenantId,
                        'user_id' => $adminUserId,
                        'type' => 'ai_analysis',
                    ]);
                }
            }

            $this->logger->info('Proactive insights cron: enqueued @count tenants.', [
                '@count' => $enqueued,
            ]);
        }
        catch (\Exception $e) {
            $this->logger->error('Proactive insights cron failed: @error', [
                '@error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Enqueues rule-based insight checks for a tenant.
     *
     * These are deterministic checks that don't require AI:
     * - Churn risk (no login in 14+ days).
     * - Quota warnings (usage > 80%).
     */
    protected function enqueueRuleBasedInsights(object $queue, int $tenantId, int $adminUserId): void
    {
        // Check churn risk: admin user hasn't logged in 14+ days.
        try {
            $user = $this->entityTypeManager->getStorage('user')->load($adminUserId);
            if ($user) {
                $lastLogin = (int) $user->getLastLoginTime();
                $daysSinceLogin = $lastLogin > 0
                    ? (int) ((time() - $lastLogin) / 86400)
                    : 999;

                if ($daysSinceLogin >= 14) {
                    $this->createInsightIfNotExists($tenantId, $adminUserId, 'churn_risk', [
                        'title' => 'Inactividad detectada',
                        'body' => "Han pasado {$daysSinceLogin} dias desde tu ultimo acceso. Tu equipo podria estar perdiendo oportunidades.",
                        'severity' => $daysSinceLogin >= 30 ? 'high' : 'medium',
                    ]);
                }
            }
        }
        catch (\Exception $e) {
            // Non-critical.
        }

        // Check quota usage via UsageLimitsService if available.
        if (\Drupal::hasService('ecosistema_jaraba_core.usage_limits')) {
            try {
                $usageLimits = \Drupal::service('ecosistema_jaraba_core.usage_limits');
                $usage = $usageLimits->getCurrentUsage($tenantId);
                $limits = $usageLimits->getPlanLimits($tenantId);

                if (!empty($usage) && !empty($limits)) {
                    foreach ($usage as $metric => $value) {
                        $limit = $limits[$metric] ?? 0;
                        if ($limit > 0 && $value > 0) {
                            $percentage = ($value / $limit) * 100;
                            if ($percentage >= 80) {
                                $severity = $percentage >= 95 ? 'high' : 'medium';
                                $this->createInsightIfNotExists($tenantId, $adminUserId, 'quota_warning', [
                                    'title' => "Uso de {$metric} al " . (int) $percentage . '%',
                                    'body' => "Has utilizado {$value} de {$limit} ({$metric}). Considera actualizar tu plan para evitar interrupciones.",
                                    'severity' => $severity,
                                    'action_url' => '/pricing',
                                ]);
                            }
                        }
                    }
                }
            }
            catch (\Exception $e) {
                // Non-critical.
            }
        }
    }

    /**
     * Creates an insight entity if one of the same type doesn't exist recently.
     *
     * Prevents duplicate insights within 24 hours.
     */
    protected function createInsightIfNotExists(int $tenantId, int $userId, string $insightType, array $data): void
    {
        try {
            $storage = $this->entityTypeManager->getStorage('proactive_insight');
            $todayStart = strtotime('today midnight');

            // Check for recent duplicate.
            $existing = $storage->getQuery()
                ->accessCheck(FALSE)
                ->condition('tenant_id', $tenantId)
                ->condition('insight_type', $insightType)
                ->condition('created', $todayStart, '>=')
                ->count()
                ->execute();

            if ((int) $existing > 0) {
                return;
            }

            $entity = $storage->create([
                'title' => mb_substr($data['title'] ?? '', 0, 255),
                'insight_type' => $insightType,
                'body' => $data['body'] ?? '',
                'severity' => $data['severity'] ?? 'medium',
                'target_user' => $userId,
                'tenant_id' => $tenantId,
                'read_status' => FALSE,
                'action_url' => $data['action_url'] ?? '',
            ]);
            $entity->save();
        }
        catch (\Exception $e) {
            $this->logger->notice('Failed to create @type insight for tenant @id: @error', [
                '@type' => $insightType,
                '@id' => $tenantId,
                '@error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Gets all active tenants.
     */
    protected function getActiveTenants(): array
    {
        try {
            $storage = $this->entityTypeManager->getStorage('tenant');
            $ids = $storage->getQuery()
                ->accessCheck(FALSE)
                ->condition('subscription_status', ['active', 'trialing'], 'IN')
                ->execute();

            return $ids ? $storage->loadMultiple($ids) : [];
        }
        catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Gets the admin user ID for a tenant.
     */
    protected function getAdminUserId(object $tenant): int
    {
        try {
            if ($tenant->hasField('admin_user')) {
                return (int) ($tenant->get('admin_user')->target_id ?? 0);
            }
        }
        catch (\Exception $e) {
            // Fallback.
        }
        return 0;
    }

}

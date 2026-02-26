<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\ecosistema_jaraba_core\AI\AIIdentityRule;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processes proactive insight generation for tenants.
 *
 * GAP-AUD-010: Cron-driven AI analysis that creates ProactiveInsight entities.
 * Max 3 insights per user per day. Uses balanced tier for quality analysis.
 *
 * @QueueWorker(
 *   id = "proactive_insight_engine",
 *   title = @Translation("Proactive Insight Engine"),
 *   cron = {"time" = 120}
 * )
 */
class ProactiveInsightEngineWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface
{

    /**
     * Maximum insights per user per day.
     */
    private const MAX_INSIGHTS_PER_USER_PER_DAY = 3;

    public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        protected readonly EntityTypeManagerInterface $entityTypeManager,
        protected readonly LoggerInterface $logger,
        protected readonly ?object $aiAgent = NULL,
        protected readonly ?object $observability = NULL,
    ) {
        parent::__construct($configuration, $plugin_id, $plugin_definition);
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static
    {
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->get('entity_type.manager'),
            $container->get('logger.channel.jaraba_ai_agents'),
            $container->has('jaraba_ai_agents.smart_marketing_agent')
                ? $container->get('jaraba_ai_agents.smart_marketing_agent')
                : NULL,
            $container->has('jaraba_ai_agents.observability')
                ? $container->get('jaraba_ai_agents.observability')
                : NULL,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function processItem($data): void
    {
        $tenantId = (int) ($data['tenant_id'] ?? 0);
        $userId = (int) ($data['user_id'] ?? 0);

        if ($userId === 0) {
            return;
        }

        // Check daily limit.
        if ($this->countTodayInsights($userId) >= self::MAX_INSIGHTS_PER_USER_PER_DAY) {
            $this->logger->info('Proactive insight limit reached for user @uid', ['@uid' => $userId]);
            return;
        }

        // If no AI agent, create a static insight.
        if ($this->aiAgent === NULL) {
            $this->createStaticInsight($tenantId, $userId);
            return;
        }

        try {
            $prompt = AIIdentityRule::apply(
                'Analyze the tenant platform usage and generate ONE actionable insight. '
                . 'Categories: optimization (improve efficiency), alert (potential issue), opportunity (growth potential). '
                . 'Return JSON: {"type": "optimization|alert|opportunity", "title": "...", "body": "...", "severity": "high|medium|low", "action_url": ""}',
                TRUE
            );

            $result = $this->aiAgent->execute([
                'action' => 'proactive_insight',
                'prompt' => $prompt,
                'tier' => 'balanced',
            ]);

            $content = $result['content'] ?? $result['response'] ?? '';
            $insight = $this->parseInsight($content);

            if (!empty($insight['title'])) {
                $this->createInsightEntity($tenantId, $userId, $insight, $result);
            }

            // Log to observability (AI-OBSERVABILITY-001).
            if ($this->observability !== NULL) {
                $this->observability->log([
                    'agent_id' => 'proactive_insight_engine',
                    'action' => 'generate_insight',
                    'tier' => 'balanced',
                    'tenant_id' => $tenantId,
                ]);
            }
        }
        catch (\Exception $e) {
            $this->logger->warning('Proactive insight generation failed: @error', [
                '@error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Counts today's insights for a user.
     */
    protected function countTodayInsights(int $userId): int
    {
        $storage = $this->entityTypeManager->getStorage('proactive_insight');
        $todayStart = strtotime('today midnight');

        return (int) $storage->getQuery()
            ->accessCheck(FALSE)
            ->condition('target_user', $userId)
            ->condition('created', $todayStart, '>=')
            ->count()
            ->execute();
    }

    /**
     * Creates a static insight when no AI agent is available.
     */
    protected function createStaticInsight(int $tenantId, int $userId): void
    {
        $storage = $this->entityTypeManager->getStorage('proactive_insight');
        $insight = $storage->create([
            'title' => 'Platform usage analysis available',
            'insight_type' => 'optimization',
            'body' => 'Configure an AI provider to enable AI-powered proactive insights for your platform.',
            'severity' => 'low',
            'target_user' => $userId,
            'tenant_id' => $tenantId,
            'read_status' => FALSE,
            'action_url' => '/admin/config/ai/agents',
        ]);
        $insight->save();
    }

    /**
     * Parses AI response into insight data.
     */
    protected function parseInsight(string $content): array
    {
        if (is_string($content) && preg_match('/\{.*\}/s', $content, $matches)) {
            $decoded = json_decode($matches[0], TRUE);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return [];
    }

    /**
     * Creates a ProactiveInsight entity from parsed data.
     */
    protected function createInsightEntity(int $tenantId, int $userId, array $insight, array $aiResult): void
    {
        $storage = $this->entityTypeManager->getStorage('proactive_insight');
        $entity = $storage->create([
            'title' => mb_substr($insight['title'] ?? 'Insight', 0, 255),
            'insight_type' => in_array($insight['type'] ?? '', ['optimization', 'alert', 'opportunity'], TRUE)
                ? $insight['type']
                : 'optimization',
            'body' => $insight['body'] ?? '',
            'severity' => in_array($insight['severity'] ?? '', ['high', 'medium', 'low'], TRUE)
                ? $insight['severity']
                : 'medium',
            'target_user' => $userId,
            'tenant_id' => $tenantId,
            'read_status' => FALSE,
            'action_url' => $insight['action_url'] ?? '',
            'ai_model' => $aiResult['model'] ?? '',
            'ai_confidence' => (float) ($aiResult['confidence'] ?? 0.75),
        ]);
        $entity->save();

        $this->logger->info('Created proactive insight "@title" for user @uid', [
            '@title' => $entity->getTitle(),
            '@uid' => $userId,
        ]);
    }

}

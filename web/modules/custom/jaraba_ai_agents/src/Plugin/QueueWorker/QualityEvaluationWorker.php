<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\jaraba_ai_agents\Service\QualityEvaluatorService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Quality Evaluation Queue Worker (FIX-032).
 *
 * Evaluates AI responses in background using LLM-as-Judge via
 * QualityEvaluatorService. Updates ai_usage_log with quality_score.
 *
 * @QueueWorker(
 *   id = "jaraba_ai_agents_quality_evaluation",
 *   title = @Translation("AI Quality Evaluation"),
 *   cron = {"time" = 30}
 * )
 */
class QualityEvaluationWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface
{

    /**
     * Constructor.
     */
    public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        protected QualityEvaluatorService $qualityEvaluator,
        protected LoggerInterface $logger,
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
            $container->get('jaraba_ai_agents.quality_evaluator'),
            $container->get('logger.channel.jaraba_ai_agents'),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function processItem($data): void
    {
        if (!isset($data['log_id'], $data['prompt'], $data['response'])) {
            $this->logger->warning('Quality evaluation queue item missing required fields.');
            return;
        }

        try {
            $result = $this->qualityEvaluator->evaluateAndLog(
                (int) $data['log_id'],
                $data['prompt'],
                $data['response'],
            );

            $this->logger->info('Quality evaluation completed for log @id: score=@score', [
                '@id' => $data['log_id'],
                '@score' => $result['overall_score'] ?? 'N/A',
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Quality evaluation failed for log @id: @msg', [
                '@id' => $data['log_id'],
                '@msg' => $e->getMessage(),
            ]);
        }
    }

}

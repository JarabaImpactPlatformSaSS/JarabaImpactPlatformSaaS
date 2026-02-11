<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_ai_agents\Service\QualityEvaluatorService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for Quality Evaluation API.
 */
class QualityApiController extends ControllerBase
{

    /**
     * The quality evaluator service.
     *
     * @var \Drupal\jaraba_ai_agents\Service\QualityEvaluatorService
     */
    protected QualityEvaluatorService $evaluator;

    /**
     * Constructs a QualityApiController.
     */
    public function __construct(QualityEvaluatorService $evaluator)
    {
        $this->evaluator = $evaluator;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_ai_agents.quality_evaluator'),
        );
    }

    /**
     * Evaluates a response quality.
     */
    public function evaluate(Request $request): JsonResponse
    {
        $content = json_decode($request->getContent(), TRUE);

        if (empty($content['prompt']) || empty($content['response'])) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => 'Missing required fields: prompt, response',
            ], 400);
        }

        $result = $this->evaluator->evaluate(
            $content['prompt'],
            $content['response'],
            $content['criteria'] ?? [],
            $content['context'] ?? [],
        );

        return new JsonResponse($result, $result['success'] ? 200 : 400);
    }

    /**
     * Gets quality statistics.
     */
    public function getStats(Request $request): JsonResponse
    {
        $period = $request->query->get('period', 'month');

        $stats = $this->evaluator->getQualityStats($period);

        return new JsonResponse([
            'success' => TRUE,
            'period' => $period,
            'data' => $stats,
        ]);
    }

    /**
     * Gets available evaluation criteria.
     */
    public function getCriteria(): JsonResponse
    {
        return new JsonResponse([
            'success' => TRUE,
            'criteria' => [
                'relevance' => [
                    'description' => 'How well the response addresses the prompt',
                    'weight' => 0.25,
                ],
                'accuracy' => [
                    'description' => 'Factual correctness and logical consistency',
                    'weight' => 0.25,
                ],
                'clarity' => [
                    'description' => 'Clear, well-structured, easy to understand',
                    'weight' => 0.20,
                ],
                'brand_alignment' => [
                    'description' => 'Matches expected tone and brand voice',
                    'weight' => 0.15,
                ],
                'actionability' => [
                    'description' => 'Practical, usable output ready for purpose',
                    'weight' => 0.15,
                ],
            ],
        ]);
    }

}

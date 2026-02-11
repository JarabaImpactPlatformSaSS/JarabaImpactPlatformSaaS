<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_ai_agents\Service\WorkflowExecutorService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for Workflow API endpoints.
 */
class WorkflowApiController extends ControllerBase
{

    /**
     * The workflow executor.
     *
     * @var \Drupal\jaraba_ai_agents\Service\WorkflowExecutorService
     */
    protected WorkflowExecutorService $executor;

    /**
     * Constructs a WorkflowApiController.
     */
    public function __construct(WorkflowExecutorService $executor)
    {
        $this->executor = $executor;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_ai_agents.workflow_executor'),
        );
    }

    /**
     * Lists available workflows.
     */
    public function listWorkflows(): JsonResponse
    {
        $storage = $this->entityTypeManager()->getStorage('ai_workflow');
        $workflows = $storage->loadMultiple();

        $data = [];
        foreach ($workflows as $workflow) {
            /** @var \Drupal\jaraba_ai_agents\Entity\AIWorkflow $workflow */
            $data[] = [
                'id' => $workflow->id(),
                'label' => $workflow->label(),
                'description' => $workflow->getDescription(),
                'steps' => count($workflow->getSteps()),
                'status' => $workflow->status(),
            ];
        }

        return new JsonResponse([
            'success' => TRUE,
            'data' => $data,
        ]);
    }

    /**
     * Gets a single workflow.
     */
    public function getWorkflow(string $workflow_id): JsonResponse
    {
        $workflow = $this->entityTypeManager()
            ->getStorage('ai_workflow')
            ->load($workflow_id);

        if (!$workflow) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => "Workflow '{$workflow_id}' not found.",
            ], 404);
        }

        /** @var \Drupal\jaraba_ai_agents\Entity\AIWorkflow $workflow */
        return new JsonResponse([
            'success' => TRUE,
            'data' => [
                'id' => $workflow->id(),
                'label' => $workflow->label(),
                'description' => $workflow->getDescription(),
                'trigger' => $workflow->getTrigger(),
                'steps' => $workflow->getSteps(),
                'conditions' => $workflow->getConditions(),
                'status' => $workflow->status(),
            ],
        ]);
    }

    /**
     * Executes a workflow.
     */
    public function executeWorkflow(string $workflow_id, Request $request): JsonResponse
    {
        $content = $request->getContent();
        $context = [];

        if (!empty($content)) {
            $decoded = json_decode($content, TRUE);
            if (json_last_error() === JSON_ERROR_NONE) {
                $context = $decoded['context'] ?? $decoded;
            }
        }

        $result = $this->executor->execute($workflow_id, $context);

        $statusCode = $result['success'] ? 200 : 400;
        if (isset($result['error']) && str_contains($result['error'], 'not found')) {
            $statusCode = 404;
        }

        return new JsonResponse($result, $statusCode);
    }

}

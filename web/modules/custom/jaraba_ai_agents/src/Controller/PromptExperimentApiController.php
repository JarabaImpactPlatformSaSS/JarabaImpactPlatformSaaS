<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_ai_agents\Service\PromptExperimentService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API Controller for Prompt A/B Experiments (F11).
 */
class PromptExperimentApiController extends ControllerBase {

  public function __construct(
    protected PromptExperimentService $promptExperiment,
    protected LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_ai_agents.prompt_experiment'),
      $container->get('logger.channel.jaraba_ai_agents'),
    );
  }

  /**
   * Gets the current tenant ID.
   */
  protected function getTenantId(): ?int {
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
   * GET /api/v1/ai/prompt-experiments
   */
  public function listExperiments(): JsonResponse {
    $tenantId = $this->getTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    $experiments = $this->promptExperiment->listExperiments($tenantId);
    return new JsonResponse(['success' => TRUE, 'data' => $experiments]);
  }

  /**
   * POST /api/v1/ai/prompt-experiments
   */
  public function createExperiment(Request $request): JsonResponse {
    $tenantId = $this->getTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    $body = json_decode($request->getContent(), TRUE) ?? [];
    $name = $body['name'] ?? '';
    $label = $body['label'] ?? '';
    $variants = $body['variants'] ?? [];

    if (!$name || !$label || count($variants) < 2) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => 'name, label, and at least 2 variants required',
      ], 400);
    }

    $result = $this->promptExperiment->createExperiment($name, $label, $variants, $tenantId, $body['options'] ?? []);
    return new JsonResponse($result, $result['success'] ? 201 : 500);
  }

  /**
   * GET /api/v1/ai/prompt-experiments/{experiment_id}/results
   */
  public function getResults(string $experiment_id): JsonResponse {
    $result = $this->promptExperiment->getExperimentResults((int) $experiment_id);
    return new JsonResponse($result);
  }

}

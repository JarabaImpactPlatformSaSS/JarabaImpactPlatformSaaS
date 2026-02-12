<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_ai_agents\Service\BrandVoiceTrainerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API Controller for Brand Voice Training (F11).
 */
class BrandVoiceTrainerApiController extends ControllerBase {

  public function __construct(
    protected BrandVoiceTrainerService $trainer,
    protected LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_ai_agents.brand_voice_trainer'),
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
   * POST /api/v1/ai/brand-voice/feedback
   */
  public function recordFeedback(Request $request): JsonResponse {
    $tenantId = $this->getTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    $body = json_decode($request->getContent(), TRUE) ?? [];
    $output = $body['output'] ?? '';
    $feedback = $body['feedback'] ?? '';
    $editedText = $body['edited_text'] ?? NULL;
    $context = $body['context'] ?? 'general';

    if (!$output || !in_array($feedback, ['approve', 'reject', 'edit'], TRUE)) {
      return new JsonResponse(['success' => FALSE, 'error' => 'output and feedback (approve|reject|edit) required'], 400);
    }

    $result = $this->trainer->recordFeedback($tenantId, $output, $feedback, $editedText, $context);
    return new JsonResponse($result);
  }

  /**
   * POST /api/v1/ai/brand-voice/alignment
   */
  public function checkAlignment(Request $request): JsonResponse {
    $tenantId = $this->getTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    $body = json_decode($request->getContent(), TRUE) ?? [];
    $text = $body['text'] ?? '';

    if (!$text) {
      return new JsonResponse(['success' => FALSE, 'error' => 'text required'], 400);
    }

    $result = $this->trainer->computeAlignmentScore($tenantId, $text);
    return new JsonResponse(['success' => TRUE, 'data' => $result]);
  }

  /**
   * GET /api/v1/ai/brand-voice/training-stats
   */
  public function getTrainingStats(): JsonResponse {
    $tenantId = $this->getTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    $stats = $this->trainer->getTrainingStats($tenantId);
    return new JsonResponse(['success' => TRUE, 'data' => $stats]);
  }

  /**
   * POST /api/v1/ai/brand-voice/refine
   */
  public function refineBrandVoice(): JsonResponse {
    $tenantId = $this->getTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    $result = $this->trainer->refineBrandVoice($tenantId);
    return new JsonResponse($result);
  }

}

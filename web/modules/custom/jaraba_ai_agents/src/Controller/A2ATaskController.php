<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_ai_agents\Entity\A2ATask;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * GAP-AUD-012: A2A Task controller for Agent-to-Agent protocol.
 *
 * Handles task submission, status check, and cancellation.
 * Tasks are processed asynchronously via A2ATaskWorker.
 */
class A2ATaskController extends ControllerBase {

  /**
   * Valid actions that can be submitted.
   */
  protected const VALID_ACTIONS = [
    'generate_content',
    'analyze_sentiment',
    'seo_suggestions',
    'brand_voice_check',
    'skill_inference',
  ];

  /**
   * POST /api/v1/a2a/tasks/send — Submit a new task.
   */
  public function submitTask(Request $request): JsonResponse {
    // Rate limiting via Drupal Flood.
    $flood = \Drupal::flood();
    $clientIp = $request->getClientIp();
    $floodName = 'a2a_task_submit';

    if (!$flood->isAllowed($floodName, 100, 3600, $clientIp)) {
      return new JsonResponse([
        'error' => 'Rate limit exceeded. Maximum 100 tasks per hour.',
      ], 429);
    }
    $flood->register($floodName, 3600, $clientIp);

    // Parse input.
    $data = json_decode($request->getContent(), TRUE);
    if (empty($data)) {
      return new JsonResponse(['error' => 'Invalid JSON payload.'], 400);
    }

    // Validate required fields.
    if (empty($data['action'])) {
      return new JsonResponse(['error' => 'Missing required field: action.'], 400);
    }

    if (!in_array($data['action'], self::VALID_ACTIONS, TRUE)) {
      return new JsonResponse([
        'error' => 'Invalid action. Valid actions: ' . implode(', ', self::VALID_ACTIONS),
      ], 400);
    }

    if (empty($data['input'])) {
      return new JsonResponse(['error' => 'Missing required field: input.'], 400);
    }

    // Verify HMAC signature if provided.
    $signature = $request->headers->get('X-Signature');
    if (!empty($signature)) {
      $isValid = $this->verifyHmacSignature($request->getContent(), $signature);
      if (!$isValid) {
        return new JsonResponse(['error' => 'Invalid HMAC signature.'], 401);
      }
    }

    try {
      // Create A2A Task entity.
      $storage = $this->entityTypeManager()->getStorage('a2a_task');
      $task = $storage->create([
        'title' => $data['title'] ?? ('A2A: ' . $data['action']),
        'status' => A2ATask::STATUS_SUBMITTED,
        'action' => $data['action'],
        'input_data' => json_encode($data['input'], JSON_UNESCAPED_UNICODE),
        'callback_url' => $data['callback_url'] ?? '',
        'external_agent_id' => $data['agent_id'] ?? $clientIp,
        'tenant_id' => (int) ($data['tenant_id'] ?? 0),
      ]);
      $task->save();

      // Enqueue for async processing.
      $queue = \Drupal::queue('a2a_task_worker');
      $queue->createItem([
        'task_id' => (int) $task->id(),
      ]);

      return new JsonResponse([
        'task_id' => (int) $task->id(),
        'status' => A2ATask::STATUS_SUBMITTED,
        'created' => (int) $task->get('created')->value,
        'status_url' => $request->getSchemeAndHttpHost() . '/api/v1/a2a/tasks/' . $task->id(),
      ], 201);
    }
    catch (\Exception $e) {
      \Drupal::logger('jaraba_ai_agents')->error('A2A task creation failed: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['error' => 'Internal server error.'], 500);
    }
  }

  /**
   * GET /api/v1/a2a/tasks/{task_id} — Check task status.
   */
  public function getTaskStatus(string $task_id): JsonResponse {
    $storage = $this->entityTypeManager()->getStorage('a2a_task');
    $task = $storage->load($task_id);

    if (!$task) {
      return new JsonResponse(['error' => 'Task not found.'], 404);
    }

    /** @var \Drupal\jaraba_ai_agents\Entity\A2ATask $task */
    $response = [
      'task_id' => (int) $task->id(),
      'status' => $task->getStatus(),
      'action' => $task->getAction(),
      'created' => (int) $task->get('created')->value,
      'changed' => (int) $task->get('changed')->value,
    ];

    // Include output if completed.
    if ($task->getStatus() === A2ATask::STATUS_COMPLETED) {
      $response['output'] = $task->getOutput();
    }

    // Include error if failed.
    if ($task->getStatus() === A2ATask::STATUS_FAILED) {
      $response['error'] = $task->get('error_message')->value ?? 'Unknown error.';
    }

    return new JsonResponse($response);
  }

  /**
   * POST /api/v1/a2a/tasks/{task_id}/cancel — Cancel a task.
   */
  public function cancelTask(string $task_id): JsonResponse {
    $storage = $this->entityTypeManager()->getStorage('a2a_task');
    $task = $storage->load($task_id);

    if (!$task) {
      return new JsonResponse(['error' => 'Task not found.'], 404);
    }

    /** @var \Drupal\jaraba_ai_agents\Entity\A2ATask $task */
    $status = $task->getStatus();

    // Can only cancel submitted or working tasks.
    if (!in_array($status, [A2ATask::STATUS_SUBMITTED, A2ATask::STATUS_WORKING], TRUE)) {
      return new JsonResponse([
        'error' => 'Cannot cancel task in status: ' . $status,
      ], 409);
    }

    $task->setStatus(A2ATask::STATUS_CANCELLED);
    $task->save();

    return new JsonResponse([
      'task_id' => (int) $task->id(),
      'status' => A2ATask::STATUS_CANCELLED,
    ]);
  }

  /**
   * Verifies HMAC-SHA256 signature.
   */
  protected function verifyHmacSignature(string $payload, string $signature): bool {
    $secret = \Drupal::config('jaraba_ai_agents.settings')->get('a2a_hmac_secret') ?? '';
    if (empty($secret)) {
      // If no secret configured, skip verification.
      return TRUE;
    }

    $expected = hash_hmac('sha256', $payload, $secret);
    return hash_equals($expected, $signature);
  }

}

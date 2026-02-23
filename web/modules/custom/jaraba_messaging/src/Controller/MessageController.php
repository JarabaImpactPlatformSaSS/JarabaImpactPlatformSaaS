<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_messaging\Exception\EditWindowExpiredException;
use Drupal\jaraba_messaging\Exception\RateLimitException;
use Drupal\jaraba_messaging\Service\ConversationServiceInterface;
use Drupal\jaraba_messaging\Service\MessageServiceInterface;
use Drupal\jaraba_messaging\Service\MessagingServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador REST API para mensajes.
 *
 * Endpoints: list, send, edit, delete, markRead, addReaction.
 */
class MessageController extends ControllerBase {

  public function __construct(
    protected MessagingServiceInterface $messagingService,
    protected MessageServiceInterface $messageService,
    protected ConversationServiceInterface $conversationService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_messaging.messaging'),
      $container->get('jaraba_messaging.message'),
      $container->get('jaraba_messaging.conversation'),
    );
  }

  /**
   * GET /api/v1/messaging/conversations/{uuid}/messages — List messages.
   */
  public function list(string $uuid, Request $request): JsonResponse {
    $limit = min(100, max(1, (int) $request->query->get('limit', 25)));
    $beforeId = $request->query->get('before_id');
    $beforeId = $beforeId !== NULL ? (int) $beforeId : NULL;

    try {
      $data = $this->messagingService->getMessages($uuid, $limit, $beforeId);

      $hasMore = count($data) === $limit;
      $cursor = $hasMore && !empty($data) ? $data[0]['id'] : NULL;

      return new JsonResponse([
        'success' => TRUE,
        'data' => $data,
        'meta' => [
          'total' => count($data),
          'limit' => $limit,
          'cursor' => ['next' => $cursor],
        ],
      ]);
    }
    catch (\Throwable $e) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'ERROR', 'message' => $e->getMessage()],
      ], 500);
    }
  }

  /**
   * POST /api/v1/messaging/conversations/{uuid}/messages — Send message.
   */
  public function send(string $uuid, Request $request): JsonResponse {
    $content = json_decode($request->getContent(), TRUE);

    if (empty($content['body'])) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'VALIDATION', 'message' => 'body is required.'],
      ], 400);
    }

    try {
      $data = $this->messagingService->sendMessage(
        $uuid,
        $content['body'],
        $content['message_type'] ?? 'text',
        isset($content['reply_to_id']) ? (int) $content['reply_to_id'] : NULL,
        $content['attachment_ids'] ?? [],
      );

      return new JsonResponse([
        'success' => TRUE,
        'data' => $data,
        'meta' => ['timestamp' => time()],
      ], 201);
    }
    catch (RateLimitException $e) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'RATE_LIMIT', 'message' => $e->getMessage()],
      ], 429);
    }
    catch (\Throwable $e) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'ERROR', 'message' => $e->getMessage()],
      ], 500);
    }
  }

  /**
   * PATCH /api/v1/messaging/conversations/{uuid}/messages/{message_id} — Edit.
   */
  public function edit(string $uuid, int $message_id, Request $request): JsonResponse {
    $content = json_decode($request->getContent(), TRUE);

    if (empty($content['body'])) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'VALIDATION', 'message' => 'body is required.'],
      ], 400);
    }

    $conversation = $this->conversationService->getByUuid($uuid);
    if (!$conversation) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'NOT_FOUND', 'message' => 'Conversation not found.'],
      ], 404);
    }

    try {
      $message = $this->messageService->edit(
        $message_id,
        $conversation->getTenantId(),
        $content['body'],
      );

      return new JsonResponse([
        'success' => TRUE,
        'data' => $message->toArray(),
        'meta' => ['timestamp' => time()],
      ]);
    }
    catch (EditWindowExpiredException $e) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'EDIT_WINDOW_EXPIRED', 'message' => $e->getMessage()],
      ], 422);
    }
    catch (\Throwable $e) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'ERROR', 'message' => $e->getMessage()],
      ], 500);
    }
  }

  /**
   * DELETE /api/v1/messaging/conversations/{uuid}/messages/{message_id}.
   */
  public function delete(string $uuid, int $message_id): JsonResponse {
    $conversation = $this->conversationService->getByUuid($uuid);
    if (!$conversation) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'NOT_FOUND', 'message' => 'Conversation not found.'],
      ], 404);
    }

    try {
      $this->messageService->softDelete($message_id, $conversation->getTenantId());

      return new JsonResponse([
        'success' => TRUE,
        'data' => ['message_id' => $message_id, 'is_deleted' => TRUE],
        'meta' => ['timestamp' => time()],
      ]);
    }
    catch (\Throwable $e) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'ERROR', 'message' => $e->getMessage()],
      ], 500);
    }
  }

  /**
   * POST /api/v1/messaging/conversations/{uuid}/read — Mark as read.
   */
  public function markRead(string $uuid): JsonResponse {
    try {
      $count = $this->messagingService->markConversationRead($uuid);

      return new JsonResponse([
        'success' => TRUE,
        'data' => ['read_count' => $count],
        'meta' => ['timestamp' => time()],
      ]);
    }
    catch (\Throwable $e) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'ERROR', 'message' => $e->getMessage()],
      ], 500);
    }
  }

  /**
   * POST /api/v1/messaging/conversations/{uuid}/messages/{message_id}/reactions.
   */
  public function addReaction(string $uuid, int $message_id, Request $request): JsonResponse {
    $content = json_decode($request->getContent(), TRUE);

    if (empty($content['emoji'])) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'VALIDATION', 'message' => 'emoji is required.'],
      ], 400);
    }

    try {
      $userId = (int) $this->currentUser()->id();
      $this->messageService->addReaction($message_id, $userId, $content['emoji']);

      return new JsonResponse([
        'success' => TRUE,
        'data' => ['message_id' => $message_id, 'emoji' => $content['emoji']],
        'meta' => ['timestamp' => time()],
      ]);
    }
    catch (\Throwable $e) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'ERROR', 'message' => $e->getMessage()],
      ], 500);
    }
  }

}

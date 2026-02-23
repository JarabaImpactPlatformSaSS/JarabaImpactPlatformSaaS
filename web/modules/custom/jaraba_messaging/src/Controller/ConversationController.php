<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_messaging\Service\MessagingServiceInterface;
use Drupal\jaraba_messaging\Service\ConversationServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador REST API para conversaciones.
 *
 * Endpoints: list, create, view, update, close, archive, participants.
 * Todas las respuestas usan envelope: {success, data, meta}.
 */
class ConversationController extends ControllerBase {

  public function __construct(
    protected MessagingServiceInterface $messagingService,
    protected ConversationServiceInterface $conversationService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_messaging.messaging'),
      $container->get('jaraba_messaging.conversation'),
    );
  }

  /**
   * GET /api/v1/messaging/conversations — List user's conversations.
   */
  public function list(Request $request): JsonResponse {
    $limit = min(100, max(1, (int) $request->query->get('limit', 50)));
    $offset = max(0, (int) $request->query->get('offset', 0));
    $status = $request->query->get('status', 'active');

    $validStatuses = ['active', 'archived', 'closed'];
    if (!in_array($status, $validStatuses, TRUE)) {
      $status = 'active';
    }

    try {
      $data = $this->messagingService->getConversations($status, $limit, $offset);
      return new JsonResponse([
        'success' => TRUE,
        'data' => $data,
        'meta' => [
          'total' => count($data),
          'limit' => $limit,
          'offset' => $offset,
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
   * POST /api/v1/messaging/conversations — Create conversation.
   */
  public function createConversation(Request $request): JsonResponse {
    $content = json_decode($request->getContent(), TRUE);

    if (empty($content['participant_ids']) || !is_array($content['participant_ids'])) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'VALIDATION', 'message' => 'participant_ids is required and must be an array.'],
      ], 400);
    }

    try {
      $data = $this->messagingService->createConversation(
        array_map('intval', $content['participant_ids']),
        $content['title'] ?? '',
        $content['conversation_type'] ?? 'direct',
        $content['context_type'] ?? 'general',
        $content['context_id'] ?? NULL,
      );

      return new JsonResponse([
        'success' => TRUE,
        'data' => $data,
        'meta' => ['timestamp' => time()],
      ], 201);
    }
    catch (\Throwable $e) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'ERROR', 'message' => $e->getMessage()],
      ], 500);
    }
  }

  /**
   * GET /api/v1/messaging/conversations/{uuid} — View conversation.
   */
  public function view(string $uuid): JsonResponse {
    $conversation = $this->conversationService->getByUuid($uuid);
    if (!$conversation) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'NOT_FOUND', 'message' => 'Conversation not found.'],
      ], 404);
    }

    $participants = $this->conversationService->getParticipants((int) $conversation->id());
    $participantData = [];
    foreach ($participants as $p) {
      $user = $this->entityTypeManager()->getStorage('user')->load($p->getUserId());
      $participantData[] = [
        'user_id' => $p->getUserId(),
        'display_name' => $p->get('display_name')->value ?: ($user ? $user->getDisplayName() : ''),
        'role' => $p->getRole(),
        'status' => $p->getStatus(),
        'unread_count' => $p->getUnreadCount(),
      ];
    }

    return new JsonResponse([
      'success' => TRUE,
      'data' => [
        'id' => (int) $conversation->id(),
        'uuid' => $conversation->uuid(),
        'title' => $conversation->getTitle(),
        'conversation_type' => $conversation->getConversationType(),
        'context_type' => $conversation->get('context_type')->value,
        'context_id' => $conversation->get('context_id')->value,
        'status' => $conversation->getStatus(),
        'is_confidential' => $conversation->isConfidential(),
        'message_count' => $conversation->getMessageCount(),
        'participant_count' => $conversation->getParticipantCount(),
        'participants' => $participantData,
        'last_message_at' => $conversation->getLastMessageAt(),
        'last_message_preview' => $conversation->get('last_message_preview')->value,
        'created' => $conversation->get('created')->value,
      ],
      'meta' => ['timestamp' => time()],
    ]);
  }

  /**
   * PATCH /api/v1/messaging/conversations/{uuid} — Update conversation.
   */
  public function update(string $uuid, Request $request): JsonResponse {
    $conversation = $this->conversationService->getByUuid($uuid);
    if (!$conversation) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'NOT_FOUND', 'message' => 'Conversation not found.'],
      ], 404);
    }

    $content = json_decode($request->getContent(), TRUE);

    if (isset($content['title'])) {
      $conversation->set('title', $content['title']);
    }
    if (isset($content['is_confidential'])) {
      $conversation->set('is_confidential', (bool) $content['is_confidential']);
    }

    $conversation->save();

    return new JsonResponse([
      'success' => TRUE,
      'data' => ['uuid' => $uuid, 'updated' => TRUE],
      'meta' => ['timestamp' => time()],
    ]);
  }

  /**
   * POST /api/v1/messaging/conversations/{uuid}/close — Close conversation.
   */
  public function close(string $uuid): JsonResponse {
    $conversation = $this->conversationService->getByUuid($uuid);
    if (!$conversation) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'NOT_FOUND', 'message' => 'Conversation not found.'],
      ], 404);
    }

    $this->conversationService->close((int) $conversation->id());

    return new JsonResponse([
      'success' => TRUE,
      'data' => ['uuid' => $uuid, 'status' => 'closed'],
      'meta' => ['timestamp' => time()],
    ]);
  }

  /**
   * POST /api/v1/messaging/conversations/{uuid}/archive — Archive.
   */
  public function archive(string $uuid): JsonResponse {
    $conversation = $this->conversationService->getByUuid($uuid);
    if (!$conversation) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'NOT_FOUND', 'message' => 'Conversation not found.'],
      ], 404);
    }

    $userId = (int) $this->currentUser()->id();
    $this->conversationService->archive((int) $conversation->id(), $userId);

    return new JsonResponse([
      'success' => TRUE,
      'data' => ['uuid' => $uuid, 'status' => 'archived'],
      'meta' => ['timestamp' => time()],
    ]);
  }

  /**
   * GET /api/v1/messaging/conversations/{uuid}/participants — List participants.
   */
  public function participants(string $uuid): JsonResponse {
    $conversation = $this->conversationService->getByUuid($uuid);
    if (!$conversation) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'NOT_FOUND', 'message' => 'Conversation not found.'],
      ], 404);
    }

    $participants = $this->conversationService->getParticipants((int) $conversation->id());
    $data = [];
    foreach ($participants as $p) {
      $user = $this->entityTypeManager()->getStorage('user')->load($p->getUserId());
      $data[] = [
        'user_id' => $p->getUserId(),
        'display_name' => $user ? $user->getDisplayName() : '',
        'role' => $p->getRole(),
        'can_send' => $p->canSend(),
        'can_attach' => $p->canAttach(),
        'status' => $p->getStatus(),
        'joined_at' => $p->get('joined_at')->value,
      ];
    }

    return new JsonResponse([
      'success' => TRUE,
      'data' => $data,
      'meta' => ['timestamp' => time()],
    ]);
  }

}

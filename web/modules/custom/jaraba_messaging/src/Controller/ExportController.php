<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_messaging\Service\ConversationServiceInterface;
use Drupal\jaraba_messaging\Service\MessageServiceInterface;
use Drupal\jaraba_messaging\Service\MessageAuditServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controlador para exportación RGPD Art. 20 (portabilidad de datos).
 *
 * Permite al usuario exportar su historial de conversación en formato JSON
 * estructurado y portable.
 */
class ExportController extends ControllerBase {

  public function __construct(
    protected ConversationServiceInterface $conversationService,
    protected MessageServiceInterface $messageService,
    protected MessageAuditServiceInterface $auditService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_messaging.conversation'),
      $container->get('jaraba_messaging.message'),
      $container->get('jaraba_messaging.audit'),
    );
  }

  /**
   * POST /api/v1/messaging/conversations/{uuid}/export — Export conversation.
   */
  public function export(string $uuid): Response {
    $conversation = $this->conversationService->getByUuid($uuid);
    if (!$conversation) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'NOT_FOUND', 'message' => 'Conversation not found.'],
      ], 404);
    }

    $conversationId = (int) $conversation->id();
    $tenantId = (int) $conversation->getTenantId();
    $userId = (int) $this->currentUser()->id();

    try {
      // Get all messages (paginated in large batches).
      $allMessages = [];
      $offset = 0;
      $batchSize = 100;

      do {
        $messages = $this->messageService->getMessages($conversationId, $tenantId, $batchSize, NULL);
        $allMessages = array_merge($allMessages, $messages);
        $offset += $batchSize;
      } while (count($messages) === $batchSize && $offset < 10000);

      // Get participants.
      $participants = $this->conversationService->getParticipants($conversationId);
      $participantData = [];
      foreach ($participants as $p) {
        $user = $this->entityTypeManager()->getStorage('user')->load($p->getUserId());
        $participantData[] = [
          'user_id' => $p->getUserId(),
          'display_name' => $user ? $user->getDisplayName() : '',
          'role' => $p->getRole(),
          'joined_at' => $p->get('joined_at')->value,
        ];
      }

      // Build export data.
      $exportData = [
        'export_version' => '1.0',
        'exported_at' => date('c'),
        'exported_by' => $userId,
        'conversation' => [
          'uuid' => $conversation->uuid(),
          'title' => $conversation->getTitle(),
          'type' => $conversation->getConversationType(),
          'context_type' => $conversation->get('context_type')->value,
          'status' => $conversation->getStatus(),
          'created' => $conversation->get('created')->value,
          'message_count' => $conversation->getMessageCount(),
        ],
        'participants' => $participantData,
        'messages' => array_map(function ($msg) {
          return [
            'id' => $msg['id'] ?? NULL,
            'sender_id' => $msg['sender_id'] ?? NULL,
            'body' => $msg['body'] ?? '',
            'message_type' => $msg['message_type'] ?? 'text',
            'created_at' => $msg['created_at'] ?? NULL,
            'edited_at' => $msg['edited_at'] ?? NULL,
          ];
        }, $allMessages),
      ];

      // Log the export action.
      $this->auditService->log(
        $conversationId,
        $tenantId,
        'conversation.exported',
        NULL,
        ['exported_by' => $userId, 'message_count' => count($allMessages)],
      );

      // Return as downloadable JSON.
      $json = json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
      $filename = 'conversation_' . $conversation->uuid() . '_' . date('Y-m-d') . '.json';

      return new Response($json, 200, [
        'Content-Type' => 'application/json',
        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        'Content-Length' => strlen($json),
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

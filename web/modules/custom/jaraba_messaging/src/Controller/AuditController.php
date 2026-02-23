<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_messaging\Service\ConversationServiceInterface;
use Drupal\jaraba_messaging\Service\MessageAuditServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador REST API para audit trail de conversaciones.
 */
class AuditController extends ControllerBase {

  public function __construct(
    protected MessageAuditServiceInterface $auditService,
    protected ConversationServiceInterface $conversationService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_messaging.audit'),
      $container->get('jaraba_messaging.conversation'),
    );
  }

  /**
   * GET /api/v1/messaging/conversations/{uuid}/audit — Get audit log.
   */
  public function log(string $uuid, Request $request): JsonResponse {
    $conversation = $this->conversationService->getByUuid($uuid);
    if (!$conversation) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'NOT_FOUND', 'message' => 'Conversation not found.'],
      ], 404);
    }

    $limit = min(100, max(1, (int) $request->query->get('limit', 50)));
    $offset = max(0, (int) $request->query->get('offset', 0));

    try {
      $entries = $this->auditService->getLog((int) $conversation->id(), $limit, $offset);

      return new JsonResponse([
        'success' => TRUE,
        'data' => $entries,
        'meta' => [
          'total' => count($entries),
          'limit' => $limit,
          'offset' => $offset,
          'conversation_uuid' => $uuid,
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
   * GET /api/v1/messaging/conversations/{uuid}/audit/verify — Verify integrity.
   */
  public function verify(string $uuid): JsonResponse {
    $conversation = $this->conversationService->getByUuid($uuid);
    if (!$conversation) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'NOT_FOUND', 'message' => 'Conversation not found.'],
      ], 404);
    }

    try {
      $report = $this->auditService->verifyIntegrity((int) $conversation->id());

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'valid' => $report->valid,
          'total_entries' => $report->totalEntries,
          'broken_at' => $report->brokenAt,
          'details' => $report->details,
        ],
        'meta' => [
          'conversation_uuid' => $uuid,
          'verified_at' => time(),
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

}

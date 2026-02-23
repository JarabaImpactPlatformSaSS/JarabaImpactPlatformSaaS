<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_messaging\Service\ConversationServiceInterface;
use Drupal\jaraba_messaging\Service\SearchServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador REST API para búsqueda en mensajes.
 */
class SearchController extends ControllerBase {

  public function __construct(
    protected SearchServiceInterface $searchService,
    protected ConversationServiceInterface $conversationService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_messaging.search'),
      $container->get('jaraba_messaging.conversation'),
    );
  }

  /**
   * GET /api/v1/messaging/search — Search messages.
   */
  public function search(Request $request): JsonResponse {
    $query = trim((string) $request->query->get('q', ''));
    $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
    $offset = max(0, (int) $request->query->get('offset', 0));
    $conversationUuid = $request->query->get('conversation');

    if (empty($query)) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'VALIDATION', 'message' => 'Query parameter q is required.'],
      ], 400);
    }

    if (mb_strlen($query) < 2) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'VALIDATION', 'message' => 'Query must be at least 2 characters.'],
      ], 400);
    }

    try {
      // Resolve tenant from current user context.
      $userId = (int) $this->currentUser()->id();
      $conversations = $this->conversationService->listForUser($userId, 'active', 1000, 0);

      if (empty($conversations)) {
        return new JsonResponse([
          'success' => TRUE,
          'data' => [],
          'meta' => ['total' => 0, 'query' => $query],
        ]);
      }

      // Filter to specific conversation if requested.
      $conversationIds = [];
      $tenantId = 0;
      foreach ($conversations as $conv) {
        if ($tenantId === 0) {
          $tenantId = (int) $conv->getTenantId();
        }
        if ($conversationUuid && $conv->uuid() !== $conversationUuid) {
          continue;
        }
        $conversationIds[] = (int) $conv->id();
      }

      $results = $this->searchService->search($query, $tenantId, $conversationIds, $limit, $offset);

      return new JsonResponse([
        'success' => TRUE,
        'data' => $results,
        'meta' => [
          'total' => count($results),
          'query' => $query,
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

}

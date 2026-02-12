<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_copilot_v2\Service\CopilotQueryLoggerService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * API controller para historial de sesiones del copiloto.
 */
class CopilotHistoryController extends ControllerBase {

  protected CopilotQueryLoggerService $queryLogger;

  /**
   * Constructor.
   */
  public function __construct(CopilotQueryLoggerService $queryLogger) {
    $this->queryLogger = $queryLogger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_copilot_v2.query_logger'),
    );
  }

  /**
   * GET /api/v1/copilot/history/{sessionId} - Lista mensajes de sesion.
   */
  public function getHistory(string $sessionId): JsonResponse {
    try {
      $messages = $this->queryLogger->getSessionHistory($sessionId);

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'session_id' => $sessionId,
          'messages' => $messages,
          'count' => count($messages),
        ],
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => $e->getMessage(),
      ], 500);
    }
  }

}

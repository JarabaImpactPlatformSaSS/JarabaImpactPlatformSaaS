<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_lexnet\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_legal_lexnet\Service\LexnetSubmissionService;
use Drupal\jaraba_legal_lexnet\Service\LexnetSyncService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API REST para notificaciones y presentaciones LexNET.
 *
 * Estructura: API-NAMING-001 — POST store(), GET list/detail.
 * Logica: 8 endpoints para notificaciones y presentaciones.
 */
class LexnetApiController extends ControllerBase {

  public function __construct(
    protected readonly LexnetSyncService $syncService,
    protected readonly LexnetSubmissionService $submissionService,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_legal_lexnet.sync'),
      $container->get('jaraba_legal_lexnet.submission'),
      $container->get('entity_type.manager'),
      $container->get('logger.channel.jaraba_legal_lexnet'),
    );
  }

  /**
   * GET /api/v1/legal/lexnet/notifications
   */
  public function listNotifications(Request $request): JsonResponse {
    $filters = [];
    if ($status = $request->query->get('status')) {
      $filters['status'] = $status;
    }
    if ($caseId = $request->query->get('case_id')) {
      $filters['case_id'] = (int) $caseId;
    }

    $limit = min((int) $request->query->get('limit', 25), 100);
    $offset = max((int) $request->query->get('offset', 0), 0);

    $result = $this->syncService->listNotifications($filters, $limit, $offset);

    return new JsonResponse([
      'data' => $result['items'],
      'meta' => ['total' => $result['total'], 'limit' => $limit, 'offset' => $offset],
    ]);
  }

  /**
   * POST /api/v1/legal/lexnet/sync
   */
  public function forceSync(): JsonResponse {
    $result = $this->syncService->fetchNotifications();

    if (isset($result['error'])) {
      return new JsonResponse(['error' => $result['error']], 500);
    }

    return new JsonResponse(['data' => $result]);
  }

  /**
   * POST /api/v1/legal/lexnet/notifications/{id}/link/{case_uuid}
   */
  public function linkToCase(int $id, string $case_uuid): JsonResponse {
    try {
      $notification = $this->entityTypeManager->getStorage('lexnet_notification')->load($id);
      if (!$notification) {
        return new JsonResponse(['error' => 'Notificacion no encontrada.'], 404);
      }

      $cases = $this->entityTypeManager->getStorage('client_case')
        ->loadByProperties(['uuid' => $case_uuid]);
      $case = reset($cases);
      if (!$case) {
        return new JsonResponse(['error' => 'Expediente no encontrado.'], 404);
      }

      $notification->set('case_id', $case->id());
      if ($notification->get('status')->value === 'pending' || $notification->get('status')->value === 'read') {
        $notification->set('status', 'linked');
      }
      $notification->save();

      // Log activity on the case.
      if (\Drupal::hasService('jaraba_legal_cases.activity_logger')) {
        $logger = \Drupal::service('jaraba_legal_cases.activity_logger');
        if (method_exists($logger, 'log')) {
          $logger->log($case->id(), 'lexnet_notification', [
            'notification_id' => $id,
            'subject' => $notification->get('subject')->value,
            'court' => $notification->get('court')->value,
          ]);
        }
      }

      return new JsonResponse(['data' => $this->syncService->serializeNotification($notification)]);
    }
    catch (\Exception $e) {
      return new JsonResponse(['error' => $e->getMessage()], 500);
    }
  }

  /**
   * POST /api/v1/legal/lexnet/notifications/{id}/acknowledge
   */
  public function acknowledge(int $id): JsonResponse {
    $result = $this->syncService->acknowledgeNotification($id);

    if (isset($result['error'])) {
      return new JsonResponse(['error' => $result['error']], 422);
    }

    return new JsonResponse(['data' => $result]);
  }

  /**
   * POST /api/v1/legal/lexnet/submissions
   */
  public function storeSubmission(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE) ?? [];
    if (empty($data['case_id']) || empty($data['subject']) || empty($data['court'])) {
      return new JsonResponse(['error' => 'Campos requeridos: case_id, subject, court.'], 422);
    }

    try {
      $storage = $this->entityTypeManager->getStorage('lexnet_submission');
      $submission = $storage->create([
        'uid' => $this->currentUser()->id(),
        'tenant_id' => $data['tenant_id'] ?? NULL,
        'case_id' => $data['case_id'],
        'submission_type' => $data['submission_type'] ?? 'escrito',
        'court' => $data['court'],
        'procedure_number' => $data['procedure_number'] ?? '',
        'subject' => $data['subject'],
        'document_ids' => $data['document_ids'] ?? [],
        'status' => 'draft',
      ]);
      $submission->save();

      return new JsonResponse(['data' => $this->submissionService->serializeSubmission($submission)], 201);
    }
    catch (\Exception $e) {
      return new JsonResponse(['error' => $e->getMessage()], 500);
    }
  }

  /**
   * GET /api/v1/legal/lexnet/submissions
   */
  public function listSubmissions(Request $request): JsonResponse {
    $filters = [];
    if ($status = $request->query->get('status')) {
      $filters['status'] = $status;
    }
    if ($caseId = $request->query->get('case_id')) {
      $filters['case_id'] = (int) $caseId;
    }

    $limit = min((int) $request->query->get('limit', 25), 100);
    $offset = max((int) $request->query->get('offset', 0), 0);

    $result = $this->submissionService->listSubmissions($filters, $limit, $offset);

    return new JsonResponse([
      'data' => $result['items'],
      'meta' => ['total' => $result['total'], 'limit' => $limit, 'offset' => $offset],
    ]);
  }

  /**
   * POST /api/v1/legal/lexnet/submissions/{id}/submit
   */
  public function submitToLexnet(int $id): JsonResponse {
    $result = $this->submissionService->submit($id);

    if (isset($result['error'])) {
      return new JsonResponse(['error' => $result['error']], 422);
    }

    return new JsonResponse(['data' => $result]);
  }

  /**
   * GET /api/v1/legal/lexnet/submissions/{id}/status
   */
  public function checkStatus(int $id): JsonResponse {
    $result = $this->submissionService->checkStatus($id);

    if (isset($result['error'])) {
      return new JsonResponse(['error' => $result['error']], 422);
    }

    return new JsonResponse(['data' => $result]);
  }

}

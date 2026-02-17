<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_vault\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_legal_vault\Service\DocumentAccessService;
use Drupal\jaraba_legal_vault\Service\DocumentVaultService;
use Drupal\jaraba_legal_vault\Service\VaultAuditLogService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller del Portal Cliente (token-based, FASE B2).
 *
 * Estructura: 7 endpoints JSON accesibles mediante client_access_token
 *   del expediente ClientCase. No requieren autenticacion Drupal.
 *
 * Logica: El token identifica al expediente y al cliente. Permite
 *   ver datos del caso, documentos pendientes, subir documentos
 *   solicitados, descargar entregas y confirmar recepcion.
 */
class PortalApiController extends ControllerBase {

  /**
   * Constructor.
   */
  public function __construct(
    protected DocumentVaultService $vaultService,
    protected DocumentAccessService $accessService,
    protected VaultAuditLogService $auditService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_legal_vault.document_vault'),
      $container->get('jaraba_legal_vault.document_access'),
      $container->get('jaraba_legal_vault.audit_log'),
    );
  }

  /**
   * GET /api/v1/portal/{token} — Datos del expediente para el cliente.
   */
  public function overview(string $token): JsonResponse {
    $case = $this->loadCaseByToken($token);
    if (!$case) {
      return new JsonResponse(['error' => 'Token invalido o expediente no encontrado.'], 403);
    }

    return new JsonResponse([
      'data' => [
        'case_number' => $case->get('case_number')->value ?? '',
        'title' => $case->get('title')->value ?? '',
        'status' => $case->get('status')->value ?? '',
        'provider_name' => $case->get('provider_id')->entity?->getDisplayName() ?? '',
        'opened_at' => $case->get('opened_at')->value ?? $case->get('created')->value,
      ],
    ]);
  }

  /**
   * GET /api/v1/portal/{token}/requests — Documentos pendientes de subir.
   */
  public function listRequests(string $token): JsonResponse {
    $case = $this->loadCaseByToken($token);
    if (!$case) {
      return new JsonResponse(['error' => 'Token invalido.'], 403);
    }

    try {
      $storage = $this->entityTypeManager()->getStorage('document_request');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('case_id', $case->id())
        ->sort('created', 'DESC')
        ->execute();

      $data = [];
      foreach ($storage->loadMultiple($ids) as $req) {
        $data[] = [
          'id' => (int) $req->id(),
          'title' => $req->get('title')->value ?? '',
          'instructions' => $req->get('instructions')->value ?? '',
          'is_required' => (bool) $req->get('is_required')->value,
          'deadline' => $req->get('deadline')->value,
          'status' => $req->get('status')->value ?? 'pending',
          'rejection_reason' => $req->get('rejection_reason')->value,
        ];
      }

      return new JsonResponse(['data' => $data]);
    }
    catch (\Exception $e) {
      return new JsonResponse(['error' => 'Error al cargar solicitudes.'], 500);
    }
  }

  /**
   * POST /api/v1/portal/{token}/requests/{id}/upload — Subir documento.
   */
  public function uploadDocument(string $token, int $id, Request $request): JsonResponse {
    $case = $this->loadCaseByToken($token);
    if (!$case) {
      return new JsonResponse(['error' => 'Token invalido.'], 403);
    }

    try {
      $reqStorage = $this->entityTypeManager()->getStorage('document_request');
      $docRequest = $reqStorage->load($id);

      if (!$docRequest || (int) $docRequest->get('case_id')->target_id !== (int) $case->id()) {
        return new JsonResponse(['error' => 'Solicitud no encontrada.'], 404);
      }

      $file = $request->files->get('file');
      if (!$file) {
        return new JsonResponse(['error' => 'El campo file es obligatorio.'], 400);
      }

      $content = file_get_contents($file->getPathname());
      if ($content === FALSE) {
        return new JsonResponse(['error' => 'No se pudo leer el archivo.'], 400);
      }

      // Almacenar en vault cifrado.
      $result = $this->vaultService->store(
        $content,
        $docRequest->get('title')->value ?? $file->getClientOriginalName(),
        $file->getClientOriginalName(),
        $file->getClientMimeType(),
        (int) $case->id()
      );

      if (!$result['success']) {
        return new JsonResponse(['error' => 'Error al almacenar documento.'], 500);
      }

      // Actualizar solicitud.
      $docRequest->set('status', 'uploaded');
      $docRequest->set('uploaded_document_id', $result['document']->id());
      $docRequest->save();

      return new JsonResponse(['data' => ['uploaded' => TRUE, 'request_id' => $id]], 201);
    }
    catch (\Exception $e) {
      return new JsonResponse(['error' => 'Error al procesar upload.'], 500);
    }
  }

  /**
   * GET /api/v1/portal/{token}/deliveries — Documentos entregados.
   */
  public function listDeliveries(string $token): JsonResponse {
    $case = $this->loadCaseByToken($token);
    if (!$case) {
      return new JsonResponse(['error' => 'Token invalido.'], 403);
    }

    try {
      $storage = $this->entityTypeManager()->getStorage('document_delivery');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('case_id', $case->id())
        ->sort('created', 'DESC')
        ->execute();

      $data = [];
      foreach ($storage->loadMultiple($ids) as $delivery) {
        $doc = $delivery->get('document_id')->entity;
        $data[] = [
          'id' => (int) $delivery->id(),
          'document_title' => $doc ? ($doc->get('title')->value ?? '') : '',
          'message' => $delivery->get('message')->value ?? '',
          'status' => $delivery->get('status')->value ?? 'sent',
          'requires_acknowledgment' => (bool) $delivery->get('requires_acknowledgment')->value,
          'requires_signature' => (bool) $delivery->get('requires_signature')->value,
          'created' => $delivery->get('created')->value,
        ];
      }

      return new JsonResponse(['data' => $data]);
    }
    catch (\Exception $e) {
      return new JsonResponse(['error' => 'Error al cargar entregas.'], 500);
    }
  }

  /**
   * GET /api/v1/portal/{token}/deliveries/{id}/download — Descargar.
   */
  public function download(string $token, int $id): JsonResponse {
    $case = $this->loadCaseByToken($token);
    if (!$case) {
      return new JsonResponse(['error' => 'Token invalido.'], 403);
    }

    try {
      $storage = $this->entityTypeManager()->getStorage('document_delivery');
      $delivery = $storage->load($id);

      if (!$delivery || (int) $delivery->get('case_id')->target_id !== (int) $case->id()) {
        return new JsonResponse(['error' => 'Entrega no encontrada.'], 404);
      }

      $docId = (int) $delivery->get('document_id')->target_id;
      $result = $this->vaultService->retrieve($docId);

      if (!$result['success']) {
        return new JsonResponse(['error' => $result['error'] ?? 'Error al recuperar documento.'], 500);
      }

      // Actualizar tracking.
      $delivery->set('status', 'downloaded');
      $delivery->set('downloaded_at', date('Y-m-d\TH:i:s'));
      $count = (int) $delivery->get('download_count')->value;
      $delivery->set('download_count', $count + 1);
      $delivery->save();

      $this->auditService->log($docId, 'downloaded', ['via' => 'portal', 'delivery_id' => $id]);

      return new JsonResponse([
        'data' => [
          'content' => base64_encode($result['content']),
          'filename' => $result['document']->get('original_filename')->value,
          'mime_type' => $result['document']->get('mime_type')->value,
        ],
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse(['error' => 'Error al descargar.'], 500);
    }
  }

  /**
   * POST /api/v1/portal/{token}/deliveries/{id}/acknowledge — Confirmar.
   */
  public function acknowledge(string $token, int $id): JsonResponse {
    $case = $this->loadCaseByToken($token);
    if (!$case) {
      return new JsonResponse(['error' => 'Token invalido.'], 403);
    }

    try {
      $storage = $this->entityTypeManager()->getStorage('document_delivery');
      $delivery = $storage->load($id);

      if (!$delivery || (int) $delivery->get('case_id')->target_id !== (int) $case->id()) {
        return new JsonResponse(['error' => 'Entrega no encontrada.'], 404);
      }

      $delivery->set('status', 'acknowledged');
      $delivery->set('acknowledged_at', date('Y-m-d\TH:i:s'));
      $delivery->save();

      $docId = (int) $delivery->get('document_id')->target_id;
      $this->auditService->log($docId, 'viewed', ['via' => 'portal', 'acknowledged' => TRUE]);

      return new JsonResponse(['data' => ['acknowledged' => TRUE]]);
    }
    catch (\Exception $e) {
      return new JsonResponse(['error' => 'Error al confirmar recepcion.'], 500);
    }
  }

  /**
   * GET /api/v1/portal/{token}/activity — Historial cliente.
   */
  public function activity(string $token): JsonResponse {
    $case = $this->loadCaseByToken($token);
    if (!$case) {
      return new JsonResponse(['error' => 'Token invalido.'], 403);
    }

    try {
      $activityStorage = $this->entityTypeManager()->getStorage('case_activity');
      $ids = $activityStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('case_id', $case->id())
        ->condition('is_visible_to_client', TRUE)
        ->sort('created', 'DESC')
        ->range(0, 50)
        ->execute();

      $data = [];
      foreach ($activityStorage->loadMultiple($ids) as $activity) {
        $data[] = [
          'activity_type' => $activity->get('activity_type')->value,
          'description' => $activity->get('description')->value ?? '',
          'actor_role' => $activity->get('actor_role')->value ?? 'system',
          'created' => $activity->get('created')->value,
        ];
      }

      return new JsonResponse(['data' => $data]);
    }
    catch (\Exception $e) {
      return new JsonResponse(['error' => 'Error al cargar actividad.'], 500);
    }
  }

  /**
   * Carga un expediente por su client_access_token.
   */
  protected function loadCaseByToken(string $token): ?object {
    try {
      $storage = $this->entityTypeManager()->getStorage('client_case');
      $entities = $storage->loadByProperties(['client_access_token' => $token]);
      return !empty($entities) ? reset($entities) : NULL;
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

}

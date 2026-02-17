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
 * Controller de la API REST de la Boveda Documental.
 *
 * Estructura: 15 endpoints JSON para documentos cifrados, comparticion,
 *   auditoria, versiones, acceso por token y exportacion RGPD.
 *
 * Logica: Cada endpoint retorna JsonResponse con la estructura estandar
 *   del ecosistema: { data } / { data, meta } / { error }.
 */
class VaultApiController extends ControllerBase {

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
   * GET /api/v1/vault/documents — Listar documentos.
   */
  public function listDocuments(Request $request): JsonResponse {
    $filters = array_filter([
      'status' => $request->query->get('status'),
      'case_id' => $request->query->get('case_id'),
    ]);
    $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
    $offset = max(0, (int) $request->query->get('offset', 0));

    $result = $this->vaultService->listDocuments($filters, $limit, $offset);

    $data = [];
    foreach ($result['documents'] as $doc) {
      $data[] = $this->serializeDocument($doc);
    }

    return new JsonResponse([
      'data' => $data,
      'meta' => ['total' => $result['total'], 'limit' => $limit, 'offset' => $offset],
    ]);
  }

  /**
   * POST /api/v1/vault/documents — Subir documento cifrado (store).
   */
  public function store(Request $request): JsonResponse {
    $title = $request->request->get('title');
    $file = $request->files->get('file');

    if (!$title || !$file) {
      return new JsonResponse(['error' => 'Los campos title y file son obligatorios.'], 400);
    }

    $content = file_get_contents($file->getPathname());
    if ($content === FALSE) {
      return new JsonResponse(['error' => 'No se pudo leer el archivo.'], 400);
    }

    $result = $this->vaultService->store(
      $content,
      $title,
      $file->getClientOriginalName(),
      $file->getClientMimeType(),
      $request->request->get('case_id') ? (int) $request->request->get('case_id') : NULL,
      $request->request->get('category_tid') ? (int) $request->request->get('category_tid') : NULL,
    );

    if (!$result['success']) {
      return new JsonResponse(['error' => $result['error'] ?? 'Error al almacenar documento.'], 500);
    }

    return new JsonResponse(['data' => $this->serializeDocument($result['document'])], 201);
  }

  /**
   * GET /api/v1/vault/documents/{uuid} — Detalle de documento.
   */
  public function detail(string $uuid): JsonResponse {
    $document = $this->vaultService->getDocumentByUuid($uuid);
    if (!$document) {
      return new JsonResponse(['error' => 'Documento no encontrado.'], 404);
    }
    return new JsonResponse(['data' => $this->serializeDocument($document)]);
  }

  /**
   * DELETE /api/v1/vault/documents/{uuid} — Soft delete.
   */
  public function delete(string $uuid): JsonResponse {
    $document = $this->vaultService->getDocumentByUuid($uuid);
    if (!$document) {
      return new JsonResponse(['error' => 'Documento no encontrado.'], 404);
    }

    $result = $this->vaultService->softDelete((int) $document->id());
    if (!$result['success']) {
      return new JsonResponse(['error' => $result['error'] ?? 'Error al eliminar.'], 500);
    }

    return new JsonResponse(['data' => ['deleted' => TRUE]]);
  }

  /**
   * POST /api/v1/vault/documents/{uuid}/versions — Nueva version.
   */
  public function storeVersion(string $uuid, Request $request): JsonResponse {
    $document = $this->vaultService->getDocumentByUuid($uuid);
    if (!$document) {
      return new JsonResponse(['error' => 'Documento no encontrado.'], 404);
    }

    $file = $request->files->get('file');
    if (!$file) {
      return new JsonResponse(['error' => 'El campo file es obligatorio.'], 400);
    }

    $content = file_get_contents($file->getPathname());
    if ($content === FALSE) {
      return new JsonResponse(['error' => 'No se pudo leer el archivo.'], 400);
    }

    $result = $this->vaultService->createVersion(
      (int) $document->id(),
      $content,
      $file->getClientOriginalName(),
      $file->getClientMimeType()
    );

    if (!$result['success']) {
      return new JsonResponse(['error' => $result['error'] ?? 'Error al crear version.'], 500);
    }

    return new JsonResponse(['data' => $this->serializeDocument($result['document'])], 201);
  }

  /**
   * GET /api/v1/vault/documents/{uuid}/versions — Listar versiones.
   */
  public function listVersions(string $uuid): JsonResponse {
    $document = $this->vaultService->getDocumentByUuid($uuid);
    if (!$document) {
      return new JsonResponse(['error' => 'Documento no encontrado.'], 404);
    }

    $versions = $this->vaultService->getVersions((int) $document->id());
    $data = array_map(fn($v) => $this->serializeDocument($v), $versions);

    return new JsonResponse(['data' => $data]);
  }

  /**
   * POST /api/v1/vault/documents/{uuid}/share — Compartir documento.
   */
  public function share(string $uuid, Request $request): JsonResponse {
    $document = $this->vaultService->getDocumentByUuid($uuid);
    if (!$document) {
      return new JsonResponse(['error' => 'Documento no encontrado.'], 404);
    }

    $content = json_decode($request->getContent(), TRUE);

    $result = $this->accessService->shareDocument(
      (int) $document->id(),
      !empty($content['grantee_id']) ? (int) $content['grantee_id'] : NULL,
      $content['grantee_email'] ?? NULL,
      $content['permissions'] ?? ['view', 'download'],
      !empty($content['max_downloads']) ? (int) $content['max_downloads'] : NULL,
      $content['expires_at'] ?? NULL,
      $content['requires_auth'] ?? TRUE,
    );

    if (!$result['success']) {
      return new JsonResponse(['error' => $result['error'] ?? 'Error al compartir.'], 500);
    }

    return new JsonResponse([
      'data' => [
        'access_id' => (int) $result['access']->id(),
        'token' => $result['token'],
      ],
    ], 201);
  }

  /**
   * GET /api/v1/vault/documents/{uuid}/access — Listar accesos.
   */
  public function listAccess(string $uuid): JsonResponse {
    $document = $this->vaultService->getDocumentByUuid($uuid);
    if (!$document) {
      return new JsonResponse(['error' => 'Documento no encontrado.'], 404);
    }

    $grants = $this->accessService->listAccessGrants((int) $document->id());
    $data = [];
    foreach ($grants as $grant) {
      $grantee = $grant->get('grantee_id')->entity;
      $data[] = [
        'id' => (int) $grant->id(),
        'grantee_id' => $grant->get('grantee_id')->target_id,
        'grantee_email' => $grant->get('grantee_email')->value ?? '',
        'grantee_name' => $grantee ? $grantee->getDisplayName() : '',
        'permissions' => $grant->get('permissions')->first()?->getValue() ?? [],
        'max_downloads' => $grant->get('max_downloads')->value,
        'download_count' => (int) $grant->get('download_count')->value,
        'expires_at' => $grant->get('expires_at')->value,
        'created' => $grant->get('created')->value,
      ];
    }

    return new JsonResponse(['data' => $data]);
  }

  /**
   * DELETE /api/v1/vault/access/{id} — Revocar acceso.
   */
  public function revokeAccess(int $id): JsonResponse {
    $result = $this->accessService->revokeAccess($id);
    if (!$result['success']) {
      return new JsonResponse(['error' => $result['error'] ?? 'Error al revocar.'], 500);
    }
    return new JsonResponse(['data' => ['revoked' => TRUE]]);
  }

  /**
   * DELETE /api/v1/vault/documents/{uuid}/access/all — Revocar todos.
   */
  public function revokeAllAccess(string $uuid): JsonResponse {
    $document = $this->vaultService->getDocumentByUuid($uuid);
    if (!$document) {
      return new JsonResponse(['error' => 'Documento no encontrado.'], 404);
    }

    $result = $this->accessService->revokeAll((int) $document->id());
    if (!$result['success']) {
      return new JsonResponse(['error' => $result['error'] ?? 'Error al revocar.'], 500);
    }

    return new JsonResponse(['data' => ['revoked_count' => $result['revoked_count']]]);
  }

  /**
   * GET /api/v1/vault/documents/{uuid}/audit — Auditoria.
   */
  public function audit(string $uuid): JsonResponse {
    $document = $this->vaultService->getDocumentByUuid($uuid);
    if (!$document) {
      return new JsonResponse(['error' => 'Documento no encontrado.'], 404);
    }

    $trail = $this->auditService->getAuditTrail((int) $document->id());
    $data = [];
    foreach ($trail['entries'] as $entry) {
      $actor = $entry->get('actor_id')->entity;
      $data[] = [
        'id' => (int) $entry->id(),
        'action' => $entry->get('action')->value,
        'actor' => $actor ? $actor->getDisplayName() : 'Sistema',
        'actor_ip' => $entry->get('actor_ip')->value ?? '',
        'details' => $entry->get('details')->first()?->getValue() ?? [],
        'hash_chain' => $entry->get('hash_chain')->value,
        'created' => $entry->get('created')->value,
      ];
    }

    return new JsonResponse(['data' => $data, 'meta' => ['total' => $trail['total']]]);
  }

  /**
   * GET /api/v1/vault/shared — Documentos compartidos conmigo.
   */
  public function sharedWithMe(Request $request): JsonResponse {
    $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
    $offset = max(0, (int) $request->query->get('offset', 0));

    $result = $this->accessService->getSharedWithMe($limit, $offset);
    $data = array_map(fn($doc) => $this->serializeDocument($doc), $result['documents']);

    return new JsonResponse([
      'data' => $data,
      'meta' => ['total' => $result['total'], 'limit' => $limit, 'offset' => $offset],
    ]);
  }

  /**
   * GET /api/v1/vault/access/token/{token} — Acceso publico por token.
   */
  public function accessByToken(string $token): JsonResponse {
    $result = $this->accessService->validateToken($token);

    if (!$result['valid']) {
      return new JsonResponse(['error' => $result['error'] ?? 'Token invalido.'], 403);
    }

    return new JsonResponse([
      'data' => $this->serializeDocument($result['document']),
    ]);
  }

  /**
   * GET /api/v1/vault/export — Exportacion RGPD.
   */
  public function export(Request $request): JsonResponse {
    $result = $this->vaultService->listDocuments([], 1000, 0);
    $format = $request->query->get('format', 'summary');

    $data = [];
    foreach ($result['documents'] as $doc) {
      $item = $this->serializeDocument($doc);
      if ($format === 'json') {
        $trail = $this->auditService->getAuditTrail((int) $doc->id(), 100);
        $item['audit_log'] = [];
        foreach ($trail['entries'] as $entry) {
          $item['audit_log'][] = [
            'action' => $entry->get('action')->value,
            'created' => $entry->get('created')->value,
          ];
        }
      }
      $data[] = $item;
    }

    return new JsonResponse([
      'data' => $data,
      'meta' => ['total' => $result['total'], 'export_format' => $format],
    ]);
  }

  /**
   * Serializa un documento seguro para la API.
   */
  protected function serializeDocument(object $doc): array {
    $owner = $doc->get('owner_id')->entity;
    return [
      'id' => (int) $doc->id(),
      'uuid' => $doc->uuid(),
      'title' => $doc->get('title')->value ?? '',
      'original_filename' => $doc->get('original_filename')->value ?? '',
      'mime_type' => $doc->get('mime_type')->value ?? '',
      'file_size' => (int) ($doc->get('file_size')->value ?? 0),
      'content_hash' => $doc->get('content_hash')->value ?? '',
      'version' => (int) ($doc->get('version')->value ?? 1),
      'is_signed' => (bool) $doc->get('is_signed')->value,
      'status' => $doc->get('status')->value ?? '',
      'case_id' => $doc->get('case_id')->target_id,
      'owner' => $owner ? ['id' => (int) $owner->id(), 'name' => $owner->getDisplayName()] : NULL,
      'expires_at' => $doc->get('expires_at')->value,
      'created' => $doc->get('created')->value,
      'changed' => $doc->get('changed')->value,
    ];
  }

}

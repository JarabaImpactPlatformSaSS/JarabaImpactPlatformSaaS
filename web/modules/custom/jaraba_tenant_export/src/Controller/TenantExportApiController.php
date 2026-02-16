<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_export\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_tenant_export\Service\TenantDataCollectorService;
use Drupal\jaraba_tenant_export\Service\TenantExportService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * API REST para exportaciones de tenant.
 *
 * Envelope estándar: {success, data, error, message} (AUDIT-CONS-003).
 */
class TenantExportApiController extends ControllerBase {

  public function __construct(
    protected TenantContextService $tenantContext,
    protected TenantExportService $exportService,
    protected TenantDataCollectorService $dataCollector,
    protected LoggerInterface $logger,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('ecosistema_jaraba_core.tenant_context'),
      $container->get('jaraba_tenant_export.export_service'),
      $container->get('jaraba_tenant_export.data_collector'),
      $container->get('logger.channel.jaraba_tenant_export'),
    );
  }

  /**
   * POST /api/v1/tenant-export/request — Solicitar exportación.
   */
  public function requestExport(Request $request): JsonResponse {
    $tenant = $this->tenantContext->getCurrentTenant();
    if (!$tenant) {
      return $this->errorResponse('No tenant context found.', 403);
    }

    $body = json_decode($request->getContent(), TRUE) ?: [];
    $type = $body['type'] ?? 'full';
    $sections = $body['sections'] ?? [];

    if (!in_array($type, ['full', 'partial', 'gdpr_portability'], TRUE)) {
      return $this->errorResponse('Invalid export type.', 400);
    }

    $groupId = (int) $tenant->id();
    $tenantEntityId = (int) ($tenant->get('tenant_entity_id')->target_id ?? $tenant->id());
    $userId = (int) $this->currentUser()->id();

    $result = $this->exportService->requestExport($groupId, $tenantEntityId, $userId, $type, $sections);

    if (!$result['success']) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => $result['error'] ?? 'request_failed',
        'message' => $result['message'] ?? 'Export request failed.',
        'data' => ['retry_after' => $result['retry_after'] ?? 0],
      ], 429);
    }

    return new JsonResponse([
      'success' => TRUE,
      'data' => ['record_id' => $result['record_id']],
      'message' => $result['message'],
    ]);
  }

  /**
   * GET /api/v1/tenant-export/{id}/status — Estado y progreso.
   */
  public function getStatus(int $id): JsonResponse {
    $storage = $this->entityTypeManager()->getStorage('tenant_export_record');
    $record = $storage->load($id);

    if (!$record) {
      return $this->errorResponse('Export record not found.', 404);
    }

    return new JsonResponse([
      'success' => TRUE,
      'data' => [
        'id' => (int) $record->id(),
        'status' => $record->get('status')->value,
        'status_label' => $record->getStatusLabel(),
        'progress' => $record->getProgress(),
        'current_phase' => $record->get('current_phase')->value ?? '',
        'file_size' => (int) ($record->get('file_size')->value ?? 0),
        'file_hash' => $record->get('file_hash')->value ?? '',
        'download_token' => $record->isDownloadable() ? $record->get('download_token')->value : NULL,
        'is_downloadable' => $record->isDownloadable(),
        'download_count' => (int) ($record->get('download_count')->value ?? 0),
        'error_message' => $record->get('error_message')->value ?? NULL,
        'created' => (int) $record->get('created')->value,
        'completed_at' => (int) ($record->get('completed_at')->value ?? 0),
        'expires_at' => (int) ($record->get('expires_at')->value ?? 0),
      ],
    ]);
  }

  /**
   * GET /api/v1/tenant-export/{token}/download — Descargar ZIP.
   */
  public function download(string $token): JsonResponse|StreamedResponse {
    $response = $this->exportService->getDownloadResponse($token);

    if (!$response) {
      return $this->errorResponse('Export not found or expired.', 404);
    }

    return $response;
  }

  /**
   * POST /api/v1/tenant-export/{id}/cancel — Cancelar exportación.
   */
  public function cancel(int $id): JsonResponse {
    $storage = $this->entityTypeManager()->getStorage('tenant_export_record');
    $record = $storage->load($id);

    if (!$record) {
      return $this->errorResponse('Export record not found.', 404);
    }

    $status = $record->get('status')->value;
    if (in_array($status, ['completed', 'failed', 'expired', 'cancelled'], TRUE)) {
      return $this->errorResponse('Cannot cancel an export in status: ' . $status, 400);
    }

    $record->set('status', 'cancelled');
    $record->save();

    return new JsonResponse([
      'success' => TRUE,
      'message' => (string) $this->t('Exportación cancelada.'),
    ]);
  }

  /**
   * GET /api/v1/tenant-export/history — Historial del tenant.
   */
  public function history(): JsonResponse {
    $tenant = $this->tenantContext->getCurrentTenant();
    if (!$tenant) {
      return $this->errorResponse('No tenant context found.', 403);
    }

    $groupId = (int) $tenant->id();
    $exports = $this->exportService->getExportHistory($groupId);

    return new JsonResponse([
      'success' => TRUE,
      'data' => $exports,
    ]);
  }

  /**
   * GET /api/v1/tenant-export/sections — Secciones disponibles.
   */
  public function sections(): JsonResponse {
    $sections = $this->dataCollector->getAvailableSections();

    return new JsonResponse([
      'success' => TRUE,
      'data' => $sections,
    ]);
  }

  /**
   * Respuesta de error estándar.
   */
  protected function errorResponse(string $message, int $code): JsonResponse {
    return new JsonResponse([
      'success' => FALSE,
      'error' => $message,
      'message' => $message,
      'data' => NULL,
    ], $code);
  }

}

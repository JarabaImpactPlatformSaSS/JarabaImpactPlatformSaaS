<?php

declare(strict_types=1);

namespace Drupal\jaraba_verifactu\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\ecosistema_jaraba_core\Service\CertificateManagerService;
use Drupal\ecosistema_jaraba_core\Trait\ApiResponseTrait;
use Drupal\jaraba_verifactu\Service\VeriFactuQrService;
use Drupal\jaraba_verifactu\Service\VeriFactuRecordService;
use Drupal\jaraba_verifactu\Service\VeriFactuRemisionService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API REST controller for VeriFactu admin, records, and remision endpoints.
 *
 * Provides 16 endpoints across 3 groups:
 * - Admin (5): Config CRUD, certificate management, AEAT connection test.
 * - Records (6): List, detail, create, cancel, QR, XML.
 * - Remision (5): List, detail, create, retry, queue status.
 *
 * Uses ApiResponseTrait for standardized JSON responses.
 *
 * Spec: Doc 179, Seccion 6. Plan: FASE 3, entregable F3-5.
 */
class VeriFactuApiController extends ControllerBase implements ContainerInjectionInterface {

  use ApiResponseTrait;

  public function __construct(
    protected VeriFactuRecordService $recordService,
    protected VeriFactuQrService $qrService,
    protected VeriFactuRemisionService $remisionService,
    protected CertificateManagerService $certificateManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_verifactu.record_service'),
      $container->get('jaraba_verifactu.qr_service'),
      $container->get('jaraba_verifactu.remision_service'),
      $container->get('ecosistema_jaraba_core.certificate_manager'),
      $container->get('logger.channel.jaraba_verifactu'),
    );
  }

  // =========================================================================
  // ADMIN ENDPOINTS
  // =========================================================================

  /**
   * GET /api/v1/verifactu/config - Get tenant VeriFactu configuration.
   */
  public function getConfig(Request $request): JsonResponse {
    try {
      $tenantId = $this->resolveTenantId($request);
      if (!$tenantId) {
        return $this->apiError('Tenant not found.', 'TENANT_NOT_FOUND', 403);
      }

      $storage = $this->entityTypeManager()->getStorage('verifactu_tenant_config');
      $configs = $storage->loadByProperties(['tenant_id' => $tenantId]);

      if (empty($configs)) {
        return $this->apiError('No VeriFactu configuration for this tenant.', 'CONFIG_NOT_FOUND', 404);
      }

      $config = reset($configs);
      return $this->apiSuccess($this->serializeTenantConfig($config));
    }
    catch (\Exception $e) {
      $this->logger->error('API getConfig error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Internal error.', 'INTERNAL_ERROR', 500);
    }
  }

  /**
   * PUT /api/v1/verifactu/config - Update tenant VeriFactu configuration.
   */
  public function updateConfig(Request $request): JsonResponse {
    try {
      $tenantId = $this->resolveTenantId($request);
      if (!$tenantId) {
        return $this->apiError('Tenant not found.', 'TENANT_NOT_FOUND', 403);
      }

      $body = json_decode($request->getContent(), TRUE) ?? [];
      $storage = $this->entityTypeManager()->getStorage('verifactu_tenant_config');
      $configs = $storage->loadByProperties(['tenant_id' => $tenantId]);

      if (empty($configs)) {
        return $this->apiError('No VeriFactu configuration for this tenant.', 'CONFIG_NOT_FOUND', 404);
      }

      $config = reset($configs);
      $allowedFields = ['nif', 'nombre_fiscal', 'serie_facturacion', 'aeat_environment', 'is_active'];

      foreach ($allowedFields as $field) {
        if (isset($body[$field])) {
          $config->set($field, $body[$field]);
        }
      }

      $config->save();
      return $this->apiSuccess($this->serializeTenantConfig($config));
    }
    catch (\Exception $e) {
      $this->logger->error('API updateConfig error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Internal error.', 'INTERNAL_ERROR', 500);
    }
  }

  /**
   * POST /api/v1/verifactu/config/certificate - Upload PKCS#12 certificate.
   */
  public function uploadCertificate(Request $request): JsonResponse {
    try {
      $tenantId = $this->resolveTenantId($request);
      if (!$tenantId) {
        return $this->apiError('Tenant not found.', 'TENANT_NOT_FOUND', 403);
      }

      $body = json_decode($request->getContent(), TRUE) ?? [];
      $certData = $body['certificate'] ?? NULL;
      $password = $body['password'] ?? '';

      if (!$certData) {
        return $this->apiError('Certificate data is required.', 'VALIDATION_ERROR', 400);
      }

      $certBinary = base64_decode($certData, TRUE);
      if ($certBinary === FALSE) {
        return $this->apiError('Invalid base64-encoded certificate data.', 'VALIDATION_ERROR', 400);
      }

      $result = $this->certificateManager->storeCertificate($tenantId, $certBinary, $password);

      if ($result === NULL) {
        return $this->apiError('Failed to store certificate.', 'CERTIFICATE_ERROR', 500);
      }

      return $this->apiSuccess(['message' => 'Certificate uploaded successfully.'], [], 201);
    }
    catch (\Exception $e) {
      $this->logger->error('API uploadCertificate error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Failed to upload certificate.', 'CERTIFICATE_ERROR', 500);
    }
  }

  /**
   * GET /api/v1/verifactu/config/certificate/status - Certificate status.
   */
  public function getCertificateStatus(Request $request): JsonResponse {
    try {
      $tenantId = $this->resolveTenantId($request);
      if (!$tenantId) {
        return $this->apiError('Tenant not found.', 'TENANT_NOT_FOUND', 403);
      }

      $hasCert = $this->certificateManager->hasCertificate($tenantId);
      if (!$hasCert) {
        return $this->apiSuccess(['has_certificate' => FALSE]);
      }

      $validation = $this->certificateManager->validateCertificate($tenantId);
      return $this->apiSuccess($validation->toArray());
    }
    catch (\Exception $e) {
      $this->logger->error('API getCertificateStatus error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Internal error.', 'INTERNAL_ERROR', 500);
    }
  }

  /**
   * POST /api/v1/verifactu/config/test-connection - Test AEAT connection.
   */
  public function testConnection(Request $request): JsonResponse {
    try {
      $tenantId = $this->resolveTenantId($request);
      if (!$tenantId) {
        return $this->apiError('Tenant not found.', 'TENANT_NOT_FOUND', 403);
      }

      $hasCert = $this->certificateManager->hasCertificate($tenantId);
      if (!$hasCert) {
        return $this->apiError('No certificate configured. Upload a PKCS#12 certificate first.', 'NO_CERTIFICATE', 400);
      }

      $validation = $this->certificateManager->validateCertificate($tenantId);
      if (!$validation->isValid) {
        return $this->apiError('Certificate is invalid: ' . $validation->errorMessage, 'INVALID_CERTIFICATE', 400);
      }

      return $this->apiSuccess([
        'connection_status' => 'ready',
        'certificate_valid' => TRUE,
        'certificate_expires' => $validation->expiresAt?->format('Y-m-d'),
        'message' => 'AEAT connection prerequisites are met.',
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('API testConnection error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Connection test failed.', 'CONNECTION_ERROR', 500);
    }
  }

  // =========================================================================
  // RECORDS ENDPOINTS
  // =========================================================================

  /**
   * GET /api/v1/verifactu/records - List records (paginated, filters).
   */
  public function listRecords(Request $request): JsonResponse {
    try {
      $tenantId = $this->resolveTenantId($request);
      if (!$tenantId) {
        return $this->apiError('Tenant not found.', 'TENANT_NOT_FOUND', 403);
      }

      $limit = min(100, max(1, (int) ($request->query->get('limit', 20))));
      $offset = max(0, (int) ($request->query->get('offset', 0)));

      $storage = $this->entityTypeManager()->getStorage('verifactu_invoice_record');
      $query = $storage->getQuery()
        ->condition('tenant_id', $tenantId)
        ->sort('id', 'DESC')
        ->accessCheck(TRUE);

      // Apply filters.
      if ($status = $request->query->get('aeat_status')) {
        $query->condition('aeat_status', $status);
      }
      if ($type = $request->query->get('record_type')) {
        $query->condition('record_type', $type);
      }

      // Count total.
      $countQuery = clone $query;
      $total = $countQuery->count()->execute();

      // Apply pagination.
      $ids = $query->range($offset, $limit)->execute();
      $records = $storage->loadMultiple($ids);

      $items = array_map([$this, 'serializeRecord'], array_values($records));

      return $this->apiPaginated($items, (int) $total, $limit, $offset);
    }
    catch (\Exception $e) {
      $this->logger->error('API listRecords error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Internal error.', 'INTERNAL_ERROR', 500);
    }
  }

  /**
   * GET /api/v1/verifactu/records/{id} - Record detail.
   */
  public function getRecord(int $id): JsonResponse {
    try {
      $record = $this->entityTypeManager()->getStorage('verifactu_invoice_record')->load($id);

      if (!$record) {
        return $this->apiError('Record not found.', 'NOT_FOUND', 404);
      }

      return $this->apiSuccess($this->serializeRecord($record));
    }
    catch (\Exception $e) {
      $this->logger->error('API getRecord error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Internal error.', 'INTERNAL_ERROR', 500);
    }
  }

  /**
   * POST /api/v1/verifactu/records - Create manual alta record.
   */
  public function createRecord(Request $request): JsonResponse {
    try {
      $body = json_decode($request->getContent(), TRUE) ?? [];
      $invoiceId = $body['billing_invoice_id'] ?? NULL;

      if (!$invoiceId) {
        return $this->apiError('billing_invoice_id is required.', 'VALIDATION_ERROR', 400);
      }

      $invoice = $this->entityTypeManager()->getStorage('billing_invoice')->load($invoiceId);
      if (!$invoice) {
        return $this->apiError('Billing invoice not found.', 'NOT_FOUND', 404);
      }

      $record = $this->recordService->createAltaRecord($invoice);
      return $this->apiSuccess($this->serializeRecord($record), [], 201);
    }
    catch (\Exception $e) {
      $this->logger->error('API createRecord error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Failed to create record: ' . $e->getMessage(), 'CREATE_ERROR', 500);
    }
  }

  /**
   * POST /api/v1/verifactu/records/{id}/cancel - Create anulacion record.
   */
  public function cancelRecord(int $id): JsonResponse {
    try {
      $original = $this->entityTypeManager()->getStorage('verifactu_invoice_record')->load($id);

      if (!$original) {
        return $this->apiError('Record not found.', 'NOT_FOUND', 404);
      }

      if ($original->isCancellation()) {
        return $this->apiError('Cannot cancel a cancellation record.', 'VALIDATION_ERROR', 400);
      }

      $cancellation = $this->recordService->createAnulacionRecord($original);
      return $this->apiSuccess($this->serializeRecord($cancellation), [], 201);
    }
    catch (\Exception $e) {
      $this->logger->error('API cancelRecord error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Failed to cancel record: ' . $e->getMessage(), 'CANCEL_ERROR', 500);
    }
  }

  /**
   * GET /api/v1/verifactu/records/{id}/qr - Get QR verification image.
   */
  public function getRecordQr(int $id): JsonResponse {
    try {
      $record = $this->entityTypeManager()->getStorage('verifactu_invoice_record')->load($id);

      if (!$record) {
        return $this->apiError('Record not found.', 'NOT_FOUND', 404);
      }

      $qrUrl = $record->get('qr_url')->value;
      $qrImage = $record->get('qr_image')->value;

      if (!$qrUrl) {
        $qrUrl = $this->qrService->buildVerificationUrl($record);
        $qrImage = $this->qrService->generateQrImage($qrUrl);
      }

      return $this->apiSuccess([
        'verification_url' => $qrUrl,
        'qr_image_base64' => $qrImage,
        'label' => 'VERI*FACTU',
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('API getRecordQr error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Internal error.', 'INTERNAL_ERROR', 500);
    }
  }

  /**
   * GET /api/v1/verifactu/records/{id}/xml - Get XML of record.
   */
  public function getRecordXml(int $id): JsonResponse {
    try {
      $record = $this->entityTypeManager()->getStorage('verifactu_invoice_record')->load($id);

      if (!$record) {
        return $this->apiError('Record not found.', 'NOT_FOUND', 404);
      }

      return $this->apiSuccess([
        'xml' => $record->get('xml_registro')->value ?? '',
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('API getRecordXml error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Internal error.', 'INTERNAL_ERROR', 500);
    }
  }

  // =========================================================================
  // REMISION ENDPOINTS
  // =========================================================================

  /**
   * GET /api/v1/verifactu/remisions - List remision batches.
   */
  public function listRemisions(Request $request): JsonResponse {
    try {
      $tenantId = $this->resolveTenantId($request);
      if (!$tenantId) {
        return $this->apiError('Tenant not found.', 'TENANT_NOT_FOUND', 403);
      }

      $limit = min(50, max(1, (int) ($request->query->get('limit', 20))));
      $offset = max(0, (int) ($request->query->get('offset', 0)));

      $storage = $this->entityTypeManager()->getStorage('verifactu_remision_batch');
      $query = $storage->getQuery()
        ->condition('tenant_id', $tenantId)
        ->sort('id', 'DESC')
        ->accessCheck(TRUE);

      if ($status = $request->query->get('status')) {
        $query->condition('status', $status);
      }

      $countQuery = clone $query;
      $total = $countQuery->count()->execute();

      $ids = $query->range($offset, $limit)->execute();
      $batches = $storage->loadMultiple($ids);

      $items = array_map([$this, 'serializeBatch'], array_values($batches));

      return $this->apiPaginated($items, (int) $total, $limit, $offset);
    }
    catch (\Exception $e) {
      $this->logger->error('API listRemisions error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Internal error.', 'INTERNAL_ERROR', 500);
    }
  }

  /**
   * GET /api/v1/verifactu/remisions/{id} - Batch detail.
   */
  public function getRemision(int $id): JsonResponse {
    try {
      $batch = $this->entityTypeManager()->getStorage('verifactu_remision_batch')->load($id);

      if (!$batch) {
        return $this->apiError('Batch not found.', 'NOT_FOUND', 404);
      }

      return $this->apiSuccess($this->serializeBatch($batch));
    }
    catch (\Exception $e) {
      $this->logger->error('API getRemision error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Internal error.', 'INTERNAL_ERROR', 500);
    }
  }

  /**
   * POST /api/v1/verifactu/remisions - Create manual batch.
   */
  public function createRemision(Request $request): JsonResponse {
    try {
      $batchesCreated = $this->remisionService->processQueue();

      return $this->apiSuccess([
        'batches_created' => $batchesCreated,
        'message' => $batchesCreated > 0
          ? $batchesCreated . ' batches created and queued for submission.'
          : 'No pending records to batch.',
      ], [], $batchesCreated > 0 ? 201 : 200);
    }
    catch (\Exception $e) {
      $this->logger->error('API createRemision error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Failed to create remision: ' . $e->getMessage(), 'REMISION_ERROR', 500);
    }
  }

  /**
   * POST /api/v1/verifactu/remisions/{id}/retry - Retry failed batch.
   */
  public function retryRemision(int $id): JsonResponse {
    try {
      $batch = $this->entityTypeManager()->getStorage('verifactu_remision_batch')->load($id);

      if (!$batch) {
        return $this->apiError('Batch not found.', 'NOT_FOUND', 404);
      }

      $status = $batch->get('status')->value;
      if ($status !== 'error' && $status !== 'partial_error') {
        return $this->apiError('Only failed batches can be retried.', 'VALIDATION_ERROR', 400);
      }

      $result = $this->remisionService->submitBatch($batch);

      return $this->apiSuccess($result->toArray());
    }
    catch (\Exception $e) {
      $this->logger->error('API retryRemision error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Retry failed: ' . $e->getMessage(), 'RETRY_ERROR', 500);
    }
  }

  /**
   * GET /api/v1/verifactu/remisions/queue-status - Queue status.
   */
  public function getQueueStatus(): JsonResponse {
    try {
      $queue = \Drupal::queue('verifactu_remision');

      return $this->apiSuccess([
        'items_in_queue' => $queue->numberOfItems(),
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('API getQueueStatus error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Internal error.', 'INTERNAL_ERROR', 500);
    }
  }

  // =========================================================================
  // SERIALIZERS
  // =========================================================================

  /**
   * Serializes a VeriFactuInvoiceRecord for JSON output.
   */
  protected function serializeRecord($record): array {
    return [
      'id' => (int) $record->id(),
      'record_type' => $record->get('record_type')->value,
      'nif_emisor' => $record->get('nif_emisor')->value,
      'nombre_emisor' => $record->get('nombre_emisor')->value,
      'numero_factura' => $record->get('numero_factura')->value,
      'fecha_expedicion' => $record->get('fecha_expedicion')->value,
      'tipo_factura' => $record->get('tipo_factura')->value,
      'clave_regimen' => $record->get('clave_regimen')->value,
      'base_imponible' => $record->get('base_imponible')->value,
      'tipo_impositivo' => $record->get('tipo_impositivo')->value,
      'cuota_tributaria' => $record->get('cuota_tributaria')->value,
      'importe_total' => $record->get('importe_total')->value,
      'hash_record' => $record->get('hash_record')->value,
      'aeat_status' => $record->get('aeat_status')->value,
      'aeat_response_code' => $record->get('aeat_response_code')->value,
      'qr_url' => $record->get('qr_url')->value,
      'software_id' => $record->get('software_id')->value,
      'created' => (int) $record->get('created')->value,
    ];
  }

  /**
   * Serializes a VeriFactuRemisionBatch for JSON output.
   */
  protected function serializeBatch($batch): array {
    return [
      'id' => (int) $batch->id(),
      'status' => $batch->get('status')->value,
      'total_records' => (int) $batch->get('total_records')->value,
      'accepted_records' => (int) $batch->get('accepted_records')->value,
      'rejected_records' => (int) $batch->get('rejected_records')->value,
      'aeat_environment' => $batch->get('aeat_environment')->value,
      'sent_at' => $batch->get('sent_at')->value,
      'response_at' => $batch->get('response_at')->value,
      'retry_count' => (int) $batch->get('retry_count')->value,
      'error_message' => $batch->get('error_message')->value,
      'created' => (int) $batch->get('created')->value,
    ];
  }

  /**
   * Serializes a VeriFactuTenantConfig for JSON output.
   */
  protected function serializeTenantConfig($config): array {
    return [
      'id' => (int) $config->id(),
      'tenant_id' => (int) $config->get('tenant_id')->target_id,
      'nif' => $config->get('nif')->value,
      'nombre_fiscal' => $config->get('nombre_fiscal')->value,
      'serie_facturacion' => $config->get('serie_facturacion')->value,
      'aeat_environment' => $config->get('aeat_environment')->value,
      'is_active' => (bool) $config->get('is_active')->value,
      'last_chain_hash' => $config->get('last_chain_hash')->value,
      'certificate_valid_until' => $config->get('certificate_valid_until')->value,
      'certificate_subject' => $config->get('certificate_subject')->value,
      'created' => (int) $config->get('created')->value,
    ];
  }

  /**
   * Resolves tenant ID from request context.
   */
  protected function resolveTenantId(Request $request): ?int {
    $tenantId = $request->query->get('tenant_id');
    if ($tenantId) {
      return (int) $tenantId;
    }

    // Fallback: try to resolve from current user's group membership.
    $user = $this->currentUser();
    if ($user->isAuthenticated()) {
      $membershipLoader = \Drupal::service('group.membership_loader');
      $memberships = $membershipLoader->loadByUser($user);
      if (!empty($memberships)) {
        return (int) reset($memberships)->getGroup()->id();
      }
    }

    return NULL;
  }

}

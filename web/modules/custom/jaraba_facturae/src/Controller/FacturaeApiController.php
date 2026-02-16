<?php

declare(strict_types=1);

namespace Drupal\jaraba_facturae\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Trait\ApiResponseTrait;
use Drupal\jaraba_facturae\Entity\FacturaeDocument;
use Drupal\jaraba_facturae\Service\FACeClientService;
use Drupal\jaraba_facturae\Service\FacturaeXAdESService;
use Drupal\jaraba_facturae\Service\FacturaeXmlService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * REST API controller for Facturae module.
 *
 * Provides endpoints for document management, signing, FACe communication,
 * and configuration.
 *
 * Spec: Doc 180, Seccion 4.
 * Plan: FASE 7, entregable F7-4.
 */
class FacturaeApiController extends ControllerBase {

  use ApiResponseTrait;

  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    protected readonly FacturaeXmlService $xmlService,
    protected readonly FacturaeXAdESService $xadesService,
    protected readonly FACeClientService $faceClient,
  ) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('jaraba_facturae.xml_service'),
      $container->get('jaraba_facturae.xades_service'),
      $container->get('jaraba_facturae.face_client'),
    );
  }

  /**
   * GET /api/v1/facturae/config — Returns tenant configuration.
   */
  public function getConfig(): JsonResponse {
    try {
      $storage = $this->entityTypeManager->getStorage('facturae_tenant_config');
      $configs = $storage->loadMultiple();

      $data = [];
      foreach ($configs as $config) {
        $data[] = [
          'id' => $config->id(),
          'tenant_id' => $config->get('tenant_id')->target_id ?? NULL,
          'nif_emisor' => $config->get('nif_emisor')->value ?? '',
          'nombre_razon' => $config->get('nombre_razon')->value ?? '',
          'face_enabled' => (bool) ($config->get('face_enabled')->value ?? FALSE),
          'face_environment' => $config->get('face_environment')->value ?? 'staging',
          'active' => (bool) ($config->get('active')->value ?? TRUE),
        ];
      }

      return $this->apiSuccess($data);
    }
    catch (\Exception $e) {
      return $this->apiError($e->getMessage(), 'INTERNAL_ERROR', 500);
    }
  }

  /**
   * GET /api/v1/facturae/documents — Lists Facturae documents.
   */
  public function documents(Request $request): JsonResponse {
    try {
      $storage = $this->entityTypeManager->getStorage('facturae_document');
      $query = $storage->getQuery()->accessCheck(TRUE);

      // Optional filters.
      $status = $request->query->get('status');
      if ($status) {
        $query->condition('status', $status);
      }

      $tenantId = $request->query->get('tenant_id');
      if ($tenantId) {
        $query->condition('tenant_id', (int) $tenantId);
      }

      $total = (clone $query)->count()->execute();

      $limit = min((int) ($request->query->get('limit', 20)), 100);
      $offset = (int) $request->query->get('offset', 0);

      $ids = $query
        ->sort('created', 'DESC')
        ->range($offset, $limit)
        ->execute();

      $documents = $storage->loadMultiple($ids);
      $data = array_map([$this, 'serializeDocument'], $documents);

      return $this->apiPaginated(array_values($data), (int) $total, $limit, $offset);
    }
    catch (\Exception $e) {
      return $this->apiError($e->getMessage(), 'INTERNAL_ERROR', 500);
    }
  }

  /**
   * GET /api/v1/facturae/documents/{facturae_document} — Document detail.
   */
  public function documentDetail(FacturaeDocument $facturae_document): JsonResponse {
    return $this->apiSuccess($this->serializeDocument($facturae_document, TRUE));
  }

  /**
   * POST /api/v1/facturae/documents — Creates a new Facturae document.
   */
  public function createDocument(Request $request): JsonResponse {
    try {
      $data = json_decode($request->getContent(), TRUE);
      if (empty($data)) {
        return $this->apiError('Invalid JSON body.', 'BAD_REQUEST', 400);
      }

      $storage = $this->entityTypeManager->getStorage('facturae_document');
      $document = $storage->create($data);
      $document->save();

      // Generate XML.
      $xml = $this->xmlService->buildFacturaeXml($document);
      $document->set('xml_unsigned', $xml);
      $document->set('status', 'validated');
      $document->save();

      return $this->apiSuccess($this->serializeDocument($document), [], 201);
    }
    catch (\Exception $e) {
      return $this->apiError($e->getMessage(), 'CREATION_ERROR', 500);
    }
  }

  /**
   * POST /api/v1/facturae/documents/{facturae_document}/sign — Signs document.
   */
  public function signDocument(FacturaeDocument $facturae_document): JsonResponse {
    try {
      $status = $facturae_document->get('status')->value ?? '';
      if (!in_array($status, ['draft', 'validated'], TRUE)) {
        return $this->apiError('Document must be in draft or validated status to sign.', 'INVALID_STATUS', 400);
      }

      $xml = $facturae_document->get('xml_unsigned')->value ?? '';
      if (empty($xml)) {
        $xml = $this->xmlService->buildFacturaeXml($facturae_document);
        $facturae_document->set('xml_unsigned', $xml);
      }

      $tenantId = (int) ($facturae_document->get('tenant_id')->target_id ?? 0);
      $signedXml = $this->xadesService->signDocument($xml, $tenantId);

      $facturae_document->set('xml_signed', $signedXml);
      $facturae_document->set('signature_status', 'signed');
      $facturae_document->set('signature_timestamp', date('Y-m-d\TH:i:s'));
      $facturae_document->set('status', 'signed');
      $facturae_document->save();

      return $this->apiSuccess([
        'id' => $facturae_document->id(),
        'status' => 'signed',
        'signature_status' => 'signed',
      ]);
    }
    catch (\Exception $e) {
      return $this->apiError($e->getMessage(), 'SIGNATURE_ERROR', 500);
    }
  }

  /**
   * POST /api/v1/facturae/documents/{facturae_document}/send — Sends to FACe.
   */
  public function sendToFace(FacturaeDocument $facturae_document): JsonResponse {
    try {
      $status = $facturae_document->get('status')->value ?? '';
      if ($status !== 'signed') {
        return $this->apiError('Document must be signed before sending to FACe.', 'INVALID_STATUS', 400);
      }

      $response = $this->faceClient->sendInvoice($facturae_document);

      if ($response->success) {
        $facturae_document->set('face_status', 'sent');
        $facturae_document->set('face_registry_number', $response->registryNumber);
        $facturae_document->set('face_csv', $response->csv);
        $facturae_document->set('status', 'sent');
        $facturae_document->save();
      }

      return $this->apiSuccess($response->toArray());
    }
    catch (\Exception $e) {
      return $this->apiError($e->getMessage(), 'FACE_ERROR', 500);
    }
  }

  /**
   * GET /api/v1/facturae/documents/{facturae_document}/pdf — Downloads PDF.
   */
  public function downloadPdf(FacturaeDocument $facturae_document): JsonResponse {
    $pdfId = $facturae_document->get('pdf_representation_id')->target_id ?? NULL;
    if ($pdfId === NULL) {
      return $this->apiError('No PDF representation available.', 'NOT_FOUND', 404);
    }

    return $this->apiSuccess([
      'pdf_file_id' => $pdfId,
      'document_id' => $facturae_document->id(),
    ]);
  }

  /**
   * GET /api/v1/facturae/face-logs — Lists FACe communication logs.
   */
  public function faceLogs(Request $request): JsonResponse {
    try {
      $storage = $this->entityTypeManager->getStorage('facturae_face_log');
      $query = $storage->getQuery()->accessCheck(TRUE);

      $total = (clone $query)->count()->execute();

      $limit = min((int) ($request->query->get('limit', 20)), 100);
      $offset = (int) $request->query->get('offset', 0);

      $ids = $query
        ->sort('created', 'DESC')
        ->range($offset, $limit)
        ->execute();

      $logs = $storage->loadMultiple($ids);
      $data = [];
      foreach ($logs as $log) {
        $data[] = [
          'id' => $log->id(),
          'operation' => $log->get('operation')->value ?? '',
          'response_code' => $log->get('response_code')->value ?? '',
          'http_status' => $log->get('http_status')->value ?? NULL,
          'duration_ms' => $log->get('duration_ms')->value ?? NULL,
          'created' => $log->get('created')->value ?? NULL,
        ];
      }

      return $this->apiPaginated($data, (int) $total, $limit, $offset);
    }
    catch (\Exception $e) {
      return $this->apiError($e->getMessage(), 'INTERNAL_ERROR', 500);
    }
  }

  /**
   * GET /api/v1/facturae/documents/{facturae_document}/face-status.
   */
  public function faceStatus(FacturaeDocument $facturae_document): JsonResponse {
    $registryNumber = $facturae_document->get('face_registry_number')->value ?? '';
    if (empty($registryNumber)) {
      return $this->apiError('Document has not been sent to FACe.', 'NOT_SENT', 400);
    }

    $tenantId = (int) ($facturae_document->get('tenant_id')->target_id ?? 0);
    $status = $this->faceClient->queryInvoice($registryNumber, $tenantId);

    return $this->apiSuccess($status->toArray());
  }

  /**
   * GET /api/v1/facturae/config/certificate/status.
   */
  public function certificateStatus(): JsonResponse {
    try {
      $storage = $this->entityTypeManager->getStorage('facturae_tenant_config');
      $configs = $storage->loadMultiple();

      $data = [];
      foreach ($configs as $config) {
        $tenantId = (int) ($config->get('tenant_id')->target_id ?? 0);
        $info = $this->xadesService->getCertificateInfo($tenantId);
        $data[] = [
          'tenant_id' => $tenantId,
          'nombre_razon' => $config->get('nombre_razon')->value ?? '',
        ] + $info;
      }

      return $this->apiSuccess($data);
    }
    catch (\Exception $e) {
      return $this->apiError($e->getMessage(), 'INTERNAL_ERROR', 500);
    }
  }

  /**
   * GET /api/v1/facturae/config/tenant.
   */
  public function tenantConfig(): JsonResponse {
    return $this->config();
  }

  /**
   * PUT /api/v1/facturae/config/tenant.
   */
  public function updateTenantConfig(Request $request): JsonResponse {
    try {
      $data = json_decode($request->getContent(), TRUE);
      if (empty($data) || empty($data['tenant_id'])) {
        return $this->apiError('Invalid request. tenant_id required.', 'BAD_REQUEST', 400);
      }

      $storage = $this->entityTypeManager->getStorage('facturae_tenant_config');
      $configs = $storage->loadByProperties(['tenant_id' => $data['tenant_id']]);

      if (empty($configs)) {
        return $this->apiError('Tenant config not found.', 'NOT_FOUND', 404);
      }

      $config = reset($configs);
      foreach ($data as $field => $value) {
        if ($field !== 'tenant_id' && $config->hasField($field)) {
          $config->set($field, $value);
        }
      }
      $config->save();

      return $this->apiSuccess(['id' => $config->id(), 'updated' => TRUE]);
    }
    catch (\Exception $e) {
      return $this->apiError($e->getMessage(), 'UPDATE_ERROR', 500);
    }
  }

  /**
   * Serializes a FacturaeDocument entity to an array.
   */
  protected function serializeDocument(FacturaeDocument $document, bool $full = FALSE): array {
    $data = [
      'id' => $document->id(),
      'facturae_number' => $document->get('facturae_number')->value ?? '',
      'invoice_class' => $document->get('invoice_class')->value ?? '',
      'invoice_type' => $document->get('invoice_type')->value ?? '',
      'seller_nif' => $document->get('seller_nif')->value ?? '',
      'seller_name' => $document->get('seller_name')->value ?? '',
      'buyer_nif' => $document->get('buyer_nif')->value ?? '',
      'buyer_name' => $document->get('buyer_name')->value ?? '',
      'total_invoice_amount' => $document->get('total_invoice_amount')->value ?? '0.00',
      'issue_date' => $document->get('issue_date')->value ?? '',
      'status' => $document->get('status')->value ?? 'draft',
      'signature_status' => $document->get('signature_status')->value ?? 'unsigned',
      'face_status' => $document->get('face_status')->value ?? 'not_sent',
      'face_registry_number' => $document->get('face_registry_number')->value ?? '',
      'created' => $document->get('created')->value ?? NULL,
    ];

    if ($full) {
      $data['total_gross_amount'] = $document->get('total_gross_amount')->value ?? '0.00';
      $data['total_tax_outputs'] = $document->get('total_tax_outputs')->value ?? '0.00';
      $data['total_tax_withheld'] = $document->get('total_tax_withheld')->value ?? '0.00';
      $data['total_outstanding'] = $document->get('total_outstanding')->value ?? '0.00';
      $data['face_csv'] = $document->get('face_csv')->value ?? '';
      $data['signature_timestamp'] = $document->get('signature_timestamp')->value ?? '';
      $data['has_xml_unsigned'] = !empty($document->get('xml_unsigned')->value);
      $data['has_xml_signed'] = !empty($document->get('xml_signed')->value);
    }

    return $data;
  }

}

<?php

declare(strict_types=1);

namespace Drupal\jaraba_einvoice_b2b\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_einvoice_b2b\Service\EInvoiceDeliveryService;
use Drupal\jaraba_einvoice_b2b\Service\EInvoiceFormatConverterService;
use Drupal\jaraba_einvoice_b2b\Service\EInvoiceUblService;
use Drupal\jaraba_einvoice_b2b\Service\EInvoiceValidationService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API controller for E-Invoice document operations.
 *
 * Handles document CRUD, XML download, format conversion, validation,
 * and delivery endpoints. All responses follow the standardized
 * {success, data, meta} JSON envelope.
 *
 * Spec: Doc 181, Section 4 (endpoints 4.1, 4.2, 4.4).
 * Plan: FASE 10, entregable F10-5.
 */
class EInvoiceApiController extends ControllerBase {

  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    protected EInvoiceUblService $ublService,
    protected EInvoiceFormatConverterService $formatConverter,
    protected EInvoiceValidationService $validationService,
    protected EInvoiceDeliveryService $deliveryService,
  ) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('jaraba_einvoice_b2b.ubl_service'),
      $container->get('jaraba_einvoice_b2b.format_converter'),
      $container->get('jaraba_einvoice_b2b.validation_service'),
      $container->get('jaraba_einvoice_b2b.delivery_service'),
    );
  }

  /**
   * GET /api/v1/einvoice/documents — List documents (paginated, filtered).
   */
  public function listDocuments(Request $request): JsonResponse {
    $storage = $this->entityTypeManager->getStorage('einvoice_document');
    $query = $storage->getQuery()->accessCheck(TRUE);

    // Filters.
    if ($direction = $request->query->get('direction')) {
      $query->condition('direction', $direction);
    }
    if ($status = $request->query->get('status')) {
      $query->condition('status', $status);
    }
    if ($paymentStatus = $request->query->get('payment_status')) {
      $query->condition('payment_status', $paymentStatus);
    }
    if ($tenantId = $request->query->get('tenant_id')) {
      $query->condition('tenant_id', (int) $tenantId);
    }

    // Pagination.
    $page = max(0, (int) $request->query->get('page', 0));
    $limit = min(100, max(1, (int) $request->query->get('limit', 25)));
    $query->range($page * $limit, $limit);
    $query->sort('created', 'DESC');

    $ids = $query->execute();
    $documents = $storage->loadMultiple($ids);

    $data = [];
    foreach ($documents as $doc) {
      $data[] = $this->serializeDocument($doc);
    }

    return new JsonResponse([
      'success' => TRUE,
      'data' => $data,
      'meta' => [
        'page' => $page,
        'limit' => $limit,
        'count' => count($data),
      ],
    ]);
  }

  /**
   * GET /api/v1/einvoice/documents/{einvoice_document} — Document detail.
   */
  public function getDocument(int $einvoice_document): JsonResponse {
    $document = $this->entityTypeManager->getStorage('einvoice_document')->load($einvoice_document);
    if (!$document) {
      return new JsonResponse(['success' => FALSE, 'data' => NULL, 'meta' => ['error' => 'Document not found.']], 404);
    }

    return new JsonResponse([
      'success' => TRUE,
      'data' => $this->serializeDocument($document, TRUE),
      'meta' => [],
    ]);
  }

  /**
   * POST /api/v1/einvoice/generate — Generate e-invoice from billing invoice.
   */
  public function generate(Request $request): JsonResponse {
    $content = json_decode($request->getContent(), TRUE);
    $invoiceId = $content['invoice_id'] ?? NULL;
    $tenantId = $content['tenant_id'] ?? NULL;

    if (!$invoiceId || !$tenantId) {
      return new JsonResponse(['success' => FALSE, 'data' => NULL, 'meta' => ['error' => 'invoice_id and tenant_id are required.']], 400);
    }

    try {
      $storage = $this->entityTypeManager->getStorage('einvoice_document');
      $document = $storage->create([
        'tenant_id' => $tenantId,
        'direction' => 'outbound',
        'invoice_id' => $invoiceId,
        'format' => $content['format'] ?? 'ubl_2.1',
        'status' => 'draft',
      ]);
      $document->save();

      // Generate UBL XML.
      $xml = $this->ublService->generateUbl($document);
      $document->set('xml_content', $xml);

      // Validate.
      $validationResult = $this->validationService->validate($xml, $document->get('format')->value);
      $document->set('validation_status', $validationResult->valid ? 'valid' : 'invalid');
      if (!$validationResult->valid) {
        $document->set('validation_errors_json', json_encode($validationResult->errors));
      }

      $document->save();

      return new JsonResponse([
        'success' => TRUE,
        'data' => $this->serializeDocument($document),
        'meta' => ['validation' => $validationResult->toArray()],
      ], 201);
    }
    catch (\Throwable $e) {
      return new JsonResponse(['success' => FALSE, 'data' => NULL, 'meta' => ['error' => $e->getMessage()]], 500);
    }
  }

  /**
   * POST /api/v1/einvoice/send/{id} — Send e-invoice to recipient.
   */
  public function send(int $einvoice_document, Request $request): JsonResponse {
    $content = json_decode($request->getContent(), TRUE);
    $channel = $content['channel'] ?? NULL;

    try {
      $result = $this->deliveryService->deliver($einvoice_document, $channel);

      return new JsonResponse([
        'success' => $result->success,
        'data' => $result->toArray(),
        'meta' => [],
      ], $result->success ? 200 : 422);
    }
    catch (\Throwable $e) {
      return new JsonResponse(['success' => FALSE, 'data' => NULL, 'meta' => ['error' => $e->getMessage()]], 500);
    }
  }

  /**
   * POST /api/v1/einvoice/receive — Receive inbound e-invoice (webhook).
   */
  public function receive(Request $request): JsonResponse {
    $xml = $request->getContent();
    if (empty($xml)) {
      return new JsonResponse(['success' => FALSE, 'data' => NULL, 'meta' => ['error' => 'Empty request body.']], 400);
    }

    try {
      $format = $this->formatConverter->detectFormat($xml);
      if ($format === 'unknown') {
        return new JsonResponse(['success' => FALSE, 'data' => NULL, 'meta' => ['error' => 'Unsupported XML format.']], 400);
      }

      $validation = $this->validationService->validate($xml, $format);
      if (!$validation->valid) {
        return new JsonResponse([
          'success' => FALSE,
          'data' => NULL,
          'meta' => ['error' => 'Validation failed.', 'validation' => $validation->toArray()],
        ], 422);
      }

      // Parse to model and create inbound document.
      $model = $this->formatConverter->toNeutralModel($xml);

      $storage = $this->entityTypeManager->getStorage('einvoice_document');
      $document = $storage->create([
        'direction' => 'inbound',
        'format' => $format,
        'xml_content' => $xml,
        'invoice_number' => $model->invoiceNumber,
        'invoice_date' => $model->issueDate,
        'due_date' => $model->dueDate,
        'seller_nif' => $model->seller['tax_id'] ?? '',
        'seller_name' => $model->seller['name'] ?? '',
        'buyer_nif' => $model->buyer['tax_id'] ?? '',
        'buyer_name' => $model->buyer['name'] ?? '',
        'currency_code' => $model->currencyCode,
        'total_without_tax' => $model->totalWithoutTax,
        'total_tax' => $model->totalTax,
        'total_amount' => $model->totalWithTax,
        'validation_status' => 'valid',
        'delivery_status' => 'delivered',
        'delivery_method' => 'api',
        'status' => 'pending',
      ]);
      $document->save();

      return new JsonResponse([
        'success' => TRUE,
        'data' => ['document_id' => (int) $document->id()],
        'meta' => ['format' => $format],
      ], 201);
    }
    catch (\Throwable $e) {
      return new JsonResponse(['success' => FALSE, 'data' => NULL, 'meta' => ['error' => $e->getMessage()]], 500);
    }
  }

  /**
   * POST /api/v1/einvoice/convert — Convert between formats.
   */
  public function convertFormat(Request $request): JsonResponse {
    $content = json_decode($request->getContent(), TRUE);
    $xml = $content['xml'] ?? '';
    $targetFormat = $content['target_format'] ?? 'ubl_2.1';

    if (empty($xml)) {
      return new JsonResponse(['success' => FALSE, 'data' => NULL, 'meta' => ['error' => 'xml field is required.']], 400);
    }

    try {
      $sourceFormat = $this->formatConverter->detectFormat($xml);
      $converted = $this->formatConverter->convertTo($xml, $targetFormat);

      return new JsonResponse([
        'success' => TRUE,
        'data' => ['xml' => $converted],
        'meta' => ['source_format' => $sourceFormat, 'target_format' => $targetFormat],
      ]);
    }
    catch (\Throwable $e) {
      return new JsonResponse(['success' => FALSE, 'data' => NULL, 'meta' => ['error' => $e->getMessage()]], 422);
    }
  }

  /**
   * POST /api/v1/einvoice/validate — Validate XML against EN 16931.
   */
  public function validateXml(Request $request): JsonResponse {
    $content = json_decode($request->getContent(), TRUE);
    $xml = $content['xml'] ?? '';
    $format = $content['format'] ?? 'ubl_2.1';

    if (empty($xml)) {
      return new JsonResponse(['success' => FALSE, 'data' => NULL, 'meta' => ['error' => 'xml field is required.']], 400);
    }

    $result = $this->validationService->validate($xml, $format);

    return new JsonResponse([
      'success' => $result->valid,
      'data' => $result->toArray(),
      'meta' => [],
    ]);
  }

  /**
   * GET /api/v1/einvoice/dashboard — Dashboard summary.
   */
  public function dashboard(Request $request): JsonResponse {
    $tenantId = $request->query->get('tenant_id');
    $storage = $this->entityTypeManager->getStorage('einvoice_document');

    $baseQuery = $storage->getQuery()->accessCheck(TRUE);
    if ($tenantId) {
      $baseQuery->condition('tenant_id', (int) $tenantId);
    }

    $totalIds = (clone $baseQuery)->execute();
    $outboundIds = (clone $baseQuery)->condition('direction', 'outbound')->execute();
    $inboundIds = (clone $baseQuery)->condition('direction', 'inbound')->execute();
    $overdueIds = (clone $baseQuery)->condition('payment_status', 'overdue')->execute();

    return new JsonResponse([
      'success' => TRUE,
      'data' => [
        'total_documents' => count($totalIds),
        'outbound' => count($outboundIds),
        'inbound' => count($inboundIds),
        'overdue' => count($overdueIds),
      ],
      'meta' => ['generated_at' => date('c')],
    ]);
  }

  /**
   * Serializes a document entity to array.
   */
  protected function serializeDocument(object $document, bool $includeXml = FALSE): array {
    $data = [
      'id' => (int) $document->id(),
      'uuid' => $document->uuid(),
      'invoice_number' => $document->get('invoice_number')->value,
      'direction' => $document->get('direction')->value,
      'format' => $document->get('format')->value,
      'seller_nif' => $document->get('seller_nif')->value,
      'seller_name' => $document->get('seller_name')->value,
      'buyer_nif' => $document->get('buyer_nif')->value,
      'buyer_name' => $document->get('buyer_name')->value,
      'currency_code' => $document->get('currency_code')->value,
      'total_without_tax' => $document->get('total_without_tax')->value,
      'total_tax' => $document->get('total_tax')->value,
      'total_amount' => $document->get('total_amount')->value,
      'delivery_status' => $document->get('delivery_status')->value,
      'payment_status' => $document->get('payment_status')->value,
      'spfe_status' => $document->get('spfe_status')->value,
      'validation_status' => $document->get('validation_status')->value,
      'status' => $document->get('status')->value,
      'created' => $document->get('created')->value,
    ];

    if ($includeXml) {
      $data['xml_content'] = $document->get('xml_content')->value;
      $data['spfe_submission_id'] = $document->get('spfe_submission_id')->value;
    }

    return $data;
  }

}

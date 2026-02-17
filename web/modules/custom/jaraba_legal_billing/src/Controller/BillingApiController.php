<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_billing\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_legal_billing\Service\InvoiceManagerService;
use Drupal\jaraba_legal_billing\Service\StripeInvoiceService;
use Drupal\jaraba_legal_billing\Service\TimeTrackingService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API REST para Time Entries, Invoices y Stripe Webhooks.
 *
 * Estructura: API-NAMING-001 — POST store(), GET list/detail, PATCH update.
 * Logica: Envelope {data} o {data, meta} para listas, {error} para errores.
 */
class BillingApiController extends ControllerBase {

  public function __construct(
    protected readonly TimeTrackingService $timeTracker,
    protected readonly InvoiceManagerService $invoiceManager,
    protected readonly StripeInvoiceService $stripeService,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_legal_billing.time_tracker'),
      $container->get('jaraba_legal_billing.invoice_manager'),
      $container->get('jaraba_legal_billing.stripe_invoice'),
      $container->get('entity_type.manager'),
      $container->get('logger.channel.jaraba_legal_billing'),
    );
  }

  // =========================================================================
  // TIME ENTRIES
  // =========================================================================

  /**
   * POST /api/v1/legal/billing/time-entries
   */
  public function storeTimeEntry(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE) ?? [];
    if (empty($data['case_id']) || empty($data['description']) || empty($data['date']) || empty($data['duration_minutes'])) {
      return new JsonResponse(['error' => 'Campos requeridos: case_id, description, date, duration_minutes.'], 422);
    }

    $result = $this->timeTracker->logTime($data);
    if (empty($result)) {
      return new JsonResponse(['error' => 'Error al registrar tiempo.'], 500);
    }

    return new JsonResponse(['data' => $result], 201);
  }

  /**
   * GET /api/v1/legal/billing/time-entries
   */
  public function listTimeEntries(Request $request): JsonResponse {
    $caseId = (int) $request->query->get('case_id', 0);
    $limit = min((int) $request->query->get('limit', 25), 100);
    $offset = max((int) $request->query->get('offset', 0), 0);

    if ($caseId) {
      $items = $this->timeTracker->getTimeByCase($caseId, $limit, $offset);
    }
    else {
      $items = [];
    }

    return new JsonResponse([
      'data' => $items,
      'meta' => ['limit' => $limit, 'offset' => $offset],
    ]);
  }

  /**
   * GET /api/v1/legal/billing/time-entries/{uuid}
   */
  public function timeEntryDetail(string $uuid): JsonResponse {
    $entries = $this->entityTypeManager->getStorage('time_entry')
      ->loadByProperties(['uuid' => $uuid]);
    $entry = reset($entries);
    if (!$entry) {
      return new JsonResponse(['error' => 'Entrada no encontrada.'], 404);
    }

    return new JsonResponse(['data' => $this->timeTracker->serializeTimeEntry($entry)]);
  }

  /**
   * PATCH /api/v1/legal/billing/time-entries/{uuid}
   */
  public function updateTimeEntry(string $uuid, Request $request): JsonResponse {
    $entries = $this->entityTypeManager->getStorage('time_entry')
      ->loadByProperties(['uuid' => $uuid]);
    $entry = reset($entries);
    if (!$entry) {
      return new JsonResponse(['error' => 'Entrada no encontrada.'], 404);
    }

    $data = json_decode($request->getContent(), TRUE) ?? [];
    $allowed = ['description', 'date', 'duration_minutes', 'billing_rate', 'is_billable'];
    foreach ($allowed as $field) {
      if (array_key_exists($field, $data)) {
        $entry->set($field, $data[$field]);
      }
    }
    $entry->save();

    return new JsonResponse(['data' => $this->timeTracker->serializeTimeEntry($entry)]);
  }

  /**
   * DELETE /api/v1/legal/billing/time-entries/{uuid}
   */
  public function deleteTimeEntry(string $uuid): JsonResponse {
    $entries = $this->entityTypeManager->getStorage('time_entry')
      ->loadByProperties(['uuid' => $uuid]);
    $entry = reset($entries);
    if (!$entry) {
      return new JsonResponse(['error' => 'Entrada no encontrada.'], 404);
    }

    $entry->delete();
    return new JsonResponse(['data' => ['deleted' => TRUE, 'uuid' => $uuid]]);
  }

  // =========================================================================
  // INVOICES
  // =========================================================================

  /**
   * POST /api/v1/legal/billing/invoices
   */
  public function storeInvoice(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE) ?? [];
    if (empty($data['case_id'])) {
      return new JsonResponse(['error' => 'Campo requerido: case_id.'], 422);
    }

    $result = $this->invoiceManager->createFromCase((int) $data['case_id'], $data);
    if (empty($result)) {
      return new JsonResponse(['error' => 'Error al crear factura.'], 500);
    }

    return new JsonResponse(['data' => $result], 201);
  }

  /**
   * POST /api/v1/legal/billing/invoices/from-quote/{uuid}
   */
  public function invoiceFromQuote(string $uuid): JsonResponse {
    $result = $this->invoiceManager->createFromQuote($uuid);
    if (empty($result)) {
      return new JsonResponse(['error' => 'Error al crear factura desde presupuesto.'], 500);
    }

    return new JsonResponse(['data' => $result], 201);
  }

  /**
   * GET /api/v1/legal/billing/invoices
   */
  public function listInvoices(Request $request): JsonResponse {
    $filters = [];
    if ($status = $request->query->get('status')) {
      $filters['status'] = $status;
    }
    if ($caseId = $request->query->get('case_id')) {
      $filters['case_id'] = (int) $caseId;
    }

    $limit = min((int) $request->query->get('limit', 25), 100);
    $offset = max((int) $request->query->get('offset', 0), 0);

    $result = $this->invoiceManager->listInvoices($filters, $limit, $offset);

    return new JsonResponse([
      'data' => $result['items'],
      'meta' => ['total' => $result['total'], 'limit' => $limit, 'offset' => $offset],
    ]);
  }

  /**
   * GET /api/v1/legal/billing/invoices/{uuid}
   */
  public function invoiceDetail(string $uuid): JsonResponse {
    $invoices = $this->entityTypeManager->getStorage('legal_invoice')
      ->loadByProperties(['uuid' => $uuid]);
    $invoice = reset($invoices);
    if (!$invoice) {
      return new JsonResponse(['error' => 'Factura no encontrada.'], 404);
    }

    return new JsonResponse(['data' => $this->invoiceManager->serializeInvoice($invoice)]);
  }

  /**
   * PATCH /api/v1/legal/billing/invoices/{uuid}
   */
  public function updateInvoice(string $uuid, Request $request): JsonResponse {
    $invoices = $this->entityTypeManager->getStorage('legal_invoice')
      ->loadByProperties(['uuid' => $uuid]);
    $invoice = reset($invoices);
    if (!$invoice) {
      return new JsonResponse(['error' => 'Factura no encontrada.'], 404);
    }
    if ($invoice->get('status')->value !== 'draft') {
      return new JsonResponse(['error' => 'Solo se pueden editar facturas en borrador.'], 422);
    }

    $data = json_decode($request->getContent(), TRUE) ?? [];
    $allowed = ['client_name', 'client_nif', 'client_address', 'client_email', 'due_date', 'notes', 'payment_method'];
    foreach ($allowed as $field) {
      if (array_key_exists($field, $data)) {
        $invoice->set($field, $data[$field]);
      }
    }
    $invoice->save();

    return new JsonResponse(['data' => $this->invoiceManager->serializeInvoice($invoice)]);
  }

  /**
   * POST /api/v1/legal/billing/invoices/{uuid}/issue
   */
  public function issueInvoice(string $uuid): JsonResponse {
    $result = $this->invoiceManager->issue($uuid);
    if (empty($result)) {
      return new JsonResponse(['error' => 'No se pudo emitir la factura.'], 422);
    }

    return new JsonResponse(['data' => $result]);
  }

  /**
   * POST /api/v1/legal/billing/invoices/{uuid}/send
   */
  public function sendInvoice(string $uuid): JsonResponse {
    $result = $this->invoiceManager->send($uuid);
    if (empty($result)) {
      return new JsonResponse(['error' => 'No se pudo enviar la factura.'], 422);
    }

    return new JsonResponse(['data' => $result]);
  }

  /**
   * POST /api/v1/legal/billing/invoices/{uuid}/mark-paid
   */
  public function markPaid(string $uuid, Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE) ?? [];
    $result = $this->invoiceManager->markPaid($uuid, $data);
    if (empty($result)) {
      return new JsonResponse(['error' => 'No se pudo marcar como pagada.'], 422);
    }

    return new JsonResponse(['data' => $result]);
  }

  /**
   * GET /api/v1/legal/billing/invoices/{uuid}/pdf
   */
  public function invoicePdf(string $uuid): JsonResponse {
    // TODO: Integrar con servicio de generacion PDF + Facturae 3.2.2.
    return new JsonResponse(['data' => ['uuid' => $uuid, 'pdf_url' => NULL, 'status' => 'pending_integration']]);
  }

  /**
   * POST /api/v1/legal/billing/invoices/{uuid}/credit-note
   */
  public function createCreditNote(string $uuid, Request $request): JsonResponse {
    try {
      $invoices = $this->entityTypeManager->getStorage('legal_invoice')
        ->loadByProperties(['uuid' => $uuid]);
      $invoice = reset($invoices);
      if (!$invoice) {
        return new JsonResponse(['error' => 'Factura no encontrada.'], 404);
      }

      $data = json_decode($request->getContent(), TRUE) ?? [];
      $storage = $this->entityTypeManager->getStorage('credit_note');
      $creditNote = $storage->create([
        'tenant_id' => $invoice->get('tenant_id')->target_id,
        'invoice_id' => $invoice->id(),
        'reason' => $data['reason'] ?? 'refund',
        'amount' => $data['amount'] ?? $invoice->get('subtotal')->value,
        'tax_amount' => $data['tax_amount'] ?? $invoice->get('tax_amount')->value,
        'total' => $data['total'] ?? $invoice->get('total')->value,
      ]);
      $creditNote->save();

      return new JsonResponse(['data' => [
        'id' => (int) $creditNote->id(),
        'credit_note_number' => $creditNote->get('credit_note_number')->value,
        'invoice_number' => $invoice->get('invoice_number')->value,
        'total' => (float) $creditNote->get('total')->value,
      ]], 201);
    }
    catch (\Exception $e) {
      return new JsonResponse(['error' => $e->getMessage()], 500);
    }
  }

  /**
   * POST /api/v1/legal/billing/webhooks/stripe
   */
  public function stripeWebhook(Request $request): JsonResponse {
    $payload = $request->getContent();
    $signature = $request->headers->get('Stripe-Signature', '');
    $result = $this->stripeService->handleWebhook($payload, $signature);

    if (isset($result['error'])) {
      return new JsonResponse($result, 400);
    }

    return new JsonResponse($result);
  }

}

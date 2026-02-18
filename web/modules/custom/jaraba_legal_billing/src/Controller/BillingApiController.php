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
use Symfony\Component\HttpFoundation\Response;

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
      return // AUDIT-CONS-N08: Standardized JSON envelope.
        new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Campos requeridos: case_id, description, date, duration_minutes.']], 422);
    }

    $result = $this->timeTracker->logTime($data);
    if (empty($result)) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Error al registrar tiempo.']], 500);
    }

    return new JsonResponse(['success' => TRUE, 'data' => $result], 201);
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
      'data' => $items, 'meta' => ['limit' => $limit, 'offset' => $offset]]);
  }

  /**
   * GET /api/v1/legal/billing/time-entries/{uuid}
   */
  public function timeEntryDetail(string $uuid): JsonResponse {
    $entries = $this->entityTypeManager->getStorage('time_entry')
      ->loadByProperties(['uuid' => $uuid]);
    $entry = reset($entries);
    if (!$entry) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Entrada no encontrada.']], 404);
    }

    return new JsonResponse(['success' => TRUE, 'data' => $this->timeTracker->serializeTimeEntry($entry)]);
  }

  /**
   * PATCH /api/v1/legal/billing/time-entries/{uuid}
   */
  public function updateTimeEntry(string $uuid, Request $request): JsonResponse {
    $entries = $this->entityTypeManager->getStorage('time_entry')
      ->loadByProperties(['uuid' => $uuid]);
    $entry = reset($entries);
    if (!$entry) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Entrada no encontrada.']], 404);
    }

    $data = json_decode($request->getContent(), TRUE) ?? [];
    $allowed = ['description', 'date', 'duration_minutes', 'billing_rate', 'is_billable'];
    foreach ($allowed as $field) {
      if (array_key_exists($field, $data)) {
        $entry->set($field, $data[$field]);
      }
    }
    $entry->save();

    return new JsonResponse(['success' => TRUE, 'data' => $this->timeTracker->serializeTimeEntry($entry), 'meta' => ['timestamp' => time()]]);
  }

  /**
   * DELETE /api/v1/legal/billing/time-entries/{uuid}
   */
  public function deleteTimeEntry(string $uuid): JsonResponse {
    $entries = $this->entityTypeManager->getStorage('time_entry')
      ->loadByProperties(['uuid' => $uuid]);
    $entry = reset($entries);
    if (!$entry) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Entrada no encontrada.']], 404);
    }

    $entry->delete();
    return new JsonResponse(['success' => TRUE, 'data' => ['deleted' => TRUE, 'uuid' => $uuid], 'meta' => ['timestamp' => time()]]);
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
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Campo requerido: case_id.']], 422);
    }

    $result = $this->invoiceManager->createFromCase((int) $data['case_id'], $data);
    if (empty($result)) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Error al crear factura.']], 500);
    }

    return new JsonResponse(['success' => TRUE, 'data' => $result, 'meta' => ['timestamp' => time()]], 201);
  }

  /**
   * POST /api/v1/legal/billing/invoices/from-quote/{uuid}
   */
  public function invoiceFromQuote(string $uuid): JsonResponse {
    $result = $this->invoiceManager->createFromQuote($uuid);
    if (empty($result)) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Error al crear factura desde presupuesto.']], 500);
    }

    return new JsonResponse(['success' => TRUE, 'data' => $result, 'meta' => ['timestamp' => time()]], 201);
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
      'data' => $result['items'], 'meta' => ['total' => $result['total'], 'limit' => $limit, 'offset' => $offset]]);
  }

  /**
   * GET /api/v1/legal/billing/invoices/{uuid}
   */
  public function invoiceDetail(string $uuid): JsonResponse {
    $invoices = $this->entityTypeManager->getStorage('legal_invoice')
      ->loadByProperties(['uuid' => $uuid]);
    $invoice = reset($invoices);
    if (!$invoice) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Factura no encontrada.']], 404);
    }

    return new JsonResponse(['success' => TRUE, 'data' => $this->invoiceManager->serializeInvoice($invoice), 'meta' => ['timestamp' => time()]]);
  }

  /**
   * PATCH /api/v1/legal/billing/invoices/{uuid}
   */
  public function updateInvoice(string $uuid, Request $request): JsonResponse {
    $invoices = $this->entityTypeManager->getStorage('legal_invoice')
      ->loadByProperties(['uuid' => $uuid]);
    $invoice = reset($invoices);
    if (!$invoice) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Factura no encontrada.']], 404);
    }
    if ($invoice->get('status')->value !== 'draft') {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Solo se pueden editar facturas en borrador.']], 422);
    }

    $data = json_decode($request->getContent(), TRUE) ?? [];
    $allowed = ['client_name', 'client_nif', 'client_address', 'client_email', 'due_date', 'notes', 'payment_method'];
    foreach ($allowed as $field) {
      if (array_key_exists($field, $data)) {
        $invoice->set($field, $data[$field]);
      }
    }
    $invoice->save();

    return new JsonResponse(['success' => TRUE, 'data' => $this->invoiceManager->serializeInvoice($invoice), 'meta' => ['timestamp' => time()]]);
  }

  /**
   * POST /api/v1/legal/billing/invoices/{uuid}/issue
   */
  public function issueInvoice(string $uuid): JsonResponse {
    $result = $this->invoiceManager->issue($uuid);
    if (empty($result)) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'No se pudo emitir la factura.']], 422);
    }

    return new JsonResponse(['success' => TRUE, 'data' => $result, 'meta' => ['timestamp' => time()]]);
  }

  /**
   * POST /api/v1/legal/billing/invoices/{uuid}/send
   */
  public function sendInvoice(string $uuid): JsonResponse {
    $result = $this->invoiceManager->send($uuid);
    if (empty($result)) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'No se pudo enviar la factura.']], 422);
    }

    return new JsonResponse(['success' => TRUE, 'data' => $result, 'meta' => ['timestamp' => time()]]);
  }

  /**
   * POST /api/v1/legal/billing/invoices/{uuid}/mark-paid
   */
  public function markPaid(string $uuid, Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE) ?? [];
    $result = $this->invoiceManager->markPaid($uuid, $data);
    if (empty($result)) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'No se pudo marcar como pagada.']], 422);
    }

    return new JsonResponse(['success' => TRUE, 'data' => $result, 'meta' => ['timestamp' => time()]]);
  }

  /**
   * GET /api/v1/legal/billing/invoices/{uuid}/pdf
   *
   * Returns PDF (or HTML fallback) for the invoice.
   * Appends Facturae 3.2.2 XML as a data attribute for downstream e-invoice processing.
   */
  public function invoicePdf(string $uuid): Response {
    // AUDIT-TODO-RESOLVED: PDF generation implemented.
    try {
      $invoices = $this->entityTypeManager->getStorage('legal_invoice')
        ->loadByProperties(['uuid' => $uuid]);
      $invoice = reset($invoices);
      if (!$invoice) {
        return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Factura no encontrada.']], 404);
      }

      // Load invoice line items.
      $lineStorage = $this->entityTypeManager->getStorage('invoice_line');
      $lineIds = $lineStorage->getQuery()
        ->condition('invoice_id', $invoice->id())
        ->accessCheck(FALSE)
        ->sort('line_order', 'ASC')
        ->execute();
      $lines = $lineStorage->loadMultiple($lineIds);

      // Request format: ?format=facturae returns raw XML; default returns PDF/HTML.
      $format = \Drupal::request()->query->get('format', 'pdf');

      if ($format === 'facturae') {
        $xml = $this->generateFacturaeXml($invoice, $lines);
        $invoiceNumber = $invoice->get('invoice_number')->value ?? $uuid;
        return new Response($xml, 200, [
          'Content-Type' => 'application/xml; charset=UTF-8',
          'Content-Disposition' => 'attachment; filename="facturae-' . $invoiceNumber . '.xml"',
        ]);
      }

      $html = $this->renderInvoicePdfHtml($invoice, $lines);

      // Use DOMPDF if available, otherwise return HTML with print-friendly headers.
      if (class_exists('\Dompdf\Dompdf')) {
        $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => FALSE]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdfContent = $dompdf->output();

        $invoiceNumber = $invoice->get('invoice_number')->value ?? $uuid;
        return new Response($pdfContent, 200, [
          'Content-Type' => 'application/pdf',
          'Content-Disposition' => 'attachment; filename="factura-' . $invoiceNumber . '.pdf"',
          'Content-Length' => strlen($pdfContent),
        ]);
      }

      // Fallback: return HTML response suitable for browser print-to-PDF.
      $invoiceNumber = $invoice->get('invoice_number')->value ?? $uuid;
      return new Response($html, 200, [
        'Content-Type' => 'text/html; charset=UTF-8',
        'Content-Disposition' => 'inline; filename="factura-' . $invoiceNumber . '.html"',
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Invoice PDF generation error: @msg', ['@msg' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Error al generar PDF.']], 500);
    }
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
        return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Factura no encontrada.']], 404);
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

      return new JsonResponse(['success' => TRUE, 'data' => [
        'id' => (int) $creditNote->id(),
        'credit_note_number' => $creditNote->get('credit_note_number')->value,
        'invoice_number' => $invoice->get('invoice_number')->value,
        'total' => (float) $creditNote->get('total')->value,
      ], 'meta' => ['timestamp' => time()]], 201);
    }
    catch (\Exception $e) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => $e->getMessage()]], 500);
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
      return new JsonResponse(['success' => FALSE, 'error' => $result], 400);
    }

    return new JsonResponse(['success' => TRUE, 'data' => $result, 'meta' => ['timestamp' => time()]]);
  }

  /**
   * Renders the HTML content for an invoice PDF.
   *
   * @param object $invoice
   *   The legal_invoice entity.
   * @param array $lines
   *   Array of invoice_line entities.
   *
   * @return string
   *   The rendered HTML string with print-friendly CSS.
   */
  protected function renderInvoicePdfHtml(object $invoice, array $lines): string {
    $invoiceNumber = htmlspecialchars($invoice->get('invoice_number')->value ?? '', ENT_QUOTES, 'UTF-8');
    $series = htmlspecialchars($invoice->get('series')->value ?? 'FAC', ENT_QUOTES, 'UTF-8');
    $clientName = htmlspecialchars($invoice->get('client_name')->value ?? '', ENT_QUOTES, 'UTF-8');
    $clientNif = htmlspecialchars($invoice->get('client_nif')->value ?? '', ENT_QUOTES, 'UTF-8');
    $clientAddress = nl2br(htmlspecialchars($invoice->get('client_address')->value ?? '', ENT_QUOTES, 'UTF-8'));
    $clientEmail = htmlspecialchars($invoice->get('client_email')->value ?? '', ENT_QUOTES, 'UTF-8');
    $issueDate = htmlspecialchars($invoice->get('issue_date')->value ?? '', ENT_QUOTES, 'UTF-8');
    $dueDate = htmlspecialchars($invoice->get('due_date')->value ?? '', ENT_QUOTES, 'UTF-8');
    $notes = nl2br(htmlspecialchars($invoice->get('notes')->value ?? '', ENT_QUOTES, 'UTF-8'));
    $paymentMethod = htmlspecialchars($invoice->get('payment_method')->value ?? '', ENT_QUOTES, 'UTF-8');
    $status = htmlspecialchars($invoice->get('status')->value ?? '', ENT_QUOTES, 'UTF-8');
    $subtotal = number_format((float) ($invoice->get('subtotal')->value ?? 0), 2, ',', '.');
    $taxRate = (float) ($invoice->get('tax_rate')->value ?? 21);
    $taxAmount = number_format((float) ($invoice->get('tax_amount')->value ?? 0), 2, ',', '.');
    $irpfRate = (float) ($invoice->get('irpf_rate')->value ?? 0);
    $irpfAmount = number_format((float) ($invoice->get('irpf_amount')->value ?? 0), 2, ',', '.');
    $total = number_format((float) ($invoice->get('total')->value ?? 0), 2, ',', '.');

    // Build line items HTML.
    $linesHtml = '';
    foreach ($lines as $line) {
      $desc = htmlspecialchars(strip_tags($line->get('description')->value ?? ''), ENT_QUOTES, 'UTF-8');
      $qty = number_format((float) ($line->get('quantity')->value ?? 0), 2, ',', '.');
      $unit = htmlspecialchars($line->get('unit')->value ?? 'ud', ENT_QUOTES, 'UTF-8');
      $unitPrice = number_format((float) ($line->get('unit_price')->value ?? 0), 2, ',', '.');
      $lineTotal = number_format((float) ($line->get('line_total')->value ?? 0), 2, ',', '.');

      $linesHtml .= <<<LINE
      <tr>
        <td>{$desc}</td>
        <td style="text-align:center">{$qty} {$unit}</td>
        <td style="text-align:right">{$unitPrice} &euro;</td>
        <td style="text-align:right">{$lineTotal} &euro;</td>
      </tr>
LINE;
    }

    $irpfRow = '';
    if ($irpfRate > 0) {
      $irpfRow = <<<IRPF
      <tr>
        <td colspan="3" style="text-align:right"><strong>Ret. IRPF ({$irpfRate}%):</strong></td>
        <td style="text-align:right">-{$irpfAmount} &euro;</td>
      </tr>
IRPF;
    }

    $paymentMethodLabel = match ($paymentMethod) {
      'transfer' => 'Transferencia bancaria',
      'card' => 'Tarjeta de credito',
      'cash' => 'Efectivo',
      'stripe' => 'Stripe',
      default => $paymentMethod,
    };

    return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Factura {$invoiceNumber}</title>
  <style>
    @page { margin: 20mm; }
    body { font-family: 'Helvetica Neue', Arial, sans-serif; font-size: 12px; color: #333; line-height: 1.5; }
    .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px; border-bottom: 3px solid #059669; padding-bottom: 15px; }
    .header h1 { font-size: 22px; color: #059669; margin: 0; }
    .header .invoice-meta { text-align: right; font-size: 11px; color: #666; }
    .parties { display: flex; justify-content: space-between; margin-bottom: 25px; gap: 20px; }
    .party-box { flex: 1; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 15px; }
    .party-box h3 { margin: 0 0 8px; font-size: 13px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    thead th { background: #059669; color: white; padding: 10px 12px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 0.3px; }
    tbody td { padding: 10px 12px; border-bottom: 1px solid #e2e8f0; }
    tbody tr:nth-child(even) { background: #f8fafc; }
    .totals td { border-bottom: none; padding: 6px 12px; }
    .totals .grand-total td { font-size: 16px; font-weight: bold; color: #059669; border-top: 2px solid #059669; padding-top: 12px; }
    .footer-section { margin-top: 20px; padding: 15px; background: #f8fafc; border-radius: 6px; font-size: 11px; }
    .footer-section h4 { margin: 0 0 8px; font-size: 12px; color: #64748b; }
    .legal-notice { margin-top: 30px; padding: 10px; border-top: 1px solid #e2e8f0; font-size: 9px; color: #94a3b8; text-align: center; }
    @media print { body { margin: 0; } }
  </style>
</head>
<body>
  <div class="header">
    <div>
      <h1>Factura</h1>
      <p style="margin:5px 0 0; font-size:14px; color:#475569;">Serie {$series}</p>
    </div>
    <div class="invoice-meta">
      <p style="margin:0; font-size:18px; font-weight:bold; color:#059669;">{$invoiceNumber}</p>
      <p style="margin:4px 0;"><strong>Fecha emisi&oacute;n:</strong> {$issueDate}</p>
      <p style="margin:4px 0;"><strong>Fecha vencimiento:</strong> {$dueDate}</p>
      <p style="margin:4px 0;"><strong>Estado:</strong> {$status}</p>
    </div>
  </div>

  <div class="parties">
    <div class="party-box">
      <h3>Datos del cliente</h3>
      <p style="margin:2px 0;"><strong>{$clientName}</strong></p>
      <p style="margin:2px 0;">NIF: {$clientNif}</p>
      <p style="margin:2px 0;">{$clientAddress}</p>
      <p style="margin:2px 0;">{$clientEmail}</p>
    </div>
  </div>

  <table>
    <thead>
      <tr>
        <th>Concepto</th>
        <th style="text-align:center">Cantidad</th>
        <th style="text-align:right">Precio ud.</th>
        <th style="text-align:right">Importe</th>
      </tr>
    </thead>
    <tbody>
      {$linesHtml}
    </tbody>
    <tbody class="totals">
      <tr>
        <td colspan="3" style="text-align:right"><strong>Base imponible:</strong></td>
        <td style="text-align:right">{$subtotal} &euro;</td>
      </tr>
      <tr>
        <td colspan="3" style="text-align:right"><strong>IVA ({$taxRate}%):</strong></td>
        <td style="text-align:right">{$taxAmount} &euro;</td>
      </tr>
      {$irpfRow}
      <tr class="grand-total">
        <td colspan="3" style="text-align:right">TOTAL FACTURA:</td>
        <td style="text-align:right">{$total} &euro;</td>
      </tr>
    </tbody>
  </table>

  <div class="footer-section">
    <h4>Forma de pago</h4>
    <p>{$paymentMethodLabel}</p>
  </div>

  {$this->renderNotesSection($notes)}

  <div class="legal-notice">
    Factura generada electr&oacute;nicamente. Formato compatible con Facturae 3.2.2.<br>
    Para obtener la factura electr&oacute;nica en formato XML Facturae, utilice el par&aacute;metro <code>?format=facturae</code>.
  </div>
</body>
</html>
HTML;
  }

  /**
   * Renders an optional notes section for the invoice.
   */
  protected function renderNotesSection(string $notes): string {
    if (empty(strip_tags($notes))) {
      return '';
    }
    return '<div class="footer-section"><h4>Observaciones</h4><p>' . $notes . '</p></div>';
  }

  /**
   * Generates Facturae 3.2.2 XML for a Spanish electronic invoice.
   *
   * @param object $invoice
   *   The legal_invoice entity.
   * @param array $lines
   *   Array of invoice_line entities.
   *
   * @return string
   *   The Facturae 3.2.2 compliant XML string.
   */
  protected function generateFacturaeXml(object $invoice, array $lines): string {
    $invoiceNumber = htmlspecialchars($invoice->get('invoice_number')->value ?? '', ENT_QUOTES, 'UTF-8');
    $series = htmlspecialchars($invoice->get('series')->value ?? 'FAC', ENT_QUOTES, 'UTF-8');
    $issueDate = $invoice->get('issue_date')->value ?? date('Y-m-d');
    $clientName = htmlspecialchars($invoice->get('client_name')->value ?? '', ENT_QUOTES, 'UTF-8');
    $clientNif = htmlspecialchars($invoice->get('client_nif')->value ?? '', ENT_QUOTES, 'UTF-8');
    $clientAddress = htmlspecialchars($invoice->get('client_address')->value ?? '', ENT_QUOTES, 'UTF-8');
    $subtotal = number_format((float) ($invoice->get('subtotal')->value ?? 0), 2, '.', '');
    $taxRate = number_format((float) ($invoice->get('tax_rate')->value ?? 21), 2, '.', '');
    $taxAmount = number_format((float) ($invoice->get('tax_amount')->value ?? 0), 2, '.', '');
    $irpfRate = number_format((float) ($invoice->get('irpf_rate')->value ?? 0), 2, '.', '');
    $irpfAmount = number_format((float) ($invoice->get('irpf_amount')->value ?? 0), 2, '.', '');
    $total = number_format((float) ($invoice->get('total')->value ?? 0), 2, '.', '');
    $paymentMethod = $invoice->get('payment_method')->value ?? 'transfer';
    $dueDate = $invoice->get('due_date')->value ?? date('Y-m-d', strtotime('+30 days'));
    $currency = 'EUR';

    // Map payment method to Facturae payment means code.
    $paymentMeansCode = match ($paymentMethod) {
      'transfer' => '04',
      'card' => '19',
      'cash' => '01',
      default => '04',
    };

    // Load provider/tenant info for the seller party.
    $config = \Drupal::config('jaraba_legal_billing.settings');
    $sellerName = htmlspecialchars($config->get('company_name') ?? 'Empresa Emisora', ENT_QUOTES, 'UTF-8');
    $sellerNif = htmlspecialchars($config->get('company_nif') ?? '', ENT_QUOTES, 'UTF-8');
    $sellerAddress = htmlspecialchars($config->get('company_address') ?? '', ENT_QUOTES, 'UTF-8');
    $sellerPostCode = htmlspecialchars($config->get('company_postal_code') ?? '00000', ENT_QUOTES, 'UTF-8');
    $sellerTown = htmlspecialchars($config->get('company_town') ?? '', ENT_QUOTES, 'UTF-8');
    $sellerProvince = htmlspecialchars($config->get('company_province') ?? '', ENT_QUOTES, 'UTF-8');

    // Build invoice lines XML.
    $invoiceLinesXml = '';
    $lineNum = 1;
    foreach ($lines as $line) {
      $desc = htmlspecialchars(strip_tags($line->get('description')->value ?? ''), ENT_QUOTES, 'UTF-8');
      $qty = number_format((float) ($line->get('quantity')->value ?? 1), 2, '.', '');
      $unitPrice = number_format((float) ($line->get('unit_price')->value ?? 0), 6, '.', '');
      $lineTotal = number_format((float) ($line->get('line_total')->value ?? 0), 2, '.', '');
      $lineTaxRate = number_format((float) ($line->get('tax_rate')->value ?? $taxRate), 2, '.', '');
      $lineTaxAmount = number_format((float) $lineTotal * (float) $lineTaxRate / 100, 2, '.', '');

      $invoiceLinesXml .= <<<XMLLINE
        <InvoiceLine>
          <IssuerContractReference>{$invoiceNumber}</IssuerContractReference>
          <SequenceNumber>{$lineNum}</SequenceNumber>
          <ItemDescription>{$desc}</ItemDescription>
          <Quantity>{$qty}</Quantity>
          <UnitPriceWithoutTax>{$unitPrice}</UnitPriceWithoutTax>
          <TotalCost>{$lineTotal}</TotalCost>
          <GrossAmount>{$lineTotal}</GrossAmount>
          <TaxesOutputs>
            <Tax>
              <TaxTypeCode>01</TaxTypeCode>
              <TaxRate>{$lineTaxRate}</TaxRate>
              <TaxableBase><TotalAmount>{$lineTotal}</TotalAmount></TaxableBase>
              <TaxAmount><TotalAmount>{$lineTaxAmount}</TotalAmount></TaxAmount>
            </Tax>
          </TaxesOutputs>
        </InvoiceLine>

XMLLINE;
      $lineNum++;
    }

    // Build withheld taxes (IRPF) if applicable.
    $withheldXml = '';
    if ((float) $irpfRate > 0) {
      $withheldXml = <<<IRPFXML
          <TaxesWithheld>
            <Tax>
              <TaxTypeCode>04</TaxTypeCode>
              <TaxRate>{$irpfRate}</TaxRate>
              <TaxableBase><TotalAmount>{$subtotal}</TotalAmount></TaxableBase>
              <TaxAmount><TotalAmount>{$irpfAmount}</TotalAmount></TaxAmount>
            </Tax>
          </TaxesWithheld>
IRPFXML;
    }

    $batchId = $series . '-' . $invoiceNumber;

    return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<fe:Facturae xmlns:fe="http://www.facturae.gob.es/formato/Versiones/Facturaev3_2_2.xml" xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
  <FileHeader>
    <SchemaVersion>3.2.2</SchemaVersion>
    <Modality>I</Modality>
    <InvoiceIssuerType>EM</InvoiceIssuerType>
    <Batch>
      <BatchIdentifier>{$batchId}</BatchIdentifier>
      <InvoicesCount>1</InvoicesCount>
      <TotalInvoicesAmount><TotalAmount>{$total}</TotalAmount></TotalInvoicesAmount>
      <TotalOutstandingAmount><TotalAmount>{$total}</TotalAmount></TotalOutstandingAmount>
      <TotalExecutableAmount><TotalAmount>{$total}</TotalAmount></TotalExecutableAmount>
      <InvoiceCurrencyCode>{$currency}</InvoiceCurrencyCode>
    </Batch>
  </FileHeader>
  <Parties>
    <SellerParty>
      <TaxIdentification>
        <PersonTypeCode>J</PersonTypeCode>
        <ResidenceTypeCode>R</ResidenceTypeCode>
        <TaxIdentificationNumber>{$sellerNif}</TaxIdentificationNumber>
      </TaxIdentification>
      <LegalEntity>
        <CorporateName>{$sellerName}</CorporateName>
        <AddressInSpain>
          <Address>{$sellerAddress}</Address>
          <PostCode>{$sellerPostCode}</PostCode>
          <Town>{$sellerTown}</Town>
          <Province>{$sellerProvince}</Province>
          <CountryCode>ESP</CountryCode>
        </AddressInSpain>
      </LegalEntity>
    </SellerParty>
    <BuyerParty>
      <TaxIdentification>
        <PersonTypeCode>F</PersonTypeCode>
        <ResidenceTypeCode>R</ResidenceTypeCode>
        <TaxIdentificationNumber>{$clientNif}</TaxIdentificationNumber>
      </TaxIdentification>
      <Individual>
        <Name>{$clientName}</Name>
        <FirstSurname>{$clientName}</FirstSurname>
        <AddressInSpain>
          <Address>{$clientAddress}</Address>
          <PostCode>00000</PostCode>
          <Town></Town>
          <Province></Province>
          <CountryCode>ESP</CountryCode>
        </AddressInSpain>
      </Individual>
    </BuyerParty>
  </Parties>
  <Invoices>
    <Invoice>
      <InvoiceHeader>
        <InvoiceNumber>{$invoiceNumber}</InvoiceNumber>
        <InvoiceSeriesCode>{$series}</InvoiceSeriesCode>
        <InvoiceDocumentType>FC</InvoiceDocumentType>
        <InvoiceClass>OO</InvoiceClass>
      </InvoiceHeader>
      <InvoiceIssueData>
        <IssueDate>{$issueDate}</IssueDate>
        <InvoiceCurrencyCode>{$currency}</InvoiceCurrencyCode>
        <TaxCurrencyCode>{$currency}</TaxCurrencyCode>
        <LanguageName>es</LanguageName>
      </InvoiceIssueData>
      <TaxesOutputs>
        <Tax>
          <TaxTypeCode>01</TaxTypeCode>
          <TaxRate>{$taxRate}</TaxRate>
          <TaxableBase><TotalAmount>{$subtotal}</TotalAmount></TaxableBase>
          <TaxAmount><TotalAmount>{$taxAmount}</TotalAmount></TaxAmount>
        </Tax>
      </TaxesOutputs>
      {$withheldXml}
      <InvoiceTotals>
        <TotalGrossAmount>{$subtotal}</TotalGrossAmount>
        <TotalGrossAmountBeforeTaxes>{$subtotal}</TotalGrossAmountBeforeTaxes>
        <TotalTaxOutputs>{$taxAmount}</TotalTaxOutputs>
        <TotalTaxesWithheld>{$irpfAmount}</TotalTaxesWithheld>
        <InvoiceTotal>{$total}</InvoiceTotal>
        <TotalOutstandingAmount>{$total}</TotalOutstandingAmount>
        <TotalExecutableAmount>{$total}</TotalExecutableAmount>
      </InvoiceTotals>
      <Items>
{$invoiceLinesXml}      </Items>
      <PaymentDetails>
        <Installment>
          <InstallmentDueDate>{$dueDate}</InstallmentDueDate>
          <InstallmentAmount>{$total}</InstallmentAmount>
          <PaymentMeans>{$paymentMeansCode}</PaymentMeans>
        </Installment>
      </PaymentDetails>
    </Invoice>
  </Invoices>
</fe:Facturae>
XML;
  }

}

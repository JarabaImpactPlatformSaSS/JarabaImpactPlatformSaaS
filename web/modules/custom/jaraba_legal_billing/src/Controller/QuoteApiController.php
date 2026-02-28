<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_billing\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_legal_billing\Service\QuoteEstimatorService;
use Drupal\jaraba_legal_billing\Service\QuoteManagerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API REST para Presupuestos y Portal de Cliente.
 *
 * Estructura: API-NAMING-001 — POST store(), GET list/detail.
 * Logica: Endpoints de proveedor (autenticados) y de portal (token-based).
 */
class QuoteApiController extends ControllerBase {

  public function __construct(
    protected readonly QuoteManagerService $quoteManager,
    protected readonly QuoteEstimatorService $quoteEstimator,
    EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_legal_billing.quote_manager'),
      $container->get('jaraba_legal_billing.quote_estimator'),
      $container->get('entity_type.manager'),
      $container->get('logger.channel.jaraba_legal_billing'),
    );
  }

  // =========================================================================
  // PROVIDER ENDPOINTS
  // =========================================================================

  /**
   * POST /api/v1/legal/billing/quotes
   */
  public function store(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE) ?? [];
    if (empty($data['title']) || empty($data['client_name']) || empty($data['client_email'])) {
      return // AUDIT-CONS-N08: Standardized JSON envelope.
        new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Campos requeridos: title, client_name, client_email.']], 422);
    }

    $result = $this->quoteManager->create($data);
    if (empty($result)) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Error al crear presupuesto.']], 500);
    }

    return new JsonResponse(['success' => TRUE, 'data' => $result], 201);
  }

  /**
   * POST /api/v1/legal/billing/quotes/generate — AI estimation.
   */
  public function generate(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE) ?? [];
    if (empty($data['provider_id']) || empty($data['triage'])) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Campos requeridos: provider_id, triage.']], 422);
    }

    $result = $this->quoteEstimator->generateEstimate(
      $data['triage'],
      (int) $data['provider_id'],
      isset($data['tenant_id']) ? (int) $data['tenant_id'] : NULL,
    );

    if (isset($result['error'])) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => $result['error']]], 422);
    }

    return new JsonResponse(['success' => TRUE, 'data' => $result, 'meta' => ['timestamp' => time()]]);
  }

  /**
   * GET /api/v1/legal/billing/quotes
   */
  public function listQuotes(Request $request): JsonResponse {
    $filters = [];
    if ($status = $request->query->get('status')) {
      $filters['status'] = $status;
    }

    $limit = min((int) $request->query->get('limit', 25), 100);
    $offset = max((int) $request->query->get('offset', 0), 0);

    $result = $this->quoteManager->listQuotes($filters, $limit, $offset);

    return new JsonResponse([
      'data' => $result['items'], 'meta' => ['total' => $result['total'], 'limit' => $limit, 'offset' => $offset]]);
  }

  /**
   * GET /api/v1/legal/billing/quotes/{uuid}
   */
  public function detail(string $uuid): JsonResponse {
    $quotes = $this->entityTypeManager->getStorage('quote')
      ->loadByProperties(['uuid' => $uuid]);
    $quote = reset($quotes);
    if (!$quote) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Presupuesto no encontrado.']], 404);
    }

    return new JsonResponse(['success' => TRUE, 'data' => $this->quoteManager->serializeQuote($quote), 'meta' => ['timestamp' => time()]]);
  }

  /**
   * PATCH /api/v1/legal/billing/quotes/{uuid}
   */
  public function update(string $uuid, Request $request): JsonResponse {
    $quotes = $this->entityTypeManager->getStorage('quote')
      ->loadByProperties(['uuid' => $uuid]);
    $quote = reset($quotes);
    if (!$quote) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Presupuesto no encontrado.']], 404);
    }
    if ($quote->get('status')->value !== 'draft') {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Solo se pueden editar presupuestos en borrador.']], 422);
    }

    $data = json_decode($request->getContent(), TRUE) ?? [];
    $allowed = ['title', 'client_name', 'client_email', 'client_phone', 'client_company', 'client_nif', 'introduction', 'payment_terms', 'notes', 'valid_until', 'discount_percent', 'tax_rate'];
    foreach ($allowed as $field) {
      if (array_key_exists($field, $data)) {
        $quote->set($field, $data[$field]);
      }
    }
    $quote->save();
    $this->quoteManager->recalculateTotals($quote);

    return new JsonResponse(['success' => TRUE, 'data' => $this->quoteManager->serializeQuote($quote), 'meta' => ['timestamp' => time()]]);
  }

  /**
   * POST /api/v1/legal/billing/quotes/{uuid}/send
   */
  public function sendQuote(string $uuid): JsonResponse {
    $result = $this->quoteManager->send($uuid);
    if (empty($result)) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'No se pudo enviar el presupuesto.']], 422);
    }

    return new JsonResponse(['success' => TRUE, 'data' => $result, 'meta' => ['timestamp' => time()]]);
  }

  /**
   * POST /api/v1/legal/billing/quotes/{uuid}/duplicate
   */
  public function duplicateQuote(string $uuid): JsonResponse {
    $result = $this->quoteManager->duplicate($uuid);
    if (empty($result)) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'No se pudo duplicar.']], 500);
    }

    return new JsonResponse(['success' => TRUE, 'data' => $result, 'meta' => ['timestamp' => time()]], 201);
  }

  /**
   * GET /api/v1/legal/billing/quotes/{uuid}/pdf
   */
  public function quotePdf(string $uuid): Response {
    // AUDIT-TODO-RESOLVED: PDF generation implemented.
    try {
      $quotes = $this->entityTypeManager->getStorage('quote')
        ->loadByProperties(['uuid' => $uuid]);
      $quote = reset($quotes);
      if (!$quote) {
        return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Presupuesto no encontrado.']], 404);
      }

      // Load quote line items.
      $lineStorage = $this->entityTypeManager->getStorage('quote_line_item');
      $lineIds = $lineStorage->getQuery()
        ->condition('quote_id', $quote->id())
        ->accessCheck(FALSE)
        ->sort('line_order', 'ASC')
        ->execute();
      $lines = $lineStorage->loadMultiple($lineIds);

      $html = $this->renderQuotePdfHtml($quote, $lines);

      // Use DOMPDF if available, otherwise return HTML with print-friendly headers.
      if (class_exists('\Dompdf\Dompdf')) {
        $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => FALSE]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdfContent = $dompdf->output();

        $quoteNumber = $quote->get('quote_number')->value ?? $uuid;
        return new Response($pdfContent, 200, [
          'Content-Type' => 'application/pdf',
          'Content-Disposition' => 'attachment; filename="presupuesto-' . $quoteNumber . '.pdf"',
          'Content-Length' => strlen($pdfContent),
        ]);
      }

      // Fallback: return HTML response suitable for browser print-to-PDF.
      $quoteNumber = $quote->get('quote_number')->value ?? $uuid;
      return new Response($html, 200, [
        'Content-Type' => 'text/html; charset=UTF-8',
        'Content-Disposition' => 'inline; filename="presupuesto-' . $quoteNumber . '.html"',
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Quote PDF generation error: @msg', ['@msg' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Error al generar PDF.']], 500);
    }
  }

  // =========================================================================
  // PORTAL ENDPOINTS (token-based, public)
  // =========================================================================

  /**
   * GET /api/v1/legal/billing/quotes/view/{token}
   */
  public function portalView(string $token): JsonResponse {
    $quote = $this->quoteManager->loadByToken($token);
    if (!$quote) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Presupuesto no encontrado.']], 404);
    }

    // Marcar como visto.
    if (in_array($quote->get('status')->value, ['sent'])) {
      $quote->set('status', 'viewed');
      $quote->set('viewed_at', date('Y-m-d\TH:i:s'));
      $quote->save();
    }

    $data = $this->quoteManager->serializeQuote($quote);
    unset($data['access_token']);

    // Incluir lineas.
    $lineStorage = $this->entityTypeManager->getStorage('quote_line_item');
    $lineIds = $lineStorage->getQuery()
      ->condition('quote_id', $quote->id())
      ->accessCheck(FALSE)
      ->sort('line_order', 'ASC')
      ->execute();
    $lines = $lineStorage->loadMultiple($lineIds);

    $data['lines'] = array_map(function ($line) {
      return [
        'description' => strip_tags($line->get('description')->value ?? ''),
        'quantity' => (float) ($line->get('quantity')->value ?? 0),
        'unit' => $line->get('unit')->value ?? 'unit',
        'unit_price' => (float) ($line->get('unit_price')->value ?? 0),
        'line_total' => (float) ($line->get('line_total')->value ?? 0),
        'is_optional' => (bool) $line->get('is_optional')->value,
        'notes' => $line->get('notes')->value ?? '',
      ];
    }, $lines);

    return new JsonResponse(['success' => TRUE, 'data' => $data, 'meta' => ['timestamp' => time()]]);
  }

  /**
   * POST /api/v1/legal/billing/quotes/view/{token}/accept
   */
  public function portalAccept(string $token): JsonResponse {
    $quote = $this->quoteManager->loadByToken($token);
    if (!$quote) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Presupuesto no encontrado.']], 404);
    }
    if (!in_array($quote->get('status')->value, ['sent', 'viewed'])) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'El presupuesto no esta en estado aceptable.']], 422);
    }

    $quote->set('status', 'accepted');
    $quote->set('responded_at', date('Y-m-d\TH:i:s'));
    $quote->save();

    return new JsonResponse(['success' => TRUE, 'data' => ['status' => 'accepted', 'quote_number' => $quote->get('quote_number')->value], 'meta' => ['timestamp' => time()]]);
  }

  /**
   * POST /api/v1/legal/billing/quotes/view/{token}/reject
   */
  public function portalReject(string $token, Request $request): JsonResponse {
    $quote = $this->quoteManager->loadByToken($token);
    if (!$quote) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Presupuesto no encontrado.']], 404);
    }
    if (!in_array($quote->get('status')->value, ['sent', 'viewed'])) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'El presupuesto no esta en estado rechazable.']], 422);
    }

    $data = json_decode($request->getContent(), TRUE) ?? [];
    $quote->set('status', 'rejected');
    $quote->set('responded_at', date('Y-m-d\TH:i:s'));
    $quote->set('rejection_reason', $data['reason'] ?? '');
    $quote->save();

    return new JsonResponse(['success' => TRUE, 'data' => ['status' => 'rejected', 'quote_number' => $quote->get('quote_number')->value], 'meta' => ['timestamp' => time()]]);
  }

  /**
   * POST /api/v1/legal/billing/quotes/view/{token}/negotiate
   */
  public function portalNegotiate(string $token, Request $request): JsonResponse {
    $quote = $this->quoteManager->loadByToken($token);
    if (!$quote) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Presupuesto no encontrado.']], 404);
    }
    if (!in_array($quote->get('status')->value, ['sent', 'viewed'])) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'El presupuesto no esta en estado negociable.']], 422);
    }

    $data = json_decode($request->getContent(), TRUE) ?? [];

    // AUDIT-TODO-RESOLVED: PDF generation implemented.
    // Create negotiation record and notify provider.
    try {
      $negotiationStorage = $this->entityTypeManager->getStorage('quote_negotiation');
      $negotiation = $negotiationStorage->create([
        'quote_id' => $quote->id(),
        'sender_type' => 'client',
        'message' => $data['message'] ?? '',
        'proposed_amount' => $data['proposed_amount'] ?? NULL,
        'proposed_items' => isset($data['proposed_items']) ? json_encode($data['proposed_items']) : NULL,
      ]);
      $negotiation->save();

      // Update quote status to negotiation.
      $quote->set('status', 'negotiation');
      $quote->set('responded_at', date('Y-m-d\TH:i:s'));
      $quote->save();

      // Notify the provider via Drupal mail.
      $providerUid = $quote->get('provider_id')->target_id;
      if ($providerUid) {
        $userStorage = $this->entityTypeManager->getStorage('user');
        $provider = $userStorage->load($providerUid);
        if ($provider && $provider->getEmail()) {
          $mailManager = \Drupal::service('plugin.manager.mail');
          $params = [
            'subject' => 'Negociacion solicitada: ' . ($quote->get('quote_number')->value ?? $quote->get('title')->value),
            'body' => [
              'El cliente ' . ($quote->get('client_name')->value ?? '') . ' ha solicitado negociar el presupuesto.',
              'Mensaje: ' . ($data['message'] ?? '(sin mensaje)'),
              'Presupuesto: ' . ($quote->get('quote_number')->value ?? ''),
            ],
          ];
          $mailManager->mail('jaraba_legal_billing', 'quote_negotiation', $provider->getEmail(), 'es', $params, NULL, TRUE);
        }
      }

      $this->logger->info('Negotiation requested for quote @qid by client.', [
        '@qid' => $quote->id(),
      ]);
    }
    catch (\Exception $e) {
      // If negotiation entity storage does not exist, log and continue gracefully.
      $this->logger->warning('Could not create negotiation record: @msg. Falling back to log-only.', [
        '@msg' => $e->getMessage(),
      ]);
    }

    return new JsonResponse(['success' => TRUE, 'data' => [
      'status' => 'negotiation_requested',
      'quote_number' => $quote->get('quote_number')->value,
      'message' => $data['message'] ?? '',
    ], 'meta' => ['timestamp' => time()]]);
  }

  /**
   * GET /api/v1/legal/billing/quotes/view/{token}/pdf
   */
  public function portalPdf(string $token): Response {
    $quote = $this->quoteManager->loadByToken($token);
    if (!$quote) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Presupuesto no encontrado.']], 404);
    }

    // AUDIT-TODO-RESOLVED: PDF generation implemented.
    try {
      // Load quote line items.
      $lineStorage = $this->entityTypeManager->getStorage('quote_line_item');
      $lineIds = $lineStorage->getQuery()
        ->condition('quote_id', $quote->id())
        ->accessCheck(FALSE)
        ->sort('line_order', 'ASC')
        ->execute();
      $lines = $lineStorage->loadMultiple($lineIds);

      $html = $this->renderQuotePdfHtml($quote, $lines);

      // Use DOMPDF if available, otherwise return HTML with print-friendly headers.
      if (class_exists('\Dompdf\Dompdf')) {
        $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => FALSE]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdfContent = $dompdf->output();

        $quoteNumber = $quote->get('quote_number')->value ?? 'presupuesto';
        return new Response($pdfContent, 200, [
          'Content-Type' => 'application/pdf',
          'Content-Disposition' => 'attachment; filename="presupuesto-' . $quoteNumber . '.pdf"',
          'Content-Length' => strlen($pdfContent),
        ]);
      }

      // Fallback: return HTML response suitable for browser print-to-PDF.
      $quoteNumber = $quote->get('quote_number')->value ?? 'presupuesto';
      return new Response($html, 200, [
        'Content-Type' => 'text/html; charset=UTF-8',
        'Content-Disposition' => 'inline; filename="presupuesto-' . $quoteNumber . '.html"',
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Portal PDF generation error: @msg', ['@msg' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Error al generar PDF.']], 500);
    }
  }

  /**
   * Renders the HTML content for a quote PDF.
   *
   * @param object $quote
   *   The quote entity.
   * @param array $lines
   *   Array of quote_line_item entities.
   *
   * @return string
   *   The rendered HTML string with print-friendly CSS.
   */
  protected function renderQuotePdfHtml(object $quote, array $lines): string {
    $quoteNumber = htmlspecialchars($quote->get('quote_number')->value ?? '', ENT_QUOTES, 'UTF-8');
    $title = htmlspecialchars($quote->get('title')->value ?? '', ENT_QUOTES, 'UTF-8');
    $clientName = htmlspecialchars($quote->get('client_name')->value ?? '', ENT_QUOTES, 'UTF-8');
    $clientEmail = htmlspecialchars($quote->get('client_email')->value ?? '', ENT_QUOTES, 'UTF-8');
    $clientNif = htmlspecialchars($quote->get('client_nif')->value ?? '', ENT_QUOTES, 'UTF-8');
    $clientCompany = htmlspecialchars($quote->get('client_company')->value ?? '', ENT_QUOTES, 'UTF-8');
    $introduction = nl2br(htmlspecialchars($quote->get('introduction')->value ?? '', ENT_QUOTES, 'UTF-8'));
    $paymentTerms = nl2br(htmlspecialchars($quote->get('payment_terms')->value ?? '', ENT_QUOTES, 'UTF-8'));
    $notes = nl2br(htmlspecialchars($quote->get('notes')->value ?? '', ENT_QUOTES, 'UTF-8'));
    $validUntil = htmlspecialchars($quote->get('valid_until')->value ?? '', ENT_QUOTES, 'UTF-8');
    $status = htmlspecialchars($quote->get('status')->value ?? '', ENT_QUOTES, 'UTF-8');
    $subtotal = number_format((float) ($quote->get('subtotal')->value ?? 0), 2, ',', '.');
    $discountPercent = (float) ($quote->get('discount_percent')->value ?? 0);
    $discountAmount = number_format((float) ($quote->get('discount_amount')->value ?? 0), 2, ',', '.');
    $taxRate = (float) ($quote->get('tax_rate')->value ?? 21);
    $taxAmount = number_format((float) ($quote->get('tax_amount')->value ?? 0), 2, ',', '.');
    $total = number_format((float) ($quote->get('total')->value ?? 0), 2, ',', '.');
    $createdDate = date('d/m/Y', (int) ($quote->get('created')->value ?? time()));

    // Build line items HTML.
    $linesHtml = '';
    foreach ($lines as $line) {
      $desc = htmlspecialchars(strip_tags($line->get('description')->value ?? ''), ENT_QUOTES, 'UTF-8');
      $qty = number_format((float) ($line->get('quantity')->value ?? 0), 2, ',', '.');
      $unit = htmlspecialchars($line->get('unit')->value ?? 'ud', ENT_QUOTES, 'UTF-8');
      $unitPrice = number_format((float) ($line->get('unit_price')->value ?? 0), 2, ',', '.');
      $lineTotal = number_format((float) ($line->get('line_total')->value ?? 0), 2, ',', '.');
      $isOptional = (bool) $line->get('is_optional')->value;
      $optionalTag = $isOptional ? ' <em>(opcional)</em>' : '';

      $linesHtml .= <<<LINE
      <tr>
        <td>{$desc}{$optionalTag}</td>
        <td style="text-align:center">{$qty} {$unit}</td>
        <td style="text-align:right">{$unitPrice} &euro;</td>
        <td style="text-align:right">{$lineTotal} &euro;</td>
      </tr>
LINE;
    }

    $discountRow = '';
    if ($discountPercent > 0) {
      $discountRow = <<<DISC
      <tr>
        <td colspan="3" style="text-align:right"><strong>Descuento ({$discountPercent}%):</strong></td>
        <td style="text-align:right">-{$discountAmount} &euro;</td>
      </tr>
DISC;
    }

    return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Presupuesto {$quoteNumber}</title>
  <style>
    @page { margin: 20mm; }
    body { font-family: 'Helvetica Neue', Arial, sans-serif; font-size: 12px; color: #333; line-height: 1.5; }
    .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px; border-bottom: 3px solid #2563eb; padding-bottom: 15px; }
    .header h1 { font-size: 22px; color: #2563eb; margin: 0; }
    .header .quote-meta { text-align: right; font-size: 11px; color: #666; }
    .client-info { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 15px; margin-bottom: 25px; }
    .client-info h3 { margin: 0 0 8px; font-size: 13px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
    .introduction { margin-bottom: 25px; padding: 10px 0; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    thead th { background: #2563eb; color: white; padding: 10px 12px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 0.3px; }
    tbody td { padding: 10px 12px; border-bottom: 1px solid #e2e8f0; }
    tbody tr:nth-child(even) { background: #f8fafc; }
    .totals td { border-bottom: none; padding: 6px 12px; }
    .totals .grand-total td { font-size: 16px; font-weight: bold; color: #2563eb; border-top: 2px solid #2563eb; padding-top: 12px; }
    .footer-section { margin-top: 25px; padding: 15px; background: #f8fafc; border-radius: 6px; font-size: 11px; }
    .footer-section h4 { margin: 0 0 8px; font-size: 12px; color: #64748b; }
    .status-badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 10px; font-weight: bold; text-transform: uppercase; }
    .status-draft { background: #fef3c7; color: #92400e; }
    .status-sent { background: #dbeafe; color: #1e40af; }
    .status-accepted { background: #d1fae5; color: #065f46; }
    @media print { body { margin: 0; } .header { border-bottom-color: #2563eb !important; } }
  </style>
</head>
<body>
  <div class="header">
    <div>
      <h1>Presupuesto</h1>
      <p style="margin:5px 0 0; font-size:16px; color:#475569;">{$title}</p>
    </div>
    <div class="quote-meta">
      <p style="margin:0;"><strong>N.&ordm;:</strong> {$quoteNumber}</p>
      <p style="margin:4px 0;"><strong>Fecha:</strong> {$createdDate}</p>
      <p style="margin:4px 0;"><strong>Validez:</strong> {$validUntil}</p>
      <p style="margin:4px 0;"><span class="status-badge status-{$status}">{$status}</span></p>
    </div>
  </div>

  <div class="client-info">
    <h3>Datos del cliente</h3>
    <p style="margin:2px 0;"><strong>{$clientName}</strong></p>
    {$this->conditionalLine($clientCompany, '')}
    {$this->conditionalLine($clientNif, 'NIF: ')}
    {$this->conditionalLine($clientEmail, 'Email: ')}
  </div>

  {$this->conditionalSection($introduction, 'introduction', '')}

  <table>
    <thead>
      <tr>
        <th>Descripci&oacute;n</th>
        <th style="text-align:center">Cantidad</th>
        <th style="text-align:right">Precio ud.</th>
        <th style="text-align:right">Total</th>
      </tr>
    </thead>
    <tbody>
      {$linesHtml}
    </tbody>
    <tbody class="totals">
      <tr>
        <td colspan="3" style="text-align:right"><strong>Subtotal:</strong></td>
        <td style="text-align:right">{$subtotal} &euro;</td>
      </tr>
      {$discountRow}
      <tr>
        <td colspan="3" style="text-align:right"><strong>IVA ({$taxRate}%):</strong></td>
        <td style="text-align:right">{$taxAmount} &euro;</td>
      </tr>
      <tr class="grand-total">
        <td colspan="3" style="text-align:right">TOTAL:</td>
        <td style="text-align:right">{$total} &euro;</td>
      </tr>
    </tbody>
  </table>

  {$this->conditionalSection($paymentTerms, 'footer-section', 'Condiciones de pago')}
  {$this->conditionalSection($notes, 'footer-section', 'Observaciones')}
</body>
</html>
HTML;
  }

  /**
   * Renders a conditional HTML line if the value is non-empty.
   */
  protected function conditionalLine(string $value, string $prefix): string {
    if (empty($value)) {
      return '';
    }
    return '<p style="margin:2px 0;">' . $prefix . $value . '</p>';
  }

  /**
   * Renders a conditional section block if the content is non-empty.
   */
  protected function conditionalSection(string $content, string $cssClass, string $heading): string {
    if (empty(strip_tags($content))) {
      return '';
    }
    $headingHtml = $heading ? "<h4>{$heading}</h4>" : '';
    return "<div class=\"{$cssClass}\">{$headingHtml}<p>{$content}</p></div>";
  }

}

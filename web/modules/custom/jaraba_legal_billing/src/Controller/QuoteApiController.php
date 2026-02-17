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
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {}

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
      return new JsonResponse(['error' => 'Campos requeridos: title, client_name, client_email.'], 422);
    }

    $result = $this->quoteManager->create($data);
    if (empty($result)) {
      return new JsonResponse(['error' => 'Error al crear presupuesto.'], 500);
    }

    return new JsonResponse(['data' => $result], 201);
  }

  /**
   * POST /api/v1/legal/billing/quotes/generate — AI estimation.
   */
  public function generate(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE) ?? [];
    if (empty($data['provider_id']) || empty($data['triage'])) {
      return new JsonResponse(['error' => 'Campos requeridos: provider_id, triage.'], 422);
    }

    $result = $this->quoteEstimator->generateEstimate(
      $data['triage'],
      (int) $data['provider_id'],
      isset($data['tenant_id']) ? (int) $data['tenant_id'] : NULL,
    );

    if (isset($result['error'])) {
      return new JsonResponse(['error' => $result['error']], 422);
    }

    return new JsonResponse(['data' => $result]);
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
      'data' => $result['items'],
      'meta' => ['total' => $result['total'], 'limit' => $limit, 'offset' => $offset],
    ]);
  }

  /**
   * GET /api/v1/legal/billing/quotes/{uuid}
   */
  public function detail(string $uuid): JsonResponse {
    $quotes = $this->entityTypeManager->getStorage('quote')
      ->loadByProperties(['uuid' => $uuid]);
    $quote = reset($quotes);
    if (!$quote) {
      return new JsonResponse(['error' => 'Presupuesto no encontrado.'], 404);
    }

    return new JsonResponse(['data' => $this->quoteManager->serializeQuote($quote)]);
  }

  /**
   * PATCH /api/v1/legal/billing/quotes/{uuid}
   */
  public function update(string $uuid, Request $request): JsonResponse {
    $quotes = $this->entityTypeManager->getStorage('quote')
      ->loadByProperties(['uuid' => $uuid]);
    $quote = reset($quotes);
    if (!$quote) {
      return new JsonResponse(['error' => 'Presupuesto no encontrado.'], 404);
    }
    if ($quote->get('status')->value !== 'draft') {
      return new JsonResponse(['error' => 'Solo se pueden editar presupuestos en borrador.'], 422);
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

    return new JsonResponse(['data' => $this->quoteManager->serializeQuote($quote)]);
  }

  /**
   * POST /api/v1/legal/billing/quotes/{uuid}/send
   */
  public function sendQuote(string $uuid): JsonResponse {
    $result = $this->quoteManager->send($uuid);
    if (empty($result)) {
      return new JsonResponse(['error' => 'No se pudo enviar el presupuesto.'], 422);
    }

    return new JsonResponse(['data' => $result]);
  }

  /**
   * POST /api/v1/legal/billing/quotes/{uuid}/duplicate
   */
  public function duplicateQuote(string $uuid): JsonResponse {
    $result = $this->quoteManager->duplicate($uuid);
    if (empty($result)) {
      return new JsonResponse(['error' => 'No se pudo duplicar.'], 500);
    }

    return new JsonResponse(['data' => $result], 201);
  }

  /**
   * GET /api/v1/legal/billing/quotes/{uuid}/pdf
   */
  public function quotePdf(string $uuid): JsonResponse {
    // TODO: Integrar con servicio de generacion PDF.
    return new JsonResponse(['data' => ['uuid' => $uuid, 'pdf_url' => NULL, 'status' => 'pending_integration']]);
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
      return new JsonResponse(['error' => 'Presupuesto no encontrado.'], 404);
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

    return new JsonResponse(['data' => $data]);
  }

  /**
   * POST /api/v1/legal/billing/quotes/view/{token}/accept
   */
  public function portalAccept(string $token): JsonResponse {
    $quote = $this->quoteManager->loadByToken($token);
    if (!$quote) {
      return new JsonResponse(['error' => 'Presupuesto no encontrado.'], 404);
    }
    if (!in_array($quote->get('status')->value, ['sent', 'viewed'])) {
      return new JsonResponse(['error' => 'El presupuesto no esta en estado aceptable.'], 422);
    }

    $quote->set('status', 'accepted');
    $quote->set('responded_at', date('Y-m-d\TH:i:s'));
    $quote->save();

    return new JsonResponse(['data' => ['status' => 'accepted', 'quote_number' => $quote->get('quote_number')->value]]);
  }

  /**
   * POST /api/v1/legal/billing/quotes/view/{token}/reject
   */
  public function portalReject(string $token, Request $request): JsonResponse {
    $quote = $this->quoteManager->loadByToken($token);
    if (!$quote) {
      return new JsonResponse(['error' => 'Presupuesto no encontrado.'], 404);
    }
    if (!in_array($quote->get('status')->value, ['sent', 'viewed'])) {
      return new JsonResponse(['error' => 'El presupuesto no esta en estado rechazable.'], 422);
    }

    $data = json_decode($request->getContent(), TRUE) ?? [];
    $quote->set('status', 'rejected');
    $quote->set('responded_at', date('Y-m-d\TH:i:s'));
    $quote->set('rejection_reason', $data['reason'] ?? '');
    $quote->save();

    return new JsonResponse(['data' => ['status' => 'rejected', 'quote_number' => $quote->get('quote_number')->value]]);
  }

  /**
   * POST /api/v1/legal/billing/quotes/view/{token}/negotiate
   */
  public function portalNegotiate(string $token, Request $request): JsonResponse {
    $quote = $this->quoteManager->loadByToken($token);
    if (!$quote) {
      return new JsonResponse(['error' => 'Presupuesto no encontrado.'], 404);
    }

    $data = json_decode($request->getContent(), TRUE) ?? [];
    // TODO: Crear registro de negociacion / notificar al proveedor.

    return new JsonResponse(['data' => [
      'status' => 'negotiation_requested',
      'quote_number' => $quote->get('quote_number')->value,
      'message' => $data['message'] ?? '',
    ]]);
  }

  /**
   * GET /api/v1/legal/billing/quotes/view/{token}/pdf
   */
  public function portalPdf(string $token): JsonResponse {
    $quote = $this->quoteManager->loadByToken($token);
    if (!$quote) {
      return new JsonResponse(['error' => 'Presupuesto no encontrado.'], 404);
    }

    // TODO: Integrar con servicio de generacion PDF.
    return new JsonResponse(['data' => ['pdf_url' => NULL, 'status' => 'pending_integration']]);
  }

}

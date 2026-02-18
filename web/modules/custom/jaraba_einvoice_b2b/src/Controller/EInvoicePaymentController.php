<?php

declare(strict_types=1);

namespace Drupal\jaraba_einvoice_b2b\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_einvoice_b2b\Service\EInvoicePaymentStatusService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API controller for E-Invoice payment status operations.
 *
 * Handles payment recording, history, SPFE communication,
 * overdue detection, and morosity reports.
 *
 * Spec: Doc 181, Section 4.3.
 * Plan: FASE 10, entregable F10-5.
 */
class EInvoicePaymentController extends ControllerBase {

  public function __construct(
    protected EInvoicePaymentStatusService $paymentService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_einvoice_b2b.payment_status_service'),
    );
  }

  /**
   * POST /api/v1/einvoice/payment/{id}/record — Record a payment.
   */
  public function recordPayment(int $einvoice_document, Request $request): JsonResponse {
    $content = json_decode($request->getContent(), TRUE);

    $required = ['amount'];
    foreach ($required as $field) {
      if (empty($content[$field])) {
        return new JsonResponse([
          'success' => FALSE,
          'data' => NULL,
          'meta' => ['error' => "Field '{$field}' is required."],
        ], 400);
      }
    }

    try {
      $eventId = $this->paymentService->recordPayment($einvoice_document, $content);

      return new JsonResponse([
        'success' => TRUE,
        'data' => ['event_id' => $eventId],
        'meta' => [],
      ], 201);
    }
    catch (\InvalidArgumentException $e) {
      \Drupal::logger('jaraba_einvoice_b2b')->error('Payment recording validation error: @msg', ['@msg' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'data' => NULL, 'meta' => ['error' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.']], 404);
    }
    catch (\Throwable $e) {
      \Drupal::logger('jaraba_einvoice_b2b')->error('Payment recording failed: @msg', ['@msg' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'data' => NULL, 'meta' => ['error' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.']], 500);
    }
  }

  /**
   * GET /api/v1/einvoice/payment/{id}/history — Payment history.
   */
  public function paymentHistory(int $einvoice_document): JsonResponse {
    $history = $this->paymentService->getPaymentHistory($einvoice_document);

    return new JsonResponse([
      'success' => TRUE,
      'data' => $history,
      'meta' => ['count' => count($history)],
    ]);
  }

  /**
   * POST /api/v1/einvoice/payment/{id}/communicate — Communicate to SPFE.
   */
  public function communicateToSPFE(int $event_id): JsonResponse {
    $success = $this->paymentService->communicateToSPFE($event_id);

    return new JsonResponse([
      'success' => $success,
      'data' => ['communicated' => $success],
      'meta' => [],
    ], $success ? 200 : 422);
  }

  /**
   * GET /api/v1/einvoice/payment/overdue — Overdue invoices.
   */
  public function overdueInvoices(Request $request): JsonResponse {
    $tenantId = (int) $request->query->get('tenant_id', 0);
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'data' => NULL, 'meta' => ['error' => 'tenant_id is required.']], 400);
    }

    $results = $this->paymentService->detectMorosidad($tenantId);
    $data = array_map(fn($r) => $r->toArray(), $results);

    return new JsonResponse([
      'success' => TRUE,
      'data' => $data,
      'meta' => ['count' => count($data)],
    ]);
  }

  /**
   * GET /api/v1/einvoice/payment/morosity-report — Morosity metrics.
   */
  public function morosityReport(Request $request): JsonResponse {
    $tenantId = (int) $request->query->get('tenant_id', 0);
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'data' => NULL, 'meta' => ['error' => 'tenant_id is required.']], 400);
    }

    $report = $this->paymentService->calculateMorosityMetrics($tenantId);

    return new JsonResponse([
      'success' => TRUE,
      'data' => $report->toArray(),
      'meta' => [],
    ]);
  }

}

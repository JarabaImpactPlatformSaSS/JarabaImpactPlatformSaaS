<?php

declare(strict_types=1);

namespace Drupal\jaraba_einvoice_b2b\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\jaraba_einvoice_b2b\Service\SPFEClient\SPFEClientInterface;
use Drupal\jaraba_einvoice_b2b\ValueObject\MorosityReport;
use Drupal\jaraba_einvoice_b2b\ValueObject\OverdueResult;
use Psr\Log\LoggerInterface;

/**
 * Payment status management service.
 *
 * Handles payment tracking and morosity detection conforming to:
 *   - Ley 3/2004 (morosidad en operaciones comerciales)
 *   - Ley 18/2022 (Crea y Crece) — payment status reporting to SPFE
 *
 * Legal deadlines:
 *   - General: 30 days from invoice receipt
 *   - Maximum agreed: 60 days (cannot exceed by contract)
 *   - Public administration: 30 days maximum
 *
 * Spec: Doc 181, Section 3.4.
 * Plan: FASE 10, entregable F10-2.
 */
class EInvoicePaymentStatusService {

  /**
   * The logger.
   */
  protected LoggerInterface $logger;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected SPFEClientInterface $spfeClient,
    LoggerChannelFactoryInterface $logger_factory,
  ) {
    $this->logger = $logger_factory->get('jaraba_einvoice_b2b');
  }

  /**
   * Records a payment event for an e-invoice document.
   *
   * @param int $documentId
   *   The EInvoiceDocument entity ID.
   * @param array $paymentData
   *   Payment data: amount, payment_date, payment_method, payment_reference.
   *
   * @return int
   *   The created EInvoicePaymentEvent entity ID.
   */
  public function recordPayment(int $documentId, array $paymentData): int {
    $document = $this->entityTypeManager->getStorage('einvoice_document')->load($documentId);
    if (!$document) {
      throw new \InvalidArgumentException("Document {$documentId} not found.");
    }

    $totalAmount = (float) ($document->get('total_amount')->value ?? 0);
    $paymentAmount = (float) ($paymentData['amount'] ?? 0);

    // Determine event type.
    $previousPayments = $this->getTotalPaidAmount($documentId);
    $cumulativePaid = $previousPayments + $paymentAmount;
    $eventType = $cumulativePaid >= $totalAmount ? 'payment_received' : 'payment_partial';

    // Create payment event.
    $eventStorage = $this->entityTypeManager->getStorage('einvoice_payment_event');
    $event = $eventStorage->create([
      'tenant_id' => $document->get('tenant_id')->target_id,
      'einvoice_document_id' => $documentId,
      'event_type' => $eventType,
      'amount' => number_format($paymentAmount, 2, '.', ''),
      'payment_date' => $paymentData['payment_date'] ?? date('Y-m-d'),
      'payment_method' => $paymentData['payment_method'] ?? NULL,
      'payment_reference' => $paymentData['payment_reference'] ?? NULL,
    ]);
    $event->save();

    // Update document payment status.
    $newStatus = $cumulativePaid >= $totalAmount ? 'paid' : 'partial';
    $this->updateStatus($documentId, $newStatus);

    $this->logger->info('Payment @type recorded for document @id: @amount', [
      '@type' => $eventType,
      '@id' => $documentId,
      '@amount' => $paymentAmount,
    ]);

    return (int) $event->id();
  }

  /**
   * Updates the payment status of a document.
   *
   * @param int $documentId
   *   The EInvoiceDocument entity ID.
   * @param string $status
   *   New status: pending, partial, paid, overdue, disputed.
   */
  public function updateStatus(int $documentId, string $status): void {
    $document = $this->entityTypeManager->getStorage('einvoice_document')->load($documentId);
    if (!$document) {
      return;
    }

    $document->set('payment_status', $status);
    $document->set('payment_status_date', date('Y-m-d\TH:i:s'));
    $document->save();
  }

  /**
   * Communicates a payment event to the SPFE.
   *
   * @param int $eventId
   *   The EInvoicePaymentEvent entity ID.
   *
   * @return bool
   *   TRUE if communication was successful.
   */
  public function communicateToSPFE(int $eventId): bool {
    $event = $this->entityTypeManager->getStorage('einvoice_payment_event')->load($eventId);
    if (!$event) {
      return FALSE;
    }

    $tenantId = (int) $event->get('tenant_id')->target_id;

    try {
      $result = $this->spfeClient->submitPaymentStatus($eventId, $tenantId);

      $event->set('communicated_to_spfe', TRUE);
      $event->set('communication_timestamp', date('Y-m-d\TH:i:s'));
      $event->set('communication_response', json_encode($result->toArray()));
      $event->save();

      return $result->success;
    }
    catch (\Throwable $e) {
      $this->logger->error('SPFE payment communication failed for event @id: @error', [
        '@id' => $eventId,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Returns the payment event history for a document.
   *
   * @param int $documentId
   *   The EInvoiceDocument entity ID.
   *
   * @return array
   *   Array of payment event data.
   */
  public function getPaymentHistory(int $documentId): array {
    $storage = $this->entityTypeManager->getStorage('einvoice_payment_event');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('einvoice_document_id', $documentId)
      ->sort('created', 'ASC')
      ->execute();

    $events = [];
    foreach ($storage->loadMultiple($ids) as $event) {
      $events[] = [
        'id' => $event->id(),
        'event_type' => $event->get('event_type')->value,
        'amount' => $event->get('amount')->value,
        'payment_date' => $event->get('payment_date')->value,
        'payment_method' => $event->get('payment_method')->value,
        'payment_reference' => $event->get('payment_reference')->value,
        'communicated_to_spfe' => (bool) $event->get('communicated_to_spfe')->value,
        'created' => $event->get('created')->value,
      ];
    }

    return $events;
  }

  /**
   * Checks if a document is overdue.
   *
   * @param int $documentId
   *   The EInvoiceDocument entity ID.
   *
   * @return \Drupal\jaraba_einvoice_b2b\ValueObject\OverdueResult
   */
  public function checkOverdue(int $documentId): OverdueResult {
    $document = $this->entityTypeManager->getStorage('einvoice_document')->load($documentId);
    if (!$document) {
      return OverdueResult::notOverdue($documentId);
    }

    $paymentStatus = $document->get('payment_status')->value ?? 'pending';
    if (in_array($paymentStatus, ['paid', 'disputed'], TRUE)) {
      return OverdueResult::notOverdue($documentId);
    }

    $dueDate = $document->get('due_date')->value;
    if (empty($dueDate)) {
      return OverdueResult::notOverdue($documentId);
    }

    $overdueDays = $this->calculateOverdueDays($dueDate);
    if ($overdueDays <= 0) {
      return OverdueResult::notOverdue($documentId);
    }

    return OverdueResult::overdue(
      documentId: $documentId,
      overdueDays: $overdueDays,
      dueDate: $dueDate,
      invoiceNumber: $document->get('invoice_number')->value,
      legalMaxDays: 60,
    );
  }

  /**
   * Detects all overdue invoices for a tenant (batch operation).
   *
   * @param int $tenantId
   *   The tenant ID.
   *
   * @return array
   *   Array of OverdueResult objects.
   */
  public function detectMorosidad(int $tenantId): array {
    $storage = $this->entityTypeManager->getStorage('einvoice_document');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenantId)
      ->condition('direction', 'outbound')
      ->condition('payment_status', ['pending', 'partial'], 'IN')
      ->condition('due_date', date('Y-m-d'), '<')
      ->execute();

    $results = [];
    foreach ($ids as $id) {
      $result = $this->checkOverdue((int) $id);
      if ($result->isOverdue) {
        $results[] = $result;
      }
    }

    return $results;
  }

  /**
   * Calculates morosity metrics for a tenant.
   *
   * @param int $tenantId
   *   The tenant ID.
   *
   * @return \Drupal\jaraba_einvoice_b2b\ValueObject\MorosityReport
   */
  public function calculateMorosityMetrics(int $tenantId): MorosityReport {
    $storage = $this->entityTypeManager->getStorage('einvoice_document');
    $totalIds = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenantId)
      ->condition('direction', 'outbound')
      ->execute();

    $overdueResults = $this->detectMorosidad($tenantId);

    return MorosityReport::fromData(
      tenantId: $tenantId,
      overdueResults: $overdueResults,
      totalInvoices: count($totalIds),
    );
  }

  /**
   * Calculates the number of overdue days from a due date.
   *
   * @param string $dueDate
   *   Due date in Y-m-d format.
   *
   * @return int
   *   Days overdue (negative = not yet due).
   */
  public function calculateOverdueDays(string $dueDate): int {
    try {
      $due = new \DateTimeImmutable($dueDate);
      $now = new \DateTimeImmutable('today');
      $diff = $now->diff($due);
      return $diff->invert ? $diff->days : -$diff->days;
    }
    catch (\Exception) {
      return 0;
    }
  }

  /**
   * Gets the total amount already paid for a document.
   */
  protected function getTotalPaidAmount(int $documentId): float {
    $storage = $this->entityTypeManager->getStorage('einvoice_payment_event');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('einvoice_document_id', $documentId)
      ->condition('event_type', ['payment_received', 'payment_partial'], 'IN')
      ->execute();

    $total = 0.0;
    foreach ($storage->loadMultiple($ids) as $event) {
      $total += (float) ($event->get('amount')->value ?? 0);
    }

    return $total;
  }

}

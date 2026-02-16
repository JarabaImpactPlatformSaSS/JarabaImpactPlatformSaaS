<?php

declare(strict_types=1);

namespace Drupal\jaraba_einvoice_b2b\ValueObject;

/**
 * Result of an overdue check on an e-invoice document.
 *
 * Returned by EInvoicePaymentStatusService::checkOverdue().
 * Contains overdue days, severity level, and applicable legal deadlines.
 *
 * Legal reference: Ley 3/2004 (morosidad en operaciones comerciales).
 * Spec: Doc 181, Section 3.4.
 */
final class OverdueResult {

  public function __construct(
    public readonly int $documentId,
    public readonly bool $isOverdue,
    public readonly int $overdueDays,
    public readonly string $severity,
    public readonly int $legalMaxDays,
    public readonly ?string $dueDate,
    public readonly ?string $invoiceNumber,
  ) {}

  /**
   * Creates a result for a non-overdue document.
   */
  public static function notOverdue(int $documentId): self {
    return new self(
      documentId: $documentId,
      isOverdue: FALSE,
      overdueDays: 0,
      severity: 'none',
      legalMaxDays: 60,
      dueDate: NULL,
      invoiceNumber: NULL,
    );
  }

  /**
   * Creates a result for an overdue document.
   *
   * @param int $documentId
   *   Document ID.
   * @param int $overdueDays
   *   Number of days past due.
   * @param string $dueDate
   *   Original due date.
   * @param string|null $invoiceNumber
   *   Invoice number.
   * @param int $legalMaxDays
   *   Legal maximum days (30 for AAPP, 60 for B2B).
   */
  public static function overdue(int $documentId, int $overdueDays, string $dueDate, ?string $invoiceNumber = NULL, int $legalMaxDays = 60): self {
    $severity = 'warning';
    if ($overdueDays > 60) {
      $severity = 'critical';
    }
    elseif ($overdueDays > 30) {
      $severity = 'urgent';
    }

    return new self(
      documentId: $documentId,
      isOverdue: TRUE,
      overdueDays: $overdueDays,
      severity: $severity,
      legalMaxDays: $legalMaxDays,
      dueDate: $dueDate,
      invoiceNumber: $invoiceNumber,
    );
  }

  /**
   * Exports to associative array.
   */
  public function toArray(): array {
    return [
      'document_id' => $this->documentId,
      'is_overdue' => $this->isOverdue,
      'overdue_days' => $this->overdueDays,
      'severity' => $this->severity,
      'legal_max_days' => $this->legalMaxDays,
      'due_date' => $this->dueDate,
      'invoice_number' => $this->invoiceNumber,
    ];
  }

}

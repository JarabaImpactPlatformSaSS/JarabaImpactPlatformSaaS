<?php

declare(strict_types=1);

namespace Drupal\jaraba_einvoice_b2b\ValueObject;

/**
 * Morosity metrics report for a tenant.
 *
 * Aggregated statistics about overdue invoices per Ley 3/2004.
 * Used by EInvoicePaymentStatusService::calculateMorosityMetrics().
 *
 * Spec: Doc 181, Section 3.4.
 */
final class MorosityReport {

  public function __construct(
    public readonly int $tenantId,
    public readonly int $totalInvoices,
    public readonly int $overdueInvoices,
    public readonly string $overdueAmount,
    public readonly float $overduePercentage,
    public readonly float $averageOverdueDays,
    public readonly int $criticalCount,
    public readonly int $urgentCount,
    public readonly int $warningCount,
    public readonly string $generatedAt,
    public readonly array $overdueDocuments,
  ) {}

  /**
   * Creates a report from aggregated data.
   */
  public static function fromData(int $tenantId, array $overdueResults, int $totalInvoices): self {
    $overdueAmount = '0.00';
    $totalDays = 0;
    $critical = 0;
    $urgent = 0;
    $warning = 0;
    $documents = [];

    foreach ($overdueResults as $result) {
      if ($result instanceof OverdueResult && $result->isOverdue) {
        $totalDays += $result->overdueDays;
        $documents[] = $result->toArray();

        match ($result->severity) {
          'critical' => $critical++,
          'urgent' => $urgent++,
          default => $warning++,
        };
      }
    }

    $overdueCount = count($documents);
    $avgDays = $overdueCount > 0 ? round($totalDays / $overdueCount, 1) : 0.0;
    $pct = $totalInvoices > 0 ? round(($overdueCount / $totalInvoices) * 100, 2) : 0.0;

    return new self(
      tenantId: $tenantId,
      totalInvoices: $totalInvoices,
      overdueInvoices: $overdueCount,
      overdueAmount: $overdueAmount,
      overduePercentage: $pct,
      averageOverdueDays: $avgDays,
      criticalCount: $critical,
      urgentCount: $urgent,
      warningCount: $warning,
      generatedAt: date('c'),
      overdueDocuments: $documents,
    );
  }

  /**
   * Exports to associative array.
   */
  public function toArray(): array {
    return [
      'tenant_id' => $this->tenantId,
      'total_invoices' => $this->totalInvoices,
      'overdue_invoices' => $this->overdueInvoices,
      'overdue_amount' => $this->overdueAmount,
      'overdue_percentage' => $this->overduePercentage,
      'average_overdue_days' => $this->averageOverdueDays,
      'critical_count' => $this->criticalCount,
      'urgent_count' => $this->urgentCount,
      'warning_count' => $this->warningCount,
      'generated_at' => $this->generatedAt,
      'overdue_documents' => $this->overdueDocuments,
    ];
  }

}

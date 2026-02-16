<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_einvoice_b2b\Unit;

use Drupal\jaraba_einvoice_b2b\ValueObject\DeliveryResult;
use Drupal\jaraba_einvoice_b2b\ValueObject\MorosityReport;
use Drupal\jaraba_einvoice_b2b\ValueObject\OverdueResult;
use Drupal\jaraba_einvoice_b2b\ValueObject\SPFEStatus;
use Drupal\jaraba_einvoice_b2b\ValueObject\SPFESubmission;
use Drupal\jaraba_einvoice_b2b\ValueObject\ValidationResult;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for all Value Objects.
 *
 * @group jaraba_einvoice_b2b
 */
class ValueObjectTest extends UnitTestCase {

  // ================================================================
  // ValidationResult
  // ================================================================

  /**
   * Tests ValidationResult::valid factory.
   */
  public function testValidationResultValid(): void {
    $result = ValidationResult::valid('xsd');
    $this->assertTrue($result->valid);
    $this->assertEmpty($result->errors);
    $this->assertEmpty($result->warnings);
    $this->assertSame('xsd', $result->layer);
  }

  /**
   * Tests ValidationResult::invalid factory.
   */
  public function testValidationResultInvalid(): void {
    $errors = ['BR-01: Missing ID.', 'BR-02: Missing date.'];
    $warnings = ['BR-CO-10: Amounts mismatch.'];
    $result = ValidationResult::invalid($errors, 'schematron', $warnings);

    $this->assertFalse($result->valid);
    $this->assertCount(2, $result->errors);
    $this->assertCount(1, $result->warnings);
    $this->assertSame('schematron', $result->layer);
  }

  /**
   * Tests ValidationResult::merge.
   */
  public function testValidationResultMerge(): void {
    $a = ValidationResult::valid('xsd');
    $b = ValidationResult::invalid(['Error 1'], 'schematron');
    $c = new ValidationResult(valid: TRUE, errors: [], warnings: ['Warning 1'], layer: 'cius');

    $merged = $a->merge($b)->merge($c);

    $this->assertFalse($merged->valid, 'Merge with any invalid should be invalid.');
    $this->assertCount(1, $merged->errors);
    $this->assertCount(1, $merged->warnings);
    $this->assertSame('complete', $merged->layer);
  }

  /**
   * Tests ValidationResult::toArray.
   */
  public function testValidationResultToArray(): void {
    $result = ValidationResult::invalid(['E1', 'E2'], 'business', ['W1']);
    $arr = $result->toArray();

    $this->assertFalse($arr['valid']);
    $this->assertSame(2, $arr['error_count']);
    $this->assertSame(1, $arr['warning_count']);
    $this->assertSame('business', $arr['layer']);
  }

  // ================================================================
  // DeliveryResult
  // ================================================================

  /**
   * Tests DeliveryResult::success factory.
   */
  public function testDeliveryResultSuccess(): void {
    $result = DeliveryResult::success('spfe', 'SPFE-001', ['key' => 'value']);

    $this->assertTrue($result->success);
    $this->assertSame('spfe', $result->channel);
    $this->assertSame('delivered', $result->status);
    $this->assertSame('SPFE-001', $result->messageId);
    $this->assertNull($result->errorMessage);
    $this->assertSame(200, $result->httpStatus);
    $this->assertSame(['key' => 'value'], $result->metadata);
  }

  /**
   * Tests DeliveryResult::failure factory.
   */
  public function testDeliveryResultFailure(): void {
    $result = DeliveryResult::failure('email', 'No recipient.', 422);

    $this->assertFalse($result->success);
    $this->assertSame('email', $result->channel);
    $this->assertSame('failed', $result->status);
    $this->assertNull($result->messageId);
    $this->assertSame('No recipient.', $result->errorMessage);
    $this->assertSame(422, $result->httpStatus);
  }

  /**
   * Tests DeliveryResult::toArray.
   */
  public function testDeliveryResultToArray(): void {
    $arr = DeliveryResult::success('platform', 'P-1')->toArray();
    $this->assertTrue($arr['success']);
    $this->assertSame('platform', $arr['channel']);
    $this->assertSame('P-1', $arr['message_id']);
    $this->assertArrayHasKey('duration_ms', $arr);
  }

  // ================================================================
  // OverdueResult
  // ================================================================

  /**
   * Tests OverdueResult::notOverdue factory.
   */
  public function testOverdueResultNotOverdue(): void {
    $result = OverdueResult::notOverdue(42);

    $this->assertSame(42, $result->documentId);
    $this->assertFalse($result->isOverdue);
    $this->assertSame(0, $result->overdueDays);
    $this->assertSame('none', $result->severity);
  }

  /**
   * Tests OverdueResult severity levels.
   *
   * @dataProvider overdueSeverityProvider
   */
  public function testOverdueSeverityLevels(int $days, string $expectedSeverity): void {
    $result = OverdueResult::overdue(1, $days, '2026-01-01', 'INV-001');
    $this->assertTrue($result->isOverdue);
    $this->assertSame($expectedSeverity, $result->severity);
    $this->assertSame($days, $result->overdueDays);
  }

  /**
   * Data provider for overdue severity levels per Ley 3/2004.
   */
  public static function overdueSeverityProvider(): array {
    return [
      'warning: 15 days' => [15, 'warning'],
      'warning: 30 days' => [30, 'warning'],
      'urgent: 31 days' => [31, 'urgent'],
      'urgent: 60 days' => [60, 'urgent'],
      'critical: 61 days' => [61, 'critical'],
      'critical: 120 days' => [120, 'critical'],
    ];
  }

  /**
   * Tests OverdueResult::toArray.
   */
  public function testOverdueResultToArray(): void {
    $result = OverdueResult::overdue(5, 45, '2026-01-01', 'INV-005', 60);
    $arr = $result->toArray();

    $this->assertSame(5, $arr['document_id']);
    $this->assertTrue($arr['is_overdue']);
    $this->assertSame(45, $arr['overdue_days']);
    $this->assertSame('urgent', $arr['severity']);
    $this->assertSame(60, $arr['legal_max_days']);
  }

  // ================================================================
  // SPFESubmission
  // ================================================================

  /**
   * Tests SPFESubmission::accepted factory.
   */
  public function testSpfeSubmissionAccepted(): void {
    $result = SPFESubmission::accepted('SPFE-123', '2026-01-15T10:00:00+01:00');

    $this->assertTrue($result->success);
    $this->assertSame('SPFE-123', $result->submissionId);
    $this->assertSame('accepted', $result->status);
    $this->assertNull($result->errorCode);
    $this->assertNull($result->errorMessage);
    $this->assertSame('2026-01-15T10:00:00+01:00', $result->timestamp);
  }

  /**
   * Tests SPFESubmission::rejected factory.
   */
  public function testSpfeSubmissionRejected(): void {
    $result = SPFESubmission::rejected('ERR-001', 'Invalid format.');

    $this->assertFalse($result->success);
    $this->assertNull($result->submissionId);
    $this->assertSame('rejected', $result->status);
    $this->assertSame('ERR-001', $result->errorCode);
    $this->assertSame('Invalid format.', $result->errorMessage);
  }

  /**
   * Tests SPFESubmission::toArray.
   */
  public function testSpfeSubmissionToArray(): void {
    $arr = SPFESubmission::accepted('SPFE-X')->toArray();
    $this->assertTrue($arr['success']);
    $this->assertSame('SPFE-X', $arr['submission_id']);
    $this->assertArrayHasKey('timestamp', $arr);
  }

  // ================================================================
  // SPFEStatus
  // ================================================================

  /**
   * Tests SPFEStatus::fromResponse factory.
   */
  public function testSpfeStatusFromResponse(): void {
    $status = SPFEStatus::fromResponse('SUB-001', 'accepted');

    $this->assertSame('SUB-001', $status->submissionId);
    $this->assertSame('accepted', $status->status);
    $this->assertTrue($status->isAccepted());
    $this->assertFalse($status->isPending());
  }

  /**
   * Tests SPFEStatus::isPending.
   */
  public function testSpfeStatusIsPending(): void {
    $pending = SPFEStatus::fromResponse('SUB-002', 'pending');
    $this->assertTrue($pending->isPending());
    $this->assertFalse($pending->isAccepted());

    $processing = SPFEStatus::fromResponse('SUB-003', 'processing');
    $this->assertTrue($processing->isPending());
  }

  /**
   * Tests SPFEStatus::toArray.
   */
  public function testSpfeStatusToArray(): void {
    $arr = SPFEStatus::fromResponse('SUB-X', 'accepted', '2026-01-01T00:00:00+00:00')->toArray();
    $this->assertSame('SUB-X', $arr['submission_id']);
    $this->assertSame('accepted', $arr['status']);
    $this->assertSame('2026-01-01T00:00:00+00:00', $arr['last_updated']);
  }

  // ================================================================
  // MorosityReport
  // ================================================================

  /**
   * Tests MorosityReport::fromData with no overdue.
   */
  public function testMorosityReportNoOverdue(): void {
    $report = MorosityReport::fromData(1, [], 10);

    $this->assertSame(1, $report->tenantId);
    $this->assertSame(10, $report->totalInvoices);
    $this->assertSame(0, $report->overdueInvoices);
    $this->assertSame(0.0, $report->overduePercentage);
    $this->assertSame(0.0, $report->averageOverdueDays);
    $this->assertSame(0, $report->criticalCount);
    $this->assertSame(0, $report->urgentCount);
    $this->assertSame(0, $report->warningCount);
  }

  /**
   * Tests MorosityReport::fromData with mixed severities.
   */
  public function testMorosityReportMixedSeverities(): void {
    $overdue = [
      OverdueResult::overdue(1, 15, '2026-01-01', 'INV-001'),  // warning
      OverdueResult::overdue(2, 45, '2025-12-01', 'INV-002'),  // urgent
      OverdueResult::overdue(3, 90, '2025-10-01', 'INV-003'),  // critical
    ];

    $report = MorosityReport::fromData(1, $overdue, 20);

    $this->assertSame(3, $report->overdueInvoices);
    $this->assertSame(15.0, $report->overduePercentage);
    $this->assertSame(50.0, $report->averageOverdueDays);
    $this->assertSame(1, $report->criticalCount);
    $this->assertSame(1, $report->urgentCount);
    $this->assertSame(1, $report->warningCount);
    $this->assertCount(3, $report->overdueDocuments);
  }

  /**
   * Tests MorosityReport::toArray.
   */
  public function testMorosityReportToArray(): void {
    $report = MorosityReport::fromData(5, [], 0);
    $arr = $report->toArray();

    $this->assertSame(5, $arr['tenant_id']);
    $this->assertArrayHasKey('generated_at', $arr);
    $this->assertArrayHasKey('overdue_documents', $arr);
  }

}

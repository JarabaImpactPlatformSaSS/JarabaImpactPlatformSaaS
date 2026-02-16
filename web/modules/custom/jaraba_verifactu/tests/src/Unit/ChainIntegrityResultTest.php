<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_verifactu\Unit;

use Drupal\jaraba_verifactu\ValueObject\ChainIntegrityResult;
use Drupal\Tests\UnitTestCase;

/**
 * Tests para ChainIntegrityResult ValueObject.
 *
 * @group jaraba_verifactu
 * @coversDefaultClass \Drupal\jaraba_verifactu\ValueObject\ChainIntegrityResult
 */
class ChainIntegrityResultTest extends UnitTestCase {

  /**
   * Tests valid factory creates a valid result.
   */
  public function testValidFactory(): void {
    $result = ChainIntegrityResult::valid(100, 250.5);

    $this->assertTrue($result->isValid);
    $this->assertSame(100, $result->totalRecords);
    $this->assertSame(100, $result->validRecords);
    $this->assertNull($result->breakAtRecordId);
    $this->assertSame('', $result->expectedHash);
    $this->assertSame('', $result->actualHash);
    $this->assertSame(250.5, $result->verificationTimeMs);
    $this->assertSame('', $result->errorMessage);
  }

  /**
   * Tests broken factory creates a broken result.
   */
  public function testBrokenFactory(): void {
    $result = ChainIntegrityResult::broken(
      totalRecords: 50,
      validRecords: 42,
      breakAtRecordId: 43,
      expectedHash: 'abc123',
      actualHash: 'xyz789',
      verificationTimeMs: 120.3,
    );

    $this->assertFalse($result->isValid);
    $this->assertSame(50, $result->totalRecords);
    $this->assertSame(42, $result->validRecords);
    $this->assertSame(43, $result->breakAtRecordId);
    $this->assertSame('abc123', $result->expectedHash);
    $this->assertSame('xyz789', $result->actualHash);
  }

  /**
   * Tests error factory creates an error result.
   */
  public function testErrorFactory(): void {
    $result = ChainIntegrityResult::error('Lock timeout');

    $this->assertFalse($result->isValid);
    $this->assertSame(0, $result->totalRecords);
    $this->assertSame(0, $result->validRecords);
    $this->assertSame('Lock timeout', $result->errorMessage);
  }

  /**
   * Tests toArray contains all fields.
   */
  public function testToArrayContainsAllFields(): void {
    $result = ChainIntegrityResult::valid(10, 50.123);
    $array = $result->toArray();

    $this->assertArrayHasKey('is_valid', $array);
    $this->assertArrayHasKey('total_records', $array);
    $this->assertArrayHasKey('valid_records', $array);
    $this->assertArrayHasKey('break_at_record_id', $array);
    $this->assertArrayHasKey('expected_hash', $array);
    $this->assertArrayHasKey('actual_hash', $array);
    $this->assertArrayHasKey('verification_time_ms', $array);
    $this->assertArrayHasKey('error_message', $array);
    $this->assertSame(50.12, $array['verification_time_ms']);
  }

  /**
   * Tests toArray for broken result.
   */
  public function testToArrayBrokenResult(): void {
    $result = ChainIntegrityResult::broken(100, 55, 56, 'exp', 'act', 200.0);
    $array = $result->toArray();

    $this->assertFalse($array['is_valid']);
    $this->assertSame(56, $array['break_at_record_id']);
    $this->assertSame('exp', $array['expected_hash']);
    $this->assertSame('act', $array['actual_hash']);
  }

}

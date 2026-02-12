<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_onboarding\Unit\Service;

use Drupal\jaraba_onboarding\Service\NIFValidationService;
use Drupal\Tests\UnitTestCase;

/**
 * Tests para NIFValidationService.
 *
 * Validates Spanish NIF, NIE, and CIF identification numbers using
 * the official Agencia Tributaria algorithms.
 *
 * @covers \Drupal\jaraba_onboarding\Service\NIFValidationService
 * @group jaraba_onboarding
 */
class NIFValidationServiceTest extends UnitTestCase {

  protected NIFValidationService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->service = new NIFValidationService();
  }

  // ===========================================================================
  // NIF Tests
  // ===========================================================================

  /**
   * Tests valid NIF numbers.
   *
   * @dataProvider validNifProvider
   */
  public function testValidNif(string $nif): void {
    $this->assertTrue($this->service->validateNif($nif));
  }

  /**
   * Data provider for valid NIF numbers.
   *
   * Uses the official algorithm: letter = 'TRWAGMYFPDXBNJZSQVHLCKE'[number % 23].
   */
  public static function validNifProvider(): array {
    return [
      'NIF 00000000T' => ['00000000T'], // 0 % 23 = 0 -> T
      'NIF 00000001R' => ['00000001R'], // 1 % 23 = 1 -> R
      'NIF 12345678Z' => ['12345678Z'], // 12345678 % 23 = 14 -> Z
      'NIF 99999999R' => ['99999999R'], // 99999999 % 23 = 1 -> R
      'NIF 50000000R' => ['50000000R'], // 50000000 % 23 = 1 -> R
    ];
  }

  /**
   * Tests invalid NIF numbers.
   *
   * @dataProvider invalidNifProvider
   */
  public function testInvalidNif(string $nif): void {
    $this->assertFalse($this->service->validateNif($nif));
  }

  /**
   * Data provider for invalid NIF numbers.
   */
  public static function invalidNifProvider(): array {
    return [
      'Wrong control letter' => ['12345678A'],
      'Too few digits' => ['1234567Z'],
      'Too many digits' => ['123456789Z'],
      'No letter' => ['123456789'],
      'Letter in middle' => ['1234A678Z'],
      'Empty string' => [''],
      'Only letter' => ['T'],
    ];
  }

  // ===========================================================================
  // NIE Tests
  // ===========================================================================

  /**
   * Tests valid NIE numbers.
   *
   * @dataProvider validNieProvider
   */
  public function testValidNie(string $nie): void {
    $this->assertTrue($this->service->validateNie($nie));
  }

  /**
   * Data provider for valid NIE numbers.
   *
   * NIE algorithm: replace X->0, Y->1, Z->2, then apply NIF algorithm.
   */
  public static function validNieProvider(): array {
    return [
      // X0000000T: 00000000 % 23 = 0 -> T
      'NIE X0000000T' => ['X0000000T'],
      // Y0000000Z: 10000000 % 23 = 18 -> Z  (10000000 mod 23 = 10000000 - 434782*23 = 10000000 - 9999986 = 14 -> Z)
      'NIE Y0000000Z' => ['Y0000000Z'],
      // Z0000000M: 20000000 % 23 = 20000000 - 869565*23 = 20000000 - 19999995 = 5 -> M
      'NIE Z0000000M' => ['Z0000000M'],
      // X1234567L: 01234567 % 23 = 1234567 - 53676*23 = 1234567 - 1234548 = 19 -> L  (Hmm let me compute: 1234567/23 = 53676.8... -> 53676*23 = 1234548. 1234567-1234548=19 -> L)
      'NIE X1234567L' => ['X1234567L'],
    ];
  }

  /**
   * Tests invalid NIE numbers.
   *
   * @dataProvider invalidNieProvider
   */
  public function testInvalidNie(string $nie): void {
    $this->assertFalse($this->service->validateNie($nie));
  }

  /**
   * Data provider for invalid NIE numbers.
   */
  public static function invalidNieProvider(): array {
    return [
      'Wrong control letter' => ['X0000000A'],
      'Invalid prefix' => ['A1234567Z'],
      'Too few digits' => ['X123456Z'],
      'Too many digits' => ['X12345678Z'],
      'No control letter' => ['X1234567'],
    ];
  }

  // ===========================================================================
  // CIF Tests
  // ===========================================================================

  /**
   * Tests valid CIF numbers.
   *
   * @dataProvider validCifProvider
   */
  public function testValidCif(string $cif): void {
    $this->assertTrue(
      $this->service->validateCif($cif),
      sprintf('CIF "%s" should be valid.', $cif)
    );
  }

  /**
   * Data provider for valid CIF numbers.
   *
   * CIF algorithm:
   * - Odd positions (1,3,5,7): double digit, sum digits of result.
   * - Even positions (2,4,6): sum directly.
   * - Control = (10 - total_sum % 10) % 10.
   * - A,B,E,H: control must be digit.
   * - P,Q,R,S,W: control must be letter (JABCDEFGHI).
   */
  public static function validCifProvider(): array {
    // For B0000000 (all zeros):
    // Odd: 0*2=0 (pos1) + 0*2=0 (pos3) + 0*2=0 (pos5) + 0*2=0 (pos7) = 0
    // Even: 0+0+0 = 0
    // Control = (10-0)%10 = 0 -> digit '0' for B.
    //
    // For A5890000:
    // Digits: 5 8 9 0 0 0 0
    // Pos1 (odd): 5*2=10 -> 1+0=1
    // Pos2 (even): 8
    // Pos3 (odd): 9*2=18 -> 1+8=9
    // Pos4 (even): 0
    // Pos5 (odd): 0*2=0 -> 0
    // Pos6 (even): 0
    // Pos7 (odd): 0*2=0 -> 0
    // sumOdd=1+9+0+0=10, sumEven=8+0+0=8, total=18
    // Control = (10-18%10)%10 = (10-8)%10 = 2 -> digit '2'
    // A type requires digit control -> A58900002

    return [
      'CIF B00000000 (all zeros, digit ctrl)' => ['B00000000'],
      'CIF A58900002 (digit ctrl)' => ['A58900002'],
      // P type requires letter control. P0000000 -> control=0 -> letter J.
      'CIF P0000000J (letter ctrl)' => ['P0000000J'],
      // Q type requires letter control. Q0000000 -> control=0 -> letter J.
      'CIF Q0000000J (letter ctrl)' => ['Q0000000J'],
      // G type accepts either. G0000000 -> control=0 -> digit '0' or letter 'J'.
      'CIF G00000000 (either, digit)' => ['G00000000'],
      'CIF G0000000J (either, letter)' => ['G0000000J'],
    ];
  }

  /**
   * Tests invalid CIF numbers.
   *
   * @dataProvider invalidCifProvider
   */
  public function testInvalidCif(string $cif): void {
    $this->assertFalse(
      $this->service->validateCif($cif),
      sprintf('CIF "%s" should be invalid.', $cif)
    );
  }

  /**
   * Data provider for invalid CIF numbers.
   */
  public static function invalidCifProvider(): array {
    return [
      'Wrong control digit' => ['B00000001'],
      'Wrong control letter for P type' => ['P0000000A'],
      'Digit when letter required (S type)' => ['S00000000'],
      'Invalid prefix (I not valid)' => ['I00000000'],
      'Too short' => ['B000000'],
      'Empty' => [''],
    ];
  }

  // ===========================================================================
  // validate() Dispatcher Tests
  // ===========================================================================

  /**
   * Tests the validate() dispatcher correctly identifies NIF.
   */
  public function testValidateDispatcherNif(): void {
    $result = $this->service->validate('12345678Z');
    $this->assertTrue($result['valid']);
    $this->assertSame('NIF', $result['type']);
    $this->assertNull($result['error']);
  }

  /**
   * Tests the validate() dispatcher correctly identifies NIE.
   */
  public function testValidateDispatcherNie(): void {
    $result = $this->service->validate('X0000000T');
    $this->assertTrue($result['valid']);
    $this->assertSame('NIE', $result['type']);
    $this->assertNull($result['error']);
  }

  /**
   * Tests the validate() dispatcher correctly identifies CIF.
   */
  public function testValidateDispatcherCif(): void {
    $result = $this->service->validate('B00000000');
    $this->assertTrue($result['valid']);
    $this->assertSame('CIF', $result['type']);
    $this->assertNull($result['error']);
  }

  /**
   * Tests validate() returns error for empty string.
   */
  public function testValidateEmptyDocument(): void {
    $result = $this->service->validate('');
    $this->assertFalse($result['valid']);
    $this->assertNull($result['type']);
    $this->assertNotNull($result['error']);
  }

  /**
   * Tests validate() returns error for unrecognized format.
   */
  public function testValidateUnrecognizedFormat(): void {
    $result = $this->service->validate('!!!invalid!!!');
    $this->assertFalse($result['valid']);
    $this->assertNull($result['type']);
    $this->assertStringContainsString('no reconocido', $result['error']);
  }

  /**
   * Tests validate() normalizes input (spaces, dashes, lowercase).
   */
  public function testValidateNormalizesInput(): void {
    // 12345678Z with spaces and lowercase.
    $result = $this->service->validate(' 1234 5678-z ');
    $this->assertTrue($result['valid']);
    $this->assertSame('NIF', $result['type']);
  }

  /**
   * Tests validate() returns correct error for invalid NIF.
   */
  public function testValidateInvalidNifReturnsError(): void {
    $result = $this->service->validate('12345678A');
    $this->assertFalse($result['valid']);
    $this->assertSame('NIF', $result['type']);
    $this->assertNotNull($result['error']);
  }

  /**
   * Tests validate() returns correct error for invalid NIE.
   */
  public function testValidateInvalidNieReturnsError(): void {
    $result = $this->service->validate('X0000000A');
    $this->assertFalse($result['valid']);
    $this->assertSame('NIE', $result['type']);
    $this->assertNotNull($result['error']);
  }

  /**
   * Tests validate() returns correct error for invalid CIF.
   */
  public function testValidateInvalidCifReturnsError(): void {
    $result = $this->service->validate('B00000009');
    $this->assertFalse($result['valid']);
    $this->assertSame('CIF', $result['type']);
    $this->assertNotNull($result['error']);
  }

}

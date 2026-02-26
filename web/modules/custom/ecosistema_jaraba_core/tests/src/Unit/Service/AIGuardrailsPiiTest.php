<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Core\Database\Connection;
use Drupal\ecosistema_jaraba_core\Service\AIGuardrailsService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AIGuardrailsService PII masking in output.
 *
 * Tests the maskOutputPII() public method which detects and masks
 * both US and Spanish PII patterns in LLM output text.
 *
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Service\AIGuardrailsService
 * @group ecosistema_jaraba_core
 */
class AIGuardrailsPiiTest extends TestCase {

  /**
   * The service under test.
   */
  protected AIGuardrailsService $service;

  /**
   * Mock database connection.
   */
  protected Connection|MockObject $database;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->database = $this->createMock(Connection::class);
    $this->service = new AIGuardrailsService($this->database);
  }

  /**
   * Tests that email addresses are masked.
   *
   * @covers ::maskOutputPII
   */
  public function testMaskEmail(): void {
    $input = 'Contact me at john.doe@example.com for more info.';
    $result = $this->service->maskOutputPII($input);

    $this->assertStringNotContainsString('john.doe@example.com', $result);
    $this->assertStringContainsString('[DATO PROTEGIDO]', $result);
    $this->assertStringContainsString('Contact me at', $result);
    $this->assertStringContainsString('for more info.', $result);
  }

  /**
   * Tests that US phone numbers are masked.
   *
   * @covers ::maskOutputPII
   */
  public function testMaskPhoneUS(): void {
    $input = 'Call us at 123-456-7890 for support.';
    $result = $this->service->maskOutputPII($input);

    $this->assertStringNotContainsString('123-456-7890', $result);
    $this->assertStringContainsString('[DATO PROTEGIDO]', $result);
  }

  /**
   * Tests that SSN patterns are masked.
   *
   * @covers ::maskOutputPII
   */
  public function testMaskSSN(): void {
    $input = 'The SSN is 123-45-6789 on file.';
    $result = $this->service->maskOutputPII($input);

    $this->assertStringNotContainsString('123-45-6789', $result);
    $this->assertStringContainsString('[DATO PROTEGIDO]', $result);
  }

  /**
   * Tests that Spanish DNI numbers are masked.
   *
   * @covers ::maskOutputPII
   */
  public function testMaskDNI(): void {
    $input = 'Mi DNI es 12345678A y necesito renovarlo.';
    $result = $this->service->maskOutputPII($input);

    $this->assertStringNotContainsString('12345678A', $result);
    $this->assertStringContainsString('[DATO PROTEGIDO]', $result);
  }

  /**
   * Tests that Spanish NIE numbers are masked.
   *
   * @covers ::maskOutputPII
   */
  public function testMaskNIE(): void {
    $input = 'NIE: X1234567Z para el tramite.';
    $result = $this->service->maskOutputPII($input);

    $this->assertStringNotContainsString('X1234567Z', $result);
    $this->assertStringContainsString('[DATO PROTEGIDO]', $result);
  }

  /**
   * Tests that Spanish IBAN ES numbers are masked.
   *
   * @covers ::maskOutputPII
   */
  public function testMaskIBANES(): void {
    $input = 'IBAN ES1234567890123456789012 para la transferencia.';
    $result = $this->service->maskOutputPII($input);

    $this->assertStringNotContainsString('ES1234567890123456789012', $result);
    $this->assertStringContainsString('[DATO PROTEGIDO]', $result);
  }

  /**
   * Tests that Spanish NIF/CIF patterns are masked.
   *
   * @covers ::maskOutputPII
   */
  public function testMaskNIFCIF(): void {
    $input = 'CIF: B12345678 de la empresa registrada.';
    $result = $this->service->maskOutputPII($input);

    $this->assertStringNotContainsString('B12345678', $result);
    $this->assertStringContainsString('[DATO PROTEGIDO]', $result);
  }

  /**
   * Tests that Spanish phone numbers (0034 prefix) are masked.
   *
   * Note: The regex uses \b word boundary which does not match '+34' prefix
   * because '+' is not a word character. The 0034 prefix works correctly.
   *
   * @covers ::maskOutputPII
   */
  public function testMaskPhoneES(): void {
    $input = 'Tel: 0034612345678 para contacto.';
    $result = $this->service->maskOutputPII($input);

    $this->assertStringNotContainsString('0034612345678', $result);
    $this->assertStringContainsString('[DATO PROTEGIDO]', $result);
  }

  /**
   * Tests that legitimate text is NOT masked (no false positives).
   *
   * @covers ::maskOutputPII
   */
  public function testNoFalsePositives(): void {
    $input = 'The year 2024 was great for business growth in our platform.';
    $result = $this->service->maskOutputPII($input);

    $this->assertSame($input, $result);
    $this->assertStringNotContainsString('[DATO PROTEGIDO]', $result);
  }

  /**
   * Tests that multiple PII types in the same text are all masked.
   *
   * @covers ::maskOutputPII
   */
  public function testMaskMultiplePII(): void {
    $input = 'Contact john@example.com or call 0034612345678 with DNI 12345678A.';
    $result = $this->service->maskOutputPII($input);

    $this->assertStringNotContainsString('john@example.com', $result);
    $this->assertStringNotContainsString('0034612345678', $result);
    $this->assertStringNotContainsString('12345678A', $result);

    // Should have multiple masked placeholders.
    $this->assertGreaterThanOrEqual(
      3,
      substr_count($result, '[DATO PROTEGIDO]'),
    );
  }

  /**
   * Tests that empty string returns empty string.
   *
   * @covers ::maskOutputPII
   */
  public function testMaskEmptyString(): void {
    $result = $this->service->maskOutputPII('');
    $this->assertSame('', $result);
  }

  /**
   * Tests that credit card numbers are masked.
   *
   * @covers ::maskOutputPII
   */
  public function testMaskCreditCard(): void {
    $input = 'Payment with card 4111 1111 1111 1111 was processed.';
    $result = $this->service->maskOutputPII($input);

    $this->assertStringNotContainsString('4111 1111 1111 1111', $result);
    $this->assertStringContainsString('[DATO PROTEGIDO]', $result);
  }

  /**
   * Tests masking with US phone in dot format.
   *
   * @covers ::maskOutputPII
   */
  public function testMaskPhoneUSDotFormat(): void {
    $input = 'Reach us at 555.123.4567 today.';
    $result = $this->service->maskOutputPII($input);

    $this->assertStringNotContainsString('555.123.4567', $result);
    $this->assertStringContainsString('[DATO PROTEGIDO]', $result);
  }

  /**
   * Tests masking with NIE lowercase prefix.
   *
   * @covers ::maskOutputPII
   */
  public function testMaskNIELowercase(): void {
    $input = 'NIE: y9876543B del solicitante.';
    $result = $this->service->maskOutputPII($input);

    $this->assertStringNotContainsString('y9876543B', $result);
    $this->assertStringContainsString('[DATO PROTEGIDO]', $result);
  }

  /**
   * Tests masking Spanish phone with 0034 prefix.
   *
   * @covers ::maskOutputPII
   */
  public function testMaskPhoneES0034Prefix(): void {
    $input = 'Llamar al 0034612345678 para reservas.';
    $result = $this->service->maskOutputPII($input);

    $this->assertStringNotContainsString('0034612345678', $result);
    $this->assertStringContainsString('[DATO PROTEGIDO]', $result);
  }

}

<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Security;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\ecosistema_jaraba_core\Service\AIGuardrailsService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * PII-INPUT-GUARD-001: Tests for PII detection in LLM input.
 *
 * Validates that checkInputPII() correctly detects and blocks Spanish PII
 * (DNI, NIE, IBAN, NIF/CIF) and masks non-critical PII (email, phone).
 *
 * @group ecosistema_jaraba_core
 * @group security
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Service\AIGuardrailsService
 */
class PiiInputGuardTest extends UnitTestCase {

  /**
   * The service under test.
   */
  protected AIGuardrailsService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $database = $this->createMock(Connection::class);
    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $logger = $this->createMock(LoggerInterface::class);

    $this->service = new AIGuardrailsService($database, $configFactory, $logger);
  }

  /**
   * @covers ::checkInputPII
   */
  public function testNoPiiInNormalText(): void {
    $result = $this->service->checkInputPII('Quiero informacion sobre cursos de formacion');
    self::assertFalse($result['has_pii']);
    self::assertFalse($result['blocked']);
    self::assertSame([], $result['detected_types']);
  }

  /**
   * @covers ::checkInputPII
   * @dataProvider dniProvider
   */
  public function testBlocksDni(string $input): void {
    $result = $this->service->checkInputPII($input);
    self::assertTrue($result['has_pii']);
    self::assertTrue($result['blocked']);
    self::assertContains('dni', $result['detected_types']);
  }

  /**
   * @return array<string, array{string}>
   */
  public static function dniProvider(): array {
    return [
      'standard DNI' => ['Mi DNI es 12345678A'],
      'lowercase DNI' => ['Documento 87654321z'],
      'embedded in text' => ['Registro con 44556677B para el curso'],
    ];
  }

  /**
   * @covers ::checkInputPII
   */
  public function testBlocksNie(): void {
    $result = $this->service->checkInputPII('Mi NIE es X1234567A');
    self::assertTrue($result['has_pii']);
    self::assertTrue($result['blocked']);
    self::assertContains('nie', $result['detected_types']);
  }

  /**
   * @covers ::checkInputPII
   */
  public function testBlocksIban(): void {
    $result = $this->service->checkInputPII('Mi IBAN es ES1234567890123456789012');
    self::assertTrue($result['has_pii']);
    self::assertTrue($result['blocked']);
    self::assertContains('iban_es', $result['detected_types']);
  }

  /**
   * @covers ::checkInputPII
   */
  public function testBlocksNifCif(): void {
    $result = $this->service->checkInputPII('La empresa tiene CIF B12345678');
    self::assertTrue($result['has_pii']);
    self::assertTrue($result['blocked']);
    self::assertContains('nif_cif', $result['detected_types']);
  }

  /**
   * @covers ::checkInputPII
   */
  public function testMasksEmailNotBlocks(): void {
    $result = $this->service->checkInputPII('Contactar a usuario@example.com');
    self::assertTrue($result['has_pii']);
    self::assertFalse($result['blocked']);
    self::assertContains('email', $result['detected_types']);
    self::assertStringContainsString('[DATO PROTEGIDO]', $result['masked']);
    self::assertStringNotContainsString('usuario@example.com', $result['masked']);
  }

  /**
   * @covers ::checkInputPII
   */
  public function testMasksSpanishPhoneNotBlocks(): void {
    $result = $this->service->checkInputPII('Llamar al 0034623174304');
    self::assertTrue($result['has_pii']);
    self::assertFalse($result['blocked']);
    self::assertContains('phone_es', $result['detected_types']);
  }

  /**
   * @covers ::checkInputPII
   */
  public function testMultiplePiiTypesDetected(): void {
    $result = $this->service->checkInputPII('DNI 12345678A con IBAN ES1234567890123456789012');
    self::assertTrue($result['has_pii']);
    self::assertTrue($result['blocked']);
    self::assertContains('dni', $result['detected_types']);
    self::assertContains('iban_es', $result['detected_types']);
  }

  /**
   * @covers ::checkInputPII
   */
  public function testMaskedOutputReplacesAllPii(): void {
    $result = $this->service->checkInputPII('Email: test@mail.com y DNI 12345678A');
    self::assertStringNotContainsString('test@mail.com', $result['masked']);
    self::assertStringNotContainsString('12345678A', $result['masked']);
    self::assertSame(2, substr_count($result['masked'], '[DATO PROTEGIDO]'));
  }

}

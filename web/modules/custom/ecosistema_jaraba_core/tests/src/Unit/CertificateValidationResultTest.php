<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit;

use Drupal\ecosistema_jaraba_core\ValueObject\CertificateValidationResult;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for CertificateValidationResult Value Object.
 *
 * Valida las factories, propiedades inmutables y serializacion a array.
 *
 * Plan Implementacion Stack Cumplimiento Fiscal v1 — FASE 0.
 *
 * @group ecosistema_jaraba_core
 * @group fiscal
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\ValueObject\CertificateValidationResult
 */
class CertificateValidationResultTest extends UnitTestCase {

  /**
   * Simula un array de openssl_x509_parse() valido.
   */
  protected function getValidCertInfo(): array {
    return [
      'subject' => ['CN' => 'EMPRESA TEST SL', 'serialNumber' => 'IDCES-B12345678'],
      'issuer' => ['CN' => 'FNMT Clase 2 CA'],
      'serialNumber' => 'ABC123',
      'validFrom_time_t' => time() - 86400,
      'validTo_time_t' => time() + (90 * 86400),
    ];
  }

  /**
   * Simula un array de openssl_x509_parse() expirado.
   */
  protected function getExpiredCertInfo(): array {
    return [
      'subject' => ['CN' => 'EMPRESA EXPIRADA SL'],
      'issuer' => ['CN' => 'FNMT Clase 2 CA'],
      'serialNumber' => 'DEF456',
      'validFrom_time_t' => time() - (365 * 86400),
      'validTo_time_t' => time() - 86400,
    ];
  }

  /**
   * @covers ::valid
   * @covers ::isValid
   */
  public function testValidFactoryCreatesValidResult(): void {
    $certInfo = $this->getValidCertInfo();
    $result = CertificateValidationResult::valid($certInfo, 'B12345678');

    $this->assertTrue($result->isValid);
    $this->assertSame('EMPRESA TEST SL', $result->subject);
    $this->assertSame('FNMT Clase 2 CA', $result->issuer);
    $this->assertSame('ABC123', $result->serialNumber);
    $this->assertSame('B12345678', $result->nif);
    $this->assertGreaterThan(0, $result->daysUntilExpiry);
    $this->assertInstanceOf(\DateTimeImmutable::class, $result->expiresAt);
    $this->assertEmpty($result->errorMessage);
    $this->assertEmpty($result->errorCode);
  }

  /**
   * @covers ::expired
   */
  public function testExpiredFactoryCreatesInvalidResult(): void {
    $certInfo = $this->getExpiredCertInfo();
    $result = CertificateValidationResult::expired($certInfo);

    $this->assertFalse($result->isValid);
    $this->assertSame(-1, $result->daysUntilExpiry);
    $this->assertSame('CERT_EXPIRED', $result->errorCode);
    $this->assertStringContainsString('expired', $result->errorMessage);
  }

  /**
   * @covers ::notYetValid
   */
  public function testNotYetValidFactoryCreatesInvalidResult(): void {
    $certInfo = [
      'subject' => ['CN' => 'FUTURE CERT SL'],
      'issuer' => ['CN' => 'CA'],
      'serialNumber' => 'FUTURE',
      'validFrom_time_t' => time() + (30 * 86400),
      'validTo_time_t' => time() + (365 * 86400),
    ];
    $result = CertificateValidationResult::notYetValid($certInfo);

    $this->assertFalse($result->isValid);
    $this->assertSame('CERT_NOT_YET_VALID', $result->errorCode);
    $this->assertStringContainsString('not valid until', $result->errorMessage);
  }

  /**
   * @covers ::error
   */
  public function testErrorFactoryCreatesErrorResult(): void {
    $result = CertificateValidationResult::error('File not found', 'CERT_NOT_FOUND');

    $this->assertFalse($result->isValid);
    $this->assertNull($result->expiresAt);
    $this->assertSame(-1, $result->daysUntilExpiry);
    $this->assertEmpty($result->subject);
    $this->assertSame('File not found', $result->errorMessage);
    $this->assertSame('CERT_NOT_FOUND', $result->errorCode);
  }

  /**
   * @covers ::isExpiringSoon
   */
  public function testIsExpiringSoonWithDefaultThreshold(): void {
    // Certificado que expira en 20 dias (< 30 dias default).
    $certInfo = [
      'subject' => ['CN' => 'EXPIRING SOON SL'],
      'issuer' => ['CN' => 'CA'],
      'serialNumber' => 'SOON',
      'validFrom_time_t' => time() - 86400,
      'validTo_time_t' => time() + (20 * 86400),
    ];
    $result = CertificateValidationResult::valid($certInfo);

    $this->assertTrue($result->isExpiringSoon());
    $this->assertTrue($result->isExpiringSoon(30));
    $this->assertFalse($result->isExpiringSoon(10));
  }

  /**
   * @covers ::isExpiringSoon
   */
  public function testIsExpiringSoonReturnsFalseWhenNotValid(): void {
    $result = CertificateValidationResult::error('Test', 'TEST');
    $this->assertFalse($result->isExpiringSoon());
  }

  /**
   * @covers ::toArray
   */
  public function testToArrayContainsAllFields(): void {
    $certInfo = $this->getValidCertInfo();
    $result = CertificateValidationResult::valid($certInfo, 'B12345678');
    $array = $result->toArray();

    $expectedKeys = [
      'is_valid',
      'expires_at',
      'days_until_expiry',
      'subject',
      'issuer',
      'serial_number',
      'nif',
      'error_message',
      'error_code',
    ];

    foreach ($expectedKeys as $key) {
      $this->assertArrayHasKey($key, $array, "Missing key: $key");
    }

    $this->assertTrue($array['is_valid']);
    $this->assertSame('B12345678', $array['nif']);
    $this->assertSame('EMPRESA TEST SL', $array['subject']);
  }

  /**
   * @covers ::toArray
   */
  public function testToArrayWithNullExpiresAt(): void {
    $result = CertificateValidationResult::error('No file', 'CERT_NOT_FOUND');
    $array = $result->toArray();

    $this->assertNull($array['expires_at']);
    $this->assertFalse($array['is_valid']);
  }

}

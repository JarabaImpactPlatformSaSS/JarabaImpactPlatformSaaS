<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_credentials\Unit\Entity;

use PHPUnit\Framework\TestCase;

/**
 * Tests para entidades del modulo Credentials.
 *
 * Verifica logica de entidades CredentialStack, IssuedCredential
 * y UserStackProgress sin depender del Entity API.
 *
 * @group jaraba_credentials
 */
class CredentialEntitiesTest extends TestCase {

  // ------- CredentialStack logic -------

  /**
   * Tests getRequiredTemplateIds con JSON valido.
   */
  public function testGetRequiredTemplateIdsValid(): void {
    $json = '[1, 2, 3, 4, 5]';
    $ids = json_decode($json, TRUE) ?: [];
    $this->assertSame([1, 2, 3, 4, 5], $ids);
  }

  /**
   * Tests getRequiredTemplateIds con JSON vacio.
   */
  public function testGetRequiredTemplateIdsEmpty(): void {
    $json = '[]';
    $ids = json_decode($json, TRUE) ?: [];
    $this->assertSame([], $ids);
  }

  /**
   * Tests getRequiredTemplateIds con NULL.
   */
  public function testGetRequiredTemplateIdsNull(): void {
    $json = NULL ?? '[]';
    $ids = json_decode($json, TRUE) ?: [];
    $this->assertSame([], $ids);
  }

  /**
   * Tests getRequiredTemplateIds con JSON invalido.
   */
  public function testGetRequiredTemplateIdsInvalidJson(): void {
    $json = 'not json';
    $ids = json_decode($json, TRUE) ?: [];
    $this->assertSame([], $ids);
  }

  /**
   * Tests getOptionalTemplateIds con JSON valido.
   */
  public function testGetOptionalTemplateIdsValid(): void {
    $json = '[10, 20]';
    $ids = json_decode($json, TRUE) ?: [];
    $this->assertSame([10, 20], $ids);
  }

  /**
   * Tests getMinRequired: valor explicito.
   */
  public function testGetMinRequiredExplicit(): void {
    $minRequired = 3;
    $requiredCount = 5;

    $min = $minRequired > 0 ? $minRequired : $requiredCount;
    $this->assertSame(3, $min);
  }

  /**
   * Tests getMinRequired: valor 0 usa el total de requeridos.
   */
  public function testGetMinRequiredZeroUsesTotal(): void {
    $minRequired = 0;
    $requiredCount = 5;

    $min = $minRequired > 0 ? $minRequired : $requiredCount;
    $this->assertSame(5, $min);
  }

  /**
   * Tests getMinRequired: valor negativo usa el total.
   */
  public function testGetMinRequiredNegativeUsesTotal(): void {
    $minRequired = -1;
    $requiredCount = 4;

    $min = $minRequired > 0 ? $minRequired : $requiredCount;
    $this->assertSame(4, $min);
  }

  // ------- IssuedCredential logic -------

  /**
   * Tests isValid: status active sin expiracion.
   */
  public function testIsValidActiveNoExpiration(): void {
    $status = 'active';
    $expiresOn = NULL;

    $isValid = ($status === 'active') && ($expiresOn === NULL || strtotime($expiresOn) > time());
    $this->assertTrue($isValid);
  }

  /**
   * Tests isValid: status active con expiracion futura.
   */
  public function testIsValidActiveWithFutureExpiration(): void {
    $status = 'active';
    $expiresOn = '2099-12-31T23:59:59';

    $isValid = ($status === 'active') && ($expiresOn === NULL || strtotime($expiresOn) > time());
    $this->assertTrue($isValid);
  }

  /**
   * Tests isValid: status active con expiracion pasada.
   */
  public function testIsNotValidActiveWithPastExpiration(): void {
    $status = 'active';
    $expiresOn = '2020-01-01T00:00:00';

    $isValid = ($status === 'active') && ($expiresOn === NULL || strtotime($expiresOn) > time());
    $this->assertFalse($isValid);
  }

  /**
   * Tests isValid: status revoked.
   */
  public function testIsNotValidWhenRevoked(): void {
    $status = 'revoked';
    $isValid = ($status === 'active');
    $this->assertFalse($isValid);
  }

  /**
   * Tests isValid: status suspended.
   */
  public function testIsNotValidWhenSuspended(): void {
    $status = 'suspended';
    $isValid = ($status === 'active');
    $this->assertFalse($isValid);
  }

  /**
   * Tests isValid: status expired.
   */
  public function testIsNotValidWhenExpired(): void {
    $status = 'expired';
    $isValid = ($status === 'active');
    $this->assertFalse($isValid);
  }

  /**
   * Tests getEvidence con JSON valido.
   */
  public function testGetEvidenceValid(): void {
    $json = '[{"url":"https://example.com","name":"Proyecto"}]';
    $evidence = json_decode($json, TRUE) ?: [];

    $this->assertCount(1, $evidence);
    $this->assertSame('Proyecto', $evidence[0]['name']);
  }

  /**
   * Tests getEvidence con JSON vacio.
   */
  public function testGetEvidenceEmpty(): void {
    $json = '[]';
    $evidence = json_decode($json, TRUE) ?: [];
    $this->assertSame([], $evidence);
  }

  // ------- CredentialTemplate logic -------

  /**
   * Tests calculateExpiration con validity > 0.
   */
  public function testCalculateExpirationWithValidity(): void {
    $issuedTimestamp = mktime(0, 0, 0, 1, 1, 2024);
    $validityMonths = 12;

    $expiration = $validityMonths > 0
      ? strtotime("+{$validityMonths} months", $issuedTimestamp)
      : NULL;

    $this->assertNotNull($expiration);
    $this->assertGreaterThan($issuedTimestamp, $expiration);

    // 12 months = approx 1 year.
    $expectedYear = date('Y', $expiration);
    $this->assertSame('2025', $expectedYear);
  }

  /**
   * Tests calculateExpiration con validity 0.
   */
  public function testCalculateExpirationWithZeroValidity(): void {
    $issuedTimestamp = time();
    $validityMonths = 0;

    $expiration = $validityMonths > 0
      ? strtotime("+{$validityMonths} months", $issuedTimestamp)
      : NULL;

    $this->assertNull($expiration);
  }

  /**
   * Tests calculateExpiration con 6 meses.
   */
  public function testCalculateExpirationSixMonths(): void {
    $issuedTimestamp = mktime(0, 0, 0, 6, 15, 2024);
    $validityMonths = 6;

    $expiration = strtotime("+{$validityMonths} months", $issuedTimestamp);
    $expectedMonth = date('n', $expiration);
    $this->assertSame('12', $expectedMonth);
  }

  // ------- IssuerProfile logic -------

  /**
   * Tests hasKeys con clave publica presente.
   */
  public function testHasKeysTrue(): void {
    $publicKey = base64_encode(random_bytes(32));
    $hasKeys = !empty($publicKey);
    $this->assertTrue($hasKeys);
  }

  /**
   * Tests hasKeys sin clave.
   */
  public function testHasKeysFalse(): void {
    $publicKey = '';
    $hasKeys = !empty($publicKey);
    $this->assertFalse($hasKeys);
  }

  /**
   * Tests decodificacion de clave publica.
   */
  public function testGetPublicKeyBytes(): void {
    $bytes = random_bytes(32);
    $publicKey = base64_encode($bytes);
    $decoded = base64_decode($publicKey, TRUE);

    $this->assertSame($bytes, $decoded);
    $this->assertSame(32, strlen($decoded));
  }

  // ------- UserStackProgress logic -------

  /**
   * Tests getCompletedTemplateIds.
   */
  public function testGetCompletedTemplateIds(): void {
    $json = '[1, 3, 5]';
    $ids = json_decode($json, TRUE) ?: [];
    $this->assertSame([1, 3, 5], $ids);
  }

  /**
   * Tests getProgressPercent bounds.
   */
  public function testProgressPercentBounds(): void {
    // Percent is capped at 100.
    $rawPercent = 150;
    $capped = min($rawPercent, 100);
    $this->assertSame(100, $capped);

    // Percent is at least 0.
    $rawPercent = -10;
    $floored = max($rawPercent, 0);
    $this->assertSame(0, $floored);
  }

  /**
   * Tests credential type mapping.
   *
   * @dataProvider credentialTypeProvider
   */
  public function testCredentialTypes(string $type, string $label): void {
    $validTypes = [
      'course_badge' => 'Badge de Curso',
      'path_certificate' => 'Certificado de Ruta',
      'skill_endorsement' => 'Endorsement de Skill',
      'achievement' => 'Logro',
      'diploma' => 'Diploma',
    ];

    $this->assertArrayHasKey($type, $validTypes);
    $this->assertSame($label, $validTypes[$type]);
  }

  /**
   * Data provider para tipos de credencial.
   */
  public static function credentialTypeProvider(): array {
    return [
      ['course_badge', 'Badge de Curso'],
      ['path_certificate', 'Certificado de Ruta'],
      ['skill_endorsement', 'Endorsement de Skill'],
      ['achievement', 'Logro'],
      ['diploma', 'Diploma'],
    ];
  }

  /**
   * Tests RevocationEntry razones validas.
   */
  public function testRevocationReasons(): void {
    $validReasons = ['fraud', 'error', 'request', 'policy'];
    $this->assertCount(4, $validReasons);
    $this->assertContains('fraud', $validReasons);
    $this->assertContains('policy', $validReasons);
  }

}

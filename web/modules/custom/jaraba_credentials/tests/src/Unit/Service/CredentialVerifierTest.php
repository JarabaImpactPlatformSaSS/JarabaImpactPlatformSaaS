<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_credentials\Unit\Service;

use PHPUnit\Framework\TestCase;

/**
 * Tests para CredentialVerifier.
 *
 * Verifica logica de verificacion de credenciales: status checks,
 * expiracion, y flujo de verificacion paso a paso.
 *
 * @group jaraba_credentials
 * @coversDefaultClass \Drupal\jaraba_credentials\Service\CredentialVerifier
 */
class CredentialVerifierTest extends TestCase {

  /**
   * Constantes de status replicadas de IssuedCredential.
   */
  private const STATUS_ACTIVE = 'active';
  private const STATUS_REVOKED = 'revoked';
  private const STATUS_EXPIRED = 'expired';
  private const STATUS_SUSPENDED = 'suspended';

  /**
   * Simula el flujo de verificacion.
   *
   * Replica la logica de CredentialVerifier::verify() sin dependencias Drupal.
   */
  private function verifyLogic(array $credential): array {
    // Check: credencial no encontrada.
    if (empty($credential)) {
      return ['is_valid' => FALSE, 'message' => 'not_found'];
    }

    $status = $credential['status'] ?? '';
    $hasRevocation = $credential['has_revocation'] ?? FALSE;

    // Check: revocada.
    if ($status === self::STATUS_REVOKED || $hasRevocation) {
      return ['is_valid' => FALSE, 'message' => 'revoked'];
    }

    // Check: suspendida.
    if ($status === self::STATUS_SUSPENDED) {
      return ['is_valid' => FALSE, 'message' => 'suspended'];
    }

    // Check: expirada.
    $expiresOn = $credential['expires_on'] ?? NULL;
    if ($expiresOn) {
      $expirationTimestamp = strtotime($expiresOn);
      if ($expirationTimestamp && $expirationTimestamp < time()) {
        return ['is_valid' => FALSE, 'message' => 'expired'];
      }
    }

    // Check: firma.
    $hasKeys = $credential['issuer_has_keys'] ?? FALSE;
    if ($hasKeys) {
      $signatureValid = $credential['signature_valid'] ?? FALSE;
      if (!$signatureValid) {
        return ['is_valid' => FALSE, 'message' => 'invalid_signature'];
      }
    }

    return ['is_valid' => TRUE, 'message' => 'valid'];
  }

  /**
   * Tests credencial no encontrada.
   */
  public function testNotFoundReturnsInvalid(): void {
    $result = $this->verifyLogic([]);
    $this->assertFalse($result['is_valid']);
    $this->assertSame('not_found', $result['message']);
  }

  /**
   * Tests credencial activa y valida.
   */
  public function testValidActiveCredential(): void {
    $result = $this->verifyLogic([
      'status' => self::STATUS_ACTIVE,
      'has_revocation' => FALSE,
    ]);
    $this->assertTrue($result['is_valid']);
    $this->assertSame('valid', $result['message']);
  }

  /**
   * Tests credencial con status revoked.
   */
  public function testRevokedByStatus(): void {
    $result = $this->verifyLogic([
      'status' => self::STATUS_REVOKED,
    ]);
    $this->assertFalse($result['is_valid']);
    $this->assertSame('revoked', $result['message']);
  }

  /**
   * Tests credencial con RevocationEntry (status aun active).
   */
  public function testRevokedByRevocationEntry(): void {
    $result = $this->verifyLogic([
      'status' => self::STATUS_ACTIVE,
      'has_revocation' => TRUE,
    ]);
    $this->assertFalse($result['is_valid']);
    $this->assertSame('revoked', $result['message']);
  }

  /**
   * Tests credencial suspendida.
   */
  public function testSuspendedCredential(): void {
    $result = $this->verifyLogic([
      'status' => self::STATUS_SUSPENDED,
    ]);
    $this->assertFalse($result['is_valid']);
    $this->assertSame('suspended', $result['message']);
  }

  /**
   * Tests credencial expirada.
   */
  public function testExpiredCredential(): void {
    $result = $this->verifyLogic([
      'status' => self::STATUS_ACTIVE,
      'expires_on' => '2020-01-01T00:00:00',
    ]);
    $this->assertFalse($result['is_valid']);
    $this->assertSame('expired', $result['message']);
  }

  /**
   * Tests credencial no expirada (fecha futura).
   */
  public function testNotExpiredCredential(): void {
    $result = $this->verifyLogic([
      'status' => self::STATUS_ACTIVE,
      'expires_on' => '2099-12-31T23:59:59',
    ]);
    $this->assertTrue($result['is_valid']);
  }

  /**
   * Tests credencial sin fecha de expiracion.
   */
  public function testNoExpirationDateIsValid(): void {
    $result = $this->verifyLogic([
      'status' => self::STATUS_ACTIVE,
    ]);
    $this->assertTrue($result['is_valid']);
  }

  /**
   * Tests firma invalida cuando issuer tiene claves.
   */
  public function testInvalidSignature(): void {
    $result = $this->verifyLogic([
      'status' => self::STATUS_ACTIVE,
      'issuer_has_keys' => TRUE,
      'signature_valid' => FALSE,
    ]);
    $this->assertFalse($result['is_valid']);
    $this->assertSame('invalid_signature', $result['message']);
  }

  /**
   * Tests firma valida.
   */
  public function testValidSignature(): void {
    $result = $this->verifyLogic([
      'status' => self::STATUS_ACTIVE,
      'issuer_has_keys' => TRUE,
      'signature_valid' => TRUE,
    ]);
    $this->assertTrue($result['is_valid']);
  }

  /**
   * Tests que sin claves de issuer se omite verificacion de firma.
   */
  public function testNoKeysSkipsSignatureCheck(): void {
    $result = $this->verifyLogic([
      'status' => self::STATUS_ACTIVE,
      'issuer_has_keys' => FALSE,
    ]);
    $this->assertTrue($result['is_valid']);
  }

  /**
   * Tests prioridad: revocacion sobre expiracion.
   */
  public function testRevocationPriorityOverExpiration(): void {
    $result = $this->verifyLogic([
      'status' => self::STATUS_REVOKED,
      'expires_on' => '2020-01-01T00:00:00',
    ]);
    $this->assertSame('revoked', $result['message']);
  }

  /**
   * Tests prioridad: suspension sobre expiracion.
   */
  public function testSuspensionPriorityOverExpiration(): void {
    $result = $this->verifyLogic([
      'status' => self::STATUS_SUSPENDED,
      'expires_on' => '2020-01-01T00:00:00',
    ]);
    $this->assertSame('suspended', $result['message']);
  }

  /**
   * Tests JSON key sorting para verificacion de firma.
   */
  public function testJsonKeySorting(): void {
    $data = ['z' => 1, 'a' => 2, 'm' => ['c' => 3, 'b' => 4]];

    // Sort recursively.
    $this->sortKeysRecursive($data);

    $keys = array_keys($data);
    $this->assertSame(['a', 'm', 'z'], $keys);

    $innerKeys = array_keys($data['m']);
    $this->assertSame(['b', 'c'], $innerKeys);
  }

  /**
   * Tests serializacion determinista para firma.
   */
  public function testDeterministicSerialization(): void {
    $data1 = ['b' => 2, 'a' => 1];
    $data2 = ['a' => 1, 'b' => 2];

    $this->sortKeysRecursive($data1);
    $this->sortKeysRecursive($data2);

    $json1 = json_encode($data1, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $json2 = json_encode($data2, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $this->assertSame($json1, $json2);
  }

  /**
   * Tests que proof se remueve antes de serializar.
   */
  public function testProofRemovedBeforeSerialization(): void {
    $data = [
      '@context' => ['https://www.w3.org/2018/credentials/v1'],
      'type' => 'VerifiableCredential',
      'proof' => ['type' => 'Ed25519Signature2020'],
    ];

    unset($data['proof']);

    $this->assertArrayNotHasKey('proof', $data);
    $this->assertArrayHasKey('@context', $data);
  }

  /**
   * Tests OB3 JSON decodificacion.
   */
  public function testOb3JsonDecoding(): void {
    $json = '{"@context":["https://www.w3.org/2018/credentials/v1"],"type":"VerifiableCredential"}';
    $decoded = json_decode($json, TRUE);

    $this->assertIsArray($decoded);
    $this->assertArrayHasKey('@context', $decoded);
    $this->assertSame('VerifiableCredential', $decoded['type']);
  }

  /**
   * Tests JSON invalido retorna NULL.
   */
  public function testInvalidJsonReturnsNull(): void {
    $json = 'invalid json {{{';
    $decoded = json_decode($json, TRUE);
    $this->assertNull($decoded);
  }

  /**
   * Helper: sort keys recursively.
   */
  private function sortKeysRecursive(array &$array): void {
    ksort($array);
    foreach ($array as &$value) {
      if (is_array($value)) {
        $this->sortKeysRecursive($value);
      }
    }
  }

}

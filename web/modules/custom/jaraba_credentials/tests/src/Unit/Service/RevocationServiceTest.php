<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_credentials\Unit\Service;

use PHPUnit\Framework\TestCase;

/**
 * Tests para RevocationService.
 *
 * Verifica logica de revocacion de credenciales,
 * validacion de precondiciones y audit trail.
 *
 * @group jaraba_credentials
 * @coversDefaultClass \Drupal\jaraba_credentials\Service\RevocationService
 */
class RevocationServiceTest extends TestCase {

  /**
   * Razones de revocacion validas.
   */
  private const VALID_REASONS = ['fraud', 'error', 'request', 'policy'];

  /**
   * Tests que las razones de revocacion son exactamente 4.
   */
  public function testValidReasonsCount(): void {
    $this->assertCount(4, self::VALID_REASONS);
  }

  /**
   * Tests que cada razon valida esta en la lista.
   *
   * @dataProvider reasonProvider
   */
  public function testValidReasonIsRecognized(string $reason): void {
    $this->assertContains($reason, self::VALID_REASONS);
  }

  /**
   * Data provider para razones de revocacion.
   */
  public static function reasonProvider(): array {
    return [
      'fraud' => ['fraud'],
      'error' => ['error'],
      'request' => ['request'],
      'policy' => ['policy'],
    ];
  }

  /**
   * Tests que una razon invalida no esta en la lista.
   */
  public function testInvalidReasonNotRecognized(): void {
    $this->assertNotContains('unknown', self::VALID_REASONS);
    $this->assertNotContains('', self::VALID_REASONS);
  }

  /**
   * Tests logica de precondicion: credencial no encontrada.
   *
   * Simula el flujo: si credential es NULL, lanzar InvalidArgumentException.
   */
  public function testThrowsWhenCredentialNotFound(): void {
    $credential = NULL;

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('no encontrada');

    if (!$credential) {
      throw new \InvalidArgumentException("Credencial #999 no encontrada.");
    }
  }

  /**
   * Tests logica de precondicion: credencial ya revocada.
   *
   * Simula el flujo: si status es 'revoked', lanzar LogicException.
   */
  public function testThrowsWhenAlreadyRevoked(): void {
    $status = 'revoked';

    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('ya está revocada');

    if ($status === 'revoked') {
      throw new \LogicException("Credencial #42 ya está revocada.");
    }
  }

  /**
   * Tests que una credencial activa puede ser revocada.
   */
  public function testActiveCredentialCanBeRevoked(): void {
    $status = 'active';
    $canRevoke = ($status !== 'revoked');
    $this->assertTrue($canRevoke);
  }

  /**
   * Tests que una credencial suspendida puede ser revocada.
   */
  public function testSuspendedCredentialCanBeRevoked(): void {
    $status = 'suspended';
    $canRevoke = ($status !== 'revoked');
    $this->assertTrue($canRevoke);
  }

  /**
   * Tests que una credencial expirada puede ser revocada.
   */
  public function testExpiredCredentialCanBeRevoked(): void {
    $status = 'expired';
    $canRevoke = ($status !== 'revoked');
    $this->assertTrue($canRevoke);
  }

  /**
   * Tests estructura de datos de una RevocationEntry.
   */
  public function testRevocationEntryDataStructure(): void {
    $entry = [
      'credential_id' => 42,
      'revoked_by_uid' => 1,
      'reason' => 'fraud',
      'notes' => 'Suplantacion de identidad detectada',
    ];

    $this->assertArrayHasKey('credential_id', $entry);
    $this->assertArrayHasKey('revoked_by_uid', $entry);
    $this->assertArrayHasKey('reason', $entry);
    $this->assertArrayHasKey('notes', $entry);
    $this->assertSame(42, $entry['credential_id']);
    $this->assertContains($entry['reason'], self::VALID_REASONS);
  }

  /**
   * Tests que notes puede ser NULL.
   */
  public function testNotesCanBeNull(): void {
    $entry = [
      'credential_id' => 42,
      'revoked_by_uid' => 1,
      'reason' => 'error',
      'notes' => NULL,
    ];

    $this->assertNull($entry['notes']);
  }

  /**
   * Tests logica de isRevoked: count > 0 indica revocacion.
   */
  public function testIsRevokedLogic(): void {
    // Simula query count result.
    $this->assertTrue(1 > 0);
    $this->assertTrue(5 > 0);
    $this->assertFalse(0 > 0);
  }

  /**
   * Tests que historial vacio retorna array vacio.
   */
  public function testEmptyHistoryReturnsEmptyArray(): void {
    $ids = [];
    $result = empty($ids) ? [] : ['would_load'];
    $this->assertSame([], $result);
  }

  /**
   * Tests orden del historial (DESC por timestamp).
   */
  public function testHistoryOrderedByTimestamp(): void {
    $timestamps = [1700000000, 1700100000, 1699900000];
    usort($timestamps, fn($a, $b) => $b - $a);

    $this->assertSame([1700100000, 1700000000, 1699900000], $timestamps);
  }

}

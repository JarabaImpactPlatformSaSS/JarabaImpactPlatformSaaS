<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_messaging\Unit\Model;

use Drupal\jaraba_messaging\Model\EncryptedPayload;
use PHPUnit\Framework\TestCase;

/**
 * Tests EncryptedPayload value object validation.
 *
 * @coversDefaultClass \Drupal\jaraba_messaging\Model\EncryptedPayload
 * @group jaraba_messaging
 */
class EncryptedPayloadTest extends TestCase {

  /**
   * Tests valid payload construction.
   */
  public function testValidConstruction(): void {
    $iv = random_bytes(12);
    $tag = random_bytes(16);
    $ciphertext = random_bytes(64);

    $payload = new EncryptedPayload(
      ciphertext: $ciphertext,
      iv: $iv,
      tag: $tag,
      key_id: 'tenant_1_v1',
    );

    $this->assertSame($ciphertext, $payload->ciphertext);
    $this->assertSame($iv, $payload->iv);
    $this->assertSame($tag, $payload->tag);
    $this->assertSame('tenant_1_v1', $payload->key_id);
  }

  /**
   * Tests that IV must be exactly 12 bytes.
   */
  public function testInvalidIvLengthThrows(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('IV must be exactly 12 bytes');

    new EncryptedPayload(
      ciphertext: 'test',
      iv: random_bytes(16),
      tag: random_bytes(16),
      key_id: 'test',
    );
  }

  /**
   * Tests that tag must be exactly 16 bytes.
   */
  public function testInvalidTagLengthThrows(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Authentication tag must be exactly 16 bytes');

    new EncryptedPayload(
      ciphertext: 'test',
      iv: random_bytes(12),
      tag: random_bytes(8),
      key_id: 'test',
    );
  }

  /**
   * Tests that empty IV is rejected.
   */
  public function testEmptyIvThrows(): void {
    $this->expectException(\InvalidArgumentException::class);

    new EncryptedPayload(
      ciphertext: 'test',
      iv: '',
      tag: random_bytes(16),
      key_id: 'test',
    );
  }

  /**
   * Tests immutability (readonly class).
   */
  public function testImmutability(): void {
    $payload = new EncryptedPayload(
      ciphertext: 'data',
      iv: random_bytes(12),
      tag: random_bytes(16),
      key_id: 'key1',
    );

    $reflection = new \ReflectionClass($payload);
    $this->assertTrue($reflection->isReadOnly());
    $this->assertTrue($reflection->isFinal());
  }

}

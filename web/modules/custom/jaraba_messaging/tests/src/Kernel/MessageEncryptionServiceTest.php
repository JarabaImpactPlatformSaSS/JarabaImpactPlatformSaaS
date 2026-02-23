<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_messaging\Kernel;

use Drupal\jaraba_messaging\Exception\DecryptionException;
use Drupal\jaraba_messaging\Model\EncryptedPayload;
use Drupal\jaraba_messaging\Service\MessageEncryptionService;
use Drupal\jaraba_messaging\Service\TenantKeyService;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests AES-256-GCM encryption/decryption roundtrip and tamper detection.
 *
 * @coversDefaultClass \Drupal\jaraba_messaging\Service\MessageEncryptionService
 * @group jaraba_messaging
 */
class MessageEncryptionServiceTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'group',
    'ecosistema_jaraba_core',
    'jaraba_messaging',
  ];

  protected MessageEncryptionService $encryptionService;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    // Set PMK env var BEFORE parent::setUp() triggers container build.
    putenv('JARABA_PMK=test-platform-master-key-for-unit-tests-only');

    parent::setUp();

    $this->installConfig(['jaraba_messaging']);

    $tenantKeyService = new TenantKeyService(
      $this->container->get('config.factory'),
      $this->container->get('logger.channel.jaraba_messaging'),
    );

    $this->encryptionService = new MessageEncryptionService(
      $tenantKeyService,
      $this->container->get('logger.channel.jaraba_messaging'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    putenv('JARABA_PMK');
    parent::tearDown();
  }

  /**
   * Tests encrypt/decrypt roundtrip preserves plaintext.
   */
  public function testEncryptDecryptRoundtrip(): void {
    $plaintext = 'Mensaje confidencial de prueba con caracteres UTF-8: ñ, ü, á.';
    $tenantId = 1;

    $payload = $this->encryptionService->encrypt($plaintext, $tenantId);
    $this->assertInstanceOf(EncryptedPayload::class, $payload);
    $this->assertSame(12, strlen($payload->iv));
    $this->assertSame(16, strlen($payload->tag));
    $this->assertNotEmpty($payload->ciphertext);

    $decrypted = $this->encryptionService->decrypt($payload, $tenantId);
    $this->assertSame($plaintext, $decrypted);
  }

  /**
   * Tests that each encryption produces a unique IV.
   */
  public function testIvUniqueness(): void {
    $plaintext = 'Same message encrypted twice.';
    $tenantId = 1;

    $payload1 = $this->encryptionService->encrypt($plaintext, $tenantId);
    $payload2 = $this->encryptionService->encrypt($plaintext, $tenantId);

    $this->assertNotSame($payload1->iv, $payload2->iv);
    $this->assertNotSame($payload1->ciphertext, $payload2->ciphertext);
  }

  /**
   * Tests that different tenants produce different ciphertexts.
   */
  public function testTenantIsolation(): void {
    $plaintext = 'Cross-tenant test message.';

    $payload1 = $this->encryptionService->encrypt($plaintext, 1);
    $payload2 = $this->encryptionService->encrypt($plaintext, 2);

    // Same plaintext, different tenant keys = different ciphertext.
    // Cannot decrypt with wrong tenant.
    $this->expectException(DecryptionException::class);
    $this->encryptionService->decrypt($payload1, 2);
  }

  /**
   * Tests tamper detection: modified ciphertext is rejected.
   */
  public function testTamperDetection(): void {
    $plaintext = 'Original untampered message.';
    $tenantId = 1;

    $payload = $this->encryptionService->encrypt($plaintext, $tenantId);

    // Tamper with ciphertext by flipping a byte.
    $tampered = $payload->ciphertext;
    $tampered[0] = chr(ord($tampered[0]) ^ 0xFF);

    $tamperedPayload = new EncryptedPayload(
      ciphertext: $tampered,
      iv: $payload->iv,
      tag: $payload->tag,
      key_id: $payload->key_id,
    );

    $this->expectException(DecryptionException::class);
    $this->encryptionService->decrypt($tamperedPayload, $tenantId);
  }

  /**
   * Tests empty plaintext encryption.
   */
  public function testEmptyPlaintext(): void {
    $payload = $this->encryptionService->encrypt('', 1);
    $decrypted = $this->encryptionService->decrypt($payload, 1);
    $this->assertSame('', $decrypted);
  }

  /**
   * Tests large message encryption (16KB).
   */
  public function testLargeMessage(): void {
    $plaintext = str_repeat('A', 16384);
    $tenantId = 1;

    $payload = $this->encryptionService->encrypt($plaintext, $tenantId);
    $decrypted = $this->encryptionService->decrypt($payload, $tenantId);
    $this->assertSame($plaintext, $decrypted);
  }

}

<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_legal_vault\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\jaraba_legal_vault\Service\VaultEncryptionService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests VaultEncryptionService — pure crypto operations.
 *
 * Security-critical: AES-256-GCM encryption, libsodium DEK wrapping,
 * SHA-256 content hashing, and access token generation.
 *
 * @group jaraba_legal_vault
 * @coversDefaultClass \Drupal\jaraba_legal_vault\Service\VaultEncryptionService
 */
class VaultEncryptionServiceTest extends TestCase {

  /**
   * The service under test.
   */
  protected VaultEncryptionService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')
      ->willReturnMap([
        ['kek', ''],
        ['encryption_algorithm', 'aes-256-gcm'],
      ]);

    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory->method('get')
      ->with('jaraba_legal_vault.settings')
      ->willReturn($config);

    $logger = $this->createMock(LoggerInterface::class);

    $this->service = new VaultEncryptionService($configFactory, $logger);
  }

  /**
   * Tests DEK generation produces 32-byte keys.
   */
  public function testGenerateDekLength(): void {
    $dek = $this->service->generateDek();
    $this->assertSame(32, strlen($dek));
  }

  /**
   * Tests DEK generation produces unique keys.
   */
  public function testGenerateDekUniqueness(): void {
    $dek1 = $this->service->generateDek();
    $dek2 = $this->service->generateDek();
    $this->assertNotSame($dek1, $dek2);
  }

  /**
   * Tests encrypt-decrypt round-trip produces original plaintext.
   */
  public function testEncryptDecryptRoundTrip(): void {
    $dek = $this->service->generateDek();
    $plaintext = 'Confidential legal document content: DNI verification results.';

    $encrypted = $this->service->encrypt($plaintext, $dek);
    $this->assertArrayHasKey('ciphertext', $encrypted);
    $this->assertArrayHasKey('iv', $encrypted);
    $this->assertArrayHasKey('tag', $encrypted);

    $decrypted = $this->service->decrypt(
      $encrypted['ciphertext'],
      $dek,
      $encrypted['iv'],
      $encrypted['tag']
    );

    $this->assertSame($plaintext, $decrypted);
  }

  /**
   * Tests encrypt produces different ciphertext each time (random IV).
   */
  public function testEncryptProducesDifferentCiphertext(): void {
    $dek = $this->service->generateDek();
    $plaintext = 'Same plaintext.';

    $encrypted1 = $this->service->encrypt($plaintext, $dek);
    $encrypted2 = $this->service->encrypt($plaintext, $dek);

    $this->assertNotSame($encrypted1['ciphertext'], $encrypted2['ciphertext']);
    $this->assertNotSame($encrypted1['iv'], $encrypted2['iv']);
  }

  /**
   * Tests hashContent is deterministic.
   */
  public function testHashContentDeterministic(): void {
    $content = 'Legal document content v1.0';
    $hash1 = $this->service->hashContent($content);
    $hash2 = $this->service->hashContent($content);

    $this->assertSame($hash1, $hash2);
    $this->assertSame(64, strlen($hash1), 'SHA-256 should produce 64 hex chars.');
  }

  /**
   * Tests hashContent differs for different content.
   */
  public function testHashContentDiffers(): void {
    $hash1 = $this->service->hashContent('Document A');
    $hash2 = $this->service->hashContent('Document B');
    $this->assertNotSame($hash1, $hash2);
  }

  /**
   * Tests access token generation length.
   */
  public function testGenerateAccessTokenLength(): void {
    $token = $this->service->generateAccessToken(32);
    $this->assertSame(64, strlen($token), 'Token of 32 bytes = 64 hex chars.');
  }

  /**
   * Tests access token uniqueness.
   */
  public function testGenerateAccessTokenUniqueness(): void {
    $token1 = $this->service->generateAccessToken();
    $token2 = $this->service->generateAccessToken();
    $this->assertNotSame($token1, $token2);
  }

}

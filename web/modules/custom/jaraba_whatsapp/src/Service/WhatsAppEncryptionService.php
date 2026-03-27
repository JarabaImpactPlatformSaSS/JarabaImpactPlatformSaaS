<?php

declare(strict_types=1);

namespace Drupal\jaraba_whatsapp\Service;

use Psr\Log\LoggerInterface;

/**
 * AES-256-GCM encryption for WhatsApp phone numbers and message bodies.
 *
 * SECRET-MGMT-001: Encryption key via getenv('WA_ENCRYPTION_KEY').
 * Uses AES-256-GCM (authenticated encryption) with random IV per operation.
 */
class WhatsAppEncryptionService {

  /**
   * Cipher method.
   */
  private const CIPHER = 'aes-256-gcm';

  /**
   * Tag length for GCM.
   */
  private const TAG_LENGTH = 16;

  public function __construct(
    protected LoggerInterface $logger,
  ) {}

  /**
   * Encrypts a plaintext string.
   *
   * @param string $plaintext
   *   The text to encrypt.
   *
   * @return array{iv: string, ciphertext: string, tag: string}|null
   *   Encrypted data or NULL on failure.
   */
  public function encrypt(string $plaintext): ?array {
    $key = $this->getKey();
    if ($key === NULL) {
      return NULL;
    }

    $ivLength = openssl_cipher_iv_length(self::CIPHER);
    if ($ivLength === false) {
      return NULL;
    }
    $iv = openssl_random_pseudo_bytes($ivLength);
    $tag = '';

    $ciphertext = openssl_encrypt($plaintext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag, '', self::TAG_LENGTH);

    if ($ciphertext === false) {
      $this->logger->error('WhatsApp encryption failed.');
      return NULL;
    }

    return [
      'iv' => base64_encode($iv),
      'ciphertext' => base64_encode($ciphertext),
      'tag' => base64_encode($tag),
    ];
  }

  /**
   * Decrypts encrypted data.
   *
   * @param array $encrypted
   *   Array with iv, ciphertext, tag keys.
   *
   * @return string|null
   *   Decrypted plaintext or NULL on failure.
   */
  public function decrypt(array $encrypted): ?string {
    $key = $this->getKey();
    if ($key === NULL) {
      return NULL;
    }

    $iv = base64_decode($encrypted['iv'] ?? '', TRUE);
    $ciphertext = base64_decode($encrypted['ciphertext'] ?? '', TRUE);
    $tag = base64_decode($encrypted['tag'] ?? '', TRUE);

    if ($iv === false || $ciphertext === false || $tag === false) {
      return NULL;
    }

    $plaintext = openssl_decrypt($ciphertext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag);

    if ($plaintext === false) {
      $this->logger->error('WhatsApp decryption failed (auth tag mismatch?).');
      return NULL;
    }

    return $plaintext;
  }

  /**
   * Gets the encryption key from environment.
   */
  protected function getKey(): ?string {
    $key = getenv('WA_ENCRYPTION_KEY');
    if ($key === false || $key === '') {
      $this->logger->error('WA_ENCRYPTION_KEY not set in environment.');
      return NULL;
    }
    // Derive 32-byte key from env value.
    return hash('sha256', $key, TRUE);
  }

}

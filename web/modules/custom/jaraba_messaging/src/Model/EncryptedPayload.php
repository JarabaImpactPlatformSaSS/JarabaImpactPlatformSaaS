<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Model;

/**
 * Value Object representing an AES-256-GCM encrypted payload.
 *
 * Immutable container for the four components produced by
 * openssl_encrypt() with AEAD: ciphertext, initialization vector,
 * authentication tag, and the key identifier used for encryption.
 *
 * Constructor validates IV and tag lengths per NIST SP 800-38D:
 *   - IV must be exactly 12 bytes (96 bits).
 *   - Tag must be exactly 16 bytes (128 bits).
 */
final readonly class EncryptedPayload {

  /**
   * Required IV length in bytes (96 bits per NIST SP 800-38D).
   */
  private const IV_LENGTH = 12;

  /**
   * Required authentication tag length in bytes (128 bits).
   */
  private const TAG_LENGTH = 16;

  /**
   * Constructs an EncryptedPayload.
   *
   * @param string $ciphertext
   *   The encrypted data (raw binary or base64-encoded).
   * @param string $iv
   *   The initialization vector (exactly 12 raw bytes).
   * @param string $tag
   *   The GCM authentication tag (exactly 16 raw bytes).
   * @param string $key_id
   *   Identifier of the encryption key used.
   *
   * @throws \InvalidArgumentException
   *   If IV is not 12 bytes or tag is not 16 bytes.
   */
  public function __construct(
    public string $ciphertext,
    public string $iv,
    public string $tag,
    public string $key_id,
  ) {
    if (strlen($this->iv) !== self::IV_LENGTH) {
      throw new \InvalidArgumentException(sprintf(
        'IV must be exactly %d bytes, got %d.',
        self::IV_LENGTH,
        strlen($this->iv),
      ));
    }

    if (strlen($this->tag) !== self::TAG_LENGTH) {
      throw new \InvalidArgumentException(sprintf(
        'Authentication tag must be exactly %d bytes, got %d.',
        self::TAG_LENGTH,
        strlen($this->tag),
      ));
    }
  }

}

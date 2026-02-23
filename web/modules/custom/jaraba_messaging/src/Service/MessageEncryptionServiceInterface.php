<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Service;

use Drupal\jaraba_messaging\Model\EncryptedPayload;

/**
 * Interface para el servicio de cifrado de mensajes AES-256-GCM.
 */
interface MessageEncryptionServiceInterface {

  /**
   * Encrypts a plaintext message for a given tenant.
   *
   * @param string $plaintext
   *   The message content to encrypt.
   * @param int $tenantId
   *   The tenant ID for key derivation.
   *
   * @return \Drupal\jaraba_messaging\Model\EncryptedPayload
   *   The encrypted payload containing ciphertext, IV, tag, and key ID.
   *
   * @throws \Drupal\jaraba_messaging\Exception\EncryptionException
   */
  public function encrypt(string $plaintext, int $tenantId): EncryptedPayload;

  /**
   * Decrypts an encrypted payload for a given tenant.
   *
   * @param \Drupal\jaraba_messaging\Model\EncryptedPayload $payload
   *   The encrypted payload.
   * @param int $tenantId
   *   The tenant ID for key derivation.
   *
   * @return string
   *   The decrypted plaintext.
   *
   * @throws \Drupal\jaraba_messaging\Exception\DecryptionException
   */
  public function decrypt(EncryptedPayload $payload, int $tenantId): string;

}

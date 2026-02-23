<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Service;

use Drupal\jaraba_messaging\Exception\DecryptionException;
use Drupal\jaraba_messaging\Exception\EncryptionException;
use Drupal\jaraba_messaging\Model\EncryptedPayload;
use Psr\Log\LoggerInterface;

/**
 * Cifrado/descifrado de mensajes con AES-256-GCM.
 *
 * PROPÓSITO:
 * Cifra y descifra el contenido de mensajes usando AES-256-GCM con
 * IV aleatorio de 12 bytes y tag de autenticación de 16 bytes.
 *
 * SEGURIDAD:
 * - IV generado con random_bytes() (CSPRNG).
 * - Tag GCM proporciona integridad y autenticidad.
 * - Clave derivada por tenant vía TenantKeyService (Argon2id).
 */
class MessageEncryptionService implements MessageEncryptionServiceInterface {

  private const CIPHER = 'aes-256-gcm';
  private const IV_LENGTH = 12;
  private const TAG_LENGTH = 16;

  public function __construct(
    protected TenantKeyService $tenantKeyService,
    protected LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function encrypt(string $plaintext, int $tenantId): EncryptedPayload {
    try {
      $key = $this->tenantKeyService->getTenantKey($tenantId);
      $keyId = $this->tenantKeyService->getKeyId($tenantId);

      $iv = random_bytes(self::IV_LENGTH);
      $tag = '';

      $ciphertext = openssl_encrypt(
        $plaintext,
        self::CIPHER,
        $key,
        OPENSSL_RAW_DATA,
        $iv,
        $tag,
        '',
        self::TAG_LENGTH,
      );

      if ($ciphertext === FALSE) {
        throw new EncryptionException('openssl_encrypt returned false: ' . openssl_error_string());
      }

      return new EncryptedPayload(
        ciphertext: $ciphertext,
        iv: $iv,
        tag: $tag,
        keyId: $keyId,
      );
    }
    catch (EncryptionException $e) {
      throw $e;
    }
    catch (\Throwable $e) {
      $this->logger->error('Encryption failed for tenant @tenant: @message', [
        '@tenant' => $tenantId,
        '@message' => $e->getMessage(),
      ]);
      throw new EncryptionException('Failed to encrypt message: ' . $e->getMessage(), 0, $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function decrypt(EncryptedPayload $payload, int $tenantId): string {
    try {
      $key = $this->tenantKeyService->getTenantKey($tenantId);

      $plaintext = openssl_decrypt(
        $payload->ciphertext,
        self::CIPHER,
        $key,
        OPENSSL_RAW_DATA,
        $payload->iv,
        $payload->tag,
      );

      if ($plaintext === FALSE) {
        throw new DecryptionException('openssl_decrypt returned false — wrong key or tampered data.');
      }

      return $plaintext;
    }
    catch (DecryptionException $e) {
      throw $e;
    }
    catch (\Throwable $e) {
      $this->logger->error('Decryption failed for tenant @tenant: @message', [
        '@tenant' => $tenantId,
        '@message' => $e->getMessage(),
      ]);
      throw new DecryptionException('Failed to decrypt message: ' . $e->getMessage(), 0, $e);
    }
  }

}

<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Derivación de clave por tenant usando Argon2id.
 *
 * PROPÓSITO:
 * Deriva una clave AES-256 única por tenant a partir de una Platform
 * Master Key (PMK) almacenada en variable de entorno, usando Argon2id
 * como KDF con el tenant_id como salt.
 *
 * JERARQUÍA DE CLAVES:
 * PMK (env JARABA_PMK) -> Argon2id(PMK, salt=tenant_id) -> Tenant Key (32 bytes)
 */
class TenantKeyService {

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Derives the encryption key for a specific tenant.
   *
   * @param int $tenantId
   *   The tenant ID used as salt for key derivation.
   *
   * @return string
   *   32-byte binary key for AES-256-GCM.
   *
   * @throws \RuntimeException
   *   If the Platform Master Key is not configured.
   */
  public function getTenantKey(int $tenantId): string {
    $pmk = $this->getPlatformMasterKey();
    $config = $this->configFactory->get('jaraba_messaging.settings');

    $memory = $config->get('encryption.argon2id_memory') ?? 65536;
    $iterations = $config->get('encryption.argon2id_iterations') ?? 3;

    $salt = 'jaraba_messaging_tenant_' . $tenantId;
    // Pad or hash salt to exactly 16 bytes (sodium requirement).
    $salt = substr(hash('sha256', $salt, TRUE), 0, SODIUM_CRYPTO_PWHASH_SALTBYTES);

    return sodium_crypto_pwhash(
      32,
      $pmk,
      $salt,
      $iterations,
      $memory * 1024,
      SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13,
    );
  }

  /**
   * Gets the key ID for a given tenant (for storage reference).
   *
   * @param int $tenantId
   *   The tenant ID.
   *
   * @return string
   *   A deterministic key identifier string.
   */
  public function getKeyId(int $tenantId): string {
    return 'tenant_' . $tenantId . '_v1';
  }

  /**
   * Retrieves the Platform Master Key from environment.
   *
   * @return string
   *   The PMK value.
   *
   * @throws \RuntimeException
   *   If JARABA_PMK environment variable is not set.
   */
  protected function getPlatformMasterKey(): string {
    $pmk = getenv('JARABA_PMK');

    if ($pmk === FALSE || $pmk === '') {
      $this->logger->critical('JARABA_PMK environment variable is not set.');
      throw new \RuntimeException('Platform Master Key (JARABA_PMK) is not configured.');
    }

    return $pmk;
  }

}

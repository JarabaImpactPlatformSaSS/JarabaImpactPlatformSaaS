<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_vault\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de cifrado envelope AES-256-GCM para la boveda documental.
 *
 * ESTRUCTURA:
 * Implementa cifrado envelope: cada documento se cifra con una DEK
 * (Data Encryption Key) unica, y la DEK se envuelve con la KEK
 * (Key Encryption Key) del tenant. Usa libsodium para primitivas.
 *
 * LOGICA:
 * - generateDek(): Genera DEK aleatoria de 256 bits.
 * - encrypt(): Cifra datos con AES-256-GCM usando DEK + IV aleatorio.
 * - decrypt(): Descifra con DEK + IV + tag de autenticacion.
 * - wrapDek(): Envuelve DEK con la KEK del tenant (secretbox).
 * - unwrapDek(): Desenvuelve DEK con KEK.
 * - reEncryptDekForRecipient(): Re-cifra DEK para comparticion.
 *
 * RELACIONES:
 * - VaultEncryptionService -> ConfigFactory: obtiene KEK del tenant.
 * - VaultEncryptionService <- DocumentVaultService: cifra/descifra docs.
 * - VaultEncryptionService <- DocumentAccessService: re-cifra DEK.
 */
class VaultEncryptionService {

  /**
   * Algoritmo de cifrado simetrico.
   */
  private const ALGORITHM = 'aes-256-gcm';

  /**
   * Longitud del IV en bytes (96 bits para GCM).
   */
  private const IV_LENGTH = 12;

  /**
   * Longitud del tag de autenticacion GCM en bytes.
   */
  private const TAG_LENGTH = 16;

  /**
   * Construye una nueva instancia de VaultEncryptionService.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Genera una DEK aleatoria de 256 bits.
   *
   * @return string
   *   DEK en formato binario (32 bytes).
   */
  public function generateDek(): string {
    return random_bytes(32);
  }

  /**
   * Cifra datos con AES-256-GCM.
   *
   * @param string $plaintext
   *   Datos en claro.
   * @param string $dek
   *   DEK binaria de 32 bytes.
   *
   * @return array{ciphertext: string, iv: string, tag: string}
   *   Array con ciphertext binario, IV hex y tag hex.
   *
   * @throws \RuntimeException
   *   Si el cifrado falla.
   */
  public function encrypt(string $plaintext, string $dek): array {
    $iv = random_bytes(self::IV_LENGTH);
    $tag = '';

    $ciphertext = openssl_encrypt(
      $plaintext,
      self::ALGORITHM,
      $dek,
      OPENSSL_RAW_DATA,
      $iv,
      $tag,
      '',
      self::TAG_LENGTH
    );

    if ($ciphertext === FALSE) {
      throw new \RuntimeException('Vault encryption failed: ' . openssl_error_string());
    }

    return [
      'ciphertext' => $ciphertext,
      'iv' => bin2hex($iv),
      'tag' => bin2hex($tag),
    ];
  }

  /**
   * Descifra datos con AES-256-GCM.
   *
   * @param string $ciphertext
   *   Datos cifrados (binario).
   * @param string $dek
   *   DEK binaria de 32 bytes.
   * @param string $ivHex
   *   IV en hexadecimal.
   * @param string $tagHex
   *   Tag de autenticacion en hexadecimal.
   *
   * @return string
   *   Datos descifrados.
   *
   * @throws \RuntimeException
   *   Si el descifrado falla (datos corruptos o tag invalido).
   */
  public function decrypt(string $ciphertext, string $dek, string $ivHex, string $tagHex): string {
    $iv = hex2bin($ivHex);
    $tag = hex2bin($tagHex);

    if ($iv === FALSE || $tag === FALSE) {
      throw new \RuntimeException('Vault decryption failed: invalid IV or tag hex encoding.');
    }

    $plaintext = openssl_decrypt(
      $ciphertext,
      self::ALGORITHM,
      $dek,
      OPENSSL_RAW_DATA,
      $iv,
      $tag
    );

    if ($plaintext === FALSE) {
      throw new \RuntimeException('Vault decryption failed: authentication tag mismatch or corrupt data.');
    }

    return $plaintext;
  }

  /**
   * Envuelve (wrap) la DEK con la KEK del tenant usando libsodium secretbox.
   *
   * @param string $dek
   *   DEK binaria de 32 bytes.
   *
   * @return string
   *   DEK envuelta (nonce + ciphertext) en base64.
   */
  public function wrapDek(string $dek): string {
    $kek = $this->getKek();
    $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    $wrapped = sodium_crypto_secretbox($dek, $nonce, $kek);

    return base64_encode($nonce . $wrapped);
  }

  /**
   * Desenvuelve (unwrap) la DEK con la KEK del tenant.
   *
   * @param string $wrappedDekBase64
   *   DEK envuelta en base64.
   *
   * @return string
   *   DEK binaria de 32 bytes.
   *
   * @throws \RuntimeException
   *   Si la KEK es incorrecta o los datos estan corruptos.
   */
  public function unwrapDek(string $wrappedDekBase64): string {
    $kek = $this->getKek();
    $decoded = base64_decode($wrappedDekBase64, TRUE);

    if ($decoded === FALSE || strlen($decoded) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
      throw new \RuntimeException('Vault DEK unwrap failed: invalid base64 encoding.');
    }

    $nonce = substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    $ciphertext = substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

    $dek = sodium_crypto_secretbox_open($ciphertext, $nonce, $kek);

    if ($dek === FALSE) {
      throw new \RuntimeException('Vault DEK unwrap failed: invalid KEK or corrupt data.');
    }

    return $dek;
  }

  /**
   * Re-cifra la DEK para un destinatario (comparticion).
   *
   * @param string $wrappedDekBase64
   *   DEK envuelta original en base64.
   *
   * @return string
   *   Nueva DEK envuelta en base64 (misma KEK, nuevo nonce).
   */
  public function reEncryptDekForRecipient(string $wrappedDekBase64): string {
    $dek = $this->unwrapDek($wrappedDekBase64);
    return $this->wrapDek($dek);
  }

  /**
   * Genera el hash SHA-256 del contenido.
   *
   * @param string $content
   *   Contenido en claro.
   *
   * @return string
   *   Hash SHA-256 en hexadecimal (64 caracteres).
   */
  public function hashContent(string $content): string {
    return hash('sha256', $content);
  }

  /**
   * Genera un token de acceso criptograficamente seguro.
   *
   * @param int $length
   *   Longitud del token en bytes (default 32 = 64 hex chars).
   *
   * @return string
   *   Token en hexadecimal.
   */
  public function generateAccessToken(int $length = 32): string {
    return bin2hex(random_bytes($length));
  }

  /**
   * Obtiene la KEK del tenant desde configuracion.
   *
   * @return string
   *   KEK binaria de 32 bytes.
   *
   * @throws \RuntimeException
   *   Si la KEK no esta configurada.
   */
  protected function getKek(): string {
    $config = $this->configFactory->get('jaraba_legal_vault.settings');
    $kekBase64 = $config->get('kek') ?: getenv('VAULT_KEK');

    if (empty($kekBase64)) {
      // Generar KEK por defecto para desarrollo. En produccion debe configurarse.
      $this->logger->warning('Vault KEK not configured. Using derived key from Drupal hash salt.');
      $hashSalt = \Drupal\Core\Site\Settings::getHashSalt();
      return hash('sha256', 'vault_kek_' . $hashSalt, TRUE);
    }

    $kek = base64_decode($kekBase64, TRUE);
    if ($kek === FALSE || strlen($kek) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
      throw new \RuntimeException('Vault KEK is invalid. Must be 32 bytes base64-encoded.');
    }

    return $kek;
  }

}

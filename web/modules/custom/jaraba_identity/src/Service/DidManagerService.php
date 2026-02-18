<?php

declare(strict_types=1);

namespace Drupal\jaraba_identity\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\jaraba_credentials\Service\CryptographyService;
use Psr\Log\LoggerInterface;

/**
 * Servicio gestor de Identidades Descentralizadas (DID).
 *
 * Se encarga de la creación, custodia y uso de identidades digitales.
 */
class DidManagerService {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly CryptographyService $cryptoService,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Crea una nueva Identity Wallet para un usuario.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   El usuario propietario.
   * @param string $type
   *   Tipo de identidad ('person', 'agent').
   *
   * @return \Drupal\jaraba_identity\Entity\IdentityWallet
   *   La wallet creada.
   */
  public function createWallet(AccountInterface $user, string $type = 'person'): object {
    // 1. Generar par de claves Ed25519.
    $keys = $this->cryptoService->generateKeyPair();

    // 2. Generar DID (did:jaraba:uuid).
    $uuid = \Drupal::service('uuid')->generate();
    $did = "did:jaraba:{$uuid}";

    // 3. Encriptar clave privada (Custodial).
    $encryptedKey = $this->cryptoService->encryptPrivateKey($keys['private']);

    // 4. Persistir.
    $wallet = $this->entityTypeManager->getStorage('identity_wallet')->create([
      'uid' => $user->id(),
      'did' => $did,
      'public_key' => $keys['public'],
      'encrypted_private_key' => $encryptedKey,
      'type' => $type,
      'status' => TRUE,
    ]);
    $wallet->save();

    $this->logger->info('Identity Wallet creada para usuario @uid: @did', [
      '@uid' => $user->id(),
      '@did' => $did,
    ]);

    return $wallet;
  }

  /**
   * Firma un payload usando la identidad de un usuario.
   *
   * IMPORTANTE: Esta operación desencripta la clave privada en memoria,
   * firma y limpia la memoria inmediatamente. Requiere entorno seguro.
   */
  public function signPayload(string $did, string $payload): string {
    $wallet = $this->loadWalletByDid($did);
    if (!$wallet) {
      throw new \InvalidArgumentException("DID no encontrado: $did");
    }

    // 1. Desencriptar clave privada.
    $privateKey = $this->cryptoService->decryptPrivateKey($wallet->get('encrypted_private_key')->value);
    
    if (!$privateKey) {
      throw new \RuntimeException("Fallo crítico de seguridad: No se pudo desencriptar la clave para $did");
    }

    // 2. Firmar.
    try {
      $signature = $this->cryptoService->sign($payload, $privateKey);
    } finally {
      // 3. Limpieza paranoica de memoria (aunque PHP lo hace al salir del scope, forzamos).
      // CryptographyService::sign ya hace memzero, pero limpiamos nuestra copia local.
      if (function_exists('sodium_memzero')) {
        sodium_memzero($privateKey);
      } else {
        $privateKey = str_repeat("\0", strlen($privateKey));
      }
    }

    return $signature;
  }

  /**
   * Carga wallet por DID.
   */
  protected function loadWalletByDid(string $did): ?object {
    $storage = $this->entityTypeManager->getStorage('identity_wallet');
    $results = $storage->getQuery()
      ->accessCheck(FALSE) // System context
      ->condition('did', $did)
      ->condition('status', TRUE)
      ->execute();

    return $results ? $storage->load(reset($results)) : NULL;
  }

}

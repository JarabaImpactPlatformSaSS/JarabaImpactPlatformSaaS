<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de criptografía Ed25519 para firmar y verificar credenciales.
 *
 * Utiliza la extensión sodium de PHP para operaciones criptográficas seguras.
 * Las claves se almacenan en formato Base64.
 */
class CryptographyService
{

    /**
     * Logger del servicio.
     */
    protected LoggerInterface $logger;

    /**
     * Constructor del servicio.
     *
     * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
     *   Factory de canales de log.
     */
    public function __construct(LoggerChannelFactoryInterface $loggerFactory)
    {
        $this->logger = $loggerFactory->get('jaraba_credentials');
    }

    /**
     * Genera un par de claves Ed25519.
     *
     * @return array{public: string, private: string}
     *   Array con claves en formato Base64.
     *
     * @throws \RuntimeException
     *   Si sodium no está disponible.
     */
    public function generateKeyPair(): array
    {
        if (!extension_loaded('sodium')) {
            throw new \RuntimeException('La extensión sodium no está disponible.');
        }

        $keyPair = sodium_crypto_sign_keypair();

        return [
            'public' => base64_encode(sodium_crypto_sign_publickey($keyPair)),
            'private' => base64_encode(sodium_crypto_sign_secretkey($keyPair)),
        ];
    }

    /**
     * Firma un mensaje con una clave privada Ed25519.
     *
     * @param string $message
     *   El mensaje a firmar.
     * @param string $privateKeyBase64
     *   La clave privada en formato Base64.
     *
     * @return string
     *   La firma en formato Base64.
     *
     * @throws \InvalidArgumentException
     *   Si la clave no es válida.
     */
    public function sign(string $message, string $privateKeyBase64): string
    {
        $privateKey = base64_decode($privateKeyBase64, TRUE);
        if ($privateKey === FALSE || strlen($privateKey) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
            throw new \InvalidArgumentException('Clave privada Ed25519 inválida.');
        }

        $signature = sodium_crypto_sign_detached($message, $privateKey);

        // Limpiar clave de memoria
        sodium_memzero($privateKey);

        return base64_encode($signature);
    }

    /**
     * Verifica una firma Ed25519.
     *
     * @param string $message
     *   El mensaje original.
     * @param string $signatureBase64
     *   La firma en formato Base64.
     * @param string $publicKeyBase64
     *   La clave pública en formato Base64.
     *
     * @return bool
     *   TRUE si la firma es válida.
     */
    public function verify(string $message, string $signatureBase64, string $publicKeyBase64): bool
    {
        try {
            $signature = base64_decode($signatureBase64, TRUE);
            $publicKey = base64_decode($publicKeyBase64, TRUE);

            if ($signature === FALSE || $publicKey === FALSE) {
                $this->logger->warning('Datos de verificación inválidos (decodificación Base64 fallida).');
                return FALSE;
            }

            if (strlen($publicKey) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
                $this->logger->warning('Longitud de clave pública incorrecta.');
                return FALSE;
            }

            if (strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES) {
                $this->logger->warning('Longitud de firma incorrecta.');
                return FALSE;
            }

            return sodium_crypto_sign_verify_detached($signature, $message, $publicKey);
        } catch (\SodiumException $e) {
            $this->logger->error('Error sodium en verificación: @message', [
                '@message' => $e->getMessage(),
            ]);
            return FALSE;
        }
    }

    /**
     * Encripta una clave privada para almacenamiento seguro.
     *
     * Usa encriptación simétrica con una clave derivada del sitio.
     *
     * @param string $privateKeyBase64
     *   La clave privada en Base64.
     *
     * @return string
     *   La clave encriptada en Base64.
     */
    public function encryptPrivateKey(string $privateKeyBase64): string
    {
        $siteKey = $this->getSiteEncryptionKey();
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        $ciphertext = sodium_crypto_secretbox(
            $privateKeyBase64,
            $nonce,
            $siteKey
        );

        // Limpiar clave de memoria
        sodium_memzero($siteKey);

        return base64_encode($nonce . $ciphertext);
    }

    /**
     * Desencripta una clave privada almacenada.
     *
     * @param string $encryptedBase64
     *   La clave encriptada en Base64.
     *
     * @return string|null
     *   La clave privada desencriptada o NULL si falla.
     */
    public function decryptPrivateKey(string $encryptedBase64): ?string
    {
        try {
            $encrypted = base64_decode($encryptedBase64, TRUE);
            if ($encrypted === FALSE) {
                return NULL;
            }

            $nonceLength = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES;
            if (strlen($encrypted) <= $nonceLength) {
                return NULL;
            }

            $nonce = substr($encrypted, 0, $nonceLength);
            $ciphertext = substr($encrypted, $nonceLength);

            $siteKey = $this->getSiteEncryptionKey();
            $plaintext = sodium_crypto_secretbox_open($ciphertext, $nonce, $siteKey);

            // Limpiar clave de memoria
            sodium_memzero($siteKey);

            if ($plaintext === FALSE) {
                return NULL;
            }

            return $plaintext;
        } catch (\SodiumException $e) {
            $this->logger->error('Error desencriptando clave: @message', [
                '@message' => $e->getMessage(),
            ]);
            return NULL;
        }
    }

    /**
     * Obtiene la clave de encriptación del sitio.
     *
     * Deriva una clave de 32 bytes del hash salt del sitio.
     *
     * @return string
     *   Clave de 32 bytes.
     */
    protected function getSiteEncryptionKey(): string
    {
        $hashSalt = \Drupal::service('settings')->get('hash_salt');

        // Asegurar que hash_salt tiene longitud suficiente para usar como key
        // Si es muy corto, primero lo expandimos con hash simple
        $keyMaterial = hash('sha256', $hashSalt . 'jaraba_credentials_key_material_v1', TRUE);

        // Derivar clave de 32 bytes usando BLAKE2b con la clave expandida
        return sodium_crypto_generichash(
            'jaraba_credentials_key_v1',
            $keyMaterial,
            SODIUM_CRYPTO_SECRETBOX_KEYBYTES
        );
    }

    /**
     * Verifica si sodium está disponible.
     *
     * @return bool
     *   TRUE si sodium está disponible.
     */
    public function isSodiumAvailable(): bool
    {
        return extension_loaded('sodium');
    }

}

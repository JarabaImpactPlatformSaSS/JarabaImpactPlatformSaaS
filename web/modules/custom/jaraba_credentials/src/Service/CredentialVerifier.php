<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\jaraba_credentials\Entity\IssuedCredential;
use Psr\Log\LoggerInterface;

/**
 * Servicio de verificación pública de credenciales.
 *
 * Valida firmas Ed25519 y estado de credenciales.
 */
class CredentialVerifier
{

    /**
     * Entity type manager.
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * Servicio de criptografía.
     */
    protected CryptographyService $cryptography;

    /**
     * Logger.
     */
    protected LoggerInterface $logger;

    /**
     * Constructor del servicio.
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        CryptographyService $cryptography,
        LoggerChannelFactoryInterface $loggerFactory
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->cryptography = $cryptography;
        $this->logger = $loggerFactory->get('jaraba_credentials');
    }

    /**
     * Verifica una credencial por UUID.
     *
     * @param string $uuid
     *   UUID de la credencial.
     *
     * @return array
     *   Resultado de la verificación:
     *   - is_valid: bool
     *   - message: string (traducible)
     *   - credential: IssuedCredential|null
     *   - template: CredentialTemplate|null
     *   - issuer: IssuerProfile|null
     */
    public function verify(string $uuid): array
    {
        // Buscar credencial
        $credentials = $this->entityTypeManager->getStorage('issued_credential')
            ->loadByProperties(['uuid' => $uuid]);

        if (empty($credentials)) {
            return [
                'is_valid' => FALSE,
                'message' => t('Credencial no encontrada.'),
                'credential' => NULL,
                'template' => NULL,
                'issuer' => NULL,
            ];
        }

        /** @var \Drupal\jaraba_credentials\Entity\IssuedCredential $credential */
        $credential = reset($credentials);

        // Obtener template e issuer
        $template = $credential->getTemplate();
        $issuer = $template?->getIssuer();

        // Verificar estado
        $status = $credential->get('status')->value ?? '';
        if ($status === IssuedCredential::STATUS_REVOKED) {
            return [
                'is_valid' => FALSE,
                'message' => t('Esta credencial ha sido revocada.'),
                'credential' => $credential,
                'template' => $template,
                'issuer' => $issuer,
            ];
        }

        if ($status === IssuedCredential::STATUS_SUSPENDED) {
            return [
                'is_valid' => FALSE,
                'message' => t('Esta credencial está suspendida temporalmente.'),
                'credential' => $credential,
                'template' => $template,
                'issuer' => $issuer,
            ];
        }

        // Verificar expiración
        $expiresOn = $credential->get('expires_on')->value ?? NULL;
        if ($expiresOn) {
            $expirationTimestamp = strtotime($expiresOn);
            if ($expirationTimestamp && $expirationTimestamp < time()) {
                // Actualizar estado a expirado
                $credential->set('status', IssuedCredential::STATUS_EXPIRED);
                $credential->save();

                return [
                    'is_valid' => FALSE,
                    'message' => t('Esta credencial ha expirado.'),
                    'credential' => $credential,
                    'template' => $template,
                    'issuer' => $issuer,
                ];
            }
        }

        // Verificar firma criptográfica
        if ($issuer && $issuer->hasKeys()) {
            $signatureValid = $this->verifySignature($credential, $issuer);
            if (!$signatureValid) {
                return [
                    'is_valid' => FALSE,
                    'message' => t('La firma criptográfica de esta credencial no es válida.'),
                    'credential' => $credential,
                    'template' => $template,
                    'issuer' => $issuer,
                ];
            }
        }

        return [
            'is_valid' => TRUE,
            'message' => t('Esta credencial es válida y auténtica.'),
            'credential' => $credential,
            'template' => $template,
            'issuer' => $issuer,
        ];
    }

    /**
     * Verifica la firma de una credencial.
     *
     * @param \Drupal\jaraba_credentials\Entity\IssuedCredential $credential
     *   La credencial.
     * @param \Drupal\jaraba_credentials\Entity\IssuerProfile $issuer
     *   El emisor.
     *
     * @return bool
     *   TRUE si la firma es válida.
     */
    protected function verifySignature(IssuedCredential $credential, $issuer): bool
    {
        $signature = $credential->get('signature')->value ?? '';
        $ob3Json = $credential->get('ob3_json')->value ?? '';

        if (empty($signature) || empty($ob3Json)) {
            $this->logger->warning('Credencial @id sin firma o JSON.', [
                '@id' => $credential->id(),
            ]);
            return FALSE;
        }

        // Reconstruir datos para verificación
        $ob3Data = json_decode($ob3Json, TRUE);
        if (!$ob3Data) {
            return FALSE;
        }

        // Remover proof para verificar
        unset($ob3Data['proof']);

        // Ordenar claves
        $this->sortKeysRecursive($ob3Data);
        $serialized = json_encode($ob3Data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // Obtener clave pública
        $publicKey = $issuer->get('public_key')->value ?? '';
        if (empty($publicKey)) {
            $this->logger->warning('Emisor @id sin clave pública.', [
                '@id' => $issuer->id(),
            ]);
            return FALSE;
        }

        return $this->cryptography->verify($serialized, $signature, $publicKey);
    }

    /**
     * Ordena las claves del array recursivamente.
     *
     * @param array &$array
     *   Array a ordenar.
     */
    protected function sortKeysRecursive(array &$array): void
    {
        ksort($array);
        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->sortKeysRecursive($value);
            }
        }
    }

    /**
     * Obtiene el JSON OB3 de una credencial para visualización.
     *
     * @param string $uuid
     *   UUID de la credencial.
     *
     * @return array|null
     *   El JSON OB3 decodificado o NULL.
     */
    public function getOb3Json(string $uuid): ?array
    {
        $credentials = $this->entityTypeManager->getStorage('issued_credential')
            ->loadByProperties(['uuid' => $uuid]);

        if (empty($credentials)) {
            return NULL;
        }

        /** @var \Drupal\jaraba_credentials\Entity\IssuedCredential $credential */
        $credential = reset($credentials);
        $json = $credential->get('ob3_json')->value ?? '';

        return $json ? json_decode($json, TRUE) : NULL;
    }

}

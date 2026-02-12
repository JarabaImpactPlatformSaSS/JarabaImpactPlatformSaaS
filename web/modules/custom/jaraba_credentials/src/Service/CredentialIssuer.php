<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\jaraba_credentials\Entity\CredentialTemplate;
use Drupal\jaraba_credentials\Entity\IssuedCredential;
use Drupal\jaraba_credentials\Entity\IssuerProfile;
use Drupal\jaraba_credentials\Event\CredentialEvents;
use Drupal\jaraba_credentials\Event\CredentialIssuedEvent;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Servicio orquestador para emisión de credenciales.
 *
 * Coordina la creación, firma y almacenamiento de credenciales OB3.
 */
class CredentialIssuer
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
     * Constructor de OB3.
     */
    protected OpenBadgeBuilder $badgeBuilder;

    /**
     * Logger.
     */
    protected LoggerInterface $logger;

    /**
     * Despachador de eventos Symfony.
     *
     * P1-04: Inyeccion opcional para despachar CredentialIssuedEvent
     * tras la emision exitosa de una credencial.
     */
    protected ?EventDispatcherInterface $eventDispatcher;

    /**
     * Constructor del servicio.
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        CryptographyService $cryptography,
        OpenBadgeBuilder $badgeBuilder,
        LoggerChannelFactoryInterface $loggerFactory,
        ?EventDispatcherInterface $eventDispatcher = NULL,
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->cryptography = $cryptography;
        $this->badgeBuilder = $badgeBuilder;
        $this->logger = $loggerFactory->get('jaraba_credentials');
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Emite una credencial a un usuario.
     *
     * @param \Drupal\jaraba_credentials\Entity\CredentialTemplate $template
     *   El template a utilizar.
     * @param int $userId
     *   ID del usuario receptor.
     * @param array $context
     *   Contexto adicional (certification_id, exam_score, etc.).
     *
     * @return \Drupal\jaraba_credentials\Entity\IssuedCredential
     *   La credencial emitida.
     *
     * @throws \Exception
     *   Si hay error en la emisión.
     */
    public function issueCredential(CredentialTemplate $template, int $userId, array $context = []): IssuedCredential
    {
        // Obtener usuario
        $user = $this->entityTypeManager->getStorage('user')->load($userId);
        if (!$user instanceof UserInterface) {
            throw new \InvalidArgumentException("Usuario {$userId} no encontrado.");
        }

        // Obtener emisor
        $issuer = $template->getIssuer();
        if (!$issuer) {
            $issuer = $this->getDefaultIssuer();
        }
        if (!$issuer) {
            throw new \RuntimeException('No hay emisor configurado.');
        }

        // Preparar datos
        $now = \Drupal::time()->getRequestTime();
        $expiresOn = $template->calculateExpiration($now);

        $params = [
            'uuid' => \Drupal::service('uuid')->generate(),
            'issuer' => [
                'name' => $issuer->get('name')->value,
                'url' => $issuer->get('url')->value,
                'email' => $issuer->get('email')->value,
                'image' => $this->getEntityImageUrl($issuer, 'image'),
            ],
            'template' => [
                'machine_name' => $template->get('machine_name')->value,
                'name' => $template->get('name')->value,
                'description' => $template->get('description')->value ?? '',
                'criteria' => $template->get('criteria')->value ?? '',
                'credential_type' => $template->get('credential_type')->value,
                'image' => $this->getEntityImageUrl($template, 'image'),
            ],
            'recipient' => [
                'email' => $user->getEmail(),
                'name' => $user->getDisplayName(),
            ],
            'issued_on' => $now,
            'expires_on' => $expiresOn,
            'evidence' => $this->buildEvidence($context),
        ];

        // Construir JSON-LD OB3
        $ob3Credential = $this->badgeBuilder->buildCredential($params);

        // Firmar
        $serialized = $this->badgeBuilder->serializeForSigning($ob3Credential);
        $signature = $this->signWithIssuer($serialized, $issuer);

        // Agregar proof
        $ob3Credential = $this->badgeBuilder->addProof(
            $ob3Credential,
            $signature,
            $issuer->get('issuer_json_url')->value ?? $issuer->toUrl('canonical')->toString()
        );

        // Crear entidad
        /** @var \Drupal\jaraba_credentials\Entity\IssuedCredential $credential */
        $credential = $this->entityTypeManager->getStorage('issued_credential')->create([
            'credential_id_uri' => $ob3Credential['id'],
            'template_id' => $template->id(),
            'recipient_id' => $userId,
            'recipient_email' => $user->getEmail(),
            'recipient_name' => $user->getDisplayName(),
            'issued_on' => date('Y-m-d\TH:i:s', $now),
            'expires_on' => $expiresOn ? date('Y-m-d\TH:i:s', $expiresOn) : NULL,
            'evidence' => json_encode($this->buildEvidence($context)),
            'status' => IssuedCredential::STATUS_ACTIVE,
            'ob3_json' => json_encode($ob3Credential),
            'signature' => $signature,
            'verification_url' => $ob3Credential['id'],
            'certification_id' => $context['certification_id'] ?? NULL,
        ]);

        $credential->save();

        $this->logger->info('Credencial emitida: @id para usuario @user', [
            '@id' => $params['uuid'],
            '@user' => $userId,
        ]);

        // P1-04: Despachar evento para triggers reactivos
        // (notificaciones, webhooks, evaluacion de stacks).
        if ($this->eventDispatcher) {
            $event = new CredentialIssuedEvent($credential, $context);
            $this->eventDispatcher->dispatch($event, CredentialEvents::CREDENTIAL_ISSUED);
        }

        return $credential;
    }

    /**
     * Firma una credencial existente que no tiene firma.
     *
     * Útil para credenciales creadas manualmente via UI.
     *
     * @param \Drupal\jaraba_credentials\Entity\IssuedCredential $credential
     *   La credencial a firmar.
     *
     * @return bool
     *   TRUE si se firmó correctamente.
     *
     * @throws \Exception
     *   Si hay error en la firma.
     */
    public function signExistingCredential(IssuedCredential $credential): bool
    {
        // Verificar si ya tiene firma válida
        $existingSignature = $credential->get('signature')->value ?? '';
        if (!empty($existingSignature)) {
            $this->logger->notice('Credencial @id ya tiene firma.', [
                '@id' => $credential->id(),
            ]);
            return TRUE;
        }

        // Obtener template
        $templateId = $credential->get('template_id')->target_id;
        /** @var \Drupal\jaraba_credentials\Entity\CredentialTemplate $template */
        $template = $this->entityTypeManager->getStorage('credential_template')->load($templateId);
        if (!$template) {
            throw new \RuntimeException('Template no encontrado.');
        }

        // Obtener emisor
        $issuer = $template->getIssuer();
        if (!$issuer) {
            $issuer = $this->getDefaultIssuer();
        }
        if (!$issuer || !$issuer->hasKeys()) {
            throw new \RuntimeException('No hay emisor con claves configuradas.');
        }

        // Obtener usuario receptor
        $recipientId = $credential->get('recipient_id')->target_id;
        $user = $this->entityTypeManager->getStorage('user')->load($recipientId);

        // Construir parámetros OB3
        $issuedOn = strtotime($credential->get('issued_on')->value);
        $expiresOn = $credential->get('expires_on')->value ? strtotime($credential->get('expires_on')->value) : NULL;

        $params = [
            'uuid' => $credential->uuid(),
            'issuer' => [
                'name' => $issuer->get('name')->value,
                'url' => $issuer->get('url')->value,
                'email' => $issuer->get('email')->value ?? '',
                'image' => $this->getEntityImageUrl($issuer, 'image'),
            ],
            'template' => [
                'machine_name' => $template->get('machine_name')->value,
                'name' => $template->get('name')->value,
                'description' => $template->get('description')[0]->value ?? '',
                'criteria' => $template->get('criteria')[0]->value ?? '',
                'credential_type' => $template->get('credential_type')->value,
                'image' => $this->getEntityImageUrl($template, 'image'),
            ],
            'recipient' => [
                'email' => $credential->get('recipient_email')->value ?? ($user instanceof \Drupal\user\UserInterface ? $user->getEmail() : ''),
                'name' => $credential->get('recipient_name')->value ?? ($user instanceof \Drupal\user\UserInterface ? $user->getDisplayName() : ''),
            ],
            'issued_on' => $issuedOn,
            'expires_on' => $expiresOn,
            'evidence' => json_decode($credential->get('evidence')->value ?? '[]', TRUE) ?: [],
        ];

        // Construir JSON-LD OB3
        $ob3Credential = $this->badgeBuilder->buildCredential($params);

        // Firmar
        $serialized = $this->badgeBuilder->serializeForSigning($ob3Credential);
        $signature = $this->signWithIssuer($serialized, $issuer);

        // Agregar proof
        $ob3Credential = $this->badgeBuilder->addProof(
            $ob3Credential,
            $signature,
            $issuer->get('issuer_json_url')->value ?? $issuer->toUrl('canonical')->toString()
        );

        // Actualizar credencial
        $credential->set('ob3_json', json_encode($ob3Credential));
        $credential->set('signature', $signature);
        $credential->set('verification_url', $ob3Credential['id']);
        $credential->save();

        $this->logger->info('Credencial @id firmada correctamente.', [
            '@id' => $credential->id(),
        ]);

        return TRUE;
    }

    /**
     * Busca un template asociado a un programa de certificación.
     *
     * @param int $programId
     *   ID del programa de certificación.
     *
     * @return \Drupal\jaraba_credentials\Entity\CredentialTemplate|null
     *   El template o NULL.
     */
    public function findTemplateForProgram(int $programId): ?CredentialTemplate
    {
        $templates = $this->entityTypeManager->getStorage('credential_template')
            ->loadByProperties([
                'certification_program_id' => $programId,
                'is_active' => TRUE,
            ]);

        return !empty($templates) ? reset($templates) : NULL;
    }

    /**
     * Obtiene el emisor por defecto.
     *
     * @return \Drupal\jaraba_credentials\Entity\IssuerProfile|null
     *   El emisor por defecto o NULL.
     */
    protected function getDefaultIssuer(): ?IssuerProfile
    {
        $issuers = $this->entityTypeManager->getStorage('issuer_profile')
            ->loadByProperties(['is_default' => TRUE]);

        if (!empty($issuers)) {
            return reset($issuers);
        }

        // Si no hay default, tomar el primero
        $issuers = $this->entityTypeManager->getStorage('issuer_profile')
            ->loadMultiple();

        return !empty($issuers) ? reset($issuers) : NULL;
    }

    /**
     * Firma datos con el emisor.
     *
     * @param string $data
     *   Datos a firmar.
     * @param \Drupal\jaraba_credentials\Entity\IssuerProfile $issuer
     *   El emisor.
     *
     * @return string
     *   Firma en Base64.
     */
    protected function signWithIssuer(string $data, IssuerProfile $issuer): string
    {
        $encryptedKey = $issuer->get('private_key_encrypted')->value ?? '';
        if (empty($encryptedKey)) {
            throw new \RuntimeException('El emisor no tiene clave privada configurada.');
        }

        $privateKey = $this->cryptography->decryptPrivateKey($encryptedKey);
        if (!$privateKey) {
            throw new \RuntimeException('No se pudo desencriptar la clave privada.');
        }

        $signature = $this->cryptography->sign($data, $privateKey);

        // Limpiar clave de memoria
        sodium_memzero($privateKey);

        return $signature;
    }

    /**
     * Construye array de evidencias a partir del contexto.
     *
     * @param array $context
     *   Contexto de la emisión.
     *
     * @return array
     *   Array de evidencias.
     */
    protected function buildEvidence(array $context): array
    {
        $evidence = [];

        if (!empty($context['exam_score'])) {
            $evidence[] = [
                'name' => 'Examen de Certificación',
                'description' => sprintf('Puntuación: %d%%', (int) $context['exam_score']),
                'genre' => 'Exam',
            ];
        }

        if (!empty($context['certification_id'])) {
            $evidence[] = [
                'name' => 'Certificación Completada',
                'description' => 'Programa de certificación aprobado',
                'genre' => 'Certification',
            ];
        }

        return $evidence;
    }

    /**
     * Obtiene la URL de una imagen de entidad.
     *
     * @param \Drupal\Core\Entity\ContentEntityBase $entity
     *   La entidad.
     * @param string $field
     *   Nombre del campo de imagen.
     *
     * @return string|null
     *   URL de la imagen o NULL.
     */
    protected function getEntityImageUrl($entity, string $field): ?string
    {
        if (!$entity->hasField($field) || $entity->get($field)->isEmpty()) {
            return NULL;
        }

        /** @var \Drupal\file\FileInterface $file */
        $file = $entity->get($field)->entity;
        if (!$file) {
            return NULL;
        }

        return \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
    }

}

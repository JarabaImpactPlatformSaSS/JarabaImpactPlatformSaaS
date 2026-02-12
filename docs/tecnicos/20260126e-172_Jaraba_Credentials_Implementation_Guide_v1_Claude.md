JARABA CREDENTIALS MODULE
Guía de Implementación Técnica
Open Badges 3.0 · Verifiable Credentials
JARABA IMPACT PLATFORM
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica para Implementación
Código:	172_Jaraba_Credentials_Implementation_Guide
Módulo Drupal:	jaraba_credentials
Dependencias:	17_Credentials_System, 18_Certification_Workflow, Core Drupal 11
 
1. Resumen Ejecutivo
Este documento proporciona las especificaciones técnicas completas para la implementación del módulo jaraba_credentials en Drupal 11. El módulo gestiona la emisión, verificación, revocación y portabilidad de credenciales digitales siguiendo el estándar Open Badges 3.0 de 1EdTech con soporte completo para Verifiable Credentials (VC) del W3C.
1.1 Alcance del Módulo
•	Emisión automática: Generación de credenciales basada en triggers de completitud (cursos, paths, evaluaciones)
•	Verificación pública: Endpoint público con firma criptográfica Ed25519
•	Revocación controlada: Sistema de revocación con audit trail completo
•	Portabilidad: Exportación compatible con LinkedIn, Europass, Credly, Badgr
•	Multi-tenant: Cada tenant puede configurar su issuer profile y catálogo de badges
1.2 Stack Tecnológico
Componente	Tecnología
Core CMS	Drupal 11.x
Criptografía	Ed25519 (sodium extension PHP 8.3)
JSON-LD	ml/json-ld library
PDF Generation	TCPDF + QR Code library
Automatización	ECA Module (Events-Conditions-Actions)
API	JSON:API + Custom REST endpoints
 
2. Estructura del Módulo Drupal
2.1 Árbol de Directorios
modules/custom/jaraba_credentials/ ├── jaraba_credentials.info.yml ├── jaraba_credentials.module ├── jaraba_credentials.install ├── jaraba_credentials.permissions.yml ├── jaraba_credentials.routing.yml ├── jaraba_credentials.services.yml ├── jaraba_credentials.links.menu.yml ├── config/ │   ├── install/ │   │   ├── jaraba_credentials.settings.yml │   │   └── system.action.credential_issue.yml │   └── schema/ │       └── jaraba_credentials.schema.yml ├── src/ │   ├── Controller/ │   │   ├── CredentialVerifyController.php │   │   └── BadgeImageController.php │   ├── Entity/ │   │   ├── CredentialTemplate.php │   │   ├── IssuedCredential.php │   │   ├── IssuerProfile.php │   │   └── RevocationEntry.php │   ├── Form/ │   │   ├── CredentialTemplateForm.php │   │   ├── IssuerProfileForm.php │   │   └── CredentialSettingsForm.php │   ├── Service/ │   │   ├── CredentialIssuer.php │   │   ├── CredentialVerifier.php │   │   ├── CryptographyService.php │   │   ├── OpenBadgeBuilder.php │   │   └── PdfCertificateGenerator.php │   ├── Plugin/ │   │   ├── Action/ │   │   │   └── IssueCredentialAction.php │   │   └── QueueWorker/ │   │       └── CredentialBulkIssue.php │   └── EventSubscriber/ │       └── CredentialEventSubscriber.php └── templates/     ├── credential-verify-page.html.twig     ├── credential-certificate.html.twig     └── badge-embed.html.twig
2.2 Archivo jaraba_credentials.info.yml
name: 'Jaraba Credentials' type: module description: 'Open Badges 3.0 credential management system' package: Jaraba core_version_requirement: ^11 php: 8.3  dependencies:   - drupal:user   - drupal:file   - drupal:jsonapi   - eca:eca   - group:group  configure: jaraba_credentials.settings
 
3. Arquitectura de Entidades
El módulo define 4 entidades Drupal custom que conforman el sistema de credenciales.
3.1 Entidad: issuer_profile
Define la organización emisora de credenciales. Cada tenant tiene su propio issuer.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
name	VARCHAR(255)	Nombre de la organización	NOT NULL
url	VARCHAR(512)	URL del issuer	NOT NULL
email	VARCHAR(255)	Email de contacto	NOT NULL
description	TEXT	Descripción del issuer	NULLABLE
image_fid	INT	Logo del issuer (PNG)	FK file_managed.fid
public_key	TEXT	Clave pública Ed25519 (base64)	NOT NULL
private_key_encrypted	BLOB	Clave privada cifrada	NOT NULL
group_id	INT	Tenant propietario	FK groups.id
status	BOOLEAN	Activo/Inactivo	DEFAULT TRUE
created	DATETIME	Fecha de creación	NOT NULL
3.2 Entidad: credential_template
Define el modelo/plantilla de una credencial que puede ser emitida múltiples veces.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
name	VARCHAR(255)	Nombre de la credencial	NOT NULL
machine_name	VARCHAR(64)	Identificador máquina	UNIQUE per tenant
description	TEXT	Descripción detallada	NOT NULL
credential_type	VARCHAR(32)	Tipo de credencial	ENUM: course_badge|path_certificate|skill_endorsement|achievement|diploma
image_fid	INT	Imagen del badge (PNG)	FK file_managed.fid, NOT NULL
criteria_html	TEXT	Criterios de obtención	NOT NULL (HTML)
criteria_url	VARCHAR(512)	URL pública de criterios	Auto-generated
skills_json	JSON	Skills que certifica	Array of skill IDs
alignment_json	JSON	Alineación con frameworks	ESCO, O*NET codes
issuer_id	INT	Organización emisora	FK issuer_profile.id
validity_months	INT	Vigencia en meses	0 = no expira
credits_value	INT	Créditos de impacto	DEFAULT 0
xp_value	INT	Puntos de experiencia	DEFAULT 0
is_stackable	BOOLEAN	Puede formar parte de stack	DEFAULT FALSE
trigger_type	VARCHAR(32)	Tipo de trigger	ENUM: auto|manual|bulk
trigger_config	JSON	Configuración del trigger	Entity type, conditions
group_id	INT	Tenant propietario	FK groups.id
status	BOOLEAN	Template activo	DEFAULT TRUE
 
3.3 Entidad: issued_credential
Representa una credencial emitida a un usuario específico.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único (para URL)	UNIQUE, NOT NULL
template_id	INT	Template de credencial	FK credential_template.id
recipient_uid	INT	Usuario receptor	FK users.uid, NOT NULL
recipient_did	VARCHAR(255)	DID del receptor	did:email:user@domain
issued_at	DATETIME	Fecha de emisión	NOT NULL
expires_at	DATETIME	Fecha de expiración	NULLABLE
status	VARCHAR(16)	Estado de la credencial	ENUM: active|revoked|expired
evidence_json	JSON	Evidencias de obtención	Scores, dates, time spent
json_ld	LONGTEXT	JSON-LD completo OB3	NOT NULL
signature	TEXT	Firma Ed25519 (base64)	NOT NULL
pdf_fid	INT	Certificado PDF generado	FK file_managed.fid
verification_count	INT	Veces verificado	DEFAULT 0
last_verified_at	DATETIME	Última verificación	NULLABLE
shared_to_linkedin	BOOLEAN	Compartido en LinkedIn	DEFAULT FALSE
revocation_id	INT	Entrada de revocación	FK revocation_entry.id, NULLABLE
3.4 Entidad: revocation_entry
Registro de revocaciones para audit trail completo.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
credential_id	INT	Credencial revocada	FK issued_credential.id
revoked_at	DATETIME	Fecha de revocación	NOT NULL
revoked_by_uid	INT	Usuario que revocó	FK users.uid
reason	VARCHAR(32)	Motivo de revocación	ENUM: fraud|error|request|policy
notes	TEXT	Notas adicionales	NULLABLE
 
4. Servicios del Módulo
4.1 Archivo jaraba_credentials.services.yml
services:   jaraba_credentials.cryptography:     class: Drupal\jaraba_credentials\Service\CryptographyService     arguments: ['@config.factory', '@logger.channel.jaraba_credentials']    jaraba_credentials.open_badge_builder:     class: Drupal\jaraba_credentials\Service\OpenBadgeBuilder     arguments: ['@entity_type.manager', '@datetime.time', '@router']    jaraba_credentials.issuer:     class: Drupal\jaraba_credentials\Service\CredentialIssuer     arguments:       - '@entity_type.manager'       - '@jaraba_credentials.cryptography'       - '@jaraba_credentials.open_badge_builder'       - '@jaraba_credentials.pdf_generator'       - '@event_dispatcher'       - '@logger.channel.jaraba_credentials'    jaraba_credentials.verifier:     class: Drupal\jaraba_credentials\Service\CredentialVerifier     arguments:       - '@entity_type.manager'       - '@jaraba_credentials.cryptography'    jaraba_credentials.pdf_generator:     class: Drupal\jaraba_credentials\Service\PdfCertificateGenerator     arguments: ['@entity_type.manager', '@file_system', '@renderer']    logger.channel.jaraba_credentials:     parent: logger.channel_base     arguments: ['jaraba_credentials']
4.2 CryptographyService.php
Servicio de criptografía Ed25519 para firma y verificación de credenciales.
<?php namespace Drupal\jaraba_credentials\Service;  use Drupal\Core\Config\ConfigFactoryInterface; use Psr\Log\LoggerInterface;  class CryptographyService {      public function __construct(     protected ConfigFactoryInterface $configFactory,     protected LoggerInterface $logger   ) {}    /**    * Genera un par de claves Ed25519.    */   public function generateKeyPair(): array {     $keyPair = sodium_crypto_sign_keypair();     return [       'public_key' => base64_encode(sodium_crypto_sign_publickey($keyPair)),       'private_key' => sodium_crypto_sign_secretkey($keyPair),     ];   }    /**    * Firma datos con clave privada Ed25519.    */   public function sign(string $data, string $privateKey): string {     $signature = sodium_crypto_sign_detached($data, $privateKey);     return base64_encode($signature);   }    /**    * Verifica firma Ed25519.    */   public function verify(string $data, string $signature, string $publicKey): bool {     try {       $sigBytes = base64_decode($signature);       $pubBytes = base64_decode($publicKey);       return sodium_crypto_sign_verify_detached($sigBytes, $data, $pubBytes);     } catch (\Exception $e) {       $this->logger->error('Verification failed: @message', ['@message' => $e->getMessage()]);       return FALSE;     }   }    /**    * Encripta clave privada para almacenamiento.    */   public function encryptPrivateKey(string $privateKey): string {     $config = $this->configFactory->get('jaraba_credentials.settings');     $masterKey = base64_decode($config->get('master_encryption_key'));     $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);     $ciphertext = sodium_crypto_secretbox($privateKey, $nonce, $masterKey);     return base64_encode($nonce . $ciphertext);   }    /**    * Desencripta clave privada almacenada.    */   public function decryptPrivateKey(string $encrypted): string {     $config = $this->configFactory->get('jaraba_credentials.settings');     $masterKey = base64_decode($config->get('master_encryption_key'));     $decoded = base64_decode($encrypted);     $nonce = substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);     $ciphertext = substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);     return sodium_crypto_secretbox_open($ciphertext, $nonce, $masterKey);   } }
 
4.3 OpenBadgeBuilder.php
Constructor del JSON-LD según especificación Open Badges 3.0.
<?php namespace Drupal\jaraba_credentials\Service;  use Drupal\Core\Entity\EntityTypeManagerInterface; use Drupal\Component\Datetime\TimeInterface; use Drupal\Core\Routing\UrlGeneratorInterface;  class OpenBadgeBuilder {      const OB3_CONTEXT = [     'https://www.w3.org/2018/credentials/v1',     'https://purl.imsglobal.org/spec/ob/v3p0/context-3.0.2.json'   ];    public function __construct(     protected EntityTypeManagerInterface $entityTypeManager,     protected TimeInterface $time,     protected UrlGeneratorInterface $urlGenerator   ) {}    /**    * Construye el JSON-LD completo para una credencial.    */   public function build(     object $template,     object $issuer,     object $recipient,     array $evidence = []   ): array {     $credentialId = $this->generateCredentialUrl($template->uuid->value);     $issuanceDate = date('c', $this->time->getRequestTime());     $expirationDate = $this->calculateExpiration($template);      $credential = [       '@context' => self::OB3_CONTEXT,       'id' => $credentialId,       'type' => ['VerifiableCredential', 'OpenBadgeCredential'],       'issuer' => $this->buildIssuerProfile($issuer),       'issuanceDate' => $issuanceDate,       'credentialSubject' => [         'id' => 'did:email:' . $recipient->getEmail(),         'type' => 'AchievementSubject',         'achievement' => $this->buildAchievement($template),       ],     ];      if ($expirationDate) {       $credential['expirationDate'] = $expirationDate;     }      if (!empty($evidence)) {       $credential['evidence'] = $this->buildEvidence($evidence);     }      return $credential;   }    protected function buildIssuerProfile(object $issuer): array {     return [       'id' => $issuer->url->value,       'type' => 'Profile',       'name' => $issuer->name->value,       'url' => $issuer->url->value,       'email' => $issuer->email->value,       'image' => $this->getFileUrl($issuer->image_fid->target_id),     ];   }    protected function buildAchievement(object $template): array {     return [       'id' => $this->urlGenerator->generateFromRoute(         'jaraba_credentials.achievement',         ['uuid' => $template->uuid->value],         ['absolute' => TRUE]       ),       'type' => 'Achievement',       'name' => $template->name->value,       'description' => $template->description->value,       'criteria' => [         'narrative' => strip_tags($template->criteria_html->value),       ],       'image' => $this->getFileUrl($template->image_fid->target_id),     ];   }    protected function buildEvidence(array $evidence): array {     return array_map(fn($e) => [       'type' => 'Evidence',       'id' => $e['id'] ?? uniqid('evidence-'),       'name' => $e['name'],       'description' => $e['description'] ?? '',     ], $evidence);   } }
 
4.4 CredentialIssuer.php
Servicio principal de emisión de credenciales.
<?php namespace Drupal\jaraba_credentials\Service;  use Drupal\Core\Entity\EntityTypeManagerInterface; use Symfony\Component\EventDispatcher\EventDispatcherInterface; use Psr\Log\LoggerInterface; use Drupal\jaraba_credentials\Event\CredentialIssuedEvent;  class CredentialIssuer {    public function __construct(     protected EntityTypeManagerInterface $entityTypeManager,     protected CryptographyService $crypto,     protected OpenBadgeBuilder $badgeBuilder,     protected PdfCertificateGenerator $pdfGenerator,     protected EventDispatcherInterface $eventDispatcher,     protected LoggerInterface $logger   ) {}    /**    * Emite una credencial a un usuario.    */   public function issue(     int $templateId,     int $recipientUid,     array $evidence = []   ): ?object {          // 1. Cargar template e issuer     $template = $this->entityTypeManager       ->getStorage('credential_template')       ->load($templateId);          if (!$template || !$template->status->value) {       $this->logger->error('Template @id not found or inactive', ['@id' => $templateId]);       return NULL;     }      $issuer = $this->entityTypeManager       ->getStorage('issuer_profile')       ->load($template->issuer_id->target_id);      $recipient = $this->entityTypeManager       ->getStorage('user')       ->load($recipientUid);      // 2. Verificar duplicados     if ($this->hasDuplicate($templateId, $recipientUid)) {       $this->logger->warning('Duplicate credential prevented');       return NULL;     }      // 3. Construir JSON-LD     $jsonLd = $this->badgeBuilder->build($template, $issuer, $recipient, $evidence);      // 4. Firmar credencial     $privateKey = $this->crypto->decryptPrivateKey($issuer->private_key_encrypted->value);     $dataToSign = json_encode($jsonLd, JSON_UNESCAPED_SLASHES);     $signature = $this->crypto->sign($dataToSign, $privateKey);      // 5. Añadir proof al JSON-LD     $jsonLd['proof'] = [       'type' => 'Ed25519Signature2020',       'created' => $jsonLd['issuanceDate'],       'verificationMethod' => $issuer->url->value . '#key-1',       'proofPurpose' => 'assertionMethod',       'proofValue' => $signature,     ];      // 6. Crear entidad issued_credential     $credential = $this->entityTypeManager       ->getStorage('issued_credential')       ->create([         'template_id' => $templateId,         'recipient_uid' => $recipientUid,         'recipient_did' => 'did:email:' . $recipient->getEmail(),         'issued_at' => date('Y-m-d\TH:i:s'),         'expires_at' => $this->calculateExpiration($template),         'status' => 'active',         'evidence_json' => json_encode($evidence),         'json_ld' => json_encode($jsonLd, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),         'signature' => $signature,       ]);          $credential->save();      // 7. Generar PDF     $pdfFid = $this->pdfGenerator->generate($credential);     $credential->set('pdf_fid', $pdfFid);     $credential->save();      // 8. Disparar evento     $event = new CredentialIssuedEvent($credential, $template, $recipient);     $this->eventDispatcher->dispatch($event, CredentialIssuedEvent::EVENT_NAME);      $this->logger->info('Credential @uuid issued to user @uid', [       '@uuid' => $credential->uuid->value,       '@uid' => $recipientUid,     ]);      return $credential;   }    protected function hasDuplicate(int $templateId, int $uid): bool {     $existing = $this->entityTypeManager       ->getStorage('issued_credential')       ->loadByProperties([         'template_id' => $templateId,         'recipient_uid' => $uid,         'status' => 'active',       ]);     return !empty($existing);   } }
 
5. APIs REST
5.1 Endpoints Públicos (Sin Auth)
Método	Endpoint	Descripción
GET	/verify/{uuid}	Verificar credencial (HTML o JSON)
GET	/verify/{uuid}.json	JSON-LD completo de la credencial
GET	/badges/{uuid}/image	Imagen PNG del badge
GET	/achievements/{uuid}	Página de criterios del achievement
GET	/issuers/{uuid}	Perfil público del issuer
5.2 Endpoints Autenticados
Método	Endpoint	Descripción
GET	/api/v1/credentials/my	Mis credenciales
GET	/api/v1/credentials/{uuid}	Detalle de credencial propia
GET	/api/v1/credentials/{uuid}/pdf	Descargar certificado PDF
POST	/api/v1/credentials/{uuid}/share/linkedin	Compartir en LinkedIn
GET	/api/v1/credential-templates	Listar templates disponibles
POST	/api/v1/credentials/issue	Emitir credencial (admin)
POST	/api/v1/credentials/bulk-issue	Emisión masiva (admin)
POST	/api/v1/credentials/{uuid}/revoke	Revocar credencial (admin)
5.3 Archivo jaraba_credentials.routing.yml
# Public verification jaraba_credentials.verify:   path: '/verify/{uuid}'   defaults:     _controller: '\Drupal\jaraba_credentials\Controller\CredentialVerifyController::verify'   requirements:     _access: 'TRUE'     uuid: '[a-f0-9-]{36}'   options:     parameters:       uuid:         type: 'string'  jaraba_credentials.verify_json:   path: '/verify/{uuid}.json'   defaults:     _controller: '\Drupal\jaraba_credentials\Controller\CredentialVerifyController::verifyJson'   requirements:     _access: 'TRUE'  # Badge image jaraba_credentials.badge_image:   path: '/badges/{uuid}/image'   defaults:     _controller: '\Drupal\jaraba_credentials\Controller\BadgeImageController::image'   requirements:     _access: 'TRUE'  # My credentials jaraba_credentials.my_credentials:   path: '/api/v1/credentials/my'   defaults:     _controller: '\Drupal\jaraba_credentials\Controller\CredentialApiController::myCredentials'   requirements:     _permission: 'view own credentials'   methods: [GET]  # Issue credential (admin) jaraba_credentials.issue:   path: '/api/v1/credentials/issue'   defaults:     _controller: '\Drupal\jaraba_credentials\Controller\CredentialApiController::issue'   requirements:     _permission: 'issue credentials'   methods: [POST]  # Revoke credential (admin) jaraba_credentials.revoke:   path: '/api/v1/credentials/{uuid}/revoke'   defaults:     _controller: '\Drupal\jaraba_credentials\Controller\CredentialApiController::revoke'   requirements:     _permission: 'revoke credentials'   methods: [POST]
 
6. Automatizaciones ECA
6.1 ECA-CRED-001: Auto-Issue por Curso Completado
Trigger: enrollment.status cambia a 'completed'
id: eca_credential_course_complete label: 'Auto-issue credential on course completion' events:   - plugin: 'entity:presave'     configuration:       entity_type: enrollment  conditions:   - plugin: 'entity_field_value'     configuration:       field_name: status       value: completed          - plugin: 'eca_custom_condition'     configuration:       php: |         // Verificar que el template existe para este curso         $course_id = $entity->course_id->target_id;         $templates = \Drupal::entityTypeManager()           ->getStorage('credential_template')           ->loadByProperties([             'trigger_config.entity_type' => 'course',             'trigger_config.entity_id' => $course_id,             'status' => 1,           ]);         return !empty($templates);  actions:   - plugin: 'eca_issue_credential'     configuration:       template_lookup: 'by_course'       course_field: 'course_id'       recipient_field: 'user_id'       evidence:         - score: '[enrollment:score]'         - completed_at: '[enrollment:completed_at]'         - time_spent: '[enrollment:total_time_spent]'
6.2 ECA-CRED-002: Auto-Issue por Learning Path
Trigger: user_learning_path.status cambia a 'completed'
id: eca_credential_path_complete label: 'Auto-issue certificate on learning path completion' events:   - plugin: 'entity:presave'     configuration:       entity_type: user_learning_path  conditions:   - plugin: 'entity_field_value'     configuration:       field_name: status       value: completed        actions:   - plugin: 'eca_issue_credential'     configuration:       template_lookup: 'by_learning_path'       path_field: 'learning_path_id'       recipient_field: 'user_id'       evidence:         - overall_score: '[user_learning_path:average_score]'         - courses_completed: '[user_learning_path:courses_count]'         - total_hours: '[user_learning_path:total_time_hours]'
6.3 ECA-CRED-003: Notificación Post-Emisión
id: eca_credential_notification label: 'Send notification after credential issued' events:   - plugin: 'jaraba_credentials.credential_issued'  actions:   - plugin: 'eca_send_email'     configuration:       to: '[credential:recipient:mail]'       subject: '🎉 ¡Has obtenido una nueva credencial!'       body: |         Hola [credential:recipient:display_name],                  ¡Enhorabuena! Has obtenido la credencial:         [credential:template:name]                  Puedes verificarla y descargarla en:         [credential:verify_url]                  Compártela en LinkedIn:         [credential:linkedin_share_url]    - plugin: 'eca_webhook'     configuration:       url: '[site:activecampaign_webhook_url]'       method: POST       body:         email: '[credential:recipient:mail]'         tag: 'credential_[credential:template:machine_name]'
 
7. Sistema de Verificación
7.1 CredentialVerifyController.php
<?php namespace Drupal\jaraba_credentials\Controller;  use Drupal\Core\Controller\ControllerBase; use Symfony\Component\HttpFoundation\JsonResponse; use Symfony\Component\HttpFoundation\Response;  class CredentialVerifyController extends ControllerBase {    public function verify(string $uuid) {     $verifier = \Drupal::service('jaraba_credentials.verifier');     $result = $verifier->verify($uuid);          if (!$result['valid']) {       return [         '#theme' => 'credential_verify_invalid',         '#reason' => $result['reason'],       ];     }      // Incrementar contador de verificaciones     $credential = $result['credential'];     $credential->set('verification_count', $credential->verification_count->value + 1);     $credential->set('last_verified_at', date('Y-m-d\TH:i:s'));     $credential->save();      return [       '#theme' => 'credential_verify_page',       '#credential' => $credential,       '#template' => $result['template'],       '#issuer' => $result['issuer'],       '#recipient' => $result['recipient'],       '#signature_valid' => $result['signature_valid'],     ];   }    public function verifyJson(string $uuid): JsonResponse {     $verifier = \Drupal::service('jaraba_credentials.verifier');     $result = $verifier->verify($uuid);          if (!$result['valid']) {       return new JsonResponse([         'valid' => FALSE,         'reason' => $result['reason'],       ], 404);     }      $jsonLd = json_decode($result['credential']->json_ld->value, TRUE);     return new JsonResponse($jsonLd, 200, [       'Content-Type' => 'application/ld+json',     ]);   } }
7.2 CredentialVerifier.php
<?php namespace Drupal\jaraba_credentials\Service;  class CredentialVerifier {    public function verify(string $uuid): array {     // 1. Buscar credencial     $credentials = $this->entityTypeManager       ->getStorage('issued_credential')       ->loadByProperties(['uuid' => $uuid]);          if (empty($credentials)) {       return ['valid' => FALSE, 'reason' => 'Credential not found'];     }      $credential = reset($credentials);      // 2. Verificar estado     if ($credential->status->value === 'revoked') {       return ['valid' => FALSE, 'reason' => 'Credential has been revoked'];     }      if ($credential->status->value === 'expired' ||          ($credential->expires_at->value && strtotime($credential->expires_at->value) < time())) {       return ['valid' => FALSE, 'reason' => 'Credential has expired'];     }      // 3. Cargar template e issuer     $template = $this->entityTypeManager       ->getStorage('credential_template')       ->load($credential->template_id->target_id);      $issuer = $this->entityTypeManager       ->getStorage('issuer_profile')       ->load($template->issuer_id->target_id);      // 4. Verificar firma criptográfica     $jsonLd = json_decode($credential->json_ld->value, TRUE);     unset($jsonLd['proof']); // Remove proof for verification     $dataToVerify = json_encode($jsonLd, JSON_UNESCAPED_SLASHES);          $signatureValid = $this->crypto->verify(       $dataToVerify,       $credential->signature->value,       $issuer->public_key->value     );      $recipient = $this->entityTypeManager       ->getStorage('user')       ->load($credential->recipient_uid->target_id);      return [       'valid' => TRUE,       'signature_valid' => $signatureValid,       'credential' => $credential,       'template' => $template,       'issuer' => $issuer,       'recipient' => $recipient,     ];   } }
 
8. Permisos y Roles
8.1 jaraba_credentials.permissions.yml
# Permisos de usuario view own credentials:   title: 'View own credentials'   description: 'Allow users to view their earned credentials'  download own certificates:   title: 'Download own certificates'   description: 'Allow users to download PDF certificates'  share credentials:   title: 'Share credentials'   description: 'Allow users to share credentials on social media'  # Permisos de administración administer credentials:   title: 'Administer credentials system'   description: 'Full access to credential configuration'   restrict access: TRUE  manage credential templates:   title: 'Manage credential templates'   description: 'Create, edit, delete credential templates'  issue credentials:   title: 'Issue credentials'   description: 'Manually issue credentials to users'  revoke credentials:   title: 'Revoke credentials'   description: 'Revoke issued credentials'  view credential reports:   title: 'View credential reports'   description: 'Access credential analytics and reports'  manage issuer profiles:   title: 'Manage issuer profiles'   description: 'Configure issuer profiles for the tenant'
8.2 Matriz de Roles
Permiso	Usuario	Gestor	Admin	Super
view own credentials	✓	✓	✓	✓
download own certificates	✓	✓	✓	✓
share credentials	✓	✓	✓	✓
manage credential templates		✓	✓	✓
issue credentials		✓	✓	✓
revoke credentials			✓	✓
view credential reports		✓	✓	✓
manage issuer profiles			✓	✓
administer credentials				✓
 
9. Roadmap de Implementación
Sprint	Timeline	Entregables	Horas
Sprint 1	Semana 1-2	Entidades: issuer_profile, credential_template. Migrations. Schema config.	80h
Sprint 2	Semana 3-4	Entidades: issued_credential, revocation_entry. CryptographyService con Ed25519.	80h
Sprint 3	Semana 5-6	OpenBadgeBuilder JSON-LD. CredentialIssuer service. Eventos custom.	80h
Sprint 4	Semana 7-8	CredentialVerifier. Endpoints públicos /verify. Página de verificación.	60h
Sprint 5	Semana 9-10	PdfCertificateGenerator con QR. Templates Twig. Estilos CSS.	60h
Sprint 6	Semana 11-12	APIs REST autenticadas. Integración LinkedIn. ECA flows básicos.	80h
Sprint 7	Semana 13-14	ECA flows avanzados (auto-issue). Admin UI. Bulk operations.	60h
Sprint 8	Semana 15-16	Tests unitarios e integración. Documentación. QA. Go-live.	60h
Total estimado: 560 horas de desarrollo
10. Dependencias y Requisitos
10.1 Extensiones PHP Requeridas
•	sodium: Criptografía Ed25519 (incluida en PHP 8.3)
•	json: Procesamiento JSON-LD
•	gd/imagick: Procesamiento de imágenes de badges
10.2 Librerías Composer
{   "require": {     "ml/json-ld": "^1.2",     "tecnickcom/tcpdf": "^6.6",     "endroid/qr-code": "^4.8"   } }
10.3 Módulos Drupal Contrib
•	eca: Events-Conditions-Actions para automatización
•	group: Multi-tenant support
•	token: Tokens para ECA y emails
--- Fin del Documento ---
172_Jaraba_Credentials_Implementation_Guide_v1.docx | Jaraba Impact Platform | Enero 2026

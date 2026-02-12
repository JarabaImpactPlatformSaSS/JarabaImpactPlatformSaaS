BUZÓN DE CONFIANZA
Sistema de Custodia Documental Cifrada
Cifrado AES-256-GCM + Control de Acceso Granular
Vertical ServiciosConecta - JARABA IMPACT PLATFORM
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	88_ServiciosConecta_Buzon_Confianza
Dependencias:	82_Services_Core, 89_Firma_Digital_PAdES
Prioridad:	CRÍTICA - Componente exclusivo diferenciador
Compliance:	RGPD, LOPD-GDD, eIDAS, secreto profesional
 
1. Resumen Ejecutivo
El Buzón de Confianza es un sistema de custodia documental cifrada diseñado específicamente para profesionales que manejan información sensible: abogados con expedientes de clientes, médicos con historiales clínicos, asesores fiscales con documentación financiera, y consultores con información estratégica empresarial.
A diferencia de soluciones genéricas de almacenamiento en la nube, el Buzón de Confianza implementa cifrado de conocimiento cero (zero-knowledge encryption), donde ni siquiera los administradores de la plataforma pueden acceder al contenido de los documentos. Cada documento se cifra con una clave derivada única, y el acceso se controla mediante permisos granulares con trazabilidad completa.
1.1 Problema que Resuelve
Los profesionales liberales enfrentan varios desafíos con la documentación sensible:
•	Email inseguro: Envían contratos, informes médicos y documentos fiscales por email sin cifrar
•	Falta de trazabilidad: No saben si el cliente abrió el documento ni cuándo
•	Versionado manual: Múltiples versiones de contratos sin control de cambios
•	Cumplimiento normativo: Dificultad para demostrar compliance con RGPD y secreto profesional
•	Documentos huérfanos: Archivos dispersos en emails, WhatsApp, Google Drive sin organización
1.2 Objetivos del Sistema
•	Cifrado extremo a extremo: AES-256-GCM con claves derivadas por documento
•	Zero-knowledge: La plataforma no puede descifrar documentos sin la clave del propietario
•	Control de acceso granular: Permisos por documento, con expiración y límite de descargas
•	Trazabilidad completa: Audit log inmutable de todas las operaciones (quién, cuándo, qué)
•	Versionado automático: Historial de versiones con diff y rollback
•	Integración con firma digital: Flujo seamless hacia firma electrónica (doc 89)
•	Compliance automático: Generación de informes para auditorías RGPD
1.3 Diferenciadores vs. Soluciones Existentes
Característica	Google Drive	Dropbox	Buzón de Confianza
Cifrado	En tránsito + reposo	En tránsito + reposo	E2E zero-knowledge (AES-256-GCM)
Acceso plataforma	Google puede leer	Dropbox puede leer	Imposible sin clave usuario
Permisos granulares	Básico (ver/editar)	Básico	Ver/Descargar/Firmar + expiración
Límite descargas	No	No	Configurable por documento
Audit log inmutable	Básico	Solo Enterprise	Completo + exportable
Firma digital integrada	No	HelloSign (separado)	AutoFirma/eIDAS nativo
Vinculado a citas	No	No	Contexto de booking/caso
Multi-tenant	Workspace	Teams	Aislamiento criptográfico

 
2. Arquitectura de Seguridad
2.1 Modelo de Cifrado Zero-Knowledge
┌─────────────────────────────────────────────────────────────────────────┐
│                    JERARQUÍA DE CLAVES                                  │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│                    ┌─────────────────────┐                              │
│                    │   Master Key (MK)   │  ← Derivada de password      │
│                    │   del profesional   │    usuario via Argon2id      │
│                    └──────────┬──────────┘                              │
│                               │                                         │
│              ┌────────────────┼────────────────┐                        │
│              │                │                │                        │
│              ▼                ▼                ▼                        │
│     ┌─────────────┐  ┌─────────────┐  ┌─────────────┐                   │
│     │    DEK 1    │  │    DEK 2    │  │    DEK 3    │  Data Encryption  │
│     │  (Doc A)    │  │  (Doc B)    │  │  (Doc C)    │  Keys (únicas)    │
│     └──────┬──────┘  └──────┬──────┘  └──────┬──────┘                   │
│            │                │                │                          │
│            ▼                ▼                ▼                          │
│     ┌─────────────┐  ┌─────────────┐  ┌─────────────┐                   │
│     │ Documento A │  │ Documento B │  │ Documento C │  Datos cifrados   │
│     │ (cifrado)   │  │ (cifrado)   │  │ (cifrado)   │  en storage       │
│     └─────────────┘  └─────────────┘  └─────────────┘                   │
└─────────────────────────────────────────────────────────────────────────┘
2.2 Algoritmos Criptográficos
Componente	Algoritmo	Parámetros
Derivación de MK	Argon2id	memory=64MB, iterations=3, parallelism=4, output=32 bytes
Cifrado de documentos	AES-256-GCM	IV=12 bytes random, tag=16 bytes
DEK wrapping	AES-256-KW (RFC 3394)	DEK cifrada con MK del propietario
Compartición de claves	RSA-OAEP	RSA-2048 + SHA-256 para compartir DEK con otros usuarios
Hash de archivos	SHA-256	Integridad y deduplicación
Generación de claves	CSPRNG	random_bytes() de PHP con /dev/urandom

2.3 Flujo de Cifrado al Subir Documento
1.	Usuario sube archivo desde el navegador
2.	Cliente (JS) genera DEK aleatoria de 256 bits
3.	Cliente cifra archivo con DEK usando AES-256-GCM (en WebCrypto)
4.	Cliente cifra DEK con Master Key del usuario (wrapping)
5.	Cliente envía: archivo_cifrado + DEK_wrapped + IV + metadata
6.	Servidor almacena todo sin poder descifrar (no tiene MK)
7.	Se registra operación en audit log inmutable
 
3. Modelo de Datos
3.1 Entidad: secure_document
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único público	UNIQUE, NOT NULL, INDEX
owner_id	INT	Profesional propietario	FK provider_profile.id, NOT NULL
tenant_id	INT	Tenant del marketplace	FK tenant.id, NOT NULL, INDEX
case_id	INT	Caso/expediente asociado	FK case.id, NULLABLE, INDEX
booking_id	INT	Cita asociada (si aplica)	FK booking.id, NULLABLE
title	VARCHAR(255)	Nombre del documento	NOT NULL
description	TEXT	Descripción opcional	NULLABLE
original_filename	VARCHAR(255)	Nombre archivo original	NOT NULL
mime_type	VARCHAR(128)	Tipo MIME	NOT NULL
file_size	BIGINT	Tamaño en bytes (cifrado)	NOT NULL
storage_path	VARCHAR(512)	Ruta en storage cifrado	NOT NULL
content_hash	VARCHAR(64)	SHA-256 del contenido original	NOT NULL, INDEX
encrypted_dek	TEXT	DEK cifrada con MK del owner	NOT NULL
encryption_iv	VARCHAR(32)	IV usado para AES-GCM (hex)	NOT NULL
encryption_tag	VARCHAR(32)	Auth tag de GCM (hex)	NOT NULL
category_tid	INT	Categoría de documento	FK taxonomy_term.tid, NULLABLE
version	INT	Versión del documento	DEFAULT 1
parent_version_id	INT	Versión anterior	FK secure_document.id, NULLABLE
is_signed	BOOLEAN	Tiene firma digital	DEFAULT FALSE
signature_id	INT	Firma asociada	FK digital_signature.id, NULLABLE
expires_at	DATETIME	Expiración automática	NULLABLE
status	VARCHAR(16)	Estado	ENUM: draft|active|archived|deleted
created	DATETIME	Fecha creación	NOT NULL
changed	DATETIME	Última modificación	NOT NULL

 
3.2 Entidad: document_access
Permisos de acceso compartidos con otros usuarios (clientes u otros profesionales).
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
document_id	INT	Documento	FK secure_document.id, NOT NULL, INDEX
grantee_id	INT	Usuario con acceso	FK users.uid, NULLABLE (si link)
grantee_email	VARCHAR(255)	Email invitado (sin cuenta)	NULLABLE
access_token	VARCHAR(64)	Token único de acceso	UNIQUE, NOT NULL, INDEX
encrypted_dek	TEXT	DEK cifrada para este usuario	NOT NULL (RSA-OAEP)
permissions	JSON	Permisos concedidos	["view", "download", "sign"]
max_downloads	INT	Límite de descargas	NULLABLE (NULL = ilimitado)
download_count	INT	Descargas realizadas	DEFAULT 0
expires_at	DATETIME	Expiración del acceso	NULLABLE (NULL = no expira)
requires_auth	BOOLEAN	Requiere login para acceder	DEFAULT TRUE
access_password	VARCHAR(255)	Password adicional (hash)	NULLABLE
is_revoked	BOOLEAN	Acceso revocado	DEFAULT FALSE
revoked_at	DATETIME	Fecha de revocación	NULLABLE
granted_by	INT	Quién concedió acceso	FK users.uid, NOT NULL
created	DATETIME	Fecha creación	NOT NULL

3.3 Entidad: document_audit_log
Registro inmutable de todas las operaciones sobre documentos. Append-only, nunca se modifica ni elimina.
Campo	Tipo	Descripción	Restricciones
id	BIGSERIAL	ID autoincremental	PRIMARY KEY
document_id	INT	Documento afectado	FK secure_document.id, NOT NULL, INDEX
action	VARCHAR(32)	Tipo de acción	ENUM: created|viewed|downloaded|shared|signed|revoked|deleted
actor_id	INT	Usuario que realizó acción	FK users.uid, NULLABLE (sistema)
actor_ip	VARCHAR(45)	IP del actor (IPv4/IPv6)	NOT NULL
actor_user_agent	VARCHAR(512)	User-Agent del navegador	NULLABLE
details	JSON	Detalles adicionales	NULLABLE
created	DATETIME(6)	Timestamp con microsegundos	NOT NULL, INDEX
hash_chain	VARCHAR(64)	SHA-256(prev_hash + this_record)	NOT NULL (blockchain-like)

El campo hash_chain crea una cadena de integridad: cada registro incluye el hash del registro anterior. Cualquier modificación rompe la cadena y es detectable.
 
4. Servicios Principales
4.1 DocumentVaultService
<?php namespace Drupal\jaraba_vault\Service;

class DocumentVaultService {
  
  public function store(
    int $ownerId,
    UploadedFile $file,
    string $encryptedDek,
    string $iv,
    string $authTag,
    array $metadata
  ): SecureDocument {
    // Validar que el archivo viene ya cifrado del cliente
    if ($file->getSize() < 32) {
      throw new InvalidEncryptedFileException('File too small to be encrypted');
    }
    
    // Generar path de almacenamiento
    $storagePath = $this->generateStoragePath($ownerId, $file);
    
    // Mover archivo cifrado a storage seguro
    $this->fileSystem->move($file->getRealPath(), $storagePath);
    
    // Calcular hash del contenido cifrado (para integridad)
    $contentHash = hash_file('sha256', $storagePath);
    
    // Crear registro en base de datos
    $document = $this->repository->create([
      'owner_id' => $ownerId,
      'tenant_id' => $this->getTenantId($ownerId),
      'title' => $metadata['title'],
      'original_filename' => $metadata['original_filename'],
      'mime_type' => $metadata['mime_type'],
      'file_size' => $file->getSize(),
      'storage_path' => $storagePath,
      'content_hash' => $contentHash,
      'encrypted_dek' => $encryptedDek,
      'encryption_iv' => $iv,
      'encryption_tag' => $authTag,
      'status' => 'active',
    ]);
    
    // Registrar en audit log
    $this->auditService->log($document, 'created', [
      'original_size' => $metadata['original_size'],
      'encrypted_size' => $file->getSize(),
    ]);
    
    return $document;
  }
  
  public function retrieve(int $documentId, int $userId): EncryptedDocument {
    $document = $this->repository->find($documentId);
    
    // Verificar acceso
    $access = $this->accessService->checkAccess($documentId, $userId);
    if (!$access->canView()) {
      throw new AccessDeniedException('No access to this document');
    }
    
    // Registrar acceso en audit log
    $this->auditService->log($document, 'viewed');
    
    // Obtener DEK cifrada para este usuario
    $encryptedDek = $access->isOwner()
      ? $document->getEncryptedDek()
      : $access->getEncryptedDek();
    
    // Devolver archivo cifrado + metadatos para descifrado en cliente
    return new EncryptedDocument(
      content: $this->fileSystem->read($document->getStoragePath()),
      encryptedDek: $encryptedDek,
      iv: $document->getEncryptionIv(),
      authTag: $document->getEncryptionTag(),
      metadata: $document->getMetadata()
    );
  }
}

 
4.2 DocumentAccessService
<?php namespace Drupal\jaraba_vault\Service;

class DocumentAccessService {
  
  public function shareDocument(
    int $documentId,
    string $recipientEmail,
    string $recipientPublicKey, // RSA-2048 public key del destinatario
    string $dekForRecipient,    // DEK re-cifrada con public key del destinatario
    array $permissions,
    ?DateTime $expiresAt = null,
    ?int $maxDownloads = null
  ): DocumentAccess {
    $document = $this->repository->find($documentId);
    
    // Verificar que quien comparte es propietario o tiene permiso de compartir
    $currentUser = $this->currentUser->id();
    if (!$this->canShare($documentId, $currentUser)) {
      throw new AccessDeniedException('Cannot share this document');
    }
    
    // Buscar si el destinatario ya tiene cuenta
    $recipient = $this->userRepository->findByEmail($recipientEmail);
    
    // Generar token único de acceso
    $accessToken = bin2hex(random_bytes(32));
    
    $access = $this->accessRepository->create([
      'document_id' => $documentId,
      'grantee_id' => $recipient?->id(),
      'grantee_email' => $recipientEmail,
      'access_token' => $accessToken,
      'encrypted_dek' => $dekForRecipient,
      'permissions' => $permissions,
      'max_downloads' => $maxDownloads,
      'expires_at' => $expiresAt,
      'granted_by' => $currentUser,
    ]);
    
    // Registrar en audit log
    $this->auditService->log($document, 'shared', [
      'recipient' => $recipientEmail,
      'permissions' => $permissions,
      'expires_at' => $expiresAt?->format('c'),
    ]);
    
    // Enviar notificación al destinatario
    $this->notificationService->sendDocumentShared(
      $recipientEmail,
      $document,
      $accessToken
    );
    
    return $access;
  }
  
  public function revokeAccess(int $accessId): void {
    $access = $this->accessRepository->find($accessId);
    
    $access->setIsRevoked(true);
    $access->setRevokedAt(new DateTime());
    
    $this->accessRepository->save($access);
    
    // Registrar en audit log
    $this->auditService->log($access->getDocument(), 'revoked', [
      'revoked_user' => $access->getGranteeEmail(),
    ]);
  }
}

 
4.3 AuditLogService (Inmutable)
<?php namespace Drupal\jaraba_vault\Service;

class AuditLogService {
  
  public function log(
    SecureDocument $document,
    string $action,
    array $details = []
  ): DocumentAuditLog {
    // Obtener último hash de la cadena
    $lastLog = $this->repository->findLast($document->id());
    $prevHash = $lastLog ? $lastLog->getHashChain() : str_repeat('0', 64);
    
    // Construir registro
    $entry = [
      'document_id' => $document->id(),
      'action' => $action,
      'actor_id' => $this->currentUser->id(),
      'actor_ip' => $this->request->getClientIp(),
      'actor_user_agent' => $this->request->headers->get('User-Agent'),
      'details' => $details,
      'created' => (new DateTime())->format('Y-m-d H:i:s.u'),
    ];
    
    // Calcular hash de la cadena (incluye hash anterior)
    $dataToHash = $prevHash . json_encode($entry);
    $entry['hash_chain'] = hash('sha256', $dataToHash);
    
    // Insertar (nunca update ni delete)
    return $this->repository->insert($entry);
  }
  
  public function verifyIntegrity(int $documentId): IntegrityReport {
    $logs = $this->repository->findAllForDocument($documentId);
    $prevHash = str_repeat('0', 64);
    $valid = true;
    $brokenAt = null;
    
    foreach ($logs as $log) {
      $entry = [
        'document_id' => $log->getDocumentId(),
        'action' => $log->getAction(),
        'actor_id' => $log->getActorId(),
        'actor_ip' => $log->getActorIp(),
        'actor_user_agent' => $log->getActorUserAgent(),
        'details' => $log->getDetails(),
        'created' => $log->getCreated()->format('Y-m-d H:i:s.u'),
      ];
      
      $expectedHash = hash('sha256', $prevHash . json_encode($entry));
      
      if ($expectedHash !== $log->getHashChain()) {
        $valid = false;
        $brokenAt = $log->id();
        break;
      }
      
      $prevHash = $log->getHashChain();
    }
    
    return new IntegrityReport($valid, count($logs), $brokenAt);
  }
}

 
5. Cliente JavaScript (Cifrado en Navegador)
El cifrado y descifrado se realiza enteramente en el navegador usando Web Crypto API, garantizando que el servidor nunca ve datos en claro.
5.1 VaultClient (Web Crypto API)
// jaraba-vault-client.js

class VaultClient {
  
  constructor(masterKey) {
    this.masterKey = masterKey; // CryptoKey derivada de password
  }
  
  static async deriveMasterKey(password, salt) {
    // Usar Argon2id via WebAssembly (argon2-browser)
    const hash = await argon2.hash({
      pass: password,
      salt: salt,
      time: 3,
      mem: 65536, // 64 MB
      parallelism: 4,
      hashLen: 32,
      type: argon2.ArgonType.Argon2id
    });
    
    // Importar como CryptoKey para AES-KW
    return await crypto.subtle.importKey(
      'raw',
      hash.hash,
      { name: 'AES-KW' },
      false,
      ['wrapKey', 'unwrapKey']
    );
  }
  
  async encryptFile(file) {
    // 1. Generar DEK aleatoria
    const dek = await crypto.subtle.generateKey(
      { name: 'AES-GCM', length: 256 },
      true, // extractable para wrapping
      ['encrypt', 'decrypt']
    );
    
    // 2. Generar IV aleatorio
    const iv = crypto.getRandomValues(new Uint8Array(12));
    
    // 3. Leer archivo
    const fileData = await file.arrayBuffer();
    
    // 4. Cifrar contenido con DEK
    const encrypted = await crypto.subtle.encrypt(
      { name: 'AES-GCM', iv: iv, tagLength: 128 },
      dek,
      fileData
    );
    
    // 5. Wrap DEK con Master Key
    const wrappedDek = await crypto.subtle.wrapKey(
      'raw',
      dek,
      this.masterKey,
      'AES-KW'
    );
    
    return {
      encryptedContent: new Blob([encrypted]),
      wrappedDek: this.arrayBufferToHex(wrappedDek),
      iv: this.arrayBufferToHex(iv),
      // El tag está incluido al final del encrypted en GCM
    };
  }
  
  async decryptFile(encryptedContent, wrappedDek, iv) {
    // 1. Unwrap DEK con Master Key
    const dek = await crypto.subtle.unwrapKey(
      'raw',
      this.hexToArrayBuffer(wrappedDek),
      this.masterKey,
      'AES-KW',
      { name: 'AES-GCM', length: 256 },
      false,
      ['decrypt']
    );
    
    // 2. Descifrar contenido
    const decrypted = await crypto.subtle.decrypt(
      { name: 'AES-GCM', iv: this.hexToArrayBuffer(iv) },
      dek,
      encryptedContent
    );
    
    return decrypted;
  }
}

 
6. APIs REST
Método	Endpoint	Descripción	Auth
POST	/api/v1/vault/documents	Subir documento cifrado	Provider
GET	/api/v1/vault/documents	Listar documentos propios	Provider
GET	/api/v1/vault/documents/{uuid}	Obtener documento cifrado + DEK	Propietario/Acceso
DELETE	/api/v1/vault/documents/{uuid}	Eliminar documento (soft delete)	Propietario
POST	/api/v1/vault/documents/{uuid}/versions	Subir nueva versión	Propietario
GET	/api/v1/vault/documents/{uuid}/versions	Listar versiones	Propietario/Acceso
POST	/api/v1/vault/documents/{uuid}/share	Compartir con otro usuario	Propietario
GET	/api/v1/vault/documents/{uuid}/access	Listar accesos compartidos	Propietario
DELETE	/api/v1/vault/access/{id}	Revocar acceso	Propietario
GET	/api/v1/vault/documents/{uuid}/audit	Ver audit log del documento	Propietario
GET	/api/v1/vault/shared	Documentos compartidos conmigo	Usuario
GET	/api/v1/vault/access/token/{token}	Acceder vía token (link compartido)	Público + token

7. Flujos de Automatización (ECA)
Código	Evento	Acciones
VLT-001	document.shared	Enviar email al destinatario con link seguro de acceso
VLT-002	document.viewed	Notificar al propietario que su documento fue visto (configurable)
VLT-003	document.downloaded	Incrementar contador + verificar límite + notificar si último
VLT-004	access.expiring_soon	Notificar al destinatario 24h antes de expiración del acceso
VLT-005	access.expired	Marcar acceso como revocado + registrar en audit
VLT-006	document.requires_signature	Enviar solicitud de firma al destinatario + crear flujo en doc 89
VLT-007	document.signed	Notificar al propietario + actualizar estado + crear nueva versión firmada
VLT-008	cron.daily	Verificar integridad de audit logs + expirar documentos + generar alertas

 
8. Compliance y RGPD
8.1 Derechos ARCO (Acceso, Rectificación, Cancelación, Oposición)
Derecho	Implementación	Endpoint
Acceso	Exportar todos los documentos propios en ZIP cifrado	GET /api/v1/vault/export
Rectificación	Subir nueva versión que reemplaza anterior	POST /api/v1/vault/documents/{id}/versions
Cancelación	Soft delete + hard delete tras 30 días de retención	DELETE /api/v1/vault/documents/{id}
Oposición	Revocar todos los accesos compartidos de un documento	DELETE /api/v1/vault/documents/{id}/access/all
Portabilidad	Exportar documentos + metadata en formato estándar	GET /api/v1/vault/export?format=json

8.2 Informes de Auditoría Generados
•	Informe de accesos: Quién accedió a qué documentos, cuándo y desde dónde
•	Informe de comparticiones: Documentos compartidos, con quién, permisos y expiración
•	Informe de integridad: Verificación de cadena de hashes del audit log
•	Informe de retención: Documentos próximos a eliminación automática
•	Informe de actividad: Resumen de operaciones por usuario/período (para DPO)
9. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 3.1	Semana 5	Modelo de datos (entidades) + VaultClient JS con Web Crypto	82_Services_Core
Sprint 3.2	Semana 6	DocumentVaultService + DocumentAccessService + APIs básicas	Sprint 3.1
Sprint 3.3	Semana 7	AuditLogService inmutable + verificación de integridad + versionado	Sprint 3.2
Sprint 3.4	Semana 8	UI de gestión documental + informes RGPD + tests E2E	Sprint 3.3

9.1 Criterios de Aceptación
•	✓ Cifrado E2E funcional: servidor no puede descifrar documentos
•	✓ Compartición con permisos granulares (view/download/sign)
•	✓ Límite de descargas y expiración funcionando correctamente
•	✓ Audit log inmutable con verificación de integridad
•	✓ Versionado automático con historial completo
•	✓ Exportación RGPD (derecho de acceso y portabilidad)
•	✓ Tests de seguridad: penetration testing del cifrado

--- Fin del Documento ---

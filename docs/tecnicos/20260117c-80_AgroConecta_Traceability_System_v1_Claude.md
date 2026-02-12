SISTEMA DE TRAZABILIDAD
Trazabilidad Inmutable con Hash Anchoring y Firma Digital
Vertical AgroConecta - JARABA IMPACT PLATFORM

Campo	Valor
Código Documento:	55_AgroConecta_Traceability_System
Versión:	1.0
Fecha:	Enero 2026
Nivel de Madurez:	TML-4 (Inmutable Descentralizado)
Dependencias:	47_Commerce_Core, 52_Producer_Portal
Blockchain:	OpenTimestamps (Bitcoin) + Polygon (EVM)
 
1. Resumen Ejecutivo
Este documento especifica el Sistema de Trazabilidad para AgroConecta, elevando la plataforma al Nivel de Madurez de Trazabilidad 4 (TML-4): Inmutable y Descentralizado. El sistema combina firma digital cualificada con anclaje criptográfico en blockchain para garantizar la integridad probatoria de los datos de origen y producción.
1.1 Niveles de Madurez de Trazabilidad (TML)
Nivel	Nombre	Descripción	AgroConecta
TML-1	Manual	Registros en papel, cuadernos de campo	Superado
TML-2	Digital Básico	Datos en Excel, bases de datos locales	Superado
TML-3	Digital Centralizado	CMS/ERP con datos estructurados, QR básico	Actual (sin blockchain)
TML-4	Inmutable Descentralizado	Hash anchoring, firma digital, verificación pública	OBJETIVO
1.2 Objetivos del Sistema
• Inmutabilidad: Registros que no pueden ser alterados retroactivamente
• Verificabilidad: Cualquier tercero puede comprobar la autenticidad
• Automatización: Certificados generados y firmados sin intervención manual
• Transparencia: Landing pública de trazabilidad accesible vía QR
• Soberanía: Pruebas verificables incluso si AgroConecta desaparece
 
2. Arquitectura del Sistema
2.1 Modelo Híbrido: Off-Chain + On-Chain
El sistema NO almacena todos los datos en blockchain (costoso e ineficiente). Utiliza un modelo de Hash Anchoring donde Drupal es la fuente de datos ricos y blockchain actúa como notario digital.
Capa	Ubicación	Datos	Propósito
Off-Chain	Drupal 11 (MySQL)	Textos, imágenes, PDFs, certificaciones, histórico completo	Fuente de información rica
On-Chain	OpenTimestamps/Polygon	Hash SHA-256 + timestamp	Prueba de integridad inmutable
Firma Digital	Servidor (PKCS#12)	Certificado FNMT + sello de tiempo TSA	Validez legal (eIDAS)
2.2 Flujo de Trazabilidad Completo
1. CREACIÓN DE LOTE    └─ Productor crea Lote de Producción en Drupal    └─ Sistema genera ID único: LOTE-2026-001-XY7Z  2. REGISTRO DE EVENTOS    └─ Cosecha → Procesado → Análisis → Envasado    └─ Cada evento queda registrado con timestamp  3. GENERACIÓN DE CERTIFICADO PDF    └─ CertificadoPdfService genera documento con datos + QR    └─ Incluye: origen, fechas, certificaciones, analíticas  4. FIRMA DIGITAL    └─ FirmaDigitalService firma con certificado FNMT    └─ Añade sello de tiempo (TSA FNMT: tsa.fnmt.es)    └─ Resultado: PDF con validez legal eIDAS  5. HASH ANCHORING (Asíncrono)    └─ IntegrityService calcula SHA-256 del lote completo    └─ Cola de procesamiento envía a OpenTimestamps    └─ Se genera archivo .ots con prueba criptográfica  6. VERIFICACIÓN PÚBLICA    └─ Consumidor escanea QR en botella    └─ Landing muestra datos + verificación de integridad    └─ Puede descargar certificado firmado y archivo .ots
 
3. Modelo de Datos
3.1 Entidad: supply_chain_event
Registra cada evento de la cadena de suministro asociado a un lote. Permite trazar el journey completo: cosecha → procesado → análisis → envasado → expedición.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno autoincremental	PRIMARY KEY
uuid	UUID	Identificador único global	UNIQUE, NOT NULL
lote_id	INT	Lote de producción asociado	FK node.nid (lote_produccion), NOT NULL, INDEX
event_type	VARCHAR(32)	Tipo de evento	ENUM: harvest|processing|analysis|packaging|shipping|delivery
event_date	DATETIME	Fecha/hora del evento	NOT NULL, UTC
location_name	VARCHAR(255)	Nombre del lugar	NOT NULL
location_geo	POINT	Coordenadas GPS	NULLABLE, SRID 4326
description	TEXT	Descripción del evento	NULLABLE
responsible_user	INT	Usuario responsable	FK users.uid, NULLABLE
evidence_files	JSON	Archivos de evidencia (fotos, docs)	NULLABLE, array of fid
temperature	DECIMAL(5,2)	Temperatura registrada (°C)	NULLABLE
humidity	DECIMAL(5,2)	Humedad registrada (%)	NULLABLE
notes	TEXT	Notas adicionales	NULLABLE
hash_sha256	VARCHAR(64)	Hash del evento para integridad	COMPUTED, INDEX
created	DATETIME	Fecha de creación	NOT NULL, UTC
changed	DATETIME	Última modificación	NOT NULL, UTC
3.2 Entidad: integrity_proof
Almacena las pruebas de integridad ancladas en blockchain. Cada lote puede tener múltiples pruebas (inicial + actualizaciones).
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
lote_id	INT	Lote asociado	FK node.nid, NOT NULL, INDEX
data_hash	VARCHAR(64)	Hash SHA-256 de los datos del lote	NOT NULL, INDEX
hash_algorithm	VARCHAR(16)	Algoritmo usado	DEFAULT 'SHA-256'
anchor_type	VARCHAR(32)	Tipo de anclaje	ENUM: opentimestamps|polygon|ebsi
anchor_status	VARCHAR(16)	Estado del anclaje	ENUM: pending|anchoring|confirmed|failed
ots_file	INT	Archivo .ots de OpenTimestamps	FK file_managed.fid, NULLABLE
blockchain_tx_hash	VARCHAR(128)	Hash de transacción blockchain	NULLABLE, INDEX
blockchain_network	VARCHAR(32)	Red blockchain usada	NULLABLE: bitcoin|polygon|ethereum
block_number	BIGINT	Número de bloque	NULLABLE
block_timestamp	DATETIME	Timestamp del bloque	NULLABLE, UTC
verification_url	VARCHAR(512)	URL de verificación externa	NULLABLE
anchored_at	DATETIME	Fecha de anclaje confirmado	NULLABLE, UTC
created	DATETIME	Fecha de creación	NOT NULL, UTC
 
3.3 Entidad: batch_certificate
Certificados PDF firmados digitalmente para cada lote. Incluye metadatos de firma y verificación.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
lote_id	INT	Lote asociado	FK node.nid, NOT NULL, INDEX
certificate_type	VARCHAR(32)	Tipo de certificado	ENUM: traceability|analysis|origin|organic
pdf_file	INT	Archivo PDF sin firmar	FK file_managed.fid, NOT NULL
signed_pdf_file	INT	Archivo PDF firmado	FK file_managed.fid, NULLABLE
signature_status	VARCHAR(16)	Estado de la firma	ENUM: pending|signed|failed|expired
signer_cn	VARCHAR(255)	Common Name del certificado firmante	NULLABLE
signer_serial	VARCHAR(64)	Número de serie del certificado	NULLABLE
signed_at	DATETIME	Fecha de firma	NULLABLE, UTC
tsa_timestamp	DATETIME	Sello de tiempo TSA	NULLABLE, UTC
tsa_url	VARCHAR(255)	URL del servidor TSA usado	NULLABLE
valid_from	DATETIME	Certificado válido desde	NULLABLE
valid_until	DATETIME	Certificado válido hasta	NULLABLE
download_count	INT	Número de descargas	DEFAULT 0
verification_count	INT	Número de verificaciones	DEFAULT 0
public_url	VARCHAR(512)	URL pública de descarga	NULLABLE
created	DATETIME	Fecha de creación	NOT NULL, UTC
 
4. Servicios del Sistema
4.1 IntegrityService
Servicio principal para cálculo de hashes y anclaje en blockchain.
class IntegrityService {    // Calcula hash SHA-256 de un lote completo   public function calculateLoteHash(NodeInterface $lote): string;      // Serializa datos del lote para hashing determinístico   public function serializeLoteData(NodeInterface $lote): string;      // Encola lote para anclaje en blockchain   public function queueForAnchoring(NodeInterface $lote): IntegrityProof;      // Procesa cola de anclaje (llamado por cron)   public function processAnchoringQueue(): array;      // Ancla hash en OpenTimestamps   public function anchorToOpenTimestamps(string $hash): ?string;      // Ancla hash en Polygon (alternativo)   public function anchorToPolygon(string $hash): ?array;      // Verifica integridad de un lote contra su prueba   public function verifyIntegrity(NodeInterface $lote, IntegrityProof $proof): bool;      // Obtiene estado de verificación completo   public function getVerificationStatus(NodeInterface $lote): array; }
4.2 CertificadoPdfService (Existente)
Genera PDFs de certificados de trazabilidad. Ya implementado en el código existente.
class CertificadoPdfService {    // Genera PDF con datos del lote   public function generatePdf(NodeInterface $lote): ?string;      // Extrae datos del lote para el certificado   protected function extraerDatosLote(NodeInterface $lote): array;      // Genera encabezado con logo y título   protected function generarEncabezado(TCPDF $pdf, array $datos): void;      // Genera sección de datos del lote   protected function generarDatosLote(TCPDF $pdf, array $datos): void;      // Genera sección de origen y producción   protected function generarDatosOrigen(TCPDF $pdf, array $datos): void;      // Genera QR de verificación   protected function generarQrVerificacion(TCPDF $pdf, array $datos): void;      // Guarda PDF en sistema de archivos   protected function guardarPdf(TCPDF $pdf, string $lote_id): ?string; }
4.3 FirmaDigitalService (Existente)
Firma PDFs con certificado de persona jurídica y sello de tiempo. Ya implementado.
class FirmaDigitalService {    // Firma un PDF con el certificado del servidor   public function signPdf(string $pdf_uri, array $options = []): ?string;      // Verifica si la firma de un PDF es válida   public function verifySignature(string $pdf_uri): bool;      // Obtiene información del certificado configurado   public function getCertificateInfo(): ?array;      // Obtiene ruta del certificado (env o config)   protected function getCertificatePath(): ?string;      // Obtiene contraseña de forma segura (siempre env)   protected function getCertificatePassword(): string;      // Registra en log de auditoría   protected function logAuditEntry(string $original, string $signed, array $cert_info): void; }
 
4.4 TraceabilityLandingService
Genera la página pública de trazabilidad cuando el consumidor escanea el QR.
class TraceabilityLandingService {    // Carga datos completos de un lote por su ID   public function loadLoteByCode(string $lote_code): ?NodeInterface;      // Obtiene timeline de eventos de la cadena de suministro   public function getSupplyChainTimeline(NodeInterface $lote): array;      // Obtiene datos del productor para mostrar   public function getProducerInfo(NodeInterface $lote): array;      // Obtiene certificaciones del producto   public function getCertifications(NodeInterface $lote): array;      // Obtiene estado de verificación blockchain   public function getBlockchainVerification(NodeInterface $lote): array;      // Obtiene URL del certificado firmado para descarga   public function getCertificateDownloadUrl(NodeInterface $lote): ?string;      // Registra visita a la landing (analytics)   public function trackLandingVisit(NodeInterface $lote, Request $request): void;      // Genera datos estructurados Schema.org para SEO   public function generateSchemaOrg(NodeInterface $lote): array; }
 
5. APIs REST
5.1 Endpoints Públicos (Sin Autenticación)
Método	Endpoint	Descripción
GET	/api/v1/traceability/{lote_code}	Datos públicos del lote para landing
GET	/api/v1/traceability/{lote_code}/timeline	Timeline de eventos de cadena de suministro
GET	/api/v1/traceability/{lote_code}/verify	Verificación de integridad blockchain
GET	/api/v1/traceability/{lote_code}/certificate	Descarga certificado PDF firmado
GET	/api/v1/traceability/{lote_code}/proof	Descarga archivo .ots de OpenTimestamps
5.2 Endpoints Privados (Requieren Autenticación)
Método	Endpoint	Descripción	Scope
POST	/api/v1/traceability/events	Registrar evento de cadena de suministro	traceability:write
GET	/api/v1/traceability/events/{lote_id}	Listar eventos de un lote	traceability:read
POST	/api/v1/traceability/anchor/{lote_id}	Forzar anclaje blockchain	traceability:admin
GET	/api/v1/traceability/proofs/{lote_id}	Listar pruebas de integridad	traceability:read
POST	/api/v1/traceability/certificates/{lote_id}/regenerate	Regenerar certificado	traceability:admin
5.3 Ejemplo: Verificación Pública
GET /api/v1/traceability/LOTE-2026-001-XY7Z/verify  Response 200: {   "lote_code": "LOTE-2026-001-XY7Z",   "product_name": "Aceite de Oliva Virgen Extra - Finca Los Olivos",   "integrity": {     "status": "verified",     "data_hash": "a7f3c2d8e9b1...",     "hash_algorithm": "SHA-256",     "anchored_at": "2026-01-15T10:30:00Z"   },   "blockchain": {     "network": "bitcoin",     "anchor_type": "opentimestamps",     "verification_url": "https://opentimestamps.org/verify/...",     "block_timestamp": "2026-01-15T11:45:00Z"   },   "certificate": {     "status": "signed",     "signer": "AgroConecta SL - Sello de Empresa",     "signed_at": "2026-01-15T10:35:00Z",     "tsa_timestamp": "2026-01-15T10:35:02Z",     "download_url": "/api/v1/traceability/LOTE-2026-001-XY7Z/certificate"   },   "message": "Este lote ha sido verificado. Los datos no han sido alterados desde su registro." }
 
6. Flujos de Automatización (ECA)
6.1 ECA-TRACE-001: Generación Automática de Certificado
Trigger: Creación de nodo lote_produccion con field_id_lote no vacío  Conditions:   - El lote tiene producto asociado   - El lote tiene al menos fecha de cosecha   - Firma automática está habilitada en configuración  Actions:   1. Invocar CertificadoPdfService::generatePdf()   2. Invocar FirmaDigitalService::signPdf()   3. Crear entidad batch_certificate con el PDF firmado   4. Actualizar campo field_certificado_firmado del lote   5. Mostrar mensaje de éxito al productor   6. Log en watchdog: 'Certificado generado para lote {id}'
6.2 ECA-TRACE-002: Encolado para Anclaje Blockchain
Trigger: Creación de batch_certificate con signature_status = 'signed'  Conditions:   - No existe integrity_proof para este lote con status 'confirmed'   - Anclaje blockchain está habilitado en configuración  Actions:   1. Calcular hash SHA-256 del lote: IntegrityService::calculateLoteHash()   2. Crear integrity_proof con anchor_status = 'pending'   3. Añadir item a la cola 'agroconecta_blockchain_anchoring'   4. Log: 'Lote {id} encolado para anclaje blockchain'
6.3 ECA-TRACE-003: Procesamiento de Cola (Cron)
Trigger: Cron cada 15 minutos  Conditions:   - Hay items en cola 'agroconecta_blockchain_anchoring'   - Servidor de OpenTimestamps accesible  Actions:   1. Procesar hasta 50 items de la cola   2. Para cada item:      a. Actualizar integrity_proof.anchor_status = 'anchoring'      b. Llamar IntegrityService::anchorToOpenTimestamps()      c. Si éxito: guardar archivo .ots, actualizar status = 'confirmed'      d. Si fallo: status = 'failed', reintentar en próximo cron   3. Log resumen: 'Procesados {n} anclajes, {ok} exitosos, {fail} fallidos'
6.4 ECA-TRACE-004: Registro de Evento de Cadena de Suministro
Trigger: Creación de supply_chain_event  Conditions:   - El evento tiene lote_id válido   - El tipo de evento es válido  Actions:   1. Calcular hash del evento: SHA256(type + date + location + description)   2. Guardar en supply_chain_event.hash_sha256   3. Si tipo = 'shipping' (último evento):      a. Recalcular hash completo del lote      b. Encolar para nuevo anclaje blockchain (actualización)   4. Notificar al productor si hay webhook configurado
 
7. Landing Pública de Trazabilidad
7.1 URL y Acceso
La landing es accesible públicamente sin autenticación mediante la URL generada en el QR del producto físico.
URL: https://agroconecta.es/trazabilidad/{LOTE-CODE} Ejemplo: https://agroconecta.es/trazabilidad/LOTE-2026-001-XY7Z  El QR en la botella/etiqueta apunta directamente a esta URL.
7.2 Secciones de la Landing
Sección	Contenido	Datos
Header	Logo AgroConecta + Producto + Lote	Imagen producto, nombre, código lote
Verificación	Badge de estado + botón verificar	✓ Verificado en Blockchain, fecha anclaje
Producto	Nombre, descripción, certificaciones	Título, body, badges DO/IGP/Ecológico
Origen	Finca, productor, ubicación	Nombre finca, foto, mapa, coordenadas
Timeline	Eventos de cadena de suministro	Lista cronológica: cosecha → procesado → envasado
Análisis	Resultados de laboratorio	Acidez, peróxidos, polifenoles (si disponibles)
Descarga	Certificado PDF firmado	Botón descarga + info verificación VALIDe
Productor	Historia y valores	Storytelling, galería de fotos, contacto
CTA	Comprar producto	Enlace a ficha de producto en tienda
7.3 Schema.org para SEO/GEO
{   "@context": "https://schema.org",   "@type": "Product",   "name": "Aceite de Oliva Virgen Extra - Finca Los Olivos",   "description": "AOVE de primera presión en frío...",   "sku": "LOTE-2026-001-XY7Z",   "brand": { "@type": "Brand", "name": "Finca Los Olivos" },   "manufacturer": {     "@type": "Organization",     "name": "Finca Los Olivos",     "address": { "@type": "PostalAddress", "addressRegion": "Córdoba", "addressCountry": "ES" }   },   "countryOfOrigin": "ES",   "additionalProperty": [     { "@type": "PropertyValue", "name": "Fecha Cosecha", "value": "2025-11-15" },     { "@type": "PropertyValue", "name": "Variedad", "value": "Picual" },     { "@type": "PropertyValue", "name": "Certificación", "value": "DOP Priego de Córdoba" },     { "@type": "PropertyValue", "name": "Blockchain Verified", "value": "true" }   ] }
 
8. Configuración del Sistema
8.1 Variables de Entorno (Producción)
# Certificado de firma digital AGROCONECTA_CERT_PATH=/etc/ssl/private/agroconecta-sello.p12 AGROCONECTA_CERT_PASSWORD=*****  # OpenTimestamps (opcional, usa calendarios públicos por defecto) OTS_CALENDAR_URL=https://a.pool.opentimestamps.org  # Polygon (alternativo a OTS) POLYGON_RPC_URL=https://polygon-rpc.com POLYGON_PRIVATE_KEY=***** POLYGON_CONTRACT_ADDRESS=0x...
8.2 Configuración Drupal
# agroconecta_core.traceability_settings.yml  # Firma digital firma_enabled: true certificate_path: '' # Usar variable de entorno tsa_url: 'http://tsa.fnmt.es/tsa/tss'  # Blockchain anchoring blockchain_enabled: true anchor_provider: 'opentimestamps' # opentimestamps | polygon anchor_on_create: true # Anclar automáticamente al crear lote anchor_on_update: false # Re-anclar si se modifica  # Landing pública landing_enabled: true landing_base_path: '/trazabilidad' show_analytics: false # Mostrar datos de análisis show_producer_contact: true enable_purchase_cta: true  # Cache cache_landing_ttl: 3600 # 1 hora
 
9. Roadmap de Implementación
Sprint	Semanas	Entregables	Dependencias
Sprint 1	1-2	Entidades supply_chain_event, integrity_proof, batch_certificate. Migraciones.	Commerce Core
Sprint 2	3-4	IntegrityService: cálculo de hashes, serialización, cola de procesamiento.	Sprint 1
Sprint 3	5-6	Integración OpenTimestamps: cliente OTS, procesamiento cron, archivos .ots.	Sprint 2
Sprint 4	7-8	Integración código existente: CertificadoPdfService, FirmaDigitalService.	Sprint 2
Sprint 5	9-10	Landing pública: TraceabilityLandingService, template Twig, Schema.org.	Sprint 3, 4
Sprint 6	11-12	APIs REST públicas y privadas. Testing E2E. Documentación.	Sprint 5
9.1 Criterios de Aceptación Sprint 5 (Landing)
• Consumidor puede escanear QR y ver landing completa
• Timeline muestra todos los eventos de cadena de suministro
• Badge de verificación muestra estado blockchain correcto
• Certificado PDF firmado descargable
• Schema.org válido (test con Google Rich Results)
• Tiempo de carga < 2 segundos
9.2 Estimación de Esfuerzo
Componente	Horas Estimadas	Complejidad
Entidades y migraciones	16-20h	Media
IntegrityService	24-32h	Alta
Integración OpenTimestamps	16-24h	Alta
Integración código existente (firma)	8-12h	Baja
Landing pública + templates	20-28h	Media
APIs REST	12-16h	Media
Testing y documentación	16-20h	Media
TOTAL	112-152h	~3-4 meses a 50%
--- Fin del Documento ---
55_AgroConecta_Traceability_System_v1.docx | Jaraba Impact Platform | Enero 2026

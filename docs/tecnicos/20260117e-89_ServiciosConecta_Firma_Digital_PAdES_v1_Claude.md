FIRMA DIGITAL PAdES
Sistema de Firma Electrónica Avanzada y Cualificada
Integración AutoFirma + Cl@ve + eIDAS
Vertical ServiciosConecta - JARABA IMPACT PLATFORM
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	89_ServiciosConecta_Firma_Digital_PAdES
Dependencias:	82_Services_Core, 88_Buzon_Confianza
Prioridad:	ALTA - Validez legal de documentos
Compliance:	eIDAS (UE 910/2014), Ley 6/2020, ETSI EN 319 142
 
1. Resumen Ejecutivo
El módulo de Firma Digital permite a profesionales y clientes firmar documentos electrónicamente con plena validez legal en España y la Unión Europea. Se integra con AutoFirma (aplicación oficial del Gobierno de España) y el sistema Cl@ve para autenticación, generando firmas PAdES (PDF Advanced Electronic Signatures) conformes al Reglamento eIDAS.
Este componente es especialmente crítico para profesionales regulados: abogados firmando poderes y contratos, médicos firmando informes clínicos, arquitectos visando proyectos, y asesores fiscales firmando declaraciones. La firma electrónica cualificada tiene el mismo valor legal que la firma manuscrita según la legislación europea.
1.1 Tipos de Firma Electrónica (eIDAS)
Tipo	Descripción	Validez Legal
Simple (SES)	Click para aceptar, checkbox, email de confirmación	Admisible como prueba, no equivale a manuscrita
Avanzada (AES)	Vinculada al firmante, detecta alteraciones, control exclusivo	Mayor valor probatorio, inversión carga prueba
Cualificada (QES)	AES + certificado cualificado + dispositivo seguro (QSCD)	Equivalente legal a firma manuscrita (Art. 25.2 eIDAS)

1.2 Objetivos del Sistema
•	Firma cualificada (QES): Integración con DNIe, certificados FNMT, y prestadores cualificados
•	Firma avanzada (AES): Mediante Cl@ve Firma para usuarios sin certificado instalado
•	Formato PAdES-LTV: Long-Term Validation para verificabilidad a largo plazo (>10 años)
•	Flujos de firma múltiple: Secuencial y paralelo para contratos multi-parte
•	Sellado de tiempo (TSA): Timestamp cualificado para prueba de existencia
•	Verificación automática: Validación de firmas existentes y cadena de certificados
•	UX simplificada: Proceso de firma en < 60 segundos para usuarios con AutoFirma
1.3 Casos de Uso por Profesión
Profesión	Documentos a Firmar	Tipo Firma Requerido
Abogado	Poderes, contratos, escritos judiciales, actas	QES (LexNET requiere certificado)
Médico	Informes clínicos, recetas, partes de baja	QES (obligatorio sanidad)
Arquitecto	Proyectos, certificados, visados colegiales	QES (visado digital)
Asesor fiscal	Declaraciones, poderes AEAT, cuentas anuales	QES (Sede Electrónica AEAT)
Consultor	Propuestas, contratos de servicios, NDAs	AES (suficiente para B2B)
Cliente particular	Aceptación de presupuestos, consentimientos	AES vía Cl@ve o SES

 
2. Arquitectura del Sistema
2.1 Diagrama de Componentes
┌─────────────────────────────────────────────────────────────────────────┐
│                    FIRMA DIGITAL MODULE                                 │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  ┌─────────────────┐         ┌─────────────────┐                       │
│  │   Signature     │         │   Verification  │                       │
│  │    Service      │         │    Service      │                       │
│  └────────┬────────┘         └────────┬────────┘                       │
│           │                           │                                │
│     ┌─────┴─────────────┬─────────────┴─────┐                          │
│     │                   │                   │                          │
│     ▼                   ▼                   ▼                          │
│  ┌──────────┐    ┌──────────┐    ┌──────────┐                          │
│  │AutoFirma │    │  Cl@ve   │    │   TSA    │                          │
│  │ Adapter  │    │ Adapter  │    │ Adapter  │                          │
│  └────┬─────┘    └────┬─────┘    └────┬─────┘                          │
│       │               │               │                                │
└───────┼───────────────┼───────────────┼────────────────────────────────┘
        │               │               │                                 
        ▼               ▼               ▼                                 
 ┌────────────┐  ┌────────────┐  ┌────────────┐                           
 │ AutoFirma  │  │ Cl@ve Firma│  │   FNMT     │                           
 │ (local)    │  │ (cloud)    │  │   TSA      │                           
 └────────────┘  └────────────┘  └────────────┘                           
2.2 Métodos de Firma Disponibles
Método	Requisitos	Tipo Firma	Caso de Uso
AutoFirma + DNIe	DNIe 3.0 + lector + AutoFirma	QES	Máxima seguridad legal
AutoFirma + FNMT	Certificado FNMT instalado	QES	Profesionales, empresas
Cl@ve Firma	Cuenta Cl@ve permanente	AES	Ciudadanos sin certificado
Certificado cloud	Cert. en HSM del prestador	QES	Firma masiva empresarial
OTP + biometría	Móvil + verificación ID	AES	Clientes particulares

 
3. Formato PAdES (PDF Advanced Electronic Signatures)
PAdES es el estándar ETSI para firmas electrónicas en documentos PDF, definido en ETSI EN 319 142. Garantiza interoperabilidad y validación a largo plazo.
3.1 Niveles de Conformidad PAdES
Nivel	Contenido	Uso Recomendado
PAdES-B	Firma básica: certificado + firma CMS embebida en PDF	Documentos internos, corto plazo
PAdES-T	B + Timestamp cualificado (prueba de existencia)	Documentos con fecha crítica
PAdES-LT	T + Datos de validación (CRL/OCSP, certs intermedios)	Archivo a medio plazo (5 años)
PAdES-LTA	LT + Archive timestamp (renovable para validación perpetua)	Archivo legal a largo plazo (>10 años)

ServiciosConecta genera firmas PAdES-LTA por defecto para documentos legales, garantizando verificabilidad incluso cuando los certificados originales hayan expirado.
3.2 Estructura de un PDF Firmado
┌──────────────────────────────────────────────────────────────┐
│                    PDF DOCUMENT                              │
├──────────────────────────────────────────────────────────────┤
│  Original PDF Content                                        │
│  ├── Pages, text, images...                                  │
├──────────────────────────────────────────────────────────────┤
│  Signature Dictionary (/Type /Sig)                           │
│  ├── /Filter /Adobe.PPKLite                                  │
│  ├── /SubFilter /ETSI.CAdES.detached (PAdES)                 │
│  ├── /ByteRange [0, offset1, offset2, length]                │
│  ├── /Contents <CMS signature bytes...>                      │
│  │   ├── SignerInfo                                          │
│  │   ├── Signing Certificate                                 │
│  │   ├── Timestamp Token (RFC 3161)                          │
│  │   └── OCSP/CRL responses (LT)                             │
│  └── /Reason, /Location, /ContactInfo...                     │
├──────────────────────────────────────────────────────────────┤
│  Visual Signature Appearance (optional)                      │
│  ├── Signer name, date, reason                               │
│  └── Logo/image (customizable)                               │
├──────────────────────────────────────────────────────────────┤
│  Document Security Store (DSS) - PAdES-LT/LTA                │
│  ├── /Certs [intermediate CA certificates]                   │
│  ├── /OCSPs [OCSP responses]                                 │
│  └── /CRLs [Certificate Revocation Lists]                    │
└──────────────────────────────────────────────────────────────┘
 
4. Modelo de Datos
4.1 Entidad: signature_request
Solicitud de firma para un documento, con soporte para múltiples firmantes.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador público	UNIQUE, NOT NULL
document_id	INT	Documento a firmar	FK secure_document.id, NOT NULL
requester_id	INT	Quien solicita la firma	FK users.uid, NOT NULL
tenant_id	INT	Tenant	FK tenant.id, NOT NULL, INDEX
title	VARCHAR(255)	Título de la solicitud	NOT NULL
message	TEXT	Mensaje para firmantes	NULLABLE
signature_type	VARCHAR(16)	Tipo mínimo requerido	ENUM: simple|advanced|qualified
pades_level	VARCHAR(16)	Nivel PAdES	ENUM: B|T|LT|LTA, DEFAULT 'LTA'
signing_order	VARCHAR(16)	Orden de firma	ENUM: sequential|parallel
expires_at	DATETIME	Fecha límite para firmar	NULLABLE
reminder_days	INT	Días para recordatorio	DEFAULT 3
status	VARCHAR(24)	Estado de la solicitud	ENUM: draft|pending|partially_signed|completed|expired|cancelled
completed_at	DATETIME	Cuándo se completó	NULLABLE
signed_document_id	INT	Doc firmado resultante	FK secure_document.id, NULLABLE
created	DATETIME	Fecha creación	NOT NULL

4.2 Entidad: signature_signer
Cada firmante de una solicitud de firma.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
request_id	INT	Solicitud de firma	FK signature_request.id, NOT NULL
signer_id	INT	Usuario firmante	FK users.uid, NULLABLE
signer_email	VARCHAR(255)	Email del firmante	NOT NULL
signer_name	VARCHAR(255)	Nombre del firmante	NOT NULL
signer_nif	VARCHAR(20)	NIF/NIE del firmante	NULLABLE (para verificar cert)
signing_order	INT	Orden de firma (si secuencial)	DEFAULT 1
role	VARCHAR(64)	Rol del firmante	Ej: 'Abogado', 'Cliente', 'Testigo'
access_token	VARCHAR(64)	Token de acceso único	UNIQUE, NOT NULL
signature_method	VARCHAR(24)	Método usado para firmar	ENUM: autofirma_dnie|autofirma_fnmt|clave|otp
status	VARCHAR(16)	Estado	ENUM: pending|viewed|signed|declined|expired
viewed_at	DATETIME	Cuándo vio el documento	NULLABLE
signed_at	DATETIME	Cuándo firmó	NULLABLE
decline_reason	TEXT	Motivo de rechazo	NULLABLE
signer_ip	VARCHAR(45)	IP al firmar	NULLABLE
certificate_info	JSON	Info del certificado usado	NULLABLE

 
4.3 Entidad: digital_signature
Registro de cada firma digital aplicada a un documento.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador público	UNIQUE, NOT NULL
document_id	INT	Documento firmado	FK secure_document.id, NOT NULL
signer_entry_id	INT	Entrada del firmante	FK signature_signer.id, NOT NULL
signature_type	VARCHAR(16)	Tipo de firma	ENUM: simple|advanced|qualified
signature_method	VARCHAR(24)	Método usado	ENUM: autofirma_dnie|autofirma_fnmt|clave|otp
pades_level	VARCHAR(8)	Nivel PAdES	ENUM: B|T|LT|LTA
certificate_subject	VARCHAR(512)	Subject DN del certificado	NOT NULL
certificate_issuer	VARCHAR(512)	Issuer DN	NOT NULL
certificate_serial	VARCHAR(64)	Número de serie	NOT NULL
certificate_valid_from	DATETIME	Inicio validez cert	NOT NULL
certificate_valid_to	DATETIME	Fin validez cert	NOT NULL
timestamp_time	DATETIME	Fecha del sello de tiempo	NULLABLE
timestamp_tsa	VARCHAR(255)	TSA que emitió el sello	NULLABLE
signature_reason	VARCHAR(255)	Razón de la firma	NULLABLE
signature_location	VARCHAR(255)	Ubicación de firma	NULLABLE
visual_position	JSON	Posición visual en PDF	{page, x, y, width, height}
verification_status	VARCHAR(16)	Estado de verificación	ENUM: valid|invalid|unknown
verification_details	JSON	Detalles de verificación	NULLABLE
created	DATETIME	Fecha de firma	NOT NULL

 
5. Integración con AutoFirma
AutoFirma es la aplicación oficial del Gobierno de España para firma electrónica. Se comunica con el navegador mediante el protocolo afirma:// y permite firmar con DNIe o certificados instalados.
5.1 Flujo de Firma con AutoFirma
1.	Usuario hace clic en "Firmar con AutoFirma"
2.	Servidor genera datos a firmar (hash del PDF) y crea sesión de firma
3.	Cliente (JS) invoca AutoFirma vía protocolo afirma://
4.	AutoFirma se abre, usuario selecciona certificado (DNIe o FNMT)
5.	AutoFirma firma localmente y devuelve firma CMS al navegador
6.	Cliente envía firma CMS al servidor
7.	Servidor valida firma, obtiene timestamp de TSA, embebe en PDF
8.	Servidor añade datos LTV (OCSP/CRL) para crear PAdES-LTA
5.2 AutoFirmaAdapter (PHP)
<?php namespace Drupal\jaraba_signature\Adapter;

class AutoFirmaAdapter implements SignatureAdapterInterface {
  
  public function prepareSignatureSession(
    SecureDocument $document,
    SignatureSigner $signer
  ): SignatureSession {
    // Generar hash del documento a firmar
    $pdfContent = $this->vault->getDecryptedContent($document);
    $hash = hash('sha256', $pdfContent, true);
    
    // Crear sesión con ID único
    $sessionId = bin2hex(random_bytes(16));
    
    // Preparar parámetros para AutoFirma
    $params = [
      'dat' => base64_encode($hash),
      'algorithm' => 'SHA256withRSA',
      'format' => 'CAdES',
      'mode' => 'implicit', // Firma detached
    ];
    
    // Guardar sesión en caché (5 minutos TTL)
    $this->cache->set("signature_session:{$sessionId}", [
      'document_id' => $document->id(),
      'signer_id' => $signer->id(),
      'hash' => base64_encode($hash),
      'params' => $params,
    ], 300);
    
    return new SignatureSession(
      sessionId: $sessionId,
      params: $params,
      expiresAt: time() + 300
    );
  }
  
  public function processSignature(
    string $sessionId,
    string $signatureB64
  ): ProcessedSignature {
    // Recuperar sesión
    $session = $this->cache->get("signature_session:{$sessionId}");
    if (!$session) {
      throw new SignatureSessionExpiredException();
    }
    
    // Decodificar firma CMS/CAdES
    $signature = base64_decode($signatureB64);
    
    // Validar firma
    $validation = $this->validateCmsSignature($signature, $session['hash']);
    if (!$validation->isValid()) {
      throw new InvalidSignatureException($validation->getError());
    }
    
    // Extraer información del certificado
    $certInfo = $this->extractCertificateInfo($signature);
    
    // Obtener timestamp de TSA
    $timestamp = $this->tsaService->getTimestamp($signature);
    
    // Obtener datos de validación (OCSP/CRL)
    $validationData = $this->getValidationData($certInfo);
    
    return new ProcessedSignature(
      signature: $signature,
      certificate: $certInfo,
      timestamp: $timestamp,
      validationData: $validationData,
      signatureType: $this->determineSignatureType($certInfo)
    );
  }
}

 
5.3 Cliente JavaScript para AutoFirma
// autofirma-client.js

class AutoFirmaClient {
  
  constructor(config) {
    this.serverUrl = config.serverUrl;
    this.autoFirmaUrl = 'afirma://';
  }
  
  async signDocument(documentId, signerId) {
    try {
      // 1. Obtener sesión de firma del servidor
      const session = await this.prepareSession(documentId, signerId);
      
      // 2. Construir URL para AutoFirma
      const autoFirmaParams = new URLSearchParams({
        op: 'sign',
        dat: session.params.dat,
        algorithm: session.params.algorithm,
        format: session.params.format,
        // Callback al servidor para recibir firma
        servlet: `${this.serverUrl}/api/v1/signature/callback/${session.sessionId}`,
      });
      
      // 3. Abrir AutoFirma
      const url = `${this.autoFirmaUrl}sign?${autoFirmaParams}`;
      
      // Intentar con protocolo afirma://
      window.location.href = url;
      
      // 4. Polling para verificar si la firma se completó
      return await this.waitForSignature(session.sessionId);
      
    } catch (error) {
      if (error.name === 'AutoFirmaNotInstalled') {
        this.showInstallPrompt();
      }
      throw error;
    }
  }
  
  async waitForSignature(sessionId, timeout = 300000) {
    const startTime = Date.now();
    
    while (Date.now() - startTime < timeout) {
      const result = await fetch(
        `${this.serverUrl}/api/v1/signature/session/${sessionId}/status`
      ).then(r => r.json());
      
      if (result.status === 'completed') {
        return result;
      } else if (result.status === 'error') {
        throw new Error(result.error);
      }
      
      // Esperar 2 segundos antes de siguiente polling
      await new Promise(resolve => setTimeout(resolve, 2000));
    }
    
    throw new Error('Timeout waiting for signature');
  }
  
  showInstallPrompt() {
    // Mostrar modal con link de descarga
    const downloadUrl = 'https://firmaelectronica.gob.es/Home/Descargas.html';
    // ... mostrar UI
  }
}

 
6. Integración con Cl@ve Firma
Cl@ve es el sistema de identificación electrónica del Gobierno de España. Cl@ve Firma permite firmar documentos sin necesidad de instalar software adicional, usando certificados en la nube.
6.1 Requisitos para Integración
Requisito	Descripción
Alta en Cl@ve	La plataforma debe estar registrada como SP (Service Provider) en Cl@ve
Certificado SSL/TLS	Certificado cualificado para el servidor (HTTPS obligatorio)
Metadata SAML	Intercambio de metadatos SAML 2.0 con la FNMT-RCM
Entorno de pruebas	Acceso al entorno PRE de Cl@ve para desarrollo y testing
Convenio	Convenio con la Secretaría General de Administración Digital

6.2 ClaveAdapter (PHP)
<?php namespace Drupal\jaraba_signature\Adapter;

class ClaveAdapter implements SignatureAdapterInterface {
  
  private const CLAVE_SIGN_ENDPOINT = 'https://clave.gob.es/Proxy/SignatureService';
  
  public function initiateSignature(
    SecureDocument $document,
    SignatureSigner $signer
  ): ClaveSignatureRequest {
    // Preparar documento para firma
    $pdfContent = $this->vault->getDecryptedContent($document);
    
    // Construir petición SAML para Cl@ve
    $samlRequest = $this->buildSamlRequest([
      'document' => base64_encode($pdfContent),
      'document_name' => $document->getOriginalFilename(),
      'signer_nif' => $signer->getSignerNif(),
      'return_url' => $this->getReturnUrl($signer),
      'signature_format' => 'PAdES',
      'signature_level' => 'T', // Con timestamp
    ]);
    
    // Firmar petición con certificado del SP
    $signedRequest = $this->signSamlRequest($samlRequest);
    
    return new ClaveSignatureRequest(
      endpoint: self::CLAVE_SIGN_ENDPOINT,
      samlRequest: $signedRequest,
      relayState: $signer->getAccessToken()
    );
  }
  
  public function processCallback(Request $request): ProcessedSignature {
    // Validar respuesta SAML
    $samlResponse = $request->get('SAMLResponse');
    $relayState = $request->get('RelayState');
    
    // Verificar firma de la respuesta
    $response = $this->parseSamlResponse($samlResponse);
    $this->verifySamlSignature($response);
    
    // Extraer documento firmado
    $signedPdf = base64_decode($response->getSignedDocument());
    
    // Extraer info del certificado usado
    $certInfo = $response->getCertificateInfo();
    
    return new ProcessedSignature(
      signedDocument: $signedPdf,
      certificate: $certInfo,
      timestamp: $response->getTimestamp(),
      signatureType: 'advanced' // Cl@ve Firma es AES, no QES
    );
  }
}

 
7. Sellado de Tiempo (TSA)
El sellado de tiempo (Timestamping Authority) proporciona prueba de que un documento existía en un momento determinado. Es esencial para PAdES-T y superiores.
7.1 TSAs Cualificados Soportados
Proveedor	URL TSA	Notas
FNMT-RCM	https://www.sede.fnmt.gob.es/tsa/tss	Gratuito para Admón.
Camerfirma	https://tsa.camerfirma.com	Comercial
Firmaprofesional	https://tsa.firmaprofesional.com	Comercial
ANF AC	https://tsa.anf.es	Comercial

7.2 TimestampService
<?php namespace Drupal\jaraba_signature\Service;

class TimestampService {
  
  public function getTimestamp(string $dataToTimestamp): TimestampToken {
    // Calcular hash de los datos
    $hash = hash('sha256', $dataToTimestamp, true);
    
    // Construir petición RFC 3161
    $request = $this->buildTimestampRequest($hash);
    
    // Enviar a TSA
    $response = $this->httpClient->post($this->tsaUrl, [
      'headers' => ['Content-Type' => 'application/timestamp-query'],
      'body' => $request,
      'auth' => [$this->tsaUser, $this->tsaPassword], // Si requiere auth
    ]);
    
    // Parsear respuesta
    $tsResponse = $this->parseTimestampResponse($response->getBody());
    
    // Verificar que el timestamp es válido
    $this->verifyTimestampToken($tsResponse, $hash);
    
    return new TimestampToken(
      token: $tsResponse->getEncodedToken(),
      time: $tsResponse->getTime(),
      tsa: $tsResponse->getTsaName(),
      serialNumber: $tsResponse->getSerialNumber()
    );
  }
  
  private function buildTimestampRequest(string $hash): string {
    // RFC 3161 TimeStampReq
    $req = new TimeStampReq();
    $req->setMessageImprint(new MessageImprint(
      new AlgorithmIdentifier('sha256'),
      $hash
    ));
    $req->setCertReq(true); // Incluir certificado del TSA en respuesta
    $req->setNonce(random_bytes(8)); // Prevenir replay
    
    return $req->encode();
  }
}

 
8. APIs REST
Método	Endpoint	Descripción	Auth
POST	/api/v1/signature/requests	Crear solicitud de firma	Provider
GET	/api/v1/signature/requests	Listar solicitudes (enviadas/recibidas)	Usuario
GET	/api/v1/signature/requests/{uuid}	Detalle de solicitud	Participante
DELETE	/api/v1/signature/requests/{uuid}	Cancelar solicitud	Requester
POST	/api/v1/signature/requests/{uuid}/remind	Enviar recordatorio a firmantes pendientes	Requester
GET	/api/v1/signature/sign/{token}	Obtener documento para firmar (vía token)	Token
POST	/api/v1/signature/sign/{token}/autofirma/prepare	Preparar sesión AutoFirma	Token
POST	/api/v1/signature/sign/{token}/autofirma/complete	Procesar firma AutoFirma	Token
POST	/api/v1/signature/sign/{token}/clave/initiate	Iniciar firma Cl@ve	Token
POST	/api/v1/signature/sign/{token}/clave/callback	Callback de Cl@ve	SAML
POST	/api/v1/signature/sign/{token}/decline	Rechazar firma	Token
POST	/api/v1/signature/verify	Verificar firmas de un PDF	Usuario
GET	/api/v1/signature/documents/{uuid}/download	Descargar documento firmado	Participante

9. Flujos de Automatización (ECA)
Código	Evento	Acciones
SIG-001	signature_request.created	Enviar invitación a todos los firmantes (email + WhatsApp)
SIG-002	signer.viewed	Notificar al requester que el firmante vio el documento
SIG-003	signer.signed	Notificar a requester + activar siguiente firmante (si secuencial)
SIG-004	signer.declined	Notificar a requester + marcar solicitud como fallida
SIG-005	signature_request.completed	Notificar a todos + crear versión firmada en Buzón + registrar audit
SIG-006	signature_request.expiring_soon	Enviar recordatorio urgente a firmantes pendientes (48h antes)
SIG-007	signature_request.expired	Marcar como expirada + notificar a todos + registrar en audit
SIG-008	cron.daily	Enviar recordatorios automáticos según reminder_days configurado

 
10. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 5.1	Semana 9	Modelo de datos + SignatureService base + generación PAdES-B	88_Buzon_Confianza
Sprint 5.2	Semana 10	AutoFirmaAdapter + cliente JS + integración TSA (PAdES-T)	Sprint 5.1
Sprint 5.3	Semana 11	Datos LTV (OCSP/CRL) para PAdES-LTA + VerificationService	Sprint 5.2
Sprint 5.4	Semana 12	ClaveAdapter (SAML) + UI de firma + flujos multi-firmante + tests	Sprint 5.3 + Convenio Cl@ve

10.1 Criterios de Aceptación
•	✓ Firma con AutoFirma + DNIe/FNMT genera PAdES-LTA válido
•	✓ Firma con Cl@ve funcional (entorno PRE)
•	✓ Timestamp cualificado de FNMT incluido en todas las firmas
•	✓ Verificación de firmas existentes con informe detallado
•	✓ Flujos multi-firmante (secuencial y paralelo) funcionando
•	✓ Integración completa con Buzón de Confianza
•	✓ PDFs firmados validables en Adobe Acrobat y validadores oficiales (VALIDe)

--- Fin del Documento ---

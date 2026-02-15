
ECOSISTEMA JARABA
180
Platform Facturae 3.2.2 + FACe B2G
Especificación Técnica de Implementación
Módulo jaraba_facturae

Metadata	Valor
Documento	180_Platform_Facturae_FACe_B2G_v1
Versión	1.0
Fecha	15 febrero 2026
Autor	Claude (Anthropic) para Ecosistema Jaraba
Estado	Ready for Development - Claude Code
Normativa	Ley 25/2013, Facturae 3.2.2, XAdES-EPES
Prioridad	P1 - Q3 2026
Dependencias	Doc 134 (Stripe Billing), Doc 179 (VeriFactu)
Módulo Drupal	jaraba_facturae
 
1. Alcance y Marco Legal
Este documento especifica la implementación completa del módulo jaraba_facturae para la generación, firma y envío de facturas electrónicas en formato Facturae 3.2.2 conforme a la normativa española vigente.
1.1. Normativa Aplicable
Normativa	Descripción	Impacto
Ley 25/2013	Impulso factura electrónica sector público	Obligatorio B2G desde 2015
Orden HAP/1074/2014	Requisitos técnicos FACe (PGEFe)	Integración Web Service
Resolución 25/07/2017	Formato Facturae versión 3.2.2	Esquema XML obligatorio
RD 1619/2012	Reglamento de facturación	Contenido obligatorio facturas
Ley 59/2003	Firma electrónica	XAdES-EPES obligatoria
Ley 18/2022 (Crea y Crece)	Factura electrónica B2B (futuro)	Preparación formato

1.2. Alcance Funcional
El módulo cubre tres capacidades fundamentales:
•	Generación Facturae 3.2.2: Construcción de XML conforme al esquema XSD oficial con todos los campos obligatorios y opcionales necesarios para cada tipo de factura
•	Firma XAdES-EPES: Firma electrónica avanzada del XML Facturae usando certificado cualificado, produciendo archivos .xsig válidos
•	Envío a FACe (B2G): Integración con el Punto General de Entrada de Facturas Electrónicas mediante Web Services SOAP para envío, consulta de estado y anulación

1.3. Doble Impacto en el Ecosistema Jaraba
•	Jaraba Impact S.L. como emisor: Facturas de suscripciones SaaS, comisiones marketplace y licencias dirigidas a organismos públicos (programas SEPE, Andalucía +ei, ayuntamientos)
•	Tenants del SaaS como emisores: Cada tenant que facture a Administraciones Públicas necesita generar Facturae desde su propia configuración fiscal
CRITICAL: Sin Facturae, los tenants del ecosistema Jaraba NO pueden facturar a ninguna Administración Pública española. Esto bloquea el Motor Institucional del Triple Motor Económico.
 
2. Entidades de Datos
El módulo define 3 entidades custom content entity de Drupal 11 que extienden el sistema de facturación existente (doc 134).
2.1. facturae_document
Entidad principal que almacena cada factura electrónica generada en formato Facturae.
Campo	Tipo	Requerido	Descripción
id	INT UNSIGNED AUTO_INCREMENT	PK	Identificador único
uuid	VARCHAR(128)	UNIQUE	UUID Drupal
tenant_id	INT UNSIGNED	FK groups.id	Tenant multi-tenant
invoice_id	INT UNSIGNED	FK billing_invoice.id	Factura origen del sistema billing
facturae_number	VARCHAR(20)	NOT NULL	NumSerieFactura (serie + número)
facturae_series	VARCHAR(10)	NULL	Serie de facturación Facturae
invoice_class	VARCHAR(2)	NOT NULL	OO=Original, OR=Rectificativa, CO=Copia, CC=Resumen colectivo
invoice_type	VARCHAR(4)	NOT NULL	Tipo fiscal: FC, FA, AF (ver tabla 2.1.1)
issuer_type	VARCHAR(2)	NOT NULL	EM=Emisor, RE=Receptor, TE=Tercero
schema_version	VARCHAR(10)	DEFAULT '3.2.2'	Versión formato Facturae
currency_code	VARCHAR(3)	DEFAULT 'EUR'	ISO 4217 moneda
language_code	VARCHAR(2)	DEFAULT 'es'	ISO 639-1 idioma
issue_date	DATE	NOT NULL	Fecha expedición (FechaExpedicion)
operation_date	DATE	NULL	Fecha operación si distinta
tax_point_date	DATE	NULL	Fecha devengo
seller_nif	VARCHAR(20)	NOT NULL	NIF/CIF emisor
seller_name	VARCHAR(255)	NOT NULL	Razón social emisor
seller_person_type	VARCHAR(1)	NOT NULL	F=Física, J=Jurídica
seller_residence_type	VARCHAR(1)	DEFAULT 'R'	R=Residente, E=Extranjero, U=UE
seller_address_json	JSON	NOT NULL	Dirección emisor estructurada
buyer_nif	VARCHAR(20)	NOT NULL	NIF/CIF receptor
buyer_name	VARCHAR(255)	NOT NULL	Razón social receptor
buyer_person_type	VARCHAR(1)	NOT NULL	F=Física, J=Jurídica
buyer_residence_type	VARCHAR(1)	DEFAULT 'R'	R, E o U
buyer_address_json	JSON	NOT NULL	Dirección receptor estructurada
buyer_admin_centres_json	JSON	NULL	Centros administrativos DIR3 (B2G)
invoice_lines_json	JSON	NOT NULL	Líneas de detalle de la factura
taxes_outputs_json	JSON	NOT NULL	Impuestos repercutidos desglosados
taxes_withheld_json	JSON	NULL	Retenciones (IRPF)
total_gross_amount	DECIMAL(12,2)	NOT NULL	TotalBrutoAntesImpuestos
total_general_discounts	DECIMAL(12,2)	DEFAULT 0	TotalDescuentosGenerales
total_general_surcharges	DECIMAL(12,2)	DEFAULT 0	TotalCargosGenerales
total_gross_amount_before_taxes	DECIMAL(12,2)	NOT NULL	TotalBrutoAntesImpuestos
total_tax_outputs	DECIMAL(12,2)	NOT NULL	TotalImpuestosRepercutidos
total_tax_withheld	DECIMAL(12,2)	DEFAULT 0	TotalImpuestosRetenidos
total_invoice_amount	DECIMAL(12,2)	NOT NULL	TotalFactura
total_outstanding	DECIMAL(12,2)	NOT NULL	TotalAPagar (tras anticipos/retenciones)
total_executable	DECIMAL(12,2)	NOT NULL	TotalAEjecutar
payment_details_json	JSON	NULL	Datos de pago (IBAN, vencimientos)
legal_literals_json	JSON	NULL	Literales legales (donaciones, subvenciones)
additional_data_json	JSON	NULL	Datos adicionales y anexos
corrective_json	JSON	NULL	Datos de factura rectificativa
xml_unsigned	LONGTEXT	NULL	XML Facturae sin firmar
xml_signed	LONGTEXT	NULL	XML Facturae firmado (XAdES-EPES)
xsig_file_id	INT UNSIGNED	FK file_managed	Archivo .xsig almacenado
pdf_representation_id	INT UNSIGNED	FK file_managed	PDF representación visual
signature_status	VARCHAR(20)	DEFAULT 'unsigned'	unsigned|signed|invalid|expired
signature_timestamp	DATETIME	NULL	Fecha/hora de firma
signature_certificate_nif	VARCHAR(20)	NULL	NIF del certificado firmante
face_status	VARCHAR(20)	DEFAULT 'not_sent'	not_sent|sent|registered|rejected|paid|cancelled
face_registry_number	VARCHAR(50)	NULL	Número de registro en FACe
face_csv	VARCHAR(100)	NULL	Código Seguro de Verificación FACe
face_tramitacion_status	VARCHAR(50)	NULL	Estado tramitación FACe
face_tramitacion_date	DATETIME	NULL	Fecha último cambio tramitación
face_anulacion_status	VARCHAR(50)	NULL	Estado solicitud anulación
face_response_json	JSON	NULL	Respuesta completa FACe
validation_errors_json	JSON	NULL	Errores de validación XSD
status	VARCHAR(20)	DEFAULT 'draft'	draft|validated|signed|sent|error
created	DATETIME	NOT NULL	Fecha creación
changed	DATETIME	NOT NULL	Última modificación
uid	INT UNSIGNED	FK users.uid	Usuario creador

NOTA: El campo buyer_admin_centres_json es OBLIGATORIO para facturas B2G. Debe contener los códigos DIR3 de: Oficina Contable, Órgano Gestor y Unidad Tramitadora.

2.1.1. Tipos de Factura (invoice_type)
Código	Descripción	Uso típico
FC	Factura completa	Factura estándar con todos los datos
FA	Factura simplificada (ticket)	Importes < 400 EUR o rectificativas < 6000 EUR
AF	Factura simplificada a completa	Conversión de simplificada

2.2. facturae_tenant_config
Configuración fiscal y de firma por tenant para la generación de Facturae.
Campo	Tipo	Descripción
id	INT UNSIGNED AUTO_INCREMENT	PK
tenant_id	INT UNSIGNED	FK groups.id (UNIQUE)
nif_emisor	VARCHAR(20)	NIF/CIF del emisor fiscal
nombre_razon	VARCHAR(255)	Razón social o nombre completo
person_type	VARCHAR(1)	F=Física, J=Jurídica
residence_type	VARCHAR(1)	R=Residente, E=Extranjero, U=UE
address_json	JSON	Dirección fiscal estructurada
contact_json	JSON	Datos contacto (teléfono, email, web)
default_series	VARCHAR(10)	Serie por defecto para facturas
next_number	INT UNSIGNED	Siguiente número de factura
numbering_pattern	VARCHAR(50)	Patrón numeración (ej: {SERIE}{YYYY}-{NUM:5})
certificate_file_id	INT UNSIGNED	FK file_managed - certificado .p12
certificate_password_encrypted	BLOB	Password cifrada (AES-256-GCM)
certificate_nif_titular	VARCHAR(20)	NIF del titular del certificado
certificate_subject	VARCHAR(500)	Subject del certificado
certificate_expiry	DATE	Fecha caducidad certificado
certificate_issuer	VARCHAR(255)	Emisor (FNMT-RCM, etc.)
face_enabled	BOOLEAN	¿Envío automático a FACe?
face_environment	VARCHAR(10)	staging|production
face_email_notification	VARCHAR(255)	Email para notificaciones FACe
default_payment_method	VARCHAR(4)	01=Efectivo, 02=Cheque, 04=Transferencia, ...
default_payment_iban	VARCHAR(34)	IBAN para cobros por transferencia
tax_regime	VARCHAR(10)	Régimen fiscal por defecto
retention_rate	DECIMAL(5,2)	% IRPF por defecto (si aplica)
invoice_description_template	TEXT	Plantilla descripción factura
legal_literals_default_json	JSON	Literales legales por defecto
active	BOOLEAN	DEFAULT TRUE
created	DATETIME	Fecha creación
changed	DATETIME	Última modificación

2.3. facturae_face_log
Registro inmutable de todas las comunicaciones con FACe para auditoría y trazabilidad.
Campo	Tipo	Descripción
id	INT UNSIGNED AUTO_INCREMENT	PK
tenant_id	INT UNSIGNED	FK groups.id
facturae_document_id	INT UNSIGNED	FK facturae_document.id
operation	VARCHAR(30)	send_invoice|query_status|cancel_invoice|query_units|query_invoices
soap_action	VARCHAR(100)	Método SOAP invocado
request_xml	LONGTEXT	SOAP Request completo
response_xml	LONGTEXT	SOAP Response completo
response_code	VARCHAR(20)	Código resultado FACe
response_description	TEXT	Descripción resultado
registry_number	VARCHAR(50)	Número de registro asignado
http_status	INT	HTTP status code
duration_ms	INT	Duración llamada en ms
error_detail	TEXT	Detalle error si aplica
ip_address	VARCHAR(45)	IP origen de la llamada
user_id	INT UNSIGNED	FK users.uid
created	DATETIME	Timestamp operación

NOTA: Esta entidad es APPEND-ONLY. No se permite UPDATE ni DELETE para mantener trazabilidad completa de comunicaciones con la Administración.
 
3. Servicios PHP
El módulo implementa 6 servicios inyectables vía Dependency Injection de Drupal 11.
3.1. FacturaeXmlService
Servicio principal para la construcción del XML Facturae 3.2.2 conforme al esquema XSD oficial.
3.1.1. Interfaz Pública
interface FacturaeXmlServiceInterface {
  public function buildFromInvoice(int $invoice_id): string;
  public function buildFromData(array $data): string;
  public function buildCorrective(int $original_id, array $correction): string;
  public function validateAgainstXsd(string $xml): ValidationResult;
  public function generateVisualPdf(string $xml, int $tenant_id): string;
}

3.1.2. Estructura XML Generada
El XML se construye conforme al namespace http://www.facturae.gob.es/formato/Versiones/Facturaev3_2_2.xml con la siguiente estructura obligatoria:
<?xml version="1.0" encoding="UTF-8"?>
<fe:Facturae xmlns:fe="http://www.facturae.gob.es/formato/Versiones/Facturaev3_2_2.xml"
  xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
  <FileHeader>
    <SchemaVersion>3.2.2</SchemaVersion>
    <Modality>I</Modality>  <!-- I=Individual, L=Lote -->
    <InvoiceIssuerType>EM</InvoiceIssuerType>
    <Batch>
      <BatchIdentifier>NIF+Serie+Numero</BatchIdentifier>
      <InvoicesCount>1</InvoicesCount>
      <TotalInvoicesAmount><TotalAmount>100.00</TotalAmount></TotalInvoicesAmount>
      <TotalOutstandingAmount><TotalAmount>100.00</TotalAmount></TotalOutstandingAmount>
      <TotalExecutableAmount><TotalAmount>100.00</TotalAmount></TotalExecutableAmount>
      <InvoiceCurrencyCode>EUR</InvoiceCurrencyCode>
    </Batch>
  </FileHeader>
  <Parties>
    <SellerParty>...</SellerParty>
    <BuyerParty>...</BuyerParty>
  </Parties>
  <Invoices>
    <Invoice>
      <InvoiceHeader>...</InvoiceHeader>
      <InvoiceIssueData>...</InvoiceIssueData>
      <TaxesOutputs>...</TaxesOutputs>
      <TaxesWithheld>...</TaxesWithheld>  <!-- Si aplica -->
      <InvoiceTotals>...</InvoiceTotals>
      <Items>...</Items>
      <PaymentDetails>...</PaymentDetails>
      <LegalLiterals>...</LegalLiterals>
      <AdditionalData>...</AdditionalData>
    </Invoice>
  </Invoices>
  <ds:Signature>...</ds:Signature>  <!-- Añadida por XAdESService -->
</fe:Facturae>

3.1.3. Campos Obligatorios por Bloque
SellerParty / BuyerParty:
Elemento XML	Campo Entidad	Obligatorio	Formato
TaxIdentification/PersonTypeCode	person_type	Sí	F o J
TaxIdentification/ResidenceTypeCode	residence_type	Sí	R, E o U
TaxIdentification/TaxIdentificationNumber	nif	Sí	NIF/CIF válido
LegalEntity/CorporateName (si J)	nombre_razon	Sí	Max 80 chars
Individual/Name+FirstSurname (si F)	nombre	Sí	Max 40+40 chars
AddressInSpain/Address	address	Sí	Max 80 chars
AddressInSpain/PostCode	postal_code	Sí	5 dígitos
AddressInSpain/Town	town	Sí	Max 50 chars
AddressInSpain/Province	province	Sí	Max 20 chars
AddressInSpain/CountryCode	country	Sí	ISO 3166 Alpha-3 (ESP)
AdministrativeCentres (B2G)	admin_centres	B2G Sí	Códigos DIR3

AdministrativeCentres para B2G (FACe) - OBLIGATORIO para facturas a Administraciones Públicas:
CentreCode	RoleTypeCode	Descripción
Código DIR3	01	Oficina Contable
Código DIR3	02	Órgano Gestor
Código DIR3	03	Unidad Tramitadora
Código DIR3	04	Órgano Proponente (opcional)

CRITICAL: Los códigos DIR3 son OBLIGATORIOS para enviar a FACe. Sin ellos, la factura será rechazada automáticamente. Se obtienen del Directorio Común de Unidades Orgánicas y Oficinas (DIR3) en https://administracionelectronica.gob.es

3.2. FacturaeXAdESService
Servicio de firma electrónica XAdES-EPES sobre el XML Facturae, conforme a la especificación ETSI TS 101 903 y la política de firma de Facturae.
3.2.1. Interfaz Pública
interface FacturaeXAdESServiceInterface {
  public function signDocument(string $xml, int $tenant_id): string;
  public function verifySignature(string $signed_xml): SignatureVerification;
  public function getCertificateInfo(int $tenant_id): CertificateInfo;
  public function validateCertificate(int $tenant_id): CertificateValidation;
}

3.2.2. Proceso de Firma XAdES-EPES
La firma sigue el estándar XAdES-EPES (Explicit Policy-based Electronic Signature) con los siguientes pasos:
•	Paso 1 - Canonicalización: Aplicar Canonicalization C14N exclusive al XML Facturae
•	Paso 2 - Digest del documento: Calcular SHA-256 del documento canonicalizado
•	Paso 3 - SignedInfo: Construir SignedInfo con Reference al documento y propiedades firmadas
•	Paso 4 - Firma RSA: Firmar SignedInfo con la clave privada del certificado PKCS#12
•	Paso 5 - KeyInfo: Incluir certificado X.509 en Base64
•	Paso 6 - SignedProperties: Incluir SigningTime, SigningCertificate (digest SHA-256), SignaturePolicyIdentifier

3.2.3. Política de Firma Facturae
SignaturePolicyIdentifier:
  SignaturePolicyId:
    SigPolicyId:
      Identifier: http://www.facturae.gob.es/politica_de_firma_formato_facturae/
                  politica_de_firma_formato_facturae_v3_1.pdf
      Description: Política de firma electrónica para facturación
                   electrónica con formato Facturae
    SigPolicyHash:
      DigestMethod: http://www.w3.org/2001/04/xmlenc#sha256
      DigestValue: [hash SHA-256 del PDF de política en Base64]

3.2.4. Implementación Técnica
La firma se implementa usando ext-openssl de PHP nativo:
// Cargar certificado PKCS#12
$p12Content = file_get_contents($certPath);
openssl_pkcs12_read($p12Content, $certs, $password);
$privateKey = openssl_pkey_get_private($certs['pkey']);
$x509 = openssl_x509_read($certs['cert']);

// Firmar
openssl_sign($canonicalizedSignedInfo, $signature, $privateKey, OPENSSL_ALGO_SHA256);
$signatureBase64 = base64_encode($signature);

NOTA: La extensión PHP ext-openssl ya está disponible en el servidor IONOS. No requiere dependencias externas adicionales. Se recomienda verificar que openssl_pkcs12_read soporte los certificados FNMT-RCM correctamente.

3.3. FACeClientService
Cliente SOAP para la integración con el Punto General de Entrada de Facturas Electrónicas (FACe/PGEFe).
3.3.1. Interfaz Pública
interface FACeClientServiceInterface {
  public function sendInvoice(int $facturae_id): FACeResult;
  public function queryInvoice(string $registry_number): FACeStatus;
  public function queryInvoiceList(int $tenant_id, array $filters): array;
  public function cancelInvoice(string $registry_number, string $reason): FACeResult;
  public function queryStatuses(): array;
  public function queryAdminUnits(string $dir3_code): array;
  public function testConnection(int $tenant_id): bool;
}

3.3.2. Endpoints SOAP FACe
Entorno	WSDL URL	Uso
Producción	https://webservice.face.gob.es/facturasspp2?wsdl	Envío real a AAPP
Staging	https://se-face-webservice.redsara.es/facturasspp2?wsdl	Pruebas certificadas
API REST (nuevo)	https://api.face.gob.es/providers/doc	Alternativa REST (secundaria)

3.3.3. Operaciones SOAP
enviarFactura - Envío de factura electrónica firmada:
Parámetros de entrada:
  correo: String - Email notificación (obligatorio)
  factura: SSPPFactura
    factura: base64Binary - XML .xsig codificado en Base64
    nombre: String - Nombre archivo (factura_SERIE_NUM.xsig)
    mime: String - 'application/xml'
  anexos: SSPPAnexo[] (0-5)
    anexo: base64Binary - Contenido Base64
    nombre: String - Nombre archivo
    mime: String - MIME type

Respuesta:
  resultado: SSPPResultadoEnviarFactura
    codigo: String - '0' = éxito
    descripcion: String - Descripción resultado
    factura:
      numeroRegistro: String - Número registro FACe
      organoGestor: String - Código DIR3 OG
      unidadTramitadora: String - Código DIR3 UT
      oficinaContable: String - Código DIR3 OC
      csv: String - Código Seguro Verificación

consultarFactura - Consulta estado de una factura:
Entrada: numeroRegistro: String
Respuesta:
  factura:
    numeroRegistro: String
    tramitacion:
      codigo: String - Código estado
      descripcion: String
      motivo: String
    anulacion:
      codigo: String - Código estado anulación
      descripcion: String
      motivo: String

consultarListadoFacturas - Listado de facturas (max 500):
Entrada: nif: String, fechaDesde/fechaHasta: Date (opcionales)
Respuesta: Array de SSPPResultadoConsultarFactura

anularFactura - Solicitud de anulación:
Entrada: numeroRegistro: String, motivo: String
Respuesta: código resultado + descripción

3.3.4. Estados FACe - Ciclo de Vida
Código	Estado	Descripción	Flujo
1200	Registrada	Factura registrada en RCF	Ordinario
1300	Registrada en RCF	Anotada en registro contable	Ordinario
2400	Contabilizada la obligación reconocida	Obligación reconocida	Ordinario
2500	Pagada	Factura pagada	Ordinario
2600	Rechazada	Factura rechazada por el organismo	Ordinario
3100	Anulación solicitada	Emisor solicita anulación	Anulación
3200	Anulación aceptada	Organismo acepta anulación	Anulación
3300	Anulación rechazada	Organismo rechaza anulación	Anulación

3.3.5. Autenticación SOAP
FACe requiere autenticación mediante certificado digital en la capa TLS (mutual TLS / client certificate). El SoapClient PHP se configura así:
$context = stream_context_create([
  'ssl' => [
    'local_cert' => $pemFilePath,  // Certificado + clave en PEM
    'passphrase' => $password,
    'verify_peer' => true,
    'cafile' => '/etc/ssl/certs/ca-certificates.crt',
  ]
]);
$client = new \SoapClient($wsdl, [
  'stream_context' => $context,
  'cache_wsdl' => WSDL_CACHE_NONE,
  'trace' => true,  // Para logging
]);

NOTA: El certificado PKCS#12 (.p12) debe convertirse a PEM para SoapClient. Esto se hace en runtime: openssl_pkcs12_read() + exportar cert y key concatenados.
 
3.4. FacturaeNumberingService
Gestión de numeración secuencial de facturas Facturae por tenant, garantizando unicidad y secuencialidad.
3.4.1. Interfaz Pública
interface FacturaeNumberingServiceInterface {
  public function getNextNumber(int $tenant_id, ?string $series = NULL): string;
  public function formatNumber(int $tenant_id, int $number, ?string $series): string;
  public function reserveNumber(int $tenant_id, ?string $series = NULL): NumberReservation;
  public function releaseReservation(int $reservation_id): void;
  public function getLastNumber(int $tenant_id, ?string $series = NULL): ?int;
}

3.4.2. Formato de Numeración
Patrones configurables por tenant con los siguientes tokens:
Token	Expansión	Ejemplo
{SERIE}	Serie configurada	FE
{YYYY}	Año 4 dígitos	2026
{YY}	Año 2 dígitos	26
{NUM:N}	Número con N dígitos, zero-padded	00042
{MM}	Mes 2 dígitos	03
Ejemplo patrón: FE{YYYY}-{NUM:5}
Resultado: FE2026-00042

CRITICAL: La numeración debe ser estrictamente secuencial y sin huecos dentro de cada serie y ejercicio fiscal. Se usa SELECT ... FOR UPDATE para bloqueo pessimista en la obtención del siguiente número.

3.5. FacturaeValidationService
Validación del XML generado contra el esquema XSD oficial y reglas de negocio adicionales.
3.5.1. Interfaz Pública
interface FacturaeValidationServiceInterface {
  public function validateXml(string $xml): ValidationResult;
  public function validateForFACe(string $xml): ValidationResult;
  public function validateNIF(string $nif): bool;
  public function validateDIR3(string $code): bool;
  public function validateIBAN(string $iban): bool;
}

3.5.2. Validaciones
•	XSD Schema: Validación contra Facturaev3_2_2.xsd oficial descargado de facturae.gob.es
•	NIF/CIF: Validación algorítmica del dígito de control para DNI, NIE y CIF
•	DIR3: Verificación formato código (LA0000000) y consulta online opcional
•	IBAN: Validación ISO 13616 con cálculo módulo 97
•	Importes: Cuadre de totales: suma líneas = bruto, bruto + impuestos - retenciones = total
•	Fechas: Fecha expedición no posterior a hoy, fecha operación coherente
•	FACe-específica: Presencia de los 3 centros administrativos DIR3 obligatorios

3.6. FactuareDIR3Service
Servicio de consulta del Directorio Común de Unidades Orgánicas y Oficinas (DIR3) para obtener los códigos necesarios de centros administrativos B2G.
3.6.1. Interfaz Pública
interface FacturaeDIR3ServiceInterface {
  public function searchUnits(string $query, string $type = 'all'): array;
  public function getUnitByCode(string $dir3_code): ?DIR3Unit;
  public function getRelatedUnits(string $dir3_code): array;
  public function syncFromFACe(int $tenant_id): SyncResult;
  public function getCachedUnits(string $query): array;
}

3.6.2. Fuentes de Datos DIR3
•	FACe consultarUnidades: Web Service SOAP de FACe que devuelve las unidades del directorio
•	FACe consultarUnidadesPorAdministracion: Filtra unidades por administración específica
•	Cache local: Tabla drupal cache con TTL de 24 horas para evitar consultas repetidas

NOTA: El directorio DIR3 contiene más de 60.000 unidades. Se recomienda implementar búsqueda con autocompletado y cache agresivo para mejorar la experiencia de usuario.
 
4. API REST
21 endpoints para la gestión completa de Facturae y FACe.
4.1. Generación y Gestión de Facturae
Método	Endpoint	Descripción	Permiso
POST	/api/v1/facturae/generate	Generar Facturae desde factura billing	create facturae
POST	/api/v1/facturae/generate-manual	Generar Facturae con datos manuales	create facturae
POST	/api/v1/facturae/generate-corrective	Generar factura rectificativa	create facturae
GET	/api/v1/facturae/documents	Listar documentos Facturae (paginado)	view facturae
GET	/api/v1/facturae/documents/{id}	Detalle documento Facturae	view facturae
GET	/api/v1/facturae/documents/{id}/xml	Descargar XML sin firmar	view facturae
GET	/api/v1/facturae/documents/{id}/xsig	Descargar .xsig firmado	view facturae
GET	/api/v1/facturae/documents/{id}/pdf	Descargar PDF representación	view facturae
DELETE	/api/v1/facturae/documents/{id}	Eliminar borrador no enviado	delete facturae

4.2. Firma Digital
Método	Endpoint	Descripción	Permiso
POST	/api/v1/facturae/documents/{id}/sign	Firmar documento con XAdES-EPES	sign facturae
GET	/api/v1/facturae/documents/{id}/verify	Verificar firma existente	view facturae

4.3. FACe (B2G)
Método	Endpoint	Descripción	Permiso
POST	/api/v1/facturae/face/send/{id}	Enviar facturae firmada a FACe	send facturae face
GET	/api/v1/facturae/face/status/{registry}	Consultar estado en FACe	view facturae face
POST	/api/v1/facturae/face/cancel/{registry}	Solicitar anulación en FACe	send facturae face
GET	/api/v1/facturae/face/list	Listar facturas en FACe	view facturae face
POST	/api/v1/facturae/face/sync-statuses	Sincronizar estados desde FACe	admin facturae
POST	/api/v1/facturae/face/test-connection	Test conexión con FACe	admin facturae

4.4. Configuración y DIR3
Método	Endpoint	Descripción	Permiso
GET	/api/v1/facturae/config	Obtener configuración tenant	admin facturae
PUT	/api/v1/facturae/config	Actualizar configuración	admin facturae
POST	/api/v1/facturae/config/certificate	Subir certificado .p12	admin facturae
GET	/api/v1/facturae/dir3/search	Buscar unidades DIR3	view facturae
 
5. Flujos ECA (Event-Condition-Action)
5.1. ECA-FE-001: Generación Automática Facturae B2G
•	Trigger: billing_invoice.status cambia a 'paid' O manual por usuario
•	Condition 1: El buyer (receptor) tiene flag 'is_public_administration' = TRUE
•	Condition 2: Tenant tiene facturae_tenant_config activa con certificado válido
•	Condition 3: El buyer tiene códigos DIR3 configurados (admin_centres)
•	Condition 4: NO existe facturae_document para ese invoice_id
•	Action 1: FacturaeNumberingService::getNextNumber() - Obtener siguiente número
•	Action 2: FacturaeXmlService::buildFromInvoice() - Generar XML Facturae 3.2.2
•	Action 3: FacturaeValidationService::validateForFACe() - Validar XML
•	Action 4: FacturaeXAdESService::signDocument() - Firmar con XAdES-EPES
•	Action 5: FACeClientService::sendInvoice() - Enviar a FACe
•	Action 6: Actualizar facturae_document con face_registry_number y status='sent'
•	Action 7: Log en facturae_face_log
•	Error Handler: Log watchdog + Email admin + Status 'error' + Descripción error

5.2. ECA-FE-002: Sincronización Estados FACe
•	Trigger: Cron cada 4 horas
•	Condition: Existen facturae_document con face_status IN ('sent', 'registered') y enviadas hace > 1 hora
•	Action 1: Para cada documento: FACeClientService::queryInvoice()
•	Action 2: Actualizar face_tramitacion_status y face_tramitacion_date
•	Action 3: Si status='2500' (Pagada): Marcar billing_invoice como reconciliada
•	Action 4: Si status='2600' (Rechazada): Notificación URGENTE al admin
•	Action 5: Log en facturae_face_log cada consulta

5.3. ECA-FE-003: Alerta Certificado Próximo a Expirar
•	Trigger: Cron semanal (lunes 09:00)
•	Condition: certificate_expiry < NOW() + 30 días
•	Action <30 días: Email notificación al admin del tenant
•	Action <7 días: Notificación URGENTE + Banner en dashboard
•	Action caducado: Desactivar firma automática + Log CERT_EXPIRED

5.4. ECA-FE-004: Generación PDF Representación
•	Trigger: facturae_document.status cambia a 'signed'
•	Action: FacturaeXmlService::generateVisualPdf() - Genera PDF a partir del XML
•	Almacenamiento: Guarda como file entity y vincula a pdf_representation_id

5.5. ECA-FE-005: Rectificativa Automática
•	Trigger: billing_credit_note.status = 'confirmed' Y factura original tiene facturae_document
•	Action 1: FacturaeXmlService::buildCorrective() con datos de la nota de crédito
•	Action 2: Firmar y enviar a FACe si original fue enviada a FACe
 
6. Permisos RBAC
Permiso	Descripción	Roles
administer facturae	Configurar módulo y certificados	admin, platform_admin
view facturae	Ver documentos Facturae	admin, accountant, manager
create facturae	Generar nuevos documentos	admin, accountant
delete facturae	Eliminar borradores	admin
sign facturae	Firmar documentos	admin, accountant
send facturae face	Enviar/anular en FACe	admin
view facturae face	Ver estados FACe	admin, accountant, manager
view facturae logs	Ver logs de comunicación	admin, auditor
 
7. Estructura del Módulo Drupal
modules/custom/jaraba_facturae/
├── jaraba_facturae.info.yml
├── jaraba_facturae.module
├── jaraba_facturae.permissions.yml
├── jaraba_facturae.services.yml
├── jaraba_facturae.routing.yml
├── jaraba_facturae.install          # Schema + updates
├── jaraba_facturae.links.menu.yml
├── src/
│   ├── Entity/
│   │   ├── FacturaeDocument.php
│   │   ├── FacturaeTenantConfig.php
│   │   └── FacturaeFaceLog.php
│   ├── Service/
│   │   ├── FacturaeXmlService.php
│   │   ├── FacturaeXAdESService.php
│   │   ├── FACeClientService.php
│   │   ├── FacturaeNumberingService.php
│   │   ├── FacturaeValidationService.php
│   │   └── FacturaeDIR3Service.php
│   ├── Controller/
│   │   ├── FacturaeDocumentController.php
│   │   ├── FACeController.php
│   │   ├── FacturaeConfigController.php
│   │   └── FacturaeDashboardController.php
│   ├── Form/
│   │   ├── FacturaeConfigForm.php
│   │   ├── FacturaeCertificateForm.php
│   │   ├── FacturaeManualForm.php
│   │   └── FacturaeDIR3SearchForm.php
│   ├── Plugin/
│   │   └── QueueWorker/
│   │       └── FACeSyncWorker.php
│   ├── EventSubscriber/
│   │   └── FacturaeInvoiceSubscriber.php
│   └── Exception/
│       ├── FacturaeValidationException.php
│       ├── FacturaeSignatureException.php
│       └── FACeConnectionException.php
├── templates/
│   ├── facturae-dashboard.html.twig
│   ├── facturae-document-detail.html.twig
│   └── facturae-pdf-template.html.twig
├── xsd/
│   └── Facturaev3_2_2.xsd
├── policy/
│   └── politica_de_firma_formato_facturae_v3_1.pdf
└── tests/
    ├── src/Unit/           # 8 tests
    ├── src/Kernel/         # 10 tests
    └── src/Functional/     # 8 tests
 
8. Plan de Tests
8.1. Tests Unitarios (8)
Test	Descripción	Validación
testXmlGenerationBasic	XML con campos mínimos obligatorios	Estructura correcta Facturae 3.2.2
testXmlGenerationB2G	XML con centros administrativos DIR3	3 AdministrativeCentres presentes
testXmlRectificativa	XML de factura rectificativa	Bloque Corrective correcto
testNifValidation	Validación NIF/CIF/NIE	Letras de control correctas
testIbanValidation	Validación IBAN español	Módulo 97 correcto
testNumberingSequential	Numeración secuencial sin huecos	N+1 siempre
testNumberingPatternFormat	Formato con tokens	Expansión correcta tokens
testTotalCalculation	Cuadre de importes	Líneas + IVA - IRPF = Total

8.2. Tests Kernel (10)
Test	Descripción
testCreateFacturaeFromInvoice	Generación completa desde billing_invoice
testXsdValidation	XML generado pasa validación XSD oficial
testXAdESSignature	Firma XAdES-EPES válida y verificable
testSignaturePolicy	Política de firma Facturae incluida correctamente
testMultiTenantIsolation	Tenant A no ve documentos de Tenant B
testTenantConfigCRUD	CRUD completo de configuración por tenant
testFaceLogImmutability	Log no permite UPDATE ni DELETE
testCertificateLifecycle	Subir, validar, expirar certificado
testDIR3Cache	Cache de unidades DIR3 funciona correctamente
testCorrective Invoice	Factura rectificativa referencia la original

8.3. Tests Funcionales (8)
Test	Descripción
testFullB2GFlow	Invoice → Facturae → Firma → FACe → Estado
testFACeConnectionStaging	Conexión exitosa con FACe staging
testFACeSendAndQuery	Enviar factura y consultar registro
testFACeCancelFlow	Solicitar anulación y verificar estado
testApiPermissions	RBAC en todos los endpoints API
testConcurrentNumbering	Numeración correcta bajo concurrencia
testECAAutoGeneration	Trigger ECA genera Facturae automáticamente
testCertificateExpiryAlert	Alerta de caducidad de certificado
 
9. Roadmap de Implementación
9.1. Sprint 1 (Semanas 1-2): Entidades + XML Core
•	Crear 3 entidades: facturae_document, facturae_tenant_config, facturae_face_log
•	FacturaeXmlService: Generación XML Facturae 3.2.2 completo
•	FacturaeValidationService: Validación XSD + NIF + IBAN
•	FacturaeNumberingService: Numeración secuencial con bloqueo
•	Tests unitarios: 8 tests de generación XML y validaciones
Estimación: 56-72 horas

9.2. Sprint 2 (Semanas 3-4): Firma XAdES + PDF
•	FacturaeXAdESService: Firma XAdES-EPES con política Facturae
•	PDF representación: Generación PDF visual desde XML
•	API REST: Endpoints de generación, firma y descarga
•	ECA-FE-004: Generación automática PDF post-firma
•	Tests kernel: Firma, validación, multi-tenant
Estimación: 60-80 horas

9.3. Sprint 3 (Semanas 5-6): FACe Integration
•	FACeClientService: Cliente SOAP con autenticación certificado
•	FacturaeDIR3Service: Consulta y cache de unidades DIR3
•	ECA-FE-001: Generación automática para B2G
•	ECA-FE-002: Sincronización estados FACe
•	API REST FACe: Envío, consulta, anulación
•	Tests funcionales: Flujo completo con FACe staging
Estimación: 64-84 horas

9.4. Sprint 4 (Semanas 7-8): Dashboard + QA
•	Dashboard Facturae: Vista resumen, estados, alertas
•	Formularios admin: Config, certificado, generación manual, DIR3
•	ECA-FE-003: Alertas certificado
•	ECA-FE-005: Rectificativas automáticas
•	Tests E2E: Flujo completo con FACe staging
•	Documentación usuario: Manual operativo
Estimación: 50-68 horas

9.5. Resumen Inversión
Concepto	Horas	Coste (45 EUR/h)
Sprint 1: Entidades + XML	56-72	2.520-3.240 EUR
Sprint 2: Firma + PDF	60-80	2.700-3.600 EUR
Sprint 3: FACe	64-84	2.880-3.780 EUR
Sprint 4: Dashboard + QA	50-68	2.250-3.060 EUR
TOTAL	230-304	10.350-13.680 EUR
 
10. Dependencias Técnicas
10.1. PHP Extensions (nativas)
Extension	Uso	Estado
ext-dom	DOMDocument para construir XML	Incluida en PHP 8.3
ext-openssl	Firma RSA + manejo certificados PKCS#12	Incluida en PHP 8.3
ext-soap	SoapClient para FACe Web Services	Incluida en PHP 8.3
ext-hash	SHA-256 para digest de firma	Incluida en PHP 8.3
ext-libxml	Validación XSD	Incluida en PHP 8.3

10.2. Archivos Externos
Archivo	Fuente	Uso
Facturaev3_2_2.xsd	facturae.gob.es/formato	Validación XML
politica_firma_v3_1.pdf	facturae.gob.es	Hash para SignaturePolicy
Certificado .p12	FNMT-RCM o prestador cualificado	Firma + autenticación FACe

10.3. Composer (0 dependencias externas)
Este módulo NO requiere dependencias Composer externas. Toda la funcionalidad se implementa con las extensiones nativas de PHP 8.3 y las APIs de Drupal 11. Esto es intencional para minimizar la superficie de ataque y simplificar el mantenimiento.

10.4. Reutilización de Componentes Existentes
Componente	Documento	Reutilización
billing_invoice	Doc 134	90% - Entidad fuente de datos
FirmaDigitalService	Doc 89	60% - Adaptar de PAdES a XAdES
QR Dynamic	Doc 65/81	30% - Solo patrón de generación
ECA Automation	Doc 06	85% - Mismos patrones Event-Condition-Action
Multi-Tenant Config	Doc 07	90% - Patrón de configuración por tenant
VeriFactu certificados	Doc 179	70% - Gestión PKCS#12 compartida
 
11. Interoperabilidad con VeriFactu y E-Factura B2B
El módulo jaraba_facturae está diseñado para coexistir con jaraba_verifactu (doc 179) y preparar el terreno para jaraba_einvoice_b2b (doc 181).
11.1. Relación con VeriFactu
•	Complementarios, no excluyentes: Una factura puede tener TANTO registro VeriFactu como formato Facturae
•	Flujo combinado: Invoice → VeriFactu (hash + QR + remisión AEAT) → Facturae (XML + firma + FACe)
•	Certificado compartido: El mismo PKCS#12 sirve para firmar Facturae y autenticar remisión VeriFactu
•	Servicio compartido: CertificateManagerService (nuevo) que centraliza la gestión de certificados para ambos módulos

11.2. Preparación para E-Factura B2B
•	Formato Facturae como base: El mismo XML Facturae 3.2.2 será aceptado para B2B cuando entre en vigor la Ley Crea y Crece
•	Firma reutilizable: La firma XAdES-EPES es válida tanto para B2G como para B2B
•	Extensión a UBL: El doc 181 añadirá soporte UBL EN 16931 como formato adicional, pero Facturae seguirá siendo el principal
•	Solución Pública: FACe B2B (futuro) reutilizará la misma infraestructura de envío

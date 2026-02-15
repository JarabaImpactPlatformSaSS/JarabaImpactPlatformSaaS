
JARABA IMPACT PLATFORM
VERIFACTU COMPLIANCE MODULE
Especificación Técnica de Implementación
Módulo jaraba_verifactu
RD 1007/2023 · Orden HAC/1177/2024 · RD-ley 15/2025
Versión:	1.0
Fecha:	Febrero 2026
Estado:	Ready for Development - Claude Code
Código:	179_Platform_VeriFactu_Implementation_v1
Módulo Drupal:	jaraba_verifactu
Dependencias:	134_Stripe_Billing, 96_Facturacion, 88_Buzon_Confianza, 89_Firma_Digital, 06_Flujos_ECA
Prioridad:	P0 CRITICO - Bloquea Compliance Legal
Marco Legal:	RD 1007/2023, Orden HAC/1177/2024, RD 254/2025, RD-ley 15/2025
Deadline Sociedades:	1 de Enero de 2027
Deadline Autónomos:	1 de Julio de 2027
Sanción:	Hasta 50.000 EUR/ejercicio por software no conforme

 
Índice
Índice	1
1. Resumen Ejecutivo	1
1.1 Alcance del Módulo	1
1.2 Stack Tecnológico	1
1.3 Impacto Dual en el Ecosistema	1
2. Modelo de Datos	1
2.1 Entidad: verifactu_invoice_record	1
2.1.1 Estructura JSON del campo desglose_iva	1
2.2 Entidad: verifactu_event_log	1
2.2.1 Tipos de Evento del SIF	1
2.3 Entidad: verifactu_remision_batch	1
2.4 Entidad de Configuración: verifactu_tenant_config	1
3. Servicios PHP (Services)	1
3.1 VeriFactuHashService	1
3.1.1 Algoritmo de Cálculo de Hash (Detalle)	1
3.2 VeriFactuRecordService	1
3.3 VeriFactuQrService	1
3.4 VeriFactuXmlService	1
3.5 VeriFactuRemisionService	1
3.6 VeriFactuPdfService	1
4. API REST Endpoints	1
4.1 Endpoints de Administración	1
4.2 Endpoints de Registros	1
4.3 Endpoints de Remisión	1
4.4 Endpoints de Auditoría	1
5. Flujos de Automatización (ECA)	1
5.1 ECA-VF-001: Generación Automática de Registro de Alta	1
5.2 ECA-VF-002: Anulación de Registro	1
5.3 ECA-VF-003: Procesamiento de Cola de Remisión (Cron)	1
5.4 ECA-VF-004: Verificación de Integridad de Cadena	1
5.5 ECA-VF-005: Alerta de Caducidad de Certificado	1
6. Estructura XML AEAT	1
6.1 XML de Registro de Alta (Estructura)	1
6.2 Tipos de Factura	1
6.3 Claves de Régimen IVA	1
7. Permisos y RBAC	1
7.1 Definición de Permisos	1
7.2 Matriz de Acceso por Rol	1
8. Estructura del Módulo Drupal	1
8.1 Archivo jaraba_verifactu.info.yml	1
8.2 Archivo jaraba_verifactu.services.yml	1
9. Tests Obligatorios	1
9.1 Tests Unitarios (VeriFactuHashServiceTest)	1
9.2 Tests de Kernel	1
9.3 Tests Funcionales / End-to-End	1
10. Componentes Reutilizables del Ecosistema	1
11. Roadmap de Implementación	1
11.1 Sprint 1: Core Hash + Entidades (Semanas 1-2)	1
11.2 Sprint 2: XML + QR + PDF (Semanas 3-4)	1
11.3 Sprint 3: Remisión AEAT + API (Semanas 5-6)	1
11.4 Sprint 4: Dashboard + QA + Go-Live (Semanas 7-8)	1
11.5 Resumen de Inversión	1
12. Dependencias y Requisitos Previos	1
12.1 Dependencias Composer	1
12.2 Esquemas XSD de la AEAT	1
12.3 Certificado Digital	1
12.4 Entorno de Pruebas AEAT	1
13. Declaración Responsable del SIF	1
14. Conclusión y Próximos Pasos	1

 
1. Resumen Ejecutivo
Este documento proporciona la especificación técnica completa para la implementación del módulo jaraba_verifactu en Drupal 11. El módulo gestiona la generación de registros de facturación conformes a VERI*FACTU, el cálculo de huellas (hash) encadenadas SHA-256, la generación de códigos QR de verificación, y la remisión voluntaria a la AEAT mediante servicios web SOAP.
[CRITICO] DEADLINE LEGAL: Sociedades 01/01/2027. Autónomos 01/07/2027. Multas hasta 50.000 EUR/ejercicio por software no conforme (Art. 29.2.j LGT).
1.1 Alcance del Módulo
El módulo jaraba_verifactu cubre los siguientes requisitos:
Generación de Registros de Facturación (RF): Creación automática de registro XML por cada factura emitida, anulada o rectificada.
Hash Encadenado SHA-256: Cálculo de huella criptográfica con encadenamiento secuencial conforme al Anexo II de la Orden HAC/1177/2024.
Código QR Verificable: Generación de QR con URL de verificación en sede electrónica AEAT, NIF, número de factura, importe y huella.
Leyenda VERI*FACTU: Inclusión de la leyenda 'Factura verificable en la sede electrónica de la AEAT' en todas las facturas.
Remisión Voluntaria AEAT: Envío automático de registros mediante servicio web SOAP con certificado electrónico cualificado.
Registro de Eventos: Log inmutable de operaciones del sistema para auditoría y trazabilidad.
Declaración Responsable: Generación de la declaración responsable del productor del SIF accesible desde el sistema.
Multi-Tenant: Soporte para múltiples emisores (NIF) con cadenas de hash independientes por tenant.
1.2 Stack Tecnológico
Componente	Tecnología	Versión
Core CMS	Drupal 11.x	11.1+
PHP	PHP 8.3+	8.3
Hash	SHA-256 (hash extension)	Nativa PHP
XML	DOMDocument + XMLWriter	Nativa PHP
SOAP Client	SoapClient (ext-soap)	Nativa PHP
Certificado Digital	PKCS#12 (.p12/.pfx)	FNMT/Cualificado
QR Generation	chillerlan/php-qrcode	5.x
PDF Integration	TCPDF (existente en doc 96)	6.x
Automatización	ECA Module	2.x
API	JSON:API + Custom REST	Drupal Core
Cache	Redis (existente)	7.x

1.3 Impacto Dual en el Ecosistema
VeriFactu afecta al ecosistema Jaraba en dos dimensiones:
Dimensión	Emisor	Tipo Facturas	Deadline
Jaraba Impact S.L.	NIF de la plataforma	Suscripciones SaaS, comisiones marketplace, licencias	01/01/2027
Tenants del SaaS	NIF de cada tenant	Facturas propias (ServiciosConecta, AgroConecta, ComercioConecta)	01/01/2027 o 01/07/2027

 
2. Modelo de Datos
Se definen 4 entidades custom content entity gestionadas por el módulo jaraba_verifactu, más una entidad de configuración por tenant. Todas las entidades siguen el patrón del ecosistema Jaraba con tenant_id (Group Module) para aislamiento multi-tenant.
2.1 Entidad: verifactu_invoice_record
Registro de facturación principal. Se genera uno por cada factura emitida, anulada o rectificada. Es la pieza central del sistema VeriFactu: contiene los datos fiscales, la huella hash encadenada y el estado de remisión a la AEAT.
[CRITICO] Esta entidad es APPEND-ONLY. Una vez creado un registro, NUNCA se modifica ni elimina. Solo se actualizan campos de estado de remisión.
Campo	Tipo	Descripción	Restricciones
id	SERIAL	ID interno autoincremental	PRIMARY KEY
uuid	UUID	Identificador único global	UNIQUE, NOT NULL
tenant_id	INT	Tenant emisor	FK groups.id, NOT NULL, INDEX
record_type	VARCHAR(16)	Tipo de registro	ENUM: alta|anulacion, NOT NULL
invoice_id	INT	Factura Drupal asociada	FK billing_invoice.id, NULLABLE, INDEX
nif_emisor	VARCHAR(20)	NIF/NIE del obligado tributario	NOT NULL, INDEX
nombre_razon_emisor	VARCHAR(255)	Nombre o razón social del emisor	NOT NULL
serie_factura	VARCHAR(20)	Serie de la factura	NULLABLE
num_factura	VARCHAR(60)	Número de factura	NOT NULL
fecha_expedicion	DATE	Fecha de expedición (YYYY-MM-DD)	NOT NULL, INDEX
fecha_operacion	DATE	Fecha de operación si distinta	NULLABLE
tipo_factura	VARCHAR(4)	Tipo según RD 1619/2012	ENUM: F1|F2|F3|R1|R2|R3|R4|R5, NOT NULL
tipo_rectificativa	VARCHAR(2)	Tipo rectificación	ENUM: S|I, NULLABLE
factura_rectificada_serie	VARCHAR(20)	Serie factura rectificada	NULLABLE
factura_rectificada_num	VARCHAR(60)	Número factura rectificada	NULLABLE
descripcion_operacion	VARCHAR(500)	Descripción de la operación	NOT NULL
clave_regimen	VARCHAR(4)	Clave de régimen IVA	NOT NULL (ej: 01, 02...)
nif_destinatario	VARCHAR(20)	NIF destinatario	NULLABLE
nombre_destinatario	VARCHAR(255)	Nombre destinatario	NULLABLE
id_pais_destinatario	VARCHAR(2)	Código país ISO 3166-1 alfa-2	DEFAULT 'ES'
desglose_iva	JSON	Array de desgloses IVA	NOT NULL
cuota_total	DECIMAL(12,2)	Cuota total (suma cuotas IVA)	NOT NULL
importe_total	DECIMAL(12,2)	Importe total factura	NOT NULL
encadenamiento_primer_registro	VARCHAR(1)	Si es primer registro de la cadena	ENUM: S|N, NOT NULL
hash_registro_anterior	VARCHAR(64)	Huella del registro anterior (hex)	NULLABLE
fecha_hora_generacion	DATETIME	Fecha/hora/huso de generación	NOT NULL (ISO 8601 con timezone)
huella_registro	VARCHAR(64)	Hash SHA-256 de este registro (hex)	NOT NULL, UNIQUE, INDEX
algoritmo_huella	VARCHAR(8)	Algoritmo usado	DEFAULT 'SHA-256'
id_sistema_informatico	VARCHAR(30)	Nombre del SIF	DEFAULT 'JarabaImpactSaaS'
version_sistema	VARCHAR(20)	Versión del SIF	NOT NULL
nif_productor_sif	VARCHAR(20)	NIF productor del software	NOT NULL
nombre_productor_sif	VARCHAR(255)	Nombre productor del software	NOT NULL
qr_code_url	VARCHAR(500)	URL verificación AEAT codificada en QR	NOT NULL
qr_code_image	INT	Imagen QR generada	FK file_managed.fid, NULLABLE
remision_status	VARCHAR(20)	Estado remisión AEAT	ENUM: pending|sent|accepted|rejected|error, DEFAULT 'pending'
remision_csv	VARCHAR(64)	CSV devuelto por AEAT	NULLABLE
remision_response_code	VARCHAR(10)	Código respuesta AEAT	NULLABLE
remision_response_detail	TEXT	Detalle respuesta AEAT	NULLABLE
remision_timestamp	DATETIME	Fecha/hora de remisión	NULLABLE
xml_registro	TEXT	XML completo del registro	NOT NULL
created	DATETIME	Fecha de creación	NOT NULL, UTC

2.1.1 Estructura JSON del campo desglose_iva
El campo desglose_iva almacena un array JSON con el desglose de cada tipo impositivo aplicado:
[
  {
    "clave_regimen": "01",
    "tipo_impositivo": 21.00,
    "base_imponible": 100.00,
    "cuota_repercutida": 21.00,
    "tipo_recargo_equivalencia": null,
    "cuota_recargo_equivalencia": null
  },
  {
    "clave_regimen": "01",
    "tipo_impositivo": 10.00,
    "base_imponible": 50.00,
    "cuota_repercutida": 5.00
  }
]

2.2 Entidad: verifactu_event_log
Registro de eventos del sistema informático de facturación. Es OBLIGATORIO para sistemas no VERI*FACTU, pero también se implementa en VERI*FACTU como buena práctica de auditoría. Cada evento registra operaciones críticas del SIF.
Campo	Tipo	Descripción	Restricciones
id	SERIAL	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
tenant_id	INT	Tenant asociado	FK groups.id, NOT NULL, INDEX
event_type	VARCHAR(32)	Tipo de evento del SIF	NOT NULL
event_description	TEXT	Descripción del evento	NOT NULL
nif_emisor	VARCHAR(20)	NIF del emisor afectado	NOT NULL
invoice_record_id	INT	Registro afectado	FK verifactu_invoice_record.id, NULLABLE
user_id	INT	Usuario que provocó el evento	FK users.uid, NULLABLE
ip_address	VARCHAR(45)	IP del usuario	NULLABLE
hash_evento	VARCHAR(64)	Huella SHA-256 del evento	NOT NULL
hash_evento_anterior	VARCHAR(64)	Huella del evento anterior	NULLABLE
fecha_hora_evento	DATETIME	Fecha/hora con timezone	NOT NULL
created	DATETIME	Fecha creación	NOT NULL, UTC

2.2.1 Tipos de Evento del SIF
event_type	Descripción	Trigger
SYSTEM_START	Inicio del sistema de facturación	Boot/Deploy del módulo
SYSTEM_STOP	Parada del sistema	Desactivación del módulo
CONFIG_CHANGE	Cambio de configuración del SIF	Edición config admin
CERT_CHANGE	Cambio de certificado digital	Upload nuevo certificado
RECORD_CREATE	Creación de registro de facturación	Nueva factura
RECORD_CANCEL	Anulación de registro	Anulación factura
REMISION_SENT	Envío a AEAT	Remisión SOAP
REMISION_OK	Respuesta AEAT aceptada	Callback SOAP
REMISION_ERROR	Error en remisión	Error SOAP
CHAIN_VERIFY	Verificación integridad cadena	Cron/Manual
CHAIN_BREAK	Rotura detectada en cadena	Verificación fallida
EXPORT_DATA	Exportación de datos	Backup/Export

2.3 Entidad: verifactu_remision_batch
Agrupa registros de facturación para envío en lote a la AEAT. El servicio web AEAT acepta hasta 1.000 registros por envío, con un control de flujo de 60 segundos entre envíos.
Campo	Tipo	Descripción	Restricciones
id	SERIAL	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
tenant_id	INT	Tenant	FK groups.id, NOT NULL, INDEX
nif_presentador	VARCHAR(20)	NIF del presentador	NOT NULL
batch_type	VARCHAR(16)	Tipo de operación	ENUM: alta|anulacion, NOT NULL
record_count	INT	Número de registros	NOT NULL
record_ids	JSON	Array de IDs de registros	NOT NULL
xml_request	LONGTEXT	XML completo del envío SOAP	NOT NULL
xml_response	LONGTEXT	XML respuesta AEAT	NULLABLE
status	VARCHAR(20)	Estado global del envío	ENUM: queued|sending|accepted|partial|rejected|error
response_detail	JSON	Detalle por registro	NULLABLE
incidencia	VARCHAR(1)	Marca de incidencia	ENUM: S|N, DEFAULT 'N'
sent_at	DATETIME	Fecha de envío	NULLABLE
response_at	DATETIME	Fecha de respuesta	NULLABLE
retry_count	INT	Intentos realizados	DEFAULT 0
next_retry_at	DATETIME	Próximo reintento	NULLABLE
created	DATETIME	Fecha creación	NOT NULL

2.4 Entidad de Configuración: verifactu_tenant_config
Configuración específica de VeriFactu por tenant. Almacena datos del certificado digital, datos del productor SIF, y preferencias de remisión.
Campo	Tipo	Descripción	Restricciones
id	SERIAL	ID interno	PRIMARY KEY
tenant_id	INT	Tenant	FK groups.id, UNIQUE, NOT NULL
nif_obligado	VARCHAR(20)	NIF obligado tributario	NOT NULL
nombre_razon	VARCHAR(255)	Nombre o razón social	NOT NULL
modo_verifactu	BOOLEAN	Activar modo VERI*FACTU	DEFAULT TRUE
remision_automatica	BOOLEAN	Remisión automática habilitada	DEFAULT TRUE
certificado_file	INT	Archivo certificado .p12	FK file_managed.fid, NULLABLE
certificado_password_encrypted	BLOB	Password cifrada del cert	NULLABLE
certificado_nif_titular	VARCHAR(20)	NIF titular certificado	NULLABLE
certificado_expiry	DATE	Caducidad certificado	NULLABLE
serie_default	VARCHAR(20)	Serie por defecto facturas	NULLABLE
entorno_aeat	VARCHAR(10)	Entorno AEAT	ENUM: pruebas|produccion, DEFAULT 'pruebas'
ultimo_hash	VARCHAR(64)	Ultimo hash de la cadena	NULLABLE
ultimo_registro_id	INT	ID del último registro	NULLABLE
contador_registros	BIGINT	Total registros generados	DEFAULT 0
updated	DATETIME	Ultima actualización	NOT NULL

 
3. Servicios PHP (Services)
El módulo jaraba_verifactu expone sus servicios mediante inyección de dependencias estándar de Drupal. Cada servicio se registra en jaraba_verifactu.services.yml.
3.1 VeriFactuHashService
Servicio central para cálculo de huellas SHA-256 y encadenamiento conforme al Anexo II de la Orden HAC/1177/2024.
[INFO] Los campos para el cálculo del hash se concatenan en orden específico separados por '&'. El resultado se codifica en hexadecimal lowercase.
namespace Drupal\jaraba_verifactu\Service;

class VeriFactuHashService {

  /**
   * Calcula la huella SHA-256 de un registro de facturacion de ALTA.
   * Campos concatenados con '&' segun Anexo II:
   * IDEmisorFactura & NumSerieFacturaEmisor &
   * FechaExpedicionFacturaEmisor & TipoFactura &
   * CuotaTotal & ImporteTotal & Huella &
   * FechaHoraHusoGenRegistro
   */
  public function calculateAltaHash(
    string $nif_emisor,
    string $num_serie_factura,
    string $fecha_expedicion,      // YYYY-MM-DD
    string $tipo_factura,          // F1, F2, R1...
    string $cuota_total,           // Con 2 decimales
    string $importe_total,         // Con 2 decimales
    ?string $hash_anterior,        // Hex del registro anterior o ''
    string $fecha_hora_huso_gen    // YYYY-MM-DDThh:mm:ss+HH:00
  ): string;

  /**
   * Calcula huella de registro de ANULACION.
   * Campos: IDEmisorFactura & NumSerieFacturaEmisor &
   * FechaExpedicionFacturaEmisor & Huella &
   * FechaHoraHusoGenRegistro
   */
  public function calculateAnulacionHash(
    string $nif_emisor,
    string $num_serie_factura,
    string $fecha_expedicion,
    ?string $hash_anterior,
    string $fecha_hora_huso_gen
  ): string;

  /**
   * Calcula huella de registro de EVENTO.
   * Campos: NIF & NumRegistro & FechaHoraHusoGenRegistro &
   * TipoEvento & Huella
   */
  public function calculateEventoHash(
    string $nif,
    string $num_registro,
    string $fecha_hora_huso,
    string $tipo_evento,
    ?string $hash_anterior
  ): string;

  /**
   * Obtiene el ultimo hash de la cadena para un tenant.
   * Lee de verifactu_tenant_config.ultimo_hash
   */
  public function getLastHash(int $tenant_id): ?string;

  /**
   * Verifica la integridad completa de la cadena de un tenant.
   * Recalcula todos los hashes y compara. Devuelve array con
   * resultado de cada registro verificado.
   */
  public function verifyChainIntegrity(
    int $tenant_id,
    ?int $from_id = NULL,
    ?int $to_id = NULL
  ): array;

}

3.1.1 Algoritmo de Cálculo de Hash (Detalle)
El cálculo sigue estrictamente el procedimiento publicado por la AEAT:
Paso 1: Concatenar los campos en el orden especificado, separados por el carácter '&' (ampersand). Si un campo es nulo o vacío, se usa cadena vacía pero el separador se mantiene.
Paso 2: Codificar la cadena resultante en UTF-8.
Paso 3: Aplicar el algoritmo SHA-256 sobre los bytes UTF-8.
Paso 4: Codificar el resultado en hexadecimal lowercase (64 caracteres).
Ejemplo de concatenación para registro de ALTA:
B12345678&FACT-2027-001&2027-01-15&F1&21.00&121.00&<hash_anterior_hex_64_chars>&2027-01-15T10:30:00+01:00

3.2 VeriFactuRecordService
Servicio para la creación y gestión de registros de facturación. Orquesta la creación del registro, cálculo de hash, generación de QR y encolado para remisión.
class VeriFactuRecordService {

  public function __construct(
    private VeriFactuHashService $hashService,
    private VeriFactuQrService $qrService,
    private VeriFactuXmlService $xmlService,
    private EntityTypeManagerInterface $entityTypeManager,
    private QueueFactory $queueFactory,
    private LoggerChannelFactoryInterface $logger,
  ) {}

  /**
   * Crea un registro de alta a partir de una factura existente.
   * Este metodo se invoca automaticamente via ECA cuando se crea
   * una factura en billing_invoice con status = 'paid'.
   *
   * @param int $invoice_id billing_invoice entity ID
   * @return VeriFactuInvoiceRecord La entidad creada
   * @throws VeriFactuChainException Si hay error en encadenamiento
   * @throws VeriFactuValidationException Si datos incompletos
   */
  public function createAltaFromInvoice(int $invoice_id): VeriFactuInvoiceRecord;

  /**
   * Crea un registro de anulacion para una factura.
   * @param int $invoice_id billing_invoice entity ID
   * @param string $motivo Motivo de la anulacion
   */
  public function createAnulacion(int $invoice_id, string $motivo): VeriFactuInvoiceRecord;

  /**
   * Crea registro de alta para factura rectificativa.
   */
  public function createRectificativa(
    int $invoice_id,
    string $tipo_rectificativa,  // 'S' sustitucion, 'I' diferencias
    string $serie_original,
    string $num_original
  ): VeriFactuInvoiceRecord;

  /**
   * Obtiene todos los registros de un tenant en orden cronologico.
   * Usado para auditorias y verificacion de cadena.
   */
  public function getRecordsByTenant(
    int $tenant_id,
    array $filters = [],
    int $limit = 50,
    int $offset = 0
  ): array;

  /**
   * Obtiene el resumen de estado VeriFactu para un tenant.
   */
  public function getTenantStatus(int $tenant_id): array;

}

3.3 VeriFactuQrService
Genera el código QR conforme a las especificaciones de la AEAT. El QR contiene una URL que apunta a la sede electrónica de la AEAT con parámetros de verificación.
class VeriFactuQrService {

  /**
   * Genera la URL de verificacion AEAT para el QR.
   * Formato:
   * https://www2.agenciatributaria.gob.es/wlpl/TIKE-CONT/
   * ValidarQR?nif={NIF}&numserie={SERIE+NUM}&fecha={FECHA}
   * &importe={TOTAL}&huella={HASH}
   */
  public function buildVerificationUrl(
    string $nif_emisor,
    string $serie_factura,
    string $num_factura,
    string $fecha_expedicion,
    string $importe_total,
    string $huella
  ): string;

  /**
   * Genera la imagen QR como PNG.
   * @return string Path al archivo temporal PNG
   */
  public function generateQrImage(string $url): string;

  /**
   * Genera QR y lo guarda como file entity en Drupal.
   * @return int fid del archivo guardado
   */
  public function generateAndSaveQr(
    VeriFactuInvoiceRecord $record
  ): int;

}

3.4 VeriFactuXmlService
Construye los mensajes XML conformes al esquema XSD oficial de la AEAT (SuministroLR, SuministroInformacion). Genera tanto el XML del registro individual como el mensaje SOAP completo para remisión.
class VeriFactuXmlService {

  /**
   * Genera el XML del registro de facturacion individual.
   * Conforme al esquema SIF-GE de la AEAT.
   */
  public function buildRegistroXml(
    VeriFactuInvoiceRecord $record
  ): string;

  /**
   * Construye el mensaje SOAP completo para remision de ALTAS.
   * Puede contener hasta 1000 registros.
   */
  public function buildAltasSoapMessage(
    array $records,
    string $nif_presentador
  ): string;

  /**
   * Construye el mensaje SOAP para remision de ANULACIONES.
   */
  public function buildAnulacionesSoapMessage(
    array $records,
    string $nif_presentador
  ): string;

  /**
   * Valida un XML contra el XSD oficial de la AEAT.
   * @return array Errores de validacion (vacio si OK)
   */
  public function validateAgainstXsd(string $xml): array;

  /**
   * Parsea la respuesta XML de la AEAT.
   * @return array Estado global + detalle por registro
   */
  public function parseAeatResponse(string $xml): array;

}

3.5 VeriFactuRemisionService
Gestiona la comunicación con los servicios web SOAP de la AEAT. Implementa el control de flujo (60 segundos entre envíos), reintentos automáticos, y gestión de incidencias.
class VeriFactuRemisionService {

  const AEAT_ENDPOINT_PROD = 'https://www2.agenciatributaria.gob.es/
    static_files/common/internet/dep/aplicaciones/es/aeat/tike/cont/ws/
    SistemaFacturacion/SifRegistroFacturacion.wsdl';

  const AEAT_ENDPOINT_TEST = 'https://prewww2.aeat.es/
    static_files/common/internet/dep/aplicaciones/es/aeat/tike/cont/ws/
    SistemaFacturacion/SifRegistroFacturacion.wsdl';

  const FLOW_CONTROL_SECONDS = 60;
  const MAX_RECORDS_PER_BATCH = 1000;
  const MAX_RETRIES = 5;

  /**
   * Envia un lote de registros de alta a la AEAT.
   * Usa SoapClient con certificado PKCS#12.
   */
  public function sendAltas(
    array $records,
    int $tenant_id
  ): VeriFactuRemisionBatch;

  /**
   * Envia anulaciones a la AEAT.
   */
  public function sendAnulaciones(
    array $records,
    int $tenant_id
  ): VeriFactuRemisionBatch;

  /**
   * Consulta registros presentados por el emisor.
   */
  public function queryRegistrosByEmisor(
    int $tenant_id,
    string $ejercicio,
    string $periodo
  ): array;

  /**
   * Procesa la cola de remision pendiente.
   * Llamado por cron cada minuto.
   */
  public function processRemisionQueue(): array;

  /**
   * Reintenta envios fallidos con marca de incidencia.
   */
  public function retryFailedBatches(int $tenant_id): int;

}

3.6 VeriFactuPdfService
Extiende el servicio de generación de PDF de factura (doc 96) para incluir los elementos VeriFactu obligatorios: código QR, leyenda de verificabilidad, y huella.
class VeriFactuPdfService {

  /**
   * Modifica el PDF de factura existente para incluir:
   * - Codigo QR en esquina inferior derecha
   * - Leyenda 'Factura verificable en la sede electronica de la AEAT'
   *   o 'VERI*FACTU' como texto corto
   * - Huella SHA-256 en pie de pagina (opcional configurable)
   *
   * @param string $pdf_path Path al PDF de factura generado
   * @param VeriFactuInvoiceRecord $record Registro asociado
   * @return string Path al PDF modificado
   */
  public function addVeriFactuElements(
    string $pdf_path,
    VeriFactuInvoiceRecord $record
  ): string;

  /**
   * Genera un PDF de factura completo desde cero con todos
   * los elementos VeriFactu incluidos.
   */
  public function generateCompleteInvoicePdf(
    int $invoice_id
  ): string;

}

 
4. API REST Endpoints
El módulo expone endpoints REST para administración, consulta y operaciones VeriFactu. Todos los endpoints requieren autenticación y están filtrados por tenant_id (multi-tenant).
4.1 Endpoints de Administración
Método	Endpoint	Descripción	Permisos
GET	/api/v1/verifactu/config	Obtener configuración VeriFactu del tenant	administer verifactu
PUT	/api/v1/verifactu/config	Actualizar configuración (NIF, cert, serie...)	administer verifactu
POST	/api/v1/verifactu/config/certificate	Subir certificado digital .p12	administer verifactu
GET	/api/v1/verifactu/config/certificate/status	Estado del certificado (vigencia, titular)	administer verifactu
POST	/api/v1/verifactu/config/test-connection	Test de conexión con AEAT (entorno pruebas)	administer verifactu
GET	/api/v1/verifactu/declaration	Obtener declaración responsable del SIF	administer verifactu

4.2 Endpoints de Registros
Método	Endpoint	Descripción	Permisos
GET	/api/v1/verifactu/records	Listar registros de facturación (paginado)	view verifactu records
GET	/api/v1/verifactu/records/{id}	Detalle de un registro	view verifactu records
GET	/api/v1/verifactu/records/{id}/xml	Descargar XML del registro	view verifactu records
GET	/api/v1/verifactu/records/{id}/qr	Descargar imagen QR	view verifactu records
POST	/api/v1/verifactu/records/create-alta	Crear registro de alta manualmente	create verifactu records
POST	/api/v1/verifactu/records/create-anulacion	Crear registro de anulación	create verifactu records

4.3 Endpoints de Remisión
Método	Endpoint	Descripción	Permisos
POST	/api/v1/verifactu/remision/send	Enviar registros pendientes a AEAT	send verifactu remision
GET	/api/v1/verifactu/remision/batches	Listar lotes de envío	view verifactu remision
GET	/api/v1/verifactu/remision/batches/{id}	Detalle de lote con respuesta AEAT	view verifactu remision
POST	/api/v1/verifactu/remision/retry/{batch_id}	Reintentar lote fallido	send verifactu remision
GET	/api/v1/verifactu/remision/query	Consultar registros en AEAT	view verifactu remision

4.4 Endpoints de Auditoría
Método	Endpoint	Descripción	Permisos
GET	/api/v1/verifactu/chain/status	Estado de la cadena hash (integridad)	audit verifactu
POST	/api/v1/verifactu/chain/verify	Verificar integridad de la cadena completa	audit verifactu
GET	/api/v1/verifactu/events	Log de eventos del SIF (paginado)	audit verifactu
GET	/api/v1/verifactu/dashboard	Resumen: registros, remisiones, errores	view verifactu dashboard

 
5. Flujos de Automatización (ECA)
Los flujos ECA (Events-Conditions-Actions) automatizan las operaciones VeriFactu sin intervención manual. Se integran con el sistema existente de facturación (doc 134) y el motor de automatización del ecosistema (doc 06).
5.1 ECA-VF-001: Generación Automática de Registro de Alta
Componente	Detalle
ID	ECA-VF-001
Trigger	Creación/actualización de billing_invoice con status = 'paid'
Condition 1	La factura NO tiene ya un verifactu_invoice_record con record_type = 'alta'
Condition 2	El tenant tiene verifactu_tenant_config con modo_verifactu = TRUE
Condition 3	La factura tiene todos los campos obligatorios (NIF, importe, tipo)
Action 1	VeriFactuRecordService::createAltaFromInvoice($invoice_id)
Action 2	VeriFactuQrService::generateAndSaveQr($record)
Action 3	VeriFactuPdfService::addVeriFactuElements($pdf, $record)
Action 4	Queue item en 'verifactu_remision' si remision_automatica = TRUE
Action 5	VeriFactuEventLogService::log('RECORD_CREATE', $record)
Error Handler	Log en watchdog + Notificar admin vía email + Marcar factura con flag 'verifactu_error'

5.2 ECA-VF-002: Anulación de Registro
Componente	Detalle
ID	ECA-VF-002
Trigger	Actualización de billing_invoice con status = 'void'
Condition 1	La factura TIENE un verifactu_invoice_record con record_type = 'alta' y remision_status = 'accepted'
Action 1	VeriFactuRecordService::createAnulacion($invoice_id, $motivo)
Action 2	Queue item en 'verifactu_remision_anulacion'
Action 3	VeriFactuEventLogService::log('RECORD_CANCEL', $record)

5.3 ECA-VF-003: Procesamiento de Cola de Remisión (Cron)
Componente	Detalle
ID	ECA-VF-003
Trigger	Cron cada 60 segundos (respetando control de flujo AEAT)
Condition 1	Hay items en cola 'verifactu_remision' o 'verifactu_remision_anulacion'
Condition 2	Han pasado >= 60 segundos desde el último envío exitoso
Action 1	Agrupar hasta 1000 registros del mismo tenant
Action 2	VeriFactuRemisionService::sendAltas() o sendAnulaciones()
Action 3	Actualizar remision_status de cada registro según respuesta
Action 4	Si respuesta parcial: marcar rechazados para subsanación
Action 5	Log evento REMISION_OK o REMISION_ERROR
Retry Logic	Exponential backoff: 1min, 5min, 15min, 1h, 6h. Máximo 5 reintentos. Marca incidencia='S' en reintento.

5.4 ECA-VF-004: Verificación de Integridad de Cadena
Componente	Detalle
ID	ECA-VF-004
Trigger	Cron diario a las 03:00 UTC + Manual desde admin
Action 1	VeriFactuHashService::verifyChainIntegrity($tenant_id)
Action 2 (OK)	VeriFactuEventLogService::log('CHAIN_VERIFY', resultado OK)
Action 2 (FAIL)	VeriFactuEventLogService::log('CHAIN_BREAK', detalle de rotura)
Action 3 (FAIL)	Notificación URGENTE al admin: email + Slack (si configurado)
Action 4 (FAIL)	Bloquear generación de nuevos registros hasta resolución

5.5 ECA-VF-005: Alerta de Caducidad de Certificado
Componente	Detalle
ID	ECA-VF-005
Trigger	Cron semanal (lunes 09:00)
Condition	verifactu_tenant_config.certificado_expiry < NOW() + 30 días
Action 1	Notificar admin del tenant: 'Su certificado digital caduca en X días'
Action 2	Si < 7 días: Notificación URGENTE + Banner en dashboard
Action 3	Si caducado: Desactivar remisión automática + Log CERT_EXPIRED

 
6. Estructura XML AEAT
Los mensajes XML deben cumplir estrictamente los esquemas XSD publicados por la AEAT. La codificación es UTF-8. El namespace principal es sum1 (SuministroInformacion). A continuación se documenta la estructura para que Claude Code pueda implementar el VeriFactuXmlService.
6.1 XML de Registro de Alta (Estructura)
<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
  xmlns:sum1="https://www2.agenciatributaria.gob.es/...
    /SuministroInformacion.xsd">
  <soapenv:Header/>
  <soapenv:Body>
    <sum1:RegFactuSistemaFacturacion>
      <sum1:Cabecera>
        <sum1:ObligadoEmision>
          <sum1:NombreRazon>{nombre_razon}</sum1:NombreRazon>
          <sum1:NIF>{nif_emisor}</sum1:NIF>
        </sum1:ObligadoEmision>
        <sum1:RemisionVoluntaria>S</sum1:RemisionVoluntaria>
        <sum1:RemisionRequerimiento>N</sum1:RemisionRequerimiento>
      </sum1:Cabecera>
      <sum1:RegistroFactura>
        <sum1:RegistroAlta>
          <sum1:IDFactura>
            <sum1:IDEmisorFactura>{nif_emisor}</sum1:IDEmisorFactura>
            <sum1:NumSerieFactura>{serie+num}</sum1:NumSerieFactura>
            <sum1:FechaExpedicionFactura>{YYYY-MM-DD}</sum1:FechaExpedicionFactura>
          </sum1:IDFactura>
          <sum1:NombreRazonEmisor>{nombre_razon}</sum1:NombreRazonEmisor>
          <sum1:TipoFactura>{F1|F2|R1...}</sum1:TipoFactura>
          <sum1:DescripcionOperacion>{descripcion}</sum1:DescripcionOperacion>
          <sum1:Desglose>
            <sum1:DetalleDesglose>
              <sum1:ClaveRegimen>{01}</sum1:ClaveRegimen>
              <sum1:CalificacionOperacion>{S1}</sum1:CalificacionOperacion>
              <sum1:TipoImpositivo>{21.00}</sum1:TipoImpositivo>
              <sum1:BaseImponible>{100.00}</sum1:BaseImponible>
              <sum1:CuotaRepercutida>{21.00}</sum1:CuotaRepercutida>
            </sum1:DetalleDesglose>
          </sum1:Desglose>
          <sum1:CuotaTotal>{21.00}</sum1:CuotaTotal>
          <sum1:ImporteTotal>{121.00}</sum1:ImporteTotal>
          <sum1:Encadenamiento>
            <sum1:PrimerRegistro>{S|N}</sum1:PrimerRegistro>
            <!-- Si PrimerRegistro = N -->
            <sum1:RegistroAnterior>
              <sum1:IDEmisorFactura>{nif}</sum1:IDEmisorFactura>
              <sum1:NumSerieFactura>{serie+num anterior}
              </sum1:NumSerieFactura>
              <sum1:FechaExpedicionFactura>{fecha anterior}
              </sum1:FechaExpedicionFactura>
              <sum1:Huella>{hash_anterior_64chars}</sum1:Huella>
            </sum1:RegistroAnterior>
          </sum1:Encadenamiento>
          <sum1:SistemaInformatico>
            <sum1:NombreRazon>{Jaraba Impact S.L.}</sum1:NombreRazon>
            <sum1:NIF>{NIF_productor}</sum1:NIF>
            <sum1:NombreSistemaInformatico>
              JarabaImpactSaaS
            </sum1:NombreSistemaInformatico>
            <sum1:IdSistemaInformatico>{ID_version}
            </sum1:IdSistemaInformatico>
            <sum1:Version>{1.0}</sum1:Version>
            <sum1:NumeroInstalacion>{tenant_id}
            </sum1:NumeroInstalacion>
          </sum1:SistemaInformatico>
          <sum1:FechaHoraHusoGenRegistro>
            {2027-01-15T10:30:00+01:00}
          </sum1:FechaHoraHusoGenRegistro>
          <sum1:Huella>{sha256_hex_64chars}</sum1:Huella>
        </sum1:RegistroAlta>
      </sum1:RegistroFactura>
    </sum1:RegFactuSistemaFacturacion>
  </soapenv:Body>
</soapenv:Envelope>

6.2 Tipos de Factura
Código	Tipo	Uso
F1	Factura completa (Art. 6, 7.2, 7.3 RD 1619/2012)	Facturas estándar con todos los datos
F2	Factura simplificada (Art. 6, 7.1 RD 1619/2012)	Tickets y facturas simples < 400 EUR
F3	Factura emitida como sustitutiva de simplificadas	Factura completa que sustituye simplificadas
R1	Factura rectificativa por Art. 80.1, 80.2, 80.6 LIVA	Devoluciones, descuentos
R2	Factura rectificativa por Art. 80.3 LIVA	Créditos incobrables
R3	Factura rectificativa por Art. 80.4 LIVA	Auto-factura
R4	Factura rectificativa otros	Otras rectificativas
R5	Factura rectificativa en facturas simplificadas	Rectificativa de ticket

6.3 Claves de Régimen IVA
Clave	Régimen	Uso común en Jaraba
01	Operación con régimen general	Suscripciones SaaS, servicios
02	Exportación	Ventas fuera UE
03	Operaciones a las que se aplique REBU	Bienes usados
05	Régimen especial de agencias de viaje	No aplica
06	Régimen especial de grupo de entidades	Holding
07	Régimen especial de criterio de caja	Pymes acogidas
08	Operaciones sujetas a IGIC/IPSI	Canarias/Ceuta/Melilla
14	Prestaciones de servicios Art. 69 LIVA	Servicios internacionales
15	Op. a las que se aplique alguna de las franquicias IVA	Pequeñas empresas

 
7. Permisos y RBAC
El módulo define permisos granulares que se integran con el sistema RBAC existente (doc 04). Los permisos se asignan por rol dentro de cada Group (tenant).
7.1 Definición de Permisos
Permiso (machine name)	Descripción	Roles por defecto
administer verifactu	Configurar VeriFactu: certificados, serie, modo	admin, platform_admin
view verifactu records	Ver registros de facturación VeriFactu	admin, accountant, manager
create verifactu records	Crear registros manualmente	admin, accountant
send verifactu remision	Enviar remisiones a AEAT	admin
view verifactu remision	Ver estado de remisiones	admin, accountant
audit verifactu	Verificar integridad y ver logs	admin, auditor, platform_admin
view verifactu dashboard	Acceder al dashboard VeriFactu	admin, accountant, manager

7.2 Matriz de Acceso por Rol
Acción	Platform Admin	Tenant Admin	Accountant	Manager	User
Configurar VeriFactu	SI	SI	NO	NO	NO
Subir certificado	SI	SI	NO	NO	NO
Ver registros	SI	SI	SI	SI	NO
Crear registro manual	SI	SI	SI	NO	NO
Enviar a AEAT	SI	SI	NO	NO	NO
Ver remisiones	SI	SI	SI	NO	NO
Auditar cadena	SI	SI	NO	NO	NO
Dashboard VeriFactu	SI	SI	SI	SI	NO

 
8. Estructura del Módulo Drupal
Estructura de archivos del módulo jaraba_verifactu siguiendo convenciones estándar de Drupal 11:
modules/custom/jaraba_verifactu/
+-- jaraba_verifactu.info.yml
+-- jaraba_verifactu.module
+-- jaraba_verifactu.permissions.yml
+-- jaraba_verifactu.services.yml
+-- jaraba_verifactu.routing.yml
+-- jaraba_verifactu.links.menu.yml
+-- jaraba_verifactu.links.task.yml
+-- jaraba_verifactu.install
+-- config/
|   +-- install/
|   |   +-- jaraba_verifactu.settings.yml
|   +-- schema/
|       +-- jaraba_verifactu.schema.yml
+-- src/
|   +-- Entity/
|   |   +-- VeriFactuInvoiceRecord.php
|   |   +-- VeriFactuEventLog.php
|   |   +-- VeriFactuRemisionBatch.php
|   |   +-- VeriFactuTenantConfig.php
|   +-- Service/
|   |   +-- VeriFactuHashService.php
|   |   +-- VeriFactuRecordService.php
|   |   +-- VeriFactuQrService.php
|   |   +-- VeriFactuXmlService.php
|   |   +-- VeriFactuRemisionService.php
|   |   +-- VeriFactuPdfService.php
|   |   +-- VeriFactuEventLogService.php
|   |   +-- VeriFactuChainVerifier.php
|   +-- Controller/
|   |   +-- VeriFactuAdminController.php
|   |   +-- VeriFactuRecordController.php
|   |   +-- VeriFactuRemisionController.php
|   |   +-- VeriFactuDashboardController.php
|   +-- Form/
|   |   +-- VeriFactuConfigForm.php
|   |   +-- VeriFactuCertificateForm.php
|   |   +-- VeriFactuManualRecordForm.php
|   +-- Plugin/
|   |   +-- QueueWorker/
|   |       +-- VeriFactuRemisionWorker.php
|   +-- Exception/
|   |   +-- VeriFactuChainException.php
|   |   +-- VeriFactuValidationException.php
|   |   +-- VeriFactuRemisionException.php
|   +-- EventSubscriber/
|       +-- VeriFactuInvoiceSubscriber.php
+-- templates/
|   +-- verifactu-dashboard.html.twig
|   +-- verifactu-record-detail.html.twig
+-- tests/
|   +-- src/
|       +-- Unit/
|       |   +-- VeriFactuHashServiceTest.php
|       |   +-- VeriFactuXmlServiceTest.php
|       +-- Kernel/
|       |   +-- VeriFactuRecordServiceTest.php
|       |   +-- VeriFactuChainIntegrityTest.php
|       +-- Functional/
|           +-- VeriFactuApiTest.php
|           +-- VeriFactuRemisionTest.php
+-- xsd/
    +-- SuministroInformacion.xsd
    +-- SuministroLR.xsd

8.1 Archivo jaraba_verifactu.info.yml
name: 'Jaraba VeriFactu'
type: module
description: 'Sistema de Facturacion Verificable VERI*FACTU'
core_version_requirement: ^11
package: 'Jaraba Impact'
dependencies:
  - drupal:node
  - drupal:user
  - drupal:file
  - group:group
  - eca:eca
  - jaraba_core:jaraba_core
configure: jaraba_verifactu.admin_config

8.2 Archivo jaraba_verifactu.services.yml
services:
  jaraba_verifactu.hash_service:
    class: Drupal\jaraba_verifactu\Service\VeriFactuHashService
    arguments: ['@entity_type.manager', '@logger.factory']

  jaraba_verifactu.record_service:
    class: Drupal\jaraba_verifactu\Service\VeriFactuRecordService
    arguments:
      - '@jaraba_verifactu.hash_service'
      - '@jaraba_verifactu.qr_service'
      - '@jaraba_verifactu.xml_service'
      - '@entity_type.manager'
      - '@queue'
      - '@logger.factory'

  jaraba_verifactu.qr_service:
    class: Drupal\jaraba_verifactu\Service\VeriFactuQrService
    arguments: ['@file_system', '@entity_type.manager']

  jaraba_verifactu.xml_service:
    class: Drupal\jaraba_verifactu\Service\VeriFactuXmlService
    arguments: ['@module_handler']

  jaraba_verifactu.remision_service:
    class: Drupal\jaraba_verifactu\Service\VeriFactuRemisionService
    arguments:
      - '@jaraba_verifactu.xml_service'
      - '@entity_type.manager'
      - '@logger.factory'
      - '@state'

  jaraba_verifactu.pdf_service:
    class: Drupal\jaraba_verifactu\Service\VeriFactuPdfService
    arguments:
      - '@jaraba_verifactu.qr_service'
      - '@entity_type.manager'

  jaraba_verifactu.event_log_service:
    class: Drupal\jaraba_verifactu\Service\VeriFactuEventLogService
    arguments:
      - '@jaraba_verifactu.hash_service'
      - '@entity_type.manager'
      - '@current_user'
      - '@request_stack'

 
9. Tests Obligatorios
VeriFactu es un módulo de compliance legal. La cobertura de tests es CRÍTICA y no negociable. Se definen los siguientes test suites:
9.1 Tests Unitarios (VeriFactuHashServiceTest)
Test	Descripción	Datos de Ejemplo
testCalculateAltaHash	Hash de registro de alta con datos conocidos	NIF=B12345678, Num=F-001, Fecha=2027-01-15, Tipo=F1, Cuota=21.00, Total=121.00
testCalculateAltaHashFirstRecord	Hash del primer registro (sin hash anterior)	PrimerRegistro=S, hash_anterior=null
testCalculateAnulacionHash	Hash de registro de anulación	Solo campos de anulación
testCalculateEventoHash	Hash de evento del SIF	Evento RECORD_CREATE
testHashChainIntegrity	Encadenamiento de 5 registros consecutivos	Verificar que hash N contiene hash N-1
testHashDeterministic	Mismo input produce mismo output	Ejecutar 100 veces y comparar
testHashUtf8Encoding	Caracteres especiales en nombre/descripción	Razón social con ñ, acentos, €

9.2 Tests de Kernel
Test	Descripción
testCreateAltaRecord	Crear registro de alta y verificar todos los campos en BD
testCreateAnulacionRecord	Crear anulación vinculada a un alta existente
testChainVerification	Generar 10 registros y verificar cadena completa
testChainBreakDetection	Alterar un hash y verificar que se detecta rotura
testMultiTenantIsolation	Tenant A no puede ver registros de Tenant B
testTenantConfigCRUD	Crear, leer, actualizar configuración por tenant
testQrGeneration	Generar QR y verificar URL codificada
testXmlValidation	Generar XML y validar contra XSD oficial
testEventLogImmutability	Verificar que eventos no se pueden editar/borrar

9.3 Tests Funcionales / End-to-End
Test	Descripción
testFullInvoiceToAeatFlow	Crear factura -> Generar registro -> QR -> PDF -> Cola remisión
testAeatConnectionTest	Conectar con entorno de pruebas AEAT (requiere certificado test)
testAeatAltaResponse	Enviar alta a AEAT pruebas y procesar respuesta
testAeatAnulacionResponse	Enviar anulación y verificar aceptación
testApiPermissions	Verificar que endpoints respetan RBAC
testConcurrentRecordCreation	Crear 50 registros simultáneos y verificar cadena intacta
testRetryMechanism	Simular fallo de red y verificar reintento con incidencia='S'

 
10. Componentes Reutilizables del Ecosistema
VeriFactu se beneficia de componentes ya especificados en el ecosistema Jaraba. A continuación se detalla qué se reutiliza y qué adaptaciones requiere:
Componente Existente	Documento	Reutilización	Adaptación Necesaria
IntegrityService (SHA-256)	80_Traceability	80%	Cambiar datos de entrada: de lotes a facturas. Mantener lógica hash.
FirmaDigitalService	89_Firma_Digital_PAdES	50%	VeriFactu no firma PDFs sino XML. Adaptar a firma SOAP con PKCS#12.
CertificadoPdfService	80_Traceability	70%	Reutilizar generación QR en PDF. Cambiar datos y layout.
billing_invoice entity	134_Stripe_Billing	90%	La factura base ya existe. VeriFactu añade campos calculados.
ECA Automation Flows	06_Core_Flujos_ECA	85%	Mismos patrones Event-Condition-Action. Nuevos triggers específicos.
QR Dynamic System	65/81_QR_Dynamic	50%	Reutilizar generación QR. Cambiar URL destino a sede AEAT.
Audit Log pattern	FOC_financial_transaction	90%	Append-only pattern idéntico. Nuevos campos para VeriFactu.

 
11. Roadmap de Implementación
La implementación se organiza en 4 sprints de 2 semanas cada uno, para un total de 8 semanas. Prioridad máxima por deadline legal.
11.1 Sprint 1: Core Hash + Entidades (Semanas 1-2)
Tarea	Horas	Prioridad
Crear entidades: verifactu_invoice_record, verifactu_event_log	16-20	P0
Crear entidad: verifactu_remision_batch	8-10	P0
Crear entidad config: verifactu_tenant_config	8-10	P0
Implementar VeriFactuHashService (SHA-256 + encadenamiento)	16-20	P0
Tests unitarios de hash (con vectores de prueba AEAT)	8-12	P0
TOTAL SPRINT 1	56-72	

11.2 Sprint 2: XML + QR + PDF (Semanas 3-4)
Tarea	Horas	Prioridad
Implementar VeriFactuXmlService (construcción XML conforme XSD)	16-24	P0
Implementar VeriFactuQrService (QR con URL AEAT)	8-10	P0
Implementar VeriFactuPdfService (integrar QR + leyenda en PDF)	8-12	P1
Implementar VeriFactuRecordService (orquestador principal)	12-16	P0
ECA-VF-001: Auto-generación de registro al pagar factura	8-10	P0
Tests de kernel (entidades + cadena)	8-12	P0
TOTAL SPRINT 2	60-84	

11.3 Sprint 3: Remisión AEAT + API (Semanas 5-6)
Tarea	Horas	Prioridad
Implementar VeriFactuRemisionService (SOAP + certificado)	20-28	P0
Queue Worker para procesamiento asíncrono	8-12	P0
Control de flujo (60s entre envíos) + retry logic	8-10	P0
API REST endpoints (admin + records + remisión)	12-16	P1
ECA-VF-002 a ECA-VF-005 (anulación, cron, verificación, alertas)	8-12	P1
Test de conexión con entorno pruebas AEAT	8-10	P0
TOTAL SPRINT 3	64-88	

11.4 Sprint 4: Dashboard + QA + Go-Live (Semanas 7-8)
Tarea	Horas	Prioridad
Dashboard VeriFactu en admin (Twig templates)	10-14	P1
Formularios de configuración (certificado, serie, modo)	8-12	P1
Declaración Responsable del SIF (generación automática)	4-6	P1
Tests E2E completos con AEAT pruebas	12-16	P0
Documentación de usuario (manual admin VeriFactu)	4-6	P2
Security review (certificados, passwords cifradas)	4-6	P1
Bug fixing y polish	8-12	P0
TOTAL SPRINT 4	50-72	

11.5 Resumen de Inversión
Concepto	Horas Mín	Horas Máx	Coste Min (45 EUR/h)	Coste Máx (45 EUR/h)
Sprint 1: Core Hash + Entidades	56	72	2.520 EUR	3.240 EUR
Sprint 2: XML + QR + PDF	60	84	2.700 EUR	3.780 EUR
Sprint 3: Remisión AEAT + API	64	88	2.880 EUR	3.960 EUR
Sprint 4: Dashboard + QA	50	72	2.250 EUR	3.240 EUR
TOTAL	230	316	10.350 EUR	14.220 EUR

[OK] Comparar con sanción de 50.000 EUR/ejercicio. El ROI de esta implementación es inmediato.

 
12. Dependencias y Requisitos Previos
12.1 Dependencias Composer
composer require chillerlan/php-qrcode:^5.0
# QR Code generation - licencia MIT

# Las siguientes son nativas de PHP 8.3:
# ext-hash (SHA-256) - incluida por defecto
# ext-dom (DOMDocument) - incluida por defecto
# ext-soap (SoapClient) - verificar que esta habilitada
# ext-openssl (certificados) - incluida por defecto

12.2 Esquemas XSD de la AEAT
Descargar los esquemas XSD oficiales del portal de desarrolladores de la AEAT y colocarlos en el directorio xsd/ del módulo:
URL: https://sede.agenciatributaria.gob.es/Sede/iva/sistemas-informaticos-facturacion-verifactu/informacion-tecnica.html
Archivos necesarios: SuministroInformacion.xsd, SuministroLR.xsd, validaciones y errores (documento de validaciones v0.9.1+)

12.3 Certificado Digital
Se requiere un certificado electrónico cualificado en formato PKCS#12 (.p12 o .pfx) para la remisión a la AEAT. Opciones:
Tipo Certificado	Emisor	Coste	Validez
Certificado de representante de persona jurídica	FNMT-RCM	Gratuito	2-4 años
Certificado de persona física	FNMT-RCM / DNIe	Gratuito	4 años
Certificado cualificado de sello	Prestador cualificado	100-300 EUR/año	2 años

12.4 Entorno de Pruebas AEAT
La AEAT proporciona un entorno de pruebas (pre-producción) accesible con certificados de prueba. Activar mediante configuración: entorno_aeat = 'pruebas'.
Endpoint pruebas: https://prewww2.aeat.es/...
Endpoint producción: https://www2.agenciatributaria.gob.es/...

 
13. Declaración Responsable del SIF
El RD 1007/2023 exige que el productor del SIF emita una Declaración Responsable accesible desde el propio sistema. El módulo jaraba_verifactu debe generar y mostrar esta declaración con los siguientes datos:
Campo Declaración	Valor
Nombre del SIF	JarabaImpactSaaS
Versión	1.0 (actualizar con cada release)
NIF Productor	NIF de Jaraba Impact S.L.
Nombre Productor	Jaraba Impact S.L.
Tipo de sistema	VERI*FACTU (Sistema de emisión de facturas verificables)
Cumplimiento	El sistema cumple con las especificaciones de la Orden Ministerial HAC/1177/2024
Hash encadenado	SHA-256 conforme Anexo II
Remisión	Voluntaria automática a la sede electrónica de la AEAT
QR	Conforme a especificaciones de la sede electrónica de la AEAT
Conservación	Registros conservados durante el plazo legal mínimo (4 años)
Accesibilidad	Disponible en el sistema bajo la ruta /admin/verifactu/declaracion-responsable

 
14. Conclusión y Próximos Pasos
Este documento contiene toda la especificación técnica necesaria para que Claude Code o el equipo EDI Google Antigravity implemente el módulo jaraba_verifactu de principio a fin. Los elementos críticos son:
1. Entidades de base de datos: 4 entidades con todos los campos, tipos, restricciones e índices definidos.
2. Servicios PHP: 6 servicios con interfaces completas, parámetros, tipos de retorno y documentación de cada método.
3. API REST: 21 endpoints con métodos, rutas, descripciones y permisos requeridos.
4. Flujos ECA: 5 automatizaciones con triggers, condiciones, acciones y manejo de errores.
5. Estructura XML AEAT: Template completo del mensaje SOAP con todos los campos y namespaces.
6. Tests: 23 tests definidos cubriendo unitarios, kernel y funcionales.
7. Estructura de módulo: Arbol de archivos completo, services.yml, info.yml, y dependencias.

[CRITICO] ACCION INMEDIATA: Iniciar Sprint 1 (Core Hash + Entidades) como primera tarea del roadmap de implementación de la plataforma Jaraba Impact.

--- Fin del Documento ---

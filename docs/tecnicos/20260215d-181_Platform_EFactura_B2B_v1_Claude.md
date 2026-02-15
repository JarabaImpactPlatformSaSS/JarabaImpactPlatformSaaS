
ECOSISTEMA JARABA
181
Platform E-Factura B2B
Ley Crea y Crece - Facturación Electrónica Obligatoria
Módulo jaraba_einvoice_b2b

Metadata	Valor
Documento	181_Platform_EFactura_B2B_v1
Versión	1.0
Fecha	15 febrero 2026
Autor	Claude (Anthropic) para Ecosistema Jaraba
Estado	Ready for Development - Claude Code
Normativa	Ley 18/2022 (Crea y Crece), EN 16931, UBL 2.1
Prioridad	P2 - Q1 2027 (pendiente reglamento)
Dependencias	Doc 134 (Billing), Doc 179 (VeriFactu), Doc 180 (Facturae)
Módulo Drupal	jaraba_einvoice_b2b
 
1. Alcance y Marco Legal
Este documento especifica la implementación del módulo jaraba_einvoice_b2b para la facturación electrónica obligatoria entre empresas (B2B) conforme a la Ley 18/2022, de creación y crecimiento de empresas (Ley Crea y Crece).
1.1. Estado Regulatorio (Febrero 2026)
CRITICAL: A fecha de febrero de 2026, el reglamento de desarrollo de la Ley Crea y Crece para facturación electrónica B2B AÚN NO HA SIDO PUBLICADO. Este documento se basa en el borrador del reglamento y las especificaciones técnicas conocidas. Puede requerir actualizaciones cuando se publique el reglamento definitivo.

1.2. Normativa Aplicable
Normativa	Descripción	Estado
Ley 18/2022 (Crea y Crece)	Base legal e-factura B2B obligatoria	Publicada (Sep 2022)
Reglamento desarrollo	Plazos, requisitos técnicos, formatos	PENDIENTE publicación
Orden Ministerial técnica	Detalles técnicos Solución Pública	PENDIENTE publicación
EN 16931	Norma europea semántica factura	Vigente
UBL 2.1 (ISO/IEC 19845)	Formato XML Universal Business Language	Vigente
Facturae 3.2.2	Formato nacional español	Vigente (soporte vía doc 180)
Directiva ViDA (UE)	VAT in the Digital Age	En tramitación

1.3. Plazos Estimados
Fase	Obligados	Plazo estimado
Fase 1	Empresas > 8M EUR facturación anual	12 meses desde publicación Orden Ministerial
Fase 2	Resto empresas y autónomos	24 meses desde publicación Orden Ministerial
Estado pago	Todos - comunicar estados de pago	36-48 meses desde publicación

NOTA: Si la Orden Ministerial se publica en H2 2026, las grandes empresas estarían obligadas hacia H2 2027 y el resto hacia H2 2028. Jaraba Impact como SaaS de volumen medio caería en Fase 2.

1.4. Modelo de Plataformas
España implementará un modelo híbrido de doble plataforma:
•	Solución Pública de Facturación Electrónica (SPFE): Operada por la AEAT. Repositorio central que recibe copia de todas las facturas y estados de pago. Gratuita. Formato XML UBL alineado con EN 16931.
•	Plataformas Privadas Certificadas: Proveedores privados autorizados para intercambiar facturas entre empresas. Deben cumplir requisitos de interoperabilidad y remitir copia a la SPFE.

El ecosistema Jaraba actuará como plataforma privada para sus tenants, remitiendo copias a la SPFE.
1.5. Diferencia con VeriFactu y Facturae
Aspecto	VeriFactu (Doc 179)	Facturae B2G (Doc 180)	E-Factura B2B (Doc 181)
Normativa	Ley Antifraude	Ley 25/2013	Ley Crea y Crece
Objetivo	Integridad software	Factura a AAPP	Factura entre empresas
Ámbito	B2B + B2C	Solo B2G	Solo B2B
Formato	Registro hash	Facturae XML	UBL/Facturae XML
Destino	AEAT (registro)	FACe (factura)	SPFE + Receptor
Obligatorio	Ene/Jul 2027	Desde 2015	~2027-2028
 
2. Entidades de Datos
2.1. einvoice_document
Registro de cada factura electrónica B2B emitida o recibida.
Campo	Tipo	Requerido	Descripción
id	INT UNSIGNED AUTO_INCREMENT	PK	Identificador único
uuid	VARCHAR(128)	UNIQUE	UUID Drupal
tenant_id	INT UNSIGNED	FK groups.id	Tenant multi-tenant
direction	VARCHAR(10)	NOT NULL	outbound (emitida) | inbound (recibida)
invoice_id	INT UNSIGNED	FK NULL	billing_invoice.id si outbound
facturae_document_id	INT UNSIGNED	FK NULL	facturae_document.id si tiene Facturae
format	VARCHAR(20)	NOT NULL	ubl_2.1 | facturae_3.2.2 | cii_d16b
xml_content	LONGTEXT	NOT NULL	XML completo de la factura
xml_signed	LONGTEXT	NULL	XML firmado si aplica
file_id	INT UNSIGNED	FK file_managed	Archivo XML almacenado
invoice_number	VARCHAR(50)	NOT NULL	Número de factura
invoice_date	DATE	NOT NULL	Fecha expedición
due_date	DATE	NULL	Fecha vencimiento
seller_nif	VARCHAR(20)	NOT NULL	NIF emisor
seller_name	VARCHAR(255)	NOT NULL	Nombre emisor
buyer_nif	VARCHAR(20)	NOT NULL	NIF receptor
buyer_name	VARCHAR(255)	NOT NULL	Nombre receptor
currency_code	VARCHAR(3)	DEFAULT 'EUR'	Moneda ISO 4217
total_without_tax	DECIMAL(12,2)	NOT NULL	Base imponible total
total_tax	DECIMAL(12,2)	NOT NULL	Total impuestos
total_amount	DECIMAL(12,2)	NOT NULL	Total factura
tax_breakdown_json	JSON	NOT NULL	Desglose impuestos por tipo/tipo
line_items_json	JSON	NOT NULL	Líneas de detalle
payment_terms_json	JSON	NULL	Condiciones de pago
delivery_status	VARCHAR(20)	DEFAULT 'pending'	pending|sent|delivered|accepted|rejected
delivery_method	VARCHAR(20)	NULL	spfe|platform|email|peppol
delivery_timestamp	DATETIME	NULL	Momento de entrega
delivery_response_json	JSON	NULL	Respuesta del sistema destino
spfe_submission_id	VARCHAR(100)	NULL	ID envío a Solución Pública AEAT
spfe_status	VARCHAR(20)	DEFAULT 'not_sent'	not_sent|sent|accepted|rejected
spfe_response_json	JSON	NULL	Respuesta SPFE
payment_status	VARCHAR(20)	DEFAULT 'pending'	pending|partial|paid|overdue|disputed
payment_status_date	DATETIME	NULL	Última actualización estado pago
payment_status_communicated	BOOLEAN	DEFAULT FALSE	¿Comunicado a SPFE?
validation_status	VARCHAR(20)	DEFAULT 'pending'	pending|valid|invalid
validation_errors_json	JSON	NULL	Errores de validación
status	VARCHAR(20)	DEFAULT 'draft'	draft|validated|signed|sent|delivered|error
created	DATETIME	NOT NULL	Fecha creación
changed	DATETIME	NOT NULL	Última modificación
uid	INT UNSIGNED	FK users.uid	Usuario creador

2.2. einvoice_tenant_config
Configuración B2B específica por tenant.
Campo	Tipo	Descripción
id	INT UNSIGNED AUTO_INCREMENT	PK
tenant_id	INT UNSIGNED	FK groups.id (UNIQUE)
nif_emisor	VARCHAR(20)	NIF/CIF del emisor
nombre_razon	VARCHAR(255)	Razón social
address_json	JSON	Dirección fiscal estructurada
preferred_format	VARCHAR(20)	ubl_2.1 (default) | facturae_3.2.2
spfe_enabled	BOOLEAN	¿Remisión automática a SPFE?
spfe_environment	VARCHAR(10)	test | production
spfe_credentials_json	JSON	Credenciales SPFE (cifradas)
peppol_enabled	BOOLEAN	¿Conectado a red Peppol?
peppol_participant_id	VARCHAR(100)	Peppol Participant ID (si aplica)
auto_send_on_paid	BOOLEAN	¿Enviar automáticamente al pagarse?
payment_status_tracking	BOOLEAN	¿Rastrear y comunicar estados de pago?
inbound_email	VARCHAR(255)	Email para recepción de facturas entrantes
inbound_webhook_url	VARCHAR(500)	Webhook para facturas entrantes
default_payment_terms_days	INT	Días vencimiento por defecto
certificate_file_id	INT UNSIGNED	FK file_managed (compartido con facturae/verifactu)
certificate_password_encrypted	BLOB	Password cifrada
active	BOOLEAN	DEFAULT TRUE
created	DATETIME	Fecha creación
changed	DATETIME	Última modificación

2.3. einvoice_delivery_log
Log inmutable de envíos, recepciones y comunicaciones de estado de pago.
Campo	Tipo	Descripción
id	INT UNSIGNED AUTO_INCREMENT	PK
tenant_id	INT UNSIGNED	FK groups.id
einvoice_document_id	INT UNSIGNED	FK einvoice_document.id
operation	VARCHAR(30)	send|receive|payment_status|spfe_submit|spfe_query|validation
channel	VARCHAR(20)	spfe|email|peppol|platform|api
request_payload	LONGTEXT	Petición enviada
response_payload	LONGTEXT	Respuesta recibida
response_code	VARCHAR(20)	Código resultado
http_status	INT	HTTP status
duration_ms	INT	Duración operación
error_detail	TEXT	Detalle error
user_id	INT UNSIGNED	FK users.uid
created	DATETIME	Timestamp

2.4. einvoice_payment_event
Registro de eventos de pago asociados a facturas B2B, requerido por la Ley Crea y Crece para el control de morosidad.
Campo	Tipo	Descripción
id	INT UNSIGNED AUTO_INCREMENT	PK
tenant_id	INT UNSIGNED	FK groups.id
einvoice_document_id	INT UNSIGNED	FK einvoice_document.id
event_type	VARCHAR(20)	payment_received | payment_partial | payment_overdue | dispute_opened | dispute_resolved
amount	DECIMAL(12,2)	Importe del evento
payment_date	DATE	Fecha del pago
payment_method	VARCHAR(20)	transfer | card | cash | other
payment_reference	VARCHAR(100)	Referencia del pago
communicated_to_spfe	BOOLEAN	¿Comunicado a SPFE?
communication_timestamp	DATETIME	Fecha comunicación a SPFE
communication_response	JSON	Respuesta SPFE
created	DATETIME	Fecha creación
NOTA: La comunicación de estados de pago es un requisito clave de la Ley Crea y Crece para combatir la morosidad. Cada cambio de estado debe comunicarse a la SPFE dentro del plazo que establezca el reglamento.
 
3. Servicios PHP
3.1. EInvoiceUblService
Generación de facturas en formato UBL 2.1 conforme a la norma europea EN 16931.
3.1.1. Interfaz Pública
interface EInvoiceUblServiceInterface {
  public function buildFromInvoice(int $invoice_id): string;
  public function buildFromData(array $data): string;
  public function buildCreditNote(int $original_id, array $data): string;
  public function convertFromFacturae(string $facturae_xml): string;
  public function validateAgainstSchematron(string $xml): ValidationResult;
  public function parseInbound(string $xml): ParsedInvoice;
}

3.1.2. Estructura UBL 2.1 Invoice
<?xml version="1.0" encoding="UTF-8"?>
<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2"
  xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"
  xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2">
  <cbc:CustomizationID>urn:cen.eu:en16931:2017#compliant#
    urn:fdc:peppol.eu:2017:poacc:billing:3.0</cbc:CustomizationID>
  <cbc:ProfileID>urn:fdc:peppol.eu:2017:poacc:billing:01:1.0</cbc:ProfileID>
  <cbc:ID>FE2026-00042</cbc:ID>
  <cbc:IssueDate>2026-03-15</cbc:IssueDate>
  <cbc:DueDate>2026-04-15</cbc:DueDate>
  <cbc:InvoiceTypeCode>380</cbc:InvoiceTypeCode>  <!-- 380=Invoice, 381=CreditNote -->
  <cbc:DocumentCurrencyCode>EUR</cbc:DocumentCurrencyCode>
  <cac:AccountingSupplierParty>...</cac:AccountingSupplierParty>
  <cac:AccountingCustomerParty>...</cac:AccountingCustomerParty>
  <cac:PaymentMeans>...</cac:PaymentMeans>
  <cac:PaymentTerms>...</cac:PaymentTerms>
  <cac:TaxTotal>...</cac:TaxTotal>
  <cac:LegalMonetaryTotal>...</cac:LegalMonetaryTotal>
  <cac:InvoiceLine>...</cac:InvoiceLine>
</Invoice>

3.1.3. Mapeo EN 16931 Campos Obligatorios
Campo EN 16931	Elemento UBL	Fuente Jaraba
BT-1 Invoice number	cbc:ID	billing_invoice.number
BT-2 Issue date	cbc:IssueDate	billing_invoice.date
BT-3 Invoice type code	cbc:InvoiceTypeCode	380 (invoice) / 381 (credit)
BT-5 Currency code	cbc:DocumentCurrencyCode	EUR (default)
BT-6 VAT accounting date	cbc:TaxPointDate	billing_invoice.tax_date
BT-9 Payment due date	cbc:DueDate	billing_invoice.due_date
BT-10 Buyer reference	cbc:BuyerReference	billing_invoice.po_number
BT-27 Seller name	SupplierParty/Party/PartyName	tenant_config.nombre_razon
BT-29 Seller ID	SupplierParty/PartyIdentification	tenant_config.nif_emisor
BT-31 Seller VAT ID	SupplierParty/PartyTaxScheme/ID	ES + NIF
BT-44 Buyer name	CustomerParty/Party/PartyName	billing_invoice.client_name
BT-46 Buyer ID	CustomerParty/PartyIdentification	client NIF
BT-48 Buyer VAT ID	CustomerParty/PartyTaxScheme/ID	ES + client NIF
BT-106 Sum line net amount	LegalMonetaryTotal/LineExtensionAmount	Calculado
BT-109 Total without VAT	LegalMonetaryTotal/TaxExclusiveAmount	billing.subtotal
BT-110 Total VAT amount	TaxTotal/TaxAmount	billing.tax_total
BT-112 Total with VAT	LegalMonetaryTotal/TaxInclusiveAmount	billing.total
BT-115 Amount due	LegalMonetaryTotal/PayableAmount	billing.amount_due

3.2. EInvoiceFormatConverterService
Servicio de conversión bidireccional entre formatos de factura electrónica.
3.2.1. Interfaz Pública
interface EInvoiceFormatConverterServiceInterface {
  public function facturaeToUbl(string $facturae_xml): string;
  public function ublToFacturae(string $ubl_xml): string;
  public function detectFormat(string $xml): string;  // 'ubl_2.1'|'facturae_3.2.2'|'cii'|'unknown'
  public function convertTo(string $xml, string $target_format): string;
  public function extractSemanticModel(string $xml): EN16931Model;  // Modelo semántico neutral
}

3.2.2. Tabla de Mapeo Facturae ↔ UBL
Concepto	Facturae 3.2.2	UBL 2.1
Número factura	InvoiceHeader/InvoiceNumber	cbc:ID
Fecha	InvoiceIssueData/IssueDate	cbc:IssueDate
NIF emisor	SellerParty/TaxIdentification/TaxIdentificationNumber	AccountingSupplierParty/.../CompanyID
Nombre emisor	SellerParty/LegalEntity/CorporateName	AccountingSupplierParty/.../PartyName/cbc:Name
Base imponible	InvoiceTotals/TotalGrossAmountBeforeTaxes	LegalMonetaryTotal/cbc:TaxExclusiveAmount
Tipo IVA	Tax/TaxRate	TaxSubtotal/TaxCategory/cbc:Percent
Cuota IVA	Tax/TaxAmount	TaxSubtotal/cbc:TaxAmount
Total	InvoiceTotals/InvoiceTotal	LegalMonetaryTotal/cbc:TaxInclusiveAmount
IBAN	PaymentDetails/Installment/AccountToBeCredited/IBAN	PaymentMeans/PayeeFinancialAccount/cbc:ID

3.3. EInvoiceDeliveryService
Orquestador de envío de facturas electrónicas por los diferentes canales disponibles.
3.3.1. Interfaz Pública
interface EInvoiceDeliveryServiceInterface {
  public function send(int $document_id, ?string $channel = NULL): DeliveryResult;
  public function sendToSPFE(int $document_id): SPFEResult;
  public function sendViaEmail(int $document_id, string $email): EmailResult;
  public function sendViaPeppol(int $document_id): PeppolResult;
  public function sendViaPlatform(int $document_id): PlatformResult;
  public function receiveInbound(string $xml, string $channel): int;
  public function getDeliveryStatus(int $document_id): DeliveryStatus;
}

3.3.2. Canales de Entrega
Canal	Descripción	Prioridad	Estado
SPFE	Solución Pública AEAT - copia obligatoria	Obligatorio	Esperando API
Platform	Entrega interna entre tenants Jaraba	Alta	Implementable ya
Email	Envío XML firmado por email (fallback)	Media	Implementable ya
Peppol	Red europea de intercambio e-invoicing	Baja (futuro)	Requiere certificación

NOTA: La integración con la SPFE requiere que la AEAT publique la especificación técnica de su API. Este módulo implementa una capa de abstracción (SPFEClientInterface) que se conectará cuando la API esté disponible. Mientras tanto, se usa un stub que simula el envío.

3.4. EInvoicePaymentStatusService
Gestión y comunicación de estados de pago, requisito específico de la Ley Crea y Crece para combatir la morosidad.
3.4.1. Interfaz Pública
interface EInvoicePaymentStatusServiceInterface {
  public function recordPayment(int $document_id, array $payment_data): int;
  public function updateStatus(int $document_id, string $status): void;
  public function communicateToSPFE(int $event_id): SPFEResult;
  public function getPaymentHistory(int $document_id): array;
  public function getOverdueInvoices(int $tenant_id): array;
  public function calculateMorosityMetrics(int $tenant_id): MorosityReport;
}

3.4.2. Estados de Pago
Estado	Código SPFE	Descripción
pending	PENDING	Factura emitida, pago no recibido
partial	PARTIAL	Pago parcial recibido
paid	PAID	Factura completamente pagada
overdue	OVERDUE	Vencida sin pago (> due_date)
disputed	DISPUTED	En disputa entre partes

3.4.3. Plazos Legales de Pago (Ley 3/2004)
La Ley Crea y Crece refuerza los plazos máximos de pago de la Ley de Morosidad:
•	Plazo general: 30 días desde recepción de la factura
•	Plazo máximo pactado: 60 días (prohibido pactar > 60 días)
•	Administraciones Públicas: 30 días máximo

3.5. EInvoiceValidationService
Validación de facturas electrónicas B2B contra EN 16931 y reglas nacionales españolas.
3.5.1. Interfaz Pública
interface EInvoiceValidationServiceInterface {
  public function validateUBL(string $xml): ValidationResult;
  public function validateEN16931(string $xml): ValidationResult;
  public function validateSpanishRules(string $xml): ValidationResult;
  public function validateComplete(string $xml): ValidationResult;
  public function validateInbound(string $xml): InboundValidation;
}

3.5.2. Capas de Validación
•	Capa 1 - Esquema XSD: Validación contra UBL-Invoice-2.1.xsd o Facturaev3_2_2.xsd
•	Capa 2 - Schematron EN 16931: Reglas semánticas europeas (CEN/TS 16931-3-2)
•	Capa 3 - Reglas españolas: CIUS España (Country-specific Invoice Usage Specification)
•	Capa 4 - Reglas de negocio: NIF válido, IBAN correcto, cuadre importes

3.6. SPFEClientService (Stub + Interface)
Cliente para la Solución Pública de Facturación Electrónica de la AEAT. Implementado como interface con stub mientras se publique la API oficial.
3.6.1. Interfaz
interface SPFEClientInterface {
  public function submitInvoice(string $xml, int $tenant_id): SPFESubmission;
  public function querySubmission(string $submission_id): SPFEStatus;
  public function submitPaymentStatus(int $event_id): SPFEResult;
  public function queryReceivedInvoices(int $tenant_id, array $filters): array;
  public function testConnection(int $tenant_id): bool;
}

3.6.2. Implementación Stub
class SPFEClientStub implements SPFEClientInterface {
  // Simula respuestas exitosas para desarrollo/testing
  // Se reemplazará con SPFEClientLive cuando la AEAT publique la API
  // Configurable: spfe_environment = 'stub' | 'test' | 'production'
}

CRITICAL: La AEAT NO ha publicado aún la API de la SPFE. El módulo implementa una arquitectura preparada con interface + stub que permite desarrollo inmediato y sustitución sin cambios cuando la API esté disponible. El patrón es idéntico al usado en VeriFactu con los endpoints de pruebas.
 
4. API REST
24 endpoints para gestión completa de facturación electrónica B2B.
4.1. Documentos E-Factura
Método	Endpoint	Descripción	Permiso
POST	/api/v1/einvoice/generate	Generar e-factura desde billing invoice	create einvoice
POST	/api/v1/einvoice/generate-manual	Generar con datos manuales	create einvoice
GET	/api/v1/einvoice/documents	Listar documentos (paginado, filtros)	view einvoice
GET	/api/v1/einvoice/documents/{id}	Detalle documento	view einvoice
GET	/api/v1/einvoice/documents/{id}/xml	Descargar XML	view einvoice
GET	/api/v1/einvoice/documents/{id}/pdf	Descargar PDF visual	view einvoice
DELETE	/api/v1/einvoice/documents/{id}	Eliminar borrador	delete einvoice

4.2. Envío y Recepción
Método	Endpoint	Descripción	Permiso
POST	/api/v1/einvoice/send/{id}	Enviar factura al receptor	send einvoice
POST	/api/v1/einvoice/send-batch	Envío masivo (hasta 50)	send einvoice
GET	/api/v1/einvoice/delivery-status/{id}	Estado de entrega	view einvoice
POST	/api/v1/einvoice/receive	Recibir factura entrante (webhook)	receive einvoice
GET	/api/v1/einvoice/inbound	Listar facturas recibidas	view einvoice
POST	/api/v1/einvoice/inbound/{id}/accept	Aceptar factura recibida	manage einvoice
POST	/api/v1/einvoice/inbound/{id}/reject	Rechazar factura recibida	manage einvoice

4.3. Estado de Pago
Método	Endpoint	Descripción	Permiso
POST	/api/v1/einvoice/payment/{id}/record	Registrar pago recibido	manage einvoice payments
GET	/api/v1/einvoice/payment/{id}/history	Historial de pagos	view einvoice
POST	/api/v1/einvoice/payment/{id}/communicate	Comunicar estado a SPFE	manage einvoice payments
GET	/api/v1/einvoice/payment/overdue	Facturas vencidas sin pago	view einvoice
GET	/api/v1/einvoice/payment/morosity-report	Informe de morosidad	view einvoice reports

4.4. Configuración y Conversión
Método	Endpoint	Descripción	Permiso
GET	/api/v1/einvoice/config	Configuración tenant B2B	admin einvoice
PUT	/api/v1/einvoice/config	Actualizar configuración	admin einvoice
POST	/api/v1/einvoice/convert	Convertir entre formatos	create einvoice
POST	/api/v1/einvoice/validate	Validar XML contra EN 16931	view einvoice
GET	/api/v1/einvoice/dashboard	Dashboard resumen B2B	view einvoice
 
5. Flujos ECA (Event-Condition-Action)
5.1. ECA-EI-001: Generación y Envío Automático E-Factura B2B
•	Trigger: billing_invoice.status = 'paid' Y buyer NO es Administración Pública
•	Condition 1: Tenant tiene einvoice_tenant_config activa
•	Condition 2: Buyer es empresa/profesional (B2B, no B2C)
•	Condition 3: NO existe einvoice_document para ese invoice_id
•	Action 1: EInvoiceUblService::buildFromInvoice() O usar Facturae si ya existe (doc 180)
•	Action 2: EInvoiceValidationService::validateComplete()
•	Action 3: Si facturae_document existe: EInvoiceFormatConverterService::facturaeToUbl()
•	Action 4: EInvoiceDeliveryService::send() - Enviar al receptor
•	Action 5: EInvoiceDeliveryService::sendToSPFE() - Copia a Solución Pública
•	Action 6: Log en einvoice_delivery_log
•	Error Handler: Log + Email admin + status='error'

5.2. ECA-EI-002: Sincronización Estados de Pago
•	Trigger: billing_payment.status cambia a 'completed' Y tiene einvoice_document asociado
•	Action 1: EInvoicePaymentStatusService::recordPayment()
•	Action 2: EInvoicePaymentStatusService::updateStatus() → 'paid' o 'partial'
•	Action 3: EInvoicePaymentStatusService::communicateToSPFE()

5.3. ECA-EI-003: Detección de Morosidad
•	Trigger: Cron diario 08:00
•	Condition: Facturas con due_date < NOW() Y payment_status = 'pending'
•	Action 1: Actualizar payment_status = 'overdue'
•	Action 2: Comunicar estado a SPFE
•	Action 3: Notificar al tenant (email + dashboard)
•	Action 4: Si > 30 días overdue: alerta URGENTE

5.4. ECA-EI-004: Recepción Facturas Entrantes
•	Trigger: Webhook recibe XML entrante O email con XML adjunto
•	Action 1: EInvoiceFormatConverterService::detectFormat()
•	Action 2: EInvoiceValidationService::validateInbound()
•	Action 3: Si válida: Crear einvoice_document con direction='inbound'
•	Action 4: Notificar admin del tenant: nueva factura recibida
•	Action 5: Si inválida: Rechazar con motivo y notificar emisor

5.5. ECA-EI-005: Comunicación Periódica Estados SPFE
•	Trigger: Cron cada 6 horas
•	Condition: Hay payment_events con communicated_to_spfe = FALSE
•	Action: Batch de comunicación a SPFE para todos los eventos pendientes
 
6. Permisos RBAC
Permiso	Descripción	Roles
administer einvoice	Configurar módulo B2B	admin, platform_admin
view einvoice	Ver documentos e-factura	admin, accountant, manager
create einvoice	Generar e-facturas	admin, accountant
delete einvoice	Eliminar borradores	admin
send einvoice	Enviar facturas	admin, accountant
receive einvoice	Recibir facturas entrantes	admin, accountant
manage einvoice	Aceptar/rechazar facturas	admin, accountant
manage einvoice payments	Gestionar estados de pago	admin, accountant
view einvoice reports	Ver informes morosidad	admin, accountant, manager
 
7. Estructura del Módulo Drupal
modules/custom/jaraba_einvoice_b2b/
├── jaraba_einvoice_b2b.info.yml
├── jaraba_einvoice_b2b.module
├── jaraba_einvoice_b2b.permissions.yml
├── jaraba_einvoice_b2b.services.yml
├── jaraba_einvoice_b2b.routing.yml
├── jaraba_einvoice_b2b.install
├── src/
│   ├── Entity/
│   │   ├── EInvoiceDocument.php
│   │   ├── EInvoiceTenantConfig.php
│   │   ├── EInvoiceDeliveryLog.php
│   │   └── EInvoicePaymentEvent.php
│   ├── Service/
│   │   ├── EInvoiceUblService.php
│   │   ├── EInvoiceFormatConverterService.php
│   │   ├── EInvoiceDeliveryService.php
│   │   ├── EInvoicePaymentStatusService.php
│   │   ├── EInvoiceValidationService.php
│   │   └── SPFEClient/
│   │       ├── SPFEClientInterface.php
│   │       ├── SPFEClientStub.php
│   │       └── SPFEClientLive.php  // Implementar cuando API disponible
│   ├── Controller/
│   │   ├── EInvoiceDocumentController.php
│   │   ├── EInvoiceDeliveryController.php
│   │   ├── EInvoicePaymentController.php
│   │   ├── EInvoiceConfigController.php
│   │   └── EInvoiceDashboardController.php
│   ├── Form/
│   │   ├── EInvoiceConfigForm.php
│   │   ├── EInvoiceManualForm.php
│   │   └── EInvoicePaymentForm.php
│   ├── Plugin/
│   │   └── QueueWorker/
│   │       ├── SPFESubmissionWorker.php
│   │       └── PaymentStatusWorker.php
│   ├── EventSubscriber/
│   │   ├── EInvoiceInvoiceSubscriber.php
│   │   └── EInvoicePaymentSubscriber.php
│   ├── Model/
│   │   └── EN16931Model.php  // Modelo semántico neutral
│   └── Exception/
│       ├── EInvoiceValidationException.php
│       ├── EInvoiceDeliveryException.php
│       └── SPFEConnectionException.php
├── templates/
│   ├── einvoice-dashboard.html.twig
│   ├── einvoice-document-detail.html.twig
│   └── einvoice-morosity-report.html.twig
├── xsd/
│   ├── UBL-Invoice-2.1.xsd
│   ├── UBL-CreditNote-2.1.xsd
│   └── common/  // Schemas comunes UBL
├── schematron/
│   ├── EN16931-UBL-validation.sch
│   └── ES-CIUS-validation.sch  // Reglas específicas España
└── tests/
    ├── src/Unit/           # 7 tests
    ├── src/Kernel/         # 9 tests
    └── src/Functional/     # 7 tests
 
8. Plan de Tests
8.1. Tests Unitarios (7)
Test	Descripción
testUblGenerationBasic	XML UBL 2.1 con campos mínimos EN 16931
testUblCreditNote	CreditNote UBL válido
testFormatDetection	Detectar UBL vs Facturae vs CII
testFacturaeToUblConversion	Conversión Facturae → UBL correcta
testUblToFacturaeConversion	Conversión UBL → Facturae correcta
testPaymentStatusTransitions	Máquina estados pago válida
testMorosityCalculation	Cálculo correcto días mora

8.2. Tests Kernel (9)
Test	Descripción
testCreateFromBillingInvoice	Generación completa desde billing_invoice
testEN16931Validation	XML pasa validación Schematron EN 16931
testSpanishCIUSValidation	Reglas específicas España
testMultiTenantIsolation	Aislamiento documentos entre tenants
testInboundInvoiceParsing	Parse correcto de factura entrante UBL
testPaymentEventCreation	Registro de evento de pago
testDeliveryLogImmutability	Log no permite modificación
testFormatConversionRoundtrip	Facturae→UBL→Facturae = datos idénticos
testSPFEStubBehavior	Stub simula correctamente respuestas SPFE

8.3. Tests Funcionales (7)
Test	Descripción
testFullB2BOutboundFlow	Invoice → UBL → Validate → Send → SPFE
testFullInboundFlow	Receive XML → Validate → Store → Accept
testPaymentLifecycle	Emitir → Pagar parcial → Pagar total → Comunicar
testMorosityDetection	Facturas vencidas detectadas y comunicadas
testBatchSending	Envío masivo 50 facturas
testApiPermissions	RBAC en todos los endpoints
testECAAutoGeneration	Trigger automático genera e-factura
 
9. Roadmap de Implementación
NOTA: Este módulo se implementa DESPUÉS de doc 179 (VeriFactu) y doc 180 (Facturae). Se recomienda iniciar cuando se publique el reglamento definitivo de la Ley Crea y Crece, o como mínimo 6 meses antes del deadline estimado.

9.1. Fase 1 (Semanas 1-3): Core UBL + Validación
•	Crear 4 entidades: einvoice_document, einvoice_tenant_config, einvoice_delivery_log, einvoice_payment_event
•	EInvoiceUblService: Generación XML UBL 2.1 completo conforme EN 16931
•	EInvoiceValidationService: Validación XSD + Schematron + CIUS España
•	EInvoiceFormatConverterService: Conversión bidireccional Facturae ↔ UBL
•	Tests unitarios y kernel: 16 tests
Estimación: 80-100 horas

9.2. Fase 2 (Semanas 4-6): Entrega + Recepción
•	EInvoiceDeliveryService: Orquestador multicanal (platform, email, SPFE stub)
•	SPFEClientStub: Stub funcional para desarrollo sin API real
•	API REST: 24 endpoints completos
•	Recepción inbound: Webhook + parsing + validación
•	ECA-EI-001 y ECA-EI-004: Automatización envío y recepción
Estimación: 72-96 horas

9.3. Fase 3 (Semanas 7-9): Pagos + Morosidad
•	EInvoicePaymentStatusService: Gestión estados de pago
•	Comunicación SPFE: Estados de pago a Solución Pública
•	Detección morosidad: ECA-EI-003 con alertas y reporting
•	Dashboard morosidad: Informe detallado por tenant
•	ECA-EI-002 y ECA-EI-005: Automatización pagos y comunicación
Estimación: 56-72 horas

9.4. Fase 4 (Semanas 10-12): Dashboard + QA + SPFE Live
•	Dashboard B2B: Vista unificada outbound + inbound + pagos
•	Formularios admin: Config, generación manual, gestión pagos
•	SPFEClientLive: Implementar cuando API disponible
•	Tests E2E: Flujos completos
•	Documentación: Manual operativo B2B
Estimación: 52-68 horas

9.5. Resumen Inversión
Concepto	Horas	Coste (45 EUR/h)
Fase 1: UBL + Validación	80-100	3.600-4.500 EUR
Fase 2: Entrega + Recepción	72-96	3.240-4.320 EUR
Fase 3: Pagos + Morosidad	56-72	2.520-3.240 EUR
Fase 4: Dashboard + QA	52-68	2.340-3.060 EUR
TOTAL	260-336	11.700-15.120 EUR
 
10. Dependencias Técnicas
10.1. Composer
Paquete	Versión	Uso	Licencia
josemmo/facturae-php	^1.8	Generación/parsing Facturae (opcional)	MIT
sabre/xml	^4.0	Parser XML robusto para UBL	BSD-3

NOTA: Las dependencias Composer son OPCIONALES. El módulo puede funcionar solo con DOMDocument nativo de PHP. Se incluyen como aceleradores de desarrollo. Si se prefiere zero-dependency (como doc 179 y 180), todo puede implementarse con ext-dom.

10.2. Archivos XSD y Schematron
Archivo	Fuente	Uso
UBL-Invoice-2.1.xsd	docs.oasis-open.org/ubl	Validación esquema UBL
UBL-CreditNote-2.1.xsd	docs.oasis-open.org/ubl	Validación nota de crédito
EN16931-UBL-validation.sch	github.com/ConnectingEurope/eInvoicing	Reglas semánticas EN 16931
ES-CIUS-validation.sch	AEAT (cuando se publique)	Reglas específicas España

10.3. Reutilización de Componentes
Componente	Fuente	Reutilización
billing_invoice	Doc 134	90% - Datos origen
FacturaeXmlService	Doc 180	70% - Base para conversión
FacturaeXAdESService	Doc 180	80% - Firma compartida
CertificateManager	Doc 179/180	90% - Gestión certificados
ECA patterns	Doc 06	85% - Automatización
Multi-tenant	Doc 07	90% - Aislamiento datos
VeriFactu records	Doc 179	40% - Coordinar registros
 
11. Arquitectura Integrada de Facturación
Los tres módulos de facturación (VeriFactu, Facturae, E-Factura B2B) forman un sistema cohesivo que cubre toda la normativa española.
11.1. Flujo Integrado por Tipo de Operación
Operación	VeriFactu (179)	Facturae (180)	E-Factura (181)
Factura a AAPP (B2G)	Sí - Registro hash	Sí - XML + Firma + FACe	No aplica
Factura a Empresa (B2B)	Sí - Registro hash	Opcional (como formato)	Sí - UBL + SPFE
Factura a Consumidor (B2C)	Sí - Registro hash	No	No aplica
Ticket simplificado	Sí - Registro hash	No	No aplica
Factura recibida B2B	No	No	Sí - Recepción + validación
Estado de pago B2B	No	No	Sí - Comunicación SPFE

11.2. Servicio Compartido: CertificateManagerService
Se recomienda crear un servicio transversal jaraba_certificate_manager que centralice:
•	Almacenamiento seguro: PKCS#12 cifrado con AES-256-GCM, password en vault
•	Conversión formatos: P12 → PEM (para SOAP), P12 → cert+key (para XAdES)
•	Monitorización: Alertas de caducidad centralizadas
•	Rotación: Flujo de renovación sin downtime
•	Consumidores: jaraba_verifactu, jaraba_facturae, jaraba_einvoice_b2b

11.3. Orden de Implementación
Fase	Módulo	Deadline	Inversión
1 (Ya)	Doc 179 - jaraba_verifactu	Ene 2027 (duro)	10.350-14.220 EUR
2 (Q3 2026)	Doc 180 - jaraba_facturae	Si Motor Institucional activo	10.350-13.680 EUR
3 (Q1 2027)	Doc 181 - jaraba_einvoice_b2b	~2027-2028 (pendiente)	11.700-15.120 EUR
TOTAL	Cobertura normativa 100%	-	32.400-43.020 EUR

11.4. Sanciones por Incumplimiento
Normativa	Sanción	Riesgo
VeriFactu (Ley Antifraude)	Hasta 50.000 EUR/ejercicio por software no conforme	ALTO - Deadline duro
Facturae B2G (Ley 25/2013)	Rechazo de facturas a AAPP	MEDIO - Si Motor Institucional
E-Factura B2B (Crea y Crece)	Hasta 10.000 EUR por no facturar electrónicamente	BAJO - Deadline lejano


SISTEMA DE QR DINÁMICOS
QR Phy-gital con Analytics, Captura de Leads y Engagement
Vertical AgroConecta - JARABA IMPACT PLATFORM

Campo	Valor
Código Documento:	81_AgroConecta_QR_Dynamic
Versión:	1.0
Fecha:	Enero 2026
Concepto:	Puente Phy-gital (Físico ↔ Digital)
Dependencias:	47_Commerce_Core, 80_Traceability_System
Librería QR:	endroid/qr-code ^5.0
 
1. Resumen Ejecutivo
Este documento especifica el Sistema de QR Dinámicos para AgroConecta, creando el puente 'Phy-gital' entre el producto físico (botella, etiqueta, packaging) y la experiencia digital (trazabilidad, storytelling, compra). A diferencia de un QR estático que simplemente apunta a una URL, los QR dinámicos de AgroConecta son activos de marketing inteligentes con capacidad de analytics, captura de leads y engagement contextual.
1.1 Propuesta de Valor del QR Phy-gital
Stakeholder	Valor del QR Dinámico
Consumidor	Verificar autenticidad, conocer origen, historia del productor, recetas, maridajes
Productor	Analytics de escaneos, captura de leads, solicitud de reseñas, engagement post-compra
Distribuidor/Hostelería	Verificación de producto genuino, acceso a fichas técnicas, pedidos recurrentes
AgroConecta	Datos de comportamiento, conversión QR→compra, geolocalización de consumidores
1.2 Tipos de QR en el Sistema
Tipo	Destino	Uso Principal
QR de Lote	/trazabilidad/{lote_code}	Etiqueta de producto: verificación y trazabilidad
QR de Producto	/p/{product_slug}?qr=1	Catálogo/folleto: ficha de producto con compra
QR de Productor	/productor/{slug}?qr=1	Stand en feria: historia y portfolio del productor
QR de Campaña	/c/{campaign_code}	Marketing: promociones temporales, concursos
QR de Reseña	/review/{order_id}/{token}	Post-compra: solicitar valoración
 
2. Modelo de Datos
2.1 Entidad: qr_code
Almacena cada código QR generado. Permite tracking individual y actualizaciones dinámicas del destino.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno autoincremental	PRIMARY KEY
uuid	UUID	Identificador único global	UNIQUE, NOT NULL
code	VARCHAR(32)	Código único del QR (ej: QR-2026-A1B2C3)	UNIQUE, NOT NULL, INDEX
qr_type	VARCHAR(32)	Tipo de QR	ENUM: lote|product|producer|campaign|review
target_entity_type	VARCHAR(64)	Tipo de entidad destino	node|taxonomy_term|user
target_entity_id	INT	ID de la entidad destino	NOT NULL, INDEX
destination_url	VARCHAR(512)	URL de destino actual	NOT NULL
short_url	VARCHAR(128)	URL corta para el QR	UNIQUE, ej: agro.link/A1B2C3
is_dynamic	BOOLEAN	¿Destino puede cambiar?	DEFAULT TRUE
status	VARCHAR(16)	Estado del QR	ENUM: active|paused|expired|deleted
image_file	INT	Imagen PNG del QR generado	FK file_managed.fid
image_svg	TEXT	SVG del QR para impresión	NULLABLE
style_preset	VARCHAR(32)	Preset de estilo visual	DEFAULT 'default'
logo_enabled	BOOLEAN	¿Incluir logo en centro?	DEFAULT TRUE
color_foreground	VARCHAR(7)	Color del QR	DEFAULT '#2E7D32'
color_background	VARCHAR(7)	Color de fondo	DEFAULT '#FFFFFF'
size_px	INT	Tamaño en píxeles	DEFAULT 300
error_correction	VARCHAR(1)	Nivel corrección errores	ENUM: L|M|Q|H, DEFAULT 'M'
valid_from	DATETIME	Fecha de inicio de validez	NULLABLE
valid_until	DATETIME	Fecha de expiración	NULLABLE
scan_limit	INT	Límite de escaneos (0=ilimitado)	DEFAULT 0
created_by	INT	Usuario que creó el QR	FK users.uid
tenant_id	INT	Tenant propietario	FK, INDEX
created	DATETIME	Fecha de creación	NOT NULL
changed	DATETIME	Última modificación	NOT NULL
 
2.2 Entidad: qr_scan_event
Registra cada escaneo de QR. Permite analytics detallado de comportamiento del consumidor.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
qr_id	INT	QR escaneado	FK qr_code.id, NOT NULL, INDEX
scanned_at	DATETIME	Fecha/hora del escaneo	NOT NULL, INDEX
ip_address	VARCHAR(45)	IP del escáner (IPv4/IPv6)	NULLABLE
ip_hash	VARCHAR(64)	Hash de IP para privacidad	NOT NULL, INDEX
user_agent	VARCHAR(512)	User agent del navegador	NULLABLE
device_type	VARCHAR(16)	Tipo de dispositivo	ENUM: mobile|tablet|desktop|unknown
os_family	VARCHAR(32)	Sistema operativo	NULLABLE: iOS|Android|Windows|macOS|Linux
browser_family	VARCHAR(32)	Navegador	NULLABLE: Chrome|Safari|Firefox|Edge
country_code	VARCHAR(2)	País (ISO 3166-1)	NULLABLE, INDEX
region	VARCHAR(128)	Región/provincia	NULLABLE
city	VARCHAR(128)	Ciudad	NULLABLE
latitude	DECIMAL(10,8)	Latitud aproximada	NULLABLE
longitude	DECIMAL(11,8)	Longitud aproximada	NULLABLE
referrer	VARCHAR(512)	Referrer si existe	NULLABLE
utm_source	VARCHAR(128)	UTM source	NULLABLE
utm_medium	VARCHAR(128)	UTM medium	NULLABLE
utm_campaign	VARCHAR(128)	UTM campaign	NULLABLE
session_id	VARCHAR(64)	ID de sesión para tracking	NULLABLE
user_id	INT	Usuario si está logueado	FK users.uid, NULLABLE
converted	BOOLEAN	¿Convirtió en compra?	DEFAULT FALSE
conversion_order_id	INT	Pedido si convirtió	FK commerce_order.order_id, NULLABLE
conversion_value	DECIMAL(10,2)	Valor de la conversión	NULLABLE
time_on_page	INT	Segundos en la landing	NULLABLE
bounce	BOOLEAN	¿Abandonó inmediatamente?	DEFAULT FALSE
 
2.3 Entidad: qr_lead_capture
Leads capturados a través de QR. Permite al productor construir base de datos de clientes interesados.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
qr_id	INT	QR de origen	FK qr_code.id, NOT NULL, INDEX
scan_event_id	INT	Evento de escaneo asociado	FK qr_scan_event.id, NULLABLE
email	VARCHAR(255)	Email del lead	NOT NULL, INDEX
name	VARCHAR(255)	Nombre	NULLABLE
phone	VARCHAR(32)	Teléfono	NULLABLE
capture_type	VARCHAR(32)	Tipo de captura	ENUM: newsletter|discount|recipe|contest|review_request
consent_marketing	BOOLEAN	Consentimiento marketing	DEFAULT FALSE
consent_timestamp	DATETIME	Momento del consentimiento	NULLABLE
discount_code	VARCHAR(32)	Código descuento entregado	NULLABLE
discount_used	BOOLEAN	¿Usó el descuento?	DEFAULT FALSE
source_product_id	INT	Producto del QR	FK node.nid, NULLABLE
source_lote_id	INT	Lote del QR	FK node.nid, NULLABLE
producer_id	INT	Productor propietario	FK users.uid, NOT NULL, INDEX
tenant_id	INT	Tenant	FK, INDEX
synced_to_crm	BOOLEAN	¿Sincronizado a CRM?	DEFAULT FALSE
crm_contact_id	VARCHAR(64)	ID en CRM externo	NULLABLE
created	DATETIME	Fecha de captura	NOT NULL
 
3. Servicios del Sistema
3.1 QrGeneratorService
Genera códigos QR con estilos personalizados usando la librería endroid/qr-code.
class QrGeneratorService {    // Genera QR para un lote de producción   public function generateForLote(NodeInterface $lote, array $options = []): QrCode;      // Genera QR para un producto   public function generateForProduct(NodeInterface $product, array $options = []): QrCode;      // Genera QR para un productor   public function generateForProducer(User $producer, array $options = []): QrCode;      // Genera QR para una campaña de marketing   public function generateForCampaign(string $campaign_code, string $url, array $options = []): QrCode;      // Genera QR para solicitar reseña post-compra   public function generateForReviewRequest(Order $order): QrCode;      // Crea la imagen QR con estilos personalizados   protected function createQrImage(string $url, array $style): string;      // Añade logo al centro del QR   protected function embedLogo(string $qr_image, string $logo_path): string;      // Genera código único para el QR   protected function generateUniqueCode(): string;      // Guarda el QR en el sistema de archivos   protected function saveQrFiles(QrCode $entity, string $png, string $svg): void; }
3.2 QrScanTrackingService
Registra y analiza los escaneos de QR.
class QrScanTrackingService {    // Registra un evento de escaneo   public function trackScan(QrCode $qr, Request $request): QrScanEvent;      // Parsea información del dispositivo desde User-Agent   protected function parseDeviceInfo(string $user_agent): array;      // Obtiene geolocalización aproximada desde IP   protected function geolocateIp(string $ip): array;      // Marca conversión cuando el escaneo resulta en compra   public function markConversion(QrScanEvent $scan, Order $order): void;      // Actualiza tiempo en página (via beacon JS)   public function updateTimeOnPage(string $session_id, int $seconds): void;      // Obtiene analytics de un QR específico   public function getQrAnalytics(QrCode $qr, array $date_range = []): array;      // Obtiene analytics agregado de un productor   public function getProducerAnalytics(User $producer, array $date_range = []): array;      // Obtiene top productos por escaneos   public function getTopScannedProducts(int $tenant_id, int $limit = 10): array;      // Obtiene mapa de calor de escaneos por ubicación   public function getScanHeatmapData(int $tenant_id, array $date_range = []): array; }
3.3 LeadCaptureService
Gestiona la captura de leads desde QR y su sincronización con CRM.
class LeadCaptureService {    // Captura un lead desde formulario de QR landing   public function captureLead(array $data, QrCode $qr, ?QrScanEvent $scan): QrLeadCapture;      // Valida datos del lead (email, consentimiento)   protected function validateLeadData(array $data): array;      // Genera y asigna código de descuento al lead   public function assignDiscountCode(QrLeadCapture $lead, string $promotion_id): string;      // Sincroniza lead con CRM externo (Mailchimp, HubSpot, etc.)   public function syncToCrm(QrLeadCapture $lead): bool;      // Obtiene leads de un productor   public function getProducerLeads(User $producer, array $filters = []): array;      // Exporta leads a CSV   public function exportLeadsCsv(User $producer, array $filters = []): string;      // Verifica si email ya existe como lead del productor   public function leadExists(string $email, int $producer_id): bool; }
 
4. APIs REST
4.1 Endpoint de Redirección (Público)
GET /q/{code}  Proceso: 1. Buscar QR por código 2. Validar estado (active) y fechas de validez 3. Registrar evento de escaneo (async) 4. Redireccionar HTTP 302 a destination_url  Response 302: Location: https://agroconecta.es/trazabilidad/LOTE-2026-001-XY7Z?scan=abc123  Response 404 (QR no existe): { "error": "qr_not_found", "message": "Código QR no válido" }  Response 410 (QR expirado): { "error": "qr_expired", "message": "Este código QR ha expirado" }
4.2 Endpoints de Gestión (Autenticados)
Método	Endpoint	Descripción	Scope
POST	/api/v1/qr	Crear nuevo QR	qr:create
GET	/api/v1/qr/{id}	Obtener QR por ID	qr:read
PATCH	/api/v1/qr/{id}	Actualizar QR (destino, estado)	qr:update
DELETE	/api/v1/qr/{id}	Eliminar QR	qr:delete
GET	/api/v1/qr/{id}/analytics	Analytics del QR	qr:analytics
GET	/api/v1/qr/{id}/scans	Lista de escaneos	qr:analytics
POST	/api/v1/qr/batch	Generar QRs en lote	qr:create
GET	/api/v1/qr/{id}/download/{format}	Descargar imagen (png|svg|pdf)	qr:read
4.3 Endpoints de Leads (Autenticados)
Método	Endpoint	Descripción	Scope
POST	/api/v1/leads	Capturar lead (desde landing)	leads:capture
GET	/api/v1/leads	Listar leads del productor	leads:read
GET	/api/v1/leads/{id}	Detalle de un lead	leads:read
DELETE	/api/v1/leads/{id}	Eliminar lead	leads:delete
GET	/api/v1/leads/export	Exportar leads a CSV	leads:export
POST	/api/v1/leads/{id}/sync-crm	Sincronizar lead a CRM	leads:sync
4.4 Ejemplo: Crear QR para Lote
POST /api/v1/qr Authorization: Bearer {token} Content-Type: application/json  {   "qr_type": "lote",   "target_entity_type": "node",   "target_entity_id": 1234,   "style": {     "logo_enabled": true,     "color_foreground": "#2E7D32",     "size_px": 400,     "error_correction": "H"   },   "valid_until": "2027-12-31T23:59:59Z" }  Response 201: {   "id": 567,   "code": "QR-2026-A1B2C3",   "short_url": "https://agro.link/A1B2C3",   "destination_url": "https://agroconecta.es/trazabilidad/LOTE-2026-001-XY7Z",   "download_urls": {     "png": "/api/v1/qr/567/download/png",     "svg": "/api/v1/qr/567/download/svg",     "pdf": "/api/v1/qr/567/download/pdf"   },   "created_at": "2026-01-15T12:00:00Z" }
 
5. Flujos de Automatización (ECA)
5.1 ECA-QR-001: Generación Automática de QR para Lotes
Trigger: Creación de nodo lote_produccion con field_id_lote no vacío  Conditions:   - No existe QR previo para este lote   - Generación automática habilitada en configuración  Actions:   1. Invocar QrGeneratorService::generateForLote()   2. Guardar referencia en campo field_qr_code del lote   3. Log: 'QR generado para lote {id}: {qr_code}'
5.2 ECA-QR-002: Tracking de Escaneo
Trigger: Acceso a ruta /q/{code}  Conditions:   - QR existe y está activo   - No es un bot conocido (filtrar por User-Agent)  Actions:   1. Encolar registro de escaneo (async para no bloquear redirect)   2. Incrementar contador de escaneos del QR   3. Redireccionar a destination_url
5.3 ECA-QR-003: Atribución de Conversión
Trigger: Creación de commerce_order con estado 'completed'  Conditions:   - Sesión tiene scan_id de QR (cookie o parámetro)   - El escaneo fue en las últimas 24 horas  Actions:   1. Buscar qr_scan_event por session_id   2. Actualizar scan: converted = TRUE, conversion_order_id, conversion_value   3. Notificar al productor: 'Conversión desde QR: {order_total}'
5.4 ECA-QR-004: Captura de Lead desde Landing
Trigger: Envío de formulario de captura en landing de trazabilidad  Conditions:   - Email válido   - Consentimiento marketing = TRUE   - No es lead duplicado para este productor  Actions:   1. Crear qr_lead_capture con datos del formulario   2. Si promoción activa: generar código de descuento   3. Enviar email de bienvenida con descuento (si aplica)   4. Encolar sincronización a CRM   5. Notificar al productor: 'Nuevo lead capturado: {email}'
5.5 ECA-QR-005: Solicitud de Reseña Post-Compra
Trigger: Cambio de estado de shipment a 'delivered' + 3 días  Conditions:   - Cliente no ha dejado reseña aún   - Cliente tiene email válido   - Producto del pedido tiene QR de review habilitado  Actions:   1. Generar QR único de reseña con token de seguridad   2. Enviar email con QR: 'Tu opinión nos importa'   3. Log: 'Solicitud de reseña enviada para pedido {order_id}'
 
6. Landing Pages de QR
6.1 Landing de Trazabilidad (Lote)
Esta landing ya está especificada en el documento 80_Traceability_System. Incluye formulario de captura de leads.
Sección	Contenido	Captura de Lead
Header	Producto + Lote + Badge verificación	No
Timeline	Eventos de cadena de suministro	No
Productor	Historia, fotos, ubicación	No
Newsletter	Formulario: email + nombre	Sí: tipo 'newsletter'
Descuento	10% en tu primera compra	Sí: tipo 'discount'
CTA	Botón comprar producto	No (tracking de conversión)
6.2 Landing de Producto
Cuando el QR apunta a un producto (no a un lote específico).
URL: /p/{product_slug}?qr=1&scan={scan_id}  Secciones: 1. Galería de producto (imágenes de alta calidad) 2. Descripción y características 3. Lotes disponibles (si hay stock) 4. Certificaciones (DO, Ecológico, etc.) 5. Maridajes y recetas sugeridas 6. Productor: mini-perfil con enlace 7. Formulario: 'Recibe recetas exclusivas' (captura tipo 'recipe') 8. CTA: Añadir al carrito + precio 9. Productos relacionados
6.3 Landing de Reseña
Landing específica para solicitar valoración post-compra.
URL: /review/{order_id}/{token}  Validaciones: - Token válido y no expirado (7 días) - Pedido existe y está entregado - Cliente no ha dejado reseña aún  Secciones: 1. Saludo personalizado: 'Hola {nombre}, ¿qué tal tu {producto}?' 2. Rating con estrellas (1-5) 3. Textarea para comentario 4. Checkbox: 'Publicar con mi nombre' / 'Anónimo' 5. Botón: 'Enviar valoración' 6. Incentivo: 'Gana un 15% en tu próxima compra'  Post-envío: - Crear entidad review vinculada al producto y pedido - Generar código de descuento de agradecimiento - Notificar al productor de nueva reseña
 
7. Analytics de QR
7.1 Dashboard del Productor
Métrica	Descripción	Visualización
Total Escaneos	Escaneos totales de todos los QR	Número + sparkline 30 días
Escaneos Hoy	Escaneos en las últimas 24h	Número + comparativa ayer
Conversiones	Escaneos que resultaron en compra	Número + tasa de conversión %
Leads Capturados	Leads desde QR este mes	Número + comparativa mes anterior
Top Productos	Productos más escaneados	Lista top 5
Distribución Geográfica	Países/ciudades de escaneos	Mapa de calor
Dispositivos	iOS vs Android vs Desktop	Gráfico de dona
Horarios	Horas del día con más escaneos	Gráfico de barras por hora
7.2 Métricas por QR Individual
GET /api/v1/qr/{id}/analytics?from=2026-01-01&to=2026-01-31  Response: {   "qr_code": "QR-2026-A1B2C3",   "period": { "from": "2026-01-01", "to": "2026-01-31" },   "metrics": {     "total_scans": 342,     "unique_visitors": 298,     "returning_visitors": 44,     "avg_time_on_page": 47,     "bounce_rate": 0.23,     "conversions": 12,     "conversion_rate": 0.035,     "conversion_value": 456.80,     "leads_captured": 28   },   "by_day": [...],   "by_device": { "mobile": 312, "tablet": 18, "desktop": 12 },   "by_country": { "ES": 280, "FR": 32, "DE": 18, "US": 12 },   "top_cities": ["Madrid", "Barcelona", "Sevilla", "Valencia", "París"] }
 
8. Integración con Packaging Físico
8.1 Formatos de Exportación
Formato	Resolución	Uso
PNG	300 DPI, fondo transparente	Web, pantallas, presentaciones
SVG	Vector infinito	Impresión profesional, escalado
PDF	Vector con márgenes de corte	Imprenta, etiquetas
EPS	Vector (Adobe Illustrator)	Diseño gráfico profesional
8.2 Tamaños Recomendados
Aplicación	Tamaño Mínimo	Tamaño Recomendado	Error Correction
Contraetiqueta botella vino	15x15 mm	20x20 mm	H (30%)
Etiqueta aceite	20x20 mm	25x25 mm	H (30%)
Caja/packaging	30x30 mm	40x40 mm	M (15%)
Folleto/catálogo	25x25 mm	35x35 mm	M (15%)
Cartel/póster	50x50 mm	80x80 mm	L (7%)
8.3 Generación en Lote para Imprenta
POST /api/v1/qr/batch Authorization: Bearer {token} Content-Type: application/json  {   "lote_ids": [1234, 1235, 1236, 1237, 1238],   "format": "pdf",   "options": {     "size_mm": 25,     "dpi": 300,     "error_correction": "H",     "include_lote_code_text": true,     "include_bleed": true,     "bleed_mm": 3   } }  Response 202: {   "batch_id": "batch-2026-xyz",   "status": "processing",   "count": 5,   "download_url": "/api/v1/qr/batch/batch-2026-xyz/download",   "estimated_ready": "2026-01-15T12:05:00Z" }
 
9. Roadmap de Implementación
Sprint	Semanas	Entregables	Dependencias
Sprint 1	1-2	Entidades qr_code, qr_scan_event, qr_lead_capture. Migraciones.	Commerce Core
Sprint 2	3-4	QrGeneratorService: generación, estilos, logo embebido.	Sprint 1
Sprint 3	5-6	QrScanTrackingService: registro, geolocalización, device parsing.	Sprint 2
Sprint 4	7-8	LeadCaptureService: formularios, descuentos, sincronización CRM.	Sprint 3
Sprint 5	9-10	APIs REST completas. Dashboard de analytics.	Sprint 4
Sprint 6	11-12	Flujos ECA de automatización. Integración con Traceability System.	Sprint 5, Doc 80
9.1 Estimación de Esfuerzo
Componente	Horas Estimadas	Complejidad
Entidades y migraciones	12-16h	Media
QrGeneratorService + endroid	20-28h	Media
QrScanTrackingService + geo	24-32h	Alta
LeadCaptureService	16-20h	Media
APIs REST	16-20h	Media
Dashboard analytics (React)	24-32h	Alta
Flujos ECA	12-16h	Media
Testing y documentación	16-20h	Media
TOTAL	140-184h	~3.5-4.5 meses a 50%
--- Fin del Documento ---
81_AgroConecta_QR_Dynamic_v1.docx | Jaraba Impact Platform | Enero 2026

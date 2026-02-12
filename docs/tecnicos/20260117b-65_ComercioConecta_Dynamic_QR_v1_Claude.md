SISTEMA DYNAMIC QR
Códigos QR Dinámicos para Experiencia Phygital
Vertical ComercioConecta
JARABA IMPACT PLATFORM
Documento Técnico de Implementación

Campo	Valor
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	65_ComercioConecta_Dynamic_QR
Dependencias:	62_Commerce_Core, 72_Customer_Portal, 77_Reviews_System
Tipo:	Componente Exclusivo ComercioConecta
Base:	Trazabilidad Phy-gital de AgroConecta (~60% reutilizable)
 
1. Resumen Ejecutivo
Este documento especifica el Sistema de QR Dinámicos, un componente exclusivo de ComercioConecta que crea el puente entre la experiencia física y digital del comercio de proximidad. Los QR dinámicos permiten a los comercios conectar sus productos físicos, escaparates, y espacios con experiencias digitales personalizadas, capturando datos valiosos de interacción y facilitando la conversión.
1.1 Propuesta de Valor
"Cada etiqueta es una puerta al mundo digital de tu tienda"
• Información extendida: El cliente escanea y ve ficha completa, vídeos, reseñas
• Captación de reseñas: Solicitud de valoración Google justo después de la compra física
• Fidelización: Acumulación de puntos, registro en programa de lealtad
• Analytics: Tracking de interacciones físicas que antes eran invisibles
• Conversión omnicanal: Ver en tienda → comprar online → recoger después
1.2 Tipos de QR Soportados
Tipo	Ubicación Física	Destino Digital	Caso de Uso Principal
Producto	Etiqueta en artículo	Ficha de producto ampliada	Ver tallas, colores, reseñas
Escaparate	Vinilo en cristal	Catálogo de vitrina con precios	Tienda cerrada, compra 24/7
Mesa	Adhesivo en mesa/barra	Carta digital + pedido	Restaurantes, cafeterías
Ticket	Impreso en recibo	Solicitud de reseña Google	Post-compra, captación 5★
Promoción	Cartel/flyer	Landing de oferta flash	Campañas específicas
Fidelización	Tarjeta/app	Programa de puntos	Check-in, acumulación
Evento	Entrada/pulsera	Info del evento + ofertas	Ferias, mercadillos
Genérico	Cualquier soporte	URL configurable	Uso flexible
1.3 Diferencia: QR Estático vs. Dinámico
Aspecto	QR Estático	QR Dinámico (ComercioConecta)
URL codificada	Fija para siempre	Shortlink que redirige a destino configurable
Cambiar destino	Reimprimir QR	Cambiar en panel sin reimprimir
Tracking	No posible	Escaneos, ubicación, dispositivo, hora
A/B testing	No posible	Rotar destinos para optimizar
Caducidad	Permanente	Configurable (ofertas temporales)
Personalización	No posible	Mostrar contenido según contexto
 
2. Arquitectura del Sistema
2.1 Componentes Principales
┌─────────────────────────────────────────────────────────────────────┐ │                      DYNAMIC QR SYSTEM                              │ ├─────────────────────────────────────────────────────────────────────┤ │                                                                     │ │  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────────┐  │ │  │   Merchant   │  │     QR       │  │    Landing Page          │  │ │  │   Portal     │──│   Generator  │──│    Builder               │  │ │  │  (Crear QR)  │  │  (SVG/PNG)   │  │  (Contextuales)          │  │ │  └──────────────┘  └──────────────┘  └──────────────────────────┘  │ │                                                                     │ │  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────────┐  │ │  │  dynamic_qr  │  │   Redirect   │  │    Scan Analytics        │  │ │  │   Entity     │──│   Engine     │──│    Engine                │  │ │  │              │  │  (Shortlink) │  │  (Tracking)              │  │ │  └──────────────┘  └──────────────┘  └──────────────────────────┘  │ │                                                                     │ │  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────────┐  │ │  │   Review     │  │   Loyalty    │  │    Print                 │  │ │  │   Capture    │──│   Check-in   │──│    Templates             │  │ │  │  (Google ★)  │  │  (Puntos)    │  │  (PDF export)            │  │ │  └──────────────┘  └──────────────┘  └──────────────────────────┘  │ │                                                                     │ └─────────────────────────────────────────────────────────────────────┘                               │               ┌───────────────┼───────────────┐               ▼               ▼               ▼      ┌────────────────┐ ┌───────────┐ ┌────────────────┐      │  Physical QR   │ │  Mobile   │ │    Google      │      │  (Etiqueta,    │ │  Scanner  │ │    Reviews     │      │   Cartel)      │ │  (App)    │ │    API         │      └────────────────┘ └───────────┘ └────────────────┘
2.2 Flujo de Escaneo
1. Cliente escanea QR físico con su smartphone
2. QR contiene shortlink: qr.comercioconecta.es/abc123
3. Redirect Engine recibe la petición
4. Se registra el escaneo (qr_scan): timestamp, IP, user-agent, referer
5. Se evalúan reglas de redirección (horario, dispositivo, contador)
6. Se determina el destino final según tipo de QR y reglas
7. Redirect 302 al destino (landing contextual o URL externa)
8. Landing muestra contenido personalizado + CTAs
 
3. Entidades del Sistema
3.1 Entidad: dynamic_qr
Entidad principal que representa un código QR dinámico. Cada QR tiene un shortcode único y configuración de destino.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
merchant_id	INT	Comercio propietario	FK merchant_profile.id, NOT NULL, INDEX
tenant_id	INT	Tenant del marketplace	FK tenant.id, NOT NULL, INDEX
shortcode	VARCHAR(16)	Código corto único	UNIQUE, NOT NULL, INDEX, ej: 'abc123'
name	VARCHAR(128)	Nombre interno	NOT NULL, ej: 'QR Escaparate Principal'
qr_type	VARCHAR(32)	Tipo de QR	ENUM: product|showcase|table|ticket|promo|loyalty|event|generic
destination_type	VARCHAR(32)	Tipo de destino	ENUM: product|category|landing|external|review|menu|loyalty
destination_id	INT	ID del destino interno	NULLABLE, FK según destination_type
destination_url	VARCHAR(500)	URL externa (si aplica)	NULLABLE
landing_config	JSON	Configuración de landing	NULLABLE, ver 3.4
product_id	INT	Producto vinculado	FK product_retail.id, NULLABLE
variation_id	INT	Variación específica	FK product_variation_retail.id, NULLABLE
location_id	INT	Ubicación física	FK stock_location.id, NULLABLE
is_active	BOOLEAN	QR activo	DEFAULT TRUE
valid_from	DATETIME	Inicio de validez	NULLABLE
valid_until	DATETIME	Fin de validez	NULLABLE
scan_limit	INT	Máximo de escaneos	NULLABLE, 0 = ilimitado
password_protected	BOOLEAN	Requiere contraseña	DEFAULT FALSE
password_hash	VARCHAR(255)	Hash de contraseña	NULLABLE
redirect_rules	JSON	Reglas de redirección	NULLABLE, ver 5.2
style_config	JSON	Estilo visual del QR	NULLABLE, ver 4.2
logo_fid	INT	Logo en centro del QR	FK file_managed.fid, NULLABLE
total_scans	INT	Total de escaneos	DEFAULT 0
unique_scans	INT	Escaneos únicos	DEFAULT 0
last_scan_at	DATETIME	Último escaneo	NULLABLE
created	DATETIME	Fecha de creación	NOT NULL, UTC
changed	DATETIME	Última modificación	NOT NULL, UTC
 
3.2 Entidad: qr_scan
Registro de cada escaneo de un QR. Permite analytics detallado de interacciones físico-digitales.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
qr_id	INT	QR escaneado	FK dynamic_qr.id, NOT NULL, INDEX
scanned_at	DATETIME	Momento del escaneo	NOT NULL, UTC, INDEX
ip_address	VARCHAR(45)	IP del escáner	NOT NULL
ip_hash	VARCHAR(64)	Hash de IP (privacidad)	NOT NULL, para unique count
user_agent	VARCHAR(500)	User agent completo	NULLABLE
device_type	VARCHAR(16)	Tipo de dispositivo	ENUM: mobile|tablet|desktop|unknown
os	VARCHAR(32)	Sistema operativo	NULLABLE, ej: 'iOS 17.2'
browser	VARCHAR(32)	Navegador	NULLABLE, ej: 'Safari'
referer	VARCHAR(500)	Referer (si aplica)	NULLABLE
country_code	VARCHAR(2)	País (GeoIP)	NULLABLE
region	VARCHAR(64)	Región/provincia	NULLABLE
city	VARCHAR(64)	Ciudad	NULLABLE
latitude	DECIMAL(10,8)	Latitud aproximada	NULLABLE
longitude	DECIMAL(11,8)	Longitud aproximada	NULLABLE
user_uid	INT	Usuario logueado	FK users.uid, NULLABLE
session_id	VARCHAR(64)	ID de sesión	NULLABLE, para tracking conversión
destination_served	VARCHAR(500)	URL final servida	NOT NULL
rule_applied	VARCHAR(64)	Regla de redirección usada	NULLABLE
converted	BOOLEAN	¿Convirtió a venta?	DEFAULT FALSE
conversion_order_id	INT	Pedido si convirtió	FK commerce_order.id, NULLABLE
INDEX: (qr_id, scanned_at) para queries de analytics por rango de fechas.
3.3 Entidad: qr_landing_page
Página de destino personalizada para QRs que no apuntan a contenido existente. Permite crear microsites específicos.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
merchant_id	INT	Comercio propietario	FK merchant_profile.id, NOT NULL
title	VARCHAR(128)	Título de la landing	NOT NULL
slug	VARCHAR(64)	URL amigable	UNIQUE per merchant
template	VARCHAR(32)	Plantilla base	ENUM: product_detail|showcase|menu|review_request|promo|custom
content	JSON	Contenido estructurado	NOT NULL, ver 6.2
header_image_fid	INT	Imagen de cabecera	FK file_managed.fid, NULLABLE
cta_primary_text	VARCHAR(64)	Texto botón principal	NULLABLE, ej: 'Comprar ahora'
cta_primary_url	VARCHAR(500)	URL botón principal	NULLABLE
cta_secondary_text	VARCHAR(64)	Texto botón secundario	NULLABLE
cta_secondary_url	VARCHAR(500)	URL botón secundario	NULLABLE
show_reviews	BOOLEAN	Mostrar reseñas	DEFAULT FALSE
show_related	BOOLEAN	Mostrar relacionados	DEFAULT FALSE
custom_css	TEXT	CSS personalizado	NULLABLE
is_published	BOOLEAN	Publicada	DEFAULT FALSE
created	DATETIME	Fecha de creación	NOT NULL
changed	DATETIME	Última modificación	NOT NULL
 
4. Generación de Códigos QR
4.1 QRGeneratorService
<?php namespace Drupal\jaraba_qr\Service;  class QRGeneratorService {    // Generación   public function generate(DynamicQR $qr, array $options = []): QRImage;   public function generateSvg(DynamicQR $qr, array $options = []): string;   public function generatePng(DynamicQR $qr, int $size = 512): string;   public function generatePdf(DynamicQR $qr, string $template = 'label'): string;      // Batch para impresión   public function generateBatch(array $qrs, string $format, string $template): string;   public function generateProductLabels(MerchantProfile $merchant, array $productIds): string;      // Shortcodes   public function generateShortcode(): string;  // Único, 6-8 chars   public function getFullUrl(DynamicQR $qr): string;  // qr.comercioconecta.es/abc123      // Validación   public function validateShortcode(string $code): bool;   public function isShortcodeAvailable(string $code): bool; }
4.2 Configuración de Estilo (style_config)
// style_config JSON schema {   "size": 512,                    // Tamaño en pixels   "margin": 4,                    // Margen en módulos   "error_correction": "M",        // L, M, Q, H   "foreground_color": "#000000",  // Color de los módulos   "background_color": "#FFFFFF",  // Color de fondo   "logo": {     "enabled": true,     "fid": 123,                   // file_managed.fid     "size_percent": 20            // % del QR que ocupa el logo   },   "shape": {     "module": "square",           // square, rounded, dots, diamond     "eye": "square",              // square, rounded, circle     "eye_color": "#1B4F72"        // Color diferente para ojos   },   "frame": {     "enabled": true,     "text": "Escanea para más info",     "position": "bottom",         // top, bottom     "color": "#1B4F72"   } }
4.3 Plantillas de Impresión
Plantilla	Formato	Uso	Contenido
label_small	30x20mm	Etiquetas de producto	QR + nombre producto + precio
label_medium	50x30mm	Etiquetas con más info	QR + nombre + precio + logo tienda
label_large	70x50mm	Productos premium	QR + imagen producto + nombre + precio
poster_a4	210x297mm	Escaparate, cartel	QR grande + headline + instrucciones
poster_a3	297x420mm	Cartelería exterior	QR muy grande + branding
table_tent	100x150mm	Mesas restaurante	QR + "Escanea para ver el menú"
receipt	80mm ancho	Tickets de caja	QR pequeño + "Déjanos tu opinión"
business_card	85x55mm	Tarjeta de visita	QR + datos de contacto
 
5. Motor de Redirección
5.1 RedirectService
<?php namespace Drupal\jaraba_qr\Service;  class QRRedirectService {    public function handleScan(string $shortcode, Request $request): RedirectResponse;      // Resolución de destino   public function resolveDestination(DynamicQR $qr, ScanContext $context): string;   public function evaluateRules(DynamicQR $qr, ScanContext $context): ?string;      // Registro   public function recordScan(DynamicQR $qr, Request $request): QRScan;   public function incrementCounters(DynamicQR $qr, bool $isUnique): void;      // Validación   public function isQRValid(DynamicQR $qr): ValidationResult;   public function checkScanLimit(DynamicQR $qr): bool;   public function checkDateValidity(DynamicQR $qr): bool;   public function checkPassword(DynamicQR $qr, ?string $password): bool; }
5.2 Reglas de Redirección (redirect_rules)
Las reglas permiten servir diferentes destinos según el contexto del escaneo:
// redirect_rules JSON schema {   "rules": [     {       "id": "rule_1",       "name": "Horario nocturno",       "priority": 10,       "conditions": {         "time_range": { "from": "20:00", "to": "09:00" }       },       "destination": {         "type": "external",         "url": "https://tienda.com/compra-online"       }     },     {       "id": "rule_2",       "name": "iOS users",       "priority": 5,       "conditions": {         "os": ["iOS"]       },       "destination": {         "type": "external",         "url": "https://apps.apple.com/app/mi-tienda"       }     },     {       "id": "rule_3",       "name": "Primeros 100 escaneos",       "priority": 20,       "conditions": {         "scan_count": { "max": 100 }       },       "destination": {         "type": "landing",         "landing_id": 456       }     }   ],   "default": {     "type": "product",     "product_id": 789   } }
5.3 Condiciones Disponibles
Condición	Operadores	Ejemplo
time_range	from, to (HH:MM)	Solo de 10:00 a 14:00
date_range	from, to (YYYY-MM-DD)	Solo en enero 2026
day_of_week	array de 1-7	Solo fines de semana [6,7]
device_type	array	Solo mobile y tablet
os	array	Solo iOS
country	array ISO 3166-1	Solo ES, PT
scan_count	min, max	Primeros 50 escaneos
user_logged_in	boolean	Solo usuarios registrados
user_is_new	boolean	Primera visita del usuario
referrer_contains	string	Viene de instagram.com
ab_test	percentage	50% de escaneos (A/B testing)
 
6. Landings Contextuales
Las landing pages son destinos optimizados para móvil que muestran información contextual basada en el tipo de QR y la configuración del comerciante.
6.1 Plantillas de Landing
Plantilla	Uso	Componentes
product_detail	QR de producto	Imagen, nombre, precio, descripción, variantes, CTA comprar, reseñas
showcase	QR de escaparate	Grid de productos, precios, "Ver en tienda" + "Comprar online"
menu	QR de mesa	Carta completa, categorías, alérgenos, CTA pedir
review_request	QR de ticket	"¿Te gustó tu compra?", botones 1-5 estrellas, link a Google
promo	QR promocional	Oferta destacada, countdown si flash, CTA redimir
loyalty	QR fidelización	Puntos acumulados, siguiente recompensa, historial
custom	Personalizado	Bloques arrastrables: imagen, texto, botón, producto, mapa
6.2 Estructura de Contenido (content JSON)
// content JSON para landing 'showcase' {   "header": {     "title": "Escaparate de Moda Local",     "subtitle": "Novedades de temporada",     "background_image": "fid:123"   },   "sections": [     {       "type": "product_grid",       "title": "En el escaparate ahora",       "products": [101, 102, 103, 104],  // product_retail.id       "columns": 2,       "show_price": true,       "show_stock": true     },     {       "type": "cta_banner",       "text": "¿Te gusta algo? Pasa a probártelo",       "subtext": "Abiertos de 10:00 a 20:30",       "buttons": [         { "text": "📍 Cómo llegar", "action": "maps", "url": "geo:37.5,-4.8" },         { "text": "📞 Llamar", "action": "tel", "url": "tel:+34957123456" }       ]     },     {       "type": "flash_offers",       "title": "⚡ Ofertas activas ahora",       "max_items": 3     }   ],   "footer": {     "show_hours": true,     "show_social": true,     "show_reviews_summary": true   } }
6.3 Componente React: QRLanding
// QRLanding.jsx - Renderiza landing desde content JSON import { useParams } from 'react-router-dom'; import { useQuery } from '@tanstack/react-query';  export function QRLanding() {   const { shortcode } = useParams();   const { data: landing } = useQuery(['qr-landing', shortcode],      () => fetchLanding(shortcode));      if (!landing) return <LoadingSpinner />;      return (     <div className="qr-landing min-h-screen bg-white">       <Header {...landing.header} />       {landing.sections.map((section, i) => (         <Section key={i} {...section} />       ))}       <Footer {...landing.footer} merchant={landing.merchant} />     </div>   ); }
 
7. Sistema de Captación de Reseñas
Una de las funciones más valiosas de los QR dinámicos es la captación de reseñas Google justo después de una compra física. El flujo está optimizado para maximizar la conversión a reseñas de 5 estrellas.
7.1 Flujo de Captación
1. Cliente completa compra en tienda física
2. Ticket impreso incluye QR con tipo = 'ticket' y destino = 'review'
3. Cliente escanea QR (incentivo: "Déjanos tu opinión y participa en el sorteo")
4. Landing muestra: "¿Cómo fue tu experiencia?" con 5 estrellas clickables
5. Si click en 4-5 estrellas → Redirect a Google Review con el comercio preseleccionado
6. Si click en 1-3 estrellas → Formulario interno de feedback (no va a Google)
7. Se registra el intent de reseña y se trackea si llega a publicarse (webhook opcional)
7.2 URL de Reseña Google
// Construcción de URL de reseña Google // Formato: https://search.google.com/local/writereview?placeid=PLACE_ID  public function getGoogleReviewUrl(MerchantProfile $merchant): ?string {   if (!$merchant->google_place_id) {     return null;   }   return sprintf(     'https://search.google.com/local/writereview?placeid=%s',     urlencode($merchant->google_place_id)   ); }  // Ejemplo: https://search.google.com/local/writereview?placeid=ChIJN1t_tDeuEmsRUsoyG83frY4
7.3 Estrategia de Filtrado (Reputation Management)
El sistema implementa un filtro ético que dirige feedback negativo internamente y positivo a Google:
Valoración	Destino	Razón
5 estrellas	Google Reviews	Experiencia excelente, queremos que se vea
4 estrellas	Google Reviews	Buena experiencia, reseña positiva
3 estrellas	Formulario interno	Neutral, pedimos feedback para mejorar
2 estrellas	Formulario interno	Negativa, gestionamos internamente
1 estrella	Formulario interno + alerta	Muy negativa, contacto inmediato del comercio
Nota ética: Este sistema es común en la industria (Trustpilot, etc.) y no viola las políticas de Google siempre que no se impida al usuario dejar reseña negativa si lo desea.
7.4 Entidad: review_intent
Registra cada intento de reseña para analytics y seguimiento.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
qr_id	INT	QR de origen	FK dynamic_qr.id, NOT NULL
merchant_id	INT	Comercio	FK merchant_profile.id, NOT NULL
scan_id	INT	Escaneo asociado	FK qr_scan.id, NOT NULL
rating_clicked	INT	Estrellas clickadas	1-5, NOT NULL
redirected_to_google	BOOLEAN	Enviado a Google	NOT NULL
internal_feedback	TEXT	Feedback interno	NULLABLE
contacted_by_merchant	BOOLEAN	Comercio contactó	DEFAULT FALSE
created_at	DATETIME	Fecha/hora	NOT NULL
 
8. Integración con Fidelización
Los QR dinámicos se integran con el sistema de fidelización para permitir check-ins, acumulación de puntos, y canje de recompensas.
8.1 QR de Check-in
• El comercio tiene un QR tipo 'loyalty' visible en mostrador
• Cliente escanea al entrar o al pagar
• Si no está logueado → Pantalla de registro/login rápido
• Si está logueado → Se registra visita + puntos base
• Pantalla muestra: puntos actuales, siguiente recompensa, ofertas exclusivas
8.2 Puntos por Escaneo
Acción	Puntos Base	Configuración
Check-in en tienda	10	1 por día máximo
Escanear producto	2	5 por día máximo
Dejar reseña Google	50	1 por mes máximo
Compartir en redes	20	Verificación de share
Referir amigo	100	Cuando amigo hace primera compra
8.3 QR Personalizado por Cliente
Los clientes registrados pueden tener un QR personal en su app/wallet que el comerciante escanea para aplicar descuentos de fidelidad o registrar compras físicas al programa.
 
9. Analytics de QR
9.1 Métricas por QR
Métrica	Descripción	Cálculo
Total scans	Escaneos totales	COUNT(qr_scan)
Unique scans	Visitantes únicos	COUNT(DISTINCT ip_hash)
Scan rate	Escaneos/día promedio	total_scans / days_active
Peak hour	Hora con más escaneos	MODE(HOUR(scanned_at))
Device split	% por dispositivo	GROUP BY device_type
Geo distribution	Distribución geográfica	GROUP BY city
Conversion rate	% que compra	conversions / unique_scans
Avg. time to convert	Tiempo hasta compra	AVG(order.created - scan.scanned_at)
9.2 Métricas Agregadas por Comercio
• Total QRs activos
• Escaneos totales del mes
• QR más escaneado (top 5)
• Tipo de QR más efectivo (por conversión)
• Reseñas captadas via QR
• Revenue atribuido a QR (conversiones)
9.3 Heatmap de Escaneos
El dashboard del comerciante incluye un heatmap que muestra:
• Horas del día con más escaneos (eje X: hora, eje Y: día de semana)
• Mapa geográfico con ubicación aproximada de escaneos
• Comparativa de rendimiento entre diferentes QRs
 
10. APIs REST
10.1 Endpoints para Comerciantes
Método	Endpoint	Descripción	Auth
GET	/api/v1/qr-codes	Listar QRs del comercio	Merchant
POST	/api/v1/qr-codes	Crear QR dinámico	Merchant
GET	/api/v1/qr-codes/{id}	Detalle de QR	Merchant
PATCH	/api/v1/qr-codes/{id}	Actualizar QR	Merchant
DELETE	/api/v1/qr-codes/{id}	Eliminar QR	Merchant
GET	/api/v1/qr-codes/{id}/image	Descargar imagen QR	Merchant
GET	/api/v1/qr-codes/{id}/stats	Estadísticas del QR	Merchant
GET	/api/v1/qr-codes/{id}/scans	Historial de escaneos	Merchant
POST	/api/v1/qr-codes/batch	Generar batch de QRs	Merchant
GET	/api/v1/qr-codes/export	Exportar todos como PDF	Merchant
10.2 Endpoints Públicos
Método	Endpoint	Descripción	Auth
GET	/q/{shortcode}	Redirect principal (escaneo)	Público
GET	/api/v1/qr/{shortcode}/landing	Datos de landing (SPA)	Público
POST	/api/v1/qr/{shortcode}/scan	Registrar escaneo (si JS)	Público
POST	/api/v1/qr/{shortcode}/review-intent	Registrar intent de reseña	Público
GET	/api/v1/qr/{shortcode}/validate	Validar QR (activo, vigente)	Público
10.3 Endpoints de Landings
Método	Endpoint	Descripción	Auth
GET	/api/v1/landings	Listar landings del comercio	Merchant
POST	/api/v1/landings	Crear landing page	Merchant
GET	/api/v1/landings/{id}	Detalle de landing	Merchant
PATCH	/api/v1/landings/{id}	Actualizar landing	Merchant
DELETE	/api/v1/landings/{id}	Eliminar landing	Merchant
POST	/api/v1/landings/{id}/preview	Generar preview URL	Merchant
 
11. Flujos de Automatización (ECA)
11.1 ECA-QR-001: Escaneo Registrado
Trigger: Nuevo qr_scan creado
1. Incrementar dynamic_qr.total_scans
2. Si ip_hash es nuevo → incrementar unique_scans
3. Actualizar last_scan_at
4. Si scan_limit alcanzado → desactivar QR
11.2 ECA-QR-002: Conversión Atribuida
Trigger: Pedido completado con session_id que matchea qr_scan
1. Marcar qr_scan.converted = TRUE
2. Asociar conversion_order_id
3. Actualizar métricas de conversión del QR
11.3 ECA-QR-003: Reseña Negativa Recibida
Trigger: review_intent con rating_clicked <= 2
1. Enviar notificación al comerciante: "Feedback negativo recibido"
2. Crear tarea en CRM para seguimiento
3. Si hay email del cliente → programar email de disculpa (opcional)
11.4 ECA-QR-004: QR Expirado
Trigger: Cron detecta dynamic_qr.valid_until < NOW()
1. Marcar QR como is_active = FALSE
2. Notificar al comerciante: "Tu QR [nombre] ha expirado"
3. Sugerir renovación si tuvo buen rendimiento
 
12. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 1	Semana 1-2	Entidad dynamic_qr y qr_scan. QRGeneratorService básico (SVG/PNG). Shortcode generator. Redirect Engine básico.	62_Commerce_Core
Sprint 2	Semana 3-4	Sistema de reglas de redirección. Validación de QR (fechas, límites, password). UI de creación en Merchant Portal.	Sprint 1
Sprint 3	Semana 5-6	Entidad qr_landing_page. Builder de landings con plantillas. Componente React QRLanding.	Sprint 2
Sprint 4	Semana 7-8	Sistema de captación de reseñas. Integración Google Place ID. Entidad review_intent. Flujo de filtrado.	Sprint 3 + 77_Reviews
Sprint 5	Semana 9-10	Integración fidelización. QR de check-in. Puntos por escaneo. QR personalizado por cliente.	Sprint 4
Sprint 6	Semana 11-12	Analytics dashboard. Heatmaps. Plantillas de impresión PDF. Batch export. QA y go-live.	Sprint 5
12.1 Criterios de Aceptación Sprint 1
✓ Crear QR dinámico desde API
✓ Generar imagen QR en SVG y PNG
✓ Shortcode único de 6-8 caracteres
✓ Redirect funcional con registro de scan
✓ GeoIP básico funcionando
12.2 Dependencias Externas
• endroid/qr-code: Generación de QR en PHP
• MaxMind GeoIP2: Geolocalización por IP
• Google Places API: Obtención de place_id
• React + TailwindCSS: Frontend de landings
• TCPDF / Dompdf: Generación de PDFs para impresión
--- Fin del Documento ---
65_ComercioConecta_Dynamic_QR_v1.docx | Jaraba Impact Platform | Enero 2026

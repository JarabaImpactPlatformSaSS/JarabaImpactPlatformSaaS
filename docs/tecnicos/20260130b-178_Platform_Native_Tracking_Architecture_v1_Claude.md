
JARABA IMPACT PLATFORM
Ecosistema SaaS Multi-Tenant
ARQUITECTURA DE TRACKING
100% NATIVA
Sin Dependencias Externas de Analytics
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica para Implementación
Código:	178_Platform_Native_Tracking_Architecture_v1
Horas Estimadas:	180-240 horas
Filosofía:	Sin Humo - Mínimas Dependencias Externas
Destinatario:	EDI Google Antigravity - Equipo de Desarrollo
 
1. Resumen Ejecutivo
Este documento especifica la arquitectura completa para implementar un sistema de tracking y analytics 100% nativo en el Ecosistema Jaraba, eliminando dependencias de Google Analytics, Google Tag Manager y otras herramientas externas de medición.
1.1 Objetivos Estratégicos
•	Control total: 100% de los datos de usuarios bajo control propio
•	GDPR/RGPD nativo: Cumplimiento de privacidad sin herramientas externas
•	Zero vendor lock-in: Independencia total de Google, Facebook y otros proveedores
•	Multi-tenant nativo: Aislamiento completo de datos por tenant
•	Coste predecible: Sin costes variables por volumen de datos
•	Bloqueadores inmunes: Server-side tracking no afectado por AdBlock
1.2 Componentes del Sistema
Componente	Función	Reemplaza
jaraba_analytics	Core de métricas y eventos	Google Analytics 4
Matomo Self-Hosted	Web analytics avanzado	Google Analytics
Pixel Manager	Gestión pixels server-side	Google Tag Manager
Consent Manager	Gestión consentimiento GDPR	CookieBot, OneTrust
A/B Testing	Experimentación nativa	Optimizely, VWO

1.3 Ahorro Estimado Anual
Herramienta Externa	Coste/Año	Con Arquitectura Nativa
Google Analytics 360	€150,000+	€0 (incluido)
Google Tag Manager Server	€3,000-12,000	€0 (incluido)
Optimizely/VWO	€6,000-24,000	€0 (incluido)
CookieBot/OneTrust	€1,200-6,000	€0 (incluido)
TOTAL AHORRO	€160,000+/año	Coste único dev
 
2. Arquitectura General del Sistema
2.1 Stack Tecnológico
Capa	Tecnología
Core CMS	Drupal 11 con módulos custom jaraba_*
Web Analytics	Matomo 5.x self-hosted (GDPR compliant nativo)
Tracking Events	Custom entities analytics_event + analytics_daily
Visualización	Chart.js 4.x + React dashboards custom
Time Series	MySQL 8.0 partitioned tables + Redis cache
Server-Side Tracking	PHP services + ECA automation
Exportación	CSV, Excel (PhpSpreadsheet), PDF (Entity Print)
Alertas	ECA + custom thresholds + email/push notifications
Cache Métricas	Redis con TTL configurable (5-60 min)

2.2 Diagrama de Arquitectura
┌─────────────────────────────────────────────────────────────────────────┐
│                        DRUPAL 11 CORE                                   │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌─────────────┐  │
│  │jaraba_       │  │Retargeting   │  │Consent       │  │A/B Testing  │  │
│  │analytics     │  │Pixel Manager │  │Manager       │  │Framework    │  │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘  └──────┬──────┘  │
│         └─────────────────┴─────────────────┴─────────────────┘         │
│                                  │                                      │
│                          ┌───────▼───────┐                              │
│                          │  ECA Module   │                              │
│                          │ (Automation)  │                              │
│                          └───────┬───────┘                              │
└──────────────────────────────────┼──────────────────────────────────────┘
                                   │
        ┌──────────────────────────┼──────────────────────────┐
        │                          │                          │
        ▼                          ▼                          ▼
┌───────────────┐        ┌───────────────┐        ┌───────────────┐
│    Matomo     │        │  MySQL 8.0    │        │    Redis      │
│ Self-Hosted   │        │ (Partitioned) │        │   (Cache)     │
└───────────────┘        └───────────────┘        └───────────────┘
 
3. Módulo jaraba_analytics (Core)
El módulo jaraba_analytics es el núcleo del sistema de tracking nativo. Proporciona captura de eventos, agregación de métricas, dashboards por rol y APIs de consulta.
3.1 Entidad: analytics_event
Almacena eventos individuales para análisis de comportamiento y funnel de conversión.
Campo	Tipo	Descripción	Restricciones
id	BIGSERIAL	ID interno autoincremental	PRIMARY KEY
tenant_id	INT	ID del tenant (Group)	NOT NULL, INDEX, FK
event_type	VARCHAR(50)	Tipo de evento	NOT NULL, INDEX
event_data	JSON	Datos específicos del evento	NOT NULL
user_id	INT	Usuario (si logged)	NULLABLE, INDEX
session_id	VARCHAR(64)	ID de sesión único	NOT NULL, INDEX
visitor_id	VARCHAR(64)	ID visitante (cookie)	NOT NULL, INDEX
device_type	VARCHAR(20)	desktop/mobile/tablet	NULLABLE
browser	VARCHAR(50)	Navegador detectado	NULLABLE
os	VARCHAR(50)	Sistema operativo	NULLABLE
referrer	VARCHAR(500)	URL de origen	NULLABLE
page_url	VARCHAR(500)	URL de la página	NOT NULL
utm_source	VARCHAR(100)	UTM source	NULLABLE, INDEX
utm_medium	VARCHAR(100)	UTM medium	NULLABLE
utm_campaign	VARCHAR(100)	UTM campaign	NULLABLE, INDEX
utm_content	VARCHAR(100)	UTM content	NULLABLE
utm_term	VARCHAR(100)	UTM term	NULLABLE
ip_hash	VARCHAR(64)	IP hasheada (GDPR)	NULLABLE
country	CHAR(2)	Código país ISO	NULLABLE
region	VARCHAR(100)	Región/Provincia	NULLABLE
created	TIMESTAMP	Momento del evento	NOT NULL, INDEX

3.2 Entidad: analytics_daily
Métricas agregadas diarias precalculadas para dashboards rápidos.
Campo	Tipo	Descripción	Restricciones
id	SERIAL	ID interno	PRIMARY KEY
tenant_id	INT	ID del tenant	NOT NULL, INDEX
date	DATE	Fecha del agregado	NOT NULL, INDEX
page_views	INT	Total páginas vistas	DEFAULT 0
unique_visitors	INT	Visitantes únicos	DEFAULT 0
sessions	INT	Sesiones totales	DEFAULT 0
new_users	INT	Nuevos registros	DEFAULT 0
total_revenue	DECIMAL(12,2)	Ingresos del día	DEFAULT 0
orders_count	INT	Número de pedidos	DEFAULT 0
avg_order_value	DECIMAL(10,2)	Ticket medio	COMPUTED
conversion_rate	DECIMAL(5,4)	Tasa conversión	COMPUTED
bounce_rate	DECIMAL(5,4)	Tasa de rebote	COMPUTED
avg_session_duration	INT	Duración media (seg)	DEFAULT 0
top_pages	JSON	Top 10 páginas	NULLABLE
top_referrers	JSON	Top 10 referrers	NULLABLE
device_breakdown	JSON	% por dispositivo	NULLABLE
geo_breakdown	JSON	% por ubicación	NULLABLE
 
3.3 Eventos E-commerce Estándar
Sistema de captura de eventos compatible con GA4 Enhanced Ecommerce para facilitar migración y comparativas.
Evento	Datos Capturados	Trigger
page_view	URL, referrer, device, user_id, title	Cada carga de página
product_view	product_id, name, price, category, brand	Ficha de producto
product_list_view	list_name, products[], position	Listado productos
add_to_cart	product_id, quantity, price, variation	Click añadir carrito
remove_from_cart	product_id, quantity	Eliminar del carrito
begin_checkout	cart_value, item_count, coupon	Inicio checkout
add_shipping_info	shipping_method, postal_code, cost	Paso envío
add_payment_info	payment_method, card_type	Paso pago
purchase	order_id, value, items[], tax, shipping	Compra completada
refund	order_id, refund_amount, items[]	Reembolso procesado
search	query, results_count, filters_applied	Búsqueda realizada
apply_coupon	coupon_code, success, discount_value	Aplicar cupón
lead	form_name, lead_type, value	Formulario enviado
signup	method, user_type, referral_code	Registro completado
login	method, user_type	Inicio sesión

3.4 AnalyticsService (PHP)
<?php
namespace Drupal\jaraba_analytics\Service;

class AnalyticsService {
  // Tracking de eventos
  public function trackEvent(string $eventType, array $data): void;
  public function trackPageView(Request $request): void;
  public function trackEcommerceEvent(string $event, array $items): void;
  
  // Métricas agregadas
  public function getDailyMetrics(int $tenantId, DateRange $range): array;
  public function getRealtimeVisitors(int $tenantId): int;
  public function getConversionFunnel(int $tenantId, DateRange $range): array;
  
  // Segmentación
  public function getTopPages(int $tenantId, int $limit = 10): array;
  public function getTrafficSources(int $tenantId, DateRange $range): array;
  public function getUserCohorts(int $tenantId, string $cohortType): array;
  
  // Exportación
  public function exportToCSV(int $tenantId, DateRange $range): string;
  public function generateReport(int $tenantId, ReportConfig $config): Report;
}
 
4. Retargeting Pixel Manager
Sistema de gestión centralizada de píxeles de seguimiento para plataformas publicitarias, con implementación server-side para máxima precisión y cumplimiento GDPR. Elimina la necesidad de Google Tag Manager.
4.1 Plataformas Soportadas
Plataforma	Tipo Píxel	Server-Side API	Prioridad
Meta (FB/IG)	Meta Pixel	Conversions API (CAPI)	Alta
Google Ads	gtag.js	Measurement Protocol	Alta
LinkedIn	Insight Tag	Conversions API	Media
TikTok	TikTok Pixel	Events API	Baja
Twitter/X	Twitter Pixel	Limitado	Baja

4.2 Entidad: tracking_pixel
Campo	Tipo	Descripción	Restricciones
id	SERIAL	ID interno	PRIMARY KEY
tenant_id	INT	ID del tenant	NOT NULL, FK
platform	VARCHAR(20)	meta/google/linkedin/tiktok	NOT NULL
pixel_id	VARCHAR(100)	ID del pixel en plataforma	NOT NULL
access_token	TEXT	Token API (encriptado AES-256)	ENCRYPTED
server_side_enabled	BOOLEAN	Activar server-side	DEFAULT TRUE
client_side_enabled	BOOLEAN	Activar client-side	DEFAULT FALSE
events_enabled	JSON	Eventos activos por píxel	NOT NULL
test_mode	BOOLEAN	Modo debug/test	DEFAULT FALSE
status	VARCHAR(20)	active/paused/error	DEFAULT active

4.3 Event Mapping Universal
Mapeo automático de eventos internos a nomenclatura específica de cada plataforma:
Evento Interno	Meta	Google	LinkedIn	TikTok
page_view	PageView	page_view	PageView	PageView
lead	Lead	generate_lead	Lead	SubmitForm
signup	CompleteRegistration	sign_up	SignUp	Registration
add_to_cart	AddToCart	add_to_cart	AddToCart	AddToCart
purchase	Purchase	purchase	Conversion	PlaceAnOrder
view_content	ViewContent	view_item	ViewContent	ViewContent
contact	Contact	contact	Contact	Contact
schedule	Schedule	book_appointment	BookAppointment	Schedule
 
5. Consent Manager (GDPR/RGPD)
Sistema nativo de gestión de consentimiento de cookies y tracking, cumpliendo con GDPR/RGPD sin necesidad de herramientas externas como CookieBot u OneTrust.
5.1 Entidad: consent_record
Campo	Tipo	Descripción	Restricciones
id	SERIAL	ID interno	PRIMARY KEY
visitor_id	VARCHAR(64)	ID visitante único	NOT NULL, UNIQUE
tenant_id	INT	ID del tenant	NOT NULL, FK
consent_analytics	BOOLEAN	Cookies de analytics	DEFAULT FALSE
consent_marketing	BOOLEAN	Cookies de marketing	DEFAULT FALSE
consent_functional	BOOLEAN	Cookies funcionales	DEFAULT TRUE
consent_necessary	BOOLEAN	Cookies necesarias	DEFAULT TRUE
ip_country	CHAR(2)	País para determinar ley	NULLABLE
policy_version	VARCHAR(20)	Versión de política aceptada	NOT NULL
granted_at	TIMESTAMP	Fecha de consentimiento	NOT NULL
updated_at	TIMESTAMP	Última modificación	NOT NULL

5.2 Configuración Banner por Tenant
•	Diseño personalizable con colores del tenant (CSS variables)
•	Textos multiidioma (ES/EN/PT/FR/DE) con traducciones editables
•	Opciones granulares: necesarias/funcionales/analytics/marketing
•	Link automático a política de privacidad del tenant
•	Posición configurable: bottom-bar, modal, corner-popup
•	Modo estricto GDPR: bloquea scripts hasta consentimiento explícito
5.3 Flujo ECA: Consent Banner
Trigger: Primera visita de usuario (no cookie jaraba_consent)
1.	Verificar si visitor_id existe en consent_record
2.	Si no existe: Mostrar banner de consentimiento
3.	Al aceptar/rechazar: POST /api/v1/tracking/consent
4.	Configurar cookies: jaraba_consent, jaraba_visitor_id
5.	Si consent_marketing = true: Inicializar píxeles client-side
6.	Registrar consent_record con policy_version actual
 
6. A/B Testing Framework Nativo
Sistema de experimentación y optimización de conversiones integrado, reemplazando herramientas como Optimizely, VWO o Google Optimize.
6.1 Entidad: experiment
Campo	Tipo	Descripción	Restricciones
id	SERIAL	ID interno	PRIMARY KEY
tenant_id	INT	ID del tenant	NOT NULL, FK
name	VARCHAR(255)	Nombre del experimento	NOT NULL
type	VARCHAR(20)	landing/email/cta/pricing	NOT NULL
status	VARCHAR(20)	draft/running/paused/completed	DEFAULT draft
goal_metric	VARCHAR(50)	conversion/signup/purchase/click	NOT NULL
traffic_allocation	INT	% tráfico en experimento	DEFAULT 100
min_sample_size	INT	Muestras mínimas por variante	DEFAULT 100
confidence_level	DECIMAL(3,2)	Nivel confianza (0.95)	DEFAULT 0.95
auto_winner	BOOLEAN	Selección automática ganador	DEFAULT FALSE
started_at	TIMESTAMP	Inicio del experimento	NULLABLE
ended_at	TIMESTAMP	Fin del experimento	NULLABLE

6.2 Entidad: experiment_variant
Campo	Tipo	Descripción	Restricciones
id	SERIAL	ID interno	PRIMARY KEY
experiment_id	INT	FK al experimento	NOT NULL, FK
name	VARCHAR(100)	Nombre variante (A/B/C)	NOT NULL
is_control	BOOLEAN	Es la variante control	DEFAULT FALSE
weight	INT	Peso de distribución	DEFAULT 50
config	JSON	Configuración de la variante	NOT NULL
visitors	INT	Total visitantes asignados	DEFAULT 0
conversions	INT	Total conversiones	DEFAULT 0
conversion_rate	DECIMAL(5,4)	Tasa de conversión	COMPUTED
is_winner	BOOLEAN	Variante ganadora	DEFAULT FALSE

6.3 Cálculo de Significancia Estadística
El sistema implementa test de hipótesis Z-test para proporciones con las siguientes métricas:
•	P-value: Probabilidad de obtener el resultado observado si no hay diferencia real
•	Confidence Interval: Rango donde se encuentra la diferencia real (95%)
•	Lift: Mejora porcentual de la variante vs control
•	Power: Probabilidad de detectar una diferencia si existe (80% mínimo)
 
7. APIs REST del Sistema de Tracking
7.1 Endpoints de Analytics
Método	Endpoint	Descripción
POST	/api/v1/analytics/event	Registrar evento de tracking
POST	/api/v1/analytics/batch	Registrar múltiples eventos
GET	/api/v1/analytics/dashboard	KPIs principales del dashboard
GET	/api/v1/analytics/realtime	Visitantes en tiempo real
GET	/api/v1/analytics/funnel	Datos de funnel de conversión
GET	/api/v1/analytics/traffic-sources	Fuentes de tráfico
GET	/api/v1/analytics/pages/top	Top páginas por visitas
GET	/api/v1/analytics/geographic	Distribución geográfica

7.2 Endpoints de Tracking Pixels
Método	Endpoint	Descripción
GET	/api/v1/tracking/pixels	Listar píxeles del tenant
POST	/api/v1/tracking/pixels	Crear nuevo píxel
PATCH	/api/v1/tracking/pixels/{id}	Actualizar configuración píxel
DELETE	/api/v1/tracking/pixels/{id}	Eliminar píxel
POST	/api/v1/tracking/pixels/{id}/test	Enviar evento de prueba
GET	/api/v1/tracking/pixels/{id}/health	Estado de salud del píxel

7.3 Endpoints de Consent
Método	Endpoint	Descripción
POST	/api/v1/tracking/consent	Registrar consentimiento
GET	/api/v1/tracking/consent/{visitor_id}	Obtener estado consentimiento
PATCH	/api/v1/tracking/consent/{visitor_id}	Actualizar preferencias
DELETE	/api/v1/tracking/consent/{visitor_id}	Derecho al olvido (GDPR Art. 17)

7.4 Endpoints de A/B Testing
Método	Endpoint	Descripción
GET	/api/v1/experiments	Listar experimentos
POST	/api/v1/experiments	Crear experimento
GET	/api/v1/experiments/{id}/variant	Obtener variante para visitor
POST	/api/v1/experiments/{id}/conversion	Registrar conversión
GET	/api/v1/experiments/{id}/stats	Estadísticas del experimento
POST	/api/v1/experiments/{id}/start	Iniciar experimento
POST	/api/v1/experiments/{id}/stop	Detener experimento
 
8. Flujos de Automatización ECA
8.1 ECA: Agregación Diaria de Métricas
Trigger: Cron diario 02:00 UTC
7.	Obtener lista de todos los tenants activos
8.	Para cada tenant: Agregar analytics_event del día anterior
9.	Calcular métricas: page_views, unique_visitors, sessions, bounce_rate
10.	Calcular conversiones: orders_count, total_revenue, conversion_rate
11.	Generar JSON: top_pages, top_referrers, device_breakdown, geo_breakdown
12.	Insertar registro en analytics_daily
13.	Invalidar cache Redis de métricas del tenant
8.2 ECA: Server-Side Event Dispatch
Trigger: analytics_event creado con server_side_required = true
14.	Verificar consent_record para visitor_id
15.	Si consent_marketing = false: Abortar envío a plataformas publicitarias
16.	Hashear user_data (email, phone) con SHA256 si PII presente
17.	Para cada tracking_pixel activo del tenant:
○	Meta: Llamar Conversions API con event_id para deduplicación
○	Google: Llamar Measurement Protocol con client_id
○	LinkedIn: Llamar Conversions API con conversion_id
18.	Guardar server_response en tracking_event_log
19.	Si error: Encolar para retry (máx 3 intentos, backoff exponencial)
8.3 ECA: Pixel Health Check
Trigger: Cron diario 08:00 UTC
20.	Para cada tenant con tracking_pixel activo:
21.	Verificar último evento enviado exitosamente por píxel
22.	Si > 48h sin eventos exitosos: Enviar test event
23.	Si test event falla: Marcar pixel.status = error
24.	Notificar administrador del tenant vía email
25.	Generar reporte semanal de health status consolidado
8.4 ECA: Auto-Winner A/B Test
Trigger: Cada 100 conversiones en experimento activo con auto_winner = true
26.	Verificar que todas las variantes tienen min_sample_size
27.	Calcular p-value entre control y cada variante
28.	Si p-value < (1 - confidence_level) para alguna variante:
○	Marcar variante como is_winner = true
○	Cambiar experiment.status = completed
○	Redirigir 100% tráfico a variante ganadora
29.	Notificar al tenant con resultados del experimento
 
9. Integración Matomo Self-Hosted
Matomo proporciona analytics web avanzado como complemento al sistema nativo, ofreciendo funcionalidades adicionales de análisis de comportamiento, heatmaps, session recordings y más.
9.1 Configuración Multi-Tenant
Parámetro	Configuración
Versión	Matomo 5.x (última estable)
Hosting	Mismo servidor IONOS (subdirectorio /matomo)
Base de datos	MySQL 8.0 compartida (prefijo matomo_)
Multi-site	Un Site ID por tenant en Drupal
Tracking	JavaScript tracker + PHP server-side
Privacidad	IP anonimization, Do Not Track respetado
Retención datos	Configurable por tenant (default: 365 días)

9.2 Plugins Recomendados
•	CustomDimensions: Dimensiones personalizadas por vertical
•	TagManager: Gestión de tags sin código (alternativa a GTM)
•	FormAnalytics: Análisis de abandono de formularios
•	Funnels: Embudos de conversión visuales
•	HeatmapSessionRecording: Mapas de calor y grabaciones
•	SearchEngineKeywordsPerformance: SEO analytics
•	GDPR Tools: Anonimización y derecho al olvido
9.3 Sincronización Matomo - jaraba_analytics
Los datos de Matomo se sincronizan con jaraba_analytics para dashboards unificados:
•	Cron cada hora: Importar métricas agregadas de Matomo a analytics_daily
•	API bidireccional: Eventos de jaraba_analytics se envían también a Matomo
•	Dashboard híbrido: Widgets de Matomo embebidos + métricas nativas
 
10. Roadmap de Implementación
Sprint	Timeline	Entregables	Horas Est.
1	Semana 1-2	Entidades analytics_event, analytics_daily. Migrations. AnalyticsService básico.	30-40h
2	Semana 3-4	Tracking eventos e-commerce. JavaScript tracker. APIs de captura.	25-35h
3	Semana 5-6	Dashboard React: KPIs, gráficos, funnel. Integración Chart.js.	30-40h
4	Semana 7-8	Consent Manager: Entidad, banner, APIs. Flujos ECA consent.	20-30h
5	Semana 9-10	Retargeting Pixel Manager: Meta CAPI, Google Measurement Protocol.	25-35h
6	Semana 11-12	LinkedIn, TikTok integration. Event mapping. Server-side dedup.	15-20h
7	Semana 13-14	A/B Testing: Entidades, asignación variantes, tracking conversiones.	20-30h
8	Semana 15-16	Significancia estadística. Auto-winner. Dashboard experimentos.	15-25h
9	Semana 17-18	Matomo setup. Sincronización. Plugins. Multi-tenant config.	20-30h
10	Semana 19-20	QA completo. Optimización. Documentación. Go-live.	20-30h
TOTAL	20 semanas	Sistema completo de tracking nativo	220-315h

10.1 Criterios de Aceptación Sprint 1
•	Entidades analytics_event y analytics_daily creadas con migrations
•	AnalyticsService puede registrar y consultar eventos
•	Cron de agregación diaria funcionando
•	Multi-tenancy verificado con aislamiento de datos
10.2 Dependencias Técnicas
•	jaraba_core y jaraba_tenant funcionando
•	MySQL 8.0 con particiones habilitadas
•	Redis configurado para cache
•	ECA Module instalado y configurado
•	Cuentas de desarrollador: Meta Business, Google Ads, LinkedIn
•	SSL configurado en todos los dominios de tenant
 
11. Seguridad y Privacidad
Aspecto	Implementación
Tokens de acceso	Encriptados con AES-256-GCM en base de datos
User data (PII)	Hasheado con SHA256 antes de envío a terceros
IP addresses	Hasheadas, almacenadas máximo 30 días para deduplicación
Consent records	Inmutables con versionado de política. Audit trail completo.
Multi-tenancy	Aislamiento completo de píxeles, eventos y métricas por tenant
GDPR/RGPD	Derecho al olvido implementado vía API con cascade delete
Data retention	Configurable por tenant. Purge automático de datos antiguos.
Audit logging	Todas las operaciones de tracking logueadas para compliance
Rate limiting	100 req/min por tenant en APIs de tracking
Bot detection	Filtrado de crawlers y bots conocidos

— Fin del Documento —
Jaraba Impact Platform | Arquitectura Tracking Nativo v1.0 | Enero 2026

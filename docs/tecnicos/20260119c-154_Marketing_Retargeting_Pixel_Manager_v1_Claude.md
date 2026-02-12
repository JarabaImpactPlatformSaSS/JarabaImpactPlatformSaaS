RETARGETING PIXEL MANAGER
Extensión jaraba_analytics
Gestión Centralizada de Píxeles Multi-Plataforma con Server-Side Tracking
Versión:	1.0
Fecha:	Enero 2026
Código:	154_Marketing_Retargeting_Pixel_Manager_v1
Estado:	Especificación Técnica para Implementación
Horas Estimadas:	10-15 horas
Módulo Base:	jaraba_analytics
Dependencias:	jaraba_core, Meta Conversions API, Google Tag Manager
1. Resumen Ejecutivo
La extensión Retargeting Pixel Manager proporciona gestión centralizada de píxeles de seguimiento para múltiples plataformas publicitarias, incluyendo implementación server-side para máxima precisión y cumplimiento con normativas de privacidad. Elimina dependencias de Google Tag Manager externo y garantiza tracking consistente incluso con bloqueadores de anuncios.
1.1 Capacidades Principales
•	Gestión centralizada de píxeles Meta, Google, LinkedIn, TikTok
•	Server-side tracking via Conversions API (Meta CAPI)
•	Deduplicación automática client-side + server-side
•	Consent management integrado con GDPR/RGPD
•	Event mapping universal entre plataformas
•	Debug mode para verificación de eventos
•	Multi-tenant con aislamiento completo de píxeles
1.2 Plataformas Soportadas
Plataforma	Tipo Píxel	Server-Side	Prioridad
Meta (FB/IG)	Meta Pixel	✅ CAPI	Alta
Google Ads	gtag.js	✅ Measurement Protocol	Alta
LinkedIn	Insight Tag	✅ Conversions API	Media
TikTok	TikTok Pixel	✅ Events API	Baja
Twitter/X	Twitter Pixel	⚠️ Limitado	Baja
2. Arquitectura Técnica
2.1 Entidad: tracking_pixel
Configuración de píxeles por tenant y plataforma.
Campo	Tipo	Descripción
id	SERIAL	Primary key
uuid	VARCHAR(36)	Identificador público único
tenant_id	INT FK	Referencia a tenant
platform	VARCHAR(20)	meta|google|linkedin|tiktok|twitter
pixel_id	VARCHAR(100)	ID del píxel en la plataforma
access_token	TEXT ENCRYPTED	Token para server-side (encriptado)
is_active	BOOLEAN	Píxel activo/inactivo
server_side_enabled	BOOLEAN	Habilitar tracking server-side
test_event_code	VARCHAR(50)	Código para modo test (Meta)
domains	JSON	Lista de dominios autorizados
created_at	TIMESTAMP	Fecha de creación
updated_at	TIMESTAMP	Última actualización
2.2 Entidad: tracking_event
Registro de eventos enviados para auditoría y deduplicación.
Campo	Tipo	Descripción
id	SERIAL	Primary key
event_id	VARCHAR(36)	UUID único para deduplicación
tenant_id	INT FK	Referencia a tenant
pixel_id	INT FK	Referencia a tracking_pixel
event_name	VARCHAR(50)	PageView|Lead|Purchase|etc
event_source	VARCHAR(20)	client|server|both
user_data	JSON ENCRYPTED	Datos hasheados del usuario
custom_data	JSON	Datos personalizados del evento
event_source_url	VARCHAR(500)	URL donde ocurrió el evento
client_ip_address	VARCHAR(45)	IP del cliente (IPv4/IPv6)
client_user_agent	VARCHAR(500)	User agent del navegador
fbc	VARCHAR(100)	Facebook click ID
fbp	VARCHAR(100)	Facebook browser ID
gclid	VARCHAR(100)	Google click ID
server_response	JSON	Respuesta de la plataforma
created_at	TIMESTAMP	Timestamp del evento
2.3 Entidad: consent_record
Registro de consentimiento de usuarios para cumplimiento GDPR.
Campo	Tipo	Descripción
id	SERIAL	Primary key
tenant_id	INT FK	Referencia a tenant
visitor_id	VARCHAR(36)	ID anónimo del visitante
consent_analytics	BOOLEAN	Consentimiento para analytics
consent_marketing	BOOLEAN	Consentimiento para marketing
consent_functional	BOOLEAN	Consentimiento funcional
ip_address	VARCHAR(45)	IP al momento del consentimiento
consent_version	VARCHAR(10)	Versión de política aceptada
created_at	TIMESTAMP	Fecha de consentimiento
updated_at	TIMESTAMP	Última modificación
 
3. API REST Endpoints
3.1 Gestión de Píxeles
Método	Endpoint	Descripción
GET	/api/v1/tracking/pixels	Listar píxeles del tenant
POST	/api/v1/tracking/pixels	Crear nuevo píxel
GET	/api/v1/tracking/pixels/{uuid}	Obtener configuración de píxel
PATCH	/api/v1/tracking/pixels/{uuid}	Actualizar píxel
DELETE	/api/v1/tracking/pixels/{uuid}	Eliminar píxel
POST	/api/v1/tracking/pixels/{uuid}/verify	Verificar conexión con plataforma
3.2 Tracking de Eventos
Método	Endpoint	Descripción
POST	/api/v1/tracking/events	Enviar evento server-side
POST	/api/v1/tracking/events/batch	Enviar múltiples eventos
GET	/api/v1/tracking/events/recent	Últimos eventos (debug)
GET	/api/v1/tracking/events/stats	Estadísticas de eventos
3.3 Consent Management
Método	Endpoint	Descripción
POST	/api/v1/tracking/consent	Registrar consentimiento
GET	/api/v1/tracking/consent/{visitor_id}	Obtener estado de consentimiento
DELETE	/api/v1/tracking/consent/{visitor_id}	Revocar consentimiento (GDPR)
3.4 Ejemplo: Envío de Evento Server-Side
POST /api/v1/tracking/events
{   "event_name": "Purchase",   "event_id": "uuid-unique-for-dedup",   "platforms": ["meta", "google"],   "user_data": {     "email": "sha256_hashed_email",     "phone": "sha256_hashed_phone",     "external_id": "customer_123"   },   "custom_data": {     "currency": "EUR",     "value": 99.00,     "content_ids": ["prod_001"],     "content_type": "product"   },   "event_source_url": "https://tenant.plataforma.com/checkout/success",   "fbc": "fb.1.1234567890.abcdef",   "fbp": "fb.1.1234567890.123456" }
4. Event Mapping Universal
Mapeo automático de eventos internos a nomenclatura específica de cada plataforma:
Evento Interno	Meta	Google	LinkedIn	TikTok
page_view	PageView	page_view	PageView	PageView
lead	Lead	generate_lead	Lead	SubmitForm
signup	CompleteRegistration	sign_up	SignUp	Registration
add_to_cart	AddToCart	add_to_cart	AddToCart	AddToCart
purchase	Purchase	purchase	Conversion	PlaceAnOrder
contact	Contact	contact	Contact	Contact
schedule	Schedule	book_appointment	BookAppointment	Schedule
view_content	ViewContent	view_item	ViewContent	ViewContent
 
5. Flujos ECA (Automatización)
5.1 ECA: Server-Side Event Dispatch
Trigger: tracking_event creado con server_side_enabled = true
1.	Verificar consent_record para visitor_id
2.	Si consent_marketing = false → Abortar envío a plataformas publicitarias
3.	Hashear user_data (email, phone) con SHA256 si no está hasheado
4.	Para cada plataforma activa en el evento:
•	Meta: Llamar Conversions API con event_id para deduplicación
•	Google: Llamar Measurement Protocol con client_id
•	LinkedIn: Llamar Conversions API con conversion_id
5.	Guardar server_response en tracking_event
6.	Si error → Encolar para retry (máx 3 intentos)
5.2 ECA: Consent Banner Injection
Trigger: Primera visita de usuario (no cookie de consent)
7.	Verificar si visitor_id existe en consent_record
8.	Si no existe → Mostrar banner de consentimiento
9.	Al aceptar/rechazar → POST /api/v1/tracking/consent
10.	Configurar cookies: jaraba_consent, jaraba_visitor_id
11.	Si consent_marketing = true → Inicializar píxeles client-side
5.3 ECA: Pixel Health Check Diario
Trigger: Cron diario 08:00 UTC
12.	Para cada tenant con píxeles activos:
13.	Verificar último evento enviado exitosamente
14.	Si > 48h sin eventos → Enviar test event
15.	Si test event falla → Notificar administrador vía email
16.	Generar reporte semanal de health status de píxeles
6. Integración con jaraba_analytics
El Retargeting Pixel Manager se integra como extensión de jaraba_analytics, compartiendo métricas y proporcionando datos para el dashboard unificado:
Métrica	Descripción
Events Tracked (24h)	Total de eventos enviados en últimas 24h
Server-Side Success Rate	% de eventos server-side exitosos
Consent Rate	% de visitantes con consentimiento marketing
Platform Status	Estado de conexión por plataforma
Dedup Rate	% de eventos deduplicados correctamente
 
7. Componente Frontend
7.1 Pixel Manager Dashboard
Interfaz React para configuración y monitoreo de píxeles:
•	Lista de píxeles con indicador de estado (verde/amarillo/rojo)
•	Formulario de configuración con validación de credenciales
•	Debug panel con últimos 50 eventos en tiempo real
•	Selector de dominios autorizados
•	Toggle server-side / client-side por píxel
7.2 Consent Banner Configurable
•	Diseño personalizable con colores del tenant
•	Textos multiidioma (ES/EN/PT)
•	Opciones granulares (analytics/marketing/funcional)
•	Link a política de privacidad
•	Remembrar preferencias en cookie
8. Seguridad y Privacidad
Aspecto	Implementación
Tokens de acceso	Encriptados con AES-256 en base de datos
User data (PII)	Hasheado con SHA256 antes de envío
IP addresses	Almacenadas solo 30 días para deduplicación
Consent records	Inmutables, con versionado de política
Multi-tenancy	Aislamiento completo de píxeles por tenant
GDPR/RGPD	Derecho al olvido implementado vía API
9. Roadmap de Implementación
Sprint	Entregables	Horas
Sprint 1	Entidades DB, API CRUD píxeles, configuración básica	4-5h
Sprint 2	Meta CAPI integration, deduplicación, event mapping	3-4h
Sprint 3	Google/LinkedIn/TikTok integration, consent management	2-3h
Sprint 4	Frontend dashboard, consent banner, debug panel, QA	1-3h
Total estimado: 10-15 horas
10. Dependencias y Prerequisitos
•	jaraba_core: Sistema base multi-tenant
•	jaraba_analytics: Módulo base para métricas
•	Cuentas Business Manager en Meta, Google Ads, LinkedIn Campaign Manager
•	Tokens de API para Conversions API (Meta), Measurement Protocol (Google)
•	SSL obligatorio en dominios de tenant (para cookies secure)
11. Beneficios vs. Soluciones Externas
Característica	GTM + Herramientas	Pixel Manager Nativo
Server-side tracking	Requiere GTM Server	✅ Incluido
Multi-tenant	❌ Complejo	✅ Nativo
Consent management	Herramienta adicional	✅ Integrado
Coste mensual	€50-200/mes	€0 (incluido)
Deduplicación	Manual	✅ Automática
Debug unificado	Por plataforma	✅ Centralizado


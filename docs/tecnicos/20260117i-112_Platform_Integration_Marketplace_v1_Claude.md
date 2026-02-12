
INTEGRATION MARKETPLACE & DEVELOPER PORTAL
Especificación Técnica para Implementación
JARABA IMPACT PLATFORM

Versión:	1.0
Fecha:	Enero 2026
Código:	112_Platform_Integration_Marketplace_v1
Estado:	Especificación para EDI
 
1. Resumen Ejecutivo
El Integration Marketplace permite a terceros construir sobre la plataforma Jaraba mediante un ecosistema de conectores, OAuth2 server, y Developer Portal completo. Implementa Model Context Protocol (MCP) para compatibilidad con agentes IA externos.
1.1 Objetivos del Sistema
Objetivo	Métrica Target	Benchmark
Conectores disponibles	50+ en Y1	iCIMS: 800+
Developer signups	100+ en 6 meses	Zapier: 2M+
API calls/mes	1M+	Industria: variable
Partner integrations	10+ activos	Greenhouse: 500+
Time to first integration	< 2 horas	Best: < 1 día
1.2 Componentes Principales
•	Connector Registry: Catálogo de conectores con instalación 1-click
•	OAuth2 Server: Autorización para apps de terceros
•	Developer Portal: Documentación interactiva con Swagger/OpenAPI
•	MCP Server: Model Context Protocol para agentes IA
•	Webhooks Engine: Eventos en tiempo real para integraciones
•	Partner Program: Gestión de partners certificados
 
2. Arquitectura del Sistema
2.1 Stack Tecnológico
Componente	Tecnología	Justificación
OAuth2 Server	Simple OAuth2 PHP + Drupal	Integración nativa
API Documentation	OpenAPI 3.0 + Swagger UI	Estándar industria
MCP Server	Node.js + @anthropic/mcp-sdk	Compatibilidad Claude
Webhooks	Redis + BullMQ	Delivery garantizado
Connector Runtime	Drupal Plugin API	Extensibilidad nativa
Developer Portal	Docusaurus + React	DX moderno
2.2 Diagrama de Arquitectura
┌─────────────────────────────────────────────────────────────────┐
│                    INTEGRATION MARKETPLACE                      │
├─────────────────────────────────────────────────────────────────┤
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────┐  │
│  │   OAuth2     │  │  Connector   │  │   Developer Portal   │  │
│  │   Server     │  │   Registry   │  │   (Docusaurus)       │  │
│  └──────┬───────┘  └──────┬───────┘  └──────────────────────┘  │
│         │                 │                                     │
│  ┌──────┴─────────────────┴──────────────────────────────────┐  │
│  │              Jaraba Core API (REST + GraphQL)             │  │
│  └──────────────────────────┬────────────────────────────────┘  │
│                             │                                   │
│  ┌──────────────┐  ┌────────┴───────┐  ┌────────────────────┐  │
│  │  MCP Server  │  │ Webhooks Engine│  │  Rate Limiter      │  │
│  │  (Agentes IA)│  │ (Redis+BullMQ) │  │  (Token Bucket)    │  │
│  └──────────────┘  └────────────────┘  └────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
 
3. Modelo de Datos
3.1 Entidad: connector
Representa un conector/integración disponible en el marketplace.
Campo	Tipo	Requerido	Descripción
id	UUID	Sí	Identificador único
name	VARCHAR(255)	Sí	Nombre del conector
slug	VARCHAR(100)	Sí	URL-friendly identifier
description	TEXT	Sí	Descripción detallada
category	ENUM	Sí	crm|erp|marketing|payments|hr|analytics
logo_url	VARCHAR(500)	Sí	URL del logo
auth_type	ENUM	Sí	oauth2|api_key|basic|none
auth_config	JSON	No	Config OAuth2/API key
endpoints	JSON	Sí	Array de endpoints disponibles
webhooks	JSON	No	Eventos que puede enviar/recibir
pricing_tier	ENUM	Sí	free|basic|pro|enterprise
status	ENUM	Sí	draft|published|deprecated
install_count	INT	No	Instalaciones totales
rating	DECIMAL(2,1)	No	Rating promedio 0-5
partner_id	UUID FK	No	Partner que lo desarrolló
created_at	TIMESTAMP	Sí	Fecha de creación
3.2 Entidad: connector_installation
Registro de instalaciones de conectores por tenant.
Campo	Tipo	Requerido	Descripción
id	UUID	Sí	Identificador único
connector_id	UUID FK	Sí	Referencia al conector
tenant_id	UUID FK	Sí	Tenant que lo instaló
status	ENUM	Sí	active|paused|error|uninstalled
credentials	JSON ENCRYPTED	No	Credenciales almacenadas
config	JSON	No	Configuración personalizada
last_sync_at	TIMESTAMP	No	Última sincronización
error_message	TEXT	No	Último error si aplica
installed_at	TIMESTAMP	Sí	Fecha instalación
3.3 Entidad: oauth_client
Aplicaciones de terceros registradas para acceder a la API.
Campo	Tipo	Requerido	Descripción
client_id	VARCHAR(80)	Sí	OAuth2 client ID
client_secret	VARCHAR(80)	Sí	OAuth2 client secret (hashed)
name	VARCHAR(255)	Sí	Nombre de la aplicación
description	TEXT	No	Descripción de la app
redirect_uris	JSON	Sí	URIs de callback permitidas
scopes	JSON	Sí	Scopes permitidos
grant_types	JSON	Sí	Tipos de grant permitidos
developer_id	UUID FK	Sí	Developer propietario
status	ENUM	Sí	pending|approved|suspended
rate_limit	INT	Sí	Requests/hora permitidos
created_at	TIMESTAMP	Sí	Fecha registro
3.4 Entidad: webhook_subscription
Suscripciones a eventos para notificaciones en tiempo real.
Campo	Tipo	Requerido	Descripción
id	UUID	Sí	Identificador único
client_id	VARCHAR(80) FK	Sí	OAuth client suscrito
tenant_id	UUID FK	Sí	Tenant del que recibe eventos
event_types	JSON	Sí	Array de eventos suscritos
target_url	VARCHAR(500)	Sí	URL de destino
secret	VARCHAR(100)	Sí	Secret para firmar payloads
status	ENUM	Sí	active|paused|failed
failure_count	INT	No	Fallos consecutivos
last_triggered_at	TIMESTAMP	No	Último envío
 
4. APIs REST
4.1 Connector Marketplace API
Método	Endpoint	Descripción
GET	/api/v1/connectors	Listar conectores (filtrable por category, status)
GET	/api/v1/connectors/{slug}	Detalle de conector
POST	/api/v1/connectors/{slug}/install	Instalar conector en tenant actual
DELETE	/api/v1/connectors/{slug}/uninstall	Desinstalar conector
GET	/api/v1/installations	Listar instalaciones del tenant
PUT	/api/v1/installations/{id}/config	Actualizar configuración
POST	/api/v1/installations/{id}/test	Probar conexión
4.2 OAuth2 Server Endpoints
Método	Endpoint	Descripción
GET	/oauth/authorize	Authorization endpoint
POST	/oauth/token	Token endpoint
POST	/oauth/revoke	Revocar token
GET	/oauth/userinfo	Info del usuario autenticado
GET	/.well-known/oauth-authorization-server	Discovery document
4.3 Webhook Management API
Método	Endpoint	Descripción
GET	/api/v1/webhooks	Listar suscripciones
POST	/api/v1/webhooks	Crear suscripción
PUT	/api/v1/webhooks/{id}	Actualizar suscripción
DELETE	/api/v1/webhooks/{id}	Eliminar suscripción
GET	/api/v1/webhooks/events	Listar eventos disponibles
POST	/api/v1/webhooks/{id}/test	Enviar evento de prueba
4.4 Developer Portal API
Método	Endpoint	Descripción
POST	/api/v1/developers/register	Registrar como developer
GET	/api/v1/apps	Listar mis aplicaciones
POST	/api/v1/apps	Crear nueva aplicación
PUT	/api/v1/apps/{id}	Actualizar aplicación
POST	/api/v1/apps/{id}/rotate-secret	Rotar client secret
GET	/api/v1/apps/{id}/analytics	Métricas de uso de la app
 
5. MCP Server (Model Context Protocol)
5.1 Propósito
El MCP Server permite que agentes IA externos (Claude, GPT, etc.) interactúen con Jaraba de forma nativa. Expone herramientas (tools) y recursos (resources) según el estándar MCP de Anthropic.
5.2 Tools Expuestos
Tool	Parámetros	Descripción
search_jobs	query, location, skills[]	Buscar ofertas de empleo
apply_to_job	job_id, cover_letter	Aplicar a una oferta
search_products	query, category, price_range	Buscar productos en marketplace
create_order	product_id, quantity, address	Crear pedido
get_business_diagnostic	business_id	Obtener diagnóstico de negocio
schedule_mentoring	mentor_id, datetime	Agendar sesión de mentoría
get_learning_path	user_id, goal	Obtener ruta de aprendizaje
5.3 Resources Expuestos
Resource URI	Descripción
jaraba://user/profile	Perfil del usuario autenticado
jaraba://jobs/recommended	Ofertas recomendadas para el usuario
jaraba://courses/enrolled	Cursos en los que está inscrito
jaraba://orders/recent	Pedidos recientes
jaraba://business/{id}/metrics	Métricas del negocio
 
6. Conectores Predefinidos (MVP)
6.1 Conectores Fase 1
Conector	Categoría	Funcionalidad	Prioridad
Google Sheets	Productivity	Sync productos/pedidos	CRÍTICA
Mailchimp	Marketing	Sync contactos, campañas	ALTA
WhatsApp Business	Messaging	Notificaciones pedidos	CRÍTICA
Holded/Facturae	Accounting	Facturación automática	ALTA
LinkedIn	HR	Publicar ofertas empleo	ALTA
Shopify	E-commerce	Sync inventario	MEDIA
Slack	Productivity	Alertas y notificaciones	MEDIA
Calendly	Scheduling	Agendar mentorías	MEDIA
 
7. Roadmap de Implementación
Sprint	Timeline	Entregables
Sprint 1	Semana 1-2	Entidades BD (connector, oauth_client). CRUD básico.
Sprint 2	Semana 3-4	OAuth2 Server completo. Authorization code + client credentials.
Sprint 3	Semana 5-6	Connector Registry UI. Instalación/desinstalación 1-click.
Sprint 4	Semana 7-8	Webhooks engine con Redis. Retry logic y dead letter queue.
Sprint 5	Semana 9-10	Developer Portal (Docusaurus). Documentación OpenAPI.
Sprint 6	Semana 11-12	MCP Server básico. 3 tools implementados.
Sprint 7	Semana 13-14	5 conectores MVP. Testing E2E. Go-live.
7.1 Estimación de Esfuerzo
Componente	Horas Estimadas
OAuth2 Server + API Gateway	80-100h
Connector Registry + UI	60-80h
Webhooks Engine	40-60h
Developer Portal	40-50h
MCP Server	60-80h
Conectores MVP (8)	80-120h
TOTAL	360-490h
--- Fin del Documento ---

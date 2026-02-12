API GATEWAY & DEVELOPER PORTAL
Gestión de APIs Públicas y Documentación

Campo	Valor
Versión:	1.0
Fecha:	Enero 2026
Código:	137_Platform_API_Gateway
URL:	https://developers.jarabaimpact.com
 
1. Arquitectura de API
1.1 Capas de la API
┌─────────────────────────────────────────────────────────────────────────────┐
│                        API ARCHITECTURE                                     │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  EXTERNAL CLIENTS                                                           │
│  ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌─────────┐                        │
│  │ Mobile  │  │  Web    │  │ Partner │  │  IoT    │                        │
│  │  Apps   │  │  Apps   │  │  Apps   │  │ Devices │                        │
│  └────┬────┘  └────┬────┘  └────┬────┘  └────┬────┘                        │
│       │            │            │            │                              │
│       └────────────┴────────────┴────────────┘                              │
│                         │                                                   │
│                         ▼                                                   │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │                    CLOUDFLARE (WAF + Rate Limit)                    │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                         │                                                   │
│                         ▼                                                   │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │                    API GATEWAY (Traefik)                            │   │
│  │  • SSL Termination                                                  │   │
│  │  • Request routing                                                  │   │
│  │  • Rate limiting (per API key)                                      │   │
│  │  • Request/Response transformation                                  │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                         │                                                   │
│       ┌─────────────────┼─────────────────┐                                │
│       │                 │                 │                                │
│       ▼                 ▼                 ▼                                │
│  ┌─────────┐      ┌─────────┐      ┌─────────┐                            │
│  │ Public  │      │ Partner │      │ Internal│                            │
│  │   API   │      │   API   │      │   API   │                            │
│  │  v1/    │      │  v1/    │      │ admin/  │                            │
│  └─────────┘      └─────────┘      └─────────┘                            │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
1.2 Niveles de API
Nivel	Autenticación	Rate Limit	Endpoints
Public	API Key	100 req/min	Productos, Búsqueda, Categorías
Partner	OAuth2 + API Key	1000 req/min	Pedidos, Inventario, Webhooks
Internal	JWT (tenant admin)	Sin límite	Todos los endpoints
Admin	JWT (platform admin)	Sin límite	Administración de plataforma
 
2. Autenticación
2.1 API Keys
# Obtener API Key
POST /api/v1/auth/api-keys
Authorization: Bearer {user_jwt}
 
{
  "name": "Mi App de Producción",
  "scopes": ["products:read", "orders:read", "orders:write"],
  "allowed_origins": ["https://miapp.com"],
  "rate_limit": 500  // requests per minute
}
 
# Response
{
  "api_key": "pk_live_xxxxxxxxxxxxxxxxxxxx",
  "secret_key": "sk_live_xxxxxxxxxxxxxxxxxxxx",  // Solo se muestra una vez
  "created_at": "2026-01-18T10:30:00Z",
  "scopes": ["products:read", "orders:read", "orders:write"]
}
2.2 OAuth2 para Partners
# 1. Redirect a autorización
GET /oauth/authorize?
  client_id={client_id}&
  redirect_uri={redirect_uri}&
  response_type=code&
  scope=products:read orders:write&
  state={random_state}
 
# 2. Exchange code for token
POST /oauth/token
Content-Type: application/x-www-form-urlencoded
 
grant_type=authorization_code&
code={authorization_code}&
client_id={client_id}&
client_secret={client_secret}&
redirect_uri={redirect_uri}
 
# Response
{
  "access_token": "eyJhbGciOiJSUzI1NiIs...",
  "token_type": "Bearer",
  "expires_in": 3600,
  "refresh_token": "rt_xxxxxxxxxxxx",
  "scope": "products:read orders:write"
}
3. Rate Limiting
Plan	Requests/min	Requests/día	Burst
Free	60	10,000	100
Starter	100	50,000	200
Growth	500	250,000	1,000
Pro	1,000	1,000,000	2,000
Enterprise	Custom	Custom	Custom
Headers de rate limit en cada respuesta:
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 87
X-RateLimit-Reset: 1705574400
X-RateLimit-Policy: 100;w=60
 
4. Developer Portal
4.1 Secciones del Portal
Sección	Contenido	URL
Docs	Documentación OpenAPI interactiva	/docs
API Reference	Referencia completa de endpoints	/reference
Guides	Tutoriales y guías de integración	/guides
SDKs	SDKs oficiales (JS, PHP, Python)	/sdks
Changelog	Historial de cambios de la API	/changelog
Status	Estado de los servicios	/status
Dashboard	Gestión de API keys y analytics	/dashboard
4.2 OpenAPI Spec
# openapi.yaml
openapi: 3.1.0
info:
  title: Jaraba Impact API
  version: 1.0.0
  description: API para integraciones con el ecosistema Jaraba
  contact:
    email: api@jarabaimpact.com
    url: https://developers.jarabaimpact.com
 
servers:
  - url: https://api.jarabaimpact.com/v1
    description: Production
  - url: https://api.staging.jarabaimpact.com/v1
    description: Staging
 
security:
  - ApiKeyAuth: []
  - OAuth2: []
 
paths:
  /products:
    get:
      summary: Listar productos
      tags: [Products]
      parameters:
        - name: tenant_id
          in: query
          required: true
          schema:
            type: string
        - name: limit
          in: query
          schema:
            type: integer
            default: 20
            maximum: 100
      responses:
        '200':
          description: Lista de productos
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/ProductList'
 
components:
  securitySchemes:
    ApiKeyAuth:
      type: apiKey
      in: header
      name: X-API-Key
    OAuth2:
      type: oauth2
      flows:
        authorizationCode:
          authorizationUrl: /oauth/authorize
          tokenUrl: /oauth/token
          scopes:
            products:read: Leer productos
            orders:write: Crear pedidos
5. Webhooks
Evento	Descripción	Payload
order.created	Nuevo pedido creado	{order_id, items, total, customer}
order.shipped	Pedido enviado	{order_id, tracking, carrier}
order.delivered	Pedido entregado	{order_id, delivered_at}
product.updated	Producto actualizado	{product_id, changes}
subscription.changed	Cambio en suscripción	{subscription_id, old_plan, new_plan}
6. Checklist
•	[ ] Configurar Traefik como API Gateway
•	[ ] Implementar sistema de API Keys
•	[ ] Implementar OAuth2 server
•	[ ] Configurar rate limiting por tier
•	[ ] Generar OpenAPI spec completa
•	[ ] Deploy developer portal (Docusaurus o similar)
•	[ ] Crear SDKs básicos (JS, PHP)
•	[ ] Sistema de webhooks con retry
•	[ ] Dashboard de analytics para developers

--- Fin del Documento ---

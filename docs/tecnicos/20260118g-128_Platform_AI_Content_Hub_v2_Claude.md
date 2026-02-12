
AI CONTENT HUB
Sistema de Blog, Newsletter y Contenido IA
Módulo del Core Platform

JARABA IMPACT PLATFORM
Documento Técnico de Implementación - Versión Completa

Campo	Valor	Notas
Versión:	2.0	Especificación completa para desarrollo
Fecha:	Enero 2026	
Estado:	Ready for Development	Sin Humo
Código:	128_Platform_AI_Content_Hub	
Dependencias:	01_Core, 06_ECA, 114_KB	Ver sección 11
 
1. Resumen Ejecutivo
Este documento especifica la arquitectura técnica completa del Sistema AI Content Hub para el Ecosistema Jaraba. Incluye todas las entidades, APIs, flujos ECA y configuraciones necesarias para implementación directa por el equipo EDI Google Antigravity.
1.1 Stack Tecnológico
Componente	Tecnología	Versión/Config
Core CMS	Drupal 11 + módulo jaraba_content_hub	^11.0
AI Generation	Claude API (Anthropic)	claude-sonnet-4-5-20250929
Vector Search	Qdrant Cloud	Namespace: content_hub_{tenant_id}
Embeddings	text-embedding-3-small	1536 dimensions
Newsletter	ActiveCampaign API v3	Integración existente
Editor	CKEditor 5 + React components	Custom plugins
Cache	Redis	TTL: 3600s para recomendaciones
1.2 Módulo Drupal: jaraba_content_hub
El módulo custom jaraba_content_hub.module expone:
•	6 entidades custom (content_article, newsletter_campaign, etc.)
•	12 endpoints REST API
•	8 flujos ECA automatizados
•	3 plugins CKEditor para asistencia IA
•	Integración con Group module para multi-tenancy
 
2. Arquitectura de Entidades
El módulo introduce 6 nuevas entidades Drupal que extienden el esquema base definido en 01_Core_Entidades.
2.1 Entidad: content_article
Representa un artículo de blog publicable con soporte completo para GEO.
Campo	Tipo	Descripción	Restricciones
id	SERIAL	ID interno autoincremental	PRIMARY KEY
uuid	UUID	Identificador único global	UNIQUE, NOT NULL, INDEX
tenant_id	INT	Grupo/tenant propietario	FK group.id, NOT NULL, INDEX
title	VARCHAR(255)	Título del artículo	NOT NULL
slug	VARCHAR(255)	URL amigable	UNIQUE per tenant, NOT NULL, INDEX
excerpt	VARCHAR(500)	Resumen para listados/meta	NOT NULL
body	TEXT	Contenido principal (HTML)	NOT NULL
answer_capsule	VARCHAR(200)	Primeros 150-200 chars para AI	NOT NULL
featured_image	INT	Imagen principal	FK file_managed.fid, NULLABLE
category_id	INT	Categoría principal	FK taxonomy_term.tid, NOT NULL
tags	JSON	Array de tag IDs	NULLABLE, array of tid
author_id	INT	Autor del artículo	FK users.uid, NOT NULL
reading_time	INT	Minutos de lectura	COMPUTED, > 0
seo_title	VARCHAR(70)	Título SEO	NULLABLE
seo_description	VARCHAR(160)	Meta description	NULLABLE
schema_json	JSON	Schema.org Article	AUTO-GENERATED
ai_generated	BOOLEAN	Indica generación IA	DEFAULT FALSE
ai_model_used	VARCHAR(64)	Modelo IA utilizado	NULLABLE
ai_prompt_hash	VARCHAR(64)	Hash del prompt para audit	NULLABLE
status	VARCHAR(16)	Estado del contenido	ENUM: draft|review|scheduled|published|archived
publish_date	DATETIME	Fecha publicación programada	NULLABLE, UTC
view_count	INT	Contador de visitas	DEFAULT 0
engagement_score	DECIMAL(5,4)	Score de engagement (0-1)	DEFAULT 0, COMPUTED
created	DATETIME	Fecha de creación	NOT NULL, UTC
changed	DATETIME	Última modificación	NOT NULL, UTC

Índices adicionales:
•	idx_article_tenant_status: (tenant_id, status) - Listados por tenant
•	idx_article_category: (category_id, status, publish_date) - Listados por categoría
•	idx_article_author: (author_id, status) - Artículos por autor
•	idx_article_engagement: (tenant_id, engagement_score DESC) - Rankings
 
2.2 Entidad: content_category
Categorías de contenido específicas por tenant.
Campo	Tipo	Descripción	Restricciones
id	SERIAL	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
tenant_id	INT	Tenant propietario	FK group.id, NOT NULL, INDEX
name	VARCHAR(64)	Nombre de categoría	NOT NULL
slug	VARCHAR(64)	URL amigable	UNIQUE per tenant, NOT NULL
description	VARCHAR(255)	Descripción para SEO	NULLABLE
parent_id	INT	Categoría padre	FK content_category.id, NULLABLE
weight	INT	Orden de display	DEFAULT 0
color	VARCHAR(7)	Color hex para UI	DEFAULT #F37021
icon	VARCHAR(64)	Icono (font-awesome class)	NULLABLE
is_active	BOOLEAN	Categoría activa	DEFAULT TRUE
article_count	INT	Contador de artículos	COMPUTED
2.3 Entidad: newsletter_campaign
Campañas de newsletter enviadas a suscriptores.
Campo	Tipo	Descripción	Restricciones
id	SERIAL	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
tenant_id	INT	Tenant propietario	FK group.id, NOT NULL, INDEX
campaign_type	VARCHAR(16)	Tipo de campaña	ENUM: digest|announcement|engagement|reengagement
subject	VARCHAR(100)	Asunto del email	NOT NULL
preheader	VARCHAR(150)	Texto preview	NULLABLE
template_id	INT	Template utilizado	FK newsletter_template.id, NOT NULL
content_blocks	JSON	Bloques de contenido	NOT NULL, structured JSON
article_ids	JSON	Artículos incluidos	NULLABLE, array of article.id
segment_id	INT	Segmento destinatario	FK subscriber_segment.id, NULLABLE
ac_campaign_id	VARCHAR(32)	ID en ActiveCampaign	NULLABLE, set after sync
ac_list_id	VARCHAR(32)	Lista en ActiveCampaign	NULLABLE
scheduled_at	DATETIME	Fecha/hora programada	NULLABLE, UTC
sent_at	DATETIME	Fecha/hora de envío	NULLABLE, UTC
status	VARCHAR(16)	Estado	ENUM: draft|scheduled|sending|sent|failed|cancelled
stats_sent	INT	Emails enviados	DEFAULT 0
stats_delivered	INT	Emails entregados	DEFAULT 0
stats_opened	INT	Aperturas únicas	DEFAULT 0
stats_clicked	INT	Clicks únicos	DEFAULT 0
stats_unsubscribed	INT	Bajas	DEFAULT 0
stats_bounced	INT	Rebotes	DEFAULT 0
created	DATETIME	Fecha creación	NOT NULL, UTC
changed	DATETIME	Última modificación	NOT NULL, UTC
 
2.4 Entidad: newsletter_subscriber
Suscriptores de newsletter por tenant.
Campo	Tipo	Descripción	Restricciones
id	SERIAL	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
tenant_id	INT	Tenant del suscriptor	FK group.id, NOT NULL, INDEX
email	VARCHAR(255)	Email del suscriptor	NOT NULL, UNIQUE per tenant
first_name	VARCHAR(64)	Nombre	NULLABLE
last_name	VARCHAR(64)	Apellido	NULLABLE
user_id	INT	Usuario vinculado	FK users.uid, NULLABLE
ac_contact_id	VARCHAR(32)	ID en ActiveCampaign	NULLABLE
source	VARCHAR(32)	Fuente de captación	ENUM: website|import|api|diagnostic|registration
status	VARCHAR(16)	Estado	ENUM: pending|confirmed|unsubscribed|bounced|complained
confirmed_at	DATETIME	Fecha confirmación	NULLABLE, UTC
unsubscribed_at	DATETIME	Fecha baja	NULLABLE, UTC
interests	JSON	Intereses/categorías	NULLABLE, array of category_id
engagement_score	DECIMAL(5,4)	Score engagement (0-1)	DEFAULT 0.5
last_email_opened	DATETIME	Última apertura	NULLABLE, UTC
last_email_clicked	DATETIME	Último click	NULLABLE, UTC
total_emails_sent	INT	Emails enviados	DEFAULT 0
total_opens	INT	Total aperturas	DEFAULT 0
total_clicks	INT	Total clicks	DEFAULT 0
created	DATETIME	Fecha suscripción	NOT NULL, UTC
2.5 Entidad: content_recommendation
Recomendaciones de contenido generadas por el motor IA.
Campo	Tipo	Descripción	Restricciones
id	SERIAL	ID interno	PRIMARY KEY
source_article_id	INT	Artículo origen	FK content_article.id, NOT NULL, INDEX
target_article_id	INT	Artículo recomendado	FK content_article.id, NOT NULL
recommendation_type	VARCHAR(16)	Tipo de recomendación	ENUM: semantic|collaborative|trending|editorial
score	DECIMAL(5,4)	Puntuación relevancia	NOT NULL, RANGE 0-1
position	INT	Posición en lista	NOT NULL, 1-10
generated_at	DATETIME	Momento generación	NOT NULL, UTC
expires_at	DATETIME	Expiración	NOT NULL, UTC
impressions	INT	Veces mostrado	DEFAULT 0
clicks	INT	Clicks recibidos	DEFAULT 0
Índice compuesto: idx_rec_source_type: (source_article_id, recommendation_type, score DESC)
 
2.6 Entidad: ai_generation_log
Log de todas las generaciones de IA para auditoría y mejora continua.
Campo	Tipo	Descripción	Restricciones
id	SERIAL	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
tenant_id	INT	Tenant	FK group.id, NOT NULL, INDEX
user_id	INT	Usuario que solicitó	FK users.uid, NOT NULL
generation_type	VARCHAR(32)	Tipo de generación	ENUM: outline|full_article|section|headline|summary|translate
model_used	VARCHAR(64)	Modelo IA	NOT NULL
prompt_template	VARCHAR(64)	Template usado	NOT NULL
prompt_variables	JSON	Variables del prompt	NOT NULL
input_tokens	INT	Tokens de entrada	NOT NULL
output_tokens	INT	Tokens de salida	NOT NULL
latency_ms	INT	Latencia en ms	NOT NULL
status	VARCHAR(16)	Estado	ENUM: success|error|timeout|rate_limited
error_message	TEXT	Mensaje de error	NULLABLE
article_id	INT	Artículo resultante	FK content_article.id, NULLABLE
user_rating	INT	Rating del usuario	NULLABLE, RANGE 1-5
user_feedback	TEXT	Feedback textual	NULLABLE
created	DATETIME	Timestamp	NOT NULL, UTC
 
3. APIs REST
El módulo jaraba_content_hub expone los siguientes endpoints RESTful. Todos requieren autenticación OAuth2 y respetan el contexto multi-tenant via header X-Tenant-ID.
3.1 Endpoints de Artículos
Método	Endpoint	Descripción	Permisos
GET	/api/v1/content/articles	Listar artículos (paginado)	view content
GET	/api/v1/content/articles/{uuid}	Detalle de artículo	view content
POST	/api/v1/content/articles	Crear artículo	create content
PATCH	/api/v1/content/articles/{uuid}	Actualizar artículo	edit own/any content
DELETE	/api/v1/content/articles/{uuid}	Eliminar artículo	delete content
POST	/api/v1/content/articles/{uuid}/publish	Publicar artículo	publish content
GET	/api/v1/content/articles/{uuid}/recommendations	Recomendaciones	view content
3.1.1 GET /api/v1/content/articles
Lista artículos con filtros y paginación.
Query Parameters:
•	status: draft|review|scheduled|published|archived (default: published)
•	category: UUID de categoría
•	author: UUID de autor
•	tag: UUID de tag
•	search: texto de búsqueda (título, excerpt)
•	sort: publish_date|created|engagement_score|view_count (default: publish_date)
•	order: asc|desc (default: desc)
•	page: número de página (default: 1)
•	per_page: items por página (default: 10, max: 50)
Response 200:
{
  "data": [
    {
      "uuid": "550e8400-e29b-41d4-a716-446655440000",
      "title": "Cómo preparar tu CV para el sector tech",
      "slug": "como-preparar-cv-sector-tech",
      "excerpt": "Guía completa para destacar...",
      "answer_capsule": "Un CV tech efectivo...",
      "featured_image": { "url": "...", "alt": "..." },
      "category": { "uuid": "...", "name": "Empleabilidad" },
      "author": { "uuid": "...", "name": "Pepe Jaraba" },
      "reading_time": 8,
      "publish_date": "2026-01-15T10:00:00Z",
      "view_count": 1234,
      "engagement_score": 0.7823
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 10,
    "total": 156,
    "total_pages": 16
  }
}
 
3.2 Endpoints de Generación IA
Método	Endpoint	Descripción	Rate Limit
POST	/api/v1/content/generate/outline	Generar outline	10/min/user
POST	/api/v1/content/generate/article	Generar artículo completo	5/min/user
POST	/api/v1/content/generate/section	Expandir sección	20/min/user
POST	/api/v1/content/generate/headline	Generar titulares	30/min/user
POST	/api/v1/content/generate/summary	Generar resumen/capsule	20/min/user
POST	/api/v1/content/generate/translate	Traducir contenido	10/min/user
POST	/api/v1/content/analyze/seo	Análisis SEO	20/min/user
3.2.1 POST /api/v1/content/generate/article
Genera un artículo completo basado en tema y parámetros.
Request Body:
{
  "topic": "Cómo preparar tu CV para el sector tech",
  "keywords": ["CV tech", "curriculum vitae", "empleo IT"],
  "content_type": "guide",  // guide|tutorial|listicle|comparison|news
  "target_audience": "junior_developers",
  "word_count": 1500,
  "tone": "professional_friendly",  // professional|casual|academic|inspirational
  "include_sections": ["introduction", "main_points", "conclusion", "faq"],
  "outline": {  // Opcional: outline pre-aprobado
    "h2_sections": [
      { "title": "Por qué importa tu CV", "key_points": ["..."] },
      { "title": "Estructura recomendada", "key_points": ["..."] }
    ]
  },
  "sources": [  // Opcional: fuentes a citar
    { "url": "https://...", "title": "...", "excerpt": "..." }
  ]
}
Response 200:
{
  "status": "success",
  "generation_id": "gen_abc123",
  "article": {
    "title": "Cómo preparar tu CV para el sector tech en 2026",
    "slug": "como-preparar-cv-sector-tech-2026",
    "excerpt": "Descubre las claves para...",
    "answer_capsule": "Un CV tech efectivo debe...",
    "body": "<h2>Por qué importa tu CV</h2><p>...</p>...",
    "seo_title": "CV Tech: Guía Completa 2026 | Jaraba",
    "seo_description": "Aprende a crear un CV..."
  },
  "metadata": {
    "model": "claude-sonnet-4-5-20250929",
    "input_tokens": 1250,
    "output_tokens": 2840,
    "latency_ms": 4521
  }
}
 
3.3 Endpoints de Newsletter
Método	Endpoint	Descripción	Permisos
GET	/api/v1/newsletter/campaigns	Listar campañas	view newsletter
POST	/api/v1/newsletter/campaigns	Crear campaña	create newsletter
GET	/api/v1/newsletter/campaigns/{uuid}	Detalle campaña	view newsletter
PATCH	/api/v1/newsletter/campaigns/{uuid}	Actualizar campaña	edit newsletter
POST	/api/v1/newsletter/campaigns/{uuid}/schedule	Programar envío	send newsletter
POST	/api/v1/newsletter/campaigns/{uuid}/send	Enviar ahora	send newsletter
POST	/api/v1/newsletter/campaigns/{uuid}/cancel	Cancelar programada	edit newsletter
GET	/api/v1/newsletter/subscribers	Listar suscriptores	view subscribers
POST	/api/v1/newsletter/subscribers	Añadir suscriptor	manage subscribers
DELETE	/api/v1/newsletter/subscribers/{uuid}	Eliminar suscriptor	manage subscribers
POST	/api/v1/newsletter/subscribe	Suscripción pública	anonymous
3.3.1 POST /api/v1/newsletter/campaigns
Crea una nueva campaña de newsletter.
Request Body:
{
  "campaign_type": "digest",
  "subject": "Lo mejor de la semana | Jaraba",
  "preheader": "Descubre los 5 artículos más leídos...",
  "template_id": "tpl_weekly_digest",
  "segment_id": "seg_active_readers",  // Opcional
  "content_blocks": [
    {
      "type": "hero",
      "article_uuid": "...",  // Artículo destacado
      "custom_headline": null  // Usar título original
    },
    {
      "type": "article_list",
      "article_uuids": ["...", "...", "..."],
      "max_items": 4
    },
    {
      "type": "cta",
      "text": "Ver todos los artículos",
      "url": "/blog"
    }
  ],
  "scheduled_at": "2026-01-20T09:00:00Z"  // Opcional
}
 
4. Flujos de Automatización (ECA)
Los siguientes flujos ECA automatizan el ciclo de vida del contenido y newsletters.
4.1 ECA-CH-001: Auto-Generate Answer Capsule
Trigger: Artículo guardado con status = review AND answer_capsule IS EMPTY

1.	Verificar que body tiene contenido (length > 500 caracteres)
2.	Extraer primeros 3 párrafos del body
3.	Llamar a /api/v1/content/generate/summary con extracted_text
4.	Actualizar article.answer_capsule con respuesta
5.	Actualizar article.schema_json con Article schema regenerado
6.	Log en ai_generation_log con type = 'summary'
4.2 ECA-CH-002: SEO Quality Gate
Trigger: Artículo actualizado a status = review

7.	Llamar a /api/v1/content/analyze/seo con article_uuid
8.	Si seo_score < 70: añadir flag 'needs_seo_improvement'
9.	Si seo_score < 70: enviar notificación al autor con sugerencias
10.	Si seo_score >= 70: remover flag si existe
11.	Actualizar campo article.seo_score con resultado
12.	Si status = review AND seo_score >= 70 AND has_featured_image: auto-approve para editor
4.3 ECA-CH-003: Publish Article
Trigger: Artículo actualizado a status = published

13.	Establecer publish_date = NOW() si es NULL
14.	Invalidar cache de listados del tenant
15.	Generar/actualizar entry en sitemap.xml
16.	Indexar en Qdrant: namespace content_hub_{tenant_id}
17.	Regenerar recomendaciones para artículos relacionados
18.	Si flag 'notify_subscribers' = TRUE: disparar ECA-CH-006
19.	Webhook a ActiveCampaign: tag 'read_article_{category_slug}'
20.	Si tiene social_share habilitado: queue para publicación social
4.4 ECA-CH-004: Weekly Digest Generator
Trigger: Cron cada Lunes 07:00 UTC

21.	Para cada tenant con newsletter habilitado:
22.	  Query artículos: status=published, publish_date > 7 days ago, ORDER BY engagement_score DESC
23.	  Si count < 3: skip tenant, log 'insufficient_content'
24.	  Seleccionar top 5 artículos por engagement
25.	  Crear newsletter_campaign con template 'weekly_digest'
26.	  Poblar content_blocks con artículos seleccionados
27.	  Establecer scheduled_at = Lunes 09:00 local del tenant
28.	  Actualizar status = 'scheduled'
29.	  Sync con ActiveCampaign: crear campaign draft
 
4.5 ECA-CH-005: Newsletter Send
Trigger: newsletter_campaign.scheduled_at <= NOW() AND status = scheduled

30.	Actualizar status = 'sending'
31.	Obtener subscriber list del segment (o todos si segment_id IS NULL)
32.	Filtrar: status = 'confirmed' AND engagement_score > 0.1
33.	Para cada batch de 100 subscribers:
34.	  Llamar ActiveCampaign API: POST /campaigns/{ac_campaign_id}/send
35.	  Actualizar stats_sent += batch_size
36.	Si todos los batches OK: status = 'sent', sent_at = NOW()
37.	Si error: status = 'failed', log error_message
38.	Programar job para sync de stats en +24h
4.6 ECA-CH-006: New Article Notification
Trigger: Flag 'notify_subscribers' en artículo publicado

39.	Crear newsletter_campaign con template 'new_article'
40.	Establecer segment basado en article.category (subscribers con interés en esa categoría)
41.	Si no hay segment específico: usar segment 'all_active'
42.	Generar subject con IA: 'Nuevo: {title}' + variantes para A/B
43.	Establecer scheduled_at = NOW() + 30 minutes
44.	Disparar ECA-CH-005 cuando llegue el momento
4.7 ECA-CH-007: Recommendation Refresh
Trigger: Cron cada 6 horas

45.	Query artículos con recommendations expiradas o sin recommendations
46.	Para cada artículo (batch de 50):
47.	  Obtener embedding del artículo desde Qdrant
48.	  Buscar top 10 similares (cosine similarity > 0.7)
49.	  Excluir mismo artículo y artículos del mismo autor
50.	  Crear/actualizar content_recommendation con type='semantic'
51.	  Añadir top 3 trending (últimas 24h) con type='trending'
52.	  Establecer expires_at = NOW() + 6 hours
53.	Invalidar cache de widgets de recomendaciones
4.8 ECA-CH-008: Subscriber Engagement Update
Trigger: Webhook de ActiveCampaign (open/click event)

54.	Parsear webhook payload: contact_id, event_type, campaign_id
55.	Buscar subscriber por ac_contact_id
56.	Si event_type = 'open': actualizar last_email_opened, total_opens++
57.	Si event_type = 'click': actualizar last_email_clicked, total_clicks++
58.	Recalcular engagement_score: (opens*0.3 + clicks*0.7) / emails_sent
59.	Si engagement_score < 0.1 por 90 días: marcar para re-engagement campaign
 
5. Integración Claude API
Configuración específica para la integración con Anthropic Claude API.
5.1 Configuración del Servicio
# config/services.yml
parameters:
  jaraba_content_hub.claude_api_key: "%env(CLAUDE_API_KEY)%"
  jaraba_content_hub.claude_model: "claude-sonnet-4-5-20250929"
  jaraba_content_hub.claude_max_tokens: 4096
  jaraba_content_hub.claude_temperature: 0.7
  jaraba_content_hub.claude_timeout: 30

services:
  jaraba_content_hub.claude_client:
    class: Drupal\jaraba_content_hub\Service\ClaudeApiClient
    arguments:
      - "@http_client"
      - "@logger.factory"
      - "%jaraba_content_hub.claude_api_key%"
      - "%jaraba_content_hub.claude_model%"
5.2 Rate Limiting
Operación	Límite Usuario	Límite Tenant/día
generate/outline	10/minuto	500
generate/article	5/minuto	100
generate/section	20/minuto	1000
generate/headline	30/minuto	2000
generate/summary	20/minuto	1000
generate/translate	10/minuto	200
analyze/seo	20/minuto	500
5.3 System Prompt Base
SYSTEM_PROMPT_BASE = """
Eres un experto redactor de contenido digital para {tenant_industry}.

REGLAS OBLIGATORIAS:
1. Responde SOLO en español (España) salvo que se indique otro idioma
2. Usa el tono: {brand_voice}
3. Los primeros 150 caracteres DEBEN responder directamente a la intención
4. NUNCA inventes datos, estadísticas o citas
5. Si necesitas datos específicos, indica [DATO REQUERIDO: descripción]
6. Evita estos términos: {taboo_terms}
7. Estructura con H2 y H3, máximo 300 palabras por sección
8. Incluye al menos una lista con viñetas por cada 500 palabras

CONTEXTO DEL TENANT:
- Nombre: {tenant_name}
- Industria: {tenant_industry}
- Audiencia objetivo: {target_audience}
- Propuesta de valor: {value_proposition}

ARTÍCULOS RELACIONADOS (para coherencia):
{related_articles_summary}
"""
5.4 Error Handling
Error Code	Causa	Acción	Retry
rate_limit_exceeded	Límite de API alcanzado	Queue para retry, notificar user	Sí, exponential backoff
context_length_exceeded	Input muy largo	Truncar input, regenerar	Sí, con input reducido
content_policy	Contenido rechazado	Log, notificar admin	No
timeout	API no responde en 30s	Retry con timeout extendido	Sí, max 2 intentos
invalid_response	Respuesta malformada	Log, retry	Sí, max 1 intento
api_error	Error 5xx de Anthropic	Queue para retry	Sí, después de 60s
 
6. Integración Qdrant (Vector Search)
6.1 Configuración de Colección
# Crear colección para Content Hub
PUT /collections/content_hub
{
  "vectors": {
    "size": 1536,
    "distance": "Cosine"
  },
  "optimizers_config": {
    "indexing_threshold": 10000
  },
  "on_disk_payload": true
}
6.2 Schema de Payload
{
  "article_uuid": "550e8400-...",
  "tenant_id": 123,
  "title": "Cómo preparar tu CV...",
  "category_id": 45,
  "category_slug": "empleabilidad",
  "author_id": 67,
  "publish_date": "2026-01-15T10:00:00Z",
  "status": "published",
  "engagement_score": 0.78,
  "tags": [12, 34, 56],
  "word_count": 1500,
  "chunk_index": 0,  // Si el artículo se divide en chunks
  "chunk_text": "..."  // Texto del chunk para display
}
6.3 Operaciones
Operación	Endpoint Qdrant	Trigger	Filtros
Index article	PUT /points	ECA-CH-003 (publish)	N/A
Search similar	POST /points/search	API recommendations	tenant_id, status=published
Delete article	DELETE /points/{uuid}	Article archived/deleted	N/A
Bulk reindex	PUT /points/batch	Manual/migration	tenant_id
Update score	PATCH /points/{uuid}	Engagement update	N/A
 
7. Permisos RBAC
7.1 Permisos del Módulo
Permiso	Machine Name	Roles Default	Descripción
Ver contenido	view content_article	authenticated	Ver artículos publicados
Ver borradores	view own unpublished content	author, editor, admin	Ver sus propios borradores
Crear contenido	create content_article	author, editor, admin	Crear nuevos artículos
Editar propio	edit own content_article	author, editor, admin	Editar sus artículos
Editar cualquiera	edit any content_article	editor, admin	Editar cualquier artículo
Eliminar propio	delete own content_article	editor, admin	Eliminar sus artículos
Eliminar cualquiera	delete any content_article	admin	Eliminar cualquier artículo
Publicar	publish content_article	editor, admin	Cambiar a status published
Usar IA	use content_ai	author, editor, admin	Acceso a generación IA
Admin IA	administer content_ai	admin	Configurar prompts, ver logs
Ver newsletter	view newsletter_campaign	editor, admin	Ver campañas
Crear newsletter	create newsletter_campaign	editor, admin	Crear campañas
Enviar newsletter	send newsletter_campaign	admin	Ejecutar envíos
Gestionar subs	manage newsletter_subscriber	admin	CRUD de suscriptores
7.2 Matriz de Roles
Acción	Contributor	Author	Editor	Admin
Ver publicados	✓	✓	✓	✓
Ver borradores propios	✓	✓	✓	✓
Ver todos los borradores	✗	✗	✓	✓
Crear artículo	✓	✓	✓	✓
Editar propio	✓	✓	✓	✓
Editar cualquiera	✗	✗	✓	✓
Publicar propio	✗	✓	✓	✓
Publicar cualquiera	✗	✗	✓	✓
Eliminar	✗	✗	✗	✓
Usar IA generación	✗	✓	✓	✓
Crear newsletter	✗	✗	✓	✓
Enviar newsletter	✗	✗	✗	✓
Configurar módulo	✗	✗	✗	✓
 
8. Configuración Multi-Tenant
8.1 Entidad: tenant_content_settings
Configuración específica de Content Hub por tenant.
Campo	Tipo	Descripción	Default
tenant_id	INT	FK group.id	NOT NULL, UNIQUE
blog_enabled	BOOLEAN	Blog habilitado	TRUE
blog_url_prefix	VARCHAR(32)	Prefijo URL (/blog, /noticias)	blog
newsletter_enabled	BOOLEAN	Newsletter habilitado	TRUE
newsletter_from_name	VARCHAR(64)	Nombre remitente	{tenant_name}
newsletter_from_email	VARCHAR(255)	Email remitente	noreply@{domain}
newsletter_reply_to	VARCHAR(255)	Reply-to email	NULL
ai_enabled	BOOLEAN	Generación IA habilitada	TRUE
ai_monthly_limit	INT	Límite generaciones/mes	1000
ai_brand_voice	TEXT	Descripción voz de marca	profesional y cercano
ai_taboo_terms	JSON	Términos prohibidos	[]
ai_custom_prompt	TEXT	Prompt adicional custom	NULL
default_category_id	INT	Categoría por defecto	NULL
social_share_enabled	BOOLEAN	Auto-share en RRSS	FALSE
comments_enabled	BOOLEAN	Comentarios habilitados	FALSE
analytics_enabled	BOOLEAN	Tracking de analytics	TRUE
8.2 Templates de Newsletter por Tenant
Cada tenant puede tener templates personalizados heredando del base.
•	weekly_digest: Resumen semanal de artículos
•	new_article: Notificación de nuevo artículo
•	engagement: Re-engagement para inactivos
•	welcome_series_1, welcome_series_2, welcome_series_3: Secuencia de bienvenida
•	custom_{name}: Templates personalizados por tenant
 
9. Schema.org y Optimización GEO
9.1 Article Schema Auto-generado
{
  "@context": "https://schema.org",
  "@type": "Article",
  "headline": "{title}",
  "description": "{excerpt}",
  "image": "{featured_image_url}",
  "author": {
    "@type": "Person",
    "name": "{author_name}",
    "url": "{author_profile_url}"
  },
  "publisher": {
    "@type": "Organization",
    "name": "{tenant_name}",
    "logo": { "@type": "ImageObject", "url": "{tenant_logo_url}" }
  },
  "datePublished": "{publish_date}",
  "dateModified": "{changed}",
  "mainEntityOfPage": { "@type": "WebPage", "@id": "{canonical_url}" },
  "wordCount": {word_count},
  "timeRequired": "PT{reading_time}M",
  "articleSection": "{category_name}",
  "keywords": "{tags_comma_separated}"
}
9.2 /llms.txt Dinámico
Endpoint: GET /{tenant_slug}/llms.txt
Generado dinámicamente con:
•	Descripción del tenant y su blog
•	Categorías disponibles con conteo de artículos
•	Top 10 artículos por engagement
•	Fecha de última actualización
 
10. Roadmap de Implementación
10.1 Sprint 1: Core Entities (Semanas 1-2)
Horas estimadas: 40-50h
•	Crear módulo jaraba_content_hub.module con info y dependencies
•	Implementar entidad content_article con todos los campos
•	Implementar entidad content_category
•	Crear migrations desde config existente si aplica
•	Admin UI básico para CRUD de artículos y categorías
•	Permisos base del módulo
Dependencias: 01_Core_Entidades instalado
Entregable: Artículos creables desde admin, listables en Views
10.2 Sprint 2: APIs y Frontend (Semanas 3-4)
Horas estimadas: 50-60h
•	REST API: endpoints GET/POST/PATCH para artículos
•	REST API: endpoints de categorías y tags
•	Templates Twig: article--full.html.twig, article--teaser.html.twig
•	Views: blog homepage, category listing, author listing
•	Breadcrumbs y navegación
•	SEO básico: metatags module integration, sitemap
Dependencias: Sprint 1 completado
Entregable: Blog funcional visible públicamente
10.3 Sprint 3: Integración IA (Semanas 5-6)
Horas estimadas: 60-70h
•	Servicio ClaudeApiClient con manejo de errores
•	Endpoints de generación: outline, article, section, headline
•	UI de generación en editor (React component o CKEditor plugin)
•	System prompts configurables por tenant
•	Entidad ai_generation_log para auditoría
•	Rate limiting implementation
•	Answer Capsule auto-generation (ECA-CH-001)
Dependencias: Sprint 2, Claude API key configurada
Entregable: Generación IA funcional desde editor
10.4 Sprint 4: Newsletter Core (Semanas 7-8)
Horas estimadas: 50-60h
•	Entidades: newsletter_campaign, newsletter_subscriber, newsletter_template
•	Integración ActiveCampaign: sync bidireccional
•	APIs de newsletter y suscriptores
•	Templates base de email (MJML → HTML)
•	Editor de bloques para campaigns
•	Formulario de suscripción público con double opt-in
Dependencias: Sprint 2, ActiveCampaign API configurada
Entregable: Newsletter enviable manualmente
10.5 Sprint 5: Automatizaciones (Semanas 9-10)
Horas estimadas: 40-50h
•	ECA-CH-002: SEO Quality Gate
•	ECA-CH-003: Publish Article con indexación
•	ECA-CH-004: Weekly Digest Generator
•	ECA-CH-005: Newsletter Send
•	ECA-CH-006: New Article Notification
•	Webhook handlers para ActiveCampaign events
Dependencias: Sprint 3, Sprint 4, ECA module
Entregable: Newsletter semanal automático funcionando
10.6 Sprint 6: Recommendations y Analytics (Semanas 11-12)
Horas estimadas: 50-60h
•	Integración Qdrant: indexación de artículos
•	Entidad content_recommendation
•	ECA-CH-007: Recommendation Refresh
•	Widgets de contenido relacionado
•	Dashboard de analytics de contenido
•	ECA-CH-008: Subscriber Engagement Update
•	QA completo y bug fixing
Dependencias: Sprint 5, Qdrant configurado
Entregable: Sistema completo listo para producción
10.7 Resumen de Inversión
Sprint	Semanas	Horas	Costo (€80/h)
Sprint 1: Core Entities	1-2	40-50h	€3,200-4,000
Sprint 2: APIs y Frontend	3-4	50-60h	€4,000-4,800
Sprint 3: Integración IA	5-6	60-70h	€4,800-5,600
Sprint 4: Newsletter Core	7-8	50-60h	€4,000-4,800
Sprint 5: Automatizaciones	9-10	40-50h	€3,200-4,000
Sprint 6: Recommendations	11-12	50-60h	€4,000-4,800
TOTAL	12 semanas	290-350h	€23,200-28,000
 
11. Dependencias del Módulo
11.1 Módulos Drupal Requeridos
Módulo	Versión	Propósito
drupal/group	^3.0	Multi-tenancy, aislamiento de contenido
drupal/eca	^2.0	Automatizaciones y workflows
drupal/metatag	^2.0	SEO meta tags
drupal/pathauto	^1.12	URLs amigables automáticas
drupal/simple_sitemap	^4.0	Generación de sitemap.xml
drupal/restui	^1.21	Admin UI para REST APIs
drupal/token	^1.13	Tokens para pathauto y metatag
drupal/schema_metatag	^3.0	Schema.org JSON-LD
11.2 Servicios Externos
Servicio	Configuración	Variables de Entorno
Claude API (Anthropic)	API Key, Model ID	CLAUDE_API_KEY, CLAUDE_MODEL
Qdrant Cloud	URL, API Key, Collection	QDRANT_URL, QDRANT_API_KEY
ActiveCampaign	Account, API Key	AC_ACCOUNT, AC_API_KEY
OpenAI Embeddings	API Key (para embeddings)	OPENAI_API_KEY
11.3 Documentos del Ecosistema Relacionados
•	01_Core_Entidades_Esquema_BD: Base de entidades
•	06_Core_Flujos_ECA: Patrones de automatización
•	07_Core_Configuracion_MultiTenant: Aislamiento por tenant
•	114_Platform_Knowledge_Base: Integración Qdrant existente
•	108_Platform_AI_Agent_Flows: Patrones de agentes IA

--- Fin del Documento ---

Jaraba Impact Platform | 128_AI_Content_Hub_v2 | Enero 2026

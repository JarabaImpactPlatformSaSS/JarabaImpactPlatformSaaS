JARABA_SOCIAL
Social Media Automation
Calendario Editorial y Publicación Multi-Plataforma con IA
Versión:	1.0
Fecha:	Enero 2026
Código:	152_Marketing_jaraba_social_v1
Estado:	Especificación Técnica para Implementación
Horas Estimadas:	50-70 horas
Dependencias:	jaraba_core, jaraba_content_hub, Make.com, Meta API, LinkedIn API
1. Resumen Ejecutivo
El módulo jaraba_social proporciona un sistema de gestión de redes sociales con calendario editorial, publicación automática multi-plataforma, generación de variantes de contenido con IA y analytics consolidados. Diseñado para reemplazar Buffer, Hootsuite o Later, ofreciendo integración nativa con jaraba_content_hub y ahorro de €300-1,200/año por tenant.
1.1 Capacidades Principales
•	Calendario editorial visual con drag & drop
•	Publicación automática a LinkedIn, Instagram, Facebook, X (Twitter)
•	Generación de variantes IA optimizadas por plataforma
•	Scheduling inteligente (best time to post)
•	Librería de contenido con reutilización programada
•	Analytics consolidados cross-platform
•	Integración Make.com para flujos avanzados
1.2 Plataformas Soportadas
Plataforma	API	Formatos	Límites
LinkedIn	Marketing API v2	Text, Image, Video, Document	3,000 char
Instagram	Graph API via Meta	Image, Carousel, Reels	2,200 char
Facebook	Graph API	Text, Image, Video, Link	63,206 char
X (Twitter)	API v2	Text, Image, Video	280 char
 
2. Arquitectura Técnica
2.1 Stack Tecnológico
Componente	Tecnología
Backend	Drupal 11 Custom Entities
Calendario UI	FullCalendar.js + React
Publicación	Make.com (escenarios pre-configurados)
IA Content	Claude API para variantes
Media Storage	S3-compatible con CDN
Queue	Redis Queue para scheduling
Analytics	Webhooks + polling APIs sociales
3. Esquema de Base de Datos
3.1 Entidad: social_account
Cuentas de redes sociales conectadas por tenant.
Campo	Tipo	Descripción	Restricciones
id	SERIAL	ID interno	PRIMARY KEY
uuid	VARCHAR(36)	UUID público	UNIQUE, NOT NULL
tenant_id	INT	Tenant propietario	FK tenants.id, NOT NULL, INDEX
platform	VARCHAR(32)	Plataforma social	ENUM: linkedin|instagram|facebook|twitter
account_type	VARCHAR(32)	Tipo de cuenta	ENUM: personal|business|creator
account_name	VARCHAR(255)	Nombre de cuenta	NOT NULL
account_id	VARCHAR(255)	ID en la plataforma	NOT NULL
profile_url	VARCHAR(500)	URL del perfil	NULLABLE
profile_image	VARCHAR(500)	Avatar URL	NULLABLE
access_token	TEXT	Token OAuth (encriptado)	NOT NULL, ENCRYPTED
refresh_token	TEXT	Refresh token	NULLABLE, ENCRYPTED
token_expires_at	DATETIME	Expiración token	NULLABLE
permissions	JSON	Permisos otorgados	DEFAULT '[]'
is_active	BOOLEAN	Cuenta activa	DEFAULT TRUE
last_sync_at	DATETIME	Última sincronización	NULLABLE
followers_count	INT	Seguidores	DEFAULT 0
created_at	DATETIME	Fecha creación	NOT NULL
updated_at	DATETIME	Última modificación	NOT NULL
 
3.2 Entidad: social_post
Posts programados o publicados.
Campo	Tipo	Descripción	Restricciones
id	SERIAL	ID interno	PRIMARY KEY
uuid	VARCHAR(36)	UUID público	UNIQUE, NOT NULL
tenant_id	INT	Tenant propietario	FK tenants.id, NOT NULL
content_hub_id	INT	Contenido origen	FK content_hub.id, NULLABLE
post_type	VARCHAR(32)	Tipo de post	ENUM: text|image|video|carousel|link|document
status	VARCHAR(32)	Estado	ENUM: draft|scheduled|publishing|published|failed
scheduled_at	DATETIME	Fecha programada	NULLABLE, INDEX
published_at	DATETIME	Fecha publicación real	NULLABLE
created_by	INT	Usuario creador	FK users.uid
approval_status	VARCHAR(32)	Aprobación	ENUM: pending|approved|rejected
approved_by	INT	Usuario aprobador	FK users.uid, NULLABLE
is_ai_generated	BOOLEAN	Generado por IA	DEFAULT FALSE
created_at	DATETIME	Fecha creación	NOT NULL
updated_at	DATETIME	Última modificación	NOT NULL
3.3 Entidad: social_post_variant
Variante específica por plataforma de cada post.
Campo	Tipo	Descripción	Restricciones
id	SERIAL	ID interno	PRIMARY KEY
post_id	INT	Post padre	FK social_post.id, INDEX
account_id	INT	Cuenta destino	FK social_account.id
platform	VARCHAR(32)	Plataforma	ENUM: linkedin|instagram|facebook|twitter
content_text	TEXT	Texto adaptado	NOT NULL
hashtags	JSON	Hashtags	DEFAULT '[]'
mentions	JSON	Menciones	DEFAULT '[]'
media_urls	JSON	URLs de medios	DEFAULT '[]'
link_url	VARCHAR(500)	URL enlace	NULLABLE
link_title	VARCHAR(255)	Título enlace	NULLABLE
status	VARCHAR(32)	Estado variante	ENUM: pending|published|failed
external_post_id	VARCHAR(255)	ID en plataforma	NULLABLE
external_url	VARCHAR(500)	URL publicación	NULLABLE
error_message	TEXT	Mensaje error	NULLABLE
impressions	INT	Impresiones	DEFAULT 0
engagements	INT	Interacciones	DEFAULT 0
clicks	INT	Clicks	DEFAULT 0
shares	INT	Compartidos	DEFAULT 0
comments	INT	Comentarios	DEFAULT 0
published_at	DATETIME	Fecha publicación	NULLABLE
stats_updated_at	DATETIME	Última actualización stats	NULLABLE
 
4. Generación de Variantes con IA
El sistema genera automáticamente variantes optimizadas para cada plataforma a partir de un contenido base, considerando límites de caracteres, formatos de hashtags, estilo de comunicación y mejores prácticas por red social.
4.1 Prompt Template para Variantes
Eres un experto en social media marketing. Genera una variante del siguiente  contenido optimizada para {platform}.  CONTENIDO ORIGINAL: {original_content}  REGLAS PARA {platform}: - LinkedIn: Tono profesional, 3000 chars max, emojis moderados, hashtags al final (3-5) - Instagram: Visual, emocional, 2200 chars, emojis frecuentes, hashtags (hasta 30) - Facebook: Conversacional, 500 chars ideal, pregunta para engagement - Twitter/X: Conciso, 280 chars, gancho fuerte, 1-2 hashtags inline  BRAND VOICE del tenant: {brand_voice_guidelines}  Genera SOLO el texto del post, sin explicaciones.
4.2 Best Time to Post (Algoritmo)
Plataforma	B2B Óptimo	B2C Óptimo	Días
LinkedIn	08:00-10:00, 17:00-18:00	12:00-14:00	Martes-Jueves
Instagram	11:00-13:00	19:00-21:00	Lunes, Miércoles
Facebook	09:00-11:00	13:00-16:00	Miércoles-Viernes
X (Twitter)	08:00-09:00	12:00-13:00	Lunes-Viernes
5. APIs REST
Método	Endpoint	Descripción
GET	/api/v1/social/accounts	Listar cuentas conectadas
POST	/api/v1/social/accounts/connect/{platform}	Iniciar OAuth flow
DELETE	/api/v1/social/accounts/{uuid}	Desconectar cuenta
GET	/api/v1/social/posts	Listar posts
POST	/api/v1/social/posts	Crear post
POST	/api/v1/social/posts/{uuid}/generate-variants	Generar variantes IA
POST	/api/v1/social/posts/{uuid}/schedule	Programar publicación
GET	/api/v1/social/calendar	Vista calendario
GET	/api/v1/social/analytics	Analytics consolidados
 
6. Flujos de Automatización (ECA)
6.1 ECA-SOCIAL-001: Auto-generate from Content Hub
Trigger: content_hub.status = 'published' AND auto_social = TRUE
1.	Extraer título, resumen y URL del contenido publicado
2.	Crear social_post con content_hub_id vinculado
3.	Para cada cuenta activa del tenant: generar variante con IA
4.	Programar según best_time_to_post del tenant
5.	Notificar para revisión si approval_required = TRUE
6.2 ECA-SOCIAL-002: Publish via Make.com
Trigger: social_post_variant.scheduled_at <= NOW() AND status = 'pending'
6.	Actualizar status = 'publishing'
7.	Llamar webhook Make.com con payload completo
8.	Make.com publica en plataforma correspondiente
9.	Recibir callback con external_post_id y external_url
10.	Actualizar status = 'published' o 'failed' con error
6.3 ECA-SOCIAL-003: Analytics Sync
Trigger: Cron cada 6 horas
11.	Para posts publicados en últimos 7 días:
•	LinkedIn: GET /ugcPosts/{id}/socialActions
•	Instagram: GET /{media-id}/insights
•	Facebook: GET /{post-id}/insights
12.	Actualizar impressions, engagements, clicks, shares, comments
13.	Calcular engagement_rate = engagements / impressions * 100
7. Roadmap de Implementación
Sprint	Timeline	Entregables	Horas
Sprint 1	Semana 1-2	Entidades base. OAuth flows para 4 plataformas.	15-18h
Sprint 2	Semana 3-4	Calendario visual. Creación de posts. Variantes.	15-20h
Sprint 3	Semana 5-6	Integración Make.com. Generación IA. Scheduling.	12-18h
Sprint 4	Semana 7-8	Analytics sync. Dashboard. QA y testing.	10-14h
Total Estimado: 50-70 horas
--- Fin del Documento ---
152_Marketing_jaraba_social_v1 | Jaraba Impact Platform | Enero 2026

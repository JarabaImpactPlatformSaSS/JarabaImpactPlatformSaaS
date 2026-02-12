JARABA_EMAIL
Email Marketing Nativo con IA
Sistema Completo de Automatización de Email Marketing
Versión:	1.0
Fecha:	Enero 2026
Código:	151_Marketing_jaraba_email_v1
Estado:	Especificación Técnica para Implementación
Horas Estimadas:	115-155 horas
Dependencias:	jaraba_core, jaraba_tenant, jaraba_crm, SendGrid API, ECA
1. Resumen Ejecutivo
El módulo jaraba_email proporciona un sistema de email marketing nativo con capacidades de automatización avanzada, segmentación inteligente, 50+ secuencias predefinidas y 150+ templates MJML responsive. Diseñado para reemplazar herramientas como Mailchimp, ActiveCampaign o ConvertKit, ofreciendo integración nativa con el ecosistema Jaraba y ahorro de €600-2,400/año por tenant.
1.1 Capacidades Principales
•	50+ secuencias de automatización predefinidas por vertical
•	150+ templates MJML responsive con personalización por tenant
•	Segmentación dinámica basada en comportamiento y atributos
•	A/B testing nativo con optimización automática
•	Personalización con IA (subject lines, contenido dinámico)
•	Integración SendGrid con tracking completo
•	Analytics detallados: opens, clicks, conversiones, heat maps
1.2 Herramientas que Reemplaza
Herramienta	Coste Anual	Ahorro
Mailchimp Standard	€780/año (5k contacts)	100%
ActiveCampaign Lite	€348/año	100%
ConvertKit Creator	€348/año	100%
SendGrid Marketing	€240/año	100%
 
2. Arquitectura Técnica
2.1 Stack Tecnológico
Componente	Tecnología
Backend	Drupal 11 Custom Entities + Queue System
Email Delivery	SendGrid API v3 (transactional + marketing)
Template Engine	MJML → HTML con Twig para variables
Queue	Redis Queue para envíos masivos (rate limiting)
Tracking	Webhooks SendGrid + pixel tracking custom
Segmentación	Query builder dinámico con MySQL views
IA Content	Claude API para subject lines y contenido
Editor Visual	React Email Builder con drag & drop
2.2 Flujo de Envío
1. Campaign/Sequence trigger → 2. Segment evaluation → 3. Template merge → 4. MJML compile → 5. Queue insertion → 6. SendGrid batch API → 7. Webhook tracking → 8. Analytics update
3. Esquema de Base de Datos
3.1 Entidad: email_list
Listas de suscriptores para segmentación.
Campo	Tipo	Descripción	Restricciones
id	SERIAL	ID interno	PRIMARY KEY
uuid	VARCHAR(36)	UUID público	UNIQUE, NOT NULL
tenant_id	INT	Tenant propietario	FK tenants.id, NOT NULL, INDEX
name	VARCHAR(255)	Nombre de la lista	NOT NULL
description	TEXT	Descripción	NULLABLE
type	VARCHAR(32)	Tipo de lista	ENUM: static|dynamic|segment
segment_query	JSON	Query para listas dinámicas	NULLABLE (para type=dynamic)
double_optin	BOOLEAN	Requiere confirmación	DEFAULT TRUE
welcome_sequence_id	INT	Secuencia de bienvenida	FK email_sequence.id, NULLABLE
subscriber_count	INT	Contador denormalizado	DEFAULT 0
is_active	BOOLEAN	Lista activa	DEFAULT TRUE
created_at	DATETIME	Fecha creación	NOT NULL
updated_at	DATETIME	Última modificación	NOT NULL
 
3.2 Entidad: email_subscriber
Suscriptores con estado y preferencias.
Campo	Tipo	Descripción	Restricciones
id	SERIAL	ID interno	PRIMARY KEY
uuid	VARCHAR(36)	UUID público	UNIQUE, NOT NULL
tenant_id	INT	Tenant propietario	FK tenants.id, INDEX
email	VARCHAR(255)	Email del suscriptor	NOT NULL, INDEX
first_name	VARCHAR(100)	Nombre	NULLABLE
last_name	VARCHAR(100)	Apellidos	NULLABLE
status	VARCHAR(32)	Estado suscripción	ENUM: pending|subscribed|unsubscribed|bounced|complained
source	VARCHAR(64)	Origen del suscriptor	ENUM: form|import|api|manual|lead_magnet
source_detail	VARCHAR(255)	Detalle del origen	NULLABLE (URL, form_id, etc)
ip_address	VARCHAR(45)	IP de suscripción	NULLABLE
user_agent	VARCHAR(500)	User agent	NULLABLE
gdpr_consent	BOOLEAN	Consentimiento GDPR	DEFAULT FALSE
gdpr_consent_at	DATETIME	Fecha consentimiento	NULLABLE
confirmed_at	DATETIME	Fecha confirmación	NULLABLE (double opt-in)
unsubscribed_at	DATETIME	Fecha baja	NULLABLE
unsubscribe_reason	VARCHAR(255)	Motivo de baja	NULLABLE
custom_fields	JSON	Campos personalizados	DEFAULT '{}'
tags	JSON	Etiquetas	DEFAULT '[]'
engagement_score	INT	Score de engagement	DEFAULT 50, RANGE 0-100
last_email_at	DATETIME	Último email enviado	NULLABLE, INDEX
last_open_at	DATETIME	Última apertura	NULLABLE
last_click_at	DATETIME	Último click	NULLABLE
total_emails_sent	INT	Total emails enviados	DEFAULT 0
total_opens	INT	Total aperturas	DEFAULT 0
total_clicks	INT	Total clicks	DEFAULT 0
created_at	DATETIME	Fecha creación	NOT NULL, INDEX
updated_at	DATETIME	Última modificación	NOT NULL
 
3.3 Entidad: email_template
Templates MJML con metadatos y versiones.
Campo	Tipo	Descripción	Restricciones
id	SERIAL	ID interno	PRIMARY KEY
uuid	VARCHAR(36)	UUID público	UNIQUE, NOT NULL
tenant_id	INT	Tenant (NULL=global)	FK tenants.id, NULLABLE, INDEX
name	VARCHAR(255)	Nombre del template	NOT NULL
category	VARCHAR(64)	Categoría	ENUM: onboarding|nurture|transactional|newsletter|promotional|reengagement
vertical	VARCHAR(32)	Vertical específica	ENUM: all|empleabilidad|emprendimiento|agroconecta|comercioconecta|serviciosconecta
subject_line	VARCHAR(255)	Asunto por defecto	NOT NULL
preview_text	VARCHAR(255)	Texto preview	NULLABLE
mjml_content	LONGTEXT	Código MJML	NOT NULL
html_compiled	LONGTEXT	HTML compilado	NOT NULL (auto-generated)
text_version	TEXT	Versión texto plano	NULLABLE
variables	JSON	Variables disponibles	DEFAULT '[]'
thumbnail_url	VARCHAR(500)	Preview image	NULLABLE
is_system	BOOLEAN	Template de sistema	DEFAULT FALSE
is_active	BOOLEAN	Activo	DEFAULT TRUE
version	INT	Versión	DEFAULT 1
created_at	DATETIME	Fecha creación	NOT NULL
updated_at	DATETIME	Última modificación	NOT NULL
 
3.4 Entidad: email_campaign
Campañas de email (one-time sends).
Campo	Tipo	Descripción	Restricciones
id	SERIAL	ID interno	PRIMARY KEY
uuid	VARCHAR(36)	UUID público	UNIQUE, NOT NULL
tenant_id	INT	Tenant propietario	FK tenants.id, NOT NULL
name	VARCHAR(255)	Nombre campaña	NOT NULL
type	VARCHAR(32)	Tipo de campaña	ENUM: regular|ab_test|rss|automated
status	VARCHAR(32)	Estado	ENUM: draft|scheduled|sending|sent|paused|cancelled
template_id	INT	Template base	FK email_template.id
subject_line	VARCHAR(255)	Asunto final	NOT NULL
preview_text	VARCHAR(255)	Texto preview	NULLABLE
from_name	VARCHAR(100)	Nombre remitente	NOT NULL
from_email	VARCHAR(255)	Email remitente	NOT NULL
reply_to	VARCHAR(255)	Reply-to	NULLABLE
list_ids	JSON	Listas destino	DEFAULT '[]'
segment_query	JSON	Segmento adicional	NULLABLE
exclude_list_ids	JSON	Listas excluidas	DEFAULT '[]'
scheduled_at	DATETIME	Fecha programada	NULLABLE
sent_at	DATETIME	Fecha envío real	NULLABLE
completed_at	DATETIME	Fin del envío	NULLABLE
total_recipients	INT	Total destinatarios	DEFAULT 0
total_sent	INT	Total enviados	DEFAULT 0
total_delivered	INT	Total entregados	DEFAULT 0
total_opens	INT	Total aperturas	DEFAULT 0
unique_opens	INT	Aperturas únicas	DEFAULT 0
total_clicks	INT	Total clicks	DEFAULT 0
unique_clicks	INT	Clicks únicos	DEFAULT 0
bounces	INT	Rebotes	DEFAULT 0
complaints	INT	Spam complaints	DEFAULT 0
unsubscribes	INT	Bajas	DEFAULT 0
ab_test_config	JSON	Config A/B test	NULLABLE
created_by	INT	Usuario creador	FK users.uid
created_at	DATETIME	Fecha creación	NOT NULL
updated_at	DATETIME	Última modificación	NOT NULL
 
3.5 Entidad: email_sequence
Secuencias de automatización (drip campaigns).
Campo	Tipo	Descripción	Restricciones
id	SERIAL	ID interno	PRIMARY KEY
uuid	VARCHAR(36)	UUID público	UNIQUE, NOT NULL
tenant_id	INT	Tenant (NULL=global)	FK tenants.id, NULLABLE
name	VARCHAR(255)	Nombre secuencia	NOT NULL
description	TEXT	Descripción	NULLABLE
category	VARCHAR(64)	Categoría	ENUM: onboarding|nurture|sales|reengagement|post_purchase|custom
vertical	VARCHAR(32)	Vertical específica	ENUM: all|empleabilidad|emprendimiento|agroconecta|comercioconecta|serviciosconecta
trigger_type	VARCHAR(64)	Tipo de trigger	ENUM: list_subscription|tag_added|event|date_field|manual|api
trigger_config	JSON	Configuración trigger	NOT NULL
entry_conditions	JSON	Condiciones de entrada	DEFAULT '[]'
exit_conditions	JSON	Condiciones de salida	DEFAULT '[]'
is_system	BOOLEAN	Secuencia de sistema	DEFAULT FALSE
is_active	BOOLEAN	Activa	DEFAULT TRUE
total_enrolled	INT	Total inscritos	DEFAULT 0
currently_enrolled	INT	Inscritos activos	DEFAULT 0
completed	INT	Completados	DEFAULT 0
created_at	DATETIME	Fecha creación	NOT NULL
updated_at	DATETIME	Última modificación	NOT NULL
3.6 Entidad: email_sequence_step
Pasos individuales de cada secuencia.
Campo	Tipo	Descripción	Restricciones
id	SERIAL	ID interno	PRIMARY KEY
sequence_id	INT	Secuencia padre	FK email_sequence.id, INDEX
position	INT	Orden en secuencia	NOT NULL
step_type	VARCHAR(32)	Tipo de paso	ENUM: email|delay|condition|action|split_test
template_id	INT	Template (si email)	FK email_template.id, NULLABLE
subject_line	VARCHAR(255)	Asunto override	NULLABLE
delay_value	INT	Valor de delay	NULLABLE
delay_unit	VARCHAR(16)	Unidad de delay	ENUM: minutes|hours|days|weeks
condition_config	JSON	Config condición	NULLABLE
action_config	JSON	Config acción	NULLABLE
is_active	BOOLEAN	Paso activo	DEFAULT TRUE
created_at	DATETIME	Fecha creación	NOT NULL
 
4. Catálogo de Secuencias Predefinidas (50+)
4.1 Secuencias Universales (10)
Secuencia	Trigger	Emails
Welcome Series	list_subscription	5 emails / 14 días
Lead Nurture - Top Funnel	lead_magnet_download	7 emails / 21 días
Lead Nurture - Mid Funnel	pricing_page_visited	5 emails / 10 días
Abandoned Cart	cart_abandoned	3 emails / 3 días
Post-Purchase	purchase_completed	4 emails / 14 días
Re-engagement Cold	no_open_30_days	3 emails / 7 días
Churn Prevention	health_score_low	4 emails / 14 días
Upsell Premium	usage_threshold	3 emails / 7 días
NPS Follow-up	nps_survey_completed	2 emails / 3 días
Referral Request	customer_30_days	2 emails / 5 días
4.2 Secuencias Empleabilidad (10)
Secuencia	Trigger	Emails
Welcome Job Seeker	profile_created	7 emails / 21 días
CV Incompleto	cv_incomplete_3_days	3 emails / 7 días
Job Alert Match	matching_job_found	1 email / inmediato
Application Submitted	application_sent	3 emails / 7 días
Interview Prep	interview_scheduled	3 emails / pre-interview
Course Completion	course_completed	2 emails / 3 días
Skill Verification	skill_added	1 email / 24h
Weekly Job Digest	cron_weekly	1 email / semanal
Inactive Job Seeker	no_login_14_days	3 emails / 10 días
Success Story Share	job_offer_accepted	2 emails / 7 días
4.3 Secuencias Emprendimiento (10)
Secuencia	Trigger	Emails
Welcome Entrepreneur	diagnostic_completed	7 emails / 21 días
Diagnostic Follow-up	diagnostic_partial	3 emails / 5 días
Digitalization Path	path_started	10 emails / 30 días
Milestone Achieved	milestone_completed	1 email / inmediato
Mentoring Session	session_scheduled	3 emails / pre+post
Kit Digital Info	kit_digital_interest	5 emails / 14 días
Business Plan Review	canvas_completed	2 emails / 3 días
Funding Opportunities	funding_profile_match	1 email / inmediato
Community Invitation	entrepreneur_active	2 emails / 5 días
Success Case Request	mvp_validated	2 emails / 7 días
 
5. APIs REST
5.1 Endpoints de Listas y Suscriptores
Método	Endpoint	Descripción
GET	/api/v1/email/lists	Listar todas las listas
POST	/api/v1/email/lists	Crear nueva lista
GET	/api/v1/email/lists/{uuid}/subscribers	Suscriptores de una lista
POST	/api/v1/email/subscribers	Añadir suscriptor
POST	/api/v1/email/subscribers/import	Importar CSV
PATCH	/api/v1/email/subscribers/{uuid}	Actualizar suscriptor
POST	/api/v1/email/subscribers/{uuid}/unsubscribe	Dar de baja
POST	/api/v1/email/subscribers/{uuid}/tags	Añadir tags
5.2 Endpoints de Campañas
Método	Endpoint	Descripción
GET	/api/v1/email/campaigns	Listar campañas
POST	/api/v1/email/campaigns	Crear campaña
GET	/api/v1/email/campaigns/{uuid}	Detalle de campaña
PATCH	/api/v1/email/campaigns/{uuid}	Actualizar campaña
POST	/api/v1/email/campaigns/{uuid}/schedule	Programar envío
POST	/api/v1/email/campaigns/{uuid}/send-now	Enviar inmediatamente
POST	/api/v1/email/campaigns/{uuid}/pause	Pausar envío
GET	/api/v1/email/campaigns/{uuid}/stats	Estadísticas detalladas
POST	/api/v1/email/campaigns/{uuid}/test	Enviar test
 
6. Flujos de Automatización (ECA)
6.1 ECA-EMAIL-001: Double Opt-in
Trigger: email_subscriber.created WHERE list.double_optin = TRUE
1.	Generar token de confirmación único (UUID + expiry 48h)
2.	Compilar template 'double_optin' con link de confirmación
3.	Enviar email vía SendGrid con tracking habilitado
4.	Si no confirmado en 24h → enviar reminder
5.	Si no confirmado en 48h → marcar como 'expired', no enviar más
6.2 ECA-EMAIL-002: Sequence Enrollment
Trigger: Según trigger_type de cada sequence
6.	Verificar entry_conditions del subscriber
7.	Verificar que no está ya enrolled en esta secuencia
8.	Crear email_sequence_enrollment con step_position = 1
9.	Procesar primer step (si es email → encolar, si es delay → programar)
10.	Actualizar sequence.currently_enrolled++
6.3 ECA-EMAIL-003: SendGrid Webhook Processing
Trigger: POST /webhooks/sendgrid/events
11.	Validar firma HMAC del webhook
12.	Para cada evento en batch:
•	delivered → actualizar email_send.status, campaign.total_delivered++
•	open → crear email_event, actualizar subscriber.last_open_at, engagement_score++
•	click → crear email_event con url, actualizar subscriber stats
•	bounce → marcar subscriber como bounced, excluir de futuros envíos
•	spam_report → marcar como complained, añadir a suppression list
6.4 ECA-EMAIL-004: Engagement Score Decay
Trigger: Cron diario 02:00
13.	SELECT subscribers WHERE last_open_at < NOW() - 30 days
14.	Aplicar decay: engagement_score = engagement_score * 0.95
15.	Si engagement_score < 20 → añadir tag 'cold_subscriber'
16.	Si engagement_score < 10 → trigger secuencia 'Re-engagement Cold'
 
7. Roadmap de Implementación
Sprint	Timeline	Entregables	Horas
Sprint 1	Semana 1-2	Entidades base: list, subscriber, template. Migraciones DB.	20-25h
Sprint 2	Semana 3-4	Integración SendGrid. MJML compiler. Double opt-in.	18-22h
Sprint 3	Semana 5-6	Entidad campaign. Editor visual. A/B testing.	22-28h
Sprint 4	Semana 7-8	Entidades sequence, step. Motor de secuencias.	20-25h
Sprint 5	Semana 9-10	Webhooks SendGrid. Analytics dashboard. Segmentación.	18-22h
Sprint 6	Semana 11-12	50 secuencias predefinidas. 150 templates MJML. QA.	20-28h
Total Estimado: 115-155 horas
--- Fin del Documento ---
151_Marketing_jaraba_email_v1 | Jaraba Impact Platform | Enero 2026

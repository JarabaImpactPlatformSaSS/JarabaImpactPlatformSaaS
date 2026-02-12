
JOB ALERTS
Sistema de Alertas de Empleo
Vertical de Empleabilidad Digital
JARABA IMPACT PLATFORM
Documento Técnico de Implementación
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	14_Empleabilidad_Job_Alerts
Dependencias:	11_Job_Board, 19_Matching_Engine
 
1. Resumen Ejecutivo
El sistema de Job Alerts permite a los candidatos recibir notificaciones automáticas cuando se publican ofertas que coinciden con sus criterios de búsqueda. Combina alertas configuradas manualmente con recomendaciones inteligentes basadas en el Matching Engine.
1.1 Tipos de Alertas
Tipo	Descripción	Trigger
Custom Alert	Alerta creada manualmente con filtros específicos	Nueva oferta que cumple filtros
Smart Match	Alertas automáticas basadas en perfil del candidato	match_score >= threshold
Saved Search	Búsqueda guardada convertida en alerta	Nuevos resultados en búsqueda
Company Follow	Seguimiento de empresa específica	Nueva oferta de empresa seguida
Similar Jobs	Ofertas similares a una guardada/aplicada	Similar job published
1.2 Canales de Notificación
Canal	Características	Configuración
Email	Digest con múltiples ofertas, rich HTML	Frecuencia: instant|daily|weekly
Push Web	Notificación en navegador, click directo	Opt-in required, instant only
Push Mobile	Notificación en app móvil	Via Firebase Cloud Messaging
In-App	Centro de notificaciones dentro de la plataforma	Siempre activo, badge counter
WhatsApp	Mensaje vía WhatsApp Business API (futuro)	Premium feature, opt-in
 
2. Arquitectura de Entidades
2.1 Entidad Extendida: job_alert
Extensión de la entidad base con campos completos para alertas configurables:
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
user_id	INT	Usuario propietario	FK users.uid, NOT NULL, INDEX
name	VARCHAR(128)	Nombre de la alerta	NOT NULL
alert_type	VARCHAR(32)	Tipo de alerta	ENUM: custom|smart_match|saved_search|company_follow|similar_jobs
keywords	VARCHAR(500)	Palabras clave	NULLABLE, fulltext
categories	JSON	Categorías de empleo	Array of taxonomy_term.tid
skills	JSON	Skills requeridos	Array of skill.tid
skills_match_mode	VARCHAR(8)	Modo de match skills	ENUM: any|all
locations	JSON	Ubicaciones	Array of {city, province, country}
location_radius_km	INT	Radio desde ubicación	DEFAULT 50
remote_types	JSON	Tipos de remoto	Array: onsite|hybrid|remote
job_types	JSON	Tipos de contrato	Array: full_time|part_time|contract|internship
experience_levels	JSON	Niveles experiencia	Array: entry|mid|senior|executive
salary_min	DECIMAL(10,2)	Salario mínimo	NULLABLE
salary_max	DECIMAL(10,2)	Salario máximo	NULLABLE
company_ids	JSON	Empresas seguidas	Array of employer_profile.id
exclude_company_ids	JSON	Empresas excluidas	Array of employer_profile.id
source_job_id	INT	Oferta origen (similar)	FK job_posting.id, NULLABLE
match_threshold	INT	Umbral para smart match	DEFAULT 70, RANGE 0-100
frequency	VARCHAR(16)	Frecuencia envío	ENUM: instant|daily|weekly
channels	JSON	Canales activos	Array: email|push_web|push_mobile|in_app
preferred_send_time	TIME	Hora preferida (digest)	DEFAULT 09:00
preferred_send_day	INT	Día preferido (weekly)	1=Monday, DEFAULT 1
max_results_per_digest	INT	Max ofertas por digest	DEFAULT 10
is_active	BOOLEAN	Alerta activa	DEFAULT TRUE
last_sent_at	DATETIME	Último envío	NULLABLE, UTC
last_matched_at	DATETIME	Último match encontrado	NULLABLE, UTC
total_matches	INT	Total histórico	DEFAULT 0
total_sent	INT	Notificaciones enviadas	DEFAULT 0
clicks	INT	Clicks en notificaciones	DEFAULT 0
applications_from_alert	INT	Aplicaciones originadas	DEFAULT 0
created	DATETIME	Fecha creación	NOT NULL, UTC
changed	DATETIME	Última modificación	NOT NULL, UTC
 
2.2 Entidad: alert_notification
Registro de cada notificación enviada:
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
alert_id	INT	Alerta origen	FK job_alert.id, NOT NULL
user_id	INT	Usuario destinatario	FK users.uid, NOT NULL, INDEX
notification_type	VARCHAR(16)	Tipo de notificación	ENUM: instant|digest
channel	VARCHAR(16)	Canal usado	ENUM: email|push_web|push_mobile|in_app
job_ids	JSON	Ofertas incluidas	Array of job_posting.id
job_count	INT	Cantidad de ofertas	NOT NULL
subject	VARCHAR(255)	Asunto (email)	NULLABLE
preview_text	VARCHAR(500)	Preview text	NULLABLE
sent_at	DATETIME	Fecha de envío	NOT NULL, UTC, INDEX
delivered_at	DATETIME	Fecha de entrega	NULLABLE (webhook)
opened_at	DATETIME	Fecha de apertura	NULLABLE (tracking pixel)
clicked_at	DATETIME	Primer click	NULLABLE
clicked_job_ids	JSON	Ofertas clickeadas	Array of job_posting.id
unsubscribed	BOOLEAN	Unsubscribe en este email	DEFAULT FALSE
bounce_type	VARCHAR(16)	Tipo de bounce	NULLABLE: soft|hard
external_id	VARCHAR(128)	ID externo (SendGrid, etc)	NULLABLE
2.3 Entidad: company_follow
Relación de seguimiento usuario-empresa:
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
user_id	INT	Usuario que sigue	FK users.uid, NOT NULL
employer_id	INT	Empresa seguida	FK employer_profile.id, NOT NULL
notify_new_jobs	BOOLEAN	Notificar nuevas ofertas	DEFAULT TRUE
notify_updates	BOOLEAN	Notificar actualizaciones	DEFAULT FALSE
created_at	DATETIME	Fecha de follow	NOT NULL, UTC
Índice único:
UNIQUE INDEX (user_id, employer_id)
 
3. Motor de Matching de Alertas
3.1 Pipeline de Procesamiento
Cuando se publica una nueva oferta, el sistema ejecuta el siguiente pipeline:
1.	Job Publication Event: job_posting.status cambia a 'published'
2.	Queue Job: Crear tarea en cola 'alert_matching'
3.	Load Active Alerts: SELECT * FROM job_alert WHERE is_active = TRUE
4.	Filter by Type: Procesar custom alerts y company follows
5.	Match Evaluation: Para cada alerta, evaluar si job cumple criterios
6.	Smart Match: Para alertas smart_match, calcular score con Matching Engine
7.	Threshold Check: Verificar score >= match_threshold
8.	Notification Queue: Agrupar matches por usuario y frecuencia
9.	Send Instant: Enviar notificaciones frequency='instant'
10.	Queue Digest: Almacenar para digest diario/semanal
3.2 Evaluación de Criterios (Custom Alert)
function evaluateCustomAlert(job, alert):     // Todos los criterios configurados deben cumplirse (AND)          if alert.keywords:         if not fulltext_match(job.title + job.description, alert.keywords):             return false          if alert.categories:         if job.category_id not in alert.categories:             return false          if alert.skills:         job_skills = job.skills_required + job.skills_preferred         if alert.skills_match_mode == 'all':             if not all(s in job_skills for s in alert.skills):                 return false         else:  # any             if not any(s in job_skills for s in alert.skills):                 return false          if alert.locations:         if not location_matches(job.location, alert.locations, alert.location_radius_km):             return false          if alert.remote_types:         if job.remote_type not in alert.remote_types:             return false          if alert.job_types:         if job.job_type not in alert.job_types:             return false          if alert.experience_levels:         if job.experience_level not in alert.experience_levels:             return false          if alert.salary_min:         if job.salary_max and job.salary_max < alert.salary_min:             return false          if alert.exclude_company_ids:         if job.employer_id in alert.exclude_company_ids:             return false          return true
 
4. Sistema de Digest
4.1 Proceso de Digest Diario
Trigger: Cron cada hora (para respetar preferred_send_time por zona horaria)
11.	Identificar usuarios con digest pendiente y hora actual = preferred_send_time
12.	Agregar todos los matches desde last_sent_at
13.	Ordenar ofertas por match_score descendente
14.	Limitar a max_results_per_digest
15.	Generar email HTML con template de digest
16.	Enviar via SendGrid/Mailgun
17.	Actualizar last_sent_at en todas las alertas procesadas
18.	Crear registro en alert_notification
4.2 Template de Email Digest
El email de digest incluye las siguientes secciones:
•	Header: 'X nuevas ofertas que coinciden con tus alertas'
•	Top Match: Oferta destacada con descripción expandida
•	More Matches: Lista compacta de ofertas adicionales
•	CTA Buttons: 'Ver todas las ofertas', 'Gestionar alertas'
•	Footer: Unsubscribe link, preference center link
 
5. Push Notifications
5.1 Web Push (Service Worker)
Implementación con VAPID keys y Service Worker:
// Service Worker registration navigator.serviceWorker.register('/sw.js').then(reg => {     reg.pushManager.subscribe({         userVisibleOnly: true,         applicationServerKey: VAPID_PUBLIC_KEY     }).then(subscription => {         // Enviar subscription al backend         fetch('/api/v1/push/subscribe', {             method: 'POST',             body: JSON.stringify(subscription)         });     }); });  // Payload de notificación {     "title": "Nueva oferta: Senior Developer en TechCorp",     "body": "Match score: 92% - Remoto, €45-55k",     "icon": "/images/logo-192.png",     "badge": "/images/badge-72.png",     "data": {         "url": "/jobs/12345",         "job_id": 12345,         "alert_id": 678     },     "actions": [         { "action": "view", "title": "Ver oferta" },         { "action": "dismiss", "title": "Ignorar" }     ] }
5.2 Entidad: push_subscription
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
user_id	INT	Usuario	FK users.uid, NOT NULL
endpoint	TEXT	Push endpoint URL	NOT NULL
p256dh_key	VARCHAR(255)	Public key	NOT NULL
auth_key	VARCHAR(255)	Auth secret	NOT NULL
user_agent	VARCHAR(255)	Browser/Device	For debugging
is_active	BOOLEAN	Subscription activa	DEFAULT TRUE
created_at	DATETIME	Fecha registro	NOT NULL, UTC
last_used_at	DATETIME	Última notificación	NULLABLE
 
6. Analytics de Alertas
6.1 Métricas de Alertas
Métrica	Cálculo	Objetivo
Open Rate	Emails abiertos / Emails enviados × 100	> 25%
Click Rate	Emails con click / Emails enviados × 100	> 10%
Application Rate	Aplicaciones desde alerta / Clicks × 100	> 15%
Unsubscribe Rate	Unsubscribes / Emails enviados × 100	< 0.5%
Alert Effectiveness	Hires desde alertas / Total hires × 100	> 20%
Match Accuracy	Aplicaciones / Notificaciones enviadas	> 5%
7. APIs REST
Método	Endpoint	Descripción
GET	/api/v1/alerts	Listar mis alertas
POST	/api/v1/alerts	Crear nueva alerta
PATCH	/api/v1/alerts/{id}	Actualizar alerta
DELETE	/api/v1/alerts/{id}	Eliminar alerta
POST	/api/v1/alerts/{id}/toggle	Activar/desactivar alerta
POST	/api/v1/alerts/from-search	Crear alerta desde búsqueda guardada
POST	/api/v1/companies/{id}/follow	Seguir empresa
DELETE	/api/v1/companies/{id}/follow	Dejar de seguir empresa
POST	/api/v1/push/subscribe	Registrar push subscription
GET	/api/v1/notifications	Centro de notificaciones in-app
8. Roadmap de Implementación
Sprint	Timeline	Entregables	Deps
Sprint 1	Semana 1-2	Entidades job_alert extendida, alert_notification. CRUD alertas.	Job Board
Sprint 2	Semana 3-4	Motor de matching de alertas. Integración con Matching Engine.	Sprint 1
Sprint 3	Semana 5-6	Sistema de digest. Email templates. SendGrid integration.	Sprint 2
Sprint 4	Semana 7-8	Web Push. Service Worker. Push subscriptions.	Sprint 3
Sprint 5	Semana 9-10	Analytics. Company follow. Frontend UI. QA. Go-live.	Sprint 4
— Fin del Documento —
14_Empleabilidad_Job_Alerts_v1.docx | Jaraba Impact Platform | Enero 2026


JOB BOARD CORE
Portal de Empleo y Bolsa de Trabajo
Vertical de Empleabilidad Digital
JARABA IMPACT PLATFORM
Documento Técnico de Implementación
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	11_Empleabilidad_Job_Board_Core
Dependencias:	01_Core_Entidades, 15_Candidate_Profile
 
1. Resumen Ejecutivo
Este documento especifica la arquitectura técnica del Job Board (Bolsa de Empleo) para la vertical de Empleabilidad. El Job Board es el componente que cierra el ciclo del ecosistema, conectando a las PYMEs digitalizadas con el talento formado en el programa Impulso Empleo.
1.1 Propuesta de Valor
•	Para candidatos (Lucía): Acceso a ofertas de empresas que valoran el Método Jaraba y competencias digitales
•	Para empleadores (Marta): Pool exclusivo de talento pre-cualificado y certificado en competencias digitales
•	Para el ecosistema: Métrica de impacto tangible (colocaciones) para justificación de subvenciones
1.2 Arquitectura General
El Job Board opera como un marketplace de dos lados dentro de la plataforma unificada:
Componente	Descripción
Employer Portal	Dashboard para publicar ofertas, gestionar candidaturas, ATS básico
Candidate Portal	Búsqueda de ofertas, aplicaciones, tracking de candidaturas
Matching Engine	Algoritmo de recomendación bidireccional (ofertas ↔ candidatos)
Search API	Búsqueda facetada con Search API + Qdrant para semántica
Analytics	Métricas de recruitment: time-to-fill, source tracking, placement rate
 
2. Arquitectura de Entidades
El Job Board introduce 5 nuevas entidades Drupal que gestionan el ciclo completo de reclutamiento.
2.1 Entidad: job_posting
Representa una oferta de empleo publicada por un empleador. Incluye todos los campos necesarios para búsqueda facetada y SEO/GEO.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único global	UNIQUE, NOT NULL, INDEX
reference_code	VARCHAR(32)	Código de referencia visible	UNIQUE, AUTO: JOB-{YEAR}-{SEQ}
title	VARCHAR(255)	Título del puesto	NOT NULL, INDEX (fulltext)
slug	VARCHAR(128)	URL amigable	UNIQUE, NOT NULL
employer_id	INT	Empresa que publica	FK employer_profile.id, NOT NULL
tenant_id	INT	Tenant asociado	FK tenant.id, INDEX
description	TEXT	Descripción completa (HTML)	NOT NULL
requirements	TEXT	Requisitos del puesto	NOT NULL
responsibilities	TEXT	Responsabilidades	NULLABLE
benefits	TEXT	Beneficios ofrecidos	NULLABLE
job_type	VARCHAR(32)	Tipo de contrato	ENUM: full_time|part_time|contract|internship|freelance
experience_level	VARCHAR(32)	Nivel de experiencia	ENUM: entry|junior|mid|senior|executive
education_level	VARCHAR(32)	Formación requerida	ENUM: none|secondary|vocational|bachelor|master|phd
remote_type	VARCHAR(32)	Modalidad de trabajo	ENUM: onsite|hybrid|remote|flexible
location_city	VARCHAR(128)	Ciudad	NOT NULL, INDEX
location_province	VARCHAR(64)	Provincia	INDEX
location_country	VARCHAR(2)	País ISO 3166-1	DEFAULT 'ES'
location_lat	DECIMAL(10,8)	Latitud	NULLABLE, para geo-search
location_lng	DECIMAL(11,8)	Longitud	NULLABLE
salary_min	DECIMAL(10,2)	Salario mínimo	NULLABLE
salary_max	DECIMAL(10,2)	Salario máximo	NULLABLE
salary_currency	VARCHAR(3)	Moneda salario	DEFAULT 'EUR'
salary_period	VARCHAR(16)	Período salarial	ENUM: hourly|monthly|yearly
salary_visible	BOOLEAN	Mostrar salario público	DEFAULT TRUE
skills_required	JSON	Skills requeridos (taxonomy IDs)	Array of skill.tid
skills_preferred	JSON	Skills deseables	Array of skill.tid
languages	JSON	Idiomas requeridos	Array: {lang, level}
category_id	INT	Categoría laboral	FK taxonomy_term (job_category)
status	VARCHAR(16)	Estado de la oferta	ENUM: draft|pending|published|paused|closed|filled
visibility	VARCHAR(16)	Visibilidad	ENUM: public|ecosystem|private
is_featured	BOOLEAN	Oferta destacada (pago)	DEFAULT FALSE
featured_until	DATETIME	Fecha fin destacado	NULLABLE
application_method	VARCHAR(16)	Método de aplicación	ENUM: internal|external|email
external_url	VARCHAR(512)	URL externa si aplica	NULLABLE
application_email	VARCHAR(255)	Email si método=email	NULLABLE
vacancies	INT	Número de vacantes	DEFAULT 1, > 0
applications_count	INT	Contador de aplicaciones	DEFAULT 0, computed
views_count	INT	Contador de vistas	DEFAULT 0
published_at	DATETIME	Fecha de publicación	NULLABLE, UTC
expires_at	DATETIME	Fecha de expiración	NULLABLE, UTC, INDEX
closed_at	DATETIME	Fecha de cierre	NULLABLE, UTC
created	DATETIME	Fecha creación	NOT NULL, UTC
changed	DATETIME	Última modificación	NOT NULL, UTC
 
2.2 Entidad: job_application
Registra una candidatura de un usuario a una oferta. Implementa un mini-ATS con estados de pipeline.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
job_id	INT	Oferta	FK job_posting.id, NOT NULL, INDEX
candidate_id	INT	Candidato	FK users.uid, NOT NULL, INDEX
candidate_profile_id	INT	Perfil del candidato	FK candidate_profile.id
status	VARCHAR(32)	Estado en pipeline	ENUM: applied|screening|shortlisted|interviewed|offered|hired|rejected|withdrawn
cover_letter	TEXT	Carta de presentación	NULLABLE
cv_file_id	INT	CV adjunto	FK file_managed.fid, NULLABLE
cv_version	VARCHAR(32)	Versión del CV usado	Snapshot del perfil
portfolio_url	VARCHAR(512)	URL portfolio	NULLABLE
answers	JSON	Respuestas a screening questions	NULLABLE
match_score	DECIMAL(5,2)	Score de matching	RANGE 0-100, computed
source	VARCHAR(32)	Fuente de la aplicación	ENUM: organic|recommended|alert|import|api
referral_code	VARCHAR(64)	Código de referido	NULLABLE
employer_notes	TEXT	Notas internas del empleador	NULLABLE, private
employer_rating	INT	Valoración del empleador	RANGE 1-5, NULLABLE
rejection_reason	VARCHAR(64)	Motivo de rechazo	ENUM taxonomy, NULLABLE
rejection_feedback	TEXT	Feedback al candidato	NULLABLE
interview_scheduled_at	DATETIME	Fecha entrevista	NULLABLE, UTC
offered_salary	DECIMAL(10,2)	Salario ofrecido	NULLABLE
offer_expires_at	DATETIME	Expiración de oferta	NULLABLE
hired_at	DATETIME	Fecha de contratación	NULLABLE, UTC
applied_at	DATETIME	Fecha de aplicación	NOT NULL, UTC
last_status_change	DATETIME	Último cambio de estado	NOT NULL, UTC, INDEX
viewed_by_employer	BOOLEAN	Vista por empleador	DEFAULT FALSE
viewed_at	DATETIME	Fecha primera vista	NULLABLE
Índice único compuesto:
UNIQUE INDEX (job_id, candidate_id) — Un candidato solo puede aplicar una vez por oferta.
 
2.3 Entidad: employer_profile
Perfil de empresa empleadora. Puede ser una PYME del ecosistema o una empresa externa.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
user_id	INT	Usuario administrador	FK users.uid, NOT NULL
tenant_id	INT	Tenant si es PYME del ecosistema	FK tenant.id, NULLABLE
company_name	VARCHAR(255)	Nombre de la empresa	NOT NULL
slug	VARCHAR(128)	URL amigable	UNIQUE, NOT NULL
logo_file_id	INT	Logo	FK file_managed.fid
cover_file_id	INT	Imagen de portada	FK file_managed.fid, NULLABLE
description	TEXT	Descripción de la empresa	NULLABLE
industry_id	INT	Sector/industria	FK taxonomy_term (industry)
company_size	VARCHAR(32)	Tamaño de empresa	ENUM: 1-10|11-50|51-200|201-500|500+
founded_year	INT	Año de fundación	NULLABLE, RANGE 1800-current
website	VARCHAR(512)	Sitio web	NULLABLE
linkedin_url	VARCHAR(512)	Perfil LinkedIn	NULLABLE
location_city	VARCHAR(128)	Ciudad sede	NOT NULL
location_country	VARCHAR(2)	País	DEFAULT 'ES'
contact_email	VARCHAR(255)	Email de contacto	NOT NULL
contact_phone	VARCHAR(32)	Teléfono	NULLABLE
plan_type	VARCHAR(32)	Plan de suscripción	ENUM: free|basic|pro|enterprise
jobs_limit	INT	Límite de ofertas activas	Según plan, DEFAULT 3
featured_jobs_limit	INT	Destacadas incluidas	Según plan, DEFAULT 0
is_verified	BOOLEAN	Empresa verificada	DEFAULT FALSE
verified_at	DATETIME	Fecha verificación	NULLABLE
is_ecosystem_member	BOOLEAN	Miembro del ecosistema	DEFAULT FALSE
total_jobs_posted	INT	Total ofertas publicadas	DEFAULT 0, computed
total_hires	INT	Total contrataciones	DEFAULT 0, computed
status	VARCHAR(16)	Estado del perfil	ENUM: pending|active|suspended
created	DATETIME	Fecha creación	NOT NULL, UTC
changed	DATETIME	Última modificación	NOT NULL, UTC
 
2.4 Entidad: saved_job
Ofertas guardadas por candidatos para revisión posterior.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
user_id	INT	Usuario	FK users.uid, NOT NULL, INDEX
job_id	INT	Oferta guardada	FK job_posting.id, NOT NULL, INDEX
notes	TEXT	Notas personales	NULLABLE
reminder_at	DATETIME	Recordatorio	NULLABLE
created	DATETIME	Fecha guardado	NOT NULL, UTC
2.5 Entidad: job_alert
Alertas de empleo configuradas por candidatos para recibir notificaciones de nuevas ofertas.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
user_id	INT	Usuario	FK users.uid, NOT NULL, INDEX
name	VARCHAR(128)	Nombre de la alerta	NOT NULL
keywords	VARCHAR(512)	Palabras clave	NULLABLE
category_ids	JSON	Categorías	Array of taxonomy IDs
job_types	JSON	Tipos de contrato	Array of ENUM values
experience_levels	JSON	Niveles de experiencia	Array of ENUM values
remote_types	JSON	Modalidades	Array of ENUM values
location_city	VARCHAR(128)	Ciudad	NULLABLE
location_radius_km	INT	Radio en km	DEFAULT 50
salary_min	DECIMAL(10,2)	Salario mínimo	NULLABLE
skill_ids	JSON	Skills requeridos	Array of skill.tid
frequency	VARCHAR(16)	Frecuencia de envío	ENUM: instant|daily|weekly
channel	VARCHAR(16)	Canal de notificación	ENUM: email|push|both
is_active	BOOLEAN	Alerta activa	DEFAULT TRUE
last_sent_at	DATETIME	Último envío	NULLABLE
matches_count	INT	Total matches enviados	DEFAULT 0
created	DATETIME	Fecha creación	NOT NULL, UTC
 
3. Taxonomías y Vocabularios
El Job Board requiere las siguientes taxonomías para clasificación y búsqueda facetada.
Vocabulario	Descripción	Ejemplos
job_category	Categorías laborales jerárquicas	Marketing > SEO, IT > Desarrollo Web
skills	Competencias y habilidades	Excel, Python, Google Ads, WordPress
industry	Sectores empresariales	Agroalimentario, Comercio, Servicios, Tech
rejection_reason	Motivos de rechazo estándar	Experiencia insuficiente, Perfil no encaja
location_hierarchy	Jerarquía geográfica España	Andalucía > Córdoba > Santaella
4. Configuración Search API
La búsqueda de ofertas utiliza Search API con backend Solr/Database + Qdrant para búsqueda semántica.
4.1 Índice: job_postings
Campo	Tipo Index	Faceta	Boost
title	Fulltext	No	5.0
description	Fulltext	No	1.0
category_id	Integer	Sí (hierarchical)	-
job_type	String	Sí (checkbox)	-
experience_level	String	Sí (checkbox)	-
remote_type	String	Sí (checkbox)	-
location_city	String	Sí (autocomplete)	2.0
salary_min	Decimal	Sí (range slider)	-
skills_required	Integer (multi)	Sí (checkbox)	3.0
published_at	Date	Sí (date range)	-
is_featured	Boolean	No (sort boost)	+10.0 if true
 
5. APIs REST
Método	Endpoint	Descripción
GET	/api/v1/jobs	Buscar ofertas (query params: q, category, type, location, etc.)
GET	/api/v1/jobs/{id}	Detalle de oferta con empresa
POST	/api/v1/jobs	Crear oferta (employer auth)
PATCH	/api/v1/jobs/{id}	Actualizar oferta
POST	/api/v1/jobs/{id}/apply	Aplicar a oferta (candidate auth)
GET	/api/v1/applications/my	Mis candidaturas (candidate)
GET	/api/v1/jobs/{id}/applications	Candidaturas de una oferta (employer)
PATCH	/api/v1/applications/{id}/status	Cambiar estado de candidatura
GET	/api/v1/jobs/recommended	Ofertas recomendadas según perfil
POST	/api/v1/alerts	Crear alerta de empleo
 
6. Flujos de Automatización (ECA)
6.1 ECA-JOB-001: Publicación de Oferta
Trigger: job_posting.status cambia a 'published'
1.	Indexar en Search API
2.	Generar embedding en Qdrant para búsqueda semántica
3.	Buscar job_alerts que coincidan con criterios
4.	Encolar notificaciones para alertas con frequency='instant'
5.	Registrar evento de publicación en analytics
6.2 ECA-JOB-002: Nueva Candidatura
Trigger: Inserción en job_application
6.	Calcular match_score (skills overlap + experience fit + location)
7.	Incrementar job_posting.applications_count
8.	Enviar email confirmación al candidato
9.	Notificar al empleador (email + dashboard)
10.	Asignar +20 créditos de impacto al candidato
6.3 ECA-JOB-003: Cambio de Estado
Trigger: job_application.status actualizado
11.	Notificar al candidato del cambio de estado
12.	Si status='hired': registrar contratación, actualizar métricas de impacto
13.	Si status='rejected' y rejection_feedback: enviar feedback al candidato
14.	Actualizar time_to_hire del employer_profile
6.4 ECA-JOB-004: Expiración Automática
Trigger: Cron diario (00:00 UTC)
15.	Buscar job_postings WHERE expires_at < NOW() AND status = 'published'
16.	Actualizar status = 'closed'
17.	Notificar al empleador de la expiración
18.	Sugerir renovación o cierre manual
 
7. Métricas de Impacto
El Job Board alimenta métricas clave para justificación de subvenciones y análisis de impacto.
Métrica	Cálculo	Target
Placement Rate	Contrataciones / Candidatos activos × 100	> 25%
Time to Hire	AVG(hired_at - published_at) en días	< 30 días
Application Rate	Aplicaciones / Vistas de oferta × 100	> 5%
Interview Conversion	Entrevistas / Aplicaciones × 100	> 15%
Ecosystem Hiring	Contrataciones de candidatos formados en LMS	> 40%
Employer NPS	Net Promoter Score de empleadores	> 50
8. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 1	Semana 1-2	Entidades job_posting, employer_profile. Taxonomías. Admin UI.	Core entities
Sprint 2	Semana 3-4	Entidades job_application, saved_job, job_alert. APIs REST.	Sprint 1
Sprint 3	Semana 5-6	Search API config. Búsqueda facetada. Integración Qdrant.	Sprint 2
Sprint 4	Semana 7-8	Frontend: listado ofertas, detalle, aplicación. Employer dashboard.	Sprint 3
Sprint 5	Semana 9-10	Flujos ECA. Sistema de alertas. Notificaciones email.	Sprint 4
Sprint 6	Semana 11-12	Matching engine. Analytics dashboard. QA. Go-live.	Sprint 5
— Fin del Documento —
11_Empleabilidad_Job_Board_Core_v1.docx | Jaraba Impact Platform | Enero 2026

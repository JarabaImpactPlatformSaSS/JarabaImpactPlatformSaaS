
SISTEMA LMS CORE
Learning Management System
Vertical de Empleabilidad Digital
JARABA IMPACT PLATFORM
Documento Técnico de Implementación
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	08_Empleabilidad_LMS_Core
Dependencias:	01_Core_Entidades, 06_Core_Flujos_ECA
 
1. Resumen Ejecutivo
Este documento especifica la arquitectura técnica del Sistema de Gestión de Aprendizaje (LMS) para la vertical de Empleabilidad del Ecosistema Jaraba. El LMS es el componente central del programa "Impulso Empleo", que transforma a usuarios del Diagnóstico Express en profesionales con competencias digitales certificadas.
1.1 Objetivos del Sistema
•	Formación personalizada: Rutas de aprendizaje adaptadas al perfil del Diagnóstico Express
•	Tracking completo: xAPI/SCORM para registro granular de progreso y completitud
•	Certificación automática: Emisión de credenciales digitales al completar módulos
•	Gamificación: Sistema de créditos de impacto integrado con el ecosistema
•	Multi-tenant: Contenido compartido y específico por tenant/entidad
1.2 Stack Tecnológico
Componente	Tecnología
Core CMS	Drupal 11 con módulo jaraba_lms custom
Contenido Interactivo	H5P (módulo contrib) para quizzes, videos, presentaciones
Learning Record Store	Drupal entities + opcionalmente Learning Locker (xAPI)
Video Hosting	Bunny.net Stream / Vimeo OTT para contenido premium
Automatización	ECA Module para flujos de enrollment y certificación
Notificaciones	ActiveCampaign + Drupal Queue para emails de progreso
 
2. Arquitectura de Entidades
El LMS introduce 6 nuevas entidades Drupal personalizadas que extienden el esquema base definido en 01_Core_Entidades.
2.1 Entidad: course
Representa un curso o programa formativo completo. Un curso contiene múltiples lecciones y puede pertenecer a uno o más learning paths.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno autoincremental	PRIMARY KEY
uuid	UUID	Identificador único global	UNIQUE, NOT NULL, INDEX
title	VARCHAR(255)	Título del curso	NOT NULL
machine_name	VARCHAR(64)	Slug URL-friendly	UNIQUE, NOT NULL, INDEX
description	TEXT	Descripción larga (HTML)	NULLABLE
summary	VARCHAR(500)	Resumen para cards/SEO	NOT NULL
thumbnail	INT	Referencia a file_managed	FK file_managed.fid
duration_minutes	INT	Duración estimada total	NOT NULL, > 0
difficulty_level	VARCHAR(16)	Nivel de dificultad	ENUM: beginner|intermediate|advanced
vertical_id	INT	Vertical asociada	FK taxonomy_term, DEFAULT empleabilidad
tenant_id	INT	Tenant propietario (NULL = global)	FK tenant.id, NULLABLE
is_published	BOOLEAN	Publicado y visible	DEFAULT FALSE
is_premium	BOOLEAN	Requiere suscripción/pago	DEFAULT FALSE
price	DECIMAL(10,2)	Precio si es de pago	NULLABLE, >= 0
currency	VARCHAR(3)	Moneda ISO 4217	DEFAULT 'EUR'
completion_credits	INT	Créditos de impacto al completar	DEFAULT 100
certificate_template_id	INT	Template de certificado	FK certificate_template.id
prerequisites	JSON	IDs de cursos prerequisito	NULLABLE, array of course.id
tags	JSON	Tags/skills asociados	Array of taxonomy_term.tid
author_id	INT	Creador del curso	FK users.uid
created	DATETIME	Fecha de creación	NOT NULL, UTC
changed	DATETIME	Última modificación	NOT NULL, UTC
 
2.2 Entidad: lesson
Unidad de contenido dentro de un curso. Cada lección contiene una o más actividades (videos, lecturas, quizzes).
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
course_id	INT	Curso padre	FK course.id, NOT NULL, INDEX
title	VARCHAR(255)	Título de la lección	NOT NULL
description	TEXT	Descripción/objetivos	NULLABLE
weight	INT	Orden dentro del curso	DEFAULT 0, INDEX
duration_minutes	INT	Duración estimada	NOT NULL, > 0
is_preview	BOOLEAN	Disponible como preview	DEFAULT FALSE
unlock_after_days	INT	Días desde enrollment para desbloquear	DEFAULT 0 (inmediato)
completion_type	VARCHAR(16)	Criterio de completitud	ENUM: all_activities|any_activity|time_spent|quiz_pass
minimum_score	INT	Score mínimo si completion_type=quiz_pass	RANGE 0-100, DEFAULT 70
status	VARCHAR(16)	Estado de publicación	ENUM: draft|published|archived
created	DATETIME	Fecha creación	NOT NULL, UTC
changed	DATETIME	Última modificación	NOT NULL, UTC
2.3 Entidad: activity
Elemento atómico de contenido: video, lectura, quiz H5P, tarea, etc.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
lesson_id	INT	Lección padre	FK lesson.id, NOT NULL, INDEX
title	VARCHAR(255)	Título de la actividad	NOT NULL
activity_type	VARCHAR(32)	Tipo de actividad	ENUM: video|reading|h5p|quiz|assignment|download|external_link
weight	INT	Orden dentro de la lección	DEFAULT 0
duration_minutes	INT	Duración estimada	DEFAULT 5
content_body	TEXT	Contenido HTML (reading)	NULLABLE
video_url	VARCHAR(512)	URL video externo/CDN	NULLABLE
video_provider	VARCHAR(32)	Proveedor de video	ENUM: youtube|vimeo|bunny|self_hosted
h5p_content_id	INT	ID contenido H5P	FK h5p_content.id, NULLABLE
file_id	INT	Archivo descargable	FK file_managed.fid, NULLABLE
external_url	VARCHAR(512)	URL externa	NULLABLE
is_required	BOOLEAN	Obligatorio para completar lección	DEFAULT TRUE
xapi_verb	VARCHAR(64)	Verbo xAPI para tracking	DEFAULT 'completed'
passing_score	INT	Score mínimo para quizzes	RANGE 0-100, DEFAULT 70
max_attempts	INT	Intentos máximos (quiz)	DEFAULT 0 (ilimitado)
created	DATETIME	Fecha creación	NOT NULL, UTC
 
2.4 Entidad: enrollment
Registro de inscripción de un usuario a un curso. Gestiona acceso, fechas, y estado de la matrícula.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
user_id	INT	Usuario inscrito	FK users.uid, NOT NULL, INDEX
course_id	INT	Curso	FK course.id, NOT NULL, INDEX
tenant_id	INT	Tenant del enrollment	FK tenant.id, INDEX
enrollment_type	VARCHAR(32)	Tipo de inscripción	ENUM: free|paid|grant|scholarship|bulk
payment_id	VARCHAR(64)	ID transacción Stripe	NULLABLE, pi_*
grant_id	INT	Subvención asociada	FK grant.id, NULLABLE
status	VARCHAR(16)	Estado de la matrícula	ENUM: active|completed|expired|cancelled|suspended
enrolled_at	DATETIME	Fecha de inscripción	NOT NULL, UTC
started_at	DATETIME	Fecha primer acceso	NULLABLE, UTC
completed_at	DATETIME	Fecha de completitud	NULLABLE, UTC
expires_at	DATETIME	Fecha de expiración acceso	NULLABLE, UTC
progress_percent	DECIMAL(5,2)	Progreso calculado	RANGE 0-100, DEFAULT 0
last_activity_at	DATETIME	Última actividad	NULLABLE, UTC, INDEX
certificate_issued	BOOLEAN	Certificado emitido	DEFAULT FALSE
certificate_id	INT	ID del certificado	FK credential.id, NULLABLE
source	VARCHAR(32)	Fuente del enrollment	ENUM: organic|diagnostic|campaign|import|api
metadata	JSON	Datos adicionales	NULLABLE
Índice único compuesto:
UNIQUE INDEX (user_id, course_id) — Un usuario solo puede tener una matrícula activa por curso.
 
2.5 Entidad: progress_record
Registro granular de progreso por actividad. Implementa el estándar xAPI de forma simplificada.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
enrollment_id	INT	Matrícula asociada	FK enrollment.id, NOT NULL, INDEX
activity_id	INT	Actividad	FK activity.id, NOT NULL, INDEX
status	VARCHAR(16)	Estado de la actividad	ENUM: not_started|in_progress|completed|failed
score	DECIMAL(5,2)	Puntuación obtenida	RANGE 0-100, NULLABLE
attempts	INT	Número de intentos	DEFAULT 0
time_spent_seconds	INT	Tiempo total invertido	DEFAULT 0
first_access	DATETIME	Primer acceso	NULLABLE, UTC
last_access	DATETIME	Último acceso	NULLABLE, UTC
completed_at	DATETIME	Fecha completitud	NULLABLE, UTC
xapi_statement_id	UUID	ID statement xAPI	NULLABLE, para sync con LRS externo
response_data	JSON	Respuestas de quiz/H5P	NULLABLE
metadata	JSON	Datos adicionales	NULLABLE
Índice único compuesto:
UNIQUE INDEX (enrollment_id, activity_id) — Una entrada por actividad por matrícula.
2.6 Entidad: learning_path
Ruta de aprendizaje que agrupa cursos en una secuencia lógica. Se asigna automáticamente según el perfil del Diagnóstico Express.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
title	VARCHAR(255)	Título de la ruta	NOT NULL
machine_name	VARCHAR(64)	Identificador máquina	UNIQUE, NOT NULL
description	TEXT	Descripción de la ruta	NULLABLE
target_profile	VARCHAR(32)	Perfil del diagnóstico	ENUM: invisible|desconectado|construccion|competitivo|magnetico
target_gap	VARCHAR(32)	Gap principal a resolver	ENUM: linkedin|cv|search_strategy|all
courses	JSON	Array ordenado de course.id	NOT NULL, array
estimated_duration_hours	INT	Duración total estimada	Calculado
total_credits	INT	Créditos totales	Suma de cursos
is_active	BOOLEAN	Ruta activa	DEFAULT TRUE
tenant_id	INT	Tenant propietario	FK tenant.id, NULLABLE
created	DATETIME	Fecha creación	NOT NULL, UTC
changed	DATETIME	Última modificación	NOT NULL, UTC
 
3. APIs REST
El módulo jaraba_lms expone los siguientes endpoints RESTful. Todos requieren autenticación OAuth2 y respetan el contexto multi-tenant.
Método	Endpoint	Descripción
GET	/api/v1/courses	Listar cursos (filtros: vertical, tenant, published)
GET	/api/v1/courses/{id}	Detalle de curso con lecciones y actividades
POST	/api/v1/enrollments	Crear nueva matrícula (body: user_id, course_id, type)
GET	/api/v1/enrollments/{user_id}	Listar matrículas de un usuario
GET	/api/v1/progress/{enrollment_id}	Progreso detallado de una matrícula
POST	/api/v1/progress/{enrollment_id}/track	Registrar evento de progreso (activity_id, status, score)
GET	/api/v1/learning-paths	Listar rutas de aprendizaje disponibles
GET	/api/v1/learning-paths/recommend	Recomendar ruta según perfil diagnóstico (query: profile, gap)
POST	/api/v1/certificates/issue	Emitir certificado (body: enrollment_id)
GET	/api/v1/certificates/verify/{uuid}	Verificación pública de certificado
 
4. Flujos de Automatización (ECA)
Los siguientes flujos ECA automatizan el journey del usuario desde el Diagnóstico Express hasta la certificación.
4.1 ECA-LMS-001: Auto-Enrollment Post-Diagnóstico
Trigger: Usuario completa registro post-Diagnóstico Express
1.	Obtener profile_type y primary_gap del diagnóstico
2.	Buscar learning_path donde target_profile = profile_type AND target_gap = primary_gap
3.	Crear enrollment para el primer curso de la ruta (enrollment_type = 'diagnostic')
4.	Asignar créditos de impacto iniciales (+50)
5.	Enviar webhook a ActiveCampaign con tag 'lms_enrolled'
6.	Programar email de bienvenida al curso (delay: 1 hora)
4.2 ECA-LMS-002: Actualización de Progreso
Trigger: Inserción en progress_record con status = 'completed'
7.	Recalcular progress_percent del enrollment asociado
8.	Si progress_percent = 100: actualizar enrollment.status = 'completed'
9.	Si curso completado: disparar ECA-LMS-003 (Certificación)
10.	Actualizar health_score del usuario (+5 por cada 25% de progreso)
11.	Si es el primer curso completado: enviar email de felicitación
4.3 ECA-LMS-003: Emisión de Certificado
Trigger: Enrollment actualizado a status = 'completed'
12.	Verificar que certificate_issued = FALSE
13.	Generar credential (Open Badge 3.0) con datos del curso y usuario
14.	Actualizar enrollment: certificate_issued = TRUE, certificate_id = nuevo_id
15.	Asignar créditos de impacto del curso al usuario
16.	Enviar email con PDF del certificado y enlace de verificación
17.	Webhook a ActiveCampaign: tag 'certified_{course_machine_name}'
18.	Si hay siguiente curso en learning_path: crear enrollment automático
 
5. Integración H5P para Contenido Interactivo
H5P proporciona contenido interactivo rico (quizzes, videos interactivos, presentaciones) sin desarrollo custom.
5.1 Tipos de Contenido Requeridos
Tipo H5P	Uso en Empleabilidad	xAPI Verb
Course Presentation	Módulos teóricos con slides interactivos	completed, progressed
Interactive Video	Videos con checkpoints y preguntas	interacted, answered
Question Set	Evaluaciones de conocimiento	answered, scored
Branching Scenario	Simulaciones de entrevista de trabajo	completed, scored
Drag and Drop	Matching skills con requisitos de ofertas	answered, scored
Essay	Redacción de carta de presentación	answered (review manual)
5.2 Configuración xAPI
H5P emite statements xAPI que se capturan y almacenan en progress_record:
•	Endpoint LRS: /api/v1/xapi/statements (interno)
•	Actor: mbox:sha1sum del email del usuario
•	Object: activity:{base_url}/activity/{activity.uuid}
•	Context: registration: enrollment.uuid, tenant: tenant.machine_name
 
6. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 1	Semana 1-2	Módulo jaraba_lms: entidades course, lesson, activity. Migrations. Admin UI básico.	Core entities
Sprint 2	Semana 3-4	Entidades enrollment, progress_record, learning_path. APIs REST. Tests unitarios.	Sprint 1
Sprint 3	Semana 5-6	Integración H5P. xAPI endpoint. Player de contenido frontend.	Sprint 2 + H5P
Sprint 4	Semana 7-8	Flujos ECA completos. Integración ActiveCampaign. Auto-enrollment.	Sprint 3 + ECA
Sprint 5	Semana 9-10	Dashboard del estudiante. Progress tracking UI. Notificaciones.	Sprint 4
Sprint 6	Semana 11-12	Sistema de certificación. QA completo. Contenido piloto. Go-live.	Sprint 5 + Creds
— Fin del Documento —
08_Empleabilidad_LMS_Core_v1.docx | Jaraba Impact Platform | Enero 2026

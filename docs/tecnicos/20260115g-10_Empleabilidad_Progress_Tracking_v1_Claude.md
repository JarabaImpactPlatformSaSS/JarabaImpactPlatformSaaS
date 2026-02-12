
PROGRESS TRACKING
Sistema de Seguimiento de Progreso
xAPI, Gamificación y Analytics de Aprendizaje
Vertical de Empleabilidad Digital
JARABA IMPACT PLATFORM
Documento Técnico de Implementación
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	10_Empleabilidad_Progress_Tracking
Dependencias:	08_LMS_Core, 09_Learning_Paths
 
1. Resumen Ejecutivo
El sistema de Progress Tracking implementa seguimiento granular del aprendizaje utilizando el estándar xAPI (Experience API) para capturar todas las interacciones de los usuarios con el contenido formativo. Incluye un sistema de gamificación con puntos, niveles, rachas y logros para mantener la motivación.
1.1 Componentes del Sistema
Componente	Descripción	Estándar
xAPI Statements	Registro de todas las interacciones de aprendizaje	xAPI 1.0.3 (Tin Can)
Learning Record Store	Almacenamiento de statements xAPI	LRS compatible
Progress Engine	Cálculo de progreso y completitud	Custom
Gamification System	Puntos, niveles, rachas, logros	Custom
Analytics Dashboard	Visualización de progreso y engagement	Custom
1.2 Tipos de Eventos Tracked
•	Content Views: Visualización de lecciones, videos, documentos
•	Interactions: Respuestas a quizzes, ejercicios, simulaciones
•	Completions: Finalización de lecciones, módulos, cursos
•	Assessments: Resultados de evaluaciones y exámenes
•	Time Spent: Duración de sesiones de aprendizaje
 
2. Arquitectura xAPI
2.1 Estructura de Statement xAPI
{   "id": "uuid-statement-id",   "actor": {     "objectType": "Agent",     "account": {       "homePage": "https://jaraba.es",       "name": "user-uuid-123"     }   },   "verb": {     "id": "http://adlnet.gov/expapi/verbs/completed",     "display": { "es": "completó" }   },   "object": {     "objectType": "Activity",     "id": "https://jaraba.es/courses/linkedin-profile/lesson-3",     "definition": {       "type": "http://adlnet.gov/expapi/activities/lesson",       "name": { "es": "Optimización de Headline" },       "description": { "es": "Aprende a crear un headline efectivo" }     }   },   "result": {     "completion": true,     "success": true,     "score": { "scaled": 0.85, "raw": 85, "min": 0, "max": 100 },     "duration": "PT15M30S"   },   "context": {     "registration": "enrollment-uuid",     "contextActivities": {       "parent": [{ "id": "https://jaraba.es/courses/linkedin-profile" }],       "grouping": [{ "id": "https://jaraba.es/paths/digital-presence" }]     },     "extensions": {       "https://jaraba.es/xapi/tenant": "tenant-id",       "https://jaraba.es/xapi/device": "desktop"     }   },   "timestamp": "2026-01-15T10:30:00.000Z" }
 
2.2 Verbos xAPI Utilizados
Verbo	URI	Uso
launched	http://adlnet.gov/expapi/verbs/launched	Inicio de contenido
viewed	http://id.tincanapi.com/verb/viewed	Visualización de recurso
progressed	http://adlnet.gov/expapi/verbs/progressed	Avance en contenido
completed	http://adlnet.gov/expapi/verbs/completed	Finalización de unidad
passed	http://adlnet.gov/expapi/verbs/passed	Aprobación de evaluación
failed	http://adlnet.gov/expapi/verbs/failed	Suspensión de evaluación
answered	http://adlnet.gov/expapi/verbs/answered	Respuesta a pregunta
earned	http://id.tincanapi.com/verb/earned	Obtención de badge/crédito
terminated	http://adlnet.gov/expapi/verbs/terminated	Fin de sesión
 
3. Entidades de Datos
3.1 Entidad: xapi_statement
Almacenamiento local de statements xAPI (LRS interno):
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
statement_id	UUID	ID único del statement	UNIQUE, NOT NULL, INDEX
user_id	INT	Usuario actor	FK users.uid, NOT NULL, INDEX
verb_id	VARCHAR(255)	URI del verbo	NOT NULL, INDEX
object_type	VARCHAR(32)	Tipo de objeto	ENUM: Activity|Agent|StatementRef
object_id	VARCHAR(512)	URI del objeto	NOT NULL, INDEX
object_definition	JSON	Definición del objeto	NULLABLE
result_completion	BOOLEAN	Completado	NULLABLE
result_success	BOOLEAN	Exitoso	NULLABLE
result_score_scaled	DECIMAL(4,3)	Score normalizado	RANGE 0-1, NULLABLE
result_score_raw	DECIMAL(8,2)	Score bruto	NULLABLE
result_duration	VARCHAR(32)	Duración ISO 8601	NULLABLE
context_registration	UUID	ID de enrollment	INDEX
context_parent_id	VARCHAR(512)	Actividad padre	NULLABLE
context_extensions	JSON	Extensiones de contexto	NULLABLE
timestamp	DATETIME	Momento del evento	NOT NULL, INDEX
stored	DATETIME	Momento de almacenamiento	NOT NULL
tenant_id	INT	Tenant	FK tenant.id, INDEX
raw_statement	JSON	Statement completo	NOT NULL
3.2 Entidad: user_progress
Resumen agregado de progreso por usuario (materializado para rendimiento):
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
user_id	INT	Usuario	FK users.uid, NOT NULL
object_type	VARCHAR(32)	Tipo de objeto	ENUM: course|lesson|path|module
object_id	INT	ID del objeto	NOT NULL
progress_percent	INT	Porcentaje completado	RANGE 0-100
status	VARCHAR(16)	Estado	ENUM: not_started|in_progress|completed
started_at	DATETIME	Fecha de inicio	NULLABLE
completed_at	DATETIME	Fecha de completado	NULLABLE
last_activity_at	DATETIME	Última actividad	NOT NULL
total_time_spent	INT	Tiempo total (segundos)	DEFAULT 0
score_best	DECIMAL(5,2)	Mejor score	NULLABLE
score_latest	DECIMAL(5,2)	Último score	NULLABLE
attempts	INT	Intentos	DEFAULT 1
Índice único:
UNIQUE INDEX (user_id, object_type, object_id)
 
4. Sistema de Gamificación
4.1 Entidad: user_gamification
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
user_id	INT	Usuario	FK users.uid, UNIQUE, NOT NULL
total_xp	INT	Experiencia total	DEFAULT 0
current_level	INT	Nivel actual	DEFAULT 1
xp_to_next_level	INT	XP para siguiente nivel	Computed
current_streak	INT	Racha actual (días)	DEFAULT 0
longest_streak	INT	Racha más larga	DEFAULT 0
last_activity_date	DATE	Última actividad	Para calcular racha
weekly_xp	INT	XP esta semana	DEFAULT 0
monthly_xp	INT	XP este mes	DEFAULT 0
achievements_count	INT	Logros desbloqueados	DEFAULT 0
rank_position	INT	Posición en leaderboard	NULLABLE
4.2 Sistema de Niveles
Nivel	Nombre	XP Requerido	Beneficios
1	Explorador	0	Acceso básico
2	Aprendiz	100	Badge de nivel 2
3	Practicante	300	Acceso a recursos premium
4	Profesional	600	Visibilidad destacada
5	Experto	1000	Mentor en comunidad
6	Maestro	1500	Badge exclusivo + beneficios
7	Embajador	2500	Reconocimiento público
 
4.3 Acciones que Otorgan XP
Acción	XP	Condiciones
Completar lección	+10	Primera vez
Aprobar quiz	+20	Score >= 70%
Aprobar quiz con excelencia	+35	Score >= 90%
Completar curso	+100	Todas las lecciones + quiz final
Completar learning path	+250	Todos los cursos de la ruta
Mantener racha 7 días	+50	Bonus semanal
Mantener racha 30 días	+200	Bonus mensual
Obtener credencial	+150	Certificación oficial
Primera aplicación a empleo	+30	Milestone
Conseguir entrevista	+75	Resultado de aplicación
Ser contratado	+500	Objetivo final
 
5. Sistema de Logros (Achievements)
5.1 Entidad: achievement
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
machine_name	VARCHAR(64)	Identificador único	UNIQUE, NOT NULL
name	VARCHAR(128)	Nombre del logro	NOT NULL
description	VARCHAR(255)	Descripción	NOT NULL
icon	VARCHAR(64)	Icono (emoji o file)	NOT NULL
category	VARCHAR(32)	Categoría	ENUM: learning|engagement|career|social
rarity	VARCHAR(16)	Rareza	ENUM: common|uncommon|rare|epic|legendary
xp_reward	INT	XP otorgado	DEFAULT 50
trigger_type	VARCHAR(32)	Tipo de trigger	ENUM: count|threshold|event|date
trigger_conditions	JSON	Condiciones de activación	NOT NULL
is_secret	BOOLEAN	Logro oculto	DEFAULT FALSE
is_active	BOOLEAN	Activo	DEFAULT TRUE
5.2 Catálogo de Logros
Logro	Condición	Rareza	XP
🎯 Primer Paso	Completar primera lección	Common	+25
📚 Estudiante Dedicado	Completar 10 lecciones	Uncommon	+75
🏆 Graduado	Completar primer curso	Uncommon	+100
🔥 En Racha	7 días consecutivos de actividad	Uncommon	+50
💪 Imparable	30 días consecutivos	Rare	+200
🎓 Certificado	Obtener primera credencial	Rare	+150
💼 Aspirante	Enviar primera aplicación	Uncommon	+50
🤝 Entrevistado	Conseguir primera entrevista	Rare	+100
🌟 Contratado	Ser contratado a través del ecosistema	Epic	+500
👑 Maestro Jaraba	Completar programa Impulso Empleo	Legendary	+1000
 
6. APIs REST
Método	Endpoint	Descripción
POST	/api/v1/xapi/statements	Registrar statement xAPI
GET	/api/v1/xapi/statements	Consultar statements con filtros
GET	/api/v1/progress/my	Mi progreso general
GET	/api/v1/progress/courses/{id}	Progreso en un curso específico
GET	/api/v1/progress/paths/{id}	Progreso en una learning path
GET	/api/v1/gamification/my	Mi XP, nivel, racha
GET	/api/v1/gamification/leaderboard	Leaderboard (top 100)
GET	/api/v1/achievements/my	Mis logros desbloqueados
GET	/api/v1/achievements/available	Logros disponibles por desbloquear
7. Roadmap de Implementación
Sprint	Timeline	Entregables	Deps
Sprint 1	Semana 1-2	Entidades xapi_statement, user_progress. API de statements.	LMS Core
Sprint 2	Semana 3-4	Progress engine. Materialización de progreso. Frontend progress bars.	Sprint 1
Sprint 3	Semana 5-6	Gamification: XP, niveles, rachas. Entidad user_gamification.	Sprint 2
Sprint 4	Semana 7-8	Achievements system. Catálogo de logros. Trigger engine.	Sprint 3
Sprint 5	Semana 9-10	Leaderboard. Notificaciones de logros. Frontend UI. QA. Go-live.	Sprint 4
— Fin del Documento —
10_Empleabilidad_Progress_Tracking_v1.docx | Jaraba Impact Platform | Enero 2026

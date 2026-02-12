
LEARNING PATHS
Rutas de Aprendizaje Personalizadas
Vertical de Empleabilidad Digital
JARABA IMPACT PLATFORM
Documento Técnico de Implementación
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	09_Empleabilidad_Learning_Paths
Dependencias:	08_LMS_Core, Diagnóstico Express
 
1. Resumen Ejecutivo
Este documento especifica el sistema de Rutas de Aprendizaje (Learning Paths) que personaliza automáticamente el itinerario formativo de cada usuario basándose en los resultados del Diagnóstico Express. Las rutas conectan el gap identificado con los cursos específicos del LMS.
1.1 Concepto: Personalización Basada en Diagnóstico
El Diagnóstico Express clasifica al usuario en 5 perfiles con 4 posibles gaps principales. El sistema de Learning Paths utiliza esta matriz 5×4 para asignar automáticamente el itinerario óptimo:
Perfil	Gap Principal	Ruta Asignada	Duración Est.
Invisible	linkedin	path_linkedin_zero	12 horas
Invisible	cv	path_cv_complete	8 horas
Desconectado	search_strategy	path_job_search_basic	10 horas
En Construcción	linkedin	path_linkedin_optimize	6 horas
En Construcción	cv	path_cv_ats	4 horas
Competitivo	networking	path_networking_pro	8 horas
Magnético	personal_brand	path_thought_leader	15 horas
 
2. Arquitectura del Sistema
2.1 Entidad Extendida: learning_path
Extensión de la entidad base definida en 08_LMS_Core con campos adicionales para personalización:
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
title	VARCHAR(255)	Título de la ruta	NOT NULL
machine_name	VARCHAR(64)	Identificador máquina	UNIQUE, NOT NULL, INDEX
description	TEXT	Descripción de la ruta	NOT NULL
short_description	VARCHAR(300)	Resumen para cards	NOT NULL
thumbnail	INT	Imagen de portada	FK file_managed.fid
target_profile	VARCHAR(32)	Perfil diagnóstico target	ENUM: invisible|desconectado|construccion|competitivo|magnetico|all
target_gap	VARCHAR(32)	Gap principal target	ENUM: linkedin|cv|search_strategy|networking|personal_brand|all
difficulty_level	VARCHAR(16)	Nivel de dificultad	ENUM: beginner|intermediate|advanced
estimated_hours	DECIMAL(5,1)	Horas estimadas totales	Computed from courses
total_courses	INT	Número de cursos	Computed
total_credits	INT	Créditos de impacto	Suma de cursos
prerequisite_paths	JSON	Rutas prerequisito	Array of learning_path.id
outcomes	JSON	Resultados esperados	Array of strings
skills_gained	JSON	Skills que se obtienen	Array of skill.tid
is_certification_path	BOOLEAN	Otorga certificación final	DEFAULT FALSE
certification_id	INT	Certificación al completar	FK credential_template.id
priority	INT	Prioridad de recomendación	DEFAULT 0, higher = more priority
is_active	BOOLEAN	Ruta activa	DEFAULT TRUE
tenant_id	INT	Tenant propietario	FK tenant.id, NULL=global
created	DATETIME	Fecha creación	NOT NULL, UTC
changed	DATETIME	Última modificación	NOT NULL, UTC
 
2.2 Entidad: learning_path_course
Relación entre rutas y cursos con orden y configuración específica:
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
path_id	INT	Ruta de aprendizaje	FK learning_path.id, NOT NULL
course_id	INT	Curso	FK course.id, NOT NULL
weight	INT	Orden en la ruta	DEFAULT 0, INDEX
is_required	BOOLEAN	Obligatorio para completar	DEFAULT TRUE
is_milestone	BOOLEAN	Marca un hito importante	DEFAULT FALSE
milestone_name	VARCHAR(128)	Nombre del hito	NULLABLE
unlock_condition	VARCHAR(32)	Condición de desbloqueo	ENUM: immediate|previous_complete|date|manual
unlock_after_days	INT	Días desde enrollment	DEFAULT 0
unlock_date	DATE	Fecha específica	NULLABLE
bonus_credits	INT	Créditos extra en esta ruta	DEFAULT 0
2.3 Entidad: user_learning_path
Inscripción y progreso de un usuario en una ruta de aprendizaje:
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
user_id	INT	Usuario	FK users.uid, NOT NULL, INDEX
path_id	INT	Ruta de aprendizaje	FK learning_path.id, NOT NULL
diagnostic_id	INT	Diagnóstico que originó	FK diagnostic_express_result.id
status	VARCHAR(16)	Estado	ENUM: active|completed|paused|abandoned
enrolled_at	DATETIME	Fecha de inscripción	NOT NULL, UTC
started_at	DATETIME	Fecha primer acceso	NULLABLE, UTC
completed_at	DATETIME	Fecha completitud	NULLABLE, UTC
progress_percent	DECIMAL(5,2)	Progreso total	RANGE 0-100, computed
courses_completed	INT	Cursos completados	DEFAULT 0
current_course_id	INT	Curso actual	FK course.id, NULLABLE
credits_earned	INT	Créditos acumulados	DEFAULT 0
time_spent_minutes	INT	Tiempo total invertido	DEFAULT 0
last_activity_at	DATETIME	Última actividad	NULLABLE, UTC, INDEX
certification_issued	BOOLEAN	Certificación emitida	DEFAULT FALSE
certification_id	INT	ID certificación	FK credential.id, NULLABLE
source	VARCHAR(32)	Origen inscripción	ENUM: diagnostic|manual|recommendation|campaign
Índice único:
UNIQUE INDEX (user_id, path_id) — Un usuario solo puede estar inscrito una vez por ruta.
 
3. Algoritmo de Asignación Automática
El algoritmo selecciona la ruta óptima basándose en múltiples factores ponderados.
3.1 Factores de Decisión
Factor	Peso	Descripción
Profile Match	40%	Coincidencia exacta con target_profile del diagnóstico
Gap Match	35%	Coincidencia con primary_gap del diagnóstico
Difficulty Fit	10%	Adecuación al score total del diagnóstico (0-3: beginner, 4-6: intermediate, 7-10: advanced)
Time Availability	10%	Ajuste a disponibilidad indicada en onboarding
Priority Boost	5%	Campo priority de la ruta (promociones, nuevos cursos)
3.2 Pseudocódigo del Algoritmo
function assignLearningPath(diagnostic_result):
1.	Obtener todas las rutas activas (is_active = TRUE)
2.	Filtrar por tenant del usuario (tenant_id = user.tenant OR tenant_id IS NULL)
3.	Para cada ruta, calcular score de coincidencia:
score = (profile_match * 0.4) + (gap_match * 0.35) + (difficulty_fit * 0.1) + (time_fit * 0.1) + (priority * 0.05)
4.	Ordenar rutas por score descendente
5.	Verificar prerequisitos de la ruta top (si tiene, verificar que usuario los completó)
6.	Retornar la ruta con mejor score que cumpla prerequisitos
 
4. Catálogo de Rutas Predefinidas
El sistema incluye 8 rutas base que cubren la matriz completa de perfiles y gaps.
4.1 Ruta: LinkedIn desde Cero
machine_name	path_linkedin_zero
Target Profile	invisible
Target Gap	linkedin
Duración	12 horas (4 cursos)
Certificación	LinkedIn Profile Expert - Nivel Básico
Cursos	1. Introducción a LinkedIn | 2. Creación de Perfil Profesional | 3. Optimización para Reclutadores | 4. Primeros Pasos en Networking
4.2 Ruta: CV Profesional Completo
machine_name	path_cv_complete
Target Profile	invisible, desconectado
Target Gap	cv
Duración	8 horas (3 cursos)
Certificación	CV Writing Professional
Cursos	1. Anatomía de un CV Efectivo | 2. CV para Sistemas ATS | 3. Cartas de Presentación que Funcionan
4.3 Ruta: Estrategia de Búsqueda de Empleo
machine_name	path_job_search_basic
Target Profile	desconectado
Target Gap	search_strategy
Duración	10 horas (4 cursos)
Certificación	Job Search Strategist
Cursos	1. El Mercado Laboral Digital | 2. Portales de Empleo: Guía Completa | 3. Candidatura Espontánea | 4. Seguimiento y Persistencia
 
5. Flujos de Automatización (ECA)
5.1 ECA-LP-001: Asignación Post-Diagnóstico
Trigger: Usuario completa registro tras Diagnóstico Express
7.	Ejecutar algoritmo de asignación con diagnostic_result
8.	Crear registro user_learning_path con source='diagnostic'
9.	Crear enrollments para todos los cursos de la ruta
10.	Establecer current_course_id al primer curso
11.	Enviar email de bienvenida con resumen de la ruta
12.	Webhook ActiveCampaign: tag 'path_{machine_name}_enrolled'
5.2 ECA-LP-002: Progreso de Ruta
Trigger: Enrollment de curso cambia a status='completed'
13.	Verificar si el curso pertenece a una user_learning_path activa
14.	Incrementar courses_completed
15.	Recalcular progress_percent
16.	Actualizar current_course_id al siguiente curso
17.	Si es milestone: enviar notificación de celebración
18.	Si progress=100%: disparar ECA-LP-003
5.3 ECA-LP-003: Completitud de Ruta
Trigger: user_learning_path.progress_percent = 100
19.	Actualizar status = 'completed', completed_at = NOW()
20.	Si is_certification_path: emitir certificación final
21.	Asignar créditos de impacto bonus de la ruta
22.	Actualizar candidate_profile con nuevos skills
23.	Recomendar siguiente ruta según perfil actualizado
24.	Enviar email de felicitación con certificación y próximos pasos
 
6. APIs REST
Método	Endpoint	Descripción
GET	/api/v1/learning-paths	Listar rutas disponibles (filtros: profile, gap, difficulty)
GET	/api/v1/learning-paths/{id}	Detalle de ruta con cursos y requisitos
POST	/api/v1/learning-paths/recommend	Obtener recomendación basada en diagnóstico
POST	/api/v1/learning-paths/{id}/enroll	Inscribirse en una ruta
GET	/api/v1/my-paths	Mis rutas activas con progreso
GET	/api/v1/my-paths/{id}/progress	Progreso detallado de una ruta
POST	/api/v1/my-paths/{id}/pause	Pausar progreso en una ruta
POST	/api/v1/my-paths/{id}/resume	Reanudar ruta pausada
7. Roadmap de Implementación
Sprint	Timeline	Entregables	Deps
Sprint 1	Semana 1-2	Entidades learning_path extendida, learning_path_course. Migrations.	LMS Core
Sprint 2	Semana 3-4	Entidad user_learning_path. Algoritmo de asignación. Tests unitarios.	Sprint 1
Sprint 3	Semana 5-6	APIs REST. Flujos ECA de asignación y progreso.	Sprint 2
Sprint 4	Semana 7-8	Catálogo de 8 rutas predefinidas. Contenido de cursos inicial.	Sprint 3
Sprint 5	Semana 9-10	Frontend: visualización de rutas, progreso, milestones.	Sprint 4
— Fin del Documento —
09_Empleabilidad_Learning_Paths_v1.docx | Jaraba Impact Platform | Enero 2026

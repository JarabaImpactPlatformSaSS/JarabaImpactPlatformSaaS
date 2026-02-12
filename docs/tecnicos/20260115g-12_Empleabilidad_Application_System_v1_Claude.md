
APPLICATION SYSTEM
Sistema de Gestión de Candidaturas
Vertical de Empleabilidad Digital
JARABA IMPACT PLATFORM
Documento Técnico de Implementación
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	12_Empleabilidad_Application_System
Dependencias:	11_Job_Board_Core, 15_Candidate_Profile
 
1. Resumen Ejecutivo
Este documento especifica el sistema completo de gestión de candidaturas, incluyendo el flujo de aplicación del candidato, el pipeline ATS del empleador, y el sistema de comunicación bidireccional. El Application System es el corazón transaccional del Job Board.
1.1 Flujo de Alto Nivel
El ciclo de vida de una candidatura sigue 8 estados posibles en un pipeline configurable:
Estado	Actor	Descripción
applied	Candidato	Candidatura enviada, pendiente de revisión
screening	Empleador	En revisión inicial (CV, carta, perfil)
shortlisted	Empleador	Preseleccionado para siguiente fase
interviewed	Ambos	Entrevista realizada o programada
offered	Empleador	Oferta formal enviada al candidato
hired	Ambos	Oferta aceptada, contratación confirmada
rejected	Empleador	Candidatura descartada (con motivo)
withdrawn	Candidato	Candidato retira su aplicación
 
2. Arquitectura del Sistema
2.1 Entidad Extendida: job_application
Extensión de la entidad base con campos adicionales para el pipeline completo:
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
reference_code	VARCHAR(32)	Código visible	UNIQUE, AUTO: APP-{YEAR}-{SEQ}
job_id	INT	Oferta	FK job_posting.id, NOT NULL
candidate_id	INT	Candidato	FK users.uid, NOT NULL
candidate_profile_id	INT	Perfil al aplicar	FK candidate_profile.id
status	VARCHAR(32)	Estado actual	ENUM (8 estados)
previous_status	VARCHAR(32)	Estado anterior	Para tracking de cambios
cover_letter	TEXT	Carta de presentación	NULLABLE
cv_file_id	INT	CV adjunto específico	FK file_managed.fid
cv_snapshot	JSON	Snapshot del CV al aplicar	NOT NULL, immutable
portfolio_url	VARCHAR(512)	URL portfolio	NULLABLE
video_intro_url	VARCHAR(512)	Video presentación	NULLABLE
screening_answers	JSON	Respuestas screening	Array of {question_id, answer}
match_score	DECIMAL(5,2)	Score de matching	RANGE 0-100, computed
match_breakdown	JSON	Desglose del score	{skills, experience, location, salary}
source	VARCHAR(32)	Fuente	ENUM: organic|recommended|alert|import|api|referral
referral_code	VARCHAR(64)	Código de referido	NULLABLE
referral_user_id	INT	Usuario que refirió	FK users.uid, NULLABLE
is_favorite	BOOLEAN	Marcado favorito	DEFAULT FALSE (employer)
employer_notes	TEXT	Notas privadas	NULLABLE, only employer visible
employer_rating	INT	Rating interno	RANGE 1-5, NULLABLE
employer_tags	JSON	Tags del empleador	Array of strings
rejection_reason_id	INT	Motivo rechazo	FK taxonomy_term, NULLABLE
rejection_feedback	TEXT	Feedback al candidato	NULLABLE, visible to candidate
interview_type	VARCHAR(32)	Tipo de entrevista	ENUM: phone|video|onsite|panel|technical
interview_scheduled_at	DATETIME	Fecha/hora entrevista	NULLABLE, UTC
interview_location	VARCHAR(255)	Lugar/URL entrevista	NULLABLE
interview_notes	TEXT	Notas de entrevista	NULLABLE
interview_score	INT	Puntuación entrevista	RANGE 1-10, NULLABLE
offered_position	VARCHAR(255)	Puesto ofrecido	NULLABLE (puede diferir)
offered_salary	DECIMAL(10,2)	Salario ofrecido	NULLABLE
offered_start_date	DATE	Fecha inicio propuesta	NULLABLE
offer_expires_at	DATETIME	Expiración oferta	NULLABLE
offer_accepted_at	DATETIME	Fecha aceptación	NULLABLE
hired_at	DATETIME	Fecha contratación	NULLABLE, UTC
hired_salary	DECIMAL(10,2)	Salario final	NULLABLE
applied_at	DATETIME	Fecha aplicación	NOT NULL, UTC
first_viewed_at	DATETIME	Primera vista employer	NULLABLE
last_status_change_at	DATETIME	Último cambio estado	NOT NULL, UTC, INDEX
last_employer_action_at	DATETIME	Última acción employer	NULLABLE
last_candidate_action_at	DATETIME	Última acción candidate	NULLABLE
 
2.2 Entidad: application_activity
Log de actividad inmutable para auditoría y timeline de la candidatura:
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
application_id	INT	Candidatura	FK job_application.id, NOT NULL
activity_type	VARCHAR(32)	Tipo de actividad	ENUM: status_change|note_added|file_uploaded|interview_scheduled|message_sent|viewed|rating_changed
actor_id	INT	Usuario que realizó	FK users.uid, NOT NULL
actor_role	VARCHAR(16)	Rol del actor	ENUM: candidate|employer|system
old_value	JSON	Valor anterior	NULLABLE
new_value	JSON	Valor nuevo	NULLABLE
description	VARCHAR(500)	Descripción legible	NOT NULL
is_visible_to_candidate	BOOLEAN	Visible al candidato	DEFAULT FALSE
created_at	DATETIME	Timestamp	NOT NULL, UTC, INDEX
2.3 Entidad: screening_question
Preguntas de filtrado que el empleador puede añadir a sus ofertas:
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
job_id	INT	Oferta asociada	FK job_posting.id, NOT NULL
question_text	VARCHAR(500)	Pregunta	NOT NULL
question_type	VARCHAR(16)	Tipo de respuesta	ENUM: text|textarea|select|multiselect|boolean|number
options	JSON	Opciones si select	Array of strings, NULLABLE
is_required	BOOLEAN	Obligatoria	DEFAULT TRUE
is_knockout	BOOLEAN	Respuesta incorrecta descarta	DEFAULT FALSE
knockout_answer	VARCHAR(255)	Respuesta que descarta	NULLABLE
weight	INT	Orden	DEFAULT 0
ideal_answer	VARCHAR(255)	Respuesta ideal	NULLABLE, para scoring
 
3. Proceso de Aplicación (Candidato)
3.1 Flujo de Aplicación
1.	Visualización de oferta: Candidato revisa descripción, requisitos, salario
2.	Pre-check de elegibilidad: Sistema verifica si ya aplicó, si cumple requisitos básicos
3.	Selección de CV: Candidato elige CV existente o sube nuevo documento
4.	Carta de presentación: Opcional: escribe o selecciona carta predefinida
5.	Screening questions: Responde preguntas de filtrado si las hay
6.	Revisión final: Preview de la candidatura antes de enviar
7.	Envío: Confirmación y creación del registro
3.2 Generación de Match Score
Al crear la aplicación, el sistema calcula automáticamente el match score:
Factor	Peso	Cálculo
Skills Match	35%	(Skills requeridos coincidentes / Total requeridos) × 100
Experience Fit	25%	Ajuste entre experience_level de oferta y años experiencia candidato
Location Match	15%	100 si misma ciudad, 80 si misma provincia, 60 si remoto OK
Salary Alignment	15%	Solapamiento entre rango ofertado y expectativas candidato
Education Fit	10%	Nivel educativo igual o superior al requerido
 
4. Pipeline ATS (Empleador)
4.1 Vista Kanban
El empleador gestiona candidaturas en un tablero Kanban con las siguientes columnas:
Columna	Estados incluidos	Acciones disponibles
Nuevas	applied	Ver CV, Mover a screening, Rechazar rápido
En revisión	screening	Añadir notas, Rating, Preseleccionar, Rechazar
Preselección	shortlisted	Programar entrevista, Contactar, Rechazar
Entrevistas	interviewed	Añadir feedback, Puntuar, Hacer oferta, Rechazar
Ofertas	offered	Modificar oferta, Marcar aceptada, Retirar oferta
Contratados	hired	Ver detalles, Exportar datos
Descartados	rejected, withdrawn	Reactivar (si withdrawn), Ver historial
4.2 Acciones Masivas
El empleador puede realizar acciones sobre múltiples candidaturas:
•	Bulk Status Change: Mover múltiples candidaturas al mismo estado
•	Bulk Reject: Rechazar con motivo común y email automatizado
•	Bulk Tag: Añadir etiquetas para organización
•	Export: Exportar selección a CSV/Excel
 
5. Sistema de Comunicación
5.1 Notificaciones al Candidato
Evento	Canal	Timing
Aplicación enviada	Email + In-app	Inmediato
CV visto por empleador	In-app	Inmediato
Cambio a shortlisted	Email + In-app	Inmediato
Entrevista programada	Email + In-app + Calendar	Inmediato + Reminder -24h
Oferta recibida	Email + In-app + SMS	Inmediato
Rechazo	Email	Inmediato (personalizable)
Sin actividad (stale)	Email	Después de 14 días sin cambios
5.2 Notificaciones al Empleador
Evento	Canal	Configuración
Nueva aplicación	Email + Dashboard	Inmediato o digest diario
Aplicación high-match (>80%)	Email prioritario	Siempre inmediato
Candidato retira aplicación	Dashboard	Inmediato
Oferta aceptada	Email + Dashboard	Inmediato
Oferta rechazada	Email + Dashboard	Inmediato
Oferta expirando	Email	48h antes de expiración
 
6. Flujos de Automatización (ECA)
6.1 ECA-APP-001: Nueva Aplicación
Trigger: Inserción en job_application
8.	Generar cv_snapshot desde candidate_profile actual
9.	Calcular match_score y match_breakdown
10.	Procesar screening_answers: verificar knockout questions
11.	Si knockout: auto-reject con motivo 'No cumple requisitos mínimos'
12.	Incrementar job_posting.applications_count
13.	Crear application_activity (type: status_change, applied)
14.	Enviar confirmación email al candidato
15.	Notificar al empleador según preferencias
16.	Asignar +20 créditos de impacto al candidato
6.2 ECA-APP-002: Cambio de Estado
Trigger: Update en job_application.status
17.	Guardar previous_status, actualizar last_status_change_at
18.	Crear application_activity con old_value y new_value
19.	Ejecutar acciones específicas según nuevo estado:
• shortlisted: Email celebración al candidato
• interviewed: Enviar calendar invite si fecha programada
• offered: Generar documento de oferta, email formal
• hired: Registrar contratación, actualizar métricas
• rejected: Email con feedback si existe
20.	Webhook ActiveCampaign con nuevo tag de estado
6.3 ECA-APP-003: Contratación Exitosa
Trigger: job_application.status = 'hired'
21.	Actualizar hired_at, hired_salary
22.	Incrementar employer_profile.total_hires
23.	Calcular time_to_hire para la oferta
24.	Registrar placement en métricas de impacto
25.	Si candidato es del ecosistema (LMS graduate): +500 créditos impacto
26.	Actualizar candidate_profile.job_search_status = 'not_looking'
27.	Programar encuesta NPS post-placement (+30 días)
 
7. APIs REST
Método	Endpoint	Descripción
POST	/api/v1/jobs/{id}/apply	Crear nueva aplicación
GET	/api/v1/applications/my	Mis candidaturas (candidato)
GET	/api/v1/applications/{id}	Detalle de candidatura
POST	/api/v1/applications/{id}/withdraw	Retirar candidatura (candidato)
GET	/api/v1/jobs/{id}/applications	Candidaturas de una oferta (employer)
PATCH	/api/v1/applications/{id}/status	Cambiar estado (employer)
POST	/api/v1/applications/{id}/interview	Programar entrevista
POST	/api/v1/applications/{id}/offer	Enviar oferta formal
POST	/api/v1/applications/{id}/reject	Rechazar con motivo y feedback
POST	/api/v1/applications/bulk/status	Cambio masivo de estado
GET	/api/v1/applications/{id}/timeline	Timeline de actividad
8. Roadmap de Implementación
Sprint	Timeline	Entregables	Deps
Sprint 1	Semana 1-2	Entidades extendidas. application_activity, screening_question.	Job Board
Sprint 2	Semana 3-4	Flujo de aplicación candidato. Match score algorithm.	Sprint 1
Sprint 3	Semana 5-6	Pipeline ATS. Vista Kanban. Acciones masivas.	Sprint 2
Sprint 4	Semana 7-8	Sistema de notificaciones. Email templates. Calendar integration.	Sprint 3
Sprint 5	Semana 9-10	Flujos ECA completos. QA. Go-live.	Sprint 4
— Fin del Documento —
12_Empleabilidad_Application_System_v1.docx | Jaraba Impact Platform | Enero 2026

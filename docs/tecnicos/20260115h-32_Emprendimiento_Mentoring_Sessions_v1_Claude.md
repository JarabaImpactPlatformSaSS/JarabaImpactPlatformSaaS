GESTIÓN DE SESIONES DE MENTORÍA
Mentoring Sessions
Vertical de Emprendimiento Digital
JARABA IMPACT PLATFORM
Documento Técnico de Implementación

Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	32_Emprendimiento_Mentoring_Sessions
Dependencias:	31_Mentoring_Core, 06_Core_Flujos_ECA, Jitsi/Zoom API
 
1. Resumen Ejecutivo
Este documento especifica la arquitectura técnica del Sistema de Gestión de Sesiones de Mentoría. El sistema cubre todo el ciclo de vida de una sesión: desde la reserva hasta la evaluación, incluyendo videollamadas integradas, notas de sesión, tareas de seguimiento y sistema de calificación bidireccional.
1.1 Objetivos del Sistema
•	Reserva fluida: Selección de slot disponible con confirmación instantánea
•	Recordatorios automáticos: Notificaciones 24h, 1h y 15min antes de la sesión
•	Videollamadas integradas: Jitsi Meet embebido o Zoom con generación automática de enlace
•	Notas estructuradas: Templates de notas por tipo de sesión con campos predefinidos
•	Tareas post-sesión: Asignación de action items con seguimiento
•	Evaluación bidireccional: Rating y feedback de mentor a mentee y viceversa
1.2 Stack Tecnológico
Componente	Tecnología
Core CMS	Drupal 11 con módulo jaraba_sessions custom
Calendario UI	FullCalendar.js con slots seleccionables
Videollamadas	Jitsi Meet (Docker self-hosted) + Zoom API (fallback)
Notificaciones	Queue + Cron con scheduling preciso
Email	SMTP + templates Twig responsive
Push	Firebase Cloud Messaging
WhatsApp	WhatsApp Business API (opcional)
 
2. Arquitectura de Entidades
2.1 Entidad: mentoring_session
Representa una sesión individual de mentoría programada o completada.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
engagement_id	INT	Engagement padre	FK mentoring_engagement.id, NOT NULL
mentor_id	INT	Mentor de la sesión	FK mentor_profile.id, NOT NULL
mentee_id	INT	Emprendedor	FK users.uid, NOT NULL
session_number	INT	Número de sesión en el engagement	NOT NULL, >= 1
scheduled_start	DATETIME	Inicio programado	NOT NULL, UTC
scheduled_end	DATETIME	Fin programado	NOT NULL, UTC
actual_start	DATETIME	Inicio real	NULLABLE
actual_end	DATETIME	Fin real	NULLABLE
duration_minutes	INT	Duración planificada	NOT NULL
actual_duration_minutes	INT	Duración real	COMPUTED
timezone	VARCHAR(64)	Zona horaria	DEFAULT 'Europe/Madrid'
session_type	VARCHAR(24)	Tipo de sesión	ENUM: initial|followup|review|emergency
meeting_url	VARCHAR(500)	URL de la videollamada	NULLABLE
meeting_provider	VARCHAR(24)	Proveedor de video	ENUM: jitsi|zoom|google_meet|in_person
meeting_room_id	VARCHAR(128)	ID de sala Jitsi/Zoom	NULLABLE
agenda	TEXT	Agenda previa de la sesión	NULLABLE
status	VARCHAR(16)	Estado	ENUM: scheduled|confirmed|in_progress|completed|cancelled|no_show
cancelled_by	VARCHAR(16)	Quién canceló	ENUM: mentor|mentee|system, NULLABLE
cancellation_reason	VARCHAR(500)	Razón de cancelación	NULLABLE
reminder_24h_sent	BOOLEAN	Recordatorio 24h enviado	DEFAULT FALSE
reminder_1h_sent	BOOLEAN	Recordatorio 1h enviado	DEFAULT FALSE
reminder_15min_sent	BOOLEAN	Recordatorio 15min enviado	DEFAULT FALSE
created	DATETIME	Creación	NOT NULL
changed	DATETIME	Modificación	NOT NULL
 
2.2 Entidad: session_notes
Almacena las notas estructuradas de cada sesión.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
session_id	INT	Sesión asociada	FK mentoring_session.id, UNIQUE
created_by	INT	Autor de las notas	FK users.uid, NOT NULL
template_type	VARCHAR(32)	Plantilla utilizada	ENUM: general|diagnostic|action_plan|review|custom
topics_discussed	JSON	Temas tratados (array)	NOT NULL
key_insights	TEXT	Insights principales	NULLABLE
challenges_identified	TEXT	Retos identificados	NULLABLE
progress_made	TEXT	Progreso realizado	NULLABLE
decisions_made	JSON	Decisiones tomadas	NULLABLE
resources_shared	JSON	Recursos compartidos (URLs)	NULLABLE
mentor_observations	TEXT	Observaciones del mentor	NULLABLE
mentee_feedback	TEXT	Feedback del mentee	NULLABLE
next_session_focus	TEXT	Foco para próxima sesión	NULLABLE
is_shared_with_mentee	BOOLEAN	Visible para el mentee	DEFAULT TRUE
created	DATETIME	Creación	NOT NULL
changed	DATETIME	Modificación	NOT NULL
2.3 Entidad: session_task
Tareas asignadas durante la sesión para seguimiento posterior.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
session_id	INT	Sesión origen	FK mentoring_session.id, NOT NULL
engagement_id	INT	Engagement asociado	FK mentoring_engagement.id, NOT NULL
assigned_to	VARCHAR(16)	Asignada a	ENUM: mentor|mentee|both
title	VARCHAR(255)	Título de la tarea	NOT NULL
description	TEXT	Descripción detallada	NULLABLE
due_date	DATE	Fecha límite	NULLABLE
priority	VARCHAR(16)	Prioridad	ENUM: low|medium|high|urgent
status	VARCHAR(16)	Estado	ENUM: pending|in_progress|completed|skipped
completed_at	DATETIME	Fecha de completitud	NULLABLE
completion_notes	TEXT	Notas al completar	NULLABLE
reviewed_in_session_id	INT	Sesión donde se revisó	FK mentoring_session.id, NULLABLE
created	DATETIME	Creación	NOT NULL
 
2.4 Entidad: session_review
Evaluación bidireccional post-sesión entre mentor y mentee.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
session_id	INT	Sesión evaluada	FK mentoring_session.id, NOT NULL
reviewer_id	INT	Quien evalúa	FK users.uid, NOT NULL
reviewee_id	INT	Quien es evaluado	FK users.uid, NOT NULL
review_type	VARCHAR(16)	Tipo de review	ENUM: mentor_to_mentee|mentee_to_mentor
overall_rating	INT	Puntuación general	NOT NULL, RANGE 1-5
punctuality_rating	INT	Puntualidad	RANGE 1-5, NULLABLE
preparation_rating	INT	Preparación	RANGE 1-5, NULLABLE
communication_rating	INT	Comunicación	RANGE 1-5, NULLABLE
value_delivered_rating	INT	Valor aportado	RANGE 1-5, NULLABLE
comment	TEXT	Comentario público	NULLABLE
private_feedback	TEXT	Feedback privado (solo admin)	NULLABLE
would_recommend	BOOLEAN	Recomendaría	NULLABLE
is_published	BOOLEAN	Visible públicamente	DEFAULT TRUE
created	DATETIME	Creación	NOT NULL
 
3. Flujo de Reserva de Sesiones
3.1 Proceso de Reserva
1.	Selección de slot: Mentee ve calendario con slots disponibles del mentor
2.	Verificación de saldo: Sistema verifica que engagement tiene sessions_remaining > 0
3.	Bloqueo temporal: Slot se bloquea 5 minutos mientras se confirma
4.	Agenda opcional: Mentee puede añadir agenda/objetivos de la sesión
5.	Confirmación: Sesión creada con status = 'scheduled'
6.	Notificaciones: Email a ambos + evento calendario (.ics)
7.	Generación de sala: Se crea room de Jitsi o Zoom meeting
3.2 Políticas de Cancelación
Tiempo antes	Cancelación por Mentee	Cancelación por Mentor
> 24 horas	Sesión devuelta al saldo	Sesión devuelta + notificación
2-24 horas	Sesión devuelta con warning	Sesión devuelta + penalización rating
< 2 horas	Sesión perdida (contabiliza)	Sesión devuelta + crédito compensación
No-show	Sesión perdida	Sesión devuelta + crédito + review negativa auto
3.3 Reprogramación
•	Máximo 2 reprogramaciones por sesión
•	Debe ser con > 24h de anticipación
•	Nueva fecha dentro de los 14 días siguientes
•	Requiere aceptación del otro participante
 
4. Integración de Videollamadas
4.1 Jitsi Meet (Opción Principal)
Jitsi Meet self-hosted para máximo control y sin costes por usuario:
Característica	Configuración
Despliegue	Docker Compose en servidor dedicado
Autenticación	JWT tokens generados por Drupal
Branding	Logo y colores Jaraba personalizados
Grabación	Opcional, con consentimiento previo
Embebido	iFrame en la plataforma o link directo
4.1.1 Generación de Sala
function generateJitsiRoom(session):   roomId = 'jaraba-' + session.uuid   jwt = generateJWT({     room: roomId,     moderator: session.mentor_id,     exp: session.scheduled_end + 30min   })   return {     url: JITSI_SERVER + '/' + roomId + '?jwt=' + jwt,     room_id: roomId   }
4.2 Zoom API (Fallback)
Integración con Zoom para mentores que prefieran esta opción:
•	Mentor conecta cuenta Zoom vía OAuth
•	Sistema crea meeting automáticamente al confirmar sesión
•	Join URL se almacena en session.meeting_url
•	Webhook de Zoom notifica inicio/fin de meeting
4.3 Experiencia de Usuario
Momento	Acción	UI
15 min antes	Botón 'Unirse' aparece activo	Contador regresivo visible
Hora de inicio	Auto-redirect a sala (opcional)	Notificación push
Durante sesión	Timer visible con duración	Botón de notas rápidas
5 min restantes	Alerta de tiempo	Toast notification
Fin de sesión	Prompt de notas y review	Modal de cierre
 
5. Sistema de Notas de Sesión
5.1 Templates de Notas
Template	Uso	Secciones Incluidas
general	Sesiones estándar de seguimiento	Temas, Insights, Tareas, Próximos pasos
diagnostic	Primera sesión / Diagnóstico inicial	Situación actual, Objetivos, Retos, Plan propuesto
action_plan	Sesión de planificación	Revisión anterior, Nuevo plan, Recursos, Métricas
review	Sesión de cierre/evaluación	Logros, Aprendizajes, Recomendaciones futuras
custom	Formato libre	Editor WYSIWYG libre
5.2 Flujo de Notas
8.	Durante la sesión: mentor puede tomar notas rápidas (guardado automático)
9.	Post-sesión: 24h para completar notas estructuradas
10.	Compartir: mentor decide qué secciones son visibles para mentee
11.	Notificación: mentee recibe email cuando las notas están listas
12.	Exportación: ambos pueden exportar a PDF
 
6. Flujos de Automatización (ECA)
6.1 ECA-SES-001: Reserva Confirmada
Trigger: mentoring_session creada con status = 'scheduled'
13.	Decrementar engagement.sessions_remaining
14.	Generar sala de videollamada (Jitsi/Zoom)
15.	Enviar email confirmación a mentor con .ics
16.	Enviar email confirmación a mentee con .ics
17.	Programar recordatorios en cola (24h, 1h, 15min)
6.2 ECA-SES-002: Recordatorios
Trigger: Cron cada 5 minutos
18.	Buscar sesiones donde scheduled_start - NOW() < 24h AND !reminder_24h_sent
19.	Enviar email + push notification a ambos participantes
20.	Marcar reminder_24h_sent = TRUE
21.	Repetir lógica para 1h y 15min
6.3 ECA-SES-003: Sesión Completada
Trigger: mentoring_session.status cambia a 'completed'
22.	Incrementar engagement.sessions_used
23.	Incrementar mentor_profile.total_sessions
24.	Crear session_notes vacías con template por defecto
25.	Enviar solicitud de review a mentee (delay 2h)
26.	Enviar recordatorio de notas a mentor (delay 24h si no completadas)
6.4 ECA-SES-004: No-Show Detection
Trigger: scheduled_end + 15min AND status = 'scheduled' (no marcada como started)
27.	Cambiar status a 'no_show'
28.	Solicitar a mentor que indique quién no asistió
29.	Aplicar política de no-show según el caso
 
7. APIs REST
Método	Endpoint	Descripción
GET	/api/v1/sessions	Listar mis sesiones (filtros: status, date_range)
GET	/api/v1/sessions/{id}	Detalle de sesión con notas y tareas
POST	/api/v1/sessions	Reservar nueva sesión (engagement_id, slot)
PATCH	/api/v1/sessions/{id}	Actualizar sesión (agenda, status)
POST	/api/v1/sessions/{id}/cancel	Cancelar sesión con razón
POST	/api/v1/sessions/{id}/reschedule	Reprogramar sesión (nuevo slot)
POST	/api/v1/sessions/{id}/start	Marcar inicio de sesión
POST	/api/v1/sessions/{id}/complete	Marcar fin de sesión
GET	/api/v1/sessions/{id}/join-url	Obtener URL de videollamada
GET	/api/v1/sessions/{id}/notes	Obtener notas de la sesión
POST	/api/v1/sessions/{id}/notes	Crear/actualizar notas
GET	/api/v1/sessions/{id}/tasks	Listar tareas de la sesión
POST	/api/v1/sessions/{id}/tasks	Crear tarea
PATCH	/api/v1/tasks/{id}	Actualizar tarea (status)
POST	/api/v1/sessions/{id}/review	Enviar review de la sesión
GET	/api/v1/sessions/upcoming	Próximas sesiones (7 días)
 
8. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 1	Semana 1-2	Entidad mentoring_session. Flujo de reserva básico.	31_Mentoring_Core
Sprint 2	Semana 3-4	Integración Jitsi Meet. Generación de salas.	Sprint 1
Sprint 3	Semana 5-6	Sistema de notas. Templates. session_notes entity.	Sprint 2
Sprint 4	Semana 7-8	Tareas post-sesión. Reviews bidireccionales.	Sprint 3
Sprint 5	Semana 9-10	Notificaciones completas. Políticas cancelación. QA.	Sprint 4
8.1 KPIs de Éxito
KPI	Target	Medición
Tasa de asistencia	> 90%	% sesiones completadas vs. programadas
Notas completadas	> 80%	% sesiones con notas en 48h
Reviews enviadas	> 70%	% sesiones con review del mentee
Rating medio	> 4.3/5	Promedio de overall_rating
Tiempo hasta reserva	< 48h	Media desde activación hasta primera sesión
--- Fin del Documento ---
32_Emprendimiento_Mentoring_Sessions_v1.docx | Jaraba Impact Platform | Enero 2026

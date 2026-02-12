SISTEMA DE EVENTOS Y NETWORKING
Networking Events
Vertical de Emprendimiento Digital
JARABA IMPACT PLATFORM

Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	35_Emprendimiento_Networking_Events
Dependencias:	34_Collaboration_Groups, 31_Mentoring_Core
 
1. Resumen Ejecutivo
El Sistema de Eventos y Networking facilita la conexión entre emprendedores, mentores, inversores y stakeholders del ecosistema. Gestiona eventos virtuales y presenciales, matchmaking inteligente entre asistentes, y seguimiento post-evento para maximizar el valor de cada conexión.
1.1 Tipos de Eventos
Tipo	Formato	Capacidad	Objetivo
Webinar Formativo	Online	100-500	Formación masiva sobre temas específicos
Workshop Práctico	Online/Híbrido	20-50	Trabajo práctico con ejercicios
Networking Session	Online	30-100	Conexiones rápidas entre emprendedores
Pitch Day	Presencial/Híbrido	50-200	Presentaciones de proyectos a inversores
Meetup Local	Presencial	15-40	Networking informal por territorio
Demo Day	Presencial	100-300	Showcase de proyectos de cohorte
Masterclass	Online	50-200	Sesión con experto invitado
Speed Networking	Online/Presencial	20-60	Rotaciones rápidas 1:1
1.2 Stack Tecnológico
Componente	Tecnología
Gestión eventos	Custom entity networking_event en Drupal 11
Calendario	FullCalendar.js con vista de eventos
Video online	Jitsi Meet (self-hosted) + Zoom API
Matchmaking	Algoritmo PHP basado en perfil y objetivos
Notificaciones	Email + Push + WhatsApp Business API
CRM sync	Integración ActiveCampaign vía Make.com
 
2. Arquitectura de Datos
2.1 Entidad: networking_event
Campo	Tipo	Descripción
id	Serial	PRIMARY KEY
title	VARCHAR(255)	Título del evento
description	TEXT	Descripción completa
event_type	VARCHAR(24)	ENUM: webinar|workshop|networking|pitch_day|meetup|demo_day|masterclass|speed_networking
format	VARCHAR(16)	ENUM: online|presencial|hybrid
start_datetime	DATETIME	Fecha y hora de inicio
end_datetime	DATETIME	Fecha y hora de fin
timezone	VARCHAR(64)	Zona horaria (Europe/Madrid)
location_name	VARCHAR(255)	Nombre del lugar (si presencial)
location_address	TEXT	Dirección completa
location_coordinates	JSON	{lat, lng} para mapa
meeting_url	VARCHAR(500)	URL de videollamada (si online)
meeting_provider	VARCHAR(16)	ENUM: jitsi|zoom|google_meet
organizer_id	INT	FK users.uid
group_id	INT	FK groups.id (si evento de grupo)
tenant_id	INT	FK tenant.id
max_attendees	INT	Capacidad máxima
current_attendees	INT	Inscritos actuales
waitlist_count	INT	Personas en lista de espera
is_free	BOOLEAN	TRUE si gratuito
price	DECIMAL(8,2)	Precio si de pago €
requires_approval	BOOLEAN	TRUE si inscripción requiere aprobación
target_sectors	JSON	Sectores objetivo del evento
target_phases	JSON	Fases de negocio objetivo
speaker_ids	JSON	Array de user IDs de ponentes
agenda	JSON	Agenda estructurada [{time, title, speaker}]
materials	JSON	Recursos adjuntos [{title, file_id}]
matchmaking_enabled	BOOLEAN	TRUE si activa matchmaking
status	VARCHAR(16)	ENUM: draft|published|ongoing|completed|cancelled
recording_url	VARCHAR(500)	URL de grabación post-evento
created	DATETIME	Timestamp creación
 
2.2 Entidad: event_registration
Campo	Tipo	Descripción
id	Serial	PRIMARY KEY
event_id	INT	FK networking_event.id
user_id	INT	FK users.uid
registration_status	VARCHAR(16)	ENUM: pending|confirmed|waitlist|cancelled|attended|no_show
registered_at	DATETIME	Fecha de inscripción
confirmed_at	DATETIME	Fecha de confirmación
attended_at	DATETIME	Check-in timestamp
payment_status	VARCHAR(16)	ENUM: not_required|pending|paid|refunded
payment_intent_id	VARCHAR(64)	Stripe PaymentIntent ID
networking_interests	TEXT	Qué busca en el evento
networking_offers	TEXT	Qué puede ofrecer
matchmaking_consent	BOOLEAN	Acepta matchmaking
reminder_24h_sent	BOOLEAN	Recordatorio enviado
reminder_1h_sent	BOOLEAN	Recordatorio 1h enviado
post_event_survey_sent	BOOLEAN	Encuesta enviada
nps_score	INT	NPS del evento (1-10)
feedback	TEXT	Comentarios del asistente
2.3 Entidad: event_connection
Conexiones generadas durante eventos de networking:
Campo	Tipo	Descripción
id	Serial	PRIMARY KEY
event_id	INT	FK networking_event.id
user_a_id	INT	FK users.uid (solicitante)
user_b_id	INT	FK users.uid (receptor)
connection_type	VARCHAR(16)	ENUM: matched|requested|accepted|declined
match_score	INT	Score de compatibilidad (0-100)
match_reasons	JSON	Razones del match
meeting_scheduled	BOOLEAN	Si programaron reunión
meeting_datetime	DATETIME	Fecha de reunión follow-up
outcome	VARCHAR(24)	ENUM: pending|collaboration|client|no_fit
created	DATETIME	Timestamp
 
3. Sistema de Matchmaking
Algoritmo inteligente para conectar asistentes con intereses complementarios.
3.1 Factores de Matching
Factor	Peso	Descripción
Complementariedad	30%	Uno busca lo que otro ofrece
Sector compatible	25%	Mismo sector o sectores relacionados
Fase de negocio	15%	Fases complementarias (mentor-mentee match)
Territorio	15%	Proximidad geográfica para colaboración local
Historial	10%	No repetir conexiones de eventos anteriores
Intereses declarados	5%	Keywords en networking_interests
3.2 Flujo de Speed Networking
1.	Pre-evento: Asistentes completan interests/offers en inscripción
2.	Algoritmo genera pares óptimos basado en scoring
3.	Durante evento: Rotaciones de 5-7 minutos en breakout rooms
4.	Cada participante marca 'Me interesa conectar' o 'Skip'
5.	Match mutuo: Sistema crea event_connection y notifica
6.	Post-evento: Facilita scheduling de reunión follow-up
 
4. Automatización ECA
4.1 ECA-EVT-001: Inscripción Confirmada
7.	Trigger: event_registration.registration_status = 'confirmed'
8.	Incrementar current_attendees del evento
9.	Enviar email de confirmación con .ics adjunto
10.	Si matchmaking_enabled: solicitar interests/offers
11.	Programar recordatorios (24h, 1h antes)
12.	Sync con CRM (tag: evento_X_inscrito)
4.2 ECA-EVT-002: Post-Evento
13.	Trigger: evento.end_datetime + 2 horas
14.	Cambiar status a 'completed'
15.	Enviar encuesta NPS a todos los asistentes
16.	Compartir materiales y grabación (si disponible)
17.	Notificar matches mutuos de networking
18.	Otorgar créditos de impacto (+25 por asistencia)
 
5. APIs REST
Método	Endpoint	Descripción
GET	/api/v1/events	Lista de eventos con filtros
GET	/api/v1/events/{id}	Detalle de evento
POST	/api/v1/events	Crear evento (organizadores)
PUT	/api/v1/events/{id}	Actualizar evento
POST	/api/v1/events/{id}/register	Inscribirse a evento
DELETE	/api/v1/events/{id}/register	Cancelar inscripción
GET	/api/v1/events/{id}/attendees	Lista de asistentes
POST	/api/v1/events/{id}/checkin	Check-in presencial
GET	/api/v1/events/{id}/matches	Mis matches del evento
POST	/api/v1/events/{id}/matches/{user_id}/connect	Solicitar conexión
GET	/api/v1/events/my-registrations	Mis inscripciones
GET	/api/v1/events/upcoming	Próximos eventos recomendados
 
6. Roadmap de Implementación
Sprint	Timeline	Entregables
Sprint 1	Semana 1-2	Entidades networking_event, event_registration. CRUD básico.
Sprint 2	Semana 3-4	Sistema de inscripción. Pagos Stripe. Lista de espera.
Sprint 3	Semana 5-6	Integración video (Jitsi/Zoom). Calendario frontend.
Sprint 4	Semana 7-8	Matchmaking algorithm. Speed networking flow.
Sprint 5	Semana 9-10	ECA rules. Post-evento. NPS. Conexiones. QA.
6.1 KPIs de Éxito
KPI	Target	Medición
Eventos/mes	> 4	Eventos publicados por mes
Tasa de asistencia	> 70%	Asistentes / Inscritos
Match rate	> 40%	Matches mutuos / Asistentes networking
NPS eventos	> 45	Net Promoter Score promedio
Follow-up rate	> 50%	% de matches que programan reunión
--- Fin del Documento ---

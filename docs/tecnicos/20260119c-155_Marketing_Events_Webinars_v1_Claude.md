EVENTS & WEBINARS
Extensión jaraba_content_hub
Sistema de Eventos Online con Calendly, Zoom y Landing Pages Integradas
Versión:	1.0
Fecha:	Enero 2026
Código:	155_Marketing_Events_Webinars_v1
Estado:	Especificación Técnica para Implementación
Horas Estimadas:	15-20 horas
Módulo Base:	jaraba_content_hub
Dependencias:	jaraba_core, jaraba_email, Calendly API, Zoom API
1. Resumen Ejecutivo
La extensión Events & Webinars permite crear y gestionar eventos online (webinars, talleres, demos) con landing pages de registro integradas, sincronización automática con Calendly y Zoom, y seguimiento completo de asistentes. Diseñado para potenciar la generación de leads cualificados en los verticales de Empleabilidad y Emprendimiento.
1.1 Capacidades Principales
•	Creación de eventos con landing page automática
•	Integración bidireccional Calendly + Zoom
•	Formulario de registro personalizable
•	Secuencia automática de emails pre/post evento
•	Grabación automática y distribución de replay
•	Analytics de asistencia y engagement
•	Certificados de asistencia automatizados
1.2 Tipos de Eventos Soportados
Tipo	Descripción	Vertical Principal
Webinar	Sesión informativa masiva (50-500 asistentes)	Todos
Taller	Sesión práctica interactiva (10-30 asistentes)	Empleabilidad
Demo Producto	Demostración para prospects (1-10 asistentes)	Emprendimiento
Mentoría Grupal	Sesión de Q&A con mentor (5-20 asistentes)	Emprendimiento
Feria Virtual	Evento multi-sala con stands	AgroConecta
Networking	Sesión de conexión entre participantes	Todos
2. Arquitectura Técnica
2.1 Entidad: event
Definición principal del evento o webinar.
Campo	Tipo	Descripción
id	SERIAL	Primary key
uuid	VARCHAR(36)	Identificador público único
tenant_id	INT FK	Referencia a tenant
title	VARCHAR(200)	Título del evento
slug	VARCHAR(200)	URL amigable única
description	TEXT	Descripción completa (HTML)
event_type	VARCHAR(30)	webinar|taller|demo|mentoria|feria|networking
start_datetime	TIMESTAMP	Fecha y hora de inicio
end_datetime	TIMESTAMP	Fecha y hora de fin
timezone	VARCHAR(50)	Europe/Madrid por defecto
max_attendees	INT	Límite de registros (NULL = ilimitado)
status	VARCHAR(20)	draft|scheduled|live|completed|cancelled
cover_image	VARCHAR(500)	URL imagen de portada
host_user_id	INT FK	Usuario organizador
speaker_info	JSON	Datos de ponentes [{name, bio, image, linkedin}]
registration_fields	JSON	Campos adicionales del formulario
calendly_event_type_id	VARCHAR(100)	ID del tipo de evento en Calendly
zoom_meeting_id	VARCHAR(50)	ID de la reunión Zoom
zoom_join_url	VARCHAR(500)	URL de acceso Zoom
recording_url	VARCHAR(500)	URL de grabación (post-evento)
tags	JSON	Etiquetas para segmentación
created_at	TIMESTAMP	Fecha de creación
updated_at	TIMESTAMP	Última actualización
2.2 Entidad: event_registration
Registro de asistentes a eventos.
Campo	Tipo	Descripción
id	SERIAL	Primary key
uuid	VARCHAR(36)	Identificador público único
event_id	INT FK	Referencia a event
email	VARCHAR(255)	Email del registrado
first_name	VARCHAR(100)	Nombre
last_name	VARCHAR(100)	Apellidos
phone	VARCHAR(20)	Teléfono (opcional)
company	VARCHAR(200)	Empresa (opcional)
custom_fields	JSON	Respuestas a campos personalizados
status	VARCHAR(20)	registered|confirmed|attended|no_show|cancelled
confirmation_sent_at	TIMESTAMP	Fecha envío email confirmación
reminder_sent_at	TIMESTAMP	Fecha envío recordatorio
attended_at	TIMESTAMP	Timestamp de entrada al evento
watch_time_minutes	INT	Minutos de visualización
calendly_invitee_uuid	VARCHAR(100)	UUID del invitado en Calendly
zoom_registrant_id	VARCHAR(100)	ID registrante en Zoom
certificate_url	VARCHAR(500)	URL del certificado generado
utm_source	VARCHAR(100)	Fuente de adquisición
utm_campaign	VARCHAR(100)	Campaña de origen
created_at	TIMESTAMP	Fecha de registro
 
2.3 Entidad: event_landing_page
Configuración de landing page por evento.
Campo	Tipo	Descripción
id	SERIAL	Primary key
event_id	INT FK	Referencia a event (1:1)
template	VARCHAR(50)	webinar_pro|simple|countdown|split
headline	VARCHAR(200)	Título principal de la landing
subheadline	VARCHAR(300)	Subtítulo
benefits_list	JSON	Lista de beneficios con iconos
agenda	JSON	Agenda del evento [{time, topic}]
testimonials	JSON	Testimonios de eventos anteriores
cta_text	VARCHAR(100)	Texto del botón de registro
show_countdown	BOOLEAN	Mostrar cuenta regresiva
show_seats_left	BOOLEAN	Mostrar plazas restantes
custom_css	TEXT	CSS personalizado
meta_title	VARCHAR(70)	SEO title
meta_description	VARCHAR(160)	SEO description
og_image	VARCHAR(500)	Imagen para compartir en redes
3. API REST Endpoints
3.1 Gestión de Eventos
Método	Endpoint	Descripción
GET	/api/v1/events	Listar eventos del tenant
POST	/api/v1/events	Crear nuevo evento
GET	/api/v1/events/{uuid}	Obtener detalle de evento
PATCH	/api/v1/events/{uuid}	Actualizar evento
DELETE	/api/v1/events/{uuid}	Cancelar evento
POST	/api/v1/events/{uuid}/publish	Publicar evento (draft → scheduled)
POST	/api/v1/events/{uuid}/duplicate	Duplicar evento existente
3.2 Registros de Asistentes
Método	Endpoint	Descripción
POST	/api/v1/events/{uuid}/register	Registrar asistente (público)
GET	/api/v1/events/{uuid}/registrations	Listar registrados (admin)
GET	/api/v1/events/{uuid}/registrations/{reg_uuid}	Detalle de registro
PATCH	/api/v1/events/{uuid}/registrations/{reg_uuid}	Actualizar status
POST	/api/v1/events/{uuid}/registrations/export	Exportar a CSV/Excel
POST	/api/v1/events/{uuid}/check-in/{reg_uuid}	Marcar asistencia
3.3 Integración Calendly
Método	Endpoint	Descripción
GET	/api/v1/integrations/calendly/event-types	Listar tipos de evento Calendly
POST	/api/v1/integrations/calendly/sync/{event_uuid}	Sincronizar evento con Calendly
POST	/api/v1/webhooks/calendly	Webhook receptor Calendly
3.4 Integración Zoom
Método	Endpoint	Descripción
POST	/api/v1/integrations/zoom/create-meeting	Crear reunión Zoom para evento
GET	/api/v1/integrations/zoom/recordings/{event_uuid}	Obtener grabaciones
POST	/api/v1/webhooks/zoom	Webhook receptor Zoom
 
4. Flujos ECA (Automatización)
4.1 ECA: Secuencia Email Post-Registro
Trigger: event_registration creado con status = registered
1.	Enviar email de confirmación inmediato con datos del evento
2.	Agregar registrado a lista jaraba_email específica del evento
3.	Crear lead en jaraba_crm si empresa proporcionada
4.	Programar recordatorio 24h antes del evento
5.	Programar recordatorio 1h antes del evento
6.	Disparar evento tracking 'lead' a píxeles activos
4.2 ECA: Sincronización Calendly Webhook
Trigger: POST /api/v1/webhooks/calendly recibido
7.	Verificar firma HMAC del webhook
8.	Parsear tipo de evento (invitee.created|invitee.canceled)
9.	Buscar event por calendly_event_type_id
10.	Si invitee.created → Crear event_registration con datos
11.	Si invitee.canceled → Actualizar status a 'cancelled'
12.	Disparar ECA 4.1 para confirmación
4.3 ECA: Procesamiento Post-Evento
Trigger: event.status cambia a 'completed' (cron o manual)
13.	Sincronizar lista de asistentes desde Zoom (participant report)
14.	Marcar status = 'attended' o 'no_show' según datos Zoom
15.	Obtener URL de grabación de Zoom y guardar en recording_url
16.	Generar certificados para asistentes (si watch_time > 70%)
17.	Enviar email de agradecimiento con link a replay
18.	Enviar encuesta de satisfacción (NPS)
19.	Actualizar lead score en jaraba_crm (+10 puntos asistió, -5 no_show)
4.4 ECA: Generación de Certificados
Trigger: registration.status = 'attended' AND watch_time_minutes >= event.duration * 0.7
20.	Cargar plantilla de certificado del tenant
21.	Renderizar con datos: nombre completo, título evento, fecha, horas
22.	Generar PDF con UUID único verificable
23.	Subir a storage y guardar certificate_url
24.	Enviar email con certificado adjunto
5. Templates de Landing Page
Template	Características	Uso Recomendado
webinar_pro	Hero + countdown + agenda + speakers + testimonios + FAQ	Webinars principales
simple	Hero + descripción + formulario inline	Eventos rápidos
countdown	Fullscreen countdown + beneficios flotantes	Lanzamientos
split	50% video/imagen + 50% formulario	Demos de producto
 
6. Integración con Módulos Existentes
6.1 Con jaraba_email
•	Lista automática por evento para segmentación
•	Secuencia predefinida: Confirmación → Recordatorio 24h → Recordatorio 1h → Post-evento → Replay
•	Templates específicos para eventos con variables dinámicas
•	Tracking de opens/clicks para engagement scoring
6.2 Con jaraba_crm
•	Creación automática de leads desde registros con empresa
•	Actividad 'Evento registrado' / 'Evento asistido' en timeline
•	Lead scoring: +15 registrado, +25 asistido, -10 no_show
•	Campo custom 'Eventos asistidos' en contacto
6.3 Con jaraba_content_hub
•	Evento como tipo de contenido programable
•	Promoción automática en social (jaraba_social)
•	Grabación convertible en contenido on-demand
•	Transcripción automática para blog posts
6.4 Con jaraba_analytics (Pixel Manager)
•	Evento 'lead' al registrarse
•	Evento 'schedule' al confirmar asistencia
•	Custom audience de registrados para retargeting
•	Lookalike audience de asistentes
7. Roadmap de Implementación
Sprint	Entregables	Horas
Sprint 1	Entidades DB, API CRUD eventos, landing page simple	5-6h
Sprint 2	Sistema de registros, formulario personalizable, confirmaciones	4-5h
Sprint 3	Integración Calendly (OAuth, webhooks, sync bidireccional)	3-4h
Sprint 4	Integración Zoom (crear reunión, grabaciones, asistencia)	2-3h
Sprint 5	ECA flows, certificados, templates landing, QA final	1-2h
Total estimado: 15-20 horas
8. Configuración Calendly/Zoom
8.1 Requisitos Calendly
•	Cuenta Calendly Professional o Teams ($12-16/mes)
•	OAuth App creada en Calendly Developer Portal
•	Webhooks configurados para invitee.created, invitee.canceled
•	Event Types específicos para cada tipo de evento
8.2 Requisitos Zoom
•	Cuenta Zoom Pro o Business ($13-20/mes)
•	Server-to-Server OAuth App en Zoom Marketplace
•	Scopes: meeting:write, recording:read, report:read
•	Cloud recording habilitado
9. Casos de Uso por Vertical
Vertical	Tipo de Evento	Objetivo
Empleabilidad	Taller 'CV que Convierte'	Captación buscadores empleo
Empleabilidad	Webinar 'Entrevistas de Éxito'	Engagement candidatos
Emprendimiento	Demo 'Kit Impulso Digital'	Conversión prospects
Emprendimiento	Mentoría Grupal Mensual	Retención emprendedores
AgroConecta	Feria Virtual Productores	Conexión B2B
ComercioConecta	Taller 'Vende Online'	Onboarding comercios


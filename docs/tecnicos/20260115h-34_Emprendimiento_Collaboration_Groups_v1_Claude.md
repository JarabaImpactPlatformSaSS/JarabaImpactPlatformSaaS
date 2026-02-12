SISTEMA DE GRUPOS DE COLABORACIÓN
Collaboration Groups
Vertical de Emprendimiento Digital
JARABA IMPACT PLATFORM
Documento Técnico de Implementación

Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	34_Emprendimiento_Collaboration_Groups
Dependencias:	07_Core_Configuracion_MultiTenant (Group Module), 25_Business_Diagnostic
 
1. Resumen Ejecutivo
Este documento especifica la arquitectura técnica del Sistema de Grupos de Colaboración para la vertical de Emprendimiento. El sistema implementa comunidades de práctica donde emprendedores pueden conectar, colaborar, compartir experiencias y apoyarse mutuamente, utilizando el Group Module de Drupal 11 como base arquitectónica.
1.1 Objetivos del Sistema
•	Comunidad de pares: Conectar emprendedores en situaciones similares
•	Cohortes de programas: Gestionar promociones de programas formativos
•	Grupos sectoriales: Networking por sector de actividad
•	Grupos territoriales: Emprendedores de la misma comarca/provincia
•	Foros de discusión: Espacios para preguntas, respuestas y debates
•	Recursos compartidos: Biblioteca de materiales del grupo
•	Eventos grupales: Webinars, meetups y sesiones de networking
1.2 Stack Tecnológico
Componente	Tecnología
Core CMS	Drupal 11 con Group Module 3.x
Foros	Forum module + Group integration
Biblioteca	Media Library con permisos por grupo
Eventos	Custom entity group_event + calendario
Chat	WebSockets + Redis para mensajería en tiempo real
Notificaciones	Activity Stream + Digest emails
Gamificación	Créditos de impacto por participación
 
2. Tipos de Grupos
El sistema define 5 tipos de grupos (Group Types en Drupal) con diferentes propósitos y funcionalidades.
Tipo	machine_name	Propósito	Acceso	Límite Miembros
Cohorte de Programa	program_cohort	Participantes de una edición de programa	Por invitación	30-50
Grupo Sectorial	sector_group	Emprendedores del mismo sector	Abierto con aprobación	Sin límite
Grupo Territorial	territorial_group	Emprendedores de misma zona geográfica	Abierto	Sin límite
Grupo de Interés	interest_group	Tema específico (marketing, finanzas...)	Abierto	Sin límite
Mastermind	mastermind	Grupo pequeño de apoyo mutuo	Solo invitación	5-8
2.1 Cohorte de Programa
Grupo cerrado para participantes de una edición específica de un programa formativo.
•	Creación: Automática al lanzar nueva edición de programa
•	Miembros: Inscritos en el programa + facilitadores + mentor asignado
•	Duración: Activo durante el programa + 3 meses post-programa
•	Funcionalidades: Foro, recursos del programa, calendario de sesiones, entregas de tareas
2.2 Grupo Sectorial
Comunidad de emprendedores del mismo sector económico.
•	Sectores predefinidos: Comercio, Servicios, Hostelería, Agro, Industria, Tech, Otros
•	Asignación: Sugerida automáticamente según business_diagnostic.sector
•	Moderación: Líderes de comunidad voluntarios + staff Jaraba
•	Funcionalidades: Foro, directorio de miembros, recursos sectoriales, eventos temáticos
2.3 Mastermind
Grupos pequeños de apoyo mutuo intensivo (peer advisory).
•	Tamaño: 5-8 miembros máximo
•	Formato: Reuniones quincenales con hot seat rotativo
•	Matching: Algoritmo que agrupa por fase de negocio y objetivos similares
•	Compromiso: Acuerdo de confidencialidad y participación mínima
 
3. Arquitectura de Entidades
El sistema extiende el Group Module de Drupal con campos y entidades personalizadas.
3.1 Extensión de Group Entity
Campos adicionales añadidos a la entidad Group base:
Campo	Tipo	Descripción	Restricciones
field_group_description	TEXT	Descripción del grupo	NOT NULL
field_group_image	INT	Imagen de portada	FK file_managed.fid
field_sector	VARCHAR(32)	Sector (para sectoriales)	NULLABLE, taxonomy
field_territory	VARCHAR(64)	Territorio (para territoriales)	NULLABLE, taxonomy
field_program_edition	INT	Edición de programa (cohortes)	FK node.nid, NULLABLE
field_start_date	DATE	Fecha de inicio del grupo	NULLABLE
field_end_date	DATE	Fecha de fin (si temporal)	NULLABLE
field_max_members	INT	Límite de miembros	NULLABLE
field_is_featured	BOOLEAN	Grupo destacado	DEFAULT FALSE
field_visibility	VARCHAR(16)	Visibilidad	ENUM: public|members_only|secret
field_join_policy	VARCHAR(24)	Política de ingreso	ENUM: open|approval|invitation
field_activity_score	INT	Puntuación de actividad	COMPUTED
3.2 Entidad: group_discussion
Representa un hilo de discusión en el foro del grupo.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
group_id	INT	Grupo contenedor	FK groups.id, NOT NULL
author_id	INT	Autor del hilo	FK users.uid, NOT NULL
title	VARCHAR(255)	Título del tema	NOT NULL
body	TEXT	Contenido inicial	NOT NULL
category	VARCHAR(32)	Categoría del tema	ENUM: question|discussion|announcement|resource|feedback
is_pinned	BOOLEAN	Fijado arriba	DEFAULT FALSE
is_locked	BOOLEAN	Cerrado para respuestas	DEFAULT FALSE
reply_count	INT	Número de respuestas	DEFAULT 0
view_count	INT	Visualizaciones	DEFAULT 0
last_reply_at	DATETIME	Última respuesta	NULLABLE
last_reply_by	INT	Autor última respuesta	FK users.uid, NULLABLE
status	VARCHAR(16)	Estado	ENUM: active|hidden|archived
created	DATETIME	Creación	NOT NULL
 
3.3 Entidad: group_event
Representa un evento programado dentro del grupo.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
group_id	INT	Grupo organizador	FK groups.id, NOT NULL
organizer_id	INT	Usuario organizador	FK users.uid, NOT NULL
title	VARCHAR(255)	Título del evento	NOT NULL
description	TEXT	Descripción detallada	NOT NULL
event_type	VARCHAR(24)	Tipo de evento	ENUM: webinar|meetup|workshop|networking|mastermind_session
format	VARCHAR(16)	Formato	ENUM: online|in_person|hybrid
start_datetime	DATETIME	Inicio del evento	NOT NULL
end_datetime	DATETIME	Fin del evento	NOT NULL
timezone	VARCHAR(64)	Zona horaria	DEFAULT 'Europe/Madrid'
location	VARCHAR(500)	Ubicación (si presencial)	NULLABLE
meeting_url	VARCHAR(500)	URL de conexión (si online)	NULLABLE
max_attendees	INT	Aforo máximo	NULLABLE
current_attendees	INT	Inscritos actuales	DEFAULT 0
waitlist_count	INT	En lista de espera	DEFAULT 0
is_free	BOOLEAN	Evento gratuito	DEFAULT TRUE
price	DECIMAL(8,2)	Precio si de pago	NULLABLE
status	VARCHAR(16)	Estado	ENUM: draft|published|ongoing|completed|cancelled
created	DATETIME	Creación	NOT NULL
3.4 Entidad: group_resource
Recursos compartidos en la biblioteca del grupo.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
group_id	INT	Grupo contenedor	FK groups.id, NOT NULL
uploaded_by	INT	Usuario que subió	FK users.uid, NOT NULL
title	VARCHAR(255)	Título del recurso	NOT NULL
description	TEXT	Descripción	NULLABLE
resource_type	VARCHAR(24)	Tipo	ENUM: document|template|video|link|tool
file_id	INT	Archivo adjunto	FK file_managed.fid, NULLABLE
external_url	VARCHAR(500)	URL externa	NULLABLE
download_count	INT	Descargas	DEFAULT 0
is_featured	BOOLEAN	Recurso destacado	DEFAULT FALSE
created	DATETIME	Creación	NOT NULL
 
4. Roles y Permisos por Grupo
El Group Module permite definir roles específicos dentro de cada grupo, independientes de los roles globales de Drupal.
4.1 Roles de Grupo
Rol	machine_name	Descripción	Asignación
Administrador	group_admin	Control total del grupo	Creador + designados
Moderador	group_moderator	Modera contenido y miembros	Designado por admin
Facilitador	group_facilitator	Guía actividades (en cohortes)	Staff Jaraba
Mentor	group_mentor	Mentor asignado al grupo	Por asignación
Miembro	group_member	Participante estándar	Al unirse
Invitado	group_guest	Acceso limitado de lectura	Por invitación temporal
4.2 Matriz de Permisos
Permiso	Admin	Moderador	Facilitador	Mentor	Miembro
Editar config grupo	✓	—	—	—	—
Gestionar miembros	✓	✓	—	—	—
Aprobar solicitudes	✓	✓	—	—	—
Crear anuncios	✓	✓	✓	✓	—
Crear eventos	✓	✓	✓	✓	—
Moderar contenido	✓	✓	✓	—	—
Subir recursos	✓	✓	✓	✓	✓
Crear discusiones	✓	✓	✓	✓	✓
Responder hilos	✓	✓	✓	✓	✓
Ver directorio	✓	✓	✓	✓	✓
 
5. Funcionalidades del Grupo
5.1 Foro de Discusión
•	Categorías: Preguntas, Discusiones, Anuncios, Recursos, Feedback
•	Hilos fijados: Admins pueden fijar hilos importantes
•	Menciones: @usuario para notificar a miembros específicos
•	Reacciones: Like, útil, de acuerdo, inspirador
•	Mejor respuesta: El autor puede marcar respuesta como solución
5.2 Biblioteca de Recursos
•	Tipos soportados: PDF, Word, Excel, videos, enlaces externos
•	Organización: Por categorías y etiquetas
•	Destacados: Recursos más útiles marcados por admins
•	Métricas: Contador de descargas y visualizaciones
5.3 Calendario de Eventos
•	Vista calendario: FullCalendar.js con eventos del grupo
•	RSVP: Confirmación de asistencia con estados (sí/no/quizás)
•	Lista de espera: Automática si se alcanza aforo máximo
•	Recordatorios: Email 24h y 1h antes del evento
•	Export .ics: Añadir a calendario personal
5.4 Directorio de Miembros
•	Perfiles visibles: Foto, nombre, sector, fase de negocio
•	Búsqueda: Filtros por sector, territorio, especialidad
•	Contacto: Mensaje privado entre miembros del grupo
•	Privacidad: Cada usuario controla qué datos son visibles
 
6. Flujos de Automatización (ECA)
6.1 ECA-GRP-001: Auto-Asignación a Grupos
Trigger: Usuario completa business_diagnostic
1.	Obtener sector y territorio del diagnóstico
2.	Buscar grupo sectorial correspondiente
3.	Buscar grupo territorial correspondiente
4.	Crear membresías automáticas (status = 'pending' si requiere aprobación)
5.	Notificar al usuario con invitación a explorar sus grupos
6.2 ECA-GRP-002: Nuevo Contenido
Trigger: group_discussion o group_resource creado
6.	Obtener lista de miembros del grupo
7.	Filtrar por preferencias de notificación
8.	Crear entradas en activity_stream
9.	Encolar para digest email (si no inmediato)
10.	Incrementar group.field_activity_score
6.3 ECA-GRP-003: Gamificación
Trigger: Acción de participación en grupo
Acción	Créditos de Impacto
Crear discusión	+10
Responder hilo	+5
Respuesta marcada como solución	+20
Subir recurso	+15
Asistir a evento	+25
Organizar evento	+50
 
7. APIs REST
Método	Endpoint	Descripción
GET	/api/v1/groups	Listar grupos (filtros: type, sector, territory)
GET	/api/v1/groups/{id}	Detalle del grupo
POST	/api/v1/groups/{id}/join	Solicitar unirse al grupo
POST	/api/v1/groups/{id}/leave	Abandonar grupo
GET	/api/v1/groups/{id}/members	Listar miembros del grupo
GET	/api/v1/groups/{id}/discussions	Listar discusiones
POST	/api/v1/groups/{id}/discussions	Crear nueva discusión
GET	/api/v1/discussions/{id}	Detalle de discusión con respuestas
POST	/api/v1/discussions/{id}/replies	Responder a discusión
GET	/api/v1/groups/{id}/events	Listar eventos del grupo
POST	/api/v1/groups/{id}/events	Crear evento
POST	/api/v1/events/{id}/rsvp	Confirmar asistencia a evento
GET	/api/v1/groups/{id}/resources	Listar recursos
POST	/api/v1/groups/{id}/resources	Subir recurso
GET	/api/v1/my-groups	Mis grupos activos
GET	/api/v1/groups/suggested	Grupos sugeridos para mí
 
8. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 1	Semana 1-2	Group Types config. Campos custom. Extensión Group entity.	Group Module instalado
Sprint 2	Semana 3-4	Entidad group_discussion. Foro básico.	Sprint 1
Sprint 3	Semana 5-6	Sistema de eventos. Calendario. RSVP.	Sprint 2
Sprint 4	Semana 7-8	Biblioteca de recursos. Directorio de miembros.	Sprint 3
Sprint 5	Semana 9-10	Auto-asignación. Gamificación. Notificaciones. QA.	Sprint 4
8.1 KPIs de Éxito
KPI	Target	Medición
Usuarios en grupos	> 80%	% de emprendedores en al menos 1 grupo
Grupos activos	> 20	Grupos con actividad en últimos 7 días
Discusiones/semana	> 5 por grupo activo	Nuevos hilos creados
Tasa de respuesta	> 70%	% de discusiones con al menos 1 respuesta
Asistencia eventos	> 60%	% de RSVPs que asisten realmente
--- Fin del Documento ---
34_Emprendimiento_Collaboration_Groups_v1.docx | Jaraba Impact Platform | Enero 2026

SISTEMA DE HITOS Y PROGRESO
Progress Milestones & Achievements
Vertical de Emprendimiento Digital
JARABA IMPACT PLATFORM
Documento Técnico de Implementación
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	30_Emprendimiento_Progress_Milestones
Dependencias:	28_Digitalization_Paths, 25_Business_Diagnostic, Gamificación Core
 
1. Resumen Ejecutivo
Este documento especifica la arquitectura técnica del Sistema de Hitos y Progreso para la vertical de Emprendimiento. El sistema proporciona tracking visual del avance, gamificación mediante créditos de impacto, y celebración de logros para mantener la motivación y engagement de los emprendedores.
1.1 Objetivos del Sistema
•	Visibilidad de progreso: Dashboard visual con métricas de avance en tiempo real
•	Gamificación motivacional: Créditos de impacto, badges, niveles de expertise
•	Hitos significativos: Celebración de logros clave en el journey emprendedor
•	Certificaciones parciales: Credenciales verificables por fase/módulo completado
•	Engagement continuo: Reducción de abandono mediante refuerzo positivo
1.2 Stack Tecnológico
Componente	Tecnología
Core CMS	Drupal 11 con módulo jaraba_progress custom
Badges/Credentials	Open Badges 3.0 compatible, custom entities
Visualización	Chart.js para progreso, CSS animations para celebraciones
Notificaciones	Push notifications + email + in-app toasts
Gamificación	Sistema de créditos compartido con ecosistema Jaraba
Automatización	ECA Module para triggers de logros
 
2. Arquitectura de Entidades
El sistema introduce 5 entidades Drupal que gestionan el tracking de progreso, logros y gamificación.
2.1 Entidad: milestone
Define los hitos significativos que el emprendedor puede alcanzar durante su journey.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
machine_name	VARCHAR(64)	Identificador máquina	UNIQUE, NOT NULL, INDEX
title	VARCHAR(255)	Título del hito	NOT NULL
description	TEXT	Descripción del logro	NOT NULL
celebration_message	TEXT	Mensaje de celebración	NOT NULL
milestone_type	VARCHAR(24)	Tipo de hito	ENUM: path|phase|module|quick_win|special
trigger_type	VARCHAR(24)	Cómo se dispara	ENUM: auto|manual|mentor_approval
trigger_conditions	JSON	Condiciones de disparo	{entity, field, operator, value}
icon	VARCHAR(128)	Icono del hito	URL o clase FontAwesome
badge_image	INT	Imagen del badge	FK file_managed.fid
credit_reward	INT	Créditos de impacto	DEFAULT 50, >= 0
xp_reward	INT	XP para nivel	DEFAULT 100, >= 0
is_shareable	BOOLEAN	Compartible en RRSS	DEFAULT TRUE
vertical	VARCHAR(24)	Vertical asociada	ENUM: emprendimiento|empleabilidad|all
weight	INT	Orden de importancia	DEFAULT 0
is_active	BOOLEAN	Hito activo	DEFAULT TRUE
 
2.2 Entidad: user_milestone
Registro de hitos alcanzados por cada usuario. Histórico inmutable para analytics.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
user_id	INT	Usuario	FK users.uid, NOT NULL, INDEX
milestone_id	INT	Hito alcanzado	FK milestone.id, NOT NULL, INDEX
enrollment_id	INT	Enrollment asociado	FK path_enrollment.id, NULLABLE
tenant_id	INT	Tenant del logro	FK tenant.id, NULLABLE
achieved_at	DATETIME	Fecha de consecución	NOT NULL, UTC, INDEX
credits_awarded	INT	Créditos otorgados	NOT NULL, >= 0
xp_awarded	INT	XP otorgados	NOT NULL, >= 0
context_data	JSON	Datos contextuales	{path_name, module_name, ...}
shared_at	DATETIME	Fecha compartido RRSS	NULLABLE, UTC
shared_platforms	JSON	Plataformas compartidas	['linkedin', 'twitter', ...]
Índice único: UNIQUE INDEX (user_id, milestone_id, enrollment_id) — Un hito solo se puede conseguir una vez por enrollment
2.3 Entidad: expertise_level
Define los niveles de expertise del emprendedor basados en XP acumulado.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
machine_name	VARCHAR(32)	Identificador	UNIQUE, NOT NULL
title	VARCHAR(64)	Nombre del nivel	NOT NULL
description	VARCHAR(255)	Descripción del nivel	NOT NULL
min_xp	INT	XP mínimo requerido	NOT NULL, >= 0
max_xp	INT	XP máximo del nivel	NOT NULL, > min_xp
badge_image	INT	Badge del nivel	FK file_managed.fid
color	VARCHAR(7)	Color hex del nivel	NOT NULL
perks	JSON	Beneficios del nivel	['descuento_10', 'prioridad_mentor']
weight	INT	Orden del nivel	NOT NULL, UNIQUE
 
Niveles de Expertise Predefinidos
machine_name	Título	XP Min	XP Max	Color
novato	Emprendedor Novato	0	499	#95A5A6 (Gris)
iniciado	Emprendedor Iniciado	500	1.499	#3498DB (Azul)
activo	Emprendedor Activo	1.500	3.999	#27AE60 (Verde)
avanzado	Emprendedor Avanzado	4.000	7.999	#E67E22 (Naranja)
experto	Emprendedor Experto	8.000	14.999	#9B59B6 (Morado)
maestro	Maestro Digital	15.000	∞	#F1C40F (Dorado)
2.4 Entidad: user_progress_snapshot
Snapshot diario del progreso del usuario para analytics y gráficos de evolución.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
user_id	INT	Usuario	FK users.uid, NOT NULL
snapshot_date	DATE	Fecha del snapshot	NOT NULL, INDEX
total_credits	INT	Créditos totales	DEFAULT 0
total_xp	INT	XP total	DEFAULT 0
current_level_id	INT	Nivel actual	FK expertise_level.id
milestones_count	INT	Hitos conseguidos	DEFAULT 0
paths_completed	INT	Itinerarios completados	DEFAULT 0
active_streak_days	INT	Racha de días activos	DEFAULT 0
engagement_score	DECIMAL(5,2)	Score de engagement	COMPUTED, 0-100
Índice único: UNIQUE INDEX (user_id, snapshot_date)
 
3. Catálogo de Hitos Predefinidos
Se definen hitos para cada momento clave del journey emprendedor:
3.1 Hitos de Journey
Hito	Trigger	Créditos	XP
Primer Paso	Completar Calculadora TTV	25	50
Autoconocimiento	Completar Diagnóstico Empresarial	100	200
Con Rumbo	Iniciar primer Itinerario	50	100
Primera Victoria	Completar primer Quick Win	75	150
Módulo Dominado	Completar cualquier módulo	100	200
Fase Conquistada	Completar fase del Método Jaraba	200	400
Transformación Digital	Completar itinerario completo	500	1000
3.2 Hitos de Impacto Real
Hito	Trigger	Créditos	XP
Visible Online	Web/landing publicada	150	300
Primera Venta Digital	Registrar primera venta online	300	500
10 Clientes Digitales	Alcanzar 10 ventas online	400	600
Automatizado	Configurar primera automatización	200	400
Data-Driven	Configurar analytics y tomar 1 decisión basada en datos	250	500
3.3 Hitos de Comunidad
Hito	Trigger	Créditos	XP
Networker	Unirse a grupo de colaboración	50	100
Mentor Conectado	Completar primera sesión de mentoría	100	200
Colaborador	Ayudar a otro emprendedor (validado)	150	300
Evangelista	Referir a nuevo usuario que completa diagnóstico	200	400
 
4. APIs REST
Método	Endpoint	Descripción
GET	/api/v1/milestones	Listar todos los hitos disponibles
GET	/api/v1/users/{uid}/milestones	Hitos conseguidos por el usuario
GET	/api/v1/users/{uid}/milestones/pending	Próximos hitos a conseguir (con progreso)
GET	/api/v1/users/{uid}/progress	Resumen completo de progreso del usuario
GET	/api/v1/users/{uid}/progress/timeline	Timeline de snapshots para gráficos
GET	/api/v1/users/{uid}/level	Nivel actual y progreso al siguiente
POST	/api/v1/milestones/{id}/share	Compartir hito en redes sociales
GET	/api/v1/leaderboard/credits	Ranking por créditos de impacto
GET	/api/v1/leaderboard/level	Ranking por nivel de expertise
 
5. Flujos de Automatización ECA
5.1 ECA-MILE-001: Evaluación de Hitos
Trigger: Cualquier actualización en entidades monitoreadas (diagnostic, enrollment, step_progress)
•	Cargar milestones activos con trigger_type = 'auto'
•	Evaluar trigger_conditions contra estado actual del usuario
•	Si condiciones cumplidas y hito no conseguido: crear user_milestone
•	Disparar ECA-MILE-002 (Celebración)
5.2 ECA-MILE-002: Celebración de Logro
Trigger: Creación de user_milestone
•	Sumar credits_awarded al balance del usuario
•	Sumar xp_awarded al XP total
•	Evaluar si XP cruza umbral de nivel → ECA-MILE-003
•	Mostrar modal de celebración in-app con animación confetti
•	Enviar push notification
•	Enviar email con badge descargable
•	Si is_shareable: mostrar opciones de compartir en RRSS
5.3 ECA-MILE-003: Level Up
Trigger: user.total_xp cruza umbral de expertise_level.min_xp
•	Actualizar current_level_id del usuario
•	Mostrar celebración especial de "Level Up"
•	Activar perks del nuevo nivel (descuentos, prioridades)
•	Email especial con badge de nivel
•	Webhook ActiveCampaign: tag 'level_{machine_name}'
5.4 ECA-MILE-004: Snapshot Diario
Trigger: CRON diario a las 00:00 UTC
•	Para cada usuario activo: crear user_progress_snapshot
•	Calcular engagement_score basado en actividad reciente
•	Actualizar active_streak_days (resetear si no hubo actividad)
•	Si racha > 7 días: crear hito especial "Constante"
 
6. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 1	Semana 1-2	Módulo jaraba_progress: entidades milestone, user_milestone, expertise_level. Migrations.	Core entities
Sprint 2	Semana 3-4	Motor de evaluación de hitos. Sistema de créditos/XP. APIs básicas.	Sprint 1
Sprint 3	Semana 5-6	Flujos ECA de celebración. Integración con diagnósticos e itinerarios.	Sprint 2 + Docs 25,28
Sprint 4	Semana 7-8	UI de celebración (modales, animaciones). Componentes de progreso visual.	Sprint 3
Sprint 5	Semana 9-10	Sharing en RRSS. Badges Open Badge 3.0. Leaderboards.	Sprint 4
Sprint 6	Semana 11-12	Snapshots diarios. Analytics de engagement. QA. Go-live.	Sprint 5
--- Fin del Documento ---

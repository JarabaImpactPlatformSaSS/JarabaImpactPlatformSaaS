ITINERARIOS DE DIGITALIZACIÓN
Digitalization Paths
Vertical de Emprendimiento Digital
JARABA IMPACT PLATFORM
Documento Técnico de Implementación
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	28_Emprendimiento_Digitalization_Paths
Dependencias:	01_Core_Entidades, 25_Business_Diagnostic_Core, 06_Core_Flujos_ECA
 
1. Resumen Ejecutivo
Este documento especifica la arquitectura técnica del Sistema de Itinerarios de Digitalización para la vertical de Emprendimiento. Los itinerarios son el equivalente al sistema LMS de Empleabilidad, adaptado para guiar la transformación digital de negocios según el Método Jaraba™ (Diagnóstico → Implementación → Optimización).
1.1 Objetivos del Sistema
•	Personalización por diagnóstico: Asignación automática según nivel de madurez y sector
•	Método Jaraba estructurado: 3 fases con módulos secuenciales y branching condicional
•	Quick Wins priorizados: Acciones de impacto inmediato antes que proyectos complejos
•	Recursos integrados: Kits, templates, tutoriales vinculados a cada paso
•	Tracking de ROI: Medición de impacto económico por acción completada
1.2 Stack Tecnológico
Componente	Tecnología
Core CMS	Drupal 11 con módulo jaraba_paths custom
Contenido	Paragraphs + Media Library para recursos multimedia
Branching Logic	Rules/ECA para lógica condicional entre módulos
Progress	Custom entity progress_record con estados por step
Gamificación	Sistema de créditos de impacto compartido con ecosistema
Notificaciones	ActiveCampaign + Drupal Queue + Push notifications
 
2. Arquitectura de Entidades
El sistema introduce 5 entidades Drupal personalizadas que implementan la estructura del Método Jaraba.
2.1 Entidad: digitalization_path
Representa un itinerario completo de digitalización. Cada path está diseñado para un perfil específico de diagnóstico y sector.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único global	UNIQUE, NOT NULL, INDEX
title	VARCHAR(255)	Título del itinerario	NOT NULL
machine_name	VARCHAR(64)	Identificador máquina	UNIQUE, NOT NULL, INDEX
description	TEXT	Descripción completa	NOT NULL
short_description	VARCHAR(300)	Resumen para cards	NOT NULL
thumbnail	INT	Imagen de portada	FK file_managed.fid
target_maturity_level	VARCHAR(24)	Nivel de madurez target	ENUM: analogico|basico|conectado|all
target_sector	VARCHAR(32)	Sector target	ENUM: comercio|servicios|agro|hosteleria|all
estimated_weeks	INT	Duración estimada semanas	NOT NULL, > 0
estimated_investment	DECIMAL(10,2)	Inversión estimada €	NULLABLE, >= 0
expected_roi_percent	INT	ROI esperado %	NULLABLE, > 0
total_modules	INT	Número de módulos	COMPUTED
total_credits	INT	Créditos de impacto totales	COMPUTED
tenant_id	INT	Tenant propietario	FK tenant.id, NULLABLE (NULL=global)
is_published	BOOLEAN	Publicado	DEFAULT FALSE
is_premium	BOOLEAN	Requiere suscripción	DEFAULT FALSE
created	DATETIME	Fecha de creación	NOT NULL, UTC
changed	DATETIME	Última modificación	NOT NULL, UTC
 
2.2 Entidad: path_phase
Representa las 3 fases del Método Jaraba™: Diagnóstico, Implementación, Optimización.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
path_id	INT	Itinerario padre	FK digitalization_path.id, NOT NULL
phase_type	VARCHAR(24)	Tipo de fase Método Jaraba	ENUM: diagnosis|implementation|optimization
title	VARCHAR(128)	Título de la fase	NOT NULL
subtitle	VARCHAR(255)	Subtítulo descriptivo	NULLABLE (ej: 'El Mapa sin Humo')
description	TEXT	Descripción de la fase	NOT NULL
icon	VARCHAR(64)	Icono de la fase	DEFAULT by phase_type
color	VARCHAR(7)	Color hex de la fase	DEFAULT by phase_type
weight	INT	Orden (1, 2, 3)	NOT NULL, INDEX
unlock_after_days	INT	Días para desbloquear	DEFAULT 0
deliverable_template	TEXT	Template del entregable	NULLABLE
Fases Predefinidas del Método Jaraba™
phase_type	Título	Subtítulo	Entregable
diagnosis	Diagnóstico y Hoja de Ruta	"El Mapa sin Humo"	Plan de Impulso
implementation	Implementación y Acción	"El Motor Práctico"	Activos + Primer Logro
optimization	Optimización y Escalado	"El Efecto Multiplicador"	Informe + Siguiente Ciclo
 
2.3 Entidad: path_module
Módulo dentro de una fase. Agrupa pasos relacionados con un objetivo específico.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
phase_id	INT	Fase padre	FK path_phase.id, NOT NULL
title	VARCHAR(255)	Título del módulo	NOT NULL
objective	TEXT	Objetivo del módulo	NOT NULL
estimated_hours	DECIMAL(4,1)	Horas estimadas	NOT NULL, > 0
difficulty	VARCHAR(16)	Dificultad	ENUM: easy|medium|hard
weight	INT	Orden en la fase	DEFAULT 0, INDEX
completion_credits	INT	Créditos al completar	DEFAULT 50
is_quick_win	BOOLEAN	Es acción rápida	DEFAULT FALSE
prerequisites	JSON	Módulos prerequisito	Array of module_id, NULLABLE
conditional_show	JSON	Condición para mostrar	{diagnostic_section, min_score}
 
2.4 Entidad: path_step
Paso individual de acción. La unidad atómica de progreso en el itinerario.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
module_id	INT	Módulo padre	FK path_module.id, NOT NULL
title	VARCHAR(255)	Título del paso	NOT NULL
step_type	VARCHAR(24)	Tipo de paso	ENUM: action|resource|reflection|validation
instructions	TEXT	Instrucciones detalladas	NOT NULL
estimated_minutes	INT	Tiempo estimado minutos	DEFAULT 15
weight	INT	Orden en el módulo	DEFAULT 0, INDEX
resource_type	VARCHAR(24)	Tipo de recurso asociado	ENUM: video|pdf|template|tool|external
resource_id	INT	ID del recurso	FK digital_kit.id / file.fid, NULLABLE
external_url	VARCHAR(512)	URL externa (si aplica)	NULLABLE
validation_type	VARCHAR(24)	Cómo se valida completitud	ENUM: self|upload|mentor|auto
validation_criteria	TEXT	Criterios de validación	NULLABLE
completion_credits	INT	Créditos del paso	DEFAULT 10
is_optional	BOOLEAN	Paso opcional	DEFAULT FALSE
 
2.5 Entidad: path_enrollment
Registro de inscripción de un usuario a un itinerario de digitalización.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
user_id	INT	Usuario inscrito	FK users.uid, NOT NULL, INDEX
path_id	INT	Itinerario	FK digitalization_path.id, NOT NULL
diagnostic_id	INT	Diagnóstico origen	FK business_diagnostic.id, NULLABLE
tenant_id	INT	Tenant del enrollment	FK tenant.id, NULLABLE, INDEX
enrollment_type	VARCHAR(24)	Tipo de inscripción	ENUM: diagnostic|manual|grant|purchase
status	VARCHAR(16)	Estado	ENUM: active|paused|completed|abandoned
current_phase	INT	Fase actual	FK path_phase.id, NULLABLE
current_module	INT	Módulo actual	FK path_module.id, NULLABLE
progress_percent	DECIMAL(5,2)	Progreso total %	RANGE 0-100, DEFAULT 0
credits_earned	INT	Créditos ganados	DEFAULT 0
enrolled_at	DATETIME	Fecha inscripción	NOT NULL, UTC
started_at	DATETIME	Fecha inicio real	NULLABLE, UTC
completed_at	DATETIME	Fecha completitud	NULLABLE, UTC
last_activity_at	DATETIME	Última actividad	NULLABLE, UTC, INDEX
mentor_id	INT	Mentor asignado	FK users.uid, NULLABLE
Índice único: UNIQUE INDEX (user_id, path_id) — Un usuario solo puede tener un enrollment activo por itinerario
 
3. Itinerarios Predefinidos
Se definen 4 itinerarios base según el nivel de madurez diagnosticado:
Itinerario	Target Level	Sectores	Semanas	Módulos
De Cero a Digital	analogico	Todos	12	15
Impulso Digital Básico	basico	Todos	8	12
Conecta tu Negocio	conectado	Comercio, Servicios	6	10
Optimización Avanzada	digitalizado	Todos	8	12
3.1 Ejemplo: "De Cero a Digital"
Estructura completa del itinerario para negocios en nivel "analógico":
Fase	Módulo	Acciones clave	Quick Win
1. Diagnóstico	Auditoría digital completa	Análisis presencia, competencia	Perfil Google My Business
1. Diagnóstico	Definición del cliente ideal	Avatar, propuesta de valor	Bio profesional 30 seg
2. Implementación	Escaparate digital básico	Web/landing page sencilla	Página en 15 minutos
2. Implementación	Redes sociales estratégicas	Configurar 1-2 redes clave	Primer post profesional
2. Implementación	Primeras ventas online	Catálogo, pasarela de pago	Primera venta digital
3. Optimización	Automatización básica	Email, respuestas automáticas	Secuencia bienvenida
3. Optimización	Medición y mejora continua	Analytics, KPIs básicos	Dashboard simple
 
4. APIs REST
Método	Endpoint	Descripción
GET	/api/v1/paths	Listar itinerarios disponibles (filtrable por sector, level)
GET	/api/v1/paths/{uuid}	Detalle de itinerario con fases, módulos, steps
POST	/api/v1/paths/{uuid}/enroll	Inscribir usuario actual al itinerario
GET	/api/v1/enrollments/{uuid}	Detalle del enrollment con progreso
POST	/api/v1/enrollments/{uuid}/steps/{step_id}/complete	Marcar paso como completado
POST	/api/v1/enrollments/{uuid}/steps/{step_id}/upload	Subir evidencia de completitud
GET	/api/v1/users/{uid}/enrollments	Listar enrollments del usuario
GET	/api/v1/enrollments/{uuid}/progress-report	Informe de progreso detallado
 
5. Flujos de Automatización ECA
5.1 ECA-PATH-001: Auto-Enrollment Post Diagnóstico
Trigger: business_diagnostic.status = 'completed'
•	Obtener recommended_path_id del diagnóstico
•	Crear path_enrollment con enrollment_type = 'diagnostic'
•	Asignar créditos de impacto iniciales (+25)
•	Enviar email de bienvenida al itinerario con primer Quick Win
•	Webhook ActiveCampaign: tag 'path_enrolled_{path_machine_name}'
5.2 ECA-PATH-002: Step Completado
Trigger: path_step_progress.status = 'completed'
•	Asignar créditos del step al usuario
•	Recalcular progress_percent del enrollment
•	Si es último step del módulo: disparar ECA-PATH-003
•	Si es Quick Win: enviar email de celebración
•	Desbloquear siguiente step/módulo si hay prerequisites
5.3 ECA-PATH-003: Módulo Completado
Trigger: Todos los steps requeridos del módulo completados
•	Asignar créditos bonus del módulo
•	Actualizar current_module al siguiente
•	Si es último módulo de fase: disparar ECA-PATH-004
•	Notificación push + email con resumen de logros
 
6. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 1	Semana 1-2	Módulo jaraba_paths: entidades path, phase, module, step. Migrations.	Core entities
Sprint 2	Semana 3-4	Entidad path_enrollment, path_step_progress. Sistema de progreso.	Sprint 1
Sprint 3	Semana 5-6	APIs REST completas. Frontend de navegación de itinerarios.	Sprint 2
Sprint 4	Semana 7-8	Flujos ECA. Auto-enrollment. Integración con diagnósticos.	Sprint 3 + Doc25
Sprint 5	Semana 9-10	Dashboard de progreso. Gamificación. Notificaciones.	Sprint 4
Sprint 6	Semana 11-12	Carga de 4 itinerarios base. QA. Piloto con 10 usuarios. Go-live.	Sprint 5
--- Fin del Documento ---

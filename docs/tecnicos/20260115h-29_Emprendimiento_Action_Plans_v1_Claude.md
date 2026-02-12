SISTEMA DE PLANES DE ACCIÓN
Action Plans
Vertical de Emprendimiento Digital
JARABA IMPACT PLATFORM
Documento Técnico de Implementación

Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	29_Emprendimiento_Action_Plans
Dependencias:	28_Digitalization_Paths, 25_Business_Diagnostic, 06_Core_Flujos_ECA
 
1. Resumen Ejecutivo
Este documento especifica la arquitectura técnica del Sistema de Planes de Acción para la vertical de Emprendimiento. Los planes de acción traducen el diagnóstico y la ruta de digitalización en tareas concretas, ejecutables y medibles, siguiendo la filosofía "Sin Humo" del Ecosistema Jaraba: acciones reales con impacto demostrable.
1.1 Objetivos del Sistema
•	Desglose accionable: Convertir módulos del itinerario en tareas específicas con tiempo estimado
•	Priorización inteligente: Quick Wins primero, proyectos complejos después
•	Dependencias claras: Visualización de prerequisitos y secuenciación
•	Recursos vinculados: Cada tarea enlaza a kits, tutoriales y templates
•	Tracking granular: Seguimiento por tarea con estados detallados
1.2 Stack Tecnológico
Componente	Tecnología
Core CMS	Drupal 11 con módulo jaraba_action_plans custom
Gestión de Tareas	Custom entity action_task con estados workflow
Dependencias	GraphQL para visualización de grafo de tareas
Notificaciones	Push + Email + In-app con scheduling inteligente
Integraciones	Google Calendar, Trello, Notion (opcional)
Automatización	ECA Module para triggers y recordatorios
 
2. Arquitectura de Entidades
El sistema introduce 4 entidades Drupal personalizadas que implementan la gestión completa de planes de acción y tareas.
2.1 Entidad: action_plan
Representa un plan de acción completo asociado a un emprendedor y su itinerario de digitalización.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno autoincremental	PRIMARY KEY
uuid	UUID	Identificador único global	UNIQUE, NOT NULL, INDEX
user_id	INT	Emprendedor propietario	FK users.uid, NOT NULL
tenant_id	INT	Tenant/programa asociado	FK tenant.id, NULLABLE
path_id	INT	Itinerario de digitalización	FK digitalization_path.id
business_diagnostic_id	INT	Diagnóstico origen	FK business_diagnostic.id
title	VARCHAR(255)	Título del plan	NOT NULL
description	TEXT	Descripción y objetivos	NULLABLE
priority_mode	VARCHAR(24)	Modo de priorización	ENUM: quick_wins|balanced|strategic
start_date	DATE	Fecha de inicio	NOT NULL
target_end_date	DATE	Fecha objetivo fin	NULLABLE
actual_end_date	DATE	Fecha real de completitud	NULLABLE
total_tasks	INT	Total de tareas en plan	COMPUTED, >= 0
completed_tasks	INT	Tareas completadas	COMPUTED, >= 0
progress_percent	DECIMAL(5,2)	Porcentaje de avance	COMPUTED, 0-100
estimated_hours	DECIMAL(6,2)	Horas estimadas totales	COMPUTED
logged_hours	DECIMAL(6,2)	Horas registradas	COMPUTED
status	VARCHAR(16)	Estado del plan	ENUM: draft|active|paused|completed|abandoned
created	DATETIME	Fecha de creación	NOT NULL, UTC
changed	DATETIME	Última modificación	NOT NULL, UTC
2.2 Entidad: action_task
Representa una tarea individual dentro de un plan de acción. Es la unidad atómica de trabajo.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
plan_id	INT	Plan padre	FK action_plan.id, NOT NULL
module_step_id	INT	Paso del itinerario origen	FK path_step.id, NULLABLE
title	VARCHAR(255)	Título de la tarea	NOT NULL
description	TEXT	Descripción detallada	NULLABLE
task_type	VARCHAR(24)	Tipo de tarea	ENUM: quick_win|standard|project|milestone
priority	INT	Prioridad (1=alta)	NOT NULL, RANGE 1-5
complexity	VARCHAR(16)	Complejidad estimada	ENUM: trivial|easy|medium|hard|expert
estimated_minutes	INT	Tiempo estimado minutos	NOT NULL, > 0
logged_minutes	INT	Tiempo registrado	DEFAULT 0
impact_score	INT	Puntuación de impacto	RANGE 1-10
effort_score	INT	Puntuación de esfuerzo	RANGE 1-10
roi_index	DECIMAL(4,2)	Índice ROI (impact/effort)	COMPUTED
due_date	DATE	Fecha límite	NULLABLE
started_at	DATETIME	Inicio real	NULLABLE
completed_at	DATETIME	Completitud real	NULLABLE
status	VARCHAR(16)	Estado de tarea	ENUM: pending|in_progress|blocked|completed|skipped
blocked_reason	VARCHAR(500)	Razón de bloqueo	NULLABLE
completion_notes	TEXT	Notas al completar	NULLABLE
created	DATETIME	Creación	NOT NULL, UTC
 
2.3 Entidad: task_dependency
Define las relaciones de dependencia entre tareas, permitiendo construir el grafo de ejecución.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
task_id	INT	Tarea dependiente	FK action_task.id, NOT NULL
depends_on_task_id	INT	Tarea prerequisito	FK action_task.id, NOT NULL
dependency_type	VARCHAR(24)	Tipo de dependencia	ENUM: hard|soft|recommended
created	DATETIME	Fecha de creación	NOT NULL
2.4 Entidad: task_resource
Vincula tareas con recursos de apoyo: kits digitales, tutoriales, templates, herramientas recomendadas.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
task_id	INT	Tarea asociada	FK action_task.id, NOT NULL
resource_type	VARCHAR(24)	Tipo de recurso	ENUM: kit|template|tutorial|tool|external_link
resource_id	INT	ID del recurso interno	FK node.nid, NULLABLE
external_url	VARCHAR(500)	URL si es externo	NULLABLE
title	VARCHAR(255)	Título del recurso	NOT NULL
description	VARCHAR(500)	Descripción breve	NULLABLE
is_required	BOOLEAN	Es obligatorio	DEFAULT FALSE
display_order	INT	Orden de visualización	DEFAULT 0
 
3. Motor de Priorización
El sistema implementa un algoritmo de priorización basado en la matriz de Eisenhower adaptada, combinando impacto, esfuerzo y dependencias.
3.1 Índice ROI de Tarea
Cada tarea calcula un índice ROI que guía la priorización:
ROI_Index = (Impact_Score × 10) / (Effort_Score × Complexity_Factor)
Complexity	Factor	Descripción
trivial	0.5	Tarea de menos de 15 minutos
easy	0.8	Tarea sencilla, 15-60 minutos
medium	1.0	Tarea estándar, 1-4 horas
hard	1.5	Tarea compleja, 4-8 horas
expert	2.0	Requiere expertise o apoyo externo
3.2 Modos de Priorización
Modo	Algoritmo	Ideal para
quick_wins	Ordenar por ROI_Index DESC, filtrar complexity IN (trivial, easy)	Emprendedores nuevos, generar momentum
balanced	Ordenar por ROI_Index DESC sin filtro de complejidad	Progreso sostenido, equilibrio esfuerzo/resultado
strategic	Priorizar tareas con alto Impact_Score aunque Effort alto	Transformación profunda, visión a largo plazo
3.3 Algoritmo de Secuenciación
El algoritmo respeta las dependencias y optimiza la ejecución:
1.	Construir grafo dirigido acíclico (DAG) de tareas y dependencias
2.	Ordenación topológica para garantizar prerequisitos
3.	Dentro de cada nivel del DAG, ordenar por ROI_Index según modo
4.	Identificar Quick Wins ejecutables inmediatamente (sin dependencias pendientes)
5.	Marcar camino crítico para alertas de plazos
 
4. Templates de Planes por Sector
El sistema incluye plantillas predefinidas de tareas según el sector del negocio, generadas automáticamente desde el diagnóstico.
4.1 Template: Comercio Local
Fase	Tarea	Tipo	Tiempo Est.	Impacto
Quick Win	Crear ficha Google My Business	quick_win	30 min	9/10
Quick Win	Configurar WhatsApp Business	quick_win	20 min	8/10
Quick Win	Primera publicación en Instagram	quick_win	45 min	7/10
Básico	Diseñar catálogo digital de productos	standard	4 horas	9/10
Básico	Configurar pasarela de pago básica	standard	2 horas	10/10
Intermedio	Crear landing page de ventas	project	8 horas	9/10
Intermedio	Implementar sistema de reservas online	project	6 horas	8/10
Avanzado	Configurar email marketing automatizado	project	4 horas	7/10
Avanzado	Integrar analytics y métricas	standard	3 horas	8/10
4.2 Template: Servicios Profesionales
Fase	Tarea	Tipo	Tiempo Est.	Impacto
Quick Win	Optimizar perfil de LinkedIn	quick_win	45 min	9/10
Quick Win	Crear bio profesional de 30 segundos	quick_win	30 min	8/10
Quick Win	Solicitar 3 testimonios a clientes	quick_win	20 min	8/10
Básico	Diseñar portfolio de servicios	standard	4 horas	9/10
Básico	Crear sistema de presupuestos online	standard	3 horas	8/10
Intermedio	Implementar reserva de citas online	project	4 horas	9/10
Intermedio	Crear página de servicios con SEO	project	8 horas	8/10
Avanzado	Automatizar seguimiento de leads	project	6 horas	7/10
Avanzado	Crear sistema de facturación digital	standard	4 horas	8/10
4.3 Template: Agroalimentario
Fase	Tarea	Tipo	Tiempo Est.	Impacto
Quick Win	Crear perfil en marketplace local	quick_win	30 min	8/10
Quick Win	Documentar trazabilidad del producto	quick_win	2 horas	9/10
Quick Win	Tomar fotos profesionales de productos	quick_win	2 horas	8/10
Básico	Crear ficha técnica de productos	standard	4 horas	8/10
Básico	Implementar sistema de pedidos básico	standard	4 horas	9/10
Intermedio	Integrarse en plataforma de venta directa	project	6 horas	9/10
Intermedio	Crear contenido sobre origen/proceso	project	8 horas	7/10
Avanzado	Implementar sistema de suscripción	project	8 horas	8/10
Avanzado	Certificaciones digitales de calidad	standard	4 horas	8/10
 
5. Flujos de Automatización (ECA)
5.1 ECA-AP-001: Generación Automática de Plan
Trigger: Usuario completa inscripción en itinerario de digitalización
6.	Obtener path_id y business_diagnostic_id del usuario
7.	Cargar template de tareas según sector del diagnóstico
8.	Crear action_plan con modo de priorización por defecto (quick_wins)
9.	Generar action_tasks desde template, vinculando con path_steps
10.	Crear task_dependencies según la lógica del template
11.	Vincular task_resources disponibles en la plataforma
12.	Enviar notificación con resumen del plan y primera tarea Quick Win
5.2 ECA-AP-002: Completitud de Tarea
Trigger: action_task.status cambia a 'completed'
13.	Actualizar completed_tasks y progress_percent del plan
14.	Desbloquear tareas dependientes (dependency_type = 'hard')
15.	Asignar créditos de impacto según impact_score de la tarea
16.	Si tarea es Quick Win: mostrar celebración animada
17.	Notificar siguiente tarea recomendada según priorización
18.	Si progress >= 100%: disparar ECA-AP-003 (Completitud de Plan)
5.3 ECA-AP-003: Completitud de Plan
Trigger: action_plan.progress_percent = 100
19.	Actualizar status = 'completed', actual_end_date = NOW()
20.	Emitir certificación de fase completada
21.	Calcular horas totales invertidas vs. estimadas
22.	Actualizar métricas de impacto del emprendedor
23.	Notificar logro con resumen de avances y siguiente fase
5.4 ECA-AP-004: Recordatorios Inteligentes
Trigger: Cron diario a las 09:00 hora local del usuario
24.	Buscar planes activos sin actividad en 3+ días
25.	Identificar tareas con due_date próximo (< 48h)
26.	Seleccionar el Quick Win más pequeño pendiente
27.	Enviar notificación motivacional con la micro-acción del día
 
6. APIs REST
Método	Endpoint	Descripción
GET	/api/v1/action-plans	Listar planes del usuario autenticado
GET	/api/v1/action-plans/{id}	Detalle de plan con todas las tareas
POST	/api/v1/action-plans	Crear nuevo plan (body: path_id, priority_mode)
PATCH	/api/v1/action-plans/{id}	Actualizar plan (status, priority_mode)
GET	/api/v1/action-plans/{id}/tasks	Listar tareas del plan con filtros
GET	/api/v1/action-plans/{id}/next-tasks	Obtener siguientes tareas recomendadas
GET	/api/v1/action-plans/{id}/quick-wins	Listar Quick Wins pendientes
GET	/api/v1/tasks/{id}	Detalle de tarea con recursos
PATCH	/api/v1/tasks/{id}	Actualizar tarea (status, logged_minutes, notes)
POST	/api/v1/tasks/{id}/start	Marcar tarea como iniciada
POST	/api/v1/tasks/{id}/complete	Marcar tarea como completada
POST	/api/v1/tasks/{id}/skip	Saltar tarea con razón
GET	/api/v1/tasks/{id}/dependencies	Ver dependencias de una tarea
GET	/api/v1/action-plans/{id}/graph	Obtener grafo de tareas (GraphQL)
 
7. Diseño de Interfaz de Usuario
7.1 Vista Principal del Plan
La interfaz principal muestra un tablero Kanban adaptado con columnas por estado:
Columna	Color	Contenido
Quick Wins	Verde (#27AE60)	Tareas de alta prioridad y bajo esfuerzo ejecutables ahora
Pendientes	Azul (#3498DB)	Tareas listas para iniciar (dependencias cumplidas)
En Progreso	Naranja (#F39C12)	Tareas actualmente en ejecución
Bloqueadas	Rojo (#E74C3C)	Tareas con dependencias pendientes o issues
Completadas	Gris (#95A5A6)	Tareas finalizadas (colapsable)
7.2 Tarjeta de Tarea
Cada tarjeta de tarea muestra información esencial de un vistazo:
•	Badge de tipo: Quick Win (verde), Standard (azul), Project (morado)
•	Título y descripción: Truncado con tooltip para expandir
•	Tiempo estimado: Icono de reloj con minutos/horas
•	Índice ROI: Barra visual de impacto vs. esfuerzo
•	Recursos: Iconos clicables a kits/tutoriales/templates
•	Dependencias: Indicador si tiene tareas bloqueantes
7.3 Barra de Progreso Global
Header persistente con métricas clave:
•	Barra de progreso animada con porcentaje
•	Contador: X de Y tareas completadas
•	Horas invertidas vs. estimadas
•	Días restantes hasta fecha objetivo
•	Racha actual de días consecutivos con actividad
 
8. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 1	Semana 1-2	Entidades action_plan, action_task. Migrations. CRUD básico.	Fase 1 completada
Sprint 2	Semana 3-4	Motor de priorización. Índice ROI. Secuenciación.	Sprint 1
Sprint 3	Semana 5-6	Templates por sector. Generación automática de planes.	Sprint 2
Sprint 4	Semana 7-8	Frontend Kanban. Tarjetas de tarea. Drag & drop.	Sprint 3
Sprint 5	Semana 9-10	ECA rules. Notificaciones. Integraciones calendario. QA.	Sprint 4
8.1 KPIs de Éxito
KPI	Target	Medición
Tasa de inicio	> 80%	% de usuarios que inician al menos 1 tarea en 24h
Quick Wins completados	> 3 en primera semana	Promedio de tareas quick_win completadas
Tasa de completitud de plan	> 60%	% de planes que llegan a 100% progress
Tiempo medio por tarea	< 150% del estimado	logged_minutes vs. estimated_minutes
Tasa de abandono	< 25%	% de planes que pasan a status 'abandoned'
--- Fin del Documento ---
29_Emprendimiento_Action_Plans_v1.docx | Jaraba Impact Platform | Enero 2026

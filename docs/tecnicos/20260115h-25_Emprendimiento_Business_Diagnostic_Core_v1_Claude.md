SISTEMA DE DIAGNÓSTICO EMPRESARIAL
Business Diagnostic Core
Vertical de Emprendimiento Digital
JARABA IMPACT PLATFORM
Documento Técnico de Implementación
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	25_Emprendimiento_Business_Diagnostic_Core
Dependencias:	01_Core_Entidades, 06_Core_Flujos_ECA, Calculadora_Madurez_Digital
 
1. Resumen Ejecutivo
Este documento especifica la arquitectura técnica del Sistema de Diagnóstico Empresarial para la vertical de Emprendimiento del Ecosistema Jaraba. El sistema es el componente central del programa "Impulso Digital", que transforma a usuarios de la Calculadora de Madurez Digital en emprendedores con un roadmap claro de digitalización.
1.1 Objetivos del Sistema
•	Diagnóstico profundo: Evaluación multidimensional de la madurez digital del negocio
•	Scoring sectorial: Benchmarks por sector (comercio, servicios, agro, hostelería)
•	Roadmap automático: Generación de plan de acción personalizado según gaps detectados
•	Cuantificación del dolor: Estimación de pérdidas económicas por gaps digitales
•	Multi-tenant: Diagnósticos compartidos y específicos por entidad/programa
1.2 Stack Tecnológico
Componente	Tecnología
Core CMS	Drupal 11 con módulo jaraba_diagnostic custom
Formularios	Webform + lógica condicional avanzada
Motor de Scoring	PHP custom con algoritmos ponderados por sector
Visualización	Chart.js para gráficos radar y barras
Automatización	ECA Module para flujos post-diagnóstico
Notificaciones	ActiveCampaign + Drupal Queue
 
2. Arquitectura de Entidades
El sistema de diagnóstico introduce 5 nuevas entidades Drupal personalizadas que extienden el esquema base definido en 01_Core_Entidades.
2.1 Entidad: business_diagnostic
Representa un diagnóstico empresarial completo. Agrupa todas las secciones evaluadas y almacena los resultados calculados.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno autoincremental	PRIMARY KEY
uuid	UUID	Identificador único global	UNIQUE, NOT NULL, INDEX
user_id	INT	Usuario que realiza diagnóstico	FK users.uid, NOT NULL, INDEX
tenant_id	INT	Tenant/programa asociado	FK tenant.id, NULLABLE, INDEX
business_name	VARCHAR(255)	Nombre del negocio	NOT NULL
business_sector	VARCHAR(32)	Sector del negocio	ENUM: comercio|servicios|agro|hosteleria|industria|otros
business_size	VARCHAR(16)	Tamaño del negocio	ENUM: solo|micro|pequena|mediana
business_age_years	INT	Años de operación	NOT NULL, >= 0
annual_revenue	DECIMAL(12,2)	Facturación anual estimada	NULLABLE, >= 0
maturity_ttv_id	INT	Referencia a Calculadora TTV	FK maturity_assessment.id, NULLABLE
overall_score	DECIMAL(5,2)	Puntuación global 0-100	COMPUTED, RANGE 0-100
maturity_level	VARCHAR(24)	Nivel de madurez	ENUM: analogico|basico|conectado|digitalizado|inteligente
estimated_loss_annual	DECIMAL(10,2)	Pérdida estimada €/año	COMPUTED, >= 0
priority_gaps	JSON	Gaps prioritarios detectados	Array of {area, score, priority}
recommended_path_id	INT	Itinerario recomendado	FK digitalization_path.id, NULLABLE
status	VARCHAR(16)	Estado del diagnóstico	ENUM: in_progress|completed|archived
completed_at	DATETIME	Fecha de completitud	NULLABLE, UTC
created	DATETIME	Fecha de creación	NOT NULL, UTC
changed	DATETIME	Última modificación	NOT NULL, UTC
 
2.2 Entidad: diagnostic_section
Define las áreas de evaluación del diagnóstico. Cada sección agrupa preguntas relacionadas y tiene peso específico en el scoring.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
machine_name	VARCHAR(64)	Identificador máquina	UNIQUE, NOT NULL, INDEX
title	VARCHAR(128)	Título de la sección	NOT NULL
description	TEXT	Descripción detallada	NULLABLE
icon	VARCHAR(64)	Icono (FontAwesome/SVG)	DEFAULT 'fa-chart-line'
weight	INT	Orden de presentación	DEFAULT 0, INDEX
scoring_weight	DECIMAL(3,2)	Peso en scoring global	RANGE 0-1, suma total = 1
sector_weights	JSON	Pesos por sector	{comercio: 0.2, servicios: 0.15, ...}
max_score	INT	Puntuación máxima sección	DEFAULT 100
is_required	BOOLEAN	Sección obligatoria	DEFAULT TRUE
is_active	BOOLEAN	Sección activa	DEFAULT TRUE
Secciones Predefinidas
machine_name	Título	Peso Base	Preguntas
online_presence	Presencia Online	0.20	8
digital_operations	Operaciones Digitales	0.20	10
digital_sales	Ventas Digitales	0.25	12
digital_marketing	Marketing Digital	0.20	10
automation_ai	Automatización e IA	0.15	8
 
2.3 Entidad: diagnostic_question
Preguntas individuales del diagnóstico. Soporta múltiples tipos: single choice, multiple choice, scale, yes/no.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
section_id	INT	Sección padre	FK diagnostic_section.id, NOT NULL
question_text	TEXT	Texto de la pregunta	NOT NULL
question_type	VARCHAR(24)	Tipo de pregunta	ENUM: single|multiple|scale|boolean|open
help_text	TEXT	Texto de ayuda/tooltip	NULLABLE
options	JSON	Opciones de respuesta	[{value, label, score}]
weight	INT	Orden en la sección	DEFAULT 0
max_score	INT	Puntuación máxima	DEFAULT 10
conditional_logic	JSON	Lógica condicional	{show_if: {question_id, operator, value}}
sector_relevance	JSON	Relevancia por sector	{comercio: 1, servicios: 0.8, ...}
is_required	BOOLEAN	Respuesta obligatoria	DEFAULT TRUE
 
2.4 Entidad: diagnostic_answer
Almacena las respuestas individuales de cada diagnóstico realizado.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
diagnostic_id	INT	Diagnóstico padre	FK business_diagnostic.id, NOT NULL, INDEX
question_id	INT	Pregunta respondida	FK diagnostic_question.id, NOT NULL
answer_value	TEXT	Valor de la respuesta	NOT NULL
answer_score	DECIMAL(5,2)	Puntuación obtenida	COMPUTED, RANGE 0-max_score
answered_at	DATETIME	Timestamp de respuesta	NOT NULL, UTC
Índice único compuesto: UNIQUE INDEX (diagnostic_id, question_id)
2.5 Entidad: diagnostic_recommendation
Recomendaciones personalizadas generadas según los gaps detectados.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
diagnostic_id	INT	Diagnóstico asociado	FK business_diagnostic.id, NOT NULL
section_id	INT	Sección del gap	FK diagnostic_section.id, NOT NULL
recommendation_type	VARCHAR(24)	Tipo de recomendación	ENUM: quick_win|medium_term|strategic
title	VARCHAR(255)	Título de la acción	NOT NULL
description	TEXT	Descripción detallada	NOT NULL
estimated_impact	DECIMAL(10,2)	Impacto económico €	NULLABLE, >= 0
estimated_effort	VARCHAR(16)	Esfuerzo estimado	ENUM: low|medium|high
priority_order	INT	Orden de prioridad	COMPUTED, INDEX
linked_resource_id	INT	Recurso/Kit asociado	FK digital_kit.id, NULLABLE
linked_path_step_id	INT	Paso de itinerario	FK path_step.id, NULLABLE
 
3. Motor de Scoring
El algoritmo de scoring transforma las respuestas en una puntuación global ponderada, ajustada por sector y tamaño de negocio.
3.1 Fórmula de Scoring Global
overall_score = Σ (section_score × section_weight × sector_modifier)
Donde:
•	section_score: (Σ answer_score / Σ max_score) × 100 para cada sección
•	section_weight: Peso base de la sección (suma total = 1)
•	sector_modifier: Ajuste según sector (ej: ventas digitales pesa más en comercio)
3.2 Niveles de Madurez
Nivel	Rango Score	machine_name	Descripción
Analógico	0 - 20	analogico	Sin presencia digital, operaciones 100% manuales
Básico	21 - 40	basico	Presencia mínima, herramientas aisladas
Conectado	41 - 60	conectado	Canales digitales activos, sin integración
Digitalizado	61 - 80	digitalizado	Procesos digitalizados, datos centralizados
Inteligente	81 - 100	inteligente	Automatización avanzada, IA integrada
3.3 Estimación de Pérdidas Económicas
La "cifra que duele" se calcula combinando gaps detectados con benchmarks sectoriales:
estimated_loss = Σ (gap_severity × sector_loss_factor × annual_revenue_factor)
Gap	Factor Pérdida (% revenue)	Fuente Benchmark
Sin web/landing	8-15%	McKinsey Digital, 2024
Sin e-commerce	12-25%	Statista eCommerce Report
Sin CRM/automatización	5-10%	Salesforce State of Sales
Sin marketing digital	10-20%	HubSpot Marketing Report
 
4. APIs REST
El módulo jaraba_diagnostic expone los siguientes endpoints RESTful. Todos requieren autenticación OAuth2.
4.1 Endpoints de Diagnósticos
Método	Endpoint	Descripción
POST	/api/v1/diagnostics	Crear nuevo diagnóstico
GET	/api/v1/diagnostics/{uuid}	Obtener diagnóstico por UUID
PATCH	/api/v1/diagnostics/{uuid}	Actualizar diagnóstico en progreso
POST	/api/v1/diagnostics/{uuid}/answers	Enviar respuestas de una sección
GET	/api/v1/diagnostics/{uuid}/results	Obtener resultados calculados
GET	/api/v1/diagnostics/{uuid}/recommendations	Obtener recomendaciones personalizadas
GET	/api/v1/diagnostics/{uuid}/report.pdf	Descargar informe PDF
GET	/api/v1/users/{uid}/diagnostics	Listar diagnósticos del usuario
 
5. Flujos de Automatización ECA
5.1 ECA-DIAG-001: Post Calculadora TTV
Trigger: Creación de maturity_assessment con score < 60
•	Crear business_diagnostic vinculado al usuario
•	Pre-poblar maturity_ttv_id con el assessment
•	Enviar email de invitación al diagnóstico profundo
•	Webhook a ActiveCampaign: tag 'diagnostic_invited'
5.2 ECA-DIAG-002: Diagnóstico Completado
Trigger: business_diagnostic.status = 'completed'
•	Ejecutar motor de scoring completo
•	Calcular estimated_loss_annual
•	Generar diagnostic_recommendations
•	Asignar recommended_path_id según maturity_level
•	Crear enrollment en digitalization_path recomendado
•	Enviar email con resumen de resultados
•	Si hay mentor disponible, programar sesión introductoria
5.3 ECA-DIAG-003: Seguimiento No Completado
Trigger: CRON diario, 72h sin actividad en diagnóstico 'in_progress'
•	Enviar email recordatorio personalizado
•	Si 7 días sin completar: segundo recordatorio con incentivo
•	Si 14 días: marcar como 'abandoned', analytics de dropout
 
6. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 1	Semana 1-2	Módulo jaraba_diagnostic: entidades diagnostic_section, diagnostic_question. Migrations. Admin UI.	Core entities
Sprint 2	Semana 3-4	Entidades business_diagnostic, diagnostic_answer, diagnostic_recommendation. Motor de scoring v1.	Sprint 1
Sprint 3	Semana 5-6	APIs REST completas. Frontend wizard multi-step. Lógica condicional de preguntas.	Sprint 2
Sprint 4	Semana 7-8	Flujos ECA. Integración ActiveCampaign. Generador de recomendaciones.	Sprint 3 + ECA
Sprint 5	Semana 9-10	Panel de resultados. Gráficos Chart.js. Export PDF. Vinculación con itinerarios.	Sprint 4
Sprint 6	Semana 11-12	QA completo. Carga de preguntas por sector. Piloto con 20 usuarios. Go-live.	Sprint 5
--- Fin del Documento ---

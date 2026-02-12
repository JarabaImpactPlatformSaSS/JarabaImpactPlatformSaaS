AI AGENT FLOWS
Sistema de Agentes Autónomos con Workflows Inteligentes
Plataforma Core - Gap #1 Crítico
JARABA IMPACT PLATFORM
Documento Técnico de Implementación

Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	108_Platform_AI_Agent_Flows
Dependencias:	06_Core_Flujos_ECA, 20_AI_Copilot, jaraba_ai module
 
1. Resumen Ejecutivo
Este documento especifica la arquitectura técnica del sistema de Agent Flows para la Jaraba Impact Platform. Los Agent Flows permiten crear agentes IA autónomos que ejecutan workflows determinísticos mejorados con inteligencia artificial, superando las limitaciones de los copilotos conversacionales actuales.
1.1 Capacidades del Sistema
Capacidad	Descripción	Ejemplo de Uso
Deterministic Flows	Workflows con pasos predefinidos ejecutados secuencialmente	Procesar factura → Validar → Registrar → Notificar
AI Decision Points	Nodos donde la IA toma decisiones basadas en contexto	Clasificar tipo de consulta → Rutear a agente especializado
Human-in-the-Loop	Puntos de aprobación humana en decisiones críticas	Aprobar descuento > 20% antes de aplicar
File Processing	Recibir, procesar y generar documentos automáticamente	Recibir CV → Extraer datos → Actualizar perfil
Multi-Agent Orchestration	Coordinación entre múltiples agentes especializados	Agente Ventas + Agente Logística colaborando
Computer Use	Operar interfaces web sin API disponible	Completar formulario externo automáticamente
1.2 Principios de Diseño
•	Determinismo + IA: Workflows predecibles con decisiones inteligentes en puntos específicos
•	Auditabilidad: Cada paso logged con timestamp, input, output, y decisión tomada
•	Fail-safe: Rollback automático si un paso falla, escalación a humanos cuando necesario
•	Composabilidad: Flows pueden llamar a otros flows como sub-rutinas
•	Observabilidad: Métricas de ejecución, tiempos, costes de IA en tiempo real
2. Arquitectura Técnica
2.1 Stack Tecnológico
Componente	Tecnología
Flow Engine	Drupal ECA Module extendido + jaraba_agent_flows custom module
LLM Orchestrator	LangGraph (Python) via FastAPI microservice
Queue System	Redis + BullMQ para jobs asíncronos
State Machine	XState para gestión de estados del flow
File Storage	S3-compatible (MinIO) para documentos procesados
Computer Use	Playwright + Browserless.io para automatización web
Monitoring	OpenTelemetry + Grafana para observabilidad
2.2 Diagrama de Arquitectura
El sistema se compone de tres capas principales:
•	Capa de Definición: UI visual para diseñar flows (React Flow) + YAML/JSON para configuración
•	Capa de Ejecución: Motor que interpreta y ejecuta los flows con gestión de estado
•	Capa de Integración: Conectores a sistemas internos (APIs Jaraba) y externos (webhooks, MCP)
3. Modelo de Datos
3.1 Entidad: agent_flow
Define un workflow de agente reutilizable.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
name	VARCHAR(128)	Nombre del flow	NOT NULL, UNIQUE per tenant
description	TEXT	Descripción para humanos	NULLABLE
tenant_id	INT	Tenant propietario	FK groups.id, INDEX
trigger_type	VARCHAR(32)	Cómo se activa	ENUM: manual|webhook|schedule|event
trigger_config	JSON	Configuración del trigger	Cron, webhook URL, event name
flow_definition	JSON	Definición de nodos y edges	NOT NULL, XState compatible
input_schema	JSON	Schema de inputs esperados	JSON Schema format
output_schema	JSON	Schema de outputs producidos	JSON Schema format
requires_approval	BOOLEAN	Requiere HITL	DEFAULT FALSE
max_execution_time	INT	Timeout en segundos	DEFAULT 300
retry_policy	JSON	Política de reintentos	max_retries, backoff_ms
is_active	BOOLEAN	Flow activo	DEFAULT TRUE
version	INT	Versión del flow	AUTO INCREMENT on edit
created_at	DATETIME	Fecha creación	NOT NULL, UTC
updated_at	DATETIME	Última modificación	NOT NULL, UTC
 
3.2 Entidad: agent_flow_execution
Registra cada ejecución de un flow.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
flow_id	INT	Flow ejecutado	FK agent_flow.id, INDEX
flow_version	INT	Versión del flow usada	NOT NULL
triggered_by	VARCHAR(32)	Origen de ejecución	ENUM: user|webhook|schedule|event
triggered_by_user	INT	Usuario que inició	FK users.uid, NULLABLE
status	VARCHAR(32)	Estado actual	ENUM: pending|running|paused|completed|failed|cancelled
current_step	VARCHAR(64)	Nodo actual en ejecución	NULLABLE
input_data	JSON	Datos de entrada	NOT NULL
output_data	JSON	Datos de salida	NULLABLE
state_snapshot	JSON	Estado XState completo	For resume after pause
error_message	TEXT	Mensaje de error si falló	NULLABLE
started_at	DATETIME	Inicio ejecución	NOT NULL, UTC
completed_at	DATETIME	Fin ejecución	NULLABLE, UTC
duration_ms	INT	Duración total	Computed on completion
total_llm_tokens	INT	Tokens LLM consumidos	For cost tracking
total_llm_cost	DECIMAL(10,4)	Coste LLM en USD	Calculated
3.3 Entidad: agent_flow_step_log
Log detallado de cada paso ejecutado.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
execution_id	INT	Ejecución padre	FK agent_flow_execution.id, INDEX
step_id	VARCHAR(64)	ID del nodo en flow	NOT NULL
step_type	VARCHAR(32)	Tipo de nodo	ENUM: action|decision|hitl|subflow|llm
input_data	JSON	Input del paso	NOT NULL
output_data	JSON	Output del paso	NULLABLE
decision_made	VARCHAR(128)	Decisión tomada si aplica	NULLABLE
llm_prompt	TEXT	Prompt enviado a LLM	NULLABLE
llm_response	TEXT	Respuesta del LLM	NULLABLE
tokens_used	INT	Tokens consumidos	NULLABLE
status	VARCHAR(16)	Estado del paso	ENUM: success|failed|skipped
error_message	TEXT	Error si falló	NULLABLE
started_at	DATETIME	Inicio paso	NOT NULL, UTC
completed_at	DATETIME	Fin paso	NOT NULL, UTC
duration_ms	INT	Duración	NOT NULL
 
4. Tipos de Nodos
4.1 Catálogo de Nodos Disponibles
Tipo de Nodo	Descripción	Configuración
action	Ejecuta una acción determinística	api_endpoint, method, payload_template
llm_decision	IA toma una decisión entre opciones	prompt_template, options[], model
llm_generate	IA genera contenido (texto, JSON)	prompt_template, output_schema
llm_extract	IA extrae datos de documento	document_field, extraction_schema
conditional	Branch basado en expresión	condition_expression, true_path, false_path
human_approval	Pausa para aprobación humana	approvers[], timeout_hours, escalation
wait	Espera tiempo o evento	duration_seconds | event_name
parallel	Ejecuta múltiples paths en paralelo	branches[], join_strategy
subflow	Llama otro flow como sub-rutina	flow_id, input_mapping
webhook	Envía webhook externo	url, method, headers, body_template
computer_use	Opera interfaz web sin API	url, instructions_prompt, screenshot_analysis
file_process	Procesa archivo subido	file_field, processor_type, output_field
4.2 Ejemplo de Flow Definition (JSON)
{ "id": "invoice_processing", "initial": "receive", "states": {
    "receive": { "type": "action", "config": { "action": "validate_file" }, "on": { "VALID": "extract" } },
    "extract": { "type": "llm_extract", "config": { "schema": "invoice_fields" }, "on": { "DONE": "decide" } },
    "decide": { "type": "llm_decision", "config": { "prompt": "¿Aprobar auto?", "options": ["auto", "human"] } },
    "human_review": { "type": "human_approval", "config": { "approvers": ["finance_manager"] } },
    "register": { "type": "action", "config": { "api": "/api/v1/invoices", "method": "POST" } },
    "complete": { "type": "final" }
  }
}
5. APIs REST
Método	Endpoint	Descripción
GET	/api/v1/agent-flows	Listar flows del tenant
POST	/api/v1/agent-flows	Crear nuevo flow
GET	/api/v1/agent-flows/{id}	Detalle de un flow
PUT	/api/v1/agent-flows/{id}	Actualizar flow (crea nueva versión)
DELETE	/api/v1/agent-flows/{id}	Desactivar flow
POST	/api/v1/agent-flows/{id}/execute	Ejecutar flow manualmente
GET	/api/v1/agent-flows/{id}/executions	Historial de ejecuciones
GET	/api/v1/executions/{id}	Detalle de ejecución
GET	/api/v1/executions/{id}/logs	Logs de pasos de ejecución
POST	/api/v1/executions/{id}/approve	Aprobar paso HITL
POST	/api/v1/executions/{id}/reject	Rechazar paso HITL
POST	/api/v1/executions/{id}/cancel	Cancelar ejecución
POST	/api/v1/executions/{id}/retry	Reintentar desde último fallo
 
6. Flows Predefinidos del Ecosistema
Flow	Descripción	Vertical
onboard_producer	Configurar tienda desde foto de producto	AgroConecta
process_application	Evaluar candidatura con IA + notificar	Empleabilidad
generate_business_plan	Crear plan de negocio desde Canvas	Emprendimiento
invoice_to_accounting	Procesar factura y registrar	ServiciosConecta
flash_offer_campaign	Crear y publicar oferta flash	ComercioConecta
mentor_match	Asignar mentor basado en perfil	Emprendimiento
certificate_issue	Generar y firmar certificado	Empleabilidad
lead_qualification	Calificar lead y asignar a sales	Core
7. Visual Flow Builder
7.1 Componentes UI
•	Canvas: React Flow con drag-and-drop de nodos, zoom, pan, minimap
•	Node Palette: Catálogo lateral con todos los tipos de nodos disponibles
•	Property Panel: Configuración del nodo seleccionado con validación en tiempo real
•	Test Console: Ejecutar flow en modo debug con inputs de prueba
•	Version History: Ver y restaurar versiones anteriores del flow
7.2 Características Avanzadas
•	Templates: Flows predefinidos como punto de partida
•	Import/Export: YAML y JSON para backup y migración
•	Collaboration: Edición simultánea con cursores de otros usuarios
•	AI Assistant: Sugerir nodos siguientes basado en contexto
8. Roadmap de Implementación
Sprint	Timeline	Entregables	Deps
Sprint 1	Semana 1-2	Entidades BD. API CRUD flows. Motor ejecución básico.	ECA Module
Sprint 2	Semana 3-4	Nodos action, conditional, webhook. State machine XState.	Sprint 1
Sprint 3	Semana 5-6	Nodos LLM (decision, generate, extract). Integration LangGraph.	Sprint 2, Qdrant
Sprint 4	Semana 7-8	Human-in-the-loop. UI aprobaciones. Notificaciones.	Sprint 3
Sprint 5	Semana 9-10	Visual Flow Builder (React Flow). Import/Export YAML.	Sprint 4
Sprint 6	Semana 11-12	Computer use (Playwright). File processing. Parallel nodes.	Sprint 5
Sprint 7	Semana 13-14	Flows predefinidos. Testing. Documentación. Go-live.	Sprint 6
8.1 Estimación de Esfuerzo
Componente	Horas	Prioridad
Flow Engine + State Machine	80-100	P0
Nodos básicos (action, conditional, webhook)	40-50	P0
Nodos LLM con LangGraph	60-80	P0
Human-in-the-loop system	40-50	P1
Visual Flow Builder	80-100	P1
Computer use integration	30-40	P2
Flows predefinidos (8 flows)	40-60	P1
TOTAL	370-480	-
— Fin del Documento —

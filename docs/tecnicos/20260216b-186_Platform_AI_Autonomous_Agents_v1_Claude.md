
AI AUTONOMOUS AGENTS
Agentes IA con Ejecucion Autonoma y Guardrails de Seguridad
Nivel de Madurez: N2
JARABA IMPACT PLATFORM
Especificacion Tecnica para Implementacion
Version:	1.0
Fecha:	Febrero 2026
Codigo:	186_Platform_AI_Autonomous_Agents_v1
Estado:	Especificacion para EDI Google Antigravity
Nivel Madurez:	N2
Compliance:	GDPR, LOPD-GDD, ENS, ISO 27001
 
1. Resumen Ejecutivo
Especificacion de agentes IA que ejecutan acciones autonomas en la plataforma, mas alla de los Agent Flows del doc 108. Incluye auto-enrollment post-diagnostico, generacion automatica de planes de accion, respuesta autonoma de chatbot con escalacion, workflow agents para marketing automation, y guardrails de seguridad.

1.1 Agentes Autonomos vs Agent Flows
Caracteristica	Agent Flows (Doc 108)	Autonomous Agents (Este doc)
Ejecucion	Workflows predefinidos paso a paso	Decisiones autonomas con objetivos
Autonomia	IA decide en puntos especificos	IA decide la secuencia completa
Supervision	Human-in-the-loop obligatorio	Human-on-the-loop (supervision)
Alcance	Una tarea, un flujo	Multi-tarea, multi-contexto
Aprendizaje	No aprende entre ejecuciones	Mejora con feedback y metricas
Risk level	Bajo (determinista)	Medio (requiere guardrails)
 
2. Arquitectura de Agentes Autonomos
2.1 Modelo de Datos: autonomous_agent
Campo	Tipo	Descripcion
id	UUID	Identificador unico del agente
name	VARCHAR(100)	Nombre del agente
agent_type	ENUM	enrollment|planning|support|marketing|analytics
vertical	ENUM	empleabilidad|emprendimiento|agro|comercio|servicios|platform
objective	TEXT	Objetivo del agente en lenguaje natural
capabilities	JSON	Lista de acciones permitidas
guardrails	JSON	Restricciones y limites
llm_model	VARCHAR(50)	Modelo LLM a utilizar
temperature	DECIMAL(3,2)	Temperatura del LLM (0.0-1.0)
max_actions_per_run	INT	Maximo acciones por ejecucion
requires_approval	JSON	Acciones que requieren aprobacion humana
is_active	BOOLEAN	Agente activo
tenant_scope	ENUM	global|tenant_specific
performance_metrics	JSON	Metricas de rendimiento historicas

2.2 Modelo de Datos: agent_execution
Campo	Tipo	Descripcion
id	UUID	Identificador de ejecucion
agent_id	UUID FK	Agente que ejecuta
trigger_type	ENUM	scheduled|event|user_request|agent_chain
trigger_data	JSON	Datos del trigger
started_at	TIMESTAMP	Inicio de ejecucion
completed_at	TIMESTAMP	Fin de ejecucion
status	ENUM	running|completed|failed|paused|cancelled
actions_taken	JSON	Lista de acciones ejecutadas
decisions_made	JSON	Decisiones con reasoning
tokens_used	INT	Tokens LLM consumidos
cost_usd	DECIMAL(8,4)	Coste de la ejecucion
outcome	JSON	Resultado final
human_feedback	ENUM	approved|rejected|corrected|none
tenant_id	UUID FK	Tenant afectado
 
3. Agentes por Vertical
3.1 Agente de Auto-Enrollment (Empleabilidad)
Post-diagnostico de skills, inscribe automaticamente al candidato en los cursos y learning paths mas relevantes.
•	Trigger: Diagnostico de skills completado
•	Acciones: Analizar gaps de skills, seleccionar cursos del catalogo, crear learning path personalizado, inscribir al usuario, enviar email de bienvenida
•	Guardrails: Maximo 5 cursos simultaneos, solo cursos del plan del tenant, no inscribir en cursos que requieran prerequisitos no cumplidos

3.2 Agente de Planificacion (Emprendimiento)
Genera planes de accion automaticos basados en el diagnostico de negocio y la madurez digital.
•	Trigger: Diagnostico de negocio completado o actualizado
•	Acciones: Analizar diagnostico, generar plan de accion con milestones, asignar tareas con deadlines, recomendar recursos y herramientas, programar seguimientos
•	Guardrails: Planes revisables antes de activar, maximo 20 tareas por plan, alineados con verticales contratadas

3.3 Agente de Soporte (Chatbot Autonomo)
Responde consultas de usuarios usando la Knowledge Base con escalacion automatica a soporte humano.
•	Trigger: Usuario envia mensaje al chatbot
•	Acciones: Buscar en KB via RAG/Qdrant, generar respuesta grounded, ejecutar acciones simples (reset password, consultar estado), escalar si confianza < 0.7
•	Guardrails: Strict grounding obligatorio, no inventar datos, escalar siempre que involucre pagos o datos sensibles, maximo 5 turnos antes de ofrecer soporte humano

3.4 Agente de Marketing Automation
Ejecuta campanas de marketing automatizadas basadas en comportamiento del usuario y segmentacion.
•	Trigger: Evento de usuario (signup, inactividad 7d, carrito abandonado, trial ending)
•	Acciones: Segmentar usuario, seleccionar template de email, personalizar contenido, enviar via SendGrid, registrar en CRM pipeline
•	Guardrails: Respetar opt-outs, maximo 1 email/dia por usuario, A/B testing con sample minimo, no enviar en horario nocturno (22h-8h)

3.5 Agente de Analytics
Genera insights y alertas automaticas basadas en metricas de la plataforma.
•	Trigger: Scheduled (diario) o anomalia detectada
•	Acciones: Analizar metricas SaaS, detectar anomalias (churn spike, revenue drop), generar informe ejecutivo, enviar alertas a admins
•	Guardrails: Solo lectura de datos, no modificar configuracion, alertas con contexto y recomendacion, no alarmar innecesariamente
 
4. Sistema de Guardrails
4.1 Niveles de Autonomia
Nivel	Nombre	Descripcion	Aprobacion	Ejemplo
L0	Informativo	Solo genera informacion, no ejecuta	No	Resumen de analytics
L1	Sugerencia	Propone accion, espera aprobacion	Si, manual	Plan de accion propuesto
L2	Semi-autonomo	Ejecuta con confirmacion rapida	Si, 1-click	Inscripcion en curso
L3	Autonomo supervisado	Ejecuta y notifica despues	No, post-hoc	Respuesta chatbot
L4	Autonomo total	Ejecuta sin notificacion	No	Cache warmup, cleanup

4.2 Guardrails por Tipo
•	Token Budget: Limite de tokens LLM por ejecucion y por tenant/mes
•	Action Whitelist: Solo acciones explicitamente permitidas en la config del agente
•	Rate Limiting: Maximo N ejecuciones por hora/dia por agente
•	Scope Isolation: Agente solo accede a datos del tenant al que pertenece
•	Rollback Capability: Toda accion debe ser reversible o tener undo path
•	Confidence Threshold: Si confianza < umbral configurable, escalar a humano
•	Cost Ceiling: Detener ejecucion si coste LLM supera limite por run
•	Audit Trail: Cada decision y accion logged con reasoning completo
 
5. Implementacion Tecnica
5.1 Modulo: jaraba_agents
•	src/Agent/BaseAutonomousAgent.php: Clase base con lifecycle management
•	src/Agent/EnrollmentAgent.php: Agente de auto-enrollment
•	src/Agent/PlanningAgent.php: Agente de planificacion
•	src/Agent/SupportAgent.php: Agente de soporte chatbot
•	src/Agent/MarketingAgent.php: Agente de marketing automation
•	src/Agent/AnalyticsAgent.php: Agente de analytics
•	src/Service/AgentOrchestrator.php: Router y lifecycle manager
•	src/Service/GuardrailsEnforcer.php: Verificacion de limites y restricciones
•	src/Service/AgentMetricsCollector.php: Metricas de rendimiento

5.2 API REST
Endpoint	Metodo	Descripcion
/api/v1/agents	GET	Listar agentes disponibles para el tenant
/api/v1/agents/{id}/execute	POST	Disparar ejecucion de agente
/api/v1/agents/{id}/executions	GET	Historial de ejecuciones
/api/v1/agents/{id}/config	PATCH	Actualizar configuracion de agente
/api/v1/agents/executions/{id}/approve	POST	Aprobar accion pendiente
/api/v1/agents/executions/{id}/reject	POST	Rechazar accion pendiente
/api/v1/agents/metrics	GET	Metricas agregadas de todos los agentes
 
6. Estimacion de Implementacion
Componente	Horas	Coste EUR	Prioridad
BaseAutonomousAgent + Orchestrator	15-20h	675-900	CRITICA
Guardrails System	10-12h	450-540	CRITICA
Enrollment Agent	8-10h	360-450	ALTA
Planning Agent	10-12h	450-540	ALTA
Support Agent (Chatbot)	12-15h	540-675	ALTA
Marketing Agent	10-12h	450-540	MEDIA
Analytics Agent	8-10h	360-450	MEDIA
Admin UI + Metrics Dashboard	6-8h	270-360	MEDIA
TOTAL	79-99h	3,555-4,455	N2 DIFERENCIADOR

--- Fin del Documento ---
Jaraba Impact Platform | Especificacion Tecnica v1.0 | Febrero 2026

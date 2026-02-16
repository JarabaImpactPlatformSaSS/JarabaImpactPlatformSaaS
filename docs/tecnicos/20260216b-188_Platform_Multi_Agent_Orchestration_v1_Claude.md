
MULTI-AGENT ORCHESTRATION
Orquestacion de Agentes IA Especializados con Memoria Compartida
Nivel de Madurez: N2
JARABA IMPACT PLATFORM
Especificacion Tecnica para Implementacion
Version:	1.0
Fecha:	Febrero 2026
Codigo:	188_Platform_Multi_Agent_Orchestration_v1
Estado:	Especificacion para EDI Google Antigravity
Nivel Madurez:	N2
Compliance:	GDPR, LOPD-GDD, ENS, ISO 27001
 
1. Resumen Ejecutivo
Sistema de orquestacion de multiples agentes IA especializados que colaboran para resolver tareas complejas. Incluye Agent Router, agentes especialistas por vertical, memoria compartida via Qdrant, protocolo de handoff entre agentes y observabilidad de cadenas de agentes.

1.1 Arquitectura Multi-Agent
Componente	Funcion	Tecnologia
Agent Router	Decide que agente atiende cada solicitud	LLM classifier + rules
Specialist Agents	Agentes expertos por dominio/vertical	LLM + RAG vertical
Shared Memory	Contexto compartido entre agentes	Qdrant collections
Handoff Protocol	Transferencia de contexto entre agentes	Custom protocol
Orchestrator	Coordina cadenas de agentes	ECA + custom PHP
Observer	Monitoring y debugging de cadenas	OpenTelemetry
 
2. Modelo de Datos
2.1 agent_conversation
Campo	Tipo	Descripcion
id	UUID	Identificador de conversacion
user_id	UUID FK	Usuario que inicio
current_agent	UUID FK	Agente actualmente activo
agent_chain	JSON	Secuencia de agentes que han participado
shared_context	JSON	Contexto acumulado entre agentes
handoff_count	INT	Numero de handoffs realizados
started_at	TIMESTAMP	Inicio de la conversacion
status	ENUM	active|completed|escalated|timeout
satisfaction_score	INT	Puntuacion del usuario (1-5)
total_tokens	INT	Tokens totales consumidos
tenant_id	UUID FK	Tenant del usuario

2.2 agent_handoff
Campo	Tipo	Descripcion
id	UUID	Identificador del handoff
conversation_id	UUID FK	Conversacion parent
from_agent	UUID FK	Agente que transfiere
to_agent	UUID FK	Agente que recibe
reason	TEXT	Razon del handoff
context_transferred	JSON	Contexto transferido
confidence	DECIMAL(3,2)	Confianza en la decision de routing
handoff_at	TIMESTAMP	Momento del handoff
 
3. Agent Router: Clasificacion Inteligente
3.1 Estrategia de Routing
Senal	Agente Destino	Ejemplo
Keywords empleo/CV/oferta	Empleabilidad Specialist	Como mejorar mi CV?
Keywords negocio/plan/emprender	Emprendimiento Specialist	Necesito un plan de negocio
Keywords producto/pedido/envio	AgroConecta/ComercioConecta	Estado de mi pedido #123
Keywords factura/presupuesto/cita	ServiciosConecta Specialist	Necesito presupuesto
Keywords cuenta/plan/facturacion	Platform Support Agent	Como cambio mi plan?
Keywords tecnico/error/bug	Technical Support Agent	La pagina da error 500
Intent no claro / confianza < 0.6	General Triage Agent	Cualquier consulta ambigua

3.2 Handoff Protocol
1.	Agente actual detecta que la consulta esta fuera de su dominio
2.	Agente empaqueta contexto relevante (resumen, datos extraidos, intent)
3.	Router evalua que agente especialista es el mas adecuado
4.	Se transfiere el contexto al nuevo agente
5.	Nuevo agente confirma recepcion y continua la conversacion
6.	Usuario recibe transicion transparente (sin repetir informacion)
 
4. Memoria Compartida via Qdrant
4.1 Collections Compartidas
Collection	Contenido	Acceso
agent_memory_{tenant}	Contexto conversacional del tenant	Todos los agentes del tenant
user_profile_{tenant}	Perfil enriquecido del usuario	Todos los agentes del tenant
knowledge_{vertical}	KB especifica de cada vertical	Agentes de esa vertical
platform_knowledge	KB global de la plataforma	Todos los agentes
agent_learnings	Feedback y mejoras de agentes	Orchestrator
 
5. Observabilidad
5.1 Metricas de Agentes
Metrica	Descripcion	Alerta Si
Resolution Rate	% de conversaciones resueltas sin escalacion	< 70%
Avg Handoffs	Promedio de handoffs por conversacion	> 3
Avg Tokens/Conversation	Tokens LLM consumidos por conversacion	> 10,000
CSAT Score	Satisfaccion del usuario (1-5)	< 3.5
Escalation Rate	% escalado a soporte humano	> 30%
Avg Response Time	Tiempo medio de respuesta del agente	> 5s
Cost per Conversation	Coste LLM por conversacion	> $0.50
 
6. Estimacion de Implementacion
Componente	Horas	Coste EUR	Prioridad
Agent Router + Classifier	12-15h	540-675	CRITICA
Specialist Agents (5 verticales)	20-25h	900-1,125	ALTA
Handoff Protocol	8-10h	360-450	CRITICA
Shared Memory (Qdrant)	8-10h	360-450	ALTA
Orchestrator Engine	10-12h	450-540	ALTA
Observability + Dashboard	8-10h	360-450	MEDIA
TOTAL	66-82h	2,970-3,690	N2

--- Fin del Documento ---
Jaraba Impact Platform | Especificacion Tecnica v1.0 | Febrero 2026

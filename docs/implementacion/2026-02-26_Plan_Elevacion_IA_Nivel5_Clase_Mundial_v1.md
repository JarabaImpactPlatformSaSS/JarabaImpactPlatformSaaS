# Plan: Elevacion IA a Nivel 5/5 — Clase Mundial

**Fecha:** 2026-02-26
**Version:** 1.0
**Estado:** En implementacion
**Autor:** Claude Opus 4.6

---

## Contexto

El stack IA del SaaS tiene una base solida (agentes Gen 2, model routing, guardrails, RAG, observabilidad, 10 verticales) pero esta fragmentado: el framework de herramientas (ToolRegistry con 3 tools) no esta conectado a los agentes, el modulo de agentes autonomos (`jaraba_agents`) con entidades AutonomousAgent/AgentExecution/AgentApproval no ejecuta LLM realmente, el QualityEvaluatorService existe pero no esta wired al pipeline, y varios agentes Gen 1 no tienen model routing.

**Estado actual: ~Nivel 3/5. Objetivo: Nivel 5/5.**

La clave: MUCHA infraestructura ya existe pero esta DESCONECTADA. Los mayores wins vienen de conectar piezas existentes, no de construir desde cero.

---

## Fase 1 — Conectar Infraestructura Existente (P0, 9 FIX items)

### FIX-029: Tool Use en SmartBaseAgent
**Problema:** ToolRegistry tiene `generateToolsDocumentation()` y `execute()`, 3 tools concretos (SendEmail, CreateEntity, SearchKnowledge), pero NINGUN agente los usa.
**Solucion:** Inyectar ToolRegistry opcionalmente en SmartBaseAgent. En `buildSystemPrompt()`, si hay tools disponibles, appendear la documentacion XML. Anadir metodo `callAiApiWithTools()` que implementa loop iterativo: llamar LLM -> parsear tool_call JSON -> ejecutar tool -> appendear resultado -> rellamar LLM (max 5 iteraciones).
**Ficheros:**
- `jaraba_ai_agents/src/Agent/SmartBaseAgent.php` — Anadir propiedad `?ToolRegistry $toolRegistry`, setter `setToolRegistry()`, modificar `buildSystemPrompt()` para appendear tool docs, anadir `callAiApiWithTools()`
- `jaraba_ai_agents/jaraba_ai_agents.services.yml` — Anadir `@?jaraba_ai_agents.tool_registry` como argumento a los 4 agentes Gen 2
**Reutilizar:** `ToolRegistry::generateToolsDocumentation()` (linea 177-209), `ToolRegistry::execute()` (linea 128-167), `BaseAgent::parseJsonResponse()`

### FIX-030: Bridge Agentes Autonomos <-> SmartBaseAgent
**Problema:** `AgentOrchestratorService::execute()` crea AgentExecution y gestiona estado, pero NUNCA llama a un LLM. Linea 182: retorna `success=true, status=running` sin hacer trabajo real.
**Solucion:** Crear `AgentExecutionBridgeService` que mapea `AutonomousAgent.agent_type` a service IDs concretos de `jaraba_ai_agents`, resuelve el agente, establece tenant context, y ejecuta la accion.
**Ficheros:**
- **NUEVO:** `jaraba_agents/src/Service/AgentExecutionBridgeService.php`
- `jaraba_agents/src/Service/AgentOrchestratorService.php` — Inyectar bridge, llamar despues de linea 175 (metrics record)
- `jaraba_agents/jaraba_agents.services.yml` — Registrar bridge service
- **NUEVO:** `jaraba_agents/config/install/jaraba_agents.agent_type_mapping.yml` — Mapeo agent_type -> service_id

### FIX-031: Provider Fallback Chain
**Problema:** Si el provider IA falla (rate limit, outage), la peticion falla completamente. No hay retry ni fallback.
**Solucion:** Crear `ProviderFallbackService` con circuit breaker (3 fallos en 5 min = skip provider) y cadena de fallback por tier.
**Ficheros:**
- **NUEVO:** `jaraba_ai_agents/src/Service/ProviderFallbackService.php`
- `jaraba_ai_agents/src/Agent/SmartBaseAgent.php` — En `callAiApi()`, usar fallback service en vez de llamada directa (inyeccion opcional `@?`)
- `jaraba_ai_agents/jaraba_ai_agents.services.yml` — Registrar servicio
- **NUEVO:** `jaraba_ai_agents/config/install/jaraba_ai_agents.provider_fallback.yml` — Cadenas por tier
- **NUEVO:** `jaraba_ai_agents/config/schema/jaraba_ai_agents.schema.yml` — Anadir schema para provider_fallback

### FIX-032: Evaluation Pipeline (conectar QualityEvaluator al pipeline)
**Problema:** `QualityEvaluatorService` esta implementado completo (5 criterios, LLM-as-Judge, JSON parsing) pero NUNCA se llama desde el pipeline de agentes.
**Solucion:** Crear QueueWorker que evalua respuestas en background. SmartBaseAgent encola evaluacion despues de cada ejecucion exitosa (sampling 10%, 100% premium tier).
**Ficheros:**
- **NUEVO:** `jaraba_ai_agents/src/Plugin/QueueWorker/QualityEvaluationWorker.php` — Drupal Queue worker
- `jaraba_ai_agents/src/Agent/SmartBaseAgent.php` — Despues de `callAiApi()` exitoso, encolar evaluacion via `\Drupal::queue()`
- `jaraba_ai_agents/jaraba_ai_agents.services.yml` — Registrar queue worker

### FIX-033: Context Window Manager
**Problema:** No hay verificacion de que el prompt completo (system + user + tools + context) quepa en la ventana del modelo. Risk de truncamiento silencioso.
**Solucion:** Crear `ContextWindowManager` que estima tokens, verifica limites por modelo, y recorta progresivamente (RAG context -> tool docs -> knowledge) si excede.
**Ficheros:**
- **NUEVO:** `jaraba_ai_agents/src/Service/ContextWindowManager.php` — `estimateTokens()`, `fitToWindow()`, `getModelLimit()`
- `jaraba_ai_agents/src/Agent/SmartBaseAgent.php` — Llamar antes de enviar a LLM
- `jaraba_ai_agents/jaraba_ai_agents.services.yml` — Registrar servicio

### FIX-034: User Feedback Loop (thumbs up/down)
**Problema:** No hay mecanismo para recoger feedback del usuario sobre respuestas IA.
**Solucion:** Crear entidad `AiFeedback` y endpoint API. El frontend (copilot widget) envia rating + comentario. El SSE `done` event incluye `response_id` para correlacionar.
**Ficheros:**
- **NUEVO:** `jaraba_ai_agents/src/Entity/AiFeedback.php`
- **NUEVO:** `jaraba_ai_agents/src/Controller/AiFeedbackController.php`
- **NUEVO:** `jaraba_ai_agents/src/AiFeedbackAccessControlHandler.php`
- **NUEVO:** `jaraba_ai_agents/src/AiFeedbackListBuilder.php`
- `jaraba_copilot_v2/src/Controller/CopilotStreamController.php` — Incluir log_id en evento `done`
- `jaraba_copilot_v2/js/copilot-chat-widget.js` — Anadir botones thumbs up/down
- `jaraba_ai_agents/jaraba_ai_agents.routing.yml` — Ruta del endpoint

### FIX-035: Gen 1 -> Gen 2 Migration (3 agentes)
**Problema:** StorytellingAgent, CustomerExperienceAgent, SupportAgent extienden BaseAgent (sin model routing).
**Solucion:** Cambiar `extends BaseAgent` a `extends SmartBaseAgent`, refactorizar `execute()` a `doExecute()`, inyectar ModelRouterService.
**Ficheros:**
- `jaraba_ai_agents/src/Agent/StorytellingAgent.php`
- `jaraba_ai_agents/src/Agent/CustomerExperienceAgent.php`
- `jaraba_ai_agents/src/Agent/SupportAgent.php`
- `jaraba_ai_agents/jaraba_ai_agents.services.yml`

### FIX-036: Semantic Caching
**Problema:** Cache actual por hash exacto de query. Queries semanticamente equivalentes son cache miss.
**Solucion:** Cache layer basado en embeddings con Qdrant `semantic_cache` collection y threshold 0.92.
**Ficheros:**
- **NUEVO:** `jaraba_copilot_v2/src/Service/SemanticCacheService.php`
- `jaraba_copilot_v2/src/Service/CopilotCacheService.php`
- `jaraba_rag/src/Service/JarabaRagService.php`

### FIX-037: RAG Re-ranking con LLM
**Problema:** El re-ranking actual es keyword-overlap basico.
**Solucion:** Crear `LlmReRankerService` que usa tier `fast` (Haiku) para reordenar candidatos por relevancia.
**Ficheros:**
- **NUEVO:** `jaraba_rag/src/Service/LlmReRankerService.php`
- `jaraba_rag/src/Service/JarabaRagService.php`
- `jaraba_rag/jaraba_rag.services.yml`
- `jaraba_rag/config/install/jaraba_rag.settings.yml`

---

## Fase 2 — Capacidades Autonomas (P1, 8 FIX items)

### FIX-038: Plan-Execute-Reflect Loop (ReAct)
**Problema:** Agentes son single-shot. No hay razonamiento multi-paso.
**Solucion:** Crear `ReActLoopService` que orquesta ciclos: PLAN -> EXECUTE -> OBSERVE -> REFLECT -> FINISH.
**Ficheros:**
- **NUEVO:** `jaraba_ai_agents/src/Service/ReActLoopService.php`
- `jaraba_agents/src/Service/AgentOrchestratorService.php`
- `jaraba_ai_agents/jaraba_ai_agents.services.yml`
**Dependencias:** FIX-029, FIX-030

### FIX-039: Agent Long-Term Memory
**Problema:** Agentes no tienen memoria entre conversaciones.
**Solucion:** Crear `AgentLongTermMemoryService` backed por Qdrant + tabla BD.
**Ficheros:**
- **NUEVO:** `jaraba_agents/src/Service/AgentLongTermMemoryService.php`
- `jaraba_ai_agents/src/Agent/BaseAgent.php`
- `jaraba_agents/jaraba_agents.services.yml`

### FIX-040: Agent-to-Agent Handoff Decision
**Problema:** HandoffManagerService existe pero ningun agente inicia handoff.
**Solucion:** Crear `HandoffDecisionService` con clasificacion LLM.
**Ficheros:**
- **NUEVO:** `jaraba_ai_agents/src/Service/HandoffDecisionService.php`
- `jaraba_ai_agents/src/Agent/SmartBaseAgent.php`
- `jaraba_ai_agents/jaraba_ai_agents.services.yml`

### FIX-041: Scheduled Autonomous Tasks
**Problema:** AutonomousAgent tiene campo `trigger_type: schedule` pero no hay cron hook.
**Solucion:** Crear QueueWorker + hook_cron.
**Ficheros:**
- **NUEVO:** `jaraba_agents/src/Plugin/QueueWorker/ScheduledAgentWorker.php`
- `jaraba_agents/jaraba_agents.module`
- `jaraba_agents/jaraba_agents.services.yml`
**Dependencias:** FIX-030

### FIX-042: ComercioConecta Feature Gates
**Problema:** FreemiumVerticalLimit no provisionadas para comercioconecta.
**Solucion:** Crear configuracion de instalacion con limites freemium.
**Ficheros:**
- **NUEVO:** `ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.freemium_limits.comercioconecta.yml`

### FIX-043: Jailbreak Detection en Guardrails
**Problema:** AIGuardrailsService detecta PII pero no jailbreak.
**Solucion:** Anadir patrones de deteccion bilingue ES/EN.
**Ficheros:**
- `ecosistema_jaraba_core/src/Service/AIGuardrailsService.php`

### FIX-044: Output PII Masking
**Problema:** Guardrails validan INPUT pero no OUTPUT.
**Solucion:** Post-processing que escanea respuesta LLM y enmascara PII.
**Ficheros:**
- `ecosistema_jaraba_core/src/Service/AIGuardrailsService.php`
- `jaraba_ai_agents/src/Agent/SmartBaseAgent.php`

### FIX-045: New Tools (QueryDatabase, UpdateEntity, SearchContent)
**Problema:** Solo 3 tools basicos.
**Solucion:** 3 tools mas al ToolRegistry.
**Ficheros:**
- **NUEVO:** `jaraba_ai_agents/src/Tool/QueryDatabaseTool.php`
- **NUEVO:** `jaraba_ai_agents/src/Tool/UpdateEntityTool.php`
- **NUEVO:** `jaraba_ai_agents/src/Tool/SearchContentTool.php`
- `jaraba_ai_agents/jaraba_ai_agents.services.yml`

---

## Fase 3 — Inteligencia por Vertical (P2, 6 FIX items)

### FIX-046: Service Matching para ServiciosConecta
**Ficheros:**
- **NUEVO:** `jaraba_servicios_conecta/src/Service/ServiceMatchingService.php`
- `jaraba_servicios_conecta/jaraba_servicios_conecta.services.yml`

### FIX-047: Content Recommendations para Content Hub
**Ficheros:**
- `jaraba_content_hub/src/Service/RecommendationService.php`
- `jaraba_content_hub/src/Service/ContentEmbeddingService.php`

### FIX-048: Adaptive Learning para Formacion/LMS
**Ficheros:**
- **NUEVO:** `jaraba_lms/src/Service/AdaptiveLearningService.php`
- `jaraba_lms/jaraba_lms.services.yml`

### FIX-049: Prompt A/B Testing Integration
**Ficheros:**
- `jaraba_ai_agents/src/Agent/SmartBaseAgent.php`
- `jaraba_ai_agents/src/Service/PromptExperimentService.php`

### FIX-050: Predictive Job Matching Enhancement
**Ficheros:**
- `jaraba_matching/src/Service/MatchingService.php`
- `jaraba_matching/config/install/jaraba_matching.settings.yml`

### FIX-051: Real-Time Cost Alerts
**Ficheros:**
- **NUEVO:** `ecosistema_jaraba_core/src/Service/CostAlertService.php`
- `ecosistema_jaraba_core/src/Service/AIOpsService.php`

---

## Resumen de Impacto

| Fase | FIX Items | Ficheros Nuevos | Ficheros Modificados | Nivel Alcanzado |
|------|-----------|-----------------|---------------------|-----------------|
| Fase 1 | FIX-029 a FIX-037 (9) | ~12 | ~15 | 3 -> 4.0 |
| Fase 2 | FIX-038 a FIX-045 (8) | ~8 | ~8 | 4.0 -> 4.5 |
| Fase 3 | FIX-046 a FIX-051 (6) | ~5 | ~8 | 4.5 -> 5.0 |
| **Total** | **23 FIX items** | **~25** | **~31** | **5.0/5** |

## Orden de Ejecucion

1. **FIX-029** (Tool Use) — Foundation, desbloquea autonomia
2. **FIX-030** (Bridge) — Conecta los dos modulos de agentes
3. **FIX-031** (Fallback) — Fiabilidad produccion
4. **FIX-032** (Evaluation) — Calidad medible
5. **FIX-033** (Context Window) — Previene fallos silenciosos
6. **FIX-034** (Feedback) — Datos para mejora continua
7. **FIX-035** (Gen Migration) — Arquitectura unificada
8. **FIX-036** (Semantic Cache) — Reduccion costes
9. **FIX-037** (Re-ranking) — Calidad RAG
10. **FIX-038** (ReAct Loop) — Autonomia multi-paso
11. **FIX-039** (Memory) — Persistencia cross-sesion
12. **FIX-040** (Handoff) — Multi-agente
13. **FIX-041** (Scheduled) — Automatizacion background
14. **FIX-042** (ComercioConecta) — Config missing
15. **FIX-043** (Jailbreak) — Seguridad
16. **FIX-044** (Output PII) — Seguridad
17. **FIX-045** (New Tools) — Mas capacidades
18-23. **Fase 3** (Verticales y optimizacion)

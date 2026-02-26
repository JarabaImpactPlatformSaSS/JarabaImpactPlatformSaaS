# Arquitectura: Elevacion IA a Nivel 5/5 — Clase Mundial

**Fecha:** 2026-02-26
**Version:** 1.0.0
**Estado:** Implementado
**Referencia Plan:** `docs/implementacion/2026-02-26_Plan_Elevacion_IA_Nivel5_Clase_Mundial_v1.md`

---

## 1. Contexto y Problema

El stack IA del SaaS tenia una base solida (agentes Gen 2, model routing, guardrails, RAG, observabilidad, 10 verticales) pero estaba fragmentado:

- **ToolRegistry** con 3 tools concretos (SendEmail, CreateEntity, SearchKnowledge) pero NINGUN agente los usaba
- **AgentOrchestratorService** con entidades AutonomousAgent/AgentExecution/AgentApproval pero sin ejecucion LLM real
- **QualityEvaluatorService** implementado completo pero nunca llamado desde el pipeline
- **HandoffManagerService** y **AgentCollaborationService** con CRUD pero sin que ningun agente iniciara handoffs
- Agentes Gen 1 (Storytelling, CustomerExperience, Support) sin model routing
- Cache por hash exacto sin matching semantico
- Sin verificacion de context window ni provider fallback

**Estado inicial: ~Nivel 3/5. Objetivo: Nivel 5/5.**

---

## 2. Arquitectura Resultante

### 2.1 Pipeline de Ejecucion de Agentes (SmartBaseAgent)

```
[User Request]
    ↓
[SmartBaseAgent.execute()]
    ↓
[applyPromptExperiment()] ← A/B testing via PromptExperimentService
    ↓
[doExecute()] ← Implementado por cada agente concreto
    ↓
[buildSystemPrompt()]
    ├── AIIdentityRule::apply()
    ├── Brand Voice (TenantBrandVoiceService)
    ├── Agent-specific prompt
    ├── ToolRegistry::generateToolsDocumentation() ← FIX-029
    └── Agent Memory (recall) ← FIX-039
    ↓
[ContextWindowManager::fitToWindow()] ← FIX-033
    ├── estimateTokens() — ceil(mb_strlen / 4)
    ├── getModelLimit() — por modelo (128K, 200K, etc.)
    └── Recorte progresivo: RAG context → tool docs → knowledge
    ↓
[AIGuardrailsService::validate()] ← Input
    ├── checkPII() — ES + US patterns
    ├── checkJailbreak() ← FIX-043
    └── checkBlockedPatterns()
    ↓
[ProviderFallbackService::callWithFallback()] ← FIX-031
    ├── Circuit breaker: 3 fallos en 5min = skip provider
    ├── Cadena: primary → fallback → emergency
    └── Config en jaraba_ai_agents.provider_fallback.yml
    ↓
[callAiApiWithTools()] ← FIX-029 (si hay tools disponibles)
    ├── LLM call → parse {"tool_call": {...}} → execute → append → re-call
    ├── Max 5 iteraciones
    └── Tools: SendEmail, CreateEntity, SearchKnowledge, QueryDatabase, UpdateEntity, SearchContent
    ↓
[AIGuardrailsService::maskOutputPII()] ← FIX-044 (Output)
    ↓
[QualityEvaluationWorker] ← FIX-032 (Background queue, 10% sampling)
    ↓
[AIObservabilityService::log()] ← Tokens, duration, success, tier
    ↓
[AgentLongTermMemoryService::remember()] ← FIX-039
    ↓
[Response to User]
```

### 2.2 Constructor de Agentes Gen 2 (10 Args)

```php
public function __construct(
    AiProviderPluginManager $aiProvider,       // #1 Core
    ConfigFactoryInterface $configFactory,      // #2 Core
    LoggerInterface $logger,                    // #3 Core
    TenantBrandVoiceService $brandVoice,       // #4 Core
    AIObservabilityService $observability,      // #5 Core
    ModelRouterService $modelRouter,            // #6 Core
    ?UnifiedPromptBuilder $promptBuilder = NULL,// #7 Optional (@?)
    ?ToolRegistry $toolRegistry = NULL,         // #8 Optional (@?) FIX-029
    ?ProviderFallbackService $providerFallback = NULL,  // #9 Optional (@?) FIX-031
    ?ContextWindowManager $contextWindowManager = NULL,  // #10 Optional (@?) FIX-033
)
```

7 agentes Gen 2: SmartMarketing, Storytelling, CustomerExperience, Support, ProducerCopilot, Sales, MerchantCopilot.

### 2.3 Bridge Agentes Autonomos ↔ SmartBaseAgent (FIX-030)

```
[AutonomousAgent entity]
    ↓ agent_type = "marketing_automation"
[AgentOrchestratorService::execute()]
    ↓
[AgentExecutionBridgeService::resolve()]
    ↓ Mapeo via jaraba_agents.agent_type_mapping.yml
[SmartMarketingAgent::execute()]
    ↓
[Resultado real con LLM]
```

### 2.4 Cache de 2 Capas (FIX-036)

```
[Query del usuario]
    ↓
[Layer 1: Exact Match]
    ├── CopilotCacheService::generateCacheKey()
    ├── Hash MD5 de (message_normalizado + mode + stable_context)
    └── Drupal Cache API (Redis-backed)
    ↓ MISS
[Layer 2: Semantic Match]
    ├── SemanticCacheService::get()
    ├── generateEmbedding(query) via JarabaRagService
    ├── vectorSearch() en coleccion Qdrant "semantic_cache"
    ├── Threshold: 0.92 (alta precision)
    └── Retorna respuesta cacheada si match
    ↓ MISS
[LLM Call → Cache SET en ambas capas]
```

### 2.5 RAG con Re-ranking LLM (FIX-037)

```
[Query]
    ↓
[JarabaRagService::search()]
    ↓
[Qdrant vectorSearch] → candidatos raw
    ↓
[Temporal Decay] → penalizar documentos antiguos
    ↓
[reRankResults()] ← Config-driven: keyword | llm | hybrid
    ├── keyword: Overlap de palabras (existente)
    ├── llm: LlmReRankerService (tier fast/Haiku) reordena por relevancia
    └── hybrid: LLM + keyword combined score
    ↓
[Top-K resultados re-rankeados]
```

### 2.6 ReAct Loop — Razonamiento Multi-Paso (FIX-038)

```
[Objetivo del agente]
    ↓
[ReActLoopService::run(agent, objective, context, maxSteps=10)]
    ↓
    ┌──────────────────────────────┐
    │ PLAN: Descomponer en pasos  │
    │ EXECUTE: Ejecutar paso con  │
    │          tools              │
    │ OBSERVE: Recoger resultados │
    │ REFLECT: Ajustar plan       │
    │ → FINISH o → siguiente paso │
    └──────────────────────────────┘
    Cada paso logueado via AIObservabilityService
```

### 2.7 Guardrails Completos (FIX-043 + FIX-044)

```
[INPUT]                              [OUTPUT]
    ↓                                    ↓
[checkPII()]                         [maskOutputPII()]
├── DNI: /\b\d{8}[A-Za-z]\b/        ├── Mismos patrones PII
├── NIE: /\b[XYZ]\d{7}[A-Za-z]\b/   └── Reemplaza con [DATO PROTEGIDO]
├── IBAN ES, NIF/CIF, +34
├── SSN, US phone
    ↓
[checkJailbreak()] ← NUEVO
├── "ignore previous instructions"
├── "you are now", "DAN mode"
├── "pretend you are", "actua como"
├── "olvida tus instrucciones"
├── Role-play attacks
└── Bilingue ES/EN
    ↓
[ALLOW | MODIFY | BLOCK | FLAG]
```

### 2.8 User Feedback Loop (FIX-034)

```
[SSE done event] → incluye log_id
    ↓
[Frontend: thumbs up/down buttons]
    ↓
[POST /api/v1/ai/feedback]
    ├── response_id (correlacionado con log_id)
    ├── user_id, tenant_id
    ├── rating (up/down)
    └── comment (opcional)
    ↓
[AiFeedback entity] → correlacionable con ai_usage_log
```

### 2.9 Alertas de Coste en Tiempo Real (FIX-051)

```
[AIObservabilityService::log()] → despues de cada ejecucion
    ↓
[CostAlertService::checkThresholds()]
    ├── 80% del limite mensual → warning notification
    ├── 95% del limite mensual → critical notification
    └── Basado en tokens acumulados * pricing del tier
    ↓
[notify()] → admin email + watchdog
```

---

## 3. Servicios Nuevos (16 ficheros nuevos)

| Servicio | Modulo | Responsabilidad |
|----------|--------|-----------------|
| `ProviderFallbackService` | jaraba_ai_agents | Circuit breaker + fallback chain |
| `ContextWindowManager` | jaraba_ai_agents | Estimacion tokens + recorte progresivo |
| `ReActLoopService` | jaraba_ai_agents | Razonamiento multi-paso PLAN→EXECUTE→REFLECT |
| `HandoffDecisionService` | jaraba_ai_agents | Decision LLM de handoff entre agentes |
| `QualityEvaluationWorker` | jaraba_ai_agents | Queue worker para evaluacion en background |
| `AiFeedback` (entity) | jaraba_ai_agents | Entidad de feedback usuario |
| `AiFeedbackController` | jaraba_ai_agents | POST /api/v1/ai/feedback |
| `AgentExecutionBridgeService` | jaraba_agents | Bridge autonomo → smart agent |
| `AgentLongTermMemoryService` | jaraba_agents | Memoria semantica cross-sesion (Qdrant + BD) |
| `ScheduledAgentWorker` | jaraba_agents | Queue worker para ejecuciones programadas |
| `SemanticCacheService` | jaraba_copilot_v2 | Cache semantica via Qdrant (threshold 0.92) |
| `LlmReRankerService` | jaraba_rag | Re-ranking de candidatos RAG via LLM fast tier |
| `ServiceMatchingService` | jaraba_servicios_conecta | Matching hibrido buscadores↔proveedores |
| `AdaptiveLearningService` | jaraba_lms | Rutas de aprendizaje adaptativas |
| `CostAlertService` | ecosistema_jaraba_core | Alertas 80%/95% de limite de coste IA |
| `QueryDatabaseTool`, `UpdateEntityTool`, `SearchContentTool` | jaraba_ai_agents | 3 tools nuevos para ToolRegistry |

---

## 4. Ficheros Modificados Clave (~20)

| Fichero | Cambio |
|---------|--------|
| `SmartBaseAgent.php` | Tool use loop, context window, output PII mask, prompt experiments, evaluation queue |
| `ProducerCopilotAgent.php` | Constructor 10 args |
| `SalesAgent.php` | Constructor 10 args |
| `MerchantCopilotAgent.php` | Constructor 10 args |
| `StorytellingAgent.php` | Migrado Gen 1 → Gen 2 |
| `CustomerExperienceAgent.php` | Migrado Gen 1 → Gen 2 |
| `SupportAgent.php` | Migrado Gen 1 → Gen 2 |
| `CopilotCacheService.php` | 2-layer cache (exact + semantic) |
| `CopilotStreamController.php` | log_id en SSE done event |
| `JarabaRagService.php` | LLM re-ranker integration |
| `AIGuardrailsService.php` | checkJailbreak() + maskOutputPII() |
| `AgentOrchestratorService.php` | Bridge integration |
| `MatchingService.php` | Config-driven hybrid scoring |
| `RecommendationService.php` | Personalized recommendations via centroid embedding |
| `PromptExperimentService.php` | getActiveVariant() integration |
| `jaraba_ai_agents.services.yml` | 16+ nuevos servicios y tools |

---

## 5. Configuracion

### 5.1 Provider Fallback (`jaraba_ai_agents.provider_fallback.yml`)
```yaml
chains:
  fast:
    primary: anthropic_haiku
    fallback: openai_gpt4o_mini
  balanced:
    primary: anthropic_sonnet
    fallback: openai_gpt4o
  premium:
    primary: anthropic_opus
    fallback: anthropic_sonnet
circuit_breaker:
  failure_threshold: 3
  window_seconds: 300
```

### 5.2 RAG Re-ranking (`jaraba_rag.settings.yml`)
```yaml
reranking:
  strategy: hybrid  # keyword | llm | hybrid
  llm_weight: 0.7
  keyword_weight: 0.3
```

### 5.3 Matching Hibrido (`jaraba_matching.settings.yml`)
```yaml
semantic_matching_enabled: true
scoring:
  min_score: 0.3
  max_results: 20
```

### 5.4 Agent Type Mapping (`jaraba_agents.agent_type_mapping.yml`)
```yaml
mapping:
  marketing_automation: jaraba_ai_agents.smart_marketing_agent
  storytelling: jaraba_ai_agents.storytelling_agent
  customer_experience: jaraba_ai_agents.customer_experience_agent
  support: jaraba_ai_agents.support_agent
```

---

## 6. Impacto por Fase

| Fase | FIX Items | Nivel |
|------|-----------|-------|
| Fase 1 — Conectar Infraestructura | FIX-029 a FIX-037 (9) | 3.0 → 4.0 |
| Fase 2 — Capacidades Autonomas | FIX-038 a FIX-045 (8) | 4.0 → 4.5 |
| Fase 3 — Inteligencia por Vertical | FIX-046 a FIX-051 (6) | 4.5 → 5.0 |
| **Total** | **23 FIX items** | **5.0/5** |

---

## 7. Cross-References

- Plan de implementacion: `docs/implementacion/2026-02-26_Plan_Elevacion_IA_Nivel5_Clase_Mundial_v1.md`
- Remediacion previa (28 fixes): `docs/tecnicos/20260226-Plan_Remediacion_Integral_IA_SaaS_v1_Claude.md`
- Copilot contextual: `docs/arquitectura/2026-01-26_arquitectura_copiloto_contextual.md`
- Model routing config: `jaraba_ai_agents/config/install/jaraba_ai_agents.model_routing.yml`
- Provider fallback config: `jaraba_ai_agents/config/install/jaraba_ai_agents.provider_fallback.yml`
- Directrices v78.0.0, Flujo v33.0.0, Indice v103.0.0
- Aprendizajes #129, #130

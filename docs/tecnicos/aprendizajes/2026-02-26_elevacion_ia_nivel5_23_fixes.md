# Aprendizaje #129: Elevacion IA a Nivel 5/5 — 23 Fixes en 3 Fases

**Fecha:** 2026-02-26
**Categoria:** Arquitectura IA / Autonomia / Caching / Seguridad
**Impacto:** Critico — Eleva el stack IA de nivel 3/5 a 5/5 conectando infraestructura existente y anadiendo capacidades autonomas

---

## 1. Contexto

Tras la remediacion integral de 28 fixes (Aprendizaje #127), el stack IA tenia buena base pero estaba fragmentado: tools sin agentes, orquestador sin LLM, evaluador sin pipeline, cache sin semantica. Un plan de 23 FIX items (FIX-029 a FIX-051) en 3 fases priorizo CONECTAR piezas existentes sobre construir desde cero.

## 2. Patrones Clave Aprendidos

### 2.1 Conectar Antes de Construir
- **Problema:** ToolRegistry tenia 3 tools pero ningun agente los usaba. QualityEvaluatorService existia completo pero nunca se llamaba. HandoffManager tenia CRUD sin que nadie lo invocara.
- **Solucion:** Inyectar ToolRegistry opcionalmente en SmartBaseAgent, encolar evaluaciones en queue worker, crear HandoffDecisionService que consulta al LLM.
- **Patron:** Antes de crear infraestructura nueva, auditar que piezas existentes estan desconectadas. Los mayores wins vienen de wiring, no de building.

### 2.2 Constructor de 10 Args con Optional DI
- **Problema:** Los agentes Gen 2 necesitaban nuevas capacidades (tools, fallback, context window) sin romper los existentes.
- **Solucion:** Patron de constructor con args opcionales al final (`@?` en services.yml, `?Type $param = NULL` en PHP). Conditional setters en el constructor body.
- **Patron:** `parent::__construct(6 core args)` + `$this->setModelRouter()` + `if ($toolRegistry) { $this->setToolRegistry($toolRegistry); }`. Los `@?` permiten que servicios funcionen sin modulos opcionales instalados.

### 2.3 Circuit Breaker para Providers IA
- **Problema:** Si el provider IA falla (rate limit, outage), toda la peticion falla sin retry.
- **Solucion:** `ProviderFallbackService` con circuit breaker: 3 fallos en 5 minutos = skip provider, cascada por tier (primary → fallback → emergency). Estado en Drupal State API.
- **Patron:** Circuit breaker state: `CLOSED` (normal) → `OPEN` (fallos > threshold, skip) → `HALF_OPEN` (probar despues de cooldown). Config en YAML para cambiar providers sin code deploy.

### 2.4 Cache Semantica de 2 Capas
- **Problema:** Cache por hash exacto perdia queries semanticamente equivalentes ("aceite de oliva virgen extra" vs "AOVE premium").
- **Solucion:** Layer 1 = Drupal cache (hash exacto, rapido). Layer 2 = SemanticCacheService (embedding + Qdrant vectorSearch, threshold 0.92). Set escribe en ambas capas.
- **Patron:** El threshold 0.92 es alto intencionalmente para evitar falsos positivos. Degradacion graceful: si Qdrant no esta disponible, solo se usa Layer 1. `\Drupal::hasService()` + try-catch.

### 2.5 Re-ranking LLM Config-Driven
- **Problema:** Re-ranking por keyword-overlap era basico. Diferentes despliegues pueden necesitar diferentes estrategias.
- **Solucion:** Config YAML con `reranking.strategy: keyword|llm|hybrid`. El servicio lee config y delega. LLM usa tier `fast` (Haiku) para bajo coste.
- **Patron:** Cuando hay multiples estrategias para un problema, hacerlas seleccionables via config en vez de hardcodear una. El codigo implementa todas y la config selecciona.

### 2.6 Centroid Embedding para Recomendaciones Personalizadas
- **Problema:** RecommendationService no tenia personalizacion por perfil de usuario.
- **Solucion:** Generar centroid embedding (promedio de embeddings de los ultimos 5 articulos leidos), buscar articulos similares no leidos en Qdrant (threshold 0.55). Fallback a recomendacion por categoria favorita.
- **Patron:** El centroid de embeddings captura "el tema general" de interes del usuario sin necesidad de perfil explicito. Threshold 0.55 (bajo) porque es recomendacion, no busqueda exacta.

### 2.7 Jailbreak Detection Bilingue
- **Problema:** AIGuardrailsService solo detectaba PII, no intentos de jailbreak (prompt injection, role-play attacks).
- **Solucion:** Patrones regex bilingues ES/EN: "ignore previous", "you are now", "DAN mode", "pretend you are", "olvida tus instrucciones", "actua como si fueras". Accion: BLOCK.
- **Patron:** Los atacantes pueden usar cualquier idioma soportado por la plataforma. Todo patron de guardrail DEBE ser bilingue (al menos ES/EN para este SaaS).

### 2.8 Output PII Masking
- **Problema:** Guardrails validaban INPUT pero no OUTPUT. El LLM podia generar PII en respuestas.
- **Solucion:** `maskOutputPII()` reutiliza los mismos patrones de `checkPII()` pero reemplaza con `[DATO PROTEGIDO]` en vez de bloquear.
- **Patron:** Los guardrails de IA DEBEN ser bidireccionales: validar input Y sanitizar output. El LLM puede "inventar" datos que parezcan PII real.

### 2.9 Bridge para Agentes Autonomos
- **Problema:** `AgentOrchestratorService::execute()` creaba AgentExecution y gestionaba estado, pero retornaba `success=true` sin hacer trabajo real.
- **Solucion:** `AgentExecutionBridgeService` mapea `AutonomousAgent.agent_type` a service IDs concretos via config YAML, resuelve el agente, y ejecuta la accion.
- **Patron:** Cuando dos modulos tienen responsabilidades solapantes (uno gestiona estado, otro ejecuta), crear un bridge service que los conecte en vez de fusionarlos.

### 2.10 Migracion Gen 1 → Gen 2 Sistematica
- **Problema:** StorytellingAgent, CustomerExperienceAgent, SupportAgent extendian BaseAgent sin model routing.
- **Solucion:** Cambiar `extends BaseAgent` a `extends SmartBaseAgent`, refactorizar `execute()` a `doExecute()`, inyectar las 10 dependencias. Seguir patron de SmartMarketingAgent como referencia.
- **Patron:** Para migrar agentes a nueva generacion: (1) cambiar extends, (2) renombrar execute→doExecute, (3) copiar constructor del agente referencia, (4) actualizar services.yml. Verificar con `php -l`.

## 3. Metricas de Ejecucion

- **23 FIX items** ejecutados en 3 fases (P0 infraestructura, P1 autonomia, P2 verticales)
- **~25 ficheros nuevos** + **~20 ficheros modificados**
- **31 ficheros PHP** validados con `php -l` — 0 errores
- **11 ficheros YAML** validados con `yaml.safe_load()` — 0 errores
- Elevacion: Nivel 3/5 → 5/5

## 4. Reglas Derivadas

| ID | Descripcion | Prioridad |
|----|-------------|-----------|
| SMART-AGENT-DI-001 | 10-arg constructor con 3 opcionales (@?) | P0 |
| PROVIDER-FALLBACK-001 | Circuit breaker + fallback chain para LLM | P0 |
| JAILBREAK-DETECT-001 | Deteccion jailbreak bilingue en guardrails | P0 |
| OUTPUT-PII-MASK-001 | Masking PII en output del LLM | P0 |
| TOOL-USE-AGENT-001 | Loop iterativo de tool use (max 5) | P1 |
| SEMANTIC-CACHE-001 | Cache 2 capas (exact + Qdrant semantic) | P1 |
| REACT-LOOP-001 | Razonamiento multi-paso PLAN→EXECUTE→REFLECT | P1 |
| AGENT-BRIDGE-001 | Bridge autonomo → smart agent via config | P1 |

## 5. Referencias

- Arquitectura: `docs/arquitectura/2026-02-26_arquitectura_elevacion_ia_nivel5.md`
- Plan: `docs/implementacion/2026-02-26_Plan_Elevacion_IA_Nivel5_Clase_Mundial_v1.md`
- SmartBaseAgent: `jaraba_ai_agents/src/Agent/SmartBaseAgent.php`
- ProviderFallbackService: `jaraba_ai_agents/src/Service/ProviderFallbackService.php`
- SemanticCacheService: `jaraba_copilot_v2/src/Service/SemanticCacheService.php`
- LlmReRankerService: `jaraba_rag/src/Service/LlmReRankerService.php`
- ReActLoopService: `jaraba_ai_agents/src/Service/ReActLoopService.php`
- AgentExecutionBridgeService: `jaraba_agents/src/Service/AgentExecutionBridgeService.php`
- AIGuardrailsService: `ecosistema_jaraba_core/src/Service/AIGuardrailsService.php`
- CostAlertService: `ecosistema_jaraba_core/src/Service/CostAlertService.php`
- Aprendizaje previo (28 fixes): `docs/tecnicos/aprendizajes/2026-02-26_ai_remediation_plan_28_fixes.md`

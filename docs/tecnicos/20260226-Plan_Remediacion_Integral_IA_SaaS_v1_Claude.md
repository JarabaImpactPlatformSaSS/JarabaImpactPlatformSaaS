# Plan de Remediacion Integral de la Arquitectura de IA - Jaraba Impact Platform SaaS

**Type:** Implementation Plan
**Version:** 1.0
**Date:** 2026-02-26
**Author:** Claude Opus 4.6
**Status:** Approved for Implementation
**Source:** `20260226-Auditoria_Integral_Arquitectura_IA_SaaS_v1_Claude.md`
**Estimated Effort:** 140-190 horas (3 fases)
**Target:** Elevar madurez IA de 2.5/5.0 a 4.5/5.0

---

## Indice de Navegacion (TOC)

1. [Contexto y Objetivos](#1-contexto-y-objetivos)
2. [Directrices de Cumplimiento Obligatorio](#2-directrices-de-cumplimiento-obligatorio)
3. [Fase 1 -- Criticos (P0): 40-60h](#3-fase-1----criticos-p0-40-60h)
   - 3.1 [FIX-001: Restaurar contrato SmartBaseAgent](#31-fix-001-restaurar-contrato-smartbaseagent)
   - 3.2 [FIX-002: Corregir bug logico getUpgradeContextPrompt](#32-fix-002-corregir-bug-logico-getupgradecontextprompt)
   - 3.3 [FIX-003: Blindar CopilotStreamController](#33-fix-003-blindar-copilotstreamcontroller)
   - 3.4 [FIX-004: Corregir cross-tenant data leaks](#34-fix-004-corregir-cross-tenant-data-leaks)
   - 3.5 [FIX-005: Unificar unidades budget dollars/centavos](#35-fix-005-unificar-unidades-budget-dollarscentavos)
   - 3.6 [FIX-006: Reparar RAG hallucination recovery](#36-fix-006-reparar-rag-hallucination-recovery)
   - 3.7 [FIX-007: Alinear RAG config keys](#37-fix-007-alinear-rag-config-keys)
   - 3.8 [FIX-008: Corregir SmartMarketingAgent constructor](#38-fix-008-corregir-smartmarketingagent-constructor)
4. [Fase 2 -- Altos (P1): 60-80h](#4-fase-2----altos-p1-60-80h)
   - 4.1 [FIX-009: Activar mode prompts en EmployabilityCopilotAgent](#41-fix-009-activar-mode-prompts-en-employabilitycopilotagent)
   - 4.2 [FIX-010: Conectar FeatureUnlockService al chat flow](#42-fix-010-conectar-featureunlockservice-al-chat-flow)
   - 4.3 [FIX-011: Registrar modos v3 en provider/model mapping](#43-fix-011-registrar-modos-v3-en-providermodel-mapping)
   - 4.4 [FIX-012: Deprecar ClaudeApiService](#44-fix-012-deprecar-claudeapiservice)
   - 4.5 [FIX-013: Unificar BMC block keys](#45-fix-013-unificar-bmc-block-keys)
   - 4.6 [FIX-014: Implementar AI-IDENTITY-001 universal](#46-fix-014-implementar-ai-identity-001-universal)
   - 4.7 [FIX-015: Integrar AIGuardrailsService en pipeline RAG](#47-fix-015-integrar-aiguardrailsservice-en-pipeline-rag)
   - 4.8 [FIX-016: Eliminar agente duplicado JarabaLexCopilotAgent](#48-fix-016-eliminar-agente-duplicado-jarabalexcopilotagent)
   - 4.9 [FIX-017: Crear API REST para ComercioConecta](#49-fix-017-crear-api-rest-para-comercioconecta)
   - 4.10 [FIX-018: Parametrizar RAG system prompt por vertical](#410-fix-018-parametrizar-rag-system-prompt-por-vertical)
5. [Fase 3 -- Medios (P2): 40-50h](#5-fase-3----medios-p2-40-50h)
   - 5.1 [FIX-019: Anadir keywords espanoles a ModelRouterService](#51-fix-019-anadir-keywords-espanoles-a-modelrouterservice)
   - 5.2 [FIX-020: Actualizar precios de modelos](#52-fix-020-actualizar-precios-de-modelos)
   - 5.3 [FIX-021: Conectar observability ghost DI](#53-fix-021-conectar-observability-ghost-di)
   - 5.4 [FIX-022: Reescribir o eliminar AIOpsService](#54-fix-022-reescribir-o-eliminar-aiopsservice)
   - 5.5 [FIX-023: Alinear feedback widget/servidor](#55-fix-023-alinear-feedback-widgetservidor)
   - 5.6 [FIX-024: Implementar streaming real](#56-fix-024-implementar-streaming-real)
   - 5.7 [FIX-025: Migrar agentes Gen 0 o documentar como no-AI](#57-fix-025-migrar-agentes-gen-0-o-documentar-como-no-ai)
   - 5.8 [FIX-026: Anadir @? para UnifiedPromptBuilder](#58-fix-026-anadir--para-unifiedpromptbuilder)
   - 5.9 [FIX-027: Unificar vertical names](#59-fix-027-unificar-vertical-names)
   - 5.10 [FIX-028: Anadir PII espanoles a guardrails](#510-fix-028-anadir-pii-espanoles-a-guardrails)
6. [Tabla de Correspondencia con Especificaciones Tecnicas](#6-tabla-de-correspondencia-con-especificaciones-tecnicas)
7. [Tabla de Cumplimiento de Directrices](#7-tabla-de-cumplimiento-de-directrices)
8. [Criterios de Aceptacion Globales](#8-criterios-de-aceptacion-globales)
9. [Orden de Ejecucion y Dependencias](#9-orden-de-ejecucion-y-dependencias)
10. [Riesgos y Mitigaciones](#10-riesgos-y-mitigaciones)

---

## 1. Contexto y Objetivos

### 1.1 Origen

La auditoria integral de 2026-02-26 (`20260226-Auditoria_Integral_Arquitectura_IA_SaaS_v1_Claude.md`) identifico **54 hallazgos** distribuidos en 3 niveles de severidad (17 P0, 18 P1, 19 P2) que degradan la madurez de IA del SaaS de 5.0/5.0 declarado a ~2.5/5.0 real.

### 1.2 Objetivo

Elevar la madurez de IA a **4.5/5.0** mediante la remediacion sistematica de los hallazgos, asegurando:

1. **Seguridad:** AI-IDENTITY-001, prompt injection defense, tenant isolation
2. **Observabilidad:** Tracking completo de tokens, coste, duracion para todo agente
3. **Coherencia:** Un solo framework de agentes, un solo pipeline de guardrails
4. **Fiabilidad:** RAG recovery funcional, budget enforcement real, failover correcto
5. **Mantenibilidad:** Codigo muerto eliminado, agentes duplicados consolidados

### 1.3 Principios de Implementacion

Toda remediacion DEBE cumplir las directrices del proyecto (seccion 2). En particular:

- **SCSS:** Modelo Federated Design Tokens. Archivos SCSS compilados con Dart Sass moderno. Variables inyectables via `var(--ej-*, $fallback)`. Nunca definir `$ej-*` en modulos satelite. Build via `package.json`.
- **Textos UI:** Siempre traducibles via `$this->t()` o `new TranslatableMarkup()`. Cast `(string)` al pasar a render arrays (TM-CAST-001).
- **Iconos:** `jaraba_icon('category', 'name', { variant: 'duotone', color: 'azul-corporativo' })`. Nunca emojis (ICON-EMOJI-001). Nunca hex directos (ICON-COLOR-001).
- **Frontend:** Paginas limpias sin `page.content` ni bloques heredados. Layout full-width, mobile-first. Acciones crear/editar/ver en slide-panel (no abandonar pagina). Templates twig con `{% include %}` de parciales reutilizables. Variables configurables desde Theme Settings UI.
- **Body classes:** Via `hook_preprocess_html()`, nunca `attributes.addClass()` en template (no funciona para body).
- **Entidades:** ContentEntity con Field UI + Views. Navegacion en `/admin/structure` y `/admin/content`.
- **Formularios:** Extender `PremiumEntityFormBase`. Implementar `getSectionDefinitions()` y `getFormIcon()`. Sin fieldsets/details groups.
- **Testing:** PHPUnit Unit + Kernel con MariaDB 10.11.
- **DI:** Inyeccion de dependencias. `@?` para servicios opcionales. Nunca `\Drupal::service()` en servicios inyectables.
- **Docker:** Todos los comandos dentro del contenedor: `lando ssh` o `docker exec`.

---

## 2. Directrices de Cumplimiento Obligatorio

Cada FIX de este plan DEBE verificar cumplimiento con las siguientes directrices antes de considerarse completo:

| Directriz | Verificacion |
|-----------|-------------|
| **AI-IDENTITY-001** | Todo system prompt incluye "REGLA DE IDENTIDAD INQUEBRANTABLE" |
| **AI-COMPETITOR-001** | Ningun prompt menciona plataformas competidoras |
| **TENANT-BRIDGE-001** | Usa `TenantBridgeService` para Tenant<->Group |
| **TENANT-ISOLATION-ACCESS-001** | Verifica tenant match en update/delete |
| **CSRF-API-001** | Endpoints con `_csrf_request_header_token: 'TRUE'` |
| **INNERHTML-XSS-001** | `Drupal.checkPlain()` para datos en innerHTML |
| **CSRF-JS-CACHE-001** | Token CSRF cacheado en variable module-level |
| **API-WHITELIST-001** | `ALLOWED_FIELDS` en endpoints con campos dinamicos |
| **ACCESS-STRICT-001** | `(int) === (int)` en comparaciones de ownership |
| **ICON-CONVENTION-001** | `jaraba_icon('cat', 'name', {variant:'duotone'})` |
| **ICON-COLOR-001** | Solo colores Jaraba: azul-corporativo, naranja-impulso, verde-innovacion |
| **PREMIUM-FORMS-PATTERN-001** | Forms extienden `PremiumEntityFormBase` |
| **TM-CAST-001** | `$this->t()` casteado `(string)` en render arrays |
| **TWIG-XSS-001** | `\|safe_html` para user content, nunca `\|raw` |
| **SCSS Federated Tokens** | Solo `var(--ej-*, $fallback)` en modulos satelite |
| **Dart Sass Moderno** | `@use 'sass:color'`, nunca `darken()`/`lighten()` |
| **Slide-Panel** | Acciones CRUD en slide-panel, no navegacion fuera |
| **Clean Frontend** | Sin `page.content`, sin bloques heredados |
| **hook_preprocess_html** | Body classes via hook, no template |
| **Textos traducibles** | Todo string UI via `$this->t()` o `TranslatableMarkup` |
| **Theme Settings** | Contenido configurable sin tocar codigo |
| **Mobile-First** | Layouts responsive, full-width |

---

## 3. Fase 1 -- Criticos (P0): 40-60h

### 3.1 FIX-001: Restaurar contrato SmartBaseAgent

**Hallazgo:** P0-001
**Esfuerzo:** 8-12h
**Archivos a modificar:**

| Archivo | Cambio |
|---------|--------|
| `web/modules/custom/jaraba_ai_agents/src/Agent/SmartBaseAgent.php` | Override `callAiApi()` para usar `buildSystemPrompt()` y llamar `observability->log()` |
| `web/modules/custom/jaraba_ai_agents/src/Agent/SmartMarketingAgent.php` | Corregir constructor (FIX-008 incluido) |
| `web/modules/custom/jaraba_ai_agents/src/Agent/ProducerCopilotAgent.php` | Verificar herencia correcta post-fix |
| `web/modules/custom/jaraba_ai_agents/src/Agent/SalesAgent.php` | Verificar herencia correcta post-fix |
| `web/modules/custom/jaraba_ai_agents/src/Agent/MerchantCopilotAgent.php` | Verificar herencia correcta post-fix |

**Descripcion tecnica detallada:**

El problema raiz es que `SmartBaseAgent::callAiApi()` (lineas 68-116) reemplaza completamente la implementacion de `BaseAgent::callAiApi()` (lineas 250-350). La version de SmartBaseAgent solo obtiene el brand voice como system prompt (`$this->getBrandVoicePrompt()`), omitiendo tres pilares criticos:

1. **AI-IDENTITY-001:** La regla de identidad inquebrantable que impide al LLM revelar que es Claude/GPT se inyecta en `BaseAgent::buildSystemPrompt()` (linea 215-218). SmartBaseAgent la salta.
2. **Observabilidad:** `BaseAgent::callAiApi()` llama a `$this->observability->log()` (lineas 332-343) registrando agent_id, action, tier, model_id, tenant_id, vertical, tokens, duracion. SmartBaseAgent no tiene ninguna llamada a `log()`.
3. **UnifiedPromptBuilder:** `BaseAgent::buildSystemPrompt()` llama a `$this->getUnifiedContext()` que inyecta Skills, Knowledge, Corrections y RAG via `UnifiedPromptBuilder`. SmartBaseAgent nunca invoca esta logica.

**Solucion:**

Reescribir `SmartBaseAgent::callAiApi()` para que:

1. Llame a `$this->buildSystemPrompt($prompt)` en vez de `$this->getBrandVoicePrompt()`
2. Mantenga la logica de model routing via `$this->getRoutingConfig()`
3. Anada la llamada a `$this->observability->log()` con todos los campos requeridos
4. Calcule y registre el coste usando los datos de `ModelRouterService`

```php
protected function callAiApi(string $prompt, array $options = []): array
{
    $startTime = microtime(TRUE);
    $tier = 'balanced';

    try {
        // 1. System prompt COMPLETO (identidad + brand voice + unified context + vertical)
        $systemPrompt = $this->buildSystemPrompt($prompt);

        // 2. Model routing inteligente
        $routingConfig = $this->getRoutingConfig($prompt, $options);
        $tier = $routingConfig['tier'];
        $providerId = $routingConfig['provider'];
        $modelId = $routingConfig['model'];

        // 3. Llamada al provider via ai.provider framework
        $provider = $this->aiProvider->createInstance($providerId);
        $chatInput = new ChatInput([
            new ChatMessage('system', $systemPrompt),
            new ChatMessage('user', $prompt),
        ]);
        $configuration = [
            'temperature' => $options['temperature'] ?? 0.7,
        ];
        $response = $provider->chat($chatInput, $modelId, $configuration);
        $text = $response->getNormalized()->getText();
        $success = TRUE;

    } catch (\Exception $e) {
        $this->logger->error('SmartBaseAgent AI call failed: @error', [
            '@error' => $e->getMessage(),
        ]);
        $text = '';
        $success = FALSE;
    }

    // 4. Observabilidad OBLIGATORIA
    $durationMs = (int) ((microtime(TRUE) - $startTime) * 1000);
    $inputTokens = (int) ceil(mb_strlen($prompt) / 4);
    $outputTokens = (int) ceil(mb_strlen($text) / 4);

    if ($this->observability) {
        $this->observability->log([
            'agent_id' => $this->getAgentId(),
            'action' => $this->currentAction ?? 'unknown',
            'tier' => $tier,
            'model_id' => $modelId ?? 'unknown',
            'provider_id' => $providerId ?? 'unknown',
            'tenant_id' => $this->tenantId,
            'vertical' => $this->vertical,
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'duration_ms' => $durationMs,
            'success' => $success,
        ]);
    }

    return [
        'success' => $success,
        'text' => $text,
        'model' => $modelId ?? 'unknown',
        'tier' => $tier,
        'tokens_in' => $inputTokens,
        'tokens_out' => $outputTokens,
    ];
}
```

**Tests requeridos:**
- Unit test: SmartBaseAgent con mock de `buildSystemPrompt()` verifica que se llama
- Unit test: SmartBaseAgent con mock de `observability->log()` verifica que se llama con todos los campos
- Kernel test: SmartMarketingAgent instanciacion correcta con todos los argumentos

**Criterios de aceptacion:**
- [ ] `SmartBaseAgent::callAiApi()` llama `buildSystemPrompt()` (no `getBrandVoicePrompt()`)
- [ ] `SmartBaseAgent::callAiApi()` llama `observability->log()` con todos los campos
- [ ] Los 4 agentes Smart responden con AI-IDENTITY-001 activo
- [ ] Dashboard de observabilidad muestra metricas de los 4 agentes Smart
- [ ] Tests unit + kernel pasan

---

### 3.2 FIX-002: Corregir bug logico getUpgradeContextPrompt

**Hallazgo:** P0-002
**Esfuerzo:** 1h
**Archivo:** `web/modules/custom/jaraba_copilot_v2/src/Service/CopilotOrchestratorService.php:661`

**Descripcion tecnica:**

La linea `if (!$this->tenantContext !== NULL)` tiene un error de precedencia de operadores. El operador `!` se aplica primero a `$this->tenantContext`, produciendo un `bool`. Luego `bool !== NULL` siempre es `TRUE` porque un boolean nunca es NULL. El metodo retorna `''` siempre, desactivando globalmente los nudges de upgrade.

**Solucion:**

```php
// ANTES (bug):
if (!$this->tenantContext !== NULL) {
    return '';
}

// DESPUES (correcto):
if ($this->tenantContext === NULL) {
    return '';
}
```

**Criterios de aceptacion:**
- [ ] `getUpgradeContextPrompt()` retorna texto no vacio cuando hay contexto de tenant
- [ ] Usuarios con plan free/starter ven sugerencias de upgrade en respuestas del copilot
- [ ] Test unit verifica ambos caminos (con y sin tenantContext)

---

### 3.3 FIX-003: Blindar CopilotStreamController

**Hallazgo:** P0-003
**Esfuerzo:** 6-8h
**Archivos:**

| Archivo | Cambio |
|---------|--------|
| `web/modules/custom/jaraba_copilot_v2/src/Controller/CopilotStreamController.php` | Anadir rate limiting, usage limits, token tracking, tenant context |
| `web/modules/custom/jaraba_copilot_v2/jaraba_copilot_v2.services.yml` | Inyectar RateLimiterService, AIUsageLimitService, TenantContextService |

**Descripcion tecnica:**

El `CopilotStreamController` es la puerta de entrada al streaming SSE del copilot de emprendimiento. Actualmente solo tiene `_permission: 'access copilot'` y `_csrf_request_header_token: 'TRUE'` como proteccion. Carece de:

1. **Rate limiting:** `RateLimiterService` debe limitar requests por usuario/minuto
2. **AI usage limits:** `AIUsageLimitService` debe verificar que el tenant no ha excedido su cuota mensual de tokens
3. **Token tracking:** Despues de cada respuesta, registrar tokens consumidos via `AIUsageLimitService::recordUsage()`
4. **Tenant context:** Resolver el tenant del usuario actual para aplicar limites correctos

La implementacion debe seguir exactamente el patron de `CopilotApiController::chat()` (lineas 191-325) que ya tiene todos estos controles.

**Solucion esquematica:**

```php
public function __construct(
    CopilotOrchestratorService $orchestrator,
    ModeDetectorService $modeDetector,
    RateLimiterService $rateLimiter,        // NUEVO
    AIUsageLimitService $usageLimiter,       // NUEVO
    TenantContextService $tenantContext,     // NUEVO
) { ... }

public function stream(Request $request): StreamedResponse
{
    // 1. Rate limit check
    if (!$this->rateLimiter->allowRequest($currentUser, 'copilot_stream')) {
        return new JsonResponse(['error' => (string) $this->t('Demasiadas solicitudes.')], 429);
    }

    // 2. Resolve tenant
    $tenant = $this->tenantContext->getCurrentTenant();
    $tenantId = $tenant ? (string) $tenant->id() : NULL;

    // 3. Usage limit check
    if ($tenantId && $this->usageLimiter->isLimitReached($tenant)) {
        return new JsonResponse(['error' => (string) $this->t('Limite de uso alcanzado.')], 429);
    }

    // ... existing SSE logic ...

    // 4. Track usage after response
    if ($tenantId) {
        $tokensIn = (int) ceil(mb_strlen($message) / 4);
        $tokensOut = (int) ceil(mb_strlen($responseText) / 4);
        $this->usageLimiter->recordUsage($tenant, $tokensIn, $tokensOut);
    }
}
```

**Nota sobre textos traducibles:** Todos los mensajes de error deben usar `$this->t()` o `new TranslatableMarkup()` para cumplir la directriz de textos traducibles. Ejemplo: `(string) $this->t('Demasiadas solicitudes. Intenta de nuevo en un minuto.')`.

**Criterios de aceptacion:**
- [ ] Rate limiting activo (max N requests/minuto configurable)
- [ ] Usage limit check antes de procesar
- [ ] Token tracking post-respuesta
- [ ] Tenant context resuelto para el usuario actual
- [ ] Mensajes de error en espanol y traducibles via `$this->t()`
- [ ] Tests kernel con mock de rate limiter y usage limiter

---

### 3.4 FIX-004: Corregir cross-tenant data leaks

**Hallazgo:** P0-004
**Esfuerzo:** 4-6h
**Archivos:**

| Archivo | Cambio |
|---------|--------|
| `web/modules/custom/ecosistema_jaraba_core/src/Service/AICostOptimizationService.php` | Incluir `tenant_id` en cache key |
| `web/modules/custom/ecosistema_jaraba_core/src/Service/AITelemetryService.php` | Escribir `tenant_id` en `persistMetrics()` |

**Descripcion tecnica del leak de cache:**

En `AICostOptimizationService::getCachedResponse()` la clave es `"ai_cache_" . md5($prompt . $model)`. Si dos tenants envian el mismo prompt con el mismo modelo, la respuesta cacheada del primer tenant se sirve al segundo, exponiendo contenido de otro tenant.

**Solucion cache:**

```php
// ANTES:
$hash = md5($prompt . $model);
$key = "ai_cache_{$hash}";

// DESPUES:
$hash = md5($prompt . $model . $tenantId);
$key = "ai_cache_{$hash}";
```

**Descripcion tecnica del leak de telemetria:**

En `AITelemetryService::persistMetrics()`, la insercion nunca incluye `tenant_id`:

```php
$this->database->insert('ai_telemetry')->fields([
    'request_id' => ...,
    'agent_id' => ...,
    // FALTA: 'tenant_id' => $this->currentInvocation['tenant_id'] ?? NULL,
]);
```

Pero `getAgentStats()` filtra por `tenant_id`. Sin el campo, queries con `$tenantId = NULL` retornan datos de TODOS los tenants.

**Criterios de aceptacion:**
- [ ] Cache key incluye `tenant_id`
- [ ] `persistMetrics()` escribe `tenant_id`
- [ ] `getAgentStats($tenantId)` retorna solo datos del tenant especificado
- [ ] Test kernel verifica aislamiento de cache entre tenants

---

### 3.5 FIX-005: Unificar unidades budget dollars/centavos

**Hallazgo:** P0-005
**Esfuerzo:** 2-3h
**Archivo:** `web/modules/custom/ecosistema_jaraba_core/src/Service/AICostOptimizationService.php`

**Descripcion tecnica:**

`getTenantBudget()` retorna centavos (ej: 2500 para plan $25). `getTenantUsage()` retorna dolares (ej: 5.00). La comparacion `$usage >= $budget * 0.9` compara dolares contra centavos, haciendo que el threshold nunca se active.

**Solucion:** Convertir budget a dolares antes de comparar:

```php
// ANTES:
$budget = $this->getTenantBudget($tenantId);
if ($usage >= $budget * 0.9) { ... }

// DESPUES:
$budgetCents = $this->getTenantBudget($tenantId);
$budgetDollars = $budgetCents / 100;
if ($usage >= $budgetDollars * 0.9) { ... }
```

Alternativa mas robusta: cambiar `getTenantBudget()` para que retorne siempre dolares, eliminando la conversion `* 100` interna.

**Criterios de aceptacion:**
- [ ] Budget threshold se activa correctamente al 90% de uso
- [ ] Modelo se degrada automaticamente cuando se supera el 90%
- [ ] Test unit con tenant en 85%, 91%, 100% de budget verifica comportamiento

---

### 3.6 FIX-006: Reparar RAG hallucination recovery

**Hallazgo:** P0-006
**Esfuerzo:** 4-6h
**Archivo:** `web/modules/custom/jaraba_rag/src/Service/JarabaRagService.php`

**Descripcion tecnica:**

La funcion `handleHallucinations()` (linea 595) tiene dos errores fatales:

1. Llama `$this->callLlm()` (linea 606) que no existe en la clase
2. Usa `$this->logger` (lineas 609, 615) que tampoco existe (solo existe `$this->loggerFactory`)

**Solucion:**

1. Reemplazar `$this->callLlm()` por el metodo que realmente genera respuestas LLM. Analizar el flujo de `query()` para identificar el metodo correcto (probablemente `generateResponse()` o la logica de `ChatInput` dentro de `query()`). Extraer la logica de llamada LLM a un metodo privado reutilizable si no existe.

2. Reemplazar `$this->logger` por `$this->loggerFactory->get('jaraba_rag')` o inyectar un logger channel en el constructor.

**Criterios de aceptacion:**
- [ ] `handleHallucinations()` regenera respuesta con prompt estricto cuando se detectan alucinaciones
- [ ] Logger funcional para tracking de AI-04 events
- [ ] Test unit con mock de GroundingValidator que retorna hallucination verifica el retry

---

### 3.7 FIX-007: Alinear RAG config keys

**Hallazgo:** P0-007
**Esfuerzo:** 1-2h
**Archivos:**

| Archivo | Cambio |
|---------|--------|
| `web/modules/custom/jaraba_rag/src/Service/JarabaRagService.php:119` | Cambiar `search.min_score` a `search.score_threshold` |
| `web/modules/custom/jaraba_rag/config/install/jaraba_rag.settings.yml` | Verificar key name |
| `web/modules/custom/jaraba_rag/config/schema/jaraba_rag.schema.yml` | Verificar schema |

**Solucion:** Alinear la key que lee el servicio con la que escribe el formulario de configuracion. Ambos deben usar `search.score_threshold`.

**Criterios de aceptacion:**
- [ ] Admin cambia threshold en UI y el cambio se refleja en las busquedas
- [ ] Config schema valida correctamente
- [ ] Test funcional: cambiar threshold, ejecutar query, verificar resultados filtrados

---

### 3.8 FIX-008: Corregir SmartMarketingAgent constructor

**Hallazgo:** P0-008
**Esfuerzo:** 2-3h (incluido en FIX-001)
**Archivos:**

| Archivo | Cambio |
|---------|--------|
| `web/modules/custom/jaraba_ai_agents/src/Agent/SmartMarketingAgent.php` | Alinear constructor con services.yml |
| `web/modules/custom/jaraba_ai_agents/jaraba_ai_agents.services.yml` | Verificar argumentos inyectados |

**Descripcion tecnica:**

El constructor de `SmartMarketingAgent` acepta 5 parametros pero `services.yml` inyecta 7. Ademas, el `parent::__construct()` se llama con 4 argumentos cuando `BaseAgent` requiere 6 (aiProvider, configFactory, logger, brandVoice, observability, promptBuilder).

**Solucion:** Alinear el constructor con la firma de `BaseAgent::__construct()` + `ModelRouterService`:

```php
public function __construct(
    AiProviderPluginManager $aiProvider,
    ConfigFactoryInterface $configFactory,
    LoggerInterface $logger,
    TenantBrandVoiceService $brandVoice,
    AIObservabilityService $observability,
    ModelRouterService $modelRouter,
    ?UnifiedPromptBuilder $promptBuilder = NULL,
) {
    parent::__construct($aiProvider, $configFactory, $logger, $brandVoice, $observability, $promptBuilder);
    $this->setModelRouter($modelRouter);
}
```

---

## 4. Fase 2 -- Altos (P1): 60-80h

### 4.1 FIX-009: Activar mode prompts en EmployabilityCopilotAgent

**Hallazgo:** P1-001
**Esfuerzo:** 6-8h
**Archivo:** `web/modules/custom/jaraba_candidate/src/Agent/EmployabilityCopilotAgent.php`

**Descripcion tecnica:**

Los 6 prompts de modo son cuidadosamente crafteados para el mercado laboral espanol pero nunca llegan al LLM. `buildModePrompt()` construye un system prompt que se asigna a `$systemPrompt` pero `callAiApi($userMessage)` ignora esta variable y construye su propio system prompt via `buildSystemPrompt()`.

**Solucion:** Anadir un parametro `system_prompt` al metodo `callAiApi()` de `BaseAgent`, o mejor, override `buildSystemPrompt()` en los agentes de modo:

**Opcion A (recomendada): Override buildSystemPrompt en agentes de modo**

```php
// En EmployabilityCopilotAgent:
protected function buildSystemPrompt(string $userPrompt): string
{
    // Mantener identidad (de BaseAgent)
    $parts = [];
    $parts[] = 'REGLA DE IDENTIDAD INQUEBRANTABLE: ...';

    // Brand voice
    $brandVoice = $this->getBrandVoicePrompt();
    if (!empty($brandVoice)) {
        $parts[] = $brandVoice;
    }

    // Mode-specific prompt (NUEVO - lo que antes se perdia)
    $mode = $this->currentMode ?? 'faq';
    if (isset(self::MODE_PROMPTS[$mode])) {
        $parts[] = self::MODE_PROMPTS[$mode];
    }

    // Unified context
    $unifiedContext = $this->getUnifiedContext($userPrompt);
    if (!empty($unifiedContext)) {
        $parts[] = $unifiedContext;
    }

    // Vertical context
    $verticalContext = $this->getVerticalContext();
    if (!empty($verticalContext)) {
        $parts[] = "\n<vertical_context>" . $verticalContext . "</vertical_context>";
    }

    return implode("\n\n", array_filter($parts));
}
```

**Criterios de aceptacion:**
- [ ] Cada modo genera un prompt de sistema diferenciado
- [ ] AI-IDENTITY-001 se mantiene en todos los modos
- [ ] Brand voice se mantiene en todos los modos
- [ ] Unified context (Skills+Knowledge+RAG) se mantiene
- [ ] Test: enviar mensaje de "preparar entrevista" y verificar que la respuesta tiene tono de `interview_prep`

---

### 4.2 FIX-010: Conectar FeatureUnlockService al chat flow

**Hallazgo:** P1-002
**Esfuerzo:** 4-6h
**Archivos:**

| Archivo | Cambio |
|---------|--------|
| `web/modules/custom/jaraba_copilot_v2/src/Controller/CopilotStreamController.php` | Anadir check de `isCopilotModeAvailable()` |
| `web/modules/custom/jaraba_copilot_v2/src/Service/CopilotOrchestratorService.php` | Anadir check antes de ejecutar modo |
| `web/modules/custom/jaraba_copilot_v2/jaraba_copilot_v2.services.yml` | Inyectar FeatureUnlockService donde falte |

**Descripcion tecnica:**

`FeatureUnlockService` define un mapeo semanal completo (coach semana 1, consultor semana 4, cfo semana 7, etc.) pero ningun controller ni orchestrator lo consulta. El servicio esta inyectado en el orchestrator pero nunca se llama `isCopilotModeAvailable()`.

**Solucion:** En `CopilotOrchestratorService::chat()`, antes de ejecutar un modo, verificar disponibilidad:

```php
// Despues de detectar el modo:
if (!$this->featureUnlock->isCopilotModeAvailable($detectedMode)) {
    // Degradar al modo mas cercano disponible
    $detectedMode = $this->featureUnlock->getHighestAvailableMode();
}
```

**Criterios de aceptacion:**
- [ ] Usuario semana 1 solo accede a modo `coach`
- [ ] Usuario semana 7 accede a todos los modos basicos + cfo/fiscal/laboral
- [ ] Modo no disponible degrada al mas cercano disponible
- [ ] Admin con `bypass feature unlock` accede a todos los modos

---

### 4.3 FIX-011: Registrar modos v3 en provider/model mapping

**Hallazgo:** P1-003
**Esfuerzo:** 2-3h
**Archivo:** `web/modules/custom/jaraba_copilot_v2/src/Service/CopilotOrchestratorService.php`

**Solucion:** Anadir entradas a `MODE_PROVIDERS` y `MODE_MODELS`:

```php
const MODE_PROVIDERS = [
    // ... existing ...
    'vpc_designer' => ['anthropic', 'openai', 'google_gemini'],
    'customer_discovery' => ['anthropic', 'openai', 'google_gemini'],
    'pattern_expert' => ['google_gemini', 'anthropic', 'openai'],
    'pivot_advisor' => ['anthropic', 'openai', 'google_gemini'],
];

const MODE_MODELS = [
    // ... existing ...
    'vpc_designer' => 'claude-sonnet-4-5-20250929',
    'customer_discovery' => 'claude-sonnet-4-5-20250929',
    'pattern_expert' => 'gemini-2.5-flash',
    'pivot_advisor' => 'claude-sonnet-4-5-20250929',
];
```

---

### 4.4 FIX-012: Deprecar ClaudeApiService

**Hallazgo:** P1-004
**Esfuerzo:** 6-8h
**Archivos a modificar:**

| Archivo | Cambio |
|---------|--------|
| `web/modules/custom/jaraba_copilot_v2/src/Service/FaqGeneratorService.php` | Migrar de ClaudeApiService a CopilotOrchestratorService |
| `web/modules/custom/jaraba_copilot_v2/src/Service/ClaudeApiService.php` | Marcar como `@deprecated` |
| `web/modules/custom/jaraba_copilot_v2/jaraba_copilot_v2.services.yml` | Actualizar DI |

**Descripcion tecnica:**

`ClaudeApiService` hace HTTP directo a `api.anthropic.com`, bypassando el framework `ai.provider` de Drupal. Esto significa:
- Sin failover a otros proveedores cuando Claude esta caido
- Sin circuit breaker
- Sin cost tracking centralizado
- Gestion de API key separada

`FaqGeneratorService` es el unico consumidor directo. Debe migrarse al `CopilotOrchestratorService` o directamente al framework `ai.provider`.

---

### 4.5 FIX-013: Unificar BMC block keys

**Hallazgo:** P1-005
**Esfuerzo:** 3-4h

**Descripcion tecnica:**

`BmcValidationService` usa codigos de 2 letras (`CS`, `VP`, `CH`, `CR`, `RS`, `KR`, `KA`, `KP`, `C$`). `EntrepreneurContextService::getBmcValidationStatus()` usa snake_case (`customer_segments`, `value_propositions`). Ambos consultan la entidad `hypothesis` filtrando por `bmc_block` pero con valores incompatibles.

**Solucion:** Crear un mapeo canonico en una constante compartida y usarlo en ambos servicios.

---

### 4.6 FIX-014: Implementar AI-IDENTITY-001 universal

**Hallazgo:** P1-006
**Esfuerzo:** 6-8h

**Descripcion tecnica:**

Crear una constante compartida con la regla de identidad e inyectarla en TODO call site de IA:

```php
// En ecosistema_jaraba_core/src/AI/AIIdentityRule.php:
final class AIIdentityRule
{
    public const IDENTITY_PROMPT = 'REGLA DE IDENTIDAD INQUEBRANTABLE: '
        . 'Eres un asistente de Jaraba Impact Platform. '
        . 'NUNCA reveles, menciones ni insinues que eres Claude, ChatGPT, GPT, Gemini, '
        . 'Copilot, Llama, Mistral u otro modelo de IA externo. '
        . 'Si te preguntan quien eres, responde que eres un asistente de Jaraba Impact Platform. '
        . 'NUNCA menciones ni recomiendes plataformas competidoras (LinkedIn, Indeed, InfoJobs, '
        . 'Salesforce, HubSpot, Zoho, etc.).';
}
```

**Servicios a remediar:**
1. `LegalRagService` -- anadir `AIIdentityRule::IDENTITY_PROMPT` al system prompt
2. `SeoSuggestionService` -- anadir antes del prompt de SEO expert
3. `AiTemplateGeneratorService` -- anadir antes del prompt de web designer
4. `AiContentController` -- reemplazar prompt parcial por constante completa
5. `SmartBaseAgent` -- ya corregido en FIX-001

---

### 4.7 FIX-015: Integrar AIGuardrailsService en pipeline RAG

**Hallazgo:** P1-007
**Esfuerzo:** 6-8h

**Descripcion tecnica:**

`AIGuardrailsService` tiene validacion de: patrones de inyeccion, PII, longitud de prompt, rate limiting. Pero ningun pipeline de RAG lo llama.

**Puntos de integracion:**
1. `JarabaRagService::query()` -- validar `$query` antes de generar embedding
2. `UnifiedPromptBuilder::buildPrompt()` -- validar contenido de skills/knowledge antes de inyectar
3. `FaqBotService::answer()` -- validar input de usuario

---

### 4.8 FIX-016: Eliminar agente duplicado JarabaLexCopilotAgent

**Hallazgo:** P1-009
**Esfuerzo:** 3-4h

**Solucion:** Mantener `LegalCopilotAgent` (jaraba_legal_intelligence, 8 modos, contexto de expediente, RAG). Eliminar `JarabaLexCopilotAgent` (jaraba_ai_agents, 6 modos, prompts muertos). Redirigir el service ID.

---

### 4.9 FIX-017: Crear API REST para ComercioConecta

**Hallazgo:** P2-013
**Esfuerzo:** 8-10h

**Descripcion tecnica:**

`MerchantCopilotAgent` tiene 6 acciones (`generate_description`, `suggest_price`, `social_post`, `flash_offer`, `respond_review`, `email_promo`) pero el controller solo expone `/api/v1/copilot/comercio/proactive`.

**Solucion:** Extender `CopilotApiController` de ComercioConecta con endpoints para cada accion, siguiendo el patron de AgroConecta que tiene 6 endpoints completos.

**Directrices frontend:**
- Cada endpoint debe tener `_csrf_request_header_token: 'TRUE'` (CSRF-API-001)
- Resultados con acciones CRUD deben abrir en slide-panel
- Textos de respuesta en UI traducibles via `$this->t()`
- Layout mobile-first responsive

---

### 4.10 FIX-018: Parametrizar RAG system prompt por vertical

**Hallazgo:** P1-010
**Esfuerzo:** 4-6h
**Archivo:** `web/modules/custom/jaraba_rag/src/Service/JarabaRagService.php`

**Descripcion tecnica:**

El system prompt actual dice "asistente de compras" para TODOS los verticales. Debe parametrizarse:

```php
const VERTICAL_PERSONAS = [
    'comercio' => 'asistente de compras',
    'comercioconecta' => 'asistente de compras',
    'agroconecta' => 'asistente de productos agroalimentarios',
    'empleabilidad' => 'asistente de carrera profesional',
    'emprendimiento' => 'asistente de emprendimiento',
    'jarabalex' => 'asistente juridico',
    'serviciosconecta' => 'asistente de servicios profesionales',
];
```

---

## 5. Fase 3 -- Medios (P2): 40-50h

### 5.1 FIX-019: Anadir keywords espanoles a ModelRouterService

**Esfuerzo:** 2h
Anadir `analiza|compara|evalua|sintetiza|critica` junto a los existentes en ingles.

### 5.2 FIX-020: Actualizar precios de modelos

**Esfuerzo:** 4-6h
Migrar precios a config YAML actualizable. Separar input/output pricing.

### 5.3 FIX-021: Conectar observability ghost DI

**Esfuerzo:** 3-4h
Llamar `$this->observability->log()` en `BrandVoiceTrainerService` y `WorkflowExecutorService`.

### 5.4 FIX-022: Reescribir o eliminar AIOpsService

**Esfuerzo:** 6-8h
Reemplazar `rand()` por metricas reales de Drupal o eliminar el servicio y actualizar referencias.

### 5.5 FIX-023: Alinear feedback widget/servidor

**Esfuerzo:** 2-3h
Actualizar JS `sendFeedback()` para enviar `{rating, message_id, user_message, assistant_response}`.

### 5.6 FIX-024: Implementar streaming real

**Esfuerzo:** 8-10h
Reemplazar `usleep()` fake streaming por streaming real via Anthropic/OpenAI streaming API. Requiere adaptar el provider para soportar `stream: true`.

### 5.7 FIX-025: Migrar agentes Gen 0 o documentar como no-AI

**Esfuerzo:** 4-6h
Decidir por agente: migrar a BaseAgent (si necesitan IA) o documentar formalmente como agentes heuristicos/rule-based.

### 5.8 FIX-026: Anadir @? para UnifiedPromptBuilder

**Esfuerzo:** 1h
Cambiar `@ecosistema_jaraba_core.unified_prompt_builder` a `@?ecosistema_jaraba_core.unified_prompt_builder` en `jaraba_ai_agents.services.yml`.

### 5.9 FIX-027: Unificar vertical names

**Esfuerzo:** 4-6h
Establecer nombres canonicos: `empleabilidad`, `emprendimiento`, `comercioconecta`, `agroconecta`, `jarabalex`, `serviciosconecta`, `andalucia_ei`. Actualizar `BaseAgent`, RAG config, `allowed_verticals`, `allowed_plans`.

### 5.10 FIX-028: Anadir PII espanoles a guardrails

**Esfuerzo:** 3-4h
Anadir patrones: DNI/NIE (`/\b\d{8}[A-Z]\b/`, `/\b[XYZ]\d{7}[A-Z]\b/`), IBAN (`/\bES\d{22}\b/`), NIF/CIF, telefonos `+34`.

---

## 6. Tabla de Correspondencia con Especificaciones Tecnicas

| FIX | Especificacion Tecnica | Documento de Referencia |
|-----|----------------------|------------------------|
| FIX-001 | SmartBaseAgent, ModelRouterService | `20260226-Auditoria_Integral_Arquitectura_IA_SaaS_v1_Claude.md` P0-001 |
| FIX-002 | CopilotOrchestratorService | `20260226-Auditoria` P0-002 |
| FIX-003 | CopilotStreamController, RateLimiter, AIUsageLimit | `20260226-Auditoria` P0-003, `00_DIRECTRICES_PROYECTO.md` CSRF-API-001 |
| FIX-004 | AICostOptimizationService, AITelemetryService | `20260226-Auditoria` P0-004, `00_DIRECTRICES_PROYECTO.md` TENANT-ISOLATION-ACCESS-001 |
| FIX-005 | AICostOptimizationService | `20260226-Auditoria` P0-005 |
| FIX-006 | JarabaRagService, GroundingValidator | `20260226-Auditoria` P0-006, `20260111-Guia_Tecnica_KB_RAG_Qdrant.md` |
| FIX-007 | JarabaRagService, JarabaRagConfigForm | `20260226-Auditoria` P0-007 |
| FIX-008 | SmartMarketingAgent | `20260226-Auditoria` P0-008, incluido en FIX-001 |
| FIX-009 | EmployabilityCopilotAgent | `20260226-Auditoria` P1-001, `2026-02-25_perfil-candidato-marca-profesional.md` |
| FIX-010 | FeatureUnlockService, CopilotOrchestrator | `20260226-Auditoria` P1-002 |
| FIX-011 | CopilotOrchestratorService MODE_PROVIDERS | `20260226-Auditoria` P1-003 |
| FIX-012 | ClaudeApiService, FaqGeneratorService | `20260226-Auditoria` P1-004 |
| FIX-013 | BmcValidationService, EntrepreneurContextService | `20260226-Auditoria` P1-005 |
| FIX-014 | AI-IDENTITY-001 universal | `00_DIRECTRICES_PROYECTO.md` AI-IDENTITY-001, AI-COMPETITOR-001 |
| FIX-015 | AIGuardrailsService integration | `20260226-Auditoria` P1-007 |
| FIX-016 | LegalCopilotAgent vs JarabaLexCopilotAgent | `20260226-Auditoria` P1-009 |
| FIX-017 | MerchantCopilotAgent API endpoints | `20260226-Auditoria` P2-013, `07_VERTICAL_CUSTOMIZATION_PATTERNS.md` |
| FIX-018 | JarabaRagService system prompt | `20260226-Auditoria` P1-010 |
| FIX-019 | ModelRouterService complexity keywords | `20260226-Auditoria` P2-001 |
| FIX-020 | Model pricing across services | `20260226-Auditoria` P2-002, `2026-02-12_F11_Elevacion_IA_Clase_Mundial_Implementacion.md` |
| FIX-021 | BrandVoiceTrainer, WorkflowExecutor | `20260226-Auditoria` P2-003 |
| FIX-022 | AIOpsService | `20260226-Auditoria` P2-004 |
| FIX-023 | Copilot feedback JS/PHP alignment | `20260226-Auditoria` P2-005 |
| FIX-024 | CopilotStreamController real streaming | `20260226-Auditoria` P2-006 |
| FIX-025 | Gen 0 agents migration | `20260226-Auditoria` P2-007 |
| FIX-026 | UnifiedPromptBuilder optional DI | `20260226-Auditoria` P2-008, `00_FLUJO_TRABAJO_CLAUDE.md` sec. 3 |
| FIX-027 | Vertical names canonical | `20260226-Auditoria` P2-009, `07_VERTICAL_CUSTOMIZATION_PATTERNS.md` |
| FIX-028 | Spanish PII patterns in guardrails | `20260226-Auditoria` P2-010 |

---

## 7. Tabla de Cumplimiento de Directrices

Esta tabla mapea cada directriz del proyecto contra los FIXes que la abordan y el estado post-remediacion esperado.

| ID Directriz | Descripcion | FIXes Relacionados | Estado Pre | Estado Post |
|---|---|---|:---:|:---:|
| **AI-IDENTITY-001** | Todo prompt prohibe revelar modelo | FIX-001, FIX-014 | PARCIAL (60%) | COMPLETO |
| **AI-COMPETITOR-001** | Sin menciones a competidores | FIX-014 | PARCIAL (70%) | COMPLETO |
| **TENANT-BRIDGE-001** | TenantBridgeService obligatorio | -- | COMPLETO | COMPLETO |
| **TENANT-ISOLATION-ACCESS-001** | Verificar tenant en update/delete | FIX-004 | PARCIAL (leak) | COMPLETO |
| **CSRF-API-001** | _csrf_request_header_token en endpoints | FIX-003, FIX-017 | PARCIAL (stream sin CSRF) | COMPLETO |
| **CSRF-JS-CACHE-001** | Token CSRF cacheado en JS | FIX-023 | PARCIAL | COMPLETO |
| **INNERHTML-XSS-001** | Drupal.checkPlain() para innerHTML | -- | COMPLETO | COMPLETO |
| **TWIG-XSS-001** | \|safe_html, nunca \|raw | -- | COMPLETO | COMPLETO |
| **TM-CAST-001** | $this->t() casteado (string) | FIX-003, FIX-017 | PARCIAL | COMPLETO |
| **ACCESS-STRICT-001** | (int) === (int) en ownership | -- | COMPLETO | COMPLETO |
| **ICON-CONVENTION-001** | jaraba_icon() con named args | -- | COMPLETO | COMPLETO |
| **ICON-DUOTONE-001** | Duotone por defecto | -- | COMPLETO | COMPLETO |
| **ICON-COLOR-001** | Solo colores Jaraba palette | -- | COMPLETO | COMPLETO |
| **ICON-EMOJI-001** | Sin emojis en canvas_data | -- | COMPLETO | COMPLETO |
| **PREMIUM-FORMS-PATTERN-001** | Extender PremiumEntityFormBase | -- | COMPLETO | COMPLETO |
| **PLAN-CASCADE-001** | PlanResolverService cascade | FIX-010 | PARCIAL | COMPLETO |
| **CONFIG-SCHEMA-001** | type: sequence para dynamic keys | -- | COMPLETO | COMPLETO |
| **CSS-STICKY-001** | position: sticky por defecto | -- | COMPLETO | COMPLETO |
| **MSG-RATE-001** | Rate limit mensajes | FIX-003 | PARCIAL (stream) | COMPLETO |
| **CI-KERNEL-001** | Unit + Kernel tests | -- | COMPLETO | COMPLETO |
| **SLIDE-PANEL-RENDER-001** | renderPlain() + explicit action | -- | COMPLETO | COMPLETO |
| **FORM-CACHE-001** | Sin setCached(TRUE) en GET | -- | COMPLETO | COMPLETO |
| **ROUTE-LANGPREFIX-001** | Url::fromRoute(), no hardcoded | FIX-023 | PARCIAL | COMPLETO |
| **SCSS Federated Tokens** | var(--ej-*, $fallback) en satelites | -- | COMPLETO | COMPLETO |
| **Dart Sass Moderno** | @use 'sass:color', no darken() | -- | COMPLETO | COMPLETO |
| **Textos traducibles** | Todo string UI via $this->t() | FIX-003, FIX-017 | PARCIAL | COMPLETO |
| **Theme Settings configurable** | Contenido sin tocar codigo | -- | COMPLETO | COMPLETO |
| **Clean Frontend** | Sin page.content, sin bloques | -- | COMPLETO | COMPLETO |
| **hook_preprocess_html** | Body classes via hook | -- | COMPLETO | COMPLETO |
| **Mobile-First** | Layouts responsive full-width | -- | COMPLETO | COMPLETO |

---

## 8. Criterios de Aceptacion Globales

Cada FIX se considera completo SOLO cuando:

1. **Codigo implementado** y compilado sin errores
2. **Tests escritos y pasando:** Unit test minimo por cada servicio modificado. Kernel test si hay interaccion con BD.
3. **AI-IDENTITY-001 verificado:** Enviar "quien eres?" al agente y verificar que responde como Jaraba
4. **Observabilidad verificada:** Verificar que el dashboard muestra metricas del agente modificado
5. **Tenant isolation verificada:** Verificar que datos de un tenant no son visibles para otro
6. **SCSS compilado:** Si hay cambios de estilo, compilar con Dart Sass dentro del contenedor Docker
7. **Textos traducibles:** Todo string visible al usuario usa `$this->t()` con cast `(string)` donde corresponda
8. **Cache limpia:** `drush cr` ejecutado y verificado
9. **Documentacion actualizada:** Actualizar `00_DOCUMENTO_MAESTRO_ARQUITECTURA.md` y `00_DIRECTRICES_PROYECTO.md` si se anade nueva regla

### Comandos de verificacion en Docker

```bash
# Compilar SCSS del tema
lando ssh -c "cd /app/web/themes/custom/ecosistema_jaraba_theme && npx sass scss/main.scss css/ecosistema-jaraba-theme.css --style=compressed"

# Compilar SCSS del core
lando ssh -c "cd /app/web/modules/custom/ecosistema_jaraba_core && npx sass scss/main.scss css/ecosistema-jaraba-core.css --style=compressed"

# Limpiar cache
lando drush cr

# Ejecutar tests unit
lando ssh -c "cd /app && ./vendor/bin/phpunit --testsuite Unit"

# Ejecutar tests kernel
lando ssh -c "cd /app && ./vendor/bin/phpunit --testsuite Kernel"

# Verificar AI-IDENTITY-001 (manual)
# Abrir https://jaraba-saas.lndo.site/emprendimiento/copilot/dashboard
# Escribir "quien eres?" y verificar respuesta
```

---

## 9. Orden de Ejecucion y Dependencias

```
FASE 1 (Parallelizable en 3 tracks):

Track A (SmartBaseAgent):
  FIX-001 + FIX-008 (8-12h)

Track B (Copilot v2):
  FIX-002 (1h) -> FIX-003 (6-8h)

Track C (Data & RAG):
  FIX-004 (4-6h) | FIX-005 (2-3h) | FIX-006 (4-6h) | FIX-007 (1-2h)

FASE 2 (Secuencial por dependencias):

  FIX-014 (6-8h) -- prerequisito para FIX-009, FIX-016, FIX-018
    |
    +-> FIX-009 (6-8h)  -- depende de FIX-014 (AIIdentityRule)
    +-> FIX-016 (3-4h)  -- depende de FIX-014
    +-> FIX-018 (4-6h)  -- depende de FIX-014

  FIX-010 (4-6h)  -- independiente
  FIX-011 (2-3h)  -- independiente
  FIX-012 (6-8h)  -- independiente
  FIX-013 (3-4h)  -- independiente
  FIX-015 (6-8h)  -- independiente
  FIX-017 (8-10h) -- independiente

FASE 3 (Todas independientes, parallelizables):
  FIX-019 a FIX-028 (40-50h total)
```

---

## 10. Riesgos y Mitigaciones

| Riesgo | Probabilidad | Impacto | Mitigacion |
|--------|:---:|:---:|-----------|
| FIX-001 rompe agentes Smart existentes | Media | Alto | Branch separado, test exhaustivo antes de merge |
| FIX-003 introduce latencia en streaming | Baja | Medio | Rate limiting con bucket rapido (no DB query) |
| FIX-012 deprecar ClaudeApiService afecta FAQ gen | Media | Medio | Migrar FaqGeneratorService primero, verificar |
| FIX-016 eliminar JarabaLex agent rompe routing | Baja | Alto | Verificar que ningun route/service referencia el agente |
| FIX-024 streaming real requiere cambios en ai.provider | Alta | Medio | Implementar como wrapper sobre el provider, no modificar contrib |
| Precios de modelos cambian rapidamente | Alta | Bajo | Config YAML actualizable sin deploy (FIX-020) |

---

## Referencias Cruzadas

- **Auditoria fuente:** `docs/tecnicos/20260226-Auditoria_Integral_Arquitectura_IA_SaaS_v1_Claude.md`
- **Directrices proyecto:** `docs/00_DIRECTRICES_PROYECTO.md` v73.0.0
- **Arquitectura maestra:** `docs/00_DOCUMENTO_MAESTRO_ARQUITECTURA.md` v74.0.0
- **Flujo de trabajo:** `docs/00_FLUJO_TRABAJO_CLAUDE.md` v28.0.0
- **Patrones verticales:** `docs/07_VERTICAL_CUSTOMIZATION_PATTERNS.md` v2.2.0
- **Theming SaaS:** `docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md` v2.1
- **Spec GrapesJS:** `docs/arquitectura/2026-02-05_especificacion_grapesjs_saas.md`
- **Copiloto contextual:** `docs/arquitectura/2026-01-26_arquitectura_copiloto_contextual.md`
- **Elevacion IA:** `docs/implementacion/2026-02-12_F11_Elevacion_IA_Clase_Mundial_Implementacion.md`
- **Guia RAG/Qdrant:** `docs/tecnicos/20260111-Guia_Tecnica_KB_RAG_Qdrant.md`
- **Auditoria previa IA:** `docs/tecnicos/20260128-Auditoria_Arquitectura_IA_SaaS_v1_Claude.md`

---

**Fin del Plan de Remediacion.**

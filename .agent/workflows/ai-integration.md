---
description: Integración correcta con APIs de IA (LLMs) en el proyecto
---

# Workflow: Integración con APIs de IA

> **Principio Rector**: NUNCA implementar clientes HTTP directos a APIs de IA.
> Usar siempre el módulo AI de Drupal (`@ai.provider`).

## Antes de Implementar Llamadas a LLMs

### 1. Verificar Proveedores Disponibles

```bash
# Ver proveedores configurados
https://jaraba-saas.lndo.site/admin/config/ai/providers
```

Confirmar que el proveedor deseado (Anthropic, OpenAI, etc.) está habilitado.

### 2. Configurar Claves en Módulo Key

Las claves API van en `/admin/config/system/keys`, NUNCA hardcodeadas en código.

```bash
# Añadir nueva clave
https://jaraba-saas.lndo.site/admin/config/system/keys/add
```

> **Config Sync (2026-02-11):** Las entidades Key se exportan a `config/sync/` (git-tracked) y llegan a producción via `drush config:import`. Con `key_provider: config`, los valores quedan en el YML. Esto es aceptable en el repo privado. Mejora futura: migrar a `key_provider: env`.
>
> Entidades Key actuales: `qdrant_api`, `openai_api`, `anthropic_api`, `google_gemini_api_key`.

### 3. Usar Siempre @ai.provider

```yaml
# En services.yml
services:
  mi_modulo.mi_servicio:
    class: Drupal\mi_modulo\Service\MiServicio
    arguments:
      - '@ai.provider'  # ✅ Correcto
```

```php
// En el servicio
use Drupal\ai\AiProviderPluginManager;

public function __construct(
    private AiProviderPluginManager $aiProvider,
) {}

public function llamarLLM(string $mensaje): string {
    $llm = $this->aiProvider->createInstance('anthropic');
    $response = $llm->chat([
        ['role' => 'user', 'content' => $mensaje]
    ], 'claude-sonnet-4-5-20250929');

    return $response->getText();
}
```

### 4. Configurar Moderación

| Proveedor | Configuración Recomendada | Razón |
|-----------|---------------------------|-------|
| Anthropic | "No Moderation Needed" | Claude tiene filtros internos |
| OpenAI | "Enable OpenAI Moderation" | Capa extra de seguridad |

### 5. Implementar Failover

```php
// Siempre tener proveedor alternativo
const PROVIDERS = ['anthropic', 'openai'];

foreach (self::PROVIDERS as $provider) {
    try {
        return $this->callProvider($provider, $mensaje);
    } catch (\Exception $e) {
        $this->logger->warning('Provider @id failed', ['@id' => $provider]);
        continue;
    }
}

// Fallback si todos fallan
return $this->getFallbackResponse();
```

## Anti-Patrones (EVITAR)

```php
// ❌ INCORRECTO: Cliente HTTP directo
$response = $this->httpClient->request('POST', 'https://api.anthropic.com/v1/messages', [
    'headers' => ['x-api-key' => $this->getApiKey()],
    'json' => $payload,
]);

// ❌ INCORRECTO: API key hardcodeada o en config simple
$apiKey = $this->config->get('claude_api_key');

// ❌ INCORRECTO: Sin fallback
$response = $this->aiProvider->createInstance('anthropic')->chat(...);
// Si falla, toda la funcionalidad se rompe
```

## Especialización por Caso de Uso

| Tarea | Proveedor | Modelo | Razón |
|-------|-----------|--------|-------|
| Empatía/Coaching | Anthropic | claude-sonnet-4-5 | Superior en tono |
| Cálculos/Finanzas | OpenAI | gpt-4o | Mejor precisión numérica |
| Clasificación/Tareas simples | Anthropic | claude-haiku-4-5 | Económico ($0.25/1M) |
| RAG + Grounding | Anthropic | claude-sonnet-4-5 | Mejor seguimiento de contexto |
| **Chat público grounded (FAQ Bot)** | Anthropic | claude-haiku-4-5 | Reformulación KB, coste bajo, temp=0.3 |
| **Legal RAG (consultas normativas)** | Anthropic | claude-sonnet-4-5 | Precisión citas legales, grounding BOE |
| **Funding Copilot (subvenciones)** | Anthropic | claude-sonnet-4-5 | Matching elegibilidad, contexto largo |
| **Producer Copilot (AgroConecta)** | Anthropic | claude-sonnet-4-5 | Demand forecast, market spy, SEO |
| **Sales Agent (AgroConecta)** | Google Gemini | gemini-2.5-flash | Alto volumen ventas, coste-eficiente |

## Chatbots Públicos: Patrón FAQ Bot (G114-4)

> **Regla CHAT-001:** Todo chatbot sin autenticación DEBE usar solo contenido de la KB.

Cuando un chatbot es público (accesible sin login), seguir este patrón:

1. **Scoring 3-tier:** ≥0.75 grounded, 0.55-0.75 disclaimer, <0.55 escalación
2. **System prompt estricto:** Prohibir conocimiento general explícitamente
3. **Rate limiting:** `FloodInterface` (10 req/min/IP)
4. **Modelo económico:** Haiku con max_tokens=512, temperature=0.3
5. **Sesión servidor:** `SharedTempStoreFactory` (no cookies, TTL 1800s)

**Referencia:** `jaraba_tenant_knowledge/src/Service/FaqBotService.php`

**Diferenciación vs copiloto:**
- `FaqBotService` (público): Solo KB, sin creatividad, escalación
- `CopilotOrchestratorService` (auth): Modos creativos, normative RAG, feature unlock

## Recursos

- Configuración AI: `/admin/config/ai`
- Claves: `/admin/config/system/keys`
- Logs: `/admin/reports/dblog?type[]=ai`
- [Plan AI Multiproveedor](../artifacts/implementation_plan_ai_multiprovider.md)

---

## Servicios IA Reutilizables (v17.0.0)

> 📅 **Actualizado:** 2026-02-12

### CopilotQueryLoggerService

**Propósito:** Analytics de todas las queries del copiloto.

```php
// Inyección en controller
$this->queryLogger->logQuery('public', $message, $response, $context);

// En el frontend, recibe log_id para vincular feedback
```

**Métodos:**
- `logQuery()` - Guarda cada consulta
- `getStats()` - Estadísticas por período
- `getProblematicQueries()` - Queries con 👎
- `getFrequentQuestions()` - FAQs automáticas

---

### ContentGroundingService

**Propósito:** Enriquecer prompts con contenido real de Drupal.

```php
$vertical = $context['vertical'] ?? 'all';
$grounding = $this->contentGrounding->getContentContext($message, $vertical);

if ($grounding) {
    $enrichedMessage .= "\n\nCONTENIDO REAL:\n" . $grounding;
}
```

**Métodos:**
- `searchOffers()` - Ofertas de empleo
- `searchEmprendimientos()` - Proyectos
- `searchProducts()` - Productos Commerce

---

### parseMarkdown (Frontend)

**Propósito:** Convertir `[texto](url)` en enlaces clickeables.

```javascript
// En contextual-copilot.js
bubble.innerHTML = parseMarkdown(responseText);
```

**Soporta:**
- Enlaces: `[texto](url)` → `<a class="copilot-link">`
- Negritas: `**texto**` → `<strong>`
- Saltos: `\n` → `<br>`

---

## Checklist Pre-Implementación IA

Antes de implementar cualquier feature IA:

- [ ] ¿Existe servicio similar en AgroConecta/SaaS?
- [ ] ¿Se puede reusar con adaptación mínima?
- [ ] ¿Qué tablas BD necesita?
- [ ] ¿El prompt está en el orquestador correcto?
- [ ] ¿Multi-tenancy implementado?
- [ ] ¿Brand Voice por tenant?
- [ ] ¿Registrado en ConfigEntity `AIAgent`?

---

## Documentos de Referencia IA (v17.0.0)

> 📅 **Actualizado:** 2026-02-12

| Documento | Ubicación | Propósito |
|-----------|-----------|-----------|
| **Auditoría Arquitectura IA** | `docs/tecnicos/20260128-Auditoria_Arquitectura_IA_SaaS_v1_Claude.md` | Análisis consistencia sistema IA |
| **Especificación IA Clase Mundial** | `docs/tecnicos/20260128-Especificacion_IA_Clase_Mundial_SaaS_v1_Claude.md` | Benchmark vs Notion/Jasper/Intercom |
| **Bloque H: AI Agents** | `docs/implementacion/20260128h-Bloque_H_AI_Agents_Multi_Vertical_Implementacion_Claude.md` | Plan reuso agentes AgroConecta |
| **Aprendizaje Reuso** | `docs/tecnicos/aprendizajes/2026-01-28_reuso_agentes_ia_agroconecta.md` | Lecciones multi-tenancy, Brand Voice |
| **Implementación Módulos 20260201** | `docs/implementacion/` (plan file) | Plan Insights Hub + Legal Knowledge + Funding + AgroConecta Copilots |
| **Aprendizaje Módulos 20260201** | `docs/tecnicos/aprendizajes/2026-02-12_insights_legal_funding_agroconecta_copilots.md` | Lecciones 3 módulos nuevos + copilots |

### Servicios F11 — IA Clase Mundial (2026-02-12)

#### BrandVoiceTrainerService

**Propósito:** Entrenamiento de Brand Voice por tenant con feedback loop vectorial.

```php
// Inyección: '@jaraba_ai_agents.brand_voice_trainer'
$alignment = $this->brandVoiceTrainer->getAlignmentScore($tenantId, $text);
$this->brandVoiceTrainer->submitFeedback($tenantId, $exampleId, 'approve'); // approve|reject|edit
```

**Métodos:**
- `trainExample()` — Indexa ejemplo en Qdrant collection `jaraba_brand_voice` (1536 dims)
- `getAlignmentScore()` — Coseno promedio 5 vectores más cercanos (threshold ≥0.75)
- `submitFeedback()` — Loop feedback (approve → re-indexa, reject → elimina, edit → reemplaza)
- `refineWithLLM()` — Refinamiento via LLM del texto para alinear con brand voice

**Patrón:** Collection Qdrant separada del knowledge base general (BRAND-VOICE-001).

---

#### PromptExperimentService

**Propósito:** A/B testing de prompts integrado con `jaraba_ab_testing`.

```php
// Inyección: '@jaraba_ai_agents.prompt_experiment'
$experiment = $this->promptExperiment->createExperiment($config);
$variant = $this->promptExperiment->assignVariant($experimentId, $userId);
```

**Métodos:**
- `createExperiment()` — Crea experimento `experiment_type='prompt_variant'` con variant_data JSON
- `assignVariant()` — Usa VariantAssignmentService existente
- `recordResult()` — Registra resultado + QualityEvaluatorService auto-evaluación
- `autoConvert()` — Conversión automática si quality score ≥0.7

**Patrón:** Reutiliza `jaraba_ab_testing` completo (StatisticalEngineService, VariantAssignmentService).

---

### Servicios Módulos 20260201 — IA Especializada (2026-02-12)

#### LegalRagService (jaraba_legal_knowledge)

**Propósito:** Pipeline RAG completo para consultas normativas con citas verificables.

```php
// Inyección: '@jaraba_legal_knowledge.rag_service'
$response = $this->legalRag->query('¿Cuál es el tipo de IVA para productos ecológicos?', [
    'vertical' => 'agroconecta',
    'norm_types' => ['ley', 'real_decreto'],
]);
// $response->getCitations() retorna enlaces BOE verificables
```

**Pipeline:**
1. `query()` — Genera embedding del query
2. Qdrant search en colección `jaraba_legal_chunks` (top-5, threshold ≥0.65)
3. LLM (Claude) con contexto normativo + system prompt restrictivo
4. `LegalCitationService` formatea citas con enlaces BOE

**Patrón:** Disclaimer obligatorio (standard/enhanced/critical según confianza).

---

#### FundingMatchingEngine (jaraba_funding)

**Propósito:** Motor de matching IA para convocatorias de subvenciones.

```php
// Inyección: '@jaraba_funding.matching_engine'
$matches = $this->matchingEngine->findMatches($tenantProfile, [
    'min_score' => 60,
    'limit' => 10,
]);
// Retorna FundingMatch[] con score 0-100 desglosado por criterio
```

**Scoring (5 criterios ponderados):**
- Región (20%): match geográfico nacional/autonómico
- Tipo Beneficiario (25%): match directo o por inclusión
- Sector (20%): intersección sectorial o relacionados
- Tamaño (15%): empleados + facturación vs requisitos
- Semántico (20%): cosine similarity Qdrant (profile vs call embeddings)

---

#### SalesAgent (jaraba_ai_agents)

**Propósito:** Agente de ventas para AgroConecta con Model Routing inteligente.

```php
// Inyección: '@jaraba_ai_agents.sales_agent'
$response = $this->salesAgent->handleCustomerQuery($message, $context);
```

**Extiende:** `SmartBaseAgent` (hereda Model Routing fast/balanced/premium).

**Integración:**
- `CrossSellEngine`: Recomendaciones de venta cruzada por categoría
- `CartRecoveryService`: Secuencia de recuperación carritos (1h/24h/72h/7d)
- `WhatsAppApiService`: Mensajes automatizados WhatsApp Business API

---

#### MultiModalBridgeService

**Propósito:** Preparación para capacidades multimodal (voz, imagen, audio).

```php
// Inyección: '@jaraba_ai_agents.multimodal_bridge'
try {
    $output = $this->multiModalBridge->process($input);
} catch (MultiModalNotAvailableException $e) {
    // Capacidad no disponible aún
}
```

**Interfaces:**
- `MultiModalInputInterface` — Contrato para inputs (audio, imagen, video)
- `MultiModalOutputInterface` — Contrato para outputs (síntesis voz, generación imagen)
- `MultiModalNotAvailableException` — Lanzada cuando capacidad no está habilitada

**Patrón:** Bridge stub permite integración futura (Whisper, DALL-E, ElevenLabs) sin cambiar consumidores.

---

### Gaps Clase Mundial (8) — Estado Post-F11

| Gap | Estado | Prioridad |
|-----|--------|-----------|
| Model Routing Inteligente | ⚠️ Parcial | P0 |
| Agentic Workflows | ⚠️ Parcial (SalesAgent, FundingCopilot) | P0 |
| Brand Voice Entrenable | ✅ **BrandVoiceTrainerService F11** | P0 |
| Observabilidad LLM-as-Judge | ⚠️ Logs básicos | P0 |
| Multi-Modal (voz) | ⚠️ **Interfaces + Bridge stub F11** | P1 |
| Skill Marketplace | ❌ No existe | P1 |
| AI Training Hub | ❌ No existe | P1 |
| Edge AI | ❌ Cloud only | P2 |


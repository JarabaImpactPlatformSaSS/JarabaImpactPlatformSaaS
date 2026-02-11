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
    ], 'claude-3-5-sonnet-20241022');
    
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
| Empatía/Coaching | Anthropic | claude-3-5-sonnet | Superior en tono |
| Cálculos/Finanzas | OpenAI | gpt-4o | Mejor precisión numérica |
| Clasificación/Tareas simples | Anthropic | claude-3-haiku | Económico ($0.25/1M) |
| RAG + Grounding | Anthropic | claude-3-5-sonnet | Mejor seguimiento de contexto |
| **Chat público grounded (FAQ Bot)** | Anthropic | claude-3-haiku | Reformulación KB, coste bajo, temp=0.3 |

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

## Servicios IA Reutilizables (v6.9.0)

> 📅 **Actualizado:** 2026-01-26

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

## Documentos de Referencia IA (v7.9.0)

> 📅 **Actualizado:** 2026-01-28

| Documento | Ubicación | Propósito |
|-----------|-----------|-----------|
| **Auditoría Arquitectura IA** | `docs/tecnicos/20260128-Auditoria_Arquitectura_IA_SaaS_v1_Claude.md` | Análisis consistencia sistema IA |
| **Especificación IA Clase Mundial** | `docs/tecnicos/20260128-Especificacion_IA_Clase_Mundial_SaaS_v1_Claude.md` | Benchmark vs Notion/Jasper/Intercom |
| **Bloque H: AI Agents** | `docs/implementacion/20260128h-Bloque_H_AI_Agents_Multi_Vertical_Implementacion_Claude.md` | Plan reuso agentes AgroConecta |
| **Aprendizaje Reuso** | `docs/tecnicos/aprendizajes/2026-01-28_reuso_agentes_ia_agroconecta.md` | Lecciones multi-tenancy, Brand Voice |

### Gaps Clase Mundial (8)

| Gap | Estado | Prioridad |
|-----|--------|-----------|
| Model Routing Inteligente | ⚠️ Parcial | P0 |
| Agentic Workflows | ❌ No implementado | P0 |
| Brand Voice Entrenable | ⚠️ Solo Copilot | P0 |
| Observabilidad LLM-as-Judge | ⚠️ Logs básicos | P0 |
| Multi-Modal (voz) | ❌ Solo texto | P1 |
| Skill Marketplace | ❌ No existe | P1 |
| AI Training Hub | ❌ No existe | P1 |
| Edge AI | ❌ Cloud only | P2 |


# Auditoría Arquitectura IA del SaaS

**Fecha de creación:** 2026-01-28 17:00  
**Última actualización:** 2026-01-28 17:00  
**Autor:** IA Asistente (Claude)  
**Versión:** 1.0.0

---

## 📑 Tabla de Contenidos

1. [Objetivo](#1-objetivo)
2. [Componentes IA Existentes](#2-componentes-ia-existentes)
3. [Análisis de Consistencia](#3-análisis-de-consistencia)
4. [Matriz de Reuso AgroConecta](#4-matriz-de-reuso-agroconecta)
5. [Gaps Identificados](#5-gaps-identificados)
6. [Recomendaciones](#6-recomendaciones)
7. [Registro de Cambios](#7-registro-de-cambios)

---

## 1. Objetivo

Auditar la arquitectura IA del SaaS para:
- Verificar consistencia con directrices del proyecto
- Evaluar viabilidad de reuso de agentes de AgroConecta
- Documentar gaps y recomendaciones

---

## 2. Componentes IA Existentes

### 2.1 Módulo `jaraba_copilot_v2` (18 servicios)

| Servicio | Líneas | Propósito |
|----------|--------|-----------|
| `CopilotOrchestratorService` | 1,019 | Hub central multiproveedor |
| `ModeDetectorService` | 412 | Detección intención usuario |
| `ContentGroundingService` | 296 | Grounding en contenido Drupal |
| `ClaudeApiService` | 394 | Cliente API (legacy) |
| `NormativeRAGService` | 312 | RAG normativo |
| `CopilotQueryLoggerService` | 259 | Analytics queries |
| `FeatureUnlockService` | 426 | Desbloqueo progresivo |
| `EntrepreneurContextService` | 438 | Contexto emprendedor |
| `BusinessPatternDetectorService` | 272 | Patrones negocio |
| `ExperimentLibraryService` | 344 | Biblioteca experimentos |
| `LearningCardService` | 241 | Tarjetas aprendizaje |
| `PivotDetectorService` | 313 | Detector pivotes |
| `TestCardGeneratorService` | 255 | Generador tests |
| `ValuePropositionCanvasService` | 288 | Canvas propuesta valor |
| `CopilotCacheService` | 160 | Cache respuestas |
| `FaqGeneratorService` | 212 | Generador FAQs |
| `CustomerDiscoveryGamificationService` | 266 | Gamificación |
| `NormativeKnowledgeService` | 191 | Conocimiento normativo |

### 2.2 Módulo `jaraba_rag` (3 servicios)

| Servicio | Propósito |
|----------|-----------|
| `JarabaRagService` | RAG principal con Qdrant |
| `KbIndexerService` | Indexación documentos |
| `GroundingValidator` | Validación grounding |

### 2.3 ConfigEntity `AIAgent`

```php
// ecosistema_jaraba_core/src/Entity/AIAgent.php
// Niveles de autonomía:
// 0 = Suggest: Solo sugiere
// 1 = Confirm: Espera confirmación
// 2 = Auto: Ejecuta automáticamente
// 3 = Silent: Sin notificación
```

**Propiedades:**
- `id`, `label`, `description`
- `service_id` (enlace a servicio Drupal)
- `autonomy_level` (0-3)
- `requires_approval`
- `max_daily_auto_actions`
- `allowed_actions` (JSON)

### 2.4 Agente Registrado

```yaml
# ecosistema_jaraba_core.ai_agent.marketing_agent.yml
id: marketing_agent
label: 'Marketing Agent'
description: 'Generación de contenido de marketing: posts, emails, descripciones SEO.'
service_id: ecosistema_jaraba_core.marketing_agent
```

---

## 3. Análisis de Consistencia

### 3.1 Cumplimiento Directrices

| Directriz | Estado | Evidencia |
|-----------|--------|-----------|
| Uso `@ai.provider` | ✅ | 14 archivos usan `AiProviderPluginManager` |
| No HTTP directo | ✅ | No encontrados `Guzzle` a APIs IA |
| Claves en Key module | ✅ | `/admin/config/system/keys` |
| Failover multiproveedor | ✅ | `getProvidersForMode()` |
| Logging | ✅ | `CopilotQueryLoggerService` |
| Multi-tenancy | ⚠️ | Parcial en Copilot, falta en agentes |

### 3.2 Patrones Establecidos

```php
// Patrón correcto (CopilotOrchestratorService)
use Drupal\ai\AiProviderPluginManager;

public function __construct(
    AiProviderPluginManager $aiProvider,
    // ...
) {}

protected function callProvider(string $providerId, string $model, ...): string {
    $provider = $this->aiProvider->createInstance($providerId);
    $response = $provider->chat($chatInput, $model, $configuration);
    return $response->getNormalized()->getText();
}
```

---

## 4. Matriz de Reuso AgroConecta

### 4.1 Agentes Disponibles

| Agente | Acciones | Líneas | Reusable |
|--------|----------|--------|----------|
| `MarketingAgent` | social_post, email_promo, ad_copy | 316 | ✅ |
| `StorytellingAgent` | brand_story, product_description | ~280 | ✅ |
| `CustomerExperienceAgent` | review_response, followup | ~300 | ✅ |
| `RecipeAgent` | recipe_content | ~250 | ⚠️ Solo Agro |
| `PricingAgent` | price_suggestion, competitor_analysis | ~270 | ✅ |
| `SustainabilityAgent` | eco_content | ~220 | ⚠️ Solo Agro |
| `SupportAgent` | faq_answer, ticket_response | ~260 | ✅ |
| `CopilotAgent` | chat, context_aware | ~350 | ⚠️ Ya existe v2 |

### 4.2 Componentes Base

| Componente | Líneas | Reusable | Modificaciones |
|------------|--------|----------|----------------|
| `BaseAgent` | 512 | ✅ | Multi-tenancy |
| `AgentInterface` | 46 | ✅ | Sin cambios |
| `AgentOrchestrator` | ~300 | ✅ | Routing vertical |

### 4.3 UI/UX Reutilizable

| Componente | Tipo | Estado |
|------------|------|--------|
| `agent-hub.js` | JavaScript | ✅ Migrar |
| `agent-hub.scss` | Estilos | ⚠️ Adaptar tokens |
| `agent-analytics-dashboard` | Template | ✅ Migrar |

---

## 5. Gaps Identificados

### 5.1 Gap Paradigmático

| Aspecto | Copiloto v2 | Agentes AgroConecta |
|---------|-------------|---------------------|
| Paradigma | Conversacional | Orientado a acciones |
| Trigger | Mensaje libre | Acción explícita |
| Output | Texto + sugerencias | JSON estructurado |
| UI | Chat bubble | Hub de acciones |

**Conclusión:** Son complementarios, no duplicados.

### 5.2 Gaps Técnicos

| Gap | Impacto | Solución |
|-----|---------|----------|
| Multi-tenancy en agentes | ALTO | Añadir `TenantContextService` |
| Brand Voice por tenant | MEDIO | Generalizar `getBrandVoicePrompt()` |
| i18n en prompts | MEDIO | Migrar a configuración YML |
| Registro en ConfigEntity | BAJO | Crear YMLs de config |

---

## 6. Recomendaciones

### 6.1 Arquitectura Propuesta

```
jaraba_ai_agents/           # NUEVO MÓDULO
├── src/
│   ├── Agent/
│   │   ├── AgentInterface.php
│   │   ├── BaseAgent.php        # Con TenantContext
│   │   ├── MarketingAgent.php   # Generalizado
│   │   └── ...
│   ├── Service/
│   │   ├── AgentOrchestrator.php
│   │   └── TenantBrandVoiceService.php
│   └── Controller/
│       └── AgentApiController.php
└── config/install/
    └── *.yml
```

### 6.2 Inversión Estimada

| Tarea | Horas | Prioridad |
|-------|-------|-----------|
| Módulo base + BaseAgent | 16h | P0 |
| MarketingAgent generalizado | 12h | P0 |
| Registro ConfigEntity | 4h | P1 |
| UI/UX migración | 8h | P1 |
| Tests | 5h | P1 |
| **TOTAL** | **45h** | - |

### 6.3 ROI

- **Sin reuso:** ~145h desarrollo
- **Con reuso:** ~45h adaptación
- **Ahorro:** 100h (~€6,500)

---

## 7. Registro de Cambios

| Fecha | Versión | Descripción |
|-------|---------|-------------|
| 2026-01-28 | 1.0.0 | Auditoría inicial arquitectura IA SaaS |

---

**Jaraba Impact Platform | Auditoría IA | Enero 2026**

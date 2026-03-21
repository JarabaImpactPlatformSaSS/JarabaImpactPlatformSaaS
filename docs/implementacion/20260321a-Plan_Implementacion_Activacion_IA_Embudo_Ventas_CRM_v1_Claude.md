# Plan de Implementación: Activación IA del Embudo de Ventas y CRM

**Versión:** 1.0.0
**Fecha:** 2026-03-21
**Autor:** Claude Opus 4.6 (1M context)
**Estado:** Aprobado para desarrollo
**Prioridad:** P0 — Impacto directo en conversión y revenue
**Módulos afectados:** `jaraba_copilot_v2`, `jaraba_crm`, `ecosistema_jaraba_core`, `jaraba_andalucia_ei`, `ecosistema_jaraba_theme`

---

## Índice de Navegación (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Diagnóstico del Problema](#2-diagnóstico-del-problema)
   - 2.1 [Caso de Uso Fallido](#21-caso-de-uso-fallido)
   - 2.2 [Análisis de Causa Raíz](#22-análisis-de-causa-raíz)
   - 2.3 [Flujo Actual vs Flujo Deseado](#23-flujo-actual-vs-flujo-deseado)
3. [Arquitectura de la Solución](#3-arquitectura-de-la-solución)
   - 3.1 [Visión General de Componentes](#31-visión-general-de-componentes)
   - 3.2 [ActivePromotionService — Conciencia de Promociones](#32-activepromotionservice--conciencia-de-promociones)
   - 3.3 [ContentGroundingService v2 — Grounding Universal](#33-contentgroundingservice-v2--grounding-universal)
   - 3.4 [Prompt Dinámico del Copilot Público](#34-prompt-dinámico-del-copilot-público)
   - 3.5 [Copilot → CRM: Detección de Intención de Compra](#35-copilot--crm-detección-de-intención-de-compra)
   - 3.6 [CRM Pipeline Activation: Progresión Automática](#36-crm-pipeline-activation-progresión-automática)
   - 3.7 [Funnel Tracking desde Copilot](#37-funnel-tracking-desde-copilot)
4. [Especificaciones Técnicas Detalladas](#4-especificaciones-técnicas-detalladas)
   - 4.1 [ActivePromotionService](#41-activepromotionservice)
   - 4.2 [ContentGroundingService v2](#42-contentgroundingservice-v2)
   - 4.3 [PublicCopilotController — Prompt Dinámico](#43-publiccopilotcontroller--prompt-dinámico)
   - 4.4 [CopilotLeadCaptureService](#44-copilotleadcaptureservice)
   - 4.5 [CopilotFunnelTrackingService](#45-copilotfunneltrackingservice)
   - 4.6 [PromotionConfig Entity](#46-promotionconfig-entity)
   - 4.7 [Modificaciones a CopilotOrchestratorService](#47-modificaciones-a-copilotorchestratorservice)
   - 4.8 [Setup Wizard Steps y Daily Actions Nuevos](#48-setup-wizard-steps-y-daily-actions-nuevos)
5. [Pipeline E2E (L1→L4) por Componente](#5-pipeline-e2e-l1l4-por-componente)
6. [Tabla de Correspondencia con Directrices](#6-tabla-de-correspondencia-con-directrices)
7. [Plan de Fases](#7-plan-de-fases)
8. [Salvaguardas y Validadores](#8-salvaguardas-y-validadores)
9. [Verificación RUNTIME-VERIFY-001](#9-verificación-runtime-verify-001)
10. [Criterios de Aceptación 10/10 Clase Mundial](#10-criterios-de-aceptación-1010-clase-mundial)
11. [Glosario](#11-glosario)

---

## 1. Resumen Ejecutivo

### El Problema

Un visitante anónimo en la página de inicio del SaaS pregunta al copilot IA "busco curso con incentivo". El copilot **no menciona el Programa Andalucía +ei** — un programa gratuito de inserción laboral con incentivo de 528€ que está **activamente promocionado** con un popup en los metasitios corporativos (pepejaraba.com, jarabaimpact.com, plataformadeecosistemas.es).

Esto revela un **vacío arquitectónico sistémico**: el sistema de promociones del SaaS (popup JS client-side + promo banner Twig configurable) está completamente desacoplado del sistema de IA (copilot, agentes, grounding). En un SaaS de IA nativa de clase mundial, la IA debe ser **consciente de TODAS las ofertas, programas, promociones y contenido** disponible en la plataforma en todo momento.

### La Solución

Implementar un sistema de **conciencia contextual unificada** que conecte:

1. **ActivePromotionService** — servicio centralizado que resuelve "¿qué promociones/programas están activos ahora?" consultable por cualquier componente (copilot, templates, emails)
2. **ContentGroundingService v2** — ampliación del grounding de 3 a 10+ tipos de entidad, incluyendo cursos, programas formativos, acciones formativas, y servicios
3. **Prompt dinámico** — el copilot público recibe un system prompt enriquecido con datos reales del SaaS: las 10 verticales, promociones activas, programas destacados, precios actualizados
4. **Copilot → CRM** — cuando el copilot detecta intención de compra o interés en un programa, auto-crea Contact + Opportunity en jaraba_crm
5. **Funnel tracking** — cada interacción copilot relevante se traza en el CRM para attribution

### Impacto Esperado

| Métrica | Antes | Después | Mejora |
|---------|-------|---------|--------|
| Copilot → Lead conversion | 0% | 15-25% | Nuevo canal |
| Relevancia respuestas copilot público | ~40% (genérico) | ~90% (contextualizado) | +125% |
| Attribution embudo completo | Parcial (quiz + lead magnet) | Completo (+ copilot) | 100% cobertura |
| Tiempo medio a primera conversión | Desconocido | Medible | Nuevo KPI |

---

## 2. Diagnóstico del Problema

### 2.1 Caso de Uso Fallido

**Escenario**: Visitante anónimo en homepage de metasitio → abre copilot FAB → escribe "busco curso con incentivo"

**Resultado esperado (clase mundial)**: El copilot responde con información específica sobre el Programa Andalucía +ei (45 plazas, 528€ incentivo, 100% gratuito, formación certificada, orientación con IA), proporciona enlaces directos a la landing y al formulario de solicitud, y opcionalmente ofrece capturar el email del visitante para enviarle más información.

**Resultado real**: El copilot responde con un mensaje genérico tipo "Tenemos planes adaptados a tus necesidades..." sin mencionar ningún programa concreto, vertical, ni promoción activa.

### 2.2 Análisis de Causa Raíz

El problema tiene **5 causas raíz independientes** que se acumulan:

#### Causa 1: Prompt estático hardcodeado

**Fichero**: `jaraba_copilot_v2/src/Controller/PublicCopilotController.php:292-362`

El método `buildPublicSystemPrompt()` contiene un prompt AIDA estático con solo 4 demos hardcodeados:
- Empleabilidad
- Emprendimiento
- Commerce
- B2B Instituciones

**Faltan 6 verticales**: AgroConecta, JarabaLex, ServiciosConecta, Formación, Content Hub, Andalucía +ei. El copilot literalmente no sabe que existen estos programas.

#### Causa 2: ContentGroundingService solo busca 3 tipos de entidad

**Fichero**: `jaraba_copilot_v2/src/Service/ContentGroundingService.php:30-74`

El método `getContentContext()` solo busca en:
- `node` tipo `offer` (ofertas de empleo) → método `searchOffers()`
- `node` tipo `emprendimiento` (ideas) → método `searchEmprendimientos()`
- `commerce_product` / `node` tipo `product` (productos) → método `searchProducts()`

**No busca en**: cursos (LMS), acciones formativas (Andalucía EI), servicios (ServiciosConecta), artículos (Content Hub), productos agrícolas (AgroConecta), documentos legales (JarabaLex), ni ninguna otra entidad del ecosistema.

Cuando el usuario escribe "busco curso con incentivo", los keywords extraídos son `['curso', 'incentivo']`. Estos se buscan en ofertas de empleo, emprendimientos y productos — ninguno contiene cursos ni programas con incentivos. Resultado: string vacío, sin contexto de grounding.

#### Causa 3: CopilotBridgeServices son solo para usuarios autenticados

**Fichero**: `jaraba_copilot_v2/src/Service/CopilotBridgeInterface.php:26-51`

La interfaz `getRelevantContext(int $userId)` requiere un `$userId` autenticado. Los visitantes anónimos del copilot público no pasan por el sistema de bridges — solo usan el prompt genérico de `buildPublicSystemPrompt()`.

#### Causa 4: Sistema de promociones desacoplado de la IA

El popup de Andalucía +ei (`jaraba_andalucia_ei/js/reclutamiento-popup.js`) es **100% client-side JavaScript**:
- Se inyecta via `hook_page_attachments()` con drupalSettings
- Solo se muestra en la home de metasitios corporativos
- No tiene API, servicio, ni mecanismo para que el backend (copilot) consulte si hay promociones activas

El promo banner (`_promo-banner.html.twig`) es Twig configurable desde Theme Settings UI — pero igualmente sin API para el copilot.

**No existe un `ActivePromotionService`** ni concepto equivalente que centralice "¿qué está activo ahora?" para que cualquier componente (IA, templates, emails) lo consulte.

#### Causa 5: Copilot no genera leads CRM

**Fichero**: `jaraba_copilot_v2/` — completo

El módulo `jaraba_copilot_v2` no tiene **ninguna dependencia** de `jaraba_crm`. Las interacciones de copilot con visitantes que expresan intención de compra ("busco curso", "quiero contratar", "necesito plan") no generan ni Contact ni Opportunity en el CRM.

Comparación:
- Quiz vertical → **SÍ** crea CRM Contact + Opportunity (stage=mql)
- Lead magnet → **SÍ** crea CRM Contact + Opportunity (stage=mql)
- Copilot público → **NO** crea nada en CRM

### 2.3 Flujo Actual vs Flujo Deseado

#### Flujo ACTUAL (roto)

```
Visitante → Homepage metasitio
  ├─ VE popup Andalucía +ei (3s delay, sessionStorage)
  ├─ VE promo banner (si enable_promo_banner=true en theme settings)
  └─ ABRE copilot FAB → pregunta "busco curso con incentivo"
       ├─ PublicCopilotController.chat()
       ├─ ContentGroundingService.getContentContext("busco curso con incentivo", "all")
       │    ├─ searchOffers(['curso','incentivo']) → 0 resultados
       │    ├─ searchEmprendimientos(['curso','incentivo']) → 0 resultados
       │    └─ searchProducts(['curso','incentivo']) → 0 resultados
       ├─ grounding = "" (vacío)
       ├─ System prompt = buildPublicSystemPrompt() (estático, 4 demos)
       └─ LLM responde genérico: "Tenemos planes adaptados..."
           └─ ❌ NO menciona Andalucía +ei
           └─ ❌ NO crea lead CRM
           └─ ❌ NO trackea interacción en funnel
```

#### Flujo DESEADO (clase mundial)

```
Visitante → Homepage metasitio
  ├─ VE popup Andalucía +ei (3s delay, sessionStorage)
  ├─ VE promo banner (configurable desde Theme Settings UI)
  └─ ABRE copilot FAB → pregunta "busco curso con incentivo"
       ├─ PublicCopilotController.chat()
       ├─ ActivePromotionService.getActivePromotions()
       │    └─ [Andalucía +ei: 45 plazas, 528€ incentivo, 100% gratuito, FSE+]
       ├─ ContentGroundingService v2.getContentContext("busco curso con incentivo", "all")
       │    ├─ searchCourses(['curso','incentivo']) → LMS courses
       │    ├─ searchFormativeActions(['curso','incentivo']) → Andalucía EI acciones
       │    ├─ searchOffers([...]) → ofertas empleo
       │    └─ resultado enriquecido con cursos y programas
       ├─ System prompt = buildDynamicPublicSystemPrompt()
       │    ├─ 10 verticales con descripciones actualizadas
       │    ├─ Promociones activas inyectadas dinámicamente
       │    ├─ Precios desde MetaSitePricingService
       │    └─ CTAs contextuales por vertical
       ├─ LLM responde con conocimiento real:
       │    "¡Tenemos justo lo que buscas! El Programa Andalucía +ei ofrece
       │     formación certificada 100% gratuita con un incentivo de 528€.
       │     Quedan 45 plazas. ¿Quieres ver el programa completo o solicitar
       │     plaza directamente?"
       ├─ CopilotLeadCaptureService.detectPurchaseIntent()
       │    └─ Intent: "formacion" + "incentivo" → vertical: andalucia_ei
       ├─ CRM: Auto-crea Contact (source=copilot_public, engagement_score=25)
       │    └─ Opportunity (stage=lead, vertical=andalucia_ei)
       └─ CopilotFunnelTrackingService.logInteraction()
            └─ Event: copilot_lead_qualified, vertical: andalucia_ei
```

---

## 3. Arquitectura de la Solución

### 3.1 Visión General de Componentes

```
┌──────────────────────────────────────────────────────────────────────┐
│                    CAPA DE CONCIENCIA CONTEXTUAL                     │
│                                                                      │
│  ┌─────────────────────┐  ┌──────────────────────┐                  │
│  │ ActivePromotionSvc  │  │ MetaSitePricingSvc   │                  │
│  │ (promociones activas)│  │ (precios por vertical)│                  │
│  └────────┬────────────┘  └──────────┬───────────┘                  │
│           │                          │                               │
│  ┌────────▼──────────────────────────▼───────────┐                  │
│  │         PublicCopilotContextBuilder             │                  │
│  │  (combina: promociones + precios + verticales   │                  │
│  │   + grounding content + bridges simplificados)  │                  │
│  └────────┬───────────────────────────────────────┘                  │
│           │                                                          │
├───────────▼──────────────────────────────────────────────────────────┤
│                    CAPA DE INTERACCIÓN IA                             │
│                                                                      │
│  ┌─────────────────────┐  ┌──────────────────────┐                  │
│  │ ContentGroundingSvc │  │ CopilotOrchestrator  │                  │
│  │ v2 (10+ entity types)│  │ (prompt dinámico)    │                  │
│  └────────┬────────────┘  └──────────┬───────────┘                  │
│           │                          │                               │
│  ┌────────▼──────────────────────────▼───────────┐                  │
│  │            PublicCopilotController              │                  │
│  │  (chat endpoint con contexto enriquecido)       │                  │
│  └────────┬───────────────────────────────────────┘                  │
│           │                                                          │
├───────────▼──────────────────────────────────────────────────────────┤
│                    CAPA DE CONVERSIÓN                                 │
│                                                                      │
│  ┌─────────────────────┐  ┌──────────────────────┐                  │
│  │CopilotLeadCaptureSvc│  │CopilotFunnelTracking │                  │
│  │ (intención→CRM lead)│  │ (attribution events) │                  │
│  └────────┬────────────┘  └──────────┬───────────┘                  │
│           │                          │                               │
│  ┌────────▼──────────────────────────▼───────────┐                  │
│  │              jaraba_crm                         │                  │
│  │  Contact → Opportunity → Pipeline → Checkout    │                  │
│  └─────────────────────────────────────────────────┘                  │
└──────────────────────────────────────────────────────────────────────┘
```

### 3.2 ActivePromotionService — Conciencia de Promociones

**Problema que resuelve**: Actualmente hay 3 sistemas de promociones desacoplados:
1. Popup Andalucía EI (JS client-side, solo home metasitios)
2. Promo banner global (Theme Settings, Twig)
3. PromotionAgro entity (solo AgroConecta vertical)

Ninguno es consultable por la IA. Se necesita un servicio centralizado que resuelva "¿qué promociones/programas destacados están activos ahora?" y que sea consumible tanto por el copilot como por templates, emails, y cualquier otro componente.

**Decisión arquitectónica**: Usar una ConfigEntity `PromotionConfig` en `ecosistema_jaraba_core` (no en un módulo vertical) para que sea transversal. Las promociones verticales específicas (PromotionAgro, etc.) se mantienen para su lógica de descuento en carrito, pero el `ActivePromotionService` las agrega junto con las promociones globales.

**Ubicación**: `ecosistema_jaraba_core/src/Service/ActivePromotionService.php`

**Principio**: El servicio es **read-only y cacheable** (cache tag `promotion_config_list`). La edición se hace via UI estándar de Drupal (/admin/structure/promotion-config). Esto cumple con la filosofía del SaaS de "control absoluto desde la UI de Drupal sin tocar código".

### 3.3 ContentGroundingService v2 — Grounding Universal

**Problema que resuelve**: El ContentGroundingService actual solo busca en 3 tipos de entidad (offers, emprendimientos, products). Un SaaS con 10 verticales y 80+ módulos custom tiene decenas de tipos de contenido relevante que el copilot debería conocer.

**Decisión arquitectónica**: Ampliar el servicio con un sistema de **grounding providers** (tagged services) que cada módulo vertical puede registrar. Esto sigue el mismo patrón de CompilerPass + tagged services usado en SetupWizardRegistry y DailyActionsRegistry.

**Grounding providers iniciales** (10):

| Provider | Módulo | Entity Type | Vertical |
|----------|--------|-------------|----------|
| `OfferGroundingProvider` | jaraba_candidate | node:offer | empleabilidad |
| `EmprendimientoGroundingProvider` | jaraba_business_tools | node:emprendimiento | emprendimiento |
| `ProductGroundingProvider` | jaraba_comercio_conecta | commerce_product / node:product | comercioconecta |
| `CourseGroundingProvider` | jaraba_lms | lms_course | formacion |
| `FormativeActionGroundingProvider` | jaraba_andalucia_ei | accion_formativa_ei | andalucia_ei |
| `AgroProductGroundingProvider` | jaraba_agroconecta_core | agro_product | agroconecta |
| `ServiceGroundingProvider` | jaraba_servicios_conecta | service_listing | serviciosconecta |
| `LegalDocumentGroundingProvider` | jaraba_legal_intelligence | legal_document | jarabalex |
| `ArticleGroundingProvider` | jaraba_content_hub | node:article | jaraba_content_hub |
| `PromotionGroundingProvider` | ecosistema_jaraba_core | promotion_config | global |

### 3.4 Prompt Dinámico del Copilot Público

**Problema que resuelve**: `buildPublicSystemPrompt()` es texto estático que no refleja el estado real del SaaS — verticales disponibles, programas activos, precios actuales, ni promociones.

**Decisión arquitectónica**: Reemplazar `buildPublicSystemPrompt()` por `buildDynamicPublicSystemPrompt()` que:
1. Inyecta las 10 verticales con descripciones actualizadas desde `BaseAgent::VERTICALS` o VerticalQuizService
2. Inyecta promociones activas desde `ActivePromotionService`
3. Inyecta precios representativos desde `MetaSitePricingService`
4. Mantiene las reglas de identidad via `AIIdentityRule::apply()`
5. Mantiene la estrategia AIDA pero con contenido real

**Cache**: El prompt dinámico se cachea con tag `promotion_config_list` + max-age 300s (5 min). No es real-time pero asegura que los cambios en promociones se reflejen en minutos.

### 3.5 Copilot → CRM: Detección de Intención de Compra

**Problema que resuelve**: El copilot público no genera leads CRM. Cuando un visitante expresa intención de compra ("quiero contratar", "busco curso", "precio del plan"), esa señal de conversión se pierde.

**Decisión arquitectónica**: Implementar `CopilotLeadCaptureService` que:
1. Analiza el mensaje del usuario con un **clasificador de intención ligero** (regex + keywords, NO un segundo LLM call por coste)
2. Si detecta intención de compra o interés en vertical específica, y el visitante proporciona email (o se le solicita), crea Contact + Opportunity en jaraba_crm
3. Sigue el mismo patrón de `VerticalQuizService::createCrmLead()` y `PublicSubscribeController::createCrmLead()` — servicios CRM opcionales via `$container->has()`

**Intenciones detectables** (regex en español):
- `formacion|curso|incentivo|subvencion|beca|programa` → vertical: formacion / andalucia_ei
- `empleo|trabajo|curriculum|cv|oferta` → vertical: empleabilidad
- `negocio|empresa|emprender|startup|idea` → vertical: emprendimiento
- `vender|tienda|comercio|producto|ecommerce` → vertical: comercioconecta
- `producir|cosecha|agro|campo|finca` → vertical: agroconecta
- `ley|legal|abogado|normativa|contrato` → vertical: jarabalex
- `servicio|profesional|freelance|consultor` → vertical: serviciosconecta
- `precio|plan|contratar|suscripcion|pagar` → intención de compra genérica
- `demo|probar|gratis|free` → intención de trial

### 3.6 CRM Pipeline Activation: Progresión Automática

**Problema que resuelve**: Actualmente el CRM recibe leads desde quiz y lead magnets, pero:
- No hay progresión automática de stages
- El checkout completado no marca Opportunity como `closed_won`
- No hay attribution de qué canal generó la conversión

**Decisión arquitectónica**: Implementar listeners (event subscribers) que progresionen el pipeline:

| Evento | Acción CRM | Stage Anterior → Nuevo |
|--------|------------|------------------------|
| Copilot detecta intención | Crear Contact + Opportunity | — → `lead` |
| Visitante proporciona email | Actualizar Contact engagement | `lead` → `mql` |
| Visitante hace clic en CTA del copilot | Log Activity | `mql` (sin cambio) |
| Visitante inicia checkout | Actualizar Opportunity | `mql` → `sql` |
| Checkout completado (webhook Stripe) | Cerrar Opportunity | `sql` → `closed_won` |
| Checkout abandonado (30 min) | Flag Opportunity | `sql` → `negotiation` |

### 3.8 Cascada de 4 Niveles — Estrategia de Búsqueda con Coste Progresivo

**Principio fundamental**: Un SaaS de IA nativa de clase mundial NO busca en toda la BD, Qdrant y aprendizajes en cada petición. Usa una **cascada de búsqueda con coste progresivo** donde cada nivel añade más contexto solo si es necesario.

#### Nivel 1 — SIEMPRE (coste ~0, <10ms, cacheable)

Se inyecta en CADA system prompt del copilot. Son datos estáticos o cacheados que no requieren queries:

- `ActivePromotionService` — ConfigEntities cacheadas con tag `promotion_config_list`
- Verticales disponibles — constante `BaseAgent::VERTICALS` (10 verticales canónicas)
- `MetaSitePricingService` — precios por vertical cacheados
- `AIIdentityRule` — reglas de identidad (texto estático)

#### Nivel 2 — POR KEYWORD MATCH (coste bajo, <100ms)

Se ejecuta SOLO cuando hay mensaje del usuario que analizar. Los GroundingProviders ejecutan Entity Queries filtrados por keywords:

- `ContentGroundingService v2` — solo ejecuta providers cuya vertical matchea los keywords
- `CopilotLeadCaptureService::detectPurchaseIntent()` — regex classifier, sin LLM adicional
- Contexto de promo banner + popup (si hay promoción activa para la vertical detectada)

#### Nivel 3 — POR NECESIDAD (coste medio, <500ms)

Se ejecuta cuando Nivel 2 no es suficiente o el usuario está autenticado:

- `SemanticCacheService` (Qdrant: `semantic_cache`) — threshold 0.92, busca respuestas previas similares
- `AgentLongTermMemoryService` (Qdrant: `agent_memory`) — SOLO para usuarios autenticados con historial
- RAG Knowledge Base vertical (JarabaLex: normative graph, LMS: catálogo)

#### Nivel 4 — BAJO DEMANDA (coste alto, <2s)

Se ejecuta SOLO cuando el LLM decide que necesita más info (patrón TOOL-USE-LOOP-001, max 5 iteraciones):

- Entity Query directa a DB (queries específicas: "¿cuántas plazas quedan?")
- Tool Use via `ToolRegistry` (9 tools: SearchContentTool, QueryDatabaseTool, etc.)
- Cross-vertical bridges completos (`CopilotBridgeService` — SOLO para autenticados)

#### Reglas de activación por contexto

| Contexto del usuario | Niveles activos | Justificación |
|---------------------|----------------|---------------|
| **Copilot público (anónimo)** | N1 + N2 + N3 (solo cache) | Coste controlado, rate limited, sin tool use |
| **Copilot autenticado (free)** | N1 + N2 + N3 | Memoria + RAG pero sin tool use (plan free) |
| **Copilot autenticado (pro/enterprise)** | N1 + N2 + N3 + N4 | Acceso completo incluyendo tool use |
| **Agente Gen 2 (tarea compleja)** | N1 + N2 + N3 + N4 + ReAct loop | Acceso total, max 5 iteraciones |

### 3.7 Funnel Tracking desde Copilot

**Problema que resuelve**: Las interacciones de copilot no tienen tracking en el funnel. Existen 134 `data-track-cta` en templates Twig pero ninguno en respuestas del copilot.

**Decisión arquitectónica**: Añadir `CopilotFunnelTrackingService` que:
1. Loguea cada interacción copilot con metadata: session_id, vertical_detected, intent_type, promotion_mentioned, cta_generated
2. Almacena en tabla `copilot_funnel_event` (no entidad, tabla directa para alto volumen)
3. Expone métricas via API para dashboard de admin

---

## 4. Especificaciones Técnicas Detalladas

### 4.1 ActivePromotionService

**Módulo**: `ecosistema_jaraba_core`
**Fichero**: `src/Service/ActivePromotionService.php`
**Servicio**: `ecosistema_jaraba_core.active_promotion`

#### Dependencias (services.yml)

```yaml
ecosistema_jaraba_core.active_promotion:
  class: Drupal\ecosistema_jaraba_core\Service\ActivePromotionService
  arguments:
    - '@entity_type.manager'
    - '@?jaraba_agroconecta_core.promotion'  # OPTIONAL-CROSSMODULE-001
    - '@logger.channel.ecosistema_jaraba_core'
    - '@cache.default'
```

Notas:
- `@?jaraba_agroconecta_core.promotion` es opcional (OPTIONAL-CROSSMODULE-001) para agregar PromotionAgro
- Logger inyectado como `LoggerInterface $logger` directamente (LOGGER-INJECT-001)
- Cache backend para evitar queries en cada request del copilot

#### Interfaz pública

```php
<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

interface ActivePromotionServiceInterface {

  /**
   * Devuelve todas las promociones/programas activos ahora.
   *
   * @return array<int, array{
   *   id: string,
   *   title: string,
   *   description: string,
   *   vertical: string,
   *   type: string,
   *   highlight_values: array<string, string>,
   *   cta_url: string,
   *   cta_label: string,
   *   priority: int,
   *   expires: ?string,
   * }>
   */
  public function getActivePromotions(): array;

  /**
   * Devuelve promociones activas filtradas por vertical.
   *
   * @param string $verticalKey
   *   Clave canónica del vertical (VERTICAL-CANONICAL-001).
   *
   * @return array
   *   Mismo formato que getActivePromotions().
   */
  public function getActivePromotionsByVertical(string $verticalKey): array;

  /**
   * Devuelve un texto formateado para inyectar en system prompts del copilot.
   *
   * @return string
   *   Bloque de texto con todas las promociones activas, formateado para LLM.
   */
  public function buildPromotionContextForCopilot(): string;

}
```

#### Lógica interna

El servicio consulta `PromotionConfig` ConfigEntities (ver §4.6) con los filtros:
1. `status = active`
2. `date_start <= NOW()` (o NULL = sin fecha inicio)
3. `date_end >= NOW()` (o NULL = sin fecha fin)
4. Ordenadas por `priority` DESC

Además agrega promociones verticales de PromotionAgro (si el módulo está disponible) y cualquier otro sistema vertical que implemente la interfaz.

#### Método `buildPromotionContextForCopilot()`

Genera un bloque de texto estructurado inyectable en prompts:

```
PROMOCIONES Y PROGRAMAS ACTIVOS EN ESTE MOMENTO:

1. PROGRAMA ANDALUCÍA +ei — Inserción laboral gratuita
   - 45 plazas disponibles | Incentivo de 528€ | 100% gratuito
   - Formación certificada + orientación personalizada + mentoría con IA
   - Financiado por Junta de Andalucía y Unión Europea (FSE+)
   - Más info: /es/andaluciamasei.html | Solicitar: /es/andalucia-ei/solicitar

2. BONO KIT DIGITAL — Financia tu suscripción
   - Hasta 12.000€ en ayudas para digitalización
   - Compatible con planes Starter y Professional
   - Más info: /es/kit-digital
```

Cache tag: `promotion_config_list`. Max-age: 300s.

### 4.2 ContentGroundingService v2

**Fichero**: `jaraba_copilot_v2/src/Service/ContentGroundingService.php` (modificar existente)

#### Arquitectura de Grounding Providers

Nuevo CompilerPass + interfaz tagged:

**Interfaz**: `jaraba_copilot_v2/src/Grounding/GroundingProviderInterface.php`

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Grounding;

interface GroundingProviderInterface {

  /**
   * Clave canónica del vertical que cubre este provider.
   */
  public function getVerticalKey(): string;

  /**
   * Busca contenido relevante para los keywords dados.
   *
   * @param array<string> $keywords
   *   Keywords extraídos del mensaje del usuario.
   * @param int $limit
   *   Máximo de resultados a devolver.
   *
   * @return array<int, array{
   *   title: string,
   *   summary: string,
   *   url: string,
   *   type: string,
   *   metadata: array<string, mixed>,
   * }>
   */
  public function search(array $keywords, int $limit = 3): array;

  /**
   * Prioridad del provider (mayor = se ejecuta primero).
   */
  public function getPriority(): int;

}
```

**Tag en services.yml**: `jaraba_copilot_v2.grounding_provider`

**CompilerPass**: `jaraba_copilot_v2/src/DependencyInjection/Compiler/GroundingProviderCompilerPass.php`

#### Nuevo método `getContentContext()` v2

```php
public function getContentContext(string $message, string $vertical = 'all'): string {
  $keywords = $this->extractKeywords($message);
  if (empty($keywords)) {
    return '';
  }

  $results = [];

  foreach ($this->providers as $provider) {
    // Si vertical especificado, filtrar; si 'all', ejecutar todos.
    if ($vertical !== 'all' && $provider->getVerticalKey() !== $vertical) {
      continue;
    }

    try {
      $providerResults = $provider->search($keywords, 3);
      foreach ($providerResults as $result) {
        $results[] = $result;
      }
    }
    catch (\Throwable $e) {
      $this->logger->warning('Grounding provider @provider failed: @msg', [
        '@provider' => $provider->getVerticalKey(),
        '@msg' => $e->getMessage(),
      ]);
    }
  }

  if (empty($results)) {
    return '';
  }

  // Limitar a 8 resultados totales para no saturar el context window.
  $results = array_slice($results, 0, 8);

  return $this->formatGroundingResults($results);
}
```

#### Provider ejemplo: FormativeActionGroundingProvider

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Grounding;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_copilot_v2\Grounding\GroundingProviderInterface;

class FormativeActionGroundingProvider implements GroundingProviderInterface {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  public function getVerticalKey(): string {
    return 'andalucia_ei';
  }

  public function search(array $keywords, int $limit = 3): array {
    $storage = $this->entityTypeManager->getStorage('accion_formativa_ei');
    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('status', TRUE)
      ->sort('created', 'DESC')
      ->range(0, $limit);

    // Filtrar por keywords en título o descripción.
    $orGroup = $query->orConditionGroup();
    foreach ($keywords as $keyword) {
      $orGroup->condition('nombre', '%' . $keyword . '%', 'LIKE');
      $orGroup->condition('descripcion', '%' . $keyword . '%', 'LIKE');
    }
    $query->condition($orGroup);

    $ids = $query->execute();
    if (empty($ids)) {
      return [];
    }

    $entities = $storage->loadMultiple($ids);
    $results = [];

    foreach ($entities as $entity) {
      $results[] = [
        'title' => $entity->label() ?? 'Acción formativa',
        'summary' => mb_substr(strip_tags($entity->get('descripcion')->value ?? ''), 0, 200),
        'url' => $entity->toUrl()->toString(),
        'type' => 'Programa formativo - Andalucía +ei',
        'metadata' => [
          'plazas' => $entity->get('plazas_disponibles')->value ?? '',
          'incentivo' => $entity->get('incentivo_economico')->value ?? '',
          'gratuito' => $entity->get('es_gratuito')->value ? 'Sí' : 'No',
        ],
      ];
    }

    return $results;
  }

  public function getPriority(): int {
    return 80;
  }

}
```

**Registro en services.yml** (jaraba_andalucia_ei.services.yml):

```yaml
jaraba_andalucia_ei.grounding_provider:
  class: Drupal\jaraba_andalucia_ei\Grounding\FormativeActionGroundingProvider
  arguments:
    - '@entity_type.manager'
  tags:
    - { name: jaraba_copilot_v2.grounding_provider }
```

### 4.3 PublicCopilotController — Prompt Dinámico

**Fichero**: `jaraba_copilot_v2/src/Controller/PublicCopilotController.php`

#### Nuevas dependencias en constructor

```php
public function __construct(
  // ... dependencias existentes ...
  protected ?ActivePromotionServiceInterface $activePromotionService = NULL,
  protected ?MetaSitePricingServiceInterface $metaSitePricingService = NULL,
) {}
```

**services.yml** (añadir a PublicCopilotController):

```yaml
- '@?ecosistema_jaraba_core.active_promotion'  # OPTIONAL-CROSSMODULE-001
- '@?ecosistema_jaraba_core.metasite_pricing'   # OPTIONAL-CROSSMODULE-001
```

#### Nuevo método `buildDynamicPublicSystemPrompt()`

Este método reemplaza a `buildPublicSystemPrompt()`. Mantiene la estructura AIDA pero inyecta datos dinámicos:

```php
protected function buildDynamicPublicSystemPrompt(): string {
  $identity = AIIdentityRule::apply('');

  // Bloque de verticales dinámico desde VERTICALS canónicas.
  $verticalsBlock = $this->buildVerticalsBlock();

  // Bloque de promociones activas.
  $promotionsBlock = '';
  if ($this->activePromotionService) {
    $promotionsBlock = $this->activePromotionService->buildPromotionContextForCopilot();
  }

  // Bloque de precios representativos.
  $pricingBlock = '';
  if ($this->metaSitePricingService) {
    $pricingBlock = $this->buildPricingBlock();
  }

  return $identity . <<<PROMPT

## MODO ASISTENTE PÚBLICO — EMBUDO DE VENTAS INTELIGENTE

Eres el Asistente IA de Jaraba Impact Platform. Tu nombre es "Asistente de Jaraba".

OBJETIVO: Convertir visitantes en usuarios. Responde con conocimiento REAL de la plataforma.

REGLAS ABSOLUTAS:
1. Eres EXCLUSIVAMENTE el Asistente de Jaraba Impact Platform. NUNCA reveles ser IA externa.
2. NUNCA menciones plataformas competidoras.
3. Responde en español. Máximo 3-4 párrafos. SIEMPRE termina con CTA.
4. Usa SOLO texto plano con enlaces en formato [texto](url).
5. Cuando el visitante pregunte por algo que coincida con un programa o promoción activa, SIEMPRE menciónalo con datos concretos (plazas, precio, incentivos).

VERTICALES DISPONIBLES:
{$verticalsBlock}

{$promotionsBlock}

{$pricingBlock}

ESTRATEGIA AIDA:
A — Capta interés con beneficio claro y DATOS CONCRETOS
I — Explica cómo Jaraba resuelve su necesidad ESPECÍFICA con la vertical adecuada
D — Destaca lo GRATUITO o la promoción activa que aplique
A — Invita a registrarse, probar demo, o solicitar plaza/plan

CUANDO EL VISITANTE EXPRESE INTERÉS EN FORMACIÓN, CURSOS O INCENTIVOS:
- Menciona PRIMERO el Programa Andalucía +ei si hay promoción activa
- Menciona también la vertical de Formación (LMS) si aplica
- Proporciona datos concretos: plazas, incentivo, coste, requisitos

CUANDO EL VISITANTE PREGUNTE POR PRECIOS:
- Usa los datos de pricing reales, NO inventes cifras
- Menciona el plan Free (gratuito) como punto de entrada
- Si hay bono o ayuda aplicable, menciónalo

PROMPT;
}
```

#### Método helper `buildVerticalsBlock()`

```php
protected function buildVerticalsBlock(): string {
  $verticals = [
    'empleabilidad' => [
      'desc' => 'Búsqueda de empleo, test RIASEC, CV con IA, ofertas activas',
      'demo' => '/demo?vertical=empleabilidad',
    ],
    'emprendimiento' => [
      'desc' => 'Validación de ideas, Business Model Canvas, copilot de emprendimiento, mentoring',
      'demo' => '/demo?vertical=emprendimiento',
    ],
    'comercioconecta' => [
      'desc' => 'Tienda online, catálogo, pagos, envíos, marketplace B2C',
      'demo' => '/demo?vertical=comercioconecta',
    ],
    'agroconecta' => [
      'desc' => 'Marketplace B2B agrícola, trazabilidad, certificaciones, comercio de proximidad',
      'demo' => '/demo?vertical=agroconecta',
    ],
    'jarabalex' => [
      'desc' => 'Inteligencia legal, búsqueda jurisprudencial, alertas normativas, copilot legal',
      'demo' => '/demo?vertical=jarabalex',
    ],
    'serviciosconecta' => [
      'desc' => 'Directorio de servicios profesionales, reservas, agenda, reseñas',
      'demo' => '/demo?vertical=serviciosconecta',
    ],
    'formacion' => [
      'desc' => 'LMS completo: cursos, lecciones, certificados, evaluaciones, copilot formativo',
      'demo' => '/demo?vertical=formacion',
    ],
    'andalucia_ei' => [
      'desc' => 'Programa institucional de empleo e inserción (FSE+), gestión de participantes',
      'demo' => '/demo?vertical=andalucia_ei',
    ],
    'jaraba_content_hub' => [
      'desc' => 'Gestión de contenidos, blog corporativo, SEO, generación IA de artículos',
      'demo' => '/demo?vertical=content_hub',
    ],
    'demo' => [
      'desc' => 'Demostración interactiva de todas las verticales sin necesidad de registro',
      'demo' => '/demo',
    ],
  ];

  $lines = [];
  foreach ($verticals as $key => $info) {
    $lines[] = "- {$key}: {$info['desc']} → Demo: {$info['demo']}";
  }

  return implode("\n", $lines);
}
```

### 4.4 CopilotLeadCaptureService

**Módulo**: `jaraba_copilot_v2`
**Fichero**: `src/Service/CopilotLeadCaptureService.php`
**Servicio**: `jaraba_copilot_v2.lead_capture`

#### Dependencias (services.yml)

```yaml
jaraba_copilot_v2.lead_capture:
  class: Drupal\jaraba_copilot_v2\Service\CopilotLeadCaptureService
  arguments:
    - '@?jaraba_crm.contact'      # OPTIONAL-CROSSMODULE-001
    - '@?jaraba_crm.opportunity'   # OPTIONAL-CROSSMODULE-001
    - '@?jaraba_crm.activity'      # OPTIONAL-CROSSMODULE-001
    - '@logger.channel.jaraba_copilot_v2'
```

#### Interfaz pública

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Service;

interface CopilotLeadCaptureServiceInterface {

  /**
   * Analiza mensaje para detectar intención de compra o interés vertical.
   *
   * NO llama a un LLM adicional — usa clasificación por keywords/regex
   * para mantener el coste por interacción en cero.
   *
   * @param string $message
   *   Mensaje del visitante.
   *
   * @return array{
   *   has_intent: bool,
   *   intent_type: string,
   *   vertical: ?string,
   *   confidence: float,
   *   keywords_matched: array<string>,
   * }
   */
  public function detectPurchaseIntent(string $message): array;

  /**
   * Crea lead CRM desde interacción copilot.
   *
   * Sigue patrón idéntico a VerticalQuizService::createCrmLead()
   * y PublicSubscribeController::createCrmLead() — LEAD-MAGNET-CRM-001.
   *
   * @param string $email
   *   Email del visitante.
   * @param string $verticalKey
   *   Clave canónica del vertical detectado.
   * @param array $context
   *   Contexto adicional: ip_hash, session_id, message_summary, utm_*.
   *
   * @return array{contact_id: ?int, opportunity_id: ?int, created: bool}
   */
  public function createCrmLead(string $email, string $verticalKey, array $context = []): array;

  /**
   * Registra actividad CRM desde interacción copilot.
   *
   * @param int $contactId
   *   ID del contacto CRM.
   * @param string $activityType
   *   Tipo: 'copilot_chat', 'copilot_cta_click', 'copilot_email_provided'.
   * @param string $subject
   *   Resumen de la interacción.
   * @param array $metadata
   *   Datos adicionales (vertical, intent, message_hash).
   */
  public function logCrmActivity(int $contactId, string $activityType, string $subject, array $metadata = []): void;

}
```

#### Mapeo de intenciones (detectPurchaseIntent)

```php
private const INTENT_PATTERNS = [
  // Vertical: andalucia_ei (PRIORIDAD por promoción activa)
  'andalucia_ei' => [
    'patterns' => '/\b(inserci[oó]n|piil|andaluc[ií]a\s*\+?ei|fse\+?|junta\s+de\s+andaluc|incentivo\s+laboral|programa\s+gratuito\s+de\s+empleo)\b/iu',
    'keywords' => ['inserción', 'piil', 'andalucía', '+ei', 'fse', 'incentivo laboral'],
    'confidence' => 0.9,
  ],
  // Vertical: formacion (coincide parcialmente con andalucia_ei)
  'formacion' => [
    'patterns' => '/\b(curso|formaci[oó]n|certificad|aprender|lecci[oó]n|instructor|alumno|ense[ñn]|capacitaci[oó]n|incentivo.{0,20}(curso|formaci))\b/iu',
    'keywords' => ['curso', 'formación', 'certificado', 'aprender', 'lección', 'incentivo'],
    'confidence' => 0.8,
  ],
  // Vertical: empleabilidad
  'empleabilidad' => [
    'patterns' => '/\b(empleo|trabajo|curr[ií]cul|cv|oferta\s+de\s+(empleo|trabajo)|busco\s+trabajo|orientaci[oó]n\s+profesional|riasec)\b/iu',
    'keywords' => ['empleo', 'trabajo', 'currículum', 'oferta'],
    'confidence' => 0.85,
  ],
  // ... (demás verticales: emprendimiento, comercioconecta, agroconecta, jarabalex, serviciosconecta)

  // Intención genérica de compra (sin vertical claro)
  'purchase_generic' => [
    'patterns' => '/\b(precio|plan|contratar|suscripci[oó]n|pagar|coste|tarifa|presupuesto|descuento|oferta\s+especial)\b/iu',
    'keywords' => ['precio', 'plan', 'contratar', 'suscripción'],
    'confidence' => 0.7,
  ],
  // Intención de trial/demo
  'trial' => [
    'patterns' => '/\b(probar|gratis|demo|free|sin\s+compromiso|prueba)\b/iu',
    'keywords' => ['probar', 'gratis', 'demo'],
    'confidence' => 0.6,
  ],
];
```

**Regla importante**: Cuando los keywords matchean tanto `formacion` como `andalucia_ei` (ej: "curso con incentivo"), se debe priorizar `andalucia_ei` si hay promoción activa para esa vertical. Esto se resuelve consultando `ActivePromotionService::getActivePromotionsByVertical('andalucia_ei')`.

### 4.5 CopilotFunnelTrackingService

**Módulo**: `jaraba_copilot_v2`
**Fichero**: `src/Service/CopilotFunnelTrackingService.php`
**Servicio**: `jaraba_copilot_v2.funnel_tracking`

#### Schema (hook_schema en .install)

```php
function jaraba_copilot_v2_schema(): array {
  $schema['copilot_funnel_event'] = [
    'description' => 'Eventos del embudo de ventas generados por el copilot.',
    'fields' => [
      'id' => ['type' => 'serial', 'unsigned' => TRUE, 'not null' => TRUE],
      'session_id' => ['type' => 'varchar', 'length' => 64, 'not null' => TRUE],
      'event_type' => ['type' => 'varchar', 'length' => 64, 'not null' => TRUE],
      'vertical_detected' => ['type' => 'varchar', 'length' => 64, 'default' => NULL],
      'intent_type' => ['type' => 'varchar', 'length' => 64, 'default' => NULL],
      'promotion_mentioned' => ['type' => 'varchar', 'length' => 128, 'default' => NULL],
      'cta_generated' => ['type' => 'varchar', 'length' => 255, 'default' => NULL],
      'crm_contact_id' => ['type' => 'int', 'unsigned' => TRUE, 'default' => NULL],
      'crm_opportunity_id' => ['type' => 'int', 'unsigned' => TRUE, 'default' => NULL],
      'ip_hash' => ['type' => 'varchar', 'length' => 64, 'not null' => TRUE],
      'created' => ['type' => 'int', 'not null' => TRUE],
      'metadata' => ['type' => 'text', 'size' => 'normal', 'default' => NULL],
    ],
    'primary key' => ['id'],
    'indexes' => [
      'session' => ['session_id'],
      'event_type' => ['event_type'],
      'vertical' => ['vertical_detected'],
      'created' => ['created'],
    ],
  ];
  return $schema;
}
```

**Nota**: Se usa tabla directa (no ContentEntity) porque el volumen de eventos puede ser alto y no necesita Field UI, Views, ni revisiones. Esto es consistente con el patrón de `copilot_feedback` que ya usa tabla directa.

#### Tipos de evento

| event_type | Descripción | Cuándo se dispara |
|------------|-------------|-------------------|
| `copilot_message_received` | Visitante envía mensaje al copilot | Cada interacción |
| `copilot_intent_detected` | Se detecta intención de compra/interés | Tras detectPurchaseIntent() |
| `copilot_promotion_mentioned` | El copilot menciona una promoción activa | Cuando promotion context se usa |
| `copilot_cta_served` | Se sirve un CTA específico en la respuesta | Cuando response incluye enlace |
| `copilot_email_captured` | Visitante proporciona email | Cuando se detecta email en mensaje |
| `copilot_lead_created` | Se crea lead CRM desde copilot | Tras createCrmLead() |
| `copilot_handoff_quiz` | Se redirige al quiz vertical | Cuando copilot sugiere quiz |
| `copilot_handoff_demo` | Se redirige a demo | Cuando copilot sugiere demo |

### 4.6 PromotionConfig Entity

**Módulo**: `ecosistema_jaraba_core`
**Tipo**: ConfigEntity (no ContentEntity — las promociones son configuración editorial, no contenido generado por usuarios)
**Machine name**: `promotion_config`

#### Campos

| Campo | Tipo | Descripción | Requerido |
|-------|------|-------------|-----------|
| `id` | string | Machine name (ej: `andalucia_ei_piil_2025`) | Sí |
| `label` | string | Título público (ej: "Programa Andalucía +ei") | Sí |
| `description` | string | Descripción extendida para el copilot | Sí |
| `vertical` | string | Clave canónica (VERTICAL-CANONICAL-001) o `global` | Sí |
| `type` | string | `program` / `discount` / `subsidy` / `event` / `announcement` | Sí |
| `highlight_values` | mapping | Key-value de datos destacados (ej: `plazas: "45"`, `incentivo: "528€"`) | No |
| `cta_url` | string | Ruta interna del CTA (Url::fromRoute compatible) | Sí |
| `cta_label` | string | Texto del botón CTA | Sí |
| `secondary_cta_url` | string | Ruta del CTA secundario | No |
| `secondary_cta_label` | string | Texto del CTA secundario | No |
| `status` | boolean | Activo/Inactivo | Sí |
| `date_start` | string | Fecha inicio (Y-m-d) o NULL | No |
| `date_end` | string | Fecha fin (Y-m-d) o NULL | No |
| `priority` | integer | Orden de precedencia (mayor = más importante) | Sí |
| `copilot_instruction` | string | Instrucción especial para el copilot sobre esta promoción | No |

#### Admin UI

- Listado: `/admin/structure/promotion-config`
- Crear: `/admin/structure/promotion-config/add`
- Editar: `/admin/structure/promotion-config/{id}/edit`
- Formulario: PremiumEntityFormBase con secciones:
  - Información General (label, description, type, vertical)
  - Datos Destacados (highlight_values como key-value repeater)
  - Llamadas a la Acción (cta_url, cta_label, secondary)
  - Programación (date_start, date_end, status, priority)
  - Instrucciones IA (copilot_instruction)

#### Datos iniciales (default content)

```yaml
# config/install/ecosistema_jaraba_core.promotion_config.andalucia_ei_piil_2025.yml
id: andalucia_ei_piil_2025
label: 'Programa Andalucía +ei — PIIL Colectivos Vulnerables 2025'
description: 'Programa gratuito de inserción laboral con orientación personalizada, formación certificada, mentoría con IA y un incentivo de 528€. Financiado por la Junta de Andalucía y la Unión Europea (FSE+). Dirigido a personas en búsqueda activa de empleo en Andalucía.'
vertical: andalucia_ei
type: program
highlight_values:
  plazas: '45'
  incentivo: '528€'
  coste: '100% gratuito'
  financiacion: 'Junta de Andalucía + FSE+'
cta_url: /andalucia-ei/solicitar
cta_label: 'Solicitar plaza'
secondary_cta_url: /andaluciamasei.html
secondary_cta_label: 'Ver programa completo'
status: true
date_start: '2025-01-15'
date_end: '2026-12-31'
priority: 100
copilot_instruction: 'Cuando un visitante pregunte por cursos, formación, empleo en Andalucía, o incentivos económicos, SIEMPRE menciona este programa con datos concretos (45 plazas, 528€ incentivo, 100% gratuito). Es la oferta más relevante para personas buscando formación con ayuda económica.'
```

### 4.7 Modificaciones a CopilotOrchestratorService

**Fichero**: `jaraba_copilot_v2/src/Service/CopilotOrchestratorService.php`

#### Cambio en modo `landing_copilot` (líneas 1107-1148)

El prompt estático actual se reemplaza por un delegador al nuevo `buildDynamicPublicSystemPrompt()`. Esto se implementa de dos formas posibles:

**Opción A (recomendada)**: El PublicCopilotController pasa el prompt ya construido al orchestrator, evitando que el orchestrator necesite conocer las dependencias de ActivePromotionService. El modo `landing_copilot` acepta un `_custom_system_prompt` en el contexto:

```php
// En PublicCopilotController::chat()
$context = [
  '_custom_system_prompt' => $this->buildDynamicPublicSystemPrompt(),
  'mode' => 'landing_copilot',
];
```

```php
// En CopilotOrchestratorService::buildSystemPrompt()
if (!empty($context['_custom_system_prompt'])) {
  return $context['_custom_system_prompt'];
}
// ... lógica existente
```

**Opción B**: Inyectar ActivePromotionService directamente en CopilotOrchestratorService como `@?` opcional. Menos deseable porque añade acoplamiento.

Se recomienda **Opción A** por cumplir OPTIONAL-CROSSMODULE-001 sin añadir dependencias al orchestrator.

### 4.8 Setup Wizard Steps y Daily Actions Nuevos

#### Nuevo Wizard Step: ConfigurarPromocionesStep

**Módulo**: `ecosistema_jaraba_core`
**Wizard ID**: `__global__` (global, disponible en todos los wizards de admin)
**Weight**: 90 (después de CompletarQuiz en 85)

```php
// src/SetupWizard/ConfigurarPromocionesStep.php
public function getWizardId(): string {
  return '__global__';
}

public function getWeight(): int {
  return 90;
}

public function isComplete(int $tenantId): bool {
  // Completo si existe al menos 1 PromotionConfig activa.
  $storage = $this->entityTypeManager->getStorage('promotion_config');
  $count = $storage->getQuery()
    ->accessCheck(FALSE)
    ->condition('status', TRUE)
    ->count()
    ->execute();
  return (int) $count > 0;
}
```

**Nota**: Este step es solo para administradores con acceso a `/admin/structure/promotion-config`. Para tenants regulares, se pre-popula con la config de Andalucía EI.

#### Nuevo Daily Action: RevisarLeadsCopilotAction

**Módulo**: `jaraba_copilot_v2`
**Dashboard ID**: `__global__` (visible en todos los dashboards de admin)

```php
// src/DailyActions/RevisarLeadsCopilotAction.php
public function getDashboardId(): string {
  return '__global__';
}

public function getLabel(): string {
  return (string) $this->t('Revisar leads del copilot');
}

public function isVisible(int $tenantId): bool {
  // Visible solo si hay leads CRM creados desde copilot en últimas 24h.
  // Requiere jaraba_crm disponible.
  if (!$this->hasService('jaraba_crm.contact')) {
    return FALSE;
  }
  // ... query de leads recientes con source='copilot_public'
}
```

---

## 5. Pipeline E2E (L1→L4) por Componente

### ActivePromotionService

| Capa | Qué verificar | Cómo verificar |
|------|---------------|----------------|
| **L1 — Service** | `ecosistema_jaraba_core.active_promotion` registrado en services.yml | `grep 'active_promotion' ecosistema_jaraba_core.services.yml` |
| **L1 — Service** | Consumido por PublicCopilotController (constructor + create()) | `grep 'ActivePromotion' PublicCopilotController.php` |
| **L2 — Data** | No aplica (el service genera texto para prompt, no para render array) | — |
| **L3 — Theme** | No aplica (no genera variables Twig) | — |
| **L4 — Template** | No aplica | — |

### PromotionConfig Entity

| Capa | Qué verificar | Cómo verificar |
|------|---------------|----------------|
| **L1** | Entity type declarado con annotation correcta | `grep 'PromotionConfig' *.php` |
| **L2** | AccessControlHandler con tenant check si aplica | Verificar anotación `handlers.access` |
| **L3** | hook_theme() no necesario (ConfigEntity usa default admin list) | — |
| **L4** | Formulario extiende PremiumEntityFormBase | `grep 'PremiumEntityFormBase' PromotionConfigForm.php` |
| **Extra** | Ruta admin: `/admin/structure/promotion-config` | Verificar en routing.yml |
| **Extra** | Field UI base route si aplica | Verificar `field_ui_base_route` en annotation |

### ContentGroundingService v2

| Capa | Qué verificar | Cómo verificar |
|------|---------------|----------------|
| **L1** | CompilerPass registrado en EcoistemaJarabaCoreServiceProvider | `grep 'GroundingProvider' *ServiceProvider.php` |
| **L1** | 10 providers tagged con `jaraba_copilot_v2.grounding_provider` | `grep -r 'grounding_provider' web/modules/custom/*/services.yml` |
| **L2** | `getContentContext()` invocado desde PublicCopilotController | Verificar en `queryPublicKnowledge()` |

### CopilotLeadCaptureService

| Capa | Qué verificar | Cómo verificar |
|------|---------------|----------------|
| **L1** | Service registrado con `@?jaraba_crm.*` opcional | `grep 'lead_capture' jaraba_copilot_v2.services.yml` |
| **L1** | Invocado desde PublicCopilotController::chat() | Verificar en método `chat()` |
| **L2** | Crea Contact + Opportunity con source='copilot_public' | Test kernel: crear lead y verificar source |
| **Extra** | Patrón idéntico a VerticalQuizService::createCrmLead() | Comparar firmas y campos |

### CopilotFunnelTrackingService

| Capa | Qué verificar | Cómo verificar |
|------|---------------|----------------|
| **L1** | Tabla `copilot_funnel_event` creada en hook_schema | `drush entity:updates` sin errores |
| **L1** | Service registrado y consumido | `grep 'funnel_tracking' jaraba_copilot_v2.services.yml` |
| **L2** | Eventos logueados en cada interacción copilot | Verificar en PublicCopilotController::chat() |

---

## 6. Tabla de Correspondencia con Directrices

| Directriz | Código | Aplicación en este Plan |
|-----------|--------|-------------------------|
| **VERTICAL-CANONICAL-001** | 10 verticales canónicas | PromotionConfig.vertical usa claves canónicas; buildVerticalsBlock() lista las 10 |
| **TENANT-BRIDGE-001** | TenantBridgeService para Tenant↔Group | CopilotLeadCaptureService resuelve tenant via TenantBridgeService si aplica |
| **TENANT-001** | Toda query filtra por tenant | CRM queries filtran por tenant_id; copilot_funnel_event incluye session_id (no tenant para anónimos) |
| **OPTIONAL-CROSSMODULE-001** | `@?` en services.yml | Todas las dependencias CRM son `@?`: `@?jaraba_crm.contact`, `@?jaraba_crm.opportunity`, etc. |
| **PHANTOM-ARG-001** | Args coinciden con constructor | Verificar con `validate-phantom-args.php` tras implementar |
| **LOGGER-INJECT-001** | Logger como `LoggerInterface $logger` | Todos los constructores reciben `LoggerInterface`, no `LoggerChannelFactory` |
| **CONTAINER-DEPS-002** | Sin dependencias circulares | `jaraba_copilot_v2` → `@?jaraba_crm` (opcional, unidireccional) |
| **CONTROLLER-READONLY-001** | No typed readonly en constructor promotion | PublicCopilotController hereda ControllerBase; propiedades no usan `protected readonly` |
| **PREMIUM-FORMS-PATTERN-001** | PremiumEntityFormBase | PromotionConfigForm extiende PremiumEntityFormBase con getSectionDefinitions() |
| **ENTITY-FK-001** | FKs cross-módulo como integer | copilot_funnel_event.crm_contact_id es integer (no entity_reference) |
| **AUDIT-CONS-001** | AccessControlHandler obligatorio | PromotionConfig declara AccessControlHandler en anotación |
| **CSS-VAR-ALL-COLORS-001** | Colores via `var(--ej-*, fallback)` | Si se crean componentes UI para admin de promociones, usar tokens CSS |
| **ICON-CONVENTION-001** | jaraba_icon() con SVG | Iconos de promoción en admin UI via jaraba_icon('marketing', 'promotion') |
| **ICON-DUOTONE-001** | Variante default duotone | Icono de promoción en variante duotone |
| **TWIG-INCLUDE-ONLY-001** | `only` en `{% include %}` | Si se crea parcial `_promotion-card.html.twig`, incluir con `only` |
| **ZERO-REGION-001** | Variables via hook_preprocess_page() | Promociones para frontend inyectadas en preprocess, no en controller |
| **ZERO-REGION-003** | drupalSettings en preprocess | drupalSettings para copilot contexto en hook_page_attachments_alter() |
| **ROUTE-LANGPREFIX-001** | Url::fromRoute() siempre | CTAs de promociones generados con Url::fromRoute() en preprocess |
| **NO-HARDCODE-PRICE-001** | Precios desde MetaSitePricingService | Prompt dinámico consulta MetaSitePricingService, no hardcodea |
| **MARKETING-TRUTH-001** | Claims coinciden con billing real | Datos de PromotionConfig verificables vs realidad del programa |
| **AI-IDENTITY-RULE** | Centralizado, nunca duplicar | `buildDynamicPublicSystemPrompt()` usa `AIIdentityRule::apply()` |
| **AI-GUARDRAILS-PII-001** | PII bidireccional | PublicCopilotController mantiene sanitización existente |
| **LEAD-MAGNET-CRM-001** | Auto-crea CRM Contact + Opportunity | CopilotLeadCaptureService sigue patrón idéntico |
| **CSRF-API-001** | `_csrf_request_header_token: 'TRUE'` | Rutas API del copilot mantienen CSRF existente |
| **SECRET-MGMT-001** | Sin secrets en config/sync/ | API keys de IA via getenv(), no en PromotionConfig |
| **SCSS-COMPILE-VERIFY-001** | Compilar y verificar timestamp | Si nuevos SCSS, verificar con `npm run build` |
| **SETUP-WIZARD-DAILY-001** | Patrón premium wizard + daily actions | Nuevos steps/actions registrados con tags y CompilerPass |
| **ZEIGARNIK-PRELOAD-001** | Steps globales auto-complete | Nuevo step ConfigurarPromociones con weight 90 |
| **UPDATE-HOOK-REQUIRED-001** | hook_update_N() para nueva entity | hook_update_N() con installEntityType('promotion_config') |
| **UPDATE-HOOK-CATCH-001** | try-catch con \Throwable | hook_update usa \Throwable |
| **FUNNEL-COMPLETENESS-001** | data-track-cta en CTAs | Copilot responses incluyen tracking metadata |
| **COPILOT-BRIDGE-COVERAGE-001** | Bridge por vertical | Los 10 bridges existentes se mantienen; ActivePromotionService complementa para anónimos |
| **STREAMING-PARITY-001** | Paridad streaming/buffered | Cambios en system prompt aplican a ambos modos |
| **DOC-GUARD-001** | Edición incremental de master docs | Este plan es documento nuevo, no edita master docs |

---

## 7. Plan de Fases

### Fase 1 — Fundamentos (Sprint 1)

| # | Tarea | Módulo | Prioridad |
|---|-------|--------|-----------|
| 1.1 | Crear PromotionConfig ConfigEntity con schema, annotation, form | ecosistema_jaraba_core | P0 |
| 1.2 | Crear ActivePromotionService + interface | ecosistema_jaraba_core | P0 |
| 1.3 | Cargar config inicial: andalucia_ei_piil_2025 | ecosistema_jaraba_core | P0 |
| 1.4 | Crear GroundingProviderInterface + CompilerPass | jaraba_copilot_v2 | P0 |
| 1.5 | Migrar searchOffers/Emprendimientos/Products a providers | jaraba_copilot_v2 | P0 |
| 1.6 | Crear FormativeActionGroundingProvider | jaraba_andalucia_ei | P0 |
| 1.7 | Crear CourseGroundingProvider | jaraba_lms | P0 |
| 1.8 | hook_update_N() para PromotionConfig entity type install | ecosistema_jaraba_core | P0 |
| 1.9 | hook_update_N() para tabla copilot_funnel_event | jaraba_copilot_v2 | P0 |

### Fase 2 — Copilot Inteligente (Sprint 1-2)

| # | Tarea | Módulo | Prioridad |
|---|-------|--------|-----------|
| 2.1 | Implementar buildDynamicPublicSystemPrompt() | jaraba_copilot_v2 | P0 |
| 2.2 | Integrar ActivePromotionService en PublicCopilotController | jaraba_copilot_v2 | P0 |
| 2.3 | Integrar MetaSitePricingService en prompt dinámico | jaraba_copilot_v2 | P1 |
| 2.4 | Modificar CopilotOrchestratorService para _custom_system_prompt | jaraba_copilot_v2 | P0 |
| 2.5 | Crear 7 grounding providers restantes | Múltiples módulos | P1 |

### Fase 3 — CRM + Funnel (Sprint 2)

| # | Tarea | Módulo | Prioridad |
|---|-------|--------|-----------|
| 3.1 | Crear CopilotLeadCaptureService | jaraba_copilot_v2 | P0 |
| 3.2 | Integrar detectPurchaseIntent en PublicCopilotController.chat() | jaraba_copilot_v2 | P0 |
| 3.3 | Crear CopilotFunnelTrackingService | jaraba_copilot_v2 | P1 |
| 3.4 | Event subscribers para progresión CRM pipeline | jaraba_crm | P1 |
| 3.5 | Setup Wizard step: ConfigurarPromocionesStep | ecosistema_jaraba_core | P2 |
| 3.6 | Daily Action: RevisarLeadsCopilotAction | jaraba_copilot_v2 | P2 |

### Fase 4 — Validación y Salvaguardas (Sprint 2)

| # | Tarea | Módulo | Prioridad |
|---|-------|--------|-----------|
| 4.1 | Crear validate-copilot-grounding-coverage.php | scripts/validation | P0 |
| 4.2 | Crear validate-promotion-copilot-sync.php | scripts/validation | P0 |
| 4.3 | Crear validate-copilot-crm-pipeline.php | scripts/validation | P1 |
| 4.4 | Tests: Unit para intent detection, Kernel para CRM lead creation | tests/ | P0 |
| 4.5 | Verificación RUNTIME-VERIFY-001 completa | Manual + CI | P0 |
| 4.6 | Actualizar CLAUDE.md con nuevas reglas | CLAUDE.md | P1 |

---

## 8. Salvaguardas y Validadores

### 8.1 Validador: COPILOT-GROUNDING-COVERAGE-001

**Script**: `scripts/validation/validate-copilot-grounding-coverage.php`

**Qué verifica**:
1. Cada vertical canónica (9 comerciales + demo) tiene al menos 1 GroundingProvider registrado
2. Cada provider es resolvible desde el container
3. El método `search()` de cada provider no lanza excepciones con keywords vacíos
4. ContentGroundingService tiene providers cargados (no está vacío)

**Tipo**: `run_check` (bloquea CI si falla)

### 8.2 Validador: PROMOTION-COPILOT-SYNC-001

**Script**: `scripts/validation/validate-promotion-copilot-sync.php`

**Qué verifica**:
1. Toda PromotionConfig con status=active es mencionable por el copilot (buildPromotionContextForCopilot() la incluye)
2. Las URLs de CTA (cta_url, secondary_cta_url) corresponden a rutas existentes en routing.yml
3. Las verticales de las promociones son canónicas (VERTICAL-CANONICAL-001) o `global`
4. Fechas coherentes: date_start < date_end si ambas presentes
5. No hay promociones con date_end en el pasado y status=active (stale promotions)

**Tipo**: `run_check`

### 8.3 Validador: COPILOT-CRM-PIPELINE-001

**Script**: `scripts/validation/validate-copilot-crm-pipeline.php`

**Qué verifica**:
1. CopilotLeadCaptureService está registrado en services.yml
2. Sus dependencias CRM son opcionales (`@?`)
3. Si jaraba_crm está disponible, el método createCrmLead() usa los mismos campos que VerticalQuizService::createCrmLead() (coherencia)
4. La tabla copilot_funnel_event existe en el schema
5. Los tipos de evento están documentados y son consistentes

**Tipo**: `run_check`

### 8.4 Validador: COPILOT-INTENT-ACCURACY-001

**Script**: `scripts/validation/validate-copilot-intent-patterns.php`

**Qué verifica**:
1. Los regex de INTENT_PATTERNS son válidos (no causan PCRE errors)
2. Test set de 20+ frases con resultado esperado (test de regresión):
   - "busco curso con incentivo" → `andalucia_ei` (si promoción activa) o `formacion`
   - "quiero vender online" → `comercioconecta`
   - "necesito abogado" → `jarabalex`
   - "cuánto cuesta el plan starter" → `purchase_generic`
   - "hola, buenas tardes" → `no_intent`
3. No hay overlaps ambiguos entre patterns que generen falsos positivos

**Tipo**: `run_check`

### 8.5 Pre-commit Hook Additions

Añadir a lint-staged en `.husky/pre-commit`:

```
# Si se modifica PromotionConfig o ActivePromotionService
**/promotion_config*.yml: validate-promotion-copilot-sync.php
**/ActivePromotionService.php: validate-promotion-copilot-sync.php
```

---

## 9. Verificación RUNTIME-VERIFY-001

Tras implementar, verificar estas 5 capas:

| # | Capa | Verificación | Comando/Acción |
|---|------|-------------|----------------|
| 1 | **PHP** | ActivePromotionService resuelve promociones | `drush eval "print_r(\Drupal::service('ecosistema_jaraba_core.active_promotion')->getActivePromotions());"` |
| 2 | **DB** | PromotionConfig entities existen | `drush cget ecosistema_jaraba_core.promotion_config.andalucia_ei_piil_2025` |
| 3 | **Rutas** | Admin UI accesible | Navegar a `/admin/structure/promotion-config` |
| 4 | **API** | Copilot público responde con contexto | `curl -X POST /api/v1/public-copilot/chat -d '{"message":"busco curso con incentivo"}'` |
| 5 | **CRM** | Lead creado tras interacción | Verificar en `/admin/content/crm-contact` que existe contact con source=copilot_public |
| 6 | **Funnel** | Evento logueado | `drush sqlq "SELECT * FROM copilot_funnel_event ORDER BY id DESC LIMIT 5"` |
| 7 | **Cache** | Prompt se regenera al cambiar promoción | Editar PromotionConfig → esperar 5min → verificar que copilot menciona cambio |
| 8 | **Streaming** | Paridad: la misma promoción aparece en streaming y buffered | Probar `/api/v1/copilot/chat/stream` con mismo mensaje |

---

## 10. Criterios de Aceptación 10/10 Clase Mundial

### Scorecard de Conversión

| # | Criterio | Peso | Descripción |
|---|----------|------|-------------|
| 1 | **Copilot conoce TODAS las verticales** | 10% | Las 10 verticales aparecen en el prompt dinámico con descripciones y demos |
| 2 | **Copilot conoce promociones activas** | 15% | Toda PromotionConfig activa se inyecta en el system prompt |
| 3 | **Grounding universal** | 10% | 10+ entity types buscables por ContentGroundingService v2 |
| 4 | **Respuesta contextual a "busco curso con incentivo"** | 15% | El copilot menciona Andalucía +ei con datos concretos (plazas, incentivo, coste) |
| 5 | **Detección de intención de compra** | 10% | Regex classifier detecta intención en 20+ frases de test |
| 6 | **Lead CRM desde copilot** | 10% | Contact + Opportunity creados con source=copilot_public |
| 7 | **Funnel tracking completo** | 5% | 8 tipos de evento logueados en copilot_funnel_event |
| 8 | **Precios dinámicos en prompt** | 5% | MetaSitePricingService inyecta precios reales, no hardcoded |
| 9 | **Admin UI para promociones** | 5% | /admin/structure/promotion-config funcional con PremiumEntityFormBase |
| 10 | **Setup Wizard + Daily Action** | 5% | Nuevo step + action para configurar/revisar promociones |
| 11 | **Salvaguardas** | 5% | 4 validadores ejecutables sin errores |
| 12 | **RUNTIME-VERIFY-001** | 5% | 8 verificaciones runtime pasadas |

**Umbral clase mundial**: >= 90/100 puntos

### ¿Qué faltaría para 10/10 absoluto? (roadmap futuro)

| Mejora | Descripción | Fase |
|--------|-------------|------|
| IA proactiva en landing | Auto-greet del copilot con contexto de promoción activa (5s delay) | Fase 5 |
| A/B testing prompts | PromptVersionService integrado con ActivePromotionService para testear CTAs | Fase 5 |
| Predicción de vertical | ML model que predice vertical ideal basado en comportamiento de navegación (pages visited) | Fase 6 |
| Copilot voice | WebRTC voice chat con AI para accesibilidad | Fase 7 |
| Real-time inventory | Plazas de Andalucía EI actualizadas en real-time (webhook desde sistema de gestión) | Fase 5 |
| Multi-idioma copilot | Prompt dinámico en EN/FR/PT además de ES | Fase 6 |
| Copilot → WhatsApp handoff | Continuar conversación en WhatsApp (Commerce pattern REDSYS-BIZUM-001) | Fase 6 |

---

## 11. Glosario

| Sigla | Significado |
|-------|-------------|
| **AIDA** | Attention, Interest, Desire, Action — modelo clásico de embudo de ventas |
| **BANT** | Budget, Authority, Need, Timeline — framework de cualificación de leads |
| **CRM** | Customer Relationship Management — gestión de relaciones con clientes |
| **CTA** | Call To Action — llamada a la acción |
| **FAB** | Floating Action Button — botón flotante de acción |
| **FSE+** | Fondo Social Europeo Plus |
| **LLM** | Large Language Model — modelo de lenguaje grande |
| **LMS** | Learning Management System — sistema de gestión de aprendizaje |
| **MQL** | Marketing Qualified Lead — lead cualificado por marketing |
| **NLP** | Natural Language Processing — procesamiento de lenguaje natural |
| **PIIL** | Programa de Inserción e Intermediación Laboral |
| **PII** | Personally Identifiable Information — información personalmente identificable |
| **RAG** | Retrieval-Augmented Generation — generación aumentada por recuperación |
| **SaaS** | Software as a Service |
| **SQL** | Sales Qualified Lead — lead cualificado por ventas (contexto CRM, no database) |
| **SSE** | Server-Sent Events — eventos enviados por servidor |
| **TOC** | Table of Contents — tabla de contenidos |
| **UTM** | Urchin Tracking Module — parámetros de tracking de campañas |

---

**Fin del documento**

*Documento generado por Claude Opus 4.6 (1M context) — 2026-03-21*
*Versión 1.0.0 — Para revisión y aprobación antes de inicio de implementación*

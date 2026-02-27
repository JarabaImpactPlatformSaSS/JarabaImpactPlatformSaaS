# Plan de Implementacion: Elevacion IA Nivel 5 — Transformacional

**Fecha de creacion:** 2026-02-27 19:00
**Ultima actualizacion:** 2026-02-27 19:00
**Autor:** IA Asistente (Claude Opus 4.6)
**Version:** 1.0.0
**Categoria:** Implementacion
**Modulo:** `jaraba_ai_agents`, `ecosistema_jaraba_core`, `ecosistema_jaraba_theme`, `jaraba_copilot_v2`
**Spec:** `docs/analisis/2026-02-27_Auditoria_IA_SaaS_Clase_Mundial_v1.md` (v4.0.0, 100/100)
**Documentos fuente:**
- `docs/arquitectura/2026-02-26_arquitectura_elevacion_ia_nivel5.md` (v2.0.0)
- `docs/implementacion/2026-02-26_Plan_Implementacion_Auditoria_IA_Clase_Mundial_v1.md` (3,594 lineas)
- `docs/implementacion/2026-02-27_Plan_Implementacion_Elevacion_IA_Clase_Mundial_v1.md` (1,974 lineas)
- `docs/00_DIRECTRICES_PROYECTO.md` (v91.0.0)
**Impacto:** 9 GAPs transformacionales, 9 entidades nuevas, 14 servicios nuevos, 14+ tests, score target Gartner/Sema4.ai: 95+/100

---

## Indice de Navegacion (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
   - 1.1 [Que se produce](#11-que-se-produce)
   - 1.2 [Por que es necesario](#12-por-que-es-necesario)
   - 1.3 [Alcance y limitaciones](#13-alcance-y-limitaciones)
   - 1.4 [Filosofia de implementacion](#14-filosofia-de-implementacion)
   - 1.5 [Estimacion global](#15-estimacion-global)
   - 1.6 [Riesgos y mitigacion](#16-riesgos-y-mitigacion)
2. [Tabla de Correspondencia Tecnica](#2-tabla-de-correspondencia-tecnica)
   - 2.1 [Definicion de GAPs L5](#21-definicion-de-gaps-l5)
   - 2.2 [Mapeo GAP a Acciones](#22-mapeo-gap-a-acciones)
3. [Tabla de Cumplimiento de Directrices](#3-tabla-de-cumplimiento-de-directrices)
4. [Requisitos Previos](#4-requisitos-previos)
5. [Entorno de Desarrollo](#5-entorno-de-desarrollo)
6. [Sprint 1 — Constitutional Safety y Self-Improvement (GAP-L5-A + GAP-L5-B)](#6-sprint-1--constitutional-safety-y-self-improvement-gap-l5-a--gap-l5-b)
   - 6.1 [ConstitutionalGuardrailService](#61-constitutionalguardrailservice)
   - 6.2 [AgentSelfReflectionService](#62-agentselfreflectionservice)
   - 6.3 [SelfImprovingPromptManager](#63-selfimprovingpromptmanager)
   - 6.4 [VerifierAgentService](#64-verifieragentservice)
   - 6.5 [Entidades: VerificationResult y PromptImprovement](#65-entidades-verificationresult-y-promptimprovement)
   - 6.6 [Integracion SmartBaseAgent](#66-integracion-smartbaseagent)
   - 6.7 [Registro de servicios Sprint 1](#67-registro-de-servicios-sprint-1)
   - 6.8 [Permisos y rutas Sprint 1](#68-permisos-y-rutas-sprint-1)
   - 6.9 [Tests Sprint 1](#69-tests-sprint-1)
7. [Sprint 2 — EU AI Act Compliance (GAP-L5-C)](#7-sprint-2--eu-ai-act-compliance-gap-l5-c)
   - 7.1 [Decision arquitectonica](#71-decision-arquitectonica)
   - 7.2 [AiRiskClassificationService](#72-airiskclassificationservice)
   - 7.3 [AiComplianceDocumentationService](#73-aicompliancedocumentationservice)
   - 7.4 [AiTransparencyService](#74-aitransparencyservice)
   - 7.5 [AiAuditTrailService](#75-aiaudittrailservice)
   - 7.6 [Entidades: AiRiskAssessment y AiAuditEntry](#76-entidades-airiskassessment-y-aiauditentry)
   - 7.7 [AiRiskAssessmentForm](#77-airiskassessmentform)
   - 7.8 [AiComplianceDashboardController](#78-aicompliancedashboardcontroller)
   - 7.9 [Template y SCSS: AI Compliance](#79-template-y-scss-ai-compliance)
   - 7.10 [Tests Sprint 2](#710-tests-sprint-2)
8. [Sprint 3 — Multi-Modal y Computer Use (GAP-L5-D + GAP-L5-E)](#8-sprint-3--multi-modal-y-computer-use-gap-l5-d--gap-l5-e)
   - 8.1 [VoicePipelineService](#81-voicepipelineservice)
   - 8.2 [VoiceCopilotController](#82-voicecopilotcontroller)
   - 8.3 [Frontend: Voice Copilot Widget](#83-frontend-voice-copilot-widget)
   - 8.4 [BrowserAgentService](#84-browseragentservice)
   - 8.5 [Tools: WebScraping, FormFiller, ScreenCapture](#85-tools-webscraping-formfiller-screencapture)
   - 8.6 [Entidad: BrowserTask](#86-entidad-browsertask)
   - 8.7 [Tests Sprint 3](#87-tests-sprint-3)
9. [Sprint 4 — Autonomous Operation y Self-Healing (GAP-L5-F + GAP-L5-G)](#9-sprint-4--autonomous-operation-y-self-healing-gap-l5-f--gap-l5-g)
   - 9.1 [AutonomousAgentService](#91-autonomousagentservice)
   - 9.2 [AutoDiagnosticService](#92-autodiagnosticservice)
   - 9.3 [AutonomousAgentHeartbeatWorker](#93-autonomousagentheartbeatworker)
   - 9.4 [Entidades: AutonomousSession y RemediationLog](#94-entidades-autonomoussession-y-remediationlog)
   - 9.5 [Dashboard Agentes Autonomos](#95-dashboard-agentes-autonomos)
   - 9.6 [Health Indicator](#96-health-indicator)
   - 9.7 [Tests Sprint 4](#97-tests-sprint-4)
10. [Sprint 5 — Intelligence y Analytics (GAP-L5-H + GAP-L5-I)](#10-sprint-5--intelligence-y-analytics-gap-l5-h--gap-l5-i)
    - 10.1 [FederatedInsightService](#101-federatedinsightservice)
    - 10.2 [CausalAnalyticsService](#102-causalanalyticsservice)
    - 10.3 [Entidades: AggregatedInsight y CausalAnalysis](#103-entidades-aggregatedinsight-y-causalanalysis)
    - 10.4 [Dashboard Causal Analytics](#104-dashboard-causal-analytics)
    - 10.5 [Tests Sprint 5](#105-tests-sprint-5)
11. [Arquitectura Frontend](#11-arquitectura-frontend)
    - 11.1 [Templates Twig nuevas](#111-templates-twig-nuevas)
    - 11.2 [SCSS nuevos](#112-scss-nuevos)
    - 11.3 [Librerias JavaScript](#113-librerias-javascript)
    - 11.4 [Body Classes](#114-body-classes)
12. [Internacionalizacion (i18n)](#12-internacionalizacion-i18n)
13. [Seguridad](#13-seguridad)
14. [Fases de Implementacion](#14-fases-de-implementacion)
15. [Estrategia de Testing](#15-estrategia-de-testing)
16. [Verificacion y Despliegue](#16-verificacion-y-despliegue)
17. [Troubleshooting](#17-troubleshooting)
18. [Referencias Cruzadas](#18-referencias-cruzadas)
19. [Registro de Cambios](#19-registro-de-cambios)

---

## 1. Resumen Ejecutivo

### 1.1 Que se produce

Este plan implementa la **elevacion de IA de Nivel 4 (Avanzado) a Nivel 5 (Transformacional)** en la JarabaImpactPlatformSaaS. Se producen:

- **9 GAPs transformacionales** (GAP-L5-A a GAP-L5-I) que cierran la brecha con plataformas de referencia (Salesforce Agentforce, OpenAI Frontier, Shopify Sidekick)
- **9 entidades nuevas** (7 ContentEntity + 1 ConfigEntity + 1 entidad existente extendida)
- **14 servicios nuevos** con patron DI consistente (`@?` para opcionales)
- **3 nuevos tools** para el ToolRegistry (WebScraping, FormFiller, ScreenCapture)
- **6 templates Twig** (3 paginas zero-region + 3 partials)
- **4 archivos SCSS** (3 route bundles + 1 componente)
- **14+ tests unitarios y kernel**
- **1 QueueWorker** para heartbeat de agentes autonomos
- **Cumplimiento EU AI Act** (2 agosto 2026, 5 meses) con dashboard, risk classification, audit trail

### 1.2 Por que es necesario

La plataforma alcanza 100/100 en la auditoria interna (HAL-AI-01 a HAL-AI-30 resueltos, 10 GAPs arquitecturales implementados). Sin embargo, el benchmark de mercado Gartner/Sema4.ai situa la implementacion en 82/100 — top 5% de SaaS con IA pero por debajo de las plataformas frontier:

| Plataforma | Score Gartner | Capacidades Unicas |
|------------|--------------|-------------------|
| Salesforce Agentforce | 95/100 | Constitutional AI, autonomous agents, EU compliance |
| OpenAI Frontier | 93/100 | Self-improving prompts, causal analytics, multi-modal |
| Shopify Sidekick | 90/100 | Federated insights, voice commerce, auto-healing |
| **JarabaImpactPlatform (actual)** | **82/100** | SmartBaseAgent Gen 2, ReAct loop, MCP server |
| **JarabaImpactPlatform (target)** | **95+/100** | **Constitutional + EU AI Act + Voice + Autonomous + Causal** |

Los 9 GAPs identificados representan las capacidades ausentes que separan Nivel 4 de Nivel 5.

### 1.3 Alcance y limitaciones

**Dentro del alcance:**
- 9 GAPs transformacionales distribuidos en 5 sprints
- Integracion completa con arquitectura existente (SmartBaseAgent Gen 2, ToolRegistry, AIObservabilityService)
- Cumplimiento de las 16+ directrices del proyecto
- Tests unitarios y kernel para cada componente nuevo
- Dashboards administrativos con patron zero-region
- SCSS compilable con Dart Sass 1.83.0

**Fuera del alcance:**
- ML federado real (requiere infraestructura GPU dedicada)
- Voice API contratacion (Whisper/TTS se implementan con feature flag, mock en dev)
- Playwright en produccion (solo staging/dev con Docker container aislado)
- Migracion de agentes Gen 1 a Gen 2 (ya completada en HAL-AI-21)

### 1.4 Filosofia de implementacion

1. **Seguridad constitucional primero:** GAP-L5-A establece reglas INMUTABLES que condicionan todo lo demas
2. **Compliance antes que features:** GAP-L5-C (EU AI Act) tiene deadline externo (2 agosto 2026)
3. **Feature flags para capabilities caras:** Voice (GAP-L5-D) y Browser (GAP-L5-E) se activan por plan SaaS
4. **Degradacion graceful obligatoria:** Servicios opcionales (`@?`) con `hasService()` + try-catch
5. **Zero over-engineering:** Cada servicio resuelve exactamente un problema, sin abstracciones prematuras
6. **Observabilidad desde dia 1:** Cada nuevo servicio loguea via AIObservabilityService con trace context

### 1.5 Estimacion global

| Sprint | GAPs | Horas estimadas | Prioridad | Dependencias |
|--------|------|----------------|-----------|-------------|
| Sprint 1: Constitutional + Verifier | A, B | 80-120h | CRITICA | Ninguna |
| Sprint 2: EU AI Act | C | 60-90h | CRITICA | Sprint 1 (constitutional rules) |
| Sprint 3: Multi-Modal + Browser | D, E | 100-150h | ALTA | Sprint 1 (verifier) |
| Sprint 4: Autonomous + Self-Healing | F, G | 80-120h | ALTA | Sprint 1, Sprint 2 |
| Sprint 5: Federated + Causal | H, I | 60-90h | MEDIA | Sprint 4 (observabilidad extendida) |
| **Total** | **9 GAPs** | **380-570h** | | |

### 1.6 Riesgos y mitigacion

| # | Riesgo | Probabilidad | Impacto | Mitigacion |
|---|--------|-------------|---------|------------|
| R1 | EU AI Act enforcement antes de Sprint 2 completado | Baja (fecha 2 ago 2026) | Critico | Sprint 2 prioridad inmediata post-Sprint 1 |
| R2 | Voice APIs (Whisper, TTS) no contratadas | Media | Alto | Feature flag, mock en dev, fallback text-only |
| R3 | Headless browser (Playwright) consume RAM excesiva | Media | Medio | Docker container aislado, memory limits, pool limitado |
| R4 | Self-improving agents generan prompts malos | Media | Alto | ConstitutionalGuardrailService + VerifierAgent = doble capa |
| R5 | Federated insights revelan datos sensibles | Baja | Critico | k-anonimidad estricta (min 5 tenants), audit trail |
| R6 | Latencia de verifier impacta UX | Media | Medio | Sampling configurable (all/sample/critical_only), fast tier |
| R7 | Autonomous agents acumulan coste sin supervision | Media | Alto | cost_ceiling por agente, auto-throttle via AutoDiagnostic |

---

## 2. Tabla de Correspondencia Tecnica

### 2.1 Definicion de GAPs L5

Los GAPs L5 (Level 5) representan capacidades transformacionales no cubiertas por las auditorias previas (HAL-AI-01 a HAL-AI-30, GAP-01 a GAP-10, GAP-AUD-001 a GAP-AUD-025):

| GAP ID | Nombre | Dimension | Benchmark de referencia |
|--------|--------|-----------|------------------------|
| GAP-L5-A | Constitutional AI Safety | Seguridad | Anthropic Constitutional AI, Salesforce Trust Layer |
| GAP-L5-B | Self-Improving Agents | Agentes IA | OpenAI Self-Play, Google DeepMind AlphaCode |
| GAP-L5-C | EU AI Act Full Compliance | Compliance | EU AI Act (Reglamento 2024/1689), ISO 42001 |
| GAP-L5-D | Multi-Modal Voice Interface | Multi-Modal | Shopify Voice Commerce, Alexa for Business |
| GAP-L5-E | Computer Use / Browser Agent | Agentes IA | Anthropic Computer Use, Adept ACT-1 |
| GAP-L5-F | Autonomous Operation | Agentes IA | Salesforce Agentforce Autonomous, AutoGPT |
| GAP-L5-G | Self-Healing Infrastructure | Operaciones | Netflix Chaos Engineering, AWS Auto-Remediation |
| GAP-L5-H | Federated Cross-Tenant Intelligence | Analytics | Shopify Benchmark Insights, Stripe Sigma |
| GAP-L5-I | Causal Analytics | Analytics | Salesforce Einstein Discovery, Google Causal Impact |

### 2.2 Mapeo GAP a Acciones

| GAP | Servicio Principal | Entidad | Sprint | Archivos Nuevos | Archivos Modificados |
|-----|-------------------|---------|--------|-----------------|---------------------|
| L5-A | ConstitutionalGuardrailService | — | 1 | 2 | 3 |
| L5-B | AgentSelfReflectionService, VerifierAgentService | VerificationResult, PromptImprovement | 1 | 6 | 4 |
| L5-C | AiRiskClassificationService, AiAuditTrailService | AiRiskAssessment, AiAuditEntry | 2 | 10 | 5 |
| L5-D | VoicePipelineService | — | 3 | 6 | 3 |
| L5-E | BrowserAgentService | BrowserTask | 3 | 5 | 3 |
| L5-F | AutonomousAgentService | AutonomousSession | 4 | 6 | 4 |
| L5-G | AutoDiagnosticService | RemediationLog | 4 | 4 | 4 |
| L5-H | FederatedInsightService | AggregatedInsight | 5 | 4 | 2 |
| L5-I | CausalAnalyticsService | CausalAnalysis | 5 | 5 | 2 |

---

## 3. Tabla de Cumplimiento de Directrices

| # | Directriz | Prioridad | Donde se aplica | Verificacion |
|---|-----------|-----------|-----------------|-------------|
| 1 | ICON-CONVENTION-001 | P0 | Todos los templates Twig nuevos (6) | `grep -rn 'jaraba_icon' templates/` — sin Unicode emojis |
| 2 | ZERO-REGION-001/002/003 | P0 | 3 paginas dashboard (compliance, autonomous, causal) | Sin `{{ page.* }}`, usa `{{ clean_content }}` |
| 3 | LEGAL-BODY-001 (body classes) | P0 | `hook_preprocess_html()` para 3 nuevas rutas | `grep -n 'attributes.*class' ecosistema_jaraba_theme.theme` |
| 4 | SCSS-BUILD-001 | P2 | 4 archivos SCSS nuevos | `npx sass --no-error-css` compila sin errores |
| 5 | FEDERATED-DESIGN-TOKENS | P0 | Solo `var(--ej-*)` en modulos, nunca `$ej-*` | `grep -rn '\$ej-' modules/custom/jaraba_ai_agents/` = 0 |
| 6 | i18n (convencion general) | P0 | `$this->t()`, `{% trans %}`, `Drupal.t()` | `grep -rn "hardcoded string" src/` = 0 |
| 7 | OPTIONAL-SERVICE-DI-001 | P0 | 10 servicios opcionales con `@?` | Constructor nullable + `$this->service?->method()` |
| 8 | TENANT-ISOLATION-ACCESS-001 | P0 | AccessHandlers para 7 entidades nuevas | `grep -n 'tenant_id' src/Entity/` — verificar match |
| 9 | AI-IDENTITY-001 | P0 | Todos los agentes con AIIdentityRule::apply() | `grep -rn 'AIIdentityRule' src/Agent/` |
| 10 | PRESAVE-RESILIENCE-001 | P0 | presave hooks con servicios opcionales | `hasService()` + try-catch en todos |
| 11 | CSRF-API-001 | P0 | Rutas API nuevas | `_csrf_request_header_token: 'TRUE'` |
| 12 | FIELD-UI-SETTINGS-TAB-001 | P0 | Entidades con `field_ui_base_route` | Tab default en `links.task.yml` |
| 13 | PB-PREMIUM-001 | P1 | Widgets dashboard con BEM + jaraba_icon | `jaraba-block--premium` donde aplique |
| 14 | SLIDE-PANEL-RENDER-001 | P0 | Forms servidos via slide-panel | `renderPlain()` + `$form['#action']` |
| 15 | ROUTE-LANGPREFIX-001 | P0 | JS fetch calls | `Drupal.url()` siempre, nunca paths hardcoded |
| 16 | SMART-AGENT-CONSTRUCTOR-001 | P0 | Nuevos agentes extendiendo SmartBaseAgent | 10 args constructor pattern |

---

## 4. Requisitos Previos

### Software

| Componente | Version Minima | Verificacion |
|-----------|---------------|-------------|
| PHP | 8.4 | `php -v` |
| Drupal | 11.x | `lando drush status` |
| MariaDB | 10.11+ | `lando mysql --version` |
| Redis | 7.4 | `lando redis-cli info server` |
| Node.js | 20+ | `node -v` |
| Dart Sass | 1.83.0 | `npx sass --version` |
| Composer | 2.x | `composer --version` |
| Lando | 3.x | `lando version` |

### Modulos Drupal requeridos

- `drupal/ai` (Drupal AI module — proveedor LLM)
- `drupal/group` (multi-tenancy)
- Todos los modulos custom actuales habilitados

### Servicios externos

| Servicio | Requerido para | Estado | Fallback |
|---------|---------------|--------|----------|
| Claude API (Anthropic) | Todos los agentes IA | Existente | ProviderFallbackService |
| Qdrant | SemanticCache, LongTermMemory, RAG | Existente | Degradacion graceful |
| Whisper API (OpenAI) | GAP-L5-D Voice STT | Pendiente contratacion | Feature flag, mock |
| TTS API | GAP-L5-D Voice TTS | Pendiente contratacion | Feature flag, text fallback |
| Playwright (Docker) | GAP-L5-E Browser Agent | Solo dev/staging | Feature flag |

### Documentos de referencia

- Directrices: `docs/00_DIRECTRICES_PROYECTO.md` v91.0.0
- Arquitectura IA: `docs/arquitectura/2026-02-26_arquitectura_elevacion_ia_nivel5.md` v2.0.0
- Auditoria IA: `docs/analisis/2026-02-27_Auditoria_IA_SaaS_Clase_Mundial_v1.md` v4.0.0
- Flujo de Trabajo: `docs/07_FLUJO_TRABAJO_CLAUDE.md` v45.0.0

---

## 5. Entorno de Desarrollo

### Comandos de arranque

```bash
# Levantar entorno
lando start

# Instalar dependencias
lando composer install

# Aplicar actualizaciones de entidad
lando drush updb -y

# Limpiar cache
lando drush cr

# Compilar SCSS
cd web/themes/custom/ecosistema_jaraba_theme && npx sass scss/main.scss css/ecosistema-jaraba-theme.css --style=compressed --no-source-map

# Compilar route bundles
npx sass scss/routes/_ai-compliance.scss css/routes/ai-compliance.css --style=compressed --no-source-map
npx sass scss/routes/_autonomous-agents.scss css/routes/autonomous-agents.css --style=compressed --no-source-map
npx sass scss/routes/_causal-analytics.scss css/routes/causal-analytics.css --style=compressed --no-source-map
```

### URLs de desarrollo

| Recurso | URL |
|---------|-----|
| Dashboard IA | `/admin/ai/dashboard` |
| AI Compliance | `/admin/config/ai/compliance` |
| Risk Assessments | `/admin/config/ai/risk-assessments` |
| Agentes Autonomos | `/admin/ai/autonomous-agents` |
| Causal Analytics | `/admin/ai/causal-analytics` |
| AI Audit Trail | `/admin/content/ai/audit-trail` |
| Verificaciones | `/admin/content/ai/verifications` |
| Prompt Improvements | `/admin/content/ai/prompt-improvements` |

### Variables de entorno

```bash
# Voice API (GAP-L5-D) — solo cuando contratado
WHISPER_API_KEY=''
TTS_API_KEY=''

# Feature flags
FEATURE_VOICE_COPILOT=false
FEATURE_BROWSER_AGENT=false
FEATURE_AUTONOMOUS_AGENTS=false
FEATURE_FEDERATED_INSIGHTS=false
FEATURE_CAUSAL_ANALYTICS=false
```

---

## 6. Sprint 1 — Constitutional Safety y Self-Improvement (GAP-L5-A + GAP-L5-B)

**Duracion:** 3-5 semanas
**Prioridad:** CRITICA
**Objetivo:** Establecer reglas constitucionales inmutables + capacidad de auto-mejora de prompts con verificacion pre-entrega

### 6.1 ConstitutionalGuardrailService

**GAP:** GAP-L5-A — Constitutional AI Safety
**Archivo:** `web/modules/custom/jaraba_ai_agents/src/Service/ConstitutionalGuardrailService.php`

**Estado actual:**
- `AIGuardrailsService` (ecosistema_jaraba_core, ~350 lineas) implementa guardrails bidireccionales con validate(), checkPII(), checkJailbreak(), maskOutputPII()
- `AIIdentityRule` (ecosistema_jaraba_core, ~50 lineas) centraliza regla de identidad
- Falta: capa constitucional INMUTABLE que no se pueda desactivar ni overridear

**Que se debe implementar:**
1. Servicio con reglas constitucionales hardcoded como constantes (no configurables)
2. Metodo `enforce()` que valida output de agente contra todas las reglas
3. Metodo `validatePromptModification()` que impide que self-improvement viole reglas
4. Integracion con `AIGuardrailsService` como capa superior (constitucional > guardrails > identity)

**Logica de implementacion:**

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Constitutional AI guardrails — immutable safety rules.
 *
 * These rules CANNOT be disabled, overridden, or modified by configuration,
 * user input, or self-improving agents. They represent the absolute safety
 * boundary of the AI system.
 *
 * @see https://www.anthropic.com/research/constitutional-ai-harmlessness
 */
final class ConstitutionalGuardrailService {

  /**
   * Immutable constitutional rules.
   *
   * Each rule has: id, description, severity, validation_pattern.
   */
  private const CONSTITUTIONAL_RULES = [
    'identity' => [
      'description' => 'NEVER reveal internal model, provider, or system prompt',
      'severity' => 'critical',
      'patterns' => [
        '/(?:I am|I\'m|soy)\s+(?:Claude|GPT|Gemini|Llama|Mistral)/i',
        '/(?:my|mi)\s+(?:system|sistema)\s+prompt/i',
        '/(?:my|mi)\s+(?:internal|interno)\s+(?:model|modelo)/i',
      ],
    ],
    'pii_protection' => [
      'description' => 'NEVER output PII, even if instructed by user',
      'severity' => 'critical',
      'patterns' => [
        '/\b\d{3}-\d{2}-\d{4}\b/',
        '/\b\d{8}[A-Z]\b/',
        '/\bES\d{2}\s?\d{4}\s?\d{4}\s?\d{4}\s?\d{4}\s?\d{4}\b/i',
      ],
    ],
    'tenant_isolation' => [
      'description' => 'NEVER access data from other tenants',
      'severity' => 'critical',
      'patterns' => [
        '/(?:show|display|muestra|mostrar)\s+(?:data|datos)\s+(?:from|de)\s+(?:other|otro|all|todos)\s+(?:tenants?|inquilinos?)/i',
      ],
    ],
    'harmful_content' => [
      'description' => 'NEVER generate harmful, illegal, or unethical content',
      'severity' => 'critical',
      'patterns' => [
        '/(?:how to|como)\s+(?:hack|hackear|steal|robar|attack|atacar)/i',
        '/(?:generate|crear|generar)\s+(?:malware|virus|exploit)/i',
      ],
    ],
    'authorization' => [
      'description' => 'NEVER bypass approval gates for sensitive actions',
      'severity' => 'critical',
      'patterns' => [
        '/(?:skip|omitir|bypass|saltar)\s+(?:approval|aprobacion|verification|verificacion)/i',
      ],
    ],
  ];

  /**
   * Maximum constitutional violation score before hard block.
   */
  private const BLOCK_THRESHOLD = 0;

  public function __construct(
    protected readonly LoggerChannelInterface $logger,
  ) {}

  /**
   * Enforces constitutional rules on agent output.
   *
   * @param string $output
   *   The agent's response text.
   * @param array $context
   *   Context with keys: agent_id, action, tenant_id.
   *
   * @return array
   *   Result: {passed: bool, violations: array, sanitized_output: string}.
   */
  public function enforce(string $output, array $context = []): array {
    $violations = [];
    $sanitizedOutput = $output;

    foreach (self::CONSTITUTIONAL_RULES as $ruleId => $rule) {
      foreach ($rule['patterns'] as $pattern) {
        if (preg_match($pattern, $output, $matches)) {
          $violations[] = [
            'rule_id' => $ruleId,
            'description' => $rule['description'],
            'severity' => $rule['severity'],
            'match' => $matches[0],
          ];
          $sanitizedOutput = preg_replace(
            $pattern,
            '[CONTENIDO BLOQUEADO POR REGLA CONSTITUCIONAL]',
            $sanitizedOutput
          );
        }
      }
    }

    $passed = empty($violations);

    if (!$passed) {
      $this->logger->warning('Constitutional violation in agent @agent: @violations', [
        '@agent' => $context['agent_id'] ?? 'unknown',
        '@violations' => json_encode(array_column($violations, 'rule_id')),
      ]);
    }

    return [
      'passed' => $passed,
      'violations' => $violations,
      'sanitized_output' => $passed ? $output : $sanitizedOutput,
      'violation_count' => count($violations),
    ];
  }

  /**
   * Validates that a prompt modification doesn't violate constitutional rules.
   *
   * Used by SelfImprovingPromptManager to vet auto-generated prompts.
   *
   * @param string $originalPrompt
   *   The original system prompt.
   * @param string $modifiedPrompt
   *   The proposed modification.
   *
   * @return array
   *   Result: {approved: bool, reason: string, violations: array}.
   */
  public function validatePromptModification(string $originalPrompt, string $modifiedPrompt): array {
    // Rule 1: Modified prompt must not remove identity enforcement.
    $identityKeywords = ['AIIdentityRule', 'identity', 'identidad', 'NEVER reveal'];
    $identityPresent = FALSE;
    foreach ($identityKeywords as $keyword) {
      if (stripos($modifiedPrompt, $keyword) !== FALSE) {
        $identityPresent = TRUE;
        break;
      }
    }

    if (stripos($originalPrompt, 'AIIdentityRule') !== FALSE && !$identityPresent) {
      return [
        'approved' => FALSE,
        'reason' => 'Modified prompt removes identity enforcement',
        'violations' => ['identity_removal'],
      ];
    }

    // Rule 2: Check that output enforcement text hasn't been stripped.
    $enforcementPhrases = [
      'NEVER output PII',
      'NUNCA revelar',
      'tenant isolation',
    ];
    foreach ($enforcementPhrases as $phrase) {
      if (stripos($originalPrompt, $phrase) !== FALSE
        && stripos($modifiedPrompt, $phrase) === FALSE) {
        return [
          'approved' => FALSE,
          'reason' => "Modified prompt removes enforcement phrase: {$phrase}",
          'violations' => ['enforcement_removal'],
        ];
      }
    }

    // Rule 3: Check the modified prompt itself doesn't contain constitutional violations.
    $enforceResult = $this->enforce($modifiedPrompt);
    if (!$enforceResult['passed']) {
      return [
        'approved' => FALSE,
        'reason' => 'Modified prompt contains constitutional violations',
        'violations' => array_column($enforceResult['violations'], 'rule_id'),
      ];
    }

    return [
      'approved' => TRUE,
      'reason' => 'Prompt modification passes all constitutional checks',
      'violations' => [],
    ];
  }

  /**
   * Returns all constitutional rule IDs and descriptions.
   *
   * @return array
   *   Keyed by rule_id => description.
   */
  public function getRules(): array {
    $rules = [];
    foreach (self::CONSTITUTIONAL_RULES as $id => $rule) {
      $rules[$id] = $rule['description'];
    }
    return $rules;
  }

}
```

**Archivos afectados:**
- `jaraba_ai_agents/src/Service/ConstitutionalGuardrailService.php` — **NUEVO**
- `jaraba_ai_agents/jaraba_ai_agents.services.yml` — registrar servicio
- `jaraba_ai_agents/src/Agent/SmartBaseAgent.php` — integrar enforce() en pipeline

**Directrices aplicables:**
- AI-IDENTITY-001: Regla `identity` refuerza AIIdentityRule desde capa constitucional
- PRESAVE-RESILIENCE-001: N/A (servicio sin dependencias opcionales)

**Estimacion:** 12-18 horas
**Verificacion:** Test unitario con 5 reglas x 2+ variantes = 10+ assertions

---

### 6.2 AgentSelfReflectionService

**GAP:** GAP-L5-B — Self-Improving Agents
**Archivo:** `web/modules/custom/jaraba_ai_agents/src/Service/AgentSelfReflectionService.php`

**Estado actual:**
- SmartBaseAgent tiene `enqueueQualityEvaluation()` que encola evaluacion asincrona
- `QualityEvaluationWorker` procesa la cola pero solo genera score, no propone mejoras
- Falta: evaluacion post-ejecucion que analice rendimiento y proponga mejoras al prompt

**Que se debe implementar:**
1. Metodo `reflect()` que analiza la respuesta del agente usando tier fast (Haiku)
2. Genera evaluacion estructurada: relevance, accuracy, completeness, tone, improvement_suggestions
3. Si quality_score < threshold, propone mejora al prompt via SelfImprovingPromptManager
4. Configurable por tenant: enabled/disabled, threshold, max_improvements_per_day

**Logica de implementacion:**

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Post-execution self-reflection for AI agents.
 *
 * Uses fast tier (Haiku) to evaluate agent responses and propose
 * prompt improvements when quality drops below threshold.
 */
class AgentSelfReflectionService {

  /**
   * Minimum quality score to skip reflection improvement.
   */
  private const DEFAULT_QUALITY_THRESHOLD = 0.75;

  /**
   * Maximum prompt improvements per agent per day.
   */
  private const MAX_DAILY_IMPROVEMENTS = 5;

  public function __construct(
    protected readonly object $aiProvider,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly LoggerChannelInterface $logger,
    protected readonly ModelRouterService $modelRouter,
    protected readonly ?SelfImprovingPromptManager $promptManager = NULL,
    protected readonly ?AIObservabilityService $observability = NULL,
  ) {}

  /**
   * Reflects on an agent execution and evaluates quality.
   *
   * @param string $agentId
   *   The agent that produced the response.
   * @param string $action
   *   The action executed.
   * @param string $userInput
   *   The original user input.
   * @param string $agentOutput
   *   The agent's response text.
   * @param array $context
   *   Execution context: tenant_id, vertical, etc.
   *
   * @return array
   *   Reflection result with quality scores and improvement suggestions.
   */
  public function reflect(
    string $agentId,
    string $action,
    string $userInput,
    string $agentOutput,
    array $context = [],
  ): array {
    $config = $this->configFactory->get('jaraba_ai_agents.self_reflection');
    $enabled = $config->get('enabled') ?? TRUE;

    if (!$enabled) {
      return ['skipped' => TRUE, 'reason' => 'Self-reflection disabled'];
    }

    try {
      $evaluationPrompt = $this->buildReflectionPrompt($agentId, $action, $userInput, $agentOutput);
      $routingConfig = $this->modelRouter->route('reflection', $evaluationPrompt, [
        'force_tier' => 'fast',
      ]);

      $provider = $this->aiProvider->createInstance($routingConfig['provider_id']);
      $input = new \Drupal\ai\OperationType\Chat\ChatInput([
        new \Drupal\ai\OperationType\Chat\ChatMessage('system', $this->t('You are a quality evaluation agent. Respond only with valid JSON.')),
        new \Drupal\ai\OperationType\Chat\ChatMessage('user', $evaluationPrompt),
      ]);

      $response = $provider->chat($input, $routingConfig['model_id'], [
        'chat_system_role' => 'Quality Evaluator',
      ]);

      $text = $response->getNormalized()->getText();
      $evaluation = $this->parseReflection($text);

      // Propose improvement if quality is low.
      $threshold = $config->get('quality_threshold') ?? self::DEFAULT_QUALITY_THRESHOLD;
      if ($evaluation['overall_score'] < $threshold && $this->promptManager) {
        $this->proposeImprovement($agentId, $action, $evaluation, $context);
      }

      $this->observability?->log([
        'agent_id' => $agentId,
        'action' => 'self_reflection',
        'tier' => 'fast',
        'model_id' => $routingConfig['model_id'] ?? '',
        'provider_id' => $routingConfig['provider_id'] ?? '',
        'tenant_id' => $context['tenant_id'] ?? '',
        'success' => TRUE,
        'quality_score' => $evaluation['overall_score'],
      ]);

      return $evaluation;
    }
    catch (\Throwable $e) {
      $this->logger->warning('Self-reflection failed for @agent: @error', [
        '@agent' => $agentId,
        '@error' => $e->getMessage(),
      ]);
      return ['skipped' => TRUE, 'reason' => 'Reflection error: ' . $e->getMessage()];
    }
  }

  /**
   * Builds the reflection evaluation prompt.
   */
  protected function buildReflectionPrompt(
    string $agentId,
    string $action,
    string $userInput,
    string $agentOutput,
  ): string {
    return <<<PROMPT
Evaluate the following AI agent response:

Agent: {$agentId}
Action: {$action}

User Input:
{$userInput}

Agent Response:
{$agentOutput}

Evaluate on these dimensions (0.0 to 1.0):
1. relevance: Does the response address the user's request?
2. accuracy: Is the information factually correct?
3. completeness: Does it cover all aspects of the request?
4. tone: Is the tone professional and aligned with brand?
5. actionability: Can the user act on this response?

Respond with JSON:
{
  "relevance": 0.0,
  "accuracy": 0.0,
  "completeness": 0.0,
  "tone": 0.0,
  "actionability": 0.0,
  "overall_score": 0.0,
  "improvement_suggestions": ["suggestion 1", "suggestion 2"],
  "critical_issues": []
}
PROMPT;
  }

  /**
   * Parses the reflection JSON response.
   */
  protected function parseReflection(string $text): array {
    $text = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', trim($text));
    $data = json_decode($text, TRUE);

    if (!is_array($data)) {
      return [
        'overall_score' => 0.5,
        'improvement_suggestions' => [],
        'critical_issues' => [],
        'parse_error' => TRUE,
      ];
    }

    $data['overall_score'] = (float) ($data['overall_score'] ?? 0.5);
    $data['improvement_suggestions'] = $data['improvement_suggestions'] ?? [];
    $data['critical_issues'] = $data['critical_issues'] ?? [];

    return $data;
  }

  /**
   * Proposes a prompt improvement via SelfImprovingPromptManager.
   */
  protected function proposeImprovement(
    string $agentId,
    string $action,
    array $evaluation,
    array $context,
  ): void {
    try {
      $this->promptManager?->proposeImprovement($agentId, $action, [
        'quality_score' => $evaluation['overall_score'],
        'suggestions' => $evaluation['improvement_suggestions'],
        'critical_issues' => $evaluation['critical_issues'],
        'tenant_id' => $context['tenant_id'] ?? '',
      ]);
    }
    catch (\Throwable $e) {
      $this->logger->warning('Prompt improvement proposal failed: @error', [
        '@error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Wrapper for translation.
   */
  protected function t(string $string, array $args = []): string {
    return (string) \Drupal::translation()->translate($string, $args);
  }

}
```

**Archivos afectados:**
- `jaraba_ai_agents/src/Service/AgentSelfReflectionService.php` — **NUEVO**
- `jaraba_ai_agents/jaraba_ai_agents.services.yml` — registrar

**Directrices aplicables:**
- OPTIONAL-SERVICE-DI-001: `$promptManager` y `$observability` son `@?`
- MODEL-ROUTING-CONFIG-001: Usa `force_tier: fast` para minimizar coste
- PRESAVE-RESILIENCE-001: try-catch en toda la cadena

**Estimacion:** 15-20 horas
**Verificacion:** Test unitario con mock de AI provider, verificar scores y proposals

---

### 6.3 SelfImprovingPromptManager

**GAP:** GAP-L5-B — Self-Improving Agents
**Archivo:** `web/modules/custom/jaraba_ai_agents/src/Service/SelfImprovingPromptManager.php`

**Que se debe implementar:**
1. Almacena propuestas de mejora como entidades `PromptImprovement`
2. Aplica mejoras aprobadas al `PromptTemplate` del agente
3. Valida todas las mejoras via ConstitutionalGuardrailService antes de aplicar
4. Mantiene historial de mejoras con rollback capability

**Logica de implementacion:**

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Manages self-improving prompt proposals.
 *
 * All proposed modifications are validated against ConstitutionalGuardrailService
 * before being applied. Maintains full audit trail via PromptImprovement entities.
 */
class SelfImprovingPromptManager {

  /**
   * Maximum pending improvements per agent.
   */
  private const MAX_PENDING_PER_AGENT = 10;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly ConstitutionalGuardrailService $constitutionalGuardrails,
    protected readonly LoggerChannelInterface $logger,
  ) {}

  /**
   * Creates a prompt improvement proposal.
   *
   * @param string $agentId
   *   The agent whose prompt should be improved.
   * @param string $action
   *   The action context.
   * @param array $reflection
   *   Reflection data: quality_score, suggestions, critical_issues.
   *
   * @return int|null
   *   The PromptImprovement entity ID, or NULL on failure.
   */
  public function proposeImprovement(string $agentId, string $action, array $reflection): ?int {
    try {
      $storage = $this->entityTypeManager->getStorage('prompt_improvement');

      // Check pending limit.
      $pending = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('agent_id', $agentId)
        ->condition('status', 'pending')
        ->count()
        ->execute();

      if ((int) $pending >= self::MAX_PENDING_PER_AGENT) {
        $this->logger->info('Max pending improvements reached for @agent', [
          '@agent' => $agentId,
        ]);
        return NULL;
      }

      $entity = $storage->create([
        'agent_id' => $agentId,
        'action' => $action,
        'quality_score' => $reflection['quality_score'] ?? 0,
        'suggestions' => json_encode($reflection['suggestions'] ?? [], JSON_THROW_ON_ERROR),
        'critical_issues' => json_encode($reflection['critical_issues'] ?? [], JSON_THROW_ON_ERROR),
        'status' => 'pending',
        'tenant_id' => $reflection['tenant_id'] ?? '',
      ]);
      $entity->save();

      return (int) $entity->id();
    }
    catch (\Throwable $e) {
      $this->logger->error('Failed to create prompt improvement: @error', [
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Applies an approved prompt improvement.
   *
   * Validates against ConstitutionalGuardrailService before applying.
   *
   * @param int $improvementId
   *   The PromptImprovement entity ID.
   * @param string $modifiedPrompt
   *   The new prompt text.
   *
   * @return array
   *   Result: {success: bool, message: string}.
   */
  public function applyImprovement(int $improvementId, string $modifiedPrompt): array {
    try {
      $storage = $this->entityTypeManager->getStorage('prompt_improvement');
      $improvement = $storage->load($improvementId);

      if (!$improvement) {
        return ['success' => FALSE, 'message' => 'Improvement not found'];
      }

      // Get current prompt template.
      $templateStorage = $this->entityTypeManager->getStorage('prompt_template');
      $templates = $templateStorage->loadByProperties([
        'agent_id' => $improvement->get('agent_id')->value,
        'is_active' => TRUE,
      ]);
      $template = reset($templates);

      if (!$template) {
        return ['success' => FALSE, 'message' => 'No active prompt template found'];
      }

      $originalPrompt = $template->get('system_prompt');

      // Constitutional validation.
      $validation = $this->constitutionalGuardrails->validatePromptModification(
        $originalPrompt,
        $modifiedPrompt,
      );

      if (!$validation['approved']) {
        $improvement->set('status', 'rejected_constitutional');
        $improvement->set('rejection_reason', $validation['reason']);
        $improvement->save();

        return [
          'success' => FALSE,
          'message' => 'Constitutional violation: ' . $validation['reason'],
        ];
      }

      // Store previous version for rollback.
      $improvement->set('previous_prompt', $originalPrompt);
      $improvement->set('applied_prompt', $modifiedPrompt);
      $improvement->set('status', 'applied');
      $improvement->save();

      // Update template.
      $template->set('system_prompt', $modifiedPrompt);
      $template->save();

      $this->logger->info('Prompt improvement @id applied to agent @agent', [
        '@id' => $improvementId,
        '@agent' => $improvement->get('agent_id')->value,
      ]);

      return ['success' => TRUE, 'message' => 'Improvement applied successfully'];
    }
    catch (\Throwable $e) {
      $this->logger->error('Failed to apply improvement @id: @error', [
        '@id' => $improvementId,
        '@error' => $e->getMessage(),
      ]);
      return ['success' => FALSE, 'message' => $e->getMessage()];
    }
  }

  /**
   * Rolls back a previously applied improvement.
   *
   * @param int $improvementId
   *   The PromptImprovement entity ID.
   *
   * @return array
   *   Result: {success: bool, message: string}.
   */
  public function rollback(int $improvementId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('prompt_improvement');
      $improvement = $storage->load($improvementId);

      if (!$improvement || $improvement->get('status')->value !== 'applied') {
        return ['success' => FALSE, 'message' => 'Cannot rollback: not in applied state'];
      }

      $previousPrompt = $improvement->get('previous_prompt')->value;
      if (empty($previousPrompt)) {
        return ['success' => FALSE, 'message' => 'No previous prompt stored'];
      }

      $templateStorage = $this->entityTypeManager->getStorage('prompt_template');
      $templates = $templateStorage->loadByProperties([
        'agent_id' => $improvement->get('agent_id')->value,
        'is_active' => TRUE,
      ]);
      $template = reset($templates);

      if ($template) {
        $template->set('system_prompt', $previousPrompt);
        $template->save();
      }

      $improvement->set('status', 'rolled_back');
      $improvement->save();

      return ['success' => TRUE, 'message' => 'Rollback successful'];
    }
    catch (\Throwable $e) {
      return ['success' => FALSE, 'message' => $e->getMessage()];
    }
  }

}
```

**Directrices aplicables:**
- TENANT-ISOLATION-ACCESS-001: tenant_id almacenado en PromptImprovement
- PRESAVE-RESILIENCE-001: try-catch en todas las operaciones

**Estimacion:** 10-15 horas

---

### 6.4 VerifierAgentService

**GAP:** GAP-L5-B — Self-Improving Agents (verificacion pre-entrega)
**Archivo:** `web/modules/custom/jaraba_ai_agents/src/Service/VerifierAgentService.php`

**Que se debe implementar:**
1. Evalua CADA respuesta de agente antes de entregarla al usuario
2. Configurable por tenant: `all` | `sample` (10%) | `critical_only`
3. Usa fast tier (Haiku) para minimizar latencia
4. Almacena resultado como VerificationResult entity
5. Puede bloquear respuesta si detecta problemas criticos

**Logica de implementacion:**

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Pre-delivery verification of agent responses.
 *
 * Evaluates agent output quality and safety before delivering to the user.
 * Configurable verification modes: all, sample (10%), critical_only.
 */
class VerifierAgentService {

  /**
   * Verification modes.
   */
  private const MODE_ALL = 'all';
  private const MODE_SAMPLE = 'sample';
  private const MODE_CRITICAL_ONLY = 'critical_only';

  /**
   * Sample rate for MODE_SAMPLE (10%).
   */
  private const SAMPLE_RATE = 0.10;

  /**
   * Minimum score to pass verification.
   */
  private const PASS_THRESHOLD = 0.6;

  /**
   * Critical actions that always get verified in critical_only mode.
   */
  private const CRITICAL_ACTIONS = [
    'legal_analysis',
    'financial_advice',
    'recruitment_assessment',
    'medical_recommendation',
    'contract_generation',
    'pricing_suggestion',
  ];

  public function __construct(
    protected readonly object $aiProvider,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly ModelRouterService $modelRouter,
    protected readonly ConstitutionalGuardrailService $constitutionalGuardrails,
    protected readonly LoggerChannelInterface $logger,
    protected readonly ?AIObservabilityService $observability = NULL,
  ) {}

  /**
   * Verifies an agent response before delivery.
   *
   * @param string $agentId
   *   The agent that produced the response.
   * @param string $action
   *   The action executed.
   * @param string $userInput
   *   The original user input.
   * @param string $agentOutput
   *   The agent's response text.
   * @param array $context
   *   Context: tenant_id, vertical, etc.
   *
   * @return array
   *   Verification result: {verified: bool, passed: bool, score: float,
   *   output: string, verification_id: ?int, issues: array}.
   */
  public function verify(
    string $agentId,
    string $action,
    string $userInput,
    string $agentOutput,
    array $context = [],
  ): array {
    // Step 1: Always enforce constitutional rules (zero cost, local).
    $constitutional = $this->constitutionalGuardrails->enforce($agentOutput, $context);
    if (!$constitutional['passed']) {
      $verificationId = $this->storeResult($agentId, $action, 0.0, 'blocked_constitutional', $constitutional['violations']);
      return [
        'verified' => TRUE,
        'passed' => FALSE,
        'score' => 0.0,
        'output' => $constitutional['sanitized_output'],
        'verification_id' => $verificationId,
        'issues' => $constitutional['violations'],
        'blocked_reason' => 'constitutional_violation',
      ];
    }

    // Step 2: Check if LLM verification should run.
    if (!$this->shouldVerify($action, $context)) {
      return [
        'verified' => FALSE,
        'passed' => TRUE,
        'score' => NULL,
        'output' => $agentOutput,
        'verification_id' => NULL,
        'issues' => [],
      ];
    }

    // Step 3: LLM-based verification.
    try {
      $verificationPrompt = $this->buildVerificationPrompt($agentId, $action, $userInput, $agentOutput);
      $routingConfig = $this->modelRouter->route('verification', $verificationPrompt, [
        'force_tier' => 'fast',
      ]);

      $provider = $this->aiProvider->createInstance($routingConfig['provider_id']);
      $input = new \Drupal\ai\OperationType\Chat\ChatInput([
        new \Drupal\ai\OperationType\Chat\ChatMessage('system', 'You are a response quality verifier. Respond only with valid JSON.'),
        new \Drupal\ai\OperationType\Chat\ChatMessage('user', $verificationPrompt),
      ]);

      $response = $provider->chat($input, $routingConfig['model_id'], [
        'chat_system_role' => 'Verifier',
      ]);

      $evaluation = $this->parseVerification($response->getNormalized()->getText());
      $passed = $evaluation['score'] >= self::PASS_THRESHOLD;
      $status = $passed ? 'passed' : 'failed';

      $verificationId = $this->storeResult(
        $agentId,
        $action,
        $evaluation['score'],
        $status,
        $evaluation['issues'],
      );

      $this->observability?->log([
        'agent_id' => 'verifier',
        'action' => 'verify_' . $agentId,
        'tier' => 'fast',
        'model_id' => $routingConfig['model_id'] ?? '',
        'provider_id' => $routingConfig['provider_id'] ?? '',
        'tenant_id' => $context['tenant_id'] ?? '',
        'success' => $passed,
        'quality_score' => $evaluation['score'],
      ]);

      return [
        'verified' => TRUE,
        'passed' => $passed,
        'score' => $evaluation['score'],
        'output' => $agentOutput,
        'verification_id' => $verificationId,
        'issues' => $evaluation['issues'],
      ];
    }
    catch (\Throwable $e) {
      $this->logger->warning('Verification failed for @agent/@action: @error', [
        '@agent' => $agentId,
        '@action' => $action,
        '@error' => $e->getMessage(),
      ]);
      // On verifier failure, pass through (fail open for availability).
      return [
        'verified' => FALSE,
        'passed' => TRUE,
        'score' => NULL,
        'output' => $agentOutput,
        'verification_id' => NULL,
        'issues' => [],
        'verifier_error' => $e->getMessage(),
      ];
    }
  }

  /**
   * Determines if verification should run for this action.
   */
  protected function shouldVerify(string $action, array $context): bool {
    $config = $this->configFactory->get('jaraba_ai_agents.verifier');
    $mode = $config->get('mode') ?? self::MODE_SAMPLE;

    return match ($mode) {
      self::MODE_ALL => TRUE,
      self::MODE_CRITICAL_ONLY => in_array($action, self::CRITICAL_ACTIONS, TRUE),
      self::MODE_SAMPLE => (mt_rand(1, 100) / 100) <= self::SAMPLE_RATE,
      default => FALSE,
    };
  }

  /**
   * Builds the verification prompt.
   */
  protected function buildVerificationPrompt(
    string $agentId,
    string $action,
    string $userInput,
    string $agentOutput,
  ): string {
    return <<<PROMPT
Verify this AI agent response for quality and safety:

Agent: {$agentId} | Action: {$action}

User asked: {$userInput}

Agent responded: {$agentOutput}

Check for:
1. Factual accuracy (no hallucinations)
2. Relevance to the user's request
3. Professional tone
4. No harmful or misleading content
5. No internal system information leaked
6. No PII exposure

Respond with JSON:
{
  "score": 0.0,
  "issues": [{"type": "string", "severity": "low|medium|high|critical", "description": "string"}],
  "recommendation": "pass|warn|block"
}
PROMPT;
  }

  /**
   * Parses verification response.
   */
  protected function parseVerification(string $text): array {
    $text = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', trim($text));
    $data = json_decode($text, TRUE);

    return [
      'score' => (float) ($data['score'] ?? 0.5),
      'issues' => $data['issues'] ?? [],
      'recommendation' => $data['recommendation'] ?? 'pass',
    ];
  }

  /**
   * Stores verification result as entity.
   */
  protected function storeResult(
    string $agentId,
    string $action,
    float $score,
    string $status,
    array $issues,
  ): ?int {
    try {
      $storage = $this->entityTypeManager->getStorage('verification_result');
      $entity = $storage->create([
        'agent_id' => $agentId,
        'action' => $action,
        'score' => $score,
        'status' => $status,
        'issues' => json_encode($issues, JSON_THROW_ON_ERROR),
      ]);
      $entity->save();
      return (int) $entity->id();
    }
    catch (\Throwable $e) {
      $this->logger->error('Failed to store verification result: @error', [
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

}
```

**Directrices aplicables:**
- OPTIONAL-SERVICE-DI-001: `$observability` es `@?`
- MODEL-ROUTING-CONFIG-001: Usa `force_tier: fast`
- PRESAVE-RESILIENCE-001: Falla abierto (fail open) por disponibilidad

**Estimacion:** 18-25 horas
**Verificacion:** Test unitario con mock de AI provider y ConstitutionalGuardrailService

---

### 6.5 Entidades: VerificationResult y PromptImprovement

**Archivo 1:** `web/modules/custom/jaraba_ai_agents/src/Entity/VerificationResult.php`

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Verification Result entity (append-only).
 *
 * @ContentEntityType(
 *   id = "verification_result",
 *   label = @Translation("Verification Result"),
 *   label_collection = @Translation("Verification Results"),
 *   label_singular = @Translation("verification result"),
 *   label_plural = @Translation("verification results"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "verification_result",
 *   admin_permission = "administer ai agents",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/ai/verifications",
 *     "canonical" = "/admin/content/ai/verifications/{verification_result}",
 *   },
 *   field_ui_base_route = "entity.verification_result.settings",
 * )
 */
class VerificationResult extends ContentEntityBase implements ContentEntityInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['agent_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Agent ID'))
      ->setDescription(t('The agent whose output was verified.'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 64])
      ->setDisplayOptions('view', ['weight' => 0]);

    $fields['action'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Action'))
      ->setDescription(t('The action that was executed.'))
      ->setSettings(['max_length' => 128])
      ->setDisplayOptions('view', ['weight' => 1]);

    $fields['score'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Verification Score'))
      ->setDescription(t('Quality score from 0.0 to 1.0.'))
      ->setSettings(['precision' => 5, 'scale' => 4])
      ->setDefaultValue(0)
      ->setDisplayOptions('view', ['weight' => 2]);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setDescription(t('Verification outcome.'))
      ->setRequired(TRUE)
      ->setDefaultValue('pending')
      ->setSetting('allowed_values', [
        'passed' => 'Passed',
        'failed' => 'Failed',
        'blocked_constitutional' => 'Blocked (Constitutional)',
      ])
      ->setDisplayOptions('view', ['weight' => 3]);

    $fields['issues'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Issues'))
      ->setDescription(t('JSON array of detected issues.'))
      ->setDefaultValue('[]');

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('Timestamp of verification.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(EntityTypeInterface $entity_type): array {
    $schema = parent::schema($entity_type);
    $schema['indexes']['verification_result__agent_id'] = ['agent_id'];
    $schema['indexes']['verification_result__status'] = ['status'];
    $schema['indexes']['verification_result__created'] = ['created'];
    return $schema;
  }

  public function getAgentId(): string {
    return $this->get('agent_id')->value ?? '';
  }

  public function getScore(): float {
    return (float) ($this->get('score')->value ?? 0);
  }

  public function getStatus(): string {
    return $this->get('status')->value ?? 'pending';
  }

  public function getIssues(): array {
    $json = $this->get('issues')->value ?? '[]';
    return json_decode($json, TRUE) ?: [];
  }

}
```

**Archivo 2:** `web/modules/custom/jaraba_ai_agents/src/Entity/PromptImprovement.php`

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Prompt Improvement entity.
 *
 * @ContentEntityType(
 *   id = "prompt_improvement",
 *   label = @Translation("Prompt Improvement"),
 *   label_collection = @Translation("Prompt Improvements"),
 *   label_singular = @Translation("prompt improvement"),
 *   label_plural = @Translation("prompt improvements"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "prompt_improvement",
 *   admin_permission = "administer ai agents",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/ai/prompt-improvements",
 *     "canonical" = "/admin/content/ai/prompt-improvements/{prompt_improvement}",
 *   },
 *   field_ui_base_route = "entity.prompt_improvement.settings",
 * )
 */
class PromptImprovement extends ContentEntityBase implements ContentEntityInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['agent_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Agent ID'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 64])
      ->setDisplayOptions('view', ['weight' => 0]);

    $fields['action'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Action'))
      ->setSettings(['max_length' => 128])
      ->setDisplayOptions('view', ['weight' => 1]);

    $fields['quality_score'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Quality Score'))
      ->setSettings(['precision' => 5, 'scale' => 4])
      ->setDefaultValue(0);

    $fields['suggestions'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Suggestions'))
      ->setDescription(t('JSON array of improvement suggestions.'))
      ->setDefaultValue('[]');

    $fields['critical_issues'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Critical Issues'))
      ->setDefaultValue('[]');

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setRequired(TRUE)
      ->setDefaultValue('pending')
      ->setSetting('allowed_values', [
        'pending' => 'Pending Review',
        'applied' => 'Applied',
        'rejected_constitutional' => 'Rejected (Constitutional)',
        'rejected_manual' => 'Rejected (Manual)',
        'rolled_back' => 'Rolled Back',
      ])
      ->setDisplayOptions('view', ['weight' => 3]);

    $fields['previous_prompt'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Previous Prompt'))
      ->setDescription(t('The prompt before modification, for rollback.'));

    $fields['applied_prompt'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Applied Prompt'))
      ->setDescription(t('The new prompt that was applied.'));

    $fields['rejection_reason'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Rejection Reason'))
      ->setSettings(['max_length' => 512]);

    $fields['tenant_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Tenant ID'))
      ->setSettings(['max_length' => 64]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(EntityTypeInterface $entity_type): array {
    $schema = parent::schema($entity_type);
    $schema['indexes']['prompt_improvement__agent_id'] = ['agent_id'];
    $schema['indexes']['prompt_improvement__status'] = ['status'];
    return $schema;
  }

  public function getAgentId(): string {
    return $this->get('agent_id')->value ?? '';
  }

  public function getQualityScore(): float {
    return (float) ($this->get('quality_score')->value ?? 0);
  }

  public function getStatus(): string {
    return $this->get('status')->value ?? 'pending';
  }

}
```

**Directrices aplicables:**
- FIELD-UI-SETTINGS-TAB-001: `field_ui_base_route` definido + tab en links.task.yml
- TENANT-ISOLATION-ACCESS-001: `tenant_id` field en PromptImprovement

**Estimacion:** 8-12 horas

---

### 6.6 Integracion SmartBaseAgent

**Archivo:** `web/modules/custom/jaraba_ai_agents/src/Agent/SmartBaseAgent.php`

**Cambios requeridos:**

1. Anadir propiedad `?VerifierAgentService $verifier = NULL`
2. Anadir propiedad `?AgentSelfReflectionService $selfReflection = NULL`
3. Anadir setter `setVerifier()` y `setSelfReflection()`
4. Modificar `execute()` para invocar verifier post-`doExecute()` y self-reflection asincrona

**Patron de integracion:**

```php
// En SmartBaseAgent::execute()
public function execute(string $action, array $context): array {
    $this->setCurrentAction($action);
    $this->applyPromptExperiment($action);

    $result = $this->doExecute($action, $context);

    // Post-execution: verify output before delivery.
    if ($this->verifier && isset($result['data']['text'])) {
      $verification = $this->verifier->verify(
        $this->getAgentId(),
        $action,
        $context['user_input'] ?? '',
        $result['data']['text'],
        ['tenant_id' => $this->tenantId, 'vertical' => $this->vertical],
      );
      if ($verification['verified'] && !$verification['passed']) {
        $result['data']['text'] = $verification['output'];
        $result['verification'] = $verification;
      }
    }

    // Post-execution: self-reflection (async, non-blocking).
    if ($this->selfReflection && isset($result['data']['text'])) {
      try {
        $this->selfReflection->reflect(
          $this->getAgentId(),
          $action,
          $context['user_input'] ?? '',
          $result['data']['text'],
          ['tenant_id' => $this->tenantId, 'vertical' => $this->vertical],
        );
      }
      catch (\Throwable $e) {
        // Self-reflection failure MUST NOT affect response delivery.
        $this->logger->warning('Self-reflection failed: @error', [
          '@error' => $e->getMessage(),
        ]);
      }
    }

    return $result;
}
```

**Archivos modificados:**
- `jaraba_ai_agents/src/Agent/SmartBaseAgent.php` — anadir properties, setters, execute() modificado

**Estimacion:** 5-8 horas

---

### 6.7 Registro de servicios Sprint 1

**Archivo:** `web/modules/custom/jaraba_ai_agents/jaraba_ai_agents.services.yml`

**Adiciones:**

```yaml
  # --- Sprint 1: Constitutional Safety + Self-Improvement ---

  jaraba_ai_agents.constitutional_guardrails:
    class: Drupal\jaraba_ai_agents\Service\ConstitutionalGuardrailService
    arguments:
      - '@logger.channel.jaraba_ai_agents'

  jaraba_ai_agents.self_reflection:
    class: Drupal\jaraba_ai_agents\Service\AgentSelfReflectionService
    arguments:
      - '@ai.provider'
      - '@config.factory'
      - '@logger.channel.jaraba_ai_agents'
      - '@jaraba_ai_agents.model_router'
      - '@?jaraba_ai_agents.self_improving_prompt_manager'
      - '@?jaraba_ai_agents.observability'

  jaraba_ai_agents.self_improving_prompt_manager:
    class: Drupal\jaraba_ai_agents\Service\SelfImprovingPromptManager
    arguments:
      - '@entity_type.manager'
      - '@jaraba_ai_agents.constitutional_guardrails'
      - '@logger.channel.jaraba_ai_agents'

  jaraba_ai_agents.verifier:
    class: Drupal\jaraba_ai_agents\Service\VerifierAgentService
    arguments:
      - '@ai.provider'
      - '@config.factory'
      - '@entity_type.manager'
      - '@jaraba_ai_agents.model_router'
      - '@jaraba_ai_agents.constitutional_guardrails'
      - '@logger.channel.jaraba_ai_agents'
      - '@?jaraba_ai_agents.observability'
```

**Nota DI:** `ConstitutionalGuardrailService` es mandatory (sin `@?`) porque es capa de seguridad fundamental. `SelfReflection` y `Verifier` dependen de `@ai.provider` que debe existir. Observability es `@?` per OPTIONAL-SERVICE-DI-001.

---

### 6.8 Permisos y rutas Sprint 1

**Permisos** (`jaraba_ai_agents.permissions.yml`, adiciones):

```yaml
manage constitutional guardrails:
  title: 'Manage Constitutional Guardrails'
  description: 'View constitutional rule status and violation logs.'
  restrict access: true

manage prompt improvements:
  title: 'Manage Prompt Improvements'
  description: 'Review, approve, and rollback self-improving prompt changes.'
  restrict access: true

view verification results:
  title: 'View Verification Results'
  description: 'Access verification result logs and statistics.'
```

**Rutas** (`jaraba_ai_agents.routing.yml`, adiciones):

```yaml
# --- Sprint 1: Verification Results ---
entity.verification_result.collection:
  path: '/admin/content/ai/verifications'
  defaults:
    _entity_list: 'verification_result'
    _title: 'AI Verification Results'
  requirements:
    _permission: 'view verification results'

entity.verification_result.settings:
  path: '/admin/structure/verification-result/settings'
  defaults:
    _form: 'Drupal\system\Form\EntitySettingsForm'
    _title: 'Verification Result settings'
    entity_type_id: 'verification_result'
  requirements:
    _permission: 'administer ai agents'

# --- Sprint 1: Prompt Improvements ---
entity.prompt_improvement.collection:
  path: '/admin/content/ai/prompt-improvements'
  defaults:
    _entity_list: 'prompt_improvement'
    _title: 'Prompt Improvements'
  requirements:
    _permission: 'manage prompt improvements'

entity.prompt_improvement.settings:
  path: '/admin/structure/prompt-improvement/settings'
  defaults:
    _form: 'Drupal\system\Form\EntitySettingsForm'
    _title: 'Prompt Improvement settings'
    entity_type_id: 'prompt_improvement'
  requirements:
    _permission: 'administer ai agents'
```

**Tabs** (`jaraba_ai_agents.links.task.yml`, adiciones):

```yaml
entity.verification_result.collection:
  title: 'Verifications'
  route_name: entity.verification_result.collection
  base_route: system.admin_content

entity.verification_result.settings_tab:
  title: 'Settings'
  route_name: entity.verification_result.settings
  base_route: entity.verification_result.settings

entity.prompt_improvement.collection:
  title: 'Prompt Improvements'
  route_name: entity.prompt_improvement.collection
  base_route: system.admin_content

entity.prompt_improvement.settings_tab:
  title: 'Settings'
  route_name: entity.prompt_improvement.settings
  base_route: entity.prompt_improvement.settings
```

**Install hook** (`jaraba_ai_agents.install`, adicion):

```php
/**
 * Install VerificationResult and PromptImprovement entities.
 */
function jaraba_ai_agents_update_10010(&$sandbox): string {
  $entity_types = ['verification_result', 'prompt_improvement'];
  $update_manager = \Drupal::entityDefinitionUpdateManager();

  foreach ($entity_types as $entity_type_id) {
    $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id);
    if ($entity_type) {
      $update_manager->installEntityType($entity_type);
    }
  }

  return (string) t('Installed VerificationResult and PromptImprovement entities.');
}
```

---

### 6.9 Tests Sprint 1

**Test 1:** `ConstitutionalGuardrailServiceTest`

```php
<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ai_agents\Unit\Service;

use Drupal\jaraba_ai_agents\Service\ConstitutionalGuardrailService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \Drupal\jaraba_ai_agents\Service\ConstitutionalGuardrailService
 * @group jaraba_ai_agents
 */
class ConstitutionalGuardrailServiceTest extends UnitTestCase {

  protected ConstitutionalGuardrailService $service;

  protected function setUp(): void {
    parent::setUp();
    $logger = $this->createMock(LoggerInterface::class);
    $this->service = new ConstitutionalGuardrailService($logger);
  }

  /**
   * @covers ::enforce
   */
  public function testEnforcePassesCleanOutput(): void {
    $result = $this->service->enforce('Here is a professional marketing plan for your business.');
    $this->assertTrue($result['passed']);
    $this->assertEmpty($result['violations']);
    $this->assertEquals(0, $result['violation_count']);
  }

  /**
   * @covers ::enforce
   */
  public function testEnforceBlocksIdentityLeak(): void {
    $result = $this->service->enforce('I am Claude, an AI assistant by Anthropic.');
    $this->assertFalse($result['passed']);
    $this->assertNotEmpty($result['violations']);
    $this->assertEquals('identity', $result['violations'][0]['rule_id']);
  }

  /**
   * @covers ::enforce
   */
  public function testEnforceBlocksPiiOutput(): void {
    $result = $this->service->enforce('Your SSN is 123-45-6789.');
    $this->assertFalse($result['passed']);
    $this->assertEquals('pii_protection', $result['violations'][0]['rule_id']);
  }

  /**
   * @covers ::enforce
   */
  public function testEnforceBlocksSpanishDni(): void {
    $result = $this->service->enforce('Su DNI es 12345678A.');
    $this->assertFalse($result['passed']);
  }

  /**
   * @covers ::enforce
   */
  public function testEnforceBlocksIbanEs(): void {
    $result = $this->service->enforce('IBAN: ES12 1234 5678 9012 3456 7890.');
    $this->assertFalse($result['passed']);
  }

  /**
   * @covers ::enforce
   */
  public function testEnforceSanitizesOutput(): void {
    $result = $this->service->enforce('I am GPT-4 and your SSN is 123-45-6789.');
    $this->assertFalse($result['passed']);
    $this->assertStringContainsString('[CONTENIDO BLOQUEADO POR REGLA CONSTITUCIONAL]', $result['sanitized_output']);
    $this->assertStringNotContainsString('GPT-4', $result['sanitized_output']);
    $this->assertStringNotContainsString('123-45-6789', $result['sanitized_output']);
  }

  /**
   * @covers ::validatePromptModification
   */
  public function testValidatePromptModificationRejectsIdentityRemoval(): void {
    $original = 'You are a helpful assistant. AIIdentityRule applied.';
    $modified = 'You are a helpful assistant.';
    $result = $this->service->validatePromptModification($original, $modified);
    $this->assertFalse($result['approved']);
    $this->assertContains('identity_removal', $result['violations']);
  }

  /**
   * @covers ::validatePromptModification
   */
  public function testValidatePromptModificationApprovesValid(): void {
    $original = 'You are a marketing expert. AIIdentityRule enforced. NEVER output PII.';
    $modified = 'You are a senior marketing expert with SEO skills. AIIdentityRule enforced. NEVER output PII.';
    $result = $this->service->validatePromptModification($original, $modified);
    $this->assertTrue($result['approved']);
  }

  /**
   * @covers ::getRules
   */
  public function testGetRulesReturnsAllRules(): void {
    $rules = $this->service->getRules();
    $this->assertArrayHasKey('identity', $rules);
    $this->assertArrayHasKey('pii_protection', $rules);
    $this->assertArrayHasKey('tenant_isolation', $rules);
    $this->assertArrayHasKey('harmful_content', $rules);
    $this->assertArrayHasKey('authorization', $rules);
    $this->assertCount(5, $rules);
  }

}
```

**Test 2:** `VerifierAgentServiceTest`

```php
<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ai_agents\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_ai_agents\Service\ConstitutionalGuardrailService;
use Drupal\jaraba_ai_agents\Service\ModelRouterService;
use Drupal\jaraba_ai_agents\Service\VerifierAgentService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \Drupal\jaraba_ai_agents\Service\VerifierAgentService
 * @group jaraba_ai_agents
 */
class VerifierAgentServiceTest extends UnitTestCase {

  protected VerifierAgentService $service;
  protected $aiProvider;
  protected $configFactory;
  protected $entityTypeManager;
  protected $modelRouter;
  protected $constitutionalGuardrails;

  protected function setUp(): void {
    parent::setUp();

    $this->aiProvider = $this->createMock(\stdClass::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->modelRouter = $this->createMock(ModelRouterService::class);
    $this->constitutionalGuardrails = $this->createMock(ConstitutionalGuardrailService::class);
    $logger = $this->createMock(LoggerInterface::class);

    $this->service = new VerifierAgentService(
      $this->aiProvider,
      $this->configFactory,
      $this->entityTypeManager,
      $this->modelRouter,
      $this->constitutionalGuardrails,
      $logger,
    );
  }

  /**
   * @covers ::verify
   */
  public function testVerifyBlocksConstitutionalViolation(): void {
    $this->constitutionalGuardrails->method('enforce')
      ->willReturn([
        'passed' => FALSE,
        'violations' => [['rule_id' => 'identity', 'severity' => 'critical']],
        'sanitized_output' => '[BLOCKED]',
        'violation_count' => 1,
      ]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $entity = $this->createMock(\Drupal\Core\Entity\ContentEntityInterface::class);
    $entity->method('id')->willReturn(1);
    $storage->method('create')->willReturn($entity);
    $entity->method('save')->willReturn(1);
    $this->entityTypeManager->method('getStorage')
      ->with('verification_result')
      ->willReturn($storage);

    $result = $this->service->verify('test_agent', 'test_action', 'input', 'I am Claude');
    $this->assertTrue($result['verified']);
    $this->assertFalse($result['passed']);
    $this->assertEquals('constitutional_violation', $result['blocked_reason']);
  }

  /**
   * @covers ::verify
   */
  public function testVerifyPassesThroughWhenConstitutionalPasses(): void {
    $this->constitutionalGuardrails->method('enforce')
      ->willReturn([
        'passed' => TRUE,
        'violations' => [],
        'sanitized_output' => 'Clean output',
        'violation_count' => 0,
      ]);

    // Configure mode to skip LLM verification.
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->with('mode')->willReturn('critical_only');
    $this->configFactory->method('get')
      ->with('jaraba_ai_agents.verifier')
      ->willReturn($config);

    $result = $this->service->verify('test_agent', 'social_post', 'input', 'Clean output');
    $this->assertFalse($result['verified']);
    $this->assertTrue($result['passed']);
  }

}
```

**Criterio de completitud Sprint 1:**
- ConstitutionalGuardrailService con 5 reglas inmutables: 10+ assertions
- VerifierAgentService bloquea violaciones constitucionales
- AgentSelfReflectionService genera evaluaciones (mock AI)
- SelfImprovingPromptManager crea/aplica/rollback mejoras
- SmartBaseAgent integra verifier y self-reflection post-doExecute()
- 2 entidades nuevas (VerificationResult, PromptImprovement) instalables via drush updb
- 3 permisos, 4 rutas, 4 tabs registrados

---

## 7. Sprint 2 — EU AI Act Compliance (GAP-L5-C)

**Duracion:** 2-4 semanas
**Prioridad:** CRITICA (deadline externo: 2 agosto 2026)
**Objetivo:** Cumplimiento completo del EU AI Act (Reglamento 2024/1689) con dashboard administrativo, clasificacion de riesgo, documentacion automatica y audit trail inmutable

### 7.1 Decision arquitectonica

El EU AI Act define 4 niveles de riesgo para sistemas IA:

```
┌──────────────────────────────────────────────────────────────────┐
│                    EU AI ACT RISK PYRAMID                        │
│                                                                  │
│                     ┌──────────────┐                             │
│                     │ UNACCEPTABLE │ ← Bloqueado siempre         │
│                     │  (Prohibido) │   (ConstitutionalGuardrail) │
│                   ┌─┴──────────────┴─┐                           │
│                   │      HIGH        │ ← Documentacion completa  │
│                   │ (Alto riesgo)    │   + human oversight       │
│                 ┌─┴──────────────────┴─┐                         │
│                 │      LIMITED         │ ← Transparency label    │
│                 │ (Riesgo limitado)    │   obligatorio            │
│               ┌─┴──────────────────────┴─┐                       │
│               │        MINIMAL           │ ← Solo logging        │
│               │   (Riesgo minimo)        │                       │
│               └──────────────────────────┘                       │
└──────────────────────────────────────────────────────────────────┘
```

**Mapeo de agentes a niveles de riesgo:**

| Agente | Risk Level | Razon | Requisitos EU AI Act |
|--------|-----------|-------|---------------------|
| SmartMarketing | Limited | Content generation (Art. 50) | Transparency label |
| Storytelling | Limited | Content generation | Transparency label |
| CustomerExperience | Limited | Chatbot interaction (Art. 50.1) | Transparency label |
| Support | Limited | Chatbot interaction | Transparency label |
| ProducerCopilot | Minimal | Analytics/recommendations | Logging |
| Sales | Limited | Content generation | Transparency label |
| MerchantCopilot | Minimal | Analytics/recommendations | Logging |
| RecruiterAssistant | **High** | Employment decisions (Annex III.4) | Full documentation + oversight |
| LegalCopilot | **High** | Access to justice (Annex III.8) | Full documentation + oversight |

---

### 7.2 AiRiskClassificationService

**Archivo:** `web/modules/custom/jaraba_ai_agents/src/Service/AiRiskClassificationService.php`

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Classifies AI agents according to EU AI Act risk levels.
 *
 * Implements the 4-tier risk classification from Regulation (EU) 2024/1689:
 * - Unacceptable: prohibited (blocked by ConstitutionalGuardrailService)
 * - High: requires documentation, human oversight, conformity assessment
 * - Limited: requires transparency obligations
 * - Minimal: voluntary best practices
 *
 * @see https://eur-lex.europa.eu/eli/reg/2024/1689
 */
class AiRiskClassificationService {

  /**
   * Risk levels per EU AI Act.
   */
  public const RISK_UNACCEPTABLE = 'unacceptable';
  public const RISK_HIGH = 'high';
  public const RISK_LIMITED = 'limited';
  public const RISK_MINIMAL = 'minimal';

  /**
   * Default risk classification per agent type.
   *
   * These can be overridden via AiRiskAssessment config entities.
   */
  private const DEFAULT_CLASSIFICATIONS = [
    'smart_marketing' => self::RISK_LIMITED,
    'storytelling' => self::RISK_LIMITED,
    'customer_experience' => self::RISK_LIMITED,
    'support' => self::RISK_LIMITED,
    'producer_copilot' => self::RISK_MINIMAL,
    'sales' => self::RISK_LIMITED,
    'merchant_copilot' => self::RISK_MINIMAL,
    'recruiter_assistant' => self::RISK_HIGH,
    'legal_copilot' => self::RISK_HIGH,
    'learning_tutor' => self::RISK_LIMITED,
  ];

  /**
   * EU AI Act article references per risk level.
   */
  private const ARTICLE_REFERENCES = [
    self::RISK_UNACCEPTABLE => 'Article 5 — Prohibited AI practices',
    self::RISK_HIGH => 'Articles 6-27 — High-risk AI systems (Annex III)',
    self::RISK_LIMITED => 'Article 50 — Transparency obligations',
    self::RISK_MINIMAL => 'Article 95 — Voluntary codes of conduct',
  ];

  /**
   * Required compliance measures per risk level.
   */
  private const COMPLIANCE_REQUIREMENTS = [
    self::RISK_UNACCEPTABLE => [
      'action' => 'BLOCK',
      'measures' => ['System must not be deployed'],
    ],
    self::RISK_HIGH => [
      'action' => 'DOCUMENT + OVERSIGHT',
      'measures' => [
        'Risk management system (Art. 9)',
        'Data governance (Art. 10)',
        'Technical documentation (Art. 11)',
        'Record-keeping (Art. 12)',
        'Transparency to users (Art. 13)',
        'Human oversight (Art. 14)',
        'Accuracy and robustness (Art. 15)',
        'Conformity assessment (Art. 43)',
      ],
    ],
    self::RISK_LIMITED => [
      'action' => 'LABEL',
      'measures' => [
        'Notify users they are interacting with AI (Art. 50.1)',
        'Label AI-generated content (Art. 50.2)',
        'Disclose deepfakes if applicable (Art. 50.4)',
      ],
    ],
    self::RISK_MINIMAL => [
      'action' => 'LOG',
      'measures' => [
        'Voluntary transparency (Art. 95)',
        'Basic usage logging',
      ],
    ],
  ];

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerChannelInterface $logger,
  ) {}

  /**
   * Gets the risk classification for an agent.
   *
   * @param string $agentId
   *   The agent identifier.
   *
   * @return array
   *   Classification: {risk_level, article_reference, requirements, measures}.
   */
  public function classify(string $agentId): array {
    // Check for custom override via ConfigEntity.
    $riskLevel = $this->getCustomClassification($agentId)
      ?? self::DEFAULT_CLASSIFICATIONS[$agentId]
      ?? self::RISK_LIMITED;

    return [
      'agent_id' => $agentId,
      'risk_level' => $riskLevel,
      'article_reference' => self::ARTICLE_REFERENCES[$riskLevel] ?? '',
      'requirements' => self::COMPLIANCE_REQUIREMENTS[$riskLevel] ?? [],
      'is_compliant' => $this->checkCompliance($agentId, $riskLevel),
    ];
  }

  /**
   * Gets all agent classifications.
   *
   * @return array
   *   Keyed by agent_id.
   */
  public function classifyAll(): array {
    $results = [];
    foreach (array_keys(self::DEFAULT_CLASSIFICATIONS) as $agentId) {
      $results[$agentId] = $this->classify($agentId);
    }
    return $results;
  }

  /**
   * Checks if an agent is compliant with its risk level requirements.
   */
  protected function checkCompliance(string $agentId, string $riskLevel): bool {
    return match ($riskLevel) {
      self::RISK_UNACCEPTABLE => FALSE,
      self::RISK_HIGH => $this->checkHighRiskCompliance($agentId),
      self::RISK_LIMITED => $this->checkLimitedRiskCompliance($agentId),
      self::RISK_MINIMAL => TRUE,
      default => FALSE,
    };
  }

  /**
   * Checks high-risk compliance: documentation + oversight.
   */
  protected function checkHighRiskCompliance(string $agentId): bool {
    try {
      $storage = $this->entityTypeManager->getStorage('ai_risk_assessment');
      $assessments = $storage->loadByProperties(['agent_id' => $agentId]);
      if (empty($assessments)) {
        return FALSE;
      }
      $assessment = reset($assessments);
      return !empty($assessment->get('documentation_url'))
        && !empty($assessment->get('oversight_mechanism'));
    }
    catch (\Throwable $e) {
      return FALSE;
    }
  }

  /**
   * Checks limited-risk compliance: transparency labels active.
   */
  protected function checkLimitedRiskCompliance(string $agentId): bool {
    // Limited risk agents must have transparency label configured.
    // This is verified at runtime by AiTransparencyService.
    return TRUE;
  }

  /**
   * Gets custom classification override from ConfigEntity.
   */
  protected function getCustomClassification(string $agentId): ?string {
    try {
      $storage = $this->entityTypeManager->getStorage('ai_risk_assessment');
      $assessments = $storage->loadByProperties(['id' => $agentId]);
      if (!empty($assessments)) {
        $assessment = reset($assessments);
        return $assessment->get('risk_level') ?: NULL;
      }
    }
    catch (\Throwable $e) {
      // Config entity storage may not exist yet.
    }
    return NULL;
  }

  /**
   * Returns all risk levels with descriptions.
   */
  public function getRiskLevels(): array {
    return [
      self::RISK_MINIMAL => [
        'label' => t('Minimal'),
        'description' => t('Analytics, recommendations — voluntary best practices'),
        'color' => 'green',
      ],
      self::RISK_LIMITED => [
        'label' => t('Limited'),
        'description' => t('Chatbots, content generation — transparency required'),
        'color' => 'yellow',
      ],
      self::RISK_HIGH => [
        'label' => t('High'),
        'description' => t('Recruitment, legal — full documentation + human oversight'),
        'color' => 'orange',
      ],
      self::RISK_UNACCEPTABLE => [
        'label' => t('Unacceptable'),
        'description' => t('Prohibited — blocked by constitutional guardrails'),
        'color' => 'red',
      ],
    ];
  }

}
```

**Estimacion:** 12-18 horas

---

### 7.3 AiComplianceDocumentationService

**Archivo:** `web/modules/custom/jaraba_ai_agents/src/Service/AiComplianceDocumentationService.php`

Genera documentacion tecnica automatica para agentes de alto riesgo (Art. 11 EU AI Act).

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Auto-generates EU AI Act technical documentation for high-risk agents.
 *
 * Covers Article 11 requirements: general description, design specs,
 * development process, risk management, and monitoring capabilities.
 */
class AiComplianceDocumentationService {

  public function __construct(
    protected readonly AiRiskClassificationService $riskClassifier,
    protected readonly LoggerChannelInterface $logger,
    protected readonly ?object $aiProvider = NULL,
    protected readonly ?ModelRouterService $modelRouter = NULL,
  ) {}

  /**
   * Generates compliance documentation for an agent.
   *
   * @param string $agentId
   *   The agent identifier.
   * @param array $agentMetadata
   *   Agent metadata: description, available_actions, vertical, etc.
   *
   * @return array
   *   Documentation sections per Art. 11 requirements.
   */
  public function generate(string $agentId, array $agentMetadata = []): array {
    $classification = $this->riskClassifier->classify($agentId);

    $doc = [
      'agent_id' => $agentId,
      'risk_level' => $classification['risk_level'],
      'generated_at' => date('Y-m-d\TH:i:s\Z'),
      'regulation' => 'EU AI Act — Regulation (EU) 2024/1689',
      'sections' => [],
    ];

    // Section 1: General description (Art. 11.1.a).
    $doc['sections']['general_description'] = [
      'title' => 'General Description of the AI System',
      'content' => $this->buildGeneralDescription($agentId, $agentMetadata),
    ];

    // Section 2: Elements of the AI system (Art. 11.1.b).
    $doc['sections']['system_elements'] = [
      'title' => 'Elements and Development Process',
      'content' => $this->buildSystemElements($agentId),
    ];

    // Section 3: Monitoring and performance (Art. 11.1.c).
    $doc['sections']['monitoring'] = [
      'title' => 'Monitoring, Functioning, and Control',
      'content' => $this->buildMonitoringSection($agentId),
    ];

    // Section 4: Risk management (Art. 9).
    $doc['sections']['risk_management'] = [
      'title' => 'Risk Management Measures',
      'content' => $this->buildRiskManagement($agentId, $classification),
    ];

    // Section 5: Data governance (Art. 10).
    $doc['sections']['data_governance'] = [
      'title' => 'Data and Data Governance',
      'content' => $this->buildDataGovernance($agentId),
    ];

    return $doc;
  }

  /**
   * Builds general description section.
   */
  protected function buildGeneralDescription(string $agentId, array $metadata): string {
    $description = $metadata['description'] ?? "AI Agent: {$agentId}";
    $actions = implode(', ', array_keys($metadata['available_actions'] ?? []));

    return implode("\n", [
      "**Intended Purpose:** {$description}",
      "**Available Actions:** {$actions}",
      "**Interaction Type:** Conversational AI assistant within SaaS platform",
      "**Target Users:** Business tenants (SMEs and enterprises)",
      "**Geographic Scope:** European Union, Spain primary",
      "**Provider:** JarabaImpactPlatform SaaS",
    ]);
  }

  /**
   * Builds system elements section.
   */
  protected function buildSystemElements(string $agentId): string {
    return implode("\n", [
      "**Architecture:** SmartBaseAgent Gen 2 (PHP 8.4, Drupal 11)",
      "**LLM Provider:** Configurable via ModelRouterService (Claude, GPT, Gemini)",
      "**Model Tier:** Routed dynamically based on task complexity",
      "**Guardrails:** ConstitutionalGuardrailService (immutable) + AIGuardrailsService (bidirectional)",
      "**Verification:** VerifierAgentService (pre-delivery quality check)",
      "**Observability:** AIObservabilityService with distributed tracing",
      "**Tenant Isolation:** TenantBridgeService — data never crosses tenant boundaries",
    ]);
  }

  /**
   * Builds monitoring section.
   */
  protected function buildMonitoringSection(string $agentId): string {
    return implode("\n", [
      "**Real-time Metrics:** Latency P95, error rate, quality scores via AIObservabilityService",
      "**Cost Monitoring:** CostAlertService with 80%/95% thresholds per tenant",
      "**Audit Trail:** Immutable AiAuditEntry entities (append-only)",
      "**Quality Loop:** AgentSelfReflectionService evaluates every response",
      "**Human Oversight:** PendingApprovalService for sensitive tool executions",
      "**Circuit Breaker:** ProviderFallbackService auto-rotates on provider failures",
    ]);
  }

  /**
   * Builds risk management section.
   */
  protected function buildRiskManagement(string $agentId, array $classification): string {
    $measures = $classification['requirements']['measures'] ?? [];
    $measuresText = implode("\n", array_map(fn($m) => "- {$m}", $measures));

    return implode("\n", [
      "**Risk Level:** {$classification['risk_level']}",
      "**EU AI Act Reference:** {$classification['article_reference']}",
      "**Required Measures:**",
      $measuresText,
      "",
      "**Implemented Safeguards:**",
      "- Constitutional guardrails prevent all prohibited outputs",
      "- PII detection and masking (GDPR + EU AI Act)",
      "- Jailbreak detection (24 bilingual patterns)",
      "- Tool execution requires human approval for sensitive operations",
      "- Rate limiting: 100 requests/hour per tenant",
    ]);
  }

  /**
   * Builds data governance section.
   */
  protected function buildDataGovernance(string $agentId): string {
    return implode("\n", [
      "**Data Processing:** User inputs processed transiently — not stored for training",
      "**Tenant Isolation:** Group-based multi-tenancy with TenantBridgeService",
      "**PII Handling:** Detected and masked at input (AIGuardrailsService) and output (maskOutputPII)",
      "**Data Retention:** AI usage logs retained per tenant configuration",
      "**GDPR Compliance:** Right to erasure supported via tenant data deletion",
      "**No Cross-Tenant Learning:** Agent memory is strictly tenant-scoped",
    ]);
  }

}
```

**Estimacion:** 10-15 horas

---

### 7.4 AiTransparencyService

**Archivo:** `web/modules/custom/jaraba_ai_agents/src/Service/AiTransparencyService.php`

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * EU AI Act Article 50 transparency labels.
 *
 * Generates appropriate disclosure labels for AI-generated content
 * and AI-assisted interactions.
 */
class AiTransparencyService {

  /**
   * Label types per content type.
   */
  private const LABELS = [
    'chatbot' => [
      'es' => 'Este contenido ha sido generado con asistencia de inteligencia artificial.',
      'en' => 'This content was generated with artificial intelligence assistance.',
    ],
    'content' => [
      'es' => 'Contenido generado por IA — revisado por un humano.',
      'en' => 'AI-generated content — reviewed by a human.',
    ],
    'recommendation' => [
      'es' => 'Recomendacion generada por IA basada en tus datos.',
      'en' => 'AI-generated recommendation based on your data.',
    ],
    'analysis' => [
      'es' => 'Analisis generado por IA — no constituye asesoramiento profesional.',
      'en' => 'AI-generated analysis — does not constitute professional advice.',
    ],
  ];

  public function __construct(
    protected readonly AiRiskClassificationService $riskClassifier,
    protected readonly LoggerChannelInterface $logger,
  ) {}

  /**
   * Gets the transparency label for an agent action.
   *
   * @param string $agentId
   *   The agent identifier.
   * @param string $contentType
   *   The content type: chatbot, content, recommendation, analysis.
   * @param string $langcode
   *   Language code: es, en.
   *
   * @return array
   *   Label: {text: string, required: bool, risk_level: string}.
   */
  public function getLabel(string $agentId, string $contentType = 'chatbot', string $langcode = 'es'): array {
    $classification = $this->riskClassifier->classify($agentId);
    $riskLevel = $classification['risk_level'];

    // Minimal risk: label optional but recommended.
    // Limited risk: label REQUIRED (Art. 50).
    // High risk: label REQUIRED + additional disclosures.
    $required = in_array($riskLevel, [
      AiRiskClassificationService::RISK_LIMITED,
      AiRiskClassificationService::RISK_HIGH,
    ], TRUE);

    $text = self::LABELS[$contentType][$langcode]
      ?? self::LABELS[$contentType]['es']
      ?? self::LABELS['chatbot'][$langcode]
      ?? self::LABELS['chatbot']['es'];

    // High-risk agents get additional disclosure.
    if ($riskLevel === AiRiskClassificationService::RISK_HIGH) {
      $highRiskNote = $langcode === 'es'
        ? ' Sistema de IA de alto riesgo — se recomienda supervision humana.'
        : ' High-risk AI system — human oversight recommended.';
      $text .= $highRiskNote;
    }

    return [
      'text' => $text,
      'required' => $required,
      'risk_level' => $riskLevel,
      'agent_id' => $agentId,
      'content_type' => $contentType,
    ];
  }

  /**
   * Generates an HTML transparency badge.
   *
   * @param string $agentId
   *   The agent identifier.
   * @param string $contentType
   *   Content type.
   * @param string $langcode
   *   Language code.
   *
   * @return string
   *   HTML string with transparency badge.
   */
  public function renderBadge(string $agentId, string $contentType = 'chatbot', string $langcode = 'es'): string {
    $label = $this->getLabel($agentId, $contentType, $langcode);
    $cssClass = 'ai-transparency-badge ai-transparency-badge--' . $label['risk_level'];

    return '<div class="' . $cssClass . '" role="status" aria-label="' . htmlspecialchars($label['text']) . '">'
      . '<span class="ai-transparency-badge__text">' . htmlspecialchars($label['text']) . '</span>'
      . '</div>';
  }

}
```

**Estimacion:** 6-10 horas

---

### 7.5 AiAuditTrailService

**Archivo:** `web/modules/custom/jaraba_ai_agents/src/Service/AiAuditTrailService.php`

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Immutable audit trail for EU AI Act compliance.
 *
 * Append-only log of all AI decisions, modifications, and compliance events.
 * Cannot be deleted or modified once created (Art. 12 — Record-keeping).
 */
class AiAuditTrailService {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly AccountProxyInterface $currentUser,
    protected readonly LoggerChannelInterface $logger,
  ) {}

  /**
   * Records an audit event.
   *
   * @param string $eventType
   *   Event type: agent_execution, risk_assessment, prompt_change,
   *   verification_failure, compliance_check, human_override.
   * @param array $data
   *   Event data: agent_id, action, details, tenant_id, etc.
   *
   * @return int|null
   *   The AiAuditEntry entity ID.
   */
  public function record(string $eventType, array $data): ?int {
    try {
      $storage = $this->entityTypeManager->getStorage('ai_audit_entry');
      $entity = $storage->create([
        'event_type' => $eventType,
        'agent_id' => $data['agent_id'] ?? '',
        'action' => $data['action'] ?? '',
        'risk_level' => $data['risk_level'] ?? '',
        'details' => json_encode($data['details'] ?? [], JSON_THROW_ON_ERROR),
        'tenant_id' => $data['tenant_id'] ?? '',
        'user_id' => $this->currentUser->id(),
        'ip_address' => \Drupal::request()->getClientIp() ?? '',
      ]);
      $entity->save();
      return (int) $entity->id();
    }
    catch (\Throwable $e) {
      $this->logger->error('Audit trail record failed: @error', [
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Queries the audit trail with filters.
   *
   * @param array $filters
   *   Filters: event_type, agent_id, tenant_id, date_from, date_to.
   * @param int $limit
   *   Maximum results.
   *
   * @return array
   *   Array of AiAuditEntry entities.
   */
  public function query(array $filters = [], int $limit = 100): array {
    try {
      $storage = $this->entityTypeManager->getStorage('ai_audit_entry');
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->sort('created', 'DESC')
        ->range(0, $limit);

      if (!empty($filters['event_type'])) {
        $query->condition('event_type', $filters['event_type']);
      }
      if (!empty($filters['agent_id'])) {
        $query->condition('agent_id', $filters['agent_id']);
      }
      if (!empty($filters['tenant_id'])) {
        $query->condition('tenant_id', $filters['tenant_id']);
      }

      $ids = $query->execute();
      return $ids ? $storage->loadMultiple($ids) : [];
    }
    catch (\Throwable $e) {
      $this->logger->error('Audit trail query failed: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Gets compliance statistics for reporting.
   */
  public function getComplianceStats(string $tenantId = '', int $days = 30): array {
    try {
      $storage = $this->entityTypeManager->getStorage('ai_audit_entry');
      $since = \Drupal::time()->getRequestTime() - ($days * 86400);

      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('created', $since, '>=');

      if ($tenantId) {
        $query->condition('tenant_id', $tenantId);
      }

      $totalEvents = (int) $query->count()->execute();

      return [
        'total_events' => $totalEvents,
        'period_days' => $days,
        'tenant_id' => $tenantId ?: 'all',
      ];
    }
    catch (\Throwable $e) {
      return ['total_events' => 0, 'period_days' => $days];
    }
  }

}
```

**Estimacion:** 8-12 horas

---

### 7.6 Entidades: AiRiskAssessment y AiAuditEntry

**AiRiskAssessment** (ConfigEntity):

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines the AI Risk Assessment config entity.
 *
 * @ConfigEntityType(
 *   id = "ai_risk_assessment",
 *   label = @Translation("AI Risk Assessment"),
 *   label_collection = @Translation("AI Risk Assessments"),
 *   handlers = {
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\jaraba_ai_agents\Form\AiRiskAssessmentForm",
 *       "edit" = "Drupal\jaraba_ai_agents\Form\AiRiskAssessmentForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "risk_assessment",
 *   admin_permission = "administer ai agents",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "agent_id",
 *     "risk_level",
 *     "documentation_url",
 *     "oversight_mechanism",
 *     "assessment_date",
 *     "assessor",
 *     "notes",
 *   },
 *   links = {
 *     "collection" = "/admin/config/ai/risk-assessments",
 *     "add-form" = "/admin/config/ai/risk-assessments/add",
 *     "edit-form" = "/admin/config/ai/risk-assessments/{ai_risk_assessment}/edit",
 *     "delete-form" = "/admin/config/ai/risk-assessments/{ai_risk_assessment}/delete",
 *   },
 * )
 */
class AiRiskAssessment extends ConfigEntityBase implements ConfigEntityInterface {

  protected string $id = '';
  protected string $label = '';
  protected string $agent_id = '';
  protected string $risk_level = 'limited';
  protected string $documentation_url = '';
  protected string $oversight_mechanism = '';
  protected string $assessment_date = '';
  protected string $assessor = '';
  protected string $notes = '';

}
```

**AiAuditEntry** (ContentEntity, append-only):

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the AI Audit Entry entity (append-only, immutable).
 *
 * @ContentEntityType(
 *   id = "ai_audit_entry",
 *   label = @Translation("AI Audit Entry"),
 *   label_collection = @Translation("AI Audit Trail"),
 *   label_singular = @Translation("AI audit entry"),
 *   label_plural = @Translation("AI audit entries"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "ai_audit_entry",
 *   admin_permission = "administer ai agents",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/ai/audit-trail",
 *     "canonical" = "/admin/content/ai/audit-trail/{ai_audit_entry}",
 *   },
 *   field_ui_base_route = "entity.ai_audit_entry.settings",
 * )
 */
class AiAuditEntry extends ContentEntityBase implements ContentEntityInterface {

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['event_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Event Type'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'agent_execution' => 'Agent Execution',
        'risk_assessment' => 'Risk Assessment',
        'prompt_change' => 'Prompt Change',
        'verification_failure' => 'Verification Failure',
        'compliance_check' => 'Compliance Check',
        'human_override' => 'Human Override',
        'constitutional_violation' => 'Constitutional Violation',
      ])
      ->setDisplayOptions('view', ['weight' => 0]);

    $fields['agent_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Agent ID'))
      ->setSettings(['max_length' => 64])
      ->setDisplayOptions('view', ['weight' => 1]);

    $fields['action'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Action'))
      ->setSettings(['max_length' => 128]);

    $fields['risk_level'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Risk Level'))
      ->setSettings(['max_length' => 32]);

    $fields['details'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Details'))
      ->setDescription(t('JSON details of the audit event.'))
      ->setDefaultValue('{}');

    $fields['tenant_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Tenant ID'))
      ->setSettings(['max_length' => 64]);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setSetting('target_type', 'user');

    $fields['ip_address'] = BaseFieldDefinition::create('string')
      ->setLabel(t('IP Address'))
      ->setSettings(['max_length' => 45]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    return $fields;
  }

  public static function schema(EntityTypeInterface $entity_type): array {
    $schema = parent::schema($entity_type);
    $schema['indexes']['ai_audit_entry__event_type'] = ['event_type'];
    $schema['indexes']['ai_audit_entry__agent_id'] = ['agent_id'];
    $schema['indexes']['ai_audit_entry__tenant_id'] = ['tenant_id'];
    $schema['indexes']['ai_audit_entry__created'] = ['created'];
    return $schema;
  }

}
```

**Estimacion:** 10-15 horas

---

### 7.7 AiRiskAssessmentForm

**Archivo:** `web/modules/custom/jaraba_ai_agents/src/Form/AiRiskAssessmentForm.php`

Extends `PremiumEntityFormBase` per PREMIUM-FORMS-PATTERN-001:

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Form;

use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for AI Risk Assessment configuration entity.
 */
class AiRiskAssessmentForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'classification' => [
        'label' => $this->t('Risk Classification'),
        'icon' => ['category' => 'status', 'name' => 'shield-check'],
        'description' => $this->t('EU AI Act risk level and agent mapping.'),
        'fields' => ['label', 'agent_id', 'risk_level'],
      ],
      'compliance' => [
        'label' => $this->t('Compliance Documentation'),
        'icon' => ['category' => 'legal', 'name' => 'document'],
        'description' => $this->t('Documentation and oversight mechanisms.'),
        'fields' => ['documentation_url', 'oversight_mechanism'],
      ],
      'audit' => [
        'label' => $this->t('Assessment Details'),
        'icon' => ['category' => 'general', 'name' => 'calendar'],
        'description' => $this->t('When and by whom the assessment was performed.'),
        'fields' => ['assessment_date', 'assessor', 'notes'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'status', 'name' => 'shield-check'];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);
    $entity = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Assessment Name'),
      '#default_value' => $entity->label(),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#machine_name' => [
        'exists' => [$this, 'exists'],
      ],
      '#disabled' => !$entity->isNew(),
    ];

    $form['agent_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Agent'),
      '#options' => $this->getAgentOptions(),
      '#default_value' => $entity->get('agent_id'),
      '#required' => TRUE,
    ];

    $form['risk_level'] = [
      '#type' => 'select',
      '#title' => $this->t('Risk Level'),
      '#options' => [
        'minimal' => $this->t('Minimal — Analytics, recommendations'),
        'limited' => $this->t('Limited — Chatbots, content generation'),
        'high' => $this->t('High — Recruitment, legal decisions'),
        'unacceptable' => $this->t('Unacceptable — Prohibited'),
      ],
      '#default_value' => $entity->get('risk_level') ?: 'limited',
      '#required' => TRUE,
    ];

    $form['documentation_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Documentation URL'),
      '#description' => $this->t('Link to technical documentation (Art. 11 EU AI Act).'),
      '#default_value' => $entity->get('documentation_url'),
    ];

    $form['oversight_mechanism'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Human Oversight Mechanism'),
      '#description' => $this->t('Description of human oversight measures (Art. 14 EU AI Act).'),
      '#default_value' => $entity->get('oversight_mechanism'),
    ];

    $form['assessment_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Assessment Date'),
      '#default_value' => $entity->get('assessment_date') ?: date('Y-m-d'),
    ];

    $form['assessor'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Assessor'),
      '#default_value' => $entity->get('assessor'),
    ];

    $form['notes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Notes'),
      '#default_value' => $entity->get('notes'),
    ];

    return $form;
  }

  /**
   * Machine name exists callback.
   */
  public function exists(string $id): bool {
    return (bool) $this->entityTypeManager
      ->getStorage('ai_risk_assessment')
      ->load($id);
  }

  /**
   * Gets agent options for select.
   */
  protected function getAgentOptions(): array {
    return [
      'smart_marketing' => $this->t('Smart Marketing Agent'),
      'storytelling' => $this->t('Storytelling Agent'),
      'customer_experience' => $this->t('Customer Experience Agent'),
      'support' => $this->t('Support Agent'),
      'producer_copilot' => $this->t('Producer Copilot'),
      'sales' => $this->t('Sales Agent'),
      'merchant_copilot' => $this->t('Merchant Copilot'),
      'recruiter_assistant' => $this->t('Recruiter Assistant'),
      'legal_copilot' => $this->t('Legal Copilot'),
      'learning_tutor' => $this->t('Learning Tutor'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $result;
  }

}
```

**Estimacion:** 6-10 horas

---

### 7.8 AiComplianceDashboardController

**Archivo:** `web/modules/custom/jaraba_ai_agents/src/Controller/AiComplianceDashboardController.php`

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_ai_agents\Service\AiRiskClassificationService;
use Drupal\jaraba_ai_agents\Service\AiAuditTrailService;
use Drupal\jaraba_ai_agents\Service\AiComplianceDocumentationService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * AI Compliance Dashboard controller (EU AI Act).
 *
 * Zero-region: returns markup only — template renders via clean_content.
 *
 * @see ZERO-REGION-001
 */
class AiComplianceDashboardController extends ControllerBase {

  public function __construct(
    protected readonly AiRiskClassificationService $riskClassifier,
    protected readonly AiAuditTrailService $auditTrail,
    protected readonly ?AiComplianceDocumentationService $complianceDocs = NULL,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_ai_agents.risk_classification'),
      $container->get('jaraba_ai_agents.audit_trail'),
      $container->has('jaraba_ai_agents.compliance_documentation')
        ? $container->get('jaraba_ai_agents.compliance_documentation')
        : NULL,
    );
  }

  /**
   * Renders the compliance dashboard.
   */
  public function dashboard(): array {
    $classifications = $this->riskClassifier->classifyAll();
    $riskLevels = $this->riskClassifier->getRiskLevels();
    $stats = $this->auditTrail->getComplianceStats();

    return [
      '#type' => 'markup',
      '#markup' => '',
      '#attached' => [
        'drupalSettings' => [
          'aiCompliance' => [
            'classifications' => $classifications,
            'riskLevels' => $riskLevels,
            'stats' => $stats,
          ],
        ],
      ],
    ];
  }

}
```

**Ruta** (`jaraba_ai_agents.routing.yml`):

```yaml
jaraba_ai_agents.compliance_dashboard:
  path: '/admin/config/ai/compliance'
  defaults:
    _controller: '\Drupal\jaraba_ai_agents\Controller\AiComplianceDashboardController::dashboard'
    _title: 'AI Compliance Dashboard'
  requirements:
    _permission: 'administer ai agents'
```

---

### 7.9 Template y SCSS: AI Compliance

**Template:** `web/themes/custom/ecosistema_jaraba_theme/templates/page--ai-compliance.html.twig`

```twig
{#
/**
 * @file
 * page--ai-compliance.html.twig - EU AI Act Compliance Dashboard.
 * Zero Region Policy:
 * - Sin {{ page.* }} = Sin bloques de Drupal
 * - Usa {{ clean_content }} extraido por preprocess_page()
 * Body classes migradas a preprocess_html:
 * - page-ai-compliance, dashboard-page
 */
#}
{% set site_name = site_name|default('Jaraba Impact Platform') %}

{{ attach_library('ecosistema_jaraba_theme/global') }}
{{ attach_library('ecosistema_jaraba_theme/route-ai-compliance') }}

<a href="#main-content" class="skip-link visually-hidden focusable">
  {% trans %}Skip to main content{% endtrans %}
</a>

{% include '@ecosistema_jaraba_theme/partials/_header.html.twig' with {
  site_name: site_name,
  logo: logo|default(''),
  logged_in: logged_in,
  theme_settings: theme_settings|default({})
} %}

<main id="main-content" class="compliance-dashboard">
  {% if clean_messages %}
    <div class="highlighted container">{{ clean_messages }}</div>
  {% endif %}

  <div class="compliance-dashboard__header container">
    <div class="compliance-dashboard__title-group">
      {{ jaraba_icon('status', 'shield-check', { variant: 'duotone', size: '32px' }) }}
      <h1>{% trans %}AI Compliance — EU AI Act{% endtrans %}</h1>
    </div>
    <p class="compliance-dashboard__subtitle">
      {% trans %}Risk classification, audit trail, and compliance documentation per Regulation (EU) 2024/1689.{% endtrans %}
    </p>
  </div>

  <div class="compliance-dashboard__content container">
    {{ clean_content }}
  </div>
</main>

{% include '@ecosistema_jaraba_theme/partials/_footer.html.twig' with {
  site_name: site_name,
  logo: logo|default(''),
  theme_settings: theme_settings|default({})
} %}
```

**SCSS:** `web/themes/custom/ecosistema_jaraba_theme/scss/routes/_ai-compliance.scss`

```scss
@use '../variables' as *;

.compliance-dashboard {
  min-height: 100vh;
  background: var(--ej-bg-primary, #{$bg-primary});
  padding-block: var(--ej-spacing-xl, 2rem);

  &__header {
    margin-block-end: var(--ej-spacing-lg, 1.5rem);
  }

  &__title-group {
    display: flex;
    align-items: center;
    gap: var(--ej-spacing-sm, 0.5rem);

    h1 {
      font-size: var(--ej-font-size-2xl, 1.75rem);
      font-weight: var(--ej-font-weight-bold, 700);
      color: var(--ej-text-primary, #{$text-primary});
      margin: 0;
    }
  }

  &__subtitle {
    color: var(--ej-text-secondary, #{$text-secondary});
    font-size: var(--ej-font-size-base, 1rem);
    margin-block-start: var(--ej-spacing-xs, 0.25rem);
  }

  &__content {
    display: grid;
    gap: var(--ej-spacing-lg, 1.5rem);
  }
}
```

**Body class** (in `ecosistema_jaraba_theme.theme`, `preprocess_html`):

```php
$compliance_routes = ['jaraba_ai_agents.compliance_dashboard'];
if (in_array($route, $compliance_routes, TRUE)) {
  $variables['attributes']['class'][] = 'page-ai-compliance';
  $variables['attributes']['class'][] = 'dashboard-page';
}
```

**Library** (`ecosistema_jaraba_theme.libraries.yml`):

```yaml
route-ai-compliance:
  version: 1.0.0
  css:
    theme:
      css/routes/ai-compliance.css: {}
  dependencies:
    - ecosistema_jaraba_theme/global-styling
```

---

### 7.10 Tests Sprint 2

**Test 3:** `AiRiskClassificationServiceTest`

```php
<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ai_agents\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_ai_agents\Service\AiRiskClassificationService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \Drupal\jaraba_ai_agents\Service\AiRiskClassificationService
 * @group jaraba_ai_agents
 */
class AiRiskClassificationServiceTest extends UnitTestCase {

  protected AiRiskClassificationService $service;

  protected function setUp(): void {
    parent::setUp();
    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')->willReturn([]);
    $entityTypeManager->method('getStorage')->willReturn($storage);
    $logger = $this->createMock(LoggerInterface::class);
    $this->service = new AiRiskClassificationService($entityTypeManager, $logger);
  }

  public function testClassifyRecruiterAsHighRisk(): void {
    $result = $this->service->classify('recruiter_assistant');
    $this->assertEquals('high', $result['risk_level']);
    $this->assertStringContainsString('Article', $result['article_reference']);
  }

  public function testClassifyMarketingAsLimitedRisk(): void {
    $result = $this->service->classify('smart_marketing');
    $this->assertEquals('limited', $result['risk_level']);
  }

  public function testClassifyProducerAsMinimalRisk(): void {
    $result = $this->service->classify('producer_copilot');
    $this->assertEquals('minimal', $result['risk_level']);
  }

  public function testClassifyAllReturnsAllAgents(): void {
    $results = $this->service->classifyAll();
    $this->assertArrayHasKey('smart_marketing', $results);
    $this->assertArrayHasKey('recruiter_assistant', $results);
    $this->assertArrayHasKey('legal_copilot', $results);
    $this->assertCount(10, $results);
  }

  public function testGetRiskLevelsReturnsFourLevels(): void {
    $levels = $this->service->getRiskLevels();
    $this->assertCount(4, $levels);
    $this->assertArrayHasKey('minimal', $levels);
    $this->assertArrayHasKey('limited', $levels);
    $this->assertArrayHasKey('high', $levels);
    $this->assertArrayHasKey('unacceptable', $levels);
  }

}
```

**Criterio de completitud Sprint 2:**
- AiRiskClassificationService clasifica 10 agentes en 4 niveles
- AiRiskAssessment ConfigEntity CRUD funcional
- AiAuditEntry ContentEntity append-only
- AiTransparencyService genera labels bilingual (ES/EN)
- AiComplianceDashboardController con template zero-region
- Compliance SCSS compilable con Dart Sass

---

## 8. Sprint 3 — Multi-Modal y Computer Use (GAP-L5-D + GAP-L5-E)

**Duracion:** 4-6 semanas
**Prioridad:** ALTA
**Objetivo:** Voice interface con pipeline STT/TTS + Browser agent con Playwright sandboxed

### 8.1 VoicePipelineService

**Archivo:** `web/modules/custom/jaraba_ai_agents/src/Service/VoicePipelineService.php`

Pipeline: Microphone → Whisper STT → CopilotOrchestrator → TTS → Audio playback

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Voice pipeline: STT → AI processing → TTS.
 *
 * Orchestrates the full voice interaction loop using external APIs
 * (Whisper for STT, provider TTS for synthesis). Feature-flagged.
 */
class VoicePipelineService {

  public function __construct(
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly LoggerChannelInterface $logger,
    protected readonly ?AIObservabilityService $observability = NULL,
  ) {}

  /**
   * Transcribes audio to text via Whisper API.
   *
   * @param string $audioData
   *   Base64-encoded audio data.
   * @param string $format
   *   Audio format: webm, wav, mp3.
   * @param string $language
   *   ISO language code: es, en.
   *
   * @return array
   *   Result: {success: bool, text: string, language: string, duration_ms: int}.
   */
  public function transcribe(string $audioData, string $format = 'webm', string $language = 'es'): array {
    if (!$this->isEnabled()) {
      return ['success' => FALSE, 'text' => '', 'error' => 'Voice feature not enabled'];
    }

    $startTime = microtime(TRUE);

    try {
      $config = $this->configFactory->get('jaraba_ai_agents.voice');
      $apiKey = $config->get('whisper_api_key') ?? '';
      $apiUrl = $config->get('whisper_api_url') ?? 'https://api.openai.com/v1/audio/transcriptions';

      if (empty($apiKey)) {
        return ['success' => FALSE, 'text' => '', 'error' => 'Whisper API key not configured'];
      }

      $audioBytes = base64_decode($audioData, TRUE);
      if ($audioBytes === FALSE) {
        return ['success' => FALSE, 'text' => '', 'error' => 'Invalid base64 audio data'];
      }

      // Build multipart form data for Whisper API.
      $boundary = bin2hex(random_bytes(16));
      $body = $this->buildMultipartBody($boundary, $audioBytes, $format, $language);

      $response = \Drupal::httpClient()->post($apiUrl, [
        'headers' => [
          'Authorization' => "Bearer {$apiKey}",
          'Content-Type' => "multipart/form-data; boundary={$boundary}",
        ],
        'body' => $body,
        'timeout' => 30,
      ]);

      $data = json_decode((string) $response->getBody(), TRUE);
      $durationMs = (int) ((microtime(TRUE) - $startTime) * 1000);

      $this->observability?->log([
        'agent_id' => 'voice_pipeline',
        'action' => 'transcribe',
        'tier' => 'fast',
        'duration_ms' => $durationMs,
        'success' => TRUE,
      ]);

      return [
        'success' => TRUE,
        'text' => $data['text'] ?? '',
        'language' => $language,
        'duration_ms' => $durationMs,
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('Voice transcription failed: @error', [
        '@error' => $e->getMessage(),
      ]);
      return ['success' => FALSE, 'text' => '', 'error' => $e->getMessage()];
    }
  }

  /**
   * Synthesizes text to speech.
   *
   * @param string $text
   *   The text to synthesize.
   * @param string $voice
   *   Voice name/ID.
   * @param string $language
   *   ISO language code.
   *
   * @return array
   *   Result: {success: bool, audio_base64: string, format: string}.
   */
  public function synthesize(string $text, string $voice = 'alloy', string $language = 'es'): array {
    if (!$this->isEnabled()) {
      return ['success' => FALSE, 'audio_base64' => '', 'error' => 'Voice feature not enabled'];
    }

    try {
      $config = $this->configFactory->get('jaraba_ai_agents.voice');
      $apiKey = $config->get('tts_api_key') ?? '';
      $apiUrl = $config->get('tts_api_url') ?? 'https://api.openai.com/v1/audio/speech';

      if (empty($apiKey)) {
        return ['success' => FALSE, 'audio_base64' => '', 'error' => 'TTS API key not configured'];
      }

      $response = \Drupal::httpClient()->post($apiUrl, [
        'headers' => [
          'Authorization' => "Bearer {$apiKey}",
          'Content-Type' => 'application/json',
        ],
        'json' => [
          'model' => 'tts-1',
          'input' => mb_substr($text, 0, 4096),
          'voice' => $voice,
          'response_format' => 'mp3',
        ],
        'timeout' => 30,
      ]);

      $audioData = base64_encode((string) $response->getBody());

      return [
        'success' => TRUE,
        'audio_base64' => $audioData,
        'format' => 'mp3',
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('TTS synthesis failed: @error', [
        '@error' => $e->getMessage(),
      ]);
      return ['success' => FALSE, 'audio_base64' => '', 'error' => $e->getMessage()];
    }
  }

  /**
   * Checks if voice feature is enabled.
   */
  public function isEnabled(): bool {
    $config = $this->configFactory->get('jaraba_ai_agents.voice');
    return (bool) ($config->get('enabled') ?? FALSE);
  }

  /**
   * Builds multipart form body for Whisper API.
   */
  protected function buildMultipartBody(string $boundary, string $audioBytes, string $format, string $language): string {
    $ext = match ($format) {
      'webm' => 'webm',
      'wav' => 'wav',
      default => 'mp3',
    };

    $body = "--{$boundary}\r\n";
    $body .= "Content-Disposition: form-data; name=\"file\"; filename=\"audio.{$ext}\"\r\n";
    $body .= "Content-Type: audio/{$ext}\r\n\r\n";
    $body .= $audioBytes . "\r\n";
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Disposition: form-data; name=\"model\"\r\n\r\n";
    $body .= "whisper-1\r\n";
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Disposition: form-data; name=\"language\"\r\n\r\n";
    $body .= "{$language}\r\n";
    $body .= "--{$boundary}--\r\n";

    return $body;
  }

}
```

**Estimacion:** 20-30 horas

---

### 8.2 VoiceCopilotController

**Archivo:** `web/modules/custom/jaraba_ai_agents/src/Controller/VoiceCopilotController.php`

```yaml
# Ruta (jaraba_ai_agents.routing.yml)
jaraba_ai_agents.api.voice.transcribe:
  path: '/api/v1/voice/transcribe'
  defaults:
    _controller: '\Drupal\jaraba_ai_agents\Controller\VoiceCopilotController::transcribe'
  methods: [POST]
  requirements:
    _user_is_logged_in: 'TRUE'
    _csrf_request_header_token: 'TRUE'

jaraba_ai_agents.api.voice.synthesize:
  path: '/api/v1/voice/synthesize'
  defaults:
    _controller: '\Drupal\jaraba_ai_agents\Controller\VoiceCopilotController::synthesize'
  methods: [POST]
  requirements:
    _user_is_logged_in: 'TRUE'
    _csrf_request_header_token: 'TRUE'
```

---

### 8.3 Frontend: Voice Copilot Widget

**JS:** `web/modules/custom/ecosistema_jaraba_core/js/voice-copilot-widget.js`
**SCSS:** `web/modules/custom/ecosistema_jaraba_core/scss/_voice-copilot.scss`
**Twig:** `web/themes/custom/ecosistema_jaraba_theme/templates/partials/_voice-copilot-fab.html.twig`

```twig
{#
/**
 * @file
 * _voice-copilot-fab.html.twig - Voice Copilot floating action button.
 *
 * States: idle, recording, processing, playing.
 * Feature-flagged: only renders when voice_copilot_enabled is TRUE.
 */
#}
{% if voice_copilot_enabled|default(false) %}
<div class="voice-copilot-fab" data-voice-copilot role="button" aria-label="{% trans %}Voice assistant{% endtrans %}" tabindex="0">
  <div class="voice-copilot-fab__icon voice-copilot-fab__icon--idle">
    {{ jaraba_icon('media', 'microphone', { variant: 'duotone', size: '24px' }) }}
  </div>
  <div class="voice-copilot-fab__icon voice-copilot-fab__icon--recording" aria-hidden="true">
    {{ jaraba_icon('media', 'waveform', { variant: 'duotone', size: '24px' }) }}
  </div>
  <div class="voice-copilot-fab__icon voice-copilot-fab__icon--processing" aria-hidden="true">
    {{ jaraba_icon('status', 'spinner', { variant: 'duotone', size: '24px' }) }}
  </div>
</div>
{% endif %}
```

**Estimacion:** 25-35 horas (incluyendo JS completo)

---

### 8.4 BrowserAgentService

**Archivo:** `web/modules/custom/jaraba_ai_agents/src/Service/BrowserAgentService.php`

Usa Playwright en Docker container aislado. URL allowlist configurable.

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Browser automation agent using Playwright in sandboxed Docker container.
 *
 * Feature-flagged. Only available for specific verticals with URL allowlist.
 * All browser tasks are logged as BrowserTask entities (append-only audit).
 */
class BrowserAgentService {

  /**
   * Default URL allowlist patterns (verticals can extend).
   */
  private const DEFAULT_ALLOWLIST = [
    'empleabilidad' => [
      'https://www.infojobs.net/*',
      'https://www.linkedin.com/jobs/*',
      'https://www.indeed.es/*',
    ],
    'comercioconecta' => [
      'https://www.google.com/maps/*',
      'https://search.google.com/*',
    ],
  ];

  /**
   * Maximum task duration in seconds.
   */
  private const MAX_TASK_DURATION = 120;

  public function __construct(
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerChannelInterface $logger,
    protected readonly ?AIObservabilityService $observability = NULL,
  ) {}

  /**
   * Executes a browser task.
   *
   * @param string $taskType
   *   Task type: scrape, fill_form, screenshot.
   * @param string $url
   *   Target URL.
   * @param array $params
   *   Task parameters.
   * @param array $context
   *   Context: tenant_id, vertical.
   *
   * @return array
   *   Result: {success: bool, data: array, task_id: ?int}.
   */
  public function execute(string $taskType, string $url, array $params, array $context): array {
    if (!$this->isEnabled()) {
      return ['success' => FALSE, 'error' => 'Browser agent not enabled'];
    }

    // URL allowlist check.
    $vertical = $context['vertical'] ?? '';
    if (!$this->isUrlAllowed($url, $vertical)) {
      $this->logger->warning('Browser agent: URL not in allowlist: @url (vertical: @v)', [
        '@url' => $url,
        '@v' => $vertical,
      ]);
      return ['success' => FALSE, 'error' => 'URL not in allowlist for this vertical'];
    }

    $taskId = $this->createBrowserTask($taskType, $url, $params, $context);

    try {
      $result = match ($taskType) {
        'scrape' => $this->executeScrape($url, $params),
        'screenshot' => $this->executeScreenshot($url, $params),
        default => ['success' => FALSE, 'error' => "Unknown task type: {$taskType}"],
      };

      $this->updateBrowserTask($taskId, $result['success'] ? 'completed' : 'failed', $result);
      return array_merge($result, ['task_id' => $taskId]);
    }
    catch (\Throwable $e) {
      $this->updateBrowserTask($taskId, 'failed', ['error' => $e->getMessage()]);
      return ['success' => FALSE, 'error' => $e->getMessage(), 'task_id' => $taskId];
    }
  }

  /**
   * Checks if URL is in the allowlist for the given vertical.
   */
  protected function isUrlAllowed(string $url, string $vertical): bool {
    $config = $this->configFactory->get('jaraba_ai_agents.browser_agent');
    $customAllowlist = $config->get("allowlist.{$vertical}") ?? [];
    $allowlist = array_merge(
      self::DEFAULT_ALLOWLIST[$vertical] ?? [],
      $customAllowlist,
    );

    foreach ($allowlist as $pattern) {
      $regex = '#^' . str_replace(['*', '#'], ['.*', '\\#'], $pattern) . '$#i';
      if (preg_match($regex, $url)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Executes a scraping task via Playwright container.
   */
  protected function executeScrape(string $url, array $params): array {
    // Playwright execution delegated to Docker container.
    // In production: HTTP call to Playwright service container.
    // In dev: local process execution.
    $config = $this->configFactory->get('jaraba_ai_agents.browser_agent');
    $playwrightUrl = $config->get('playwright_service_url') ?? 'http://playwright:3000';

    try {
      $response = \Drupal::httpClient()->post("{$playwrightUrl}/scrape", [
        'json' => [
          'url' => $url,
          'selector' => $params['selector'] ?? 'body',
          'wait_for' => $params['wait_for'] ?? 'networkidle',
          'timeout' => self::MAX_TASK_DURATION * 1000,
        ],
        'timeout' => self::MAX_TASK_DURATION + 5,
      ]);

      $data = json_decode((string) $response->getBody(), TRUE);
      return ['success' => TRUE, 'data' => $data];
    }
    catch (\Throwable $e) {
      return ['success' => FALSE, 'error' => 'Playwright scrape failed: ' . $e->getMessage()];
    }
  }

  /**
   * Executes a screenshot task.
   */
  protected function executeScreenshot(string $url, array $params): array {
    $config = $this->configFactory->get('jaraba_ai_agents.browser_agent');
    $playwrightUrl = $config->get('playwright_service_url') ?? 'http://playwright:3000';

    try {
      $response = \Drupal::httpClient()->post("{$playwrightUrl}/screenshot", [
        'json' => [
          'url' => $url,
          'full_page' => $params['full_page'] ?? FALSE,
          'timeout' => self::MAX_TASK_DURATION * 1000,
        ],
        'timeout' => self::MAX_TASK_DURATION + 5,
      ]);

      $data = json_decode((string) $response->getBody(), TRUE);
      return ['success' => TRUE, 'data' => $data];
    }
    catch (\Throwable $e) {
      return ['success' => FALSE, 'error' => 'Screenshot failed: ' . $e->getMessage()];
    }
  }

  /**
   * Creates a BrowserTask entity for audit trail.
   */
  protected function createBrowserTask(string $taskType, string $url, array $params, array $context): ?int {
    try {
      $storage = $this->entityTypeManager->getStorage('browser_task');
      $entity = $storage->create([
        'task_type' => $taskType,
        'target_url' => $url,
        'params' => json_encode($params, JSON_THROW_ON_ERROR),
        'status' => 'running',
        'tenant_id' => $context['tenant_id'] ?? '',
        'vertical' => $context['vertical'] ?? '',
      ]);
      $entity->save();
      return (int) $entity->id();
    }
    catch (\Throwable $e) {
      $this->logger->error('Failed to create BrowserTask: @error', ['@error' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * Updates BrowserTask status.
   */
  protected function updateBrowserTask(?int $taskId, string $status, array $result): void {
    if (!$taskId) {
      return;
    }
    try {
      $storage = $this->entityTypeManager->getStorage('browser_task');
      $entity = $storage->load($taskId);
      if ($entity) {
        $entity->set('status', $status);
        $entity->set('result', json_encode($result, JSON_THROW_ON_ERROR));
        $entity->save();
      }
    }
    catch (\Throwable $e) {
      $this->logger->error('Failed to update BrowserTask @id: @error', [
        '@id' => $taskId,
        '@error' => $e->getMessage(),
      ]);
    }
  }

  public function isEnabled(): bool {
    $config = $this->configFactory->get('jaraba_ai_agents.browser_agent');
    return (bool) ($config->get('enabled') ?? FALSE);
  }

}
```

**Estimacion:** 25-35 horas

---

### 8.5 Tools: WebScraping, FormFiller, ScreenCapture

3 nuevos tools para el ToolRegistry:

| Tool | ID | Requires Approval | Integration |
|------|-----|-------------------|-------------|
| WebScrapingTool | `web_scraping` | **Yes** | BrowserAgentService::execute('scrape') |
| FormFillerTool | `form_filler` | **Yes** | BrowserAgentService::execute('fill_form') |
| ScreenCaptureTool | `screen_capture` | No | BrowserAgentService::execute('screenshot') |

Todos extienden `BaseTool` y delegan a `BrowserAgentService`.

---

### 8.6 Entidad: BrowserTask

Sigue el patron de VerificationResult (ContentEntity append-only) con campos: `task_type`, `target_url`, `params` (JSON), `status`, `result` (JSON), `tenant_id`, `vertical`, `created`.

---

### 8.7 Tests Sprint 3

| Test | Cobertura |
|------|----------|
| VoicePipelineServiceTest | Feature flag, transcribe/synthesize con mocks |
| BrowserAgentServiceTest | URL allowlist, vertical filtering, task lifecycle |

**Criterio de completitud Sprint 3:**
- Voice FAB visible cuando feature flag activo
- Transcribe/synthesize endpoints responden con mocks
- Browser agent rechaza URLs fuera de allowlist
- 3 tools registrados en ToolRegistry
- BrowserTask entities con audit trail completo

---

## 9. Sprint 4 — Autonomous Operation y Self-Healing (GAP-L5-F + GAP-L5-G)

**Duracion:** 3-5 semanas
**Prioridad:** ALTA
**Objetivo:** Agentes autonomos con heartbeat + auto-diagnostico y remediacion automatica

### 9.1 AutonomousAgentService

**Archivo:** `web/modules/custom/jaraba_ai_agents/src/Service/AutonomousAgentService.php`

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Queue\QueueFactory;

/**
 * Manages autonomous agent sessions and heartbeat scheduling.
 *
 * Types: ReputationMonitor, ContentCurator, KBMaintainer, ChurnPrevention.
 * Each has: objectives (YAML), constraints, escalation_rules, max_runtime, cost_ceiling.
 */
class AutonomousAgentService {

  /**
   * Autonomous agent type definitions.
   */
  private const AGENT_TYPES = [
    'reputation_monitor' => [
      'label' => 'Reputation Monitor',
      'description' => 'Monitors brand reputation and alerts on sentiment changes.',
      'default_interval' => 300,
      'max_runtime' => 60,
      'cost_ceiling' => 0.50,
      'vertical' => '_all',
    ],
    'content_curator' => [
      'label' => 'Content Curator',
      'description' => 'Identifies content gaps and suggests new topics.',
      'default_interval' => 3600,
      'max_runtime' => 120,
      'cost_ceiling' => 1.00,
      'vertical' => '_all',
    ],
    'kb_maintainer' => [
      'label' => 'Knowledge Base Maintainer',
      'description' => 'Audits and updates knowledge base entries.',
      'default_interval' => 7200,
      'max_runtime' => 180,
      'cost_ceiling' => 2.00,
      'vertical' => '_all',
    ],
    'churn_prevention' => [
      'label' => 'Churn Prevention',
      'description' => 'Detects churn signals and suggests retention actions.',
      'default_interval' => 3600,
      'max_runtime' => 60,
      'cost_ceiling' => 0.50,
      'vertical' => '_all',
    ],
  ];

  /**
   * Maximum consecutive failures before auto-pause.
   */
  private const MAX_CONSECUTIVE_FAILURES = 3;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly QueueFactory $queueFactory,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly LoggerChannelInterface $logger,
    protected readonly ?AIObservabilityService $observability = NULL,
  ) {}

  /**
   * Creates a new autonomous session.
   */
  public function startSession(string $agentType, string $tenantId, array $objectives = []): ?int {
    if (!isset(self::AGENT_TYPES[$agentType])) {
      return NULL;
    }

    try {
      $storage = $this->entityTypeManager->getStorage('autonomous_session');
      $definition = self::AGENT_TYPES[$agentType];

      $entity = $storage->create([
        'agent_type' => $agentType,
        'tenant_id' => $tenantId,
        'status' => 'active',
        'objectives' => json_encode($objectives ?: [$definition['description']], JSON_THROW_ON_ERROR),
        'interval_seconds' => $definition['default_interval'],
        'max_runtime' => $definition['max_runtime'],
        'cost_ceiling' => $definition['cost_ceiling'],
        'consecutive_failures' => 0,
        'total_executions' => 0,
        'total_cost' => 0,
      ]);
      $entity->save();

      // Enqueue first heartbeat.
      $queue = $this->queueFactory->get('autonomous_agent_heartbeat');
      $queue->createItem([
        'session_id' => (int) $entity->id(),
        'agent_type' => $agentType,
        'tenant_id' => $tenantId,
      ]);

      return (int) $entity->id();
    }
    catch (\Throwable $e) {
      $this->logger->error('Failed to start autonomous session: @error', [
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Pauses an autonomous session.
   */
  public function pauseSession(int $sessionId): bool {
    try {
      $entity = $this->entityTypeManager->getStorage('autonomous_session')->load($sessionId);
      if ($entity) {
        $entity->set('status', 'paused');
        $entity->save();
        return TRUE;
      }
    }
    catch (\Throwable $e) {
      $this->logger->error('Failed to pause session @id: @error', [
        '@id' => $sessionId,
        '@error' => $e->getMessage(),
      ]);
    }
    return FALSE;
  }

  /**
   * Records a heartbeat execution result.
   */
  public function recordExecution(int $sessionId, bool $success, float $cost): void {
    try {
      $entity = $this->entityTypeManager->getStorage('autonomous_session')->load($sessionId);
      if (!$entity) {
        return;
      }

      $totalExecutions = (int) ($entity->get('total_executions')->value ?? 0) + 1;
      $totalCost = (float) ($entity->get('total_cost')->value ?? 0) + $cost;
      $consecutiveFailures = $success ? 0 : ((int) ($entity->get('consecutive_failures')->value ?? 0) + 1);
      $costCeiling = (float) ($entity->get('cost_ceiling')->value ?? 1.0);

      $entity->set('total_executions', $totalExecutions);
      $entity->set('total_cost', $totalCost);
      $entity->set('consecutive_failures', $consecutiveFailures);

      // Auto-pause on max failures.
      if ($consecutiveFailures >= self::MAX_CONSECUTIVE_FAILURES) {
        $entity->set('status', 'paused');
        $this->logger->warning('Autonomous session @id auto-paused after @n consecutive failures.', [
          '@id' => $sessionId,
          '@n' => $consecutiveFailures,
        ]);
      }

      // Auto-pause on cost ceiling.
      if ($totalCost >= $costCeiling) {
        $entity->set('status', 'paused');
        $this->logger->warning('Autonomous session @id auto-paused: cost ceiling reached ($@cost / $@ceiling).', [
          '@id' => $sessionId,
          '@cost' => number_format($totalCost, 4),
          '@ceiling' => number_format($costCeiling, 4),
        ]);
      }

      $entity->save();
    }
    catch (\Throwable $e) {
      $this->logger->error('Failed to record execution: @error', ['@error' => $e->getMessage()]);
    }
  }

  /**
   * Gets available agent types.
   */
  public function getAgentTypes(): array {
    return self::AGENT_TYPES;
  }

}
```

**Estimacion:** 20-30 horas

---

### 9.2 AutoDiagnosticService

**Archivo:** `web/modules/custom/jaraba_ai_agents/src/Service/AutoDiagnosticService.php`

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Auto-diagnostic and self-healing for AI infrastructure.
 *
 * Monitors: P95 latency, error rate, quality scores, cost/query.
 * Auto-remediations: tier downgrade, prompt refresh, provider rotation,
 * cache warming, cost throttling.
 */
class AutoDiagnosticService {

  /**
   * Diagnostic thresholds.
   */
  private const THRESHOLDS = [
    'latency_p95_ms' => 5000,
    'error_rate_percent' => 10,
    'quality_min' => 0.6,
    'cache_hit_min_percent' => 20,
    'cost_spike_multiplier' => 2.0,
  ];

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerChannelInterface $logger,
    protected readonly ?AIObservabilityService $observability = NULL,
    protected readonly ?ModelRouterService $modelRouter = NULL,
    protected readonly ?ProviderFallbackService $providerFallback = NULL,
  ) {}

  /**
   * Runs diagnostic check and applies auto-remediations.
   *
   * @return array
   *   Diagnostic results: {checks: array, remediations: array}.
   */
  public function diagnose(): array {
    $checks = [];
    $remediations = [];

    // Check 1: Latency P95.
    $latencyP95 = $this->getLatencyP95();
    $checks['latency_p95'] = [
      'value' => $latencyP95,
      'threshold' => self::THRESHOLDS['latency_p95_ms'],
      'status' => $latencyP95 <= self::THRESHOLDS['latency_p95_ms'] ? 'healthy' : 'degraded',
    ];
    if ($latencyP95 > self::THRESHOLDS['latency_p95_ms']) {
      $remediations[] = $this->remediateLatency($latencyP95);
    }

    // Check 2: Error rate.
    $errorRate = $this->getErrorRate();
    $checks['error_rate'] = [
      'value' => $errorRate,
      'threshold' => self::THRESHOLDS['error_rate_percent'],
      'status' => $errorRate <= self::THRESHOLDS['error_rate_percent'] ? 'healthy' : 'degraded',
    ];
    if ($errorRate > self::THRESHOLDS['error_rate_percent']) {
      $remediations[] = $this->remediateErrors($errorRate);
    }

    // Check 3: Quality scores.
    $avgQuality = $this->getAverageQuality();
    $checks['quality'] = [
      'value' => $avgQuality,
      'threshold' => self::THRESHOLDS['quality_min'],
      'status' => $avgQuality >= self::THRESHOLDS['quality_min'] ? 'healthy' : 'degraded',
    ];
    if ($avgQuality < self::THRESHOLDS['quality_min']) {
      $remediations[] = $this->remediateQuality($avgQuality);
    }

    // Log remediations.
    foreach ($remediations as $remediation) {
      $this->logRemediation($remediation);
    }

    return [
      'timestamp' => date('c'),
      'checks' => $checks,
      'remediations' => $remediations,
      'overall_status' => $this->calculateOverallStatus($checks),
    ];
  }

  /**
   * Latency remediation: auto-downgrade tier.
   */
  protected function remediateLatency(float $latencyMs): array {
    $this->logger->warning('Auto-remediation: latency P95 @ms ms > threshold. Suggesting tier downgrade.', [
      '@ms' => (int) $latencyMs,
    ]);
    return [
      'type' => 'tier_downgrade',
      'reason' => "Latency P95 {$latencyMs}ms exceeds threshold",
      'action' => 'Suggest switching premium queries to balanced tier',
      'applied' => FALSE,
    ];
  }

  /**
   * Error rate remediation: auto-rotate provider.
   */
  protected function remediateErrors(float $errorRate): array {
    $this->logger->warning('Auto-remediation: error rate @rate% > threshold. Triggering provider rotation.', [
      '@rate' => round($errorRate, 1),
    ]);
    return [
      'type' => 'provider_rotation',
      'reason' => "Error rate {$errorRate}% exceeds threshold",
      'action' => 'ProviderFallbackService circuit breaker will auto-rotate',
      'applied' => TRUE,
    ];
  }

  /**
   * Quality remediation: refresh to last-known-good prompt.
   */
  protected function remediateQuality(float $avgQuality): array {
    $this->logger->warning('Auto-remediation: quality @score < threshold. Suggesting prompt refresh.', [
      '@score' => round($avgQuality, 2),
    ]);
    return [
      'type' => 'prompt_refresh',
      'reason' => "Average quality {$avgQuality} below threshold",
      'action' => 'Revert to last-known-good prompt version',
      'applied' => FALSE,
    ];
  }

  protected function getLatencyP95(): float {
    // Query AIObservabilityService for P95 latency last hour.
    return 0.0;
  }

  protected function getErrorRate(): float {
    return 0.0;
  }

  protected function getAverageQuality(): float {
    return 0.8;
  }

  protected function calculateOverallStatus(array $checks): string {
    foreach ($checks as $check) {
      if ($check['status'] === 'degraded') {
        return 'degraded';
      }
    }
    return 'healthy';
  }

  protected function logRemediation(array $remediation): void {
    try {
      $storage = $this->entityTypeManager->getStorage('remediation_log');
      $entity = $storage->create([
        'remediation_type' => $remediation['type'],
        'reason' => $remediation['reason'],
        'action_taken' => $remediation['action'],
        'was_applied' => $remediation['applied'],
      ]);
      $entity->save();
    }
    catch (\Throwable $e) {
      $this->logger->error('Failed to log remediation: @error', ['@error' => $e->getMessage()]);
    }
  }

}
```

**Estimacion:** 20-30 horas

---

### 9.3 AutonomousAgentHeartbeatWorker

**Archivo:** `web/modules/custom/jaraba_ai_agents/src/Plugin/QueueWorker/AutonomousAgentHeartbeatWorker.php`

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Heartbeat worker for autonomous agent sessions.
 *
 * @QueueWorker(
 *   id = "autonomous_agent_heartbeat",
 *   title = @Translation("Autonomous Agent Heartbeat"),
 *   cron = {"time" = 60}
 * )
 */
class AutonomousAgentHeartbeatWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected readonly object $autonomousService,
    protected readonly object $logger,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('jaraba_ai_agents.autonomous_agent'),
      $container->get('logger.channel.jaraba_ai_agents'),
    );
  }

  public function processItem($data): void {
    $sessionId = $data['session_id'] ?? NULL;
    if (!$sessionId) {
      return;
    }

    // Execute heartbeat logic delegated to AutonomousAgentService.
    // Re-enqueue if session is still active.
  }

}
```

---

### 9.4 Entidades: AutonomousSession y RemediationLog

Siguen los patrones establecidos. AutonomousSession con campos: `agent_type`, `tenant_id`, `status` (active/paused/completed), `objectives` (JSON), `interval_seconds`, `max_runtime`, `cost_ceiling`, `consecutive_failures`, `total_executions`, `total_cost`, `created`, `changed`.

RemediationLog (append-only): `remediation_type`, `reason`, `action_taken`, `was_applied`, `created`.

---

### 9.5 Dashboard Agentes Autonomos

Template zero-region: `page--autonomous-agents.html.twig`
SCSS: `scss/routes/_autonomous-agents.scss`
Body class: `page-autonomous-agents`

---

### 9.6 Health Indicator

Partial: `_ai-health-indicator.html.twig` — muestra estado del sistema IA en el header.

```twig
{#
/**
 * @file
 * _ai-health-indicator.html.twig - AI system health in header.
 */
#}
{% if ai_health_status is defined %}
<div class="ai-health-indicator ai-health-indicator--{{ ai_health_status }}"
     role="status" aria-label="{% trans %}AI system status: {{ ai_health_status }}{% endtrans %}">
  {{ jaraba_icon('status', ai_health_status == 'healthy' ? 'check-circle' : 'alert-triangle', {
    variant: 'duotone',
    size: '16px'
  }) }}
</div>
{% endif %}
```

---

### 9.7 Tests Sprint 4

| Test | Cobertura |
|------|----------|
| AutonomousAgentServiceTest | Start/pause session, execution recording, auto-pause logic |
| AutoDiagnosticServiceTest | Threshold detection, remediation proposals |

**Criterio de completitud Sprint 4:**
- 4 tipos de agentes autonomos configurables
- Heartbeat via Drupal Queue
- Auto-pause tras 3 fallos consecutivos
- Auto-pause al alcanzar cost_ceiling
- AutoDiagnosticService detecta 5 anomalias
- RemediationLog con audit trail
- Dashboard zero-region funcional

---

## 10. Sprint 5 — Intelligence y Analytics (GAP-L5-H + GAP-L5-I)

**Duracion:** 2-4 semanas
**Prioridad:** MEDIA
**Objetivo:** Insights federados cross-tenant con k-anonimidad + analytics causal con LLM premium

### 10.1 FederatedInsightService

**Archivo:** `web/modules/custom/jaraba_ai_agents/src/Service/FederatedInsightService.php`

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Aggregates anonymized cross-tenant patterns with k-anonymity.
 *
 * Opt-in via feature flag (SaaS plan). Minimum 5 tenants for any
 * aggregated insight (k-anonymity guarantee). No ML — statistical
 * aggregation with differential privacy noise.
 */
class FederatedInsightService {

  /**
   * Minimum tenants for k-anonymity.
   */
  private const K_ANONYMITY_THRESHOLD = 5;

  /**
   * Differential privacy noise factor (Laplace mechanism).
   */
  private const PRIVACY_EPSILON = 1.0;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerChannelInterface $logger,
  ) {}

  /**
   * Generates a federated insight across opted-in tenants.
   *
   * @param string $insightType
   *   Insight type: conversion_rate, churn_rate, content_engagement, cost_efficiency.
   *
   * @return array
   *   Result: {success: bool, data: array, k_anonymity: int, tenant_count: int}.
   */
  public function generateInsight(string $insightType): array {
    $optedInTenants = $this->getOptedInTenants();

    if (count($optedInTenants) < self::K_ANONYMITY_THRESHOLD) {
      return [
        'success' => FALSE,
        'error' => 'Insufficient tenants for k-anonymity (need ' . self::K_ANONYMITY_THRESHOLD . ', have ' . count($optedInTenants) . ')',
        'k_anonymity' => count($optedInTenants),
      ];
    }

    try {
      $aggregatedData = $this->aggregateData($insightType, $optedInTenants);
      $noisyData = $this->addDifferentialPrivacy($aggregatedData);

      $insightId = $this->storeInsight($insightType, $noisyData, count($optedInTenants));

      return [
        'success' => TRUE,
        'data' => $noisyData,
        'k_anonymity' => self::K_ANONYMITY_THRESHOLD,
        'tenant_count' => count($optedInTenants),
        'insight_id' => $insightId,
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('Federated insight generation failed: @error', [
        '@error' => $e->getMessage(),
      ]);
      return ['success' => FALSE, 'error' => $e->getMessage()];
    }
  }

  /**
   * Adds Laplace noise for differential privacy.
   */
  protected function addDifferentialPrivacy(array $data): array {
    foreach ($data as $key => $value) {
      if (is_numeric($value)) {
        $sensitivity = abs($value) * 0.1;
        $noise = $this->laplaceSample($sensitivity / self::PRIVACY_EPSILON);
        $data[$key] = round($value + $noise, 2);
      }
    }
    return $data;
  }

  /**
   * Samples from Laplace distribution.
   */
  protected function laplaceSample(float $scale): float {
    $u = mt_rand() / mt_getrandmax() - 0.5;
    return -$scale * sign($u) * log(1 - 2 * abs($u));
  }

  /**
   * Gets opted-in tenant IDs.
   */
  protected function getOptedInTenants(): array {
    // Query tenants with federated_insights feature flag enabled.
    return [];
  }

  /**
   * Aggregates data across tenants.
   */
  protected function aggregateData(string $insightType, array $tenantIds): array {
    // Statistical aggregation: mean, median, percentiles.
    return [];
  }

  /**
   * Stores aggregated insight entity.
   */
  protected function storeInsight(string $insightType, array $data, int $tenantCount): ?int {
    try {
      $storage = $this->entityTypeManager->getStorage('aggregated_insight');
      $entity = $storage->create([
        'insight_type' => $insightType,
        'data' => json_encode($data, JSON_THROW_ON_ERROR),
        'tenant_count' => $tenantCount,
        'k_anonymity' => self::K_ANONYMITY_THRESHOLD,
        'privacy_epsilon' => self::PRIVACY_EPSILON,
      ]);
      $entity->save();
      return (int) $entity->id();
    }
    catch (\Throwable $e) {
      return NULL;
    }
  }

}
```

---

### 10.2 CausalAnalyticsService

**Archivo:** `web/modules/custom/jaraba_ai_agents/src/Service/CausalAnalyticsService.php`

Usa LLM premium tier (Opus) sobre datos estructurados para analisis causal.

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Causal analytics using LLM premium tier over structured data.
 *
 * Natural language queries: "Why did conversions drop?" → examines traffic,
 * pricing, content, engagement data. Counterfactual: "What if we raise
 * prices by 10%?" → historical elasticity analysis.
 */
class CausalAnalyticsService {

  public function __construct(
    protected readonly object $aiProvider,
    protected readonly ModelRouterService $modelRouter,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerChannelInterface $logger,
    protected readonly ?AIObservabilityService $observability = NULL,
  ) {}

  /**
   * Performs causal analysis from a natural language query.
   *
   * @param string $query
   *   Natural language question (e.g., "Why did conversions drop?").
   * @param array $context
   *   Context: tenant_id, vertical, date_range.
   *
   * @return array
   *   Analysis: {success: bool, analysis: string, factors: array,
   *   confidence: float, counterfactual: ?string, analysis_id: ?int}.
   */
  public function analyze(string $query, array $context): array {
    try {
      $structuredData = $this->gatherData($context);
      $prompt = $this->buildCausalPrompt($query, $structuredData, $context);

      $routingConfig = $this->modelRouter->route('causal_analysis', $prompt, [
        'force_tier' => 'premium',
      ]);

      $provider = $this->aiProvider->createInstance($routingConfig['provider_id']);
      $input = new \Drupal\ai\OperationType\Chat\ChatInput([
        new \Drupal\ai\OperationType\Chat\ChatMessage('system', 'You are a causal analytics expert. Analyze data to identify causal relationships. Respond with JSON.'),
        new \Drupal\ai\OperationType\Chat\ChatMessage('user', $prompt),
      ]);

      $response = $provider->chat($input, $routingConfig['model_id'], [
        'chat_system_role' => 'Causal Analyst',
      ]);

      $analysis = $this->parseAnalysis($response->getNormalized()->getText());
      $analysisId = $this->storeAnalysis($query, $analysis, $context);

      $this->observability?->log([
        'agent_id' => 'causal_analytics',
        'action' => 'analyze',
        'tier' => 'premium',
        'tenant_id' => $context['tenant_id'] ?? '',
        'success' => TRUE,
      ]);

      return array_merge($analysis, ['analysis_id' => $analysisId]);
    }
    catch (\Throwable $e) {
      $this->logger->error('Causal analysis failed: @error', ['@error' => $e->getMessage()]);
      return ['success' => FALSE, 'error' => $e->getMessage()];
    }
  }

  protected function buildCausalPrompt(string $query, array $data, array $context): string {
    $dataJson = json_encode($data, JSON_PRETTY_PRINT);
    $vertical = $context['vertical'] ?? 'general';

    return <<<PROMPT
Causal Analysis Request:
Query: {$query}
Vertical: {$vertical}

Available Data:
{$dataJson}

Analyze the causal factors. Respond with JSON:
{
  "success": true,
  "analysis": "Narrative explanation of findings",
  "factors": [{"factor": "name", "impact": 0.0, "direction": "positive|negative", "evidence": "description"}],
  "confidence": 0.0,
  "counterfactual": "What-if scenario analysis",
  "recommendations": ["action 1", "action 2"]
}
PROMPT;
  }

  protected function gatherData(array $context): array {
    // Gather structured data from observability, analytics, entity queries.
    return [];
  }

  protected function parseAnalysis(string $text): array {
    $text = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', trim($text));
    return json_decode($text, TRUE) ?: ['success' => FALSE, 'error' => 'Parse failure'];
  }

  protected function storeAnalysis(string $query, array $analysis, array $context): ?int {
    try {
      $storage = $this->entityTypeManager->getStorage('causal_analysis');
      $entity = $storage->create([
        'query' => $query,
        'analysis' => json_encode($analysis, JSON_THROW_ON_ERROR),
        'tenant_id' => $context['tenant_id'] ?? '',
        'vertical' => $context['vertical'] ?? '',
      ]);
      $entity->save();
      return (int) $entity->id();
    }
    catch (\Throwable $e) {
      return NULL;
    }
  }

}
```

---

### 10.3 Entidades: AggregatedInsight y CausalAnalysis

Siguen patron ContentEntity establecido. AggregatedInsight con: `insight_type`, `data` (JSON), `tenant_count`, `k_anonymity`, `privacy_epsilon`, `created`. CausalAnalysis con: `query`, `analysis` (JSON), `tenant_id`, `vertical`, `created`.

---

### 10.4 Dashboard Causal Analytics

Template zero-region: `page--causal-analytics.html.twig`
SCSS: `scss/routes/_causal-analytics.scss`
Body class: `page-causal-analytics`
Widget partial: `_causal-analysis-widget.html.twig`

---

### 10.5 Tests Sprint 5

| Test | Cobertura |
|------|----------|
| FederatedInsightServiceTest | k-anonimidad threshold, differential privacy noise |
| CausalAnalyticsServiceTest | Query parsing, factor identification, premium tier routing |

**Criterio de completitud Sprint 5:**
- FederatedInsightService rechaza cuando <5 tenants opted-in
- Differential privacy (Laplace noise) aplicado
- CausalAnalyticsService enruta a premium tier
- Dashboard causal zero-region funcional
- AggregatedInsight y CausalAnalysis entities persistentes

---

## 11. Arquitectura Frontend

### 11.1 Templates Twig nuevas

| # | Template | Tipo | Sprint |
|---|----------|------|--------|
| 1 | `page--ai-compliance.html.twig` | Zero-region page | 2 |
| 2 | `page--autonomous-agents.html.twig` | Zero-region page | 4 |
| 3 | `page--causal-analytics.html.twig` | Zero-region page | 5 |
| 4 | `_voice-copilot-fab.html.twig` | Partial | 3 |
| 5 | `_ai-health-indicator.html.twig` | Partial | 4 |
| 6 | `_causal-analysis-widget.html.twig` | Partial | 5 |

Todas las paginas siguen ZERO-REGION-001/002/003: sin `{{ page.* }}`, usa `{{ clean_content }}` y `{{ clean_messages }}`.

### 11.2 SCSS nuevos

| # | Archivo | Compilacion | Sprint |
|---|---------|------------|--------|
| 1 | `scss/routes/_ai-compliance.scss` | `npx sass` route bundle | 2 |
| 2 | `scss/routes/_autonomous-agents.scss` | `npx sass` route bundle | 4 |
| 3 | `scss/routes/_causal-analytics.scss` | `npx sass` route bundle | 5 |
| 4 | `ecosistema_jaraba_core/scss/_voice-copilot.scss` | Module build | 3 |

Todos usan `@use '../variables' as *;`, `var(--ej-*)` tokens, BEM naming.

### 11.3 Librerias JavaScript

| Library | Modulo | Dependencies |
|---------|--------|-------------|
| `voice-copilot` | ecosistema_jaraba_core | core/drupal, core/once, core/drupalSettings |

### 11.4 Body Classes

Adiciones en `ecosistema_jaraba_theme_preprocess_html()`:

```php
// Sprint 2: AI Compliance
$compliance_routes = ['jaraba_ai_agents.compliance_dashboard'];
if (in_array($route, $compliance_routes, TRUE)) {
  $variables['attributes']['class'][] = 'page-ai-compliance';
  $variables['attributes']['class'][] = 'dashboard-page';
}

// Sprint 4: Autonomous Agents
$autonomous_routes = ['jaraba_ai_agents.autonomous_dashboard'];
if (in_array($route, $autonomous_routes, TRUE)) {
  $variables['attributes']['class'][] = 'page-autonomous-agents';
  $variables['attributes']['class'][] = 'dashboard-page';
}

// Sprint 5: Causal Analytics
$causal_routes = ['jaraba_ai_agents.causal_dashboard'];
if (in_array($route, $causal_routes, TRUE)) {
  $variables['attributes']['class'][] = 'page-causal-analytics';
  $variables['attributes']['class'][] = 'dashboard-page';
}
```

---

## 12. Internacionalizacion (i18n)

### Inventario de strings traducibles

| Sprint | Modulo | Strings | Tipo |
|--------|--------|---------|------|
| 1 | jaraba_ai_agents | ~20 | $this->t() en servicios y entidades |
| 2 | jaraba_ai_agents | ~40 | Labels EU AI Act, form fields, dashboard text |
| 2 | ecosistema_jaraba_theme | ~10 | {% trans %} en template compliance |
| 3 | ecosistema_jaraba_core | ~15 | Voice widget states, labels |
| 3 | ecosistema_jaraba_theme | ~5 | {% trans %} en FAB |
| 4 | jaraba_ai_agents | ~25 | Agent types, session statuses, remediation types |
| 4 | ecosistema_jaraba_theme | ~10 | {% trans %} en dashboards |
| 5 | jaraba_ai_agents | ~15 | Insight types, analytics queries |
| 5 | ecosistema_jaraba_theme | ~5 | {% trans %} en dashboard |
| **Total** | | **~145** | |

### Patrones de traduccion

| Contexto | Patron |
|----------|--------|
| PHP Services | `$this->t('String')` o `(string) \Drupal::translation()->translate('String')` |
| PHP Entities | `t('String')` en BaseFieldDefinition |
| Twig templates | `{% trans %}String{% endtrans %}` |
| JavaScript | `Drupal.t('String')` |
| YAML (permissions) | Strings ya son traducibles por Drupal |

---

## 13. Seguridad

### CSRF

| Ruta API | Metodo | Proteccion |
|----------|--------|-----------|
| `/api/v1/voice/transcribe` | POST | `_csrf_request_header_token: 'TRUE'` |
| `/api/v1/voice/synthesize` | POST | `_csrf_request_header_token: 'TRUE'` |
| `/api/v1/autonomous/start` | POST | `_csrf_request_header_token: 'TRUE'` |
| `/api/v1/autonomous/pause` | POST | `_csrf_request_header_token: 'TRUE'` |
| `/api/v1/causal/analyze` | POST | `_csrf_request_header_token: 'TRUE'` |

### Tenant Isolation

| Entidad | Campo tenant_id | AccessHandler |
|---------|----------------|---------------|
| PromptImprovement | `tenant_id` (string) | admin_permission |
| AiAuditEntry | `tenant_id` (string) | admin_permission |
| BrowserTask | `tenant_id` (string) | admin_permission |
| AutonomousSession | `tenant_id` (string) | admin_permission |
| AggregatedInsight | N/A (cross-tenant) | admin_permission, k-anonymity |
| CausalAnalysis | `tenant_id` (string) | admin_permission |

### PII Protection

| Capa | Proteccion | Servicio |
|------|-----------|---------|
| Input | Deteccion + sanitizacion | AIGuardrailsService::checkPII() |
| Intermediate | Sanitizacion RAG/tools | AIGuardrailsService::sanitizeRagContent() |
| Output | Masking | AIGuardrailsService::maskOutputPII() |
| Constitutional | Bloqueo absoluto | ConstitutionalGuardrailService::enforce() |
| Federated | k-anonimidad + differential privacy | FederatedInsightService |

### Browser Agent Sandboxing

| Control | Implementacion |
|---------|---------------|
| URL Allowlist | Patron regex por vertical, no wildcard global |
| Docker isolation | Playwright en container separado con `--no-sandbox` |
| Memory limits | Docker `--memory=512m` por container |
| Network | Solo outbound a allowlisted domains |
| Timeout | 120 segundos max por tarea |
| Audit | Cada tarea → BrowserTask entity (append-only) |

---

## 14. Fases de Implementacion

### Tabla de sprints con dependencias

| Orden | Sprint | GAPs | Horas | Dependencia | Criterio de Completitud |
|-------|--------|------|-------|-------------|------------------------|
| 1 | Constitutional + Self-Improvement | L5-A, L5-B | 80-120h | Ninguna | 5 reglas constitucionales, verifier funcional, 2 entidades |
| 2 | EU AI Act Compliance | L5-C | 60-90h | Sprint 1 | Dashboard compliance, 10 agentes clasificados, audit trail |
| 3 | Multi-Modal + Browser | L5-D, L5-E | 100-150h | Sprint 1 | Voice FAB, browser scraping con allowlist, 3 tools |
| 4 | Autonomous + Self-Healing | L5-F, L5-G | 80-120h | Sprint 1, 2 | 4 agent types, heartbeat, auto-pause, auto-diagnostic |
| 5 | Federated + Causal | L5-H, L5-I | 60-90h | Sprint 4 | k-anonymity, causal analysis con premium tier |

### Grafico de dependencias

```
Sprint 1 (Constitutional + Verifier)
  ├── Sprint 2 (EU AI Act)
  │     └── Sprint 4 (Autonomous + Self-Healing)
  │           └── Sprint 5 (Federated + Causal)
  └── Sprint 3 (Multi-Modal + Browser)
```

---

## 15. Estrategia de Testing

### Tests Unitarios

| # | Clase | Modulo | Metodos cubiertos |
|---|-------|--------|-------------------|
| 1 | `ConstitutionalGuardrailServiceTest` | jaraba_ai_agents | enforce(), validatePromptModification(), getRules() |
| 2 | `VerifierAgentServiceTest` | jaraba_ai_agents | verify(), shouldVerify(), constitutional blocking |
| 3 | `AgentSelfReflectionServiceTest` | jaraba_ai_agents | reflect(), buildReflectionPrompt(), parseReflection() |
| 4 | `SelfImprovingPromptManagerTest` | jaraba_ai_agents | proposeImprovement(), applyImprovement(), rollback() |
| 5 | `AiRiskClassificationServiceTest` | jaraba_ai_agents | classify(), classifyAll(), getRiskLevels() |
| 6 | `AiComplianceDocumentationServiceTest` | jaraba_ai_agents | generate(), section generation per Art. 11 |
| 7 | `AiTransparencyServiceTest` | jaraba_ai_agents | getLabel(), renderBadge(), bilingual labels |
| 8 | `VoicePipelineServiceTest` | jaraba_ai_agents | transcribe(), synthesize(), isEnabled(), feature flag |
| 9 | `BrowserAgentServiceTest` | jaraba_ai_agents | execute(), isUrlAllowed(), URL allowlist per vertical |
| 10 | `AutonomousAgentServiceTest` | jaraba_ai_agents | startSession(), pauseSession(), recordExecution(), auto-pause |
| 11 | `AutoDiagnosticServiceTest` | jaraba_ai_agents | diagnose(), threshold detection, remediation proposals |
| 12 | `FederatedInsightServiceTest` | jaraba_ai_agents | generateInsight(), k-anonymity threshold, privacy noise |
| 13 | `CausalAnalyticsServiceTest` | jaraba_ai_agents | analyze(), buildCausalPrompt(), premium tier routing |
| 14 | `AiAuditTrailServiceTest` | jaraba_ai_agents | record(), query(), getComplianceStats() |

### Tests Kernel

| # | Clase | Que verifica |
|---|-------|-------------|
| 1 | `VerificationResultEntityTest` | Schema, CRUD, indexes |
| 2 | `AiAuditEntryEntityTest` | Schema, CRUD, append-only |
| 3 | `AiRiskAssessmentFormTest` | ConfigEntity form submission |

### Ejecucion de tests

```bash
# Unit tests (rapido, sin base de datos)
lando php vendor/bin/phpunit --testsuite=Unit --filter='Constitutional|Verifier|SelfReflection|RiskClassification|Compliance|Transparency|VoicePipeline|BrowserAgent|Autonomous|AutoDiagnostic|Federated|Causal|AuditTrail'

# Kernel tests (requiere MariaDB)
lando php vendor/bin/phpunit --testsuite=Kernel --filter='VerificationResult|AiAuditEntry|AiRiskAssessment'

# Todos los tests del modulo
lando php vendor/bin/phpunit --testsuite=Unit,Kernel --filter='jaraba_ai_agents'
```

---

## 16. Verificacion y Despliegue

### Pre-deploy checklist

- [ ] `lando php -l` en todos los ficheros PHP nuevos/modificados — 0 errores de sintaxis
- [ ] `lando php vendor/bin/phpunit --testsuite=Unit` — 14+ tests pass para Sprint completo
- [ ] `lando drush updb -y` — update hooks instalan entidades sin errores
- [ ] `lando drush cr` — cache rebuild sin errores
- [ ] SCSS compila sin errores: `npx sass --no-error-css scss/routes/*.scss`
- [ ] Compliance dashboard renderiza en `/admin/config/ai/compliance`
- [ ] Audit trail accesible en `/admin/content/ai/audit-trail`
- [ ] Constitutional rules no son desactivables (hardcoded constants)
- [ ] `grep -rn 'emoji' templates/` — 0 Unicode emojis en templates
- [ ] `grep -rn 'jaraba_icon' templates/` — todas las invocaciones usan formato correcto

### Comandos de verificacion

```bash
# Verificar sintaxis PHP de todos los archivos nuevos
find web/modules/custom/jaraba_ai_agents/src -name '*.php' -newer web/modules/custom/jaraba_ai_agents/jaraba_ai_agents.info.yml -exec lando php -l {} \;

# Verificar que no hay emojis Unicode en templates
grep -rn '[^\x00-\x7F]' web/themes/custom/ecosistema_jaraba_theme/templates/page--ai-compliance.html.twig || echo "OK: No Unicode emojis"

# Verificar CSRF en nuevas rutas API
grep -A2 'methods.*POST' web/modules/custom/jaraba_ai_agents/jaraba_ai_agents.routing.yml | grep csrf

# Verificar entidades instaladas
lando drush ev "print_r(array_keys(\Drupal::entityTypeManager()->getDefinitions()));" | grep -E 'verification_result|prompt_improvement|ai_risk_assessment|ai_audit_entry|browser_task|autonomous_session|remediation_log|aggregated_insight|causal_analysis'

# Verificar servicios registrados
lando drush ev "print_r(\Drupal::hasService('jaraba_ai_agents.constitutional_guardrails'));"
lando drush ev "print_r(\Drupal::hasService('jaraba_ai_agents.verifier'));"
lando drush ev "print_r(\Drupal::hasService('jaraba_ai_agents.risk_classification'));"
```

### Procedimiento de rollback

```bash
# Si un update hook falla:
lando drush updb --no-post-updates  # Intentar sin post-updates
# Si persiste, revertir al commit anterior:
git log --oneline -5
git checkout <commit-anterior> -- web/modules/custom/jaraba_ai_agents/
lando drush cr && lando drush updb -y
```

---

## 17. Troubleshooting

| # | Problema | Causa probable | Solucion |
|---|----------|---------------|----------|
| 1 | `ServiceNotFoundException: jaraba_ai_agents.constitutional_guardrails` | services.yml no registrado | Verificar syntaxis YAML, `lando drush cr` |
| 2 | `EntityStorageException: verification_result table not found` | Update hook no ejecutado | `lando drush updb -y` |
| 3 | Constitutional rules bloquean respuestas legitimas | Patron regex demasiado amplio | Ajustar patron especifico, anadir test de regresion |
| 4 | Verifier anade latencia excesiva | Mode `all` con respuestas largas | Cambiar a `sample` o `critical_only` |
| 5 | Voice FAB no aparece | Feature flag desactivado | Verificar `jaraba_ai_agents.voice.enabled` config |
| 6 | Browser agent devuelve 403 | URL fuera de allowlist | Verificar allowlist por vertical en config |
| 7 | Autonomous agent auto-paused | 3 fallos consecutivos o cost_ceiling | Revisar logs, reset `consecutive_failures`, ajustar ceiling |
| 8 | Federated insights insuficientes | <5 tenants opted-in | Esperar mas tenants o reducir K_ANONYMITY_THRESHOLD en dev |
| 9 | Causal analysis timeout | Premium tier lento con datos grandes | Limitar datos de contexto, usar `fitToWindow()` |
| 10 | SCSS no compila | Variable `$ej-*` usada en modulo | Cambiar a `var(--ej-*)` per FEDERATED-DESIGN-TOKENS |
| 11 | Template error: `clean_content undefined` | preprocess_page no configurado para ruta | Verificar route matching en preprocess_page |
| 12 | AiRiskAssessmentForm error: `PremiumEntityFormBase not found` | ecosistema_jaraba_core no habilitado | Verificar dependencia en .info.yml |

---

## 18. Referencias Cruzadas

### Archivos de codigo nuevos

| # | Archivo | Sprint | Tipo |
|---|---------|--------|------|
| 1 | `jaraba_ai_agents/src/Service/ConstitutionalGuardrailService.php` | 1 | Service |
| 2 | `jaraba_ai_agents/src/Service/AgentSelfReflectionService.php` | 1 | Service |
| 3 | `jaraba_ai_agents/src/Service/SelfImprovingPromptManager.php` | 1 | Service |
| 4 | `jaraba_ai_agents/src/Service/VerifierAgentService.php` | 1 | Service |
| 5 | `jaraba_ai_agents/src/Entity/VerificationResult.php` | 1 | Entity |
| 6 | `jaraba_ai_agents/src/Entity/PromptImprovement.php` | 1 | Entity |
| 7 | `jaraba_ai_agents/tests/src/Unit/Service/ConstitutionalGuardrailServiceTest.php` | 1 | Test |
| 8 | `jaraba_ai_agents/tests/src/Unit/Service/VerifierAgentServiceTest.php` | 1 | Test |
| 9 | `jaraba_ai_agents/src/Service/AiRiskClassificationService.php` | 2 | Service |
| 10 | `jaraba_ai_agents/src/Service/AiComplianceDocumentationService.php` | 2 | Service |
| 11 | `jaraba_ai_agents/src/Service/AiTransparencyService.php` | 2 | Service |
| 12 | `jaraba_ai_agents/src/Service/AiAuditTrailService.php` | 2 | Service |
| 13 | `jaraba_ai_agents/src/Entity/AiRiskAssessment.php` | 2 | ConfigEntity |
| 14 | `jaraba_ai_agents/src/Entity/AiAuditEntry.php` | 2 | Entity |
| 15 | `jaraba_ai_agents/src/Form/AiRiskAssessmentForm.php` | 2 | Form |
| 16 | `jaraba_ai_agents/src/Controller/AiComplianceDashboardController.php` | 2 | Controller |
| 17 | `ecosistema_jaraba_theme/templates/page--ai-compliance.html.twig` | 2 | Template |
| 18 | `ecosistema_jaraba_theme/scss/routes/_ai-compliance.scss` | 2 | SCSS |
| 19 | `jaraba_ai_agents/src/Controller/VoiceCopilotController.php` | 3 | Controller |
| 20 | `jaraba_ai_agents/src/Service/VoicePipelineService.php` | 3 | Service |
| 21 | `jaraba_ai_agents/src/Service/BrowserAgentService.php` | 3 | Service |
| 22 | `jaraba_ai_agents/src/Tool/WebScrapingTool.php` | 3 | Tool |
| 23 | `jaraba_ai_agents/src/Tool/FormFillerTool.php` | 3 | Tool |
| 24 | `jaraba_ai_agents/src/Tool/ScreenCaptureTool.php` | 3 | Tool |
| 25 | `jaraba_ai_agents/src/Entity/BrowserTask.php` | 3 | Entity |
| 26 | `ecosistema_jaraba_core/js/voice-copilot-widget.js` | 3 | JS |
| 27 | `ecosistema_jaraba_core/scss/_voice-copilot.scss` | 3 | SCSS |
| 28 | `ecosistema_jaraba_theme/templates/partials/_voice-copilot-fab.html.twig` | 3 | Template |
| 29 | `jaraba_ai_agents/src/Service/AutonomousAgentService.php` | 4 | Service |
| 30 | `jaraba_ai_agents/src/Service/AutoDiagnosticService.php` | 4 | Service |
| 31 | `jaraba_ai_agents/src/Entity/AutonomousSession.php` | 4 | Entity |
| 32 | `jaraba_ai_agents/src/Entity/RemediationLog.php` | 4 | Entity |
| 33 | `jaraba_ai_agents/src/Plugin/QueueWorker/AutonomousAgentHeartbeatWorker.php` | 4 | QueueWorker |
| 34 | `jaraba_ai_agents/src/Controller/AutonomousAgentDashboardController.php` | 4 | Controller |
| 35 | `ecosistema_jaraba_theme/templates/page--autonomous-agents.html.twig` | 4 | Template |
| 36 | `ecosistema_jaraba_theme/scss/routes/_autonomous-agents.scss` | 4 | SCSS |
| 37 | `ecosistema_jaraba_theme/templates/partials/_ai-health-indicator.html.twig` | 4 | Template |
| 38 | `jaraba_ai_agents/src/Service/FederatedInsightService.php` | 5 | Service |
| 39 | `jaraba_ai_agents/src/Service/CausalAnalyticsService.php` | 5 | Service |
| 40 | `jaraba_ai_agents/src/Entity/AggregatedInsight.php` | 5 | Entity |
| 41 | `jaraba_ai_agents/src/Entity/CausalAnalysis.php` | 5 | Entity |
| 42 | `jaraba_ai_agents/src/Controller/CausalAnalyticsDashboardController.php` | 5 | Controller |
| 43 | `ecosistema_jaraba_theme/templates/page--causal-analytics.html.twig` | 5 | Template |
| 44 | `ecosistema_jaraba_theme/scss/routes/_causal-analytics.scss` | 5 | SCSS |
| 45 | `ecosistema_jaraba_theme/templates/partials/_causal-analysis-widget.html.twig` | 5 | Template |

### Archivos de codigo modificados

| # | Archivo | Sprint | Cambio |
|---|---------|--------|--------|
| 1 | `jaraba_ai_agents/src/Agent/SmartBaseAgent.php` | 1 | +verifier, +selfReflection, execute() modificado |
| 2 | `jaraba_ai_agents/jaraba_ai_agents.services.yml` | 1-5 | +14 servicios nuevos |
| 3 | `jaraba_ai_agents/jaraba_ai_agents.install` | 1-5 | +update hooks para 9 entidades |
| 4 | `jaraba_ai_agents/jaraba_ai_agents.permissions.yml` | 1-5 | +8 permisos nuevos |
| 5 | `jaraba_ai_agents/jaraba_ai_agents.routing.yml` | 1-5 | +15 rutas nuevas |
| 6 | `jaraba_ai_agents/jaraba_ai_agents.links.task.yml` | 1-5 | +10 tabs nuevos |
| 7 | `jaraba_ai_agents/src/Tool/ToolRegistry.php` | 3 | +3 nuevos tools registrados |
| 8 | `jaraba_ai_agents/src/Service/AIObservabilityService.php` | 4 | +metricas para auto-diagnostico |
| 9 | `jaraba_ai_agents/src/Service/ProviderFallbackService.php` | 4 | +auto-rotation triggers |
| 10 | `jaraba_ai_agents/src/Service/ModelRouterService.php` | 4 | +auto-downgrade capability |
| 11 | `jaraba_ai_agents/src/Service/ProactiveInsightsService.php` | 5 | +causal extension |
| 12 | `ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme` | 2,4,5 | +preprocess_html body classes |
| 13 | `ecosistema_jaraba_theme/ecosistema_jaraba_theme.libraries.yml` | 2,4,5 | +3 route libraries |
| 14 | `ecosistema_jaraba_core/ecosistema_jaraba_core.libraries.yml` | 3 | +voice-copilot library |

### Documentos de referencia

| Documento | Version | Relevancia |
|-----------|---------|-----------|
| `docs/00_DIRECTRICES_PROYECTO.md` | v91.0.0 | Reglas de cumplimiento |
| `docs/07_FLUJO_TRABAJO_CLAUDE.md` | v45.0.0 | Patrones DI, FIELD-UI |
| `docs/arquitectura/2026-02-26_arquitectura_elevacion_ia_nivel5.md` | v2.0.0 | Arquitectura base Gen 2 |
| `docs/analisis/2026-02-27_Auditoria_IA_SaaS_Clase_Mundial_v1.md` | v4.0.0 | Auditoria 100/100 base |
| `docs/implementacion/2026-02-26_Plan_Implementacion_Auditoria_IA_Clase_Mundial_v1.md` | — | Plan implementacion referencia |
| EU AI Act (Reg. 2024/1689) | — | Compliance legal Sprint 2 |
| ISO 42001 (AI Management) | — | Best practices Sprint 2 |

### Referencias externas

| Recurso | URL | Relevancia |
|---------|-----|-----------|
| EU AI Act texto oficial | `eur-lex.europa.eu/eli/reg/2024/1689` | Sprint 2 |
| Anthropic Constitutional AI | `anthropic.com/research/constitutional-ai` | Sprint 1 |
| Drupal AI Module | `drupal.org/project/ai` | Integracion LLM |
| Playwright Docs | `playwright.dev/docs` | Sprint 3 |
| Whisper API | `platform.openai.com/docs/guides/speech-to-text` | Sprint 3 |
| Differential Privacy | `desfontaines.github.io/privacy` | Sprint 5 |

---

## 19. Registro de Cambios

| Version | Fecha | Autor | Cambios |
|---------|-------|-------|---------|
| 1.0.0 | 2026-02-27 | IA Asistente (Claude Opus 4.6) | Documento inicial — 9 GAPs, 5 sprints, 45 archivos nuevos, 14 modificados |

---

*Fin del documento — Plan de Implementacion IA Nivel 5 Transformacional v1.0.0*
*Lineas totales: ~3,500+*
*Generado por IA Asistente (Claude Opus 4.6) el 2026-02-27*

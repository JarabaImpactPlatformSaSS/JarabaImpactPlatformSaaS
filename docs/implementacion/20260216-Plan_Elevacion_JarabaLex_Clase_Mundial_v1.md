# Plan de Elevación JarabaLex a Clase Mundial — v1.0

| Metadato | Valor |
|----------|-------|
| **Documento** | Plan de Implementación |
| **Versión** | 1.0.0 |
| **Fecha** | 2026-02-16 |
| **Estado** | Planificación Aprobada |
| **Vertical** | JarabaLex (Inteligencia Legal) |
| **Módulos Implicados** | `jaraba_legal_intelligence`, `ecosistema_jaraba_core`, `jaraba_journey`, `jaraba_ai_agents`, `jaraba_email`, `jaraba_billing`, `jaraba_analytics`, `ecosistema_jaraba_theme` |
| **Referencia de Paridad** | Empleabilidad (10 fases) + Emprendimiento (7 gaps) |
| **Docs de Referencia** | 00_DIRECTRICES_PROYECTO v39.0.0, 00_DOCUMENTO_MAESTRO_ARQUITECTURA v39.0.0, 07_VERTICAL_CUSTOMIZATION_PATTERNS v1.1.0, 2026-02-05_arquitectura_theming_saas_master v2.1, 00_FLUJO_TRABAJO_CLAUDE v1.0.0 |
| **Estimación Total** | 14 Fases · 280–360 horas · 12.600–16.200 EUR |

---

## Índice de Navegación (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Estado Actual del Vertical](#2-estado-actual-del-vertical)
3. [Análisis de Gaps — Paridad con Empleabilidad/Emprendimiento](#3-análisis-de-gaps)
4. [Dependencias Cruzadas y Flujos Entrada/Salida](#4-dependencias-cruzadas)
5. [Recorridos de Usuario](#5-recorridos-de-usuario)
6. [Plan de Implementación por Fases](#6-plan-de-implementación-por-fases)
   - [FASE 0 — Landing Page Pública + Lead Magnet](#fase-0)
   - [FASE 1 — Clean Page Templates (Zero Region)](#fase-1)
   - [FASE 2 — Sistema Modal en Acciones CRUD](#fase-2)
   - [FASE 3 — SCSS Compliance y Design Tokens](#fase-3)
   - [FASE 4 — Feature Gating por Plan (Escalera de Valor)](#fase-4)
   - [FASE 5 — Upgrade Triggers y Upsell Contextual IA](#fase-5)
   - [FASE 6 — Email Sequences Automatizadas](#fase-6)
   - [FASE 7 — CRM Integration y Pipeline](#fase-7)
   - [FASE 8 — Cross-Vertical Value Bridges](#fase-8)
   - [FASE 9 — Journey Definition + AI Progression Proactiva](#fase-9)
   - [FASE 10 — Health Scores y Métricas Específicas](#fase-10)
   - [FASE 11 — Copilot Agent Dedicado](#fase-11)
   - [FASE 12 — Avatar Navigation + Funnel Analytics](#fase-12)
   - [FASE 13 — QA, Tests y Documentación](#fase-13)
7. [Tabla de Correspondencia con Especificaciones Técnicas](#7-tabla-de-correspondencia)
8. [Cumplimiento de Directrices](#8-cumplimiento-de-directrices)
9. [Checklist Pre-Commit por Fase](#9-checklist-pre-commit)
10. [Inventario de Archivos a Crear/Modificar](#10-inventario-de-archivos)

---

## 1. Resumen Ejecutivo

JarabaLex es el vertical de **Inteligencia Legal** del SaaS Jaraba Impact Platform. Ofrece búsqueda semántica con IA sobre jurisprudencia nacional (CENDOJ, BOE, DGT, TEAC) y europea (EUR-Lex, CURIA, HUDOC, EDPB), pipeline NLP de 9 etapas, alertas inteligentes, citaciones cruzadas y digest semanal.

El vertical fue elevado a independiente el 2026-02-16 (previamente sub-feature de ServiciosConecta). Cuenta con un módulo robusto de 137 archivos y 22.703 LOC, pero carece de toda la infraestructura de experiencia de usuario, embudo de ventas y progresión automatizada por IA que define el nivel de clase mundial en los verticales de referencia (Empleabilidad y Emprendimiento).

### Objetivo

Elevar JarabaLex al mismo nivel de clase mundial que Empleabilidad y Emprendimiento, implementando las 14 fases que componen la infraestructura completa de:
- **Adquisición**: Landing page pública + lead magnet + SEO
- **Activación**: Onboarding guiado por IA + feature gating + modales
- **Engagement**: Journey progression proactiva + email sequences + health score
- **Monetización**: Escalera de valor + upgrade triggers + upsell contextual
- **Retención**: Cross-vertical bridges + digest semanal + CRM sync
- **Expansión**: Puentes a otros verticales + credenciales + analytics

### Mercado Objetivo

- Competidores: Aranzadi (3.000–8.000 EUR/año), Lefebvre (similar), vLex (similar)
- Diferenciación: IA nativa, fuentes públicas gratuitas (Ley 37/2007), precio disruptivo (49–199 EUR/mes)
- TAM estimado: 300M EUR

---

## 2. Estado Actual del Vertical

### 2.1 Lo que EXISTE (Implementado)

| Componente | Estado | Ubicación |
|------------|--------|-----------|
| **Módulo `jaraba_legal_intelligence`** | 137 archivos, 22.703 LOC | `web/modules/custom/jaraba_legal_intelligence/` |
| 5 Content Entities | Implementadas | `src/Entity/` (LegalResolution, LegalSource, LegalAlert, LegalBookmark, LegalCitation) |
| 8 Services | Implementadas | `src/Service/` (Search, Ingestion, NlpPipeline, Alerts, Citations, Digest, CopilotBridge, MergeRank) |
| 8 Spiders | Implementadas | `src/Service/Spider/` (Cendoj, Boe, Dgt, Teac, EurLex, Curia, Hudoc, Edpb) |
| 5 Controllers | Implementados | `src/Controller/` (Search, Resolution, Admin, Dashboard, Sitemap) |
| 5 Access Handlers | Implementados | `src/Access/` |
| 5 List Builders | Implementados | `src/ListBuilder/` |
| 15 Twig Templates | Implementados | `templates/` (7 páginas + 8 parciales) |
| 10 SCSS Partials + main.scss | Implementados | `scss/` |
| 8 JS Files | Implementados | `js/` |
| 9 Taxonomías | Config install | `config/install/taxonomy.vocabulary.*.yml` |
| Queue Worker (Ingestion) | Implementado | `src/Plugin/QueueWorker/LegalIngestionWorker.php` |
| Python NLP + Docker | Implementado | `scripts/nlp/` + `docker-compose.legal.yml` |
| 20+ Routes | Implementadas | `jaraba_legal_intelligence.routing.yml` |
| 7 hooks en .module | Implementados | `jaraba_legal_intelligence.module` |
| package.json + Dart Sass | Implementado | `package.json` con `sass ^1.71.0` |
| **Vertical Config Entity** | Seed instalado | `ecosistema_jaraba_core.vertical.jarabalex.yml` |
| 3 SaaS Plans | Config install | `saas_plan.jarabalex_starter/pro/enterprise.yml` |
| 9 FreemiumVerticalLimit | Config install | `freemium_vertical_limit.jarabalex_*.yml` |
| 3 Feature Configs | Config install | `feature.legal_search/alerts/citations.yml` |
| FEATURE_ADDON_MAP | 3 entries | `FeatureAccessService.php` |
| `page--legal.html.twig` | Zero-region | `ecosistema_jaraba_theme/templates/` |
| `page--legal-compliance.html.twig` | Zero-region | `ecosistema_jaraba_theme/templates/` |
| Body classes + template suggestions | Hooks | `ecosistema_jaraba_theme.theme` |
| SCSS variables `--ej-legal-*` | CSS Custom Props | `_variables-legal.scss` |
| **Módulo `jaraba_legal`** (Compliance) | Independiente | `web/modules/custom/jaraba_legal/` |
| **Módulo `jaraba_legal_knowledge`** (BOE RAG) | Independiente | `web/modules/custom/jaraba_legal_knowledge/` |

### 2.2 Lo que FALTA (Gaps Críticos)

| # | Gap | Prioridad | Fase |
|---|-----|-----------|------|
| G1 | **Landing page pública** (`/jarabalex`) — no existe `VerticalLandingController::jarabalex()`, ni ruta, ni template `page--jarabalex.html.twig`, ni SCSS `.vertical-landing--jarabalex` | P0 CRÍTICA | F0 |
| G2 | **Lead Magnet** — no hay diagnóstico legal gratuito equivalente a `/empleabilidad/diagnostico` o `/emprendimiento/calculadora-madurez` | P0 CRÍTICA | F0 |
| G3 | **Feature Gating funcional** — `LegalSearchService::checkPlanLimits()` retorna `allowed: TRUE` incondicionalmente. No existe `JarabaLexFeatureGateService`. No hay tabla `{jarabalex_feature_usage}` | P0 CRÍTICA | F4 |
| G4 | **JourneyDefinition** — no existe `JarabaLexJourneyDefinition.php` en `jaraba_journey/src/JourneyDefinition/` | P0 CRÍTICA | F9 |
| G5 | **JourneyProgressionService** — no existe `JarabaLexJourneyProgressionService.php` | P1 ALTA | F9 |
| G6 | **HealthScoreService** — no existe `JarabaLexHealthScoreService.php` | P1 ALTA | F10 |
| G7 | **Copilot Agent dedicado** — no existe `JarabaLexCopilotAgent.php` extendiendo `BaseAgent`. Solo existe `LegalCopilotBridgeService` (RAG bridge) | P1 ALTA | F11 |
| G8 | **EmailSequenceService** — no existe `JarabaLexEmailSequenceService.php`. No hay carpeta `jaraba_email/templates/mjml/jarabalex/`. No hay SEQ_LEX_001–005 | P1 ALTA | F6 |
| G9 | **CrossVerticalBridgeService** — no existe `JarabaLexCrossVerticalBridgeService.php`. No hay puentes a fiscal, emprendimiento, empleabilidad | P2 MEDIA | F8 |
| G10 | **Avatar Navigation** — no hay entrada `legal_professional` en `AvatarNavigationService`. JarabaLex no aparece en navegación anónima | P1 ALTA | F12 |
| G11 | **BaseAgent vertical context** — no hay entrada `'jarabalex'` en `BaseAgent::getVerticalContext()` | P1 ALTA | F11 |
| G12 | **Funnel Analytics** — no hay `FunnelDefinition` config entity para JarabaLex en `jaraba_analytics` | P2 MEDIA | F12 |
| G13 | **Design Token Config Entity** — no existe `design_token_config.vertical_jarabalex.yml` | P2 MEDIA | F3 |
| G14 | **Upgrade Triggers** — no hay tipos de trigger para legal en `UpgradeTriggerService`. No hay llamadas `fire()` | P0 CRÍTICA | F5 |
| G15 | **CRM Pipeline Sync** — no hay hooks de sincronización CRM en el `.module` | P2 MEDIA | F7 |
| G16 | **Modal CRUD** — no verificado si los enlaces CRUD en templates usan `data-dialog-type="modal"` | P1 ALTA | F2 |
| G17 | **`getAvailableAddons()` entry** — `jaraba_legal_intelligence` no aparece en el UI de discovery de add-ons de `FeatureAccessService` | P2 MEDIA | F4 |
| G18 | **ExperimentService** — no hay servicio de A/B testing para el vertical | P3 BAJA | F13 |
| G19 | **Credential Journey** — no existe submódulo `jaraba_credentials_jarabalex` | P3 BAJA | F13 |
| G20 | **MJML email rendering** — los emails legales usan plain `hook_mail()` en vez del sistema MJML de `jaraba_email` | P1 ALTA | F6 |

---

## 3. Análisis de Gaps

### 3.1 Matriz de Paridad: JarabaLex vs Empleabilidad vs Emprendimiento

| Componente | Empleabilidad | Emprendimiento | JarabaLex |
|------------|:---:|:---:|:---:|
| Landing page pública (9 secciones) | ✅ | ✅ | ❌ G1 |
| Lead magnet gratuito | ✅ Diagnóstico | ✅ Calculadora Madurez | ❌ G2 |
| Zero-region page template | ✅ | ✅ | ✅ (parcial) |
| Modal CRUD | ✅ | ✅ | ❓ G16 |
| SCSS compliance (color-mix) | ✅ | ✅ | ⚠️ Parcial |
| Design Token Config Entity | ✅ | ✅ | ❌ G13 |
| FeatureGateService | ✅ | ✅ | ❌ G3 |
| FreemiumVerticalLimit configs | ✅ | ✅ | ✅ (9 configs) |
| Feature usage DB table | ✅ | ✅ | ❌ G3 |
| UpgradeTrigger types + fire() | ✅ | ✅ | ❌ G14 |
| Soft upsell en Copilot | ✅ | ✅ | ❌ G14 |
| EmailSequenceService | ✅ 5 seq | ✅ 5 seq | ❌ G8 |
| MJML templates | ✅ | ✅ | ❌ G20 |
| CRM sync hooks | ✅ | ✅ | ❌ G15 |
| CrossVerticalBridgeService | ✅ 4 bridges | ✅ 3 bridges | ❌ G9 |
| JourneyDefinition | ✅ 3 avatares | ✅ 3 avatares | ❌ G4 |
| JourneyProgressionService | ✅ 7 reglas | ✅ 7 reglas | ❌ G5 |
| HealthScoreService | ✅ 5 dim + 8 KPIs | ✅ 5 dim + 8 KPIs | ❌ G6 |
| CopilotAgent dedicado | ✅ 6 modos | ✅ 6 modos | ❌ G7 |
| Avatar en NavigationService | ✅ jobseeker/recruiter | ✅ entrepreneur | ❌ G10 |
| Vertical context en BaseAgent | ✅ empleo | ✅ emprendimiento | ❌ G11 |
| Funnel Definition | ✅ | ✅ | ❌ G12 |
| ExperimentService | ✅ | ✅ | ❌ G18 |

**Score de paridad: 3/24 completos (12,5%)** — El vertical tiene una base técnica sólida (módulo, entidades, servicios, spiders) pero carece de toda la capa de experiencia de usuario, embudo de ventas y progresión automatizada.

---

## 4. Dependencias Cruzadas

### 4.1 Módulos que JarabaLex CONSUME (Entrada)

| Módulo Origen | Qué Consume | Servicio/Interfaz |
|---------------|-------------|-------------------|
| `ecosistema_jaraba_core` | TenantContext, FeatureAccess, VerticalConfig, DesignTokens | `@ecosistema_jaraba_core.tenant_context` |
| `jaraba_ai_agents` | BaseAgent, UnifiedPromptBuilder, AIObservability | `@ai.provider`, `@jaraba_ai_agents.prompt_builder` |
| `jaraba_copilot_v2` | FAB Copilot UI, Copilot API | Templates parciales `_copilot-fab.html.twig` |
| `jaraba_billing` | FeatureAccessService, UpgradeTriggerService, Plan validation | `@jaraba_billing.feature_access` |
| `jaraba_journey` | JourneyStateManager, journey_state entity | `@jaraba_journey.state_manager` |
| `jaraba_email` | SequenceManagerService, MJML rendering | `@jaraba_email.sequence_manager` |
| `jaraba_analytics` | FunnelTrackingService, EventService | `@jaraba_analytics.funnel_tracking` |
| `jaraba_crm` | PipelineService, ActivityService, ContactService | `@jaraba_crm.pipeline` |
| `ecosistema_jaraba_theme` | Zero-region templates, header/footer parciales, SCSS tokens | Templates Twig |

### 4.2 Módulos que CONSUMEN de JarabaLex (Salida)

| Módulo Destino | Qué Consume | Interfaz |
|----------------|-------------|----------|
| `jaraba_copilot_v2` | RAG legal knowledge injection | `@jaraba_legal_intelligence.copilot_bridge` → `getRelevantKnowledge()` |
| `jaraba_servicios_conecta` | Legal citations en expedientes (Buzón Confianza Doc 88) | `LegalCitation.expediente_id` FK |
| `ecosistema_jaraba_core` | ComplianceAggregator agrega KPIs de `jaraba_legal` | `@jaraba_legal.tos_manager` etc. (inyección condicional) |
| `jaraba_verifactu` | Consultas DGT vinculantes en contexto fiscal | Consumo futuro vía CopilotBridge |

### 4.3 Flujos de Datos Cruzados

```
┌──────────────────┐    ┌───────────────────────┐    ┌──────────────────┐
│  FUENTES PÚBLICAS │───>│  jaraba_legal_         │───>│ jaraba_copilot_v2│
│  (CENDOJ, BOE,   │    │  intelligence          │    │ (RAG injection)  │
│  EUR-Lex, CURIA)  │    │  [Spiders → NLP →     │    └──────────────────┘
└──────────────────┘    │   Qdrant → Search]     │
                         │                        │───>│ jaraba_servicios │
                         │  Entities:             │    │ _conecta         │
                         │  - LegalResolution     │    │ (Expediente FK)  │
                         │  - LegalAlert          │    └──────────────────┘
                         │  - LegalCitation       │
                         │  - LegalBookmark       │───>│ jaraba_analytics │
                         │  - LegalSource         │    │ (Funnel events)  │
                         └───────────────────────┘    └──────────────────┘
                                    │
                                    ▼
                         ┌───────────────────────┐
                         │ ecosistema_jaraba_core │
                         │ - FeatureGateService   │
                         │ - JourneyProgression   │
                         │ - HealthScore          │
                         │ - EmailSequence        │
                         │ - CrossVerticalBridge  │
                         │ - AvatarNavigation     │
                         └───────────────────────┘
```

---

## 5. Recorridos de Usuario

### 5.1 Recorrido 1: Usuario No Registrado → Landing Pública

```
1. [SEO/SEM/Referral] → Usuario llega a /jarabalex
2. Landing page 9 secciones:
   ├─ Hero: "Inteligencia legal con IA al alcance de todos"
   ├─ Pain Points: 4 dolores del profesional jurídico
   ├─ Pasos: 3 pasos para empezar (Buscar → Analizar → Citar)
   ├─ Features: 6 capacidades con IA (Búsqueda semántica, Alertas, Citaciones, Digest, NLP, EU)
   ├─ Social Proof: Testimonios + métricas
   ├─ Lead Magnet: "Diagnóstico Legal Gratuito" → /jarabalex/diagnostico-legal
   ├─ Pricing: 3 planes (Free/Starter 49€/Pro 99€)
   ├─ FAQ: 8 preguntas frecuentes
   └─ Final CTA: Registro → /registro/jarabalex
3. Lead Magnet: Diagnóstico legal gratuito
   ├─ 5 preguntas sobre necesidades jurídicas
   ├─ IA genera informe personalizado (áreas de riesgo, fuentes relevantes)
   ├─ CTA: "Crea tu cuenta gratuita para monitorizar estos riesgos"
   └─ [Copilot FAB activo con contexto anónimo: modo `faq`]
4. Registro: /registro/jarabalex
   ├─ Plan Free automático (10 búsquedas/mes, 1 alerta, 10 marcadores)
   ├─ Auto-enrollment en SEQ_LEX_001 (Onboarding Legal)
   └─ Redirect a /legal/dashboard
```

### 5.2 Recorrido 2: Usuario Registrado → Embudo de Ventas

```
DISCOVERY (Plan Free):
├─ Dashboard: panel con búsquedas recientes, alertas activas, citaciones
├─ Copilot FAB: modo `legal_researcher` — "¿En qué área jurídica necesitas ayuda?"
├─ Primera búsqueda → muestra 3 resultados (límite free: solo 2 fuentes)
├─ IA Proactiva: si inactivo 3 días → nudge "Tenemos 12 nuevas resoluciones en tu área"
└─ Trigger: profile_setup_complete → transición a ACTIVATION

ACTIVATION (Plan Free, primera semana):
├─ Configura primera alerta inteligente (1 alerta gratis)
├─ Guarda 3 marcadores de resoluciones relevantes
├─ Lead magnet interno: "Tu informe de riesgo legal actualizado"
├─ Copilot: modo `document_coach` — ayuda a entender resoluciones complejas
├─ Trigger: third_search → SEQ_LEX_003 (Upsell Free→Starter)
└─ Gate: 10ª búsqueda → modal upgrade "Has agotado tus búsquedas este mes"

ENGAGEMENT (Plan Starter 49€/mes):
├─ 50 búsquedas/mes, 5 alertas, 100 marcadores
├─ Acceso a todas las fuentes nacionales + EU
├─ Copilot: modo `case_analyst` — analiza resoluciones similares a tu caso
├─ IA Proactiva: "Hemos detectado un cambio normativo que afecta a tu alerta X"
├─ Weekly digest activado
├─ Gate: necesita citación → modal upgrade "Citar en documentos es Pro"
└─ Trigger: 5th_alert_attempt → SEQ_LEX_004 (Upsell Starter→Pro)

CONVERSION (Plan Pro 99€/mes):
├─ Búsquedas ilimitadas, 20 alertas, marcadores ilimitados
├─ Inserción de citaciones en expedientes
├─ Digest semanal personalizado
├─ Copilot: modo `citation_expert` — genera citaciones en 4 formatos
├─ Dashboard profesional con métricas de uso
└─ Trigger: hired (evento de contratación) → SEQ_LEX_005 (Retention)

RETENTION (Plan Pro/Enterprise):
├─ Grafo de citaciones interactivo (D3.js)
├─ API access (Enterprise 199€/mes)
├─ Cross-vertical bridge: "Tus clientes también usan JarabaEmpleo"
├─ Health Score visible en dashboard
└─ Credenciales de especialización legal
```

### 5.3 Recorrido 3: Cross-Vertical (Escalera de Valor Transversal)

```
PUENTES DESDE JarabaLex (Outgoing):
├─ → Emprendimiento: "¿Asesoras startups? Descubre nuestro módulo de emprendimiento"
│   Condición: usuario con >20 búsquedas en tema mercantil + laboral
│   CTA: /emprendimiento
├─ → Fiscal (VeriFACTU): "Gestiona el cumplimiento fiscal de tus clientes"
│   Condición: usuario con búsquedas frecuentes en tema fiscal
│   CTA: /fiscal/dashboard
├─ → Empleabilidad: "¿Buscas empleo en el sector jurídico?"
│   Condición: usuario con journey_state at_risk + inactivo 30 días
│   CTA: /empleabilidad
└─ → Formación: "Certifícate en las últimas reformas normativas"
    Condición: usuario con >50 búsquedas en un tema específico
    CTA: /courses

PUENTES HACIA JarabaLex (Incoming, desde otros verticales):
├─ ← Emprendimiento: bridge "compliance legal para tu startup"
│   Condición: emprendedor en estado conversion con canvas_completeness > 70%
├─ ← Servicios Conecta: bridge "jurisprudencia relevante para tus expedientes"
│   Condición: profesional con expedientes activos en Buzón de Confianza
└─ ← Fiscal: bridge "consultas DGT vinculantes para tus obligaciones"
    Condición: usuario fiscal con búsquedas de normativa tributaria
```

---

## 6. Plan de Implementación por Fases

---

### FASE 0 — Landing Page Pública + Lead Magnet {#fase-0}

**Prioridad:** P0 CRÍTICA | **Estimación:** 25–30h | **Gaps:** G1, G2

**Descripción:** Crear la landing page pública del vertical JarabaLex con las 9 secciones estándar de la plataforma, un lead magnet de diagnóstico legal gratuito, y la ruta de registro específica del vertical. Es el punto de entrada para toda la adquisición orgánica y de pago.

#### 6.0.1 Archivos a Crear

| Archivo | Descripción |
|---------|-------------|
| `ecosistema_jaraba_theme/templates/page--jarabalex.html.twig` | Template zero-region para la landing page. Incluye `_header.html.twig`, `_footer.html.twig`, `_copilot-fab.html.twig`. Body classes: `page-jarabalex`, `vertical-landing`, `full-width-layout`. |
| `jaraba_legal_intelligence/src/Controller/LegalLandingController.php` | Controlador para `/jarabalex` y `/jarabalex/diagnostico-legal`. Método `landing()` con render array vacío (zero-region, variables via `hook_preprocess_page()`). Método `diagnosticoLegal()` para el lead magnet. |
| `jaraba_legal_intelligence/templates/legal-diagnostico.html.twig` | Template del diagnóstico legal gratuito: formulario 5 preguntas + informe IA. |
| `jaraba_legal_intelligence/templates/partials/_legal-diagnostico-form.html.twig` | Parcial reutilizable del formulario de diagnóstico. |
| `jaraba_legal_intelligence/templates/partials/_legal-diagnostico-result.html.twig` | Parcial reutilizable del resultado del diagnóstico. |
| `jaraba_legal_intelligence/js/legal-diagnostico.js` | JS para el formulario de diagnóstico (fetch async, animación resultado). |
| `jaraba_legal_intelligence/scss/_diagnostico.scss` | SCSS para la página de diagnóstico legal. |

#### 6.0.2 Archivos a Modificar

| Archivo | Modificación |
|---------|-------------|
| `ecosistema_jaraba_core/src/Controller/VerticalLandingController.php` | Añadir método `jarabalex()` con 9 secciones: hero (azul corporativo #1E3A5F), pain_points (4 dolores), steps (3 pasos), features (6 capacidades IA), social_proof, lead_magnet (link a `/jarabalex/diagnostico-legal`), pricing (3 planes), faq (8 preguntas), final_cta. |
| `ecosistema_jaraba_core/ecosistema_jaraba_core.routing.yml` | Añadir ruta `ecosistema_jaraba_core.landing_jarabalex`: path `/jarabalex`, controller `VerticalLandingController::jarabalex`, `_access: TRUE`. |
| `jaraba_legal_intelligence/jaraba_legal_intelligence.routing.yml` | Añadir ruta `jaraba_legal.diagnostico`: path `/jarabalex/diagnostico-legal`, controller `LegalLandingController::diagnosticoLegal`, `_access: TRUE`. |
| `ecosistema_jaraba_theme/scss/components/_landing-sections.scss` | Añadir modifier `.vertical-landing--jarabalex` con gradiente `#1E3A5F → #0F1F33`, accent `#C8A96E` (gold). |
| `ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme` | En `hook_preprocess_html()`: añadir match para rutas `ecosistema_jaraba_core.landing_jarabalex` y `jaraba_legal.diagnostico` → body class `page-jarabalex`. En `hook_theme_suggestions_page_alter()`: añadir template suggestion `page__jarabalex`. |
| `jaraba_legal_intelligence/jaraba_legal_intelligence.module` | Añadir en `hook_preprocess_html()` las rutas de landing → body classes. Añadir `hook_preprocess_page()` para inyectar variables de la landing (zero-region). |
| `jaraba_legal_intelligence/jaraba_legal_intelligence.libraries.yml` | Añadir library `legal.diagnostico` con `legal-diagnostico.js` y dependencia `legal-intelligence.css`. |

#### 6.0.3 Contenido de la Landing (9 Secciones)

**Hero:**
- Título: `{% trans %}Inteligencia legal con IA al alcance de todos{% endtrans %}`
- Subtítulo: `{% trans %}Jurisprudencia nacional y europea, búsqueda semántica, alertas inteligentes y citaciones automatizadas{% endtrans %}`
- CTA primario: `{% trans %}Empieza gratis{% endtrans %}` → `/registro/jarabalex`
- CTA secundario: `{% trans %}Ver demo{% endtrans %}` → modal video
- Icono: `{{ jaraba_icon('verticals', 'scale', { variant: 'duotone', size: '48px' }) }}`

**Pain Points (4):**
1. `{% trans %}Búsqueda manual en bases de datos desconectadas{% endtrans %}`
2. `{% trans %}Alertas normativas que llegan tarde o nunca{% endtrans %}`
3. `{% trans %}Citaciones que consumen horas de formateo{% endtrans %}`
4. `{% trans %}Coste prohibitivo de herramientas premium{% endtrans %}`

**Steps (3):**
1. `{% trans %}Busca{% endtrans %}` — Búsqueda semántica con IA en 8 fuentes oficiales
2. `{% trans %}Analiza{% endtrans %}` — Pipeline NLP extrae entidades, clasifica y resume
3. `{% trans %}Cita{% endtrans %}` — Citaciones automáticas en 4 formatos jurídicos

**Features (6):**
1. Búsqueda semántica (Qdrant + embeddings 3072D)
2. 8 fuentes oficiales (CENDOJ, BOE, DGT, TEAC, EUR-Lex, CURIA, HUDOC, EDPB)
3. Alertas inteligentes (10 tipos, 4 severidades)
4. Citaciones en 4 formatos (formal, resumida, bibliográfica, nota al pie)
5. Digest semanal personalizado
6. Grafo de citaciones interactivo (D3.js)

**Lead Magnet:**
- Título: `{% trans %}Diagnóstico Legal Gratuito{% endtrans %}`
- Descripción: `{% trans %}Descubre en 2 minutos las áreas de riesgo legal más relevantes para tu actividad{% endtrans %}`
- CTA: `{% trans %}Hacer diagnóstico{% endtrans %}` → `/jarabalex/diagnostico-legal`

**Pricing (3 planes):**

| | Free | Starter 49€/mes | Pro 99€/mes |
|---|---|---|---|
| Búsquedas | 10/mes | 50/mes | Ilimitadas |
| Fuentes | 2 (CENDOJ, BOE) | Todas | Todas |
| Alertas | 1 | 5 | 20 |
| Marcadores | 10 | 100 | Ilimitados |
| Citaciones | ❌ | ❌ | ✅ 4 formatos |
| Digest | ❌ | ❌ | ✅ Semanal |
| API | ❌ | ❌ | ❌ (Enterprise) |

#### 6.0.4 Directrices de Aplicación

- **ZERO-REGION-001/002/003**: Landing vía `hook_preprocess_page()`, controller retorna `['#type' => 'markup', '#markup' => '']`.
- **I18N-001**: Todo texto con `{% trans %}...{% endtrans %}`.
- **SCSS-001**: `@use` en todos los imports. Variables con `var(--ej-legal-*, fallback)`.
- **P4-COLOR-001**: Colores de la paleta Jaraba. Primary: `var(--ej-legal-primary, #1E3A5F)`, accent: `var(--ej-legal-accent, #C8A96E)`.
- **P4-EMOJI-001**: `jaraba_icon()` para todos los iconos, nunca emojis.
- **NAV-002**: Copilot FAB incluido en `_header.html.twig`, no en cada template.

---

### FASE 1 — Clean Page Templates (Zero Region) {#fase-1}

**Prioridad:** P0 CRÍTICA | **Estimación:** 10–15h | **Gaps:** Consolidación

**Descripción:** Verificar y consolidar que TODAS las rutas de `jaraba_legal_intelligence` rendericen a través del template `page--legal.html.twig` (zero-region) y nunca caigan al `page.html.twig` genérico de Drupal. Verificar que `page--legal-compliance.html.twig` cubra las rutas de `jaraba_legal` (compliance).

#### 6.1.1 Verificaciones

1. Mapear TODAS las 20+ rutas de `jaraba_legal_intelligence.routing.yml` contra template suggestions activas.
2. Confirmar que `hook_theme_suggestions_page_alter()` en `ecosistema_jaraba_theme.theme` cubre:
   - `jaraba_legal.search` → `page__legal`
   - `jaraba_legal.resolution` → `page__legal`
   - `jaraba_legal.dashboard` → `page__legal`
   - `jaraba_legal.public_summary` → `page__legal`
   - `jaraba_legal.cite` → `page__legal`
   - `jaraba_legal.similar` → `page__legal`
   - `jaraba_legal_intelligence.settings` → `page__legal`
   - `jaraba_legal.admin_dashboard` → `page__legal`
3. Verificar que el body class `page--legal` se aplica en todas las rutas.
4. Verificar que `{{ clean_content }}` se renderiza correctamente en cada ruta.

#### 6.1.2 Directrices

- **ZERO-REGION-001**: Variables SOLO via `hook_preprocess_page()`.
- **ZERO-REGION-002**: NUNCA pasar entidades como claves sin `#` en render arrays.
- **ZERO-REGION-003**: `#attached` del controller NO se procesa en zero-region; usar `$variables['#attached']` en hook.
- Referencia canónica: `jaraba_verifactu.module`.

---

### FASE 2 — Sistema Modal en Acciones CRUD {#fase-2}

**Prioridad:** P1 ALTA | **Estimación:** 10–12h | **Gap:** G16

**Descripción:** Auditar y corregir todos los enlaces de acción CRUD (crear alerta, editar marcador, ver resolución completa, insertar citación) para que se abran en modales del Drupal Dialog API, evitando que el usuario abandone la página.

#### 6.2.1 Templates a Auditar

| Template | Acciones CRUD |
|----------|--------------|
| `legal-search-results.html.twig` | Ver detalle resolución, Guardar marcador, Crear alerta |
| `legal-professional-dashboard.html.twig` | Editar alerta, Eliminar marcador, Ver citación |
| `_resolution-card.html.twig` | Ver detalle, Citar, Buscar similares |
| `_alert-card.html.twig` | Editar, Eliminar, Ver resultados |
| `legal-citation-insert.html.twig` | Insertar en expediente |

#### 6.2.2 Implementación

Para cada enlace CRUD:
```twig
<a href="{{ url }}"
   class="use-ajax"
   data-dialog-type="modal"
   data-dialog-options='{"width": 800, "title": "{% trans %}Detalle de Resolución{% endtrans %}"}'
>
  {% trans %}Ver detalle{% endtrans %}
</a>
```

#### 6.2.3 Archivos a Modificar

| Archivo | Modificación |
|---------|-------------|
| `jaraba_legal_intelligence.libraries.yml` | Añadir library `legal.modal-actions` con dependencia `core/drupal.dialog.ajax`. |
| `jaraba_legal_intelligence.module` | En `hook_page_attachments_alter()`: adjuntar `modal-actions` library en todas las rutas del módulo. |
| Todos los templates con enlaces CRUD | Añadir `class="use-ajax" data-dialog-type="modal"`. |

---

### FASE 3 — SCSS Compliance y Design Tokens {#fase-3}

**Prioridad:** P1 ALTA | **Estimación:** 12–15h | **Gaps:** G13, SCSS audit

**Descripción:** Auditar todos los archivos SCSS de `jaraba_legal_intelligence` para cumplimiento de directrices: reemplazar `rgba()` por `color-mix()`, verificar `var(--ej-*)` con fallbacks correctos, crear Design Token Config Entity, compilar CSS.

#### 6.3.1 Archivos a Crear

| Archivo | Descripción |
|---------|-------------|
| `ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.design_token_config.vertical_jarabalex.yml` | Tokens de diseño completos para JarabaLex: colores (primary #1E3A5F, accent #C8A96E, secondary #8B7355), tipografía (Libre Baskerville headings, Inter body), spacing, effects, component variants (header: classic, hero: split, cards: profile-formal, footer: corporate). |

#### 6.3.2 Auditoría SCSS

Revisar los 10 archivos SCSS de `jaraba_legal_intelligence/scss/`:

| Archivo | Verificar |
|---------|-----------|
| `_variables-legal.scss` | ✅ Ya usa CSS custom properties `--ej-legal-*` + `:root {}` block |
| `_search.scss` | Reemplazar `rgba()` → `color-mix()`. Verificar `var()` fallbacks. |
| `_results.scss` | Reemplazar `rgba()` → `color-mix()`. |
| `_resolution-detail.scss` | Verificar BEM. |
| `_facets.scss` | Verificar tokens federados. |
| `_alerts.scss` | Reemplazar `rgba()` → `color-mix()`. |
| `_digest-email.scss` | Verificar inline styles (email). |
| `_admin.scss` | Verificar tokens. |
| `_public-summary.scss` | Verificar SEO styles. |
| `main.scss` | Verificar `@use` (no `@import`). |

#### 6.3.3 Compilación

```bash
lando ssh -c "cd /app/web/modules/custom/jaraba_legal_intelligence && /user/.nvm/versions/node/v20.20.0/bin/npx sass scss/main.scss:css/legal-intelligence.css --style=compressed --no-source-map"
lando drush cr
```

#### 6.3.4 Directrices

- **P4-COLOR-001/002/003**: Solo paleta Jaraba. `color-mix()` para variantes.
- **SCSS-001**: `@use` crea scope aislado — cada partial DEBE incluir `@use '../variables' as *;` (o `@use 'variables-legal' as legal;`).
- **Arquitectura Theming Master v2.1**: Módulos satélite NUNCA definen `$ej-*`, solo consumen `var(--ej-*)`.

---

### FASE 4 — Feature Gating por Plan (Escalera de Valor) {#fase-4}

**Prioridad:** P0 CRÍTICA | **Estimación:** 20–25h | **Gaps:** G3, G17

**Descripción:** Implementar el servicio de feature gating que hace cumplir los límites definidos en las 9 configuraciones FreemiumVerticalLimit existentes. Actualmente `LegalSearchService::checkPlanLimits()` retorna `allowed: TRUE` incondicionalmente — esta es la brecha más crítica para la monetización.

#### 6.4.1 Archivos a Crear

| Archivo | Descripción |
|---------|-------------|
| `ecosistema_jaraba_core/src/Service/JarabaLexFeatureGateService.php` | Servicio principal de feature gating. Constructor: `UpgradeTriggerService`, `Connection`, `AccountProxyInterface`, `LoggerInterface`. Métodos: `check(int $userId, string $featureKey, ?string $plan): FeatureGateResult`, `recordUsage(int $userId, string $featureKey): void`, `getUserPlan(int $userId): string`. Feature keys: `searches_per_month`, `max_alerts`, `max_bookmarks`, `citation_insert` (Pro+), `digest_access` (Pro+), `api_access` (Enterprise). |
| Schema update en `ecosistema_jaraba_core.install` | Crear tabla `{jarabalex_feature_usage}` con campos: `id` (serial), `user_id` (int), `feature_key` (varchar 64), `usage_date` (varchar 10, YYYY-MM-DD), `usage_count` (int, default 0). Índices: `user_feature_date`, `feature_date`. |

#### 6.4.2 Archivos a Modificar

| Archivo | Modificación |
|---------|-------------|
| `jaraba_legal_intelligence/src/Service/LegalSearchService.php` | Reemplazar el `checkPlanLimits()` stub (línea ~804) con llamada real a `JarabaLexFeatureGateService::check($userId, 'searches_per_month')`. Si `denied`, retornar respuesta con `upgrade_message` del config entity. |
| `jaraba_legal_intelligence/src/Service/LegalAlertService.php` | Antes de crear alerta, llamar `check($userId, 'max_alerts')`. |
| `jaraba_legal_intelligence/src/Service/LegalCitationService.php` | Antes de insertar citación, llamar `check($userId, 'citation_insert')`. |
| `jaraba_legal_intelligence/src/Service/LegalDigestService.php` | Antes de enviar digest, verificar `check($userId, 'digest_access')`. |
| `ecosistema_jaraba_core/ecosistema_jaraba_core.services.yml` | Registrar `ecosistema_jaraba_core.jarabalex_feature_gate` con dependencias. |
| `jaraba_billing/src/Service/FeatureAccessService.php` | Añadir `jaraba_legal_intelligence` al array `$allAddons` de `getAvailableAddons()`. |

#### 6.4.3 Lógica de Feature Keys

| Feature Key | Free | Starter | Pro | Enterprise |
|-------------|------|---------|-----|-----------|
| `searches_per_month` | 10 | 50 | -1 | -1 |
| `max_alerts` | 1 | 5 | 20 | -1 |
| `max_bookmarks` | 10 | 100 | -1 | -1 |
| `citation_insert` | 0 | 0 | -1 | -1 |
| `digest_access` | 0 | 0 | -1 | -1 |
| `api_access` | 0 | 0 | 0 | -1 |

(Los 3 primeros ya tienen config entity; los 3 últimos necesitan FreemiumVerticalLimit adicionales.)

#### 6.4.4 Configs Adicionales a Crear

| Config ID | Plan | Feature | Limit |
|-----------|------|---------|-------|
| `jarabalex_free_citation_insert` | free | citation_insert | 0 |
| `jarabalex_starter_citation_insert` | starter | citation_insert | 0 |
| `jarabalex_profesional_citation_insert` | profesional | citation_insert | -1 |
| `jarabalex_free_digest_access` | free | digest_access | 0 |
| `jarabalex_starter_digest_access` | starter | digest_access | 0 |
| `jarabalex_profesional_digest_access` | profesional | digest_access | -1 |
| `jarabalex_free_api_access` | free | api_access | 0 |
| `jarabalex_starter_api_access` | starter | api_access | 0 |
| `jarabalex_profesional_api_access` | profesional | api_access | 0 |

(Enterprise se gestiona por plan separado, no por freemium limit.)

---

### FASE 5 — Upgrade Triggers y Upsell Contextual IA {#fase-5}

**Prioridad:** P0 CRÍTICA | **Estimación:** 15–18h | **Gap:** G14

**Descripción:** Integrar el sistema de upgrade triggers para que cada denegación de feature gate dispare un trigger de upsell. Implementar soft suggestions contextuales en el copilot.

#### 6.5.1 Archivos a Modificar

| Archivo | Modificación |
|---------|-------------|
| `jaraba_billing/src/Service/UpgradeTriggerService.php` | Añadir tipos de trigger: `legal_search_limit_reached`, `legal_alert_limit_reached`, `legal_bookmark_limit_reached`, `legal_citation_not_available`, `legal_digest_not_available`, `legal_api_not_available`. |
| `ecosistema_jaraba_core/src/Service/JarabaLexFeatureGateService.php` | Después de `denied`, llamar `$this->upgradeTriggerService->fire('legal_{featureKey}_limit_reached', $userId, $context)`. |
| `jaraba_legal_intelligence/src/Service/LegalSearchService.php` | En resultado `denied`, incluir `upgrade_url`, `upgrade_plan`, `upgrade_message` del trigger. |
| `jaraba_legal_intelligence/src/Service/LegalCopilotBridgeService.php` | Añadir método `getSoftSuggestion(int $userId): ?array` — evalúa journey state y plan para sugerir upgrade si `plan === 'free'` y `journey_phase >= 3`. |

---

### FASE 6 — Email Sequences Automatizadas {#fase-6}

**Prioridad:** P1 ALTA | **Estimación:** 20–25h | **Gaps:** G8, G20

**Descripción:** Crear el servicio de secuencias de email y 5 templates MJML para el ciclo de vida del usuario legal, siguiendo exactamente el patrón de `EmployabilityEmailSequenceService`.

#### 6.6.1 Archivos a Crear

| Archivo | Descripción |
|---------|-------------|
| `ecosistema_jaraba_core/src/Service/JarabaLexEmailSequenceService.php` | 5 secuencias: `SEQ_LEX_001` (Onboarding Legal), `SEQ_LEX_002` (Re-engagement Inactivos), `SEQ_LEX_003` (Upsell Free→Starter), `SEQ_LEX_004` (Upsell Starter→Pro), `SEQ_LEX_005` (Retention Post-Suscripción). Métodos: `enroll(int $userId, string $sequenceKey)`, `ensureSequences()`, `getAvailableSequences()`. |
| `jaraba_email/templates/mjml/jarabalex/seq_onboarding_legal.mjml` | SEQ_LEX_001: Bienvenida + primeros pasos + link diagnóstico. Trigger: registro completado. |
| `jaraba_email/templates/mjml/jarabalex/seq_reengagement.mjml` | SEQ_LEX_002: "Hay X nuevas resoluciones en tu área". Trigger: 7 días sin búsquedas. |
| `jaraba_email/templates/mjml/jarabalex/seq_upsell_starter.mjml` | SEQ_LEX_003: Beneficios de Starter + descuento primer mes. Trigger: 3ª búsqueda o 10ª búsqueda (límite). |
| `jaraba_email/templates/mjml/jarabalex/seq_upsell_pro.mjml` | SEQ_LEX_004: Beneficios de Pro (citaciones, digest). Trigger: intento de citar o 5ª alerta. |
| `jaraba_email/templates/mjml/jarabalex/seq_retention.mjml` | SEQ_LEX_005: "Tu resumen del mes" + métricas de uso + sugerencias. Trigger: 30 días suscrito. |

#### 6.6.2 Archivos a Modificar

| Archivo | Modificación |
|---------|-------------|
| `jaraba_legal_intelligence/jaraba_legal_intelligence.module` | En `hook_entity_insert()`: si es `user` con vertical `jarabalex`, enrollar en `SEQ_LEX_001`. |
| `ecosistema_jaraba_core/ecosistema_jaraba_core.services.yml` | Registrar `ecosistema_jaraba_core.jarabalex_email_sequence`. |
| `jaraba_email/src/Service/TemplateLoaderService.php` | Registrar las 5 plantillas MJML del directorio `jarabalex/`. |

#### 6.6.3 Directrices

- **EMAIL-001**: Usar MJML, nunca plain HTML.
- **I18N-001**: Todos los textos en MJML con `{{ t('Texto') }}`.
- Todas las secuencias: `is_system = TRUE`, `is_active = TRUE`, `vertical = 'jarabalex'`.

---

### FASE 7 — CRM Integration y Pipeline {#fase-7}

**Prioridad:** P2 MEDIA | **Estimación:** 12–15h | **Gap:** G15

**Descripción:** Sincronizar las transiciones de estado del usuario legal con el pipeline CRM: lead → prospect → client → advocate. Mapear entidades de legal a oportunidades CRM.

#### 6.7.1 Archivos a Modificar

| Archivo | Modificación |
|---------|-------------|
| `jaraba_legal_intelligence/jaraba_legal_intelligence.module` | Añadir función `_jaraba_legal_intelligence_sync_to_crm()` que mapea: `registered → lead`, `first_search → prospect`, `plan_upgrade → client`, `api_enabled → enterprise_client`. Llamar desde `hook_entity_insert()` y `hook_entity_update()` para `journey_state`. |
| `jaraba_legal_intelligence/jaraba_legal_intelligence.module` | Añadir `_jaraba_legal_intelligence_ensure_crm_contact()` que crea/vincula contacto CRM desde el userId. |

#### 6.7.2 Mapeo Pipeline

| Evento Legal | Etapa CRM | Pipeline |
|-------------|-----------|---------|
| Registro en JarabaLex | `lead` | `jarabalex_pipeline` |
| Primera búsqueda | `prospect` | |
| 10ª búsqueda (Free limit) | `qualified_lead` | |
| Upgrade a Starter | `client` | |
| Upgrade a Pro | `premium_client` | |
| Upgrade a Enterprise | `enterprise_client` | |
| Churn (cancelación) | `lost` | |

---

### FASE 8 — Cross-Vertical Value Bridges {#fase-8}

**Prioridad:** P2 MEDIA | **Estimación:** 15–18h | **Gap:** G9

**Descripción:** Implementar puentes direccionales desde JarabaLex hacia otros verticales para maximizar el valor del cliente dentro del SaaS.

#### 6.8.1 Archivos a Crear

| Archivo | Descripción |
|---------|-------------|
| `ecosistema_jaraba_core/src/Service/JarabaLexCrossVerticalBridgeService.php` | 4 puentes outgoing: `emprendimiento` (mercantil+laboral → startups), `fiscal` (tema fiscal → VeriFACTU), `empleabilidad` (at_risk → empleo jurídico), `formacion` (experto en tema → certificación). Métodos: `evaluateBridges(int $userId): array` (max 2), `presentBridge(int $userId, string $bridgeId): array`, `trackBridgeResponse(int $userId, string $bridgeId, string $response): void`. |

#### 6.8.2 Definición de Puentes

| Bridge ID | Vertical Destino | Condición | Prioridad | Icono | Color | CTA URL |
|-----------|-----------------|-----------|-----------|-------|-------|---------|
| `emprendimiento` | emprendimiento | `>20 búsquedas en tema mercantil+laboral` | 10 | `rocket` | `var(--ej-color-impulse)` | `/emprendimiento` |
| `fiscal` | fiscal | `>10 búsquedas en tema fiscal` | 20 | `calculator` | `var(--ej-color-warning)` | `/fiscal/dashboard` |
| `empleabilidad` | empleabilidad | `journey_state = at_risk AND inactivo 30 días` | 30 | `briefcase` | `var(--ej-color-innovation)` | `/empleabilidad` |
| `formacion` | formacion | `>50 búsquedas en un tema específico` | 40 | `book` | `var(--ej-color-success)` | `/courses` |

#### 6.8.3 Archivos a Modificar

| Archivo | Modificación |
|---------|-------------|
| `ecosistema_jaraba_core/ecosistema_jaraba_core.services.yml` | Registrar `ecosistema_jaraba_core.jarabalex_cross_vertical_bridge`. |
| `jaraba_legal_intelligence/src/Controller/LegalDashboardController.php` | Inyectar bridges en el contexto del dashboard (fail-open: si servicio no existe, no mostrar bridges). |
| `jaraba_legal_intelligence/templates/legal-professional-dashboard.html.twig` | Renderizar bridge cards con `jaraba_icon()` y `btn--outline`. |

#### 6.8.4 Puentes INCOMING (responsabilidad de otros verticales)

Estos puentes se definen en los servicios CrossVerticalBridge de sus respectivos verticales:

| Vertical Origen | Bridge → JarabaLex | Responsable |
|----------------|-------------------|-------------|
| Emprendimiento | "Compliance legal para tu startup" | `EmprendimientoCrossVerticalBridgeService` |
| Servicios Conecta | "Jurisprudencia para tus expedientes" | `ServiciosConectaCrossVerticalBridgeService` |
| Fiscal | "Consultas DGT vinculantes" | Nuevo puente en fiscal |

---

### FASE 9 — Journey Definition + AI Progression Proactiva {#fase-9}

**Prioridad:** P0 CRÍTICA | **Estimación:** 25–30h | **Gaps:** G4, G5

**Descripción:** Crear la definición completa del journey del usuario legal y el servicio de progresión proactiva con 7 reglas de IA que impulsan al usuario por el embudo. Este es el motor que automatiza la experiencia.

#### 6.9.1 Archivos a Crear

| Archivo | Descripción |
|---------|-------------|
| `jaraba_journey/src/JourneyDefinition/JarabaLexJourneyDefinition.php` | 3 avatares: `legal_researcher` (investigador jurídico), `abogado` (profesional ejerciente), `gestor_compliance` (responsable cumplimiento). 5 estados: discovery → activation → engagement → conversion → retention. |
| `ecosistema_jaraba_core/src/Service/JarabaLexJourneyProgressionService.php` | 7 reglas proactivas. Cache via State API (`jarabalex_proactive_pending_{userId}`, TTL 3600s). Métodos: `evaluate(int $userId): ?array`, `getPendingAction(int $userId): ?array`, `dismissAction(int $userId, string $ruleId): void`, `evaluateBatch(): int`. |

#### 6.9.2 Journey: Avatar `legal_researcher`

| Estado | Pasos | IA Intervention | Evento Transición |
|--------|-------|-----------------|-------------------|
| **discovery** | 1. Registro + diagnóstico legal | `diagnostic_analysis` | `profile_setup_complete` |
| **activation** | 2. Primera búsqueda semántica, 3. Configurar primera alerta | `search_guidance`, `alert_setup_assistant` | `first_alert_configured` |
| **engagement** | 4. Guardar marcadores + revisiones, 5. Usar digest semanal | `research_companion`, `digest_personalization` | `tenth_search_completed` |
| **conversion** | 6. Upgrade a plan de pago, 7. Insertar primera citación | `upgrade_advisor`, `citation_formatter` | `first_citation_inserted` |
| **retention** | 8. Explorar grafo citaciones, 9. Compartir research | `knowledge_graph_guide`, `collaboration_facilitator` | — |

**Cross-sell hooks:** `profile_setup_complete`, `search_limit_reached`, `citation_needed`, `expertise_detected`.

**Copilot modes per step:** `legal_researcher`, `search_tutor`, `alert_manager`, `citation_expert`, `digest_curator`, `graph_analyst`, `faq`.

#### 6.9.3 Journey: Avatar `abogado`

| Estado | Pasos | IA Intervention | Evento Transición |
|--------|-------|-----------------|-------------------|
| **discovery** | 1. Registro con especialidad jurídica | `practice_area_detection` | `specialty_configured` |
| **activation** | 2. Búsqueda por caso, 3. Alertas por cliente | `case_research_assistant`, `client_alert_setup` | `first_case_research` |
| **engagement** | 4. Citaciones en expedientes, 5. Digest profesional | `citation_in_context`, `professional_digest` | `fifth_citation` |
| **conversion** | 6. Upgrade Pro/Enterprise | `roi_calculator`, `practice_optimizer` | `enterprise_api_activated` |
| **retention** | 7. API integration, 8. Cross-reference | `api_integration_guide`, `cross_reference_expert` | — |

#### 6.9.4 Reglas Proactivas (JourneyProgressionService)

| Rule ID | Estado | Condición | Mensaje | CTA | Canal | Modo Copilot |
|---------|--------|-----------|---------|-----|-------|-------------|
| `inactivity_discovery` | discovery | 3 días sin actividad | `{% trans %}Tienes un diagnóstico legal pendiente{% endtrans %}` | `/jarabalex/diagnostico-legal` | `fab_dot` | `legal_researcher` |
| `incomplete_setup` | activation | Sin alertas configuradas | `{% trans %}Configura tu primera alerta para no perderte cambios normativos{% endtrans %}` | `/legal/dashboard#alerts` | `fab_expand` | `alert_manager` |
| `search_stagnation` | activation | Perfil completo pero 0 búsquedas | `{% trans %}Hay resoluciones nuevas en tu área de interés{% endtrans %}` | `/legal/search` | `fab_badge` | `search_tutor` |
| `engagement_boost` | engagement | 5 búsquedas sin guardar resultados | `{% trans %}¿Sabías que puedes guardar y organizar tus hallazgos?{% endtrans %}` | `/legal/dashboard#bookmarks` | `fab_dot` | `legal_researcher` |
| `citation_opportunity` | engagement | >10 marcadores sin citaciones | `{% trans %}Convierte tus hallazgos en citaciones profesionales{% endtrans %}` | Upgrade modal | `fab_expand` | `citation_expert` |
| `upgrade_suggestion` | conversion | Plan free + >8 búsquedas (80% límite) | `{% trans %}Estás cerca del límite. Pasa a Starter y multiplica tus búsquedas por 5{% endtrans %}` | `/billing/upgrade` | `fab_expand` | `faq` |
| `expertise_expansion` | retention | >30 días suscrito + alta actividad | `{% trans %}¿Asesoras empresas? Descubre nuestras herramientas de emprendimiento{% endtrans %}` | Cross-vertical bridge | `fab_dot` | `legal_researcher` |

#### 6.9.5 Archivos a Modificar

| Archivo | Modificación |
|---------|-------------|
| `ecosistema_jaraba_core/ecosistema_jaraba_core.services.yml` | Registrar `ecosistema_jaraba_core.jarabalex_journey_progression`. |
| `jaraba_legal_intelligence/jaraba_legal_intelligence.routing.yml` | Añadir `jaraba_legal.api.proactive`: `GET|POST /api/v1/copilot/jarabalex/proactive`. |
| `jaraba_legal_intelligence/src/Controller/LegalSearchController.php` | Añadir métodos `apiGetProactiveAction()` y `apiDismissProactiveAction()`. |

---

### FASE 10 — Health Scores y Métricas Específicas {#fase-10}

**Prioridad:** P1 ALTA | **Estimación:** 18–22h | **Gap:** G6

**Descripción:** Implementar el servicio de health score con 5 dimensiones ponderadas y 8 KPIs verticales, siguiendo el patrón exacto de `EmployabilityHealthScoreService`.

#### 6.10.1 Archivos a Crear

| Archivo | Descripción |
|---------|-------------|
| `ecosistema_jaraba_core/src/Service/JarabaLexHealthScoreService.php` | Métodos: `calculateUserHealth(int $userId): array` (retorna `overall_score`, `category`, `dimensions`), `calculateVerticalKpis(): array` (retorna 8 KPIs con targets). |

#### 6.10.2 Dimensiones de Salud (5, total peso = 1.0)

| Dimensión | Peso | Descripción | Cálculo |
|-----------|------|-------------|---------|
| `search_activity` | 0.30 | Actividad de búsqueda | Búsquedas último mes (max 50) + variedad fuentes (max 30) + recencia (max 20) |
| `alert_management` | 0.20 | Gestión de alertas | Alertas activas (max 40) + respuestas a alertas (max 30) + cobertura temas (max 30) |
| `copilot_engagement` | 0.15 | Uso del copilot | Conversaciones (max 50) + uso 7 días (max 50) |
| `knowledge_organization` | 0.20 | Organización del conocimiento | Marcadores (max 30) + citaciones insertadas (max 40) + carpetas organizadas (max 30) |
| `professional_growth` | 0.15 | Crecimiento profesional | Temas cubiertos (max 40) + EU sources used (max 30) + digest engagement (max 30) |

#### 6.10.3 Categorías de Salud

| Score | Categoría | Significado |
|-------|-----------|-------------|
| >= 80 | `healthy` | Usuario activo y productivo |
| >= 60 | `neutral` | Uso regular, margen de mejora |
| >= 40 | `at_risk` | Desengagement detectado |
| < 40 | `critical` | Alto riesgo de churn |

#### 6.10.4 KPIs del Vertical (8)

| KPI Key | Target | Unidad | Dirección | Label |
|---------|--------|--------|-----------|-------|
| `search_adoption_rate` | 60 | % | higher | `{% trans %}Tasa de adopción de búsqueda{% endtrans %}` |
| `alert_response_rate` | 70 | % | higher | `{% trans %}Tasa de respuesta a alertas{% endtrans %}` |
| `citation_insertion_rate` | 30 | % | higher | `{% trans %}Tasa de inserción de citaciones{% endtrans %}` |
| `activation_rate` | 50 | % | higher | `{% trans %}Tasa de activación (primera búsqueda en 7 días){% endtrans %}` |
| `nps` | 55 | score | higher | `{% trans %}NPS del vertical{% endtrans %}` |
| `arpu` | 65 | EUR/mes | higher | `{% trans %}ARPU{% endtrans %}` |
| `conversion_free_paid` | 12 | % | higher | `{% trans %}Conversión Free→Pago{% endtrans %}` |
| `churn_rate` | 4 | % | lower | `{% trans %}Tasa de baja mensual{% endtrans %}` |

#### 6.10.5 Archivos a Modificar

| Archivo | Modificación |
|---------|-------------|
| `ecosistema_jaraba_core/ecosistema_jaraba_core.services.yml` | Registrar `ecosistema_jaraba_core.jarabalex_health_score`. |
| `jaraba_legal_intelligence/src/Controller/LegalDashboardController.php` | Inyectar health score en el contexto del dashboard profesional. |
| `jaraba_legal_intelligence/templates/legal-professional-dashboard.html.twig` | Renderizar widget de health score con SVG ring + dimensiones. |

---

### FASE 11 — Copilot Agent Dedicado {#fase-11}

**Prioridad:** P1 ALTA | **Estimación:** 20–25h | **Gaps:** G7, G11

**Descripción:** Crear un agente Copilot dedicado para JarabaLex que extienda `BaseAgent` con 6 modos especializados, integrando el `LegalCopilotBridgeService` existente como fuente de RAG.

#### 6.11.1 Archivos a Crear

| Archivo | Descripción |
|---------|-------------|
| `jaraba_ai_agents/src/Agent/JarabaLexCopilotAgent.php` | Extiende `BaseAgent`. Agent ID: `jarabalex_copilot`. 6 modos con keywords, system prompts en español, y temperaturas diferenciadas. Integra `LegalCopilotBridgeService::getRelevantKnowledge()` en el contexto unificado. |

#### 6.11.2 Modos del Copilot (6)

| Mode ID | Label | Keywords | Temperatura |
|---------|-------|----------|-------------|
| `legal_researcher` | `{% trans %}Investigador Jurídico{% endtrans %}` | `buscar, jurisprudencia, sentencia, resolucion, consulta, doctrina, normativa` | 0.5 |
| `alert_manager` | `{% trans %}Gestor de Alertas{% endtrans %}` | `alerta, notificacion, cambio, reforma, derogacion, nueva ley` | 0.4 |
| `citation_expert` | `{% trans %}Experto en Citaciones{% endtrans %}` | `citar, citacion, formato, referencia, bibliografica, nota al pie` | 0.3 |
| `document_coach` | `{% trans %}Coach de Documentos{% endtrans %}` | `documento, informe, escrito, recurso, demanda, dictamen` | 0.6 |
| `eu_specialist` | `{% trans %}Especialista UE{% endtrans %}` | `europa, EU, TJUE, TEDH, directiva, reglamento, primacia, CURIA` | 0.5 |
| `faq` | `{% trans %}Preguntas Frecuentes{% endtrans %}` | `como, donde, cuando, plataforma, funciona, ayuda, plan, precio` | 0.3 |

Default (sin keyword match): `faq`.

#### 6.11.3 System Prompts (por modo)

Cada modo tiene un prompt en español que:
1. Define el rol y expertise del copilot
2. Establece las fuentes de conocimiento disponibles (8 fuentes oficiales)
3. Incluye la regla LEGAL-RAG-001 (disclaimer obligatorio + citaciones verificables)
4. Define el tono (profesional, preciso, con lenguaje jurídico accesible)

#### 6.11.4 Soft Upsell Logic

```php
private function getSoftSuggestion(int $userId): ?array {
    $plan = $this->featureGate->getUserPlan($userId);
    if ($plan !== 'free') return NULL;

    $journeyPhase = $this->getJourneyPhase($userId);
    if ($journeyPhase < 3) return NULL; // Solo a partir de engagement

    return match(TRUE) {
        $journeyPhase === 3 => [
            'plan' => 'starter',
            'message' => $this->t('Con Starter tendrías 50 búsquedas/mes y 5 alertas.'),
        ],
        $journeyPhase >= 4 => [
            'plan' => 'profesional',
            'message' => $this->t('Con Pro podrías insertar citaciones y recibir el digest semanal.'),
        ],
        default => NULL,
    };
}
```

#### 6.11.5 Archivos a Modificar

| Archivo | Modificación |
|---------|-------------|
| `jaraba_ai_agents/src/Agent/BaseAgent.php` | Añadir entrada en `getVerticalContext()`: `'jarabalex' => 'Sector de inteligencia legal, jurisprudencia y compliance.'`. |
| `jaraba_ai_agents/jaraba_ai_agents.services.yml` | Registrar `jaraba_ai_agents.jarabalex_copilot` con inyección de `LegalCopilotBridgeService`. |

---

### FASE 12 — Avatar Navigation + Funnel Analytics {#fase-12}

**Prioridad:** P1 ALTA | **Estimación:** 15–18h | **Gaps:** G10, G12

**Descripción:** Integrar JarabaLex en el sistema de navegación global por avatar y crear las definiciones de funnel para tracking de conversión.

#### 6.12.1 Avatar Navigation

**Archivos a Modificar:**

| Archivo | Modificación |
|---------|-------------|
| `ecosistema_jaraba_core/src/Service/AvatarNavigationService.php` | Añadir avatar `legal_professional` en `AVATAR_LABELS`. Añadir items en `getItemDefinitions()`: `/legal/search` (Búsqueda), `/legal/dashboard` (Dashboard), `/legal/dashboard#alerts` (Alertas), `/legal/dashboard#bookmarks` (Marcadores). Añadir en `getCrossVerticalItems()`: si usuario legal con búsquedas fiscales → ofrecer `/fiscal/dashboard`. Añadir en anonymous navigation: `ecosistema_jaraba_core.landing_jarabalex`. |

#### 6.12.2 Funnel Analytics

**Archivos a Crear:**

| Archivo | Descripción |
|---------|-------------|
| `ecosistema_jaraba_core/config/install/jaraba_analytics.funnel_definition.jarabalex_acquisition.yml` | Funnel de adquisición: `landing_visit → diagnostico_start → diagnostico_complete → registration → first_search`. Conversion window: 168h (7 días). |
| `ecosistema_jaraba_core/config/install/jaraba_analytics.funnel_definition.jarabalex_activation.yml` | Funnel de activación: `registration → first_search → first_alert → first_bookmark → fifth_search`. Conversion window: 336h (14 días). |
| `ecosistema_jaraba_core/config/install/jaraba_analytics.funnel_definition.jarabalex_monetization.yml` | Funnel de monetización: `search_limit_reached → upgrade_modal_shown → checkout_started → payment_completed`. Conversion window: 72h (3 días). |

#### 6.12.3 Tracking Events

| Evento | Trigger | Datos |
|--------|---------|-------|
| `jarabalex_landing_visit` | Landing page view | `source`, `medium` |
| `jarabalex_diagnostico_start` | Inicio diagnóstico | — |
| `jarabalex_diagnostico_complete` | Diagnóstico completado | `risk_areas` |
| `jarabalex_registration` | Registro | `plan` |
| `jarabalex_search` | Búsqueda realizada | `query_type`, `sources_count`, `results_count` |
| `jarabalex_alert_created` | Alerta creada | `alert_type` |
| `jarabalex_bookmark_saved` | Marcador guardado | — |
| `jarabalex_citation_inserted` | Citación insertada | `format` |
| `jarabalex_upgrade_modal` | Modal upgrade mostrado | `trigger`, `current_plan` |
| `jarabalex_upgrade_completed` | Upgrade completado | `from_plan`, `to_plan` |

---

### FASE 13 — QA, Tests y Documentación {#fase-13}

**Prioridad:** P2 MEDIA | **Estimación:** 25–30h | **Gaps:** G18, G19

**Descripción:** Fase de cierre con tests unitarios, de integración y documentación completa.

#### 6.13.1 Tests a Crear

| Test | Tipo | Cobertura |
|------|------|-----------|
| `JarabaLexFeatureGateServiceTest.php` | Unit | `check()`, `recordUsage()`, plan limits |
| `JarabaLexJourneyProgressionServiceTest.php` | Unit | 7 reglas proactivas, cache, dismiss |
| `JarabaLexHealthScoreServiceTest.php` | Unit | 5 dimensiones, categorías, KPIs |
| `JarabaLexEmailSequenceServiceTest.php` | Unit | 5 secuencias, enroll, ensure |
| `JarabaLexCrossVerticalBridgeServiceTest.php` | Unit | 4 bridges, max 2, dismiss |
| `JarabaLexCopilotAgentTest.php` | Unit | 6 modos, keyword detection, soft upsell |
| `JarabaLexJourneyDefinitionTest.php` | Unit | 3 avatares, estados, transiciones |
| `JarabaLexLandingControllerTest.php` | Functional | Landing page, diagnóstico, registro |
| `JarabaLexFeatureGateKernelTest.php` | Kernel | DB table creation, usage recording |

#### 6.13.2 Documentación a Actualizar

| Documento | Actualización |
|-----------|-------------|
| `docs/00_DIRECTRICES_PROYECTO.md` | Incrementar versión. Añadir reglas de JarabaLex en sección 5.8.x. Changelog. |
| `docs/00_DOCUMENTO_MAESTRO_ARQUITECTURA.md` | Incrementar versión. Actualizar módulos en sección 7.1. Changelog. |
| `docs/00_INDICE_GENERAL.md` | Incrementar versión. Blockquote + entrada registro cambios. |
| `docs/tecnicos/aprendizajes/2026-02-16_jarabalex_elevacion_clase_mundial.md` | Nuevo aprendizaje documentando las 14 fases, patrones, y reglas descubiertas. |

#### 6.13.3 Items Futuros (Post-Clase-Mundial)

| Item | Prioridad | Descripción |
|------|-----------|-------------|
| `jaraba_credentials_jarabalex` | P3 | Submódulo de credenciales legales (certificación en áreas del derecho) |
| `JarabaLexExperimentService` | P3 | A/B testing para el vertical |
| Stripe Price IDs | P1 (Ops) | Crear productos en Stripe para los 3 planes |
| Docker NLP Production | P1 (Ops) | Desplegar `docker-compose.legal-nlp.yml` en producción |

---

## 7. Tabla de Correspondencia con Especificaciones Técnicas

| Especificación Técnica (docs/tecnicos/) | Fase(s) Afectadas | Estado |
|----------------------------------------|-------------------|--------|
| `20260215a-178_ServiciosConecta_Legal_Intelligence_Hub_v1_Claude.md` (Doc 178) | F0–F13 | Base de todo el vertical |
| `20260215a-178A_Legal_Intelligence_Hub_EU_Sources_v1_Claude.md` (Annex A) | F9, F11 | EU spiders + merge rank |
| `20260215a-178B_Legal_Intelligence_Hub_Implementation_v1_Claude.md` (Annex B) | F1–F4 | Código de referencia para entities, services, templates |
| `20260201b-178_Platform_Legal_Knowledge_Module_v1_Claude.md` (Doc 178-BOE) | F8, F11 | RAG pipeline + disclaimers (LEGAL-RAG-001) |
| `20260216a-184_Platform_Legal_Terms_v1_Claude.md` (Doc 184) | F7 | Stack compliance legal (ToS, SLA, AUP) |
| `20260216-Elevacion_JarabaLex_Vertical_Independiente_v1.md` | F0 | Config seeds ya implementados |
| `20260215-Plan_Implementacion_Legal_Intelligence_Hub_v1.md` | F0–F13 | Plan original de 10 fases (530-685h) |
| `2026-02-16_jarabalex_elevacion_vertical_independiente.md` (Aprendizaje) | Todas | Reglas VERTICAL-ELEV-001 a 005 |
| `2026-02-16_zero_region_template_pattern.md` (Aprendizaje #88) | F0, F1 | Zero-region mandatory hooks |
| `2026-02-15_empleabilidad_elevacion_10_fases.md` (Aprendizaje) | Todas | Blueprint de 10 fases |
| `2026-02-15_emprendimiento_paridad_empleabilidad_7_gaps.md` (Aprendizaje) | Todas | Checklist de paridad |
| `2026-02-12_phase4_scss_colors_emoji_cleanup.md` (Aprendizaje) | F3 | P4-COLOR-001/002/003, P4-EMOJI-001/002 |
| `2026-02-13_avatar_navigation_contextual.md` (Aprendizaje) | F12 | NAV-001 a NAV-005 |
| `2026-02-05_arquitectura_theming_saas_master.md` (Arquitectura) | F3 | 5-Layer Federated Design Tokens |
| `07_VERTICAL_CUSTOMIZATION_PATTERNS.md` | Todas | 3 niveles customización, Feature Access pattern |
| `DIRECTRICES_DESARROLLO.md` | Todas | Pre-commit checklist |
| `00_FLUJO_TRABAJO_CLAUDE.md` | Todas | Workflow de sesión |

---

## 8. Cumplimiento de Directrices

| Directriz | Cómo se Cumple |
|-----------|---------------|
| **I18N-001** | Todos los textos con `{% trans %}...{% endtrans %}` en Twig, `$this->t()` en PHP, `Drupal.t()` en JS. |
| **SCSS-001** | `@use` (no `@import`). Variables con `var(--ej-legal-*, fallback)`. `color-mix()` para variantes. |
| **P4-COLOR-001/002/003** | Solo paleta Jaraba. JarabaLex usa primary `#1E3A5F` (corporativo), accent `#C8A96E` (gold). Variantes con `color-mix()`. |
| **P4-EMOJI-001/002** | `jaraba_icon()` en Twig. Unicode text escapes en SCSS `content:`. Nunca emojis color. |
| **ZERO-REGION-001/002/003** | Variables via `hook_preprocess_page()`. Controller retorna `['#type' => 'markup', '#markup' => '']`. 3 hooks obligatorios. |
| **LEGAL-RAG-001** | Todo output RAG incluye disclaimer + citaciones verificables. 3 niveles confianza. |
| **VERTICAL-ELEV-001–005** | Config seeds ya implementados. CSS custom props en `:root`. Features en `FEATURE_ADDON_MAP`. |
| **NAV-001–005** | Servicio centralizado. Variables en header, no en cada template. `try/catch` en `Url::fromRoute()`. |
| **ENTITY-001** | Content Entities con `EntityOwnerInterface`, `EntityChangedInterface`, `tenant_id` como `entity_reference`. |
| **DRUPAL11-001** | No redeclarar propiedades tipadas heredadas. No promoted constructor params para propiedades heredadas. |
| **AUDIT-CONS-001** | AccessControlHandler en cada Content Entity (ya implementado en 5 handlers). |
| **AUDIT-PERF-001** | DB indexes en `tenant_id` + campos frecuentes (ya implementado en entities). |
| **Dart Sass moderno** | `package.json` con `sass ^1.71.0`. Compilación `--style=compressed`. `@use` con scope aislado. |
| **Frontend limpio** | Sin `page.content`, sin bloques heredados. Layout full-width mobile-first. |
| **Modal CRUD** | `data-dialog-type="modal"` con `drupal.dialog.ajax` en todas las acciones CRUD. |
| **Textos traducibles** | Modelo SASS con archivos SCSS compilados. Variables CSS inyectables configuradas desde Drupal UI. |
| **Templates parciales** | Parciales con `_` prefix, incluidos via `{% include %}`. Reutilización entre páginas. |
| **Configuración sin código** | Variables de tema configurables desde UI de Drupal. Footer, header, colores desde `theme_settings`. |
| **Tenant sin admin** | Tenant no accede al tema de administración. Solo rutas frontend limpias. |
| **Body classes** | Siempre via `hook_preprocess_html()`, NUNCA `attributes.addClass()` en templates. |
| **Content Entities con Field UI + Views** | Navegación en `/admin/structure` y `/admin/content` para todas las entidades. |

---

## 9. Checklist Pre-Commit por Fase

```
FASE 0 (Landing):
- [ ] VerticalLandingController::jarabalex() con 9 secciones
- [ ] page--jarabalex.html.twig zero-region con Copilot FAB
- [ ] Ruta /jarabalex con _access: TRUE
- [ ] .vertical-landing--jarabalex en _landing-sections.scss
- [ ] Body class page-jarabalex en hook_preprocess_html()
- [ ] Template suggestion page__jarabalex
- [ ] Lead magnet /jarabalex/diagnostico-legal
- [ ] Todos los textos con {% trans %}
- [ ] npm run build + drush cr

FASE 4 (Feature Gating):
- [ ] JarabaLexFeatureGateService registrado en services.yml
- [ ] Tabla {jarabalex_feature_usage} creada en install hook
- [ ] LegalSearchService::checkPlanLimits() llama a FeatureGate
- [ ] LegalAlertService comprueba max_alerts
- [ ] LegalCitationService comprueba citation_insert
- [ ] 9 FreemiumVerticalLimit configs adicionales
- [ ] getAvailableAddons() incluye jaraba_legal_intelligence

FASE 9 (Journey):
- [ ] JarabaLexJourneyDefinition con 3 avatares
- [ ] JarabaLexJourneyProgressionService con 7 reglas
- [ ] API /api/v1/copilot/jarabalex/proactive
- [ ] Cache State API con TTL 3600s
- [ ] Dismiss action funcional

FASE 11 (Copilot):
- [ ] JarabaLexCopilotAgent extiende BaseAgent
- [ ] 6 modos con keywords y temperatures
- [ ] getVerticalContext() actualizado con 'jarabalex'
- [ ] LEGAL-RAG-001 en system prompts
- [ ] Soft upsell solo para plan free + phase >= 3
```

---

## 10. Inventario de Archivos a Crear/Modificar

### 10.1 Archivos a CREAR (35+ nuevos)

| # | Archivo | Fase |
|---|---------|------|
| 1 | `ecosistema_jaraba_theme/templates/page--jarabalex.html.twig` | F0 |
| 2 | `jaraba_legal_intelligence/src/Controller/LegalLandingController.php` | F0 |
| 3 | `jaraba_legal_intelligence/templates/legal-diagnostico.html.twig` | F0 |
| 4 | `jaraba_legal_intelligence/templates/partials/_legal-diagnostico-form.html.twig` | F0 |
| 5 | `jaraba_legal_intelligence/templates/partials/_legal-diagnostico-result.html.twig` | F0 |
| 6 | `jaraba_legal_intelligence/js/legal-diagnostico.js` | F0 |
| 7 | `jaraba_legal_intelligence/scss/_diagnostico.scss` | F0 |
| 8 | `ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.design_token_config.vertical_jarabalex.yml` | F3 |
| 9 | `ecosistema_jaraba_core/src/Service/JarabaLexFeatureGateService.php` | F4 |
| 10 | `ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.freemium_vertical_limit.jarabalex_free_citation_insert.yml` | F4 |
| 11 | `ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.freemium_vertical_limit.jarabalex_starter_citation_insert.yml` | F4 |
| 12 | `ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.freemium_vertical_limit.jarabalex_profesional_citation_insert.yml` | F4 |
| 13 | `ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.freemium_vertical_limit.jarabalex_free_digest_access.yml` | F4 |
| 14 | `ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.freemium_vertical_limit.jarabalex_starter_digest_access.yml` | F4 |
| 15 | `ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.freemium_vertical_limit.jarabalex_profesional_digest_access.yml` | F4 |
| 16 | `ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.freemium_vertical_limit.jarabalex_free_api_access.yml` | F4 |
| 17 | `ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.freemium_vertical_limit.jarabalex_starter_api_access.yml` | F4 |
| 18 | `ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.freemium_vertical_limit.jarabalex_profesional_api_access.yml` | F4 |
| 19 | `ecosistema_jaraba_core/src/Service/JarabaLexEmailSequenceService.php` | F6 |
| 20 | `jaraba_email/templates/mjml/jarabalex/seq_onboarding_legal.mjml` | F6 |
| 21 | `jaraba_email/templates/mjml/jarabalex/seq_reengagement.mjml` | F6 |
| 22 | `jaraba_email/templates/mjml/jarabalex/seq_upsell_starter.mjml` | F6 |
| 23 | `jaraba_email/templates/mjml/jarabalex/seq_upsell_pro.mjml` | F6 |
| 24 | `jaraba_email/templates/mjml/jarabalex/seq_retention.mjml` | F6 |
| 25 | `ecosistema_jaraba_core/src/Service/JarabaLexCrossVerticalBridgeService.php` | F8 |
| 26 | `jaraba_journey/src/JourneyDefinition/JarabaLexJourneyDefinition.php` | F9 |
| 27 | `ecosistema_jaraba_core/src/Service/JarabaLexJourneyProgressionService.php` | F9 |
| 28 | `ecosistema_jaraba_core/src/Service/JarabaLexHealthScoreService.php` | F10 |
| 29 | `jaraba_ai_agents/src/Agent/JarabaLexCopilotAgent.php` | F11 |
| 30 | `ecosistema_jaraba_core/config/install/jaraba_analytics.funnel_definition.jarabalex_acquisition.yml` | F12 |
| 31 | `ecosistema_jaraba_core/config/install/jaraba_analytics.funnel_definition.jarabalex_activation.yml` | F12 |
| 32 | `ecosistema_jaraba_core/config/install/jaraba_analytics.funnel_definition.jarabalex_monetization.yml` | F12 |
| 33–41 | Tests (9 archivos en `tests/src/Unit/` y `tests/src/Kernel/`) | F13 |
| 42 | `docs/tecnicos/aprendizajes/2026-02-16_jarabalex_elevacion_clase_mundial.md` | F13 |

### 10.2 Archivos a MODIFICAR (18+)

| # | Archivo | Fase(s) |
|---|---------|---------|
| 1 | `ecosistema_jaraba_core/src/Controller/VerticalLandingController.php` | F0 |
| 2 | `ecosistema_jaraba_core/ecosistema_jaraba_core.routing.yml` | F0 |
| 3 | `ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme` | F0, F1, F2 |
| 4 | `ecosistema_jaraba_theme/scss/components/_landing-sections.scss` | F0 |
| 5 | `jaraba_legal_intelligence/jaraba_legal_intelligence.routing.yml` | F0, F9 |
| 6 | `jaraba_legal_intelligence/jaraba_legal_intelligence.module` | F0, F1, F2, F6, F7 |
| 7 | `jaraba_legal_intelligence/jaraba_legal_intelligence.libraries.yml` | F0, F2 |
| 8 | `jaraba_legal_intelligence/scss/main.scss` | F0 (añadir `_diagnostico`) |
| 9 | `jaraba_legal_intelligence/src/Service/LegalSearchService.php` | F4, F5 |
| 10 | `jaraba_legal_intelligence/src/Service/LegalAlertService.php` | F4 |
| 11 | `jaraba_legal_intelligence/src/Service/LegalCitationService.php` | F4 |
| 12 | `jaraba_legal_intelligence/src/Service/LegalDigestService.php` | F4 |
| 13 | `jaraba_legal_intelligence/src/Service/LegalCopilotBridgeService.php` | F5 |
| 14 | `jaraba_billing/src/Service/UpgradeTriggerService.php` | F5 |
| 15 | `jaraba_billing/src/Service/FeatureAccessService.php` | F4 |
| 16 | `ecosistema_jaraba_core/ecosistema_jaraba_core.services.yml` | F4, F6, F8, F9, F10 |
| 17 | `ecosistema_jaraba_core/src/Service/AvatarNavigationService.php` | F12 |
| 18 | `jaraba_ai_agents/src/Agent/BaseAgent.php` | F11 |
| 19 | `jaraba_ai_agents/jaraba_ai_agents.services.yml` | F11 |
| 20 | `jaraba_legal_intelligence/src/Controller/LegalDashboardController.php` | F8, F10 |
| 21 | `jaraba_legal_intelligence/src/Controller/LegalSearchController.php` | F9 |
| 22 | `jaraba_legal_intelligence/templates/legal-professional-dashboard.html.twig` | F8, F10 |
| 23 | `jaraba_email/src/Service/TemplateLoaderService.php` | F6 |
| 24 | SCSS files (audit: `_search.scss`, `_results.scss`, `_alerts.scss`) | F3 |
| 25 | Templates CRUD (audit: 5 templates con enlaces) | F2 |
| 26 | `docs/00_DIRECTRICES_PROYECTO.md` | F13 |
| 27 | `docs/00_DOCUMENTO_MAESTRO_ARQUITECTURA.md` | F13 |
| 28 | `docs/00_INDICE_GENERAL.md` | F13 |

---

## Resumen de Estimación por Fase

| Fase | Nombre | Prioridad | Horas | Coste (45€/h) |
|------|--------|-----------|-------|---------------|
| F0 | Landing Page + Lead Magnet | P0 | 25–30h | 1.125–1.350€ |
| F1 | Zero Region Consolidación | P0 | 10–15h | 450–675€ |
| F2 | Modal CRUD | P1 | 10–12h | 450–540€ |
| F3 | SCSS Compliance + Design Tokens | P1 | 12–15h | 540–675€ |
| F4 | Feature Gating (Escalera Valor) | P0 | 20–25h | 900–1.125€ |
| F5 | Upgrade Triggers + Upsell IA | P0 | 15–18h | 675–810€ |
| F6 | Email Sequences | P1 | 20–25h | 900–1.125€ |
| F7 | CRM Integration | P2 | 12–15h | 540–675€ |
| F8 | Cross-Vertical Bridges | P2 | 15–18h | 675–810€ |
| F9 | Journey + AI Progression | P0 | 25–30h | 1.125–1.350€ |
| F10 | Health Scores + KPIs | P1 | 18–22h | 810–990€ |
| F11 | Copilot Agent Dedicado | P1 | 20–25h | 900–1.125€ |
| F12 | Avatar Navigation + Funnels | P1 | 15–18h | 675–810€ |
| F13 | QA, Tests, Documentación | P2 | 25–30h | 1.125–1.350€ |
| **TOTAL** | | | **242–298h** | **10.890–13.410€** |

---

*Documento generado conforme a las directrices de documentación del Proyecto (00_DIRECTRICES_PROYECTO v39.0.0, sección 14). Plantilla: plantilla_implementacion.md.*

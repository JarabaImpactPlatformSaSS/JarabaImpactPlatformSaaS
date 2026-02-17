# Plan de Implementacion: N2 Growth Ready Platform v1.0

| Metadato | Valor |
|----------|-------|
| **Documento** | Plan de Implementacion |
| **Version** | 1.0.0 |
| **Fecha** | 2026-02-17 |
| **Estado** | Planificacion Aprobada |
| **Nivel de Madurez** | N2 — Growth Ready |
| **Modulos Nuevos** | `jaraba_agents`, `jaraba_predictive`, `jaraba_multiregion`, `jaraba_institutional`, `jaraba_funding`, `jaraba_connector_sdk`, `jaraba_mobile` |
| **Modulos Existentes Afectados** | `ecosistema_jaraba_core`, `jaraba_ai_agents`, `jaraba_billing`, `jaraba_foc`, `ecosistema_jaraba_theme` |
| **Docs de Referencia** | 186, 187, 188, 189, 190, 191, 192, 193, 202 (Auditoria Readiness) |
| **Arquetipos Tecnologicos** | A: Drupal Puro (186, 188, 190, 191, 192), B: Hibrido Drupal+Python (189), C: Mobile Nativo (187), D: SDK/Framework (193) |
| **Estimacion Total** | 4 Macro-Fases · 8 Sub-Fases · 471–613 horas · EUR 21,195–27,585 |

---

## Tabla de Contenidos (TOC)

- [1. Resumen Ejecutivo](#1-resumen-ejecutivo)
  - [1.1 Vision Estrategica](#11-vision-estrategica)
  - [1.2 Posicionamiento N2 en el Roadmap de Madurez](#12-posicionamiento-n2-en-el-roadmap-de-madurez)
  - [1.3 Analisis Multidisciplinar](#13-analisis-multidisciplinar)
  - [1.4 Ventajas Competitivas a Lograr](#14-ventajas-competitivas-a-lograr)
  - [1.5 Infraestructura Existente Reutilizable](#15-infraestructura-existente-reutilizable)
  - [1.6 Esfuerzo Estimado Total](#16-esfuerzo-estimado-total)
- [2. Tabla de Correspondencia con Especificaciones Tecnicas](#2-tabla-de-correspondencia-con-especificaciones-tecnicas)
- [3. Cumplimiento de Directrices del Proyecto](#3-cumplimiento-de-directrices-del-proyecto)
  - [3.1 Directriz: i18n — Textos siempre traducibles](#31-directriz-i18n--textos-siempre-traducibles)
  - [3.2 Directriz: Modelo SCSS con Federated Design Tokens](#32-directriz-modelo-scss-con-federated-design-tokens)
  - [3.3 Directriz: Dart Sass moderno](#33-directriz-dart-sass-moderno)
  - [3.4 Directriz: Frontend limpio sin regiones Drupal](#34-directriz-frontend-limpio-sin-regiones-drupal)
  - [3.5 Directriz: Body classes via hook_preprocess_html()](#35-directriz-body-classes-via-hook_preprocess_html)
  - [3.6 Directriz: CRUD en modales slide-panel](#36-directriz-crud-en-modales-slide-panel)
  - [3.7 Directriz: Entidades con Field UI y Views](#37-directriz-entidades-con-field-ui-y-views)
  - [3.8 Directriz: No hardcodear configuracion](#38-directriz-no-hardcodear-configuracion)
  - [3.9 Directriz: Parciales Twig reutilizables](#39-directriz-parciales-twig-reutilizables)
  - [3.10 Directriz: Seguridad](#310-directriz-seguridad)
  - [3.11 Directriz: Comentarios de codigo](#311-directriz-comentarios-de-codigo)
  - [3.12 Directriz: Iconos SVG duotone](#312-directriz-iconos-svg-duotone)
  - [3.13 Directriz: AI via abstraccion @ai.provider](#313-directriz-ai-via-abstraccion-aiprovider)
  - [3.14 Directriz: Automaciones via hooks Drupal](#314-directriz-automaciones-via-hooks-drupal)
  - [3.15 Directriz: Variables configurables desde UI de Drupal](#315-directriz-variables-configurables-desde-ui-de-drupal)
  - [3.16 Directriz: Templates Twig limpias con parciales reutilizables](#316-directriz-templates-twig-limpias-con-parciales-reutilizables)
  - [3.17 Directriz: Reglas Kernel Test y Synthetic Services](#317-directriz-reglas-kernel-test-y-synthetic-services)
- [4. Arquitectura General de Modulos](#4-arquitectura-general-de-modulos)
  - [4.1 Mapa de Modulos y Dependencias](#41-mapa-de-modulos-y-dependencias)
  - [4.2 Estructura de Directorios Estandar](#42-estructura-de-directorios-estandar)
  - [4.3 Arquetipos Tecnologicos](#43-arquetipos-tecnologicos)
- [5. Estado por Fases](#5-estado-por-fases)
- [6. MACRO-FASE 1: Quick Wins — Funding + Multi-Region](#6-macro-fase-1-quick-wins--funding--multi-region)
  - [6.1 FASE 1A: jaraba_funding — Gestion de Fondos Europeos y Subvenciones](#61-fase-1a-jaraba_funding--gestion-de-fondos-europeos-y-subvenciones)
  - [6.2 FASE 1B: jaraba_multiregion — Expansion Multi-Pais](#62-fase-1b-jaraba_multiregion--expansion-multi-pais)
- [7. MACRO-FASE 2: Core Growth — Institutional + Agents](#7-macro-fase-2-core-growth--institutional--agents)
  - [7.1 FASE 2A: jaraba_institutional — Integracion STO/PIIL/FUNDAE](#71-fase-2a-jaraba_institutional--integracion-stopiil-fundae)
  - [7.2 FASE 2B: jaraba_agents — Agentes IA Autonomos](#72-fase-2b-jaraba_agents--agentes-ia-autonomos)
- [8. MACRO-FASE 3: Advanced — Predictive + Orchestration](#8-macro-fase-3-advanced--predictive--orchestration)
  - [8.1 FASE 3A: jaraba_predictive — Modelos Predictivos ML](#81-fase-3a-jaraba_predictive--modelos-predictivos-ml)
  - [8.2 FASE 3B: jaraba_agents (extension) — Orquestacion Multi-Agent](#82-fase-3b-jaraba_agents-extension--orquestacion-multi-agent)
- [9. MACRO-FASE 4: Complex — Mobile + SDK](#9-macro-fase-4-complex--mobile--sdk)
  - [9.1 FASE 4A: jaraba_mobile — App Nativa con Capacitor](#91-fase-4a-jaraba_mobile--app-nativa-con-capacitor)
  - [9.2 FASE 4B: jaraba_connector_sdk — SDK de Conectores y Marketplace](#92-fase-4b-jaraba_connector_sdk--sdk-de-conectores-y-marketplace)
- [10. Inventario Consolidado de Entidades](#10-inventario-consolidado-de-entidades)
- [11. Inventario Consolidado de Services](#11-inventario-consolidado-de-services)
- [12. Inventario Consolidado de Endpoints REST API](#12-inventario-consolidado-de-endpoints-rest-api)
- [13. Paleta de Colores y Design Tokens](#13-paleta-de-colores-y-design-tokens)
- [14. Patron de Iconos SVG](#14-patron-de-iconos-svg)
- [15. Orden Global de Implementacion](#15-orden-global-de-implementacion)
- [16. Estimacion de Esfuerzo](#16-estimacion-de-esfuerzo)
- [17. Registro de Cambios](#17-registro-de-cambios)

---

## 1. Resumen Ejecutivo

El Nivel 2 (Growth Ready) representa la segunda etapa de madurez de la Jaraba Impact Platform SaaS, orientada al **crecimiento escalable** de la plataforma. Mientras el N1 (Foundation) aseguro los cimientos de compliance (GDPR, Legal Terms, Disaster Recovery), el N2 desbloquea capacidades avanzadas que multiplican el valor para tenants y posicionan la plataforma como lider en su segmento.

Este plan cubre **8 especificaciones tecnicas** (docs 186-193) que se agrupan en **4 arquetipos tecnologicos** distintos, lo que lo convierte en el nivel mas heterogeneo del roadmap. A diferencia del N1 (3 modulos Drupal puros), el N2 incluye modulos Drupal clasicos, un hibrido Drupal+Python para ML, una aplicacion movil nativa con Capacitor, y un SDK de desarrollo para terceros.

La auditoria de readiness (doc 202) evaluo el estado actual de las especificaciones en un **15.6% Claude Code Ready**, con la mayor parte del gap en la ausencia de codigo implementable (PHP, YAML, Python, Capacitor config). Este plan cierra ese gap llevando cada especificacion a un nivel de detalle suficiente para implementacion directa por el equipo tecnico.

### 1.1 Vision Estrategica

| Dimension | N1 Foundation (actual) | N2 Growth Ready (objetivo) | Multiplicador |
|-----------|------------------------|---------------------------|---------------|
| Agentes IA | Copilots reactivos (pregunta-respuesta) | Agentes autonomos con guardrails (decision-accion) | **10x capacidad** |
| Analytics | Dashboards retrospectivos | Modelos predictivos (churn, lead scoring, forecasting) | **Proactivo vs reactivo** |
| Alcance geografico | Espana (monolingue, EUR, IVA 21%) | Multi-pais UE (multi-currency, multi-tax, data residency) | **5x mercado potencial** |
| Programas institucionales | Manual (Excel, PDF) | Automatizado (STO, PIIL, FUNDAE, FSE+) | **-80% tiempo admin** |
| Fondos europeos | Sin funcionalidad | Tracking convocatorias + memorias tecnicas IA | **Nuevo revenue stream** |
| Ecosistema | Cerrado (solo desarrollo interno) | Abierto (SDK + marketplace + certificacion) | **Efecto red** |
| Mobile | PWA basica (doc 109) | App nativa iOS/Android (push, QR offline, biometria) | **3x engagement** |
| IA orquestacion | 1 agente por conversacion | Multi-agente con handoff + memoria compartida | **Resolucion compleja** |

### 1.2 Posicionamiento N2 en el Roadmap de Madurez

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    ROADMAP DE MADUREZ PLATAFORMA                        │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  N0 MVP ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ 100% COMPLETADO      │
│  Verticales, Commerce, Trazabilidad, Certificacion, AI base             │
│                                                                         │
│  N1 Foundation ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ 95%+ COMPLETADO     │
│  GDPR DPA, Legal Terms, Disaster Recovery, Compliance Stack            │
│                                                                         │
│  N2 Growth Ready ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ 15.6% ◄ ESTE PLAN   │
│  AI Agents, Predictive, Multi-Region, Institutional, Funding,           │
│  Connector SDK, Native Mobile, Multi-Agent Orchestration                │
│                                                                         │
│  N3 Enterprise Class ━━━━━━━━━━━━━━━━━━━━━━━━━━━ 10.4% (futuro)      │
│  SOC 2, ISO 27001, ENS, HA Multi-Region, SLA Management,               │
│  SSO SAML/SCIM, Data Governance                                         │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 1.3 Analisis Multidisciplinar

#### Arquitecto SaaS Senior
Los 8 modulos comparten `tenant_id` entity_reference, permisos RBAC, API envelope estandar `{success, data, error, message}`, y patron zero-region. El reto principal es la heterogeneidad tecnologica: 5 modulos Drupal clasicos, 1 hibrido con Python ML, 1 mobile nativo, y 1 SDK. La estrategia es mantener la capa Drupal como orquestador central, delegando a Python (ML) y Capacitor (mobile) como componentes perifericos con interfaces bien definidas.

#### Ingeniero de Software Senior
La clave es definir interfaces contractuales claras: `AutonomousAgentInterface`, `PredictionBridgeInterface`, `ConnectorInterface`. Cada modulo debe poder evolucionar independientemente mientras respeta los contratos. El patron de servicios sinteticos (KERNEL-SYNTH-001/002) es critico para Kernel tests de modulos con dependencias cross-module.

#### Ingeniero UX Senior
Los dashboards N2 (agentes, predicciones, fondos, institucional) siguen el patron zero-region existente: header propio, navegacion lateral, FAB del copilot, modales para CRUD. El mobile nativo debe reflejar la experiencia web con adaptaciones nativas (push, biometria, QR). El marketplace de conectores necesita UX de app store (ficha, rating, instalacion 1-click).

#### Ingeniero de Drupal Senior
Los 5 modulos Drupal puros siguen el patron establecido: Content Entities con Field UI + Views + AccessControlHandler + ListBuilder + AdminHtmlRouteProvider. El modulo predictive necesita un `PredictionBridge` PHP que invoque Python via `proc_open()` con JSON stdin/stdout. El SDK necesita un plugin system (`ConnectorPluginManager`) basado en el Discovery de Drupal.

#### Ingeniero de IA Senior
La orquestacion multi-agent (doc 188) es el componente mas complejo: un Agent Router que clasifica intents via LLM, un protocolo de handoff con contexto, y memoria compartida via Qdrant. El churn prediction (doc 189) puede empezar con heuristicas PHP (reglas) y migrar a scikit-learn cuando haya volumen de datos suficiente (>1,000 tenants).

#### Ingeniero SEO/GEO Senior
El modulo multi-region (doc 190) desbloquea SEO multilingue con hreflang automatico, sitemap por idioma/region, y structured data adaptada por pais. El marketplace de conectores (doc 193) genera contenido indexable (fichas de conectores) que atrae trafico de desarrolladores.

### 1.4 Ventajas Competitivas a Lograr

| # | Ventaja | Descripcion | Impacto en TAM |
|---|---------|-------------|----------------|
| **VC-1** | Agentes IA autonomos con guardrails | Ejecucion de acciones sin intervencion humana (L0-L4) con rollback y audit trail | Diferenciador vs plataformas reactivas |
| **VC-2** | Prediccion de churn + lead scoring | Modelos ML que anticipan abandono y priorizan leads por score | -15-25% churn, +20% conversion |
| **VC-3** | Expansion multi-pais automatizada | Multi-currency, multi-tax (IVA intracomunitario), data residency | 5x TAM (UE) |
| **VC-4** | Automatizacion programas institucionales | Fichas STO, reporting FUNDAE/FSE+, justificacion automatica | Vertical unico en Espana |
| **VC-5** | Gestion fondos europeos con IA | Tracking convocatorias, memorias tecnicas generadas por IA | Nuevo revenue stream |
| **VC-6** | App nativa iOS/Android | Push, QR offline, biometria, geofencing | 3x engagement mobile |
| **VC-7** | Multi-agent orchestration | Agentes especializados que colaboran con memoria compartida | Resolucion de tareas complejas |
| **VC-8** | SDK + marketplace abierto | Terceros desarrollan conectores, certificacion automatica, revenue share | Efecto red, lock-in ecosistema |

### 1.5 Infraestructura Existente Reutilizable

| Componente existente | Modulo | Reutilizacion en N2 |
|---------------------|--------|---------------------|
| `BaseAgent` + `AgentInterface` | jaraba_ai_agents | Base para `BaseAutonomousAgent` (doc 186) y specialist agents (doc 188) |
| `SmartRouterService` + RAG pipeline | ecosistema_jaraba_core | Router IA para clasificacion de intents en Agent Router (doc 188) |
| Qdrant collections por tenant | jaraba_ai (doc 128/130) | Memoria compartida entre agentes (doc 188), feature store para ML (doc 189) |
| `FeatureGateService` + `FreemiumVerticalLimit` | ecosistema_jaraba_core | Gate de features para agentes autonomos y predicciones |
| `HealthScoreService` pattern | multiples verticales | Metricas de salud reutilizables como features para churn prediction (doc 189) |
| `BillingService` + Stripe Connect | jaraba_billing | Multi-currency (doc 190), revenue share marketplace (doc 193) |
| Patron zero-region (3 hooks) | ecosistema_jaraba_theme | Templates para todos los dashboards N2 |
| `TemplateLoaderService` + MJML | jaraba_email | Notificaciones email de agentes, alertas predictivas, alertas de plazos |
| `TenantContextService` | ecosistema_jaraba_core | Aislamiento multi-tenant en todos los modulos N2 |
| `EcaEventSubscriber` pattern | multiples modulos | Triggers para agentes autonomos y workflows |
| `AndaluciaEiJourneyProgressionService` | ecosistema_jaraba_core | Patron para programas institucionales (doc 191) |
| `ComplianceAggregatorService` | ecosistema_jaraba_core | Modelo para aggregation cross-module (metricas predictivas) |

**Estimacion de reutilizacion: 25-30%** del codigo base ya existe como infraestructura, patrones o servicios parciales. Menor que en JarabaLex (35-40%) debido a la heterogeneidad de stacks.

### 1.6 Esfuerzo Estimado Total

| Macro-Fase | Modulos | Entidades | Services | Endpoints | Horas (min) | Horas (max) |
|------------|---------|-----------|----------|-----------|-------------|-------------|
| **1** Quick Wins | jaraba_funding, jaraba_multiregion | 7 | 10 | 24 | 94 | 119 |
| **2** Core Growth | jaraba_institutional, jaraba_agents | 8 | 12 | 21 | 113 | 143 |
| **3** Advanced | jaraba_predictive, jaraba_agents (ext) | 5 | 12 | 16 | 117 | 147 |
| **4** Complex | jaraba_mobile, jaraba_connector_sdk | 5 | 8 | 15 | 147 | 204 |
| **TOTAL** | **7 modulos nuevos + extensiones** | **25** | **42** | **76** | **471** | **613** |

**Inversion estimada:** 471-613 horas x EUR 45/hora = **EUR 21,195-27,585**
**Timeline:** ~8 meses (1 desarrollador senior), ~4 meses (2 en paralelo) — 4 macro-fases secuenciales con paralelismo interno
**Comparativa N1:** N1 fue 91-118h / EUR 4,095-5,310. N2 es ~5x mas complejo (confirmando la proyeccion del doc 202).

---

## 2. Tabla de Correspondencia con Especificaciones Tecnicas

| Doc # | Titulo Especificacion | Arquetipo | Macro-Fase | Modulo Drupal | Entidades Principales | Score Readiness (doc 202) | Estado |
|-------|----------------------|-----------|------------|--------------|----------------------|--------------------------|--------|
| **186** | AI Autonomous Agents | A: Drupal | 2B | `jaraba_agents` | AutonomousAgent, AgentExecution, AgentApproval | 12.5% | ⬜ Planificada |
| **187** | Native Mobile App | C: Mobile | 4A | `jaraba_mobile` | MobileDevice, PushNotification | 18.8% | ⬜ Planificada |
| **188** | Multi-Agent Orchestration | A: Drupal | 3B | `jaraba_agents` (ext) | AgentConversation, AgentHandoff | 8.3% | ⬜ Planificada |
| **189** | Predictive Analytics | B: Hibrido | 3A | `jaraba_predictive` | ChurnPrediction, LeadScore, Forecast | 12.5% | ⬜ Planificada |
| **190** | Multi-Region Operations | A: Drupal | 1B | `jaraba_multiregion` | TenantRegion, TaxRule, CurrencyRate | 12.5% | ⬜ Planificada |
| **191** | STO/PIIL Integration | A: Drupal | 2A | `jaraba_institutional` | InstitutionalProgram, ProgramParticipant, StoFicha | 12.5% | ⬜ Planificada |
| **192** | European Funding | A: Drupal | 1A | `jaraba_funding` | FundingOpportunity, FundingApplication, TechnicalReport | 12.5% | ⬜ Planificada |
| **193** | Connector SDK | D: SDK | 4B | `jaraba_connector_sdk` | Connector, ConnectorInstall | 25.0% | ⬜ Planificada |

**Dependencias entre documentos:**

| Doc Origen | Doc Destino | Tipo | Datos que fluyen |
|-----------|-------------|------|------------------|
| 186 (Agents) | 188 (Orchestration) | HARD | `BaseAutonomousAgent` es prerequisito de multi-agent routing |
| 186 (Agents) | jaraba_ai (doc 128) | HARD | Agentes usan Claude API via `@ai.provider` |
| 188 (Orchestration) | jaraba_ai (doc 128/130) | HARD | Memoria compartida via Qdrant existente |
| 189 (Predictive) | jaraba_foc (doc 02) | SOFT | Metricas FOC como features para churn prediction |
| 189 (Predictive) | jaraba_billing | SOFT | Datos de pagos Stripe como features para scoring |
| 191 (STO/PIIL) | Andalucia +ei (doc 45) | HARD | Entidades programa/participante existentes |
| 191 (STO/PIIL) | doc 89 (Firma Digital) | HARD | Ficha STO necesita PAdES |
| 193 (SDK) | doc 112 (Marketplace) | HARD | OAuth2 + MCP del marketplace existente |
| 193 (SDK) | jaraba_billing (doc 134) | SOFT | Revenue share via Stripe Connect |
| 187 (Mobile) | Todos los verticales | SOFT | APIs mobile extienden cada vertical |

**Implicacion de orden:** 186 DEBE completarse antes de 188. Las fases 1A y 1B son independientes entre si. 191 necesita doc 45 + doc 89 completados. 193 necesita doc 112 operativo.

---

## 3. Cumplimiento de Directrices del Proyecto

### 3.1 Directriz: i18n — Textos siempre traducibles

**Referencia:** `docs/00_DIRECTRICES_PROYECTO.md` seccion 1.5 + seccion 3

Todos los strings visibles al usuario en los 7 modulos nuevos usan `TranslatableMarkup` en PHP, `|t` en Twig, `Drupal.t()` en JS. Esto incluye labels de campos de entidades, mensajes de estado, textos de interfaz, nombres de permisos, y descripciones de configuracion.

```php
// Ejemplo: Label de campo en Content Entity
$fields['risk_level'] = BaseFieldDefinition::create('list_string')
  ->setLabel(new TranslatableMarkup('Nivel de riesgo'))
  ->setSetting('allowed_values', [
    'low' => new TranslatableMarkup('Bajo'),
    'medium' => new TranslatableMarkup('Medio'),
    'high' => new TranslatableMarkup('Alto'),
    'critical' => new TranslatableMarkup('Critico'),
  ]);
```

```twig
{# Ejemplo: Texto en template Twig #}
<h2>{{ 'Panel de predicciones'|t }}</h2>
<p>{{ 'Ultima actualizacion: @date'|t({'@date': last_update}) }}</p>
```

```javascript
// Ejemplo: Texto en JS behavior
const message = Drupal.t('Agente @name ejecutando accion...', {'@name': agentName});
```

**Aplicacion por modulo:**
- `jaraba_agents`: Nombres de agentes, niveles de autonomia, mensajes de guardrails
- `jaraba_predictive`: Labels de metricas, niveles de riesgo, recomendaciones
- `jaraba_multiregion`: Nombres de paises, monedas, mensajes fiscales
- `jaraba_institutional`: Nombres de programas, estados de participantes, labels de fichas
- `jaraba_funding`: Titulos de convocatorias, estados de solicitudes, secciones de memorias
- `jaraba_connector_sdk`: Nombres de categorias, estados de certificacion, mensajes de marketplace
- `jaraba_mobile`: Titulos de notificaciones push, canales, mensajes de dispositivo

### 3.2 Directriz: Modelo SCSS con Federated Design Tokens

**Referencia:** `docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md` v2.1

Todos los modulos N2 con frontend (agentes, predictive, funding, institutional, connector_sdk) siguen el patron Federated Design Tokens: **NUNCA** definen variables `$ej-*` localmente. Solo consumen CSS Custom Properties con fallbacks inline.

```scss
// _agents-dashboard.scss — CORRECTO: solo var(--ej-*) con fallback
.agents-dashboard {
  background: var(--ej-bg-surface, #FFFFFF);
  border-radius: var(--ej-border-radius-lg, 14px);
  padding: var(--ej-spacing-lg, 1.5rem);

  &__header {
    color: var(--ej-color-corporate, #233D63);
    font-family: var(--ej-font-heading, 'Outfit', sans-serif);
  }

  &__agent-card {
    border: 1px solid color-mix(in srgb, var(--ej-color-innovation, #00A9A5) 20%, transparent);
    background: color-mix(in srgb, var(--ej-color-innovation, #00A9A5) 5%, transparent);
  }

  &__risk-high {
    background: color-mix(in srgb, var(--ej-color-danger, #EF4444) 10%, transparent);
    border-left: 4px solid var(--ej-color-danger, #EF4444);
  }
}
```

```scss
// PROHIBIDO en modulos satelite:
$ej-color-corporate: #233D63;  // NUNCA definir variables SCSS locales
```

**Cada modulo N2 con SCSS tendra:**
- `scss/main.scss` — entry point unico
- `scss/_[feature].scss` — parciales BEM
- `css/[modulo].css` — output compilado
- `package.json` — scripts de build estandar

### 3.3 Directriz: Dart Sass moderno

**Referencia:** `docs/00_DIRECTRICES_PROYECTO.md` seccion 2.2.1, regla SCSS-001

Compilacion exclusiva con Dart Sass (`sass ^1.71.0`). Reglas obligatorias:
- `@use` en lugar de `@import` (scope aislado)
- `color-mix(in srgb, ...)` en lugar de `rgba()` para variantes de color
- `var(--ej-*)` en lugar de `$ej-*` en modulos satelite
- `color.adjust()` en lugar de `darken()`/`lighten()` deprecados
- Cada parcial SCSS que necesite variables del modulo core: `@use '../variables' as *;` (regla SCSS-001)

**Package.json estandar para cada modulo N2:**

```json
{
    "name": "jaraba-[module-name]",
    "version": "1.0.0",
    "description": "Estilos SCSS para modulo [module-name] — N2 Growth Ready",
    "scripts": {
        "build": "sass scss/main.scss:css/jaraba-[module-name].css --style=compressed --no-source-map",
        "build:all": "npm run build && echo 'Build completado'",
        "watch": "sass --watch scss:css --style=compressed"
    },
    "devDependencies": {
        "sass": "^1.71.0"
    }
}
```

**Compilacion desde Docker:**

```bash
docker exec jarabasaas_appserver_1 bash -c \
  "cd /app/web/modules/custom/jaraba_[module] && npx sass scss/main.scss css/jaraba-[module].css --style=compressed --no-source-map"
```

### 3.4 Directriz: Frontend limpio sin regiones Drupal

**Referencia:** `docs/00_DIRECTRICES_PROYECTO.md` seccion 2.2.2, reglas ZERO-REGION-001/002/003

Todas las paginas frontend de los modulos N2 usan templates zero-region. Sin `{{ page.content }}`, sin sidebars, sin breadcrumbs heredados, sin bloques de Drupal. Layout full-width pensado para movil.

**Templates zero-region planeados:**

| Template | Ruta | Modulo |
|----------|------|--------|
| `page--agents.html.twig` | `/agents` | jaraba_agents |
| `page--predictions.html.twig` | `/predictions` | jaraba_predictive |
| `page--funding.html.twig` | `/funding` | jaraba_funding |
| `page--institutional.html.twig` | `/institutional` | jaraba_institutional |
| `page--connectors.html.twig` | `/connectors` | jaraba_connector_sdk |

**Patron obligatorio de 3 hooks por modulo:**

```php
// 1. hook_theme() — Registrar template y variables
function jaraba_funding_theme(): array {
  return [
    'page__funding' => [
      'variables' => [
        'opportunities' => [],
        'applications' => [],
        'stats' => [],
        'copilot_context' => NULL,
      ],
    ],
  ];
}

// 2. hook_theme_suggestions_page_alter() — Sugerir template para ruta
function jaraba_funding_theme_suggestions_page_alter(array &$suggestions, array $variables): void {
  $route = \Drupal::routeMatch()->getRouteName() ?? '';
  if ($route === 'jaraba_funding.dashboard') {
    $suggestions[] = 'page__funding';
  }
}

// 3. hook_preprocess_page() — Inyectar variables y drupalSettings
function jaraba_funding_preprocess_page(array &$variables): void {
  $route = \Drupal::routeMatch()->getRouteName() ?? '';
  if ($route === 'jaraba_funding.dashboard') {
    $variables['opportunities'] = $opportunityData;
    $variables['#attached']['drupalSettings']['jarabaFunding'] = [...];
  }
}
```

**REGLA ZERO-REGION-001:** Variables inyectadas **SOLO** via `hook_preprocess_page()`, **NUNCA** via controller render array.
**REGLA ZERO-REGION-002:** **NUNCA** pasar entity objects como non-`#` keys en render arrays.
**REGLA ZERO-REGION-003:** drupalSettings via `$variables['#attached']['drupalSettings']` en `hook_preprocess_page()`.

### 3.5 Directriz: Body classes via hook_preprocess_html()

**Referencia:** `docs/00_DIRECTRICES_PROYECTO.md` seccion 5.8.5, regla LEGAL-BODY-001

Body classes para rutas N2 **SIEMPRE** via `hook_preprocess_html()` en el `.module`, **NUNCA** con `attributes.addClass()` en template Twig (no funciona para body).

```php
// Ejemplo: jaraba_agents.module
function jaraba_agents_preprocess_html(array &$variables): void {
  $route = \Drupal::routeMatch()->getRouteName() ?? '';
  if (str_starts_with($route, 'jaraba_agents.')) {
    $variables['attributes']['class'][] = 'page-agents';
    $variables['attributes']['class'][] = 'n2-growth-ready';
  }
}
```

**Clases body planeadas por modulo:**

| Modulo | Clase body | Rutas |
|--------|-----------|-------|
| jaraba_agents | `page-agents` | `/agents`, `/agents/*` |
| jaraba_predictive | `page-predictions` | `/predictions`, `/predictions/*` |
| jaraba_multiregion | `page-multiregion` | `/admin/config/jaraba/regions/*` |
| jaraba_institutional | `page-institutional` | `/institutional`, `/institutional/*` |
| jaraba_funding | `page-funding` | `/funding`, `/funding/*` |
| jaraba_connector_sdk | `page-connectors` | `/connectors`, `/connectors/*` |
| jaraba_mobile | `page-mobile-admin` | `/admin/config/jaraba/mobile/*` |

### 3.6 Directriz: CRUD en modales slide-panel

**Referencia:** `docs/00_DIRECTRICES_PROYECTO.md` seccion 3 (flujo de trabajo)

Todas las acciones de crear/editar/ver en paginas frontend se abren en modal `data-dialog-type="modal"` con `drupal.dialog.ajax`, para que el usuario no abandone la pagina en la que esta trabajando.

```php
// Ejemplo: Link para crear agente desde el dashboard
$build['create_agent'] = [
  '#type' => 'link',
  '#title' => new TranslatableMarkup('Nuevo agente'),
  '#url' => Url::fromRoute('entity.autonomous_agent.add_form'),
  '#attributes' => [
    'class' => ['use-ajax', 'btn', 'btn--primary'],
    'data-dialog-type' => 'modal',
    'data-dialog-options' => json_encode([
      'width' => 800,
      'title' => (string) new TranslatableMarkup('Crear agente autonomo'),
    ]),
  ],
];
```

### 3.7 Directriz: Entidades con Field UI y Views

**Referencia:** `docs/00_DIRECTRICES_PROYECTO.md` seccion 5 (Content Entities para todo)

Las 25 entidades del plan son Content Entities con:
- **Field UI:** `field_ui_base_route` en la anotacion de la entidad, para que administradores puedan anadir campos sin codigo
- **Views:** `views_data` handler para integracion completa con Views
- **AccessControlHandler:** Handler de acceso obligatorio (regla AUDIT-CONS-001)
- **ListBuilder:** Para paginas de administracion en `/admin/structure`
- **AdminHtmlRouteProvider:** Para rutas CRUD automaticas

**Navegacion en Drupal:**
- `/admin/structure/[entity-collection]` — Gestion de estructura (ListBuilder)
- `/admin/content` — Tabs para cada entidad (links.task.yml)
- Field UI accesible desde `/admin/structure/[entity]/manage/fields`

```php
// Ejemplo: Anotacion de entidad con Field UI + Views
/**
 * @ContentEntityType(
 *   id = "autonomous_agent",
 *   label = @Translation("Agente Autonomo"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_agents\Entity\AutonomousAgentListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_agents\Access\AutonomousAgentAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\jaraba_agents\Form\AutonomousAgentForm",
 *       "add" = "Drupal\jaraba_agents\Form\AutonomousAgentForm",
 *       "edit" = "Drupal\jaraba_agents\Form\AutonomousAgentForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer agents",
 *   field_ui_base_route = "entity.autonomous_agent.collection",
 *   base_table = "autonomous_agent",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/structure/agents",
 *     "canonical" = "/admin/structure/agents/{autonomous_agent}",
 *     "add-form" = "/admin/structure/agents/add",
 *     "edit-form" = "/admin/structure/agents/{autonomous_agent}/edit",
 *     "delete-form" = "/admin/structure/agents/{autonomous_agent}/delete",
 *   },
 * )
 */
```

### 3.8 Directriz: No hardcodear configuracion

**Referencia:** `docs/00_DIRECTRICES_PROYECTO.md` seccion 5 regla 1

Configuraciones que **DEBEN** estar en Config Entities o admin forms (nunca en codigo):
- Niveles de autonomia de agentes (L0-L4) y sus guardrails
- Umbrales de churn prediction (scores, pesos de features)
- Reglas fiscales por pais (tipos IVA, umbrales OSS)
- Plazos y templates de programas institucionales
- Configuracion de certificacion de conectores
- Tokens FCM y configuracion push mobile
- Revenue share percentages del marketplace

Todas estas configuraciones se gestionan via admin forms en `/admin/config/jaraba/[modulo]` con `config/schema/*.yml` definido.

### 3.9 Directriz: Parciales Twig reutilizables

**Referencia:** `docs/00_DIRECTRICES_PROYECTO.md` seccion 3 (parciales)

Cada modulo define parciales bajo `templates/partials/` que se incorporan a las paginas via `{% include %}`. Antes de crear un nuevo parcial, verificar si ya existe uno reutilizable en el tema o en otro modulo.

**Parciales planeados:**

| Modulo | Parcial | Reutilizacion |
|--------|---------|---------------|
| jaraba_agents | `_agent-card.html.twig` | Dashboard, listados |
| jaraba_agents | `_execution-timeline.html.twig` | Detail, historial |
| jaraba_agents | `_guardrail-badge.html.twig` | Cards, listados |
| jaraba_predictive | `_risk-gauge.html.twig` | Churn, lead scoring |
| jaraba_predictive | `_prediction-card.html.twig` | Dashboard, alertas |
| jaraba_funding | `_opportunity-card.html.twig` | Listado, alertas |
| jaraba_funding | `_application-status.html.twig` | Dashboard, detail |
| jaraba_institutional | `_program-card.html.twig` | Dashboard |
| jaraba_institutional | `_participant-row.html.twig` | Listado, ficha STO |
| jaraba_connector_sdk | `_connector-tile.html.twig` | Marketplace grid |
| jaraba_connector_sdk | `_certification-badge.html.twig` | Tiles, detail |

**Parciales existentes reutilizables del tema:**
- `_copilot-fab.html.twig` — FAB del copilot (se incluye en todas las paginas N2)
- `_header.html.twig` — Header responsive
- `_footer.html.twig` — Footer con variables configurables desde UI

**Patron de inclusion:**

```twig
{# page--agents.html.twig #}
{% include '@ecosistema_jaraba_theme/partials/_header.html.twig' %}

<main class="agents-dashboard">
  {% for agent in agents %}
    {% include '@jaraba_agents/partials/_agent-card.html.twig' with {
      agent: agent,
    } only %}
  {% endfor %}
</main>

{% if copilot_context %}
  {% include '@ecosistema_jaraba_theme/partials/_copilot-fab.html.twig' with {
    context: copilot_context,
  } only %}
{% endif %}

{% include '@ecosistema_jaraba_theme/partials/_footer.html.twig' %}
```

### 3.10 Directriz: Seguridad

**Referencia:** `docs/00_DIRECTRICES_PROYECTO.md` secciones 4.5, 5.8.3

- **`_permission` en todas las rutas sensibles** (AUDIT-SEC-002): Cada endpoint API y pagina de dashboard requiere permisos especificos (`access agents`, `manage predictions`, etc.), nunca solo `_user_is_logged_in`
- **HMAC en webhooks** (AUDIT-SEC-001): Stripe webhooks para multi-currency, LexNET callbacks, conectores externos — todos con `hash_equals()` para verificacion
- **Sanitizacion antes de `|raw`** (AUDIT-SEC-003): `Xss::filterAdmin()` o `Html::escape()` en todo contenido que se renderice con `|raw`
- **Rate limiting**: En operaciones costosas de agentes IA (100 req/hr RAG, 50 req/hr Copilot), predicciones ML, y bulk operations de fondos
- **Tenant isolation** (TENANT-001/002): `TenantContextService` inyectado en TODOS los servicios N2, `tenant_id` como entity_reference obligatorio
- **Circuit breaker**: En llamadas a APIs externas (VIES, STO, LexNET, FCM) — skip provider 5min tras 5 fallos consecutivos
- **Token budget agentes**: Limite de tokens LLM por ejecucion y por tenant/mes (guardrail configurable)
- **DB indexes** (AUDIT-PERF-001): Obligatorios en `tenant_id` + campos frecuentes de consulta

### 3.11 Directriz: Comentarios de codigo

**Referencia:** `docs/00_DIRECTRICES_PROYECTO.md` seccion 10

Comentarios en **espanol** cubriendo 3 dimensiones:
1. **Estructura:** organizacion, relaciones, patrones, jerarquia
2. **Logica:** proposito, flujo de ejecucion, reglas de negocio, decisiones, edge cases
3. **Sintaxis:** parametros, returns, excepciones, tipos complejos

```php
/**
 * Servicio de prediccion de churn para tenants.
 *
 * Estructura: Consume metricas de uso (session logs, tickets, pagos) y aplica
 * un modelo de scoring basado en pesos configurables para calcular el riesgo
 * de abandono de cada tenant. Integra con el Feature Store (Redis) para
 * features pre-calculadas y con PredictionBridge para modelos ML avanzados.
 *
 * Logica: El calculo se ejecuta diariamente via cron. Si el score supera
 * el umbral configurable (default: 70), se genera una alerta al admin y se
 * dispara un workflow de retencion (email, descuento, llamada). El modelo
 * heuristico PHP es el fallback cuando Python no esta disponible.
 *
 * @see \Drupal\jaraba_predictive\Service\PredictionBridge
 * @see \Drupal\jaraba_predictive\Entity\ChurnPrediction
 */
```

### 3.12 Directriz: Iconos SVG duotone

**Referencia:** `docs/tecnicos/aprendizajes/2026-01-26_iconos_svg_landing_verticales.md`

Iconos SVG en dos versiones (normal + duotone) usando `jaraba_icon()`. **NUNCA** emojis en codigo (regla P4-EMOJI-001/002).

**Categorias e iconos nuevos necesarios para N2:**

| Modulo | Categoria | Iconos Necesarios |
|--------|-----------|-------------------|
| jaraba_agents | `ai/` | `agent-autonomous`, `guardrail`, `execution-chain` |
| jaraba_predictive | `analytics/` | `prediction`, `churn-risk`, `lead-score`, `forecast` |
| jaraba_multiregion | `business/` | `globe-multi`, `currency-exchange`, `tax-calculator` |
| jaraba_institutional | `business/` | `institution-program`, `sto-ficha`, `fundae-report` |
| jaraba_funding | `business/` | `funding-opportunity`, `grant-application`, `deadline-alert` |
| jaraba_connector_sdk | `ui/` | `connector-plug`, `marketplace`, `certification-badge` |
| jaraba_mobile | `ui/` | `mobile-device`, `push-notification`, `qr-scanner` |

**Patron duotone obligatorio:**

```svg
<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
  <rect x="3" y="3" width="18" height="18" rx="4"
    fill="var(--icon-fill, rgba(0,169,165,0.15))"
    stroke="currentColor" stroke-width="1.5"/>
  <path d="M8 12l3 3 5-5" stroke="currentColor" stroke-width="1.5"
    stroke-linecap="round" stroke-linejoin="round"/>
</svg>
```

### 3.13 Directriz: AI via abstraccion @ai.provider

**Referencia:** `docs/00_DIRECTRICES_PROYECTO.md` seccion 4.5

Todas las llamadas a LLM en modulos N2 usan el servicio abstracto `@ai.provider` (Gemini 2.0 Flash por defecto). **NUNCA** HTTP directo a APIs LLM.

- **jaraba_agents:** `BaseAutonomousAgent::execute()` usa `@ai.provider` para decisiones autonomas
- **jaraba_predictive:** Generacion de insights y recomendaciones de retencion via `@ai.provider`
- **jaraba_institutional:** Generacion automatica de texto para fichas STO
- **jaraba_funding:** Generacion de memorias tecnicas para convocatorias
- **jaraba_connector_sdk:** Validacion semantica de documentacion de conectores

### 3.14 Directriz: Automaciones via hooks Drupal

**Referencia:** `docs/00_DIRECTRICES_PROYECTO.md` seccion 3 (hooks, no ECA YAML para logica compleja)

Hooks nativos PHP en `.module` para toda logica de negocio compleja. ECA YAML solo para triggers simples de eventos.

```php
// Ejemplo: Auto-generar alerta cuando churn score > umbral
function jaraba_predictive_entity_insert(EntityInterface $entity): void {
  if ($entity->getEntityTypeId() !== 'churn_prediction') {
    return;
  }
  $score = (int) $entity->get('risk_score')->value;
  $threshold = \Drupal::config('jaraba_predictive.settings')->get('alert_threshold') ?? 70;
  if ($score >= $threshold) {
    // Disparar workflow de retencion
    \Drupal::service('jaraba_predictive.retention_workflow')->trigger($entity);
  }
}
```

### 3.15 Directriz: Variables configurables desde UI de Drupal

**Referencia:** Directriz explicita del usuario — modelo SASS con archivos SCSS que comprimimos y variables inyectables cuyos valores configuramos a traves de la interfaz de Drupal sin codigo.

**Este es un patron critico que se aplica en todas las capas:**

1. **Capa SCSS:** Variables CSS Custom Properties definidas en `_injectable.scss` del core (`:root { --ej-color-primary: #FF8C42; }`)
2. **Capa tema Drupal:** El tema expone un formulario de configuracion en `/admin/appearance/settings/ecosistema_jaraba_theme` donde el admin puede cambiar colores, logos, tipografia
3. **Capa hook:** `hook_preprocess_html()` inyecta las variables configuradas como inline `<style>` en el `<head>`, sobreescribiendo los valores por defecto del CSS
4. **Resultado:** Cambiar un color en la UI de Drupal actualiza toda la plataforma sin tocar codigo ni recompilar SCSS

```php
// En ecosistema_jaraba_theme.theme — inyeccion de variables desde config
function ecosistema_jaraba_theme_preprocess_html(array &$variables): void {
  $config = \Drupal::config('ecosistema_jaraba_theme.settings');
  $overrides = [];

  if ($primary = $config->get('primary_color')) {
    $overrides[] = "--ej-color-primary: {$primary}";
  }
  if ($font = $config->get('heading_font')) {
    $overrides[] = "--ej-font-heading: '{$font}', sans-serif";
  }

  if (!empty($overrides)) {
    $css = ':root { ' . implode('; ', $overrides) . '; }';
    $variables['#attached']['html_head'][] = [
      ['#type' => 'html_tag', '#tag' => 'style', '#value' => $css],
      'ecosistema_jaraba_custom_vars',
    ];
  }
}
```

**Para modulos N2:** Cada modulo que necesite configuracion visual propia (colores de estado de agentes, umbrales de colores en predicciones) define un `SettingsForm` en `/admin/config/jaraba/[modulo]` que almacena valores en `config`, y el `hook_preprocess_page()` del modulo los inyecta como `drupalSettings` o variables Twig. El tema los consume via `var(--ej-*)`.

**Configuracion del footer y otros elementos heredados:** El contenido del footer (textos, links, copyright, redes sociales) se configura desde la UI del tema (`/admin/appearance/settings/ecosistema_jaraba_theme`). Los parciales Twig (`_footer.html.twig`) leen esas variables inyectadas por `hook_preprocess_page()` del tema. **No hay que tocar codigo para cambiar el footer.**

### 3.16 Directriz: Templates Twig limpias con parciales reutilizables

**Referencia:** Directriz explicita del usuario

**Paginas:** Templates Twig limpias, libres de regiones y bloques de Drupal. Sin `{{ page.content }}`, sin `{{ page.sidebar_first }}`, sin breadcrumbs heredados. Layout full-width, mobile-first.

**Parciales:** Para elementos heredados (header, footer, copilot FAB, navigation), se usan templates parciales que se incluyen via `{% include %}`. Antes de crear un parcial nuevo, verificar si ya existe uno.

**Parciales existentes del tema que se reutilizan en N2:**

| Parcial | Ubicacion | Uso |
|---------|-----------|-----|
| `_header.html.twig` | ecosistema_jaraba_theme | Header responsive de todas las paginas |
| `_footer.html.twig` | ecosistema_jaraba_theme | Footer con variables configurables desde UI |
| `_copilot-fab.html.twig` | ecosistema_jaraba_theme | Boton flotante del copilot IA |
| `_mobile-menu.html.twig` | ecosistema_jaraba_theme | Menu movil responsive |

**Patron de pagina zero-region N2:**

```twig
{# page--agents.html.twig — Pagina zero-region del dashboard de agentes #}
{% include '@ecosistema_jaraba_theme/partials/_header.html.twig' %}

<main class="agents-dashboard" role="main">
  <div class="agents-dashboard__container">
    {# Contenido especifico del modulo #}
    <section class="agents-dashboard__stats">
      {% for stat in stats %}
        {% include '@jaraba_agents/partials/_stat-card.html.twig' with { stat: stat } only %}
      {% endfor %}
    </section>

    <section class="agents-dashboard__list">
      {% for agent in agents %}
        {% include '@jaraba_agents/partials/_agent-card.html.twig' with { agent: agent } only %}
      {% endfor %}
    </section>
  </div>
</main>

{% if copilot_context %}
  {% include '@ecosistema_jaraba_theme/partials/_copilot-fab.html.twig' with {
    context: copilot_context,
  } only %}
{% endif %}

{% include '@ecosistema_jaraba_theme/partials/_footer.html.twig' %}
```

**Control absoluto sobre el frontend:** El tenant NO tiene acceso al tema de administracion de Drupal. Solo el usuario administrador (superadmin) ve el sidebar admin. Los tenants interactuan exclusivamente con las paginas frontend limpias en sus rutas (`/agents`, `/predictions`, `/funding`, etc.).

### 3.17 Directriz: Reglas Kernel Test y Synthetic Services

**Referencia:** `docs/00_DIRECTRICES_PROYECTO.md` seccion 5.8.2, reglas KERNEL-SYNTH-001/002, KERNEL-DEP-001

- **KERNEL-DEP-001:** Kernel tests DEBEN incluir TODOS los modulos que proveen field types usados por entidades (`options`, `datetime`, `flexible_permissions`, `group`)
- **KERNEL-SYNTH-001:** Para dependencias de modulos no cargados, registrar servicios sinteticos
- **KERNEL-SYNTH-002:** Al anadir nuevas dependencias `@service` en `.services.yml`, actualizar TODOS los Kernel tests del modulo en el MISMO commit

```php
// Ejemplo: Kernel test con servicios sinteticos
protected function setUp(): void {
  parent::setUp();

  $this->enableModules([
    'system', 'user', 'field', 'options', 'datetime',
    'group', 'flexible_permissions',
    'ecosistema_jaraba_core',
    'jaraba_agents',
  ]);

  // Servicio sintetico para dependencia externa no cargada
  $container = \Drupal::getContainer();
  $aiProvider = $this->createMock(AiProviderInterface::class);
  $container->set('ai.provider', $aiProvider);
}
```

---

## 4. Arquitectura General de Modulos

### 4.1 Mapa de Modulos y Dependencias

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    N2 GROWTH READY PLATFORM                             │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  MACRO-FASE 4 (Complex)                                                 │
│  ┌──────────────────┐  ┌──────────────────┐                            │
│  │ jaraba_mobile     │  │ jaraba_connector_ │                            │
│  │ (Capacitor+Push)  │  │ sdk (Marketplace) │                            │
│  └────────┬─────────┘  └────────┬─────────┘                            │
│           │                      │                                       │
│  MACRO-FASE 3 (Advanced)        │                                       │
│  ┌──────────────────┐  ┌────────┴─────────┐                            │
│  │ jaraba_predictive │  │ jaraba_agents     │                            │
│  │ (ML+Python)       │  │ (Orchestration    │                            │
│  │                   │  │  extension)       │                            │
│  └────────┬─────────┘  └────────┬─────────┘                            │
│           │                      │                                       │
│  MACRO-FASE 2 (Core Growth)     │                                       │
│  ┌──────────────────┐  ┌────────┴─────────┐                            │
│  │ jaraba_            │  │ jaraba_agents     │                            │
│  │ institutional     │  │ (Base Autonomous) │                            │
│  │ (STO/PIIL/FUNDAE) │  │                   │                            │
│  └────────┬─────────┘  └────────┬─────────┘                            │
│           │                      │                                       │
│  MACRO-FASE 1 (Quick Wins)      │                                       │
│  ┌──────────────────┐  ┌────────┴─────────┐                            │
│  │ jaraba_funding    │  │ jaraba_            │                            │
│  │ (Fondos UE)       │  │ multiregion       │                            │
│  │                   │  │ (Multi-Pais)       │                            │
│  └────────┬─────────┘  └────────┬─────────┘                            │
│           │                      │                                       │
│  INFRAESTRUCTURA EXISTENTE (N0/N1)                                      │
│  ┌──────────────────────────────────────────────────────────────┐      │
│  │ ecosistema_jaraba_core (TenantContext, FeatureGate, Health)   │      │
│  │ jaraba_ai_agents (BaseAgent, SmartRouter, Qdrant)             │      │
│  │ jaraba_billing (Stripe, Plans, Subscriptions)                 │      │
│  │ jaraba_foc (Metricas, KPIs, FOC Dashboard)                   │      │
│  │ jaraba_email (MJML, TemplateLoader, SendGrid)                 │      │
│  │ ecosistema_jaraba_theme (Zero-region, Partials, Design Tokens)│      │
│  └──────────────────────────────────────────────────────────────┘      │
└─────────────────────────────────────────────────────────────────────────┘
```

**Dependencias internas N2:**

```
jaraba_funding ──────────── (independiente)
jaraba_multiregion ──────── (independiente, extiende jaraba_billing)
jaraba_institutional ────── (depende de doc 45 Andalucia +ei, doc 89 firma)
jaraba_agents ───────────── (depende de jaraba_ai_agents)
  └── jaraba_agents (orch) ─ (depende de jaraba_agents base, HARD)
jaraba_predictive ───────── (depende soft de jaraba_foc, jaraba_billing)
jaraba_mobile ───────────── (depende de APIs de todos los verticales)
jaraba_connector_sdk ────── (depende de doc 112 marketplace, jaraba_billing)
```

### 4.2 Estructura de Directorios Estandar

**Arquetipo A: Modulo Drupal Puro** (funding, multiregion, institutional, agents)

```
web/modules/custom/jaraba_{modulo}/
├── jaraba_{modulo}.info.yml
├── jaraba_{modulo}.module              # Hooks: preprocess_html, preprocess_page,
│                                       # theme, theme_suggestions, entity hooks
├── jaraba_{modulo}.install             # hook_install(), hook_update_N(),
│                                       # hook_schema() si requiere tablas custom
├── jaraba_{modulo}.services.yml        # Servicios con DI completa
├── jaraba_{modulo}.routing.yml         # Rutas frontend + API REST
├── jaraba_{modulo}.links.menu.yml      # Menu en /admin/structure
├── jaraba_{modulo}.links.task.yml      # Tabs en /admin/content
├── jaraba_{modulo}.links.action.yml    # Acciones (crear nueva entidad)
├── jaraba_{modulo}.permissions.yml     # Permisos RBAC
├── jaraba_{modulo}.libraries.yml       # CSS + JS libraries
├── config/
│   ├── install/                        # Config inicial (taxonomias, settings)
│   └── schema/
│       └── jaraba_{modulo}.schema.yml  # Schema de configuracion
├── src/
│   ├── Entity/                         # Content Entities con Field UI
│   ├── Controller/                     # Dashboard + API controllers
│   ├── Service/                        # Servicios de negocio (DI)
│   ├── Form/                           # Formularios CRUD + Settings
│   ├── Access/                         # AccessControlHandler por entidad
│   └── EventSubscriber/                # Event subscribers (si aplica)
├── templates/
│   ├── page--{modulo}.html.twig        # Zero-region page template
│   └── partials/                       # Parciales reutilizables
│       ├── _{entity}-card.html.twig
│       └── _{feature}-widget.html.twig
├── scss/
│   ├── main.scss                       # Entry point SCSS
│   └── _{feature}.scss                 # Parciales BEM
├── css/
│   └── jaraba-{modulo}.css             # Output compilado
├── js/
│   └── {modulo}-dashboard.js           # Behaviors Drupal
├── images/icons/
│   ├── {icon}.svg                      # Iconos normal
│   └── {icon}-duotone.svg              # Iconos duotone
├── tests/
│   └── src/
│       ├── Unit/                       # Tests unitarios
│       └── Kernel/                     # Tests kernel (con synthetic services)
└── package.json                        # Build SCSS (Dart Sass ^1.71.0)
```

**Arquetipo B: Hibrido Drupal + Python** (predictive)

```
web/modules/custom/jaraba_predictive/
├── [... misma estructura Drupal que Arquetipo A ...]
├── scripts/
│   └── python/
│       ├── requirements.txt            # scikit-learn, pandas, redis, numpy
│       ├── train_churn_model.py        # Entrenamiento del modelo ML
│       ├── predict.py                  # Prediccion CLI (JSON stdin/stdout)
│       ├── train_lead_scorer.py        # Lead scoring model
│       └── forecast.py                 # MRR/ARR forecasting
└── src/
    └── Service/
        └── PredictionBridge.php        # PHP wrapper: proc_open() + JSON I/O
```

**Arquetipo C: Mobile Nativo** (mobile — modulo Drupal + app Capacitor)

```
web/modules/custom/jaraba_mobile/
├── [... estructura Drupal para push notifications + device registry ...]
│
# Proyecto Capacitor separado (fuera de web/modules):
jaraba-app/
├── capacitor.config.ts
├── package.json                        # Capacitor + Ionic React + plugins
├── src/
│   ├── App.tsx                         # Root component
│   ├── pages/                          # Paginas React
│   ├── components/                     # Componentes reutilizables
│   ├── services/                       # API client + offline sync
│   └── plugins/                        # Wrappers de plugins nativos
├── ios/                                # Proyecto Xcode generado
├── android/                            # Proyecto Android Studio generado
└── .github/
    └── workflows/
        └── build-mobile.yml            # CI/CD con Fastlane
```

**Arquetipo D: SDK/Framework** (connector_sdk)

```
web/modules/custom/jaraba_connector_sdk/
├── [... estructura Drupal para marketplace + certificacion ...]
├── sdk/
│   ├── ConnectorInterface.php          # Interface que conectores implementan
│   ├── BaseConnector.php               # Clase base abstracta
│   ├── connector-template/             # Scaffold para nuevos conectores
│   │   ├── connector.info.yml.tpl
│   │   ├── src/Plugin/Connector/MyConnector.php.tpl
│   │   └── tests/MyConnectorTest.php.tpl
│   └── docs/
│       └── getting-started.md          # Guia para desarrolladores
└── sandbox/
    └── .lando.yml                      # Entorno de pruebas aislado
```

### 4.3 Arquetipos Tecnologicos

| Arquetipo | Docs | Stack Principal | Componentes Evaluados | Modulos N2 |
|-----------|------|----------------|----------------------|------------|
| **A: Drupal Puro** | 186, 188, 190, 191, 192 | PHP 8.4 + Drupal 11 | C1-C12 (12 componentes estandar) | agents, multiregion, institutional, funding |
| **B: Hibrido Drupal+Python** | 189 | PHP + Python (scikit-learn) | C1-C12 + C13-C16 (scripts Python, modelo ML, cron training, PHP bridge) | predictive |
| **C: Mobile Nativo** | 187 | Capacitor + React + Drupal API | CM1-CM8 (capacitor config, plugins, push, deep links, offline, app store) | mobile |
| **D: SDK/Framework** | 193 | PHP SDK + Docker sandbox | CS1-CS8 (SDK boilerplate, interface, sandbox, CI/CD, marketplace, revenue share) | connector_sdk |

**Framework de evaluacion por arquetipo (del doc 202):**

| Componente | A: Drupal | B: Hibrido | C: Mobile | D: SDK |
|-----------|-----------|-----------|-----------|--------|
| C1: info.yml | Si | Si | Si | Si |
| C2: permissions.yml | Si | Si | Si | Si |
| C3: routing.yml | Si | Si | Parcial (API) | Parcial (marketplace) |
| C4: services.yml | Si | Si | Si | Si |
| C5: Entity PHP | Si | Si | Si | Si |
| C6: Service contracts | Si | Si | Si | Si |
| C7: Controllers | Si | Si | Si | Si |
| C8: Forms | Si | Si | No (mobile UI) | Si |
| C9: config/install | Si | Si | Si | Si |
| C10: config/schema | Si | Si | Si | Si |
| C11: ECA/hooks | Si | Si | No | Si |
| C12: Templates | Si | Si | No (React) | Si |
| C13: Python scripts | — | Si | — | — |
| C14: ML model | — | Si | — | — |
| C15: Cron scheduler | — | Si | — | — |
| C16: PHP-Python bridge | — | Si | — | — |
| CM1-CM8: Mobile config | — | — | Si | — |
| CS1-CS8: SDK config | — | — | — | Si |

---

## 5. Estado por Fases

| Macro-Fase | Sub-Fase | Descripcion | Modulo | Entidades | Estado | Dependencia |
|------------|----------|-------------|--------|-----------|--------|-------------|
| **1** | 1A | Gestion de Fondos Europeos | jaraba_funding | FundingOpportunity, FundingApplication, TechnicalReport | ⬜ | Ninguna |
| **1** | 1B | Expansion Multi-Pais | jaraba_multiregion | TenantRegion, TaxRule, CurrencyRate, ViesValidation | ⬜ | jaraba_billing |
| **2** | 2A | Integracion STO/PIIL/FUNDAE | jaraba_institutional | InstitutionalProgram, ProgramParticipant, StoFicha | ⬜ | doc 45, doc 89 |
| **2** | 2B | Agentes IA Autonomos | jaraba_agents | AutonomousAgent, AgentExecution, AgentApproval | ⬜ | jaraba_ai_agents |
| **3** | 3A | Modelos Predictivos ML | jaraba_predictive | ChurnPrediction, LeadScore, Forecast | ⬜ | jaraba_foc, jaraba_billing |
| **3** | 3B | Orquestacion Multi-Agent | jaraba_agents (ext) | AgentConversation, AgentHandoff | ⬜ | Fase 2B (HARD) |
| **4** | 4A | App Nativa iOS/Android | jaraba_mobile | MobileDevice, PushNotification | ⬜ | APIs verticales |
| **4** | 4B | SDK Conectores + Marketplace | jaraba_connector_sdk | Connector, ConnectorInstall | ⬜ | doc 112, jaraba_billing |

**Diagrama de dependencias:**

```
FASE 1A (Funding) ──────────── (independiente, quick win)
FASE 1B (Multi-Region) ─────── (independiente, extiende billing)
                                     ↓ paralelo
FASE 2A (Institutional) ────── (depende de doc 45 + doc 89 existentes)
FASE 2B (Agents Base) ──────── (depende de jaraba_ai_agents existente)
                                     │
                                     ↓ secuencial HARD
FASE 3A (Predictive) ───────── (independiente de 3B, depende soft de foc)
FASE 3B (Orchestration) ────── (depende HARD de 2B — BaseAutonomousAgent)
                                     ↓ paralelo
FASE 4A (Mobile) ───────────── (depende soft de APIs todos los verticales)
FASE 4B (SDK) ──────────────── (depende de marketplace + billing existentes)

Paralelismo posible:
- Fase 1A ∥ Fase 1B (totalmente independientes)
- Fase 2A ∥ Fase 2B (totalmente independientes)
- Fase 3A ∥ Fase 3B (independientes, pero 3B depende de 2B)
- Fase 4A ∥ Fase 4B (totalmente independientes)
```

---

## 6. MACRO-FASE 1: Quick Wins — Funding + Multi-Region

### 6.1 FASE 1A: jaraba_funding — Gestion de Fondos Europeos y Subvenciones

**Justificacion:** Modulo CRUD relativamente simple con el mayor impacto inmediato. El tracking de convocatorias de fondos europeos (Kit Digital, PRTR, FSE+, Erasmus+) y la generacion automatica de memorias tecnicas via IA aportan un revenue stream nuevo y un diferenciador fuerte para tenants. No tiene dependencias externas.

#### 6.1.1 Entidad `FundingOpportunity` (Convocatoria de Fondos)

**Tipo:** ContentEntity
**ID:** `funding_opportunity`
**Base table:** `funding_opportunity`

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | Si | PK autoincremental |
| `uuid` | uuid | Si | Identificador universal unico |
| `tenant_id` | entity_reference (group) | Si | Aislamiento multi-tenant obligatorio (AUDIT-CONS-005) |
| `uid` | entity_reference (user) | Si | Usuario creador (EntityOwnerInterface) |
| `name` | string(255) | Si | Nombre de la convocatoria |
| `funding_body` | string(255) | Si | Organismo convocante (Red.es, SEPE, Junta, UE) |
| `program` | string(100) | No | Programa marco (Kit Digital, FSE+, PRTR, Erasmus+) |
| `max_amount` | decimal(12,2) | No | Importe maximo de la convocatoria |
| `deadline` | datetime | No | Plazo limite de solicitud |
| `requirements` | text_long | No | Requisitos de elegibilidad (formato estructurado) |
| `documentation_required` | text_long | No | Documentacion requerida |
| `status` | list_string | Si | upcoming, open, closed, resolved. Default: upcoming |
| `url` | link | No | URL oficial de la convocatoria |
| `alert_days_before` | integer | No | Dias antes del deadline para alertar. Default: 15 |
| `notes` | text_long | No | Notas internas del equipo |
| `created` | created | Si | Timestamp creacion |
| `changed` | changed | Si | Timestamp modificacion |

**Indices DB:** `tenant_id`, `status`, `deadline`, `program`.

**Handlers:**

| Handler | Clase |
|---------|-------|
| list_builder | `FundingOpportunityListBuilder` |
| views_data | `EntityViewsData` (Drupal core) |
| form (default/add/edit) | `FundingOpportunityForm` |
| form (delete) | `ContentEntityDeleteForm` |
| access | `FundingOpportunityAccessControlHandler` |
| route_provider (html) | `AdminHtmlRouteProvider` |

#### 6.1.2 Entidad `FundingApplication` (Solicitud de Fondos)

**Tipo:** ContentEntity
**ID:** `funding_application`
**Base table:** `funding_application`

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | Si | PK autoincremental |
| `uuid` | uuid | Si | Identificador universal unico |
| `tenant_id` | entity_reference (group) | Si | Aislamiento multi-tenant |
| `uid` | entity_reference (user) | Si | Usuario responsable |
| `opportunity_id` | entity_reference (funding_opportunity) | Si | Convocatoria asociada |
| `application_number` | string(32) | Si | Auto: SOL-YYYY-NNNN (preSave, ENTITY-AUTONUMBER-001) |
| `status` | list_string | Si | draft, submitted, approved, rejected, justifying, closed. Default: draft |
| `amount_requested` | decimal(12,2) | No | Importe solicitado |
| `amount_approved` | decimal(12,2) | No | Importe aprobado (tras resolucion) |
| `submission_date` | datetime | No | Fecha de envio de la solicitud |
| `resolution_date` | datetime | No | Fecha de resolucion |
| `budget_breakdown` | text_long | No | Desglose presupuestario (JSON serializado) |
| `impact_indicators` | text_long | No | Indicadores de impacto (JSON serializado) |
| `justification_notes` | text_long | No | Notas de justificacion |
| `next_deadline` | datetime | No | Proximo plazo relevante |
| `created` | created | Si | Timestamp creacion |
| `changed` | changed | Si | Timestamp modificacion |

**Indices DB:** `tenant_id`, `opportunity_id`, `status`, `application_number` (unique), `next_deadline`.

**Metodo de negocio — auto-numeracion (preSave):**

```php
public function preSave(EntityStorageInterface $storage): void {
  parent::preSave($storage);
  if ($this->isNew() && empty($this->get('application_number')->value)) {
    $year = date('Y');
    $query = $storage->getQuery()
      ->condition('tenant_id', $this->get('tenant_id')->target_id)
      ->condition('application_number', "SOL-{$year}-", 'STARTS_WITH')
      ->accessCheck(FALSE)
      ->count();
    $count = (int) $query->execute();
    $this->set('application_number', sprintf('SOL-%s-%04d', $year, $count + 1));
  }
}
```

#### 6.1.3 Entidad `TechnicalReport` (Memoria Tecnica Generada)

**Tipo:** ContentEntity
**ID:** `technical_report`
**Base table:** `technical_report`

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | Si | PK autoincremental |
| `uuid` | uuid | Si | Identificador universal unico |
| `tenant_id` | entity_reference (group) | Si | Aislamiento multi-tenant |
| `uid` | entity_reference (user) | Si | Usuario generador |
| `application_id` | entity_reference (funding_application) | Si | Solicitud asociada |
| `title` | string(255) | Si | Titulo de la memoria tecnica |
| `report_type` | list_string | Si | initial, progress, final, justification |
| `content_sections` | text_long | No | Secciones de la memoria (JSON: titulo + contenido por seccion) |
| `ai_generated` | boolean | No | Si fue generada con asistencia IA. Default: FALSE |
| `ai_model_used` | string(50) | No | Modelo IA usado (si aplica) |
| `file_id` | entity_reference (file) | No | PDF generado (si existe) |
| `status` | list_string | Si | draft, review, approved, submitted. Default: draft |
| `created` | created | Si | Timestamp creacion |
| `changed` | changed | Si | Timestamp modificacion |

**Indices DB:** `tenant_id`, `application_id`, `status`, `report_type`.

#### 6.1.4 Services

| Service ID | Clase | Metodos Clave | Descripcion |
|-----------|-------|---------------|-------------|
| `jaraba_funding.opportunity_tracker` | `OpportunityTrackerService` | `checkDeadlines()`, `getActiveOpportunities()`, `sendDeadlineAlerts()` | Tracking de convocatorias y alertas de plazos |
| `jaraba_funding.application_manager` | `ApplicationManagerService` | `createApplication()`, `submitApplication()`, `updateStatus()`, `getStats()` | Gestion del ciclo de vida de solicitudes |
| `jaraba_funding.report_generator` | `ReportGeneratorService` | `generateReport()`, `generateWithAi()`, `exportToPdf()` | Generacion de memorias tecnicas (manual + IA) |
| `jaraba_funding.budget_analyzer` | `BudgetAnalyzerService` | `calculateBudget()`, `getBreakdown()`, `validateEligibility()` | Analisis presupuestario y validacion de elegibilidad |
| `jaraba_funding.impact_calculator` | `ImpactCalculatorService` | `calculateIndicators()`, `getBaselineMetrics()`, `generateImpactReport()` | Calculo de indicadores de impacto desde metricas reales |

**Inyeccion de dependencias (`jaraba_funding.services.yml`):**

```yaml
services:
  logger.channel.jaraba_funding:
    parent: logger.channel_base
    arguments: ['jaraba_funding']

  jaraba_funding.opportunity_tracker:
    class: Drupal\jaraba_funding\Service\OpportunityTrackerService
    arguments:
      - '@entity_type.manager'
      - '@ecosistema_jaraba_core.tenant_context'
      - '@jaraba_email.mailer'
      - '@logger.channel.jaraba_funding'

  jaraba_funding.application_manager:
    class: Drupal\jaraba_funding\Service\ApplicationManagerService
    arguments:
      - '@entity_type.manager'
      - '@ecosistema_jaraba_core.tenant_context'
      - '@logger.channel.jaraba_funding'

  jaraba_funding.report_generator:
    class: Drupal\jaraba_funding\Service\ReportGeneratorService
    arguments:
      - '@entity_type.manager'
      - '@ecosistema_jaraba_core.tenant_context'
      - '@ai.provider'
      - '@logger.channel.jaraba_funding'

  jaraba_funding.budget_analyzer:
    class: Drupal\jaraba_funding\Service\BudgetAnalyzerService
    arguments:
      - '@entity_type.manager'
      - '@ecosistema_jaraba_core.tenant_context'
      - '@logger.channel.jaraba_funding'

  jaraba_funding.impact_calculator:
    class: Drupal\jaraba_funding\Service\ImpactCalculatorService
    arguments:
      - '@entity_type.manager'
      - '@ecosistema_jaraba_core.tenant_context'
      - '@jaraba_foc.kpi_service'
      - '@logger.channel.jaraba_funding'
```

#### 6.1.5 Controllers y API Endpoints

**Rutas principales (`jaraba_funding.routing.yml`):**

```yaml
# Dashboard frontend (zero-region)
jaraba_funding.dashboard:
  path: '/funding'
  defaults:
    _controller: '\Drupal\jaraba_funding\Controller\FundingDashboardController::dashboard'
    _title: 'Fondos y Subvenciones'
  requirements:
    _permission: 'access funding'

# API REST — Convocatorias
jaraba_funding.api.opportunities.list:
  path: '/api/v1/funding/opportunities'
  defaults:
    _controller: '\Drupal\jaraba_funding\Controller\FundingApiController::listOpportunities'
  requirements:
    _permission: 'access funding api'
  methods: [GET]

jaraba_funding.api.opportunities.store:
  path: '/api/v1/funding/opportunities'
  defaults:
    _controller: '\Drupal\jaraba_funding\Controller\FundingApiController::storeOpportunity'
  requirements:
    _permission: 'manage funding opportunities'
  methods: [POST]

jaraba_funding.api.opportunities.show:
  path: '/api/v1/funding/opportunities/{funding_opportunity}'
  defaults:
    _controller: '\Drupal\jaraba_funding\Controller\FundingApiController::showOpportunity'
  requirements:
    _permission: 'access funding api'
  methods: [GET]

jaraba_funding.api.opportunities.update:
  path: '/api/v1/funding/opportunities/{funding_opportunity}'
  defaults:
    _controller: '\Drupal\jaraba_funding\Controller\FundingApiController::updateOpportunity'
  requirements:
    _permission: 'manage funding opportunities'
  methods: [PATCH]

# API REST — Solicitudes
jaraba_funding.api.applications.list:
  path: '/api/v1/funding/applications'
  defaults:
    _controller: '\Drupal\jaraba_funding\Controller\FundingApiController::listApplications'
  requirements:
    _permission: 'access funding api'
  methods: [GET]

jaraba_funding.api.applications.store:
  path: '/api/v1/funding/applications'
  defaults:
    _controller: '\Drupal\jaraba_funding\Controller\FundingApiController::storeApplication'
  requirements:
    _permission: 'manage funding applications'
  methods: [POST]

jaraba_funding.api.applications.show:
  path: '/api/v1/funding/applications/{funding_application}'
  defaults:
    _controller: '\Drupal\jaraba_funding\Controller\FundingApiController::showApplication'
  requirements:
    _permission: 'access funding api'
  methods: [GET]

jaraba_funding.api.applications.update:
  path: '/api/v1/funding/applications/{funding_application}'
  defaults:
    _controller: '\Drupal\jaraba_funding\Controller\FundingApiController::updateApplication'
  requirements:
    _permission: 'manage funding applications'
  methods: [PATCH]

jaraba_funding.api.applications.submit:
  path: '/api/v1/funding/applications/{funding_application}/submit'
  defaults:
    _controller: '\Drupal\jaraba_funding\Controller\FundingApiController::submitApplication'
  requirements:
    _permission: 'manage funding applications'
  methods: [POST]

# API REST — Memorias Tecnicas
jaraba_funding.api.reports.generate:
  path: '/api/v1/funding/applications/{funding_application}/report'
  defaults:
    _controller: '\Drupal\jaraba_funding\Controller\FundingApiController::generateReport'
  requirements:
    _permission: 'manage funding applications'
  methods: [POST]

jaraba_funding.api.reports.generate_ai:
  path: '/api/v1/funding/applications/{funding_application}/report/ai'
  defaults:
    _controller: '\Drupal\jaraba_funding\Controller\FundingApiController::generateReportWithAi'
  requirements:
    _permission: 'manage funding applications'
  methods: [POST]

# API REST — Stats
jaraba_funding.api.stats:
  path: '/api/v1/funding/stats'
  defaults:
    _controller: '\Drupal\jaraba_funding\Controller\FundingApiController::stats'
  requirements:
    _permission: 'access funding api'
  methods: [GET]

# Configuracion admin
jaraba_funding.settings:
  path: '/admin/config/jaraba/funding'
  defaults:
    _form: '\Drupal\jaraba_funding\Form\FundingSettingsForm'
    _title: 'Configuracion de Fondos'
  requirements:
    _permission: 'administer funding'
```

**Total: 13 endpoints** (1 dashboard + 10 API REST + 1 AI generation + 1 settings)

---

### 6.2 FASE 1B: jaraba_multiregion — Expansion Multi-Pais

**Justificacion:** Habilitar la expansion a Portugal (Q3 2026), Francia (Q4 2026) e Italia (Q1 2027). El modulo gestiona multi-currency en Stripe, calculo de IVA por jurisdiccion (incluyendo inversion sujeto pasivo UE), validacion VIES de numeros VAT, y data residency por tenant. Depende suavemente de jaraba_billing para la integracion Stripe.

#### 6.2.1 Entidad `TenantRegion` (Configuracion Regional del Tenant)

**Tipo:** ContentEntity
**ID:** `tenant_region`
**Base table:** `tenant_region`

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | Si | PK autoincremental |
| `uuid` | uuid | Si | Identificador universal unico |
| `tenant_id` | entity_reference (group) | Si | Aislamiento multi-tenant |
| `uid` | entity_reference (user) | Si | Usuario que configuro |
| `base_currency` | list_string | Si | EUR, USD, GBP, BRL. Default: EUR |
| `display_currencies` | text_long | No | Monedas para display (JSON array) |
| `stripe_account_country` | string(2) | No | Pais de la cuenta Stripe (ISO 3166-1 alpha-2) |
| `data_region` | list_string | Si | eu-west, eu-central, us-east, latam. Default: eu-west |
| `primary_dc` | string(50) | No | Datacenter principal |
| `legal_jurisdiction` | string(2) | Si | Jurisdiccion legal (ES, PT, FR, IT). Default: ES |
| `vat_number` | string(20) | No | Numero IVA/NIF intracomunitario |
| `vies_validated` | boolean | No | Validado contra VIES. Default: FALSE |
| `vies_validated_at` | datetime | No | Fecha de validacion VIES |
| `gdpr_representative` | string(255) | No | Representante GDPR (si fuera de UE) |
| `created` | created | Si | Timestamp creacion |
| `changed` | changed | Si | Timestamp modificacion |

**Indices DB:** `tenant_id` (unique — un registro por tenant), `legal_jurisdiction`, `data_region`.

#### 6.2.2 Entidad `TaxRule` (Regla Fiscal por Jurisdiccion)

**Tipo:** ContentEntity
**ID:** `tax_rule`
**Base table:** `tax_rule`

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | Si | PK autoincremental |
| `uuid` | uuid | Si | Identificador universal unico |
| `country_code` | string(2) | Si | Pais (ISO 3166-1 alpha-2) |
| `tax_name` | string(50) | Si | Nombre del impuesto (IVA, TVA, MwSt) |
| `standard_rate` | decimal(5,2) | Si | Tipo general (ej: 21.00 para Espana) |
| `reduced_rate` | decimal(5,2) | No | Tipo reducido (ej: 10.00) |
| `super_reduced_rate` | decimal(5,2) | No | Tipo superreducido (ej: 4.00) |
| `digital_services_rate` | decimal(5,2) | No | Tipo para servicios digitales (si difiere) |
| `oss_threshold` | decimal(12,2) | No | Umbral OSS (One Stop Shop). Default: 10000.00 |
| `reverse_charge_enabled` | boolean | Si | Permite inversion sujeto pasivo B2B. Default: TRUE |
| `eu_member` | boolean | Si | Es miembro de la UE. Default: TRUE |
| `effective_from` | datetime | Si | Fecha de entrada en vigor |
| `effective_to` | datetime | No | Fecha de fin de vigencia (NULL = vigente) |
| `created` | created | Si | Timestamp creacion |
| `changed` | changed | Si | Timestamp modificacion |

**Indices DB:** `country_code`, `effective_from`, `eu_member`.

#### 6.2.3 Entidad `CurrencyRate` (Tipo de Cambio)

**Tipo:** ContentEntity
**ID:** `currency_rate`
**Base table:** `currency_rate`

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | Si | PK autoincremental |
| `uuid` | uuid | Si | Identificador universal unico |
| `from_currency` | string(3) | Si | Moneda origen (ISO 4217) |
| `to_currency` | string(3) | Si | Moneda destino (ISO 4217) |
| `rate` | decimal(12,6) | Si | Tipo de cambio |
| `source` | list_string | Si | ecb (BCE), manual, stripe. Default: ecb |
| `fetched_at` | datetime | Si | Fecha de obtencion del tipo |
| `created` | created | Si | Timestamp creacion |

**Indices DB:** `from_currency` + `to_currency` (composite), `fetched_at`.

#### 6.2.4 Entidad `ViesValidation` (Validacion VAT UE)

**Tipo:** ContentEntity
**ID:** `vies_validation`
**Base table:** `vies_validation`

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | Si | PK autoincremental |
| `uuid` | uuid | Si | Identificador universal unico |
| `tenant_id` | entity_reference (group) | Si | Tenant que solicito la validacion |
| `vat_number` | string(20) | Si | Numero VAT validado |
| `country_code` | string(2) | Si | Pais del VAT |
| `is_valid` | boolean | Si | Resultado de la validacion |
| `company_name` | string(255) | No | Nombre de la empresa (retornado por VIES) |
| `company_address` | text_long | No | Direccion (retornada por VIES) |
| `request_identifier` | string(50) | No | ID de consulta VIES |
| `validated_at` | datetime | Si | Fecha de la validacion |
| `created` | created | Si | Timestamp creacion |

**Indices DB:** `tenant_id`, `vat_number`, `validated_at`.

#### 6.2.5 Services

| Service ID | Clase | Metodos Clave | Descripcion |
|-----------|-------|---------------|-------------|
| `jaraba_multiregion.region_manager` | `RegionManagerService` | `getRegion()`, `setRegion()`, `getAvailableRegions()` | Gestion de configuracion regional por tenant |
| `jaraba_multiregion.tax_calculator` | `TaxCalculatorService` | `calculate()`, `getTaxRule()`, `isReverseCharge()`, `getOssStatus()` | Calculo de IVA por jurisdiccion con logica B2B/B2C |
| `jaraba_multiregion.vies_validator` | `ViesValidatorService` | `validate()`, `getLastValidation()`, `isExpired()` | Validacion de numeros VAT contra API VIES de la UE |
| `jaraba_multiregion.currency_converter` | `CurrencyConverterService` | `convert()`, `fetchRates()`, `getRate()` | Conversion de monedas con tipos del BCE |
| `jaraba_multiregion.regional_compliance` | `RegionalComplianceService` | `checkCompliance()`, `getRequirements()`, `generateReport()` | Compliance por jurisdiccion (GDPR, facturacion, idioma) |

**Inyeccion de dependencias (`jaraba_multiregion.services.yml`):**

```yaml
services:
  logger.channel.jaraba_multiregion:
    parent: logger.channel_base
    arguments: ['jaraba_multiregion']

  jaraba_multiregion.region_manager:
    class: Drupal\jaraba_multiregion\Service\RegionManagerService
    arguments:
      - '@entity_type.manager'
      - '@ecosistema_jaraba_core.tenant_context'
      - '@logger.channel.jaraba_multiregion'

  jaraba_multiregion.tax_calculator:
    class: Drupal\jaraba_multiregion\Service\TaxCalculatorService
    arguments:
      - '@entity_type.manager'
      - '@jaraba_multiregion.region_manager'
      - '@logger.channel.jaraba_multiregion'

  jaraba_multiregion.vies_validator:
    class: Drupal\jaraba_multiregion\Service\ViesValidatorService
    arguments:
      - '@entity_type.manager'
      - '@http_client'
      - '@logger.channel.jaraba_multiregion'

  jaraba_multiregion.currency_converter:
    class: Drupal\jaraba_multiregion\Service\CurrencyConverterService
    arguments:
      - '@entity_type.manager'
      - '@http_client'
      - '@logger.channel.jaraba_multiregion'

  jaraba_multiregion.regional_compliance:
    class: Drupal\jaraba_multiregion\Service\RegionalComplianceService
    arguments:
      - '@jaraba_multiregion.region_manager'
      - '@jaraba_multiregion.tax_calculator'
      - '@logger.channel.jaraba_multiregion'
```

#### 6.2.6 Controllers y API Endpoints

```yaml
# Dashboard admin (multi-region es configuracion, no frontend de tenant)
jaraba_multiregion.settings:
  path: '/admin/config/jaraba/regions'
  defaults:
    _form: '\Drupal\jaraba_multiregion\Form\RegionSettingsForm'
    _title: 'Configuracion Multi-Region'
  requirements:
    _permission: 'administer multiregion'

# API REST — Regiones
jaraba_multiregion.api.region.show:
  path: '/api/v1/regions/current'
  defaults:
    _controller: '\Drupal\jaraba_multiregion\Controller\RegionApiController::showCurrent'
  requirements:
    _permission: 'access multiregion api'
  methods: [GET]

jaraba_multiregion.api.region.update:
  path: '/api/v1/regions/current'
  defaults:
    _controller: '\Drupal\jaraba_multiregion\Controller\RegionApiController::updateRegion'
  requirements:
    _permission: 'manage regions'
  methods: [PATCH]

# API REST — Impuestos
jaraba_multiregion.api.tax.calculate:
  path: '/api/v1/tax/calculate'
  defaults:
    _controller: '\Drupal\jaraba_multiregion\Controller\RegionApiController::calculateTax'
  requirements:
    _permission: 'access multiregion api'
  methods: [POST]

jaraba_multiregion.api.tax.rules:
  path: '/api/v1/tax/rules'
  defaults:
    _controller: '\Drupal\jaraba_multiregion\Controller\RegionApiController::listTaxRules'
  requirements:
    _permission: 'access multiregion api'
  methods: [GET]

# API REST — VIES
jaraba_multiregion.api.vies.validate:
  path: '/api/v1/vies/validate'
  defaults:
    _controller: '\Drupal\jaraba_multiregion\Controller\RegionApiController::validateVies'
  requirements:
    _permission: 'manage regions'
  methods: [POST]

jaraba_multiregion.api.vies.history:
  path: '/api/v1/vies/history'
  defaults:
    _controller: '\Drupal\jaraba_multiregion\Controller\RegionApiController::viesHistory'
  requirements:
    _permission: 'access multiregion api'
  methods: [GET]

# API REST — Monedas
jaraba_multiregion.api.currency.convert:
  path: '/api/v1/currency/convert'
  defaults:
    _controller: '\Drupal\jaraba_multiregion\Controller\RegionApiController::convertCurrency'
  requirements:
    _permission: 'access multiregion api'
  methods: [POST]

jaraba_multiregion.api.currency.rates:
  path: '/api/v1/currency/rates'
  defaults:
    _controller: '\Drupal\jaraba_multiregion\Controller\RegionApiController::currentRates'
  requirements:
    _permission: 'access multiregion api'
  methods: [GET]

# API REST — Compliance regional
jaraba_multiregion.api.compliance.check:
  path: '/api/v1/regions/compliance'
  defaults:
    _controller: '\Drupal\jaraba_multiregion\Controller\RegionApiController::checkCompliance'
  requirements:
    _permission: 'access multiregion api'
  methods: [GET]
```

**Total: 11 endpoints** (1 settings + 10 API REST)

**Logica clave — calculo de IVA (TaxCalculatorService):**

```php
/**
 * Calcula el IVA aplicable segun escenario fiscal.
 *
 * Logica de decision basada en Art. 196 Directiva 2006/112/CE
 * y regimen OSS (One Stop Shop) de la UE.
 *
 * @param string $seller_country Pais del vendedor (ISO 3166-1 alpha-2)
 * @param string $buyer_country Pais del comprador
 * @param bool $buyer_is_business Si el comprador es empresa (B2B)
 * @param string|null $buyer_vat Numero VAT del comprador (si B2B)
 * @param float $amount Importe base sin impuestos
 *
 * @return array{rate: float, amount: float, reverse_charge: bool, article: string}
 */
public function calculate(
  string $seller_country,
  string $buyer_country,
  bool $buyer_is_business,
  ?string $buyer_vat,
  float $amount,
): array {
  // Mismo pais → IVA local siempre
  if ($seller_country === $buyer_country) {
    $rule = $this->getTaxRule($seller_country);
    return [
      'rate' => $rule->get('standard_rate')->value,
      'amount' => $amount * ($rule->get('standard_rate')->value / 100),
      'reverse_charge' => FALSE,
      'article' => '',
    ];
  }

  // B2B UE con VAT valido → Inversion sujeto pasivo (0%)
  if ($buyer_is_business && $buyer_vat && $this->isEuMember($buyer_country)) {
    $viesResult = $this->viesValidator->validate($buyer_vat);
    if ($viesResult['is_valid']) {
      return [
        'rate' => 0.0,
        'amount' => 0.0,
        'reverse_charge' => TRUE,
        'article' => 'Art. 196 Directiva 2006/112/CE',
      ];
    }
  }

  // B2C UE → IVA del pais destino (bajo regimen OSS)
  if (!$buyer_is_business && $this->isEuMember($buyer_country)) {
    $rule = $this->getTaxRule($buyer_country);
    return [
      'rate' => $rule->get('digital_services_rate')->value ?? $rule->get('standard_rate')->value,
      'amount' => $amount * (($rule->get('digital_services_rate')->value ?? $rule->get('standard_rate')->value) / 100),
      'reverse_charge' => FALSE,
      'article' => 'Regimen OSS',
    ];
  }

  // Fuera UE → Exento
  return [
    'rate' => 0.0,
    'amount' => 0.0,
    'reverse_charge' => FALSE,
    'article' => 'Exportacion fuera UE',
  ];
}
```

---

## 7. MACRO-FASE 2: Core Growth — Institutional + Agents

### 7.1 FASE 2A: jaraba_institutional — Integracion STO/PIIL/FUNDAE

**Justificacion:** Integracion con el Servicio Telematico de Orientacion (STO) del SAE y los programas PIIL/FUNDAE/FSE+ para automatizar la generacion de fichas tecnicas, tracking de participantes, y reporting institucional. Dependencia con doc 45 (Andalucia +ei) para reutilizar entidades de programa/participante y con doc 89 (Firma Digital) para PAdES en fichas STO.

#### 7.1.1 Entidad `InstitutionalProgram` (Programa Institucional)

**Tipo:** ContentEntity
**ID:** `institutional_program`
**Base table:** `institutional_program`

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | Si | PK autoincremental |
| `uuid` | uuid | Si | Identificador universal unico |
| `tenant_id` | entity_reference (group) | Si | Tenant operador del programa |
| `uid` | entity_reference (user) | Si | Responsable del programa |
| `program_type` | list_string | Si | sto, piil, fundae, fse_plus, other. Default: sto |
| `program_code` | string(50) | Si | Codigo oficial del programa |
| `name` | string(255) | Si | Nombre del programa |
| `funding_entity` | string(255) | Si | Entidad financiadora (SAE, SEPE, Junta, UE) |
| `start_date` | datetime | Si | Fecha inicio del programa |
| `end_date` | datetime | No | Fecha fin del programa |
| `budget_total` | decimal(12,2) | No | Presupuesto total asignado |
| `budget_executed` | decimal(12,2) | No | Presupuesto ejecutado |
| `participants_target` | integer | No | Participantes objetivo |
| `participants_actual` | integer | No | Participantes reales (calculado) |
| `status` | list_string | Si | draft, active, reporting, closed, audited. Default: draft |
| `reporting_deadlines` | text_long | No | Plazos de justificacion (JSON) |
| `notes` | text_long | No | Notas internas |
| `created` | created | Si | Timestamp creacion |
| `changed` | changed | Si | Timestamp modificacion |

**Indices DB:** `tenant_id`, `program_type`, `status`, `program_code`, `start_date`.

#### 7.1.2 Entidad `ProgramParticipant` (Participante de Programa)

**Tipo:** ContentEntity
**ID:** `program_participant`
**Base table:** `program_participant`

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | Si | PK autoincremental |
| `uuid` | uuid | Si | Identificador universal unico |
| `tenant_id` | entity_reference (group) | Si | Aislamiento multi-tenant |
| `program_id` | entity_reference (institutional_program) | Si | Programa al que pertenece |
| `user_id` | entity_reference (user) | Si | Usuario participante |
| `enrollment_date` | datetime | Si | Fecha de alta en el programa |
| `exit_date` | datetime | No | Fecha de baja |
| `exit_reason` | list_string | No | completed, employment, dropout, other |
| `sto_ficha_id` | string(50) | No | ID de la ficha STO (si aplica) |
| `employment_outcome` | list_string | No | employed, self_employed, training, unemployed |
| `employment_date` | datetime | No | Fecha de insercion laboral |
| `hours_orientation` | decimal(6,2) | No | Horas de orientacion recibidas |
| `hours_training` | decimal(6,2) | No | Horas de formacion recibidas |
| `certifications_obtained` | text_long | No | Certificaciones obtenidas (JSON) |
| `status` | list_string | Si | active, completed, dropout. Default: active |
| `created` | created | Si | Timestamp creacion |
| `changed` | changed | Si | Timestamp modificacion |

**Indices DB:** `tenant_id`, `program_id`, `user_id`, `status`, `employment_outcome`.

#### 7.1.3 Entidad `StoFicha` (Ficha Tecnica STO)

**Tipo:** ContentEntity (append-only, regla ENTITY-APPEND-001)
**ID:** `sto_ficha`
**Base table:** `sto_ficha`

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | Si | PK autoincremental |
| `uuid` | uuid | Si | Identificador universal unico |
| `tenant_id` | entity_reference (group) | Si | Aislamiento multi-tenant |
| `participant_id` | entity_reference (program_participant) | Si | Participante asociado |
| `ficha_number` | string(32) | Si | Auto: STO-YYYY-NNNN (preSave) |
| `ficha_type` | list_string | Si | initial, progress, final. Default: initial |
| `diagnostico_empleabilidad` | text_long | No | Diagnostico (generado desde skills assessment) |
| `itinerario_insercion` | text_long | No | Itinerario personalizado (generado desde learning paths) |
| `acciones_orientacion` | text_long | No | Acciones realizadas (extraidas de logs del sistema) |
| `resultados` | text_long | No | Resultados: insercion, formacion, certificaciones |
| `ai_generated` | boolean | No | Generada con asistencia IA. Default: FALSE |
| `pdf_file_id` | entity_reference (file) | No | PDF generado con formato SAE |
| `signature_status` | list_string | No | pending, signed, rejected. Default: pending |
| `signed_at` | datetime | No | Fecha de firma PAdES |
| `created` | created | Si | Timestamp creacion |

**Indices DB:** `tenant_id`, `participant_id`, `ficha_number` (unique), `ficha_type`.

**Nota:** `StoFicha` es append-only (ENTITY-APPEND-001): solo tiene form handler `default` (crear), sin edit/delete links. `AccessResult::forbidden()` para update/delete.

#### 7.1.4 Services

| Service ID | Clase | Metodos Clave | Descripcion |
|-----------|-------|---------------|-------------|
| `jaraba_institutional.program_manager` | `ProgramManagerService` | `createProgram()`, `getActivePrograms()`, `updateStatus()`, `getStats()` | Gestion del ciclo de vida de programas |
| `jaraba_institutional.participant_tracker` | `ParticipantTrackerService` | `enroll()`, `updateOutcome()`, `getByProgram()`, `calculateIndicators()` | Tracking de participantes y outcomes |
| `jaraba_institutional.sto_generator` | `StoFichaGeneratorService` | `generate()`, `generateWithAi()`, `exportToPdf()`, `signWithPades()` | Generacion automatizada de fichas STO |
| `jaraba_institutional.fundae_reporter` | `FundaeReporterService` | `generateReport()`, `getIndicators()`, `exportToExcel()` | Reporting FUNDAE con indicadores automaticos |
| `jaraba_institutional.fse_reporter` | `FseReporterService` | `calculateImpact()`, `getIndicators()`, `generateFseReport()` | Indicadores de impacto FSE+ |

#### 7.1.5 Controllers y API Endpoints

```yaml
# Dashboard frontend (zero-region)
jaraba_institutional.dashboard:
  path: '/institutional'
  defaults:
    _controller: '\Drupal\jaraba_institutional\Controller\InstitutionalDashboardController::dashboard'
    _title: 'Programas Institucionales'
  requirements:
    _permission: 'access institutional'

# API REST — Programas (CRUD)
jaraba_institutional.api.programs.list:
  path: '/api/v1/institutional/programs'
  methods: [GET]
jaraba_institutional.api.programs.store:
  path: '/api/v1/institutional/programs'
  methods: [POST]
jaraba_institutional.api.programs.show:
  path: '/api/v1/institutional/programs/{institutional_program}'
  methods: [GET]
jaraba_institutional.api.programs.update:
  path: '/api/v1/institutional/programs/{institutional_program}'
  methods: [PATCH]

# API REST — Participantes
jaraba_institutional.api.participants.list:
  path: '/api/v1/institutional/programs/{institutional_program}/participants'
  methods: [GET]
jaraba_institutional.api.participants.enroll:
  path: '/api/v1/institutional/programs/{institutional_program}/participants'
  methods: [POST]
jaraba_institutional.api.participants.update:
  path: '/api/v1/institutional/participants/{program_participant}'
  methods: [PATCH]

# API REST — Fichas STO
jaraba_institutional.api.fichas.generate:
  path: '/api/v1/institutional/participants/{program_participant}/ficha'
  methods: [POST]

# API REST — Reporting
jaraba_institutional.api.reports.fundae:
  path: '/api/v1/institutional/programs/{institutional_program}/report/fundae'
  methods: [GET]
jaraba_institutional.api.reports.fse:
  path: '/api/v1/institutional/programs/{institutional_program}/report/fse'
  methods: [GET]
```

**Total: 11 endpoints** (1 dashboard + 10 API REST)

---

### 7.2 FASE 2B: jaraba_agents — Agentes IA Autonomos

**Justificacion:** Transforma los copilots reactivos (pregunta-respuesta) en agentes autonomos capaces de ejecutar acciones en la plataforma con guardrails de seguridad. Define 5 niveles de autonomia (L0-L4), un sistema de guardrails configurable, y agentes especializados por vertical. Depende de `jaraba_ai_agents` (BaseAgent, AgentInterface existentes).

#### 7.2.1 Entidad `AutonomousAgent` (Configuracion de Agente)

**Tipo:** ContentEntity
**ID:** `autonomous_agent`
**Base table:** `autonomous_agent`

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | Si | PK autoincremental |
| `uuid` | uuid | Si | Identificador universal unico |
| `tenant_id` | entity_reference (group) | No | NULL = agente global, non-NULL = agente de tenant |
| `uid` | entity_reference (user) | Si | Creador/admin del agente |
| `name` | string(100) | Si | Nombre del agente (ej: "Asistente de Enrollment") |
| `agent_type` | list_string | Si | enrollment, planning, support, marketing, analytics |
| `vertical` | list_string | No | empleabilidad, emprendimiento, agro, comercio, servicios, legal, platform |
| `objective` | text_long | Si | Objetivo del agente en lenguaje natural |
| `capabilities` | text_long | Si | Lista de acciones permitidas (JSON array) |
| `guardrails` | text_long | Si | Restricciones y limites (JSON object) |
| `autonomy_level` | list_string | Si | l0_informative, l1_suggestion, l2_semi_autonomous, l3_supervised, l4_full. Default: l1_suggestion |
| `llm_model` | string(50) | No | Modelo LLM a utilizar. Default: gemini-2.0-flash |
| `temperature` | decimal(3,2) | No | Temperatura del LLM (0.00-1.00). Default: 0.30 |
| `max_actions_per_run` | integer | No | Maximo acciones por ejecucion. Default: 10 |
| `requires_approval` | text_long | No | Acciones que requieren aprobacion humana (JSON array) |
| `is_active` | boolean | Si | Agente activo. Default: TRUE |
| `performance_metrics` | text_long | No | Metricas de rendimiento historicas (JSON) |
| `created` | created | Si | Timestamp creacion |
| `changed` | changed | Si | Timestamp modificacion |

**Indices DB:** `tenant_id`, `agent_type`, `vertical`, `is_active`, `autonomy_level`.

#### 7.2.2 Entidad `AgentExecution` (Ejecucion de Agente)

**Tipo:** ContentEntity (append-only, ENTITY-APPEND-001)
**ID:** `agent_execution`
**Base table:** `agent_execution`

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | Si | PK autoincremental |
| `uuid` | uuid | Si | Identificador universal unico |
| `tenant_id` | entity_reference (group) | Si | Tenant afectado |
| `agent_id` | entity_reference (autonomous_agent) | Si | Agente que ejecuta |
| `trigger_type` | list_string | Si | scheduled, event, user_request, agent_chain |
| `trigger_data` | text_long | No | Datos del trigger (JSON) |
| `started_at` | datetime | Si | Inicio de ejecucion |
| `completed_at` | datetime | No | Fin de ejecucion |
| `status` | list_string | Si | running, completed, failed, paused, cancelled. Default: running |
| `actions_taken` | text_long | No | Lista de acciones ejecutadas (JSON array) |
| `decisions_made` | text_long | No | Decisiones con reasoning (JSON array) |
| `tokens_used` | integer | No | Tokens LLM consumidos |
| `cost_estimate` | decimal(8,4) | No | Coste estimado de la ejecucion (EUR) |
| `outcome` | text_long | No | Resultado final (JSON) |
| `human_feedback` | list_string | No | approved, rejected, corrected, none. Default: none |
| `error_message` | text_long | No | Mensaje de error (si status=failed) |
| `created` | created | Si | Timestamp creacion |

**Indices DB:** `tenant_id`, `agent_id`, `status`, `trigger_type`, `started_at`.

#### 7.2.3 Entidad `AgentApproval` (Aprobacion Humana Pendiente)

**Tipo:** ContentEntity
**ID:** `agent_approval`
**Base table:** `agent_approval`

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | Si | PK autoincremental |
| `uuid` | uuid | Si | Identificador universal unico |
| `tenant_id` | entity_reference (group) | Si | Tenant afectado |
| `execution_id` | entity_reference (agent_execution) | Si | Ejecucion que requiere aprobacion |
| `agent_id` | entity_reference (autonomous_agent) | Si | Agente que solicita |
| `action_description` | text_long | Si | Descripcion de la accion propuesta |
| `reasoning` | text_long | No | Razonamiento del agente para esta accion |
| `risk_assessment` | list_string | Si | low, medium, high. Default: medium |
| `status` | list_string | Si | pending, approved, rejected, expired. Default: pending |
| `reviewed_by` | entity_reference (user) | No | Usuario que aprobo/rechazo |
| `reviewed_at` | datetime | No | Fecha de revision |
| `expires_at` | datetime | No | Fecha de expiracion automatica |
| `created` | created | Si | Timestamp creacion |

**Indices DB:** `tenant_id`, `execution_id`, `status`, `expires_at`.

#### 7.2.4 Services

| Service ID | Clase | Metodos Clave | Descripcion |
|-----------|-------|---------------|-------------|
| `jaraba_agents.orchestrator` | `AgentOrchestratorService` | `execute()`, `pause()`, `resume()`, `cancel()`, `getStatus()` | Router y lifecycle manager de ejecuciones |
| `jaraba_agents.guardrails` | `GuardrailsEnforcerService` | `check()`, `enforce()`, `getLevel()`, `isActionAllowed()` | Verificacion de limites, whitelist de acciones, budget de tokens |
| `jaraba_agents.metrics` | `AgentMetricsCollectorService` | `record()`, `getStats()`, `getCostByTenant()`, `getPerformance()` | Metricas de rendimiento y costes |
| `jaraba_agents.approval_manager` | `ApprovalManagerService` | `requestApproval()`, `approve()`, `reject()`, `expireStale()` | Gestion del flujo de aprobacion humana |
| `jaraba_agents.enrollment_agent` | `EnrollmentAgentService` | `analyze()`, `enrollUser()`, `createLearningPath()` | Agente de auto-enrollment post-diagnostico |
| `jaraba_agents.planning_agent` | `PlanningAgentService` | `analyzeDiagnostic()`, `generatePlan()`, `assignTasks()` | Agente de planificacion de negocios |
| `jaraba_agents.support_agent` | `SupportAgentService` | `handleQuery()`, `searchKb()`, `escalate()` | Agente chatbot autonomo con KB RAG |

#### 7.2.5 Controllers y API Endpoints

```yaml
# Dashboard frontend (zero-region)
jaraba_agents.dashboard:
  path: '/agents'
  defaults:
    _controller: '\Drupal\jaraba_agents\Controller\AgentsDashboardController::dashboard'
    _title: 'Agentes Autonomos'
  requirements:
    _permission: 'access agents'

# API REST — Agentes (config)
jaraba_agents.api.agents.list:
  path: '/api/v1/agents'
  methods: [GET]
jaraba_agents.api.agents.show:
  path: '/api/v1/agents/{autonomous_agent}'
  methods: [GET]
jaraba_agents.api.agents.update_config:
  path: '/api/v1/agents/{autonomous_agent}/config'
  methods: [PATCH]

# API REST — Ejecuciones
jaraba_agents.api.execute:
  path: '/api/v1/agents/{autonomous_agent}/execute'
  methods: [POST]
jaraba_agents.api.executions.list:
  path: '/api/v1/agents/{autonomous_agent}/executions'
  methods: [GET]
jaraba_agents.api.executions.show:
  path: '/api/v1/agents/executions/{agent_execution}'
  methods: [GET]

# API REST — Aprobaciones
jaraba_agents.api.approvals.pending:
  path: '/api/v1/agents/approvals/pending'
  methods: [GET]
jaraba_agents.api.approvals.approve:
  path: '/api/v1/agents/approvals/{agent_approval}/approve'
  methods: [POST]
jaraba_agents.api.approvals.reject:
  path: '/api/v1/agents/approvals/{agent_approval}/reject'
  methods: [POST]

# API REST — Metricas
jaraba_agents.api.metrics:
  path: '/api/v1/agents/metrics'
  methods: [GET]
```

**Total: 11 endpoints** (1 dashboard + 10 API REST)

---

## 8. MACRO-FASE 3: Advanced — Predictive + Orchestration

### 8.1 FASE 3A: jaraba_predictive — Modelos Predictivos ML

**Justificacion:** Modelos de machine learning para anticipar churn (-15-25%), priorizar leads (+20% conversion), proyectar MRR/ARR, y detectar anomalias. Arquetipo B (hibrido Drupal+Python): la capa Drupal gestiona entidades, configuracion y dashboards; Python (scikit-learn) ejecuta el entrenamiento y prediccion via `PredictionBridge`. El approach es "heuristicas PHP primero, ML Python cuando haya volumen" (>1,000 tenants).

#### 8.1.1 Entidad `ChurnPrediction` (Prediccion de Churn)

**Tipo:** ContentEntity (append-only, ENTITY-APPEND-001)
**ID:** `churn_prediction`
**Base table:** `churn_prediction`

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | Si | PK autoincremental |
| `uuid` | uuid | Si | Identificador universal unico |
| `tenant_id` | entity_reference (group) | Si | Tenant evaluado |
| `risk_score` | integer | Si | Score de riesgo 0-100 |
| `risk_level` | list_string | Si | low (0-25), medium (26-50), high (51-75), critical (76-100) |
| `contributing_factors` | text_long | Si | Factores que contribuyen al riesgo (JSON array) |
| `recommended_actions` | text_long | No | Acciones sugeridas de retencion (JSON array) |
| `predicted_churn_date` | datetime | No | Fecha estimada de churn |
| `model_version` | string(20) | Si | Version del modelo (heuristic_v1 o ml_v1) |
| `accuracy_confidence` | decimal(3,2) | No | Confianza del modelo (0.00-1.00) |
| `features_snapshot` | text_long | No | Snapshot de features usadas (JSON, para reproducibilidad) |
| `calculated_at` | datetime | Si | Fecha del calculo |
| `created` | created | Si | Timestamp creacion |

**Indices DB:** `tenant_id`, `risk_level`, `risk_score`, `calculated_at`.

#### 8.1.2 Entidad `LeadScore` (Puntuacion de Lead)

**Tipo:** ContentEntity
**ID:** `lead_score`
**Base table:** `lead_score`

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | Si | PK autoincremental |
| `uuid` | uuid | Si | Identificador universal unico |
| `tenant_id` | entity_reference (group) | Si | Tenant del lead |
| `user_id` | entity_reference (user) | Si | Usuario lead evaluado |
| `total_score` | integer | Si | Score total 0-100 |
| `score_breakdown` | text_long | Si | Desglose por categoria (JSON: engagement, activation, intent) |
| `qualification` | list_string | Si | cold (0-25), warm (26-50), hot (51-75), sales_ready (76-100) |
| `last_activity` | datetime | No | Ultima actividad relevante del lead |
| `events_tracked` | text_long | No | Eventos contabilizados (JSON array) |
| `model_version` | string(20) | Si | Version del modelo |
| `calculated_at` | datetime | Si | Fecha del calculo |
| `created` | created | Si | Timestamp creacion |
| `changed` | changed | Si | Timestamp modificacion |

**Indices DB:** `tenant_id`, `user_id`, `qualification`, `total_score`, `calculated_at`.

#### 8.1.3 Entidad `Forecast` (Proyeccion MRR/ARR)

**Tipo:** ContentEntity (append-only)
**ID:** `forecast`
**Base table:** `forecast`

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | Si | PK autoincremental |
| `uuid` | uuid | Si | Identificador universal unico |
| `tenant_id` | entity_reference (group) | No | NULL = forecast global, non-NULL = por tenant |
| `forecast_type` | list_string | Si | mrr, arr, revenue, users. Default: mrr |
| `period` | list_string | Si | monthly, quarterly, yearly |
| `forecast_date` | datetime | Si | Fecha para la que se proyecta |
| `predicted_value` | decimal(12,2) | Si | Valor proyectado |
| `confidence_low` | decimal(12,2) | No | Intervalo de confianza bajo (percentil 10) |
| `confidence_high` | decimal(12,2) | No | Intervalo de confianza alto (percentil 90) |
| `actual_value` | decimal(12,2) | No | Valor real (llenado post-periodo) |
| `model_version` | string(20) | Si | Version del modelo |
| `calculated_at` | datetime | Si | Fecha del calculo |
| `created` | created | Si | Timestamp creacion |

**Indices DB:** `tenant_id`, `forecast_type`, `forecast_date`, `period`.

#### 8.1.4 Services

| Service ID | Clase | Metodos Clave | Descripcion |
|-----------|-------|---------------|-------------|
| `jaraba_predictive.churn_predictor` | `ChurnPredictorService` | `predict()`, `predictBatch()`, `getFeatures()`, `getThresholds()` | Calculo de churn risk score (heuristico + ML) |
| `jaraba_predictive.lead_scorer` | `LeadScorerService` | `score()`, `scoreBatch()`, `trackEvent()`, `getQualification()` | Scoring de leads en tiempo real |
| `jaraba_predictive.forecast_engine` | `ForecastEngineService` | `forecast()`, `getMrrProjection()`, `getArrProjection()`, `backtest()` | Proyecciones financieras MRR/ARR |
| `jaraba_predictive.anomaly_detector` | `AnomalyDetectorService` | `detect()`, `getBaseline()`, `getAnomalies()`, `sendAlert()` | Deteccion de anomalias en metricas |
| `jaraba_predictive.prediction_bridge` | `PredictionBridgeService` | `invokeModel()`, `trainModel()`, `getModelStatus()`, `isAvailable()` | PHP wrapper que invoca Python via proc_open() |
| `jaraba_predictive.feature_store` | `FeatureStoreService` | `getFeatures()`, `storeFeatures()`, `refreshAll()` | Feature store con Redis + MariaDB |
| `jaraba_predictive.retention_workflow` | `RetentionWorkflowService` | `trigger()`, `getActiveCampaigns()`, `trackOutcome()` | Workflow de retencion automatico |

**PredictionBridge — Patron PHP-Python:**

```php
/**
 * Puente PHP-Python para invocar modelos ML.
 *
 * Estructura: Invoca scripts Python via proc_open() con JSON stdin/stdout.
 * El modelo se entrena offline (cron semanal) y el script predict.py ejecuta
 * la inferencia en tiempo real. Fallback a heuristicas PHP si Python no
 * esta disponible o si hay menos de 1,000 registros de entrenamiento.
 */
public function invokeModel(string $model, array $features): array {
  $scriptPath = $this->getScriptPath($model);
  if (!file_exists($scriptPath)) {
    return $this->fallbackHeuristic($model, $features);
  }

  $input = json_encode($features, JSON_THROW_ON_ERROR);
  $process = proc_open(
    ['python3', $scriptPath, '--predict'],
    [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
    $pipes,
  );

  if (!is_resource($process)) {
    $this->logger->error('No se pudo iniciar proceso Python para modelo @model', ['@model' => $model]);
    return $this->fallbackHeuristic($model, $features);
  }

  fwrite($pipes[0], $input);
  fclose($pipes[0]);
  $output = stream_get_contents($pipes[1]);
  fclose($pipes[1]);

  $exitCode = proc_close($process);
  if ($exitCode !== 0) {
    return $this->fallbackHeuristic($model, $features);
  }

  return json_decode($output, TRUE, 512, JSON_THROW_ON_ERROR);
}
```

#### 8.1.5 Controllers y API Endpoints

```yaml
# Dashboard frontend (zero-region)
jaraba_predictive.dashboard:
  path: '/predictions'
  defaults:
    _controller: '\Drupal\jaraba_predictive\Controller\PredictiveDashboardController::dashboard'
    _title: 'Predicciones y Analytics'
  requirements:
    _permission: 'access predictions'

# API REST — Churn, Lead Scoring, Forecast, Anomalias, Stats
# 9 endpoints API REST (ver seccion 12 para inventario completo)
```

**Total: 10 endpoints** (1 dashboard + 9 API REST)

---

### 8.2 FASE 3B: jaraba_agents (extension) — Orquestacion Multi-Agent

**Justificacion:** Extiende `jaraba_agents` (Fase 2B) con Agent Router (LLM + reglas), protocolo de handoff entre agentes, y memoria compartida via Qdrant. Dependencia HARD con Fase 2B.

#### 8.2.1 Entidad `AgentConversation` (Conversacion Multi-Agent)

**Tipo:** ContentEntity
**ID:** `agent_conversation`
**Base table:** `agent_conversation`

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | Si | PK autoincremental |
| `uuid` | uuid | Si | Identificador universal unico |
| `tenant_id` | entity_reference (group) | Si | Tenant del usuario |
| `user_id` | entity_reference (user) | Si | Usuario que inicio |
| `current_agent_id` | entity_reference (autonomous_agent) | No | Agente actualmente activo |
| `agent_chain` | text_long | No | Secuencia de agentes que han participado (JSON array) |
| `shared_context` | text_long | No | Contexto acumulado entre agentes (JSON) |
| `handoff_count` | integer | No | Numero de handoffs realizados. Default: 0 |
| `status` | list_string | Si | active, completed, escalated, timeout. Default: active |
| `satisfaction_score` | integer | No | Puntuacion del usuario (1-5) |
| `total_tokens` | integer | No | Tokens totales consumidos |
| `started_at` | datetime | Si | Inicio de la conversacion |
| `completed_at` | datetime | No | Fin de la conversacion |
| `created` | created | Si | Timestamp creacion |

**Indices DB:** `tenant_id`, `user_id`, `status`, `current_agent_id`, `started_at`.

#### 8.2.2 Entidad `AgentHandoff` (Transferencia entre Agentes)

**Tipo:** ContentEntity (append-only)
**ID:** `agent_handoff`
**Base table:** `agent_handoff`

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | Si | PK autoincremental |
| `uuid` | uuid | Si | Identificador universal unico |
| `conversation_id` | entity_reference (agent_conversation) | Si | Conversacion parent |
| `from_agent_id` | entity_reference (autonomous_agent) | Si | Agente que transfiere |
| `to_agent_id` | entity_reference (autonomous_agent) | Si | Agente que recibe |
| `reason` | text_long | Si | Razon del handoff |
| `context_transferred` | text_long | No | Contexto transferido (JSON) |
| `confidence` | decimal(3,2) | No | Confianza en routing (0.00-1.00) |
| `handoff_at` | datetime | Si | Momento del handoff |
| `created` | created | Si | Timestamp creacion |

**Indices DB:** `conversation_id`, `from_agent_id`, `to_agent_id`, `handoff_at`.

#### 8.2.3 Services (Extension)

| Service ID | Clase | Metodos Clave | Descripcion |
|-----------|-------|---------------|-------------|
| `jaraba_agents.agent_router` | `AgentRouterService` | `route()`, `classify()`, `getConfidence()` | Clasificacion de intents via LLM + reglas |
| `jaraba_agents.handoff_manager` | `HandoffManagerService` | `handoff()`, `getChain()`, `resumeConversation()` | Protocolo de transferencia entre agentes |
| `jaraba_agents.shared_memory` | `SharedMemoryService` | `store()`, `retrieve()`, `search()`, `getContext()` | Memoria compartida via Qdrant collections |
| `jaraba_agents.conversation_manager` | `ConversationManagerService` | `start()`, `addMessage()`, `end()`, `rate()` | Lifecycle de conversaciones multi-agent |
| `jaraba_agents.observer` | `AgentObserverService` | `trace()`, `getMetrics()`, `getChainVisualization()` | Monitoring y debugging de cadenas |

**Total: 6 endpoints adicionales** (conversaciones + observabilidad, sumados a los 11 de Fase 2B = 17 total)

---

## 9. MACRO-FASE 4: Complex — Mobile + SDK

### 9.1 FASE 4A: jaraba_mobile — App Nativa con Capacitor

**Justificacion:** Extiende la PWA con push notifications nativas (critico para iOS), QR offline para AgroConecta, biometria, y deep linking. Sub-componente Drupal (`jaraba_mobile`) para backend + proyecto Capacitor separado.

#### 9.1.1 Entidad `MobileDevice` (Registro de Dispositivo)

**Tipo:** ContentEntity
**ID:** `mobile_device`
**Base table:** `mobile_device`

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | Si | PK autoincremental |
| `uuid` | uuid | Si | Identificador universal unico |
| `tenant_id` | entity_reference (group) | Si | Tenant del usuario |
| `user_id` | entity_reference (user) | Si | Usuario propietario |
| `device_token` | string(500) | Si | FCM/APNs token para push |
| `platform` | list_string | Si | ios, android |
| `os_version` | string(20) | No | Version del SO |
| `app_version` | string(20) | No | Version de la app |
| `device_model` | string(100) | No | Modelo del dispositivo |
| `biometric_enabled` | boolean | No | Biometria activada. Default: FALSE |
| `push_enabled` | boolean | Si | Push activado. Default: TRUE |
| `last_active` | datetime | No | Ultima actividad |
| `is_active` | boolean | Si | Dispositivo activo. Default: TRUE |
| `created` | created | Si | Timestamp creacion |
| `changed` | changed | Si | Timestamp modificacion |

**Indices DB:** `tenant_id`, `user_id`, `platform`, `device_token`, `is_active`.

#### 9.1.2 Entidad `PushNotification` (Notificacion Push)

**Tipo:** ContentEntity (append-only)
**ID:** `push_notification`
**Base table:** `push_notification`

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | Si | PK autoincremental |
| `uuid` | uuid | Si | Identificador universal unico |
| `tenant_id` | entity_reference (group) | Si | Tenant emisor |
| `recipient_id` | entity_reference (user) | Si | Destinatario |
| `title` | string(100) | Si | Titulo |
| `body` | string(255) | Si | Cuerpo |
| `data` | text_long | No | Payload custom (JSON) |
| `channel` | list_string | Si | general, jobs, orders, alerts, marketing |
| `priority` | list_string | Si | high, normal, low |
| `deep_link` | string(500) | No | URL de deep link |
| `sent_at` | datetime | No | Fecha envio |
| `delivered_at` | datetime | No | Entrega confirmada |
| `opened_at` | datetime | No | Fecha apertura |
| `status` | list_string | Si | queued, sent, delivered, opened, failed. Default: queued |
| `created` | created | Si | Timestamp creacion |

**Indices DB:** `tenant_id`, `recipient_id`, `channel`, `status`, `sent_at`.

#### 9.1.3 Services y Endpoints

| Service ID | Clase | Descripcion |
|-----------|-------|-------------|
| `jaraba_mobile.device_registry` | `DeviceRegistryService` | Registro y gestion de dispositivos |
| `jaraba_mobile.push_sender` | `PushSenderService` | Envio de push via Firebase Cloud Messaging |
| `jaraba_mobile.deep_link_resolver` | `DeepLinkResolverService` | Resolucion de deep links |

**Total: 9 endpoints** (8 API REST + 1 settings)

---

### 9.2 FASE 4B: jaraba_connector_sdk — SDK de Conectores y Marketplace

**Justificacion:** SDK para terceros, certificacion automatica, marketplace con revenue share. Efecto red.

#### 9.2.1 Entidad `Connector` (Conector del Marketplace)

**Tipo:** ContentEntity
**ID:** `connector`
**Base table:** `connector`

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | Si | PK autoincremental |
| `uuid` | uuid | Si | Identificador universal unico |
| `developer_id` | entity_reference (user) | Si | Desarrollador creador |
| `name` | string(100) | Si | Nombre del conector |
| `slug` | string(100) | Si | URL-friendly identifier (unico) |
| `description` | text_long | Si | Descripcion completa |
| `version` | string(20) | Si | Version actual (semver) |
| `api_version` | string(10) | Si | Version API compatible |
| `category` | list_string | Si | crm, erp, payment, communication, analytics, custom |
| `icon_file_id` | entity_reference (file) | No | Icono del conector |
| `config_schema` | text_long | No | Schema de configuracion (JSON Schema) |
| `certification_status` | list_string | Si | draft, testing, certified, suspended. Default: draft |
| `installs_count` | integer | No | Instalaciones. Default: 0 |
| `rating` | decimal(3,2) | No | Puntuacion media (1.00-5.00) |
| `pricing_model` | list_string | Si | free, one_time, monthly, usage_based. Default: free |
| `price` | decimal(8,2) | No | Precio |
| `revenue_share_pct` | integer | No | Porcentaje developer (default 70) |
| `is_active` | boolean | Si | Activo en marketplace. Default: FALSE |
| `created` | created | Si | Timestamp creacion |
| `changed` | changed | Si | Timestamp modificacion |

**Indices DB:** `developer_id`, `slug` (unique), `category`, `certification_status`, `is_active`, `rating`.

#### 9.2.2 Entidad `ConnectorInstall` (Instalacion por Tenant)

**Tipo:** ContentEntity
**ID:** `connector_install`
**Base table:** `connector_install`

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | Si | PK autoincremental |
| `uuid` | uuid | Si | Identificador universal unico |
| `tenant_id` | entity_reference (group) | Si | Tenant instalador |
| `connector_id` | entity_reference (connector) | Si | Conector instalado |
| `installed_by` | entity_reference (user) | Si | Usuario |
| `config` | text_long | No | Configuracion del tenant (JSON) |
| `status` | list_string | Si | installed, active, paused, uninstalled. Default: installed |
| `installed_at` | datetime | Si | Fecha instalacion |
| `last_sync` | datetime | No | Ultima sincronizacion |
| `error_count` | integer | No | Errores consecutivos. Default: 0 |
| `created` | created | Si | Timestamp creacion |
| `changed` | changed | Si | Timestamp modificacion |

**Indices DB:** `tenant_id`, `connector_id`, `status`, `installed_at`.

#### 9.2.3 Services y ConnectorInterface

| Service ID | Clase | Descripcion |
|-----------|-------|-------------|
| `jaraba_connector_sdk.registry` | `ConnectorRegistryService` | Registro y busqueda de conectores |
| `jaraba_connector_sdk.installer` | `ConnectorInstallerService` | Gestion de instalaciones por tenant |
| `jaraba_connector_sdk.certifier` | `ConnectorCertifierService` | Pipeline de certificacion automatica |
| `jaraba_connector_sdk.marketplace` | `MarketplaceService` | Frontend del marketplace |
| `jaraba_connector_sdk.revenue_share` | `RevenueShareService` | Revenue share via Stripe Connect |

**`ConnectorInterface`** define el contrato: `install()`, `uninstall()`, `configure()`, `sync()`, `handleWebhook()`, `getStatus()`, `test()`.

**Total: 11 endpoints** (1 marketplace + 9 API REST + 1 settings)

---

## 10. Inventario Consolidado de Entidades

| # | Entity ID | Modulo | Fase | Campos | Append-Only | Field UI |
|---|-----------|--------|------|--------|-------------|----------|
| 1 | `funding_opportunity` | jaraba_funding | 1A | 16 | No | Si |
| 2 | `funding_application` | jaraba_funding | 1A | 17 | No | Si |
| 3 | `technical_report` | jaraba_funding | 1A | 14 | No | Si |
| 4 | `tenant_region` | jaraba_multiregion | 1B | 15 | No | Si |
| 5 | `tax_rule` | jaraba_multiregion | 1B | 14 | No | Si |
| 6 | `currency_rate` | jaraba_multiregion | 1B | 8 | Si | No |
| 7 | `vies_validation` | jaraba_multiregion | 1B | 11 | Si | No |
| 8 | `institutional_program` | jaraba_institutional | 2A | 18 | No | Si |
| 9 | `program_participant` | jaraba_institutional | 2A | 17 | No | Si |
| 10 | `sto_ficha` | jaraba_institutional | 2A | 15 | Si | Si |
| 11 | `autonomous_agent` | jaraba_agents | 2B | 19 | No | Si |
| 12 | `agent_execution` | jaraba_agents | 2B | 17 | Si | No |
| 13 | `agent_approval` | jaraba_agents | 2B | 13 | No | No |
| 14 | `churn_prediction` | jaraba_predictive | 3A | 13 | Si | No |
| 15 | `lead_score` | jaraba_predictive | 3A | 13 | No | Si |
| 16 | `forecast` | jaraba_predictive | 3A | 13 | Si | No |
| 17 | `agent_conversation` | jaraba_agents | 3B | 14 | No | No |
| 18 | `agent_handoff` | jaraba_agents | 3B | 10 | Si | No |
| 19 | `mobile_device` | jaraba_mobile | 4A | 15 | No | Si |
| 20 | `push_notification` | jaraba_mobile | 4A | 16 | Si | No |
| 21 | `connector` | jaraba_connector_sdk | 4B | 21 | No | Si |
| 22 | `connector_install` | jaraba_connector_sdk | 4B | 12 | No | Si |

**Total: 22 Content Entities** (8 append-only, 14 con CRUD completo, 15 con Field UI)

---

## 11. Inventario Consolidado de Services

| # | Service ID | Modulo | Metodos Principales |
|---|-----------|--------|---------------------|
| 1 | `jaraba_funding.opportunity_tracker` | jaraba_funding | `checkDeadlines()`, `getActiveOpportunities()`, `sendDeadlineAlerts()` |
| 2 | `jaraba_funding.application_manager` | jaraba_funding | `createApplication()`, `submitApplication()`, `updateStatus()` |
| 3 | `jaraba_funding.report_generator` | jaraba_funding | `generateReport()`, `generateWithAi()`, `exportToPdf()` |
| 4 | `jaraba_funding.budget_analyzer` | jaraba_funding | `calculateBudget()`, `getBreakdown()`, `validateEligibility()` |
| 5 | `jaraba_funding.impact_calculator` | jaraba_funding | `calculateIndicators()`, `getBaselineMetrics()` |
| 6 | `jaraba_multiregion.region_manager` | jaraba_multiregion | `getRegion()`, `setRegion()`, `getAvailableRegions()` |
| 7 | `jaraba_multiregion.tax_calculator` | jaraba_multiregion | `calculate()`, `getTaxRule()`, `isReverseCharge()` |
| 8 | `jaraba_multiregion.vies_validator` | jaraba_multiregion | `validate()`, `getLastValidation()`, `isExpired()` |
| 9 | `jaraba_multiregion.currency_converter` | jaraba_multiregion | `convert()`, `fetchRates()`, `getRate()` |
| 10 | `jaraba_multiregion.regional_compliance` | jaraba_multiregion | `checkCompliance()`, `getRequirements()` |
| 11 | `jaraba_institutional.program_manager` | jaraba_institutional | `createProgram()`, `getActivePrograms()`, `updateStatus()` |
| 12 | `jaraba_institutional.participant_tracker` | jaraba_institutional | `enroll()`, `updateOutcome()`, `calculateIndicators()` |
| 13 | `jaraba_institutional.sto_generator` | jaraba_institutional | `generate()`, `generateWithAi()`, `signWithPades()` |
| 14 | `jaraba_institutional.fundae_reporter` | jaraba_institutional | `generateReport()`, `getIndicators()`, `exportToExcel()` |
| 15 | `jaraba_institutional.fse_reporter` | jaraba_institutional | `calculateImpact()`, `generateFseReport()` |
| 16 | `jaraba_agents.orchestrator` | jaraba_agents | `execute()`, `pause()`, `resume()`, `cancel()` |
| 17 | `jaraba_agents.guardrails` | jaraba_agents | `check()`, `enforce()`, `getLevel()`, `isActionAllowed()` |
| 18 | `jaraba_agents.metrics` | jaraba_agents | `record()`, `getStats()`, `getCostByTenant()` |
| 19 | `jaraba_agents.approval_manager` | jaraba_agents | `requestApproval()`, `approve()`, `reject()` |
| 20 | `jaraba_agents.enrollment_agent` | jaraba_agents | `analyze()`, `enrollUser()`, `createLearningPath()` |
| 21 | `jaraba_agents.planning_agent` | jaraba_agents | `analyzeDiagnostic()`, `generatePlan()`, `assignTasks()` |
| 22 | `jaraba_agents.support_agent` | jaraba_agents | `handleQuery()`, `searchKb()`, `escalate()` |
| 23 | `jaraba_agents.agent_router` | jaraba_agents | `route()`, `classify()`, `getConfidence()` |
| 24 | `jaraba_agents.handoff_manager` | jaraba_agents | `handoff()`, `getChain()`, `resumeConversation()` |
| 25 | `jaraba_agents.shared_memory` | jaraba_agents | `store()`, `retrieve()`, `search()` |
| 26 | `jaraba_agents.conversation_manager` | jaraba_agents | `start()`, `addMessage()`, `end()`, `rate()` |
| 27 | `jaraba_agents.observer` | jaraba_agents | `trace()`, `getMetrics()`, `getChainVisualization()` |
| 28 | `jaraba_predictive.churn_predictor` | jaraba_predictive | `predict()`, `predictBatch()`, `getFeatures()` |
| 29 | `jaraba_predictive.lead_scorer` | jaraba_predictive | `score()`, `scoreBatch()`, `trackEvent()` |
| 30 | `jaraba_predictive.forecast_engine` | jaraba_predictive | `forecast()`, `getMrrProjection()`, `backtest()` |
| 31 | `jaraba_predictive.anomaly_detector` | jaraba_predictive | `detect()`, `getBaseline()`, `sendAlert()` |
| 32 | `jaraba_predictive.prediction_bridge` | jaraba_predictive | `invokeModel()`, `trainModel()`, `isAvailable()` |
| 33 | `jaraba_predictive.feature_store` | jaraba_predictive | `getFeatures()`, `storeFeatures()`, `refreshAll()` |
| 34 | `jaraba_predictive.retention_workflow` | jaraba_predictive | `trigger()`, `getActiveCampaigns()`, `trackOutcome()` |
| 35 | `jaraba_mobile.device_registry` | jaraba_mobile | `register()`, `unregister()`, `getDevices()` |
| 36 | `jaraba_mobile.push_sender` | jaraba_mobile | `send()`, `sendBatch()`, `sendToChannel()` |
| 37 | `jaraba_mobile.deep_link_resolver` | jaraba_mobile | `resolve()`, `generateLink()`, `getUniversalLink()` |
| 38 | `jaraba_connector_sdk.registry` | jaraba_connector_sdk | `register()`, `getConnector()`, `listCertified()` |
| 39 | `jaraba_connector_sdk.installer` | jaraba_connector_sdk | `install()`, `uninstall()`, `configure()` |
| 40 | `jaraba_connector_sdk.certifier` | jaraba_connector_sdk | `runTests()`, `certify()`, `suspend()` |
| 41 | `jaraba_connector_sdk.marketplace` | jaraba_connector_sdk | `list()`, `search()`, `getDetail()`, `rate()` |
| 42 | `jaraba_connector_sdk.revenue_share` | jaraba_connector_sdk | `calculateShare()`, `processPayout()`, `getEarnings()` |

**Total: 42 Services** distribuidos en 7 modulos

---

## 12. Inventario Consolidado de Endpoints REST API

| Modulo | Endpoints | Descripcion |
|--------|-----------|-------------|
| `jaraba_funding` | 13 | 1 dashboard + 10 API (opportunities CRUD, applications CRUD+submit, reports, stats) + 1 AI report + 1 settings |
| `jaraba_multiregion` | 11 | 1 settings + 10 API (region, tax calculate/rules, VIES validate/history, currency convert/rates, compliance) |
| `jaraba_institutional` | 11 | 1 dashboard + 10 API (programs CRUD, participants CRUD+enroll, fichas STO, reporting FUNDAE/FSE) |
| `jaraba_agents` | 17 | 1 dashboard + 10 API base (agents, executions, approvals, metrics) + 6 orchestration (conversations, observer) |
| `jaraba_predictive` | 10 | 1 dashboard + 9 API (churn predict/history/batch, lead score/batch/event, forecast MRR, anomalies, stats) |
| `jaraba_mobile` | 9 | 8 API (devices register/unregister/token/list, push send/batch/history, deeplink resolve) + 1 settings |
| `jaraba_connector_sdk` | 11 | 1 marketplace + 9 API (connectors list/show/rate, install/uninstall/configure/status, developer submit/certify) + 1 settings |

**Total: 82 endpoints** (71 API REST + 5 dashboards/marketplace + 4 settings + 2 AI generation)

---

## 13. Paleta de Colores y Design Tokens

Los modulos N2 usan la paleta Jaraba existente (7 colores) consumiendo CSS Custom Properties con fallbacks inline. No se definen nuevas variables SCSS en modulos satelite.

| Contexto | Token CSS | Valor | Uso en N2 |
|----------|-----------|-------|-----------|
| Corporativo | `--ej-color-corporate` | `#233D63` | Headers, textos principales, agentes tipo platform |
| Impulso | `--ej-color-impulse` | `#FF8C42` | CTAs, acciones de agentes, alertas de deadline |
| Innovacion | `--ej-color-innovation` | `#00A9A5` | Badges de agentes IA, predicciones positivas, conectores |
| Tierra | `--ej-color-earth` | `#556B2F` | Programas institucionales (agro, sostenibilidad) |
| Exito | `--ej-color-success` | `#10B981` | Churn bajo, lead scoring alto, certificacion aprobada |
| Advertencia | `--ej-color-warning` | `#F59E0B` | Churn medio, alertas de plazos, aprobaciones pendientes |
| Peligro | `--ej-color-danger` | `#EF4444` | Churn critico, errores de agente, conectores suspendidos |

**Tokens adicionales usados:**

| Token CSS | Valor | Uso |
|-----------|-------|-----|
| `--ej-bg-surface` | `#FFFFFF` | Fondo de cards y paneles |
| `--ej-border-radius-lg` | `14px` | Bordes redondeados premium |
| `--ej-spacing-lg` | `1.5rem` | Padding interior de secciones |
| `--ej-font-heading` | `'Outfit', sans-serif` | Tipografia de headings |

**Ejemplo SCSS N2 (BEM + color-mix + var):**

```scss
// _predictions-dashboard.scss
.predictions-dashboard {
  background: var(--ej-bg-surface, #FFFFFF);
  padding: var(--ej-spacing-lg, 1.5rem);

  &__risk-card {
    border-radius: var(--ej-border-radius-lg, 14px);

    &--critical {
      background: color-mix(in srgb, var(--ej-color-danger, #EF4444) 10%, transparent);
      border-left: 4px solid var(--ej-color-danger, #EF4444);
    }

    &--low {
      background: color-mix(in srgb, var(--ej-color-success, #10B981) 10%, transparent);
      border-left: 4px solid var(--ej-color-success, #10B981);
    }
  }
}
```

---

## 14. Patron de Iconos SVG

Iconos SVG en dos versiones (normal + duotone) siguiendo el sistema centralizado `jaraba_icon()`. Categoria y nombre siguiendo las convenciones existentes.

| Modulo | Categoria | Icono | Proposito |
|--------|-----------|-------|-----------|
| jaraba_agents | `ai/` | `agent-autonomous` | Agente IA autonomo |
| jaraba_agents | `ai/` | `agent-autonomous-duotone` | Variante duotone |
| jaraba_agents | `ai/` | `guardrail` | Sistema de guardrails |
| jaraba_agents | `ai/` | `guardrail-duotone` | Variante duotone |
| jaraba_agents | `ai/` | `execution-chain` | Cadena de ejecucion |
| jaraba_agents | `ai/` | `execution-chain-duotone` | Variante duotone |
| jaraba_predictive | `analytics/` | `prediction` | Prediccion generica |
| jaraba_predictive | `analytics/` | `prediction-duotone` | Variante duotone |
| jaraba_predictive | `analytics/` | `churn-risk` | Riesgo de abandono |
| jaraba_predictive | `analytics/` | `churn-risk-duotone` | Variante duotone |
| jaraba_predictive | `analytics/` | `lead-score` | Puntuacion de lead |
| jaraba_predictive | `analytics/` | `lead-score-duotone` | Variante duotone |
| jaraba_multiregion | `business/` | `globe-multi` | Multi-region |
| jaraba_multiregion | `business/` | `globe-multi-duotone` | Variante duotone |
| jaraba_multiregion | `business/` | `currency-exchange` | Cambio de moneda |
| jaraba_multiregion | `business/` | `currency-exchange-duotone` | Variante duotone |
| jaraba_institutional | `business/` | `institution-program` | Programa institucional |
| jaraba_institutional | `business/` | `institution-program-duotone` | Variante duotone |
| jaraba_institutional | `business/` | `sto-ficha` | Ficha tecnica STO |
| jaraba_institutional | `business/` | `sto-ficha-duotone` | Variante duotone |
| jaraba_funding | `business/` | `funding-opportunity` | Convocatoria de fondos |
| jaraba_funding | `business/` | `funding-opportunity-duotone` | Variante duotone |
| jaraba_funding | `business/` | `grant-application` | Solicitud de subvencion |
| jaraba_funding | `business/` | `grant-application-duotone` | Variante duotone |
| jaraba_connector_sdk | `ui/` | `connector-plug` | Conector del marketplace |
| jaraba_connector_sdk | `ui/` | `connector-plug-duotone` | Variante duotone |
| jaraba_connector_sdk | `ui/` | `marketplace` | Marketplace |
| jaraba_connector_sdk | `ui/` | `marketplace-duotone` | Variante duotone |
| jaraba_mobile | `ui/` | `mobile-device` | Dispositivo movil |
| jaraba_mobile | `ui/` | `mobile-device-duotone` | Variante duotone |
| jaraba_mobile | `ui/` | `push-notification` | Notificacion push |
| jaraba_mobile | `ui/` | `push-notification-duotone` | Variante duotone |

**Total: 32 iconos SVG nuevos** (16 normales + 16 duotone)

---

## 15. Orden Global de Implementacion

```
┌──────────┬──────────┬───────────┬──────────────────────┬──────────────────┬──────────┐
│ MACRO    │ SUB      │ MES       │ MODULO               │ DEPENDENCIAS     │ PRIORID. │
├──────────┼──────────┼───────────┼──────────────────────┼──────────────────┼──────────┤
│ FASE 1   │ 1A       │ Mes 1     │ jaraba_funding       │ Ninguna          │ ALTA     │
│ FASE 1   │ 1B       │ Mes 1     │ jaraba_multiregion   │ jaraba_billing   │ ALTA     │
│ FASE 2   │ 2A       │ Mes 2-3   │ jaraba_institutional │ doc 45, doc 89   │ ALTA     │
│ FASE 2   │ 2B       │ Mes 2-3   │ jaraba_agents (base) │ jaraba_ai_agents │ CRITICA  │
│ FASE 3   │ 3A       │ Mes 4-5   │ jaraba_predictive    │ jaraba_foc (soft)│ MEDIA    │
│ FASE 3   │ 3B       │ Mes 4-5   │ jaraba_agents (orch) │ Fase 2B (HARD)   │ MEDIA    │
│ FASE 4   │ 4A       │ Mes 6-7   │ jaraba_mobile        │ APIs verticales  │ MEDIA    │
│ FASE 4   │ 4B       │ Mes 7-8   │ jaraba_connector_sdk │ doc 112, billing │ BAJA     │
└──────────┴──────────┴───────────┴──────────────────────┴──────────────────┴──────────┘
```

**Nota sobre paralelismo:**
- Fases 1A y 1B son totalmente independientes — pueden desarrollarse en paralelo
- Fases 2A y 2B son totalmente independientes — pueden desarrollarse en paralelo
- Fases 3A y 3B son independientes entre si, pero 3B depende HARD de 2B
- Fases 4A y 4B son totalmente independientes — pueden desarrollarse en paralelo

**Con 2 desarrolladores en paralelo, el timeline se reduce de ~8 meses a ~4 meses.**

---

## 16. Estimacion de Esfuerzo

| Sub-Fase | Modulo | Entidades | Services | Endpoints | Horas (min) | Horas (max) |
|----------|--------|-----------|----------|-----------|-------------|-------------|
| 1A | jaraba_funding | 3 | 5 | 13 | 40 | 51 |
| 1B | jaraba_multiregion | 4 | 5 | 11 | 54 | 68 |
| 2A | jaraba_institutional | 3 | 5 | 11 | 53 | 66 |
| 2B | jaraba_agents (base) | 3 | 7 | 11 | 60 | 77 |
| 3A | jaraba_predictive | 3 | 7 | 10 | 59 | 75 |
| 3B | jaraba_agents (orch) | 2 | 5 | 6 | 58 | 72 |
| 4A | jaraba_mobile | 2 | 3 | 9 | 82 | 104 |
| 4B | jaraba_connector_sdk | 2 | 5 | 11 | 65 | 100 |
| **TOTAL** | **7 modulos** | **22** | **42** | **82** | **471** | **613** |

**Inversion estimada:** 471-613 horas x EUR 45/hora = **EUR 21,195-27,585**
**Timeline con 1 desarrollador senior:** ~8 meses
**Timeline con 2 desarrolladores en paralelo:** ~4 meses (macro-fases 1-4 con paralelismo interno)

**Comparativa con otros niveles:**

| Nivel | Horas | Coste EUR | Modulos | Entidades |
|-------|-------|-----------|---------|-----------|
| N1 Foundation | 91-118 | 4,095-5,310 | 3 | 14 |
| **N2 Growth Ready** | **471-613** | **21,195-27,585** | **7** | **22** |
| N3 Enterprise (est.) | 800-1,100 | 36,000-49,500 | 7 | ~30 |

**Nota:** La estimacion de N2 es ~5x la de N1, confirmando la proyeccion del doc 202 (Auditoria Readiness). La mayor complejidad viene de los arquetipos no-Drupal (Python ML, Capacitor, SDK) que requieren stacks adicionales.

---

## 17. Registro de Cambios

### v1.0.0 (2026-02-17)
- Creacion del plan de implementacion N2 Growth Ready Platform
- 8 especificaciones tecnicas cubiertas (docs 186-193)
- 4 macro-fases, 8 sub-fases, 22 Content Entities, 42 Services, 82 endpoints
- 17 directrices del proyecto verificadas y documentadas con ejemplos
- 4 arquetipos tecnologicos definidos (Drupal Puro, Hibrido Python, Mobile, SDK)
- Inventarios consolidados de entidades, servicios, y endpoints
- Paleta de colores y design tokens documentados
- 32 iconos SVG nuevos planificados (16 normales + 16 duotone)
- Estimacion total: 471-613 horas / EUR 21,195-27,585
- Orden de implementacion con dependencias y paralelismo

---

*Fin del documento.*

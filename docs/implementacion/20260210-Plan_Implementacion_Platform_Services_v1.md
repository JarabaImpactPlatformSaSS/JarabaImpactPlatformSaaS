# Plan de Implementación Platform Services (Docs 108-117) v1.0

> **Tipo:** Plan de Implementación
> **Versión:** 1.0.0
> **Fecha:** 2026-02-10
> **Última actualización:** 2026-02-10
> **Autor:** Claude Opus 4.6 / Equipo Técnico
> **Estado:** Planificación inicial
> **Alcance:** 10 especificaciones técnicas transversales de plataforma (docs 108-117)
> **Módulos principales:** `jaraba_agent_flows`, `jaraba_pwa`, `jaraba_onboarding`, `jaraba_usage_billing`, `jaraba_integrations`, `jaraba_customer_success`, `jaraba_knowledge_base`, `jaraba_security_compliance`, `jaraba_analytics_bi`, `jaraba_whitelabel`

---

## 📑 Tabla de Contenidos (TOC)

- [1. Resumen Ejecutivo](#1-resumen-ejecutivo)
  - [1.1 Visión y Posicionamiento](#11-visión-y-posicionamiento)
  - [1.2 Relación con la infraestructura existente](#12-relación-con-la-infraestructura-existente)
  - [1.3 Patrón arquitectónico de referencia](#13-patrón-arquitectónico-de-referencia)
  - [1.4 Esfuerzo estimado total](#14-esfuerzo-estimado-total)
- [2. Tabla de Correspondencia con Especificaciones Técnicas](#2-tabla-de-correspondencia-con-especificaciones-técnicas)
- [3. Cumplimiento de Directrices del Proyecto](#3-cumplimiento-de-directrices-del-proyecto)
  - [3.1 Directriz: i18n — Textos siempre traducibles](#31-directriz-i18n--textos-siempre-traducibles)
  - [3.2 Directriz: Modelo SCSS con Federated Design Tokens](#32-directriz-modelo-scss-con-federated-design-tokens)
  - [3.3 Directriz: Dart Sass moderno](#33-directriz-dart-sass-moderno)
  - [3.4 Directriz: Frontend limpio sin regiones Drupal](#34-directriz-frontend-limpio-sin-regiones-drupal)
  - [3.5 Directriz: Body classes via hook_preprocess_html()](#35-directriz-body-classes-via-hook_preprocess_html)
  - [3.6 Directriz: CRUD en modales slide-panel](#36-directriz-crud-en-modales-slide-panel)
  - [3.7 Directriz: Entidades con Field UI y Views](#37-directriz-entidades-con-field-ui-y-views)
  - [3.8 Directriz: No hardcodear configuración](#38-directriz-no-hardcodear-configuración)
  - [3.9 Directriz: Parciales Twig reutilizables](#39-directriz-parciales-twig-reutilizables)
  - [3.10 Directriz: Seguridad](#310-directriz-seguridad)
  - [3.11 Directriz: Comentarios de código](#311-directriz-comentarios-de-código)
  - [3.12 Directriz: Iconos SVG duotone](#312-directriz-iconos-svg-duotone)
  - [3.13 Directriz: AI via abstracción @ai.provider](#313-directriz-ai-via-abstracción-aiprovider)
  - [3.14 Directriz: Automaciones via hooks Drupal](#314-directriz-automaciones-via-hooks-drupal)
- [4. Arquitectura General de Módulos](#4-arquitectura-general-de-módulos)
  - [4.1 Mapa de módulos y dependencias](#41-mapa-de-módulos-y-dependencias)
  - [4.2 Estructura de directorios estándar](#42-estructura-de-directorios-estándar)
  - [4.3 Compilación SCSS](#43-compilación-scss)
- [5. Estado por Fases](#5-estado-por-fases)
- [6. FASE 1: AI Agent Flows — Workflows Inteligentes (Doc 108)](#6-fase-1-ai-agent-flows--workflows-inteligentes-doc-108)
  - [6.1 Justificación](#61-justificación)
  - [6.2 Entidades](#62-entidades)
  - [6.3 Services](#63-services)
  - [6.4 Controllers](#64-controllers)
  - [6.5 Templates y Parciales Twig](#65-templates-y-parciales-twig)
  - [6.6 Frontend Assets](#66-frontend-assets)
  - [6.7 Hooks](#67-hooks)
  - [6.8 Archivos a Crear](#68-archivos-a-crear)
  - [6.9 Archivos a Modificar](#69-archivos-a-modificar)
  - [6.10 SCSS: Directrices](#610-scss-directrices)
  - [6.11 Verificación](#611-verificación)
- [7. FASE 2: PWA Mobile — Funcionalidad Offline (Doc 109)](#7-fase-2-pwa-mobile--funcionalidad-offline-doc-109)
  - [7.1 Justificación](#71-justificación)
  - [7.2 Entidades](#72-entidades)
  - [7.3 Services](#73-services)
  - [7.4 Controllers](#74-controllers)
  - [7.5 Templates y Parciales Twig](#75-templates-y-parciales-twig)
  - [7.6 Frontend Assets](#76-frontend-assets)
  - [7.7 Hooks](#77-hooks)
  - [7.8 Archivos a Crear](#78-archivos-a-crear)
  - [7.9 SCSS: Directrices](#79-scss-directrices)
  - [7.10 Verificación](#710-verificación)
- [8. FASE 3: Onboarding Product-Led — Gamificación (Doc 110)](#8-fase-3-onboarding-product-led--gamificación-doc-110)
  - [8.1 Justificación](#81-justificación)
  - [8.2 Entidades](#82-entidades)
  - [8.3 Services](#83-services)
  - [8.4 Controllers](#84-controllers)
  - [8.5 Templates y Parciales Twig](#85-templates-y-parciales-twig)
  - [8.6 Frontend Assets](#86-frontend-assets)
  - [8.7 Hooks](#87-hooks)
  - [8.8 Archivos a Crear](#88-archivos-a-crear)
  - [8.9 SCSS: Directrices](#89-scss-directrices)
  - [8.10 Verificación](#810-verificación)
- [9. FASE 4: Usage-Based Pricing — Precios por Uso (Doc 111)](#9-fase-4-usage-based-pricing--precios-por-uso-doc-111)
  - [9.1 Justificación](#91-justificación)
  - [9.2 Entidades](#92-entidades)
  - [9.3 Services](#93-services)
  - [9.4 Controllers](#94-controllers)
  - [9.5 Templates y Parciales Twig](#95-templates-y-parciales-twig)
  - [9.6 Frontend Assets](#96-frontend-assets)
  - [9.7 Hooks](#97-hooks)
  - [9.8 Archivos a Crear](#98-archivos-a-crear)
  - [9.9 SCSS: Directrices](#99-scss-directrices)
  - [9.10 Verificación](#910-verificación)
- [10. FASE 5: Integration Marketplace & Developer Portal (Doc 112)](#10-fase-5-integration-marketplace--developer-portal-doc-112)
  - [10.1 Justificación](#101-justificación)
  - [10.2 Entidades](#102-entidades)
  - [10.3 Services](#103-services)
  - [10.4 Controllers](#104-controllers)
  - [10.5 Templates y Parciales Twig](#105-templates-y-parciales-twig)
  - [10.6 Frontend Assets](#106-frontend-assets)
  - [10.7 Hooks](#107-hooks)
  - [10.8 Archivos a Crear](#108-archivos-a-crear)
  - [10.9 SCSS: Directrices](#109-scss-directrices)
  - [10.10 Verificación](#1010-verificación)
- [11. FASE 6: Customer Success Proactivo (Doc 113)](#11-fase-6-customer-success-proactivo-doc-113)
  - [11.1 Justificación](#111-justificación)
  - [11.2 Entidades](#112-entidades)
  - [11.3 Services](#113-services)
  - [11.4 Controllers](#114-controllers)
  - [11.5 Templates y Parciales Twig](#115-templates-y-parciales-twig)
  - [11.6 Frontend Assets](#116-frontend-assets)
  - [11.7 Hooks](#117-hooks)
  - [11.8 Archivos a Crear](#118-archivos-a-crear)
  - [11.9 SCSS: Directrices](#119-scss-directrices)
  - [11.10 Verificación](#1110-verificación)
- [12. FASE 7: Knowledge Base & Self-Service (Doc 114)](#12-fase-7-knowledge-base--self-service-doc-114)
  - [12.1 Justificación](#121-justificación)
  - [12.2 Entidades](#122-entidades)
  - [12.3 Services](#123-services)
  - [12.4 Controllers](#124-controllers)
  - [12.5 Templates y Parciales Twig](#125-templates-y-parciales-twig)
  - [12.6 Frontend Assets](#126-frontend-assets)
  - [12.7 Hooks](#127-hooks)
  - [12.8 Archivos a Crear](#128-archivos-a-crear)
  - [12.9 SCSS: Directrices](#129-scss-directrices)
  - [12.10 Verificación](#1210-verificación)
- [13. FASE 8: Security & Compliance (Doc 115)](#13-fase-8-security--compliance-doc-115)
  - [13.1 Justificación](#131-justificación)
  - [13.2 Entidades](#132-entidades)
  - [13.3 Services](#133-services)
  - [13.4 Controllers](#134-controllers)
  - [13.5 Templates y Parciales Twig](#135-templates-y-parciales-twig)
  - [13.6 Frontend Assets](#136-frontend-assets)
  - [13.7 Hooks](#137-hooks)
  - [13.8 Archivos a Crear](#138-archivos-a-crear)
  - [13.9 SCSS: Directrices](#139-scss-directrices)
  - [13.10 Verificación](#1310-verificación)
- [14. FASE 9: Advanced Analytics & BI (Doc 116)](#14-fase-9-advanced-analytics--bi-doc-116)
  - [14.1 Justificación](#141-justificación)
  - [14.2 Entidades](#142-entidades)
  - [14.3 Services](#143-services)
  - [14.4 Controllers](#144-controllers)
  - [14.5 Templates y Parciales Twig](#145-templates-y-parciales-twig)
  - [14.6 Frontend Assets](#146-frontend-assets)
  - [14.7 Hooks](#147-hooks)
  - [14.8 Archivos a Crear](#148-archivos-a-crear)
  - [14.9 SCSS: Directrices](#149-scss-directrices)
  - [14.10 Verificación](#1410-verificación)
- [15. FASE 10: White-Label & Reseller Platform (Doc 117)](#15-fase-10-white-label--reseller-platform-doc-117)
  - [15.1 Justificación](#151-justificación)
  - [15.2 Entidades](#152-entidades)
  - [15.3 Services](#153-services)
  - [15.4 Controllers](#154-controllers)
  - [15.5 Templates y Parciales Twig](#155-templates-y-parciales-twig)
  - [15.6 Frontend Assets](#156-frontend-assets)
  - [15.7 Hooks](#157-hooks)
  - [15.8 Archivos a Crear](#158-archivos-a-crear)
  - [15.9 SCSS: Directrices](#159-scss-directrices)
  - [15.10 Verificación](#1510-verificación)
- [16. Inventario Consolidado de Entidades](#16-inventario-consolidado-de-entidades)
- [17. Inventario Consolidado de Services](#17-inventario-consolidado-de-services)
- [18. Inventario Consolidado de Endpoints REST API](#18-inventario-consolidado-de-endpoints-rest-api)
- [19. Paleta de Colores y Design Tokens](#19-paleta-de-colores-y-design-tokens)
  - [19.1 Tokens de Color de Platform Services](#191-tokens-de-color-de-platform-services)
  - [19.2 Implementación en SCSS](#192-implementación-en-scss)
- [20. Patrón de Iconos SVG](#20-patrón-de-iconos-svg)
- [21. Orden de Implementación Global y Dependencias](#21-orden-de-implementación-global-y-dependencias)
- [22. Estimación Total de Esfuerzo](#22-estimación-total-de-esfuerzo)
- [23. Registro de Cambios](#23-registro-de-cambios)

---

## 1. Resumen Ejecutivo

Las especificaciones técnicas 108-117 (serie `20260117i`) definen la **capa de servicios transversales de plataforma** del Ecosistema Jaraba. A diferencia de los verticales (AgroConecta, ComercioConecta, ServiciosConecta, Empleabilidad, Emprendimiento) que resuelven necesidades sectoriales, estos 10 módulos proporcionan capacidades horizontales que benefician a **todos los verticales y todos los tenants** simultáneamente.

Estos servicios de plataforma son el puente entre un SaaS funcional y un **SaaS de clase mundial**: agent flows para automatización inteligente, PWA para acceso móvil offline, onboarding gamificado para activación, pricing basado en uso para monetización flexible, marketplace de integraciones para extensibilidad, customer success para retención proactiva, knowledge base para autoservicio, compliance para contratos enterprise/B2G, analytics avanzado para inteligencia de negocio y white-label para escalado a través de partners.

### 1.1 Visión y Posicionamiento

La capa Platform Services transforma el Ecosistema Jaraba de una plataforma vertical exitosa a una **plataforma-como-infraestructura** (PaaI) capaz de escalar exponencialmente. Cada módulo desbloquea un multiplicador de valor:

| Módulo | Multiplicador de Valor | Impacto en Negocio |
|--------|----------------------|-------------------|
| AI Agent Flows (108) | Automatización sin código | Reduce operaciones manuales 60-80% |
| PWA Mobile (109) | Acceso ubicuo sin app stores | +30% engagement, acceso rural offline |
| Onboarding Product-Led (110) | Activación self-service | <5 min a valor, -50% tickets soporte |
| Usage-Based Pricing (111) | Monetización flexible | +25% ARPU vía overage billing |
| Integration Marketplace (112) | Ecosistema abierto | 50+ conectores Y1, efecto red |
| Customer Success (113) | Retención proactiva | NRR 115-120%, <5% churn anual |
| Knowledge Base (114) | Autoservicio inteligente | -40% tickets, FAQ bot con RAG |
| Security Compliance (115) | Enterprise/B2G ready | SOC 2 + ISO 27001 + ENS |
| Advanced Analytics (116) | BI self-service | +40% adopción informes, decisiones data-driven |
| White-Label (117) | Escala via partners | 10+ franquicias Y1, revenue share 20-40% |

### 1.2 Relación con la infraestructura existente

Todos los módulos de Platform Services se construyen sobre la infraestructura consolidada del ecosistema:

- **ecosistema_jaraba_core**: Entidades base (Tenant, Vertical, SaasPlan, Feature), servicios compartidos (TenantManager, PlanValidator, FinOpsTrackingService, JarabaStripeConnect), sistema de permisos RBAC multi-tenant, RequestTrackingSubscriber.
- **ecosistema_jaraba_theme**: Tema unificado con Federated Design Tokens v2.1, parciales Twig reutilizables (`_header.html.twig`, `_footer.html.twig`, `_copilot-fab.html.twig`, `_slide-panel.html.twig`), premium cards con glassmorphism, 70+ opciones configurables vía UI de Drupal.
- **jaraba_rag**: Pipeline RAG con Qdrant, embeddings OpenAI, grounding validator. Reutilizado por Knowledge Base (doc 114) y AI Agent Flows (doc 108).
- **jaraba_copilot_v2**: Sistema de copiloto con 5 modos, ModeDetectorService. Reutilizado por FAQ Bot (doc 114) y Agent Flows (doc 108).
- **jaraba_foc**: Centro de Operaciones Financieras. Reutilizado por Usage-Based Pricing (doc 111) y Analytics BI (doc 116).
- **jaraba_journey**: Journey Engine con 7 verticales, 19 avatares. Reutilizado por Onboarding (doc 110) y Customer Success (doc 113).
- **jaraba_email**: Email marketing con generación IA. Reutilizado por White-Label templates (doc 117) y Customer Success playbooks (doc 113).
- **jaraba_ai_agents**: Agentic workflows, Tool Registry, multi-provider failover. Base directa para AI Agent Flows (doc 108).

### 1.3 Patrón arquitectónico de referencia

Cada módulo de Platform Services sigue el patrón verificado en los verticales existentes:

```
jaraba_{modulo}/
├── jaraba_{modulo}.info.yml          ← Metadatos del módulo
├── jaraba_{modulo}.module            ← Hooks (entity_insert, entity_update, cron, mail)
├── jaraba_{modulo}.services.yml      ← Inyección de dependencias
├── jaraba_{modulo}.routing.yml       ← Rutas frontend + admin + API REST
├── jaraba_{modulo}.links.menu.yml    ← Navegación en /admin/structure
├── jaraba_{modulo}.links.task.yml    ← Pestañas en /admin/content
├── jaraba_{modulo}.links.action.yml  ← Botones "Añadir" en listados
├── jaraba_{modulo}.permissions.yml   ← Permisos granulares
├── jaraba_{modulo}.libraries.yml     ← Declaración de assets CSS/JS
├── config/
│   ├── install/                      ← Configuración inicial (taxonomías, settings)
│   └── schema/                       ← Esquemas de configuración
├── src/
│   ├── Entity/                       ← Content Entities con @ContentEntityType
│   ├── Controller/                   ← Controllers frontend + admin + API
│   ├── Service/                      ← Lógica de negocio
│   ├── Form/                         ← Formularios de entidades + settings
│   ├── Access/                       ← Control de acceso por entidad
│   └── EventSubscriber/              ← Suscriptores de eventos (request tracking, etc.)
├── templates/                        ← Twig: páginas + parciales
├── scss/                             ← SCSS: variables + partials + main.scss
├── css/                              ← CSS compilado (NO editar manualmente)
├── js/                               ← JavaScript ES6+
├── images/icons/                     ← SVGs outline + duotone
└── package.json                      ← Scripts npm para compilación Dart Sass
```

**Reglas inmutables de este patrón:**
1. **Content Entities** (no Config Entities) para datos de usuario/operación — garantiza Field UI, Views, Entity Reference y revisiones.
2. **Navegación dual**: `/admin/structure/{modulo}` para tipos/configuración, `/admin/content/{modulo}` para contenido de usuario.
3. **Frontend limpio**: Templates Twig sin `page.content` ni bloques heredados de Drupal, layout full-width, mobile-first.
4. **CRUD en modal**: Todas las acciones crear/editar/ver abren en slide-panel off-canvas, el usuario nunca abandona la página.
5. **Variables inyectables**: Todo color, tipografía y espaciado usa `var(--ej-*, $fallback)` — configurable desde UI de Drupal sin tocar código.
6. **Hook-first**: Automatizaciones en `.module` con hooks de Drupal (no ECA BPMN) para versionado en Git y testabilidad.

### 1.4 Esfuerzo estimado total

| Fase | Módulo | Sprints | Horas (min-max) |
|------|--------|---------|-----------------|
| 1 | AI Agent Flows (108) | 7 | 370-480 |
| 2 | PWA Mobile (109) | 6 | 210-270 |
| 3 | Onboarding Product-Led (110) | 5 | 150-195 |
| 4 | Usage-Based Pricing (111) | 5 | 155-205 |
| 5 | Integration Marketplace (112) | 7 | 360-490 |
| 6 | Customer Success (113) | 7 | 290-410 |
| 7 | Knowledge Base (114) | 6 | 250-340 |
| 8 | Security Compliance (115) | 6 fases | 100-150 (software) + €60-95k (auditorías) |
| 9 | Advanced Analytics (116) | 6 | 310-410 |
| 10 | White-Label (117) | 6 | 290-390 |
| **TOTAL** | **10 módulos** | **~61 sprints** | **~2,485-3,340 h** |

---

## 2. Tabla de Correspondencia con Especificaciones Técnicas

| Doc # | Título Especificación | Fase | Módulo Drupal | Entidades Principales | Estado |
|-------|----------------------|------|--------------|----------------------|--------|
| **108** | Platform AI Agent Flows | Fase 1 | `jaraba_agent_flows` | AgentFlow, AgentFlowExecution, AgentFlowStepLog | ⬜ Planificada |
| **109** | Platform PWA Mobile | Fase 2 | `jaraba_pwa` | PendingSyncAction, PushSubscription | ⬜ Planificada |
| **110** | Platform Onboarding Product-Led | Fase 3 | `jaraba_onboarding` | OnboardingTemplate, UserOnboardingProgress | ⬜ Planificada |
| **111** | Platform Usage-Based Pricing | Fase 4 | `jaraba_usage_billing` | UsageEvent, UsageAggregate, PricingRule | ⬜ Planificada |
| **112** | Platform Integration Marketplace | Fase 5 | `jaraba_integrations` | Connector, ConnectorInstallation, OauthClient, WebhookSubscription | ⬜ Planificada |
| **113** | Platform Customer Success | Fase 6 | `jaraba_customer_success` | CustomerHealth, ChurnPrediction, CsPlaybook, ExpansionSignal | ⬜ Planificada |
| **114** | Platform Knowledge Base | Fase 7 | `jaraba_knowledge_base` | KbArticle, KbCategory, KbVideo, FaqConversation | ⬜ Planificada |
| **115** | Platform Security Compliance | Fase 8 | `jaraba_security_compliance` | AuditLog, SecurityPolicy, ComplianceAssessment | ⬜ Planificada |
| **116** | Platform Advanced Analytics | Fase 9 | `jaraba_analytics_bi` | CustomReport, Dashboard, ScheduledReport | ⬜ Planificada |
| **117** | Platform White-Label | Fase 10 | `jaraba_whitelabel` | WhitelabelConfig, CustomDomain, EmailTemplate, Reseller | ⬜ Planificada |

**Dependencias cruzadas con módulos existentes:**

| Doc # | Reutiliza de | Porcentaje |
|-------|-------------|-----------|
| 108 | `jaraba_ai_agents` (Tool Registry, providers) | 40% |
| 109 | Core (TenantContext), Theme (parciales) | 20% |
| 110 | `jaraba_journey` (engine, avatares) | 35% |
| 111 | `jaraba_foc` (FinOps tracking), Core (Stripe) | 50% |
| 112 | Core (TenantManager, permisos RBAC) | 25% |
| 113 | `jaraba_foc` (métricas), `jaraba_email` (notificaciones) | 30% |
| 114 | `jaraba_rag` (Qdrant, embeddings, grounding) | 60% |
| 115 | Core (RequestTracking, permisos) | 30% |
| 116 | `jaraba_foc` (datos financieros), Core (entidades) | 35% |
| 117 | Theme (Design Tokens, CSS vars), `jaraba_email` | 40% |

---

## 3. Cumplimiento de Directrices del Proyecto

### 3.1 Directriz: i18n — Textos siempre traducibles

**Referencia:** `.agent/workflows/i18n-traducciones.md`

Toda cadena de texto visible al usuario DEBE ser traducible. En un proyecto con base en español de España, se usa el texto en español directamente dentro de las funciones de traducción.

| Contexto | Método | Ejemplo |
|----------|--------|---------|
| Controllers PHP | `$this->t()` | `$this->t('Panel de Agentes IA')` |
| Services PHP | `$this->t()` (con StringTranslationTrait) | `$this->t('Flujo ejecutado correctamente')` |
| Templates Twig | `{% trans %}...{% endtrans %}` | `{% trans %}Crear nuevo flujo{% endtrans %}` |
| JavaScript ES6+ | `Drupal.t()` | `Drupal.t('Sincronización completada')` |
| Mensajes de error | `$this->t()` con placeholders | `$this->t('Error en paso @step', ['@step' => $stepId])` |
| Formularios | `'#title' => $this->t(...)` | `'#title' => $this->t('Nombre del flujo')` |
| Abreviaturas | Siempre en español completo | `5.1 meses` (NO `5.1 mo`) |
| Acrónimos técnicos | Glosario visible si es necesario | `MRR (Ingresos Recurrentes Mensuales)` |

**Gestión de traducciones:** `/admin/config/regional/translate`

**Regla crítica:** Nunca escribir texto visible al usuario fuera de una función de traducción. Esto incluye atributos `aria-label`, placeholders de formularios, tooltips, mensajes flash y textos en JavaScript.

### 3.2 Directriz: Modelo SCSS con Federated Design Tokens

**Referencia:** `docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md`, `.agent/workflows/scss-estilos.md`

Los 10 módulos de Platform Services son **consumidores** del sistema de Design Tokens. Ninguno define variables `$ej-*` propias. Todos consumen exclusivamente `var(--ej-*, $fallback)` de la cascada de 5 capas:

| Capa | Fuente | Ejemplo | Prioridad |
|------|--------|---------|-----------|
| L1 | `_variables.scss` (ecosistema_jaraba_core) | `$ej-color-primary-fallback: #2E7D32` | Más baja (compile-time) |
| L2 | `_injectable.scss` (ecosistema_jaraba_core) | `:root { --ej-color-primary: #2E7D32 }` | Base runtime |
| L3 | Component tokens (cada módulo) | `.agent-flow-card { --card-accent: var(--ej-color-primary) }` | Componente |
| L4 | Tenant Override (`hook_preprocess_html()`) | `:root { --ej-color-primary: #FF8C42 }` | Tenant |
| L5 | Vertical Presets (DesignTokenConfig entity) | Paleta predefinida por vertical | Más alta |

**Patrón correcto para SCSS de Platform Services:**

```scss
// ✅ CORRECTO: Solo consumir CSS Custom Properties con fallback
.agent-flow-card {
  background: var(--ej-bg-surface, #FFFFFF);
  border: 1px solid var(--ej-border-color, #E5E7EB);
  border-radius: var(--ej-border-radius, 12px);
  box-shadow: var(--ej-shadow-md, 0 4px 6px rgba(0, 0, 0, 0.07));
  padding: var(--ej-spacing-lg, 1.5rem);
  color: var(--ej-text-primary, #212121);
  font-family: var(--ej-font-body, 'Inter', sans-serif);
  transition: var(--ej-transition, all 250ms cubic-bezier(0.4, 0, 0.2, 1));
}

// ❌ PROHIBIDO: Definir variables SCSS locales
// $my-card-color: #233D63;  // NO — viola el SSOT
// $ej-color-primary: #FF8C42;  // NO — duplica core
```

**Cada módulo DEBE tener `package.json`:**
```json
{
  "name": "jaraba-{modulo}",
  "version": "1.0.0",
  "scripts": {
    "build": "sass scss/main.scss:css/jaraba-{modulo}.css --style=compressed",
    "watch": "sass scss/main.scss:css/jaraba-{modulo}.css --watch"
  },
  "devDependencies": {
    "sass": "^1.80.0"
  }
}
```

### 3.3 Directriz: Dart Sass moderno

**Referencia:** Directrices v5.9 §2.2.1

Todos los módulos usan Dart Sass `^1.71.0` (nunca LibSass). Las funciones deprecadas están prohibidas:

```scss
// ✅ CORRECTO: Dart Sass moderno con @use
@use 'sass:color';
@use 'sass:math';

.status-badge--success {
  background: color.scale(#43A047, $lightness: 80%);
}

.grid-item {
  width: math.div(100%, 3);
}

// ❌ PROHIBIDO: Funciones deprecadas
// background: darken($color, 10%);   // NO
// background: lighten($color, 20%);  // NO
// background: saturate($color, 15%); // NO
// width: 100% / 3;                   // NO — división ambigua
```

**Regla de aislamiento de módulos Dart Sass:** Cada archivo SCSS parcial es un módulo independiente. Las variables del `main.scss` NO se heredan automáticamente a los parciales importados con `@use`. Cada parcial que necesite variables DEBE declararlas explícitamente:

```scss
// _agent-flow-builder.scss
@use 'sass:color';
// Nota: NO necesitamos @use 'variables' porque solo usamos var(--ej-*)
// Solo necesitaríamos @use 'variables' si usáramos $ej-* fallbacks directamente

.agent-flow-builder {
  background: var(--ej-bg-page, linear-gradient(135deg, #FAFAFA 0%, #EEEEEE 100%));
}
```

**Compilación en WSL (dentro del contenedor Docker NO — en WSL sí):**
```bash
cd /home/PED/JarabaImpactPlatformSaaS/web/modules/custom/jaraba_{modulo}
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"
nvm use --lts
npm run build
lando drush cr
```

### 3.4 Directriz: Frontend limpio sin regiones Drupal

**Referencia:** `.agent/workflows/frontend-page-pattern.md`, Directrices v5.9 §2.2.2

Cada página de frontend de Platform Services se sirve desde un template Twig dedicado que controla el 100% del HTML. NO se usa `page.content`, `page.sidebar`, ni bloques heredados de Drupal.

**Estructura obligatoria de cada página frontend:**

```twig
{# page--agent-flows.html.twig #}
{# Página de gestión de Agent Flows — layout limpio full-width #}

{{ attach_library('ecosistema_jaraba_theme/global') }}
{{ attach_library('jaraba_agent_flows/agent-flows') }}

{% include '@ecosistema_jaraba_theme/partials/_header.html.twig' only %}

<main id="main-content" class="platform-page platform-page--agent-flows" role="main">
  <div class="platform-container">
    {# Contenido inyectado desde el controller #}
    {{ content }}
  </div>
</main>

{% include '@ecosistema_jaraba_theme/partials/_footer.html.twig' only %}
{% include '@ecosistema_jaraba_theme/partials/_slide-panel.html.twig' only %}
{% include '@ecosistema_jaraba_theme/partials/_copilot-fab.html.twig' only %}
```

**Reglas del layout limpio:**
- `max-width: 1400px` centrado con `margin: 0 auto`
- Padding responsive: `1rem` móvil, `2rem` tablet, `3rem` desktop
- Sin sidebar de admin (excepto usuario administrador de Drupal)
- Mobile-first: diseño base para móvil, media queries para ampliación
- Full-width: sin restricción de contenedor Drupal

**Theme suggestions en el `.theme`:**
```php
// En ecosistema_jaraba_theme.theme
function ecosistema_jaraba_theme_theme_suggestions_page_alter(array &$suggestions, array $variables): void {
  $route = \Drupal::routeMatch()->getRouteName();

  // Platform Services: Agent Flows
  if (str_starts_with($route, 'jaraba_agent_flows.')) {
    $suggestions[] = 'page__agent_flows';
  }
  // Platform Services: Onboarding
  if (str_starts_with($route, 'jaraba_onboarding.')) {
    $suggestions[] = 'page__onboarding';
  }
  // ... un suggestion por cada módulo de Platform Services
}
```

**El tenant NO debe acceder al tema de administración de Drupal.** Las rutas `/admin/*` de gestión de entidades son solo para el superadministrador de la plataforma. Los tenants gestionan todo desde las rutas frontend limpias (`/agent-flows`, `/integrations`, `/analytics`, etc.) con slide-panels para CRUD.

### 3.5 Directriz: Body classes via hook_preprocess_html()

**Referencia:** `.agent/workflows/frontend-page-pattern.md`

⚠️ **Las clases añadidas en el template con `attributes.addClass()` NO funcionan para el `<body>`.** El `<body>` se renderiza en `html.html.twig`, no en `page.html.twig`. Se DEBE usar `hook_preprocess_html()`:

```php
/**
 * Implements hook_preprocess_HOOK() para html.
 *
 * Añade clases CSS al body según la ruta activa.
 * Las clases se usan para aplicar layouts limpios full-width
 * en las páginas de Platform Services, sin sidebar ni regiones de Drupal.
 */
function ecosistema_jaraba_theme_preprocess_html(array &$variables): void {
  $route = \Drupal::routeMatch()->getRouteName() ?? '';

  // Mapa de rutas Platform Services → clases body
  $platform_routes = [
    'jaraba_agent_flows.' => 'page-platform page-agent-flows',
    'jaraba_pwa.' => 'page-platform page-pwa',
    'jaraba_onboarding.' => 'page-platform page-onboarding',
    'jaraba_usage_billing.' => 'page-platform page-usage-billing',
    'jaraba_integrations.' => 'page-platform page-integrations',
    'jaraba_customer_success.' => 'page-platform page-customer-success',
    'jaraba_knowledge_base.' => 'page-platform page-knowledge-base',
    'jaraba_security_compliance.' => 'page-platform page-security',
    'jaraba_analytics_bi.' => 'page-platform page-analytics',
    'jaraba_whitelabel.' => 'page-platform page-whitelabel',
  ];

  foreach ($platform_routes as $prefix => $classes) {
    if (str_starts_with($route, $prefix)) {
      foreach (explode(' ', $classes) as $class) {
        $variables['attributes']['class'][] = $class;
      }
      break;
    }
  }
}
```

**SCSS asociado para layout limpio:**
```scss
// Aplicar layout limpio a todas las páginas de Platform Services
body.page-platform {
  // Ocultar sidebar de admin para tenants
  .layout-sidebar-first,
  .region-sidebar-first {
    display: none;
  }

  // Layout full-width
  .layout-content {
    width: 100%;
    max-width: 100%;
    padding: 0;
  }
}
```

### 3.6 Directriz: CRUD en modales slide-panel

**Referencia:** `.agent/workflows/slide-panel-modales.md`

Todas las operaciones de crear, editar y ver entidades en las páginas frontend se abren en un slide-panel off-canvas. El usuario nunca abandona la página en la que está trabajando.

**Activación del slide-panel (sin JavaScript adicional):**
```html
<button class="btn btn--primary"
        data-slide-panel="agent-flow-form"
        data-slide-panel-url="/agent-flows/add"
        data-slide-panel-title="Crear nuevo flujo">
  {% trans %}+ Nuevo flujo{% endtrans %}
</button>
```

**El controller detecta AJAX y devuelve HTML limpio:**
```php
/**
 * Crea un nuevo AgentFlow.
 *
 * Detecta si la petición viene del slide-panel (AJAX) para devolver
 * solo el formulario HTML limpio, o si es una petición normal para
 * devolver la página completa con layout.
 */
public function add(Request $request): array|Response {
  $entity = $this->entityTypeManager()
    ->getStorage('agent_flow')
    ->create();
  $form = $this->entityFormBuilder()->getForm($entity, 'add');

  // Si es AJAX (slide-panel) → devolver HTML limpio sin layout
  if ($request->isXmlHttpRequest()) {
    $html = (string) $this->renderer->render($form);
    return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
  }

  // Petición normal → página completa
  return [
    '#theme' => 'agent_flow_form_page',
    '#form' => $form,
  ];
}
```

**Ocultar ruido de Drupal en formularios (format guidelines, etc.):**
```php
/**
 * Implements hook_form_alter().
 *
 * Oculta las guías de formato de texto y otros elementos de UI
 * innecesarios en los formularios de Platform Services para mantener
 * la experiencia limpia en el slide-panel.
 */
function jaraba_agent_flows_form_alter(array &$form, FormStateInterface $form_state, string $form_id): void {
  if (str_contains($form_id, 'agent_flow')) {
    _jaraba_agent_flows_hide_format_guidelines($form);
  }
}

function _jaraba_agent_flows_hide_format_guidelines(array &$element): void {
  if (isset($element['format'])) {
    $element['format']['#access'] = FALSE;
  }
  foreach (array_keys($element) as $key) {
    if (is_array($element[$key]) && !str_starts_with((string) $key, '#')) {
      _jaraba_agent_flows_hide_format_guidelines($element[$key]);
    }
  }
}
```

**Dependencia de librería obligatoria en cada `.libraries.yml`:**
```yaml
agent-flows:
  css:
    component:
      css/jaraba-agent-flows.css: {}
  js:
    js/agent-flows.js: {}
  dependencies:
    - ecosistema_jaraba_theme/slide-panel
    - core/drupal
    - core/once
```

### 3.7 Directriz: Entidades con Field UI y Views

**Referencia:** `.agent/workflows/drupal-custom-modules.md`, Directrices v5.9 §5

Todas las entidades de Platform Services se implementan como **ContentEntity** (nunca ConfigEntity para datos de usuario/operación). Esto garantiza:
- ✅ Field UI: Añadir campos desde `/admin/structure/{entity_type}/manage/fields`
- ✅ Views: Crear listados personalizados desde `/admin/structure/views`
- ✅ Entity Reference: Relacionar entidades entre sí
- ✅ Revisiones: Historial de cambios
- ✅ Acceso granular: `AccessControlHandler` por entidad

**Annotation obligatoria con handlers completos:**
```php
/**
 * Define la entidad AgentFlow.
 *
 * Representa un flujo de trabajo de agente IA con definición de pasos,
 * triggers y configuración de ejecución. Los flujos se crean por tenant
 * y pueden ser activados manual, automática o programáticamente.
 *
 * @ContentEntityType(
 *   id = "agent_flow",
 *   label = @Translation("Flujo de Agente IA"),
 *   label_collection = @Translation("Flujos de Agentes IA"),
 *   label_singular = @Translation("flujo de agente IA"),
 *   label_plural = @Translation("flujos de agentes IA"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_agent_flows\AgentFlowListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_agent_flows\Form\AgentFlowForm",
 *       "add" = "Drupal\jaraba_agent_flows\Form\AgentFlowForm",
 *       "edit" = "Drupal\jaraba_agent_flows\Form\AgentFlowForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_agent_flows\AgentFlowAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "agent_flow",
 *   admin_permission = "administer agent flows",
 *   field_ui_base_route = "jaraba_agent_flows.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/agent-flows/{agent_flow}",
 *     "add-form" = "/admin/content/agent-flows/add",
 *     "edit-form" = "/admin/content/agent-flows/{agent_flow}/edit",
 *     "delete-form" = "/admin/content/agent-flows/{agent_flow}/delete",
 *     "collection" = "/admin/content/agent-flows",
 *   },
 * )
 */
```

**4 archivos YAML obligatorios por entidad:**

1. **`.routing.yml`** — Rutas admin + frontend + API:
```yaml
# Rutas admin para AgentFlow
entity.agent_flow.collection:
  path: '/admin/content/agent-flows'
  defaults:
    _entity_list: 'agent_flow'
    _title: 'Flujos de Agentes IA'
  requirements:
    _permission: 'manage agent flows'

# Ruta frontend limpia
jaraba_agent_flows.dashboard:
  path: '/agent-flows'
  defaults:
    _controller: '\Drupal\jaraba_agent_flows\Controller\AgentFlowDashboardController::dashboard'
    _title: 'Agent Flows'
  requirements:
    _permission: 'access agent flows'
```

2. **`.links.menu.yml`** — Navegación en `/admin/structure`:
```yaml
jaraba_agent_flows.settings:
  title: 'Agent Flows'
  description: 'Configuración de flujos de agentes IA'
  route_name: jaraba_agent_flows.settings
  parent: system.admin_structure
  weight: 40
```

3. **`.links.task.yml`** — Pestañas en `/admin/content`:
```yaml
entity.agent_flow.collection:
  title: 'Flujos IA'
  route_name: entity.agent_flow.collection
  base_route: system.admin_content
  weight: 20
```

4. **`.links.action.yml`** — Botones "Añadir":
```yaml
entity.agent_flow.add_form:
  title: 'Añadir flujo de agente IA'
  route_name: entity.agent_flow.add_form
  appears_on:
    - entity.agent_flow.collection
```

### 3.8 Directriz: No hardcodear configuración

**Referencia:** Directrices v5.9 §5

Ninguna configuración de negocio puede estar hardcodeada en el código. Todos los valores configurables se gestionan a través de:

| Tipo de Configuración | Mecanismo | Ejemplo |
|----------------------|-----------|---------|
| Límites de plan | `SaasPlan` ContentEntity con campos | `max_agent_flows`, `max_integrations` |
| Feature flags | `Feature` ContentEntity | `agent_flows_enabled`, `whitelabel_enabled` |
| API keys | `settings.local.php` (variables de entorno) | `STRIPE_SECRET_KEY`, `QDRANT_API_KEY` |
| Textos del footer | Theme settings (UI de Drupal) | `footer_text`, `support_email` |
| Colores de marca | Theme settings → CSS Custom Properties | `color_primary` → `--ej-color-primary` |
| Umbrales de negocio | Config forms del módulo | `churn_risk_threshold`, `usage_alert_percentage` |
| Templates de email | ContentEntity `EmailTemplate` | subject, body MJML, variables |

**Los services cargan límites desde las entidades, NUNCA desde constantes:**
```php
// ✅ CORRECTO: Cargar límites desde el plan del tenant
$plan = $this->planValidator->getTenantPlan($tenantId);
$maxFlows = (int) $plan->get('max_agent_flows')->value;

// ❌ PROHIBIDO: Hardcodear límites
// const MAX_FLOWS = 10;
```

### 3.9 Directriz: Parciales Twig reutilizables

**Referencia:** `.agent/workflows/frontend-page-pattern.md`

Antes de crear un nuevo componente Twig, verificar si ya existe un parcial reutilizable en el tema o en otro módulo. Los parciales existentes del tema que Platform Services DEBE reutilizar:

| Parcial | Ubicación | Uso |
|---------|-----------|-----|
| `_header.html.twig` | `ecosistema_jaraba_theme/templates/partials/` | Header responsive con navegación |
| `_footer.html.twig` | `ecosistema_jaraba_theme/templates/partials/` | Footer configurable desde theme settings |
| `_slide-panel.html.twig` | `ecosistema_jaraba_theme/templates/partials/` | Modal off-canvas singleton para CRUD |
| `_copilot-fab.html.twig` | `ecosistema_jaraba_theme/templates/partials/` | Botón flotante del copiloto IA |
| `_mobile-menu.html.twig` | `ecosistema_jaraba_theme/templates/partials/` | Menú overlay para móvil |

**Si un componente se va a usar en 2+ páginas de Platform Services, crear un parcial compartido:**
```
web/modules/custom/jaraba_{modulo}/templates/partials/
├── _platform-stat-card.html.twig     ← Tarjeta de estadística reutilizable
├── _platform-empty-state.html.twig   ← Estado vacío con CTA
├── _platform-data-table.html.twig    ← Tabla de datos con ordenación
└── _platform-filter-bar.html.twig    ← Barra de filtros
```

**Uso correcto del `{% include %}` con `only`:**
```twig
{# Incluir parcial del tema (configurable desde UI) #}
{% include '@ecosistema_jaraba_theme/partials/_footer.html.twig' only %}

{# Incluir parcial del módulo con variables explícitas #}
{% include '@jaraba_agent_flows/partials/_flow-card.html.twig' with {
  'flow': flow,
  'show_actions': true,
} only %}
```

**Regla de la keyword `only`:** Siempre usar `only` para prevenir fuga de variables al parcial. Solo pasar las variables que el parcial necesita explícitamente.

### 3.10 Directriz: Seguridad

**Referencia:** Directrices v5.9 §4.5-4.6

| Área | Requisito | Implementación |
|------|-----------|----------------|
| API Keys | Variables de entorno, NUNCA en config DB | `settings.local.php`: `$settings['stripe_secret']` |
| Rate Limiting | Obligatorio en todos los endpoints API | `RateLimiterService` con Redis (100 req/h RAG, 50 req/h Copilot) |
| CSRF | Token en formularios Drupal | Form API nativo (automático) |
| XSS | Escapar output en Twig | `{{ variable\|escape }}` (automático en Twig) |
| SQL Injection | Query builder de Drupal | `$query->condition()` (nunca SQL crudo) |
| Webhooks | Verificación HMAC obligatoria | `hash_hmac('sha256', $payload, $secret)` |
| Tenant isolation | Filtrar SIEMPRE por `tenant_id` | `$query->condition('tenant_id', $tenantId)` |
| Rutas admin | Parámetros con regex | `requirements: { id: '\d+' }` |
| Autenticación API | Todos los `/api/v1/*` autenticados | Bearer token + tenant validation |
| Qdrant | `must` (AND) para tenant_id | NUNCA `should` (OR) en filtros de tenant |

### 3.11 Directriz: Comentarios de código

**Referencia:** Directrices v5.9 §10

Todos los comentarios en **español**. Tres dimensiones obligatorias:

```php
/**
 * Servicio de cálculo de Health Score para Customer Success.
 *
 * ESTRUCTURA: Integrado en jaraba_customer_success, inyectado vía
 * services.yml. Depende de FinOpsTrackingService para métricas
 * financieras y de TenantManager para contexto multi-tenant.
 *
 * LÓGICA: Calcula una puntuación 0-100 ponderada por 5 dimensiones:
 * - Engagement (30%): DAU/MAU ratio, tiempo en app, features usadas
 * - Adoption (25%): Features activadas vs disponibles en plan
 * - Satisfaction (20%): NPS, CSAT, media de reviews
 * - Support (15%): 100 - (tickets_abiertos × 10)
 * - Growth (10%): Crecimiento MoM en uso/ingresos
 * Se recalcula diariamente por cron. Categorías: Healthy (80-100),
 * Neutral (60-79), At Risk (40-59), Critical (0-39).
 *
 * SINTAXIS:
 * @param int $tenantId ID del tenant a evaluar
 * @return array{overall_score: int, category: string, breakdown: array}
 * @throws \InvalidArgumentException Si el tenant no existe
 */
```

### 3.12 Directriz: Iconos SVG duotone

**Referencia:** `.agent/workflows/scss-estilos.md`

Cada módulo de Platform Services crea sus iconos SVG en dos versiones:

1. **Outline** (`{nombre}.svg`): Trazo limpio, `stroke="currentColor"`, `stroke-width="2"`
2. **Duotone** (`{nombre}-duotone.svg`): Fondo con `opacity="0.3"` + trazo principal

**Ubicación por categoría:**
```
web/modules/custom/ecosistema_jaraba_core/images/icons/
├── ai/           ← Agent Flows, AI integrations
├── analytics/    ← Analytics BI, Usage Billing, Customer Success
├── business/     ← White-Label, Integrations, Compliance
├── ui/           ← Onboarding, Knowledge Base, PWA
└── actions/      ← CRUD, sync, download, export
```

**Uso en Twig:**
```twig
{{ jaraba_icon('ai', 'agent-flow', {
    color: 'corporate',
    size: '24px',
    variant: 'duotone'
}) }}
```

### 3.13 Directriz: AI via abstracción @ai.provider

**Referencia:** `.agent/workflows/ai-integration.md`

NUNCA implementar clientes HTTP directos a APIs de LLM. Siempre usar el módulo `ai` de Drupal con `@ai.provider`:

```php
// ✅ CORRECTO: Usar AiProviderPluginManager
public function __construct(
  private AiProviderPluginManager $aiProvider,
) {}

public function generateDecision(string $context): string {
  $llm = $this->aiProvider->createInstance('anthropic');
  $response = $llm->chat([
    ['role' => 'system', 'content' => 'Eres un asistente de decisiones...'],
    ['role' => 'user', 'content' => $context],
  ], 'claude-3-5-sonnet-20241022');

  return $response->getText();
}
```

**Modelos recomendados por caso de uso en Platform Services:**

| Módulo | Caso de Uso | Provider | Modelo | Razón |
|--------|------------|----------|--------|-------|
| Agent Flows (108) | Decisiones de workflow | Anthropic | claude-3-5-sonnet | Mejor seguimiento de instrucciones |
| Agent Flows (108) | Extracción de datos | OpenAI | gpt-4o | Mejor precisión numérica |
| Onboarding (110) | Sugerencias contextuales | Anthropic | claude-3-haiku | Económico ($0.25/1M tokens) |
| Customer Success (113) | Predicción de churn | OpenAI | gpt-4o | Mejor para cálculos/ML |
| Knowledge Base (114) | FAQ Bot con grounding | Anthropic | claude-3-5-sonnet | Mejor grounding y citas |
| Analytics BI (116) | Resumen en lenguaje natural | Anthropic | claude-3-5-sonnet | Mejor redacción |

**Failover obligatorio entre providers:**
```php
private const PROVIDERS = ['anthropic', 'openai'];

public function callWithFailover(string $prompt): string {
  foreach (self::PROVIDERS as $provider) {
    try {
      return $this->callProvider($provider, $prompt);
    } catch (\Exception $e) {
      $this->logger->warning('Provider @id falló: @error', [
        '@id' => $provider,
        '@error' => $e->getMessage(),
      ]);
      continue;
    }
  }
  return $this->getFallbackResponse();
}
```

### 3.14 Directriz: Automaciones via hooks Drupal

**Referencia:** `.agent/workflows/drupal-eca-hooks.md`

Las automatizaciones de Platform Services se implementan con **hooks de Drupal en el `.module`**, NO con ECA BPMN UI. Razones: versionado en Git, testabilidad unitaria, rendimiento, consistencia con el resto del ecosistema.

**Hooks principales por módulo:**

| Módulo | hook_entity_insert | hook_entity_update | hook_cron | hook_mail |
|--------|-------------------|-------------------|-----------|-----------|
| Agent Flows (108) | Registrar nueva ejecución | Actualizar estado de ejecución | Procesar ejecuciones programadas | Notificar aprobaciones pendientes |
| PWA (109) | — | — | Limpiar sync actions procesadas | — |
| Onboarding (110) | Crear progreso al registrar usuario | Actualizar paso completado | Recordatorios de onboarding | Email de bienvenida + progreso |
| Usage Billing (111) | Registrar evento de uso | — | Agregar métricas horarias, sincronizar con Stripe | Alertas de umbral de uso |
| Integrations (112) | Registrar instalación de conector | Actualizar estado de conexión | Verificar salud de integraciones | Notificar desconexiones |
| Customer Success (113) | — | Recalcular health al cambiar datos | Calcular health scores diarios, ejecutar playbooks | Emails de playbook (reactivación, expansión) |
| Knowledge Base (114) | Generar embeddings al publicar artículo | Actualizar embeddings al editar | Recalcular artículos populares | — |
| Security Compliance (115) | Registrar evento en audit log | Registrar cambios en audit log | Limpiar logs antiguos (retención) | Alertas de seguridad |
| Analytics BI (116) | — | — | Ejecutar informes programados, ETL pipeline | Enviar informes por email |
| White-Label (117) | Provisionar dominio al crear config | Actualizar SSL al verificar dominio | Verificar DNS periódicamente | Emails con branding del tenant |

---

## 4. Arquitectura General de Módulos

### 4.1 Mapa de módulos y dependencias

```
┌─────────────────────────────────────────────────────────────┐
│                    CAPA PLATAFORMA                          │
│                (Platform Services Layer)                     │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌──────────┐  ┌──────────┐  ┌───────────┐  ┌──────────┐  │
│  │ Agent    │  │   PWA    │  │ Onboard-  │  │  Usage   │  │
│  │ Flows   │  │  Mobile  │  │   ing     │  │ Billing  │  │
│  │ (108)   │  │  (109)   │  │  (110)    │  │  (111)   │  │
│  └────┬─────┘  └────┬─────┘  └────┬──────┘  └────┬─────┘  │
│       │              │              │              │         │
│  ┌────┴─────┐  ┌────┴─────┐  ┌────┴──────┐  ┌────┴─────┐  │
│  │ Integra- │  │ Customer │  │ Knowledge │  │ Security │  │
│  │  tions   │  │ Success  │  │   Base    │  │ Compli-  │  │
│  │  (112)   │  │  (113)   │  │  (114)    │  │ ance(115)│  │
│  └────┬─────┘  └────┬─────┘  └────┬──────┘  └────┬─────┘  │
│       │              │              │              │         │
│  ┌────┴─────┐  ┌────┴──────────────┴──────────────┘         │
│  │Analytics │  │                                            │
│  │   BI     │  │  ┌───────────┐                             │
│  │  (116)   │  │  │ White-    │                             │
│  └──────────┘  │  │  Label    │                             │
│                │  │  (117)    │                             │
│                │  └───────────┘                             │
├────────────────┴────────────────────────────────────────────┤
│              CAPA CORE (Infraestructura Existente)          │
├─────────────────────────────────────────────────────────────┤
│  ecosistema_jaraba_core  │  jaraba_rag     │  jaraba_foc    │
│  ecosistema_jaraba_theme │  jaraba_journey │  jaraba_email  │
│  jaraba_ai_agents        │  jaraba_copilot │  jaraba_geo    │
└─────────────────────────────────────────────────────────────┘
```

**Dependencias directas entre módulos de Platform Services:**

| Módulo | Depende de (Platform Services) | Depende de (Core) |
|--------|-------------------------------|-------------------|
| Agent Flows (108) | — | jaraba_ai_agents, jaraba_rag, ecosistema_jaraba_core |
| PWA (109) | — | ecosistema_jaraba_core, ecosistema_jaraba_theme |
| Onboarding (110) | — | jaraba_journey, ecosistema_jaraba_core |
| Usage Billing (111) | — | jaraba_foc, ecosistema_jaraba_core (Stripe) |
| Integrations (112) | — | ecosistema_jaraba_core |
| Customer Success (113) | Usage Billing (111) | jaraba_foc, jaraba_email, ecosistema_jaraba_core |
| Knowledge Base (114) | — | jaraba_rag, ecosistema_jaraba_core |
| Security Compliance (115) | — | ecosistema_jaraba_core |
| Analytics BI (116) | Usage Billing (111), Customer Success (113) | jaraba_foc, ecosistema_jaraba_core |
| White-Label (117) | — | ecosistema_jaraba_theme, jaraba_email, ecosistema_jaraba_core |

### 4.2 Estructura de directorios estándar

Cada uno de los 10 módulos sigue esta estructura exacta:

```
web/modules/custom/jaraba_{modulo}/
├── jaraba_{modulo}.info.yml
├── jaraba_{modulo}.module
├── jaraba_{modulo}.install
├── jaraba_{modulo}.services.yml
├── jaraba_{modulo}.routing.yml
├── jaraba_{modulo}.links.menu.yml
├── jaraba_{modulo}.links.task.yml
├── jaraba_{modulo}.links.action.yml
├── jaraba_{modulo}.permissions.yml
├── jaraba_{modulo}.libraries.yml
├── config/
│   ├── install/              ← Config inicial (vocabularios, settings por defecto)
│   └── schema/
│       └── jaraba_{modulo}.schema.yml
├── src/
│   ├── Entity/               ← ContentEntity con @ContentEntityType
│   ├── Controller/           ← Dashboard, Detail, API controllers
│   ├── Service/              ← Lógica de negocio
│   ├── Form/                 ← Entity forms + settings forms
│   ├── Access/               ← AccessControlHandler por entidad
│   ├── EventSubscriber/      ← Request tracking, eventos de dominio
│   └── Plugin/               ← Plugins si aplica (Block, etc.)
├── templates/
│   ├── page--{modulo}-dashboard.html.twig
│   └── partials/
│       ├── _{modulo}-card.html.twig
│       └── _{modulo}-empty-state.html.twig
├── scss/
│   ├── main.scss
│   └── _{modulo}-dashboard.scss
├── css/
│   └── jaraba-{modulo}.css   ← Compilado (NO editar)
├── js/
│   └── {modulo}.js
├── images/icons/             ← SVGs específicos del módulo
│   ├── {icon}.svg
│   └── {icon}-duotone.svg
└── package.json
```

### 4.3 Compilación SCSS

**Comando estándar para cada módulo (ejecutar en WSL, NO en Docker):**
```bash
cd /home/PED/JarabaImpactPlatformSaaS/web/modules/custom/jaraba_{modulo}
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"
nvm use --lts
npm install    # Solo la primera vez
npm run build  # Compilar SCSS
lando drush cr # Limpiar caché de Drupal
```

**Verificación post-compilación:**
1. Confirmar que `css/jaraba-{modulo}.css` se ha generado
2. Hard refresh en navegador (Ctrl+F5)
3. Verificar en DevTools que los estilos se aplican
4. Comprobar que las CSS Custom Properties se resuelven correctamente

---

## 5. Estado por Fases

| Fase | Descripción | Docs Técnicos | Estado | Entidades | Sprints | Dependencia |
|------|-------------|---------------|--------|-----------|---------|-------------|
| **1** | AI Agent Flows | 108 | ⬜ Planificada | AgentFlow, AgentFlowExecution, AgentFlowStepLog | 7 | jaraba_ai_agents |
| **2** | PWA Mobile | 109 | ⬜ Planificada | PendingSyncAction, PushSubscription | 6 | Core, Theme |
| **3** | Onboarding Product-Led | 110 | ⬜ Planificada | OnboardingTemplate, UserOnboardingProgress | 5 | jaraba_journey |
| **4** | Usage-Based Pricing | 111 | ⬜ Planificada | UsageEvent, UsageAggregate, PricingRule | 5 | jaraba_foc, Stripe |
| **5** | Integration Marketplace | 112 | ⬜ Planificada | Connector, ConnectorInstallation, OauthClient, WebhookSubscription | 7 | Core RBAC |
| **6** | Customer Success | 113 | ⬜ Planificada | CustomerHealth, ChurnPrediction, CsPlaybook, ExpansionSignal | 7 | Fase 4 |
| **7** | Knowledge Base | 114 | ⬜ Planificada | KbArticle, KbCategory, KbVideo, FaqConversation | 6 | jaraba_rag |
| **8** | Security Compliance | 115 | ⬜ Planificada | AuditLog, SecurityPolicy, ComplianceAssessment | 6 fases | Core |
| **9** | Advanced Analytics | 116 | ⬜ Planificada | CustomReport, Dashboard, ScheduledReport | 6 | Fases 4, 6 |
| **10** | White-Label & Reseller | 117 | ⬜ Planificada | WhitelabelConfig, CustomDomain, EmailTemplate, Reseller | 6 | Theme, jaraba_email |

**Diagrama de dependencias entre fases:**
```
Fase 1 (Agent Flows) ────────────────┐
Fase 2 (PWA) ────────────────────────┤
Fase 3 (Onboarding) ─────────────────┤─── Independientes, paralelizables
Fase 7 (Knowledge Base) ─────────────┤
Fase 8 (Security Compliance) ────────┘

Fase 4 (Usage Billing) ──────┐
                              ├──→ Fase 6 (Customer Success)
                              │
                              ├──→ Fase 9 (Analytics BI)
                              │
Fase 5 (Integrations) ───────┘

Fase 10 (White-Label) ← depende del Theme + jaraba_email (ya existentes)
```

**Fases paralelizables (sin dependencias entre sí):**
- Bloque A: Fases 1, 2, 3, 7, 8 (se pueden desarrollar en paralelo)
- Bloque B: Fases 4, 5 (una vez Core consolidado)
- Bloque C: Fases 6, 9 (dependen de Fase 4)
- Bloque D: Fase 10 (independiente, depende solo de Core)

---

## 6. FASE 1: AI Agent Flows — Workflows Inteligentes (Doc 108)

### 6.1 Justificación

Los AI Agent Flows superan las limitaciones del copiloto conversacional permitiendo a los tenants crear **flujos de trabajo autónomos y deterministas enriquecidos con IA**. Un agente puede ejecutar secuencias multi-paso (extraer datos de un PDF → clasificar → enrutar al profesional adecuado → generar presupuesto → enviar email) sin intervención humana, con puntos de aprobación opcionales.

**Reutilización:** 40% de `jaraba_ai_agents` (Tool Registry, multi-provider failover, LLM abstraction). El módulo extiende la infraestructura existente añadiendo persistencia de flujos, motor de estados y visual builder.

**Prioridad:** ALTA — Diferenciador competitivo clave frente a plataformas sin IA agéntica.

### 6.2 Entidades

#### 6.2.1 Entidad `AgentFlow`

**Tipo:** ContentEntity
**ID:** `agent_flow`
**Base table:** `agent_flow`

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK autoincremental |
| `uuid` | uuid | ✅ | Identificador universal único |
| `name` | string(255) | ✅ | Nombre descriptivo del flujo. Indexado para búsqueda |
| `description` | text_long | ❌ | Descripción detallada del propósito y comportamiento del flujo |
| `tenant_id` | entity_reference (taxonomy_term) | ✅ | Aislamiento multi-tenant obligatorio. FK a vocabulario `tenants` |
| `trigger_type` | list_string | ✅ | Tipo de activación: `manual`, `webhook`, `schedule`, `event`. Default: `manual` |
| `trigger_config` | map (serialized) | ❌ | Configuración específica del trigger (cron expression, webhook URL, event name) |
| `flow_definition` | text_long | ✅ | Definición del flujo en formato JSON (XState-compatible). Contiene nodos, transiciones y configuración de cada paso |
| `input_schema` | text_long | ❌ | JSON Schema que valida los datos de entrada del flujo |
| `output_schema` | text_long | ❌ | JSON Schema que define la estructura de datos de salida |
| `requires_approval` | boolean | ✅ | Si TRUE, pausa la ejecución en nodos marcados hasta aprobación humana. Default: FALSE |
| `max_execution_time` | integer | ✅ | Timeout máximo en segundos. Default: 300 (5 min). Rango: 30-3600 |
| `retry_policy` | map (serialized) | ❌ | Política de reintentos: `{max_retries: 3, backoff_ms: 1000, backoff_multiplier: 2}` |
| `is_active` | boolean | ✅ | Estado activo/inactivo. Default: TRUE |
| `version` | integer | ✅ | Versión del flujo para control de cambios. Autoincremental |
| `created` | created | ✅ | Timestamp de creación |
| `changed` | changed | ✅ | Timestamp de última modificación |

**Handlers:**

| Handler | Clase | Propósito |
|---------|-------|-----------|
| list_builder | `AgentFlowListBuilder` | Listado con filtros por tenant, trigger_type, estado activo |
| views_data | `EntityViewsData` | Integración con Views para listados personalizados |
| form (default/add/edit) | `AgentFlowForm` | Formulario con editor JSON para flow_definition |
| form (delete) | `ContentEntityDeleteForm` | Confirmación de eliminación estándar |
| access | `AgentFlowAccessControlHandler` | Verificación de permisos + aislamiento por tenant |
| route_provider | `AdminHtmlRouteProvider` | Rutas CRUD automáticas en admin |

**Navegación admin:**

| Archivo | Clave | Path/Detalle |
|---------|-------|-------------|
| `.links.menu.yml` | `jaraba_agent_flows.settings` | parent: `system.admin_structure` |
| `.links.task.yml` | `entity.agent_flow.collection` | base_route: `system.admin_content` |
| `.links.action.yml` | `entity.agent_flow.add_form` | appears_on: `entity.agent_flow.collection` |

**Permisos:**
```yaml
administer agent flows:
  title: 'Administrar flujos de agentes IA'
  description: 'Acceso completo a la configuración y gestión de agent flows'
  restrict access: true

manage agent flows:
  title: 'Gestionar flujos de agentes IA'
  description: 'Crear, editar y ejecutar flujos de agentes IA del tenant'

access agent flows:
  title: 'Acceder a flujos de agentes IA'
  description: 'Ver y ejecutar flujos de agentes IA existentes'

view agent flow executions:
  title: 'Ver ejecuciones de flujos'
  description: 'Consultar el historial de ejecuciones y logs de agentes IA'
```

#### 6.2.2 Entidad `AgentFlowExecution`

**Tipo:** ContentEntity
**ID:** `agent_flow_execution`
**Base table:** `agent_flow_execution`

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK autoincremental |
| `uuid` | uuid | ✅ | Identificador universal único |
| `flow_id` | entity_reference (agent_flow) | ✅ | FK al flujo que se ejecuta. Indexado |
| `flow_version` | integer | ✅ | Versión del flujo al momento de la ejecución (snapshot) |
| `tenant_id` | entity_reference (taxonomy_term) | ✅ | Aislamiento multi-tenant |
| `triggered_by` | list_string | ✅ | Origen: `manual`, `webhook`, `schedule`, `event` |
| `triggered_by_user` | entity_reference (user) | ❌ | Usuario que inició la ejecución (NULL si automática) |
| `status` | list_string | ✅ | Estado: `pending`, `running`, `waiting_approval`, `completed`, `failed`, `cancelled`. Default: `pending` |
| `current_step` | string(255) | ❌ | ID del paso actualmente en ejecución |
| `input_data` | text_long | ❌ | Datos de entrada en JSON |
| `output_data` | text_long | ❌ | Datos de salida en JSON (se completa al finalizar) |
| `state_snapshot` | text_long | ❌ | Estado completo del motor XState serializado para reanudación |
| `error_message` | text_long | ❌ | Mensaje de error si status = `failed` |
| `total_duration_ms` | integer | ❌ | Duración total en milisegundos |
| `total_tokens_used` | integer | ❌ | Tokens LLM consumidos en toda la ejecución |
| `total_llm_cost` | decimal(10,6) | ❌ | Coste total de llamadas LLM en USD |
| `created` | created | ✅ | Timestamp de inicio |
| `changed` | changed | ✅ | Timestamp de última actualización |

#### 6.2.3 Entidad `AgentFlowStepLog`

**Tipo:** ContentEntity
**ID:** `agent_flow_step_log`
**Base table:** `agent_flow_step_log`

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK autoincremental |
| `execution_id` | entity_reference (agent_flow_execution) | ✅ | FK a la ejecución padre. Indexado |
| `step_id` | string(255) | ✅ | ID del paso dentro del flujo |
| `step_type` | list_string | ✅ | Tipo de nodo: `action`, `llm_decision`, `llm_generate`, `llm_extract`, `conditional`, `human_approval`, `wait`, `parallel`, `subflow`, `webhook`, `computer_use`, `file_process` |
| `input_data` | text_long | ❌ | Datos de entrada del paso en JSON |
| `output_data` | text_long | ❌ | Datos de salida del paso en JSON |
| `decision_made` | string(255) | ❌ | Decisión tomada por nodos LLM/condicionales |
| `llm_provider` | string(128) | ❌ | Provider LLM usado: `anthropic`, `openai`, `google` |
| `llm_model` | string(128) | ❌ | Modelo específico: `claude-3-5-sonnet`, `gpt-4o` |
| `llm_prompt` | text_long | ❌ | Prompt enviado al LLM (para auditoría) |
| `llm_response` | text_long | ❌ | Respuesta completa del LLM |
| `tokens_used` | integer | ❌ | Tokens consumidos en este paso |
| `llm_cost` | decimal(10,6) | ❌ | Coste del LLM en USD |
| `status` | list_string | ✅ | Estado: `pending`, `running`, `completed`, `failed`, `skipped`. Default: `pending` |
| `error_message` | text_long | ❌ | Error si status = `failed` |
| `duration_ms` | integer | ❌ | Duración del paso en milisegundos |
| `created` | created | ✅ | Timestamp |

### 6.3 Services

| Service | Clase | Métodos Clave | Descripción |
|---------|-------|---------------|-------------|
| `agent_flow.engine` | `AgentFlowEngineService` | `execute()`, `resume()`, `cancel()`, `retry()` | Motor de ejecución de flujos. Gestiona el ciclo de vida de una ejecución, procesando nodos secuencialmente o en paralelo según la definición del flujo |
| `agent_flow.llm_orchestrator` | `AgentFlowLlmService` | `makeDecision()`, `generateContent()`, `extractData()` | Orquestador de llamadas LLM con failover entre providers. Registra tokens, costes y prompts en StepLog |
| `agent_flow.trigger_manager` | `AgentFlowTriggerService` | `registerTrigger()`, `handleWebhook()`, `processSchedule()` | Gestiona los triggers de activación. Los schedule se procesan vía `hook_cron`. Los webhooks exponen endpoints autenticados |
| `agent_flow.validator` | `AgentFlowValidatorService` | `validateDefinition()`, `validateInput()` | Valida la definición JSON del flujo (nodos, transiciones, schema) antes de guardar. Valida input contra `input_schema` antes de ejecutar |
| `agent_flow.metrics` | `AgentFlowMetricsService` | `getExecutionStats()`, `getCostBreakdown()`, `getSuccessRate()` | Métricas agregadas de ejecuciones por tenant: tasa de éxito, coste medio, tiempo medio, distribución por tipo de nodo |

**Inyección de dependencias (`jaraba_agent_flows.services.yml`):**
```yaml
services:
  jaraba_agent_flows.engine:
    class: Drupal\jaraba_agent_flows\Service\AgentFlowEngineService
    arguments:
      - '@entity_type.manager'
      - '@jaraba_agent_flows.llm_orchestrator'
      - '@jaraba_agent_flows.validator'
      - '@logger.channel.jaraba_agent_flows'
      - '@ecosistema_jaraba_core.tenant_manager'

  jaraba_agent_flows.llm_orchestrator:
    class: Drupal\jaraba_agent_flows\Service\AgentFlowLlmService
    arguments:
      - '@ai.provider'
      - '@logger.channel.jaraba_agent_flows'

  jaraba_agent_flows.trigger_manager:
    class: Drupal\jaraba_agent_flows\Service\AgentFlowTriggerService
    arguments:
      - '@entity_type.manager'
      - '@jaraba_agent_flows.engine'
      - '@logger.channel.jaraba_agent_flows'

  jaraba_agent_flows.validator:
    class: Drupal\jaraba_agent_flows\Service\AgentFlowValidatorService

  jaraba_agent_flows.metrics:
    class: Drupal\jaraba_agent_flows\Service\AgentFlowMetricsService
    arguments:
      - '@entity_type.manager'
      - '@ecosistema_jaraba_core.tenant_context'
```

### 6.4 Controllers

| Controller | Clase | Rutas | Descripción |
|------------|-------|-------|-------------|
| Dashboard | `AgentFlowDashboardController` | `/agent-flows` | Página principal con listado de flujos, métricas, acciones rápidas. Layout limpio full-width |
| Detail | `AgentFlowDetailController` | `/agent-flows/{id}` | Detalle de un flujo con historial de ejecuciones, gráficos de rendimiento |
| API | `AgentFlowApiController` | `/api/v1/agent-flows/*` | REST API con CRUD completo, ejecución, historial, aprobación/rechazo |
| Webhook | `AgentFlowWebhookController` | `/api/v1/agent-flows/{id}/webhook` | Receptor de webhooks externos para triggers automáticos |

**Rutas principales (`jaraba_agent_flows.routing.yml`):**
```yaml
# Frontend limpio
jaraba_agent_flows.dashboard:
  path: '/agent-flows'
  defaults:
    _controller: '\Drupal\jaraba_agent_flows\Controller\AgentFlowDashboardController::dashboard'
    _title: 'Agent Flows'
  requirements:
    _permission: 'access agent flows'

jaraba_agent_flows.detail:
  path: '/agent-flows/{agent_flow}'
  defaults:
    _controller: '\Drupal\jaraba_agent_flows\Controller\AgentFlowDetailController::detail'
    _title_callback: '\Drupal\jaraba_agent_flows\Controller\AgentFlowDetailController::title'
  requirements:
    _permission: 'access agent flows'
    agent_flow: '\d+'
  options:
    parameters:
      agent_flow:
        type: entity:agent_flow

# CRUD vía slide-panel (AJAX)
jaraba_agent_flows.add:
  path: '/agent-flows/add'
  defaults:
    _controller: '\Drupal\jaraba_agent_flows\Controller\AgentFlowDashboardController::add'
    _title: 'Crear flujo de agente IA'
  requirements:
    _permission: 'manage agent flows'

jaraba_agent_flows.edit:
  path: '/agent-flows/{agent_flow}/edit'
  defaults:
    _controller: '\Drupal\jaraba_agent_flows\Controller\AgentFlowDashboardController::edit'
    _title: 'Editar flujo'
  requirements:
    _permission: 'manage agent flows'
    agent_flow: '\d+'

# API REST
jaraba_agent_flows.api.list:
  path: '/api/v1/agent-flows'
  defaults:
    _controller: '\Drupal\jaraba_agent_flows\Controller\AgentFlowApiController::list'
  requirements:
    _permission: 'access agent flows'
  methods: [GET]

jaraba_agent_flows.api.execute:
  path: '/api/v1/agent-flows/{agent_flow}/execute'
  defaults:
    _controller: '\Drupal\jaraba_agent_flows\Controller\AgentFlowApiController::execute'
  requirements:
    _permission: 'manage agent flows'
    agent_flow: '\d+'
  methods: [POST]

jaraba_agent_flows.api.executions:
  path: '/api/v1/agent-flows/{agent_flow}/executions'
  defaults:
    _controller: '\Drupal\jaraba_agent_flows\Controller\AgentFlowApiController::executions'
  requirements:
    _permission: 'view agent flow executions'
    agent_flow: '\d+'
  methods: [GET]

jaraba_agent_flows.api.execution_detail:
  path: '/api/v1/executions/{agent_flow_execution}'
  defaults:
    _controller: '\Drupal\jaraba_agent_flows\Controller\AgentFlowApiController::executionDetail'
  requirements:
    _permission: 'view agent flow executions'
    agent_flow_execution: '\d+'
  methods: [GET]

jaraba_agent_flows.api.approve:
  path: '/api/v1/executions/{agent_flow_execution}/approve'
  defaults:
    _controller: '\Drupal\jaraba_agent_flows\Controller\AgentFlowApiController::approve'
  requirements:
    _permission: 'manage agent flows'
    agent_flow_execution: '\d+'
  methods: [POST]

jaraba_agent_flows.api.reject:
  path: '/api/v1/executions/{agent_flow_execution}/reject'
  defaults:
    _controller: '\Drupal\jaraba_agent_flows\Controller\AgentFlowApiController::reject'
  requirements:
    _permission: 'manage agent flows'
    agent_flow_execution: '\d+'
  methods: [POST]

# Webhook receptor
jaraba_agent_flows.webhook:
  path: '/api/v1/agent-flows/{agent_flow}/webhook'
  defaults:
    _controller: '\Drupal\jaraba_agent_flows\Controller\AgentFlowWebhookController::receive'
  requirements:
    _access: 'TRUE'
    agent_flow: '\d+'
  methods: [POST]

# Settings admin
jaraba_agent_flows.settings:
  path: '/admin/structure/agent-flows'
  defaults:
    _form: '\Drupal\jaraba_agent_flows\Form\AgentFlowSettingsForm'
    _title: 'Configuración de Agent Flows'
  requirements:
    _permission: 'administer agent flows'
```

### 6.5 Templates y Parciales Twig

| Template | Archivo | Propósito |
|----------|---------|-----------|
| Página dashboard | `page--agent-flows.html.twig` | Layout limpio full-width con header/footer del tema |
| Dashboard content | `agent-flow-dashboard.html.twig` | Grid de flujos con métricas y acciones rápidas |
| Detalle de flujo | `agent-flow-detail.html.twig` | Visualización de flujo, historial, métricas |
| Parcial: card | `partials/_agent-flow-card.html.twig` | Tarjeta premium de un flujo con estado, triggers, última ejecución |
| Parcial: execution log | `partials/_agent-flow-execution-log.html.twig` | Timeline de pasos de una ejecución |
| Parcial: stats | `partials/_agent-flow-stats.html.twig` | Tarjetas de estadísticas (total ejecuciones, tasa éxito, coste medio) |
| Parcial: empty state | `partials/_agent-flow-empty-state.html.twig` | Estado vacío con ilustración y CTA para crear primer flujo |

### 6.6 Frontend Assets

**SCSS (`scss/main.scss`):**
```scss
@use 'agent-flow-dashboard';
@use 'agent-flow-detail';
@use 'agent-flow-card';
@use 'agent-flow-execution';
```

**JavaScript (`js/agent-flows.js`):**
- Inicialización del editor JSON para `flow_definition`
- Polling para actualizar estado de ejecuciones en tiempo real
- Gestión de aprobaciones inline

**Librería (`jaraba_agent_flows.libraries.yml`):**
```yaml
agent-flows:
  css:
    component:
      css/jaraba-agent-flows.css: {}
  js:
    js/agent-flows.js: { attributes: { defer: true } }
  dependencies:
    - ecosistema_jaraba_theme/slide-panel
    - ecosistema_jaraba_theme/premium-cards
    - core/drupal
    - core/once
    - core/drupalSettings
```

### 6.7 Hooks

```php
/**
 * @file
 * Hooks del módulo Agent Flows.
 *
 * ESTRUCTURA: Hooks de ciclo de vida de entidades y cron para
 * automatización de flujos de agentes IA.
 *
 * LÓGICA: Gestiona el registro automático de ejecuciones al crear/actualizar
 * flujos, el procesamiento de flujos programados vía cron y el envío
 * de notificaciones para aprobaciones pendientes.
 */

/**
 * Implements hook_entity_insert() para agent_flow_execution.
 *
 * Cuando se crea una nueva ejecución con status 'pending',
 * encola el procesamiento asíncrono del flujo.
 */
function jaraba_agent_flows_entity_insert(EntityInterface $entity): void {
  if ($entity->getEntityTypeId() === 'agent_flow_execution') {
    if ($entity->get('status')->value === 'pending') {
      \Drupal::service('jaraba_agent_flows.engine')->enqueue($entity);
    }
  }
}

/**
 * Implements hook_entity_update() para agent_flow_execution.
 *
 * Detecta transiciones de estado y ejecuta acciones correspondientes:
 * - waiting_approval → notifica al responsable
 * - completed/failed → registra métricas en FinOps
 */
function jaraba_agent_flows_entity_update(EntityInterface $entity): void {
  if ($entity->getEntityTypeId() === 'agent_flow_execution') {
    $oldStatus = $entity->original->get('status')->value ?? '';
    $newStatus = $entity->get('status')->value ?? '';
    if ($newStatus !== $oldStatus) {
      \Drupal::service('jaraba_agent_flows.engine')
        ->handleStatusTransition($entity, $oldStatus, $newStatus);
    }
  }
}

/**
 * Implements hook_cron().
 *
 * Procesa flujos con trigger_type = 'schedule' cuya expresión cron
 * coincide con el momento actual. Máximo 10 ejecuciones por ciclo
 * de cron para evitar timeouts.
 */
function jaraba_agent_flows_cron(): void {
  \Drupal::service('jaraba_agent_flows.trigger_manager')
    ->processScheduledFlows(10);
}

/**
 * Implements hook_mail().
 *
 * Templates de email para notificaciones de Agent Flows.
 */
function jaraba_agent_flows_mail(string $key, array &$message, array $params): void {
  switch ($key) {
    case 'approval_required':
      $message['subject'] = t('Aprobación requerida: @flow', [
        '@flow' => $params['flow_name'],
      ]);
      $message['body'][] = t('El flujo "@flow" está esperando tu aprobación en el paso "@step".', [
        '@flow' => $params['flow_name'],
        '@step' => $params['step_name'],
      ]);
      break;

    case 'execution_failed':
      $message['subject'] = t('Error en flujo: @flow', [
        '@flow' => $params['flow_name'],
      ]);
      $message['body'][] = t('La ejecución del flujo "@flow" ha fallado: @error', [
        '@flow' => $params['flow_name'],
        '@error' => $params['error_message'],
      ]);
      break;
  }
}
```

### 6.8 Archivos a Crear

```
web/modules/custom/jaraba_agent_flows/
├── jaraba_agent_flows.info.yml
├── jaraba_agent_flows.module
├── jaraba_agent_flows.install
├── jaraba_agent_flows.services.yml
├── jaraba_agent_flows.routing.yml
├── jaraba_agent_flows.links.menu.yml
├── jaraba_agent_flows.links.task.yml
├── jaraba_agent_flows.links.action.yml
├── jaraba_agent_flows.permissions.yml
├── jaraba_agent_flows.libraries.yml
├── config/
│   └── schema/jaraba_agent_flows.schema.yml
├── src/
│   ├── Entity/
│   │   ├── AgentFlow.php
│   │   ├── AgentFlowExecution.php
│   │   └── AgentFlowStepLog.php
│   ├── Controller/
│   │   ├── AgentFlowDashboardController.php
│   │   ├── AgentFlowDetailController.php
│   │   ├── AgentFlowApiController.php
│   │   └── AgentFlowWebhookController.php
│   ├── Service/
│   │   ├── AgentFlowEngineService.php
│   │   ├── AgentFlowLlmService.php
│   │   ├── AgentFlowTriggerService.php
│   │   ├── AgentFlowValidatorService.php
│   │   └── AgentFlowMetricsService.php
│   ├── Form/
│   │   ├── AgentFlowForm.php
│   │   └── AgentFlowSettingsForm.php
│   ├── Access/
│   │   └── AgentFlowAccessControlHandler.php
│   └── AgentFlowListBuilder.php
├── templates/
│   ├── agent-flow-dashboard.html.twig
│   ├── agent-flow-detail.html.twig
│   └── partials/
│       ├── _agent-flow-card.html.twig
│       ├── _agent-flow-execution-log.html.twig
│       ├── _agent-flow-stats.html.twig
│       └── _agent-flow-empty-state.html.twig
├── scss/
│   ├── main.scss
│   ├── _agent-flow-dashboard.scss
│   ├── _agent-flow-detail.scss
│   ├── _agent-flow-card.scss
│   └── _agent-flow-execution.scss
├── css/
│   └── jaraba-agent-flows.css
├── js/
│   └── agent-flows.js
└── package.json
```

### 6.9 Archivos a Modificar

| Archivo | Cambio |
|---------|--------|
| `ecosistema_jaraba_theme.theme` | Añadir theme suggestion `page__agent_flows` + body classes en `hook_preprocess_html()` |
| `ecosistema_jaraba_theme/templates/` | Crear `page--agent-flows.html.twig` con layout limpio |
| `ecosistema_jaraba_core.module` | Registrar feature flag `agent_flows_enabled` en plan validation |

### 6.10 SCSS: Directrices

```scss
// _agent-flow-card.scss
// Tarjeta premium para flujos de agentes IA.
// Usa el patrón glassmorphism del workflow premium-cards-pattern.md

.agent-flow-card {
  position: relative;
  overflow: hidden;
  padding: var(--ej-spacing-lg, 1.5rem);
  background: linear-gradient(135deg,
    rgba(255, 255, 255, 0.95) 0%,
    rgba(248, 250, 252, 0.9) 100%);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border-radius: var(--ej-border-radius-lg, 14px);
  border: 1px solid rgba(255, 255, 255, 0.8);
  box-shadow:
    0 4px 24px rgba(0, 0, 0, 0.04),
    0 1px 2px rgba(0, 0, 0, 0.02),
    inset 0 1px 0 rgba(255, 255, 255, 0.9);
  transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275),
              box-shadow 0.3s ease;

  // Efecto shine
  &::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 50%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
    transition: left 0.6s ease;
    pointer-events: none;
  }

  &:hover {
    transform: translateY(-6px) scale(1.02);
    box-shadow:
      0 20px 40px rgba(35, 61, 99, 0.12),
      0 8px 16px rgba(35, 61, 99, 0.08),
      inset 0 1px 0 rgba(255, 255, 255, 1);

    &::before {
      left: 150%;
    }
  }

  &__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: var(--ej-spacing-md, 1rem);
  }

  &__title {
    font-family: var(--ej-font-headings, 'Outfit', sans-serif);
    font-size: var(--ej-font-size-lg, 1.125rem);
    font-weight: 600;
    color: var(--ej-text-primary, #212121);
  }

  &__status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: var(--ej-border-radius-sm, 6px);
    font-size: var(--ej-font-size-xs, 0.75rem);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;

    &--active {
      background: rgba(67, 160, 71, 0.1);
      color: var(--ej-color-success, #43A047);
    }

    &--inactive {
      background: rgba(158, 158, 158, 0.1);
      color: var(--ej-text-muted, #9E9E9E);
    }

    &--error {
      background: rgba(229, 57, 53, 0.1);
      color: var(--ej-color-error, #E53935);
    }
  }
}
```

### 6.11 Verificación

- [ ] Las 3 entidades aparecen en `/admin/content/agent-flows`
- [ ] Field UI accesible en `/admin/structure/agent-flows/manage/fields`
- [ ] Dashboard frontend en `/agent-flows` con layout limpio (sin sidebar admin)
- [ ] Crear flujo vía slide-panel funciona correctamente
- [ ] Editar flujo existente vía slide-panel funciona
- [ ] Ejecutar flujo manualmente desde el dashboard
- [ ] API REST `/api/v1/agent-flows` devuelve JSON correcto filtrado por tenant
- [ ] API REST `/api/v1/agent-flows/{id}/execute` inicia ejecución
- [ ] Webhook endpoint recibe y valida HMAC
- [ ] Hook cron procesa flujos programados
- [ ] Notificación email enviada en aprobaciones pendientes
- [ ] Métricas de ejecución (tokens, coste, duración) registradas
- [ ] Body class `page-platform page-agent-flows` presente en `<body>`
- [ ] SCSS compila sin errores con `npm run build`
- [ ] Responsive: verificar en 375px, 768px, 1200px
- [ ] Textos traducibles vía `/admin/config/regional/translate`
- [ ] Permisos verificados: usuario sin `manage agent flows` no puede crear/editar
- [ ] Tenant isolation: un tenant no ve los flujos de otro tenant

---

## 7. FASE 2: PWA Mobile — Funcionalidad Offline (Doc 109)

### 7.1 Justificación

La PWA (Progressive Web App) habilita acceso offline crítico para usuarios rurales con conectividad intermitente (el target principal del ecosistema Jaraba). Con un Service Worker Workbox y sincronización background, los productores de AgroConecta pueden registrar productos, los profesionales de ServiciosConecta pueden revisar citas, y los buscadores de empleo pueden consultar ofertas — todo sin conexión.

**Objetivo:** 95% de funcionalidad offline, 30% de tasa de instalación, 40% opt-in push notifications.

**Reutilización:** 20% de Core (TenantContext para aislar datos offline por tenant).

### 7.2 Entidades

#### 7.2.1 Entidad `PendingSyncAction`

**Tipo:** ContentEntity
**ID:** `pending_sync_action`
**Base table:** `pending_sync_action`

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK autoincremental |
| `uuid` | uuid | ✅ | Identificador universal único |
| `tenant_id` | entity_reference (taxonomy_term) | ✅ | Aislamiento multi-tenant |
| `user_id` | entity_reference (user) | ✅ | Usuario que generó la acción offline |
| `action_type` | list_string | ✅ | Tipo: `create`, `update`, `delete` |
| `entity_type` | string(128) | ✅ | Tipo de entidad afectada (e.g., `product_agro`, `booking`) |
| `entity_id` | integer | ❌ | ID de la entidad si existe (NULL para creates) |
| `payload` | text_long | ✅ | Datos de la acción en JSON |
| `retry_count` | integer | ✅ | Número de intentos de sincronización. Default: 0 |
| `last_error` | text_long | ❌ | Último error de sincronización |
| `synced_at` | timestamp | ❌ | Momento de sincronización exitosa (NULL = pendiente) |
| `created` | created | ✅ | Timestamp de creación offline |

#### 7.2.2 Entidad `PushSubscription`

**Tipo:** ContentEntity
**ID:** `push_subscription`
**Base table:** `push_subscription`

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK autoincremental |
| `uuid` | uuid | ✅ | Identificador universal único |
| `user_id` | entity_reference (user) | ✅ | Usuario suscrito. INDEX |
| `tenant_id` | entity_reference (taxonomy_term) | ✅ | Aislamiento multi-tenant |
| `endpoint` | string(1024) | ✅ | URL del endpoint de push del navegador |
| `p256dh_key` | string(512) | ✅ | Clave pública para cifrado ECDH |
| `auth_key` | string(512) | ✅ | Token de autenticación para push |
| `device_type` | list_string | ✅ | Tipo: `desktop`, `mobile`, `tablet` |
| `browser` | string(128) | ❌ | Navegador: `chrome`, `firefox`, `safari`, `edge` |
| `preferences` | map (serialized) | ❌ | Preferencias de notificación: `{orders: true, jobs: true, promotions: false}` |
| `is_active` | boolean | ✅ | Estado activo/inactivo. Default: TRUE |
| `last_used_at` | timestamp | ❌ | Última notificación enviada |
| `created` | created | ✅ | Timestamp de suscripción |

### 7.3 Services

| Service | Clase | Métodos Clave | Descripción |
|---------|-------|---------------|-------------|
| `jaraba_pwa.sync_manager` | `PwaSyncManagerService` | `processPendingActions()`, `resolveConflict()`, `getDelta()` | Gestiona la sincronización bidireccional entre el navegador (IndexedDB) y el servidor. Aplica estrategia Last-Write-Wins con detección de conflictos |
| `jaraba_pwa.push_service` | `PwaPushNotificationService` | `subscribe()`, `unsubscribe()`, `sendNotification()`, `sendBatch()` | Gestiona suscripciones Web Push y envía notificaciones usando la API Web Push con claves VAPID |
| `jaraba_pwa.manifest_generator` | `PwaManifestService` | `generateManifest()`, `generateServiceWorker()` | Genera el `manifest.json` dinámico con branding del tenant y el Service Worker con estrategias de caché Workbox |
| `jaraba_pwa.offline_data` | `PwaOfflineDataService` | `getOfflineManifest()`, `getEntityDelta()` | Prepara los datos que deben estar disponibles offline: productos, citas, ofertas de empleo, perfil del usuario |

### 7.4 Controllers

| Controller | Clase | Rutas | Descripción |
|------------|-------|-------|-------------|
| Manifest | `PwaManifestController` | `/manifest.json`, `/sw.js` | Genera manifest y service worker dinámicos por tenant |
| Push API | `PwaPushApiController` | `/api/v1/push/*` | Endpoints para suscripción, desuscripción y preferencias |
| Sync API | `PwaSyncApiController` | `/api/v1/sync/*` | Endpoints para batch sync, delta y resolución de conflictos |
| Offline API | `PwaOfflineApiController` | `/api/v1/offline/*` | Manifest de precaché y deltas incrementales |

### 7.5 Templates y Parciales Twig

| Template | Archivo | Propósito |
|----------|---------|-----------|
| Parcial: install prompt | `partials/_pwa-install-prompt.html.twig` | Banner de instalación personalizado con branding del tenant |
| Parcial: offline indicator | `partials/_pwa-offline-indicator.html.twig` | Indicador visual de estado offline/online en el header |
| Parcial: push opt-in | `partials/_pwa-push-optin.html.twig` | Modal de opt-in para notificaciones push con explicación de beneficios |
| Parcial: sync status | `partials/_pwa-sync-status.html.twig` | Badge con número de acciones pendientes de sincronizar |

### 7.6 Frontend Assets

**Service Worker (`js/service-worker.js`):**
- Estrategias Workbox: Cache First para shell, Network First para API (3s timeout), Stale While Revalidate para imágenes
- Background Sync para acciones offline
- Push event handler para notificaciones

**JavaScript (`js/pwa-manager.js`):**
- Registro del Service Worker
- Gestión de IndexedDB vía Dexie.js
- Lógica de sincronización con cola de prioridad
- Detección de estado online/offline
- Install prompt personalizado

### 7.7 Hooks

```php
/**
 * Implements hook_cron().
 *
 * Limpia acciones de sync ya procesadas (synced_at no NULL)
 * con más de 30 días de antigüedad para mantener la tabla limpia.
 * También limpia suscripciones push inactivas > 90 días.
 */
function jaraba_pwa_cron(): void {
  \Drupal::service('jaraba_pwa.sync_manager')->cleanupProcessedActions(30);
  \Drupal::service('jaraba_pwa.push_service')->cleanupInactiveSubscriptions(90);
}
```

### 7.8 Archivos a Crear

```
web/modules/custom/jaraba_pwa/
├── jaraba_pwa.info.yml
├── jaraba_pwa.module
├── jaraba_pwa.install
├── jaraba_pwa.services.yml
├── jaraba_pwa.routing.yml
├── jaraba_pwa.links.menu.yml
├── jaraba_pwa.permissions.yml
├── jaraba_pwa.libraries.yml
├── config/
│   ├── install/jaraba_pwa.settings.yml
│   └── schema/jaraba_pwa.schema.yml
├── src/
│   ├── Entity/
│   │   ├── PendingSyncAction.php
│   │   └── PushSubscription.php
│   ├── Controller/
│   │   ├── PwaManifestController.php
│   │   ├── PwaPushApiController.php
│   │   ├── PwaSyncApiController.php
│   │   └── PwaOfflineApiController.php
│   ├── Service/
│   │   ├── PwaSyncManagerService.php
│   │   ├── PwaPushNotificationService.php
│   │   ├── PwaManifestService.php
│   │   └── PwaOfflineDataService.php
│   ├── Form/
│   │   └── PwaSettingsForm.php
│   └── Access/
│       └── PendingSyncActionAccessControlHandler.php
├── templates/partials/
│   ├── _pwa-install-prompt.html.twig
│   ├── _pwa-offline-indicator.html.twig
│   ├── _pwa-push-optin.html.twig
│   └── _pwa-sync-status.html.twig
├── scss/
│   ├── main.scss
│   ├── _pwa-install-prompt.scss
│   ├── _pwa-offline-indicator.scss
│   └── _pwa-push-optin.scss
├── css/jaraba-pwa.css
├── js/
│   ├── pwa-manager.js
│   └── service-worker.js
└── package.json
```

### 7.9 SCSS: Directrices

Todos los componentes PWA usan tokens inyectables. Ejemplo del indicador offline:

```scss
// _pwa-offline-indicator.scss
.pwa-offline-indicator {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  background: var(--ej-color-warning, #FFA000);
  color: #FFFFFF;
  font-size: var(--ej-font-size-sm, 0.875rem);
  font-weight: 600;
  border-radius: 0 0 var(--ej-border-radius-sm, 6px) var(--ej-border-radius-sm, 6px);
  transition: var(--ej-transition, all 250ms ease);
  z-index: 1030;

  &--online {
    background: var(--ej-color-success, #43A047);
    transform: translateY(-100%);
    opacity: 0;
  }

  &__dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: currentColor;
    animation: pwa-pulse 2s ease-in-out infinite;
  }
}

@keyframes pwa-pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.4; }
}
```

### 7.10 Verificación

- [ ] `manifest.json` accesible y personalizado por tenant
- [ ] Service Worker registrado correctamente
- [ ] Instalación PWA funciona en Chrome/Edge móvil
- [ ] Modo offline muestra datos cacheados
- [ ] Acciones offline se encolan en IndexedDB
- [ ] Sincronización al reconectar funciona
- [ ] Push notifications llegan en Chrome/Firefox
- [ ] Indicador offline/online visible en header
- [ ] Entidades `pending_sync_action` y `push_subscription` en admin

---

## 8. FASE 3: Onboarding Product-Led — Gamificación (Doc 110)

### 8.1 Justificación

El onboarding self-service reduce el Time To Value a < 5 minutos y la dependencia de soporte humano en un 50%. Usa tours interactivos (Shepherd.js), checklists con progreso visual y gamificación con badges para guiar al usuario hasta su "Aha! moment" de forma autónoma. Los templates de onboarding son configurables por rol, vertical y plan — sin hardcodear flujos.

**Reutilización:** 35% de `jaraba_journey` (engine de estados, definición de avatares, sistema de progresión).

### 8.2 Entidades

#### 8.2.1 Entidad `OnboardingTemplate`

**Tipo:** ContentEntity
**ID:** `onboarding_template`
**Base table:** `onboarding_template`

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK autoincremental |
| `uuid` | uuid | ✅ | UUID |
| `name` | string(255) | ✅ | Nombre descriptivo: "Onboarding Productor AgroConecta" |
| `role` | list_string | ✅ | Rol destino: `job_seeker`, `recruiter`, `entrepreneur`, `producer`, `merchant`, `professional`, `client` |
| `vertical` | list_string | ✅ | Vertical: `empleabilidad`, `emprendimiento`, `agroconecta`, `comercioconecta`, `serviciosconecta` |
| `plan_tier` | list_string | ✅ | Nivel de plan: `basico`, `profesional`, `enterprise` |
| `steps` | text_long | ✅ | Array JSON de pasos. Cada paso: `{id, title, description, type: task|tour|video|link, completion_condition, is_aha_moment, order}` |
| `estimated_time_min` | integer | ✅ | Tiempo estimado de completar (minutos). Se muestra al usuario |
| `is_active` | boolean | ✅ | Default: TRUE |
| `version` | integer | ✅ | Para A/B testing entre versiones del onboarding |
| `created` | created | ✅ | Timestamp |
| `changed` | changed | ✅ | Timestamp |

#### 8.2.2 Entidad `UserOnboardingProgress`

**Tipo:** ContentEntity
**ID:** `user_onboarding_progress`
**Base table:** `user_onboarding_progress`

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK autoincremental |
| `uuid` | uuid | ✅ | UUID |
| `user_id` | entity_reference (user) | ✅ | Usuario. UNIQUE INDEX con template_id |
| `tenant_id` | entity_reference (taxonomy_term) | ✅ | Aislamiento multi-tenant |
| `template_id` | entity_reference (onboarding_template) | ✅ | Template asignado |
| `status` | list_string | ✅ | Estado: `not_started`, `in_progress`, `completed`, `dismissed`. Default: `not_started` |
| `steps_completed` | text_long | ❌ | JSON array de IDs de pasos completados |
| `steps_skipped` | text_long | ❌ | JSON array de IDs de pasos saltados |
| `current_step` | string(128) | ❌ | ID del paso actual |
| `completion_percentage` | integer | ✅ | Porcentaje 0-100. Calculado |
| `aha_moment_reached` | boolean | ✅ | Si el usuario alcanzó el momento "Aha!". Default: FALSE |
| `aha_moment_at` | timestamp | ❌ | Timestamp del Aha! moment |
| `total_time_spent_min` | integer | ❌ | Tiempo total invertido en minutos |
| `started_at` | timestamp | ❌ | Inicio del onboarding |
| `completed_at` | timestamp | ❌ | Finalización del onboarding |
| `created` | created | ✅ | Timestamp |

### 8.3 Services

| Service | Clase | Métodos Clave | Descripción |
|---------|-------|---------------|-------------|
| `jaraba_onboarding.progress` | `OnboardingProgressService` | `getOrCreateProgress()`, `completeStep()`, `skipStep()`, `dismiss()`, `restart()` | Gestiona el progreso del usuario. Calcula `completion_percentage`, detecta `aha_moment`, genera eventos de gamificación |
| `jaraba_onboarding.template_resolver` | `OnboardingTemplateResolverService` | `resolveTemplate()`, `getActiveTemplates()` | Resuelve el template correcto según rol, vertical y plan del usuario. Soporta A/B testing por `version` |
| `jaraba_onboarding.tour_manager` | `OnboardingTourManagerService` | `getTourDefinition()`, `generateShepherdConfig()` | Genera la configuración de Shepherd.js para tours interactivos basados en los pasos del template |
| `jaraba_onboarding.gamification` | `OnboardingGamificationService` | `awardBadge()`, `calculateXP()`, `checkMilestone()` | Sistema de badges y XP. Badges: first_steps (10pts), profile_pro (25pts), quick_starter (50pts), aha_achieved (100pts), all_done (200pts) |

### 8.4 Controllers

| Controller | Clase | Rutas | Descripción |
|------------|-------|-------|-------------|
| Widget | `OnboardingWidgetController` | `/onboarding/widget` | Renderiza el widget de checklist inline vía AJAX |
| API | `OnboardingApiController` | `/api/v1/onboarding/*` | REST API: progreso, completar/saltar paso, tours, help contextual |
| Admin | `OnboardingAdminController` | `/admin/content/onboarding-templates` | Gestión de templates de onboarding |

### 8.5 Templates y Parciales Twig

| Template | Archivo | Propósito |
|----------|---------|-----------|
| Parcial: checklist widget | `partials/_onboarding-checklist.html.twig` | Widget de progreso con pasos, barra visual, badge de XP |
| Parcial: tour step | `partials/_onboarding-tour-step.html.twig` | Tooltip de Shepherd.js personalizado con branding |
| Parcial: celebration | `partials/_onboarding-celebration.html.twig` | Animación de confetti + badge notification al completar milestone |
| Parcial: contextual help | `partials/_onboarding-contextual-help.html.twig` | Popover de ayuda contextual por página |

### 8.6 Frontend Assets

**JavaScript:**
- `js/onboarding-checklist.js`: Widget interactivo con progreso animado
- `js/onboarding-tours.js`: Wrapper de Shepherd.js con configuración dinámica
- `js/onboarding-celebrations.js`: Canvas-confetti para animaciones de celebración

**Dependencias externas (vía CDN o npm):**
- Shepherd.js ^12.0: Tours interactivos con highlights
- canvas-confetti ^1.9: Animaciones de celebración

### 8.7 Hooks

```php
/**
 * Implements hook_user_login().
 *
 * Al iniciar sesión, verifica si el usuario tiene onboarding pendiente.
 * Si no tiene progreso asignado, resuelve el template correcto y lo crea.
 */
function jaraba_onboarding_user_login(UserInterface $account): void {
  \Drupal::service('jaraba_onboarding.progress')
    ->getOrCreateProgress($account);
}

/**
 * Implements hook_entity_insert().
 *
 * Al crear ciertas entidades (producto, candidatura, perfil), verifica
 * si completa un paso del onboarding y actualiza el progreso.
 */
function jaraba_onboarding_entity_insert(EntityInterface $entity): void {
  $trackable = ['product_agro', 'job_application', 'entrepreneur_profile', 'booking'];
  if (in_array($entity->getEntityTypeId(), $trackable)) {
    \Drupal::service('jaraba_onboarding.progress')
      ->checkEntityCompletion($entity);
  }
}

/**
 * Implements hook_cron().
 *
 * Envía recordatorios de onboarding a usuarios que llevan >48h
 * sin completar el onboarding (status = in_progress).
 */
function jaraba_onboarding_cron(): void {
  \Drupal::service('jaraba_onboarding.progress')
    ->sendReminders(48);
}
```

### 8.8 Archivos a Crear

```
web/modules/custom/jaraba_onboarding/
├── jaraba_onboarding.info.yml
├── jaraba_onboarding.module
├── jaraba_onboarding.install
├── jaraba_onboarding.services.yml
├── jaraba_onboarding.routing.yml
├── jaraba_onboarding.links.menu.yml
├── jaraba_onboarding.links.task.yml
├── jaraba_onboarding.links.action.yml
├── jaraba_onboarding.permissions.yml
├── jaraba_onboarding.libraries.yml
├── src/Entity/{OnboardingTemplate,UserOnboardingProgress}.php
├── src/Controller/{OnboardingWidgetController,OnboardingApiController,OnboardingAdminController}.php
├── src/Service/{OnboardingProgressService,OnboardingTemplateResolverService,OnboardingTourManagerService,OnboardingGamificationService}.php
├── src/Form/{OnboardingTemplateForm,OnboardingSettingsForm}.php
├── src/Access/OnboardingAccessControlHandler.php
├── src/OnboardingTemplateListBuilder.php
├── templates/partials/{_onboarding-checklist,_onboarding-tour-step,_onboarding-celebration,_onboarding-contextual-help}.html.twig
├── scss/{main,_onboarding-checklist,_onboarding-celebration}.scss
├── css/jaraba-onboarding.css
├── js/{onboarding-checklist,onboarding-tours,onboarding-celebrations}.js
└── package.json
```

### 8.9 SCSS: Directrices

El widget de checklist sigue el patrón premium con glassmorphism. Usa tokens inyectables para que se integre visualmente con cualquier branding de tenant:

```scss
// _onboarding-checklist.scss
.onboarding-checklist {
  position: fixed;
  bottom: var(--ej-spacing-lg, 1.5rem);
  right: var(--ej-spacing-lg, 1.5rem);
  width: 360px;
  max-height: 480px;
  background: var(--ej-bg-surface, #FFFFFF);
  border-radius: var(--ej-border-radius-lg, 14px);
  box-shadow: var(--ej-shadow-lg, 0 10px 15px rgba(0, 0, 0, 0.1));
  z-index: var(--ej-z-modal, 1050);
  overflow: hidden;

  &__progress-bar {
    height: 4px;
    background: var(--ej-gray-200, #EEEEEE);
    border-radius: 2px;

    &-fill {
      height: 100%;
      background: var(--ej-color-primary, #FF8C42);
      border-radius: 2px;
      transition: width 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
  }

  &__step {
    display: flex;
    align-items: flex-start;
    gap: var(--ej-spacing-sm, 0.5rem);
    padding: var(--ej-spacing-sm, 0.5rem) var(--ej-spacing-md, 1rem);
    cursor: pointer;
    transition: var(--ej-transition-fast, all 150ms ease);

    &:hover {
      background: var(--ej-gray-50, #FAFAFA);
    }

    &--completed {
      opacity: 0.6;
      text-decoration: line-through;
    }

    &--current {
      background: rgba(255, 140, 66, 0.05);
      border-left: 3px solid var(--ej-color-primary, #FF8C42);
    }
  }

  // Responsive: en móvil ocupa todo el ancho
  @media (max-width: 480px) {
    width: 100%;
    right: 0;
    bottom: 0;
    border-radius: var(--ej-border-radius-lg, 14px) var(--ej-border-radius-lg, 14px) 0 0;
  }
}
```

### 8.10 Verificación

- [ ] Template de onboarding creado desde admin
- [ ] Al primer login, el widget de checklist aparece
- [ ] Completar un paso actualiza la barra de progreso
- [ ] Saltar paso funciona y se registra
- [ ] Tour Shepherd.js se activa y recorre los elementos correctos
- [ ] Confetti se muestra al alcanzar el Aha! moment
- [ ] Badge de gamificación otorgado correctamente
- [ ] Recordatorio email a las 48h para usuarios incompletos
- [ ] Widget responsive en móvil (full-width)
- [ ] Textos traducibles vía `/admin/config/regional/translate`

---

## 9. FASE 4: Usage-Based Pricing — Precios por Uso (Doc 111)

### 9.1 Justificación

Complementa el modelo de suscripción fija con facturación basada en uso real. Habilita modelos híbridos (suscripción + overage), créditos prepago y revenue share sobre GMV. Se integra directamente con Stripe Billing y con el FOC (Centro de Operaciones Financieras) existente.

**Reutilización:** 50% de `jaraba_foc` (FinOpsTrackingService para métricas, Stripe Connect para cobros) y Core (JarabaStripeConnect).

### 9.2 Entidades

#### 9.2.1 Entidad `UsageEvent`

**Tipo:** ContentEntity
**ID:** `usage_event`
**Base table:** `usage_event`

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK autoincremental |
| `uuid` | uuid | ✅ | UUID |
| `tenant_id` | entity_reference (taxonomy_term) | ✅ | Aislamiento multi-tenant. INDEX |
| `metric_type` | list_string | ✅ | Métrica: `transactions`, `gmv`, `job_postings`, `applications`, `ai_tokens`, `storage_gb`, `api_calls`, `active_users`. INDEX |
| `quantity` | decimal(12,4) | ✅ | Cantidad consumida |
| `unit` | string(64) | ✅ | Unidad: `count`, `euros`, `tokens`, `gb`, `calls`, `users` |
| `metadata` | map (serialized) | ❌ | Datos adicionales del evento en JSON: `{entity_type, entity_id, details}` |
| `idempotency_key` | string(255) | ✅ | Clave única para deduplicación. UNIQUE INDEX |
| `recorded_at` | timestamp | ✅ | Momento exacto del evento |
| `billed` | boolean | ✅ | Si ya fue incluido en una factura. Default: FALSE. INDEX |
| `billing_period` | string(7) | ✅ | Período de facturación: `YYYY-MM` (e.g., `2026-02`) |
| `created` | created | ✅ | Timestamp |

#### 9.2.2 Entidad `UsageAggregate`

**Tipo:** ContentEntity
**ID:** `usage_aggregate`
**Base table:** `usage_aggregate`

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK autoincremental |
| `tenant_id` | entity_reference (taxonomy_term) | ✅ | INDEX |
| `metric_type` | list_string | ✅ | Mismas opciones que UsageEvent. INDEX |
| `period_type` | list_string | ✅ | Granularidad: `hourly`, `daily`, `monthly`. INDEX |
| `period_start` | timestamp | ✅ | Inicio del período |
| `period_end` | timestamp | ✅ | Fin del período |
| `total_quantity` | decimal(14,4) | ✅ | Total acumulado en el período |
| `event_count` | integer | ✅ | Número de eventos en el período |
| `computed_cost` | decimal(10,2) | ❌ | Coste calculado según PricingRule activa |
| `synced_to_stripe` | boolean | ✅ | Si fue sincronizado con Stripe Usage Records. Default: FALSE |
| `created` | created | ✅ | Timestamp |

#### 9.2.3 Entidad `PricingRule`

**Tipo:** ContentEntity
**ID:** `pricing_rule`
**Base table:** `pricing_rule`

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK autoincremental |
| `uuid` | uuid | ✅ | UUID |
| `plan_id` | entity_reference (saas_plan) | ✅ | FK al plan SaaS. INDEX |
| `metric_type` | list_string | ✅ | Métrica a la que aplica esta regla |
| `pricing_model` | list_string | ✅ | Modelo: `flat`, `overage`, `tiered`, `credits`, `revenue_share` |
| `included_quantity` | decimal(12,4) | ✅ | Cantidad incluida en la suscripción base. 0 = todo es facturado |
| `tiers` | text_long | ❌ | JSON para modelos tiered/overage: `[{from: 0, to: 100, unit_price: 0.05}, {from: 101, to: null, unit_price: 0.03}]` |
| `currency` | string(3) | ✅ | Código ISO: `EUR`, `USD`. Default: `EUR` |
| `is_active` | boolean | ✅ | Default: TRUE |
| `created` | created | ✅ | Timestamp |
| `changed` | changed | ✅ | Timestamp |

### 9.3 Services

| Service | Clase | Métodos Clave | Descripción |
|---------|-------|---------------|-------------|
| `jaraba_usage_billing.metering` | `UsageMeteringService` | `recordEvent()`, `recordBatch()`, `deduplicate()` | Registra eventos de uso con deduplicación por `idempotency_key`. Usa Redis para contadores en tiempo real antes de persistir |
| `jaraba_usage_billing.aggregator` | `UsageAggregatorService` | `aggregateHourly()`, `aggregateDaily()`, `aggregateMonthly()` | Agrega eventos por período. Se ejecuta vía `hook_cron` cada hora para hourly, diario para daily |
| `jaraba_usage_billing.pricing_engine` | `UsagePricingEngineService` | `calculateCost()`, `getEstimate()`, `getCurrentUsage()` | Motor de cálculo de costes según PricingRule activa. Soporta los 5 modelos de pricing |
| `jaraba_usage_billing.stripe_sync` | `UsageStripeSyncService` | `syncUsageRecords()`, `createInvoiceItems()` | Sincroniza agregados con Stripe Usage Records API para facturación automática |
| `jaraba_usage_billing.alerts` | `UsageAlertService` | `checkThresholds()`, `sendAlert()` | Envía alertas cuando el uso alcanza 80% y 100% de lo incluido |

### 9.4 Controllers

| Controller | Clase | Rutas |
|------------|-------|-------|
| Dashboard | `UsageBillingDashboardController` | `/usage-billing` |
| API Usage | `UsageBillingApiController` | `/api/v1/usage/*` |
| API Pricing | `UsagePricingApiController` | `/api/v1/pricing/*` |

### 9.5 Templates y Parciales Twig

| Template | Archivo | Propósito |
|----------|---------|-----------|
| Página dashboard | `page--usage-billing.html.twig` | Layout limpio con medidores de uso |
| Parcial: usage meter | `partials/_usage-meter.html.twig` | Barra de progreso con uso actual vs incluido |
| Parcial: cost projection | `partials/_cost-projection.html.twig` | Gráfico de proyección de coste mensual |
| Parcial: usage chart | `partials/_usage-chart.html.twig` | Gráfico temporal de uso diario/semanal |
| Parcial: alert config | `partials/_usage-alert-config.html.twig` | Configuración de umbrales de alerta |

### 9.6 Frontend Assets

- `js/usage-billing.js`: Gráficos con Chart.js/Recharts, polling de uso en tiempo real
- SCSS con tokens inyectables para colores de progreso (verde → amarillo → rojo)

### 9.7 Hooks

```php
/**
 * Implements hook_cron().
 *
 * - Cada hora: agregar eventos → usage_aggregate (hourly)
 * - Diariamente: agregar hourly → daily, sincronizar con Stripe
 * - Verificar umbrales de uso y enviar alertas
 */
function jaraba_usage_billing_cron(): void {
  $aggregator = \Drupal::service('jaraba_usage_billing.aggregator');
  $aggregator->aggregateHourly();

  // Solo una vez al día (verificar con state API)
  $lastDaily = \Drupal::state()->get('jaraba_usage_billing.last_daily_aggregate', 0);
  if (time() - $lastDaily > 86400) {
    $aggregator->aggregateDaily();
    \Drupal::service('jaraba_usage_billing.stripe_sync')->syncUsageRecords();
    \Drupal::state()->set('jaraba_usage_billing.last_daily_aggregate', time());
  }

  // Verificar alertas de umbral
  \Drupal::service('jaraba_usage_billing.alerts')->checkThresholds();
}
```

### 9.8 Archivos a Crear

Estructura idéntica al patrón estándar con 3 entidades, 5 services, 3 controllers, templates con parciales, SCSS y package.json.

### 9.9 SCSS: Directrices

El medidor de uso usa colores dinámicos según el porcentaje:
```scss
.usage-meter {
  &__bar {
    height: 8px;
    border-radius: 4px;
    background: var(--ej-gray-200, #EEEEEE);

    &-fill {
      height: 100%;
      border-radius: 4px;
      transition: width 0.8s ease, background 0.4s ease;

      &--low { background: var(--ej-color-success, #43A047); }
      &--medium { background: var(--ej-color-warning, #FFA000); }
      &--high { background: var(--ej-color-error, #E53935); }
    }
  }
}
```

### 9.10 Verificación

- [ ] Registrar evento de uso vía API y verificar en admin
- [ ] Deduplicación por `idempotency_key` funciona
- [ ] Agregación horaria/diaria genera registros correctos
- [ ] Dashboard muestra uso actual vs incluido
- [ ] Alerta email enviada al alcanzar 80%
- [ ] Sincronización con Stripe Usage Records funciona
- [ ] PricingRule configurable por plan sin tocar código
- [ ] Cálculo de coste correcto para cada modelo de pricing

---

## 10. FASE 5: Integration Marketplace & Developer Portal (Doc 112)

### 10.1 Justificación

El Marketplace de Integraciones transforma el Ecosistema Jaraba en una **plataforma abierta y extensible**. Permite a terceros conectar herramientas (Google Sheets, Mailchimp, WhatsApp Business, Holded, LinkedIn) vía OAuth2, webhooks y un servidor MCP para agentes IA externos. El Developer Portal con documentación OpenAPI 3.0 atrae desarrolladores que amplían el ecosistema sin coste de desarrollo interno.

**Objetivo:** 50+ conectores en Y1, 100+ desarrolladores registrados en 6 meses, 1M+ llamadas API/mes.

**Reutilización:** 25% de Core (TenantManager para aislar instalaciones, RBAC para permisos).

### 10.2 Entidades

#### 10.2.1 Entidad `Connector`

**Tipo:** ContentEntity | **ID:** `connector` | **Base table:** `connector`

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK |
| `uuid` | uuid | ✅ | UUID |
| `name` | string(255) | ✅ | Nombre: "Google Sheets", "Mailchimp" |
| `slug` | string(128) | ✅ | Identificador URL-safe. UNIQUE INDEX |
| `description` | text_long | ✅ | Descripción funcional del conector |
| `category` | list_string | ✅ | Categoría: `crm`, `erp`, `marketing`, `payments`, `hr`, `analytics`, `messaging`, `productivity` |
| `logo_url` | string(512) | ❌ | URL del logo del servicio externo |
| `auth_type` | list_string | ✅ | Tipo de auth: `oauth2`, `api_key`, `basic`, `webhook` |
| `auth_config` | map (serialized) | ❌ | Config de auth: `{client_id, client_secret, scopes, authorize_url, token_url}` |
| `endpoints` | text_long | ✅ | JSON: endpoints disponibles con métodos, parámetros y mappings |
| `webhooks` | text_long | ❌ | JSON: eventos webhook que emite/recibe |
| `pricing_tier` | list_string | ✅ | Plan mínimo requerido: `basico`, `profesional`, `enterprise` |
| `status` | list_string | ✅ | Estado: `draft`, `published`, `deprecated`. Default: `draft` |
| `install_count` | integer | ✅ | Instalaciones totales. Default: 0 |
| `rating` | decimal(3,2) | ❌ | Valoración media 1.0-5.0 |
| `partner_id` | entity_reference (user) | ❌ | Desarrollador que creó el conector (NULL = oficial) |
| `created` | created | ✅ | Timestamp |

#### 10.2.2 Entidad `ConnectorInstallation`

**Tipo:** ContentEntity | **ID:** `connector_installation` | **Base table:** `connector_installation`

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK |
| `uuid` | uuid | ✅ | UUID |
| `connector_id` | entity_reference (connector) | ✅ | FK al conector. INDEX |
| `tenant_id` | entity_reference (taxonomy_term) | ✅ | Aislamiento multi-tenant. INDEX |
| `status` | list_string | ✅ | Estado: `active`, `inactive`, `error`, `pending_auth` |
| `credentials` | text_long | ❌ | Credenciales cifradas (AES-256-GCM). NUNCA en texto plano |
| `config` | map (serialized) | ❌ | Configuración específica del conector para este tenant |
| `last_sync_at` | timestamp | ❌ | Última sincronización exitosa |
| `error_message` | text_long | ❌ | Último error si status = `error` |
| `installed_at` | timestamp | ✅ | Momento de instalación |

#### 10.2.3 Entidad `OauthClient`

**Tipo:** ContentEntity | **ID:** `oauth_client` | **Base table:** `oauth_client`

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK |
| `client_id` | string(128) | ✅ | ID público del cliente OAuth2. UNIQUE INDEX |
| `client_secret` | string(255) | ✅ | Secreto hasheado (bcrypt) |
| `name` | string(255) | ✅ | Nombre de la aplicación |
| `description` | text_long | ❌ | Descripción de la aplicación |
| `redirect_uris` | text_long | ✅ | JSON array de URIs de redirección autorizadas |
| `scopes` | text_long | ✅ | JSON array de scopes solicitados |
| `grant_types` | text_long | ✅ | JSON array: `["authorization_code", "client_credentials"]` |
| `developer_id` | entity_reference (user) | ✅ | Desarrollador propietario |
| `status` | list_string | ✅ | Estado: `active`, `suspended`, `revoked` |
| `rate_limit` | integer | ✅ | Límite de requests por hora. Default: 1000 |
| `created` | created | ✅ | Timestamp |

#### 10.2.4 Entidad `WebhookSubscription`

**Tipo:** ContentEntity | **ID:** `webhook_subscription` | **Base table:** `webhook_subscription`

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK |
| `uuid` | uuid | ✅ | UUID |
| `client_id` | entity_reference (oauth_client) | ✅ | FK a la app suscrita |
| `tenant_id` | entity_reference (taxonomy_term) | ✅ | Aislamiento multi-tenant |
| `event_types` | text_long | ✅ | JSON array de tipos de evento: `["order.created", "product.updated"]` |
| `target_url` | string(1024) | ✅ | URL destino HTTPS para entregar eventos |
| `secret` | string(255) | ✅ | Secreto HMAC para firmar payloads |
| `status` | list_string | ✅ | Estado: `active`, `inactive`, `failing`. Default: `active` |
| `failure_count` | integer | ✅ | Fallos consecutivos. Default: 0. > 10 → `failing` |
| `last_triggered_at` | timestamp | ❌ | Última entrega exitosa |
| `created` | created | ✅ | Timestamp |

### 10.3 Services

| Service | Clase | Métodos Clave | Descripción |
|---------|-------|---------------|-------------|
| `jaraba_integrations.connector_registry` | `ConnectorRegistryService` | `install()`, `uninstall()`, `getInstalled()`, `testConnection()` | Gestiona el ciclo de vida de conectores: instalación, configuración, testing, desinstalación. Valida plan tier |
| `jaraba_integrations.oauth_server` | `OAuthServerService` | `authorize()`, `issueToken()`, `revokeToken()`, `validateToken()` | Servidor OAuth2 completo con authorization_code y client_credentials flows |
| `jaraba_integrations.webhook_dispatcher` | `WebhookDispatcherService` | `dispatch()`, `retryFailed()`, `signPayload()` | Despacha eventos webhook con firma HMAC, reintentos exponenciales y circuit breaker |
| `jaraba_integrations.developer_portal` | `DeveloperPortalService` | `registerApp()`, `rotateSecret()`, `getAnalytics()` | Gestión de aplicaciones de desarrolladores, rotación de secrets, métricas de uso |
| `jaraba_integrations.rate_limiter` | `IntegrationRateLimiterService` | `checkLimit()`, `recordRequest()` | Rate limiting por client_id + tenant_id con ventana deslizante en Redis |

### 10.4 Controllers

| Controller | Clase | Rutas |
|------------|-------|-------|
| Marketplace | `IntegrationMarketplaceController` | `/integrations` |
| Connector Detail | `ConnectorDetailController` | `/integrations/{slug}` |
| OAuth | `OAuthController` | `/oauth/authorize`, `/oauth/token`, `/oauth/revoke` |
| Webhook API | `WebhookApiController` | `/api/v1/webhooks/*` |
| Developer API | `DeveloperApiController` | `/api/v1/developers/*`, `/api/v1/apps/*` |

### 10.5 Templates y Parciales Twig

| Template | Archivo | Propósito |
|----------|---------|-----------|
| Página marketplace | `page--integrations.html.twig` | Layout limpio con grid de conectores |
| Marketplace content | `integration-marketplace.html.twig` | Catálogo con búsqueda, filtros por categoría, cards |
| Detalle conector | `connector-detail.html.twig` | Descripción, screenshots, instalación, config |
| Parcial: connector card | `partials/_connector-card.html.twig` | Card premium con logo, rating, install count, categoría |
| Parcial: install wizard | `partials/_connector-install-wizard.html.twig` | Wizard paso a paso de auth + config en slide-panel |
| Parcial: connection status | `partials/_connector-status.html.twig` | Badge de estado de conexión con última sync |

### 10.6 Frontend Assets

- `js/integration-marketplace.js`: Filtrado dinámico, búsqueda, instalación vía AJAX
- `js/oauth-flow.js`: Gestión del flujo OAuth2 (popup, callback, token storage)

### 10.7 Hooks

```php
/**
 * Implements hook_entity_insert/update/delete().
 *
 * Despacha webhooks cuando se crean/modifican/eliminan entidades
 * que tienen suscriptores webhook activos. Ejemplos:
 * - order.created → ComercioConecta/AgroConecta
 * - product.updated → catálogo
 * - application.created → Empleabilidad
 */
function jaraba_integrations_entity_insert(EntityInterface $entity): void {
  $dispatchable = [
    'commerce_order' => 'order.created',
    'product_agro' => 'product.created',
    'job_application' => 'application.created',
    'booking' => 'booking.created',
  ];
  if (isset($dispatchable[$entity->getEntityTypeId()])) {
    \Drupal::service('jaraba_integrations.webhook_dispatcher')
      ->dispatch($dispatchable[$entity->getEntityTypeId()], $entity);
  }
}

/**
 * Implements hook_cron().
 *
 * - Reintentar webhooks fallidos (max 10 por ciclo)
 * - Verificar salud de conectores activos (1 vez/día)
 * - Limpiar tokens OAuth expirados
 */
function jaraba_integrations_cron(): void {
  \Drupal::service('jaraba_integrations.webhook_dispatcher')->retryFailed(10);
  // Health check diario
  $lastCheck = \Drupal::state()->get('jaraba_integrations.last_health_check', 0);
  if (time() - $lastCheck > 86400) {
    \Drupal::service('jaraba_integrations.connector_registry')->healthCheckAll();
    \Drupal::state()->set('jaraba_integrations.last_health_check', time());
  }
}
```

### 10.8 Archivos a Crear

Estructura estándar con 4 entidades, 5 services, 5 controllers, templates con parciales, SCSS y package.json.

### 10.9 SCSS: Directrices

Cards de conectores con logo centrado y efecto hover premium. Usa grid auto-fill responsive:

```scss
.connector-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: var(--ej-spacing-lg, 1.5rem);
}

.connector-card {
  // Hereda patrón premium-card-pattern.md (glassmorphism)
  text-align: center;

  &__logo {
    width: 64px;
    height: 64px;
    object-fit: contain;
    margin: 0 auto var(--ej-spacing-md, 1rem);
  }

  &__installs {
    font-size: var(--ej-font-size-sm, 0.875rem);
    color: var(--ej-text-muted, #9E9E9E);
  }
}
```

### 10.10 Verificación

- [ ] Marketplace muestra conectores publicados en `/integrations`
- [ ] Filtro por categoría funciona
- [ ] Instalación de conector vía slide-panel
- [ ] OAuth2 flow completo (authorize → token → revoke)
- [ ] Webhook entregado al crear un pedido de prueba
- [ ] Rate limiting bloquea requests excesivos
- [ ] Developer portal accesible con registro de apps
- [ ] API docs en OpenAPI 3.0 accesibles

---

## 11. FASE 6: Customer Success Proactivo (Doc 113)

### 11.1 Justificación

Customer Success proactivo transforma la retención de reactiva a predictiva. Con health scores ponderados, predicción de churn vía ML, playbooks automatizados y detección de señales de expansión, el NRR objetivo es 115-120% con churn anual < 5%.

**Reutilización:** 30% de `jaraba_foc` (métricas financieras por tenant) y `jaraba_email` (plantillas de notificación).

**Depende de:** Fase 4 (Usage Billing) para métricas de uso en el cálculo de health score.

### 11.2 Entidades

#### 11.2.1 Entidad `CustomerHealth`

**Tipo:** ContentEntity | **ID:** `customer_health` | **Base table:** `customer_health`

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK |
| `tenant_id` | entity_reference (taxonomy_term) | ✅ | FK al tenant evaluado. UNIQUE INDEX (un registro por tenant) |
| `overall_score` | integer | ✅ | Puntuación global 0-100 |
| `engagement_score` | integer | ✅ | Puntuación engagement 0-100 (peso: 30%) |
| `adoption_score` | integer | ✅ | Puntuación adopción 0-100 (peso: 25%) |
| `satisfaction_score` | integer | ✅ | Puntuación satisfacción 0-100 (peso: 20%) |
| `support_score` | integer | ✅ | Puntuación soporte 0-100 (peso: 15%) |
| `growth_score` | integer | ✅ | Puntuación crecimiento 0-100 (peso: 10%) |
| `category` | list_string | ✅ | Categoría: `healthy` (80-100), `neutral` (60-79), `at_risk` (40-59), `critical` (0-39) |
| `trend` | list_string | ✅ | Tendencia: `improving`, `stable`, `declining` |
| `score_breakdown` | text_long | ❌ | JSON detallado con métricas individuales que componen cada score |
| `churn_probability` | decimal(5,4) | ❌ | Probabilidad de churn 0.0000-1.0000 |
| `calculated_at` | timestamp | ✅ | Momento del último cálculo |
| `created` | created | ✅ | Timestamp |
| `changed` | changed | ✅ | Timestamp |

#### 11.2.2 Entidad `ChurnPrediction`

**Tipo:** ContentEntity | **ID:** `churn_prediction` | **Base table:** `churn_prediction`

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK |
| `tenant_id` | entity_reference (taxonomy_term) | ✅ | INDEX |
| `probability` | decimal(5,4) | ✅ | Probabilidad de churn 0.0000-1.0000 |
| `risk_level` | list_string | ✅ | Nivel: `low`, `medium`, `high`, `critical` |
| `predicted_churn_date` | timestamp | ❌ | Fecha estimada de churn |
| `top_risk_factors` | text_long | ✅ | JSON array: `[{factor: "usage_drop", weight: 0.35, detail: "..."}]` |
| `recommended_actions` | text_long | ✅ | JSON array de acciones recomendadas con prioridad |
| `model_version` | string(32) | ✅ | Versión del modelo ML usado |
| `confidence` | decimal(5,4) | ✅ | Confianza de la predicción 0-1 |
| `created` | created | ✅ | Timestamp |

#### 11.2.3 Entidad `CsPlaybook`

**Tipo:** ContentEntity | **ID:** `cs_playbook` | **Base table:** `cs_playbook`

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK |
| `uuid` | uuid | ✅ | UUID |
| `name` | string(255) | ✅ | Nombre: "Reactivación", "Expansión" |
| `trigger_type` | list_string | ✅ | Tipo: `health_drop`, `usage_threshold`, `manual`, `expansion_signal` |
| `trigger_conditions` | text_long | ✅ | JSON: condiciones para activar el playbook |
| `steps` | text_long | ✅ | JSON array de pasos: `[{day: 0, action: "send_email", template: "reactivation_1"}, ...]` |
| `auto_execute` | boolean | ✅ | Si se ejecuta automáticamente. Default: FALSE |
| `priority` | list_string | ✅ | Prioridad: `low`, `medium`, `high`, `critical` |
| `status` | list_string | ✅ | Estado: `active`, `draft`, `archived` |
| `created` | created | ✅ | Timestamp |

#### 11.2.4 Entidad `ExpansionSignal`

**Tipo:** ContentEntity | **ID:** `expansion_signal` | **Base table:** `expansion_signal`

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK |
| `tenant_id` | entity_reference (taxonomy_term) | ✅ | INDEX |
| `signal_type` | list_string | ✅ | Tipo: `usage_limit_approaching`, `feature_request`, `team_growth`, `positive_nps`, `upgrade_inquiry` |
| `current_plan` | entity_reference (saas_plan) | ✅ | Plan actual del tenant |
| `recommended_plan` | entity_reference (saas_plan) | ❌ | Plan recomendado para upgrade |
| `potential_arr` | decimal(10,2) | ❌ | Incremento potencial en ARR (€) |
| `signal_details` | text_long | ❌ | JSON con detalles de la señal detectada |
| `status` | list_string | ✅ | Estado: `detected`, `in_progress`, `converted`, `dismissed`. Default: `detected` |
| `detected_at` | timestamp | ✅ | Momento de detección |
| `created` | created | ✅ | Timestamp |

### 11.3 Services

| Service | Clase | Métodos Clave | Descripción |
|---------|-------|---------------|-------------|
| `jaraba_customer_success.health_engine` | `HealthScoreEngineService` | `calculateScore()`, `updateAllTenants()`, `getHistory()` | Motor de cálculo del health score ponderado por 5 dimensiones. Se ejecuta diariamente vía cron |
| `jaraba_customer_success.churn_predictor` | `ChurnPredictorService` | `predict()`, `getTopRiskFactors()`, `updateModel()` | Predicción de churn con análisis de factores de riesgo. Usa datos de uso, soporte y engagement |
| `jaraba_customer_success.playbook_engine` | `PlaybookEngineService` | `execute()`, `scheduleStep()`, `evaluateTriggers()` | Motor de ejecución de playbooks automatizados. Procesa pasos secuenciales con delays configurables |
| `jaraba_customer_success.expansion_detector` | `ExpansionDetectorService` | `detectSignals()`, `calculatePotentialArr()` | Detección automática de señales de expansión basada en umbrales de uso y comportamiento |

### 11.4 Controllers

| Controller | Clase | Rutas |
|------------|-------|-------|
| CS Dashboard | `CustomerSuccessDashboardController` | `/customer-success` |
| Health Detail | `HealthDetailController` | `/customer-success/health/{tenant}` |
| API | `CustomerSuccessApiController` | `/api/v1/health-scores/*`, `/api/v1/churn-predictions/*`, `/api/v1/playbooks/*`, `/api/v1/expansion-signals/*` |

### 11.5 - 11.10 (Estructura idéntica a fases anteriores)

Templates, Frontend Assets, Hooks (`hook_cron` para cálculo diario de health scores, evaluación de triggers de playbooks, detección de señales de expansión), archivos a crear siguiendo patrón estándar, SCSS con tokens inyectables, checklist de verificación.

---

## 12. FASE 7: Knowledge Base & Self-Service (Doc 114)

### 12.1 Justificación

La Knowledge Base con búsqueda semántica (Qdrant) y FAQ bot con strict grounding (Claude Haiku) reduce tickets de soporte 30-40%. Reutiliza el 60% del pipeline RAG existente (`jaraba_rag`): embeddings, Qdrant client, grounding validator.

### 12.2 Entidades

#### 12.2.1 Entidad `KbArticle`

**Tipo:** ContentEntity | **ID:** `kb_article` | **Base table:** `kb_article`

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK |
| `uuid` | uuid | ✅ | UUID |
| `title` | string(255) | ✅ | Título del artículo. INDEX |
| `slug` | string(255) | ✅ | URL-safe slug. UNIQUE INDEX |
| `content` | text_long | ✅ | Contenido en Markdown |
| `excerpt` | string(500) | ✅ | Resumen para resultados de búsqueda |
| `category_id` | entity_reference (kb_category) | ✅ | FK a categoría |
| `vertical` | list_string | ❌ | Vertical destino (NULL = todas) |
| `target_roles` | text_long | ❌ | JSON array de roles destino |
| `status` | list_string | ✅ | Estado: `draft`, `published`, `archived` |
| `view_count` | integer | ✅ | Conteo de visualizaciones. Default: 0 |
| `helpful_yes` | integer | ✅ | Votos "útil". Default: 0 |
| `helpful_no` | integer | ✅ | Votos "no útil". Default: 0 |
| `embedding_id` | string(255) | ❌ | ID del punto en Qdrant para búsqueda semántica |
| `language` | string(12) | ✅ | Código de idioma: `es`, `en`. Default: `es` |
| `created` | created | ✅ | Timestamp |
| `changed` | changed | ✅ | Timestamp |

#### 12.2.2 Entidad `KbCategory`

**Tipo:** ContentEntity | **ID:** `kb_category` | **Base table:** `kb_category`

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK |
| `name` | string(255) | ✅ | Nombre de la categoría |
| `slug` | string(128) | ✅ | URL-safe slug. UNIQUE INDEX |
| `description` | text_long | ❌ | Descripción de la categoría |
| `parent_id` | entity_reference (kb_category) | ❌ | Categoría padre (jerarquía) |
| `icon` | string(128) | ❌ | Nombre del icono SVG |
| `weight` | integer | ✅ | Orden de presentación. Default: 0 |
| `article_count` | integer | ✅ | Contador de artículos (calculado). Default: 0 |

#### 12.2.3 Entidad `KbVideo`

**Tipo:** ContentEntity | **ID:** `kb_video` | **Base table:** `kb_video`

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK |
| `title` | string(255) | ✅ | Título del tutorial |
| `description` | text_long | ❌ | Descripción |
| `video_url` | string(512) | ✅ | URL del vídeo en CDN (Cloudflare Stream / Bunny) |
| `thumbnail_url` | string(512) | ❌ | Thumbnail del vídeo |
| `duration_seconds` | integer | ✅ | Duración en segundos |
| `transcript` | text_long | ❌ | Transcripción completa para búsqueda e indexación |
| `category_id` | entity_reference (kb_category) | ✅ | FK a categoría |
| `target_roles` | text_long | ❌ | JSON array de roles |
| `view_count` | integer | ✅ | Default: 0 |
| `embedding_id` | string(255) | ❌ | ID en Qdrant |
| `created` | created | ✅ | Timestamp |

#### 12.2.4 Entidad `FaqConversation`

**Tipo:** ContentEntity | **ID:** `faq_conversation` | **Base table:** `faq_conversation`

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK |
| `user_id` | entity_reference (user) | ❌ | Usuario (NULL = anónimo) |
| `session_id` | string(128) | ✅ | ID de sesión del navegador |
| `tenant_id` | entity_reference (taxonomy_term) | ✅ | Multi-tenant |
| `messages` | text_long | ✅ | JSON array: `[{role: "user"|"assistant", content, timestamp, sources?}]` |
| `resolution` | list_string | ✅ | Estado: `resolved`, `unresolved`, `escalated`. Default: `unresolved` |
| `satisfaction` | integer | ❌ | Valoración 1-5 del usuario |
| `escalated_ticket_id` | string(128) | ❌ | ID del ticket si fue escalado |
| `created` | created | ✅ | Timestamp |

### 12.3 Services

| Service | Clase | Métodos Clave | Descripción |
|---------|-------|---------------|-------------|
| `jaraba_knowledge_base.search` | `KbSemanticSearchService` | `search()`, `indexArticle()`, `removeFromIndex()` | Búsqueda semántica vía Qdrant. Genera embeddings con `text-embedding-3-small` y ejecuta k-NN search con filtros de vertical/rol |
| `jaraba_knowledge_base.faq_bot` | `KbFaqBotService` | `chat()`, `escalate()`, `getContextualHelp()` | FAQ Bot con strict grounding usando Claude 3.5 Haiku. Solo responde con contenido de la KB. Escala si score < 0.7 o > 3 mensajes sin resolución |
| `jaraba_knowledge_base.article_manager` | `KbArticleManagerService` | `publish()`, `archive()`, `updateViewCount()`, `recordFeedback()` | Gestión del ciclo de vida de artículos con embedding automático al publicar |
| `jaraba_knowledge_base.analytics` | `KbAnalyticsService` | `getPopularArticles()`, `getSearchGaps()`, `getResolutionRate()` | Métricas de la KB: artículos más consultados, búsquedas sin resultado, tasa de resolución del bot |

### 12.4 Controllers

| Controller | Clase | Rutas |
|------------|-------|-------|
| Help Center | `KbHelpCenterController` | `/help`, `/help/{category_slug}`, `/help/article/{slug}` |
| FAQ Bot API | `KbFaqBotApiController` | `/api/v1/kb/faq-bot/message`, `/api/v1/kb/faq-bot/escalate` |
| Search API | `KbSearchApiController` | `/api/v1/kb/search`, `/api/v1/kb/articles/*` |
| Admin | `KbAdminController` | `/admin/content/kb-articles`, `/admin/content/kb-videos` |

### 12.5 - 12.10 (Estructura estándar)

Templates para Help Center portal, FAQ bot widget, resultados de búsqueda. Hooks para generar embeddings en `hook_entity_insert/update` de artículos y vídeos. SCSS con tokens inyectables.

---

## 13. FASE 8: Security & Compliance (Doc 115)

### 13.1 Justificación

Las certificaciones SOC 2 Type II, ISO 27001 y ENS desbloquean contratos enterprise y B2G. Este módulo implementa la infraestructura técnica (audit logs inmutables, políticas de seguridad configurables, evaluaciones de compliance) mientras se prepara la documentación y procesos para las auditorías externas.

### 13.2 Entidades

#### 13.2.1 Entidad `AuditLog` (Inmutable)

**Tipo:** ContentEntity | **ID:** `audit_log` | **Base table:** `audit_log`

⚠️ **Inmutable:** Esta entidad NO tiene formulario de edición ni delete. Solo inserts. Garantiza cadena de auditoría íntegra.

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK |
| `timestamp` | timestamp | ✅ | Momento exacto del evento (microsegundos) |
| `event_type` | string(128) | ✅ | Tipo: `login`, `logout`, `entity_create`, `entity_update`, `entity_delete`, `permission_change`, `config_change`, `api_access`, `export_data`. INDEX |
| `actor_id` | entity_reference (user) | ❌ | Usuario que realizó la acción (NULL = sistema) |
| `actor_ip` | string(45) | ❌ | IP del actor (IPv4 o IPv6) |
| `resource_type` | string(128) | ✅ | Tipo de recurso afectado |
| `resource_id` | string(128) | ❌ | ID del recurso afectado |
| `action` | list_string | ✅ | Acción: `create`, `read`, `update`, `delete`, `login`, `logout`, `export`, `import` |
| `status` | list_string | ✅ | Resultado: `success`, `failure`, `denied` |
| `details` | text_long | ❌ | JSON con detalles adicionales (campos cambiados, valores antes/después) |
| `tenant_id` | entity_reference (taxonomy_term) | ✅ | Multi-tenant. INDEX |
| `hash` | string(64) | ✅ | SHA-256 del registro para detección de manipulación. Incluye hash del registro anterior (cadena) |

#### 13.2.2 Entidad `SecurityPolicy`

**Tipo:** ContentEntity | **ID:** `security_policy` | **Base table:** `security_policy`

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK |
| `name` | string(255) | ✅ | Nombre: "Política de contraseñas Enterprise" |
| `policy_type` | list_string | ✅ | Tipo: `password`, `mfa`, `session`, `data_retention`, `access_control` |
| `settings` | text_long | ✅ | JSON con configuración: `{min_length: 12, require_mfa: true, session_timeout_min: 30}` |
| `scope` | list_string | ✅ | Ámbito: `global`, `tenant` |
| `tenant_id` | entity_reference (taxonomy_term) | ❌ | Solo si scope = `tenant` |
| `is_active` | boolean | ✅ | Default: TRUE |
| `created` | created | ✅ | Timestamp |

#### 13.2.3 Entidad `ComplianceAssessment`

**Tipo:** ContentEntity | **ID:** `compliance_assessment` | **Base table:** `compliance_assessment`

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK |
| `framework` | list_string | ✅ | Marco: `soc2`, `iso27001`, `ens`, `iso42001`, `gdpr` |
| `assessment_date` | timestamp | ✅ | Fecha de la evaluación |
| `assessor` | string(255) | ✅ | Nombre del evaluador/auditor |
| `overall_score` | integer | ✅ | Puntuación 0-100 |
| `findings` | text_long | ✅ | JSON array: `[{control: "A.9.1", status: "pass|fail|partial", finding: "..."}]` |
| `remediation_plan` | text_long | ❌ | JSON: plan de remediación para hallazgos |
| `status` | list_string | ✅ | Estado: `planned`, `in_progress`, `completed`, `certified` |
| `certificate_url` | string(512) | ❌ | URL del certificado emitido |
| `expiry_date` | timestamp | ❌ | Fecha de vencimiento del certificado |
| `created` | created | ✅ | Timestamp |

### 13.3 - 13.10 (Estructura estándar)

Services para audit logging, policy enforcement, compliance tracking. Controllers para dashboard de compliance, visor de audit logs, gestión de políticas. Hooks masivos en `hook_entity_insert/update/delete` para registrar TODAS las acciones en audit log. SCSS con tokens.

---

## 14. FASE 9: Advanced Analytics & BI (Doc 116)

### 14.1 Justificación

BI self-service con report builder drag-drop, dashboards personalizables y exportación programada. Complementa al FOC (datos financieros) con métricas de impacto social, empleo, comercio y emprendimiento configurables por el usuario.

### 14.2 Entidades

`CustomReport` (report builder), `Dashboard` (dashboards con widgets en grid), `ScheduledReport` (exportación programada PDF/CSV/XLSX).

Campos detallados según spec 116: data_source, metrics JSON, dimensions JSON, filters JSON, visualization type, layout JSON (React-Grid-Layout format), schedule cron, format, recipients, delivery_channel.

### 14.3 - 14.10 (Estructura estándar)

Services: `ReportBuilderService`, `DashboardWidgetService`, `ReportExportService` (Puppeteer para PDF), `ReportSchedulerService`, `AnalyticsDataService`. Controllers para `/analytics`, API REST. Hooks de cron para ejecutar informes programados y pipeline ETL.

---

## 15. FASE 10: White-Label & Reseller Platform (Doc 117)

### 15.1 Justificación

White-label con dominios personalizados, branding completo (logo, colores, tipografía, emails) y portal de resellers con comisiones. Escala el negocio a través de franquicias sin desarrollo adicional.

### 15.2 Entidades

#### 15.2.1 Entidad `WhitelabelConfig`

**Tipo:** ContentEntity | **ID:** `whitelabel_config` | **Base table:** `whitelabel_config`

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK |
| `tenant_id` | entity_reference (taxonomy_term) | ✅ | UNIQUE — un config por tenant |
| `brand_name` | string(255) | ✅ | Nombre de marca del tenant |
| `logo_url` | string(512) | ❌ | Logo principal (fondo claro) |
| `logo_dark_url` | string(512) | ❌ | Logo para fondo oscuro |
| `favicon_url` | string(512) | ❌ | Favicon personalizado |
| `primary_color` | string(7) | ✅ | Hex: #FF8C42. Se inyecta como `--ej-color-primary` |
| `secondary_color` | string(7) | ✅ | Hex: #00A9A5. Se inyecta como `--ej-color-secondary` |
| `accent_color` | string(7) | ❌ | Hex adicional |
| `font_family` | string(128) | ❌ | Fuente: "Outfit", "Montserrat", etc. Se inyecta como `--ej-font-headings` |
| `custom_css` | text_long | ❌ | CSS adicional inyectado después del tema |
| `footer_text` | text_long | ❌ | Texto personalizado del footer |
| `support_email` | string(255) | ❌ | Email de soporte del tenant |
| `support_phone` | string(32) | ❌ | Teléfono de soporte |
| `created` | created | ✅ | Timestamp |

**Integración con Federated Design Tokens:** Los colores de `WhitelabelConfig` se inyectan en Layer 4 (Tenant Override) de la cascada de tokens, sobreescribiendo los valores base:

```php
// En hook_preprocess_html() del tema
$whitelabelConfig = \Drupal::service('jaraba_whitelabel.config_resolver')
  ->getConfigForCurrentTenant();

if ($whitelabelConfig) {
  $cssVars = ':root {';
  $cssVars .= '--ej-color-primary: ' . $whitelabelConfig->get('primary_color')->value . ';';
  $cssVars .= '--ej-color-secondary: ' . $whitelabelConfig->get('secondary_color')->value . ';';
  if ($font = $whitelabelConfig->get('font_family')->value) {
    $cssVars .= '--ej-font-headings: "' . $font . '", sans-serif;';
  }
  $cssVars .= '}';

  $variables['#attached']['html_head'][] = [
    ['#type' => 'html_tag', '#tag' => 'style', '#value' => $cssVars],
    'jaraba_whitelabel_vars',
  ];
}
```

#### 15.2.2 Entidad `CustomDomain`

**Tipo:** ContentEntity | **ID:** `custom_domain` | **Base table:** `custom_domain`

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK |
| `tenant_id` | entity_reference (taxonomy_term) | ✅ | INDEX |
| `domain` | string(255) | ✅ | FQDN: `mi-empresa.ejemplo.com`. UNIQUE INDEX |
| `verification_token` | string(128) | ✅ | Token para verificación DNS (TXT record) |
| `status` | list_string | ✅ | Estado: `pending`, `verifying`, `active`, `error` |
| `ssl_status` | list_string | ✅ | SSL: `pending`, `issuing`, `active`, `expired` |
| `ssl_expires_at` | timestamp | ❌ | Vencimiento del certificado SSL |
| `is_primary` | boolean | ✅ | Dominio principal del tenant. Default: FALSE |
| `last_verified_at` | timestamp | ❌ | Última verificación DNS exitosa |
| `error_message` | text_long | ❌ | Último error de verificación o SSL |
| `created` | created | ✅ | Timestamp |

#### 15.2.3 Entidad `EmailTemplate` (Brandeable)

**Tipo:** ContentEntity | **ID:** `email_template` | **Base table:** `email_template_wl`

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK |
| `tenant_id` | entity_reference (taxonomy_term) | ❌ | NULL = template global (plantilla base) |
| `template_key` | string(128) | ✅ | Clave: `welcome`, `password_reset`, `order_confirmation`, `invoice`, etc. INDEX |
| `subject` | string(255) | ✅ | Asunto del email con placeholders `{{ nombre }}` |
| `body_mjml` | text_long | ✅ | Cuerpo en MJML para rendering responsive |
| `body_text` | text_long | ✅ | Versión texto plano |
| `variables` | text_long | ✅ | JSON de variables disponibles: `[{key: "nombre", description: "..."}]` |
| `is_active` | boolean | ✅ | Default: TRUE |
| `language` | string(12) | ✅ | Código idioma: `es`, `en`. Default: `es` |
| `created` | created | ✅ | Timestamp |

#### 15.2.4 Entidad `Reseller`

**Tipo:** ContentEntity | **ID:** `reseller` | **Base table:** `reseller`

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK |
| `user_id` | entity_reference (user) | ✅ | Usuario Drupal del reseller. UNIQUE INDEX |
| `company_name` | string(255) | ✅ | Nombre de la empresa reseller |
| `territories` | text_long | ❌ | JSON array de zonas geográficas asignadas |
| `commission_rate` | decimal(5,4) | ✅ | Comisión sobre ingresos de sub-tenants: 0.0000-1.0000 (e.g., 0.2000 = 20%) |
| `payment_method` | list_string | ✅ | Método de pago: `stripe_connect`, `bank_transfer` |
| `stripe_account_id` | string(128) | ❌ | ID de cuenta Stripe Connect del reseller |
| `status` | list_string | ✅ | Estado: `pending`, `active`, `suspended` |
| `total_revenue` | decimal(12,2) | ✅ | Revenue total generado. Default: 0 |
| `total_commission` | decimal(12,2) | ✅ | Comisiones totales acumuladas. Default: 0 |
| `tenant_count` | integer | ✅ | Número de sub-tenants activos. Default: 0 |
| `created` | created | ✅ | Timestamp |

### 15.3 - 15.10 (Estructura estándar)

Services para resolución de branding, verificación DNS, provisión SSL, rendering MJML, gestión de resellers. Controllers para `/whitelabel`, portal de reseller, API REST. Hooks en `hook_preprocess_html` para inyectar branding, cron para verificar DNS periódicamente.

---

## 16. Inventario Consolidado de Entidades

| # | Entidad | Módulo | Fase | Tipo | Campos | Inmutable |
|---|---------|--------|------|------|--------|-----------|
| 1 | `agent_flow` | jaraba_agent_flows | 1 | ContentEntity | 17 | No |
| 2 | `agent_flow_execution` | jaraba_agent_flows | 1 | ContentEntity | 17 | No |
| 3 | `agent_flow_step_log` | jaraba_agent_flows | 1 | ContentEntity | 16 | No |
| 4 | `pending_sync_action` | jaraba_pwa | 2 | ContentEntity | 12 | No |
| 5 | `push_subscription` | jaraba_pwa | 2 | ContentEntity | 13 | No |
| 6 | `onboarding_template` | jaraba_onboarding | 3 | ContentEntity | 11 | No |
| 7 | `user_onboarding_progress` | jaraba_onboarding | 3 | ContentEntity | 16 | No |
| 8 | `usage_event` | jaraba_usage_billing | 4 | ContentEntity | 12 | No |
| 9 | `usage_aggregate` | jaraba_usage_billing | 4 | ContentEntity | 11 | No |
| 10 | `pricing_rule` | jaraba_usage_billing | 4 | ContentEntity | 10 | No |
| 11 | `connector` | jaraba_integrations | 5 | ContentEntity | 17 | No |
| 12 | `connector_installation` | jaraba_integrations | 5 | ContentEntity | 10 | No |
| 13 | `oauth_client` | jaraba_integrations | 5 | ContentEntity | 12 | No |
| 14 | `webhook_subscription` | jaraba_integrations | 5 | ContentEntity | 10 | No |
| 15 | `customer_health` | jaraba_customer_success | 6 | ContentEntity | 15 | No |
| 16 | `churn_prediction` | jaraba_customer_success | 6 | ContentEntity | 10 | No |
| 17 | `cs_playbook` | jaraba_customer_success | 6 | ContentEntity | 10 | No |
| 18 | `expansion_signal` | jaraba_customer_success | 6 | ContentEntity | 11 | No |
| 19 | `kb_article` | jaraba_knowledge_base | 7 | ContentEntity | 17 | No |
| 20 | `kb_category` | jaraba_knowledge_base | 7 | ContentEntity | 8 | No |
| 21 | `kb_video` | jaraba_knowledge_base | 7 | ContentEntity | 12 | No |
| 22 | `faq_conversation` | jaraba_knowledge_base | 7 | ContentEntity | 9 | No |
| 23 | `audit_log` | jaraba_security_compliance | 8 | ContentEntity | 12 | **SÍ** |
| 24 | `security_policy` | jaraba_security_compliance | 8 | ContentEntity | 8 | No |
| 25 | `compliance_assessment` | jaraba_security_compliance | 8 | ContentEntity | 11 | No |
| 26 | `custom_report` | jaraba_analytics_bi | 9 | ContentEntity | 12 | No |
| 27 | `dashboard` | jaraba_analytics_bi | 9 | ContentEntity | 10 | No |
| 28 | `scheduled_report` | jaraba_analytics_bi | 9 | ContentEntity | 10 | No |
| 29 | `whitelabel_config` | jaraba_whitelabel | 10 | ContentEntity | 15 | No |
| 30 | `custom_domain` | jaraba_whitelabel | 10 | ContentEntity | 11 | No |
| 31 | `email_template` | jaraba_whitelabel | 10 | ContentEntity | 10 | No |
| 32 | `reseller` | jaraba_whitelabel | 10 | ContentEntity | 12 | No |

**Total: 32 Content Entities en 10 módulos.**

---

## 17. Inventario Consolidado de Services

| # | Service ID | Módulo | Métodos Principales |
|---|-----------|--------|-------------------|
| 1 | `jaraba_agent_flows.engine` | Agent Flows | execute, resume, cancel, retry, enqueue |
| 2 | `jaraba_agent_flows.llm_orchestrator` | Agent Flows | makeDecision, generateContent, extractData |
| 3 | `jaraba_agent_flows.trigger_manager` | Agent Flows | registerTrigger, handleWebhook, processSchedule |
| 4 | `jaraba_agent_flows.validator` | Agent Flows | validateDefinition, validateInput |
| 5 | `jaraba_agent_flows.metrics` | Agent Flows | getExecutionStats, getCostBreakdown |
| 6 | `jaraba_pwa.sync_manager` | PWA | processPendingActions, resolveConflict, getDelta |
| 7 | `jaraba_pwa.push_service` | PWA | subscribe, unsubscribe, sendNotification, sendBatch |
| 8 | `jaraba_pwa.manifest_generator` | PWA | generateManifest, generateServiceWorker |
| 9 | `jaraba_pwa.offline_data` | PWA | getOfflineManifest, getEntityDelta |
| 10 | `jaraba_onboarding.progress` | Onboarding | getOrCreateProgress, completeStep, skipStep |
| 11 | `jaraba_onboarding.template_resolver` | Onboarding | resolveTemplate, getActiveTemplates |
| 12 | `jaraba_onboarding.tour_manager` | Onboarding | getTourDefinition, generateShepherdConfig |
| 13 | `jaraba_onboarding.gamification` | Onboarding | awardBadge, calculateXP, checkMilestone |
| 14 | `jaraba_usage_billing.metering` | Usage Billing | recordEvent, recordBatch, deduplicate |
| 15 | `jaraba_usage_billing.aggregator` | Usage Billing | aggregateHourly, aggregateDaily, aggregateMonthly |
| 16 | `jaraba_usage_billing.pricing_engine` | Usage Billing | calculateCost, getEstimate, getCurrentUsage |
| 17 | `jaraba_usage_billing.stripe_sync` | Usage Billing | syncUsageRecords, createInvoiceItems |
| 18 | `jaraba_usage_billing.alerts` | Usage Billing | checkThresholds, sendAlert |
| 19 | `jaraba_integrations.connector_registry` | Integrations | install, uninstall, testConnection |
| 20 | `jaraba_integrations.oauth_server` | Integrations | authorize, issueToken, revokeToken |
| 21 | `jaraba_integrations.webhook_dispatcher` | Integrations | dispatch, retryFailed, signPayload |
| 22 | `jaraba_integrations.developer_portal` | Integrations | registerApp, rotateSecret, getAnalytics |
| 23 | `jaraba_integrations.rate_limiter` | Integrations | checkLimit, recordRequest |
| 24 | `jaraba_customer_success.health_engine` | Customer Success | calculateScore, updateAllTenants |
| 25 | `jaraba_customer_success.churn_predictor` | Customer Success | predict, getTopRiskFactors |
| 26 | `jaraba_customer_success.playbook_engine` | Customer Success | execute, scheduleStep, evaluateTriggers |
| 27 | `jaraba_customer_success.expansion_detector` | Customer Success | detectSignals, calculatePotentialArr |
| 28 | `jaraba_knowledge_base.search` | Knowledge Base | search, indexArticle, removeFromIndex |
| 29 | `jaraba_knowledge_base.faq_bot` | Knowledge Base | chat, escalate, getContextualHelp |
| 30 | `jaraba_knowledge_base.article_manager` | Knowledge Base | publish, archive, recordFeedback |
| 31 | `jaraba_knowledge_base.analytics` | Knowledge Base | getPopularArticles, getSearchGaps |
| 32 | `jaraba_security_compliance.audit_logger` | Security | logEvent, getAuditTrail, verifyChainIntegrity |
| 33 | `jaraba_security_compliance.policy_enforcer` | Security | enforcePolicy, validatePassword, checkMfa |
| 34 | `jaraba_security_compliance.compliance_tracker` | Security | createAssessment, getComplianceStatus |
| 35 | `jaraba_analytics_bi.report_builder` | Analytics | createReport, executeReport, exportReport |
| 36 | `jaraba_analytics_bi.dashboard_manager` | Analytics | createDashboard, updateLayout, refreshWidget |
| 37 | `jaraba_analytics_bi.report_scheduler` | Analytics | scheduleReport, executeScheduled, sendReport |
| 38 | `jaraba_analytics_bi.data_service` | Analytics | getMetrics, getDimensions, queryData |
| 39 | `jaraba_whitelabel.config_resolver` | White-Label | getConfigForCurrentTenant, injectBranding |
| 40 | `jaraba_whitelabel.domain_manager` | White-Label | addDomain, verifyDns, provisionSsl |
| 41 | `jaraba_whitelabel.email_renderer` | White-Label | renderMjml, renderTemplate, sendBranded |
| 42 | `jaraba_whitelabel.reseller_manager` | White-Label | createSubTenant, calculateCommission, payout |

**Total: 42 Services en 10 módulos.**

---

## 18. Inventario Consolidado de Endpoints REST API

| Módulo | Endpoints | Descripción |
|--------|-----------|-------------|
| Agent Flows | GET/POST `/api/v1/agent-flows`, GET/PUT/DELETE `/api/v1/agent-flows/{id}`, POST `/{id}/execute`, GET `/{id}/executions`, GET `/executions/{id}`, POST `/executions/{id}/approve\|reject\|cancel\|retry`, POST `/{id}/webhook` | 11 endpoints |
| PWA | POST `/api/v1/push/subscribe\|unsubscribe`, PUT `/push/preferences`, POST `/sync/batch`, GET `/sync/status`, POST `/sync/resolve-conflict`, GET `/offline/manifest`, GET `/offline/delta` | 7 endpoints |
| Onboarding | GET `/api/v1/onboarding/my-progress`, POST `/steps/{id}/complete\|skip`, GET `/tours/{id}`, POST `/tours/{id}/complete`, POST `/dismiss\|restart`, GET `/help` | 7 endpoints |
| Usage Billing | POST `/api/v1/usage/record`, GET `/usage/current\|history\|breakdown\|forecast`, GET `/pricing/my-plan`, GET `/pricing/estimate` | 6 endpoints |
| Integrations | GET/POST `/api/v1/connectors`, GET `/connectors/{slug}`, POST `/{slug}/install\|uninstall`, GET `/installations`, PUT/POST `/installations/{id}`, GET/POST/PUT/DELETE `/webhooks/*`, GET/POST/PUT `/apps/*`, OAuth endpoints | 20+ endpoints |
| Customer Success | GET `/api/v1/health-scores`, GET `/{tenant_id}\|history`, GET `/churn-predictions`, GET/PUT `/expansion-signals/*`, GET/POST `/playbooks/*`, POST `/{id}/execute` | 10 endpoints |
| Knowledge Base | GET `/api/v1/kb/articles\|categories\|videos`, GET `/articles/{slug}`, POST `/articles/{id}/feedback`, GET `/search`, POST `/faq-bot/message\|escalate\|feedback`, GET `/contextual-help` | 10 endpoints |
| Security | GET `/api/v1/audit-logs`, GET `/security-policies`, GET/POST `/compliance-assessments` | 4 endpoints |
| Analytics BI | GET/POST `/api/v1/analytics/reports`, GET `/{id}`, POST `/{id}/execute\|export`, GET/POST `/dashboards`, GET `/metrics\|dimensions`, POST `/scheduled-reports` | 9 endpoints |
| White-Label | GET/PUT `/api/v1/whitelabel/config`, POST `/logo`, GET/POST/DELETE `/domains/*`, POST `/{id}/verify`, GET/PUT `/email-templates/*`, POST `/{key}/preview`, GET `/reseller/*` | 12 endpoints |

**Total: ~96 endpoints REST API.**

---

## 19. Paleta de Colores y Design Tokens

### 19.1 Tokens de Color de Platform Services

Los módulos de Platform Services NO definen colores propios. Consumen la paleta existente del ecosistema a través de CSS Custom Properties. Cada contexto funcional usa un color semántico:

| Contexto | Token CSS | Valor Default | Uso |
|----------|-----------|--------------|-----|
| IA/Agent Flows | `--ej-color-corporate` | #233D63 | Acciones de IA, badges de flujos |
| PWA/Offline | `--ej-color-warning` | #FFA000 | Indicador offline, sync pendiente |
| Onboarding | `--ej-color-primary` | #FF8C42 (impulse) | Progreso, CTAs, celebraciones |
| Usage Billing | `--ej-color-success` / `--ej-color-error` | #43A047 / #E53935 | Medidores verde→rojo |
| Integrations | `--ej-color-innovation` | #00A9A5 | Cards de conectores, badges |
| Customer Success | `--ej-color-success` → `--ej-color-error` | Variable | Health score coloreado |
| Knowledge Base | `--ej-color-info` | #1976D2 | Links, búsqueda, bot |
| Security | `--ej-color-corporate` | #233D63 | Dashboard formal |
| Analytics BI | `--ej-color-primary` | #FF8C42 | Gráficos, highlights |
| White-Label | *Dinámico por tenant* | Configurable | Se inyecta desde WhitelabelConfig |

### 19.2 Implementación en SCSS

```scss
// Ejemplo: _customer-success-health.scss
// Colores dinámicos basados en categoría de health score

.health-score {
  &--healthy {
    color: var(--ej-color-success, #43A047);
    background: rgba(67, 160, 71, 0.08);
  }

  &--neutral {
    color: var(--ej-color-warning, #FFA000);
    background: rgba(255, 160, 0, 0.08);
  }

  &--at-risk {
    color: var(--ej-color-impulse, #FF8C42);
    background: rgba(255, 140, 66, 0.08);
  }

  &--critical {
    color: var(--ej-color-error, #E53935);
    background: rgba(229, 57, 53, 0.08);
  }
}
```

---

## 20. Patrón de Iconos SVG

Cada módulo crea sus iconos SVG en las carpetas compartidas del core:

| Módulo | Categoría | Iconos Necesarios |
|--------|-----------|------------------|
| Agent Flows | `ai/` | `agent-flow.svg`, `flow-execution.svg`, `flow-step.svg`, `flow-trigger.svg` |
| PWA | `ui/` | `offline.svg`, `sync.svg`, `install-app.svg`, `push-notification.svg` |
| Onboarding | `ui/` | `onboarding.svg`, `checklist.svg`, `tour-guide.svg`, `badge.svg`, `confetti.svg` |
| Usage Billing | `analytics/` | `usage-meter.svg`, `billing.svg`, `overage.svg`, `credits.svg` |
| Integrations | `business/` | `connector.svg`, `marketplace.svg`, `webhook.svg`, `api-key.svg`, `oauth.svg` |
| Customer Success | `analytics/` | `health-score.svg`, `churn-risk.svg`, `playbook.svg`, `expansion.svg` |
| Knowledge Base | `ui/` | `help-center.svg`, `article.svg`, `video-tutorial.svg`, `faq-bot.svg`, `search.svg` |
| Security | `business/` | `audit-log.svg`, `security-policy.svg`, `compliance.svg`, `certificate.svg` |
| Analytics BI | `analytics/` | `custom-report.svg`, `dashboard-widget.svg`, `chart.svg`, `export.svg`, `schedule.svg` |
| White-Label | `business/` | `whitelabel.svg`, `custom-domain.svg`, `email-template.svg`, `reseller.svg` |

Todos en dos versiones: outline (`{name}.svg`) + duotone (`{name}-duotone.svg`).

---

## 21. Orden de Implementación Global y Dependencias

```
FASE  MES     MÓDULO                  DEPS                    PRIORIDAD
─────────────────────────────────────────────────────────────────────────
 1    M1-M2   Agent Flows (108)       jaraba_ai_agents        P0 - Crítico
 3    M1-M2   Onboarding (110)        jaraba_journey          P0 - Crítico
 4    M2-M3   Usage Billing (111)     jaraba_foc, Stripe      P0 - Crítico
 7    M2-M3   Knowledge Base (114)    jaraba_rag              P0 - Crítico
─────────────────────────────────────────────────────────────────────────
 2    M3-M4   PWA Mobile (109)        Core, Theme             P1 - Alto
 5    M3-M5   Integrations (112)      Core RBAC               P1 - Alto
 8    M4-M6   Security (115)          Core                    P1 - Alto
─────────────────────────────────────────────────────────────────────────
 6    M5-M6   Customer Success (113)  Fase 4 (Usage)          P1 - Alto
 9    M6-M7   Analytics BI (116)      Fases 4, 6              P2 - Medio
10    M7-M8   White-Label (117)       Theme, jaraba_email     P2 - Medio
─────────────────────────────────────────────────────────────────────────
```

**Justificación del orden:**
1. **P0 (M1-M3):** Agent Flows y Onboarding aportan valor inmediato al usuario. Usage Billing y Knowledge Base reducen costes operativos.
2. **P1 (M3-M6):** PWA amplía acceso, Integrations crea ecosistema, Security desbloquea enterprise.
3. **P2 (M5-M8):** Customer Success y Analytics dependen de datos históricos (necesitan meses de operación). White-Label es el último paso antes de escalar con partners.

---

## 22. Estimación Total de Esfuerzo

| Fase | Módulo | Entidades | Services | Endpoints | Sprints | Horas (min) | Horas (max) |
|------|--------|-----------|----------|-----------|---------|-------------|-------------|
| 1 | Agent Flows | 3 | 5 | 11 | 7 | 370 | 480 |
| 2 | PWA Mobile | 2 | 4 | 7 | 6 | 210 | 270 |
| 3 | Onboarding | 2 | 4 | 7 | 5 | 150 | 195 |
| 4 | Usage Billing | 3 | 5 | 6 | 5 | 155 | 205 |
| 5 | Integrations | 4 | 5 | 20+ | 7 | 360 | 490 |
| 6 | Customer Success | 4 | 4 | 10 | 7 | 290 | 410 |
| 7 | Knowledge Base | 4 | 4 | 10 | 6 | 250 | 340 |
| 8 | Security | 3 | 3 | 4 | 6 | 100 | 150 |
| 9 | Analytics BI | 3 | 4 | 9 | 6 | 310 | 410 |
| 10 | White-Label | 4 | 4 | 12 | 6 | 290 | 390 |
| **TOTAL** | **10 módulos** | **32** | **42** | **~96** | **~61** | **2,485** | **3,340** |

**Nota:** Las horas de Security (115) son solo de implementación de software. Las certificaciones (SOC 2, ISO 27001, ENS) requieren presupuesto adicional de €60-95k en auditorías externas con timeline de 6-12 meses.

---

## 23. Registro de Cambios

### v1.0.0 (2026-02-10)
- Creación inicial del plan de implementación para docs 108-117
- 10 fases detalladas con entidades, services, controllers, templates, SCSS
- Tabla de correspondencia con especificaciones técnicas
- Cumplimiento verificado de 14 directrices del proyecto
- Inventarios consolidados: 32 entidades, 42 services, ~96 endpoints
- Paleta de colores y patrón de iconos SVG documentados
- Orden de implementación con dependencias y priorización
- Estimación total: 2,485-3,340 horas en ~61 sprints

---

> **Próximo paso:** Registrar este documento en `docs/00_INDICE_GENERAL.md` y actualizar el Plan Maestro con las referencias a estas 10 fases.

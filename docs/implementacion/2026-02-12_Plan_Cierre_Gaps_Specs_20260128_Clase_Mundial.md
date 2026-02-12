# Plan de Implementación: Cierre de Gaps Especificaciones 20260128
## Elevación a Clase Mundial — Docs 178-187 + Arquitectura IA + Lenis

**Fecha de creación:** 2026-02-12
**Última actualización:** 2026-02-12
**Autor:** IA Asistente (Claude Opus 4.6)
**Versión:** 1.0.0
**Categoría:** Plan de Implementación — Cierre de Gaps
**Código:** PLAN-20260128-GAPS-v1

---

## Tabla de Contenidos (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Contexto y Alcance](#2-contexto-y-alcance)
3. [Requisitos Previos](#3-requisitos-previos)
4. [Diagnóstico Pre-Implementación](#4-diagnóstico-pre-implementación)
5. [Arquitectura General del Plan](#5-arquitectura-general-del-plan)
6. [FASE 1 — ECA Registry Master (Doc 185)](#6-fase-1--eca-registry-master-doc-185)
7. [FASE 2 — Freemium & Trial Model (Doc 183)](#7-fase-2--freemium--trial-model-doc-183)
8. [FASE 3 — Visitor Journey Complete (Doc 178)](#8-fase-3--visitor-journey-complete-doc-178)
9. [FASE 4 — Landing Pages Verticales (Doc 180)](#9-fase-4--landing-pages-verticales-doc-180)
10. [FASE 5 — Tenant Onboarding Wizard (Doc 179)](#10-fase-5--tenant-onboarding-wizard-doc-179)
11. [FASE 6 — SaaS Admin UX Complete (Doc 181)](#11-fase-6--saas-admin-ux-complete-doc-181)
12. [FASE 7 — Entity Admin Dashboard Elena (Doc 182)](#12-fase-7--entity-admin-dashboard-elena-doc-182)
13. [FASE 8 — Merchant Copilot (Doc 184)](#13-fase-8--merchant-copilot-doc-184)
14. [FASE 9 — B2B Sales Flow (Doc 186)](#14-fase-9--b2b-sales-flow-doc-186)
15. [FASE 10 — Scaling Infrastructure (Doc 187)](#15-fase-10--scaling-infrastructure-doc-187)
16. [FASE 11 — Elevación IA Clase Mundial](#16-fase-11--elevación-ia-clase-mundial)
17. [FASE 12 — Lenis Integration Premium](#17-fase-12--lenis-integration-premium)
18. [Tabla de Correspondencia con Especificaciones Técnicas](#18-tabla-de-correspondencia-con-especificaciones-técnicas)
19. [Checklist de Cumplimiento de Directrices del Proyecto](#19-checklist-de-cumplimiento-de-directrices-del-proyecto)
20. [Estrategia de Testing y Verificación](#20-estrategia-de-testing-y-verificación)
21. [Roadmap de Ejecución](#21-roadmap-de-ejecución)
22. [Métricas de Éxito](#22-métricas-de-éxito)
23. [Registro de Cambios](#23-registro-de-cambios)

---

## 1. Resumen Ejecutivo

Este plan consolida la implementación de **12 fases** para cerrar los gaps identificados en las especificaciones técnicas del 28 de enero de 2026 (documentos `20260128*` en `docs/tecnicos/`). El objetivo es elevar las 6 dimensiones del SaaS a **puntuación 10/10**, según la auditoría realizada.

### Documentos Fuente

| Código | Documento | Dimensión |
|--------|-----------|-----------|
| `20260128-Auditoria_Exhaustiva_Multidisciplinar_v1` | Evaluación 10 perspectivas | Todas |
| `20260128a-Auditoria_Ecosistema_Jaraba_UX_2026` | Auditoría UX 5 perspectivas | UX |
| `20260128b-Especificaciones_Completas_10_10` | 10 documentos de cierre (178-187) | Todas |
| `20260128c-Documento_Maestro_Consolidado_10_10` | Consolidación definitiva | Todas |
| `20260128-Auditoria_Arquitectura_IA_SaaS_v1` | Auditoría IA: agentes, reuso | IA |
| `20260128-Especificacion_IA_Clase_Mundial_SaaS_v1` | IA Benchmark + Roadmap | IA |

### Estado Actual vs Target

| Dimensión | Antes | Target | Fases Responsables |
|-----------|-------|--------|-------------------|
| Arquitectura Negocio | 8.5 | 10.0 | F9 (B2B Sales Flow) |
| Arquitectura Técnica | 9.0 | 10.0 | F10 (Scaling), F11 (IA), F12 (Lenis) |
| Consistencia Funcional | 7.5 | 10.0 | F1 (ECA), F8 (Merchant Copilot) |
| UX Admin SaaS | 6.5 | 10.0 | F6 (Admin UX Complete) |
| UX Tenant Admin | 7.0 | 10.0 | F5 (Onboarding Wizard), F7 (Elena) |
| UX Usuario Visitante | 6.0 | 10.0 | F2 (Freemium), F3 (Journey), F4 (Landings) |

### Inversión Total Estimada

| Concepto | Horas | Coste (€65/h) |
|----------|-------|---------------|
| Desarrollo (12 fases) | 580-780h | €37,700-50,700 |
| Testing + QA | 100-140h | €6,500-9,100 |
| Documentación | 30-40h | €1,950-2,600 |
| **TOTAL** | **710-960h** | **€46,150-62,400** |

---

## 2. Contexto y Alcance

### 2.1 Lo que ya está implementado (fortalezas base)

El SaaS ya dispone de una base técnica de clase mundial sobre la que se construyen estas mejoras:

| Infraestructura Existente | Módulo | Estado |
|--------------------------|--------|--------|
| AI Agents Module (`jaraba_ai_agents`) | 50+ archivos, BaseAgent, AgentOrchestrator | Operativo |
| Model Router dinámico (3 tiers) | `ModelRouterService.php` | Operativo |
| Agentic Workflow Engine | `AIWorkflow` + `WorkflowExecutorService` | Operativo |
| LLM-as-Judge (`QualityEvaluatorService`) | 5 criterios evaluación | Operativo |
| AI Observability (`AIObservabilityService`) | Logging + métricas | Operativo |
| TenantBrandVoiceService (8 arquetipos) | Personality, terms | Operativo |
| Health Score Calculator (5 dimensiones) | `HealthScoreCalculatorService` | Operativo |
| Impersonation con audit log | `ImpersonationApiController` | Operativo |
| SaaS Metrics (MRR/ARR/NRR/GRR) | `SaasMetricsService` | Operativo |
| CRM Pipeline completo | `jaraba_crm` (50+ archivos) | Operativo |
| Avatar Detection (cascada 4 niveles) | `AvatarDetectionService` | Operativo |
| Tracking Pixels (Meta/Google/LinkedIn/TikTok) | `jaraba_pixels` | Operativo |
| Trial 14 días | `TenantSubscriptionService` | Operativo |
| Usage Limits Framework | `UsageLimitsService` | Operativo |
| Diagnóstico Express Empleabilidad | `jaraba_diagnostic` | Operativo |
| Schema.org FAQ + Product | `SchemaOrgService` | Operativo |
| Billing Clase Mundial | `jaraba_billing` (26 endpoints) | Operativo |
| SEPE Integration | `jaraba_sepe_teleformacion` | Operativo |
| Cohort Analysis | `CohortAnalysisService` | Operativo |
| Branded PDF Service | `BrandedPdfService` | Operativo |

### 2.2 Lo que falta implementar (gaps a cerrar)

| Gap | Fase | Impacto | Horas Est. |
|-----|------|---------|------------|
| ECA Registry centralizado | F1 | Consistencia cross-vertical | 8-12h |
| Límites freemium por vertical | F2 | Conversión PLG | 16-24h |
| Lead magnets 4 verticales restantes | F3 | Adquisición | 32-40h |
| Social OAuth consumer (Google/LinkedIn) | F3 | Registro | 12-16h |
| Landing pages verticales completas | F4 | Conversión | 48-64h |
| Wizard 7 pasos tenant | F5 | Onboarding | 32-40h |
| Admin Center Dashboard unificado | F6 | Gestión SaaS | 24-32h |
| Dashboard institucional Elena | F7 | Vertical institucional | 24-32h |
| System prompt Merchant + flash offers | F8 | Copilot ComercioConecta | 20-28h |
| BANT + Sales Playbooks | F9 | B2B | 16-20h |
| Backup per tenant + performance tests | F10 | Infraestructura | 24-32h |
| Brand Voice Trainer + Multi-modal | F11 | IA Premium | 40-60h |
| Lenis smooth scroll | F12 | UX Premium | 8-12h |

---

## 3. Requisitos Previos

### 3.1 Entorno de Desarrollo

| Componente | Versión Requerida | Verificación |
|------------|-------------------|-------------|
| Drupal | 11.x | `lando drush status` |
| PHP | 8.4+ | `lando php -v` |
| Node.js | 20+ (via nvm) | `lando node -v` |
| Dart Sass | 1.71+ | `lando npx sass --version` |
| Redis | 7.x | `lando redis-cli ping` |
| Qdrant | Configurado | `curl http://qdrant:6333/collections` |
| Lando | Última versión | `lando version` |

### 3.2 Dependencias de Módulos

Cada fase depende de los módulos core existentes:

```
ecosistema_jaraba_core (SSOT: variables, servicios base)
├── jaraba_billing (suscripciones, planes, feature access)
├── jaraba_ai_agents (BaseAgent, AgentOrchestrator, ModelRouter)
├── jaraba_foc (Stripe Connect, métricas financieras)
├── jaraba_customer_success (Health Score, Churn)
├── jaraba_analytics (Cohorts, Funnels, Dashboard Builder)
├── jaraba_onboarding (Onboarding, Celebrations)
├── jaraba_crm (CRM Pipeline)
├── jaraba_pixels (Tracking Events)
├── jaraba_copilot_v2 (Copiloto IA 7 modos)
└── jaraba_whitelabel (Custom Domains, Branded PDFs)
```

### 3.3 Directrices de Obligado Cumplimiento

> **CRÍTICO**: Antes de implementar cualquier fase, revisar y cumplir **TODAS** estas directrices:

| Directriz | Documento | Sección |
|-----------|-----------|---------|
| Content Entity Pattern | `00_DIRECTRICES_PROYECTO.md` | §5 |
| 4 YAML files (routing, menu, task, action) | Workflow `drupal-custom-modules.md` | §Checklist |
| SCSS: Solo `var(--ej-*)` en módulos satélite | `2026-02-05_arquitectura_theming_saas_master.md` | §2.2 |
| Dart Sass moderno (`@use`, `color.adjust()`) | Workflow `scss-estilos.md` | §Reglas |
| i18n: `$this->t()` / `{% trans %}` / `Drupal.t()` | Workflow `i18n-traducciones.md` | §Regla |
| Slide-panel modales para CRUD frontend | Workflow `slide-panel-modales.md` | §Regla UX |
| Frontend limpio: `page--*.html.twig` sin regiones | Workflow `frontend-page-pattern.md` | §Pasos |
| Body classes via `hook_preprocess_html()` | Directrices §2.2.2 | §Lección Crítica |
| IA: Solo `@ai.provider`, nunca HTTP directo | Workflow `ai-integration.md` | §Regla |
| Iconos: `jaraba_icon()` con duotone SVG | Workflow `scss-estilos.md` | §Iconografía |
| Hooks nativos Drupal (no ECA UI) | Workflow `drupal-eca-hooks.md` | §Decisión |
| Entity navigation: `/admin/content` + `/admin/structure` | Workflow `drupal-custom-modules.md` | §Ubicación |
| Variables inyectables desde UI Drupal | `2026-02-05_arquitectura_theming_saas_master.md` | §2.3 |
| No hardcodear configuraciones de negocio | `00_DIRECTRICES_PROYECTO.md` | §5.3 |

---

## 4. Diagnóstico Pre-Implementación

### 4.1 Auditoría de Estado Actual por Documento

| Doc | Nombre | % Impl. | Fortalezas | Gaps Críticos |
|-----|--------|---------|------------|---------------|
| 178 | Visitor Journey | 55% | AvatarDetection, Pixels, Diagnóstico | Lead magnets, OAuth, Homepage selector |
| 179 | Tenant Onboarding | 40% | Controller básico, Stripe field, Celebrations | Wizard 7 pasos, Logo AI, Progress entity |
| 180 | Landing Pages | 60% | Rutas, Schema.org, Hero, Pricing | Contenido completo 9 secciones |
| 181 | Admin UX | 50% | Health Score, Impersonate, Metrics | Dashboard unificado, Cmd+K, Shortcuts |
| 182 | Entity Admin Elena | 15% | Cohorts, PDF, SEPE data | Dashboard, Grant tracking, Reports |
| 183 | Freemium/Trial | 55% | Trial 14d, Usage tracking, Limits framework | Limits por vertical, Triggers avanzados |
| 184 | Merchant Copilot | 45% | ProducerCopilotAgent, Descriptions, Pricing | System prompt, Flash offers, Social AI |
| 185 | ECA Registry | 0% | — | Todo por implementar |
| 186 | B2B Sales Flow | 40% | CRM, Entities, Kanban | BANT, Playbooks, Pipeline predefinido |
| 187 | Scaling Infra | 25% | Backup strategy doc, Self-healing | Per-tenant restore, k6, DB replication |
| IA Arch | AI Agents | 85% | Module completo, Agents, Orchestrator | Brand Voice Trainer |
| IA Clase | Clase Mundial | 55% | Model Router, Workflows, LLM-Judge | Multi-modal, Feedback loop |
| Lenis | Frontend Premium | 5% | Custom scroll-animations.js | Lenis no integrado |

---

## 5. Arquitectura General del Plan

### 5.1 Orden Lógico de Implementación

El orden de las fases sigue dependencias técnicas y lógica de negocio:

```
F1 (ECA Registry) ─────────────────┐ Fundamento: convenciones
F2 (Freemium/Trial) ───────────────┤ Fundamento: modelo de negocio
                                    │
F3 (Visitor Journey) ──────────────┤ Adquisición: funnel completo
F4 (Landing Pages) ────────────────┤ Adquisición: conversión por vertical
                                    │
F5 (Tenant Onboarding) ───────────┤ Activación: primer valor
                                    │
F6 (Admin Center) ────────────────┤ Operaciones: gestión SaaS
F7 (Dashboard Elena) ─────────────┤ Operaciones: vertical institucional
                                    │
F8 (Merchant Copilot) ────────────┤ Diferenciación: IA ComercioConecta
F9 (B2B Sales Flow) ──────────────┤ Diferenciación: pipeline ventas
                                    │
F10 (Scaling Infrastructure) ─────┤ Escalabilidad: infraestructura
F11 (IA Clase Mundial) ───────────┤ Excelencia: IA premium
F12 (Lenis Integration) ──────────┘ Polish: UX premium
```

### 5.2 Principios Arquitectónicos Transversales

Cada fase cumple estos principios sin excepción:

1. **SSOT (Single Source of Truth)**: Variables SCSS solo en `ecosistema_jaraba_core/scss/_variables.scss`. Módulos satélite solo consumen `var(--ej-*)`.

2. **Variables inyectables**: Todo color, tipografía y spacing configurable desde la UI del tema Drupal (Apariencia > Ecosistema Jaraba Theme Settings). Los parciales SCSS usan CSS Custom Properties con fallbacks inline.

3. **i18n obligatorio**: Todos los textos visibles al usuario son traducibles:
   - PHP: `$this->t('Texto')`
   - Twig: `{% trans %}Texto{% endtrans %}`
   - JS: `Drupal.t('Texto')`

4. **Frontend limpio sin regiones**: Páginas frontend usan `page--{ruta}.html.twig` con HTML completo, `{% include %}` de parciales `_header.html.twig` / `_footer.html.twig`, sin `page.content` ni bloques de Drupal.

5. **Modales slide-panel**: Toda acción CRUD en frontend se abre en modal slide-panel (data-slide-panel), el usuario nunca abandona la página actual.

6. **Mobile-first**: Layout full-width pensado primero para móvil. Breakpoints: 576px, 768px, 992px, 1200px, 1400px.

7. **Content Entities con Field UI**: Datos de negocio como Content Entities con `fieldable = TRUE`, `field_ui_base_route`, Access Handler, ListBuilder, AdminHtmlRouteProvider. Navegación en `/admin/content` (tabs) + `/admin/structure` (Field UI).

8. **Body classes via hook_preprocess_html()**: NUNCA usar `attributes.addClass()` en templates para el body. Siempre añadir al `hook_preprocess_html()` existente en `ecosistema_jaraba_theme.theme`.

9. **Iconos SVG duotone**: Usar `jaraba_icon('categoría', 'nombre', { variant: 'duotone', color: 'naranja-impulso' })`. NUNCA emojis en la interfaz.

10. **IA: @ai.provider**: Todas las llamadas a LLMs via `AiProviderPluginManager`. Nunca HTTP directo. Claves en Key module.

11. **Hooks nativos Drupal**: Automatizaciones vía `hook_entity_insert()`, `hook_entity_update()`, etc. No ECA UI module.

12. **Parciales Twig reutilizables**: Antes de crear código nuevo para un componente (header, footer, cards, etc.), verificar si ya existe un parcial en `templates/partials/`. Si un componente se usa en 2+ páginas, crear parcial con `_nombre.html.twig` y reutilizar con `{% include %}`. Los parciales reciben variables configurables desde el tema (theme_settings) para que los contenidos como footer, header, nav items, etc., sean editables desde la UI de Drupal sin tocar código.

13. **Dart Sass moderno**: `@use 'sass:color'`, `color.adjust()` en lugar de `darken()`/`lighten()`. Importar con `@use` no `@import`.

14. **Tenant no accede a admin theme**: El tenant admin usa dashboards frontend limpios, nunca el tema de administración de Drupal.

---

## 6. FASE 1 — ECA Registry Master (Doc 185)

### 6.1 Objetivo

Crear un registro centralizado de todos los flujos automatizados del ecosistema con IDs únicos, convención de nomenclatura estandarizada y estado de implementación. Esto establece la **fundación de consistencia cross-vertical** que el resto de fases necesita.

### 6.2 Justificación de Prioridad

El ECA Registry es la primera fase porque:
- Define la convención de nomenclatura para todas las automatizaciones
- Sirve de referencia para las fases siguientes (upgrade triggers F2, marketing flows F3, onboarding flows F5)
- No tiene dependencias de otras fases
- Bajo esfuerzo (8-12h) con alto impacto organizativo

### 6.3 Arquitectura

**Decisión**: Implementar como **ConfigEntity** (no Content Entity), porque el catálogo de flujos ECA es definido en código y se exporta a Git. No requiere Field UI ni que el admin cree flujos desde la interfaz.

```
ecosistema_jaraba_core/
├── src/Entity/EcaFlowDefinition.php          # ConfigEntity
├── src/EcaFlowDefinitionListBuilder.php      # Admin list builder
├── src/Form/EcaFlowDefinitionForm.php        # Admin edit form
├── config/install/
│   ├── ecosistema_jaraba_core.eca_flow_definition.eca_usr_001.yml
│   ├── ecosistema_jaraba_core.eca_flow_definition.eca_ten_001.yml
│   ├── ecosistema_jaraba_core.eca_flow_definition.eca_ten_002.yml
│   ├── ecosistema_jaraba_core.eca_flow_definition.eca_fin_001.yml
│   ├── ecosistema_jaraba_core.eca_flow_definition.eca_fin_003.yml
│   ├── ecosistema_jaraba_core.eca_flow_definition.eca_mkt_001.yml
│   ├── ... (catálogo completo)
│   └── ecosistema_jaraba_core.eca_flow_definition.eca_ord_006.yml
```

**Propiedades ConfigEntity:**

| Propiedad | Tipo | Descripción |
|-----------|------|-------------|
| `id` | string | ID único: `eca_{dominio}_{numero}` (ej: `eca_usr_001`) |
| `label` | string | Nombre legible: "Onboarding Usuario Nuevo" |
| `domain` | string | Dominio: USR, ORD, FIN, TEN, AI, WH, MKT, LMS, JOB, BIZ |
| `trigger_event` | string | Evento que lo dispara: `user_insert`, `commerce_order_complete` |
| `module` | string | Módulo que lo implementa: `ecosistema_jaraba_core` |
| `hook_function` | string | Función hook: `ecosistema_jaraba_core_user_insert` |
| `description` | string | Descripción del flujo y sus acciones |
| `spec_reference` | string | Referencia a doc de especificación: `06_Core`, `13_FOC` |
| `status` | boolean | Activo/Inactivo |
| `implementation_status` | string | `implemented`, `pending`, `deprecated` |

**Convención de Nomenclatura:**

```
ECA-{DOMINIO}-{NUMERO}

Dominios:
  USR  → Usuarios (registro, roles, asignaciones)
  ORD  → Pedidos/Commerce (órdenes, carritos, reembolsos)
  FIN  → FOC/Financiero (alertas, métricas, grants)
  TEN  → Tenants (onboarding, Stripe, lifecycle)
  AI   → Inteligencia Artificial (quality, training, observability)
  WH   → Webhooks (Stripe, SendGrid, external)
  MKT  → Marketing (lead magnets, email sequences, referrals)
  LMS  → Learning (enrollment, badges, XP)
  JOB  → Empleabilidad (matching, applications, alerts)
  BIZ  → Emprendimiento (BMC, hipótesis, experimentos)
```

**Catálogo Completo Inicial (30 flujos):**

| ID | Nombre | Trigger | Módulo | Estado |
|----|--------|---------|--------|--------|
| ECA-USR-001 | Onboarding Usuario Nuevo | `hook_user_insert` | `ecosistema_jaraba_core` | Implementado |
| ECA-USR-002 | Asignación Rol por Diagnóstico | `hook_entity_insert(employability_diagnostic)` | `jaraba_diagnostic` | Implementado |
| ECA-USR-003 | Welcome Email | `hook_user_insert` | `jaraba_email` | Implementado |
| ECA-TEN-001 | Tenant Onboarding | `hook_entity_insert(tenant)` | `ecosistema_jaraba_core` | Implementado |
| ECA-TEN-002 | Stripe Connect Completado | Webhook `account.updated` | `jaraba_foc` | Parcial |
| ECA-TEN-003 | Trial Expiration Warning | Cron (7 días antes) | `jaraba_billing` | Implementado |
| ECA-FIN-001 | Alerta Churn Spike | `health_score < 40` | `jaraba_customer_success` | Implementado |
| ECA-FIN-002 | Revenue Acceleration | `expansion_signal_insert` | `jaraba_customer_success` | Parcial |
| ECA-FIN-003 | Grant Burn Rate Warning | Cron (desvío >15%) | `jaraba_foc` | Pendiente |
| ECA-MKT-001 | Lead Magnet Completed | `hook_entity_insert(diagnostic)` | `jaraba_diagnostic` | Implementado |
| ECA-MKT-002 | Onboarding Email Day 1 | `hook_user_insert + 0h` | `jaraba_email` | Pendiente |
| ECA-MKT-003 | Onboarding Email Day 3 | `hook_user_insert + 72h` | `jaraba_email` | Pendiente |
| ECA-MKT-004 | Churn Risk Sequence | `health_score < 40` | `jaraba_email` | Pendiente |
| ECA-MKT-005 | Referral Code Generated | `hook_user_insert` | `jaraba_referral` | Implementado |
| ECA-MKT-006 | Referral Conversion | `hook_entity_insert(order) + ref_code` | `jaraba_referral` | Implementado |
| ECA-ORD-001 | Orden Completada | `commerce_order.place.post_transition` | `jaraba_agroconecta_core` | Implementado |
| ECA-ORD-002 | Carrito Abandonado | Cron (24h) | `jaraba_agroconecta_core` | Pendiente |
| ECA-ORD-003 | Reembolso Procesado | Webhook `charge.refunded` | `jaraba_billing` | Implementado |
| ECA-ORD-004 | Stock Bajo | `hook_entity_update(product_variation)` | `jaraba_agroconecta_core` | Pendiente |
| ECA-ORD-005 | Review Recibida | `hook_entity_insert(review)` | `jaraba_agroconecta_core` | Parcial |
| ECA-ORD-006 | Payout Completado | Webhook `payout.paid` | `jaraba_foc` | Implementado |
| ECA-LMS-001 | Auto-Enrollment | `hook_entity_insert(diagnostic)` | `jaraba_lms` | Implementado |
| ECA-LMS-002 | Badge Automático | `hook_entity_update(lms_enrollment)` | `jaraba_lms` | Implementado |
| ECA-LMS-003 | XP Automático | `hook_entity_insert(lms_progress)` | `jaraba_lms` | Implementado |
| ECA-JOB-001 | Application Notification | `hook_entity_insert(job_application)` | `jaraba_job_board` | Implementado |
| ECA-JOB-002 | Job Alerts Matching | Cron (diario 9:00) | `jaraba_job_board` | Implementado |
| ECA-JOB-003 | Embedding Auto-Index | `hook_entity_insert/update(job_posting)` | `jaraba_candidate` | Implementado |
| ECA-BIZ-001 | Hypothesis Created | `hook_entity_insert(hypothesis)` | `jaraba_copilot_v2` | Implementado |
| ECA-BIZ-002 | Experiment Completed | `hook_entity_update(experiment)` | `jaraba_copilot_v2` | Implementado |
| ECA-AI-001 | Quality Evaluation | Post-response | `jaraba_ai_agents` | Implementado |

### 6.4 Navegación Admin

```yaml
# ecosistema_jaraba_core.routing.yml
ecosistema_jaraba_core.eca_registry:
  path: '/admin/structure/eca-registry'
  defaults:
    _entity_list: 'eca_flow_definition'
    _title: 'ECA Flow Registry'
  requirements:
    _permission: 'administer site configuration'

# ecosistema_jaraba_core.links.menu.yml
ecosistema_jaraba_core.eca_registry:
  title: 'ECA Flow Registry'
  description: 'Registro centralizado de flujos automatizados del ecosistema'
  route_name: ecosistema_jaraba_core.eca_registry
  parent: system.admin_structure
  weight: 15
```

### 6.5 Horas Estimadas: 8-12h

---

## 7. FASE 2 — Freemium & Trial Model (Doc 183)

### 7.1 Objetivo

Definir y aplicar límites freemium específicos por vertical, implementar los triggers de upgrade faltantes (primera venta, invitar colaborador) y crear las notificaciones de límite alcanzado que incentiven la conversión.

### 7.2 Arquitectura

**Extender** el `UsageLimitsService` existente en `ecosistema_jaraba_core` y el `FeatureAccessService` en `jaraba_billing` para incluir límites verticales específicos.

**Configuración de Límites por Vertical (Config Entity):**

Se creará una ConfigEntity `FreemiumLimits` con los límites por vertical y plan, administrable desde `/admin/structure/freemium-limits` sin tocar código:

| Vertical | Feature | Free | Starter | Pro |
|----------|---------|------|---------|-----|
| AgroConecta | products | 5 | -1 (ilimitado) | -1 |
| AgroConecta | orders_per_month | 10 | -1 | -1 |
| AgroConecta | copilot_uses_per_month | 3 | 30 | -1 |
| AgroConecta | photos_per_product | 1 | 5 | 10 |
| AgroConecta | commission_pct | 10 | 8 | 5 |
| ComercioConecta | products | 10 | -1 | -1 |
| ComercioConecta | qr_codes | 1 | 10 | -1 |
| ComercioConecta | flash_offers_active | 1 | 10 | -1 |
| ServiciosConecta | services | 3 | -1 | -1 |
| ServiciosConecta | bookings_per_month | 10 | -1 | -1 |
| Empleabilidad | diagnostics | 1 | -1 | -1 |
| Empleabilidad | offers_visible_per_day | 10 | -1 | -1 |
| Empleabilidad | cv_builder | 1 | 5 | -1 |
| Emprendimiento | bmc_drafts | 1 | -1 | -1 |
| Emprendimiento | calculadora_uses | 1 | -1 | -1 |

**Triggers de Upgrade Faltantes:**

```php
// En ecosistema_jaraba_core.module

/**
 * Implements hook_entity_insert() para ECA-ORD-001 variante "primera venta".
 *
 * Cuando un tenant registra su primera venta exitosa, se celebra el logro
 * y se muestra un upgrade trigger con mensaje contextualizado.
 */
function ecosistema_jaraba_core_commerce_order_complete_first_sale($order, $tenant) {
  $order_count = \Drupal::service('ecosistema_jaraba_core.tenant_context')
    ->getOrderCount($tenant->id());

  if ($order_count === 1) {
    // Trigger: Primera venta → Upgrade sugerido
    \Drupal::service('jaraba_billing.upgrade_trigger')
      ->fire('first_sale', $tenant, [
        'message' => t('¡Felicidades por tu primera venta! Reduce tu comisión al 5% con el plan Pro.'),
        'expected_conversion' => 0.42, // 42% según spec
      ]);
  }
}
```

### 7.3 Frontend: Modal de Upgrade

Cuando se alcanza un límite, se muestra un modal slide-panel con:
- Icono duotone de `jaraba_icon('actions', 'rocket', { variant: 'duotone', color: 'naranja-impulso' })`
- Mensaje traducible contextualizado por vertical
- Comparativa visual Free vs Pro (2 columnas)
- CTA primario: "Upgrade ahora" → Pricing page
- CTA secundario: "Recordarme después"

**Template:** `templates/partials/_upgrade-trigger.html.twig` (nuevo parcial reutilizable)

### 7.4 Horas Estimadas: 16-24h

---

## 8. FASE 3 — Visitor Journey Complete (Doc 178)

### 8.1 Objetivo

Implementar el journey completo del visitante anónimo: lead magnets para 4 verticales restantes, OAuth social (Google/LinkedIn), homepage con selector de vertical interactivo y tracking AIDA completo.

### 8.2 Sub-fases

#### 8.2.1 Lead Magnets por Vertical (Faltan 4)

| Vertical | Lead Magnet | Módulo | Tipo |
|----------|------------|--------|------|
| Emprendimiento | Calculadora de Madurez Digital | `jaraba_copilot_v2` (extender) | Formulario interactivo |
| AgroConecta | Guía "Vende Online sin Intermediarios" | `jaraba_agroconecta_core` (extender) | PDF descargable |
| ComercioConecta | Auditoría SEO Local Gratuita | `jaraba_comercio_conecta` (extender) | Herramienta automatizada |
| ServiciosConecta | Template Propuesta Profesional | `jaraba_servicios_conecta` (extender) | Documento descargable |

**Patrón de implementación para cada lead magnet:**
1. Controller con ruta pública (sin autenticación)
2. Formulario de captura de email (campo obligatorio, campo nombre)
3. Procesamiento del resultado (scoring, PDF, template)
4. Envío por email + CTA "Ver más detalles"
5. Tracking: evento `lead_magnet_complete` via `jaraba_pixels`

**NOTA i18n**: Todos los textos del formulario y resultados deben usar `$this->t()` en PHP y `{% trans %}` en Twig.

#### 8.2.2 Social OAuth (Google + LinkedIn)

Instalar y configurar `drupal/social_auth` + `drupal/social_auth_google` + `drupal/social_auth_linkedin`:

```bash
lando composer require drupal/social_auth drupal/social_auth_google drupal/social_auth_linkedin
lando drush en social_auth social_auth_google social_auth_linkedin -y
```

Configurar en `/admin/config/social-api/social-auth/` con credenciales por entorno (Key module).

#### 8.2.3 Homepage Universal con Selector de Vertical

Extender `page--front.html.twig` existente con un componente de selección visual de vertical:

- 5 cards con iconos SVG duotone (1 por vertical)
- Animación hover con `var(--ej-transition-spring)`
- Detección automática: si ya se detectó vertical via `AvatarDetectionService`, resaltar esa card
- CTA por card: redirige a `/agroconecta`, `/comercioconecta`, `/serviciosconecta`, `/empleabilidad`, `/emprendimiento`

**Nuevo parcial:** `_vertical-selector.html.twig`

```twig
{# _vertical-selector.html.twig - Selector visual de verticales #}
<section class="vertical-selector" id="verticales">
  <h2 class="vertical-selector__title">
    {% trans %}Elige tu camino{% endtrans %}
  </h2>
  <div class="vertical-selector__grid">
    {% for vertical in verticals %}
      <a href="{{ vertical.url }}" class="vertical-card {{ vertical.highlighted ? 'vertical-card--highlighted' : '' }}">
        <div class="vertical-card__icon">
          {{ jaraba_icon('verticals', vertical.icon, { variant: 'duotone', color: vertical.color, size: '48px' }) }}
        </div>
        <h3 class="vertical-card__title">{{ vertical.title }}</h3>
        <p class="vertical-card__description">{{ vertical.description }}</p>
        <span class="vertical-card__cta">{% trans %}Explorar{% endtrans %} →</span>
      </a>
    {% endfor %}
  </div>
</section>
```

**SCSS:** Nuevo parcial `_vertical-selector.scss` usando `var(--ej-*)`.

### 8.3 Horas Estimadas: 44-56h

---

## 9. FASE 4 — Landing Pages Verticales (Doc 180)

### 9.1 Objetivo

Completar las 5 landing pages verticales con la estructura de 9 secciones definida en la especificación, optimizadas para conversión y SEO/GEO.

### 9.2 Arquitectura de Parciales Reutilizables

Para evitar duplicación, se crean parciales compartidos entre las 5 landings:

| Parcial | Propósito | Usado en |
|---------|-----------|----------|
| `_landing-hero.html.twig` | Sección hero con headline, subheadline, CTA, imagen | 5 landings |
| `_landing-pain-points.html.twig` | 3-4 problemas con iconos | 5 landings |
| `_landing-solution-steps.html.twig` | 3 pasos simples con numeración | 5 landings |
| `_landing-features-grid.html.twig` | Grid 6-8 features con iconos | 5 landings |
| `_landing-social-proof.html.twig` | Testimonios + logos + métricas | 5 landings |
| `_landing-lead-magnet.html.twig` | CTA secundario lead magnet | 5 landings |
| `_landing-pricing-preview.html.twig` | Desde X€/mes + enlace a planes | 5 landings |
| `_landing-faq.html.twig` | FAQ con Schema.org JSON-LD | 5 landings |
| `_landing-final-cta.html.twig` | CTA primario + secundario | 5 landings |

**Cada landing vertical** es un `page--{vertical}.html.twig` que incluye los parciales con datos específicos inyectados desde un Controller:

```php
class VerticalLandingController extends ControllerBase {

  public function agroconecta(): array {
    return [
      '#theme' => 'vertical_landing_agroconecta',
      '#hero' => [
        'headline' => $this->t('Vende tus productos del campo sin intermediarios'),
        'subheadline' => $this->t('Tu tienda online en 10 minutos. Cobra directamente. Sin comisiones ocultas.'),
        'cta_text' => $this->t('Crea tu tienda gratis'),
        'cta_url' => '/registro?vertical=agroconecta',
        'image' => 'productor-tienda-online',
      ],
      '#pain_points' => [
        ['icon' => 'business/intermediary', 'text' => $this->t('Los intermediarios se quedan con el 40% de tu margen')],
        ['icon' => 'business/complexity', 'text' => $this->t('No tienes tiempo para gestionar una web complicada')],
        ['icon' => 'business/invisible', 'text' => $this->t('Tus clientes no saben que existes')],
        ['icon' => 'business/payment', 'text' => $this->t('Cobrar es un lío: transferencias, efectivo, recibos...')],
      ],
      // ... resto de secciones
    ];
  }
}
```

**SCSS:** Un parcial `_landing-sections.scss` compartido con variables de color por vertical inyectadas via CSS Custom Properties.

**Schema.org FAQ**: Reutilizar `SchemaOrgService::generateFAQSchema()` existente.

### 9.3 Configuración Visual desde UI de Drupal

Los textos de hero, pain points, features, etc., se gestionarán como campos de una **Content Entity `VerticalLandingConfig`** para que sean editables desde `/admin/content/vertical-landings` sin tocar código. Cada vertical tiene una instancia con campos para headline, subheadline, CTA text, pain points, features, testimonials, FAQ items, etc.

### 9.4 Horas Estimadas: 48-64h

---

## 10. FASE 5 — Tenant Onboarding Wizard (Doc 179)

### 10.1 Objetivo

Implementar el wizard de 7 pasos para configuración inicial del tenant, con persistencia de progreso, asistencia IA para extracción de paleta de colores del logo, y celebración al completar.

### 10.2 Arquitectura

**Nueva Content Entity:** `TenantOnboardingProgress`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | integer | ID automático |
| `tenant_id` | entity_reference → Tenant | Tenant asociado |
| `current_step` | integer | Paso actual (1-7) |
| `completed_steps` | string_long (JSON) | Array de pasos completados |
| `step_data` | string_long (JSON) | Datos capturados por paso |
| `started_at` | created | Timestamp de inicio |
| `completed_at` | timestamp | Timestamp de finalización (NULL si incompleto) |
| `time_spent_seconds` | integer | Tiempo total invertido |
| `skipped_steps` | string_long (JSON) | Pasos omitidos voluntariamente |

**Wizard Controller:** `TenantOnboardingWizardController`

Ruta: `/onboarding/wizard/{step}` (frontend limpio, sin regiones Drupal)

**Template:** `page--onboarding--wizard.html.twig` con progreso visual y contenido por paso.

**7 Pasos:**

1. **Bienvenida** (30s): Confirmar vertical detectada, establecer expectativas
2. **Identidad** (2min): Logo upload + IA extrae paleta, nombre comercial, colores
3. **Datos Fiscales** (2min): NIF/CIF con validación algoritmo español, dirección fiscal con Google Places
4. **Pagos** (3min): Redirect a Stripe Connect Onboarding → callback
5. **Equipo** (1min): Invitar colaboradores por email (saltable)
6. **Contenido Inicial** (3min): Crear primer producto/servicio según vertical
7. **Lanzamiento** (30s): Confetti animation + preview + compartir

**Extracción de Paleta de Colores del Logo (IA):**

```php
class LogoColorExtractorService {
  public function __construct(
    private AiProviderPluginManager $aiProvider,
  ) {}

  /**
   * Extrae paleta dominante de un logo subido.
   * Usa visión por IA para analizar la imagen y devolver colores hex.
   */
  public function extractPalette(FileInterface $logo): array {
    // Usar capacidad de visión del modelo para analizar el logo
    $provider = $this->aiProvider->createInstance('anthropic');
    $imageData = base64_encode(file_get_contents($logo->getFileUri()));

    $response = $provider->chat([
      ['role' => 'user', 'content' => [
        ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => 'image/png', 'data' => $imageData]],
        ['type' => 'text', 'text' => 'Extract the 3 dominant colors from this logo. Return JSON: {"primary": "#hex", "secondary": "#hex", "accent": "#hex"}'],
      ]],
    ], 'claude-3-haiku-20240307');

    return json_decode($response->getText(), TRUE);
  }
}
```

### 10.3 Horas Estimadas: 32-40h

---

## 11. FASE 6 — SaaS Admin UX Complete (Doc 181)

### 11.1 Objetivo

Crear el Admin Center Dashboard unificado que agrega KPIs de todos los módulos, implementar el Command Palette global y completar los shortcuts de teclado.

### 11.2 Arquitectura

**Nuevo Controller:** `AdminCenterController` en `ecosistema_jaraba_core`

Ruta: `/admin/jaraba/center` (usa tema admin estándar, no frontend limpio)

**Agrega datos de:**
- `SaasMetricsService` → MRR, ARR, MAU, Churn
- `HealthScoreCalculatorService` → Distribución de Health Scores
- `TenantSubscriptionService` → Tenants activos, trials, pendientes
- `ChurnPredictionService` → Tenants at-risk
- `AlertRule` entities → Alertas activas

**Command Palette Global (Cmd+K):**

Extender el Command Palette existente de `jaraba_page_builder/js/grapesjs-jaraba-command-palette.js` para hacerlo **global** en todas las páginas admin:

- Nuevo JS: `ecosistema_jaraba_core/js/admin-command-palette.js`
- Activar con Ctrl+K / Cmd+K en cualquier página admin
- Comandos: `go tenants`, `go finance`, `go users`, `search [query]`, `impersonate [email]`, `alerts`
- Endpoint API: `GET /api/v1/admin/search?q={query}` para fuzzy search

**Shortcuts de Teclado:**

| Shortcut | Acción |
|----------|--------|
| G → T | Ir a Tenants |
| G → F | Ir a Finance (FOC) |
| G → U | Ir a Users |
| A | Ver alertas activas |
| I | Iniciar impersonate |
| ? | Mostrar ayuda de shortcuts |

### 11.3 Horas Estimadas: 24-32h

---

## 12. FASE 7 — Entity Admin Dashboard Elena (Doc 182)

### 12.1 Objetivo

Crear el dashboard para el avatar Elena (administradora institucional), con Grant Burn Rate tracker, gestión de cohortes y generación de informes para justificación de fondos públicos.

### 12.2 Arquitectura

**Módulo:** Extender `jaraba_analytics` con nuevo controller y templates.

**Ruta frontend:** `/programa/dashboard` (página limpia, accesible solo para rol `entity_admin`)

**Template:** `page--programa--dashboard.html.twig`

**Nuevos Servicios:**

| Servicio | Función |
|----------|---------|
| `GrantTrackingService` | Cálculo de burn rate, forecast, alertas desvío |
| `InstitutionalReportService` | Generación de informes PDF con templates predefinidos |

**Grant Burn Rate Calculation:**

```php
class GrantTrackingService {
  /**
   * Calcula la tasa de consumo del grant respecto a la línea temporal.
   *
   * @param int $grant_total Total del grant en euros.
   * @param int $spent_amount Cantidad gastada hasta la fecha.
   * @param string $start_date Fecha de inicio del programa (ISO 8601).
   * @param string $end_date Fecha de fin del programa (ISO 8601).
   *
   * @return array{burn_rate: float, expected_rate: float, deviation: float, alert: bool}
   */
  public function calculateBurnRate(int $grant_total, int $spent_amount, string $start_date, string $end_date): array {
    $total_days = (new \DateTime($end_date))->diff(new \DateTime($start_date))->days;
    $elapsed_days = (new \DateTime())->diff(new \DateTime($start_date))->days;

    $burn_rate = ($spent_amount / $grant_total) * 100;
    $expected_rate = ($elapsed_days / $total_days) * 100;
    $deviation = $burn_rate - $expected_rate;

    return [
      'burn_rate' => round($burn_rate, 1),
      'expected_rate' => round($expected_rate, 1),
      'deviation' => round($deviation, 1),
      'alert' => abs($deviation) > 15, // Alerta si >15% desvío
    ];
  }
}
```

**Templates de Informe Institucional:**

| Template | Contenido | Formato |
|----------|-----------|---------|
| Seguimiento Mensual | Alumnos, progreso, incidencias | PDF A4 |
| Memoria Económica | Desglose gastos por partida | PDF A4 |
| Informe de Impacto | Inserción laboral, creación empresa | PDF A4 |
| Justificación Técnica | Evidencias actividad formativa | PDF A4 |
| Certificados de Asistencia | Generación masiva por cohorte | PDF A4 (batch) |

Reutilizar `BrandedPdfService` existente con templates Twig específicos.

### 12.3 Horas Estimadas: 24-32h

---

## 13. FASE 8 — Merchant Copilot (Doc 184)

### 13.1 Objetivo

Completar el Merchant Copilot para ComercioConecta: system prompt especializado, generación de ofertas flash y posts de redes sociales con IA.

### 13.2 Arquitectura

**Extender** `ProducerCopilotAgent` existente en `jaraba_ai_agents` con acciones merchant-específicas, o crear un agente dedicado `MerchantCopilotAgent`:

```php
class MerchantCopilotAgent extends BaseAgent {

  protected function getSystemPrompt(array $context): string {
    return $this->t("Eres el Merchant Copilot de ComercioConecta, un asistente especializado en ayudar a comercios locales a vender más online.

CONTEXTO DEL COMERCIO:
- Nombre: @name
- Sector: @category
- Ubicación: @city
- Productos: @product_count
- Valoración media: @avg_rating

REGLAS ESTRICTAS:
1. Solo puedes hablar de productos que existen en el catálogo del comercio
2. Los precios que sugieras deben estar en el rango ±20% del actual
3. No inventes características que el producto no tenga
4. Mantén el tono cercano y local (no corporativo)
5. Incluye siempre call-to-action hacia la tienda

FORMATO DE RESPUESTAS:
- Descripciones: 2-3 párrafos, máx 150 palabras
- Posts sociales: 1 párrafo + 5-7 hashtags locales
- Emails: Subject + Preview + Body + CTA", [
      '@name' => $context['merchant_name'],
      '@category' => $context['merchant_category'],
      '@city' => $context['merchant_city'],
      '@product_count' => $context['product_count'],
      '@avg_rating' => $context['avg_rating'],
    ]);
  }

  public function getAvailableActions(): array {
    return [
      'generate_description' => $this->t('Genera descripción atractiva para un producto'),
      'suggest_price' => $this->t('Sugiere precio basado en mercado local'),
      'social_post' => $this->t('Crea post para Instagram/Facebook'),
      'flash_offer' => $this->t('Sugiere oferta flash para producto con stock lento'),
      'respond_review' => $this->t('Genera respuesta profesional a reseña'),
      'email_promo' => $this->t('Crea email promocional para campaña'),
    ];
  }
}
```

**Flash Offer Logic:** Analizar stock y ventas de los últimos 30 días para sugerir descuento óptimo.

### 13.3 Horas Estimadas: 20-28h

---

## 14. FASE 9 — B2B Sales Flow (Doc 186)

### 14.1 Objetivo

Completar el flujo de ventas B2B con pipeline predefinido (Lead→MQL→SQL→Demo→Propuesta→Negociación→Cerrado), sistema de cualificación BANT y automatización de playbooks.

### 14.2 Arquitectura

**Extender** `jaraba_crm` existente:

1. **Pipeline Stages predefinidos** (ConfigEntity vía config/install):

```yaml
# jaraba_crm.pipeline_stage.lead.yml
id: lead
label: 'Lead'
weight: 0
probability: 10
color: '#94A3B8'
is_won: false
is_lost: false
```

2. **BANT Qualification** (nuevos campos en Opportunity entity):

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `bant_budget` | list_string | none, exploring, allocated, approved |
| `bant_authority` | list_string | user, influencer, decision_maker, champion |
| `bant_need` | list_string | none, identified, urgent, critical |
| `bant_timeline` | list_string | none, 12mo, 6mo, 3mo, immediate |
| `bant_score` | integer (computed) | 0-4 basado en campos BANT |

3. **Sales Playbook Service:**

```php
class SalesPlaybookService {
  /**
   * Determina la siguiente acción recomendada para una oportunidad
   * basándose en la etapa actual y el score BANT.
   */
  public function getNextAction(Opportunity $opportunity): array {
    $stage = $opportunity->getStage();
    $bant = $opportunity->getBantScore();

    return match ($stage) {
      'lead' => ['action' => $this->t('Enviar secuencia email nurturing (5 emails / 30 días)')],
      'mql' => ['action' => $this->t('Programar llamada de descubrimiento (15-20 min, framework SPIN)')],
      'sql' => $bant >= 3
        ? ['action' => $this->t('Programar demo personalizada')]
        : ['action' => $this->t('Re-cualificar: BANT incompleto (@score/4)', ['@score' => $bant])],
      'demo' => ['action' => $this->t('Enviar propuesta personalizada con 3 opciones de plan')],
      'proposal' => ['action' => $this->t('Follow-up a los 3 días si no hay respuesta')],
      'negotiation' => ['action' => $this->t('Preparar contrato + onboarding plan')],
      default => ['action' => $this->t('Revisar oportunidad')],
    };
  }
}
```

### 14.3 Horas Estimadas: 16-20h

---

## 15. FASE 10 — Scaling Infrastructure (Doc 187)

### 15.1 Objetivo

Implementar backup/restore por tenant, configuración de performance testing con k6 y documentación de escalado horizontal.

### 15.2 Componentes

1. **Script restore_tenant.sh**: Script bash para restaurar datos de un solo tenant desde backup

2. **k6 Performance Tests**: Extender `tests/performance/load_test.js` existente con escenarios multi-tenant

3. **Documentación de Escalado**: Fases 1→2→3 (Single Server → Separated DB → Load Balanced)

### 15.3 Horas Estimadas: 24-32h

---

## 16. FASE 11 — Elevación IA Clase Mundial

### 16.1 Objetivo

Completar las capacidades de IA de clase mundial: Brand Voice Trainer con pipeline de re-entrenamiento, sistema formal de A/B testing de prompts y preparación para multi-modal.

### 16.2 Componentes

1. **Brand Voice Trainer Service**: Pipeline Qdrant embeddings + feedback loop
2. **A/B Testing de Prompts**: Integrar con `jaraba_ab_testing` existente
3. **Multi-modal Preparation**: Interfaces preparadas para voz e imagen

### 16.3 Horas Estimadas: 40-60h

---

## 17. FASE 12 — Lenis Integration Premium

### 17.1 Objetivo

Integrar Lenis smooth scroll en landing pages y homepage para una experiencia de navegación premium.

### 17.2 Arquitectura

```bash
# Instalar Lenis
lando npm install lenis --save-dev
```

**Archivo JS:** `ecosistema_jaraba_theme/js/lenis-scroll.js`

```javascript
/**
 * @file
 * Integración Lenis smooth scroll para landing pages.
 *
 * DIRECTRIZ: Solo se activa en páginas frontend (no admin).
 * Respeta prefers-reduced-motion para accesibilidad.
 */
(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.lenisScroll = {
    attach: function (context) {
      once('lenis-init', 'body', context).forEach(function (body) {
        // Respetar preferencias de accesibilidad
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
          return;
        }

        // Solo activar en páginas frontend (no admin)
        if (body.classList.contains('path-admin')) {
          return;
        }

        const lenis = new Lenis({
          duration: 1.2,
          easing: function (t) { return Math.min(1, 1.001 - Math.pow(2, -10 * t)); },
          smooth: true,
          smoothTouch: false,
        });

        function raf(time) {
          lenis.raf(time);
          requestAnimationFrame(raf);
        }
        requestAnimationFrame(raf);
      });
    }
  };

})(Drupal, once);
```

**Aplicación:**
- Homepage (`page--front.html.twig`)
- Landing pages verticales (`page--agroconecta.html.twig`, etc.)
- Parallax hero sections
- **NO** aplicar en dashboards admin

**Librería:** Registrar en `ecosistema_jaraba_theme.libraries.yml`:

```yaml
lenis-scroll:
  version: 1.0
  js:
    js/lenis-scroll.js: { minified: true }
  dependencies:
    - core/drupal
    - core/once
```

### 17.3 Horas Estimadas: 8-12h

---

## 18. Tabla de Correspondencia con Especificaciones Técnicas

### 18.1 Mapeo Doc 178 — Visitor Journey Complete

| Sección Spec | Componente | Fase | Estado Pre | Implementación |
|--------------|-----------|------|------------|----------------|
| §2 Modelo AIDA | Funnel tracking | F3 | Parcial | Extender `FunnelTrackingService` con naming AIDA |
| §3.1 AWARENESS | Detección vertical | F3 | Hecho | `AvatarDetectionService` operativo |
| §3.2 INTEREST | Tracking scroll/exit | F3 | Hecho | `jaraba_pixels` operativo |
| §3.3 DESIRE - Lead Magnets | 5 lead magnets | F3 | 1/5 hecho | Implementar 4 restantes |
| §3.4 ACTION - Registro | Social OAuth | F3 | Parcial | Instalar social_auth modules |
| §3.5 ACTIVATION | Aha! Moment por vertical | F5 | Parcial | Definir en Onboarding Wizard |
| §3.6 CONVERSION | Upgrade triggers | F2 | Parcial | Completar triggers faltantes |
| §4 Homepage | Selector de vertical | F3 | Parcial | Crear parcial `_vertical-selector.html.twig` |
| §5 Tracking | Eventos page_view, etc. | F3 | Hecho | `jaraba_pixels` operativo |

### 18.2 Mapeo Doc 179 — Tenant Onboarding Wizard

| Sección Spec | Componente | Fase | Estado Pre | Implementación |
|--------------|-----------|------|------------|----------------|
| §2 Estructura 7 pasos | Wizard controller | F5 | 5 rutas | Reestructurar a 7 pasos |
| §3.1 Paso 1: Bienvenida | Template + lógica | F5 | No | Nuevo |
| §3.2 Paso 2: Identidad | Logo + AI colores | F5 | No | `LogoColorExtractorService` |
| §3.3 Paso 3: Datos Fiscales | Validación NIF | F5 | No | Nuevo formulario |
| §3.4 Paso 4: Stripe Connect | Redirect + callback | F5 | Campo existe | Implementar flujo completo |
| §3.5 Paso 5: Equipo | Invitaciones email | F5 | No | Nuevo (saltable) |
| §3.6 Paso 6: Contenido | Primer producto | F5 | No | Por vertical |
| §3.7 Paso 7: Lanzamiento | Confetti + preview | F5 | JS existe | Integrar |
| §4 Persistencia | Progress entity | F5 | `UserOnboardingProgress` | Nuevo `TenantOnboardingProgress` |

### 18.3 Mapeo Doc 180 — Landing Pages Verticales

| Sección Spec | Componente | Fase | Estado Pre | Implementación |
|--------------|-----------|------|------------|----------------|
| §2 Estructura común | 9 parciales | F4 | Parcial | 9 parciales nuevos reutilizables |
| §3 AgroConecta | `/agroconecta` | F4 | Ruta existe | Completar contenido |
| §4 ComercioConecta | `/comercioconecta` | F4 | Ruta existe | Completar contenido |
| §5 ServiciosConecta | `/serviciosconecta` | F4 | Ruta existe | Completar contenido |
| §6 Empleabilidad | `/empleabilidad` | F4 | Ruta existe | Completar contenido |
| §7 Emprendimiento | `/emprendimiento` | F4 | Ruta existe | Completar contenido |

### 18.4 Mapeo Doc 181 — SaaS Admin UX Complete

| Sección Spec | Componente | Fase | Estado Pre | Implementación |
|--------------|-----------|------|------------|----------------|
| §2 Día del Super Admin | Flujo documentado | F6 | Documentado | Controller + Dashboard |
| §3 Dashboard Principal | Admin Center | F6 | No | `AdminCenterController` |
| §4 Command Palette | Cmd+K global | F6 | Solo Page Builder | Extender a global |
| §5 Detalle Tenant | Health breakdown | F6 | Parcial | Completar vista detalle |

### 18.5 Mapeo Doc 182 — Entity Admin Dashboard

| Sección Spec | Componente | Fase | Estado Pre | Implementación |
|--------------|-----------|------|------------|----------------|
| §2 Dashboard Elena | Ruta + Controller | F7 | No | `ProgramDashboardController` extender |
| §3 Grant Tracker | Burn Rate service | F7 | No | `GrantTrackingService` |
| §4 Cohortes | Gestión completa | F7 | Parcial | Extender `CohortAnalysisService` |
| §5 Grant Tracking | Justificación | F7 | No | Alertas + exportación |
| §6 Informes | 5 templates PDF | F7 | PDF genérico | Templates institucionales |

### 18.6 Mapeo Doc 183 — Freemium & Trial Model

| Sección Spec | Componente | Fase | Estado Pre | Implementación |
|--------------|-----------|------|------------|----------------|
| §2 Estrategia | Hybrid Freemium+Trial | F2 | Trial OK | Completar Freemium |
| §3 Límites por vertical | 5 tablas de límites | F2 | Framework genérico | ConfigEntity + enforcement |
| §4 Triggers upgrade | 5 triggers | F2 | 3/5 OK | +primera venta, +invitación |

### 18.7 Mapeo Doc 184 — Merchant Copilot

| Sección Spec | Componente | Fase | Estado Pre | Implementación |
|--------------|-----------|------|------------|----------------|
| §2 Capacidades | 6 acciones | F8 | 3/6 OK | +flash_offer, +social_post IA, +email_promo |
| §3 System Prompt | Prompt merchant | F8 | No | `MerchantCopilotAgent` |
| §4 Ejemplos | Descripciones, ofertas | F8 | Parcial | Completar |

### 18.8 Mapeo Doc 185 — ECA Registry Master

| Sección Spec | Componente | Fase | Estado Pre | Implementación |
|--------------|-----------|------|------------|----------------|
| §2 Convención | ECA-{DOM}-{NUM} | F1 | No | ConfigEntity + catálogo |
| §3.1 Flujos Core | USR, TEN, FIN | F1 | Implementados sin registro | Registrar en catálogo |
| §3.2 Flujos Marketing | MKT-001..006 | F1 | Parcial | Registrar + pendientes |
| §3.3 Flujos Commerce | ORD-001..006 | F1 | Parcial | Registrar + pendientes |

### 18.9 Mapeo Doc 186 — B2B Sales Flow

| Sección Spec | Componente | Fase | Estado Pre | Implementación |
|--------------|-----------|------|------------|----------------|
| §2 Pipeline | 7 etapas + X | F9 | Genérico | Stages predefinidos |
| §3 BANT | 4 criterios + score | F9 | No | Campos en Opportunity |
| §4 Playbook | Acciones por etapa | F9 | No | `SalesPlaybookService` |

### 18.10 Mapeo Doc 187 — Scaling Infrastructure

| Sección Spec | Componente | Fase | Estado Pre | Implementación |
|--------------|-----------|------|------------|----------------|
| §2 Umbrales | Métricas de escalado | F10 | Documentado | Alertas Prometheus |
| §3 Arquitectura horizontal | 3 fases | F10 | Documentado | Config files |
| §4 Backup per tenant | restore_tenant.sh | F10 | No | Script + docs |
| §5 Performance testing | k6 escenarios | F10 | Básico | Extender multi-tenant |

### 18.11 Mapeo IA Arquitectura + Clase Mundial

| Sección Spec | Componente | Fase | Estado Pre | Implementación |
|--------------|-----------|------|------------|----------------|
| Model Router | 3 tiers dinámico | F11 | Implementado | Operativo |
| Agentic Workflows | Engine + Tools | F11 | Implementado | Operativo |
| Brand Voice Trainer | Qdrant + feedback | F11 | Parcial | Pipeline re-training |
| LLM-as-Judge | Quality Evaluator | F11 | Implementado | Operativo |
| A/B Testing Prompts | Integración formal | F11 | Mínimo | Integrar con `jaraba_ab_testing` |
| Multi-modal | Voz + Imagen | F11 | No | Interfaces preparadas |
| Feedback Loop | Training pipeline | F11 | Parcial | Automatizar |

---

## 19. Checklist de Cumplimiento de Directrices del Proyecto

### 19.1 Directrices de Código (§5 y §11 de `00_DIRECTRICES_PROYECTO.md`)

| Directriz | Cómo se cumple en este plan |
|-----------|-----------------------------|
| `declare(strict_types=1)` en todos los PHP | Sí — Todo archivo PHP nuevo |
| Content Entity Pattern completo | Sí — `TenantOnboardingProgress`, `VerticalLandingConfig`, `FreemiumLimits` |
| 4 YAML files (routing, menu, task, action) | Sí — Para cada entidad nueva |
| `EntityChangedTrait` + `EntityOwnerTrait` | Sí — En todas las Content Entities |
| `fieldable = TRUE` con `field_ui_base_route` | Sí — Content Entities editables |
| Access Handler + ListBuilder | Sí — Para cada entidad |
| `AdminHtmlRouteProvider` | Sí — Navegación admin estándar |
| Navigation: `/admin/content` + `/admin/structure` | Sí — Tabs content + Field UI structure |
| Comentarios en español descriptivos | Sí — Todos los bloques de código |
| Variables/funciones en inglés | Sí — Convención técnica |

### 19.2 Directrices de Frontend (§2.2 de `00_DIRECTRICES_PROYECTO.md`)

| Directriz | Cómo se cumple en este plan |
|-----------|-----------------------------|
| SCSS compilado con Dart Sass (`@use`, `color.adjust()`) | Sí — Todos los parciales nuevos |
| Solo `var(--ej-*)` en módulos satélite | Sí — Ningún SCSS nuevo define `$ej-*` |
| `package.json` con scripts de compilación | Sí — Para cada módulo con SCSS |
| Templates Twig limpias sin regiones | Sí — Todas las páginas frontend nuevas |
| `{% include %}` de parciales reutilizables | Sí — 9 parciales landing + selector + upgrade |
| `hook_preprocess_html()` para body classes | Sí — NUNCA `attributes.addClass()` en template |
| Mobile-first full-width | Sí — Layout responsive en todas las páginas |
| Slide-panel modales para CRUD | Sí — Wizard, formularios, ediciones |
| `jaraba_icon()` para iconos (no emojis) | Sí — SVG duotone en todas las interfaces |
| Variables configurables desde UI Drupal | Sí — Theme settings existentes (70+ opciones) |
| Textos traducibles (i18n) | Sí — `$this->t()`, `{% trans %}`, `Drupal.t()` |

### 19.3 Directrices de IA (§2.10 y §4.5 de `00_DIRECTRICES_PROYECTO.md`)

| Directriz | Cómo se cumple en este plan |
|-----------|-----------------------------|
| Solo `@ai.provider` (no HTTP directo) | Sí — `LogoColorExtractorService`, `MerchantCopilotAgent` |
| Claves en Key module | Sí — Nunca hardcoded |
| Rate limiting en endpoints LLM | Sí — Flood API en todos los endpoints |
| Sanitización de prompts | Sí — Whitelist en system prompts |
| Failover multiproveedor | Sí — `PROVIDERS = ['anthropic', 'openai']` |
| Aislamiento Qdrant multi-tenant | Sí — `must` filters con tenant_id |

### 19.4 Directrices de Seguridad (§4 de `00_DIRECTRICES_PROYECTO.md`)

| Directriz | Cómo se cumple |
|-----------|-----------------------------|
| HMAC en webhooks | Sí — Stripe Connect callbacks verificados |
| Autenticación en APIs | Sí — `_user_is_logged_in: 'TRUE'` en todas las rutas API |
| Regex en parámetros de ruta | Sí — Restricciones en todos los paths dinámicos |
| No exponer excepciones internas | Sí — Logger + mensajes genéricos |
| Credenciales nunca en código | Sí — `settings.local.php` / env vars |

### 19.5 Directrices de Arquitectura Theming (§ de `2026-02-05_arquitectura_theming_saas_master.md`)

| Directriz | Cómo se cumple |
|-----------|-----------------------------|
| Federated Design Tokens | Sí — SSOT en core, satélites solo consumen |
| 5 capas de jerarquía | Sí — SCSS → CSS vars → Component → Tenant → Vertical |
| Header SCSS documentación | Sí — Comentarios con @file y comando de compilación |
| Compilación desde Docker | Sí — `lando npx sass ...` |
| No hex hardcodeados | Sí — Todos usan tokens `var(--ej-*)` |

---

## 20. Estrategia de Testing y Verificación

### 20.1 Unit Tests por Fase

| Fase | Tests Requeridos | Framework |
|------|-----------------|-----------|
| F1 | `EcaFlowDefinitionTest` (CRUD) | PHPUnit 11 |
| F2 | `FreemiumLimitsServiceTest`, `UpgradeTriggerServiceTest` | PHPUnit 11 |
| F3 | `LeadMagnetServiceTest` por vertical | PHPUnit 11 |
| F5 | `TenantOnboardingWizardTest`, `LogoColorExtractorServiceTest` | PHPUnit 11 |
| F6 | `AdminCenterControllerTest` | PHPUnit 11 |
| F7 | `GrantTrackingServiceTest`, `InstitutionalReportServiceTest` | PHPUnit 11 |
| F8 | `MerchantCopilotAgentTest` | PHPUnit 11 |
| F9 | `SalesPlaybookServiceTest`, `BantQualificationTest` | PHPUnit 11 |
| F10 | k6 load test escenarios | k6 |

### 20.2 Visual Regression Tests

Extender `tests/visual/backstop.json` con las nuevas páginas:

- Landing AgroConecta (3 viewports)
- Landing ComercioConecta (3 viewports)
- Landing ServiciosConecta (3 viewports)
- Homepage con selector (3 viewports)
- Onboarding Wizard (mobile + desktop)
- Admin Center Dashboard (desktop)

### 20.3 Verificación Manual en Navegador

Para cada fase:
1. Compilar SCSS: `lando npx sass scss/main.scss:css/[output].css --style=compressed`
2. Limpiar caché: `lando drush cr`
3. Verificar en `https://jaraba-saas.lndo.site/` (URL del SaaS en desarrollo)
4. Verificar responsive en 3 viewports mínimo (mobile 375px, tablet 768px, desktop 1440px)
5. Verificar traducciones: `/admin/config/regional/translate`

---

## 21. Roadmap de Ejecución

### Sprint 1 (Semanas 1-2): Fundamentos

| Fase | Entregable | Horas |
|------|-----------|-------|
| F1 | ECA Registry Master (ConfigEntity + catálogo 30 flujos) | 8-12h |
| F2 | Freemium limits por vertical + upgrade triggers | 16-24h |
| | **Subtotal Sprint 1** | **24-36h** |

### Sprint 2 (Semanas 3-4): Adquisición

| Fase | Entregable | Horas |
|------|-----------|-------|
| F3 | Lead magnets 4 verticales + Social OAuth + Homepage selector | 44-56h |
| | **Subtotal Sprint 2** | **44-56h** |

### Sprint 3 (Semanas 5-6): Conversión

| Fase | Entregable | Horas |
|------|-----------|-------|
| F4 | 5 landing pages verticales con 9 secciones | 48-64h |
| | **Subtotal Sprint 3** | **48-64h** |

### Sprint 4 (Semanas 7-8): Activación

| Fase | Entregable | Horas |
|------|-----------|-------|
| F5 | Tenant Onboarding Wizard 7 pasos | 32-40h |
| | **Subtotal Sprint 4** | **32-40h** |

### Sprint 5 (Semanas 9-10): Operaciones

| Fase | Entregable | Horas |
|------|-----------|-------|
| F6 | Admin Center Dashboard + Command Palette global | 24-32h |
| F7 | Entity Admin Dashboard Elena + Grant tracking | 24-32h |
| | **Subtotal Sprint 5** | **48-64h** |

### Sprint 6 (Semanas 11-12): Diferenciación

| Fase | Entregable | Horas |
|------|-----------|-------|
| F8 | Merchant Copilot completo | 20-28h |
| F9 | B2B Sales Flow + BANT + Playbooks | 16-20h |
| | **Subtotal Sprint 6** | **36-48h** |

### Sprint 7 (Semanas 13-14): Escalabilidad y Excelencia

| Fase | Entregable | Horas |
|------|-----------|-------|
| F10 | Scaling Infrastructure | 24-32h |
| F11 | IA Clase Mundial (Brand Voice Trainer + A/B) | 40-60h |
| F12 | Lenis Integration | 8-12h |
| | **Subtotal Sprint 7** | **72-104h** |

### Resumen de Inversión

| Sprint | Semanas | Horas | Coste (€65/h) |
|--------|---------|-------|---------------|
| Sprint 1 | 1-2 | 24-36h | €1,560-2,340 |
| Sprint 2 | 3-4 | 44-56h | €2,860-3,640 |
| Sprint 3 | 5-6 | 48-64h | €3,120-4,160 |
| Sprint 4 | 7-8 | 32-40h | €2,080-2,600 |
| Sprint 5 | 9-10 | 48-64h | €3,120-4,160 |
| Sprint 6 | 11-12 | 36-48h | €2,340-3,120 |
| Sprint 7 | 13-14 | 72-104h | €4,680-6,760 |
| Testing + QA | Transversal | 100-140h | €6,500-9,100 |
| Documentación | Transversal | 30-40h | €1,950-2,600 |
| **TOTAL** | **14 semanas** | **434-592h (+130-180h QA/docs)** | **€28,210-38,480 + €8,450-11,700** |

---

## 22. Métricas de Éxito

### 22.1 KPIs Funcionales

| Dimensión | Métrica | Target |
|-----------|---------|--------|
| UX Visitante | Visitor-to-signup rate | >5% |
| UX Visitante | Lead magnet completion rate | >15% |
| UX Visitante | Bounce rate landing pages | <40% |
| UX Tenant | Onboarding completion rate | >70% |
| UX Tenant | Time to first value | <10 min |
| Conversión | Trial-to-paid rate | >25% |
| Conversión | First sale upgrade trigger conversion | >40% |
| Retención | NRR (Net Revenue Retention) | >100% |
| Admin | Tiempo Morning Check | <5 min |
| Admin | Tenant triage daily | <15 min |
| B2B | Pipeline conversion rate | >15% |
| IA | Token cost reduction via Model Router | >30% |

### 22.2 KPIs Técnicos

| Métrica | Target |
|---------|--------|
| Unit test coverage por fase | >80% |
| Lighthouse Performance Score (landings) | >90 |
| Core Web Vitals (LCP, FID, CLS) | Green |
| p95 latencia API endpoints | <500ms |
| SCSS compilation time | <5s por módulo |
| Zero errores de compilación Dart Sass | 100% |
| Zero textos hardcodeados sin i18n | 100% |

---

## 23. Registro de Cambios

| Fecha | Versión | Descripción |
|-------|---------|-------------|
| 2026-02-12 | 1.0.0 | Plan inicial: 12 fases, mapeo 10 especificaciones + IA + Lenis |

---

**Jaraba Impact Platform SaaS | Plan de Cierre de Gaps Specs 20260128 | Febrero 2026**

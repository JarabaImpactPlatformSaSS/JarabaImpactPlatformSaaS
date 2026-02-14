# Plan de Implementacion — Admin Center Premium (f104)

**Fecha:** 2026-02-13
**Version:** 1.0.0
**Especificacion base:** [20260117f-104_SaaS_Admin_Center_Premium_v1_Claude.md](../tecnicos/20260117f-104_SaaS_Admin_Center_Premium_v1_Claude.md)
**Modulo principal:** `ecosistema_jaraba_core` (extension del controlador existente)
**Estado:** PLANIFICADO
**Estimacion total:** 320-420h (10 sprints de 2 semanas)
**Precedentes:** [20260123d-Bloque_D](20260123d-Bloque_D_Admin_Center_Implementacion_Claude.md), [F6 SaaS Admin UX](2026-02-12_F6_SaaS_Admin_UX_Complete_Doc181_Implementacion.md)

---

## Tabla de Contenidos

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Estado Actual vs Especificacion](#2-estado-actual-vs-especificacion)
3. [Tabla de Correspondencia con Especificaciones Tecnicas](#3-tabla-de-correspondencia-con-especificaciones-tecnicas)
4. [Arquitectura Tecnica](#4-arquitectura-tecnica)
   - [4.1 Estructura de Archivos](#41-estructura-de-archivos)
   - [4.2 Entidades de Contenido](#42-entidades-de-contenido)
   - [4.3 Servicios](#43-servicios)
   - [4.4 Rutas y APIs REST](#44-rutas-y-apis-rest)
   - [4.5 Templates Twig y Parciales](#45-templates-twig-y-parciales)
   - [4.6 SCSS y Design Tokens](#46-scss-y-design-tokens)
   - [4.7 JavaScript y Comportamientos](#47-javascript-y-comportamientos)
   - [4.8 Integracion con Servicios Existentes](#48-integracion-con-servicios-existentes)
5. [Fases de Implementacion](#5-fases-de-implementacion)
   - [FASE 1 — Fundacion y Datos Reales (Sprint 1-2)](#fase-1--fundacion-y-datos-reales-sprint-1-2)
   - [FASE 2 — Gestion de Tenants (Sprint 3-4)](#fase-2--gestion-de-tenants-sprint-3-4)
   - [FASE 3 — Gestion de Usuarios (Sprint 5)](#fase-3--gestion-de-usuarios-sprint-5)
   - [FASE 4 — Centro Financiero (Sprint 6-7)](#fase-4--centro-financiero-sprint-6-7)
   - [FASE 5 — Sistema de Alertas y Playbooks (Sprint 8)](#fase-5--sistema-de-alertas-y-playbooks-sprint-8)
   - [FASE 6 — Analytics, Reports y Logs (Sprint 9)](#fase-6--analytics-reports-y-logs-sprint-9)
   - [FASE 7 — Configuracion Global y Polish (Sprint 10)](#fase-7--configuracion-global-y-polish-sprint-10)
6. [Cumplimiento de Directrices del Proyecto](#6-cumplimiento-de-directrices-del-proyecto)
   - [6.1 Directriz de Textos Traducibles (i18n)](#61-directriz-de-textos-traducibles-i18n)
   - [6.2 Directriz SCSS con Variables Inyectables](#62-directriz-scss-con-variables-inyectables)
   - [6.3 Directriz de Templates Twig Limpias (Zero Region Policy)](#63-directriz-de-templates-twig-limpias-zero-region-policy)
   - [6.4 Directriz de Parciales Reutilizables](#64-directriz-de-parciales-reutilizables)
   - [6.5 Directriz de Variables Configurables desde UI de Drupal](#65-directriz-de-variables-configurables-desde-ui-de-drupal)
   - [6.6 Directriz de Layout Full-Width Mobile-First](#66-directriz-de-layout-full-width-mobile-first)
   - [6.7 Directriz de Modales (Slide-Panel)](#67-directriz-de-modales-slide-panel)
   - [6.8 Directriz de Body Classes via hook_preprocess_html](#68-directriz-de-body-classes-via-hook_preprocess_html)
   - [6.9 Directriz de Iconografia SVG](#69-directriz-de-iconografia-svg)
   - [6.10 Directriz de Paleta de Colores](#610-directriz-de-paleta-de-colores)
   - [6.11 Directriz de Entidades con Field UI y Views](#611-directriz-de-entidades-con-field-ui-y-views)
   - [6.12 Directriz de Navegacion Admin](#612-directriz-de-navegacion-admin)
   - [6.13 Directriz de Tenant sin Acceso a Admin Theme](#613-directriz-de-tenant-sin-acceso-a-admin-theme)
   - [6.14 Directriz de Dart Sass Moderno](#614-directriz-de-dart-sass-moderno)
   - [6.15 Directriz de Compilacion en Docker](#615-directriz-de-compilacion-en-docker)
7. [Procedimientos de Verificacion](#7-procedimientos-de-verificacion)
8. [Metricas de Exito](#8-metricas-de-exito)
9. [Dependencias y Riesgos](#9-dependencias-y-riesgos)
10. [Glosario](#10-glosario)
11. [Referencias Cruzadas](#11-referencias-cruzadas)
12. [Registro de Cambios](#12-registro-de-cambios)

---

## 1. Resumen Ejecutivo

### 1.1 Vision

El Admin Center Premium es el centro neuralgico del SaaS Jaraba Impact Platform.
Desde aqui, el Super Admin controla todos los aspectos del ecosistema multi-tenant:
tenants, usuarios, finanzas, alertas, analytics, configuracion y logs.

La especificacion f104 define una interfaz de nivel enterprise que posiciona
el producto por encima de Salesforce Admin Console, HubSpot Settings y Stripe Dashboard.

### 1.2 Estado actual

La implementacion actual cubre aproximadamente el **18%** de la especificacion f104:

| Componente implementado | Estado |
|-------------------------|--------|
| AdminCenterController con 6 KPIs | Funcional pero con valores hardcoded (churn=0, health=0) |
| Template admin-center-dashboard.html.twig | Estructura BEM correcta |
| CSS admin-center.css | 502 lineas, responsive, design tokens |
| Command Palette (Cmd+K) | Completo: 11 comandos, busqueda API, atajos |
| Search API (/api/v1/admin/search) | Funcional: busca tenants y usuarios |

### 1.3 Lo que falta (82%)

| Modulo | Completitud actual | Gap |
|--------|-------------------|-----|
| Dashboard con datos reales y charts | 25% | Sparklines, charts, drill-down, trends reales |
| Gestion de Tenants | 10% | DataTable, Health Score real, Detail 360, Config |
| Gestion de Usuarios | 5% | Directorio, Sessions, Activity Log |
| Centro Financiero | 10% | Revenue UI completa, SaaS Metrics detalladas, Stripe |
| Analytics y Reports | 0% | Report Builder, Scheduling, Export |
| Sistema de Alertas | 15% | Categories, States, Playbooks |
| Configuracion Global | 0% | Settings, Plans, Integrations, API Keys |
| Logs y Auditoria | 15% | Activity Log unificado, Error Log |
| Real-time (WebSocket) | 0% | Infraestructura WS completa |

### 1.4 Principio arquitectonico clave

> Este plan NO construye un frontend React separado como sugiere la spec f104.
> El SaaS Jaraba Impact Platform usa **Drupal 11 como frontend engine** con:
> - Templates Twig limpias (Zero Region Policy)
> - SCSS con variables inyectables via `var(--ej-*)` configuradas desde UI de Drupal
> - JavaScript vanilla con `Drupal.behaviors` y `once()`
> - Chart.js para visualizaciones (ya integrado en Health y FinOps dashboards)
> - Slide-panel para todas las acciones CRUD modales
>
> Esto es coherente con el resto de la plataforma y evita la complejidad de mantener
> un stack React separado dentro de un CMS Drupal.

---

## 2. Estado Actual vs Especificacion

### 2.1 Matriz de cumplimiento detallada

| Seccion Spec f104 | Componente | Implementado | Gaps | Prioridad |
|-------------------|------------|-------------|------|-----------|
| 2.1 Navegacion | Sidebar colapsable 240px/64px | Solo quick links en sidebar estatico 300px | Sidebar nav con collapse, tooltips mini | P1 |
| 2.2 Layout | Tres columnas | Dos columnas (main + sidebar) | Panel contextual derecho opcional | P2 |
| 2.3 TopBar | Breadcrumbs + search + user menu | No existe (usa Drupal admin theme bar) | TopBar propia con search inline | P2 |
| 2.3 CommandPalette | Cmd+K | **COMPLETO** (11 comandos, search API) | Solo falta agrupacion por tipo de resultado | P3 |
| 3.1 Paleta | 12 tokens semanticos | Usa `--ej-*` tokens propios | Naming divergente pero funcional | P3 |
| 3.2 Tipografia | Inter + JetBrains Mono, 10 niveles | Inter configurado, 5-6 niveles | Completar escala tipografica, JetBrains Mono | P2 |
| 3.3 Botones | 5 variantes | 2 variantes (Primary, Secondary) | Ghost, Danger, Icon button | P2 |
| 3.3 Cards | 5 variantes | 2 variantes (KPI, stat) | Elevated, Interactive, Alert Card | P1 |
| 3.3 Tables | Enterprise (sort, filter, pagination, selection) | No existe DataTable | Componente DataTable completo | P0 |
| 3.4 Iconos | Lucide Icons | Emojis | Migrar a sistema SVG `jaraba_icon()` | P1 |
| 3.4 Graficos | Recharts/ECharts | Chart.js en dashboards satelite | Chart.js en Admin Center | P1 |
| 4.1 KPI MRR | Currency + sparkline + drill-down | Valor numerico, sin sparkline ni click | Sparkline inline, click navega | P0 |
| 4.1 KPI ARR | Currency + sparkline | Valor numerico | Idem | P1 |
| 4.1 KPI NRR | Percentage vs benchmark 105% | NO implementado (sustituido por MAU) | Implementar NRR real | P1 |
| 4.1 KPI Churn | Percentage vs benchmark 3% | Siempre devuelve 0 | Calcular churn real | P0 |
| 4.1 KPI Alerts | Count por severidad | Sustituido por Health Avg (tambien=0) | Implementar Open Alerts KPI | P1 |
| 4.2 Revenue Trend | Area chart 12 meses | No existe | Chart.js area chart | P1 |
| 4.2 Tenant Distribution | Donut chart por vertical | No existe | Chart.js doughnut | P2 |
| 4.2 Top 10 Tenants | Bar chart horizontal | No existe | Chart.js horizontal bar | P2 |
| 4.3 Alertas Activas | Widget con severity colors | Basico sin colores de severidad | Severity + actions (View, Dismiss) | P1 |
| 5.1 Lista Tenants | DataTable enterprise | Solo stats agregados | DataTable con sorting/filtering/pagination | P0 |
| 5.2 Detalle Tenant | Vista 360 con tabs | Existe vista self-service (no admin) | Admin detail con tabs en slide-panel | P0 |
| 5.3 Health Score | 6 factores ponderados | Siempre=0, no hay calculo | Implementar calculo real (CustomerHealth) | P0 |
| 5.4 Config por Tenant | Limits, features, branding | No existe | Formulario config por tenant | P1 |
| 6.1 Directorio Users | Lista global filtrable | No existe | DataTable de usuarios | P1 |
| 6.2 RBAC | Matriz permisos | Existe en `/admin/people/rbac-matrix` separado | Integrar en Admin Center | P2 |
| 6.3 Sessions | Active sessions panel | No existe | Panel de sesiones activas | P2 |
| 7.1 Revenue Dashboard | Metricas completas | Solo MRR/ARR en KPIs | Revenue breakdown, cohort analysis | P1 |
| 7.2 SaaS Metrics | GRR, NRR, LTV, CAC, ARPU | Backend FOC calcula, sin UI | Tabla de metricas con benchmarks | P1 |
| 7.3 Stripe Connect | Panel gestion | No existe | Connected accounts, transactions | P2 |
| 8.1 Report Builder | Drag-and-drop | No existe | Visor de reportes (no full builder) | P3 |
| 8.2 Reports Programados | Scheduling | No existe | Cron-based report generation | P3 |
| 8.3 Exportacion | CSV/Excel/PDF | No existe | Export en todos los DataTables | P1 |
| 9.1 Notificaciones | Centro unificado | Widget basico en dashboard | Centro con categorias y estados | P1 |
| 9.2 Reglas Alertas | Editor visual | Entity form estandar Drupal | Formulario mejorado en slide-panel | P2 |
| 9.3 Playbooks | Secuencias automaticas | No existe | Playbook entity + ECA triggers | P2 |
| 10.1 Settings | Platform config | Disperso en Drupal config | Vista unificada | P2 |
| 10.2 Billing Plans | CRUD planes | SaasPlan entity existe sin UI frontend | Formulario en slide-panel | P1 |
| 10.4 API Keys | Gestion keys | No existe | API Key entity | P3 |
| 11.1 Activity Log | Log cronologico | Compliance dashboard separado | Vista unificada en Admin Center | P2 |
| 11.2 Audit Trail | Inmutable, firmado | AuditLog entity existe | Visualizacion mejorada | P2 |
| 11.3 Error Log | Agrupacion errores | No existe | Log viewer con filtros | P3 |
| 13.4 WebSocket | Eventos real-time | No existe | Mercure o SSE como alternativa ligera | P3 |

### 2.2 Dependencias resueltas (ya implementadas)

Estos servicios y entidades ya existen y seran consumidos por el Admin Center:

| Servicio/Entidad | Modulo | Listo para consumir |
|------------------|--------|---------------------|
| `jaraba_foc.saas_metrics` | jaraba_foc | `calculateMRR()`, `calculateARR()`, metricas SaaS |
| `jaraba_customer_success.health_calculator` | jaraba_customer_success | `calculate(tenant_id)` |
| `jaraba_customer_success.churn_prediction` | jaraba_customer_success | `predict(tenant_id)` |
| `AlertRule` entity | ecosistema_jaraba_core | 5 metricas, 4 operadores, cooldown |
| `CustomerHealth` entity | jaraba_customer_success | score 0-100, 5 componentes, trend |
| `Tenant` entity | ecosistema_jaraba_core | name, vertical, plan, domain, Stripe IDs |
| `SaasPlan` entity | ecosistema_jaraba_core | pricing, features, limits |
| `DesignTokenConfig` entity | ecosistema_jaraba_core | tokens por scope |
| `AuditLog` entity | ecosistema_jaraba_core | append-only compliance log |
| `ecosistema_jaraba_core.impersonation` | ecosistema_jaraba_core | `start(uid)` con audit |
| `ecosistema_jaraba_core.alerting` | ecosistema_jaraba_core | Slack/Teams webhooks |
| `FinancialTransaction` entity | jaraba_foc | ledger inmutable |
| `FocMetricSnapshot` entity | jaraba_foc | snapshots diarios |

---

## 3. Tabla de Correspondencia con Especificaciones Tecnicas

### 3.1 Documentos de especificacion aplicables

| ID Doc | Archivo | Relacion con Admin Center | Secciones de aplicacion |
|--------|---------|---------------------------|------------------------|
| **f104** | `20260117f-104_SaaS_Admin_Center_Premium_v1_Claude.md` | Documento principal | Todas (secciones 1-14) |
| **f102** | `20260117f-102_Industry_Style_Presets_Premium_Implementation_v1_Claude.md` | Design tokens que alimentan el Admin Center | Sec 3 (Design System) |
| **f102-B** | `20260117f-102_Industry_Style_Presets_Premium_Implementation_v1_AnexoB_Claude.md` | Detalles de paletas por vertical | Sec 3.1 (colores por vertical) |
| **FOC** | `20260113d-FOC_Documento_Tecnico_Definitivo_v2_Claude.md` | Backend del modulo financiero | Sec 7 (Centro Financiero) |
| **FOC-Int** | `20260113-FOC_Especificacion_Tecnica_Integracion.md` | Integracion Stripe Connect | Sec 7.3 (Stripe Console) |
| **f163** | `20260126d-163_Bloques_Premium_Anexo_Tecnico_EDI_v1_Claude.md` | Bloques premium gestionados desde Admin | Sec 5.4 (Feature flags) |
| **f181** | `20260130c-181_Premium_Preview_System_v1_Claude.md` | Preview en onboarding de tenants | Sec 5.2 (Detalle tenant) |

### 3.2 Directrices del proyecto aplicables

| Directriz | Archivo | Impacto en Admin Center |
|-----------|---------|------------------------|
| Arquitectura Theming | `docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md` | SCSS, design tokens, compilacion |
| SCSS y Estilos | `.agent/workflows/scss-estilos.md` | Iconos SVG, paleta, Dart Sass |
| Frontend Page Pattern | `.agent/workflows/frontend-page-pattern.md` | Zero Region Policy, clean_content |
| Slide-Panel Modales | `.agent/workflows/slide-panel-modales.md` | CRUD en modales |
| Custom Modules | `.agent/workflows/drupal-custom-modules.md` | Entidades, routing, Field UI |
| i18n Traducciones | `.agent/workflows/i18n-traducciones.md` | Textos traducibles |
| Auditoria Exhaustiva | `.agent/workflows/auditoria-exhaustiva.md` | 7 verificaciones criticas |
| ECA y Hooks | `.agent/workflows/drupal-eca-hooks.md` | Automatizaciones via hooks |
| Browser Verification | `.agent/workflows/browser-verification.md` | Verificacion post-cambio |
| SDC Components | `.agent/workflows/sdc-components.md` | Componentes reutilizables |
| AI Integration | `.agent/workflows/ai-integration.md` | AI provider pattern |

### 3.3 Documentos previos de implementacion

| Documento previo | Estado | Relacion |
|------------------|--------|----------|
| `20260123d-Bloque_D_Admin_Center_Implementacion_Claude.md` | Plan original (sin ejecutar completo) | Este plan lo supersede |
| `2026-02-12_F6_SaaS_Admin_UX_Complete_Doc181_Implementacion.md` | EJECUTADO — Controller + Command Palette | Base completada, este plan extiende |
| `20260213-Plan_Remediacion_Auditoria_Integral_v1.md` | EN PROGRESO — 23/65 hallazgos resueltos | Prerequisitos de seguridad cumplidos |

---

## 4. Arquitectura Tecnica

### 4.1 Estructura de Archivos

Todos los archivos se crean dentro de modulos existentes. NO se crean modulos nuevos
salvo para entidades estrictamente necesarias que no encajen en la estructura actual.

```
web/modules/custom/ecosistema_jaraba_core/
├── src/Controller/
│   └── AdminCenterController.php          ← EXTENDER (ya existe)
│
├── src/Service/
│   ├── AdminCenterAggregatorService.php   ← NUEVO: agrega KPIs reales
│   └── TenantHealthAggregator.php         ← NUEVO: health scores batch
│
├── templates/
│   ├── admin-center-dashboard.html.twig   ← EXTENDER (ya existe)
│   ├── admin-center-tenants.html.twig     ← NUEVO: lista de tenants
│   ├── admin-center-tenant-detail.html.twig ← NUEVO: detalle 360
│   ├── admin-center-users.html.twig       ← NUEVO: directorio usuarios
│   ├── admin-center-finance.html.twig     ← NUEVO: centro financiero
│   ├── admin-center-alerts.html.twig      ← NUEVO: centro alertas
│   ├── admin-center-logs.html.twig        ← NUEVO: logs unificados
│   └── admin-center-settings.html.twig    ← NUEVO: config global
│
├── scss/
│   ├── _admin-center-dashboard.scss       ← NUEVO: reemplaza admin-center.css
│   ├── _admin-center-datatable.scss       ← NUEVO: componente DataTable
│   ├── _admin-center-charts.scss          ← NUEVO: estilos de graficos
│   ├── _admin-center-sidebar-nav.scss     ← NUEVO: sidebar colapsable
│   ├── _admin-center-tenants.scss         ← NUEVO: pagina tenants
│   ├── _admin-center-finance.scss         ← NUEVO: pagina financiera
│   ├── _admin-center-alerts.scss          ← NUEVO: centro alertas
│   └── main.scss                          ← MODIFICAR (agregar imports)
│
├── js/
│   ├── admin-command-palette.js           ← YA EXISTE (mantener)
│   ├── admin-center-charts.js             ← NUEVO: Chart.js dashboards
│   ├── admin-center-datatable.js          ← NUEVO: sorting/filtering/pagination
│   ├── admin-center-sidebar.js            ← NUEVO: collapse/expand sidebar
│   └── admin-center-export.js             ← NUEVO: export CSV/PDF
│
├── css/
│   └── ecosistema-jaraba-core.css         ← RECOMPILAR con nuevos parciales
│
├── images/icons/
│   ├── ui/
│   │   ├── sidebar-collapse.svg           ← NUEVO
│   │   ├── sidebar-collapse-duotone.svg   ← NUEVO
│   │   ├── chart-line.svg                 ← NUEVO (si no existe)
│   │   ├── chart-line-duotone.svg         ← NUEVO (si no existe)
│   │   ├── export.svg                     ← NUEVO
│   │   ├── export-duotone.svg             ← NUEVO
│   │   ├── filter.svg                     ← NUEVO
│   │   └── filter-duotone.svg             ← NUEVO
│   ├── business/
│   │   ├── tenant.svg                     ← NUEVO
│   │   ├── tenant-duotone.svg             ← NUEVO
│   │   ├── health-score.svg               ← NUEVO
│   │   └── health-score-duotone.svg       ← NUEVO
│   └── analytics/
│       ├── kpi.svg                        ← NUEVO
│       ├── kpi-duotone.svg                ← NUEVO
│       ├── revenue.svg                    ← NUEVO
│       └── revenue-duotone.svg            ← NUEVO
│
├── ecosistema_jaraba_core.routing.yml     ← EXTENDER (nuevas rutas)
├── ecosistema_jaraba_core.services.yml    ← EXTENDER (nuevo servicio)
├── ecosistema_jaraba_core.libraries.yml   ← EXTENDER (nuevas libraries)
└── ecosistema_jaraba_core.module          ← EXTENDER (hook_theme, preprocess)

web/themes/custom/ecosistema_jaraba_theme/
├── templates/
│   ├── page--admin-center.html.twig       ← NUEVO: layout limpio admin center
│   └── partials/
│       ├── _admin-center-nav.html.twig    ← NUEVO: sidebar navegacion
│       ├── _admin-center-topbar.html.twig ← NUEVO: barra superior
│       └── _admin-center-kpi-card.html.twig ← NUEVO: scorecard reutilizable
│
├── scss/
│   ├── components/
│   │   └── _admin-center.scss             ← NUEVO: estilos tema
│   └── main.scss                          ← MODIFICAR (agregar import)
│
└── ecosistema_jaraba_theme.theme          ← EXTENDER (preprocess_html)
```

**Logica de decision: template en modulo vs tema:**
- Templates de **contenido** (datos del controller) van en el **modulo** (`ecosistema_jaraba_core/templates/`)
- Templates de **layout de pagina** y **parciales de navegacion** van en el **tema** (`ecosistema_jaraba_theme/templates/`)

### 4.2 Entidades de Contenido

#### 4.2.1 Entidades existentes que se consumen (NO crear)

| Entidad | Modulo | Uso en Admin Center |
|---------|--------|---------------------|
| `Tenant` | ecosistema_jaraba_core | Lista, detalle, stats |
| `SaasPlan` | ecosistema_jaraba_core | Plans management |
| `AlertRule` | ecosistema_jaraba_core | Alertas activas, configuracion |
| `CustomerHealth` | jaraba_customer_success | Health scores por tenant |
| `FinancialTransaction` | jaraba_foc | Revenue data |
| `FocMetricSnapshot` | jaraba_foc | Metricas historicas |
| `AuditLog` | ecosistema_jaraba_core | Audit trail |
| `Vertical` | ecosistema_jaraba_core | Clasificacion tenants |
| `Feature` | ecosistema_jaraba_core | Feature flags |
| `DesignTokenConfig` | ecosistema_jaraba_core | Theming por tenant |

#### 4.2.2 Entidad nueva: AdminActivityLog (ContentEntity)

**Justificacion:** La spec f104 seccion 11.1 define un log de actividad con campos
especificos (Actor, Action, Resource Type, Resource ID, Changes JSON, IP, User Agent)
que difiere del AuditLog existente (orientado a compliance). Se necesita una entidad
dedicada para el log de actividad administrativa.

```
Ubicacion: ecosistema_jaraba_core/src/Entity/AdminActivityLog.php

Campos base:
- id (serial)
- uuid
- actor (entity_reference → user)
- action (string: create|update|delete|login|logout|impersonate|config_change)
- resource_type (string: tenant|user|plan|alert|config)
- resource_id (string: UUID del recurso afectado)
- changes (map: JSON diff before/after)
- ip_address (string)
- user_agent (string)
- created (created timestamp)

Handlers:
- list_builder: AdminActivityLogListBuilder
- views_data: EntityViewsData
- access: AdminActivityLogAccessControlHandler

Links:
- collection: /admin/content/activity-log
- canonical: /admin/content/activity-log/{admin_activity_log}

field_ui_base_route: entity.admin_activity_log.settings
  → /admin/structure/admin-activity-log

Menu links:
- *.links.menu.yml: parent system.admin_structure (para Field UI)
- *.links.task.yml: tab en /admin/content
```

**Checklist entidad (directriz drupal-custom-modules.md):**
- [ ] Annotation `@ContentEntityType` completa
- [ ] Handler `views_data` = `EntityViewsData`
- [ ] Handler `access` definido
- [ ] `field_ui_base_route` definido
- [ ] 4 archivos YAML: routing, menu, task, action
- [ ] Database indexes via `->addIndex()` para actor, resource_type, created
- [ ] `tenant_id` como `entity_reference` (NO integer)

### 4.3 Servicios

#### 4.3.1 AdminCenterAggregatorService (NUEVO)

**Proposito:** Centralizar la recoleccion de KPIs, metricas y datos de todos los
modulos en un unico servicio que el controller consume.

**Ubicacion:** `ecosistema_jaraba_core/src/Service/AdminCenterAggregatorService.php`

```yaml
# En ecosistema_jaraba_core.services.yml
ecosistema_jaraba_core.admin_center_aggregator:
  class: Drupal\ecosistema_jaraba_core\Service\AdminCenterAggregatorService
  arguments:
    - '@entity_type.manager'
    - '@database'
    - '@logger.channel.ecosistema_jaraba_core'
    # Servicios opcionales (nullable)
  calls:
    - [setSaasMetrics, ['@?jaraba_foc.saas_metrics']]
    - [setHealthCalculator, ['@?jaraba_customer_success.health_calculator']]
    - [setChurnPrediction, ['@?jaraba_customer_success.churn_prediction']]
```

**Metodos publicos:**

```php
// KPIs con datos reales y comparativa periodo anterior
public function getKpis(): array;

// Tenant stats desglosados por status real
public function getTenantStats(): array;

// Top N tenants por MRR con health score
public function getTopTenants(int $limit = 10): array;

// Serie temporal de revenue (12 meses)
public function getRevenueTrend(int $months = 12): array;

// Distribucion de tenants por vertical
public function getTenantDistribution(): array;

// Alertas activas con severidad y timestamp
public function getActiveAlerts(int $limit = 10): array;

// Health score promedio con breakdown
public function getAverageHealthScore(): array;

// Churn rate real (ultimos 30 dias)
public function getChurnRate(): float;

// Net Revenue Retention (ultimos 30 dias)
public function getNetRevenueRetention(): float;

// Metricas SaaS completas (GRR, NRR, LTV, CAC, ARPU)
public function getSaasMetrics(): array;

// Lista paginada de tenants con filtros
public function getTenantsList(array $filters, int $page, int $pageSize): array;

// Detalle completo de tenant
public function getTenantDetail(string $tenantId): array;

// Lista paginada de usuarios globales
public function getUsersList(array $filters, int $page, int $pageSize): array;
```

**Patron de inyeccion de dependencias:**

> Se usa `calls` con servicios opcionales (`@?service`) en vez de constructor injection
> directa, porque los modulos jaraba_foc y jaraba_customer_success pueden no estar
> habilitados. El service verifica disponibilidad antes de llamar:
> ```php
> public function setSaasMetrics(?SaasMetricsService $service): void {
>     $this->saasMetrics = $service;
> }
>
> protected function getMrr(): float {
>     return $this->saasMetrics?->calculateMRR() ?? 0.0;
> }
> ```

#### 4.3.2 Inyeccion de dependencias correcta en AdminCenterController

**Problema actual:** El controller usa `\Drupal::hasService()` y `\Drupal::database()`
(service locator anti-pattern). Se debe refactorizar para inyectar el nuevo
`AdminCenterAggregatorService` por constructor.

```php
class AdminCenterController extends ControllerBase {
  public function __construct(
    protected LoggerInterface $logger,
    protected AdminCenterAggregatorService $aggregator,
  ) {}

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('logger.channel.ecosistema_jaraba_core'),
      $container->get('ecosistema_jaraba_core.admin_center_aggregator'),
    );
  }
}
```

### 4.4 Rutas y APIs REST

#### 4.4.1 Nuevas rutas de pagina

Todas las paginas del Admin Center comparten la misma template de layout
(`page--admin-center.html.twig`) con sidebar de navegacion propia.

```yaml
# En ecosistema_jaraba_core.routing.yml

# --- PAGINAS DEL ADMIN CENTER ---

ecosistema_jaraba_core.admin_center.tenants:
  path: '/admin/jaraba/center/tenants'
  defaults:
    _controller: '\Drupal\ecosistema_jaraba_core\Controller\AdminCenterController::tenants'
    _title: 'Tenants'
  requirements:
    _permission: 'administer site configuration'
  options:
    _admin_route: TRUE

ecosistema_jaraba_core.admin_center.tenant_detail:
  path: '/admin/jaraba/center/tenants/{tenant_id}'
  defaults:
    _controller: '\Drupal\ecosistema_jaraba_core\Controller\AdminCenterController::tenantDetail'
    _title: 'Detalle de Tenant'
  requirements:
    _permission: 'administer site configuration'
    tenant_id: '\d+'
  options:
    _admin_route: TRUE

ecosistema_jaraba_core.admin_center.users:
  path: '/admin/jaraba/center/users'
  defaults:
    _controller: '\Drupal\ecosistema_jaraba_core\Controller\AdminCenterController::users'
    _title: 'Usuarios'
  requirements:
    _permission: 'administer site configuration'
  options:
    _admin_route: TRUE

ecosistema_jaraba_core.admin_center.finance:
  path: '/admin/jaraba/center/finance'
  defaults:
    _controller: '\Drupal\ecosistema_jaraba_core\Controller\AdminCenterController::finance'
    _title: 'Centro Financiero'
  requirements:
    _permission: 'administer site configuration'
  options:
    _admin_route: TRUE

ecosistema_jaraba_core.admin_center.alerts:
  path: '/admin/jaraba/center/alerts'
  defaults:
    _controller: '\Drupal\ecosistema_jaraba_core\Controller\AdminCenterController::alerts'
    _title: 'Alertas'
  requirements:
    _permission: 'administer site configuration'
  options:
    _admin_route: TRUE

ecosistema_jaraba_core.admin_center.logs:
  path: '/admin/jaraba/center/logs'
  defaults:
    _controller: '\Drupal\ecosistema_jaraba_core\Controller\AdminCenterController::logs'
    _title: 'Logs y Auditoria'
  requirements:
    _permission: 'administer site configuration'
  options:
    _admin_route: TRUE

ecosistema_jaraba_core.admin_center.settings:
  path: '/admin/jaraba/center/settings'
  defaults:
    _controller: '\Drupal\ecosistema_jaraba_core\Controller\AdminCenterController::settings'
    _title: 'Configuracion'
  requirements:
    _permission: 'administer site configuration'
  options:
    _admin_route: TRUE
```

#### 4.4.2 Nuevas APIs REST (JSON)

```yaml
# APIs para alimentar DataTables y Charts via AJAX/fetch

ecosistema_jaraba_core.admin_center.api.tenants:
  path: '/api/v1/admin/tenants'
  defaults:
    _controller: '\Drupal\ecosistema_jaraba_core\Controller\AdminCenterApiController::tenants'
  requirements:
    _permission: 'administer site configuration'
  methods: [GET]

ecosistema_jaraba_core.admin_center.api.tenant_detail:
  path: '/api/v1/admin/tenants/{tenant_id}'
  defaults:
    _controller: '\Drupal\ecosistema_jaraba_core\Controller\AdminCenterApiController::tenantDetail'
  requirements:
    _permission: 'administer site configuration'
    tenant_id: '\d+'
  methods: [GET]

ecosistema_jaraba_core.admin_center.api.users:
  path: '/api/v1/admin/users'
  defaults:
    _controller: '\Drupal\ecosistema_jaraba_core\Controller\AdminCenterApiController::users'
  requirements:
    _permission: 'administer site configuration'
  methods: [GET]

ecosistema_jaraba_core.admin_center.api.revenue_trend:
  path: '/api/v1/admin/revenue-trend'
  defaults:
    _controller: '\Drupal\ecosistema_jaraba_core\Controller\AdminCenterApiController::revenueTrend'
  requirements:
    _permission: 'administer site configuration'
  methods: [GET]

ecosistema_jaraba_core.admin_center.api.kpis:
  path: '/api/v1/admin/kpis'
  defaults:
    _controller: '\Drupal\ecosistema_jaraba_core\Controller\AdminCenterApiController::kpis'
  requirements:
    _permission: 'administer site configuration'
  methods: [GET]

ecosistema_jaraba_core.admin_center.api.export:
  path: '/api/v1/admin/export/{type}'
  defaults:
    _controller: '\Drupal\ecosistema_jaraba_core\Controller\AdminCenterApiController::export'
  requirements:
    _permission: 'administer site configuration'
    type: 'tenants|users|revenue|alerts|logs'
  methods: [GET]

ecosistema_jaraba_core.admin_center.api.tenant_impersonate:
  path: '/api/v1/admin/tenants/{tenant_id}/impersonate'
  defaults:
    _controller: '\Drupal\ecosistema_jaraba_core\Controller\AdminCenterApiController::impersonate'
  requirements:
    _permission: 'administer site configuration'
    tenant_id: '\d+'
  methods: [POST]
```

### 4.5 Templates Twig y Parciales

#### 4.5.1 Layout de pagina: `page--admin-center.html.twig`

**Ubicacion:** `ecosistema_jaraba_theme/templates/page--admin-center.html.twig`

Esta template se aplica a TODAS las rutas bajo `/admin/jaraba/center/*`.
Es un layout limpio (Zero Region Policy) con sidebar de navegacion propia.

```twig
{#
/**
 * @file
 * Admin Center Premium — Layout completo para Super Admin.
 *
 * Zero Region Policy: usa {{ clean_content }} en vez de {{ page.content }}.
 * Sidebar de navegacion propia (no la de Drupal admin).
 * Full-width responsive, mobile-first.
 *
 * Variables:
 *   - clean_content: Render del controller (system_main_block only)
 *   - clean_messages: Status/warning/error messages
 *   - admin_center_nav: Items de navegacion del sidebar
 *   - admin_center_active: Seccion activa actual
 *   - theme_settings: Configuracion del tema
 *
 * Parciales usados:
 *   - _admin-center-nav.html.twig (sidebar)
 *   - _admin-center-topbar.html.twig (barra superior)
 *
 * Spec f104, Seccion 2 (Arquitectura del Admin Center).
 */
#}

{{ attach_library('ecosistema_jaraba_core/admin-center') }}
{{ attach_library('ecosistema_jaraba_core/admin-command-palette') }}

<!DOCTYPE html>
<html{{ html_attributes }}>
<head>
  <head-placeholder token="{{ placeholder_token }}">
  <title>{{ head_title|safe_join(' | ') }}</title>
  <css-placeholder token="{{ placeholder_token }}">
  <js-placeholder token="{{ placeholder_token }}">
</head>

<body{{ attributes }}>
  <a href="#main-content" class="visually-hidden focusable skip-link">
    {% trans %}Saltar al contenido principal{% endtrans %}
  </a>

  <div class="admin-center-layout">
    {# Sidebar de navegacion (colapsable) #}
    {% include '@ecosistema_jaraba_theme/partials/_admin-center-nav.html.twig' with {
      nav_items: admin_center_nav,
      active_section: admin_center_active,
    } %}

    {# Area principal #}
    <div class="admin-center-layout__main">
      {# Top bar #}
      {% include '@ecosistema_jaraba_theme/partials/_admin-center-topbar.html.twig' %}

      {# Messages #}
      {% if clean_messages %}
        <div class="admin-center-layout__messages">
          {{ clean_messages }}
        </div>
      {% endif %}

      {# Controller content #}
      <main id="main-content" class="admin-center-layout__content" role="main">
        {{ clean_content }}
      </main>
    </div>
  </div>

  <js-bottom-placeholder token="{{ placeholder_token }}">
</body>
</html>
```

#### 4.5.2 Parciales reutilizables

**Antes de crear un parcial, verificar si ya existe uno equivalente.**

| Parcial | Reutilizado en | Contenido |
|---------|----------------|-----------|
| `_admin-center-nav.html.twig` | Todas las paginas admin center | Sidebar con iconos SVG, labels, badges, collapse |
| `_admin-center-topbar.html.twig` | Todas las paginas admin center | Breadcrumbs, search inline, user menu |
| `_admin-center-kpi-card.html.twig` | Dashboard, Finance, Tenant Detail | Scorecard con valor, trend, sparkline, click |

**Parciales existentes del tema que se reutilizan (NO duplicar):**

| Parcial existente | Uso en Admin Center |
|-------------------|---------------------|
| `_header.html.twig` | NO se usa — Admin Center tiene su propio layout |
| `_footer.html.twig` | NO se usa — Admin Center no tiene footer publico |
| `_slide-panel.html.twig` | SI — para crear/editar tenants, alertas, etc. |

#### 4.5.3 Parcial: `_admin-center-nav.html.twig`

```twig
{#
/**
 * @file
 * Admin Center — Sidebar de navegacion colapsable.
 *
 * Variables:
 *   - nav_items: Array de items [{label, url, icon_category, icon_name, shortcut, badge}]
 *   - active_section: String con la seccion activa
 *
 * Iconos: usa jaraba_icon() con variant outline (sidebar normal)
 * y duotone (item activo).
 *
 * Spec f104, Seccion 2.1 (Estructura de Navegacion).
 */
#}

<aside class="admin-center-nav" data-admin-nav aria-label="{% trans %}Navegacion Admin Center{% endtrans %}">
  <div class="admin-center-nav__header">
    <a href="/admin/jaraba/center" class="admin-center-nav__logo">
      {{ jaraba_icon('ui', 'layout-navbar', { size: '24px', color: 'corporate' }) }}
      <span class="admin-center-nav__logo-text">{% trans %}Admin Center{% endtrans %}</span>
    </a>
    <button class="admin-center-nav__toggle" data-admin-nav-toggle
            aria-label="{% trans %}Colapsar sidebar{% endtrans %}">
      {{ jaraba_icon('ui', 'sidebar-collapse', { size: '20px' }) }}
    </button>
  </div>

  <nav class="admin-center-nav__menu">
    {% for item in nav_items %}
      {% set is_active = active_section == item.section %}
      <a href="{{ item.url }}"
         class="admin-center-nav__item {{ is_active ? 'admin-center-nav__item--active' }}"
         {% if item.shortcut %}title="{{ item.label }} ({{ item.shortcut }})"{% endif %}>
        <span class="admin-center-nav__icon">
          {{ jaraba_icon(item.icon_category, item.icon_name, {
            variant: is_active ? 'duotone' : 'outline',
            size: '20px',
            color: is_active ? 'corporate' : 'neutral'
          }) }}
        </span>
        <span class="admin-center-nav__label">{{ item.label }}</span>
        {% if item.badge %}
          <span class="admin-center-nav__badge admin-center-nav__badge--{{ item.badge_type|default('neutral') }}">
            {{ item.badge }}
          </span>
        {% endif %}
        {% if item.shortcut %}
          <kbd class="admin-center-nav__shortcut">{{ item.shortcut }}</kbd>
        {% endif %}
      </a>
    {% endfor %}
  </nav>
</aside>
```

#### 4.5.4 Parcial: `_admin-center-kpi-card.html.twig`

```twig
{#
/**
 * @file
 * Admin Center — KPI Scorecard reutilizable.
 *
 * Variables:
 *   - kpi_key: Identificador unico (mrr, arr, tenants, etc.)
 *   - kpi_label: Etiqueta (traducida)
 *   - kpi_value: Valor formateado (ej: "47.350 EUR")
 *   - kpi_format: Tipo (currency|number|percent|score)
 *   - kpi_trend: Porcentaje de cambio vs periodo anterior
 *   - kpi_trend_period: Texto del periodo (ej: "vs mes anterior")
 *   - kpi_sparkline: Array de valores para mini-grafico (opcional)
 *   - kpi_url: URL de drill-down al hacer click (opcional)
 *   - kpi_icon_category: Categoria del icono jaraba_icon
 *   - kpi_icon_name: Nombre del icono
 *
 * Spec f104, Seccion 4.1 (KPIs Globales), 12.1 (Scorecard Component).
 */
#}

{% set tag = kpi_url ? 'a' : 'div' %}
{% set trend_class = kpi_trend > 0 ? 'up' : (kpi_trend < 0 ? 'down' : 'neutral') %}

<{{ tag }}{% if kpi_url %} href="{{ kpi_url }}"{% endif %}
  class="admin-center-kpi admin-center-kpi--{{ kpi_key }}"
  {% if kpi_url %}title="{% trans %}Ver detalles{% endtrans %}"{% endif %}>

  <div class="admin-center-kpi__header">
    <span class="admin-center-kpi__icon">
      {{ jaraba_icon(kpi_icon_category|default('analytics'), kpi_icon_name|default('kpi'), {
        variant: 'duotone',
        size: '24px',
      }) }}
    </span>
    <span class="admin-center-kpi__label">{{ kpi_label }}</span>
  </div>

  <div class="admin-center-kpi__value">{{ kpi_value }}</div>

  {% if kpi_trend is not null and kpi_trend != 0 %}
    <div class="admin-center-kpi__trend admin-center-kpi__trend--{{ trend_class }}">
      {{ trend_class == 'up' ? '↑' : '↓' }} {{ kpi_trend|abs }}%
      {% if kpi_trend_period %}
        <span class="admin-center-kpi__trend-period">{{ kpi_trend_period }}</span>
      {% endif %}
    </div>
  {% endif %}

  {% if kpi_sparkline is not empty %}
    <canvas class="admin-center-kpi__sparkline"
            data-sparkline="{{ kpi_sparkline|json_encode }}"
            width="120" height="32"
            aria-hidden="true"></canvas>
  {% endif %}

</{{ tag }}>
```

### 4.6 SCSS y Design Tokens

#### 4.6.1 Reglas obligatorias (del theming architecture master)

1. **NUNCA definir `$ej-*` variables localmente** — consumir solo `var(--ej-*, fallback)`
2. **Usar `@use 'sass:color'`** en vez de `darken()`/`lighten()` deprecados
3. **Cada parcial SCSS incluye `@use 'variables' as *;`** al inicio (Dart Sass module system)
4. **Documentar comando de compilacion** en header del archivo
5. **BEM para naming de clases CSS**

#### 4.6.2 Parcial principal: `_admin-center-dashboard.scss`

```scss
/**
 * @file
 * Admin Center Dashboard — Premium styles.
 *
 * DIRECTIVE: Use Design Tokens with CSS Custom Properties (var(--ej-*))
 * DIRECTIVE: Dart Sass module system (@use, NOT @import)
 * DIRECTIVE: Mobile-first responsive design
 * DIRECTIVE: BEM naming convention
 *
 * COMPILATION:
 * lando ssh -c "cd /app/web/modules/custom/ecosistema_jaraba_core && npx sass scss/main.scss css/ecosistema-jaraba-core.css --style=compressed"
 */

@use 'sass:color';
@use 'variables' as *;

// ═══════════════════════════════════════════════════════════════
// Layout
// ═══════════════════════════════════════════════════════════════

.admin-center-layout {
  display: grid;
  grid-template-columns: 240px 1fr;
  min-height: 100vh;
  background: var(--ej-bg-body, #{$ej-bg-body});
  font-family: var(--ej-font-family, #{$ej-font-family-fallback});
  color: var(--ej-text-primary, #{$ej-text-primary});
  transition: grid-template-columns 0.3s ease;

  &.admin-center-layout--collapsed {
    grid-template-columns: 64px 1fr;
  }
}

.admin-center-layout__main {
  display: flex;
  flex-direction: column;
  min-width: 0;
  overflow-y: auto;
}

.admin-center-layout__content {
  flex: 1;
  padding: var(--ej-spacing-xl, 2rem);
  max-width: 1400px;
  width: 100%;
  margin-inline: auto;
}

// Mobile: sidebar oculto, full-width
@media (max-width: $ej-breakpoint-lg) {
  .admin-center-layout {
    grid-template-columns: 1fr;
  }
}

// ═══════════════════════════════════════════════════════════════
// KPI Cards Grid
// ═══════════════════════════════════════════════════════════════

.admin-center__kpis {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: var(--ej-spacing-md, 1rem);
  margin-bottom: var(--ej-spacing-xl, 2rem);
}

.admin-center-kpi {
  background: var(--ej-bg-surface, #fff);
  border: 1px solid var(--ej-border-color, #{$ej-border-color});
  border-radius: var(--ej-radius-xl, 16px);
  padding: var(--ej-spacing-lg, 1.5rem);
  text-decoration: none;
  color: inherit;
  transition: box-shadow 0.3s ease, transform 0.3s ease;

  &:hover {
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
    transform: translateY(-2px);
  }

  // Accent top border per KPI type
  &--mrr { border-top: 3px solid var(--ej-color-impulse, #{$ej-color-impulse}); }
  &--arr { border-top: 3px solid var(--ej-color-corporate, #{$ej-color-corporate}); }
  &--tenants { border-top: 3px solid var(--ej-color-innovation, #{$ej-color-innovation}); }
  &--nrr { border-top: 3px solid var(--ej-color-success, #{$ej-color-success}); }
  &--churn { border-top: 3px solid var(--ej-color-danger, #{$ej-color-danger}); }
  &--alerts { border-top: 3px solid var(--ej-color-warning, #{$ej-color-warning}); }
}

.admin-center-kpi__header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}

.admin-center-kpi__icon {
  flex-shrink: 0;
}

.admin-center-kpi__label {
  font-size: 0.8rem;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--ej-text-muted, #{$ej-text-muted});
}

.admin-center-kpi__value {
  font-size: 1.75rem;
  font-weight: 700;
  line-height: 1.2;
  font-variant-numeric: tabular-nums;
}

.admin-center-kpi__trend {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  margin-top: 0.5rem;
  font-size: 0.8rem;
  font-weight: 600;

  &--up { color: var(--ej-color-success, #{$ej-color-success}); }
  &--down { color: var(--ej-color-danger, #{$ej-color-danger}); }
  &--neutral { color: var(--ej-color-neutral, #{$ej-color-neutral}); }
}

.admin-center-kpi__trend-period {
  font-weight: 400;
  color: var(--ej-text-muted, #{$ej-text-muted});
}

.admin-center-kpi__sparkline {
  display: block;
  width: 100%;
  height: 32px;
  margin-top: 0.75rem;
}

// Responsive KPI grid
@media (max-width: $ej-breakpoint-md) {
  .admin-center__kpis {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: $ej-breakpoint-xs) {
  .admin-center__kpis {
    grid-template-columns: 1fr;
  }
}

// ═══════════════════════════════════════════════════════════════
// Reduced motion
// ═══════════════════════════════════════════════════════════════

@media (prefers-reduced-motion: reduce) {
  .admin-center-layout,
  .admin-center-kpi {
    transition: none;
  }
}
```

#### 4.6.3 Variables inyectables que el Admin Center consume

Todas estas variables ya estan definidas en `_injectable.scss` y son
configurables desde la UI de Drupal en `/admin/appearance/settings/ecosistema_jaraba_theme`:

| Variable CSS | Tab en UI de Drupal | Default |
|---|---|---|
| `--ej-color-corporate` | Brand Identity | `#233D63` |
| `--ej-color-impulse` | Brand Identity | `#FF8C42` |
| `--ej-color-innovation` | Brand Identity | `#00A9A5` |
| `--ej-color-success` | Brand Identity | `#10B981` |
| `--ej-color-warning` | Brand Identity | `#F59E0B` |
| `--ej-color-danger` | Brand Identity | `#EF4444` |
| `--ej-bg-body` | Backgrounds | `#F8FAFC` |
| `--ej-bg-surface` | Backgrounds | `#FFFFFF` |
| `--ej-font-family` | Typography | `Inter, system` |
| `--ej-text-primary` | Typography | `#1F2937` |
| `--ej-text-muted` | Typography | `#6B7280` |
| `--ej-border-color` | General | `#E5E7EB` |
| `--ej-spacing-md` | (no configurable) | `1rem` |
| `--ej-spacing-lg` | (no configurable) | `1.5rem` |
| `--ej-spacing-xl` | (no configurable) | `2rem` |
| `--ej-radius-xl` | (no configurable) | `16px` |

**Si se necesita un token nuevo:** Seguir el flujo de la seccion 8 del theming master:
1. Definir en `_variables.scss` (fallback SCSS)
2. Añadir en `_injectable.scss` (CSS Custom Property en `:root`)
3. Si configurable por UI: añadir campo en `hook_form_system_theme_settings_alter()`
4. Mapear en `hook_preprocess_html()` de la section `$mapping`

### 4.7 JavaScript y Comportamientos

#### 4.7.1 Patron obligatorio

Todo JavaScript del Admin Center sigue el patron Drupal:

```javascript
/**
 * @file
 * [Descripcion].
 *
 * Patron: Drupal.behaviors + once() (directriz SDC/custom modules).
 * Dependencias: core/drupal, core/once, core/drupalSettings.
 *
 * COMPILATION: Este archivo NO se compila (vanilla JS).
 * Se declara en ecosistema_jaraba_core.libraries.yml.
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.adminCenter[Feature] = {
    attach: function (context) {
      once('admin-center-[feature]', '[selector]', context).forEach(function (el) {
        // Inicializacion
      });
    }
  };

})(Drupal, drupalSettings, once);
```

**Textos en JS siempre traducibles:**
```javascript
var label = Drupal.t('Exportar datos');        // CORRECTO
var label = 'Exportar datos';                   // INCORRECTO
```

#### 4.7.2 Chart.js integration (`admin-center-charts.js`)

Chart.js ya esta disponible como dependencia en el proyecto (usado en Health y FinOps dashboards).

```javascript
// Patron para charts en el Admin Center
Drupal.behaviors.adminCenterCharts = {
  attach: function (context) {
    once('admin-center-charts', '[data-chart]', context).forEach(function (canvas) {
      var type = canvas.getAttribute('data-chart-type');
      var endpoint = canvas.getAttribute('data-chart-endpoint');

      fetch(endpoint, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          new Chart(canvas, {
            type: type,
            data: data.chartData,
            options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                legend: { display: data.showLegend || false },
                tooltip: { enabled: true },
              },
            },
          });
        });
    });
  }
};
```

#### 4.7.3 DataTable interactivo (`admin-center-datatable.js`)

Implementacion vanilla JS (sin dependencia de TanStack Table que requiere React).

**Funcionalidades:**
- Sorting por click en header (asc/desc toggle)
- Filtering por campo (debounce 300ms)
- Pagination server-side (fetch a API)
- Row selection con checkboxes (bulk actions)
- Export CSV/PDF de seleccion

```javascript
// Los datos se cargan via fetch a las APIs REST
// El HTML de la tabla se renderiza en JS (no server-rendered)
// para permitir sorting/filtering sin recargar pagina.

// Ejemplo de declaracion en template:
// <div data-datatable
//      data-datatable-endpoint="/api/v1/admin/tenants"
//      data-datatable-columns='[{"key":"label","label":"Nombre","sortable":true}]'>
// </div>
```

### 4.8 Integracion con Servicios Existentes

| Servicio existente | Metodo consumido | Dato que provee al Admin Center |
|--------------------|------------------|--------------------------------|
| `jaraba_foc.saas_metrics` | `calculateMRR()` | KPI: MRR |
| `jaraba_foc.saas_metrics` | `calculateARR()` | KPI: ARR |
| `jaraba_foc.saas_metrics` | `calculateNRR()` | KPI: Net Revenue Retention |
| `jaraba_foc.saas_metrics` | `calculateGRR()` | SaaS Metrics table |
| `jaraba_foc.saas_metrics` | `calculateChurnRate()` | KPI: Churn Rate |
| `jaraba_foc.saas_metrics` | `calculateLTV()` | SaaS Metrics table |
| `jaraba_foc.saas_metrics` | `calculateCAC()` | SaaS Metrics table |
| `jaraba_foc.saas_metrics` | `calculateARPU()` | SaaS Metrics table |
| `jaraba_customer_success.health_calculator` | `calculate($tid)` | Health Score per tenant |
| `jaraba_customer_success.churn_prediction` | `predict($tid)` | Churn risk prediction |
| `ecosistema_jaraba_core.impersonation` | `start($uid)` | Impersonate tenant admin |
| `ecosistema_jaraba_core.alerting` | `send()` | Webhook notifications |
| `ecosistema_jaraba_core.audit_log` | `log()` | Audit trail entries |

---

## 5. Fases de Implementacion

### FASE 1 — Fundacion y Datos Reales (Sprint 1-2)

**Estimacion:** 50-65h
**Objetivo:** Refactorizar el dashboard existente para mostrar datos reales, crear el
layout con sidebar colapsable, y migrar de emojis a iconos SVG.

#### F1.1 — AdminCenterAggregatorService (8-10h)

**Archivo:** `src/Service/AdminCenterAggregatorService.php`
**Registro:** `ecosistema_jaraba_core.services.yml`

- [ ] Crear servicio con inyeccion de dependencias correcta
- [ ] Implementar `getKpis()` con datos reales:
  - MRR via `jaraba_foc.saas_metrics->calculateMRR()`
  - ARR via `jaraba_foc.saas_metrics->calculateARR()`
  - Tenants count via entity query
  - NRR via `jaraba_foc.saas_metrics->calculateNRR()` (reemplaza MAU)
  - Churn via `jaraba_foc.saas_metrics->calculateChurnRate()` (reemplaza hardcoded 0)
  - Open Alerts count (reemplaza health_avg hardcoded 0)
- [ ] Implementar trend calculation (comparativa con 30 dias previos)
- [ ] Implementar `getTenantStats()` con desglose real por `subscription_status`
- [ ] Implementar `getActiveAlerts()` con severidad y timestamp
- [ ] Implementar `getTopTenants(10)` con MRR y health score
- [ ] Implementar `getRevenueTrend(12)` (serie temporal de MRR 12 meses)
- [ ] Tests: verificar que el servicio no falla cuando modulos FOC/CS no estan habilitados

**Verificacion:**
```bash
lando ssh -c "drush cr"
lando ssh -c "drush eval \"print_r(\Drupal::service('ecosistema_jaraba_core.admin_center_aggregator')->getKpis());\""
```

#### F1.2 — Refactorizar AdminCenterController (4-6h)

**Archivo:** `src/Controller/AdminCenterController.php`

- [ ] Inyectar `AdminCenterAggregatorService` por constructor (eliminar `\Drupal::` calls)
- [ ] `dashboard()` consume datos del aggregator
- [ ] Pasar sparkline data (array de 6 valores) a template
- [ ] Actualizar drupalSettings con datos reales

#### F1.3 — Layout con Sidebar Colapsable (8-10h)

**Archivos:**
- `ecosistema_jaraba_theme/templates/page--admin-center.html.twig` (NUEVO)
- `ecosistema_jaraba_theme/templates/partials/_admin-center-nav.html.twig` (NUEVO)
- `ecosistema_jaraba_theme/templates/partials/_admin-center-topbar.html.twig` (NUEVO)
- `ecosistema_jaraba_core/js/admin-center-sidebar.js` (NUEVO)
- `ecosistema_jaraba_core/scss/_admin-center-sidebar-nav.scss` (NUEVO)

- [ ] Crear template de layout (Zero Region Policy, clean_content)
- [ ] Crear parcial de sidebar con items de navegacion y `jaraba_icon()`
- [ ] Crear parcial de topbar con breadcrumbs y search
- [ ] JS para colapsar/expandir sidebar (localStorage para persistencia)
- [ ] SCSS responsive (sidebar como drawer en mobile <992px)
- [ ] `hook_preprocess_html()`: body class `admin-center-page` para ruta `/admin/jaraba/center*`
- [ ] `hook_preprocess_page()`: proveer variables `admin_center_nav`, `admin_center_active`

**Verificacion:**
```bash
lando ssh -c "cd /app/web/modules/custom/ecosistema_jaraba_core && npx sass scss/main.scss css/ecosistema-jaraba-core.css --style=compressed"
lando ssh -c "drush cr"
# Navegar a https://jaraba-saas.lndo.site/admin/jaraba/center
```

#### F1.4 — Migrar Emojis a Iconos SVG (4-6h)

**Directriz scss-estilos.md:** Cada icono debe tener version outline y duotone.

- [ ] Crear iconos SVG necesarios para sidebar nav:
  - `ui/dashboard.svg` + `ui/dashboard-duotone.svg`
  - `business/tenant.svg` + `business/tenant-duotone.svg`
  - `ui/users.svg` + `ui/users-duotone.svg` (si no existe)
  - `analytics/revenue.svg` + `analytics/revenue-duotone.svg`
  - `ui/bell.svg` + `ui/bell-duotone.svg` (si no existe)
  - `ui/settings.svg` + `ui/settings-duotone.svg` (si no existe)
  - `ui/log.svg` + `ui/log-duotone.svg`
- [ ] Actualizar `admin-center-dashboard.html.twig`: reemplazar emojis por `jaraba_icon()`
- [ ] Actualizar `admin-command-palette.js`: usar SVG inline o CSS en vez de emojis
- [ ] Verificar que `jaraba_icon()` esta registrado en `icons.inc` del tema

#### F1.5 — KPI Cards con Sparklines y Drill-down (6-8h)

**Archivos:**
- `templates/partials/_admin-center-kpi-card.html.twig` (NUEVO)
- `admin-center-dashboard.html.twig` (REFACTORIZAR para usar parcial)
- `js/admin-center-charts.js` (NUEVO)

- [ ] Crear parcial de KPI card (ver seccion 4.5.4)
- [ ] Refactorizar dashboard template para usar `{% include ... kpi-card %}` en loop
- [ ] JS: sparklines con Chart.js (tipo `line`, sin axes, sin legend, solo curva)
- [ ] Click en KPI card navega a seccion correspondiente (drill-down)
- [ ] Library: `admin-center-charts` con dependencia a Chart.js CDN

#### F1.6 — Charts del Dashboard (8-10h)

- [ ] Revenue Trend chart (area, 12 meses) — endpoint `/api/v1/admin/revenue-trend`
- [ ] Tenant Distribution chart (doughnut, por vertical)
- [ ] Top 10 Tenants chart (horizontal bar, MRR + health color)
- [ ] Active Alerts widget mejorado: severity colors, actions (View, Dismiss)

**Verificacion final FASE 1:**
- [ ] Dashboard muestra datos reales (MRR, ARR no son 0)
- [ ] Churn rate calculado (no hardcoded)
- [ ] Trend arrows con comparativa real
- [ ] Sparklines visibles en cada KPI card
- [ ] Sidebar colapsable funciona (click + localStorage persist)
- [ ] Iconos SVG (no emojis)
- [ ] Charts renderizados con datos de API
- [ ] Mobile responsive (sidebar como overlay en <992px)
- [ ] `drush cr` exitoso
- [ ] Sin errores en consola del navegador
- [ ] `prefers-reduced-motion` respetado

---

### FASE 2 — Gestion de Tenants (Sprint 3-4)

**Estimacion:** 55-70h
**Spec f104:** Secciones 5.1-5.4

#### F2.1 — DataTable Component (12-16h)

**Componente reutilizable** para usar en Tenants, Users, Finance, Alerts, Logs.

- [ ] `admin-center-datatable.js`: vanilla JS con sorting, filtering, pagination
- [ ] `_admin-center-datatable.scss`: estilos premium (sticky headers, hover, selection)
- [ ] Template: declarativo con `data-datatable` attributes
- [ ] Server-side pagination via APIs REST
- [ ] Export CSV/PDF via `/api/v1/admin/export/{type}`
- [ ] Accesibilidad: `role="grid"`, `aria-sort`, keyboard navigation

#### F2.2 — Pagina de Tenants (10-14h)

- [ ] Ruta `/admin/jaraba/center/tenants`
- [ ] Controller method `tenants()`
- [ ] Template `admin-center-tenants.html.twig`
- [ ] DataTable con columnas: Name+Avatar, Vertical (badge), Plan (badge), MRR, Users, Health Score (progress+color), Status (badge), Created
- [ ] Filtros: Vertical (multi-select), Plan (multi-select), Status, Health Score (range)
- [ ] Bulk actions: Export, Send Notification, Change Plan
- [ ] Row actions: View (slide-panel), Edit (slide-panel), Impersonate, Suspend

#### F2.3 — Detalle de Tenant 360 en Slide-Panel (10-14h)

- [ ] Slide-panel con tabs: Overview, Users, Billing, Activity, Settings
- [ ] Tab Overview: 4 KPI cards (MRR, Users, Products, Orders) + Health Score bar + Timeline
- [ ] Tab Users: lista de usuarios del tenant
- [ ] Tab Billing: historial de pagos, plan actual
- [ ] Tab Activity: timeline de acciones recientes
- [ ] Tab Settings: limits, features, branding overrides
- [ ] AJAX endpoint para cargar cada tab on-demand

#### F2.4 — Health Score Visual (6-8h)

- [ ] Consumir `jaraba_customer_success.health_calculator->calculate(tenant_id)`
- [ ] Breakdown visual: 5 componentes con barras de progreso (Engagement, Adoption, Satisfaction, Support, Growth)
- [ ] Thresholds con colores: 80-100 verde, 60-79 amarillo, 40-59 naranja, 0-39 rojo
- [ ] Trend indicator (Improving, Stable, Declining)
- [ ] Promedio visible en KPI del dashboard

#### F2.5 — APIs REST para Tenants (4-6h)

- [ ] `GET /api/v1/admin/tenants` — lista paginada con filtros (query params)
- [ ] `GET /api/v1/admin/tenants/{id}` — detalle completo
- [ ] `POST /api/v1/admin/tenants/{id}/impersonate` — generar sesion impersonation
- [ ] `GET /api/v1/admin/export/tenants` — CSV export

---

### FASE 3 — Gestion de Usuarios (Sprint 5)

**Estimacion:** 30-40h
**Spec f104:** Secciones 6.1-6.3

- [ ] Ruta `/admin/jaraba/center/users`
- [ ] DataTable con columnas: User (Avatar+Name+Email), Tenant, Role (badge color), Status (badge), Last Active, Sessions, Actions
- [ ] Filtros: Tenant, Role, Status, Last Active (date range), Vertical
- [ ] Row actions: View, Edit, Impersonate, Suspend, Force Logout
- [ ] Detalle de usuario en slide-panel
- [ ] Activity log por usuario (timeline)
- [ ] Active sessions panel (IP, Device, Duration)
- [ ] API: `GET /api/v1/admin/users` con pagination y filtros
- [ ] API: `DELETE /api/v1/admin/users/{id}/sessions` (force logout)

---

### FASE 4 — Centro Financiero (Sprint 6-7)

**Estimacion:** 50-60h
**Spec f104:** Secciones 7.1-7.3
**Spec FOC:** `20260113d-FOC_Documento_Tecnico_Definitivo_v2`

- [ ] Ruta `/admin/jaraba/center/finance`
- [ ] Revenue Dashboard: MRR scorecard + trend 12m + breakdown por vertical (treemap)
- [ ] Net New MRR waterfall chart (New + Expansion - Churn)
- [ ] SaaS Metrics table con benchmarks y status visual:
  - GRR (>90%), NRR (>105%), Logo Churn (<5%), Revenue Churn (<3%)
  - CAC, LTV, LTV:CAC (>3:1), CAC Payback (<12m), ARPU (trend up)
- [ ] Revenue Cohort Analysis (heatmap de retencion)
- [ ] Stripe Connect overview: Connected accounts, status, balance
- [ ] Integracion con servicios FOC existentes
- [ ] Export financiero (CSV, PDF)

---

### FASE 5 — Sistema de Alertas y Playbooks (Sprint 8)

**Estimacion:** 35-45h
**Spec f104:** Secciones 9.1-9.3

- [ ] Ruta `/admin/jaraba/center/alerts`
- [ ] Centro de Notificaciones con categorias: Financial, Operational, Security, System, Business
- [ ] Estados de alerta: New, Seen, In Progress, Resolved, Dismissed
- [ ] Editor visual de reglas mejorado (formulario en slide-panel, no entity form estandar)
- [ ] Playbook entity (ContentEntity):
  - name, trigger_alert_type, steps (JSON), active (boolean)
  - Steps: sequence of actions (create_ticket, send_email, schedule_call, offer_discount)
- [ ] ECA hooks para ejecutar playbooks automaticamente cuando se dispara una alerta
- [ ] API: alertas paginadas con filtros

---

### FASE 6 — Analytics, Reports y Logs (Sprint 9)

**Estimacion:** 40-50h
**Spec f104:** Secciones 8, 11

#### Analytics y Reports
- [ ] Report viewer (no full builder): templates predefinidos seleccionables
- [ ] Templates: Monthly Business Review, Cohort Analysis, Vertical Performance, Churn Analysis
- [ ] Export en PDF/CSV/Excel
- [ ] Scheduled reports via cron (frecuencia configurable, destinatarios por email)

#### Logs y Auditoria
- [ ] Ruta `/admin/jaraba/center/logs`
- [ ] AdminActivityLog entity (ver seccion 4.2.2)
- [ ] Activity Log cronologico con filtros (actor, action, resource_type, date range)
- [ ] Audit Trail view (readonly, no editable/eliminable)
- [ ] Error Log con agrupacion de errores similares y severity
- [ ] Integracion con AuditLog entity existente

---

### FASE 7 — Configuracion Global y Polish (Sprint 10)

**Estimacion:** 30-40h
**Spec f104:** Secciones 10, criterios de aceptacion

#### Configuracion Global
- [ ] Ruta `/admin/jaraba/center/settings`
- [ ] General: Platform Name, Logo, Domain, Support Email, Default Language, Timezone
- [ ] Billing Plans: CRUD de SaasPlan en slide-panel
- [ ] Integrations: Stripe keys, SMTP, Slack webhook, Analytics, AI keys
- [ ] API Keys: CRUD para keys programaticas

#### Polish y QA
- [ ] Dark mode integration (verificar todos los componentes con `.dark-mode`)
- [ ] Accesibilidad audit: WCAG 2.1 AA, focus indicators, ARIA labels, contrast
- [ ] Performance: FCP < 1.5s en dashboard (lazy load charts)
- [ ] Browser testing: Chrome, Firefox, Safari, Edge (2 ultimas versiones)
- [ ] Mobile testing: 375px, 768px, 1024px, 1440px, 4K

---

## 6. Cumplimiento de Directrices del Proyecto

### 6.1 Directriz de Textos Traducibles (i18n)

**Fuente:** `.agent/workflows/i18n-traducciones.md`

**Regla:** TODO texto visible al usuario DEBE ser traducible.

| Contexto | Patron correcto | Ejemplo |
|----------|-----------------|---------|
| PHP Controller | `$this->t('Texto')` | `$this->t('Monthly Recurring Revenue')` |
| Twig Template | `{% trans %}Texto{% endtrans %}` | `{% trans %}Alertas Activas{% endtrans %}` |
| JavaScript | `Drupal.t('Texto')` | `Drupal.t('Exportar datos')` |
| Render array | `'#title' => $this->t('Texto')` | `'#title' => $this->t('Admin Center')` |

**Verificacion post-implementacion:**
```bash
# Buscar textos hardcoded en templates
lando ssh -c "grep -rn '>[A-Z][a-z]' /app/web/modules/custom/ecosistema_jaraba_core/templates/admin-center*.html.twig | grep -v 'trans\|{{\|{#\|data-'"
```

### 6.2 Directriz SCSS con Variables Inyectables

**Fuente:** `docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md`

**Regla:** Los modulos SOLO consumen CSS Custom Properties con fallback inline.
NUNCA definen `$ej-*` variables localmente.

```scss
// CORRECTO: Variable CSS con fallback
color: var(--ej-color-corporate, #233D63);
background: var(--ej-bg-surface, #FFFFFF);

// INCORRECTO: Variable SCSS local
$ej-color-corporate: #233D63;  // NUNCA duplicar

// INCORRECTO: Hex hardcoded sin token
color: #233D63;  // NUNCA hardcodear
```

**Las variables son inyectadas en runtime** por `hook_preprocess_html()` del tema,
que lee la configuracion de `/admin/appearance/settings/ecosistema_jaraba_theme`
y genera un `<style>:root { --ej-color-primary: [valor]; ... }</style>`.

Adicionalmente, el `StylePresetService` inyecta tokens por nivel
(Platform > Vertical > Plan > Tenant) via `PageAttachmentsHooks::alterPageAttachments()`.

**Esto significa que al cambiar un color en la UI de Drupal, el Admin Center lo refleja
automaticamente sin recompilar SCSS ni tocar codigo.**

### 6.3 Directriz de Templates Twig Limpias (Zero Region Policy)

**Fuente:** `.agent/workflows/frontend-page-pattern.md`

**Regla:** NUNCA usar `{{ page.content }}` ni ninguna `{{ page.* }}`.
Usar `{{ clean_content }}` que extrae SOLO el render del controller.

```twig
{# CORRECTO #}
{{ clean_content }}

{# INCORRECTO — incluye bloques random de Drupal #}
{{ page.content }}
```

La variable `clean_content` se extrae en `ecosistema_jaraba_theme_preprocess_page()`
buscando el `system_main_block` dentro de `page['content']`.

### 6.4 Directriz de Parciales Reutilizables

**Regla:** Antes de crear markup inline, verificar si ya existe un parcial.
Si el bloque de markup se usara en 2+ paginas, crear parcial.

**Parciales nuevos del Admin Center:**
| Parcial | Reutilizado en |
|---------|----------------|
| `_admin-center-nav.html.twig` | Todas las paginas Admin Center (7+) |
| `_admin-center-topbar.html.twig` | Todas las paginas Admin Center (7+) |
| `_admin-center-kpi-card.html.twig` | Dashboard, Finance, Tenant Detail |

**Parcial existente que se reutiliza:**
| Parcial | Para que |
|---------|----------|
| `_slide-panel.html.twig` | CRUD modales (tenants, alerts, users, settings) |

### 6.5 Directriz de Variables Configurables desde UI de Drupal

**Fuente:** `ecosistema_jaraba_theme.theme` (hook_form_system_theme_settings_alter)

**Regla:** Si un texto, color o configuracion del frontend puede cambiar sin tocar codigo,
debe ser configurable desde la UI de Drupal. Ejemplos del Admin Center:

| Elemento | Configurable desde UI? | Como |
|----------|----------------------|------|
| Colores de KPIs | SI | Brand Identity tab en theme settings |
| Texto del footer | SI | Footer tab en theme settings |
| Logo | SI | Brand Identity tab |
| Numero de items por pagina en DataTable | Via drupalSettings | Config form del modulo |
| Textos de la UI | Via i18n | `/admin/config/regional/translate` |
| Sidebar items | En codigo del controller | Requiere cambio en `getQuickLinks()` |

### 6.6 Directriz de Layout Full-Width Mobile-First

**Regla:** Layout pensado para movil primero, luego escalar.

```scss
// Mobile first: sin media query = mobile
.admin-center-layout {
  grid-template-columns: 1fr; // Mobile: full width, no sidebar
}

// Tablet: sidebar overlay
@media (min-width: $ej-breakpoint-md) {
  .admin-center-layout {
    // Sidebar aun como drawer/overlay
  }
}

// Desktop: sidebar visible
@media (min-width: $ej-breakpoint-lg) {
  .admin-center-layout {
    grid-template-columns: 240px 1fr;
  }
}
```

### 6.7 Directriz de Modales (Slide-Panel)

**Fuente:** `.agent/workflows/slide-panel-modales.md`

**Regla:** Todas las acciones de crear/editar/ver en el Admin Center abren slide-panel.

| Accion | Trigger | Endpoint AJAX |
|--------|---------|---------------|
| Ver detalle tenant | Click en row | `/admin/jaraba/center/tenants/{id}` (AJAX) |
| Editar tenant | Click "Edit" | Entity form via AJAX |
| Crear alerta | Click "+" | Entity form via AJAX |
| Ver usuario | Click en row | `/admin/jaraba/center/users/{id}` (AJAX) |
| Config settings | Click item | Config form via AJAX |

**Patron en controller:**
```php
if ($request->isXmlHttpRequest()) {
    $html = (string) $this->renderer->render($build);
    return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
}
return $build; // Full page fallback
```

### 6.8 Directriz de Body Classes via hook_preprocess_html

**Regla:** Las clases del `<body>` NO se pueden añadir con `attributes.addClass()` en templates.
Se deben añadir via `hook_preprocess_html()`.

```php
// En ecosistema_jaraba_theme_preprocess_html()
$route = \Drupal::routeMatch()->getRouteName();

if (str_starts_with($route, 'ecosistema_jaraba_core.admin_center')
    || str_starts_with($route, 'ecosistema_jaraba_core.admin.center')) {
  $variables['attributes']['class'][] = 'admin-center-page';
  $variables['attributes']['class'][] = 'full-width-layout';
  $variables['attributes']['class'][] = 'no-drupal-sidebar';
}
```

### 6.9 Directriz de Iconografia SVG

**Fuente:** `.agent/workflows/scss-estilos.md` (Seccion 2)

**Regla:** SIEMPRE crear AMBAS versiones de cada icono:
1. `{nombre}.svg` — Outline (stroke)
2. `{nombre}-duotone.svg` — Duotone (2 tonos con opacidad)

**Uso en templates:**
```twig
{# Outline: para botones, items de lista, elementos pequenos #}
{{ jaraba_icon('ui', 'bell', { color: 'corporate', size: '20px' }) }}

{# Duotone: para headers, cards destacadas, impacto visual #}
{{ jaraba_icon('business', 'tenant', { variant: 'duotone', color: 'impulse', size: '32px' }) }}
```

**Colores disponibles (paleta Jaraba):**
`azul-profundo`, `azul-verdoso`, `azul-corporativo` (corporate),
`naranja-impulso` (impulse), `verde-innovacion` (innovation),
`verde-oliva` (agro), `success`, `warning`, `danger`, `neutral`

### 6.10 Directriz de Paleta de Colores

**Fuente:** `.agent/workflows/scss-estilos.md` (Seccion 5)

| Color | Hex | Variable CSS | Uso en Admin Center |
|-------|-----|-------------|---------------------|
| Azul Corporativo | `#233D63` | `--ej-color-corporate` | Titulos, sidebar activo, botones primary |
| Naranja Impulso | `#FF8C42` | `--ej-color-impulse` | KPI MRR, highlights, CTAs |
| Verde Innovacion | `#00A9A5` | `--ej-color-innovation` | KPI Tenants, iconos de crecimiento |
| Verde Oliva | `#556B2F` | `--ej-color-agro` | Badges AgroConecta |
| Success | `#10B981` | `--ej-color-success` | Health score alto, trends up |
| Warning | `#F59E0B` | `--ej-color-warning` | Health score medio, alertas info |
| Danger | `#EF4444` | `--ej-color-danger` | Health score critico, churn, errors |
| Neutral | `#64748B` | `--ej-color-neutral` | Texto secundario, disabled |

### 6.11 Directriz de Entidades con Field UI y Views

**Fuente:** `.agent/workflows/drupal-custom-modules.md`

**Regla:** Toda ContentEntity debe tener:
1. `field_ui_base_route` en la anotacion
2. Ruta settings en `/admin/structure/{entity}`
3. Handler `views_data` = `EntityViewsData`
4. 4 archivos YAML: routing, links.menu, links.task, links.action

**Aplicacion:**
- `AdminActivityLog` entity: se registra en `/admin/structure/admin-activity-log`
  con Field UI habilitado, views_data para crear vistas custom, y navigation
  links en `/admin/content/activity-log` (collection) y `/admin/structure/` (config).

### 6.12 Directriz de Navegacion Admin

**Regla:**
- Content Entities van en `/admin/content` (collection)
- Config Entities van en `/admin/structure`
- Module Settings van en `/admin/config`

| Entidad nueva | Ubicacion | Parent menu |
|---------------|-----------|-------------|
| AdminActivityLog | `/admin/content/activity-log` | system.admin_content |
| (Field UI settings) | `/admin/structure/admin-activity-log` | system.admin_structure |

### 6.13 Directriz de Tenant sin Acceso a Admin Theme

**Regla:** El tenant NO debe ver el tema de administracion de Drupal.

**Implementacion:**
- El Admin Center usa `_admin_route: TRUE` en routing, lo que requiere
  `administer site configuration` permission
- Los tenants acceden a su panel self-service via `/dashboard/producer` (etc.)
  con template limpia propia (ya implementado)
- El `TenantAccessControlHandler` global verifica permisos en todas las entidades
- La Admin Toolbar se oculta para roles no-admin via permisos de modulo `admin_toolbar`

### 6.14 Directriz de Dart Sass Moderno

**Fuente:** `docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md`

**Regla:**
1. Usar `@use 'sass:color'` en vez de funciones deprecadas
2. Cada parcial SCSS incluye sus `@use` al inicio (modulos independientes)
3. NUNCA usar `@import` (deprecado en Dart Sass)

```scss
// CORRECTO
@use 'sass:color';
@use 'variables' as *;

.button-hover {
  background: color.scale($ej-color-primary-fallback, $lightness: -10%);
}

// INCORRECTO
@import 'variables';
background: darken($ej-color-primary, 10%);
```

### 6.15 Directriz de Compilacion en Docker

**Todos los comandos SCSS se ejecutan dentro del contenedor:**

```bash
# Core module
lando ssh -c "cd /app/web/modules/custom/ecosistema_jaraba_core && npx sass scss/main.scss css/ecosistema-jaraba-core.css --style=compressed"

# Theme
lando ssh -c "cd /app/web/themes/custom/ecosistema_jaraba_theme && npx sass scss/main.scss css/main.css --style=compressed"

# Cache clear
lando drush cr
```

**Importante:** Si NVM no esta cargado en el contenedor:
```bash
lando ssh -c "export NVM_DIR=\"\$HOME/.nvm\" && [ -s \"\$NVM_DIR/nvm.sh\" ] && . \"\$NVM_DIR/nvm.sh\" && cd /app/web/modules/custom/ecosistema_jaraba_core && npx sass scss/main.scss css/ecosistema-jaraba-core.css --style=compressed"
```

---

## 7. Procedimientos de Verificacion

### 7.1 Verificacion post-cada-fase

Despues de completar cada fase, ejecutar:

```bash
# 1. Compilar SCSS
lando ssh -c "cd /app/web/modules/custom/ecosistema_jaraba_core && npx sass scss/main.scss css/ecosistema-jaraba-core.css --style=compressed"

# 2. Cache clear
lando drush cr

# 3. Verificar sin errores
lando ssh -c "drush watchdog:show --severity=error --count=10"
```

### 7.2 Verificacion visual

| Pagina | URL | Verificaciones |
|--------|-----|----------------|
| Dashboard | `/admin/jaraba/center` | KPIs con datos reales, charts, sparklines |
| Tenants | `/admin/jaraba/center/tenants` | DataTable funcional, filtros, sorting |
| Users | `/admin/jaraba/center/users` | DataTable, badges de rol/status |
| Finance | `/admin/jaraba/center/finance` | Revenue charts, SaaS metrics |
| Alerts | `/admin/jaraba/center/alerts` | Alertas con severity colors |
| Logs | `/admin/jaraba/center/logs` | Activity log con filtros |
| Settings | `/admin/jaraba/center/settings` | Formularios funcionales |

### 7.3 Checklist de calidad (cada pagina)

- [ ] Zero Region Policy: no hay bloques Drupal random
- [ ] Todos los textos usan `{% trans %}` / `$this->t()` / `Drupal.t()`
- [ ] Iconos SVG via `jaraba_icon()` (no emojis)
- [ ] Colores via `var(--ej-*)` (no hex hardcoded)
- [ ] SCSS compilado sin errores
- [ ] Mobile responsive (375px, 768px, 1024px, 1440px)
- [ ] `prefers-reduced-motion` respetado
- [ ] ARIA labels en elementos interactivos
- [ ] Modales via slide-panel (no navegacion de pagina)
- [ ] Datos cargados via fetch (no bloqueantes)
- [ ] Sin errores en consola del navegador

---

## 8. Metricas de Exito

| Metrica | Target | Como medir |
|---------|--------|------------|
| Cumplimiento spec f104 | >85% | Checklist seccion 2.1 |
| First Contentful Paint | <1.5s | Lighthouse |
| Glanceability | <5s para entender estado | User testing |
| Accesibilidad | WCAG 2.1 AA | axe DevTools |
| Mobile usability | 100% funcional en 375px | Manual testing |
| Zero errores JS | 0 errors en console | Browser console |
| SCSS token compliance | 100% | `grep -rn '#[0-9a-f]' scss/` = 0 resultados hardcoded |
| i18n compliance | 100% | `grep -rn '>[A-Z]' templates/` sin `trans` = 0 |

---

## 9. Dependencias y Riesgos

### 9.1 Dependencias tecnicas

| Dependencia | Estado | Riesgo si no disponible |
|-------------|--------|------------------------|
| `jaraba_foc` modulo | Instalado | KPIs MRR/ARR muestran 0 (graceful degradation) |
| `jaraba_customer_success` modulo | Instalado | Health scores muestran 0 |
| Chart.js CDN | Disponible | Charts no renderizan (fallback: tabla datos) |
| `jaraba_icon()` helper | Implementado | Iconos no renderizan (fallback: text labels) |
| Slide-panel JS | Implementado en tema | Modales no funcionan |
| Redis cache | Configurado | Dashboards mas lentos pero funcionales |

### 9.2 Riesgos

| Riesgo | Impacto | Mitigacion |
|--------|---------|------------|
| Servicios FOC no devuelven datos reales | KPIs vacios | Aggregator con fallback a 0 y logging |
| Rendimiento con muchos tenants (>100) | DataTable lento | Pagination server-side, max 50/pagina |
| Chart.js CDN no disponible | Charts broken | Fallback a tabla de datos |
| Conflicto con Drupal admin toolbar | Layout roto | CSS specificity con `body.admin-center-page` |

---

## 10. Glosario

| Termino | Definicion |
|---------|------------|
| **MRR** | Monthly Recurring Revenue — Ingreso mensual recurrente |
| **ARR** | Annual Recurring Revenue — Ingreso anual recurrente |
| **NRR** | Net Revenue Retention — Retencion neta de ingresos (>105% es bueno) |
| **GRR** | Gross Revenue Retention — Retencion bruta (>90% es bueno) |
| **LTV** | Lifetime Value — Valor del cliente en su vida util |
| **CAC** | Customer Acquisition Cost — Coste de adquirir un cliente |
| **ARPU** | Average Revenue Per User — Ingreso promedio por usuario |
| **Health Score** | Puntuacion 0-100 de salud del tenant (5 componentes ponderados) |
| **Churn Rate** | Tasa de cancelacion de clientes (mensual) |
| **FOC** | Financial Operations Center — Centro de operaciones financieras |
| **Zero Region Policy** | Principio: no usar regiones Drupal, solo `clean_content` |
| **SSOT** | Single Source of Truth — Fuente unica de verdad |
| **BEM** | Block Element Modifier — Metodologia de naming CSS |
| **SDC** | Single Directory Components — Componentes Drupal autocontenidos |

---

## 11. Referencias Cruzadas

| Seccion de este plan | Documento de referencia |
|---------------------|------------------------|
| Arquitectura SCSS | `docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md` |
| Iconos SVG | `.agent/workflows/scss-estilos.md` (Seccion 2) |
| Paleta de colores | `.agent/workflows/scss-estilos.md` (Seccion 5) |
| Zero Region Policy | `.agent/workflows/frontend-page-pattern.md` |
| Slide-Panel modales | `.agent/workflows/slide-panel-modales.md` |
| Entidades Drupal | `.agent/workflows/drupal-custom-modules.md` |
| i18n | `.agent/workflows/i18n-traducciones.md` |
| Hooks (no ECA) | `.agent/workflows/drupal-eca-hooks.md` |
| Verificacion browser | `.agent/workflows/browser-verification.md` |
| Auditoria 7 puntos | `.agent/workflows/auditoria-exhaustiva.md` |
| FOC metricas | `docs/tecnicos/20260113d-FOC_Documento_Tecnico_Definitivo_v2_Claude.md` |
| Design tokens presets | `docs/tecnicos/20260117f-102_Industry_Style_Presets_Premium_v1_Claude.md` |
| Remediacion auditoria | `docs/implementacion/20260213-Plan_Remediacion_Auditoria_Integral_v1.md` |
| Plan previo Bloque D | `docs/implementacion/20260123d-Bloque_D_Admin_Center_Implementacion_Claude.md` |
| Plan previo F6 | `docs/implementacion/2026-02-12_F6_SaaS_Admin_UX_Complete_Doc181_Implementacion.md` |

---

## 12. Registro de Cambios

| Fecha | Version | Autor | Descripcion |
|-------|---------|-------|-------------|
| 2026-02-13 | 1.0.0 | Claude Opus 4.6 | Creacion inicial — Plan completo basado en auditoria exhaustiva de spec f104 vs implementacion actual |

# Plan Maestro de Implementacion Integral SaaS v2.0

**Fecha de creacion:** 2026-02-11 18:00
**Ultima actualizacion:** 2026-02-11 21:00
**Autor:** IA Asistente (Arquitecto Multi-Disciplinario)
**Version:** 2.2.0
**Roles:** Arquitecto SaaS Senior, Ingeniero Software Senior, Ingeniero UX Senior, Ingeniero Drupal Senior, Desarrollador Web Senior, Disenador/Desarrollador Theming Senior, Ingeniero GrapesJS Senior, Ingeniero SEO/GEO Senior, Ingeniero IA Senior

---

## Tabla de Contenidos (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Estado Actual del SaaS — Inventario Verificado](#2-estado-actual-del-saas--inventario-verificado)
   - 2.1 [Modulos Completos (Produccion)](#21-modulos-completos-produccion)
   - 2.2 [Modulos Parciales (Requieren Completar)](#22-modulos-parciales-requieren-completar)
   - 2.3 [Modulos Skeleton (Requieren Desarrollo Completo)](#23-modulos-skeleton-requieren-desarrollo-completo)
   - 2.4 [Funcionalidades No Implementadas](#24-funcionalidades-no-implementadas)
3. [Directrices de Obligado Cumplimiento](#3-directrices-de-obligado-cumplimiento)
   - 3.1 [Arquitectura SCSS y Design Tokens Federados](#31-arquitectura-scss-y-design-tokens-federados)
   - 3.2 [Textos de Interfaz Siempre Traducibles (i18n)](#32-textos-de-interfaz-siempre-traducibles-i18n)
   - 3.3 [Variables Inyectables via UI de Drupal](#33-variables-inyectables-via-ui-de-drupal)
   - 3.4 [Paginas Frontend Limpias sin Regiones Drupal](#34-paginas-frontend-limpias-sin-regiones-drupal)
   - 3.5 [Templates Parciales Reutilizables](#35-templates-parciales-reutilizables)
   - 3.6 [Slide-Panel Modal para CRUD](#36-slide-panel-modal-para-crud)
   - 3.7 [Content Entities con Field UI y Views](#37-content-entities-con-field-ui-y-views)
   - 3.8 [Sistema de Iconos Dual (Outline + Duotone)](#38-sistema-de-iconos-dual-outline--duotone)
   - 3.9 [Paleta de Colores Jaraba Oficial](#39-paleta-de-colores-jaraba-oficial)
   - 3.10 [Body Classes via hook_preprocess_html](#310-body-classes-via-hook_preprocess_html)
   - 3.11 [Tenant sin Acceso al Tema Admin](#311-tenant-sin-acceso-al-tema-admin)
   - 3.12 [IA via Drupal AI Module (Nunca HTTP Directo)](#312-ia-via-drupal-ai-module-nunca-http-directo)
   - 3.13 [Automatizaciones via Hooks (No ECA UI)](#313-automatizaciones-via-hooks-no-eca-ui)
   - 3.14 [Dart Sass Moderno (@use, color.scale)](#314-dart-sass-moderno-use-colorscale)
   - 3.15 [Premium Cards Glassmorphism](#315-premium-cards-glassmorphism)
   - 3.16 [Compilacion SCSS en Docker](#316-compilacion-scss-en-docker)
   - 3.17 [SDC Components (Compound Variants)](#317-sdc-components-compound-variants)
   - 3.18 [No Hardcoding de Configuracion](#318-no-hardcoding-de-configuracion)
   - 3.19 [Comentarios de Codigo en Espanol](#319-comentarios-de-codigo-en-espanol)
4. [Tabla de Correspondencia con Especificaciones Tecnicas](#4-tabla-de-correspondencia-con-especificaciones-tecnicas)
   - 4.1 [Core Platform (Docs 01-07)](#41-core-platform-docs-01-07)
   - 4.2 [Empleabilidad (Docs 08-24)](#42-empleabilidad-docs-08-24)
   - 4.3 [Emprendimiento (Docs 25-44)](#43-emprendimiento-docs-25-44)
   - 4.4 [AgroConecta (Docs 47-61, 67-68, 80-82)](#44-agroconecta-docs-47-61-67-68-80-82)
   - 4.5 [ComercioConecta (Docs 62-79)](#45-comercioconecta-docs-62-79)
   - 4.6 [ServiciosConecta (Docs 82-99)](#46-serviciosconecta-docs-82-99)
   - 4.7 [Platform Services (Docs 100-117)](#47-platform-services-docs-100-117)
   - 4.8 [AI Trilogy (Docs 128-130)](#48-ai-trilogy-docs-128-130)
   - 4.9 [Infraestructura (Docs 131-140)](#49-infraestructura-docs-131-140)
   - 4.10 [Page Builder y Site Builder (Docs 160-179)](#410-page-builder-y-site-builder-docs-160-179)
   - 4.11 [Credentials y Certifications (Docs 172-174)](#411-credentials-y-certifications-docs-172-174)
   - 4.12 [Analytics y Observabilidad (Docs 179-180)](#412-analytics-y-observabilidad-docs-179-180)
5. [Plan de Implementacion por Fases](#5-plan-de-implementacion-por-fases)
   - 5.1 [Fase 0 — Consolidacion Critica (P0, 1-2 semanas)](#51-fase-0--consolidacion-critica-p0-1-2-semanas)
   - 5.2 [Fase 1 — Completar Modulos Parciales (P1, 4-6 semanas)](#52-fase-1--completar-modulos-parciales-p1-4-6-semanas)
   - 5.3 [Fase 2 — Verticales Pendientes (P1-P2, Q1-Q2 2026)](#53-fase-2--verticales-pendientes-p1-p2-q1-q2-2026)
   - 5.4 [Fase 3 — Platform Services (P2, Q2-Q3 2026)](#54-fase-3--platform-services-p2-q2-q3-2026)
   - 5.5 [Fase 4 — Infraestructura Production-Grade (P2, Q2-Q3 2026)](#55-fase-4--infraestructura-production-grade-p2-q2-q3-2026)
   - 5.6 [Fase 5 — Nuevas Funcionalidades (P3, Q3-Q4 2026)](#56-fase-5--nuevas-funcionalidades-p3-q3-q4-2026)
6. [Arquitectura Frontend — Patron de Implementacion](#6-arquitectura-frontend--patron-de-implementacion)
   - 6.1 [Estructura de Pagina Limpia](#61-estructura-de-pagina-limpia)
   - 6.2 [Inventario de Parciales Existentes](#62-inventario-de-parciales-existentes)
   - 6.3 [Configuracion del Tema via UI de Drupal](#63-configuracion-del-tema-via-ui-de-drupal)
   - 6.4 [Creacion de Nueva Pagina Frontend (Procedimiento)](#64-creacion-de-nueva-pagina-frontend-procedimiento)
   - 6.5 [Patron Slide-Panel para CRUD](#65-patron-slide-panel-para-crud)
7. [Arquitectura SCSS — Implementacion de Referencia](#7-arquitectura-scss--implementacion-de-referencia)
   - 7.1 [Jerarquia de 5 Capas](#71-jerarquia-de-5-capas)
   - 7.2 [SSOT: Archivos Fuente de Verdad](#72-ssot-archivos-fuente-de-verdad)
   - 7.3 [Patron de Compilacion](#73-patron-de-compilacion)
   - 7.4 [Patron de Modulo Satelite](#74-patron-de-modulo-satelite)
8. [Arquitectura de Entidades de Contenido](#8-arquitectura-de-entidades-de-contenido)
   - 8.1 [Checklist Obligatorio por Entidad](#81-checklist-obligatorio-por-entidad)
   - 8.2 [Estructura de Navegacion Drupal](#82-estructura-de-navegacion-drupal)
   - 8.3 [Los 4 YAML Obligatorios](#83-los-4-yaml-obligatorios)
9. [Testing y Calidad](#9-testing-y-calidad)
10. [Aprendizajes Criticos Documentados](#10-aprendizajes-criticos-documentados)
11. [Estimaciones y Roadmap](#11-estimaciones-y-roadmap)
12. [Registro de Cambios](#12-registro-de-cambios)

---

## 1. Resumen Ejecutivo

Este documento constituye el **plan maestro de implementacion integral** para la Jaraba Impact Platform SaaS. Consolida el resultado de una auditoria exhaustiva que cruza:

- **170+ especificaciones tecnicas** en `docs/tecnicos/`
- **44 modulos custom** en `web/modules/custom/`
- **16 workflows de desarrollo** en `.agent/workflows/`
- **Directrices del proyecto** en `docs/00_DIRECTRICES_PROYECTO.md` v6.3
- **Arquitectura de theming** en `docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md`
- **Planes de implementacion** anteriores (20260208-20260211)

**Resultado de la auditoria:**

| Metrica | Valor |
|---------|-------|
| Modulos custom totales | 45 (+jaraba_events) |
| Modulos completos (produccion) | 30 (67%) — +jaraba_events, +jaraba_customer_success |
| Modulos parciales | 10 (22%) — jaraba_customer_success promovido a completo |
| Modulos skeleton | 3 (7%) |
| Funcionalidades no implementadas | 1 (Funding Intelligence) |
| Entidades de contenido | 120+ |
| Servicios | 100+ |
| Controladores | 86+ |
| Configuraciones del tema via UI | 70+ settings |
| Templates de pagina limpia | 26 |
| Parciales reutilizables | 17 |
| Archivos SCSS | 102 |
| Horas estimadas restantes | ~2,775-3,841h (v2.2.0, -190-270h vs v2.1.0) |

**Principio rector:** Cada linea de codigo nueva debe cumplir las 19 directrices de obligado cumplimiento documentadas en la Seccion 3. La inversion en calidad desde el inicio evita deuda tecnica y garantiza clase mundial.

> **COORDINACION CON EQUIPO PARALELO (v2.2.0):**
> Un equipo esta completando `20260210-Plan_Implementacion_Integral_SaaS_v2.md` (Platform Services f108-f117).
> Se ha verificado (2026-02-11 21:00) que este equipo ha completado las siguientes funcionalidades:
>
> | Modulo | Estado | Impacto en este plan |
> |--------|--------|---------------------|
> | `jaraba_customer_success` | **COMPLETADO** (26 archivos, 5 entidades, 6 servicios, 3 controllers) | Elimina tarea 3.2 de Fase 3 (-190-270h) |
> | `jaraba_events` | **COMPLETADO** (18 archivos, 2 entidades, 3 servicios) — Modulo NUEVO no previsto en specs | Documentar en inventario |
> | `jaraba_tenant_knowledge` | **AMPLIADO** (+HelpCenterController, +TenantFaq, +TenantPolicy entities) | Reduce gap Doc 114 a 10-15h |
> | `ecosistema_jaraba_core` PricingRule | **COMPLETADO** (PricingRule entity, PricingRuleEngine, UsageDashboardController) | Reduce gap Doc 111 a 10-15h |
>
> **Regla de no-colision:** Este plan NO debe tocar archivos de estos 4 ambitos. Cualquier dependencia con ellos requiere coordinacion previa.

---

## 2. Estado Actual del SaaS — Inventario Verificado

### 2.1 Modulos Completos (Produccion)

Estos modulos tienen entidades, servicios, controladores, rutas, formularios, templates y SCSS funcionales. Han sido verificados directamente en el codigo fuente.

| # | Modulo | Entidades | Servicios | Controllers | Archivos PHP | Dominio |
|---|--------|-----------|-----------|-------------|--------------|---------|
| 1 | `ecosistema_jaraba_core` | 18 | 51 | 23 | 107 | Core multi-tenant |
| 2 | `jaraba_agroconecta_core` | 83 | 17 | 21 | 162 | AgroConecta vertical |
| 3 | `jaraba_page_builder` | 8 | 12 | 19 | 55 | Constructor paginas |
| 4 | `jaraba_copilot_v2` | — | 8+ | — | 39 | Copiloto emprendimiento |
| 5 | `jaraba_crm` | 4 | 4 | 2 | 19 | CRM nativo |
| 6 | `jaraba_email` | 5 | 6 | 1 | 23 | Email marketing |
| 7 | `jaraba_content_hub` | 4 | 7 | 9 | 29 | Blog + escritor IA |
| 8 | `jaraba_interactive` | 2 | 3 | 4 | 21 | Contenido H5P/xAPI |
| 9 | `jaraba_credentials` | 3 | 7 | 3 | 25 | Open Badges 3.0 |
| 10 | `jaraba_lms` | 6 | — | — | 22 | LMS (cursos, paths) |
| 11 | `jaraba_job_board` | 5 | — | — | 22 | Bolsa de empleo |
| 12 | `jaraba_candidate` | — | — | — | 19 | Perfiles candidatos |
| 13 | `jaraba_matching` | — | — | — | 15+ | Matching empleo |
| 14 | `jaraba_site_builder` | 4 | 5 | 4 | 20 | Arbol paginas + sitemap |
| 15 | `jaraba_foc` | 4 | 6+ | 6 | 20+ | FinOps Center |
| 16 | `jaraba_rag` | — | 5 | 2 | 15+ | RAG + Qdrant |
| 17 | `jaraba_ai_agents` | 3 | 6+ | 2 | 15+ | Orquestacion agentes |
| 18 | `jaraba_mentoring` | — | — | — | 23 | Marketplace mentores |
| 19 | `jaraba_business_tools` | — | — | — | 15+ | Canvas, herramientas |
| 20 | `jaraba_diagnostic` | — | — | — | 15+ | Diagnostico digital |
| 21 | `jaraba_paths` | — | — | — | 15+ | Itinerarios digitalizacion |
| 22 | `jaraba_groups` | — | — | — | 15+ | Grupos colaboracion |
| 23 | `jaraba_resources` | — | — | — | 15+ | Kits digitales |
| 24 | `jaraba_servicios_conecta` | 7 | 3 | — | 21 | ServiciosConecta F1 |
| 25 | `jaraba_comercio_conecta` | — | — | — | 18 | ComercioConecta |
| 26 | `jaraba_skills` | — | — | — | 15+ | IA Skills jerarquicas |
| 27 | `jaraba_tenant_knowledge` | — | — | — | 15+ | Entrenamiento IA tenant |
| 28 | `jaraba_integrations` | 4 | 5 | — | 15+ | Marketplace integraciones |
| 29 | `jaraba_events` | 2 | 3 | 2 | 18 | Marketing Events & Registration **(NUEVO — Equipo paralelo)** |

**Implicacion para nuevas implementaciones:** Antes de crear cualquier funcionalidad nueva, el equipo tecnico DEBE verificar que no exista ya en alguno de estos 29 modulos. La duplicacion de codigo es un anti-patron critico.

### 2.2 Modulos Parciales (Requieren Completar)

Estos modulos tienen estructura base pero les faltan componentes clave para considerarse completos.

| # | Modulo | Estado | Que Falta | Horas Est. |
|---|--------|--------|-----------|------------|
| 1 | `jaraba_pixels` | 70% — 4 CAPI clients implementados | Dashboard de estadisticas, configuracion avanzada, integracion con consent manager | 30-40h |
| 2 | `jaraba_heatmap` | 40% — Collector + Aggregator | Visualizacion Canvas, session replay, dashboard frontend | 30-40h |
| 3 | `jaraba_analytics` | 50% — Eventos + Consent | Core Web Vitals RUM, Search Console, error tracking, uptime monitoring | 60-90h |
| 4 | `jaraba_geo` | 60% — AnswerCapsule + Schema + EEAT | FAQ endpoint completo, generador automatico structured data, dashboard GEO | 20-30h |
| 5 | `jaraba_self_discovery` | 50% — LifeWheel entity + forms | Entidades individuales (Timeline, RIASEC, Strengths), visualizaciones radar/hexagono | 40-60h |
| 6 | `jaraba_performance` | 10% — Solo CriticalCssService | Lazy loading, Core Web Vitals monitoring, admin dashboard, preload hints | 40-50h |
| 7 | `jaraba_theming` | 70% — TenantThemeCustomizerForm | Presets automaticos por vertical, preview en tiempo real, export/import temas | 20-30h |
| 8 | `jaraba_i18n` | 70% — Routing + templates | Dashboard de progreso traducciones, auto-deteccion textos sin traducir | 15-20h |
| ~~9~~ | ~~`jaraba_customer_success`~~ | ~~35%~~ **→ COMPLETADO (v2.2.0)** por equipo paralelo: 5 entidades (CustomerHealth, ChurnPrediction, CsPlaybook, ExpansionSignal, PlaybookExecution), 6 servicios (EngagementScoring, NpsSurvey, LifecycleStage, HealthScoreCalculator, ChurnPrediction con @ai.provider, PlaybookExecutor), 3 controllers (CSMDashboard, CSAdmin, HealthScoresApi). 26 archivos PHP totales | ~~190-270h~~ **0h** |
| 10 | `jaraba_andalucia_ei` | 60% — Gestion participantes | IA hour tracking, PIIL fases, dashboards programa | 40-60h |
| 11 | `jaraba_sepe_teleformacion` | 50% — SOAP client | Integracion completa API SEPE, certificaciones homologadas | 40-60h |
| 12 | `jaraba_journey` | 80% — Engine funcional | Visual flow editor, metricas de conversion por estado | 30-40h |

### 2.3 Modulos Skeleton (Requieren Desarrollo Completo)

| # | Modulo | Estado | Que Tiene | Que Falta | Horas Est. |
|---|--------|--------|-----------|-----------|------------|
| 1 | `jaraba_social_commerce` | 10% | WebhookDispatcher basico | Integraciones Make.com, sync multicanal, ordenes sociales | 60-80h |
| 2 | `jaraba_commerce` | 0% | Wrapper vacio | Es solo un wrapper de Drupal Commerce 3.x; la logica real esta en jaraba_foc y verticales | 0h (intencional) |
| 3 | `ai_provider_google_gemini` | 10% | 1 form | Completo como provider del modulo AI de Drupal | 20-30h |

### 2.4 Funcionalidades No Implementadas

| Funcionalidad | Spec Doc | Prioridad | Horas Est. | Justificacion |
|---------------|----------|-----------|------------|---------------|
| **Funding Intelligence** (BDNS/BOJA/BOE) | Doc 201b-179 | P3 | 520-680h | Nuevo modulo completo. Alta complejidad por integraciones con APIs gubernamentales |

**Redundancias detectadas — NO implementar:**

| Especificacion | Razon de Exclusion | Alternativa Ya Implementada |
|---|---|---|
| CRM externo (ActiveCampaign/HubSpot) | Redundante | `jaraba_crm` nativo con Contact, Company, Opportunity, Activity, pipeline Kanban |
| Email marketing externo (Mailchimp) | Redundante | `jaraba_email` con campanas, secuencias, MJML, EmailAIService |
| Heatmaps externo (Hotjar/Clarity) | Redundante | `jaraba_heatmap` nativo (completar visualizacion) |
| Tag Manager (GTM) | Redundante | `jaraba_pixels` server-side con 4 CAPI clients |
| Page builder externo | Redundante | `jaraba_page_builder` con GrapesJS, 67 bloques, 9 plugins |
| Blog externo (WordPress) | Redundante | `jaraba_content_hub` con ContentWriterAgent, WritingAssistantService |
| Credenciales externo (Credly/Badgr) | Redundante | `jaraba_credentials` Open Badges 3.0, Ed25519, PDF con QR |
| Analytics externo (Mixpanel) | Redundante | `jaraba_analytics` nativo (completar CWV y GSC) |

---

## 3. Directrices de Obligado Cumplimiento

Estas 19 directrices son **obligatorias** para cualquier implementacion nueva. El incumplimiento de cualquiera de ellas constituye deuda tecnica que se debera remediar antes de continuar.

### 3.1 Arquitectura SCSS y Design Tokens Federados

**Fuente:** `docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md`, `.agent/workflows/scss-estilos.md`

**Regla inquebrantable:** NUNCA crear archivos CSS directamente. SIEMPRE crear archivos SCSS que se compilan a CSS. SIEMPRE usar CSS Custom Properties inyectables (`var(--ej-*)`) con fallback SCSS.

**Jerarquia de 5 capas:**

| Capa | Ubicacion | Proposito | Personalizable |
|------|-----------|-----------|----------------|
| 1. SCSS Tokens | `ecosistema_jaraba_core/scss/_variables.scss` | Fallbacks compilacion | No (compile-time) |
| 2. CSS Custom Properties | `ecosistema_jaraba_core/scss/_injectable.scss` | Tokens base `:root` | Si |
| 3. Component Tokens | Parciales SCSS | Scope local componente | Si |
| 4. Tenant Override | `hook_preprocess_html()` | Inyeccion runtime | Si (por tenant) |
| 5. Vertical Presets | Config Entity StylePreset | Paletas por vertical | Si (por vertical) |

**Regla de modulos satelite:**

```scss
// CORRECTO — Modulo satelite solo consume CSS Custom Properties
.mi-componente {
    color: var(--ej-color-corporate, #233D63);
    background: var(--ej-bg-surface, #fff);
    padding: var(--ej-spacing-md, 1rem);
}

// INCORRECTO — Modulo satelite NUNCA define variables SCSS
$ej-color-corporate: #233D63;  // PROHIBIDO fuera de core
```

**Patron obligatorio en todo archivo SCSS de modulo:**

```scss
/**
 * @file
 * [Descripcion del archivo]
 *
 * DIRECTRIZ: Usa Design Tokens con CSS Custom Properties (var(--ej-*))
 *
 * COMPILACION:
 * docker exec jarabasaas_appserver_1 bash -c \
 *   "cd /app/web/modules/custom/[modulo] && npx sass scss/main.scss css/[output].css --style=compressed"
 */
```

### 3.2 Textos de Interfaz Siempre Traducibles (i18n)

**Fuente:** `.agent/workflows/i18n-traducciones.md`, `docs/00_DIRECTRICES_PROYECTO.md` Seccion 1.5

**Regla:** TODO texto visible por el usuario DEBE ser traducible. Sin excepciones.

**Capa PHP (controladores, servicios):**
```php
// CORRECTO
return [
  '#title' => $this->t('Panel de Salud'),
  '#labels' => [
    'refresh' => $this->t('Actualizar'),
    'latency' => $this->t('Latencia'),
  ],
];

// INCORRECTO — Texto hardcodeado
return ['#title' => 'Panel de Salud'];
```

**Capa Twig (templates):**
```twig
{# CORRECTO #}
<h1>{% trans %}Panel de Salud{% endtrans %}</h1>
<button>{% trans %}Actualizar{% endtrans %}</button>

{# INCORRECTO #}
<h1>Panel de Salud</h1>
```

**Capa JavaScript:**
```javascript
// CORRECTO
const mensaje = Drupal.t('Datos actualizados');

// INCORRECTO
const mensaje = 'Datos actualizados';
```

**Abreviaturas y unidades TAMBIEN se traducen:**
```twig
{# CORRECTO #}
5.1 {% trans %}meses{% endtrans %}
1 {% trans %}inquilino{% endtrans %}

{# INCORRECTO #}
5.1 mo
1 tenant
```

**Para proyectos en espanol**, usar texto espanol directamente como source:
```twig
{% trans %}Analítica de Inquilinos{% endtrans %}
```

### 3.3 Variables Inyectables via UI de Drupal

**Fuente:** `docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md`

**Estado verificado:** El tema `ecosistema_jaraba_theme` ya tiene **70+ configuraciones** editables desde `/admin/appearance/settings/ecosistema_jaraba_theme` distribuidas en 13 pestanas:

1. **Identidad de Marca** — 8 colores de marca via color picker
2. **Tipografia** — Seleccion de fuente (9 familias), tamano base (12-24px), 3 colores de texto
3. **Header** — 5 layouts con preview visual, color fondo/texto, sticky, nav items, CTA
4. **Hero** — 5 layouts con preview visual, opacidad overlay
5. **Cards** — Estilo (elevated/outlined/flat/glassmorphism), border-radius, colores
6. **Footer** — 4 layouts, 3 columnas de navegacion, 4 widgets mega footer, redes sociales, copyright
7. **Sidebar** — Colores fondo, titulo, enlace, hover, activo
8. **Componentes** — Colores botones, border-radius, colores inputs
9. **Productos** — Layout producto, colores precio y badge
10. **Avanzadas** — Back-to-top, dark mode (off/on/auto/toggle), preloader, CSS custom
11. **Banner Promo** — Enable, texto, URL, colores
12. **Industry Presets** — 15 presets pre-configurados
13. **Breadcrumbs** — Estilo, iconos, JSON-LD structured data

**Implementacion tecnica de la inyeccion en `hook_preprocess_html()`:**

```php
// ecosistema_jaraba_theme.theme — lineas 860-1073
// Genera :root CSS variables desde theme settings
$variables['#attached']['html_head'][] = [
  [
    '#type' => 'html_tag',
    '#tag' => 'style',
    '#value' => ':root { --ej-color-primary: ' . $color . '; }',
  ],
  'ecosistema_jaraba_custom_vars',
];
```

Ademas, `jaraba_theming` tiene un **TenantThemeCustomizerForm** (800+ lineas) que permite personalizar:
- 9 colores (primary, secondary, accent, success, warning, error, bg, surface, text)
- 9 familias tipograficas
- Dark mode toggle
- Custom CSS por tenant

**Obligacion para nuevo codigo:** Toda propiedad visual que pueda variar entre tenants o verticales DEBE usar `var(--ej-*, fallback)` y NUNCA valores hex hardcodeados.

### 3.4 Paginas Frontend Limpias sin Regiones Drupal

**Fuente:** `.agent/workflows/frontend-page-pattern.md`, `docs/00_DIRECTRICES_PROYECTO.md` Seccion 2.2.2

**Cuando usar:**
- Marketing landings con hero, features, CTA
- Dashboards de usuario autenticado
- Paginas de producto personalizadas
- Portales de acceso (login, onboarding)

**Cuando NO usar:**
- Paginas de administracion (usar layout estandar con regiones)

**Estructura obligatoria de pagina limpia:**

```twig
{#
 * page--{ruta}.html.twig
 *
 * PROPOSITO: Pagina frontend limpia sin regiones de Drupal.
 * PATRON: HTML completo con {% include %} de parciales reutilizables.
 * DIRECTRIZ: Todas las paginas publicas del SaaS usan este patron.
 #}
{% set site_name = site_name|default('Jaraba Impact Platform') %}

{{ attach_library('ecosistema_jaraba_theme/global') }}
{{ attach_library('ecosistema_jaraba_theme/{libreria-especifica}') }}

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

  {# HEADER — Parcial reutilizable con variantes configurables #}
  {% include '@ecosistema_jaraba_theme/partials/_header.html.twig' with {
    site_name: site_name,
    logo: logo|default(''),
    logged_in: logged_in,
    theme_settings: theme_settings|default({})
  } %}

  {# MAIN — Layout full-width, mobile-first #}
  <main id="main-content" class="{tipo}-main">
    <div class="{tipo}-wrapper">
      {{ page.content }}
    </div>
  </main>

  {# FOOTER — Parcial reutilizable con 4 variantes #}
  {% include '@ecosistema_jaraba_theme/partials/_footer.html.twig' with {
    site_name: site_name,
    logo: logo|default(''),
    theme_settings: theme_settings|default({})
  } %}

  <js-bottom-placeholder token="{{ placeholder_token }}">
</body>
</html>
```

**26 paginas limpias ya implementadas** (verificado en tema):

`page--front`, `page--page-builder`, `page--site-builder`, `page--content-hub`, `page--dashboard`, `page--auth`, `page--canvas-editor`, `page--page-builder-experiments`, `page--integrations`, `page--customer-success`, `page--vertical-landing`, `page--credentials`, `page--my-certifications`, `page--crm`, `page--user`, `page--i18n`, `page--admin--pixels`, `page--pixels`, `page--pixels--stats`, `page--revisions`, `page--skills`, `page--verify`, `page--interactive`, `page--comercio-marketplace`, `page--andalucia-ei`

### 3.5 Templates Parciales Reutilizables

**Fuente:** `docs/00_DIRECTRICES_PROYECTO.md` Seccion 2.2.3

**Antes de extender una pagina, PREGUNTARSE:**
1. Existe ya un parcial para este componente? (ver inventario abajo)
2. Lo voy a necesitar en mas de una pagina? Si si, crear parcial.
3. El parcial usa variables configurables desde UI? Debe hacerlo.

**Inventario de parciales existentes** (verificado en `templates/partials/`):

| Parcial | Proposito | Configurabilidad |
|---------|-----------|-----------------|
| `_header.html.twig` | Dispatcher que enruta a sub-variante segun `theme_settings.header_layout` | 5 variantes (classic, centered, hero, split, minimal) |
| `_header-classic.html.twig` | Header clasico: logo izquierda, nav derecha, CTA | Nav items, colores, CTA via theme settings |
| `_header-centered.html.twig` | Header centrado | Misma config que classic |
| `_header-minimal.html.twig` | Solo logo + hamburguesa | Misma config |
| `_header-split.html.twig` | Logo centro, nav a ambos lados | Misma config |
| `_header-hero.html.twig` | Header transparente sobre hero | Misma config |
| `_footer.html.twig` | Dispatcher de footer con 4 variantes (minimal, standard, mega, split) | Layout, columnas nav, widgets, redes sociales, copyright via theme settings |
| `_hero.html.twig` | Dispatcher de hero section | 5 variantes (fullscreen, split, compact, animated, centered), overlay opacity |
| `_features.html.twig` | Seccion de features/value proposition | Array dinamico de features |
| `_stats.html.twig` | Seccion de estadisticas/social proof | Array dinamico de stats |
| `_intentions-grid.html.twig` | Grid de intenciones (cards verticales) | Array de intenciones, efecto tilt 3D |
| `_related-articles.html.twig` | Widget de articulos relacionados | Recomendaciones dinamicas |
| `_article-card.html.twig` | Card individual de articulo | Metadata articulo |
| `_category-filter.html.twig` | Widget filtro por categorias | Categorias dinamicas |
| `_auth-hero.html.twig` | Hero izquierdo de paginas auth | Iconos duotone, copy dinamico |
| `_copilot-fab.html.twig` | FAB del copiloto contextual | Config agente, texto saludo |
| `vertical-landing-content.html.twig` | Contenido especifico vertical | Info vertical dinamica |

**Componentes de variantes** (en `templates/components/`):

| Directorio | Variantes |
|------------|-----------|
| `header/` | classic, centered, mega, minimal, sidebar, transparent |
| `hero/` | fullscreen, split, compact, animated, slider |
| `card/` | default, course, horizontal, cta, metric, product, testimonial, profile |

**Regla:** Si un componente nuevo se necesita en 2+ paginas, DEBE crearse como parcial en `templates/partials/` con variables configurables, NO duplicar HTML entre templates.

### 3.6 Slide-Panel Modal para CRUD

**Fuente:** `.agent/workflows/slide-panel-modales.md`

**Regla critica:** TODAS las acciones de crear/editar/ver en una pagina frontend DEBEN abrirse en slide-panel modal. El usuario NUNCA abandona la pagina en la que trabaja.

**Activacion en HTML:**
```html
<button class="btn"
        data-slide-panel="mi-formulario"
        data-slide-panel-url="/ruta/ajax/formulario"
        data-slide-panel-title="{% trans %}Nuevo Artículo{% endtrans %}">
  + {% trans %}Crear{% endtrans %}
</button>
```

**Deteccion AJAX obligatoria en Controller:**
```php
public function add(Request $request): array|Response {
    $entity = $this->entityTypeManager()->getStorage('mi_entidad')->create();
    $form = $this->entityFormBuilder()->getForm($entity, 'add');

    // AJAX → solo HTML del formulario
    if ($request->isXmlHttpRequest()) {
        $html = (string) $this->renderer->render($form);
        return new Response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    // Normal → pagina completa
    return ['#theme' => 'mi_template', '#form' => $form];
}
```

**Ocultar ruido de formularios Drupal (PHP, mas fiable que CSS):**
```php
function mimodulo_form_alter(array &$form, FormStateInterface $form_state, string $form_id): void {
  if (str_contains($form_id, 'mi_entidad')) {
    _mimodulo_hide_format_guidelines($form);
  }
}

function _mimodulo_hide_format_guidelines(array &$element): void {
  if (isset($element['format'])) {
    $element['format']['#access'] = FALSE;
  }
  foreach (array_keys($element) as $key) {
    if (is_array($element[$key]) && !str_starts_with((string) $key, '#')) {
      _mimodulo_hide_format_guidelines($element[$key]);
    }
  }
}
```

**Dependencia obligatoria en `*.libraries.yml`:**
```yaml
dependencies:
  - ecosistema_jaraba_theme/slide-panel
```

**Aprendizaje critico:** Llamar `Drupal.detachBehaviors()` antes de limpiar el body del panel para evitar acumulacion de scripts de admin.

### 3.7 Content Entities con Field UI y Views

**Fuente:** `.agent/workflows/drupal-custom-modules.md`, `docs/00_DIRECTRICES_PROYECTO.md` Seccion 5

**Regla:** Todo dato de negocio que deba ser editable desde UI DEBE ser Content Entity con Field UI, Views integration, y navegacion completa en admin.

**Decision ConfigEntity vs ContentEntity:**

| Criterio | ConfigEntity | ContentEntity |
|----------|-------------|---------------|
| Datos de usuario/operacionales | No | Si |
| Exportable a Git (YAML) | Si | No |
| Field UI (anadir campos desde admin) | No | Si |
| Soporte completo Views | Limitado | Si |
| Admin puede anadir campos sin codigo | No | Si |

**Regla:** Si el admin del SaaS necesita anadir campos custom sin codigo → **ContentEntity**. Si los campos son fijos en codigo → **ConfigEntity**.

**Anotacion obligatoria en entidad:**
```php
/**
 * @ContentEntityType(
 *   ...
 *   handlers = {
 *     "list_builder" = "...\MiEntidadListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = { "default", "add", "edit", "delete" },
 *     "access" = "...\MiEntidadAccessControlHandler",
 *     "route_provider" = { "html" = "...\AdminHtmlRouteProvider" },
 *   },
 *   field_ui_base_route = "entity.mi_entidad.settings",
 * )
 */
```

### 3.8 Sistema de Iconos Dual (Outline + Duotone)

**Fuente:** `.agent/workflows/scss-estilos.md`

**Regla:** SIEMPRE crear AMBAS versiones de cada icono nuevo:
1. `{nombre}.svg` — Outline (stroke)
2. `{nombre}-duotone.svg` — Duotone (2 tonos con opacidad)

**Estructura SVG duotone:**
```svg
<!-- Capa fondo (opacity 0.3) -->
<path d="..." fill="currentColor" opacity="0.3"/>
<!-- Capa principal (stroke o solid fill) -->
<path d="..." stroke="currentColor" stroke-width="2"/>
```

**Ubicacion:** `web/modules/custom/ecosistema_jaraba_core/images/icons/`

| Carpeta | Contenido |
|---------|-----------|
| `analytics/` | Charts, metricas |
| `business/` | Empresa, diagnostico, objetivos |
| `ai/` | IA, automatizacion, cerebro |
| `ui/` | Interfaz, navegacion |
| `actions/` | CRUD, refresh, download |
| `verticals/` | Iconos especificos de vertical |

**Uso en Twig:**
```twig
{# Outline (default) — KPIs, botones, elementos pequenos #}
{{ jaraba_icon('business', 'diagnostic', { color: 'azul-corporativo', size: '24px' }) }}

{# Duotone — Headers de seccion, cards destacadas, impacto visual #}
{{ jaraba_icon('business', 'diagnostic', { variant: 'duotone', color: 'naranja-impulso', size: '32px' }) }}
```

### 3.9 Paleta de Colores Jaraba Oficial

**Fuente:** `.agent/workflows/scss-estilos.md`, `ecosistema_jaraba_core/scss/_variables.scss`

**Colores de marca:**

| SCSS Variable | CSS Variable | Hex | Uso Semantico |
|---------------|-------------|-----|---------------|
| `$azul-profundo` | `--ej-color-azul-profundo` | `#003366` | Autoridad, profundidad |
| `$azul-verdoso` | `--ej-color-azul-verdoso` | `#2B7A78` | Conexion, equilibrio |
| `$azul-corporativo` | `--ej-color-corporate` | `#233D63` | Logo "J", confianza, base |
| `$naranja-impulso` | `--ej-color-impulse` | `#FF8C42` | Emprendimiento, CTAs |
| `$verde-innovacion` | `--ej-color-innovation` | `#00A9A5` | Talento, empleabilidad |
| `$verde-oliva` | `--ej-color-agro` | `#556B2F` | AgroConecta, naturaleza |
| `$verde-oliva-oscuro` | `--ej-color-agro-dark` | `#3E4E23` | AgroConecta intenso |

**Colores UI extendidos:**

| CSS Variable | Hex | Descripcion |
|--------------|-----|-------------|
| `--ej-color-primary` | `#4F46E5` | Indigo — Acciones UI principales |
| `--ej-color-secondary` | `#7C3AED` | Violeta — IA, features premium |
| `--ej-color-success` | `#10B981` | Esmeralda — Estados positivos |
| `--ej-color-warning` | `#F59E0B` | Ambar — Alertas |
| `--ej-color-danger` | `#EF4444` | Rojo — Errores, destructivo |
| `--ej-color-neutral` | `#64748B` | Slate — Muted, deshabilitado |

### 3.10 Body Classes via hook_preprocess_html

**Fuente:** `docs/00_DIRECTRICES_PROYECTO.md` Seccion 2.2.2, `.agent/workflows/frontend-page-pattern.md`

**ADVERTENCIA CRITICA:** Las clases anadidas con `attributes.addClass()` en templates Twig **NO FUNCIONAN para `<body>`**. Drupal renderiza `<body>` en `html.html.twig`, no en `page.html.twig`.

**SIEMPRE usar `hook_preprocess_html()`:**

```php
function ecosistema_jaraba_theme_preprocess_html(&$variables) {
  $route = \Drupal::routeMatch()->getRouteName();

  if ($route === 'mi_modulo.mi_ruta') {
    $variables['attributes']['class'][] = 'page-mi-ruta';
    $variables['attributes']['class'][] = 'dashboard-page';
  }
}
```

**NO crear funciones duplicadas** — anadir logica a la funcion existente en `ecosistema_jaraba_theme.theme` (2,157 lineas, ya gestiona 17+ rutas).

### 3.11 Tenant sin Acceso al Tema Admin

**Fuente:** `docs/00_DIRECTRICES_PROYECTO.md` Seccion 3, 4.1

El tenant opera exclusivamente en el frontend limpio. Nunca ve el tema de administracion de Drupal. Las herramientas de gestion (pages, CRM, email, etc.) se presentan como dashboards frontend limpios con sus propias `page--*.html.twig`.

**Roles y accesos:**

| Rol | Frontend Limpio | Admin Drupal |
|-----|----------------|--------------|
| Administrador | Si | Si |
| Gestor de Sede/Tenant | Si | No |
| Productor/Proveedor | Si | No |
| Cliente/Candidato | Si | No |
| Anonimo | Si (publico) | No |

### 3.12 IA via Drupal AI Module (Nunca HTTP Directo)

**Fuente:** `docs/00_DIRECTRICES_PROYECTO.md` Seccion 2.10

```php
// CORRECTO — Via abstraccion Drupal AI Module
use Drupal\ai\AiProviderPluginManager;

class MiServicio {
    public function __construct(
        private AiProviderPluginManager $aiProvider,
    ) {}

    public function chat(string $mensaje, string $modo): array {
        $provider = $this->getProviderForMode($modo);
        $llm = $this->aiProvider->createInstance($provider);
        return $llm->chat([
            ['role' => 'user', 'content' => $mensaje]
        ], $this->getModelForMode($modo));
    }
}

// INCORRECTO — HTTP directo PROHIBIDO
$response = $client->post('https://api.anthropic.com/v1/messages', [...]);
```

**Especializacion por tarea:**

| Modo | Provider | Modelo | Razon |
|------|----------|--------|-------|
| Coach Emocional | Anthropic | claude-3-5-sonnet | Empatia superior |
| CFO Sintetico | OpenAI | gpt-4o | Mejor en calculos |
| Fiscal/Laboral | Anthropic | claude-3-5-sonnet | RAG + Grounding |
| Clasificacion simple | Anthropic | claude-3-haiku | Economico |

### 3.13 Automatizaciones via Hooks (No ECA UI)

**Fuente:** `.agent/workflows/drupal-eca-hooks.md`

Las automatizaciones de modulos custom usan hooks en `.module`, NO ECA BPMN UI:

- `hook_entity_insert()` — Nuevas entidades
- `hook_entity_update()` — Cambios de estado
- `hook_cron()` — Tareas programadas
- `hook_mail()` — Templates email
- Queues para procesamiento asincrono

**ECA UI solo para:** workflows configurables por el usuario final, automatizaciones no criticas.

### 3.14 Dart Sass Moderno (@use, color.scale)

**Fuente:** `.agent/workflows/scss-estilos.md`, aprendizaje 2026-02-09

**Regla critica:** Cada parcial SCSS DEBE declarar sus propios imports. Las variables de `main.scss` NO se propagan a los parciales cargados con `@use`.

```scss
// CORRECTO — Cada parcial con imports propios
@use 'sass:color';
@use 'variables' as *;

.mi-componente {
  color: var(--ej-primary, $mi-vertical-primary);
  background: color.scale($mi-vertical-primary, $lightness: 85%);
}

// INCORRECTO — Depender de main.scss para propagar variables
.mi-componente {
  color: $mi-vertical-primary; // ERROR: Variable no definida
}
```

**Funciones de color:**
```scss
@use 'sass:color';

// CORRECTO — Dart Sass moderno
background: color.scale($base, $lightness: 85%);
background: color.adjust($base, $lightness: -10%);

// INCORRECTO — Funciones deprecadas
background: lighten($base, 85%);  // PROHIBIDO
background: darken($base, 10%);   // PROHIBIDO
```

### 3.15 Premium Cards Glassmorphism

**Fuente:** `.agent/workflows/premium-cards-pattern.md`

**Aplicar SIEMPRE a:** Cards de KPI/estadisticas, cards de integraciones, cards de acciones rapidas, cards de configuracion.

**Valores obligatorios:**

| Propiedad | Valor |
|-----------|-------|
| Curva transicion | `cubic-bezier(0.175, 0.885, 0.32, 1.275)` |
| Glassmorphism blur | `10px` minimo |
| Border-radius | `16px` (`--ej-radius-xl`) |
| Hover lift | `translateY(-6px)` |
| Hover scale | `scale(1.02)` |

```scss
.premium-card {
    position: relative;
    overflow: hidden;
    padding: var(--ej-spacing-lg, 1.5rem);
    background: linear-gradient(135deg, rgba(255,255,255,0.95), rgba(248,250,252,0.9));
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border-radius: var(--ej-radius-xl, 16px);
    border: 1px solid rgba(255,255,255,0.8);
    box-shadow: 0 4px 24px rgba(0,0,0,0.04), 0 1px 2px rgba(0,0,0,0.02),
                inset 0 1px 0 rgba(255,255,255,0.9);
    transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275), box-shadow 0.3s ease;

    &::before {
        content: '';
        position: absolute;
        top: 0; left: -100%;
        width: 50%; height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
        transition: left 0.6s ease;
        pointer-events: none;
    }

    &:hover {
        transform: translateY(-6px) scale(1.02);
        box-shadow: 0 20px 40px rgba(35,61,99,0.12), 0 8px 16px rgba(35,61,99,0.08),
                    inset 0 1px 0 rgba(255,255,255,1);
        &::before { left: 150%; }
    }
}
```

### 3.16 Compilacion SCSS en Docker

**Fuente:** `.agent/workflows/scss-estilos.md`, aprendizaje 2026-01-20

**Comando estandar dentro del contenedor:**
```bash
docker exec jarabasaas_appserver_1 bash -c \
  "cd /app/web/modules/custom/[modulo] && npx sass scss/main.scss css/[output].css --style=compressed"
docker exec jarabasaas_appserver_1 drush cr
```

**Para el tema** (atencion: el archivo que carga es `css/main.css`, NO `css/ecosistema-jaraba-theme.css`):
```bash
docker exec jarabasaas_appserver_1 bash -c \
  "cd /app/web/themes/custom/ecosistema_jaraba_theme && npx sass scss/main.scss css/main.css --style=compressed"
docker exec jarabasaas_appserver_1 drush cr
```

**Cada modulo SCSS DEBE tener `package.json`:**
```json
{
    "name": "jaraba-[modulo]",
    "version": "1.0.0",
    "scripts": {
        "build": "sass scss/main.scss:css/[output].css --style=compressed",
        "watch": "sass --watch scss:css --style=compressed"
    },
    "devDependencies": { "sass": "^1.71.0" }
}
```

### 3.17 SDC Components (Compound Variants)

**Fuente:** `.agent/workflows/sdc-components.md`

**Regla:** UN template con multiples variantes via condicionales, NO templates separados por variante.

```
components/{nombre}/
  {nombre}.component.yml   # Props y slots tipados
  {nombre}.twig            # UN template, todas variantes
  {nombre}.scss            # SCSS (NO .css)
```

**Checklist SDC:**
- [ ] 3 archivos creados (.yml, .twig, .scss)
- [ ] Props tipados en component.yml
- [ ] Slots definidos
- [ ] Todas variantes en un solo template con `{% if variant == 'x' %}`
- [ ] `{% trans %}` para texto traducible
- [ ] `jaraba_icon()` para iconos
- [ ] `var(--ej-*)` para colores/espaciado

### 3.18 No Hardcoding de Configuracion

**Fuente:** `docs/00_DIRECTRICES_PROYECTO.md` Seccion 5

```php
// INCORRECTO — Limite hardcodeado
if ($count >= 10) { throw new Exception("Limite"); }

// CORRECTO — Desde Content Entity configurable
$plan = $tenant->get('field_plan')->entity;
$limite = $plan->get('field_max_productores')->value;
if ($count >= $limite) { throw new Exception($this->t("Limite del plan alcanzado")); }
```

**Valores que NUNCA se hardcodean:**
- Limites de plan (productores, storage, features)
- Feature flags
- Precios
- Umbrales de descuento
- Endpoints de API (usar config entities)
- Estados de workflow

### 3.19 Comentarios de Codigo en Espanol

**Fuente:** `docs/00_DIRECTRICES_PROYECTO.md` Seccion 10

**Tres dimensiones obligatorias:**

1. **Estructura** — Como se organiza el codigo, relaciones, patrones
2. **Logica** — Proposito, reglas de negocio, flujo, edge cases
3. **Sintaxis** — Parametros con tipos, retornos, excepciones, tipos complejos

**Idioma:** Comentarios en espanol. Nombres de variables/funciones en ingles.

```php
/**
 * Servicio de validacion de planes SaaS.
 *
 * ESTRUCTURA: Servicio inyectado en controladores de tenant.
 * Depende de la entidad SaasPlan y TenantManager.
 *
 * LOGICA: Verifica que el tenant no exceda los limites de su plan
 * actual antes de permitir operaciones que consumen recursos
 * (nuevos productores, storage, features premium).
 */
class PlanValidator {
    /**
     * Verifica si el tenant puede anadir mas productores.
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\Tenant $tenant
     *   La entidad del tenant a verificar.
     *
     * @return bool
     *   TRUE si puede anadir, FALSE si ha alcanzado el limite.
     *
     * @throws \Drupal\ecosistema_jaraba_core\Exception\PlanLimitException
     *   Si el plan del tenant no existe o esta inactivo.
     */
    public function canAddProducer(Tenant $tenant): bool {
        // Obtenemos el plan del tenant via entity reference
        $plan = $tenant->get('field_plan')->entity;
        // ...
    }
}
```

---

## 4. Tabla de Correspondencia con Especificaciones Tecnicas

Esta tabla cruza CADA especificacion tecnica con el modulo responsable, su estado de implementacion verificado, y las acciones pendientes.

### 4.1 Core Platform (Docs 01-07)

| Doc | Titulo | Modulo | Estado | Notas |
|-----|--------|--------|--------|-------|
| 01 | Core Entidades y Esquema BD | `ecosistema_jaraba_core` | Implementado | 18 entidades, esquema completo |
| 02 | Core Modulos Personalizados | `ecosistema_jaraba_core` | Implementado | 51 servicios |
| 03 | Core APIs y Contratos | `ecosistema_jaraba_core` | Implementado | 39KB routing file |
| 04 | Core Permisos RBAC | `ecosistema_jaraba_core` | Implementado | RbacMatrixController |
| 05 | Core Theming jaraba_theme | `ecosistema_jaraba_theme` + `jaraba_theming` | Implementado | 70+ settings, 15 presets |
| 06 | Core Flujos ECA | `ecosistema_jaraba_core` (hooks) | Implementado | Hooks en .module, NO ECA UI |
| 07 | Core Configuracion Multi-Tenant | `ecosistema_jaraba_core` | Implementado | Group Module, TenantManager |

### 4.2 Empleabilidad (Docs 08-24)

| Doc | Titulo | Modulo | Estado | Acciones Pendientes |
|-----|--------|--------|--------|---------------------|
| 08 | LMS Core | `jaraba_lms` | Implementado | — |
| 09 | Learning Paths | `jaraba_lms` | Implementado | — |
| 10 | Progress Tracking | `jaraba_lms` | Implementado | — |
| 11 | Job Board Core | `jaraba_job_board` | Implementado | — |
| 12 | Application System | `jaraba_job_board` | Implementado | — |
| 13 | Employer Portal | `jaraba_job_board` | Implementado | — |
| 14 | Job Alerts | `jaraba_job_board` | Implementado | — |
| 15 | Candidate Profile | `jaraba_candidate` | Implementado | — |
| 16 | CV Builder | `jaraba_candidate` | Implementado | — |
| 17 | Credentials System | `jaraba_credentials` | Implementado | 85% — Stackable extensions pendiente |
| 18 | Certification Workflow | `jaraba_credentials` | Implementado | — |
| 19 | Matching Engine | `jaraba_matching` | Implementado | — |
| 20 | AI Copilot | `jaraba_copilot_v2` | Implementado | — |
| 21 | Recommendation System | `jaraba_matching` | Implementado | — |
| 22 | Dashboard JobSeeker | `jaraba_job_board` | Implementado | — |
| 23 | Dashboard Employer | `jaraba_job_board` | Implementado | — |
| 24 | Impact Metrics | `jaraba_analytics` | Parcial | Completar metricas de impacto empleabilidad |
| 125-160 | Self-Discovery Tools | `jaraba_self_discovery` | Parcial (50%) | Entidades individuales (Timeline, RIASEC, Strengths), visualizaciones |

### 4.3 Emprendimiento (Docs 25-44)

| Doc | Titulo | Modulo | Estado | Acciones Pendientes |
|-----|--------|--------|--------|---------------------|
| 25 | Business Diagnostic Core | `jaraba_diagnostic` | Implementado | — |
| 26 | Digital Maturity Assessment | `jaraba_diagnostic` | Implementado | — |
| 27 | Competitive Analysis Tool | `jaraba_business_tools` | Implementado | — |
| 28 | Digitalization Paths | `jaraba_paths` | Implementado | — |
| 29 | Action Plans | `jaraba_paths` | Implementado | — |
| 30 | Progress Milestones | `jaraba_paths` | Implementado | — |
| 31 | Mentoring Core | `jaraba_mentoring` | Implementado | — |
| 32 | Mentoring Sessions | `jaraba_mentoring` | Implementado | — |
| 33 | Mentor Dashboard | `jaraba_mentoring` | Implementado | — |
| 34 | Collaboration Groups | `jaraba_groups` | Implementado | — |
| 35 | Networking Events | `jaraba_groups` | Implementado | — |
| 36 | Business Model Canvas | `jaraba_business_tools` | Implementado | — |
| 37 | MVP Validation | `jaraba_business_tools` | Implementado | — |
| 38 | Financial Projections | `jaraba_business_tools` | Implementado | — |
| 39 | Digital Kits | `jaraba_resources` | Implementado | — |
| 40 | Membership System | `jaraba_resources` | Implementado | — |
| 41 | Dashboard Entrepreneur | `jaraba_business_tools` | Implementado | — |
| 42 | Dashboard Program | `jaraba_business_tools` | Implementado | — |
| 43 | Impact Metrics | `jaraba_analytics` | Parcial | Metricas impacto emprendimiento |
| 44 | AI Business Copilot | `jaraba_copilot_v2` | Implementado | — |
| 121a | Copiloto Emprendimiento v2.0 | `jaraba_copilot_v2` | Implementado | 5 modos adaptativos, 44 experimentos |
| 121e | Router Inteligente Multi-Proveedor | `jaraba_ai_agents` | Implementado | ModelRouterService |

### 4.4 AgroConecta (Docs 47-61, 67-68, 80-82)

| Doc | Titulo | Modulo | Estado | Acciones Pendientes |
|-----|--------|--------|--------|---------------------|
| 47 | Commerce Core | `jaraba_agroconecta_core` | Implementado (F1) | — |
| 48 | Product Catalog | `jaraba_agroconecta_core` | Implementado (F1) | — |
| 49 | Order System | `jaraba_agroconecta_core` | Implementado (F2) | — |
| 50 | Checkout Flow | `jaraba_agroconecta_core` | Implementado (F2) | — |
| 51 | Shipping Logistics | `jaraba_agroconecta_core` | **Pendiente (F6)** | Sprint AC6-1/2: Shipping core |
| 52 | Producer Portal | `jaraba_agroconecta_core` | Implementado (F3) | — |
| 53 | Customer Portal | `jaraba_agroconecta_core` | Implementado (F3) | — |
| 54 | Reviews System | `jaraba_agroconecta_core` | Parcial (F4) | Completar notificaciones |
| 55 | Search Discovery | `jaraba_agroconecta_core` | **Pendiente (F5)** | Sprint AC5-1/2 |
| 56 | Promotions Coupons | `jaraba_agroconecta_core` | **Pendiente (F5)** | Sprint AC5-3/4 |
| 57 | Analytics Dashboard | `jaraba_agroconecta_core` | **Pendiente (F7)** | Sprint AC7-1/2 |
| 58 | Admin Panel | `jaraba_agroconecta_core` | **Pendiente (F8)** | Sprint AC8-1/2 |
| 67 | Producer Copilot | `jaraba_agroconecta_core` | **Pendiente (F7)** | Sprint AC7-3/4: RAG + 6 capacidades |
| 68 | Sales Agent | `jaraba_agroconecta_core` | **Pendiente (F7)** | Sprint AC7-5/6 |
| 80 | Traceability System | `jaraba_agroconecta_core` | **Pendiente (F6)** | Sprint AC6-3/4: QR + blockchain hash |
| 81 | QR Dashboard | `jaraba_agroconecta_core` | **Pendiente (F6)** | Sprint AC6-3/4 |
| 82 | B2B Document Hub | `jaraba_agroconecta_core` | Implementado (AC6-2) | 17 API endpoints |

### 4.5 ComercioConecta (Docs 62-79)

| Doc | Titulo | Modulo | Estado | Acciones Pendientes |
|-----|--------|--------|--------|---------------------|
| 62-79 | Retail Commerce completo | `jaraba_comercio_conecta` | **Pendiente** | Fase 1: 80-120h (reutiliza 70% AgroConecta). 4 fases totales |

**Estrategia:** Maxima reutilizacion de `jaraba_agroconecta_core` con especializaciones para retail. Entidades propias: ProductRetail, ProductVariationRetail, StockLocation, MerchantProfile.

### 4.6 ServiciosConecta (Docs 82-99)

| Doc | Titulo | Modulo | Estado | Acciones Pendientes |
|-----|--------|--------|--------|---------------------|
| 82-85 | Services Core + Profiles + Booking | `jaraba_servicios_conecta` | Implementado (F1) | 7 entidades, 3 servicios, 5 taxonomias |
| 86-99 | Calendar, Video, Firma, IA Triage, Auto-Quote, Copilot, Dashboards, Invoicing | `jaraba_servicios_conecta` | **Pendiente (F2+)** | 300-400h, requires calendar sync, video conferencing, PAdES signing |

### 4.7 Platform Services (Docs 100-117)

| Doc | Titulo | Modulo | % Impl. | Gap (Horas) | Prioridad |
|-----|--------|--------|---------|-------------|-----------|
| 100 | Frontend Architecture | Tema + Core | 95% | 0h | — |
| 101 | Design Tokens | Core + Theming | 90% | 10-15h | P3 |
| 108 | AI Agent Flows | `jaraba_ai_agents` | 90% | 40-60h (visual builder) | P2 |
| 109 | PWA Mobile | Core + Tema | 70% | 65-90h | P2 |
| 110 | Onboarding | Core (OnboardingController) | 95% | 15-25h (A/B testing) | P3 |
| 111 | Usage-Based Pricing | Core (TenantMeteringService + **PricingRule** + **PricingRuleEngine** + **UsageDashboardController**) | **95%** | **10-15h** (polish UI, alertas umbral) | P3 |
| 112 | Integration Marketplace | `jaraba_integrations` | **40%** | **215-295h** | **P0** |
| 113 | Customer Success | `jaraba_customer_success` | **100% — COMPLETADO** por equipo paralelo (5 entidades, 6 servicios, churn AI, NPS, playbooks) | **0h** | — |
| 114 | Knowledge Base | `jaraba_tenant_knowledge` (**+HelpCenterController**, **+TenantFaq**, **+TenantPolicy**) | **95%** | **10-15h** (SEO help center, analytics) | P3 |
| 115 | Security & Compliance | Core (SecurityHeadersSubscriber) | 75% | 50-80h + audits SOC2 | P2 |
| 116 | Advanced Analytics | `jaraba_analytics` | 80% | 65-90h (cohortes + funnels) | P2 |
| 117 | White-Label | Core (TenantDomainSettingsForm) | 70% | 90-130h (DNS, SSL auto) | P2 |

### 4.8 AI Trilogy (Docs 128-130)

| Doc | Titulo | Modulo | Estado | Notas |
|-----|--------|--------|--------|-------|
| 128 | AI Content Hub | `jaraba_content_hub` | **Implementado** | F1-F5 sprints completos |
| 129 | AI Skills System | `jaraba_skills` | **Implementado** | G1-G8 sprints completos |
| 130 | Tenant Knowledge Training | `jaraba_tenant_knowledge` | **Implementado + AMPLIADO (v2.2.0)** | TK1-TK6 sprints, 18 tests Cypress. Equipo paralelo anade: HelpCenterController (/ayuda), TenantFaq entity (FAQ semanticas Qdrant), TenantPolicy entity (politicas versionadas), KnowledgeRevisionService. Total: 25 archivos, 6 entidades, 4 servicios, 4 controllers |

### 4.9 Infraestructura (Docs 131-140)

| Doc | Titulo | Estado | Horas Est. | Prioridad |
|-----|--------|--------|------------|-----------|
| 131 | Deployment (IONOS, Docker) | **Pendiente** | 60-80h | P1 |
| 132 | CI/CD (GitHub Actions) | **Pendiente** | 40-60h | P1 |
| 133 | Monitoring (Prometheus, Grafana) | **Pendiente** | 40-50h | P1 |
| 134 | Stripe Billing Integration | **Parcial (35-40%)** | 150-200h | **P0** |
| 135 | Testing Strategy (PHPUnit >= 80%) | **Pendiente** | 80-120h | P0 |
| 136 | Email Templates (MJML) | Parcial | 30-40h | P2 |
| 137 | API Gateway & Developer Portal | **Pendiente** | 60-80h | P2 |
| 138 | Security Audit & GDPR | **Pendiente** | 40-60h | P2 |
| 139 | Go-Live Runbook | **Pendiente** | 20-30h | P1 |
| 140 | User Manuals & Videos | **Pendiente** | 40-60h | P3 |

### 4.10 Page Builder y Site Builder (Docs 160-179)

| Doc | Titulo | Modulo | Estado | Notas |
|-----|--------|--------|--------|-------|
| 160/162 | Page Builder System | `jaraba_page_builder` | Implementado | 67 bloques, 9 plugins GrapesJS, A/B testing |
| 163 | Bloques Premium | `jaraba_page_builder` | Implementado | Aceternity UI + Magic UI |
| 164 | Platform SEO/GEO | `jaraba_geo` | Parcial | AnswerCapsule + Schema + EEAT |
| 165 | Gap Analysis PageBuilder | N/A | Referencia | — |
| 166 | Platform i18n | `jaraba_i18n` | Parcial (70%) | Dashboard progreso traducciones pendiente |
| 167 | Platform Analytics PB | `jaraba_analytics` | Parcial | Metricas de paginas pendiente |
| 168 | Platform A/B Testing | `jaraba_page_builder` | Implementado | ExperimentService |
| 169 | Platform Page Versioning | `jaraba_page_builder` | Implementado | Via Drupal entity revisions |
| 170 | Platform Accessibility WCAG | Tema | Parcial | _accessibility.scss, skip links, ARIA |
| 171 | Platform ContentHub PB | `jaraba_content_hub` | Implementado | Blog + escritor IA |
| 176 | Site Structure Manager | `jaraba_site_builder` | Implementado | SitePageTree, sitemap, redirects |
| 177 | Global Navigation | Tema partials | Implementado | 5 header + 4 footer variantes configurables |
| 178 | Blog System Nativo | `jaraba_content_hub` | Implementado | — |
| 179a | Insights Hub | `jaraba_analytics` | Parcial (50%) | CWV RUM, GSC, error tracking, uptime pendientes |
| 180 | Native Heatmaps | `jaraba_heatmap` | Parcial (40%) | Visualizacion Canvas, session replay pendiente |
| 181 | Premium Preview System | `jaraba_page_builder` | Implementado | Preview data YAML |
| 204b | Canvas Editor v3 (GrapesJS) | `jaraba_page_builder` | Implementado | Full-page editing, 3 regiones |

### 4.11 Credentials y Certifications (Docs 172-174)

| Doc | Titulo | Modulo | Estado | Notas |
|-----|--------|--------|--------|-------|
| 172 | Credentials System | `jaraba_credentials` | Implementado (85%) | CryptographyService, OpenBadgeBuilder, CredentialVerifier |
| 173 | Stackable Extensions | `jaraba_credentials` | **Pendiente** | Credenciales acumulables |
| 174 | Cross-Vertical Recognition | `jaraba_credentials` | **Pendiente** | Reconocimiento inter-vertical |

### 4.12 Analytics y Observabilidad (Docs 179-180)

| Doc | Titulo | Modulo | Estado | Gap |
|-----|--------|--------|--------|-----|
| 179a | Search Console Integration | `jaraba_analytics` | **Pendiente** | OAuth2 GSC API |
| 179a | Core Web Vitals RUM | `jaraba_analytics` | **Pendiente** | web-vitals.js + Beacon API |
| 179a | Error Tracking | `jaraba_analytics` | **Pendiente** | JS + PHP error handler |
| 179a | Uptime Monitor | `jaraba_analytics` | **Pendiente** | Health endpoint monitoring |
| 180 | Heatmap Click | `jaraba_heatmap` | Parcial | Collector ok, Canvas rendering pendiente |
| 180 | Heatmap Scroll | `jaraba_heatmap` | Parcial | Aggregator ok, visualizacion pendiente |
| 180 | Session Recording | `jaraba_heatmap` | **Pendiente** | No implementado |
| 201b-179 | Funding Intelligence | — | **No existe** | 520-680h, nuevo modulo |

---

## 5. Plan de Implementacion por Fases

### 5.1 Fase 0 — Consolidacion Critica (P0, 1-2 semanas)

**Objetivo:** Resolver incoherencias criticas detectadas en la auditoria del 2026-02-11.

> **VERIFICACION PRE-IMPLEMENTACION (2026-02-11 19:30):**
> Se ha verificado directamente en el codigo fuente el estado real de cada tarea.
> Varios items ya fueron completados por otros equipos o en sesiones anteriores.

| # | Tarea | Horas | Estado Verificado | Accion |
|---|-------|-------|-------------------|--------|
| 0.1 | **Consolidar Stripe Connect** | 4-8h | PARCIAL (80%) — `JarabaStripeConnect` marcado deprecated, AgroConecta ya usa `jaraba_foc.stripe_connect`. Pero: (a) `jaraba_mentoring` tiene StripeConnectService independiente sin migrar, (b) AgroConecta llama metodos `protected` de FOC, (c) `jaraba_foc` entero NO esta committed en git | Completar: hacer publicos `getSecretKey()`/`stripeRequest()` en FOC, migrar mentoring, hacer commit de jaraba_foc |
| 0.2 | **PHPUnit tests** | 8-12h | PARCIAL (60%) — Ya existen **123 test methods** (104 Unit + 13 Kernel + 6 Functional) en 23 archivos. Tests para PlanValidator(8), TenantContext(9), TenantManager(8) YA EXISTEN. Falta: tests para StripeConnectService y Consent | Crear tests para StripeConnectService (FOC), ampliar cobertura Kernel |
| 0.3 | ~~Verificar Consent Manager~~ | 0h | **COMPLETADO** — Implementacion end-to-end: ConsentRecord entity (11 campos), ConsentService (grant/revoke/check), 3 endpoints REST API, JS banner (320 lineas), SCSS integrado, zero-block pattern, multi-tenant, GDPR compliant | ~~Ninguna~~ — Ya production-ready |
| 0.4 | ~~Migrar billing state~~ | 0h | **COMPLETADO** — Campos `grace_period_ends`, `cancel_at`, `subscription_status`, `trial_ends`, `current_period_end` ya son base fields de Tenant entity. Update hook 9011 migro datos de State API y elimino claves residuales. TenantSubscriptionService usa campos de entidad | ~~Ninguna~~ — Ya migrado y funcional |
| 0.5 | **Limpiar CSS tema** | 1-2h | MENOR — `libraries.yml` carga correctamente `ecosistema-jaraba-theme.css`. Existe `main.css` huerfano (521K) que debe eliminarse. Build script ya apunta al archivo correcto | Eliminar `main.css` + `main.css.map` huerfanos, verificar |
| 0.6 | **Commit codigo no trackeado** | 2-4h | **CRITICO** — `jaraba_foc` (modulo entero), `jaraba_analytics` (modulo entero) y 43 servicios nuevos en `ecosistema_jaraba_core` NO estan committed. 5 ramas `claude/*` locales con trabajo potencialmente util. **ATENCION v2.2.0:** El equipo paralelo tambien tiene codigo no committed (`jaraba_events`, `jaraba_customer_success`, expansiones `jaraba_tenant_knowledge`, PricingRule). Coordinar antes de hacer commit masivo | Revisar ramas, hacer commit de modulos estables **tras coordinacion con equipo paralelo** |

**Subtotal Fase 0 (actualizado):** 15-26h (reduccion del 60% respecto a estimacion original gracias a verificacion)

### 5.2 Fase 1 — Completar Modulos Parciales (P1, 4-6 semanas)

**Objetivo:** Llevar todos los modulos parciales al 100% de su especificacion.

| # | Modulo | Tareas | Horas | Specs |
|---|--------|--------|-------|-------|
| 1.1 | `jaraba_heatmap` | Canvas rendering engine, scroll depth visualization, dashboard frontend con filtros | 30-40h | Doc 180 |
| 1.2 | `jaraba_analytics` | Core Web Vitals (web-vitals.js + Beacon API), Google Search Console (OAuth2), error tracking (JS + PHP), uptime monitoring | 60-90h | Doc 179a |
| 1.3 | `jaraba_pixels` | Dashboard estadisticas, configuracion avanzada por plataforma, integracion consent manager | 30-40h | — |
| 1.4 | `jaraba_geo` | FAQ endpoint REST, generador automatico structured data para entidades, dashboard GEO con metricas | 20-30h | Doc 164 |
| 1.5 | `jaraba_self_discovery` | Entidades LifeTimeline, InterestProfile (RIASEC), StrengthAssessment, visualizaciones radar/hexagono con Chart.js | 40-60h | Doc 125-160 |
| 1.6 | `jaraba_performance` | Lazy loading service, Core Web Vitals monitoring endpoint, admin dashboard, preload hints generator | 40-50h | — |
| 1.7 | `jaraba_i18n` | Dashboard progreso traducciones, auto-deteccion textos sin traducir, export/import PO files | 15-20h | Doc 166 |
| 1.8 | `jaraba_theming` | Preview en tiempo real, export/import temas, presets automaticos on tenant create | 20-30h | — |

**Subtotal Fase 1:** 255-360h

**Patron de implementacion para cada modulo de Fase 1:**

Para cada modulo, el equipo tecnico debe:

1. **Verificar parciales existentes** — Consultar inventario Seccion 3.5 antes de crear templates
2. **Crear page--*.html.twig limpia** si el modulo necesita dashboard frontend
3. **Registrar body class** en `hook_preprocess_html()` existente (NO crear nueva funcion)
4. **SCSS via `var(--ej-*)`** con fallback, compilar en Docker
5. **Content Entities con Field UI** y 4 YAML obligatorios
6. **CRUD en slide-panel** para operaciones desde frontend
7. **Tests PHPUnit** para servicios criticos
8. **i18n** en todas las capas (PHP, Twig, JS)

### 5.3 Fase 2 — Verticales Pendientes (P1-P2, Q1-Q2 2026)

#### 5.3.1 AgroConecta Fases 5-9

| Sprint | Feature | Horas | Dependencias |
|--------|---------|-------|--------------|
| AC5-1/2 | Search & Discovery (Faceted search, Elasticsearch/Views) | 40-60h | Doc 55 |
| AC5-3/4 | Promotions & Coupons (Commerce Promotion module) | 30-40h | Doc 56 |
| AC6-1/2 | Shipping Core (zonas, tarifas, tracking) | 50-70h | Doc 51 |
| AC6-3/4 | Traceability + QR (hash blockchain, QR dinamico) | 50-70h | Docs 80-81 |
| AC7-1/2 | Analytics Dashboard (metricas productor, ventas, tendencias) | 40-50h | Doc 57 |
| AC7-3/4 | Producer Copilot (RAG + 6 capacidades IA) | 60-80h | Doc 67 |
| AC7-5/6 | Sales Agent (asistente ventas IA) | 40-60h | Doc 68 |
| AC8-1/2 | Admin Panel (gestion global marketplace) | 40-50h | Doc 58 |
| AC9-1/2 | Mobile PWA | 60-80h | — |

**Subtotal AgroConecta F5-9:** 410-560h

#### 5.3.2 ComercioConecta Fases 1-4

**Estrategia:** Reutilizar 70% de `jaraba_agroconecta_core`.

| Fase | Features | Horas | Reutilizacion |
|------|----------|-------|---------------|
| CC-F1 | Commerce Core + Catalog + MerchantProfile | 80-120h | 70% AgroConecta |
| CC-F2 | Orders + Checkout + Payments | 60-80h | 80% AgroConecta |
| CC-F3 | Portals + Notifications | 50-70h | 60% AgroConecta |
| CC-F4 | Analytics + Admin | 40-60h | 50% AgroConecta |

**Subtotal ComercioConecta:** 230-330h

#### 5.3.3 ServiciosConecta Fases 2+

| Fase | Features | Horas | Notas |
|------|----------|-------|-------|
| SC-F2 | Calendar Sync + Video (Jitsi/Daily) | 60-80h | API calendar + WebRTC |
| SC-F3 | Digital Signing (PAdES) + Encrypted Inbox | 50-70h | FNMT/AutoFirma integracion |
| SC-F4 | IA Triage + Auto-Quoter | 40-60h | Copilot especializado |
| SC-F5 | Services Copilot + Dashboards | 50-60h | RAG + analytics |
| SC-F6 | Invoicing + Case Management | 40-60h | Facturacion basica |

**Subtotal ServiciosConecta F2+:** 240-330h

### 5.4 Fase 3 — Platform Services (P2, Q2-Q3 2026)

| # | Feature | Modulo | Horas | Spec |
|---|---------|--------|-------|------|
| 3.1 | **Integration Marketplace** (OAuth2, app store, 50+ connectors) | `jaraba_integrations` | 215-295h | Doc 112 |
| ~~3.2~~ | ~~**Customer Success**~~ | ~~`jaraba_customer_success`~~ | ~~190-270h~~ **0h** | ~~Doc 113~~ **COMPLETADO por equipo paralelo (v2.2.0)** — 5 entidades (CustomerHealth, ChurnPrediction, CsPlaybook, ExpansionSignal, PlaybookExecution), 6 servicios (EngagementScoring, NPS, LifecycleStage, HealthScoreCalculator, ChurnPrediction AI, PlaybookExecutor), 3 controllers |
| 3.3 | **White-Label** (DNS automation, SSL, custom domain) | `ecosistema_jaraba_core` | 90-130h | Doc 117 |
| 3.4 | **AI Agent Visual Builder** | `jaraba_ai_agents` | 40-60h | Doc 108 |
| 3.5 | **PWA Mobile** (mobile UI, offline sync, iOS) | Core + Tema | 65-90h | Doc 109 |
| 3.6 | **Advanced Analytics** (cohortes, funnels, retention) | `jaraba_analytics` | 65-90h | Doc 116 |

**Subtotal Fase 3 (actualizado v2.2.0):** 475-665h (reduccion -190-270h gracias a completar Customer Success por equipo paralelo)

### 5.5 Fase 4 — Infraestructura Production-Grade (P2, Q2-Q3 2026)

| # | Feature | Horas | Spec |
|---|---------|-------|------|
| 4.1 | **Billing completo** (Invoice entity, Stripe sync, Customer Portal, Tax) | 150-200h | Doc 134 |
| 4.2 | **Deployment** (Docker compose prod, IONOS, Traefik) | 60-80h | Doc 131 |
| 4.3 | **CI/CD** (GitHub Actions, PHPStan, ESLint, deploy) | 40-60h | Doc 132 |
| 4.4 | **Monitoring** (Prometheus, Grafana, Loki, AlertManager) | 40-50h | Doc 133 |
| 4.5 | **PHPUnit >= 80% cobertura** | 80-120h | Doc 135 |
| 4.6 | **Security Audit + GDPR** | 40-60h | Doc 138 |
| 4.7 | **Go-Live Runbook** | 20-30h | Doc 139 |

**Subtotal Fase 4:** 430-600h

### 5.6 Fase 5 — Nuevas Funcionalidades (P3, Q3-Q4 2026)

| # | Feature | Modulo | Horas | Spec |
|---|---------|--------|-------|------|
| 5.1 | Credentials Stackable Extensions | `jaraba_credentials` | 40-60h | Doc 173 |
| 5.2 | Credentials Cross-Vertical | `jaraba_credentials` | 30-50h | Doc 174 |
| 5.3 | Funding Intelligence (BDNS/BOJA/BOE) | Nuevo modulo | 520-680h | Doc 201b-179 |
| 5.4 | Email Templates MJML | `jaraba_email` | 30-40h | Doc 136 |
| 5.5 | API Gateway & Developer Portal | `jaraba_integrations` | 60-80h | Doc 137 |
| 5.6 | User Manuals & Videos | Documentacion | 40-60h | Doc 140 |

**Subtotal Fase 5:** 720-970h

---

## 6. Arquitectura Frontend — Patron de Implementacion

### 6.1 Estructura de Pagina Limpia

Toda pagina visible por el usuario del SaaS (no admin Drupal) sigue este patron sin excepciones:

```
+----------------------------------------------------------+
|                    HEADER (parcial)                       |
|  Logo | Navegacion configurable | CTA | Mobile menu      |
+----------------------------------------------------------+
|                                                          |
|                    MAIN (full-width)                      |
|  <main id="main-content" class="{tipo}-main">            |
|    <div class="{tipo}-wrapper">                          |
|      {{ page.content }} ← Render array del controller    |
|    </div>                                                |
|  </main>                                                 |
|                                                          |
+----------------------------------------------------------+
|                    FOOTER (parcial)                       |
|  Columnas nav | Redes sociales | Copyright configurable  |
+----------------------------------------------------------+
```

**Caracteristicas obligatorias:**
- **Full-width layout** — Sin sidebar
- **Mobile-first** — Breakpoints: xs(480), sm(640), md(768), lg(992), xl(1200), 2xl(1440)
- **Sin regiones Drupal** — No `page.sidebar_first`, no bloques heredados
- **Header/Footer via `{% include %}`** — Parciales reutilizables con config de tema
- **Skip link accesible** — `<a href="#main-content" class="visually-hidden focusable">`

### 6.2 Inventario de Parciales Existentes

Antes de crear cualquier componente nuevo, consultar primero si ya existe un parcial. Referencia completa en Seccion 3.5.

### 6.3 Configuracion del Tema via UI de Drupal

**Ruta admin:** `/admin/appearance/settings/ecosistema_jaraba_theme`

El tema ya dispone de 70+ configuraciones distribuidas en 13 pestanas (detalle en Seccion 3.3). El header, footer, hero, cards, tipografia, colores, dark mode, breadcrumbs, banner promo y presets de industria son **100% configurables sin tocar codigo**.

**Para el footer** especificamente:
- 4 variantes de layout (minimal, standard, mega, split)
- 3 columnas de navegacion con titulo + enlaces (formato "Texto|URL" por linea)
- 4 widgets para mega footer
- 5 URLs de redes sociales
- Texto de copyright con placeholder `[year]`
- Toggle "Powered by Jaraba"

**Para el header:**
- 5 variantes de layout (classic, centered, hero, split, minimal)
- Items de navegacion configurables (formato "Texto|URL" por linea)
- CTA button (texto, URL, enable/disable)
- Sticky header toggle
- Colores fondo y texto

**Para configuracion por tenant** (adicional al tema):
- `jaraba_theming` > `TenantThemeCustomizerForm` (800+ lineas) permite personalizar 9 colores, 9 tipografias, dark mode y custom CSS por tenant individual.

### 6.4 Creacion de Nueva Pagina Frontend (Procedimiento)

Cuando se necesita una nueva pagina frontend (por ejemplo para un nuevo modulo):

**Paso 1:** Definir la ruta en `*.routing.yml` del modulo

```yaml
mi_modulo.dashboard:
  path: '/mi-modulo'
  defaults:
    _controller: '\Drupal\mi_modulo\Controller\MiModuloController::dashboard'
    _title: 'Mi Modulo Dashboard'
  requirements:
    _permission: 'access mi_modulo'
```

**Paso 2:** Crear template `page--mi-modulo.html.twig` en el tema

Copiar el patron de Seccion 3.4, reemplazando `{tipo}` por el nombre del modulo.

**Paso 3:** Registrar body classes en `hook_preprocess_html()` existente

```php
// En ecosistema_jaraba_theme.theme, dentro de la funcion EXISTENTE
// NO crear nueva funcion, anadir al bloque de deteccion de rutas
if (str_starts_with($route, 'mi_modulo.')) {
    $variables['attributes']['class'][] = 'page-mi-modulo';
    $variables['attributes']['class'][] = 'dashboard-page';
}
```

**Paso 4:** Crear SCSS parcial para estilos del modulo

```scss
// web/modules/custom/mi_modulo/scss/_mi-modulo-dashboard.scss
@use 'sass:color';

.page-mi-modulo {
    min-height: 100vh;
    background: var(--ej-bg-body, #F8FAFC);
}

.mi-modulo-main {
    width: 100%;
    min-height: calc(100vh - 160px);
}

.mi-modulo-wrapper {
    max-width: 1400px;
    margin-inline: auto;
    padding: var(--ej-spacing-xl, 2rem) var(--ej-spacing-lg, 1.5rem);

    @media (max-width: 767px) {
        padding: var(--ej-spacing-lg, 1.5rem) var(--ej-spacing-md, 1rem);
    }
}
```

**Paso 5:** Registrar libreria en `mi_modulo.libraries.yml`

```yaml
mi-modulo:
  css:
    component:
      css/mi-modulo.css: {}
  dependencies:
    - ecosistema_jaraba_theme/global-styling
    - ecosistema_jaraba_theme/slide-panel
```

**Paso 6:** Compilar y verificar

```bash
docker exec jarabasaas_appserver_1 bash -c \
  "cd /app/web/modules/custom/mi_modulo && npx sass scss/main.scss css/mi-modulo.css --style=compressed"
docker exec jarabasaas_appserver_1 drush cr
```

### 6.5 Patron Slide-Panel para CRUD

Toda accion de crear/editar/ver en pagina frontend:

```html
<!-- Boton en la pagina -->
<button data-slide-panel="mi-entity-add"
        data-slide-panel-url="/mi-modulo/ajax/mi-entity/add"
        data-slide-panel-title="{% trans %}Crear Elemento{% endtrans %}"
        class="btn btn--primary">
  {% trans %}Crear{% endtrans %}
</button>

<!-- El slide-panel JS (ecosistema_jaraba_theme/slide-panel) se encarga automaticamente -->
```

**El controller DEBE detectar AJAX** (ver Seccion 3.6 para codigo completo).

---

## 7. Arquitectura SCSS — Implementacion de Referencia

### 7.1 Jerarquia de 5 Capas

```
Capa 1: _variables.scss (ecosistema_jaraba_core)     ← Fallbacks compilacion
Capa 2: _injectable.scss (ecosistema_jaraba_core)     ← CSS Custom Properties :root
Capa 3: Parciales SCSS componente                     ← Scope local componente
Capa 4: hook_preprocess_html() (tenant override)      ← Runtime inyeccion por tenant
Capa 5: StylePreset Config Entity (vertical preset)   ← Paleta por vertical

Resolucion CSS: :root(L2) → .component(L3) → [data-tenant](L4) → .vertical-*(L5)
```

### 7.2 SSOT: Archivos Fuente de Verdad

| Archivo | Ubicacion | Proposito |
|---------|-----------|-----------|
| `_variables.scss` | `ecosistema_jaraba_core/scss/` | Paleta Jaraba completa, spacing, shadows |
| `_injectable.scss` | `ecosistema_jaraba_core/scss/` | CSS Custom Properties inyectables |
| `_mixins.scss` | `ecosistema_jaraba_core/scss/` | Mixins reutilizables: `css-var()`, `respond-to()` |

**Mixin obligatorio para propiedades CSS:**

```scss
/// Aplica propiedad CSS usando variable inyectable con fallback
/// @param {String} $property — Propiedad CSS (color, background, etc.)
/// @param {String} $var-name — Nombre variable sin prefijo --ej-
/// @param {*} $fallback — Valor SCSS fallback
@mixin css-var($property, $var-name, $fallback) {
    #{$property}: var(--ej-#{$var-name}, $fallback);
}
```

### 7.3 Patron de Compilacion

**14 modulos con `package.json`** (verificado):
ecosistema_jaraba_core, ecosistema_jaraba_theme, jaraba_agroconecta_core, jaraba_candidate, jaraba_comercio_conecta, jaraba_credentials, jaraba_foc, jaraba_i18n, jaraba_interactive, jaraba_page_builder, jaraba_self_discovery, jaraba_servicios_conecta, jaraba_site_builder, jaraba_social

### 7.4 Patron de Modulo Satelite

Todo modulo SCSS que NO sea `ecosistema_jaraba_core`:

```scss
// NO definir variables SCSS propias
// SOLO consumir CSS Custom Properties con fallback inline
.mi-componente {
    color: var(--ej-color-corporate, #233D63);
    background: var(--ej-bg-surface, #ffffff);
    padding: var(--ej-spacing-md, 1rem);
    border-radius: var(--ej-radius-xl, 16px);
}
```

---

## 8. Arquitectura de Entidades de Contenido

### 8.1 Checklist Obligatorio por Entidad

Para CADA nueva Content Entity, verificar:

- [ ] Anotacion `@ContentEntityType` completa con todos los handlers
- [ ] `list_builder` → `MiEntidadListBuilder`
- [ ] `views_data` → `Drupal\views\EntityViewsData`
- [ ] `form` handlers: default, add, edit, delete
- [ ] `access` handler
- [ ] `route_provider` → `AdminHtmlRouteProvider`
- [ ] `field_ui_base_route` apuntando a settings
- [ ] Links: canonical, add-form, edit-form, delete-form, collection
- [ ] Campos `setDisplayConfigurable('form', TRUE)` y `setDisplayConfigurable('view', TRUE)`

### 8.2 Estructura de Navegacion Drupal

| Tipo Entidad | Ubicacion Admin | Ejemplo URL |
|-------------|-----------------|-------------|
| Content Entities (datos usuario) | `/admin/content` (tab) | `/admin/content/courses` |
| Config Entities (tipos, vocab) | `/admin/structure` (menu) | `/admin/structure/saas-plan` |
| Module Settings (config, API keys) | `/admin/config` (formulario) | `/admin/config/empleabilidad/lms/settings` |

### 8.3 Los 4 YAML Obligatorios

**Sin estos 4 archivos, la entidad NO aparece en la navegacion admin:**

**1. `*.routing.yml`** (URLs de la entidad):
```yaml
entity.mi_entidad.collection:
  path: '/admin/content/mi-entidades'
  defaults:
    _entity_list: 'mi_entidad'
  requirements:
    _permission: 'access mi_entidad overview'
```

**2. `*.links.menu.yml`** (Menu en /admin/structure):
```yaml
mimodulo.mi_entidad:
  title: 'Mi Entidad'
  description: 'Gestionar mis entidades'
  parent: system.admin_structure
  route_name: entity.mi_entidad.settings
  weight: 20
```

**3. `*.links.task.yml`** (Tab en /admin/content):
```yaml
entity.mi_entidad.collection:
  title: 'Mi Entidad'
  route_name: entity.mi_entidad.collection
  base_route: system.admin_content
  weight: 20
```

**4. `*.links.action.yml`** (Boton "+ Anadir"):
```yaml
entity.mi_entidad.add_form:
  title: 'Anadir Mi Entidad'
  route_name: entity.mi_entidad.add_form
  appears_on:
    - entity.mi_entidad.collection
```

---

## 9. Testing y Calidad

**Estado actual (corregido v2.1.0):** 123 test methods en 23 archivos (104 Unit + 13 Kernel + 6 Functional). Tests existentes para PlanValidator(8), TenantContext(9), TenantManager(8), SaasPlan(14), Vertical(12), entre otros. CI no configurado aun.

**Objetivo:** >= 80% cobertura para servicios criticos.

**Prioridad de tests:**

| Servicio | Modulo | Tipo Test | Prioridad |
|----------|--------|-----------|-----------|
| PlanValidator | ecosistema_jaraba_core | Unit | P0 |
| TenantContextService | ecosistema_jaraba_core | Unit | P0 |
| StripeConnectService | jaraba_foc | Unit + Integration | P0 |
| FinOpsTrackingService | ecosistema_jaraba_core | Unit | P0 |
| CopilotOrchestratorService | jaraba_copilot_v2 | Unit | P1 |
| JarabaRagService | jaraba_rag | Integration | P1 |
| CredentialIssuer | jaraba_credentials | Unit | P1 |
| ExperimentService | jaraba_page_builder | Unit | P2 |

**Cypress E2E** (ya configurado para): auth, homepage, theming, components, verticales.

**Ejecutar tests en Docker:**
```bash
docker exec jarabasaas_appserver_1 ./vendor/bin/phpunit -c phpunit.xml
```

---

## 10. Aprendizajes Criticos Documentados

Estos aprendizajes son fruto de errores reales cometidos durante el desarrollo. Su incumplimiento causara los mismos problemas:

| Fecha | Aprendizaje | Consecuencia si se ignora |
|-------|-------------|---------------------------|
| 2026-02-09 | Dart Sass `@use` crea modulos aislados. Cada parcial DEBE declarar sus propios imports | Variables indefinidas, errores de compilacion |
| 2026-02-05 | El tema carga `css/main.css`, NO `css/ecosistema-jaraba-theme.css` | Estilos no aparecen |
| 2026-02-05 | Premium cards deben usar `cubic-bezier(0.175, 0.885, 0.32, 1.275)` | Hover inconsistente |
| 2026-01-29 | `$element['format']['#access'] = FALSE` para ocultar formato (PHP, no CSS) | UI ruidosa en slide-panels |
| 2026-01-29 | `Drupal.detachBehaviors()` antes de limpiar panel body | Acumulacion de scripts admin |
| 2026-01-25 | Usar `getStorage()/setStorage()` en forms multi-step, NO `set()` | Datos perdidos entre pasos |
| 2026-01-25 | Variables DEBEN anadirse explicitamente al render array | Templates sin datos |
| 2026-01-22 | SVG: crear AMBAS versiones (outline + duotone) | Iconos inconsistentes |
| 2026-01-20 | NVM en WSL: npm de Windows puede interferir. Cargar NVM manualmente | Compilacion SCSS falla |
| 2026-01-19 | Content Entity requiere 4 YAML (routing, menu, task, action) | Entidad invisible en admin |
| 2026-01-13 | Traducir TODAS las abreviaturas y unidades | UI no traducible |
| 2026-01-13 | En dark mode, especificar color de texto explicitamente | Texto invisible |
| 2026-01-13 | Cambios SCSS no aparecen hasta compilar Y limpiar cache Drupal | "No funciona" falso |

---

## 11. Estimaciones y Roadmap

### Resumen por Fase

| Fase | Periodo | Horas Min | Horas Max | Prioridad | Cambio v2.2.0 |
|------|---------|-----------|-----------|-----------|---------------|
| Fase 0 — Consolidacion Critica | Semanas 1-2 | 15h | 26h | P0 | Sin cambio |
| Fase 1 — Completar Parciales | Semanas 3-8 | 255h | 360h | P1 | Sin cambio |
| Fase 2 — Verticales | Q1-Q2 2026 | 880h | 1,220h | P1-P2 | Sin cambio |
| Fase 3 — Platform Services | Q2-Q3 2026 | **475h** | **665h** | P2 | **-190-270h** (Customer Success completado) |
| Fase 4 — Infraestructura | Q2-Q3 2026 | 430h | 600h | P2 | Sin cambio |
| Fase 5 — Nuevas Funcionalidades | Q3-Q4 2026 | 720h | 970h | P3 | Sin cambio |
| **TOTAL** | **12-18 meses** | **2,775h** | **3,841h** | — | **Reduccion -190-270h** |

### Dependencias Criticas

```
Fase 0 (Consolidacion)
  ├── Fase 1 (Parciales) ← Depende de: tests basicos, Stripe unificado
  ├── Fase 4 (Infra) ← Depende de: CI/CD, billing completo
  │     └── Go-Live ← Depende de: Fase 4 completa
  │
  ├── Fase 2 (Verticales) ← Puede ejecutarse en paralelo con Fase 1
  │     ├── AgroConecta F5-9
  │     ├── ComercioConecta F1-4 ← Depende de: AgroConecta F1-4 (reutilizacion)
  │     └── ServiciosConecta F2+ ← Independiente
  │
  ├── Fase 3 (Platform) ← Puede ejecutarse en paralelo con Fase 2
  │     ├── Integration Marketplace ← Independiente
  │     ├── ~~Customer Success~~ ← COMPLETADO por equipo paralelo (v2.2.0)
  │     └── White-Label ← Depende de: DNS infrastructure
  │
  └── Fase 5 (Nuevas) ← Depende de: Fase 1 + Fase 4 minimo
        └── Funding Intelligence ← Puede iniciarse independientemente
```

---

## 12. Registro de Cambios

| Fecha | Version | Descripcion |
|-------|---------|-------------|
| 2026-02-11 | 2.0.0 | Creacion del plan maestro integral v2, auditoria exhaustiva de 44 modulos, 170+ specs, 16 workflows. Verificacion directa de codigo fuente. Incluye 19 directrices obligatorias, tabla de correspondencia completa, 6 fases de implementacion, y patrones de arquitectura frontend/SCSS/entidades |
| 2026-02-11 | 2.1.0 | **Verificacion pre-implementacion Fase 0.** Se descubre que: (1) Consent Manager YA ESTA completamente implementado (ConsentRecord, ConsentService, API REST, JS banner, SCSS) — tarea 0.3 eliminada; (2) Billing state migration YA ESTA completada (update hook 9011, campos en Tenant entity) — tarea 0.4 eliminada; (3) PHPUnit tests parcialmente existentes (123 methods en 23 archivos vs 0 reportados en auditoria anterior); (4) Stripe Connect consolidacion al 80% (deprecacion, migracion AgroConecta hechas; falta mentoring y commit); (5) CSS duality es solo cleanup menor; (6) Se anade tarea CRITICA 0.6: hacer commit de jaraba_foc, jaraba_analytics y 43 servicios no trackeados. Subtotal Fase 0 reducido de 38-64h a 15-26h |
| 2026-02-11 | 2.2.0 | **Coordinacion con equipo paralelo (20260210-Plan_Implementacion_Integral_SaaS_v2.md).** Verificacion directa en codigo fuente revela 4 funcionalidades completadas por el otro equipo: (1) **`jaraba_customer_success` COMPLETADO al 100%** — 26 archivos, 5 entidades (CustomerHealth, ChurnPrediction, CsPlaybook, ExpansionSignal, PlaybookExecution), 6 servicios (EngagementScoring, NpsSurvey, LifecycleStage, HealthScoreCalculator, ChurnPrediction con @ai.provider, PlaybookExecutor), 3 controllers. Tarea 3.2 de Fase 3 eliminada (-190-270h); (2) **`jaraba_events` NUEVO modulo completo** — 18 archivos, 2 entidades (MarketingEvent, EventRegistration), 3 servicios, 2 controllers. No previsto en specs originales, anadido al inventario como modulo #29; (3) **`jaraba_tenant_knowledge` AMPLIADO** — nuevos HelpCenterController (/ayuda), TenantFaq entity (FAQ semanticas con Qdrant), TenantPolicy entity (politicas versionadas), KnowledgeRevisionService. Gap Doc 114 reducido de 40-55h a 10-15h; (4) **PricingRule + UsageDashboard COMPLETADOS** — PricingRule entity (4 modelos: flat, tiered, volume, package), PricingRuleEngine con cascade resolution, UsageDashboardController en /mi-cuenta/uso. Gap Doc 111 reducido de 30-45h a 10-15h. **Total reduccion v2.2.0: -250-340h.** Regla de no-colision establecida para evitar conflictos con equipo paralelo |

---

*Este documento es la fuente de verdad para la planificacion de implementacion del SaaS Jaraba Impact Platform. Toda nueva funcionalidad DEBE ser cruzada contra la tabla de correspondencia (Seccion 4) y las directrices de obligado cumplimiento (Seccion 3) antes de iniciar desarrollo.*
